<?php
// --- Global ---
function _setup_global() {//��������� ��������-���������� ��� ���� ����������
	$key = CACHE_PREFIX.'setup_global';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT `key`,`value` FROM `_setup_global`";
		$arr = query_ass($sql, GLOBAL_MYSQL_CONNECT);
		xcache_set($key, $arr, 86400);
	}
	foreach($arr as $key => $value)
		define($key, $value);
}



// --- _setup --- ������ �������� ����������
function _setup() {
/*
	$sub:   ����������� ������� ������� ��������.
			��������� � ����:
			array(
				'worker' => 'rule'
			);
			��� ����� ����������� �������� �������: setup_worker_rule()
			���������� ��� ������� ������� $_GET['id'], ������� intval � �� 0.
*/
	$page = array(
		'my' => '��� ���������',
		'worker' => '����������',
		'rekvisit' => '��������� �����������',
		'service' => '���� ������������',
		'expense' => '��������� ��������',
		'zayav_status' => '������� ������',
		'zayav_expense' => 'SA: ������� �� ������',
		'product' => '���� �������'
	) + (function_exists('setup') ? setup() : array());

	$sub = array(
		'worker' => 'rule',
		'service' => 'cartridge',
		'expense' => 'sub',
		'product' => 'sub'
	);

	if(!RULE_SETUP_WORKER)
		unset($page['worker']);

	if(!RULE_SETUP_REKVISIT)
		unset($page['rekvisit']);

	if(!RULE_SETUP_ZAYAV_STATUS)
		unset($page['zayav_status']);

	if(_service('count') < 2)
		unset($page['service']);

	if(!SA)
		unset($page['zayav_expense']);

	$d = empty($_GET['d']) || empty($page[$_GET['d']]) ? 'my' : $_GET['d'];

	$id = _num(@$_GET['id']);
	$func = 'setup_'.$d.(isset($sub[$d]) && $id ? '_'.$sub[$d] : '');
	$left = function_exists($func) ? $func($id) : setup_my();

	$links = '';
	foreach($page as $p => $name)
		$links .= '<a href="'.URL.'&p=setup&d='.$p.'"'.($d == $p ? ' class="sel"' : '').'>'.$name.'</a>';

	return
		'<div id="setup">'.
			'<table class="tabLR">'.
				'<tr><td class="left">'.$left.
					'<td class="right"><div class="rightLink">'.$links.'</div>'.
			'</table>'.
		'</div>';
}

function setup_my() {
	return
	'<div id="setup_my">'.
		'<div class="headName">���-���</div>'.
		'<div class="_info">'.
			'<p><b>���-���</b> ��������� ��� ��������������� ������������� ����� ��������, '.
			'���� ������ ������������ ������� ������ � ����� �������� ���������.'.
			'<br />'.
			'<p>���-��� ����� ����� ������� ������ ����� ����� � ����������, '.
			'� ����� ��� ��������� �������� � ��������� � ������� <b>1-�� ����</b>.'.
			'<br />'.
			'<p>���� �� ������ ���-���, ���������� � ������������, ����� �������� ���.'.
		'</div>'.
	(PIN ?
		'<div class="vkButton" id="pinchange"><button>�������� ���-���</button></div>'.
		'<div class="vkButton" id="pindel"><button>������� ���-���</button></div>'
	:
		'<div class="vkButton" id="pinset"><button>���������� ���-���</button></div>'
	).


		'<div class="headName" id="dop" >�������������</div>'.
		'<table class="bs10">'.
			'<tr><td class="label">���������� �������:<td><input type="hidden" id="RULE_MY_PAY_SHOW_PERIOD" value="'._num(@RULE_MY_PAY_SHOW_PERIOD).'" />'.
		'</table>'.

	'</div>';
}

