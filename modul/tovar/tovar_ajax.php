<?php
switch(@$_POST['op']) {
	case 'tovar_spisok':
		$_POST['find'] = win1251(@$_POST['find']);

		if($_POST['page'] == 1)
			$send['cc'] = _tovarCategoryCount($_POST);

		$data = _tovar_spisok($_POST);

		$filter = $data['filter'];
		if($filter['page'] == 1)
			$send['result'] = utf8($data['result']);

		$send['spisok'] = utf8($data['spisok']);

		jsonSuccess($send);
		break;
	case 'tovar_category_sub_for_select'://��������� ������������ ��� _select
		if(!$category_id = _num($_POST['category_id']))
			jsonError('������������ id ���������');
		$send['spisok'] = _selArray(_tovarCategory($category_id, 'child_ass'));
		jsonSuccess($send);
		break;
	case 'tovar_vendor_load':
		$send['spisok'] = _tovar_category_vendor();
		jsonSuccess($send);
		break;
	case 'tovar_add':
		$val = _tovarValuesCheck();

		$sql = "INSERT INTO `_tovar` (
					`app_id`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".VIEWER_ID."
				)";
		query($sql);

		$tovar_id = query_insert_id('_tovar');

		$sql = "INSERT INTO `_tovar_bind` (
				`app_id`,
				`category_id`,
				`tovar_id`,
				`articul`
			) VALUES (
				".APP_ID.",
				".$val['v']['category_id'].",
				".$tovar_id.",
				'".addslashes($val['v']['articul'])."'
			)";
		query($sql);

		$sql = "UPDATE `_tovar` SET ".$val['upd']." WHERE `id`=".$tovar_id;
		query($sql);

		$send['id'] = $tovar_id;

		$r = _tovarQuery($tovar_id);
		$send['arr'][$tovar_id] = array(
				'img' => '<img class="w35" src="'.API_HTML.'/img/nofoto-s.gif">',
				'name' => utf8($r['name']),
				'about' => utf8(_br($r['about']))
			);
		jsonSuccess($send);
		break;
	case 'tovar_edit':
		if(!$tovar_id = _num($_POST['id']))
			jsonError();

		$val = _tovarValuesCheck($tovar_id);

		$sql = "UPDATE `_tovar` SET ".$val['upd']." WHERE `id`=".$tovar_id;
		query($sql);

		$sql = "UPDATE `_tovar_bind`
				SET `category_id`=".$val['v']['category_id'].",
					`articul`='".addslashes($val['v']['articul'])."'
				WHERE `app_id`=".APP_ID."
				  AND `tovar_id`=".$tovar_id;
		query($sql);

//		_tovar_feature_update($tovar_id);

/*
		if($changes =
			_historyChange('���������', _tovarCategory($r['category_id']), _tovarCategory($category_id)).
			_historyChange('������������', _tovarName($r['name_id']), _tovarName($name_id)).
			_historyChange('�������������', _tovarVendor($r['vendor_id']), _tovarVendor($vendor_id)).
			_historyChange('��������', $r['name'], $name).
			_historyChange('��������', $r['about'], $about).
			_historyChange('����������', _tovarPosition($r['set_position_id']), _tovarPosition($set_position_id)).
			_historyChange('������� ���������', _tovarMeasure($r['measure_id']), _tovarMeasure($measure_id))
		)   _history(array(
				'type_id' => 106,
				'tovar_id' => $tovar_id,
				'v1' => '<table>'.$changes.'</table>'
			));
*/
		$send['id'] = $tovar_id;
		jsonSuccess($send);
		break;
	case 'tovar_del'://�������� ������ - ������� �� ����������
		if(!$tovar_id = _num($_POST['id']))
			jsonError();

		if(!$r = _tovarQuery($tovar_id))
			jsonError();

		if($reason = _tovarDelAccess($tovar_id))
			jsonError($reason);

