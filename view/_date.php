<?php
function _monthFull($n=0) {
	$mon = array(
		1 => '������',
		2 => '�������',
		3 => '�����',
		4 => '������',
		5 => '���',
		6 => '����',
		7 => '����',
		8 => '�������',
		9 => '��������',
		10 => '�������',
		11 => '������',
		12 => '�������'
	);
	return $n ? $mon[intval($n)] : $mon;
}
function _monthDef($n=0, $firstUp=false) {
	$mon = array(
		1 => '������',
		2 => '�������',
		3 => '����',
		4 => '������',
		5 => '���',
		6 => '����',
		7 => '����',
		8 => '������',
		9 => '��������',
		10 => '�������',
		11 => '������',
		12 => '�������'
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
		1 => '���',
		2 => '���',
		3 => '���',
		4 => '���',
		5 => '���',
		6 => '���',
		7 => '���',
		8 => '���',
		9 => '���',
		10 => '���',
		11 => '���',
		12 => '���'
	);
	return $mon[intval($n)];
}
function _monthLost($dtime) {//��������, ������ �� 30 ����
	return strtotime($dtime) - time() + 60 * 60 * 24 * 30 > 0 ? 0 : 1;
}
function _week($n) {
	$week = array(
		1 => '��',
		2 => '��',
		3 => '��',
		4 => '��',
		5 => '��',
		6 => '��',
		0 => '��'
	);
	return $week[intval($n)];
}
function FullData($v=0, $noyear=0, $cut=0, $week=0) {//��. 14 ������ 2010
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
function FullDataTime($v=0, $cut=0) {//14 ������ 2010 � 12:45
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
		' � '.$t[0].':'.$t[1];
}
function _curMonday() { //����������� � ������� ������
	// ����� �������� ��� ������
	$time = time();
	$curDay = date("w", $time);
	if($curDay == 0) $curDay = 7;
	// ���������� ��� � ������������
	$time -= 86400 * ($curDay - 1);
	return strftime('%Y-%m-%d', $time);
}
function _curSunday() { //����������� � ������� ������
	$time = time();
	$curDay = date("w", $time);
	if($curDay == 0) $curDay = 7;
	$time += 86400 * (7 - $curDay);
	return strftime('%Y-%m-%d', $time);

}
function _dataDog($v) {//������ ���� ��� ��� ��������
	$ex = explode(' ', $v);
	$d = explode('-', $ex[0]);
	return $d[2].'/'.$d[1].'/'.$d[0];
}
function curTime() { return strftime('%Y-%m-%d %H:%M:%S'); }
function _dateLost($v) {//��������, ������ �� ����
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
function _dtimeAdd($v=array()) {//���� � ����� �������� ������ � ���������� ����������, ������� ������ ������
	return
		'<div class="'._tooltip(_viewerAdded($v['viewer_id_add']), -40).FullDataTime($v['dtime_add'], 1).'</div>'.
	(@$v['viewer_id_del'] ?
		'<div class="ddel '._tooltip(_viewerDeleted($v['viewer_id_del']), -40).
			FullDataTime($v['dtime_del'], 1).
		'</div>'
	: '');
}
