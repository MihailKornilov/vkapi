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
						case 1: $period = TODAY; break;
						case 2: $period = substr(TODAY, 0,7); break;
						default: $period = _period();
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
			'<a class="link'.($d == 'schet' ? ' sel' : '').'" href="'.URL.'&p=money&d=schet">����� �� ������</a>'.
			'<a class="link'.($d == 'invoice' ? ' sel' : '').'" href="'.URL.'&p=money&d=invoice">��������� �����</a>'.
		'</div>'.
		$content;
}//_money()

function _accrualFilter($v) {
	$send = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 100,
		'client_id' => _num(@$v['client_id']),
		'zayav_id' => _num(@$v['zayav_id'])
	);
	return $send;
}//_refundFilter()
function _accrual_spisok($v=array()) {//������ ����������
	$filter = _accrualFilter($v);
	$filter = _filterJs('ACCRUAL', $filter);

	$cond = "`app_id`=".APP_ID."
	     AND `ws_id`=".WS_ID."
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
	$send = query_assoc($sql, GLOBAL_MYSQL_CONNECT);

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
	$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT);

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
}//_accrual_spisok()
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
		'<td>'.trim($about).
		'<td class="dtime">'._dtimeAdd($r).
		'<td class="ed">'.
			(!$r['schet_id'] && !$r['dogovor_id'] ?
				_iconDel(array(
					'id' => $r['id'],
					'class' => '_accrual-del'
				))
			: '');
}//_accrual_unit()
/* --- ����� ������ ���������� � ���������� � ������ --- */
function _zayavInfoAccrual($zayav_id) {
	return '<div id="_zayav-accrual">'._zayavInfoAccrual_spisok($zayav_id).'</div>';
}//_zayavInfoAccrual()
function _zayavInfoAccrual_spisok($zayav_id) {
	$accrual = _accrual_spisok(array('zayav_id'=>$zayav_id));

	if(!$accrual['all'])
		return '';

	$spisok = '';
	foreach($accrual['arr'] as $r)
		$spisok .= _accrual_unit($r, array('zayav_id'=>$zayav_id));

	return
		'<div class="headBlue">'.
			'����������'.
			'<a class="add _accrual-add">������ ����������</a>'.
		'</div>'.
		'<table class="_spisok">'.$spisok.'</table>';
}//_zayavInfoAccrual_spisok()



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

	$cond = "`app_id`=".APP_ID."
		 AND `ws_id`=".WS_ID."
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
	$send = query_assoc($sql, GLOBAL_MYSQL_CONNECT);

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
	$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT);

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
}//_refund_spisok()
function _refund_unit($r, $filter=array()) {//������ �������� � �������
	return
	'<tr><td class="sum '.$r['type']._tooltip('�������', -3)._sumSpace($r['sum']).
		'<td>'._refundAbout($r, $filter).
		'<td class="dtime">'._dtimeAdd($r).
		'<td class="ed">'._iconDel($r + array('class'=>'_refund-del'));
}//_refund_unit()
function _refundAbout($r, $filter=array()) {
	$about = '';
	if($r['zayav_id'] && !@$filter['zayav_id'])
		$about .= '������ '.@$r['zayav_link'];

	$about .=
		($r['about'] && $about ? ', ' : '').$r['about'].
		($r['client_id'] && !@$filter['zayav_id'] ? '<div class="refund-client">������: '.$r['client_link'].'</div>' : '');

	return '<span class="type">'._invoice($r['invoice_id']).($about ? ':' : '').'</span> '.$about;
}//incomeAbout()
function _refund_right() {
	return
		'<div class="f-label">�����</div>'.
		'<input type="hidden" id="invoice_id" />'.
		'<script type="text/javascript">_refundLoad();</script>';
}//_refund_right()

