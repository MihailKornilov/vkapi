<?php
function _monthFull($n=0) {
	$mon = array(
		1 => 'января',
		2 => 'февраля',
		3 => 'марта',
		4 => 'апреля',
		5 => 'мая',
		6 => 'июня',
		7 => 'июля',
		8 => 'августа',
		9 => 'сентября',
		10 => 'октября',
		11 => 'ноября',
		12 => 'декабря'
	);
	return $n ? $mon[intval($n)] : $mon;
}
function _monthDef($n=0, $firstUp=false) {
	$mon = array(
		1 => 'январь',
		2 => 'февраль',
		3 => 'март',
		4 => 'апрель',
		5 => 'май',
		6 => 'июнь',
		7 => 'июль',
		8 => 'август',
		9 => 'сентябрь',
		10 => 'октябрь',
		11 => 'ноябрь',
		12 => 'декабрь'
	);
	if(!$n) {
		if($firstUp)
			foreach($mon as $k => $m)
				$mon[$k][0] = strtoupper($m);
		return $mon;
	}
	$send = $mon[intval($n)];
	if($firstUp)
		$send[0] = strtoupper($send[0]);
	return $send;
}
function _monthCut($n) {
	$mon = array(
		0 => '',
		1 => 'янв',
		2 => 'фев',
		3 => 'мар',
		4 => 'апр',
		5 => 'май',
		6 => 'июн',
		7 => 'июл',
		8 => 'авг',
		9 => 'сен',
		10 => 'окт',
		11 => 'ноя',
		12 => 'дек'
	);
	return $mon[intval($n)];
}
function _monthLost($dtime) {//проверка, прошло ли 30 дней
	return strtotime($dtime) - time() + 60 * 60 * 24 * 30 > 0 ? 0 : 1;
}
function _week($n) {
	$week = array(
		1 => 'пн',
		2 => 'вт',
		3 => 'ср',
		4 => 'чт',
		5 => 'пт',
		6 => 'сб',
		0 => 'вс'
	);
	return $week[intval($n)];
}
function FullData($v=0, $noyear=0, $cut=0, $week=0) {//пт. 14 апреля 2010
	if($v == '0000-00-00 00:00:00')
		return '';
	if(!$v)
		$v = curTime();
	$d = explode('-', $v);
	return
		($week ? _week(date('w', strtotime($v))).'. ' : '').
		abs($d[2]).' '.
		($cut ? _monthCut($d[1]) : _monthFull($d[1])).
		(!$noyear || date('Y') != $d[0] ? ' '.$d[0] : '');
}
function FullDataTime($v=0, $cut=0) {//14 апреля 2010 в 12:45
	if(!$v)
		$v = curTime();
	$arr = explode(' ', $v);
	$d = explode('-', $arr[0]);
	if(!intval($arr[0]) || empty($arr[1]) || empty($d[1]) || empty($d[2]))
		return '';
	$t = explode(':',$arr[1]);
	if(empty($t[1]) || empty($t[2]))
		return '';
	return
		abs($d[2]).' '.
		($cut ? _monthCut($d[1]) : _monthFull($d[1])).
		(date('Y') == $d[0] ? '' : ' '.$d[0]).
		' в '.$t[0].':'.$t[1];
}
function _curMonday() { //Понедельник в текущей неделе
	// Номер текущего дня недели
	$time = time();
	$curDay = date("w", $time);
	if($curDay == 0) $curDay = 7;
	// Приведение дня к понедельнику
	$time -= 86400 * ($curDay - 1);
	return strftime('%Y-%m-%d', $time);
}
function _curSunday() { //Воскресенье в текущей неделе
	$time = time();
	$curDay = date("w", $time);
	if($curDay == 0) $curDay = 7;
	$time += 86400 * (7 - $curDay);
	return strftime('%Y-%m-%d', $time);

}
function _dataDog($v) {//формат даты как для договора
	$ex = explode(' ', $v);
	$d = explode('-', $ex[0]);
	return $d[2].'/'.$d[1].'/'.$d[0];
}
function curTime() { return strftime('%Y-%m-%d %H:%M:%S'); }
function _dateLost($v) {//проверка, прошла ли дата
	$ex = explode(' ', $v);

	if($ex[0] == '0000-00-00')
		return true;

	if(empty($ex[1]))
		$ex[1] = '23:59:59';

	$v = implode(' ', $ex);
	if(strtotime($v) < strtotime(curTime()))
		return true;

	return false;
}
function _dtimeAdd($v=array()) {//дата и время внесения записи с подсказкой сотрудника, который вносил запись
	return
		'<div class="'._tooltip(_viewerAdded($v['viewer_id_add']), -40).FullDataTime($v['dtime_add'], 1).'</div>'.
	(@$v['viewer_id_del'] ?
		'<div class="ddel '._tooltip(_viewerDeleted($v['viewer_id_del']), -40).
			FullDataTime($v['dtime_del'], 1).
		'</div>'
	: '');
}
