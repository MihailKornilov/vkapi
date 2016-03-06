<?php
switch(@$_POST['op']) {
	case 'zayav_add':
		if(!$client_id = _num($_POST['client_id']))
			jsonError();

		$type_id = _num($_POST['type_id']);
		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);
		$count = _num($_POST['count']);
		$adres = _txt($_POST['adres']);

		$device_id = _num($_POST['device_id']);
		$vendor_id = _num($_POST['vendor_id']);
		$model_id = _num($_POST['model_id']);
		$equip = _ids($_POST['equip']);
		$imei = _txt($_POST['imei']);
		$serial = _txt($_POST['serial']);

		$place_id = _num($_POST['place']);
		$place_other = !$place_id ? _txt($_POST['place_other']) : '';

		$color_id = _num($_POST['color_id']);
		$color_dop = _num($_POST['color_dop']);
		$sum_cost = _cena($_POST['sum_cost']);
		$pay_type = _num($_POST['pay_type']);
		$srok = $_POST['srok'];

		_service('const_define', $type_id);

		if(ZAYAV_INFO_DEVICE && !$device_id)
			jsonError('Не выбрано устройство');

		if(ZAYAV_INFO_SROK && $srok == '0000-00-00')
			jsonError('Не указан срок выполнения заявки');

		if(ZAYAV_INFO_PAY_TYPE && !$pay_type)
			jsonError('Не указан вид платежа');

		if(ZAYAV_INFO_COUNT && !$count)
			jsonError('Не указано количество');

		if(!ZAYAV_INFO_COUNT)
			$count = 0;

		$sql = "INSERT INTO `_zayav` (
					`app_id`,
					`type_id`,
					`client_id`,
					`nomer`,

					`name`,
					`about`,
					`count`,
					`adres`,

					`base_device_id`,
					`base_vendor_id`,
					`base_model_id`,
					`equip`,
					`imei`,
					`serial`,

					`color_id`,
					`color_dop`,
					`sum_cost`,
					`pay_type`,
					`srok`,

					`status_id`,
					`status_dtime`,

					`barcode`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$type_id.",
					".$client_id.",
					"._maxSql('_zayav', 'nomer', 1).",

					'".addslashes($name)."',
					'".addslashes($about)."',
					".$count.",
					'".addslashes($adres)."',

					".$device_id.",
					".$vendor_id.",
					".$model_id.",
					'".$equip."',
					'".addslashes($imei)."',
					'".addslashes($serial)."',

					".$color_id.",
					".$color_dop.",
					".$sum_cost.",
					".$pay_type.",
					'".$srok."',

					"._zayavStatus('default').",
					current_timestamp,

					'".rand(10, 99).(time() + rand(10000, 99999))."',
					".VIEWER_ID."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);
		$send['id'] = query_insert_id('_zayav', GLOBAL_MYSQL_CONNECT);

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
					'".$srok."',
					".VIEWER_ID."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);


		_zayavProductUpdate($send['id'], $_POST);
		_zayavNameUpdate($send['id']);

		if(ZAYAV_INFO_DEVICE)
			zayavPlaceCheck($send['id'], $place_id, $place_other);

		_note(array(
			'add' => 1,
			'p' => 'zayav',
			'id' => $send['id'],
			'txt' => _txt(@$_POST['note'])
		));

		_history(array(
			'type_id' => 73,
			'client_id' => $client_id,
			'zayav_id' => $send['id']
		));
		jsonSuccess($send);
		break;
	case 'zayav_edit':
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();
		if(!$client_id = _num($_POST['client_id']))
			jsonError();

		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);
		$count = _num($_POST['count']);
		$adres = _txt($_POST['adres']);

		$device_id = _num($_POST['device_id']);
		$vendor_id = _num($_POST['vendor_id']);
		$model_id = _num($_POST['model_id']);
		$equip = _ids($_POST['equip']);
		$imei = _txt($_POST['imei']);
		$serial = _txt($_POST['serial']);

		$color_id = _num($_POST['color_id']);
		$color_dop = _num($_POST['color_dop']);
		$sum_cost = _cena($_POST['sum_cost']);
		$pay_type = _num($_POST['pay_type']);

		if(!$z = _zayavQuery($zayav_id))
			jsonError();

		_service('const_define', $z['type_id']);

		if(ZAYAV_INFO_DEVICE && !$device_id)
			jsonError('Не выбрано устройство');

		if(ZAYAV_INFO_PAY_TYPE && !$pay_type)
			jsonError();

		if(ZAYAV_INFO_COUNT && !$count)
			jsonError();

		$sql = "UPDATE `_zayav`
				SET `client_id`=".$client_id.",
					`name`='".addslashes($name)."',
					`about`='".addslashes($about)."',
					`count`=".$count.",
					`adres`='".addslashes($adres)."',

					`base_device_id`=".$device_id.",
					`base_vendor_id`=".$vendor_id.",
					`base_model_id`=".$model_id.",
					`equip`='".addslashes($equip)."',
					`imei`='".addslashes($imei)."',
					`serial`='".addslashes($serial)."',

					`color_id`=".$color_id.",
					`color_dop`=".$color_dop.",
					`sum_cost`=".$sum_cost.",
					`pay_type`=".$pay_type."
				WHERE `id`=".$zayav_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		if($z['client_id'] != $client_id) {
			$sql = "UPDATE `_money_accrual`
					SET `client_id`=".$client_id."
					WHERE `app_id`=".APP_ID."
					  AND `zayav_id`=".$zayav_id."
					  AND `client_id`=".$z['client_id'];
			query($sql, GLOBAL_MYSQL_CONNECT);
			$sql = "UPDATE `_money_income`
					SET `client_id`=".$client_id."
					  AND `zayav_id`=".$zayav_id."
					  AND `client_id`=".$z['client_id'];
			query($sql, GLOBAL_MYSQL_CONNECT);
			$sql = "UPDATE `_money_refund`
					SET `client_id`=".$client_id."
					WHERE `app_id`=".APP_ID."
					  AND `zayav_id`=".$zayav_id."
					  AND `client_id`=".$z['client_id'];
			query($sql, GLOBAL_MYSQL_CONNECT);
			_clientBalansUpdate($z['client_id']);
			_clientBalansUpdate($client_id);
		}

		$productArrOld = array();
		if($r = _zayav_product_query($zayav_id)) {
			$r = $r[key($r)];
			$productArrOld = array(
				_num($r['product_id']),
				_num($r['product_sub_id']),
				_num($r['count'])
			);
		}
		$productArrNew = _zayavProductUpdate($zayav_id, $_POST);

		_zayavNameUpdate($zayav_id);

		$labelName = 'Название';
		if(ZAYAV_INFO_DEVICE)
			$labelName = 'Устройство';

		if($changes =
			_historyChange('Клиент', $z['client_id'], $client_id, _clientVal($z['client_id'], 'go'), _clientVal($client_id, 'go')).
			_historyChange($labelName, $z['name'], $name).
			_historyChange('Описание', $z['about'], $about).
			_historyChange('Количество', $z['count'], $count).
			_historyChange('Изделия', _zayavProductTxt($productArrOld), _zayavProductTxt($productArrNew)).
			_historyChange('Адрес', $z['adres'], $adres).
		(ZAYAV_INFO_DEVICE ?
			_historyChange('Комплект', zayavEquipSpisok($z['equip']), zayavEquipSpisok($equip))
		: '').
			_historyChange('IMEI', $z['imei'], $imei).
			_historyChange('Серийный номер', $z['serial'], $serial).
			_historyChange('Цвет', _color($z['color_id'], $z['color_dop']), _color($color_id, $color_dop)).
			_historyChange('Предварительная стоимость', _cena($z['sum_cost']), $sum_cost).
			_historyChange('Расчёт', _payType($z['pay_type']), _payType($pay_type)))
			_history(array(
				'type_id' => 72,
				'client_id' => $z['client_id'],
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
				SET `deleted`=1
				WHERE `id`=".$zayav_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		$sql = "SELECT IFNULL(SUM(`sum`),0)
				FROM `_money_accrual`
				WHERE !`deleted`
				  AND `zayav_id`=".$zayav_id;
		if($accrual_sum = query_value($sql, GLOBAL_MYSQL_CONNECT)) {
			//удаление произвольных начислений
			$sql = "UPDATE `_money_accrual`
					SET `deleted`=1,
						`viewer_id_del`=".VIEWER_ID.",
						`dtime_del`=CURRENT_TIMESTAMP
					WHERE !`deleted`
					  AND `zayav_id`=".$zayav_id;
			query($sql, GLOBAL_MYSQL_CONNECT);

			_zayavBalansUpdate($zayav_id);

			//внесение баланса для клиента
			_balans(array(
				'action_id' => 40,
				'client_id' => $z['client_id'],
				'zayav_id' => $zayav_id,
				'sum' => $accrual_sum
			));
		}

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
		jsonSuccess($send);
		break;

	case 'zayav_type_js'://получение констант для js конкретного типа заявок
		if(!$type_id = _num($_POST['type_id']))
			jsonError();

		$send['js'] = _service('const_arr', $type_id);

		jsonSuccess($send);
		break;
	case 'zayav_status':
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();
		if(!$status_id = _num($_POST['status_id']))
			jsonError();

		if(!$z = _zayavQuery($zayav_id))
			jsonError();

		_service('const_define', $z['type_id']);

		$place_id = _num($_POST['place']);
		$place_other = !$place_id ? _txt($_POST['place_other']) : '';
//		if(ZAYAV_INFO_DEVICE && !$place_id && !$place_other)
//			jsonError();

		$executer_id = _num($_POST['executer_id']);

		if(!preg_match(REGEXP_DATE, $_POST['srok']))
			jsonError();
		$srok = $_POST['srok'];

		$status_day = $_POST['status_day'];
		$comm = _txt($_POST['comm']);

//		if(ZAYAV_INFO_SROK && $status_id == 1 && $srok == '0000-00-00')
//			jsonError();

		if(!$z = _zayavQuery($zayav_id))
			jsonError();

		if($z['status_id'] == $status_id)
			jsonError();

		$sql = "UPDATE `_zayav`
				SET `status_id`=".$status_id.",
					`status_dtime`=CURRENT_TIMESTAMP,
					`status_day`='".$status_day."',
					`executer_id`=".$executer_id.",
					`srok`='".$srok."'
				WHERE `id`=".$zayav_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

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
					'".$srok."',
					".VIEWER_ID."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);



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
	case 'zayav_type_change':
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();
		if(!$type_id = _num($_POST['type_id']))
			jsonError();

		if(!$z = _zayavQuery($zayav_id))
			jsonError();

		if($z['type_id'] == $type_id)
			jsonError();

		$sql = "UPDATE `_zayav`
				SET `type_id`=".$type_id."
				WHERE `id`=".$zayav_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		_history(array(
			'type_id' => 72,
			'client_id' => $z['client_id'],
			'zayav_id' => $zayav_id,
			'v1' => '<table>'._historyChange('Категория', _service('name', $z['type_id']), _service('name', $type_id)).'</table>'
		));

		jsonSuccess();
		break;

	case 'zayav_srok':
		if(!preg_match(REGEXP_DATE, $_POST['day']))
			jsonError();

		$day = $_POST['day'];
		$zayav_spisok = _bool($_POST['zayav_spisok']);

		$send['html'] = utf8(_zayavSrokCalendar($day, '', $zayav_spisok));
		jsonSuccess($send);
		break;
	case 'zayav_srok_next':
		if(!preg_match(REGEXP_DATE, $_POST['day']))
			jsonError();

		$day = $_POST['day'];
		$zayav_spisok = _bool($_POST['zayav_spisok']);

		$send['html'] = utf8(_zayavSrokCalendar($day, $_POST['mon'], $zayav_spisok));
		jsonSuccess($send);
		break;
	case 'zayav_srok_save':
		if(!preg_match(REGEXP_DATE, $_POST['day']))
			jsonError();

		$day = $_POST['day'];
		$zayav_id = _num(@$_POST['zayav_id']);
		$save = _bool($_POST['save']);

		if($zayav_id && $save)
			if(!_zayavSrokChange($zayav_id, $day))
				jsonError();

		$send['data'] = utf8($day == '0000-00-00' ? 'не указан' : FullData($day, 1, 0, 1));
		jsonSuccess($send);
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
			if(!query_value($sql, GLOBAL_MYSQL_CONNECT))
				jsonError();
		}

		if($z['executer_id'] == $executer_id)
			jsonError();

		$sql = "UPDATE `_zayav` SET `executer_id`=".$executer_id." WHERE `id`=".$zayav_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

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
		if(query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError('Ошибка: на эту заявку уже заключён договор.');

		foreach($v as $k => $r)
			$v[$k] = win1251($r);

		$sql = "INSERT INTO `_zayav_dogovor` (
					`app_id`,
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
		query($sql, GLOBAL_MYSQL_CONNECT);

		$dog_id = query_insert_id('_zayav_dogovor', GLOBAL_MYSQL_CONNECT);
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
		query($sql, GLOBAL_MYSQL_CONNECT);

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
		query($sql, GLOBAL_MYSQL_CONNECT);

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
		if(!$dog = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
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
		query($sql, GLOBAL_MYSQL_CONNECT);

		// Обновление начисления по договору
		$sql = "UPDATE `_money_accrual`
				SET `sum`=".$v['sum']."
				WHERE `dogovor_id`=".$dog['id'];
		query($sql, GLOBAL_MYSQL_CONNECT);

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
		if($avans = query_assoc($sql, GLOBAL_MYSQL_CONNECT)) {
			if(!$v['avans'] || !$v['invoice_id']) {//удаление платежа
				$sql = "UPDATE `_money_income`
						SET `deleted`=1
						WHERE `id`=".$avans['id'];
				query($sql, GLOBAL_MYSQL_CONNECT);
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
				query($sql, GLOBAL_MYSQL_CONNECT);

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
/*
	case 'dogovor_terminate'://расторжение договора
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();

		$sql = "SELECT * FROM `zayav` WHERE !`deleted` AND `id`=".$zayav_id;
		if(!$z = mysql_fetch_assoc(query($sql)))
			jsonError();

		$sql = "SELECT * FROM `zayav_dogovor` WHERE !`deleted` AND `zayav_id`=".$zayav_id;
		if(!$dog = mysql_fetch_assoc(query($sql)))
			jsonError();

		query("UPDATE `zayav_dogovor` SET `deleted`=1 WHERE `id`=".$dog['id']);
		query("UPDATE `accrual` SET `deleted`=1 WHERE `dogovor_id`=".$dog['id']);
		query("UPDATE `zayav` SET `dogovor_id`=0 WHERE `id`=".$zayav_id);

		_historyInsert(
			59,
			array(
				'client_id' => $z['client_id'],
				'zayav_id' => $zayav_id,
				'dogovor_id' => $dog['id']
			)
		);

		$sql = "SELECT * FROM `money` WHERE !`deleted` AND `dogovor_id`=".$dog['id'];
		if($money = mysql_fetch_assoc(query($sql))) {
			$prim = 'Возврат авансового платежа при расторжении договора №'.$dog['nomer'].'.';
			$sql = "INSERT INTO `money` (
					`zayav_id`,
					`client_id`,
					`invoice_id`,
					`sum`,
					`prim`,
					`refund`,
					`dogovor_id`,
					`owner_id`,
					`viewer_id_add`
				) VALUES (
					".$zayav_id.",
					".$z['client_id'].",
					1,
					-".$money['sum'].",
					'".$prim."',
					1,
					".$dog['id'].",
					".VIEWER_ID.",
					".VIEWER_ID."
				)";
			query($sql);

			invoice_history_insert(array(
				'action' => 13,
				'table' => 'money',
				'id' => mysql_insert_id()
			));

			_historyInsert(
				56,
				array(
					'zayav_id' => $zayav_id,
					'client_id' => $z['client_id'],
					'value' => round($money['sum'], 2),
					'value1' => $prim
				)
			);
		}

		_clientBalansUpdate($z['client_id']);
		_zayavBalansUpdate($zayav_id);

		jsonSuccess();
		break;
*/
	case 'zayav_expense_edit'://изменение расходов по заявке
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();

		$expense = _zayav_expense_test($_POST['expense']);
		if($expense === false)
			jsonError();

		if(!$z = _zayavQuery($zayav_id))
			jsonError();

		$sql = "SELECT *
				FROM `_zayav_expense`
				WHERE `app_id`=".APP_ID."
				  AND `zayav_id`=".$zayav_id."
				ORDER BY `id`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		$arr = array();
		$workerBalans = array();//сохранение старых балансов сотрудников
		while($r = mysql_fetch_assoc($q)) {
			$dop = '';
			$ze = _zayavExpense($r['category_id'], 'all');
			if($ze['txt'])
				$dop = $r['txt'];
			if($ze['worker'])
				$dop = $r['worker_id'];
			if($ze['zp'])
				$dop = $r['zp_id'];
			if($ze['attach'])
				$dop = $r['attach_id'] ? $r['attach_id'] : '.'.$r['txt'];
			$arr[] =
				$r['id'].'&&&'.
				$r['category_id'].'&&&'.
				$dop.'&&&'.
				_cena($r['sum']);
		}

		$old = _zayav_expense_array(implode('###', $arr));
		$new = _zayav_expense_array($expense);

		sort($old);
		sort($new);

		if($old != $new) {
			$toDelete = array();
			foreach($old as $r)
				$toDelete[$r[0]] = $r;

			//получение старого массива для истории действий
			$sql = "SELECT *
					FROM `_zayav_expense`
					WHERE `app_id`=".APP_ID."
					  AND `zayav_id`=".$zayav_id."
					ORDER BY `id`";
			$arrOld = query_arr($sql, GLOBAL_MYSQL_CONNECT);
			$arrOld = _attachValToList($arrOld);

			foreach($new as $r) {
				$mon = intval(strftime('%m'));
				$year = strftime('%Y');

				//пропускать, если есть привязка к листу выдачи зп
				if($r[0]) {
					$sql = "SELECT *
							FROM `_zayav_expense`
							WHERE `id`=".$r[0];
					if($zeEx = query_assoc($sql, GLOBAL_MYSQL_CONNECT)) {
						if($zeEx['salary_list_id']) {
							unset($toDelete[$r[0]]);
							continue;
						}
						$mon = $zeEx['mon'];
						$year = $zeEx['year'];
					}
				}

				$ze = _zayavExpense($r[1], 'all');
				$txt = '';
				if($ze['txt'])
					$txt = _txt($r[2]);
				if($ze['attach'] && !preg_match(REGEXP_NUMERIC, $r[2]))
					$txt = _txt(substr($r[2], 1));

				$worker_id = $ze['worker'] ? _num($r[2]) : 0;

				//если стоит галочка "Не начислять по заявкам с долгами" и есть долг по заявке
				if($worker_id && _viewerRule($worker_id, 'RULE_SALARY_ZAYAV_ON_PAY'))
					if($z['sum_accrual'] - $z['sum_pay'] > 0) {
						$mon = 0;
						$year = 0;
					}

				$sql = "INSERT INTO `_zayav_expense` (
							`id`,
							`app_id`,
							`zayav_id`,
							`category_id`,
							`txt`,
							`worker_id`,
							`zp_id`,
							`attach_id`,
							`sum`,
							`mon`,
							`year`,
							`viewer_id_add`
						) VALUES (
							".$r[0].",
							".APP_ID.",
							".$zayav_id.",
							".$r[1].",
							'".addslashes($txt)."',
							".$worker_id.",
							".($ze['zp'] ? _num($r[2]) : 0).",
							".($ze['attach'] ? _num($r[2]) : 0).",
							".$r[3].",
							".$mon.",
							".$year.",
							".VIEWER_ID."
						) ON DUPLICATE KEY UPDATE
							`category_id`=VALUES(`category_id`),
							`txt`=VALUES(`txt`),
							`zp_id`=VALUES(`zp_id`),
							`worker_id`=VALUES(`worker_id`),
							`attach_id`=VALUES(`attach_id`),
							`sum`=VALUES(`sum`),
							`mon`=VALUES(`mon`),
							`year`=VALUES(`year`)";
				query($sql, GLOBAL_MYSQL_CONNECT);

				unset($toDelete[$r[0]]);
			}

			//удаление расходов, которые были удалены
			if(!empty($toDelete)) {
				$sql = "DELETE FROM `_zayav_expense` WHERE `id` IN (".implode(',', array_keys($toDelete)).")";
				query($sql, GLOBAL_MYSQL_CONNECT);
			}

			//получение нового массива для истории дейсвтий
			$sql = "SELECT *
					FROM `_zayav_expense`
					WHERE `app_id`=".APP_ID."
					  AND `zayav_id`=".$zayav_id."
					ORDER BY `id`";
			$arrNew = query_arr($sql, GLOBAL_MYSQL_CONNECT);
			$arrNew = _attachValToList($arrNew);

			_zayav_expense_worker_balans($z, $arrOld, $arrNew);

			$old = _zayav_expense_html($arrOld, false, $arrNew);
			$new = _zayav_expense_html($arrNew, false, $arrOld, true);

			_zayavBalansUpdate($zayav_id);
			_salaryZayavBonus($zayav_id);

			if($old != $new)
				_history(array(
					'type_id' => 30,
					'client_id' => $z['client_id'],
					'zayav_id' => $zayav_id,
					'v1' => '<table><tr><td>'.$old.'<td>»<td>'.$new.'</table>'
				));
		}

		$send['html'] = utf8(_zayav_expense_spisok($zayav_id));
		jsonSuccess($send);
		break;
}

function _zayavProductUpdate($zayav_id, $p) {//внесение|обновление изделия для конкретной заявки
	if(!$product_id = _num(@$p['product_id']))
		return array();

	$product_sub_id = _num($p['product_sub_id']);
	$count = _num($p['product_count']);

	$sql = "DELETE FROM `_zayav_product` WHERE `zayav_id`=".$zayav_id;
	query($sql, GLOBAL_MYSQL_CONNECT);

	$sql = "INSERT INTO `_zayav_product` (
				`app_id`,
				`zayav_id`,
				`product_id`,
				`product_sub_id`,
				`count`
			) VALUES (
				".APP_ID.",
				".$zayav_id.",
				".$product_id.",
				".$product_sub_id.",
				".$count."
			)";
	query($sql, GLOBAL_MYSQL_CONNECT);

	//если не было указано названия заявки, то вносится название продукта
	$z = _zayavQuery($zayav_id);
	if(!$z['name']) {
		$product_name = _product($product_id);
		if($product_sub_id)
			$product_name .= ' '._productSub($product_sub_id);

		$sql = "UPDATE `_zayav`
				SET `name`='".addslashes($product_name)."'
				WHERE `id`=".$zayav_id;
		query($sql, GLOBAL_MYSQL_CONNECT);
	}

	return array($product_id, $product_sub_id, $count);
}
function _zayavProductTxt($r) {//преобразование id изделий в текст
	return $r ? _product($r[0]).($r[1] ? ' '._productSub($r[1]) : '').': '.$r[2].' шт.' : '';
}
function _zayavNameUpdate($zayav_id) {//обновление названия заявки и строки для поиска
	$z = _zayavQuery($zayav_id);

	$name = $z['name'];

	if(ZAYAV_INFO_DEVICE)
		$name = _deviceName($z['base_device_id'])._vendorName($z['base_vendor_id'])._modelName($z['base_model_id']);

	if(ZAYAV_INFO_CARTRIDGE)
		$name = 'Картриджи';

	if(!$name)
		$name = 'Заявка #'.$z['nomer'];

	$find = $name;

	$sql = "UPDATE `_zayav`
			SET `name`='".addslashes($name)."',
				`find`='".addslashes($find)."'
			WHERE `id`=".$zayav_id;
	query($sql, GLOBAL_MYSQL_CONNECT);

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
		query($sql, GLOBAL_MYSQL_CONNECT);

		$income_id = query_insert_id('_money_income', GLOBAL_MYSQL_CONNECT);

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
function _zayavSrokChange($zayav_id, $day) {//изменение срока выполнения
	if(!$z = _zayavQuery($zayav_id))
		return false;
	if($day != $z['srok'] && $day != '0000-00-00') {
		$sql = "UPDATE `_zayav`
				SET `srok`='".$day."'
				WHERE `id`=".$zayav_id;
		query($sql, GLOBAL_MYSQL_CONNECT);
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
	return true;
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
