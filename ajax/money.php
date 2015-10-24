<?php
switch(@$_POST['op']) {
	case 'accrual_add':
		if(!$sum = _cena($_POST['sum']))
			jsonError();

		$zayav_id = _num($_POST['zayav_id']);
		$sum = intval($_POST['sum']);
		$about = _txt($_POST['about']);
		$client_id = 0;

		if($zayav_id) {
			$sql = "SELECT *
					FROM `zayav`
					WHERE `ws_id`=".WS_ID."
					  AND !`deleted`
					  AND `id`=".$zayav_id;
			if(!$z = query_assoc($sql))
				jsonError();

			$client_id = $z['client_id'];
		}

		$sql = "INSERT INTO `_money_accrual` (
					`app_id`,
					`ws_id`,
					`zayav_id`,
					`client_id`,
					`sum`,
					`about`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".WS_ID.",
					".$zayav_id.",
					".$client_id.",
					".$sum.",
					'".addslashes($about)."',
					".VIEWER_ID."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

/*
		clientBalansUpdate($z['client_id']);
		zayavBalansUpdate($zayav_id);

		history_insert(array(
			'type' => 5,
			'client_id' => $z['client_id'],
			'zayav_id' => $zayav_id,
			'value' => $sum
		));

		//���������� ������� ������, ���� ���������
		if($z['zayav_status'] != $status) {
			$sql = "UPDATE `zayav`
					SET `zayav_status`=".$status.",`zayav_status_dtime`=CURRENT_TIMESTAMP
					WHERE `id`=".$zayav_id;
			query($sql);
			history_insert(array(
				'type' => 4,
				'client_id' => $z['client_id'],
				'zayav_id' => $zayav_id,
				'value' => $status,
				'value1' => $z['zayav_status']
			));
			$send['status'] = _zayavStatus($status);
			$send['status']['name'] = utf8($send['status']['name']);
			$send['status']['dtime'] = utf8(FullDataTime(curTime()));
		}

		//�������� �����������, ���� ����
		if($remind) {
			_remind_add(array(
				'zayav_id' => $zayav_id,
				'txt' => $remind_txt,
				'day' => $remind_day
			));
			$send['remind'] = utf8(_remind_spisok(array('zayav_id'=>$zayav_id), 'spisok'));
		}

		$send['html'] = utf8(zayav_info_money($zayav_id));
*/

		jsonSuccess();
		break;
	case 'accrual_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT * FROM `accrual` WHERE !`deleted` AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "SELECT * FROM `zayav` WHERE `ws_id`=".WS_ID." AND !`deleted` AND `id`=".$r['zayav_id'];
		if(!$z = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `accrual` SET
					`deleted`=1,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;
		query($sql);

		clientBalansUpdate($r['client_id']);
		zayavBalansUpdate($r['zayav_id']);

		history_insert(array(
			'type' => 8,
			'client_id' => $r['client_id'],
			'zayav_id' => $r['zayav_id'],
			'value' => $r['sum'],
			'value1' => $r['prim']
		));
		jsonSuccess();
		break;
	case 'accrual_rest':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT
		            *,
					'acc' AS `type`
				FROM `accrual`
				WHERE `ws_id`=".WS_ID."
				  AND `deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "SELECT * FROM `zayav` WHERE `ws_id`=".WS_ID." AND !`deleted` AND `id`=".$r['zayav_id'];
		if(!$z = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `accrual` SET
					`deleted`=0,
					`viewer_id_del`=0,
					`dtime_del`='0000-00-00 00:00:00'
				WHERE `id`=".$id;
		query($sql);

		clientBalansUpdate($r['client_id']);
		zayavBalansUpdate($r['zayav_id']);

		history_insert(array(
			'type' => 27,
			'client_id' => $r['client_id'],
			'zayav_id' => $r['zayav_id'],
			'value' => $r['sum'],
			'value1' => $r['prim']
		));
		$send['html'] = utf8(zayav_accrual_unit($r));
		jsonSuccess($send);
		break;

	case 'income_spisok'://������ ��������
		$data = income_spisok($_POST);
		$send['html'] = utf8($data['spisok']);
		if($data['filter']['page'] == 1)
			$send['path'] = utf8(income_path($data['filter']['period']));
		jsonSuccess($send);
		break;
	case 'income_add':
		if(!$invoice_id = $_POST['invoice_id'])
			jsonError();
		if(!$sum = _cena($_POST['sum']))
			jsonError();

		$about = _txt($_POST['about']);

		if(!$about)
			jsonError();

		$sql = "INSERT INTO `_money_income` (
				`app_id`,
				`ws_id`,
				`invoice_id`,
				`sum`,
				`about`,
				`viewer_id_add`
			) VALUES (
				".APP_ID.",
				".WS_ID.",
				".$invoice_id.",
				".$sum.",
				'".addslashes($about)."',
				".VIEWER_ID."
			)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		jsonSuccess();
		break;
/*
	case 'income_add':
		if(!preg_match(REGEXP_NUMERIC, $_POST['zayav_id']))
			jsonError();
		if(!preg_match(REGEXP_NUMERIC, $_POST['invoice_id']) || !$_POST['invoice_id'])
			jsonError();
		if(!preg_match(REGEXP_CENA, $_POST['sum']))
			jsonError();
		if(!preg_match(REGEXP_BOOL, $_POST['prepay']))
			jsonError();
		if(!preg_match(REGEXP_NUMERIC, $_POST['place']))
			jsonError();

		$place = intval($_POST['place']);
		$place_other = !$place ? win1251(htmlspecialchars(trim($_POST['place_other']))) : '';
		$remind_active = _bool($_POST['remind_active']);

		if(!$_POST['zayav_id'] && empty($_POST['prim']))
			jsonError();

		if(!$v = income_insert($_POST))
			jsonError();

		$send = array();
		if($v['zayav_id']) {
			_zayavPlaceCheck($v['zayav_id'], $place, $place_other);
			$send['html'] = utf8(zayav_info_money($v['zayav_id']));
			$send['comment'] = utf8(_vkComment('zayav', $v['zayav_id']));
			if($remind_active) {
				_remind_active_to_ready_in_zayav($v['zayav_id']);
				$send['remind'] = utf8(_remind_spisok(array('zayav_id'=>$v['zayav_id']), 'spisok'));
			}
		}

		jsonSuccess($send);
		break;
*/
	case 'income_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_money_income`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		if(TODAY != substr($r['dtime_add'], 0, 10))
			jsonError();

		$sql = "UPDATE `_money_income`
				SET `deleted`=1,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);
/*
		invoice_history_insert(array(
			'action' => 2,
			'table' => 'money',
			'id' => $id
		));
		clientBalansUpdate($r['client_id']);
		zayavBalansUpdate($r['zayav_id']);
*/
		_history(array(
			'type_id' => 9,
			'client_id' => $r['client_id'],
			'zayav_id' => $r['zayav_id'],
			'zp_id' => $r['zp_id'],
			'v1' => round($r['sum'], 2),
			'v2' => $r['about'],
			'v3' => _invoice($r['invoice_id'])
		));
		jsonSuccess();
		break;

	case 'expense_spisok':
		$data = expense_spisok($_POST);
		$send['html'] = utf8($data['spisok']);
//		$send['mon'] = utf8(expenseMonthSum($_POST));
		jsonSuccess($send);
		break;
	case 'expense_add':
		if(!$invoice_id = _num($_POST['invoice_id']))
			jsonError();
		if(!$sum = _cena($_POST['sum']))
			jsonError();

		$expense_id = _num($_POST['expense_id']);
		$prim = win1251(htmlspecialchars(trim($_POST['prim'])));
		if(!$expense_id && empty($prim))
			jsonError();

		$worker_id = _num($_POST['worker_id']);
		$mon = _num($_POST['mon']);
		$year = _num($_POST['year']);
		if($expense_id == 1 && (!$worker_id || !$year || !$mon))
			jsonError();

		$sql = "INSERT INTO `money` (
					`ws_id`,
					`sum`,
					`prim`,
					`invoice_id`,
					`expense_id`,
					`worker_id`,
					`year`,
					`mon`,
					`viewer_id_add`
				) VALUES (
					".WS_ID.",
					-".$sum.",
					'".addslashes($prim)."',
					".$invoice_id.",
					".$expense_id.",
					".$worker_id.",
					".$year.",
					".$mon.",
					".VIEWER_ID."
				)";
		query($sql);

		invoice_history_insert(array(
			'action' => 6,
			'table' => 'money',
			'id' => mysql_insert_id()
		));

		history_insert(array(
			'type' => 21,
			'value' => abs($sum),
			'value1' => $prim,
			'value2' => $expense_id ? $expense_id : '',
			'value3' => $worker_id ? $worker_id : ''
		));
		jsonSuccess();
		break;
	case 'expense_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_money_expense`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		if(TODAY != substr($r['dtime_add'], 0, 10))
			jsonError();

		$sql = "UPDATE `_money_expense` SET
					`deleted`=1,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);
/*
		invoice_history_insert(array(
			'action' => 7,
			'table' => 'money',
			'id' => $id
		));
*/
		_history(array(
			'type_id' => 22,
			'invoice_id' => $r['invoice_id'],
			'worker_id' => $r['worker_id'],
			'v1' => round($r['sum'], 2),
			'v2' => $r['about']
		));
		jsonSuccess();
		break;
}
