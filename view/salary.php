<?php
function _salary() {
	if(_num(@$_GET['id']))
		return salary_worker($_GET);

	return
		'<div id="salary">'.
			'<div class="headName">Зарплата сотрудников</div>'.
			'<div id="spisok">'._salary_spisok().'</div>'.
		'</div>';
}//_salary()
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

	//произвольные начисления
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

	//Начисления по заявкам
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

	//вычеты
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

	//Выданная  з/п (берётся из расходов)
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
				'<tr><th>Фио'.
					'<th>Ставка'.
					'<th>Баланс';
	foreach($worker as $r) {
		$balans = round($r['balans'] + $r['salary_balans_start'], 2);
		$send .=
			'<tr><td class="fio"><a href="'.URL.'&p=report&d=salary&id='.$r['id'].'" class="name">'.$r['name'].'</a>'.
				'<td class="rate">'.($r['salary_rate_sum'] == 0 ? '' : '<b>'.round($r['salary_rate_sum'], 2).'</b>/'._salaryPeriod($r['salary_rate_period'])).
				'<td class="balans" style="color:#'.($balans < 0 ? 'A00' : '090').'">'._sumSpace($balans);
	}
	$send .= '</table>';

	return $send;
}//_salary_spisok()
function _salaryPeriod($v=false) {
	$arr = array(
		1 => 'месяц',
		2 => 'неделя',
		3 => 'день'
	);
	if($v == false)
		return $arr;
	return $arr[$v];
}//_salaryPeriod()

function salary_month_list($v) {
	$filter = salaryFilter($v);

	$acc = array();
	$zp = array();
	for($n = 1; $n <= 12; $n++) {
		$acc[$n] = 0;
		$zp[$n] = 0;
	}

	//начисления по заявкам
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

	//произвольные начисления
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

	//вычеты
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

	//з/п
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
}//salary_month_list()
function salaryFilter($v) {
	$v = array(
		'id' => _num(@$v['id']),
		'mon' => _num(@$v['mon']) ? intval($v['mon']) : intval(strftime('%m')),
		'year' => _num(@$v['year']) ? intval($v['year']) : intval(strftime('%Y')),
		'acc_id' => _num(@$v['acc_id'])
	);
	$v['month'] = _monthDef($v['mon'], 1).' '.$v['year'];
	$v['year-mon'] = $v['year'].'-'.($v['mon'] < 10 ? 0 : '').$v['mon'];
	return $v;
}//salaryFilter()
function salaryWorkerBalans($worker_id, $color=0) {//получение текущего баланса зп сотрудника
	$start = _viewer($worker_id, 'balans_start');

	//произвольные начисления
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_salary_accrual`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker_id`=".$worker_id;
	$acc = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//начисления по заявкам
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker_id`=".$worker_id;
	$zayav = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//вычеты
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_salary_deduct`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker_id`=".$worker_id;
	$deduct = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//выданная зп
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker_id`=".$worker_id."
			  AND !`deleted`";
	$zp = query_value($sql, GLOBAL_MYSQL_CONNECT);

	$balans = round($acc + $zayav - $deduct - $zp + $start, 2);

	if($color)
		return '<b style="color:#'.($balans < 0 ? 'A00' : '090').'">'._sumSpace($balans).'</b>';

	return $balans;
}//salaryWorkerBalans()
function salaryWorkerRate($worker_id) {//получение ставки сотрудника
	$u = _viewer($worker_id);
	$period = '';
	switch($u['rate_period']) {
		case 1: $period = $u['rate_day'].'-е число месяца'; break;
		case 2: $period = 'еженедельно, '.$u['rate_day'].'-й день недели'; break;
		case 3: $period = 'ежедневно'; break;
	}
	return
		$u['rate_sum'] ?
			'<b>'._sumSpace($u['rate_sum']).'</b> руб.<span>('.$period.')</span>'
			:
			'нет';
}
function salary_worker($v) {
	$filter = salaryFilter($v);

	if(!$r = _viewerWorkerQuery($filter['id']))
		return _err('Сотрудника не существует.');

	define('WORKER_OK', true);//для вывода фильтра

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
		'<div class="headName">'._viewer($filter['id'], 'viewer_name').': история з/п за <em>'.$filter['month'].'</em>.</div>'.
		'<h2>Баланс: '.
			'<a  val="5:'.$filter['id'].'" class="_balans-show'._tooltip('История операций', -40).salaryWorkerBalans($filter['id'], 1).'</a> руб.'.
			'<input type="hidden" id="action" />'.
		'<h2>'.
		'<h1>Ставка: <em>'.salaryWorkerRate($filter['id']).'</em></h1>'.
		'<div id="spisok-acc">'.salary_worker_acc($filter).'</div>'.
		'<div id="spisok-zp">'.salary_worker_zp($filter).'</div>'.
	'</div>';
}//salary_worker()
/*
function salary_worker($v) {





	return
	'<script type="text/javascript">'.
		'var PROCENT='._viewerRules($filter['worker_id'], 'RULES_MONEY_PROCENT').';'.
}//salary_worker()
*/

