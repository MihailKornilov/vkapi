<?php
define('DOCUMENT_ROOT', dirname(dirname(__FILE__)).'/mobile');
require_once(DOCUMENT_ROOT.'/syncro.php');
require_once(DOCUMENT_ROOT.'/view/main.php');
require_once 'view/_vk.php';
require_once(DOCUMENT_ROOT.'/view/ws.php');
require_once(DOCUMENT_ROOT.'/view/ws_zp.php');
require_once(DOCUMENT_ROOT.'/view/ws_report.php');
require_once(DOCUMENT_ROOT.'/view/ws_setup.php');
require_once 'view/_nofunc.php';


_dbConnect();  //подключение к базе mobile
_const();               //установка основных констант
_appAuth();             //получение данных о приложении, проверка авторизации
_getVkUser();           //получение данных о пользователе, внесение в базу, если нет, обновление даты прихода
_ws();
_setup_global();

//глобальные константы для конкретной организации
$sql = "SELECT * FROM `setup` WHERE `ws_id`=".WS_ID." LIMIT 1";
$setup = query_assoc($sql);
define('WS_DEVS', $setup['devs']);
define('WS_TYPE', $setup['ws_type_id']);


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
require_once GLOBAL_DIR_AJAX.'/money.php';
require_once GLOBAL_DIR_AJAX.'/remind.php';
require_once GLOBAL_DIR_AJAX.'/history.php';
require_once GLOBAL_DIR_AJAX.'/setup.php';

require_once DOCUMENT_ROOT.'/ajax/ws.php';
require_once DOCUMENT_ROOT.'/ajax/setup.php';

require_once GLOBAL_DIR_AJAX.'/manual.php';
require_once GLOBAL_DIR_AJAX.'/sa.php';

jsonError();
