<?php
/*

--- История (процесс) разработки приложения ---
Начало: 2016-09-03

*/

function _devstory_script() {//подключаемые скрипты
	if(PIN_ENTER)
		return '';

	return
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/devstory/devstory'.MIN.'.css?'.VERSION.'" />'.
		'<script src="'.API_HTML.'/modul/devstory/devstory'.MIN.'.js?'.VERSION.'"></script>';
}
function _devstory_footer() {//текст в нижней части приложения
	if(!SA)
		return '';

	if(APP_ID == 2881875 && !SA)
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
		'<a href="'.URL.'&p=devstory" class="dev-page'._tooltip('Процесс разработки приложения', -158, 'r').'разработка</a>'
	: '').
	'</div>';
}

function _devstoryPart($id=false, $i='name') {
	$key = CACHE_PREFIX.'devstory_part';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT *
				FROM `_devstory_part`
				ORDER BY `sort`";
		if($arr = query_arr($sql))
			xcache_set($key, $arr, 86400);
	}

	if($id == 'all')
		return $arr;

	//список JS для select
	if($id == 'js') {
		$spisok = array();
		foreach($arr as $r)
			$spisok[$r['id']] = $r['name'];

		return _assJson($spisok);
	}

	if(!isset($arr[$id]))
		return _cacheErr('неизвестный id раздела разработки', $id);

	if(!isset($arr[$id][$i]))
		return _cacheErr('неизвестный ключ раздела разработки', $i);

	return $arr[$id][$i];
}
function _devstoryKeyword($id=false, $i='name') {
	$key = CACHE_PREFIX.'devstory_keyword';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT *
				FROM `_devstory_keyword`
				ORDER BY `id`";
		if($arr = query_arr($sql))
			xcache_set($key, $arr, 86400);
	}

	if($id == 'all')
		return $arr;

	//список JS для select
	if($id == 'js') {
		$spisok = array();
		foreach($arr as $r)
			$spisok[$r['id']] = $r['name'];

		return _assJson($spisok);
	}

	//слова в списке задач
	if($id = 'task') {
		if(!$i)
			return '';
		$send = '';
		foreach(explode(',', $i) as $r)
			if($r)
				$send .= '<span class="word">'.$arr[$r]['name'].'</span>';
		return $send;
	}

	if(!isset($arr[$id]))
		return _cacheErr('неизвестный id ключевого слова', $id);

	if(!isset($arr[$id][$i]))
		return _cacheErr('неизвестный ключ ключевого слова', $i);

	return $arr[$id][$i];
}
function _devstoryStatus($id) {//названия статусов
	$arr = array(
		0 => 'ожидание',
		1 => 'в процессе',
		2 => 'пауза',
		3 => 'выполнено',
		4 => 'отменено'
	);
	return $arr[$id];
}

