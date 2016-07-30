<?php
switch(@$_POST['op']) {
	case 'pin_enter':
		unset($_SESSION[PIN_TIME_KEY]);

		$key = CACHE_PREFIX.'pin_enter_count'.VIEWER_ID;
		$count = xcache_get($key);
		if(empty($count))
			$count = 0;
		if($count > 4) {
			$send = array(
				'max' => 1,
				'text' => utf8('Превышено максимальное количество попыток ввода.<br />'.
					'Продолжить ввод можно будет через 30 минут.<br /><br />'.
					'Если вы забыли свой пин-код, обратитесь к руководителю для его сброса.')
			);
			jsonError($send);
		}
		xcache_set($key, ++$count, 1800);

		$pin = _txt($_POST['pin']);

		$sql = "SELECT COUNT(*)
				FROM `_vkuser`
				WHERE `app_id`=".APP_ID."
				  AND `pin`='".$pin."'
				  AND `viewer_id`=".VIEWER_ID;
		if(!query_value($sql))
			jsonError('Неверный пин-код');

		xcache_unset($key);
		$_SESSION[PIN_TIME_KEY] = time() + PIN_TIME_LEN;

		jsonSuccess();
		break;

	case 'setup_my_pinset':
		$pin = _txt(@$_POST['pin']);
		if(PIN || !$pin || strlen($pin) < 3 || strlen($pin) > 10)
			jsonError('Некорректная длина пинкода');

		$sql = "UPDATE `_vkuser`
				SET `pin`='".addslashes($pin)."'
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".VIEWER_ID;
		query($sql);

		xcache_unset(CACHE_PREFIX.'viewer_'.VIEWER_ID);
		unset($_SESSION[PIN_TIME_KEY]);

		jsonSuccess();
		break;
	case 'setup_my_pinchange':
		if(!PIN)
			jsonError();

		$oldpin = _txt($_POST['oldpin']);
		$pin = _txt($_POST['pin']);

		if(!$oldpin || strlen($oldpin) < 3 || strlen($oldpin) > 10)
			jsonError();
		if(!$pin || strlen($pin) < 3 || strlen($pin) > 10)
			jsonError();
		if(_viewer(VIEWER_ID, 'pin') != $oldpin)
			jsonError('Неверный старый пин-код');

		$sql = "UPDATE `_vkuser`
				SET `pin`='".addslashes($pin)."'
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".VIEWER_ID;
		query($sql);

		xcache_unset(CACHE_PREFIX.'viewer_'.VIEWER_ID);
		unset($_SESSION[PIN_TIME_KEY]);

		jsonSuccess();
		break;
	case 'setup_my_pindel':
		if(!PIN)
			jsonError();

		$oldpin = _txt($_POST['oldpin']);

		if(_viewer(VIEWER_ID, 'pin') != $oldpin)
			jsonError('Неверный старый пин-код');

		$sql = "UPDATE `_vkuser`
				SET `pin`=''
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".VIEWER_ID;
		query($sql);

		xcache_unset(CACHE_PREFIX.'viewer_'.VIEWER_ID);
		unset($_SESSION[PIN_TIME_KEY]);

		jsonSuccess();
		break;

	case 'setup_worker_add':
		if(!RULE_SETUP_WORKER)
			jsonError();

		$viewer_id = _num($_POST['viewer_id']);

		if($viewer_id) {
			$sql = "SELECT *
					FROM `_vkuser`
					WHERE `app_id`=".APP_ID."
					  AND `viewer_id`=".$viewer_id;
			if($r = query_assoc($sql)) {
				if($r['worker'])
					jsonError('Этот пользователь уже является сотрудником Вашей организации');
			}

			_viewer($viewer_id);
			$sql = "UPDATE `_vkuser`
					SET `worker`=1
					WHERE `app_id`=".APP_ID."
					  AND `viewer_id`=".$viewer_id;
			query($sql);

			xcache_unset(CACHE_PREFIX.'viewer_'.$viewer_id);
		} else {
			$first_name = _txt($_POST['first_name']);
			$last_name = _txt($_POST['last_name']);
			$sex = _num($_POST['sex']);
			$post = _txt($_POST['post']);

			if(!$first_name || !$last_name)
				jsonError();

			$viewer_id = _maxSql('_vkuser', 'viewer_id');
			if($viewer_id < VIEWER_MAX)
				$viewer_id = VIEWER_MAX;

			$sql = "INSERT INTO `_vkuser` (
						`app_id`,
						`viewer_id`,
						`first_name`,
						`last_name`,
						`sex`,
						`post`,
						`photo`,
						`worker`
					) VALUES (
						".APP_ID.",
						".$viewer_id.",
						'".addslashes($first_name)."',
						'".addslashes($last_name)."',
						".$sex.",
						'".addslashes($post)."',
						'http://vk.com/images/camera_c.gif',
						1
					)";
			query($sql);
		}

		_appJsValues();

		_history(array(
			'type_id' => 1024,
			'worker_id' => $viewer_id
		));


		$send['html'] = utf8(setup_worker_spisok());
		jsonSuccess($send);
		break;
	case 'setup_worker_del':
		if(!RULE_SETUP_WORKER)
			jsonError();

		if(!$viewer_id = _num($_POST['id']))
			jsonError();

		$u = _viewer($viewer_id);
		if($u['viewer_admin'])
			jsonError('Сотрудник является руководителем');

		if(!$u['viewer_worker'])
			jsonError('Пользователь уже не является сотрудником');

		$sql = "UPDATE `_vkuser`
				SET `worker`=0
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$viewer_id;
		query($sql);

		$sql = "DELETE FROM `_vkuser_rule`
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$viewer_id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'viewer_'.$viewer_id);
		_appJsValues();

		_history(array(
			'type_id' => 1025,
			'worker_id' => $viewer_id
		));

		jsonSuccess();
		break;
	case 'setup_worker_save':
		if(!RULE_SETUP_WORKER)
			jsonError();

		if(!$viewer_id = _num($_POST['viewer_id']))
			jsonError();

		$u = _viewer($viewer_id);

		$first_name = _txt($_POST['first_name']);
		$last_name = _txt($_POST['last_name']);
		$middle_name = _txt($_POST['middle_name']);
		$post = _txt($_POST['post']);

		if(!$first_name || !$last_name)
			jsonError();

		$sql = "UPDATE `_vkuser`
				SET `first_name`='".addslashes($first_name)."',
					`last_name`='".addslashes($last_name)."',
					`middle_name`='".addslashes($middle_name)."',
			        `post`='".addslashes($post)."'
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$viewer_id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'viewer_'.$viewer_id);

		$changes =
			_historyChange('Имя', $u['viewer_first_name'], $first_name).
			_historyChange('Фамилия', $u['viewer_last_name'], $last_name).
			_historyChange('Отчество', $u['viewer_middle_name'], $middle_name).
			_historyChange('Должность', $u['viewer_post'], $post);

		if($changes)
			_history(array(
				'type_id' => 1001,
				'worker_id' => $viewer_id,
				'v1' => '<table>'.$changes.'</table>'
			));

		jsonSuccess();
		break;
	case 'setup_worker_pin_clear':
		if(!VIEWER_ADMIN)
			jsonError();
		if(!RULE_SETUP_WORKER)
			jsonError();
		if(!$viewer_id = _num($_POST['viewer_id']))
			jsonError();

		$u = _viewer($viewer_id);
		if(!$u['pin'])
			jsonError();

		$sql = "UPDATE `_vkuser`
				SET `pin`=''
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$viewer_id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'viewer_'.$viewer_id);

		_history(array(
			'type_id' => 1018,
			'worker_id' => $viewer_id
		));

		jsonSuccess();
		break;

	case 'RULE_SALARY_SHOW'://Показывать в списке з/п сотрудников
		$_POST['h1'] = 1036;
		$_POST['h0'] = 1035;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;
	case 'RULE_EXECUTER'://может быть исполнителем
		if(!RULE_SETUP_RULES)
			jsonError();

		if(!$viewer_id = _num($_POST['viewer_id']))
			jsonError();

		$new = _num($_POST['v']);

		$old = _viewerRule($viewer_id, 'RULE_EXECUTER');

		if($old == $new)
			jsonError();

		_workerRuleQuery($viewer_id, 'RULE_EXECUTER', $new);
		_appJsValues();

		_history(array(
			'type_id' => 1012,
			'worker_id' => $viewer_id,
			'v1' => '<table>'.
						_historyChange('Может быть исполнителем заявок', _daNet($old), _daNet($new)).
					'</table>'
		));

		jsonSuccess();
		break;
	case 'RULE_SALARY_ZAYAV_ON_PAY'://Начислять з/п по заявке при отсутствии долга
		$_POST['h1'] = 1046;
		$_POST['h0'] = 1047;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;
	case 'RULE_SALARY_BONUS'://Начисления бонусов
		$_POST['h1'] = 1043;
		$_POST['h0'] = 1044;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;
	case 'salary_bonus_sum'://установка баланса сотрудника
		if(!$worker_id = _num($_POST['worker_id']))
			jsonError();

		if(!$sum = _cena($_POST['sum']))
			jsonError('Некорректно введено знаяение');

		if(!$r = _viewerWorkerQuery($worker_id))
			jsonError('Сотрудника не существует');

		if($r['salary_bonus_sum'] == $sum)
			jsonError('Значение не было изменено');

		$sql = "UPDATE `_vkuser`
				SET `salary_bonus_sum`=".$sum."
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$worker_id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'viewer_'.$worker_id);

		_history(array(
			'type_id' => 1045,
			'worker_id' => $worker_id,
			'v1' => $sum
		));

		jsonSuccess();
		break;
	case 'RULE_APP_ENTER'://разрешать сотруднику вход в приложение
		$_POST['h1'] = 1002;
		$_POST['h0'] = 1003;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;

	case 'setup_menu_access':
		if(!RULE_SETUP_RULES)
			jsonError();

		if(!$viewer_id = _num($_POST['viewer_id']))
			jsonError();
	
		$menu_id = _num($_POST['menu_id']);
		$v = _bool($_POST['v']);

		if(_viewerMenuAccess($viewer_id, $menu_id) == $v)
			jsonError();

		$sql = "UPDATE `_menu_viewer`
				SET `access`=".$v."
				WHERE `app_id`=".APP_ID."
				  AND `menu_id`=".$menu_id."
				  AND `viewer_id`=".$viewer_id;
		query($sql);
		
		xcache_unset(CACHE_PREFIX.'viewer_menu_access_'.$viewer_id);

		jsonSuccess();
		break;
	
	case 'RULE_SETUP_WORKER'://разрешать сотруднику изменять данные сотрудника
		$_POST['h1'] = 1004;
		$_POST['h0'] = 1005;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		_workerRuleQuery($_POST['viewer_id'], 'RULE_SETUP_RULES', 0);

		jsonSuccess();
		break;

	case 'RULE_ZAYAV_EXECUTER'://Видит только те заявки, в которых является исполнителем
