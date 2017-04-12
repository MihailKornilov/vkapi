<?php
require_once 'modul/vk/vk.php';

_const();               //установка основных констант
_appAuth();             //получение данных о приложении, проверка авторизации
_setup_global();

if(VIEWER_ID_ADMIN)
	jsonError();

$nopin = array(
	'pin_enter' => 1,
	'cache_clear' => 1,
	'cookie_clear' => 1
);
if(empty($nopin[$_POST['op']]) && PIN_ENTER)
	jsonError(array('pin'=>1));

$_SESSION[PIN_TIME_KEY] = time() + PIN_TIME_LEN;

require_once GLOBAL_DIR.'/modul/vk/vk_ajax.php';
require_once GLOBAL_DIR.'/modul/client/client_ajax.php';
require_once GLOBAL_DIR.'/modul/zayav/zayav_ajax.php';
require_once GLOBAL_DIR.'/modul/tovar/tovar_ajax.php';
require_once GLOBAL_DIR.'/modul/money/money_ajax.php';
require_once GLOBAL_DIR.'/modul/remind/remind_ajax.php';
require_once GLOBAL_DIR.'/modul/history/history_ajax.php';
require_once GLOBAL_DIR.'/modul/setup/setup_ajax.php';
require_once GLOBAL_DIR.'/modul/manual/manual_ajax.php';
require_once GLOBAL_DIR.'/modul/devstory/devstory_ajax.php';
require_once GLOBAL_DIR.'/modul/kupezz/kupezz_ajax.php';
require_once GLOBAL_DIR.'/modul/test/test_ajax.php';//todo
require_once GLOBAL_DIR.'/modul/sa/sa_ajax.php';
require_once GLOBAL_DIR.'/modul/debug/debug_ajax.php';



jsonError('Условие не найдено');
