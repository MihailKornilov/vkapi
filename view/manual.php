<?php
function _manual() {
	if(@$_GET['d'] == 'part')
		return _manual_path();

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

	if($i == 'menu') {//меню справа в списке разделов
		if(!$id)
			$id = key($arr);

		$send = '';
		foreach($arr as $r) {
			$sel = $id == $r['id'] ? ' class="sel"' : '';
			$send .= '<a'.$sel.' href="'.URL.'&p=manual&d=part&part_id='.$r['id'].'">'.$r['name'].'</a>';
		}
		return '<div class="rightLink">'.$send.'</div>';
	}

	if($id == 'default') {//получение значения по умолчанию, либо если соответствует GET
		$id = _num(@$_GET['part_id']);
		if(isset($arr[$id]))
			return $id;
		return key($arr);
	}

	if($id == 'all')
		return $arr;

	if(!isset($arr[$id]))
		return '<span class="red">раздел мануала <b>'.$id.'</b> отсутствует</span>';

	if($i == 'name')
		return $arr[$id]['name'];

	return '<span class="red">неизвестный ключ раздела мануала: <b>'.$i.'</b></span>';
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
		return '<span class="red">подраздел мануала <b>'.$id.'</b> отсутствует</span>';

	if($i == 'name')
		return $arr[$id]['name'];

	return '<span class="red">неизвестный ключ подраздела мануала: <b>'.$i.'</b></span>';
}

function _manualMenu() {//разделы основного меню
	$menu = array(
		'main' => 'Мануал',
		'part' => 'Разделы'
//		'new' => 'Нововведения',
//		'dialog' => 'Обсуждения'
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
function _manual_main() {//главная страница
	return
	_manualMenu().
	'<div id="manual">'.
		'<div class="_info">'.
			'<b>Мануал</b> - это руководство по грамотной и эффективной работе в приложении.'.
		'</div>'.
		_manual_menu_add().
		'<div id="part-spisok">'._manual_part().'</div>'.
	'</div>'.
	_manual_part_js();
}
function _manual_part() {//главная страница
	$sql = "SELECT
				*,
				'' `sub`
			FROM `_manual_part`
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return 'Разделов нет.';

	$spisok = _manual_part_sub($spisok);

	$send = '';
	foreach($spisok as $r) {
		$send .=
			'<div class="part">'.
				'<a class="part-head" href="'.URL.'&p=manual&d=part&part_id='.$r['id'].'">'.
					'<span class="name">'.$r['name'].'</span>'.
				'</a>'.
				'<div class="part-sub">'.$r['sub'].'</div>'.
			'</div>';
	}

	return $send;
}
function _manual_part_sub($spisok) {//присвоение подразделов к разделам
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
function _manual_menu_add() {//вывод списка меню для SA
	if(!SA)
		return '';

	return
	'<div id="manual-add">'.
		'<a id="part-add">Новый раздел</a>'.
		'<tt> :: </tt>'.
		'<a id="part-sub-add">Новый подраздел</a>'.
		'<tt> :: </tt>'.
		'<a id="page-add">Новая страница</a>'.
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

function _manual_path() {//путь
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
function _manual_content() {//содержание с левой стороны
	if($id = _num(@$_GET['page_id']))
		return _manual_page($id);


	$part_id = _manualPart('default');

	//если в разделе всего одна страница, то переход сразу на неё
	$sql = "SELECT *
			FROM `_manual`
			WHERE `part_id`=".$part_id;
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	if(mysql_num_rows($q) == 1) {
		$r = mysql_fetch_assoc($q);
		return _manual_page($r['id']);
	}

	return
	'<h1>'._manualPart($part_id).'</h1>'.
	_manual_page_spisok($part_id);
}
function _manual_page_spisok($part_id) {
	$sql = "SELECT *
			FROM `_manual`
			WHERE `part_id`=".$part_id."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return 'Страниц нет.';

	$end = end($spisok);
	$send = '';
	foreach($spisok as $r) {
		$send .=
			'<div class="page-unit'.($r['id'] == $end['id'] ? ' noborder' : '').'">'.
				'<a href="'.URL.'&p=manual&d=part&page_id='.$r['id'].'">'.$r['name'].'</a>'.
			'</div>';
	}

	return $send;
}
function _manual_page($id) {//отображение страницы мануала
	$sql = "SELECT *
			FROM `_manual`
			WHERE `id`=".$id;
	if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		return _err('Страницы не существует.');

	$_GET['part_id'] = $r['part_id'];

	return
	(SA ?
		_iconDel($r).
		'<div class="img_edit" val="'.$r['id'].'#'.$r['part_id'].'#'.$r['part_sub_id'].'"></div>'.
		'<textarea>'.$r['content'].'</textarea>'
	: '').
	'<h1>'.$r['name'].'</h1>'.
	'<h2>'._br($r['content']).'</h2>'.
	'<div id="created">'.
		'Страница создана '.FullDataTime($r['dtime_add']).'.'.
		($r['count_upd'] ?
			'<br />Всего был'._end($r['count_upd'], 'а', 'о').' '.$r['count_upd'].' редакци'._end($r['count_upd'], 'я', 'и', 'й').', '.
			'последняя '.FullDataTime($r['dtime_upd']).'.'
		: '').
	'</div>';
}
