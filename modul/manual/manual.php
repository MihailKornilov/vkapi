<?php
function _manual() {
	header('Location:'.URL.'&p=56');
	exit;

	switch(@$_GET['d']) {
		case 'new': return _manual_new();
		case 'part':
			if(@$_GET['part_id'] == 1) {
				$_GET['d'] = 'new';
				return _manual_new();
			}
			return _manual_part();
		case 'action': return _manual_action();
	}

}

function _manual_script() {//скрипты и стили
	$id = _num(@$_GET['p']);
	if($id != 10 && _menuCache('parent_main_id', $id) != 10)
		return '';

	return
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/manual/manual'.MIN.'.css?'.VERSION.'" />'.
		'<script src="'.API_HTML.'/modul/manual/manual'.MIN.'.js?'.VERSION.'"></script>';
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
		if($arr = query_arr($sql))
			xcache_set($key, $arr, 86400);
	}

	if($i == 'menu') {//меню справа в списке разделов
		if(!$id)
			$id = key($arr);

		$send = '';
		foreach($arr as $r) {
			if(!SA && !$r['access'])
				continue;
			$sel = $id == $r['id'] ? ' class="sel"' : '';
			$send .= '<a'.$sel.' href="'.URL.'&p=57&part_id='.$r['id'].'">'.$r['name'].'</a>';
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

	if($i == 'access')
		return _bool($arr[$id]['access']);

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
		if($arr = query_arr($sql))
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
	//подсветка выбранного раздела, в том числе, если дочерняя страница
	$sel_id = _num($_GET['p']);

	$link = '';
	foreach(_menuCache() as $id => $r) {
		if($r['func_menu'] != '_manualMenu')
			continue;
		if(!$r['parent_id'])
			continue;
		if(!SA && $r['hidden'])
			continue;

		$sel = $id == $sel_id ? ' sel' : '';
		$link .= '<a class="p'.$sel.'" href="'.URL.'&p='.$id.'">'.$r['name'].'</a>';
	}

	return
	'<div id="_menu">'.
		$link.
		'<a class="back" href="'.URL.'&p=2">'._app('app_name').'</a>'.
	'</div>';

}
function _manual_main() {//главная страница
	return
	'<div id="manual">'.
		'<div class="_info">'.
			'<b>Мануал</b> - это руководство по грамотной и эффективной работе в приложении.'.
		'</div>'.
		_manual_menu_add().
		'<div id="part-spisok">'._manual_main_spisok().'</div>'.
	'</div>'.
	_manual_part_js();
}
function _manual_main_spisok() {//главная страница
	$sql = "SELECT
				*,
				'' `sub`
			FROM `_manual_part`".
   (!SA ? " WHERE `access`" : '')."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return 'Разделов нет.';

	$spisok = _manual_part_sub($spisok);

	$send = '';
	foreach($spisok as $r) {
		$noaccess = $r['access'] ? '' : ' noaccess';
		$send .=
			'<div class="part'.$noaccess.'">'.
				'<a class="part-head" href="'.URL.'&p=57&part_id='.$r['id'].'">'.
					'<span class="name">'.$r['name'].'</span>'.
				'</a>'.
				'<div class="part-sub">'.$r['sub'].'</div>'.
			'</div>';
	}

	return $send;
}
function _manual_part_sub($spisok) {//присвоение подразделов к разделам
	$sql = "SELECT * FROM `_manual_part_sub` ORDER BY `sort`";
	if(!$sub = query_arr($sql))
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
	$part = query_selJson($sql);

	return
	'<script>'.
		'var MANUAL_PART_SPISOK='.$part.','.
			'MANUAL_PART_SUB_SPISOK='.Gvalues_obj('_manual_part_sub', '`part_id`,`name`', 'part_id').';'.
	'</script>';
}

function _manual_part() {//разделы
	return
	_manual_part_js().
	'<div id="manual-part">'.
		'<table class="tabLR">'.
			'<tr><td id="left">'._manual_content().
				'<td class="right">'._manualPart(_manualPart('default'), 'menu').
		'</table>'.
	'</div>';
}
function _manual_content() {//содержание с левой стороны
	if($id = _num(@$_GET['page_id']))
		return _manual_page_info($id);

	$part_id = _manualPart('default');

	//если в разделе всего одна страница, то переход сразу на неё
	if($page_id = _manualPageCountInPart($part_id))
		return _manual_page_info($page_id);

	$access = _manualPart($part_id, 'access');

	if(!SA && !$access)
		return 'Раздела нет.';

	return
	(SA && !$access ? '<div id="no-access">Раздел недоступен для просмотра.</div>' : '').
	(SA ?
		'<div id="mp-edit">'.
			'<div class="img_edit manual-part-edit" val="'.$part_id.'#'.addslashes(_manualPart($part_id)).'#'.$access.'"></div>'.
			_iconDel(array('id'=>$part_id,'class'=>'manual-part-del')).
		'</div>'
	: '').
	'<div id="head">'.
		(SA ? '<span>{part'.$part_id.'}</span>' : '').
		_manualPart($part_id).
	'</div>'.
	_manual_page_spisok($part_id);
}
function _manual_page_spisok($part_id) {
	$sql = "SELECT *
			FROM `_manual`
			WHERE `part_id`=".$part_id.
	 (!SA ? " AND `access`" : '')."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return 'Страниц нет.';

	$end = end($spisok);
	$send = '';
	foreach($spisok as $r) {
		$noborder = $r['id'] == $end['id'] ? ' noborder' : '';
		$noaccess = $r['access'] ? '' : ' noaccess';
		$send .=
			'<div class="page-unit'.$noborder.$noaccess.'">'.
				'<a href="'.URL.'&p=57&page_id='.$r['id'].'">'.$r['name'].'</a>'.
			'</div>';
	}

	return $send;
}
function _manualPageCountInPart($part_id) {//если количество страниц в мануале = 1, возврат id этой страницы
	if(defined('MANUAL_PAGE_ONE_IN_PART'))
		return MANUAL_PAGE_ONE_IN_PART;

	$page_id = 0;

	$sql = "SELECT *
			FROM `_manual`
			WHERE `part_id`=".$part_id.
	 (!SA ? " AND `access`" : '');
	$q = query($sql);
	if(mysql_num_rows($q) == 1) {
		$r = mysql_fetch_assoc($q);
		$page_id = $r['id'];
	}

	define('MANUAL_PAGE_ONE_IN_PART', $page_id);
	return $page_id;
}
function _manual_page_info($id) {//отображение страницы мануала
	$sql = "SELECT *
			FROM `_manual`
			WHERE `id`=".$id.
	 (!SA ? " AND `access`" : '');
	if(!$r = query_assoc($sql))
		return _err('Страницы не существует.');

	$_GET['part_id'] = $r['part_id'];

	$content = _manual_page_image($id, $r['content']);
	$content = _manual_page_link_part($content);
	$content = _manual_page_link_page($content);

	return
	(SA && !$r['access'] ? '<div id="no-access">Страница недоступна для просмотра.</div>' : '').
	(SA ?
		'<div id="mp-edit">'.
			'<div class="img_edit manual-page-edit" val="'.$r['id'].'#'.$r['access'].'#'.$r['part_id'].'#'.$r['part_sub_id'].'"></div>'.
			_iconDel(array('dtime_add'=>'','class'=>'manual-page-del') + $r).
			'<input type="hidden" id="mp-edit-name" value="'.$r['name'].'" />'.
			'<textarea id="orig">'.$r['content'].'</textarea>'.
		'</div>'
	: '').

	(!_manualPageCountInPart($r['part_id']) ?
		'<a class="back" href="'.URL.'&p=57&part_id='.$r['part_id'].'"><< назад к разделу <b>'._manualPart($r['part_id']).'</b></a>'
	: '').

	'<div id="head">'.
		(SA ? '<span>{page'.$id.'}</span>' : '').
		$r['name'].
	'</div>'.
	'<h2>'._br($content).'</h2>'.
	'<div id="created">'.
		'Страница создана '.FullDataTime($r['dtime_add']).'.'.
		($r['count_upd'] ?
			'<br />Всего был'._end($r['count_upd'], 'а', 'о').' '.$r['count_upd'].' редакци'._end($r['count_upd'], 'я', 'и', 'й').', '.
			'последняя '.FullDataTime($r['dtime_upd']).'.'
		: '').
	'</div>'.
	_manual_page_bottom($id).
	(SA ?
		'<div id="action">'._manual_action_spisok(array('manual_id'=>$id)).'</div>'
	: '');
}
function _manual_page_image($id, $content) {//вставка изображений в страницу мануала {img5432}
	$sql = "SELECT *
			FROM `_image`
			WHERE !`deleted`
			  AND `unit_name`='manual'
			  AND `unit_id`=".$id."
			ORDER BY `id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		$s = _imageResize($r['big_x'], $r['big_y'], 426, 400);
		$content = str_replace(
							'{img'.$r['id'].'}',
							'<div class="image"><img class="_iview" val="'.$r['id'].'" width="'.$s['x'].'" height="'.$s['y'].'" src="'.$r['path'].$r['big_name'].'" /></div>',
							$content);
	}

	return $content;
}
function _manual_page_link_part($content) {//вставка ссылок на разделы мануала {part1}
	$sql = "SELECT `id`,`name` FROM `_manual_part`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$content = str_replace(
					'{part'.$r['id'].'}',
					'<a href="'.URL.'&p=57&part_id='.$r['id'].'">'.$r['name'].'</a>',
					$content);
	return $content;
}
function _manual_page_link_page($content) {//вставка ссылок на страницы мануала {page15}
	$sql = "SELECT `id`,`name` FROM `_manual`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$content = str_replace(
					'{page'.$r['id'].'}',
					'<a href="'.URL.'&p=57&page_id='.$r['id'].'">'.$r['name'].'</a>',
					$content);
	return $content;
}
function _manual_page_bottom($id) {//отображение кнопок для ответа, либо заметок
	return
	'<div id="page-bottom" class="'._manual_answer($id).'">'.
		'<div id="page-but">'.
			'<button class="vk" val="'.$id.'#3">Все понятно</button>'.
			'<button class="vk red" val="'.$id.'#4">Мне не понятно</button>'.
			'<button class="vk grey" val="'.$id.'#5">Не интересно</button>'.
		'</div>'.
		_note(array(
			'p' => 'manual_page',
			'id' => $id,
			'noapp' => 1
		)).
	'</div>';
}
function _manual_answer($id) {
	if(SA)
		return 'answered';

	$sql = "SELECT *
			FROM `_manual_answer`
			WHERE `manual_id`=".$id."
			  AND `viewer_id`=".VIEWER_ID."
			LIMIT 1";
	if(!$r = query_assoc($sql)) {
		_manual_answer_insert($id);
		return '';
	}

	return $r['val'] > 2 ? 'answered' : '';
}
function _manual_answer_insert($manual_id, $val=1) {
	if(SA)
		return;

	$sql = "SELECT `id`
			FROM `_manual_answer`
			WHERE `manual_id`=".$manual_id."
			  AND `viewer_id`=".VIEWER_ID."
			LIMIT 1";
	$id = _num(query_value($sql));

	$sql = "INSERT INTO `_manual_answer` (
				`id`,
				`manual_id`,
				`viewer_id`,
				`val`
			) VALUES (
				".$id.",
				".$manual_id.",
				".VIEWER_ID.",
				".$val."
			) ON DUPLICATE KEY UPDATE
				`val`=VALUES(`val`)";
	query($sql);
}

function _manual_new() {
	return
	'<div id="manual-new">'.
		'<div id="spisok">'._manual_new_spisok().'</div>'.
	'</div>';
}
function _manual_new_spisok() {
	$sql = "SELECT *
			FROM `_manual`
			WHERE `part_id`=1".
	 (!SA ? " AND `access`" : '')."
			ORDER BY `id` DESC";
	if(!$spisok = query_arr($sql))
		return 'Новостей нет.';

	$end = end($spisok);
	$send = '';
	foreach($spisok as $r) {
		$noborder = $r['id'] == $end['id'] ? ' noborder' : '';
		$noaccess = $r['access'] ? '' : ' noaccess';
		$send .=
			'<div class="unit'.$noborder.$noaccess.'">'.
				'<a href="'.URL.'&p=57&page_id='.$r['id'].'">'.$r['name'].'</a>'.
				'<div class="dtime">'.FullDataTime($r['dtime_add']).'</div>'.
			'</div>';
	}

	return $send;

}

function _menuInfoTop() {//информационное сообщение сверху страницы
	if(SA)
		return '';

	$sql = "SELECT *
			FROM `_manual`
			WHERE `part_id`=1
			  AND `access`
			ORDER BY `id` DESC
			LIMIT 1";
	if(!$r = query_assoc($sql))
		return '';

	$sql = "SELECT COUNT(*)
			FROM `_manual_answer`
			WHERE `manual_id`=".$r['id']."
			  AND `viewer_id`=".VIEWER_ID;
	if(query_value($sql))
		return '';

	return
	'<div id="_info-top">'.
		'<div class="img_del" val="'.$r['id'].'"></div>'.
		'<a href="'.URL.'&p=57&page_id='.$r['id'].'">'.$r['name'].'</a>'.
	'</div>';
}

function _manualAction($id, $i='name') {
	$name = array(
		1 => 'страница просмотрена',
        2 => 'объявление закрыто',
        3 => 'Всё понятно',
        4 => 'Мне не понятно',
        5 => 'Не интересно'
	);

	$bg = array(
		1 => 'dfd',
        2 => 'ffd',
        3 => 'e9e9ff',
        4 => 'fdd',
        5 => 'eee'
	);

	if($i == 'bg')
		return ' style="background:#'.$bg[$id].'"';

	return $name[$id];
}
function _manual_action() {//действия пользователей в отношении страниц
	if(!SA)
		return _manual_main();
	return
	'<div id="manual-action">'.
		'<div id="spisok">'._manual_action_spisok().'</div>'.
	'</div>';
}
function _manual_action_filter($v) {
	return array(
		'manual_id' => _num(@$v['manual_id'])
	);
}
function _manual_action_spisok($v=array()) {
	$filter = _manual_action_filter($v);

	$cond = "`id`";
	if($filter['manual_id'])
		$cond .= " AND `manual_id`=".$filter['manual_id'];

	$sql = "SELECT *
			FROM `_manual_answer`
			WHERE ".$cond."
			ORDER BY `dtime_add` DESC
			LIMIT 100";
	if(!$spisok = query_arr($sql))
		return 'Событий нет.';

	$sql = "SELECT `id`,`name` FROM `_manual`";
	$page = query_ass($sql);

	$send = '<table class="_spisok">'.
				'<tr>'.
			(!$filter['manual_id'] ?
					'<th>Страница' :
			'').
					'<th>Сотрудник'.
					'<th>Действие'.
					'<th>Дата';
	foreach($spisok as $r) {
		$send .=
				'<tr>'.
			(!$filter['manual_id'] ?
					'<td><a href="'.URL.'&p=57&page_id='.$r['manual_id'].'">'.$page[$r['manual_id']].'</a>'
			: '').
					'<td class="user">'._viewer($r['viewer_id'], 'viewer_link').
					'<td class="act"'._manualAction($r['val'], 'bg').'>'._manualAction($r['val']).
					'<td class="dtime">'.FullDataTime($r['dtime_add']);
	}
	$send .= '</table>';
	return $send;
}


