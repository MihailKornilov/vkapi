<?php
function _salary() {
	if(_num(@$_GET['id']))
		return salary_worker($_GET);

	if(RULE_WORKER_SALARY_VIEW == 1)
		return salary_worker(array('id'=>VIEWER_ID));

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
				0 `balans`,
				'' `dolg`
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `worker`
			  AND !`hidden`
			  ".(RULE_WORKER_SALARY_VIEW == 1 ? " AND `viewer_id`=".VIEWER_ID : '')."
			ORDER BY `dtime_add`";
	$worker = query_arr($sql);

	//������������ ����������
	$sql = "SELECT
 				`worker_id`,
				IFNULL(SUM(`sum`),0) AS `sum`
			FROM `_salary_accrual`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`
			GROUP BY `worker_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		if(isset($worker[$r['worker_id']]))
			$worker[$r['worker_id']]['balans'] += $r['sum'];

	//������
	$sql = "SELECT
 				`worker_id`,
				IFNULL(SUM(`sum`),0) AS `sum`
			FROM `_salary_bonus`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`
			GROUP BY `worker_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		if(isset($worker[$r['worker_id']]))
			$worker[$r['worker_id']]['balans'] += $r['sum'];

	//���������� �� �������
	$sql = "SELECT
 				`worker_id`,
				IFNULL(SUM(`sum`),0) AS `sum`
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`
			GROUP BY `worker_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		if(!isset($worker[$r['worker_id']]))
			continue;
		$worker[$r['worker_id']]['balans'] += $r['sum'];
	}

	//������������ �� �������
	$sql = "SELECT
 				`worker_id`,
				IFNULL(SUM(`sum`),0) AS `sum`
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`
			  AND (!`year` OR !`mon`)
			GROUP BY `worker_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		if(!isset($worker[$r['worker_id']]))
			continue;
		if(!_viewerRule($r['worker_id'], 'RULE_SALARY_ZAYAV_ON_PAY'))
			continue;
		$worker[$r['worker_id']]['balans'] -= $r['sum'];
	}

	//������
	$sql = "SELECT
 				`worker_id`,
				IFNULL(SUM(`sum`),0) AS `sum`
			FROM `_salary_deduct`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`
			GROUP BY `worker_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		if(isset($worker[$r['worker_id']]))
			$worker[$r['worker_id']]['balans'] -= $r['sum'];

	//��������  �/� (������ �� ��������)
	$sql = "SELECT
				DISTINCT `worker_id`,
				IFNULL(SUM(`sum`),0) AS `sum`
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`
			  AND !`deleted`
			GROUP BY `worker_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		if(isset($worker[$r['worker_id']]))
			$worker[$r['worker_id']]['balans'] -= $r['sum'];

	//���� �����������
	$sql = "SELECT
 				`worker_id`,
				`balans`
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`
			  AND `balans`<0";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		if(isset($worker[$r['worker_id']]))
			$worker[$r['worker_id']]['dolg'] = _sumSpace(_cena(abs($r['balans'])));


	$send = '<table class="_spisok">'.
				'<tr><th>���'.
					'<th>������'.
					'<th>������'.
					'<th>����������<br />����';

	foreach($worker as $r) {
		if(!_viewerRule($r['id'], 'RULE_SALARY_SHOW'))
			continue;
		$balans = round($r['balans'] + $r['salary_balans_start'], 2);
		$send .=
			'<tr><td class="fio"><a href="'.URL.'&p=report&d=salary&id='.$r['id'].'" class="name">'.$r['name'].'</a>'.
				'<td class="rate">'.($r['salary_rate_sum'] == 0 ? '' : '<b>'.round($r['salary_rate_sum'], 2).'</b>/'._salaryPeriod($r['salary_rate_period'])).
				'<td class="balans" style="color:#'.($balans < 0 ? 'A00' : '090').'">'._sumSpace($balans).
				'<td class="dolg">'.$r['dolg'];
	}
	$send .= '</table>';

	return $send;
}
function _salaryPeriod($v=false) {
	$arr = array(
		1 => '�����',
		2 => '������',
		3 => '���������'
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
			  AND `worker_id`=".$filter['id']."
			  AND `year`=".$filter['year']."
			GROUP BY `mon`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$acc[$r['mon']] = round($r['sum']);

	//������������ ����������
	$sql = "SELECT
	            `mon`,
				SUM(`sum`) AS `sum`
			FROM `_salary_accrual`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`=".$filter['id']."
			  AND `year`=".$filter['year']."
			GROUP BY `mon`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$acc[$r['mon']] += round($r['sum']);

	//������
	$sql = "SELECT
	            `mon`,
				SUM(`sum`) AS `sum`
			FROM `_salary_bonus`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`=".$filter['id']."
			  AND `year`=".$filter['year']."
			GROUP BY `mon`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$acc[$r['mon']] += round($r['sum']);

	//������
	$sql = "SELECT
	            `mon`,
				SUM(`sum`) AS `sum`
			FROM `_salary_deduct`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`=".$filter['id']."
			  AND `year`=".$filter['year']."
			GROUP BY `mon`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$acc[$r['mon']] -= round($r['sum']);

	//�/�
	$sql = "SELECT
	            `mon`,
				SUM(`sum`) AS `sum`
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `worker_id`=".$filter['id']."
			  AND `year`=".$filter['year']."
			GROUP BY `mon`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$zp[$r['mon']] = round($r['sum']);

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
		'acc_show' => _bool(@$v['acc_show']), //���������� ����� ������ ����������
		'list_type' => 'html' //� ����� ���� ���������� ����� ������ ��: html, js
	);
	$v['month'] = _monthDef($v['mon'], 1).' '.$v['year'];
	$v['year-mon'] = $v['year'].'-'.($v['mon'] < 10 ? 0 : '').$v['mon'];
	if($v['acc_id'])
		$v['acc_show'] = 1;
	return $v;
}
function salaryWorkerBalans($worker_id, $color=0) {//��������� �������� ������� �� ����������
	$start = _viewer($worker_id, 'balans_start');
	$onPay = _viewerRule($worker_id, 'RULE_SALARY_ZAYAV_ON_PAY');

	//������������ ����������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_salary_accrual`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`=".$worker_id;
	$acc = query_value($sql);

	//������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_salary_bonus`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`=".$worker_id;
	$bonus = query_value($sql);

	//���������� �� �������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  ".($onPay ? 'AND `year` AND `mon`' : '')."
			  AND `worker_id`=".$worker_id;
	$zayav = query_value($sql);

	//������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_salary_deduct`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`=".$worker_id;
	$deduct = query_value($sql);

	//�������� ��
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`=".$worker_id."
			  AND !`deleted`";
	$zp = query_value($sql);

	$balans = round($acc + $bonus + $zayav - $deduct - $zp + $start, 2);

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

	if(RULE_WORKER_SALARY_VIEW == 1 && $filter['id'] != VIEWER_ID)
		return _err('��� ���� ��� ���������.');

	define('WORKER_OK', true);//��� ������ �������

	$acc_show = $filter['acc_show'] ? ' class="acc-show"' : '';

	return
	'<script>'.
		'var SALARY={'.
			'worker_id:'.$filter['id'].','.
			'year:'.$filter['year'].','.
			'mon:'.$filter['mon'].','.
			'rate_sum:'._viewer($filter['id'], 'rate_sum').','.
			'rate_period:'._viewer($filter['id'], 'rate_period').','.
			'rate_day:'._viewer($filter['id'], 'rate_day').','.
			'list:'.salary_worker_list(array('list_type'=>'js') + $filter).
		'};'.
	'</script>'.
	'<div id="salary-worker">'.
		'<div class="headName">'._viewer($filter['id'], 'viewer_name').': ������� �/� �� <em>'.$filter['month'].'</em>.</div>'.
		'<h2>������: '.
			'<a onclick="_balansShow(5,'.$filter['id'].')" class="balans'._tooltip('������� ��������', -40).salaryWorkerBalans($filter['id'], 1).'</a> ���.'.
			'<input type="hidden" id="action" />'.
		'<h2>'.
		'<h1>������: <em>'.salaryWorkerRate($filter['id']).'</em></h1>'.
		salary_worker_client($filter['id']).
		'<div id="spisok-acc"'.$acc_show.'>'.salary_worker_acc($filter).'</div>'.
		'<div id="spisok-noacc">'.salary_worker_noacc($filter).'</div>'.
		'<div id="spisok-list">'.salary_worker_list($filter).'</div>'.
		'<div id="spisok-zp">'.salary_worker_zp($filter).'</div>'.
	'</div>';
}

function salary_worker_client($worker_id) {//���� ����� � ��������
	$sql = "SELECT *
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `worker_id`=".$worker_id."
			LIMIT 1";
	if(!$c = query_assoc($sql))
		return '';

	$c = _clientVal($c['id']);
	return
		'<div class="_info">'.
			'��������� �������� � ������� '.$c['link'].'.'.
			($c['balans'] < 0 ? '<div class="dolg'._tooltip('���������� ����', -16, 'l')._sumSpace(abs($c['balans'])).'</div>' : '').
		'</div>';
}
function salary_worker_acc($filter) {
	$sql = "SELECT
				*,
				'����������' `type`,
				'accrual' `class`,
				0 `zayav_id`
			FROM `_salary_accrual`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`=".$filter['id']."
			  AND `year`=".$filter['year']."
			  AND `mon`=".$filter['mon'];
	$accrual = query_arr($sql);

	$sql = "SELECT
				*,
				'�����' `type`,
				'bonus' `class`
			FROM `_salary_bonus`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`=".$filter['id']."
			  AND `year`=".$filter['year']."
			  AND `mon`=".$filter['mon'];
	$bonus = query_arr($sql);
	$bonus = _zayavValToList($bonus);

	$sql = "SELECT
				*,
				-`sum` `sum`,
				'�����' `type`,
				'deduct' `class`,
				0 `zayav_id`
			FROM `_salary_deduct`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`=".$filter['id']."
			  AND `year`=".$filter['year']."
			  AND `mon`=".$filter['mon'];
	$deduct = query_arr($sql);

	$sql = "SELECT
				*,
				'����������' `type`,
				'expense' `class`
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`=".$filter['id']."
			  AND `year`=".$filter['year']."
			  AND `mon`=".$filter['mon'];
	$zayav = query_arr($sql);
	$zayav = _zayavValToList($zayav);

	$spisok = _arrayTimeGroup($accrual);
	$spisok += _arrayTimeGroup($bonus, $spisok);
	$spisok += _arrayTimeGroup($zayav, $spisok);
	$spisok += _arrayTimeGroup($deduct, $spisok);
	krsort($spisok);

	$send = '';
	$accSum = 0;    //����� ������������ ����������
	$bonusSum = 0;  //����� �������
	$zayavSum = 0;  //����� ���������� �� �������
	$dSum = 0;      //����� �������
	$chAllShow = 0; //���������� ������� ��� ��������� ���� ����������
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

		$accSum += $r['class'] == 'accrual' ? $r['sum'] : 0;
		$bonusSum += $r['class'] == 'bonus' ? $r['sum'] : 0;
		$dSum += $r['class'] == 'deduct' ? $r['sum'] : 0;
		$zayavSum += $r['class'] == 'expense' ? $r['sum'] : 0;

		if(!$list)
			$chAllShow = 1;
	}

	$allCount = count($accrual) + count($bonus) + count($deduct) + count($zayav);
	$send =
		'<h3><b>���������� � ������</b></h3>'.
		'<h4>'.
			(!$allCount ?
				'<div>������� ���.</div>' :
				'<a id="podr">��������</a>'.
				'<a id="hid">������ �����������</a>'.
				'<div><u>����� <b>'.$allCount.'</b> �����'._end($allCount, '�', '�', '��').'. ����� ����� <b>'._sumSpace($accSum + $bonusSum + $zayavSum + $dSum).'</b> ���.</u></div>'
			).
			($accSum ? '<div>����������: <b>'.count($accrual).'</b> �� �����<b> '._sumSpace($accSum).'</b> ���.</div>' : '').
			($bonusSum ? '<div>������: <b>'.count($bonus).'</b> �� �����<b> '._sumSpace($bonusSum).'</b> ���.</div>' : '').
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
function salary_worker_noacc($filter) {//���������� ���������� �� �������
	//������������ ������ � ������� ������
	if($filter['year'] != strftime('%Y') || $filter['mon'] != strftime('%m'))
		return '';

	$sql = "SELECT *
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`=".$filter['id']."
			  AND (!`year` OR !`mon`)";
	if(!$spisok = query_arr($sql))
		return '';

	$spisok = _zayavValToList($spisok);

	$send = '';
	$accSum = 0;
	$zayav_ids = '';
	foreach($spisok as $r) {
		$show = $filter['acc_id'] == $r['id'] ? ' show' : '';

		$send .=
			'<tr class="noacc'.$show.'">'.
				'<td class="sum">'.round($r['sum'], 2).
				'<td class="about">'.$r['zayav_color'].$r['zayav_dolg'].
				'<td class="dtime">'.FullDataTime($r['dtime_add']);

		$accSum += $r['sum'];
		$zayav_ids .= ','.$r['zayav_id'];
	}

	//����� ����� �� �������
	$sql = "SELECT IFNULL(SUM(`sum_dolg`),0)
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `sum_dolg`<0
			  AND `id` IN (0".$zayav_ids.")";
	$zayavDolgSum = abs(query_value($sql));

	$send =
		'<h3><b>�� ��������� �� �������</b>'.
			(VIEWER_ADMIN ? '<a id="noacc-recalc">�����������</a>' : '').
		'</h3>'.
		'<table id="noacc-count">'.
			'<tr><td class="c">'.count($spisok).'<td>�����'._end(count($spisok), '�', '�', '��').'.'.
			'<tr><td class="c">'._sumSpace($accSum).'<td> ���. �� ���������.'.
			'<tr><td class="c">'._sumSpace($zayavDolgSum).'<td> ���. ����� ����� �� �������.'.
		'</table>'.
		'<table class="_spisok">'.
			'<tr>'.
				'<th>�����'.
				'<th>������'.
				'<th>����'.
			$send.
		'</table>';

	return $send;
}
function salary_worker_list($v) {
	$sql = "SELECT
				*,
				0 `pay`
			FROM `_salary_list`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`=".$v['id']."
			  AND `year`=".$v['year']."
			  AND `mon`=".$v['mon']."
			ORDER BY `id` DESC";
	$spisok = query_arr($sql);

	if($v['list_type'] == 'js') {
		$send = array();
		foreach($spisok as $r)
			$send[$r['id']] = LIST_VYDACI.' �'.$r['nomer'];
		return _selJson($send);
	}

	if($v['list_type'] == 'array') {
		$send = array();
		foreach($spisok as $r)
			$send[$r['id']] = LIST_VYDACI.' �'.$r['nomer'];
		return _sel($send);
	}

	if(!$spisok)
		return '';

	$sql = "SELECT
				`salary_list_id`,
				SUM(`sum`) `sum`
			FROM `_money_expense`
			WHERE `salary_list_id` IN (".implode(',', array_keys($spisok)).")
			  AND !`deleted`
			GROUP BY `salary_list_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['salary_list_id']]['pay'] = _cena($r['sum']);

	//���� �� ������ ��, ������� �� �������� ����������. ��� ����������� �������� �����
	$sql = "SELECT COUNT(`id`)
			FROM `_money_expense`
			WHERE !`deleted`
			  AND !`salary_avans`
			  AND `salary_list_id` IN (".implode(',', array_keys($spisok)).")
			GROUP BY `salary_list_id`";
	$payNoAvans = query_value($sql);

	$send =
		'<h3><b>'.LIST_VYDACI.'</b></h3>'.
		'<table class="_spisok">'.
			'<tr><th>������������'.
				'<th>���������'.
				'<th>������'.
				'<th>�������'.
				'<th>����'.
				'<th>';
	foreach($spisok as $r) {
		$diff = $r['sum'] - $r['pay'];
		$send .=
			'<tr><td class="about"><a class="img_xls" val="'.$r['id'].'">'.LIST_VYDACI.' �'.$r['nomer'].'</a>'.
				'<td class="sum acc">'._sumSpace(_cena($r['sum'])).
				'<td class="sum pay">'.($r['pay'] ? _sumSpace($r['pay']) : '').
				'<td class="sum '.($diff < 0 ? 'red'._tooltip('������ ������, ��� ���������', -60) : 'grey">')._sumSpace($diff).
				'<td class="dtime">'.FullData($r['dtime_add']).
				'<td class="ed">'._iconDel(array('class'=>'salary-list-del','nodel'=>$payNoAvans) + $r);
	}
	$send .= '</table>';
	return $send;
}
function salary_worker_zp($v) {
	$sql = "SELECT *
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `worker_id`=".$v['id']."
			  AND `year`=".$v['year']."
			  AND `mon`=".$v['mon']."
			ORDER BY `id` DESC";
	if(!$spisok = query_arr($sql))
		return '';

	$list = array();
	if($ids = _idsGet($spisok, 'salary_list_id')) {
		$sql = "SELECT
					`id`,
					`nomer`
				FROM `_salary_list`
				WHERE `id` IN (".$ids.")";
		$list = query_ass($sql);
	}

	$zp = '';
	$summa = 0;
	foreach($spisok as $r) {
		$sum = _cena($r['sum']);
		$summa += $sum;
		$zp .= '<tr>'.
			'<td class="sum">'._sumSpace($sum).
			'<td class="about">'.
				'<span class="type">'.
					_invoice($r['invoice_id']).(empty($r['about']) ? '' : ':').
				'</span> '.
				$r['about'].
				($r['salary_list_id'] ?
					'<div class="nl">'.
						LIST_VYDACI.' �'.$list[$r['salary_list_id']].
						($r['salary_avans'] ? ', �����' : '').
					'.</div>'
				: '').
			'<td class="dtime">'.FullDataTime($r['dtime_add']).
			'<td class="ed">'._iconEdit($r)._iconDel($r);
	}
	$send =
		'<h3>'.
			'<b>�/� �� '.$v['month'].'</b>:'.
			'<span>�����: <b>'.$summa.'</b> ���.</span>'.
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

function _salaryZayavCheck($zayav_id) {//��������, ���� ������ �������� ���������, ��������� �/� ���������� �� ����������� ������, ���� ����� ����
	if(!$zayav_id)
		return;

	if(!$z = _zayavQuery($zayav_id))
		return;

	$sql = "SELECT *
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `zayav_id`=".$zayav_id."
			  AND `worker_id`
			  AND !`salary_list_id`
			  AND (!`year` OR !`mon`)";
	if(!$spisok = query_arr($sql))
		return;

	$dolg = $z['sum_accrual'] - $z['sum_pay'] > 0;

	//_viewerRule($worker_id, 'RULE_SALARY_ZAYAV_ON_PAY')

	foreach($spisok as $r) {
		if(!$dolg) {
			$sql = "UPDATE `_zayav_expense`
					SET `year`=".strftime('%Y').",
						`mon`=".strftime('%m')."
					WHERE `id`=".$r['id'];
			query($sql);
			_balans(array(
				'action_id' => 19,
				'worker_id' => $r['worker_id'],
				'zayav_id' => $r['zayav_id'],
				'sum' => $r['sum'],
				'about' => '������� ���� �� ������.'
			));
		}
	}
}
function _salaryZayavBonus($zayav_id) {//���������� ������ ����������
	if(!$z = _zayavQuery($zayav_id))
		return;

	//�� ������ ���� ����
	define('BZDOLG', round($z['sum_dolg'], 1));

	//������� ������
	define('PROFIT', _cena($z['sum_profit']));

	$sql = "SELECT *
			FROM `_vkuser_rule`
			WHERE `app_id`=".APP_ID."
			  AND `key`='RULE_SALARY_BONUS'
			  AND `value`";
	if(!$spisok = query_arr($sql))
		return;

	foreach($spisok as $r) {
		if(!$procent = _viewer($r['viewer_id'], 'bonus_sum'))
			continue;

		$sql = "SELECT *
				FROM `_salary_bonus`
				WHERE `app_id`=".APP_ID."
				  AND `zayav_id`=".$zayav_id."
				  AND `worker_id`=".$r['viewer_id'];
		$bonus = query_assoc($sql);

		//�������� ������, ���� ���� ���� �� ������ ��� ��� �������
		if($bonus && (BZDOLG || !PROFIT)) {
			$sql = "DELETE FROM `_salary_bonus` WHERE `id`=".$bonus['id'];
			query($sql);

			_balans(array(
				'action_id' => 47,
				'worker_id' => $r['viewer_id'],
				'zayav_id' => $zayav_id,
				'sum' => $bonus['sum']
			));

			$reason = '��� ������� �� ������';
			if(BZDOLG)
				$reason = '������ �������� �� ���������';

			_history(array(
				'type_id' => 93,
				'client_id' => $z['client_id'],
				'zayav_id' => $zayav_id,
				'worker_id' => $r['viewer_id'],
				'v1' => _cena($bonus['sum']),
				'v2' => $reason
			));

			continue;
		}

		//���� ���� ���� ��� ��� ������� - ������ �� ��������
		if(BZDOLG || !PROFIT)
			continue;

		//���� ����� �������� � ����� ������ - ������ �� ��������
		if($bonus && $bonus['salary_list_id'])
			continue;

		$sum = round(PROFIT * $procent / 100, 2);

		if($bonus && $bonus['sum'] != $sum) {
			$sql = "UPDATE `_salary_bonus`
					SET `zayav_profit`=".PROFIT.",
						`procent`=".$procent.",
						`sum`=".$sum."
					WHERE `id`=".$bonus['id'];
			query($sql);

			_balans(array(
				'action_id' => 46,
				'worker_id' => $r['viewer_id'],
				'zayav_id' => $zayav_id,
				'sum_old' => $bonus['sum'],
				'sum' => $sum
			));

			$reason = '��������� ������ �� ������';
			if($bonus['procent'] != $procent)
				$reason = '��������� ������� ������';

			_history(array(
				'type_id' => 92,
				'client_id' => $z['client_id'],
				'zayav_id' => $zayav_id,
				'worker_id' => $r['viewer_id'],
				'v1' => '<table>'._historyChange('����� ������', _cena($bonus['sum']), $sum).'</table>',
				'v2' => $reason
			));

			continue;
		}

		if($bonus)
			continue;

		$sql = "INSERT INTO `_salary_bonus` (
					`app_id`,
					`procent`,
					`zayav_id`,
					`zayav_profit`,
					`worker_id`,
					`sum`,
					`mon`,
					`year`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$procent.",
					".$zayav_id.",
					".PROFIT.",
					".$r['viewer_id'].",
					".$sum.",
					".intval(strftime('%m')).",
					".strftime('%Y').",
					".VIEWER_ID."
				)";
		query($sql);

		_balans(array(
			'action_id' => 45,
			'worker_id' => $r['viewer_id'],
			'zayav_id' => $zayav_id,
			'sum' => $sum,
			'about' => $procent.'%'
		));

		_history(array(
			'type_id' => 91,
			'client_id' => $z['client_id'],
			'zayav_id' => $zayav_id,
			'worker_id' => $r['viewer_id'],
			'v1' => $sum,
			'v2' => $procent
		));
	}
}

