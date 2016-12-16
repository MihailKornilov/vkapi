<?php
function sa_appCount() {
	$sql = "SELECT COUNT(`id`) FROM `_app`";
	return query_value($sql);
}
function sa_userCount() {
	$sql = "SELECT COUNT(*)
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `last_seen`!='0000-00-00 00:00:00'";
	return query_value($sql);
}

function _sa_script() {
	if(@$_GET['p'] != 'sa')
		return '';

	return
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/sa/sa'.MIN.'.css?'.VERSION.'" />'.
		'<script src="'.API_HTML.'/modul/sa/sa'.MIN.'.js?'.VERSION.'"></script>';
}
function sa_global_index() {//����� ������ ������������������� ��� ���� ����������
	return
	saPath().

	'<div id="sa-index">'.
		'<h1>Global:</h1>'.
		'<a href="'.URL.'&p=sa&d=menu">������� ����</a>'.
		'<a href="'.URL.'&p=sa&d=history">������� ��������</a>'.
		'<a href="'.URL.'&p=sa&d=rule">����� �����������</a>'.
		'<a href="'.URL.'&p=sa&d=balans">�������</a>'.
		'<a href="'.URL.'&p=sa&d=client">�������</a>'.
		'<a href="'.URL.'&p=sa&d=zayav">������</a>'.
		'<a href="'.URL.'&p=sa&d=tovar_measure">������: ������� ���������</a>'.
		'<a href="'.URL.'&p=sa&d=color">�����</a>'.
		'<a href="'.URL.'&p=sa&d=template">������� ����������</a>'.
		'<a href="'.URL.'&p=sa&d=count">��������</a>'.
		'<br />'.

		'<h1>App:</h1>'.
		'<a href="'.URL.'&p=sa&d=app">���������� ('.sa_appCount().'): '._app('app_name').'</a>'.
		'<a href="'.URL.'&p=sa&d=user">������������ ('.sa_userCount().')</a>'.
		'<br />'.

		(function_exists('sa_index') ? sa_index() : '').
	'</div>';
}
function sa_cookie_back() {//���������� ���� ��� ����������� �� ������� �������� ����� ��������� �����������
	if(!empty($_GET['pre_p'])) {
		$_COOKIE['pre_p'] = $_GET['pre_p'];
		$_COOKIE['pre_d'] = empty($_GET['pre_d']) ? '' : $_GET['pre_d'];
		$_COOKIE['pre_d1'] = empty($_GET['pre_d1']) ? '' : $_GET['pre_d1'];
		$_COOKIE['pre_id'] = empty($_GET['pre_id']) ? '' : $_GET['pre_id'];
		setcookie('pre_p', $_COOKIE['pre_p'], time() + 2592000, '/');
		setcookie('pre_d', $_COOKIE['pre_d'], time() + 2592000, '/');
		setcookie('pre_d1', $_COOKIE['pre_d1'], time() + 2592000, '/');
		setcookie('pre_id', $_COOKIE['pre_id'], time() + 2592000, '/');
	}
	$d = empty($_COOKIE['pre_d']) ? '' :'&d='.$_COOKIE['pre_d'];
	$d1 = empty($_COOKIE['pre_d1']) ? '' :'&d1='.$_COOKIE['pre_d1'];
	$id = empty($_COOKIE['pre_id']) ? '' :'&id='.$_COOKIE['pre_id'];
	return '<a class="link" href="'.URL.'&p='.@$_COOKIE['pre_p'].$d.$d1.$id.'">'._app('app_name').'</a> � ';
}
function saPath($v=array()) {//������� ������: ���� ��������
	$v = array(
		'name' => @$v['name'],          // �������� �������
		'name_d' => @$v['name_d'],      // �������� ����������
		'link_d' => @$v['link_d']       // ������ �� ���������� ������
	);

	if($v['name_d'])
		$v['name_d'] = '<a class="link" href="'.URL.'&p=sa&d='.$v['link_d'].'">'.$v['name_d'].'</a> � ';

	return
	'<div class="hd1">'.
		sa_cookie_back().
		($v['name'] ? '<a class="link" href="'.URL.'&p=sa">�����������������</a> � ' : '�����������������').
		$v['name_d'].
		$v['name'].
	'</div>';
}



