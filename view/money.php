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
		case 'refund': $content = _refund(); break;
		case 'schet': $content = _schet(); break;
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

function _accrual_unit($r) {//строка начисления в таблице
	return
	'<tr><td class="sum '.$r['type']._tooltip('Начисление', -3)._sumSpace($r['sum']).
		'<td>'.$r['about'].
		'<td class="dtime">'._dtimeAdd($r).
		'<td class="ed">'.
			_iconDel(array(
				'id' => $r['id'],
				'class' => '_accrual-del'
			));
}//_accrual_unit()


function _refund() {
	$data = _refund_spisok();
	return
		'<table class="tabLR" id="money-refund">'.
			'<tr><td class="left">'.
					'<div class="headName">Возвраты</div>'.
					'<div id="spisok">'.$data['spisok'].'</div>'.
				'<td class="right">'._refund_right().
		'</table>';
}//_refund()
function _refundFilter($v) {
	$send = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 50,
		'invoice_id' => _num(@$v['invoice_id']),
		'client_id' => _num(@$v['client_id']),
		'zayav_id' => _num(@$v['zayav_id'])
	);
	return $send;
}//_refundFilter()
function _refund_spisok($filter=array()) {
	$filter = _refundFilter($filter);
	$filter = _filterJs('REFUND', $filter);

	$cond = "`app_id`=".APP_ID." AND `ws_id`=".WS_ID;

	if($filter['invoice_id'])
		$cond .= " AND `invoice_id`=".$filter['invoice_id'];
	if($filter['client_id'])
		$cond .= " AND `client_id`=".$filter['client_id'];
	if($filter['zayav_id'])
		$cond .= " AND `zayav_id`=".$filter['zayav_id'];

	$sql = "SELECT
				COUNT(`id`) AS `all`,
				SUM(`sum`) AS `sum`
			FROM `_money_refund`
			WHERE ".$cond;
	$send = query_assoc($sql, GLOBAL_MYSQL_CONNECT);

	$send['filter'] = $filter;
	if(!$send['all'])
		return $send + array(
				'spisok' => $filter['js'].'<div class="_empty">Возвратов нет.</div>',
				'arr' => array()
			);

	$all = $send['all'];

	$sql = "SELECT
				*,
				'refund' `type`
			FROM `_money_refund`
			WHERE ".$cond."
			ORDER BY `dtime_add` DESC
			LIMIT "._start($filter).",".$filter['limit'];
	$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	$spisok = _clientValToList($spisok);
	if(function_exists('_zayavValToList'))
		$spisok = _zayavValToList($spisok);

	$send['spisok'] = $filter['page'] != 1 ? '' :
		$filter['js'].
		'<div id="summa">'.
			'Показан'._end($all, '', 'о').' <b>'.$all.'</b> возврат'._end($all, '', 'а', 'ов').
			' на сумму <b>'._sumSpace($send['sum']).'</b> руб.'.
		'</div>'.
		'<table class="_spisok">'.
			'<tr><th>Сумма'.
				'<th>Описание'.
				'<th>Дата'.
				'<th>';

	foreach($spisok as $r)
		$send['spisok'] .= _refund_unit($r, $filter);

	$send['spisok'] .= _next($filter + array(
			'all' => $all,
			'tr' => 1
		));
	$send['arr'] = $spisok;

	return $send;
}//_refund_spisok()
function _refund_unit($r, $filter=array()) {//строка возврата в таблице
	return
	'<tr><td class="sum '.$r['type']._tooltip('Возврат', -3)._sumSpace($r['sum']).
		'<td>'._refundAbout($r, $filter).
		'<td class="dtime">'._dtimeAdd($r).
		'<td class="ed">'._iconDel($r + array('class'=>'_refund-del'));
}//_refund_unit()
function _refundAbout($r, $filter=array()) {
	$about = '';
	if($r['zayav_id'] && !@$filter['zayav_id'])
		$about .= 'Заявка '.@$r['zayav_link'];

	$about .=
		($r['about'] && $about ? ', ' : '').$r['about'].
		(!@$filter['zayav_id'] ? '<div class="refund-client">Клиент: '.$r['client_link'].'</div>' : '');

	return '<span class="type">'._invoice($r['invoice_id']).($about ? ':' : '').'</span> '.$about;
}//incomeAbout()
function _refund_right() {
	return
		'<div class="f-label">Счета</div>'.
		'<input type="hidden" id="invoice_id" />'.
		'<script type="text/javascript">_refundLoad();</script>';
}//_refund_right()

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
		'<a class="_income-add add">Внести платёж</a>';
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
	$filter = _filterJs('INCOME', $filter);

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
		return $send + array(
						'spisok' => $filter['js'].'<div class="_empty">Платежей нет.</div>',
						'arr' => array()
					);

	$all = $send['all'];

	$sql = "SELECT
				*,
				'income' `type`
			FROM `_money_income`
			WHERE ".$cond."
			ORDER BY `dtime_add` DESC
			LIMIT "._start($filter).",".$filter['limit'];
	$money = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	//$money = _viewer($money);
	$money = _clientValToList($money);
	if(function_exists('_zayavValToList'))
		$money = _zayavValToList($money);
