<?php
switch(@$_POST['op']) {
	case 'sa_menu_add':
		$name = _txt($_POST['name']);
		$p = _txt($_POST['p']);

		if(!$name)
			jsonError();

		$sql = "INSERT INTO `_menu` (
					`name`,
					`p`
				) VALUES (
					'".addslashes($name)."',
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

			//изменение на новый type_id записей истории действий
			$sql = "UPDATE `_history`
					SET `type_id`=".$type_id."
					WHERE `type_id`=".$type_id_current;
			query($sql, GLOBAL_MYSQL_CONNECT);

			//изменение на новый type_id ids-истории действий
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
			jsonError(' онстанта уже внесена в базу');

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
			jsonError(' онстанта уже внесена в базу');

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
	case 'sa_rule_flag'://изменение параметра по умолчанию права сотрудника
		if(!$id = _num($_POST['id']))
			jsonError();

		$value_name = _txt($_POST['value_name']);
		$v = _bool($_POST['v']);

		if($value_name != 'admin' && $value_name != 'worker')
			jsonError();

		$sql = "SELECT * FROM `_vkuser_rule_default` WHERE `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		$sql = "UPDATE `_vkuser_rule_default`
		        SET `value_".$value_name."`=".$v."
		        WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		xcache_unset(CACHE_PREFIX.'viewer_rule_default_admin');
		xcache_unset(CACHE_PREFIX.'viewer_rule_default_worker');

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
		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);
		$const = strtoupper(_txt($_POST['const']));

		if(!$name)
			jsonError();
		if(!$const)
			jsonError();

		$sql = "SELECT COUNT(*)
				FROM `_zayav_setup`
				WHERE `const`='".addslashes($const)."'";
		if(query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError(' онстанта зан€та');

		$sql = "INSERT INTO `_zayav_setup` (
					`name`,
					`about`,
					`const`
				) VALUES (
					'".addslashes($name)."',
					'".addslashes($about)."',
					'".addslashes($const)."'
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		sa_zayav_type_link();
		_globalValuesJS();

		$send['html'] = utf8(sa_zayav_pole_spisok());
		jsonSuccess($send);
		break;
	case 'sa_zayav_pole_edit':
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);
		$const = strtoupper(_txt($_POST['const']));

		if(!$name)
			jsonError();
		if(!$const)
			jsonError();

		$sql = "SELECT COUNT(*)
				FROM `_zayav_setup`
				WHERE `const`='".addslashes($const)."'
				  AND `id`!=".$id;
		if(query_value($sql, GLOBAL_MYSQL_CONNECT))
			jsonError(' онстанта зан€та');

		$sql = "UPDATE `_zayav_setup`
				SET `name`='".addslashes($name)."',
					`about`='".addslashes($about)."',
					`const`='".addslashes($const)."'
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		_globalValuesJS();
		sa_zayav_type_link();

		$send['html'] = utf8(sa_zayav_pole_spisok());
		jsonSuccess($send);
		break;
	case 'sa_zayav_setup_use_change':
		if(!$id = _num($_POST['id']))
			jsonError();

		$type_id = _num($_POST['type_id']);

		$sql = "DELETE FROM `_zayav_setup_use`
				WHERE `app_id`=".APP_ID."
				  AND `type_id`=".$type_id."
				  AND `pole_id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		if(_bool($_POST['v'])) {
			$sql = "INSERT INTO `_zayav_setup_use` (
						`app_id`,
						`type_id`,
						`pole_id`
					) VALUES (
						".APP_ID.",
						".$type_id.",
						".$id."
					)";
			query($sql, GLOBAL_MYSQL_CONNECT);
		}

		_globalValuesJS();

		jsonSuccess();
		break;
	case 'sa_zayav_type_add':
		$name = _txt($_POST['name']);

		if(!$name)
			jsonError();

		$sql = "INSERT INTO `_zayav_setup_type` (
					`app_id`,
					`name`
				) VALUES (
					".APP_ID.",
					'".addslashes($name)."'
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		$type_id = query_insert_id('_zayav_setup_type', GLOBAL_MYSQL_CONNECT);

		$sql = "SELECT COUNT(*)
				FROM `_zayav_setup_type`
				WHERE `app_id`=".APP_ID;
		if(query_value($sql, GLOBAL_MYSQL_CONNECT) == 1) {
			$sql = "UPDATE `_zayav`
					SET `type_id`=".$type_id."
					WHERE `app_id`=".APP_ID;
			query($sql, GLOBAL_MYSQL_CONNECT);

			//применение типа за€вки к используемым пол€м
			$sql = "UPDATE `_zayav_setup_use`
					SET `type_id`=".$type_id."
					WHERE `app_id`=".APP_ID;
			query($sql, GLOBAL_MYSQL_CONNECT);
		}

		_globalValuesJS();

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
		_globalValuesJS();

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
		_globalValuesJS();

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
		_globalValuesJS();

		$send['html'] = utf8(sa_color_spisok());
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
			jsonError('Ќеверный id');
		$sql = "SELECT * FROM `workshop` WHERE `id`=".$ws_id;
		if(!$ws = mysql_fetch_assoc(query($sql)))
			jsonError('ќрганизаци€ не существует');
		if($ws['status']) {
			query("UPDATE `workshop` SET `status`=0,`dtime_del`=CURRENT_TIMESTAMP WHERE `id`=".$ws_id);
			query("UPDATE `vk_user` SET `ws_id`=0,`admin`=0 WHERE `ws_id`=".$ws_id);
		} else {
			if(query_value("SELECT `ws_id` FROM `vk_user` WHERE `viewer_id`=".$ws['admin_id']))
				jsonError('«а администратором закреплена друга€ организаци€');
			query("UPDATE `workshop` SET `status`=1,`dtime_del`='0000-00-00 00:00:00' WHERE `id`=".$ws_id);
			query("UPDATE `vk_user` SET `ws_id`=".$ws_id.",`admin`=1 WHERE `viewer_id`=".$ws['admin_id']);
			xcache_unset(CACHE_PREFIX.'viewer_'.$ws['admin_id']);
		}
		_cacheClear($ws_id);
		jsonSuccess();
		break;
	case 'sa_ws_del':
		if(!$ws_id = _num($_POST['ws_id']))
			jsonError();
		foreach(sa_ws_tables() as $tab => $about)
			query("DELETE FROM `".$tab."` WHERE `ws_id`=".$ws_id);
		query("DELETE FROM `workshop` WHERE `id`=".$ws_id);
		query("UPDATE `vk_user` SET `ws_id`=0,`admin`=0 WHERE `ws_id`=".$ws_id);
		_cacheClear($ws_id);
		jsonSuccess();
		break;
	case 'sa_ws_client_balans'://корректировка балансов клиентов
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

		//начислени€
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

		//платежи
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

		//¬озвраты
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

function sa_history_ids_insert($type_id, $ids) {//внесение категорий типам истории действий
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