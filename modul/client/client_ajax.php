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
			$name = $r['name'];
			$phone = $r['phone'];
			$adres = $r['adres'];
			$unit = array(
				'uid' => $r['id'],
				'title' => utf8(htmlspecialchars_decode($name))
			);
			if($phone)
				$unit['content'] = utf8($name.'<span>'.$phone.'</span>');
			if($adres)
				$unit['adres'] = utf8($adres);
			$unit['skidka'] = _num($r['skidka']);
			$send['spisok'][] = $unit;
		}
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
	case 'client_edit_load'://загрузка данных заявки для внесения или редактирования
		if(!$category_id = _num($_POST['category_id']))
			$category_id = _clientCategory('default');

		$client_id = _num($_POST['client_id']);
		if($c = _clientQuery($client_id, 1))
			$category_id = $c['category_id'];

		$link = '';
		if(!$client_id && _clientCategory('count') > 1) {
			$i = 0;
			foreach(_clientCategory('all') as $r) {
				$sel = !$i++ ? ' sel' : '';
				$link .= '<a class="link'.$sel.'" val="'.$r['id'].'">'.$r['name'].'</a>';
			}
			$link = '<div id="dopLinks" class="center">'.$link.'</div>';
		}

		$_POST['category_id'] = $category_id;
		$_POST['type_id'] = 1;
		$_POST['i'] = 'edit';
		$tabs = '';
		$i = 0;
		foreach(_clientCategory('all') as $r) {
			if($client_id && $category_id != $r['id'])
				continue;

			$_POST['category_id'] = $r['id'];
			$tabs .=
				'<div class="tabs tab'.$r['id'].($i++ ? ' dn' : '').'">'.
					'<table class="bs10">'._clientPole($_POST + $c).'</table>'.
				'</div>';
		}

		$from =
			'<table class="bs10 mb10'.(_app('client_from_use') ? '' : ' dn').'">'.
				'<tr><td class="label r"><span id="td-from">Откуда клиент нашёл нас:</span>'.
					'<td><input type="hidden" id="from_id" value="0" />'.
			'</table>';

		$send['html'] = utf8(
			$link.
			'<input type="hidden" id="ce-category_id" value="'.$category_id.'" />'.
			$tabs.
			$from
		);
		jsonSuccess($send);
		break;
	case 'client_add':
		if(!$category_id = _num($_POST['category_id']))
			jsonError('Некорректный id категории');

		$update = _clientValuesCheck($category_id);

		$sql = "INSERT INTO `_client` (
					`app_id`,
					`category_id`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$category_id.",
					".VIEWER_ID."
				)";
		query($sql);

		$client_id = query_insert_id('_client');

		$sql = "UPDATE `_client` SET ".$update." WHERE `id`=".$client_id;
		query($sql);

		_clientFindUpdate($client_id);

		_history(array(
			'type_id' => 1,
			'client_id' => $client_id
		));

		$send['id'] = $client_id;
		jsonSuccess($send);
		break;
	case 'client_edit':
		if(!$client_id = _num($_POST['id']))
			jsonError('Некорректный id клиента');

		if(!$c = _clientQuery($client_id))
			jsonError('Клиента не существует');

		$update = _clientValuesCheck($c['category_id']);

		if($worker_id = _num($_POST['worker_id'])) {
			$sql = "SELECT COUNT(`id`)
					FROM `_client`
					WHERE `app_id`=".APP_ID."
			          AND `id`!=".$client_id."
					  AND `worker_id`=".$worker_id;
			if(query_value($sql))
				jsonError('Этот сотрудник связан с другим клиентом');
		}

		$sql = "UPDATE `_client` SET ".$update." WHERE `id`=".$client_id;
		query($sql);

		_clientFindUpdate($client_id);