function setup_worker() {
	if(!RULE_SETUP_WORKER)
		return _err('������������ ����: ���������� ������������');

	return
		'<div id="setup_worker">'.
			'<div class="headName">���������� ������������<a class="add">����� ���������</a></div>'.
			'<div id="spisok">'.setup_worker_spisok().'</div>'.
		'</div>';
}
function setup_worker_spisok() {
	$sql = "SELECT *
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker`
			  AND `viewer_id`!=982006
			ORDER BY `dtime_add`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$send = '';
	while($r = mysql_fetch_assoc($q)) {
		$send .=
			'<table class="unit">'.
				'<tr><td class="photo"><img src="'.$r['photo'].'">'.
					'<td>'.
						'<a class="name" href="'.URL.'&p=setup&d=worker&id='.$r['viewer_id'].'">'.$r['first_name'].' '.$r['last_name'].'</a>'.
						'<div>'.$r['post'].'</div>'.
					  ($r['last_seen'] != '0000-00-00 00:00:00' ? '<div class="activity">�������'.($r['sex'] == 1 ? 'a' : '').' � ���������� '.FullDataTime($r['last_seen']).'</div>' : '').
			'</table>';
	}
	return $send;
}
function setup_worker_rule($viewer_id) {
	if(!RULE_SETUP_WORKER)
		return _err('������������ ����: ���������� ������������.');

	$u = _viewer($viewer_id);
	if($u['viewer_ws_id'] != WS_ID)
		return _err('���������� �� ����������.');

	if(!$u['viewer_worker'])
		return _err('������������ <b>'.$u['viewer_name'].'</b><br />��� �� �������� �����������.');

	$rule = _viewerRule($viewer_id);

	//��������� ������� �������� ���� �����������
	$sql = "SELECT `viewer_id`,`value`
			FROM `_vkuser_rule`
			WHERE `app_id`=".APP_ID."
			  AND `key`='RULE_HISTORY_VIEW'
			  AND `viewer_id`<".VIEWER_MAX;
	$hist_worker_all = query_assJson($sql, GLOBAL_MYSQL_CONNECT);

	return
	'<script type="text/javascript">'.
		'var RULE_VIEWER_ID='.$viewer_id.','.
			'RULE_HISTORY_ALL='.$hist_worker_all.';'.
	'</script>'.
	'<div id="setup_rule">'.
		(!$u['viewer_admin'] ? '<div class="img_del'._tooltip('������� ����������', -119, 'r').'</div>' : '').
		'<table class="utab">'.
			'<tr><td>'.$u['viewer_photo'].
				'<td>'.
					'<div class="name">'.$u['viewer_name'].'</div>'.
					($viewer_id < VIEWER_MAX ? '<a href="http://vk.com/id'.$viewer_id.'" class="vklink" target="_blank">������� �� �������� VK</a>' : '').
					'<a href="'.URL.'&p=report&d=salary&id='.$viewer_id.'" class="vklink">�������� �/�</a>'.
		'</table>'.

		'<div class="headName">������ ����������</div>'.
		'<table class="rtab">'.
			'<tr><td class="lab">�������:<td><input type="text" id="last_name" value="'.$u['viewer_last_name'].'" />'.
			'<tr><td class="lab">���:<td><input type="text" id="first_name" value="'.$u['viewer_first_name'].'" />'.
			'<tr><td class="lab">��������:<td><input type="text" id="middle_name" value="'.$u['viewer_middle_name'].'" />'.
			'<tr><td class="lab">���������:<td><input type="text" id="post" value="'.$u['viewer_post'].'" />'.
			'<tr><td><td><div class="vkButton" id="w-save"><button>���������</button></div>'.
		'</table>'.

	(RULE_SETUP_RULES ?
		'<div class="headName">�������������� ���������</div>'.
		'<table class="rtab">'.
			'<tr><td class="lab"><td>'._check('RULE_SALARY_SHOW', '���������� � ������ �/� �����������', $rule['RULE_SALARY_SHOW']).
			'<tr><td class="lab"><td>'._check('RULE_EXECUTER', '����� ���� ������������ ������', $rule['RULE_EXECUTER']).
			'<tr><td class="lab"><td>'._check('RULE_SALARY_ZAYAV_ON_PAY', '��������� �/� �� ������ ��� ���������� �����', $rule['RULE_SALARY_ZAYAV_ON_PAY']).
			'<tr><td class="lab">��������� ������:'.
				'<td>'._check('RULE_SALARY_BONUS', '', $rule['RULE_SALARY_BONUS']).
					'<span'.($rule['RULE_SALARY_BONUS'] ? '' : ' class="vh"').'>'.
						'<input type="text" id="salary_bonus_sum" maxlength="5" value="'.$u['bonus_sum'].'" />%'.
					'<span>'.
		'</table>'.

	(!$u['viewer_admin'] && $viewer_id < VIEWER_MAX ?

	($u['pin'] ?
		'<div class="headName">���-���</div>'.
		'<div class="vkButton" id="pin-clear"><button>�������� ���-���</button></div>'
	: '').

/*		'<div class="headName">�������������</div>'.
			'<table class="rtab">'.
				'<tr><td class="lab">������� �� ��������:<td><input type="text" id="rules_money_procent" value="'.$rule['RULES_MONEY_PROCENT'].'" maxlength="2" />'.
				'<tr><td><td><div class="vkButton dop-save"><button>���������</button></div>'.
			'</table.
*/

		'<div class="headName">����� � ����������</div>'.
			_check('RULE_APP_ENTER', '��������� ���� � ����������', $rule['RULE_APP_ENTER'], 1).
			'<table class="rtab'.($rule['RULE_APP_ENTER'] ? '' : ' dn').'" id="div-app-enter">'.
				'<tr><td class="label top"><b>������ � ����������:</b>'.
					'<td id="td-rule-setup">'.
						_check('RULE_SETUP_WORKER', '����������', $rule['RULE_SETUP_WORKER']).
						'<div id="div-w-rule"'.($rule['RULE_SETUP_WORKER'] ? '' : ' style="display:none"').'>'.
							_check('RULE_SETUP_RULES', '����� �����������', $rule['RULE_SETUP_RULES']).
						'</div>'.
						_check('RULE_SETUP_REKVISIT', '��������� �����������', $rule['RULE_SETUP_REKVISIT']).
						_check('RULE_SETUP_INVOICE', '��������� �����', $rule['RULE_SETUP_INVOICE']).
						_check('RULE_SETUP_ZAYAV_STATUS', '������� ������', $rule['RULE_SETUP_ZAYAV_STATUS']).
				'<tr><td class="label"><a class="history-view-worker-all'._tooltip('�������� ����� ���� �����������', -20).'����� ������� ��������</a>:'.
					'<td><input type="hidden" id="RULE_HISTORY_VIEW" value="'.$rule['RULE_HISTORY_VIEW'].'" />'.
				'<tr><td><td>'.

				'<tr><td><td><b>������</b>'.
				'<tr><td class="label">����� ������� ��������<br />�� ��������� ������:'.
					'<td>'._check('RULE_INVOICE_HISTORY', '', $rule['RULE_INVOICE_HISTORY']).
				'<tr><td class="label">����� ��������<br />�� ��������� ������:'.
					'<td><input type="hidden" id="RULE_INVOICE_TRANSFER" value="'.$rule['RULE_INVOICE_TRANSFER'].'" />'.
//				'<tr><td class="label">����� ������ �������:<td>'._check('RULE_INCOME_VIEW', '', $rule['RULE_INCOME_VIEW']).
			'</table>'.
		'</div>'

	: '')

	: '').

	'</div>';

}
function setup_worker_rule_save($post) {//���������� ��������� ����� ����������
	if(!RULE_SETUP_RULES)
		return false;

	if(!$viewer_id = _num($post['viewer_id']))
		return false;

	$u = _viewer($viewer_id);
	if($u['viewer_admin'] && $post['op'] != 'RULE_SALARY_SHOW')
		return false;

	if($u['viewer_ws_id'] != WS_ID)
		return false;

	$r = _viewerRule($viewer_id);
	if(!isset($r[$post['op']]))
		return false;

	$key = $post['op'];
	$old = $r[$post['op']];
	$v = $post['v'];
	if($old != $v) {
		_workerRuleQuery($viewer_id, $key, $v);

		_history(array(
			'type_id' => $post['h' . $v],
			'worker_id' => $viewer_id
		));
	}
	return true;
}
function _workerRuleQuery($viewer_id, $key, $v) {//��������� �������� ����� ���������� � ����
	$sql = "SELECT COUNT(*)
			FROM `_vkuser_rule`
			WHERE `app_id`=".APP_ID."
			  AND `key`='".$key."'
			  AND `viewer_id`=".$viewer_id;
	if(!query_value($sql, GLOBAL_MYSQL_CONNECT)) {
		$sql = "INSERT INTO `_vkuser_rule` (
					`app_id`,
					`viewer_id`,
					`key`,
					`value`
				) VALUES (
					".APP_ID.",
					".$viewer_id.",
					'".strtoupper($key)."',
					'".$v."'
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'viewer_'.$viewer_id);
		xcache_unset(CACHE_PREFIX.'viewer_rule_'.$viewer_id);

		return;
	}

	$sql = "UPDATE `_vkuser_rule`
			SET `value`=".$v."
			WHERE `app_id`=".APP_ID."
			  AND `viewer_id`=".$viewer_id."
			  AND `key`='".$key."'";
	query($sql, GLOBAL_MYSQL_CONNECT);

	xcache_unset(CACHE_PREFIX.'viewer_'.$viewer_id);
	xcache_unset(CACHE_PREFIX.'viewer_rule_'.$viewer_id);
}
function _ruleHistoryView($id=false) {
	$arr = array(
		0 => '���',
		1 => '������ ����',
		2 => '��� �������'
	);

	if($id === false)
		return $arr;

	if(!isset($arr[$id]))
		return '����������� id';

	return $arr[$id];
}
function _ruleInvoiceTransfer($id=false) {
	$arr = array(
		0 => '���',
		1 => '������ ����',
		2 => '���'
	);

	if($id === false)
		return $arr;

	if(!isset($arr[$id]))
		return '����������� id';

	return $arr[$id];
}

function setup_rekvisit() {
	if(!RULE_SETUP_REKVISIT)
		return _err('������������ ����: ��������� �����������');

	$sql = "SELECT *
			FROM `_ws`
			WHERE `app_id`=".APP_ID."
			  AND `id`=".WS_ID;
	$g = query_assoc($sql, GLOBAL_MYSQL_CONNECT);
	return
		'<div id="setup_rekvisit">'.
			'<div class="headName">��������� �����������</div>'.
			'<table class="t">'.
				'<tr><td class="label topi">�������� �����������:<td><textarea id="name">'.$g['name'].'</textarea>'.
				'<tr><td class="label topi">������������ ������������ ����:<td><textarea id="name_yur">'.$g['name_yur'].'</textarea>'.
				'<tr><td class="label">����:<td><input type="text" id="ogrn" value="'.$g['ogrn'].'" />'.
				'<tr><td class="label">���:<td><input type="text" id="inn" value="'.$g['inn'].'" />'.
				'<tr><td class="label">���:<td><input type="text" id="kpp" value="'.$g['kpp'].'" />'.
				'<tr><td class="label">���������� ��������:<td><input type="text" id="phone" value="'.$g['phone'].'" />'.
				'<tr><td class="label">����:<td><input type="text" id="fax" value="'.$g['fax'].'" />'.
				'<tr><td class="label topi">����������� �����:<td><textarea id="adres_yur">'.$g['adres_yur'].'</textarea>'.
				'<tr><td class="label topi">����� �����:<td><textarea id="adres_ofice">'.$g['adres_ofice'].'</textarea>'.
				'<tr><td class="label">����� ������:<td><input type="text" id="time_work" value="'.$g['time_work'].'" />'.
			'</table>'.
			'<div class="headName">���� ����������</div>'.
			'<table class="t">'.
				'<tr><td class="label topi">������������ �����:<td><textarea id="bank_name">'.$g['bank_name'].'</textarea>'.
				'<tr><td class="label">���:<td><input type="text" id="bank_bik" value="'.$g['bank_bik'].'" />'.
				'<tr><td class="label">��������� ����:<td><input type="text" id="bank_account" value="'.$g['bank_account'].'" />'.
				'<tr><td class="label">����������������� ����:<td><input type="text" id="bank_account_corr" value="'.$g['bank_account_corr'].'" />'.
				'<tr><td><td><div class="vkButton"><button>���������</button></div>'.
			'</table>'.
		'</div>';
}

function setup_service() {
	$sql = "SELECT
				*,
				0 `active`
			FROM `_zayav_type`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$sql = "SELECT `type_id`
			FROM `_zayav_type_active`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID;
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['type_id']]['active'] = 1;

	$send = '';
	foreach($spisok as $r) {
		$send .=
		'<div class="unit'.($r['active'] ? ' on' : '').'" val="'.$r['id'].'">'.
			(SA ? _iconEdit() : '').
			'<input type="hidden" class="name" value="'.$r['name'].'">'.
			'<h1>'.$r['head'].'</h1>'.
			'<h2>'.$r['about'].'</h2>'.
			'<h3><a class="service-toggle">��������</a></h3>'.
			'<h4>'.
				($r['id'] == 2 ? '<a href="'.URL.'&p=setup&d=service&d1=cartridge&id=1">���������</a> :: ' : '').
				'<a class="service-toggle">���������</a>'.
			'</h4>'.
		'</div>';
	}

	return
	'<div id="setup-service">'.
		'<div class="headName">���� ������������</div>'.
		$send.
	'</div>';
}

function setup_expense() {
	return
		'<div id="setup_expense">'.
			'<div class="headName">��������� �������� �����������<a class="add">����� ���������</a></div>'.
			'<div id="spisok">'.setup_expense_spisok().'</div>'.
		'</div>';
}
function setup_expense_spisok() {
	$sql = "SELECT
				*,
				0 `sub`,
				0 `count`
			FROM `_money_expense_category`
			WHERE (`app_id`=".APP_ID." OR !`app_id`)
			  AND (`ws_id`=".WS_ID." OR !`ws_id`)
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '������ ����.';

	//���������� ������������
	$sql = "SELECT
				DISTINCT `category_id`,
				COUNT(`id`) `sub`
			FROM `_money_expense_category_sub`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			GROUP BY `category_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['category_id']]['sub'] = $r['sub'];

	$sql = "SELECT
				DISTINCT `category_id`,
				COUNT(`id`) `count`
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `category_id`
			GROUP BY `category_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['category_id']]['count'] = $r['count'];

	$send =
		'<table class="_spisok">'.
			'<tr><th class="name">������������'.
				'<th class="sub">���-��<br />���-<br />���������'.
				'<th class="count">���-��<br />�������'.
				'<th class="ed">'.
		'</table>'.
		'<dl class="_sort" val="_money_expense_category">';

	foreach($spisok as $id => $r)
		$send .= '<dd val="'.$id.'">'.
			'<table class="_spisok">'.
				'<tr><td class="td-name">'.
						($id == 1 ? '<span class="name">'.$r['name'].'</span>' :
									'<a class="name" href="'.URL.'&p=setup&d=expense&id='.$id.'">'.$r['name'].'</a>'
						).
					'<td class="sub">'.($r['sub'] ? $r['sub'] : '').
					'<td class="count">'.($r['count'] ? $r['count'] : '').
					'<td class="ed">'.
						($id != 1 ? _iconEdit($r) : '').
						($id != 1 && !$r['count'] ? _iconDel($r) : '').
			'</table>';
	$send .= '</dl>';
	return $send;
}
function setup_expense_sub($id) {
	$sql = "SELECT *
			FROM `_money_expense_category`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `id`!=1
			  AND `id`=".$id;
	if(!$cat = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		return '��������� id = '.$id.' �� ����������.';

	return
	'<script type="text/javascript">var CAT_ID='.$id.';</script>'.
	'<div id="setup_expense_sub">'.
		'<a href="'.URL.'&p=setup&d=expense"><< ����� � ���������� ��������</a>'.
		'<div class="headName">������ ������������ ��������<a class="add">��������</a></div>'.
		'<div id="cat-name">'.$cat['name'].'</div>'.
		'<div id="spisok">'.setup_expense_sub_spisok($id).'</div>'.
	'</div>';
}
function setup_expense_sub_spisok($id) {
	$sql = "SELECT
				*
			FROM `_money_expense_category_sub`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `category_id`=".$id."
			ORDER BY `name`";
	$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT);
	if(empty($arr))
		return '������ ����.';

	$send = '<table class="_spisok">'.
				 '<tr><th>������������'.
					 '<th>';
	foreach($arr as $r)
		$send .= '<tr val="'.$r['id'].'">'.
			 '<td class="name">'.$r['name'].
			 '<td class="ed">'._iconEdit($r)._iconDel($r);

	$send .= '</table>';

	return $send;
}


function setup_zayav_status() {
	return
		'<div id="setup_zayav_status">'.
			'<div class="headName">������� ������<a class="add status-add">����� ������</a></div>'.
			'<div id="status-spisok">'.setup_zayav_status_spisok().'</div>'.
		'</div>'.
		'<script type="text/javascript">'.
			'var '._service('const_js', _service('current')).';'.
		'</script>';
}
function setup_zayav_status_spisok() {
	_service('const_define', _service('current'));

	$sql = "SELECT
	            *,
	            0 `next`
			FROM `_zayav_status`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		$spisok = setup_zayav_status_default();

	$spisok = setup_zayav_status_next($spisok);

	$send =
		'<table class="_spisok">'.
			'<tr><th class="name">������������'.
				'<th class="ed">'.
		'</table>'.
		'<dl class="_sort" val="_zayav_status">';

	foreach($spisok as $r)
		$send .= '<dd val="'.$r['id'].'">'.
			'<table class="_spisok">'.
				'<tr><td class="name'.($r['default'] ? ' b' : '').'" style="background-color:#'.$r['color'].'" val="'.$r['color'].'">'.
						'<span>'.$r['name'].'</span>'.
						'<div class="about">'.$r['about'].'</div>'.
		 ($r['nouse'] ? '<div class="dop">�� ������������ ��������</div>' : '').
		  ($r['hide'] ? '<div class="dop">�������� ������ �� ������ ������</div>' : '').
	  ($r['executer'] ? '<div class="dop">��������� �����������</div>' : '').
		  ($r['srok'] ? '<div class="dop">�������� ����</div>' : '').
	  ($r['day_fact'] ? '<div class="dop">�������� ����������� ����</div>' : '').
	   ($r['accrual'] ? '<div class="dop">������� ����������</div>' : '').
		($r['remind'] ? '<div class="dop">��������� �����������</div>' : '').
					'<td class="ed">'.
						_iconEdit($r + array('class'=>'status-edit')).
						_iconDel($r).
			'</table>'.
			'<input type="hidden" class="nouse" value="'.$r['nouse'].'" />'.
			'<input type="hidden" class="hide" value="'.$r['hide'].'" />'.
			'<input type="hidden" class="next" value="'.$r['next'].'" />'.
			'<input type="hidden" class="executer" value="'.$r['executer'].'" />'.
			'<input type="hidden" class="srok" value="'.$r['srok'].'" />'.
			'<input type="hidden" class="accrual" value="'.$r['accrual'].'" />'.
			'<input type="hidden" class="remind" value="'.$r['remind'].'" />'.
			'<input type="hidden" class="day_fact" value="'.$r['day_fact'].'" />';
	$send .= '</dl>';
	setup_zayav_status_next_js();
	return $send;
}
function setup_zayav_status_default() {//������������ ������ �������� �� ���������
	$sql = "SELECT *
			FROM `_zayav_status_default`
			ORDER BY `id`";
	$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	$values = array();
	foreach($spisok as $id => $r)
		$values[] = "(
			".APP_ID.",
			".WS_ID.",
			'".$r['name']."',
			'".$r['about']."',
			'".$r['color']."',
			".$r['default'].",
			".$id.",
			".$id."
		)";

	$sql = "INSERT INTO `_zayav_status` (
				`app_id`,
				`ws_id`,
				`name`,
				`about`,
				`color`,
				`default`,
				`sort`,
				`id_old`
			) VALUES ".implode(',', $values);
	query($sql, GLOBAL_MYSQL_CONNECT);

	//���������� ����� �������� � �������
	$sql = "UPDATE `_zayav` `z`
			SET `status_id`=(

				SELECT `id`
				FROM `_zayav_status`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `id_old`=`z`.`status_id`
				LIMIT 1

			)
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `status_id`";
	query($sql, GLOBAL_MYSQL_CONNECT);

	//���������� ����� �������� � �������
	$sql = "UPDATE `_history` `h`
			SET `v1`=(
					SELECT `id`
					FROM `_zayav_status`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND `id_old`=`h`.`v1`
					LIMIT 1
				),
				`v2`=(
					SELECT `id`
					FROM `_zayav_status`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND `id_old`=`h`.`v2`
					LIMIT 1
				)
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `type_id`=71";
	query($sql, GLOBAL_MYSQL_CONNECT);

	xcache_unset(CACHE_PREFIX.'zayav_status'.WS_ID);
	_wsJsValues();

	$sql = "SELECT
	            *,
	            0 `next`
			FROM `_zayav_status`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			ORDER BY `sort`";
	return query_arr($sql, GLOBAL_MYSQL_CONNECT);
}
function setup_zayav_status_next($spisok) {//��������� ids ��������� ��������
	$sql = "SELECT *
			FROM `_zayav_status_next`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['status_id']]['next'] .= ','.$r['next_id'];

	foreach($spisok as $id => $r)
		if($r['next'])
			$spisok[$id]['next'] = substr($r['next'], 2, strlen($r['next']) - 2);

	return $spisok;
}
function setup_zayav_status_next_js() {//��������� ids ��������� �������� ��� values
	$spisok = array();
	$sql = "SELECT *
			FROM `_zayav_status_next`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['status_id']][$r['next_id']] = 1;

	if(!$spisok)
		return '{}';

	return str_replace('"', '', json_encode($spisok));
}
function setupZayavStatusDefaultDrop($default) {//����� ������� �� ���������, ���� ��������������� ����� ���������
	if(!$default)
		return false;

	$sql = "UPDATE `_zayav_status`
			SET `default`=0,
				`nouse`=0
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `default`";
	query($sql, GLOBAL_MYSQL_CONNECT);

	return true;
}


function setup_product() {
	return
	'<div id="setup_product">'.
		'<div class="headName">��������� ����� �������<a class="add">��������</a></div>'.
		'<div class="spisok">'.setup_product_spisok().'</div>'.
	'</div>';
}
function setup_product_spisok() {
	$sql = "SELECT
				*,
				0 `sub`,
				0 `zayav`
			FROM `_product`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			ORDER BY `name`";
	$product = query_arr($sql, GLOBAL_MYSQL_CONNECT);
	if(empty($product))
		return '������ ����.';

	$sql = "SELECT `product_id`,
				   COUNT(`id`) AS `sub`
			FROM `_product_sub`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			GROUP BY `product_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$product[$r['product_id']]['sub'] = $r['sub'];

	$sql = "SELECT `product_id`,
				   COUNT(`id`) AS `zayav`
			FROM `_zayav_product`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			GROUP BY `product_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$product[$r['product_id']]['zayav'] = $r['zayav'];

	$send = '<table class="_spisok">'.
				'<tr><th>������������'.
					'<th>�������'.
					'<th>���-��<br />������'.
					'<th>';
	foreach($product as $id => $r)
		$send .= '<tr val="'.$id.'">'.
					'<td class="name"><a href="'.URL.'&p=setup&d=product&id='.$id.'">'.$r['name'].'</a>'.
					'<td class="sub">'.($r['sub'] ? $r['sub'] : '').
					'<td class="zayav">'.($r['zayav'] ? $r['zayav'] : '').
					'<td><div class="img_edit"></div>'.
						($r['sub'] || $r['zayav'] ? '' :'<div class="img_del"></div>');
	$send .= '</table>';
	return $send;
}
function setup_product_sub($product_id) {
	$sql = "SELECT *
			FROM `_product`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `id`=".$product_id;
	if(!$pr = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		return '������� id = '.$product_id.' �� ����������.';

	return
	'<script type="text/javascript">var PRODUCT_ID='.$product_id.';</script>'.
	'<div id="setup_product_sub">'.
		'<a href="'.URL.'&p=setup&d=product"><< ����� � ����� �������</a>'.
		'<div class="headName">������ �������� ������� ��� "'.$pr['name'].'"<a class="add">��������</a></div>'.
		'<div class="spisok">'.setup_product_sub_spisok($product_id).'</div>'.
	'</div>';
}
function setup_product_sub_spisok($product_id) {
	$sql = "SELECT
				*,
				0 `zayav`
			FROM `_product_sub`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `product_id`=".$product_id."
			ORDER BY `name`";
	$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT);
	if(empty($arr))
		return '������ ����.';

	$sql = "SELECT
				`product_sub_id`,
				COUNT(`id`) `zayav`
			FROM `_zayav_product`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `product_id`=".$product_id."
			  AND `product_sub_id`
			GROUP BY `product_sub_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$arr[$r['product_sub_id']]['zayav'] = $r['zayav'];

	$send = '<table class="_spisok">'.
				 '<tr><th>������������'.
					 '<th>���-��<br />������'.
					 '<th>';
	foreach($arr as $id => $r)
		$send .= '<tr val="'.$r['id'].'">'.
			 '<td class="name">'.$r['name'].
			 '<td class="zayav">'.($r['zayav'] ? $r['zayav'] : '').
			 '<td><div class="img_edit"></div>'.
					($r['zayav'] ? '' : '<div class="img_del"></div>');
		$send .= '</table>';
	return $send;
}


