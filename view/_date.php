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