//	$money = _zpLink($money);
//	$money = _schetValues($money);

	$send['spisok'] = $filter['page'] != 1 ? '' :
		$filter['js'].
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
		$send['spisok'] .= _income_unit($r, $filter);

	$send['spisok'] .= _next($filter + array(
			'type' => 3,
			'all' => $all,
			'tr' => 1
		));
	$send['arr'] = $money;

	return $send;
}//income_spisok()
function _income_unit($r, $filter=array()) {
	return
		'<tr class="_income-unit'.($r['deleted'] ? ' deleted' : '').($r['prepay'] ? ' prepay' : '').'">'.
			'<td class="sum '.$r['type'].(@$filter['zayav_id'] ? _tooltip('Платёж', 8) : '">')._sumSpace($r['sum']).
			'<td>'.incomeAbout($r, $filter).
			'<td class="dtime">'._dtimeAdd($r).
			'<td class="ed">'._iconDel($r + array('class'=>'income-del'));
}//_income_unit()
function incomeAbout($r, $filter=array()) {
	$about = '';
	if($r['zayav_id'] && !@$filter['zayav_id'])
		$about .= 'Заявка '.@$r['zayav_link'];
	if($r['zp_id'])
		$about .= 'Продажа запчасти '.$r['zp_link'];
	if($r['schet_id'])
		$about .= '<br />Счёт № СЦ'.$r['schet_nomer'].'. День оплаты: '.FullData($r['schet_paid_day'], 1);

	$about .=
		($r['about'] && $about ? ', ' : '').$r['about'].
		($r['client_id'] && !@$filter['zayav_id'] ? '<div class="income-client">Клиент: '.$r['client_link'].'</div>' : '');

	return '<span class="type">'._invoice($r['invoice_id']).($about ? ':' : '').'</span> '.$about;
}//incomeAbout()

