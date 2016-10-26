<?php
switch(@$_POST['op']) {
	case 'zayav_edit_load'://загрузка данных заявки для внесения или редактирования
		$send['html'] = utf8(_zayavPoleEdit($_POST));
		jsonSuccess($send);
		break;
	case 'zayav_add'://внесение новой заявки
		$service_id = _num($_POST['service_id']);

		if(!$v = _zayavValuesCheck($service_id))
			jsonError('Нет данных для внесения заявки');

		$sql = "INSERT INTO `_zayav` (
					`app_id`,
					`service_id`,
					`nomer`,

					`client_id`,
					`about`,
					`count`,
					`phone`,
					`adres`,
					`imei`,
					`serial`,
					`color_id`,
					`color_dop`,
					`executer_id`,

					`rubric_id`,
					`rubric_id_sub`,
					`size_x`,
					`size_y`,

					`skidka`,

					`srok`,
					`sum_manual`,
					`sum_cost`,
					`pay_type`,
					`equip`,

					`status_id`,
					`status_dtime`,

					`barcode`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$service_id.",
					"._maxSql('_zayav', 'nomer', 1).",

					".$v['client_id'].",
					'".addslashes($v['about'])."',
					".$v['count'].",
					'".addslashes($v['phone'])."',
					'".addslashes($v['adres'])."',
					'".addslashes($v['imei'])."',
					'".addslashes($v['serial'])."',
					".$v['color_id'].",
					".$v['color_dop'].",
					".$v['executer_id'].",

					".$v['rubric_id'].",
					".$v['rubric_id_sub'].",
					".$v['size_x'].",
					".$v['size_y'].",

					".$v['skidka'].",

					'".$v['srok']."',
					".$v['sum_manual'].",
					".$v['sum_cost'].",
					".$v['pay_type'].",
					'".$v['equip']."',

					"._zayavStatus('default').",
					current_timestamp,

					'".rand(10, 99).(time() + rand(10000, 99999))."',
					".VIEWER_ID."
				)";
		query($sql);
		$send['id'] = query_insert_id('_zayav');

		$sql = "INSERT INTO `_zayav_status_move` (
					`app_id`,
					`zayav_id`,
					`status_id`,
					`srok`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$send['id'].",
					"._zayavStatus('default').",
					'".$v['srok']."',
					".VIEWER_ID."
				)";
		query($sql);

		_zayavTovarUpdate($send['id']);
		_zayavNameUpdate($send['id'], $v);
		_zayavTovarPlaceUpdate($send['id'], $v['place_id'], $v['place_other']); //обновление местонахождения товара
		_zayavGazetaNomerUpdate($send['id'], $v);
		kupezzZayavObUpdate($send['id']);

		_note(array(
			'add' => 1,
			'p' => 'zayav',
			'id' => $send['id'],
			'txt' => $v['note']
		));

		_history(array(
			'type_id' => 73,
			'client_id' => $v['client_id'],
			'zayav_id' => $send['id']
		));

		jsonSuccess($send);
		break;
	case 'zayav_edit':
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError('Некорректный id заявки');
		
		if(!$z = _zayavQuery($zayav_id))
			jsonError('Заявки id:'.$zayav_id.' не существует');

		$v = _zayavValuesCheck($z['service_id'], $zayav_id);

		$zpu = $v['zpu'];

		if(!$v['update'])
			jsonError('Нет данных для обновления заявки');

		if(isset($zpu[5]) && $z['client_id'] != $v['client_id']) {
			if($z['client_id'] && !$v['client_id'])
				jsonError('Нельзя производить отвязку заявки от клиента');
			$sql = "UPDATE `_money_accrual`
					SET `client_id`=".$v['client_id']."
					WHERE `app_id`=".APP_ID."
					  AND `zayav_id`=".$zayav_id."
					  AND `client_id`=".$z['client_id'];
			query($sql);
			$sql = "UPDATE `_money_income`
					SET `client_id`=".$v['client_id']."
					WHERE `app_id`=".APP_ID."
					  AND `zayav_id`=".$zayav_id."
					  AND `client_id`=".$z['client_id'];
			query($sql);
			$sql = "UPDATE `_money_refund`
					SET `client_id`=".$v['client_id']."
					WHERE `app_id`=".APP_ID."
					  AND `zayav_id`=".$zayav_id."
					  AND `client_id`=".$z['client_id'];
			query($sql);
			_clientBalansUpdate($z['client_id']);
			_clientBalansUpdate($v['client_id']);
		}

		$sql = "UPDATE `_zayav` SET ".$v['update']." WHERE `id`=".$zayav_id;
		query($sql);

		$v['name'] = _zayavNameUpdate($zayav_id, $v);
		_zayavTovarUpdate($zayav_id);
		_zayavGazetaNomerUpdate($zayav_id, $v);
		kupezzZayavObUpdate($zayav_id);


		if($changes =
			(isset($zpu[5])  ? _historyChange($zpu[5]['name'], $z['client_id'], $v['client_id'], _clientVal($z['client_id'], 'go'), _clientVal($v['client_id'], 'go')) : '').
			(isset($zpu[1])  ? _historyChange($zpu[1]['name'], $z['name'], $v['name']) : '').
			(isset($zpu[2])  ? _historyChange($zpu[2]['name'], $z['about'], $v['about']) : '').
			(isset($zpu[3])  ? _historyChange($zpu[3]['name'], $z['count'], $v['count']) : '').
			(isset($zpu[6])  ? _historyChange($zpu[6]['name'], $z['adres'], $v['adres']) : '').
			(isset($zpu[7])  ? _historyChange($zpu[7]['name'], $z['imei'], $v['imei']) : '').
			(isset($zpu[8])  ? _historyChange($zpu[8]['name'], $z['serial'], $v['serial']) : '').
			(isset($zpu[9])  ? _historyChange($zpu[9]['name'], _color($z['color_id'], $z['color_dop']), _color($v['color_id'], $v['color_dop'])) : '').
			(isset($zpu[15]) ? _historyChange($zpu[15]['name'], _cena($z['sum_cost']), $v['sum_cost']) : '').
			(isset($zpu[16]) ? _historyChange($zpu[16]['name'], _payType($z['pay_type']), _payType($v['pay_type'])) : '').
			(isset($zpu[4]['v1']) ? _historyChange('Комплект', _tovarEquip('spisok', $z['equip']), _tovarEquip('spisok', $v['equip'])) : '').
			(isset($zpu[40]) ? _historyChange($zpu[40]['name'], _rubric($z['rubric_id'], $z['rubric_id_sub']), _rubric($v['rubric_id'], $v['rubric_id_sub'])) : '')
		)	_history(array(
				'type_id' => 72,
				'client_id' => $v['client_id'],
				'zayav_id' => $zayav_id,
				'v1' => '<table>'.$changes.'</table>'
			));

		$send['id'] = $zayav_id;
		jsonSuccess($send);
		break;
	case 'zayav_del'://удаление заявки
		if(!$zayav_id = _num($_POST['id']))
			jsonError();

		if(!$z = _zayavQuery($zayav_id))
			jsonError('Заявки не существует');

		if($z['deleted'])
			jsonError('Заявка уже была удалена');

		if(!_zayavToDel($zayav_id))
			jsonError();

		$sql = "UPDATE `_zayav`
				SET `deleted`=1,
					`onpay_checked`=0
				WHERE `id`=".$zayav_id;
		query($sql);

		$sql = "SELECT IFNULL(SUM(`sum`),0)
				FROM `_money_accrual`
				WHERE !`deleted`
				  AND `zayav_id`=".$zayav_id;
		if($accrual_sum = query_value($sql)) {
			//удаление произвольных начислений
			$sql = "UPDATE `_money_accrual`
					SET `deleted`=1,
						`viewer_id_del`=".VIEWER_ID.",
						`dtime_del`=CURRENT_TIMESTAMP
					WHERE !`deleted`
					  AND `zayav_id`=".$zayav_id;
			query($sql);

			_zayavBalansUpdate($zayav_id);

			//внесение баланса для клиента
			_balans(array(
				'action_id' => 40,
				'client_id' => $z['client_id'],
				'zayav_id' => $zayav_id,
				'sum' => $accrual_sum
			));
		}

		kupezzZayavObUpdate($zayav_id);

		_history(array(
			'type_id' => 80,
			'client_id' => $z['client_id'],
			'zayav_id' => $zayav_id
		));

		jsonSuccess();
		break;
	case 'zayav_spisok':
		$_POST['find'] = win1251(@$_POST['find']);
		$data = _zayav_spisok($_POST);
		if($data['filter']['page'] == 1)
			$send['all'] = utf8($data['result']);
		$send['spisok'] = utf8($data['spisok']);
		$send['gn_year_spisok'] = _gn('arr_year_spisok', $data['filter']);
		jsonSuccess($send);
		break;

	case 'zayav_onpay_public':
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError('Некорректный id заявки');

		if(!$z = _zayavQuery($zayav_id))
			jsonError('Заявки id:'.$zayav_id.' не существует');

		if($z['onpay_checked'] != 2 && $z['onpay_checked'] != 3)
			jsonError('Объявление не нуждается в разрешении на публикацию');

		$sql = "UPDATE `_zayav`
				SET `onpay_checked`=1
				WHERE `id`=".$zayav_id;
		query($sql);

		_history(array(
			'type_id' => 104,
			'client_id' => $z['client_id'],
			'zayav_id' => $zayav_id
		));

		jsonSuccess();
		break;
	case 'zayav_onpay_public_no':
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError('Некорректный id заявки');

		if(!$z = _zayavQuery($zayav_id))
			jsonError('Заявки id:'.$zayav_id.' не существует');

		if($z['onpay_checked'] != 2)
			jsonError('Объявление не нуждается в разрешении на публикацию');

		$sql = "UPDATE `_zayav`
				SET `onpay_checked`=3
				WHERE `id`=".$zayav_id;
		query($sql);

		_history(array(
			'type_id' => 131,
			'client_id' => $z['client_id'],
			'zayav_id' => $zayav_id
		));

		jsonSuccess();
		break;

	case 'zayav_status':
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();
		if(!$status_id = _num($_POST['status_id']))
			jsonError();

		if(!$z = _zayavQuery($zayav_id))
			jsonError();

		$place_id = _num($_POST['place']);
		$place_other = !$place_id ? _txt($_POST['place_other']) : '';
