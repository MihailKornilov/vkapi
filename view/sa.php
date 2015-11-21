<?php
function sa_global_index() {//����� ������ ������������������� ��� ���� ����������
	return
	'<div class="path">'.sa_cookie_back().'�����������������</div>'.
	'<div id="sa-index">'.
		'<h1>Global:</h1>'.
		'<a href="'.URL.'&p=sa&d=menu">������� �������� ����</a>'.
		'<a href="'.URL.'&p=sa&d=history">������� ��������</a>'.
		'<a href="'.URL.'&p=sa&d=rule">����� �����������</a>'.
		'<a href="'.URL.'&p=sa&d=balans">�������</a>'.
		'<br />'.
		(function_exists('sa_index') ? sa_index() : '').
	'</div>';
}//sa_global_index()
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
	return '<a href="'.URL.'&p='.$_COOKIE['pre_p'].$d.$d1.$id.'">�����</a> � ';
}//sa_cookie_back()
function sa_path($v1, $v2='') {
	return
		'<div class="path">'.
			sa_cookie_back().
			'<a href="'.URL.'&p=sa">�����������������</a> � '.
			$v1.($v2 ? ' � ' : '').
			$v2.
		'</div>';
}//sa_path()


function sa_menu() {//���������� �������� ��������
	return
		sa_path('������� �������� ����').
		'<div id="sa-menu">'.
			'<div class="headName">������� ����<a class="add">��������</a></div>'.
			'<div id="spisok">'.sa_menu_spisok().'</div>'.
		'</div>';
}//sa_menu()
function sa_menu_spisok() {
	$sql = "SELECT
				`ma`.`id`,
				`m`.`name`,
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
				'<th class="p">����� ��� ������'.
				'<th class="show">����������<br />� ����������'.
				'<th class="ed">'.
		'</table>'.
		'<dl class="_sort" val="_menu_app">';
	foreach($spisok as $r)
		$send .= '<dd val="'.$r['id'].'">'.
		'<table class="_spisok">'.
			'<tr><td class="name">'.$r['name'].
				'<td class="p">'.$r['p'].
				'<td class="show">'._check('show'.$r['id'], '', $r['show']).
				'<td class="ed">'._iconEdit($r).
		'</table>';

	return $send;
}//sa_menu_spisok()


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
}//sa_history()
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
}//sa_history_spisok()
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
}//sa_history_spisok_page()
function sa_history_cat() {//��������� ��������� ������� ��������
	return
		sa_path('<a href="'.URL.'&p=sa&d=history">������� ��������</a>', '��������� ���������').
		'<div id="sa-history-cat">'.
			'<div class="headName">��������� ������� ��������<a class="add">��������</a></div>'.
			'<div id="spisok">'.sa_history_cat_spisok().'</div>'.
		'</div>';
}//sa_history_cat()
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
}//sa_history_cat_spisok()


function sa_rule() {//���������� �������� ��������
	return
		sa_path('����� �����������').
		'<div id="sa-rule">'.
			'<div class="headName">����� �����������<a class="add">��������</a></div>'.
			'<div id="spisok">'.sa_rule_spisok().'</div>'.
		'</div>';
}//sa_rule()
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
				'<th>��������<br />��� ������'.
				'<th>��������<br />��� ����������'.
				'<th>';
	foreach($spisok as $r)
		$send .=
			'<tr val="'.$r['id'].'">'.
				'<td class="key">'.
					'<b>'.$r['key'].'</b>'.
					'<div class="about">'.$r['about'].'</div>'.
				'<td class="admin">'._check('admin'.$r['id'], '', $r['value_admin']).
				'<td class="worker">'._check('worker'.$r['id'], '', $r['value_worker']).
				'<td class="set">'.
					'<div class="img_edit" val="'.$r['id'].'"></div>';
	//'<div class="img_del"></div>';
	$send .= '</table>';

	return $send;
}//sa_rule_spisok()


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
}//sa_balans()
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
}//sa_balans_spisok()
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
}//sa_balans_action_spisok()