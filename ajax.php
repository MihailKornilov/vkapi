<?php
require_once 'view/_vk.php';

_const();               //установка основных констант
_appAuth();             //получение данных о приложении, проверка авторизации
_setup_global();

$nopin = array(
	'pin_enter' => 1,
	'cache_clear' => 1,
	'cookie_clear' => 1
);
if(empty($nopin[$_POST['op']]) && PIN_ENTER)
	jsonError(array('pin'=>1));

$_SESSION[PIN_TIME_KEY] = time() + PIN_TIME_LEN;

require_once GLOBAL_DIR_AJAX.'/vk.php';
require_once GLOBAL_DIR_AJAX.'/client.php';
require_once GLOBAL_DIR_AJAX.'/zayav.php';
require_once GLOBAL_DIR_AJAX.'/tovar.php';
require_once GLOBAL_DIR_AJAX.'/money.php';
require_once GLOBAL_DIR_AJAX.'/remind.php';
require_once GLOBAL_DIR_AJAX.'/history.php';
require_once GLOBAL_DIR_AJAX.'/setup.php';
require_once GLOBAL_DIR_AJAX.'/manual.php';
require_once GLOBAL_DIR_AJAX.'/sa.php';
require_once GLOBAL_DIR.'/modul/devstory/devstory_ajax.php';

jsonError();
