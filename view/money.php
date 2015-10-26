<?php
function _money() {
	$d = empty($_GET['d']) ? 'income' : $_GET['d'];
	switch($d) {
		default:
			$d = 'income';
			switch(@$_GET['d1']) {
				case 'income':
				default:
					if(!_calendarDataCheck(@$_GET['day']))
						$_GET['day'] = _calendarWeek();
					$content = income_day($_GET['day']);
					break;
				case 'all': $left = income_all(); break;
				case 'year':
					if(empty($_GET['year']) || !preg_match(REGEXP_YEAR, $_GET['year'])) {
						$left = 'Указан некорректный год.';
						break;
					}
					$left = income_year(intval($_GET['year']));
					break;
				case 'month':
					if(empty($_GET['mon']) || !preg_match(REGEXP_YEARMONTH, $_GET['mon'])) {
						$left = 'Указан некорректный месяц.';
						break;
					}
					$left = income_month($_GET['mon']);
					break;
			}
			break;
		case 'expense': $content = expense(); break;
		case 'schet': $content = report_schet(); break;
		case 'invoice': $content = invoice(); break;
	}
	return
		'<div id="dopLinks">'.
			'<a class="link'.($d == 'income' ? ' sel' : '').'" href="'.URL.'&p=money&d=income">Платежи</a>'.
			'<a class="link'.($d == 'expense' ? ' sel' : '').'" href="'.URL.'&p=money&d=expense">Расходы</a>'.
			'<a class="link'.($d == 'refund' ? ' sel' : '').'" href="'.URL.'&p=money&d=refund">Возвраты</a>'.
			'<a class="link'.($d == 'schet' ? ' sel' : '').'" href="'.URL.'&p=money&d=schet">Счета на оплату</a>'.
			'<a class="link'.($d == 'invoice' ? ' sel' : '').'" href="'.URL.'&p=money&d=invoice">Расчётные счета</a>'.
		'</div>'.
		$content;
}//_money()


