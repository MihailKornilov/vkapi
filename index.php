<?php
require_once 'view/_vk.php';
require_once 'view/_nofunc.php';

//�������� ������ ����� � IE ����� �����
header('P3P: CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');

_const();               //��������� �������� ��������
_appAuth();             //��������� ������ � ����������, �������� �����������
_setup_global();
_pinCheck();
_hashRead();

$html = _header();
$html .= _menu();
$html .= _global_index();
$html .= _footer();

die($html);
