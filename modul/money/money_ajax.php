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

		_salaryZayavBonus($zayav_id);

		jsonSuccess();
		break;
	case 'accrual_edit':
		if(!$id = _num($_POST['id']))
			jsonError();
		if(!$sum = _cena($_POST['sum']))
			jsonError();

		$about = _txt($_POST['about']);

		$sql = "SELECT *
				FROM `_money_accrual`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		if($r['dogovor_id'])
			jsonError();
		if($r['schet_id'])
			jsonError();

		$sql = "UPDATE `_money_accrual`
				SET `sum`=".$sum.",
					`about`='".addslashes($about)."'
				WHERE `id`=".$id;
		query($sql);

		//баланс клиента
		if(_cena($r['sum']) != $sum)
			_balans(array(
				'action_id' => 37,
				'client_id' => $r['client_id'],
				'zayav_id' => $r['zayav_id'],
				'sum_old' => $r['sum'],
				'sum' => $sum,
				'about' => $about
			));

		_zayavBalansUpdate($r['zayav_id']);
		_salaryZayavCheck($r['zayav_id']);
		_salaryZayavBonus($r['zayav_id']);

		if($changes =
			_historyChange('Сумма', _cena($r['sum']), $sum).
			_historyChange('Описание', $r['about'], $about))
			_history(array(
				'type_id' => 90,
				'zayav_id' => $r['zayav_id'],
				'v1' => '<table>'.$changes.'</table>'
			));

		jsonSuccess();
		break;
	case 'accrual_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_money_accrual`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
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
		query($sql);

		_zayavBalansUpdate($r['zayav_id']);
		_salaryZayavCheck($r['zayav_id']);
		_salaryZayavBonus($r['zayav_id']);

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
		$place_id = _num(@$_POST['place_id']);
		$place_other = !$place_id ? _txt(@$_POST['place_other']) : '';
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
				".$invoice_id.",
				".$sum.",
				'".addslashes($about)."',
				".$zayav_id.",
				".$client_id.",
				".$confirm.",
				".$prepay.",
				".VIEWER_ID."
			)";
		query($sql);

		$insert_id = query_insert_id('_money_income');

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
			_zayavTovarPlaceUpdate($zayav_id, $place_id, $place_other);

			//отметка выбранных активных напоминаних выполненными
			if($remind_ids)
				_remind_active_to_ready($remind_ids);

			//проверка, если заявка оплачена полностью, перенести з/п сотрудника из неактивного списка, если такие есть
			_salaryZayavCheck($zayav_id);
			_salaryZayavBonus($zayav_id);
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
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('Платежа не существует');

		//если платёж уже подтверждён
		if($r['confirm'] == 2)
			jsonError('Платёж был подтверждён');

		if($r['confirm'] != 1 && TODAY != substr($r['dtime_add'], 0, 10))
			jsonError('Время для удаления платежа истекло');

		$sql = "UPDATE `_money_income`
				SET `deleted`=1,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;
		query($sql);

		_zayavBalansUpdate($r['zayav_id']);
		_salaryZayavBonus($r['zayav_id']);

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

		//была произведена продажа товара
		_tovarAvaiUpdate($r['tovar_id']);

		_history(array(
			'type_id' => 9,
			'invoice_id' => $r['invoice_id'],
			'client_id' => $r['client_id'],
			'zayav_id' => $r['zayav_id'],
			'schet_id' => $r['schet_id'],
			'tovar_id' => $r['tovar_id'],
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
				  AND !`deleted`
				  AND !`client_id`
				  AND !`tovar_id`
				  AND !`refund_id`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$about = $r['about'].'<br />Возврат платёжа от <u>'.$dtime.'</u>.';

		$sql = "INSERT INTO `_money_refund` (
					`app_id`,
					`invoice_id`,
					`zayav_id`,
					`sum`,
					`about`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$r['invoice_id'].",
					".$r['zayav_id'].",
					".$r['sum'].",
					'".addslashes($about)."',
					".VIEWER_ID."
				)";
		query($sql);

		$insert_id = query_insert_id('_money_refund');

		$sql = "UPDATE `_money_income`
				SET `refund_id`=".$insert_id."
				WHERE `id`=".$id;
		query($sql);

		_zayavBalansUpdate($r['zayav_id']);

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
				  AND !`deleted`
				  AND `confirm`=1
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_money_income`
				SET `confirm`=2,
					`confirm_dtime`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;
		query($sql);

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
		
		_zayavBalansUpdate($r['zayav_id']);
		_salaryZayavCheck($r['zayav_id']);

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
					`zayav_id`,
					`client_id`,
					`invoice_id`,
					`sum`,
					`about`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$zayav_id.",
					".$z['client_id'].",
					".$invoice_id.",
					".$sum.",
					'".addslashes($about)."',
					".VIEWER_ID."
				)";
		query($sql);

		$insert_id = query_insert_id('_money_refund');

		_zayavBalansUpdate($zayav_id);
		_salaryZayavBonus($zayav_id);

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
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		if(TODAY != substr($r['dtime_add'], 0, 10))
			jsonError();

		$sql = "UPDATE `_money_refund`
				SET `deleted`=1,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;
		query($sql);

		//отмена возврата произвольного платежа, если есть
		$sql = "UPDATE `_money_income`
				SET `refund_id`=0
				WHERE `refund_id`=".$id;
		query($sql);

		_zayavBalansUpdate($r['zayav_id']);
		_salaryZayavBonus($r['zayav_id']);

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
			if(VIEWER_ADMIN)
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

		$category_sub_id = _num(@$_POST['category_sub_id']);
		$worker_id = _num(@$_POST['worker_id']);
		$attach_id = _num(@$_POST['attach_id']);
		$salary_avans = _bool(@$_POST['salary_avans']);
		$salary_list_id = _num(@$_POST['salary_list_id']);
		$mon = _num(@$_POST['mon']);
		$year = _num(@$_POST['year']);
		if($category_id == 1 && (!$worker_id || !$year || !$mon))
			jsonError();

		$sql = "INSERT INTO `_money_expense` (
					`app_id`,
					`sum`,
					`about`,
					`invoice_id`,
					`category_id`,
					`category_sub_id`,
					`worker_id`,
					`salary_avans`,
					`salary_list_id`,
					`attach_id`,
					`year`,
					`mon`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$sum.",
					'".addslashes($about)."',
					".$invoice_id.",
					".$category_id.",
					".$category_sub_id.",
					".$worker_id.",
					".$salary_avans.",
					".$salary_list_id.",
					".$attach_id.",
					".$year.",
					".$mon.",
					".VIEWER_ID."
				)";
		query($sql);

		$insert_id = query_insert_id('_money_expense');

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

		$category_sub_id = _num(@$_POST['category_sub_id']);
		$worker_id = _num(@$_POST['worker_id']);
		$attach_id = _num(@$_POST['attach_id']);
		$salary_avans = _bool(@$_POST['salary_avans']);
		$salary_list_id = _num(@$_POST['salary_list_id']);
		$mon = _num(@$_POST['mon']);
		$year = _num(@$_POST['year']);
		if($category_id == 1 && (!$worker_id || !$year || !$mon))
			jsonError();

		$sql = "SELECT *
				FROM `_money_expense`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_money_expense`
				SET `about`='".addslashes($about)."',
					`category_id`=".$category_id.",
					`category_sub_id`=".$category_sub_id.",
					`worker_id`=".$worker_id.",
					`attach_id`=".$attach_id.",
					`salary_avans`=".$salary_avans.",
					`salary_list_id`=".$salary_list_id.",
					`year`=".$year.",
					`mon`=".$mon."
				WHERE `id`=".$id;
		query($sql);

		$mon_old = $r['mon'] ? _monthDef($r['mon']).' '.$r['year'] : '';
		$mon_new = $mon ? _monthDef($mon).' '.$year : '';

		$list = array();
		if($salary_list_id || $r['salary_list_id']) {
			$sql = "SELECT
						`id`,
						`nomer`
					FROM `_salary_list`
					WHERE `id` IN (".$salary_list_id.",".$r['salary_list_id'].")";
			$list = query_ass($sql);
		}


		if($changes =
			_historyChange('Категория',
				$r['category_id'] ? _expense($r['category_id']).($r['category_sub_id'] ? ': '._expenseSub($r['category_sub_id']) : '') : '',
				$category_id ? _expense($category_id).($category_sub_id ? ': '._expenseSub($category_sub_id) : '') : '').
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
					`category_sub_id`,
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
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
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
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('Расхода не существует');

		if(TODAY != substr($r['dtime_add'], 0, 10))
			jsonError('Время для удаления расхода истекло');

		$sql = "UPDATE `_money_expense`
				SET `deleted`=1,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;
		query($sql);

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
		if(!$schet_id = _num($_POST['id']))
			jsonError();

		if(!$schet = _schetQuery($schet_id, 1))
			jsonError();

		//сегодня создан счёт или нет - для возможности редактирования
		$noedit = TODAY == substr($schet['dtime_add'], 0, 10) ? 0 : 1;

		$sql = "SELECT *
				FROM `_schet_content`
				WHERE `schet_id`=".$schet_id."
				ORDER BY `id`";
		$content = query_arr($sql);
		$tovar = _tovarValToList($content);

		$html = '<table class="_spisok">'.
					'<tr><th>№'.
						'<th>Наименование товара'.
						'<th>Кол-во'.
						'<th>Цена'.
						'<th>Сумма';
		$n = 0;
		$sum = 0;
		$arr = array();
		$avai = 2; // варианты выбора товара (из tovar-select)
		foreach($content as $id => $r) {
			if($avai == 2 && $r['tovar_id'])
				$avai = 0;
			if(($avai == 2 || !$avai) && $r['tovar_avai_id'])
				$avai = 1;
			$html .=
				'<tr><td class="n r">'.(++$n).
					'<td class="name">'.($r['tovar_id'] ? '<a href="'.URL.'&p=tovar&d=info&id='.$r['tovar_id'].'">'.$r['name'].'</a>' : $r['name']).
					'<td class="count">'.($r['tovar_avai_id'] ? '<b class="avai">'.$r['count'].' '.$tovar[$id]['tovar_measure_name'].'</b>' : $r['count']).
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
		$send['zayav_spisok'] = query_selArray($sql);

		$send['schet_id'] = $schet_id;
		$send['client_id'] = _num($schet['client_id']);
		$send['zayav_id'] = _num($schet['zayav_id']);
		$send['avai'] = $avai;
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
		$hist = _history(array('schet_id'=>$schet_id));
		$send['hist'] = _num($hist['all']);
		$send['hist_spisok'] = utf8($hist['spisok']);
		jsonSuccess($send);
		break;
	case 'schet_edit'://создание или редактирование счёта
		if(!preg_match(REGEXP_DATE, $_POST['date_create']))
			jsonError('Некорректная дата создания');

		$schet_id = _num($_POST['schet_id']);
		$client_id = _num($_POST['client_id']);
		$zayav_id = _num($_POST['zayav_id']);
		$date_create = $_POST['date_create'];
		$nakl = _bool($_POST['nakl']);
		$act = _bool($_POST['act']);

		$schet = array();
		if($schet_id) {
			if(!$schet = _schetQuery($schet_id))
				jsonError('Счёта не существует');
			$client_id = $schet['client_id'];
		}

		if($zayav_id) {
			if(!$z = _zayavQuery($zayav_id))
				jsonError();
			$client_id = $z['client_id'];
		}

		if(!$client_id || !_clientQuery($client_id))
			jsonError();


		if(empty($_POST['spisok']))
			jsonError();

		$spisok = array();
		$sum = 0;
		foreach($_POST['spisok'] as $r) {
			$r['name'] = _txt($r['name']);
			if(empty($r['name']))
				continue;
			$spisok[] = $r;
			$sum += _num($r['count']) * _cena($r['cost']);
		}

		if(empty($spisok))
			jsonError('Некорректно заполнены поля');

		$sql = "INSERT INTO `_schet` (
					`id`,
					`app_id`,
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
		query($sql);

		$insert_id = 0;//изначально считается, что не вносится новый счёт, а редактируется существующий
		if(!$schet_id) {
			$insert_id = query_insert_id('_schet');
			$schet_id = $insert_id;
		}

		$sql = "DELETE FROM `_schet_content` WHERE `schet_id`=".$schet_id;
		query($sql);

		//внесение списка наименований для счёта
		$values = array();
		foreach($spisok as $r) {
			if(empty($r['name']))
				continue;
			$values[] = "(".
				$schet_id.",".
				$r['tovar_id'].",".
				$r['tovar_avai_id'].",".
				"'".addslashes($r['name'])."',".
				$r['count'].",".
				$r['cost'].",".
				$r['readonly'].
			")";
		}

		$sql = "INSERT INTO `_schet_content` (
					`schet_id`,
					`tovar_id`,
					`tovar_avai_id`,
					`name`,
					`count`,
					`cost`,
					`readonly`
				) VALUES ".implode(',', $values);
		query($sql);

		foreach($spisok as $r) {
			if(empty($r['name']))
				continue;
			if(!$r['tovar_avai_id'])
				continue;
			_tovarAvaiUpdate($r['tovar_id']);
		}

		//начисление по счёту
		if($insert_id) {
			$sql = "INSERT INTO `_money_accrual` (
						`app_id`,
						`schet_id`,
						`client_id`,
						`zayav_id`,
						`sum`,
						`viewer_id_add`
					) VALUES (
						".APP_ID.",
						".$schet_id.",
						".$client_id.",
						".$zayav_id.",
						".$sum.",
						".VIEWER_ID."
					)";
			query($sql);

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
					  AND !`deleted`
					  AND `schet_id`=".$schet_id."
					LIMIT 1";
			$r = query_assoc($sql);

			$sql = "UPDATE `_money_accrual`
					SET `sum`=".$sum.",
						`zayav_id`=".$zayav_id."
					WHERE `id`=".$r['id'];
			query($sql);

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
		_salaryZayavBonus($zayav_id);

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
			$zayav = query_ass($sql) + array(0=>'');
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
		query($sql);

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
		query($sql);

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
				`schet_id`,
				`schet_paid_day`,
				`invoice_id`,
				`sum`,
				`client_id`,
				`zayav_id`,
				`viewer_id_add`
			) VALUES (
				".APP_ID.",
				".$schet_id.",
				'".$day."',
				".$invoice_id.",
				".$sum.",
				".$r['client_id'].",
				".$r['zayav_id'].",
				".VIEWER_ID."
			)";
		query($sql);

		$income_id = query_insert_id('_money_income');

		_schetPayCorrect($schet_id);
		_zayavBalansUpdate($r['zayav_id']);
		_salaryZayavBonus($r['zayav_id']);

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
			jsonError('Счёта не существует');

		$sql = "SELECT SUM(`sum`)
				FROM `_money_income`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `schet_id`=".$schet_id;
		if(query_value($sql))
			jsonError('По этому счёту уже производились платежи');

		$sql = "UPDATE `_schet`
				SET `deleted`=1
				WHERE `id`=".$schet_id;
		query($sql);

		//удаление начисления
		$sql = "UPDATE `_money_accrual`
				SET `deleted`=1
				WHERE `schet_id`=".$schet_id;
		query($sql);

		//отвязка картриджей от счёта
		$sql = "UPDATE `_zayav_cartridge`
				SET `schet_id`=0
				WHERE `schet_id`=".$schet_id;
		query($sql);

		//обновление наличия товара
		$sql = "SELECT *
				FROM `_schet_content`
				WHERE `schet_id`=".$schet_id;
		$q = query($sql);
		while($sc = mysql_fetch_assoc($q)) {
			if(empty($sc['name']))
				continue;
			if(!$sc['tovar_avai_id'])
				continue;
			_tovarAvaiUpdate($sc['tovar_id']);
		}

		_zayavBalansUpdate($r['zayav_id']);
		_salaryZayavBonus($r['zayav_id']);

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
		if(($visible = _ids($_POST['visible'])) == false && $_POST['visible'] != 0)
			jsonError();
		$income_confirm = _bool($_POST['income_confirm']);
		$transfer_confirm = _bool($_POST['transfer_confirm']);
		if(($income_insert = _ids($_POST['income_insert'])) == false && $_POST['income_insert'] != 0)
			jsonError();
		if(($expense_insert = _ids($_POST['expense_insert'])) == false && $_POST['expense_insert'] != 0)
			jsonError();

		if(empty($name))
			jsonError();

		$sql = "INSERT INTO `_money_invoice` (
					`app_id`,
					`name`,
					`about`,
					`visible`,
					`income_confirm`,
					`transfer_confirm`,
					`income_insert`,
					`expense_insert`
				) VALUES (
					".APP_ID.",
					'".addslashes($name)."',
					'".addslashes($about)."',
					'".$visible."',
					".$income_confirm.",
					".$transfer_confirm.",
					'".$income_insert."',
					'".$expense_insert."'
				)";
		query($sql);

		xcache_unset(CACHE_PREFIX.'invoice');
		_appJsValues();

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
		if(($visible = _ids($_POST['visible'])) == false && $_POST['visible'] != 0)
			jsonError();
		$income_confirm = _bool($_POST['income_confirm']);
		$transfer_confirm = _bool($_POST['transfer_confirm']);
		if(($income_insert = _ids($_POST['income_insert'])) == false && $_POST['income_insert'] != 0)
			jsonError();
		if(($expense_insert = _ids($_POST['expense_insert'])) == false && $_POST['expense_insert'] != 0)
			jsonError();

		if(empty($name))
			jsonError();

		$sql = "SELECT *
				FROM `_money_invoice`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted` AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$visible_old = _invoice($id, 'visible_worker');
		$income_insert_old = _invoice($id, 'income_insert_worker');
		$expense_insert_old = _invoice($id, 'expense_insert_worker');

		$sql = "UPDATE `_money_invoice`
				SET `name`='".addslashes($name)."',
					`about`='".addslashes($about)."',
					`visible`='".$visible."',
					`income_confirm`=".$income_confirm.",
					`transfer_confirm`=".$transfer_confirm.",
					`income_insert`='".$income_insert."',
					`expense_insert`='".$expense_insert."'
				WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'invoice');
		_appJsValues();

		if($changes =
			_historyChange('Наименование', $r['name'], $name).
			_historyChange('Описание', _br($r['about']), _br($about)).
			_historyChange('Видимость для сотрудников', $visible_old, _invoice($id, 'visible_worker')).
			_historyChange('Подтверждение поступления на счёт', _daNet($r['income_confirm']), _daNet($income_confirm)).
			_historyChange('Подтверждение перевода', _daNet($r['transfer_confirm']), _daNet($transfer_confirm)).
			_historyChange('Внесение платежей и возвратов', $income_insert_old, _invoice($id, 'income_insert_worker')).
			_historyChange('Внесение расходов и выдача з/п', $expense_insert_old, _invoice($id, 'expense_insert_worker')))
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
				  AND !`deleted`
				  AND `id`=".$invoice_id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_money_invoice`
				SET `start`="._invoiceBalans($invoice_id, $sum)."
				WHERE `id`=".$invoice_id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'invoice');

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
				  AND !`deleted`
				  AND `id`=".$invoice_id;
		if(!$r = query_assoc($sql))
			jsonError();

		if($r['start'] == -1)
			jsonError();

		$sql = "UPDATE `_money_invoice`
				SET `start`=-1
				WHERE `id`=".$invoice_id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'invoice');

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
				  AND !`deleted`
				  AND `id`=".$invoice_id;
		if(!$r = query_assoc($sql))
			jsonError();

		if($balans = _invoiceBalans($invoice_id)) {//перевод средств, если деньги остались на закрываемом счёте
			if(!$invoice_to)
				jsonError();

			$sql = "INSERT INTO `_money_invoice_transfer` (
						`app_id`,
						`invoice_id_from`,
						`invoice_id_to`,
						`sum`,
						`about`,
						`viewer_id_add`
					) VALUES (
						".APP_ID.",
						".$invoice_id.",
						".$invoice_to.",
						".$balans.",
						'закрыт счёт \""._invoice($invoice_id)."\"',
						".VIEWER_ID."
					)";
			query($sql);

			$insert_id = query_insert_id('_money_invoice_transfer');

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
		query($sql);

		xcache_unset(CACHE_PREFIX.'invoice');
		_appJsValues();

		_balans(array(
			'action_id' => 15,//закрытие
			'invoice_id' => $invoice_id
		));

		$send['html'] = utf8(invoice_spisok());
		jsonSuccess($send);
		break;
	case 'invoice_default':
		if(!$invoice_id = _num($_POST['invoice_id']))
			jsonError();

		$sql = "UPDATE `_vkuser`
				SET `invoice_id_default`=".$invoice_id."
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".VIEWER_ID;
		query($sql);

		xcache_unset(CACHE_PREFIX.'viewer_'.VIEWER_ID);

		jsonSuccess();
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

		$confirm = _invoice($from, 'transfer_confirm') || _invoice($to, 'transfer_confirm') ? 1 : 0;

		$sql = "INSERT INTO `_money_invoice_transfer` (
					`app_id`,
					`invoice_id_from`,
					`invoice_id_to`,
					`sum`,
					`about`,
					`confirm`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$from.",
					".$to.",
					".$sum.",
					'".addslashes($about)."',
					".$confirm.",
					".VIEWER_ID."
				)";
		query($sql);

		$insert_id = query_insert_id('_money_invoice_transfer');

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
	case 'invoice_transfer_confirm':
		if(!VIEWER_ADMIN)
			jsonError();
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_money_invoice_transfer`
				WHERE `app_id`=".APP_ID."
				  AND `confirm`=1
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_money_invoice_transfer`
				SET `confirm`=2
				WHERE `id`=".$id;
		query($sql);

		_history(array(
			'type_id' => 89,
			'v1' => _sumSpace($r['sum']),
			'v2' => _invoice($r['invoice_id_from']),
			'v3' => _invoice($r['invoice_id_to'])
		));

		$send['t'] = utf8(invoice_transfer_spisok());
		jsonSuccess($send);
		break;
	case 'invoice_transfer_edit'://редактирование комментария перевода
		if(!$id = _num($_POST['id']))
			jsonError();

		$about = _txt($_POST['about']);

		$sql = "SELECT *
				FROM `_money_invoice_transfer`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_money_invoice_transfer` SET `about`='".addslashes($about)."' WHERE `id`=".$id;
		query($sql);

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
				  AND !`deleted`
				  AND `confirm`!=2
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_money_invoice_transfer` SET `deleted`=1 WHERE `id`=".$id;

		query($sql);

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
	case 'invoice_in_add'://внесение денег на расчётный счёт
		if(!$invoice_id = _num($_POST['invoice_id']))
			jsonError();
		if(!$sum = _cena($_POST['sum']))
			jsonError();

		$about = _txt($_POST['about']);

		$sql = "INSERT INTO `_money_invoice_in` (
					`app_id`,
					`invoice_id`,
					`sum`,
					`about`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$invoice_id.",
					".$sum.",
					'".addslashes($about)."',
					".VIEWER_ID."
				)";
		query($sql);

		_balans(array(
			'action_id' => 48,
			'invoice_id' => $invoice_id,
			'sum' => $sum,
			'about' => $about
		));

		_history(array(
			'type_id' => 97,
			'invoice_id' => $invoice_id,
			'v1' => _sumSpace($sum),
			'v2' => $about
		));

		$send['i'] = utf8(invoice_spisok());
		$send['io'] = utf8(invoice_inout_spisok());
		jsonSuccess($send);
		break;
	case 'invoice_in_del'://удаление внесения денег
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_money_invoice_in`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_money_invoice_in`
				SET `deleted`=1,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;

		query($sql);

		_balans(array(
			'action_id' => 49,
			'invoice_id' => $r['invoice_id'],
			'sum' => $r['sum'],
			'about' => $r['about']
		));

		_history(array(
			'type_id' => 98,
			'invoice_id' => $r['invoice_id'],
			'v1' => _sumSpace($r['sum']),
			'v2' => $r['about']
		));

		jsonSuccess();
		break;
	case 'invoice_out_add'://вывод денег с расчётного счёта
		if(!$invoice_id = _num($_POST['invoice_id']))
			jsonError();
		if(!$sum = _cena($_POST['sum']))
			jsonError();
		if(!$worker_id = _num($_POST['worker_id']))
			jsonError();

		$about = _txt($_POST['about']);

		$sql = "INSERT INTO `_money_invoice_out` (
					`app_id`,
					`invoice_id`,
					`sum`,
					`worker_id`,
					`about`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$invoice_id.",
					".$sum.",
					".$worker_id.",
					'".addslashes($about)."',
					".VIEWER_ID."
				)";
		query($sql);

		_balans(array(
			'action_id' => 50,
			'invoice_id' => $invoice_id,
			'sum' => $sum,
			'about' => $about
		));
		_history(array(
			'type_id' => 99,
			'invoice_id' => $invoice_id,
			'worker_id' => $worker_id,
			'v1' => _sumSpace($sum),
			'v2' => $about
		));

		$send['i'] = utf8(invoice_spisok());
		$send['io'] = utf8(invoice_inout_spisok());
		jsonSuccess($send);
		break;
	case 'invoice_out_del'://удаление вывода денег
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_money_invoice_out`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_money_invoice_out`
				SET `deleted`=1,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;

		query($sql);

		_balans(array(
			'action_id' => 51,
			'invoice_id' => $r['invoice_id'],
			'sum' => $r['sum'],
			'about' => $r['about']
		));

		_history(array(
			'type_id' => 100,
			'invoice_id' => $r['invoice_id'],
			'worker_id' => $r['worker_id'],
			'v1' => _sumSpace($r['sum']),
			'v2' => $r['about']
		));

		jsonSuccess();
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
				  AND `viewer_id`=".$worker_id;
		query($sql);

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
				  AND `viewer_id`=".$worker_id;
		query($sql);

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
					`worker_id`,
					`sum`,
					`about`,
					`mon`,
					`year`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$worker_id.",
					".$sum.",
					'".addslashes($about)."',
					".$mon.",
					".$year.",
					".VIEWER_ID."
				)";
		query($sql);

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
				  AND !`salary_list_id`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "DELETE FROM `_salary_accrual` WHERE `id`=".$id;
		query($sql);

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
					`worker_id`,
					`sum`,
					`about`,
					`year`,
					`mon`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$worker_id.",
					".$sum.",
					'".addslashes($about)."',
					".$year.",
					".$mon.",
					".VIEWER_ID."
				)";
		query($sql);

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
				  AND !`salary_list_id`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "DELETE FROM `_salary_deduct` WHERE `id`=".$id;
		query($sql);

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
					  AND `salary_list_id`
					  AND `id` IN (".$accrual_ids.")";
			if(query_value($sql))
				jsonError('Некоторые произвольные начисления уже были внесены');

			$sql = "SELECT IFNULL(SUM(`sum`), 0)
					FROM `_salary_accrual`
					WHERE `app_id`=".APP_ID."
					  AND `id` IN (".$accrual_ids.")";
			$accrual_sum = query_value($sql);
		}
		if($deduct_ids) {
			$sql = "SELECT COUNT(*)
					FROM `_salary_deduct`
					WHERE `app_id`=".APP_ID."
					  AND `salary_list_id`
					  AND `id` IN (".$deduct_ids.")";
			if(query_value($sql))
				jsonError('Некоторые вычеты уже были внесены');

			$sql = "SELECT IFNULL(SUM(`sum`), 0)
					FROM `_salary_deduct`
					WHERE `app_id`=".APP_ID."
					  AND `id` IN (".$deduct_ids.")";
			$deduct_sum = query_value($sql);
		}
		if($expense_ids) {
			$sql = "SELECT COUNT(*)
					FROM `_zayav_expense`
					WHERE `app_id`=".APP_ID."
					  AND `salary_list_id`
					  AND `id` IN (".$expense_ids.")";
			if(query_value($sql))
				jsonError('Некоторые начисления расходов по заявке уже были внесены');

			$sql = "SELECT IFNULL(SUM(`sum`), 0)
					FROM `_zayav_expense`
					WHERE `app_id`=".APP_ID."
					  AND `id` IN (".$expense_ids.")";
			$expense_sum = query_value($sql);
		}

		//Общая сумма всех начислений
		$sum = $accrual_sum - $deduct_sum + $expense_sum;
		
		//Внесение листа выдачи
		$sql = "INSERT INTO `_salary_list` (
					`app_id`,
					`nomer`,
					`worker_id`,
					`sum`,
					`year`,
					`mon`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					"._maxSql('_salary_list', 'nomer', 1).",
					".$worker_id.",
					".$sum.",
					'".$year."',
					'".$mon."',
					".VIEWER_ID."
				)";
		query($sql);

		$insert_id = query_insert_id('_salary_list');

		//Привязка листа выдачи к начислениям
		if($accrual_ids) {
			$sql = "UPDATE `_salary_accrual`
					SET `salary_list_id`=".$insert_id."
					WHERE `id` IN (".$accrual_ids.")";
			query($sql);
		}
		if($deduct_ids) {
			$sql = "UPDATE `_salary_deduct`
					SET `salary_list_id`=".$insert_id."
					WHERE `id` IN (".$deduct_ids.")";
			query($sql);
		}
		if($expense_ids) {
			$sql = "UPDATE `_zayav_expense`
					SET `salary_list_id`=".$insert_id."
					WHERE `id` IN (".$expense_ids.")";
			query($sql);
		}

		//Привязка листа выдачи к авансам
		$sql = "UPDATE `_money_expense`
				SET `salary_list_id`=".$insert_id.",
					`salary_avans`=1
				WHERE `app_id`=".APP_ID."
				  AND `worker_id`=".$worker_id."
				  AND `year`=".$year."
				  AND `mon`=".$mon."
				  AND !`deleted`
				  AND !`salary_list_id`";
		query($sql);

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
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