function income_top($sel) { //Условия поиска сверху для платежей
	$sql = "SELECT DISTINCT `viewer_id_add`
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID;
	$worker = query_workerSelJson($sql, GLOBAL_MYSQL_CONNECT);

	return
		'<table id="top">'.
			'<tr><td id="mi-l">'.
					'<div class="f-label">Счета</div>'.
					'<input type="hidden" id="invoice_id" />'.
				'<td id="mi-center">'.
					'<div class="f-label">Вносил сотрудник</div>'.
					'<input type="hidden" id="worker_id" />'.
					'<div class="f-label">Дополнительно</div>'.
					_check('prepay', 'предоплата', 0, 1).
					_check('deleted', '+ удалённые платежи', 0, 1).
					_check('deleted_only', 'показать только удалённые', 0, 1).
				'<td id="mi-calendar">'.
					_calendarFilter(array(
						'days' => income_days(),
						'func' => 'income_days',
						'sel' => $sel
					)).
		'</table>'.
		'<script type="text/javascript">'.
			'var INCOME_WORKER='.$worker.';'.
			'incomeLoad();'.
		'</script>';
//		(VIEWER_ADMIN ? _check('del', 'Удалённые платежи') : '');
}//income_top()
function income_days($mon=0) {//отметка дней в календаре, в которые вносились платежи
	$sql = "SELECT DATE_FORMAT(`dtime_add`,'%Y-%m-%d') AS `day`
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `dtime_add` LIKE ('".($mon ? $mon : strftime('%Y-%m'))."%')
			GROUP BY DATE_FORMAT(`dtime_add`,'%d')";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$days = array();
	while($r = mysql_fetch_assoc($q))
		$days[$r['day']] = 1;
	return $days;
}//income_days()
function income_path($data) {//путь с датой
	$ex = explode(':', $data);
	$d = explode('-', $ex[0]);
	define('YEAR', $d[0]);
	define('MON', @$d[1]);
	define('DAY', @$d[2]);
	$to = '';
	if(!empty($ex[1])) {
		$d = explode('-', $ex[1]);
		$to = ' - '.intval($d[2]).
			($d[1] != MON ? ' '._monthFull($d[1]) : '').
			($d[0] != YEAR ? ' '.$d[0] : '');
	}
	return
		'<a href="'.URL.'&p=report&d=money&d1=income&d2=all">Год</a> » '.(YEAR ? '' : '<b>За всё время</b>').
		(MON ? '<a href="'.URL.'&p=report&d=money&d1=income&d2=year&year='.YEAR.'">'.YEAR.'</a> » ' : '<b>'.YEAR.'</b>').
		(DAY ? '<a href="'.URL.'&p=report&d=money&d1=income&d2=month&mon='.YEAR.'-'.MON.'">'._monthDef(MON, 1).'</a> » ' : (MON ? '<b>'._monthDef(MON, 1).'</b>' : '')).
		(DAY ? '<b>'.intval(DAY).$to.'</b>' : '').
		'<a class="income-add add">Внести платёж</a>';
}//income_path()
function income_day($day) {
	$data = income_spisok(array('day'=>$day));
	return
		'<div id="money-income">'.
			income_top($day).
			'<div id="path">'.income_path($day).'</div>'.
			'<div id="spisok">'.$data['spisok'].'</div>'.
		'</div>';
}//income_day()
function incomeFilter($v) {
	$send = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 50,
		'invoice_id' => _num(@$v['invoice_id']),
		'client_id' => _num(@$v['client_id']),
		'zayav_id' => _num(@$v['zayav_id']),
		'worker_id' => _num(@$v['worker_id']),
		'prepay' => _bool(@$v['prepay']),
		'deleted' => _bool(@$v['deleted']),
		'deleted_only' => _bool(@$v['deleted_only'])
	);
	$send = _calendarPeriod(@$v['day']) + $send;
	return $send;
}//incomeFilter()
function income_spisok($filter=array()) {
	$filter = incomeFilter($filter);

	define('PAGE1', $filter['page'] == 1);
	$js = !PAGE1 ? '' :
		'<script type="text/javascript">'.
			'var MONEY={'.
				'limit:'.$filter['limit'].','.
				'invoice_id:'.$filter['invoice_id'].','.
				'client_id:'.$filter['client_id'].','.
				'zayav_id:'.$filter['zayav_id'].','.
				'worker_id:'.$filter['worker_id'].','.
				'day:"'.$filter['period'].'",'.
				'prepay:'.$filter['prepay'].','.
				'deleted:'.$filter['deleted'].','.
				'deleted_only:'.$filter['deleted_only'].
			'};'.
		'</script>';

	$cond = "`app_id`=".APP_ID." AND `ws_id`=".WS_ID;

	if($filter['invoice_id'])
		$cond .= " AND `invoice_id`=".$filter['invoice_id'];
	if($filter['worker_id'])
		$cond .= " AND `viewer_id_add`=".$filter['worker_id'];
	if($filter['client_id'])
		$cond .= " AND `client_id`=".$filter['client_id'];
	if($filter['zayav_id'])
		$cond .= " AND `zayav_id`=".$filter['zayav_id'];
	if($filter['prepay'])
		$cond .= " AND `prepay`";
	if(!$filter['deleted'])
		$cond .= " AND !`deleted`";
	elseif($filter['deleted_only'])
		$cond .= " AND `deleted`";
	if($filter['day'])
		$cond .= " AND `dtime_add` LIKE '".$filter['day']."%'";
	if($filter['from'])
		$cond .= " AND `dtime_add`>='".$filter['from']." 00:00:00' AND `dtime_add`<='".$filter['to']." 23:59:59'";

	$sql = "SELECT
				COUNT(`id`) AS `all`,
				SUM(`sum`) AS `sum`
			FROM `_money_income`
			WHERE ".$cond;
	$send = query_assoc($sql, GLOBAL_MYSQL_CONNECT);

	$send['filter'] = $filter;
	if(!$send['all'])
		return $send + array('spisok' => $js.'<div class="_empty">Платежей нет.</div>');

	$all = $send['all'];

	$sql = "SELECT *
			FROM `_money_income`
			WHERE ".$cond."
			ORDER BY `dtime_add` DESC
			LIMIT "._start($filter).",".$filter['limit'];
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$money = array();
	while($r = mysql_fetch_assoc($q))
		$money[$r['id']] = $r;

	//$money = _viewer($money);
	$money = _clientValToList($money);
	if(function_exists('_zayavValToList'))
		$money = _zayavValToList($money);
//	$money = _zpLink($money);
//	$money = _schetValues($money);

	$send['spisok'] = !PAGE1 ? '' :
		$js.
		'<div id="summa">'.
			'Показан'._end($all, '', 'о').' <b>'.$all.'</b> платеж'._end($all, '', 'а', 'ей').
			' на сумму <b>'._sumSpace($send['sum']).'</b> руб.'.
		'</div>'.
		'<table class="_spisok">'.
			'<tr><th>Сумма'.
				'<th>Описание'.
				'<th>Дата'.
				'<th>';

	foreach($money as $r)
		$send['spisok'] .= income_unit($r, $filter);

	$send['spisok'] .= _next($filter + array(
			'type' => 3,
			'all' => $all,
			'tr' => 1,
			'id' => 'income-next'
		));

	return $send;
}//income_spisok()
function income_unit($r) {
	$about = '';
	if($r['zayav_id'])
		$about = 'Заявка '.@$r['zayav_link'];
	if($r['zp_id'])
		$about = 'Продажа запчасти '.$r['zp_link'];
	if($r['schet_id'])
		$about .= '<br />Счёт № СЦ'.$r['schet_nomer'].'. День оплаты: '.FullData($r['schet_paid_day'], 1);

	$about .=
		($about ? '. ' : '').$r['about'].
		($r['client_id'] ? '<div class="income-client">Клиент: '.$r['client_link'].'</div>' : '');

	return
		'<tr'.($r['deleted'] ? ' class="deleted"' : '').'>'.
			'<td class="sum">'._sumSpace($r['sum']).
			'<td><span class="type">'._invoice($r['invoice_id']).':</span> '.$about.
			'<td class="dtime">'.
				'<div class="'._tooltip(_viewerAdded($r['viewer_id_add']), -40).FullDataTime($r['dtime_add']).'</div>'.
				($r['viewer_id_del'] ?
					'<div class="ddel '._tooltip(_viewerDeleted($r['viewer_id_del']), -40).
						FullDataTime($r['dtime_del']).
					'</div>'
				: '').
			'<td class="ed">'.
			(TODAY == substr($r['dtime_add'], 0, 10) ?
				'<div val="'.$r['id'].'" class="img_del income-del'._tooltip('Удалить платёж', -95, 'r').'</div>'
			: '');
}//income_unit()