function _devstory() {//основная страница
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
function _devstoryMenu() {//разделы основного меню
	$menu = array(
		'main' => 'Разработка - главная',
		'task' => 'Задачи',
		'offer' => 'Предложения',
		'about' => 'О разделе'
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
			'Основные разделы'.
			(SA ? '<a class="add" onclick="devStoryPartEdit()">Новый раздел</a>' : '').
		'</div>'.
		'<div id="part-spisok">'._devstory_part_spisok().'</div>';
}
function _devstory_part_spisok() {//список разделов
	$sql = "SELECT *
			FROM `_devstory_part`
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return 'Список пуст.';

	$send = '<dl class="'.(SA ? '_sort' : '').'" val="_devstory_part">';
	foreach($spisok as $r)
		$send .=
			'<dd val="'.$r['id'].'">'.
				'<table class="part-u w100p">'.
					'<tr><td class="'.(SA ? 'curM' : '').'">'.
							'<a class="name">'.$r['name'].'</a>'.
					(SA ?
						'<td class="ed">'.
							'<div onclick="devStoryTaskEdit('.$r['id'].')" class="img_add m30'._tooltip('Добавить задачу', -94, 'r').'</div>'.
							_iconEdit($r)
					: '').
				'</table>';
	$send .= '</dl>';
	return $send;
}



function _devstory_task() {
	return
		'<div class="headName m1">Список задач</div>'.
		'<div id="spisok">'._devstory_task_spisok().'</div>';
}
function _devstory_task_spisok() {
	$sql = "SELECT
				*,
				0 `keyword`,
				0 `day`,
				'' `period`
			FROM `_devstory_task`
			WHERE !`deleted`
			ORDER BY `dtime_add` DESC";
	if(!$spisok = query_arr($sql))
		return 'Задач не найдено.';

	//id ключевых слов
	$sql = "SELECT *
			FROM `_devstory_keyword_use`
			WHERE `task_id` IN ("._idsGet($spisok).")
			ORDER BY `id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['task_id']]['keyword'] .= ','.$r['keyword_id'];

	//есть ли выполняющаяся задача. Если есть, то невозможно запускать другие задачи на выполнение
	$sql = "SELECT COUNT(*)
			FROM `_devstory_task`
			WHERE `status_id`=1";
	$started = query_value($sql);

	//количество дней, потраченное на выполнение задачи
	$sql = "SELECT
				`task_id`,
				COUNT(*) `day`
			FROM (
				SELECT
					`task_id`
				FROM `_devstory_time`
				WHERE `task_id` IN ("._idsGet($spisok).")
				GROUP BY `task_id`,SUBSTRING(`time_start`,1,10)
			) `dt`
			GROUP BY `task_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['task_id']]['day'] = $r['day'];

	//список периодов выполнения по дням (для графического представления)
	$sql = "SELECT *
			FROM `_devstory_time`
			WHERE `task_id` IN ("._idsGet($spisok).")
			ORDER BY `id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		$start = explode(' ', $r['time_start']);
		if(empty($spisok[$r['task_id']]['period'][$start[0]]))
			$spisok[$r['task_id']]['period'][$start[0]] = array();

		$end = explode(' ', $r['time_end']);

		$hour = floor($r['spent'] / 60);
		$min = $r['spent'] - $hour * 60;
		$min = $min < 10 ? '0'.$min : $min;
		$hour = $hour ? $hour.' ч. ' : '';
		$msg = $hour.$min.' мин.<br />'.substr($start[1], 0, 5).' - '.substr($end[1], 0, 5);


		if($start[0] != $end[0]) {
			$spisok[$r['task_id']]['period'][$start[0]][] = array(
				'start' => $start[1],
				'end' => '23:59:59',
				'msg' => $msg
			);
			$spisok[$r['task_id']]['period'][$end[0]][] = array(
				'start' => '00:00:00',
				'end' => $end[1],
				'msg' => $msg
			);
		} else
			$spisok[$r['task_id']]['period'][$start[0]][] = array(
				'start' => $start[1],
				'end' => $end[1],
				'msg' => $msg
			);
	}

	$send = '';
	foreach($spisok as $r) {
		$send .=
			'<div val="'.$r['id'].'" class="task-u status'.$r['status_id'].'">'.
				'<div class="pp">'.
			  (SA ? '<div onclick="devStoryTaskEdit(0,'.$r['id'].')" class="img_edit'._tooltip('Редактировать задачу', -125, 'r').'</div>' : '').
					'<div class="dtime">'.FullDataTime($r['dtime_add'], 1).'</div>'.
					'<b>'._devstoryPart($r['part_id']).'</b>'.
					_devstoryKeyword('task', $r['keyword']).
				'</div>'.
				'<table class="w100p">'.
					'<tr><td class="top">'.
							'<div class="name">'.$r['name'].'</div>'.
							'<div class="about">'._br($r['about']).'</div>'.
						'<td class="td-status top w150">'.
							'<div class="st center">'._devstoryStatus($r['status_id']).'</div>'.

							_devstory_task_spent($r).

	(SA && $r['status_id'] == 0 && !$started ?
								'<a class="st-action start">начать выполнение</a>'
	: '').

	(SA && $r['status_id'] == 1 ? '<a class="st-action pause">приостановить</a>' : '').
	(SA && $r['status_id'] == 1 ? '<a class="st-action ready">выполнено</a>' : '').

	(SA && $r['status_id'] == 2 && !$started ? '<a class="st-action next">продолжить</a>' : '').

	(SA && $r['status_id'] == 1 ? '<a class="st-action cancel red">отменить</a>' : '').
	(SA && $r['status_id'] == 2 ? '<a class="st-action cancel red">отменить</a>' : '').

				'</table>'.
			'</div>';
	}

	return $send;
}
function _devstory_task_spent($r) {//отображение затраченного времени для каждой задачи
	if(!$r['spent'])
		return '';

	$hour = floor($r['spent'] / 60);
	$min = $r['spent'] - $hour * 60;
	$min = $min < 10 ? '0'.$min : $min;
	$hour = $hour ? '<b>'.$hour.'</b> ч. ' : '';

	$period = '';
	if(!empty($r['period']) && $r['status_id'] != 4) {
		$period = '<div class="period">'.
					'<table class="bs5">';

		$period .= '<tr><td><td>'.
					'<table class="period-hour"><tr>';
		for($n = 0; $n < 24; $n++)
			$period .= '<td>'.$n;

		$period .= '<tr>';
		for($n = 0; $n < 24; $n++)
			$period .= '<td class="line'.(!$n ? ' first' : '').'">';

		$period .= '</table>';

		foreach($r['period'] as $day => $i) {
			$period .= '<tr><td class="label r">'.FullData($day, 1, 1).
						   '<td>';

			$full = 480;
			foreach($i as $key => $k) {
				//пустота в начале
				if(!$key && $k['start'] != '00:00:00') {
					$ex = explode(':', $k['start']);
					$w = round(($ex[0] * 60 + intval($ex[1])) / 3);
					$period .= '<div class="graf" style="width:'.$w.'px"></div>';
					$full -= $w;
				}

				//активное действие
				$ex = explode(':', $k['start']);
				$start = round(($ex[0] * 60 + intval($ex[1])) / 3);
				$ex = explode(':', $k['end']);
				$end = round(($ex[0] * 60 + intval($ex[1])) / 3);
				$w = $end - $start;
				$period .= '<div style="width:'.$w.'px" class="graf active'._tooltip($k['msg'], -76, 'r', 1).'</div>';
				$full -= $w;

				//пустота в центре
				if(isset($i[$key + 1])) {
					$ex = explode(':', $k['end']);
					$start = round(($ex[0] * 60 + intval($ex[1])) / 3);
					$ex = explode(':', $i[$key + 1]['start']);
					$end = round(($ex[0] * 60 + intval($ex[1])) / 3);
					$w = $end - $start;
					$period .= '<div class="graf" style="width:'.$w.'px"></div>';
					$full -= $w;
				}

				//пустота в конце
				if($key == count($i) - 1 && $k['end'] != '23:59:59')
					$period .= '<div class="graf" style="width:'.$full.'px"></div>';
			}
		}

		$period .= '</table></div>';
	}

	return
	'<div class="spent">'.
		$period.
		'<table class="bs5 curP head">'.
			'<tr><td class="label r">Затрачено'.
				'<td>'.$hour.'<b>'.$min.'</b> мин.'.
			'<tr><td class="label r">в течение'.
				'<td><b>'.$r['day'].'</b> '._end($r['day'], 'дня', 'дней').'.'.
		'</table>'.
	'</div>';
}



function _devstory_offer() {//предложение о новых возможностях приложения
	return
		'Предложения';
}


function _devstory_about() {//описание модуля процесса разработки
	return
		'about';
}



