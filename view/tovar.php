<?php
function _tovar() {
	switch(@$_GET['d']) {
		case 'info': return _tovar_info();
	}

	$d = empty($_GET['d']) ? 'category' : $_GET['d'];

	$arr = array(
		0 => 'Все товары',
		1 => 'Есть в наличии',
//		2 => 'Нет в наличии',
		3 => 'Заказано'
	);

	$data = _tovar_spisok_icon();

	return
	'<div id="_tovar">'.
//		'<div id="dopLinks">'.
//			'<a class="link'.($d == 'catalog' ? ' sel' : '').'" href="'.URL.'&p=tovar&d=catalog">Каталог товаров</a>'.
//			'<a class="link'.($d == 'provider' ? ' sel' : '').'" href="'.URL.'&p=tovar&d=provider">Поставщики</a>'.
//		'</div>'.
		'<div class="result">'.$data['result'].'</div>'.
		'<div id="icon">'.
//			'<div val="1" class="img img_tovar_group'._tooltip('Товары по группам', -57).'</div>'.
			'<div val="2" class="img img_tovar_category sel'._tooltip('По категориям', -43).'</div>'.
			'<div val="3" class="img img_tovar_foto'._tooltip('Подробный список', -100, 'r').'</div>'.
			'<div val="4" class="img img_tovar_spisok'._tooltip('Краткий список', -79, 'r').'</div>'.
		'</div>'.
		'<table class="tabLR">'.
			'<tr><td class="left">'.
					'<div id="spisok">'.$data['spisok'].'</div>'.
				'<td class="right">'.
					'<button class="vk fw" id="tovar-add">Внести новый товар<br />в каталог</button>'.
					'<div id="find"></div>'.
					'<br />'.
					'<input type="hidden" id="icon_id" value="2" />'.
					_radio('group', $arr, 0, 1).

					'<div class="div-cat dn">'.
						'<div class="findHead">Категория</div>'.
						'<input type="hidden" id="category_id" />'.

						'<div class="findHead">Наименование</div>'.
						'<input type="hidden" id="name_id" />'.

						'<div class="findHead">Производитель</div>'.
						'<input type="hidden" id="vendor_id" />'.
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
function _tovarMeasure($id=0) {//единицы изменения
	$arr = array(
		1 => 'шт.',
		2 => 'м.',
		3 => 'мм.'
	);
	
	if(!$id)
		return $arr;
	
	if(!isset($arr[$id]))
		return _cacheErr('неизвестный id единицы измерения', $id);
	return $arr[$id];
}


function _tovarValToList($arr, $keyName='tovar_id') {//вставка ссылок на файлы в массив по tovar_id
	$ids = array();
	$arrIds = array();
	foreach($arr as $key => $r) {
		$arr[$r['id']]['tovar_set'] = '';
		$arr[$r['id']]['tovar_set_name'] = '';
		if(!empty($r[$keyName])) {
			$ids[$r[$keyName]] = 1;
			$arrIds[$r[$keyName]][] = $key;
		}
	}

	if(empty($ids))
		return $arr;

	$sql = "SELECT *
			FROM `_tovar`
			WHERE `id` IN (".implode(',', array_keys($ids)).")";
	$tovar = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	//получение данных товаров, на которые устанавливается запчасть
	$set = array();
	if($ids = _idsGet($tovar, 'tovar_id_set')) {
		$sql = "SELECT *
				FROM `_tovar`
				WHERE `id` IN (".$ids.")";
		foreach(query_arr($sql, GLOBAL_MYSQL_CONNECT) as $r)
			$set[$r['id']] =
				_tovarName($r['name_id']).
				_tovarVendor($r['vendor_id']).
				$r['name'];
	}

	foreach($tovar as $r) {
		foreach($arrIds[$r['id']] as $id) {
			$arr[$id]['tovar_measure_name'] = _tovarMeasure($r['measure_id']);
			$arr[$id]['tovar_set'] =
				'<a class="tovar-info-go" val="'.$r['id'].'">'.
					($r['tovar_id_set'] ? '<b>'._tovarName($r['name_id']).'</b>' : _tovarName($r['name_id'])).
					'<b>'._tovarVendor($r['vendor_id']).'</b>'.
					($r['tovar_id_set'] ? $r['name'] : '<b>'.$r['name'].'</b>').

					($r['tovar_id_set'] ? '<br />для '.$set[$r['tovar_id_set']] : '').
				'</a>';

			$arr[$id]['tovar_sale'] =
				'<div class="tovar-info-go tovar-sale" val="'.$r['id'].'">'.
					'<div class="cat">'._tovarCategory($r['category_id']).'</div>'.
					_tovarName($r['name_id']).
					'<b>'._tovarVendor($r['vendor_id']).
						  $r['name'].
					'</b>:'.
					'<b class="count">'.@$arr[$id]['tovar_count'].' '._tovarMeasure($r['measure_id']).'</b>'.
				'</div>';

			$arr[$id]['tovar_set_name'] =
				($r['tovar_id_set'] ? '<b>'._tovarName($r['name_id']).'</b>' : _tovarName($r['name_id'])).
				'<b>'.
					_tovarVendor($r['vendor_id']).
					$r['name'].
				'</b>';

			$arr[$id]['tovar_name'] =
				_tovarName($r['name_id']).
				_tovarVendor($r['vendor_id']).
				$r['name'];

			$arr[$id]['tovar_link'] = '<a class="tovar-info-go" val="'.$r['id'].'">'.$arr[$id]['tovar_name'].'</a>';
		}
	}

	return $arr;
}
function _tovarAvaiToList($arr) {
	if(empty($arr))
		return array();
	
	foreach($arr as $r)
		$arr[$r['id']]['avai_count'] = 0;

	$ids = _idsGet($arr);
	$sql = "SELECT
				`tovar_id`,
				SUM(`count`) `count`
			FROM `_tovar_avai`
			WHERE `tovar_id` IN (".$ids.")
			GROUP BY `tovar_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$arr[$r['tovar_id']]['avai_count'] = $r['count'];

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

function _tovarFilter($v) {
	return array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 50,
		'icon_id' => _num(@$v['icon_id']) ? $v['icon_id'] : 2,
		'find' => trim(@$v['find']),
		'group' => _num(@$v['group']),
		'category_id' => intval(@$v['category_id']),
		'name_id' => intval(@$v['name_id']),
		'vendor_id' => intval(@$v['vendor_id'])
	);
}
function _tovar_spisok_icon($v=array()) {
	$filter = _tovarFilter($v);
	$filter = _filterJs('TOVAR', $filter);

	switch($filter['icon_id']) {
		case 2:
		default: return _tovar_category_spisok($filter);
		case 3: return _tovar_spisok($filter);
		case 4: return _tovar_spisok($filter);
	}
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
			'result' => 'Категории не определены',
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
		$spisok[$r['id']]['count'] = 0;
	}

	$all = count($spisok);
	$send = array(
		'all' => $all,
		'result' => 'Всего '.$all.' категори'._end($all, 'я', 'и', 'й').' товаров',
		'spisok' => $filter['js'],
		'filter' => $filter
	);

	$cond = "`category_id` IN ("._idsGet($spisok).")";

	if(!empty($filter['find'])) {
//		$cond .= " AND `find` LIKE '%".$filter['find']."%'";
		$engRus = _engRusChar($filter['find']);
		$cond .= " AND (`find` LIKE '%".$filter['find']."%'".
			($engRus ? " OR `find` LIKE '%".$engRus."%'" : '').")";
	}

	$RJ_AVAI = $filter['group'] ?
					"RIGHT JOIN `_tovar_avai` `ta`
				     ON `ta`.`tovar_id`=`t`.`id` AND `ta`.`count`"
					:
					"LEFT JOIN `_tovar_avai` `ta`
					 ON `ta`.`tovar_id`=`t`.`id`";


	//количество товаров в каждой категории
	$sql = "SELECT
				`category_id`,
				COUNT(`t`.`id`) `count`
			FROM `_tovar` `t`
			".$RJ_AVAI."
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
				SUM(`ta`.`count`) `avai`
			FROM `_tovar` `t`
			".$RJ_AVAI."
			WHERE ".$cond."
			GROUP BY `category_id`,`name_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		foreach($spisok as $sp)
			if($r['category_id'] == $sp['id']) {
				$spisok[$sp['id']]['sub'][$r['name_id']] = trim(_tovarName($r['name_id']));
				$spisok[$sp['id']]['sub_count'][$r['name_id']] = $r['count'];
				$spisok[$sp['id']]['avai'][$r['name_id']] = $r['avai'];
			}

	foreach($spisok as $id => $r) {
		if(!$r['count'])
			continue;
		asort($r['sub']);
		foreach($r['sub'] as $k => $i)
			$r['sub'][$k] =
				'<a class="sub-unit" val="'.$k.'">'.
					$i.
					'<span class="sub-count">'.$r['sub_count'][$k].'</span>'.
  ($r['avai'][$k] ? '<span class="avai">'.$r['avai'][$k].'</span>' : '').
				'</a>';
		$send['spisok'] .=
			'<div class="tovar-category-unit" val="'.($r['id'] ? $r['id'] : -1).'">'.
				'<a class="hd">'.$r['name'].'</a>'.
				'<span class="hd-count">'.$r['count'].'</span>'.
				implode('', $r['sub']).
			'</div>';
	}

	$send['spisok'] .= '<a id="tset" href="'.URL.'&p=setup&d=tovar&d1=category">Настроить категории товаров</a>';
	$send['spisok'] .= _next($filter + array('all'=>$all));

	return $send;
}
function _tovar_spisok($filter) {
	$cond = "`category_id` IN ("._tovarCategory('use').")";

	if(!empty($filter['find'])) {
//		$cond .= " AND `find` LIKE '%".$filter['find']."%'";
		$engRus = _engRusChar($filter['find']);
		$cond .= " AND (`find` LIKE '%".$filter['find']."%'".
			($engRus ? " OR `find` LIKE '%".$engRus."%'" : '').")";
	}

	$RJ_AVAI = $filter['group'] ?
					"RIGHT JOIN `_tovar_avai` `ta`
				     ON `ta`.`tovar_id`=`t`.`id` AND `ta`.`count`"
				: '';

	if($filter['category_id'])
		$cond .= " AND `category_id`=".($filter['category_id'] == -1 ? 0 : $filter['category_id']);
	if($filter['name_id'])
		$cond .= " AND `name_id`=".$filter['name_id'];
	if($filter['vendor_id'])
		$cond .= " AND `vendor_id`=".$filter['vendor_id'];


	$sql = "SELECT COUNT(*) AS `all` FROM `_tovar` `t` ".$RJ_AVAI." WHERE ".$cond;
	if(!$all = query_value($sql, GLOBAL_MYSQL_CONNECT))
		return array(
			'all' => 0,
			'result' => 'Товаров не найдено',
			'spisok' => $filter['js'].'<div class="_empty">Товаров не найдено.</div>',
			'filter' => $filter
		);

	$filter['all'] = $all;

	$send = array(
		'all' => $all,
		'result' => 'Показан'._end($all, ' ', 'о ').$all.' товар'._end($all, '', 'а', 'ов'),
		'spisok' => $filter['js'],
		'filter' => $filter
	);

	$sql = "SELECT `t`.*
			FROM `_tovar` `t`
			".$RJ_AVAI."
			WHERE ".$cond."
			ORDER BY `name_id` ASC,`vendor_id` ASC,`name` ASC
			LIMIT "._startLimit($filter);
	$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	$spisok = _tovarValToList($spisok, 'tovar_id_set');
	$spisok = _tovarAvaiToList($spisok);

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
							'<a href="'.URL.'&p=tovar&d=info&id='.$id.'" class="name">'.
								_tovarName($r['name_id']).
								'<b>'.
									_tovarVendor($r['vendor_id']).
									$r['name'].
								'</b>'.
								($r['tovar_id_set'] ? '<div class="tovar-set">'.$r['tovar_set_name'].'</div>' : '').
							'</a>'.
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
				'<tr><th><th>Нал.'
			: '';

	foreach($spisok as $id => $r)
		$send .=
			'<tr class="tovar-unit-min">'.
				'<td>'.
					'<a href="'.URL.'&p=tovar&d=info&id='.$id.'">'.
						_tovarName($r['name_id']).
						'<b>'.
							_tovarVendor($r['vendor_id']).
							$r['name'].
						'</b>'.
						($r['tovar_id_set'] ? '<div class="tovar-set">'.$r['tovar_set_name'].'</div>' : '').
					'</a>'.
				'<td class="avai">'.($r['avai_count'] ? $r['avai_count'] : '');

	$send .= _next($filter + array('tr'=>1,'all'=>$filter['all']));

	return $send;
}


function _tovarQuery($tovar_id, $old=0) {//запрос данных об одном товаре
	$sql = "SELECT
				*,
				'' `tovar_set_name`
			FROM `_tovar`
			WHERE `id".($old ? '_old' : '')."`=".$tovar_id;
	$tovar = query_assoc($sql, GLOBAL_MYSQL_CONNECT);

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
function _tovarAvaiArticul($tovar_id, $radio=0) {//таблица наличия товара по конкретным артикулам
	$sql = "SELECT *
			FROM `_tovar_avai`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar_id."
			ORDER BY `count` DESC";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$send =
		'<table class="_spisok tovar-avai-articul _radio"'.($radio ? ' id="ta-articul_radio"' : '').'>'.
  ($radio ? '<input type="hidden" id="ta-articul" />' : '').
			'<tr>'.
	  ($radio ? '<th>' : '').
				'<th>Артикул'.
				'<th>Кол-во'.
				'<th>Закуп.<br />цена'.
				'<th>Примечание';
	foreach($spisok as $r) {
		if($radio && !$r['count'])
			continue;
		$send .=
			'<tr>'.
	  ($radio ? '<td class="rs"><div class="off" val="'.$r['id'].'"><s></s></div>' : '').
				'<td class="articul r">'.$r['articul'].
				'<td class="count center"><b>'.$r['count'].'</b>'.
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

		//движение в информации о товаре
		$sql = "SELECT IFNULL(SUM(`count`),0)
				FROM `_tovar_move`
				WHERE `tovar_avai_id`=".$r['id']."
				  AND `type_id`!=1";
		$count -= query_value($sql, GLOBAL_MYSQL_CONNECT);

		//применение в расходах по заявкам
		$sql = "SELECT COUNT(`id`)
				FROM `_zayav_expense`
				WHERE `tovar_avai_id`=".$r['id'];
		$count -= query_value($sql, GLOBAL_MYSQL_CONNECT);

		//продажа товара - платежи
		$sql = "SELECT SUM(`tovar_count`)
				FROM `_money_income`
				WHERE !`deleted`
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
		'count' => _num(@$v['count']) ? _num($v['count']) : 1,
		'cena' => _cena(@$v['cena']),
		'client_id' => _num(@$v['client_id']),
		'zayav_id' => _num(@$v['zayav_id'])
	);

	if(!$v['tovar_id'])
		return 0;

	if(!$v['tovar_avai_id'])
		return 0;

	if(!$v['cena']) {
		$sql = "SELECT `sum_buy`
				FROM `_tovar_avai`
				WHERE `id`=".$v['tovar_avai_id'];
		$v['cena'] = query_value($sql, GLOBAL_MYSQL_CONNECT);
	}
	
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
						'<a>Заказать</a>'.
						'<a id="tovar-sell">Продажа</a>'.
						'<a>Списание</a>'.
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
					_tovar_info_move($tovar_id).
		'</table>'.
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
	$avai = query_value($sql, GLOBAL_MYSQL_CONNECT);

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
	$spisok = _tovarValToList($spisok, 'tovar_id_set');

	$send = '<h1>Совместимости:</h1>';
	$n = 1;
	foreach($spisok as $r) {
		$send .=
			'<div>'.
				$n.'. '.
				'<a href="'.URL.'&p=tovar&d=info&id='.$r['id'].'">'.
					_tovarName($r['name_id']).
					$r['tovar_set_name'].
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
	return '<div id="ti-zayav">Заявки: '.$count.'</div>';
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

	$spisok = _arrayTimeGroup($move);
	$spisok += _arrayTimeGroup($ze, $spisok);
	$spisok += _arrayTimeGroup($mi, $spisok);
	krsort($spisok);

	if(empty($spisok))
		return '<div id="ti-move">Движения товара нет.</div>';

	$type = array(
		1 => 'Приход',
		2 => 'Установка',   //set
		3 => 'Продажа',     //sale
		4 => 'Брак',        //defect
		5 => 'Возврат',     //return
		6 => 'Списание',    //writeoff
		7 => 'Расход<br />в заявке'
	);

	$send =
		'<table id="ti-move" class="_spisok">'.
			'<th>Действие'.
			'<th class="count-sum">Кол-во<br>Сумма'.
			'<th>Описание'.
			'<th>Дата'.
			'<th>';
	$c = 0;
	foreach($spisok as $r) {
		$count = abs($r['count']);

		$summa = _cena($r['summa']);
		if($summa)
			$summa = '<div class="sm">'.
						($count > 1 ?
							'<a class="'._tooltip(_cena($r['cena']).' руб./'.MEASURE, 0, 'l').'<b>'.$summa.'</b> руб.</a>'
							:
							'<b>'.$summa.'</b> руб.'
						).
					 '</div>';
		else $summa = '';

		$send .= '<tr class="'.($r['type_id'] == 1 ? 'plus' : 'minus').'">'.
				'<td>'.$type[$r['type_id']].
				'<td class="count-sum r">'.
					($count ? '<b>'.$count.'</b> '.MEASURE : '').
					$summa.
				'<td>'.
					($r['client_id'] && !$r['zayav_id'] ? 'клиент '.$r['client_link'].'. ' : '').
					($r['zayav_id'] ? 'заявка '.$r['zayav_link'].'. ' : '').
					$r['about'].
				'<td class="dtime">'._dtimeAdd($r).
				'<td class="ed">'._iconDel(array('dtime_add'=>(!$c++ && $r['class'] == 'move' ? '' : $r['dtime_add'])) + $r).
		'</div>';
	}

	$send .= '</table>';

	return $send;
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
