<?php
function kvit_head() {
	return
	'<table class="head">'.
		'<tr><td>'.BARCODE.
			'<td class="rekvisit">'.
				'<h1>'._wsType(WS_TYPE).' �<b>'._app('name').'</b>�</h1>'.
				'<h1>�����: '._app('adres_yur').'.</h1>'.
				'<h2>�������: '._app('phone').'. ����� ������: '._app('time_work').'.</h2>'.
	'</table>';
}//kvit_head()
function kvit_name($nomer, $barcode=0) {
	return
		'<div class="name">'.
			($barcode ? BARCODE : '').
			'��������� �'.$nomer.
		'</div>';
}//kvit_name()
function kvit_content($k) {
	return
	'<table class="content_tab">'.
		'<tr><td>'.

			'<table class="content">'.
				'<tr><td class="label">���� �����:<td>'.FullData($k['dtime']).
				'<tr><td class="label">����������:'.
					'<td>'._deviceName($k['device_id']).
						'<b>'._vendorName($k['vendor_id'])._modelName($k['model_id']).'</b>'.
				($k['color_id'] ? '<tr><td class="label">����:<td>'._color($k['color_id'], $k['color_dop']) : '').
				($k['imei'] ? '<tr><td class="label">IMEI:<td>'.$k['imei'] : '').
				($k['serial'] ? '<tr><td class="label">�������� �����:<td>'.$k['serial'] : '').
				($k['equip'] ? '<tr><td class="label">������������:<td>'.zayavEquipSpisok($k['equip']) : '').
			'</table>'.
			'<div class="line"></div>'.
			'<table class="content">'.
				'<tr><td class="label">��������:<td>'.$k['client_fio'].
				'<tr><td class="label">���������� ��������:<td>'.$k['client_telefon'].
			'</table>'.
			'<div class="line"></div>'.
			'<table class="content">'.
				'<tr><td class="label fault">������������� �� ���� ���������:'.
				'<tr><td>'.$k['defect'].
			'</table>'.

		'<td class="image">'.$k['image'].
	'</table>';
}//kvit_content()
function kvit_conditions() {
	return
	'<div class="conditions">'.
		'<div class="label">������� ���������� �������:</div>'.
		'<ul><li>��������� ����������� ���������� �� 300 �� 1500 ������ � ����������� �� ������ ��������;'.
			'<li>������� �������������� �������������� � ��������� ������� � ������ �����;'.
			'<li>'._wsType(WS_TYPE).' ��������� ������ ���������� �������������;'.
			'<li>������������ ����������� ��� ��������� ��� ������, ���������� � ������ �������, �� ������ ���������;'.
			'<li>'._wsType(WS_TYPE).' �� ����� ��������������� �� ��������� ������ ���������� �� ����������� �������� � ������ ������;'.
			'<li>����� ��������� ������� ��������� '._wsType(WS_TYPE, 2).' �������� ��������� � ����������;'.
			'<li>��������, ���������������� � ������� 3-� ������� ����� ����������� ��������� � ���������� ��� ������������� �������, '.
				'����� ���� ����������� � ������������� ������� ������� ��� ��������� ������������� ��������� ����� '._wsType(WS_TYPE, 2).';'.
			'<li>���� �������� ���������� 30 ���� � ������� ������ ������� ���������;'.
			'<li>�� ��������, ������������ ����������� �����, �����, ����������� ������������� �� ����������������.'.
		'</ul>'.
	'</div>';
}//kvit_conditions()
function kvit_podpis($bottom=0) {
	return
	'<div class="podpis'.($bottom ? ' bottom' : '').'">'.
		'<h1>� ��������� ������� ��������(�).<span>������� ���������: ________________________________</span></h1>'.
//		'<h2>������� ������: __________________________ ('._viewer(VIEWER_ID, 'viewer_name').')'.
		'<h2>������� ������: __________________________ (������� ��������)'.
			'<em>'.FullData(curTime()).'</em>'.
		'</h2>'.
	'</div>';
}//kvit_podpis()
function kvit_cut() {
	return '<div class="cut">����� ������</div>';
}//kvit_cut()

if(!$id = _num($_GET['id']))
	die('�������� id ���������.');

$sql = "SELECT * FROM `zayav_kvit` WHERE `app_id`=".APP_ID." AND `id`=".$id;
if(!$k = query_assoc($sql))
	die('��������� �� ����������.');

if(!$z = _zayavQuery($k['zayav_id']))
	die('������ �� ����������.');

define('BARCODE', '<img src="'.API_HTML.'/barcode/barcode.php?code='.$z['barcode'].'&encoding=ean&mode=gif" />');


echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'.
	'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">'.
	'<head>'.
		'<meta http-equiv="content-type" content="text/html; charset=windows-1251" />'.
		'<title>��������� �� ������ �'.$k['nomer'].'</title>'.
		'<link href="'.APP_HTML.'/css/kvit_html'.(DEBUG ? '' : '.min').'.css?'.VERSION.'" rel="stylesheet" type="text/css" />'.
	'</head>'.
	'<body>'.
		'<img src="'.API_HTML.'/img/printer.png" class="printer" onclick="this.style.display=\'none\';window.print()" title="�����������" />'.
		kvit_head().
		kvit_name($k['nomer']).
		kvit_content($k).
		kvit_conditions().
		kvit_podpis().
		kvit_cut().
		kvit_name($k['nomer'], 1).
		kvit_content($k).
		kvit_podpis(1).
	'</body>'.
	'</html>';


exit;


