<?php
function _history($v=array()) {
	if(isset($v['type_id']))
		return _history_insert($v);

	return _history_spisok($v);
}

function _history_script() {//скрипты и стили
	if(PIN_ENTER)
		return '';

	return
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/history/history'.MIN.'.css?'.VERSION.'" />'.
		'<script src="'.API_HTML.'/modul/history/history'.MIN.'.js?'.VERSION.'"></script>';
}

function _history_insert($v=array()) {//внесение истории действий
	$app_id = _num(@$v['app_id']) ? _num($v['app_id']) : APP_ID;
	$client_id = _num(@$v['client_id']);
	$zayav_id = _num(@$v['zayav_id']);

	if(!$client_id && $zayav_id) {//если указана заявка, но не указан клиент, обновляется id клиента
		if($r = _zayavQuery($zayav_id, 1))
			$client_id = $r['client_id'];
	}

	$sql = "INSERT INTO `_history` (
				`app_id`,

				`type_id`,

				`client_id`,
				`zayav_id`,
				`ob_id`,
				`dogovor_id`,
				`attach_id`,
				`schet_id`,
				`tovar_id`,
				`worker_id`,
				`invoice_id`,

				`v1`,
				`v2`,
				`v3`,
				`v4`,

				`viewer_id_add`
			) VALUES (
				".$app_id.",

				".$v['type_id'].",

				".$client_id.",
				".$zayav_id.",
				"._num(@$v['ob_id']).",
				"._num(@$v['dogovor_id']).",
				"._num(@$v['attach_id']).",
				"._num(@$v['schet_id']).",
				"._num(@$v['tovar_id']).",
				"._num(@$v['worker_id']).",
				"._num(@$v['invoice_id']).",

				'".addslashes(@$v['v1'])."',
				'".addslashes(@$v['v2'])."',
				'".addslashes(@$v['v3'])."',
				'".addslashes(@$v['v4'])."',

				".(_num(@$v['viewer_id']) ? $v['viewer_id'] : VIEWER_ID)."
			)";
	query($sql);
	return true;
}
function _historyFilter($v) {
	return array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 30,
		'viewer_id_add' => _num(@$v['viewer_id_add']),
		'category_id' => _num(@$v['category_id']),
		'client_id' => _num(@$v['client_id']),
		'zayav_id' => _num(@$v['zayav_id']),
		'attach_id' => _num(@$v['attach_id']),
		'schet_id' => _num(@$v['schet_id'])
	);
}
function _history_spisok($v=array()) {
	$filter = _historyFilter($v);
	$filter = _filterJs('HISTORY', $filter);

	define('PAGE1', $filter['page'] == 1);
	define('HIST_LOCAL', $filter['category_id'] || $filter['client_id'] || $filter['zayav_id'] || $filter['schet_id']); //история конкретных объектов
	$spisok = $filter['js'];

	$cond = "`app_id`=".APP_ID;
//	   " AND `type_id` IN (128)";//todo удалить

	if($filter['viewer_id_add'])
		$cond .= " AND `viewer_id_add`=".$filter['viewer_id_add'];
	if($filter['category_id']) {
		$sql = "SELECT `type_id` FROM `_history_ids` WHERE `category_id`=".$filter['category_id'];
		$ids = query_ids($sql);
		$cond .= " AND `type_id` IN (".$ids.")";
	}
	if($filter['client_id'])
		$cond .= " AND `client_id`=".$filter['client_id'];
	if($filter['zayav_id'])
		$cond .= " AND `zayav_id`=".$filter['zayav_id'];
	if($filter['schet_id'])
		$cond .= " AND `schet_id`=".$filter['schet_id'];
	if(!HIST_LOCAL && RULE_HISTORY_VIEW == 1)
		$cond .= " AND `viewer_id_add`=".VIEWER_ID;

	$add = HIST_LOCAL ? '' : '<div id="history-add" class="img_add m30'._tooltip('Добавить событие', -60).'</div>';

	$sql = "SELECT COUNT(`id`) FROM `_history` WHERE ".$cond;
	if(!$all = query_value($sql))
		return array(
			'all' => 0,
			'spisok' =>
				$spisok.
				(PAGE1 ? '<div class="result norm">Истории по указанным условиям нет'.$add.'</div>' : '').
				'<div class="_empty mar8">Истории по указанным условиям нет</div>',
			'filter' => $filter
		);

	$send = array(
		'all' => $all,
		'spisok' =>
			(PAGE1 ? '<div class="result norm">Показан'._end($all, 'а ', 'о ')._sumSpace($all).' запис'._end($all, 'ь', 'и', 'ей').$add.'</div>' : '').
			$spisok,
		'filter' => $filter
	);

	$sql = "SELECT *
			FROM `_history`
			WHERE ".$cond."
			ORDER BY `dtime_add` DESC
			LIMIT "._start($filter).",".$filter['limit'];
	$q = query($sql);
	$history = array();
	while($r = mysql_fetch_assoc($q)) {
		if($r['invoice_id'])
			$r['invoice_name'] = _invoice($r['invoice_id']);
		$history[$r['id']] = $r;
	}

	$history = _viewerValToList($history);
	$history = _clientValToList($history);
	$history = _zayavValToList($history);
	$history = _dogovorValToList($history);
	$history = _tovarValToList($history);
	$history = _attachValToList($history);
	$history = _schetValToList($history);
	$history = _schetPayValToList($history);
	$history = _history_types($history, $filter);

	$txt = '';
	end($history);
	$keyEnd = key($history);
	reset($history);
	foreach($history as $r) {
		if(!$txt) {
			$time = strtotime($r['dtime_add']);
			$viewer_id = $r['viewer_id_add'];
		}
		$txt .= '<li'.(SA ? ' class="light"' : '').'>'.
					(SA ? '<h4 val="'.$r['id'].'">'.($r['type_id_old'] ? $r['type_id_old'].'-' : '').$r['type_id'].'</h4>' : '').//todo удалить type_id_old после перенесения
					'<div class="li">'.$r['txt'].'</div>';
		$key = key($history);
		if(!$key ||
			$key == $keyEnd ||
			$time - strtotime($history[$key]['dtime_add']) > 900 ||
			$viewer_id != $history[$key]['viewer_id_add']) {
			$send['spisok'] .=
				'<div class="_hist-un">'.
					'<table><tr>'.
				  ($viewer_id ? '<td class="hist-img">'.$r['viewer_photo'] : '').
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

	$send['spisok'] .= _next($filter + array('all'=>$all));
	return $send;
}
function _history_types($history, $filter) {//перевод type_id в текст
	$txtType = array();
	$sql = "SELECT *
			FROM `_history_type`
			WHERE `id` IN ("._idsGet($history, 'type_id').")";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		$txtType[$r['id']] = $r['txt'];
		if($filter['client_id'] && $r['txt_client'])
			$txtType[$r['id']] = $r['txt_client'];
		if($filter['zayav_id'] && $r['txt_zayav'])
			$txtType[$r['id']] = $r['txt_zayav'];
		if($filter['schet_id'] && $r['txt_schet'])
			$txtType[$r['id']] = $r['txt_schet'];
	}

	$str = array(
		'client_name',
		'client_link',
		'zayav_link',
		'ob_id',
		'dogovor_nomer',
		'dogovor_data',
		'dogovor_sum',
		'dogovor_avans',
		'tovar_link',
		'attach_link',
		'schet_link_full',
		'schet_pay_link',
		'invoice_name',
		'worker_name',
		'worker_link',
		'worker_salary',
		'v1',
		'v2',
		'v3',
		'v4'
	);

	foreach($history as $id => $r) {
		$txt = $txtType[$r['type_id']];
		foreach($str as $v) {
			// проверка условия: ?{v1}, если v1 пустая, то не выводится строка, заключённая между пробелами с этой переменной.
			// например: <em>?{v1}</em>
			if(strpos($txt, '?{'.$v.'}') !== false) {
				$ex = explode(' ', $txt);
				foreach($ex as $i => $e)
					if(strpos($e, '?{'.$v.'}') !== false && empty($r[$v]))
						$ex[$i] = '';
					else $ex[$i] = str_replace('?{', '{', $ex[$i]);
				$txt = implode(' ', $ex);
			}
			if(strpos($txt, '{'.$v.'}') !== false && isset($r[$v]))
				$txt = str_replace('{'.$v.'}', $r[$v], $txt);
			if(strpos($txt, '#'.$v.'#') !== false)
				$txt = str_replace('#'.$v.'#', '<div class="changes">'.$r[$v].'</div>', $txt);
			//статус заявки: название статуса, подсвеченное соответствующим цветом (для mobile)
			if(strpos($txt, '^'.$v.'^') !== false) {
				$status = '';
				if($r[$v])
					$status = '<span class="zstatus"'._zayavStatus($r[$v], 'bg').'>'._zayavStatus($r[$v]).'</span>';
				$txt = str_replace('^'.$v.'^', $status, $txt);
			}
		}
		$history[$id]['txt'] = _br($txt);
	}

	return $history;
}


function _historyChange($name, $old, $new, $v1='', $v2='') {//возвращается элемент таблицы, если было изменение при редактировании данных
	if($old == $new)
		return '';

	if($v1 && $v2) {
		$old = $v1;
		$new = $v2;
	}
	$name = $name ? '<th>'.$name.':' : '';
	return '<tr>'.$name.'<td>'.$old.'<td>»<td>'.$new;
}

function _history_right($v=array()) {//вывод условий поиска для истории действий
	$v = array(
		'client_id' => _num(@$v['client_id'])
	);

	define('VIEWER_ID_ONLY', !$v['client_id'] && RULE_HISTORY_VIEW == 1);

	$worker = '[]';
	//если более 100 сотрудников (пользователей), то селект с сотрудниками не выводится
	$sql = "SELECT COUNT(DISTINCT `viewer_id_add`)
			FROM `_history`
			WHERE `app_id`=".APP_ID;
	if(query_value($sql) < 100) {
		$sql = "SELECT DISTINCT `viewer_id_add`
				FROM `_history`
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id_add`".
				  ($v['client_id'] ? " AND `client_id`=".$v['client_id'] : '');
		$worker = VIEWER_ID_ONLY ? '[]' : query_workerSelJson($sql);
	}


	$sql = "SELECT
	            `cat`.`id`,
				`cat`.`name`
			FROM
			 	`_history_category` `cat`,
				`_history_ids` `ids`,
				`_history` `h`
			WHERE `h`.`app_id`=".APP_ID."
			  AND `cat`.`id`=`ids`.`category_id`
			  AND `h`.`type_id`=`ids`.`type_id`
			  AND `cat`.`js_use`
			  ".($v['client_id'] ? " AND `h`.`client_id`=".$v['client_id'] : '')."
			  ".(VIEWER_ID_ONLY ? " AND `h`.`viewer_id_add`=".VIEWER_ID : '')."
			GROUP BY `cat`.`id`
			ORDER BY `cat`.`sort`";
	$category = query_selJson($sql);
	return
	(!VIEWER_ID_ONLY ?
		'<div class="findHead">Действия сотрудника</div>'.
		'<input type="hidden" id="viewer_id_add" />'
	: '').
		(strlen($category) > 2 ? '<div class="findHead">Категория</div>' : '').
		'<input type="hidden" id="category_id" />'.

		'<script>'.
			'var HIST_WORKER='.$worker.','.
				'HIST_CAT='.$category.';'.
			'_historyRight();'.
		'</script>';
}