function _expense($id=0, $i='name') {//Список категорий расходов
		$key = CACHE_PREFIX.'expense'.WS_ID;
		$arr = xcache_get($key);
		if(empty($arr)) {
			$sql = "SELECT * FROM `_money_expense_category` ORDER BY `sort`";
			$q = query($sql, GLOBAL_MYSQL_CONNECT);
			while($r = mysql_fetch_assoc($q))
				$arr[$r['id']] = $r;
			xcache_set($key, $arr, 86400);
		}

	//все категории
	if(!$id)
		return $arr;

	//некорректный id категории
	if(!_num($id))
		die('Error: expense category_id <b>'.$id.'</b> not correct');

	//неизвестный id категории
	if(!isset($arr[$id]))
		die('Error: no expense category_id <b>'.$id.'</b> in _invoice');

	//массив всех категорий
	if($i == 'all')
		return $arr[$id];

	//неизвестный ключ категории
	if(!isset($arr[$id][$i]))
		return '<span class="red">неизвестный ключ категории расхода: <b>'.$i.'</b></span>';

	//возврат данных конкретной категории расхода
	return $arr[$id][$i];
}//_expense()
function expense() {
	$data = expense_spisok();
	return
		'<table class="tabLR" id="money-expense">'.
			'<tr><td class="left">'.
					'<div class="headName">Список расходов организации<a class="add">Внести новый расход</a></div>'.
					'<div id="spisok">'.$data['spisok'].'</div>'.
				'<td class="right">'.expense_right().
		'</table>';
}//expense()
function expenseFilter($v) {
	$send = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 50,
		'invoice_id' => _num(@$v['invoice_id']),
		'category_id' => _num(@$v['category_id']),
		'worker_id' => _num(@$v['worker_id']),
		'year' => _year(@$v['year']),
		'month' => _ids(@$v['month']) //список выбранных месяцев
		//'del' => isset($v['del']) && preg_match(REGEXP_BOOL, $v['del']) ? $v['del'] : 0
	);
	return $send;
}//expenseFilter()
function expense_right() {
	$sql_worker = "SELECT DISTINCT `worker_id`
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker_id`";

	$year = array();
	for($n = 2014; $n <= strftime('%Y'); $n++)
		$year[$n] = $n;

	return
		'<div class="f-label">Счёт</div>'.
		'<input type="hidden" id="invoice_id">'.

		'<div class="findHead">Категория</div>'.
		'<input type="hidden" id="category_id">'.

		'<div class="findHead">Сотрудник</div>'.
		'<input type="hidden" id="worker_id">'.


		'<input type="hidden" id="year">'.
		'<div id="mon-list">'.expenseMonthSum().'</div>'.
		'<script type="text/javascript">'.
			'var EXPENSE_WORKER='.query_workerSelJson($sql_worker, GLOBAL_MYSQL_CONNECT).','.
				'YEAR_SPISOK='._selJson($year).';'.
			'expenseLoad();'.
		'</script>';
}//expense_right()
function expenseMonthSum($v=array()) {//список чекбоксов с месяцами и суммами расходов по каждому месяцу
	$filter = expenseFilter($v);

	$sql = "SELECT
				DISTINCT(DATE_FORMAT(`dtime_add`,'%m')) AS `month`,
				SUM(`sum`) AS `sum`
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `dtime_add` LIKE '".$filter['year']."-%'".
		($filter['invoice_id'] ? " AND `invoice_id`=".$filter['invoice_id'] : '').
		($filter['category_id'] ? " AND `category_id`=".$filter['category_id'] : '').
		($filter['worker_id'] ? " AND `worker_id`=".$filter['worker_id'] : '')."
			GROUP BY DATE_FORMAT(`dtime_add`,'%m')
			ORDER BY `dtime_add` ASC";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$res = array();
	while($r = mysql_fetch_assoc($q))
		$res[intval($r['month'])] = abs($r['sum']);
	$send = '';
	for($n = 1; $n <= 12; $n++)
		$send .= _check(
			'c'.$n,
			_monthDef($n).(isset($res[$n]) ? '<span class="sum">'._sumSpace($res[$n]).'</span>' : ''),
			isset($filter['month'][$n]),
			1
		);
	return $send;
}//expenseMonthSum()
function expense_spisok($v=array()) {
	$filter = expenseFilter($v);
/*
	$dtime = array();
	foreach($filter['month'] as $mon => $k)
		$dtime[] = "`dtime_add` LIKE '".$filter['year']."-".($mon < 10 ? 0 : '').$mon."%'";
*/

	define('PAGE1', $filter['page'] == 1);
	$js = !PAGE1 ? '' :
		'<script type="text/javascript">'.
			'var EXPENSE={'.
				'limit:'.$filter['limit'].','.
				'invoice_id:'.$filter['invoice_id'].','.
				'worker_id:'.$filter['worker_id'].','.
				'category_id:'.$filter['category_id'].
			'};'.
		'</script>';

	$cond = "`app_id`=".APP_ID." AND `ws_id`=".WS_ID." AND !`deleted`";

	if($filter['invoice_id'])
		$cond .= " AND `invoice_id`=".$filter['invoice_id'];
	if($filter['worker_id'])
		$cond .= " AND `worker_id`=".$filter['worker_id'];
	if($filter['category_id'])
		$cond .= " AND `category_id`=".$filter['category_id'];

	$sql = "SELECT
				COUNT(`id`) AS `all`,
				SUM(`sum`) AS `sum`
			FROM `_money_expense`
			WHERE ".$cond;
	$send = query_assoc($sql, GLOBAL_MYSQL_CONNECT);

	$send['filter'] = $filter;
	if(!$send['all'])
		return $send + array('spisok' => $js.'<div class="_empty">Платежей нет.</div>');

	$all = $send['all'];

	$sql = "SELECT *
			FROM `_money_expense`
			WHERE ".$cond."
			ORDER BY `dtime_add` DESC
			LIMIT "._start($filter).",".$filter['limit'];
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$expense = array();
	while($r = mysql_fetch_assoc($q))
		$expense[$r['id']] = $r;

	$expense = _viewer($expense);

	$send['spisok'] = !PAGE1 ? '' :
		$js.
		'<div id="summa">'.
			'Показан'._end($all, 'а', 'о').' <b>'.$all.'</b> запис'._end($all, 'ь', 'и', 'ей').
			' на сумму <b>'._sumSpace($send['sum']).'</b> руб.'.
			(empty($dtime) ? ' за всё время.' : '').
		'</div>'.
		'<table class="_spisok">'.
			'<tr><th>Сумма'.
				'<th>Описание'.
				'<th>Дата'.
				'<th>';

	foreach($expense as $r)
		$send['spisok'] .=
			'<tr'.($r['deleted'] ? ' class="deleted"' : '').'>'.
				'<td class="sum"><b>'.abs($r['sum']).'</b>'.
				'<td>'.($r['category_id'] ? '<span class="type">'._expense($r['category_id']).
							($r['about'] || $r['worker_id'] ? ':' : '').'</span> '
					   : '').
					   ($r['worker_id'] ?
						   _viewer($r['worker_id'], 'viewer_link_zp').
							($r['about'] ? ', ' : '')
					   : '').
					   $r['about'].
				'<td class="dtime">'.
					'<div class="'._tooltip(_viewerAdded($r['viewer_id_add']), -40).FullDataTime($r['dtime_add']).'</div>'.
		($r['viewer_id_del'] ?
					'<div class="ddel '._tooltip(_viewerDeleted($r['viewer_id_del']), -40).
						FullDataTime($r['dtime_del']).
					'</div>'
		: '').
				'<td class="ed">'.
					'<div val="'.$r['id'].'" class="img_edit'._tooltip('Изменить', -32).'</div>'.
		(TODAY == substr($r['dtime_add'], 0, 10) ?
					'<div val="'.$r['id'].'" class="img_del'._tooltip('Удалить', -29).'</div>'
		: '');


	$send['spisok'] .= _next($filter + array(
			'all' => $all,
			'tr' => 1
		));

	return $send;
}//expense_spisok()

/*

function income_all() {//Суммы платежей по годам
	$sql = "SELECT DATE_FORMAT(`dtime_add`,'%Y') AS `year`,
				   SUM(`sum`) AS `sum`
			FROM `money`
			WHERE `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `sum`<0
			GROUP BY DATE_FORMAT(`dtime_add`,'%Y')
			ORDER BY `dtime_add` ASC";
	$q = query($sql);
	$expense = array();
	while($r = mysql_fetch_assoc($q))
		$expense[$r['year']] = round(abs($r['sum']), 2);

	$sql = "SELECT DATE_FORMAT(`dtime_add`,'%Y') AS `year`,
				   SUM(`sum`) AS `sum`
			FROM `money`
			WHERE `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `sum`>0
			GROUP BY DATE_FORMAT(`dtime_add`,'%Y')
			ORDER BY `dtime_add` ASC";
	$q = query($sql);
	$spisok = array();
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['year']] = '<tr>'.
			'<td><a href="'.URL.'&p=report&d=money&d1=income&d2=year&year='.$r['year'].'">'.$r['year'].'</a>'.
			'<td class="r"><b>'._sumSpace($r['sum']).'</b>'.
			'<td class="r">'.(isset($expense[$r['year']]) ? _sumSpace($expense[$r['year']]) : '').
			'<td class="r">'.(isset($expense[$r['year']]) ? _sumSpace($r['sum'] - $expense[$r['year']]) : '');

	return
		'<div class="headName">Суммы платежей по годам</div>'.
		'<table class="_spisok">'.
		'<tr><th>Год'.
		'<th>Платежи'.
		'<th>Расход'.
		'<th>Чистый доход'.
		implode('', $spisok).
		'</table>';
}//income_all()



function income_year($year) {//Суммы платежей по месяцам
	$sql = "SELECT DATE_FORMAT(`dtime_add`,'%m') AS `mon`,
				   SUM(`sum`) AS `sum`
			FROM `money`
			WHERE `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `sum`<0
			  AND `dtime_add` LIKE '".$year."%'
			GROUP BY DATE_FORMAT(`dtime_add`,'%m')
			ORDER BY `dtime_add` ASC";
	$q = query($sql);
	$expense = array();
	while($r = mysql_fetch_assoc($q))
		$expense[$r['mon']] = round(abs($r['sum']), 2);


	$spisok = array();
	for($n = 1; $n <= (strftime('%Y', time()) == $year ? intval(strftime('%m', time())) : 12); $n++)
		$spisok[$n] =
			'<tr><td class="r grey">'._monthDef($n, 1).
			'<td class="r">';
	$sql = "SELECT DATE_FORMAT(`dtime_add`,'%m') AS `mon`,
				   SUM(`sum`) AS `sum`
			FROM `money`
			WHERE `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `sum`>0
			  AND `dtime_add` LIKE '".$year."%'
			GROUP BY DATE_FORMAT(`dtime_add`,'%m')
			ORDER BY `dtime_add` ASC";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[intval($r['mon'])] =
			'<tr><td class="r"><a href="'.URL.'&p=report&d=money&d1=income&d2=month&mon='.$year.'-'.$r['mon'].'">'._monthDef($r['mon'], 1).'</a>'.
			'<td class="r"><b>'._sumSpace($r['sum']).'</b>'.
			'<td class="r">'.(isset($expense[$r['mon']]) ? _sumSpace($expense[$r['mon']]) : '').
			'<td class="r">'.(isset($expense[$r['mon']]) ? _sumSpace($r['sum'] - $expense[$r['mon']]) : '');

	return
		'<div class="headName">Суммы платежей по месяцам за '.$year.' год</div>'.
		'<div class="inc-path">'.income_path($year).'</div>'.
		'<table class="_spisok">'.
		'<tr><th>Месяц'.
		'<th>Платежи'.
		'<th>Расход'.
		'<th>Чистый доход'.
		implode('', $spisok).
		'</table>';
}//income_year()



function income_month($mon) {
	$path = income_path($mon);
	$spisok = array();
	for($n = 1; $n <= (strftime('%Y', time()) == YEAR ? intval(strftime('%d', time())) : date('t', strtotime($mon.'-01'))); $n++)
		$spisok[$n] =
			'<tr><td class="r grey">'.$n.'.'.MON.'.'.YEAR.
			'<td class="r">';
	$sql = "SELECT DATE_FORMAT(`dtime_add`,'%d') AS `day`,
				   SUM(`sum`) AS `sum`
			FROM `money`
			WHERE `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `sum`>0
			  AND `dtime_add` LIKE '".$mon."%'
			GROUP BY DATE_FORMAT(`dtime_add`,'%d')
			ORDER BY `dtime_add` ASC";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[intval($r['day'])] =
			'<tr><td class="r"><a href="'.URL.'&p=report&d=money&d1=income&day='.$mon.'-'.$r['day'].'">'.intval($r['day']).'.'.MON.'.'.YEAR.'</a>'.
			'<td class="r"><b>'._sumSpace($r['sum']).'</b>';

	return
		'<div class="headName">Суммы платежей по дням за '._monthDef(MON, 1).' '.YEAR.'</div>'.
		'<div class="inc-path">'.$path.'</div>'.
		'<table class="_spisok sums">'.
		'<tr><th>Месяц'.
		'<th>Всего'.
		implode('', $spisok).
		'</table>';
}//income_month()