/* --- расходы --- */
function _expense($id=0, $i='name') {//Список категорий расходов
	$key = CACHE_PREFIX.'expense'.WS_ID;
	$arr = xcache_get($key);
	if(empty($arr)) {
		$sql = "SELECT *
				FROM `_money_expense_category`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				ORDER BY `sort`";
		$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT);
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
	return array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 50,
		'invoice_id' => _num(@$v['invoice_id']),
		'category_id' => _num(@$v['category_id']),
		'worker_id' => _num(@$v['worker_id']),
		'year' => _year(@$v['year']),
		'month' => isset($v['month']) ? _ids(@$v['month']) : intval(strftime('%m')) //список выбранных месяцев
		//'del' => isset($v['del']) && preg_match(REGEXP_BOOL, $v['del']) ? $v['del'] : 0
	);
}//expenseFilter()
function expense_right() {
	$sql_worker = "SELECT DISTINCT `worker_id`
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker_id`";

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
			'var EXPENSE_WORKER='.query_workerSelJson($sql_worker, GLOBAL_MYSQL_CONNECT).';'.
			'expenseLoad();'.
		'</script>';
}//expense_right()
function expenseMonthSum($v=array()) {//список чекбоксов с месяцами и суммами расходов по каждому месяцу
	$filter = expenseFilter($v);

	$mon = _ids($filter['month'], 1);

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
			in_array($n, $mon),
			1
		);
	return $send;
}//expenseMonthSum()
function expense_spisok($v=array()) {
	$filter = expenseFilter($v);

	define('PAGE1', $filter['page'] == 1);

	$js = !PAGE1 ? '' :
		'<script type="text/javascript">'.
			'var EXPENSE={'.
				'limit:'.$filter['limit'].','.
				'invoice_id:'.$filter['invoice_id'].','.
				'worker_id:'.$filter['worker_id'].','.
				'category_id:'.$filter['category_id'].','.
				'year:'.$filter['year'].','.
				'month:"'.$filter['month'].'"'.
			'};'.
		'</script>';

	$cond = "`app_id`=".APP_ID." AND `ws_id`=".WS_ID." AND !`deleted`";

	if($filter['invoice_id'])
		$cond .= " AND `invoice_id`=".$filter['invoice_id'];
	if($filter['worker_id'])
		$cond .= " AND `worker_id`=".$filter['worker_id'];
	if($filter['category_id'])
		$cond .= " AND `category_id`=".$filter['category_id'];
	if($filter['month']) {
		$dtime = array();
		foreach(_ids($filter['month'], 1) as $mon)
			$dtime[] = "`dtime_add` LIKE '" . $filter['year'] . "-" . ($mon < 10 ? 0 : '') . $mon . "%'";
		$cond .= " AND (".implode(' OR ', $dtime).")";
	}

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
				'<td>'.expenseAbout($r).
				'<td class="dtime">'._dtimeAdd($r).
				'<td class="ed">'._iconEdit($r)._iconDel($r);

	$send['spisok'] .= _next($filter + array(
			'all' => $all,
			'tr' => 1
		));

	return $send;
}//expense_spisok()
function expenseAbout($r) {//описание для расходов
	return
		($r['category_id'] ? '<span class="type">'._expense($r['category_id']).
			($r['about'] || $r['worker_id'] ? ':' : '').'</span> '
		: '').
		($r['worker_id'] ?
			   _viewer($r['worker_id'], 'viewer_link_zp').
				($r['about'] ? ', ' : '')
		: '').
		$r['about'];
}//expenseAbout()


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
*/



function _invoice($id=0, $i='name') {//получение списка счетов из кеша
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

	//возврат данных всех счетов
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

	//Возвраты
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_refund`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `invoice_id`=".$invoice_id;
	$refund = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//перевод: счёт-отправитель
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_invoice_transfer`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `invoice_id_from`=".$invoice_id;
	$from = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//перевод: счёт-получатель
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_invoice_transfer`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `invoice_id_to`=".$invoice_id;
	$to = query_value($sql, GLOBAL_MYSQL_CONNECT);

	return round($income - $expense - $refund - $from + $to - $start, 2);
}//_invoiceBalans()
function invoice() {//страница со списком счетов и переводами между счетами
	if(_num(@$_GET['id']))
		return invoice_info();

	return
		'<div id="money-invoice">'.
			'<div class="headName">'.
				'Расчётные счета'.
				'<a class="add" id="transfer-add">Перевод между счетами</a>'.
				'<span>::</span>'.
				'<a href="'.URL.'&p=setup&d=invoice" class="add">Управление счетами</a>'.
			'</div>'.
			'<div id="invoice-spisok">'.invoice_spisok().'</div>'.
		(RULE_INVOICE_TRANSFER ?
			'<div class="headName">История переводов между счетами</div>'.
			'<div id="transfer-spisok">'.invoice_transfer_spisok().'</div>'
		: '').
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
					'<a href="'.URL.'&p=money&d=invoice&id='.$r['id'].'">'.$r['name'].'</a>'.
					'<div class="about">'.$r['about'].'</div>'.
			($r['start'] != -1 ?
				'<td class="balans"><b>'._sumSpace(_invoiceBalans($r['id'])).'</b> руб.'.
				'<td><div val="1:'.$r['id'].'" class="_balans-show img_note'._tooltip('Посмотреть историю операций', -95).'</div>'
				:
				'<td><a class="invoice-set" val="'.$r['id'].'">Установить<br />текущую сумму</a>'
			);
//			(VIEWER_ADMIN && $r['start'] != -1 ?
//				'<td><a class="invoice-reset" val="'.$r['id'].'">Сбросить<br />сумму</a>'
//			: '');
	$send .= '</table>';
	return $send;
}//invoice_spisok()
function invoice_transfer_spisok($v=array()) {//история переводов по счетам
	$filter = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 30
	);

	$cond = "`app_id`=".APP_ID."
		 AND `ws_id`=".WS_ID."
		 AND !`deleted`";

	$sql = "SELECT COUNT(*) FROM `_money_invoice_transfer` WHERE ".$cond;
	$all = query_value($sql, GLOBAL_MYSQL_CONNECT);
	if(!$all)
		return 'Переводов нет.';

	$sql = "SELECT *
	        FROM `_money_invoice_transfer`
	        WHERE ".$cond."
	        ORDER BY `id` DESC
	        LIMIT "._start($filter).",".$filter['limit'];
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$send = $filter['page'] != 1 ? '' :
		'<table class="_spisok">'.
			'<tr>'.
				'<th>Cумма'.
				'<th>Со счёта'.
				'<th>На счёт'.
				'<th>Подробно'.
				'<th>Дата'.
				'<th>';
	while($r = mysql_fetch_assoc($q))
		$send .=
			'<tr>'.
				'<td class="sum">'._sumSpace($r['sum']).
				'<td><span class="type">'._invoice($r['invoice_id_from']).'</span>'.
				'<td><span class="type">'._invoice($r['invoice_id_to']).'</span>'.
				'<td class="about">'.$r['about'].
				'<td class="dtime">'._dtimeAdd($r).
				'<td class="ed">'._iconDel($r);

	$send .= _next($filter + array(
			'all' => $all,
			'tr' => 1
		));

	return $send;
}//invoice_transfer_spisok()

