<?php
function _money_script() {//скрипты и стили
	return
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/money/money'.MIN.'.css?'.VERSION.'" />'.
		'<script src="'.API_HTML.'/modul/money/money'.MIN.'.js?'.VERSION.'"></script>';
}

function _accrualAdd($v) {//внесение нового начисления
	$client_id = _num(@$v['client_id']);
	$zayav_id = _num(@$v['zayav_id']);
	$schet_id = _num(@$v['schet_id']);
	$dogovor_id = _num(@$v['dogovor_id']);
	$about = _txt(@$v['about']);

	if(!$sum = _cena(@$v['sum']))
		jsonError('Некорректно указана сумма');

	if($client_id && !$zayav_id && !$about)
		jsonError('Укажите описание');

	if($zayav_id) {
		if(!$z = _zayavQuery($zayav_id))
			jsonError('Заявки не существует');
		$client_id = $z['client_id'];
	}

	if(!$client_id && !$zayav_id)
		jsonError('Не указан клиент либо заявка');

	$sql = "INSERT INTO `_money_accrual` (
				`app_id`,
				`client_id`,
				`zayav_id`,
				`schet_id`,
				`dogovor_id`,
				`sum`,
				`about`,
				`viewer_id_add`
			) VALUES (
				".APP_ID.",
				".$client_id.",
				".$zayav_id.",
				".$schet_id.",
				".$dogovor_id.",
				".$sum.",
				'".addslashes($about)."',
				".VIEWER_ID."
			)";
	query($sql);

	//внесение баланса для клиента
	if($client_id)
		_balans(array(
			'action_id' => 25,
			'client_id' => $client_id,
			'zayav_id' => $zayav_id,
			'sum' => $sum,
			'about' => $about
		));

	_zayavBalansUpdate($zayav_id);

	_history(array(
		'type_id' => $zayav_id ? 74 : 133,
		'client_id' => $client_id,
		'zayav_id' => $zayav_id,
		'v1' => $sum,
		'v2' => $about
	));

}
function _accrualFilter($v) {
	$send = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 100,
		'client_id' => _num(@$v['client_id']),
		'zayav_id' => _num(@$v['zayav_id'])
	);
	return $send;
}
function _accrual_spisok($v=array()) {//список начислений
	$filter = _accrualFilter($v);
	$filter = _filterJs('ACCRUAL', $filter);

	$cond = "`app_id`=".APP_ID."
	     AND !`deleted`";

	if($filter['client_id'])
		$cond .= " AND `client_id`=".$filter['client_id'];
	if($filter['zayav_id'])
		$cond .= " AND `zayav_id`=".$filter['zayav_id'];

	$sql = "SELECT
				COUNT(`id`) AS `all`,
				SUM(`sum`) AS `sum`
			FROM `_money_accrual`
			WHERE ".$cond;
	$send = query_assoc($sql);

	$send['filter'] = $filter;
	if(!$send['all'])
		return $send + array(
				'spisok' => $filter['js'].'<div class="_empty">Начислений нет.</div>'
			);

	$all = $send['all'];
	$send['spisok'] = $filter['page'] != 1 ? '' :
		$filter['js'].
		'<div id="summa">'.
			'Показано <b>'.$all.'</b> начислени'._end($all, 'е', 'я', 'й').
			' на сумму <b>'._sumSpace($send['sum']).'</b> руб.'.
		'</div>'.
		'<table class="_spisok">'.
			'<tr><th>Сумма'.
				'<th>Описание'.
				'<th>Дата'.
				'<th>';

	$sql = "SELECT *
			FROM `_money_accrual`
			WHERE ".$cond."
			ORDER BY `dtime_add` DESC
			LIMIT "._start($filter).",".$filter['limit'];
	$spisok = query_arr($sql);

	$spisok = _schetPayValToList($spisok);
	$spisok = _zayavValToList($spisok);
	$spisok = _dogovorValToList($spisok);

	foreach($spisok as $r)
		$send['spisok'] .= _accrual_unit($r, $filter);

	$send['spisok'] .= _next($filter + array(
			'all' => $all,
			'tr' => 1
		));

	$send['arr'] = $spisok;

	return $send;
}
function _accrual_unit($r, $filter) {//строка начисления в таблице
	$about = '';
	if($r['schet_id'])
		$about = $r['schet_pay_acc'];
	if(!$filter['zayav_id'] && $r['zayav_id'])
		$about .= ' Заявка '.$r['zayav_link'].'. ';
	if($r['dogovor_id'])
		$about .= 'Договор <u>'.$r['dogovor_nomer'].'</u> от '.$r['dogovor_data'];
	$about .= $r['about'];

	return
	'<tr class="over1'.($r['deleted'] ? ' deleted' : '').'">'.
		'<td class="color-acc curD sum">'._sumSpace($r['sum']).
		'<td class="about">'.trim($about).
		'<td class="dtime">'._dtimeAdd($r).
		'<td class="ed">'.
			(!$r['schet_id'] && !$r['dogovor_id'] ?
				(!@$filter['client_id'] ? _iconEdit(array(
					'id' => $r['id'],
					'class' => '_accrual-edit'
				)) : '').
				_iconDel(array(
					'id' => $r['id'],
					'class' => '_accrual-del'
				))
			: '');
}
/* --- вывод списка начислений в информации о заявке --- */
function _zayavInfoAccrual($zayav_id) {
	return '<div id="_zayav-accrual">'._zayavInfoAccrual_spisok($zayav_id).'</div>';
}
function _zayavInfoAccrual_spisok($zayav_id) {
	$accrual = _accrual_spisok(array('zayav_id'=>$zayav_id));

	if(!$accrual['all'])
		return '';

	$spisok = '';
	foreach($accrual['arr'] as $r)
		$spisok .= _accrual_unit($r, array('zayav_id'=>$zayav_id));

	return
		'<div class="headBlue but">'.
			'Начисления'.
			'<button class="vk small" onclick="_accrualAdd()">Внести начисление</button>'.
		'</div>'.
		'<table class="_spisok">'.$spisok.'</table>';
}



/* --- возвраты --- */
function _refund() {
	$data = _refund_spisok();
	return
		'<table class="tabLR" id="money-refund">'.
			'<tr><td class="left">'.
					'<div class="headName">Возвраты</div>'.
					'<div id="spisok">'.$data['spisok'].'</div>'.
				'<td class="right">'._refund_right().
		'</table>';
}
function _refundFilter($v) {
	$send = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 50,
		'invoice_id' => _num(@$v['invoice_id']),
		'client_id' => _num(@$v['client_id']),
		'zayav_id' => _num(@$v['zayav_id'])
	);
	return $send;
}
function _refund_spisok($filter=array()) {
	$filter = _refundFilter($filter);
	$filter = _filterJs('REFUND', $filter);

	$cond = "`app_id`=".APP_ID."
		 AND !`deleted`";

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
	$send = query_assoc($sql);

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
	$spisok = query_arr($sql);

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
}
function _refund_unit($r, $filter=array()) {//строка возврата в таблице
	return
	'<tr><td class="sum '.$r['type']._tooltip('Возврат', -3)._sumSpace($r['sum']).
		'<td>'._refundAbout($r, $filter).
		'<td class="dtime">'._dtimeAdd($r).
		'<td class="ed">'._iconDel($r + array('class'=>'_refund-del'));
}
function _refundAbout($r, $filter=array()) {
	$about = '';
	if($r['zayav_id'] && !@$filter['zayav_id'])
		$about .= 'Заявка '.@$r['zayav_link'];

	$about .=
		($r['about'] && $about ? ', ' : '').$r['about'].
		($r['client_id'] && !@$filter['zayav_id'] ? '<div class="refund-client">Клиент: '.$r['client_link'].'</div>' : '');

	return '<span class="type">'._invoice($r['invoice_id']).($about ? ':' : '').'</span> '.$about;
}
function _refund_right() {
	return
		'<div class="f-label">Счета</div>'.
		'<input type="hidden" id="invoice_id" />'.
		'<script>_refundLoad();</script>';
}

/* --- платежи --- */
function income_top($sel) { //Условия поиска сверху для платежей
	$sql = "SELECT DISTINCT `viewer_id_add`
			FROM `_money_income`
			WHERE `app_id`=".APP_ID;
	$worker = query_workerSelJson($sql);

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
					_check('schet', 'платежи по счетам', 0, 1).
					_check('deleted', '+ удалённые платежи', 0, 1).
					_check('deleted_only', 'показать только удалённые', 0, 1).
				'<td id="mi-calendar">'.
					_calendarFilter(array(
						'days' => income_days(),
						'func' => 'income_days',
						'sel' => $sel
					)).
		'</table>'.
		'<script>'.
			'var INCOME_WORKER='.$worker.';'.
			'incomeLoad();'.
		'</script>';
}
function income_days($mon=0) {//отметка дней в календаре, в которые вносились платежи
	$sql = "SELECT DATE_FORMAT(`dtime_add`,'%Y-%m-%d') AS `day`
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `dtime_add` LIKE ('".($mon ? $mon : strftime('%Y-%m'))."%')
			GROUP BY DATE_FORMAT(`dtime_add`,'%d')";
	$q = query($sql);
	$days = array();
	while($r = mysql_fetch_assoc($q))
		$days[$r['day']] = 1;
	return $days;
}
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
//		'<a href="'.URL.'&p=??">Год</a> » '.(YEAR ? '' : '<b>За всё время</b>').
		'Год'.
//		(MON ? '<a href="'.URL.'&p=??&year='.YEAR.'">'.YEAR.'</a> » ' : '<b>'.YEAR.'</b>').
		' » '.YEAR.
		(MON ? ' » '._monthDef(MON, 1) : '').
