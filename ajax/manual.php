<?php
if(!SA)
	jsonError();

switch(@$_POST['op']) {
	case 'manual_part_add'://внесение нового раздела
		$name = _txt($_POST['name']);

		if(!$name)
			jsonError();

		$sql = "INSERT INTO `_manual_part` (
					`name`,
					`sort`
				) VALUES (
					'".addslashes($name)."',
					"._maxSql('_manual_part')."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'manual_part');

		$send['html'] = utf8(_manual_part());

		jsonSuccess($send);
		break;
	case 'manual_part_sub_add'://внесение нового подраздела
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);

		if(!$name)
			jsonError();

		$sql = "INSERT INTO `_manual_part_sub` (
					`part_id`,
					`name`,
					`sort`
				) VALUES (
					".$id.",
					'".addslashes($name)."',
					"._maxSql('_manual_part_sub')."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'manual_part_sub');

		$send['html'] = utf8(_manual_part());

		jsonSuccess($send);
		break;
	case 'manual_page_add'://внесение новой страницы
		if(!$part_id = _num($_POST['part_id']))
			jsonError();

		$part_sub_id = _num($_POST['part_sub_id']);
		$name = _txt($_POST['name']);
		$content = win1251(trim($_POST['content']));

		if(!$name)
			jsonError();

		$sql = "INSERT INTO `_manual` (
					`part_id`,
					`part_sub_id`,
					`name`,
					`content`,
					`sort`
				) VALUES (
					".$part_id.",
					".$part_sub_id.",
					'".addslashes($name)."',
					'".addslashes($content)."',
					"._maxSql('_manual')."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$insert_id = query_insert_id('_manual', GLOBAL_MYSQL_CONNECT);

		$send['id'] = $insert_id;

		jsonSuccess($send);
		break;
	case 'manual_page_edit'://редактирование страницы мануала
		if(!$id = _num($_POST['id']))
			jsonError();
		if(!$part_id = _num($_POST['part_id']))
			jsonError();

		$part_sub_id = _num($_POST['part_sub_id']);
		$name = _txt($_POST['name']);
		$content = win1251(trim($_POST['content']));

		if(!$name)
			jsonError();

		$sql = "SELECT *
				FROM `_manual`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();


		$sql = "UPDATE `_manual`
				SET `part_id`=".$part_id.",
					`part_sub_id`=".$part_sub_id.",
					`name`='".addslashes($name)."'
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		if($r['content'] != $content) {
			$sql = "UPDATE `_manual`
					SET `content`='".addslashes($content)."',
						`count_upd`=`count_upd`+1,
						`dtime_upd`=CURRENT_TIMESTAMP
					WHERE `id`=".$id;
			query($sql, GLOBAL_MYSQL_CONNECT);
		}

		$send['id'] = $id;

		jsonSuccess($send);
		break;
}