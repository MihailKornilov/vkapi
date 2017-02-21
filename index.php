<?php
require_once 'modul/vk/vk.php';

//¬ключает работу куков в IE через фрейм
header('P3P: CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');



_const();               //установка основных констант

if(!SA) _appError('¬ход в приложение будет недоступен некоторое врем€');

_appAuth();             //получение данных о приложении, проверка авторизации
_setup_global();
_pinCheck();
_hashRead();

die(
	_header().
	_global_index().
	_footer()
);