function invoice_info_balans_day($v) {//отображение баланса счёта за каждый день
	$v = array(
		'invoice_id' => _num($v['invoice_id']),
		'year' => !_num(@$v['year']) ? strftime('%Y') : $v['year'],
		'mon' => !_num(@$v['mon']) ? strftime('%m') : ($v['mon'] < 10 ? 0 : '').$v['mon']
	);

//	define('MONTH', $v['year'].'-'.$v['mon']);
	define('MONTH', '2015-10');

	$ass = array();

	// остаток на конец дня
	$sql = "SELECT
				DATE_FORMAT(`dtime_add`,'%d') AS `day`,
				`balans`
			FROM `_balans`
			WHERE `id` IN (
				SELECT
					MAX(`id`)
				FROM `_balans`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `category_id`=1
				  AND `unit_id`=".$v['invoice_id']."
				  AND `dtime_add` LIKE '".MONTH."%'
				GROUP BY DATE_FORMAT(`dtime_add`,'%d')
				ORDER BY `id`
			)";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$ass[intval($r['day'])]['balans'] = round($r['balans'], 2);

	// суммы приходов
	$sql = "SELECT
				DATE_FORMAT(`dtime_add`,'%d') AS `day`,
				SUM(`sum`) AS `sum`
			FROM `_balans`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `category_id`=1
			  AND `unit_id`=".$v['invoice_id']."
			  AND `sum`>0
			  AND `dtime_add` LIKE '".MONTH."%'
			GROUP BY DATE_FORMAT(`dtime_add`,'%d')
			ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$ass[intval($r['day'])]['inc'] = round($r['sum'], 2);

	// суммы расходов
	$sql = "SELECT
				DATE_FORMAT(`dtime_add`,'%d') AS `day`,
				SUM(`sum`) AS `sum`
			FROM `_balans`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `category_id`=1
			  AND `unit_id`=".$v['invoice_id']."
			  AND `sum`<0
			  AND `dtime_add` LIKE '".MONTH."%'
			GROUP BY DATE_FORMAT(`dtime_add`,'%d')
			ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$ass[intval($r['day'])]['dec'] = round($r['sum'], 2);

	$unix = strtotime(MONTH.'-01');
	$prev_month = strftime('%Y-%m', $unix - 86400);

	// баланс за последний день
	$sql = "SELECT `balans`
			FROM `_balans`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `category_id`=1
			  AND `unit_id`=".$v['invoice_id']."
			  AND `dtime_add` LIKE '".$prev_month."%'
			ORDER BY `id` DESC
			LIMIT 1";
	$balans = query_value($sql, GLOBAL_MYSQL_CONNECT);

	$send = '<table class="_spisok l" id="balans-day">'.
			'<tr><th>День'.
				'<th>Начало дня'.
				'<th>Приход'.
				'<th>Расход'.
				'<th>Разница'.
				'<th>Остаток';

//	$balans = $balans ? $balans : 0;
	for($d = 1; $d <= date('t', $unix); $d++) {
		if(strtotime(MONTH.'-'.$d) > TODAY_UNIXTIME)
			break;
		$day = FullData(MONTH.'-'.$d, 1, 1, 1);
		$balans = isset($ass[$d]) ? $ass[$d]['balans'] : $balans;
		$inc = isset($ass[$d]['inc']) ? $ass[$d]['inc'] : 0;
		$dec = isset($ass[$d]['dec']) ? $ass[$d]['dec'] : 0;
		$diff = $inc + $dec;
		$start = $balans - $inc - $dec;
		$send .= '<tr'.(isset($ass[$d]) ? '' : ' class="emp"').'>'.
				'<td class="day">'.(isset($ass[$d]) ? '<a class="to-day" val="'.MONTH.'-'.($d < 10 ? 0 : '').$d.'">'.$day.'</a>' : $day).
				'<td class="start">'._sumSpace($start).
				'<td class="inc">'.($inc ? _sumSpace($inc) : '').
				'<td class="dec">'.($dec ? _sumSpace($dec) : '').
				'<td class="diff '.($diff > 0 ? 'inc' : 'dec').'">'.($diff ? _sumSpace($diff) : '').
				'<td class="ost">'._sumSpace($balans);
	}
	$send .= '</table>';
	return $send;
}//invoice_info_balans_day()

