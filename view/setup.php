<?php
// --- Global ---
function _setup_global() {//��������� ��������-���������� ��� ���� ����������
	$key = CACHE_PREFIX.'setup_global';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT `key`,`value`
				FROM `_setup_global`
				WHERE `app_id` IN (".APP_ID.",0)";
		$arr = query_ass($sql);
		xcache_set($key, $arr, 86400);
	}
	foreach($arr as $key => $value)
		define($key, $value);
}



// --- _setup --- ������ �������� ����������
function _setup() {
	$sub = array(
		'worker' => 'rule',
		'rubric' => 'sub',
		'expense' => 'sub',
		'product' => 'sub'
	);

	$d = empty($_GET['d']) ? 'my' : $_GET['d'];

	$id = _num(@$_GET['id']);
	$func = 'setup_'.$d.(isset($sub[$d]) && $id ? '_'.$sub[$d] : '');
	$left = function_exists($func) ? $func($id) : setup_my();


	$links = '';
	foreach(_menuCache('setup') as $r) {
		//���� �� ���������� ���� ������������
		if($r['p'] == 'service' && (!SA || _service('count') < 2))
			continue;
		$links .= '<a href="'.URL.'&p=setup&d='.$r['p'].'"'.($d == $r['p'] ? ' class="sel"' : '').'>'.$r['name'].'</a>';
	}

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
		'<button class="vk" id="pinchange">�������� ���-���</button>'.
		'<button class="vk" id="pindel">������� ���-���</button>'
	:
		'<button class="vk" id="pinset">���������� ���-���</button>'
	).

		'<div class="headName" id="dop" >�������������</div>'.
		'<table class="bs10">'.
			'<tr><td class="label">���������� �������:<td><input type="hidden" id="RULE_MY_PAY_SHOW_PERIOD" value="'._num(@RULE_MY_PAY_SHOW_PERIOD).'" />'.
		'</table>'.

	'</div>';
}

