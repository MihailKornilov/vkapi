<?php
function sa_appCount() {
	$sql = "SELECT COUNT(`id`) FROM `_app`";
	return query_value($sql, GLOBAL_MYSQL_CONNECT);
}
function sa_userCount() {
	$sql = "SELECT COUNT(*)
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID;
	return query_value($sql, GLOBAL_MYSQL_CONNECT);
}

function sa_global_index() {//����� ������ ������������������� ��� ���� ����������
	return
	'<div class="path">'.
		'<div id="app-id" val="'._app('app_name').'">'.APP_ID.'</div>'.
		sa_cookie_back().
		'�����������������'.
	'</div>'.
	'<div id="sa-index">'.
		'<h1>Global:</h1>'.
		'<a href="'.URL.'&p=sa&d=menu">������� �������� ����</a>'.
		'<a href="'.URL.'&p=sa&d=history">������� ��������</a>'.
		'<a href="'.URL.'&p=sa&d=rule">����� �����������</a>'.
		'<a href="'.URL.'&p=sa&d=balans">�������</a>'.
		'<a href="'.URL.'&p=sa&d=zayav">������</a>'.
		'<a href="'.URL.'&p=sa&d=color">�����</a>'.
		'<a href="'.URL.'&p=sa&d=count">��������</a>'.
		'<br />'.

		'<h1>App:</h1>'.
		'<a href="'.URL.'&p=sa&d=app">���������� ('.sa_appCount().')</a>'.
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
	return '<a href="'.URL.'&p='.@$_COOKIE['pre_p'].$d.$d1.$id.'">�����</a> � ';
}
function sa_path($v1, $v2='') {
	return
		'<div class="path">'.
			'<div id="app-id" val="'._app('app_name').'">'.APP_ID.'</div>'.
			sa_cookie_back().
			'<a href="'.URL.'&p=sa">�����������������</a> � '.
			$v1.($v2 ? ' � ' : '').
			$v2.
		'</div>';
}


function sa_menu() {//���������� �������� ��������
	return
		sa_path('������� �������� ����').
		'<div id="sa-menu">'.
			'<div class="headName">������� ����<a class="add">��������</a></div>'.
			'<div id="spisok">'.sa_menu_spisok().'</div>'.
		'</div>';
}
function sa_menu_spisok() {
	$sql = "SELECT
				`ma`.`id`,
				`m`.`id` `menu_id`,
				`m`.`name`,
				`m`.`about`,
				`m`.`p`,
				`ma`.`show`
			FROM
				`_menu` `m`,
				`_menu_app` `ma`
			WHERE `m`.`id`=`ma`.`menu_id`
			  AND `ma`.`app_id`=".APP_ID."
			ORDER BY `ma`.`sort`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	if(!mysql_num_rows($q))
		return '������ ����.';

	$spisok = array();
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']] = $r;

	$send =
		'<table class="_spisok">'.
			'<tr><th class="name">��������'.
				'<th class="p">Link'.
				'<th class="show">App<br />show'.
				'<th class="ed">'.
		'</table>'.
		'<dl class="_sort" val="_menu_app">';
	foreach($spisok as $r)
		$send .= '<dd val="'.$r['id'].'">'.
		'<table class="_spisok">'.
			'<tr><td class="name" val="'.$r['menu_id'].'">'.
					'<span>'.$r['name'].'</span>'.
					'<div class="about">'.$r['about'].'</div>'.
				'<td class="p">'.$r['p'].
				'<td class="show">'._check('show'.$r['id'], '', $r['show']).
				'<td class="ed">'._iconEdit($r).
		'</table>';

	return $send;
}