function salary_worker_acc($v) {
	$filter = salaryFilter($v);

	$sql = "SELECT
				*,
				'Начисление' `type`,
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
				'Вычет' `type`,
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
				'Начисление' `type`
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker_id`=".$filter['id']."
			  AND `year`=".$filter['year']."
			  AND `mon`=".$filter['mon'];
	$zayav = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	if(function_exists('_zayavValToList'))
		$zayav = _zayavValToList($zayav);

	$spisok = _arrayTimeGroup($accrual);
	$spisok += _arrayTimeGroup($zayav, $spisok);
	$spisok += _arrayTimeGroup($deduct, $spisok);
	krsort($spisok);

	$send = '';
	$accSum = 0;
	$zayavSum = 0;
	$dSum = 0;
	foreach($spisok as $r) {
		$about = $r['zayav_id'] ?
			$r['zayav_color'].$r['zayav_dolg']
			:
			$r['about'];

		$del = $r['zayav_id'] ? '' :
			_iconDel(array(
				'id' => $r['id'],
				'class' => $r['type'] == 'Начисление' ? 'worker-acc-del' : 'worker-deduct-del'
			));

		$send .=
			'<tr val="'.$r['id'].'" class="'.($filter['acc_id'] == $r['id'] ? ' show' : '').'">'.
				'<td class="type">'.$r['type'].
				'<td class="sum">'.round($r['sum'], 2).
				'<td class="about">'.$about.
				'<td class="dtime">'.FullDataTime($r['dtime_add']).
				'<td class="ed">'.$del;

		$accSum += (!$r['zayav_id'] && $r['sum'] > 0 ? $r['sum'] : 0);
		$zayavSum += ($r['zayav_id'] ? $r['sum'] : 0);
		$dSum += ($r['sum'] < 0 ? $r['sum'] : 0);
	}

	$allCount = count($accrual) + count($deduct) + count($zayav);
	$send =
		'<h4>'.
			(!$allCount ?
				'<div>Записей нет.</div>' :
				'<div><u>Всего <b>'.$allCount.'</b> запис'._end($allCount, 'ь', 'и', 'ей').'. Общая сумма <b>'._sumSpace($accSum + $zayavSum + $dSum).'</b> руб.</u></div>'
			).
			($accSum ? '<div>Начисления: <b>'.count($accrual).'</b> на сумму<b> '._sumSpace($accSum).'</b> руб.</div>' : '').
			($zayavSum ? '<div>Заявки: <b>'.count($zayav).'</b> на сумму<b> '._sumSpace($zayavSum).'</b> руб.</div>' : '').
			($dSum ? '<div>Вычеты: <b>'.count($deduct).'</b> на сумму<b> '._sumSpace($dSum).'</b> руб.</div>' : '').
		'</h4>'.

		'<table class="_spisok">'.
			'<tr>'.
				'<th>Вид'.
				'<th>Сумма'.
				'<th>Описание'.
				'<th>Дата'.
				'<th>'.
			$send.
		'</table>';
	return $send;
}//salary_worker_acc()
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
			'<b>З/п за '.$filter['month'].'</b>:'.
			'<span><a class="worker-zp-add">Выдать з/п</a> :: Сумма: <b>'.$summa.'</b> руб.</span>'.
		'</h3>'.
		'<table class="_spisok">'.
			'<tr><th>Сумма'.
				'<th>Описание'.
				'<th>Дата'.
				'<th>'.
				$zp.
		'</table>';

	return $send;
}//salary_worker_zp()















// --- Расходы по заявке ---

function _zayavExpense($id=0, $i='name') {//расходы заявки из кеша
	$key = CACHE_PREFIX.'zayav_expense'.APP_ID;
	$arr = xcache_get($key);
	if(empty($arr)) {
		$sql = "SELECT *
				FROM `_zayav_expense_category`
				WHERE `app_id`=".APP_ID."
				ORDER BY `sort`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		$arr = array();
		while($r = mysql_fetch_assoc($q)) {
			$r['txt'] = $r['dop'] == 1;
			$r['worker'] = $r['dop'] == 2;
			$r['zp'] = $r['dop'] == 3;
			$arr[$r['id']] = $r;
		}
		xcache_set($key, $arr, 86400);
	}

	//все категории
	if(!$id)
		return $arr;

	//некорректный id
	if(!_num($id))
		die('Error: _zayav_expense_category id: <b>'.$id.'</b> not correct');

	//неизвестный id
	if(!isset($arr[$id]))
		die('Error: no _zayav_expense_category id: <b>'.$id.'</b>');

	switch($i) {
		case 'all': return $arr[$id];   //возврат данных конкретной категории
		case 'name':
		case 'txt':
		case 'worker':
		case 'zp': return $arr[$id][$i];
		default: return '<span class="red">неизвестный ключ категории заявки: <b>'.$i.'</b></span>';
	}
}//_zayavExpense()
function _zayavExpenseDop($id=false) {//дополнительное условие для категории расхода по заявке
	$arr =  array(
		0 => 'нет',
		1 => 'текстовое поле',
		2 => 'список сотрудников',
		3 => 'список запчастей'
	);
	return $id !== false ? $arr[$id] : $arr;
}//_zayavExpenseDop()
function _zayav_expense($zayav_id) {//вставка расходов по заявке в информацию о заявке
	//$arr = _zpLink($arr);

	return '<div id="_zayav-expense">'._zayav_expense_spisok($zayav_id).'</div>';
}//_zayav_expense()
function _zayav_expense_spisok($zayav_id) {//вставка расходов по заявке в информацию о заявке
	$sql = "SELECT *
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `zayav_id`=".$zayav_id."
			ORDER BY `id`";
	$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	$send =
		'<script type="text/javascript">'.
			'var ZAYAV_EXPENSE=['._zayav_expense_json($arr).'];'.
		'</script>';

	if(empty($arr))
		return $send;

	//сумма начислений по заявке
	$sql = "SELECT SUM(`sum`)
			FROM `_money_accrual`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `zayav_id`=".$zayav_id."
			  AND !`deleted`";
	$accrual_sum = query_value($sql, GLOBAL_MYSQL_CONNECT);

	return
		$send.
		'<div class="headBlue">'.
			'Расходы по заявке'.
			'<div class="img_edit'._tooltip('Изменить расходы по заявке', -88).'</div>'.
		'</div>'.
		'<h1>'.($accrual_sum ? 'Общая сумма начислений: <b>'.round($accrual_sum, 2).'</b> руб.' : 'Начислений нет.').'</h1>'.
		_zayav_expense_html($arr, $accrual_sum);
}//_zayav_expense_spisok()
function _zayav_expense_test($v) {// Проверка корректности данных расходов заявки при внесении в базу
	if(empty($v))
		return true;

	foreach(explode(',', $v) as $r) {
		$ids = explode(':', $r);
		if($ids[0] != 0 && !_num($ids[0]))//id расхода
			return false;
		if(!$cat_id = _num($ids[1]))//категория
			return false;
		$ze = _zayavExpense($cat_id, 'all');
		if(($ze['worker'] || $ze['zp']) && !_num($ids[2]))
			return false;
		if(!_cena($ids[3]) && $ids[3] != 0)
			return false;
	}
	return true;
}//_zayav_expense_test()
function _zayav_expense_html($arr, $accrual_sum=false, $diff=false, $new=false) {//вывод таблицы расходов по заявке
	$expense_sum = 0;
	$send = '<table class="ze-spisok">';
	foreach($arr as $arr_id => $r) {
		$tr = ''; // изначално ничего не менялось
		$changeSum = '';
		$changeDop = '';

		if(is_array($diff)) {
			$line = false; // изначально считаем, что строка была удалена или добавлена
 			foreach($diff as $diff_id => $d) {
				if($arr_id == $diff_id) {
					$line = true;
					if($r['sum'] != $d['sum'])
						$changeSum = ' change';
					if($r['txt'] != $d['txt'] || $r['worker_id'] != $d['worker_id'] || $r['zp_id'] != $d['zp_id'])
						$changeDop = ' class="change"';
					break;
				}
			}
			if(!$line)
				$tr = ' class="'.($new ? 'new' : 'del').'"';
		}

		$sum = round($r['sum'], 2);
		$expense_sum += $sum;
		$send .=
			'<tr'.$tr.'>'.
				'<td class="name">'._zayavExpense($r['category_id']).
				'<td'.$changeDop.'>'.
					(_zayavExpense($r['category_id'], 'txt') ? $r['txt'] : '').
					(_zayavExpense($r['category_id'], 'worker') ?
						'<a class="go-report-salary" val="'.$r['worker_id'].':'.$r['year'].':'.$r['mon'].':'.$r['id'].'">'.
							_viewer($r['worker_id'], 'viewer_name').
						'</a>'
					: '').
	//				(_zayavExpense($r['category_id'], 'zp') ? $r['zp_short'] : '').
				'<td class="sum'.$changeSum.'">'.$sum.' р.';
	}

	if($accrual_sum !== false) {
		$ost = $accrual_sum - $expense_sum;
		$send .= '<tr><td colspan="2" class="itog">Итог:<td class="sum"><b>'.$expense_sum.'</b> р.'.
				 '<tr><td colspan="2" class="itog">Остаток:<td class="sum '.($ost > 0 ? ' plus' : 'minus').'">'.$ost.' р.';

	}
	$send .= '</table>';
//echo '<textarea>'.$send.'<textarea/>';
	return $send;
}//_zayav_expense_html()
function _zayav_expense_json($arr) {//расходы по заявке в формате json
	$json = array();
	foreach($arr as $r) {
		$ze = _zayavExpense($r['category_id'], 'all');
		$json[] = '['.
			$r['id'].','.
			$r['category_id'].','.
			($ze['txt'] ? '"'.trim($r['txt']).'"' : '').
			($ze['worker'] ? intval($r['worker_id']) : '').
			($ze['zp'] ? intval($r['zp_id']) : '').','.
			round($r['sum'], 2).
		']';
	}
	return implode(',', $json);
}//_zayav_expense_json()
function _zayav_expense_array($v) {//расходы по заявке в формате array
	if(empty($v))
		return array();
	$array = array();
	foreach(explode(',', $v) as $r) {
		$ex = explode(':', $r);
		$array[] = array(
			intval($ex[0]),
			intval($ex[1]),
			trim($ex[2]),
			_cena($ex[3])
		);
	}

	return $array;
}//_zayav_expense_array()
