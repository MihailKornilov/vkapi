<?php
function _tovar_script() {//������� ��� �������
	return
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/tovar/tovar'.MIN.'.css?'.VERSION.'" />'.
		'<script src="'.API_HTML.'/modul/tovar/tovar'.MIN.'.js?'.VERSION.'"></script>';
}


function _tovarCategory($id=false, $i='name') {
	$key = CACHE_PREFIX.'tovar_category';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					*,
					0 `count`
				FROM `_tovar_category`
				WHERE `app_id`=".APP_ID."
				ORDER BY `sort`";
		$arr = query_arr($sql);
		
		//���������� ������� ��� �������� ���������
		$sql = "SELECT
					`category_id` `id`,
					COUNT(*) `count`
				FROM `_tovar_bind`
				WHERE `app_id`=".APP_ID."
				GROUP BY `category_id`";
		foreach(query_arr($sql) as $r)
			$arr[$r['id']]['count'] = $r['count'];

		//���������� ������� ��� �������� ���������
		foreach($arr as $r)
			if($r['parent_id'])
				$arr[$r['parent_id']]['count'] += $r['count'];

		//��� ���������
		$arr[-1] = array(
			'id' => -1,
			'name' => '��� ���������',
			'parent_id' => 0,
			'count' => 0
		);

		xcache_set($key, $arr, 86400);
	}

	//������ ���������, ������� ������������ � ����������
	if($id == 'first') {
		if(empty($arr))
			return 0;
		return key($arr);
	}

	//������������� ������ �������� ���������
	if($id == 'main_ass') {
		$send = array();
		foreach($arr as $r)
			if(!$r['parent_id'] && $r['id'] > 0)
				$send[$r['id']] = $r['name'];
		return $send;
	}

	//������������� ������ ���� ��������� � ������������
	if($id == 'all_ass') {
		$send = array();
		foreach($arr as $r)
			$send[$r['id']] = $r['name'];
		return $send;
	}

	//JS ������ �������� ���������
	if($id == 'main_js') {
		$send = array();
		foreach($arr as $r)
			if(!$r['parent_id'])
				$send[$r['id']] = $r['name'];
		return _selJson($send);
	}

	//������ ���������
	if($id == 'tree') {
		$send = array();
		foreach($arr as $id => $r) {
			if($id == -1)
				continue;

			if(!$r['parent_id']) {
				$send[$id] = array(
					'name' => $r['name'],
					'count' => $r['count'],
					'child' => array()
				);
				continue;
			}

			$send[$r['parent_id']]['child'][$id] = array(
				'name' => $r['name'],
				'count' => $r['count'],
				'child' => array()
			);
		}
		return $send;
	}






	if(!isset($arr[$id]))
		return _cacheErr('����������� id ���������', $id);

	//ID �������� ���������
	if($i == 'main_id') {
		if(!$parent_id = $arr[$id]['parent_id'])
			return $id;
		return $arr[$parent_id]['id'];
	}

	//�������� �������� ���������
	if($i == 'main_name') {
		if(!$parent_id = $arr[$id]['parent_id'])
			return $arr[$id]['name'];
		return $arr[$parent_id]['name'];
	}

	//������ id ��������� ��������
	if($i == 'child_ids') {
		$ids = array(0);
		foreach($arr as $r)
			if($r['parent_id'] == $id)
				$ids[] = $r['id'];
		return implode(',', $ids);
	}

	//������ ��������� ��������
	if($i == 'child_arr') {
		$send = array();
		foreach($arr as $r)
			if($r['parent_id'] == $id)
				$send[$r['id']] = $r;
		return $send;
	}

	//������������� ������ ��������� ��������: id => name
	if($i == 'child_ass') {
		$send = array();
		foreach($arr as $r)
			if($r['parent_id'] == $id)
				$send[$r['id']] = $r['name'];
		return $send;
	}

	//���� ���������
	if($i == 'path') {
		$path = $arr[$id]['name'];
		if(!$parent_id = $arr[$id]['parent_id'])
			return $path;
		return $arr[$parent_id]['name'].' � '.$path;
	}

	if(!isset($arr[$id][$i]))
		return _cacheErr('����������� ���� ��������� ������', $i);

	return $arr[$id][$i];
}
function _tovarMeasure($id='all', $i='short') {//������� ��������� �������
	$key = CACHE_PREFIX.'tovar_measure';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT *
				FROM `_tovar_measure`
				ORDER BY `sort`";
		if($arr = query_arr($sql))
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

	//������� ����� ��� JS
	if($id == 'js_fraction') {
		$spisok = array();
		foreach($arr as $r)
			if($r['fraction'])
				$spisok[$r['id']] = 1;
		return _assJson($spisok);
	}

	//������� ������� ��� JS
	if($id == 'js_area') {
		$spisok = array();
		foreach($arr as $r)
			if($r['area'])
				$spisok[$r['id']] = 1;
		return _assJson($spisok);
	}

	if(!isset($arr[$id]))
		return _cacheErr('����������� id ������� ���������', $id);

	if(!isset($arr[$id][$i]))
		return _cacheErr('����������� ���� �������������', $i);

	return $arr[$id][$i];
}
function _tovarEquip($id=0, $i='', $ids='') {//����������� ������������ �������
	$key = CACHE_PREFIX.'tovar_equip';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT * FROM `_tovar_equip` ORDER BY `name`";
		$arr = query_arr($sql);
		xcache_set($key, $arr, 86400);
	}

	if(!$id)
		return $arr;

	//��������� ������ ������������ � ���� ���������
	if($id == 'check') {
		if(!$t = _tovarQuery($i))
			return '';
		if(empty($t['equip_ids']))
			return '';

		$send = '';

		// $ids - ������ id ������������ ����� �������, ������� ���� ���������� �������
		$sel = _idsAss($ids);
		foreach(_ids($t['equip_ids'], 1) as $equip_id)
			$send .=
				'<div class="mt3 ml10">'.
					_check('eq'.$equip_id, $arr[$equip_id]['name'], _bool(@$sel[$equip_id]), 1).
				'</div>';

		return $send;
	}

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
function _tovarEquipRemake() {//���������� ������������ ��� ������ (������� �������)
	$sql = "
			SELECT
				`z`.`id`,
				`zayav_id`,
				`z`.`app_id`,
				`zt`.`tovar_id`,
				`tovar_equip_ids`,
				`bind`.`category_id`
			FROM `_zayav` `z`

				RIGHT JOIN `_zayav_tovar` `zt`
				ON `z`.`id`=`zt`.`zayav_id`

				RIGHT JOIN `_tovar_bind` `bind`
				ON `bind`.`tovar_id`=`zt`.`tovar_id`

			WHERE `tovar_equip_ids`
			GROUP BY
				`zt`.`app_id`,
				`zt`.`tovar_id`,
				`tovar_equip_ids`
	";
	$spisok = query_arr($sql);

	$cat = array();
	foreach($spisok as $r) {
		foreach(explode(',', $r['tovar_equip_ids']) as $equip_id)
			$cat[$r['app_id']]
				[$r['category_id']]
				[$equip_id] = $equip_id;
	}

	$sql = "DELETE FROM `_tovar_equip_bind`";
	query($sql);

	$sql = "ALTER TABLE `_tovar_equip_bind` AUTO_INCREMENT=0";
	query($sql);

	foreach($cat as $app_id => $appArr)
		foreach($appArr as $category_id => $catArr)
			foreach($catArr as $equip_id) {
				$sql = "INSERT INTO `_tovar_equip_bind` (
							`app_id`,
							`category_id`,
							`equip_id`
						) VALUES (
							".$app_id.",
							".$category_id.",
							".$equip_id."
						)";
				query($sql);
			}

	$sql = "UPDATE `_tovar_equip_bind` SET `sort`=`id`";
	query($sql);
}
function _tovarFeature($id=false, $i='name') {//�������� ������������� �������
	$key = CACHE_PREFIX.'tovar_feature_name';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					`id`,
					`name`
				FROM `_tovar_feature_name`
				ORDER BY `id`";
		if($arr = query_arr($sql))
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
		if(!$name_id = query_value($sql)) {
			$sql = "INSERT INTO `_tovar_feature_name` (
						`name`
					) VALUES (
						'".addslashes($name)."'
					)";
			query($sql);
			$name_id = query_insert_id('_tovar_feature_name');
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
	return query_selJson($sql);
}
function _tovarVendor($id=false, $i='name') {
	$key = CACHE_PREFIX.'tovar_vendor';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					`id`,
					`name`
				FROM `_tovar_vendor`
				ORDER BY `id`";
		if($arr = query_arr($sql))
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
function _tovarVendorJs() {//������������� ������� JS
	$sql = "SELECT
				`id`,
				`name`
			FROM `_tovar_vendor`
			ORDER BY `name`";
	return query_selJson($sql);
}
function _tovarStock($id='all', $i='name') {//����������� ������� �������
	$key = CACHE_PREFIX.'tovar_stock';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					*,
					0 `avai`
				FROM `_tovar_stock`
				WHERE `app_id`=".APP_ID."
				ORDER BY `name`";
		if(!$arr = query_arr($sql)) {//���� ������ �����������, �������� �������� ����� � �� ������� ������ ���������� � ����
			$sql = "INSERT INTO `_tovar_stock` (
						`app_id`,
						`name`
					) VALUES (
						".APP_ID.",
						'�������� �����'
					)";
			query($sql);

			$stock_id = query_insert_id('_tovar_stock');

			$sql = "UPDATE `_tovar_avai`
					SET `stock_id`=".$stock_id."
					WHERE `app_id`=".APP_ID;
			query($sql);

			return _tovarStock($id, $i);
		}
		xcache_set($key, $arr, 86400);
	}

	if($id == 'all')
		return $arr;

	if(!isset($arr[$id]))
		return _cacheErr('����������� id ������', $id);

	if(!isset($arr[$id][$i]))
		return _cacheErr('����������� ���� ������', $i);

	return $arr[$id][$i];
}

