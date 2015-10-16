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
		$txt = win1251(trim($_POST['txt']));

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
	case 'sa_history_type_edit':
		if(!$type_id_current = _num($_POST['type_id_current']))
			jsonError();
		if(!$type_id = _num($_POST['type_id']))
			jsonError();

		$txt = win1251(trim($_POST['txt']));

		if(!$txt)
			jsonError();

		if($type_id_current != $type_id) {
			$sql = "SELECT COUNT(`id`) FROM `_history_type` WHERE `id`=".$type_id;
			if(query_value($sql, GLOBAL_MYSQL_CONNECT))
				jsonError();
			$sql = "UPDATE `_history_type`
					SET `id`=".$type_id."
					WHERE `id`=".$type_id_current;
			query($sql, GLOBAL_MYSQL_CONNECT);

			//изменение на новый type_id записей истории действий
			$sql = "UPDATE `_history`
					SET `type_id`=".$type_id."
					WHERE `type_id`=".$type_id_current;
			query($sql, GLOBAL_MYSQL_CONNECT);

			$type_id = $type_id_current;
		}


		$sql = "UPDATE `_history_type`
				SET `txt`='".addslashes($txt)."'
				WHERE `id`=".$type_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		$send['html'] = utf8(sa_history_spisok());
		jsonSuccess($send);
		break;
}