function _balans($v) {//внесение записи о балансе
	$category_id = _balansAction($v['action_id'], 'category_id');
	$unit_id = 0;
	$balans = 0;
	$sum = _cena(@$v['sum'], 1);
	$invoice_transfer_id = _num(@$v['invoice_transfer_id']);

	if(_balansAction($v['action_id'], 'minus'))
		$sum *= -1;

	// счёт
	if(!empty($v['invoice_id'])) {
		$unit_id = _num($v['invoice_id']);
		$balans = _invoiceBalans($unit_id);

		// изменение знака суммы у счёта-отправителя
		if($invoice_transfer_id) {
			$sql = "SELECT * FROM `_money_invoice_transfer` WHERE `id`=".$invoice_transfer_id;
			$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT);
			if($unit_id == $r['invoice_id_from'])
				$sum *= -1;
		}
	}

	//клиент
	if(!empty($v['client_id'])) {
		$unit_id = _num($v['client_id']);
		$balans = clientBalansUpdate($unit_id);
	}

	//сотрудник
	if(!empty($v['worker_id'])) {
		$unit_id = _num($v['worker_id']);
		$balans = salaryWorkerBalans($unit_id);
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

				`income_id`,
				`expense_id`,
				`invoice_transfer_id`,

				`viewer_id_add`
			) VALUES (
				".APP_ID.",
				".WS_ID.",

				".$category_id.",
				".$unit_id.",
				".$v['action_id'].",
				".$sum.",
				".$balans.",
				'".addslashes(@$v['about'])."',

				"._num(@$v['income_id']).",
				"._num(@$v['expense_id']).",
				".$invoice_transfer_id.",

				".VIEWER_ID."
			)";
	query($sql, GLOBAL_MYSQL_CONNECT);
}//_balans()
function _balansAction($id, $i='name') {//получение списка названий действий по счетам из кеша
	$key = CACHE_PREFIX.'balans_action';
	$arr = xcache_get($key);
	if(empty($arr)) {
		$sql = "SELECT * FROM `_balans_action`";
		$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT);
		xcache_set($key, $arr, 86400);
	}

	if(!isset($arr[$id]))
		return '<span class="red">неизвестное действие баланса: <b>'.$id.'</b></span>';

	if(!isset($arr[$id][$i]))
		return '<span class="red">неизвестный ключ действия баланса: <b>'.$i.'</b></span>';

	return $arr[$id][$i];
}//_balansAction()