//		(DAY ? '<a href="'.URL.'&p=??&mon='.YEAR.'-'.MON.'">'._monthDef(MON, 1).'</a> » ' : (MON ? '<b>'._monthDef(MON, 1).'</b>' : '')).
		(DAY ? ' » <b>'.intval(DAY).$to.'</b>' : '').
		'<button class="vk fr" onclick="_incomeAdd()">Внести платёж</button>';
}
function income_invoice_sum($data) {//таблица с суммами платежей по каждому счёту
	$sql = "SELECT
				`invoice_id` `id`,
				COUNT(`id`) `count`,
				SUM(`sum`) `sum`
			FROM `_money_income`
			WHERE ".$data['cond']."
			GROUP BY `invoice_id`
			ORDER BY `invoice_id`";
	if(!$arr = query_arr($sql))
		return '';

	$send = '<table class="_spisokTab w400 mb10">';
	foreach($arr as $r) {
		$send .=
			'<tr class="over1">'.
				'<td class="color-sal">'._invoice($r['id']).
				'<td class="w50 center">'.$r['count'].
				'<td class="w100 r">'._sumSpace($r['sum'], 1);
	}
	$send .=
		'<tr><td class="r"><b>Всего:</b>'.
			'<td class="center"><b>'.$data['all'].'</b>'.
			'<td class="r"><b>'._sumSpace($data['sum'], 1).'</b>';
	$send .= '</table>';

	return $send;
}
function income_day() {
	switch(@RULE_MY_PAY_SHOW_PERIOD) {
		case 1: $period = TODAY; break;             //1 - день
		default: $period = _period(); break;        //2 - неделя
		case 3: $period = substr(TODAY, 0,7); break;//3 - месяц
	}

	$data = income_spisok(array('period'=>$period));
	return
		'<div id="money-income">'.
			income_top($period).
			'<div id="path">'.income_path($period).'</div>'.
			'<div id="spisok">'.$data['spisok'].'</div>'.
		'</div>';
}
function incomeFilter($v) {
	$send = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 50,
		'invoice_id' => _num(@$v['invoice_id']),
		'client_id' => _num(@$v['client_id']),
		'zayav_id' => _num(@$v['zayav_id']),
		'schet' => _bool(@$v['schet']),     //показывать только платежи по счетам на оптату
		'schet_id' => _num(@$v['schet_id']),//платежи по конкретному счёту на оплату
		'worker_id' => _num(@$v['worker_id']),
		'prepay' => _bool(@$v['prepay']),
		'deleted' => _bool(@$v['deleted']),
		'deleted_only' => _bool(@$v['deleted_only']),
		'period' => _period(@$v['period'])
	);
	return $send;
}
function income_spisok($filter=array()) {
	$filter = incomeFilter($filter);
	$filter = _filterJs('INCOME', $filter);

	$cond = "`app_id`=".APP_ID;

	if($filter['invoice_id'])
		$cond .= " AND `invoice_id`=".$filter['invoice_id'];
	if($filter['worker_id'])
		$cond .= " AND `viewer_id_add`=".$filter['worker_id'];
	if($filter['client_id'])
		$cond .= " AND `client_id`=".$filter['client_id'];
	if($filter['zayav_id'])
		$cond .= " AND `zayav_id`=".$filter['zayav_id'];
	if($filter['schet'])
		$cond .= " AND `schet_id`";
	if($filter['schet_id'])
		$cond .= " AND `schet_id`=".$filter['schet_id'];
	if($filter['prepay'])
		$cond .= " AND `prepay`";
	if(!$filter['deleted'])
		$cond .= " AND !`deleted`";
	elseif($filter['deleted_only'])
		$cond .= " AND `deleted`";
	if(!$filter['client_id'] && !$filter['zayav_id'] && !$filter['schet_id'])
		$cond .= _period($filter['period'], 'sql');

	$sql = "SELECT
				COUNT(`id`) AS `all`,
				SUM(`sum`) AS `sum`
			FROM `_money_income`
			WHERE ".$cond;
	$send = query_assoc($sql);

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
	$money = query_arr($sql);

	//$money = _viewer($money);
	$money = _clientValToList($money);
	if(function_exists('_zayavValToList'))
		$money = _zayavValToList($money);
	$money = _schetPayValToList($money);
	$money = _dogovorValToList($money);
	$money = _tovarValToList($money);

	$send['cond'] = $cond;

	$send['spisok'] = $filter['page'] != 1 ? '' :
		$filter['js'].
		income_invoice_sum($send).
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
}
function _income_unit($r, $filter=array()) {
	$prepay = $r['prepay'] ? ' prepay' : '';
	$refund = $r['refund_id'] ? ' ref' : '';
	$confirm = $r['confirm'] == 1;
	$confirmed = $r['confirm'] == 2;
	$deleted = $r['deleted'] ? ' deleted' : '';
	return
		'<tr class="_income-unit'.$prepay.$refund.$deleted.'" val="'._sumSpace($r['sum']).'">'.
			'<td class="sum '.$r['type'].(@$filter['zayav_id'] ? _tooltip('Платёж', 8) : '">')._sumSpace($r['sum']).
			'<td>'.incomeAbout($r, $filter).
			'<td class="dtime">'._dtimeAdd($r).
			'<td class="ed">'.
		  (SA ? '<div onclick="incomeUnbind('.$r['id'].')" class="img_cancel'._tooltip('Отвязать...', -59, 'r').'</div>' : '').
				_incomePrint($r['id']).
				_iconDel($r + array('class'=>'income-del','nodel'=>($confirmed || $refund || $r['dogovor_id']),'del'=>$confirm));
}
function incomeAbout($r, $filter=array()) {
	$about = '';
	if($r['zayav_id'] && !@$filter['zayav_id'])
		$about .= 'Заявка '.@$r['zayav_link'].'. ';

	$about .= $r['tovar_sale'];

	if($r['schet_id'])
		$about .= '<div class="schet">'.$r['schet_pay_income'].' День оплаты: '.FullData($r['schet_paid_day'], 1).'</div>';
	if($r['dogovor_id'])
		$about .= 'Авансовый платёж по договору <u>'.$r['dogovor_nomer'].'</u> от '.$r['dogovor_data'];
	if($r['confirm'] == 1)
		$about .=
			'<button val="'.$r['id'].'#'.$r['invoice_id'].'#'._sumSpace($r['sum']).'#'.FullDataTime($r['dtime_add']).'" class="vk small'._tooltip('Подтвердить поступление на счёт', -67).
				'подтвердить'.
			'</button>'.
			'<div class="confirm">Ожидает подтверждения</div>';
	if($r['confirm'] == 2)
		$about .= '<div class="confirmed">Подтверждён '.FullDataTime($r['confirm_dtime']).'</div>';

	$refund = !@$r['no_refund_show'] && !$r['refund_id'] && !$r['client_id'] && !$r['tovar_id'] ?
			'<a class="refund" val="'.$r['id'].'">возврат</a>'.
			'<input type="hidden" class="refund-dtime" value="'.FullDataTime($r['dtime_add']).'">'
			: '';

	$about .= $refund.
		($r['about'] && $about ? ', ' : '').$r['about'].
		($r['client_id'] && !@$filter['client_id'] && !@$filter['zayav_id'] ? '<div class="income-client">Клиент: '.$r['client_link'].'</div>' : '').
		($r['refund_id'] ? ' <span class="red">Платёж возвращён.</span>' : '');

	return '<span class="type">'._invoice($r['invoice_id']).($about ? ':' : '').'</span> '.$about;
}
function _incomePrint($income_id) {//ссылка-иконка для печати товарного чека
	if(!defined('INCOME_RECEIPT_EXISTS'))
		define('INCOME_RECEIPT_EXISTS', _templateVerify('income-receipt'));

	if(!INCOME_RECEIPT_EXISTS)
		return '';

	return
	'<a onclick="_templatePrint(\'income-receipt\',\'income_id\','.$income_id.')" '.
	   'class="img_doc'._tooltip('Распечатать товарный чек', -155, 'r').
	'</a>';
}
function _incomeReceipt($id) {//товарный чек для платежа
	$sql = "SELECT *
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `id`=".$id;
	$money = query_assoc($sql);

	$zayav = _zayavQuery($money['zayav_id']);

	$sql = "SELECT *
			FROM `_zayav_dogovor`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$money['zayav_id'];
	$dog = query_assoc($sql);

	return
	'<div class="org-name">Общество с ограниченной ответственностью <b>«'._app('name').'»</b></div>'.
	'<div class="cash-rekvisit">'.
		'ИНН '._app('inn').'<br />'.
		'ОГРН '._app('ogrn').'<br />'.
		'КПП '._app('kpp').'<br />'.
		str_replace("\n", '<br />', _app('adres_yur')).'<br />'.
		'<table><tr>'.
			'<td>Тел.: '._app('phone').
			'<th>'.FullData($money['dtime_add']).' г.'.
		'</table>'.
	'</div>'.
	'<div class="head">'.(TEMPLATE_2 ? 'Товарный чек' : 'Квитанция').' №'.$money['id'].'</div>'.
	'<div class="shop">Магазин</div>'.
	'<div class="shop-about">(наименование магазина, структурного подразделения, транспортного средства, и т.д.)</div>'.
	'<table class="tab">'.
		'<tr><th>№<br />п.п.'.
			'<th>Наименование товара'.
			'<th>Количество'.
			'<th>Цена'.
			'<th>Сумма'.
		'<tr><td class="nomer">1'.
			'<td class="about">'.
				($zayav['dogovor_id'] ? 'Оплата по договору №'.$dog['nomer'] : '').
			'<td class="count">1.00'.
			'<td class="sum">'.$money['sum'].
			'<td class="summa">'.$money['sum'].
		'</table>'.
	'<div class="summa-propis">'._numToWord($money['sum'], 1).' рубл'._end($money['sum'], 'ь', 'я', 'ей').'</div>'.
	'<div class="shop-about">(сумма прописью)</div>'.
	'<table class="cash-podpis">'.
		'<tr><td>Продавец ______________________<div class="prod-bot">(подпись)</div>'.
			'<td><u>/'._viewer($money['viewer_id_add'], 'viewer_name_init').'/</u><div class="r-bot">(расшифровка подписи)</div>'.
	'</table>';
}
function income_schet_spisok($schet) {//список платежей по конкретному счёту на оплату
	$sql = "SELECT *
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `schet_id`=".$schet['id']."
			ORDER BY `id` DESC";
	if(!$spisok = query_arr($sql))
		return '<div id="no-pay">По данному счёту платежи не производились.</div>';

	$sum = 0;
	$send = '<table class="_spisok">';
	foreach($spisok as $r) {
		$send .= '<tr>'.
			'<td class="sum">'._sumSpace($r['sum']).
			'<td><span class="type">'._invoice($r['invoice_id']).':</span> день оплаты: '.FullData($r['schet_paid_day'], 1).
			'<td class="dtime">'._dtimeAdd($r);
		$sum += _cena($r['sum']);
	}

	$send .= '</table>';

	$count = count($spisok);
	$diff = $schet['sum'] - $sum;
	return
	'<div>'.
		'Всего <b>'.$count.'</b> плат'._end($count, 'ёж', 'ежа', 'ей').' на сумму <b>'._sumSpace($sum).'</b> руб.'.
		($diff > 0 ?
			'<span class="diff">Недоплачено <b>'._sumSpace($diff).'</b> руб.</span>'
			:
			'<span class="diff full">Оплачено полностью.</span>'
		).
	'</div>'.
	$send;
}

