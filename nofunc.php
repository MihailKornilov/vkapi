<?php
/*
	������������ ������� � ��������, ������� ��� � �����������.
	��� ���������� ����������.
*/

if(!defined('SERVIVE_CARTRIDGE'))
	define('SERVIVE_CARTRIDGE', 0);

if(!function_exists('zayav_spisok')) {//������ ������
	function zayav_spisok() {
		return array(
			'all' => 0
		);
	}
}

if(!function_exists('zayav_cartridge_spisok')) {//������ ������ � �����������
	function zayav_cartridge_spisok() {
		return array(
			'all' => 0
		);
	}
}

if(!function_exists('zayavStatusChange')) {//���������� ������� ������
	function zayavStatusChange($zayav_id, $status) {
		$z = _zayavQuery($zayav_id);

		if($z['status'] != $status) {
			$sql = "UPDATE `_zayav`
					SET `status`=".$status.",
						`status_dtime`=CURRENT_TIMESTAMP
					WHERE `id`=".$zayav_id;
			query($sql, GLOBAL_MYSQL_CONNECT);
			_history(array(
				'type_id' => 71,
				'client_id' => $z['client_id'],
				'zayav_id' => $zayav_id,
				'v1' => $z['status'],
				'v2' => $status,
			));
		}
	}
}

if(!function_exists('_zayavValToList')) {
	function _zayavValToList($arr) {
		return $arr;
	}
}

if(!function_exists('_zayavCountToClient')) {
	function _zayavCountToClient($arr) {
		return $arr;
	}
}

if(!function_exists('zayavCartridgeSchetDel')) {
	function zayavCartridgeSchetDel($schet_id) {
		return;
	}
}



