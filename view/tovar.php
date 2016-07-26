<?php
function _tovar() {
	switch(@$_GET['d']) {
		case 'info': return _tovar_info();
	}

	$arr = array(
		0 => 'Все товары',
		1 => 'Есть в наличии',
		2 => 'Добавлено в заказ'
//		4 => 'В пути'
	);

	$data = _tovar_spisok_icon(_hashFilter('tovar'));
	$v = $data['filter'];

	return
	'<div id="_tovar">'.
//		'<div id="dopLinks">'.
//			'<a class="link'.($d == 'catalog' ? ' sel' : '').'" href="'.URL.'&p=tovar&d=catalog">Каталог товаров</a>'.
//			'<a class="link'.($d == 'provider' ? ' sel' : '').'" href="'.URL.'&p=tovar&d=provider">Поставщики</a>'.
//		'</div>'.
		'<div class="result">'.$data['result'].'</div>'.
		'<div id="icon">'.
			'<div val="5" class="img img_tovar_stat'.($v['icon_id'] == 5 ? ' sel' : '')._tooltip('Статистика', -33).'</div>'.
//			'<div val="1" class="img img_tovar_group'._tooltip('Товары по группам', -57).'</div>'.
			'<div val="2" class="img img_tovar_category'.($v['icon_id'] == 2 ? ' sel' : '')._tooltip('По категориям', -43).'</div>'.
//			'<div val="3" class="img img_tovar_foto'._tooltip('Подробный список', -100, 'r').'</div>'.
			'<div val="4" class="img img_tovar_spisok'.($v['icon_id'] == 4 ? ' sel' : '')._tooltip('Краткий список', -79, 'r').'</div>'.
		'</div>'.
		'<table class="tabLR">'.
			'<tr><td class="left">'.
					'<div id="spisok">'.$data['spisok'].'</div>'.
				'<td class="right">'.
					'<div class="div-but'.($v['icon_id'] == 5 ? ' dn' : '').'">'.
						'<button class="vk fw" id="tovar-add">Внести новый товар<br />в каталог</button>'.
						'<div id="find"></div>'.
						'<br />'.
						'<input type="hidden" id="icon_id" value="'.$v['icon_id'].'" />'.
						_radio('group', $arr, $v['group'], 1).
					'</div>'.

					'<div class="div-cat'.($v['icon_id'] == 5 || $v['icon_id'] == 2 ? ' dn' : '').'">'.
						'<div class="findHead">Категория</div>'.
						'<input type="hidden" id="category_id" value="'.$v['category_id'].'" />'.

						'<div class="findHead">Наименование</div>'.
						'<input type="hidden" id="name_id" value="'.$v['name_id'].'" />'.

						'<div class="findHead">Производитель</div>'.
						'<input type="hidden" id="vendor_id" value="'.$v['vendor_id'].'" />'.
					'</div>'.
		'</table>'.
	'</div>';
}

function _tovarCategory($id=false, $i='name') {
	$key = CACHE_PREFIX.'tovar_category'.APP_ID;
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					`id`,
					`app_id`,
					`name`,
					0 `use`
				FROM `_tovar_category`
				ORDER BY `id`";
		if($arr = query_arr($sql, GLOBAL_MYSQL_CONNECT)) {
			$sql = "SELECT `category_id`
					FROM `_tovar_category_use`
					WHERE `app_id`=".APP_ID;
			$q = query($sql, GLOBAL_MYSQL_CONNECT);
			while($r = mysql_fetch_assoc($q))
				$arr[$r['category_id']]['use'] = 1;
			xcache_set($key, $arr, 86400);
		}
	}

	//список id общих категорий (app_id = 0)
	if($id == 'noapp') {
		$ids = array();
		foreach($arr as $r) {
			if($r['app_id'])
				continue;
			$ids[] = $r['id'];
		}
		return implode(',', $ids);
	}

	//список id категорий, которые используются в приложении
	if($id == 'use') {
		$ids = array(0);
		foreach($arr as $r) {
			if(!$r['use'])
				continue;
			$ids[] = $r['id'];
		}
		return implode(',', $ids);
	}

	if(!isset($arr[$id]))
		return _cacheErr('неизвестный id категории', $id);

	if($i == 'name')
		return $arr[$id]['name'].' ';

	return _cacheErr('неизвестный ключ категории товара', $i);
}
function _tovarName($id=false, $i='name') {
	$key = CACHE_PREFIX.'tovar_name';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					`id`,
					`name`
				FROM `_tovar_name`
				ORDER BY `id`";
		if($arr = query_arr($sql, GLOBAL_MYSQL_CONNECT))
			xcache_set($key, $arr, 86400);
	}

	if(!isset($arr[$id]))
		return _cacheErr('отсутствует id товара', $id);

	if($i == 'name')
		return $arr[$id]['name'].' ';

	return _cacheErr('неизвестный ключ товара', $i);
}
function _tovarVendor($id=false, $i='name') {
	$key = CACHE_PREFIX.'tovar_vendor';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					`id`,
					`name`
				FROM `_tovar_vendor`
				ORDER BY `id`";
		if($arr = query_arr($sql, GLOBAL_MYSQL_CONNECT))
			xcache_set($key, $arr, 86400);
	}

	if($id == 0)
		return '';

	if(!isset($arr[$id]))
		return _cacheErr('неизвестный id производителя', $id);

	if($i == 'name')
		return $arr[$id]['name'].' ';

	return _cacheErr('неизвестный ключ производителя', $i);
}
function _tovarCategoryJs() {//категории товаров JS
	$sql = "SELECT
				`c`.`id`,
				`c`.`name`
			FROM `_tovar_category` `c`,
				 `_tovar_category_use` `u`
			WHERE `u`.`app_id`=".APP_ID."
			  AND `u`.`category_id`=`c`.`id`
			ORDER BY `u`.`sort`";
	return query_selJson($sql, GLOBAL_MYSQL_CONNECT);
}
function _tovarVendorJs() {//производители товаров JS
	$sql = "SELECT
				`id`,
				`name`
			FROM `_tovar_vendor`
			ORDER BY `name`";
	return query_selJson($sql, GLOBAL_MYSQL_CONNECT);
}
function _tovarPosition($id=false) {//виды применения к другому товару
	$arr = array(
		0 => '',
		1 => 'запчасть',
		2 => 'комплектующее',
		3 => 'аксессуар',
		4 => 'ингредиент'
	);
	
	if($id === false) {
		unset($arr[0]);
		return $arr;
	}
	
	if(!isset($arr[$id]))
		return _cacheErr('неизвестный id применения', $id);

	return $arr[$id];
}
function _tovarFeature($id=false, $i='name') {//названия характеристик товаров
	$key = CACHE_PREFIX.'tovar_feature_name';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					`id`,
					`name`
				FROM `_tovar_feature_name`
				ORDER BY `id`";
		if($arr = query_arr($sql, GLOBAL_MYSQL_CONNECT))
			xcache_set($key, $arr, 86400);
	}

	if($id == 'get_id') {//получение id характеристики по названию, если нет такого названия, то внесени в базу
		$name = _txt($i);
		if(empty($name))
			return 0;

//      попытка сравнения названий без участия базы данных
//		foreach($arr as $r)
//			if(!strcasecmp($name, $r['name']))
//				return $r['id'];

		$sql = "SELECT `id`
				FROM `_tovar_feature_name`
				WHERE `name`='".$name."'
				ORDER BY `id`
				LIMIT 1";
		if(!$name_id = query_value($sql, GLOBAL_MYSQL_CONNECT)) {
			$sql = "INSERT INTO `_tovar_feature_name` (
						`name`
					) VALUES (
						'".addslashes($name)."'
					)";
			query($sql, GLOBAL_MYSQL_CONNECT);
			$name_id = query_insert_id('_tovar_feature_name', GLOBAL_MYSQL_CONNECT);
			xcache_unset($key);
			_appJsValues();
		}
		return $name_id;
	}

	if(!isset($arr[$id]))
		return _cacheErr('неизвестный id характеристики', $id);

	if($i == 'name')
		return $arr[$id]['name'];

	return _cacheErr('неизвестный ключ характеристики', $i);
}
function _tovarFeatureJs() {//характеристики товаров JS
	$sql = "SELECT
				`id`,
				`name`
			FROM `_tovar_feature_name`
			ORDER BY `name`";
	return query_selJson($sql, GLOBAL_MYSQL_CONNECT);
}
function _tovarMeasure($id='all', $i='short') {//единицы изменения товаров
	$key = CACHE_PREFIX.'tovar_measure';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT *
				FROM `_tovar_measure`
				ORDER BY `sort`";
		if($arr = query_arr($sql, GLOBAL_MYSQL_CONNECT))
			xcache_set($key, $arr, 86400);
	}

	if($id == 'all')
		return $arr;

	if($id == 'js') {
		$spisok = array();
		foreach($arr as $r)
			$spisok[$r['id']] = array(
				'title' => $r['short'],
				'content' => '<b>'.$r['short'].'</b>'.($r['name'] ? ' - '.$r['name'] : '')
			);
		return _selJson($spisok);
	}

	if(!isset($arr[$id]))
		return _cacheErr('неизвестный id единицы измерения', $id);

	if(!isset($arr[$id][$i]))
		return _cacheErr('неизвестный ключ производителя', $i);

	return $arr[$id][$i];
}


