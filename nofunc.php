<?php
/*
	Прописывание функций и констант, которых нет в приложениях.
	Для устранения конфликтов.
*/

if(!defined('SERVICE_CARTRIDGE'))
	define('SERVICE_CARTRIDGE', 0);

if(!function_exists('zayavPlaceCheck')) {
	function zayavPlaceCheck() {
		return;
	}
}

if(!function_exists('zayavCartridgeSchetDel')) {
	function zayavCartridgeSchetDel() {
		return;
	}
}