/* --- расходы --- */
function _expense($id=0, $i='name') {//Список категорий расходов
	$key = CACHE_PREFIX.'expense';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					*,
					0 `sub`
				FROM `_money_expense_category`
				WHERE `app_id`=".APP_ID." OR !`app_id`
				ORDER BY `sort`";
		$arr = query_arr($sql);

		//количество подкатегорий
		$sql = "SELECT
					DISTINCT `category_id`,
					COUNT(`id`) `sub`
				FROM `_money_expense_category_sub`
				WHERE `app_id`=".APP_ID."
				GROUP BY `category_id`";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q))
			$arr[$r['category_id']]['sub'] = $r['sub'];

		xcache_set($key, $arr, 86400);
	}

	//все категории
	if(!$id)
		return $arr;

	//список JS для select
	if($id == 'js') {
		$spisok = array();
		foreach($arr as $r)
			$spisok[$r['id']] = $r['name'];
		return _selJson($spisok);
	}

	//неизвестный id категории
	if(!isset($arr[$id]))
		return _cacheErr('неизвестный id категории расходов', $id);

	//данные конкретной категории
	if($i == 'all')
		return $arr[$id];

	//неизвестный ключ категории
	if(!isset($arr[$id][$i]))
		return _cacheErr('неизвестный ключ категории расходов', $i);

	//возврат данных конкретной категории расхода
	return $arr[$id][$i];
}
function _expenseSub($id, $i='name') {//Список подкатегорий расходов
	$key = CACHE_PREFIX.'expense_sub';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT *
				FROM `_money_expense_category_sub`
				WHERE `app_id`=".APP_ID;
		$arr = query_arr($sql);
		xcache_set($key, $arr, 86400);
	}

	//список JS для select
	if($id == 'js') {
		$spisok = array();
		foreach($arr as $r)
			$spisok[$r['category_id']][$r['id']] = $r['name'];

		$js = array();
		foreach($spisok as $uid => $r)
			$js[] = $uid.':'._selJson($r);

		return '{'.implode(',', $js).'}';
	}

	//неизвестный id
	if(!isset($arr[$id]))
		return _cacheErr('неизвестный id подкатегории расходов', $id);

	//неизвестный ключ
	if(!isset($arr[$id][$i]))
		return _cacheErr('неизвестный ключ подкатегории расходов', $i);

	//возврат данных конкретной категории расхода
	return $arr[$id][$i];
}
function _expenseValToList($arr) {//вставка данных расходов организации в массив по expense_id
	$ids = array();
	$arrIds = array();
	foreach($arr as $key => $r)
		if(!empty($r['expense_id'])) {
			$ids[$r['expense_id']] = 1;
			$arrIds[$r['expense_id']][] = $key;
		}
	if(empty($ids))
		return $arr;

	$sql = "SELECT *
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `id` IN (".implode(',', array_keys($ids)).")";
	$spisok = query_arr($sql);

	foreach($spisok as $r) {
		foreach($arrIds[$r['id']] as $id) {
			$arr[$id] += array(
				'expense_sum' => _sumSpace(_cena($r['sum'])),
				'expense_invoice' => '',
				'expense_dtime' => FullDataTime($r['dtime_add'])
			);
		}
	}

	return $arr;

}
function expense() {
	$data = expense_spisok();
	return
		'<script src="/.vkapp/.js/highcharts.js"></script>'.
		'<table class="tabLR" id="money-expense">'.
			'<tr><td class="left">'.
					'<div class="headName">Список расходов организации<a class="add">Внести новый расход</a></div>'.
					'<div id="container"></div>'.
					'<div id="spisok">'.$data['spisok'].'</div>'.
				'<td class="right">'.expense_right().
		'</table>'.

		'<script>'.
			'var ATTACH={},'.
//(VIEWER_ADMIN ? 'GRAF='.expense_graf($data['filter']).',' : '').
				'GRAF='.expense_graf($data['filter']).','.
				'EXPENSE_MON='._selJson(expenseMonthSum()).';'.
			'_expenseLoad();'.
		'</script>';
}
function expenseFilter($v) {
	return array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 50,
		'invoice_id' => _num(@$v['invoice_id']),
		'category_id' => intval(@$v['category_id']),
		'category_sub_id' => _num(@$v['category_sub_id']),
		'year' => _year(@$v['year']),
		'mon' => isset($v['mon']) ? _num($v['mon']) : _num(strftime('%m'))
	);
}
function expense_right() {
	return
		'<div class="f-label">Категория</div>'.
		'<input type="hidden" id="category_id">'.
		'<input type="hidden" id="category_sub_id">'.

		'<div class="findHead">Счёт</div>'.
		'<input type="hidden" id="invoice_id">'.

		'<input type="hidden" id="year">'.
		'<input type="hidden" id="mon" value="'._num(strftime('%m')).'">';
}
function expenseMonthSum($v=array()) {//список чекбоксов с месяцами и суммами расходов по каждому месяцу
	$filter = expenseFilter($v);

	$res = array();
	for($n = 1; $n <= 12; $n++)
		$res[$n] = _monthDef($n);

	$sql = "SELECT
				DISTINCT(DATE_FORMAT(`dtime_add`,'%m')) AS `month`,
				SUM(`sum`) AS `sum`
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `dtime_add` LIKE '".$filter['year']."-%'".
		($filter['invoice_id'] ? " AND `invoice_id`=".$filter['invoice_id'] : '').
		($filter['category_id'] ? " AND `category_id`=".($filter['category_id'] == -1 ? 0 : $filter['category_id']) : '').
		($filter['category_sub_id'] ? " AND `category_sub_id`=".$filter['category_sub_id'] : '')."
			GROUP BY DATE_FORMAT(`dtime_add`,'%m')
			ORDER BY `dtime_add` ASC";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$res[_num($r['month'])] .= '<span>'._sumSpace(round($r['sum'])).'</span>';

	return $res;
}
function expense_graf($filter, $i='json') {//список сумм расходов по категориям
	$sql = "SELECT
				`id`,
				`name`,
				0 `sum`
			FROM `_money_expense_category`
			WHERE `app_id` IN (".APP_ID.",0)
			ORDER BY `sort`";
	$spisok = query_arr($sql);
	$spisok[0] = array(
			'id' => 0,
			'name' => 'Без категории',
			'sum' => 0
	);

	$sql = "SELECT
				DISTINCT `category_id`,
				SUM(`sum`) `sum`
			FROM `_money_expense`
			WHERE ".$filter['cond']."
			GROUP BY `category_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['category_id']]['sum'] = _cena($r['sum']);

	foreach($spisok as $id => $r)
		if(!$r['sum'])
			unset($spisok[$id]);

	if($i == 'json')
		return '{'.
					'index:'._arrJson($spisok, 'id').','.
					'categories:'._arrJson($spisok, 'name').','.
					'sum:'._arrJson($spisok, 'sum').
				'}';

	return array(
		'index' => _arr($spisok, 'id'),
		'categories' => _arr($spisok, 'name'),
		'sum' => _arr($spisok, 'sum')
	);
}
function expense_spisok($v=array()) {
	$filter = expenseFilter($v);
	$filter = _filterJs('EXPENSE', $filter);

	define('PAGE1', $filter['page'] == 1);

	$cond = "`app_id`=".APP_ID." AND !`deleted`";

	if($filter['invoice_id'])
		$cond .= " AND `invoice_id`=".$filter['invoice_id'];
	if($filter['category_id']) {
		$cond .= " AND `category_id`=".($filter['category_id'] == -1 ? 0 : $filter['category_id']);
		if($filter['category_sub_id'])
			$cond .= " AND `category_sub_id`=".$filter['category_sub_id'];
	}
	$cond .= " AND `dtime_add` LIKE '".$filter['year']."-".($filter['mon'] < 10 ? 0 : '').$filter['mon']."-%'";

	$sql = "SELECT
				COUNT(`id`) AS `all`,
				SUM(`sum`) AS `sum`
			FROM `_money_expense`
			WHERE ".$cond;
	$send = query_assoc($sql);

	$filter['cond'] = $cond;
	$send['filter'] = $filter;

	if(!$send['all'])
		return $send + array('spisok' => $filter['js'].'<div class="_empty">Записей нет</div>');

	$all = $send['all'];

	$sql = "SELECT *
			FROM `_money_expense`
			WHERE ".$cond."
			ORDER BY `dtime_add` DESC
			LIMIT "._start($filter).",".$filter['limit'];
	$q = query($sql);
	$expense = array();
	while($r = mysql_fetch_assoc($q))
		$expense[$r['id']] = $r;

	$expense = _viewer($expense);
	$expense = _attachValToList($expense);

	$send['spisok'] = !PAGE1 ? '' :
		$filter['js'].
		'<div id="summa">'.
			'Показан'._end($all, 'а', 'о').' <b>'.$all.'</b> запис'._end($all, 'ь', 'и', 'ей').
			' на сумму <b>'._sumSpace($send['sum']).'</b> руб.'.
		'</div>'.
		'<table class="_spisok" id="tab">'.
			'<tr><th>Сумма'.
				'<th>Описание'.
				'<th>Дата'.
				'<th>';

	foreach($expense as $r) {
		$send['spisok'] .=
			'<tr'.($r['deleted'] ? ' class="deleted"' : '').'>'.
				'<td class="sum"><b>'._sumSpace($r['sum']).'</b>'.
				'<td>'.expenseAbout($r).
				'<td class="dtime">'._dtimeAdd($r).
				'<td class="ed">'.
					($r['category_id'] != 1 ?
						_iconEdit($r).
						_iconDel($r + array('del' => APP_ID == 3495523 ? 1 : 0)) //todo удаление расхода для Купца временно
					: '');
	}

	$send['spisok'] .= _next($filter + array(
			'all' => $all,
			'tr' => 1
		));

	return $send;
}
function expenseAbout($r) {//описание для расходов
	return
		'<span class="type">'._invoice($r['invoice_id']).'</span>: '.
		($r['category_id'] ?
			'<b class="cat">'._expense($r['category_id']).
			($r['category_sub_id'] ? ': '._expenseSub($r['category_sub_id']) : '').
			($r['about'] || $r['worker_id'] ? ': ' : '').'</b>'
		: '').
		($r['worker_id'] ?
			   _viewer($r['worker_id'], 'viewer_link_zp').
				($r['about'] ? ', ' : '')
		: '').
		$r['about'].
		($r['attach_id'] ? '<div>Файл: '.$r['attach_link'].'</div>' : '');
}

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
			'<td><a href="'.URL.'&p=??&year='.$r['year'].'">'.$r['year'].'</a>'.
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
}



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
			'<tr><td class="r"><a href="'.URL.'&p=??&mon='.$year.'-'.$r['mon'].'">'._monthDef($r['mon'], 1).'</a>'.
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
}



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
			'<tr><td class="r"><a href="'.URL.'&p=??&day='.$mon.'-'.$r['day'].'">'.intval($r['day']).'.'.MON.'.'.YEAR.'</a>'.
			'<td class="r"><b>'._sumSpace($r['sum']).'</b>';

	return
		'<div class="headName">Суммы платежей по дням за '._monthDef(MON, 1).' '.YEAR.'</div>'.
		'<div class="inc-path">'.$path.'</div>'.
		'<table class="_spisok sums">'.
		'<tr><th>Месяц'.
		'<th>Всего'.
		implode('', $spisok).
		'</table>';
}





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
	_clientBalansUpdate($v['client_id']);
	_zayavBalansUpdate($v['zayav_id']);

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
}
*/



function _invoice($id=0, $i='name') {//получение списка счетов из кеша
	$key = CACHE_PREFIX.'invoice';
	if(!$arr = xcache_get($key)) {
		$arr = array();
		$sql = "SELECT *
				FROM `_money_invoice`
				WHERE `app_id`=".APP_ID."
				ORDER BY `sort`";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q)) {
			$r['start'] = round($r['start'], 2);

			$r['visible_worker'] = _invoiceWorker($r['visible']);//список сотрудников, которым виден счёт
			$r['visible_ass'] = _invoiceWorkerAss($r['visible']);

			$r['income_insert_worker'] = _invoiceWorker($r['income_insert']);//список сотрудников, которые могут вносить платежи
			$r['income_insert_ass'] = _invoiceWorkerAss($r['income_insert']);

			$r['expense_insert_worker'] = _invoiceWorker($r['expense_insert']);//список сотрудников, которые могут вносить расходы
			$r['expense_insert_ass'] = _invoiceWorkerAss($r['expense_insert']);

			$arr[$r['id']] = $r;
		}

		xcache_set($key, $arr, 86400);
	}

	//все счета
	if(!$id)
		return $arr;

	//список для _select
	if($id == 'js') {
		if(empty($arr))
			return '[]';

		$spisok = array();
		foreach($arr as $r) {
			if($r['deleted'])
				continue;
			$spisok[$r['id']] = $r['name'];
		}

		return _selJson($spisok);
	}

	//получение ids сотрудников, которые могут вносить платежи по доступным счетам для js
	if($id == 'income_insert_js') {
		$spisok = array();

		foreach($arr as $r)
			$spisok[$r['id']] = $r['income_insert_ass'];

		if(!$spisok)
			return '{}';

		return str_replace('"', '', json_encode($spisok));
	}

	//получение ids сотрудников, которые могут вносить платежи по доступным счетам для js
	if($id == 'income_confirm_js') {
		$spisok = array();
		foreach($arr as $r) {
			if($r['deleted'])
				continue;
			if(!$r['income_confirm'])
				continue;
			$spisok[$r['id']] = 1;
		}

		if(!$spisok)
			return '{}';

		return _assJson($spisok);
	}

	//получение ids сотрудников, которые могут вносить расходы по доступным счетам для js
	if($id == 'expense_insert_js') {
		$spisok = array();

		foreach($arr as $r)
			$spisok[$r['id']] = $r['expense_insert_ass'];

		if(!$spisok)
			return '{}';

		return str_replace('"', '', json_encode($spisok));
	}

	//некорректный id счёта
	if(!_num($id))
		_cacheErr('Некорректный id расчётного счёта', $id);

	//проверка существования счёта (для ajax)
	if($i == 'test') {
		if(!isset($arr[$id]))
			return false;
		if($arr[$id]['deleted'])
			return false;
		return true;
	}

	//неизвестный id счёта
	if(!isset($arr[$id]))
		_cacheErr('Неизвестный id расчётного счёта', $id);

	//возврат данных всех счетов
	if($i == 'all')
		return $arr[$id];

	//видимость для текущего сотрудника
	if($i == 'viewer_visible')
		return _bool(@$arr[$id]['visible_ass'][VIEWER_ID]);

	//неизвестный ключ счёта
	if(!isset($arr[$id][$i]))
		return '<span class="red">неизвестный ключ счёта: <b>'.$i.'</b></span>';

	return $arr[$id][$i];
}
function _invoiceWorker($worker_ids) {//получение списка имён сотрудников
	if(!$worker_ids)
		return '';

	$vw = array();//список имён сотрудников
	foreach(explode(',', $worker_ids) as $k)
		$vw[] = _viewer($k, 'viewer_name');

	return implode('<br />', $vw);
}
function _invoiceWorkerAss($worker_ids) {//получение ассоциативного списка id сотрудников
	if(!$worker_ids)
		return array();

	$ass = array();
	foreach(explode(',', $worker_ids) as $k)
		$ass[$k] = 1;

	return $ass;
}
function _invoiceBalans($invoice_id, $start=false) {// Получение текущего баланса счёта
	if($start === false)
		$start = _invoice($invoice_id, 'start');

	//Платежи
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `confirm` NOT IN (1,3)
			  AND !`deleted`
			  AND `invoice_id`=".$invoice_id;
	$income = query_value($sql);

	//Расходы
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `invoice_id`=".$invoice_id;
	$expense = query_value($sql);

	//Возвраты
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_refund`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `invoice_id`=".$invoice_id;
	$refund = query_value($sql);

	//перевод: счёт-отправитель
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_invoice_transfer`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `invoice_id_from`=".$invoice_id;
	$from = query_value($sql);

	//перевод: счёт-получатель
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_invoice_transfer`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `invoice_id_to`=".$invoice_id;
	$to = query_value($sql);

	//Внесение денег на счёт
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_invoice_in`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `invoice_id`=".$invoice_id;
	$in = query_value($sql);

	//Снятие денег со счёта
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_invoice_out`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `invoice_id`=".$invoice_id;
	$out = query_value($sql);

	return round($income - $expense - $refund - $from + $to + $in - $out - $start, 2);
}

