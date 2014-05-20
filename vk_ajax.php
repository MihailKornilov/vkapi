<?php
switch(@$_POST['op']) {
	case 'cookie_clear':
		if(!empty($_COOKIE))
			foreach($_COOKIE as $key => $val)
				setcookie($key, '', time() - 3600, '/');
		jsonSuccess();
		break;

	case 'sort':
		if(!preg_match(REGEXP_MYSQLTABLE, $_POST['table']))
			jsonError();
		$table = htmlspecialchars(trim($_POST['table']));
		$sql = "SHOW TABLES LIKE '".$table."'";
		if(!mysql_num_rows(query($sql)))
			jsonError();

		$sort = explode(',', $_POST['ids']);
		if(empty($sort))
			jsonError();
		for($n = 0; $n < count($sort); $n++)
			if(!preg_match(REGEXP_NUMERIC, $sort[$n]))
				jsonError();

		for($n = 0; $n < count($sort); $n++)
			query("UPDATE `".$table."` SET `sort`=".$n." WHERE `id`=".intval($sort[$n]));
		_cacheClear();
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

	case 'vkcomment_add':
		$table = htmlspecialchars(trim($_POST['table']));
		if(strlen($table) > 20)
			jsonError();
		if(!preg_match(REGEXP_NUMERIC, $_POST['id']))
			jsonError();
		if(empty($_POST['txt']))
			jsonError();
		$txt = win1251(htmlspecialchars(trim($_POST['txt'])));
		$sql = "INSERT INTO `vk_comment` (
					`table_name`,
					`table_id`,
					`txt`,
					`viewer_id_add`
				) VALUES (
					'".$table."',
					".intval($_POST['id']).",
					'".addslashes($txt)."',
					".VIEWER_ID."
				)";
		query($sql);
		$data = array(
			'id' => mysql_insert_id(),
			'txt' => $txt,
			'viewer_id_add' => VIEWER_ID,
			'dtime_add' => curTime()
		);
		$send['html'] = utf8(_vkCommentUnit($data + _viewer()));
		jsonSuccess($send);
		break;
	case 'vkcomment_add_child':
		$table = htmlspecialchars(trim($_POST['table']));
		if(strlen($table) > 20)
			jsonError();
		if(!preg_match(REGEXP_NUMERIC, $_POST['id']))
			jsonError();
		if(!preg_match(REGEXP_NUMERIC, $_POST['parent']))
			jsonError();
		if(empty($_POST['txt']))
			jsonError();
		$txt = win1251(htmlspecialchars(trim($_POST['txt'])));
		$sql = "INSERT INTO `vk_comment` (
					`table_name`,
					`table_id`,
					`txt`,
					`parent_id`,
					`viewer_id_add`
				) VALUES (
					'".$table."',
					".intval($_POST['id']).",
					'".addslashes($txt)."',
					".intval($_POST['parent']).",
					".VIEWER_ID."
				)";
		query($sql);
		$data = array(
			'id' => mysql_insert_id(),
			'txt' => $txt,
			'viewer_id_add' => VIEWER_ID,
			'dtime_add' => curTime()
		);
		$send['html'] = utf8(_vkCommentChild($data + _viewer()));
		jsonSuccess($send);
		break;
	case 'vkcomment_del':
		if(!preg_match(REGEXP_NUMERIC, $_POST['id']))
			jsonError();
		$id = intval($_POST['id']);
		if(!VIEWER_ADMIN) {
			$sql = "SELECT `viewer_id_add` FROM `vk_comment` WHERE `status`=1 AND `id`=".$id;
			if(!$r = mysql_fetch_assoc(query($sql)))
				jsonError();
			if($r['viewer_id_add'] != VIEWER_ID)
				jsonError();
		}

		$childs = array();

		$sql = "SELECT `id` FROM `vk_comment` WHERE `status`=1 AND `parent_id`=".$id;
		$q = query($sql);
		if(mysql_num_rows($q)) {
			while($r = mysql_fetch_assoc($q))
				$childs[] = $r['id'];
			$sql = "UPDATE `vk_comment` SET
					`status`=0,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
			   WHERE `parent_id`=".$id;
			query($sql);
		}

		$sql = "UPDATE `vk_comment` SET
					`status`=0,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP,
					`child_del`=".(!empty($childs) ? "'".implode(',', $childs)."'" : 'NULL')."
			   WHERE `id`=".$id;
		query($sql);
		jsonSuccess();
		break;
	case 'vkcomment_rest':
		if(!preg_match(REGEXP_NUMERIC, $_POST['id']))
			jsonError();
		$id = intval($_POST['id']);

		$sql = "SELECT `child_del` FROM `vk_comment` WHERE `id`=".$id;
		$r = mysql_fetch_assoc(query($sql));
		if($r['child_del']) {
			$sql = "UPDATE `vk_comment` SET
					`status`=1,
					`viewer_id_del`=0,
					`dtime_del`='0000-00-00 00:00:00'
			   WHERE `id` IN (".$r['child_del'].")";
			query($sql);
		}

		$sql = "UPDATE `vk_comment` SET
					`status`=1,
					`viewer_id_del`=0,
					`dtime_del`='0000-00-00 00:00:00',
					`child_del`=NULL
			   WHERE `id`=".$id;
		query($sql);
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
		switch ($f['type']) {
			case 'image/jpeg': $im = @imagecreatefromjpeg($f['tmp_name']); break;
			case 'image/png': $im = @imagecreatefrompng($f['tmp_name']); break;
			case 'image/gif': $im = @imagecreatefromgif($f['tmp_name']); break;
			case 'image/tiff':
				$tmp = PATH.'files/tmp'.VIEWER_ID.'.jpg';
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

		$small = _imageImCreate($im, $x, $y, 80, 80, PATH.'files/images/'.$fileName.'-s.jpg');
		$big = _imageImCreate($im, $x, $y, 610, 610, PATH.'files/images/'.$fileName.'-b.jpg');

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
				  '".addslashes(SITE.'/files/images/')."',
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
			'link' => SITE.'/files/images/'.$fileName.'-s.jpg',
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

	case 'history_spisok':
		if(!preg_match(REGEXP_MYSQLTABLE, $_POST['table']))
			jsonError();
		$table = $_POST['table'];
		if(!function_exists($table))
			jsonError();
		$data = $table($_POST);
		$send['html'] = utf8($data['spisok']);
		jsonSuccess($send);
		break;
}