function sa_history() {//���������� �������� ��������
	$sql = "SELECT `id`,`name` FROM `_history_category` ORDER BY `sort`";
	$category = query_selJson($sql, GLOBAL_MYSQL_CONNECT);
	return
		'<script type="text/javascript">'.
			'var CAT='.$category.
		'</script>'.
		sa_path('������� ��������').
		'<div id="sa-history">'.
			'<div class="headName">'.
				'��������� ������� ��������'.
				'<a class="add const">�������� ���������</a>'.
				'<span> :: </span>'.
				'<a class="add" href="'.URL.'&p=sa&d=historycat">��������� ���������</a>'.
			'</div>'.
			'<div id="spisok">'.sa_history_spisok().'</div>'.
		'</div>';
}
function sa_history_spisok() {
	$sql = "SELECT * FROM `_history_type` ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
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
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		if(isset($spisok[$r['type_id']]))
			$spisok[$r['type_id']]['count'] = $r['count'];


	//���������� ��� ���������
	$sql = "SELECT `id`,`name` FROM `_history_category`";
	$cat = query_ass($sql, GLOBAL_MYSQL_CONNECT);

	//������� ������ �� ���������
	$sql = "SELECT `id`,`name`
			FROM `_history_category`
			ORDER BY `sort`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$category = array();
	while($r = mysql_fetch_assoc($q)) {
		$r['type'] = array();//������ ��������� $spisok, ������� ��������� � ����������
		$category[$r['id']] = $r;
	}

	//�������� ������ ���������
	$sql = "SELECT *
			FROM `_history_ids`
			ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
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
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q)) {
		$category[$r['category_id']]['type'][$r['type_id']] = $spisok[$r['type_id']];
		unset($spisok[$r['type_id']]);
	}

	$send =
		'<table class="_spisok">'.
			'<tr><th class="type_id">type_id'.
				'<th class="txt">������������'.
				'<th class="count">���-��'.
		'</table>';
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
		'<div class="cat-name">'.$name.'</div>'.
		'<table class="_spisok">';

	foreach($spisok as $r)
		$send .=
			'<tr><td class="type_id">'.$r['id'].
				'<td class="txt">'.
					'<textarea readonly id="txt'.$r['id'].'">'.$r['txt'].'</textarea>'.
					(!empty($r['cats']) ? '<div class="cats">'.implode('&nbsp;&nbsp;&nbsp;', $r['cats']).'</div>' : '').
				'<td class="count">'.(empty($r['count']) ? '' : $r['count']).
				'<td class="set">'.
					'<div class="img_edit" val="'.$r['id'].'"></div>'.
					//'<div class="img_del"></div>'.
					'<input type="hidden" id="ids'.$r['id'].'" value="'.implode(',', $r['ids']).'" />';
	$send .= '</table>';
	return $send;
}
function sa_history_cat() {//��������� ��������� ������� ��������
	return
		sa_path('<a href="'.URL.'&p=sa&d=history">������� ��������</a>', '��������� ���������').
		'<div id="sa-history-cat">'.
			'<div class="headName">��������� ������� ��������<a class="add">��������</a></div>'.
			'<div id="spisok">'.sa_history_cat_spisok().'</div>'.
		'</div>';
}
function sa_history_cat_spisok() {
	$sql = "SELECT * FROM `_history_category` ORDER BY `sort`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	if(!mysql_num_rows($q))
		return '������ ����.';

	$spisok = array();
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']] = $r;

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
		sa_path('����� �����������').
		'<div id="sa-rule">'.
			'<div class="headName">����� �����������<a class="add">��������</a></div>'.
			'<div id="spisok">'.sa_rule_spisok().'</div>'.
		'</div>';
}
function sa_rule_spisok() {
	$sql = "SELECT * FROM `_vkuser_rule_default` ORDER BY `key`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
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
		sa_path('�������').
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
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	if(!mysql_num_rows($q))
		return '������ ����.';

	$spisok = array();
	while($r = mysql_fetch_assoc($q)) {
		$r['action'] = array(); //������ �������� ��� ������ ���������
		$spisok[$r['id']] = $r;
	}

	$sql = "SELECT * FROM `_balans_action`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['category_id']]['action'][] = $r;


	//���������� ��������� ������� ��� ������� ��������
	$sql = "SELECT
				`action_id`,
				COUNT(`id`) `count`
			FROM `_balans`
			WHERE `app_id`=".APP_ID."
			GROUP BY `action_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
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



function sa_zayav() {//��������� ������
	/*
		+ ���� ��� ����������� ���������� � ������
		- ����� ���� ��������� � ������� ������ find
		+ ����� ������� ������ �������� � ������ ������
		+ ��� ����������� � name ������
		- ���������� ��� ��� �����������
		+ ���������-���������� �������:
			- ������������ ��������
			- ������ ��������
			- ����������� ����� �� ������
	*/
	switch(@$_GET['d1']) {
		case 'edit':   return sa_zayav_pole(1);
		case 'filter': return sa_zayav_pole(2);
		case 'info':   return sa_zayav_pole(3);
		case 'service': return sa_zayav_service();
	}

	return
		sa_path('��������� ������').
		'<div id="sa-zayav">'.
			'<div class="headName">��������� ������</div>'.
			'<a href="'.URL.'&p=sa&d=zayav&d1=edit">���� - ��������/�������������� ������</a>'.
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
	*/
	$arr = array(
		1 => '��������/�������������� ������',
		2 => '������ ������',
		3 => '���������� � ������'
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
		sa_path('<a href="'.URL.'&p=sa&d=zayav">��������� ������</a>', sa_zayav_pole_type($type_id)).
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
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '������ ����.';

	if($sel === false) {
		$sql = "SELECT
					`pole_id`,
					COUNT(`id`) `count`
				FROM `_zayav_pole_use`
				WHERE `pole_id` IN ("._idsGet($spisok).")
				GROUP BY `pole_id`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
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
   ($r['param1'] ? '<div class="param">'.$r['param1'].'</div>' : '').
	   ($sel === false ? '<td class="use">'.(@$r['use'] ? $r['use'] : '') : '').
	   ($sel === false ? '<td class="ed">'._iconEdit($r)._iconDel($r + array('nodel'=>_num(@$r['use']))) : '');
	$send .= '</table>';

	return $send;
}

function sa_zayav_service() {
	$link = sa_zayav_service_link();
	return
		sa_path('<a href="'.URL.'&p=sa&d=zayav">��������� ������</a>', '���� ������������').
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
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT)) {
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
				`u`.`param_v1`
			FROM
			    `_zayav_pole_use` `u`,
				`_zayav_pole` `zp`
			WHERE `app_id`=".APP_ID."
			  AND `service_id`=".SERVICE_ID."
			  AND `zp`.`id`=`u`.`pole_id`
			  AND `zp`.`type_id`=".$type_id."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
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
						'<input type="hidden" class="e-name" value="'.$r['name'].'" />'.
						'<input type="hidden" class="e-label" value="'.$r['label'].'" />'.
						'<input type="hidden" class="require" value="'.$r['require'].'" />'.
						'<input type="hidden" class="param1" value="'.$r['param1'].'" />'.
						'<input type="hidden" class="param_v1" value="'.$r['param_v1'].'" />'.
						'<input type="hidden" class="type_id" value="'.$type_id.'" />'.
					'<td>'.
					'<td class="ed">'._iconEdit($r)._iconDel($r).
			'</table>';

	return $send;
}



function sa_color() {
	return
		sa_path('�����').
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
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '����� �� �������.';

	$sql = "SELECT
				`color_id`,
				COUNT(`id`) `c`
			FROM `_zayav`
			WHERE `color_id`
			GROUP BY `color_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['color_id']]['zayav'] = $r['c'];

	$sql = "SELECT
				`color_dop`,
				COUNT(`id`) `c`
			FROM `_zayav`
			WHERE `color_dop`
			GROUP BY `color_dop`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
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





