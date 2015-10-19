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
function _setup($v) {
	$page = array(
		'my' => '��� ���������',
		'worker' => '����������'
	) + $v;

	if(!VIEWER_ADMIN)
		unset($page['worker']);

	$d = empty($_GET['d']) || empty($page[$_GET['d']]) ? 'my' : $_GET['d'];
	$func = 'setup_'.$d;
	$left = function_exists($func) ? $func() : setup_my();

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
			'<table class="unit" val="'.$r['viewer_id'].'">'.
				'<tr><td class="photo"><img src="'.$r['photo'].'">'.
					'<td>'.($r['admin'] ? '' : '<div class="img_del"></div>').
						'<a class="name">'.$r['first_name'].' '.$r['last_name'].'</a>'.
//					($r['admin'] ? '' : '<a href="'.URL.'&p=setup&d=worker&id='.$r['viewer_id'].'" class="rules_set">��������� �����</a>').
			'</table>';
	}
	return $send;
}//setup_worker_spisok()