<?php
function _zayav() {
	if(function_exists('zayavCase'))
		return zayavCase();
	if(@$_GET['d'] == 'info')
		return _zayavInfo();
	
	//вывести список заявок, в котором использовался конкретный товар (переход со страницы информации о товаре)
	if($tovar_id = _num(@$_GET['from_tovar_id'])) {
		foreach($_COOKIE as $key => $val)
			if(strpos($key, APP_ID.'_'.VIEWER_ID.'_zayav') !== false)
			unset($_COOKIE[$key]);
		$_COOKIE[APP_ID.'_'.VIEWER_ID.'_zayav'._service('current').'_tovar_id'] = $tovar_id;
	}
	
	return _zayav_list(_hashFilter('zayav'._service('current')));
}

function _zayav_script() {//скрипты для заявок
	if(PIN_ENTER)
		return '';

	return
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/zayav/zayav'.MIN.'.css?'.VERSION.'" />'.
		'<script src="'.API_HTML.'/modul/zayav/zayav'.MIN.'.js?'.VERSION.'"></script>';
}
function _rubric($id='all', $i='name') {//Кеширование рубрик объявлений
	$key = CACHE_PREFIX.'rubric';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					`id`,
					`name`
				FROM `_setup_rubric`
				WHERE `app_id`=".APP_ID."
				ORDER BY `sort`";
		$arr = query_arr($sql);
		xcache_set($key, $arr, 86400);
	}

	//все рубрики
	if($id == 'all')
		return $arr;

	//все рубрики
	if($id == 'ass') {//ассоциативный список id => name
		$send = array();
		foreach($arr as $id => $r)
			$send[$id] = $r['name'];
		return $send;
	}

	//список JS для select
	if($id == 'js') {
		$spisok = array();
		foreach($arr as $r)
			$spisok[$r['id']] = $r['name'];
		return _selJson($spisok);
	}

	//рубрика » подрубрика
	if(preg_match(REGEXP_NUMERIC, $i))
		return $arr[$id]['name'].($i ? ' » '._rubricSub($i) : '');

	//неизвестный id
	if(!isset($arr[$id]))
		return _cacheErr('неизвестный id рубрики', $id);
	
	//неизвестный ключ
	if(!isset($arr[$id][$i]))
		return _cacheErr('неизвестный ключ рубрики', $i);

	//конкретная рубрика
	return $arr[$id][$i];
}
function _rubricSub($id='all', $i='name') {//Кеширование рубрик объявлений
	$key = CACHE_PREFIX.'rubric_sub';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					`id`,
					`rubric_id`,
					`name`
				FROM `_setup_rubric_sub`
				WHERE `app_id`=".APP_ID."
				ORDER BY `sort`";
		$arr = query_arr($sql);
		xcache_set($key, $arr, 86400);
	}

	//все рубрики
	if($id == 'all')
		return $arr;

	//список JS для select
	if($id == 'js') {
		$spisok = array();
		foreach($arr as $r)
			$spisok[$r['rubric_id']][$r['id']] = $r['name'];

		$js = array();
		foreach($spisok as $uid => $r)
			$js[] = $uid.':'._selJson($r);

		return '{'.implode(',', $js).'}';
	}

	//неизвестный id
	if(!isset($arr[$id]))
		return _cacheErr('неизвестный id подрубрики', $id);
	
	//неизвестный ключ
	if(!isset($arr[$id][$i]))
		return _cacheErr('неизвестный ключ подрубрики', $i);

	//возврат конкретной подрубрики
	return $arr[$id][$i];
}
function _gn($nomer='all', $i='') {//Получение информации о всех номерах газеты из кеша
	$key = CACHE_PREFIX.'gn';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT *
				FROM `_setup_gazeta_nomer`
				WHERE `app_id`=".APP_ID."
				ORDER BY `general_nomer`";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q)) {
			$pubex = explode('-', $r['day_public']);
			$arr[$r['id']] = array(
				'general_nomer' => $r['general_nomer'],
				'week' => $r['week_nomer'],
				'day_print' => $r['day_print'],
				'day_public' => $r['day_public'],
				'day_public_1' => $pubex[2].'.'.$pubex[1].'.'.$pubex[0],
				'pub' => FullData($r['day_public'], 1, 1, 1),
				'pc' => $r['polosa_count'],
				'pub_count' => '',  //количество выпусков для конкретного вида деятельности
				'lost' => strtotime($r['day_public']) < time() //прошедший номер
			);
		}
		xcache_set($key, $arr, 86400);
	}

	//все номера
	if($nomer == 'all')
		return $arr;

	//ассоциативный список номеров газет для JS
	if($nomer == 'js_ass') {
		if(empty($arr))
			return '{}';

		$gn = array();
		foreach($arr as $n => $r)
			array_push($gn, "\n".$n.':{'.
				'gen:'.$r['general_nomer'].','.
				'week:'.$r['week'].','.
				'pub:"'.$r['day_public'].'",'.
				'txt:"'.FullData($r['day_public'], 0, 1, 1).'",'.
				'pc:'.$r['pc'].
			'}');
	
		return '{'.implode(',', $gn).'}';
	}

	//ассоциативный список номеров газет для JS
	if($nomer == 'js_year_spisok' || $nomer == 'arr_year_spisok') {
		if(empty($arr))
			return '[]';

		$year = $i['gn_year'];
		$service_id = $i['service_id'];

		if(!preg_match(REGEXP_YEAR, $year))
			return '[]';

		$inYear = array();
		foreach($arr as $id => $r)
			if($year == substr($r['day_public'], 0, 4))
				$inYear[$id] = $r;

		if(empty($inYear))
			return '[]';

		// количество выходов для каждого номера
		$sql = "SELECT
					`gazeta_nomer_id`,
					COUNT(`gn`.`id`) `count`
				FROM
					`_zayav_gazeta_nomer` `gn`,
					`_zayav` `z`
				WHERE `z`.`app_id`=".APP_ID."
				  AND `z`.`id`=`gn`.`zayav_id`
				  AND `z`.`service_id`=".$service_id."
				  AND `gazeta_nomer_id` IN ("._idsGet($inYear, 'key').")
				GROUP BY `gazeta_nomer_id`";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q))
			$inYear[$r['gazeta_nomer_id']]['pub_count'] = $r['count'];

		$gn = array();
		foreach($inYear as $id => $r) {
			$ex = explode('-', $r['day_public']);
			$public = abs($ex[2]).' '._monthCut($ex[1]);

/*			$gn[$id] = array(
				'title' => $r['week'].' ('.$r['general_nomer'].') выход '.$public,
				'content' =>
					'<div'.($r['lost'] ? ' class="lost"' : '').'>'.
						'<b>'.$r['week'].'</b>'.
						'('.$r['general_nomer'].')'.
						'<span> выход '.$public.'</span>'.
					 '</div>'
			);
*/
			$gn[$id] =
				'<span'.($r['lost'] ? ' class="lost"' : '').'>'.
					'<b>'.$r['week'].'</b> '.
					'('.$r['general_nomer'].')'.
				'</span>'.
				' вых. '.$public.
				'<em>'.$r['pub_count'].'</em>';
		}
		return $nomer == 'js_year_spisok' ? _selJson($gn) : _selArray($gn);
	}

	//первый активный номер
	if($nomer == 'first') {
		if(empty($arr))
			return 0;

		foreach($arr as $n => $r) {
			if(strtotime($r['day_public']) < time())
				continue;
			return $n;
		}

		return 0;
	}

	//последний активный номер
	if($nomer == 'last') {
		if(empty($arr))
			return 0;

		end($arr);
		return key($arr);
	}

	if($nomer == 'gn_max') {
		if(empty($arr))
			return 0;

		end($arr);
		$id = key($arr);
		return $arr[$id]['general_nomer'] + 1;
	}

	//неизвестный номер
	if(!isset($arr[$nomer]))
		return _cacheErr('неизвестный номер газеты', $nomer);

	//возвращение данных одного номера
	if(!$i)
		return $arr[$nomer];

	//неизвестный ключ
	if(!isset($arr[$nomer][$i]))
		return _cacheErr('неизвестный ключ номера газеты', $i);

	return $arr[$nomer][$i];
}
function _gnToZayav($arr, $gazeta_nomer_id=0) {//вставка данных полос газеты конкретного номера в заявки
	if(empty($arr))
		return array();

	foreach($arr as $r)
		$arr[$r['id']] += array(
			'gn_zgn_id' => 0,   //id публикации номера из _zayav_gazeta_nomer
			'gn_polosa_id' => 0,
			'gn_polosa_name' => '',
			'gn_polosa_nomer' => 0,
			'gn_polosa_cena' => 0
		);

	if(!$gazeta_nomer_id)
		return $arr;

	if(!$zayav_ids = _idsGet($arr))
		return $arr;

	$sql = "SELECT *
			FROM `_zayav_gazeta_nomer`
			WHERE `gazeta_nomer_id`=".$gazeta_nomer_id."
			  AND `zayav_id` IN (".$zayav_ids.")";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		$arr[$r['zayav_id']]['gn_zgn_id'] = $r['id'];
		$arr[$r['zayav_id']]['gn_polosa_id'] = $r['dop'];
		$arr[$r['zayav_id']]['gn_polosa_name'] = _polosa($r['dop']);
		$arr[$r['zayav_id']]['gn_polosa_nomer'] = $r['polosa'];
		$arr[$r['zayav_id']]['gn_polosa_cena'] = round($r['cena'], 2);
	}

	return $arr;
}
function _obDop($id, $i='name') {//Дополнительные параметры для объявлений
	$key = CACHE_PREFIX.'gazeta_obdop';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT *
				FROM `_setup_gazeta_ob_dop`
				WHERE `app_id`=".APP_ID;
		$arr = query_arr($sql);
		xcache_set($key, $arr, 86400);
	}

	if(!$id)
		return '';

	//список названий для select
	if($id == 'js_name') {
		$send = array();
		foreach($arr as $r)
			$send[$r['id']] = $r['name'];
		return _selJson($send);
	}

	//цены в ассоциативном массиве JS
	if($id == 'js_cena') {
		$send = array();
		foreach($arr as $r)
			$send[$r['id']] = _cena($r['cena']);
		return _assJson($send);
	}

	//неизвестный id
	if(!isset($arr[$id]))
		return _cacheErr('неизвестный id дополнительного параметра', $id);

	//неизвестный ключ
	if(!isset($arr[$id][$i]))
		return _cacheErr('неизвестный ключ дополнительного параметра', $i);

	//конкретный дополнительный параметр
	return $arr[$id][$i];
}
function _polosa($id, $i='name') {//Название полосы для рекламы
	$key = CACHE_PREFIX.'gazeta_polosa';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT *
				FROM `_setup_gazeta_polosa_cost`
				WHERE `app_id`=".APP_ID."
				ORDER BY `sort`";
		$arr = query_arr($sql);
		xcache_set($key, $arr, 86400);
	}

	//список названий для select
	if($id == 'js_name') {
		$send = array();
		foreach($arr as $r)
			$send[$r['id']] = $r['name'];
		return _selJson($send);
	}

	//цены в ассоциативном массиве JS
	if($id == 'js_cena') {
		$send = array();
		foreach($arr as $r)
			$send[$r['id']] = _cena($r['cena']);
		return _assJson($send);
	}

	//требуется ли указание полосы в ассоциативном массиве JS
	if($id == 'js_polosa') {
		$send = array();
		foreach($arr as $r) {
			if(!$r['polosa'])
				continue;
			$send[$r['id']] = 1;
		}
		return _assJson($send);
	}

	//неизвестный id
	if(!isset($arr[$id]))
		return _cacheErr('неизвестный id полосы газеты', $id);

	//неизвестный ключ
	if(!isset($arr[$id][$i]))
		return _cacheErr('неизвестный ключ полосы газеты', $i);

	//значение по ключу конкретной полоса газеты
	return $arr[$id][$i];
}