//		$sql = "UPDATE `_tovar` SET `deleted`=1 WHERE `id`=".$tovar_id;
//		query($sql);

		$sql = "DELETE FROM `_tovar_bind`
				WHERE `app_id`=".APP_ID."
				  AND `tovar_id`=".$tovar_id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'tovar_category');

		_history(array(
			'type_id' => 113,
			'tovar_id' => $tovar_id
		));

		jsonSuccess();
		break;
	case 'tovar_to_new_category'://������� ������� � ������ ���������
		if(!$category_id = _tovar_category_insert())
			jsonError('�� ������� ���������');

		if($sub_id = _tovar_category_sub_insert($category_id))
			$category_id = $sub_id;

		if(!$tovar_ids = _ids($_POST['tovar_ids']))
			jsonError('�� ������� ������');

		//�������� ������� ������� � ����
		$sql = "SELECT COUNT(`id`)
				FROM `_tovar`
				WHERE !`deleted`
				  AND `id` IN (".$tovar_ids.")";
		if(!query_value($sql))
			jsonError('��������� ������� �� ����������');

		//���������� ���������
		$sql = "UPDATE `_tovar_bind`
				SET `category_id`=".$category_id."
				WHERE `app_id`=".APP_ID."
				  AND `tovar_id` IN (".$tovar_ids.")";
		query($sql);


		//�������� �������������� ������������
		$sql = "DELETE FROM `_tovar_category`
				WHERE `id` IN (
					SELECT `id` FROM (
						SELECT
							`tc`.`id`,
							COUNT(`tcb`.`id`) `count`
						FROM `_tovar_category` `tc`
				
							LEFT JOIN `_tovar_bind` `tcb`
							ON `tc`.`id`=`tcb`.`category_id`
				
						WHERE `tc`.`app_id`=".APP_ID."
						  AND `parent_id`
						GROUP BY `tc`.`id`
					) `tab`
					WHERE !`count`
				)";
		query($sql);


		//�������� ������ �������� �������
		$sql = "DELETE FROM `_tovar_bind`
				WHERE `tovar_id` IN (
					SELECT `id` FROM (
						SELECT
							`t`.`id`,
							COUNT(`tcb`.`id`) `count`
						FROM `_tovar` `t`,
							 `_tovar_bind` `tcb`
						WHERE `tcb`.`app_id`=".APP_ID."
						  AND `t`.`id`=`tcb`.`tovar_id`
						  AND `deleted`
						GROUP BY `t`.`id`
					) `tab`
					WHERE `count`
				)";
		query($sql);

		xcache_unset(CACHE_PREFIX.'tovar_category');
		_appJsValues();

		jsonSuccess();
		break;

	case 'tovar_setup_category_load'://��������� ������ ��������� �������
		$send['html'] = utf8(_tovar_setup_category_spisok());
		jsonSuccess($send);
		break;
	case 'tovar_setup_category_add'://�������� ����� ��������� �������
		if(!$name = _txt($_POST['name']))
			jsonError('�� ������� ��������');

		$sql = "INSERT INTO `_tovar_category` (
					`app_id`,
					`name`,
					`sort`
				) VALUES (
					".APP_ID.",
					'".addslashes($name)."',
					"._maxSql('_tovar_category')."
				)";
		query($sql);
		$insert_id = query_insert_id('_tovar_category');

		xcache_unset(CACHE_PREFIX.'tovar_category');
		_appJsValues();

		jsonSuccess();
		break;
	case 'tovar_setup_category_edit'://�������������� ��������� �������
		if(!$id = _num($_POST['id']))
			jsonError('������������ id ���������');
		if(!$name = _txt($_POST['name']))
			jsonError('�� ������� ��������');

		$sql = "SELECT *
				FROM `_tovar_category`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('��������� �� ����������');

		$sql = "UPDATE `_tovar_category`
		        SET `name`='".addslashes($name)."'
		        WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'tovar_category');
		_appJsValues();

		jsonSuccess();
		break;
	case 'tovar_setup_category_del'://�������� ��������� �������
		if(!$id = _num($_POST['id']))
			jsonError('������������ id ���������');

		$sql = "SELECT *
				FROM `_tovar_category`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('��������� �� ����������');

		$sql = "SELECT COUNT(*)
				FROM `_tovar_category`
				WHERE `parent_id`=".$id;
		if(query_value($sql))
			jsonError('� ������ ��������� ���� ������������');

		$sql = "SELECT COUNT(*)
				FROM `_tovar_bind`
				WHERE `category_id`=".$id;
		if(query_value($sql))
			jsonError('� ������ ��������� ���� ������');

		$sql = "DELETE FROM `_tovar_category` WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'tovar_category');
		_appJsValues();

		jsonSuccess();
		break;

	case 'tovar_setup_stock_load'://��������� ������ ������� ��� �������
		$send['html'] = utf8(_tovar_setup_stock_spisok());
		jsonSuccess($send);
		break;
	case 'tovar_setup_stock_add'://�������� ������ ������ ��� �������
		if(!$name = _txt($_POST['name']))
			jsonError('�� ������� ��������');

		$sql = "INSERT INTO `_tovar_stock` (
					`app_id`,
					`name`
				) VALUES (
					".APP_ID.",
					'".addslashes($name)."'
				)";
		query($sql);

		xcache_unset(CACHE_PREFIX.'tovar_stock');
		_appJsValues();

		jsonSuccess();
		break;
	case 'tovar_setup_stock_edit'://�������������� ������ ��� �������
		if(!$id = _num($_POST['id']))
			jsonError('������������ id ������');
		if(!$name = _txt($_POST['name']))
			jsonError('�� ������� ��������');

		$sql = "SELECT *
				FROM `_tovar_stock`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('������ �� ����������');

		$sql = "UPDATE `_tovar_stock`
		        SET `name`='".addslashes($name)."'
		        WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'tovar_stock');
		_appJsValues();

		jsonSuccess();
		break;
	case 'tovar_setup_stock_del'://�������� ������ ��� �������
		if(!$id = _num($_POST['id']))
			jsonError('������������ id ������');

		$sql = "SELECT *
				FROM `_tovar_stock`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('������ �� ����������');

		$sql = "SELECT COUNT(*)
				FROM `_tovar_avai`
				WHERE `stock_id`=".$id;
		if(query_value($sql))
			jsonError('�� ������ ������ ���� ������ � �������');

		$sql = "DELETE FROM `_tovar_stock` WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'tovar_stock');
		_appJsValues();

		jsonSuccess();
		break;

	case 'tovar_select_find'://��������� ������ ������� ��� ������
		$find = win1251(@$_POST['find']);

		$cond = "`bind`.`app_id`=".APP_ID."
			 AND `t`.`id`=`bind`.`tovar_id`
			 AND !`t`.`deleted`";

		$msgResult = '������� ����������� ������:';

		if($find) {
			$f = array();

			$reg = '/(\')/'; // ��������� ������� '
			if(!preg_match($reg, $find))
				$f[] = "`name` LIKE '%".$find."%' OR `about` LIKE '%".$find."%'";

			$engRus = _engRusChar($find);
			if($engRus)
				$f[] = "`name` LIKE '%".$engRus."%' OR `about` LIKE '%".$engRus."%'";

			$cond .= " AND ".(
				empty($f) ? " !`id` "
				:
				"(".implode(' OR ', $f).")"
			);
		}
		elseif($tovar_id_use = _num($_POST['tovar_id_use'])) {
			$sql = "SELECT `use_id`
					FROM `_tovar_use`
					WHERE `tovar_id`=".$tovar_id_use;
			if($ids = query_ids($sql)) {
				$cond .= " AND `t`.`id` IN (".$ids.")";
				$msgResult = '����������� ������:';
			}
		}

		if($avai = _bool($_POST['avai']))
			$cond .= " AND `avai`";

		if($tovar_id_no = _num($_POST['tovar_id_no']))
			$cond .= " AND `t`.`id`!=".$tovar_id_no;

		$JOIN = '';
		//������ ������, ������� �������������� � �������
		if($zayav_use = _bool($_POST['zayav_use']))
			$JOIN = "RIGHT JOIN `_zayav_tovar` `z`
					 ON `z`.`app_id`=".APP_ID."
					AND `z`.`tovar_id`=`bind`.`tovar_id`";

		$send['html'] =	utf8('<div class="_empty mar10">������� �� �������.</div>');

		$sql = "SELECT COUNT(*) AS `all`
				FROM
					`_tovar` `t`,
					`_tovar_bind` `bind`
				".$JOIN."
				WHERE ".$cond;
		if(!$all = query_value($sql))
			jsonSuccess($send);

		$html =
			'<div class="color-pay fs14 pl10 pb5 line-b">'.
				($find ?
					'������'._end($all, '', '�').' <b class="fs14">'.$all.'</b> �����'._end($all, '', '�', '��').':'
					:
					$msgResult
				).
			'</div>'.
			'<div class="bg-ffe pl10 pr10 pb10">';

		$sql = "SELECT
					`t`.*,
					`category_id`,
					`avai`,
					`sum_buy`,
					'' `zayav`
				FROM
					`_tovar` `t`,
					`_tovar_bind` `bind`
				".$JOIN."
				WHERE ".$cond."
				ORDER BY `id` DESC
				LIMIT 30";
		$spisok = query_arr($sql);
		$spisok = _imageValToList($spisok, 'tovar');

		if($zayav_use) {
			$sql = "SELECT
						`tovar_id`,
						COUNT(*) `c`
					FROM `_zayav_tovar`
					WHERE `tovar_id` IN (".implode(',', array_keys($spisok)).")
					GROUP BY `tovar_id`";
			$q = query($sql);
			while($r = mysql_fetch_assoc($q))
				$spisok[$r['tovar_id']]['zayav'] =
					'<a class="fr">'.
						'<b>'.$r['c'].'</b> ����'._end($r['c'], '��', '��', '��').
					'</a>';
		}

		$child = array();
		$arr = array();//������ ��� ������ ������
		foreach($spisok as $id => $r) {
			$r['avai'] = _ms($r['avai']) ? '<b>'._ms($r['avai']).'</b> '._tovarMeasure($r['measure_id']) : '';
			$child[$r['category_id']][] = $r;
			if(strlen($r['about']) > 60)
				$r['about'] = substr($r['about'], 0, 60)."...";
			$arr[$id] = array(
				'img' => $r['image_min'],
				'name' => utf8($r['name']),
				'about' => utf8(_br($r['about'])),
				'measure' => utf8(_tovarMeasure($r['measure_id'])),
				'sum_buy' => _cena($r['sum_buy'])
			);
		}

		foreach($child as $id => $r) {
			$html .=
				'<div class="fs15 color-555 pt10">'.
					_tovarCategory($id).
					'<span class="pale ml10">'.($find ? count($r) : _tovarCategory($id, 'count')).'</span>'.
				'</div>'.
				_tovar_unit_select($r, $find, $avai);
		}
		$html .= '</div>';

		$send['html'] =	utf8($html);
		$send['arr'] =	$arr;
		jsonSuccess($send);
		break;
	case 'tovar_selected_avai'://��������� ������� ������ ����� ������
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError('������������ ID ������');

		$send['html'] = utf8(_tovarAvaiSpisok($tovar_id, 'radio'));
		$send['arr'] = _tovarAvaiSpisok($tovar_id, 'arr');
		$send['arr_count'] = count($send['arr']);
		$send['arr_first'] = count($send['arr']) ? key($send['arr']) : 0;
		jsonSuccess($send);
		break;
	case 'tovar_selected_load'://������ �������, ������� ���� ������� (��� ��������������)
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

		$sql = "SELECT *
				FROM `_tovar`
				WHERE `id` IN (".implode(',', array_keys($tovar)).")";
		if(!$spisok = query_arr($sql))
			jsonError();
		$spisok = _imageValToList($spisok, 'tovar');

		$send['arr'] = array();
		foreach($spisok as $id => $r)
			$send['arr'][$id] = array(
				'img' => $r['image_min'],
				'count' => $tovar[$id],
				'name' => utf8($r['name']),
				'about' => utf8(_br($r['about']))
			);

		jsonSuccess($send);
		break;

	case 'tovar_inventory_start'://������ ��������������
		if(!VIEWER_ADMIN)
			jsonError('�������������� ����� ������ ������ �������������');
			
		$sql = "SELECT COUNT(`id`)
				FROM `_tovar_inventory`
				WHERE `app_id`=".APP_ID."
				  AND `dtime_end`='0000-00-00 00:00:00'";
		if(query_value($sql))
			jsonError('�������������� ��� ������');

		$sql = "INSERT INTO `_tovar_inventory` (
					`app_id`
				) VALUES (
					".APP_ID."
				)";
		query($sql);

		//������� ���� ������� � �������, ������� ������ ����� ������ ��������������
		$sql = "UPDATE `_tovar_avai`
				SET `inventory`=1
				WHERE `app_id`=".APP_ID."
				  AND `count`";
		query($sql);

		_history(array(
			'type_id' => 169
		));

		jsonSuccess();
		break;
	case 'tovar_inventory_cancel'://������ ��������������
		if(!VIEWER_ADMIN)
			jsonError('�������������� ����� �������� ������ �������������');
			
		$sql = "SELECT COUNT(`id`)
				FROM `_tovar_inventory`
				WHERE `app_id`=".APP_ID."
				  AND `dtime_end`='0000-00-00 00:00:00'";
		if(!query_value($sql))
			jsonError('�������������� �� ���� ��������');

		$sql = "DELETE FROM `_tovar_inventory`
				WHERE `app_id`=".APP_ID."
				  AND `dtime_end`='0000-00-00 00:00:00'";
		query($sql);

		//������� ���� ������� � �������, ������� ������ ����� ������ ��������������
		$sql = "UPDATE `_tovar_avai`
				SET `inventory`=0
				WHERE `app_id`=".APP_ID;
		query($sql);

		_history(array(
			'type_id' => 170
		));

		jsonSuccess();
		break;
	case 'tovar_inventory_avai_confirm'://��������������: ������������� ������� ������
		if(!$avai_id = _num($_POST['avai_id']))
			jsonError('������������ id �������');

		$sql = "SELECT *
				FROM `_tovar_avai`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$avai_id;
		if(!$avai = query_assoc($sql))
			jsonError('������� �� ����������');

		if(!$avai['inventory'])
			jsonError('�������������� ���� ��������');

		$sql = "UPDATE `_tovar_avai`
				SET `inventory`=0
				WHERE `id`=".$avai_id;
		query($sql);

		_history(array(
			'type_id' => 171,
			'tovar_id' => $avai['tovar_id'],
			'v1' => _ms($avai['count'])
		));

		_tovarInventoryFinish();

		jsonSuccess();
		break;


	case 'tovar_equip_load'://��������� ������ ������������ �� ������
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError('������������ ID ������');
			
		$ids = $_POST['ids'];//������� ����������

		if(!$t = _tovarQuery($tovar_id))
			jsonError('������ �� ����������');

		$html =
			'<div class="color-555 fs14">������������:</div>'.
			_tovarEquip('check', $tovar_id, $ids).
			'<div class="dib mt5 ml10">'.
				'<a id="equip-add">�������� �������...</a>'.
				'<input type="hidden" id="equip_id" />'.
				'<button class="vk ml5 dn">��������</button>'.
			'</div>';

		$send['html'] = utf8($html);
		$send['equip_js'] = _tovarEquip('js', $tovar_id);
		jsonSuccess($send);
		break;
	case 'tovar_equip_add'://���������� ����� ������������ � ������
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError('������������ ID ������');

		$equip_id = _num($_POST['equip_id']);
		$equip_name = _txt($_POST['equip_name']);

		if(!$equip_id && !$equip_name)
			jsonError('�������� ������� ��� ������� �����');

		if(!$r = _tovarQuery($tovar_id))
			jsonError('������ �� ����������');

		//�������� ������ �������� ������������
		if(!$equip_id && $equip_name) {
			$sql = "SELECT `id`
					FROM `_tovar_equip`
					WHERE `name`='".addslashes($equip_name)."'";
			if(!$equip_id = query_value($sql)) {
				$sql = "INSERT INTO `_tovar_equip` (`name`) VALUES ('".addslashes($equip_name)."')";
				query($sql);
				$equip_id = query_insert_id('_tovar_equip');
				xcache_unset(CACHE_PREFIX.'tovar_equip');
			}
		}

		//��������, ���� �� ������� ������������ ��� ������
		$equip_exist = _idsAss($r['equip_ids']);
		if(isset($equip_exist[$equip_id]))
			jsonError('������ ������� ��� ������������');

		$sql = "INSERT INTO `_tovar_equip_bind` (
					`app_id`,
					`category_id`,
					`equip_id`,
					`sort`
				) VALUES (
					".APP_ID.",
					".($r['sub_id'] ? $r['sub_id'] : $r['category_id']).",
					".$equip_id.",
					"._maxSql('_tovar_equip_bind')."
				)";
		query($sql);

		$send['html'] = utf8(
			'<div class="mt3 ml10">'.
				_check('eq'.$equip_id, _tovarEquip($equip_id), 1, 1).
			'</div>'
		);
		jsonSuccess($send);
		break;

	case 'tovar_join'://����������� ������� - ����� ������ �� ��� ���������� �����
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError('������������ id ������');
		if(!$join_id = _num($_POST['join_id']))
			jsonError('�� ������ ����� ��� �����������');

		if($tovar_id == $join_id)
			jsonError('����� �� ����� ���� �������� ��� � �����');

		if(!$r = _tovarQuery($tovar_id))
			jsonError('������-���������� �� ����������');
		if(!$join = _tovarQuery($join_id))
			jsonError('������������� ������ �� ����������');

		$sql = "SELECT COUNT(*)
				FROM `_tovar_use`
				WHERE `tovar_id`=".$tovar_id."
				  AND `use_id`=".$join_id;
		if(query_value($sql))
			jsonError('������������ ����� ����������� � ������-����������');

		$sql = "SELECT COUNT(*)
				FROM `_tovar_use`
				WHERE `tovar_id`=".$join_id."
				  AND `use_id`=".$tovar_id;
		if(query_value($sql))
			jsonError('�����-���������� ����������� � ������������� ������');


		//� ������������� ������ ����������� ������ ������
		$sql = "DELETE FROM `_tovar_use`
				WHERE `tovar_id`=".$join_id;
		query($sql);

		//������������ ����� ���������� � ������� ������
		$sql = "UPDATE `_tovar_use`
				SET `use_id`=".$tovar_id."
				WHERE `use_id`=".$join_id;
		query($sql);

		//�������� ������������� ����������
		$sql = "DELETE FROM _tovar_use WHERE `id` IN (
					SELECT id
					FROM (
						SELECT
							id,
							tovar_id,
							COUNT(use_id) `cu`
						FROM _tovar_use
						GROUP BY tovar_id,use_id
					) t
					WHERE cu>1
				)";
		query($sql);

		//��������������
		$sql = "UPDATE `_tovar_feature`
				SET `tovar_id`=".$tovar_id."
				WHERE `tovar_id`=".$join_id;
		query($sql);

		//������� ��������
		$sql = "UPDATE `_history`
				SET `tovar_id`=".$tovar_id."
				WHERE `tovar_id`=".$join_id;
		query($sql);

		//������� - �������
		$sql = "UPDATE `_money_income`
				SET `tovar_id`=".$tovar_id."
				WHERE `tovar_id`=".$join_id;
		query($sql);

		//�������
		$sql = "UPDATE `_tovar_avai`
				SET `tovar_id`=".$tovar_id."
				WHERE `tovar_id`=".$join_id;
		query($sql);
		_tovarAvaiUpdate($tovar_id);

		//�����
		$sql = "UPDATE `_tovar_zakaz`
				SET `tovar_id`=".$tovar_id."
				WHERE `tovar_id`=".$join_id;
		query($sql);

		//��������
		$sql = "UPDATE `_tovar_move`
				SET `tovar_id`=".$tovar_id."
				WHERE `tovar_id`=".$join_id;
		query($sql);

		//������� � �������
		$sql = "UPDATE `_zayav_expense`
				SET `tovar_id`=".$tovar_id."
				WHERE `tovar_id`=".$join_id;
		query($sql);

		//������������� � �������
		$sql = "UPDATE `_zayav_tovar`
				SET `tovar_id`=".$tovar_id."
				WHERE `tovar_id`=".$join_id;
		query($sql);

		//��������� � �������
		$sql = "UPDATE `_zayav_kvit`
				SET `tovar_id`=".$tovar_id."
				WHERE `tovar_id`=".$join_id;
		query($sql);

		//������ � �����������
		$sql = "DELETE FROM `_tovar_bind`
				WHERE `tovar_id`=".$join_id;
		query($sql);

		jsonSuccess();
		break;

	case 'tovar_use'://���������� ������
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError('������������ id ������');
		if(!$use_id = _num($_POST['use_id']))
			jsonError('�� ������ ����������� �����');

		if($tovar_id == $use_id)
			jsonError('����� �� ����� ���� ������� � ������ ����');

		if(!$r = _tovarQuery($tovar_id))
			jsonError('������ �� ����������');
		if(!$use = _tovarQuery($use_id))
			jsonError('������������ ������ �� ����������');

		$sql = "SELECT COUNT(*)
				FROM `_tovar_use`
				WHERE `tovar_id`=".$tovar_id."
				  AND `use_id`=".$use_id;
		if(query_value($sql))
			jsonError('����� ���������� ��� ����������');

		$sql = "SELECT COUNT(*)
				FROM `_tovar_use`
				WHERE `tovar_id`=".$use_id."
				  AND `use_id`=".$tovar_id;
		if(query_value($sql))
			jsonError('������ ������� �������� ����������');

		$sql = "INSERT INTO `_tovar_use` (
					`tovar_id`,
					`use_id`
				) VALUES (
					".$tovar_id.",
					".$use_id."
				)";
		query($sql);

		jsonSuccess();
		break;
	case 'tovar_use_cancel'://������ ���������� ������
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError('������������ id ������');
		if(!$use_id = _num($_POST['use_id']))
			jsonError('�� ������ ����������� �����');

		if(!$r = _tovarQuery($tovar_id))
			jsonError('������ �� ����������');
		if(!$use = _tovarQuery($use_id))
			jsonError('������������ ������ �� ����������');

		$sql = "SELECT COUNT(*)
				FROM `_tovar_use`
				WHERE `tovar_id`=".$tovar_id."
				  AND `use_id`=".$use_id;
		$countIn = query_value($sql);

		$sql = "SELECT COUNT(*)
				FROM `_tovar_use`
				WHERE `tovar_id`=".$use_id."
				  AND `use_id`=".$tovar_id;
		$countOut = query_value($sql);

		if(!$countIn && !$countOut)
			jsonError('���������� �� ����������');

		//�������� ������� ����������
		if($countIn) {
			$sql = "DELETE FROM `_tovar_use`
					WHERE `tovar_id`=".$tovar_id."
					  AND `use_id`=".$use_id;
			query($sql);
		}

		//�������� ��������� ����������
		if($countOut) {
			$sql = "DELETE FROM `_tovar_use`
					WHERE `tovar_id`=".$use_id."
					  AND `use_id`=".$tovar_id;
			query($sql);
		}

		jsonSuccess();
		break;

	case 'tovar_cost'://��������� ���������� ��������� � �������
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError('������������ id ������');

		$sum_buy = _cena($_POST['sum_buy']);
		$sum_sell = _cena($_POST['sum_sell']);

		if(!$r = _tovarQuery($tovar_id))
			jsonError('������ �� ����������');

		$sql = "UPDATE `_tovar_bind`
		        SET `sum_buy`=".$sum_buy.",
					`sum_sell`=".$sum_sell."
				WHERE `app_id`=".APP_ID."
				  AND `tovar_id`=".$tovar_id;
		query($sql);

		if($changes =
			_historyChange('�������', $r['sum_buy'], $sum_buy).
			_historyChange('�������', $r['sum_sell'], $sum_sell)
		)   _history(array(
				'type_id' => 106,
				'tovar_id' => $tovar_id,
				'v1' => '<table>'.$changes.'</table>'
			));

		jsonSuccess();
		break;

	case 'tovar_avai_add'://�������� ������� ������
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError('������������ id ������');
		if(!$stock_id = _num($_POST['stock_id']))
			jsonError('�� ������ �����');
		if(!$count = _ms($_POST['count']))
			jsonError('����������� ������� ����������');

		$sum_buy = _cena($_POST['sum_buy']);
		$about = _txt($_POST['about']);

		if(!$r = _tovarQuery($tovar_id))
			jsonError('������ �� ����������');

		$sql = "SELECT `id`
				FROM `_tovar_avai`
				WHERE `app_id`=".APP_ID."
				  AND `tovar_id`=".$tovar_id."
				  AND `stock_id`=".$stock_id."
				  AND `sum_buy`=".$sum_buy."
				  AND `about`='".$about."'";
		$avai_id = query_value($sql);

		$sql = "INSERT INTO `_tovar_avai` (
					`id`,
					`app_id`,
					`tovar_id`,
					`stock_id`,
					`count`,
					`sum_buy`,
					`about`
				) VALUES (
					".$avai_id.",
					".APP_ID.",
					".$tovar_id.",
					".$stock_id.",
					".$count.",
					".$sum_buy.",
					'".addslashes($about)."'
				) ON DUPLICATE KEY UPDATE
					`count`=`count`+VALUES(`count`)";
		query($sql);

		if(!$avai_id)
			$avai_id = query_insert_id('_tovar_avai');

		_tovarMoveInsert(array(
			'tovar_id' => $tovar_id,
			'tovar_avai_id' => $avai_id,
			'count' => $count,
			'cena' => $sum_buy
		));

		//���������� ���������� ���������
		if($sum_buy && !$r['sum_buy']) {
			$sql = "UPDATE `_tovar_bind`
					SET `sum_buy`=".$sum_buy."
					WHERE `id`=".$r['bind_id'];
			query($sql);
		}

		//�������� ������ �� ������
		$sql = "DELETE FROM `_tovar_zakaz`
				WHERE `app_id`=".APP_ID."
				  AND `tovar_id`=".$tovar_id;
		query($sql);

		_history(array(
			'type_id' => 107,
			'tovar_id' => $tovar_id,
			'v1' => $count,
			'v2' => $sum_buy,
			'v3' => _cena($count * $sum_buy),
			'v4' => _tovarMeasure($r['measure_id'])
		));

		jsonSuccess();
		break;
	case 'tovar_avai_edit'://��������� ������ ������� ������
		if(!$avai_id = _num($_POST['avai_id']))
			jsonError('������������ id �������');

		$about = _txt($_POST['about']);

		$sql = "SELECT *
				FROM `_tovar_avai`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$avai_id;
		if(!$avai = query_assoc($sql))
			jsonError('������� �� ����������');

		if(!$t = _tovarQuery($avai['tovar_id']))
			jsonError('������ �� ����������');

		$sql = "UPDATE `_tovar_avai`
				SET `about`='".addslashes($about)."'
				WHERE `id`=".$avai_id;
		query($sql);

		_tovarAvaiUpdate($avai['tovar_id']);

		jsonSuccess();
		break;

	case 'tovar_zakaz'://���������� ������ � �����
		if(!$count = _ms($_POST['count']))
			jsonError('����������� ������� ����������');
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError('������������ id ������');

		if(!$t = _tovarQuery($tovar_id))
			jsonError('������ �� ����������');

		$client_id = _num(@$_POST['client_id']);
		if($zayav_id = _num(@$_POST['zayav_id'])) {
			if(!$z = _zayavQuery($zayav_id))
				jsonError('������ �� ����������');
			$client_id = $z['client_id'];
		}

		$about = _txt(@$_POST['about']);

		$sql = "INSERT INTO `_tovar_zakaz` (
					`app_id`,
					`tovar_id`,
					`client_id`,
					`zayav_id`,
					`count`,
					`about`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$tovar_id.",
					".$client_id.",
					".$zayav_id.",
					".$count.",
					'".addslashes($about)."',
					".VIEWER_ID."
				)";
		query($sql);

		$send['id'] = query_insert_id('_tovar_zakaz');
		_tovarZakazUpdate($tovar_id);

		_history(array(
			'type_id' => 112,
			'client_id' => $client_id,
			'tovar_id' => $tovar_id,
			'v1' => $about
		));

		jsonSuccess($send);
		break;
	case 'tovar_zakaz_del'://�������� ������ �� ������
		if(!$id = _num($_POST['id']))
			jsonError('������������ id ������');

		//id ������ ��� �����������, �� ������ ��������� ��� ���
		$zayav_id = _num(@$_POST['zayav_id']);

		$sql = "SELECT *
				FROM `_tovar_zakaz`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('���� ������� � ������ �� ����������');

		if(!$tovar = _tovarQuery($r['tovar_id']))
			jsonError('������ �� ����������');

		$sql = "DELETE FROM `_tovar_zakaz` WHERE `id`=".$id;
		query($sql);

		_tovarZakazUpdate($r['tovar_id']);

		_history(array(
			'type_id' => 167,
			'tovar_id' => $tovar['id'],
			'v1' => _ms($r['count']),
			'v2' => _tovarMeasure($tovar['measure_id'])
		));

		$send['html'] = $zayav_id ? '' : utf8(_tovar_info_zakaz($tovar));

		jsonSuccess($send);
		break;

	case 'tovar_sale_load'://�������� ������ ��� ������� ������
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError('������������ ID ������');

		if(!$r = _tovarQuery($tovar_id))
			jsonError('������ �� ����������');
		
		if(!$r['avai'])
			jsonError('������ � ������� ���');

		$send['html'] = utf8(
			'<div class="_info">'.
				'����� ���������� ������� ����� ��������� ����� �� ��������� ��������� ����.'.
			'</div>'.
			'<div class="fs18 mt15">'.$r['name'].'</div>'.
			'<div class="hd2 mt10">����� �� �������</div>'.
			_tovarAvaiSpisok($tovar_id, 'radio').
			'<table id="sale-tab" class="bs10 dn">'.
				'<tr'.(_tovarMeasure($r['measure_id'], 'area') ? '' : ' class="dn"').'>'.
					'<td class="label r">�������:'.
					'<td><input type="text" id="sale-length" class="w50" placeholder="����� �." />'.
						' x '.
						'<input type="text" id="sale-width" class="w50" placeholder="������ �." />'.

				'<tr><td class="label r w125">����������:*'.
					'<td><input type="text" id="count" class="w35" value="1" /> '._tovarMeasure($r['measure_id']).
						' <span class="grey">(max: <b id="max"></b>)</span>'.
				'<tr><td class="label r">���� (�� 1 '._tovarMeasure($r['measure_id']).'):*'.
					'<td><input type="text" id="cena" class="money" value="'._cena($r['sum_sell']).'" /> ���.'.
				'<tr><td class="label r">�����:<td><b id="summa"></b> ���.'.
				'<tr><td class="label r topi">����:*'.
					'<td><input type="hidden" id="invoice_id-add" />'.
						'<div class="tr_confirm mt5 dn"><input type="hidden" id="confirm" /></div>'.
				'<tr><td class="label r">������:<td><input type="hidden" id="client_id" />'.
			'</table>'
		);

		$send['arr'] = _tovarAvaiSpisok($tovar_id, 'arr');
		$send['arr_count'] = count($send['arr']);
		$send['arr_first'] = count($send['arr']) ? key($send['arr']) : 0;
		jsonSuccess($send);
		break;
	case 'tovar_sale':// ������� ������
		if(!$avai_id = _num($_POST['avai_id']))
			jsonError('������������ ID �������');
		if(!$count = _ms($_POST['count']))
			jsonError('����������� ������� ����������');
		if(!$cena = _cena($_POST['cena']))
			jsonError('������� ������� ����');
		if(!$invoice_id = _num($_POST['invoice_id']))
			jsonError('�� ������ ��������� ����');

		$confirm = _bool($_POST['confirm']);
		$client_id = _num($_POST['client_id']);

		$sql = "SELECT *
				FROM `_tovar_avai`
				WHERE id=".$avai_id;
		if(!$avai = query_assoc($sql))
			jsonError('������� id'.$avai_id.' �� ����������');

		if(!_ms($avai['count']))
			jsonError('������ ��� � �������');

		if($count > $avai['count'])
			jsonError('��������� ���������� ��������� �������');

		if(!$r = _tovarQuery($avai['tovar_id']))
			jsonError('������ �� ����������');

		if(!_tovarMeasure($r['measure_id'], 'fraction') && $count != round($count))
			jsonError('��� ����� ������ ���������� ������������ ����� � ����������');

		$sum = round($count * $cena);

		//���������� ������ � ������� ��� �������
		$sql = "INSERT INTO `_money_income` (
					`app_id`,
					`invoice_id`,
					`confirm`,
					`client_id`,
					`tovar_id`,
					`tovar_avai_id`,
					`tovar_count`,
					`sum`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$invoice_id.",
					".$confirm.",
					".$client_id.",
					".$avai['tovar_id'].",
					".$avai_id.",
					".$count.",
					".$sum.",
					".VIEWER_ID."
				)";
		query($sql);

		$insert_id = query_insert_id('_money_income');

		//������ ��� ���������� �����
		_balans(array(
			'action_id' => 1,
			'invoice_id' => $invoice_id,
			'sum' => $sum,
			'income_id' => $insert_id
		));

		_tovarAvaiUpdate($avai['tovar_id']);

		//������������� �������������� ����� ��������
		$sql = "UPDATE `_tovar_avai`
				SET `inventory`=0
				WHERE `app_id`=".APP_ID."
				  AND !`count`";
		query($sql);

		_history(array(
			'type_id' => 108,
			'tovar_id' => $avai['tovar_id'],
			'client_id' => $client_id,
			'v1' => $count,
			'v2' => _tovarMeasure($r['measure_id']),
			'v3' => $sum
		));

		jsonSuccess();
		break;

	case 'tovar_stock_move_load'://�������� ������ ��� ����������� ������
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError('������������ ID ������');

		if(!$r = _tovarQuery($tovar_id))
			jsonError('������ �� ����������');

		if(!$r['avai'])
			jsonError('������ � ������� ���');

		if(_tovarStock('one'))
			jsonError('� ����������� ����� ���� �����');

		$send['html'] = utf8(
			'<div class="_info">'.
				'����������� ������� ������.'.
//				'<br />'.
			'</div>'.
			'<div class="fs18 mt15">'.$r['name'].'</div>'.
			'<div class="hd2 mt10">����� �� �������</div>'.
			_tovarAvaiSpisok($tovar_id, 'radio').
			'<table id="stock-move-tab" class="bs10 dn">'.
				'<tr><td class="label r w150">����������:*'.
					'<td><input type="text" id="count" class="w35" value="1" /> '._tovarMeasure($r['measure_id']).
						' <span class="grey">(max: <b id="max"></b>)</span>'.
				'<tr><td class="label r">����������� �� �����:*<td><input type="hidden" id="stock_id" />'.
				'<tr><td class="label r">�����������:<td><input type="text" id="about" class="w300" placeholder="�� �����������" />'.
			'</table>'
		);

		$send['arr'] = _tovarAvaiSpisok($tovar_id, 'arr');
		$send['arr_count'] = count($send['arr']);
		$send['arr_first'] = count($send['arr']) ? key($send['arr']) : 0;
		jsonSuccess($send);
		break;
	case 'tovar_stock_move'://����������� ������
		if(!$avai_id = _num($_POST['avai_id']))
			jsonError('������������ ID �������');
		if(!$count = _ms($_POST['count']))
			jsonError('����������� ������� ����������');
		if(!$stock_id = _num($_POST['stock_id']))
			jsonError('�� ������ �����');

		$about = _txt($_POST['about']);

		$sql = "SELECT *
				FROM `_tovar_avai`
				WHERE id=".$avai_id;
		if(!$avai = query_assoc($sql))
			jsonError('������� id'.$avai_id.' �� ����������');

		if(!_ms($avai['count']))
			jsonError('������ ��� � �������');

		if($count > $avai['count'])
			jsonError('��������� ���������� ��������� �������');

		if(!$r = _tovarQuery($avai['tovar_id']))
			jsonError('������ �� ����������');

		if(!_tovarMeasure($r['measure_id'], 'fraction') && $count != round($count))
			jsonError('��� ����� ������ ���������� ������������ ����� � ����������');

		if($avai['stock_id'] == $stock_id)
			jsonError('�������� ������ �����');

		$sql = "SELECT `id`
				FROM `_tovar_avai`
				WHERE `app_id`=".APP_ID."
				  AND `tovar_id`=".$avai['tovar_id']."
				  AND `stock_id`=".$stock_id."
				  AND `sum_buy`=".$avai['sum_buy']."
				  AND `about`='".$avai['about']."'";
		$avai_id_to = query_value($sql);

		$sql = "INSERT INTO `_tovar_avai` (
					`id`,
					`app_id`,
					`tovar_id`,
					`stock_id`,
					`count`,
					`sum_buy`,
					`about`
				) VALUES (
					".$avai_id_to.",
					".APP_ID.",
					".$avai['tovar_id'].",
					".$stock_id.",
					".$count.",
					".$avai['sum_buy'].",
					'".addslashes($about)."'
				) ON DUPLICATE KEY UPDATE
					`count`=`count`+VALUES(`count`)";
		query($sql);

		if(!$avai_id_to)
			$avai_id_to = query_insert_id('_tovar_avai');
		
		_tovarMoveInsert(array(
			'type_id' => 9,
			'tovar_id' => $r['id'],

			'tovar_avai_id' => $avai_id,
			'tovar_avai_id_to' => $avai_id_to,

			'stock_id' => $avai['stock_id'],
			'stock_id_to' => $stock_id,

			'count' => $count,
			'about' => $about
		));
