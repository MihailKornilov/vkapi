<?php
function kvit_head() {
	return
	'<table class="head">'.
		'<tr><td>'.BARCODE.
			'<td class="rekvisit">'.
				'<h1>'._appType(APP_TYPE).' «<b>'._app('name').'</b>»</h1>'.
				'<h1>Адрес: '._app('adres_yur').'.</h1>'.
				'<h2>Телефон: '._app('phone').'. Время работы: '._app('time_work').'.</h2>'.
	'</table>';
}
function kvit_name($nomer, $barcode=0) {
	return
		'<div class="name">'.
			($barcode ? BARCODE : '').
			'Квитанция №'.$nomer.
		'</div>';
}
function kvit_content($k, $z) {
	$t = $k['tovar_id'] ? _tovarQuery($k['tovar_id']) : '';
	return
	'<table class="content_tab">'.
		'<tr><td>'.

			'<table class="content">'.
				'<tr><td class="label">Дата приёма:<td>'.FullData($z['dtime_add']).

($k['tovar_id'] ?
				'<tr><td class="label">Устройство:'.
					'<td>'.$t['name']
: '').

				($k['color_id'] ? '<tr><td class="label">Цвет:<td>'._color($k['color_id'], $k['color_dop']) : '').
				($k['imei'] ? '<tr><td class="label">IMEI:<td>'.$k['imei'] : '').
				($k['serial'] ? '<tr><td class="label">Серийный номер:<td>'.$k['serial'] : '').
				($k['equip'] ? '<tr><td class="label">Комплектация:<td>'._tovarEquip('spisok', $k['equip']) : '').
			'</table>'.
			'<div class="line"></div>'.
			'<table class="content">'.
				'<tr><td class="label">Заказчик:<td>'.$k['client_name'].
				'<tr><td class="label">Контактные телефоны:<td>'.$k['client_phone'].
			'</table>'.
			'<div class="line"></div>'.
			'<table class="content">'.
				'<tr><td class="label fault">Неисправность со слов Заказчика:'.
				'<tr><td>'.$k['defect'].
			'</table>'.

		'<td class="image">'.$k['image'].
	'</table>';
}
function kvit_conditions() {
	return
	'<div class="conditions">'.
		'<div class="label">Условия проведения ремонта:</div>'.
		'<ul><li>Стоимость диагностики составляет от 300 до 1500 рублей в зависимости от модели аппарата;'.
			'<li>Стороны предварительно договариваются о стоимости ремонта в устной форме;'.
			'<li>'._appType(APP_TYPE).' устраняет только заявленные неисправности;'.
			'<li>Настоятельно рекомендуем Вам сохранять все данные, хранящиеся в памяти изделия, на других носителях;'.
			'<li>'._appType(APP_TYPE).' не несет ответственности за возможную потерю информации на устройствах хранения и записи данных;'.
			'<li>Ремонт может быть увеличен на срок до 30 дней, если в процессе будет повреждён какой-либо компонент аппарата по вине мастерской.'.
				'<br />'.
				'Стоимость ремонта изменена не будет;'.
			'<li>После окончания ремонта сотрудник '._appType(APP_TYPE, 2).' сообщает Заказчику о готовности;'.
			'<li>Аппараты, невостребованные в течение 3-х месяцев после уведомления Заказчика о готовности или невозможности ремонта, '.
				'могут быть реализованы в установленном законом порядке для погашения задолженности Заказчика перед '._appType(APP_TYPE, 2).';'.
			'<li>Срок гарантии составляет 30 дней с момента выдачи изделия Заказчику;'.
			'<li>Мастерская не будет нести ответственности, если в процессе диагностики аппарата после воды будут повреждены какие-либо его части, '.
				'а также если он перестанет включаться, даже если до этого он включался;'.
			'<li>На аппараты после воды или удара гарантийные обязательства не распространяются.'.
		'</ul>'.
	'</div>';
}
function kvit_podpis($bottom=0) {
	return
	'<div class="podpis'.($bottom ? ' bottom' : '').'">'.
		'<h1>С условиями ремонта согласен(а).<span>Подпись Заказчика: ________________________________</span></h1>'.
//		'<h2>Аппарат принял: __________________________ ('._viewer(VIEWER_ID, 'viewer_name').')'.
		'<h2>Аппарат принял: __________________________'.
			'<em>'.FullData(curTime()).'</em>'.
		'</h2>'.
	'</div>';
}
function kvit_cut() {
	return '<div class="cut">линия отреза</div>';
}

if(!$id = _num($_GET['id']))
	die('Неверный id квитанции.');

$sql = "SELECT *
		FROM `_zayav_kvit`
		WHERE `app_id`=".APP_ID."
		  AND `id`=".$id;
if(!$k = query_assoc($sql))
	die('Квитанции не существует.');

if(!$z = _zayavQuery($k['zayav_id']))
	die('Заявки не существует.');

define('BARCODE', '<img src="'.API_HTML.'/inc/barcode/barcode.php?code='.$z['barcode'].'&encoding=ean&mode=gif" />');


echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'.
	'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">'.
	'<head>'.
		'<meta http-equiv="content-type" content="text/html; charset=windows-1251" />'.
		'<title>Квитанция по заявке №'.$z['nomer'].'</title>'.
		'<link href="'.API_HTML.'/css/kvit_html'.(DEBUG ? '' : '.min').'.css?'.VERSION.'" rel="stylesheet" type="text/css" />'.
	'</head>'.
	'<body>'.
		'<img src="'.API_HTML.'/img/printer.png" class="printer" onclick="this.style.display=\'none\';window.print()" title="Распечатать" />'.
		kvit_head().
		kvit_name($z['nomer']).
		kvit_content($k, $z).
		kvit_conditions().
		kvit_podpis().
		kvit_cut().
		kvit_name($z['nomer'], 1).
		kvit_content($k, $z).
		kvit_podpis(1).
	'</body>'.
	'</html>';