function _tovarValToList($arr, $key='tovar_id') {//������� ������ � �������� � ������ �� tovar_id
	if(empty($arr))
		return array();

	foreach($arr as $r)
		$arr[$r['id']] += array(
			'tovar_name' => '',         //�������� ������
			'tovar_about' => '',        //��������
			'tovar_image_small' => '',  //�������� 50px
			'tovar_image_min' => '',    //�������� 30px
			'tovar_link' => '',         //������ �� �������� ����������� � ������

			'tovar_sale' => '',         //����������� ���������� ������ � ������

			'tovar_category_id' => 0,   //id ���������
			'tovar_category_name' => 0, //��� ���������

			'tovar_measure_name' => '', //������� ���������
			'tovar_avai' => 0,          //���������� ������ � �������





			'_end' => 0//todo �������
		);

	if(!$tovar_ids = _idsGet($arr, $key))
		return $arr;

	$sql = "SELECT
				`t`.*,
				`bind`.`category_id`,
				`bind`.`avai`
			FROM `_tovar` `t`

				RIGHT JOIN `_tovar_bind` `bind`
				ON `bind`.`app_id`=".APP_ID."
			   AND `bind`.`tovar_id`=`t`.`id`
				 
			WHERE `t`.`id` IN (".$tovar_ids.")";
	if(!$tovar = query_arr($sql))
		return $arr;

	//�����������
	$tovar = _imageValToList($tovar, 'tovar');

	foreach($arr as $id => $r) {
		if(!$r[$key])
			continue;
//		if(!isset($tovar[$r[$key]]))
//			continue;

		$t = $tovar[$r[$key]];
		$tovar_id = $t['id'];
		$go = ' class="tovar-info-go'.($t['deleted'] ? ' deleted' : '').'" val="'.$tovar_id.'"';

		$arr[$id] = array(
			'tovar_name' => trim($t['name']),
			'tovar_about' => trim($t['about']),
			'tovar_image_small' => $t['image_small'],
			'tovar_image_min' => $t['image_min'],

			'tovar_sale' =>
				'<div class="tovar-info-go dib w300 bg-gr1 bor-f0 pad5 curP over1" val="'.$tovar_id.'">'.
					'<div class="fl mr5">'.$t['image_min'].'</div>'.
					'<div class="grey">'._tovarCategory($t['category_id']).'</div>'.
					'<b>'.trim($t['name']).':</b> '.
					'<span class="color-pay"><b>'._ms(@$r['tovar_count']).'</b> '._tovarMeasure($t['measure_id']).'</span>'.
				'</div>',

			'tovar_category_id' => $t['category_id'],
			'tovar_category_name' => _tovarCategory($t['category_id']),

			'tovar_measure_name' => _tovarMeasure($t['measure_id']),
			'tovar_avai' => _ms($t['avai'])
		) + $arr[$id];

		$arr[$id]['tovar_link'] = '<a'.$go.'>'.$arr[$id]['tovar_name'].'</a>';
	}

	return $arr;
}
function _tovarDelAccess($tovar_id) {//���������� �� �������� ������
	$sql = "SELECT * FROM `_tovar` WHERE `id`=".$tovar_id;
	if(!$t = query_assoc($sql))
		return '������ �� ����������';

	if($t['deleted'])
		return '����� ��� ��� �����';

	if($t['app_id'] != APP_ID)
		return '����� ��� ������ �� � ���� ����������';

	$sql = "SELECT COUNT(*) FROM `_money_income` WHERE !`deleted` AND `tovar_id`=".$tovar_id;
	if(query_value($sql))
		return '������������� ������� ������';

	$sql = "SELECT COUNT(*) FROM `_tovar_avai` WHERE `tovar_id`=".$tovar_id;
	if(query_value($sql))
		return '����� ���� � �������';

	$sql = "SELECT COUNT(*) FROM `_tovar_move` WHERE `tovar_id`=".$tovar_id;
	if(query_value($sql))
		return '� ������ ���� ������� ��������';

	$sql = "SELECT COUNT(*) FROM `_zayav_expense` WHERE `tovar_id`=".$tovar_id;
	if(query_value($sql))
		return '����� ������������ � �������� �� �������';

	$sql = "SELECT COUNT(*) FROM `_zayav_tovar` WHERE `tovar_id`=".$tovar_id;
	if(query_value($sql))
		return '����� ������������ � �������';

	//������� �� ������ �������� ���
	return 0;
}