//		if(ZAYAV_INFO_DEVICE && !$place_id && !$place_other)
//			jsonError();

		$executer_id = _num($_POST['executer_id']);
		$srok = _txt($_POST['srok']);
		$status_day = $_POST['status_day'];
		$comm = _txt($_POST['comm']);

		if($z['status_id'] == $status_id)
			jsonError();

		$sql = "UPDATE `_zayav`
				SET `status_id`=".$status_id.",
					`status_dtime`=CURRENT_TIMESTAMP,
					`status_day`='".$status_day."',
					`executer_id`=".$executer_id."
				WHERE `id`=".$zayav_id;
		query($sql);

		if(preg_match(REGEXP_DATE, $srok)) {
			$sql = "UPDATE `_zayav`
					SET `srok`='".$srok."'
					WHERE `id`=".$zayav_id;
			query($sql);
		}

		$sql = "INSERT INTO `_zayav_status_move` (
					`app_id`,
					`zayav_id`,
					`status_id_old`,
					`status_id`,
					`executer_id`,
					`srok`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$zayav_id.",
					".$z['status_id'].",
					".$status_id.",
					".$executer_id.",
					'".($srok ? $srok : '0000-00-00')."',
					".VIEWER_ID."
				)";
		query($sql);



//		if(ZAYAV_INFO_DEVICE)
//			zayavPlaceCheck($zayav_id, $place_id, $place_other);

		_accrualAdd($z, $_POST['accrual_sum']);

		_zayavStatusRemindAdd($zayav_id);

		_salaryZayavBonus($zayav_id);

		_note(array(
			'add' => 1,
			'comment' => 1,
			'p' => 'zayav',
			'id' => $zayav_id,
			'txt' => $comm
		));

		_history(array(
			'type_id' => 71,
			'client_id' => $z['client_id'],
			'zayav_id' => $zayav_id,
			'v1' => $z['status_id'],
			'v2' => $status_id
//			'v3' => $reason
		));

		jsonSuccess();
		break;
	case 'zayav_service_change':
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();
		if(!$service_id = _num($_POST['service_id']))
			jsonError();

		if(!$z = _zayavQuery($zayav_id))
			jsonError();

		if($z['service_id'] == $service_id)
			jsonError();

		$sql = "UPDATE `_zayav`
				SET `service_id`=".$service_id."
				WHERE `id`=".$zayav_id;
		query($sql);

		_history(array(
			'type_id' => 72,
			'client_id' => $z['client_id'],
			'zayav_id' => $zayav_id,
			'v1' => '<table>'._historyChange('Категория', _service('name', $z['service_id']), _service('name', $service_id)).'</table>'
		));

		jsonSuccess();
		break;

	case 'zayav_tovar_place_change':
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();

		$place_id = _num($_POST['place_id']);
		$place_other = !$place_id ? _txt(@$_POST['place_other']) : '';

		if(!_zayavTovarPlaceUpdate($zayav_id, $place_id, $place_other))
			jsonError();

		jsonSuccess();
		break;
	case 'zayav_tovar_zakaz':// заказ товара из заявки
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError();

		if(!$z = _zayavQuery($zayav_id))
			jsonError();

		if(!$t = _tovarQuery($tovar_id))
			jsonError();

		$sql = "INSERT INTO `_tovar_zakaz` (
					`app_id`,
					`zayav_id`,
					`tovar_id`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$zayav_id.",
					".$tovar_id.",
					".VIEWER_ID."
				)";
		query($sql);

		_history(array(
			'type_id' => 112,
			'client_id' => $z['client_id'],
			'zayav_id' => $zayav_id,
			'tovar_id' => $tovar_id
		));

		jsonSuccess();
		break;
	case 'zayav_tovar_set_load':
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError();

		if(!$r = _tovarQuery($tovar_id))
			jsonError();

		$send['html'] = utf8(
			'<div class="_info">'.
				'<u>Товар</u> будет добавлен в расходы заявки с указанием закупочной стоимости. '.
//				'<u>Наличие</u> товара будет уменьшено на 1 '.
			'</div>'.
			'<h1><b>'._tovarName($r['name_id']).'</b> '.$r['name'].'</h1>'.
			'<h2>для '.$r['tovar_set_name'].'</h2>'.
			'<div class="headName">Выбор по наличию</div>'.
			_tovarAvaiArticul($tovar_id, 1)
		);

		jsonSuccess($send);
		break;
	case 'zayav_tovar_set':// Установка товара из заявки
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();
		if(!$avai_id = _num($_POST['avai_id']))
			jsonError();

		if(!$z = _zayavQuery($zayav_id))
			jsonError();

		$sql = "SELECT *
				FROM `_tovar_avai`
				WHERE id=".$avai_id;
		if(!$avai = query_assoc($sql))
			jsonError();

		if(!$avai['count'])
			jsonError();