function _tovarValToList($arr, $key='tovar_id', $zayav_id=0) {//вставка данных с товарами в массив по tovar_id
	if(empty($arr))
		return array();
	
	foreach($arr as $r)
		$arr[$r['id']] += array(
			'tovar_set' => '',
			'tovar_set_name' => '',
			'tovar_measure_name' => '',
			'tovar_name' => '',
			'tovar_link' => '',
			'tovar_name_min' => '',

			'tovar_zayav' => '',
			'tovar_sale' => '',

			'tovar_buy' => 0,
			'tovar_sell' => 0,

			'tovar_avai_count' => 0,
			'tovar_avai_b' => '',
			'tovar_articul' => '',
			'tovar_articul_full' => '',
			'tovar_zakaz_count' => 0,

			'tovar_image_small' => ''
		);

	if(!$tovar_ids = _idsGet($arr, $key))
		return $arr;
	
	$sql = "SELECT
				*,
				`id` `tovar_id`,
				'' `set_name`,
				'' `set_name_b`,
				'' `set_noname`,
				0 `buy`,
				0 `sell`,
				0 `avai_count`,
				'' `articul`,
				0 `articul_count`,
				0 `articul_count_first`,
				'' `articul_arr`,
				0 `zakaz_count`
			FROM `_tovar`
			WHERE `id` IN (".$tovar_ids.")";
	if(!$tovar = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return $arr;

	//получение данных товаров, на которые устанавливается запчасть
	if($ids = _idsGet($tovar, 'tovar_id_set')) {
		$sql = "SELECT *
				FROM `_tovar`
				WHERE `id` IN (".$ids.")";
		if($set = query_arr($sql, GLOBAL_MYSQL_CONNECT))
			foreach($tovar as $id => $r) {
				if(!$r['tovar_id_set'])
					continue;

				$s = $set[$r['tovar_id_set']];
				$name = _tovarName($s['name_id']);
				$vendor = _tovarVendor($s['vendor_id']);

				$tovar[$id] = array(
					'set_name' => $name.$vendor.$s['name'],
					'set_name_b' => $name.'<b>'.$vendor.$s['name'].'</b>',
					'set_noname' => $vendor.$s['name']
				) + $tovar[$id];
			}
	}


	//закупка и продажа
	$sql = "SELECT *
			FROM `_tovar_cost`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id` IN (".$tovar_ids.")";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q)) {
		$tovar[$r['tovar_id']]['buy'] = $r['sum_buy'];
		$tovar[$r['tovar_id']]['sell'] = $r['sum_sell'];
	}


	//наличие товара
	$sql = "SELECT
				`tovar_id`,
				SUM(`count`) `count`
			FROM `_tovar_avai`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id` IN (".$tovar_ids.")
			GROUP BY `tovar_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$tovar[$r['tovar_id']]['avai_count'] = _ms($r['count']);

	//артикулы
	$articul = array();
	$sql = "SELECT *
			FROM `_tovar_avai`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id` IN (".$tovar_ids.")
			  AND `count`
			ORDER BY `tovar_id`,`count` DESC";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$articul[$r['tovar_id']][$r['id']] = $r;

	foreach($articul as $id => $r) {
		$tovar[$id]['articul'] = _tovarAvaiArticulTab($r, 1);
		$tovar[$id]['articul_count'] = count($r);
		$tovar[$id]['articul_count_first'] = _ms($r[key($r)]['count']);

		foreach($r as $k => $i) {
			unset($r[$k]['id']);
			unset($r[$k]['app_id']);
			unset($r[$k]['articul']);
			unset($r[$k]['tovar_id']);
			unset($r[$k]['about']);
			$r[$k]['count'] = _ms($i['count']);
			$r[$k]['sum_buy'] = _cena($i['sum_buy']);
		}
		$tovar[$id]['articul_arr'] = $r;
	}

	//заказ товара
	$sql = "SELECT
				`tovar_id`,
				SUM(`count`) `count`
			FROM `_tovar_zakaz`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id` IN (".$tovar_ids.")
".($zayav_id ? " AND `zayav_id`=".$zayav_id : '')."
			GROUP BY `tovar_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$tovar[$r['tovar_id']]['zakaz_count'] = $r['count'];


	//прикрепление картинок
	$tovar = _imageValToList($tovar, 'tovar_id');

	foreach($arr as $id => $r) {
		if(!$r[$key])
			continue;

		$t = $tovar[$r[$key]];
		$tovar_id = $t['id'];
		$set = $t['tovar_id_set'];
		$go = ' class="tovar-info-go'.($t['deleted'] ? ' deleted' : '').'" val="'.$tovar_id.'"';
		$name = _tovarName($t['name_id']);
		$nameB = $set ? '<b>'.$name.'</b>' : $name;
		$vendor = _tovarVendor($t['vendor_id']);

		$arr[$id] = array(
			'tovar_set' =>
				'<a'.$go.'>'.
					$nameB.'<b>'.$vendor.'</b>'.
					($set ? $t['name'] : '<b>'.$t['name'].'</b>').
					($set ? '<br />для '.$t['set_name'] : '').
				'</a>',

			'tovar_set_name' => $t['set_name_b'],

			'tovar_name' => trim($name.$vendor.$t['name'].' '.$t['set_name']),
			'tovar_name_b' => $name.'<br /><b>'.$vendor.$t['name'].'</b>',

			'tovar_name_min' =>
				$name.'<b>'.$vendor.$t['name'].'</b>'.
				($set ? '<div class="tovar-set">'.$t['set_name'].'</div>' : ''),


			'tovar_measure_name' => _tovarMeasure($t['measure_id']),

			'tovar_zayav' =>
				'<a'.$go.'>'.
					'<b>'.$name.'</b>'.$vendor.
					($t['name'] ? $t['name'].'<br />' : '').
					$t['set_noname'].
				'</a>',

			'tovar_sale' =>
				'<div class="tovar-info-go tovar-sale" val="'.$tovar_id.'">'.
					'<div class="cat">'._tovarCategory($t['category_id']).'</div>'.
					$name.'<b>'.$vendor.$t['name'].'</b>:'.
					'<b class="count">'._ms(@$r['tovar_count']).' '._tovarMeasure($t['measure_id']).'</b>'.
				'</div>',

			'tovar_select' => $set ?
								'<b>'.$name.'</b>'.$t['name'].'<br /><tt>'.$t['set_name_b'].'</tt>'
								:
								$name.'<b>'.$vendor.$t['name'].'</b>',

			'tovar_buy' => _cena($t['buy']),
			'tovar_sell' => _cena($t['sell']),

			'tovar_avai_count' => _ms($t['avai_count']),
			'tovar_avai_b' => $t['avai_count'] ? '<b class="avai">'._ms($t['avai_count']).'</b>' : '',
			'tovar_zakaz_count' => $t['zakaz_count'],

			'tovar_image_small' => $t['image_small']
		) + $arr[$id];

		$arr[$id]['tovar_link'] = '<a'.$go.'>'.$arr[$id]['tovar_name'].'</a>';
		$arr[$id]['tovar_articul'] = $t['articul'];
		$arr[$id]['tovar_articul_full'] =
			'<div id="ts-articul">'.
				'<div class="headName">Выбор из наличия:</div>'.
				'<table class="tsa-tab bs5 w100p">'.
					'<tr><td class="top w50">'.$t['image_small'].
						'<td class="top name">'.$arr[$id]['tovar_select'].
				'</table>'.
				$t['articul'].
				'<table class="tsa-bottom bs10 w100p'.($t['articul_count'] == 1 ? '' : ' dn').'">'.
					'<tr><td><td>'.
					'<tr><td class="label r w70">Количество:*'.
						'<td><input type="text" class="w50" id="tsa-count" value="1" /> '.
							$arr[$id]['tovar_measure_name'].
							'<span>(max: <b class="max">'.$t['articul_count_first'].'</b>)</span>'.
					'<tr><td>'.
						'<td><button class="vk submit">Добавить</button>'.
							'<button class="vk cancel">Выбрать другой товар</button>'.
				'</table>'.
			'</div>';
		$arr[$id]['tovar_articul_arr'] = $t['articul_arr'];
	}

	return $arr;
}
function _tovarArticulCreate() {//формирование очередного артикула товара
	$sql = "SELECT MAX(`articul`)
			FROM `_tovar_avai`
			WHERE `app_id`=".APP_ID;
	$max = _num(query_value($sql, GLOBAL_MYSQL_CONNECT));
	
	$max++;

	$articul = $max;
	for($i = 0; $i < 6 - strlen($max); $i++)
		$articul = '0'.$articul;
	
	return $articul;
}

function _tovarDelAccess($r) {//разрешение на удаление товара
	$tovar_id = $r['id'];

	if(!$r['app_id'])
		return 'Товар был создан не в этом приложении';

	$sql = "SELECT COUNT(*) FROM `_tovar` WHERE `id`=".$r['tovar_id_set'];
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 'Этот товар применяется к другому товару';

	$sql = "SELECT COUNT(*) FROM `_money_income` WHERE !`deleted` AND `tovar_id`=".$tovar_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 'Производилась продажа товара';

	$sql = "SELECT COUNT(*) FROM `_schet_content` WHERE `tovar_id`=".$tovar_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 'Товар используется в счётах на оплату';

	$sql = "SELECT COUNT(*) FROM `_tovar_avai` WHERE `tovar_id`=".$tovar_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 'Товар есть в наличии';

	$sql = "SELECT COUNT(*) FROM `_tovar_move` WHERE `tovar_id`=".$tovar_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 'У товара есть история действий';

	$sql = "SELECT COUNT(*) FROM `_zayav_expense` WHERE `tovar_id`=".$tovar_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 'Товар используется в расходах по заявкам';

	$sql = "SELECT COUNT(*) FROM `_zayav_tovar` WHERE `tovar_id`=".$tovar_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 'Товар используется в заявках';

	//причины на запрет удаления нет
	return 0;
}

function _tovarFilter($v) {
	$default = array(
		'page' => 1,
		'limit' => 50,
		'icon_id' => 2,
		'find' => '',
		'group' => 0,
		'category_id' => 0,
		'name_id' => 0,
		'vendor_id' => 0

	);

	$filter = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 50,
		'icon_id' => _num(@$v['icon_id']) ? $v['icon_id'] : 2,
		'find' => trim(@$v['find']),
		'group' => _num(@$v['group']),
		'category_id' => intval(@$v['category_id']),
		'name_id' => intval(@$v['name_id']),
		'vendor_id' => intval(@$v['vendor_id']),

		'clear' => ''
	);

	foreach($default as $k => $r)
		if($r != $filter[$k]) {
			$filter['clear'] = '<a id="filter_clear">Очистить фильтр</a>';
			break;
		}
	return $filter;
}
function _tovar_spisok_icon($v=array()) {
	$filter = _tovarFilter($v);
	$filter = _filterJs('TOVAR', $filter);

	switch($filter['icon_id']) {
		case 5: return _tovar_stat($filter);
		case 2:
		default: return _tovar_category_spisok($filter);
		case 3: return _tovar_spisok($filter);
		case 4: return _tovar_spisok($filter);
	}
}

