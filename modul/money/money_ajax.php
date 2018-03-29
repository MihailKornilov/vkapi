<?php
switch(@$_POST['op']) {
	case 'accrual_add':
		_accrualAdd($_POST);
//		_salaryZayavBonus($zayav_id);
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

		//������ �������
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
			_historyChange('�����', _cena($r['sum']), $sum).
			_historyChange('��������', $r['about'], $about))
			_history(array(
				'type_id' => 90,
				'zayav_id' => $r['zayav_id'],
				'v1' => '<table>'.$changes.'</table>'
			));

		jsonSuccess();
		break;
	case 'accrual_del':
		if(!$id = _num($_POST['id']))
			jsonError('������������ id ����������');

		$sql = "SELECT *
				FROM `_money_accrual`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('���������� �� ����������');

		if($r['schet_id'])
			jsonError('���������� ��������� � ����� �� ������');

		if($r['dogovor_id'])
			jsonError('���������� ��������� � ��������');

		$sql = "UPDATE `_money_accrual`
				SET `deleted`=1,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;
		query($sql);

		_zayavBalansUpdate($r['zayav_id']);
		_salaryZayavCheck($r['zayav_id']);
		_salaryZayavBonus($r['zayav_id']);

		//�������� ������� ��� �������
		if($r['client_id'])
			_balans(array(
				'action_id' => 26,
				'client_id' => $r['client_id'],
				'zayav_id' => $r['zayav_id'],
				'sum' => $r['sum'],
				'about' => $r['about']
			));

		_history(array(
			'type_id' => $r['zayav_id'] ? 77 : 134,
			'zayav_id' => $r['zayav_id'],
			'client_id' => $r['client_id'],
			'v1' => _cena($r['sum']),
			'v2' => $r['about']
		));

		jsonSuccess();
		break;

	case 'income_spisok'://������ ��������
		$data = income_spisok($_POST);
		$send['spisok'] = utf8($data['spisok']);
		if($data['filter']['page'] == 1)
			$send['path'] = utf8(income_path($data['filter']['period']));
		jsonSuccess($send);
		break;
	case 'income_add':
		if(!$invoice_id = $_POST['invoice_id'])
			jsonError('�� ������ ��������� ����');
		if(!$sum = _cena($_POST['sum']))
			jsonError('����������� ������� �����');

		$dtime_add = $_POST['dtime_add'];//todo ��� ����� �� ��������
		$about = _txt($_POST['about']);

		$client_id = _num($_POST['client_id']);
		$zayav_id = _num($_POST['zayav_id']);
		$confirm = _bool($_POST['confirm']);
		$prepay = _bool($_POST['prepay']);
		$place_id = _num(@$_POST['place_id']);
		$place_other = !$place_id ? _txt(@$_POST['place_other']) : '';
		$remind_ids = _ids($_POST['remind_ids']);

		//� ������������ ������� � � ������� ��� ������� ����������� ����������� ��������
		if(!$zayav_id && !$about)
			jsonError('������� ��������');

		if($zayav_id) {
			if(!$r = _zayavQuery($zayav_id))
				jsonError('������ �� ����������');
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
				`viewer_id_add`,
				`dtime_add`
			) VALUES (
				".APP_ID.",
				".$invoice_id.",
				".$sum.",
				'".addslashes($about)."',
				".$zayav_id.",
				".$client_id.",
				".$confirm.",
				".$prepay.",
				".VIEWER_ID.",
				'".$dtime_add.' '.strftime('%H:%M:%S')."'
			)";
		query($sql);

		$insert_id = query_insert_id('_money_income');

		//������ ��� ���������� �����
		if(!$confirm)// && $dtime_add == TODAY
			_balans(array(
				'action_id' => 1,
				'invoice_id' => $invoice_id,
				'sum' => $sum,
				'income_id' => $insert_id
			));

		$about = ($prepay ? '����������' : '').
				 ($prepay && $about ? '. ' : '').
				 $about;

		//������ ��� �������
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

			//������� ��������� �������� ����������� ������������
			if($remind_ids)
				_remind_active_to_ready($remind_ids);

			//��������, ���� ������ �������� ���������, ��������� �/� ���������� �� ����������� ������, ���� ����� ����
			_salaryZayavCheck($zayav_id);
			_salaryZayavBonus($zayav_id);
		}

		_history(array(
			'type_id' => $zayav_id ? 135 : ($client_id ? 136 : 78),
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
			jsonError('������� �� ����������');

		//���� ����� ��� ����������
		if($r['confirm'] == 2 && !DEBUG)
			jsonError('����� ��� ����������');

		if(!DEBUG)
			if($r['confirm'] != 1 && TODAY != substr($r['dtime_add'], 0, 10))
				jsonError('����� ��� �������� ������� �������');

		$sql = "UPDATE `_money_income`
				SET `deleted`=1,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;
		query($sql);

		_zayavBalansUpdate($r['zayav_id']);
		_salaryZayavBonus($r['zayav_id']);

		//������ ��� ���������� �����
		_balans(array(
			'action_id' => 2,
			'invoice_id' => $r['invoice_id'],
			'sum' => $r['sum'],
			'income_id' => $r['id']
		));

		$about = ($r['prepay'] ? '����������' : '').
				 ($r['prepay'] && $r['about'] ? '. ' : '').
				 $r['about'];

		//������ ��� �������
		if($r['client_id'])
			_balans(array(
				'action_id' => 28,
				'schet_id' => $r['schet_id'],
				'client_id' => $r['client_id'],
				'sum' => $r['sum'],
				'about' => $about
			));

		//����� ��� ��������� �� �����
		_schetPaySumCorrect($r['schet_id']);

		//���� ����������� ������� ������
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
	case 'income_refund'://�������� �������� ������������� �������
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

		$about = $r['about'].'<br />������� ������ �� <u>'.$dtime.'</u>.';

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
	case 'income_confirm'://������������� ����������� �� ����
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
	case 'income_unbind_load'://��������� ������ � ������� ��� �������
		if(!SA)
			jsonError('��� ����');
		if(!$income_id = _num($_POST['income_id']))
			jsonError('������������ id �������');

		$sql = "SELECT *
				FROM `_money_income`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `id`=".$income_id;
		if(!$r = query_assoc($sql))
			jsonError('������� �� ����������');

		$send['html'] = utf8(
			'<div class="_info">'.
				'����� �� ����� <b>'._sumSpace($r['sum']).'</b> ���.'.
				'<br />'.
				'����: '.FullDataTime($r['dtime_add']).
			'</div>'.
			'<table class="bs10">'.
				'<tr><td class="label r w100 top">������:<td>'.($r['client_id'] ? _clientVal($r['client_id'], 'link') : '<i class="grey">�� ��������</i>').
				'<tr><td class="label r">������:<td>'.($r['zayav_id'] ? '��������' : '<i class="grey">�� ��������</i>').
				'<tr><td class="label r">�����:<td>'.($r['tovar_id'] ? '��������' : '<i class="grey">�� ��������</i>').
				'<tr><td class="label r">�������:<td>'.($r['dogovor_id'] ? _check('pay_unbind_dogovor', '��������') : '<i class="grey">�� ��������</i>').
				'<tr><td class="label r">���� �� ������:<td>'.($r['schet_id'] ? _check('pay_unbind_schet', '��������') : '<i class="grey">�� ��������</i>').
			'</table>'
		);

		jsonSuccess($send);
		break;
	case 'income_unbind'://������� �������
		if(!SA)
			jsonError('��� ����');
		if(!$income_id = _num($_POST['income_id']))
			jsonError('������������ id �������');

		$sql = "SELECT *
				FROM `_money_income`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `id`=".$income_id;
		if(!$r = query_assoc($sql))
			jsonError('������� �� ����������');

		//������� �� ��������
		if(_bool($_POST['dogovor'])) {
			if(!$r['dogovor_id'])
				jsonError('����� �� �������� � ��������');

			$sql = "SELECT *
					FROM `_zayav_dogovor`
					WHERE `app_id`=".APP_ID."
					  AND `id`=".$r['dogovor_id'];
			if(!query_assoc($sql))
				jsonError('�������� �� ����������');

			$sql = "UPDATE `_money_income`
					SET `dogovor_id`=0
					WHERE `id`=".$income_id;
			query($sql);

			_history(array(
				'type_id' => 168,
				'client_id' => $r['client_id'],
				'zayav_id' => $r['zayav_id'],
				'dogovor_id' => $r['dogovor_id'],
				'v1' => _cena($r['sum']),
				'v2' => FullDataTime($r['dtime_add'])
			));
		}

		//������� �� ����� �� ������
		if(_bool($_POST['schet'])) {
			if(!$r['schet_id'])
				jsonError('����� �� �������� � ����� �� ������');

			$sql = "SELECT *
					FROM `_schet_pay`
					WHERE `app_id`=".APP_ID."
					  AND !`deleted`
					  AND `id`=".$r['schet_id'];
			if(!query_assoc($sql))
				jsonError('����� �� ������ �� ����������');

			$sql = "UPDATE `_money_income`
					SET `schet_id`=0,
						`schet_paid_day`='0000-00-00'
					WHERE `id`=".$income_id;
			query($sql);

			_schetPaySumCorrect($r['schet_id']);

			_history(array(
				'type_id' => 138,
				'client_id' => $r['client_id'],
				'zayav_id' => $r['zayav_id'],
				'schet_id' => $r['schet_id'],
				'v1' => _cena($r['sum']),
				'v2' => FullDataTime($r['dtime_add'])
			));
		}

		jsonSuccess();
		break;

	case 'refund_add'://�������� ��������
		if(!$invoice_id = _num($_POST['invoice_id']))
			jsonError('�� ������ ����');
		if(!$sum = _cena($_POST['sum']))
			jsonError('����������� ������� �����');
		if(!$about = _txt($_POST['about']))
			jsonError('������� ������� ��������');

		$client_id = _num($_POST['client_id']);

		if($zayav_id = _num($_POST['zayav_id'])) {
			if(!$z = _zayavQuery($zayav_id))
				jsonError();
			$client_id = $z['client_id'];
		}

		if(!$client_id && !$zayav_id)
			jsonError('�� ������ ������ ���� ������');

		$sql = "INSERT INTO `_money_refund` (
					`app_id`,
					`client_id`,
					`zayav_id`,
					`invoice_id`,
					`sum`,
					`about`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$client_id.",
					".$zayav_id.",
					".$invoice_id.",
					".$sum.",
					'".addslashes($about)."',
					".VIEWER_ID."
				)";
		query($sql);

		$insert_id = query_insert_id('_money_refund');

		_zayavBalansUpdate($zayav_id);
//		_salaryZayavBonus($zayav_id);

		//������ ��� ���������� �����
		_balans(array(
			'action_id' => 13,
			'invoice_id' => $invoice_id,
			'sum' => $sum,
			'about' => $about
		));

		//�������� ������� ��� �������
		if($client_id)
			_balans(array(
				'action_id' => 29,
				'client_id' => $client_id,
				'sum' => $sum,
				'about' => $about
			));

		_history(array(
			'type_id' => $zayav_id ? 75 : 137,
			'zayav_id' => $zayav_id,
			'client_id' => $client_id,
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

		if(!DEBUG && TODAY != substr($r['dtime_add'], 0, 10))
			jsonError();

		$sql = "UPDATE `_money_refund`
				SET `deleted`=1,
					`viewer_id_del`=".VIEWER_ID.",
					`dtime_del`=CURRENT_TIMESTAMP
				WHERE `id`=".$id;
		query($sql);

		//������ �������� ������������� �������, ���� ����
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

		//�������� ������� ��� �������
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
	case 'refund_spisok'://������ ���������
		$data = _refund_spisok($_POST);
		$send['spisok'] = utf8($data['spisok']);
		jsonSuccess($send);
		break;

	case 'expense_spisok':
		$data = expense_spisok($_POST);
		$send['spisok'] = utf8($data['spisok']);
		if($data['filter']['page'] == 1) {
			$send['mon'] = _sel(expenseMonthSum($_POST));
//			if(VIEWER_ADMIN)
				$send['graf'] = expense_graf($data['filter'], 'arr');
		}
		jsonSuccess($send);
		break;
	case 'expense_add':
		if(!$invoice_id = _num($_POST['invoice_id']))
			jsonError('�� ������ ��������� ����');
		if(!$sum = _cena($_POST['sum']))
			jsonError('������������ �����');

		//����������� ������ ���� ������� ���������, ���� �������� �������
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
		
		

		expense_dtime_old_update_for_kupez($invoice_id, $insert_id, $sum);//todo ������ �����������, ���� ����������� ����


		//������� ������� ��� ���������� �����
		_balans(array(
			'action_id' => 6,
			'invoice_id' => $invoice_id,
			'sum' => $sum,
			'expense_id' => $insert_id,
			'about' => $about
		));

		//������� ������� ��� ����������
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
			jsonError('������������ id �������');
		if(!$sum = _cena($_POST['sum']))
			jsonError('������������ �����');

		//����������� ������ ���� ������� ���������, ���� �������� �������
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
			jsonError('������ ��� �����');

		if($r['sum'] != $sum && _monthLost($r['dtime_add']))
			jsonError('����� ������� ����� �������� � ������� 30 ����');

		$sql = "UPDATE `_money_expense`
				SET `about`='".addslashes($about)."',
					`category_id`=".$category_id.",
					`category_sub_id`=".$category_sub_id.",
					`sum`=".$sum.",
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

		expense_dtime_old_update_for_kupez($r['invoice_id'], $id, $sum);

		if($r['sum'] != $sum)
			_balans(array(
				'action_id' => 9,
				'invoice_id' => $r['invoice_id'],
				'sum' => $sum,
				'sum_old' => $r['sum'],
				'expense_id' => $id,
				'about' => $r['about']
			));

		if($changes =
			_historyChange('���������',
				$r['category_id'] ? _expense($r['category_id']).($r['category_sub_id'] ? ': '._expenseSub($r['category_sub_id']) : '') : '',
				$category_id ? _expense($category_id).($category_sub_id ? ': '._expenseSub($category_sub_id) : '') : '').
			_historyChange('�����', _sumSpace($r['sum']), _sumSpace($sum)).
			_historyChange('��������', $r['about'], $about).
			_historyChange('���������', $r['worker_id'] ? _viewer($r['worker_id'], 'viewer_name') : '', $worker_id ? _viewer($worker_id, 'viewer_name') : '').
			_historyChange('�����', _daNet($r['salary_avans']),  _daNet($salary_avans)).
			_historyChange(LIST_VYDACI,
					$r['salary_list_id'] ? '�'.$list[$r['salary_list_id']] : '',
					$salary_list_id ? '�'.$list[$salary_list_id] : '').
			_historyChange('�����', $mon_old, $mon_new))
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

		$sql = "SELECT *
				FROM `_money_expense`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$ex = explode(' ', $r['dtime_add']);
		$r['dtime_add'] = $ex[0];
		$r['sum'] = round($r['sum'], 2);
		$r['about'] = utf8($r['about']);
		$r['attach'] = _attachArr($r['attach_id']);
		//����������� �������������� ����� �������: � ������� 30 ����
		$r['sum_edit'] = !_monthLost($r['dtime_add']);
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
			jsonError('������� �� ����������');

		if(TODAY != substr($r['dtime_add'], 0, 10) && !DEBUG)
			if(APP_ID != 3495523)   //todo �������� ��� �����
				jsonError('����� ��� �������� ������� �������');

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

		//������� ������� ��� ����������
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

	case 'schet_pay_load'://�������� ������ ��� ��������/�������������� ����� �� ������
		$send['org_id'] = _app('org_default');
		$send['bank_id'] = _app('bank_default');
		$nomer = 0;
		$date_create = '';
		$act_date = '';
		$head = '����� �ר� �� ������';
		$send['noedit'] = 0;
		$send['content'] = array();
		$cartridge_ids = _ids(@$_POST['cartridge_ids']);//������ id ����������, ���� ����������
		$gn_ids = _ids(@$_POST['gn_ids']);//������ id ������� ������, ���� ����������

		$client_id = _num($_POST['client_id']);

		if($zayav_id = _num($_POST['zayav_id'])) {
			if(!$z = _zayavQuery($zayav_id))
				jsonError('������ id'.$zayav_id.' �� ����������');
			$client_id = $z['client_id'];
		}

		if($schet_id = _num($_POST['schet_id'])) {
			if(!$schet = _schetPayQuery($schet_id))
				jsonError('����� �� ����������');

			if($schet['sum'] - $schet['sum_paid'] <= 0)
				jsonError('���� �������, �������������� ����������.');

			$head = '';
			$send['org_id'] = $schet['org_id'];
			$send['bank_id'] = $schet['bank_id'];
			$client_id = $schet['client_id'];
			$zayav_id = $schet['zayav_id'];
			$nomer = $schet['type_id'] == 2 ? '-' : $schet['nomer'];
			$date_create = $schet['date_create'];
			$act_date = $schet['act_date'];

			//������� ������ ���� ��� ��� - ��� ����������� ��������������
			$send['noedit'] = $schet['type_id'] == 2 || TODAY == substr($schet['dtime_add'], 0, 10) ? 0 : 1;

			foreach($schet['content'] as $i => $r) {
				$r['name'] = utf8($r['name']);
				$r['cena'] = _cena($r['cena']);
				$r['summa'] = _cena($r['count'] * $r['cena']);
				$r['readonly'] = _num($r['readonly']) + $send['noedit'];
				$send['content'][] = $r;
				unset($send['content'][$i]['app_id']);
			}
		} else {
			if(!$send['content'] = _zayavInfoCartridgeForSchetPay($cartridge_ids))  //��������� ������ �� ���������� ��� ����������� � �������
				$send['content'] = _zayavInfoGazetaNomerForSchetPay($gn_ids);       //��������� ������ �� ������� ����� ��� ����������� � �������
		}
		
		$send['client_id'] = $client_id;

		//������ ����������� ��� _select
		$sql = "SELECT `id`,`name`
				FROM `_setup_org`
				WHERE `app_id`=".APP_ID."
				ORDER BY `id`";
		$send['org'] = query_selArray($sql);

		//������ ������ ��� _select
		$sql = "SELECT `id`,`name`,`org_id`
				FROM `_setup_org_bank`
				WHERE `app_id`=".APP_ID."
				ORDER BY `id`";
		$send['bank'] = query_selMultiArray($sql);
		$bankExist = isset($send['bank'][$send['org_id']]);
		$bankCount = count(@$send['bank'][$send['org_id']]);

		
		//������ ������ ������� ����� (���� ����� ������������� ���� � ������ ������)
		$sql = "SELECT `id`,CONCAT('�',`nomer`,': ', `name`)
				FROM `_zayav`
				WHERE `client_id`=".$client_id."
				ORDER BY `id`";
		$send['zayav_spisok'] = query_selArray($sql);

		//�����/������� ������ ���� �����
		$typeShow = !$schet_id && empty($send['content']);

		$send['html'] = utf8(
			_schetPayTypeSelect($typeShow).

			($send['noedit'] ?
				'<div class="_info">'.
					'<b>��������!</b> �������������� ������� ����� ����������.'.
					'<br />'.
					'�� �� ������ �������� ����� �����.'.
					'<br />'.
					'�� �� ������ �������� ��� ������� ������� ����������.'.
				'</div>'
			: '').

			'<div id="schet-pay-edit"'.($typeShow ? ' class="dn"' : '').'>'.
				'<table class="bs10">'.

					'<tr><td class="label r">����������:'.
						//���� ��� �����������, �� ��������� �� ����������
						'<td class="red b'.(!_app('org_count') ? '' : ' dn').'">����������� ��������� �����������. <a href="'.URL.'&p=13">���������</a>.'.
						//���� ���� �����������, �� ������������ ������ ��������
						'<td class="b'.(_app('org_count') == 1 ? '' : ' dn').'">'._app('name').
						//���� ����� ����� �����������, �� ����������� ������
						'<td'.(_app('org_count') > 1 ? '' : ' class="dn"').'><input type="hidden" id="org_id" value="'.$send['org_id'].'" />'.

					'<tr'.(!_app('org_count') ? ' class="dn"' : '').'>'.
						'<td class="label r">���� ����������:'.
						//���� ��� ������, �� ��������� �� ����������
						'<td class="bank-0 red b'.($bankExist ? ' dn' : '').'">����������� ������ �����. <a href="'.URL.'&p=13">���������</a>.'.
						//���� ���� ����, �� ������������ ������ ��������
						'<td class="bank-1 b'.($bankCount == 1 ? '' : ' dn').'">'.win1251(@$send['bank'][$send['org_id']][0]['title']).
						//���� ����� ������ �����, �� ����������� ������
						'<td class="bank-2'.($bankCount > 1 ? '' : ' dn').'"><input type="hidden" id="bank_id" value="'.$send['bank_id'].'" />'.

					'<tr><td class="label r">C��� �����:'.
						'<td><input type="text" class="w70 r grey" id="nomer" value="'._app('schet_prefix').schet_pay_nomer_next($nomer).'" readonly />'.
							'<span class="ml20">��</span> '.
							'<input type="hidden" id="date-create" value="'.$date_create.'" />'.

					'<tr'.(!_app('schet_act_date_set') ? ' class="dn"' : '').'>'.
						'<td class="label r">��� �����:'.
						'<td><input type="text" class="w70 r grey" id="nomer" value="'._app('schet_prefix').schet_pay_nomer_next($nomer).'" readonly />'.
							'<span class="ml20">��</span> '.
							'<input type="hidden" id="act-date" value="'.$act_date.'" />'.

					'<tr><td class="label r">����������:'.
						'<td class="b">'.
							'<input type="hidden" id="client_id" value="'.$client_id.'" />'.
							($client_id ? _clientVal($client_id, 'name') : '').

	  ($client_id ? '<tr><td class="label r">������:<td class="b">' : '').
					'<input type="hidden" id="zayav_id" value="'.$zayav_id.'" />'.

				'</table>'.

				'<div class="mr20">'.
					'<div id="schet-pay-head" class="center b mt20 fs14">'.$head.'</div>'.
					'<table class="_spisokTab mt10">'.
						'<tr><th class="w15">�'.
							'<th>������������ ������'.
							'<th class="w70">���-��'.
							'<th class="w50">��.���.'.
							'<th class="w70">����'.
							'<th class="w70">�����'.
					'</table>'.

					'<input type="hidden" id="schet-pay-content" />'.
				'</div>'.
				'<input type="hidden" id="cartridge_ids" value="'.$cartridge_ids.'" />'.
				'<input type="hidden" id="gn_ids" value="'.$gn_ids.'" />'.
			'</div>'
		);

		jsonSuccess($send);
		break;
	case 'schet_pay_add'://�������� ����� �� ������
		if(!$type_id = _num($_POST['type_id']))
			jsonError('������������ ��� �����');
		if(!$org_id = _num($_POST['org_id']))
			jsonError('�� ������� �����������');
		if(!$bank_id = _num($_POST['bank_id']))
			jsonError('�� ������ ���� ����������');
		if(!preg_match(REGEXP_DATE, $_POST['date_create']))
			jsonError('������������ ���� ��������');

		$date_create = $_POST['date_create'];
		$act_date = $_POST['act_date'];
		$client_id = _num($_POST['client_id']);

		if($zayav_id = _num($_POST['zayav_id'])) {
			if(!$z = _zayavQuery($zayav_id))
				jsonError('������ �� �������');
			$client_id = $z['client_id'];
		}

		if(!$client_id)
			jsonError('�� ������ ����������');
		if(!$client = _clientQuery($client_id))
			jsonError('���������� �� ������');

		$content = schet_pay_content('new');
		$cartridge_ids = _ids(@$_POST['cartridge_ids']);//ids ���������� ��� ��������
		$gn_ids = _ids(@$_POST['gn_ids']);//ids ������� ����� ��� ��������

		$sql = "SELECT *
				FROM `_setup_org`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$org_id;
		if(!$org = query_assoc($sql))
			jsonError('����������� �� ����������');

		$sql = "SELECT *
				FROM `_setup_org_bank`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$bank_id;
		if(!$bank = query_assoc($sql))
			jsonError('����� �� ����������');

		$sum = schet_pay_content('sum');

		$sql = "INSERT INTO `_schet_pay` (
					`app_id`,
					`type_id`,
					`prefix`,
					`nomer`,
					`zayav_id`,
					`date_create`,
					`act_date`,
					`sum`,

					`org_id`,
					`org_name_yur`,
					`org_adres_yur`,
					`org_inn`,
					`org_kpp`,
					`org_boss`,
					`org_accountant`,
					
					`bank_id`,
					`bank_name`,
					`bank_bik`,
					`bank_account`,
					`bank_account_corr`,

					`client_id`,
					`client_name`,
					`client_adres`,
					`client_inn`,
					`client_kpp`,

					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$type_id.",
					'".($type_id == 2 ? '' : addslashes(_app('schet_prefix')))."',
					".($type_id == 2 ? '-' : '').schet_pay_nomer_next(0).",
					".$zayav_id.",
					'".$date_create."',
					'".(_app('schet_act_date_set') ? $act_date : $date_create)."',
					".$sum.",

					".$org_id.",
					'".addslashes($org['name_yur'])."',
					'".addslashes($org['adres_yur'])."',
					'".addslashes($org['inn'])."',
					'".addslashes($org['kpp'])."',
					'".addslashes($org['post_boss'])."',
					'".addslashes($org['post_accountant'])."',

					".$bank_id.",
					'".addslashes($bank['name'])."',
					'".addslashes($bank['bik'])."',
					'".addslashes($bank['account'])."',
					'".addslashes($bank['account_corr'])."',

					".$client_id.",
					'".addslashes(_clientVal($client_id, 'name'))."',
					'".addslashes($client['adres'])."',
					'".addslashes($client['inn'])."',
					'".addslashes($client['kpp'])."',

					".VIEWER_ID."
				)";
		query($sql);

		$schet_id = query_insert_id('_schet_pay');
		schet_pay_content('insert', $schet_id);

		//�������� ����������
		if($cartridge_ids) {
			$sql = "UPDATE `_zayav_cartridge`
					SET `schet_id`=".$schet_id."
					WHERE !`schet_id`
					  AND `id` IN (".$cartridge_ids.")";
			query($sql);
		}

		//�������� ������� �����
		if($gn_ids) {
			$sql = "UPDATE `_zayav_gazeta_nomer`
					SET `schet_id`=".$schet_id."
					WHERE !`schet_id`
					  AND `id` IN (".$gn_ids.")";
			query($sql);
		}

		//���������� �� �����
		if($type_id == 1) {
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

			//������ ��� �������
			_balans(array(
				'action_id' => 25,//����� ����������
				'client_id' => $client_id,
				'schet_id' => $schet_id,
				'zayav_id' => $zayav_id,
				'sum' => $sum
			));

			_zayavBalansUpdate($zayav_id);
		}

		_history(array(
			'type_id' => 59,
			'schet_id' => $schet_id,
			'client_id' => $client_id,
			'zayav_id' => $zayav_id,
			'v1' => $sum,
			'v2' => $type_id == 2 ? '���������������' : ''
		));

		$send['schet_id'] = $schet_id;
		jsonSuccess($send);
		break;
	case 'schet_pay_edit'://���������� ����� �� ������ ����� ��������������
		if(!$schet_id = _num($_POST['schet_id']))
			jsonError('������������ id �����');
		if(!$org_id = _num($_POST['org_id']))
			jsonError('�� ������� �����������');
		if(!$bank_id = _num($_POST['bank_id']))
			jsonError('�� ������ ���� ����������');
		if(!preg_match(REGEXP_DATE, $_POST['date_create']))
			jsonError('������������ ���� ��������');

		$date_create = $_POST['date_create'];
		$act_date = $_POST['act_date'];
		$zayav_id = _num($_POST['zayav_id']);

		if(!$schet = _schetPayQuery($schet_id))
			jsonError('����� �� ����������');

		if($schet['sum'] - $schet['sum_paid'] <= 0)
			jsonError('���� �������, �������������� ����������');

		$client_id = $schet['client_id'];

		//�������� ������ ������ ������
		if($schet['zayav_id']) {
			if(!$zOld = _zayavQuery($schet['zayav_id']))
				jsonError('������ ������ �� ����������');
			if($zOld['client_id'] != $schet['client_id'])
				jsonError('������������ ������� � ����� � ������ ������');
		}

		//�������� ������ ����� ������
		if($zayav_id && $schet['zayav_id'] != $zayav_id) {
			if(!$z = _zayavQuery($zayav_id))
				jsonError('������ �� �������');
			if($z['client_id'] != $schet['client_id'])
				jsonError('������������ ������� � ����� � ����� ������');
		}

		$content = schet_pay_content('old');
		$sum = schet_pay_content('sum');

		$sql = "UPDATE `_schet_pay`
				SET `zayav_id`=".$zayav_id.",
					`date_create`='".$date_create."',
					`act_date`='".(_app('schet_act_date_set') ? $act_date : $date_create)."',
					`sum`=".$sum."
				WHERE `id`=".$schet_id;
		query($sql);

		//���������� ������ �����������
		$org = _app('org', $org_id, 'all');
		$sql = "UPDATE `_schet_pay`
				SET `org_id`=".$org_id.",
					`org_name_yur`='".addslashes($org['name_yur'])."',
					`org_adres_yur`='".addslashes($org['adres_yur'])."',
					`org_inn`='".addslashes($org['inn'])."',
					`org_kpp`='".addslashes($org['kpp'])."',
					`org_boss`='".addslashes($org['post_boss'])."',
					`org_accountant`='".addslashes($org['post_accountant'])."'
				WHERE `id`=".$schet_id;
		query($sql);

		//���������� ������ �����
		$bank = _app('bank', $bank_id, 'all');
		$sql = "UPDATE `_schet_pay`
				SET `bank_id`=".$bank_id.",
					`bank_name`='".addslashes($bank['name'])."',
					`bank_bik`='".addslashes($bank['bik'])."',
					`bank_account`='".addslashes($bank['account'])."',
					`bank_account_corr`='".addslashes($bank['account_corr'])."'
				WHERE `id`=".$schet_id;
		query($sql);

		//���������� ����������
		schet_pay_content('insert', $schet_id);


		if($schet['type_id'] == 1) {
			//��������� ����������
			$sql = "SELECT *
					FROM `_money_accrual`
					WHERE `app_id`=".APP_ID."
					  AND !`deleted`
					  AND `schet_id`=".$schet_id."
					LIMIT 1";
			$r = query_assoc($sql);

			$sql = "UPDATE `_money_accrual`
					SET `zayav_id`=".$zayav_id.",
						`sum`=".$sum."
					WHERE `id`=".$r['id'];
			query($sql);

			if($schet['sum'] != $sum)
				//������ ��� �������
				_balans(array(
					'action_id' => 37,//��������� ����������
					'client_id' => $client_id,
					'schet_id' => $schet_id,
					'zayav_id' => $zayav_id,
					'sum' => $sum,
					'sum_old' => $schet['sum']
				));

			if($schet['zayav_id'] != $zayav_id) {
				//������� ��������
				$sql = "UPDATE `_money_income`
						SET `zayav_id`=".$zayav_id."
						WHERE `app_id`=".APP_ID."
						  AND `schet_id`=".$schet_id;
				query($sql);
				_zayavBalansUpdate($zayav_id);
			}

			_zayavBalansUpdate($schet['zayav_id']);
		}

		$content_changes = '';
		//��������� ����������: ����� �������
		$pos = '';
		foreach(schet_pay_content('new') as $r)
			$pos .= '<tr><td>'.$r[0].
						'<td class="center">'.$r[1].
						'<td class="center">��.'.
						'<td class="r">'._sumSpace($r[2], 1).
						'<td class="r">'._sumSpace($r[1] * $r[2], 1);
		if($pos)
			$content_changes .=
				'<div class="mt5 color-pay">��������� �������:</div>'.
				'<table class="bg-add mb10">'.$pos.'</table>';

		//��������� ����������: �������� �������
		$pos = '';
		foreach($schet['content'] as $id => $r)
			if(!isset($content[$id]))
				$pos .= '<tr><td>'.$r['name'].
							'<td class="center">'.$r['count'].
							'<td class="center">��.'.
							'<td class="r">'._sumSpace($r['cena'], 1).
							'<td class="r">'._sumSpace($r['count'] * $r['cena'], 1);
		if($pos)
			$content_changes .=
				'<div class="mt5 color-vin">������� �������:</div>'.
				'<table class="bg-del mb10">'.$pos.'</table>';

		//��������� ����������: ��������� �������
		$pos = '';
		foreach($schet['content'] as $id => $r) {
			if(!$new = @$content[$id])
				continue;

			$name = $new[0] != $r['name'];
			$count = $new[1] != $r['count'];
			$cena = $new[2] != $r['cena'];
			$posSum = _cena($new[1] * $new[2]) != _cena($r['count'] * $r['cena']);

			if(!$name && !$count && !$cena)
				continue;

			$pos .=
			'<table class="mb10">'.
				'<tr><td'.($name ? ' class="bg-ch"' : '').'>'.$r['name'].
					'<td class="center'.($count ? ' bg-ch' : '').'">'.$r['count'].
					'<td class="center">��.'.
					'<td class="r'.($cena ? ' bg-ch' : '').'">'._sumSpace($r['cena'], 1).
					'<td class="r'.($posSum ? ' bg-ch' : '').'">'._sumSpace($r['count'] * $r['cena'], 1).
				'<tr><td'.($name ? ' class="bg-ch"' : '').'>'.$new[0].
					'<td class="center'.($count ? ' bg-ch' : '').'">'.$new[1].
					'<td class="center">��.'.
					'<td class="r'.($cena ? ' bg-ch' : '').'">'._sumSpace($new[2], 1).
					'<td class="r'.($posSum ? ' bg-ch' : '').'">'._sumSpace($new[1] * $new[2], 1).
			'</table>';
		}
		if($pos)
			$content_changes .= '<div class="mt5">�������� �������:</div>'.$pos;

		//���� ���������� ������
		$zayavOld = '';
		$zayavNew = '';
		if($schet['zayav_id'] != $zayav_id) {
			if($schet['zayav_id']) {
				$z = _zayavQuery($schet['zayav_id']);
				$zayavOld = $z['go'];
			}
			if($zayav_id) {
				$z = _zayavQuery($zayav_id);
				$zayavNew = $z['go'];
			}
		}


		if($changes =
			_historyChange('����������', _app('org', $schet['org_id'], 'name'), _app('org', $org_id, 'name')).
			_historyChange('���� ����������', _app('bank', $schet['bank_id'], 'name'), _app('bank', $bank_id, 'name')).
			_historyChange('����', FullData($schet['date_create']), FullData($date_create)).
			_historyChange('�����', _cena($schet['sum']), _cena($sum)).
			_historyChange('������', $zayavOld, $zayavNew))
			$changes = '<table>'.$changes.'</table>';

		if($changes || $content_changes)
			_history(array(
				'type_id' => 61,
				'schet_id' => $schet_id,
				'client_id' => $client_id,
				'zayav_id' => $zayav_id,
				'v1' => $content_changes.$changes
			));

		$send['schet_id'] = $schet_id;
		jsonSuccess($send);
		break;
	case 'schet_pay_show'://���������� � �����
		if(!$schet_id = _num($_POST['schet_id']))
			jsonError('�������� id �����');

		if(!$schet = _schetPayQuery($schet_id, 1))
			jsonError('����� �� ����������');

		if($schet['zayav_id'])
			$z = _zayavQuery($schet['zayav_id']);

		$content = '';
		$num = 1;
		foreach($schet['content'] as $r) {
			$content .=
				'<tr><td class="grey r">'.($num++).
					'<td>'._br($r['name']).
					'<td class="center">'.$r['count'].
					'<td class="center">'._tovarMeasure($r['measure_id']).
					'<td class="r">'._sumSpace($r['cena'], 1).
					'<td class="r">'._sumSpace(_cena($r['count'] * $r['cena']), 1);
		}

		define('PREVIEW', $schet['type_id'] == 2);

		$hist = _history(array('schet_id'=>$schet_id));
		$send['hist'] = _num($hist['all']);
		$send['hist_spisok'] = utf8($hist['spisok']);
		$send['nomer'] = utf8($schet['prefix'].$schet['nomer']);
		$send['invoice_id'] = _app('schet_invoice_id');
		$send['sum_to_pay'] = _cena($schet['sum'] - $schet['sum_paid']);//����� ��� ������
		$paid = $send['sum_to_pay'] <= 0;

		$send['html'] = utf8(
			(PREVIEW ?
				'<div class="_info center">'.
					'<b class="fs14">��������������� ����.</b>'.
					'<div class="mt5">����� �������������� ������ ��� ������������.</div>'.
					'������� �������� ����� ������� � ��� ������ ����������.'.
					'<br />'.
					'<button class="vk mt10" id="schet-pay-to-pay">�������� ������ �� "���� �� ������"</button>'.
				'</div>'
			: '').
			'<table class="bs10">'.
				'<tr><td class="label r w125">����������:'.
					'<td>'.$schet['org_name_yur'].
				'<tr><td class="label r">���� ����������:'.
					'<td>'.$schet['bank_name'].
				'<tr><td class="label r top">����������:'.
					'<td>'._clientVal($schet['client_id'], 'link').

			($schet['zayav_id'] ?
				'<tr><td class="label r">������:<td class="b">'.$z['nomer_link']
			: '').

			'</table>'.

			'<div class="center b mt20 fs14">'.
				(PREVIEW ? '��������������� ����' : '���� �� ������ � '.$schet['prefix'].$schet['nomer']).
				' �� '.FullData($schet['date_create']).' �.'.
			'</div>'.

			'<table class="_spisokTab mt10">'.
				'<tr><th class="w15">�'.
					'<th>������������ ������'.
					'<th class="w50">���-��'.
					'<th class="w50">��.���.'.
					'<th class="w70">����'.
					'<th class="w70">�����'.
				$content.
			'</table>'.

			'<div class="mt10">����� ������������ <b>'.count($schet['content']).'</b>, �� ����� <b>'._sumSpace($schet['sum'], 1).'</b> ���.</div>'.

			'<table class="w100p mt20'.($schet['deleted'] ? ' dn' : '').'">'.
				'<tr><td class="top">'.
						($schet['pass'] && !_cena($schet['sum_paid']) ?
							'<div class="_info mt10 mb20">'.
								'���� ������� ������� <u>'.FullData($schet['pass_day'], 1).'</u>.'.
								'<button class="vk small ml20 grey pass-cancel">�������� ��������</button>'.
							'</div>'
						: '').
						_schetPay_income($schet).
					'<td class="w175 pl20">'.
						'<div class="_menuDop3">'.
				  (!$paid ? '<a class="link"><div class="icon icon-edit"></div>������������� ����</a>' : '').

							'<a class="link" onclick="_templatePrint(\'schet-pay\',\'schet_id\','.$schet_id.')">'.
								'<div class="icon icon-print"></div>'.
								'�����������'.
							'</a>'.

						(!PREVIEW && !$schet['pass'] && !$paid ?
							'<a class="link"><div class="icon icon-out"></div>�������� �������</a>'
						: '').

			(!PREVIEW && !$paid ? '<a class="link b"><div class="icon icon-rub"></div>��������</a>' : '').
(!_cena($schet['sum_paid']) ? '<a class="link red"><div class="icon icon-del-red"></div>�������</a>' : '').
						'</div>'.
			'</table>'.

			($schet['deleted'] ? '<div class="_info mt20 red center b fs14">���� �����.</div>' : '').

			'<div class="bg-link mt20">�������� ������� �������� �� ������</div>'.
			'<div class="mt20 dn">'.$hist['spisok'].'</div>'
		);

		jsonSuccess($send);
		break;
	case 'schet_pay_to_pay'://��������� ������� ����� �� ������
		if(!$schet_id = _num($_POST['schet_id']))
			jsonError('�������� id �����');

		if(!$schet = _schetPayQuery($schet_id))
			jsonError('����� �� ����������');

		if($schet['type_id'] != 2)
			jsonError('���� �� �������� ���������������');

		//�������� ����������
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
					".$schet['client_id'].",
					".$schet['zayav_id'].",
					".$schet['sum'].",
					".VIEWER_ID."
				)";
		query($sql);

		//������ ��� �������
		_balans(array(
			'action_id' => 25,//����� ����������
			'client_id' => $schet['client_id'],
			'schet_id' => $schet_id,
			'zayav_id' => $schet['zayav_id'],
			'sum' => $schet['sum']
		));

		$nomer = schet_pay_nomer_next(0);
		$sql = "UPDATE `_schet_pay`
				SET `type_id`=1,
					`prefix`='".addslashes(_app('schet_prefix'))."',
					`nomer`=".$nomer."
				WHERE `id`=".$schet_id;
		query($sql);

		_history(array(
			'type_id' => 61,
			'schet_id' => $schet_id,
			'client_id' => $schet['client_id'],
			'zayav_id' => $schet['zayav_id'],
			'v1' => '<table>'.
						_historyChange('������', '���������������', '�� ������').
						_historyChange('�����', '-', _app('schet_prefix').$nomer).
					'</table>'
		));

		jsonSuccess();
		break;
	case 'schet_pay_pass'://�������� ����� �������
		if(!$schet_id = _num($_POST['schet_id']))
			jsonError('������������ id �����');
		if(!preg_match(REGEXP_DATE, $_POST['day']))
			jsonError('������������ ���� ��������');

		$day = $_POST['day'];
		if(!$r = _schetPayQuery($schet_id))
			jsonError('����� �� ����������');

		if($r['type_id'] == 2)
			jsonError('��������������� ���� �� ����� ���� �������');

		if($r['pass'])
			jsonError('���� ��� ��� ������� �������');

		$sql = "UPDATE `_schet_pay`
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
	case 'schet_pay_pass_cancel'://������ �������� �����
		if(!$schet_id = _num($_POST['schet_id']))
			jsonError('������������ id �����');

		if(!$r = _schetPayQuery($schet_id))
			jsonError('����� �� ����������');

		if(!$r['pass'])
			jsonError('���� �� ��� ������� �������');

		if($r['type_id'] == 2)
			jsonError('��������������� ���� �� ����� ���� �������');

		$sql = "UPDATE `_schet_pay`
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
	case 'schet_pay_pay'://������ �����
		if(!$schet_id = _num($_POST['schet_id']))
			jsonError('������������ id ����� �� ������');
		if(!$invoice_id = _num($_POST['invoice_id']))
			jsonError('������������ id ���������� �����');
		if(!$sum = _cena($_POST['sum']))
			jsonError('����������� ������� �����');
		if(!preg_match(REGEXP_DATE, $_POST['day']))
			jsonError('������������ ���� ������');

		$day = $_POST['day'];

		if(!$r = _schetPayQuery($schet_id))
			jsonError('����� �� ����������');

		if(!_invoice($invoice_id, 'test'))
			jsonError('���������� ����� �� ����������');

		if($r['type_id'] == 2)
			jsonError('���������� ���������� ��������������� ����');
		
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

		_schetPaySumCorrect($schet_id);
		_zayavBalansUpdate($r['zayav_id']);

		//������ ��� ���������� �����
		_balans(array(
			'action_id' => 1,
			'invoice_id' => $invoice_id,
			'income_id' => $income_id,
			'schet_id' => $schet_id,
			'sum' => $sum
		));

		//������ ��� �������
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
	case 'schet_pay_del'://�������� ����� �� ������
		if(!$schet_id = _num($_POST['id']))
			jsonError('�������� id �����');

		if(!$schet = _schetPayQuery($schet_id))
			jsonError('����� �� ����������');

		if($schet['deleted'])
			jsonError('���� ��� ��� �����');

		$sql = "SELECT SUM(`sum`)
				FROM `_money_income`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `schet_id`=".$schet_id;
		if(query_value($sql))
			jsonError('�� ����� ����� ��� ������������� �������');

		$sql = "UPDATE `_schet_pay`
				SET `deleted`=1
				WHERE `id`=".$schet_id;
		query($sql);

		//�������� ����������
		$sql = "UPDATE `_money_accrual`
				SET `deleted`=1
				WHERE `schet_id`=".$schet_id;
		query($sql);


		//������� ���������� �� �����
		$sql = "UPDATE `_zayav_cartridge`
				SET `schet_id`=0
				WHERE `schet_id`=".$schet_id;
		query($sql);

		//������� ������� ����� �� �����
		$sql = "UPDATE `_zayav_gazeta_nomer`
				SET `schet_id`=0
				WHERE `schet_id`=".$schet_id;
		query($sql);


		if($schet['type_id'] == 1) {
			//�������� ������� ��� �������
			_balans(array(
				'action_id' => 26,//�������� ����������
				'client_id' => $schet['client_id'],
				'zayav_id' => $schet['zayav_id'],
				'schet_id' => $schet_id,
				'sum' => $schet['sum']
			));

			_zayavBalansUpdate($schet['zayav_id']);
		}

		_history(array(
			'type_id' => 66,
			'schet_id' => $schet_id,
			'client_id' => $schet['client_id'],
			'zayav_id' => $schet['zayav_id']
		));

		jsonSuccess();
		break;
	case 'schet_pay_spisok':
		$_POST['find'] = win1251(@$_POST['find']);
		$data = _schetPay_spisok($_POST);
		$send['spisok'] = utf8($data['spisok']);
		jsonSuccess($send);
		break;
	case 'schet_pay_all_remove':
		if(!SA)
			jsonError('��� ����');

		//�������� ������
		$sql = "DELETE FROM `_schet_pay` WHERE `app_id`=".APP_ID;
		query($sql);

		//�������� ���������� ������
		$sql = "DELETE FROM `_schet_pay_content` WHERE `app_id`=".APP_ID;
		query($sql);

		//�������� ������� �������� �� ������
		$sql = "SELECT `type_id`
				FROM `_history_ids`
				WHERE `category_id`=7";
		if($ids = query_ids($sql)) {
			$sql = "DELETE FROM `_history`
					WHERE `app_id`=".APP_ID."
					  AND `type_id` IN (".$ids.")";
			query($sql);
		}

		$sql = "DELETE FROM `_history`
				WHERE `app_id`=".APP_ID."
				  AND `schet_id`";
		query($sql);


		//�������� ���������� �� �������
		$sql = "DELETE FROM `_money_accrual`
				WHERE `app_id`=".APP_ID."
				  AND `schet_id`";
		query($sql);


		//������� �� ������ � ������� ��������
		$sql = "UPDATE `_balans`
				SET `schet_id`=0
				WHERE `app_id`=".APP_ID."
				  AND `schet_id`";
		query($sql);


		//������� �������� �� ������
		$sql = "UPDATE `_money_income`
				SET `schet_id`=0
				WHERE `app_id`=".APP_ID."
				  AND `schet_id`";
		query($sql);

/*
		//todo ��������: ���������� app_id � _zayav_cartridge
		//������� ���������� �� ������
		$sql = "UPDATE `_zayav_cartridge`
				SET `schet_id`=0
				WHERE `app_id`=".APP_ID."
				  AND `schet_id`";
		query($sql);
*/

		//������� ������� ������ �� ������
		$sql = "UPDATE `_zayav_gazeta_nomer`
				SET `schet_id`=0
				WHERE `app_id`=".APP_ID."
				  AND `schet_id`";
		query($sql);


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
			_historyChange('������������', $r['name'], $name).
			_historyChange('��������', _br($r['about']), _br($about)).
			_historyChange('��������� ��� �����������', $visible_old, _invoice($id, 'visible_worker')).
			_historyChange('������������� ����������� �� ����', _daNet($r['income_confirm']), _daNet($income_confirm)).
			_historyChange('������������� ��������', _daNet($r['transfer_confirm']), _daNet($transfer_confirm)).
			_historyChange('�������� �������� � ���������', $income_insert_old, _invoice($id, 'income_insert_worker')).
			_historyChange('�������� �������� � ������ �/�', $expense_insert_old, _invoice($id, 'expense_insert_worker')))
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

		if($invoice_id == $invoice_to) // ���� ����� ����������
			jsonError();

		$sql = "SELECT *
				FROM `_money_invoice`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `id`=".$invoice_id;
		if(!$r = query_assoc($sql))
			jsonError();

		if($balans = _invoiceBalans($invoice_id)) {//������� �������, ���� ������ �������� �� ����������� �����
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
						'������ ���� \""._invoice($invoice_id)."\"',
						".VIEWER_ID."
					)";
			query($sql);

			$insert_id = query_insert_id('_money_invoice_transfer');

			//������� ������� ��� �����-����������� (������� �����������)
			_balans(array(
				'action_id' => 4,
				'invoice_id' => $invoice_id,
				'sum' => $balans,
				'invoice_transfer_id' => $insert_id
			));

			//������� ������� ��� �����-����������
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
			'action_id' => 15,//��������
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
	case 'invoice_transfer_edit'://�������������� ����������� ��������
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
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('�������� �� ����������');

		if($r['deleted'])
			jsonError('������� ��� ��� �����');

		if($r['confirm'] == 2)
			jsonError('������ ������� �������, ������� ��� ����������');

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
	case 'invoice_in_add'://�������� ����� �� ��������� ����
		if(!$invoice_id = _num($_POST['invoice_id']))
			jsonError('�� ������ ����');
		if(!$sum = _cena($_POST['sum']))
			jsonError('����������� ������� �����');

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
	case 'invoice_in_del'://�������� �������� �����
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
	case 'invoice_out_add'://����� ����� � ���������� �����
		if(!$invoice_id = _num($_POST['invoice_id']))
			jsonError('�� ������ ����');
		if(!$sum = _cena($_POST['sum']))
			jsonError('����������� ������� �����');
		if(!$worker_id = _num($_POST['worker_id']))
			jsonError('�� ������ ���������-����������');

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
	case 'invoice_out_del'://�������� ������ �����
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
	case 'balans_everyday':
		$send['html'] = utf8(balans_everyday($_POST));
		jsonSuccess($send);
		break;
	case 'balans_spisok':
		$data = balans_show_spisok($_POST);
		$send['spisok'] = utf8($data['spisok']);
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
	case 'salary_balans_set'://��������� ������� ����������
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
	case 'salary_rate_set'://��������� ������ ����������
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
			_historyChange('�����', _cena($r['salary_rate_sum']) ? _cena($r['salary_rate_sum']) : '', $sum ? $sum : '').
			_historyChange('������', $r['salary_rate_period'] ? _salaryPeriod($r['salary_rate_period']) : '', _salaryPeriod($period)).
			_historyChange('����', $r['salary_rate_day'] ? $r['salary_rate_day'] : '', $day ? $day : ''))
			_history(array(
				'type_id' => 35,
				'worker_id' => $worker_id,
				'v1' => '<table>'.$changes.'</table>'
			));

		$send['rate'] = utf8(salaryWorkerRate($worker_id));
		jsonSuccess($send);
		break;
	case 'salary_accrual_add'://�������� ������������� ���������� �� ����������
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

		//������������ ������� id ����������
		$accrual_ids = array();
		$deduct_ids = array();
		$expense_ids = array();
		foreach(explode(',', $ids) as $r) {
			$i = explode(':', $r);
			if(!$id = _num($i[1]))
				jsonError('������������ id ����������');
			switch($i[0]) {
				case 'accrual': $accrual_ids[] = $id; break;
				case 'deduct': $deduct_ids[] = $id; break;
				case 'expense': $expense_ids[] = $id; break;
				default: jsonError('����������� ��� ����������');
			}
		}
		if(!$accrual_ids && !$deduct_ids && !$expense_ids)
			jsonError('���������� �����������');

		$accrual_ids = implode(',', $accrual_ids);
		$deduct_ids  = implode(',', $deduct_ids);
		$expense_ids = implode(',', $expense_ids);

		$accrual_sum = 0;
		$deduct_sum = 0;
		$expense_sum = 0;

		//��������, ����� ������� ���������� �� ���� ������� �����
		if($accrual_ids) {
			$sql = "SELECT COUNT(*)
					FROM `_salary_accrual`
					WHERE `app_id`=".APP_ID."
					  AND `salary_list_id`
					  AND `id` IN (".$accrual_ids.")";
			if(query_value($sql))
				jsonError('��������� ������������ ���������� ��� ���� �������');

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
				jsonError('��������� ������ ��� ���� �������');

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
				jsonError('��������� ���������� �������� �� ������ ��� ���� �������');

			$sql = "SELECT IFNULL(SUM(`sum`), 0)
					FROM `_zayav_expense`
					WHERE `app_id`=".APP_ID."
					  AND `id` IN (".$expense_ids.")";
			$expense_sum = query_value($sql);
		}

		//����� ����� ���� ����������
		$sum = $accrual_sum - $deduct_sum + $expense_sum;
		
		//�������� ����� ������
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

		//�������� ����� ������ � �����������
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

		//�������� ����� ������ � �������
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
			jsonError('�� ����� ������ ��� ����������� ������');

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
	case 'salary_noacc_recalc'://���������� ������� ���������� �� ���������� �����������
		if(!VIEWER_ADMIN)
			jsonError();
		if(!$worker_id = _num($_POST['worker_id']))
			jsonError();

		$onPay = _viewerRule($worker_id, 'RULE_SALARY_ZAYAV_ON_PAY');
		$balansStart = salaryWorkerBalans($worker_id);

		$changes = '';

		//�������� ���������� �� �� �������� �������
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
				$changes .= '<tr><td>������� ���������� �� �������� ������:<td>'.mysql_affected_rows();
		}

		//������ ���� ������, � ������� ���� ����
		$sql = "SELECT `id`
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `sum_dolg`<0";
		$ids = query_ids($sql);

		if($onPay) {
			//������� ���������� �� �������, � ������� ���� �����, � ���������� ������
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
				$changes .= '<tr><td>���������� ���������� �� �������<br />� ���������� ������, � ������� ���� �����:<td>'.mysql_affected_rows();
		}

		//id ������� �� ����������� ������
		$sql = "SELECT `zayav_id`
				FROM `_zayav_expense`
				WHERE `app_id`=".APP_ID."
				  AND `worker_id`=".$worker_id."
				  AND (!`year` OR !`mon`)";
		if($ids = query_ids($sql)) {
			$rec = 0;//���������� ��������������� ����������
			//������, � ������� ��� ������, ������ �� ����������� ������ � ����� ������
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

			if(!$onPay) {//������� ���������� �� ������� �� ����������� ������ � ������� ����� (������� �� �����������)
				$sql = "UPDATE `_zayav_expense`
						SET `year`=".strftime('%Y').",
							`mon`=".strftime('%m')."
						WHERE `worker_id`=".$worker_id."
						  AND `zayav_id` IN (".$ids.")";
				query($sql);
				$rec += mysql_affected_rows();
			}

			if($rec)
				$changes .= '<tr><td>������������� ���������� �� �������<br />�� ���������� ������, � ������� ��� ������:<td>'.$rec;
		}

		$balansEnd = salaryWorkerBalans($worker_id);

		if($balansStart != $balansEnd) {
			_balans(array(
				'action_id' => 43,
				'worker_id' => $worker_id,
				'about' => '������������� ���������� �� ������� � �������.'
			));
			$changes .= '<tr><td>��������� ������ ����������:<td>'.$balansStart.' -> '.$balansEnd;
		}

		if($changes)
			_history(array(
				'type_id' => 500,
				'worker_id' => $worker_id,
				'v1' => '<table>'.$changes.'</table>'
			));

		jsonSuccess();
		break;

	case 'smena_start':
		if(!_smenaStartTest())
			jsonError('�������� ����� �� ���������');

		$started = _num($_POST['started']);

		$sql = "INSERT INTO `_smena` (
					`app_id`,
					`worker_id`,
					`started`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".VIEWER_ID.",
					".$started.",
					".VIEWER_ID."
				)";
		query($sql);

		jsonSuccess();
		break;
	case 'smena_budget_set':
		if(!VIEWER_ADMIN)
			jsonError('��� ����');
		if(!$sum = _num($_POST['sum']))
			jsonError('�� ���������� �����');

		$sql = "UPDATE `_setup_global`
				SET `value`=".$sum."
				WHERE `app_id`=".APP_ID."
				  AND `key`='SMENA_MON_BUDGET'";
		query($sql);

		xcache_unset(CACHE_PREFIX.'setup_global');

		jsonSuccess();
		break;
	case 'smena-mon-load':
		if(!preg_match(REGEXP_YEARMONTH, $_POST['mon']))
			jsonError('������������ �����');

		$send['html'] = utf8(_smenaMon($_POST['mon']));
		jsonSuccess($send);
		break;
}


