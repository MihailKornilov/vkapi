<?php
switch(@$_POST['op']) {
	case 'tovar_spisok':
		$_POST['find'] = win1251(@$_POST['find']);
		$data = _tovar_spisok_icon($_POST);

		$filter = $data['filter'];
		if($filter['page'] == 1) {
			$send['result'] = utf8($data['result']);
			$send['name_spisok'] = $filter['category_id'] ? _tovar_category_name($filter['category_id'] == -1 ? 0 : $filter['category_id']) : _sel(array());
			$send['name_id'] = $filter['name_id'];
			$send['vendor_spisok'] = _tovar_category_vendor($filter);
		}

		$send['spisok'] = utf8($data['spisok']);
		jsonSuccess($send);
		break;
	case 'tovar_name_load':
		if(!$category_id = _num($_POST['category_id']))
			jsonError();

		$send['spisok'] = _tovar_category_name($category_id);
		jsonSuccess($send);
		break;
	case 'tovar_add':
		if(!$category_id = _num($_POST['category_id']))
			jsonError();

		if(!$name_id = _num($_POST['name_id']))
			if(!$name_id = _tovar_name_insert())
				jsonError();

		$vendor_id = _tovar_vendor_get();
		$name = _txt($_POST['name']);

		//применение к другому товару
		$set_position_id = _num($_POST['set_position_id']);
		$tovar_id_set = _num($_POST['tovar_id_set']);
		if(_num($_POST['set']) && (!$set_position_id || !$tovar_id_set))
			jsonError();
		
		if(!$measure_id = _num($_POST['measure_id']))
			jsonError();

		$cost_buy = _txt($_POST['cost_buy']);
		$cost_sell = _txt($_POST['cost_sell']);
		$about = _txt($_POST['about']);

		$sql = "INSERT INTO `_tovar` (
					`app_id`,
					`category_id`,
					`name_id`,
					`vendor_id`,
					`name`,
					`about`,

					`set_position_id`,
					`tovar_id_set`,

					`measure_id`,
					`cost_buy`,
					`cost_sell`,

					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$category_id.",
					".$name_id.",
					".$vendor_id.",
					'".addslashes($name)."',
					'".addslashes($about)."',

					".$set_position_id.",
					".$tovar_id_set.",

					".$measure_id.",
					".$cost_buy.",
					".$cost_sell.",

					".VIEWER_ID."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$send['id'] = query_insert_id('_tovar', GLOBAL_MYSQL_CONNECT);

		_tovar_find_update($send['id']);
		_tovar_feature_update($send['id']);

		$send['arr'][$send['id']] = array(
			'id' => $send['id'],
			'tovar_id' => $send['id'],
			'name' => utf8(_tovarName($name_id).'<br /><b>'._tovarVendor($vendor_id).$name.'</b>')
		);
		$send['arr'] = _imageValToList($send['arr'], 'tovar_id');

		jsonSuccess($send);
		break;
	case 'tovar_edit':
		if(!$tovar_id = _num($_POST['id']))
			jsonError();
		if(!$category_id = _num($_POST['category_id']))
			jsonError();

		if(!$name_id = _num($_POST['name_id']))
			if(!$name_id = _tovar_name_insert())
				jsonError();

		$name = _txt($_POST['name']);

		//применение к другому товару
		$set_position_id = _num($_POST['set_position_id']);
		$tovar_id_set = _num($_POST['tovar_id_set']);
		if(_num($_POST['set']) && (!$set_position_id || !$tovar_id_set))
			jsonError();

		if(!$measure_id = _num($_POST['measure_id']))
			jsonError();

		$cost_buy = _txt($_POST['cost_buy']);
		$cost_sell = _txt($_POST['cost_sell']);
		$about = _txt($_POST['about']);


		if(!$r = _tovarQuery($tovar_id))
			jsonError();
		
		$sql = "UPDATE `_tovar`
				SET `category_id`=".$category_id.",
					`name_id`=".$name_id.",
					`vendor_id`="._tovar_vendor_get().",
					`name`='".addslashes($name)."',
					`about`='".addslashes($about)."',

					`set_position_id`=".$set_position_id.",
					`tovar_id_set`=".$tovar_id_set.",

					`measure_id`=".$measure_id.",
					`cost_buy`=".$cost_buy.",
					`cost_sell`=".$cost_sell."
				WHERE `id`=".$tovar_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		_tovar_find_update($tovar_id);
		_tovar_feature_update($tovar_id);

		jsonSuccess();
		break;
	case 'tovar_select'://получение списка товаров дл€ выбора
		$tovar_id = _num($_POST['tovar_id']);
		$v = _txt($_POST['v']);

		$cond = "`category_id` IN ("._tovarCategory('use').")";

		if($v) {
			$find = array();
			$find[] = "`find` LIKE '%".$v."%'";

			$engRus = _engRusChar($v);
			if($engRus)
				$find[] = "`find` LIKE '%".$engRus."%'";

			$cond .= " AND (".implode(' OR ', $find).")";
		}

		if($_POST['ids'] != 'none')
			$cond .= " AND `id` IN (".$_POST['ids'].")";

		if(!_bool($_POST['set']))
			$cond .= " AND !`tovar_id_set`";

		//наличие товара
		$RJ_AVAI = _num($_POST['avai']) ?
					"RIGHT JOIN `_tovar_avai` `ta`
				     ON `ta`.`tovar_id`=`t`.`id` AND `ta`.`count`"
				: '';

		$tovar_id_set = _num($_POST['tovar_id_set']);
		if(!$v && $tovar_id_set) {
			$cond_set = " AND `tovar_id_set`=".$tovar_id_set;
			$sql = "SELECT COUNT(*) FROM `_tovar` `t` ".$RJ_AVAI." WHERE ".$cond.$cond_set;
			if(query_value($sql, GLOBAL_MYSQL_CONNECT)) //если нет наличи€ по товару дл€ установки, то вывод всех по-умолчанию.
				$cond .= $cond_set;
			else $tovar_id_set = 0;
		}


		$sql = "SELECT COUNT(*) FROM `_tovar` `t` ".$RJ_AVAI." WHERE ".$cond;
		$spisok = '';
		$send['arr'] = array();
		if($count = query_value($sql, GLOBAL_MYSQL_CONNECT)) {
			$order = $v || $tovar_id_set ? "`name_id` ASC,`vendor_id` ASC,`name` ASC" : "`id` DESC";
			$sql = "SELECT `t`.*
					FROM `_tovar` `t`
					".$RJ_AVAI."
					WHERE ".$cond."
					ORDER BY ".$order."
					LIMIT 20";
			$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT);
			$arr = _tovarValToList($arr, 'tovar_id_set');
			$arr = _tovarAvaiToList($arr);

			foreach($arr as $r) {
				$tovarName = _tovarName($r['name_id']);
				$tovarVendor = _tovarVendor($r['vendor_id']);

				$name = $tovarName.'<br /><b>'.$tovarVendor.$r['name'].'</b>';
				if($r['tovar_id_set'])
					$name = '<b>'.$tovarName.'</b><br /><tt>дл€ '.$r['tovar_set_name'].'</tt>';

				//составление массива дл€ выбора товара
				$send['arr'][$r['id']] = array(
					'id' => $r['id'],
					'tovar_id' => $r['id'],
					'name' => utf8($name),
					'cost_buy' => _cena($r['cost_buy']),
					'cost_sell' => _cena($r['cost_sell'])
				);

				$spisok .=
					'<div class="ts-unit'.($tovar_id == $r['id'] ? ' sel' : '').'" val="'.$r['id'].'">'.
						_findRegular($v, str_replace('<br />', '', $name)).
						($r['avai_count'] ? '<b class="avai">'.$r['avai_count'].'</b>' : '').
					'</div>';
			}

			$send['arr'] = _imageValToList($send['arr'], 'tovar_id');
		}

		$result = $count ? 'Ќайден'._end($count, ' ', 'о ').$count.' товар'._end($count, '', 'а', 'ов').($RJ_AVAI ? ' <b>в наличии</b>' : '').':' : '“оваров не найдено.';

		$send['html'] =	utf8('<div class="ts-count'.($count ? '' : ' no').'">'.$result.'</div>'.$spisok);

		jsonSuccess($send);
		break;
	case 'tovar_selected'://список товаров, которые были выбраны
		$v = _txt($_POST['v']);
		if(!$v)
			jsonError();

		$tovar = array();
		$arr = explode(',', $v);
		if(count($arr) == 1) {
			$ex = explode(':', $arr[0]);
			$count = _num(@$ex[1]);
			$tovar[_num($ex[0])] = $count ? $count : 1;
		} else
			foreach($arr as $r) {
				$ex = explode(':', $r);
				if(!$id = _num($ex[0]))
					continue;
				if(!$count = _num($ex[1]))
					continue;
				$tovar[$id] = $count;
			}

		if(empty($tovar))
			jsonError();

		$sql = "SELECT
					*,
					`id` `tovar_id`
				FROM `_tovar`
				WHERE `id` IN (".implode(',', array_keys($tovar)).")";
		if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$spisok = _imageValToList($spisok, 'tovar_id');

		$send['arr'] = array();

		foreach($spisok as $r) {
			$name = _tovarName($r['name_id']).'<br /><b>'._tovarVendor($r['vendor_id']).$r['name'].'</b>';
			if($r['tovar_id_set']) {
				$ts = _tovarQuery($tovar_id);
				$name = '<b>'._tovarName($ts['name_id']).'</b><br /><tt>дл€ '.$ts['tovar_set_name'].'</tt>';
			}

			$send['arr'][$r['id']] = array(
				'id' => $r['id'],
				'tovar_id' => $r['id'],
				'name' => utf8($name),
				'count' => $tovar[$r['id']],
				'image_small' => $r['image_small']
			);
		}

		jsonSuccess($send);
		break;
	case 'tovar_select_avai':
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError();

		if(!$r = _tovarQuery($tovar_id))
			jsonError();

		$sql = "SELECT
					`id`,
					`articul`,
					`count`,
					`cost_buy`,
					'".utf8(_tovarMeasure($r['measure_id']))."' `measure`
				FROM `_tovar_avai`
				WHERE `app_id`=".APP_ID."
				  AND `tovar_id`=".$tovar_id."
				  AND `count`";
		$send['arr'] = query_arr($sql, GLOBAL_MYSQL_CONNECT);
		$send['html'] = utf8(_tovarAvaiArticul($tovar_id, 1));
		$send['count'] = count($send['arr']);

		jsonSuccess($send);
		break;

	case 'tovar_avai_add':
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError();
		if(!$count = _num($_POST['count']))
			jsonError();

		$cost_buy = _cena($_POST['cost_buy']);
		$bu = _bool($_POST['bu']);
		$about = _txt($_POST['about']);

		if(!$r = _tovarQuery($tovar_id))
			jsonError();

		$sql = "SELECT `id`
				FROM `_tovar_avai`
				WHERE `app_id`=".APP_ID."
				  AND `tovar_id`=".$tovar_id."
				  AND `cost_buy`=".$cost_buy."
				  AND `bu`=".$bu."
				  AND `about`='".$about."'";
		$avai_id = query_value($sql, GLOBAL_MYSQL_CONNECT);

		$sql = "INSERT INTO `_tovar_avai` (
					`id`,
					`app_id`,
					`tovar_id`,
					`articul`,
					`count`,
					`cost_buy`,
					`bu`,
					`about`
				) VALUES (
					".$avai_id.",
					".APP_ID.",
					".$tovar_id.",
					'"._tovarArticulCreate()."',
					".$count.",
					".$cost_buy.",
					".$bu.",
					'".addslashes($about)."'
				) ON DUPLICATE KEY UPDATE
					`count`=`count`+".$count;
		query($sql, GLOBAL_MYSQL_CONNECT);

		if(!$avai_id)
			$avai_id = query_insert_id('_tovar_avai', GLOBAL_MYSQL_CONNECT);


		//обновление закупочной стоимости товара
		if($cost_buy && $r['cost_buy'] != $cost_buy) {
			$sql = "UPDATE `_tovar`
					SET `cost_buy`=".$cost_buy."
					WHERE `id`=".$tovar_id;
			query($sql, GLOBAL_MYSQL_CONNECT);
		}

		_tovarMoveInsert(array(
			'tovar_id' => $tovar_id,
			'tovar_avai_id' => $avai_id,
			'count' => $count,
			'cena' => $cost_buy
		));

		jsonSuccess();
		break;

	case 'tovar_sell_load'://загрузка данных дл€ продажи товара
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError();

		if(!$r = _tovarQuery($tovar_id))
			jsonError();

		$send['html'] = utf8('<div id="nosell">“овара в наличии нет.</div>');

		$sql = "SELECT
					`id`,
					`articul`,
					`count`
				FROM `_tovar_avai`
				WHERE `app_id`=".APP_ID."
				  AND `tovar_id`=".$tovar_id."
				  AND `count`";
		if($send['arr'] = query_arr($sql, GLOBAL_MYSQL_CONNECT)) {
			$send['html'] = utf8(
				'<div class="_info">'.
					'ѕосле применени€ продажи будет произведЄн платЄж на указанный расчЄтный счЄт. '.
				'</div>'.
				'<h1><b>'._tovarName($r['name_id']).'</b> '.$r['name'].'</h1>'.
				($r['tovar_id_set'] ? '<h2>дл€ '.$r['tovar_set_name'].'</h2>' : '').
				'<div class="headName">¬ыбор по наличию</div>'.
				_tovarAvaiArticul($tovar_id, 1).
				'<table id="ts-tab" class="bs10 dn">'.
					'<tr><td class="label r"> оличество:*'.
							'<td><input type="text" id="count" /> '._tovarMeasure($r['measure_id']).
								'<span id="max">(max: <b></b>)</span>'.
					'<tr><td class="label r">÷ена продажи (за '._tovarMeasure($r['measure_id']).'):*<td><input type="text" id="cena" class="money" value="'._cena($r['cost_sell']).'" /> руб.'.
					'<tr><td class="label r">—умма:<td><b id="summa"></b> руб.'.
					'<tr><td class="label r">—чЄт:*<td><input type="hidden" id="invoice_id" />'.
					'<tr><td class="label r"> лиент:<td><input type="hidden" id="client_id" />'.
				'</table>'
			);
		}

		$send['count'] = count($send['arr']);

		jsonSuccess($send);
		break;
	case 'tovar_sell':// продажа товара
		if(!$avai_id = _num($_POST['avai_id']))
			jsonError();
		if(!$count = _num($_POST['count']))
			jsonError();
		if(!$cena = _cena($_POST['cena']))
			jsonError();
		if(!$invoice_id = _num($_POST['invoice_id']))
			jsonError();

		$client_id = _num($_POST['client_id']);

		$sql = "SELECT *
				FROM `_tovar_avai`
				WHERE id=".$avai_id;
		if(!$avai = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		if(!$avai['count'])
			jsonError();

		if($count > $avai['count'])
			jsonError();
		
		$sum = _cena($count * $cena);

		//добавление товара в платежи при продаже
		$sql = "INSERT INTO `_money_income` (
					`app_id`,
					`invoice_id`,
					`client_id`,
					`tovar_id`,
					`tovar_avai_id`,
					`tovar_count`,
					`sum`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$invoice_id.",
					".$client_id.",
					".$avai['tovar_id'].",
					".$avai_id.",
					".$count.",
					".$sum.",
					".VIEWER_ID."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$insert_id = query_insert_id('_money_income', GLOBAL_MYSQL_CONNECT);

		//баланс дл€ расчЄтного счЄта
		_balans(array(
			'action_id' => 1,
			'invoice_id' => $invoice_id,
			'sum' => $sum,
			'income_id' => $insert_id
		));

		_tovarAvaiUpdate($avai['tovar_id']);

/*
		_history(array(
			'type_id' => 13,
			'client_id' => $z['client_id'],
			'zayav_id' => $zayav_id,
			'v1' => $count,
			'zp_id' => $zp_id
		));
*/
		jsonSuccess();
		break;

	case 'tovar_move_del':
		if(!$id = _num($_POST['id']))
			jsonError();
		
		$sql = "SELECT *
				FROM `_tovar_move`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "DELETE FROM `_tovar_move` WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		_tovarAvaiUpdate($r['tovar_id']);

		jsonSuccess();
		break;

	case 'tovar_equip_load'://получение списка комплектаций по товару
		$tovar_id = _num($_POST['tovar_id']);
		$ids_sel = $_POST['ids_sel'];//галочки поставлены

		$send['check'] = utf8(_tovarEquipCheck($tovar_id, $ids_sel));
		$send['equip_js'] = _tovarEquip('js', $tovar_id);
		jsonSuccess($send);
		break;
	case 'tovar_equip_add'://добавление новой комплектации к товару
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError();

		$equip_id = _num($_POST['equip_id']);
		$equip_name = _txt($_POST['equip_name']);
		$ids_sel = $_POST['ids_sel'];//галочки поставлены

		if(!$equip_id && !$equip_name)
			jsonError();

		if(!$r = _tovarQuery($tovar_id))
			jsonError();

		//внесение нового названи€ комплектации
		if(!$equip_id && $equip_name) {
			$sql = "SELECT `id`
					FROM `_tovar_equip_name`
					WHERE `name`='".addslashes($equip_name)."'";
			if(!$equip_id = query_value($sql, GLOBAL_MYSQL_CONNECT)) {
				$sql = "INSERT INTO `_tovar_equip_name` (
							`name`,
							`viewer_id_add`
						) VALUES (
							'".addslashes($equip_name)."',
							".VIEWER_ID."
						)";
				query($sql, GLOBAL_MYSQL_CONNECT);
				$equip_id = query_insert_id('_tovar_equip_name', GLOBAL_MYSQL_CONNECT);
				xcache_unset(CACHE_PREFIX.'tovar_equip');
			}
		}

		//проверка, была ли внесена комплектаци€ дл€ товара
		$equip_exist = _idsAss($r['equip_ids']);
		if(isset($equip_exist[$equip_id]))
			jsonError();

		$sql = "INSERT INTO `_tovar_equip` (
					`category_id`,
					`name_id`,
					`equip_id`,
					`sort`
				) VALUES (
					".$r['category_id'].",
					".$r['name_id'].",
					".$equip_id.",
					"._maxSql('_tovar_equip')."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$send['check'] = utf8(_tovarEquipCheck($tovar_id, $ids_sel));
		$send['equip_js'] = _tovarEquip('js', $tovar_id);
		jsonSuccess($send);
		break;
}

