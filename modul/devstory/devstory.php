<?php
/*

--- ������� (�������) ���������� ���������� ---
������: 2016-09-03

*/

function _devstory_script() {//������������ �������
	if(PIN_ENTER)
		return '';

	return
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/devstory/devstory'.MIN.'.css?'.VERSION.'" />'.
		'<script src="'.API_HTML.'/modul/devstory/devstory'.MIN.'.js?'.VERSION.'"></script>';
}
function _devstory_footer() {//����� � ������ ����� ����������
	if(!SA)
		return '';

	if(PIN_ENTER)
		return '';

	$startYear = substr(_app('dtime_add'), 0, 4);
	$curYear = strftime('%Y');

	$year = $startYear == $curYear ? $curYear : $startYear.'-'.$curYear;

	return
	'<div id="devstory-footer">'.
		_app('app_name').
		'<span class="grey">'.$year.'</span>'.
		_viewer(VIEWER_ID, 'viewer_link_my').
	($_GET['p'] != 'devstory' ?
		'<a href="'.URL.'&p=devstory" class="dev-page'._tooltip('������� ���������� ����������', -158, 'r').'����������</a>'
	: '').
	'</div>';
}

function _devstoryPart($id=false, $i='name') {
	$key = CACHE_PREFIX.'devstory_part';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT *
				FROM `_devstory_part`
				ORDER BY `sort`,`id`";
		if($arr = query_arr($sql))
			xcache_set($key, $arr, 86400);
	}

	if($id == 'all')
		return $arr;

	//������ JS ��� select
	if($id == 'js') {
		$spisok = array();
		foreach($arr as $r) {
			if(!$r['parent_id'])
				continue;
			$spisok[$r['parent_id']][$r['id']] = $r['name'];
		}

		$js = array();
		foreach($spisok as $uid => $r)
			$js[] = $uid.':'._selJson($r);

		return '{'.implode(',', $js).'}';
	}

	if(!isset($arr[$id]))
		return _cacheErr('����������� id ������� ����������', $id);

	if($i == 'path') {
		$send = '';
		$u = $arr[$id];
		while($u['parent_id']) {
			$send .= ' � '.$u['name'].'</b>';
			$u = $arr[$u['parent_id']];
		}
		return '<b>'.$u['name'].'</b>'.$send;
	}

	if($i == 'parent_name'){
		$parent_id = $arr[$id]['parent_id'];
		return $arr[$parent_id]['name'];
	}

	if(!isset($arr[$id][$i]))
		return _cacheErr('����������� ���� ������� ����������', $i);

	return $arr[$id][$i];
}
function _devstoryStatus($id) {//�������� ��������
	$arr = array(
		0 => '��������',
		1 => '� ��������',
		2 => '�����',
		3 => '���������',
		4 => '��������'
	);
	return $arr[$id];
}

function _devstory() {//�������� ��������
	switch(@$_GET['d']) {
		default:
		case 'main': $content = _devstory_part(); break;
		case 'task': $content = _devstory_task(); break;
		case 'offer': $content = _devstory_offer(); break;
		case 'about': $content = _devstory_about(); break;
	}
	return
	_devstoryMenu().
	'<div id="devstory">'.
		$content.
	'</div>'.
	'<script>'.
		'var DEVSTORY_PART_SPISOK='._devstoryPart('js').','.
			'DTIME="'.strftime('%Y-%m-%d %H:%M:00').'";'.
	'</script>';
}
function _devstoryMenu() {//������� ��������� ����
	$menu = array(
		'main' => '���������� - �������',
		'task' => '������',
		'offer' => '�����������',
		'about' => '� �������'
	);

	if(empty($_GET['d']) || !isset($menu[@$_GET['d']]))
		$_GET['d'] = 'main';

	$link = '';
	foreach($menu as $d => $name) {
		$sel = $_GET['d'] == $d ? ' sel' : '';
		$link .=
			'<a class="p'.$sel.'" href="'.URL.'&p=devstory&d='.$d.'">'.
				$name.
			'</a>';
	}

	return
	'<div id="_menu">'.
		$link.
		'<a class="back" href="'.URL.'">'._app('app_name').'</a>'.
	'</div>';
}

