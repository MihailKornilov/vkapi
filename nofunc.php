<?php
/*
	Прописывание функций и констант, которых нет в приложениях.
	Для устранения конфликтов.
*/

if(!defined('SERVIVE_CARTRIDGE'))
	define('SERVIVE_CARTRIDGE', 0);


function _zayavStatus($id=false, $i='name') {
	$name = array(
		0 => 'Любой статус',
		1 => 'Ожидает выполнения',
		2 => 'Выполнено',
		3 => 'Завершить не удалось'
	);
	$color = array(
		0 => 'ffffff',
		1 => 'E8E8FF',
		2 => 'CCFFCC',
		3 => 'FFDDDD'
	);

	if($id === false)
		return $name;

	//неизвестный id статуса
	if(!isset($name[$id]))
		return '<span class="red">неизвестный id статуса: <b>'.$id.'</b></span>';

	switch($i) {
		case 'name': return $name[$id];
		case 'color': return $color[$id];
		case 'bg': return ' style="background-color:#'.$color[$id].'"';
		default: return '<span class="red">неизвестный ключ статуса: <b>'.$i.'</b></span>';
	}
}//_zayavStatus()
function _zayavStatusButton($z, $class='status') {
	return
		'<div id="zayav-status-button">'.
			'<h1'._zayavStatus($z['status'], 'bg').' class="'.$class.'">'.
				_zayavStatus($z['status']).
			'</h1>'.
			'<span>от '.FullDataTime($z['status_dtime'], 1).'</span>'.
		'</div>';
}//_zayavStatusButton()







if(!function_exists('zayav_spisok')) {//список заявок
	function zayav_spisok() {
		return array(
			'all' => 0
		);
	}
}

if(!function_exists('zayav_cartridge_spisok')) {//список заявок с картриджами
	function zayav_cartridge_spisok() {
		return array(
			'all' => 0
		);
	}
}

if(!function_exists('zayavStatusChange')) {//обновление статуса заявки
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

if(!function_exists('zayavBalansUpdate')) {//обновление баланса заявки
	function zayavBalansUpdate() {
		return;
	}
}

if(!function_exists('zayavPlaceCheck')) {//Обновление местонахождения заявки
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