function invoice() {//страница со списком счетов и переводами между счетами
	return
	'<script>'.
		'var RULE_SETUP_INVOICE='.RULE_SETUP_INVOICE.','.
			'RULE_INVOICE_TRANSFER='.RULE_INVOICE_TRANSFER.';'.
	'</script>'.
	'<div id="money-invoice">'.

	(_invoiceTransferConfirmCount() ?
		'<div class="_info">'.
			'Есть переводы между счетами, ожидающие подтверждения: <b>'._invoiceTransferConfirmCount().'</b>. '.
		'</div>'
	: '').

		'<input type="hidden" id="invoice_menu" value="1" />'.

		'<div class="invoice_menu-1">'.
		(RULE_SETUP_INVOICE ?
			'<button class="vk" onclick="_invoiceEdit()">Создать новый счёт</button>'
		: '').
			'<div id="invoice-spisok">'.invoice_spisok().'</div>'.
		'</div>'.

	(RULE_INVOICE_TRANSFER ?
		'<div class="invoice_menu-2 dn">'.
			'<button class="vk" onclick="_invoiceTransfer()">Выполнить перевод</button>'.
			'<div id="transfer-spisok">'.invoice_transfer_spisok().'</div>'.
		'</div>'.

		'<div class="invoice_menu-3 dn">'.
			'<button class="vk mr5" onclick="_invoiceIn()">Внести деньги на счёт</button>'.
			'<button class="vk" onclick="_invoiceOut()">Вывести деньги со счёта</button>'.
			'<div id="inout-spisok">'.invoice_inout_spisok().'</div>'.
		'</div>'
	: '').

	'</div>';
}
function invoice_spisok() {
	if(!_invoice())
		return 'Счета не определены.';

	$def = _viewer(VIEWER_ID, 'invoice_id_default');

	$send = '<dl'.(RULE_SETUP_INVOICE ? ' class="_sort" val="_money_invoice"' : '').'>';
	foreach(_invoice() as $r) {
		if($r['deleted'])
			continue;
		if(!RULE_SETUP_INVOICE && !_invoice($r['id'], 'viewer_visible'))
			continue;

		$send .= '<dd val="'.$r['id'].'">'.
		'<table class="_spisok">'.
			'<tr>'.
				'<td class="name'.(RULE_SETUP_INVOICE ? ' move' : '').'">'.
					'<b>'.$r['name'].'</b>'.
					'<div class="about">'.$r['about'].'</div>'.
(RULE_SETUP_INVOICE && $r['income_confirm'] ? '<h6>Подтверждение поступления на счёт</h6>' : '').
(RULE_SETUP_INVOICE && $r['transfer_confirm'] ? '<h6>Подтверждение переводов</h6>' : '').
(RULE_SETUP_INVOICE ?
				'<td class="worker">'.
					($r['visible'] ? '<h4>Видимость для сотрудников:</h4><h5>'.$r['visible_worker'].'<h5>' : '').
					($r['income_insert'] ? '<h4>Могут вносить платежи:</h4><h5>'.$r['income_insert_worker'].'<h5>' : '').
					($r['expense_insert'] ? '<h4>Могут вносить расходы:</h4><h5>'.$r['expense_insert_worker'].'<h5>' : '')
: '').
				'<td class="balans"><b>'.($r['start'] != -1 ? _sumSpace(_invoiceBalans($r['id'])).'</b> руб.' : '').
				'<td class="ed">'.
					'<span class="'._tooltip('Использовать по умолчанию', -162, 'r')._check('def'.$r['id'], '', $def == $r['id'] ? 1 : 0).'</span>'.
					'<div val="'.$r['id'].'" class="img_setup'._tooltip('Выполнить операцию над счётом', -195, 'r').'</div>'.
(RULE_INVOICE_HISTORY ?
					'<div onclick="_balansShow(1,'.$r['id'].')" class="img_note'._tooltip('Посмотреть историю операций', -176, 'r').'</div>'
: '').

(RULE_SETUP_INVOICE ?
				'<input type="hidden" class="visible" value="'.$r['visible'].'" />'.
				'<input type="hidden" class="income_confirm" value="'.$r['income_confirm'].'" />'.
				'<input type="hidden" class="transfer_confirm" value="'.$r['transfer_confirm'].'" />'.
				'<input type="hidden" class="income_insert" value="'.$r['income_insert'].'" />'.
				'<input type="hidden" class="expense_insert" value="'.$r['expense_insert'].'" />'
: '').
		'</table>';
	}
	$send .= '</dl>';
	return $send;
}
function _invoiceTransferConfirmCount($plus_b=0) { //Получение количества переводов по счетам, которые необходимо подтвердить
	if(!VIEWER_ADMIN)
		return $plus_b ? '' : 0;

	if(defined('INVOICE_TRANSFER_CONFIRM_COUNT')) {
		if($plus_b)
			return INVOICE_TRANSFER_CONFIRM_COUNT ? ' <b>+'.INVOICE_TRANSFER_CONFIRM_COUNT.'</b>' : '';
		return INVOICE_TRANSFER_CONFIRM_COUNT;
	}

	$sql = "SELECT COUNT(`id`)
			FROM `_money_invoice_transfer`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `confirm`=1";
	define('INVOICE_TRANSFER_CONFIRM_COUNT', query_value($sql));

	return _invoiceTransferConfirmCount($plus_b);
}
function invoice_transfer_spisok($v=array()) {//история переводов по счетам
	$filter = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 30
	);

	$cond = "`app_id`=".APP_ID."
		 AND !`deleted`";

	if(RULE_INVOICE_TRANSFER == 1)
		$cond .= " AND `viewer_id_add`=".VIEWER_ID;

	$sql = "SELECT COUNT(*) FROM `_money_invoice_transfer` WHERE ".$cond;
	if(!$all = query_value($sql))
		return 'Переводов нет.';

	$sql = "SELECT *
	        FROM `_money_invoice_transfer`
	        WHERE ".$cond."
	        ORDER BY `id` DESC
	        LIMIT "._start($filter).",".$filter['limit'];
	$q = query($sql);
	$send = $filter['page'] != 1 ? '' :
		'<div class="mt10">Всего '.$all.' перевод'._end($all, '', 'а', 'ов').'.</div>'.
		'<table class="_spisok mt5">'.
			'<tr>'.
				'<th>Cумма'.
				'<th>Со счёта'.
				'<th>На счёт'.
				'<th>Комментарий'.
				'<th>Дата'.
				'<th>';
	while($r = mysql_fetch_assoc($q)) {
		$confirm = '';
		if($r['confirm']) {
			$class = '';
			$ne = '';
			$button = '';
			if($r['confirm'] == 1) {
				$class = ' no';
				$ne = 'не ';
				$button =
				(VIEWER_ADMIN ?
					'<button val="'.$r['id'].
								'#'.$r['invoice_id_from'].
								'#'.$r['invoice_id_to'].
								'#'._sumSpace($r['sum']).
								'#'.FullDataTime($r['dtime_add']).'" '.
							'class="vk small fr'._tooltip('Подтвердить перевод', -35).
						'подтвердить'.
					'</button>'
				: '');
			}
			$confirm = $r['about'] ? '<br />' : '';
			$confirm .= '<span class="confirm'.$class.'">'.$ne.'подтверждено</span>'.$button;
		}
		$val =  $r['id'].'###'.
				$r['invoice_id_from'].'###'.
				$r['invoice_id_to'].'###'.
				_cena($r['sum']).'###'.
				addslashes($r['about']);
		$send .=
			'<tr val="'.$val.'">'.
				'<td class="sum">'._sumSpace($r['sum']).
				'<td><span class="type">'._invoice($r['invoice_id_from']).'</span>'.
				'<td><span class="type">'._invoice($r['invoice_id_to']).'</span>'.
				'<td class="about">'.$r['about'].$confirm.
				'<td class="dtime">'._dtimeAdd($r).
				'<td class="ed">'._iconEdit($r)._iconDel($r);
	}

	$send .= _next($filter + array(
			'all' => $all,
			'tr' => 1
		));

	return $send;
}
function invoice_inout_spisok($v=array()) {
	if(RULE_INVOICE_TRANSFER < 2)
		return '';

	$filter = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 30
	);

	$cond = "`app_id`=".APP_ID."
		 AND !`deleted`";

	$sql = "SELECT COUNT(*)	FROM `_money_invoice_in` WHERE ".$cond;
	$all = query_value($sql);

	$sql = "SELECT COUNT(*)	FROM `_money_invoice_out` WHERE ".$cond;
	$all += query_value($sql);

	if(!$all)
		return 'Записей нет.';


	$sql = "(
				SELECT
					'in' `action`,
					`id`,
					`invoice_id`,
					`sum`,
					0 `worker_id`,
					`about`,
					`viewer_id_add`,
					`dtime_add`
				FROM `_money_invoice_in`
				WHERE ".$cond."
			) UNION (
				SELECT
					'out' `action`,
					`id`,
					`invoice_id`,
					`sum`,
					`worker_id`,
					`about`,
					`viewer_id_add`,
					`dtime_add`
				FROM `_money_invoice_out`
				WHERE ".$cond."
			)
			ORDER BY `dtime_add` DESC";
	$q = query($sql);

	$send = $filter['page'] != 1 ? '' :
		'<div class="mt10">Всего '.$all.' запис'._end($all, 'ь', 'и', 'ей').'.</div>'.
		'<table class="_spisok mt5">'.
			'<tr>'.
				'<th>Действие'.
				'<th>Cумма'.
				'<th>Счёт'.
				'<th>Комментарий'.
				'<th>Дата'.
				'<th>';

	$action = array(
		'in' => 'Внесение',
		'out' => 'Вывод'
	);

	while($r = mysql_fetch_assoc($q)) {
		$worker = $r['worker_id'] ? '<div>Получатель: <u>'._viewer($r['worker_id'], 'viewer_name').'</u></div>' : '';
		$send .=
			'<tr class="'.$r['action'].'">'.
				'<td class="action">'.$action[$r['action']].
				'<td class="sum">'._sumSpace($r['sum']).
				'<td class="type">'._invoice($r['invoice_id']).
				'<td class="about">'.$worker.$r['about'].
				'<td class="dtime">'._dtimeAdd($r).
				'<td class="ed">'._iconDel($r);
	}

	$send .= _next($filter + array(
			'all' => $all,
			'tr' => 1
		));

	return $send;
}

