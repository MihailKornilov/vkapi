<?php
switch(@$_POST['op']) {
	case 'client_sel':
		$send['spisok'] = array();
		if(!empty($_POST['val']) && !preg_match(REGEXP_WORDFIND, win1251($_POST['val'])))
			jsonSuccess($send);

		$val = win1251($_POST['val']);
		$client_id = _num($_POST['client_id']);

		$sql = "SELECT *
				FROM `client`
				WHERE `ws_id`=".WS_ID."
				  AND !`deleted`".
			(!empty($val) ?
				" AND (`org_name` LIKE '%".$val."%'
							OR `org_telefon` LIKE '%".$val."%'
							OR `org_adres` LIKE '%".$val."%'
							OR `org_inn` LIKE '%".$val."%'
							OR `org_kpp` LIKE '%".$val."%'
							OR `fio1` LIKE '%".$val."%'
							OR `fio2` LIKE '%".$val."%'
							OR `fio3` LIKE '%".$val."%'
							OR `telefon1` LIKE '%".$val."%'
							OR `telefon2` LIKE '%".$val."%'
							OR `telefon3` LIKE '%".$val."%'
							  )"
				: '').
			($client_id > 0 ? " AND `id`<=".$client_id : '')."
				ORDER BY `id` DESC
				LIMIT 50";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q)) {
			$name = _clientName($r);
			$telefon = _clientTelefon($r);
			$unit = array(
				'uid' => $r['id'],
				'title' => utf8(htmlspecialchars_decode($name))
			);
			if($telefon)
				$unit['content'] = utf8($name.'<span>'.$telefon.'</span>');
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
		$info_dop = _txt($_POST['info_dop']);

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
					`info_dop`,
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
					'".addslashes($info_dop)."',
					".VIEWER_ID."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$sql = "SELECT `id` FROM `_client` WHERE `app_id`=".APP_ID." ORDER BY `id` DESC LIMIT 1";
		$insert_id = query_value($sql, GLOBAL_MYSQL_CONNECT);

		$person_insert = array();
		foreach($_POST['person'] as $r) {
			if(empty($r['fio']))
				continue;
			$person_insert[] = "(" .
				$insert_id.','.
				"'".addslashes(_txt($r['fio']))."'," .
				"'".addslashes(_txt($r['phone']))."',".
				"'".addslashes(_txt($r['post']))."'".
			")";
		}

		$sql = "INSERT INTO `_client_person` (
					`client_id`,
					`fio`,
					`phone`,
					`post`
				) VALUES ".implode(',', $person_insert);
		query($sql, GLOBAL_MYSQL_CONNECT);

		_clientFindUpdate($insert_id);

//		$name = $category_id == 1 ? $fio : $org_name;
//		$telefon = $category_id == 1 ? $telefon1 : $org_telefon;
		$send = array(
			'uid' => $insert_id
//			'title' => utf8($name),
//			'content' => utf8($name.'<span>'.$telefon.'</span>')
		);
/*		history_insert(array(
			'type' => 3,
			'client_id' => $send['uid']
		));
*/		jsonSuccess($send);
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

		$sql = "SELECT * FROM `_client` WHERE `ws_id`=".WS_ID." AND !`deleted` AND `id`=".$client_id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		define('ORG', $r['category_id'] > 1);

		$org_name = ORG ? _txt($_POST['org_name']) : '';
		$org_phone = ORG ? _txt($_POST['org_phone']) : '';
		$org_fax = ORG ? _txt($_POST['org_fax']) : '';
		$org_adres = ORG ? _txt($_POST['org_adres']) : '';
		$org_inn = ORG ? _txt($_POST['org_inn']) : '';
		$org_kpp = ORG ? _txt($_POST['org_kpp']) : '';
		$info_dop = _txt($_POST['info_dop']);
//		$join = _bool($_POST['join']);
//		$client2 = _num($_POST['client2']);

		if(!ORG) { // Для частного лица обязательно указывается ФИО
			if(!$person_id = _num($_POST['person_id']))
				jsonError();

			$fio = _txt($_POST['fio']);
			$phone = _txt($_POST['phone']);
			if(empty($fio))
				jsonError();

			$sql = "SELECT * FROM `_client_person` WHERE `client_id`=".$client_id." AND `id`=".$person_id;
			if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
				jsonError();

			$sql = "UPDATE `_client_person`
				SET `fio`='".addslashes($fio)."',
					`phone`='".addslashes($phone)."'
			   WHERE `id`=".$person_id;
			query($sql, GLOBAL_MYSQL_CONNECT);
		} else
			if(empty($org_name))//Для организации обязательно указывается Название организации
				jsonError();