/* --- ������� --- */
function income_top($sel) { //������� ������ ������ ��� ��������
	$sql = "SELECT DISTINCT `viewer_id_add`
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID;
	$worker = query_workerSelJson($sql, GLOBAL_MYSQL_CONNECT);

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
					_check('schet_id', '������� �� ������', 0, 1).
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
}//income_top()
function income_days($mon=0) {//������� ���� � ���������, � ������� ��������� �������
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
}//income_path()
function income_day($day) {
	$data = income_spisok(array('period'=>$day));
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
		'schet_id' => _num(@$v['schet_id']),
		'worker_id' => _num(@$v['worker_id']),
		'prepay' => _bool(@$v['prepay']),
		'deleted' => _bool(@$v['deleted']),
		'deleted_only' => _bool(@$v['deleted_only']),
		'period' => _period(@$v['period'])
	);
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
	if($filter['schet_id'])
		$cond .= " AND `schet_id`";
	if($filter['prepay'])
		$cond .= " AND `prepay`";
	if(!$filter['deleted'])
		$cond .= " AND !`deleted`";
	elseif($filter['deleted_only'])
		$cond .= " AND `deleted`";
	if(!$filter['client_id'] && !$filter['zayav_id'])
		$cond .= _period($filter['period'], 'sql');

	$sql = "SELECT
				COUNT(`id`) AS `all`,
				SUM(`sum`) AS `sum`
			FROM `_money_income`
			WHERE ".$cond;
	$send = query_assoc($sql, GLOBAL_MYSQL_CONNECT);

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
	$money = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	//$money = _viewer($money);
	$money = _clientValToList($money);
	if(function_exists('_zayavValToList'))
		$money = _zayavValToList($money);
	$money = _schetValToList($money);
	$money = _dogovorValToList($money);
//	$money = _zpLink($money);

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
}//income_spisok()
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
}//_income_unit()
function incomeAbout($r, $filter=array()) {
	$about = '';
	if($r['zayav_id'] && !@$filter['zayav_id'])
		$about .= '������ '.@$r['zayav_link'].'. ';
	if($r['zp_id'])
		$about .= '������� �������� '.@$r['zp_link'];//todo
	if($r['schet_id'])
		$about .= '<div class="schet">'.$r['schet_link'].' ���� ������: '.FullData($r['schet_paid_day'], 1).'</div>';
	if($r['dogovor_id'])
		$about .= '��������� ����� �� �������� <u>'.$r['dogovor_nomer'].'</u> �� '.$r['dogovor_data'];
	if($r['confirm'])
		$about .= '<div class="confirm">������� �������������</div>';
	if($r['confirm_dtime'] != '0000-00-00 00:00:00')
		$about .= '<div class="confirmed">���������� '.FullDataTime($r['confirm_dtime']).'</div>';

	$refund = !@$r['no_refund_show'] && !$r['refund_id'] && !$r['client_id'] && !$r['zp_id'] ?
			'<a class="refund" val="'.$r['id'].'">�������</a>'.
			'<input type="hidden" class="refund-dtime" value="'.FullDataTime($r['dtime_add']).'">'
			: '';

	$about .= $refund.
		($r['about'] && $about ? ', ' : '').$r['about'].
		($r['client_id'] && !@$filter['client_id'] && !@$filter['zayav_id'] ? '<div class="income-client">������: '.$r['client_link'].'</div>' : '').
		($r['refund_id'] ? ' <span class="red">����� ���������.</span>' : '');

	return '<span class="type">'._invoice($r['invoice_id']).($about ? ':' : '').'</span> '.$about;
}//incomeAbout()
function _incomeReceipt($id) {//�������� ��� ��� �������
	$sql = "SELECT *
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `id`=".$id;
	$money = query_assoc($sql, GLOBAL_MYSQL_CONNECT);

	$sql = "SELECT *
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `id`=".$money['zayav_id'];
	$zayav = query_assoc($sql, GLOBAL_MYSQL_CONNECT);

	$sql = "SELECT *
			FROM `_zayav_dogovor`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$money['zayav_id'];
	$dog = query_assoc($sql, GLOBAL_MYSQL_CONNECT);

	return
	'<div class="org-name">�������� � ������������ ���������������� <b>�'._ws('name').'�</b></div>'.
	'<div class="cash-rekvisit">'.
		'��� '._ws('inn').'<br />'.
		'���� '._ws('ogrn').'<br />'.
		'��� '._ws('kpp').'<br />'.
		str_replace("\n", '<br />', _ws('adres_yur')).'<br />'.
		'<table><tr>'.
			'<td>���.: '._ws('phone').
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
}//_incomeReceipt()
function _incomeReceiptPrint() {//������ ��������� ���� ��� �������
	if(!$id = _num(@$_GET['id']))
		die('������������ id.');

	$sql = "SELECT *
		FROM `_money_income`
		WHERE `app_id`=".APP_ID."
		  AND `ws_id`=".WS_ID."
		  AND !`deleted`
		  AND `id`=".$id;

	if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		die('������� id='.$id.' �� ����������.');

	$doc = new clsMsDocGenerator(
		$pageOrientation = 'PORTRAIT',
		$pageType = 'A4',
		$cssFile = DOCUMENT_ROOT.'/css/dogovor.css',
		$topMargin = 1,
		$rightMargin = 2,
		$bottomMargin = 1,
		$leftMargin = 1
	);
	$doc->addParagraph(_incomeReceipt($id));
	$doc->output(time().'-income-receipt-'.$id.'.doc');
	mysql_close();
}//_incomeReceipt()

