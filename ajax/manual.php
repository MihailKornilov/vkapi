<?php
switch(@$_POST['op']) {
	case 'manual_part_add'://�������� ������ �������
		if(!SA)
			return;

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
	case 'manual_part_sub_add'://�������� ������ ����������
		if(!SA)
			return;

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
	case 'manual_page_add'://�������� ����� ��������
		if(!SA)
			return;

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

		$send['html'] = utf8(_manual_part());

		jsonSuccess($send);
		break;
}