function income_insert($v) {
	$v = array(
		'client_id' => _num(@$v['client_id']),
		'zayav_id' => _num(@$v['zayav_id']),
		'zp_id' => _num(@$v['zp_id']),
		'schet_id' => _num(@$v['schet_id']),
		'schet_paid_day' => preg_match(REGEXP_DATE, @$v['schet_paid_day']) ? $v['schet_paid_day'] : '0000-00-00',
		'invoice_id' => _num($v['invoice_id']),
		'sum' => _cena($v['sum']),
		'prepay' => _bool(@$v['prepay']),
		'prim' => _txt(@$v['prim'])
	);

	if($v['zayav_id']) {
		$sql = "SELECT * FROM `zayav` WHERE `ws_id`=".WS_ID." AND !`deleted` AND `id`=".$v['zayav_id'];
		if(!$r = mysql_fetch_assoc(query($sql)))
			return false;
		$v['client_id'] = $r['client_id'];
	}

	$sql = "INSERT INTO `money` (
				`ws_id`,
				`client_id`,
				`zayav_id`,
				`zp_id`,
				`schet_id`,
				`schet_paid_day`,
				`invoice_id`,
				`sum`,
				`prepay`,
				`prim`,
				`viewer_id_add`
			) VALUES (
				".WS_ID.",
				".$v['client_id'].",
				".$v['zayav_id'].",
				".$v['zp_id'].",
				".$v['schet_id'].",
				'".$v['schet_paid_day']."',
				".$v['invoice_id'].",
				".$v['sum'].",
				".$v['prepay'].",
				'".addslashes($v['prim'])."',
				".VIEWER_ID."
			)";
	query($sql);

	invoice_history_insert(array(
		'action' => 1,
		'table' => 'money',
		'id' => mysql_insert_id()
	));
	clientBalansUpdate($v['client_id']);
	zayavBalansUpdate($v['zayav_id']);

	history_insert(array(
		'type' => 6,
		'client_id' => $v['client_id'],
		'zayav_id' => $v['zayav_id'],
		'zp_id' => $v['zp_id'],
		'value' => $v['sum'],
		'value1' => $v['prim'],
		'value2' => $v['invoice_id']
	));

	return $v;
}//income_insert()






