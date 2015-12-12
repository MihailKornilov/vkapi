<?php
/*
	������������ ������� � ��������, ������� ��� � �����������.
	��� ���������� ����������.
*/

if(!defined('SERVIVE_CARTRIDGE'))
	define('SERVIVE_CARTRIDGE', 0);


function _zayavStatus($id=false, $i='name') {
	$name = array(
		0 => '����� ������',
		1 => '������� ����������',
		2 => '���������',
		3 => '��������� �� �������'
	);
	$color = array(
		0 => 'ffffff',
		1 => 'E8E8FF',
		2 => 'CCFFCC',
		3 => 'FFDDDD'
	);

	if($id === false)
		return $name;

	//����������� id �������
	if(!isset($name[$id]))
		return '<span class="red">����������� id �������: <b>'.$id.'</b></span>';

	switch($i) {
		case 'name': return $name[$id];
		case 'color': return $color[$id];
		case 'bg': return ' style="background-color:#'.$color[$id].'"';
		default: return '<span class="red">����������� ���� �������: <b>'.$i.'</b></span>';
	}
}//_zayavStatus()
function _zayavStatusButton($z, $class='status') {
	return
		'<div id="zayav-status-button">'.
			'<h1'._zayavStatus($z['status'], 'bg').' class="'.$class.'">'.
				_zayavStatus($z['status']).
			'</h1>'.
			'<span>�� '.FullDataTime($z['status_dtime'], 1).'</span>'.
		'</div>';
}//_zayavStatusButton()







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
		$sql = "SELECT *
				FROM `zayav`
				WHERE `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$zayav_id;
		$z = query_assoc($sql);

		if($z['status'] != $status) {
			$sql = "UPDATE `zayav`
					SET `status`=".$status.",`status_dtime`=CURRENT_TIMESTAMP
					WHERE `id`=".$zayav_id;
			query($sql);
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

if(!function_exists('zayavBalansUpdate')) {//���������� ������� ������
	function zayavBalansUpdate() {
		return;
	}
}

if(!function_exists('zayavPlaceCheck')) {//���������� ��������������� ������
	function zayavPlaceCheck() {
		return;
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