function _balans($v) {//внесение записи о балансе
	$app_id = _num(@$v['app_id']) ? _num($v['app_id']) : APP_ID;
	$category_id = _balansAction($v['action_id'], 'category_id');
	$unit_id = 0;
	$balans = 0;
	$sum = _cena(@$v['sum'], 1);
	$sum_old = _cena(@$v['sum_old'], 1);
	$invoice_transfer_id = _num(@$v['invoice_transfer_id']);

	if(_balansAction($v['action_id'], 'minus'))
		$sum *= -1;

	//расчётный счёт
	if(!empty($v['invoice_id'])) {
		$unit_id = _num($v['invoice_id']);
		$balans = 0;
		if($v['action_id'] != 15)//если не закрытие счёта
			$balans = _invoiceBalans($unit_id);


		// изменение знака суммы у счёта-отправителя
		if($invoice_transfer_id) {
			$sql = "SELECT * FROM `_money_invoice_transfer` WHERE `id`=".$invoice_transfer_id;
			$r = query_assoc($sql);
			if($unit_id == $r['invoice_id_from'])
				$sum *= -1;
		}
	}

	//клиент
	if(!empty($v['client_id'])) {
		$unit_id = _num($v['client_id']);
		$balans = _clientBalansUpdate($unit_id);
	}

	//сотрудник
	if(!empty($v['worker_id'])) {
		$unit_id = _num($v['worker_id']);
		$balans = salaryWorkerBalans($unit_id, 0);
	}


	$sql = "INSERT INTO `_balans` (
				`app_id`,

				`category_id`,
				`unit_id`,
				`action_id`,
				`sum`,
				`sum_old`,
				`balans`,
				`about`,

				`income_id`,
				`expense_id`,
				`invoice_transfer_id`,
				`schet_id`,
				`zayav_id`,
				`dogovor_id`,

				`viewer_id_add`
			) VALUES (
				".$app_id.",

				".$category_id.",
				".$unit_id.",
				".$v['action_id'].",
				".$sum.",
				".$sum_old.",
				".$balans.",
				'".addslashes(@$v['about'])."',

				"._num(@$v['income_id']).",
				"._num(@$v['expense_id']).",
				".$invoice_transfer_id.",
				"._num(@$v['schet_id']).",
				"._num(@$v['zayav_id']).",
				"._num(@$v['dogovor_id']).",

				".VIEWER_ID."
			)";
	query($sql);
}
function _balansAction($id, $i='name') {//получение списка названий действий по счетам из кеша
	$key = CACHE_PREFIX.'balans_action';
	$arr = xcache_get($key);
	if(empty($arr)) {
		$sql = "SELECT * FROM `_balans_action`";
		$arr = query_arr($sql);
		xcache_set($key, $arr, 86400);
	}

	if(!isset($arr[$id]))
		return '<span class="red">неизвестное действие баланса: <b>'.$id.'</b></span>';

	if(!isset($arr[$id][$i]))
		return '<span class="red">неизвестный ключ действия баланса: <b>'.$i.'</b></span>';

	return $arr[$id][$i];
}

