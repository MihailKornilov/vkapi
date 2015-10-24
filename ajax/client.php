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
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`".
			(!empty($val) ? " AND (`find` LIKE '%".$val."%')" : '').
			($category_id ? " AND `category_id`=".$category_id : '').
			($client_id ? " AND `id`<=".$client_id : '').
			($not_client_id ? " AND `id`!=".$not_client_id : '')."
				ORDER BY `id` DESC
				LIMIT 50";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		if(!mysql_num_rows($q))
			jsonSuccess($send);

		$spisok = array();
		while($r = mysql_fetch_assoc($q))
			$spisok[$r['id']] = $r;

		// фио и телефоны клиентов
		$sql = "SELECT *
			FROM `_client_person`
			WHERE `client_id` IN (".implode(',', array_keys($spisok)).")
			ORDER BY `client_id`,`id`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		$k = 0;
		$client_id = key($spisok);
		while($r = mysql_fetch_assoc($q)) {
			if($client_id != $r['client_id']) {
				$client_id = $r['client_id'];
				$k = 0;
			}

			if(!$k) {
				$spisok[$r['client_id']]['fio'] = $r['fio'];
				$spisok[$r['client_id']]['phone'] = $r['phone'];
			}
			$k++;
		}

		foreach($spisok as $r) {
			$name = $r['category_id'] == 1 ? $r['fio'] : $r['org_name'];
			$phone = $r['category_id'] == 1 ? $r['phone'] : $r['org_phone'];
			$unit = array(
				'uid' => $r['id'],
				'title' => utf8(htmlspecialchars_decode($name))
			);
			if($phone)
				$unit['content'] = utf8($name.'<span>'.$phone.'</span>');
			$send['spisok'][] = $unit;
		}
		jsonSuccess($send);
		break;
	case 'client_add':
		if(!$category_id = _num($_POST['category_id']))
			jsonError();

		define('ORG', $category_id > 1);

		$org_name =  ORG ? _txt($_POST['org_name']) : '';
		$org_phone = ORG ? _txt($_POST['org_phone']) : '';
		$org_fax =   ORG ? _txt($_POST['org_fax']) : '';
		$org_adres = ORG ? _txt($_POST['org_adres']) : '';
		$org_inn =   ORG ? _txt($_POST['org_inn']) : '';
		$org_kpp =   ORG ? _txt($_POST['org_kpp']) : '';

		if(!ORG && empty($_POST['person']))//Для частного лица обязательно указывается ФИО
			jsonError();
		if(ORG && empty($org_name))//Для организаций обязательно указывается Название организации
			jsonError();

		$sql = "INSERT INTO `_client` (
					`app_id`,
					`ws_id`,
					`category_id`,
					`org_name`,
					`org_phone`,
					`org_fax`,
					`org_adres`,
					`org_inn`,
					`org_kpp`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".WS_ID.",
					".$category_id.",
					'".addslashes($org_name)."',
					'".addslashes($org_phone)."',
					'".addslashes($org_fax)."',
					'".addslashes($org_adres)."',
					'".addslashes($org_inn)."',
					'".addslashes($org_kpp)."',
					".VIEWER_ID."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$sql = "SELECT `id` FROM `_client` WHERE `app_id`=".APP_ID." ORDER BY `id` DESC LIMIT 1";
		$client_id = query_value($sql, GLOBAL_MYSQL_CONNECT);

		if(!empty($_POST['person']))
			foreach($_POST['person'] as $r) {
				$r['client_id'] = $client_id;
				_clientPersonInsert($r);
			}

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
		$data = client_data($_POST);
		if($data['filter']['page'] == 1)
			$send['all'] = utf8($data['result']);
		$send['spisok'] = utf8($data['spisok']);
		jsonSuccess($send);
		break;
	case 'client_edit':
		if(!$client_id = _num($_POST['id']))
			jsonError();

		if(!$r = _clientQuery($client_id))
			jsonError();

		define('ORG', $r['category_id'] > 1);

		$org_name = ORG ? _txt($_POST['org_name']) : '';
		$org_phone = ORG ? _txt($_POST['org_phone']) : '';
		$org_fax = ORG ? _txt($_POST['org_fax']) : '';
		$org_adres = ORG ? _txt($_POST['org_adres']) : '';
		$org_inn = ORG ? _txt($_POST['org_inn']) : '';
		$org_kpp = ORG ? _txt($_POST['org_kpp']) : '';
		$join = _bool($_POST['join']);
		$client2 = _num($_POST['client2']);

		if(!ORG) { // Для частного лица обязательно указывается ФИО
			if(!$person_id = _num($_POST['person_id']))
				jsonError();

			$fio = _txt($_POST['fio']);
			$phone = _txt($_POST['phone']);
			$adres = _txt($_POST['adres']);
			$pasp_seria = _txt($_POST['pasp_seria']);
			$pasp_nomer = _txt($_POST['pasp_nomer']);
			$pasp_adres = _txt($_POST['pasp_adres']);
			$pasp_ovd = _txt($_POST['pasp_ovd']);
			$pasp_data = _txt($_POST['pasp_data']);
			if(empty($fio))
				jsonError();

			$sql = "SELECT * FROM `_client_person` WHERE `client_id`=".$client_id." AND `id`=".$person_id;
			if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
				jsonError();

			$sql = "UPDATE `_client_person`
					SET `fio`='".addslashes($fio)."',
						`phone`='".addslashes($phone)."',
						`adres`='".addslashes($adres)."',
						`pasp_seria`='".addslashes($pasp_seria)."',
						`pasp_nomer`='".addslashes($pasp_nomer)."',
						`pasp_adres`='".addslashes($pasp_adres)."',
						`pasp_ovd`='".addslashes($pasp_ovd)."',
						`pasp_data`='".addslashes($pasp_data)."'
					WHERE `id`=".$person_id;
			query($sql, GLOBAL_MYSQL_CONNECT);
		} else
			if(empty($org_name))//Для организации обязательно указывается Название организации
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
				SET `org_name`='".addslashes($org_name)."',
					`org_phone`='".addslashes($org_phone)."',
					`org_fax`='".addslashes($org_fax)."',
					`org_adres`='".addslashes($org_adres)."',
					`org_inn`='".addslashes($org_inn)."',
					`org_kpp`='".addslashes($org_kpp)."'
			   WHERE `id`=".$client_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		if($join) {
//			query("UPDATE `accrual`	SET `client_id`=".$client_id." WHERE `client_id`=".$client2);
//			query("UPDATE `money`	SET `client_id`=".$client_id." WHERE `client_id`=".$client2);
//			query("UPDATE `vk_comment` SET `table_id`=".$client_id."  WHERE `table_name`='client' AND `table_id`=".$client2);
//			query("UPDATE `zayav`	SET `client_id`=".$client_id." WHERE `client_id`=".$client2);
//			query("UPDATE `zp_move`	SET `client_id`=".$client_id." WHERE `client_id`=".$client2);
			query("UPDATE `_client` SET `deleted`=1,`join_id`=".$client_id." WHERE `id`=".$client2, GLOBAL_MYSQL_CONNECT);

			// доверенные лица переносятся новому клиенту. Если это частное лицо, то первый по порядку не трогается
			$sql = "SELECT `id`
					FROM `_client_person`
					WHERE `client_id`=".$client2."
					ORDER BY `id`".
					(!ORG ? " LIMIT 1,1000" : '');
			if($ids = query_ids($sql, GLOBAL_MYSQL_CONNECT)) {
				$sql = "UPDATE `_client_person`
						SET `client_id`=".$client_id."
						WHERE `id` IN (".$ids.")";
				query($sql, GLOBAL_MYSQL_CONNECT);
			}

//			clientBalansUpdate($client_id);

			_history(array(
				'type_id' => 3,
				'client_id' => $client_id,
				'v1' => _clientVal($client2, 'name')
			));
		}

		_clientFindUpdate($client_id);

		$changes = !ORG ?
					_historyChange('ФИО', $r['fio'], $fio).
					_historyChange('Телефон', $r['phone'], $phone).
					_historyChange('Адрес', $r['adres'], $adres).
					_historyChange('Паспорт серия', $r['pasp_seria'], $pasp_seria).
					_historyChange('Паспорт номер', $r['pasp_nomer'], $pasp_nomer).
					_historyChange('Паспорт прописка', $r['pasp_adres'], $pasp_adres).
					_historyChange('Паспорт кем выдан', $r['pasp_ovd'], $pasp_ovd).
					_historyChange('Паспорт когда выдан', $r['pasp_data'], $pasp_data)
					:
					_historyChange('Название организации', $r['org_name'], $org_name).
					_historyChange('Телефон', $r['org_phone'], $org_phone).
					_historyChange('Факс', $r['org_fax'], $org_fax).
					_historyChange('Адрес', $r['org_adres'], $org_adres).
					_historyChange('ИНН', $r['org_inn'], $org_inn).
					_historyChange('КПП', $r['org_kpp'], $org_kpp);

		if($changes)
			_history(array(
				'type_id' => 2,
				'client_id' => $client_id,
				'v1' => '<table>'.$changes.'</table>'
			));

		jsonSuccess();
		break;
	case 'client_del':
		jsonError();//todo доделать удаление клиента
		if(!$client_id = _num($_POST['id']))
			jsonError();

		if(!$r = _clientQuery($client_id))
			jsonError();

		$sql = "UPDATE `_client` SET `deleted`=1 WHERE `id`=".$client_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		_history(array(
			'type_id' => 4,
			'client_id' => $client_id
		));
		jsonSuccess();
		break;
	case 'client_person_add':
		$r = _clientPersonInsert($_POST);

		_clientFindUpdate($r['client_id']);

		$send['html'] = utf8(_clientInfoPerson($r['client_id']));
		$send['array'] = _clientInfoPerson($r['client_id'], 'array');
		jsonSuccess($send);
		break;
	case 'client_person_del':
		if(!$person_id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT * FROM `_client_person` WHERE `id`=".$person_id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		if(!$c = _clientQuery($r['client_id']))
			jsonError();

		$sql = "DELETE FROM `_client_person` WHERE `id`=".$person_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		_clientFindUpdate($r['client_id']);

		_history(array(
			'type_id' => 7,
			'client_id' => $r['client_id'],
			'v1' => $r['fio']
		));

		$send['html'] = utf8(_clientInfoPerson($r['client_id']));
		$send['array'] = _clientInfoPerson($r['client_id'], 'array');
		jsonSuccess($send);
		break;
	case 'client_zayav_spisok':
		$_POST['limit'] = 10;
		$data = zayav_spisok($_POST);
		if($data['filter']['page'] == 1)
			$send['all'] = utf8($data['result']);
		$send['html'] = utf8($data['spisok']);
		jsonSuccess($send);
		break;
}

function _clientFindUpdate($client_id) {// обновление быстрого поиска клиента
	if(!$r = _clientQuery($client_id, 1))
		return;

	$find =
		$r['org_name'].' '.
		$r['org_phone'].' '.
		$r['org_fax'].' '.
		$r['org_adres'].' '.
		$r['org_inn'].' '.
		$r['org_kpp'].' ';

	$sql = "SELECT * FROM `_client_person` WHERE `client_id`=".$client_id." ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$person = array();
	while($r = mysql_fetch_assoc($q))
		$person[] = $r['fio'].' '.$r['phone'].' '.$r['adres'];

	$find .= implode(' ', $person);

	$sql = "UPDATE `_client` SET `find`='".addslashes($find)."' WHERE `id`=".$client_id;
	query($sql, GLOBAL_MYSQL_CONNECT);
}//_clientFindUpdate()
function _clientPersonInsert($v) {//внесение или обновление доверенного лица
	$person_id = _num(@$v['person_id']);
	$client_id = 0;
	if($person_id) {
		$sql = "SELECT * FROM `_client_person` WHERE `id`=".$person_id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();
		$client_id = $r['client_id'];
	}

	if(!$client_id)
		$client_id = _num(@$v['client_id']);

	if(!$c = _clientQuery($client_id))
		jsonError();

	$fio = _txt($v['fio']);
	$phone = _txt($v['phone']);
	$adres = _txt($v['adres']);
	$post = _txt($v['post']);
	$pasp_seria = _txt($v['pasp_seria']);
	$pasp_nomer = _txt($v['pasp_nomer']);
	$pasp_adres = _txt($v['pasp_adres']);
	$pasp_ovd = _txt($v['pasp_ovd']);
	$pasp_data = _txt($v['pasp_data']);

	if(empty($fio))
		jsonError();

	$sql = "INSERT INTO `_client_person` (
				`id`,
				`client_id`,
				`fio`,
				`phone`,
				`adres`,
				`post`,
				`pasp_seria`,
				`pasp_nomer`,
				`pasp_adres`,
				`pasp_ovd`,
				`pasp_data`
			) VALUES (
				".$person_id.",
				".$client_id.",
				'".addslashes($fio)."',
				'".addslashes($phone)."',
				'".addslashes($adres)."',
				'".addslashes($post)."',
				'".addslashes($pasp_seria)."',
				'".addslashes($pasp_nomer)."',
				'".addslashes($pasp_adres)."',
				'".addslashes($pasp_ovd)."',
				'".addslashes($pasp_data)."'
			) ON DUPLICATE KEY UPDATE
				`fio`=VALUES(`fio`),
				`phone`=VALUES(`phone`),
				`adres`=VALUES(`adres`),
				`post`=VALUES(`post`),
				`pasp_seria`=VALUES(`pasp_seria`),
				`pasp_nomer`=VALUES(`pasp_nomer`),
				`pasp_adres`=VALUES(`pasp_adres`),
				`pasp_ovd`=VALUES(`pasp_ovd`),
				`pasp_data`=VALUES(`pasp_data`)";
	query($sql, GLOBAL_MYSQL_CONNECT);

	if(!$person_id)
		_history(array(
			'type_id' => 5,
			'client_id' => $client_id,
			'v1' => $fio
		));
	else {
		$changes =
			_historyChange('ФИО', $r['fio'], $fio).
			_historyChange('Телефон', $r['phone'], $phone).
			_historyChange('Адрес', $r['adres'], $adres).
			_historyChange('Должность', $r['post'], $post).
			_historyChange('Паспорт серия', $r['pasp_seria'], $pasp_seria).
			_historyChange('Паспорт номер', $r['pasp_nomer'], $pasp_nomer).
			_historyChange('Паспорт прописка', $r['pasp_adres'], $pasp_adres).
			_historyChange('Паспорт кем выдан', $r['pasp_ovd'], $pasp_ovd).
			_historyChange('Паспорт когда выдан', $r['pasp_data'], $pasp_data);
		if($changes)
			_history(array(
				'type_id' => 6,
				'client_id' => $client_id,
				'v1' => $fio,
				'v2' => '<table>'.$changes.'</table>'
			));
	}

	$v['client_id'] = $client_id;

	return $v;
}//_clientPersonInsert()