/*
		_history(array(
			'type_id' => 109,
			'tovar_id' => $r['id'],
			'v1' => $count,
			'v2' => _tovarMeasure($r['measure_id']),
			'v3' => $about
		));
*/
		jsonSuccess();
		break;

	case 'tovar_writeoff_load'://�������� ������ ��� �������� ������
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError('������������ ID ������');

		if(!$r = _tovarQuery($tovar_id))
			jsonError('������ �� ����������');

		if(!$r['avai'])
			jsonError('������ � ������� ���');

		$send['html'] = utf8(
			'<div class="_info">'.
				'�������� ������ ������������ �� �������.'.
				'<br />'.
				'���������� ����������� ������� ������� ��������.'.
			'</div>'.
			'<div class="fs18 mt15">'.$r['name'].'</div>'.
			'<div class="hd2 mt10">����� �� �������</div>'.
			_tovarAvaiSpisok($tovar_id, 'radio').
			'<table id="write-tab" class="bs10 dn">'.
				'<tr><td class="label r w100">����������:*'.
					'<td><input type="text" id="count" class="w35" value="1" /> '._tovarMeasure($r['measure_id']).
						' <span class="grey">(max: <b id="max"></b>)</span>'.
				'<tr><td class="label r">�������:*<td><input type="text" id="about" class="w300" />'.
			'</table>'
		);

		$send['arr'] = _tovarAvaiSpisok($tovar_id, 'arr');
		$send['arr_count'] = count($send['arr']);
		$send['arr_first'] = count($send['arr']) ? key($send['arr']) : 0;
		jsonSuccess($send);
		break;
	case 'tovar_writeoff'://�������� ������
		if(!$avai_id = _num($_POST['avai_id']))
			jsonError('������������ ID �������');
		if(!$count = _ms($_POST['count']))
			jsonError('����������� ������� ����������');

		if(!$about = _txt($_POST['about']))
			jsonError('�� ������� �������');

		$sql = "SELECT *
				FROM `_tovar_avai`
				WHERE id=".$avai_id;
		if(!$avai = query_assoc($sql))
			jsonError('������� id'.$avai_id.' �� ����������');

		if(!_ms($avai['count']))
			jsonError('������ ��� � �������');

		if($count > $avai['count'])
			jsonError('��������� ���������� ��������� �������');

		if(!$r = _tovarQuery($avai['tovar_id']))
			jsonError('������ �� ����������');

		if(!_tovarMeasure($r['measure_id'], 'fraction') && $count != round($count))
			jsonError('��� ����� ������ ���������� ������������ ����� � ����������');

		_tovarMoveInsert(array(
			'type_id' => 6,
			'tovar_id' => $r['id'],
			'tovar_avai_id' => $avai_id,
			'count' => $count,
			'about' => $about
		));

		//������������� �������������� ����� ��������
		$sql = "UPDATE `_tovar_avai`
				SET `inventory`=0
				WHERE `app_id`=".APP_ID."
				  AND !`count`";
		query($sql);

		_history(array(
			'type_id' => 109,
			'tovar_id' => $r['id'],
			'v1' => $count,
			'v2' => _tovarMeasure($r['measure_id']),
			'v3' => $about
		));

		_tovarInventoryFinish();

		jsonSuccess();
		break;

	case 'tovar_move_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_tovar_move`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		if(!$tovar = _tovarQuery($r['tovar_id']))
			jsonError();

		$sql = "DELETE FROM `_tovar_move` WHERE `id`=".$id;
		query($sql);

		_tovarAvaiUpdate($r['tovar_id']);

		if($r['type_id'] == 1)
			_history(array(
				'type_id' => 110,
				'tovar_id' => $tovar['id'],
				'v1' => _ms($r['count']),
				'v2' => _cena($r['cena']),
				'v3' => _cena($r['summa']),
				'v4' => _tovarMeasure($tovar['measure_id'])
			));

		if($r['type_id'] == 6)
			_history(array(
				'type_id' => 111,
				'tovar_id' => $tovar['id'],
				'v1' => _ms($r['count']),
				'v2' => _tovarMeasure($tovar['measure_id']),
				'v3' => $r['about']
			));

		jsonSuccess();
		break;

	case 'tovar_image_attach_stat'://���������� ����������� �������� �� �������
		$start = time() - 3600 * 24 * 30;//������ ���������� ����� �����

		$sql = "SELECT
					DATE_FORMAT(`dtime_add`,'%Y-%m-%d') AS `day`,
					COUNT(DISTINCT `unit_id`) `count`
				FROM `_image`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `unit_name`='tovar'
				  AND `dtime_add`>'".strftime('%Y-%m-%d', $start)."'
				GROUP BY `day`
				ORDER BY `day`";
		$spisok = query_ass($sql);

		//���������� ������ ����
		$end = strtotime(TODAY);
		while($start < $end) {
			$start += 3600 * 24;
			$day = strftime('%Y-%m-%d', $start);
			if(!isset($spisok[$day]))
				$spisok[$day] = 0;
		}

		ksort($spisok);

		$categories = array();
		$data = array();
		foreach($spisok as $day => $count) {
			$categories[] = utf8(FullData($day, 1, 1));
			$data[] = $count;
		}

		$send['html'] = '<div id="tovar-image-attach-stat"></div>';
		$send['series'][] = array(
			'name' => utf8('����������� ��������'),
			'data' => $data
		);
		$send['categories'] = $categories;

		jsonSuccess($send);
		break;
}

