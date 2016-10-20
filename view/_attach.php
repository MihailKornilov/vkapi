<?php
function _attachValToList($arr, $key='attach_id') {//вставка ссылок на файлы в массив по attach_id
	if(empty($arr))
		return array();

	foreach($arr as $r)
		$arr[$r['id']] += array(
			'attach_name' => '',
			'attach_link' => ''
		);


	if(!$attach_ids = _idsGet($arr, $key))
		return $arr;

	$sql = "SELECT *
			FROM `_attach`
			WHERE `app_id`=".APP_ID."
			  AND `id` IN (".$attach_ids.")";
	if(!$attach = query_arr($sql))
		return $arr;

	foreach($arr as $id => $r) {
		if(!_num(@$r[$key]))
			continue;

		$att = $attach[$r[$key]];

		$arr[$id]['attach_name'] = $att['name'];
		$arr[$id]['attach_link'] = _attachLink($att);
	}

	return $arr;
}
function _attachLink($r) {//формирование ссылки на скачивание файла
	return
	'<div class="_attach-link">'.
		'<a href="'.$r['link'].'" val="'.$r['id'].'" target="_blank">'.$r['name'].'</a>'.
		'<span class="'._tooltip(_sumSpace($r['size']), -7, 'l')._fileSize($r['size']).'</span>'.
		(!file_exists(GLOBAL_PATH.'/..'.$r['link']) ? '<tt class="'._tooltip('Файл отсутствует на сервере', -6, 'l').'del</tt>' : '').
	'</div>';
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
	$attach = query_arr($sql);

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
	if($r = query_assoc($sql))
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
	$r = query_assoc($sql);
	if(!$all = $r['all'])
		return array(
			'all' => 0,
			'result' => '',
			'spisok' => $filter['js'].'<div class="_empty">Файлов не найдено.</div>',
			'filter' => $filter
		);

	$all = $r['all'];
	$send['all'] = $all;
	$send['result'] = 'Показан'._end($all, ' ', 'о ').$all.' файл'._end($all, '', 'а', 'ов').' общим размером '._fileSize($r['size']);
	$send['filter'] = $filter;
	$send['spisok'] = $filter['js'];

	$sql = "SELECT
				*,
				0 `zayav0`,
				0 `zayav1`,
				0 `ze`,
				0 `expense_id`
			FROM `_attach`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT "._startLimit($filter);
	$spisok = query_arr($sql);
	$spisok = _zayavValToList($spisok);

	$attach_ids = _idsGet($spisok);

	//заявка attach_id
	$sql = "SELECT *
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `attach_id` IN (".$attach_ids.")";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['attach_id']]['zayav0'] = $r['id'];

	//заявка attach1_id
	$sql = "SELECT *
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `attach1_id` IN (".$attach_ids.")";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['attach1_id']]['zayav1'] = $r['id'];

	//расход по заявке
	$sql = "SELECT *
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `attach_id` IN (".$attach_ids.")";
	$q = query($sql);
	$ze = array();
	while($r = mysql_fetch_assoc($q)) {
		$spisok[$r['attach_id']]['ze'] = 1;
		$ze[$r['attach_id']] = array(
			'id' => $r['attach_id'],
			'zayav_id' => $r['zayav_id']
		);
	}
	$ze = _zayavValToList($ze);

	//расход организации
	$sql = "SELECT *
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `attach_id` IN (".$attach_ids.")";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['attach_id']]['expense_id'] = $r['id'];
	$spisok = _expenseValToList($spisok);

	$zpu = _zayavPole(0);

	$send['spisok'] .= $filter['page'] != 1 ? '' :
		'<table class="_spisok">'.
			'<tr>'.
				'<th>Название'.
				'<th>Дата<br />загрузки';

	foreach($spisok as $r) {
		$send['spisok'] .=
			'<tr class="l">'.
				'<td>'._attachLink($r).
					($r['zayav0'] ? '<div class="about">Заявка '.$r['zayav_link_name'].': '.@$zpu[22]['name'].'.</div>' : '').
					($r['zayav1'] ? '<div class="about">Заявка '.$r['zayav_link_name'].': '.@$zpu[34]['name'].'.</div>' : '').
					($r['ze'] ? '<div class="about">Расход по заявке '.$ze[$r['id']]['zayav_link_name'].'.</div>' : '').
					($r['expense_id'] ? '<div class="about">Расход организации на сумму <b>'.$r['expense_sum'].'</b> руб.</div>' : '').
				'<td class="dtime">'._dtimeAdd($r);
	}

	$send['spisok'] .= _next($filter + array(
		'all' => $all,
		'tr' => 1
	));

	return $send;
}



