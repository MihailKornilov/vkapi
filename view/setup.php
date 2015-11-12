<?php
// --- vk Global ---
function _setupApp() {//���������� �������� ���������� � �������� �� ����������� ������������ ��������
	//��������� ������ �������� ���������� �� ����
	$key = CACHE_PREFIX.'setup'.APP_ID;
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT `key`,`value` FROM `_setup` WHERE `app_id`=".APP_ID;
		$arr = query_ass($sql, GLOBAL_MYSQL_CONNECT);
		xcache_set($key, $arr, 86400);
	}
	foreach($arr as $key => $value)
		define($key, $value);

	// �������� ������� ������������ ��������:
	_setupValue('VERSION', 0, '������ �������� � ������');
	_setupValue('GLOBAL_VALUES', 0, '������ ����� global_values.js');
	_setupValue('G_VALUES', 0, '������ ����� G_values.js');
}//_setupApp()
function _setupValue($key, $v='', $about='') {//��������� �������� ��������� � ��������, ���� � ��� � ������� ����
	if(defined($key))
		return true;
	$sql = "SELECT `value`
			FROM `_setup`
			WHERE `app_id`=".APP_ID."
			  AND `key`='".$key."'
			LIMIT 1";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	if(!mysql_num_rows($q)) {
		$sql = "INSERT INTO `_setup` (
					`app_id`,
					`key`,
					`value`,
					`about`
				) VALUES (
					".APP_ID.",
					'".strtoupper($key)."',
					'".addslashes($v)."',
					'".addslashes($about)."'
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);
		return _setupValue($key);
	}
	$r = mysql_fetch_assoc($q);
	define($key, $r['value']);

	return $r['value'];
}//_setupValue()





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
		'invoice' => '��������� �����',
		'expense' => '��������� ��������'
	) + (function_exists('setup') ? setup() : array());

	$sub = array(
		'worker' => 'rule'
	);

	if(!RULE_SETUP_WORKER)
		unset($page['worker']);

	if(!RULE_SETUP_INVOICE)
		unset($page['invoice']);

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
}//_setup()

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
	'</div>';
}//setup_my()

function setup_worker() {
	if(!RULE_SETUP_WORKER)
		return _err('������������ ����: ���������� ������������');

	return
		'<div id="setup_worker">'.
			'<div class="headName">���������� ������������<a class="add">����� ���������</a></div>'.
			'<div id="spisok">'.setup_worker_spisok().'</div>'.
		'</div>';
}//setup_worker()
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
			'</table>';
	}
	return $send;
}//setup_worker_spisok()
function setup_worker_rule($viewer_id) {
	if(!RULE_SETUP_WORKER)
		return _err('������������ ����: ���������� ������������.');

	$u = _viewer($viewer_id);
	if($u['viewer_ws_id'] != WS_ID)
		return _err('���������� �� ����������.');

	if(!$u['viewer_worker'])
		return _err('������������ <b>'.$u['viewer_name'].'</b><br />��� �� �������� �����������.');

	$rule = _viewerRule($viewer_id);
	return
	'<script type="text/javascript">var RULE_VIEWER_ID='.$viewer_id.';</script>'.
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


	(!$u['viewer_admin'] && $u['pin'] ?
		'<div class="headName">���-���</div>'.
		'<div class="vkButton" id="pin-clear"><button>�������� ���-���</button></div>'
	: '').
/*		'<div class="headName">�������������</div>'.
			'<table class="rtab">'.
				'<tr><td class="lab">������� �� ��������:<td><input type="text" id="rules_money_procent" value="'.$rule['RULES_MONEY_PROCENT'].'" maxlength="2" />'.
				'<tr><td><td><div class="vkButton dop-save"><button>���������</button></div>'.
			'</table.
*/
	(!$u['viewer_admin'] && $viewer_id < VIEWER_MAX && RULE_SETUP_RULES ?
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
				'<tr><td class="label">����� ������� ��������:<td>'._check('RULE_HISTORY_VIEW', '', $rule['RULE_HISTORY_VIEW']).
				'<tr><td class="label">����� ������� ��������� �� ��������� ������:<td>'._check('RULE_INVOICE_TRANSFER', '', $rule['RULE_INVOICE_TRANSFER']).
				'<tr><td class="label">����� ������ �������:<td>'._check('RULE_INCOME_VIEW', '', $rule['RULE_INCOME_VIEW']).
			'</table>'.
		'</div>'
	: '').

	'</div>';

}//setup_worker_rule()
function setup_worker_rule_save($post) {//���������� ��������� ����� ����������
	if(!RULE_SETUP_RULES)
		return false;

	if(!$viewer_id = _num($post['viewer_id']))
		return false;

	$u = _viewer($viewer_id);
	if($u['viewer_admin'])
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

		xcache_unset(CACHE_PREFIX.'viewer_'.$viewer_id);
		xcache_unset(CACHE_PREFIX.'viewer_rule_'.$viewer_id);
	}
	return true;
}//setup_worker_rule_save()
function _workerRuleQuery($viewer_id, $key, $v) {//��������� �������� ����� ���������� � ����
	$sql = "UPDATE `_vkuser_rule`
				SET `value`=".$v."
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$viewer_id."
				  AND `key`='".$key."'";
	query($sql, GLOBAL_MYSQL_CONNECT);
}//_workerRuleQuery()