function reportSchetFilter($v) {
	$send = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 50,
		'find' => trim(@$v['find']),
		'client_id' => _num(@$v['client_id']),
		'passpaid' => _num(@$v['passpaid'])
	);
	return $send;
}//reportSchetFilter()
function report_schet_right() {
	return
		'<div id="find"></div>'.
		'<div class="findHead">Счета:</div>'.
		_radio('passpaid',
			array(
				0 => 'ВСЕ',
				1 => 'Не переданы',
				2 => 'Переданы, не опл.',
				3 => 'Оплачены'
			), 0, 1);
}//report_schet_right()
function report_schet() {
	$data = report_schet_spisok();
	return
		'<div class="headName">Список счетов на оплату</div>'.
		'<div id="spisok">'.$data['spisok'].'</div>';
}//report_schet()
function report_schet_spisok($v=array()) {
	$filter = reportSchetFilter($v);
	$cond = "`ws_id`=".WS_ID;

	if($filter['find'])
		$cond .= " AND `nomer`="._num($filter['find']);
	else {
		if($filter['client_id'])
			$cond .= " AND `client_id`=" . $filter['client_id'];
		switch ($filter['passpaid']) {
			case 1:
				$cond .= " AND !`pass`";
				break;
			case 2:
				$cond .= " AND `pass` AND `paid_sum`<`sum`";
				break;
			case 3:
				$cond .= " AND `paid_sum`>=`sum`";
				break;
		}
	}
	$sql = "SELECT
				COUNT(`id`) AS `all`,
				SUM(`sum`) AS `sum`
			FROM `zayav_schet`
			WHERE ".$cond;
	$send = mysql_fetch_assoc(query($sql));
	$send['filter'] = $filter;
	if(!$send['all'])
		return $send + array('spisok' => '<div class="_empty">Счетов нет.</div>');

	$all = $send['all'];
	$filter['all'] = $all;

	$send['spisok'] =
		($filter['page'] == 1 ?
			'<div id="result">'.
			'Показан'._end($all, '', 'о').' <b>'.$all.'</b> сч'._end($all, 'ёт', 'ёта', 'етов').
			' на сумму <b>'.$send['sum'].'</b> руб.'.
			'</div>'.
			'<table class="_spisok _money">'
			: '');

	$sql = "SELECT *
			FROM `zayav_schet`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT "._start($filter).",".$filter['limit'];
	$q = query($sql);

	$spisok = array();
	while($r = mysql_fetch_assoc($q)) {
		$r['paids'] = array();
		$spisok[$r['id']] = $r;
	}

	$spisok = _zayavValues($spisok);


	//список платежей по счетам
	$sql = "SELECT * FROM `money` WHERE `schet_id` IN (".implode(',', array_keys($spisok)).")";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['schet_id']]['paids'][] = array(
			'sum' => $r['sum'],
			'day' => $r['schet_paid_day']
		);

	foreach($spisok as $r)
		$send['spisok'] .= schet_unit($r);


	$send['spisok'] .=
		_next(array(
				'tr' => 1,
				'type' => 4,
				'id' => 'schet_next'
			) + $filter);

	return $send;
}//report_schet_spisok()