function _tovarCatMenu($cat_id, $cc) {//������ - ������ ���������
	$spisok = '';
	foreach(_tovarCategory('main_ass') as $id => $r) {

		$child = '';
		foreach(_tovarCategory($id, 'child_arr') as $sub)
			$child .=
				'<div val="'.$sub['id'].'">'.
					$sub['name'].
					'<em id="cat'.$sub['id'].'">'.($cc[$sub['id']] ? $cc[$sub['id']] : '').'</em>'.
				'</div>';

		$spisok .=
			'<a class="main'.($cat_id == $id ? ' sel' : '').'" val="'.$id.'">'.
				'<em id="cat'.$id.'">'.($cc[$id] ? $cc[$id] : '').'</em>'.
				$r.

	  ($child ? '<div class="sub">'.$child.'</div>' : '').

			'</a>';
	}
	return
	'<div id="rightLinkMenu" class="rightLink mar8">'.$spisok.'</div>';
}
function _tovar() {//8 - ������� �������� �������
	$data = _tovar_spisok(_hashFilter('tovar'));
	$v = $data['filter'];
	_pre(_tovarStock());
	return
	'<div id="_tovar">'.
		'<table class="bs10 w100p bg-gr1 line-b">'.
			'<tr>'.
				'<td class="w230"><div id="find"></div>'.
				'<td class="w100">'._check('avai', '�������', $v['avai'], 1).
				'<td class="w100">'._check('zakaz', '��������', $v['zakaz'], 1).
//				'<td class="w70">'._check('f3', '� ����', $v['path'], 1).
				'<td class="r"><button class="vk green" onclick="_tovarEdit()">������ ����� �����</button>'.
					'<a href="'.URL.'&p=79" class="icon icon-stat mt5 ml10'._tooltip('������� �������', -90, 'r').'</a>'.
					'<a onclick="_tovarSetup()" class="icon icon-setup-big mt5 ml5'._tooltip('������� ��������� �������', -160, 'r').'</a>'.
		'</table>'.

		'<div class="line-b">'.
			'<div id="tovar-result" class="mar10">'.$data['result'].'</div>'.
		'</div>'.

		'<table class="w100p bg-gr1">'.
			'<tr><td class="w200 top">'.
					'<div class="f-label ml10 mt10">���������</div>'.
					_tovarCatMenu($v['category_id'], _tovarCategoryCount($v)).
				'<td class="top">'.
					'<div id="tovar-spisok" class="mt10 mr10 mb10">'.$data['spisok'].'</div>'.
		'</table>'.
	'</div>'.
	'<script>'.
		'var CATEGORY_ID_DEF='._tovarCategory('first').','.
			'CATEGORY_ID='._num(@$_GET['category_id']).','.
			'SUB_ID='.intval(@$_GET['sub_id']).';'.
	'</script>';
}