/* --- ������� --- */
function _expense($id=0, $i='name') {//������ ��������� ��������
	$key = CACHE_PREFIX.'expense'.WS_ID;
	$arr = xcache_get($key);
	if(empty($arr)) {
		$sql = "SELECT *
				FROM `_money_expense_category`
				WHERE (`app_id`=".APP_ID." OR !`app_id`)
				  AND (`ws_id`=".WS_ID." OR !`ws_id`)
				ORDER BY `sort`";
		$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT);
		xcache_set($key, $arr, 86400);
	}

	//��� ���������
	if(!$id)
		return $arr;

	//������������ id ���������
	if(!_num($id))
		die('Error: expense category_id <b>'.$id.'</b> not correct');

	//����������� id ���������
	if(!isset($arr[$id]))
		die('Error: no expense category_id <b>'.$id.'</b> in _invoice');

	//������ ���� ���������
	if($i == 'all')
		return $arr[$id];

	//����������� ���� ���������
	if(!isset($arr[$id][$i]))
		return '<span class="red">����������� ���� ��������� �������: <b>'.$i.'</b></span>';

	//������� ������ ���������� ��������� �������
	return $arr[$id][$i];
}//_expense()
function expense() {
	$data = expense_spisok();
	return
		'<table class="tabLR" id="money-expense">'.
			'<tr><td class="left">'.
					'<div class="headName">������ �������� �����������<a class="add">������ ����� ������</a></div>'.
					'<div id="container"></div>'.
					'<div id="spisok">'.$data['spisok'].'</div>'.
				'<td class="right">'.expense_right().
		'</table>'.

		'<script type="text/javascript">'.
			'var GRAF='.expense_graf($data['filter']).','.
				'ATTACH={},'.
				'EXPENSE_MON='._selJson(expenseMonthSum()).';'.
			'expenseLoad();'.
		'</script>';
}//expense()
function expenseFilter($v) {
	return array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 50,
		'invoice_id' => _num(@$v['invoice_id']),
		'category_id' => intval(@$v['category_id']),
		'worker_id' => _num(@$v['worker_id']),
		'year' => _year(@$v['year']),
		'mon' => isset($v['mon']) ? _num($v['mon']) : _num(strftime('%m'))
	);
}//expenseFilter()
function expense_right() {
	$sql_worker = "SELECT DISTINCT `worker_id`
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker_id`";

	return
		'<div class="f-label">����</div>'.
		'<input type="hidden" id="invoice_id">'.

		'<div class="findHead">���������</div>'.
		'<input type="hidden" id="category_id">'.

		'<div class="findHead">���������</div>'.
		'<input type="hidden" id="worker_id">'.

		'<input type="hidden" id="year">'.
		'<input type="hidden" id="mon" value="'._num(strftime('%m')).'">'.
		'<script type="text/javascript">'.
			'var EXPENSE_WORKER='.query_workerSelJson($sql_worker, GLOBAL_MYSQL_CONNECT).';'.
		'</script>';
}//expense_right()
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
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `dtime_add` LIKE '".$filter['year']."-%'".
		($filter['invoice_id'] ? " AND `invoice_id`=".$filter['invoice_id'] : '').
		($filter['category_id'] ? " AND `category_id`=".($filter['category_id'] == -1 ? 0 : $filter['category_id']) : '').
		($filter['worker_id'] ? " AND `worker_id`=".$filter['worker_id'] : '')."
			GROUP BY DATE_FORMAT(`dtime_add`,'%m')
			ORDER BY `dtime_add` ASC";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$res[_num($r['month'])] .= '<span>'._sumSpace(round($r['sum'])).'</span>';

	return $res;
//	return _radio('mon', $res, $filter['mon'], 1);
}//expenseMonthSum()
function expense_graf($filter, $i='json') {//������ ���� �������� �� ����������
	$sql = "SELECT
				`id`,
				`name`,
				0 `sum`
			FROM `_money_expense_category`
			WHERE `app_id` IN (".APP_ID.",0)
			  AND `ws_id` IN(".WS_ID.",0)
			ORDER BY `sort`";
	$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT);
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
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
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
}//expense_graf()
function expense_spisok($v=array()) {
	$filter = expenseFilter($v);

	define('PAGE1', $filter['page'] == 1);

	$cond = "`app_id`=".APP_ID." AND `ws_id`=".WS_ID." AND !`deleted`";

	if($filter['invoice_id'])
		$cond .= " AND `invoice_id`=".$filter['invoice_id'];
	if($filter['worker_id'])
		$cond .= " AND `worker_id`=".$filter['worker_id'];
	if($filter['category_id'])
		$cond .= " AND `category_id`=".($filter['category_id'] == -1 ? 0 : $filter['category_id']);
	$cond .= " AND `dtime_add` LIKE '".$filter['year']."-".($filter['mon'] < 10 ? 0 : '').$filter['mon']."-%'";

	$sql = "SELECT
				COUNT(`id`) AS `all`,
				SUM(`sum`) AS `sum`
			FROM `_money_expense`
			WHERE ".$cond;
	$send = query_assoc($sql, GLOBAL_MYSQL_CONNECT);

	$filter['cond'] = $cond;
	$send['filter'] = $filter;

	$js = !PAGE1 ? '' :
		'<script type="text/javascript">'.
			'var EXPENSE={'.
				'limit:'.$filter['limit'].','.
				'invoice_id:'.$filter['invoice_id'].','.
				'worker_id:'.$filter['worker_id'].','.
				'category_id:'.$filter['category_id'].','.
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
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
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
function expenseAbout($r) {//�������� ��� ��������
	return
		'<span class="type">'._invoice($r['invoice_id']).'</span>: '.
		($r['category_id'] ?
			'<b class="cat">'._expense($r['category_id']).
			($r['about'] || $r['worker_id'] ? ': ' : '').'</b>'
		: '').
		($r['worker_id'] ?
			   _viewer($r['worker_id'], 'viewer_link_zp').
				($r['about'] ? ', ' : '')
		: '').
		$r['about'].
		($r['attach_id'] ? '<div>����: '.$r['attach_link'].'</div>' : '');
}//expenseAbout()


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
}//income_all()



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
		'<div class="headName">����� �������� �� ���� �� '._monthDef(MON, 1).' '.YEAR.'</div>'.
		'<div class="inc-path">'.$path.'</div>'.
		'<table class="_spisok sums">'.
		'<tr><th>�����'.
		'<th>�����'.
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
}//income_insert()
*/



function _invoice($id=0, $i='name') {//��������� ������ ������ �� ����
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

	//��� �����
	if(!$id)
		return $arr;

	//������������ id �����
	if(!_num($id))
		die('Error: incoice_id <b>'.$id.'</b> not correct');

	//����������� id �����
	if(!isset($arr[$id]))
		die('Error: no invoice_id <b>'.$id.'</b> in _invoice');

	//������� ������ ���� ������
	if($i == 'all')
		return $arr[$id];

	//����������� ���� �����
	if(!isset($arr[$id][$i]))
		return '<span class="red">����������� ���� �����: <b>'.$i.'</b></span>';

	return $arr[$id][$i];
}//_invoice()
function _invoiceBalans($invoice_id, $start=false) {// ��������� �������� ������� �����
	if($start === false)
		$start = _invoice($invoice_id, 'start');

	//�������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`confirm`
			  AND !`deleted`
			  AND `invoice_id`=".$invoice_id;
	$income = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//�������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `invoice_id`=".$invoice_id;
	$expense = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//��������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_refund`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `invoice_id`=".$invoice_id;
	$refund = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//�������: ����-�����������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_invoice_transfer`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `invoice_id_from`=".$invoice_id;
	$from = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//�������: ����-����������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_invoice_transfer`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `invoice_id_to`=".$invoice_id;
	$to = query_value($sql, GLOBAL_MYSQL_CONNECT);

	return round($income - $expense - $refund - $from + $to - $start, 2);
}//_invoiceBalans()
function invoice() {//�������� �� ������� ������ � ���������� ����� �������
	return
		'<div id="money-invoice">'.
			'<div class="headName">'.
				'��������� �����'.
				'<a class="add" id="transfer-add">������� ����� �������</a>'.
				'<span>::</span>'.
				'<a href="'.URL.'&p=setup&d=invoice" class="add">���������� �������</a>'.
			'</div>'.
			'<div id="invoice-spisok">'.invoice_spisok().'</div>'.
		(RULE_INVOICE_TRANSFER ?
			'<div class="headName">������� ��������� ����� �������</div>'.
			'<div id="transfer-spisok">'.invoice_transfer_spisok().'</div>'
		: '').
		'</div>';
}//invoice()
function invoice_spisok() {
	$invoice = _invoice();
	if(empty($invoice))
		return '����� �� ����������.';

	$send = '<table class="_spisok">';
	foreach($invoice as $r) {
		if($r['deleted'])
			continue;
		$send .=
			'<tr>'.
				'<td class="name">'.
					'<b>'.$r['name'].'</b>'.
					'<div class="about">'.$r['about'].'</div>'.
			($r['start'] != -1 ?
				'<td class="balans"><b>'._sumSpace(_invoiceBalans($r['id'])).'</b> ���.'.
				'<td><div val="1:'.$r['id'].'" class="_balans-show img_note'._tooltip('���������� ������� ��������', -95).'</div>'
				:
				'<td><a class="invoice-set" val="'.$r['id'].'">����������<br />������� �����</a>'
			);
//			(VIEWER_ADMIN && $r['start'] != -1 ?
//				'<td><a class="invoice-reset" val="'.$r['id'].'">��������<br />�����</a>'
//			: '');
	}
	$send .= '</table>';
	return $send;
}//invoice_spisok()
function invoice_transfer_spisok($v=array()) {//������� ��������� �� ������
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
		return '��������� ���.';

	$sql = "SELECT *
	        FROM `_money_invoice_transfer`
	        WHERE ".$cond."
	        ORDER BY `id` DESC
	        LIMIT "._start($filter).",".$filter['limit'];
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$send = $filter['page'] != 1 ? '' :
		'<table class="_spisok">'.
			'<tr>'.
				'<th>C����'.
				'<th>�� �����'.
				'<th>�� ����'.
				'<th>��������'.
				'<th>����'.
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

	// ����� ��������
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

	// ����� ��������
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

	// ������ �� ��������� ����
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
}//invoice_info_balans_day()