/*
		$join = _bool($_POST['join']);
		$client2 = _num($_POST['client2']);

		if($join) {
			if(!$client2)
				jsonError();
			if($client_id == $client2)
				jsonError();
			if(!_clientQuery($client2, 1))
				jsonError();
		}
		if($join) {
			query("UPDATE `_money_accrual` SET `client_id`=".$client_id." WHERE `client_id`=".$client2);
			query("UPDATE `_money_income` SET `client_id`=".$client_id." WHERE `client_id`=".$client2);
			query("UPDATE `_money_refund` SET `client_id`=".$client_id." WHERE `client_id`=".$client2);
			query("UPDATE `_schet_pay` SET `client_id`=".$client_id." WHERE `client_id`=".$client2);
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
*/

		$v = _clientQuery($client_id);
		$cp = _clientPole(array(
			'i' => 'use',
			'category_id' => $v['category_id']
		));

		$changes = '';
		if(isset($cp[1]))
			$changes .= _historyChange($cp[1]['name'], $c['name'], $v['name']);
		if(isset($cp[2]))
			$changes .= _historyChange($cp[2]['name'], $c['phone'], $v['phone']);
		if(isset($cp[3]))
			$changes .= _historyChange($cp[3]['name'], $c['adres'], $v['adres']);
		if(isset($cp[4]))
			$changes .= _historyChange($cp[4]['name'], $c['post'], $v['post']);
		if(isset($cp[5]))
			$changes .=
				_historyChange('Паспорт серия', $c['pasp_seria'], $v['pasp_seria']).
				_historyChange('Паспорт номер', $c['pasp_nomer'], $v['pasp_nomer']).
				_historyChange('Паспорт прописка', $c['pasp_adres'], $v['pasp_adres']).
				_historyChange('Паспорт кем выдан', $c['pasp_ovd'], $v['pasp_ovd']).
				_historyChange('Паспорт когда выдан', $c['pasp_data'], $v['pasp_data']);
		if(isset($cp[6]))
			$changes .= _historyChange($cp[6]['name'], $c['fax'], $v['fax']);
		if(isset($cp[7]))
			$changes .= _historyChange($cp[7]['name'], $c['email'], $v['email']);
		if(isset($cp[8]))
			$changes .= _historyChange($cp[8]['name'], $c['inn'], $v['inn']);
		if(isset($cp[9]))
			$changes .= _historyChange($cp[9]['name'], $c['kpp'], $v['kpp']);
		if(isset($cp[10]))
			$changes .= _historyChange($cp[10]['name'], $c['skidka'].'%', $v['skidka'].'%');
		if(isset($cp[11]))
			$changes .= _historyChange(
							$cp[11]['name'],
							$c['worker_id'] ? _viewer($c['worker_id'], 'viewer_name') : '',
							$v['worker_id'] ? _viewer($v['worker_id'], 'viewer_name') : ''
						);

		if($changes)
			_history(array(
				'type_id' => 2,
				'client_id' => $client_id,
				'v1' => '<table>'.$changes.'</table>'
			));

		$send['id'] = $client_id;
		jsonSuccess($send);
		break;
	case 'client_del':
		if(!$client_id = _num($_POST['id']))
			jsonError('Некорректный id клиента');

		if(!$r = _clientQuery($client_id))
			jsonError('Клиента не существует');

		if($r['deleted'])
			jsonError('Клиент уже был удалён');

		$delAccess = _clientDelAccess($client_id);
		if($delAccess !== true)
			jsonError($delAccess);

		$sql = "UPDATE `_client` SET `deleted`=1 WHERE `id`=".$client_id;
		query($sql);

		_history(array(
			'type_id' => 4,
			'client_id' => $client_id
		));
		jsonSuccess();
		break;

	case 'client_person_load'://загрузка данных заявки для внесения или редактирования
		if(!$category_id = _clientPole(array('i'=>'pole_use','pole_id'=>12)))
			jsonError();

		$v = array(
			'i' => 'edit',
			'type_id' => 1,
			'category_id' => $category_id
		);

		$send['html'] = utf8(
			'<table class="bs10">'._clientPole($v).'</table>'
		);
		
		$send['category_id'] = $category_id;
		
		jsonSuccess($send);
		break;
	case 'client_person_add':
		if(!$category_id = _num($_POST['category_id']))
			jsonError('Некорректный id категории');

		if(!$client_id = _num($_POST['client_id']))
			jsonError('Некорректный id клиента');

		if(!$c = _clientQuery($client_id))
			jsonError('Клиента не существует');

		if($person_id = _num($_POST['person_id'])) {
			if(!$c = _clientQuery($person_id))
				jsonError();
			$sql = "SELECT COUNT(*)
					FROM `_client_person`
					WHERE `client_id`=".$client_id."
					  AND `person_id`=".$person_id;
			if(query_value($sql))
				jsonError('Указанный клиент уже является доверенным лицом');
		}

		if(!$person_id) {
			$update = _clientValuesCheck($category_id);

			$sql = "INSERT INTO `_client` (
						`app_id`,
						`category_id`,
						`viewer_id_add`
					) VALUES (
						".APP_ID.",
						".$category_id.",
						".VIEWER_ID."
					)";
			query($sql);

			$person_id = query_insert_id('_client');

			$sql = "UPDATE `_client` SET ".$update." WHERE `id`=".$person_id;
			query($sql);

			_clientFindUpdate($person_id);

			_history(array(
				'type_id' => 1,
				'client_id' => $person_id
			));
		}

		$sql = "INSERT INTO `_client_person` (
					`app_id`,
					`client_id`,
					`person_id`
				) VALUES (
					".APP_ID.",
					".$client_id.",
					".$person_id."
				)";
		query($sql);
		$insert_id = query_insert_id('_client_person');

		_clientFindUpdate($client_id);

		_history(array(
			'type_id' => 5,
			'client_id' => $client_id,
			'v1' => _clientVal($person_id, 'name')
		));

		$send['html'] = utf8(_clientInfoPerson($client_id));
		$send['array'] = _clientInfoPersonArr($client_id);
		jsonSuccess($send);
		break;
	case 'client_person_del':
		if(!$id = _num($_POST['id']))
			jsonError('Некорректный id доверенного лица');

		$sql = "SELECT *
				FROM `_client_person`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('Доверенного лица не существует');

		$sql = "DELETE FROM `_client_person` WHERE `id`=".$id;
		query($sql);

		_history(array(
			'type_id' => 7,
			'client_id' => $r['client_id'],
			'v1' => _clientVal($r['person_id'], 'name')
		));

		$send['html'] = utf8(_clientInfoPerson($r['client_id']));
		$send['array'] = _clientInfoPersonArr($r['client_id']);
		jsonSuccess($send);
		break;

	case 'client_poa_spisok':
		$data = _clientPoaSpisok($_POST);
		if($data['filter']['page'] == 1)
			$send['all'] = utf8($data['result']);
		$send['spisok'] = utf8($data['spisok']);
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

		$sql = "SELECT *
				FROM `_client_person`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$person_id;
		if(!$p = query_assoc($sql))
			jsonError('Доверенного лица не существует');

		$client_id = $p['person_id'];
		if(!$c = _clientQuery($client_id))
			jsonError('Клиента не существует');

		$sql = "UPDATE `_client_person`
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
		$send['array'] = _clientInfoPersonArr($client_id);
		jsonSuccess($send);
		break;
	case 'client_poa_del'://удаление доверенности
		if(!$person_id = _num($_POST['person_id']))
			jsonError();

		$sql = "SELECT *
				FROM `_client_person`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$person_id;
		if(!$p = query_assoc($sql))
			jsonError('Доверенного лица не существует');

		if(!$p['poa_nomer'])
			jsonError();

		$client_id = $p['person_id'];
		if(!$c = _clientQuery($client_id))
			jsonError();

		$sql = "UPDATE `_client_person`
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
		$send['array'] = _clientInfoPersonArr($client_id);
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

		xcache_unset(CACHE_PREFIX.'client_from');
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

		xcache_unset(CACHE_PREFIX.'client_from');
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

		xcache_unset(CACHE_PREFIX.'app');
		_appJsValues();

		jsonSuccess();
		break;
}
function _clientValuesCheck($category_id) {//проверка корректности полей при внесении/редактировании клиента
	$cp = _clientPole(array(
		'i' => 'use',
		'category_id' => $category_id
	));

	$upd = array();

	$val = _txt(@$_POST['name']);
	if($u = @$cp[1]) {
		if($u['require'] && !$val)
			jsonError('Не заполнено поле '.$u['name']);
		$upd[] = "`name`='".addslashes($val)."'";
	}

	$val = _txt(@$_POST['phone']);
	if($u = @$cp[2]) {
		if($u['require'] && !$val)
			jsonError('Не заполнено поле '.$u['name']);
		$upd[] = "`phone`='".addslashes($val)."'";
	}

	$val = _txt(@$_POST['adres']);
	if($u = @$cp[3]) {
		if($u['require'] && !$val)
			jsonError('Не заполнено поле '.$u['name']);
		$upd[] = "`adres`='".addslashes($val)."'";
	}

	$val = _txt(@$_POST['post']);
	if($u = @$cp[4]) {
		if($u['require'] && !$val)
			jsonError('Не заполнено поле '.$u['name']);
		$upd[] = "`post`='".addslashes($val)."'";
	}

	$seria = _txt(@$_POST['pasp_seria']);
	$nomer = _txt(@$_POST['pasp_nomer']);
	$adres = _txt(@$_POST['pasp_adres']);
	$ovd = _txt(@$_POST['pasp_ovd']);
	$data = _txt(@$_POST['pasp_data']);
	if($u = @$cp[5]) {
		$upd[] = "`pasp_seria`='".addslashes($seria)."'";
		$upd[] = "`pasp_nomer`='".addslashes($nomer)."'";
		$upd[] = "`pasp_adres`='".addslashes($adres)."'";
		$upd[] = "`pasp_ovd`='".addslashes($ovd)."'";
		$upd[] = "`pasp_data`='".addslashes($data)."'";
	}

	$val = _txt(@$_POST['fax']);
	if($u = @$cp[6]) {
		if($u['require'] && !$val)
			jsonError('Не заполнено поле '.$u['name']);
		$upd[] = "`fax`='".addslashes($val)."'";
	}

	$val = _txt(@$_POST['email']);
	if($u = @$cp[7]) {
		if($u['require'] && !$val)
			jsonError('Не заполнено поле '.$u['name']);
		$upd[] = "`email`='".addslashes($val)."'";
	}

	$val = _txt(@$_POST['inn']);
	if($u = @$cp[8]) {
		if($u['require'] && !$val)
			jsonError('Не заполнено поле '.$u['name']);
		$upd[] = "`inn`='".addslashes($val)."'";
	}

	$val = _txt(@$_POST['kpp']);
	if($u = @$cp[9]) {
		if($u['require'] && !$val)
			jsonError('Не заполнено поле '.$u['name']);
		$upd[] = "`kpp`='".addslashes($val)."'";
	}

	$val = _num(@$_POST['skidka']);
	if($u = @$cp[10]) {
		if($u['require'] && !$val)
			jsonError('Не указано поле '.$u['name']);
		$upd[] = "`skidka`=".$val;
	}

	$val = _num(@$_POST['worker_id']);
	if($u = @$cp[11])
		$upd[] = "`worker_id`=".$val;

	$val = _txt(@$_POST['ogrn']);
	if($u = @$cp[13]) {
		if($u['require'] && !$val)
			jsonError('Не заполнено поле '.$u['name']);
		$upd[] = "`ogrn`='".addslashes($val)."'";
	}



	if($_POST['op'] != 'client_person_add') {//если добавляется не доверенное лицо
		$val = _clientFromGet();
		if(_app('client_from_use')) {
			if(_app('client_from_require') && !$val)
				jsonError('Не указан источник, откуда пришёл клиент');
			$upd[] = "`from_id`=".$val;
		}
	}

	if(empty($upd))
		jsonError('Нет данных');

	return implode(',', $upd);
}
function _clientFindUpdate($client_id) {//обновление быстрого поиска клиента
	if(!$r = _clientQuery($client_id, 1))
		return;

	$sql = "UPDATE `_client`
			SET `find`=CONCAT(
			    IFNULL(CONCAT(`name`, ' '), ''),
			    IFNULL(CONCAT(`phone`, ' '), ''),
			    IFNULL(CONCAT(`adres`, ' '), ''),
			    IFNULL(CONCAT(`fax`, ' '), ''),
			    IFNULL(CONCAT(`email`, ' '), ''),
			    IFNULL(CONCAT(`inn`, ' '), ''),
			    IFNULL(CONCAT(`kpp`, ' '), ''),
			    IFNULL(CONCAT(`ogrn`, ' '), '')
			) WHERE `id`=".$client_id;
	query($sql);
}
function _clientFromGet() {//получение id источника откуда пришёл клиент
	if($from_id = _num(@$_POST['from_id']))
		return $from_id;

	$name = _txt(@$_POST['from_name']);
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

		xcache_unset(CACHE_PREFIX.'client_from');
		_appJsValues();
	}
	return $from_id;
}