function _zayavStatus($id=false, $i='name') {//кеширование статусов заявки
	$key = CACHE_PREFIX.'zayav_status';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					*,
					0 `count`
				FROM `_zayav_status`
				WHERE `app_id`=".APP_ID."
				ORDER BY `sort`";
		$arr = query_arr($sql);
		xcache_set($key, $arr, 86400);
	}

	//фильтр для списка заявок
	if($i == 'filter') {
		$sql = "SELECT
					`status_id`,
					COUNT(*) `count`
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND `service_id`="._service('current')."
				  AND `status_id`
				  ".(RULE_ZAYAV_EXECUTER ?  " AND `executer_id`=".VIEWER_ID : '')."
				  AND !`deleted`
				GROUP BY `status_id`";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q))
			$arr[$r['status_id']]['count'] = $r['count'];

		$filter = '';
		foreach($arr as $r) {
			if(!$r['count'])
				continue;
			$filter .=
				'<tr style="background-color:#'.$r['color'].'">'.
					'<td val="'.$r['id'].'">'.
						$r['name'].
						'<em>'.$r['count'].'</em>';
		}
		return
			'<div id="zayav-status-filter"'.($id ? ' class="us"' : '').'">'.
				'<div id="any">Любой статус</div>'.
				'<div id="sel"'.($id ? ' style="background:#'.$arr[$id]['color'].'"' : '').'>'.($id ? $arr[$id]['name'] : '').'</div>'.
				'<div id="status-tab">'.
					'<table>'.
						'<tr><td val="0"><b>Любой статус</b>'.
						$filter.
					'</table>'.
				'</div>'.
			'</div>';
	}

	if($id == 'all')
		return $arr;

	//список названий JS для select
	if($id == 'js_name') {
		$send = array();
		foreach($arr as $r) {
			if($r['deleted'])
				continue;
			$send[$r['id']] = $r['name'];
		}
		return _selJson($send);
	}

	//ассоциативный список цветов JS
	if($id == 'js_color_ass') {
		$send = array();
		foreach($arr as $r) {
			if($r['deleted'])
				continue;
			$send[$r['id']] = $r['color'];
		}
		return _assJson($send);
	}

	//ассоциативный список описаний JS
	if($id == 'js_about_ass') {
		$send = array();
		foreach($arr as $r) {
			if($r['deleted'])
				continue;
			$send[$r['id']] = $r['about'];
		}
		return _br(_assJson($send));
	}

	//ассоциативный список "Не использовать повторно" JS
	if($id == 'js_nouse_ass') {
		$send = array();
		foreach($arr as $r) {
			if($r['deleted'])
				continue;
			if(!$r['nouse'])
				continue;
			$send[$r['id']] = 1;
		}
		return _assJson($send);
	}

	//ассоциативный список "Исполнители" JS
	if($id == 'js_executer_ass') {
		$send = array();
		foreach($arr as $r) {
			if($r['deleted'])
				continue;
			if(!$r['executer'])
				continue;
			$send[$r['id']] = 1;
		}
		return _assJson($send);
	}

	//ассоциативный список "Показывать срок" JS
	if($id == 'js_srok_ass') {
		$send = array();
		foreach($arr as $r) {
			if($r['deleted'])
				continue;
			if(!$r['srok'])
				continue;
			$send[$r['id']] = 1;
		}
		return _assJson($send);
	}

	//ассоциативный список "Вносить начисление" JS
	if($id == 'js_accrual_ass') {
		$send = array();
		foreach($arr as $r) {
			if($r['deleted'])
				continue;
			if(!$r['accrual'])
				continue;
			$send[$r['id']] = 1;
		}
		return _assJson($send);
	}

	//ассоциативный список "Добавлять напоминание" JS
	if($id == 'js_remind_ass') {
		$send = array();
		foreach($arr as $r) {
			if($r['deleted'])
				continue;
			if(!$r['remind'])
				continue;
			$send[$r['id']] = 1;
		}
		return _assJson($send);
	}

	//ассоциативный список "Указывать фактический день" JS
	if($id == 'js_day_fact_ass') {
		$send = array();
		foreach($arr as $r) {
			if($r['deleted'])
				continue;
			if(!$r['day_fact'])
				continue;
			$send[$r['id']] = 1;
		}
		return _assJson($send);
	}

	//возвращение статуса по умолчанию
	if($id == 'default') {
		foreach($arr as $r)
			if($r['default'])
				return _num($r['id']);
		return 0;
	}

	//id статусов, которые скрываются из общего списка
	if($id == 'hide_ids') {
		$ids = array();
		foreach($arr as $r)
			if($r['hide'])
				$ids[] = $r['id'];
		if(empty($ids))
			return 0;
		return implode(',', $ids);
	}

	//id статусов, у которых нужно учитывать срок выполнения
	if($id == 'srok_ids') {
		$ids = array();
		foreach($arr as $r)
			if($r['srok'])
				$ids[] = $r['id'];
		if(empty($ids))
			return 0;
		return implode(',', $ids);
	}

	if($id && !isset($arr[$id])) {
		if($i == 'bg')
			return '';
		return _cacheErr('неизвестный id статуса', $id);
	}

	if(!$id) {
		if($i == 'bg')
			return '';
		return _cacheErr('нулевой статус');
	}

	if($i == 'name')
		return $arr[$id]['name'];

	if($i == 'color') {
		$c = $arr[$id]['color'];
		if(strlen($c) != 6)
			return $c[0].$c[0].$c[1].$c[1].$c[2].$c[2];
		return $c;
	}

	if($i == 'executer')
		return _bool($arr[$id]['executer']);

	if($i == 'srok')
		return _bool($arr[$id]['srok']);

	if($i == 'day_fact')
		return _bool($arr[$id]['day_fact']);

	if($i == 'bg')
		return ' style="background:#'.$arr[$id]['color'].'"';

	return _cacheErr('неизвестный ключ статуса', $i);
}
function _zayavStatusButton($z) {//кнопка статуса в информации о заявке
	if(empty($z['zpu'][45]))
		return '';

	if($z['status_day'] == '0000-00-00')
		$z['status_day'] = $z['status_dtime'];

	return
	'<tr><td class="label">Статус:<td>'.
		'<div id="zayav-status-button"'._zayavStatus($z['status_id'], 'bg').'>'.
			'<b class="hd">'._zayavStatus($z['status_id']).'</b> '.
			(_zayavStatus($z['status_id'], 'day_fact') ? FullData($z['status_day'], 1) : '').
		'</div>';
}
function _zayavValToList($arr) {//вставка данных заявок в массив по zayav_id
	$ids = array();
	$arrIds = array();
	foreach($arr as $key => $r)
		if(!empty($r['zayav_id'])) {
			$ids[$r['zayav_id']] = 1;
			$arrIds[$r['zayav_id']][] = $key;
		}
	if(empty($ids))
		return $arr;

	$sql = "SELECT *
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `id` IN (".implode(',', array_keys($ids)).")";
	$zayav = query_arr($sql);

	if(!isset($r['client_phone'])) {
		foreach($zayav as $r)
			foreach($arrIds[$r['id']] as $id)
				$arr[$id]['client_id'] = $r['client_id'];
		$arr = _clientValToList($arr);
	}

	foreach($zayav as $r) {
		foreach($arrIds[$r['id']] as $id) {
			$dolg = $r['sum_accrual'] - $r['sum_pay'];
			$dolg = $dolg > 0 ? $dolg : 0;
			$arr[$id] += array(
				'zayav_name' => $r['name'],
				'zayav_n' => $r['nomer'],
				'zayav_link' =>
					'<a href="'.URL.'&p=zayav&d=info&id='.$r['id'].'" class="zayav_link">'.
						'<span'.($r['deleted'] ? ' class="deleted"' : '').'>№'.$r['nomer'].'</span>'.
						'<div class="tooltip">'._zayavTooltip($r, $arr[$id]).'</div>'.
					'</a>',
				'zayav_link_name' =>
					'<a href="'.URL.'&p=zayav&d=info&id='.$r['id'].'">'.
						'<b'.($r['deleted'] ? ' class="deleted"' : '').'>'.$r['name'].'</b>'.
					'</a>',
				'zayav_nomer_name' =>
					'<a href="'.URL.'&p=zayav&d=info&id='.$r['id'].'"'.($r['deleted'] ? ' class="deleted"' : '').'>'.
						'№<b>'.$r['nomer'].'</b>: '.$r['name'].
					'</a>',
				'zayav_color' => //подсветка заявки на основании статуса
					'<a href="'.URL.'&p=zayav&d=info&id='.$r['id'].'" class="zayav_link color"'._zayavStatus($r['status_id'], 'bg').'>'.
						'№'.$r['nomer'].
						'<div class="tooltip">'._zayavTooltip($r, $arr[$id]).'</div>'.
					'</a>',
				'zayav_dolg_sum' => $dolg,
				'zayav_dolg' => $dolg ? '<span class="zayav-dolg'._tooltip('Долг по заявке', -45).$dolg.'</span>' : '',
				'zayav_status_day' => $r['status_day'],
				'zayav_adres' => $r['adres'],
				'dogovor_id' => $r['dogovor_id']
			);
		}
	}

	return $arr;
}
function _zayavTooltip($z, $v) {
	return $html =
		'<table>'.
			'<tr><td>'.
				'<td class="inf">'.
					'<div'._zayavStatus($z['status_id'], 'bg').
						' class="tstat'._tooltip('Статус заявки: '._zayavStatus($z['status_id']), -7, 'l').
					'</div>'.
					'<b>'.$z['name'].'</b>'.
	($z['client_id'] ?
			'<table>'.
				'<tr><td class="label top">Клиент:'.
					'<td>'.$v['client_name'].
						($v['client_phone'] ? '<br />'.$v['client_phone'] : '').
				'<tr><td class="label">Баланс:'.
					'<td><span class="bl" style=color:#'.($v['client_balans'] < 0 ? 'A00' : '090').'>'.$v['client_balans'].'</span>'.
			'</table>'
	: '').
		'</table>';
}
function _zayavCountToClient($spisok) {//прописывание квадратиков с количеством заявок в список клиентов
	if(APP_ID == 3495523)//todo не показывать в Купце - удалить
		return $spisok;

	//получение статусов заявок, которые есть у текущих клиентов
	$sql = "SELECT DISTINCT `status_id` `id`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `status_id`
			  AND `client_id` IN ("._keys($spisok).")";
	if(!$status_ids = query_ids($sql))
		return $spisok;

	//примерение каждому из клиентов количества заявок, которое соответствует каждому статусу
	foreach(_ids($status_ids, 1) as $id) {
		$sql = "SELECT
					`id`,
					`client_id`,
					COUNT(`id`) AS `count`
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND `status_id`=".$id."
				  AND !`deleted`
				  AND `client_id` IN ("._keys($spisok).")
				GROUP BY `client_id`";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q)) {
			$link = $r['count'] == 1 ? ' link' : '';
			$href = $r['count'] == 1 ? ' href="'.URL.'&p=zayav&d=info&id='.$r['id'].'"' : '';
			$spisok[$r['client_id']]['zayav'] .=
				'<a'.$href._zayavStatus($id, 'bg').' class="z-count'.$link._tooltip(_zayavStatus($id), -8, 'l').
					$r['count'].
				'</a>';
		}
	}

	return $spisok;
}
function _zayavExecuterToList($zayav) {//прописывание исполнителей в список заявок
	if(empty($zayav))
		return array();

	$ids = _idsGet($zayav);

	foreach($zayav as $r)
		$zayav[$r['id']]['executer_spisok'] = array();

	$sql = "SELECT *
			FROM `_zayav_expense`
			WHERE `zayav_id` IN (".$ids.")
			  AND `worker_id`
			ORDER BY `id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		$zayav[$r['zayav_id']]['executer_spisok'][] = _viewer($r['worker_id'], 'viewer_link_zp');
	}

	foreach($zayav as $r)
		$zayav[$r['id']]['executer_spisok'] = implode('<br />',$r['executer_spisok']);


	return $zayav;
}
function _zayavSkidka() {//массив скидок
	return array(
		5 => '5%',
		10 => '10%',
		15 => '15%',
		20 => '20%',
		25 => '25%',
		30 => '30%',
		35 => '35%',
		40 => '40%',
		45 => '45%',
		50 => '50%',
	);
}

function _zayavFilter($v) {
	$default = array(
		'page' => 1,
		'limit' => 20,
		'client_id' => 0,
		'find' => '',
		'sort' => 1,
		'desc' => 0,
		'ob_onpay' => 0,
		'status' => 0,
		'finish' => '0000-00-00',
		'executer_id' => 0,
		'zpzakaz' => 0,
		'tovar_place_id' => 0,
		'paytype' => 0,
		'noschet' => 0,
		'nofile' => 0,
		'noattach' => 0,
		'noattach1' => 0,
		'tovar_name_id' => 0,
		'tovar_id' => 0,
		'gn_year' => strftime('%Y'),  //год номеров газеты 51
		'gn_nomer_id' => _gn('first'),//номер газеты 51
		'gn_polosa' => 0,             //полоса (для рекламы)
		'gn_polosa_color' => 0,       //цветность полосы
		'deleted' => 0,
		'deleted_only' => 0
	);
	$filter = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 20,
		'client_id' => _num(@$v['client_id']),
		'find' => trim(@$v['find']),
		'sort' => _num(@$v['sort']) ? _num(@$v['sort']) : 1,
		'desc' => _bool(@$v['desc']),
		'ob_onpay' => _bool(@$v['ob_onpay']),
		'status' => _num(@$v['status']),
		'finish' => preg_match(REGEXP_DATE, @$v['finish']) ? $v['finish'] : $default['finish'],
		'executer_id' => intval(@$v['executer_id']),
		'zpzakaz' => _num(@$v['zpzakaz']),
		'tovar_place_id' => _num(@$v['tovar_place_id']),
		'paytype' => _num(@$v['paytype']),
		'noschet' => _bool(@$v['noschet']),
		'nofile' => _bool(@$v['nofile']),
		'noattach' => _bool(@$v['noattach']),
		'noattach1' => _bool(@$v['noattach1']),
		'tovar_name_id' => _num(@$v['tovar_name_id']),
		'tovar_id' => _num(@$v['tovar_id']),
		'gn_year' => _num(@$v['gn_year']) ? $v['gn_year'] : $default['gn_year'],
		'gn_nomer_id' => _num(@$v['gn_nomer_id']) ? $v['gn_nomer_id'] : $default['gn_nomer_id'],
		'gn_polosa' => _num(@$v['gn_polosa']),
		'gn_polosa_color' => _num(@$v['gn_polosa_color']),
		'deleted' => _bool(@$v['deleted']),
		'deleted_only' => _bool(@$v['deleted_only']),
		'clear' => ''
	);
	foreach($default as $k => $r)
		if($r != $filter[$k]) {
			$filter['clear'] = '<button class="vk small red fr">Очистить фильтр</button>';
			break;
		}

	$filter['service_id'] = _service('current', _num(@$v['service_id']));
	$filter['nofind'] = empty($filter['find']) ? '' : ' dn';//скрытие остальных полей, если использовался быстрый поиск

	return $filter;
}
function _zayav_list($v=array()) {
	$data = _zayav_spisok($v);
	$v = $data['filter'];

	return
	'<div id="_zayav">'.
		_service('menu').
		'<div class="result">'.$data['result'].'</div>'.
		'<table class="tabLR">'.
			'<tr><td id="spisok">'.$data['spisok'].
				'<td class="right">'.
					'<button class="vk fw" onclick="_zayavEdit('.$v['service_id'].')">Новая заявка</button>'.
					_zayavPoleFilter($v).
	(VIEWER_ADMIN ? _check('deleted', '+ удалённые заявки', $v['deleted'], 1).
					'<div id="deleted-only-div"'.($v['deleted'] ? '' : ' class="dn"').'>'.
						_check('deleted_only', 'только удалённые', $v['deleted_only'], 1).
					'</div>'
	: '').
		'</table>'.
	'</div>';
}
//	if(ZAYAV_INFO_DEVICE)
//		$zayav = _imageValToZayav($zayav);

/*
	//Запчасти
	$sql = "SELECT `zayav_id`,`zp_id` FROM `zp_zakaz` WHERE `zayav_id` IN (".$zayavIds.")";
	$q = query($sql);
	$zp = array();
	$zpZakaz = array();
	while($r = mysql_fetch_assoc($q)) {
		$zp[$r['zp_id']] = $r['zp_id'];
		$zpZakaz[$r['zayav_id']][] = $r['zp_id'];
	}
	if(!empty($zp)) {
		$sql = "SELECT `id`,`name_id` FROM `zp_catalog` WHERE `id` IN (".implode(',', $zp).")";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q))
			$zp[$r['id']] = $r['name_id'];
		foreach($zpZakaz as $id => $zz)
			foreach($zz as $i => $zpId)
				$zpZakaz[$id][$i] = _zpName($zp[$zpId]);
	}
//	(isset($zpZakaz[$id]) ? '<tr><td class="label">Заказаны з/п:<td class="zz">'.implode(', ', $zpZakaz[$id]) : '').
//					'<td class="image">'.$img.
 			  ($r['imei'] ? '<tr><td class="label">IMEI:<td>'.$r['imei'] : '').
		    ($r['serial'] ? '<tr><td class="label">Серийный номер:<td>'.$r['serial'] : '').
		$img = $images['zayav'.$id]['id'] ? $images['zayav'.$id]['img'] : $images['dev'.$r['base_model_id']]['img'];
		if($filter['find']) {
			if(preg_match($reg, $r['model']))
				$r['model'] = preg_replace($reg, "<em>\\1</em>", $r['model'], 1);
			if($regEngRus && preg_match($regEngRus, $r['model']))
				$r['model'] = preg_replace($regEngRus, '<em>\\1</em>', $r['model'], 1);
			$r['imei'] = preg_match($reg, $r['imei']) ? preg_replace($reg, "<em>\\1</em>", $r['imei'], 1) : '';
			$r['serial'] = preg_match($reg, $r['serial']) ? preg_replace($reg, "<em>\\1</em>", $r['serial'], 1) : '';
		} else {
			$r['imei'] = '';
			$r['serial'] = '';
		}

*/
function _zayav_spisok($v) {
	$filter = _zayavFilter($v);
	$filter = _filterJs('ZAYAV', $filter);

	$cond = "`app_id`=".APP_ID."
		 AND `service_id`=".$filter['service_id'];
	if(!VIEWER_ADMIN)
		$cond .= " AND !`deleted`";
	if(RULE_ZAYAV_EXECUTER)
		$cond .= " AND `executer_id`=".VIEWER_ID;

	define('SROK', $filter['finish'] != '0000-00-00');
	define('FIND', !empty($filter['find']));

	$nomer = 0;
	$zpu = _zayavPole($filter['service_id']);


	if(FIND) {
		$engRus = _engRusChar($filter['find']);

		$cond .= " AND (`find` LIKE '%".$filter['find']."%'
					OR `about` LIKE '%".$filter['find']."%'
					OR `phone` LIKE '%".$filter['find']."%'
					OR `adres` LIKE '%".$filter['find']."%'

					OR REPLACE(`phone`,' ','') LIKE '%".$filter['find']."%'
					".
			($engRus ?
				  " OR `find` LIKE '%".$engRus."%'
					OR `about` LIKE '%".$engRus."%'
					OR `phone` LIKE '%".$engRus."%'
					OR `adres` LIKE '%".$engRus."%'
				  "
			: '').")";
		
		//просмотр условия быстрого поиска в клиентах в полях: name
		$sql = "SELECT `id`
				FROM `_client`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				   AND`name` LIKE '%".$filter['find']."%'";
		if($client_ids = query_ids($sql)) {
			$sql = "SELECT DISTINCT `id`
					FROM `_zayav`
					WHERE `app_id`=".APP_ID."
					  AND `client_id` IN (".$client_ids.")";
			$zayav_ids = query_ids($sql);
			$cond .= " OR `id` IN (".$zayav_ids.")";
		}

		if($filter['page'] == 1)
			$nomer = _num($filter['find']);
	} else {
		if($filter['ob_onpay'])
			$cond .= " AND `onpay_checked`=2";
		else {
			if($filter['client_id'])
				$cond .= " AND `client_id`=".$filter['client_id'];
			if($filter['status'])
				$cond .= " AND `status_id`=".$filter['status'];
			if(SROK)
				$cond .= " AND `srok`='".$filter['finish']."' AND `status_id` IN ("._zayavStatus('srok_ids').")";
			if($filter['paytype'])
				$cond .= " AND `pay_type`=".$filter['paytype'];
			if($filter['noschet'])
				$cond .= " AND !`schet_count`";
			if($filter['executer_id'])
				$cond .= " AND `executer_id`=".($filter['executer_id'] < 0 ? 0 : $filter['executer_id']);
			if($filter['nofile']) {
				//получение id расходов по заявке из настроек, к которым прикрепляется файл
				$sql = "SELECT `id`
						FROM `_zayav_expense_category`
						WHERE `app_id`=".APP_ID."
						  AND `dop`=4";
				if($zeIds = query_ids($sql)) {
					//id заявок, к которым прикреплены файлы
					$sql = "SELECT DISTINCT(`zayav_id`)
							FROM `_zayav_expense`
							WHERE `app_id`=".APP_ID."
							  AND `category_id` IN (".$zeIds.")";
					$zayav_ids = query_ids($sql);

					//инверсия
					$cond .= " AND `id` NOT IN (".$zayav_ids.")";
				}
			}

			if($filter['noattach'])
				$cond .= " AND !`attach_id` AND !`attach_cancel`";
			if($filter['noattach1'])
				$cond .= " AND !`attach1_id` AND !`attach1_cancel`";

			if($filter['tovar_name_id']) {
				$sql = "SELECT DISTINCT `zayav_id`
						FROM `_zayav_tovar` `zt`,
							`_tovar` `t`
						WHERE `zt`.`app_id`=".APP_ID."
						  AND `t`.`id`=`zt`.`tovar_id`
						  AND `t`.`name_id`=".$filter['tovar_name_id'];
				$zayav_ids = query_ids($sql);
				$cond .= " AND `id` IN (".$zayav_ids.")";
			}

			if($filter['tovar_id']) {
				$sql = "SELECT DISTINCT `zayav_id`
						FROM `_zayav_tovar`
						WHERE `app_id`=".APP_ID."
						  AND `tovar_id`=".$filter['tovar_id'];
				$zayav_ids = query_ids($sql);
				$cond .= " AND `id` IN (".$zayav_ids.")";
			}

			if($filter['tovar_place_id'])
				$cond .= " AND `tovar_place_id`=".$filter['tovar_place_id'];

			if(isset($zpu[51]) && $filter['gn_nomer_id'] && !$filter['client_id']) {
				//дополнительное условие по номеру полосы
				$polosaCond = '';
				if($filter['gn_polosa'])
					switch($filter['gn_polosa']) {
						case 1: $polosaCond = " AND `dop`=1"; break;  //первая
						case 102: $polosaCond = " AND `dop`=2"; break;//последняя
						case 103: $polosaCond = " AND `dop`=3"; break;//Внутренняя чёрно-белая
						case 104: $polosaCond = " AND `dop`=4"; break;//Внутренняя цветная
						case 105: $polosaCond = " AND `dop` IN (3,4) AND !`polosa`"; break;//Внутренняя (номер полосы не указан)
						default:
							$polosaCond = " AND `polosa`=".$filter['gn_polosa'];
							if($filter['gn_polosa_color'])
								$polosaCond .= " AND `dop`=".$filter['gn_polosa_color'];
					}

				$sql = "SELECT DISTINCT `zayav_id`
						FROM `_zayav_gazeta_nomer`
						WHERE `app_id`=".APP_ID."
						  AND `gazeta_nomer_id`=".$filter['gn_nomer_id'].
						  $polosaCond;
				$zayav_ids = query_ids($sql);
				$cond .= " AND `id` IN (".$zayav_ids.")";
			}

			if(!SROK && _zayavStatus('hide_ids') && !$filter['client_id'] && !$filter['status'])
				$cond .= " AND `status_id` NOT IN ("._zayavStatus('hide_ids').")";
		}
	}

	if(VIEWER_ADMIN) {
		if($filter['deleted']) {
			if($filter['deleted_only'])
				$cond .= " AND `deleted`";
		} else
			$cond .= " AND !`deleted`";
	}

	$sql = "SELECT
				COUNT(*) `all`,
				SUM(`count`) `count`
			FROM `_zayav`
			WHERE ".$cond;
	$r = query_assoc($sql);
	$all = $r['all'];
	$count = $r['count'];

	$zayav = array();

	//выделение номера заявки и номера договора при быстром поиске
	$dogNomerId = 0;//id заявки, которой будет совпадение при номере договора
	if($nomer) {
		$sql = "SELECT *
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `nomer`=".$nomer."
				LIMIT 1";
		if($r = query_assoc($sql)) {
			$all++;
			$filter['limit']--;
			$r['nomer'] = '<em>'.$r['nomer'].'</em>';
			$r['note'] = '';
			$r['schet'] = '';
			$r['sum_accrual'] = round($r['sum_accrual']);
			$r['sum_pay'] = round($r['sum_pay']);
			$zayav[$r['id']] = $r;
		}

		$sql = "SELECT `zayav_id`
				FROM `_zayav_dogovor`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `nomer`=".$nomer."
				LIMIT 1";
		if($id = query_value($sql)) {
			$dogNomerId = $id;
			$sql = "SELECT *
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `id`=".$id;
			if($r = query_assoc($sql)) {
				$all++;
				$filter['limit']--;
				$r['dogovor_nomer'] = '<em>'.$r['nomer'].'</em>';
				$r['note'] = '';
				$r['schet'] = '';
				$r['sum_accrual'] = round($r['sum_accrual']);
				$r['sum_pay'] = round($r['sum_pay']);
				$zayav[$r['id']] = $r;
			}
		}
	}

	if(!$all)
		return array(
			'all' => 0,
			'result' => 'Заявок не найдено'.$filter['clear'],
			'spisok' => $filter['js'].'<div class="_empty">Заявок не найдено</div>',
			'filter' => $filter
		);

	$send = array(
		'all' => $all,
		'result' => 'Показан'._end($all, 'а', 'о').' '.$all.' заяв'._end($all, 'ка', 'ки', 'ок').
					(!empty($zpu[49]['v1']) && $count ? '<span id="z-count">('.$count.' шт.)</span>' : '').
					$filter['clear'],
		'spisok' => $filter['js'],
		'filter' => $filter
	);

	$sql = "SELECT
	            *,
	            '' `note`,
				'' `schet`
			FROM `_zayav`
			WHERE ".$cond."
			ORDER BY `".($filter['sort'] == 2 ? 'status_dtime' : 'dtime_add')."` ".($filter['desc'] ? 'ASC' : 'DESC')."
			LIMIT "._startLimit($filter);
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		if($nomer == $r['nomer'])
			continue;
		if($dogNomerId == $r['id'])
			continue;
		$r['sum_accrual'] = round($r['sum_accrual']);
		$r['sum_pay'] = round($r['sum_pay']);
		if(FIND) {
			$r['name'] = _findRegular($filter['find'], $r['name']);
			$r['about'] = _findRegular($filter['find'], $r['about']);

			if(!$phone = _findRegular($filter['find'], $r['phone'], 1))
				$phone = _findRegular($filter['find'], $r['phone'], 1, 1);
			$r['phone'] = $phone;

			$r['adres'] = _findRegular($filter['find'], $r['adres'], 1);
		}

		$zayav[$r['id']] = $r;
	}

	if(!$filter['client_id'])
		$zayav = _clientValToList($zayav);

	$zayav = _dogovorValToList($zayav);
	$zayav = _schetPayToZayav($zayav);
	$zayav = _gnToZayav($zayav, $filter['gn_nomer_id']);
	$zayav = _zayavNote($zayav);
	if(isset($zpu[42]))
		$zayav = _imageValToZayav($zayav);

	foreach($zayav as $r)
		$send['spisok'] .= _zayavPoleUnit($zpu, $r, $filter);

	$send['spisok'] .= _next($filter + array(
			'type' => 2,
			'all' => $all
		));
	return $send;
}
function _zayavNote($arr) {//прикрепление заметок или комментариев в массив заявок
	if(empty($arr))
		return array();

	$ids = implode(',', array_keys($arr));

	$zn = array(); //ассоциация: id заметки -> id заявки

	//Прикрепление заметок
	$sql = "SELECT
				`id`,
				`page_id`,
				`txt`
			FROM `_note`
			WHERE `page_name`='zayav'
			  AND `page_id` IN (".$ids.")
			  AND !`deleted`
			ORDER BY `page_id`,`id` DESC";
	$q = query($sql);
	$zayav_id = 0; //выбор только верхней заметки в заявке
	while($r = mysql_fetch_assoc($q)) {
		if($zayav_id != $r['page_id']) {
			$zayav_id = $r['page_id'];
			$arr[$r['page_id']]['note'] = $r['txt'];
			$zn[$r['id']] = $r['page_id'];
		}
	}

	if(empty($zn))
		return $arr;

	//Прикрепление комментариев
	$note_ids = implode(',', array_keys($zn));
	$sql = "SELECT
				`note_id`,
				`txt`
			FROM `_note_comment`
			WHERE `note_id` IN (".$note_ids.")
			  AND !`deleted`
			ORDER BY `id` ASC";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		$zayav_id = $zn[$r['note_id']];
		$arr[$zayav_id]['note'] = $r['txt'];
	}

	return $arr;
}
function _dogovorValToList($arr) {//вставка данных договора в массив по dogovor_id
	$ids = array();
	$arrIds = array();
	foreach($arr as $key => $r)
		if(!empty($r['dogovor_id'])) {
			$ids[$r['dogovor_id']] = 1;
			$arrIds[$r['dogovor_id']][] = $key;
		}
	if(empty($ids))
		return $arr;

	$sql = "SELECT *
			FROM `_zayav_dogovor`
			WHERE `app_id`=".APP_ID."
			  AND `id` IN (".implode(',', array_keys($ids)).")";
	$dog = query_arr($sql);

	foreach($dog as $r) {
		foreach($arrIds[$r['id']] as $id) {
			$arr[$id] += array(
				'dogovor_n' => $r['nomer'],
				'dogovor_nomer' => '№'.$r['nomer'],
				'dogovor_data' => _dataDog($r['data_create']).' г.',
				'dogovor_sum' => _cena($r['sum']),
				'dogovor_avans' => _sumSpace(_cena($r['avans'])),
				'dogovor_line' => '<span class="dogovor_line '._tooltip('Сумма: '._sumSpace($r['sum']).' руб.', -3).
									'<b>№'.$r['nomer'].'</b>'.
									' от '._dataDog($r['data_create']).
								  '</span>',
				'dogovor_min' => '<span class="dogovor_line '._tooltip('Сумма: '._sumSpace($r['sum']).' руб. от '._dataDog($r['data_create']), -3, 'l').
									'<b>№'.$r['nomer'].'</b>'.
								  '</span>'
			);
		}
	}

	return $arr;
}
function _zayavObWord() {//Печать объявлений в формате Word
	$back = ' <button onclick="history.back()">назад</button>';

	if(!$gn = _num(@$_GET['gn']))
		die('Получен некорректный номер газеты.'.$back);

	if(!$service_id = _num(@$_GET['service_id']))
		die('Получен некорректный id вида деятельности.'.$back);

	$zpu = _zayavPole($service_id, 2);
	if(empty($zpu[54]))
		die('Для данного вида деятельности список номеров не может быть распечатан.'.$back);

	$sql = "SELECT
				`pub`.`id`,
				`rub`.`name` AS `rub`,
				IFNULL(`sub`.`name`,'') `sub`,
				`z`.`about` `txt`,
				`z`.`phone`,
				`z`.`adres`,
				IFNULL(`dop`.`name`,'') `dop`,
				`z`.`viewer_id_add`
			FROM `_zayav_gazeta_nomer` `pub`
				LEFT JOIN `_setup_gazeta_nomer` `sgn` ON `sgn`.`id`=`pub`.`gazeta_nomer_id`
				LEFT JOIN `_zayav` AS `z` ON `pub`.`zayav_id`=`z`.`id`
				LEFT JOIN `_setup_rubric` AS `rub` ON `z`.`rubric_id`=`rub`.`id`
				LEFT JOIN `_setup_rubric_sub` AS `sub` ON `z`.`rubric_id_sub`=`sub`.`id`
				LEFT JOIN `_setup_gazeta_ob_dop` AS `dop` ON `pub`.`dop`=`dop`.`id`
			WHERE `pub`.`app_id`=".APP_ID."
			  AND `pub`.`gazeta_nomer_id`=".$gn."
			  AND !`z`.`deleted`
			  AND `z`.`service_id`=".$service_id."
			  AND `z`.`onpay_checked` NOT IN (2,3)
			ORDER BY
			    `rub`.`sort`,
			    `sub`.`sort`,
			    `z`.`about`";
	if(!$spisok = query_arr($sql))
		die('Нет объявлений для номера '.$gn.'.'.$back);

	$word = 'Список объявлений для номера <b>'._gn($gn, 'general_nomer').'</b>:';  // Составление объявлений для отправки
	$rub = '';   // Контроль рубрик
	$sub = '';// Контроль подрубрик
	foreach($spisok as $r) {
		// Если рубрика изменилась, то печать
		if ($rub != $r['rub']) {
			$rub = $r['rub'];
			$word .= '<div class="rub">'.$rub.'</div>';
		}
		// Если подрубрика изменилась, то печать
		if ($sub != $r['sub']) {
			$sub = $r['sub'];
			$word .= '<div class="sub">'.$sub.'</div>';
		}
		$word .=
			'<div class="unit">'.
				$r['txt'].' '.
				($r['phone'] ? '<b>Тел.: '.$r['phone'].'</b>' : '').' '.
				($r['adres'] ? ($r['phone'] ? ', ' : '').'<b>Адрес: '.$r['adres'].'</b>' : '').
				($r['dop'] ? '<span class="dop">('.$r['dop'].')</span>' : '').
			'</div>';
	}

	$doc = new clsMsDocGenerator(
	    $pageOrientation = 'PORTRAIT',
	    $pageType = 'A4',
	    $cssFile = GLOBAL_DIR.'/css/ob-word.css',
	    $topMargin = 0.5,
	    $rightMargin = 1.0,
	    $bottomMargin = 0.5,
	    $leftMargin = 1.0);
	$doc->addParagraph($word);
	$doc->output('ob-word-nomer-'._gn($gn, 'general_nomer'));
}


/* Поля заявки */
function _zayavPole($service_id, $type_id=0, $i='') {
	$sql = "SELECT `id`,`name`
			FROM `_zayav_pole`
			".($type_id ? " WHERE `type_id`=".$type_id : '');
	$zpn = query_ass($sql);

	$send = array();
	$sql = "SELECT *
			FROM `_zayav_pole_use`
			WHERE `app_id`=".APP_ID."
			  AND `service_id`=".$service_id."
			  ".($type_id ? " AND `pole_id` IN ("._idsGet($zpn, 'key').")" : '')."
			ORDER BY `sort`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		$name = $r['label'] ? $r['label'] : $zpn[$r['pole_id']];
		$label = $name.':';
		$label .= $r['require'] ? '*' : '';
		$send[$r['pole_id']] = array(
			'label' => $label,
			'name' => $name,
			'require' => $r['require'],
			'v1' => $r['param_v1'],
			'v2' => $r['param_v2']
		);
	}

	if($i == 'js') {//в формате 21:1 для информации о заявке
		$ids = _idsGet($send, 'key');
		$ids = _idsAss($ids);
		return _assJson($ids);
	}

	return $send;
}
function _zayavPoleParamJs() {//используемые параметры заявки в формате JS
	$sql = "SELECT *
			FROM `_zayav_pole_use`
			WHERE `app_id`=".APP_ID."
			  AND (`param_v1` OR `param_v2`)
			ORDER BY `service_id`";
	if(!$spisok = query_arr($sql))
		return '{}';

	$send = array();
	foreach($spisok as $r)
		$send[$r['service_id']][$r['pole_id']] = '['.$r['param_v1'].','.$r['param_v2'].']';

	foreach($send as $i => $r)
		$send[$i] = _assJson($send[$i]);

	return str_replace('"', '', _assJson($send));
}
function _zayavPoleEdit($v=array()) {//Внесение/редактирование заявки
	$service_id = _num(@$v['service_id']);
	$client_id = _num(@$v['client_id']);
	$zayav_id = _num(@$v['zayav_id']);

	if(!$z = _zayavQuery($zayav_id))
		$z['client_id'] = $client_id;

	$tovar = _zayavTovarValue($zayav_id);
	$zpu = _zayavPole($service_id, 1);

	//получение размера скидки
	$skidka = 0;
	if($zayav_id)
		$skidka = $z['skidka'];
	else if($client_id)
		$skidka = _clientVal($client_id, 'skidka');


	$pole = array(
		1 => '<tr><td class="label">{label}'.
				 '<td><input type="text" id="ze-name" value="'.@$z['name'].'" />',

		2 => '<tr><td class="label topi">{label}'.
				 '<td><textarea id="ze-about">'.@$z['about'].'</textarea>'.
				 (@$zpu[2]['v1'] ? '<div id="ze-about-calc"></div>' : ''),
		
		3 => '<tr><td class="label">{label}'.
				 '<td><input type="text" class="money" id="ze-count" value="'.@$z['count'].'" /> шт.',
		
		4 => '<tr><td class="label topi">{label}'.
				 '<td><input type="hidden" id="ze-tovar-one" value="'.$tovar.'" />'.
			(@$zpu[4]['v1'] ?
			 '<tr class="tr-equip'.($tovar ? '' : ' dn').'">'.
				  '<td class="label top">Комплектация'.
				  '<td><input type="hidden" id="ze-equip" value="'.@$z['equip'].'" />'.
						'<div id="ze-equip-spisok"></div>'
			: ''),

		5 => '<tr><td class="label">{label}'.
				 '<td><input type="hidden" id="ze-client_id" value="'.@$z['client_id'].'" />'.
					($client_id ? '<b id="ze-client-name">'._clientVal($client_id, 'name').'</b>' : ''),

		6 => '<tr><td class="label">{label}'.
				 '<td><input type="text" id="ze-adres" value="'.@$z['adres'].'" />'.
			(@$zpu[6]['v1'] ?
					 '<input type="hidden" id="client-adres" />'
			: ''),

		7 => '<tr><td class="label">{label}'.
				 '<td><input type="text" id="ze-imei" value="'.@$z['imei'].'" />',
		
		8 => '<tr><td class="label">{label}'.
				 '<td><input type="text" id="ze-serial" value="'.@$z['serial'].'" />',
		
		9 => '<tr><td class="label">{label}<td id="ze-color">',
		
		10 => '<tr><td class="label">{label}'.
				  '<td><input type="hidden" id="ze-executer_id" value="'.@$z['executer_id'].'" />',

		11 => '<tr><td class="label topi">{label}'.
				 '<td><input type="hidden" id="ze-tovar-several" value="'.$tovar.'" />',

		12 => '<tr><td class="label top">{label}'.
				  '<td><input type="hidden" id="tovar-place" />',
		
		13 => '<tr><td class="label">{label}'.
				  '<td><input type="hidden" id="ze-srok" value="'.@$z['srok'].'" />',

		14 => '<tr><td class="label top">{label}<td><textarea id="ze-note"></textarea>',
		
		15 => (@$zpu[15]['v1'] ?
			   '<tr><td class="label">Указать стоимость вручную:'.
					'<td>'._check('ze-sum_cost_manual', '', _bool(@$z['sum_manual']))
			  : '').
			   '<tr><td class="label">{label}'.
				   '<td><input type="text" '.
							  'class="money" '.
							  'id="ze-sum_cost" '.
		   (@$zpu[15]['v1'] && !_bool(@$z['sum_manual']) ? 'readonly ' : '').
							  'value="'.(_cena(@$z['sum_cost']) ? _cena($z['sum_cost']) : '').'" '.
						'/> руб.'.
						'<span id="ze-skidka_sum"></span>',

		16 => '<tr><td class="label topi">{label}'.
				  '<td><input type="hidden" id="ze-pay_type" value="'.@$z['pay_type'].'" />',

		31 => '<tr><td class="label topi">{label}'.
				  '<td><input type="text" id="ze-size_x" maxlength="5" value="'.(_cena(@$z['size_x']) ? _cena($z['size_x']) : '').'" />'.
                    '<b class="xb">x</b>'.
                    '<input type="text" id="ze-size_y" maxlength="5" value="'.(_cena(@$z['size_y']) ? _cena($z['size_y']) : '').'" />'.
                    ' = '.
					'<input type="text" id="ze-kv_sm" readonly /> см<sup>2</sup>',

		37 => '<tr><td class="label">{label}'.
				  '<td><input type="text" id="ze-phone" value="'.@$z['phone'].'" />',

		38 => '<tr><td class="label top">{label}'.
			  '<tr><td colspan="2"><input type="hidden" id="ze-gn" />',

		39 => '<tr><td class="label">{label}'.
				  '<td><input type="hidden" id="ze-skidka" value="'.$skidka.'" />',

		40 => '<tr><td class="label">{label}'.
				   '<td><input type="hidden" id="ze-rubric_id" value="'.@$z['rubric_id'].'" />'.
					   '<input type="hidden" id="ze-rubric_id_sub" value="'.@$z['rubric_id_sub'].'" />'
	);

	$send = '';
	foreach($zpu as $pole_id => $r) {
		if(empty($pole[$pole_id]))
			continue;
		if($zayav_id && ($pole_id == 10 || $pole_id == 12 || $pole_id == 13 || $pole_id == 14))
			continue;

		$send .= str_replace('{label}', $r['label'], $pole[$pole_id]);
	}

	return
	'<table class="bs10">'.$send.'</table>';
}
function _zayavPoleFilter($v=array()) {//поля фильтра списка заявок
	$zpu = _zayavPole($v['service_id'], 2);
	$pole = array(
		17 => '<div id="find"></div>',

		18 => '<div class="findHead">{label}</div>'.
			   _radio('sort', array(1=>'По дате добавления',2=>'По обновлению статуса'), $v['sort']).
			   _check('desc', 'Обратный порядок', $v['desc']),

		24 => '<div class="findHead">{label}</div>'.
			  _zayavStatus($v['status'], 'filter'),

		25 => '<div class="findHead">{label}<input type="hidden" id="finish" value="'.$v['finish'].'" /></div>',

		26 => '<div class="findHead">{label}</div>'.
			  _radio('paytype', array(0=>'Не важно',1=>'Наличный',2=>'Безналиный'), $v['paytype'], 1),

		27 => '<div class="findHead">{label}</div>'.
			  '<input type="hidden" id="executer_id" value="'.$v['executer_id'].'" />',

		28 => '<div class="findHead">{label}</div>'.
			  '<input type="hidden" id="tovar_place_id" value="'.$v['tovar_place_id'].'" />',

		29 => _check('noschet', 'Счёт не выписан', $v['noschet']),
		30 => _check('nofile', '{label}', $v['nofile'], 1),
		35 => _check('noattach', '{label}', $v['noattach'], 1),
		36 => _check('noattach1', '{label}', $v['noattach1'], 1),

		32 => '<script>var ZAYAV_TOVAR_NAME_SPISOK='._zayavTovarName().';</script>'.
			  '<div class="findHead">{label}</div>'.
			  '<input type="hidden" id="tovar_name_id" value="'.$v['tovar_name_id'].'" />',

		33 => '<script>var ZAYAV_TOVAR_IDS="'._zayavTovarIds($v['service_id']).'";</script>'.
			  '<div class="findHead">{label}</div>'.
			  '<input type="hidden" id="tovar_id" value="'.$v['tovar_id'].'" />',

		51 => '<script>'.
				'var ZAYAV_GN_YEAR_SPISOK='._gn('js_year_spisok', $v).';'.
			  '</script>'.
			  '<div class="findHead">{label}</div>'.
			  '<input type="hidden" id="gn_year" value="'.$v['gn_year'].'" />'.
			  '<input type="hidden" id="gn_nomer_id" value="'.$v['gn_nomer_id'].'" />'.
			(@$zpu[51]['v1'] ?
			  '<div class="findHead">Полоса</div>'.
			  '<input type="hidden" id="gn_polosa" value="'.$v['gn_polosa'].'" />'.
			  '<div id="gn_polosa_color_filter"'.($v['gn_polosa'] > 1 && $v['gn_polosa'] < _gn($v['gn_nomer_id'], 'pc') ? '' : ' class="dn"').'>'.
					_radio('gn_polosa_color',
							array(
								0 => 'Любая цветность',
								3 => 'Чёрно-белая',
								4 => 'Цветная'
							),
							$v['gn_polosa_color'],
							1
					).
			  '</div>'

			: ''),
		54 => '<a id="obWordPrint" onclick="location.href=\''.URL.'&p=print&d=ob_word&gn=\'+ZAYAV.gn_nomer_id+\'&service_id=\'+ZAYAV.service_id" class="'._tooltip('Номер не определён', 5).
				'<div class="img_word"></div>'.
				'открыть в формате Word'.
			  '</a>',

		55 => _zayavObOnpay($v)
	);

	$send = '';
	foreach(_zayavPole($v['service_id'], 2) as $pole_id => $r) {
		if(empty($pole[$pole_id]))
			continue;

		//если показываются заявки только если сотрудник является исполнителем, то фильтр с исполнителями не выводится
		if(RULE_ZAYAV_EXECUTER && $pole_id == 27)
			continue;

		$unit = str_replace('{label}', $r['name'], $pole[$pole_id]);

		if($pole_id != 17 && $pole_id != 18)
			$unit = '<div class="nofind'.$v['nofind'].'">'.$unit.'</div>';

		$send .= $unit;
	}

	return $send;
}
/*

		//подсветка номера договора при быстром поиске
		if(FIND && $r['dogovor_id'])
			$r['dogovor_line'] = _findRegular($filter['find'], $r['dogovor_line']);


*/
function _zayavPoleUnit($zpu, $z, $filter) {//поля единицы списка заявок
	$deleted = $z['deleted'] ? ' deleted' : '';
	$statusColor = isset($zpu[41]) && !$deleted ? _zayavStatus($z['status_id'], 'bg') : '';
	$statusName =
		isset($zpu[41]) && !$deleted ?
			'<div class="status"'.($z['status_id'] ? ' style="color:#'._zayavStatus($z['status_id'], 'color').'"' : '').'>'.
				_zayavStatus($z['status_id']).
			'</div>'
		: '';


	// начисления и платежи
	$pay = '';
	if($z['sum_accrual'] || $z['sum_pay']) {
		$diff = abs($z['sum_dolg']) ? ($z['sum_dolg'] < 0 ? 'Недо' : 'Предо').'плата '.abs($z['sum_dolg']).' руб.' : 'Оплачено';
		$diffClass = $z['sum_accrual'] != $z['sum_pay'] ? ' diff' : '';
		$pay = '<div class="balans'.$diffClass.'">'.
				'<span class="acc'._tooltip('Начислено', -39).$z['sum_accrual'].'</span>/'.
				'<span class="pay'._tooltip($diff, -17, 'l').$z['sum_pay'].'</span>'.
			'</div>';
	}

	if(FIND && !$filter['client_id'] && $z['client_id'])
		$z['client_go'] = _findRegular($filter['find'], $z['client_go']);

	return
	'<div class="_zayav-unit'.$deleted.'"'.
		' id="u'.$z['id'].'"'.
		' val="'.$z['id'].'"'.
		$statusColor.
	'>'.
		'<table class="zu-main">'.
			'<tr><td class="zu-td1">'.

				'<div class="zd">'.
					'#'.$z['nomer'].
					'<h2>'.FullDataTime($z['dtime_add'], 1, 1).'</h2>'.
					$pay.
				'</div>'.
		
				'<a class="name">'.$z['name'].'</a>'.
		
				'<table class="tab">'.
		($z['dogovor_id'] ? '<tr><td class="label top">Договор:<td class="dog">'.$z['dogovor_line'] : '').
	(!$filter['client_id'] && $z['client_id'] ? '<tr><td class="label top">Клиент:<td>'.$z['client_go'] : '').
			_zayavUnit43($z).       //рубрика
			_zayavUnit44($z, $zpu). //текст
			_zayavUnit46($z, $zpu). //размер
			_zayavUnit49($z, $zpu). //количество
			_zayavUnit50($z, $zpu, $filter). //полоса
	 (FIND && $z['phone'] ? '<tr><td class="label">Телефон:<td>'.$z['phone'] : '').
			 ($z['adres'] ? '<tr><td class="label top">Адрес:<td>'.$z['adres'] : '').
	         ($z['schet'] ? '<tr><td class="label topi">Счета:<td>'.$z['schet'] : '').
				'</table>'.

				$statusName.

		(isset($zpu[42]) ?
			'<td class="image"'.$statusColor.'>'.
				'<span>'.$z['image_small'].'</span>'
		: '').

		'</table>'.

		'<div class="note">'.$z['note'].'</div>'.

	'</div>';
}
function _zayavUnit43($z) {//единица списка заявки: рубрика
	if(!$z['rubric_id'])
		return '';
	return
	'<tr><td class="label">Рубрика:<td>'._rubric($z['rubric_id']).
		($z['rubric_id_sub'] ? '<span class="ug">»</span>'._rubricSub($z['rubric_id_sub']) : '');
}
function _zayavUnit44($z, $zpu) {//единица списка заявки: текст
	if(!isset($zpu[44]))
		return '';
	if(empty($z['about']))
		return '';
	return '<tr><td class="label topi">'.$zpu[44]['label'].'<td><div class="about">'.$z['about'].'</div>';
}
function _zayavUnit46($z, $zpu) {//единица списка заявки: размер
	if(!isset($zpu[46]))
		return '';

	$size_x = round($z['size_x'], 1);
	$size_y = round($z['size_y'], 1);

	if(!$size_x || !$size_y)
		return '';

	return
	'<tr><td class="label">Размер:'.
		'<td>'.$size_x.' x '.$size_y.' = <b>'.round($size_x * $size_y).'</b> см&sup2;';
}
function _zayavUnit49($z, $zpu) {//единица списка заявки: количество
	if(!isset($zpu[49]))
		return '';
	if(!$z['count'])
		return '';
	return '<tr><td class="label">Количество:<td><b>'.$z['count'].'</b> шт.';
}
function _zayavUnit50($z, $zpu, $filter) {//единица списка заявки: полоса (газета)
	if(!isset($zpu[50]))
		return '';

	$gn = _gn($filter['gn_nomer_id']);
	return
		'<tr><td class="label">Полоса:'.
			'<td>'.
				'<b>'.$gn['week'].'</b> ('.$gn['general_nomer'].') '.
	($z['gn_polosa_id'] ?
				'<div class="polosa-rek'.($gn['lost'] ? ' lost' : '').'">'.
					$z['gn_polosa_name'].
		(_polosa($z['gn_polosa_id'], 'polosa') ?
					' <input type="hidden" '.
							'id="zayav-polosa-nomer'.$z['id'].'" '.
							'val="'.$z['gn_zgn_id'].'" '.
							'class="zayav-polosa-nomer" '.
							'value="'.$z['gn_polosa_nomer'].'" />'
		: '')
	: '').
				'</div>'.
				'<b>'.$z['gn_polosa_cena'].'</b> руб.';
}
function _zayavTovarName() {
	$sql = "SELECT DISTINCT `tovar_id`
			FROM `_zayav_tovar`
			WHERE `app_id`=".APP_ID;
	if(!$tovar_ids = query_ids($sql))
		return '[]';

	$sql = "SELECT DISTINCT `name_id`
			FROM `_tovar`
			WHERE `id` IN (".$tovar_ids.")";
	$name_ids = query_ids($sql);

	$sql = "SELECT `id`,`name`
			FROM `_tovar_name`
			WHERE `id` IN (".$name_ids.")";
	return query_selJson($sql);
}
function _zayavTovarIds($service_id) {
	$sql = "SELECT DISTINCT `zt`.`tovar_id`
			FROM `_zayav` `z`,
				 `_zayav_tovar` `zt`
			WHERE `z`.`app_id`=".APP_ID."
			  AND `z`.`id`=`zt`.`zayav_id`
			  AND !`z`.`deleted`
			  AND `z`.`service_id`=".$service_id;
	return query_ids($sql);
}
function _zayavObOnpay($v) {
	$sql = "SELECT COUNT(*)
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `onpay_checked`=2";
	if(!$count = query_value($sql))
		return '';

	return _check('ob_onpay', '{label} (<b>'.$count.'</b>)', $v['ob_onpay']);
}


/* Информация о заявке */
function _zayavQuery($zayav_id, $withDel=0) {
	if(!$zayav_id)
		return array();

	$sql = "SELECT *
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  ".($withDel ? '' : ' AND !`deleted`')."
			  AND `id`=".$zayav_id;
	if(!$z = query_assoc($sql))
		return array();

	$z['link'] = '<a href="'.URL.'&p=zayav&d=info&id='.$z['id'].'">'.$z['name'].'</a>';
	$z['go'] = '<a onclick="_zayavGo(\''.$z['id'].'\')">№'.$z['nomer'].': '.$z['name'].'</a>';
	$z['nomer_link'] = '<a href="'.URL.'&p=zayav&d=info&id='.$z['id'].'">№'.$z['nomer'].': '.$z['name'].'</a>';

	return $z;
}
function _zayavInfo() {
	if(!$zayav_id = _num(@$_GET['id']))
		return _err('Страницы не существует');

	if(!$z = _zayavQuery($zayav_id, 1))
		return _err('Заявки не существует.');

	if(RULE_ZAYAV_EXECUTER && $z['executer_id'] != VIEWER_ID)
		return _err('Нет доступа.');

	_service('current', $z['service_id']);//установка куки для возврата на соответствующий списов заявок

	if(!VIEWER_ADMIN && $z['deleted'])
		return _noauth('Заявка удалёна');

	$zpu = _zayavPole($z['service_id']);
	$z['zpu'] = $zpu;

	$z['sum_cost'] = _cena($z['sum_cost']);
	$z['sum_accrual'] = _cena($z['sum_accrual']);
	$z['sum_pay'] = _cena($z['sum_pay']);

	$history = _history(array('zayav_id'=>$zayav_id));

	return
	_attachJs(array('zayav_id'=>$zayav_id)).
	'<script>'.
		'var ZI={'.
				'id:'.$zayav_id.','.
				'service_id:'.$z['service_id'].','.
				'pole:'._zayavPole($z['service_id'], 0, 'js').','.
				'nomer:'.$z['nomer'].','.
				'client_id:'.$z['client_id'].','.
				'client_link:"'.addslashes(_clientVal($z['client_id'], 'link')).'",'.
				'name:"'.addslashes($z['name']).'",'.
				'about:"'.addslashes(str_replace("\n", '', $z['about'])).'",'.
				'size_x:'.$z['size_x'].','.
				'size_y:'.$z['size_y'].','.
				'count:'.$z['count'].','.
				'gns:'._zayavInfoGazetaNomerJS($zayav_id).','.
				'status_id:'.$z['status_id'].','.
				'status_day:"'.($z['status_day'] == '0000-00-00' ? '' : $z['status_day']).'",'.
				'executer_id:'.$z['executer_id'].','.
				'srok:"'.$z['srok'].'",'.
				'adres:"'.addslashes($z['adres']).'",'.
				'tovar_id:'._zayavTovarOneId($z).','.
				'tovar:"'._zayavTovarValue($zayav_id).'",'.
				'place_id:'.$z['tovar_place_id'].','.
				'equip:"'.$z['equip'].'",'.
				'imei:"'.addslashes($z['imei']).'",'.
				'serial:"'.addslashes($z['serial']).'",'.
				'color_id:'.$z['color_id'].','.
				'color_dop:'.$z['color_dop'].','.
				'sum_cost:'.$z['sum_cost'].','.
				'pay_type:'.$z['pay_type'].','.
				'todel:'._zayavToDel($zayav_id).','.//показывать пункт для удаления заявки
				'deleted:'.$z['deleted'].
			'},'.
			'DOG={'._zayavDogovorJs($z).'};'.
			'KVIT={'.
				'dtime:"'.FullDataTime($z['dtime_add']).'",'.
				'color:"'._color($z['color_id'], $z['color_dop']).'",'.
				'phone:"'._clientVal($z['client_id'], 'phone').'",'.
				'defect:"'.addslashes(str_replace("\n", ' ', _note(array('last'=>1)))).'"'.
			'};'.

	'</script>'.
	'<div id="_zayav-info">'.
		'<div id="dopLinks">'.
			'<a class="link a-page sel">Информация</a>'.
			'<a class="link" onclick="_zayavEdit('.$z['service_id'].')">Редактирование</a>'.
			'<a class="link" onclick="_accrualAdd()">Начислить</a>'.
			'<a class="link" onclick="_incomeAdd()">Принять платёж</a>'.
			'<a class="link a-page">История</a>'.
			'<div id="nz" class="'._tooltip('Номер заявки', -74, 'r').'#'.$z['nomer'].'</div>'.
		'</div>'.

		($z['deleted'] ? '<div id="zayav-deleted">Заявка удалена.</div>' : '').

		'<table class="page">'.
			'<tr><td id="left">'.

					'<div class="headName">'.
						_zayavInfoCategory($z).
						$z['name'].
						'<input type="hidden" id="zayav-action" />'.
					'</div>'.

					_zayavInfoPay($z).

	 ($z['about'] ? '<div class="_info">'._br($z['about']).'</div>' : '').

					'<table id="tab">'.
	 ($z['client_id'] ? '<tr><td class="label top">Клиент:<td>'._clientVal($z['client_id'], 'go') : '').

	                _zayavUnit46($z, $zpu). //размер
					_zayavUnit43($z).       //рубрика

				(isset($zpu[47]) && $z['count'] ?
			            '<tr><td class="label">Количество:<td><b>'.$z['count'].'</b> шт.'
				: '').

						_zayav_tovar_several($z).
		 ($z['phone'] ? '<tr><td class="label">'.(isset($zpu[37]) ? $zpu[37]['name'] : 'Тел.').':<td>'.$z['phone'] : '').
		 ($z['adres'] ? '<tr><td class="label">Адрес:<td>'.$z['adres'] : '').
	($z['dogovor_id'] ? '<tr><td class="label">Договор:<td>'._zayavDogovor($z) : '').
	  ($z['pay_type'] ? '<tr><td class="label">Расчёт:<td>'._payType($z['pay_type']) : '').

					(isset($zpu[10]) || $z['executer_id'] ?
						'<tr><td class="label r">Исполнитель:'.
							'<td id="executer_td"><input type="hidden" id="executer_id" value="'.$z['executer_id'].'" />'
					: '').

					(isset($zpu[13]) || $z['srok'] != '0000-00-00' ?
		                '<tr><td class="label">Срок:<td><input type="hidden" id="srok" value="'.$z['srok'].'" />'
					: '').

						_zayavStatusButton($z).

					(isset($zpu[22]) || $z['attach_id'] ?
						'<tr><td class="label">'._br(isset($zpu[22]) ? $zpu[22]['name'] : 'Документ').':'.
							'<td><input type="hidden" id="attach_id" value="'.$z['attach_id'].'" />'.
								 _zayavAttachCancel($z, 22)
					: '').

					(isset($zpu[34]) || $z['attach1_id'] ?
						'<tr><td class="label">'._br(isset($zpu[34]) ? $zpu[34]['name'] : 'Документ 1').':'.
							'<td><input type="hidden" id="attach1_id" value="'.$z['attach1_id'].'" />'.
								 _zayavAttachCancel($z, 34)
					: '').

						_zayavInfoGazetaNomer($z, $zpu).

					'</table>'.

					($z['onpay_checked'] == 2 ?
						'<div class="onpay _info">'.
							'Данное объявление будет публиковаться в печатном формате<br />только после проверки.'.
							'<br />'.
							'<br />'.
							'<button class="vk" onclick="_zayavOnpayPublic()">Разрешить публикацию</button>'.
							'&nbsp;&nbsp;&nbsp;&nbsp;'.
							'<button class="vk red" onclick="_zayavOnpayPublicNo()">Не разрешать</button>'.
						'</div>'
					: '').

					($z['onpay_checked'] == 3 ?
						'<div class="onpay _info">'.
							'Публикация объявленя была <b>запрещена</b>.'.
							'<br />'.
							'<br />'.
							'<button class="vk" onclick="_zayavOnpayPublic()">Разрешить публикацию</button>'.
						'</div>'
					: '').

					'<div id="added">'.
						'Заявку '._viewerAdded($z['viewer_id_add']).' '.
						FullDataTime($z['dtime_add']).
					'</div>'.

					_zayavInfoCartridge($z).
					_zayavKvit($zayav_id).
					_zayavInfoAccrual($zayav_id).
					_zayav_expense($zayav_id).
					_remind_zayav($zayav_id).
					_zayavInfoMoney($zayav_id).
					_note().

				'<td id="right">'.
 (isset($zpu[52]) ? _zayavImg($zayav_id) : '').
					_zayavInfoTovar($z).
		'</table>'.

		'<div class="page dn">'.
			'<div class="headName">'._zayavInfoCategory($z).$z['name'].' - история действий</div>'.
			$history['spisok'].
		'</div>'.

	'</div>';
}
function _zayavInfoCategory($z) {//отображение названия категории заявки
	if(!$z['service_id'])
		return '';

	return
		'<a id="category" onclick="_zayavTypeChange()">'.
			_service('name', $z['service_id']).
		'</a>'.
		'<br />';
}
function _zayavToDel($zayav_id) {//можно ли удалять заявку..
	if(!_zayavQuery($zayav_id))
		return 0;

	//проверка на наличие платежей
	$sql = "SELECT COUNT(`id`)
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	if(query_value($sql))
		return 0;

	//проверка на наличие возвратов
	$sql = "SELECT COUNT(`id`)
			FROM `_money_refund`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	if(query_value($sql))
		return 0;

	//проверка на наличие заключённых договоров
	$sql = "SELECT COUNT(`id`)
			FROM `_zayav_dogovor`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	if(query_value($sql))
		return 0;

	//проверка на наличие счетов на оплату
	$sql = "SELECT COUNT(`id`)
			FROM `_schet_pay`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	if(query_value($sql))
		return 0;

	//проверка на наличие начислений зп сотрудникам
	$sql = "SELECT COUNT(`id`)
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`
			  AND `zayav_id`=".$zayav_id;
	if(query_value($sql))
		return 0;

	//проверка на наличие номеров газеты, которые уже выходили
	$sql = "SELECT COUNT(`id`)
			FROM `_zayav_gazeta_nomer`
			WHERE `app_id`=".APP_ID."
			  AND `zayav_id`=".$zayav_id."
			  AND `gazeta_nomer_id`<"._gn('first');
	if(query_value($sql))
		return 0;

	return 1;
}
function _zayavInfoPay($z) {//блок информации о деньгах: стоимость, начисления, платежи
	if((!$z['sum_cost'] || !empty($z['zpu'][38])) && !$z['sum_accrual'] && !$z['sum_pay'])
		return '';

	//подсветка таблицы на основании долга заявки
	$class = '';
	if($z['sum_accrual'] || $z['sum_pay'])
		if(_cena(abs($z['sum_dolg'])))
			$class = ' class="nopaid"';
		else $class = ' class="paid"';

	//получение суммы скидки
	$sql = "SELECT SUM(`skidka_sum`)
			FROM `_zayav_gazeta_nomer`
			WHERE `zayav_id`=".$z['id'];
	$skidka_sum = round(query_value($sql), 2);

	return
	'<table id="pay-tab"'.$class.'>'.
		'<tr>'.

		($z['sum_cost'] && empty($z['zpu'][38]) ?
			'<td class="cost">'.
				'<div class="grey">Cтоимость</div>'.
				_sumSpace($z['sum_cost'])
		: '').

			'<td class="acc">'.
				'<div class="grey">Начислено</div>'.
				($z['sum_accrual'] ? '<b class="fs14">'._sumSpace($z['sum_accrual']).'</b>' : '').
				($skidka_sum ?
					'<div class="grey mt5">Скидка '.$z['skidka'].'%</div>'.
					'<div class="grey"><b>'._sumSpace($skidka_sum).'</b> руб.</div>'
				: '').

			'<td class="pay">'.
				'<div class="grey">Оплачено</div>'.
				($z['sum_pay'] ? '<b class="fs14">'._sumSpace($z['sum_pay']).'</b>' : '').

		(abs($z['sum_dolg']) ?
			'<td>'.
				'<div class="grey">'.($z['sum_dolg'] < 0 ? 'Не оплачено' : 'Предоплата').'</div>'.
				'<span class="'.($z['sum_dolg'] < 0 ? 'dolg' : 'pay').'">'._sumSpace(abs($z['sum_dolg'])).'</span>'
		: '').
	'</table>';
}
function _zayavInfoGazetaNomerJS($zayav_id) {
	$sql = "SELECT *
			FROM `_zayav_gazeta_nomer`
			WHERE `app_id`=".APP_ID."
			  AND `zayav_id`=".$zayav_id."
			  AND `gazeta_nomer_id`>="._gn('first')."
			ORDER BY `id`";
	if(!$spisok = query_arr($sql))
		return '{}';

	$send = array();
	foreach($spisok as $r)
		$send[] =
			$r['gazeta_nomer_id'].':'.
				'['.
					$r['dop'].','.
					$r['polosa'].','.
					$r['skidka'].','.
					round($r['cena'], 6).','.
					$r['id'].','.
					$r['schet_id'].
				']';

	return '{'.implode(',', $send).'}';
}
function _zayavInfoGazetaNomer($z, $zpu) {//номера выхода газеты
	if(!isset($zpu[48]))
		return '';

	$sql = "SELECT `z`.*
			FROM
				`_zayav_gazeta_nomer` `z`,
				`_setup_gazeta_nomer` `s`
			WHERE `s`.`id`=`z`.`gazeta_nomer_id`
			  AND `z`.`app_id`=".APP_ID."
			  AND `zayav_id`=".$z['id']."
			ORDER BY `s`.`general_nomer`";
	if(!$spisok = query_arr($sql))
		return '';

	$spisok = _schetPayValToList($spisok);

	//проверка, выставлялся ли счёт на оплату (для отображения колонки с номерами счетов)
	$schetOn = 0;
	foreach($spisok as $r)
		if($r['schet_id']) {
			$schetOn = 1;
			break;
		}

	//проверка, есть ли номера газет, по которым счёт на оплату не выславлялся (для отображения галочек)
	$schetOff = 0;
	foreach($spisok as $r)
		if(!$r['schet_id']) {
			$schetOff = 1;
			break;
		}

	define('SCHET_PAY_ON', $z['client_id'] && $schetOff && _app('schet_pay'));

	$send =
		'<table class="_spisokTab ml20 w500">'.
			'<tr><th class="w50">Номер'.
				'<th class="w70">Выход'.
				'<th class="w50">Цена'.
				($zpu[48]['v1'] ? '<th>Дополнительно' : '').
				($zpu[48]['v2'] ? '<th>Полоса' : '').
					  ($schetOn ? '<th class="w50">№ счёта' : '').
				  (SCHET_PAY_ON ? '<th class="w15">'._check('check_all') : '');
	$lost = 0;
	foreach($spisok as $r)
		if(_gn($r['gazeta_nomer_id'], 'lost'))
			$lost++;
	$send .= ($lost ?
		'<tr class="gn-lost center bg-eee over4 curP color-555" onclick="_zayavGNLostShow()">'.
			'<td colspan="10">Показать прошедшие выходы ('.$lost.')' : '');

	foreach($spisok as $r) {
		$gn = _gn($r['gazeta_nomer_id']);
		$send .=
			'<tr class="'.($gn['lost'] ? 'bg-gr1 dn grey' : 'bg-dfd').'">'.
				'<td class="r"><b>'.$gn['week'].'</b> <em class="grey">('.$gn['general_nomer'].')</em>'.
				'<td class="dtime r wsnw">'.$gn['pub'].
				'<td class="r">'._sumSpace($r['cena']).
				($zpu[48]['v1'] ? '<td class="color-555 fs12">'._obDop($r['dop']) : '').
				($zpu[48]['v2'] ?
					'<td class="color-555 fs12">'.($r['dop'] ? _polosa($r['dop']).($r['polosa'] ? ' '.$r['polosa'].'-я' : '') : '')
				: '').
		($schetOn ? '<td class="grey fs12 center">'.$r['schet_pay_nomer'] : '').
	(SCHET_PAY_ON ?	'<td class="ch">'.(!$r['schet_id'] ? _check('ch'.$r['id']) : '') : '');
	}
	$send .= '</table>';

	return
		'<script>CHECK_ALL_FUNC=_zayavGNLostShow;</script>'.
		'<tr><td colspan="2" class="label">'.
			'Номера выпуска:'.
			(SCHET_PAY_ON ? '<button class="vk small fr" onclick="_zayavSchetPayKupez($(this))">Сформировать счёт на оплату</button>' : '').
		'<tr><td colspan="2">'.$send;
}
function _zayavInfoGazetaNomerForSchetPay($ids) {//получение списка картриджей для вставления в счёт
	if(!$ids)
		return array();

	$sql = "SELECT *
			FROM `_zayav_gazeta_nomer`
			WHERE `id` IN (".$ids.")
			  AND !`schet_id`
			ORDER BY `id`";
	if(!$zgn = query_arr($sql))
		return array();


	//получение площади рекламного модуля из заявки
	$k = key($zgn);
	$zayav_id = $zgn[$k]['zayav_id'];
	$z = _zayavQuery($zayav_id);
	if(!$kvsm = round($z['size_x'] * $z['size_y']))
		$kvsm = 1;

	$zpu = _zayavPole($z['service_id']);

	$spisok = array();
	foreach($zgn as $r) {
		$gn = _gn($r['gazeta_nomer_id']);
		$spisok[] = array(
			'name' => utf8(_service('name', $z['service_id']).' в газете "Купец" номер '.$gn['week'].' ('.$gn['general_nomer'].') '.$gn['day_public_1']),
			'count' => $kvsm,
			'measure_id' => isset($zpu[31]) ? 6 : 1,
			'cena' => round($r['cena'] / $kvsm, 2),
			'summa' => round($r['cena'], 2),
			'readonly' => 1
		);
	}

	return $spisok;
}
function _zayavDogovor($z) {//отображение номера договора
	$sql = "SELECT *
			FROM `_zayav_dogovor`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$z['id'];
	if(!$r = query_assoc($sql))
		return '';

	$title = 'от '._dataDog($r['data_create']).' г. на сумму '._cena($r['sum']).' руб.';

	return '<b class="dogn'._tooltip($title, -7, 'l').'№'.$r['nomer'].'</b> '.
			'<a href="'.LINK_DOGOVOR.'/'.$r['link'].'.doc" class="img_word'._tooltip('Распечатать', -41).'</a>';
}
function _zayavDogovorJs($z) {
	if(!$z['dogovor_id']) {
		$c = _clientVal($z['client_id']);
		return
			'nomer_next:'._maxSql('_zayav_dogovor', 'nomer', 1).','.
			'fio:"'.addslashes($c['name']).'",'.
			'adres:"'.addslashes($c['adres']).'",'.
			'pasp_seria:"'.addslashes($c['pasp_seria']).'",'.
			'pasp_nomer:"'.addslashes($c['pasp_nomer']).'",'.
			'pasp_adres:"'.addslashes($c['pasp_adres']).'",'.
			'pasp_ovd:"'.addslashes($c['pasp_ovd']).'",'.
			'pasp_data:"'.addslashes($c['pasp_data']).'"';
	}

	$sql = "SELECT *
			FROM `_zayav_dogovor`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `id`=".$z['dogovor_id'];
	if(!$r = query_assoc($sql))
		return '';

	$sql = "SELECT `invoice_id`
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `dogovor_id`=".$r['id']."
			LIMIT 1";
	$invoice_id = query_value($sql);

	return
		'id:'.$r['id'].','.
		'template_id:'.$r['template_id'].','.
		'nomer:'.$r['nomer'].','.
		'fio:"'.addslashes($r['fio']).'",'.
		'adres:"'.addslashes($r['adres']).'",'.
		'pasp_seria:"'.addslashes($r['pasp_seria']).'",'.
		'pasp_nomer:"'.addslashes($r['pasp_nomer']).'",'.
		'pasp_adres:"'.addslashes($r['pasp_adres']).'",'.
		'pasp_ovd:"'.addslashes($r['pasp_ovd']).'",'.
		'pasp_data:"'.addslashes($r['pasp_data']).'",'.
		'data_create:"'.$r['data_create'].'",'.
		'sum:'._cena($r['sum']).','.
		'avans_hide:'.(TODAY == substr($r['dtime_add'], 0, 10) ? 0 : 1).','.
		'avans_invoice_id:'.$invoice_id.','.
		'avans_sum:'.($invoice_id ? _cena($r['avans']) : '""');
}
function _zayavDogovorFilter($v) {//проверка всех введённых данных по договору
	if(!_num($v['id']) && $v['id'] != 0)
		return 'Ошибка: некорректный идентификатор договора';
	if(!_num($v['zayav_id']))
		return 'Ошибка: неверный номер заявки';
	if(!_num($v['id']) && !_num($v['template_id']))
		return 'Ошибка: неверный id шаблона договора';
	if(!_num($v['nomer']))
		return 'Ошибка: некорректно указан номер договора';
	if(!preg_match(REGEXP_DATE, $v['data_create']))
		return 'Ошибка: некорректно указана дата заключения договора';
	if(!_cena($v['sum']))
		return 'Ошибка: некорректно указана сумма по договору';
//	if(!empty($v['avans']) && !_cena($v['avans']))
//		return 'Ошибка: некорректно указан авансовый платёж';
	$send = array(
		'id' => _num($v['id']),
		'zayav_id' => _num($v['zayav_id']),
		'template_id' => _num($v['template_id']),
		'nomer' => _num($v['nomer']),
		'fio' => trim($v['fio']),
		'adres' => trim($v['adres']),
		'sum' => _cena($v['sum']),
		'invoice_id' => _num($v['invoice_id']),
		'avans' => _cena($v['avans']),
		'data_create' => $v['data_create'],
		'link' => time().'_dogovor_'.intval($v['nomer']).'_'.$v['data_create'],
		'pasp_seria' => trim($v['pasp_seria']),
		'pasp_nomer' => trim($v['pasp_nomer']),
		'pasp_adres' => trim($v['pasp_adres']),
		'pasp_ovd' => trim($v['pasp_ovd']),
		'pasp_data' => trim($v['pasp_data'])
	);

	$sql = "SELECT COUNT(`id`)
			FROM `_zayav_dogovor`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `id`!=".$send['id']."
			  AND `nomer`=".$send['nomer'];
	if(query_value($sql))
		return 'Ошибка: договор с номером <b>'.$send['nomer'].'</b> уже был заключен';

	if(empty($send['fio']))
		return 'Ошибка: не указаны ФИО клиента';

//	if($send['sum'] < $send['avans'])
//		return 'Ошибка: авансовый платёж не может быть больше суммы договора';

	if($send['avans'] && !$send['invoice_id'])
		return 'Ошибка: не указан номер счёта для авансового платежа';

	$sql = "SELECT `client_id`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `id`=".$send['zayav_id'];
	if(!$send['client_id'] = query_value($sql))
		return 'Ошибка: заявки id='.$send['zayav_id'].' не существует, либо она была удалена';

	return $send;
}
function _zayavDogovorPrint($v) {
	require_once(GLOBAL_DIR.'/inc/clsMsDocGenerator.php');

	$income_id = 0;
	if(!is_array($v)) {
		$sql = "SELECT *
				FROM `_zayav_dogovor`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `id`=".$v;
		$v = query_assoc($sql);

		$v['save'] = 1; //сохранить договор

		if($v['avans']) {
			$sql = "SELECT `id`
					FROM `_money_income`
					WHERE `app_id`=".APP_ID."
					  AND !`deleted`
					  AND `dogovor_id`=".$v['id']."
					LIMIT 1";
			$income_id = query_value($sql);
		}
	}

	define('TEMPLATE_2', $v['template_id'] == 2);

	$ex = explode(' ', $v['fio']);
	$fioPodpis = $ex[0].' '.
				 (isset($ex[1]) ? ' '.$ex[1][0].'.' : '').
				 (isset($ex[2]) ? ' '.$ex[2][0].'.' : '');

	$doc = new clsMsDocGenerator(
		$pageOrientation = 'PORTRAIT',
		$pageType = 'A4',
		$cssFile = GLOBAL_DIR.'/css/dogovor.css',
		$topMargin = 1,
		$rightMargin = 2,
		$bottomMargin = 1,
		$leftMargin = 1
	);

	$v['sum'] = _cena($v['sum']);
	$v['avans'] = _cena($v['avans']);
	$dopl = $v['sum'] - $v['avans'];
	$dopl = $dopl < 0 ? 0 : $dopl;
	$adres = $v['pasp_adres'] ? $v['pasp_adres'] : $v['adres'];

	$doc->addParagraph(
	'<div class="head-name">'.
		'ДОГОВОР '.
		(TEMPLATE_2 ? 'НА ОКАЗАНИЕ УСЛУГ ' : '').
		'№'.$v['nomer'].
	'</div>'.
	'<table class="city_data"><tr><td>Город Няндома<th>'._dataDog($v['data_create']).'</table>'.
	'<div class="paragraph">'.
		'<p>Общество с ограниченной ответственностью «Территория Комфорта», '.
		'в лице менеджера по продажам, '._viewer(VIEWER_ID, 'viewer_name_full').', действующей на основании доверенности, '.
		'с одной стороны, и '.$v['fio'].($adres ? ', '.$adres : '').', именуемый в дальнейшем «Заказчик», с другой стороны, '.
		'заключили настоящий договор, далее «Договор», о нижеследующем:'.
	'</div>'.
	'<div class="p-head">1. Предмет договора</div>'.
	'<div class="paragraph">'.
		'<p>1.1. Поставщик принимает на себя обязательство по исполнению ЗАКАЗА на изготовление'.
				(TEMPLATE_2 ? ', доставку и монтаж ' : ' и доставку ').
				'изделий (оконных блоков, дверных блоков, защитных роллет, гаражных и промышленных ворот) в соответствии с индивидуальными характеристиками объекта и требованиями Заказчика (далее «Товар»). Работы по установке изделий и конструкций из них по адресу заказчика.'.
		'<p>1.2. Полная характеристика Заказа содержится в Спецификации, являющейся неотъемлемой частью настоящего договора.'.
	'</div>'.
	'<div class="p-head">2. Обязанности сторон</div>'.
	'<div class="paragraph">'.
		'<p>2.1. Поставщик обязуется исполнить заказ с соблюдением условий настоящего договора и требований, предъявляемых к продукции данного типа и указанных в ГОСТах №23166-99 «Блоки оконные ТУ», №30970-2002 «Блоки оконные из ПВХ» для оконных блоков, в рабочей документации разработчиков систем профилей для дверных блоков, в «Инструкции по изготовлению роллет», «Инструкции по изготовлению ворот», в ГОСТах №111-2001 «Стекло листовое», №24866-99 «Стеклопакеты клееные строительного назначения».'.
		'<p>2.2. Предварительный согласованный срок поставки товара и выполнения предусмотренных работ составляет 20 рабочих дней. Окончательный срок выполнения договора не более тридцати рабочих дней с момента поступления от Заказчика полной оплаты по договору и обеспечения Заказчиком условий пунктов 2.3. и 2.4. Данные сроки предусмотрены по стандартным изделиям. В случае заказа сложных и цветных изделий, срок договора увеличивается на количество дополнительных дней на изготовление сложной конструкции, указанное в Спецификации.'.
		'<p>2.3. Заказчик обязуется обеспечить доступ монтажников и подвод электропитания к оконным проемам, защиту личного имущества, напольных покрытий от пыли и повреждений, если договор предполагает выполнение работ по установке продукции по адресу заказчика. '.
		'Поставщик не отвечает за сохранность стеновых покрытий в зоне работ. Поставщик не несёт ответственности за нарушение элементов конструкций фасадов зданий при выполнении монтажных и отделочных работ, возникших в следствии ветхости строений и наличия скрытых строительных дефектов. Восстановительные работы проводятся по желанию и за счёт заказчика. В стоимость отделки откосов не входит герметизация наружного шва до 4 см шириной.'.
		'<p>2.4. Заказчик обязуется принять меры по обеспечению отсутствия автотранспорта на тротуаре под оконными проемами. В случае, если Заказчик не принял данные меры и монтаж осуществить невозможно, Заказчик оплачивает дополнительный выезд монтажной бригады из расчета 1000 руб./выезд, при этом окончательный срок выполнения договора составит 10 рабочих дней с момента обеспечения надлежащего доступа к месту установки.'.
		'<p>2.5. Все необходимые материалы доставляются Поставщиком на адрес Заказчика к моменту установки. В случае, отсутствия заказчика или его представителя на объекте в согласованный день поставки, повторная доставка оплачивается из расчёта 1000 руб./заказ, при этом окончательный срок выполнения договора составит 10 рабочих дней с момента обеспечения надлежащего доступа к месту установки.'.
		'<p>2.6. Поставщик обязуется собрать строительный мусор в мешки, если они присутствуют на объекте. Поставщик не несет ответственности за вывоз строительного мусора, образованного после выполнения работ по установке продукции. Заказчик обязуется осуществить вывоз и размещение мусора согласно действующим нормам. Поставщик обязуется вывезти строительный мусор на специализированную площадку (в соответствии с законом №239-29 от 29.05.2003), только в случае, если данная услуга была заказана Заказчиком и указана в Спецификации.'.
		'<p>2.7. Заказчик обязуется оплатить полную стоимость Заказа до начала установки изделий и конструкций из них в соответствии со Спецификацией.'.
		'<p>2.8. Право собственности на товар переходит к Заказчику в момент подписания им товаросопроводительных документов. В случае разногласия по количеству, комплектности и внешнему виду, суть разногласий отмечается в товаросопроводительных документах.'.
		'<p>2.9. Заказчик обязуется осуществить приёмку выполненных работ по монтажу изделий, по количеству, качеству, комплектности, внешнему виду и качеству отделки и заполнить две идентичные части «Приложения» к Акту сдачи – приёмки заказа и две идентичные части Акта сдачи - приёмки заказа. Отрывная часть №1 «Приложения» остаётся у заказчика, а часть №2 данного «Приложения» передается бригадиру установщиков. Акт приёма-передачи передаётся бригадиру мастеров по восстановлению откосов или бригадиру установщиков в случае, если отделка производится унифицированной бригадой. «Приложение» необходимо для оперативного обоснования претензии Заказчиком по качеству выполнения Поставщиком условий договора. В случае, если Заказчик отказывается подписывать «Акт сдачи-приемки заказа» и/или «Приложение», заказ считается автоматически выполненным.'.
		'<p>2.10. В случае подписания заказчиком товаросопроводительных документов или Акта выполненных работ с разногласиями, Поставщик обязуется рассмотреть данные разногласия в течение 5 дней, при этом срок выполнения договора автоматически продлевается на указанный срок.'.
		'<p>2.11. В случае согласия с претензией Заказчика Поставщик обязан заменить соответствующую часть товара или выполнить иные действия, предусмотренные настоящим договором для таких случаев в течение 14 рабочих дней, следующих за днем получения претензии Заказчика.'.
	'</div>'.
	'<div class="p-head">3. Цена товара и порядок расчетов</div>'.
	'<div class="paragraph">'.
		'<p>3.1. Полная стоимость заказа составляет: '.$v['sum'].' ('._numToWord($v['sum']).' рубл'._end($v['sum'], 'ь', 'я', 'ей').') указанные в спецификации, являются твердыми, и изменению без обоюдного согласия сторон не подлежат.'.
		($v['avans'] ?
			'<p>3.2. Оплата по настоящему договору осуществляется в следующем порядке:'.
			'<p>3.2.1. Авансовый платёж в размере '.$v['avans'].' ('._numToWord($v['avans']).' рубл'._end($v['avans'], 'ь', 'я', 'ей').') вносится Заказчиком в день заключения настоящего договора. В случае отсутствия работ по договору, авансовый платёж составляет 100% суммы договора.'.
			($dopl ?
				'<p>3.2.2. Доплата по договору, в сумме '.$dopl.' ('._numToWord($dopl).' рубл'._end($dopl, 'ь', 'я', 'ей').'), оплачивается в кассу до установки изделий: ______________________________________.'
			: '')
		: '').
	'</div>'.
	'<div class="p-head">4. Качество и гарантийные обязательства</div>'.
	'<div class="paragraph">'.
		'<p>4.1. Гарантийный срок на оконные блоки – три года, на монтажные и отделочные работы по оконным блокам – один год. Гарантийный срок на дверные блоки, роллетные системы и ворота - один год. На монтажные и отделочные работы по установке дверных блоков, роллетных систем и ворот – один год. Гарантийный срок действует с момента подписания сторонами отгрузочных документов (Акт сдачи – приемки заказа). Для климатических условий Северо-западного и Центрального региона России рекомендовано использовать двухкамерные стеклопакеты. Заказчик предупреждается, что при установке однокамерного стеклопакета, возможно образование конденсата и промерзание стеклопакета в зимний период при более высокой температуре окружающей среды, чем при установке двухкамерного стеклопакета. Заказчик предупреждён, что для исключения возможности выпадения конденсата и образования наледи на стеклопакетах, необходимо поддержание уровня температуры и влажности рекомендованного для жилого помещения.'.
		'<p>4.2. Поставщик обязуется заменить входящие в состав товара комплектующие за свой счёт, в случае выхода их из строя в течение Гарантийного срока. Срок выполнения гарантийных работ составляет не более 20 рабочих дней с момента поступления письменной претензии. Письменная претензия принимается в центральном офисе компании либо по почте.'.
		'<p>4.3. Гарантия не распространяется на случаи, когда товар (или его комплектующие) утратили свои качественные характеристики вследствие неправильной эксплуатации Товара, действий третьих лиц или в случае возникновения обстоятельств непреодолимой силы.'.
	'</div>'.
	'<div class="p-head">5. Ответственность сторон, форс-мажорные обстоятельства и ответственность сторон</div>'.
	'<div class="paragraph">'.
		'<p>5.1. Стороны освобождаются от ответственности за частичное или полное неисполнение обязательств по настоящему Договору, если это явилось следствием обстоятельств непреодолимой силы (форс-мажор), т.е. пожара, стихийных бедствий, войны, блокад, введение правительственных ограничений постфактум, объявления карантина и эпидемий. При этом срок исполнения обязательств по Договору продлевается на период действия указанных обстоятельств.'.
		'<p>5.2. За неисполнение или ненадлежащее исполнение обязательств стороны несут ответственность в соответствии с действующим законодательством Российской Федерации. В случае нарушения сроков выполнения договора поставщик выплачивает Заказчику неустойку в соответствии с Законом РФ "О защите прав потребителей" размере 3% в день от суммы недопоставленных комплектующих Заказа указанных в Спецификации и от суммы не оказанных услуг и работ, указанных в Спецификации.'.
	'</div>'.
	'<div class="p-head">6. Изменение условий договора и порядок разрешения споров</div>'.
	'<div class="paragraph">'.
		'<p>6.1. Все изменения и дополнения к настоящему договору действительны лишь в том случае, если они оформлены в письменном виде и подписаны обеими сторонами.'.
		'<p>6.2. Все споры и разногласия, которые могут возникнуть из настоящего договора будут по возможности разрешаться путём двусторонних переговоров.'.
		'<p>6.3. Споры, не получившие разрешения в результате переговоров, подлежат разрешению в соответствии с действующим законодательством РФ.'.
	'</div>'.
	'<div class="p-head">7. Срок действия договора</div>'.
	'<div class="paragraph">'.
		'<p>7.1. Настоящий договор вступает в силу с момента его подписания и действует до полного выполнения обязательств обеими сторонами.'.
	'</div>'.
	'<div class="p-head">8. Заключительные положения</div>'.
	'<div class="paragraph">'.
		'<p>8.1. Настоящий договор составлен в двух экземплярах по одному для каждой из сторон, имеющих равную юридическую силу.'.
	'</div>'.
	'<div class="p-head">9. Юридические адреса и банковские реквизиты сторон</div>'.
	'<table class="rekvisit">'.
		'<tr><td><b>Поставщик:</b><br />'.
				'ООО «'._app('name').'»<br />'.
				'ОГРН '._app('ogrn').'<br />'.
				'ИНН '._app('inn').'<br />'.
				'КПП '._app('kpp').'<br />'.
				str_replace("\n", '<br />', _app('adres_yur')).'<br />'.
				'Тел. '._app('phone').'<br /><br />'.
				'Адрес офиса: '._app('adres_ofice').
			'<td><b>Заказчик:</b><br />'.
				$v['fio'].'<br />'.
				'Паспорт серии '.$v['pasp_seria'].' '.$v['pasp_nomer'].'<br />'.
				'выдан '.$v['pasp_ovd'].' '.$v['pasp_data'].'<br /><br />'.
				$adres.
	'</table>'.
	'<div class="podpis-head">Подписи сторон:</div>'.
	'<table class="podpis">'.
		'<tr><td>Поставщик ________________ '._viewer(VIEWER_ID, 'viewer_name_init').
			'<td>Заказчик ________________ '.$fioPodpis.
	'</table>'.
	'<div class="mp">М.П.</div>');

	$doc->newPage();

	$doc->addParagraph(
	'<div class="ekz">Экземпляр заказчика</div>'.
	'<div class="act-head">АКТ сдачи-приёмки заказа</div>'.
	'<table class="act-tab">'.
		'<tr><td class="label">По адресу:<td class="title">'.$v['adres'].'<td><td>'.
		'<tr><td class="label">Заказ:<td class="title">'.$v['nomer'].'<td class="label">Заказчик:<td>'.$fioPodpis.
	'</table>'.
	'<div class="act-inf">Экземпляр Заказчика является основанием для направления претензии.</div>'.
	'<div class="act-p">'.
		'<p>1. Оконные блоки принял без замечаний, со следующими замечаниями (ненужное зачеркнуть) по количеству, качеству, комплектности и внешнему виду:'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
	'</div>'.
	'<div class="act-p">'.
		'<p>2. Выполненные работы принял без замечаний, со следующими замечаниями (ненужное зачеркнуть):'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
	'</div>'.
	'<div class="act-p">От заказчика ___________________________________</div>'.
	'<div class="act-p">От поставщика /Бригадир монтажников/ ____________________________________</div>'.
	'<div class="act-p">Дата _______________</div>'.
	'<div class="cut-line">отрезать</div>'.
	'<div class="ekz">Экземпляр бригадира монтажников</div>'.
	'<div class="act-head">АКТ сдачи-приёмки заказа</div>'.
	'<table class="act-tab">'.
		'<tr><td class="label">По адресу:<td class="title">'.$v['adres'].'<td><td>'.
		'<tr><td class="label">Заказ:<td class="title">'.$v['nomer'].'<td class="label">Заказчик:<td>'.$fioPodpis.
	'</table>'.
	'<div class="time-dost">Время доставки _____________________</div>'.
	'<div class="act-p">'.
		'<p>1. Оконные блоки принял без замечаний, со следующими замечаниями (ненужное зачеркнуть) по количеству, качеству, комплектности и внешнему виду:'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
	'</div>'.
	'<div class="act-p">'.
		'<p>2. Выполненные работы принял без замечаний, со следующими замечаниями (ненужное зачеркнуть):'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
	'</div>'.
	'<div class="act-p">От заказчика ___________________________________</div>'.
	'<div class="act-p">От поставщика /Бригадир монтажников/ ____________________________________</div>'.
	'<div class="act-p">Дата _______________</div>'
	);

	if($income_id) {
		$doc->newPage();
		$doc->addParagraph(_incomeReceipt($income_id));
	}

	if(!is_dir(PATH_DOGOVOR))
		mkdir(PATH_DOGOVOR, 0777, true);

	$doc->output($v['link'], @$v['save'] ? PATH_DOGOVOR : '');
}
function _zayavBalansUpdate($zayav_id) {//Обновление баланса заявки
	if(!$zayav_id)
		return;

	//начисления
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_accrual`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	$accrual = query_value($sql);

	//номера газет
	$sql = "SELECT IFNULL(SUM(`cena`),0)
			FROM `_zayav_gazeta_nomer`
			WHERE `app_id`=".APP_ID."
			  AND !`schet_id`
			  AND `zayav_id`=".$zayav_id;
	$accrual += query_value($sql);

	//платежи
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `confirm` NOT IN (1,3)
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	$income = query_value($sql);

	//возвраты
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_refund`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	$refund = query_value($sql);

	$income -= $refund;

	//наличие счетов
	$sql = "SELECT COUNT(`id`)
			FROM `_schet_pay`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `type_id`!=2
			  AND `zayav_id`=".$zayav_id;
	$schet_count = query_value($sql);

	//расходы
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `zayav_id`=".$zayav_id;
	$expense = query_value($sql);

	$sql = "UPDATE `_zayav`
			SET `sum_accrual`=".$accrual.",
				`sum_pay`=".$income.",
				`sum_dolg`=`sum_pay`-`sum_accrual`,
				`sum_expense`=".$expense.",
				`sum_profit`=`sum_accrual`-`sum_expense`,
				`schet_count`=".$schet_count."
			WHERE `id`=".$zayav_id;
	query($sql);
}
function _zayavExecuterJs() {//список сотрудников, которые могут быть исполнителями
	$sql = "SELECT `viewer_id`
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `worker`";
	$ids = query_ids($sql);

	$sql = "SELECT `viewer_id`,1
			FROM `_vkuser_rule`
			WHERE `app_id`=".APP_ID."
			  AND `key`='RULE_EXECUTER'
			  AND `value`
			  AND `viewer_id` IN (".$ids.")";
	return query_assJson($sql);
}
function _zayavTovarOneId($z) {//получение id товара, если используется один товар
	if(!isset($z['zpu'][4]))
		return 0;

	$sql = "SELECT `tovar_id`
			FROM `_zayav_tovar`
			WHERE `zayav_id`=".$z['id']."
			LIMIT 1";
	return query_value($sql);
}
function _zayavTovarValue($zayav_id) {//получение значения товаров для js в формате: tovar_id:count,4345:1
	if(!$zayav_id)
		return '';

	$sql = "SELECT *
			FROM `_zayav_tovar`
			WHERE `zayav_id`=".$zayav_id;
	if(!$arr = query_arr($sql))
		return '';

	$send = array();
	foreach($arr as $r)
		$send[] = $r['tovar_id'].':'.$r['count'];

	return implode(',', $send);
}
function _zayavInfoTovar($z) {//информация о товаре
	if(!isset($z['zpu'][4]))
		return '';

	$sql = "SELECT `tovar_id`
			FROM `_zayav_tovar`
			WHERE `zayav_id`=".$z['id']."
			LIMIT 1";
	if(!$tovar_id = query_value($sql))
		return '';

	if(!$tovar = _tovarQuery($tovar_id))
		return '';

	return
	_zayavImg($z['id'], $tovar_id).
	'<div id="zayav-tovar">'.
		'<div class="headBlue">Информация о товаре</div>'.

		'<div id="content">'.
			'<div id="tovar-name">'.
				_tovarName($tovar['name_id']).
				'<br />'.
				'<a href="'.URL.'&p=tovar&d=info&id='.$tovar['id'].'">'._tovarVendor($tovar['vendor_id']).$tovar['name'].'</a>'.
			'</div>'.
			'<table id="info">'.
	($z['imei'] ? '<tr><th>imei:	<td>'.$z['imei'] : '').
  ($z['serial'] ? '<tr><th>serial:	<td>'.$z['serial'] : '').
   ($z['equip'] ? '<tr><th valign="top">Комплект:<td>'._tovarEquip('spisok', $z['equip']) : '').
($z['color_id'] ? '<tr><th>Цвет:	<td>'._color($z['color_id'], $z['color_dop']) : '').
(isset($z['zpu'][12]) ? '<tr><th>Нахождение:<td><a id="zayav-tovar-place-change">'._zayavTovarPlace($z['tovar_place_id']).'</a>' : '').
			'</table>'.
		'</div>'.
		_zayavInfoTovarSet($z['id'], $tovar_id).
	'</div>';
}
function _zayavInfoTovarSet($zayav_id, $tovar_id) {//список запчастей для товара заявки
	$sql = "SELECT
				*,
				0 `zakaz`
			FROM `_tovar`
			WHERE `tovar_id_set`=".$tovar_id."
			  AND !`deleted`";
	if(!$arr = query_arr($sql))
		return '';

	$arr = _tovarValToList($arr, 'id');

	$sql = "SELECT *
			FROM `_tovar_zakaz`
			WHERE `zayav_id`=".$zayav_id;
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$arr[$r['tovar_id']]['zakaz'] = $r['id'];


	$spisok = '';
	foreach($arr as $r)
		$spisok .=
			'<div class="unit pad5 over2" val="'.$r['id'].'">'.
//				'<div class="image"><div>'.$r['image_small'].'</div></div>'.
				$r['tovar_zayav'].
//				($r['version'] ? '<div class="version">'.$r['version'].'</div>' : '').
//				($r['color_id'] ? '<div class="color">Цвет: '._color($r['color_id']).'</div>' : '').
				'<div class="mt5">'.
					'<div class="dib pad2-7 color-ref bg-del fs12'.(!$r['zakaz'] ? ' dn' : '').'" val="'.$r['zakaz'].'">'.
						'<div onclick="_zayavTovarZakazRemove($(this))" class="img_minidel fr'._tooltip('Удалить из заказа', -57).'</div>'.
						'Добавлено в заказ'.
					'</div>'.
					'<a class="zakaz-add color-ref fs12'.($r['zakaz'] ? ' dn' : '').'" onclick="_zayavTovarZakazAdd($(this),'.$r['id'].')">Добавить в заказ</a>'.
					'&nbsp;'.
					($r['tovar_avai_count'] ? '<a class="zayav-tovar-avai fr color-pay fs12">Нал: <b class="fs12">'.$r['tovar_avai_count'].'</b></a>' : '').
				'</div>'.
			'</div>'.
			'<div class="line-b mar0-5"></div>';



	return
	'<div class="headBlue">'.
		'Список запчастей'.
//		'<a class="add">добавить</a>'.
	'</div>'.
	'<div id="zayav-tovar-spisok">'.$spisok.'</div>';
}
function _zayavImg($zayav_id, $tovar_id=0) {
	$sql = "SELECT *
			FROM `_image`
			WHERE !`deleted`
			  AND !`sort`
			  AND `unit_name`='zayav'
			  AND `unit_id`=".$zayav_id."
			LIMIT 1";
	if(!$r = query_assoc($sql)) {
		$sql = "SELECT *
				FROM `_image`
				WHERE !`deleted`
				  AND !`sort`
				  AND `unit_name`='tovar'
				  AND `unit_id`=".$tovar_id."
				LIMIT 1";
		$r = query_assoc($sql);
	}

	if(empty($r))
		return _imageNoFoto('zayav', $zayav_id);

	$size = _imageResize($r['big_x'], $r['big_y'], 200, 320);
	return
	'<div>'.
		'<img class="_iview" '.
			'val="'.$r['id'].'" '.
			'width="'.$size['x'].'" '.
			'height="'.$size['y'].'" '.
			'src="'.$r['path'].$r['big_name'].'" '.
		'/>'.
		_imageBut200('zayav', $zayav_id).
	'</div>';
}
function _zayavKvit($zayav_id) {
	$sql = "SELECT *
			FROM `_zayav_kvit`
			WHERE `app_id`=".APP_ID."
			  AND `active`
			  AND `zayav_id`=".$zayav_id."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return '';

	$send = '<div class="headBlue">Квитанции</div>'.
			'<table class="_spisok _money">';
	$n = 1;
	foreach($arr as $r)
		$send .=
			'<tr><td><a onclick="_zayavKvitHtml('.$r['id'].')">Квитанция '.($n++).'</a>. '.
					'<span class="kvit_defect">'.$r['defect'].'</span>'.
				'<td class="dtime">'._dtimeAdd($r);
	$send .= '</table>';

	return $send;
}
function _zayavAttachCancel($z, $i) {//вывод сообщения о необязательном прикреплении документа
	$v = $i == 22 ? '' : 1;

	if($z['attach'.$v.'_id'])
		return '';

	if(!@$z['zpu'][$i]['v1'])
		return '';

	if($z['attach'.$v.'_cancel']) {
		$viewer_id = $z['attach'.$v.'_cancel_viewer_id'];
		$val = 'Отметил'.(_viewer($viewer_id, 'viewer_sex') == 1 ? 'а' : '').' '.
				_viewer($viewer_id, 'viewer_name').
				'<br />'.
				FullDataTime($z['attach'.$v.'_cancel_dtime']);
		return
			'<span class="attach-canceled" val="'.$val.'">'.
				'не требуется'.
			'</span>';
	}

	return '<div id="attach'.$v.'_cancel" class="img_cancel'._tooltip('Прикрепление не требуется', -12, 'l').'</div>';
}


