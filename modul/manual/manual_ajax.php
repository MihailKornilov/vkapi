<?php
switch(@$_POST['op']) {
	case 'manual_part_add'://внесение нового раздела
		if(!SA)
			jsonError();

		$name = _txt($_POST['name']);
		$access = _bool($_POST['access']);

		if(!$name)
			jsonError();

		$sql = "INSERT INTO `_manual_part` (
					`name`,
					`access`,
					`sort`
				) VALUES (
					'".addslashes($name)."',
					'".addslashes($access)."',
					"._maxSql('_manual_part')."
				)";
		query($sql);

		xcache_unset(CACHE_PREFIX.'manual_part');

		$send['html'] = utf8(_manual_main_spisok());

		jsonSuccess($send);
		break;
	case 'manual_part_edit'://редактирование раздела
		if(!SA)
			jsonError();

		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);
		$access = _bool($_POST['access']);

		if(!$name)
			jsonError();

		$sql = "SELECT *
				FROM `_manual_part`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_manual_part`
				SET `name`='".addslashes($name)."',
					`access`=".$access."
				WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'manual_part');
		xcache_unset(CACHE_PREFIX.'manual_part_sub');

		$send['id'] = $id;
		jsonSuccess($send);
		break;
	case 'manual_part_del'://удаление страницы мануала
		if(!SA)
			jsonError();

		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_manual_part`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		//проверка на наличие страниц в разделе
		$sql = "SELECT COUNT(*)
				FROM `_manual`
				WHERE `part_id`=".$id;
		if(query_value($sql))
			jsonError();

		$sql = "DELETE FROM `_manual_part` WHERE `id`=".$id;
		query($sql);

		$sql = "DELETE FROM `_manual_part_sub` WHERE `part_id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'manual_part');
		xcache_unset(CACHE_PREFIX.'manual_part_sub');

		jsonSuccess();
		break;

	case 'manual_part_sub_add'://внесение нового подраздела
		if(!SA)
			jsonError();

		if(!$part_id = _num($_POST['part_id']))
			jsonError();

		$name = _txt($_POST['name']);

		if(!$name)
			jsonError();

		$sql = "INSERT INTO `_manual_part_sub` (
					`part_id`,
					`name`,
					`sort`
				) VALUES (
					".$part_id.",
					'".addslashes($name)."',
					"._maxSql('_manual_part_sub')."
				)";
		query($sql);

		xcache_unset(CACHE_PREFIX.'manual_part_sub');

		$send['html'] = utf8(_manual_main_spisok());

		jsonSuccess($send);
		break;

	case 'manual_page_add'://внесение новой страницы
		if(!SA)
			jsonError();

		if(!$part_id = _num($_POST['part_id']))
			jsonError();

		$part_sub_id = _num($_POST['part_sub_id']);
		$name = _txt($_POST['name']);
		$content = win1251(trim($_POST['content']));
		$access = _bool($_POST['access']);

		if(!$name)
			jsonError();

		$sql = "INSERT INTO `_manual` (
					`part_id`,
					`part_sub_id`,
					`name`,
					`content`,
					`access`,
					`sort`
				) VALUES (
					".$part_id.",
					".$part_sub_id.",
					'".addslashes($name)."',
					'".addslashes($content)."',
					".$access.",
					"._maxSql('_manual')."
				)";
		query($sql);

		$insert_id = query_insert_id('_manual');

		$send['id'] = $insert_id;

		jsonSuccess($send);
		break;
	case 'manual_page_edit'://редактирование страницы мануала
		if(!SA)
			jsonError();

		if(!$id = _num($_POST['id']))
			jsonError();
		if(!$part_id = _num($_POST['part_id']))
			jsonError();

		$part_sub_id = _num($_POST['part_sub_id']);
		$name = _txt($_POST['name']);
		$content = win1251(trim($_POST['content']));
		$access = _bool($_POST['access']);

		if(!$name)
			jsonError();

		$sql = "SELECT *
				FROM `_manual`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();


		$sql = "UPDATE `_manual`
				SET `part_id`=".$part_id.",
					`part_sub_id`=".$part_sub_id.",
					`name`='".addslashes($name)."',
					`access`=".$access."
				WHERE `id`=".$id;
		query($sql);

		if($r['content'] != $content) {
			$sql = "UPDATE `_manual`
					SET `content`='".addslashes($content)."',
						`count_upd`=`count_upd`+1,
						`dtime_upd`=CURRENT_TIMESTAMP
					WHERE `id`=".$id;
			query($sql);
		}

		$send['id'] = $id;

		jsonSuccess($send);
		break;
	case 'manual_page_del'://удаление страницы мануала
		if(!SA)
			jsonError();

		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_manual`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();


		$sql = "DELETE FROM `_manual` WHERE `id`=".$id;
		query($sql);

		$send['part_id'] = $r['part_id'];

		jsonSuccess($send);
		break;
}