<?php
require_once 'modul/vk/vk.php';

//�������� ������ ����� � IE ����� �����
header('P3P: CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');



_const();               //��������� �������� ��������

//if(!SA) _appError('���� � ���������� ����� ���������� ��������� �����');

_appAuth();             //��������� ������ � ����������, �������� �����������
_setup_global();
_pinCheck();
_hashRead();

$html = _header();
$html .= _menu();
$html .= _global_index();
$html .= _footer();

die($html);