/* Местонахождение товара в заявке */
function _zayavTovarPlace($place_id=false, $type=APP_TYPE) {
	$arr = array(
		1 => _appType($type, 7),
		2 => 'у клиента'
	);

	$sql = "SELECT `id`,`place`
			FROM `_zayav_tovar_place`
			WHERE `app_id`=".APP_ID."
			ORDER BY `place`";
	$arr += query_ass($sql);

	if($place_id === false)
		return $arr;

	return isset($arr[$place_id]) ? $arr[$place_id] : '';
}
function _zayavTovarPlaceUpdate($zayav_id, $place_id, $place_name) {// Обновление местонахождения заявки
	// - внесение нового местонахождения, если place_id = 0
	// - обновление place_id, если отличается от текущего в заявке
	
	if(!$place_id && empty($place_name))
		return false;

	$z = _zayavQuery($zayav_id);
	$placeNew = 0;

	if(!$place_id && !empty($place_name)) {
		$sql = "SELECT `id`
				FROM `_zayav_tovar_place`
				WHERE `app_id`=".APP_ID."
				  AND `place`='".$place_name."'
				LIMIT 1";
		if(!$place_id = query_value($sql)) {
			$sql = "INSERT INTO `_zayav_tovar_place` (
						`app_id`,
						`place`
					) VALUES (
						".APP_ID.",
						'".addslashes($place_name)."'
					)";
			query($sql);
			$place_id = query_insert_id('_zayav_tovar_place');
			$placeNew++;
		}
	}
	
	if($place_id != $z['tovar_place_id']) {
		$sql = "UPDATE `_zayav`
				SET `tovar_place_id`=".$place_id.",
					`tovar_place_dtime`=CURRENT_TIMESTAMP
				WHERE `id`=".$zayav_id;
		query($sql);

		if($z['tovar_place_id']) { //история вносится, если заявка изменяется
			_history(array(
				'type_id' => 29,
				'client_id' => $z['client_id'],
				'zayav_id' => $zayav_id,
				'v1' => '<table>'._historyChange('', _zayavTovarPlace($z['tovar_place_id']), _zayavTovarPlace($place_id)).'</table>'
			));

			if($place_id == 2)
				_note(array(
					'add' => 1,
					'comment' => 1,
					'p' => 'zayav',
					'id' => $zayav_id,
					'txt' => 'Передано клиенту.'
				));

			//удаление пустых местонахождений
			$sql = "SELECT DISTINCT `tovar_place_id` FROM `_zayav` WHERE `tovar_place_id`";
			if($ids = query_ids($sql)) {
				$sql = "DELETE FROM `_zayav_tovar_place` WHERE `id` NOT IN (".$ids.")";
				query($sql);
			}

			$placeNew += mysql_affected_rows();
		}

		if($placeNew)
			_appJsValues();
	}
	return true;
}


