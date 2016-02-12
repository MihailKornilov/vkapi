<?php
switch(@$_POST['op']) {
	case 'accrual_add':
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();
		if(!$sum = _cena($_POST['sum']))
			jsonError();

		$about = _txt($_POST['about']);

		if(!$z = _zayavQuery($zayav_id))
			jsonError();

		_accrualAdd($z, $sum, $about);

		jsonSuccess();
		break;
	case 'accrual_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_money_accrual`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		if($r['schet_id'])
			jsonError('Начисление привязано к счёту на оплату');

		if($r['dogovor_id'])
			jsonError('Начисление привязано к договору');

		$sql = "UPDATE `_money_accrual`
				SET `deleted`=1,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		_zayavBalansUpdate($r['zayav_id']);

		//внесение баланса для клиента
		_balans(array(
			'action_id' => 26,
			'client_id' => $r['client_id'],
			'zayav_id' => $r['zayav_id'],
			'sum' => $r['sum'],
			'about' => $r['about']
		));

		_history(array(
			'type_id' => 77,
			'zayav_id' => $r['zayav_id'],
			'client_id' => $r['client_id'],
			'v1' => _cena($r['sum'], 2),
			'v2' => $r['about']
		));

		jsonSuccess();
		break;

	case 'income_spisok'://список платежей
		$data = income_spisok($_POST);
		$send['spisok'] = utf8($data['spisok']);
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

		$zayav_id = _num($_POST['zayav_id']);
		$client_id = 0;
		$confirm = _bool($_POST['confirm']);
		$prepay = _bool($_POST['prepay']);
		$place = _num($_POST['place']);
		$place_other = !$place ? _txt($_POST['place_other']) : '';
		$remind_ids = _ids($_POST['remind_ids']);

		//в произвольном платеже обязательно указывается описание
		if(!$zayav_id && !$about)
			jsonError();

		if($zayav_id) {
			if(!$r = _zayavQuery($zayav_id))
				jsonError();
			$client_id = $r['client_id'];
		}

		$sql = "INSERT INTO `_money_income` (
				`app_id`,
				`ws_id`,
				`invoice_id`,
				`sum`,
				`about`,
				`zayav_id`,
				`client_id`,
				`confirm`,
				`prepay`,
				`viewer_id_add`
			) VALUES (
				".APP_ID.",
				".WS_ID.",
				".$invoice_id.",
				".$sum.",
				'".addslashes($about)."',
				".$zayav_id.",
				".$client_id.",
				".$confirm.",
				".$prepay.",
				".VIEWER_ID."
			)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$insert_id = query_insert_id('_money_income', GLOBAL_MYSQL_CONNECT);

		//баланс для расчётного счёта
		if(!$confirm)
			_balans(array(
				'action_id' => 1,
				'invoice_id' => $invoice_id,
				'sum' => $sum,
				'income_id' => $insert_id
			));

		$about = ($prepay ? 'предоплата' : '').
				 ($prepay && $about ? '. ' : '').
				 $about;

		//баланс для клиента
		if($client_id && !$confirm)
			_balans(array(
				'action_id' => 27,
				'client_id' => $client_id,
				'sum' => $sum,
				'about' => $about
			));

		if($zayav_id) {
			_zayavBalansUpdate($zayav_id);
			zayavPlaceCheck($zayav_id, $place, $place_other);

			//отметка выбранных активных напоминаних выполненными
			if($remind_ids)
				_remind_active_to_ready($remind_ids);

			//проверка, если заявка оплачена полностью, перенести з/п сотрудника из неактивного списка, если такие есть
			_salaryZayavCheck($zayav_id);
		}

		_history(array(
			'type_id' => 78,
			'invoice_id' => $invoice_id,
			'client_id' => $client_id,
			'zayav_id' => $zayav_id,
			'v1' => $sum,
			'v2' => $about
		));

		jsonSuccess();
		break;
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

		_zayavBalansUpdate($r['zayav_id']);

		//баланс для расчётного счёта
		_balans(array(
			'action_id' => 2,
			'invoice_id' => $r['invoice_id'],
			'sum' => $r['sum'],
			'income_id' => $r['id']
		));

		$about = ($r['prepay'] ? 'предоплата' : '').
				 ($r['prepay'] && $r['about'] ? '. ' : '').
				 $r['about'];

		//баланс для клиента
		if($r['client_id'])
			_balans(array(
				'action_id' => 28,
				'schet_id' => $r['schet_id'],
				'client_id' => $r['client_id'],
				'sum' => $r['sum'],
				'about' => $about
			));

		//платёж быть произведён по счёту
		_schetPayCorrect($r['schet_id']);

		_history(array(
			'type_id' => 9,
			'invoice_id' => $r['invoice_id'],
			'client_id' => $r['client_id'],
			'zayav_id' => $r['zayav_id'],
			'schet_id' => $r['schet_id'],
			'zp_id' => $r['zp_id'],
			'v1' => round($r['sum'], 2),
			'v2' => $about
		));
		jsonSuccess();
		break;
	case 'income_refund'://внесение возврата произвольного платежа
		if(!$id = _num($_POST['id']))
			jsonError();

		$dtime = _txt($_POST['dtime']);

		$sql = "SELECT *
				FROM `_money_income`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND !`client_id`
				  AND !`zp_id`
				  AND !`refund_id`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$about = $r['about'].'<br />Платёж от <u>'.$dtime.'</u>.';

		$sql = "INSERT INTO `_money_refund` (
					`app_id`,
					`ws_id`,
					`invoice_id`,
					`sum`,
					`about`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".WS_ID.",
					".$r['invoice_id'].",
					".$r['sum'].",
					'".addslashes($about)."',
					".VIEWER_ID."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$insert_id = query_insert_id('_money_refund', GLOBAL_MYSQL_CONNECT);

		$sql = "UPDATE `_money_income`
				SET `refund_id`=".$insert_id."
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		_balans(array(
			'action_id' => 13,
			'invoice_id' => $r['invoice_id'],
			'sum' => $r['sum'],
			'about' => $about
		));

		_history(array(
			'type_id' => 79,
			'invoice_id' => $r['invoice_id'],
			'v1' => _cena($r['sum']),
			'v2' => $r['about'],
			'v3' => $dtime
		));

		jsonSuccess();
		break;
	case 'income_confirm'://подтверждение поступления на счёт
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_money_income`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `confirm`=1
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_money_income`
				SET `confirm`=2,
					`confirm_dtime`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		_balans(array(
			'action_id' => 11,
			'invoice_id' => $r['invoice_id'],
			'income_id' => $r['id'],
			'sum' => $r['sum']
		));

		if($r['client_id'])
			_balans(array(
				'action_id' => 42,
				'client_id' => $r['client_id'],
				'income_id' => $r['id'],
				'sum' => $r['sum']
			));

		_history(array(
			'type_id' => 43,
			'invoice_id' => $r['invoice_id'],
			'zayav_id' => $r['zayav_id'],
			'v1' => _cena($r['sum'])
		));

		$send['dtime'] = utf8(FullDataTime());
		jsonSuccess($send);
		break;

	case 'refund_add'://внесение возврата
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();
		if(!$invoice_id = _num($_POST['invoice_id']))
			jsonError();
		if(!$sum = _cena($_POST['sum']))
			jsonError();
		if(!$about = _txt($_POST['about']))
			jsonError();

		if(!$z = _zayavQuery($zayav_id))
			jsonError();

		$sql = "INSERT INTO `_money_refund` (
					`app_id`,
					`ws_id`,
					`zayav_id`,
					`client_id`,
					`invoice_id`,
					`sum`,
					`about`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".WS_ID.",
					".$zayav_id.",
					".$z['client_id'].",
					".$invoice_id.",
					".$sum.",
					'".addslashes($about)."',
					".VIEWER_ID."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$insert_id = query_insert_id('_money_refund', GLOBAL_MYSQL_CONNECT);

		_zayavBalansUpdate($zayav_id);

		_balans(array(
			'action_id' => 13,
			'invoice_id' => $invoice_id,
			'sum' => $sum,
			'about' => $about
		));

		//внесение баланса для клиента
		_balans(array(
			'action_id' => 29,
			'client_id' => $z['client_id'],
			'sum' => $sum,
			'about' => $about
		));

		_history(array(
			'type_id' => 75,
			'zayav_id' => $zayav_id,
			'client_id' => $z['client_id'],
			'invoice_id' => $invoice_id,
			'v1' => _cena($sum),
			'v2' => $about
		));

		jsonSuccess();
		break;
	case 'refund_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_money_refund`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		if(TODAY != substr($r['dtime_add'], 0, 10))
			jsonError();

		$sql = "UPDATE `_money_refund`
				SET `deleted`=1,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		//отмена возврата произвольного платежа, если есть
		$sql = "UPDATE `_money_income`
				SET `refund_id`=0
				WHERE `refund_id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		_zayavBalansUpdate($r['zayav_id']);

		_balans(array(
			'action_id' => 14,
			'invoice_id' => $r['invoice_id'],
			'sum' => $r['sum'],
			'about' => $r['about']
		));

		//внесение баланса для клиента
		if($r['client_id'])
			_balans(array(
				'action_id' => 30,
				'client_id' => $r['client_id'],
				'sum' => $r['sum'],
				'about' => $r['about']
			));

		_history(array(
			'type_id' => 76,
			'zayav_id' => $r['zayav_id'],
			'client_id' => $r['client_id'],
			'invoice_id' => $r['invoice_id'],
			'v1' => _cena($r['sum'], 2),
			'v2' => $r['about']
		));

		jsonSuccess();
		break;
	case 'refund_spisok'://список возвратов
		$data = _refund_spisok($_POST);
		$send['spisok'] = utf8($data['spisok']);
		jsonSuccess($send);
		break;

	case 'expense_spisok':
		$data = expense_spisok($_POST);
		$send['html'] = utf8($data['spisok']);
		if($data['filter']['page'] == 1) {
			$send['mon'] = _sel(expenseMonthSum($_POST));
			$send['graf'] = expense_graf($data['filter'], 'arr');
		}
		jsonSuccess($send);
		break;
	case 'expense_add':
		if(!$invoice_id = _num($_POST['invoice_id']))
			jsonError();
		if(!$sum = _cena($_POST['sum']))
			jsonError();

		//обязательно должна быть указана категория, либо описание расхода
		$category_id = _num($_POST['category_id']);
		$about = _txt($_POST['about']);
		if(!$category_id && empty($about))
			jsonError();

		$worker_id = _num($_POST['worker_id']);
		$attach_id = _num(@$_POST['attach_id']);
		$salary_avans = _bool(@$_POST['salary_avans']);
		$salary_list_id = _num(@$_POST['salary_list_id']);
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
					`salary_avans`,
					`salary_list_id`,
					`attach_id`,
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
					".$salary_avans.",
					".$salary_list_id.",
					".$attach_id.",
					".$year.",
					".$mon.",
					".VIEWER_ID."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$insert_id = query_insert_id('_money_expense', GLOBAL_MYSQL_CONNECT);

		//история баланса для расчётного счёта
		_balans(array(
			'action_id' => 6,
			'invoice_id' => $invoice_id,
			'sum' => $sum,
			'expense_id' => $insert_id,
			'about' => $about
		));

		//история баланса для сотрудника
		if($worker_id)
			_balans(array(
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

		//обязательно должна быть указана категория, либо описание расхода
		$category_id = _num($_POST['category_id']);
		$about = _txt($_POST['about']);
		if(!$category_id && empty($about))
			jsonError();

		$worker_id = _num($_POST['worker_id']);
		$attach_id = _num(@$_POST['attach_id']);
		$salary_avans = _bool(@$_POST['salary_avans']);
		$salary_list_id = _num(@$_POST['salary_list_id']);
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
					`attach_id`=".$attach_id.",
					`salary_avans`=".$salary_avans.",
					`salary_list_id`=".$salary_list_id.",
					`year`=".$year.",
					`mon`=".$mon."
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		$mon_old = $r['mon'] ? _monthDef($r['mon']).' '.$r['year'] : '';
		$mon_new = $mon ? _monthDef($mon).' '.$year : '';

		$list = array();
		if($salary_list_id || $r['salary_list_id']) {
			$sql = "SELECT
						`id`,
						`nomer`
					FROM `_salary_list`
					WHERE `id` IN (".$salary_list_id.",".$r['salary_list_id'].")";
			$list = query_ass($sql, GLOBAL_MYSQL_CONNECT);
		}


		if($changes =
			_historyChange('Категория', $r['category_id'] ? _expense($r['category_id']) : '', $category_id ? _expense($category_id) : '').
			_historyChange('Описание', $r['about'], $about).
			_historyChange('Сотрудник', $r['worker_id'] ? _viewer($r['worker_id'], 'viewer_name') : '', $worker_id ? _viewer($worker_id, 'viewer_name') : '').
			_historyChange('Аванс', _daNet($r['salary_avans']),  _daNet($salary_avans)).
			_historyChange('Лист выдачи',
					$r['salary_list_id'] ? '№'.$list[$r['salary_list_id']] : '',
					$salary_list_id ? '№'.$list[$salary_list_id] : '').
			_historyChange('Месяц', $mon_old, $mon_new))
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
					`id`,
					`category_id`,
					`invoice_id`,
					`worker_id`,
					`salary_avans`,
					`salary_list_id`,
					`attach_id`,
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
		$r['attach'] = _attachArr($r['attach_id']);
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

		$sql = "UPDATE `_money_expense`
				SET `deleted`=1,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		_balans(array(
			'action_id' => 7,
			'invoice_id' => $r['invoice_id'],
			'sum' => $r['sum'],
			'expense_id' => $r['id'],
			'about' => $r['about']
		));

		//история баланса для сотрудника
		if($r['worker_id'])
			_balans(array(
				'action_id' => 24,
				'invoice_id' => $r['invoice_id'],
				'worker_id' => $r['worker_id'],
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

	case 'schet_spisok':
		$data = _schet_spisok($_POST);
		$send['spisok'] = utf8($data['spisok']);
		jsonSuccess($send);
		break;
	case 'schet_load':
		if(!$id = _num($_POST['id']))
			jsonError();

		if(!$schet = _schetQuery($id, 1))
			jsonError();

		//сегодня создан счёт или нет - для возможности редактирования
		$noedit = TODAY == substr($schet['dtime_add'], 0, 10) ? 0 : 1;

		$sql = "SELECT *
				FROM `_schet_content`
				WHERE `schet_id`=".$id."
				ORDER BY `id`";
		$content = query_arr($sql, GLOBAL_MYSQL_CONNECT);

		$html = '<table class="_spisok">'.
					'<tr><th>№'.
						'<th>Наименование товара'.
						'<th>Кол-во'.
						'<th>Цена'.
						'<th>Сумма';
		$n = 0;
		$sum = 0;
		$arr = array();
		foreach($content as $r) {
			$html .=
				'<tr><td class="n">'.(++$n).
					'<td class="name">'.$r['name'].
					'<td class="count">'.$r['count'].
					'<td class="cost">'._sumSpace($r['cost']).
					'<td class="sum">'._sumSpace($r['count'] * $r['cost']);
			$sum += $r['count'] * $r['cost'];

			$r['name'] = utf8($r['name']);
			$r['cost'] = _cena($r['cost']);
			$r['readonly'] = _num($r['readonly']) + $noedit;
			$arr[] = $r;
		}
		$html .= '</table>';

		//данные о заявке, которая привязана к счёту
		if($schet['zayav_id']) {
			$zayav[1] = array(
					'id' => 1,
					'zayav_id' => $schet['zayav_id']
			);
			$zayav = _zayavValToList($zayav);
			$send['zayav_link'] = utf8($zayav[1]['zayav_link']);
		}

		//список заявок клиента счёта (если нужно перепривязать счёт к другой заявке)
		$sql = "SELECT `id`,CONCAT('№',`nomer`)
				FROM `_zayav`
				WHERE `client_id`=".$schet['client_id']."
				ORDER BY `id`";
		$send['zayav_spisok'] = query_selArray($sql, GLOBAL_MYSQL_CONNECT);

		$send['schet_id'] = $id;
		$send['client_id'] = _num($schet['client_id']);
		$send['zayav_id'] = _num($schet['zayav_id']);
		$send['date_create'] = $schet['date_create'];
		$send['nakl'] = _bool($schet['nakl']);
		$send['act'] = _bool($schet['act']);
		$send['pass'] = _bool($schet['pass']);
		$send['paid'] = _num(_cena($schet['paid_sum']) && $schet['paid_sum'] >= $schet['sum']);
		$send['income'] = utf8(income_schet_spisok($schet));
		$send['client'] = utf8(_clientVal($schet['client_id'], 'link'));
		$send['nomer'] = utf8('СЦ'.$schet['nomer']);
		$send['ot'] = utf8(' от '.FullData($schet['date_create']).' г.');
		$send['html'] = utf8($html);
		$send['itog'] = utf8('Всего наименований <b>'.$n.'</b>, на сумму <b>'._sumSpace($sum).'</b> руб.');
		$send['arr'] = $arr;
		$send['noedit'] = $noedit;
		$send['del'] = _bool($schet['deleted']);
		$hist = _history(array('schet_id'=>$id));
		$send['hist'] = _num($hist['all']);
		$send['hist_spisok'] = utf8($hist['spisok']);
		jsonSuccess($send);
		break;
	case 'schet_edit'://создание или редактирование счёта
		if(!preg_match(REGEXP_DATE, $_POST['date_create']))
			jsonError();

		$schet_id = _num($_POST['schet_id']);
		$client_id = _num($_POST['client_id']);
		$zayav_id = _num($_POST['zayav_id']);
		$date_create = $_POST['date_create'];
		$nakl = _bool($_POST['nakl']);
		$act = _bool($_POST['act']);

		$schet = array();
		if($schet_id) {
			if(!$schet = _schetQuery($schet_id))
				jsonError();
			$client_id = $schet['client_id'];
		}

		if($zayav_id) {
			if(!$z = _zayavQuery($zayav_id))
				jsonError();
			$client_id = $z['client_id'];
		}

		if(!$client_id || !_clientQuery($client_id))
			jsonError();

		$spisok = @$_POST['spisok'];
		if(empty($spisok))
			jsonError();

		$sum = 0;
		foreach($spisok as $r) {
			$r['name'] = _txt($r['name']);
			$sum += _num($r['count']) * _cena($r['cost']);
		}

		$sql = "INSERT INTO `_schet` (
					`id`,
					`app_id`,
					`ws_id`,
					`nomer`,
					`client_id`,
					`zayav_id`,
					`date_create`,
					`nakl`,
					`act`,
					`sum`,
					`viewer_id_add`
				) VALUES (
					".$schet_id.",
					".APP_ID.",
					".WS_ID.",
					".(_maxSql('_schet', 'nomer', 1)).",
					".$client_id.",
					".$zayav_id.",
					'".$date_create."',
					".$nakl.",
					".$act.",
					".$sum.",
					".VIEWER_ID."
				) ON DUPLICATE KEY UPDATE
					`date_create`=VALUES(`date_create`),
					`zayav_id`=VALUES(`zayav_id`),
					`nakl`=VALUES(`nakl`),
					`act`=VALUES(`act`),
					`sum`=VALUES(`sum`)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$insert_id = 0;//изначально считается, что не вносится новый счёт, а редактируется существующий
		if(!$schet_id) {
			$insert_id = query_insert_id('_schet', GLOBAL_MYSQL_CONNECT);
			$schet_id = $insert_id;
		}

		$sql = "DELETE FROM `_schet_content` WHERE `schet_id`=".$schet_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		//внесение списка наименований для счёта
		$values = array();
		foreach($spisok as $r)
			$values[] = "(".
				$schet_id.",".
				"'".addslashes(win1251($r['name']))."',".
				$r['count'].",".
				$r['cost'].",".
				$r['readonly'].
			")";
		$sql = "INSERT INTO `_schet_content` (
					`schet_id`,
					`name`,
					`count`,
					`cost`,
					`readonly`
				) VALUES ".implode(',', $values);
		query($sql, GLOBAL_MYSQL_CONNECT);

		//начисление по счёту
		if($insert_id) {
			$sql = "INSERT INTO `_money_accrual` (
						`app_id`,
						`ws_id`,
						`schet_id`,
						`client_id`,
						`zayav_id`,
						`sum`,
						`viewer_id_add`
					) VALUES (
						".APP_ID.",
						".WS_ID.",
						".$schet_id.",
						".$client_id.",
						".$zayav_id.",
						".$sum.",
						".VIEWER_ID."
					)";
			query($sql, GLOBAL_MYSQL_CONNECT);

			//баланс для клиента
			_balans(array(
				'action_id' => 25,//новое начисление
				'client_id' => $client_id,
				'schet_id' => $schet_id,
				'zayav_id' => $zayav_id,
				'sum' => $sum
			));
		} else {
			//изменение начисления
			$sql = "SELECT *
					FROM `_money_accrual`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND !`deleted`
					  AND `schet_id`=".$schet_id."
					LIMIT 1";
			$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT);

			$sql = "UPDATE `_money_accrual`
					SET `sum`=".$sum.",
						`zayav_id`=".$zayav_id."
					WHERE `id`=".$r['id'];
			query($sql, GLOBAL_MYSQL_CONNECT);

			//баланс для клиента
			if($sum != $r['sum'])
				_balans(array(
					'action_id' => 37,//изменение начисления
					'client_id' => $client_id,
					'schet_id' => $schet_id,
					'zayav_id' => $zayav_id,
					'sum' => $sum,
					'sum_old' => $r['sum']
				));
		}

		_zayavBalansUpdate(_num(@$schet['zayav_id']));
		_zayavBalansUpdate($zayav_id);

		if($insert_id)
			_history(array(
				'type_id' => 59,
				'schet_id' => $schet_id,
				'client_id' => $client_id,
				'zayav_id' => $zayav_id,
				'v1' => $sum
			));
		else {
			//список заявок клиента счёта (если нужно перепривязать счёт к другой заявке)
			$sql = "SELECT `id`,CONCAT('№',`nomer`)
					FROM `_zayav`
					WHERE `client_id`=".$schet['client_id']."
					ORDER BY `id`";
			$zayav = query_ass($sql, GLOBAL_MYSQL_CONNECT) + array(0=>'');
			if($changes =
				_historyChange('Заявка', $zayav[$schet['zayav_id']], $zayav[$zayav_id]).
				_historyChange('Дата', FullData($schet['date_create']), FullData($date_create)).
				_historyChange('Накладная', _daNet($schet['nakl']), _daNet($nakl)).
				_historyChange('Акт', _daNet($schet['act']), _daNet($act)).
				_historyChange('Сумма', _cena($schet['sum']), _cena($sum)))
				_history(array(
					'type_id' => 61,
					'schet_id' => $schet_id,
					'client_id' => $client_id,
					'zayav_id' => $zayav_id,
					'v1' => '<table>'.$changes.'</table>'
				));
		}

		$send['schet_id'] = $schet_id;
		jsonSuccess($send);
		break;
	case 'schet_pass'://передача счёта клиенту
		if(!$schet_id = _num($_POST['schet_id']))
			jsonError();
		if(!preg_match(REGEXP_DATE, $_POST['day']))
			jsonError();

		$day = $_POST['day'];
		if(!$r = _schetQuery($schet_id))
			jsonError();

		if($r['pass'])
			jsonError();

		$sql = "UPDATE `_schet`
				SET `pass`=1,
					`pass_day`='".$day."'
					WHERE `id`=".$schet_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		_history(array(
			'type_id' => 63,
			'schet_id' => $schet_id,
			'client_id' => $r['client_id'],
			'zayav_id' => $r['zayav_id']
		));

		jsonSuccess();
		break;
	case 'schet_pass_cancel'://отмена передачи счёта
		if(!$schet_id = _num($_POST['schet_id']))
			jsonError();

		if(!$r = _schetQuery($schet_id))
			jsonError();

		if(!$r['pass'])
			jsonError();

		$sql = "UPDATE `_schet`
				SET `pass`=0,
					`pass_day`='0000-00-00'
					WHERE `id`=".$schet_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		_history(array(
			'type_id' => 65,
			'schet_id' => $schet_id,
			'client_id' => $r['client_id'],
			'zayav_id' => $r['zayav_id']
		));

		jsonSuccess();
		break;
	case 'schet_pay'://оплата счёта
		if(!$schet_id = _num($_POST['schet_id']))
			jsonError();
		if(!$invoice_id = _num($_POST['invoice_id']))
			jsonError();
		if(!$sum = _cena($_POST['sum']))
			jsonError();
		if(!preg_match(REGEXP_DATE, $_POST['day']))
			jsonError();

		$day = $_POST['day'];

		if(!$r = _schetQuery($schet_id))
			jsonError();

		$sql = "INSERT INTO `_money_income` (
				`app_id`,
				`ws_id`,
				`schet_id`,
				`schet_paid_day`,
				`invoice_id`,
				`sum`,
				`client_id`,
				`zayav_id`,
				`viewer_id_add`
			) VALUES (
				".APP_ID.",
				".WS_ID.",
				".$schet_id.",
				'".$day."',
				".$invoice_id.",
				".$sum.",
				".$r['client_id'].",
				".$r['zayav_id'].",
				".VIEWER_ID."
			)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$income_id = query_insert_id('_money_income', GLOBAL_MYSQL_CONNECT);

		_schetPayCorrect($schet_id);

		//баланс для расчётного счёта
		_balans(array(
			'action_id' => 1,
			'invoice_id' => $invoice_id,
			'schet_id' => $schet_id,
			'income_id' => $income_id,
			'sum' => $sum
		));

		//баланс для клиента
		_balans(array(
			'action_id' => 27,
			'schet_id' => $schet_id,
			'client_id' => $r['client_id'],
			'sum' => $sum
		));

		_history(array(
			'type_id' => 60,
			'schet_id' => $schet_id,
			'invoice_id' => $invoice_id,
			'client_id' => $r['client_id'],
			'zayav_id' => $r['zayav_id'],
			'v1' => _cena($sum)
		));

		jsonSuccess();
		break;
	case 'schet_del'://удаление счёта
		if(!$schet_id = _num($_POST['id']))
			jsonError();

		if(!$r = _schetQuery($schet_id))
			jsonError();

		$sql = "SELECT SUM(`sum`)
				FROM `_money_income`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `schet_id`=".$schet_id;
		if(query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError('По этому счёту уже производились платежи');

		$sql = "UPDATE `_schet`
				SET `deleted`=1
				WHERE `id`=".$schet_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		//удаление начисления
		$sql = "UPDATE `_money_accrual`
				SET `deleted`=1
				WHERE `schet_id`=".$schet_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		zayavCartridgeSchetDel($schet_id);
		_zayavBalansUpdate($r['zayav_id']);

		//внесение баланса для клиента
		_balans(array(
			'action_id' => 26,//удаление начисления
			'client_id' => $r['client_id'],
			'zayav_id' => $r['zayav_id'],
			'schet_id' => $schet_id,
			'sum' => $r['sum']
		));

		_history(array(
			'type_id' => 66,
			'schet_id' => $schet_id,
			'client_id' => $r['client_id'],
			'zayav_id' => $r['zayav_id']
		));

		jsonSuccess();
		break;

	case 'invoice_add':
		if(!RULE_SETUP_INVOICE)
			jsonError();

		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);
		$income = _bool($_POST['income']);
		$transfer = _bool($_POST['transfer']);
		if(($visible = _ids($_POST['visible'])) == false && $_POST['visible'] != 0)
			jsonError();

		if(empty($name))
			jsonError();

		$sql = "INSERT INTO `_money_invoice` (
					`app_id`,
					`ws_id`,
					`name`,
					`about`,
					`confirm_income`,
					`confirm_transfer`,
					`visible`
				) VALUES (
					".APP_ID.",
					".WS_ID.",
					'".addslashes($name)."',
					'".addslashes($about)."',
					".$income.",
					".$transfer.",
					'".$visible."'
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'invoice'.WS_ID);
		_wsJsValues();

		_history(array(
			'type_id' => 1022,
			'v1' => $name
		));

		$send['html'] = utf8(invoice_spisok());
		jsonSuccess($send);
		break;
	case 'invoice_edit':
		if(!RULE_SETUP_INVOICE)
			jsonError();
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);
		$income = _bool($_POST['income']);
		$transfer = _bool($_POST['transfer']);
		if(($visible = _ids($_POST['visible'])) == false && $_POST['visible'] != 0)
			jsonError();

		if(empty($name))
			jsonError();

		$sql = "SELECT *
				FROM `_money_invoice`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted` AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_money_invoice`
				SET `name`='".addslashes($name)."',
					`about`='".addslashes($about)."',
					`confirm_income`=".$income.",
					`confirm_transfer`=".$transfer.",
					`visible`='".$visible."'
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'invoice'.WS_ID);
		_wsJsValues();

		//формирование списка сотрудников, которым доступен счёт
		$old = array();
		if($r['visible'])
			foreach(explode(',', $r['visible']) as $i)
				$old[] = _viewer($i, 'viewer_name');
		$old = implode('<br />', $old);

		$new = array();
		if($visible)
			foreach(explode(',', $visible) as $i)
				$new[] = _viewer($i, 'viewer_name');
		$new = implode('<br />', $new);

		if($changes =
			_historyChange('Наименование', $r['name'], $name).
			_historyChange('Описание', _br($r['about']), _br($about)).
			_historyChange('Подтверждение поступления на счёт', _daNet($r['confirm_income']), _daNet($income)).
			_historyChange('Подтверждение перевода', _daNet($r['confirm_transfer']), _daNet($transfer)).
			_historyChange('Видимость для сотрудников', $old, $new))
			_history(array(
				'type_id' => 1023,
				'v1' => $name,
				'v2' => '<table>'.$changes.'</table>'
			));

		$send['html'] = utf8(invoice_spisok());
		jsonSuccess($send);
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
		if(!RULE_SETUP_INVOICE)
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
	case 'invoice_close':
		if(!RULE_SETUP_INVOICE)
			jsonError();
		if(!$invoice_id = _num($_POST['invoice_id']))
			jsonError();

		$invoice_to = _num($_POST['invoice_to']);

		if($invoice_id == $invoice_to) // если счета одинаковые
			jsonError();

		$sql = "SELECT *
				FROM `_money_invoice`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$invoice_id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		if($balans = _invoiceBalans($invoice_id)) {//перевод средств, если деньги остались на закрываемом счёте
			if(!$invoice_to)
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
						".$invoice_id.",
						".$invoice_to.",
						".$balans.",
						'закрыт счёт \""._invoice($invoice_id)."\"',
						".VIEWER_ID."
					)";
			query($sql, GLOBAL_MYSQL_CONNECT);

			$insert_id = query_insert_id('_money_invoice_transfer', GLOBAL_MYSQL_CONNECT);

			//история баланса для счёта-отправителя (который закрывается)
			_balans(array(
				'action_id' => 4,
				'invoice_id' => $invoice_id,
				'sum' => $balans,
				'invoice_transfer_id' => $insert_id
			));

			//история баланса для счёта-получателя
			_balans(array(
				'action_id' => 4,
				'invoice_id' => $invoice_to,
				'sum' => $balans,
				'invoice_transfer_id' => $insert_id
			));
		}

		$sql = "UPDATE `_money_invoice` SET `deleted`=1 WHERE `id`=".$invoice_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'invoice'.WS_ID);
		_wsJsValues();

		_balans(array(
			'action_id' => 15,//закрытие
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
			'action_id' => 4,
			'invoice_id' => $from,
			'sum' => $sum,
			'invoice_transfer_id' => $insert_id
		));

		_balans(array(
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
			'action_id' => 12,
			'invoice_id' => $r['invoice_id_from'],
			'sum' => $r['sum'],
			'invoice_transfer_id' => $r['id']
		));

		_balans(array(
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

	case 'salary_spisok':
		$send['balans'] = salaryWorkerBalans(_num($_POST['id']), 1);
		$filter = salaryFilter($_POST);
		$send['list'] = utf8(salary_worker_list($filter));
		$send['list_array'] = salary_worker_list(array('list_type'=>'array') + $filter);
		$send['acc'] = utf8(salary_worker_acc($filter));
		$send['noacc'] = utf8(salary_worker_noacc($filter));
		$send['zp'] = utf8(salary_worker_zp($filter));
		$send['month'] = utf8(salary_month_list($filter));
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
			'action_id' => 39,
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

		if($changes =
			_historyChange('Сумма', _cena($r['salary_rate_sum']) ? _cena($r['salary_rate_sum']) : '', $sum ? $sum : '').
			_historyChange('Период', $r['salary_rate_period'] ? _salaryPeriod($r['salary_rate_period']) : '', _salaryPeriod($period)).
			_historyChange('День', $r['salary_rate_day'] ? $r['salary_rate_day'] : '', $day ? $day : ''))
			_history(array(
				'type_id' => 35,
				'worker_id' => $worker_id,
				'v1' => '<table>'.$changes.'</table>'
			));

		$send['rate'] = utf8(salaryWorkerRate($worker_id));
		jsonSuccess($send);
		break;
	case 'salary_accrual_add'://внесение произвольного начисления зп сотруднику
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
			'action_id' => 19,
			'worker_id' => $worker_id,
			'sum' => $sum,
			'about' => $about
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
				  AND !`salary_list_id`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "DELETE FROM `_salary_accrual` WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

 		_balans(array(
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
				  AND !`salary_list_id`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "DELETE FROM `_salary_deduct` WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

 		_balans(array(
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
	case 'salary_list_create':
		if(!$worker_id = _num($_POST['worker_id']))
			jsonError();
		if(!$mon = _num($_POST['mon']))
			jsonError();
		if(!$year = _num($_POST['year']))
			jsonError();
		if(!$ids = _txt($_POST['ids']))
			jsonError();

		//Формирование списков id начислений
		$accrual_ids = array();
		$deduct_ids = array();
		$expense_ids = array();
		foreach(explode(',', $ids) as $r) {
			$i = explode(':', $r);
			if(!$id = _num($i[1]))
				jsonError('Некорректный id начисления');
			switch($i[0]) {
				case 'accrual': $accrual_ids[] = $id; break;
				case 'deduct': $deduct_ids[] = $id; break;
				case 'expense': $expense_ids[] = $id; break;
				default: jsonError('Неизвестный тип начисления');
			}
		}
		if(!$accrual_ids && !$deduct_ids && !$expense_ids)
			jsonError('Начисления отсутствуют');

		$accrual_ids = implode(',', $accrual_ids);
		$deduct_ids  = implode(',', $deduct_ids);
		$expense_ids = implode(',', $expense_ids);

		$accrual_sum = 0;
		$deduct_sum = 0;
		$expense_sum = 0;

		//Проверка, чтобы текущие начисления не были внесены ранее
		if($accrual_ids) {
			$sql = "SELECT COUNT(*)
					FROM `_salary_accrual`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND `salary_list_id`
					  AND `id` IN (".$accrual_ids.")";
			if(query_value($sql, GLOBAL_MYSQL_CONNECT))
				jsonError('Некоторые произвольные начисления уже были внесены');

			$sql = "SELECT IFNULL(SUM(`sum`), 0)
					FROM `_salary_accrual`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND `id` IN (".$accrual_ids.")";
			$accrual_sum = query_value($sql, GLOBAL_MYSQL_CONNECT);
		}
		if($deduct_ids) {
			$sql = "SELECT COUNT(*)
					FROM `_salary_deduct`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND `salary_list_id`
					  AND `id` IN (".$deduct_ids.")";
			if(query_value($sql, GLOBAL_MYSQL_CONNECT))
				jsonError('Некоторые вычеты уже были внесены');

			$sql = "SELECT IFNULL(SUM(`sum`), 0)
					FROM `_salary_deduct`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND `id` IN (".$deduct_ids.")";
			$deduct_sum = query_value($sql, GLOBAL_MYSQL_CONNECT);
		}
		if($expense_ids) {
			$sql = "SELECT COUNT(*)
					FROM `_zayav_expense`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND `salary_list_id`
					  AND `id` IN (".$expense_ids.")";
			if(query_value($sql, GLOBAL_MYSQL_CONNECT))
				jsonError('Некоторые начисления расходов по заявке уже были внесены');

			$sql = "SELECT IFNULL(SUM(`sum`), 0)
					FROM `_zayav_expense`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND `id` IN (".$expense_ids.")";
			$expense_sum = query_value($sql, GLOBAL_MYSQL_CONNECT);
		}

		//Общая сумма всех начислений
		$sum = $accrual_sum - $deduct_sum + $expense_sum;
		
		//Внесение листа выдачи
		$sql = "INSERT INTO `_salary_list` (
					`app_id`,
					`ws_id`,
					`nomer`,
					`worker_id`,
					`sum`,
					`year`,
					`mon`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".WS_ID.",
					"._maxSql('_salary_list', 'nomer', 1).",
					".$worker_id.",
					".$sum.",
					'".$year."',
					'".$mon."',
					".VIEWER_ID."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$insert_id = query_insert_id('_salary_list', GLOBAL_MYSQL_CONNECT);

		//Привязка листа выдачи к начислениям
		if($accrual_ids) {
			$sql = "UPDATE `_salary_accrual`
					SET `salary_list_id`=".$insert_id."
					WHERE `id` IN (".$accrual_ids.")";
			query($sql, GLOBAL_MYSQL_CONNECT);
		}
		if($deduct_ids) {
			$sql = "UPDATE `_salary_deduct`
					SET `salary_list_id`=".$insert_id."
					WHERE `id` IN (".$deduct_ids.")";
			query($sql, GLOBAL_MYSQL_CONNECT);
		}
		if($expense_ids) {
			$sql = "UPDATE `_zayav_expense`
					SET `salary_list_id`=".$insert_id."
					WHERE `id` IN (".$expense_ids.")";
			query($sql, GLOBAL_MYSQL_CONNECT);
		}

		//Привязка листа выдачи к авансам
		$sql = "UPDATE `_money_expense`
				SET `salary_list_id`=".$insert_id.",
					`salary_avans`=1
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `worker_id`=".$worker_id."
				  AND `year`=".$year."
				  AND `mon`=".$mon."
				  AND !`deleted`
				  AND !`salary_list_id`";
		query($sql, GLOBAL_MYSQL_CONNECT);

		_history(array(
			'type_id' => 87,
			'worker_id' => $worker_id,
			'v1' => round($sum, 2),
			'v2' => _monthDef($mon).' '.$year
		));

		jsonSuccess();
		break;
	case 'salary_list_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_salary_list`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		if(TODAY != substr($r['dtime_add'], 0, 10))
			jsonError();

		$sql = "SELECT COUNT(`id`)
				FROM `_money_expense`
				WHERE !`deleted`
				  AND !`salary_avans`
				  AND `salary_list_id`=".$id;
		if(query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError('По листу выдачи уже произведена оплата');

		$sql = "UPDATE `_salary_accrual` SET `salary_list_id`=0 WHERE `salary_list_id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		$sql = "UPDATE `_salary_deduct` SET `salary_list_id`=0 WHERE `salary_list_id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		$sql = "UPDATE `_zayav_expense` SET `salary_list_id`=0 WHERE `salary_list_id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		$sql = "UPDATE `_money_expense` SET `salary_list_id`=0 WHERE `salary_list_id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		$sql = "DELETE FROM `_salary_list` WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		_history(array(
			'type_id' => 88,
			'worker_id' => $r['worker_id'],
			'v1' => round($r['sum'], 2),
			'v2' => _monthDef($r['mon']).' '.$r['year']
		));

		jsonSuccess();
		break;
	case 'salary_noacc_recalc'://перерасчёт баланса сотрудника по неактивным начислениям
		if(!VIEWER_ADMIN)
			jsonError();
		if(!$worker_id = _num($_POST['worker_id']))
			jsonError();

		$onPay = _viewerRule($worker_id, 'RULE_SALARY_ZAYAV_ON_PAY');
		$balansStart = salaryWorkerBalans($worker_id);

		$changes = '';

		//удаление начислений зп по удалённым заявкам
		$sql = "SELECT `id`
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `deleted`";
		if($ids = query_ids($sql, GLOBAL_MYSQL_CONNECT)) {
			$sql = "DELETE FROM `_zayav_expense`
					WHERE `worker_id`=".$worker_id."
					  AND `zayav_id` IN (".$ids.")";
			query($sql, GLOBAL_MYSQL_CONNECT);
			if(mysql_affected_rows())
				$changes .= '<tr><td>Удалены начисления из удалённых заявок:<td>'.mysql_affected_rows();
		}

		//список всех заявок, у которых есть долг
		$sql = "SELECT `id`
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `sum_dolg`<0";
		$ids = query_ids($sql, GLOBAL_MYSQL_CONNECT);

		if($onPay) {
			//перенос начислений по заявкам, у которых есть долги, в неактивный список
			$sql = "UPDATE `_zayav_expense`
					SET `year`=0,
						`mon`=0
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND `worker_id`=".$worker_id."
					  AND !`salary_list_id`
					  AND `year`
					  AND `mon`
					  AND `zayav_id` IN (".$ids.")";
			query($sql, GLOBAL_MYSQL_CONNECT);
			if(mysql_affected_rows())
				$changes .= '<tr><td>Перенесены начисления по заявкам<br />в неактивный список, у которых есть долги:<td>'.mysql_affected_rows();
		}

		//id заявкок из неактивного списка
		$sql = "SELECT `zayav_id`
				FROM `_zayav_expense`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `worker_id`=".$worker_id."
				  AND (!`year` OR !`mon`)";
		if($ids = query_ids($sql, GLOBAL_MYSQL_CONNECT)) {
			$rec = 0;//количество восстановленных начислений
			//заявки, у которых нет долгов, уходят из неактивного списка в любом случае
			$sql = "SELECT `id`
					FROM `_zayav`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND !`deleted`
					  AND `id` IN (".$ids.")
					  AND `sum_accrual`-`sum_pay`<=0";
			if($zayav_ids = query_ids($sql, GLOBAL_MYSQL_CONNECT)) {
				$sql = "UPDATE `_zayav_expense`
						SET `year`=".strftime('%Y').",
							`mon`=".strftime('%m')."
						WHERE `worker_id`=".$worker_id."
						  AND `zayav_id` IN (".$zayav_ids.")";
				query($sql, GLOBAL_MYSQL_CONNECT);
				$rec = mysql_affected_rows();
			}

			if(!$onPay) {//перенос начислений по заявкам из неактивного списка в текущий месяц (галочка не установлена)
				$sql = "UPDATE `_zayav_expense`
						SET `year`=".strftime('%Y').",
							`mon`=".strftime('%m')."
						WHERE `worker_id`=".$worker_id."
						  AND `zayav_id` IN (".$ids.")";
				query($sql, GLOBAL_MYSQL_CONNECT);
				$rec += mysql_affected_rows();
			}

			if($rec)
				$changes .= '<tr><td>Восстановлены начисления по заявкам<br />из неактивнго списка, у которых нет долгов:<td>'.$rec;
		}

		$balansEnd = salaryWorkerBalans($worker_id);

		if($balansStart != $balansEnd) {
			_balans(array(
				'action_id' => 43,
				'worker_id' => $worker_id,
				'about' => 'Корректировка начислений по заявкам с долгами.'
			));
			$changes .= '<tr><td>Изменился баланс сотрудника:<td>'.$balansStart.' -> '.$balansEnd;
		}

		if($changes)
			_history(array(
				'type_id' => 500,
				'worker_id' => $worker_id,
				'v1' => '<table>'.$changes.'</table>'
			));

		jsonSuccess();
		break;
}
