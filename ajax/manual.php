<?php
switch(@$_POST['op']) {
	case 'manual_part_add'://внесение нового раздела
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

		$send['html'] = utf8(_manual_part());

		jsonSuccess($send);
		break;
	case 'manual_part_sub_add'://внесение нового подраздела
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

		$send['html'] = utf8(_manual_part());

		jsonSuccess($send);
		break;
}