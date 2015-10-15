<?php
switch(@$_POST['op']) {
	case 'history_spisok':
		$data = _history($_POST);
		$send['html'] = utf8($data['spisok']);
		jsonSuccess($send);
		break;

	case 'sa_history_type_add':
		if(!empty($_POST['type_id']) && !_num($_POST['type_id']))
			jsonError();

		$type_id = _num($_POST['type_id']);
		$txt = _txt($_POST['txt']);

		if($type_id) {
			$sql = "SELECT COUNT(`id`) FROM `_history_type` WHERE `id`=".$type_id;
			if(query_value($sql, GLOBAL_MYSQL_CONNECT))
				jsonError();
		}

		if(!$txt)
			jsonError();

		$sql = "INSERT INTO `_history_type` (
					`id`,
					`txt`
				) VALUES (
					".$type_id.",
					'".addslashes($txt)."'
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$send['html'] = utf8(sa_history_spisok());
		jsonSuccess($send);
		break;
}
