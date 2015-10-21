<?php
switch(@$_POST['op']) {
	case 'history_add'://внесение произвольного события
		$txt = _txt($_POST['txt']);

		if(!$txt)
			jsonError();

		_history(array(
			'type_id' => 999,
			'v1' => $txt
		));

		$data = _history_spisok($_POST);
		$send['html'] = utf8($data['spisok']);

		jsonSuccess($send);
		break;
	case 'history_spisok':
		$data = _history_spisok($_POST);
		$send['html'] = utf8($data['spisok']);
		jsonSuccess($send);
		break;
	case 'history_del'://удаление элемента истории
		if(!SA)
			jsonError();

		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_history`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "DELETE FROM `_history` WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		jsonSuccess();
		break;
}