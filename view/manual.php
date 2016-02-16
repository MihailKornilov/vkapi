<?php
function _manual() {
	if($id = _num(@$_GET['page_id']))
		return _manual_page($id);
	return _manual_main();
}

function _manualPart($id=false, $i='name') {
	$key = CACHE_PREFIX.'manual_part';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					`id`,
					`name`
				FROM `_manual_part`
				ORDER BY `sort`";
		if($arr = query_arr($sql, GLOBAL_MYSQL_CONNECT))
			xcache_set($key, $arr, 86400);
	}

	if($id == 'all')
		return $arr;

	if(!isset($arr[$id]))
		return '<span class="red">������ ������� <b>'.$id.'</b> �����������</span>';

	if($i == 'name')
		return $arr[$id]['name'];

	return '<span class="red">����������� ���� ������� �������: <b>'.$i.'</b></span>';
}
function _manualPartSub($id=false, $i='name') {
	$key = CACHE_PREFIX.'manual_part_sub';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					`id`,
					`name`
				FROM `_manual_part_sub`
				ORDER BY `sort`";
		if($arr = query_arr($sql, GLOBAL_MYSQL_CONNECT))
			xcache_set($key, $arr, 86400);
	}

	if($id == 'all')
		return $arr;

	if(!isset($arr[$id]))
		return '<span class="red">��������� ������� <b>'.$id.'</b> �����������</span>';

	if($i == 'name')
		return $arr[$id]['name'];

	return '<span class="red">����������� ���� ���������� �������: <b>'.$i.'</b></span>';
}

function _manualMenu() {//������� ��������� ����
	$menu = array(
		'main' => '������',
		'part' => '�������',
		'new' => '������������',
		'dialog' => '����������'
	);

	if(empty($_GET['d']) || !isset($menu[@$_GET['d']]))
		$_GET['d'] = 'main';

	$link = '';
	foreach($menu as $d => $name) {
		$sel = $d == $_GET['d'] ? ' sel' : '';
		$link .=
			'<a class="p'.$sel.'" href="'.URL.'&p=manual&d='.$d.'">'.
				$name.
			'</a>';
	}

	return
	'<div id="_menu">'.
		$link.
		'<a class="back" href="'.URL.'">'._app('name').'</a>'.
	'</div>';
}
function _manual_path($part_id=0,$part_sub_id=0) {//����
	return
	'<div class="path">'.
		'<a href="'.URL.'">'._app('name').'</a>'.
		' � '.
		($part_id ? '<a href="'.URL.'&p=manual">������</a> � '._manualPart($part_id) : '������').
		($part_sub_id ? ' � '._manualPartSub($part_sub_id) : '').
	'</div>';
}
function _manual_main() {//������� ��������
	return
	_manualMenu().
	'<div id="manual">'.
		'<h1>����������'._manual_menu_add().'</h1>'.
		'<div id="part-spisok">'._manual_part().'</div>'.
	'</div>'.
	_manual_part_js();
}
function _manual_part() {//������� ��������
	$sql = "SELECT
				*,
				'' `sub`
			FROM `_manual_part`
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '�������� ���.';

	$spisok = _manual_part_sub($spisok);

	$send = '';
	foreach($spisok as $r) {
		$send .=
			'<div class="part">'.
				'<a class="part-head">'.
					'<span class="name">'.$r['name'].'</span>'.
				'</a>'.
				'<div class="part-sub">'.$r['sub'].'</div>'.
			'</div>';
	}

	return $send;
}
function _manual_part_sub($spisok) {//���������� ����������� � ��������
	$sql = "SELECT * FROM `_manual_part_sub` ORDER BY `sort`";
	if(!$sub = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return $spisok;

	foreach($sub as $r) {
		$spisok[$r['part_id']]['sub'] .=
			'<a>'.
				$r['name'].
			'</a>';
	}

	return $spisok;
}
function _manual_menu_add() {//����� ������ ���� ��� SA
	if(!SA)
		return '';

	return
	'<div id="manual-add">'.
		'<a id="part-add">����� ������</a>'.
		'<tt> :: </tt>'.
		'<a id="part-sub-add">����� ���������</a>'.
		'<tt> :: </tt>'.
		'<a id="page-add">����� ��������</a>'.
	'</div>';
}
function _manual_part_js() {
	$sql = "SELECT `id`,`name` FROM `_manual_part`";
	$part = query_selJson($sql, GLOBAL_MYSQL_CONNECT);

	return
	'<script>'.
		'var MANUAL_PART_SPISOK='.$part.','.
			'MANUAL_PART_SUB_SPISOK='.Gvalues_obj('_manual_part_sub', '`part_id`,`name`', 'part_id', GLOBAL_MYSQL_CONNECT).';'.
	'</script>';
}

function _manual_page($id) {//����������� �������� �������
	$sql = "SELECT *
			FROM `_manual`
			WHERE `id`=".$id;
	if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		return _err('�������� �� ����������.');

	return
	_manual_path($r['part_id'], $r['part_sub_id']).
	$r['name'];
}
