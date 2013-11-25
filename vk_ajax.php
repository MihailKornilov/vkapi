<?php
switch(@$_POST['op']) {
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
		$send['html'] = utf8(_vkCommentUnit(mysql_insert_id(), _viewer(), $txt, curTime()));
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
		$send['html'] = utf8(_vkCommentChild(mysql_insert_id(), _viewer(), $txt, curTime()));
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
}