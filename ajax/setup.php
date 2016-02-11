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
				'text' => utf8('��������� ������������ ���������� ������� �����.<br />'.
					'���������� ���� ����� ����� ����� 30 �����.<br /><br />'.
					'���� �� ������ ���� ���-���, ���������� � ������������ ��� ��� ������.')
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
			jsonError('�������� ���-���');

		xcache_unset($key);
		$_SESSION[PIN_TIME_KEY] = time() + PIN_TIME_LEN;

		jsonSuccess();
		break;

	case 'setup_my_pinset':
		$pin = _txt(@$_POST['pin']);
		if(PIN || !$pin || strlen($pin) < 3 || strlen($pin) > 10)
			jsonError('������������ ����� �������');

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
			jsonError('�������� ������ ���-���');

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
			jsonError('�������� ������ ���-���');

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
					jsonError('���� ������������ ��� �������� ����������� ����� �����������');
				if($r['worker'] && $r['ws_id'])
					jsonError('���� ������������ ��� �������� ����������� ������ �����������');
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

		_globalJsValues();

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
			jsonError('��������� �������� �������������');

		if(!$u['viewer_worker'])
			jsonError('������������ ��� �� �������� �����������');

		if($u['viewer_ws_id'] != WS_ID)
			jsonError('��������� �� �� ���� �����������');

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
		_globalJsValues();

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

		$changes =
			_historyChange('���', $u['viewer_first_name'], $first_name).
			_historyChange('�������', $u['viewer_last_name'], $last_name).
			_historyChange('��������', $u['viewer_middle_name'], $middle_name).
			_historyChange('���������', $u['viewer_post'], $post);

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

	case 'RULE_SALARY_SHOW'://���������� � ������ �/� �����������
		$_POST['h1'] = 1036;
		$_POST['h0'] = 1035;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;
	case 'RULE_SALARY_ZAYAV_ON_PAY'://��������� �/� �� ������ ��� ���������� �����
		$_POST['h1'] = 1046;
		$_POST['h0'] = 1047;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;
	case 'RULE_SALARY_BONUS'://���������� �������
		$_POST['h1'] = 1043;
		$_POST['h0'] = 1044;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;
	case 'salary_bonus_sum'://��������� ������� ����������
		if(!$worker_id = _num($_POST['worker_id']))
			jsonError();

		if(!$sum = _cena($_POST['sum']))
			jsonError('����������� ������� ��������');

		if(!$r = _viewerWorkerQuery($worker_id))
			jsonError('���������� �� ����������');

		if($r['salary_bonus_sum'] == $sum)
			jsonError('�������� �� ���� ��������');

		$sql = "UPDATE `_vkuser`
				SET `salary_bonus_sum`=".$sum."
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `viewer_id`=".$worker_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'viewer_'.$worker_id);

		_history(array(
			'type_id' => 1045,
			'worker_id' => $worker_id,
			'v1' => $sum
		));

		jsonSuccess();
		break;
	case 'RULE_APP_ENTER'://��������� ���������� ���� � ����������
		$_POST['h1'] = 1002;
		$_POST['h0'] = 1003;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;
	case 'RULE_SETUP_WORKER'://��������� ���������� �������� ������ ����������
		$_POST['h1'] = 1004;
		$_POST['h0'] = 1005;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		_workerRuleQuery($_POST['viewer_id'], 'RULE_SETUP_RULES', 0);

		jsonSuccess();
		break;
	case 'RULE_SETUP_RULES'://��������� ���������� ����������� ����� ������ �����������
		$_POST['h1'] = 1006;
		$_POST['h0'] = 1007;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;
	case 'RULE_SETUP_REKVISIT'://��������� ���������� �������� ��������� �����������
		$_POST['h1'] = 1008;
		$_POST['h0'] = 1009;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;
	case 'RULE_SETUP_INVOICE'://��������� ���������� ��������� ���������� �������
		$_POST['h1'] = 1010;
		$_POST['h0'] = 1011;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;
	case 'RULE_HISTORY_VIEW'://��������� ���������� ������������� ������� ��������
		if(!RULE_SETUP_RULES)
			jsonError();

		if(!$viewer_id = _num($_POST['viewer_id']))
			jsonError();

		$new = _num($_POST['v']);

		$arr = array(
			0 => '���',
			1 => '������ ����',
			2 => '��� �������'
		);

		$old = _viewerRule($viewer_id, 'RULE_HISTORY_VIEW');

		if($old == $new)
			jsonError();

		_workerRuleQuery($viewer_id, 'RULE_HISTORY_VIEW', $new);

		_history(array(
			'type_id' => 1012,
			'worker_id' => $viewer_id,
			'v1' => '<table>'.
						_historyChange('����� ������� ��������:', $arr[$old], $arr[$new]).
					'</table>'
		));


		jsonSuccess();
		break;
	case 'RULE_INVOICE_TRANSFER'://��������� ���������� ������ ������� ��������� �� ��������� ������
		$_POST['h1'] = 1014;
		$_POST['h0'] = 1015;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;
	case 'RULE_INCOME_VIEW'://��������� ���������� ������ ������� ��������
		$_POST['h1'] = 1016;
		$_POST['h0'] = 1017;

		if(!setup_worker_rule_save($_POST))
			jsonError();

		jsonSuccess();
		break;

	case 'RULE_MY_PAY_SHOW_PERIOD'://��� ���������: ���������� �������: �� ����, ������, �����
		_workerRuleQuery(VIEWER_ID, 'RULE_MY_PAY_SHOW_PERIOD', _num($_POST['v']));

		xcache_unset(CACHE_PREFIX.'viewer_'.VIEWER_ID);
		xcache_unset(CACHE_PREFIX.'viewer_rule_'.VIEWER_ID);

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
			_historyChange('�������� �����������', $r['name'], $name).
			_historyChange('������������ ������������ ����', $r['name_yur'], $name_yur).
			_historyChange('����', $r['ogrn'], $ogrn).
			_historyChange('���', $r['inn'], $inn).
			_historyChange('���', $r['kpp'], $kpp).
			_historyChange('���������� ��������', $r['phone'], $phone).
			_historyChange('����', $r['fax'], $fax).
			_historyChange('����������� �����', $r['adres_yur'], $adres_yur).
			_historyChange('����� �����', $r['adres_ofice'], $adres_ofice).
			_historyChange('����� ������', $r['time_work'], $time_work).
			_historyChange('������������ �����', $r['bank_name'], $bank_name).
			_historyChange('���', $r['bank_bik'], $bank_bik).
			_historyChange('��������� ����', $r['bank_account'], $bank_account).
			_historyChange('����������������� ����', $r['bank_account_corr'], $bank_account_corr);

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
				FROM `_zayav_type`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "SELECT COUNT(*)
				FROM `_zayav_type_active`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `type_id`=".$id;
		if($active = !query_value($sql, GLOBAL_MYSQL_CONNECT)) {
			$sql = "INSERT INTO `_zayav_type_active` (
						`app_id`,
						`ws_id`,
						`type_id`
					) VALUES (
						".APP_ID.",
						".WS_ID.",
						".$id."
					)";
		} else {
			$sql = "DELETE FROM `_zayav_type_active`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND `type_id`=".$id;
		}

		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'service'.WS_ID);
		_globalJsValues();

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
				FROM `_zayav_type`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_zayav_type`
				SET `name`='".addslashes($name)."',
					`head`='".addslashes($head)."',
					`about`='".addslashes($about)."'
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'service'.WS_ID);
		_globalJsValues();

		jsonSuccess();
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
		_globalJsValues();

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
		_globalJsValues();

		$changes =
			_historyChange('������������', $r['name'], $name).
			_historyChange('������ �����������', $r['worker_use'] ? '��' : '���', $worker_use ? '��' : '���');

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
		_globalJsValues();

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
		_globalJsValues();

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
		_globalJsValues();

		if($changes = _historyChange('������������', $r['name'], $name))
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
		_globalJsValues();

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
		_globalJsValues();

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
		_globalJsValues();

		if($changes = _historyChange('������������', $r['name'], $name))
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
		_globalJsValues();

		_history(array(
			'type_id' => 1042,
			'v1' => _product($r['product_id']),
			'v2' => $r['name']
		));

		jsonSuccess();
		break;

	case 'setup_zayav_status_add':
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
					`ws_id`,
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
					".WS_ID.",
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
		query($sql, GLOBAL_MYSQL_CONNECT);

		$id = query_insert_id('_zayav_status', GLOBAL_MYSQL_CONNECT);

		setup_status_next_insert($id, $_POST);

		xcache_unset(CACHE_PREFIX.'zayav_status'.WS_ID);
		_wsJsValues();
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
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);
		$color = _txt($_POST['color']);
		$default = _bool($_POST['default']);
		$nouse = _bool($_POST['nouse']);
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
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_zayav_status`
				SET `name`='".addslashes($name)."',
					`about`='".addslashes($about)."',
					`color`='".$color."',
					`default`='".$default."',
					`nouse`='".$nouse."',
					`srok`='".$srok."',
					`executer`='".$executer."',
					`accrual`='".$accrual."',
					`remind`='".$remind."',
					`day_fact`='".$day_fact."'
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		$sql = "DELETE FROM `_zayav_status_next` WHERE `status_id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);
		setup_status_next_insert($id, $_POST);

		xcache_unset(CACHE_PREFIX.'zayav_status'.WS_ID);
		_wsJsValues();
/*
		$changes =
			_historyChange('������������', $r['name'], $name).
			_historyChange('�������������� ����', $r['dop'], $dop, _zayavExpenseDop($r['dop']), _zayavExpenseDop($dop));
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
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "SELECT COUNT(`id`) FROM `_zayav` WHERE `status`=".$id;
		if(query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_zayav_status` WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'zayav_expense'.APP_ID);
		_globalJsValues();

		_history(array(
			'type_id' => 1029,
			'v1' => $r['name']
		));

		$send['html'] = utf8(setup_zayav_expense_spisok());
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
			".WS_ID.",
			".$status_id.",
			".$i."
		)";
	$sql = "INSERT INTO `_zayav_status_next` (
				`app_id`,
				`ws_id`,
				`status_id`,
				`next_id`
			) VALUES ".implode(',', $values);
	query($sql, GLOBAL_MYSQL_CONNECT);
}
