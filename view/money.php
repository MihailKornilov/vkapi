<?php
function _money() {
	$d = empty($_GET['d']) ? 'income' : $_GET['d'];
	switch($d) {
		default:
			$d = 'income';
			switch(@$_GET['d1']) {
				case 'income':
				default:
					switch(@RULE_MY_PAY_SHOW_PERIOD) {
						case 1: $period = TODAY; break;             //1 - ����
						default: $period = _period(); break;        //2 - ������
						case 3: $period = substr(TODAY, 0,7); break;//3 - �����
					}
					$content = income_day($period);
					break;
				case 'all': $left = income_all(); break;
				case 'year':
					if(empty($_GET['year']) || !preg_match(REGEXP_YEAR, $_GET['year'])) {
						$left = '������ ������������ ���.';
						break;
					}
					$left = income_year(intval($_GET['year']));
					break;
				case 'month':
					if(empty($_GET['mon']) || !preg_match(REGEXP_YEARMONTH, $_GET['mon'])) {
						$left = '������ ������������ �����.';
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
			'<a class="link'.($d == 'income' ? ' sel' : '').'" href="'.URL.'&p=money&d=income">�������</a>'.
			'<a class="link'.($d == 'expense' ? ' sel' : '').'" href="'.URL.'&p=money&d=expense">�������</a>'.
			'<a class="link'.($d == 'refund' ? ' sel' : '').'" href="'.URL.'&p=money&d=refund">��������</a>'.
			(_schetCount() ? '<a class="link'.($d == 'schet' ? ' sel' : '').'" href="'.URL.'&p=money&d=schet">����� �� ������</a>' : '').
			'<a class="link'.($d == 'invoice' ? ' sel' : '').'" href="'.URL.'&p=money&d=invoice">��������� �����'._invoiceTransferConfirmCount(1).'</a>'.
		'</div>'.
		$content;
}

function _accrualAdd($z, $sum, $about='') {//�������� ������ ����������
	if(!_cena($sum))
		return;

	$sql = "INSERT INTO `_money_accrual` (
				`app_id`,
				`zayav_id`,
				`client_id`,
				`sum`,
				`about`,
				`viewer_id_add`
			) VALUES (
				".APP_ID.",
				".$z['id'].",
				".$z['client_id'].",
				".$sum.",
				'".addslashes($about)."',
				".VIEWER_ID."
			)";
	query($sql);

	//�������� ������� ��� �������
	_balans(array(
		'action_id' => 25,
		'client_id' => $z['client_id'],
		'zayav_id' => $z['id'],
		'sum' => $sum,
		'about' => $about
	));

	_zayavBalansUpdate($z['id']);

	_history(array(
		'type_id' => 74,
		'client_id' => $z['client_id'],
		'zayav_id' => $z['id'],
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

	$spisok = _schetValToList($spisok);
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
		$about = '���� '.$r['schet_link_full'].'<br />';
	if(!$filter['zayav_id'] && $r['zayav_id'])
		$about .= '������ '.$r['zayav_link'].'. ';
	if($r['dogovor_id'])
		$about .= '������� <u>'.$r['dogovor_nomer'].'</u> �� '.$r['dogovor_data'];
	$about .= $r['about'];

	return
	'<tr class="_accrual-unit'.($r['deleted'] ? ' deleted' : '').'">'.
		'<td class="sum">'._sumSpace($r['sum']).
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
			'<button class="vk small _accrual-add">������ ����������</button>'.
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
		'<script type="text/javascript">_refundLoad();</script>';
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
		'<script type="text/javascript">'.
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
		'<a href="'.URL.'&p=report&d=money&d1=income&d2=all">���</a> � '.(YEAR ? '' : '<b>�� �� �����</b>').
		(MON ? '<a href="'.URL.'&p=report&d=money&d1=income&d2=year&year='.YEAR.'">'.YEAR.'</a> � ' : '<b>'.YEAR.'</b>').
		(DAY ? '<a href="'.URL.'&p=report&d=money&d1=income&d2=month&mon='.YEAR.'-'.MON.'">'._monthDef(MON, 1).'</a> � ' : (MON ? '<b>'._monthDef(MON, 1).'</b>' : '')).
		(DAY ? '<b>'.intval(DAY).$to.'</b>' : '').
		'<a class="_income-add add">������ �����</a>';
}
function income_day($day) {
	$data = income_spisok(array('period'=>$day));
	return
		'<div id="money-income">'.
			income_top($day).
			'<div id="path">'.income_path($day).'</div>'.
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
	$money = _schetValToList($money);
	$money = _dogovorValToList($money);
	$money = _tovarValToList($money);

	$send['spisok'] = $filter['page'] != 1 ? '' :
		$filter['js'].
		'<div id="summa">'.
			'�������'._end($all, '', '�').' <b>'.$all.'</b> ������'._end($all, '', '�', '��').
			' �� ����� <b>'._sumSpace($send['sum']).'</b> ���.'.
		'</div>'.
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
	$deleted = $r['deleted'] ? ' deleted' : '';
	return
		'<tr class="_income-unit'.$prepay.$refund.$deleted.'">'.
			'<td class="sum '.$r['type'].(@$filter['zayav_id'] ? _tooltip('�����', 8) : '">')._sumSpace($r['sum']).
			'<td>'.incomeAbout($r, $filter).
			'<td class="dtime">'._dtimeAdd($r).
			'<td class="ed">'.
				'<a href="'.URL.'&p=print&d=receipt&id='.$r['id'].'" class="img_doc'._tooltip('����������� �������� ���', -157, 'r').'</a>'.
				_iconDel($r + array('class'=>'income-del','nodel'=>($refund || $r['dogovor_id'])));
}
function incomeAbout($r, $filter=array()) {
	$about = '';
	if($r['zayav_id'] && !@$filter['zayav_id'])
		$about .= '������ '.@$r['zayav_link'].'. ';

	$about .= $r['tovar_sale'];

	if($r['schet_id'])
		$about .= '<div class="schet">'.$r['schet_link'].' ���� ������: '.FullData($r['schet_paid_day'], 1).'</div>';
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
	'<div class="head">�������� ��� �'.$money['id'].'</div>'.
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
function _incomeReceiptPrint() {//������ ��������� ���� ��� �������
	if(!$id = _num(@$_GET['id']))
		die('������������ id.');

	$sql = "SELECT *
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `id`=".$id;

	if(!$r = query_assoc($sql))
		die('������� id='.$id.' �� ����������.');

	$doc = new clsMsDocGenerator(
		$pageOrientation = 'PORTRAIT',
		$pageType = 'A4',
		$cssFile = GLOBAL_DIR.'/css/dogovor.css',
		$topMargin = 1,
		$rightMargin = 2,
		$bottomMargin = 1,
		$leftMargin = 1
	);
	$doc->addParagraph(_incomeReceipt($id));
	$doc->output(time().'-income-receipt-'.$id.'.doc');
	mysql_close();
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
	$key = CACHE_PREFIX.'expense'.APP_ID;
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
	$key = CACHE_PREFIX.'expense_sub'.APP_ID;
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
		'<script type="text/javascript" src="/.vkapp/.js/highcharts.js"></script>'.
		'<table class="tabLR" id="money-expense">'.
			'<tr><td class="left">'.
					'<div class="headName">������ �������� �����������<a class="add">������ ����� ������</a></div>'.
					'<div id="container"></div>'.
					'<div id="spisok">'.$data['spisok'].'</div>'.
				'<td class="right">'.expense_right().
		'</table>'.

		'<script type="text/javascript">'.
			'var ATTACH={},'.
(VIEWER_ADMIN ? 'GRAF='.expense_graf($data['filter']).',' : '').
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

	$js = !PAGE1 ? '' :
		'<script type="text/javascript">'.
			'var EXPENSE={'.
				'limit:'.$filter['limit'].','.
				'invoice_id:'.$filter['invoice_id'].','.
				'category_id:'.$filter['category_id'].','.
				'category_sub_id:'.$filter['category_sub_id'].','.
				'year:'.$filter['year'].','.
				'mon:'.$filter['mon'].
			'};'.
		'</script>';

	if(!$send['all'])
		return $send + array('spisok' => $js.'<div class="_empty">������� ���</div>');

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
		$js.
		'<div id="summa">'.
			'�������'._end($all, '�', '�').' <b>'.$all.'</b> �����'._end($all, '�', '�', '��').
			' �� ����� <b>'._sumSpace($send['sum']).'</b> ���.'.
		'</div>'.
		'<table class="_spisok" id="tab">'.
			'<tr><th>�����'.
				'<th>��������'.
				'<th>����'.
				'<th>';

	foreach($expense as $r)
		$send['spisok'] .=
			'<tr'.($r['deleted'] ? ' class="deleted"' : '').'>'.
				'<td class="sum"><b>'._sumSpace($r['sum']).'</b>'.
				'<td>'.expenseAbout($r).
				'<td class="dtime">'._dtimeAdd($r).
				'<td class="ed">'.
					($r['category_id'] != 1 ? _iconEdit($r)._iconDel($r) : '');

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
			'<td><a href="'.URL.'&p=report&d=money&d1=income&d2=year&year='.$r['year'].'">'.$r['year'].'</a>'.
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
			'<tr><td class="r"><a href="'.URL.'&p=report&d=money&d1=income&d2=month&mon='.$year.'-'.$r['mon'].'">'._monthDef($r['mon'], 1).'</a>'.
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
			'<tr><td class="r"><a href="'.URL.'&p=report&d=money&d1=income&day='.$mon.'-'.$r['day'].'">'.intval($r['day']).'.'.MON.'.'.YEAR.'</a>'.
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
	$key = CACHE_PREFIX.'invoice'.APP_ID;
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
		die('Error: incoice_id <b>'.$id.'</b> not correct');

	//����������� id �����
	if(!isset($arr[$id]))
		die('Error: no invoice_id <b>'.$id.'</b> in _invoice');

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
	'<div id="money-invoice">'.
		'<div class="headName">'.
			'��������� �����'.
(RULE_SETUP_INVOICE ? '<a class="add">����� ����</a>' : '').
		'</div>'.

(_invoiceTransferConfirmCount() ?
		'<div class="_info">'.
			'���� ��������, ��������� �������������: <b>'._invoiceTransferConfirmCount().'</b>. '.
		'</div>'
: '').

		'<div id="invoice-spisok">'.invoice_spisok().'</div>'.

(RULE_INVOICE_TRANSFER ?
		'<div class="dlink js">'.
			'<a class="link sel">�������� ����� �������</a>'.
			'<a class="link">�������� � ������</a>'.
		'</div>'.
		'<div id="transfer-spisok" class="dlink-page">'.invoice_transfer_spisok().'</div>'.
		'<div id="inout-spisok" class="dlink-page dn">'.invoice_inout_spisok().'</div>'
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
					'<div val="1:'.$r['id'].'" class="_balans-show img_note'._tooltip('���������� ������� ��������', -176, 'r').'</div>'
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
		'<div>����� '.$all.' �������'._end($all, '', '�', '��').'.</div>'.
		'<table class="_spisok">'.
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
							'class="vk small'._tooltip('����������� �������', -35).
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
		'<div>����� '.$all.' �����'._end($all, '�', '�', '��').'.</div>'.
		'<table class="_spisok">'.
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

function invoice_info_balans_day($v) {//����������� ������� ����� �� ������ ����
	$v = array(
		'invoice_id' => _num($v['invoice_id']),
		'year' => !_num(@$v['year']) ? strftime('%Y') : $v['year'],
		'mon' => !_num(@$v['mon']) ? strftime('%m') : ($v['mon'] < 10 ? 0 : '').$v['mon']
	);

//	define('MONTH', $v['year'].'-'.$v['mon']);
	define('MONTH', '2015-10');

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
				  AND `category_id`=1
				  AND `unit_id`=".$v['invoice_id']."
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
			  AND `category_id`=1
			  AND `unit_id`=".$v['invoice_id']."
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
			  AND `category_id`=1
			  AND `unit_id`=".$v['invoice_id']."
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
			  AND `category_id`=1
			  AND `unit_id`=".$v['invoice_id']."
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
				'<td class="day">'.(isset($ass[$d]) ? '<a class="to-day" val="'.MONTH.'-'.($d < 10 ? 0 : '').$d.'">'.$day.'</a>' : $day).
				'<td class="start">'._sumSpace($start).
				'<td class="inc">'.($inc ? _sumSpace($inc) : '').
				'<td class="dec">'.($dec ? _sumSpace($dec) : '').
				'<td class="diff '.($diff > 0 ? 'inc' : 'dec').'">'.($diff ? _sumSpace($diff) : '').
				'<td class="ost">'._sumSpace($balans);
	}
	$send .= '</table>';
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
		'unit_id' => _num(@$v['unit_id'])
	);
}
function balans_show($v) {//����� ������� � ��������� ����������� �����
	$filter = balansFilter($v);

	$r = balans_show_category($filter);
	if(!empty($r['error']))
		return $r['about'];

	$data = balans_show_spisok($filter);

	return
		'<div id="balans-show">'.
			'<div class="headName">'.$r['head'].'</div>'.
			(@$r['about'] ? '<div class="_info">'.$r['about'].'</div>' : '').
			'<div>������� ������: <b>'.$r['balans'].'</b> ���.</div>'.

			'<div id="dopLinks">' .
				'<span>������� ��������:</span>'.
				'<a class="link sel">��������</a>' .
				'<a class="link">���������</a>' .
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
				'head' => '���� '.$r['name'],
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
				'head' => '������ '.$r['name'],
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
				'head' => '��������� '._viewer($v['unit_id'], 'viewer_name'),
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
	define('PAGE1', $filter['page'] == 1);

	$cond = "`app_id`=".APP_ID."
		 AND `category_id`=".$filter['category_id']."
		 AND `unit_id`=".$filter['unit_id'];

	$send = array(
		'all' => 0,
		'spisok' => '<div class="_empty">������� ���.</div>',
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

	$spisok = _schetValToList($spisok);
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
		$income = _schetValToList($income);
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
		'<table class="_spisok" id="balans-tab">'.
			'<tr><th>��������'.
				'<th>�����'.
				'<th>������'.
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

	$send['spisok'] .= _next($filter + array(
			'all' => $all,
			'tr' => 1,
			'id' => '_balans_next'
		));

	return $send;
}








/* --- ����� �� ������ --- */

function _schetQuery($id, $withDeleted=0) {//������ ������ �� ����� �������
	$sql = "SELECT *
			FROM `_schet`
			WHERE `app_id`=".APP_ID.
			  ($withDeleted ? '' : ' AND !`deleted`')."
			  AND `id`=".$id;
	return query_assoc($sql);
}
function _schetCount() {//���������� ������ � ���������� (��� ������ ������ �� �����)
	$sql = "SELECT COUNT(*)
			FROM `_schet`
			WHERE `app_id`=".APP_ID;
	return query_value($sql);
}
function _schetValToList($arr) {//������ � �����, ������������� � ������
	$ids = array();
	$arrIds = array();
	foreach($arr as $key => $r)
		if(!empty($r['schet_id'])) {
			$ids[$r['schet_id']] = 1;
			$arrIds[$r['schet_id']][] = $key;
		}
	if(empty($ids))
		return $arr;
	$sql = "SELECT
	            *
			FROM `_schet`
			WHERE `app_id`=".APP_ID."
			  AND `id` IN (".implode(',', array_keys($ids)).")";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		foreach($arrIds[$r['id']] as $id)
			$arr[$id] += _schetValForm($r);
	return $arr;
}
function _schetValForm($r) {//������������ ���������� ����� ��� �����������
	$prefix = '��';
	$deleted = $r['deleted'] ? ' deleted' : '';
	$classPaid = !$deleted && _cena($r['paid_sum']) && $r['paid_sum'] >= $r['sum'] ? ' paid' : '';
	$classPass = !$deleted && !$classPaid && $r['pass'] ? ' pass' : '';
	return array(
		'schet_nomer' => $prefix.$r['nomer'],
		'schet_date' => FullData($r['date_create']),
		'schet_link' => '<span class="schet-link'.$deleted.$classPass.$classPaid.'" val="'.$r['id'].'">'.$prefix.$r['nomer'].'</span>',
		'schet_link_full' => '<span class="schet-link'.$deleted.$classPass.$classPaid.'" val="'.$r['id'].'"><b>'.$prefix.$r['nomer'].'</b> �� '.FullData($r['date_create'], 1).'</span>'
	);
}
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
}
function _schet() {
	$data = _schet_spisok();
	return
	'<table class="tabLR" id="money-schet">'.
		'<tr><td class="left">'.
				'<div class="headName">����� �� ������</div>'.
				'<div id="spisok">'.$data['spisok'].'</div>'.
			'<td class="right">'._schet_right().
	'</table>';
}
function _schet_spisok($v=array()) {
	$filter = _schetFilter($v);
	$filter = _filterJs('SCHET', $filter);

	$cond = "`app_id`=".APP_ID."
		 AND !`deleted`";

	if($filter['find'])
		$cond .= " AND `nomer`="._num($filter['find']);
	else {
		if($filter['client_id'])
			$cond .= " AND `client_id`=" . $filter['client_id'];
		switch ($filter['passpaid']) {
			case 1:
				$cond .= " AND !`pass` AND `paid_sum`<`sum`";
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
	$send = query_assoc($sql);
	$send['filter'] = $filter;
	if(!$send['all'])
		return $send + array('spisok' => $filter['js'].'<div class="_empty">������ ���.</div>');

	$all = $send['all'];
	$filter['all'] = $all;

	$send['spisok'] =
		($filter['page'] == 1 ?
			$filter['js'].
			'<div id="result">'.
				'�������'._end($all, '', '�').' <b>'.$all.'</b> ��'._end($all, '��', '���', '����').
				' �� ����� <b>'._sumSpace($send['sum']).'</b> ���.'.
			'</div>'.
			'<table class="_spisok">'
		: '');

	$sql = "SELECT *
			FROM `_schet`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT "._start($filter).",".$filter['limit'];
	$q = query($sql);

	$spisok = array();
	while($r = mysql_fetch_assoc($q)) {
		$r['paids'] = array();
		$spisok[$r['id']] = $r;
	}

	$spisok = _zayavValToList($spisok);


	//������ �������� �� ������
	$sql = "SELECT *
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `schet_id` IN (".implode(',', array_keys($spisok)).")";
	$q = query($sql);
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
}
function _schet_unit($r, $zayav=1) {
	$paid = _cena($r['paid_sum']) && $r['paid_sum'] >= $r['sum'];
	$pass_info = $r['pass'] && !$paid ? '<div class="pass-info">�������� ������� '.FullData($r['pass_day'], 1).'</div>' : '';

	$paid_info = '';
	if($r['paid_sum'] > 0)
		foreach($r['paids'] as $i)
			$paid_info .= '<div class="paid-info">�������� <b>'.round($i['sum'], 2) . '</b> ���. '.FullData($i['day'], 1).'.</div>';

	$paid = $paid ? ' paid' : '';
	$pass = $pass_info ? ' pass' : '';
	return
		'<tr class="schet-unit'.$pass.$paid.'" id="schet-unit'.$r['id'].'">'.
			'<td class="td-content">'.
	  (!$paid ? '<input type="hidden" class="schet-action" id="act'.$r['id'].'" />' : '').
				'<a class="info" val="'.$r['id'].'">'.
					'���� � <b class="pay-nomer">��'.$r['nomer'].'</b>'.
				'</a> '.
				'�� <u>'.FullData($r['date_create']).'</u> �. '.
				'�� ����� <b class="pay-sum">'._sumSpace($r['sum']).'</b> ���. '.
				$pass_info.
				$paid_info.
			($zayav && $r['zayav_id'] ? '<div class="schet-zayav">������ '.$r['zayav_link'].'.</div>' : '');
}
function _schet_right() {
	return
		'<div id="find"></div>'.
		'<div class="findHead">���������� �����:</div>'.
		_radio('passpaid',
			array(
				0 => '���',
				1 => '�� ��������',
				2 => '��������, �� ���.',
				3 => '��������'
			), 0, 1);
}
function _schetPayCorrect($schet_id) {//�������� � ����� ����� ��������, ������� ���� ����������� ��� �������� ��� �������� �������
	if(empty($schet_id))
		return;

	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `schet_id`=".$schet_id;
	$sum = query_value($sql);
	$sql = "UPDATE `_schet`
			SET `paid_sum`=".$sum."
			WHERE `id`=".$schet_id;
	query($sql);
}
function _schetToZayav($zayav) {//����������� ������ ������ � ������� ������ ������
	$sql = "SELECT *
			FROM `_schet`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `zayav_id` IN (".implode(',', array_keys($zayav)).")";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		$form = _schetValForm($r);
		$zayav[$r['zayav_id']]['schet'] .= $form['schet_link'];
	}
	return $zayav;
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
			'<button class="vk small red _refund-add'._tooltip('���������� ������� �������� �������', -210, 'r').'�������</button>'.
			'<button class="vk small _income-add">������� �����</button>'.
		'</div>'.
		'<table class="_spisok">'.$spisok.'</table>';
}