/*
		if($join) {
			if(!$client2)
				jsonError();
			if(!query_value("SELECT * FROM `client` WHERE `ws_id`=".WS_ID." AND !`deleted` AND `id`=".$client2))
				jsonError();
			if($client_id == $client2)
				jsonError();
		}
*/
		$sql = "UPDATE `_client`
				SET `org_name`='".addslashes($org_name)."',
					`org_phone`='".addslashes($org_phone)."',
					`org_fax`='".addslashes($org_fax)."',
					`org_adres`='".addslashes($org_adres)."',
					`org_inn`='".addslashes($org_inn)."',
					`org_kpp`='".addslashes($org_kpp)."',
					`info_dop`='".addslashes($info_dop)."'
			   WHERE `id`=".$client_id;
		query($sql, GLOBAL_MYSQL_CONNECT);
/*
		if($join) {
			query("UPDATE `accrual`	SET `client_id`=".$client_id." WHERE `client_id`=".$client2);
			query("UPDATE `money`	SET `client_id`=".$client_id." WHERE `client_id`=".$client2);
			query("UPDATE `vk_comment` SET `table_id`=".$client_id."  WHERE `table_name`='client' AND `table_id`=".$client2);
			query("UPDATE `zayav`	SET `client_id`=".$client_id." WHERE `client_id`=".$client2);
			query("UPDATE `zp_move`	SET `client_id`=".$client_id." WHERE `client_id`=".$client2);
			query("UPDATE `client`  SET `deleted`=1,`join_id`=".$client_id." WHERE `id`=".$client2);
			clientBalansUpdate($client_id);
			history_insert(array(
				'type' => 11,
				'client_id' => $client_id,
				'value' => _clientLink($client2, 1)
			));
		}
*/
		_clientFindUpdate($client_id);
/*
		$changes = '';
		if($client['category_id'] != $category_id)
			$changes .= '<tr><th>Категория:<td>'._clientCategory($client['category_id']).'<td>»<td>'._clientCategory($category_id);
		if($client['org_name'] != $org_name)
			$changes .= '<tr><th>Название организации:<td>'.$client['org_name'].'<td>»<td>'.$org_name;
		if($client['org_telefon'] != $org_telefon)
			$changes .= '<tr><th>Телефон:<td>'.$client['org_telefon'].'<td>»<td>'.$org_telefon;
		if($client['org_fax'] != $org_fax)
			$changes .= '<tr><th>Факс:<td>'.$client['org_fax'].'<td>»<td>'.$org_fax;
		if($client['org_adres'] != $org_adres)
			$changes .= '<tr><th>Адрес:<td>'.$client['org_adres'].'<td>»<td>'.$org_adres;
		if($client['org_inn'] != $org_inn)
			$changes .= '<tr><th>ИНН:<td>'.$client['org_inn'].'<td>»<td>'.$org_inn;
		if($client['org_kpp'] != $org_kpp)
			$changes .= '<tr><th>КПП:<td>'.$client['org_kpp'].'<td>»<td>'.$org_kpp;
		if($client['info_dop'] != $info_dop)
			$changes .= '<tr><th>Дополнительнo:<td>'.nl2br($client['info_dop']).'<td>»<td>'.nl2br($info_dop);
		if($changes)
			history_insert(array(
				'type' => 10,
				'client_id' => $client_id,
				'value' => '<table>'.$changes.'</table>'
			));
*/
		jsonSuccess();
		break;
	case 'client_person_add':
		if(!$client_id = _num($_POST['client_id']))
			jsonError();

		$sql = "SELECT * FROM `_client` WHERE `ws_id`=".WS_ID." AND !`deleted` AND `id`=".$client_id;
		if(!$c = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$fio = _txt($_POST['fio']);
		$phone = _txt($_POST['phone']);
		$post = _txt($_POST['post']);

		if(empty($fio))
			jsonError();

		$sql = "INSERT INTO `_client_person` (
					`client_id`,
					`fio`,
					`phone`,
					`post`
				) VALUES (
					".$client_id.",
					'".addslashes($fio)."',
					'".addslashes($phone)."',
					'".addslashes($post)."'
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		_clientFindUpdate($client_id);

		$send['html'] = utf8(_clientInfoPerson($client_id, $c['category_id']));
		jsonSuccess($send);
		break;
	case 'client_person_del':
		if(!$person_id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT * FROM `_client_person` WHERE `id`=".$person_id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "SELECT * FROM `_client` WHERE `ws_id`=".WS_ID." AND !`deleted` AND `id`=".$r['client_id'];
		if(!$c = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "DELETE FROM `_client_person` WHERE `id`=".$person_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		_clientFindUpdate($r['client_id']);

		$send['html'] = utf8(_clientInfoPerson($r['client_id'], $c['category_id']));
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
	$sql = "SELECT * FROM `_client` WHERE `id`=".$client_id;
	if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
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
		$person[] = $r['fio'].' '.$r['phone'];

	$find .= implode(' ', $person);

	$sql = "UPDATE `_client` SET `find`='".addslashes($find)."' WHERE `id`=".$client_id;
	query($sql, GLOBAL_MYSQL_CONNECT);
}//_clientFindUpdate()