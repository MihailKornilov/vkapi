<?php
switch(@$_POST['op']) {
	case 'zayav_add':
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

		$place_id = _num($_POST['place']);
		$place_other = !$place_id ? _txt($_POST['place_other']) : '';

		$color_id = _num($_POST['color_id']);
		$color_dop = _num($_POST['color_dop']);
		$diagnost = _bool($_POST['diagnost']);
		$sum_cost = _cena($_POST['sum_cost']);
		$pay_type = _num($_POST['pay_type']);
		$day_finish = $_POST['day_finish'];


		if(ZAYAV_INFO_DEVICE) {
			if(!$device_id)
				jsonError();
			$name = _deviceName($device_id)._vendorName($vendor_id)._modelName($model_id);
		}

		if(ZAYAV_INFO_SROK && $day_finish == '0000-00-00')
			jsonError();

		if(ZAYAV_INFO_PAY_TYPE && !$pay_type)
			jsonError();

		if(ZAYAV_INFO_COUNT && !$count)
			jsonError();

		if(!$name)
			$name = 'Заявка #'._maxSql('_zayav', 'nomer', 1);

		$sql = "INSERT INTO `_zayav` (
					`app_id`,
					`ws_id`,
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
					`diagnost`,
					`sum_cost`,
					`pay_type`,
					`day_finish`,

					`status_dtime`,

					`barcode`,
					`viewer_id_add`,
					`find`
				) VALUES (
					".APP_ID.",
					".WS_ID.",
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
					".$diagnost.",
					".$sum_cost.",
					".$pay_type.",
					'".$day_finish."',

					current_timestamp,

					'".rand(10, 99).(time() + rand(10000, 99999))."',
					".VIEWER_ID.",
					''
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);
		$send['id'] = query_insert_id('_zayav', GLOBAL_MYSQL_CONNECT);

		_zayavProductUpdate($send['id'], $_POST);

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
		$diagnost = _bool($_POST['diagnost']);
		$sum_cost = _cena($_POST['sum_cost']);
		$pay_type = _num($_POST['pay_type']);

		if(!$z = _zayavQuery($zayav_id))
			jsonError();

		if(ZAYAV_INFO_DEVICE) {
			if(!$device_id)
				jsonError();
			$name = _deviceName($device_id)._vendorName($vendor_id)._modelName($model_id);
		}

		if(ZAYAV_INFO_PAY_TYPE && !$pay_type)
			jsonError();

		if(ZAYAV_INFO_COUNT && !$count)
			jsonError();

		$sql = "UPDATE `_zayav` SET
					`client_id`=".$client_id.",
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
					`diagnost`=".$diagnost.",
					`sum_cost`=".$sum_cost.",
					`pay_type`=".$pay_type."
				WHERE `id`=".$zayav_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		if($z['client_id'] != $client_id) {
			$sql = "UPDATE `_money_accrual`
					SET `client_id`=".$client_id."
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND `zayav_id`=".$zayav_id."
					  AND `client_id`=".$z['client_id'];
			query($sql, GLOBAL_MYSQL_CONNECT);
			$sql = "UPDATE `_money_income`
					SET `client_id`=".$client_id."
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND `zayav_id`=".$zayav_id."
					  AND `client_id`=".$z['client_id'];
			query($sql, GLOBAL_MYSQL_CONNECT);
			$sql = "UPDATE `_money_refund`
					SET `client_id`=".$client_id."
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
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
			_historyChange('Диагностика', _daNet($z['diagnost']), _daNet($diagnost)).
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
	case 'zayav_spisok':
		$_POST['find'] = win1251($_POST['find']);
		$data = _zayav_spisok($_POST);
		if($data['filter']['page'] == 1)
			$send['all'] = utf8($data['result']);
		$send['spisok'] = utf8($data['spisok']);
		jsonSuccess($send);
		break;

	case 'zayav_status':
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();
		if(!$status = _num($_POST['status']))
			jsonError();

		$place_id = _num($_POST['place']);
		$place_other = !$place_id ? _txt($_POST['place_other']) : '';
		if(ZAYAV_INFO_DEVICE && !$place_id && !$place_other)
			jsonError();

		if(!preg_match(REGEXP_DATE, $_POST['day_finish']))
			jsonError();
		$day_finish = $_POST['day_finish'];

		$status_day = $_POST['status_day'];
		$reason = $status == 3 ? 'Причина: '._txt($_POST['reason']) : '';

		if(ZAYAV_INFO_SROK && $status == 1 && $day_finish == '0000-00-00')
			jsonError();

		if(!$z = _zayavQuery($zayav_id))
			jsonError();

		if($z['status'] == $status)
			jsonError();

		$sql = "UPDATE `_zayav`
				SET `status`=".$status.",
					`status_dtime`=CURRENT_TIMESTAMP,
					`status_day`='".$status_day."'
				WHERE `id`=".$zayav_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		if(ZAYAV_INFO_DEVICE)
			zayavPlaceCheck($zayav_id, $place_id, $place_other);

		_zayavDayFinishChange($zayav_id, $day_finish);

		_history(array(
			'type_id' => 71,
			'client_id' => $z['client_id'],
			'zayav_id' => $zayav_id,
			'v1' => $z['status'],
			'v2' => $status,
			'v3' => $reason
		));

		jsonSuccess();
		break;

	case 'zayav_day_finish':
		if(!preg_match(REGEXP_DATE, $_POST['day']))
			jsonError();

		$day = $_POST['day'];
		$zayav_spisok = _bool($_POST['zayav_spisok']);

		$send['html'] = utf8(_zayavFinishCalendar($day, '', $zayav_spisok));
		jsonSuccess($send);
		break;
	case 'zayav_day_finish_next':
		if(!preg_match(REGEXP_DATE, $_POST['day']))
			jsonError();

		$day = $_POST['day'];
		$zayav_spisok = _bool($_POST['zayav_spisok']);

		$send['html'] = utf8(_zayavFinishCalendar($day, $_POST['mon'], $zayav_spisok));
		jsonSuccess($send);
		break;
	case 'zayav_day_finish_save':
		if(!preg_match(REGEXP_DATE, $_POST['day']))
			jsonError();

		$day = $_POST['day'];
		$zayav_id = _num(@$_POST['zayav_id']);
		$save = _bool($_POST['save']);

		if($zayav_id && $save)
			if(!_zayavDayFinishChange($zayav_id, $day))
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
					  AND `ws_id`=".WS_ID."
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
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `zayav_id`=".$v['zayav_id'];
		if(query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError('Ошибка: на эту заявку уже заключён договор.');

		foreach($v as $k => $r)
			$v[$k] = win1251($r);

		$sql = "INSERT INTO `_zayav_dogovor` (
					`app_id`,
					`ws_id`,
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
					".WS_ID.",
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
					`ws_id`,
					`zayav_id`,
					`client_id`,
					`dogovor_id`,
					`sum`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".WS_ID.",
					".$v['zayav_id'].",
					".$v['client_id'].",
					".$dog_id.",
					".$v['sum'].",
					".VIEWER_ID."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		//Присвоение заявке id договора и обновление адреса
		$sql = "UPDATE `_zayav`
		        SET `dogovor_id`=".$dog_id."
		        WHERE `id`=".$v['zayav_id'];
		query($sql, GLOBAL_MYSQL_CONNECT);

		// Внесение авансового платежа, если есть
		_zayavDogovorAvansInsert($v);

		_clientBalansUpdate($v['client_id']);
		_zayavBalansUpdate($v['zayav_id']);

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
				  AND `ws_id`=".WS_ID."
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

		// Обновление авансового платежа
		$sql = "SELECT *
				FROM `_money_income`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
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
			}
		} else
			_zayavDogovorAvansInsert($v);

		_zayavDogovorPrint($dog['id']);

		_clientBalansUpdate($v['client_id']);
		_zayavBalansUpdate($v['zayav_id']);

		unlink(PATH_DOGOVOR.$dog['link'].'.doc');

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
		$expense = _txt($_POST['expense']);
		if(!_zayav_expense_test($expense))
			jsonError();

		$sql = "SELECT *
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$zayav_id;
		if(!$z = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "SELECT *
				FROM `_zayav_expense`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
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
				$dop = $r['attach_id'];
			$arr[] =
				$r['id'].':'.
				$r['category_id'].':'.
				$dop.':'.
				$r['sum'];
		}

		$old = _zayav_expense_array(implode(',', $arr));
		$new = _zayav_expense_array($expense);

		if($old != $new) {
			$toDelete = array();
			foreach($old as $r)
				$toDelete[$r[0]] = $r;

			//получение старого массива для истории действий
			$sql = "SELECT *
					FROM `_zayav_expense`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND `zayav_id`=".$zayav_id."
					ORDER BY `id`";
			$arrOld = query_arr($sql, GLOBAL_MYSQL_CONNECT);
			$arrOld = _attachValToList($arrOld);

			foreach($new as $r) {
				$ze = _zayavExpense($r[1], 'all');
				$sql = "INSERT INTO `_zayav_expense` (
							`id`,
							`app_id`,
							`ws_id`,
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
							".WS_ID.",
							".$zayav_id.",
							".$r[1].",
							'".($ze['txt'] ? addslashes($r[2]) : '')."',
							".($ze['worker'] ? intval($r[2]) : 0).",
							".($ze['zp'] ? intval($r[2]) : 0).",
							".($ze['attach'] ? intval($r[2]) : 0).",
							".$r[3].",
							".intval(strftime('%m')).",
							".strftime('%Y').",
							".VIEWER_ID."
						) ON DUPLICATE KEY UPDATE
							`category_id`=VALUES(`category_id`),
							`txt`=VALUES(`txt`),
							`zp_id`=VALUES(`zp_id`),
							`worker_id`=VALUES(`worker_id`),
							`attach_id`=VALUES(`attach_id`),
							`sum`=VALUES(`sum`)";
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
					  AND `ws_id`=".WS_ID."
					  AND `zayav_id`=".$zayav_id."
					ORDER BY `id`";
			$arrNew = query_arr($sql, GLOBAL_MYSQL_CONNECT);
			$arrNew = _attachValToList($arrNew);

			_zayav_expense_worker_balans($arrOld, $arrNew);

			$old = _zayav_expense_html($arrOld, false, $arrNew);
			$new = _zayav_expense_html($arrNew, false, $arrOld, true);

			_zayavBalansUpdate($zayav_id);

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
				`ws_id`,
				`zayav_id`,
				`product_id`,
				`product_sub_id`,
				`count`
			) VALUES (
				".APP_ID.",
				".WS_ID.",
				".$zayav_id.",
				".$product_id.",
				".$product_sub_id.",
				".$count."
			)";
	query($sql, GLOBAL_MYSQL_CONNECT);

	$product_name = _product($product_id);
	if($product_sub_id)
		$product_name .= ' '._productSub($product_sub_id);

	$sql = "UPDATE `_zayav`
			SET `name`='".addslashes($product_name)."'
			WHERE `id`=".$zayav_id;
	query($sql, GLOBAL_MYSQL_CONNECT);

	return array($product_id, $product_sub_id, $count);
}
function _zayavProductTxt($r) {//преобразование id изделий в текст
	return $r ? _product($r[0]).($r[1] ? ' '._productSub($r[1]) : '').': '.$r[2].' шт.' : '';
}
function _zayavDogovorAvansInsert($v) {//Внесение авансового платежа при заключении/изменении договора
	if($v['avans']) {
		$sql = "INSERT INTO `_money_income` (
				`app_id`,
				`ws_id`,
				`invoice_id`,
				`sum`,
				`client_id`,
				`zayav_id`,
				`dogovor_id`,
				`viewer_id_add`
			) VALUES (
				".APP_ID.",
				".WS_ID.",
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
	}
}
function _zayavDayFinishChange($zayav_id, $day) {//изменение срока выполнения
	if(!$z = _zayavQuery($zayav_id))
		return false;
	if($day != $z['day_finish'] && $day != '0000-00-00') {
		$sql = "UPDATE `_zayav`
				SET `day_finish`='".$day."'
				WHERE `id`=".$zayav_id;
		query($sql, GLOBAL_MYSQL_CONNECT);
		_history(array(
			'type_id' => 52,
			'client_id' => $z['client_id'],
			'zayav_id' => $zayav_id,
			'v1' => '<table><tr>'.
				'<th>Срок:'.
					'<td>'.($z['day_finish'] == '0000-00-00' ? 'не указан' : FullData($z['day_finish'], 0, 1, 1)).
					'<td>»'.
					'<td>'.FullData($day, 0, 1, 1).
				'</table>'
		));
	}
	return true;
}
