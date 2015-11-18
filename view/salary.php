<?php
function _salary() {
	if(_num(@$_GET['id']))
		return salary_worker($_GET);

	return
		'<div class="headName">Зарплата сотрудников</div>'.
		'<div id="spisok">'._salary_spisok().'</div>';
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


	//Начисления
	$sql = "SELECT
 				`worker_id`,
				IFNULL(SUM(`sum`),0) AS `sum`
			FROM `zayav_expense`
			WHERE `ws_id`=".WS_ID."
			  AND `worker_id`
			GROUP BY `worker_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		if(isset($worker[$r['worker_id']]))
			$worker[$r['worker_id']]['balans'] += $r['sum'];

	$send = '<table class="_spisok">'.
				'<tr><th>Фио'.
					'<th>Ставка'.
					'<th>Баланс';
	foreach($worker as $r) {
		$balans = $r['salary_balans_start'] == -1 ? '' : round($r['balans'] + $r['salary_balans_start'], 2);
		$send .=
			'<tr><td class="fio"><a href="'.URL.'&p=report&d=salary&id='.$r['id'].'" class="name">'.$r['name'].'</a>'.
				'<td class="rate">'.($r['salary_rate_sum'] == 0 ? '' : '<b>'.round($r['salary_rate_sum'], 2).'</b>/'._salaryPeriod($r['salary_rate_period'])).
				'<td class="balans" style="color:#'.($balans < 0 ? 'A00' : '090').'">'.$balans;
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

function salary_monthList($v) {
	$filter = salaryFilter($v);

	$acc = array();
	$zp = array();
	for($n = 1; $n <= 12; $n++) {
		$acc[$n] = 0;
		$zp[$n] = 0;
	}

	//Получение сумм автоматичиских, ручных начислений и по заявкам
	$sql = "SELECT
	            `mon`,
				SUM(`sum`) AS `sum`
			FROM `zayav_expense`
			WHERE `ws_id`=".WS_ID."
			  AND `worker_id`=".$filter['worker_id']."
			  AND `year`=".$filter['year']."
			GROUP BY `mon`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$acc[intval($r['mon'])] = round($r['sum']);

	//Получение сумм зп
	$sql = "SELECT
	            `mon`,
				SUM(`sum`) AS `sum`
			FROM `money`
			WHERE `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `worker_id`=".$filter['worker_id']."
			  AND `year`=".$filter['year']."
			GROUP BY `mon`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$zp[intval($r['mon'])] = abs(round($r['sum'], 2));

	$mon = array();
	foreach(_monthDef(0, 1) as $i => $r)
		$mon[$i] = $r.($acc[$i] || $zp[$i]? '<em>'.$acc[$i].'/'.$zp[$i].'</em>' : '');
	return _radio('salmon', $mon, $filter['mon'], 1);
}//salary_monthList()
function salaryFilter($v) {
	$v = array(
		'worker_id' => _num(@$v['id']),
		'mon' => _num(@$v['mon']) ? intval($v['mon']) : intval(strftime('%m')),
		'year' => _num(@$v['year']) ? intval($v['year']) : intval(strftime('%Y')),
		'acc_id' => _num(@$v['acc_id'])
	);
	$v['month'] = _monthDef($v['mon'], 1).' '.$v['year'];
	$v['year-mon'] = $v['year'].'-'.($v['mon'] < 10 ? 0 : '').$v['mon'];
	return $v;
}//salaryFilter()
function salary_worker($v) {
	$filter = salaryFilter($v);

	$sql = "SELECT COUNT(*)
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker`
			  AND `viewer_id`=".$filter['worker_id'];
	if(!query_value($sql, GLOBAL_MYSQL_CONNECT))
		return _err('Сотрудника не существует.');

	define('WORKER_OK', true);

	$year = array();
	for($n = 2014; $n <= $filter['year']; $n++)
		$year[$n] = $n;

	return
	'<script type="text/javascript">'.
		'var WORKER_ID='.$filter['worker_id'].','.
			'MON='.$filter['mon'].','.
			'MON_SPISOK='._selJson(_monthDef(0, 1)).','.
			'YEAR='.$filter['year'].','.
			'YEAR_SPISOK='._selJson($year).','.
			'RATE={'.
				'sum:'.round(_viewer($filter['worker_id'], 'rate_sum'), 2).','.
				'period:'._viewer($filter['worker_id'], 'rate_period').','.
				'day:'._viewer($filter['worker_id'], 'rate_day').
			'},'.
//			'PROCENT='._viewerRules($filter['worker_id'], 'RULES_MONEY_PROCENT').';'.
	'</script>'.
	'<div class="headName">'._viewer($filter['worker_id'], 'viewer_name').': история з/п за <em>'.$filter['month'].'</em>.</div>'.
	'<div id="spisok">'.salary_worker_spisok($filter).'</div>';
}//salary_worker()
function salary_worker_spisok($v) {
	$filter = salaryFilter($v);

	if(!$filter['worker_id'])
		return 'Некорректный id сотрудника';

	$start = _viewer($filter['worker_id'], 'salary_balans_start');
	if($start != -1) {
		$sMoney = query_value("
			SELECT IFNULL(SUM(`sum`),0)
			FROM `money`
			WHERE `worker_id`=".$filter['worker_id']."
			  AND `sum`<0
			  AND !`deleted`");
		$sExpense = query_value("
			SELECT IFNULL(SUM(`sum`),0)
			FROM `zayav_expense`
			WHERE `mon`
			  AND `worker_id`=".$filter['worker_id']);
		$balans = round($sMoney + $sExpense + $start, 2);
		$balans = '<b style="color:#'.($balans < 0 ? 'A00' : '090').'">'.$balans.'</b> руб.';
	} else
		$balans = '<a class="start-set">установить</a>';

	$rate_sum = _cena(_viewer($filter['worker_id'], 'rate_sum'));
	$rate_period = _viewer($filter['worker_id'], 'rate_period');
	$rate_day = _viewer($filter['worker_id'], 'rate_day');
	$send =
	'<div class="uhead">'.
		'<h1>'.
			'Ставка: '.
				($rate_sum
					? '<b>'.$rate_sum.'</b> руб.'.
					  '<span>('.
						($rate_period == 1 ? $rate_day.'-е число месяца' : '').
						($rate_period == 2 ? 'еженедельно, '.$rate_day.'-й день недели' : '').
						($rate_period == 3 ? 'ежедневно' : '').
					  ')</span>'
					: 'нет'
				).
			'<a class="rate-set">Изменить ставку</a>'.
		'</h1>'.
		'Баланс: '.$balans.
		'<div class="a">'.
	  (SA ? '<a class="bonus">Бонус по платежам</a> :: ' : '').
			'<a class="up">Начислить</a> :: '.
			'<a class="zp_add">Выдать з/п</a> :: '.
			'<a class="deduct">Внести вычет</a>'.
		'</div>'.
	'</div>'.
	'<div id="salary-sel">&nbsp;</div>';

	$send .= salary_worker_acc($filter);
	$send .= salary_worker_zp($filter);
	return $send;
}//salary_worker_spisok()
function salary_worker_acc($v) {
	$sql = "(SELECT
				'Начисление' AS `type`,
				`e`.`id`,
			    `e`.`sum`,
				'' AS `about`,
				`e`.`zayav_id`,
				`e`.`dtime_add`
			FROM `zayav_expense` `e`,
				 `zayav` `z`
			WHERE `z`.`id`=`e`.`zayav_id`
			  AND !`z`.`deleted`
			  AND `e`.`year`=".$v['year']."
			  AND `e`.`mon`=".$v['mon']."
			  AND `e`.`worker_id`=".$v['worker_id']."
			  AND `e`.`sum`>0
			GROUP BY `e`.`id`
		) UNION (
			SELECT
				'Начисление' AS `type`,
				`id`,
			    `sum`,
				`txt` AS `about`,
				0 AS `zayav_id`,
				`dtime_add`
			FROM `zayav_expense`
			WHERE !`zayav_id`
			  AND `worker_id`=".$v['worker_id']."
			  AND `sum`>0
			  AND `year`=".$v['year']."
			  AND `mon`=".$v['mon']."
		) UNION (
			SELECT
				'Вычет' AS `type`,
				`id`,
			    `sum`,
				`txt` AS `about`,
				0 AS `zayav_id`,
				`dtime_add`
			FROM `zayav_expense`
			WHERE `worker_id`=".$v['worker_id']."
			  AND `sum`<0
			  AND `year`=".$v['year']."
			  AND `mon`=".$v['mon']."
		)
		ORDER BY `id` DESC";
	$q = query($sql);
	if(!mysql_num_rows($q))
		return '';
	$spisok = array();
	while($r = mysql_fetch_assoc($q)) {
		$key = strtotime($r['dtime_add']);
		while(isset($spisok[$key]))
			$key++;
		$spisok[$key] = $r;
	}

	$spisok = _zayavLink($spisok);
	krsort($spisok);

	$send = '<table class="_spisok _money">'.
		'<tr>'.
			'<th>Вид'.
			'<th>Сумма'.
			'<th>Описание'.
			'<th>';

	foreach($spisok as $r) {
		$about = $r['zayav_id'] ?
			'<span class="s-zayav" style="background-color:#'.$r['zayav_status_color'].'">'.$r['zayav_link'].'</span>'.
			'<tt>от '.$r['zayav_add'].'</tt>'
			:
			$r['about'];
		$send .=
			'<tr val="'.$r['id'].'" class="'.($v['acc_id'] == $r['id'] ? ' show' : '').'">'.
				'<td class="type">'.$r['type'].
				'<td class="sum">'.round($r['sum'], 2).
				'<td class="about">'.$about.
				'<td class="ed">'.
					($r['type'] == 'Начисление' && !$r['zayav_id'] ? '<div class="img_del ze_del'._tooltip('Удалить', -29).'</div>' : '').
					($r['type'] == 'Вычет' ? '<div class="img_del deduct_del'._tooltip('Удалить', -29).'</div>' : '');
	}
	$send .= '</table>';
	return $send;
}//salary_worker_acc()
function salary_worker_zp($v) {
	$sql = "SELECT *
			FROM `money`
			WHERE `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `worker_id`=".$v['worker_id']."
			  AND `sum`<0
			  AND `year`=".$v['year']."
			  AND `mon`=".$v['mon']."
			ORDER BY `id`";
	$q = query($sql);
	if(!mysql_num_rows($q))
		return '';
	$zp = '';
	$summa = 0;
	while($r = mysql_fetch_assoc($q)) {
		$sum = abs(round($r['sum'], 2));
		$summa += $sum;
		$zp .= '<tr>'.
			'<td class="sum">'.$sum.
			'<td class="about"><span class="type">'._invoice($r['invoice_id']).(empty($r['prim']) ? '' : ':').'</span> '.$r['prim'].
			'<td class="dtime">'.FullDataTime($r['dtime_add']).
			'<td class="ed"><div val="'.$r['id'].'" class="img_del zp_del'._tooltip('Удалить', -29).'</div>';
	}
	$send =
		'<div class="zp-head">'.
			'<b>З/п за '.$v['month'].'</b>:'.
			'<span><a class="zp_add">Выдать з/п</a> :: Сумма: <b>'.$summa.'</b> руб.</span>'.
		'</div>'.
		'<table class="_spisok _money">'.
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
	$sql = "SELECT *
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `zayav_id`=".$zayav_id."
			ORDER BY `id`";
	if(!$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	//$arr = _zpLink($arr);

	//сумма начислений по заявке
	$sql = "SELECT SUM(`sum`)
			FROM `_money_accrual`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `zayav_id`=".$zayav_id."
			  AND !`deleted`";
	$accrual_sum = query_value($sql, GLOBAL_MYSQL_CONNECT);

	return
		'<script type="text/javascript">'.
			'var ZAYAV_EXPENSE=['._zayav_expense_json($arr).'];'.
		'</script>'.
		'<div id="_zayav-expense">'.
			'<div class="headBlue">'.
				'Расходы по заявке'.
				'<div class="img_edit'._tooltip('Изменить расходы по заявке', -88).'</div>'.
			'</div>'.
			'<h1>'.($accrual_sum ? 'Общая сумма начислений: <b>'.round($accrual_sum, 2).'</b> руб.' : 'Начислений нет.').'</h1>'.
			_zayav_expense_html($arr, $accrual_sum).
		'</div>';
}//_zayav_expense()
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
function _zayav_expense_html($arr, $accrual_sum=false, $diff=array(), $new=false) {//вывод таблицы расходов по заявке
	$expense_sum = 0;
	$send = '<table class="ze-spisok">';
	foreach($arr as $arr_id => $r) {
		$tr = ''; // изначално ничего не менялось
		$changeSum = '';
		$changeDop = '';

		if(!empty($diff)) {
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
