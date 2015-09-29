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

		$fio1 = _txt($_POST['fio1']);
		$fio2 = _txt($_POST['fio2']);
		$fio3 = _txt($_POST['fio3']);
		$telefon1 = _txt($_POST['telefon1']);
		$telefon2 = _txt($_POST['telefon2']);
		$telefon3 = _txt($_POST['telefon3']);
		$post1 = _txt($_POST['post1']);
		$post2 = _txt($_POST['post2']);
		$post3 = _txt($_POST['post3']);
		$org_name = _txt($_POST['org_name']);
		$org_telefon = _txt($_POST['org_telefon']);
		$org_fax = _txt($_POST['org_fax']);
		$org_adres = _txt($_POST['org_adres']);
		$org_inn = _txt($_POST['org_inn']);
		$org_kpp = _txt($_POST['org_kpp']);
		$info_dop = _txt($_POST['info_dop']);

		if($category_id == 1 && empty($fio1))//Для частного лица обязательно указывается ФИО
			jsonError();
		if($category_id > 1 && empty($org_name))//Для ИП и ООО обязательно указывается Название организации
			jsonError();

		$sql = "INSERT INTO `client` (
					`ws_id`,
					`category_id`,
					`org_name`,
					`org_telefon`,
					`org_fax`,
					`org_adres`,
					`org_inn`,
					`org_kpp`,
					`info_dop`,
					`fio1`,
					`fio2`,
					`fio3`,
					`telefon1`,
					`telefon2`,
					`telefon3`,
					`post1`,
					`post2`,
					`post3`,
					`viewer_id_add`
				) VALUES (
					".WS_ID.",
					".$category_id.",
					'".addslashes($org_name)."',
					'".addslashes($org_telefon)."',
					'".addslashes($org_fax)."',
					'".addslashes($org_adres)."',
					'".addslashes($org_inn)."',
					'".addslashes($org_kpp)."',
					'".addslashes($info_dop)."',
					'".addslashes($fio1)."',
					'".addslashes($fio2)."',
					'".addslashes($fio3)."',
					'".addslashes($telefon1)."',
					'".addslashes($telefon2)."',
					'".addslashes($telefon3)."',
					'".addslashes($post1)."',
					'".addslashes($post2)."',
					'".addslashes($post3)."',
					".VIEWER_ID."
				)";
		query($sql);

		$name = $category_id == 1 ? $fio1 : $org_name;
		$telefon = $category_id == 1 ? $telefon1 : $org_telefon;
		$send = array(
			'uid' => mysql_insert_id(),
			'title' => utf8($name),
			'content' => utf8($name.'<span>'.$telefon.'</span>')
		);
		history_insert(array(
			'type' => 3,
			'client_id' => $send['uid']
		));
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
		if(!$category_id = _num($_POST['category_id']))
			jsonError();

		define('ORG', $category_id > 1);

		$fio1 = _txt($_POST['fio1']);
		$fio2 = ORG ? _txt($_POST['fio2']) : '';
		$fio3 = ORG ? _txt($_POST['fio3']) : '';
		$telefon1 = _txt($_POST['telefon1']);
		$telefon2 = ORG ? _txt($_POST['telefon2']) : '';
		$telefon3 = ORG ? _txt($_POST['telefon3']) : '';
		$post1 = ORG ? _txt($_POST['post1']) : '';
		$post2 = ORG ? _txt($_POST['post2']) : '';
		$post3 = ORG ? _txt($_POST['post3']) : '';
		$org_name = ORG ? _txt($_POST['org_name']) : '';
		$org_telefon = ORG ? _txt($_POST['org_telefon']) : '';
		$org_fax = ORG ? _txt($_POST['org_fax']) : '';
		$org_adres = ORG ? _txt($_POST['org_adres']) : '';
		$org_inn = ORG ? _txt($_POST['org_inn']) : '';
		$org_kpp = ORG ? _txt($_POST['org_kpp']) : '';
		$info_dop = _txt($_POST['info_dop']);
		$join = _bool($_POST['join']);
		$client2 = _num($_POST['client2']);

		if(!ORG && empty($fio1))//Для частного лица обязательно указывается ФИО
			jsonError();
		if(ORG && empty($org_name))//Для ИП и ООО обязательно указывается Название организации
			jsonError();

		$sql = "SELECT * FROM `client` WHERE `ws_id`=".WS_ID." AND !`deleted` AND `id`=".$client_id;
		if(!$client = query_assoc($sql))
			jsonError();

		if($join) {
			if(!$client2)
				jsonError();
			if(!query_value("SELECT * FROM `client` WHERE `ws_id`=".WS_ID." AND !`deleted` AND `id`=".$client2))
				jsonError();
			if($client_id == $client2)
				jsonError();
		}

		$sql = "UPDATE `client`
				SET `category_id`=".$category_id.",
					`org_name`='".addslashes($org_name)."',
					`org_telefon`='".addslashes($org_telefon)."',
					`org_fax`='".addslashes($org_fax)."',
					`org_adres`='".addslashes($org_adres)."',
					`org_inn`='".addslashes($org_inn)."',
					`org_kpp`='".addslashes($org_kpp)."',
					`info_dop`='".addslashes($info_dop)."',
					`fio1`='".addslashes($fio1)."',
					`fio2`='".addslashes($fio2)."',
					`fio3`='".addslashes($fio3)."',
					`telefon1`='".addslashes($telefon1)."',
					`telefon2`='".addslashes($telefon2)."',
					`telefon3`='".addslashes($telefon3)."',
					`post1`='".addslashes($post1)."',
					`post2`='".addslashes($post2)."',
					`post3`='".addslashes($post3)."'
			   WHERE `id`=".$client_id;
		query($sql);

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

		jsonSuccess();
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