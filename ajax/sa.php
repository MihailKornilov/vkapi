<?php
if(!SA)
	jsonError();

switch(@$_POST['op']) {
	case 'sa_menu_add':
		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);
		$p = _txt($_POST['p']);

		if(!$name)
			jsonError();

		$sql = "INSERT INTO `_menu` (
					`name`,
					`about`,
					`p`
				) VALUES (
					'".addslashes($name)."',
					'".addslashes($about)."',
					'".strtolower(addslashes($p))."'
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'menu');
		xcache_unset(CACHE_PREFIX.'menu_app');
		xcache_unset(CACHE_PREFIX.'menu_sort');

		_menuCache();

		$send['html'] = utf8(sa_menu_spisok());
		jsonSuccess($send);
		break;
	case 'sa_menu_edit'://�������������� ������� �������� ����
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);
		$p = _txt($_POST['p']);

		if(!$name || !$p)
			jsonError();

		$sql = "SELECT *
				FROM `_menu`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_menu`
				SET `name`='".addslashes($name)."',
					`about`='".addslashes($about)."',
					`p`='".addslashes($p)."'
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'menu');
		xcache_unset(CACHE_PREFIX.'menu_app');
		xcache_unset(CACHE_PREFIX.'menu_sort');

		_menuCache();

		$send['html'] = utf8(sa_menu_spisok());
		jsonSuccess($send);
		break;
	case 'sa_menu_show':
		if(!$id = _num($_POST['id']))
			jsonError();

		$v = _bool($_POST['v']);

		$sql = "UPDATE `_menu_app`
				SET `show`=".$v."
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'menu');
		xcache_unset(CACHE_PREFIX.'menu_app');
		xcache_unset(CACHE_PREFIX.'menu_sort');

		_menuCache();
		jsonSuccess();
		break;

	case 'sa_history_type_add':
		if(!empty($_POST['type_id']) && !_num($_POST['type_id']))
			jsonError();

		$type_id = _num($_POST['type_id']);
		$txt = win1251(trim($_POST['txt']));
		if(($ids = _ids($_POST['category_ids'], 1)) == false && $_POST['category_ids'] != 0)
			jsonError();

		if($type_id) {
			$sql = "SELECT COUNT(`id`) FROM `_history_type` WHERE `id`=".$type_id;
			if(query_value($sql, GLOBAL_MYSQL_CONNECT))
				jsonError();
		}

		if(!$txt)
			jsonError();

		$sql = "INSERT INTO `_history_type` (
					`id`,
					`txt`
				) VALUES (
					".$type_id.",
					'".addslashes($txt)."'
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		if(!$type_id)
			$type_id = query_insert_id('_history_type', GLOBAL_MYSQL_CONNECT);


		sa_history_ids_insert($type_id, $ids);

		$send['html'] = utf8(sa_history_spisok());
		jsonSuccess($send);
		break;
	case 'sa_history_type_edit':
		if(!$type_id_current = _num($_POST['type_id_current']))
			jsonError();
		if(!$type_id = _num($_POST['type_id']))
			jsonError();
		if(($ids = _ids($_POST['category_ids'], 1)) == false && $_POST['category_ids'] != 0)
			jsonError();

		$txt = win1251(trim($_POST['txt']));

		if(!$txt)
			jsonError();

		if($type_id_current != $type_id) {
			$sql = "SELECT COUNT(`id`) FROM `_history_type` WHERE `id`=".$type_id;
			if(query_value($sql, GLOBAL_MYSQL_CONNECT))
				jsonError();
			$sql = "UPDATE `_history_type`
					SET `id`=".$type_id."
					WHERE `id`=".$type_id_current;
			query($sql, GLOBAL_MYSQL_CONNECT);

			//��������� �� ����� type_id ������� ������� ��������
			$sql = "UPDATE `_history`
					SET `type_id`=".$type_id."
					WHERE `type_id`=".$type_id_current;
			query($sql, GLOBAL_MYSQL_CONNECT);

			//��������� �� ����� type_id ids-������� ��������
			$sql = "UPDATE `_history_ids`
					SET `type_id`=".$type_id."
					WHERE `type_id`=".$type_id_current;
			query($sql, GLOBAL_MYSQL_CONNECT);
		}

		$sql = "UPDATE `_history_type`
				SET `txt`='".addslashes($txt)."'
				WHERE `id`=".$type_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		$sql = "DELETE FROM `_history_ids` WHERE `type_id`=".$type_id;
		query($sql, GLOBAL_MYSQL_CONNECT);
		sa_history_ids_insert($type_id, $ids);


		$send['html'] = utf8(sa_history_spisok());
		jsonSuccess($send);
		break;

	case 'sa_history_cat_add':
		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);
		$js_use = _bool($_POST['js_use']);

		if(!$name)
			jsonError();

		$sql = "INSERT INTO `_history_category` (
					`name`,
					`about`,
					`js_use`,
					`sort`
				) VALUES (
					'".addslashes($name)."',
					'".addslashes($about)."',
					".$js_use.",
					"._maxSql('_history_category')."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$send['html'] = utf8(sa_history_cat_spisok());
		jsonSuccess($send);
		break;
	case 'sa_history_cat_edit':
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);
		$js_use = _bool($_POST['js_use']);

		if(!$name)
			jsonError();

		$sql = "SELECT COUNT(`id`) FROM `_history_category` WHERE `id`=".$id;
		if(!query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_history_category`
				SET `name`='".addslashes($name)."',
					`about`='".addslashes($about)."',
					`js_use`=".$js_use."
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		$send['html'] = utf8(sa_history_cat_spisok());
		jsonSuccess($send);
		break;

	case 'sa_rule_add':
		$key = _txt($_POST['key']);
		$about = _txt($_POST['about']);

		if(!$key)
			jsonError();

		$sql = "SELECT COUNT(`id`) FROM `_vkuser_rule_default` WHERE `key`='".$key."'";
		if(query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError('��������� ��� ������� � ����');

		$sql = "INSERT INTO `_vkuser_rule_default` (
					`key`,
					`about`
				) VALUES (
					'".strtoupper(addslashes($key))."',
					'".addslashes($about)."'
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);


		xcache_unset(CACHE_PREFIX.'viewer_rule_default_admin');
		xcache_unset(CACHE_PREFIX.'viewer_rule_default_worker');

		$send['html'] = utf8(sa_rule_spisok());
		jsonSuccess($send);
		break;
	case 'sa_rule_edit':
		if(!$id = _num($_POST['id']))
			jsonError();

		$key = _txt($_POST['key']);
		$about = _txt($_POST['about']);

		if(!$key)
			jsonError();

		$sql = "SELECT COUNT(`id`)
				FROM `_vkuser_rule_default`
				WHERE `key`='".$key."'
				  AND `id`!=".$id;
		if(query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError('��������� ��� ������� � ����');

		$sql = "UPDATE `_vkuser_rule_default`
		        SET `key`='".strtoupper(addslashes($key))."',
		            `about`='".addslashes($about)."'
		        WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'viewer_rule_default_admin');
		xcache_unset(CACHE_PREFIX.'viewer_rule_default_worker');

		$send['html'] = utf8(sa_rule_spisok());
		jsonSuccess($send);
		break;
	case 'sa_rule_flag'://��������� ��������� �� ��������� ����� ����������
		if(!$id = _num($_POST['id']))
			jsonError();

		$value_name = _txt($_POST['value_name']);
		$v = _num($_POST['v']);

		if($value_name != 'admin' && $value_name != 'worker')
			jsonError();

		$sql = "SELECT * FROM `_vkuser_rule_default` WHERE `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_vkuser_rule_default`
		        SET `value_".$value_name."`=".$v."
		        WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		//��������� �������� ������ ��������� �� ���� ���������������
		if($value_name == 'admin') {
			$sql = "SELECT `viewer_id`
					FROM `_vkuser`
					WHERE `admin`";
			$ids = query_ids($sql, GLOBAL_MYSQL_CONNECT);

			$sql = "DELETE FROM `_vkuser_rule`
					WHERE `key`='".$r['key']."'
					  AND `viewer_id` IN (".$ids.")";
			query($sql, GLOBAL_MYSQL_CONNECT);

			foreach(_ids($ids, 1) as $viewer_id) {
				xcache_unset(CACHE_PREFIX.'viewer_'.$viewer_id);
				xcache_unset(CACHE_PREFIX.'viewer_rule_'.$viewer_id);
			}
		}

		xcache_unset(CACHE_PREFIX.'viewer_rule_default_admin');
		xcache_unset(CACHE_PREFIX.'viewer_rule_default_worker');

		$send['v'] = $v;
		$send['html'] = utf8(sa_rule_spisok());
		jsonSuccess($send);
		break;

	case 'sa_balans_category_add':
		$name = _txt($_POST['name']);

		if(!$name)
			jsonError();

		$sql = "INSERT INTO `_balans_category` (
					`name`
				) VALUES (
					'".addslashes($name)."'
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'balans_category');

		$send['html'] = utf8(sa_balans_spisok());
		jsonSuccess($send);
		break;
	case 'sa_balans_category_edit':
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);

		if(!$name)
			jsonError();

		$sql = "SELECT COUNT(`id`)
				FROM `_balans_category`
				WHERE `id`=".$id;
		if(!query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_balans_category`
				SET `name`='".addslashes($name)."'
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'balans_category');

		$send['html'] = utf8(sa_balans_spisok());
		jsonSuccess($send);
		break;
	case 'sa_balans_category_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT COUNT(`id`)
				FROM `_balans_category`
				WHERE `id`=".$id;
		if(!query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "DELETE FROM `_balans_category` WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'balans_category');

		$send['html'] = utf8(sa_balans_spisok());
		jsonSuccess($send);
		break;
	case 'sa_balans_action_add':
		if(!$category_id = _num($_POST['category_id']))
			jsonError();
		if(!$name = _txt($_POST['name']))
			jsonError();

		$minus = _bool($_POST['minus']);

		$sql = "INSERT INTO `_balans_action` (
					`category_id`,
					`name`,
					`minus`
				) VALUES (
					".$category_id.",
					'".addslashes($name)."',
					".$minus."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'balans_action');

		$send['html'] = utf8(sa_balans_spisok());
		jsonSuccess($send);
		break;
	case 'sa_balans_action_edit':
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);
		$minus = _bool($_POST['minus']);

		if(!$name)
			jsonError();

		$sql = "SELECT COUNT(`id`)
				FROM `_balans_action`
				WHERE `id`=".$id;
		if(!query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_balans_action`
				SET `name`='".addslashes($name)."',
					`minus`=".$minus."
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'balans_action');

		$send['html'] = utf8(sa_balans_spisok());
		jsonSuccess($send);
		break;
	case 'sa_balans_action_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT COUNT(`id`)
				FROM `_balans_action`
				WHERE `id`=".$id;
		if(!query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "DELETE FROM `_balans_action` WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'balans_action');

		$send['html'] = utf8(sa_balans_spisok());
		jsonSuccess($send);
		break;

	case 'sa_zayav_pole_add':
		if(!$type_id = _num($_POST['type_id']))
			jsonError();
		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);
		$param1 = _txt($_POST['param1']);

		if(!$name)
			jsonError();

		$sql = "INSERT INTO `_zayav_pole` (
					`type_id`,
					`name`,
					`about`,
					`param1`
				) VALUES (
					".$type_id.",
					'".addslashes($name)."',
					'".addslashes($about)."',
					'".addslashes($param1)."'
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'service'.APP_ID);
		_globalJsValues();

		$send['html'] = utf8(sa_zayav_pole_spisok($type_id));
		jsonSuccess($send);
		break;
	case 'sa_zayav_pole_edit'://�������������� ���� ������
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);
		$param1 = _txt($_POST['param1']);

		if(!$name)
			jsonError();

		$sql = "SELECT * FROM `_zayav_pole` WHERE `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_zayav_pole`
				SET `name`='".addslashes($name)."',
					`about`='".addslashes($about)."',
					`param1`='".addslashes($param1)."'
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'service'.APP_ID);
		_globalJsValues();

		$send['html'] = utf8(sa_zayav_pole_spisok($r['type_id']));
		jsonSuccess($send);
		break;
	case 'sa_zayav_pole_del'://�������� ���� ������
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT * FROM `_zayav_pole` WHERE `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError('id ���� �� ����������');

		$sql = "SELECT COUNT(*) FROM `_zayav_pole_use` WHERE `pole_id`=".$id;
		if(query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError('��� ���� ������������');

		$sql = "DELETE FROM `_zayav_pole` WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		$sql = "ALTER TABLE `_zayav_pole` AUTO_INCREMENT=0";
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'service'.APP_ID);
		_globalJsValues();

		jsonSuccess();
		break;

	case 'sa_zayav_service_pole_load':
		$service_id = _num($_POST['service_id']);

		if(!$type_id = _num($_POST['type_id']))
			jsonError();
		
		$sql = "SELECT `pole_id`
				FROM `_zayav_pole_use`
				WHERE `app_id`=".APP_ID."
				  AND `service_id`=".$service_id;
		$ids = query_ids($sql, GLOBAL_MYSQL_CONNECT);
		
		$send['html'] = utf8('<div id="sa-zayav-pole">'.sa_zayav_pole_spisok($type_id, $ids).'</div>');
		jsonSuccess($send);
		break;
	case 'sa_zayav_service_pole_add'://���������� ���������� ���� ������
		if(!$pole_id = _num($_POST['pole_id']))
			jsonError();

		$service_id = _num($_POST['service_id']);
		$name_use = _txt($_POST['label']);
		$require = _bool($_POST['require']);
		$param_v1 = _bool($_POST['param_v1']);

		$sql = "SELECT * FROM `_zayav_pole` WHERE `id`=".$pole_id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "INSERT INTO `_zayav_pole_use` (
					`app_id`,
					`service_id`,
					`pole_id`,
					`label`,
					`require`,
					`param_v1`,
					`sort`
				) VALUES (
					".APP_ID.",
					".$service_id.",
					".$pole_id.",
					'".addslashes($name_use)."',
					".$require.",
					".$param_v1.",
					"._maxSql('_zayav_pole_use')."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);
		$insert_id = query_insert_id('_zayav_pole_use', GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'service'.APP_ID);
		_globalJsValues();

		define('SERVICE_ID', $service_id);
		$send['html'] = utf8(sa_zayav_service_use($r['type_id'], $insert_id));
		$send['type_id'] = $r['type_id'];
		jsonSuccess($send);
		break;
	case 'sa_zayav_service_pole_edit'://�������������� ���������� ���� ������
		if(!$id = _num($_POST['id']))
			jsonError();

		$name_use = _txt($_POST['label']);
		$require = _bool($_POST['require']);
		$param_v1 = _bool($_POST['param_v1']);

		$sql = "SELECT * FROM `_zayav_pole_use` WHERE `id`=".$id;
		if(!$u = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "SELECT * FROM `_zayav_pole` WHERE `id`=".$u['pole_id'];
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_zayav_pole_use`
				SET `label`='".addslashes($name_use)."',
					`require`=".$require.",
					`param_v1`=".$param_v1."
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'service'.APP_ID);
		_globalJsValues();

		define('SERVICE_ID', _num($u['service_id']));
		$send['html'] = utf8(sa_zayav_service_use($r['type_id'], $id));
		$send['type_id'] = $r['type_id'];
		jsonSuccess($send);
		break;
	case 'sa_zayav_service_pole_del'://�������� ���������� ���� ������
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT * FROM `_zayav_pole_use` WHERE `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "DELETE FROM `_zayav_pole_use` WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'service'.APP_ID);
		_globalJsValues();

		jsonSuccess();
		break;
	case 'sa_zayav_type_add':
		$name = _txt($_POST['name']);

		if(!$name)
			jsonError();

		$sql = "INSERT INTO `_zayav_service` (
					`app_id`,
					`name`,
					`head`
				) VALUES (
					".APP_ID.",
					'".addslashes($name)."',
					'".addslashes($name)."'
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$type_id = query_insert_id('_zayav_type', GLOBAL_MYSQL_CONNECT);

		$sql = "SELECT COUNT(*)
				FROM `_zayav_service`
				WHERE `app_id`=".APP_ID;
		if(query_value($sql, GLOBAL_MYSQL_CONNECT) == 1) {
			$sql = "UPDATE `_zayav`
					SET `service_id`=".$type_id."
					WHERE `app_id`=".APP_ID;
			query($sql, GLOBAL_MYSQL_CONNECT);

			//���������� ���� ������ � ������������ �����
			$sql = "UPDATE `_zayav_pole_use`
					SET `service_id`=".$type_id."
					WHERE `app_id`=".APP_ID;
			query($sql, GLOBAL_MYSQL_CONNECT);
		}

		xcache_unset(CACHE_PREFIX.'service'.APP_ID);
		_globalJsValues();

		jsonSuccess();
		break;

	case 'sa_color_add':
		$predlog = _txt($_POST['predlog']);
		$name = _txt($_POST['name']);

		if(empty($predlog))
			jsonError();
		if(empty($name))
			jsonError();

		$sql = "INSERT INTO `_setup_color` (
					`predlog`,
					`name`
				) VALUES (
					'".addslashes($predlog)."',
					'".addslashes($name)."'
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'setup_color');
		_appJsValues();

		$send['html'] = utf8(sa_color_spisok());
		jsonSuccess($send);
		break;
	case 'sa_color_edit':
		if(!$id = _num($_POST['id']))
			jsonError();

		$predlog = _txt($_POST['predlog']);
		$name = _txt($_POST['name']);

		if(empty($predlog))
			jsonError();
		if(empty($name))
			jsonError();

		$sql = "UPDATE `_setup_color`
				SET `predlog`='".addslashes($predlog)."',
					`name`='".addslashes($name)."'
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'setup_color');
		_appJsValues();

		$send['html'] = utf8(sa_color_spisok());
		jsonSuccess($send);
		break;
	case 'sa_color_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT * FROM `_setup_color` WHERE `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "SELECT COUNT(`id`) FROM `_zayav` WHERE `color_id`=".$id;
		if(query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "SELECT COUNT(`id`) FROM `_zayav` WHERE `color_dop`=".$id;
		if(query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "DELETE FROM `_setup_color` WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'setup_color');
		_appJsValues();

		$send['html'] = utf8(sa_color_spisok());
		jsonSuccess($send);
		break;

	case 'sa_count_client_load':
		$sql = "UPDATE `_client` `c`
				SET `balans_test`=(
					SELECT IFNULL(SUM(`sum`),0)
					FROM `_money_income`
					WHERE !`tovar_id`
					  AND `confirm` NOT IN (1,3)
					  AND !`deleted`
					  AND `client_id`=`c`.`id`
				) - (
					SELECT IFNULL(SUM(`sum`),0)
					FROM `_money_accrual`
					WHERE !`deleted`
					  AND `client_id`=`c`.`id`
				) - (
					SELECT IFNULL(SUM(`sum`),0)
					FROM `_money_refund`
					WHERE !`deleted`
					  AND `client_id`=`c`.`id`
				) WHERE `app_id`=".APP_ID;
		query($sql, GLOBAL_MYSQL_CONNECT);

		$sql = "SELECT *
				FROM `_client`
				WHERE `app_id`=".APP_ID."
				  AND `balans`!=`balans_test`";
		$client = query_arr($sql, GLOBAL_MYSQL_CONNECT);

		$spisok = '';
		foreach($client as $r)
			$spisok .= '<a href="'.URL.'&p=client&d=info&id='.$r['id'].'"><b>'.$r['id'].'</b></a> '.
					   '<a class="client-balans-repair" val="'.$r['id'].'">���������</a>'.
					   '<br />';

		$send['html'] = utf8(
			'<div>app: '.APP_ID.' - '._app('name').'</div>'.
			'<div>��������: '.count($client).'</div>'.
			'<br />'.
			$spisok
		);
		jsonSuccess($send);
		break;
	case 'sa_count_client_balans_repair':
		if(!$client_id = _num($_POST['client_id']))
			jsonError();

		_clientBalansUpdate($client_id);

		_balans(array(
			'action_id' => 52,
			'client_id' => $client_id
		));

		jsonSuccess();
		break;

	case 'sa_count_zayav_load':
		$sql = "UPDATE `_zayav` `z`
				SET `sum_dolg_test`=(
					SELECT IFNULL(SUM(`sum`),0)
					FROM `_money_income`
					WHERE `confirm` NOT IN (1,3)
					  AND !`deleted`
					  AND `zayav_id`=`z`.`id`
				) - (
					SELECT IFNULL(SUM(`sum`),0)
					FROM `_money_accrual`
					WHERE !`deleted`
					  AND `zayav_id`=`z`.`id`
				) - (
					SELECT IFNULL(SUM(`sum`),0)
					FROM `_money_refund`
					WHERE !`deleted`
					  AND `zayav_id`=`z`.`id`
				) WHERE `app_id`=".APP_ID;
		query($sql, GLOBAL_MYSQL_CONNECT);

		$sql = "SELECT *
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND `sum_dolg`!=`sum_dolg_test`";
		$zayav = query_arr($sql, GLOBAL_MYSQL_CONNECT);

		$spisok = '';
		foreach($zayav as $r)
			$spisok .= '<a href="'.URL.'&p=zayav&d=info&id='.$r['id'].'">������ <b>#'.$r['id'].'</b></a> '.
					   '<a class="zayav-balans-repair" val="'.$r['id'].'">���������</a>'.
					   '<br />';

		$send['html'] = utf8(
			'<div>app: '.APP_ID.' - '._app('name').'</div>'.
			'<div>��������: '.count($zayav).'</div>'.
			'<br />'.
			$spisok
		);
		jsonSuccess($send);
		break;
	case 'sa_count_zayav_balans_repair':
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();

		_zayavBalansUpdate($zayav_id);

		jsonSuccess();
		break;

	case 'sa_count_tovar_set_find_update':
		$start = _num(@$_POST['start']);
		
		$sql = "SELECT DISTINCT(`tovar_id_set`)
				FROM `_tovar`
				WHERE `tovar_id_set`";
		$ids = query_ids($sql, GLOBAL_MYSQL_CONNECT);

		$sql = "SELECT *
				FROM `_tovar`
				WHERE `id` IN (".$ids.")";
		$tovar = query_arr($sql, GLOBAL_MYSQL_CONNECT);

		$sql = "SELECT *
				FROM `_tovar`
				WHERE `tovar_id_set`
				LIMIT ".$start.",500";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		$count = mysql_num_rows($q);
		while($r = mysql_fetch_assoc($q)) {
			$t = $tovar[$r['tovar_id_set']];

			$find =
				_tovarName($r['name_id']).
				' '.
				_tovarName($t['name_id']).
				_tovarVendor($t['vendor_id']).
				$t['name'];

			$sql = "UPDATE `_tovar`
					SET `find`='".addslashes($find)."'
					WHERE `id`=".$r['id'];
			query($sql, GLOBAL_MYSQL_CONNECT);
		}

		if($count < 500)
			$start += $count;
		else
			$start += 500;

		$send['start'] = $start;
		jsonSuccess($send);
		break;
	case 'sa_count_tovar_articul_update':
		$sql = "SELECT DISTINCT `app_id` FROM `_tovar_avai`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		while($r = mysql_fetch_assoc($q)) {
			$sql = "SELECT COUNT(`id`)
					FROM `_tovar_avai`
					WHERE !LENGTH(`articul`)
					  AND `app_id`=".$r['app_id'];
			if(!$count = query_value($sql, GLOBAL_MYSQL_CONNECT))
				continue;

			//��������� ������������� �������� ��������
			$sql = "SELECT MAX(`articul`)
					FROM `_tovar_avai`
					WHERE `app_id`=".$r['app_id'];
			$max = _num(query_value($sql, GLOBAL_MYSQL_CONNECT)) + 1;

			for($n = 0; $n < $count; $n ++) {
				$articul = $max;
				for($i = 0; $i < 6 - strlen($max); $i++)
					$articul = '0'.$articul;
				$sql = "UPDATE `_tovar_avai`
						SET `articul`='".$articul."'
						WHERE `app_id`=".$r['app_id']."
						  AND !LENGTH(`articul`)
						ORDER BY `id` ASC
						LIMIT 1";
				query($sql, GLOBAL_MYSQL_CONNECT);

				$max++;
			}
		}

		jsonSuccess();
		break;

	case 'sa_app_add':
		if(!$id = _num($_POST['id']))
			jsonError();

		$title = _txt($_POST['title']);
		$app_name = _txt($_POST['app_name']);
		$secret = _txt($_POST['secret']);

		if(empty($title))
			jsonError();
		if(empty($app_name))
			jsonError();

		$sql = "INSERT INTO `_app` (
					`id`,
					`title`,
					`app_name`,
					`secret`,
					`viewer_id_add`
				) VALUES (
					".$id.",
					'".addslashes($title)."',
					'".addslashes($app_name)."',
					'".addslashes($secret)."',
					".VIEWER_ID."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$send['html'] = utf8(sa_app_spisok());
		jsonSuccess($send);
		break;
	case 'sa_app_edit':
		if(!$id = _num($_POST['id']))
			jsonError();

		$title = _txt($_POST['title']);
		$app_name = _txt($_POST['app_name']);
		$secret = _txt($_POST['secret']);

		if(empty($title))
			jsonError();
		if(empty($app_name))
			jsonError();

		$sql = "UPDATE `_app`
				SET `title`='".addslashes($title)."',
					`app_name`='".addslashes($app_name)."',
					`secret`='".addslashes($secret)."'
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'app'.$id);

		$send['html'] = utf8(sa_app_spisok());
		jsonSuccess($send);
		break;


	/*
	case 'sa_user_action':
		if(!$viewer_id = _num($_POST['viewer_id']))
			jsonError();

		$sql = "SHOW TABLES";
		$q = query($sql);
		$tab = '';
		while($r = mysql_fetch_row($q)) {
			$count = '';
			if(!$count = sa_user_tab_test($r[0], 'viewer_id_add', $viewer_id))
				if(!$count = sa_user_tab_test($r[0], 'viewer_id', $viewer_id))
					if(!$count = sa_user_tab_test($r[0], 'admin_id', $viewer_id))
						continue;
			$tab .= '<tr><td>'.$r[0].'<td class="c">'.$count;
		}

		$send['html'] = '<table class="action-res">'.$tab.'</table>';
		jsonSuccess($send);
		break;

	case 'sa_ws_status_change':
		if(!$ws_id = _num($_POST['ws_id']))
			jsonError('�������� id');
		$sql = "SELECT * FROM `workshop` WHERE `id`=".$ws_id;
		if(!$ws = mysql_fetch_assoc(query($sql)))
			jsonError('����������� �� ����������');
		if($ws['status']) {
			query("UPDATE `workshop` SET `status`=0,`dtime_del`=CURRENT_TIMESTAMP WHERE `id`=".$ws_id);
			query("UPDATE `vk_user` SET `ws_id`=0,`admin`=0 WHERE `ws_id`=".$ws_id);
		} else {
			if(query_value("SELECT `ws_id` FROM `vk_user` WHERE `viewer_id`=".$ws['admin_id']))
				jsonError('�� ��������������� ���������� ������ �����������');
			query("UPDATE `workshop` SET `status`=1,`dtime_del`='0000-00-00 00:00:00' WHERE `id`=".$ws_id);
			query("UPDATE `vk_user` SET `ws_id`=".$ws_id.",`admin`=1 WHERE `viewer_id`=".$ws['admin_id']);
			xcache_unset(CACHE_PREFIX.'viewer_'.$ws['admin_id']);
		}
		jsonSuccess();
		break;
	case 'sa_ws_del':
		if(!$ws_id = _num($_POST['ws_id']))
			jsonError();
		foreach(sa_ws_tables() as $tab => $about)
			query("DELETE FROM `".$tab."` WHERE `ws_id`=".$ws_id);
		query("DELETE FROM `workshop` WHERE `id`=".$ws_id);
		query("UPDATE `vk_user` SET `ws_id`=0,`admin`=0 WHERE `ws_id`=".$ws_id);
		jsonSuccess();
		break;
	case 'sa_ws_client_balans'://������������� �������� ��������
		if(!$ws_id = _num($_POST['ws_id']))
			jsonError();

		$sql = "SELECT
					`id`,
					`balans`,
					0 `balans_new`
				FROM `_client`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".$ws_id."
				  AND !`deleted`";
		$client = query_arr($sql, GLOBAL_MYSQL_CONNECT);

		//����������
		$sql = "SELECT
					`client_id`,
					IFNULL(SUM(`sum`),0) `sum`
				FROM `_money_accrual`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".$ws_id."
				  AND `client_id`
				  AND !`deleted`
				GROUP BY `client_id`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		while($r = mysql_fetch_assoc($q))
			$client[$r['client_id']]['balans_new'] -= $r['sum'];

		//�������
		$sql = "SELECT
					`client_id`,
					IFNULL(SUM(`sum`),0) `sum`
				FROM `_money_income`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".$ws_id."
				  AND `client_id`
				  AND !`zp_id`
				  AND !`deleted`
				GROUP BY `client_id`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		while($r = mysql_fetch_assoc($q))
			$client[$r['client_id']]['balans_new'] += $r['sum'];

		//��������
		$sql = "SELECT
					`client_id`,
					IFNULL(SUM(`sum`),0) `sum`
				FROM `_money_refund`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".$ws_id."
				  AND `client_id`
				  AND !`deleted`
				GROUP BY `client_id`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		while($r = mysql_fetch_assoc($q))
			$client[$r['client_id']]['balans_new'] -= $r['sum'];

		$send['count'] = 0;
		foreach($client as $r)
			if(round($r['balans'], 2) != round($r['balans_new'], 2)) {
				$upd[] = '('.$r['id'].','.$r['balans_new'].')';
				$send['count']++;
			}

		if(!empty($upd)) {
			$sql = "INSERT INTO `_client`
						(`id`,`balans`)
					VALUES ".implode(',', $upd)."
					ON DUPLICATE KEY UPDATE `balans`=VALUES(`balans`)";
			query($sql, GLOBAL_MYSQL_CONNECT);
		}

		$send['time'] = round(microtime(true) - TIME, 3);
		jsonSuccess($send);
		break;
	case 'sa_ws_zayav_balans':
		if(!$ws_id = _num($_POST['ws_id']))
			jsonError();

		$sql = "SELECT
				  `id`,
				  0 `accrual`,
				  `accrual_sum`,
				  0 `oplata`,
				  `oplata_sum`,
				  0 `schet_exist`,
				  `schet`
				FROM `zayav`
				WHERE `ws_id`=".$ws_id."
				  AND !`deleted`";
		$zayav = query_arr($sql);

		$sql = "SELECT
					`zayav_id`,
		            IFNULL(SUM(`sum`),0) `sum`
				FROM `_money_accrual`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".$ws_id."
				  AND !`deleted`
				  AND `zayav_id`
				GROUP BY `zayav_id`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		while($r = mysql_fetch_assoc($q))
			$zayav[$r['zayav_id']]['accrual'] = round($r['sum']);

		$sql = "SELECT
					`zayav_id`,
		            IFNULL(SUM(`sum`),0) `sum`
				FROM `_money_income`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".$ws_id."
				  AND !`deleted`
				  AND `zayav_id`
				GROUP BY `zayav_id`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		while($r = mysql_fetch_assoc($q))
			$zayav[$r['zayav_id']]['oplata'] = round($r['sum']);

		$sql = "SELECT
					`zayav_id`,
		            IFNULL(SUM(`sum`),0) `sum`
				FROM `_money_refund`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".$ws_id."
				  AND !`deleted`
				  AND `zayav_id`
				GROUP BY `zayav_id`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		while($r = mysql_fetch_assoc($q))
			$zayav[$r['zayav_id']]['oplata'] -= round($r['sum']);

		$sql = "SELECT
					`zayav_id`,
		            IFNULL(COUNT(`id`),0) `count`
				FROM `_schet`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".$ws_id."
				  AND !`deleted`
				  AND `zayav_id`
				GROUP BY `zayav_id`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		while($r = mysql_fetch_assoc($q))
			$zayav[$r['zayav_id']]['schet_exist'] = _num($r['count']) ? 1 : 0;

		$send['count'] = 0;
		$upd = array();
		foreach($zayav as $r)
			if($r['accrual_sum'] != $r['accrual'] || $r['oplata_sum'] != $r['oplata'] || $r['schet'] != $r['schet_exist']) {
				$upd[] = '('.
						$r['id'].','.
						$r['accrual'].','.
						$r['oplata'].','.
						$r['schet_exist'].
					')';
				$send['count']++;
			}

		if(!empty($upd)) {
			$sql = "INSERT INTO `zayav` (
						`id`,
						`accrual_sum`,
						`oplata_sum`,
						`schet`
					) VALUES ".implode(',', $upd)."
					ON DUPLICATE KEY UPDATE
						`accrual_sum`=VALUES(`accrual_sum`),
						`oplata_sum`=VALUES(`oplata_sum`),
						`schet`=VALUES(`schet`)";
			query($sql);
		}

		$send['time'] = round(microtime(true) - TIME, 3);
		jsonSuccess($send);
		break;
*/

}

function sa_history_ids_insert($type_id, $ids) {//�������� ��������� ����� ������� ��������
	if(empty($ids))
		return;
	foreach($ids as $i => $id) {
		$sql = "INSERT INTO `_history_ids` (
					`type_id`,
					`category_id`,
					`main`
				) VALUES (
					".$type_id.",
					".$id.",
					".(!$i ? 1 : 0)."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);
	}
}