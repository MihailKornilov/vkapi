<?php
switch(@$_POST['op']) {
	case 'zayav_edit_load'://�������� ������ ������ ��� �������� ��� ��������������
		$send['html'] = utf8(_zayavPoleEdit($_POST));
		jsonSuccess($send);
		break;
	case 'zayav_add'://�������� ����� ������
		$service_id = _num($_POST['service_id']);

		if(!$v = _zayavValuesCheck($service_id))
			jsonError('��� ������ ��� �������� ������');

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
					`tovar_equip_ids`,

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
					'".$v['tovar_equip_ids']."',

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
		_zayavTovarPlaceUpdate($send['id'], $v['place_id'], $v['place_other']); //���������� ��������������� ������
		_zayavGazetaNomerUpdate($send['id'], $v);
		_zayavBalansUpdate($send['id']);
		kupezzZayavObUpdate($send['id']);
		_clientBalansUpdate($v['client_id']);

		_note(array(
			'add' => 1,
			'p' => 45,
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
			jsonError('������������ id ������');
		
		if(!$z = _zayavQuery($zayav_id))
			jsonError('������ id:'.$zayav_id.' �� ����������');

		$v = _zayavValuesCheck($z['service_id'], $zayav_id);

		$zpu = $v['zpu'];

		if(!$v['update'])
			jsonError('��� ������ ��� ���������� ������');

		if(isset($zpu[5]) && $z['client_id'] != $v['client_id']) {
			if($z['client_id'] && !$v['client_id'])
				jsonError('������ ����������� ������� ������ �� �������');

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

			$sql = "UPDATE `_zayav_gazeta_nomer`
					SET `client_id`=".$v['client_id']."
					WHERE `app_id`=".APP_ID."
					  AND `zayav_id`=".$zayav_id."
					  AND `client_id`=".$z['client_id'];
			query($sql);
		}

		$sql = "UPDATE `_zayav` SET ".$v['update']." WHERE `id`=".$zayav_id;
		query($sql);

		$v['name'] = _zayavNameUpdate($zayav_id, $v);
		_zayavTovarUpdate($zayav_id);
		_zayavGazetaNomerUpdate($zayav_id, $v);
		_zayavBalansUpdate($zayav_id);
		kupezzZayavObUpdate($zayav_id);

		_clientBalansUpdate($v['client_id']);
		if($z['client_id'] != $v['client_id'])
			_clientBalansUpdate($z['client_id']);


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
			(isset($zpu[4]['v1']) ? _historyChange('��������', _tovarEquip('spisok', $z['tovar_equip_ids']), _tovarEquip('spisok', $v['tovar_equip_ids'])) : '').
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
	case 'zayav_del'://�������� ������
		if(!$zayav_id = _num($_POST['id']))
			jsonError();

		if(!$z = _zayavQuery($zayav_id))
			jsonError('������ �� ����������');

		if($z['deleted'])
			jsonError('������ ��� ���� �������');

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
			//�������� ������������ ����������
			$sql = "UPDATE `_money_accrual`
					SET `deleted`=1,
						`viewer_id_del`=".VIEWER_ID.",
						`dtime_del`=CURRENT_TIMESTAMP
					WHERE !`deleted`
					  AND `zayav_id`=".$zayav_id;
			query($sql);

			//�������� ������� ��� �������
			_balans(array(
				'action_id' => 40,
				'client_id' => $z['client_id'],
				'zayav_id' => $zayav_id,
				'sum' => $accrual_sum
			));
		}

		//�������� ������� �����
		$sql = "DELETE FROM `_zayav_gazeta_nomer`
				WHERE `app_id`=".APP_ID."
				  AND `zayav_id`=".$zayav_id;
		query($sql);

		_zayavBalansUpdate($zayav_id);

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
			jsonError('������������ id ������');

		if(!$z = _zayavQuery($zayav_id))
			jsonError('������ id:'.$zayav_id.' �� ����������');

		if($z['onpay_checked'] != 2 && $z['onpay_checked'] != 3)
			jsonError('���������� �� ��������� � ���������� �� ����������');

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
			jsonError('������������ id ������');

		if(!$z = _zayavQuery($zayav_id))
			jsonError('������ id:'.$zayav_id.' �� ����������');

		if($z['onpay_checked'] != 2)
			jsonError('���������� �� ��������� � ���������� �� ����������');

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

		if($acc_sum = _cena($_POST['accrual_sum']))
			_accrualAdd(array(
				'zayav_id' => $z['id'],
				'sum' => $acc_sum
			));

		_zayavStatusRemindAdd($zayav_id);

		_salaryZayavBonus($zayav_id);

		_note(array(
			'add' => 1,
			'comment' => 1,
			'p' => 45,
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
	case 'zayav_group_status_change':
		if(!$status_id = _num($_POST['status_id']))
			jsonError('������������ ID �������');

		$sql = "SELECT `zayav_id`
				FROM `_unit_check`
				WHERE `app_id`=".APP_ID."
				  AND `zayav_id`
				  AND `viewer_id_add`=".VIEWER_ID;
		if(!$zayav_ids = query_ids($sql))
			jsonError('������ �� �������');

		$sql = "SELECT *
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND `id` IN (".$zayav_ids.")
				  AND `status_id`!=".$status_id;
		if(!$zayav = query_arr($sql))
			jsonError('��������� ������ ��� �������� ��������� ������');

		$sql = "UPDATE `_zayav`
				SET `status_id`=".$status_id."
				WHERE `id` IN ("._idsGet($zayav).")";
		query($sql);

		foreach($zayav as $z)
			_history(array(
				'type_id' => 71,
				'client_id' => $z['client_id'],
				'zayav_id' => $z['id'],
				'v1' => $z['status_id'],
				'v2' => $status_id
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
			'v1' => '<table>'._historyChange('���������', _service('name', $z['service_id']), _service('name', $service_id)).'</table>'
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
	case 'zayav_tovar_avai_load':
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError();

		if(!$r = _tovarQuery($tovar_id))
			jsonError();

		$send['html'] = utf8(
			'<div class="fs18 mb5">'.$r['name'].'</div>'.
			_tovarAvaiSpisok($tovar_id, 'radio').
			'<button class="vk mt10 zta-but">��������� � ������</button>'.
			'<div class="_info">'.
				'<u>�����</u> ����� �������� � ������ �� ������ � ���������� <b>1</b> ��. � ��������� ���������� ���������.'.
			'</div>'
		);

		jsonSuccess($send);
		break;
	case 'zayav_tovar_set':// ��������� ������ �� ������
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
		//�������� �� ������ ��������, ����������� � ������
		$sql = "DELETE FROM `zp_zakaz`
				WHERE `app_id`=".APP_ID."
				  AND `zayav_id`=".$zayav_id."
				  AND `zp_id`=".$zp_id;
		query($sql);

		_note(array(
			'add' => 1,
			'comment' => 1,
			'p' => 45,
			'id' => $zayav_id,
			'txt' => '��������� ��������: ' .
				'<a class="zp-id" val="'.$zp_id.'">' .
				_zpName($zp['name_id']).' ' .
				_vendorName($zp['base_vendor_id'])._modelName($zp['base_model_id']) .
				'</a>'
		));


		//�������� �������� ������
		$move_id = _tovarMoveInsert(array(
			'type_id' => 2,
			'tovar_id' => $avai['tovar_id'],
			'tovar_avai_id' => $avai_id,
			'zayav_id' => $zayav_id
		));
*/
		//���������� �������� � ������� �� ������
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

	case 'zayav_srok_open'://�������� ��������� ������
		$send['html'] = utf8(_zayavSrokCalendar($_POST));
		jsonSuccess($send);
		break;
	case 'zayav_srok_save'://��������� ����� ����������
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
					'<th>����:'.
						'<td>'.($z['srok'] == '0000-00-00' ? '�� ������' : FullData($z['srok'], 0, 1, 1)).
						'<td>�'.
						'<td>'.FullData($day, 0, 1, 1).
					'</table>'
			));
		}

		jsonSuccess();
		break;

	case 'zayav_executer_change'://��������� ����������� ������
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();

		if(!$z = _zayavQuery($zayav_id))
			jsonError();

		$executer_id = _num($_POST['executer_id']);
		if($executer_id) {//���� id ������ ���������� ��� � ���������� - ������
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
						'<td>�'.
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
			jsonError('������: �� ��� ������ ��� �������� �������.');

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

		//�������� ���������� �� ��������
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

		//�������� ������� ��� �������
		_balans(array(
			'action_id' => 25,
			'client_id' => $v['client_id'],
			'zayav_id' => $v['zayav_id'],
			'dogovor_id' => $dog_id,
			'sum' => $v['sum']
		));

		//���������� ������ id �������� � ���������� ������
		$sql = "UPDATE `_zayav`
		        SET `dogovor_id`=".$dog_id."
		        WHERE `id`=".$v['zayav_id'];
		query($sql);

		// �������� ���������� �������, ���� ����
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
			jsonError('������: �������� �� ����������.');

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

		// ���������� ���������� �� ��������
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

		// ���������� ���������� �������
		$sql = "SELECT *
				FROM `_money_income`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `dogovor_id`=".$dog['id']."
				LIMIT 1";
		if($avans = query_assoc($sql)) {
			if(!$v['avans'] || !$v['invoice_id']) {//�������� �������
				$sql = "UPDATE `_money_income`
						SET `deleted`=1
						WHERE `id`=".$avans['id'];
				query($sql);
				//������ ��� ���������� �����
				_balans(array(
					'action_id' => 2,
					'invoice_id' => $avans['invoice_id'],
					'sum' => $avans['sum'],
					'income_id' => $avans['id']
				));
				//������ ��� �������
				_balans(array(
					'action_id' => 28,
					'client_id' => $v['client_id'],
					'zayav_id' => $v['zayav_id'],
					'dogovor_id' => $dog['id'],
					'sum' => $avans['sum']
				));
			} elseif($avans['sum'] != $v['avans'] || $avans['invoice_id'] != $v['invoice_id']) {//��������� �������
				$sql = "UPDATE `_money_income`
						SET `sum`=".$v['avans'].",
							`invoice_id`=".$v['invoice_id']."
						WHERE `id`=".$avans['id'];
				query($sql);

				if($avans['invoice_id'] != $v['invoice_id']) {
					//�������� ��� ����������� �����
					_balans(array(
						'action_id' => 2,
						'invoice_id' => $avans['invoice_id'],
						'sum' => $avans['sum'],
						'income_id' => $avans['id']
					));
					//�������� ��� ������ �����
					_balans(array(
						'action_id' => 1,
						'invoice_id' => $v['invoice_id'],
						'sum' => $v['avans'],
						'income_id' => $avans['id']
					));
				} else {
					//���������� ����� �������, ���� ���� �� �������
					_balans(array(
						'action_id' => 10,
						'invoice_id' => $avans['invoice_id'],
						'sum_old' => $avans['sum'],
						'sum' => $v['avans'],
						'income_id' => $avans['id']
					));
				}

				if($avans['sum'] != $v['avans']) {
					//������ ��� �������
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

		if(file_exists(PATH_DOGOVOR.'/'.$dog['link'].'.doc'))
			unlink(PATH_DOGOVOR.'/'.$dog['link'].'.doc');

		$changes =
			_historyChange('���', $dog['fio'], $v['fio']).
			_historyChange('�����', $dog['adres'], $v['adres']).
			_historyChange('������� �����', $dog['pasp_seria'], $v['pasp_seria']).
			_historyChange('������� �����', $dog['pasp_nomer'], $v['pasp_nomer']).
			_historyChange('������� ��������', $dog['pasp_adres'], $v['pasp_adres']).
			_historyChange('������� ��� �����', $dog['pasp_ovd'], $v['pasp_ovd']).
			_historyChange('������� ����� �����', $dog['pasp_data'], $v['pasp_data']).
			_historyChange('���� ����������', _dataDog($dog['data_create']), _dataDog($v['data_create'])).
			_historyChange('�����', $dog['nomer'], $v['nomer']).
			_historyChange('�����', _cena($dog['sum']), _cena($v['sum'])).
			_historyChange('��������� �����', _cena($dog['avans']), _cena($v['avans']));
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
	case 'dogovor_terminate'://����������� ��������
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
		//�������� ������� ��� �������
		_balans(array(
			'action_id' => 26,
			'client_id' => $z['client_id'],
			'zayav_id' => $z['id'],
			'dogovor_id' => $dogovor_id,
			'sum' => $dog['sum']
		));

		//�������� ���������� �������, ���� ����
		$sql = "SELECT *
				FROM `_money_income`
				WHERE !`deleted`
				  AND `dogovor_id`=".$dogovor_id."
				LIMIT 1";
		if($inc = query_assoc($sql)) {
			query("UPDATE `_money_income` SET `deleted`=1 WHERE `id`=".$inc['id']);
			//������ ��� ���������� �����
			_balans(array(
				'action_id' => 2,
				'invoice_id' => $inc['invoice_id'],
				'sum' => $inc['sum'],
				'income_id' => $inc['id']
			));
			//������ ��� �������
			if($inc['client_id'])
				_balans(array(
					'action_id' => 28,
					'client_id' => $inc['client_id'],
					'zayav_id' => $z['id'],
					'dogovor_id' => $dogovor_id,
					'sum' => $inc['sum']
				));
		}

		query("UPDATE `_zayav` SET `dogovor_id`=0 WHERE `id`=".$dog['zayav_id']);

		_clientBalansUpdate($z['client_id']);
		_zayavBalansUpdate($z['id']);
//		_salaryZayavBonus($z['id']);

		_history(array(
			'type_id' => 96,
			'client_id' => $z['client_id'],
			'zayav_id' => $z['id'],
			'dogovor_id' => $dogovor_id
		));

		jsonSuccess();
		break;

	case 'zayav_attach_cancel'://������� ������ ������������ ���������
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError('������������ ID ������');

		$v = _txt($_POST['v']);
		if($v != 'attach' && $v != 'attach1')
			jsonError('�������� �������� �����');

		if(!$reason = _txt($_POST['reason']))
			jsonError('�� ������� �������');

		if(!$z = _zayavQuery($zayav_id))
			jsonError('������ �� ����������');

		$sql = "UPDATE `_zayav`
				SET `".$v."_cancel`=1,
					`".$v."_cancel_reason`='".addslashes($reason)."',
					`".$v."_cancel_viewer_id`=".VIEWER_ID.",
					`".$v."_cancel_dtime`=CURRENT_TIMESTAMP
				WHERE `id`=".$zayav_id;
		query($sql);

		$zpu = _zayavPole($z['service_id']);

		_history(array(
			'type_id' => 103,
			'client_id' => $z['client_id'],
			'zayav_id' => $zayav_id,
			'v1' => str_replace("\n", ' ', $zpu[$v == 'attach' ? 22 : 34]['name']),
			'v2' => $reason
		));

		jsonSuccess();
		break;
	case 'zayav_attach_cancel_reason_load':
		$v = _txt($_POST['v']);
		if($v != 'attach' && $v != 'attach1')
			jsonError('�������� �������� �����');

		$sql = "SELECT
					`id`,
					`".$v."_cancel_reason`
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND LENGTH(`".$v."_cancel_reason`)
				GROUP BY `".$v."_cancel_reason`
				ORDER BY `".$v."_cancel_reason`";
		$send['spisok'] = query_selArray($sql);

		jsonSuccess($send);
		break;


	case 'zayav_expense_add'://�������� ������� �� ������
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError('������������ id ������');
		if(!$cat_id = _num($_POST['cat_id']))
			jsonError('�� ������� ���������');

		if(!$z = _zayavQuery($zayav_id))
			jsonError('������ id:'.$zayav_id.' �� ����������');

		$txt = '';
		$worker_id = 0;
		$workerOnPay = 0;//�������� ������� ���������� ��� ���������� ����� ������
		$tovar_id = 0;
		$tovar_avai_id = 0;
		$tovar_count = 0;
		$attach_id = 0;
		$mon = 0;
		$year = 0;
		$sum = _cena($_POST['sum']);
		
		$expense_dub = _num($_POST['expense_dub']);
		$expense_cat_id = _num($_POST['expense_cat_id']);
		$expense_cat_id_sub = _num($_POST['expense_cat_id_sub']);
		$invoice_id = _num($_POST['invoice_id']);
		$expense_id = 0;

		switch(_zayavExpense($cat_id, 'dop')) {
			case 1:
				$txt = _txt($_POST['dop']);
				if($expense_dub && !$invoice_id)
					jsonError('�� ������ ��������� ����');
				if($expense_dub && !$sum)
					jsonError('����� �� ����� ���� �������, ���� ������������ ������������ �������');
				break;
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
					jsonError('�� ������ �����');
				if(!$tovar_count = _ms($_POST['count']))
					jsonError('����������� ������� ����������');
				if(!$r = _tovarQuery($tovar_id))
					jsonError('������ id'.$tovar_id.' �� ����������');
				break;
			case 3:
				if(!$tovar_avai_id = _num($_POST['dop']))
					jsonError('�� ������� ������� ������');
				if(!$tovar_count = _ms($_POST['count']))
					jsonError('�� ������� ����������');
				$sql = "SELECT *
						FROM `_tovar_avai`
						WHERE `app_id`=".APP_ID."
						  AND `id`=".$tovar_avai_id;
				if(!$r = query_assoc($sql))
					jsonError('������� ������ id'.$tovar_avai_id.' �� ����������');
				if($tovar_count > $r['count'])
					jsonError('��������� ���������� ��������� ����������');
				$tovar_id = $r['tovar_id'];
				break;
			case 4: $attach_id = _num($_POST['dop']); break;
		}
		
		//�������� ������������ ������� �����������
		if($expense_dub) {
			$sql = "INSERT INTO `_money_expense` (
						`app_id`,
						`sum`,
						`about`,
						`invoice_id`,
						`category_id`,
						`category_sub_id`,
						`viewer_id_add`
					) VALUES (
						".APP_ID.",
						".$sum.",
						'".addslashes($txt)."',
						".$invoice_id.",
						".$expense_cat_id.",
						".$expense_cat_id_sub.",
						".VIEWER_ID."
					)";
			query($sql);
			$expense_id = query_insert_id('_money_expense');

			//������� ������� ��� ���������� �����
			_balans(array(
				'action_id' => 6,
				'invoice_id' => $invoice_id,
				'sum' => $sum,
				'expense_id' => $expense_id,
				'about' => $txt
			));
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
					`expense_id`,
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
					".$expense_id.",
					".$sum.",
					".$mon.",
					".$year.",
					".VIEWER_ID."
				)";
		query($sql);
		$insert_id = query_insert_id('_zayav_expense');

		_zayavBalansUpdate($zayav_id);

		//�������� ������� ������� ����������
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
						'<td>'._sumSpace($sum).' �.'.
				'</table>'
		));

		$send['html'] = utf8(_zayav_expense_spisok($zayav_id, $insert_id));
		jsonSuccess($send);
		break;
	case 'zayav_expense_del'://�������� ������� �� ������
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_zayav_expense`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		$r = query_assoc($sql);

		if($r['salary_list_id'] && !SA)
			jsonError('������ ���� � ����� ������ �/�');

		if($r['v1'] && !SA)
			jsonError('������ ������� ��� "���� �������"');

		$sql = "DELETE FROM `_zayav_expense` WHERE `id`=".$id;
		query($sql);

		_zayavBalansUpdate($r['zayav_id']);

		//�������� ������� ������� ����������
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
						'<td>'._sumSpace($r['sum']).' �.'.
				'</table>'
		));
		
		//�������� ����������������� ������� �����������
		if($r['expense_id']) {
			$sql = "SELECT *
					FROM `_money_expense`
					WHERE `app_id`=".APP_ID."
					  AND !`deleted`
					  AND `id`=".$r['expense_id'];
			if($me = query_assoc($sql)) {
				$sql = "UPDATE `_money_expense`
						SET `deleted`=1,
							`viewer_id_del`=".VIEWER_ID.",
							`dtime_del`=CURRENT_TIMESTAMP
						WHERE `id`=".$r['expense_id'];
				query($sql);

				_balans(array(
					'action_id' => 7,
					'invoice_id' => $me['invoice_id'],
					'sum' => $me['sum'],
					'expense_id' => $me['id'],
					'about' => $me['about']
				));
			}
		}

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
			jsonError('������������ id ������� �� ������');

		$sql = "SELECT *
				FROM `_zayav_expense`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('P������ �� ������ �� ����������');

		if(!$z = _zayavQuery($r['zayav_id']))
			jsonError('������ id:'.$r['zayav_id'].' �� ����������');

		if($r['v1'])
			jsonError('���� ��� ��� �������');

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
					'".addslashes($z['tovar_equip_ids'])."',

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

	case 'zayav_gn_polosa_nomer_change'://��������� ������ ������ �� ������ ������ (��� �������)
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

	case 'cartridge_new'://�������� ����� ������ ���������
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
	case 'zayav_cartridge_add'://���������� ���������� � ������
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();

		// ���� �� ������ �� ���� ��������
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
	case 'zayav_cartridge_edit'://���������� �������� �� ���������
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
			_historyChange('������', _cartridgeName($r['cartridge_id']), _cartridgeName($cartridge_id)) .
			_historyChange('���������', _cena($r['cost']), $cost) .
			_historyChange('����������', $r['prim'], $prim);
		if($r['filling'] != $filling || $r['restore'] != $restore || $r['chip'] != $chip) {
			$old = array();
			if($r['filling'])
				$old[] = '���������';
			if($r['restore'])
				$old[] = '������������';
			if($r['chip'])
				$old[] = '������� ���';
			$new = array();
			if($filling)
				$new[] = '���������';
			if($restore)
				$new[] = '������������';
			if($chip)
				$new[] = '������� ���';
			$changes .= _historyChange('��������', implode(', ', $old), implode(', ', $new));
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
	case 'zayav_cartridge_del'://�������� ��������� �� ������
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_zayav_cartridge`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('��������� ��� ��� �����');

		if($r['schet_id'])
			jsonError('�������� �������� � ����� �� ������');

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

	case 'zayav_report_cols_set'://�����������, ������� ������� ������ �� �������
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
	case 'zayav_report_spisok'://�����������, ������� ������� ������ �� �������
		$send['status'] = utf8(_zayav_report_status_count($_POST));
		$send['executer'] = utf8(_zayav_report_executer($_POST));
		$send['spisok'] = utf8(_zayav_report_spisok($_POST));
		jsonSuccess($send);
		break;

	case 'zayav_nomer_report':
		if($nomer = _num(@$_POST['nomer']))
			$year = _gn($nomer, 'year');
		elseif($year = _num(@$_POST['gnyear']))
			$nomer = _gn('first_year', $year);

		$send['nomer_spisok'] = _zayav_nomer_report_gn($year, 'arr');
		$send['nomer'] = $nomer;
		$send['spisok'] = utf8(_zayav_nomer_report($nomer));
		jsonSuccess($send);
		break;

	case 'zayav_stat_load'://���������� �� �������
		$service_id = _num($_POST['service_id']);
		if(!$year = _num($_POST['year']))
			$year = strftime('%Y');

		$year_compare = _num($_POST['year_compare']);

		$zayav_ids = -1;
		if($tovar_cat_id = _num($_POST['tovar_cat_id'])) {
			$sql = "SELECT DISTINCT `tovar_id`
					FROM `_tovar_bind`
					WHERE `app_id`=".APP_ID."
					  AND `category_id`=".$tovar_cat_id;
			if($tovar_ids = query_ids($sql)) {
				$sql = "SELECT DISTINCT `zayav_id`
						FROM `_zayav_tovar`
						WHERE `app_id`=".APP_ID."
						  AND `tovar_id` IN (".$tovar_ids.")";
				$zayav_ids = query_ids($sql);
			}
		}

		if($tovar_id = _num($_POST['tovar_id'])) {
			$sql = "SELECT DISTINCT `zayav_id`
					FROM `_zayav_tovar`
					WHERE `app_id`=".APP_ID."
					  AND `tovar_id`=".$tovar_id;
			$zayav_ids = query_ids($sql);
		}

		
		//������ �����
		$sql = "SELECT DATE_FORMAT(`dtime_add`,'%Y')
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `service_id`=".$service_id."
				ORDER BY `dtime_add`
				LIMIT 1";
		if(!$yearMin = query_value($sql))
			$yearMin = strftime('%Y');
		
		$filterYears = array();
		for($n = $yearMin; $n <= strftime('%Y'); $n++)
			$filterYears[$n] = $n;
		

		//��������� �������
		$sql = "SELECT
					`bind`.`category_id`,
					COUNT(`zt`.`zayav_id`) `c`
				FROM
					`_tovar_bind` `bind`,
					`_zayav_tovar` `zt`,
					`_zayav` `z`
				WHERE `bind`.`app_id`=".APP_ID."
				  AND `zt`.`app_id`=".APP_ID."
				  AND `zt`.`tovar_id`=`bind`.`tovar_id`
				  AND `zt`.`zayav_id`=`z`.`id`
				  AND !`z`.`deleted`
				  AND `z`.`service_id`=".$service_id."
				  AND `z`.`dtime_add` LIKE '".$year."-%'
				GROUP BY `bind`.`category_id`";
		$q = query($sql);
		$tovarCat = array();
		while($r = mysql_fetch_assoc($q))
			$tovarCat[] = array(
				'uid' => $r['category_id'],
				'title' => utf8(_tovarCategory($r['category_id'])),
				'content' => utf8(_tovarCategory($r['category_id']).' <div class="dib grey">('.$r['c'].')</div>')
			);


		if($year_compare && $year_compare != $year)
			$send['series'][] = array(
				'name' => utf8(_service('count') == 1 ? '������' : _service('name', $service_id).($year_compare ? ' '.$year_compare : '')),
				'color' => '#ccc',
				'data' => _zayavStatYear($service_id, $year_compare, $zayav_ids)
			);

		$send['series'][] = array(
			'name' => utf8(_service('count') == 1 ? '������' : _service('name', $service_id).($year_compare ? ' '.$year : '')),
			'color' => '#86C4FF',
			'data' => _zayavStatYear($service_id, $year, $zayav_ids)
		);


		$send['year'] = $year;
		$send['year_compare'] = $year_compare;
		$send['filterYears'] = _selArray($filterYears);
		$send['filterService'] = _service('js_arr');
		$send['filterTovarCat'] = $tovarCat;
		$send['html'] = utf8(
			'<table class="bs5 bor1 bg-gr1 w100p">'.
				'<tr'.(_service('count') == 1 ? ' class="dn"' : '').'>'.
					'<td class="label pb5 r">��� ������������:'.
					'<td><input type="hidden" id="filter-service" value="'.$service_id.'" />'.
				'<tr><td class="label pb5 r w125">���:'.
					'<td><input type="hidden" id="filter-year" value="'.$year.'" />'.
				'<tr><td class="label pb5 r w125">�������� � �����:'.
					'<td><input type="hidden" id="filter-year-compare" value="'.$year_compare.'" />'.
				'<tr'.(empty($tovarCat) ? ' class="dn"' : '').'>'.
					'<td class="label r">��������� �������:'.
					'<td><input type="hidden" id="filter-tovar-cat" value="'.$tovar_cat_id.'" />'.
				'<tr'.(empty($tovarCat) ? ' class="dn"' : '').'>'.
					'<td class="label r topi">���������� �����:'.
					'<td><div class="w300">'.
							'<input type="hidden" id="filter-tovar-id" value="'.$tovar_id.'" />'.
						'</div>'.
			'</table>'.
			'<div id="zayav-stat" class="mt20"></div>'
		);

		jsonSuccess($send);
		break;
}

function _zayavValuesCheck($service_id, $zayav_id=0) {//�������� ������������ ����� ��� ��������/�������������� ������
	$zpu = _zayavPole($service_id); //Zayav Pole Use

	$v = array();
	$upd = array();   // ��� �������������� ������

	if(empty($zpu) && !$zayav_id) //���� ��� ������ ��� �������� ����� ������, �� �����
		return $v;


	$v['name'] = _txt(@$_POST['name']);
	if($u = @$zpu[1]) {
		if($u['require'] && !$v['name'])
			jsonError('�� ��������� ���� '.$u['name']);
	}

	$v['about'] = _txt(@$_POST['about']);
	if($u = @$zpu[2]) {
		if($u['require'] && !$v['about'])
			jsonError('�� ��������� ���� '.$u['name']);
		$upd[] = "`about`='".addslashes($v['about'])."'";
	}

	$v['count'] = _num(@$_POST['count']);
	if($u = @$zpu[3]) {
		if($u['require'] && !$v['count'])
			jsonError('����������� ��������� ���� '.$u['name']);
		$upd[] = "`count`='".addslashes($v['count'])."'";
	}

	if(@$zpu[4]['require'] && empty($_POST['tovar']))
		jsonError('�� ������� ���� '.$zpu[4]['name']);

	$v['client_id'] = _num(@$_POST['client_id']);
	if($u = @$zpu[5]) {
		if($u['require'] && !$v['client_id'])
			jsonError('�� ������ '.$u['name']);
		$upd[] = "`client_id`=".$v['client_id'];
	}

	$v['adres'] = _txt(@$_POST['adres']);
	if($u = @$zpu[6]) {
		if($u['require'] && !$v['adres'])
			jsonError('�� ��������� ���� '.$u['name']);
		$upd[] = "`adres`='".addslashes($v['adres'])."'";
	}

	$v['imei'] = _txt(@$_POST['imei']);
	if($u = @$zpu[7]) {
		if($u['require'] && !$v['imei'])
			jsonError('�� ��������� ���� '.$u['name']);
		$upd[] = "`imei`='".addslashes($v['imei'])."'";
	}

	$v['serial'] = _txt(@$_POST['serial']);
	if($u = @$zpu[8]) {
		if($u['require'] && !$v['serial'])
			jsonError('�� ��������� ���� '.$u['name']);
		$upd[] = "`serial`='".addslashes($v['serial'])."'";
	}

	$v['color_id'] = _num(@$_POST['color_id']);
	$v['color_dop'] = _num(@$_POST['color_dop']);
	if($u = @$zpu[9]) {
		if($u['require'] && !$v['color_id'])
			jsonError('�� ������ '.$u['name']);
		$upd[] = "`color_id`=".$v['color_id'];
		$upd[] = "`color_dop`=".$v['color_dop'];
	}

	$v['executer_id'] = _num(@$_POST['executer_id']);
	if(!$zayav_id && @$zpu[10]['require'] && !$v['executer_id'])
		jsonError('�� �������� '.$zpu[10]['name']);

	if(@$zpu[11]['require'] && empty($_POST['tovar']))
		jsonError('�� ������� ���� '.$zpu[11]['name']);

	$v['place_id'] = _num(@$_POST['place_id']);
	$v['place_other'] = !$v['place_id'] ? _txt(@$_POST['place_other']) : '';
	if(!$zayav_id && @$zpu[12]['require'] && !$v['place_id'] && !$v['place_other'])
		jsonError('�� ������� ���� '.$zpu[12]['name']);

	$v['srok'] = empty($_POST['srok']) ? '0000-00-00' : $_POST['srok'];
	if(!$zayav_id && @$zpu[13]['require'] && $v['srok'] == '0000-00-00')
		jsonError('�� ������ ���� ���������� ������');

    $v['note'] = _txt(@$_POST['note']);
	if(!$zayav_id && @$zpu[14]['require'] && !$v['note'])
		jsonError('�� ��������� ���� '.$zpu[14]['name']);

	$v['sum_cost'] = _cena(@$_POST['sum_cost']);
	$v['sum_manual'] = _bool(@$_POST['sum_manual']);
	if($u = @$zpu[15]) {
		if($u['require'] && !$v['sum_cost'])
			jsonError('����������� ��������� ���� '.$u['name']);
		$upd[] = "`sum_manual`=".$v['sum_manual'];
		$upd[] = "`sum_cost`=".$v['sum_cost'];
	}

	$v['pay_type'] = _num(@$_POST['pay_type']);
	if($u = @$zpu[16]) {
		if($u['require'] && !$v['pay_type'])
			jsonError('�� ������ ��� �������');
		$upd[] = "`pay_type`=".$v['pay_type'];
	}

	$v['tovar_equip_ids'] = _ids(@$_POST['tovar_equip_ids']);
	if(@$zpu[4] && $zpu[4]['v1'])
		$upd[] = "`tovar_equip_ids`='".$v['tovar_equip_ids']."'";

	$v['size_x'] = _cena(@$_POST['size_x']);
	$v['size_y'] = _cena(@$_POST['size_y']);
	if($u = @$zpu[31]) {
		if($u['require'] && (!$v['size_x'] || !$v['size_y']))
			jsonError('����������� ��������� ���� '.$u['name']);
		$upd[] = "`size_x`=".$v['size_x'];
		$upd[] = "`size_y`=".$v['size_y'];
	}

	$v['phone'] = _txt(@$_POST['phone']);
	if($u = @$zpu[37]) {
		if($u['require'] && !$v['phone'])
			jsonError('�� ��������� ���� '.$u['name']);
		$upd[] = "`phone`='".addslashes($v['phone'])."'";
	}

	$v['gn'] = _txt(@$_POST['gn']);
	if($u = @$zpu[38]) {
		if(!empty($v['gn'])){
			$gn = array();
			foreach(explode('###', $v['gn']) as $r) {
				$ex = explode(':', $r);
				if(!$gn_id = _num($ex[0]))
					jsonError('������������ id ������ ������');
				if($u['v2'] && !_num($ex[1]))
					jsonError('������ ������� �� �� ���� ��������� �������');
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
			jsonError('�� ������� ������');
		$upd[] = "`skidka`=".$v['skidka'];
	}

	$v['rubric_id'] = _num(@$_POST['rubric_id']);
	$v['rubric_id_sub'] = _num(@$_POST['rubric_id_sub']);
	if($u = @$zpu[40]) {
		if($u['require'] && !$v['rubric_id'])
			jsonError('�� ������� '.$u['name']);
		$upd[] = "`rubric_id`=".$v['rubric_id'];
		$upd[] = "`rubric_id_sub`=".$v['rubric_id_sub'];
	}

	$v['update'] = implode(',', $upd);
	$v['zpu'] = $zpu;
	return $v;
}
function _zayavNameUpdate($zayav_id, $v) {//���������� �������� ������ � ������ ��� ������
	$z = _zayavQuery($zayav_id);

	$zpu = $v['zpu'];

	$name = $z['name'];

	//�������� ������� �������
	if(isset($zpu[1]))
		$name = $v['name'];

	if(!$name || $name && !isset($zpu[1]))
		if(isset($zpu[4]) || isset($zpu[11]))
			if(@$_POST['tovar']) {
				$ex = explode(',', $_POST['tovar']);
				$ex = explode(':', $ex[0]);
				$tovar_id = $ex[0];

				$sql = "SELECT `name`
						FROM `_tovar`
						WHERE `id`=".$tovar_id;
				$name = query_value($sql);
			}

	if(!$name && isset($zpu[23]))
		$name = '���������';

	if(!$name && $z['service_id'])
		$name = _service('name', $z['service_id']).' '.$z['nomer'];

	if(!$name)
		$name = '������ #'.$z['nomer'];

	$find = $name;

	$sql = "UPDATE `_zayav`
			SET `name`='".addslashes($name)."',
				`find`='".addslashes($find)."'
			WHERE `id`=".$zayav_id;
	query($sql);

	return $name;
}
function _zayavDogovorAvansInsert($v) {//�������� ���������� ������� ��� ����������/��������� ��������
	if(!$v['avans'])
		return;

	$sql = "INSERT INTO `_money_income` (
			`app_id`,
			`invoice_id`,
			`confirm`,
			`sum`,
			`client_id`,
			`zayav_id`,
			`dogovor_id`,
			`viewer_id_add`
		) VALUES (
			".APP_ID.",
			".$v['invoice_id'].",
			".$v['confirm'].",
			".$v['avans'].",
			".$v['client_id'].",
			".$v['zayav_id'].",
			".$v['id'].",
			".VIEWER_ID."
		)";
	query($sql);

	$income_id = query_insert_id('_money_income');

	//������ ��� ���������� �����
	_balans(array(
		'action_id' => 1,
		'invoice_id' => $v['invoice_id'],
		'sum' => $v['avans'],
		'income_id' => $income_id
	));

	//������ ��� �������
	_balans(array(
		'action_id' => 27,
		'client_id' => $v['client_id'],
		'zayav_id' => $v['zayav_id'],
		'dogovor_id' => $v['id'],
		'sum' => $v['avans'],
		'about' => '��������� �����.'
	));

	_history(array(
		'type_id' => 20,
		'client_id' => $v['client_id'],
		'zayav_id' => $v['zayav_id'],
		'dogovor_id' => $v['id']
	));
}
function _zayavStatusRemindAdd($zayav_id) {//�������� ����������� ��� ��������� ������� ������
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
function _zayavTovarUpdate($zayav_id) {//���������� ������ ������� ������
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
function _zayavGazetaNomerUpdate($zayav_id, $v) {//���������� ������� �����
	if(empty($v['zpu'][38]))
		return;

	$sql = "DELETE FROM `_zayav_gazeta_nomer`
			WHERE `zayav_id`=".$zayav_id."
			  AND !`schet_id`
			  AND `gazeta_nomer_id`>="._gn('first');
	query($sql);

	if(empty($v['gn']))
		return;

	$z = _zayavQuery($zayav_id);

	$insert = array();
	foreach($v['gn'] as $r) {
		$skidka_sum = $r['skidka'] ? round($r['cena'] / (100 - $r['skidka']) * 100 - $r['cena'], 6) : 0;
		$insert[] = '('.
			APP_ID.','.
			$z['client_id'].','.
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
				`client_id`,
				`zayav_id`,
				`gazeta_nomer_id`,
				`dop`,
				`polosa`,
				`cena`,
				`skidka`,
				`skidka_sum`
			) VALUES ".implode(',', $insert);
	query($sql);
}

function _zayavStatYear($service_id, $year, $zayav_ids) {//���������� ������ �� ��������� ���
	//���������� ������ �� ������� ������ �� ��������� ���
	$sql = "SELECT
				DATE_FORMAT(`dtime_add`,'%c') AS `mon`,
				COUNT(*)
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `service_id`=".$service_id."
			  AND `dtime_add` LIKE '".$year."-%'
			  ".($zayav_ids != -1 ? " AND `id` IN (".$zayav_ids.")" : '')."
			GROUP BY `mon`
			ORDER BY `dtime_add`";

	$spisok = query_ass($sql);

	$data = array();
	for($n = 1; $n <= 12; $n++)
		$data[] = isset($spisok[$n]) ? _num($spisok[$n]) : 0;

	return $data;
}