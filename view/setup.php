<?php
// --- vk Global ---
function _setupApp() {//���������� �������� ���������� � �������� �� ����������� ������������ ��������
	// ������������ ���������:
	_setupValue('VERSION', 0, '������ �������� � ������');
	_setupValue('G_VALUES', 0, '������ ����� g_values.js');

	$sql = "SELECT * FROM `_setup` WHERE `app_id`=".APP_ID;
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		define($r['key'], $r['value']);
}//_setupApp()
function _setupValue($key, $v='', $about='') {//��������� �������� ��������� � ��������, ���� � ��� � ������� ����
	$sql = "SELECT `value`
			FROM `_setup`
			WHERE `app_id`=".APP_ID."
			  AND `key`='".$key."'";
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
	return $r['value'];
}//_setupValue()





// --- _setup --- ������ �������� ����������
function _setup($v, $sub=array()) {
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
		'worker' => '����������'
	) + $v;

	$sub += array(
		'worker' => 'rule'
	);

	if(!RULE_SETUP_WORKER || !VIEWER_ADMIN)
		unset($page['worker']);

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
			'</table>';
	}
	return $send;
}//setup_worker_spisok()
function setup_worker_rule($viewer_id) {
	if(!RULE_SETUP_WORKER || !VIEWER_ADMIN)
		return '';

	$u = _viewer($viewer_id);
	if($u['viewer_ws_id'] != WS_ID)
		return '���������� �� ����������.';

	$rule = _viewerRule($viewer_id);
	return
	'<script type="text/javascript">var RULE_VIEWER_ID='.$viewer_id.';</script>'.
	'<div id="setup_rule">'.

		'<table class="utab">'.
			'<tr><td>'.$u['viewer_photo'].
				'<td><div class="name">'.$u['viewer_name'].'</div>'.
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