function sa_menu() {//���������� ��������� ����
	return
		saPath(array('name'=>'������� ����')).
		'<div id="sa-menu">'.
			'<div class="headName">������� ����<a class="add" onclick="saMenuEdit()">��������</a></div>'.
			'<div id="spisok">'.sa_menu_spisok().'</div>'.

			'<div class="headName">���������<a class="add" onclick="saMenuEdit({tp:\'setup\'})">��������</a></div>'.
			'<div id="setup-spisok">'.sa_menu_setup_spisok().'</div>'.
		'</div>';
}
function sa_menu_spisok($id=0) {
	$sql = "SELECT
				`m`.*,
				IFNULL(`ma`.`id`,0) `ma_id`
			FROM `_menu` `m`

			LEFT JOIN `_menu_app` `ma`
			ON `m`.`id`=`ma`.`menu_id`
			AND `ma`.`app_id`=".APP_ID."
			
			WHERE `m`.`type`='main'
			ORDER BY `ma`.`sort`";
	if(!$spisok = query_arr($sql))
		return '������ ����.';

	$send =
		'<table class="_spisok">'.
			'<tr><th class="name">��������'.
				'<th class="p">Link'.
				'<th class="show">App<br />show'.
				'<th class="access">user<br />access<br />default'.
				'<th class="ed">'.
		'</table>'.
		'<dl class="_sort" val="_menu_app">';
	foreach($spisok as $r) {
		if(!$r['ma_id'])
			continue;
		$send .= '<dd val="'.$r['ma_id'].'">'.
		'<table class="_spisok">'.sa_menu_spisok_tr($r, $id).'</table>';
	}
	$send .= '</dl>';


	//�������������� ������� (�� �����������)
	$send .= '<b class="db mt20">�� ������������:</b>'.
			 '<table class="_spisok nouse">';
	foreach($spisok as $r) {
		if($r['ma_id'])
			continue;
		$send .= sa_menu_spisok_tr($r, $id);
	}
	$send .= '</table>';


	return $send;
}
function sa_menu_spisok_tr($r, $edited_id) {//������� ������ ����
	return
	'<tr'.($edited_id == $r['id'] ? ' class="edited"' : '').'>'.
		'<td class="name" val="'.$r['id'].'">'.
			'<span>'.$r['name'].'</span>'.
			'<div class="about">'.$r['about'].'</div>'.
		'<td class="p">'.$r['p'].
		'<td class="show">'._check('show'.$r['id'], '', $r['ma_id']).
		'<td class="access">'.($r['id'] == 12 ? '' : _check('access'.$r['id'], '', $r['access_default'])).
		'<td class="ed">'._iconEdit($r);
}
function sa_menu_setup_spisok($id=0) {
	$sql = "SELECT
				`m`.*,
				IFNULL(`ma`.`id`,0) `ma_id`
			FROM `_menu` `m`

			LEFT JOIN `_menu_app` `ma`
			ON `m`.`id`=`ma`.`menu_id`
			AND `ma`.`app_id`=".APP_ID."
			
			WHERE `m`.`type`='setup'
			ORDER BY `ma`.`sort`";
	if(!$spisok = query_arr($sql))
		return '������ ����.';

	$send =
		'<table class="_spisok">'.
			'<tr><th class="name">��������'.
				'<th class="p">Link'.
				'<th class="show">App<br />show'.
				'<th class="access">user<br />access<br />default'.
				'<th class="ed">'.
		'</table>'.
		'<dl class="_sort" val="_menu_app">';
	foreach($spisok as $r) {
		if(!$r['ma_id'])
			continue;
		$send .= '<dd val="'.$r['ma_id'].'">'.
		'<table class="_spisok">'.sa_menu_spisok_tr($r, $id).'</table>';
	}
	$send .= '</dl>';


	//�������������� ������� (�� �����������)
	$send .= '<b class="db mt20">�� ������������:</b>'.
			 '<table class="_spisok nouse">';
	foreach($spisok as $r) {
		if($r['ma_id'])
			continue;
		$send .= sa_menu_spisok_tr($r, $id);
	}
	$send .= '</table>';

	return $send;
}