function _zayav_tovar_several($z) {//список нескольких товаров для информации о заявке
	if(empty($z['zpu'][11]))
		return '';

	$sql = "SELECT *
			FROM `_zayav_tovar`
			WHERE `zayav_id`=".$z['id']."
			ORDER BY `id`";
	if(!$arr = query_arr($sql))
		return '';

	$arr =  _tovarValToList($arr);

	$send = '<table id="tsev">';
	$n = 1;
	foreach($arr as $r)
		$send .=
			'<tr><td class="n r">'.($n++).
				'<td>'.$r['tovar_set'].
				'<td class="r">'.$r['count'].' '.$r['tovar_measure_name'];

	$send .= '</table>';

	return 	'<tr><td class="label topi">'.$z['zpu'][11]['name'].':<td>'.$send;
}
function _zayavTovarValToList($arr) {//список нескольких товаров для информации о заявке
	$sql = "SELECT *
			FROM `_zayav_tovar`
			WHERE `zayav_id` IN ("._idsGet($arr).")
			ORDER BY `id`";
	if(!$spisok = query_arr($sql))
		return $arr;

	foreach($arr as $r)
		$arr[$r['id']]['tovar_report'] = array();
	
	$spisok =  _tovarValToList($spisok);

	foreach($spisok as $r)
		$arr[$r['zayav_id']]['tovar_report'][] = $r['tovar_name'].': '.$r['count'].' '.$r['tovar_measure_name'];

	foreach($arr as $r)
		$arr[$r['id']]['tovar_report'] = implode("\n", $r['tovar_report']);

	return $arr;
}



