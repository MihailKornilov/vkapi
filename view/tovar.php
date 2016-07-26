<?php
function _tovar() {
	switch(@$_GET['d']) {
		case 'info': return _tovar_info();
	}

	$arr = array(
		0 => '��� ������',
		1 => '���� � �������',
		2 => '��������� � �����'
//		4 => '� ����'
	);

	$data = _tovar_spisok_icon(_hashFilter('tovar'));
	$v = $data['filter'];

	return
	'<div id="_tovar">'.
//		'<div id="dopLinks">'.
//			'<a class="link'.($d == 'catalog' ? ' sel' : '').'" href="'.URL.'&p=tovar&d=catalog">������� �������</a>'.
//			'<a class="link'.($d == 'provider' ? ' sel' : '').'" href="'.URL.'&p=tovar&d=provider">����������</a>'.
//		'</div>'.
		'<div class="result">'.$data['result'].'</div>'.
		'<div id="icon">'.
			'<div val="5" class="img img_tovar_stat'.($v['icon_id'] == 5 ? ' sel' : '')._tooltip('����������', -33).'</div>'.
//			'<div val="1" class="img img_tovar_group'._tooltip('������ �� �������', -57).'</div>'.
			'<div val="2" class="img img_tovar_category'.($v['icon_id'] == 2 ? ' sel' : '')._tooltip('�� ����������', -43).'</div>'.
//			'<div val="3" class="img img_tovar_foto'._tooltip('��������� ������', -100, 'r').'</div>'.
			'<div val="4" class="img img_tovar_spisok'.($v['icon_id'] == 4 ? ' sel' : '')._tooltip('������� ������', -79, 'r').'</div>'.
		'</div>'.
		'<table class="tabLR">'.
			'<tr><td class="left">'.
					'<div id="spisok">'.$data['spisok'].'</div>'.
				'<td class="right">'.
					'<div class="div-but'.($v['icon_id'] == 5 ? ' dn' : '').'">'.
						'<button class="vk fw" id="tovar-add">������ ����� �����<br />� �������</button>'.
						'<div id="find"></div>'.
						'<br />'.
						'<input type="hidden" id="icon_id" value="'.$v['icon_id'].'" />'.
						_radio('group', $arr, $v['group'], 1).
					'</div>'.

					'<div class="div-cat'.($v['icon_id'] == 5 || $v['icon_id'] == 2 ? ' dn' : '').'">'.
						'<div class="findHead">���������</div>'.
						'<input type="hidden" id="category_id" value="'.$v['category_id'].'" />'.

						'<div class="findHead">������������</div>'.
						'<input type="hidden" id="name_id" value="'.$v['name_id'].'" />'.

						'<div class="findHead">�������������</div>'.
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

	//������ id ����� ��������� (app_id = 0)
	if($id == 'noapp') {
		$ids = array();
		foreach($arr as $r) {
			if($r['app_id'])
				continue;
			$ids[] = $r['id'];
		}
		return implode(',', $ids);
	}

	//������ id ���������, ������� ������������ � ����������
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
		return _cacheErr('����������� id ���������', $id);

	if($i == 'name')
		return $arr[$id]['name'].' ';

	return _cacheErr('����������� ���� ��������� ������', $i);
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
		return _cacheErr('����������� id ������', $id);

	if($i == 'name')
		return $arr[$id]['name'].' ';

	return _cacheErr('����������� ���� ������', $i);
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
		return _cacheErr('����������� id �������������', $id);

	if($i == 'name')
		return $arr[$id]['name'].' ';

	return _cacheErr('����������� ���� �������������', $i);
}
function _tovarCategoryJs() {//��������� ������� JS
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
function _tovarVendorJs() {//������������� ������� JS
	$sql = "SELECT
				`id`,
				`name`
			FROM `_tovar_vendor`
			ORDER BY `name`";
	return query_selJson($sql, GLOBAL_MYSQL_CONNECT);
}
function _tovarPosition($id=false) {//���� ���������� � ������� ������
	$arr = array(
		0 => '',
		1 => '��������',
		2 => '�������������',
		3 => '���������',
		4 => '����������'
	);
	
	if($id === false) {
		unset($arr[0]);
		return $arr;
	}
	
	if(!isset($arr[$id]))
		return _cacheErr('����������� id ����������', $id);

	return $arr[$id];
}
function _tovarFeature($id=false, $i='name') {//�������� ������������� �������
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

	if($id == 'get_id') {//��������� id �������������� �� ��������, ���� ��� ������ ��������, �� ������� � ����
		$name = _txt($i);
		if(empty($name))
			return 0;

//      ������� ��������� �������� ��� ������� ���� ������
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
		return _cacheErr('����������� id ��������������', $id);

	if($i == 'name')
		return $arr[$id]['name'];

	return _cacheErr('����������� ���� ��������������', $i);
}
function _tovarFeatureJs() {//�������������� ������� JS
	$sql = "SELECT
				`id`,
				`name`
			FROM `_tovar_feature_name`
			ORDER BY `name`";
	return query_selJson($sql, GLOBAL_MYSQL_CONNECT);
}
function _tovarMeasure($id='all', $i='short') {//������� ��������� �������
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
		return _cacheErr('����������� id ������� ���������', $id);

	if(!isset($arr[$id][$i]))
		return _cacheErr('����������� ���� �������������', $i);

	return $arr[$id][$i];
}


function _tovarValToList($arr, $key='tovar_id', $zayav_id=0) {//������� ������ � �������� � ������ �� tovar_id
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

	//��������� ������ �������, �� ������� ��������������� ��������
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


	//������� � �������
	$sql = "SELECT *
			FROM `_tovar_cost`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id` IN (".$tovar_ids.")";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q)) {
		$tovar[$r['tovar_id']]['buy'] = $r['sum_buy'];
		$tovar[$r['tovar_id']]['sell'] = $r['sum_sell'];
	}


	//������� ������
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

	//��������
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

	//����� ������
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


	//������������ ��������
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
					($set ? '<br />��� '.$t['set_name'] : '').
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
				'<div class="headName">����� �� �������:</div>'.
				'<table class="tsa-tab bs5 w100p">'.
					'<tr><td class="top w50">'.$t['image_small'].
						'<td class="top name">'.$arr[$id]['tovar_select'].
				'</table>'.
				$t['articul'].
				'<table class="tsa-bottom bs10 w100p'.($t['articul_count'] == 1 ? '' : ' dn').'">'.
					'<tr><td><td>'.
					'<tr><td class="label r w70">����������:*'.
						'<td><input type="text" class="w50" id="tsa-count" value="1" /> '.
							$arr[$id]['tovar_measure_name'].
							'<span>(max: <b class="max">'.$t['articul_count_first'].'</b>)</span>'.
					'<tr><td>'.
						'<td><button class="vk submit">��������</button>'.
							'<button class="vk cancel">������� ������ �����</button>'.
				'</table>'.
			'</div>';
		$arr[$id]['tovar_articul_arr'] = $t['articul_arr'];
	}

	return $arr;
}
function _tovarArticulCreate() {//������������ ���������� �������� ������
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

function _tovarDelAccess($r) {//���������� �� �������� ������
	$tovar_id = $r['id'];

	if(!$r['app_id'])
		return '����� ��� ������ �� � ���� ����������';

	$sql = "SELECT COUNT(*) FROM `_tovar` WHERE `id`=".$r['tovar_id_set'];
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return '���� ����� ����������� � ������� ������';

	$sql = "SELECT COUNT(*) FROM `_money_income` WHERE !`deleted` AND `tovar_id`=".$tovar_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return '������������� ������� ������';

	$sql = "SELECT COUNT(*) FROM `_schet_content` WHERE `tovar_id`=".$tovar_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return '����� ������������ � ������ �� ������';

	$sql = "SELECT COUNT(*) FROM `_tovar_avai` WHERE `tovar_id`=".$tovar_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return '����� ���� � �������';

	$sql = "SELECT COUNT(*) FROM `_tovar_move` WHERE `tovar_id`=".$tovar_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return '� ������ ���� ������� ��������';

	$sql = "SELECT COUNT(*) FROM `_zayav_expense` WHERE `tovar_id`=".$tovar_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return '����� ������������ � �������� �� �������';

	$sql = "SELECT COUNT(*) FROM `_zayav_tovar` WHERE `tovar_id`=".$tovar_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return '����� ������������ � �������';

	//������� �� ������ �������� ���
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
			$filter['clear'] = '<a id="filter_clear">�������� ������</a>';
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

function _tovar_category_name($category_id, $i='arr') {//������ ������������ �� ��������� ���������
/*
	$i - �������� ��������:
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
function _tovar_category_vendor($filter, $i='arr') {//������ �������������� �� ��������� ���������
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

function _tovar_stat($filter) {//���������� �� �������
	$sql = "SELECT
				`category_id` `id`,
				0 `pos`,
				0 `count`,
				0 `sum`
			FROM `_tovar_category_use`
			WHERE `app_id`=".APP_ID."
			ORDER BY `sort`";
	$cat = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	//����� � ������ ��� ������ ���������
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
					($r['pos'] ? '<span class="pos'._tooltip('�������', -31).$r['pos'].'</span>' : '').
				'<td class="r">'.($r['sum'] ? _sumSpace($r['sum']).' ���.' : '');


	$spisok =
	$filter['js'].
	'<div id="tovar-stat">'.
		'<div class="headName">������� �������</div>'.
		'<table class="_spisok">'.
			'<tr><th>���������'.
				'<th>����������'.
				'<th>�����'.
			$catSpisok.
			'<tr><td class="r"><b>�����:</b>'.
				'<td>'.
				'<td class="r"><b>'.($summa ? _sumSpace($summa).'</b> ���.' : '').
		'</table>'.
	'</div>';

	return array(
		'result' => '���������� �� �������',
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
			'result' => '��������� �� ����������'.$filter['clear'],
			'spisok' => $filter['js'].
						'<div class="_empty">'.
							'��������� ������� �� ���������.'.
							'<a href="'.URL.'&p=setup&d=tovar&d1=category">���������</a>'.
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
		'result' => '����� '.$all.' ��������'._end($all, '�', '�', '�').' �������'.$filter['clear'],
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

	//���������� ������� � ������ ���������
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
	$spisok[0]['name'] = '��� ���������';
	$spisok[0]['category_id'] = 0;
	$spisok[0]['sub'] = '';
	$spisok[0]['id'] = 0;
*/

	//���������� ������� � ������� �� ������� ������������
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

	//���������� ������� � ������� �� ������� ������������
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
					($vendor ? '<div class="ven-plus'._tooltip('�������� ��������������', -15, 'l').'+</div>' : '').
					$i.
					'<span class="sub-count">'.$r['sub_count'][$k].'</span>'. //���������� ������� � ������������
 (@$r['avai'][$k] ? '<span class="avai">'.$r['avai'][$k].'</span>' : '').
(@$r['zakaz'][$k] ? '<span class="zakaz">'.$r['zakaz'][$k].'</span>' : '').
				'</a>'.
				$vendor;
		}

		$send['spisok'] .=
			'<div class="tovar-category-unit" val="'.($r['id'] ? $r['id'] : -1).'">'.
				'<a class="hd">'.$r['name'].'</a>'.
				'<span class="hd-count">'.$r['count'].'</span>'.//���������� ������� � ���������
				implode('', $r['sub']).
			'</div>';
	}

	$send['spisok'] .= '<a id="tset" href="'.URL.'&p=setup&d=tovar&d1=category">��������� ��������� �������</a>';
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
				'������� �� �������'.
				$filter['clear'].
				$nameSpisokJs.
				$vendorSpisokJs,
			'spisok' => $filter['js'].'<div class="_empty">������� �� �������.</div>',
			'filter' => $filter
		);

	$filter['all'] = $all;

	$send = array(
		'all' => $all,
		'result' =>
			'�������'._end($all, ' ', '� ').$all.' �����'._end($all, '', '�', '��').
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
function _tovar_spisok_image($spisok, $filter) {//������ ������� � ����������
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
function _tovar_spisok_min($spisok, $filter) {//����������� ������ �������
	if($filter['icon_id'] != 4)
		return '';


	$send = $filter['page'] == 1 ?
			'<table class="_spisok">'.
				'<tr>'.
					'<th>'.
	 (ZAKAZ_ADDED ? '<th>�����' : '').
					'<th>���.'
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


function _tovarQuery($tovar_id, $old=0) {//������ ������ �� ����� ������
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

	//���������� ids ������������
	$sql = "SELECT `equip_id`
			FROM `_tovar_equip`
			WHERE `category_id`=".$tovar['category_id']."
			  AND `name_id`=".$tovar['name_id']."
			ORDER BY `sort`";
	$tovar['equip_ids'] = query_ids($sql, GLOBAL_MYSQL_CONNECT);

	//������� � �������
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
function _tovarAvaiArticulTab($spisok, $radio) {//������� ������� ������ �� ���������� ���������
	$count = count($spisok);
	$avai_id = $count == 1 ? key($spisok) : 0;

	$send =
		'<table class="_spisok tovar-avai-articul _radio"'.($radio ? ' id="ta-articul_radio"' : '').'>'.
  ($radio ? '<input type="hidden" id="ta-articul" value="'.$avai_id.'" />' : '').
			'<tr>'.
				'<th>�������'.
				'<th>���-��'.
				'<th>�����.<br />����'.
				'<th>����������';
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

function _tovarAvaiUpdate($tovar_id) {//���������� ���������� ������� ������ ����� ������������ �����-���� ��������
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
		//����������e
		$sql = "SELECT IFNULL(SUM(`count`),0)
				FROM `_tovar_move`
				WHERE `tovar_avai_id`=".$r['id']."
				  AND `type_id`=1";
		$count = query_value($sql, GLOBAL_MYSQL_CONNECT);

		//������: �������� ������
		$sql = "SELECT IFNULL(SUM(`count`),0)
				FROM `_tovar_move`
				WHERE `tovar_avai_id`=".$r['id']."
				  AND `type_id`!=1";
		$count -= query_value($sql, GLOBAL_MYSQL_CONNECT);

		//���������� � �������� �� �������
		$sql = "SELECT IFNULL(SUM(`tovar_count`),0)
				FROM `_zayav_expense`
				WHERE `tovar_avai_id`=".$r['id'];
		$count -= query_value($sql, GLOBAL_MYSQL_CONNECT);

		//������� ������ - �������
		$sql = "SELECT IFNULL(SUM(`tovar_count`),0)
				FROM `_money_income`
				WHERE !`deleted`
				  AND `tovar_avai_id`=".$r['id'];
		$count -= query_value($sql, GLOBAL_MYSQL_CONNECT);

		//����� �� ������
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
function _tovarMoveInsert($v) {//�������� �������� ������
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

	//��������� id �������, ���� ���� ������
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
function _tovar_info() {//���������� � ������
	if(!$tovar_id = _num($_GET['id']))
		return _err('�������� �� ����������');

	$old = _bool(@$_GET['old']);//todo ������ �� ����� �� ����������� (������ ������)

	if(!$r = _tovarQuery($tovar_id, $old))
		return _err('������ �� ����������');

	if($r['deleted'])
		return _err('����� ��� �����');

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
						'<a id="ti-edit" onclick="_tovarEdit()">�������������</a>'.
						'<a class="tovar-avai-add">������ �������</a>'.
						'<a>�������� � �����</a>'.
						'<a id="tovar-sell">�������</a>'.
						'<a onclick="_tovarWriteOff()">��������</a>'.
(!_tovarDelAccess($r) ? '<a class="red" onclick="_tovarDel()">������� �����</a>' : '').
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
function _tovar_info_set($tovar) {//�����, ��� �������� ��� ��������, ������ ��� �������������
	if(!$tovar['tovar_id_set'])
		return '';

	$r = _tovarQuery($tovar['tovar_id_set']);
	return
	'<div id="set">'.
		_tovarPosition($tovar['set_position_id']).' ��� '.
		'<a href="'.URL.'&p=tovar&d=info&id='.$tovar['tovar_id_set'].'">'.
			_tovarName($r['name_id']).
			_tovarVendor($r['vendor_id']).
			$r['name'].
		'</a>'.
	'</div>';
}
function _tovar_info_avai_cost($tovar) {//������� � ���� ������
	$sql = "SELECT SUM(`count`)
			FROM `_tovar_avai`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar['id'];
	$avai = _ms(query_value($sql, GLOBAL_MYSQL_CONNECT));

	return
		'<table id="avai-cost">'.
			'<tr><td class="ac avai'.($avai ? '' : ' no').'">'.
					'<span>�������</span>'.
					($avai ?
						'<tt><b>'.$avai.'</b> '.MEASURE.'</tt>' :
						'<tt><b>-</b></tt>'
					).
					'<a class="tovar-avai-add">������ �������</a>'.

					'<div id="avai-show">'._tovarAvaiArticul($tovar['id']).'</div>'.

		(APP_ID != 4357416 ?
				'<td class="ac buy">'.
					'<span>�������</span>'.
					(_cena($tovar['sum_buy']) ?
						'<tt><b>'._sumSpace($tovar['sum_buy']).'</b> ���.</tt>' :
						'<tt><b>-</b></tt>'
					).
					'<a onclick="_tovarCostSet(\'buy\')">��������</a>'
		: '').

				'<td class="ac sell">'.
					'<span>�������</span>'.
					($tovar['sum_sell'] ?
						'<tt><b>'._sumSpace($tovar['sum_sell']).'</b> ���.</tt>' :
						'<tt><b>-</b></tt>'
					).
					'<a onclick="_tovarCostSet(\'sell\')">��������</a>'.
		'</table>';
}
function _tovar_info_about($about) {//����� �������� ������, ���� ����
	$about = trim($about);
	if(empty($about))
		return '';
	return '<div class="_info">'._br($about).'</div>';
}
function _tovar_info_feature($tovar_id, $js=0) {//�������������� ������
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
function _tovar_info_set_spisok($tovar) {//�������� ��� ����� ������
	if($tovar['tovar_id_set'])
		return '';

	$sql = "SELECT *
			FROM `_tovar`
			WHERE `tovar_id_set`=".$tovar['id'];
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$send = '<h1>��������:</h1>';
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
function _tovar_info_compat($tovar) {//������������� ������
	if(!$tovar['tovar_id_compat'])
		return '';

	$sql = "SELECT *
			FROM `_tovar`
			WHERE `tovar_id_compat`=".$tovar['tovar_id_compat']."
			  AND `id`!=".$tovar['id'];
	$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT);
	$spisok = _tovarValToList($spisok, 'id');

	$send = '<h1>�������������:</h1>';
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
function _tovar_info_zayav($tovar_id) {//������ �� ����� ������
	$sql = "SELECT COUNT(DISTINCT `zayav_id`)
			FROM `_zayav_tovar`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar_id;
	if(!$count = query_value($sql, GLOBAL_MYSQL_CONNECT))
		return '';
	return
		'<div id="ti-zayav">'.
			'<a href="'.URL.'&p=zayav&from_tovar_id='.$tovar_id.'">������������� � �������: '.$count.'</a>'.
		'</div>';
}
function _tovar_info_zakaz($tovar_id) {//������ �� ����� ������
	$sql = "SELECT COUNT(`id`)
			FROM `_tovar_zakaz`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar_id;
	if(!$count = query_value($sql, GLOBAL_MYSQL_CONNECT))
		return '';
	return '<div id="ti-zakaz">�����: '.$count.'</div>';
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

	//������� �� ������
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

	//�������
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

	//���� �� ������
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
//		return '�������� ������ ���.';

	//��������� ������� ����
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
function _tovar_info_move_year($year, $spisok) {//����������� �������� ������ �� ���������� ���
	if(empty($spisok))
		return '<div class="year-empty">'.$year.'</div>';
	
	$type = array(
		1 => '������',
		2 => '���������',   //set
		3 => '�������',     //sale
		4 => '����',        //defect
		5 => '�������',     //return
		6 => '��������',    //writeoff
		7 => '������ � ������',
		8 => '���� �� ������'
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
							'<a class="'._tooltip(_cena($r['cena']).' ���./'.MEASURE, 0, 'l').'<b>'._sumSpace($summa).'</b> ���.</a>'
							:
							'<b>'._sumSpace($summa).'</b> ���.'
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
					($r['client_id'] && !$r['zayav_id'] ? '������ '.$r['client_link'].'. ' : '').
					($r['zayav_id'] ? '������ '.$r['zayav_link'].'. ' : '').
					($r['schet_id'] ? '���� '.$r['schet_link_full'] : '').
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
				'<td class="prihod w150">������: <em>'.($prihod ? '<b>'.$prihod.'</b ' : '&nbsp;').'</em>'.
				'<td class="rashod">������: <em>'.($rashod ? '<b>'.$rashod.'</b> ' : '&nbsp;').'</em>'.
		'</table>'.
		'<div'.($year == strftime('%Y') ? '' : ' class="dn"').'>'.
			$send.
			'<br />'.
		'</div>';
}




function _tovarEquip($id=0, $i='') {//����������� ������������ �������
	$key = CACHE_PREFIX.'tovar_equip';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT * FROM `_tovar_equip_name` ORDER BY `name`";
		$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT);
		xcache_set($key, $arr, 86400);
	}
	
	if(!$id)
		return $arr;
	
	//������ ������������ ��� select, ������� ���� ��� �� �������
	if($id == 'js') {
		$sel = array();//��� ���� ������� ��� ����������� ������
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
		return _cacheErr('����������� id ������������', $id);

	return $arr[$id]['name'];
}
function _tovarEquipCheck($tovar_id, $ids='') {//��������� ������ ������������ � ���� ��������� ��� �������� ��� �������������� ������
	// $ids - ������ id ������������ ����� �������, ������� ���� ���������� �������

	if(!$t = _tovarQuery($tovar_id))
		return '';

	$send = '';
	$sel = _idsAss($ids);

	foreach(_ids($t['equip_ids'], 1) as $equip_id)
		$send .= _check('eq'.$equip_id, _tovarEquip($equip_id), _bool(@$sel[$equip_id]), 1).'<br />';

	$send .= '<a id="equip-add">��������...</a>'.
			 '<input type="hidden" id="equip_id" />'.
			 '<button class="vk small dn">��������</button>';

	return $send;
}