function _tovar_category_name($category_id) {//список наименований по выбранной категории
	$sql = "SELECT DISTINCT(`name_id`)
			FROM `_tovar`
			WHERE `category_id`=".$category_id;
	$ids = query_ids($sql, GLOBAL_MYSQL_CONNECT);

	$nameIds = array();
	foreach(_ids($ids, 1) as $r)
		$nameIds[$r] = _tovarName($r);

	asort($nameIds);

	return _sel($nameIds);
}
function _tovar_category_vendor($filter) {//список производителей по выбранной категории
	if(!$filter['category_id'])
		return _sel(array());

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

	return _sel($vendorIds);
}
function _tovar_name_insert() {//обновление наименовани€ товара и получение его id
	$name_name = _txt($_POST['name_name']);
	if(empty($name_name))
		return 0;

	$sql = "SELECT `id`
			FROM `_tovar_name`
			WHERE `name`='".addslashes($name_name)."'
			LIMIT 1";
	if(!$name_id = query_value($sql, GLOBAL_MYSQL_CONNECT)) {
		$sql = "INSERT INTO `_tovar_name` (
							`name`,
							`viewer_id_add`
						) VALUES (
							'" . addslashes($name_name) . "',
							" . VIEWER_ID . "
						)";
		query($sql, GLOBAL_MYSQL_CONNECT);
		$name_id = query_insert_id('_tovar_name', GLOBAL_MYSQL_CONNECT);
		xcache_unset(CACHE_PREFIX . 'tovar_name');
	}

	return $name_id;
}
function _tovar_vendor_get() {//получение id производител€. ¬несение нового на основании имени, если есть.
	if($vendor_id = _num(@$_POST['vendor_id']))
		return $vendor_id;

	$vendor_name = _txt(@$_POST['vendor_name']);

	if(!$vendor_name)
		return 0;

	$sql = "SELECT `id`
			FROM `_tovar_vendor`
			WHERE `name`='".addslashes($vendor_name)."'
			LIMIT 1";
	if($vendor_id = query_value($sql, GLOBAL_MYSQL_CONNECT))
		return $vendor_id;

	$sql = "INSERT INTO `_tovar_vendor` (
				`name`,
				`viewer_id_add`
			) VALUES (
				'".addslashes($vendor_name)."',
				".VIEWER_ID."
			)";
	query($sql, GLOBAL_MYSQL_CONNECT);

	$vendor_id = query_insert_id('_tovar_vendor', GLOBAL_MYSQL_CONNECT);
	xcache_unset(CACHE_PREFIX.'tovar_vendor');

	return $vendor_id;
}
function _tovar_find_update($tovar_id) {
	$r = _tovarQuery($tovar_id);

	$find =
		_tovarName($r['name_id']).
		_tovarVendor($r['vendor_id']).
		$r['name'];

	if($r['tovar_id_set'])
		if($r = _tovarQuery($r['tovar_id_set']))
			$find .= ' '.
				_tovarName($r['name_id']).
				_tovarVendor($r['vendor_id']).
				$r['name'];

	$sql = "UPDATE `_tovar`
			SET `find`='".addslashes($find)."'
			WHERE `id`=".$tovar_id;
	query($sql, GLOBAL_MYSQL_CONNECT);
}
function _tovar_feature_update($tovar_id) {//обновление характеристик товара
	$sql = "DELETE FROM `_tovar_feature` WHERE `tovar_id`=".$tovar_id;
	query($sql, GLOBAL_MYSQL_CONNECT);

	if(empty($_POST['feature']))
		return;

	$insert = array();
	foreach($_POST['feature'] as $r) {
		$v = _txt($r[2]);
		if(empty($v))//пустое значение
			continue;

		if(!$name_id = _num($r[0]))
			if(!$name_id = _tovarFeature('get_id', $r[1]))
				continue;

		$insert[] = "(
			".$tovar_id.",
			".$name_id.",
			'".addslashes($v)."'
		)";
	}

	if(empty($insert))
		return;

	$sql = "INSERT INTO `_tovar_feature` (
				`tovar_id`,
				`name_id`,
				`v`
			) VALUES ".implode(',', $insert);
	query($sql, GLOBAL_MYSQL_CONNECT);
}









