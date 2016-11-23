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
		if(!_viewerMenuAccess(15))
			jsonError();

		$viewer_id = _num($_POST['viewer_id']);

		if($viewer_id) {
			$sql = "SELECT *
					FROM `_vkuser`
					WHERE `app_id`=".APP_ID."
					  AND `viewer_id`=".$viewer_id;
			if($r = query_assoc($sql))
				if($r['worker'])
					jsonError('Этот пользователь уже является сотрудником Вашей организации');

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


		$send['id'] = $viewer_id;
		jsonSuccess($send);
		break;
	case 'setup_worker_del':
		if(!_viewerMenuAccess(15))
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
	case 'setup_worker_edit':
		if(!_viewerMenuAccess(15))
			jsonError();

		if(!$viewer_id = _num($_POST['viewer_id']))
			jsonError();

		$u = _viewer($viewer_id);

		$first_name = _txt($_POST['first_name']);
		$last_name = _txt($_POST['last_name']);
		$middle_name = _txt($_POST['middle_name']);
		$post = _txt($_POST['post']);

		if(!$first_name || !$last_name)
			jsonError('Не указаны Фамилия или Имя');

		$sql = "UPDATE `_vkuser`
				SET `first_name`='".addslashes($first_name)."',
					`last_name`='".addslashes($last_name)."',
					`middle_name`='".addslashes($middle_name)."',
			        `post`='".addslashes($post)."'
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$viewer_id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'viewer_'.$viewer_id);

		if($changes =
			_historyChange('Имя', $u['viewer_first_name'], $first_name).
			_historyChange('Фамилия', $u['viewer_last_name'], $last_name).
			_historyChange('Отчество', $u['viewer_middle_name'], $middle_name).
			_historyChange('Должность', $u['viewer_post'], $post)
		)	_history(array(
				'type_id' => 1001,
				'worker_id' => $viewer_id,
				'v1' => '<table>'.$changes.'</table>'
			));

		jsonSuccess();
		break;
	case 'setup_worker_bind'://привязка сотрудника к учётной записи ВКонтакте
		if(!_viewerMenuAccess(15))
			jsonError('Нет прав');

		if(!$worker_id = _num($_POST['worker_id']))
			jsonError('Некорректный id сотрудника');

		if(!$viewer_id = _num($_POST['viewer_id']))
			jsonError('Некорректный id учётной записи ВКонтакте');

		if($worker_id < VIEWER_MAX)
			jsonError('Сотрудник является пользователем ВКонтакте');

		$sql = "SELECT *
				FROM `_vkuser`
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$worker_id."
				LIMIT 1";
		if(!$w = query_assoc($sql))
			jsonError('Сотрудника не существует');

		if(!$w['worker'])
			jsonError($w['first_name'].' '.$w['last_name'].' не является сотрудником');

		$u = _viewer($viewer_id);
		if($u['viewer_worker'])
			jsonError($u['viewer_name'].' уже является сотрудником Вашей организации');

		//применение данных на новую учётную запись
		$sql = "UPDATE `_vkuser`
				SET `worker`=1,
					`admin`=".$w['admin'].",

					`first_name`='".addslashes($w['first_name'])."',
					`last_name`='".addslashes($w['last_name'])."',
					`middle_name`='".addslashes($w['middle_name'])."',
			        `post`='".addslashes($w['post'])."',

			        `salary_balans_start`=".$w['salary_balans_start'].",
			        `salary_rate_sum`=".$w['salary_rate_sum'].",
			        `salary_rate_period`=".$w['salary_rate_period'].",
			        `salary_rate_day`=".$w['salary_rate_day'].",
			        `salary_bonus_sum`=".$w['salary_bonus_sum'].",

					`dtime_add`='".$w['dtime_add']."'
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$viewer_id;
		query($sql);

		//удаление старой учётной записи
		$sql = "DELETE FROM `_vkuser`
 				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$worker_id;
		query($sql);

		//изменение в балансах
		$sql = "UPDATE `_balans`
				SET `unit_id`=".$viewer_id."
 				WHERE `app_id`=".APP_ID."
 				  AND `category_id`=5
				  AND `unit_id`=".$worker_id;
		query($sql);

		//привязка к клиенту
		$sql = "UPDATE `_client`
				SET `worker_id`=".$viewer_id."
 				WHERE `app_id`=".APP_ID."
				  AND `worker_id`=".$worker_id;
		query($sql);

		//расходы: зарплата
		$sql = "UPDATE `_money_expense`
				SET `worker_id`=".$viewer_id."
 				WHERE `app_id`=".APP_ID."
				  AND `worker_id`=".$worker_id;
		query($sql);

		//зарплата: начисления
		$sql = "UPDATE `_salary_accrual`
				SET `worker_id`=".$viewer_id."
 				WHERE `app_id`=".APP_ID."
				  AND `worker_id`=".$worker_id;
		query($sql);

		//зарплата: бонусы
		$sql = "UPDATE `_salary_bonus`
				SET `worker_id`=".$viewer_id."
 				WHERE `app_id`=".APP_ID."
				  AND `worker_id`=".$worker_id;
		query($sql);

		//зарплата: вычеты
		$sql = "UPDATE `_salary_deduct`
				SET `worker_id`=".$viewer_id."
 				WHERE `app_id`=".APP_ID."
				  AND `worker_id`=".$worker_id;
		query($sql);

		//зарплата: листы выдачи
		$sql = "UPDATE `_salary_list`
				SET `worker_id`=".$viewer_id."
 				WHERE `app_id`=".APP_ID."
				  AND `worker_id`=".$worker_id;
		query($sql);

		//права в приложении
		$sql = "UPDATE `_vkuser_rule`
				SET `viewer_id`=".$viewer_id."
 				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$worker_id;
		query($sql);

		//исполнители в заявках
		$sql = "UPDATE `_zayav`
				SET `executer_id`=".$viewer_id."
 				WHERE `app_id`=".APP_ID."
				  AND `executer_id`=".$worker_id;
		query($sql);

		//расходы по заявкам
		$sql = "UPDATE `_zayav_expense`
				SET `worker_id`=".$viewer_id."
 				WHERE `app_id`=".APP_ID."
				  AND `worker_id`=".$worker_id;
		query($sql);

		//статусы заявок
		$sql = "UPDATE `_zayav_status_move`
				SET `executer_id`=".$viewer_id."
 				WHERE `app_id`=".APP_ID."
				  AND `executer_id`=".$worker_id;
		query($sql);

		//история действий: поле сотрудника
		$sql = "UPDATE `_history`
				SET `worker_id`=".$viewer_id."
 				WHERE `app_id`=".APP_ID."
				  AND `worker_id`=".$worker_id;
		query($sql);

		//история действий: поле v1
		$sql = "UPDATE `_history`
				SET `v1`=REPLACE(`v1`,'".$worker_id."','".$viewer_id."')
 				WHERE `app_id`=".APP_ID;
		query($sql);

		_appJsValues();
		_globalCacheClear();

		_history(array(
			'type_id' => 1063,
			'worker_id' => $viewer_id
		));

		jsonSuccess();
		break;
	case 'setup_worker_pin_clear':
		if(!VIEWER_ADMIN)
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
		if(!_viewerMenuAccess(5))
			jsonError();
		if(!RULE_SETUP_RULES)
			jsonError();

		if(!$viewer_id = _num($_POST['viewer_id']))
			jsonError();
		if(!$menu_id = _num($_POST['menu_id']))
			jsonError();
		$v = _bool($_POST['v']);

		if(_viewerMenuAccess($menu_id, $viewer_id) == $v)
			jsonError();

		if($viewer_id >= VIEWER_MAX)
			jsonError();

		if($v)
			$sql = "INSERT INTO `_menu_viewer` (
						`app_id`,
						`viewer_id`,
						`menu_id`
					) VALUES (
						".APP_ID.",
						".$viewer_id.",
						".$menu_id."
					)";
		else
			$sql = "DELETE FROM `_menu_viewer`
					WHERE `app_id`=".APP_ID."
					  AND `viewer_id`=".$viewer_id."
					  AND `menu_id`=".$menu_id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'viewer_menu_access_'.$viewer_id);

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
	case 'RULE_WORKER_SALARY_VIEW'://видит з/п сотрудников
		if(!RULE_SETUP_RULES)
			jsonError();

		if(!$viewer_id = _num($_POST['viewer_id']))
			jsonError();

		$new = _num($_POST['v']);

		$old = _viewerRule($viewer_id, 'RULE_WORKER_SALARY_VIEW');

		if($old == $new)
			jsonError();

		_workerRuleQuery($viewer_id, 'RULE_WORKER_SALARY_VIEW', $new);

		$arr = array(
			0 => 'нет',
			1 => 'только свою',
			2 => 'всех сотрудников'
		);

		_history(array(
			'type_id' => 1012,
			'worker_id' => $viewer_id,
			'v1' => '<table>'.
						_historyChange('Видит з/п', $arr[$old], $arr[$new]).
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

	case 'RULE_MY_PAY_SHOW_PERIOD'://мои настройки: показывать платежи: за день, неделю, месяц
		_workerRuleQuery(VIEWER_ID, 'RULE_MY_PAY_SHOW_PERIOD', _num($_POST['v']));

		xcache_unset(CACHE_PREFIX.'viewer_'.VIEWER_ID);
		xcache_unset(CACHE_PREFIX.'viewer_rule_'.VIEWER_ID);

		jsonSuccess();
		break;

	case 'setup_org_load':
		if($org_id = _num($_POST['org_id'])) {
			$sql = "SELECT *
					FROM `_setup_org`
					WHERE `app_id`=".APP_ID."
					  AND `id`=".$org_id;
			$g = query_assoc($sql);
		}

		$send['html'] = utf8(
			'<div class="hd2 mar020">Основная информация</div>'.
			'<table class="bs10">'.
				'<tr><td class="label r topi w175">Название организации*:<td><textarea id="name'.$org_id.'" class="w400">'.@$g['name'].'</textarea>'.
				'<tr><td class="label r topi">Наименование юр. лица:<td><textarea id="name_yur'.$org_id.'" class="w400">'.@$g['name_yur'].'</textarea>'.
				'<tr><td class="label r">Контактные телефоны:<td><input type="text" id="phone'.$org_id.'" class="w400" value="'.@$g['phone'].'" />'.
				'<tr><td class="label r">Факс:<td><input type="text" id="fax'.$org_id.'" class="w400" value="'.@$g['fax'].'" />'.
				'<tr><td class="label r topi">Юридический адрес:<td><textarea id="adres_yur'.$org_id.'" class="w400">'.@$g['adres_yur'].'</textarea>'.
				'<tr><td class="label r topi">Адрес офиса:<td><textarea id="adres_ofice'.$org_id.'" class="w400">'.@$g['adres_ofice'].'</textarea>'.
				'<tr><td class="label r">Режим работы:<td><input type="text" id="time_work'.$org_id.'" class="w400" value="'.@$g['time_work'].'" />'.
			'</table>'.

			'<div class="hd2 mt20 mar020">Реквизиты</div>'.
			'<table class="bs10">'.
				'<tr><td class="label r w175">ОГРН:<td><input type="text" id="ogrn'.$org_id.'" class="w400" value="'.@$g['ogrn'].'" />'.
				'<tr><td class="label r">ИНН:<td><input type="text" id="inn'.$org_id.'" class="w400" value="'.@$g['inn'].'" />'.
				'<tr><td class="label r">КПП:<td><input type="text" id="kpp'.$org_id.'" class="w400" value="'.@$g['kpp'].'" />'.
				'<tr><td class="label r">ОКУД:<td><input type="text" id="okud'.$org_id.'" class="w400" value="'.@$g['okud'].'" />'.
				'<tr><td class="label r">ОКПО:<td><input type="text" id="okpo'.$org_id.'" class="w400" value="'.@$g['okpo'].'" />'.
				'<tr><td class="label r topi">Вид деятельности<br />по ОКВЭД:'.
					'<td><textarea id="okved'.$org_id.'" class="w400">'.@$g['okved'].'</textarea>'.
			'</table>'.
	
			'<div class="hd2 mt20 mar020">Должностные лица</div>'.
			'<table class="bs10">'.
				'<tr><td class="label r w175">Руководитель:<td><input type="text" id="post_boss'.$org_id.'" class="w400" value="'.@$g['post_boss'].'" />'.
				'<tr><td class="label r">Главный бухгалтер:<td><input type="text" id="post_accountant'.$org_id.'" class="w400" value="'.@$g['post_accountant'].'" />'.
			'</table>'
		);

		jsonSuccess($send);
		break;
	case 'setup_org_add'://Внесение новой организации
		if(!_viewerMenuAccess(13))
			jsonError('Нет прав');

		if(!$name = _txt($_POST['name']))
			jsonError('Не указано название');

		$name_yur = _txt($_POST['name_yur']);
		$phone = _txt($_POST['phone']);
		$fax = _txt($_POST['fax']);
		$adres_yur = _txt($_POST['adres_yur']);
		$adres_ofice = _txt($_POST['adres_ofice']);
		$time_work = _txt($_POST['time_work']);

		$ogrn = _txt($_POST['ogrn']);
		$inn = _txt($_POST['inn']);
		$kpp = _txt($_POST['kpp']);
		$okud = _txt($_POST['okud']);
		$okpo = _txt($_POST['okpo']);
		$okved = _txt($_POST['okved']);

		$post_boss = _txt($_POST['post_boss']);
		$post_accountant = _txt($_POST['post_accountant']);

		$sql = "INSERT INTO `_setup_org` (
					`app_id`,

					`name`,
					`name_yur`,
					`phone`,
					`fax`,
					`adres_yur`,
					`adres_ofice`,
					`time_work`,

					`ogrn`,
					`inn`,
					`kpp`,
					`okud`,
					`okpo`,
					`okved`,

					`post_boss`,
					`post_accountant`
				) VALUES (
					".APP_ID.",

					'".addslashes($name)."',
					'".addslashes($name_yur)."',
					'".addslashes($phone)."',
					'".addslashes($fax)."',
					'".addslashes($adres_yur)."',
					'".addslashes($adres_ofice)."',
					'".addslashes($time_work)."',

					'".addslashes($ogrn)."',
					'".addslashes($inn)."',
					'".addslashes($kpp)."',
					'".addslashes($okud)."',
					'".addslashes($okpo)."',
					'".addslashes($okved)."',

					'".addslashes($post_boss)."',
					'".addslashes($post_accountant)."'
				)";
		query($sql);

		xcache_unset(CACHE_PREFIX.'app');

		_history(array(
				'type_id' => 1064,
				'v1' => $name
			));
		jsonSuccess();
		break;
	case 'setup_org_edit':
		if(!_viewerMenuAccess(13))
			jsonError('Нет прав');

		if(!$org_id = _num($_POST['org_id']))
			jsonError('Некорректный id');
		if(!$name = _txt($_POST['name']))
			jsonError('Не указано название');

		$name_yur = _txt($_POST['name_yur']);
		$phone = _txt($_POST['phone']);
		$fax = _txt($_POST['fax']);
		$adres_yur = _txt($_POST['adres_yur']);
		$adres_ofice = _txt($_POST['adres_ofice']);
		$time_work = _txt($_POST['time_work']);

		$ogrn = _txt($_POST['ogrn']);
		$inn = _txt($_POST['inn']);
		$kpp = _txt($_POST['kpp']);
		$okud = _txt($_POST['okud']);
		$okpo = _txt($_POST['okpo']);
		$okved = _txt($_POST['okved']);

		$post_boss = _txt($_POST['post_boss']);
		$post_accountant = _txt($_POST['post_accountant']);

		$sql = "SELECT *
				FROM `_setup_org`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$org_id;
		if(!$r = query_assoc($sql))
			jsonError('Организации не существует');

		$sql = "UPDATE `_setup_org`
				SET `name`='".addslashes($name)."',
					`name_yur`='".addslashes($name_yur)."',
					`phone`='".addslashes($phone)."',
					`fax`='".addslashes($fax)."',
					`adres_yur`='".addslashes($adres_yur)."',
					`adres_ofice`='".addslashes($adres_ofice)."',
					`time_work`='".addslashes($time_work)."',

					`ogrn`='".addslashes($ogrn)."',
					`inn`='".addslashes($inn)."',
					`kpp`='".addslashes($kpp)."',
					`okud`='".addslashes($okud)."',
					`okpo`='".addslashes($okpo)."',
					`okved`='".addslashes($okved)."',

					`post_boss`='".addslashes($post_boss)."',
					`post_accountant`='".addslashes($post_accountant)."'
				WHERE `id`=".$org_id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'app');

		if($changes =
			_historyChange('Название организации', $r['name'], $name).
			_historyChange('Наименование юридического лица', $r['name_yur'], $name_yur).
			_historyChange('Контактные телефоны', $r['phone'], $phone).
			_historyChange('Факс', $r['fax'], $fax).
			_historyChange('Юридический адрес', $r['adres_yur'], $adres_yur).
			_historyChange('Адрес офиса', $r['adres_ofice'], $adres_ofice).
			_historyChange('Режим работы', $r['time_work'], $time_work).

			_historyChange('ОГРН', $r['ogrn'], $ogrn).
			_historyChange('ИНН', $r['inn'], $inn).
			_historyChange('КПП', $r['kpp'], $kpp).
			_historyChange('ОКУД', $r['okud'], $okud).
			_historyChange('ОКПО', $r['okpo'], $okpo).
			_historyChange('Вид деятельности по ОКВЭД', $r['okved'], $okved).

			_historyChange('Руководитель', $r['post_boss'], $post_boss).
			_historyChange('Главный бухгалтер', $r['post_accountant'], $post_accountant)
		)	_history(array(
				'type_id' => 1026,
				'v1' => '<table>'.$changes.'</table>',
				'v2' => $name
			));

		jsonSuccess();
		break;
	case 'setup_bik_load'://получение данных банка по БИК
		$bik = _txt($_POST['bik']);

		if(!preg_match(REGEXP_NUMERIC, $bik))
			jsonError('Некорректно заполнено поле БИК');

		$sql = "SELECT *
				FROM `_setup_biks`
				WHERE `bik`='".$bik."'
				LIMIT 1";
		if(!$send = query_assoc($sql))
			jsonError('По данному БИК информации нет');

		foreach($send as $i => $r)
			$send[$i] = utf8($r);

		jsonSuccess($send);
		break;
	case 'setup_bank_load'://получение информации о банке
		if($bank_id = _num($_POST['bank_id'])) {
			$sql = "SELECT *
					FROM `_setup_org_bank`
					WHERE `app_id`=".APP_ID."
					  AND `id`=".$bank_id;
			$r = query_assoc($sql);
		}

		$send['html'] = utf8(
			'<table class="bs10">'.
				'<tr><td class="label r w175">БИК:<td><input type="text" id="bik" class="w300" value="'.@$r['bik'].'" />'.
				'<tr><td class="label"><td><button class="vk" id="bik-load">Получить данные по БИК</button>'.
				'<tr><td class="label r topi">Наименование банка:<td><textarea id="name" class="w300">'.@$r['name'].'</textarea>'.
				'<tr><td class="label r">Корреспондентский счёт:<td><input type="text" id="account_corr" class="w300" value="'.@$r['account_corr'].'" />'.
				'<tr><td><td>'.
				'<tr><td class="label r">Расчётный счёт:<td><input type="text" id="account" class="w300" value="'.@$r['account'].'" />'.
			'</table>'
		);

		jsonSuccess($send);
		break;
	case 'setup_bank_add'://Внесение нового банка
		if(!_viewerMenuAccess(13))
			jsonError('Нет прав');

		if(!$org_id = _num($_POST['org_id']))
			jsonError('Некорректный id организации');
		if(!$bik = _txt($_POST['bik']))
			jsonError('Некорректно заполнен БИК');

		$name = _txt($_POST['name']);
		$account_corr = _txt($_POST['account_corr']);
		$account = _txt($_POST['account']);

		$sql = "SELECT *
				FROM `_setup_org`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$org_id;
		if(!$r = query_assoc($sql))
			jsonError('Организации не существует');


		$sql = "INSERT INTO `_setup_org_bank` (
					`app_id`,
					`org_id`,

					`bik`,
					`name`,
					`account_corr`,
					`account`
				) VALUES (
					".APP_ID.",
					".$org_id.",

					'".$bik."',
					'".addslashes($name)."',
					'".addslashes($account_corr)."',
					'".addslashes($account)."'
				)";
		query($sql);

		xcache_unset(CACHE_PREFIX.'app');
