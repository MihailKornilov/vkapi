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

if(!function_exists('zayavBalansUpdate')) {//���������� ������� ������
	function zayavBalansUpdate() {
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