function balansFilter($v) {
	return array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 50,
		'category_id' => _num(@$v['category_id']),
		'unit_id' => _num(@$v['unit_id']),
		'podrobno_day' => @$v['podrobno_day'],
		'everyday_year' => _num(@$v['everyday_year']) ? $v['everyday_year'] : strftime('%Y'),
		'everyday_mon' => _num(@$v['everyday_mon']) ? (_num($v['everyday_mon']) < 10 ? '0' : '')._num($v['everyday_mon']) : strftime('%m')
	);
}
function balans_show($v) {//вывод таблицы с балансами конкретного счёта
	$filter = balansFilter($v);

	$r = balans_show_category($filter);
	if(!empty($r['error']))
		return $r['about'];

	$data = balans_show_spisok($filter);

	//список активных годов
	$sql = "SELECT
				DISTINCT DATE_FORMAT(`dtime_add`,'%Y'),
				DATE_FORMAT(`dtime_add`,'%Y')
			FROM `_balans`
			WHERE `app_id`=".APP_ID."
			  AND `category_id`=".$filter['category_id']."
			  AND `unit_id`=".$filter['unit_id']."
			ORDER BY `dtime_add`";
	$yearSpisok = query_selJson($sql);

	return
	'<script>var YEAR_SPISOK='.$yearSpisok.';</script>'.
	'<div id="balans-show">'.
		'<div class="hd1">'.
			$r['type'].' <u>'.$r['head'].'</u>'.
			'<div class="fr curD'._tooltip('Текущий баланс', -53).'<b>'.$r['balans'].'</b> руб.</div>'.
		'</div>'.
		'<div class="mar8">'.
			(@$r['about'] ? '<div class="_info">'.$r['about'].'</div>' : '').

			'<input type="hidden" id="menu_id" value="1" />'.

			'<div class="menu_id-1">'.$data['spisok'].'</div>'.
			'<div class="menu_id-2 dn">'.
				'<input type="hidden" id="menu_year" value="'.strftime('%Y').'" />'.
				'<input type="hidden" id="menu_mon" value="'._num(strftime('%m')).'" />'.
				'<div id="spisok2">'.balans_everyday($filter).'</div>'.
			'</div>'.
		'</div>'.
	'</div>';
}
function balans_show_category($v) {
	switch($v['category_id']) {
		case 1: //расчётные счета
			$sql = "SELECT *
					FROM `_money_invoice`
					WHERE `app_id`=".APP_ID."
					  AND `id`=".$v['unit_id'];
			if(!$r = query_assoc($sql))
				return array(
					'error' => 1,
					'about' => _err('Счёта id:<b>'.$v['unit_id'].'</b> не существует.')
				);
			return array(
				'type' => 'Счёт',
				'head' => $r['name'],
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
				'type' => 'Клиент',
				'head' => $r['name'],
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
				'type' => 'Сотрудник',
				'head' => _viewer($v['unit_id'], 'viewer_name'),
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
}
function balans_show_spisok($filter) {
	$filter = _filterJs('BALANS', $filter);
	define('PAGE1', $filter['page'] == 1);

	$cond = "`app_id`=".APP_ID."
		 AND `category_id`=".$filter['category_id']."
		 AND `unit_id`=".$filter['unit_id'];

	$dayInfo = '';
	if($filter['podrobno_day']) {
		$cond .= " AND `dtime_add` LIKE '".$filter['podrobno_day']."%'";

		//остаток на конец дня
		$sql = "SELECT `balans`
				FROM `_balans`
				WHERE ".$cond."
				ORDER BY `id` DESC
				LIMIT 1";
		$sumEnd = query_value($sql);

		//остаток на начало дня
		$sql = "SELECT SUM(`sum`)
				FROM `_balans`
				WHERE ".$cond;
		$sumStart = $sumEnd - query_value($sql);

		//список действий
		$action = '';
		$sql = "SELECT
					DISTINCT `action_id`,
					COUNT(`id`) `count`,
					SUM(`sum`) `sum`
				FROM `_balans`
				WHERE ".$cond."
				GROUP BY `action_id`";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q))
			$action .=
				'<tr><td>'._balansAction($r['action_id']).':'.
					'<td class="w50 center">'.$r['count'].
					'<td class="w70 r">'._sumSpace($r['sum']);

		$dayInfo =
		'<table class="bs10">'.
			'<tr><td class="day-hd topi b r">'.
					FullData($filter['podrobno_day'], 0, 0, 1).':'.
				'<td>'.
					'<table class="_spisok">'.
						'<tr class="grey"><td colspan="2">Начало дня:<td class="r">'._sumSpace($sumStart).
						$action.
						'<tr class="grey"><td colspan="2">Конец дня:<td class="r">'._sumSpace($sumEnd).
					'</table>'.
				'<td class="top">'.
					'<button class="vk small red day-clear">отменить выбор</button>'.
		'</table>';
	}

	$send = array(
		'all' => 0,
		'spisok' => $filter['js'].'<div class="_empty">Истории нет.</div>',
		'filter' => $filter
	);

	$sql = "SELECT COUNT(`id`) FROM `_balans` WHERE ".$cond;
	if(!$all = query_value($sql))
		return $send;

	$sql = "SELECT *
			FROM `_balans`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT "._start($filter).",".$filter['limit'];
	$spisok = query_arr($sql);

	$spisok = _schetPayValToList($spisok);
	$spisok = _zayavValToList($spisok);
	$spisok = _dogovorValToList($spisok);

	$transfer = array();
	if($transfer_ids = _idsGet($spisok, 'invoice_transfer_id')) {
		$sql = "SELECT * FROM `_money_invoice_transfer` WHERE `id` IN (".$transfer_ids.")";
		$transfer = query_arr($sql);
	}

	$income = array();
	if($income_ids = _idsGet($spisok, 'income_id')) {
		$sql = "SELECT * FROM `_money_income` WHERE `id` IN (".$income_ids.")";
		$income = query_arr($sql);
		$income = _clientValToList($income);
		$income = _zayavValToList($income);
		$income = _dogovorValToList($income);
		$income = _schetPayValToList($income);
		$income = _tovarValToList($income);
	}

	$expense = array();
	if($expense_ids = _idsGet($spisok, 'expense_id')) {
		$sql = "SELECT * FROM `_money_expense` WHERE `id` IN (".$expense_ids.")";
		$expense = query_arr($sql);
		$expense = _viewer($expense);
		$expense = _attachValToList($expense);
	}

	$send['spisok'] = !PAGE1 ? '' :
		$filter['js'].
		$dayInfo.
		'<table class="_spisok" id="balans-tab">'.
			'<tr><th>Действие'.
				'<th>Сумма'.
				'<th>Остаток'.
				'<th>Описание'.
				'<th>Дата';

	foreach($spisok as $r) {
		$sum = _sumSpace($r['sum']);
		$sum_diff = '';
		if(round($r['sum_old'], 2)) {//если сумма изменялась
			$sum = ($r['sum'] - $r['sum_old'] > 0  ? '+' : '')._sumSpace($r['sum'] - $r['sum_old']);
			$sum_diff = '<div class="diff">'.round($r['sum_old'], 2).' &rarr; '.round($r['sum'], 2).'</div>';
		}
		$sum = $sum ? $sum : '';


		$about = @$r['schet_link_full'];

		if($r['zayav_id'])
			$about .= 'Заявка '.$r['zayav_link'].'. ';

		if($r['dogovor_id'])
			$about .= 'Договор '.$r['dogovor_nomer'].'. ';

		$about .= $r['about'];

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
			$income[$r['income_id']]['no_refund_show'] = 1;//не отображать ссылку "возврат"
			$about = incomeAbout($income[$r['income_id']]);
		}

		// описание для расходов
		if($r['expense_id'])
			$about = expenseAbout($expense[$r['expense_id']]);

		$balans = _sumSpace($r['balans']);
		if($r['action_id'] == 15)
			$balans = '';

		$send['spisok'] .=
			'<tr><td class="action'.($r['sum'] < 0 ? ' minus' : '').'">'._balansAction($r['action_id']).
				'<td class="sum">'.$sum.$sum_diff.
				'<td class="balans">'.$balans.
				'<td>'.$about.
				'<td class="dtime">'._dtimeAdd($r);
	}

	$send['spisok'] .=
		_next($filter + array(
			'all' => $all,
			'tr' => 1
		));

	return $send;
}
function balans_everyday($v) {//отображение баланса за каждый день
	$v = balansFilter($v);

	define('MONTH', $v['everyday_year'].'-'.$v['everyday_mon']);

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
				  AND `category_id`=".$v['category_id']."
				  AND `unit_id`=".$v['unit_id']."
				  AND `dtime_add` LIKE '".MONTH."%'
				GROUP BY DATE_FORMAT(`dtime_add`,'%d')
				ORDER BY `id`
			)";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$ass[intval($r['day'])]['balans'] = round($r['balans'], 2);

	// суммы приходов
	$sql = "SELECT
				DATE_FORMAT(`dtime_add`,'%d') AS `day`,
				SUM(`sum`) AS `sum`
			FROM `_balans`
			WHERE `app_id`=".APP_ID."
			  AND `category_id`=".$v['category_id']."
			  AND `unit_id`=".$v['unit_id']."
			  AND `sum`>0
			  AND `dtime_add` LIKE '".MONTH."%'
			GROUP BY DATE_FORMAT(`dtime_add`,'%d')
			ORDER BY `id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$ass[intval($r['day'])]['inc'] = round($r['sum'], 2);

	// суммы расходов
	$sql = "SELECT
				DATE_FORMAT(`dtime_add`,'%d') AS `day`,
				SUM(`sum`) AS `sum`
			FROM `_balans`
			WHERE `app_id`=".APP_ID."
			  AND `category_id`=".$v['category_id']."
			  AND `unit_id`=".$v['unit_id']."
			  AND `sum`<0
			  AND `dtime_add` LIKE '".MONTH."%'
			GROUP BY DATE_FORMAT(`dtime_add`,'%d')
			ORDER BY `id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$ass[intval($r['day'])]['dec'] = round($r['sum'], 2);

	$unix = strtotime(MONTH.'-01');
	$prev_month = strftime('%Y-%m', $unix - 86400);

	// баланс за последний день
	$sql = "SELECT `balans`
			FROM `_balans`
			WHERE `app_id`=".APP_ID."
			  AND `category_id`=".$v['category_id']."
			  AND `unit_id`=".$v['unit_id']."
			  AND `dtime_add` LIKE '".$prev_month."%'
			ORDER BY `id` DESC
			LIMIT 1";
	$balans = query_value($sql);

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
				'<td class="day wsnw">'.
					(isset($ass[$d]) ?
						'<a class="podrobno" val="'.MONTH.'-'.($d < 10 ? 0 : '').$d.'">'.$day.'</a>'
					: $day).
				'<td class="start">'._sumSpace($start).
				'<td class="inc">'.($inc ? _sumSpace($inc) : '').
				'<td class="dec">'.($dec ? _sumSpace($dec) : '').
				'<td class="diff '.($diff > 0 ? 'inc' : 'dec').'">'.($diff ? _sumSpace($diff) : '').
				'<td class="ost">'._sumSpace($balans);
	}

	$send .= '</table>';
	return $send;
}