/*
		_history(array(
				'type_id' => 1064,
				'v1' => $name
			));
*/

		$sql = "SELECT *
				FROM `_setup_org_bank`
				WHERE `app_id`=".APP_ID."
				  AND `org_id`=".$org_id."
				ORDER BY `id`";
		$bank = query_arr($sql);

		foreach($bank as $id => $r)
			foreach($r as $k => $i)
				$bank[$id][$k] = utf8($i);

//		$send['html'] = setup_org_bank($org_id, $bank);
		
		jsonSuccess();
		break;
	case 'setup_bank_edit'://Редактирование данных банка
		if(!_viewerMenuAccess(13))
			jsonError('Нет прав');

		if(!$bank_id = _num($_POST['bank_id']))
			jsonError('Некорректный id банка');
		if(!$bik = _txt($_POST['bik']))
			jsonError('Некорректно заполнен БИК');

		$name = _txt($_POST['name']);
		$account_corr = _txt($_POST['account_corr']);
		$account = _txt($_POST['account']);

		$sql = "SELECT *
				FROM `_setup_org_bank`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$bank_id;
		if(!$r = query_assoc($sql))
			jsonError('Банка не существует');


		$sql = "UPDATE `_setup_org_bank`
				SET `bik`='".$bik."',
					`name`='".addslashes($name)."',
					`account_corr`='".addslashes($account_corr)."',
					`account`='".addslashes($account)."'
				WHERE `id`=".$bank_id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'app');