/* Картриджи */
function _cartridgeName($item_id) {
	if(!defined('CARTRIDGE_NAME_LOADED')) {
		$key = CACHE_PREFIX.'cartridge';
		$arr = xcache_get($key);
		if(empty($arr)) {
			$sql = "SELECT `id`,`name` FROM `_setup_cartridge`";
			$arr = query_ass($sql);
			xcache_set($key, $arr, 86400);
		}
		foreach($arr as $id => $name)
			define('CARTRIDGE_NAME_'.$id, $name);
		define('CARTRIDGE_NAME_LOADED', true);
	}
	return constant('CARTRIDGE_NAME_'.$item_id);
}
function _cartridgeType($type_id=0) {
	$arr = array(
		1 => 'Лазерные',
		2 => 'Струйные'
	);
	return $type_id ? $arr[$type_id] : $arr;
}

function _zayavInfoCartridge($z) {
	if(!isset($z['zpu'][23]))
		return '';
	return
	'<div id="zayav-cartridge">'.
		'<div class="headBlue but">'.
			'Список картриджей'.
			'<button class="vk small" onclick="_zayavCartridgeAdd()">Добавить картриджи</button>'.
			'<button class="vk small" onclick="_zayavCartridgeSchetPay($(this))">Сформировать счёт на оплату</button>'.
		'</div>'.
		'<div id="zc-spisok">'._zayavInfoCartridge_spisok($z['id']).'</div>'.
	'</div>';
}
function _zayavInfoCartridge_spisok($zayav_id) {//список картриджей в инфо по заявке
	$sql = "SELECT *
 			FROM `_zayav_cartridge`
 			WHERE `zayav_id`=".$zayav_id."
 			ORDER BY `id`";
	$spisok = query_arr($sql);

	$spisok = _schetPayValToList($spisok);

	$send = '<table class="_spisokTab">'.
		'<tr>'.
			'<th class="w15">'.
			'<th class="w175">Наименование'.
			'<th class="w50">Стоимость'.
			'<th class="w100">Дата вып.'.
			'<th>Информация'.
			'<th class="w35">'.
			'<th class="w15">'._check('check_all');

	$n = 1;
	foreach($spisok as $r) {
		$prim = array();
		if($r['filling'])
			$prim[] = 'заправлен';
		if($r['restore'])
			$prim[] = 'восстановлен';
		if($r['chip'])
			$prim[] = 'заменён чип';
		$prim = !empty($prim) ? implode(', ', $prim) : '';
		$prim .= ($prim && $r['prim'] ? ', ' : '').'<u>'.$r['prim'].'</u>';

		$ready = $r['filling'] || $r['restore'] || $r['chip'];

		$send .=
			'<tr val="'.$r['id'].'"'.($ready ? ' class="bg-dfd"' : '').'>'.
				'<td class="r grey">'.($n++).
				'<td class="b">'._cartridgeName($r['cartridge_id']).
				'<td class="r">'.(_cena($r['cost']) || $ready ? _cena($r['cost']) : '').
				'<td class="r fs11 grey">'.($r['dtime_ready'] != '0000-00-00 00:00:00' ? FullDataTime($r['dtime_ready'], 1) : '').
				'<td>'.$prim.
				'<td>'.
					($r['schet_id'] ?
						'<div class="grey fs12">'.$r['schet_pay_nomer'].'</div>'
						:
						'<div class="img_edit cart-edit'._tooltip('Изменить', -33).'</div>'.
						'<div class="img_del cart-del'._tooltip('Удалить', -29).'</div>'.
						'<input type="hidden" class="cart_id" value="'.$r['cartridge_id'].'" />'.
						'<input type="hidden" class="filling" value="'.$r['filling'].'" />'.
						'<input type="hidden" class="restore" value="'.$r['restore'].'" />'.
						'<input type="hidden" class="chip" value="'.$r['chip'].'" />'
					).
				'<td class="ch">'.($ready && !$r['schet_id'] ? _check('ch'.$r['id']) : '');

	}

	$send .= '</table>';

	return $send;
}
function _zayavInfoCartridgeForSchetPay($ids) {//получение списка картриджей для вставления в счёт
	if(!$ids)
		return array();

	$sql = "SELECT *
			FROM `_zayav_cartridge`
			WHERE `id` IN (".$ids.")
			  AND (`filling` OR `restore` OR `chip`)
			  AND `cost`
			  AND !`schet_id`
			ORDER BY `id`";
	$q = query($sql);
	$schet = array();
	$n = 1;
	while($r = mysql_fetch_assoc($q)) {
		$same = 0;//тут будет номер, с которым будет найдено совпадение
		foreach($schet as $sn => $unit) {
			$diff = 0; // пока различий не обнаружено
			foreach($unit as $key => $val) {
				if($key == 'count')
					continue;
				if($r[$key] != $val) {
					$diff = 1;
					break;
				}
			}
			if(!$diff) { //если различий нет, то запоминание номера и выход
				$same = $sn;
				break;
			}
		}

		if($same)
			$schet[$same]['count']++;
		else {
			$schet[$n] = array(
				'cartridge_id' => $r['cartridge_id'],
				'filling' => $r['filling'],
				'restore' => $r['restore'],
				'chip' => $r['chip'],
				'cost' => $r['cost'],
				'prim' => $r['prim'],
				'count' => 1
			);
			$n++;
		}
	}

	$spisok = array();
	foreach($schet as $r) {
		$prim = array();
		if($r['filling'])
			$prim[] = 'заправка';
		if($r['restore'])
			$prim[] = 'восстановление';
		if($r['chip'])
			$prim[] = 'замена чипа у';

		$txt = implode(', ', $prim).' картриджа '._cartridgeName($r['cartridge_id']).($r['prim'] ? ', '.$r['prim'] : '');
		$txt = mb_ucfirst($txt);

		$spisok[] = array(
			'name' => utf8($txt),
			'count' => $r['count'],
			'cena' => _cena($r['cost']),
			'summa' => _cena($r['count'] * $r['cost']),
			'readonly' => 1
		);
	}
	return $spisok;
}






