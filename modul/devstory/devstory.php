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
	if(!$id)
		return '';

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

	//массив для select через Ajax 
	if($id == 'array') {
		$spisok = array();
		foreach($arr as $r)
			$spisok[$r['id']] = $r['name'];

		return _selArray($spisok);
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
function _devstoryStatus($id, $type='name') {//названия статусов
	$name = array(
		0 => 'ожидание',
		1 => 'в процессе',
		2 => 'на паузе',
		3 => 'выполнено',
		4 => 'отменено'
	);

	$bg = array(
		0 => 'eee',
		1 => 'ccf',
		2 => 'f2f2aa',
		3 => 'beb',
		4 => 'd9d9d9'
	);

	if($type == 'bg')
		return $bg[$id];

	return $name[$id];
}

function _devstory() {//основная страница
	switch(@$_GET['d']) {
		default:
		case 'main': $content = _devstory_part(); break;
		case 'task': $content = _devstory_process(); break;
		case 'offer': $content = _devstory_offer(); break;
		case 'about': $content = _devstory_about(); break;
	}
	return
	_devstoryMenu().
	$content.
	'<script>'.
		'var DEVSTORY_PART_SPISOK='._devstoryPart('js').','.
			'DTIME="'.strftime('%Y-%m-%d %H:%M:00').'";'.
	'</script>';
}
function _devstoryMenu() {//разделы основного меню
	$menu = array(
		'main' => 'Разделы',
		'task' => 'Список задач',
		'offer' => 'Предложения',
		'about' => 'О разработке'
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
	if($id = _num(@$_GET['id']))
		return _devstory_part_info($id);

	return
	'<div id="devstory-part">'.
		'<div class="headName m1">'.
			'Основные разделы'.
			(SA ? '<a class="add" onclick="devStoryPartEdit()">Новый раздел</a>' : '').
		'</div>'.
		'<div id="part-spisok">'._devstory_part_spisok().'</div>'.
	'</div>';
}
function _devstory_part_spisok() {//список разделов
	$sql = "SELECT *
			FROM `_devstory_part`
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return 'Список пуст.';

	$sql = "SELECT * FROM `_devstory_keyword`";
	$keyword = query_ass($sql);



	foreach($spisok as $id => $r)
		$spisok[$id]['keyword'] = array();

	$sql = "SELECT * FROM `_devstory_keyword_use`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['part_id']]['keyword'][$r['keyword_id']] = 1;

	foreach($spisok as $id => $r) {
		$kw = array();
		foreach($spisok[$id]['keyword'] as $k => $i)
			$kw[] = $keyword[$k];

		sort($kw);

		foreach($kw as $k => $i)
			$kw[$k] = '<a>'.$i.'</a>';

		$spisok[$id]['keyword'] = implode('<br />', $kw);
	}


	$send = '<dl class="'.(SA ? '_sort' : '').'" val="_devstory_part">';
	foreach($spisok as $r)
		$send .=
			'<dd val="'.$r['id'].'">'.
				'<table class="part-u w100p">'.
					'<tr><td class="'.(SA ? 'curM' : '').'">'.
							'<a href="'.URL.'&p=devstory&d=main&id='.$r['id'].'" class="name">'.$r['name'].'</a>'.
							'<div class="keyword">'.$r['keyword'].'</div>'.
					(SA ?
						'<td class="ed">'.
							'<div onclick="devStoryTaskEdit('.$r['id'].')" class="img_add m30'._tooltip('Добавить задачу', -94, 'r').'</div>'.
							_iconEdit($r)
					: '').
				'</table>';
	$send .= '</dl>';
	return $send;
}
function _devstory_part_info($part_id) {
	$sql = "SELECT *
			FROM `_devstory_part`
			WHERE `id`=".$part_id;
	if(!$part = query_assoc($sql)) {
		$_GET['id'] = 0;
		return _devstory_part();
	}

	return
	'<div id="devstory-part-info">'.
		'<div class="part-name">'.
	 (SA ? '<button class="vk small fr" onclick="devStoryTaskEdit('.$part_id.')">Новая задача</button>' : '').
			$part['name'].
		'</div>'.
		'<div class="mar8">'.
			'<div class="mar8 mb20">'._devstory_process_wait($part_id).'</div>'.
			'<div class="mar8 mb20">'._devstory_process_process($part_id).'</div>'.
			'<div class="mar8 mb20">'._devstory_process_pause($part_id).'</div>'.
			'<div class="mar8">'._devstory_process_ready($part_id).'</div>'.
		'</div>'.
	'</div>';
}


function _devstory_task_info($task_id) {
	$sql = "SELECT
				*,
				0 `keyword`,
				0 `day`,
				'' `period`
			FROM `_devstory_task`
			WHERE !`deleted`
			  AND `id`=".$task_id;
	if(!$task = query_assoc($sql))
		return 'Задачи не существует.';

	//id ключевых слов
	$sql = "SELECT *
			FROM `_devstory_keyword_use`
			WHERE `task_id`=".$task_id."
			ORDER BY `id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$task['keyword'] .= ','.$r['keyword_id'];

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
				WHERE `task_id`=".$task_id."
				GROUP BY `task_id`,SUBSTRING(`time_start`,1,10)
			) `dt`
			GROUP BY `task_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$task['day'] = $r['day'];

	//список периодов выполнения по дням (для графического представления)
	$sql = "SELECT *
			FROM `_devstory_time`
			WHERE `task_id`=".$task_id."
			ORDER BY `id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		$start = explode(' ', $r['time_start']);
		if(empty($task['period'][$start[0]]))
			$task['period'][$start[0]] = array();

		$end = explode(' ', $r['time_end']);

		$hour = floor($r['spent'] / 60);
		$min = $r['spent'] - $hour * 60;
		$min = $min < 10 ? '0'.$min : $min;
		$hour = $hour ? $hour.' ч. ' : '';
		$msg = $hour.$min.' мин.<br />'.substr($start[1], 0, 5).' - '.substr($end[1], 0, 5);


		if($start[0] != $end[0]) {
			$task['period'][$start[0]][] = array(
				'start' => $start[1],
				'end' => '23:59:59',
				'msg' => $msg
			);
			$task['period'][$end[0]][] = array(
				'start' => '00:00:00',
				'end' => $end[1],
				'msg' => $msg
			);
		} else
			$task['period'][$start[0]][] = array(
				'start' => $start[1],
				'end' => $end[1],
				'msg' => $msg
			);
	}

	$r = $task;
	return
	'<div id="devstory-task-info">'.
		'<div class="part-name">'.
			'<a href="'.URL.'&p=devstory&d=main&id='.$r['part_id'].'">'._devstoryPart($r['part_id']).'</a> » Информация о задаче'.
		'</div>'.
		'<div val="'.$r['id'].'" class="task-u status'.$r['status_id'].'">'.
			'<div class="pp">'.
		  (SA ? '<div onclick="devStoryTaskEdit(0,'.$r['id'].')" class="img_edit'._tooltip('Редактировать задачу', -125, 'r').'</div>' : '').
				'<div class="dtime">'.FullDataTime($r['dtime_add'], 1).'</div>'.
				'<a href="'.URL.'&p=devstory&d=main&id='.$r['part_id'].'" class="part_name b">'._devstoryPart($r['part_id']).'</a>'.
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
(SA && $r['status_id'] == 1 ? '<a class="st-action ready"><b>выполнено</b></a>' : '').

(SA && $r['status_id'] == 2 && !$started ? '<a class="st-action next">продолжить</a>' : '').
(SA && $r['status_id'] == 2 ? '<a class="st-action ready from-pause"><b>выполнено</b></a>' : '').

(SA && $r['status_id'] == 1 ? '<a class="st-action cancel red">отменить</a>' : '').
(SA && $r['status_id'] == 2 ? '<a class="st-action cancel red">отменить</a>' : '').

			'</table>'.
		'</div>'.
	'</div>';
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




function _devstory_process() {
	if($task_id = _num(@$_GET['id']))
		return _devstory_task_info($task_id);

	return
	'<div class="mar8">'.
  (SA ? '<button class="vk fr" onclick="devStoryTaskEdit()">Новая задача</button>' : '').
		'<div class="mb20">'._devstory_process_wait().'</div>'.
		'<div class="mb20">'._devstory_process_process().'</div>'.
		'<div class="mb20">'._devstory_process_pause().'</div>'.
		'<div class="mb20">'._devstory_process_ready().'</div>'.
	'</div>';
}
function _devstory_process_ready($part_id=0) {//список выполненных задач

	$sql = "SELECT
				*
			FROM `_devstory_task`
			WHERE `status_id`=3
			  AND !`deleted`
".($part_id ? "AND `part_id`=".$part_id : '')."  
			ORDER BY `dtime_end` DESC";
	if(!$task = query_arr($sql))
		return '';

	$send = '<div class="devstory-head-ready">Выполнено</div>';

	$curMon = '';
	foreach($task as $r) {
		$time = strtotime($r['dtime_end']);
		$mon = strftime('%Y %m', $time);
		if($curMon != $mon) {
			$y = strftime('%Y', $time);
			$m = _monthDef(strftime('%m', $time), 1);
			$send .= '<div class="devstory-task-mon'.($curMon ? ' mt20' : '').'">'.$m.' '.$y.':</div>';
			$curMon = $mon;
		}
		$send .= _devstory_task_unit($r, $part_id);
	}

	return $send;
}
function _devstory_process_pause($part_id=0) {//список задач на паузе
	$sql = "SELECT
				*
			FROM `_devstory_task`
			WHERE `status_id`=2
			  AND !`deleted`
".($part_id ? "AND `part_id`=".$part_id : '')."  
			ORDER BY `dtime_add` DESC";
	if(!$task = query_arr($sql))
		return '';

	$send = '<div class="devstory-head-pause">На паузе</div>';

	foreach($task as $r)
		$send .= _devstory_task_unit($r, $part_id);

	return $send;
}
function _devstory_process_wait($part_id=0) {//список задач, ожидающих выполнения
	$sql = "SELECT
				*
			FROM `_devstory_task`
			WHERE !`status_id`
			  AND !`deleted`
".($part_id ? "AND `part_id`=".$part_id : '')."  
			ORDER BY `part_id`,`dtime_add` DESC";
	if(!$task = query_arr($sql))
		return '';

	$send =
		'<div class="devstory-wait-head curP" onclick="$(this).next().slideToggle()">'.
			'<u>Ожидающие выполнения</u> '.
			'('.count($task).')'.
		'</div>';

	$send .= '<div class="devstory-wait-content'.($part_id ? '' : ' dn').'">';
	$part_id_cur = 0;
	foreach($task as $r) {
		if(!$part_id && $part_id_cur != $r['part_id']) {
			$send .= '<a href="'.URL.'&p=devstory&d=main&id='.$r['part_id'].'" class="devstory-wait-pname fs13">'._devstoryPart($r['part_id']).'</a>';
			$part_id_cur = $r['part_id'];
		}
		$send .= _devstory_task_unit($r, $part_id);
	}
	$send .= '</div>';

	return $send;
}
function _devstory_process_process($part_id=0) {//список задач в процессе
	$sql = "SELECT
				*
			FROM `_devstory_task`
			WHERE `status_id`=1
			  AND !`deleted`
".($part_id ? "AND `part_id`=".$part_id : '')."  
			ORDER BY `dtime_add` DESC";
	if(!$task = query_arr($sql))
		return '';

	$send = '<div class="devstory-head-process">В процессе</div>';

	foreach($task as $r)
		$send .= _devstory_task_unit($r, $part_id);

	return $send;
}
function _devstory_task_unit($r, $part_id) {//единица списка задач
	return
	'<div class="devstory-task-unit">'.
		'<div style="background:#'._devstoryStatus($r['status_id'], 'bg').'" class="stat'._tooltip('Статус: '._devstoryStatus($r['status_id']), -8, 'l').'</div>'.
		($r['status_id'] == 3 ? '<span class="dtime-end grey">'.FullData($r['dtime_end'], 1, 1).':</span> ' : '').
		'<a class="name" href="'.URL.'&p=devstory&d=task&id='.$r['id'].'">'.$r['name'].'</a>'.
		(!$part_id && $r['status_id'] ?
			'<span class="part-name">(<a href="'.URL.'&p=devstory&d=main&id='.$r['part_id'].'">'._devstoryPart($r['part_id']).'</a>)</span>'
		: '').
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