/*
		//Удаление из заказа запчасти, привязанной к заявке
		$sql = "DELETE FROM `zp_zakaz`
				WHERE `app_id`=".APP_ID."
				  AND `zayav_id`=".$zayav_id."
				  AND `zp_id`=".$zp_id;
		query($sql);

		_note(array(
			'add' => 1,
			'comment' => 1,
			'p' => 'zayav',
			'id' => $zayav_id,
			'txt' => 'Установка запчасти: ' .
				'<a class="zp-id" val="'.$zp_id.'">' .
				_zpName($zp['name_id']).' ' .
				_vendorName($zp['base_vendor_id'])._modelName($zp['base_model_id']) .
				'</a>'
		));


		//внесение движения товара
		$move_id = _tovarMoveInsert(array(
			'type_id' => 2,
			'tovar_id' => $avai['tovar_id'],
			'tovar_avai_id' => $avai_id,
			'zayav_id' => $zayav_id
		));
*/
		//добавление запчасти в расходы по заявке
		$sql = "INSERT INTO `_zayav_expense` (
					`app_id`,
					`zayav_id`,
					`category_id`,
					`tovar_id`,
					`tovar_avai_id`,
					`tovar_count`,
					`sum`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$zayav_id.",
					2,
					".$avai['tovar_id'].",
					".$avai_id.",
					1,
					".$avai['sum_buy'].",
					".VIEWER_ID."
				)";
		query($sql);

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

	case 'zayav_srok_open'://открытие календаря заявок
		$send['html'] = utf8(_zayavSrokCalendar($_POST));
		jsonSuccess($send);
		break;
	case 'zayav_srok_save'://изменение срока выполнения
		if(!$zayav_id = _num(@$_POST['zayav_id']))
			jsonError();
		if(!preg_match(REGEXP_DATE, $_POST['day']))
			jsonError();

		$day = $_POST['day'];

		if(!$z = _zayavQuery($zayav_id))
			jsonError();

		if($day != $z['srok'] && $day != '0000-00-00') {
			$sql = "UPDATE `_zayav`
					SET `srok`='".$day."'
					WHERE `id`=".$zayav_id;
			query($sql);
			_history(array(
				'type_id' => 52,
				'client_id' => $z['client_id'],
				'zayav_id' => $zayav_id,
				'v1' => '<table><tr>'.
					'<th>Срок:'.
						'<td>'.($z['srok'] == '0000-00-00' ? 'не указан' : FullData($z['srok'], 0, 1, 1)).
						'<td>»'.
						'<td>'.FullData($day, 0, 1, 1).
					'</table>'
			));
		}

		jsonSuccess();
		break;

	case 'zayav_executer_change'://изменение исполнителя заявки
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();

		if(!$z = _zayavQuery($zayav_id))
			jsonError();

		$executer_id = _num($_POST['executer_id']);
		if($executer_id) {//если id такого сотрудника нет в мастерской - ошибка
			$sql = "SELECT COUNT(*)
					FROM `_vkuser`
					WHERE `app_id`=".APP_ID."
					  AND `worker`
					  AND `viewer_id`=".$executer_id;
			if(!query_value($sql))
				jsonError();
		}

		if($z['executer_id'] == $executer_id)
			jsonError();

		$sql = "UPDATE `_zayav` SET `executer_id`=".$executer_id." WHERE `id`=".$zayav_id;
		query($sql);

		_history(array(
			'type_id' => 58,
			'client_id' => $z['client_id'],
			'zayav_id' => $zayav_id,
			'v1' =>
				'<table>'.
					'<tr><td>'.($z['executer_id'] ? _viewer($z['executer_id'], 'viewer_name') : '').
						'<td>»'.
						'<td>'.($executer_id ? _viewer($executer_id, 'viewer_name') : '').
					'</table>'
		));

		jsonSuccess();
		break;

	case 'dogovor_preview':
		$v = _zayavDogovorFilter($_POST);
		if(!is_array($v))
			die($v);
		_zayavDogovorPrint($v);
		exit;
	case 'dogovor_create':
		$v = _zayavDogovorFilter($_POST);
		if(!is_array($v))
			jsonError($v);

		$sql = "SELECT COUNT(`id`)
				FROM `_zayav_dogovor`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `zayav_id`=".$v['zayav_id'];
		if(query_value($sql))
			jsonError('Ошибка: на эту заявку уже заключён договор.');

		foreach($v as $k => $r)
			$v[$k] = win1251($r);

		$sql = "INSERT INTO `_zayav_dogovor` (
					`app_id`,
					`template_id`,
					`nomer`,
					`data_create`,
					`zayav_id`,
					`client_id`,
					`fio`,
					`adres`,
					`pasp_seria`,
					`pasp_nomer`,
					`pasp_adres`,
					`pasp_ovd`,
					`pasp_data`,
					`sum`,
					`avans`,
					`link`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$v['template_id'].",
					".$v['nomer'].",
					'".$v['data_create']."',
					".$v['zayav_id'].",
					".$v['client_id'].",
					'".addslashes($v['fio'])."',
					'".addslashes($v['adres'])."',
					'".addslashes($v['pasp_seria'])."',
					'".addslashes($v['pasp_nomer'])."',
					'".addslashes($v['pasp_adres'])."',
					'".addslashes($v['pasp_ovd'])."',
					'".addslashes($v['pasp_data'])."',
					".$v['sum'].",
					".$v['avans'].",
					'".$v['link']."',
					".VIEWER_ID."
				)";
		query($sql);

		$dog_id = query_insert_id('_zayav_dogovor');
		$v['id'] = $dog_id;

		//Внесение начисления по договору
		$sql = "INSERT INTO `_money_accrual` (
					`app_id`,
					`zayav_id`,
					`client_id`,
					`dogovor_id`,
					`sum`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$v['zayav_id'].",
					".$v['client_id'].",
					".$dog_id.",
					".$v['sum'].",
					".VIEWER_ID."
				)";
		query($sql);

		//внесение баланса для клиента
		_balans(array(
			'action_id' => 25,
			'client_id' => $v['client_id'],
			'zayav_id' => $v['zayav_id'],
			'dogovor_id' => $dog_id,
			'sum' => $v['sum']
		));

		//Присвоение заявке id договора и обновление адреса
		$sql = "UPDATE `_zayav`
		        SET `dogovor_id`=".$dog_id."
		        WHERE `id`=".$v['zayav_id'];
		query($sql);

		// Внесение авансового платежа, если есть
		_zayavDogovorAvansInsert($v);

		_clientBalansUpdate($v['client_id']);
		_zayavBalansUpdate($v['zayav_id']);
		_salaryZayavBonus($v['zayav_id']);

		_zayavDogovorPrint($dog_id);

		_history(array(
			'type_id' => 19,
			'client_id' => $v['client_id'],
			'zayav_id' => $v['zayav_id'],
			'dogovor_id' => $dog_id
		));

		jsonSuccess();
		break;
	case 'dogovor_edit':
		$v = _zayavDogovorFilter($_POST);
		if(!is_array($v))
			jsonError($v);

		$sql = "SELECT *
				FROM `_zayav_dogovor`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `id`=".$v['id'];
		if(!$dog = query_assoc($sql))
			jsonError('Ошибка: договора не существует.');

		foreach($v as $k => $r)
			$v[$k] = win1251($r);

		$sql = "UPDATE `_zayav_dogovor`
				SET `nomer`=".$v['nomer'].",
					`data_create`='".$v['data_create']."',
					`zayav_id`=".$v['zayav_id'].",
					`client_id`=".$v['client_id'].",
					`fio`='".addslashes($v['fio'])."',
					`adres`='".addslashes($v['adres'])."',
					`pasp_seria`='".addslashes($v['pasp_seria'])."',
					`pasp_nomer`='".addslashes($v['pasp_nomer'])."',
					`pasp_adres`='".addslashes($v['pasp_adres'])."',
					`pasp_ovd`='".addslashes($v['pasp_ovd'])."',
					`pasp_data`='".addslashes($v['pasp_data'])."',
					`sum`=".$v['sum'].",
					`avans`=".$v['avans'].",
					`link`='".$v['link']."'
				WHERE `id`=".$dog['id'];
		query($sql);

		// Обновление начисления по договору
		$sql = "UPDATE `_money_accrual`
				SET `sum`=".$v['sum']."
				WHERE `dogovor_id`=".$dog['id'];
		query($sql);

		if($dog['sum'] != $v['sum']) {
			_balans(array(
				'action_id' => 37,
				'client_id' => $v['client_id'],
				'zayav_id' => $v['zayav_id'],
				'dogovor_id' => $dog['id'],
				'sum_old' => $dog['sum'],
				'sum' => $v['sum']
			));
		}

		// Обновление авансового платежа
		$sql = "SELECT *
				FROM `_money_income`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `dogovor_id`=".$dog['id']."
				LIMIT 1";
		if($avans = query_assoc($sql)) {
			if(!$v['avans'] || !$v['invoice_id']) {//удаление платежа
				$sql = "UPDATE `_money_income`
						SET `deleted`=1
						WHERE `id`=".$avans['id'];
				query($sql);
				//баланс для расчётного счёта
				_balans(array(
					'action_id' => 2,
					'invoice_id' => $avans['invoice_id'],
					'sum' => $avans['sum'],
					'income_id' => $avans['id']
				));
				//баланс для клиента
				_balans(array(
					'action_id' => 28,
					'client_id' => $v['client_id'],
					'zayav_id' => $v['zayav_id'],
					'dogovor_id' => $dog['id'],
					'sum' => $avans['sum']
				));
			} elseif($avans['sum'] != $v['avans'] || $avans['invoice_id'] != $v['invoice_id']) {//изменение платежа
				$sql = "UPDATE `_money_income`
						SET `sum`=".$v['avans'].",
							`invoice_id`=".$v['invoice_id']."
						WHERE `id`=".$avans['id'];
				query($sql);

				if($avans['invoice_id'] != $v['invoice_id']) {
					//удаление для предыдущего счёта
					_balans(array(
						'action_id' => 2,
						'invoice_id' => $avans['invoice_id'],
						'sum' => $avans['sum'],
						'income_id' => $avans['id']
					));
					//внесение для нового счёта
					_balans(array(
						'action_id' => 1,
						'invoice_id' => $v['invoice_id'],
						'sum' => $v['avans'],
						'income_id' => $avans['id']
					));
				} else {
					//обновление суммы платежа, если счёт не менялся
					_balans(array(
						'action_id' => 10,
						'invoice_id' => $avans['invoice_id'],
						'sum_old' => $avans['sum'],
						'sum' => $v['avans'],
						'income_id' => $avans['id']
					));
				}

				if($avans['sum'] != $v['avans']) {
					//баланс для клиента
					_balans(array(
						'action_id' => 41,
						'client_id' => $v['client_id'],
						'zayav_id' => $v['zayav_id'],
						'dogovor_id' => $dog['id'],
						'sum_old' => $avans['sum'],
						'sum' => $v['avans']
					));
				}
			}
		} else
			_zayavDogovorAvansInsert($v);

		_zayavDogovorPrint($dog['id']);

		_zayavBalansUpdate($v['zayav_id']);
		_salaryZayavCheck($v['zayav_id']);
		_salaryZayavBonus($v['zayav_id']);


		unlink(PATH_DOGOVOR.'/'.$dog['link'].'.doc');

		$changes =
			_historyChange('ФИО', $dog['fio'], $v['fio']).
			_historyChange('Адрес', $dog['adres'], $v['adres']).
			_historyChange('Паспорт серия', $dog['pasp_seria'], $v['pasp_seria']).
			_historyChange('Паспорт номер', $dog['pasp_nomer'], $v['pasp_nomer']).
			_historyChange('Паспорт прописка', $dog['pasp_adres'], $v['pasp_adres']).
			_historyChange('Паспорт кем выдан', $dog['pasp_ovd'], $v['pasp_ovd']).
			_historyChange('Паспорт когда выдан', $dog['pasp_data'], $v['pasp_data']).
			_historyChange('Дата заключения', _dataDog($dog['data_create']), _dataDog($v['data_create'])).
			_historyChange('Номер', $dog['nomer'], $v['nomer']).
			_historyChange('Сумма', _cena($dog['sum']), _cena($v['sum'])).
			_historyChange('Авансовый платёж', _cena($dog['avans']), _cena($v['avans']));
		if($changes)
			_history(array(
				'type_id' => 42,
				'client_id' => $v['client_id'],
				'zayav_id' => $v['zayav_id'],
				'dogovor_id' => $dog['id'],
				'v1' => '<table>'.$changes.'</table>'
			));

		jsonSuccess();
		break;
	case 'dogovor_terminate'://расторжение договора
		if(!$dogovor_id = _num($_POST['dogovor_id']))
			jsonError();

		$sql = "SELECT *
				FROM `_zayav_dogovor`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `id`=".$dogovor_id;
		if(!$dog = query_assoc($sql))
			jsonError();

		if(!$z = _zayavQuery($dog['zayav_id']))
			jsonError();

		query("UPDATE `_zayav_dogovor` SET `deleted`=1 WHERE `id`=".$dogovor_id);
		query("UPDATE `_money_accrual` SET `deleted`=1 WHERE `dogovor_id`=".$dogovor_id);
		query("UPDATE `_zayav` SET `dogovor_id`=0 WHERE `id`=".$dog['zayav_id']);

		//внесение баланса для клиента
		_balans(array(
			'action_id' => 26,
			'client_id' => $z['client_id'],
			'zayav_id' => $z['id'],
			'dogovor_id' => $dogovor_id,
			'sum' => $dog['sum']
		));

		_clientBalansUpdate($z['client_id']);
		_zayavBalansUpdate($z['id']);
		_salaryZayavBonus($z['id']);

		_history(array(
			'type_id' => 96,
			'client_id' => $z['client_id'],
			'zayav_id' => $z['id'],
			'dogovor_id' => $dogovor_id
		));

		jsonSuccess();
		break;

	case 'zayav_attach_cancel':
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();

		$v = _txt($_POST['v']);
		if($v != 'attach' && $v != 'attach1')
			jsonError();

		if(!$z = _zayavQuery($zayav_id))
			jsonError();

		$sql = "UPDATE `_zayav`
				SET `".$v."_cancel`=1,
					`".$v."_cancel_viewer_id`=".VIEWER_ID.",
					`".$v."_cancel_dtime`=CURRENT_TIMESTAMP
				WHERE `id`=".$zayav_id;
		query($sql);

		$zpu = _zayavPole($z['service_id']);

		_history(array(
			'type_id' => 103,
			'client_id' => $z['client_id'],
			'zayav_id' => $zayav_id,
			'v1' => str_replace("\n", ' ', $zpu[$v == 'attach' ? 22 : 34]['name'])
		));

		jsonSuccess();
		break;

	case 'zayav_expense_add'://внесение расхода по заявке
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();
		if(!$cat_id = _num($_POST['cat_id']))
			jsonError();

		if(!$z = _zayavQuery($zayav_id))
			jsonError('Заявки id:'.$zayav_id.' не существует');

		$txt = '';
		$worker_id = 0;
		$workerOnPay = 0;//внесение баланса сотрудника при отсутствии долга заявки
		$tovar_id = 0;
		$tovar_avai_id = 0;
		$tovar_count = 0;
		$attach_id = 0;
		$mon = 0;
		$year = 0;
		$sum = _cena($_POST['sum']);

		switch(_zayavExpense($cat_id, 'dop')) {
			case 1: $txt = _txt($_POST['dop']); break;
			case 2:
				if($worker_id = _num($_POST['dop'])) {
					if($workerOnPay = !(_viewerRule($worker_id, 'RULE_SALARY_ZAYAV_ON_PAY') && $z['sum_dolg'] < 0)) {
						$mon = intval(strftime('%m'));
						$year = strftime('%Y');
					}
				}
				break;
			case 5:
				if(!$tovar_id = _num($_POST['dop']))
					jsonError('Не выбран товар');
				if(!$tovar_count = _ms($_POST['count']))
					jsonError('Некорректно указано количество');
				if(!$r = _tovarQuery($tovar_id))
					jsonError('Товара id'.$tovar_id.' не существует');
				break;
			case 3:
				if(!$tovar_avai_id = _num($_POST['dop']))
					jsonError('Не выбрано наличие товара');
				if(!$tovar_count = _ms($_POST['count']))
					jsonError('Не указано количество');
				$sql = "SELECT *
						FROM `_tovar_avai`
						WHERE `app_id`=".APP_ID."
						  AND `id`=".$tovar_avai_id;
				if(!$r = query_assoc($sql))
					jsonError('Наличия товара id'.$tovar_avai_id.' не существует');
				if($tovar_count > $r['count'])
					jsonError('Указанное количество превышает допустимое');
				$tovar_id = $r['tovar_id'];
				break;
			case 4: $attach_id = _num($_POST['dop']); break;
		}
		
		$sql = "INSERT INTO `_zayav_expense` (
					`app_id`,
					`zayav_id`,
					`category_id`,
					`txt`,
					`worker_id`,
					`tovar_id`,
					`tovar_avai_id`,
					`tovar_count`,
					`attach_id`,
					`sum`,
					`mon`,
					`year`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$zayav_id.",
					".$cat_id.",
					'".addslashes($txt)."',
					".$worker_id.",
					".$tovar_id.",
					".$tovar_avai_id.",
					".$tovar_count.",
					".$attach_id.",
					".$sum.",
					".$mon.",
					".$year.",
					".VIEWER_ID."
				)";
		query($sql);
		$insert_id = query_insert_id('_zayav_expense');

		_zayavBalansUpdate($zayav_id);

		//внесение истории баланса сотрудника
		if($workerOnPay)
			_balans(array(
				'action_id' => 19,
				'worker_id' => $worker_id,
				'zayav_id' => $zayav_id,
				'sum' => $sum
			));

		_tovarAvaiUpdate($tovar_id);

		$sql = "SELECT *
				FROM `_zayav_expense`
				WHERE `id`=".$insert_id;
		$r = query_assoc($sql);
		
		_history(array(
			'type_id' => 101,
			'client_id' => $z['client_id'],
			'zayav_id' => $zayav_id,
			'v1' =>
				'<table>'.
					'<tr><td>'._zayavExpense($cat_id).
						'<td>'._zayavExpenseDopVal($r).
						'<td>'._sumSpace($sum).' р.'.
				'</table>'
		));


		$send['html'] = utf8(_zayav_expense_spisok($zayav_id, $insert_id));
		jsonSuccess($send);
		break;
	case 'zayav_expense_del'://удаление расхода по заявке
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_zayav_expense`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		$r = query_assoc($sql);

		if($r['salary_list_id'] && !SA)
			jsonError('Расход учтён в листе выдачи з/п');

		if($r['v1'] && !SA)
			jsonError('Расход отмечен как "Счёт оплачен"');

		$sql = "DELETE FROM `_zayav_expense` WHERE `id`=".$id;
		query($sql);

		_zayavBalansUpdate($r['zayav_id']);

		//внесение истории баланса сотрудника
		if($r['worker_id'] && $r['year'] && $r['mon'])
			_balans(array(
				'action_id' => 21,
				'worker_id' => $r['worker_id'],
				'zayav_id' => $r['zayav_id'],
				'sum' => $r['sum']
			));

		_tovarAvaiUpdate($r['tovar_id']);

		$z = _zayavQuery($r['zayav_id']);

		_history(array(
			'type_id' => 102,
			'client_id' => $z['client_id'],
			'zayav_id' => $r['zayav_id'],
			'v1' =>
				'<table>'.
					'<tr><td>'._zayavExpense($r['category_id']).
						'<td>'._zayavExpenseDopVal($r).
						'<td>'._sumSpace($r['sum']).' р.'.
				'</table>'
		));

		$send['html'] = utf8(_zayav_expense_spisok($r['zayav_id']));
		jsonSuccess($send);
		break;

	case 'ze_attach_schet_spisok':
		$_POST['find'] = win1251(@$_POST['find']);
		$data = _zayav_expense_attach_schet_spisok($_POST);
		$send['spisok'] = utf8($data['spisok']);
		jsonSuccess($send);
		break;
	case 'ze_attach_schet_pay':
		if(!$id = _num($_POST['id']))
			jsonError('Некорректный id расхода по заявке');

		$sql = "SELECT *
				FROM `_zayav_expense`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('Pасхода по заявке не существует');

		if(!$z = _zayavQuery($r['zayav_id']))
			jsonError('Заявки id:'.$r['zayav_id'].' не существует');

		if($r['v1'])
			jsonError('Счёт уже был оплачен');

		$sql = "UPDATE `_zayav_expense`
				SET `v1`=1,
					`v1_viewer_id`=".VIEWER_ID.",
					`v1_dtime`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;
		query($sql);

		_history(array(
			'type_id' => 132,
			'client_id' => $z['client_id'],
			'zayav_id' => $r['zayav_id'],
			'attach_id' => $r['attach_id'],
			'v1' => _cena($r['sum'])
		));

		jsonSuccess();
		break;


	case 'zayav_kvit':
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();

		$active = _bool(@$_POST['active']);
		$defect = _txt($_POST['defect']);

		if(empty($defect))
			jsonError();

		if(!$z = _zayavQuery($zayav_id))
			jsonError();

		$sql = "DELETE FROM `_zayav_kvit`
				WHERE `app_id`=".APP_ID."
				  AND !`active`
				  AND `zayav_id`=".$zayav_id;
		query($sql);

		$sql = "SELECT `tovar_id`
				FROM `_zayav_tovar`
				WHERE `zayav_id`=".$zayav_id."
				ORDER BY `id`
				LIMIT 1";
		$tovar_id = query_value($sql);

		$sql = "INSERT INTO `_zayav_kvit` (
					`app_id`,
					`zayav_id`,

					`tovar_id`,
					`color_id`,
					`color_dop`,
					`imei`,
					`serial`,
					`equip`,

					`client_name`,
					`client_phone`,

					`defect`,
					`active`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$zayav_id.",

					".$tovar_id.",

					".$z['color_id'].",
					".$z['color_dop'].",

					'".addslashes($z['imei'])."',
					'".addslashes($z['serial'])."',
					'".addslashes($z['equip'])."',

					'".addslashes(_clientVal($z['client_id'], 'name'))."',
					'".addslashes(_clientVal($z['client_id'], 'phone'))."',

					'".addslashes($defect)."',
					".$active.",
					".VIEWER_ID."
				)";
		query($sql);
		$send['id'] = query_insert_id('_zayav_kvit');

		jsonSuccess($send);
		break;

	case 'zayav_gn_polosa_nomer_change'://изменение номера полосы из списка заявок (для рекламы)
		if(!$zgn_id = _num($_POST['zgn_id']))
			jsonError();

		$polosa = _num($_POST['polosa']);

		$sql = "SELECT *
				FROM `_zayav_gazeta_nomer`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$zgn_id;
		if(!$r = query_assoc($sql))
			jsonError();

		if($r['polosa'] == $polosa)
			jsonError();

		$sql = "UPDATE `_zayav_gazeta_nomer`
				SET `polosa`=".$polosa."
				WHERE `id`=".$zgn_id;
		query($sql);

		jsonSuccess();
		break;

	case 'cartridge_new'://внесение новой модели картриджа
		if(!$type_id = _num($_POST['type_id']))
			jsonError();

		$name = _txt($_POST['name']);
		$cost_filling = _num($_POST['cost_filling']);
		$cost_restore = _num($_POST['cost_restore']);
		$cost_chip = _num($_POST['cost_chip']);

		if(empty($name))
			jsonError();

		$sql = "INSERT INTO `_setup_cartridge` (
					`type_id`,
					`name`,
					`cost_filling`,
					`cost_restore`,
					`cost_chip`
				) VALUES (
					".$type_id.",
					'".addslashes($name)."',
					".$cost_filling.",
					".$cost_restore.",
					".$cost_chip."
				)";
		query($sql);
		$send['insert_id'] = query_insert_id('_setup_cartridge');

		xcache_unset(CACHE_PREFIX.'cartridge');
		_appJsValues();

		_history(array(
			'type_id' => 1030,
			'v1' => $name
		));

		if($_POST['from'] == 'setup')
			$send['spisok'] = utf8(setup_cartridge_spisok($send['insert_id']));
		else {
			$sql = "SELECT `id`,`name` FROM `_setup_cartridge` ORDER BY `name`";
			$send['spisok'] = query_selArray($sql);
		}

		jsonSuccess($send);
		break;
	case 'zayav_cartridge_add'://добавление картриджей к заявке
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();

		// Если не указан ни один картридж
		if(!$ids = _ids($_POST['ids'], 1))
			jsonError();

		if(!$z = _zayavQuery($zayav_id))
			jsonError();

		$cartgidge = array();
		foreach($ids as $id) {
			$sql = "INSERT INTO `_zayav_cartridge` (
						`zayav_id`,
						`cartridge_id`
					) VALUES (
						".$zayav_id.",
						".$id."
					)";
			query($sql);
			$cartgidge[] = '<u>'._cartridgeName($id).'</u>';
		}


		_history(array(
			'type_id' => 55,
			'client_id' => $z['client_id'],
			'zayav_id' => $zayav_id,
			'v1' => implode(', ', $cartgidge)
		));

		$send['html'] = utf8(_zayavInfoCartridge_spisok($zayav_id));
		jsonSuccess($send);
		break;
	case 'zayav_cartridge_edit'://применение действия по картриджу
		if(!$id = _num($_POST['id']))
			jsonError();
		if(!$cartridge_id = _num($_POST['cart_id']))
			jsonError();

		$filling = _bool($_POST['filling']);
		$restore = _bool($_POST['restore']);
		$chip = _bool($_POST['chip']);
		$cost = _cena($_POST['cost']);
		$prim = _txt($_POST['prim']);

		$sql = "SELECT * FROM `_zayav_cartridge` WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		if(!$z = _zayavQuery($r['zayav_id']))
			jsonError();

		$sql = "UPDATE `_zayav_cartridge`
				SET `cartridge_id`=".$cartridge_id.",
					`filling`=".$filling.",
					`restore`=".$restore.",
					`chip`=".$chip.",
					`cost`=".$cost.",
					`dtime_ready`=".($filling || $restore || $chip ? "CURRENT_TIMESTAMP" : "'0000-00-00 00:00:00'").",
					`prim`='".addslashes($prim)."'
				WHERE `id`=".$id;
		query($sql);

		$changes =
			_historyChange('Модель', _cartridgeName($r['cartridge_id']), _cartridgeName($cartridge_id)) .
			_historyChange('Стоимость', _cena($r['cost']), $cost) .
			_historyChange('Примечание', $r['prim'], $prim);
		if($r['filling'] != $filling || $r['restore'] != $restore || $r['chip'] != $chip) {
			$old = array();
			if($r['filling'])
				$old[] = 'заправлен';
			if($r['restore'])
				$old[] = 'восстановлен';
			if($r['chip'])
				$old[] = 'заменён чип';
			$new = array();
			if($filling)
				$new[] = 'заправлен';
			if($restore)
				$new[] = 'восстановлен';
			if($chip)
				$new[] = 'заменён чип';
			$changes .= _historyChange('Действие', implode(', ', $old), implode(', ', $new));
		}
		if($changes)
			_history(array(
				'type_id' => 57,
				'client_id' => $z['client_id'],
				'zayav_id' => $r['zayav_id'],
				'v1' => _cartridgeName($cartridge_id),
				'v2' => '<table>'.$changes.'</table>'
			));

		$send['html'] = utf8(_zayavInfoCartridge_spisok($r['zayav_id']));
		jsonSuccess($send);
		break;
	case 'zayav_cartridge_del'://удаление картриджа из заявки
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_zayav_cartridge`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('Картриджа уже был удалён');

		if($r['schet_id'])
			jsonError('Картридж привязан к счёту на оплату');

		if(!$z = _zayavQuery($r['zayav_id']))
			jsonError();

		$sql = "DELETE FROM `_zayav_cartridge` WHERE `id`=".$id;
		query($sql);

		_history(array(
			'type_id' => 56,
			'client_id' => $z['client_id'],
			'zayav_id' => $r['zayav_id'],
			'v1' => _cartridgeName($r['cartridge_id'])
		));

		jsonSuccess();
		break;
	case 'zayav_cartridge_ids':
		if(!$ids = _ids($_POST['ids']))
			jsonError();

		$send['arr'] = _zayavInfoCartridgeForSchet($ids);
		jsonSuccess($send);
		break;
	case 'zayav_cartridge_schet_set':
		if(!$schet_id = _num($_POST['schet_id']))
			jsonError();
		if(!$ids = _ids($_POST['ids']))
			jsonError();

		$sql = "UPDATE `_zayav_cartridge`
				SET `schet_id`=".$schet_id."
				WHERE `id` IN (".$ids.")";
		query($sql);

		jsonSuccess();
		break;

	case 'zayav_report_cols_set'://отображение, скрытие колонок отчёта по заявкам
		if(!$ids = _ids($_POST['ids']))
			jsonError();
		
		$sql = "UPDATE `_vkuser`
				SET `zayav_report_cols_show`='".$ids."'
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".VIEWER_ID;
		query($sql);

		xcache_unset(CACHE_PREFIX.'viewer_'.VIEWER_ID);

		jsonSuccess();
		break;
	case 'zayav_report_spisok'://отображение, скрытие колонок отчёта по заявкам
		$send['status'] = utf8(_zayav_report_status_count($_POST));
		$send['executer'] = utf8(_zayav_report_executer($_POST));
		$send['spisok'] = utf8(_zayav_report_spisok($_POST));
		jsonSuccess($send);
		break;
}

