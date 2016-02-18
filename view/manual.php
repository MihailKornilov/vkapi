<?php
function _manual() {
	if(@$_GET['d'] == 'part')
		return _manual_part();

	return _manual_main();
}

function _manualPart($id=false, $i='name') {
	$key = CACHE_PREFIX.'manual_part';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					`id`,
					`name`,
					`access`
				FROM `_manual_part`
				ORDER BY `sort`";
		if($arr = query_arr($sql, GLOBAL_MYSQL_CONNECT))
			xcache_set($key, $arr, 86400);
	}

	if($i == 'menu') {//���� ������ � ������ ��������
		if(!$id)
			$id = key($arr);

		$send = '';
		foreach($arr as $r) {
			if(!SA && !$r['access'])
				continue;
			$sel = $id == $r['id'] ? ' class="sel"' : '';
			$send .= '<a'.$sel.' href="'.URL.'&p=manual&d=part&part_id='.$r['id'].'">'.$r['name'].'</a>';
		}
		return '<div class="rightLink">'.$send.'</div>';
	}

	if($id == 'default') {//��������� �������� �� ���������, ���� ���� ������������� GET
		$id = _num(@$_GET['part_id']);
		if(isset($arr[$id]))
			return $id;
		return key($arr);
	}

	if($id == 'all')
		return $arr;

	if(!isset($arr[$id]))
		return '<span class="red">������ ������� <b>'.$id.'</b> �����������</span>';

	if($i == 'name')
		return $arr[$id]['name'];

	if($i == 'access')
		return _bool($arr[$id]['access']);

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
		'part' => '�������'
//		'new' => '������������',
//		'dialog' => '����������'
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
function _manual_main() {//������� ��������
	return
	_manualMenu().
	'<div id="manual">'.
		'<div class="_info">'.
			'<b>������</b> - ��� ����������� �� ��������� � ����������� ������ � ����������.'.
		'</div>'.
		_manual_menu_add().
		'<div id="part-spisok">'._manual_main_spisok().'</div>'.
	'</div>'.
	_manual_part_js();
}
function _manual_main_spisok() {//������� ��������
	$sql = "SELECT
				*,
				'' `sub`
			FROM `_manual_part`".
   (!SA ? " WHERE `access`" : '')."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '�������� ���.';

	$spisok = _manual_part_sub($spisok);

	$send = '';
	foreach($spisok as $r) {
		$noaccess = $r['access'] ? '' : ' noaccess';
		$send .=
			'<div class="part'.$noaccess.'">'.
				'<a class="part-head" href="'.URL.'&p=manual&d=part&part_id='.$r['id'].'">'.
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

function _manual_part() {//�������
	return
	_manual_part_js().
	_manualMenu().
	'<div id="manual-part">'.
		'<table class="tabLR">'.
			'<tr><td id="left">'._manual_content().
				'<td class="right">'._manualPart(_manualPart('default'), 'menu').
		'</table>'.
	'</div>';
}
function _manual_content() {//���������� � ����� �������
	if($id = _num(@$_GET['page_id']))
		return _manual_page_info($id);

	$part_id = _manualPart('default');

	//���� � ������� ����� ���� ��������, �� ������� ����� �� ��
	if($page_id = _manualPageCountInPart($part_id))
		return _manual_page_info($page_id);

	$access = _manualPart($part_id, 'access');

	if(!SA && !$access)
		return '������� ���.';

	return
	(SA && !$access ? '<div id="no-access">������ ���������� ��� ���������.</div>' : '').
	(SA ?
		_iconDel(array('id'=>$part_id,'class'=>'manual-part-del')).
		'<div class="img_edit manual-part-edit" val="'.$part_id.'#'.addslashes(_manualPart($part_id)).'#'.$access.'"></div>'
	: '').
	'<h1>'._manualPart($part_id).'</h1>'.
	_manual_page_spisok($part_id);
}
function _manual_page_spisok($part_id) {
	$sql = "SELECT *
			FROM `_manual`
			WHERE `part_id`=".$part_id.
	 (!SA ? " AND `access`" : '')."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '������� ���.';

	$end = end($spisok);
	$send = '';
	foreach($spisok as $r) {
		$noborder = $r['id'] == $end['id'] ? ' noborder' : '';
		$noaccess = $r['access'] ? '' : ' noaccess';
		$send .=
			'<div class="page-unit'.$noborder.$noaccess.'">'.
				'<a href="'.URL.'&p=manual&d=part&page_id='.$r['id'].'">'.$r['name'].'</a>'.
			'</div>';
	}

	return $send;
}
function _manualPageCountInPart($part_id) {//���� ���������� ������� � ������� = 1, ������� id ���� ��������
	if(defined('MANUAL_PAGE_ONE_IN_PART'))
		return MANUAL_PAGE_ONE_IN_PART;

	$page_id = 0;

	$sql = "SELECT *
			FROM `_manual`
			WHERE `part_id`=".$part_id.
	 (!SA ? " AND `access`" : '');
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	if(mysql_num_rows($q) == 1) {
		$r = mysql_fetch_assoc($q);
		$page_id = $r['id'];
	}

	define('MANUAL_PAGE_ONE_IN_PART', $page_id);
	return $page_id;
}
function _manual_page_info($id) {//����������� �������� �������
	$sql = "SELECT *
			FROM `_manual`
			WHERE `id`=".$id.
	 (!SA ? " AND `access`" : '');
	if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		return _err('�������� �� ����������.');

	$_GET['part_id'] = $r['part_id'];

	return
	(SA && !$r['access'] ? '<div id="no-access">�������� ���������� ��� ���������.</div>' : '').
	(!_manualPageCountInPart($r['part_id']) ?
		'<a class="back" href="'.URL.'&p=manual&d=part&part_id='.$r['part_id'].'"><< ����� � ������� <b>'._manualPart($r['part_id']).'</b></a>'
	: '').
	(SA ?
		_iconDel(array('dtime_add'=>'','class'=>'manual-page-del') + $r).
		'<div class="img_edit manual-page-edit" val="'.$r['id'].'#'.$r['access'].'#'.$r['part_id'].'#'.$r['part_sub_id'].'"></div>'.
		'<textarea>'.$r['content'].'</textarea>'
	: '').
	'<h1>'.$r['name'].'</h1>'.
	'<h2>'._br($r['content']).'</h2>'.
	'<div id="created">'.
		'�������� ������� '.FullDataTime($r['dtime_add']).'.'.
		($r['count_upd'] ?
			'<br />����� ���'._end($r['count_upd'], '�', '�').' '.$r['count_upd'].' �������'._end($r['count_upd'], '�', '�', '�').', '.
			'��������� '.FullDataTime($r['dtime_upd']).'.'
		: '').
	'</div>';
}
