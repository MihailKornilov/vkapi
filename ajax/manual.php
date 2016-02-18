<?php
if(!SA)
	jsonError();

switch(@$_POST['op']) {
	case 'manual_part_add'://�������� ������ �������
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
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'manual_part');

		$send['html'] = utf8(_manual_main_spisok());

		jsonSuccess($send);
		break;
	case 'manual_part_edit'://�������������� �������
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);
		$access = _bool($_POST['access']);

		if(!$name)
			jsonError();

		$sql = "SELECT *
				FROM `_manual_part`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_manual_part`
				SET `name`='".addslashes($name)."',
					`access`=".$access."
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'manual_part');
		xcache_unset(CACHE_PREFIX.'manual_part_sub');

		$send['id'] = $id;
		jsonSuccess($send);
		break;
	case 'manual_part_del'://�������� �������� �������
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_manual_part`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		//�������� �� ������� ������� � �������
		$sql = "SELECT COUNT(*)
				FROM `_manual`
				WHERE `part_id`=".$id;
		if(query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "DELETE FROM `_manual_part` WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		$sql = "DELETE FROM `_manual_part_sub` WHERE `part_id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'manual_part');
		xcache_unset(CACHE_PREFIX.'manual_part_sub');

		jsonSuccess();
		break;

	case 'manual_part_sub_add'://�������� ������ ����������
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
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'manual_part_sub');

		$send['html'] = utf8(_manual_main_spisok());

		jsonSuccess($send);
		break;

	case 'manual_page_add'://�������� ����� ��������
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
		query($sql, GLOBAL_MYSQL_CONNECT);

		$insert_id = query_insert_id('_manual', GLOBAL_MYSQL_CONNECT);

		$send['id'] = $insert_id;

		jsonSuccess($send);
		break;
	case 'manual_page_edit'://�������������� �������� �������
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
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();


		$sql = "UPDATE `_manual`
				SET `part_id`=".$part_id.",
					`part_sub_id`=".$part_sub_id.",
					`name`='".addslashes($name)."',
					`access`=".$access."
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
	case 'manual_page_del'://�������� �������� �������
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_manual`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();


		$sql = "DELETE FROM `_manual` WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		$send['part_id'] = $r['part_id'];

		jsonSuccess($send);
		break;
}