function sa_history() {//���������� �������� ��������
	$sql = "SELECT `id`,`name` FROM `_history_category` ORDER BY `sort`";
	$category = query_selJson($sql);
	$sql = "SELECT MAX(`id`)+1 FROM `_history_type` WHERE `id`<500";
	$max = query_value($sql);
	return
		'<script>'.
			'var CAT='.$category.','.
				'TYPE_ID_MAX='.$max.';'.
		'</script>'.

		saPath(array('name'=>'������� ��������')).

		'<div id="sa-history">'.
			'<div class="headName">'.
				'��������� ������� ��������'.
				'<a class="add" onclick="saHistoryEdit()">�������� ���������</a>'.
				'<span> :: </span>'.
				'<a class="add" href="'.URL.'&p=sa&d=historycat">��������� ���������</a>'.
			'</div>'.
			'<div id="spisok" class="mar20">'.sa_history_spisok().'</div>'.
		'</div>';
}
function sa_history_spisok() {
	$sql = "SELECT * FROM `_history_type` ORDER BY `id`";
	$q = query($sql);
	if(!mysql_num_rows($q))
		return '������ ����.';

	$spisok = array();
	while($r = mysql_fetch_assoc($q)) {
		$r['ids'] = array(); //������ id ��������� ��� _select
		$r['cats'] = array();//������ ��������� � ������ (�� �������� ���������)
		$spisok[$r['id']] = $r;
	}

	//���������� ��������� ������� ��� ������� ���� ������� ��������
	$sql = "SELECT
				`type_id`,
				COUNT(`id`) `count`
			FROM `_history`
			WHERE `type_id`
			  AND `app_id`=".APP_ID."
			GROUP BY `type_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		if(isset($spisok[$r['type_id']]))
			$spisok[$r['type_id']]['count'] = $r['count'];


	//���������� ��� ���������
	$sql = "SELECT `id`,`name` FROM `_history_category`";
	$cat = query_ass($sql);

	//������� ������ �� ���������
	$sql = "SELECT `id`,`name`
			FROM `_history_category`
			ORDER BY `sort`";
	$q = query($sql);
	$category = array();
	while($r = mysql_fetch_assoc($q)) {
		$r['type'] = array();//������ ��������� $spisok, ������� ��������� � ����������
		$category[$r['id']] = $r;
	}

	//�������� ������ ���������
	$sql = "SELECT *
			FROM `_history_ids`
			ORDER BY `id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		$spisok[$r['type_id']]['ids'][] = $r['category_id'];
		if(!$r['main'])
			$spisok[$r['type_id']]['cats'][] = $cat[$r['category_id']];
	}

	//��������� ������� ���������
	$sql = "SELECT *
			FROM `_history_ids`
			WHERE `main`
			ORDER BY `id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		$category[$r['category_id']]['type'][$r['type_id']] = $spisok[$r['type_id']];
		unset($spisok[$r['type_id']]);
	}

	$send = '';
	foreach($category as $r)
		$send .= sa_history_spisok_page($r['name'], $r['type']);

	$send .= sa_history_spisok_page('��� ���������', $spisok);

	return $send;
}
function sa_history_spisok_page($name, $spisok) {//����� ������ ���������� ���������
	if(empty($spisok))
		return '';

	ksort($spisok);

	$send =
		'<div class="curP b mt10 pad10 over1" onclick="$(this).next().slideToggle(200)">'.
			$name.' '.
			'<span class="grey">('.count($spisok).')</span>'.
		'</div>'.
		'<div class="dn">'.
			'<table class="_spisokTab">'.
				'<tr><th class="w50">type_id'.
					'<th class="txt">������������'.
					'<th class="w50">���-��'.
					'<th class="w50">';

	foreach($spisok as $r)
		$send .=
			'<tr class="over3">'.
				'<td class="r grey">'.$r['id'].
				'<td><textarea class="txt w450 min curP" readonly>'.$r['txt'].'</textarea>'.
($r['txt_client'] ? '<div class="color-pay b mt10 ml8">������:</div><textarea class="txt_client w450 min grey curP" readonly>'.$r['txt_client'].'</textarea>' : '').
($r['txt_zayav'] ? '<div class="color-pay b mt10 ml8">������:</div><textarea class="txt_zayav w450 min grey curP" readonly>'.$r['txt_zayav'].'</textarea>' : '').
($r['txt_schet'] ? '<div class="color-pay b mt10 ml8">���� �� ������:</div><textarea class="txt_schet w450 min grey curP" readonly>'.$r['txt_schet'].'</textarea>' : '').
					(!empty($r['cats']) ? '<div class="fr fs11 color-vin">'.implode('&nbsp;&nbsp;&nbsp;', $r['cats']).'</div>' : '').
				'<td class="center">'.(empty($r['count']) ? '' : $r['count']).
				'<td>'.
					'<div class="icon icon-edit" val="'.$r['id'].'"></div>'.
					'<input type="hidden" class="ids" value="'.implode(',', $r['ids']).'" />';
	$send .= '</table>'.
		'</div>';
	return $send;
}
function sa_history_cat() {//��������� ��������� ������� ��������
	return
		saPath(array(
			'name' => '��������� ������� ��������',
			'name_d' => '������� ��������',
			'link_d' => 'history'
		)).
		'<div id="sa-history-cat">'.
			'<div class="headName">��������� ������� ��������<a class="add">��������</a></div>'.
			'<div id="spisok">'.sa_history_cat_spisok().'</div>'.
		'</div>';
}
function sa_history_cat_spisok() {
	$sql = "SELECT * FROM `_history_category` ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return '������ ����.';

	$send =
		'<table class="_spisok">'.
			'<tr><th class="name">������������'.
				'<th class="js">use_js'.
				'<th class="set">'.
		'</table>'.
		'<dl class="_sort" val="_history_category">';
	foreach($spisok as $r)
		$send .=
			'<dd val="'.$r['id'].'">'.
				'<table class="_spisok">'.
					'<tr><td class="name">'.
							'<b>'.$r['name'].'</b>'.
							'<div class="about">'.$r['about'].'</div>'.
						'<td class="js">'.($r['js_use'] ? '+' : '').
						'<td class="set">'.
							'<div class="img_edit"></div>'.
							'<div class="img_del"></div>'.
				'</table>';
	$send .= '</dl>';
	return $send;
}



function sa_rule() {//���������� �������� ��������
	return
		saPath(array('name'=>'����� �����������')).
		'<div id="sa-rule">'.
			'<div class="headName">����� �����������<a class="add">��������</a></div>'.
			'<div id="spisok">'.sa_rule_spisok().'</div>'.
		'</div>';
}
function sa_rule_spisok() {
	$sql = "SELECT * FROM `_vkuser_rule_default` ORDER BY `key`";
	$q = query($sql);
	if(!mysql_num_rows($q))
		return '������ ����.';

	$spisok = array();
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']] = $r;

	$send =
		'<table class="_spisok">'.
			'<tr><th>���������'.
				'<th>�����'.
				'<th>���������'.
				'<th>';
	foreach($spisok as $r)
		$send .=
			'<tr val="'.$r['id'].'">'.
				'<td class="key">'.
					'<b>'.$r['key'].'</b>'.
					'<div class="about">'.$r['about'].'</div>'.
				'<td class="admin"><input type="text" id="admin'.$r['id'].'" value="'.$r['value_admin'].'" maxlength="3" />'.
				'<td class="worker"><input type="text" id="worker'.$r['id'].'" value="'.$r['value_worker'].'" maxlength="3" />'.
				'<td class="ed">'._iconEdit($r);
	$send .= '</table>';

	return $send;
}



function sa_balans() {//���������� ���������
	return
		saPath(array('name'=>'�������')).
		'<div id="sa-balans">'.
			'<div class="headName">'.
				'���������� ���������'.
				'<a class="add" id="category-add">����� ���������</a>'.
			'</div>'.
			'<div id="spisok">'.sa_balans_spisok().'</div>'.
		'</div>';
}
function sa_balans_spisok() {
	$sql = "SELECT * FROM `_balans_category` ORDER BY `id`";
	$q = query($sql);
	if(!mysql_num_rows($q))
		return '������ ����.';

	$spisok = array();
	while($r = mysql_fetch_assoc($q)) {
		$r['action'] = array(); //������ �������� ��� ������ ���������
		$spisok[$r['id']] = $r;
	}

	$sql = "SELECT * FROM `_balans_action`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['category_id']]['action'][] = $r;


	//���������� ��������� ������� ��� ������� ��������
	$sql = "SELECT
				`action_id`,
				COUNT(`id`) `count`
			FROM `_balans`
			WHERE `app_id`=".APP_ID."
			GROUP BY `action_id`";
	$q = query($sql);
	$actionCount = array();
	while($r = mysql_fetch_assoc($q))
		$actionCount[$r['action_id']] = $r['count'];

	$send = '';
	foreach($spisok as $r) {
		$send .=
			'<table class="_spisok">'.
				'<tr><td colspan="4" class="head">'.
						'<b>'.$r['id'].'.</b> '.
						'<b class="c-name">'.$r['name'].'</b>'.
						_iconDel($r).
						'<div val="'.$r['id'].'" class="img_add m30'._tooltip('�������� ��������', -63).'</div>'.
						_iconEdit($r).
				sa_balans_action_spisok($r['action'], $actionCount).
			'</table>';
	}

	return $send;
}
function sa_balans_action_spisok($arr, $count) {
	if(empty($arr))
		return '';

	$send = '';
	foreach($arr as $r)
		$send .=
			'<tr val="'.$r['id'].'">'.
				'<td class="id">'.$r['id'].
				'<td class="name'.($r['minus'] ? ' minus' : '').'">'.$r['name'].
				'<td class="count">'.(empty($count[$r['id']]) ? '' : $count[$r['id']]).
				'<td class="ed">'.
					'<div class="img_edit balans-action-edit"></div>'.
					'<div class="img_del balans-action-del"></div>';

	return $send;
}




function sa_client() {//��������� ��������
	switch(@$_GET['d1']) {
		case 'edit':   return sa_client_pole(1);
		case 'info':   return sa_client_pole(3);
		case 'category': return sa_client_category();
	}

	return
		saPath(array('name'=>'��������� ��������')).
		'<div id="sa-client">'.
			'<div class="headName">��������� ��������</div>'.
			'<a href="'.URL.'&p=sa&d=client&d1=edit">���� - ��������/�������������� �������</a>'.
			'<a href="'.URL.'&p=sa&d=client&d1=info">���� - ���������� � �������</a>'.
			'<br />'.
			'<a href="'.URL.'&p=sa&d=client&d1=category"><b>��������� �������� � ������������� �����</b></a>'.
		'</div>';
}
function sa_client_pole_type($type_id=0) {//���� ����� ��������
	$arr = array(
		1 => '��������/�������������� �������',
		3 => '���������� � �������'
	);
	if($type_id)
		return $arr[$type_id];
	return $arr;
}
function sa_client_pole($type_id) {
	return
		'<script>'.
			'var SA_CLIENT_POLE_TYPE_ID='.$type_id.','.
				'SA_CLIENT_POLE_TYPE_NAME="'.sa_client_pole_type($type_id).'";'.
		'</script>'.
		saPath(array(
			'name' => sa_client_pole_type($type_id),
			'name_d' => '��������� ��������',
			'link_d' => 'client'
		)).
		'<div id="sa-client-pole">'.
			'<div class="headName">'.
				'��������� �����: '.sa_client_pole_type($type_id).
				'<a class="add" onclick="saClientPoleEdit()">����� ����</a>'.
			'</div>'.
			'<div id="spisok">'.sa_client_pole_spisok($type_id).'</div>'.
		'</div>';
}
function sa_client_pole_spisok($type_id, $sel=false) {//����������� ������ ���� ����� �������
	//$sel - ����������� ������ ��� ����������� �������
	$sql = "SELECT *
			FROM `_client_pole`
			WHERE `type_id`=".$type_id."
			".($sel !== false ? " AND `id` NOT IN (".$sel.")" : '')."
			ORDER BY `id`";
	if(!$spisok = query_arr($sql))
		return '������ ����.';

	if($sel === false) {
		$sql = "SELECT
					`pole_id`,
					COUNT(`id`) `count`
				FROM `_client_pole_use`
				WHERE `pole_id` IN ("._idsGet($spisok).")
				GROUP BY `pole_id`";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q))
			$spisok[$r['pole_id']]['use'] = $r['count'];
	}

	$send =
		'<table class="_spisok">'.
			'<tr><th>'.
				'<th>������������'.
				'<th>��������'.
	   ($sel === false ? '<th>use' : '').
	   ($sel === false ? '<th>' : '');
	foreach($spisok as $r)
		$send .=
			'<tr'.($sel !== false ? ' class="sel" val="'.$r['id'].'"' : '').'>'.
				'<td class="id">'.$r['id'].
				'<td class="name">'.$r['name'].
				'<td>'.
					'<div class="about">'.$r['about'].'</div>'.
	   ($sel === false ? '<td class="use">'.(@$r['use'] ? $r['use'] : '') : '').
	   ($sel === false ? '<td class="ed">'._iconEdit($r)._iconDel($r + array('nodel'=>_num(@$r['use']))) : '');
	$send .= '</table>';

	return $send;
}

function sa_client_category() {
	$link = sa_client_category_link();
	return
		saPath(array(
			'name' => '��������� � ����',
			'name_d' => '��������� ��������',
			'link_d' => 'client'
		)).
		'<div id="sa-client-category">'.
			'<div class="headName">'.
				'��������� �������� � ������������� �����'.
				'<a class="add" onclick="saClientCategoryEdit()">����� ��������� ��������</a>'.
				'<a class="add edit" val="'.CLIENT_CATEGORY_ID.'">edit</a>'.
			'</div>'.

			$link.

			'<div class="hd">'.
				'����� ������'.
				'<button class="vk small" onclick="saClientCategoryPoleLoad('.CLIENT_CATEGORY_ID.',1)">�������� ����</button>'.
				'<button class="vk small red" onclick="_clientEdit('.CLIENT_CATEGORY_ID.')">����������</button>'.
			'</div>'.
			'<table class="_spisok mb1">'.
				'<tr><th class="w50">pole_id'.
					'<th class="w250">�������� ����'.
					'<th>���. ���������'.
					'<th class="ed">'.
			'</table>'.
			'<dl id="spisok1" class="_sort" val="_client_pole_use">'.sa_client_category_use(1).'</dl>'.

			'<div class="hd">'.
				'���������� � �������'.
				'<button class="vk small" onclick="saClientCategoryPoleLoad('.CLIENT_CATEGORY_ID.',3)">�������� ����</button>'.
			'</div>'.
			'<dl id="spisok3" class="_sort" val="_client_pole_use">'.sa_client_category_use(3).'</dl>'.
		'</div>';
}
function sa_client_category_link() {//���� ������ ����� ������ � ��������� CLIENT_CATEGORY_ID
	_clientCategoryOneCheck();

	$sql = "SELECT *
			FROM `_client_category`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	$spisok = query_arr($sql);

	if(!$id = _num(@$_GET['id']))
		$id = key($spisok);

	$exist = 0; //��������, ����� id ��������� ������� �������� � �������������, ����� �������� �� ���������
	foreach($spisok as $r)
		if($r['id'] == $id) {
			$exist = 1;
			break;
		}

	if(!$exist) {
		reset($spisok);
		$id = key($spisok);
	}

	$link = '';
	foreach($spisok as $r) {
		$sel = $r['id'] == $id ? ' sel' : '';
		$link .= '<a href="'.URL.'&p=sa&d=client&d1=category&id='.$r['id'].'" class="link'.$sel.'">'.$r['name'].'</a>';
	}

	define('CLIENT_CATEGORY_ID', $id);

	return '<div id="dopLinks">'.$link.'</div>';
}
function sa_client_category_use($type_id, $show=0) {//������������� ����� ��� ����������� ���� ������������
	$sql = "SELECT
				`u`.`id`,
				`u`.`pole_id`,
				`cp`.`name`,
				`cp`.`about`,
				`u`.`label`,
				`u`.`require`
			FROM
			    `_client_pole_use` `u`,
				`_client_pole` `cp`
			WHERE `app_id`=".APP_ID."
			  AND `category_id`=".CLIENT_CATEGORY_ID."
			  AND `cp`.`id`=`u`.`pole_id`
			  AND `cp`.`type_id`=".$type_id."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return '���� �� ����������.';

	$send = '';
	foreach($spisok as $r)
		$send .=
		'<dd val="'.$r['id'].'">'.
			'<table class="_spisok mb1'.($show == $r['id'] ? ' show' : '').'">'.
				'<tr><td class="w50 r">'.$r['pole_id'].
					'<td class="w250 curM">'.
						'<div class="name">'._br($r['label'] ? $r['label'] : $r['name']).($r['require'] ? ' *' : '').'</div>'.
						'<div class="label">'.($r['label'] ? $r['name'] : '').'</div>'.
						'<div class="about">'.$r['about'].'</div>'.
						'<input type="hidden" class="e-name" value="'.$r['name'].'" />'.
						'<input type="hidden" class="e-label" value="'.$r['label'].'" />'.
						'<input type="hidden" class="require" value="'.$r['require'].'" />'.
						'<input type="hidden" class="type_id" value="'.$type_id.'" />'.
					'<td>'.
					'<td class="ed">'._iconEdit($r)._iconDel($r).
			'</table>';

	return $send;
}





function sa_zayav() {//��������� ������
	/*
		+ ���� ��� ����������� ���������� � ������
		- ����� ���� ��������� � ������� ������ find
		+ ����� ������� ������ �������� � ������ ������
		+ ��� ����������� � name ������
		- ���������� ��� ��� �����������
		+ ���������-���������� �������:
			+ ������������ ��������
			+ ������ ��������
			+ ����������� ����� �� ������
	*/
	switch(@$_GET['d1']) {
		case 'edit':   return sa_zayav_pole(1);
		case 'unit': return sa_zayav_pole(4);
		case 'filter': return sa_zayav_pole(2);
		case 'info':   return sa_zayav_pole(3);
		case 'service': return sa_zayav_service();
	}

	return
		saPath(array('name'=>'��������� ������')).
		'<div id="sa-zayav">'.
			'<div class="headName">��������� ������</div>'.
			'<a href="'.URL.'&p=sa&d=zayav&d1=edit">���� - ��������/�������������� ������</a>'.
			'<a href="'.URL.'&p=sa&d=zayav&d1=unit">���� - ������� ������ ������</a>'.
			'<a href="'.URL.'&p=sa&d=zayav&d1=filter">���� - ������ ������</a>'.
			'<a href="'.URL.'&p=sa&d=zayav&d1=info">���� - ���������� � ������</a>'.
			'<br />'.
			'<a href="'.URL.'&p=sa&d=zayav&d1=service">���� ������������ ������ � ������������� �����</a>'.
		'</div>';
}
function sa_zayav_pole_type($type_id=0) {//���� ����� ������
	/*
		1 - edit: ��������/�������������� ������
		2 - filter: ������ ������
		3 - info: ���������� � ������
		4 - unit: ������� ������ ������
	*/
	$arr = array(
		1 => '��������/�������������� ������',
		2 => '������ ������',
		3 => '���������� � ������',
		4 => '������� ������ ������'
	);
	if($type_id)
		return $arr[$type_id];
	return $arr;
}
function sa_zayav_pole($type_id) {
	return
		'<script>'.
			'var SAZP_TYPE_ID='.$type_id.','.
				'SAZP_TYPE_NAME="'.sa_zayav_pole_type($type_id).'";'.
		'</script>'.
		saPath(array(
			'name' => sa_zayav_pole_type($type_id),
			'name_d' => '��������� ������',
			'link_d' => 'zayav'
		)).
		'<div id="sa-zayav-pole">'.
			'<div class="headName">'.
				'��������� �����: '.sa_zayav_pole_type($type_id).
				'<a class="add" onclick="saZayavPoleEdit()">����� ����</a>'.
			'</div>'.
			'<div id="spisok">'.sa_zayav_pole_spisok($type_id).'</div>'.
		'</div>';
}
function sa_zayav_pole_spisok($type_id, $sel=false) {//����������� ������ ���� ����� ������
	//$sel - ����������� ������ ��� ����������� �������
	$sql = "SELECT *
			FROM `_zayav_pole`
			WHERE `type_id`=".$type_id."
			".($sel !== false ? " AND `id` NOT IN (".$sel.")" : '')."
			ORDER BY `id`";
	if(!$spisok = query_arr($sql))
		return '������ ����.';

	if($sel === false) {
		$sql = "SELECT
					`pole_id`,
					COUNT(`id`) `count`
				FROM `_zayav_pole_use`
				WHERE `pole_id` IN ("._idsGet($spisok).")
				GROUP BY `pole_id`";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q))
			$spisok[$r['pole_id']]['use'] = $r['count'];
	}

	$send =
		'<table class="_spisok">'.
			'<tr><th>'.
				'<th>������������'.
				'<th>��������'.
	   ($sel === false ? '<th>use' : '').
	   ($sel === false ? '<th>' : '');
	foreach($spisok as $r)
		$send .=
			'<tr'.($sel !== false ? ' class="sel" val="'.$r['id'].'"' : '').'>'.
				'<td class="id">'.$r['id'].
				'<td class="name">'.$r['name'].
				'<td>'.
					'<div class="about">'.$r['about'].'</div>'.
   ($r['param1'] ? '<div class="param param1">'.$r['param1'].'</div>' : '').
   ($r['param2'] ? '<div class="param param2">'.$r['param2'].'</div>' : '').
	   ($sel === false ? '<td class="use">'.(@$r['use'] ? $r['use'] : '') : '').
	   ($sel === false ? '<td class="ed">'._iconEdit($r)._iconDel($r + array('nodel'=>_num(@$r['use']))) : '');
	$send .= '</table>';

	return $send;
}

function sa_zayav_service() {
	$link = sa_zayav_service_link();
	return
		saPath(array(
			'name' => '���� ������������',
			'name_d' => '��������� ������',
			'link_d' => 'zayav'
		)).
		'<div id="sa-zayav-service">'.
			'<div class="headName">'.
				'���� ������������ ������ � ������������� �����'.
				'<a class="add" onclick="saServiceEdit()">����� ��� ������������</a>'.
   (SERVICE_ID ?'<a class="add edit" val="'.SERVICE_ID.'">edit</a>' : '').
			'</div>'.
			$link.

			'<div class="zs-head">'.
				'����� ������'.
				'<button class="vk small" onclick="saZayavServicePoleAdd('.SERVICE_ID.',1)">�������� ����</button>'.
				'<button class="vk small red" onclick="_zayavEdit('.SERVICE_ID.')">����������</button>'.
			'</div>'.
			'<table class="_spisok">'.
				'<tr><th class="pole">pole_id'.
					'<th class="head">�������� ����'.
					'<th>'.
					'<th class="ed">'.
			'</table>'.
			'<dl id="spisok1" class="_sort" val="_zayav_pole_use">'.sa_zayav_service_use(1).'</dl>'.

			'<div class="zs-head">'.
				'������� ������ ������'.
				'<button class="vk small" onclick="saZayavServicePoleAdd('.SERVICE_ID.',4)">�������� ����</button>'.
			'</div>'.
			'<dl id="spisok4" class="_sort" val="_zayav_pole_use">'.sa_zayav_service_use(4).'</dl>'.

			'<div class="zs-head">'.
				'������ ������'.
				'<button class="vk small" onclick="saZayavServicePoleAdd('.SERVICE_ID.',2)">�������� ����</button>'.
			'</div>'.
			'<dl id="spisok2" class="_sort" val="_zayav_pole_use">'.sa_zayav_service_use(2).'</dl>'.

			'<div class="zs-head">'.
				'���������� � ������'.
				'<button class="vk small" onclick="saZayavServicePoleAdd('.SERVICE_ID.',3)">�������� ����</button>'.
			'</div>'.
			'<dl id="spisok3" class="_sort" val="_zayav_pole_use">'.sa_zayav_service_use(3).'</dl>'.
		'</div>';
}
function sa_zayav_service_link() {//���� ������ ����� ������ � ��������� SERVICE_ID
	$sql = "SELECT *
			FROM `_zayav_service`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	if(!$spisok = query_arr($sql)) {
		define('SERVICE_ID', 0);
		return '';
	}

	if(!$id = _num(@$_GET['id']))
		$id = key($spisok);
	$exist = 0; //��������, ����� id ���� ������ �������� � �������������, ����� �������� �� ���������
	foreach($spisok as $r)
		if($r['id'] == $id) {
			$exist = 1;
			break;
		}

	if(!$exist) {
		reset($spisok);
		$id = key($spisok);
	}

	$link = '';
	foreach($spisok as $r) {
		$sel = $r['id'] == $id ? ' sel' : '';
		$link .= '<a href="'.URL.'&p=sa&d=zayav&d1=service&id='.$r['id'].'" class="link'.$sel.'">'.$r['name'].'</a>';
	}

	define('SERVICE_ID', $id);

	return '<div id="dopLinks">'.$link.'</div>';
}
function sa_zayav_service_use($type_id, $show=0) {//������������� ����� ��� ����������� ���� ������������
	$sql = "SELECT
				`u`.`id`,
				`u`.`pole_id`,
				`zp`.`name`,
				`zp`.`about`,
				`u`.`label`,
				`u`.`require`,
				`zp`.`param1`,
				`u`.`param_v1`,
				`zp`.`param2`,
				`u`.`param_v2`
			FROM
			    `_zayav_pole_use` `u`,
				`_zayav_pole` `zp`
			WHERE `app_id`=".APP_ID."
			  AND `service_id`=".SERVICE_ID."
			  AND `zp`.`id`=`u`.`pole_id`
			  AND `zp`.`type_id`=".$type_id."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return '���� �� ����������.';

	$send = '';
	foreach($spisok as $r)
		$send .=
		'<dd val="'.$r['id'].'">'.
			'<table class="_spisok'.($show == $r['id'] ? ' show' : '').'">'.
				'<tr><td class="pole">'.$r['pole_id'].
					'<td class="head">'.
						'<div class="name">'._br($r['label'] ? $r['label'] : $r['name']).($r['require'] ? ' *' : '').'</div>'.
						'<div class="label">'.($r['label'] ? $r['name'] : '').'</div>'.
						'<div class="about">'.$r['about'].'</div>'.
		($r['param1'] ? '<div class="param'.($r['param_v1'] ? ' on' : '').'">'.$r['param1'].'</div>' : '').
		($r['param2'] ? '<div class="param'.($r['param_v2'] ? ' on' : '').'">'.$r['param2'].'</div>' : '').
						'<input type="hidden" class="e-name" value="'.$r['name'].'" />'.
						'<input type="hidden" class="e-label" value="'.$r['label'].'" />'.
						'<input type="hidden" class="require" value="'.$r['require'].'" />'.
						'<input type="hidden" class="param1"   value="'.$r['param1'].'" />'.
						'<input type="hidden" class="param_v1" value="'.$r['param_v1'].'" />'.
						'<input type="hidden" class="param2"   value="'.$r['param2'].'" />'.
						'<input type="hidden" class="param_v2" value="'.$r['param_v2'].'" />'.
						'<input type="hidden" class="type_id" value="'.$type_id.'" />'.
					'<td>'.
					'<td class="ed">'._iconEdit($r)._iconDel($r).
			'</table>';

	return $send;
}




function sa_tovar_measure() {//������� ��������� �������
	return
		saPath(array('name'=>'������: ������� ���������')).
		'<div id="sa-measure">'.
			'<div class="headName">'.
				'������: ������� ���������'.
				'<a class="add" onclick="saTovarMeasureEdit()">����� ������� ���������</a>'.
			'</div>'.
			'<div id="spisok" class="mar20">'.sa_tovar_measure_spisok().'</div>'.
		'</div>';
}
function sa_tovar_measure_spisok() {
	$sql = "SELECT
				*,
				0 `tovar`
			FROM `_tovar_measure`
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return '������ ����.';

	$sql = "SELECT
				DISTINCT `measure_id`,
				COUNT(`id`) `count`
			FROM `_tovar`
			GROUP BY `measure_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['measure_id']]['tovar'] = $r['count'];

	$send =
		'<table class="_spisok mb1">'.
			'<tr><th class="td-name">��������'.
				'<th class="fraction w50">�����'.
				'<th class="area w70">�������'.
				'<th class="tovar w50">������'.
				'<th class="ed">'.
		'</table>'.
		'<dl class="_sort" val="_tovar_measure">';
	foreach($spisok as $r)
		$send .= '<dd val="'.$r['id'].'">'.
		'<table class="_spisok mb1">'.
			'<tr><td class="td-name curM" val="'.$r['id'].'">'.
					'<b class="short">'.$r['short'].'</b>'.
					($r['name'] ? ' - ' : '').
					'<span class="name">'.$r['name'].'</span>'.
					'<div class="about">'.$r['about'].'</div>'.
				'<td class="fraction center w50">'.($r['fraction'] ? '��' : '').
				'<td class="area center w70">'.($r['area'] ? '��' : '').
				'<td class="tovar center w50">'.($r['tovar'] ? $r['tovar'] : '').
				'<td class="ed">'.
					_iconEdit($r).
					_iconDel($r + array('nodel'=>$r['tovar'])).
		'</table>';

	$send .= '</dl>';

	return $send;
}




