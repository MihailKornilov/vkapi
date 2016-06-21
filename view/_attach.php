<?php
function _attachValToList($arr, $keyName='attach_id') {//вставка ссылок на файлы в массив по attach_id
	$ids = array();
	$arrIds = array();
	foreach($arr as $key => $r)
		if(!empty($r[$keyName])) {
			$ids[$r[$keyName]] = 1;
			$arrIds[$r[$keyName]][] = $key;
		}
	if(empty($ids))
		return $arr;

	$sql = "SELECT *
			FROM `_attach`
			WHERE `app_id`=".APP_ID."
			  AND `id` IN (".implode(',', array_keys($ids)).")";
	$attach = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	foreach($attach as $r) {
		foreach($arrIds[$r['id']] as $id) {
			$arr[$id] += array(
				'attach_name' => $r['name'],
				'attach_link' => '<a href="'.$r['link'].'" class="_attach-link" val="'.$r['id'].'" target="_blank">'.$r['name'].'</a>',
			);
		}
	}

	return $arr;
}
function _attachJs($v=array()) {//получение ссылок на файлы в javascript
	$v = array(
		'id' => _ids(@$v['id']),
		'array' => _num(@$v['array']), //передача списка массивом через ajax
		'zayav_id' => _num(@$v['zayav_id'])
	);

	$sql = "SELECT *
			FROM `_attach`
			WHERE `app_id`=".APP_ID.
			($v['zayav_id'] ? " AND `zayav_id`=".$v['zayav_id'] : '').
			($v['id'] ? " AND `id` IN(".$v['id'].")" : '');
	$attach = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	$send = array();
	$array = array();
	foreach($attach as $r) {
		$send[] =
			$r['id'].':{'.
				'name:"'.addslashes($r['name']).'",'.
				'link:"'.addslashes($r['link']).'",'.
				'size:'.$r['size'].
			'}';
		$array[intval($r['id'])] = array(
			'name' => utf8($r['name']),
			'link' => $r['link'],
			'size' => $r['size']
		);
	}

	if($v['array'])
		return $array;

	return
	'<script>'.
		'var ATTACH={'.implode(',', $send).'};'.
	'</script>';
}
function _attachArr($id) {//получение данных о файле в формате json для ajax
	$sql = "SELECT *
			FROM `_attach`
			WHERE `app_id`=".APP_ID."
			  AND `id`=".$id;
	if($r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		return array(
			'name' => utf8($r['name']),
			'link' => $r['link'],
			'size' => $r['size']
		);

	return array();
}

function _attach_list() {
	$data = _attach_spisok();
	return
	'<div id="attach-list">'.
		$data['result'].
		$data['spisok'].
	'</div>';
}
function _attachFilter($v) {
	$filter = array(
		'limit' => _num(@$v['limit']) ? $v['limit'] : 50,
		'page' => _num(@$v['page']) ? $v['page'] : 1
	);
	return $filter;
}
function _attach_spisok($v=array()) {// список клиентов
	$filter = _attachFilter($v);
	$filter = _filterJs('ATTACH', $filter);

	$cond = "`app_id`=".APP_ID."
		 AND !`deleted`";

	$sql = "SELECT
				COUNT(`id`) `all`,
				SUM(`size`) `size`
			FROM `_attach` WHERE ".$cond;
	$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT);
	if(!$all = $r['all'])
		return array(
			'all' => 0,
			'result' => '',
			'spisok' => $filter['js'].'<div class="_empty">Файлов не найдено.</div>',
			'filter' => $filter
		);

	$all = $r['all'];
	$send['all'] = $all;
	$send['result'] = 'Найден'._end($all, ' ', 'о ').$all.' файл'._end($all, '', 'а', 'ов').' общим размером '._fileSize($r['size']);
	$send['filter'] = $filter;
	$send['spisok'] = $filter['js'];

	$sql = "SELECT *
			FROM `_attach`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT "._startLimit($filter);
	$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	$send['spisok'] .= $filter['page'] != 1 ? '' :
		'<table class="_spisok">'.
			'<tr>'.
				'<th>Описание'.
				'<th>Размер'.
				'<th>Дата<br />загрузки';

	foreach($spisok as $r) {
		$send['spisok'] .=
			'<tr>'.
				'<td>'.$r['name'].
				'<td class="size'._tooltip(_sumSpace($r['size']), 5)._fileSize($r['size']).
				'<td class="dtime">'._dtimeAdd($r);
	}

	$send['spisok'] .= _next($filter + array(
		'all' => $all,
		'tr' => 1
	));

	return $send;
}