function setup_rekvisit() {
	$sql = "SELECT * FROM `workshop` WHERE `id`=".WS_ID;
	$g = query_assoc($sql);
	return
		'<div id="setup_rekvisit">'.
			'<div class="headName">��������� �����������</div>'.
			'<table class="t">'.
				'<tr><td class="label top">�������� �����������:<td><textarea id="org_name">'.$g['org_name'].'</textarea>'.
				'<tr><td class="label">����:<td><input type="text" id="ogrn" value="'.$g['ogrn'].'" />'.
				'<tr><td class="label">���:<td><input type="text" id="inn" value="'.$g['inn'].'" />'.
				'<tr><td class="label">���:<td><input type="text" id="kpp" value="'.$g['kpp'].'" />'.
				'<tr><td class="label top">����������� �����:<td><textarea id="adres_yur">'.$g['adres_yur'].'</textarea>'.
				'<tr><td class="label">��������:<td><input type="text" id="telefon" value="'.$g['telefon'].'" />'.
				'<tr><td class="label top">����� �����:<td><textarea id="adres_ofice">'.$g['adres_ofice'].'</textarea>'.
				'<tr><td class="label">����� ������:<td><input type="text" id="time_work" value="'.$g['time_work'].'" />'.
			'</table>'.
			'<div class="headName">���� ����������</div>'.
			'<table class="t">'.
				'<tr><td class="label top">������������ �����:<td><textarea id="bank_name">'.$g['bank_name'].'</textarea>'.
				'<tr><td class="label">���:<td><input type="text" id="bik" value="'.$g['bik'].'" />'.
				'<tr><td class="label">��������� ����:<td><input type="text" id="schet" value="'.$g['schet'].'" />'.
				'<tr><td class="label">����������������� ����:<td><input type="text" id="kor_schet" value="'.$g['kor_schet'].'" />'.
				'<tr><td><td><div class="vkButton"><button>���������</button></div>'.
			'</table>'.
		'</div>';
}//setup_rekvisit()

function setup_invoice() {
	if(!RULE_SETUP_INVOICE)
		return _err('������������ ����: ��������� ������ ��� ����� ��������');
	return
		'<div id="setup_invoice">'.
			'<div class="headName">���������� �������<a class="add">����� ����</a></div>'.
			'<div class="spisok">'.setup_invoice_spisok().'</div>'.
		'</div>';
}//setup_invoice()
function setup_invoice_spisok() {
	$sql = "SELECT *
			FROM `_money_invoice`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	if(!mysql_num_rows($q))
		return '������ ����.';

	$spisok = array();
	while($r = mysql_fetch_assoc($q)) {
		$r['worker'] = array();
		if($r['visible'])
			foreach(explode(',', $r['visible']) as $i)
				$r['worker'][] = _viewer($i, 'viewer_name');
		$spisok[$r['id']] = $r;
	}

	$send =
		'<table class="_spisok">'.
			'<tr><th>������������'.
			'<th>���������<br />��� �����������'.
			'<th>';
	foreach($spisok as $id => $r)
		$send .=
			'<tr val="'.$id.'">'.
				'<td class="name">'.
					'<div>'.$r['name'].'</div>'.
					'<pre>'.$r['about'].'</pre>'.
					($r['confirm_income'] ? '<h6>������������� ����������� �� ����</h6>' : '').
					($r['confirm_transfer'] ? '<h6>��������� ������������� ���������</h6>' : '').
				'<td class="visible">'.
					implode('<br />', $r['worker']).
				'<td class="ed">'.
					'<div class="img_edit"></div>'.
					'<div class="img_del"></div>'.

			'<input type="hidden" class="confirm_income" value="'.$r['confirm_income'].'" />'.
			'<input type="hidden" class="confirm_transfer" value="'.$r['confirm_transfer'].'" />'.
			'<input type="hidden" class="visible_id" value="'.(empty($r['worker']) ? 0 : $r['visible']).'" />';

	$send .= '</table>';

	return $send;
}//setup_invoice_spisok()

function setup_expense() {
	return
		'<div id="setup_expense">'.
			'<div class="headName">��������� �������� �����������<a class="add">����� ���������</a></div>'.
			'<div id="spisok">'.setup_expense_spisok().'</div>'.
		'</div>';
}//setup_expense()
function setup_expense_spisok() {
	$sql = "SELECT *,
				0 `count`
			FROM `_money_expense_category`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			ORDER BY `sort`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	if(!mysql_num_rows($q))
		return '������ ����.';

	$spisok = array();
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']] = $r;

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
				'<th class="worker_use">����������<br />������<br />�����������'.
				'<th class="count">���-��<br />�������'.
				'<th class="ed">'.
		'</table>'.
		'<dl class="_sort" val="_money_expense_category">';

	foreach($spisok as $r)
		$send .= '<dd val="'.$r['id'].'">'.
			'<table class="_spisok">'.
				'<tr><td class="name">'.$r['name'].
					'<td class="worker_use">'.($r['worker_use'] ? '��' : '').
					'<td class="count">'.($r['count'] ? $r['count'] : '').
					'<td class="ed">'.
						'<div class="img_edit'._tooltip('��������', -33).'</div>'.
						(!$r['count'] ? '<div class="img_del"></div>' : '').
			'</table>';
	$send .= '</dl>';
	return $send;
}//setup_expense_spisok()
