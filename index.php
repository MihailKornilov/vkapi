<?php
require_once 'view/_vk.php';
require_once 'view/_nofunc.php';

//Включает работу куков в IE через фрейм
header('P3P: CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');

_const();               //установка основных констант
_appAuth();             //получение данных о приложении, проверка авторизации
_getVkUser();           //получение данных о пользователе, внесение в базу, если нет, обновление даты прихода
_ws();
_setup_global();
_pinCheck();
_hashRead();

$html = _header();
$html .= _menu();
$html .= _global_index();
$html .= _footer();

die($html);
