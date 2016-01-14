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
		if(!query_value($sql, GLOBAL_MYSQL_CONNECT))
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
		query($sql, GLOBAL_MYSQL_CONNECT);

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
		query($sql, GLOBAL_MYSQL_CONNECT);

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
		query($sql, GLOBAL_MYSQL_CONNECT);

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
			if($r = query_assoc($sql, GLOBAL_MYSQL_CONNECT)) {
				if($r['worker'] && $r['ws_id'] == WS_ID)
					jsonError('Этот пользователь уже является сотрудником Вашей организации');
				if($r['worker'] && $r['ws_id'])
					jsonError('Этот пользователь уже является сотрудником другой организации');
			}

			_viewer($viewer_id);
			$sql = "UPDATE `_vkuser`
					SET `ws_id`=".WS_ID.",
						`worker`=1
					WHERE `app_id`=".APP_ID."
					  AND `viewer_id`=".$viewer_id;
			query($sql, GLOBAL_MYSQL_CONNECT);

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
						`ws_id`,
						`viewer_id`,
						`first_name`,
						`last_name`,
						`sex`,
						`post`,
						`photo`,
						`worker`
					) VALUES (
						".APP_ID.",
						".WS_ID.",
						".$viewer_id.",
						'".addslashes($first_name)."',
						'".addslashes($last_name)."',
						".$sex.",
						'".addslashes($post)."',
						'http://vk.com/images/camera_c.gif',
						1
					)";
			query($sql, GLOBAL_MYSQL_CONNECT);
		}

		_globalValuesJS();

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

		if($u['viewer_ws_id'] != WS_ID)
			jsonError('Сотрудник не из этой организации');

		$sql = "UPDATE `_vkuser`
				SET `ws_id`=0,
					`worker`=0
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$viewer_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		$sql = "DELETE FROM `_vkuser_rule`
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$viewer_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'viewer_'.$viewer_id);
		_globalValuesJS();

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
		if($u['viewer_ws_id'] != WS_ID)
			jsonError();

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
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'viewer_'.$viewer_id);
//		GvaluesCreate();

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
		if($u['viewer_ws_id'] != WS_ID)
			jsonError();

		if(!$u['pin'])
			jsonError();

		$sql = "UPDATE `_vkuser`
				SET `pin`=''
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$viewer_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

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
	case 'RULE_APP_ENTER'://разрешать сотруднику вход в приложение
		$_POST['h1'] = 1002;
		$_POST['h0'] = 1003;

		if(!setup_worker_rule_save($_POST))
			jsonError();

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
	case 'RULE_HISTORY_VIEW'://разрешать сотруднику просматривать историю действий
		$_POST['h1'] = 1012;
		$_POST['h0'] = 1013;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;
	case 'RULE_INVOICE_TRANSFER'://разрешать сотруднику видеть историю переводов по расчётным счетам
		$_POST['h1'] = 1014;
		$_POST['h0'] = 1015;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;
	case 'RULE_INCOME_VIEW'://разрешать сотруднику видеть историю платежей
		$_POST['h1'] = 1016;
		$_POST['h0'] = 1017;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;

	case 'setup_rekvisit':
		if(!RULE_SETUP_REKVISIT)
			jsonError();

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
				FROM `_ws`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".WS_ID;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_ws`
				SET `name`='".addslashes($name)."',
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
				WHERE `id`=".WS_ID;
		query($sql, GLOBAL_MYSQL_CONNECT);

		$changes =
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

	case 'setup_invoice_add':
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
		_globalValuesJS();

		_history(array(
			'type_id' => 1022,
			'v1' => $name
		));

		$send['html'] = utf8(setup_invoice_spisok());
		jsonSuccess($send);
		break;
	case 'setup_invoice_edit':
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
		_globalValuesJS();

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

		$changes =
			_historyChange('Наименование', $r['name'], $name).
			_historyChange('Описание', _br($r['about']), _br($about)).
			_historyChange('Подтверждение поступления на счёт', _daNet($r['confirm_income']), _daNet($income)).
			_historyChange('Подтверждение перевода', _daNet($r['confirm_transfer']), _daNet($transfer)).
			_historyChange('Видимость для сотрудников', $r['visible'], $visible, $old, $new);

		if($changes)
			_history(array(
				'type_id' => 1023,
				'v1' => $name,
				'v2' => '<table>'.$changes.'</table>'
			));

		$send['html'] = utf8(setup_invoice_spisok());
		jsonSuccess($send);
		break;

	case 'expense_category_add':
		$name = _txt($_POST['name']);
		$worker_use = _bool($_POST['worker_use']);

		if(empty($name))
			jsonError();

		$sql = "INSERT INTO `_money_expense_category` (
					`app_id`,
					`ws_id`,
					`name`,
					`worker_use`,
					`sort`
				) VALUES (
					".APP_ID.",
					".WS_ID.",
					'".addslashes($name)."',
					".$worker_use.",
					"._maxSql('_money_expense_category')."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'expense'.WS_ID);
		_globalValuesJS();

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
		$worker_use = _bool($_POST['worker_use']);

		if(empty($name))
			jsonError();

		$sql = "SELECT *
				FROM `_money_expense_category`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_money_expense_category`
				SET `name`='".addslashes($name)."',
					`worker_use`=".$worker_use."
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'expense'.WS_ID);
		_globalValuesJS();

		$changes =
			_historyChange('Наименование', $r['name'], $name).
			_historyChange('Список сотрудников', $r['worker_use'] ? 'да' : 'нет', $worker_use ? 'да' : 'нет');

		if($changes)
			_history(array(
				'type_id' => 1021,
				'v1' => $name,
				'v2' => '<table>'.$changes.'</table>'
			));

		$send['html'] = utf8(setup_expense_spisok());
		jsonSuccess($send);
		break;
	case 'expense_category_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_money_expense_category`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "SELECT COUNT(`id`)
				FROM `_money_expense`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `category_id`=".$id;
		if(query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "DELETE FROM `_money_expense_category` WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'expense'.WS_ID);
		_globalValuesJS();

		_history(array(
			'type_id' => 1020,
			'v1' => $r['name']
		));

		$send['html'] = utf8(setup_expense_spisok());
		jsonSuccess($send);
		break;

	case 'setup_product_add':
		if(!$name = _txt($_POST['name']))
			jsonError();

		$sql = "INSERT INTO `_product` (
					`app_id`,
					`ws_id`,
					`name`
				) VALUES (
					".APP_ID.",
					".WS_ID.",
					'".addslashes($name)."'
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'product'.WS_ID);
		_globalValuesJS();

		_history(array(
			'type_id' => 1037,
			'v1' => $name
		));

		$send['html'] = utf8(setup_product_spisok());
		jsonSuccess($send);
		break;
	case 'setup_product_edit':
		if(!$product_id = _num($_POST['id']))
			jsonError();
		if(!$name = _txt($_POST['name']))
			jsonError();

		$sql = "SELECT *
				FROM `_product`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `id`=".$product_id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_product`
		        SET `name`='".addslashes($name)."'
		        WHERE `id`=".$product_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'product'.WS_ID);
		_globalValuesJS();

		if($changes = _historyChange('Наименование', $r['name'], $name))
			_history(array(
				'type_id' => 1038,
				'v1' => $name,
				'v2' => '<table>'.$changes.'</table>'
			));

		$send['html'] = utf8(setup_product_spisok());
		jsonSuccess($send);
		break;
	case 'setup_product_del':
		if(!$product_id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT * FROM `_product` WHERE `id`=".$product_id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "SELECT COUNT(`id`) FROM `_product_sub` WHERE `product_id`=".$product_id;
		if(query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "SELECT COUNT(`id`) FROM `_zayav_product` WHERE `product_id`=".$product_id;
		if(query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "DELETE FROM `_product` WHERE `id`=".$product_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'product'.WS_ID);
		_globalValuesJS();

		_history(array(
			'type_id' => 1039,
			'v1' => $r['name']
		));

		jsonSuccess();
		break;
	case 'setup_product_sub_add':
		if(!$product_id = _num($_POST['product_id']))
			jsonError();
		if(!$name = _txt($_POST['name']))
			jsonError();

		$sql = "SELECT * FROM `_product` WHERE `id`=".$product_id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "INSERT INTO `_product_sub` (
					`app_id`,
					`ws_id`,
					`product_id`,
					`name`
				) VALUES (
					".APP_ID.",
					".WS_ID.",
					".$product_id.",
					'".addslashes($name)."'
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'product_sub'.WS_ID);
		_globalValuesJS();

		_history(array(
			'type_id' => 1040,
			'v1' => _product($product_id),
			'v2' => $name
		));

		$send['html'] = utf8(setup_product_sub_spisok($product_id));
		jsonSuccess($send);
		break;
	case 'setup_product_sub_edit':
		if(!$id = _num($_POST['id']))
			jsonError();
		if(!$name = _txt($_POST['name']))
			jsonError();

		$sql = "SELECT * FROM `_product_sub` WHERE `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_product_sub`
				SET `name`='".addslashes($name)."'
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'product_sub'.WS_ID);
		_globalValuesJS();

		if($changes = _historyChange('Наименование', $r['name'], $name))
			_history(array(
				'type_id' => 1041,
				'v1' => _product($r['product_id']),
				'v2' => '<table>'.$changes.'</table>'
			));

		jsonSuccess();
		break;
	case 'setup_product_sub_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT * FROM `_product_sub` WHERE `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "SELECT COUNT(`id`) FROM `_zayav_product` WHERE `product_sub_id`=".$id;
		if(query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "DELETE FROM `_product_sub` WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'product_sub'.WS_ID);
		_globalValuesJS();

		_history(array(
			'type_id' => 1042,
			'v1' => _product($r['product_id']),
			'v2' => $r['name']
		));

		jsonSuccess();
		break;

	case 'setup_zayav_expense_add':
		if(!SA)
			jsonError();

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
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'zayav_expense'.APP_ID);
		_globalValuesJS();

		_history(array(
			'type_id' => 1027,
			'v1' => $name
		));

		$send['html'] = utf8(setup_zayav_expense_spisok());
		jsonSuccess($send);
		break;
	case 'setup_zayav_expense_edit':
		if(!SA)
			jsonError();

		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);
		$dop = _num($_POST['dop']);

		if(empty($name))
			jsonError();

		$sql = "SELECT * FROM `_zayav_expense_category` WHERE `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_zayav_expense_category`
				SET `name`='".addslashes($name)."',
					`dop`=".$dop."
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'zayav_expense'.APP_ID);
		_globalValuesJS();

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
		if(!SA)
			jsonError();

		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT * FROM `_zayav_expense_category` WHERE `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "SELECT COUNT(`id`) FROM `_zayav_expense` WHERE `category_id`=".$id;
		if(query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "DELETE FROM `_zayav_expense_category` WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'zayav_expense'.APP_ID);
		_globalValuesJS();

		_history(array(
			'type_id' => 1029,
			'v1' => $r['name']
		));

		$send['html'] = utf8(setup_zayav_expense_spisok());
		jsonSuccess($send);
		break;

}
