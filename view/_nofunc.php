<?php
/*
	Прописывание функций и констант, которых нет в приложениях.
	Для устранения конфликтов.
*/

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

if(!function_exists('_cacheClear')) {
	function _cacheClear() {
		return;
	}
}

if(!function_exists('_appScripts')) {
	function _appScripts() {
		return '';
	}
}