function _zayavValuesCheck($service_id, $zayav_id=0) {//проверка корректности полей при внесении/редактировании заявки
	$zpu = _zayavPole($service_id); //Zayav Pole Use

	$v = array();
	$upd = array();   // для редактирования заявки

	if(empty($zpu) && !$zayav_id) //если нет данных для внесения новой заявки, то выход
		return $v;


	$v['name'] = _txt(@$_POST['name']);
	if($u = @$zpu[1]) {
		if($u['require'] && !$v['name'])
			jsonError('Не заполнено поле '.$u['name']);
	}

	$v['about'] = _txt(@$_POST['about']);
	if($u = @$zpu[2]) {
		if($u['require'] && !$v['about'])
			jsonError('Не заполнено поле '.$u['name']);
		$upd[] = "`about`='".addslashes($v['about'])."'";
	}

	$v['count'] = _num(@$_POST['count']);
	if($u = @$zpu[3]) {
		if($u['require'] && !$v['count'])
			jsonError('Некорректно заполнено поле '.$u['name']);
		$upd[] = "`count`='".addslashes($v['count'])."'";
	}

	if(@$zpu[4]['require'] && empty($_POST['tovar']))
		jsonError('Не выбрано поле '.$zpu[4]['name']);

	$v['client_id'] = _num(@$_POST['client_id']);
	if($u = @$zpu[5]) {
		if($u['require'] && !$v['client_id'])
			jsonError('Не выбран '.$u['name']);
		$upd[] = "`client_id`=".$v['client_id'];
	}

	$v['adres'] = _txt(@$_POST['adres']);
	if($u = @$zpu[6]) {
		if($u['require'] && !$v['adres'])
			jsonError('Не заполнено поле '.$u['name']);
		$upd[] = "`adres`='".addslashes($v['adres'])."'";
	}

	$v['imei'] = _txt(@$_POST['imei']);
	if($u = @$zpu[7]) {
		if($u['require'] && !$v['imei'])
			jsonError('Не заполнено поле '.$u['name']);
		$upd[] = "`imei`='".addslashes($v['imei'])."'";
	}

	$v['serial'] = _txt(@$_POST['serial']);
	if($u = @$zpu[8]) {
		if($u['require'] && !$v['serial'])
			jsonError('Не заполнено поле '.$u['name']);
		$upd[] = "`serial`='".addslashes($v['serial'])."'";
	}

	$v['color_id'] = _num(@$_POST['color_id']);
	$v['color_dop'] = _num(@$_POST['color_dop']);
	if($u = @$zpu[9]) {
		if($u['require'] && !$v['color_id'])
			jsonError('Не выбран '.$u['name']);
		$upd[] = "`color_id`=".$v['color_id'];
		$upd[] = "`color_dop`=".$v['color_dop'];
	}

	$v['executer_id'] = _num(@$_POST['executer_id']);
	if(!$zayav_id && @$zpu[10]['require'] && !$v['executer_id'])
		jsonError('Не назначен '.$zpu[10]['name']);

	if(@$zpu[11]['require'] && empty($_POST['tovar']))
		jsonError('Не выбрано поле '.$zpu[11]['name']);

	$v['place_id'] = _num(@$_POST['place_id']);
	$v['place_other'] = !$v['place_id'] ? _txt(@$_POST['place_other']) : '';
	if(!$zayav_id && @$zpu[12]['require'] && !$v['place_id'] && !$v['place_other'])
		jsonError('Не выбрано поле '.$zpu[12]['name']);

	$v['srok'] = empty($_POST['srok']) ? '0000-00-00' : $_POST['srok'];
	if(!$zayav_id && @$zpu[13]['require'] && $v['srok'] == '0000-00-00')
		jsonError('Не указан срок выполнения заявки');

    $v['note'] = _txt(@$_POST['note']);
	if(!$zayav_id && @$zpu[14]['require'] && !$v['note'])
		jsonError('Не заполнено поле '.$zpu[14]['name']);

	$v['sum_cost'] = _cena(@$_POST['sum_cost']);
	$v['sum_manual'] = _bool(@$_POST['sum_manual']);
	if($u = @$zpu[15]) {
		if($u['require'] && !$v['sum_cost'])
			jsonError('Некорректно заполнено поле '.$u['name']);
		$upd[] = "`sum_manual`=".$v['sum_manual'];
		$upd[] = "`sum_cost`=".$v['sum_cost'];
	}

	$v['pay_type'] = _num(@$_POST['pay_type']);
	if($u = @$zpu[16]) {
		if($u['require'] && !$v['pay_type'])
			jsonError('Не указан вид расчёта');
		$upd[] = "`pay_type`=".$v['pay_type'];
	}

	$v['equip'] = _ids(@$_POST['equip']);
	if(@$zpu[4] && $zpu[4]['v1'])
		$upd[] = "`equip`='".$v['equip']."'";

	$v['size_x'] = _cena(@$_POST['size_x']);
	$v['size_y'] = _cena(@$_POST['size_y']);
	if($u = @$zpu[31]) {
		if($u['require'] && (!$v['size_x'] || !$v['size_y']))
			jsonError('Некорректно заполнено поле '.$u['name']);
		$upd[] = "`size_x`=".$v['size_x'];
		$upd[] = "`size_y`=".$v['size_y'];
	}

	$v['phone'] = _txt(@$_POST['phone']);
	if($u = @$zpu[37]) {
		if($u['require'] && !$v['phone'])
			jsonError('Не заполнено поле '.$u['name']);
		$upd[] = "`phone`='".addslashes($v['phone'])."'";
	}

	$v['gn'] = _txt(@$_POST['gn']);
	if($u = @$zpu[38]) {
		if(!empty($v['gn'])){
			$gn = array();
			foreach(explode('###', $v['gn']) as $r) {
				$ex = explode(':', $r);
				if(!$gn_id = _num($ex[0]))
					jsonError('Некорректный id номера газеты');
				if($u['v2'] && !_num($ex[1]))
					jsonError('Полоса указана не на всех выбранных номерах');
				$gn[] = array(
					'gn_id' => $gn_id,
					'dop' => _num($ex[1]),
					'pn' => _num($ex[2]),
					'skidka' => _num($ex[3]),
					'cena' => round($ex[4], 6)
				);
			}
			$v['gn'] = $gn;
		}
	}

	$v['skidka'] = _num(@$_POST['skidka']);
	if($u = @$zpu[39]) {
		if($u['require'] && !$v['skidka'])
			jsonError('Не указана скидка');
		$upd[] = "`skidka`=".$v['skidka'];
	}

	$v['rubric_id'] = _num(@$_POST['rubric_id']);
	$v['rubric_id_sub'] = _num(@$_POST['rubric_id_sub']);
	if($u = @$zpu[40]) {
		if($u['require'] && !$v['rubric_id'])
			jsonError('Не выбрана '.$u['name']);
		$upd[] = "`rubric_id`=".$v['rubric_id'];
		$upd[] = "`rubric_id_sub`=".$v['rubric_id_sub'];
	}

	$v['update'] = implode(',', $upd);
	$v['zpu'] = $zpu;
	return $v;
}
function _zayavNameUpdate($zayav_id, $v) {//обновление названия заявки и строки для поиска
	$z = _zayavQuery($zayav_id);

	$zpu = $v['zpu'];

	$name = $z['name'];

	//название пишется вручную
	if(isset($zpu[1]))
		$name = $v['name'];

	if(!$name || $name && !isset($zpu[1]))
		if(isset($zpu[4]) || isset($zpu[11]))
			if(@$_POST['tovar']) {
				$ex = explode(',', $_POST['tovar']);
				$ex = explode(':', $ex[0]);
				$tovar_id = $ex[0];

				$sql = "SELECT `find`
						FROM `_tovar`
						WHERE `id`=".$tovar_id;
				$name = query_value($sql);
			}

	if(!$name && isset($zpu[23]))
		$name = 'Картриджи';

	if(!$name && $z['service_id'])
		$name = _service('name', $z['service_id']).' '.$z['nomer'];

	if(!$name)
		$name = 'Заявка #'.$z['nomer'];

	$find = $name;

	$sql = "UPDATE `_zayav`
			SET `name`='".addslashes($name)."',
				`find`='".addslashes($find)."'
			WHERE `id`=".$zayav_id;
	query($sql);

	return $name;
}
function _zayavDogovorAvansInsert($v) {//Внесение авансового платежа при заключении/изменении договора
	if($v['avans']) {
		$sql = "INSERT INTO `_money_income` (
				`app_id`,
				`invoice_id`,
				`sum`,
				`client_id`,
				`zayav_id`,
				`dogovor_id`,
				`viewer_id_add`
			) VALUES (
				".APP_ID.",
				".$v['invoice_id'].",
				".$v['avans'].",
				".$v['client_id'].",
				".$v['zayav_id'].",
				".$v['id'].",
				".VIEWER_ID."
			)";
		query($sql);

		$income_id = query_insert_id('_money_income');

		//баланс для расчётного счёта
		_balans(array(
			'action_id' => 1,
			'invoice_id' => $v['invoice_id'],
			'sum' => $v['avans'],
			'income_id' => $income_id
		));

		//баланс для клиента
		_balans(array(
			'action_id' => 27,
			'client_id' => $v['client_id'],
			'zayav_id' => $v['zayav_id'],
			'dogovor_id' => $v['id'],
			'sum' => $v['avans'],
			'about' => 'Авансовый платёж.'
		));

		_history(array(
			'type_id' => 20,
			'client_id' => $v['client_id'],
			'zayav_id' => $v['zayav_id'],
			'dogovor_id' => $v['id']
		));
	}
}
function _zayavStatusRemindAdd($zayav_id) {//внесение напоминания при изменении статуса заявки
	if(!_bool($_POST['remind']))
		return;

	$remind_txt = _txt($_POST['remind_txt']);
	$remind_day = _txt($_POST['remind_day']);

	if(!$remind_txt)
		return;
	if(!preg_match(REGEXP_DATE, $remind_day))
		return;

	_remind_add(array(
		'zayav_id' => $zayav_id,
		'txt' => $remind_txt,
		'day' => $remind_day
	));
}
function _zayavTovarUpdate($zayav_id) {//обновление списка товаров заявки
	$z = _zayavQuery($zayav_id);
	$z['zpu'] = _zayavPole($z['service_id'], 1);

	if(empty($z['zpu'][4]) && empty($z['zpu'][11]))
		return;

	$sql = "DELETE FROM `_zayav_tovar` WHERE `zayav_id`=".$zayav_id;
	query($sql);

	$v = _txt($_POST['tovar']);
	if(!$v)
		return;

	$values = array();
	foreach(explode(',', $_POST['tovar']) as $r) {
		$ex = explode(':', $r);
		if(!$id = _num($ex[0]))
			continue;
		$count = _num(@$ex[1]);
		if(isset($z['zpu'][11]) && !$count)
			continue;
		if(!$count)
			$count = 1;
		$values[] = "(
			".APP_ID.",
			".$zayav_id.",
			".$id.",
			".$count."
		)";
	}

	if(empty($values))
		return;

	$sql = "INSERT INTO `_zayav_tovar` (
				`app_id`,
				`zayav_id`,
				`tovar_id`,
				`count`
			) VALUES ".implode(',', $values);
	query($sql);
}
function _zayavGazetaNomerUpdate($zayav_id, $v) {//обновление номеров газет
	if(empty($v['zpu'][38]))
		return;

	$sql = "DELETE FROM `_zayav_gazeta_nomer`
			WHERE `zayav_id`=".$zayav_id."
			  AND `gazeta_nomer_id`>="._gn('first');
	query($sql);

	if(empty($v['gn']))
		return;

	$insert = array();
	foreach($v['gn'] as $r) {
		$skidka_sum = $r['skidka'] ? round($r['cena'] / (100 - $r['skidka']) * 100 - $r['cena'], 6) : 0;
		$insert[] = '('.
			APP_ID.','.
			$zayav_id.','.
			$r['gn_id'].','.
			$r['dop'].','.
			$r['pn'].','.
			$r['cena'].','.
			$r['skidka'].','.
			$skidka_sum.
		')';
	}


	$sql = "INSERT INTO `_zayav_gazeta_nomer` (
				`app_id`,
				`zayav_id`,
				`gazeta_nomer_id`,
				`dop`,
				`polosa`,
				`cena`,
				`skidka`,
				`skidka_sum`
			) VALUES ".implode(',', $insert);
	query($sql);

	_clientBalansUpdate($v['client_id']);
	_zayavBalansUpdate($zayav_id);
}
