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
		xcache_unset(CACHE_PREFIX.'menu_app'.APP_ID);
		xcache_unset(CACHE_PREFIX.'menu_sort'.APP_ID);

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
		xcache_unset(CACHE_PREFIX.'menu_app'.APP_ID);
		xcache_unset(CACHE_PREFIX.'menu_sort'.APP_ID);

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
					"._maxSql('_history_category', 'sort', GLOBAL_MYSQL_CONNECT)."
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
}//sa_history_ids_insert()