function _tovarFilter($v) {
	$default = array(
		'page' => 1,
		'limit' => 500,
		'find' => '',
		'category_id' => _tovarCategory('first'),
		'sub_id' => 0,
		'avai' => 0,
		'zakaz' => 0
	);

	$filter = array(
		'page' => _num(@$v['page']) ? $v['page'] : $default['page'],
		'limit' => _num(@$v['limit']) ? $v['limit'] : $default['limit'],
		'find' => trim(@$v['find']),
		'category_id' => isset($v['category_id']) ? _num($v['category_id']) : $default['category_id'],
		'sub_id' => isset($v['sub_id']) ? intval($v['sub_id']) : $default['sub_id'],
		'avai' => _num(@$v['avai']) ? $v['avai'] : $default['avai'],
		'zakaz' => _num(@$v['zakaz']) ? $v['zakaz'] : $default['zakaz'],

		'clear' => ''
	);

	foreach($default as $k => $r)
		if($r != $filter[$k]) {
			$filter['clear'] = '<button class="vk small red fr" onclick="_tovarFilterClear()">�������� ������</button>';
			break;
		}

	return $filter;
}
function _tovarCategoryCount($filter) {//��������� ���������� ������� ��� �������� ��������� � ������ �������
	$send = _tovarCategory('all_ass');
	foreach($send as $id => $r)
		$send[$id] = 0;

	$cond = "`cat`.`app_id`=".APP_ID."
		 AND `t`.`id`=`bind`.`tovar_id`
		 AND `cat`.`id`=`bind`.`category_id`";

	if($filter['find'])
		$cond .= " AND (`t`.`name` LIKE '%".$filter['find']."%'
					 OR `about` LIKE '%".$filter['find']."%'
					 OR `articul`='".$filter['find']."'
					   )";

	if($filter['avai'])
		$cond .= " AND `bind`.`avai`";

	if($filter['zakaz'])
		$cond .= " AND `bind`.`zakaz`";

	$sql = "SELECT
				`cat`.`id`,
				COUNT(`bind`.`id`) `count`
			FROM
				`_tovar` `t`,
				`_tovar_category` `cat`,
				`_tovar_bind` `bind`
			WHERE ".$cond."			  
			GROUP BY `cat`.`id`";
	if(!$spisok = query_arr($sql))
		return $send;

	foreach($spisok as $r) {
		$main_id = _tovarCategory($r['id'], 'main_id');
		$send[$main_id] += $r['count'];
		$send[$r['id']] = $r['count'];

	}

	return $send;
}
function _tovar_spisok($v=array()) {
	$filter = _tovarFilter($v);
	$filter = _filterJs('TOVAR', $filter);

	$cond = "`bind`.`app_id`=".APP_ID."
		 AND `t`.`id`=`bind`.`tovar_id`
		 AND !`t`.`deleted`";

	if($filter['find']) {
		$cond .= " AND (`name` LIKE '%".$filter['find']."%'
					 OR `about` LIKE '%".$filter['find']."%'
					 OR `articul`='".$filter['find']."'
					   )";
	}

	if($filter['avai'])
		$cond .= " AND `bind`.`avai`";

	if($filter['zakaz'])
		$cond .= " AND `bind`.`zakaz`";

	if($filter['sub_id']) {
		if($filter['sub_id'] == -1)
			$cond .= " AND `bind`.`category_id`=".$filter['category_id']." AND `bind`.`category_id` NOT IN ("._tovarCategory($filter['category_id'], 'child_ids').")";
		else
			$cond .= " AND `bind`.`category_id`=".$filter['sub_id'];
	} elseif($filter['category_id'])
		$cond .= " AND `bind`.`category_id` IN (".$filter['category_id'].","._tovarCategory($filter['category_id'], 'child_ids').")";


	$sql = "SELECT COUNT(*) AS `all`
			FROM `_tovar` `t`,
				 `_tovar_bind` `bind`
			WHERE ".$cond;
	if(!$all = query_value($sql))
		return array(
			'all' => 0,
			'result' => '������� �� �������'.$filter['clear'],
			'spisok' => $filter['js'].'<div class="_empty">������� �� �������.</div>',
			'filter' => $filter
		);

	$sql = "SELECT
				`t`.*,
				`bind`.`category_id`,
				`bind`.`articul`,
				`bind`.`avai`,
				`bind`.`zakaz`,
				`bind`.`sum_sell`
			FROM `_tovar` `t`,
				 `_tovar_bind` `bind`
			WHERE ".$cond."
			LIMIT "._startLimit($filter);
	$spisok = query_arr($sql);
	$spisok = _imageValToList($spisok, 'tovar');


	$child = array();
	foreach($spisok as $r) {
		$r['avai'] = _ms($r['avai']) ? '<b>'._ms($r['avai']).'</b> '._tovarMeasure($r['measure_id']) : '';
		$r['zakaz'] = _ms($r['zakaz']) ? '�������� <b>'._ms($r['zakaz']).'</b> '._tovarMeasure($r['measure_id']) : '';
		$r['sum_sell'] = _cena($r['sum_sell']);
		$child[$r['category_id']][] = $r;
	}

	$send = '';
	foreach($child as $id => $r) {
		$send .=
			'<div class="fs15 color-555">'.
				(!$filter['category_id'] ? '<b class="fs15">'._tovarCategory($id, 'main_name').'</b> � ' : '').
				($id == $filter['category_id'] ? '<span class="fs15">��� ���������</span>' : _tovarCategory($id)).
				'<span class="pale ml10">'.($filter['find'] ? count($r) : _tovarCategory($id, 'count')).'</span>'.
			'</div>'.
			_tovar_unit($r, $filter);
	}

	return array(
		'all' => $all,
		'result' => '�������� �������: '.$all.$filter['clear'],
		'spisok' => $filter['js'].'<button class="vk small pink fr dn" id="but-tovar-selected" onclick="_tovarSelectedAction()"></button>'.$send,
		'filter' => $filter
	);
}
function _tovar_unit($spisok, $filter=array()) {
	$send = '<table class="collaps w100p bg-fff '.(!empty($filter) ? 'mt5 mb30' : 'mt1 mb10').'">';
	foreach($spisok as $r) {
		if(@$filter['find']) {
			$reg = '/('.$filter['find'].')/iu';
			$reg = utf8($reg);
			$r['name'] = utf8($r['name']);
			$r['name'] = preg_replace($reg, '<span class="fndd b">\\1</span>', $r['name'], 1);
			$r['name'] = win1251($r['name']);

			$r['about'] = utf8($r['about']);
			$r['about'] = preg_replace($reg, '<span class="fndd fs12">\\1</span>', $r['about'], 1);
			$r['about'] = win1251($r['about']);

			if($filter['find'] == $r['articul'])
				$r['articul'] = '<span class="fndd fs12">'.$r['articul'].'</span>';
		}
		$send .=
			'<tr class="tovar-unit over1 curP" val="'.$r['id'].'">'.
				'<td class="bor1">'.
					'<table class="bs10 w100p">'.
						'<tr>'.
							'<td class="top w35 h25">'.$r['image_min'].
							'<td class="top">'.
								'<b class="fs14 color-555">'.$r['name'].'</b>'.
				 ($r['about'] ? '<div class="fs12 grey mt1 w400">'._br($r['about']).'</div>' : '').
							'<td class="top">'.
					'</table>'.

					'<div class="fs12 grey ml10 mb5 prel">'.
						'������� '.
						'<span class="fs12 black">'.$r['articul'].'</span>'.
		  ($r['avai'] ? '<div class="tovar-unit-avai">'.$r['avai'].'</div>' : '').
		  ($r['zakaz'] ? '<div class="tovar-unit-zakaz">'.$r['zakaz'].'</div>' : '').
	  ($r['sum_sell'] ? '<div class="tovar-unit-sell">'._sumSpace($r['sum_sell']).' <span class="fs15">���.</span></div>' : '').
	                    '<div class="tovar-unit-check'._tooltip('������� �����', -78, 'r').
							_check('t'.$r['id'], '').
						'</div>'.
					'</div>';
	}
	$send .= '</table>';

	return $send;
}