function sa_color() {
	return
		saPath(array('name'=>'�����')).
		'<div id="sa-color">'.
			'<div class="headName">'.
				'�����'.
				'<a class="add">����� ����</a>'.
			'</div>'.
			'<div id="spisok">'.sa_color_spisok().'</div>'.
		'</div>';
}
function sa_color_spisok() {
	$sql = "SELECT
				*,
				0 `zayav`,
				0 `zp`
			FROM `_setup_color`
			ORDER BY `name`";
	if(!$spisok = query_arr($sql))
		return '����� �� �������.';

	$sql = "SELECT
				`color_id`,
				COUNT(`id`) `c`
			FROM `_zayav`
			WHERE `color_id`
			GROUP BY `color_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['color_id']]['zayav'] = $r['c'];

	$sql = "SELECT
				`color_dop`,
				COUNT(`id`) `c`
			FROM `_zayav`
			WHERE `color_dop`
			GROUP BY `color_dop`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['color_dop']]['zayav'] += $r['c'];

	$send =
		'<table class="_spisok">'.
			'<tr><th>�������'.
				'<th>����'.
				'<th>���-��<br />������'.
				'<th>���-��<br />���������'.
				'<th>';
	foreach($spisok as $r) {
		$r['nodel'] = $r['zayav'] || $r['zp'];
		$send .=
			'<tr>'.
				'<td class="predlog">'.$r['predlog'].
				'<td class="name">'.$r['name'].
				'<td class="zayav">'.($r['zayav'] ? $r['zayav'] : '').
				'<td class="zp">'.($r['zp'] ? $r['zp'] : '').
				'<td class="ed">'._iconEdit($r)._iconDel($r);
	}
	$send .= '</table>';
	return $send;
}




function sa_template() {//���������� ����������� �������� ����������
	return
		saPath(array('name'=>'������� ����������')).
		'<div id="sa-template">'.
			'<div class="headName">'.
				'������� �� ���������'.
				'<a class="add" onclick="saTemplateDefaultEdit()">��������</a>'.
			'</div>'.
			'<div id="spisok-def">'.sa_template_default_spisok().'</div>'.

			'<div class="headName">'.
				'���������� ��� ��������'.
				'<button class="vk small red fr" onclick="saTemplateVarEdit()">+ ����������</button>'.
				'<button class="vk small fr mr5" onclick="saTemplateGroupEdit()">����� ������</button>'.
			'</div>'.
			'<div id="spisok-var">'.sa_template_spisok().'</div>'.
		'</div>';
}
function sa_template_default_spisok() {
	$sql = "SELECT *
			FROM `_template_default`
			ORDER BY `id`";
	if(!$spisok = query_arr($sql))
		return '�������� ���.';

	$spisok = _attachValToList($spisok);

	$n = 1;
	$send =
		_attachJs(array('id'=>_idsGet($spisok, 'attach_id'))).
		'<table class="_spisok">';
	foreach($spisok as $r) {
		$send .=
			'<tr><td class="w15 r grey">'.($n++).
				'<td class="w150">'.
					'<b class="name">'.$r['name'].'</b>'.
					'<br />'.
					$r['attach_link'].
					'<input type="hidden" class="attach_id" value="'.$r['attach_id'].'" />'.
				'<td><span class="grey">����� ������:</span> '.
					'<span class="name_link">'.$r['name_link'].'</span>'.
					'<br />'.
					'<span class="grey">��� �����:</span> '.
					'<span class="name_file">'.$r['name_file'].'</span>'.
				'<td class="use w100 grey">'.$r['use'].
				'<td class="ed">'._iconEdit($r);
	}

	$send .= '</table>';

	return $send;
}
function sa_template_spisok() {
	$sql = "SELECT *
			FROM `_template_var_group`
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return '������ �� ���������.';

	foreach($spisok as $id => $r)
		$spisok[$id]['var'] = array();

	$sql = "SELECT *
			FROM `_template_var`
			ORDER BY `sort`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['group_id']]['var'][] = $r;

	$send = '';
	foreach($spisok as $r)
		$send .=
			'<div class="b mt20">'.$r['name'].':</div>'.
			sa_template_var($r['var'], $r['table_name']);

	return $send;
}
function sa_template_var($spisok, $table_name) {
	if(empty($spisok))
		return '';

	$send = '<table class="_spisok mar8">';
	foreach($spisok as $r) {
		$send .=
			'<tr><td class="w125 b">'.$r['v'].
				'<td>'.$r['name'].
				'<td class="w100">`'.$table_name.'`.`'.$r['col_name'].'`'.
				'<td class="ed">'._iconEdit($r + array('onclick'=>'saTemplateVarEdit('.$r['id'].')'));
	}

	$send .= '</table>';

	return $send;
}




function sa_count() {
	return
	saPath(array('name'=>'��������')).
	'<div id="sa-count">'.
		'<div class="headName">��������</div>'.
		'<button class="vk client">�������</button>'.
		'<br />'.
		'<br />'.
		'<button class="vk" onclick="zbDialog=saZayavBalans()">������</button>'.
		'<br />'.
		'<br />'.
		'<button class="vk tovar-set-find-update">�������� find �������-��������� <em></em></button>'.
		'<br />'.
		'<br />'.
		'<button class="vk tovar-articul-update">�������� �������� �������</button>'.
		'<br />'.
		'<br />'.
		'<button class="vk tovar-avai-check">�������� ������������ ������� ������</button>'.
	'</div>';
}




function sa_app() {
	return
		saPath(array('name'=>'����������')).
		'<div id="sa-app">'.
			'<div class="headName">'.
				'����������'.
				'<a class="add">����� ����������</a>'.
			'</div>'.
			'<div id="spisok">'.sa_app_spisok().'</div>'.
		'</div>';
}
function sa_app_spisok() {
	$sql = "SELECT
				*
			FROM `_app`
			ORDER BY `id`";
	if(!$spisok = query_arr($sql))
		return '���������� ���.';

	$send =
		'<table class="_spisok">'.
			'<tr><th>app_id'.
				'<th>��������'.
				'<th>title'.
				'<th class="w50">���'.
				'<th>���� ��������'.
				'<th>';
	foreach($spisok as $r) {
		$name = APP_ID == $r['id'] ? '<b>'.$r['app_name'].'</b>' : $r['app_name'];
		$name = LOCAL ? '<a href="'.API_HTML.'/index.php'.'?api_id='.$r['id'].'&viewer_id='.VIEWER_ID.'&p=sa&d=app">'.$name.'</a>' : $name;
		$send .=
			'<tr>'.
				'<td class="id">'.$r['id'].
					'<input type="hidden" class="name" value="'.$r['app_name'].'" />'.
					'<input type="hidden" class="secret" value="'.$r['secret'].'" />'.
				'<td class="app_name">'.$name.
				'<td class="title">'.$r['title'].
				'<td class="center">'.
					'<a onclick="saAppCacheClear('.$r['id'].')" class="'._tooltip('��������', -30).$r['js_values'].'</a>'.
				'<td class="dtime">'._dtimeAdd($r).
				'<td class="ed">'._iconEdit($r);
	}
	$send .= '</table>';
	return $send;
}




function sa_user() {
	$data = sa_user_spisok();
	return
	saPath(array('name'=>'������������')).
	'<div id="sa-user">'.
		'<div class="result">'.$data['result'].'</div>'.
		'<table class="tabLR">'.
			'<tr><td class="left">'.$data['spisok'].
				'<td class="right">'.
		'</table>'.
	'</div>';
}
function sa_user_spisok() {
	$sql = "SELECT *
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `last_seen`!='0000-00-00 00:00:00'
			ORDER BY `last_seen` DESC";
	$q = query($sql);
	$all = mysql_num_rows($q);
	$send = array(
		'all' => $all,
		'result' => '�������� '.$all.' �����������'._end($all, '�', '�', '��'),
		'spisok' => ''
	);
	while($r = mysql_fetch_assoc($q))
		$send['spisok'] .=
			'<div class="un" val="'.$r['viewer_id'].'">'.
				'<table class="tab">'.
					'<tr><td class="img"><a href="http://vk.com/id'.$r['viewer_id'].'" target="_blank"><img src="'.$r['photo'].'"></a>'.
						'<td class="inf">'.
							'<div class="dtime">'.
								'<div class="added'._tooltip('���� ����������', 10).FullDataTime($r['dtime_add']).'</div>'.
								(substr($r['last_seen'], 0, 16) != substr($r['dtime_add'], 0, 16) ?
									'<div class="enter'._tooltip('����������', 40).FullDataTime($r['last_seen']).'</div>'
								: '').
							'</div>'.
							'<a href="http://vk.com/id'.$r['viewer_id'].'" target="_blank"><b>'.$r['first_name'].' '.$r['last_name'].'</b></a>'.
							($r['admin'] ? '<b class="adm">�����</b>' : '').
							'<div class="city">'.$r['city_title'].($r['country_title'] ? ', '.$r['country_title'] : '').'</div>'.
							'<a class="action">��������</a>'.
				'</table>'.
			'</div>';
	return $send;
}
function sa_user_tab_test($tab, $col, $viewer_id) {//�������� ���������� ������� ��� ������������ � ����������� �������
	$sql = "SELECT COUNT(*)
			FROM information_schema.COLUMNS
			WHERE TABLE_SCHEMA='".GLOBAL_MYSQL_DATABASE."'
			  AND TABLE_NAME='".$tab."'
			  AND COLUMN_NAME='".$col."'";
	if(query_value($sql)) {
		$sql = "SELECT COUNT(*)
				FROM `".$tab."`
				WHERE `".$col."`=".$viewer_id;
		return query_value($sql);
	}
	return 0;
}

