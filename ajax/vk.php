<?php
$nopin = array(
	'pin_enter' => 1,
	'cache_clear' => 1,
	'cookie_clear' => 1
);
if(empty($nopin[$_POST['op']]) && PIN_ENTER)
	jsonError(array('pin'=>1));

$_SESSION[PIN_TIME_KEY] = time() + PIN_TIME_LEN;

require_once GLOBAL_DIR_AJAX.'/client.php';
require_once GLOBAL_DIR_AJAX.'/zayav.php';
require_once GLOBAL_DIR_AJAX.'/money.php';
require_once GLOBAL_DIR_AJAX.'/remind.php';
require_once GLOBAL_DIR_AJAX.'/history.php';
require_once GLOBAL_DIR_AJAX.'/setup.php';
require_once GLOBAL_DIR_AJAX.'/sa.php';

switch(@$_POST['op']) {
	case 'cache_clear':
		if(!SA)
			jsonError();
		_globalValuesJS();
		_globalCacheClear();
		_cacheClear();

		//обновление значения скриптов и стилей приложения
		$sql = "UPDATE `_setup`
				SET `value`=`value`+1
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `key`='VERSION'";
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
		_globalValuesJS();

		jsonSuccess();
		break;

	case 'pagehelp_add':
		if(!SA)
			jsonError();
		if(!preg_match(REGEXP_MYSQLTABLE, $_POST['page']))
			jsonError();

		$page = htmlspecialchars(trim($_POST['page']));
		$name = win1251(htmlspecialchars(trim($_POST['name'])));
		$txt = win1251(trim($_POST['txt']));

		if(empty($name))
			jsonError();
		if(query_value("SELECT `id` FROM `pagehelp` WHERE `page`='".$page."' LIMIT 1"))
			jsonError();

		$sql = "INSERT INTO `pagehelp` (
					`page`,
					`name`,
					`txt`
				) VALUES (
					'".addslashes($page)."',
					'".addslashes($name)."',
					'".addslashes($txt)."'
				)";
		query($sql);
		jsonSuccess();
		break;
	case 'pagehelp_get':
		if(!preg_match(REGEXP_NUMERIC, $_POST['id']))
			jsonError();
		$id = intval($_POST['id']);
		$sql = "SELECT * FROM `pagehelp` WHERE `id`='".$id."' LIMIT 1";
		if(!$r = mysql_fetch_assoc(query($sql)))
			jsonError();
		$send['page'] = $r['page'];
		$send['name'] = utf8($r['name']);
		$send['edit'] = (SA ? utf8('<a class="add">Редактировать</a>') : '');
		$send['txt'] = utf8($r['txt']);
		$send['dtime'] = utf8('<div class="pagehelp_show_dtime">Изменено '.FullDataTime($r['updated']).'</div>');
		jsonSuccess($send);
		break;
	case 'pagehelp_edit':
		if(!SA)
			jsonError();
		if(!preg_match(REGEXP_NUMERIC, $_POST['id']))
			jsonError();

		$id = intval($_POST['id']);
		$name = win1251(htmlspecialchars(trim($_POST['name'])));
		$txt = win1251(trim($_POST['txt']));
		if(empty($name))
			jsonError();

		$sql = "SELECT * FROM `pagehelp` WHERE `id`='".$id."' LIMIT 1";
		if(!$r = mysql_fetch_assoc(query($sql)))
			jsonError();

		$sql = "UPDATE `pagehelp`
				SET	`name`='".addslashes($name)."',
					`txt`='".addslashes($txt)."'
				WHERE `id`=".$id;
		query($sql);
		jsonSuccess();
		break;

	case 'attach_upload':
		/*
			Прикрепление файлов
			1 - успешно
			2 - неверный формат
			3 - загрузить не удалось
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
		if(!is_dir(ATTACH_PATH))
			mkdir(ATTACH_PATH, 0777, true);
		$fname = time().'_'.translit(trim($f['name'])); //имя файла, сохраняемое на диск
		if(move_uploaded_file($f['tmp_name'], ATTACH_PATH.'/'.$fname)) {
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
						'".addslashes(ATTACH_HTML.$fname)."',
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
	case 'attach_get'://получение данных о файле после его загрузке
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
	case 'attach_save'://сохранение файла после его загрузки
		if(!$id = _num($_POST['id']))
			jsonError();

		if($zayav_id = _num($_POST['zayav_id']))
			if(!$z = _zayavQuery($zayav_id))
				jsonError();

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

		$send['arr'] = _attachArr($id);
		jsonSuccess($send);
		break;
	case 'attach_edit'://редактирование данных файла
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
			jsonError('Файл был удалён');

		$sql = "UPDATE `_attach`
				SET `name`='".addslashes($name)."'
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		jsonSuccess();
		break;
	case 'attach_del'://удаление файла
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_attach`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError('Файл уже был удалён');

		$sql = "UPDATE `_attach`
				SET `deleted`=1,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		//удаление из расходов
		$sql = "UPDATE `_money_expense`
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

		//заметку может удалить только руководитель или кто её внёс
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
	case 'note_rest'://восстановление заметки
		if(!$note_id = _num($_POST['note_id']))
			jsonError();

		if(!$r = _noteQuery($note_id, 1))
			jsonError();

		if(!$r['deleted'])
			jsonError();

		//заметку может восстановить только руководитель или кто её внёс
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
	case 'note_comment_add'://внесение нового комментария
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
	case 'note_comment_del'://удаление комментария
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

		//комментарий может удалить только руководитель или кто его внёс
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
	case 'note_comment_rest'://восстановление комментария
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

		//комментарий может восстановить только руководитель или кто его внёс
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

	case 'image_add'://добавление изображения
		/*
		Коды ошибок:
			0 - неизвестная ошибка (или некорректный owner)
			1 - неверный формат файла
			2 - слишком маленькое изображение
			3 - превышено количество закружаемых изображений
		*/

		if(empty($_POST['owner']) || !preg_match(REGEXP_WORD, $_POST['owner']))
			_imageCookie(array('error'=>0));
		if(empty($_POST['max']) || !preg_match(REGEXP_NUMERIC, $_POST['max']))
			_imageCookie(array('error'=>0));

		$owner = trim($_POST['owner']);
		$max = intval($_POST['max']);
		$fileName = $owner.'-'._imageNameCreate();

		$f = $_FILES['f1']['name'] ? $_FILES['f1'] :
			($_FILES['f2']['name'] ? $_FILES['f2'] : $_FILES['f3']);
		$im = null;