/* Календарь заявок */
function _zayavSrokCalendar($v=array()) {
	// переменные:
	//      day
	//      mon
	//      zayav_spisok

	//флаг: выбран день или нет
	define('SROK_NOSEL', empty($v['day']) || $v['day'] == '0000-00-00' || !preg_match(REGEXP_DATE, $v['day']));

	//дата выбранного дня в формате 2016-04-21
	define('SROK_DAY', SROK_NOSEL ? '0000-00-00' : $v['day']);

	//отображаемый месяц. Если выбран день, то показывается месяц, в котором этот день
	$mon = SROK_NOSEL ? strftime('%Y-%m') : substr(SROK_DAY, 0, 7);
	define('SROK_MON', empty($v['mon']) ? $mon : $v['mon']);

	//запрос календаря из списка заявок.
	define('SROK_ZS', empty($v['zayav_spisok']) ? 0 : 1);

	$service_id = _num(@$v['service_id']);
	$executer_id = _num(@$v['executer_id']);

	$day = SROK_MON.'-01';
	$ex = explode('-', $day);
	$SHOW_YEAR = $ex[0];
	$SHOW_MON = $ex[1];

	$back = $SHOW_MON - 1;
	$back = !$back ? ($SHOW_YEAR - 1).'-12' : $SHOW_YEAR.'-'.($back < 10 ? 0 : '').$back;
	$next = $SHOW_MON + 1;
	$next = $next > 12 ? ($SHOW_YEAR + 1).'-01' : $SHOW_YEAR.'-'.($next < 10 ? 0 : '').$next;

	$sql = "SELECT COUNT(`id`)
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `service_id`=".$service_id."
			  AND !`deleted`
			  AND `status_id` IN ("._zayavStatus('srok_ids').")
			  AND `srok`!='0000-00-00'
			  AND `srok`<'".$day."'
			  ".($executer_id ? " AND `executer_id`=".$executer_id : '');
	$countBack = query_value($sql);

	$sql = "SELECT COUNT(`id`)
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `service_id`=".$service_id."
			  AND !`deleted`
			  AND `status_id` IN ("._zayavStatus('srok_ids').")
			  AND `srok`!='0000-00-00'
			  AND `srok`>'".SROK_MON."-31'
			  ".($executer_id ? " AND `executer_id`=".$executer_id : '');
	$countNext = query_value($sql);

	$send =
		'<div id="zayav-srok-calendar">'.
			'<table class="filter bs10">'.
				'<tr><td class="label">Исполнитель:'.
					'<td><input type=hidden id="fc-executer_id" value="'.$executer_id.'" />'.
			'</table>'.
			'<table id="fc-head">'.
				'<tr><td class="ch" val="'.$back.'">&laquo;'.
						($countBack ? '<tt>'.$countBack.'</tt>' : '').
					'<td><span>'._monthDef($SHOW_MON).' '.$SHOW_YEAR.'</span> '.
					'<td class="ch r" val="'.$next.'">'.
						($countNext ? '<tt>'.$countNext.'</tt>' : '').
						'&raquo;'.
			'</table>'.
			'<table id="fc-mon">'.
				'<tr id="week-name">'.
					'<td>пн<td>вт<td>ср<td>чт<td>пт<td>сб<td>вс';

	$sql = "SELECT
				DATE_FORMAT(`srok`,'%Y-%m-%d') AS `day`,
				COUNT(`id`) AS `count`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `service_id`=".$service_id."
			  AND !`deleted`
			  AND `status_id` IN ("._zayavStatus('srok_ids').")
			  AND `srok` LIKE ('".SROK_MON."%')
			  ".($executer_id ? " AND `executer_id`=".$executer_id : '')."
			GROUP BY DATE_FORMAT(`srok`,'%d')";
	$q = query($sql);
	$days = array();
	while($r = mysql_fetch_assoc($q))
		$days[$r['day']] = $r['count'];

	$unix = strtotime($day);
	$dayCount = date('t', $unix);   // Количество дней в месяце
	$week = date('w', $unix);       // Номер первого дня недели
	if(!$week)
		$week = 7;

	$send .= '<tr>'.($week - 1 ? '<td colspan="'.($week - 1).'">' : '');

	for($n = 1; $n <= $dayCount; $n++) {
		$day = SROK_MON.'-'.($n < 10 ? '0' : '').$n;
		$cur = TODAY == $day ? ' cur' : '';
		$sel = SROK_DAY == $day ? ' sel' : '';
		$old = $unix + $n * 86400 <= TODAY_UNIXTIME ? ' old' : '';
		$val = $old ? '' : ' val="'.$day.'"';
		$send .=
			'<td class="d '.$cur.$old.$sel.'"'.$val.'>'.
				($cur ? '<u>'.$n.'</u>' : $n).
				(isset($days[$day]) ? ': <b'.($old && SROK_ZS ? ' class="fc-old-sel" val="'.$day.'"' : '').'>'.$days[$day].'</b>' : '');
		$week++;
		if($week > 7)
			$week = 1;
		if($week == 1 && $n < $dayCount)
			$send .= '<tr>';
	}
	$send .= '</table>'.
			(SROK_ZS && !SROK_NOSEL ? '<div id="fc-cancel" val="0000-00-00">День не указан</div>' : '').
		'</div>';

	return $send;
}