//		if(TODAY != substr($r['dtime_add'], 0, 10))
//			jsonError();

		$sql = "SELECT COUNT(`id`)
				FROM `_money_expense`
				WHERE !`deleted`
				  AND !`salary_avans`
				  AND `salary_list_id`=".$id;
		if(query_value($sql))
			jsonError('По листу выдачи уже произведена оплата');

		$sql = "UPDATE `_salary_accrual` SET `salary_list_id`=0 WHERE `salary_list_id`=".$id;
		query($sql);

		$sql = "UPDATE `_salary_deduct` SET `salary_list_id`=0 WHERE `salary_list_id`=".$id;
		query($sql);

		$sql = "UPDATE `_zayav_expense` SET `salary_list_id`=0 WHERE `salary_list_id`=".$id;
		query($sql);

		$sql = "UPDATE `_money_expense` SET `salary_list_id`=0 WHERE `salary_list_id`=".$id;
		query($sql);

		$sql = "DELETE FROM `_salary_list` WHERE `id`=".$id;
		query($sql);

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
				  AND `deleted`";
		if($ids = query_ids($sql)) {
			$sql = "DELETE FROM `_zayav_expense`
					WHERE `worker_id`=".$worker_id."
					  AND `zayav_id` IN (".$ids.")";
			query($sql);
			if(mysql_affected_rows())
				$changes .= '<tr><td>Удалены начисления из удалённых заявок:<td>'.mysql_affected_rows();
		}

		//список всех заявок, у которых есть долг
		$sql = "SELECT `id`
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `sum_dolg`<0";
		$ids = query_ids($sql);

		if($onPay) {
			//перенос начислений по заявкам, у которых есть долги, в неактивный список
			$sql = "UPDATE `_zayav_expense`
					SET `year`=0,
						`mon`=0
					WHERE `app_id`=".APP_ID."
					  AND `worker_id`=".$worker_id."
					  AND !`salary_list_id`
					  AND `year`
					  AND `mon`
					  AND `zayav_id` IN (".$ids.")";
			query($sql);
			if(mysql_affected_rows())
				$changes .= '<tr><td>Перенесены начисления по заявкам<br />в неактивный список, у которых есть долги:<td>'.mysql_affected_rows();
		}

		//id заявкок из неактивного списка
		$sql = "SELECT `zayav_id`
				FROM `_zayav_expense`
				WHERE `app_id`=".APP_ID."
				  AND `worker_id`=".$worker_id."
				  AND (!`year` OR !`mon`)";
		if($ids = query_ids($sql)) {
			$rec = 0;//количество восстановленных начислений
			//заявки, у которых нет долгов, уходят из неактивного списка в любом случае
			$sql = "SELECT `id`
					FROM `_zayav`
					WHERE `app_id`=".APP_ID."
					  AND !`deleted`
					  AND `id` IN (".$ids.")
					  AND `sum_accrual`-`sum_pay`<=0";
			if($zayav_ids = query_ids($sql)) {
				$sql = "UPDATE `_zayav_expense`
						SET `year`=".strftime('%Y').",
							`mon`=".strftime('%m')."
						WHERE `worker_id`=".$worker_id."
						  AND `zayav_id` IN (".$zayav_ids.")";
				query($sql);
				$rec = mysql_affected_rows();
			}

			if(!$onPay) {//перенос начислений по заявкам из неактивного списка в текущий месяц (галочка не установлена)
				$sql = "UPDATE `_zayav_expense`
						SET `year`=".strftime('%Y').",
							`mon`=".strftime('%m')."
						WHERE `worker_id`=".$worker_id."
						  AND `zayav_id` IN (".$ids.")";
				query($sql);
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