function setup_zayav_expense() {//��������� �������� �� ������
	if(!SA)
		return '';

	return
	'<div id="setup_zayav_expense">'.
		'<div class="headName">��������� ��������� �������� �� ������<a class="add">��������</a></div>'.
		'<div id="spisok">'.setup_zayav_expense_spisok().'</div>'.
	'</div>';
}
function setup_zayav_expense_spisok() {
	if(!SA)
		return '';

	$sql = "SELECT
				*,
				0 `use`
			FROM `_zayav_expense_category`
			WHERE `app_id`=".APP_ID."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '������ ����.';

	$sql = "SELECT
				DISTINCT `category_id`,
				COUNT(`id`) `use`
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `category_id`
			GROUP BY `category_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['category_id']]['use'] = $r['use'];

	$send =
	'<table class="_spisok">'.
		'<tr><th class="name">������������'.
			'<th class="dop">�������������� ����'.
			'<th class="use">���-��<br />�������'.
			'<th class="ed">'.
	'</table>'.
	'<dl class="_sort" val="_zayav_expense_category">';
	foreach($spisok as $id => $r)
		$send .=
		'<dd val="'.$id.'">'.
			'<table class="_spisok">'.
				'<tr><td class="name">'.$r['name'].
					'<td class="dop">'.
						($r['dop'] ? _zayavExpenseDop($r['dop']) : '').
						'<input class="hdop" type="hidden" value="'.$r['dop'].'" />'.
					'<td class="use">'.($r['use'] ? $r['use'] : '').
					'<td class="ed">'.
						_iconEdit().
						(!$r['use'] ? _iconDel() : '').
			'</table>';
	$send .= '</dl>';
	return $send;
}