/* --- Расходы по заявке --- */
function _zayavExpense($id='all', $i='name') {//категории расходов заявки из кеша
	$key = CACHE_PREFIX.'zayav_expense';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT *
				FROM `_zayav_expense_category`
				WHERE `app_id`=".APP_ID."
				ORDER BY `sort`";
		$arr = query_arr($sql);
		xcache_set($key, $arr, 86400);
	}

	if($i == 'sort')//ассоциативный массив порядка категорий
		return array_keys($arr);

	//все категории
	if($id == 'all')
		return $arr;

	//список JS для select
	if($id == 'js') {
		$spisok = array();
		foreach($arr as $r)
			$spisok[$r['id']] = $r['name'];
		return _selJson($spisok);
	}

	//ассоциативный список дополнительных параметров
	if($id == 'dop_ass') {
		$spisok = array();
		foreach($arr as $r)
			$spisok[$r['id']] = $r['dop'];
		return _assJson($spisok);
	}

	//проверка: используется ли ведение прикреплённых счетов в расходе по заявке
	if($id == 'attach_schet') {
		foreach($arr as $r) {
			if($r['dop'] != 4)
				continue;
			if($r['param'])
				return 1;
		}
		return 0;
	}

	//список id категорий, в которых используется введение прикреплённых счетов
	if($id == 'attach_schet_ids') {
		$ids = array();
		foreach($arr as $r) {
			if($r['dop'] != 4)
				continue;
			if($r['param'])
				$ids[] = $r['id'];
		}
		if(empty($ids))
			return 0;
		return implode(',', $ids);
	}

	//неизвестный id
	if(!isset($arr[$id]))
		return _cacheErr('неизвестный id расхода по заявке', $id);

	//возврат данных конкретной категории
	if($i == 'all')
		return $arr[$id];

	//неизвестный ключ
	if(!isset($arr[$id][$i]))
		return _cacheErr('неизвестный ключ расхода по заявке', $i);

	return $arr[$id][$i];
}
function _zayavExpenseDop($id=false) {//дополнительное условие для категории расхода по заявке
	$arr =  array(
		0 => 'нет',
		1 => 'Описание',
		2 => 'Сотрудник',
		5 => 'Товар',
		3 => 'Товар <b>наличие</b>',
		4 => 'Файл'
	);
	return $id !== false ? $arr[$id] : $arr;
}
function _zayavExpenseDopVal($r) {//значение дополнительного поля
	if($r['worker_id'])
		return '<a class="go-report-salary" val="'.$r['worker_id'].':'.$r['year'].':'.$r['mon'].':'.$r['id'].'">'.
					_viewer($r['worker_id'], 'viewer_name').
				'</a>';

	if($r['tovar_id']) {
		$arr = _tovarValToList(array($r['id']=>$r));
		$r = $arr[$r['id']];
		$count = _ms($r['tovar_count']);
		return $r['tovar_set'].($r['tovar_avai_id'] ? '<td><b>'.$count.'</b>' : ': '.$count).' '.$r['tovar_measure_name'];
	}

	if($r['attach_id']) {
		$arr = _attachValToList(array($r['id']=>$r));
		return $arr[$r['id']]['attach_link'];
	}

	return $r['txt'];
}
function _zayav_expense($zayav_id) {//вставка расходов по заявке в информацию о заявке
	return
	'<div id="_zayav-expense">'.
		_zayav_expense_spisok($zayav_id).
		_zayav_bonus_spisok($zayav_id).
	'</div>';
}
function _zayav_expense_spisok($zayav_id, $insert_id=0) {//вставка расходов по заявке в информацию о заявке
	$sql = "SELECT *
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `zayav_id`=".$zayav_id."
			ORDER BY `id`";
	$arr = query_arr($sql);

	if(empty($arr))
		return '';

	$arr = _zayav_expense_sort($arr);
	$arr = _attachValToList($arr);
	$arr = _tovarValToList($arr);

	//сумма начислений по заявке
	$sql = "SELECT SUM(`sum`)
			FROM `_money_accrual`
			WHERE `app_id`=".APP_ID."
			  AND `zayav_id`=".$zayav_id."
			  AND !`deleted`";
	$accrual_sum = query_value($sql);

	$send =
		'<div class="headBlue but">'.
			'Расходы по заявке'.
			'<button class="vk small" onclick="_zayavExpenseEdit()">Добавить расход</button>'.
		'</div>'.
		'<h1>'.($accrual_sum ? 'Общая сумма начислений: <b>'.round($accrual_sum, 2).'</b> руб.' : 'Начислений нет.').'</h1>';

	$expense_sum = 0;
	$send .= '<table>';
	foreach($arr as $r) {
		$sum = _cena($r['sum']);
		$expense_sum += $sum;
		$ze = _zayavExpense($r['category_id'], 'all');

		$dop = $r['txt'];

		if($r['worker_id'])
			$dop = '<a class="go-report-salary" val="'.$r['worker_id'].':'.$r['year'].':'.$r['mon'].':'.$r['id'].'">'.
						_viewer($r['worker_id'], 'viewer_name').
					'</a>';

		if($r['tovar_id'])
			$dop = $r['tovar_set'].($r['tovar_avai_id'] ? '' : ': '._ms($r['tovar_count']).' '.$r['tovar_measure_name']);

		if($r['attach_id'])
			$dop = $r['attach_link'];

		$inserted = $insert_id == $r['id'] ? ' inserted' : ''; //подсветка только что внесённой записи
		$list = $r['salary_list_id'] ? ' list' : '';  //затемнение зп, которые привязаны к листу выдачи
		$send .=
			'<tr class="l'.$inserted.$list.'">'.
				'<td class="name">'.$ze['name'].
				'<td'.(!$r['tovar_avai_id'] ? ' colspan="2"' : '').'>'.$dop.
					($ze['dop'] == 4 && $ze['param'] ? '<a class="grey fr" href="'.URL.'&p=report&d=attach_schet">к списку счетов</a>' : '').
		($r['tovar_avai_id'] ?
				'<td class="count"><b>'._ms($r['tovar_count']).'</b> '.$r['tovar_measure_name']
		: '').
				'<td class="sum'.($r['v1'] ? ' paid' : '').
					($r['v1'] ?
						_tooltip('Счёт оплачен '.FullDataTime($r['v1_dtime'], 1).'<br>изменил'.(_viewer($r['v1_viewer_id'], 'viewer_sex') == 1 ? 'a' : '').' '._viewer($r['v1_viewer_id'], 'viewer_name'), -130, 'r', 1)
					: '">').

					'<em>'._sumSpace($sum).' р.</em>'.
					(!$r['salary_list_id'] && !$r['v1'] || SA?
						'<div val="'.$r['id'].'" class="img_del m15'._tooltip('Удалить', -46, 'r').'</div>'
					: '');
	}

	$ost = $accrual_sum - $expense_sum;
	$send .= '<tr><td colspan="3" class="r">Итог:<td class="sum"><b>'._sumSpace($expense_sum).'</b> р.'.
			 '<tr><td colspan="3" class="r">Остаток:<td class="sum '.($ost > 0 ? ' plus' : 'minus').'">'._sumSpace($ost).' р.'.
			'</table>';

	return $send;
}
function _zayav_expense_sort($arr) {
	$send = array();
	foreach(_zayavExpense(0, 'sort') as $i)
		foreach($arr as $id => $r)
			if($i == $r['category_id'])
				$send[$id] = $r;
	return $send;
}
function _zayav_bonus_spisok($zayav_id) {
	$sql = "SELECT *
			FROM `_salary_bonus`
			WHERE `app_id`=".APP_ID."
			  AND `zayav_id`=".$zayav_id;
	if(!$arr = query_arr($sql))
		return '';

	$send = '<table class="ze-spisok">';
	foreach($arr as $r) {
		$send .=
			'<tr><td class="name">Бонус '._cena($r['procent']).'%'.
				'<td><a class="go-report-salary" val="'.$r['worker_id'].':'.$r['year'].':'.$r['mon'].':'.$r['id'].'">'.
						_viewer($r['worker_id'], 'viewer_name').
					'</a>'.
				'<td class="sum">'._cena($r['sum']).' р.';
	}

	$send .= '</table>';

	return $send;
}




