<?php
function _money_script() {//������� � �����
	return
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/money/money'.MIN.'.css?'.VERSION.'" />'.
		'<script src="'.API_HTML.'/modul/money/money'.MIN.'.js?'.VERSION.'"></script>';
}

function _accrualAdd($v) {//�������� ������ ����������
	$client_id = _num(@$v['client_id']);
	$zayav_id = _num(@$v['zayav_id']);
	$schet_id = _num(@$v['schet_id']);
	$dogovor_id = _num(@$v['dogovor_id']);
	$about = _txt(@$v['about']);

	if(!$sum = _cena(@$v['sum']))
		jsonError('����������� ������� �����');

	if($client_id && !$zayav_id && !$about)
		jsonError('������� ��������');

	if($zayav_id) {
		if(!$z = _zayavQuery($zayav_id))
			jsonError('������ �� ����������');
		$client_id = $z['client_id'];
	}

	if(!$client_id && !$zayav_id)
		jsonError('�� ������ ������ ���� ������');

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

	//�������� ������� ��� �������
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
function _accrual_spisok($v=array()) {//������ ����������
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
				'spisok' => $filter['js'].'<div class="_empty">���������� ���.</div>'
			);

	$all = $send['all'];
	$send['spisok'] = $filter['page'] != 1 ? '' :
		$filter['js'].
		'<div id="summa">'.
			'�������� <b>'.$all.'</b> ���������'._end($all, '�', '�', '�').
			' �� ����� <b>'._sumSpace($send['sum']).'</b> ���.'.
		'</div>'.
		'<table class="_spisok">'.
			'<tr><th>�����'.
				'<th>��������'.
				'<th>����'.
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
function _accrual_unit($r, $filter) {//������ ���������� � �������
	$about = '';
	if($r['schet_id'])
		$about = $r['schet_pay_acc'];
	if(!$filter['zayav_id'] && $r['zayav_id'])
		$about .= ' ������ '.$r['zayav_link'].'. ';
	if($r['dogovor_id'])
		$about .= '������� <u>'.$r['dogovor_nomer'].'</u> �� '.$r['dogovor_data'];
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
/* --- ����� ������ ���������� � ���������� � ������ --- */
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
			'����������'.
			'<button class="vk small" onclick="_accrualAdd()">������ ����������</button>'.
		'</div>'.
		'<table class="_spisok">'.$spisok.'</table>';
}



/* --- �������� --- */
function _refund() {
	$data = _refund_spisok();
	return
		'<table class="tabLR" id="money-refund">'.
			'<tr><td class="left">'.
					'<div class="headName">��������</div>'.
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
				'spisok' => $filter['js'].'<div class="_empty">��������� ���.</div>',
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
			'�������'._end($all, '', '�').' <b>'.$all.'</b> �������'._end($all, '', '�', '��').
			' �� ����� <b>'._sumSpace($send['sum']).'</b> ���.'.
		'</div>'.
		'<table class="_spisok">'.
			'<tr><th>�����'.
				'<th>��������'.
				'<th>����'.
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
function _refund_unit($r, $filter=array()) {//������ �������� � �������
	return
	'<tr><td class="sum '.$r['type']._tooltip('�������', -3)._sumSpace($r['sum']).
		'<td>'._refundAbout($r, $filter).
		'<td class="dtime">'._dtimeAdd($r).
		'<td class="ed">'._iconDel($r + array('class'=>'_refund-del'));
}
function _refundAbout($r, $filter=array()) {
	$about = '';
	if($r['zayav_id'] && !@$filter['zayav_id'])
		$about .= '������ '.@$r['zayav_link'];

	$about .=
		($r['about'] && $about ? ', ' : '').$r['about'].
		($r['client_id'] && !@$filter['zayav_id'] ? '<div class="refund-client">������: '.$r['client_link'].'</div>' : '');

	return '<span class="type">'._invoice($r['invoice_id']).($about ? ':' : '').'</span> '.$about;
}
function _refund_right() {
	return
		'<div class="f-label">�����</div>'.
		'<input type="hidden" id="invoice_id" />'.
		'<script>_refundLoad();</script>';
}

/* --- ������� --- */
function income_top($sel) { //������� ������ ������ ��� ��������
	$sql = "SELECT DISTINCT `viewer_id_add`
			FROM `_money_income`
			WHERE `app_id`=".APP_ID;
	$worker = query_workerSelJson($sql);

	return
		'<table id="top">'.
			'<tr><td id="mi-l">'.
					'<div class="f-label">�����</div>'.
					'<input type="hidden" id="invoice_id" />'.
				'<td id="mi-center">'.
					'<div class="f-label">������ ���������</div>'.
					'<input type="hidden" id="worker_id" />'.
					'<div class="f-label">�������������</div>'.
					_check('prepay', '����������', 0, 1).
					_check('schet', '������� �� ������', 0, 1).
					_check('deleted', '+ �������� �������', 0, 1).
					_check('deleted_only', '�������� ������ ��������', 0, 1).
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
function income_days($mon=0) {//������� ���� � ���������, � ������� ��������� �������
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
function income_path($data) {//���� � �����
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
//		'<a href="'.URL.'&p=??">���</a> � '.(YEAR ? '' : '<b>�� �� �����</b>').
		'���'.
//		(MON ? '<a href="'.URL.'&p=??&year='.YEAR.'">'.YEAR.'</a> � ' : '<b>'.YEAR.'</b>').
		' � '.YEAR.
		(MON ? ' � '._monthDef(MON, 1) : '').
//		(DAY ? '<a href="'.URL.'&p=??&mon='.YEAR.'-'.MON.'">'._monthDef(MON, 1).'</a> � ' : (MON ? '<b>'._monthDef(MON, 1).'</b>' : '')).
		(DAY ? ' � <b>'.intval(DAY).$to.'</b>' : '').
		'<button class="vk fr" onclick="_incomeAdd()">������ �����</button>';
}
function income_invoice_sum($data) {//������� � ������� �������� �� ������� �����
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
		'<tr><td class="r"><b>�����:</b>'.
			'<td class="center"><b>'.$data['all'].'</b>'.
			'<td class="r"><b>'._sumSpace($data['sum'], 1).'</b>';
	$send .= '</table>';

	return $send;
}
function income_day() {
	switch(@RULE_MY_PAY_SHOW_PERIOD) {
		case 1: $period = TODAY; break;             //1 - ����
		default: $period = _period(); break;        //2 - ������
		case 3: $period = substr(TODAY, 0,7); break;//3 - �����
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
		'schet' => _bool(@$v['schet']),     //���������� ������ ������� �� ������ �� ������
		'schet_id' => _num(@$v['schet_id']),//������� �� ����������� ����� �� ������
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
						'spisok' => $filter['js'].'<div class="_empty">�������� ���.</div>',
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
			'<tr><th>�����'.
				'<th>��������'.
				'<th>����'.
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
			'<td class="sum '.$r['type'].(@$filter['zayav_id'] ? _tooltip('�����', 8) : '">')._sumSpace($r['sum']).
			'<td>'.incomeAbout($r, $filter).
			'<td class="dtime">'._dtimeAdd($r).
			'<td class="ed">'.
		  (SA ? '<div onclick="incomeUnbind('.$r['id'].')" class="img_cancel'._tooltip('��������...', -59, 'r').'</div>' : '').
				_incomePrint($r['id']).
				_iconDel($r + array('class'=>'income-del','nodel'=>($confirmed || $refund || $r['dogovor_id']),'del'=>$confirm));
}
function incomeAbout($r, $filter=array()) {
	$about = '';
	if($r['zayav_id'] && !@$filter['zayav_id'])
		$about .= '������ '.@$r['zayav_link'].'. ';

	$about .= $r['tovar_sale'];

	if($r['schet_id'])
		$about .= '<div class="schet">'.$r['schet_pay_income'].' ���� ������: '.FullData($r['schet_paid_day'], 1).'</div>';
	if($r['dogovor_id'])
		$about .= '��������� ����� �� �������� <u>'.$r['dogovor_nomer'].'</u> �� '.$r['dogovor_data'];
	if($r['confirm'] == 1)
		$about .=
			'<button val="'.$r['id'].'#'.$r['invoice_id'].'#'._sumSpace($r['sum']).'#'.FullDataTime($r['dtime_add']).'" class="vk small'._tooltip('����������� ����������� �� ����', -67).
				'�����������'.
			'</button>'.
			'<div class="confirm">������� �������������</div>';
	if($r['confirm'] == 2)
		$about .= '<div class="confirmed">���������� '.FullDataTime($r['confirm_dtime']).'</div>';

	$refund = !@$r['no_refund_show'] && !$r['refund_id'] && !$r['client_id'] && !$r['tovar_id'] ?
			'<a class="refund" val="'.$r['id'].'">�������</a>'.
			'<input type="hidden" class="refund-dtime" value="'.FullDataTime($r['dtime_add']).'">'
			: '';

	$about .= $refund.
		($r['about'] && $about ? ', ' : '').$r['about'].
		($r['client_id'] && !@$filter['client_id'] && !@$filter['zayav_id'] ? '<div class="income-client">������: '.$r['client_link'].'</div>' : '').
		($r['refund_id'] ? ' <span class="red">����� ���������.</span>' : '');

	return '<span class="type">'._invoice($r['invoice_id']).($about ? ':' : '').'</span> '.$about;
}
function _incomePrint($income_id) {//������-������ ��� ������ ��������� ����
	if(!defined('INCOME_RECEIPT_EXISTS'))
		define('INCOME_RECEIPT_EXISTS', _templateVerify('income-receipt'));

	if(!INCOME_RECEIPT_EXISTS)
		return '';

	return
	'<a onclick="_templatePrint(\'income-receipt\',\'income_id\','.$income_id.')" '.
	   'class="img_doc'._tooltip('����������� �������� ���', -155, 'r').
	'</a>';
}
function _incomeReceipt($id) {//�������� ��� ��� �������
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
	'<div class="org-name">�������� � ������������ ���������������� <b>�'._app('name').'�</b></div>'.
	'<div class="cash-rekvisit">'.
		'��� '._app('inn').'<br />'.
		'���� '._app('ogrn').'<br />'.
		'��� '._app('kpp').'<br />'.
		str_replace("\n", '<br />', _app('adres_yur')).'<br />'.
		'<table><tr>'.
			'<td>���.: '._app('phone').
			'<th>'.FullData($money['dtime_add']).' �.'.
		'</table>'.
	'</div>'.
	'<div class="head">'.(TEMPLATE_2 ? '�������� ���' : '���������').' �'.$money['id'].'</div>'.
	'<div class="shop">�������</div>'.
	'<div class="shop-about">(������������ ��������, ������������ �������������, ������������� ��������, � �.�.)</div>'.
	'<table class="tab">'.
		'<tr><th>�<br />�.�.'.
			'<th>������������ ������'.
			'<th>����������'.
			'<th>����'.
			'<th>�����'.
		'<tr><td class="nomer">1'.
			'<td class="about">'.
				($zayav['dogovor_id'] ? '������ �� �������� �'.$dog['nomer'] : '').
			'<td class="count">1.00'.
			'<td class="sum">'.$money['sum'].
			'<td class="summa">'.$money['sum'].
		'</table>'.
	'<div class="summa-propis">'._numToWord($money['sum'], 1).' ����'._end($money['sum'], '�', '�', '��').'</div>'.
	'<div class="shop-about">(����� ��������)</div>'.
	'<table class="cash-podpis">'.
		'<tr><td>�������� ______________________<div class="prod-bot">(�������)</div>'.
			'<td><u>/'._viewer($money['viewer_id_add'], 'viewer_name_init').'/</u><div class="r-bot">(����������� �������)</div>'.
	'</table>';
}
function income_schet_spisok($schet) {//������ �������� �� ����������� ����� �� ������
	$sql = "SELECT *
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `schet_id`=".$schet['id']."
			ORDER BY `id` DESC";
	if(!$spisok = query_arr($sql))
		return '<div id="no-pay">�� ������� ����� ������� �� �������������.</div>';

	$sum = 0;
	$send = '<table class="_spisok">';
	foreach($spisok as $r) {
		$send .= '<tr>'.
			'<td class="sum">'._sumSpace($r['sum']).
			'<td><span class="type">'._invoice($r['invoice_id']).':</span> ���� ������: '.FullData($r['schet_paid_day'], 1).
			'<td class="dtime">'._dtimeAdd($r);
		$sum += _cena($r['sum']);
	}

	$send .= '</table>';

	$count = count($spisok);
	$diff = $schet['sum'] - $sum;
	return
	'<div>'.
		'����� <b>'.$count.'</b> ����'._end($count, '��', '���', '��').' �� ����� <b>'._sumSpace($sum).'</b> ���.'.
		($diff > 0 ?
			'<span class="diff">����������� <b>'._sumSpace($diff).'</b> ���.</span>'
			:
			'<span class="diff full">�������� ���������.</span>'
		).
	'</div>'.
	$send;
}

/* --- ������� --- */
function _expense($id=0, $i='name') {//������ ��������� ��������
	$key = CACHE_PREFIX.'expense';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					*,
					0 `sub`
				FROM `_money_expense_category`
				WHERE `app_id`=".APP_ID." OR !`app_id`
				ORDER BY `sort`";
		$arr = query_arr($sql);

		//���������� ������������
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

	//��� ���������
	if(!$id)
		return $arr;

	//������ JS ��� select
	if($id == 'js') {
		$spisok = array();
		foreach($arr as $r)
			$spisok[$r['id']] = $r['name'];
		return _selJson($spisok);
	}

	//����������� id ���������
	if(!isset($arr[$id]))
		return _cacheErr('����������� id ��������� ��������', $id);

	//������ ���������� ���������
	if($i == 'all')
		return $arr[$id];

	//����������� ���� ���������
	if(!isset($arr[$id][$i]))
		return _cacheErr('����������� ���� ��������� ��������', $i);

	//������� ������ ���������� ��������� �������
	return $arr[$id][$i];
}
function _expenseSub($id, $i='name') {//������ ������������ ��������
	$key = CACHE_PREFIX.'expense_sub';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT *
				FROM `_money_expense_category_sub`
				WHERE `app_id`=".APP_ID;
		$arr = query_arr($sql);
		xcache_set($key, $arr, 86400);
	}

	//������ JS ��� select
	if($id == 'js') {
		$spisok = array();
		foreach($arr as $r)
			$spisok[$r['category_id']][$r['id']] = $r['name'];

		$js = array();
		foreach($spisok as $uid => $r)
			$js[] = $uid.':'._selJson($r);

		return '{'.implode(',', $js).'}';
	}

	//����������� id
	if(!isset($arr[$id]))
		return _cacheErr('����������� id ������������ ��������', $id);

	//����������� ����
	if(!isset($arr[$id][$i]))
		return _cacheErr('����������� ���� ������������ ��������', $i);

	//������� ������ ���������� ��������� �������
	return $arr[$id][$i];
}
function _expenseValToList($arr) {//������� ������ �������� ����������� � ������ �� expense_id
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
					'<div class="headName">������ �������� �����������<a class="add">������ ����� ������</a></div>'.
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
		'<div class="f-label">���������</div>'.
		'<input type="hidden" id="category_id">'.
		'<input type="hidden" id="category_sub_id">'.

		'<div class="findHead">����</div>'.
		'<input type="hidden" id="invoice_id">'.

		'<input type="hidden" id="year">'.
		'<input type="hidden" id="mon" value="'._num(strftime('%m')).'">';
}
function expenseMonthSum($v=array()) {//������ ��������� � �������� � ������� �������� �� ������� ������
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
function expense_graf($filter, $i='json') {//������ ���� �������� �� ����������
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
			'name' => '��� ���������',
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
		return $send + array('spisok' => $filter['js'].'<div class="_empty">������� ���</div>');

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
			'�������'._end($all, '�', '�').' <b>'.$all.'</b> �����'._end($all, '�', '�', '��').
			' �� ����� <b>'._sumSpace($send['sum']).'</b> ���.'.
		'</div>'.
		'<table class="_spisok" id="tab">'.
			'<tr><th>�����'.
				'<th>��������'.
				'<th>����'.
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
						_iconDel($r + array('del' => APP_ID == 3495523 ? 1 : 0)) //todo �������� ������� ��� ����� ��������
					: '');
	}

	$send['spisok'] .= _next($filter + array(
			'all' => $all,
			'tr' => 1
		));

	return $send;
}
function expenseAbout($r) {//�������� ��� ��������
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
		($r['attach_id'] ? '<div>����: '.$r['attach_link'].'</div>' : '');
}

/*
function income_all() {//����� �������� �� �����
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
		'<div class="headName">����� �������� �� �����</div>'.
		'<table class="_spisok">'.
		'<tr><th>���'.
		'<th>�������'.
		'<th>������'.
		'<th>������ �����'.
		implode('', $spisok).
		'</table>';
}



function income_year($year) {//����� �������� �� �������
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
		'<div class="headName">����� �������� �� ������� �� '.$year.' ���</div>'.
		'<div class="inc-path">'.income_path($year).'</div>'.
		'<table class="_spisok">'.
		'<tr><th>�����'.
		'<th>�������'.
		'<th>������'.
		'<th>������ �����'.
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
		'<div class="headName">����� �������� �� ���� �� '._monthDef(MON, 1).' '.YEAR.'</div>'.
		'<div class="inc-path">'.$path.'</div>'.
		'<table class="_spisok sums">'.
		'<tr><th>�����'.
		'<th>�����'.
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



function _invoice($id=0, $i='name') {//��������� ������ ������ �� ����
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

			$r['visible_worker'] = _invoiceWorker($r['visible']);//������ �����������, ������� ����� ����
			$r['visible_ass'] = _invoiceWorkerAss($r['visible']);

			$r['income_insert_worker'] = _invoiceWorker($r['income_insert']);//������ �����������, ������� ����� ������� �������
			$r['income_insert_ass'] = _invoiceWorkerAss($r['income_insert']);

			$r['expense_insert_worker'] = _invoiceWorker($r['expense_insert']);//������ �����������, ������� ����� ������� �������
			$r['expense_insert_ass'] = _invoiceWorkerAss($r['expense_insert']);

			$arr[$r['id']] = $r;
		}

		xcache_set($key, $arr, 86400);
	}

	//��� �����
	if(!$id)
		return $arr;

	//������ ��� _select
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

	//��������� ids �����������, ������� ����� ������� ������� �� ��������� ������ ��� js
	if($id == 'income_insert_js') {
		$spisok = array();

		foreach($arr as $r)
			$spisok[$r['id']] = $r['income_insert_ass'];

		if(!$spisok)
			return '{}';

		return str_replace('"', '', json_encode($spisok));
	}

	//��������� ids �����������, ������� ����� ������� ������� �� ��������� ������ ��� js
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

	//��������� ids �����������, ������� ����� ������� ������� �� ��������� ������ ��� js
	if($id == 'expense_insert_js') {
		$spisok = array();

		foreach($arr as $r)
			$spisok[$r['id']] = $r['expense_insert_ass'];

		if(!$spisok)
			return '{}';

		return str_replace('"', '', json_encode($spisok));
	}

	//������������ id �����
	if(!_num($id))
		_cacheErr('������������ id ���������� �����', $id);

	//�������� ������������� ����� (��� ajax)
	if($i == 'test') {
		if(!isset($arr[$id]))
			return false;
		if($arr[$id]['deleted'])
			return false;
		return true;
	}

	//����������� id �����
	if(!isset($arr[$id]))
		_cacheErr('����������� id ���������� �����', $id);

	//������� ������ ���� ������
	if($i == 'all')
		return $arr[$id];

	//��������� ��� �������� ����������
	if($i == 'viewer_visible')
		return _bool(@$arr[$id]['visible_ass'][VIEWER_ID]);

	//����������� ���� �����
	if(!isset($arr[$id][$i]))
		return '<span class="red">����������� ���� �����: <b>'.$i.'</b></span>';

	return $arr[$id][$i];
}
function _invoiceWorker($worker_ids) {//��������� ������ ��� �����������
	if(!$worker_ids)
		return '';

	$vw = array();//������ ��� �����������
	foreach(explode(',', $worker_ids) as $k)
		$vw[] = _viewer($k, 'viewer_name');

	return implode('<br />', $vw);
}
function _invoiceWorkerAss($worker_ids) {//��������� �������������� ������ id �����������
	if(!$worker_ids)
		return array();

	$ass = array();
	foreach(explode(',', $worker_ids) as $k)
		$ass[$k] = 1;

	return $ass;
}
function _invoiceBalans($invoice_id, $start=false) {// ��������� �������� ������� �����
	if($start === false)
		$start = _invoice($invoice_id, 'start');

	//�������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `confirm` NOT IN (1,3)
			  AND !`deleted`
			  AND `invoice_id`=".$invoice_id;
	$income = query_value($sql);

	//�������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `invoice_id`=".$invoice_id;
	$expense = query_value($sql);

	//��������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_refund`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `invoice_id`=".$invoice_id;
	$refund = query_value($sql);

	//�������: ����-�����������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_invoice_transfer`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `invoice_id_from`=".$invoice_id;
	$from = query_value($sql);

	//�������: ����-����������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_invoice_transfer`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `invoice_id_to`=".$invoice_id;
	$to = query_value($sql);

	//�������� ����� �� ����
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_invoice_in`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `invoice_id`=".$invoice_id;
	$in = query_value($sql);

	//������ ����� �� �����
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_invoice_out`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `invoice_id`=".$invoice_id;
	$out = query_value($sql);

	return round($income - $expense - $refund - $from + $to + $in - $out - $start, 2);
}

function invoice() {//�������� �� ������� ������ � ���������� ����� �������
	return
	'<script>'.
		'var RULE_SETUP_INVOICE='.RULE_SETUP_INVOICE.','.
			'RULE_INVOICE_TRANSFER='.RULE_INVOICE_TRANSFER.';'.
	'</script>'.
	'<div id="money-invoice">'.

	(_invoiceTransferConfirmCount() ?
		'<div class="_info">'.
			'���� �������� ����� �������, ��������� �������������: <b>'._invoiceTransferConfirmCount().'</b>. '.
		'</div>'
	: '').

		'<input type="hidden" id="invoice_menu" value="1" />'.

		'<div class="invoice_menu-1">'.
		(RULE_SETUP_INVOICE ?
			'<button class="vk" onclick="_invoiceEdit()">������� ����� ����</button>'
		: '').
			'<div id="invoice-spisok">'.invoice_spisok().'</div>'.
		'</div>'.

	(RULE_INVOICE_TRANSFER ?
		'<div class="invoice_menu-2 dn">'.
			'<button class="vk" onclick="_invoiceTransfer()">��������� �������</button>'.
			'<div id="transfer-spisok">'.invoice_transfer_spisok().'</div>'.
		'</div>'.

		'<div class="invoice_menu-3 dn">'.
			'<button class="vk mr5" onclick="_invoiceIn()">������ ������ �� ����</button>'.
			'<button class="vk" onclick="_invoiceOut()">������� ������ �� �����</button>'.
			'<div id="inout-spisok">'.invoice_inout_spisok().'</div>'.
		'</div>'
	: '').

	'</div>';
}
function invoice_spisok() {
	if(!_invoice())
		return '����� �� ����������.';

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
(RULE_SETUP_INVOICE && $r['income_confirm'] ? '<h6>������������� ����������� �� ����</h6>' : '').
(RULE_SETUP_INVOICE && $r['transfer_confirm'] ? '<h6>������������� ���������</h6>' : '').
(RULE_SETUP_INVOICE ?
				'<td class="worker">'.
					($r['visible'] ? '<h4>��������� ��� �����������:</h4><h5>'.$r['visible_worker'].'<h5>' : '').
					($r['income_insert'] ? '<h4>����� ������� �������:</h4><h5>'.$r['income_insert_worker'].'<h5>' : '').
					($r['expense_insert'] ? '<h4>����� ������� �������:</h4><h5>'.$r['expense_insert_worker'].'<h5>' : '')
: '').
				'<td class="balans"><b>'.($r['start'] != -1 ? _sumSpace(_invoiceBalans($r['id'])).'</b> ���.' : '').
				'<td class="ed">'.
					'<span class="'._tooltip('������������ �� ���������', -162, 'r')._check('def'.$r['id'], '', $def == $r['id'] ? 1 : 0).'</span>'.
					'<div val="'.$r['id'].'" class="img_setup'._tooltip('��������� �������� ��� ������', -195, 'r').'</div>'.
(RULE_INVOICE_HISTORY ?
					'<div onclick="_balansShow(1,'.$r['id'].')" class="img_note'._tooltip('���������� ������� ��������', -176, 'r').'</div>'
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
function _invoiceTransferConfirmCount($plus_b=0) { //��������� ���������� ��������� �� ������, ������� ���������� �����������
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
function invoice_transfer_spisok($v=array()) {//������� ��������� �� ������
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
		return '��������� ���.';

	$sql = "SELECT *
	        FROM `_money_invoice_transfer`
	        WHERE ".$cond."
	        ORDER BY `id` DESC
	        LIMIT "._start($filter).",".$filter['limit'];
	$q = query($sql);
	$send = $filter['page'] != 1 ? '' :
		'<div class="mt10">����� '.$all.' �������'._end($all, '', '�', '��').'.</div>'.
		'<table class="_spisok mt5">'.
			'<tr>'.
				'<th>C����'.
				'<th>�� �����'.
				'<th>�� ����'.
				'<th>�����������'.
				'<th>����'.
				'<th>';
	while($r = mysql_fetch_assoc($q)) {
		$confirm = '';
		if($r['confirm']) {
			$class = '';
			$ne = '';
			$button = '';
			if($r['confirm'] == 1) {
				$class = ' no';
				$ne = '�� ';
				$button =
				(VIEWER_ADMIN ?
					'<button val="'.$r['id'].
								'#'.$r['invoice_id_from'].
								'#'.$r['invoice_id_to'].
								'#'._sumSpace($r['sum']).
								'#'.FullDataTime($r['dtime_add']).'" '.
							'class="vk small fr'._tooltip('����������� �������', -35).
						'�����������'.
					'</button>'
				: '');
			}
			$confirm = $r['about'] ? '<br />' : '';
			$confirm .= '<span class="confirm'.$class.'">'.$ne.'������������</span>'.$button;
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
		return '������� ���.';


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
		'<div class="mt10">����� '.$all.' �����'._end($all, '�', '�', '��').'.</div>'.
		'<table class="_spisok mt5">'.
			'<tr>'.
				'<th>��������'.
				'<th>C����'.
				'<th>����'.
				'<th>�����������'.
				'<th>����'.
				'<th>';

	$action = array(
		'in' => '��������',
		'out' => '�����'
	);

	while($r = mysql_fetch_assoc($q)) {
		$worker = $r['worker_id'] ? '<div>����������: <u>'._viewer($r['worker_id'], 'viewer_name').'</u></div>' : '';
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

function _balans($v) {//�������� ������ � �������
	$app_id = _num(@$v['app_id']) ? _num($v['app_id']) : APP_ID;
	$category_id = _balansAction($v['action_id'], 'category_id');
	$unit_id = 0;
	$balans = 0;
	$sum = _cena(@$v['sum'], 1);
	$sum_old = _cena(@$v['sum_old'], 1);
	$invoice_transfer_id = _num(@$v['invoice_transfer_id']);

	if(_balansAction($v['action_id'], 'minus'))
		$sum *= -1;

	//��������� ����
	if(!empty($v['invoice_id'])) {
		$unit_id = _num($v['invoice_id']);
		$balans = 0;
		if($v['action_id'] != 15)//���� �� �������� �����
			$balans = _invoiceBalans($unit_id);


		// ��������� ����� ����� � �����-�����������
		if($invoice_transfer_id) {
			$sql = "SELECT * FROM `_money_invoice_transfer` WHERE `id`=".$invoice_transfer_id;
			$r = query_assoc($sql);
			if($unit_id == $r['invoice_id_from'])
				$sum *= -1;
		}
	}

	//������
	if(!empty($v['client_id'])) {
		$unit_id = _num($v['client_id']);
		$balans = _clientBalansUpdate($unit_id);
	}

	//���������
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
function _balansAction($id, $i='name') {//��������� ������ �������� �������� �� ������ �� ����
	$key = CACHE_PREFIX.'balans_action';
	$arr = xcache_get($key);
	if(empty($arr)) {
		$sql = "SELECT * FROM `_balans_action`";
		$arr = query_arr($sql);
		xcache_set($key, $arr, 86400);
	}

	if(!isset($arr[$id]))
		return '<span class="red">����������� �������� �������: <b>'.$id.'</b></span>';

	if(!isset($arr[$id][$i]))
		return '<span class="red">����������� ���� �������� �������: <b>'.$i.'</b></span>';

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
function balans_show($v) {//����� ������� � ��������� ����������� �����
	$filter = balansFilter($v);

	$r = balans_show_category($filter);
	if(!empty($r['error']))
		return $r['about'];

	$data = balans_show_spisok($filter);

	//������ �������� �����
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
			'<div class="fr curD'._tooltip('������� ������', -53).'<b>'.$r['balans'].'</b> ���.</div>'.
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
		case 1: //��������� �����
			$sql = "SELECT *
					FROM `_money_invoice`
					WHERE `app_id`=".APP_ID."
					  AND `id`=".$v['unit_id'];
			if(!$r = query_assoc($sql))
				return array(
					'error' => 1,
					'about' => _err('����� id:<b>'.$v['unit_id'].'</b> �� ����������.')
				);
			return array(
				'type' => '����',
				'head' => $r['name'],
				'about' => $r['about'],
				'balans' => _sumSpace(_invoiceBalans($v['unit_id']))
			);
			break;
		case 2: //������
			if(!_clientQuery($v['unit_id']))
				return array(
					'error' => 1,
					'about' => _err('������� id:<b>'.$v['unit_id'].'</b> �� ����������.')
				);
			$r = _clientVal($v['unit_id']);
			return array(
				'type' => '������',
				'head' => $r['name'],
				'balans' => _sumSpace($r['balans'])
			);
			break;
		case 5: //�� ����������
			if(!_viewerWorkerQuery($v['unit_id']))
				return array(
					'error' => 1,
					'about' => _err('���������� id:<b>'.$v['unit_id'].'</b> �� ����������.')
				);
			return array(
				'type' => '���������',
				'head' => _viewer($v['unit_id'], 'viewer_name'),
				'balans' => salaryWorkerBalans($v['unit_id'], 1)
			);
			break;
	}
	return array(
		'error' => 1,
		'head' => '���������',
		'about' => _err('����������� ��������� ��������: <b>'.$v['category_id'].'</b>.'),
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

		//������� �� ����� ���
		$sql = "SELECT `balans`
				FROM `_balans`
				WHERE ".$cond."
				ORDER BY `id` DESC
				LIMIT 1";
		$sumEnd = query_value($sql);

		//������� �� ������ ���
		$sql = "SELECT SUM(`sum`)
				FROM `_balans`
				WHERE ".$cond;
		$sumStart = $sumEnd - query_value($sql);

		//������ ��������
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
						'<tr class="grey"><td colspan="2">������ ���:<td class="r">'._sumSpace($sumStart).
						$action.
						'<tr class="grey"><td colspan="2">����� ���:<td class="r">'._sumSpace($sumEnd).
					'</table>'.
				'<td class="top">'.
					'<button class="vk small red day-clear">�������� �����</button>'.
		'</table>';
	}

	$send = array(
		'all' => 0,
		'spisok' => $filter['js'].'<div class="_empty">������� ���.</div>',
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
			'<tr><th>��������'.
				'<th>�����'.
				'<th>�������'.
				'<th>��������'.
				'<th>����';

	foreach($spisok as $r) {
		$sum = _sumSpace($r['sum']);
		$sum_diff = '';
		if(round($r['sum_old'], 2)) {//���� ����� ����������
			$sum = ($r['sum'] - $r['sum_old'] > 0  ? '+' : '')._sumSpace($r['sum'] - $r['sum_old']);
			$sum_diff = '<div class="diff">'.round($r['sum_old'], 2).' &rarr; '.round($r['sum'], 2).'</div>';
		}
		$sum = $sum ? $sum : '';


		$about = @$r['schet_link_full'];

		if($r['zayav_id'])
			$about .= '������ '.$r['zayav_link'].'. ';

		if($r['dogovor_id'])
			$about .= '������� '.$r['dogovor_nomer'].'. ';

		$about .= $r['about'];

		// �������� ��� ��������� ����� �������
		if($r['invoice_transfer_id']) {
			$trans = $transfer[$r['invoice_transfer_id']];
			if($trans['invoice_id_from'] != $r['unit_id'])
				$about = '����������� �� ����� <span class="type">'._invoice($trans['invoice_id_from']).'</span>.';
			elseif($trans['invoice_id_to'] != $r['unit_id'])
				$about = '����������� �� ���� <span class="type">'._invoice($trans['invoice_id_to']).'</span>.';
		}

		// �������� ��� ��������
		if($r['income_id']) {
			$income[$r['income_id']]['client_id'] = 0;
			$income[$r['income_id']]['no_refund_show'] = 1;//�� ���������� ������ "�������"
			$about = incomeAbout($income[$r['income_id']]);
		}

		// �������� ��� ��������
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
function balans_everyday($v) {//����������� ������� �� ������ ����
	$v = balansFilter($v);

	define('MONTH', $v['everyday_year'].'-'.$v['everyday_mon']);

	$ass = array();

	// ������� �� ����� ���
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

	// ����� ��������
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

	// ����� ��������
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

	// ������ �� ��������� ����
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
			'<tr><th>����'.
				'<th>������ ���'.
				'<th>������'.
				'<th>������'.
				'<th>�������'.
				'<th>�������';

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








/* --- ����� �� ������ --- */

function _schetPayQuery($id, $withDel=0) {//������ ������ � ����� �� ������
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
function _schetPayTypeSelect($show) {//����� ������ ���� ����� (��� ������ �����)
	if(!$show)
		return '';

	$type = array(
		1 => '<b>���� �� ������</b>'.
			 '<div class="grey ml20 fs12 mt5 mb20">'.
				'����� ������ ����� ���� �� ������, ����� ����������� ���������� �������.'.
			'</div>',

		2 => '<b>��������������� ����</b>'.
			 '<div class="grey ml20 fs12 mt5">'.
				'���� ����� ������ � ������� ��� �������� ��� ������������.'.
				'<br />'.
				'���������� ����� �������� �� �����.'.
				'<br />'.
				'���������� ������� ������������� <b>�� �����</b>.'.
				'<br />'.
				'�� ������� ����� ���������� ����� ����������� �������.'.
				'<br />'.
				'� ���������� ���� ���� ����� ����� ��������� "�� ������".'.
			'</div>'
	);

	return
		'<div id="schet-pay-type-select">'.
			'<div class="hd2">������� ��� ������������ �����:</div>'.
			'<div class="mar20">'.
				_radio('schet-pay-type', $type).
			'</div>'.
		'</div>';
}
function _schetPayValToList($arr, $key='schet_id') {//������� ������ ������ �� ������ � ������
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
			//��� ����������
			'schet_pay_acc' => '<div class="schet-pay-acc '._schetPayStatusBg($c).'" onclick="schetPayShow('.$c['id'].')">'.
									'���� �� ������ '.
									'� <b>'.$c['prefix'].$c['nomer'].'</b> '.
								'</div>',
			//��� ��������, ������, ������� ��������
			'schet_pay_income' => '<div class="schet-pay-income '._schetPayStatusBg($c).'" onclick="schetPayShow('.$c['id'].')">'.
									$c['prefix'].$c['nomer'].
								'</div>'
		) + $arr[$id];
	}
	return $arr;
}
function _schetPayToZayav($zayav) {//����������� ������ ������ � ������� ������ ������
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
function _schetPaySumCorrect($schet_id) {//�������� � ����� ����� ��������, ������� ���� ����������� ��� �������� ��� �������� �������
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
		'group_id' => _num(@$v['group_id']),//������ ������: ��������, �� ��������, ��������, �� ��������
		'mon' => _txt(@$v['mon']),
		'client_id' => _num(@$v['client_id']),
		'zayav_id' => _num(@$v['zayav_id']),
		'clear' => ''
	);
	foreach($default as $k => $r)
		if($r != $filter[$k]) {
			$filter['clear'] = '<button class="vk small red fr" onclick="schetPayFilterClear()">�������� ������</button>';
			break;
		}
	return $filter;
}
function _schetPay() {//�������� ������ �� ������
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
						'<button class="vk" onclick="schetPayEdit()">������� ���� �� ������</button>'.
						'<a href="'.URL.'&p=28" class="icon icon-setup-big mt5 ml10 mr5'._tooltip('��������� ����� �� ������', -154, 'r').'</a>'.
			'</table>'.

   (DEBUG ? '<div class="mt10">'.
				'<button class="vk red" onclick="schetPayAllRemove($(this))">SA: ������� ��� ����� �� ������</button>'.
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
	$contentIds = '';   //ids ������, ��������� � ���������� ��� ������� ������
	$yearMon = '';      //�������� ������ � ����, ���� ������� ����

	if($filter['find']) {
		//������� ����� �� ����������
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
			case 2: $cond .= " AND `type_id`=2"; break;                                 //���������������
			case 3: $cond .= " AND `type_id`=1 AND !`pass` AND `sum_paid`<`sum`"; break;//�� ��������
			case 4:	$cond .= " AND `type_id`=1 AND `sum_paid`<`sum`"; break;            //�� ��������
			case 5: $cond .= " AND `type_id`=1 AND `pass` AND `sum_paid`<`sum`"; break; //��������, �� ��������
			case 6: $cond .= " AND `sum_paid`>=`sum`"; break;                           //��������
		}
		if($filter['mon']) {
			$cond .= " AND `date_create` LIKE '".$filter['mon']."%'";
			$ex = explode('-', $filter['mon']);
			$yearMon = ' �� <span class="pad5 bg-ddf">'._monthDef($ex[1], 1).' '.$ex[0].'</span>';
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
		return $send + array('spisok' => $filter['js'].'<div class="_empty">����� �� �������</div>');

	$all = $send['all'];
	$filter['all'] = $all;
	$nopaid = $send['sum'] - $send['paid'];

	$schet = array();

	//��������� ����� �� ������ �����, � �������� ������ ����� � ������� �������
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

	//������� ���������� ����������� �������� ������
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
						'<td class="fs11 w35 center">'.$r['count'].' ��.'.
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
				'�������'._end($all, '', '�').' <b>'.$all.'</b> ��'._end($all, '��', '���', '����').
				$yearMon.
				' �� ����� <b>'._sumSpace($send['sum']).'</b> ���. '.

			($filter['group_id'] == 3 || $filter['group_id'] == 4 || $filter['group_id'] == 5 ?
				'<span class="color-ref bg-del ml20 pad5">�� ��������: <b>'._sumSpace($nopaid).'</b> ���.</span>'
			: '').

			'</div>'.
			'<table class="_spisokTab">'.
				'<tr>'.
					'<th class="w35">�����'.
					'<th class="w100">����'.
					'<th>����������'.
					'<th class="w70">�����'.
					'<th class="w100">������'
		: '');
	foreach($schet as $r) {
		$status = $r['pass'] ? '<div class="fs12'._tooltip(FullData($r['pass_day']), -15).'������� �������</div>' : '';

		$paid = $r['sum_paid'] >= $r['sum'];
		$status = $paid ? '<div class="color-pay">��������</div>' : $status;

		$status = $r['type_id'] == 2 ? '���������������' : $status;

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
function _schetPayStatusBg($r) {//��������� ����� ������� ����� �� ������
	//�����
	if($r['deleted'])
		return 'bg-eee grey';

	//���������������
	if($r['type_id'] == 2)
		return '';

	//��������
	if($r['sum_paid'] >= $r['sum'])
		return 'bg-dfd';

	//�������� �������
	if($r['pass'])
		return 'bg-ch';

	//�� ��������
	return 'bg-ffe';

}
function _schetPay_income($schet) {//������ �������� �� ����������� ����� �� ������
	if($schet['type_id'] == 2)
		return '';

	$sql = "SELECT *
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `schet_id`=".$schet['id']."
			ORDER BY `id` DESC";
	if(!$spisok = query_arr($sql))
		return '<div class="_info mt10">�� ������� ����� ������� �� �������������.</div>';

	$c = count($spisok);
	$diff = _cena($schet['sum'] - $schet['sum_paid']);

	$send =
	'<table class="w100p mt20">'.
		'<tr>'.
			'<td>����� <b>'.$c.'</b> ����'._end($c, '��', '���', '��').' �� ����� <b>'._sumSpace($schet['sum_paid']).'</b> ���.'.
($diff <= 0 ? '<td class="bg-dfd color-pay pad5 center w150">�������� ���������.' : '').
($diff > 0 ? '<td class="bg-del color-ref pad5 center w200">����������'._end($diff, '', '�').' <b>'._sumSpace($diff).'</b> ���.' : '').
	'</table>'.

	'<table class="_spisokTab mt5">';
	foreach($spisok as $r)
		$send .= '<tr class="over2">'.
			'<td class="w70 b r">'._sumSpace($r['sum']).
			'<td><span class="color-sal">'._invoice($r['invoice_id']).':</span> ���� ������: '.FullData($r['schet_paid_day'], 1).
			'<td class="grey r">'._dtimeAdd($r);

	$send .= '</table>';

	return $send;
}
function _schetPay_stat() {//������ �� ������ �� �������
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
		return '<div class="_empty">��� ������.</div>';

	foreach($year as $id => $r) {
		$year[$id]['new'] = array();
	}

	//����� �����
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
					'<th class="w70">�����'.
					'<th class="w50">�����'.
					'<th class="w100">����������<br />�� �����'.
					'<th class="w100">��������'.
					'<th class="w100">�� ��������'.
					'<th>';

		$diff = $r['year_paid'] - $r['year_sum'];
		$send .=
			'<tr>'.
				'<td class="b">���:'.
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


/* --- ����� ������ �������� � ��������� � ���������� � ������ --- */
function _zayavInfoMoney($zayav_id) {
	return '<div id="_zayav-money">'._zayavInfoMoney_spisok($zayav_id).'</div>';
}
function _zayavInfoMoney_spisok($zayav_id) {
	//�������
	$income = income_spisok(array('zayav_id'=>$zayav_id));

	//��������
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
			'������� � ��������'.
			'<button onclick="_refundAdd()" class="vk small red'._tooltip('���������� ������� �������� �������', -210, 'r').'�������</button>'.
			'<button class="vk small" onclick="_incomeAdd()">������� �����</button>'.
		'</div>'.
		'<table class="_spisok">'.$spisok.'</table>';
}