function sa_count() {
	return
		sa_path('��������').
		'<div id="sa-count">'.
			'<div class="headName">��������</div>'.
			'<button class="vk client">�������</button>'.
			'<br />'.
			'<br />'.
			'<button class="vk zayav">������</button>'.
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
		sa_path('����������').
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
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '���������� ���.';

	$send =
		'<table class="_spisok">'.
			'<tr><th>app_id'.
				'<th>��������'.
				'<th>title'.
				'<th>���� ��������'.
				'<th>';
	foreach($spisok as $r) {
		$send .=
			'<tr>'.
				'<td class="id">'.$r['id'].
					'<input type="hidden" class="secret" value="'.$r['secret'].'" />'.
				'<td class="app_name">'.(LOCAL ? '<a href="'.API_HTML.'/index.php'.'?api_id='.$r['id'].'&viewer_id='.VIEWER_ID.'">'.$r['app_name'].'</a>' : $r['app_name']).
				'<td class="title">'.$r['title'].
				'<td class="dtime">'._dtimeAdd($r).
				'<td class="ed">'._iconEdit($r);
	}
	$send .= '</table>';
	return $send;
}




function sa_user() {
	$data = sa_user_spisok();
	return
	sa_path('������������').
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
			ORDER BY `dtime_add` DESC";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
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
							($r['ws_id'] ? '<a class="ws_id" href="'.URL.'&p=sa&d=ws&id='.$r['ws_id'].'">ws: <b>'.$r['ws_id'].'</b></a>' : '').
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
			WHERE TABLE_SCHEMA='".MYSQL_DATABASE."'
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

function sa_ws() {
	$wsSpisok =
		'<tr><th>id'.
			'<th>������������'.
			'<th>�����'.
			'<th>���� ��������';
	$sql = "SELECT * FROM `_ws` WHERE `app_id`=".APP_ID." ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$count = mysql_num_rows($q);
	while($r = mysql_fetch_assoc($q))
		$wsSpisok .=
			'<tr><td class="id">'.$r['id'].
				'<td class="name'.($r['deleted'] ? ' del' : '').'">'.
					'<a href="'.URL.'&p=sa&d=ws&id='.$r['id'].'">'.$r['name'].'</a>'.
					'<div class="city">'.$r['city_name'].($r['country_id'] != 1 ? ', '.$r['country_name'] : '').'</div>'.
				'<td>'._viewer($r['admin_id'], 'viewer_link').
				'<td class="dtime">'.FullDataTime($r['dtime_add']);

	return
	sa_path('�����������').
	'<div id="sa-ws">'.
		'<div class="count">����� <b>'.$count.'</b> ���������'._end($count, '��', '��', '��').'.</div>'.
		'<table class="_spisok">'.$wsSpisok.'</table>'.
	'</div>';
}
function sa_ws_tables() {//�������, ������� ������������� � ����������
	$sql = "SHOW TABLES";
	$q = query($sql);
	$send = array();
	while($r = mysql_fetch_assoc($q)) {
		$v = $r[key($r)];
		if(query_value("SHOW COLUMNS FROM `".$v."` WHERE Field='ws_id'"))
			$send[$v] = $v;
	}

//	unset($send['vk_user']);
	return $send;
}
function sa_ws_info($id) {
	$sql = "SELECT * FROM `_ws` WHERE `app_id`=".APP_ID." AND `id`=".$id;
	if(!$ws = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		return sa_ws();

	$counts = '';
	foreach(sa_ws_tables() as $tab) {
		$c = query_value("SELECT COUNT(`id`) FROM `".$tab."`");
		if($c)
			$counts .= '<tr><td class="tb">'.$tab.':<td class="c">'.$c.'<td>';
	}

	$workers = '';
	if(!$ws['deleted']) {
		$sql = "SELECT *
				FROM `_vkuser`
				WHERE `app_id`=".APP_ID."
				  AND `worker`
				  AND `viewer_id`!=".$ws['admin_id'];
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		while($r = mysql_fetch_assoc($q))
			$workers .= _viewer($r['viewer_id'], 'viewer_link').'<br />';
	}

	return
	sa_path('�����������', $ws['name']).
	'<div id="sa-ws-info">'.
		'<div class="headName">���������� �� �����������</div>'.
		'<table class="tab">'.
			'<tr><td class="label">������������:<td><b>'.$ws['name'].'</b>'.
			'<tr><td class="label">�����:<td>'.$ws['city_name'].', '.$ws['country_name'].
			'<tr><td class="label">���� ��������:<td>'.FullDataTime($ws['dtime_add']).
			'<tr><td class="label">������:<td><div class="status'.($ws['deleted'] ? ' off' : '').'">'.($ws['deleted'] ? '�� ' : '').'�������</div>'.
			($ws['deleted'] ? '<tr><td class="label">���� ��������:<td>'.FullDataTime($ws['dtime_del']) : '').
			'<tr><td class="label">�������������:<td>'._viewer($ws['admin_id'], 'viewer_link').
			(!$ws['deleted'] && $workers ? '<tr><td class="label top">����������:<td>'.$workers : '').
		'</table>'.
		'<div class="headName">��������</div>'.
		'<div class="vkButton ws_status_change" val="'.$ws['id'].'"><button>'.($ws['deleted'] ? '������������' : '��������������').' �����������</button></div>'.
		'<br />'.
		(!$ws['deleted'] ?
			'<div class="vkButton ws_enter" val="'.$ws['admin_id'].'"><button>��������� ���� � ��� �����������</button></div><br />'
		: '').
		'<div class="vkCancel ws_del" val="'.$ws['id'].'"><button style="color:red">���������� �������� �����������</button></div>'.
		'<div class="headName">������ � ����</div>'.
		'<table class="counts">'.$counts.'</table>'.
		'<div class="headName">��������</div>'.
		'<div class="vkButton ws_client_balans" val="'.$ws['id'].'"><button>�������� ������� ��������</button></div>'.
		'<br />'.
		'<div class="vkButton ws_zayav_balans" val="'.$ws['id'].'"><button>�������� � �������: ����������, �������, ������� ������</button></div>'.
	'</div>';
}
