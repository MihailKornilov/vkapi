<?php
switch(@$_POST['op']) {
	case 'client_sel'://список клиентов для select
		$send['spisok'] = array();
		if(!empty($_POST['val']) && !preg_match(REGEXP_WORDFIND, win1251($_POST['val'])))
			jsonSuccess($send);

		$val = win1251($_POST['val']);
		$client_id = _num($_POST['client_id']);
		$not_client_id = _num($_POST['not_client_id']);
		$category_id = _num($_POST['category_id']);

		$sql = "SELECT *
				FROM `_client`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`".
			(!empty($val) ? " AND (`find` LIKE '%".$val."%')" : '').
			($category_id ? " AND `category_id`=".$category_id : '').
			($client_id ? " AND `id`<=".$client_id : '').
			($not_client_id ? " AND `id`!=".$not_client_id : '')."
				ORDER BY `id` DESC
				LIMIT 50";
		if(!$spisok = query_arr($sql))
			jsonSuccess($send);

		foreach($spisok as $r) {
			$name = $r['category_id'] == 1 ? $r['fio'] : $r['org_name'];
			$phone = $r['category_id'] == 1 ? $r['phone'] : $r['org_phone'];
			$adres = $r['category_id'] == 1 ? $r['adres'] : $r['org_adres'];
			$unit = array(
				'uid' => $r['id'],
				'title' => utf8(htmlspecialchars_decode($name))
			);
			if($phone)
				$unit['content'] = utf8($name.'<span>'.$phone.'</span>');
			if($adres)
				$unit['adres'] = utf8($adres);
			$send['spisok'][] = $unit;
		}
		jsonSuccess($send);
		break;
	case 'client_add':
		if(!$category_id = _num($_POST['category_id']))
			jsonError();

		define('ORG', $category_id > 1);

		$fio = !ORG ? _txt($_POST['fio']) : '';
		$phone = !ORG ? _txt($_POST['phone']) : '';
		$adres = !ORG ? _txt($_POST['adres']) : '';
		$post = !ORG ? _txt($_POST['post']) : '';
		$pasp_seria = !ORG ? _txt($_POST['pasp_seria']) : '';
		$pasp_nomer = !ORG ? _txt($_POST['pasp_nomer']) : '';
		$pasp_adres = !ORG ? _txt($_POST['pasp_adres']) : '';
		$pasp_ovd = !ORG ? _txt($_POST['pasp_ovd']) : '';
		$pasp_data = !ORG ? _txt($_POST['pasp_data']) : '';

		$org_name =  ORG ? _txt($_POST['org_name']) : '';
		$org_phone = ORG ? _txt($_POST['org_phone']) : '';
		$org_fax =   ORG ? _txt($_POST['org_fax']) : '';
		$org_adres = ORG ? _txt($_POST['org_adres']) : '';
		$org_inn =   ORG ? _txt($_POST['org_inn']) : '';
		$org_kpp =   ORG ? _txt($_POST['org_kpp']) : '';

		if(!ORG && empty($fio))     //Для частного лица обязательно указывается ФИО
			jsonError();
		if(ORG && empty($org_name)) //Для организаций обязательно указывается Название организации
			jsonError();

		$sql = "INSERT INTO `_client` (
					`app_id`,
					`category_id`,

					`fio`,
					`phone`,
					`adres`,
					`post`,
					`pasp_seria`,
					`pasp_nomer`,
					`pasp_adres`,
					`pasp_ovd`,
					`pasp_data`,

					`org_name`,
					`org_phone`,
					`org_fax`,
					`org_adres`,
					`org_inn`,
					`org_kpp`,

					`from_id`,

					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$category_id.",

					'".addslashes($fio)."',
					'".addslashes($phone)."',
					'".addslashes($adres)."',
					'".addslashes($post)."',
					'".addslashes($pasp_seria)."',
					'".addslashes($pasp_nomer)."',
					'".addslashes($pasp_adres)."',
					'".addslashes($pasp_ovd)."',
					'".addslashes($pasp_data)."',

					'".addslashes($org_name)."',
					'".addslashes($org_phone)."',
					'".addslashes($org_fax)."',
					'".addslashes($org_adres)."',
					'".addslashes($org_inn)."',
					'".addslashes($org_kpp)."',

					"._clientFromGet().",

					".VIEWER_ID."
				)";
		query($sql);

		$client_id = query_insert_id('_client');

		_clientFindUpdate($client_id);

		_history(array(
			'type_id' => 1,
			'client_id' => $client_id
		));

		$send['uid'] = $client_id;
		jsonSuccess($send);
		break;
	case 'client_spisok':
		$_POST['find'] = win1251($_POST['find']);
		$data = _client_spisok($_POST);
		if($data['filter']['page'] == 1)
			$send['all'] = utf8($data['result']);
		$send['spisok'] = utf8($data['spisok']);
		jsonSuccess($send);
		break;
	case 'client_edit':
		if(!$client_id = _num($_POST['id']))
			jsonError();
		if(!$category_id = _num($_POST['category_id']))
			jsonError();

		if(!$c = _clientQuery($client_id))
			jsonError();

		define('ORG', $category_id > 1);

		if($worker_id = _num($_POST['worker_id'])) {
			$sql = "SELECT COUNT(`id`)
					FROM `_client`
					WHERE `app_id`=".APP_ID."
			          AND `id`!=".$client_id."
					  AND `worker_id`=".$worker_id;
			if(query_value($sql))
				jsonError('Этот сотрудник связан с другим клиентом');
		}

		$fio = !ORG ? _txt($_POST['fio']) : '';
		$phone = !ORG ? _txt($_POST['phone']) : '';
		$adres = !ORG ? _txt($_POST['adres']) : '';
		$post = !ORG ? _txt($_POST['post']) : '';
		$pasp_seria = !ORG ? _txt($_POST['pasp_seria']) : '';
		$pasp_nomer = !ORG ? _txt($_POST['pasp_nomer']) : '';
		$pasp_adres = !ORG ? _txt($_POST['pasp_adres']) : '';
		$pasp_ovd = !ORG ? _txt($_POST['pasp_ovd']) : '';
		$pasp_data = !ORG ? _txt($_POST['pasp_data']) : '';

		$org_name = ORG ? _txt($_POST['org_name']) : '';
		$org_phone = ORG ? _txt($_POST['org_phone']) : '';
		$org_fax = ORG ? _txt($_POST['org_fax']) : '';
		$org_adres = ORG ? _txt($_POST['org_adres']) : '';
		$org_inn = ORG ? _txt($_POST['org_inn']) : '';
		$org_kpp = ORG ? _txt($_POST['org_kpp']) : '';
		$join = _bool($_POST['join']);
		$client2 = _num($_POST['client2']);

		if(!ORG && empty($fio)) // Для частного лица обязательно указывается ФИО
			jsonError();

		if(ORG && empty($org_name))//Для организации обязательно указывается Название организации
			jsonError();

		if($join) {
			if(!$client2)
				jsonError();
			if($client_id == $client2)
				jsonError();
			if(!_clientQuery($client2, 1))
				jsonError();
		}

		$sql = "UPDATE `_client`
				SET `category_id`=".$category_id.",
					`worker_id`=".$worker_id.",
					
					`fio`='".addslashes($fio)."',
					`phone`='".addslashes($phone)."',
					`adres`='".addslashes($adres)."',
					`pasp_seria`='".addslashes($pasp_seria)."',
					`pasp_nomer`='".addslashes($pasp_nomer)."',
					`pasp_adres`='".addslashes($pasp_adres)."',
					`pasp_ovd`='".addslashes($pasp_ovd)."',
					`pasp_data`='".addslashes($pasp_data)."',

					`org_name`='".addslashes($org_name)."',
					`org_phone`='".addslashes($org_phone)."',
					`org_fax`='".addslashes($org_fax)."',
					`org_adres`='".addslashes($org_adres)."',
					`org_inn`='".addslashes($org_inn)."',
					`org_kpp`='".addslashes($org_kpp)."',

					`from_id`="._clientFromGet()."
			   WHERE `id`=".$client_id;
		query($sql);

		if($join) {
			query("UPDATE `_money_accrual` SET `client_id`=".$client_id." WHERE `client_id`=".$client2);
			query("UPDATE `_money_income` SET `client_id`=".$client_id." WHERE `client_id`=".$client2);
			query("UPDATE `_money_refund` SET `client_id`=".$client_id." WHERE `client_id`=".$client2);
			query("UPDATE `_schet` SET `client_id`=".$client_id." WHERE `client_id`=".$client2);
			query("UPDATE `_remind` SET `client_id`=".$client_id." WHERE `client_id`=".$client2);
			query("UPDATE `_note` SET `page_id`=".$client_id."  WHERE `page_name`='client' AND `page_id`=".$client2);
			query("UPDATE `_zayav`	SET `client_id`=".$client_id." WHERE `client_id`=".$client2);
//			query("UPDATE `zp_move`	SET `client_id`=".$client_id." WHERE `client_id`=".$client2);
			query("UPDATE `_client` SET `deleted`=1,`join_id`=".$client_id." WHERE `id`=".$client2);

			// доверенные лица переносятся новому клиенту
			$sql = "UPDATE `_client`
					SET `client_id_person`=".$client_id."
					WHERE `client_id_person`=".$client2;
			query($sql);

//			_clientBalansUpdate($client_id);
			//баланс для клиента
			_balans(array(
				'action_id' => 38,//Объединение с другим клиентом
				'client_id' => $client_id
			));

			_history(array(
				'type_id' => 3,
				'client_id' => $client_id,
				'v1' => _clientVal($client2, 'name')
			));
		}

		_clientFindUpdate($client_id);

		$changes =
			_historyChange('Категория', _clientCategory($c['category_id'], 1), _clientCategory($category_id, 1)).
			(!ORG ?
				_historyChange('ФИО', $c['fio'], $fio).
				_historyChange('Телефон', $c['phone'], $phone).
				_historyChange('Адрес', $c['adres'], $adres).
				_historyChange('Связан с сотрудником',
								$c['worker_id'] ? _viewer($c['worker_id'], 'viewer_name') : '',
								$worker_id ? _viewer($worker_id, 'viewer_name') : '').
				_historyChange('Паспорт серия', $c['pasp_seria'], $pasp_seria).
				_historyChange('Паспорт номер', $c['pasp_nomer'], $pasp_nomer).
				_historyChange('Паспорт прописка', $c['pasp_adres'], $pasp_adres).
				_historyChange('Паспорт кем выдан', $c['pasp_ovd'], $pasp_ovd).
				_historyChange('Паспорт когда выдан', $c['pasp_data'], $pasp_data)
			:
				_historyChange('Название организации', $c['org_name'], $org_name).
				_historyChange('Телефон', $c['org_phone'], $org_phone).
				_historyChange('Факс', $c['org_fax'], $org_fax).
				_historyChange('Адрес', $c['org_adres'], $org_adres).
				_historyChange('ИНН', $c['org_inn'], $org_inn).
				_historyChange('КПП', $c['org_kpp'], $org_kpp)
			);

		if($changes)
			_history(array(
				'type_id' => 2,
				'client_id' => $client_id,
				'v1' => '<table>'.$changes.'</table>'
			));

		jsonSuccess();
		break;
	case 'client_del':
		if(!$client_id = _num($_POST['id']))
			jsonError();

		if(!$r = _clientQuery($client_id))
			jsonError();

		if(!_clientDelAccess($client_id))
			jsonError();

		$sql = "UPDATE `_client` SET `deleted`=1 WHERE `id`=".$client_id;
		query($sql);

		_history(array(
			'type_id' => 4,
			'client_id' => $client_id
		));
		jsonSuccess();
		break;

	case 'client_person_add':
		if(!$client_id = _num($_POST['client_id']))
			jsonError();

		if(!$c = _clientQuery($client_id))
			jsonError();

		$fio = _txt($_POST['fio']);
		$phone = _txt($_POST['phone']);
		$adres = _txt($_POST['adres']);
		$post = _txt($_POST['post']);
		$pasp_seria = _txt($_POST['pasp_seria']);
		$pasp_nomer = _txt($_POST['pasp_nomer']);
		$pasp_adres = _txt($_POST['pasp_adres']);
		$pasp_ovd = _txt($_POST['pasp_ovd']);
		$pasp_data = _txt($_POST['pasp_data']);

		if(empty($fio))
			jsonError();

		$sql = "INSERT INTO `_client` (
					`app_id`,
					`category_id`,
					`client_id_person`,
					`fio`,
					`phone`,
					`adres`,
					`post`,
					`pasp_seria`,
					`pasp_nomer`,
					`pasp_adres`,
					`pasp_ovd`,
					`pasp_data`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					1,
					".$client_id.",
					'".addslashes($fio)."',
					'".addslashes($phone)."',
					'".addslashes($adres)."',
					'".addslashes($post)."',
					'".addslashes($pasp_seria)."',
					'".addslashes($pasp_nomer)."',
					'".addslashes($pasp_adres)."',
					'".addslashes($pasp_ovd)."',
					'".addslashes($pasp_data)."',
					".VIEWER_ID."
				)";
		query($sql);
		$person_id = query_insert_id('_client');

		_clientFindUpdate($client_id);
		_clientFindUpdate($person_id);

		_history(array(
			'type_id' => 5,
			'client_id' => $client_id,
			'v1' => $fio
		));

		$send['html'] = utf8(_clientInfoPerson($client_id));
		$send['array'] = _clientInfoPerson($client_id, 'array');
		jsonSuccess($send);
		break;
	case 'client_person_edit':
		if(!$person_id = _num($_POST['id']))
			jsonError();
		if(!$client_id = _num($_POST['client_id']))
			jsonError();

		if(!$c = _clientQuery($client_id))
			jsonError();

		if($person_id != $c['client_id_person'])
		
		if(!$p = _clientQuery($person_id))
			jsonError();

		$fio = _txt($_POST['fio']);
		$phone = _txt($_POST['phone']);
		$adres = _txt($_POST['adres']);
		$post = _txt($_POST['post']);
		$pasp_seria = _txt($_POST['pasp_seria']);
		$pasp_nomer = _txt($_POST['pasp_nomer']);
		$pasp_adres = _txt($_POST['pasp_adres']);
		$pasp_ovd = _txt($_POST['pasp_ovd']);
		$pasp_data = _txt($_POST['pasp_data']);

		if(empty($fio))
			jsonError();

		$sql = "UPDATE `_client`
				SET `fio`='".addslashes($fio)."',
					`phone`='".addslashes($phone)."',
					`adres`='".addslashes($adres)."',
					`post`='".addslashes($post)."',
					`pasp_seria`='".addslashes($pasp_seria)."',
					`pasp_nomer`='".addslashes($pasp_nomer)."',
					`pasp_adres`='".addslashes($pasp_adres)."',
					`pasp_ovd`='".addslashes($pasp_ovd)."',
					`pasp_data`='".addslashes($pasp_data)."'
				WHERE `id`=".$person_id;
		query($sql);

		_clientFindUpdate($client_id);
		_clientFindUpdate($person_id);

		$changes =
			_historyChange('ФИО', $p['fio'], $fio).
			_historyChange('Телефон', $p['phone'], $phone).
			_historyChange('Адрес', $p['adres'], $adres).
			_historyChange('Должность', $p['post'], $post).
			_historyChange('Паспорт серия', $p['pasp_seria'], $pasp_seria).
			_historyChange('Паспорт номер', $p['pasp_nomer'], $pasp_nomer).
			_historyChange('Паспорт прописка', $p['pasp_adres'], $pasp_adres).
			_historyChange('Паспорт кем выдан', $p['pasp_ovd'], $pasp_ovd).
			_historyChange('Паспорт когда выдан', $p['pasp_data'], $pasp_data);
		if($changes)
			_history(array(
				'type_id' => 6,
				'client_id' => $client_id,
				'v1' => $fio,
				'v2' => '<table>'.$changes.'</table>'
			));

		$send['html'] = utf8(_clientInfoPerson($client_id));
		$send['array'] = _clientInfoPerson($client_id, 'array');
		jsonSuccess($send);
		break;
	case 'client_person_del':
		if(!$person_id = _num($_POST['id']))
			jsonError();


		if(!$p = _clientQuery($person_id))
			jsonError();

		$client_id = $p['client_id_person'];
		if(!$c = _clientQuery($client_id))
			jsonError();

		$sql = "UPDATE `_client`
				SET `client_id_person`=0,
					`poa_nomer`='',
					`poa_date_begin`='0000-00-00',
					`poa_date_end`='0000-00-00',
					`poa_attach_id`=0
				WHERE `id`=".$person_id;
		query($sql);

		_clientFindUpdate($client_id);

		_history(array(
			'type_id' => 7,
			'client_id' => $client_id,
			'v1' => $p['fio']
		));

		$send['html'] = utf8(_clientInfoPerson($client_id));
		$send['array'] = _clientInfoPerson($client_id, 'array');
		jsonSuccess($send);
		break;

	case 'client_poa_add'://внесение информации о доверенности
		if(!$person_id = _num($_POST['person_id']))
			jsonError();
		if(!preg_match(REGEXP_DATE, $_POST['date_begin']))
			jsonError();
		if(!preg_match(REGEXP_DATE, $_POST['date_end']))
			jsonError();

		$nomer = _txt($_POST['nomer']);
		$date_begin = $_POST['date_begin'];
		$date_end = $_POST['date_end'];
		$attach_id = _num($_POST['attach_id']);

		if(!$p = _clientQuery($person_id))
			jsonError();

		$client_id = $p['client_id_person'];
		if(!$c = _clientQuery($client_id))
			jsonError();

		$sql = "UPDATE `_client`
				SET `poa_nomer`='".addslashes($nomer)."',
					`poa_date_begin`='".$date_begin."',
					`poa_date_end`='".$date_end."',
					`poa_attach_id`=".$attach_id."
				WHERE `id`=".$person_id;
		query($sql);

		_clientFindUpdate($client_id);

		if(!$p['poa_nomer'])
			_history(array(
				'type_id' => 94,
				'client_id' => $client_id,
				'v1' => $nomer,
				'v2' => FullData($date_end)
			));

		$send['html'] = utf8(_clientInfoPerson($client_id));
		$send['array'] = _clientInfoPerson($client_id, 'array');
		jsonSuccess($send);
		break;
	case 'client_poa_del'://удаление доверенности
		if(!$person_id = _num($_POST['person_id']))
			jsonError();

		if(!$p = _clientQuery($person_id))
			jsonError();

		if(!$p['poa_nomer'])
			jsonError();

		$client_id = $p['client_id_person'];
		if(!$c = _clientQuery($client_id))
			jsonError();

		$sql = "UPDATE `_client`
				SET `poa_nomer`='',
					`poa_date_begin`='0000-00-00',
					`poa_date_end`='0000-00-00',
					`poa_attach_id`=0
				WHERE `id`=".$person_id;
		query($sql);

		_clientFindUpdate($client_id);

		_history(array(
			'type_id' => 95,
			'client_id' => $client_id,
			'v1' => $p['poa_nomer']
		));

		$send['html'] = utf8(_clientInfoPerson($client_id));
		$send['array'] = _clientInfoPerson($client_id, 'array');
		jsonSuccess($send);
		break;

	case 'client_from_add'://внесение нового источника, откуда приходит клиент
		if(!_clientFromGet())
			jsonError();

		$send['spisok'] = utf8(_client_from_spisok());
		jsonSuccess($send);
		break;
	case 'client_from_edit'://редактирование источника, откуда приходит клиент
		if(!$id = _num($_POST['from_id']))
			jsonError();

		$name = _txt($_POST['from_name']);
		if(empty($name))
			jsonError();

		$sql = "SELECT *
				FROM `_client_from`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_client_from`
				SET `name`='".addslashes($name)."'
				WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'client_from'.APP_ID);
		_appJsValues();

		$send['spisok'] = utf8(_client_from_spisok());
		jsonSuccess($send);
		break;
	case 'client_from_del'://удаление источника, откуда приходит клиент
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `_client_from`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "SELECT COUNT(`id`)
				FROM `_client`
				WHERE `app_id`=".APP_ID."
				  AND `from_id`=".$id;
		if(query_value($sql))
			jsonError();

		$sql = "DELETE FROM `_client_from` WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'client_from'.APP_ID);
		_appJsValues();

		jsonSuccess();
		break;
	case 'client_from_setup'://настройка использования источников
		if(!VIEWER_ADMIN)
			jsonError();

		$use = _bool($_POST['use']);
		$require = $use ? _bool($_POST['require']) : 0;

		$sql = "UPDATE `_app`
				SET `client_from_use`=".$use.",
					`client_from_require`=".$require."
				WHERE `id`=".APP_ID;
		query($sql);

		xcache_unset(CACHE_PREFIX.'app'.APP_ID);
		_appJsValues();

		jsonSuccess();
		break;

}