function _tovar_setup_category_spisok() {//��������� ������� ��� ���������
	$send = '<table class="_spisokTab">'.
				'<tr><th class="name">�������� ���������'.
					'<th class="w70">������'.
					'<th class="w50">'.
			'</table>'.
			'<dl class="_sort" val="_tovar_category">';
	foreach(_tovarCategory('tree') as $id => $r) {
		$child = '';
		if(!empty($r['child'])) {
			$child = '<table class="_spisokTab mt1">';
			foreach($r['child'] as $key => $ch)
				$child .= '<tr class="over2" val="'.$key.'">'.
					'<td class="name">'.$ch['name'].
					'<td class="w70 center grey">'.($ch['count'] ? $ch['count'] : '').
					'<td class="w50">'.
						_iconEditNew($ch).
		(!$ch['count'] ? _iconDelNew($ch) : '');

			$child .= '</table>';

		}
		$send .= '<dd val="'.$id.'">'.
			'<table class="_spisokTab mt1 over1">'.
				'<tr val="'.$id.'">'.
					'<td class="curM">'.
						($child ? '<a class="category-sub-open fr color-ccc fs11'._tooltip('���������� / ��������', -30).'������������: <b>'.count($r['child']).'</b></a>' : '').
						'<div class="name fs15 b">'.$r['name'].'</div>'.
					'<td class="w70 center b grey">'.($r['count'] ? $r['count'] : '').
					'<td class="w50">'.
						_iconEditNew($r).
		(!$r['count'] ? _iconDelNew($r) : '').
			'</table>'.
			($child ? '<div class="category-sub ml40 mb20 dn">'.$child.'</div>' : '');

	}
	$send .= '</dl>';

	return
	'<div class="_info">'.
		'1. ���������� ����� ���������.<br />'.
		'2. �������� �������� ������������ ���������.<br />'.
		'3. ������� ������ ���������, � ������� ��� �������.<br />'.
		'4. �������� ������� �������� ��������� ���������������.'.
	'</div>'.
	'<button class="vk" onclick="_tovarSetupCategoryEdit()">������� ����� ���������</button>'.
//	'<button class="vk" id="join">���������� ��������� �� ��������</button>'.
	'<div class="mt10">'.$send.'</div>';
}
function _tovar_setup_stock_spisok() {//������ ������� ��� ���������
	$stock = _tovarStock();

	//������� ������� ������� ��� ������� ������
	$sql = "SELECT
				`stock_id`,
				COUNT(`id`) `count`
			FROM `_tovar_avai`
			WHERE `app_id`=".APP_ID."
			GROUP BY `stock_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$stock[$r['stock_id']]['avai'] = $r['count'];

	$send = '<table class="_spisokTab">'.
				'<tr><th>�������� ������'.
					'<th class="w150">���������� �������<br />������� � �������'.
					'<th class="w50">';

	foreach($stock as $id => $r) {
		$send .=
				'<tr val="'.$id.'">'.
					'<td class="name">'.$r['name'].
					'<td class="center b grey">'.($r['avai'] ? $r['avai'] : '').
					'<td>'.
						_iconEditNew($r).
		 (!$r['avai'] ? _iconDelNew($r) : '');

	}
	$send .= '</table>';

	return
//	'<div class="_info">'.
//	'</div>'.
	'<button class="vk" onclick="_tovarSetupStockEdit()">������� ����� �����</button>'.
	'<div class="mt10">'.$send.'</div>';
}




// ---=== ���������� � ������ ===---
function _tovarQuery($tovar_id) {//������ ������ �� ����� ������
	$sql = "SELECT * FROM `_tovar` WHERE `id`=".$tovar_id;
	if(!$tovar = query_assoc($sql))
		return array();

	//�������� ������ � ���������� � ID ���������
	$sql = "SELECT *
			FROM `_tovar_bind`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar_id."
			LIMIT 1";
	if(!$bind = query_assoc($sql))
		return array();

	//ids ������������
	$sql = "SELECT `equip_id`
			FROM `_tovar_equip_bind`
			WHERE `app_id`=".APP_ID."
			  AND `category_id`=".$bind['category_id']."
			ORDER BY `sort`";
	$tovar['equip_ids'] = query_ids($sql);

	//��������� � ������������
	$tovar['category_id'] = $bind['category_id'];
	$tovar['sub_id'] = 0;
	$main_id = _tovarCategory($bind['category_id'], 'main_id');
	if($bind['category_id'] != $main_id) {
		$tovar['category_id'] = $main_id;
		$tovar['sub_id'] = $bind['category_id'];
	}

	$tovar['bind_id'] = $bind['id'];
	$tovar['articul'] = $bind['articul'];
	$tovar['avai'] = _ms($bind['avai']);
	$tovar['measure'] = _tovarMeasure($tovar['measure_id']);

	//������� � �������
	$tovar['sum_buy'] = _cena($bind['sum_buy']);
	$tovar['sum_sell'] = _cena($bind['sum_sell']);

	return $tovar;
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
		$v['client_id'] = query_value($sql);
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
	query($sql);
	
	$insert_id = query_insert_id('_tovar_move');

	_tovarAvaiUpdate($v['tovar_id']);
	
	return $insert_id;
}

function _tovarAvaiSpisok($tovar_id, $v='') {//������ ������� ������
/*
	$v: radio - ����������� ������ ������� �� �������
		arr - ������� �������
*/
	$sql = "SELECT *
			FROM `_tovar_avai`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar_id."
			  AND `count`
			ORDER BY `count` DESC";
	if(!$spisok = query_arr($sql))
		return $v == 'arr' ? array() : '';

	if($v == 'arr') {
		$send = array();
		foreach($spisok as $id => $r)
			$send[$id] = array(
				'id' => _num($r['id']),
				'count' => _ms($r['count']),
				'sum_buy' => _cena($r['sum_buy'])
			);
		return $send;
	}

	$spisok = _tovarValToList($spisok);

	$count = count($spisok);
	$avai_id = $count == 1 ? key($spisok) : 0;

	$send =
		'<table class="_spisokTab _radio" id="tovar-avai-id_radio">'.
($v == 'radio' ? '<input type="hidden" id="tovar-avai-id" value="'.$avai_id.'" />' : '').
			'<tr>'.
($v == 'radio' ? '<th>' : '').
				'<th class="w50">���-��'.
				'<th class="w100">�����. ����'.
				'<th>����������';
	foreach($spisok as $r) {
		$send .=
			'<tr>'.
($v == 'radio' ? '<td class="w35 center">'.
		            '<div class="'.($avai_id == $r['id'] ? 'on' : 'off').'" val="'.$r['id'].'"><s></s></div>'
: '').
				'<td class="count center color-pay b">'.(_ms($r['count']) ? _ms($r['count']) : '').
				'<td class="cena r">'._sumSpace($r['sum_buy']).
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
	if(!$spisok = query_arr($sql))
		return;

	foreach($spisok as $r) {
		//����������e
		$sql = "SELECT IFNULL(SUM(`count`),0)
				FROM `_tovar_move`
				WHERE `tovar_avai_id`=".$r['id']."
				  AND `type_id`=1";
		$count = query_value($sql);

		//������: �������� ������
		$sql = "SELECT IFNULL(SUM(`count`),0)
				FROM `_tovar_move`
				WHERE `tovar_avai_id`=".$r['id']."
				  AND `type_id`!=1";
		$count -= query_value($sql);

		//���������� � �������� �� �������
		$sql = "SELECT IFNULL(SUM(`tovar_count`),0)
				FROM `_zayav_expense`
				WHERE `tovar_avai_id`=".$r['id'];
		$count -= query_value($sql);

		//������� ������ - �������
		$sql = "SELECT IFNULL(SUM(`tovar_count`),0)
				FROM `_money_income`
				WHERE !`deleted`
				  AND `tovar_avai_id`=".$r['id'];
		$count -= query_value($sql);

		if($r['count'] == $count)
			continue;

		$sql = "UPDATE `_tovar_avai`
				SET `count`="._ms($count)."
				WHERE `id`=".$r['id'];
		query($sql);
	}
	
	//���������� ������� ������ � bind
	$sql = "SELECT SUM(`count`)
			FROM `_tovar_avai`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar_id;
	$countAll = query_value($sql);

	$sql = "UPDATE `_tovar_bind`
			SET `avai`=".$countAll."
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar_id;
	query($sql);
}
function _tovarZakazUpdate($tovar_id) {//���������� ���������� ������ ������ � bind
	$sql = "SELECT SUM(`count`)
			FROM `_tovar_zakaz`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar_id;
	$count = _ms(query_value($sql));

	$sql = "UPDATE `_tovar_bind`
			SET `zakaz`=".$count."
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar_id;
	query($sql);
}

function _tovar_info() {//���������� � ������
	if(!$tovar_id = _num($_GET['id']))
		return _err('������������ ID ������');

	if(!$r = _tovarQuery($tovar_id))
		return _err('������ �� ����������');

	if($r['deleted'])
		return _err('����� ��� �����');

	return
	'<script>'.
		'var TI={'.
			'id:'.$tovar_id.','.
			'bind_id:'.$r['bind_id'].','.
			'category_id:'.$r['category_id'].','.
			'sub_id:'.$r['sub_id'].','.
			'vendor_id:'.$r['vendor_id'].','.
			'name:"'.$r['name'].'",'.
			'about:"'._br($r['about']).'",'.
			'articul:"'.$r['articul'].'",'.

			'sum_buy:'.$r['sum_buy'].','.
			'sum_sell:'.$r['sum_sell'].','.
	
			'measure_id:'.$r['measure_id'].','.
			'measure_name:"'._tovarMeasure($r['measure_id']).'",'.
			'measure_length:'._ms($r['measure_length']).','.
			'measure_width:'._ms($r['measure_width']).
		'};'.
	'</script>'.
	'<div id="tovar-info">'.
		'<table id="tab">'.
			'<tr><td id="ti-left">'.
					'<div id="ti-foto">'._image200(array('tovar_id'=>$tovar_id)).'</div>'.
					'<div id="ti-link" class="mt15">'.
						'<a class="db" onclick="_tovarAvaiAdd()">'.
							'<div class="icon icon-avai mr5"></div>'.
							'������ �������'.
						'</a>'.

						'<a class="db" onclick="_tovarZakaz()">'.
							'<div class="icon icon-order mr5"></div>'.
							'�������� � �����'.
						'</a>'.

						'<a class="db" onclick="_tovarSale()">'.
							'<div class="icon icon-rub mr5"></div>'.
							'�������'.
						'</a>'.
/*
						'<a class="db">'.
							'<div class="icon icon-move mr5"></div>'.
							'�����������'.
						'</a>'.
*/
						'<a class="db" onclick="_tovarWriteOff()">'.
							'<div class="icon icon-off mr5"></div>'.
							'��������'.
						'</a>'.
					'</div>'.
				'<td id="ti-right">'.
					_tovar_info_avai_cost($r).
					'<div class="grey mt10">'._tovarCategory($r['sub_id'] ? $r['sub_id'] : $r['category_id'], 'path').'</div>'.
					_tovar_menu_edit($tovar_id).
					'<div class="fs18 w500">'.$r['name'].'</div>'.
					_tovar_info_about($r['about']).
					_tovar_info_feature($r).

					_tovar_info_use_for($tovar_id).
					_tovar_info_use_spisok($tovar_id).
					_tovar_info_zakaz($r).
//					_tovar_info_zayav($tovar_id).
		'</table>'.
		'<div id="ti-move">'._tovar_info_move($r).'</div>'.
	'</div>';
}
function _tovar_info_avai_cost($tovar) {//������� � ���� ������
	return
		'<table id="avai-cost">'.
			'<tr><td class="ac avai curP'.($tovar['avai'] ? '' : ' no').'" onclick="_tovarAvaiAdd()">'.
					'<div class="color-555 mb5">�������</div>'.
					($tovar['avai'] ?
						'<tt><b>'.$tovar['avai'].'</b> '.$tovar['measure'].'</tt>' :
						'<tt><b>-</b></tt>'
					).

					'<div id="avai-show">'._tovarAvaiSpisok($tovar['id']).'</div>'.

		(APP_ID != 4357416 ?
				'<td class="ac buy curP" onclick="_tovarCost()">'.
					'<div class="color-555 mb5">�������</div>'.
					(_cena($tovar['sum_buy']) ?
						'<div class="grey fs14 pt1">'.
							'<span class="fs17">'._sumSpace($tovar['sum_buy']).'</span>'.
							' ���.'.
						'</div>'
						:
						'<tt>-</tt>'
					)
		: '').

				'<td class="ac sell curP" onclick="_tovarCost()">'.
					'<div class="color-555 mb5">�������</div>'.
					($tovar['sum_sell'] ?
						'<tt><b>'._sumSpace($tovar['sum_sell']).'</b> ���.</tt>'
						:
						'<tt><b>-</b></tt>'
					).
		'</table>';
}
function _tovar_menu_edit($tovar_id) {//���� �������������� ������
	return
	'<div class="fr prel">'.
		'<div class="tovar-menu-dot">'.
			'<div class="icon icon-dot fr"></div>'.
		'</div>'.

		'<div class="tovar-menu pabs w200 curP dn">'.
			'<h1 class="fs14 color-555">'.
				'��������:'.
				'<div class="icon icon-dot fr"></div>'.
			'</h1>'.
			'<dl>'.
				'<dd onclick="_tovarEdit()"><div class="icon icon-edit fl"></div>������������� ������'.
				'<dd onclick="_tovarCost()"><div class="icon icon-empty fl"></div>�������� ����<br />������� � �������'.
				'<dd onclick="_tovarJoin()"><div class="icon icon-join fl"></div>���������� � �������..'.
				'<dd onclick="_tovarUse()"><div class="icon icon-link fl"></div>�������� ����������'.
			(!_tovarDelAccess($tovar_id) ?
				'<dd onclick="_tovarDel()" style="color:#C5616F"><div class="icon icon-del-red fl"></div>�������'
			: '').
			'</dl>'.
		'</div>'.

	'</div>';
}
function _tovar_info_about($about) {//����� �������� ������, ���� ����
	if(!$about = trim($about))
		return '';

	return
	'<div class="dib fs12 color-555 pad5 mt5 bg-gr1 bor-f0">'.
		_br($about).
	'</div>';
}
function _tovar_info_feature($tovar) {//�������������� ������
	$send = '<table class="bs5 mt5">'.
				'<tr><td class="label r">�������:<td class="b">'.$tovar['articul'].
				'<tr><td class="label r">��. ���������:<td class="b">'.$tovar['measure'];

	if(_tovarMeasure($tovar['measure_id'], 'area'))
		$send .=
			'<tr><td class="label r">�������:'.
				'<td><span class="curD'._tooltip('�����', -26)._ms($tovar['measure_length']).'</span>'.
					' x '.
					'<span class="curD'._tooltip('������', -30)._ms($tovar['measure_width']).'</span>'.
					' = '.
					'<b>'._ms($tovar['measure_area']) .'</b> ��. �';

	$sql = "SELECT *
			FROM `_tovar_feature`
			WHERE `tovar_id`=".$tovar['id']."
			ORDER BY `id`";
	if($arr = query_arr($sql))
		foreach($arr as $r) {
			$send .=
				'<tr><td class="label r">'._tovarFeature($r['name_id']).':'.
					'<td>'.$r['v'];
		}

	$send .= '</table>';

	return $send;
}
function _tovar_info_use_for($tovar_id) {//������, � ������� ����������� ���� �����
	$sql = "SELECT
				`t`.*,
				`bind`.`category_id`
			FROM
				`_tovar` `t`,
				`_tovar_use` `use`,
				`_tovar_bind` `bind`
			WHERE `bind`.`app_id`=".APP_ID."
			  AND `bind`.`tovar_id`=`use`.`tovar_id`
			  AND `t`.`id`=`use`.`tovar_id`
			  AND `use`.`use_id`=".$tovar_id."
			  AND !`t`.`deleted`";
	if(!$tovar = query_arr($sql))
		return '';

	$c = count($tovar);
	$tovar = _imageValToList($tovar, 'tovar');

	//���������� �� ����������
	$child = array();
	foreach($tovar as $r)
		$child[$r['category_id']][] = $tovar[$r['id']];

	$send = '';
	foreach($child as $id => $r) {
		$send .=
			'<div class="fs14 color-555">'._tovarCategory($id).'</div>'.
			_tovar_unit_use($r);
	}

	return
	'<div class="mt10 pl10 pr10 pt1 bg-ffd bor-f0">'.
		'<div class="hd2 curP" onclick="$(this).next().slideToggle()">'.
			'�������� ��� <b>'.$c.'</b>-'._end($c, '��', '�', '�').' �����'._end($c, '�', '��').':'.
		'</div>'.
		'<div'.($c > 1 ? ' class="dn"' : '').'>'.$send.'</div>'.
	'</div>';
}
function _tovar_info_use_spisok($tovar_id) {//������, ������� ����������� ��� ����� ������
	$sql = "SELECT
				`t`.*,
				`bind`.`category_id`
			FROM
				`_tovar` `t`,
				`_tovar_use` `use`,
				`_tovar_bind` `bind`
			WHERE `bind`.`app_id`=".APP_ID."
			  AND `bind`.`tovar_id`=`use_id`
			  AND `t`.`id`=`use_id`
			  AND `use`.`tovar_id`=".$tovar_id."
			  AND !`deleted`";
	if(!$tovar = query_arr($sql))
		return '';

	$c = count($tovar);
	$tovar = _imageValToList($tovar, 'tovar');

	//���������� �� ����������
	$child = array();
	foreach($tovar as $r)
		$child[$r['category_id']][] = $tovar[$r['id']];

	$send = '';
	foreach($child as $id => $r)
		$send .=
			'<div class="fs14 color-555">'._tovarCategory($id).'</div>'.
			_tovar_unit_use($r);

	return
	'<div class="mt10 pl10 pr10 pt1 bg-ffd bor-f0">'.
		'<div class="hd2 curP" onclick="$(this).next().slideToggle()">'.
			'� ����� ������ �������'._end($c, '�', '�').'��� <b>'.$c.'</b> �����'._end($c, '', '�', '��').':'.
		'</div>'.
		'<div class="dn">'.$send.'</div>'.
	'</div>';
}
function _tovar_unit_use($spisok) {//������� ������ � ����������
	$send = '<table class="collaps w100p bg-fff '.(!empty($filter) ? 'mt5 mb30' : 'mt1 mb10').'">';
	foreach($spisok as $r) {
		$send .=
			'<tr class="tovar-unit over1 curP" val="'.$r['id'].'">'.
				'<td class="bor1">'.
					'<table class="bs5 w100p">'.
						'<tr>'.
							'<td class="top w35 h25">'.$r['image_min'].
							'<td class="top">'.

			                    '<div class="icon icon-del fr'._tooltip('�������� ����������', -125, 'r').'</div>'.

								'<b>'.$r['name'].'</b>'.
				 ($r['about'] ? '<div class="fs12 grey mt1 w400">'._br($r['about']).'</div>' : '').
					'</table>';
	}
	$send .= '</table>';

	return $send;
}
function _tovar_info_zakaz($tovar) {//������ �� ����� ������
	$sql = "SELECT *
			FROM `_tovar_zakaz`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar['id']."
			ORDER BY `id` DESC";
	if(!$zakaz = query_arr($sql))
		return '';

	$zakaz = _clientValToList($zakaz);
	$zakaz = _zayavValToList($zakaz);

	$send = '<div class="mt20"></div>'.
			'<div class="headBlue">��������� � �����</div>'.
			'<table class="_spisokTab">';

	$count = 0;
	foreach($zakaz as $r) {
		$send .=
		'<tr class="over2">'.
			'<td class="grey w70 r wsnw">'._dtimeAdd($r).
			'<td>'.
				($r['zayav_id'] ? '<p>������ '.$r['zayav_nomer_name'] : '').
				($r['client_id'] && !$r['zayav_id'] ? '<p>������ '.$r['client_link'] : '').
				'<p class="i">'.$r['about'].'</p>'.
			'<td class="w50 r wsnw">'._ms($r['count']).' '.$tovar['measure'].
			'<td class="w15">'.
				'<div onclick="_tovarZakazDel('.$r['id'].')" class="icon icon-del'._tooltip('������� �� ������', -99, 'r').'</div>';
		$count += _ms($r['count']);
	}

	$send .=
		(count($zakaz) > 1 ?
			'<tr><td colspan="2" class="r">�����:'.
				'<td class="r wsnw"><b>'._ms($count).'</b> '.$tovar['measure'].
				'<td>'
		: '').
		'</table>';

	return $send;
}
function _tovar_info_move($tovar) {
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
			  AND `tovar_id`=".$tovar['id'];
	$move = query_arr($sql);
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
				`tovar_count` `count`,
				ROUND(`sum`/`tovar_count`) `cena`,
				`sum` `summa`,
				'' `about`,
				`viewer_id_add`,
				`dtime_add`
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar['id']."
			  AND `tovar_avai_id`";
	$ze = query_arr($sql);
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
				`tovar_count` `count`,
				ROUND(`sum`/`tovar_count`) `cena`,
				`sum` `summa`,
				`about`,
				`viewer_id_add`,
				`dtime_add`
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar['id']."
			  AND `tovar_avai_id`
			  AND !`deleted`";
	$mi = query_arr($sql);
	$mi = _clientValToList($mi);

	$spisok = _arrayTimeGroup($move);
	$spisok += _arrayTimeGroup($ze, $spisok);
	$spisok += _arrayTimeGroup($mi, $spisok);
	ksort($spisok);

	if(empty($spisok))
		return '';
//		return '�������� ������ ���.';

	//��������� ������� ����
	$yearBegin = strftime('%Y', key($spisok));
	$yearCurrent = strftime('%Y');

	krsort($spisok);

	//������� ������� �������� ������
	$k = key($spisok);
	$spisok[$k]['first'] = 1;

	$year = array();

	foreach($spisok as $key => $r) {
		$y = strftime('%Y', $key);
		if(!isset($year[$y]))
			$year[$y] = array();
		$r['measure'] = $tovar['measure'];
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
							'<a class="'._tooltip(_cena($r['cena']).' ���./'.$r['measure'], 0, 'l').'<b>'._sumSpace($summa).'</b> ���.</a>'
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

		//����� ������ ��� �������� ���������� ��������� �������
		$toDel = array();
		if(isset($r['first']) && $r['type_id'] == 1)
			$toDel = array('del'=>1);
		
		$send .= '<tr class="'.$class.'">'.
				'<td class="w70">'.$type[$r['type_id']].
				'<td class="w50 r wsnw">'. ($count ? '<b>'.$count.'</b> '.$r['measure'] : '').
				'<td class="w100 r">'.$summa.
				'<td>'.
					($r['client_id'] && !$r['zayav_id'] ? '������ '.$r['client_link'].'. ' : '').
					($r['zayav_id'] ? '������ '.$r['zayav_link'].'. ' : '').
					$r['about'].
				'<td class="dtime">'._dtimeAdd($r).
				'<td class="ed">'._iconDel($r + $toDel).
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

function _tovar_info_zayav($tovar_id) {//������ �� ����� ������
	$sql = "SELECT COUNT(DISTINCT `zayav_id`)
			FROM `_zayav_tovar`
			WHERE `app_id`=".APP_ID."
			  AND `tovar_id`=".$tovar_id;
	if(!$count = query_value($sql))
		return '';
	return
		'<div id="ti-zayav">'.
			'<a href="'.URL.'&p=2&from_tovar_id='.$tovar_id.'">������������� � �������: '.$count.'</a>'.
		'</div>';
}









// ---=== ���������� ������ ===---
function _tovar_stat() {//�������
	return
	'<div class="mar10">'.
		'<div class="hd2">������� ������� �� �������</div>'.
		'<div class="mar20">'._tovar_stat_spisok().'</div>'.
	'</div>';
}
function _tovar_stat_spisok() {
	$sql = "SELECT
				`category_id` `id`,
				SUM(`avai`) `count`,
				SUM(`avai`*`sum_buy`) `sum`
			FROM `_tovar_bind`
			WHERE `app_id`=".APP_ID."
			  AND `avai`
			GROUP BY `category_id`";
	if(!$spisok = query_arr($sql))
		return '<div class="_empty">�������� ���</div>';


	$mainCount = array();//�������� ��������� - ����������
	$main = array();//�������� ��������� - ����� ���.
	$subCount = array(); //������������ - ����������
	$sub = array(); //������������
	foreach($spisok as $id => $r) {
		if($parent_id = _tovarCategory($id, 'parent_id')) {
			if(empty($main[$parent_id]))
				$main[$parent_id] = 0;
			if(empty($mainCount[$parent_id]))
				$mainCount[$parent_id] = 0;

			if(empty($subCount[$parent_id]))
				$subCount[$parent_id] = array();
			if(empty($sub[$parent_id]))
				$sub[$parent_id] = array();

			$mainCount[$parent_id] += $r['count'];
			$main[$parent_id] += $r['sum'];

			$subCount[$parent_id][$id] = $r['count'];
			$sub[$parent_id][$id] = round($r['sum'], 2);
			continue;
		}

		if(empty($mainCount[$id]))
			$mainCount[$id] = 0;
		if(empty($main[$id]))
			$main[$id] = 0;

		$mainCount[$id] += $r['count'];
		$main[$id] += $r['sum'];

		$subCount[$id][-1] = $r['count'];
		$sub[$id][-1] = round($r['sum'], 2);

	}

	$send =
		'<div class="_info">�������� ���������� ������� �� ���������� ����.</div>'.
		'<table class="_spisokTab">'.
			'<tr><th>���������'.
				'<th class="w100">����������'.
				'<th class="w100">�����'.
		'</table>';
	foreach($main as $main_id => $mSum) {
		$send .=
			'<table class="_spisokTab mt1 over1" onclick="$(this).next().slideToggle()">'.
				'<tr><td class="fs15 b curP">'._tovarCategory($main_id).
					'<td class="w100 fs14 center color-555">'._ms($mainCount[$main_id]).
					'<td class="w100 fs14 b r '.($mSum ? 'color-pay' : 'pale').'">'._sumSpace($mSum, 1).
			'</table>';

		$send .= '<div class="ml20 mb20 dn"><table class="_spisokTab mt1">';
		foreach($sub[$main_id] as $id => $sum)
			$send .=
				'<tr class="over1">'.
					'<td><a href="'.URL.'&p=8&category_id='.$main_id.'&sub_id='.$id.'">'._tovarCategory($id).'</a>'.
					'<td class="w100 center color-555">'._ms($subCount[$main_id][$id]).
					'<td class="w100 r '.($sum ? 'color-pay' : 'pale').'">'._sumSpace($sum, 1);
		$send .= '</table></div>';
	}

	return $send;
}

