function balansFilter($v) {
	return array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 50,
		'category_id' => _num(@$v['category_id']),
		'unit_id' => _num(@$v['unit_id'])
//		'day' => !empty($v['day']) && preg_match(REGEXP_DATE, $v['day']) ? $v['day'] : TODAY
	);
}//balansFilter()
function balans_show($v) {//вывод таблицы с балансами конкретного счёта
	$filter = balansFilter($v);

	$r = balans_show_category($filter);
	if(!empty($r['error']))
		return $r['about'];

	$data = balans_show_spisok($filter);

	return
		'<div id="balans-show">'.
			'<div class="headName">'.$r['head'].'</div>'.
			(@$r['about'] ? '<div class="_info">'.$r['about'].'</div>' : '').
			'<div>Текущий баланс: <b>'.$r['balans'].'</b> руб.</div>'.

			'<div id="dopLinks">' .
				'<span>История операций:</span>'.
				'<a class="link sel">Подробно</a>' .
				'<a class="link">Ежедневно</a>' .
			'</div>'.
			'<div id="ih-spisok" class="ih-cont">'.$data['spisok'].'</div>'.
			'<div id="ih-day" class="ih-cont dn">'.
				'<div id="ih-data">'.
					'<input type="hidden" id="ih-year" value="'.strftime('%Y').'" />'.
					'<input type="hidden" id="ih-mon" value="'.intval(strftime('%m')).'" />'.
				'</div>'.
//				invoice_info_balans_day(array('invoice_id'=>$invoice_id)).
			'</div>'.
		'</div>'.

		'<script type="text/javascript">'.
			'var BALANS={'.
				'limit:'.$filter['limit'].','.
				'category_id:'.$filter['category_id'].','.
				'unit_id:'.$filter['unit_id'].
			'};'.
		'</script>';
}//balans_show()
function balans_show_category($v) {
	switch($v['category_id']) {
		case 1: //расчётные счета
			$sql = "SELECT *
					FROM `_money_invoice`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND !`deleted`
					  AND `id`=".$v['unit_id'];
			if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
				return array(
					'error' => 1,
					'about' => _err('Счёта id:<b>'.$v['unit_id'].'</b> не существует.')
				);
			return array(
				'head' => 'Счёт '.$r['name'],
				'about' => $r['about'],
				'balans' => _sumSpace(_invoiceBalans($v['unit_id']))
			);
			break;
		case 2: //клиент
			if(!_clientQuery($v['unit_id']))
				return array(
					'error' => 1,
					'about' => _err('Клиента id:<b>'.$v['unit_id'].'</b> не существует.')
				);
			$r = _clientVal($v['unit_id']);
			return array(
				'head' => 'Клиент '.$r['name'],
				'balans' => _sumSpace($r['balans'])
			);
			break;
		case 5: //зп сотрудника
			if(!_viewerWorkerQuery($v['unit_id']))
				return array(
					'error' => 1,
					'about' => _err('Сотрудника id:<b>'.$v['unit_id'].'</b> не существует.')
				);
			return array(
				'head' => 'Сотрудник '._viewer($v['unit_id'], 'viewer_name'),
				'balans' => salaryWorkerBalans($v['unit_id'], 1)
			);
			break;
	}
	return array(
		'error' => 1,
		'head' => 'Заголовок',
		'about' => _err('Неизвестная категория балансов: <b>'.$v['category_id'].'</b>.'),
		'balans' => 0
	);
}//balans_show_category()
function balans_show_spisok($filter) {
	define('PAGE1', $filter['page'] == 1);

	$cond = "`app_id`=".APP_ID."
		 AND `ws_id`=".WS_ID."
		 AND `category_id`=".$filter['category_id']."
		 AND `unit_id`=".$filter['unit_id'];

	$send = array(
		'all' => 0,
		'spisok' => '<div class="_empty">Истории нет.</div>',
		'filter' => $filter
	);

	$sql = "SELECT COUNT(`id`) FROM `_balans` WHERE ".$cond;
	if(!$all = query_value($sql, GLOBAL_MYSQL_CONNECT))
		return $send;

	$sql = "SELECT *
			FROM `_balans`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT "._start($filter).",".$filter['limit'];
	$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	$transfer = array();
	if($transfer_ids = _idsGet($spisok, 'invoice_transfer_id')) {
		$sql = "SELECT * FROM `_money_invoice_transfer` WHERE `id` IN (".$transfer_ids.")";
		$transfer = query_arr($sql, GLOBAL_MYSQL_CONNECT);
	}

	$income = array();
	if($income_ids = _idsGet($spisok, 'income_id')) {
		$sql = "SELECT * FROM `_money_income` WHERE `id` IN (".$income_ids.")";
		$income = query_arr($sql, GLOBAL_MYSQL_CONNECT);
		$income = _clientValToList($income);
		if(function_exists('_zayavValToList'))
			$income = _zayavValToList($income);
	}

	$expense = array();
	if($expense_ids = _idsGet($spisok, 'expense_id')) {
		$sql = "SELECT * FROM `_money_expense` WHERE `id` IN (".$expense_ids.")";
		$expense = query_arr($sql, GLOBAL_MYSQL_CONNECT);
		$expense = _viewer($expense);
	}

	$send['spisok'] = !PAGE1 ? '' :
		'<table class="_spisok" id="balans-tab">'.
			'<tr><th>Действие'.
				'<th>Сумма'.
				'<th>Баланс'.
				'<th>Описание'.
				'<th>Дата';

	foreach($spisok as $r) {
		$about = $r['about'];

		// описание для переводов между счетами
		if($r['invoice_transfer_id']) {
			$trans = $transfer[$r['invoice_transfer_id']];
			if($trans['invoice_id_from'] != $r['unit_id'])
				$about = 'Поступление со счёта <span class="type">'._invoice($trans['invoice_id_from']).'</span>.';
			elseif($trans['invoice_id_to'] != $r['unit_id'])
				$about = 'Отправление на счёт <span class="type">'._invoice($trans['invoice_id_to']).'</span>.';
		}

		// описание для платежей
		if($r['income_id']) {
			$income[$r['income_id']]['client_id'] = 0;
			$about = incomeAbout($income[$r['income_id']]);
		}

		// описание для расходов
		if($r['expense_id'])
			$about = expenseAbout($expense[$r['expense_id']]);

		$send['spisok'] .=
			'<tr><td class="action">'._balansAction($r['action_id']).
				'<td class="sum">'._sumSpace(round($r['sum'], 2)).
				'<td class="balans">'._sumSpace(round($r['balans'], 2)).
				'<td>'.$about.
				'<td class="dtime">'._dtimeAdd($r);
	}

	$send['spisok'] .= _next($filter + array(
			'all' => $all,
			'tr' => 1,
			'id' => '_balans_next'
		));

	return $send;
}//balans_show_spisok()








