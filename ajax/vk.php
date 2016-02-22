<?php
switch(@$_POST['op']) {
	case 'cache_clear':
		if(!SA)
			jsonError();

		_globalCacheClear();
		_globalJsValues();
		_wsJsValues();

		_cacheClear();//todo ��� ��������

		//���������� �������� ���� ���������� ���� ����������
		$sql = "UPDATE `_setup_global` SET `value`=`value`+1";
		query($sql, GLOBAL_MYSQL_CONNECT);

		//���������� �������� js ���� �����������
		$sql = "UPDATE `_ws` SET `js_values`=`js_values`+1";
		query($sql, GLOBAL_MYSQL_CONNECT);

		jsonSuccess();
		break;
	case 'cookie_clear':
		if(!empty($_COOKIE))
			foreach($_COOKIE as $key => $val)
				setcookie($key, '', time() - 3600, '/');
		jsonSuccess();
		break;

	case 'debug_sql':
		if(!DEBUG)
			jsonError();

		$nocache = _bool($_POST['nocache']);
		$explain = _bool($_POST['explain']);

		$sql = ($explain ? 'EXPLAIN ' : '').trim($_POST['query']);
		$q = query($sql, GLOBAL_MYSQL_CONNECT);

		if($nocache)
			$sql = preg_replace('/SELECT/', 'SELECT NO_SQL_CACHE', $sql);

		if($explain) {
			$exp = '<table>';
			$n = 1;
			while($r = mysql_fetch_assoc($q)) {
				$exp .= '<tr>';
				if($n++ == 1) {
					foreach($r as $i => $v)
						$exp .= '<th>'.$i;
					$exp .= '<tr>';
				}
				foreach($r as $v)
					$exp .= '<td>'.$v;
			}
			$exp .= '<table>';
			$send['exp'] = $exp;
		}

		$send['query'] = $sql;
		$send['html'] =
			//'rows: <b>'.$q['rows'].'</b>, '.
			'time: '.$q['time'];
		jsonSuccess($send);
		break;
	case 'debug_cookie':
		if(!DEBUG)
			jsonError();
		$send['html'] = _debug_cookie();
		jsonSuccess($send);
		break;

	case 'sort':
		if(!preg_match(REGEXP_MYSQLTABLE, $_POST['table']))
			jsonError();

		$table = htmlspecialchars(trim($_POST['table']));
		$conn = 0;

		$sql = "SHOW TABLES LIKE '".$table."'";
		if(!mysql_num_rows(query($sql)))
			if(mysql_num_rows(query($sql, GLOBAL_MYSQL_CONNECT)))
				$conn = GLOBAL_MYSQL_CONNECT;
			else
				jsonError();

		$sort = explode(',', $_POST['ids']);
		if(empty($sort))
			jsonError();
		for($n = 0; $n < count($sort); $n++)
			if(!preg_match(REGEXP_NUMERIC, $sort[$n]))
				jsonError();

		for($n = 0; $n < count($sort); $n++) {
			$sql = "UPDATE `".$table."` SET `sort`=".$n." WHERE `id`=".intval($sort[$n]);
			query($sql, $conn);

		}

		_globalCacheClear();
		_cacheClear();
		_wsJsValues();

		jsonSuccess();
		break;

	case 'attach_upload':
		/*
			������������ ������
			1 - �������
			2 - �������� ������
			3 - ��������� �� �������
		*/

		$f = $_FILES['f1'];
		switch($f['type']) {
			case 'application/pdf':             //pdf
			case 'application/rtf':             //rtf
			case 'application/msword':          //doc
			case 'application/vnd.ms-excel':    //xls
			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':       //xlsx
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document': //docx
				break;
			default: setcookie('_attached', 2, time() + 3600, '/'); exit;
		}
		$dir = ATTACH_PATH.'/ws_'.WS_ID;
		if(!is_dir($dir))
			mkdir($dir, 0777, true);
		$fname = time().'_'.translit(trim($f['name'])); //��� �����, ����������� �� ����
		if(move_uploaded_file($f['tmp_name'], $dir.'/'.$fname)) {
			$sql = "INSERT INTO `_attach` (
						`app_id`,
						`ws_id`,
						`name`,
						`size`,
						`link`,
						`viewer_id_add`
					) VALUES (
						".APP_ID.",
						".WS_ID.",
						'".addslashes(trim($f['name']))."',
						".$f['size'].",
						'".addslashes(ATTACH_HTML.'/ws_'.WS_ID.'/'.$fname)."',
						".VIEWER_ID."
					)";
			query($sql, GLOBAL_MYSQL_CONNECT);

			$id = query_insert_id('_attach', GLOBAL_MYSQL_CONNECT);

			setcookie('_attached', 1, time() + 3600, '/');
			setcookie('_attached_id', $id, time() + 3600, '/');
			exit;
		}
		setcookie('_attached', 3, time() + 3600, '/');
		exit;
	case 'attach_get'://��������� ������ � ����� ����� ��� ��������
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_attach`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$send['name'] = utf8($r['name']);
		$send['size'] = round($r['size'] / 1024);
		jsonSuccess($send);
		break;
	case 'attach_save'://���������� ����� ����� ��� ��������
		if(!$id = _num($_POST['id']))
			jsonError();

		if($zayav_id = _num($_POST['zayav_id']))
			if(!$z = _zayavQuery($zayav_id))
				jsonError();

		$zayav_save = _bool($_POST['zayav_save']);
		$name = _txt($_POST['name']);

		$sql = "SELECT *
				FROM `_attach`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_attach`
				SET `name`='".addslashes($name)."',
					`zayav_id`=".$zayav_id."
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		_history(array(
			'type_id' => 85,
			'attach_id' => $id,
			'zayav_id' => $zayav_id
		));

		if($zayav_save && $zayav_id) {
			$sql = "UPDATE `_zayav`
					SET `attach_id`=".$id."
					WHERE `id`=".$zayav_id;
			query($sql, GLOBAL_MYSQL_CONNECT);
		}

		$send['arr'] = _attachArr($id);
		jsonSuccess($send);
		break;
	case 'attach_edit'://�������������� ������ �����
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);

		$sql = "SELECT *
				FROM `_attach`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError('���� ��� �����');

		$sql = "UPDATE `_attach`
				SET `name`='".addslashes($name)."'
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		jsonSuccess();
		break;
	case 'attach_del'://�������� �����
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_attach`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError('���� ��� ��� �����');

		$sql = "UPDATE `_attach`
				SET `deleted`=1,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		//�������� �� ��������
		$sql = "UPDATE `_money_expense`
				SET `attach_id`=0
				WHERE `attach_id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		//�������� �� ������
		$sql = "UPDATE `_zayav`
				SET `attach_id`=0
				WHERE `attach_id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		//�������� �� �������� �� ������
		$sql = "UPDATE `_zayav_expense`
				SET `attach_id`=0
				WHERE `attach_id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		_history(array(
			'type_id' => 86,
			'attach_id' => $id,
			'zayav_id' => $r['zayav_id']
		));

		jsonSuccess();
		break;

	case 'note_add':
		if(!$page_id = _num($_POST['page_id']))
			jsonError();

		$page_name = _txt($_POST['page_name']);

		if(!_note(array(
			'add' => 1,
			'p' => $page_name,
			'id' => $page_id,
			'txt' => _txt($_POST['txt'])
		)))
			jsonError();


		$sql = "SELECT *
				FROM `_note`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				ORDER BY `id` DESC
				LIMIT 1";
		$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT);

		$send['html'] = utf8(_noteUnit($r + _viewer()));
		$send['count'] = utf8(_noteCount(array(
			'p' => $page_name,
			'id' => $page_id
		)));
		jsonSuccess($send);
		break;
	case 'note_del':
		if(!$note_id = _num($_POST['note_id']))
			jsonError();

		if(!$r = _noteQuery($note_id))
			jsonError();

		//������� ����� ������� ������ ������������ ��� ��� � ����
		if(!VIEWER_ADMIN && $r['viewer_id_add'] != VIEWER_ID)
			jsonError();

		$sql = "UPDATE `_note`
				SET `deleted`=1,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$note_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		jsonSuccess();
		break;
	case 'note_rest'://�������������� �������
		if(!$note_id = _num($_POST['note_id']))
			jsonError();

		if(!$r = _noteQuery($note_id, 1))
			jsonError();

		if(!$r['deleted'])
			jsonError();

		//������� ����� ������������ ������ ������������ ��� ��� � ����
		if(!VIEWER_ADMIN && $r['viewer_id_add'] != VIEWER_ID)
			jsonError();

		$sql = "UPDATE `_note`
				SET `deleted`=0,
					`viewer_id_del`=0,
					`dtime_del`='0000-00-00 00:00:00'
				WHERE `id`=".$note_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		jsonSuccess();
		break;
	case 'note_comment_add'://�������� ������ �����������
		if(!$note_id = _num($_POST['note_id']))
			jsonError();

		$txt = _txt($_POST['txt']);

		if(empty($_POST['txt']))
			jsonError();

		if(!$r = _noteQuery($note_id))
			jsonError();

		$sql = "INSERT INTO `_note_comment` (
					`app_id`,
					`ws_id`,
					`note_id`,
					`txt`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".WS_ID.",
					".$note_id.",
					'".addslashes($txt)."',
					".VIEWER_ID."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		_noteCommentCountUpdate($note_id);

		$sql = "SELECT *
				FROM `_note_comment`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `note_id`=".$note_id."
				ORDER BY `id` DESC
				LIMIT 1";
		$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT);

		$send['html'] = utf8(_noteCommentUnit($r + _viewer()));
		jsonSuccess($send);
		break;
	case 'note_comment_del'://�������� �����������
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_note_comment`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		//����������� ����� ������� ������ ������������ ��� ��� ��� ����
		if(!VIEWER_ADMIN && $r['viewer_id_add'] != VIEWER_ID)
			jsonError();

		$sql = "UPDATE `_note_comment`
				SET `deleted`=1,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		_noteCommentCountUpdate($r['note_id']);

		jsonSuccess();
		break;
	case 'note_comment_rest'://�������������� �����������
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_note_comment`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		//����������� ����� ������������ ������ ������������ ��� ��� ��� ����
		if(!VIEWER_ADMIN && $r['viewer_id_add'] != VIEWER_ID)
			jsonError();

		$sql = "UPDATE `_note_comment`
				SET `deleted`=0,
					`viewer_id_del`=0,
					`dtime_del`='0000-00-00 00:00:00'
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		_noteCommentCountUpdate($r['note_id']);

		jsonSuccess();
		break;

	case 'calendar_filter_rewind':
		if(!preg_match(REGEXP_YEARMONTH, $_POST['month']))
			jsonError();
		if(!preg_match(REGEXP_BOOL, $_POST['noweek']))
			jsonError();

		$days = array();
		if(!empty($_POST['func']) && preg_match(REGEXP_MYSQLTABLE, $_POST['func']) && function_exists($_POST['func']))
			$days = $_POST['func']($_POST['month']);

		$send['html'] = utf8(_calendarFilter(array(
			'upd' => 1,
			'month' => $_POST['month'],
			'days' => $days,
			'sel' => $_POST['sel'],
			'noweek' => $_POST['noweek']
		)));
		jsonSuccess($send);
		break;

	case 'image_upload'://���������� �����������
		$zayav_id = _num($_POST['zayav_id']);
		$zp_id = _num($_POST['zp_id']);
		$manual_id = _num($_POST['manual_id']);

		$f = $_FILES['f1'];
		$im = null;

		//������ ����������� �� ����� 15 ��.
		if($f['size'] > 15728640)
			_imageCookie(4);

		switch ($f['type']) {
			case 'image/jpeg': $im = @imagecreatefromjpeg($f['tmp_name']); break;
			case 'image/png': $im = @imagecreatefrompng($f['tmp_name']); break;
			case 'image/gif': $im = @imagecreatefromgif($f['tmp_name']); break;
			case 'image/tiff':
				$tmp = IMAGE_PATH.'/'.VIEWER_ID.'.jpg';
				$image = NewMagickWand(); // magickwand.org
				MagickReadImage($image, $f['tmp_name']);
				MagickSetImageFormat($image, 'jpg');
				MagickWriteImage($image, $tmp); //���������� ����������
				ClearMagickWand($image); //�������� � �������� ����������� ����������� �� ������
				DestroyMagickWand($image);
				$im = @imagecreatefromjpeg($tmp);
				unlink($tmp);
				break;
		}


		if(!$im)
			_imageCookie(1);

		$x = imagesx($im);
		$y = imagesy($im);
		if($x < 100 || $y < 100)
			_imageCookie(2);

		$fileName = time().'-'._imageNameCreate();

		if(!is_dir(IMAGE_PATH))
			mkdir(IMAGE_PATH, 0777, true);

		$small = _imageImCreate($im, $x, $y, 80, 80, IMAGE_PATH.'/'.$fileName.'-s.jpg');
		$big = _imageImCreate($im, $x, $y, 625, 625, IMAGE_PATH.'/'.$fileName.'-b.jpg');

		$sql = "SELECT COUNT(`id`)
				FROM `_image`
				WHERE !`deleted`
 ".($zayav_id ? " AND `zayav_id`=".$zayav_id : '')."
	".($zp_id ? " AND `zp_id`=".$zp_id : '')."