//		$_POST['h1'] = 1004;
//		$_POST['h0'] = 1005;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;
	case 'RULE_SETUP_RULES'://разрешать сотруднику настраивать права других сотрудников
		$_POST['h1'] = 1006;
		$_POST['h0'] = 1007;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;
	case 'RULE_SETUP_REKVISIT'://разрешать сотруднику изменять реквизиты организации
		$_POST['h1'] = 1008;
		$_POST['h0'] = 1009;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;
	case 'RULE_SETUP_INVOICE'://разрешать сотруднику управлять расчётными счетами
		$_POST['h1'] = 1010;
		$_POST['h0'] = 1011;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;
	case 'RULE_HISTORY_VIEW'://видит историю действий
		if(!RULE_SETUP_RULES)
			jsonError();

		if(!$viewer_id = _num($_POST['viewer_id']))
			jsonError();

		$new = _num($_POST['v']);

		$old = _viewerRule($viewer_id, 'RULE_HISTORY_VIEW');

		if($old == $new)
			jsonError();

		_workerRuleQuery($viewer_id, 'RULE_HISTORY_VIEW', $new);

		_history(array(
			'type_id' => 1012,
			'worker_id' => $viewer_id,
			'v1' => '<table>'.
						_historyChange('Видит историю действий', _ruleHistoryView($old), _ruleHistoryView($new)).
					'</table>'
		));

		jsonSuccess();
		break;
	case 'setup_history_view_worker_all'://видимость истории действий для всех сотрудников
		if(!RULE_SETUP_RULES)
			jsonError();

		if(!$values = $_POST['v'])
			jsonError();

		$changes = '';

		foreach(explode(',', $values) as $v) {
			$ex = explode(':', $v);


			if(!$viewer_id = _num($ex[0]))
				jsonError();
			if(!preg_match(REGEXP_NUMERIC, $ex[1]) || $ex[1] > 2)
				jsonError();

			$new = _num($ex[1]);
			$old = _viewerRule($viewer_id, 'RULE_HISTORY_VIEW');

			if($old == $new)
				continue;

			_workerRuleQuery($viewer_id, 'RULE_HISTORY_VIEW', $new);

			$changes .= _historyChange(_viewer($viewer_id, 'viewer_name'), _ruleHistoryView($old), _ruleHistoryView($new));
		}

		if($changes)
			_history(array(
				'type_id' => 1013,
				'v1' => '<table>'.$changes.'</table>'
			));

		jsonSuccess();
		break;
	case 'RULE_INVOICE_HISTORY'://разрешать сотруднику видеть историю расчётных счетов
		$_POST['h1'] = 1014;
		$_POST['h0'] = 1015;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;
	case 'RULE_INVOICE_TRANSFER'://разрешать сотруднику видеть историю переводов по расчётным счетам
		if(!RULE_SETUP_RULES)
			jsonError();

		if(!$viewer_id = _num($_POST['viewer_id']))
			jsonError();

		$new = _num($_POST['v']);

		$old = _viewerRule($viewer_id, 'RULE_INVOICE_TRANSFER');

		if($old == $new)
			jsonError();

		_workerRuleQuery($viewer_id, 'RULE_INVOICE_TRANSFER', $new);

		_history(array(
			'type_id' => 1012,
			'worker_id' => $viewer_id,
			'v1' => '<table>'.
						_historyChange('Видит переводы по расчётным счетам ', _ruleHistoryView($old), _ruleHistoryView($new)).
					'</table>'
		));

		jsonSuccess();
		break;
	case 'RULE_INCOME_VIEW'://разрешать сотруднику видеть список платежей
		$_POST['h1'] = 1016;
		$_POST['h0'] = 1017;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;
	case 'RULE_SETUP_ZAYAV_STATUS'://разрешать сотруднику настраивать статусы заявок
		if(!RULE_SETUP_RULES)
			jsonError();

		if(!$viewer_id = _num($_POST['viewer_id']))
			jsonError();

		$new = _num($_POST['v']);

		$old = _viewerRule($viewer_id, 'RULE_SETUP_ZAYAV_STATUS');

		if($old == $new)
			jsonError();

		_workerRuleQuery($viewer_id, 'RULE_SETUP_ZAYAV_STATUS', $new);

		_history(array(
			'type_id' => 1012,
			'worker_id' => $viewer_id,
			'v1' => '<table>'.
						_historyChange('Настройка статусов заявок', _daNet($old), _daNet($new)).
					'</table>'
		));

		jsonSuccess();
		break;

	case 'RULE_MY_PAY_SHOW_PERIOD'://мои настройки: показывать платежи: за день, неделю, месяц
		_workerRuleQuery(VIEWER_ID, 'RULE_MY_PAY_SHOW_PERIOD', _num($_POST['v']));

		xcache_unset(CACHE_PREFIX.'viewer_'.VIEWER_ID);
		xcache_unset(CACHE_PREFIX.'viewer_rule_'.VIEWER_ID);

		jsonSuccess();
		break;

	case 'setup_rekvisit':
		if(!RULE_SETUP_REKVISIT)
			jsonError();

		$type_id = _num($_POST['type_id']);
		$name = _txt($_POST['name']);
		$name_yur = _txt($_POST['name_yur']);
		$ogrn = _txt($_POST['ogrn']);
		$inn = _txt($_POST['inn']);
		$kpp = _txt($_POST['kpp']);
		$phone = _txt($_POST['phone']);
		$fax = _txt($_POST['fax']);
		$adres_yur = _txt($_POST['adres_yur']);
		$adres_ofice = _txt($_POST['adres_ofice']);
		$time_work = _txt($_POST['time_work']);
		$bank_name = _txt($_POST['bank_name']);
		$bank_bik = _txt($_POST['bank_bik']);
		$bank_account = _txt($_POST['bank_account']);
		$bank_account_corr = _txt($_POST['bank_account_corr']);

		$sql = "SELECT *
				FROM `_app`
				WHERE `id`=".APP_ID;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_app`
				SET `type_id`=".$type_id.",
					`name`='".addslashes($name)."',
					`name_yur`='".addslashes($name_yur)."',
					`ogrn`='".addslashes($ogrn)."',
					`inn`='".addslashes($inn)."',
					`kpp`='".addslashes($kpp)."',
					`phone`='".addslashes($phone)."',
					`fax`='".addslashes($fax)."',
					`adres_yur`='".addslashes($adres_yur)."',
					`adres_ofice`='".addslashes($adres_ofice)."',
					`time_work`='".addslashes($time_work)."',
					`bank_name`='".addslashes($bank_name)."',
					`bank_bik`='".addslashes($bank_bik)."',
					`bank_account`='".addslashes($bank_account)."',
					`bank_account_corr`='".addslashes($bank_account_corr)."'
				WHERE `id`=".APP_ID;
		query($sql);

		$changes =
			_historyChange('Вид организации', _appType($r['type_id']), _appType($type_id)).
			_historyChange('Название организации', $r['name'], $name).
			_historyChange('Наименование юридического лица', $r['name_yur'], $name_yur).
			_historyChange('ОГРН', $r['ogrn'], $ogrn).
			_historyChange('ИНН', $r['inn'], $inn).
			_historyChange('КПП', $r['kpp'], $kpp).
			_historyChange('Контактные телефоны', $r['phone'], $phone).
			_historyChange('Факс', $r['fax'], $fax).
			_historyChange('Юридический адрес', $r['adres_yur'], $adres_yur).
			_historyChange('Адрес офиса', $r['adres_ofice'], $adres_ofice).
			_historyChange('Режим работы', $r['time_work'], $time_work).
			_historyChange('Наименование банка', $r['bank_name'], $bank_name).
			_historyChange('БИК', $r['bank_bik'], $bank_bik).
			_historyChange('Расчётный счёт', $r['bank_account'], $bank_account).
			_historyChange('Корреспондентский счёт', $r['bank_account_corr'], $bank_account_corr);

		if($changes)
			_history(array(
				'type_id' => 1026,
				'v1' => '<table>'.$changes.'</table>'
			));

		jsonSuccess();
		break;

	case 'setup_service_toggle':
		if(!VIEWER_ADMIN)
			jsonError();

		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_zayav_service`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "SELECT COUNT(*)
				FROM `_zayav_service_use`
				WHERE `app_id`=".APP_ID."
				  AND `service_id`=".$id;
		if($active = !query_value($sql)) {
			$sql = "INSERT INTO `_zayav_service_use` (
						`app_id`,
						`service_id`
					) VALUES (
						".APP_ID.",
						".$id."
					)";
		} else {
			$sql = "DELETE FROM `_zayav_service_use`
					WHERE `app_id`=".APP_ID."
					  AND `service_id`=".$id;
		}

		query($sql);

		xcache_unset(CACHE_PREFIX.'service'.APP_ID);
		_appJsValues();

		$send['on'] = $active;
		jsonSuccess($send);
		break;
	case 'setup_service_edit':
		if(!SA)
			jsonError();

		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);
		$head = _txt($_POST['head']);
		$about = win1251($_POST['about']);

		$sql = "SELECT *
				FROM `_zayav_service`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_zayav_service`
				SET `name`='".addslashes($name)."',
					`head`='".addslashes($head)."',
					`about`='".addslashes($about)."'
				WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'service'.APP_ID);
		_appJsValues();

		jsonSuccess();
		break;

	case 'expense_category_add':
		$name = _txt($_POST['name']);

		if(empty($name))
			jsonError();

		$sql = "INSERT INTO `_money_expense_category` (
					`app_id`,
					`name`,
					`sort`
				) VALUES (
					".APP_ID.",
					'".addslashes($name)."',
					"._maxSql('_money_expense_category')."
				)";
		query($sql);

		xcache_unset(CACHE_PREFIX.'expense'.APP_ID);
		_appJsValues();

		_history(array(
			'type_id' => 1019,
			'v1' => $name
		));

		$send['html'] = utf8(setup_expense_spisok());
		jsonSuccess($send);
		break;
	case 'expense_category_edit':
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);


		if(empty($name))
			jsonError();

		$sql = "SELECT *
				FROM `_money_expense_category`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_money_expense_category`
				SET `name`='".addslashes($name)."'
				WHERE `id`=".$id;
		query($sql);

		$changes =
			_historyChange('Наименование', $r['name'], $name);

		if($changes)
			_history(array(
				'type_id' => 1021,
				'v1' => $name,
				'v2' => '<table>'.$changes.'</table>'
			));

		if($category_id = _num($_POST['category_id'])) {
			$category_sub_id = _num($_POST['category_sub_id']);

			//если не указана подкатегория, но в категории есть подкатегории, объединение не производится
			if(!$category_sub_id && _expense($category_id, 'sub'))
				jsonError();

			$sql = "UPDATE `_money_expense`
					SET `category_id`=".$id.",
						`category_sub_id`=0
					WHERE `category_id`=".$category_id."
					  AND `category_sub_id`=".$category_sub_id;
			query($sql);

			_history(array(
				'type_id' => 1051,
				'v1' => _expense($id),
				'v2' => _expense($category_id).
						($category_sub_id ? ': '._expenseSub($category_sub_id) : '')
			));

			if($category_sub_id) {
				$sql = "DELETE FROM `_money_expense_category_sub` WHERE `id`=".$category_sub_id;
				query($sql);
			} else {
				$sql = "DELETE FROM `_money_expense_category` WHERE `id`=".$category_id;
				query($sql);
			}

		}

		xcache_unset(CACHE_PREFIX.'expense'.APP_ID);
		_appJsValues();

		$send['html'] = utf8(setup_expense_spisok());
		jsonSuccess($send);
		break;
	case 'expense_category_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_money_expense_category`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "SELECT COUNT(`id`)
				FROM `_money_expense`
				WHERE `app_id`=".APP_ID."
				  AND `category_id`=".$id;
		if(query_value($sql))
			jsonError();

		$sql = "DELETE FROM `_money_expense_category` WHERE `id`=".$id;
		query($sql);

		$sql = "DELETE FROM `_money_expense_category_sub` WHERE `category_id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'expense'.APP_ID);
		_appJsValues();

		_history(array(
			'type_id' => 1020,
			'v1' => $r['name']
		));

		$send['html'] = utf8(setup_expense_spisok());
		jsonSuccess($send);
		break;

	case 'expense_category_sub_add':
		if(!$category_id = _num($_POST['category_id']))
			jsonError();

		$name = _txt($_POST['name']);

		if(empty($name))
			jsonError();

		$sql = "SELECT *
				FROM `_money_expense_category`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$category_id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "INSERT INTO `_money_expense_category_sub` (
					`app_id`,
					`category_id`,
					`name`
				) VALUES (
					".APP_ID.",
					".$category_id.",
					'".addslashes($name)."'
				)";
		query($sql);

		xcache_unset(CACHE_PREFIX.'expense_sub'.APP_ID);
		_appJsValues();

		_history(array(
			'type_id' => 1048,
			'v1' => _expense($category_id),
			'v2' => $name
		));

		$send['html'] = utf8(setup_expense_sub_spisok($category_id));
		jsonSuccess($send);
		break;
	case 'expense_category_sub_edit':
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);

		if(empty($name))
			jsonError();

		$sql = "SELECT *
				FROM `_money_expense_category_sub`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_money_expense_category_sub`
				SET `name`='".addslashes($name)."'
				WHERE `id`=".$id;
		query($sql);

		if($changes =
			_historyChange('Наименование', $r['name'], $name))
			_history(array(
				'type_id' => 1049,
				'v1' => _expense($r['category_id']),
				'v2' => '<table>'.$changes.'</table>'
			));

		if($category_id = _num($_POST['category_id_join'])) {
			$category_sub_id = _num($_POST['category_sub_id_join']);

			//если не указана подкатегория, но в категории есть подкатегории, объединение не производится
			if(!$category_sub_id && _expense($category_id, 'sub'))
				jsonError();

			$sql = "UPDATE `_money_expense`
					SET `category_id`=".$r['category_id'].",
						`category_sub_id`=".$id."
					WHERE `category_id`=".$category_id."
					  AND `category_sub_id`=".$category_sub_id;
			query($sql);

			_history(array(
				'type_id' => 1051,
				'v1' => _expense($r['category_id']).': '._expenseSub($id),
				'v2' => _expense($category_id).
						($category_sub_id ? ': '._expenseSub($category_sub_id) : '')
			));

			if($category_sub_id) {
				$sql = "DELETE FROM `_money_expense_category_sub` WHERE `id`=".$category_sub_id;
				query($sql);
			} else {
				$sql = "DELETE FROM `_money_expense_category` WHERE `id`=".$category_id;
				query($sql);
			}
		}

		xcache_unset(CACHE_PREFIX.'expense_sub'.APP_ID);
		_appJsValues();

		$send['html'] = utf8(setup_expense_sub_spisok($r['category_id']));
		jsonSuccess($send);
		break;
	case 'expense_category_sub_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_money_expense_category_sub`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "SELECT COUNT(`id`)
				FROM `_money_expense`
				WHERE `app_id`=".APP_ID."
				  AND `category_sub_id`=".$id;
		if(query_value($sql))
			jsonError();

		$sql = "DELETE FROM `_money_expense_category_sub` WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'expense_sub'.APP_ID);
		_appJsValues();

		_history(array(
			'type_id' => 1050,
			'v1' => _expense($r['category_id']),
			'v2' => $r['name']
		));

		$send['html'] = utf8(setup_expense_sub_spisok($r['category_id']));
		jsonSuccess($send);
		break;

	case 'setup_rubric_add':
		$name = _txt($_POST['name']);

		if(empty($name))
			jsonError();

		$sql = "INSERT INTO `_setup_rubric` (
					`app_id`,
					`name`,
					`sort`
				) VALUES (
					".APP_ID.",
					'".addslashes($name)."',
					"._maxSql('_setup_rubric', 'sort')."
				)";
		query($sql);

		xcache_unset(CACHE_PREFIX.'rubric'.APP_ID);
		_appJsValues();

		_history(array(
			'type_id' => 1057,
			'v1' => $name
		));

		$send['html'] = utf8(setup_rubric_spisok());
		jsonSuccess($send);
		break;
	case 'setup_rubric_edit':
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);

		if(empty($name))
			jsonError();

		$sql = "SELECT *
				FROM `_setup_rubric`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_setup_rubric`
				SET `name`='".addslashes($name)."'
				WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'rubric'.APP_ID);
		_appJsValues();

		if($changes =
			_historyChange('Наименование', $r['name'], $name))
			_history(array(
				'type_id' => 1058,
				'v1' => $name,
				'v2' => '<table>'.$changes.'</table>'
			));

		$send['html'] = utf8(setup_rubric_spisok());
		jsonSuccess($send);
		break;
	case 'setup_rubric_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_setup_rubric`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "SELECT COUNT(`id`) FROM `_setup_rubric_sub` WHERE `rubric_id`=".$id;
		if(query_value($sql))
			jsonError();

		$sql = "SELECT COUNT(`id`) FROM `_zayav` WHERE `rubric_id`=".$id;
		if(query_value($sql))
			jsonError();

		$sql = "DELETE FROM `_setup_rubric` WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'rubric'.APP_ID);
		_appJsValues();

		_history(array(
			'type_id' => 1059,
			'v1' => $r['name']
		));

		$send['html'] = utf8(setup_rubric_spisok());
		jsonSuccess($send);
		break;

	case 'setup_rubric_sub_add':
		if(!$rubric_id = _num($_POST['rubric_id']))
			jsonError();

		$name = _txt($_POST['name']);

		if(empty($name))
			jsonError();

		$sql = "SELECT *
				FROM `_setup_rubric`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$rubric_id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "INSERT INTO `_setup_rubric_sub` (
					`app_id`,
					`rubric_id`,
					`name`,
					`sort`
				) VALUES (
					".APP_ID.",
					".$rubric_id.",
					'".addslashes($name)."',
					"._maxSql('_setup_rubric_sub', 'sort')."
				)";
		query($sql);

		xcache_unset(CACHE_PREFIX.'rubric_sub'.APP_ID);
		_appJsValues();

		_history(array(
			'type_id' => 1060,
			'v1' => $name,
			'v2' => _rubric($rubric_id)
		));

		$send['html'] = utf8(setup_rubric_sub_spisok($rubric_id));
		jsonSuccess($send);
		break;
	case 'setup_rubric_sub_edit':
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);

		if(empty($name))
			jsonError();

		$sql = "SELECT *
				FROM `_setup_rubric_sub`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_setup_rubric_sub`
				SET `name`='".addslashes($name)."'
				WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'rubric_sub'.APP_ID);
		_appJsValues();

		if($changes =
			_historyChange('Наименование', $r['name'], $name))
			_history(array(
				'type_id' => 1061,
				'v1' => $name,
				'v2' => _rubric($r['rubric_id']),
				'v3' => '<table>'.$changes.'</table>'
			));

		$send['html'] = utf8(setup_rubric_sub_spisok($r['rubric_id']));
		jsonSuccess($send);
		break;
	case 'setup_rubric_sub_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_setup_rubric_sub`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "SELECT COUNT(`id`) FROM `_zayav` WHERE `rubric_id_sub`=".$id;
		if(query_value($sql))
			jsonError();

		$sql = "DELETE FROM `_setup_rubric_sub` WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'rubric_sub'.APP_ID);
		_appJsValues();

		_history(array(
			'type_id' => 1062,
			'v1' => $r['name'],
			'v2' => _rubric($r['rubric_id'])
		));

		$send['html'] = utf8(setup_rubric_sub_spisok($r['rubric_id']));
		jsonSuccess($send);
		break;

	case 'setup_zayav_status_add':
		if(!RULE_SETUP_ZAYAV_STATUS)
			jsonError();

		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);
		$color = _txt($_POST['color']);
		$default = _bool($_POST['default']);
		$nouse = _bool($_POST['nouse']);
		$executer = _bool($_POST['executer']);
		$srok = _bool($_POST['srok']);
		$accrual = _bool($_POST['accrual']);
		$remind = _bool($_POST['remind']);
		$day_fact = _bool($_POST['day_fact']);

		if(empty($name))
			jsonError();

		setupZayavStatusDefaultDrop($default);

		$sql = "INSERT INTO `_zayav_status` (
					`app_id`,
					`name`,
					`about`,
					`color`,
					`default`,
					`nouse`,
					`srok`,
					`executer`,
					`accrual`,
					`remind`,
					`day_fact`,
					`sort`
				) VALUES (
					".APP_ID.",
					'".addslashes($name)."',
					'".addslashes($about)."',
					'".$color."',
					".$default.",
					".$nouse.",
					".$srok.",
					".$executer.",
					".$accrual.",
					".$remind.",
					".$day_fact.",
					"._maxSql('_zayav_status')."
				)";
		query($sql);

		$id = query_insert_id('_zayav_status');

		setup_status_next_insert($id, $_POST);

		xcache_unset(CACHE_PREFIX.'zayav_status'.APP_ID);
		_appJsValues();