function _balans($v) {//�������� ������ � �������
	$ws_id = _num(@$v['ws_id']) ? _num($v['ws_id']) : WS_ID;
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
		$balans = _invoiceBalans($unit_id);

		// ��������� ����� ����� � �����-�����������
		if($invoice_transfer_id) {
			$sql = "SELECT * FROM `_money_invoice_transfer` WHERE `id`=".$invoice_transfer_id;
			$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT);
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
		$balans = salaryWorkerBalans($unit_id, 0, $ws_id);
	}


	$sql = "INSERT INTO `_balans` (
				`app_id`,
				`ws_id`,

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
				".APP_ID.",
				".$ws_id.",

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
	query($sql, GLOBAL_MYSQL_CONNECT);
}//_balans()
function _balansAction($id, $i='name') {//��������� ������ �������� �������� �� ������ �� ����
	$key = CACHE_PREFIX.'balans_action';
	$arr = xcache_get($key);
	if(empty($arr)) {
		$sql = "SELECT * FROM `_balans_action`";
		$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT);
		xcache_set($key, $arr, 86400);
	}

	if(!isset($arr[$id]))
		return '<span class="red">����������� �������� �������: <b>'.$id.'</b></span>';

	if(!isset($arr[$id][$i]))
		return '<span class="red">����������� ���� �������� �������: <b>'.$i.'</b></span>';

	return $arr[$id][$i];
}//_balansAction()