function _tovar_category_name($category_id, $i='arr') {//список наименований по выбранной категории
/*
	$i - варианты возврата:
		arr
		json
*/

	if(empty($category_id)) {
		if($i == 'json')
			return '[]';
		return _sel(array());
	}

	$sql = "SELECT DISTINCT(`name_id`)
			FROM `_tovar`
			WHERE `category_id`=".$category_id;
	$ids = query_ids($sql, GLOBAL_MYSQL_CONNECT);

	$nameIds = array();
	foreach(_ids($ids, 1) as $r)
		$nameIds[$r] = _tovarName($r);

	asort($nameIds);


	if($i == 'json')
		return _selJson($nameIds);

	return _sel($nameIds);
}
function _tovar_category_vendor($filter, $i='arr') {//список производителей по выбранной категории
	if(!$filter['category_id']) {
		if($i == 'json')
			return '[]';
		return _sel(array());
	}

	$sql = "SELECT DISTINCT(`vendor_id`)
			FROM `_tovar`
			WHERE `vendor_id`
			  AND `category_id`=".$filter['category_id'].
				($filter['name_id'] ? " AND `name_id`=".$filter['name_id'] : '');
	$ids = query_ids($sql, GLOBAL_MYSQL_CONNECT);

	$vendorIds = array();
	foreach(_ids($ids, 1) as $r)
		$vendorIds[$r] = _tovarVendor($r);

	asort($vendorIds);

	if($i == 'json')
		return _selJson($vendorIds);

	return _sel($vendorIds);
}