/* --- Счета на оплату --- */

function _schetQuery($id) {//запрос данных об одном клиенте
	$sql = "SELECT *
			FROM `_schet`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `id`=".$id;
	return query_assoc($sql, GLOBAL_MYSQL_CONNECT);
}//_schetQuery()
function _schetFilter($v) {
	$send = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 50,
		'find' => trim(@$v['find']),
		'client_id' => _num(@$v['client_id']),
		'zayav_id' => _num(@$v['zayav_id']),
		'passpaid' => _num(@$v['passpaid'])
	);
	return $send;
}//_schetFilter()
function _schet() {
	$data = _schet_spisok();
	return
	'<table class="tabLR" id="money-schet">'.
		'<tr><td class="left">'.
				'<div class="headName">Счета на оплату</div>'.
				'<div id="spisok">'.$data['spisok'].'</div>'.
			'<td class="right">'._schet_right().
	'</table>';
}//_schet()
function _schet_spisok($v=array()) {
	$filter = _schetFilter($v);
	$filter = _filterJs('SCHET', $filter);

	$cond = "`app_id`=".APP_ID."
		 AND `ws_id`=".WS_ID;

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
			FROM `_schet`
			WHERE ".$cond;
	$send = query_assoc($sql, GLOBAL_MYSQL_CONNECT);
	$send['filter'] = $filter;
	if(!$send['all'])
		return $send + array('spisok' => $filter['js'].'<div class="_empty">Счетов нет.</div>');

	$all = $send['all'];
	$filter['all'] = $all;

	$send['spisok'] =
		($filter['page'] == 1 ?
			$filter['js'].
			'<div id="result">'.
				'Показан'._end($all, '', 'о').' <b>'.$all.'</b> сч'._end($all, 'ёт', 'ёта', 'етов').
				' на сумму <b>'._sumSpace($send['sum']).'</b> руб.'.
			'</div>'.
			'<table class="_spisok">'
		: '');

	$sql = "SELECT *
			FROM `_schet`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT "._start($filter).",".$filter['limit'];
	$q = query($sql, GLOBAL_MYSQL_CONNECT);

	$spisok = array();
	while($r = mysql_fetch_assoc($q)) {
		$r['paids'] = array();
		$spisok[$r['id']] = $r;
	}

	$spisok = _zayavValToList($spisok);


	//список платежей по счетам
	$sql = "SELECT *
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `schet_id` IN (".implode(',', array_keys($spisok)).")";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['schet_id']]['paids'][] = array(
			'sum' => $r['sum'],
			'day' => $r['schet_paid_day']
		);

	foreach($spisok as $r)
		$send['spisok'] .= _schet_unit($r);

	$send['spisok'] .=
		_next(array(
				'tr' => 1,
				'type' => 4
			) + $filter);

	return $send;
}//_schet_spisok()
function _schet_unit($r, $zayav=1) {
	$paid = $r['paid_sum'] >= $r['sum'];
	$pass_info = $r['pass'] && !$paid ? '<div class="pass-info">Передано клиенту '.FullData($r['pass_day'], 1).'</div>' : '';

	$paid_info = '';
	if($r['paid_sum'] > 0)
		foreach($r['paids'] as $i)
			$paid_info .= '<div class="paid-info">Оплачено <b>'.round($i['sum'], 2) . '</b> руб. '.FullData($i['day'], 1).'.</div>';

	$paid = $paid ? ' paid' : '';
	$pass = $pass_info ? ' pass' : '';
	return
		'<tr class="schet-unit'.$pass.$paid.'">'.
			'<td class="td-content">'.
				'<input type="hidden"'.($paid ? '' : ' class="schet-action"').' id="act'.$r['id'].'" />'.
				'<a class="info" val="'.$r['id'].'">'.
					'Счёт № <b class="pay-nomer">СЦ'.$r['nomer'].'</b>'.
				'</a> '.
//				' + накладная '.
				'от <u>'.FullData($r['date_create']).'</u> г. '.
//				'<br />'.
				'на сумму <b class="pay-sum">'._sumSpace($r['sum']).'</b> руб. '.
				$pass_info.
				$paid_info.
			($zayav ? '<div class="schet-zayav">Заявка '.$r['zayav_link'].'.</div>' : '');
}//_schet_unit()
function _schet_right() {
	return
		'<div id="find"></div>'.
		'<div class="findHead">Показывать счета:</div>'.
		_radio('passpaid',
			array(
				0 => 'ВСЕ',
				1 => 'Не переданы',
				2 => 'Переданы, не опл.',
				3 => 'Оплачены'
			), 0, 1);
}//_schet_right()