/* --- Счета на оплату --- */

function _schetPayQuery($id, $withDel=0) {//запрос данных о счёте на оплату
	$sql = "SELECT *
			FROM `_schet_pay`
			WHERE `app_id`=".APP_ID.
			  ($withDel ? '' : ' AND !`deleted`')."
			  AND `id`=".$id;
	if(!$schet = query_assoc($sql))
		return false;
	
	$sql = "SELECT *
			FROM `_schet_pay_content`
			WHERE `schet_id`=".$id."
			ORDER BY `id`";
	$schet['content'] = query_arr($sql);
	
	return $schet;
}
function _schetPayTypeSelect($show) {//вывод выбора вида счёта (для нового счёта)
	if(!$show)
		return '';

	$type = array(
		1 => '<b>Счёт на оплату</b>'.
			 '<div class="grey ml20 fs12 mt5 mb20">'.
				'Будет создан новый счёт на оплату, также произведено начисление клиенту.'.
			'</div>',

		2 => '<b>Ознакомительный счёт</b>'.
			 '<div class="grey ml20 fs12 mt5">'.
				'Счёт будет создан и сохранён как документ для ознакомления.'.
				'<br />'.
				'Порядковый номер присвоен не будет.'.
				'<br />'.
				'Начисление клиенту производиться <b>не будет</b>.'.
				'<br />'.
				'По данному счёту невозможно будет производить платежи.'.
				'<br />'.
				'В дальнейшем этот счёт можно будет перевести "на оплату".'.
			'</div>'
	);

	return
		'<div id="schet-pay-type-select">'.
			'<div class="hd2">Укажите вид создаваемого счёта:</div>'.
			'<div class="mar20">'.
				_radio('schet-pay-type', $type).
			'</div>'.
		'</div>';
}
function _schetPayValToList($arr, $key='schet_id') {//вставка данных счетов на оплату в массив
	if(empty($arr))
		return array();

	foreach($arr as $r)
		$arr[$r['id']] += array(
			'schet_pay_nomer' => '',
			'schet_pay_acc' => '',
			'schet_pay_income' => ''
		);

	if(!$schet_ids = _idsGet($arr, $key))
		return $arr;

	$sql = "SELECT *
			FROM `_schet_pay`
			WHERE `app_id`=".APP_ID."
			  AND `id` IN (".$schet_ids.")";
	if(!$schet = query_arr($sql))
		return $arr;


	foreach($arr as $id => $r) {
		if(!$schet_id = _num(@$r[$key]))
			continue;

		$c = $schet[$r[$key]];

		$arr[$id] = array(
			'schet_pay_nomer' => $c['prefix'].$c['nomer'],
			//для начисления
			'schet_pay_acc' => '<div class="schet-pay-acc '._schetPayStatusBg($c).'" onclick="schetPayShow('.$c['id'].')">'.
									'Счёт на оплату '.
									'№ <b>'.$c['prefix'].$c['nomer'].'</b> '.
								'</div>',
			//для платежей, заявок, истории действий
			'schet_pay_income' => '<div class="schet-pay-income '._schetPayStatusBg($c).'" onclick="schetPayShow('.$c['id'].')">'.
									$c['prefix'].$c['nomer'].
								'</div>'
		) + $arr[$id];
	}
	return $arr;
}
function _schetPayToZayav($zayav) {//подстановка списка счетов в элемент списка заявок
	if(empty($zayav))
		return array();

	$sql = "SELECT *
			FROM `_schet_pay`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `zayav_id` IN (".implode(',', array_keys($zayav)).")";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$zayav[$r['zayav_id']]['schet'] .=
			'<div class="schet-pay-income '._schetPayStatusBg($r).'" onclick="schetPayShow('.$r['id'].',0,event)">'.
				$r['prefix'].$r['nomer'].
			'</div> ';

	return $zayav;
}
function _schetPaySumCorrect($schet_id) {//поправка в счёте суммы платежей, которые были произведены при внесении или удалении платежа
	if(empty($schet_id))
		return;

	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `schet_id`=".$schet_id;
	$sum = query_value($sql);

	$sql = "UPDATE `_schet_pay`
			SET `sum_paid`=".$sum."
			WHERE `id`=".$schet_id;
	query($sql);
}
function _schetPayFilter($v) {
	$default = array(
		'page' => 1,
		'limit' => 100,
		'find' => '',
		'group_id' => 0,
		'mon' => '',
		'client_id' => 0,
		'zayav_id' => 0
	);
	$filter = array(
		'page' => _num(@$v['page']) ? $v['page'] : $default['page'],
		'limit' => _num(@$v['limit']) ? $v['limit'] : $default['limit'],
		'find' => trim(@$v['find']),
		'group_id' => _num(@$v['group_id']),//группы счетов: переданы, не переданы, оплачены, не оплачены
		'mon' => _txt(@$v['mon']),
		'client_id' => _num(@$v['client_id']),
		'zayav_id' => _num(@$v['zayav_id']),
		'clear' => ''
	);
	foreach($default as $k => $r)
		if($r != $filter[$k]) {
			$filter['clear'] = '<button class="vk small red fr" onclick="schetPayFilterClear()">Очистить фильтр</button>';
			break;
		}
	return $filter;
}
function _schetPay() {//страница счетов на оплату
	$v = _hashFilter('schet_pay');
	$data = _schetPay_spisok($v);
	$v = $data['filter'];
	$hist = _history(array('category_id'=>7,'limit'=>20));

	return
	'<div class="mar10">'.
		'<input type="hidden" id="schet-pay-menu" value="1" />'.
		'<div class="schet-pay-menu-1">'.
			'<table class="w100p">'.
				'<tr>'.
					'<td class="w400"><div id="find"></div>'.
					'<td id="td-group"><input type="hidden" id="group_id" value="'.$v['group_id'].'" />'.
					'<td class="r">'.
						'<button class="vk" onclick="schetPayEdit()">Создать счёт на оплату</button>'.
						'<a href="'.URL.'&p=28" class="icon icon-setup-big mt5 ml10 mr5'._tooltip('Настроить счета на оплату', -154, 'r').'</a>'.
			'</table>'.

   (DEBUG ? '<div class="mt10">'.
				'<button class="vk red" onclick="schetPayAllRemove($(this))">SA: удалить все счета на оплату</button>'.
			'</div>'
	 : '').

			'<div class="mt10" id="schet-pay-spisok">'.$data['spisok'].'</div>'.
		'</div>'.
		'<div class="schet-pay-menu-2 dn">'.$hist['spisok'].'</div>'.
		'<div class="schet-pay-menu-3 dn">'._schetPay_stat().'</div>'.
	'</div>';
}
function _schetPay_spisok($v=array()) {
	$filter = _schetPayFilter($v);
	$filter = _filterJs('SCHET_PAY', $filter);

	$cond = "`app_id`=".APP_ID."
		 AND !`deleted`";

	define('FIND_NOMER', _num($filter['find']));
	define('FIND_SUM', _cena($filter['find']));
	$contentIds = '';   //ids счетов, выбранных в содержании при быстром поиске
	$yearMon = '';      //название месяца и года, если указана дата

	if($filter['find']) {
		//быстрый поиск по содержанию
		$sql = "SELECT DISTINCT `schet_id`
				FROM `_schet_pay_content`
				WHERE `app_id`=".APP_ID."
				  AND `name` LIKE '%".$filter['find']."%'";
		$contentIds = query_ids($sql);

		$cond .= " AND (`nomer`=".FIND_NOMER.
						(FIND_SUM ? " OR `sum`=".FIND_SUM : '').
						($contentIds ? " OR `id` IN (".$contentIds.")" : '').
						" OR `client_name` LIKE '%".$filter['find']."%'".
					  ")";
	} else {
		switch ($filter['group_id']) {
			case 2: $cond .= " AND `type_id`=2"; break;                                 //ознакомительные
			case 3: $cond .= " AND `type_id`=1 AND !`pass` AND `sum_paid`<`sum`"; break;//не переданы
			case 4:	$cond .= " AND `type_id`=1 AND `sum_paid`<`sum`"; break;            //не оплачены
			case 5: $cond .= " AND `type_id`=1 AND `pass` AND `sum_paid`<`sum`"; break; //переданы, не оплачены
			case 6: $cond .= " AND `sum_paid`>=`sum`"; break;                           //оплачены
		}
		if($filter['mon']) {
			$cond .= " AND `date_create` LIKE '".$filter['mon']."%'";
			$ex = explode('-', $filter['mon']);
			$yearMon = ' за <span class="pad5 bg-ddf">'._monthDef($ex[1], 1).' '.$ex[0].'</span>';
		}
	}

	$sql = "SELECT
				COUNT(`id`) `all`,
				SUM(`sum_paid`) `paid`,
				SUM(`sum`) `sum`
			FROM `_schet_pay`
			WHERE ".$cond;
	$send = query_assoc($sql);
	$send['filter'] = $filter;
	if(!$send['all'])
		return $send + array('spisok' => $filter['js'].'<div class="_empty">Счета не найдены</div>');

	$all = $send['all'];
	$filter['all'] = $all;
	$nopaid = $send['sum'] - $send['paid'];

	$schet = array();

	//Установка счёта на первое место, у которого совпал номер с быстрым поиском
	if($filter['page'] == 1 && FIND_NOMER) {
		$sql = "SELECT *
				FROM `_schet_pay`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `nomer`=".FIND_NOMER."
				LIMIT 1";
		if($r = query_assoc($sql)) {
			$r['content'] = '';
			$schet[$r['id']] = $r;
		}
	}

	$sql = "SELECT
				*,
				'' `content`
			FROM `_schet_pay`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT "._start($filter).",".$filter['limit'];
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		if($r['nomer'] == FIND_NOMER)
			continue;
		$schet[$r['id']] = $r;
	}

	//вставка найденного содержимого быстрого поиска
	if($contentIds) {
		$reg = '/('.$filter['find'].')/iu';
		$reg = utf8($reg);
		$sql = "SELECT *
				FROM `_schet_pay_content`
				WHERE `app_id`=".APP_ID."
				  AND `schet_id` IN (".$contentIds.")
				  AND `name` LIKE '%".$filter['find']."%'";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q)) {
			if(!isset($schet[$r['schet_id']]))
				continue;

			$r['name'] = utf8($r['name']);
			$r['name'] = preg_replace($reg, '<b class="fs11 black u">\\1</b>', $r['name'], 1);
			$r['name'] = win1251($r['name']);

			$schet[$r['schet_id']]['content'] =
				'<table class="w100p mt5 grey collaps">'.
					'<tr class="bg-fff">'.
						'<td class="fs11">'.$r['name'].
						'<td class="fs11 w35 center">'.$r['count'].' шт.'.
						'<td class="fs11 w50 r wsnw">'._sumSpace($r['cena'], 1).
				'</table>';
		}
	}

	$schet = _clientValToList($schet);

	$send['spisok'] =
		($filter['page'] == 1 ?
			$filter['js'].
			'<div class="mt20 mb10">'.
				$filter['clear'].
				'Показан'._end($all, '', 'о').' <b>'.$all.'</b> сч'._end($all, 'ёт', 'ёта', 'етов').
				$yearMon.
				' на сумму <b>'._sumSpace($send['sum']).'</b> руб. '.

			($filter['group_id'] == 3 || $filter['group_id'] == 4 || $filter['group_id'] == 5 ?
				'<span class="color-ref bg-del ml20 pad5">Не оплачено: <b>'._sumSpace($nopaid).'</b> руб.</span>'
			: '').

			'</div>'.
			'<table class="_spisokTab">'.
				'<tr>'.
					'<th class="w35">Номер'.
					'<th class="w100">Дата'.
					'<th>Плательщик'.
					'<th class="w70">Сумма'.
					'<th class="w100">Статус'
		: '');
	foreach($schet as $r) {
		$status = $r['pass'] ? '<div class="fs12'._tooltip(FullData($r['pass_day']), -15).'передан клиенту</div>' : '';

		$paid = $r['sum_paid'] >= $r['sum'];
		$status = $paid ? '<div class="color-pay">оплачено</div>' : $status;

		$status = $r['type_id'] == 2 ? 'ознакомительный' : $status;

		if($filter['find'] && !FIND_NOMER && !FIND_SUM) {
			$reg = '/('.$filter['find'].')/iu';
			$reg = utf8($reg);
			$r['client_name'] = utf8($r['client_name']);
			$r['client_name'] = preg_replace($reg, '<b class="u">\\1</b>', $r['client_name'], 1);
			$r['client_name'] = win1251($r['client_name']);
		}

		$send['spisok'] .=
			'<tr class="over1 curP '._schetPayStatusBg($r).'" onclick="schetPayShow('.$r['id'].')">'.
				'<td class="r top'.($r['nomer'] == FIND_NOMER ? ' b u' : '').'">'.($r['type_id'] == 2 ? '-' : $r['prefix'].$r['nomer']).
				'<td class="r wsnw top">'.FullData($r['date_create']).
				'<td>'.$r['client_name'].$r['content'].
				'<td class="r top'.($r['sum'] == FIND_SUM ? ' b u' : '').'">'._sumSpace($r['sum'], 1).
				'<td class="top">'.$status;
	}

	$send['spisok'] .=
		_next(array(
				'tr' => 1,
				'type' => 4
			) + $filter);

	return $send;
}
function _schetPayStatusBg($r) {//получение цвета статуса счёта на оплату
	//удалён
	if($r['deleted'])
		return 'bg-eee grey';

	//ознакомительный
	if($r['type_id'] == 2)
		return '';

	//оплачено
	if($r['sum_paid'] >= $r['sum'])
		return 'bg-dfd';

	//передано клиенту
	if($r['pass'])
		return 'bg-ch';

	//не передано
	return 'bg-ffe';

}
function _schetPay_income($schet) {//список платежей по конкретному счёту на оплату
	if($schet['type_id'] == 2)
		return '';

	$sql = "SELECT *
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `schet_id`=".$schet['id']."
			ORDER BY `id` DESC";
	if(!$spisok = query_arr($sql))
		return '<div class="_info mt10">По данному счёту платежи не производились.</div>';

	$c = count($spisok);
	$diff = _cena($schet['sum'] - $schet['sum_paid']);

	$send =
	'<table class="w100p mt20">'.
		'<tr>'.
			'<td>Всего <b>'.$c.'</b> плат'._end($c, 'ёж', 'ежа', 'ей').' на сумму <b>'._sumSpace($schet['sum_paid']).'</b> руб.'.
($diff <= 0 ? '<td class="bg-dfd color-pay pad5 center w150">Оплачено полностью.' : '').
($diff > 0 ? '<td class="bg-del color-ref pad5 center w200">Недоплачен'._end($diff, '', 'о').' <b>'._sumSpace($diff).'</b> руб.' : '').
	'</table>'.

	'<table class="_spisokTab mt5">';
	foreach($spisok as $r)
		$send .= '<tr class="over2">'.
			'<td class="w70 b r">'._sumSpace($r['sum']).
			'<td><span class="color-sal">'._invoice($r['invoice_id']).':</span> день оплаты: '.FullData($r['schet_paid_day'], 1).
			'<td class="grey r">'._dtimeAdd($r);

	$send .= '</table>';

	return $send;
}
function _schetPay_stat() {//сводка по счетам по месяцам
	$sql = "SELECT
				DISTINCT DATE_FORMAT(`date_create`, '%Y') `id`,
				DATE_FORMAT(`date_create`, '%Y') `y`,
				COUNT(*) `year_count`,
				SUM(`sum`) `year_sum`,
				SUM(`sum_paid`) `year_paid`
			FROM `_schet_pay`
			WHERE `app_id`=".APP_ID."
			   AND `type_id`=1
			  AND !`deleted`
			GROUP BY `y`
			ORDER BY `date_create` DESC";
	if(!$year = query_arr($sql))
		return '<div class="_empty">Нет данных.</div>';

	foreach($year as $id => $r) {
		$year[$id]['new'] = array();
	}

	//новые счета
	$sql = "SELECT
				DISTINCT DATE_FORMAT(`date_create`, '%Y-%m') `ym`,
				DATE_FORMAT(`date_create`, '%Y') `y`,
				DATE_FORMAT(`date_create`, '%m') `m`,
				COUNT(*) `count`,
				SUM(`sum`) `sum`,
				SUM(`sum_paid`) `paid`
			FROM `_schet_pay`
			WHERE `app_id`=".APP_ID."
			   AND `type_id`=1
			  AND !`deleted`
			GROUP BY `ym`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		$year[$r['y']]['new'][_num($r['m'])] = $r['count'];
		$year[$r['y']]['sum'][_num($r['m'])] = _cena($r['sum']);
		$year[$r['y']]['paid'][_num($r['m'])] = _cena($r['paid']);
	}

	$send = '';
	foreach($year as $r) {
		$send .=
			'<div class="mt20 b fs14 curP">'.$r['id'].'</div>'.
			'<table class="_spisokTab mt5">'.
				'<tr>'.
					'<th class="w70">Месяц'.
					'<th class="w50">Счета'.
					'<th class="w100">Выставлено<br />на сумму'.
					'<th class="w100">Оплачено'.
					'<th class="w100">Не оплачено'.
					'<th>';

		$diff = $r['year_paid'] - $r['year_sum'];
		$send .=
			'<tr>'.
				'<td class="b">Год:'.
				'<td class="b center">'.$r['year_count'].
				'<td class="b r">'._sumSpace($r['year_sum'], 1).
				'<td class="b r color-pay">'._sumSpace($r['year_paid'], 1).
				'<td class="b r color-'.($diff < 0 ? 'ref' : 'pay').'">'._sumSpace($diff, 1).
				'<td>';

//		for($mon = 1; $mon <= 12; $mon++) {
		for($mon = 12; $mon > 0; $mon--) {
			$diff = '';
			if(!$emp = empty($r['new'][$mon]))
				$diff = $r['paid'][$mon] - $r['sum'][$mon];

			$send .=
				'<tr class="'.($emp ? 'grey' : 'over3 curP schet-stat-tr').'" val="'.$r['id'].'-'.($mon < 10 ? 0 : '').$mon.'">'.
					'<td class="'.($emp ? '' : 'u').'">'._monthDef($mon).':'.
					'<td class="center">'.@$r['new'][$mon].
					'<td class="r">'.($emp ? '' : _sumSpace(@$r['sum'][$mon], 1)).
					'<td class="r color-pay">'.($emp ? '' : _sumSpace(@$r['paid'][$mon], 1)).
					'<td class="r color-'.($diff < 0 ? 'ref' : 'pay').'">'.($emp ? '' : _sumSpace($diff, 1)).
					'<td>';
		}

		$send .= '</table>';
	}

	return $send;
}