function _tovarValuesCheck($tovar_id=0) {//�������� ������ ����� ��������� ��� ��������������� ������
	$v = array();
	if(!$v['name'] = _txt($_POST['name']))
		jsonError('�� ������� ��������');

	if(!$v['measure_id'] = _num($_POST['measure_id']))
		jsonError('�� ������� ������� ���������');

	$v['measure_length'] = 0;
	$v['measure_width'] = 0;
	$v['measure_area'] = 0;
	if(_tovarMeasure($v['measure_id'], 'area')) {
		if(!$v['measure_length'] = _ms($_POST['measure_length']))
			jsonError('����������� ������� �����');
		if(!$v['measure_width'] = _ms($_POST['measure_width']))
			jsonError('����������� ������� ������');
		$v['measure_area'] = _ms($v['measure_length'] * $v['measure_width']);
	}

	if(!$v['category_id'] = _tovar_category_insert())
		jsonError('�� ������� ���������');
	if($sub_id = _tovar_category_sub_insert($v['category_id']))
		$v['category_id'] = $sub_id;

	$v['vendor_id'] = _tovar_vendor_get();
	$v['about'] = _txt($_POST['about']);

	if(!$v['articul'] = _txt($_POST['articul'])) {
		$v['articul'] = '000001';
		$sql = "SELECT MAX(`articul`)
				FROM `_tovar_bind`
				WHERE `app_id`=".APP_ID."
				ORDER BY `id` DESC
				LIMIT 1";
		if($articul = _num(query_value($sql)))
			$v['articul'] = '0'.(++$articul);
/*
		$articul = $max;
		for($i = 0; $i < 6 - strlen($max); $i++)
			$articul = '0'.$articul;
*/
	} elseif($tovar_id) {
		$sql = "SELECT COUNT(*)
				FROM `_tovar_bind`
				WHERE `app_id`=".APP_ID."
				  AND `articul`='".addslashes($v['articul'])."'
				  AND `tovar_id`!=".$tovar_id;
		if(query_value($sql))
			jsonError('������� ������������ � ������ ������');
	}

	$upd = array();
	foreach($v as $k => $r) {
		if($k == 'category_id')
			continue;
		if($k == 'articul')
			continue;
		$upd[] = '`'.$k.'`="'.addslashes($r).'"';
	}

	return array(
		'v' => $v,
		'upd' => implode(',', $upd),
	);
}
function _tovar_category_insert() {//���������� ��������� ������ � ��������� ��� id
	if($category_id = _num($_POST['category_id']))
		return $category_id;
	if(!$category_name = _txt($_POST['category_name']))
		return 0;

	$sql = "SELECT `id`
			FROM `_tovar_category`
			WHERE `app_id`=".APP_ID."
			  AND `name`='".addslashes($category_name)."'
			LIMIT 1";
	if(!$category_id = query_value($sql)) {
		$sql = "INSERT INTO `_tovar_category` (
					`app_id`,
					`name`,
					`sort`
				) VALUES (
					".APP_ID.",
					'".addslashes($category_name)."',
					"._maxSql('_tovar_category')."
				)";
		query($sql);

		$category_id = query_insert_id('_tovar_category');

		xcache_unset(CACHE_PREFIX.'tovar_category');
	}

	return $category_id;
}
function _tovar_category_sub_insert($category_id) {//���������� ������������ ������ � ��������� ��� id
	if($sub_id = _num($_POST['sub_id']))
		return $sub_id;
	if(!$sub_name = _txt($_POST['sub_name']))
		return 0;

	$sql = "SELECT `id`
			FROM `_tovar_category`
			WHERE `app_id`=".APP_ID."
			  AND `parent_id`=".$category_id."
			  AND `name`='".addslashes($sub_name)."'
			LIMIT 1";
	if(!$sub_id = query_value($sql)) {
		$sql = "INSERT INTO `_tovar_category` (
					`app_id`,
					`parent_id`,
					`name`,
					`sort`
				) VALUES (
					".APP_ID.",
					".$category_id.",
					'".addslashes($sub_name)."',
					"._maxSql('_tovar_category')."
				)";
		query($sql);

		$sub_id = query_insert_id('_tovar_category');

		xcache_unset(CACHE_PREFIX.'tovar_category');
	}

	return $sub_id;
}
function _tovar_vendor_get() {//��������� id �������������. �������� ������ �� ��������� �����, ���� ����.
	if($vendor_id = _num(@$_POST['vendor_id'])) {
		$sql = "SELECT `id`
				FROM `_tovar_vendor`
				WHERE `id`=".$vendor_id."
				LIMIT 1";
		if(query_value($sql))
			return $vendor_id;
	}

	if(!$vendor_name = _txt(@$_POST['vendor_name']))
		return 0;

	$sql = "SELECT `id`
			FROM `_tovar_vendor`
			WHERE `name`='".addslashes($vendor_name)."'
			LIMIT 1";
	if($vendor_id = query_value($sql))
		return $vendor_id;

	$sql = "INSERT INTO `_tovar_vendor` (
				`name`,
				`viewer_id_add`
			) VALUES (
				'".addslashes($vendor_name)."',
				".VIEWER_ID."
			)";
	query($sql);

	$vendor_id = query_insert_id('_tovar_vendor');
	xcache_unset(CACHE_PREFIX.'tovar_vendor');

	return $vendor_id;
}
function _tovar_feature_update($tovar_id) {//���������� ������������� ������
	$sql = "DELETE FROM `_tovar_feature` WHERE `tovar_id`=".$tovar_id;
	query($sql);

	if(empty($_POST['feature']))
		return;

	$insert = array();
	foreach($_POST['feature'] as $r) {
		$v = _txt($r[2]);
		if(empty($v))//������ ��������
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
	query($sql);
}

function _tovar_unit_select($spisok, $find, $avai) {
	$send = '<table class="collaps w100p bg-fff mt1 mb10">';
	foreach($spisok as $r) {
		if($find) {
			$reg = '/('.$find.')/iu';
			$reg = utf8($reg);
//			$r['name'] = _findRegular($find, $r['name']);
			$r['name'] = utf8($r['name']);
			$r['name'] = preg_replace($reg, '<span class="fndd b">\\1</span>', $r['name'], 1);
			$r['name'] = win1251($r['name']);
			$r['about'] = utf8($r['about']);
			$r['about'] = preg_replace($reg, '<span class="fndd fs12">\\1</span>', $r['about'], 1);
			$r['about'] = win1251($r['about']);
		}
		$send .=
			'<tr class="tsu bor-e8 over1 curP" val="'.$r['id'].'">'.
				'<td>'.
					'<table class="bs5 w100p prel">'.
						'<tr>'.
							'<td class="top w35 h25">'.$r['image_min'].
							'<td class="top b">'.
		 ($avai && $r['avai'] ? '<div class="tovar-unit-avai fr">'.$r['avai'].'</div>' : '').
								$r['name'].
				 ($r['about'] ? '<div class="fs12 grey mt1 w400">'._br($r['about']).'</div>' : '').
								$r['zayav'].
					'</table>';
	}
	$send .= '</table>';

	return $send;
}

function _tovar_category_vendor() {//������ ��������������
	$sql = "SELECT DISTINCT(`vendor_id`)
			FROM
				`_tovar` `t`,
				`_tovar_bind` `bind`
			WHERE `t`.`id`=`bind`.`tovar_id`
			  AND `bind`.`app_id`=".APP_ID."
			  AND `vendor_id`";
	$ids = query_ids($sql);

	$vendorIds = array();
	foreach(_ids($ids, 1) as $r)
		$vendorIds[$r] = _tovarVendor($r);

	asort($vendorIds);

	return _sel($vendorIds);
}

function _tovarInventoryFinish() {//�������� ��������� ��������������
	$sql = "SELECT `id`
			FROM `_tovar_inventory`
			WHERE `app_id`=".APP_ID."
			  AND `dtime_end`='0000-00-00 00:00:00'
			LIMIT 1";
	if(!$inventory_id = query_value($sql))
		return;

	$sql = "SELECT COUNT(*)
			FROM `_tovar_avai`
			WHERE `app_id`=".APP_ID."
			  AND `inventory`";
	if(query_value($sql))
		return;

	$sql = "UPDATE `_tovar_inventory`
			SET `dtime_end`=CURRENT_TIMESTAMP
			WHERE `id`=".$inventory_id;
	query($sql);

	_history(array(
		'type_id' => 172
	));
}