function transfer_spisok($v=array()) {
	if(!RULES_HISTORYTRANSFER)
		return  '';
	$v = array(
		//	'page' => !empty($v['page']) && preg_match(REGEXP_NUMERIC, $v['page']) ? $v['page'] : 1,
		//	'limit' => !empty($v['limit']) && preg_match(REGEXP_NUMERIC, $v['limit']) ? $v['limit'] : 15,
	);
	$sql = "SELECT *
	        FROM `invoice_transfer`
	        WHERE `ws_id`=".WS_ID."
	        ORDER BY `id` DESC";
	$q = query($sql);
	$send = '<table class="_spisok _money">'.
		'<tr><th>Cумма'.
		'<th>Со счёта'.
		'<th>На счёт'.
		'<th>Подробно'.
		'<th>Дата';
	while($r = mysql_fetch_assoc($q))
		$send .=
			'<tr><td class="sum">'._sumSpace($r['sum']).
			'<td>'.($r['invoice_from'] ? '<span class="type">'._invoice($r['invoice_from']).'</span>' : '').
			($r['worker_from'] && $r['invoice_from'] ? '<br />' : '').
			($r['worker_from'] ? _viewer($r['worker_from'], 'name') : '').
			'<td>'.($r['invoice_to'] ? '<span class="type">'._invoice($r['invoice_to']).'</span>' : '').
			($r['worker_to'] && $r['invoice_to'] ? '<br />' : '').
			($r['worker_to'] ? _viewer($r['worker_to'], 'name') : '').
			'<td class="about">'.$r['about'].
			'<td class="dtime">'.FullDataTime($r['dtime_add'], 1);
	$send .= '</table>';
	return $send;
}//transfer_spisok()
function invoice_history($v) {
	$v = array(
		'page' => !empty($v['page']) && preg_match(REGEXP_NUMERIC, $v['page']) ? $v['page'] : 1,
		'limit' => !empty($v['limit']) && preg_match(REGEXP_NUMERIC, $v['limit']) ? $v['limit'] : 15,
		'invoice_id' => intval($v['invoice_id'])
	);
	$send = $v['page'] == 1 ?
		'<div>Счёт <u>'._invoice($v['invoice_id']).'</u>:</div>'.
		'<input type="hidden" id="invoice_history_id" value="'.$v['invoice_id'].'" />'
		: '';

	$all = query_value("SELECT COUNT(*) FROM `invoice_history` WHERE `ws_id`=".WS_ID." AND `invoice_id`=".$v['invoice_id']);
	if(!$all)
		return $send.'<br />Истории нет.';

	$start = ($v['page'] - 1) * $v['limit'];
	$sql = "SELECT `h`.*,
				   IFNULL(`m`.`zayav_id`,0) AS `zayav_id`,
				   IFNULL(`m`.`zp_id`,0) AS `zp_id`,
				   IFNULL(`m`.`expense_id`,0) AS `expense_id`,
				   IFNULL(`m`.`worker_id`,0) AS `worker_id`,
				   IFNULL(`m`.`prim`,'') AS `prim`,
				   IFNULL(`i`.`invoice_from`,0) AS `invoice_from`,
				   IFNULL(`i`.`invoice_to`,0) AS `invoice_to`,
				   IFNULL(`i`.`worker_from`,0) AS `worker_from`,
				   IFNULL(`i`.`worker_to`,0) AS `worker_to`
			FROM `invoice_history` `h`
				LEFT JOIN `money` `m`
				ON `m`.`ws_id`=".WS_ID." AND `h`.`table`='money' AND `h`.`table_id`=`m`.`id`
				LEFT JOIN `invoice_transfer` `i`
				ON `i`.`ws_id`=".WS_ID." AND `h`.`table`='invoice_transfer' AND `h`.`table_id`=`i`.`id`
			WHERE `h`.`ws_id`=".WS_ID."
			  AND `h`.`invoice_id`=".$v['invoice_id']."
			ORDER BY `h`.`id` DESC
			LIMIT ".$start.",".$v['limit'];
	$q = query($sql);
	$history = array();
	while($r = mysql_fetch_assoc($q))
		$history[$r['id']] = $r;

	$history = _zayavNomerLink($history);
	$history = _zpLink($history);

	if($v['page'] == 1)
		$send .= '<table class="_spisok _money invoice-history">'.
			'<tr><th>Действие'.
			'<th>Сумма'.
			'<th>Баланс'.
			'<th>Описание'.
			'<th>Дата';
	foreach($history as $r) {
		$about = '';
		if($r['zayav_id'])
			$about = 'Заявка '.$r['zayav_link'].'. ';
		if($r['zp_id'])
			$about = 'Продажа запчасти '.$r['zp_link'].'. ';
		$about .= $r['prim'].' ';
		$worker = $r['worker_id'] ? '<u>'._viewer($r['worker_id'], 'name').'</u> ' : '';
		$expense = $r['expense_id'] ? '<span class="type">'._expense($r['expense_id']).(!trim($about) && !$worker ? '' : ': ').'</span> ' : '';
		if($r['invoice_from'] != $r['invoice_to']) {//Счета не равны, перевод внешний
			if(!$r['invoice_to'])//Деньги были переданы руководителю
				$about .= 'Передача сотруднику '._viewer($r['worker_to'], 'name');
			elseif(!$r['invoice_from'])//Деньги были получены от руководителя
				$about .= 'Получение от сотрудника '._viewer($r['worker_from'], 'name');
			elseif($r['invoice_id'] == $r['invoice_from'])//Просматриваемый счёт общий - оправитель
				$about .= 'Отправление на счёт <span class="type">'._invoice($r['invoice_to']).'</span>';
			elseif($r['invoice_id'] == $r['invoice_to'])//Просматриваемый счёт общий - получатель
				$about .= 'Поступление со счёта <span class="type">'._invoice($r['invoice_from']).'</span>';;
		} else {//Счета равны, перевод внутренний
			if($r['invoice_id'] == $r['worker_from'])//Просматриваемый счёт сотрудника - оправитель
				$about .= 'Отправление на счёт <span class="type">'._invoice($r['invoice_to']).'</span> '._viewer($r['worker_to'], 'name');
			if($r['invoice_id'] == $r['worker_to'])//Просматриваемый счёт сотрудника - получатель
				$about .= 'Поступление со счёта <span class="type">'._invoice($r['invoice_from']).'</span> '._viewer($r['worker_from'], 'name');
		}
		$send .=
			'<tr><td class="action">'.invoiceHistoryAction($r['action']).
			'<td class="sum">'.($r['sum'] != 0 ? _sumSpace($r['sum']) : '').
			'<td class="balans">'._sumSpace($r['balans']).
			'<td>'.$expense.$worker.$about.
			'<td class="dtime">'.FullDataTime($r['dtime_add']);
	}

	if($start + $v['limit'] < $all) {
		$c = $all - $start - $v['limit'];
		$c = $c > $v['limit'] ? $v['limit'] : $c;
		$send .=
			'<tr class="_next" val="'.($v['page'] + 1).'"><td colspan="5">'.
			'<span>Показать ещё '.$c.' запис'._end($c, 'ь', 'и', 'ей').'</span>';
	}
	if($v['page'] == 1)
		$send .= '</table>';
	return $send;
}//invoice_history()
function invoiceHistoryAction($id, $i='name') {//Варианты действий в истории счетов
	$action = array(
		1 => array(
			'name' => 'Внесение платежа',
			'znak' => ''
		),
		2 => array(
			'name' => 'Удаление платежа',
			'znak' => '-'
		),
		3 => array(
			'name' => 'Восстановление платежа',
			'znak' => ''
		),
		4 => array(
			'name' => 'Перевод между счетами',
			'znak' => ''
		),
		5 => array(
			'name' => 'Установка текущей суммы',
			'znak' => ''
		),
		6 => array(
			'name' => 'Внесение расхода',
			'znak' => '-'
		),
		7 => array(
			'name' => 'Удаление расхода',
			'znak' => ''
		),
		8 => array(
			'name' => 'Восстановление расхода',
			'znak' => '-'
		),
		9 => array(
			'name' => 'Редактирование расхода',
			'znak' => ''
		)
	);
	return $action[$id][$i];
}//invoiceHistoryAction()
function invoice_history_insert($v) {
	$v = array(
		'action' => $v['action'],
		'table' => empty($v['table']) ? '' : $v['table'],
		'id' => empty($v['id']) ? 0 : $v['id'],
		'sum' => empty($v['sum']) ? 0 : $v['sum'],
		'worker_id' => empty($v['worker_id']) ? 0 : $v['worker_id'],
		'invoice_id' => empty($v['invoice_id']) ? 0 : $v['invoice_id']
	);

	if($v['table']) {
		$r = query_assoc("SELECT * FROM `".$v['table']."` WHERE `id`=".$v['id']);
		$v['sum'] = abs($r['sum']);
		switch($v['table']) {
			case 'money':
				$v['invoice_id'] = $r['invoice_id'];
				$v['sum'] = invoiceHistoryAction($v['action'], 'znak').$v['sum'];
				break;
			case 'invoice_transfer':
				if(!$r['invoice_from'] && !$r['invoice_to'])
					return;
				if(!$r['invoice_from']) {//взятие средств у руководителя
					$v['invoice_id'] = $r['invoice_to'];
					if($r['worker_to'])
						invoice_history_insert_sql($r['worker_to'], $v);
					break;
				}
				if(!$r['invoice_to']) {//передача средств руководителю
					$v['invoice_id'] = $r['invoice_from'];
					$v['sum'] *= -1;
					if($r['worker_from'])
						invoice_history_insert_sql($r['worker_from'], $v);
					break;
				}
				//Передача из банка в наличные и на счета сотрудников
				$v['invoice_id'] = $r['invoice_from'];
				invoice_history_insert_sql($r['invoice_to'], $v);
				break;
		}
	}
	invoice_history_insert_sql($v['invoice_id'], $v);
}//invoice_history_insert()
function invoice_history_insert_sql($invoice_id, $v) {
	if(_invoice($invoice_id, 'start') == -1)
		return;
	$sql = "INSERT INTO `invoice_history` (
				`ws_id`,
				`action`,
				`table`,
				`table_id`,
				`invoice_id`,
				`sum`,
				`balans`,
				`viewer_id_add`
			) VALUES (
				".WS_ID.",
				'".$v['action']."',
				'".$v['table']."',
				".$v['id'].",
				".$invoice_id.",
				".$v['sum'].",
				"._invoiceBalans($invoice_id).",
				".VIEWER_ID."
			)";
	query($sql);
}
*/