/*
		_history(array(
				'type_id' => 1064,
				'v1' => $name
			));
*/

		jsonSuccess();
		break;
	case 'setup_bank_del'://удаление банка
		if(!_viewerMenuAccess(13))
			jsonError('Нет прав');

		if(!$bank_id = _num($_POST['id']))
			jsonError('Некорректный id банка');

		$sql = "SELECT *
				FROM `_setup_org_bank`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$bank_id;
		if(!$r = query_assoc($sql))
			jsonError('Банка не существует');


		$sql = "DELETE FROM `_setup_org_bank` WHERE `id`=".$bank_id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'app');
/*
		_history(array(
				'type_id' => 1064,
				'v1' => $name
			));
*/

		jsonSuccess();
		break;
	case 'setup_org_nalog_edit'://настройка налогового учёта
		if(!_viewerMenuAccess(13))
			jsonError('Нет прав');

		if(!$org_id = _num($_POST['org_id']))
			jsonError('Некорректный id');

		$sql = "SELECT *
				FROM `_setup_org`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$org_id;
		if(!$r = query_assoc($sql))
			jsonError('Организации не существует');

		$nalog_system = _num($_POST['nalog_system']);
		$nds = _num($_POST['nds']);

		$sql = "UPDATE `_setup_org`
				SET `nalog_system`=".$nalog_system.",
					`nds`=".$nds."
				WHERE `id`=".$org_id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'app');

		if($changes =
			_historyChange('Система налогообложеня', setupNalogSystem($r['nalog_system']), setupNalogSystem($nalog_system)).
			_historyChange('НДС', setupNds($r['nds']), setupNds($nds))
		)	_history(array(
				'type_id' => 1065,
				'v1' => $r['name'],
				'v2' => '<table>'.$changes.'</table>',
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

		xcache_unset(CACHE_PREFIX.'service');
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

		xcache_unset(CACHE_PREFIX.'service');
		_appJsValues();

		jsonSuccess();
		break;

	case 'expense_category_add':
		if(!$name = _txt($_POST['name']))
			jsonError('Не указано наименование');

		$about = _txt($_POST['about']);

		$sql = "INSERT INTO `_money_expense_category` (
					`app_id`,
					`name`,
					`about`,
					`sort`
				) VALUES (
					".APP_ID.",
					'".addslashes($name)."',
					'".addslashes($about)."',
					"._maxSql('_money_expense_category')."
				)";
		query($sql);

		xcache_unset(CACHE_PREFIX.'expense');
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
			jsonError('Некорректный идентификатор');

		if(!$name = _txt($_POST['name']))
			jsonError('Не указано наименование');

		$about = _txt($_POST['about']);

		$sql = "SELECT *
				FROM `_money_expense_category`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('Расход не найден');

		$sql = "UPDATE `_money_expense_category`
				SET `name`='".addslashes($name)."',
					`about`='".addslashes($about)."'
				WHERE `id`=".$id;
		query($sql);

		if($changes =
			_historyChange('Наименование', $r['name'], $name).
			_historyChange('Описание', _br($r['about']), _br($about))
		)	_history(array(
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

		xcache_unset(CACHE_PREFIX.'expense');
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

		xcache_unset(CACHE_PREFIX.'expense');
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

		xcache_unset(CACHE_PREFIX.'expense_sub');
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

		xcache_unset(CACHE_PREFIX.'expense_sub');
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

		xcache_unset(CACHE_PREFIX.'expense_sub');
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

		xcache_unset(CACHE_PREFIX.'rubric');
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

		xcache_unset(CACHE_PREFIX.'rubric');
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

		xcache_unset(CACHE_PREFIX.'rubric');
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

		xcache_unset(CACHE_PREFIX.'rubric_sub');
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

		xcache_unset(CACHE_PREFIX.'rubric_sub');
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

		xcache_unset(CACHE_PREFIX.'rubric_sub');
		_appJsValues();

		_history(array(
			'type_id' => 1062,
			'v1' => $r['name'],
			'v2' => _rubric($r['rubric_id'])
		));

		$send['html'] = utf8(setup_rubric_sub_spisok($r['rubric_id']));
		jsonSuccess($send);
		break;

	case 'setup_polosa_add':
		if(!$cena = _cena($_POST['cena']))
			jsonError();

		$polosa = _bool($_POST['polosa']);
		$name = _txt($_POST['name']);

		if(empty($name))
			jsonError();

		$sql = "INSERT INTO `_setup_gazeta_polosa_cost` (
					`app_id`,
					`name`,
					`cena`,
					`polosa`,
					`sort`
				) VALUES (
					".APP_ID.",
					'".addslashes($name)."',
					".$cena.",
					".$polosa.",
					"._maxSql('_setup_gazeta_polosa_cost', 'sort')."
				)";
		query($sql);

		xcache_unset(CACHE_PREFIX.'gazeta_polosa');
//		GvaluesCreate();

		$send['html'] = utf8(setup_polosa_spisok());
		jsonSuccess($send);
		break;
	case 'setup_polosa_edit':
		if(!$id = _num($_POST['id']))
			jsonError();
		if(!$cena = _cena($_POST['cena']))
			jsonError();

		$polosa = _bool($_POST['polosa']);
		$name = _txt($_POST['name']);

		if(empty($name))
			jsonError();

		$sql = "SELECT *
				FROM `_setup_gazeta_polosa_cost`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_setup_gazeta_polosa_cost`
				SET `name`='".addslashes($name)."',
					`cena`=".$cena.",
					`polosa`=".$polosa."
				WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'gazeta_polosa');
//		GvaluesCreate();

		$send['html'] = utf8(setup_polosa_spisok());
		jsonSuccess($send);
		break;

	case 'setup_obdop_edit':
		if(!$id = _num($_POST['id']))
			jsonError();
		if(!$cena = _cena($_POST['cena']))
			jsonError();

		$sql = "SELECT *
				FROM `_setup_gazeta_ob_dop`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_setup_gazeta_ob_dop`
				SET `cena`=".$cena."
				WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'gazeta_obdop');
//		GvaluesCreate();

		$send['html'] = utf8(setup_obdop_spisok());
		jsonSuccess($send);
		break;

	case 'setup_oblen_edit':
		if(!$txt_len_first = _num($_POST['txt_len_first']))
			jsonError();
		if(!$txt_cena_first = _num($_POST['txt_cena_first']))
			jsonError();
		if(!$txt_len_next = _num($_POST['txt_len_next']))
			jsonError();
		if(!$txt_cena_next = _num($_POST['txt_cena_next']))
			jsonError();

		if(TXT_LEN_FIRST == $txt_len_first &&
		   TXT_CENA_FIRST == $txt_cena_first &&
		   TXT_LEN_NEXT == $txt_len_next &&
		   TXT_CENA_NEXT == $txt_cena_next)
			jsonError();

		$sql = "UPDATE `_setup_global`
				SET `value`=".$txt_len_first."
				WHERE `app_id`=".APP_ID."
				  AND `key`='TXT_LEN_FIRST'";
		query($sql);

		$sql = "UPDATE `_setup_global`
				SET `value`=".$txt_cena_first."
				WHERE `app_id`=".APP_ID."
				  AND `key`='TXT_CENA_FIRST'";
		query($sql);

		$sql = "UPDATE `_setup_global`
				SET `value`=".$txt_len_next."
				WHERE `app_id`=".APP_ID."
				  AND `key`='TXT_LEN_NEXT'";
		query($sql);

		$sql = "UPDATE `_setup_global`
				SET `value`=".$txt_cena_next."
				WHERE `app_id`=".APP_ID."
				  AND `key`='TXT_CENA_NEXT'";
		query($sql);

		xcache_unset(CACHE_PREFIX.'setup_global');
//		GvaluesCreate();

		jsonSuccess();
		break;

	case 'setup_gn_spisok_get':
		if(!preg_match(REGEXP_YEAR, $_POST['year']))
			jsonError();

		$year = _num($_POST['year']);

		$send['html'] = utf8(setup_gn_spisok($year));
		jsonSuccess($send);
		break;
	case 'setup_gn_spisok_create':
		if(!$year = _num($_POST['year']))
			jsonError();
		if(!$week_nomer = _num($_POST['week_nomer']))
			jsonError();
		if(!$general_nomer = _num($_POST['general_nomer']))
			jsonError();
		if(!$day_print = _num($_POST['day_print']))
			jsonError();
		if(!$day_public = _num($_POST['day_public']))
			jsonError();
		if(!preg_match(REGEXP_DATE, $_POST['day_first']))
			jsonError();
		if(!$polosa_count = _num($_POST['polosa_count']))
			jsonError();

		$day_first = $_POST['day_first'];

		$sql = "SELECT `general_nomer`
				FROM `_setup_gazeta_nomer`
				WHERE `app_id`=".APP_ID."
				  AND `general_nomer`=".$general_nomer;
		if(query_value($sql))
			jsonError('Номер газеты <b>'.$general_nomer.'</b> уже есть в списке');

		// Первая неделя
		$weekFirst = strtotime($day_first);
		// Номер дня первой недели
		$printFirst = date('w', $weekFirst);
		if($printFirst == 0)
			$printFirst = 7;
		// Приведение первой недели к понедельнику
		if($printFirst != 1)
			$weekFirst -= 86400 * ($printFirst - 1);
		// Определение первого дня следующего года, если цикл за него уходит, то остановка
		$timeEnd = strtotime($year.'-12-31');
		$gnArr = array();
		while($weekFirst < $timeEnd) {
			array_push($gnArr,
				'('.
			        APP_ID.','.
			        $general_nomer++.','.
			        $week_nomer++.','.
			        'DATE_ADD("'.strftime('%Y-%m-%d', $weekFirst).'", INTERVAL '.$day_print.' DAY),'.
			        'DATE_ADD("'.strftime('%Y-%m-%d', $weekFirst).'", INTERVAL '.$day_public.' DAY),'.
					$polosa_count.
				')'
			);
			$weekFirst += 604800;
		}

		$sql = 'INSERT INTO `_setup_gazeta_nomer` (
					`app_id`,
					`general_nomer`,
					`week_nomer`,
					`day_print`,
					`day_public`,
					`polosa_count`
				) VALUES '.implode(',', $gnArr);
		query($sql);

		xcache_unset(CACHE_PREFIX.'gn');
		_appJsValues();

		$send['year'] = utf8(setup_gn_year($year));
		$send['html'] = utf8(setup_gn_spisok($year));
		jsonSuccess($send);
		break;
	case 'setup_gn_clear':
		if(!$year = _num($_POST['year']))
			jsonError();

		$sql = "DELETE FROM `_setup_gazeta_nomer`
				WHERE `app_id`=".APP_ID."
				  AND (`day_print` LIKE '".$year."-%'
				   OR `day_public` LIKE '".$year."-%')";
		query($sql);

		xcache_unset(CACHE_PREFIX.'gn');
		_appJsValues();

		$send['year'] = utf8(setup_gn_year($year));
		$send['html'] = utf8(setup_gn_spisok($year));
		jsonSuccess($send);
		break;
	case 'setup_gn_add':
		if(!$week_nomer = _num($_POST['week_nomer']))
			jsonError('Некорректно указан номер недели выпуска');
		if(!$general_nomer = _num($_POST['general_nomer']))
			jsonError('Некорректно указан общий номер выпуска');
		if(!preg_match(REGEXP_DATE, $_POST['day_print']))
			jsonError('Некорректно указана дата отправки в печать');
		if(!preg_match(REGEXP_DATE, $_POST['day_public']))
			jsonError('Некорректно указана дата выхода газеты');
		if(!$year = _num($_POST['year']))
			jsonError();
		if(!$polosa_count = _num($_POST['polosa_count']))
			jsonError();
		$day_print = $_POST['day_print'];
		$day_public = $_POST['day_public'];

		$sql = "SELECT `general_nomer`
				FROM `_setup_gazeta_nomer`
				WHERE `app_id`=".APP_ID."
				  AND `general_nomer`=".$general_nomer;
		if(query_value($sql))
			jsonError('Номер газеты <b>'.$general_nomer.'</b> уже есть в списке');

		$sql = "INSERT INTO `_setup_gazeta_nomer` (
					`app_id`,
					`week_nomer`,
					`general_nomer`,
					`day_print`,
					`day_public`,
					`polosa_count`
				) VALUES (
					".APP_ID.",
					".$week_nomer.",
					".$general_nomer.",
					'".$day_print."',
					'".$day_public."',
					".$polosa_count."
				)";
		query($sql);

		xcache_unset(CACHE_PREFIX.'gn');
		_appJsValues();

		$send['year'] = utf8(setup_gn_year($year));
		$send['html'] = utf8(setup_gn_spisok($year, $general_nomer));
		jsonSuccess($send);
		break;
	case 'setup_gn_edit':
		if(!$id = _num($_POST['id']))
			jsonError();
		if(!$week_nomer = _num($_POST['week_nomer']))
			jsonError('Некорректно указан номер недели выпуска');
		if(!$general_nomer = _num($_POST['general_nomer']))
			jsonError('Некорректно указан общий номер выпуска');
		if(!preg_match(REGEXP_DATE, $_POST['day_print']))
			jsonError('Некорректно указана дата отправки в печать');
		if(!preg_match(REGEXP_DATE, $_POST['day_public']))
			jsonError('Некорректно указана дата выхода газеты');
		if(!$year = _num($_POST['year']))
			jsonError();
		if(!$polosa_count = _num($_POST['polosa_count']))
			jsonError();
		$day_print = $_POST['day_print'];
		$day_public = $_POST['day_public'];

		$sql = "SELECT `general_nomer`
				FROM `_setup_gazeta_nomer`
				WHERE `app_id`=".APP_ID."
				  AND `id`!=".$id."
				  AND `general_nomer`=".$general_nomer;
		if(query_value($sql))
			jsonError('Номер газеты <b>'.$general_nomer.'</b> уже есть в списке');

		$sql = "SELECT *
				FROM `_setup_gazeta_nomer`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_setup_gazeta_nomer`
				SET `week_nomer`=".$week_nomer.",
					`general_nomer`=".$general_nomer.",
					`day_print`='".$day_print."',
					`day_public`='".$day_public."',
					`polosa_count`=".$polosa_count."
				WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'gn');
		_appJsValues();

		$send['year'] = utf8(setup_gn_year($year));
		$send['html'] = utf8(setup_gn_spisok($year, $general_nomer));
		jsonSuccess($send);
		break;
	case 'setup_gn_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_setup_gazeta_nomer`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "DELETE FROM `_setup_gazeta_nomer` WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'gn');
		_appJsValues();

		jsonSuccess();
		break;

	case 'setup_zayav_status_add':
		if(!_viewerMenuAccess(16))
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

		xcache_unset(CACHE_PREFIX.'zayav_status');
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
		if(!_viewerMenuAccess(16))
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

		xcache_unset(CACHE_PREFIX.'zayav_status');
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

		xcache_unset(CACHE_PREFIX.'zayav_expense');
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
		$param = _num($_POST['param']);

		if(empty($name))
			jsonError();

		if($dop != 4)
			$param = 0;

		$sql = "INSERT INTO `_zayav_expense_category` (
					`app_id`,
					`name`,
					`dop`,
					`param`,
					`sort`
				) VALUES (
					".APP_ID.",
					'".addslashes($name)."',
					".$dop.",
					".$param.",
					"._maxSql('_zayav_expense_category')."
				)";
		query($sql);

		xcache_unset(CACHE_PREFIX.'zayav_expense');
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
		$param = _num($_POST['param']);

		if(empty($name))
			jsonError();

		if($dop != 4)
			$param = 0;


		$sql = "SELECT * FROM `_zayav_expense_category` WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_zayav_expense_category`
				SET `name`='".addslashes($name)."',
					`dop`=".$dop.",
					`param`=".$param."
				WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'zayav_expense');
		_appJsValues();

		$changes =
			_historyChange('Наименование', $r['name'], $name).
			_historyChange('Доп. поле', _zayavExpenseDop($r['dop']), _zayavExpenseDop($dop)).
			_historyChange('Ведение прикреплённых счетов', _daNet($r['param']), _daNet($param));
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

		xcache_unset(CACHE_PREFIX.'zayav_expense');
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

		xcache_unset(CACHE_PREFIX.'app');

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

		xcache_unset(CACHE_PREFIX.'tovar_category');
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

		xcache_unset(CACHE_PREFIX.'tovar_category');
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

		xcache_unset(CACHE_PREFIX.'tovar_category');
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



		xcache_unset(CACHE_PREFIX.'tovar_category');
		_appJsValues();

		$send['html'] = utf8(setup_tovar_category_spisok());
		jsonSuccess($send);
		break;

	case 'setup_template_add':
		if(!$name = _txt($_POST['name']))
			jsonError('Не указано название');

		$sql = "INSERT INTO `_template` (
					`app_id`,
					`name`,
					`name_link`,
					`name_file`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					'".addslashes($name)."',
					'".addslashes($name)."',
					'".addslashes($name)."',
					".VIEWER_ID."
				)";
		query($sql);
		$insert_id = query_insert_id('_template');

		$send['id'] = $insert_id;
		jsonSuccess($send);
		break;
	case 'setup_template_save':
		if(!$id = _num($_POST['id']))
			jsonError();
		if(!$name = _txt($_POST['name']))
			jsonError('Не указано название шаблона');
		if(!$name_link = _txt($_POST['name_link']))
			jsonError('Не указан текст ссылки');
		if(!$name_file = _txt($_POST['name_file']))
			jsonError('Не указано имя файла документа');

		$sql = "UPDATE `_template`
				SET `name`='".addslashes($name)."',
					`name_link`='".addslashes($name_link)."',
					`name_file`='".addslashes($name_file)."'
				WHERE `id`=".$id;
		query($sql);

		jsonSuccess();
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