/* --- вывод списка платежей и возвратов в информации о заявке --- */
function _zayavInfoMoney($zayav_id) {
	return '<div id="_zayav-money">'._zayavInfoMoney_spisok($zayav_id).'</div>';
}
function _zayavInfoMoney_spisok($zayav_id) {
	//платежи
	$income = income_spisok(array('zayav_id'=>$zayav_id));

	//возвраты
	$refund = _refund_spisok(array('zayav_id'=>$zayav_id));

	$money = _arrayTimeGroup($income['arr']);
	$money = _arrayTimeGroup($refund['arr'], $money);

	if(empty($money))
		return '';

	krsort($money);

	$spisok = '';
	foreach($money as $r) {
		if($r['type'] == 'refund')
			$spisok .= _refund_unit($r, array('zayav_id'=>$zayav_id));
		else
			$spisok .= _income_unit($r, array('zayav_id'=>$zayav_id));
	}
	return
		'<div class="headBlue but">'.
			'Платежи и возвраты'.
			'<button onclick="_refundAdd()" class="vk small red'._tooltip('Произвести возврат денежных средств', -210, 'r').'Возврат</button>'.
			'<button class="vk small" onclick="_incomeAdd()">Принять платёж</button>'.
		'</div>'.
		'<table class="_spisok">'.$spisok.'</table>';
}