/*
		_history(array(
			'type_id' => 1027,
			'v1' => $name
		));
*/
		$send['html'] = utf8(setup_zayav_status_spisok());
		jsonSuccess($send);
		break;
	case 'setup_zayav_status_edit':
		if(!RULE_SETUP_ZAYAV_STATUS)
			jsonError();
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);
		$color = _txt($_POST['color']);
		$default = _bool($_POST['default']);
		$nouse = _bool($_POST['nouse']);
		$hide = _bool($_POST['hide']);
		$srok = _bool($_POST['srok']);
		$executer = _bool($_POST['executer']);
		$accrual = _bool($_POST['accrual']);
		$remind = _bool($_POST['remind']);
		$day_fact = _bool($_POST['day_fact']);

		if(empty($name))
			jsonError();

		setupZayavStatusDefaultDrop($default);

		$sql = "SELECT *
				FROM `_zayav_status`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_zayav_status`
				SET `name`='".addslashes($name)."',
					`about`='".addslashes($about)."',
					`color`='".$color."',
					`default`=".$default.",
					`nouse`='".$nouse."',
					`hide`=".$hide.",
					`srok`=".$srok.",
					`executer`=".$executer.",
					`accrual`=".$accrual.",
					`remind`=".$remind.",
					`day_fact`=".$day_fact."
				WHERE `id`=".$id;
		query($sql);

		$sql = "DELETE FROM `_zayav_status_next` WHERE `status_id`=".$id;
		query($sql);
		setup_status_next_insert($id, $_POST);

		xcache_unset(CACHE_PREFIX.'zayav_status'.APP_ID);
		_appJsValues();
/*
		$changes =
			_historyChange('Наименование', $r['name'], $name).
			_historyChange('Дополнительное поле', $r['dop'], $dop, _zayavExpenseDop($r['dop']), _zayavExpenseDop($dop));
		if($changes)
			_history(array(
				'type_id' => 1028,
				'v1' => $name,
				'v2' => '<table>'.$changes.'</table>'
			));
*/
		$send['html'] = utf8(setup_zayav_status_spisok());
		jsonSuccess($send);
		break;
	case 'setup_zayav_status_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_zayav_status`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "SELECT COUNT(`id`) FROM `_zayav` WHERE `status`=".$id;
		if(query_value($sql))
			jsonError();

		$sql = "UPDATE `_zayav_status` WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'zayav_expense'.APP_ID);
		_appJsValues();

		_history(array(
			'type_id' => 1029,
			'v1' => $r['name']
		));

		$send['html'] = utf8(setup_zayav_expense_spisok());
		jsonSuccess($send);
		break;

	case 'setup_zayav_expense_add':
		$name = _txt($_POST['name']);
		$dop = _num($_POST['dop']);

		if(empty($name))
			jsonError();

		$sql = "INSERT INTO `_zayav_expense_category` (
					`app_id`,
					`name`,
					`dop`,
					`sort`
				) VALUES (
					".APP_ID.",
					'".addslashes($name)."',
					".$dop.",
					"._maxSql('_zayav_expense_category')."
				)";
		query($sql);

		xcache_unset(CACHE_PREFIX.'zayav_expense'.APP_ID);
		_appJsValues();

		_history(array(
			'type_id' => 1027,
			'v1' => $name
		));

		$send['html'] = utf8(setup_zayav_expense_spisok());
		jsonSuccess($send);
		break;
	case 'setup_zayav_expense_edit':
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);
		$dop = _num($_POST['dop']);

		if(empty($name))
			jsonError();

		$sql = "SELECT * FROM `_zayav_expense_category` WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_zayav_expense_category`
				SET `name`='".addslashes($name)."',
					`dop`=".$dop."
				WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'zayav_expense'.APP_ID);
		_appJsValues();

		$changes =
			_historyChange('Наименование', $r['name'], $name).
			_historyChange('Дополнительное поле', $r['dop'], $dop, _zayavExpenseDop($r['dop']), _zayavExpenseDop($dop));
		if($changes)
			_history(array(
				'type_id' => 1028,
				'v1' => $name,
				'v2' => '<table>'.$changes.'</table>'
			));

		$send['html'] = utf8(setup_zayav_expense_spisok());
		jsonSuccess($send);
		break;
	case 'setup_zayav_expense_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT * FROM `_zayav_expense_category` WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "SELECT COUNT(`id`) FROM `_zayav_expense` WHERE `category_id`=".$id;
		if(query_value($sql))
			jsonError();

		$sql = "DELETE FROM `_zayav_expense_category` WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'zayav_expense'.APP_ID);
		_appJsValues();

		_history(array(
			'type_id' => 1029,
			'v1' => $r['name']
		));

		$send['html'] = utf8(setup_zayav_expense_spisok());
		jsonSuccess($send);
		break;

	case 'setup_salary_list'://сохранение настройки листа выдачи
		if(!$ids = _ids($_POST['ids']))
			jsonError();

		$sql = "UPDATE `_app`
				SET `salary_list_setup`='".$ids."'
				WHERE `id`=".APP_ID;
		query($sql);

		xcache_unset(CACHE_PREFIX.'app'.APP_ID);

		jsonSuccess();
		break;

	case 'cartridge_edit':
		if(!$id = _num($_POST['id']))
			jsonError();
		if(!$type_id = _num($_POST['type_id']))
			jsonError();
		$name = _txt($_POST['name']);
		$cost_filling = _num($_POST['cost_filling']);
		$cost_restore = _num($_POST['cost_restore']);
		$cost_chip = _num($_POST['cost_chip']);
		$join_id = _num($_POST['join_id']);

		if(empty($name))
			jsonError();
		if($join_id == $id)
			jsonError();

		$sql = "SELECT * FROM `_setup_cartridge` WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		if($join_id) {
			$sql = "SELECT * FROM `_setup_cartridge` WHERE `id`=".$join_id;
			if(!$j = query_assoc($sql))
				jsonError();
			$sql = "UPDATE `_zayav_cartridge`
					SET `cartridge_id`=".$id."
					WHERE `cartridge_id`=".$join_id;
			query($sql);
			$sql = "DELETE FROM `_setup_cartridge` WHERE `id`=".$join_id;
			query($sql);

			_history(array(
				'type_id' => 1019,
				'v1' => $name,
				'v2' => $j['name']
			));
		}

		$sql = "UPDATE `_setup_cartridge`
				SET `type_id`=".$type_id.",
					`name`='".addslashes($name)."',
					`cost_filling`=".$cost_filling.",
					`cost_restore`=".$cost_restore.",
					`cost_chip`=".$cost_chip."
				WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'cartridge');
		_appJsValues();

		if($changes =
			_historyChange('Вид', _cartridgeType($r['type_id']), _cartridgeType($type_id)).
			_historyChange('Модель', $r['name'], $name).
			_historyChange('Заправка', $r['cost_filling'], $cost_filling).
			_historyChange('Восстановление', $r['cost_restore'], $cost_restore).
			_historyChange('Замена чипа', $r['cost_chip'], $cost_chip))
			_history(array(
				'type_id' => 1034,
				'v1' => $name,
				'v2' => '<table>'.$changes.'</table>'
			));

		$send['html'] = utf8(setup_cartridge_spisok($id));
		$sql = "SELECT `id`,`name` FROM `_setup_cartridge` ORDER BY `name`";
		$send['cart'] = query_selArray($sql);
		jsonSuccess($send);
		break;

	case 'setup_tovar_category_add':
		if(!$name = _txt($_POST['name']))
			jsonError();

		$sql = "INSERT INTO `_tovar_category` (
					`app_id`,
					`name`
				) VALUES (
					".APP_ID.",
					'".addslashes($name)."'
				)";
		query($sql);
		$insert_id = query_insert_id('_tovar_category');

		setup_tovar_category_use_insert($insert_id);

		xcache_unset(CACHE_PREFIX.'tovar_category'.APP_ID);
		_appJsValues();

		$send['html'] = utf8(setup_tovar_category_spisok());
		jsonSuccess($send);
		break;
	case 'setup_tovar_category_edit':
		if(!$id = _num($_POST['id']))
			jsonError();
		if(!$name = _txt($_POST['name']))
			jsonError();

		$sql = "SELECT *
				FROM `_tovar_category`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_tovar_category`
		        SET `name`='".addslashes($name)."'
		        WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'tovar_category'.APP_ID);
		_appJsValues();

		$send['html'] = utf8(setup_tovar_category_spisok());
		jsonSuccess($send);
		break;
	case 'setup_tovar_category_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_tovar_category`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "DELETE FROM `_tovar_category` WHERE `id`=".$id;
		query($sql);

		$sql = "DELETE FROM `_tovar_category_use` WHERE `category_id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'tovar_category'.APP_ID);
		_appJsValues();

		jsonSuccess();
		break;
	case 'setup_tovar_category_join_load'://получение списка категорий готовых каталогов
		$sql = "SELECT
					*,
					0 `tovar_count`,
					0 `use`
				FROM `_tovar_category`
				WHERE !`app_id`
				ORDER BY `name`";
		$spisok = query_arr($sql);

		$sql = "SELECT
					`category_id`,
					COUNT(`id`) `count`
				FROM `_tovar`
				WHERE `category_id` IN ("._idsGet($spisok).")
				GROUP BY `category_id`";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q))
			$spisok[$r['category_id']]['tovar_count'] = $r['count'];

		$sql = "SELECT `category_id`
				FROM `_tovar_category_use`
				WHERE `app_id`=".APP_ID;
		$q = query($sql);
		while($r = mysql_fetch_assoc($q)) {
			if(!isset($spisok[$r['category_id']]))
				continue;
			$spisok[$r['category_id']]['use'] = 1;
		}

		$html =
			'<table class="_spisok" id="setup_tovar_category_join">'.
				'<tr><th>'._check('check_all').
					'<th>Категория'.
					'<th>Товары';
		foreach($spisok as $r) {
			if(!$r['tovar_count'])
				continue;
			$html .=
				'<tr><td class="ch">'._check('ch'.$r['id'], '', $r['use']).
					'<td>'.$r['name'].
					'<td class="tovar_count">'.$r['tovar_count'];
		}

		$html .= '</table>';

		$send['html'] = utf8($html);

		jsonSuccess($send);
		break;
	case 'setup_tovar_category_join_save'://применение категорий из общих каталогов
		if(empty($_POST['ids'])) {
			//если было ничего не выбрано, удаление всех общих категорий
			$sql = "DELETE FROM `_tovar_category_use`
					WHERE `app_id`=".APP_ID."
					  AND `category_id` IN ("._tovarCategory('noapp').")";
			query($sql);
		} else {
			if(!$ids = _ids($_POST['ids']))
				jsonError();

			//удаление категорий, которые были не выбраны
			$sql = "DELETE FROM `_tovar_category_use`
					WHERE `app_id`=".APP_ID."
					  AND `category_id` IN ("._tovarCategory('noapp').")
					  AND `category_id` NOT IN (".$ids.")";
			query($sql);

			$sql = "SELECT `category_id`,0
					FROM `_tovar_category_use`
					WHERE `app_id`=".APP_ID;
			$use = query_ass($sql);

			foreach(_ids($ids, 1) as $id) {
				if(isset($use[$id]))
					continue;
				setup_tovar_category_use_insert($id);
			}
		}



		xcache_unset(CACHE_PREFIX.'tovar_category'.APP_ID);
		_appJsValues();

		$send['html'] = utf8(setup_tovar_category_spisok());
		jsonSuccess($send);
		break;
}

function setup_status_next_insert($status_id, $post) {
	if(!$ids = _ids($post['next_ids'], 1))
		return;

	$values = array();
	foreach($ids as $i)
		$values[] = "(
			".APP_ID.",
			".$status_id.",
			".$i."
		)";
	$sql = "INSERT INTO `_zayav_status_next` (
				`app_id`,
				`status_id`,
				`next_id`
			) VALUES ".implode(',', $values);
	query($sql);
}
function setup_tovar_category_use_insert($id) {//внесение категории товаров, доступной для приложения
	$sql = "INSERT INTO `_tovar_category_use` (
				`app_id`,
				`category_id`,
				`sort`
			) VALUES (
				".APP_ID.",
				".$id.",
				"._maxSql('_tovar_category_use')."
			)";
	query($sql);
}

