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

		//внесение баланса дл€ клиента
		_balans(array(
			'category_id' => 2,
			'action_id' => 19,
			'client_id' => $client_id,
			'sum' => $sum
		));

		_history(array(
			'type_id' => 74,
			'client_id' => $client_id,
			'zayav_id' => $zayav_id,
			'v1' => $sum
		));

/*
		zayavBalansUpdate($zayav_id);



		//ќбновление статуса за€вки, если измен€лс€
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

		//¬несение напоминани€, если есть
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

	case 'income_spisok'://список платежей
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

		$insert_id = query_insert_id('_money_income', GLOBAL_MYSQL_CONNECT);

		_balans(array(
			'category_id' => 1,
			'action_id' => 1,
			'invoice_id' => $invoice_id,
			'sum' => $sum,
			'income_id' => $insert_id
		));

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
		clientBalansUpdate($r['client_id']);
		zayavBalansUpdate($r['zayav_id']);
*/

		_balans(array(
			'category_id' => 1,
			'action_id' => 2,
			'invoice_id' => $r['invoice_id'],
			'sum' => $r['sum'],
			'income_id' => $r['id']
		));

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
		$send['mon'] = utf8(expenseMonthSum($_POST));
		jsonSuccess($send);
		break;
	case 'expense_add':
		if(!$invoice_id = _num($_POST['invoice_id']))
			jsonError();
		if(!$sum = _cena($_POST['sum']))
			jsonError();

		//об€зательно должна быть указана категори€, либо описание расхода
		$category_id = _num($_POST['category_id']);
		$about = _txt($_POST['about']);
		if(!$category_id && empty($about))
			jsonError();

		//
		$worker_id = _num($_POST['worker_id']);
		$mon = _num($_POST['mon']);
		$year = _num($_POST['year']);
		if($category_id == 1 && (!$worker_id || !$year || !$mon))
			jsonError();

		$sql = "INSERT INTO `_money_expense` (
					`app_id`,
					`ws_id`,
					`sum`,
					`about`,
					`invoice_id`,
					`category_id`,
					`worker_id`,
					`year`,
					`mon`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".WS_ID.",
					".$sum.",
					'".addslashes($about)."',
					".$invoice_id.",
					".$category_id.",
					".$worker_id.",
					".$year.",
					".$mon.",
					".VIEWER_ID."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$insert_id = query_insert_id('_money_expense', GLOBAL_MYSQL_CONNECT);

		//истори€ баланса дл€ расчЄтного счЄта
		_balans(array(
			'category_id' => 1,
			'action_id' => 6,
			'invoice_id' => $invoice_id,
			'sum' => $sum,
			'expense_id' => $insert_id,
			'about' => $about
		));

		//истори€ баланса дл€ сотрудника
		if($worker_id)
			_balans(array(
				'category_id' => 5,
				'action_id' => 23,
				'worker_id' => $worker_id,
				'sum' => $sum,
				'expense_id' => $insert_id,
				'about' => $about
			));

		_history(array(
			'type_id' => $worker_id ? 37 : 21,
			'invoice_id' => $invoice_id,
			'worker_id' => $worker_id,
			'v1' => $sum,
			'v2' => $about,
			'v3' => $category_id
		));

		jsonSuccess();
		break;
	case 'expense_edit':
		if(!$id = _num($_POST['id']))
			jsonError();
		if(!$sum = _cena($_POST['sum']))
			jsonError();

		//об€зательно должна быть указана категори€, либо описание расхода
		$category_id = _num($_POST['category_id']);
		$about = _txt($_POST['about']);
		if(!$category_id && empty($about))
			jsonError();

		$worker_id = _num($_POST['worker_id']);
		$mon = _num($_POST['mon']);
		$year = _num($_POST['year']);
		if($category_id == 1 && (!$worker_id || !$year || !$mon))
			jsonError();

		$sql = "SELECT *
				FROM `_money_expense`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_money_expense`
				SET `about`='".addslashes($about)."',
					`category_id`=".$category_id.",
					`worker_id`=".$worker_id.",
					`year`=".$year.",
					`mon`=".$mon."
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		$mon_old = $r['mon'] ? _monthDef($r['mon']).' '.$r['year'] : '';
		$mon_new = $mon ? _monthDef($mon).' '.$year : '';

		$changes =
			_historyChange(' атегори€', $r['category_id'] ? _expense($r['category_id']) : '', $category_id ? _expense($category_id) : '').
			_historyChange('ќписание', $r['about'], $about).
			_historyChange('—отрудник', $r['worker_id'] ? _viewer($r['worker_id'], 'viewer_name') : '', $worker_id ? _viewer($worker_id, 'viewer_name') : '').
			_historyChange('ћес€ц', $mon_old, $mon_new);

		if($changes)
			_history(array(
				'type_id' => 23,
				'invoice_id' => $r['invoice_id'],
				'worker_id' => $worker_id,
				'v1' => $sum,
				'v2' => '<table>'.$changes.'</table>',
				'v3' => _invoice($r['invoice_id'])
			));

		jsonSuccess();
		break;
	case 'expense_load':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT
					`category_id`,
					`invoice_id`,
					`worker_id`,
					`sum`,
					`about`,
					`year`,
					`mon`
				FROM `_money_expense`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$r['sum'] = round($r['sum'], 2);
		$r['about'] = utf8($r['about']);
		$send['arr'] = $r;

		jsonSuccess($send);
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

		_balans(array(
			'category_id' => 1,
			'action_id' => 7,
			'invoice_id' => $r['invoice_id'],
			'sum' => $r['sum'],
			'expense_id' => $r['id'],
			'about' => $r['about']
		));

		//истори€ баланса дл€ сотрудника
		if($r['worker_id'])
			_balans(array(
				'category_id' => 5,
				'action_id' => 24,
				'invoice_id' => $r['invoice_id'],
				'sum' => $r['sum'],
				'expense_id' => $r['id'],
				'about' => $r['about']
			));

		_history(array(
			'type_id' => $r['worker_id'] ? 64 : 22,
			'invoice_id' => $r['invoice_id'],
			'worker_id' => $r['worker_id'],
			'v1' => round($r['sum'], 2),
			'v2' => $r['about'],
			'v3' => $r['category_id']
		));

		jsonSuccess();
		break;

	case 'invoice_set':
		if(!RULE_SETUP_INVOICE)
			jsonError();
		if(!$invoice_id = _num($_POST['invoice_id']))
			jsonError();
		if(!preg_match(REGEXP_CENA, $_POST['sum']))
			jsonError();

		$sum = _cena($_POST['sum']);

		$sql = "SELECT *
				FROM `_money_invoice`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$invoice_id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_money_invoice`
				SET `start`="._invoiceBalans($invoice_id, $sum)."
				WHERE `id`=".$invoice_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'invoice'.WS_ID);

		_balans(array(
			'category_id' => 1,
			'action_id' => 5,
			'invoice_id' => $invoice_id,
			'sum' => $sum
		));

		_history(array(
			'type_id' => 28,
			'invoice_id' => $invoice_id,
			'v1' => $sum
		));

		$send['html'] = utf8(invoice_spisok());
		jsonSuccess($send);
		break;
	case 'invoice_reset':
		if(!VIEWER_ADMIN)
			jsonError();
		if(!$invoice_id = _num($_POST['invoice_id']))
			jsonError();

		$sql = "SELECT *
				FROM `_money_invoice`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$invoice_id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		if($r['start'] == -1)
			jsonError();

		$sql = "UPDATE `_money_invoice`
				SET `start`=-1
				WHERE `id`=".$invoice_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'invoice'.WS_ID);

		_history(array(
			'type_id' => 53,
			'invoice_id' => $invoice_id
		));

		$send['html'] = utf8(invoice_spisok());
		jsonSuccess($send);
		break;
	case 'invoice_transfer_spisok':
		$send['html'] = utf8(invoice_transfer_spisok($_POST));
		jsonSuccess($send);
		break;
	case 'invoice_transfer_add':
		if(!$from = _num($_POST['from']))
			jsonError();
		if(!$to = _num($_POST['to']))
			jsonError();
		if(!$sum = _cena($_POST['sum']))
			jsonError();

		$about = _txt($_POST['about']);

		if($from == $to)
			jsonError();

		$sql = "INSERT INTO `_money_invoice_transfer` (
					`app_id`,
					`ws_id`,
					`invoice_id_from`,
					`invoice_id_to`,
					`sum`,
					`about`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".WS_ID.",
					".$from.",
					".$to.",
					".$sum.",
					'".addslashes($about)."',
					".VIEWER_ID."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$insert_id = query_insert_id('_money_invoice_transfer', GLOBAL_MYSQL_CONNECT);

		_balans(array(
			'category_id' => 1,
			'action_id' => 4,
			'invoice_id' => $from,
			'sum' => $sum,
			'invoice_transfer_id' => $insert_id
		));

		_balans(array(
			'category_id' => 1,
			'action_id' => 4,
			'invoice_id' => $to,
			'sum' => $sum,
			'invoice_transfer_id' => $insert_id
		));

		_history(array(
			'type_id' => 39,
			'v1' => _sumSpace($sum),
			'v2' => _invoice($from),
			'v3' => _invoice($to),
			'v4' => $about
		));

		$send['i'] = utf8(invoice_spisok());
		$send['t'] = utf8(invoice_transfer_spisok());
		jsonSuccess($send);
		break;
	case 'invoice_transfer_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_money_invoice_transfer`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_money_invoice_transfer` SET `deleted`=1 WHERE `id`=".$id;

		query($sql, GLOBAL_MYSQL_CONNECT);

		_balans(array(
			'category_id' => 1,
			'action_id' => 12,
			'invoice_id' => $r['invoice_id_from'],
			'sum' => $r['sum'],
			'invoice_transfer_id' => $r['id']
		));

		_balans(array(
			'category_id' => 1,
			'action_id' => 12,
			'invoice_id' => $r['invoice_id_to'],
			'sum' => $r['sum'],
			'invoice_transfer_id' => $r['id']
		));

		_history(array(
			'type_id' => 40,
			'v1' => _sumSpace($r['sum']),
			'v2' => _invoice($r['invoice_id_from']),
			'v3' => _invoice($r['invoice_id_to']),
			'v4' => $r['about']
		));

		$send['i'] = utf8(invoice_spisok());
		$send['t'] = utf8(invoice_transfer_spisok());
		jsonSuccess($send);
		break;

	case 'balans_show':
		$send['html'] = utf8(balans_show($_POST));
		jsonSuccess($send);
		break;
	case 'balans_spisok':
		$data = balans_show_spisok($_POST);
		$send['html'] = utf8($data['spisok']);
		jsonSuccess($send);
		break;

	case 'zayav_expense_edit'://изменение расходов по за€вке
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();
		$expense = _txt($_POST['expense']);
		if(!_zayav_expense_test($expense))
			jsonError();

		$sql = "SELECT *
				FROM `zayav`
				WHERE `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$zayav_id;
		if(!$z = query_assoc($sql))
			jsonError();

		$sql = "SELECT *
				FROM `_zayav_expense`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `zayav_id`=".$zayav_id."
				ORDER BY `id`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		$arr = array();
		while($r = mysql_fetch_assoc($q)) {
			$dop = '';
			$ze = _zayavExpense($r['category_id'], 'all');
			if($ze['txt'])
				$dop = $r['txt'];
			if($ze['worker'])
				$dop = $r['worker_id'];
			if($ze['zp'])
				$dop = $r['zp_id'];
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

			//получение старой html-таблицы дл€ истории дейсвтий
			$sql = "SELECT *
					FROM `_zayav_expense`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND `zayav_id`=".$zayav_id."
					ORDER BY `id`";
			$arrOld = query_arr($sql, GLOBAL_MYSQL_CONNECT);

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
							".$r[3].",
							".intval(strftime('%m')).",
							".strftime('%Y').",
							".VIEWER_ID."
						) ON DUPLICATE KEY UPDATE
							`category_id`=VALUES(`category_id`),
							`txt`=VALUES(`txt`),
							`worker_id`=VALUES(`worker_id`),
							`zp_id`=VALUES(`zp_id`),
							`sum`=VALUES(`sum`)";
				query($sql, GLOBAL_MYSQL_CONNECT);

				unset($toDelete[$r[0]]);
			}

			//удаление расходов, которые были удалены
			if(!empty($toDelete)) {
				$sql = "DELETE FROM `_zayav_expense` WHERE `id` IN (".implode(',', array_keys($toDelete)).")";
				query($sql, GLOBAL_MYSQL_CONNECT);
			}

		//	_zayavBalansUpdate($zayav_id);

			//получение новой html-таблицы дл€ истории дейсвтий
			$sql = "SELECT *
					FROM `_zayav_expense`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND `zayav_id`=".$zayav_id."
					ORDER BY `id`";
			$arrNew = query_arr($sql, GLOBAL_MYSQL_CONNECT);

			$old = _zayav_expense_html($arrOld, false, $arrNew);
			$new = _zayav_expense_html($arrNew, false, $arrOld, true);

			_history(array(
				'type_id' => 30,
				'client_id' => $z['client_id'],
				'zayav_id' => $zayav_id,
				'v1' => '<table><tr><td>'.$old.'<td>ї<td>'.$new.'</table>'
			));
		}
		jsonSuccess();
		break;

	case 'salary_spisok':
		$send['balans'] = salaryWorkerBalans(_num($_POST['id']), 1);
		$send['acc'] = utf8(salary_worker_acc($_POST));
		$send['zp'] = utf8(salary_worker_zp($_POST));
		$send['month'] = utf8(salary_month_list($_POST));
		jsonSuccess($send);
		break;
	case 'salary_balans_set'://установка баланса сотрудника
		if(!$worker_id = _num($_POST['worker_id']))
			jsonError();

		$sum = _cena($_POST['sum'], 1);

		if(!$r = _viewerWorkerQuery($worker_id))
			jsonError();

		$start = $sum - (salaryWorkerBalans($worker_id) - $r['salary_balans_start']);

		$sql = "UPDATE `_vkuser`
				SET `salary_balans_start`=".$start."
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `viewer_id`=".$worker_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'viewer_'.$worker_id);

 		_balans(array(
			'category_id' => 5,
			'action_id' => 5,
			'worker_id' => $worker_id,
			'sum' => $sum
		));

		_history(array(
			'type_id' => 45,
			'worker_id' => $worker_id,
			'v1' => $sum
		));

		$send['balans'] = salaryWorkerBalans($worker_id, 1);
		jsonSuccess($send);
		break;
	case 'salary_rate_set'://установка ставки сотрудника
		if(!$worker_id = _num($_POST['worker_id']))
			jsonError();
		if(!$period = _num($_POST['period']))
			jsonError();

		$sum = _cena($_POST['sum']);

		$day = 0;
		switch($period) {
			case 1:
				if(!$day = _num($_POST['day']))
					jsonError();
				if($day > 28)
					jsonError();
				break;
			case 2:
				if(!$day = _num($_POST['day']))
					jsonError();
				if($day > 7)
					jsonError();
		}

		if(!$r = _viewerWorkerQuery($worker_id))
			jsonError();

		$sql = "UPDATE `_vkuser`
		        SET `salary_rate_sum`=".$sum.",
		            `salary_rate_period`=".$period.",
		            `salary_rate_day`=".$day."
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `viewer_id`=".$worker_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'viewer_'.$worker_id);

		$changes =
			_historyChange('—умма', _cena($r['salary_rate_sum']), $sum).
			_historyChange('ѕериод', $r['salary_rate_period'], $period, _salaryPeriod($r['salary_rate_period']), _salaryPeriod($period)).
			_historyChange('ƒень', $r['salary_rate_day'], $day, $r['salary_rate_day'] ? $r['salary_rate_day'] : '', $day ? $day : '');

		if($changes)
			_history(array(
				'type_id' => 35,
				'worker_id' => $worker_id,
				'v1' => '<table>'.$changes.'</table>'
			));

		$send['rate'] = utf8(salaryWorkerRate($worker_id));
		jsonSuccess($send);
		break;
	case 'salary_accrual_add'://внесение произвольного начислени€ зп сотруднику
		if(!$worker_id = _num($_POST['worker_id']))
			jsonError();
		if(!$sum = _cena($_POST['sum']))
			jsonError();
		if(!$mon = _num($_POST['mon']))
			jsonError();
		if(!$year = _num($_POST['year']))
			jsonError();
		if(!$about = _txt($_POST['about']))
			jsonError();

		if(!$r = _viewerWorkerQuery($worker_id))
			jsonError();

		$sql = "INSERT INTO `_salary_accrual` (
					`app_id`,
					`ws_id`,
					`worker_id`,
					`sum`,
					`about`,
					`mon`,
					`year`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".WS_ID.",
					".$worker_id.",
					".$sum.",
					'".addslashes($about)."',
					".$mon.",
					".$year.",
					".VIEWER_ID."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

 		_balans(array(
			'category_id' => 5,
			'action_id' => 19,
			'worker_id' => $worker_id,
			'sum' => $sum
		));

		_history(array(
			'type_id' => 36,
			'worker_id' => $worker_id,
			'v1' => $sum,
			'v2' => $about
		));

		jsonSuccess();
		break;
	case 'salary_accrual_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_salary_accrual`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "DELETE FROM `_salary_accrual` WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

 		_balans(array(
			'category_id' => 5,
			'action_id' => 21,
			'worker_id' => $r['worker_id'],
			'sum' => _cena($r['sum'])
		));

		_history(array(
			'type_id' => 50,
			'worker_id' => $r['worker_id'],
			'v1' => _cena($r['sum']),
			'v2' => $r['about']
		));

		jsonSuccess();
		break;
	case 'salary_deduct_add':
		if(!$worker_id = _num($_POST['worker_id']))
			jsonError();
		if(!$sum = _num($_POST['sum']))
			jsonError();
		if(!$year = _num($_POST['year']))
			jsonError();
		if(!$mon = _num($_POST['mon']))
			jsonError();
		if(!$about = _txt($_POST['about']))
			jsonError();

		if(!$r = _viewerWorkerQuery($worker_id))
			jsonError();

		$sql = "INSERT INTO `_salary_deduct` (
					`app_id`,
					`ws_id`,
					`worker_id`,
					`sum`,
					`about`,
					`year`,
					`mon`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".WS_ID.",
					".$worker_id.",
					".$sum.",
					'".addslashes($about)."',
					".$year.",
					".$mon.",
					".VIEWER_ID."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		_balans(array(
			'category_id' => 5,
			'action_id' => 20,
			'worker_id' => $worker_id,
			'sum' => $sum
		));

		_history(array(
			'type_id' => 44,
			'worker_id' => $worker_id,
			'v1' => $sum,
			'v2' => $about
		));

		jsonSuccess();
		break;
	case 'salary_deduct_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_salary_deduct`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "DELETE FROM `_salary_deduct` WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

 		_balans(array(
			'category_id' => 5,
			'action_id' => 22,
			'worker_id' => $r['worker_id'],
			'sum' => _cena($r['sum'])
		));

		_history(array(
			'type_id' => 51,
			'worker_id' => $r['worker_id'],
			'v1' => _cena($r['sum']),
			'v2' => $r['about']
		));

		jsonSuccess();
		break;
	case 'salary_zp_add':
		if(!$worker_id = _num($_POST['worker_id']))
			jsonError();
		if(!$invoice_id = _num($_POST['invoice_id']))
			jsonError();
		if(!$sum = _cena($_POST['sum']))
			jsonError();
		if(!$mon = _num($_POST['mon']))
			jsonError();
		if(!$year = _num($_POST['year']))
			jsonError();

		$about = win1251(htmlspecialchars(trim($_POST['about'])));
		$about = _monthDef($mon).' '.$year.($about ? ', ' : '').$about;

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
					'".addslashes($about)."',
					".$invoice_id.",
					1,
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
			'type' => 37,
			'value' => $sum,
			'value1' => $about,
			'value2' => $worker_id
		));

		jsonSuccess();
		break;

}