function _devstory_part() {
	return
		'<div class="headName m1">'.
			'�������� �������'.
			(SA ? '<a class="add" onclick="devStoryMainEdit()">����� ������</a>' : '').
		'</div>'.
		'<div id="part-spisok">'._devstory_part_spisok().'</div>';
}
function _devstory_part_spisok() {//������ ��������
	$sql = "SELECT *
			FROM `_devstory_part`
			WHERE !`parent_id`
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return '������ ����.';

	$send = '<dl class="'.(SA ? '_sort' : '').'" val="_devstory_part">';
	foreach($spisok as $r)
		$send .=
			'<dd val="'.$r['id'].'">'.
				'<table class="part-u w100p">'.
					'<tr><td class="'.(SA ? 'curM' : '').'">'.
							'<a class="name">'.$r['name'].'</a>'.
					(SA ?
						'<td class="ed">'.
							'<div class="img_add m30'._tooltip('�������� ������', -94, 'r').'</div>'.
							_iconEdit($r)
					: '').
				'</table>';
	$send .= '</dl>';
	return $send;
}



function _devstory_task() {
	return
		'<div class="headName m1">������ �����</div>'.
		'<div id="spisok">'._devstory_task_spisok().'</div>';
}
function _devstory_task_spisok() {
	$sql = "SELECT *
			FROM `_devstory_task`
			WHERE !`deleted`
			ORDER BY `dtime_add` DESC";
	if(!$spisok = query_arr($sql))
		return '����� �� �������.';

	//���� �� ������������� ������. ���� ����, �� ���������� ��������� ������ ������ �� ����������
	$sql = "SELECT COUNT(*)
			FROM `_devstory_task`
			WHERE `status_id`=1";
	$started = query_value($sql);

	$send = '';
	foreach($spisok as $r) {
		$send .=
			'<div val="'.$r['id'].'" class="task-u status'.$r['status_id'].'">'.
				'<div class="pp">'.
			   (SA ? _iconEdit($r) : '').
					'<div class="dtime">'.FullDataTime($r['dtime_add'], 1).'</div>'.
					_devstoryPart($r['part_id'], 'path').
				'</div>'.
				'<table class="w100p">'.
					'<tr><td class="top">'.
							'<div class="name">'.$r['name'].'</div>'.
							'<div class="about">'._br($r['about']).'</div>'.
						'<td class="td-status top w150">'.
							'<div class="st center">'._devstoryStatus($r['status_id']).'</div>'.

	(SA && $r['status_id'] == 0 && !$started ?
								'<a class="st-action start">������ ����������</a>'
	: '').

	(SA && $r['status_id'] == 1 ? '<a class="st-action pause">�������������</a>' : '').
	(SA && $r['status_id'] == 1 ? '<a class="st-action ready">���������</a>' : '').

	(SA && $r['status_id'] == 2 && !$started ? '<a class="st-action next">����������</a>' : '').

	(SA && $r['status_id'] == 1 ? '<a class="st-action cancel red">��������</a>' : '').
	(SA && $r['status_id'] == 2 ? '<a class="st-action cancel red">��������</a>' : '').

				'</table>'.
				'<input type="hidden" class="part_id" value="'._devstoryPart($r['part_id'], 'parent_id').'" />'.
				'<input type="hidden" class="part_name" value="'._devstoryPart($r['part_id'], 'parent_name').'" />'.
				'<input type="hidden" class="part_sub_id" value="'.$r['part_id'].'" />'.
				'<input type="hidden" class="part_sub_name" value="'._devstoryPart($r['part_id']).'" />'.
			'</div>';
	}

	return $send;
}




function _devstory_offer() {//����������� � ����� ������������ ����������
	return
		'�����������';
}


function _devstory_about() {//�������� ������ �������� ����������
	return
		'about';
}