//		echo $f['type'];
		switch ($f['type']) {
			case 'image/jpeg': $im = @imagecreatefromjpeg($f['tmp_name']); break;
			case 'image/png': $im = @imagecreatefrompng($f['tmp_name']); break;
			case 'image/gif': $im = @imagecreatefromgif($f['tmp_name']); break;
			case 'image/tiff':
				$tmp = APP_PATH.'/files/tmp'.VIEWER_ID.'.jpg';
				$image = NewMagickWand(); // magickwand.org
				MagickReadImage($image, $f['tmp_name']);
				MagickSetImageFormat($image, 'jpg');
				MagickWriteImage($image, $tmp); //Сохраняем результат
				ClearMagickWand($image); //Удаляем и выгружаем полученное изображение из памяти
				DestroyMagickWand($image);
				$im = @imagecreatefromjpeg($tmp);
				unlink($tmp);
				break;
		}

		if(!$im)
			_imageCookie(array('error'=>1));

		$x = imagesx($im);
		$y = imagesy($im);
		if($x < 100 || $y < 100)
			_imageCookie(array('error'=>2));

		$small = _imageImCreate($im, $x, $y, 80, 80, APP_PATH.'/files/images/'.$fileName.'-s.jpg');
		$big = _imageImCreate($im, $x, $y, 610, 610, APP_PATH.'/files/images/'.$fileName.'-b.jpg');

		$sort = query_value("SELECT COUNT(`id`) FROM `images` WHERE !`deleted` AND `owner`='".$owner."' LIMIT 1");
		if($sort + 1 > $max)
			_imageCookie(array('error'=>3));

		$link = '/files/images/'.$fileName;
		$sql = "INSERT INTO `images` (
				  `path`,
				  `small_name`,
				  `small_x`,
				  `small_y`,
				  `big_name`,
				  `big_x`,
				  `big_y`,
				  `owner`,
				  `sort`,
				  `viewer_id_add`
			  ) VALUES (
				  '".addslashes('//'.DOMAIN.APP_HTML.'/files/images/')."',
				  '".$fileName."-s.jpg',
				  ".$small['x'].",
				  ".$small['y'].",
				  '".$fileName."-b.jpg',
				  ".$big['x'].",
				  ".$big['y'].",
				  '".$owner."',
				  ".$sort.",
				  ".VIEWER_ID."
			  )";
		query($sql);

		_imageCookie(array(
			'id' => mysql_insert_id(),
			'link' => '//'.DOMAIN.APP_HTML.'/files/images/'.$fileName.'-s.jpg',
			'max' => $sort + 2 > $max ? 1 : 0
		));
		break;
	case 'image_del':
		if(!preg_match(REGEXP_NUMERIC, $_POST['id']))
			jsonError();
		$id = intval($_POST['id']);

		$sql = "SELECT * FROM `images` WHERE !`deleted` AND `id`=".$id;
		if(!$r = mysql_fetch_assoc(query($sql)))
			jsonError();

		query("UPDATE `images` SET `deleted`=1 WHERE `id`=".$id);

		$ids = query_ids("SELECT * FROM `images` WHERE !`deleted` AND `owner`='".$r['owner']."' ORDER BY `sort`");
		if($ids)
			foreach(explode(',', $ids) as $n => $id)
				query("UPDATE `images` SET `sort`=".$n." WHERE `id`=".$id);

		jsonSuccess();
		break;
	case 'image_sort':
		if(empty($_POST['ids']))
			jsonError();

		$sort = explode(',', $_POST['ids']);
		foreach($sort as $id)
			if(!preg_match(REGEXP_NUMERIC, $id))
				jsonError();

		foreach($sort as $n => $id)
			query("UPDATE `images` SET `sort`=".$n." WHERE `id`=".$id);

		jsonSuccess();
		break;
	case 'image_view':
		if(!preg_match(REGEXP_NUMERIC, $_POST['id']))
			jsonError();
		$id = intval($_POST['id']);
		$sql = "SELECT * FROM `images` WHERE !`deleted` AND `id`=".$id;
		if(!$im = mysql_fetch_assoc(query($sql)))
			jsonError();

		$n = 0; //определение порядкового номера просматриваемого изображения
		$sql = "SELECT * FROM `images` WHERE !`deleted` AND `owner`='".$im['owner']."' ORDER BY `sort`";
		$q = query($sql);
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
}
