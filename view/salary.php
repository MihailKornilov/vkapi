<?php
function _salary() {
	if(_num(@$_GET['id']))
		return salary_worker($_GET);

	return
		'<div id="salary">'.
			'<div class="headName">�������� �����������</div>'.
			'<div id="spisok">'._salary_spisok().'</div>'.
		'</div>';
}
function _salary_spisok() {
	$sql = "SELECT
				`viewer_id` `id`,
				CONCAT(`first_name`,' ',`last_name`) AS `name`,
				`salary_balans_start`,
				`salary_rate_sum`,
				`salary_rate_period`,
				0 `balans`
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker`
			ORDER BY `dtime_add`";
	$worker = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	//������������ ����������
	$sql = "SELECT
 				`worker_id`,
				IFNULL(SUM(`sum`),0) AS `sum`
			FROM `_salary_accrual`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker_id`
			GROUP BY `worker_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		if(isset($worker[$r['worker_id']]))
			$worker[$r['worker_id']]['balans'] += $r['sum'];

	//���������� �� �������
	$sql = "SELECT
 				`worker_id`,
				IFNULL(SUM(`sum`),0) AS `sum`
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker_id`
			GROUP BY `worker_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		if(isset($worker[$r['worker_id']]))
			$worker[$r['worker_id']]['balans'] += $r['sum'];

	//������
	$sql = "SELECT
 				`worker_id`,
				IFNULL(SUM(`sum`),0) AS `sum`
			FROM `_salary_deduct`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker_id`
			GROUP BY `worker_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		if(isset($worker[$r['worker_id']]))
			$worker[$r['worker_id']]['balans'] -= $r['sum'];

	//��������  �/� (������ �� ��������)
	$sql = "SELECT
				DISTINCT `worker_id`,
				IFNULL(SUM(`sum`),0) AS `sum`
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker_id`
			  AND !`deleted`
			GROUP BY `worker_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		if(isset($worker[$r['worker_id']]))
			$worker[$r['worker_id']]['balans'] -= $r['sum'];

	$send = '<table class="_spisok">'.
				'<tr><th>���'.
					'<th>������'.
					'<th>������';

	foreach($worker as $r) {
		if(!_viewerRule($r['id'], 'RULE_SALARY_SHOW'))
			continue;
		$balans = round($r['balans'] + $r['salary_balans_start'], 2);
		$send .=
			'<tr><td class="fio"><a href="'.URL.'&p=report&d=salary&id='.$r['id'].'" class="name">'.$r['name'].'</a>'.
				'<td class="rate">'.($r['salary_rate_sum'] == 0 ? '' : '<b>'.round($r['salary_rate_sum'], 2).'</b>/'._salaryPeriod($r['salary_rate_period'])).
				'<td class="balans" style="color:#'.($balans < 0 ? 'A00' : '090').'">'._sumSpace($balans);
	}
	$send .= '</table>';

	return $send;
}
function _salaryPeriod($v=false) {
	$arr = array(
		1 => '�����',
		2 => '������',
		3 => '����'
	);
	if($v == false)
		return $arr;
	return $arr[$v];
}

function salary_month_list($v) {
	$filter = salaryFilter($v);

	$acc = array();
	$zp = array();
	for($n = 1; $n <= 12; $n++) {
		$acc[$n] = 0;
		$zp[$n] = 0;
	}

	//���������� �� �������
	$sql = "SELECT
	            `mon`,
				SUM(`sum`) AS `sum`
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker_id`=".$filter['id']."
			  AND `year`=".$filter['year']."
			GROUP BY `mon`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$acc[$r['mon']] = _cena($r['sum']);

	//������������ ����������
	$sql = "SELECT
	            `mon`,
				SUM(`sum`) AS `sum`
			FROM `_salary_accrual`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker_id`=".$filter['id']."
			  AND `year`=".$filter['year']."
			GROUP BY `mon`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$acc[$r['mon']] += _cena($r['sum']);

	//������
	$sql = "SELECT
	            `mon`,
				SUM(`sum`) AS `sum`
			FROM `_salary_deduct`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker_id`=".$filter['id']."
			  AND `year`=".$filter['year']."
			GROUP BY `mon`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$acc[$r['mon']] -= _cena($r['sum']);

	//�/�
	$sql = "SELECT
	            `mon`,
				SUM(`sum`) AS `sum`
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `worker_id`=".$filter['id']."
			  AND `year`=".$filter['year']."
			GROUP BY `mon`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$zp[$r['mon']] = _cena($r['sum']);

	$mon = array();
	foreach(_monthDef(0, 1) as $i => $r)
		$mon[$i] = $r.($acc[$i] || $zp[$i]? '<em>'.$acc[$i].'/'.$zp[$i].'</em>' : '');
	return _radio('salmon', $mon, $filter['mon'], 1);
}
function salaryFilter($v) {
	$v = array(
		'id' => _num(@$v['id']),
		'mon' => _num(@$v['mon']) ? intval($v['mon']) : intval(strftime('%m')),
		'year' => _num(@$v['year']) ? intval($v['year']) : intval(strftime('%Y')),
		'acc_id' => _num(@$v['acc_id']),
		'acc_show' => _bool(@$v['acc_show']) //���������� ����� ������ ����������
	);
	$v['month'] = _monthDef($v['mon'], 1).' '.$v['year'];
	$v['year-mon'] = $v['year'].'-'.($v['mon'] < 10 ? 0 : '').$v['mon'];
	if($v['acc_id'])
		$v['acc_show'] = 1;
	return $v;
}
function salaryWorkerBalans($worker_id, $color=0, $ws_id=WS_ID) {//��������� �������� ������� �� ����������
	$start = _viewer($worker_id, 'balans_start');

	//������������ ����������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_salary_accrual`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".$ws_id."
			  AND `worker_id`=".$worker_id;
	$acc = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//���������� �� �������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".$ws_id."
			  AND `worker_id`=".$worker_id;
	$zayav = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_salary_deduct`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".$ws_id."
			  AND `worker_id`=".$worker_id;
	$deduct = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//�������� ��
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".$ws_id."
			  AND `worker_id`=".$worker_id."
			  AND !`deleted`";
	$zp = query_value($sql, GLOBAL_MYSQL_CONNECT);

	$balans = round($acc + $zayav - $deduct - $zp + $start, 2);

	if($color)
		return '<b style="color:#'.($balans < 0 ? 'A00' : '090').'">'._sumSpace($balans).'</b>';

	return $balans;
}
function salaryWorkerRate($worker_id) {//��������� ������ ����������
	$u = _viewer($worker_id);
	$period = '';
	switch($u['rate_period']) {
		case 1: $period = $u['rate_day'].'-� ����� ������'; break;
		case 2: $period = '�����������, '.$u['rate_day'].'-� ���� ������'; break;
		case 3: $period = '���������'; break;
	}
	return
		$u['rate_sum'] ?
			'<b>'._sumSpace($u['rate_sum']).'</b> ���.<span>('.$period.')</span>'
			:
			'���';
}
function salary_worker($v) {
	$filter = salaryFilter($v);

	if(!$r = _viewerWorkerQuery($filter['id']))
		return _err('���������� �� ����������.');

	define('WORKER_OK', true);//��� ������ �������

	$acc_show = $filter['acc_show'] ? ' class="acc-show"' : '';

	return
	'<script type="text/javascript">'.
		'var SALARY={'.
			'worker_id:'.$filter['id'].','.
			'year:'.$filter['year'].','.
			'mon:'.$filter['mon'].','.
			'rate_sum:'._viewer($filter['id'], 'rate_sum').','.
			'rate_period:'._viewer($filter['id'], 'rate_period').','.
			'rate_day:'._viewer($filter['id'], 'rate_day').
		'};'.
	'</script>'.
	'<div id="salary-worker">'.
		'<div class="headName">'._viewer($filter['id'], 'viewer_name').': ������� �/� �� <em>'.$filter['month'].'</em>.</div>'.
		'<h2>������: '.
			'<a  val="5:'.$filter['id'].'" class="_balans-show'._tooltip('������� ��������', -40).salaryWorkerBalans($filter['id'], 1).'</a> ���.'.
			'<input type="hidden" id="action" />'.
		'<h2>'.
		'<h1>������: <em>'.salaryWorkerRate($filter['id']).'</em></h1>'.
		salary_worker_client($filter['id']).
		'<div id="spisok-acc"'.$acc_show.'>'.salary_worker_acc($filter).'</div>'.
		'<div id="spisok-list">'.salary_worker_list($filter).'</div>'.
		'<div id="spisok-zp">'.salary_worker_zp($filter).'</div>'.
	'</div>';
}

function salary_worker_client($worker_id) {//���� ����� � ��������
	$sql = "SELECT *
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `worker_id`=".$worker_id."
			LIMIT 1";
	if($c = query_assoc($sql, GLOBAL_MYSQL_CONNECT)) {
		$c = _clientVal($c['id']);
		if($c['balans'] < 0)
			$send =
				'������������ ���������� ���� � ������� '.
				'<a href="'.URL.'&p=client&d=info&id='.$c['id'].'" class="dolg '._tooltip('������� �� ���������� ��������', -85).
					$c['balans'].
				'</a> ���.';
		else
			$send = '��������� �������� � ������� '.$c['link'].'.';
		return '<div class="_info">'.$send.'</div>';
	}

	return '';
}
function salary_worker_acc($v) {
	$filter = salaryFilter($v);

	$sql = "SELECT
				*,
				'����������' `type`,
				'accrual' `class`,
				0 `zayav_id`
			FROM `_salary_accrual`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker_id`=".$filter['id']."
			  AND `year`=".$filter['year']."
			  AND `mon`=".$filter['mon'];
	$accrual = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	$sql = "SELECT
				*,
				-`sum` `sum`,
				'�����' `type`,
				'deduct' `class`,
				0 `zayav_id`
			FROM `_salary_deduct`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker_id`=".$filter['id']."
			  AND `year`=".$filter['year']."
			  AND `mon`=".$filter['mon'];
	$deduct = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	$sql = "SELECT
				*,
				'����������' `type`,
				'expense' `class`
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker_id`=".$filter['id']."
			  AND `year`=".$filter['year']."
			  AND `mon`=".$filter['mon'];
	$zayav = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	$zayav = _zayavValToList($zayav);

	$spisok = _arrayTimeGroup($accrual);
	$spisok += _arrayTimeGroup($zayav, $spisok);
	$spisok += _arrayTimeGroup($deduct, $spisok);
	krsort($spisok);

	$send = '';
	$accSum = 0;
	$zayavSum = 0;
	$dSum = 0;
	$chAllShow = 0;//���������� ������� ��� ��������� ���� ����������
	foreach($spisok as $r) {
		$show = $filter['acc_id'] == $r['id'] ? ' show' : '';
		$list = $r['salary_list_id'] ? ' list' : '';

		$about = $r['zayav_id']?
			$r['zayav_color'].$r['zayav_dolg']
			:
			$r['about'];

		$del = $r['zayav_id'] || $list ? '' :
				_iconDel(array(
					'id' => $r['id'],
					'class' => $r['type'] == '����������' ? 'worker-acc-del' : 'worker-deduct-del'
				));

		$send .=
			'<tr class="'.$show.$list.'">'.
				'<td class="ch" val="'.$r['class'].':'.$r['id'].'">'.($list ? '' : _check('ch'.$r['id'])).
				'<td class="type">'.$r['type'].
				'<td class="sum">'.round($r['sum'], 2).
				'<td class="about">'.$about.
				'<td class="dtime">'.FullDataTime($r['dtime_add']).
				'<td class="ed">'.$del;

		$accSum += (!$r['zayav_id'] && $r['sum'] > 0 ? $r['sum'] : 0);
		$zayavSum += ($r['zayav_id'] ? $r['sum'] : 0);
		$dSum += ($r['sum'] < 0 ? $r['sum'] : 0);

		if(!$list)
			$chAllShow = 1;
	}

	$allCount = count($accrual) + count($deduct) + count($zayav);
	$send =
		'<h3><b>���������� � ������</b></h3>'.
		'<h4>'.
			(!$allCount ?
				'<div>������� ���.</div>' :
				'<a id="podr">��������</a>'.
				'<a id="hid">������ �����������</a>'.
				'<div><u>����� <b>'.$allCount.'</b> �����'._end($allCount, '�', '�', '��').'. ����� ����� <b>'._sumSpace($accSum + $zayavSum + $dSum).'</b> ���.</u></div>'
			).
			($accSum ? '<div>����������: <b>'.count($accrual).'</b> �� �����<b> '._sumSpace($accSum).'</b> ���.</div>' : '').
			($zayavSum ? '<div>������: <b>'.count($zayav).'</b> �� �����<b> '._sumSpace($zayavSum).'</b> ���.</div>' : '').
			($dSum ? '<div>������: <b>'.count($deduct).'</b> �� �����<b> '._sumSpace($dSum).'</b> ���.</div>' : '').
		'</h4>'.

		'<div id="sp">'.
			'<table class="_spisok">'.
				'<tr>'.
					'<th>'.($chAllShow ? _check('check_all') : '').
					'<th>���'.
					'<th>�����'.
					'<th>��������'.
					'<th>����'.
					'<th>'.
				$send.
			'</table>'.
		'</div>';
	return $send;
}
function salary_worker_list($v) {
	$filter = salaryFilter($v);

	$sql = "SELECT *
			FROM `_salary_list`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker_id`=".$filter['id']."
			  AND `year`=".$filter['year']."
			  AND `mon`=".$filter['mon']."
			ORDER BY `id`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$send =
		'<h3><b>����� ������ �/�</b></h3>'.
		'<table class="_spisok">'.
			'<tr><th>������������'.
				'<th>� ������'.
				'<th>���� ��������'.
				'<th>';
	$n = 1;
	foreach($spisok as $r)
		$send .=
			'<tr><td class="about">'.
					'<a class="img_xls" val="'.$r['id'].'">���� ������ �/� '.($n++).'</a>'.
				'<td class="sum">'._cena($r['sum']).
				'<td class="dtime">'.FullData($r['dtime_add']).
				'<td class="ed">'._iconDel($r + array('class'=>'salary-list-del'));
	$send .= '</table>';
	return $send;
}
function salary_worker_zp($v) {
	$filter = salaryFilter($v);

	$sql = "SELECT *
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `worker_id`=".$filter['id']."
			  AND `year`=".$filter['year']."
			  AND `mon`=".$filter['mon']."
			ORDER BY `id` DESC";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$zp = '';
	$summa = 0;
	foreach($spisok as $r) {
		$sum = _cena($r['sum']);
		$summa += $sum;
		$zp .= '<tr>'.
			'<td class="sum">'.$sum.
			'<td class="about"><span class="type">'._invoice($r['invoice_id']).(empty($r['about']) ? '' : ':').'</span> '.$r['about'].
			'<td class="dtime">'.FullDataTime($r['dtime_add']).
			'<td class="ed">'._iconDel($r + array('class'=>'worker-zp-del'));
	}
	$send =
		'<h3>'.
			'<b>�/� �� '.$filter['month'].'</b>:'.
			'<span><a class="worker-zp-add">������ �/�</a> :: �����: <b>'.$summa.'</b> ���.</span>'.
		'</h3>'.
		'<table class="_spisok">'.
			'<tr><th>�����'.
				'<th>��������'.
				'<th>����'.
				'<th>'.
				$zp.
		'</table>';

	return $send;
}