function expense_dtime_old_update_for_kupez($invoice_id, $expense_id, $sum) {//todo ��� ����� ��� ��������
	if(APP_ID != 3495523)
		return false;

	$dtime_add = @$_POST['dtime_add'];

	if(!preg_match(REGEXP_DATE, $dtime_add))
		return false;
	
//	if($dtime_add == TODAY)
//		return false;
	
	$sql = "UPDATE `_money_expense`
			SET `dtime_add`='".$dtime_add.' '.strftime('%H:%M:%S')."'
			WHERE `id`=".$expense_id;
	query($sql);
/*
	//������������� ������� �����: ������ �������� ����������
	$sql = "UPDATE `_money_invoice`
			SET `start`=`start`-".$sum."
			WHERE `id`=".$invoice_id;
	query($sql);
*/
	xcache_unset(CACHE_PREFIX.'invoice');

	return true;
}
function schet_pay_content($action, $schet_id=0) {//��������� ���������� ����� �� ������ ��� �������� ��� ��������������
	if(empty($_POST['content']))
		jsonError('����������� ���������� �����');

	$insert = array();  //������ ��� �������� ����� ����������
	$old = array();     //������ ������ ��� ��������� ���������
	$new = array();     //����� ������
	$sum = 0;
	foreach(explode('###', $_POST['content']) as $r) {
		$sp = explode('&&&', $r);
		if(!$name = _txt($sp[0]))
			continue;
		if(!$count = _num(@$sp[1]))
			continue;

		$measure_id = _num(@$sp[2]);
		$cena = _cena(@$sp[3]);
		$sum += _num($count) * $cena;

		if($id = _num(@$sp[4]))
			$old[$id] = array($name, $count, $cena);
		else
			$new[] = array($name, $count, $cena);

		$insert[] = "(".
			APP_ID.",".
			$schet_id.",".
			"'".addslashes($name)."',".
			$count.",".
			$measure_id.",".
			$cena.",".
			$sp[5]. //readonly
		")";
	}

	if(empty($insert))
		jsonError('����������� ��������� ���� ����������');

	if($action == 'sum')
		return $sum;

	if($action == 'insert') {//�������� ����������
		$sql = "DELETE FROM `_schet_pay_content` WHERE `schet_id`=".$schet_id;
		query($sql);
		$sql = "INSERT INTO `_schet_pay_content` (
					`app_id`,
					`schet_id`,
					`name`,
					`count`,
					`measure_id`,
					`cena`,
					`readonly`
				) VALUES ".implode(',', $insert);
		query($sql);
		return true;
	}

	if($action == 'new')
		return $new;

	return $old;
}
function schet_pay_nomer_next($nomer) {//��������� ���������� ����� �����
	if($nomer)
		return $nomer;
	$nomer = _app('schet_nomer_start');
	$max = _maxSql('_schet_pay', 'nomer', 1);
	return $nomer > $max ? $nomer : $max;
}