function _clientFindUpdate($client_id) {// обновление быстрого поиска клиента
	if(!$r = _clientQuery($client_id, 1))
		return;

	$sql = "UPDATE `_client`
			SET `find`=CONCAT(
			    IFNULL(CONCAT(`org_name`, ' '), ''),
			    IFNULL(CONCAT(`org_phone`, ' '), ''),
			    IFNULL(CONCAT(`org_fax`, ' '), ''),
			    IFNULL(CONCAT(`org_adres`, ' '), ''),
			    IFNULL(CONCAT(`org_inn`, ' '), ''),
			    IFNULL(CONCAT(`org_kpp`, ' '), ''),
			    IFNULL(CONCAT(`fio`, ' '), ''),
			    IFNULL(CONCAT(`phone`, ' '), ''),
			    IFNULL(CONCAT(`adres`, ' '), ''),
			    IFNULL(CONCAT(`email`, ' '), ''),
			    IFNULL(CONCAT(`org_email`, ' '), '')
			) WHERE `id`=".$client_id;
	query($sql);
}
function _clientFromGet() {//получение id источника откуда пришёл клиент
	if($from_id = _num($_POST['from_id']))
		return $from_id;

	$name = _txt($_POST['from_name']);
	if(empty($name))
		return 0;

	$sql = "SELECT IFNULL(`id`,0)
	        FROM `_client_from`
			WHERE `app_id`=".APP_ID."
			  AND `name`='".addslashes($name)."'
			LIMIT 1";
	if(!$from_id = query_value($sql)) {
		$sql = "INSERT INTO `_client_from` (
					`app_id`,
					`name`
				) VALUES (
					".APP_ID.",
					'".addslashes($name)."'
				)";
		query($sql);

		$from_id = query_insert_id('_client_from');

		xcache_unset(CACHE_PREFIX.'client_from'.APP_ID);
		_appJsValues();
	}
	return $from_id;
}