/*
function zayav_info_schet($zayav_id) {//Счета
	return
		'<div class="headBlue">'.
			'<a href="'.URL.'&p=report&d=money&d1=schet"><b>Счета, накладные, акты</b></a>'.
			'<a class="add schet-add">Сформировать счёт</a>'.
		'</div>'.
		'<div id="schet-spisok">'.zayav_info_schet_spisok($zayav_id).'</div>';
}//zayav_info_schet()
function zayav_info_schet_spisok($zayav_id) {
	$sql = "SELECT * FROM `zayav_schet` WHERE `zayav_id`=".$zayav_id." ORDER BY `id`";
	$q = query($sql);
	if(!mysql_num_rows($q))
		return '<div id="no-schet">Счетов нет. <a class="schet-add">Сформировать</a></div>';

	$spisok = array();
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']] = $r;

	$spisok = _zayavValToList($spisok);

	//список платежей по счетам
	$sql = "SELECT * FROM `money` WHERE `schet_id` IN (".implode(',', array_keys($spisok)).")";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['schet_id']]['paids'][] = array(
			'sum' => $r['sum'],
			'day' => $r['schet_paid_day']
		);

	$send =
		'<table class="_spisok _money">';
	foreach($spisok as $r)
		$send .= schet_unit($r, 0);
	$send .= '</table>';
	return $send;
}//zayav_info_schet_spisok()
*/











/* --- вывод списка начислений, платежей и возвратов в информации о заявке --- */
function _zayavInfoMoney($zayav_id) {
	return '<div id="_zayav-money">'._zayavInfoMoney_spisok($zayav_id).'</div>';
}//_zayavInfoMoney()
function _zayavInfoMoney_spisok($zayav_id) {
	//начисления
	$sql = "SELECT
				*,
				'accrual' `type`
			FROM `_money_accrual`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	$accrual = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	//платежи
	$income = income_spisok(array('zayav_id'=>$zayav_id));

	//возвраты
	$refund = _refund_spisok(array('zayav_id'=>$zayav_id));

	$money = _arrayTimeGroup($accrual);
	$money = _arrayTimeGroup($income['arr'], $money);
	$money = _arrayTimeGroup($refund['arr'], $money);

	if(empty($money))
		return '';

	krsort($money);

	$spisok = '';
	foreach($money as $r) {
		if($r['type'] == 'accrual')
			$spisok .= _accrual_unit($r);
		elseif($r['type'] == 'refund')
			$spisok .= _refund_unit($r, array('zayav_id'=>$zayav_id));
		else
			$spisok .= _income_unit($r, array('zayav_id'=>$zayav_id));
	}
	return
		'<div class="headBlue">'.
			'Начисления и платежи'.
			'<a class="add _refund-add'._tooltip('Произвести возврат денежных средств', -215, 'r').'Возврат</a>'.
			'<em>::</em>'.
			'<a class="add _income-add">Внести платёж</a>'.
			'<em>::</em>'.
			'<a class="add _accrual-add">Начислить</a>'.
		'</div>'.
		'<table class="_spisok">'.$spisok.'</table>';
}//_zayavInfoMoney_spisok()