function _tovar_stat($filter) {//статистика по товарам
	$sql = "SELECT
				`category_id` `id`,
				0 `pos`,
				0 `count`,
				0 `sum`
			FROM `_tovar_category_use`
			WHERE `app_id`=".APP_ID."
			ORDER BY `sort`";
	$cat = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	//суммы в рублях для каждой категории
	$summa = 0;
	$sql = "SELECT
				`t`.`category_id` `id`,
				SUM(`ta`.`count`*`ta`.`sum_buy`) `sum`,
				SUM(`ta`.`count`) `count`
			FROM `_tovar` `t`,
				 `_tovar_avai` `ta`
			WHERE `ta`.`app_id`=".APP_ID."
			  AND `t`.`id`=`ta`.`tovar_id`
			  AND `t`.`category_id` IN ("._idsGet($cat).")
			GROUP BY `t`.`category_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q)) {
		$cat[$r['id']]['sum'] = _cena($r['sum']);
		$cat[$r['id']]['count'] = _cena($r['count']);
		$summa += $r['sum'];
	}

	$sql = "SELECT
				`id`,
				COUNT(`tovar_id`) `count`
			FROM (
				SELECT
					`category_id` `id`,
					`t`.`id` `tovar_id`
				FROM `_tovar` `t`
					RIGHT JOIN `_tovar_avai` `ta`
					ON `ta`.`app_id`=".APP_ID."
					AND `ta`.`tovar_id`=`t`.`id`
					AND `ta`.`count`
				WHERE `t`.`category_id` IN ("._idsGet($cat).")
				GROUP BY `t`.`id`
			) `tt`
			GROUP BY `tt`.`id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$cat[$r['id']]['pos'] = $r['count'];

	$catSpisok = '';
	foreach($cat as $r)
		$catSpisok .=
			'<tr><td>'.trim(_tovarCategory($r['id'])).
				'<td class="center">'.
					($r['pos'] ? '<span class="pos'._tooltip('Позиции', -31).$r['pos'].'</span>' : '').
				'<td class="r">'.($r['sum'] ? _sumSpace($r['sum']).' руб.' : '');


	$spisok =
	$filter['js'].
	'<div id="tovar-stat">'.
		'<div class="headName">Текущий остаток</div>'.
		'<table class="_spisok">'.
			'<tr><th>Категория'.
				'<th>Количество'.
				'<th>Сумма'.
			$catSpisok.
			'<tr><td class="r"><b>Итого:</b>'.
				'<td>'.
				'<td class="r"><b>'.($summa ? _sumSpace($summa).'</b> руб.' : '').
		'</table>'.
	'</div>';

	return array(
		'result' => 'Статистика по товарам',
		'spisok' => $spisok,
		'filter' => $filter
	);
}
function _tovar_category_spisok($filter) {
	$sql = "SELECT
				`category_id` `id`
			FROM `_tovar_category_use`
			WHERE `app_id`=".APP_ID."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return array(
			'all' => 0,
			'result' => 'Категории не определены'.$filter['clear'],
			'spisok' => $filter['js'].
						'<div class="_empty">'.
							'Категории товаров не настроены.'.
							'<a href="'.URL.'&p=setup&d=tovar&d1=category">Настроить</a>'.
						'</div>',
			'filter' => $filter
		);

	foreach($spisok as $r) {
		$spisok[$r['id']]['name'] = trim(_tovarCategory($r['id']));
		$spisok[$r['id']]['sub'] = array();
		$spisok[$r['id']]['sub_vendor'] = array();
		$spisok[$r['id']]['count'] = 0;
	}

	$all = count($spisok);
	$send = array(
		'all' => $all,
		'result' => 'Всего '.$all.' категори'._end($all, 'я', 'и', 'й').' товаров'.$filter['clear'],
		'spisok' => $filter['js'],
		'filter' => $filter
	);

	$cond = "!`deleted` AND `category_id` IN ("._idsGet($spisok).")";

	if(!empty($filter['find'])) {
//		$cond .= " AND `find` LIKE '%".$filter['find']."%'";
		$engRus = _engRusChar($filter['find']);
		$cond .= " AND (`find` LIKE '%".$filter['find']."%'".
			($engRus ? " OR `find` LIKE '%".$engRus."%'" : '').")";
	}

	$JOIN = "LEFT JOIN `_tovar_avai` `ta`
				 ON `ta`.`app_id`=".APP_ID."
				 AND `ta`.`tovar_id`=`t`.`id`";

	if($filter['group'] == 1)
		$JOIN = "RIGHT JOIN `_tovar_avai` `ta`
				     ON `ta`.`app_id`=".APP_ID."
				     AND `ta`.`tovar_id`=`t`.`id`
				     AND `ta`.`count`";

	if($filter['group'] == 2)
		$JOIN = "RIGHT JOIN `_tovar_zakaz` `ta`
				     ON `ta`.`app_id`=".APP_ID."
				     AND `ta`.`tovar_id`=`t`.`id`";

	//количество товаров в каждой категории
	$sql = "SELECT
				`category_id`,
				COUNT(`t`.`id`) `count`
			FROM `_tovar` `t`
			".$JOIN."
			WHERE ".$cond."
			GROUP BY `category_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['category_id']]['count'] = $r['count'];

/*
	$spisok[0]['name'] = 'Без категории';
	$spisok[0]['category_id'] = 0;
	$spisok[0]['sub'] = '';
	$spisok[0]['id'] = 0;
*/

	//количество товаров и наличия по каждому наименованию
	$sql = "SELECT
				`category_id`,
				`name_id`,
				COUNT(`t`.`id`) `count`,
				SUM(`ta`.`count`) `avai_zakaz`
			FROM `_tovar` `t`
			".$JOIN."
			WHERE ".$cond."
			GROUP BY `category_id`,`name_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		foreach($spisok as $sp)
			if($r['category_id'] == $sp['id']) {
				$spisok[$sp['id']]['sub'][$r['name_id']] = trim(_tovarName($r['name_id']));
				$spisok[$sp['id']]['sub_count'][$r['name_id']] = $r['count'];
				$spisok[$sp['id']][$filter['group'] == 2 ? 'zakaz' : 'avai'][$r['name_id']] = _ms($r['avai_zakaz']);
			}

	//количество товаров и наличия по каждому наименованию
	$sql = "SELECT
				`category_id`,
				`name_id`,
				`vendor_id`,
				COUNT(`t`.`id`) `count`,
				SUM(`ta`.`count`) `avai_zakaz`
			FROM `_tovar` `t`
			".$JOIN."
			WHERE ".$cond."
			GROUP BY `category_id`,`name_id`,`vendor_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		foreach($spisok as $sp)
			if($r['category_id'] == $sp['id']) {
				if(!$r['vendor_id'])
					continue;
				$spisok[$sp['id']]['sub_vendor'][$r['name_id']][$r['vendor_id']] = trim(_tovarVendor($r['vendor_id']));
				$spisok[$sp['id']]['sub_vendor_count'][$r['name_id']][$r['vendor_id']] = $r['count'];
				$spisok[$sp['id']]['sub_vendor_'.($filter['group'] == 2 ? 'zakaz' : 'avai')][$r['name_id']][$r['vendor_id']] = _ms($r['avai_zakaz']);
			}

	foreach($spisok as $id => $r) {
		if(!$r['count'])
			continue;
		asort($r['sub']);


		foreach($r['sub'] as $k => $i) {
			$vendor = '';
			if(isset($r['sub_vendor'][$k])) {
				asort($r['sub_vendor'][$k]);
				foreach($r['sub_vendor'][$k] as $kk => $ii)
					$vendor .=
						'<a class="sub-vendor" val="'.$id.':'.$k.':'.$kk.'">'.$ii.'</a>'.
						'<span class="ven-count">'.$r['sub_vendor_count'][$k][$kk].'</span>'.
 (@$r['sub_vendor_avai'][$k][$kk] ? '<span class="ven-avai">'.$r['sub_vendor_avai'][$k][$kk].'</span>' : '').
(@$r['sub_vendor_zakaz'][$k][$kk] ? '<span class="ven-zakaz">'.$r['sub_vendor_zakaz'][$k][$kk].'</span>' : '').
						'<br />';
				$vendor = '<div class="ven">'.$vendor.'</div>';
			}
			$r['sub'][$k] =
				'<a class="sub-unit" val="'.$k.'">'.
					($vendor ? '<div class="ven-plus'._tooltip('Показать производителей', -15, 'l').'+</div>' : '').
					$i.
					'<span class="sub-count">'.$r['sub_count'][$k].'</span>'. //количество товаров в наименовании
 (@$r['avai'][$k] ? '<span class="avai">'.$r['avai'][$k].'</span>' : '').
(@$r['zakaz'][$k] ? '<span class="zakaz">'.$r['zakaz'][$k].'</span>' : '').
				'</a>'.
				$vendor;
		}

		$send['spisok'] .=
			'<div class="tovar-category-unit" val="'.($r['id'] ? $r['id'] : -1).'">'.
				'<a class="hd">'.$r['name'].'</a>'.
				'<span class="hd-count">'.$r['count'].'</span>'.//количество товаров в категории
				implode('', $r['sub']).
			'</div>';
	}

	$send['spisok'] .= '<a id="tset" href="'.URL.'&p=setup&d=tovar&d1=category">Настроить категории товаров</a>';
	$send['spisok'] .= _next($filter + array('all'=>$all));

	return $send;
}
function _tovar_spisok($filter) {
	$cond = "!`deleted` AND `category_id` IN ("._tovarCategory('use').")";

	define('ZAKAZ_ADDED', $filter['group'] == 2);
	define('PAGE1', $filter['page'] == 1);

	if(!empty($filter['find'])) {
//		$cond .= " AND `find` LIKE '%".$filter['find']."%'";
		$engRus = _engRusChar($filter['find']);
		$cond .= " AND (`find` LIKE '%".$filter['find']."%'".
			($engRus ? " OR `find` LIKE '%".$engRus."%'" : '').")";
	}

	$JOIN = '';

	if($filter['group'] == 1)
		$JOIN = "RIGHT JOIN `_tovar_avai` `ta`
				     ON `ta`.`app_id`=".APP_ID."
				     AND `ta`.`tovar_id`=`t`.`id`
				     AND `ta`.`count`";

	if(ZAKAZ_ADDED)
		$JOIN = "RIGHT JOIN `_tovar_zakaz` `ta`
				     ON `ta`.`app_id`=".APP_ID."
				     AND `ta`.`tovar_id`=`t`.`id`";

	if($filter['category_id'])
		$cond .= " AND `category_id`=".$filter['category_id'];

	if($filter['name_id'])
		$cond .= " AND `name_id`=".$filter['name_id'];
	$nameSpisokJs = '<script>var NAME_SPISOK='._tovar_category_name($filter['category_id'], 'json').';</script>';


	if($filter['vendor_id'])
		$cond .= " AND `vendor_id`=".$filter['vendor_id'];
	$vendorSpisokJs = '<script>var VENDOR_SPISOK='._tovar_category_vendor($filter, 'json').';</script>';


	$sql = "SELECT COUNT(*) AS `all` FROM `_tovar` `t` ".$JOIN." WHERE ".$cond;
	if(!$all = query_value($sql, GLOBAL_MYSQL_CONNECT))
		return array(
			'all' => 0,
			'result' =>
				'Товаров не найдено'.
				$filter['clear'].
				$nameSpisokJs.
				$vendorSpisokJs,
			'spisok' => $filter['js'].'<div class="_empty">Товаров не найдено.</div>',
			'filter' => $filter
		);

	$filter['all'] = $all;

	$send = array(
		'all' => $all,
		'result' =>
			'Показан'._end($all, ' ', 'о ').$all.' товар'._end($all, '', 'а', 'ов').
			$filter['clear'].
			$nameSpisokJs.
			$vendorSpisokJs,
		'spisok' =>	$filter['js'],
		'filter' => $filter
	);

	$sql = "SELECT `t`.*
			FROM `_tovar` `t`
			".$JOIN."
			WHERE ".$cond."
			ORDER BY `name_id` ASC,`vendor_id` ASC,`name` ASC
			LIMIT "._startLimit($filter);
	$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	$spisok = _tovarValToList($spisok, 'id');

	$send['spisok'] .= _tovar_spisok_image($spisok, $filter);
	$send['spisok'] .= _tovar_spisok_min($spisok, $filter);

	return $send;
}
function _tovar_spisok_image($spisok, $filter) {//список товаров с картинками
	if($filter['icon_id'] != 3)
		return '';

	$spisok = _imageValToList($spisok, 'tovar_id');

	$send = '';
	foreach($spisok as $id => $r)
		$send .=
			'<div class="tovar-unit-image">'.
				'<table>'.
					'<tr><td class="img">'.$r['image_small'].
						'<td class="inf">'.
							'<a href="'.URL.'&p=tovar&d=info&id='.$id.'" class="name">'.$r['tovar_name'].'</a>'.
				'</table>'.
			'</div>';

	$send .= _next($filter + array('all'=>$filter['all']));

	return $send;
}
function _tovar_spisok_min($spisok, $filter) {//сокращённый список товаров
	if($filter['icon_id'] != 4)
		return '';


	$send = $filter['page'] == 1 ?
			'<table class="_spisok">'.
				'<tr>'.
					'<th>'.
	 (ZAKAZ_ADDED ? '<th>Заказ' : '').
					'<th>Нал.'
			: '';

	foreach($spisok as $id => $r)
		$send .=
			'<tr class="tovar-unit-min">'.
				'<td><a href="'.URL.'&p=tovar&d=info&id='.$id.'">'.$r['tovar_name_min'].'</a>'.
 (ZAKAZ_ADDED ? '<td class="zakaz">'._sumSpace($r['tovar_zakaz_count']).' <span>'.$r['tovar_measure_name'].'</span>' : '').
				'<td class="avai">'.($r['tovar_avai_count'] ? _sumSpace($r['tovar_avai_count']).' <span>'.$r['tovar_measure_name'].'</span>' : '');

	$send .= _next($filter + array('tr'=>1,'all'=>$filter['all']));

	return $send;
}


function _tovarQuery($tovar_id, $old=0) {//запрос данных об одном товаре
	$sql = "SELECT
				*,
				'' `tovar_set_name`
			FROM `_tovar`
			WHERE `id".($old ? '_old' : '')."`=".$tovar_id;
	if(!$tovar = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		return array();

	if($tovar['tovar_id_set']) {
		$sql = "SELECT *
				FROM `_tovar`
				WHERE `id`=".$tovar['tovar_id_set'];
		$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT);
		$tovar['tovar_set_name'] =
			($r['tovar_id_set'] ? '<b>'._tovarName($r['name_id']).'</b>' : _tovarName($r['name_id'])).
			'<b>'.
				_tovarVendor($r['vendor_id']).
				$r['name'].
			'</b>';

	}

	//добавление ids комплектаций
	$sql = "SELECT `equip_id`
			FROM `_tovar_equip`
			WHERE `category_id`=".$tovar['category_id']."
			  AND `name_id`=".$tovar['name_id']."
			ORDER BY `sort`";
	$tovar['equip_ids'] = query_ids($sql, GLOBAL_MYSQL_CONNECT);

	//закупка и продажа
	$sql = "SELECT *
			FROM `_tovar_cost`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar_id."
			LIMIT 1";
	$cost = query_assoc($sql, GLOBAL_MYSQL_CONNECT);
	$tovar['sum_buy'] = _cena(@$cost['sum_buy']);
	$tovar['sum_sell'] = _cena(@$cost['sum_sell']);

	return $tovar;
}
function _tovarAvaiArticul($tovar_id, $radio=0) {
	$sql = "SELECT *
			FROM `_tovar_avai`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar_id."
			ORDER BY `count` DESC";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	return _tovarAvaiArticulTab($spisok, $radio);
}
function _tovarAvaiArticulTab($spisok, $radio) {//таблица наличия товара по конкретным артикулам
	$count = count($spisok);
	$avai_id = $count == 1 ? key($spisok) : 0;

	$send =
		'<table class="_spisok tovar-avai-articul _radio"'.($radio ? ' id="ta-articul_radio"' : '').'>'.
  ($radio ? '<input type="hidden" id="ta-articul" value="'.$avai_id.'" />' : '').
			'<tr>'.
				'<th>Артикул'.
				'<th>Кол-во'.
				'<th>Закуп.<br />цена'.
				'<th>Примечание';
	foreach($spisok as $r) {
		if($radio && !$r['count'])
			continue;
		$send .=
			'<tr>'.
				'<td class="articul r">'.
		($radio ? '<div class="'.($avai_id == $r['id'] ? 'on' : 'off').'" val="'.$r['id'].'"><s></s>'.$r['articul'].'</div>'
					:
					$r['articul']
		).
				'<td class="count center"><b>'._ms($r['count']).'</b>'.
				'<td class="cena r">'._cena($r['sum_buy']).
				'<td>'.$r['about'];
	}
	$send .= '</table>';

	return $send;

}

function _tovarAvaiUpdate($tovar_id) {//обновление количества наличия товара после произведения каких-либо действий
	if(empty($tovar_id))
		return;

	$sql = "SELECT *
			FROM `_tovar_avai`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar_id."
			ORDER BY `count` DESC";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return;

	foreach($spisok as $r) {
		//поступлениe
		$sql = "SELECT IFNULL(SUM(`count`),0)
				FROM `_tovar_move`
				WHERE `tovar_avai_id`=".$r['id']."
				  AND `type_id`=1";
		$count = query_value($sql, GLOBAL_MYSQL_CONNECT);

		//расход: движение товара
		$sql = "SELECT IFNULL(SUM(`count`),0)
				FROM `_tovar_move`
				WHERE `tovar_avai_id`=".$r['id']."
				  AND `type_id`!=1";
		$count -= query_value($sql, GLOBAL_MYSQL_CONNECT);

		//применение в расходах по заявкам
		$sql = "SELECT IFNULL(SUM(`tovar_count`),0)
				FROM `_zayav_expense`
				WHERE `tovar_avai_id`=".$r['id'];
		$count -= query_value($sql, GLOBAL_MYSQL_CONNECT);

		//продажа товара - платежи
		$sql = "SELECT IFNULL(SUM(`tovar_count`),0)
				FROM `_money_income`
				WHERE !`deleted`
				  AND `tovar_avai_id`=".$r['id'];
		$count -= query_value($sql, GLOBAL_MYSQL_CONNECT);

		//счета на оплату
		$sql = "SELECT IFNULL(SUM(`count`),0)
				FROM
					`_schet` `s`,
					`_schet_content` `sc`
				WHERE `s`.`id`=`sc`.`schet_id`
				  AND !`deleted`
				  AND `tovar_avai_id`=".$r['id'];
		$count -= query_value($sql, GLOBAL_MYSQL_CONNECT);

		if($r['count'] == $count)
			continue;

		$sql = "UPDATE `_tovar_avai`
				SET `count`=".$count."
				WHERE `id`=".$r['id'];
		query($sql, GLOBAL_MYSQL_CONNECT);
	}
}
function _tovarMoveInsert($v) {//внесение движения товара
	$v = array(
		'type_id' => _num(@$v['type_id']) ? _num($v['type_id']) : 1,
		'tovar_id' => _num(@$v['tovar_id']),
		'tovar_avai_id' => _num(@$v['tovar_avai_id']),
		'count' => _ms(@$v['count']) ? _ms($v['count']) : 1,
		'cena' => _cena(@$v['cena']),
		'client_id' => _num(@$v['client_id']),
		'zayav_id' => _num(@$v['zayav_id']),
		'about' => @$v['about']
	);

	if(!$v['tovar_id'])
		return 0;

	if(!$v['tovar_avai_id'])
		return 0;

	//получение id клиента, если есть заявка
	if($v['zayav_id']) {
		$sql = "SELECT `client_id`
				FROM `_zayav`
				WHERE `id`=".$v['zayav_id'];
		$v['client_id'] = query_value($sql, GLOBAL_MYSQL_CONNECT);
	}
	
	$sql = "INSERT INTO `_tovar_move` (
				`app_id`,
				`type_id`,
				`tovar_id`,
				`tovar_avai_id`,
				`count`,
				`cena`,
				`summa`,
				`client_id`,
				`zayav_id`,
				`about`,
				`viewer_id_add`
			) VALUES (
				".APP_ID.",
				".$v['type_id'].",
				".$v['tovar_id'].",
				".$v['tovar_avai_id'].",
				".$v['count'].",
				".$v['cena'].",
				".($v['count'] * $v['cena']).",
				".$v['client_id'].",
				".$v['zayav_id'].",
				'".addslashes($v['about'])."',
				".VIEWER_ID."
			)";
	query($sql, GLOBAL_MYSQL_CONNECT);
	
	$insert_id = query_insert_id('_tovar_move', GLOBAL_MYSQL_CONNECT);

	_tovarAvaiUpdate($v['tovar_id']);
	
	return $insert_id;
}
function _tovar_info() {//информация о товаре
	if(!$tovar_id = _num($_GET['id']))
		return _err('Страницы не существует');

	$old = _bool(@$_GET['old']);//todo ссылка на товар из комментария (старая версия)

	if(!$r = _tovarQuery($tovar_id, $old))
		return _err('Товара не существует');

	if($r['deleted'])
		return _err('Товар был удалён');

	$tovar_id = $r['id'];//todo

	define('MEASURE', _tovarMeasure($r['measure_id']));

	return
	'<script type="text/javascript">'.
		'var TI={'.
			'id:'.$tovar_id.','.
			'category_id:'.$r['category_id'].','.
			'name_id:'.$r['name_id'].','.
			'vendor_id:'.$r['vendor_id'].','.
			'name:"'.$r['name'].'",'.
			'set_position_id:'.$r['set_position_id'].','.
			'tovar_id_set:'.$r['tovar_id_set'].','.
			'measure_id:'.$r['measure_id'].','.
			'measure_name:"'._tovarMeasure($r['measure_id']).'",'.
			'sum_buy:'.$r['sum_buy'].','.
			'sum_sell:'.$r['sum_sell'].','.
			'about:"'._br($r['about']).'",'.
			'feature:'._tovar_info_feature_js($tovar_id).
		'};'.
	'</script>'.
	'<div id="tovar-info">'.
		'<table id="tab">'.
			'<tr><td id="ti-left">'.
					'<div id="ti-foto">'._image200(array('tovar_id'=>$tovar_id)).'</div>'.
					'<div id="ti-link">'.
						'<a id="ti-edit" onclick="_tovarEdit()">Редактировать</a>'.
						'<a class="tovar-avai-add">Внести наличие</a>'.
						'<a>Добавить в заказ</a>'.
						'<a id="tovar-sell">Продажа</a>'.
						'<a onclick="_tovarWriteOff()">Списание</a>'.
(!_tovarDelAccess($r) ? '<a class="red" onclick="_tovarDel()">Удалить товар</a>' : '').
					'</div>'.
				'<td id="ti-right">'.
					_tovar_info_avai_cost($r).
					'<div id="category">'._tovarCategory($r['category_id']).'</div>'.
					'<div id="head">'.
						_tovarName($r['name_id']).
						_tovarVendor($r['vendor_id']).
						$r['name'].
					'</div>'.
					_tovar_info_set($r).
					_tovar_info_about($r['about']).
					_tovar_info_feature($tovar_id).

					_tovar_info_zakaz($tovar_id).
					_tovar_info_set_spisok($r).
					_tovar_info_compat($r).
					_tovar_info_zayav($tovar_id).
		'</table>'.
		'<div id="ti-move">'._tovar_info_move($tovar_id).'</div>'.
	'</div>';
}
function _tovar_info_set($tovar) {//товар, для которого эта запчасть, деталь или комплектующее
	if(!$tovar['tovar_id_set'])
		return '';

	$r = _tovarQuery($tovar['tovar_id_set']);
	return
	'<div id="set">'.
		_tovarPosition($tovar['set_position_id']).' для '.
		'<a href="'.URL.'&p=tovar&d=info&id='.$tovar['tovar_id_set'].'">'.
			_tovarName($r['name_id']).
			_tovarVendor($r['vendor_id']).
			$r['name'].
		'</a>'.
	'</div>';
}
function _tovar_info_avai_cost($tovar) {//наличие и цены товара
	$sql = "SELECT SUM(`count`)
			FROM `_tovar_avai`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar['id'];
	$avai = _ms(query_value($sql, GLOBAL_MYSQL_CONNECT));

	return
		'<table id="avai-cost">'.
			'<tr><td class="ac avai'.($avai ? '' : ' no').'">'.
					'<span>Наличие</span>'.
					($avai ?
						'<tt><b>'.$avai.'</b> '.MEASURE.'</tt>' :
						'<tt><b>-</b></tt>'
					).
					'<a class="tovar-avai-add">внести наличие</a>'.

					'<div id="avai-show">'._tovarAvaiArticul($tovar['id']).'</div>'.

		(APP_ID != 4357416 ?
				'<td class="ac buy">'.
					'<span>Закупка</span>'.
					(_cena($tovar['sum_buy']) ?
						'<tt><b>'._sumSpace($tovar['sum_buy']).'</b> руб.</tt>' :
						'<tt><b>-</b></tt>'
					).
					'<a onclick="_tovarCostSet(\'buy\')">изменить</a>'
		: '').

				'<td class="ac sell">'.
					'<span>Продажа</span>'.
					($tovar['sum_sell'] ?
						'<tt><b>'._sumSpace($tovar['sum_sell']).'</b> руб.</tt>' :
						'<tt><b>-</b></tt>'
					).
					'<a onclick="_tovarCostSet(\'sell\')">изменить</a>'.
		'</table>';
}
function _tovar_info_about($about) {//вывод описания товара, если есть
	$about = trim($about);
	if(empty($about))
		return '';
	return '<div class="_info">'._br($about).'</div>';
}
function _tovar_info_feature($tovar_id, $js=0) {//характеристики товара
	$sql = "SELECT *
			FROM `_tovar_feature`
			WHERE `tovar_id`=".$tovar_id."
			ORDER BY `id`";
	if(!$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$send = '<table id="ti-feature">';
	foreach($arr as $r) {
		$send .=
			'<tr><td class="label">'._tovarFeature($r['name_id']).':'.
				'<td>'.$r['v'];
	}
	$send .= '</table>';

	return $send;
}
function _tovar_info_feature_js($tovar_id) {
	$sql = "SELECT `name_id`,`v`
			FROM `_tovar_feature`
			WHERE `tovar_id`=".$tovar_id."
			ORDER BY `id`";
	return query_selJson($sql, GLOBAL_MYSQL_CONNECT);
}
function _tovar_info_set_spisok($tovar) {//запчасти для этого товара
	if($tovar['tovar_id_set'])
		return '';

	$sql = "SELECT *
			FROM `_tovar`
			WHERE `tovar_id_set`=".$tovar['id'];
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$send = '<h1>Запчасти:</h1>';
	$n = 1;
	foreach($spisok as $r) {
		$send .=
			'<div>'.
				$n.'. '.
				'<a href="'.URL.'&p=tovar&d=info&id='.$r['id'].'">'._tovarName($r['name_id']).'</a>'.
			'</div>';

		$n++;
	}
	return '<div id="ti-zp">'.$send.'</div>';
}
function _tovar_info_compat($tovar) {//совместимости товара
	if(!$tovar['tovar_id_compat'])
		return '';

	$sql = "SELECT *
			FROM `_tovar`
			WHERE `tovar_id_compat`=".$tovar['tovar_id_compat']."
			  AND `id`!=".$tovar['id'];
	$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT);
	$spisok = _tovarValToList($spisok, 'id');

	$send = '<h1>Совместимости:</h1>';
	$n = 1;
	foreach($spisok as $r) {
		$send .=
			'<div>'.
				$n.'. '.
				'<a href="'.URL.'&p=tovar&d=info&id='.$r['id'].'">'.
					$r['tovar_name'].
				'</a>'.
			'</div>';

		$n++;
	}
	return '<div id="ti-compat">'.$send.'</div>';
}
function _tovar_info_zayav($tovar_id) {//заявки по этому товару
	$sql = "SELECT COUNT(DISTINCT `zayav_id`)
			FROM `_zayav_tovar`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar_id;
	if(!$count = query_value($sql, GLOBAL_MYSQL_CONNECT))
		return '';
	return
		'<div id="ti-zayav">'.
			'<a href="'.URL.'&p=zayav&from_tovar_id='.$tovar_id.'">Использование в заявках: '.$count.'</a>'.
		'</div>';
}
function _tovar_info_zakaz($tovar_id) {//заказы по этому товару
	$sql = "SELECT COUNT(`id`)
			FROM `_tovar_zakaz`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar_id;
	if(!$count = query_value($sql, GLOBAL_MYSQL_CONNECT))
		return '';
	return '<div id="ti-zakaz">Заказ: '.$count.'</div>';
}
function _tovar_info_move($tovar_id) {
	$sql = "SELECT
				'move' `class`,
				`id`,
				`type_id`,
				`tovar_id`,
				`tovar_avai_id`,
				`client_id`,
				`zayav_id`,
				0 `schet_id`,
				`count`,
				`cena`,
				`summa`,
				`about`,
				`viewer_id_add`,
				`dtime_add`
			FROM `_tovar_move`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar_id;
	$move = query_arr($sql, GLOBAL_MYSQL_CONNECT);
	$move = _clientValToList($move);
	$move = _zayavValToList($move);

	//расходы по заявке
	$sql = "SELECT
				'ze' `class`,
				`id`,
				7 `type_id`,
				`tovar_id`,
				`tovar_avai_id`,
				0 `client_id`,
				`zayav_id`,
				0 `schet_id`,
				`tovar_count` `count`,
				ROUND(`sum`/`tovar_count`) `cena`,
				`sum` `summa`,
				'' `about`,
				`viewer_id_add`,
				`dtime_add`
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar_id."
			  AND `tovar_avai_id`";
	$ze = query_arr($sql, GLOBAL_MYSQL_CONNECT);
	$ze = _zayavValToList($ze);

	//продажа
	$sql = "SELECT
				'mi' `class`,
				`id`,
				3 `type_id`,
				`tovar_id`,
				`tovar_avai_id`,
				`client_id`,
				0 `zayav_id`,
				0 `schet_id`,
				`tovar_count` `count`,
				ROUND(`sum`/`tovar_count`) `cena`,
				`sum` `summa`,
				`about`,
				`viewer_id_add`,
				`dtime_add`
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar_id."
			  AND `tovar_avai_id`
			  AND !`deleted`";
	$mi = query_arr($sql, GLOBAL_MYSQL_CONNECT);
	$mi = _clientValToList($mi);

	//счёт на оплату
	$sql = "SELECT
				'schet' `class`,
				`sc`.`id`,
				8 `type_id`,
				`tovar_id`,
				`tovar_avai_id`,
				0 `client_id`,
				0 `zayav_id`,
				`schet_id`,
				`count`,
				`cost` `cena`,
				ROUND(`count`*`cost`) `summa`,
				'' `about`,
				`viewer_id_add`,
				`dtime_add`
			FROM
				`_schet` `s`,
				`_schet_content` `sc`
			WHERE `s`.`app_id`=".APP_ID."
			  AND `s`.`id`=`sc`.`schet_id`
			  AND `tovar_id`=".$tovar_id."
			  AND `tovar_avai_id`
			  AND !`s`.`deleted`";
	$schet = query_arr($sql, GLOBAL_MYSQL_CONNECT);
	$schet = _schetValToList($schet);

	$spisok = _arrayTimeGroup($move);
	$spisok += _arrayTimeGroup($ze, $spisok);
	$spisok += _arrayTimeGroup($mi, $spisok);
	$spisok += _arrayTimeGroup($schet, $spisok);
	ksort($spisok);

	if(empty($spisok))
		return '';
//		return 'Движения товара нет.';

	//получение первого года
	$yearBegin = strftime('%Y', key($spisok));
	$yearCurrent = strftime('%Y');

	krsort($spisok);

	$year = array();

	foreach($spisok as $key => $r) {
		$y = strftime('%Y', $key);
		if(!isset($year[$y]))
			$year[$y] = array();
		$year[$y][] = $r;
	}

	$send = '';
	for($y = $yearCurrent; $y >= $yearBegin; $y--) {
		$send .= _tovar_info_move_year($y, @$year[$y]);
	}


	return $send;
}
function _tovar_info_move_year($year, $spisok) {//отображение движения товара за конкретный год
	if(empty($spisok))
		return '<div class="year-empty">'.$year.'</div>';
	
	$type = array(
		1 => 'Приход',
		2 => 'Установка',   //set
		3 => 'Продажа',     //sale
		4 => 'Брак',        //defect
		5 => 'Возврат',     //return
		6 => 'Списание',    //writeoff
		7 => 'Расход в заявке',
		8 => 'Счёт на оплату'
	);


	$prihod = 0;
	$rashod = 0;

	$send = '<table class="_spisok">';
	foreach($spisok as $r) {
		$count = abs($r['count']);

		$summa = _cena($r['summa']);
		if($summa)
			$summa = '<div class="sm">'.
						($count > 1 ?
							'<a class="'._tooltip(_cena($r['cena']).' руб./'.MEASURE, 0, 'l').'<b>'._sumSpace($summa).'</b> руб.</a>'
							:
							'<b>'._sumSpace($summa).'</b> руб.'
						).
					 '</div>';
		else $summa = '';

		$class = 'plus';
		if($r['type_id'] != 1) {
			$class = 'minus';
			$rashod += $count;
		} else
			$prihod += $count;

		if($r['type_id'] == 6)
			$class = 'off';

		$send .= '<tr class="'.$class.'">'.
				'<td class="w70">'.$type[$r['type_id']].
				'<td class="w50 r">'. ($count ? '<b>'.$count.'</b> '.MEASURE : '').
				'<td class="w100 r">'.$summa.
				'<td>'.
					($r['client_id'] && !$r['zayav_id'] ? 'клиент '.$r['client_link'].'. ' : '').
					($r['zayav_id'] ? 'заявка '.$r['zayav_link'].'. ' : '').
					($r['schet_id'] ? 'счёт '.$r['schet_link_full'] : '').
					$r['about'].
				'<td class="dtime">'._dtimeAdd($r).
				'<td class="ed">'.(!$r['schet_id'] ? _iconDel($r) : '').
		'</div>';
	}

	$send .= '</table>';

	return
		'<table class="year-tab w100p">'.
			'<tr>'.
				'<td class="y">'.$year.':'.
				'<td class="prihod w150">Приход: <em>'.($prihod ? '<b>'.$prihod.'</b ' : '&nbsp;').'</em>'.
				'<td class="rashod">Расход: <em>'.($rashod ? '<b>'.$rashod.'</b> ' : '&nbsp;').'</em>'.
		'</table>'.
		'<div'.($year == strftime('%Y') ? '' : ' class="dn"').'>'.
			$send.
			'<br />'.
		'</div>';
}




function _tovarEquip($id=0, $i='') {//кеширование комплектации товаров
	$key = CACHE_PREFIX.'tovar_equip';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT * FROM `_tovar_equip_name` ORDER BY `name`";
		$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT);
		xcache_set($key, $arr, 86400);
	}
	
	if(!$id)
		return $arr;
	
	//список комплектации для select, которые были ещё не выбраны
	if($id == 'js') {
		$sel = array();//уже были выбраны для конкретного товара
		if(_num($i))
			if($t = _tovarQuery($i))
				$sel = _idsAss($t['equip_ids']);
		$ass = array();
		foreach($arr as $id => $r) {
			if(!empty($sel[$id]))
				continue;
			$ass[$id] = $r['name'];
		}
		return _sel($ass);
	}

	if($id == 'spisok') {
		if(empty($i))
			return '';
		$sel = _idsAss($i);
		$send = array();
		foreach($arr as $id => $r)
			if(isset($sel[$id]))
				$send[] = $r['name'];
		return implode(', ', $send);

	}

	if(!isset($arr[$id]))
		return _cacheErr('неизвестный id комплектации', $id);

	return $arr[$id]['name'];
}
function _tovarEquipCheck($tovar_id, $ids='') {//Получение списка комплектаций в виде чекбоксов для внесения или редактирования заявки
	// $ids - список id комплектаций через запятую, которым были поставлены галочки

	if(!$t = _tovarQuery($tovar_id))
		return '';

	$send = '';
	$sel = _idsAss($ids);

	foreach(_ids($t['equip_ids'], 1) as $equip_id)
		$send .= _check('eq'.$equip_id, _tovarEquip($equip_id), _bool(@$sel[$equip_id]), 1).'<br />';

	$send .= '<a id="equip-add">добавить...</a>'.
			 '<input type="hidden" id="equip_id" />'.
			 '<button class="vk small dn">добавить</button>';

	return $send;
}