".($manual_id ? " AND `manual_id`=".$manual_id : '')."
				LIMIT 1";
		$sort = query_value($sql, GLOBAL_MYSQL_CONNECT);

		$sql = "INSERT INTO `_image` (
					`app_id`,
					`ws_id`,
					`path`,

					`small_name`,
					`small_x`,
					`small_y`,
					`small_size`,

					`big_name`,
					`big_x`,
					`big_y`,
					`big_size`,

					`zayav_id`,
					`zp_id`,
					`manual_id`,

					`sort`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".WS_ID.",
					'".addslashes('//'.DOMAIN.IMAGE_HTML.'/')."',

					'".$fileName."-s.jpg',
					".$small['x'].",
					".$small['y'].",
					".$small['size'].",

					'".$fileName."-b.jpg',
					".$big['x'].",
					".$big['y'].",
					".$big['size'].",

					'".$zayav_id."',
					'".$zp_id."',
					'".$manual_id."',

					".$sort.",
					".VIEWER_ID."
			)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		_imageCookie(7);
		break;
	case 'image_view':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_image`
				WHERE !`deleted`
				  AND `id`=".$id;
		if(!$im = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "SELECT *
				FROM `_image`
				WHERE !`deleted`
				  AND `model_id`=".$im['model_id']."
				  AND `zayav_id`=".$im['zayav_id']."
				  AND `zp_id`=".$im['zp_id']."
				  AND `manual_id`=".$im['manual_id']."
				ORDER BY `sort`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		$n = 0; //����������� ����������� ������ ���������������� �����������
		$send['img'] = array();
		while($r = mysql_fetch_assoc($q)) {
			if($r['id'] == $im['id'])
				$send['n'] = $n;
			$send['img'][] = array(
				'link' => $r['path'].$r['big_name'],
				'x' => $r['big_x'],
				'y' => $r['big_y'],
				'dtime' => utf8(FullData($r['dtime_add'], 1))
			);
			$n++;
		}
		jsonSuccess($send);
		break;
	case 'image_obj_get':
		$zayav_id = _num($_POST['zayav_id']);
		$zp_id = _num($_POST['zp_id']);
		$manual_id = _num($_POST['manual_id']);

		$sql = "SELECT *
				FROM `_image`
				WHERE !`deleted`
 ".($zayav_id ? " AND `zayav_id`=".$zayav_id : '')."
	".($zp_id ? " AND `zp_id`=".$zp_id : '')."
".($manual_id ? " AND `manual_id`=".$manual_id : '')."
				ORDER BY `id`";
		$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT);

		$send['img'] = '';

		foreach($arr as $r) {
			$send['img'] .= '<img class="_iview" val="'.$r['id'].'" src="'.$r['path'].$r['small_name'].'">';
		}

		jsonSuccess($send);
		break;

	case 'scanner_word':
		$word = _txt($_POST['word']);

		if(empty($word))
			jsonError();

		if(!preg_match(REGEXP_WORD, $word))
			jsonError();

		$send = array();

		$sql = "SELECT `id`
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND (`imei`='".$word."'
				   OR `serial`='".$word."'
				   OR `barcode`='".substr($word, 0, 12)."')";
		if($id = query_value($sql, GLOBAL_MYSQL_CONNECT))
			$send['zayav_id'] = $id;
		else
			if(preg_match(REGEXP_NUMERIC, $word) && strlen($word) == 15)
				$send['imei'] = 1;

		jsonSuccess($send);
		break;

	case 'manual_answer'://���������� ������ �� �������� �������
		if(!$manual_id = _num($_POST['manual_id']))
			jsonError();

		$val = _num($_POST['val']);

		$sql = "SELECT *
				FROM `_manual`
				WHERE `id`=".$manual_id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		_manual_answer_insert($manual_id, $val);

		jsonSuccess();
		break;
}