function setup_worker() {
	if(!_viewerMenuAccess(15))
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
			  AND `worker`
			  AND !`hidden`
			ORDER BY `dtime_add`";
	$q = query($sql);
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
	if(!_viewerMenuAccess(15))
		return _err('������������ ����: ���������� ������������.');

	$u = _viewer($viewer_id);
	if(!$u['viewer_worker'])
		return _err('������������ <b>'.$u['viewer_name'].'</b><br />��� �� �������� �����������.');

	$rule = _viewerRule($viewer_id);

	//��������� ������� �������� ���� �����������
	$sql = "SELECT `viewer_id`,`value`
			FROM `_vkuser_rule`
			WHERE `app_id`=".APP_ID."
			  AND `key`='RULE_HISTORY_VIEW'
			  AND `viewer_id`<".VIEWER_MAX;
	$hist_worker_all = query_assJson($sql);

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
			'<tr><td><td><button class="vk" id="w-save">���������</button>'.
		'</table>'.

	(RULE_SETUP_RULES ?
		'<div class="headName">�������������� ���������</div>'.
		'<table class="rtab">'.
			'<tr><td class="lab"><td>'._check('RULE_SALARY_SHOW', '���������� � ������ �/� �����������', $rule['RULE_SALARY_SHOW']).
			'<tr><td class="lab"><td>'._check('RULE_EXECUTER', '����� ���� ������������ ������', $rule['RULE_EXECUTER']).
			'<tr><td class="lab"><td>'._check('RULE_SALARY_ZAYAV_ON_PAY', '��������� �/� �� ������ ��� ���������� �����', $rule['RULE_SALARY_ZAYAV_ON_PAY']).
/*
			'<tr><td class="lab">��������� ������:'.
				'<td>'._check('RULE_SALARY_BONUS', '', $rule['RULE_SALARY_BONUS']).
					'<span'.($rule['RULE_SALARY_BONUS'] ? '' : ' class="vh"').'>'.
						'<input type="text" id="salary_bonus_sum" maxlength="5" value="'.$u['bonus_sum'].'" />%'.
					'<span>'.
*/
		'</table>'.

	(!$u['viewer_admin'] && $viewer_id < VIEWER_MAX ?

	($u['pin'] ?
		'<div class="headName">���-���</div>'.
		'<button class="vk" id="pin-clear">�������� ���-���</button>'
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

				'<tr><td class="label top"><b>������ � �������� �������� ����:</b>'.
					'<td id="td-rule-menu">'._setup_worker_rule_menu($viewer_id).

				'<tr id="tr-rule-zayav"'.(_viewerMenuAccess(2, $viewer_id) ? '' : ' class="dn"').'>'.
					'<td class="label top"><b>����� � �������:</b>'.
					'<td id="td-rule-zayav">'.
						_check('RULE_ZAYAV_EXECUTER', '����� ������ �� ������,<br />� ������� �������� ������������', $rule['RULE_ZAYAV_EXECUTER']).

				'<tr id="tr-rule-setup"'.(_viewerMenuAccess(5, $viewer_id) ? '' : ' class="dn"').'>'.
					'<td class="label top"><b>������ � ����������:</b>'.
					'<td id="td-rule-setup">'._setup_worker_rule_menu_setup($viewer_id, $rule).
				'<tr><td class="label"><a class="history-view-worker-all'._tooltip('�������� ����� ���� �����������', -20).'����� ������� ��������</a>:'.
					'<td><input type="hidden" id="RULE_HISTORY_VIEW" value="'.$rule['RULE_HISTORY_VIEW'].'" />'.
				'<tr><td><td>'.

				'<tr><td><td><b>������</b>'.
				'<tr><td class="label">���������� ���������� �������:'.
					'<td>'._check('RULE_SETUP_INVOICE', '', $rule['RULE_SETUP_INVOICE']).
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
function _setup_worker_rule_menu($viewer_id) {//����� �������� ���� � ���������
	$send = '';
	foreach(_menuCache() as $r) {
		if($r['p'] == 'main')
			continue;
		if($r['p'] == 'manual')
			continue;
		$send .= _check('RULE_MENU_'.$r['id'], $r['name'], _viewerMenuAccess($r['id'], $viewer_id));
	}

	return $send;
}
function _setup_worker_rule_menu_setup($viewer_id, $rule) {//����� �������� ���� �������� � ���������
	$send = '';
	foreach(_menuCache('setup') as $r) {
		if($r['p'] == 'my')
			continue;

		$send .= _check('RULE_MENU_'.$r['id'], $r['name'], _viewerMenuAccess($r['id'], $viewer_id));
		if($r['p'] == 'worker')
			$send .=
				'<div id="div-worker-rule"'.(_viewerMenuAccess(15, $viewer_id) ? '' : ' style="display:none"').'>'.
					_check('RULE_SETUP_RULES', '����� �����������', $rule['RULE_SETUP_RULES']).
				'</div>';
	}

	return $send;
}
function setup_worker_rule_save($post) {//���������� ��������� ����� ����������
	if(!RULE_SETUP_RULES)
		return false;

	if(!$viewer_id = _num($post['viewer_id']))
		return false;

	$u = _viewer($viewer_id);
	if($u['viewer_admin'] && $post['op'] != 'RULE_SALARY_SHOW')
		return false;

	$r = _viewerRule($viewer_id);
	if(!isset($r[$post['op']]))
		return false;

	$key = $post['op'];
	$old = $r[$post['op']];
	$v = $post['v'];
	if($old != $v) {
		_workerRuleQuery($viewer_id, $key, $v);

		if(!empty($post['h' . $v]))
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
	if(!query_value($sql)) {
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
		query($sql);

		xcache_unset(CACHE_PREFIX.'viewer_'.$viewer_id);
		xcache_unset(CACHE_PREFIX.'viewer_rule_'.$viewer_id);

		return;
	}

	$sql = "UPDATE `_vkuser_rule`
			SET `value`=".$v."
			WHERE `app_id`=".APP_ID."
			  AND `viewer_id`=".$viewer_id."
			  AND `key`='".$key."'";
	query($sql);

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
	if(!_viewerMenuAccess(13))
		return _err('������������ ����: ��������� �����������');

	$sql = "SELECT * FROM `_app` WHERE `id`=".APP_ID;
	$g = query_assoc($sql);
	return
	'<script>'.
		'var APP_TYPE='._selJson(_appType()).';'.
	'</script>'.
	'<div id="setup_rekvisit">'.
		'<div class="headName">�������� ����������</div>'.
		'<table class="t">'.
			'<tr><td class="label">��� �����������:<td><input type="hidden" id="type_id" value="'.$g['type_id'].'" />'.
		'</table>'.
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
			'<tr><td><td><button class="vk">���������</button>'.
		'</table>'.
	'</div>';
}

function setup_service() {
	$sql = "SELECT
				*,
				0 `active`
			FROM `_zayav_service`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	if(!$spisok = query_arr($sql))
		return '���� ������������ �� ����������.';

	$send = '';
	foreach($spisok as $r) {
		$send .=
		'<div class="unit" val="'.$r['id'].'">'.
			(SA ? _iconEdit() : '').
			'<input type="hidden" class="name" value="'.$r['name'].'">'.
			'<h1>'.$r['head'].'</h1>'.
			'<h2>'.$r['about'].'</h2>'.
		'</div>';
	}

	return
	'<div id="setup-service">'.
		'<div class="headName">���� ������������</div>'.
		$send.
	'</div>';
}

function setup_expense() {
	if(!_viewerMenuAccess(19))
		return _err('������������ ����: ��������� ��������.');

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
				0 `count`,
				0 `deleted`
			FROM `_money_expense_category`
			WHERE `app_id`=".APP_ID." OR !`app_id`
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return '������ ����.';

	//���������� ������������
	$sql = "SELECT
				DISTINCT `category_id`,
				COUNT(`id`) `sub`
			FROM `_money_expense_category_sub`
			WHERE `app_id`=".APP_ID."
			GROUP BY `category_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['category_id']]['sub'] = $r['sub'];

	$sql = "SELECT
				DISTINCT `category_id`,
				COUNT(`id`) `count`
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `category_id`
			GROUP BY `category_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['category_id']]['count'] = $r['count'];

	$sql = "SELECT
				DISTINCT `category_id`,
				COUNT(`id`) `count`
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `deleted`
			  AND `category_id`
			GROUP BY `category_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['category_id']]['deleted'] = $r['count'];

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
					'<td class="count">'.
						($r['count'] ? $r['count'] : '').
						($r['deleted'] ? '<em class="'._tooltip('�������', -30).'('.$r['deleted'].')</em>' : '').
					'<td class="ed">'.
						($id != 1 ? _iconEdit($r) : '').
						($id != 1 && !$r['count'] && !$r['deleted'] ? _iconDel($r) : '').
			'</table>';
	$send .= '</dl>';
	return $send;
}
function setup_expense_sub($id) {
	if(!_viewerMenuAccess(19))
		return _err('������������ ����: ��������� ��������.');

	$sql = "SELECT *
			FROM `_money_expense_category`
			WHERE `app_id`=".APP_ID."
			  AND `id`!=1
			  AND `id`=".$id;
	if(!$cat = query_assoc($sql))
		return '��������� id = '.$id.' �� ����������.';

	return
	'<script>var CAT_ID='.$id.';</script>'.
	'<div id="setup_expense_sub">'.
		'<a href="'.URL.'&p=setup&d=expense"><< ����� � ���������� ��������</a>'.
		'<div class="headName">������ ������������ ��������<a class="add">��������</a></div>'.
		'<div id="cat-name">'.$cat['name'].'</div>'.
		'<div id="spisok">'.setup_expense_sub_spisok($id).'</div>'.
	'</div>';
}
function setup_expense_sub_spisok($id) {
	$sql = "SELECT
				*,
				0 `count`,
				0 `deleted`
			FROM `_money_expense_category_sub`
			WHERE `app_id`=".APP_ID."
			  AND `category_id`=".$id."
			ORDER BY `name`";
	$arr = query_arr($sql);
	if(empty($arr))
		return '������ ����.';

	$sql = "SELECT
				DISTINCT `category_sub_id`,
				COUNT(`id`) `count`
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `category_id`=".$id."
			  AND `category_sub_id`
			GROUP BY `category_sub_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$arr[$r['category_sub_id']]['count'] = $r['count'];

	$sql = "SELECT
				DISTINCT `category_sub_id`,
				COUNT(`id`) `count`
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `deleted`
			  AND `category_id`=".$id."
			  AND `category_sub_id`
			GROUP BY `category_sub_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$arr[$r['category_sub_id']]['deleted'] = $r['count'];

	$send = '<table class="_spisok">'.
				'<tr><th>������������'.
				'<th class="count">���-��<br />�������'.
				'<th>';
	foreach($arr as $r)
		$send .= '<tr val="'.$r['id'].'">'.
			'<td class="name">'.$r['name'].
			'<td class="count">'.
				($r['count'] ? $r['count'] : '').
				($r['deleted'] ? '<em class="'._tooltip('�������', -30).'('.$r['deleted'].')</em>' : '').
			 '<td class="ed">'._iconEdit($r)._iconDel($r);

	$send .= '</table>';

	return $send;
}


function setup_zayav_status() {
	if(!_viewerMenuAccess(16))
		return _err('������������ ����: ������� ������.');

	return
		'<div id="setup_zayav_status">'.
			'<div class="headName">������� ������<a class="add status-add">����� ������</a></div>'.
			'<div id="status-spisok">'.setup_zayav_status_spisok().'</div>'.
		'</div>';
}
function setup_zayav_status_spisok() {
	$sql = "SELECT
	            *,
	            0 `next`
			FROM `_zayav_status`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
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
	$spisok = query_arr($sql);

	$values = array();
	foreach($spisok as $id => $r)
		$values[] = "(
			".APP_ID.",
			'".$r['name']."',
			'".$r['about']."',
			'".$r['color']."',
			".$r['default'].",
			".$id.",
			".$id."
		)";

	$sql = "INSERT INTO `_zayav_status` (
				`app_id`,
				`name`,
				`about`,
				`color`,
				`default`,
				`sort`,
				`id_old`
			) VALUES ".implode(',', $values);
	query($sql);

	//���������� ����� �������� � �������
	$sql = "UPDATE `_zayav` `z`
			SET `status_id`=(

				SELECT `id`
				FROM `_zayav_status`
				WHERE `app_id`=".APP_ID."
				  AND `id_old`=`z`.`status_id`
				LIMIT 1

			)
			WHERE `app_id`=".APP_ID."
			  AND `status_id`";
	query($sql);

	//���������� ����� �������� � �������
	$sql = "UPDATE `_history` `h`
			SET `v1`=(
					SELECT `id`
					FROM `_zayav_status`
					WHERE `app_id`=".APP_ID."
					  AND `id_old`=`h`.`v1`
					LIMIT 1
				),
				`v2`=(
					SELECT `id`
					FROM `_zayav_status`
					WHERE `app_id`=".APP_ID."
					  AND `id_old`=`h`.`v2`
					LIMIT 1
				)
			WHERE `app_id`=".APP_ID."
			  AND `type_id`=71";
	query($sql);

	xcache_unset(CACHE_PREFIX.'zayav_status');
	_appJsValues();

	$sql = "SELECT
	            *,
	            0 `next`
			FROM `_zayav_status`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			ORDER BY `sort`";
	return query_arr($sql);
}
function setup_zayav_status_next($spisok) {//��������� ids ��������� ��������
	$sql = "SELECT *
			FROM `_zayav_status_next`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	$q = query($sql);
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
			ORDER BY `id`";
	$q = query($sql);
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
			  AND `default`";
	query($sql);

	return true;
}


function setup_zayav_expense() {//��������� �������� �� ������
	if(!_viewerMenuAccess(17))
		return _err('������������ ����: ������� �� ������.');

	return
	'<div id="setup_zayav_expense">'.
		'<div class="headName">��������� ��������� �������� �� ������<a class="add">��������</a></div>'.
		'<div id="spisok">'.setup_zayav_expense_spisok().'</div>'.
	'</div>';
}
function setup_zayav_expense_spisok() {
	$sql = "SELECT
				*,
				0 `use`
			FROM `_zayav_expense_category`
			WHERE `app_id`=".APP_ID."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return '������ ����.';

	$sql = "SELECT
				DISTINCT `category_id`,
				COUNT(`id`) `use`
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `category_id`
			GROUP BY `category_id`";
	$q = query($sql);
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

function setup_salary_list() {
	if(!_viewerMenuAccess(22))
		return _err('������������ ����: ����� ������ �/�.');

	return
	'<div id="setup_salary_list">'.
		'<div class="headName">��������� ����� ������ �/�</div>'.
		'<div class="spisok">'.setup_salary_list_spisok().'</div>'.
		'<div class="center mt20"><button class="vk">���������</button></div>'.
	'</div>';
}
function setup_salary_list_spisok() {
	$spisok = salary_list_head();
	$pole = array();
	$check = array();

	if(_app('salary_list_setup'))
		foreach(explode(',', _app('salary_list_setup')) as $k) {
			$pole[$k] = $spisok[$k];
			$check[$k] = 1;
			unset($spisok[$k]);
		}

	foreach($spisok as $id => $name) {
		$pole[$id] = $spisok[$id];
		$check[$id] = 0;
	}

	$send =
	'<table class="_spisok">'.
		'<tr><th class="name">�������� ������� ����� ������'.
	'</table>'.
	'<dl class="_sort no">';
	foreach($pole as $id => $name)
		$send .=
		'<dd val="'.$id.'">'.
			'<table class="_spisok">'.
				'<tr><td class="ch">'._check('ch'.$id, '', $check[$id]).
					'<td class="name" style="cursor:move">'.$name.
			'</table>';
	$send .= '</dl>';
	return $send;
}
function salary_list_head() {
	return array(
		1 => '� ������',
		2 => '�������� ������',
		3 => '� ���.',
		4 => '�����',
		5 => '���� ����������',
		6 => '�����',
		7 => '��������',
		8 => '���� ����������'
	);
}






function setup_rubric() {
	if(!_viewerMenuAccess(18))
		return _err('������������ ����: ������� ����������.');

	return
	'<div id="setup_rubric">'.
		'<div class="headName">������� ����������<a class="add" onclick="setupRubricEdit()">����� �������</a></div>'.
		'<div id="spisok">'.setup_rubric_spisok().'</div>'.
	'</div>';
}
function setup_rubric_spisok() {
	$sql = "SELECT
				*,
				0 `sub`,
				0 `zayav`
			FROM `_setup_rubric`
			WHERE `app_id`=".APP_ID."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return '������ ����.';


	//����������
	$sql = "SELECT
				DISTINCT `rubric_id`,
				COUNT(`id`) `count`
			FROM `_setup_rubric_sub`
			WHERE `app_id`=".APP_ID."
			  AND `rubric_id`
			GROUP BY `rubric_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['rubric_id']]['sub'] = $r['count'];


	//������������� � �������
	$sql = "SELECT
				DISTINCT `rubric_id`,
				COUNT(`id`) `count`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `rubric_id`
			GROUP BY `rubric_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['rubric_id']]['zayav'] = $r['count'];

	$send =
		'<table class="_spisok">'.
			'<tr><th class="name">������������'.
				'<th class="sub">����������'.
				'<th class="zayav">���-��<br />����������'.
				'<th class="ed">'.
		'</table>'.
		'<dl class="_sort" val="_setup_rubric">';
	foreach($spisok as $id => $r) {
		$nodel = $r['sub'] || $r['zayav'];
		$send .='<dd val="'.$id.'">'.
			'<table class="_spisok">'.
				'<tr><td class="name"><a href="'.URL.'&p=setup&d=rubric&id='.$id.'">'.$r['name'].'</a>'.
					'<td class="sub">'.($r['sub'] ? $r['sub'] : '').
					'<td class="zayav">'.($r['zayav'] ? $r['zayav'] : '').
					'<td class="ed">'.
						_iconEdit($r).
						_iconDel(array('nodel' => $nodel) + $r).
			'</table>';
	}

	$send .= '</dl>';
	return $send;
}

function setup_rubric_sub($id) {
	if(!_viewerMenuAccess(18))
		return _err('������������ ����: ������� ����������.');

	$sql = "SELECT *
			FROM `_setup_rubric`
			WHERE `app_id`=".APP_ID."
			  AND `id`=".$id;
	if(!$rub = query_assoc($sql))
		return '������� id = '.$id.' �� ����������. ';

	return
	'<script>var RUBRIC_ID='.$id.';</script>'.
	'<a href="'.URL.'&p=setup&d=rubric"><< ����� � ������ ������</a>'.
	'<div id="setup_rubric_sub">'.
		'<div class="headName">������ ��������� ��� "'.$rub['name'].'"'.
			'<a class="add" onclick="setupRubricSubEdit()">����� ����������</a>'.
		'</div>'.
		'<div id="spisok">'.setup_rubric_sub_spisok($id).'</div>'.
	'</div>';
}
function setup_rubric_sub_spisok($rubric_id) {
	$sql = "SELECT
				*,
				0 `zayav`
			FROM `_setup_rubric_sub`
			WHERE `app_id`=".APP_ID."
			  AND `rubric_id`=".$rubric_id."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return '������ ����.';

	//������������� � �������
	$sql = "SELECT
				DISTINCT `rubric_id_sub`,
				COUNT(`id`) `count`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `rubric_id`=".$rubric_id."
			  AND `rubric_id_sub`
			GROUP BY `rubric_id_sub`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['rubric_id_sub']]['zayav'] = $r['count'];

	$send =
		'<table class="_spisok">'.
			'<tr><th class="name">������������'.
				'<th class="zayav">���-��<br />����������'.
				'<th class="ed">'.
		'</table>'.
		'<dl class="_sort" val="_setup_rubric_sub">';
	foreach($spisok as $id => $r)
		$send .='<dd val="'.$id.'">'.
			'<table class="_spisok">'.
				'<tr><td class="name">'.$r['name'].
					'<td class="zayav">'.($r['zayav'] ? $r['zayav'] : '').
					'<td class="ed">'.
						_iconEdit($r).
						_iconDel(array('nodel' => $r['zayav']) + $r).
			'</table>';

	$send .= '</dl>';
	return $send;
}






function setup_cartridge() {
	if(!_viewerMenuAccess(21))
		return _err('������������ ����: ���������.');

	return
		'<div id="setup-cartridge">'.
			'<div class="headName">���������� ��������� ����������<a class="add" onclick="cartridgeNew()">������ ����� ��������</a></div>'.
			'<div id="spisok">'.setup_cartridge_spisok().'</div>'.
		'</div>';
}
function setup_cartridge_spisok($edit_id=0) {
	$send = '';
	foreach(_cartridgeType() as $type_id => $name) {
		$sql = "SELECT `s`.*,
				   COUNT(`z`.`id`) `count`
			FROM `_setup_cartridge` `s`
				LEFT JOIN `_zayav_cartridge` AS `z`
				ON `s`.`id`=`z`.`cartridge_id`
			WHERE `type_id`=".$type_id."
			GROUP BY `s`.`id`
			ORDER BY `name`";
		if(!$spisok = query_arr($sql))
			continue;

		$send .=
			'<div class="type">'.$name.':</div>'.
			'<table class="_spisok">' .
				'<tr><th class="n">�' .
					'<th class="name">������' .
					'<th class="cost">��� ������:<br />����./�����./���' .
					'<th class="count">���-��' .
					'<th class="set">';
		$n = 1;
		foreach ($spisok as $id => $r) {
			$cost = array();
			if($r['cost_filling'])
				$cost[] = '<span class="'._tooltip('��������', -30).$r['cost_filling'].'</span>';
			if($r['cost_restore'])
				$cost[] = '<span class="'._tooltip('��������������', -48).$r['cost_restore'].'</span>';
			if($r['cost_chip'])
				$cost[] = '<span class="'._tooltip('������ ����', -40).$r['cost_chip'].'</span>';
			$send .=
				'<tr'.($edit_id == $r['id'] ? ' class="edited"' : '').'>' .
					'<td class="n">'.($n++) .
					'<td class="name">'.$r['name'] .
					'<td class="cost">'.implode(' / ', $cost) .
						'<input type="hidden" class="type_id" value="'.$r['type_id'].'" />' .
						'<input type="hidden" class="filling" value="'.$r['cost_filling'].'" />' .
						'<input type="hidden" class="restore" value="'.$r['cost_restore'].'" />' .
						'<input type="hidden" class="chip" value="'.$r['cost_chip'].'" />' .
					'<td class="count">'.($r['count'] ? $r['count'] : '') .
					'<td class="set">' .
						'<div val="'.$id.'" class="img_edit'._tooltip('��������', -33).'</div>';
		}
		$send .= '</table>';
	}
	return $send ? $send : '������ ����.';
}













function setup_tovar() {
	if(!_viewerMenuAccess(20))
		return _err('������������ ����: ������.');

	return setup_tovar_category();
	switch(@$_GET['d1']) {
		case 'category': return setup_tovar_category();
		case 'name': return setup_tovar_name();
		case 'vendor': return setup_tovar_vendor();
	}

	return
	'<div id="setup_tovar">'.
		'<div class="headName">��������� �������</div>'.
		'<a href="'.URL.'&p=setup&d=tovar&d1=category">��������� ��������� �������</a>'.
		'<br />'.
		'<a href="'.URL.'&p=setup&d=tovar&d1=name">�������� �������</a>'.
		'<br />'.
		'<a href="'.URL.'&p=setup&d=tovar&d1=vendor">�������������</a>'.
	'</div>';
}

function setup_tovar_category() {
	//��������� ����� ������� ����, ���� ���������� �� ������������
	//��� ����������� ������������ ��������� ��� ������ ���������� ���������� ��� �������� ����������
	return
	'<div id="setup_tovar_category">'.
		'<div class="headName">��������� �������</div>'.
		'<div class="_info">'.
			'<u>���������</u> ������������� ��� ���������� ������� �� ������� ��������� ��� ���������������. '.
			'<br />'.
			'�������� ��������� ����������� ��������� �������, ���� ���������� ������� ��������� �� ��������.'.
		'</div>'.
		'<table id="but">'.
			'<tr><td><button class="vk" id="add">������� ����� ���������</button>'.
				'<td><button class="vk" id="join">���������� ��������� �� ��������</button>'.
		'</table>'.
		'<div id="spisok">'.setup_tovar_category_spisok().'</div>'.
	'</div>';
}
function setup_tovar_category_spisok() {//��������� �������
	$sql = "SELECT *
			FROM `_tovar_category_use`
			WHERE `app_id`=".APP_ID."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return '������ ����.';

	$sql = "SELECT *
			FROM `_tovar_category`
			WHERE `id` IN ("._idsGet($spisok, 'category_id').")";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		foreach($spisok as $sp)
			if($r['id'] == $sp['category_id']) {
				$spisok[$sp['id']]['name'] = $r['name'];
				continue;
			}

	$send = '<table class="_spisok">'.
				'<tr><th class="name">������������'.
					'<th class="ed">'.
			'</table>'.
			'<dl class="_sort" val="_tovar_category_use">';
	foreach($spisok as $r)
		$send .= '<dd val="'.$r['id'].'">'.
			'<table class="_spisok">'.
				'<tr val="'.$r['category_id'].'">'.
					'<td class="name">'.$r['name'].
					'<td class="ed">'._iconEdit($r)._iconDel($r).
			'</table>';
	$send .= '</dl>';

	return $send;
}

function setup_tovar_name() {
	return
	'<div id="setup_tovar_name">'.
		'<div class="headName">�������� �������<a class="add">��������</a></div>'.
		'<div class="_info">'.
			'<u>�������� �������</u> ������������� ��� �������� � ������� �������� ������. '.
			'<br />'.
			'�������� ��������, ��� ���� � �� �� �������� ����� ����������� � ������ ���������� �������. '.
			'<br />'.
			'���������� ����� �������� ������ � ������, ���� ��� ����� ��� � ���� ������. '.
		'</div>'.
		'<div id="spisok">'.setup_tovar_name_spisok().'</div>'.
	'</div>';
}
function setup_tovar_name_spisok() {
	$sql = "SELECT
				*,
				0 `tovar`
			FROM `_tovar_name`
			ORDER BY `name`";
	if(!$spisok = query_arr($sql))
		return '������ ����.';

	$sql = "SELECT
				`name_id`,
				COUNT(`id`) `count`
			FROM `_tovar`
			WHERE `category_id`
			GROUP BY `name_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['name_id']]['tovar'] = $r['count'];

	$send = '<table class="_spisok">'.
				'<tr><th>��������'.
					'<th>���-��<br />�������'.
					'<th>';
	foreach($spisok as $id => $r)
		$send .= '<tr val="'.$id.'">'.
					'<td class="name">'.$r['name'].' <b>'.$r['id'].'</b>'.
					'<td class="tovar center">'.($r['tovar'] ? $r['tovar'] : '').
					'<td class="ed">'._iconEdit($r)._iconDel($r);
	$send .= '</table>';

	return $send;
}

function setup_tovar_vendor() {
	return
	'<div id="setup_tovar_vendor">'.
		'<div class="headName">������������� �������<a class="add">����� �������������</a></div>'.
		'<div class="_info">'.
			'<u>������������� �������</u>'.
			'<br />'.
		'</div>'.
		'<div id="spisok">'.setup_tovar_vendor_spisok().'</div>'.
	'</div>';
}
function setup_tovar_vendor_spisok() {
	$sql = "SELECT
				*,
				0 `tovar`
			FROM `_tovar_vendor`
			ORDER BY `name`";
	if(!$spisok = query_arr($sql))
		return '������ ����.';

		$sql = "SELECT
				`vendor_id`,
				COUNT(`id`) `count`
			FROM `_tovar`
			WHERE `vendor_id`
			GROUP BY `vendor_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['vendor_id']]['tovar'] = $r['count'];

	$send = '<table class="_spisok">'.
				'<tr><th>��������'.
					'<th>���-��<br />�������'.
					'<th>';
	foreach($spisok as $id => $r)
		$send .= '<tr val="'.$id.'">'.
					'<td class="name">'.$r['name'].' <b>'.$r['id'].'</b>'.
					'<td class="tovar center">'.($r['tovar'] ? $r['tovar'] : '').
					'<td class="ed">'._iconEdit($r)._iconDel($r);
	$send .= '</table>';

	return $send;
}













function setup_polosa() {//�����: ��������� ��2 ��� ������ ������
	return
	'<div id="setup_polosa">'.
		'<div class="headName">'.
			'��������� ��&sup2; ������� ��� ������ ������'.
			'<a class="add" onclick="setupPolosaCostEdit()">����� ������</a>'.
		'</div>'.
		'<div id="spisok">'.setup_polosa_spisok().'</div>'.
	'</div>';
}
function setup_polosa_spisok() {
	$sql = "SELECT *
			FROM `_setup_gazeta_polosa_cost`
			WHERE `app_id`=".APP_ID."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return '������ ����.';

	$send =
		'<table class="_spisok">'.
			'<tr><th class="name">������'.
				'<th class="cena">���� �� ��&sup2;<br />���.'.
				'<th class="pn">���������<br />�����<br />������'.
				'<th class="ed">'.
		'</table>'.
		'<dl class="_sort" val="_setup_gazeta_polosa_cost">';
	foreach($spisok as $id => $r)
		$send .='<dd val="'.$id.'">'.
			'<table class="_spisok">'.
				'<tr><td class="name curM">'.$r['name'].
					'<td class="cena center">'.round($r['cena'], 2).
					'<td class="pn center">'.($r['polosa'] ? '��' : '').
					'<td class="ed">'._iconEdit($r).
			'</table>';
	$send .= '</dl>';
	return $send;
}




function setup_obdop() {//�����: �������������� ��������� ����������
	return
	'<div id="setup_obdop">'.
		'<div class="headName">�������������� ��������� ����������</div>'.
		'<div id="spisok">'.setup_obdop_spisok().'</div>'.
	'</div>';
}
function setup_obdop_spisok() {
	$sql = "SELECT *
			FROM `_setup_gazeta_ob_dop`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	if(!$spisok = query_arr($sql))
		return '������ ����.';

	$send =
		'<table class="_spisok">'.
			'<tr><th>������������'.
				'<th>���������<br />���.'.
				'<th>';
	foreach($spisok as $r)
		$send .= '<tr val="'.$r['id'].'">'.
			'<td class="name">'.$r['name'].
			'<td class="cena w100 center">'._cena($r['cena']).
			'<td class="ed">'._iconEdit($r);
	$send .= '</table>';
	return $send;
}



function setup_oblen() {//�����: ��������� ����� ����������
	return
	'<div id="setup_oblen">'.
		'<div class="headName">��������� ��������� ����� ����������</div>'.
		'<table>'.
            '<tr><td>������'.
				'<td><input type="text" value="'.TXT_LEN_FIRST.'" id="txt_len_first" />'.
				'<td>��������:'.
                '<td><input type="text" value="'.TXT_CENA_FIRST.'" id="txt_cena_first" /> ���.'.
            '<tr><td>�����������'.
				'<td><input type="text" value="'.TXT_LEN_NEXT.'" id="txt_len_next" />'.
				'<td>��������:'.
                '<td><input type="text" value="'.TXT_CENA_NEXT.'" id="txt_cena_next" /> ���.'.
        '</table>'.
		'<button class="vk" onclick="setupObLenEdit()">���������</button>'.
	'</div>';
}





function setup_gn() {
	define('CURRENT_YEAR', strftime('%Y', time()));
	$sql = "SELECT MAX(`general_nomer`)+1
			FROM `_setup_gazeta_nomer`
			WHERE `app_id`=".APP_ID;
	$gnMax = query_value($sql);
	return
	'<script>var GN_MAX="'.$gnMax.'";</script>'.
	'<div id="setup_gn">'.
		'<div class="headName">'.
			'������ �������� ������'.
			'<a class="add" onclick="setupGnEdit()">����� �����</a>'.
		'</div>'.
		'<div id="dopLinks">'.setup_gn_year().'</div>'.
		'<div id="spisok">'.setup_gn_spisok().'</div>'.
	'</div>';
}
function setup_gn_year($y=CURRENT_YEAR) {
	$sql = "SELECT
            	SUBSTR(MIN(`day_public`),1,4) AS `begin`,
            	SUBSTR(MAX(`day_public`),1,4) AS `end`,
            	MAX(`general_nomer`) AS `max`
            FROM `_setup_gazeta_nomer`
   			WHERE `app_id`=".APP_ID."
            LIMIT 1";
	$r = mysql_fetch_assoc(query($sql));
	if(!$r['begin'])
		$r = array(
			'begin' => CURRENT_YEAR,
			'end' => CURRENT_YEAR
		);
	$send = '';
	for($n = $r['begin']; $n <= $r['end'] + 1; $n++)
		$send .= '<a class="link'.($n == $y ? ' sel' : '').'">'.$n.'</a>';
	return $send;
}
function setup_gn_spisok($y=CURRENT_YEAR, $gnedit=0) {
	$sql = "SELECT *
			FROM `_setup_gazeta_nomer`
			WHERE `app_id`=".APP_ID."
			  AND `day_public` LIKE '".$y."-%'
			ORDER BY `general_nomer`";
	$q = query($sql);
	if(!mysql_num_rows($q))
		return
			'������ �����, ������� ����� �������� � '.$y.' ����, �� ����������.'.
			'<button class="vk">������� ������</button>';
	$send =
		'<a id="gn-clear" val="'.$y.'">�������� ������ �� '.$y.' ���</a>'.
		'<table class="_spisok">'.
			'<tr><th>�����<br />�������'.
				'<th>���� ��������<br />� ������'.
				'<th>���� ������'.
				'<th>���-��<br />�����'.
				'<th>';
	$cur = time() - 86400;
	while($r = mysql_fetch_assoc($q)) {
		$grey = $cur > strtotime($r['day_print']) ? 'grey' : '';
		$edit = $gnedit == $r['general_nomer'] ? ' edit' : '';
		$class = $grey || $edit ? ' class="'.$grey.$edit.'"' : '';
		$send .= '<tr'.$class.'>'.
			'<td class="nomer center"><b>'.$r['week_nomer'].'</b> (<span>'.$r['general_nomer'].'</span>)'.
			'<td class="print r">'.FullData($r['day_print'], 0, 1, 1).'<s>'.$r['day_print'].'</s>'.
			'<td class="pub r">'.FullData($r['day_public'], 0, 1, 1).'<s>'.$r['day_public'].'</s>'.
			'<td class="pc">'.$r['polosa_count'].
			'<td class="ed">'._iconEdit($r)._iconDel($r);
	}
	$send .= '</table>';
	return $send;
}