/* Ведение прикреплённых счетов в расходах по заявке */
function _zayav_expense_attach_schet() {
	$data = _zayav_expense_attach_schet_spisok();
	return
	'<div id="ze-attach-schet" class="mar8">'.
		$data['spisok'].
	'</div>';
}
function _zayav_expense_attach_schetFilter($v) {
	$filter = array(
		'limit' => _num(@$v['limit']) ? $v['limit'] : 50,
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'find' => trim(@$v['find']),
		'no_attach' => _num(@$v['no_attach']),
		'no_pay' => !isset($v['no_pay']) ? 1 : _num($v['no_pay'])
	);
	return $filter;
}
function _zayav_expense_attach_schet_spisok($v=array()) {// список клиентов
	$filter = _zayav_expense_attach_schetFilter($v);
	$filter = _filterJs('ZE_ATTACH_SCHET', $filter);

	$cond = "`ze`.`app_id`=".APP_ID."
		 AND `ze`.`category_id` IN ("._zayavExpense('attach_schet_ids').")";

	$JOIN = '';

	if($filter['find']) {
		if(_cena($filter['find']))
			$cond .= " AND `ze`.`sum`="._cena($filter['find']);
		else
			$JOIN = "RIGHT JOIN `_attach` `att`
					 ON `att`.`id`=`ze`.`attach_id`
					AND `att`.`name` LIKE '%".$filter['find']."%'";
	}

	if($filter['no_attach'])
		$cond .= " AND !`ze`.`attach_id`";
	if($filter['no_pay'])
		$cond .= " AND !`ze`.`v1`";

	$sql = "SELECT
				COUNT(*) `all`,
				SUM(`ze`.`sum`) `sum`
			FROM `_zayav_expense` `ze`
			".$JOIN."
			WHERE ".$cond;
	if(!$r = query_assoc($sql))
		return array(
			'spisok' => $filter['js'].'<div class="_empty">Счетов не найдено.</div>',
			'filter' => $filter
		);

	$all = $r['all'];
	$send['filter'] = $filter;
	$send['spisok'] = $filter['js'];

	$sql = "SELECT `ze`.*
			FROM `_zayav_expense` `ze`
			".$JOIN."
			WHERE ".$cond."
			ORDER BY `ze`.`id` DESC
			LIMIT "._startLimit($filter);
	$spisok = query_arr($sql);
	$spisok = _zayavValToList($spisok);
	$spisok = _attachValToList($spisok);

	$send['spisok'] .= $filter['page'] != 1 ? '' :
		'Показан'._end($all, ' ', 'о ').$all.
			' файл'._end($all, '', 'а', 'ов').
			'-сч'._end($all, 'ёт', 'ёта', 'етов').
			($filter['no_pay'] ? ' на сумму <b>'._sumSpace($r['sum']).'</b> руб.' : '').
		'<table class="_spisok mt5">'.
			'<tr>'.
				'<th>Счёт'.
				'<th>Сумма'.
				'<th>Дата<br />внесения';

	foreach($spisok as $r) {
		if($filter['find'])
			$r['attach_link'] = _findRegular($filter['find'], $r['attach_link']);
		$send['spisok'] .=
			'<tr class="l">'.
				'<td>Заявка '.$r['zayav_link_name'].
					'<div>'.$r['attach_link'].'</div>'.
			(!$r['v1'] ?
					'<div class="to-pay fr">'.
						'Счёт не оплачен. '.
						'<a onclick="_zayavExpenseAttachSchetPay('.$r['id'].')">Изменить на "<b>оплачено</b>"</a>'.
					'</div>'
			: '').
				'<td class="w70 r '.($r['v1'] ? 'paid' : 'grey').
					($r['v1'] ?
						_tooltip('Счёт оплачен '.FullDataTime($r['v1_dtime'], 1).'<br>изменил'.(_viewer($r['v1_viewer_id'], 'viewer_sex') == 1 ? 'a' : '').' '._viewer($r['v1_viewer_id'], 'viewer_name'), -50, '', 1)
					: '">').
					_sumSpace($r['sum'], 1).
				'<td class="dtime">'._dtimeAdd($r);
	}

	$send['spisok'] .= _next($filter + array(
		'all' => $all,
		'tr' => 1
	));

	return $send;
}




/* Виды деятельности */
function _service($i=false, $id=0) {
	$key = CACHE_PREFIX.'service';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					`id`,
					`name`
				FROM `_zayav_service`
				WHERE `app_id`=".APP_ID."
				ORDER BY `id`";
		if($arr = query_arr($sql)) {
			foreach($arr as $k => $r)
				$arr[$k]['const'] = array();
		} else
			$arr[0] = array(
				'id' => 0,
				'name' => ''
			);
		xcache_set($key, $arr, 86400);
	}

	if($i == 'menu')
		return _serviceMenu($arr);

	if($i == 'count')
		return count($arr);

	if($i == 'active_count')
		return count($arr) - 1;

	if($i == 'current')
		return _serviceCurrentId($arr, $id);

	if($i == 'js')
		return _serviceJs($arr);

	if($i == 'js_client')
		return _serviceJsClient($arr, $id);

	if($i == 'const_arr')
		return _serviceConstArr($arr[$id]['const']);

	if($i == 'name') {
		if(!$id)
			return '';
		return $arr[$id]['name'];
	}

	return false;
}
function _serviceMenu($arr) {//меню для списка заявок
	if(count($arr) < 2)
		return '';

	$id = _serviceCurrentId($arr);

	$link = '';
	foreach($arr as $r) {
		$sel = $r['id'] == $id ? ' sel' : '';
		$link .= '<a href="'.URL.'&p=zayav&type_id='.$r['id'].'" class="link'.$sel.'">'.$r['name'].'</a>';
	}

	return '<div id="dopLinks">'.$link.'</div>';
}
function _serviceCurrentId($spisok, $type_id=0) {//получение текущего type_id заявок
	if(!$spisok)
		return 0;

	//если есть только один вид деятельности, возвращение его, не важно, активен или нет
	if(count($spisok) == 1)
		return key($spisok);

	$cookie_key = COOKIE_PREFIX.'zayav-type';

	if($type_id)
		foreach($spisok as $r)
			if($r['id'] == $type_id) {
				setcookie($cookie_key, $type_id, time() + 3600, '/');
				return $type_id;
			}

	reset($spisok);
	if(!$id = _num(@$_GET['type_id'])) {
		if(_num(@$_COOKIE[$cookie_key]))
			foreach($spisok as $r)
				if($r['id'] == $_COOKIE[$cookie_key])
					return $_COOKIE[$cookie_key];
		reset($spisok);
		return key($spisok);
	}

	foreach($spisok as $r)
		if($r['id'] == $id) {
			setcookie($cookie_key, $id, time() + 3600, '/');
			return $id;
		}

	reset($spisok);
	return key($spisok);
}
function _serviceJs($arr) {//список видов деятельности в формате JS - ассоциативный список
	$send = array();
	foreach($arr as $r)
		$send[$r['id']] = $r['name'];
	return _assJson($send);
}
function _serviceJsClient($arr, $client_id) {//список видов деятельности, которые использовались в заявках клиента. В формате JS - ассоциативный список
	if(count($arr) < 2)
		return '{}';

	$ass = array();
	foreach($arr as $r)
		$ass[$r['id']] = $r['name'];

	$send = array();
	$sql = "SELECT
				DISTINCT `service_id`,
				COUNT(`id`) `count`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `client_id`=".$client_id."
			GROUP BY `service_id`
			ORDER BY `service_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$send[$r['service_id']] = $ass[$r['service_id']].'<em>'.$r['count'].'</em>';

	return _assJson($send);
}
function _serviceConstArr($arr) {//получение ассоциативного массива констант
	$send = array();
	foreach($arr as $key => $val)
		$send[$key] = $val;
	return $send;
}






/* Отчёты по заявкам */
function _zayav_report() {
	$filter = _zayav_reportFilter();
	return
	'<div id="zayav-report">'.
		_zayav_report_filter($filter['period']).
		'<div id="status-count">'._zayav_report_status_count($filter).'</div>'.
		'<div id="executer-count">'._zayav_report_executer($filter).'</div>'.
		_zayav_report_cols_set().
		'<div id="spisok">'._zayav_report_spisok().'</div>'.
	'</div>';
}
function _zayav_report_status_count($v) {//статусы с количеством заявок
	$filter = _zayav_reportFilter($v);

	$cond = "`app_id`=".APP_ID;
	$cond .= _period($filter['period'], 'sql');

	$sql = "SELECT
				`status_id`,
				COUNT(`id`)
			FROM `_zayav`
			WHERE ".$cond."
			GROUP BY `status_id`
			ORDER BY `status_id`";
	if(!$spisok = query_ass($sql))
		return '';

	$all = 0;
	$send = '';
//	foreach($spisok as $id => $r) {
	foreach(_zayavStatus('all') as $id => $r) {
		if(!isset($spisok[$id]))
			continue;
		$send .= '<div'._zayavStatus($id, 'bg').' class="cub'._tooltip(_zayavStatus($id), 2, 'l').$spisok[$id].'</div>';
		$all += $spisok[$id];
	}

	return
	'<div class="cub all">'.
		'Всего <b>'.$all.'</b> заяв'._end($all, 'ка', 'ки', 'ок').':'.
	'</div>'.
	$send;
}
function _zayav_report_executer($v) {//исполнители с количеством заявок и суммой заработанных денег
	$ass = _idsAss(_viewer(VIEWER_ID, 'zayav_report_cols_show'));
	if(empty($ass[7]))
		return '';

	$filter = _zayav_reportFilter($v);

	$cond = "`app_id`=".APP_ID;
	$cond .= _period($filter['period'], 'sql');

	$sql = "SELECT `id`
			FROM `_zayav`
			WHERE ".$cond;
	if(!$zayav_ids = query_ids($sql))
		return '';

	$sql = "SELECT
				`worker_id` `id`,
				COUNT(`id`) `count`,
				SUM(`sum`) `sum`
			FROM `_zayav_expense`
			WHERE `zayav_id` IN (".$zayav_ids.")
			  AND `worker_id`
			GROUP BY `worker_id`";
	if(!$spisok = query_arr($sql))
		return '';

	$summa = 0;
	$send =
		'<div class="headName">Начисления по каждому сотруднику</div>'.
		'<div class="_info">Учтены только новые заявки и начисления по ним за выбранный период времени, даже если начисления было произведено в другом месяце.</div>'.
		'<table class="_spisok">'.
			'<tr><th>Сотрудник'.
				'<th>Заявки'.
				'<th>Начисления з/п';
	foreach($spisok as $id => $r) {
		$send .= '<tr><td>'._viewer($id, 'viewer_name').
					 '<td class="center">'.$r['count'].
					 '<td class="r">'._sumSpace($r['sum']).' руб.';

		$summa += $r['sum'];
	}

	$send .= '</table>';

	return $send;
}
function _zayav_report_filter($sel) {
	return
	'<div id="filter">'.
		_calendarFilter(array(
			'days' => _zayav_report_days(),
			'func' => '_zayav_report_days',
			'sel' => $sel
		)).
		'<br />'.
		'<a class="zayav-erm'.(APP_ID != 3978722 && !SA ? ' dn' : '').'" val="'.TODAY.'">'.
			'Отчёт XLS за <b>'._monthDef(strftime('%m')).' '.strftime('%Y').'</b>'.
		'</a>'.
	'</div>';
}
function _zayav_report_days($mon=0) {//отметка дней в календаре, в которые вносились новые заявки
	$sql = "SELECT DATE_FORMAT(`dtime_add`,'%Y-%m-%d') AS `day`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `dtime_add` LIKE ('".($mon ? $mon : strftime('%Y-%m'))."%')
			GROUP BY DATE_FORMAT(`dtime_add`,'%d')";
	$q = query($sql);
	$days = array();
	while($r = mysql_fetch_assoc($q))
		$days[$r['day']] = 1;
	return $days;
}
function _zayav_report_cols_set() {
	$ass = _idsAss(_viewer(VIEWER_ID, 'zayav_report_cols_show'));
	$spisok = '';
	foreach(_zayav_report_cols() as $id => $r) {
		$spisok .= _checkNew(array(
						'id' => 'ch'.$id,
						'txt' => $r,
						'value' => $id == 1 ? 1 : isset($ass[$id]),
						'light' => 1,
						'disabled' => $id == 1,
						'block' => 1
					));
	}
	return
	'<div class="cols-div">'.
		'<a>Настроить отображение колонок</a>'.
		'<div id="sp">'.
			'<a>Настроить отображение колонок</a>'.
			$spisok.
		'</div>'.
	'</div>';
}
function _zayav_report_cols($id=false) {//отображаемые поля списка заявок
	$arr = array(
		1 => 'Дата',
		2 => 'Номер заявки',
		3 => 'Название',
		4 => '№ дог.',
		5 => 'Клиент',
		6 => 'Адрес',
		7 => 'Исполнители',
		8 => 'Стоимость',
		9 => 'Начислено',
		10 => 'Оплачено',
		11 => 'Баланс',
		12 => 'Расходы',
		13 => 'Остаток'
	);
	if($id === false)
		return $arr;

	if(!isset($arr[$id]))
		return '';

	return $arr[$id];
}
function _zayav_reportFilter($v=array()) {
	$send = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 500,
		'period' => _period(empty($v['period']) ? strftime('%Y-%m') : $v['period'])
//		'period' => _period(empty($v['period']) ? '2016-04' : $v['period'])
	);
	return $send;
}
function _zayav_report_spisok($v=array()) {
	$filter = _zayav_reportFilter($v);
	$filter = _filterJs('ZAYAV_REPORT', $filter);

	$cond = "`app_id`=".APP_ID;
	$cond .= _period($filter['period'], 'sql');

	$sql = "SELECT *
			FROM `_zayav`
			WHERE ".$cond."
			ORDER BY `id`";
	$zayav = query_arr($sql);

	$zayav = _clientValToList($zayav);
	$zayav = _dogovorValToList($zayav);
	$zayav = _zayavExecuterToList($zayav);

	$send =
		$filter['js'].
		'<table class="_spisok"><tr>';

	$colsAss = _idsAss(_viewer(VIEWER_ID, 'zayav_report_cols_show'));
	foreach($colsAss as $id => $r)
		$send .= '<th>'._zayav_report_cols($id);

	foreach($zayav as $id => $r) {
		$sum_cost = _cena($r['sum_cost']) ? _sumSpace($r['sum_cost']) : '';
		$sum_accrual = _cena($r['sum_accrual']) ? _sumSpace($r['sum_accrual']) : '';
		$sum_pay = _cena($r['sum_pay']) ? _sumSpace($r['sum_pay']) : '';
		$sum_dolg = _cena($r['sum_dolg'], 1) ? _sumSpace($r['sum_dolg']) : '';
		$sum_expense = _cena($r['sum_expense']) ? _sumSpace($r['sum_expense']) : '';
		$sum_profit = _cena($r['sum_profit'], 1) ? _sumSpace($r['sum_profit']) : '';

		$send .= '<tr class="unit">'.
			'<td'._zayavStatus($r['status_id'], 'bg').' class="dtime'._tooltip(_zayavStatus($r['status_id']), 10, 'l').FullData($r['dtime_add'], 1, 1);
		if(isset($colsAss[2]))
			$send .= '<td class="nomer r"><a href="'.URL.'&p=zayav&d=info&id='.$id.'">#'.$r['nomer'].'</a>';
		if(isset($colsAss[3]))
			$send .= '<td class="name">'.$r['name'];
		if(isset($colsAss[4]))
			$send .= '<td class="dog r">'.($r['dogovor_id'] ? $r['dogovor_min'] : '');
		if(isset($colsAss[5]))
			$send .= '<td>'.$r['client_link'];
		if(isset($colsAss[6]))
			$send .= '<td class="adres">'.$r['adres'];
		if(isset($colsAss[7]))
			$send .= '<td class="executer">'.$r['executer_spisok'];
		if(isset($colsAss[8]))
			$send .= '<td class="sum-cost r">'.$sum_cost;
		if(isset($colsAss[9]))
			$send .= '<td class="sum-accrual r">'.$sum_accrual;
		if(isset($colsAss[10]))
			$send .= '<td class="sum-pay r">'.$sum_pay;
		if(isset($colsAss[11]))
			$send .= '<td class="sum-dolg r'.($r['sum_dolg'] < 0 ? ' minus' : '').'">'.$sum_dolg;
		if(isset($colsAss[12]))
			$send .= '<td class="sum-expense r">'.$sum_expense;
		if(isset($colsAss[13]))
			$send .= '<td class="sum-profit r'.($r['sum_profit'] < 0 ? ' minus' : '').'">'.$sum_profit;
	}
	$send .= _next($filter + array(
		'all' => count($zayav),
		'tr' => 1
	));

	return $send;
}






