function balansFilter($v) {
	return array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 50,
		'category_id' => _num(@$v['category_id']),
		'unit_id' => _num(@$v['unit_id'])
	);
}//balansFilter()
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
}//balans_show()
function balans_show_category($v) {
	switch($v['category_id']) {
		case 1: //��������� �����
			$sql = "SELECT *
					FROM `_money_invoice`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND !`deleted`
					  AND `id`=".$v['unit_id'];
			if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
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
}//balans_show_category()
function balans_show_spisok($filter) {
	define('PAGE1', $filter['page'] == 1);

	$cond = "`app_id`=".APP_ID."
		 AND `ws_id`=".WS_ID."
		 AND `category_id`=".$filter['category_id']."
		 AND `unit_id`=".$filter['unit_id'];

	$send = array(
		'all' => 0,
		'spisok' => '<div class="_empty">������� ���.</div>',
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

	$spisok = _schetValToList($spisok);
	$spisok = _zayavValToList($spisok);
	$spisok = _dogovorValToList($spisok);

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
		$income = _zayavValToList($income);
		$income = _dogovorValToList($income);
		$income = _schetValToList($income);
	}

	$expense = array();
	if($expense_ids = _idsGet($spisok, 'expense_id')) {
		$sql = "SELECT * FROM `_money_expense` WHERE `id` IN (".$expense_ids.")";
		$expense = query_arr($sql, GLOBAL_MYSQL_CONNECT);
		$expense = _viewer($expense);
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

		$send['spisok'] .=
			'<tr><td class="action">'._balansAction($r['action_id']).
				'<td class="sum">'.$sum.$sum_diff.
				'<td class="balans">'._sumSpace($r['balans']).
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








/* --- ����� �� ������ --- */

function _schetQuery($id, $withDeleted=0) {//������ ������ �� ����� �������
	$sql = "SELECT *
			FROM `_schet`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID.
			  ($withDeleted ? '' : ' AND !`deleted`')."
			  AND `id`=".$id;
	return query_assoc($sql, GLOBAL_MYSQL_CONNECT);
}//_schetQuery()
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
			  AND `ws_id`=".WS_ID."
			  AND `id` IN (".implode(',', array_keys($ids)).")";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		foreach($arrIds[$r['id']] as $id)
			$arr[$id] += _schetValForm($r);
	return $arr;
}//_schetValToList()
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
}//_schetValForm()
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
				'<div class="headName">����� �� ������</div>'.
				'<div id="spisok">'.$data['spisok'].'</div>'.
			'<td class="right">'._schet_right().
	'</table>';
}//_schet()
function _schet_spisok($v=array()) {
	$filter = _schetFilter($v);
	$filter = _filterJs('SCHET', $filter);

	$cond = "`app_id`=".APP_ID."
		 AND `ws_id`=".WS_ID."
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
	$send = query_assoc($sql, GLOBAL_MYSQL_CONNECT);
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
	$q = query($sql, GLOBAL_MYSQL_CONNECT);

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
}//_schet_unit()
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
}//_schet_right()
function _schetPayCorrect($schet_id) {//�������� � ����� ����� ��������, ������� ���� ����������� ��� �������� ��� �������� �������
	if(empty($schet_id))
		return;

	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `schet_id`=".$schet_id;
	$sum = query_value($sql, GLOBAL_MYSQL_CONNECT);
	$sql = "UPDATE `_schet`
			SET `paid_sum`=".$sum."
			WHERE `id`=".$schet_id;
	query($sql, GLOBAL_MYSQL_CONNECT);
}//_schetPayCorrect()
function _schetToZayav($zayav) {//����������� ������ ������ � ������� ������ ������
	$sql = "SELECT *
			FROM `_schet`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id` IN (".implode(',', array_keys($zayav)).")";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q)) {
		$form = _schetValForm($r);
		$zayav[$r['zayav_id']]['schet'] .= $form['schet_link'];
	}
	return $zayav;
}//_schetToZayav()







/* --- ����� ������ �������� � ��������� � ���������� � ������ --- */
function _zayavInfoMoney($zayav_id) {
	return '<div id="_zayav-money">'._zayavInfoMoney_spisok($zayav_id).'</div>';
}//_zayavInfoMoney()
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
		'<div class="headBlue">'.
			'������� � ��������'.
			'<a class="add _refund-add'._tooltip('���������� ������� �������� �������', -215, 'r').'�������</a>'.
			'<em>::</em>'.
			'<a class="add _income-add">������ �����</a>'.
		'</div>'.
		'<table class="_spisok">'.$spisok.'</table>';
}//_zayavInfoMoney_spisok()

