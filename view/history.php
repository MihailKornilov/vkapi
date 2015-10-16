<?php
function _history($v=array()) {
	if(isset($v['type_id']))
		return _history_insert($v);

	return _history_spisok($v);
}//_history()

function _history_insert($v=array()) {//внесение истории действий
	$sql = "INSERT INTO `_history` (
				`app_id`,
				`ws_id`,

				`type_id`,

				`client_id`,
				`zayav_id`,
				`zp_id`,
				`tovar_id`,
				`worker_id`,

				`v1`,
				`v2`,
				`v3`,
				`v4`,

				`viewer_id_add`
			) VALUES (
				".APP_ID.",
				".WS_ID.",

				".$v['type_id'].",

				"._num(@$v['client_id']).",
				"._num(@$v['zayav_id']).",
				"._num(@$v['zp_id']).",
				"._num(@$v['tovar_id']).",
				"._num(@$v['worker_id']).",

				'".addslashes(@$v['v1'])."',
				'".addslashes(@$v['v2'])."',
				'".addslashes(@$v['v3'])."',
				'".addslashes(@$v['v4'])."',

				".(_num(@$v['viewer_id']) ? $v['viewer_id'] : VIEWER_ID)."
			)";
	query($sql, GLOBAL_MYSQL_CONNECT);
	return true;
}//_history_insert()
function _historyFilter($v) {
	return array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 30
	);
}//_historyFilter()
function _history_spisok($v=array()) {
	$filter = _historyFilter($v);

	$spisok = '';
	if($filter['page'] == 1)
		$spisok =
			'<script type="text/javascript">'.
				'var HIST={'.
						''.
					'};'.
			'</script>';

	$cond = "`app_id`=".APP_ID.
	  // " AND `type_id` IN (2,3,4)".//todo удалить
	   " AND `ws_id`=".WS_ID;

	$sql = "SELECT COUNT(`id`) `all` FROM `_history` WHERE ".$cond;
	$all = query_value($sql, GLOBAL_MYSQL_CONNECT);
	if(!$all)
		return array(
			'all' => 0,
			'result' => 'Истории по указанным условиям нет',
			'spisok' => $spisok.'<div class="_empty">Истории по указанным условиям нет</div>',
			'filter' => $filter
		);

	$send = array(
		'all' => $all,
		'result' => 'Показан'._end($all, 'а ', 'о ').$all.' запис'._end($all, 'ь', 'и', 'ей'),
		'spisok' => $spisok,
		'filter' => $filter
	);

	$sql = "SELECT *
			FROM `_history`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT "._start($filter).",".$filter['limit'];
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$history = array();
	while($r = mysql_fetch_assoc($q))
		$history[$r['id']] = $r;

	$history = _viewer($history);
	$history = _clientValToList($history);
	$history = _history_types($history);

	$txt = '';
	end($history);
	$keyEnd = key($history);
	reset($history);
	foreach($history as $r) {
		if(!$txt) {
			$time = strtotime($r['dtime_add']);
			$viewer_id = $r['viewer_id_add'];
		}
		$txt .= '<li>'.(SA ? '<h4>'.$r['type_id'].'</h4>' : '').
					'<div class="li">'.$r['txt'].'</div>';
		$key = key($history);
		if(!$key ||
			$key == $keyEnd ||
			$time - strtotime($history[$key]['dtime_add']) > 900 ||
			$viewer_id != $history[$key]['viewer_id_add']) {
			$send['spisok'] .=
				'<div class="_hist-un">'.
					'<table><tr>'.
				  ($viewer_id ? '<td class="hist-img">'.$r['photo'] : '').
								'<td>'.
					  ($viewer_id ? '<h5>'.$r['viewer_name'].'</h5>' : '').
									'<h6>'.FullDataTime($r['dtime_add']).(!$viewer_id ? '<span>cron</span>' : '').'</h6>'.
					'</table>'.
					'<ul>'.$txt.'</ul>'.
				'</div>';
			$txt = '';
		}
		next($history);
	}

	$send['spisok'] .= _next($filter + array(
			'all' => $all,
			'id' => '_hist-next'
		));
/*
	if($start + $limit < $all) {
		$c = $all - $start - $limit;
		$c = $c > $limit ? $limit : $c;
		$send['spisok'] .=
			'<div class="_next" id="_hist-next" val="'.($page + 1).'">'.
				'<span>Показать ещё '.$c.' запис'._end($c, 'ь', 'и', 'ей').'</span>'.
			'</div>';
	}
*/
	return $send;
}//_history_spisok()
function _history_types($history) {//перевод type_id в текст
	$types = array();
	foreach($history as $r)
		$types[$r['type_id']] = $r['type_id'];

	$sql = "SELECT * FROM `_history_type` WHERE `id` IN (".implode(',', $types).")";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$types[$r['id']] = $r['txt'];

	$str = array(
		'client_link',
		'client_name',
		'v1',
		'v2',
		'v3',
		'v4'
	);

	foreach($history as $id => $r) {
		$txt = $types[$r['type_id']];
		foreach($str as $v)
			if(strpos($txt, '{'.$v.'}'))
				$txt = str_replace('{'.$v.'}', $r[$v], $txt);
		$history[$id]['txt'] = $txt;
	}

	return $history;
}//_history_types()


function _historyChange($name, $old, $new) {//возвращается элемент таблицы, если было изменение при редактировании данных
	if($old != $new)
		return '<tr><th>'.$name.':<td>'.$old.'<td>»<td>'.$new;
	return '';
}//_historyChange()



function sa_history() {//управление историей действий
	return
		sa_path('История действий').
		'<div id="sa-history">'.
			'<div class="headName">Константы истории действий<a class="add">Добавить</a></div>'.
			'<div id="spisok">'.sa_history_spisok().'</div>'.
		'</div>';
}//sa_history()
function sa_history_spisok() {
	$sql = "SELECT * FROM `_history_type` ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	if(!mysql_num_rows($q))
		return 'Список пуст.';

	$spisok = array();
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']] = $r;

	$sql = "SELECT
				`type_id`,
				COUNT(`id`) `count`
			FROM `_history`
			WHERE `type_id`
			  AND `app_id`=".APP_ID."
			GROUP BY `type_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		if(isset($spisok[$r['type_id']]))
			$spisok[$r['type_id']]['count'] = $r['count'];

	$send =
		'<table class="_spisok">'.
			'<tr><th>type_id'.
				'<th>Наименование'.
				'<th>Кол-во'.
				'<th>';
	foreach($spisok as $r)
		$send .=
			'<tr><td class="type_id">'.$r['id'].
				'<td class="txt"><textarea readonly id="txt'.$r['id'].'">'.$r['txt'].'</textarea>'.
				'<td class="count">'.(empty($r['count']) ? '' : $r['count']).
				'<td class="set">'.
					'<div class="img_edit" val="'.$r['id'].'"></div>'.
					'<div class="img_del"></div>';
	$send .= '</table>';
	return $send;
}//sa_history_spisok()