function _invoice($id=0, $i='name') {//Список счетов
	$key = CACHE_PREFIX.'invoice'.WS_ID;
	$arr = xcache_get($key);
	if(empty($arr)) {
		$arr = array();
		$sql = "SELECT *
					FROM `_money_invoice`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					ORDER BY `id`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		while($r = mysql_fetch_assoc($q)) {
			$r['start'] = round($r['start'], 2);
			$arr[$r['id']] = $r;
		}
		xcache_set($key, $arr, 86400);
	}

	//все счета
	if(!$id)
		return $arr;

	//некорректный id счёта
	if(!_num($id))
		die('Error: incoice_id <b>'.$id.'</b> not correct');

	//неизвестный id счёта
	if(!isset($arr[$id]))
		die('Error: no invoice_id <b>'.$id.'</b> in _invoice');

	//возврат данных конкретного счёта
	if($i == 'all')
		return $arr[$id];

	//неизвестный ключ счёта
	if(!isset($arr[$id][$i]))
		return '<span class="red">неизвестный ключ счёта: <b>'.$i.'</b></span>';

	return $arr[$id][$i];
}//_invoice()
function _invoiceBalans($invoice_id, $start=false) {// Получение текущего баланса счёта
	if($start === false)
		$start = _invoice($invoice_id, 'start');

	//Платежи
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `invoice_id`=".$invoice_id;
	$income = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//Расходы
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `invoice_id`=".$invoice_id;
	$expense = query_value($sql, GLOBAL_MYSQL_CONNECT);

//	$from = query_value("SELECT IFNULL(SUM(`sum`),0) FROM `invoice_transfer` WHERE `ws_id`=".WS_ID." AND `invoice_from`=".$invoice_id);
//	$to = query_value("SELECT IFNULL(SUM(`sum`),0) FROM `invoice_transfer` WHERE `ws_id`=".WS_ID." AND `invoice_to`=".$invoice_id);

	return round($income - $expense - $start, 2);
}//_invoiceBalans()
function invoice() {
	return
		'<div id="money-invoice">'.
			'<div class="headName">'.
				'Расчётные счета'.
				'<a class="add" id="transfer">Перевод между счетами</a>'.
				'<span>::</span>'.
				'<a href="'.URL.'&p=setup&d=invoice" class="add">Управление счетами</a>'.
			'</div>'.
			'<div id="invoice-spisok">'.invoice_spisok().'</div>'.
//			(RULES_HISTORYTRANSFER ? '<div class="headName">История переводов</div>' : '').
//			'<div class="transfer-spisok">'.transfer_spisok().'</div>'.
		'</div>';
}//invoice()
function invoice_spisok() {
	$invoice = _invoice();
	if(empty($invoice))
		return 'Счета не определены.';

	$send = '<table class="_spisok">';
	foreach($invoice as $r)
		$send .=
			'<tr>'.
				'<td class="name">'.
					'<b>'.$r['name'].'</b>'.
					'<div class="about">'.$r['about'].'</div>'.
			($r['start'] != -1 ?
				'<td class="balans"><b>'._sumSpace(_invoiceBalans($r['id'])).'</b> руб.'.
				'<td><div val="'.$r['id'].'" class="img_note'._tooltip('Посмотреть историю операций', -95).'</div>'
			: '').
				//(VIEWER_ADMIN || $r['start'] != -1 ?
				'<td><a class="invoice-set" val="'.$r['id'].'">Установить<br />текущую<br />сумму</a>'.
			(VIEWER_ADMIN && $r['start'] != -1 ?
				'<td><a class="invoice-reset" val="'.$r['id'].'">Сбросить<br />сумму</a>'
			: '');
	$send .= '</table>';
	return $send;
}//invoice_spisok()


function _balans($v) {//внесение записи о балансе
	$unit_id = 0;
	$balans = 0;

	if(!empty($v['invoice_id'])) {
		$unit_id = _num($v['invoice_id']);
		$balans = _invoiceBalans($unit_id);
	}

	$sql = "INSERT INTO `_balans` (
				`app_id`,
				`ws_id`,

				`category_id`,
				`unit_id`,
				`action_id`,
				`sum`,
				`balans`,
				`about`,

				`viewer_id_add`
			) VALUES (
				".APP_ID.",
				".WS_ID.",

				"._num(@$v['category_id']).",
				".$unit_id.",
				"._num(@$v['action_id']).",
				"._cena(@$v['sum']).",
				".$balans.",

				'".addslashes(@$v['about'])."',

				".VIEWER_ID."
			)";
	query($sql, GLOBAL_MYSQL_CONNECT);
}//_balans()