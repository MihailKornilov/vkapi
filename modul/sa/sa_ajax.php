<?php

if(SA)
switch(@$_POST['op']) {
	case 'sa_menu_add':
		$parent_id = _num($_POST['parent_id']);
		if($dop_id = _num($_POST['dop_id']))
			$parent_id = $dop_id;
		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);
		$hidden = _bool($_POST['hidden']);
		$norule = _bool($_POST['norule']);
		$func_menu = _txt($_POST['func_menu']);
		$func_page = _txt($_POST['func_page']);
		$dop_menu_type = _num($_POST['dop_menu_type']);

		if(!$name)
			jsonError('�� ������� ��������');

		$sql = "INSERT INTO `_menu` (
					`parent_id`,
					`name`,
					`about`,
					`hidden`,
					`norule`,
					`func_menu`,
					`func_page`,
					`dop_menu_type`
				) VALUES (
					".$parent_id.",
					'".addslashes($name)."',
					'".addslashes($about)."',
					".$hidden.",
					".$norule.",
					'".addslashes($func_menu)."',
					'".addslashes($func_page)."',
					".$dop_menu_type."
				)";
		query($sql);

		$sql = "SELECT `id` FROM `_menu` ORDER BY `id` DESC LIMIT 1";
		$id = query_value($sql);

		xcache_unset(CACHE_PREFIX.'menu');

		$send['main'] = utf8(sa_menu_spisok($id));
		jsonSuccess($send);
		break;
	case 'sa_menu_edit'://�������������� ������� �������� ����
		if(!$id = _num($_POST['id']))
			jsonError('������������ �������������');

		$parent_id = _num($_POST['parent_id']);
		if($dop_id = _num($_POST['dop_id']))
			$parent_id = $dop_id;
		$name = win1251(trim($_POST['name']));
		$about = _txt($_POST['about']);
		$hidden = _bool($_POST['hidden']);
		$norule = _bool($_POST['norule']);
		$func_menu = _txt($_POST['func_menu']);
		$func_page = _txt($_POST['func_page']);
		$dop_menu_type = _num($_POST['dop_menu_type']);

		if(!$name)
			jsonError('�� ������� ��������');

		$sql = "SELECT *
				FROM `_menu`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_menu`
				SET `parent_id`=".$parent_id.",
					`name`='".addslashes($name)."',
					`about`='".addslashes($about)."',
					`hidden`=".$hidden.",
					`norule`=".$norule.",
					`func_menu`='".addslashes($func_menu)."',
					`func_page`='".addslashes($func_page)."',
					`dop_menu_type`=".$dop_menu_type."
				WHERE `id`=".$id;
		query($sql);

		//��������� ������� �� ���������
		if(_num($_POST['def'])) {
			$sql = "SELECT COUNT(*)
					FROM `_menu_app`
					WHERE `app_id`=".APP_ID."
					  AND `menu_id`=".$id."
					  AND !`def`";
			if(query_value($sql)) {
				$sql = "UPDATE `_menu_app`
						SET `def`=0
						WHERE `app_id`=".APP_ID;
				query($sql);

				$sql = "UPDATE `_menu_app`
						SET `def`=1
						WHERE `app_id`=".APP_ID."
						  AND `menu_id`=".$id;
				query($sql);
			}
		}


		xcache_unset(CACHE_PREFIX.'menu');

		$send['main'] = utf8(sa_menu_spisok($id));
		jsonSuccess($send);
		break;
	case 'sa_menu_sort':
		$parent_id = _num($_POST['parent_id']);

		$html =
			'<div class="fs15 b">'.
				($parent_id ? _menuCache('name', $parent_id) : '---').
			'</div>'.
			'<dl class="_sort mt10" val="_menu">';

		foreach(_menuCache($parent_id ? 'dop' : 'main', $parent_id) as $r) {
			$html .=
				'<dd val="'.$r['id'].'">'.
					'<table class="_spisokTab mt1 curM">'.
						'<tr><td class="w15 r grey">'.$r['id'].
							'<td>'.$r['name'].
					'</table>';
		}
		$html .= '</dl>';

		$send['html'] = utf8($html);
		jsonSuccess($send);
		break;
	case 'sa_menu_show':
		if(!$id = _num($_POST['id']))
			jsonError();

		if($v = _bool($_POST['v']))
			$sql = "INSERT INTO `_menu_app` (
						`app_id`,
						`menu_id`
					) VALUES (
						".APP_ID.",
						".$id."
					)";
		else
			$sql = "DELETE FROM `_menu_app` WHERE `app_id`=".APP_ID." AND `menu_id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'menu');

		$send['main'] = utf8(sa_menu_spisok($id));
		jsonSuccess($send);
		break;
	case 'sa_menu_access'://������ ��� ������������� �� ���������
		if(!$id = _num($_POST['id']))
			jsonError();

		$v = _bool($_POST['v']);

		$sql = "UPDATE `_menu`
				SET `viewer_access_default`=".$v."
				WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'menu');

		jsonSuccess();
		break;

	case 'sa_history_type_add':
		if(!empty($_POST['type_id']) && !_num($_POST['type_id']))
			jsonError('������������ type_id');

		$type_id = _num($_POST['type_id']);
		$txt = win1251(trim($_POST['txt']));
		$txt_client = win1251(trim($_POST['txt_client']));
		$txt_zayav = win1251(trim($_POST['txt_zayav']));
		$txt_schet = win1251(trim($_POST['txt_schet']));
		if(($ids = _ids($_POST['category_ids'], 1)) == false && $_POST['category_ids'] != 0)
			jsonError('������������ ������ id ���������');

		if($type_id) {
			$sql = "SELECT COUNT(`id`) FROM `_history_type` WHERE `id`=".$type_id;
			if(query_value($sql))
				jsonError('type_id '.$type_id.' �����');
		}

		if(!$txt)
			jsonError('����������� ����� ���������');

		$sql = "INSERT INTO `_history_type` (
					`id`,
					`txt`,
					`txt_client`,
					`txt_zayav`,
					`txt_schet`
				) VALUES (
					".$type_id.",
					'".addslashes($txt)."',
					'".addslashes($txt_client)."',
					'".addslashes($txt_zayav)."',
					'".addslashes($txt_schet)."'
				)";
		query($sql);

		if(!$type_id)
			$type_id = query_insert_id('_history_type');

		sa_history_ids_insert($type_id, $ids);

		$send['html'] = utf8(sa_history_spisok());
		jsonSuccess($send);
		break;
	case 'sa_history_type_edit':
		if(!$type_id_current = _num($_POST['type_id_current']))
			jsonError('������������ ������� type_id');
		if(!$type_id = _num($_POST['type_id']))
			jsonError('������������ type_id');
		if(($ids = _ids($_POST['category_ids'], 1)) == false && $_POST['category_ids'] != 0)
			jsonError('������������ ������ id ���������');

		$txt = win1251(trim($_POST['txt']));
		$txt_client = win1251(trim($_POST['txt_client']));
		$txt_zayav = win1251(trim($_POST['txt_zayav']));
		$txt_schet = win1251(trim($_POST['txt_schet']));

		if(!$txt)
			jsonError('����������� ����� ���������');

		if($type_id_current != $type_id) {
			$sql = "SELECT COUNT(`id`) FROM `_history_type` WHERE `id`=".$type_id;
			if(query_value($sql))
				jsonError();
			$sql = "UPDATE `_history_type`
					SET `id`=".$type_id."
					WHERE `id`=".$type_id_current;
			query($sql);

			//��������� �� ����� type_id ������� ������� ��������
			$sql = "UPDATE `_history`
					SET `type_id`=".$type_id."
					WHERE `type_id`=".$type_id_current;
			query($sql);

			//��������� �� ����� type_id ids-������� ��������
			$sql = "UPDATE `_history_ids`
					SET `type_id`=".$type_id."
					WHERE `type_id`=".$type_id_current;
			query($sql);
		}

		$sql = "UPDATE `_history_type`
				SET `txt`='".addslashes($txt)."',
					`txt_client`='".addslashes($txt_client)."',
					`txt_zayav`='".addslashes($txt_zayav)."',
					`txt_schet`='".addslashes($txt_schet)."'
				WHERE `id`=".$type_id;
		query($sql);

		$sql = "DELETE FROM `_history_ids` WHERE `type_id`=".$type_id;
		query($sql);
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
		query($sql);

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
		if(!query_value($sql))
			jsonError();

		$sql = "UPDATE `_history_category`
				SET `name`='".addslashes($name)."',
					`about`='".addslashes($about)."',
					`js_use`=".$js_use."
				WHERE `id`=".$id;
		query($sql);

		$send['html'] = utf8(sa_history_cat_spisok());
		jsonSuccess($send);
		break;

	case 'sa_rule_edit'://��������/�������������� ��������� ���� �����������
		$id = _num($_POST['id']);
		if(!$key = _txt($_POST['key']))
			jsonError('�� ������� ���������');
		if(!$name = _txt($_POST['name']))
			jsonError('�� ������� ���');
		$about = _txt($_POST['about']);

		$sql = "SELECT COUNT(`id`)
				FROM `_vkuser_rule_default`
				WHERE `key`='".$key."'
				  AND `id`!=".$id;
		if(query_value($sql))
			jsonError('��������� ��� ������� � ����');

		if(!$id) {
			$sql = "INSERT INTO `_vkuser_rule_default` VALUES ()";
			query($sql);
			$id = query_insert_id('_vkuser_rule_default');
		}

		$sql = "UPDATE `_vkuser_rule_default`
		        SET `key`='".strtoupper(addslashes($key))."',
		            `name`='".addslashes($name)."',
		            `about`='".addslashes($about)."'
		        WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'rule_default');

		$send['html'] = utf8(sa_rule_spisok());
		jsonSuccess($send);
		break;
	case 'sa_rule_del'://�������� ��������� ���� �����������
		if(!$id = _num($_POST['id']))
			jsonError('������������ id');

		$sql = "SELECT *
				FROM `_vkuser_rule_default`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('��������� �� ����������');

		$sql = "DELETE FROM `_vkuser_rule_default` WHERE `id`=".$id;
		query($sql);

		$sql = "DELETE FROM `_vkuser_rule` WHERE `key`='".$r['key']."'";
		query($sql);

		xcache_unset(CACHE_PREFIX.'rule_default');

		jsonSuccess();
		break;
	case 'sa_rule_flag'://��������� ��������� �� ��������� ����� ����������
		if(!$id = _num($_POST['id']))
			jsonError();

		$value_name = _txt($_POST['value_name']);
		$v = _num($_POST['v']);

		if($value_name != 'admin' && $value_name != 'worker')
			jsonError('');

		$sql = "SELECT * FROM `_vkuser_rule_default` WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_vkuser_rule_default`
		        SET `value_".$value_name."`=".$v."
		        WHERE `id`=".$id;
		query($sql);

		//��������� �������� ������ ��������� �� ���� ���������������
		if($value_name == 'admin') {
			$sql = "SELECT `viewer_id`
					FROM `_vkuser`
					WHERE `admin`";
			$ids = query_ids($sql);

			$sql = "DELETE FROM `_vkuser_rule`
					WHERE `key`='".$r['key']."'
					  AND `viewer_id` IN (".$ids.")";
			query($sql);

			foreach(_ids($ids, 1) as $viewer_id) {
				xcache_unset(CACHE_PREFIX.'viewer_'.$viewer_id);
				xcache_unset(CACHE_PREFIX.'viewer_rule_'.$viewer_id);
			}
		}

		xcache_unset(CACHE_PREFIX.'rule_default');

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
		query($sql);

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
		if(!query_value($sql))
			jsonError();

		$sql = "UPDATE `_balans_category`
				SET `name`='".addslashes($name)."'
				WHERE `id`=".$id;
		query($sql);

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
		if(!query_value($sql))
			jsonError();

		$sql = "DELETE FROM `_balans_category` WHERE `id`=".$id;
		query($sql);

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
		query($sql);

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
		if(!query_value($sql))
			jsonError();

		$sql = "UPDATE `_balans_action`
				SET `name`='".addslashes($name)."',
					`minus`=".$minus."
				WHERE `id`=".$id;
		query($sql);

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
		if(!query_value($sql))
			jsonError();

		$sql = "DELETE FROM `_balans_action` WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'balans_action');

		$send['html'] = utf8(sa_balans_spisok());
		jsonSuccess($send);
		break;

	case 'sa_client_pole_add'://�������� ������ ���� ������
		if(!$type_id = _num($_POST['type_id']))
			jsonError();
		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);

		if(!$name)
			jsonError();

		$sql = "INSERT INTO `_client_pole` (
					`type_id`,
					`name`,
					`about`
				) VALUES (
					".$type_id.",
					'".addslashes($name)."',
					'".addslashes($about)."'
				)";
		query($sql);

//		xcache_unset(CACHE_PREFIX.'service');
		_globalJsValues();

		$send['html'] = utf8(sa_client_pole_spisok($type_id));
		jsonSuccess($send);
		break;
	case 'sa_client_pole_edit'://�������������� ���� ��������
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);

		if(!$name)
			jsonError();

		$sql = "SELECT * FROM `_client_pole` WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_client_pole`
				SET `name`='".addslashes($name)."',
					`about`='".addslashes($about)."'
				WHERE `id`=".$id;
		query($sql);

//		xcache_unset(CACHE_PREFIX.'service');
		_globalJsValues();

		$send['html'] = utf8(sa_client_pole_spisok($r['type_id']));
		jsonSuccess($send);
		break;
	case 'sa_client_pole_del'://�������� ���� ������
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT * FROM `_client_pole` WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('id ���� �� ����������');

		$sql = "SELECT COUNT(*) FROM `_client_pole_use` WHERE `pole_id`=".$id;
		if(query_value($sql))
			jsonError('��� ���� ������������');

		$sql = "DELETE FROM `_client_pole` WHERE `id`=".$id;
		query($sql);

		$sql = "ALTER TABLE `_client_pole` AUTO_INCREMENT=0";
		query($sql);

//		xcache_unset(CACHE_PREFIX.'service');
		_globalJsValues();

		jsonSuccess();
		break;
	case 'sa_client_category_add'://�������� ����� ��������� ��������
		$name = _txt($_POST['name']);

		if(!$name)
			jsonError();

		$sql = "INSERT INTO `_client_category` (
					`app_id`,
					`name`
				) VALUES (
					".APP_ID.",
					'".addslashes($name)."'
				)";
		query($sql);

		$insert_id = query_insert_id('_client_category');

		xcache_unset(CACHE_PREFIX.'client_category');
		_globalJsValues();

		jsonSuccess();
		break;
	case 'sa_client_category_edit'://�������������� ��������� ��������
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);

		if(empty($name))
			jsonError();

		$sql = "UPDATE `_client_category`
				SET `name`='".addslashes($name)."'
				WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'client_category');
		_appJsValues();

		jsonSuccess();
		break;
	case 'sa_client_category_pole_load':
		$category_id = _num($_POST['category_id']);

		if(!$type_id = _num($_POST['type_id']))
			jsonError();

		$sql = "SELECT `pole_id`
				FROM `_client_pole_use`
				WHERE `app_id`=".APP_ID."
				  AND `category_id`=".$category_id;
		$ids = query_ids($sql);

		$send['html'] = utf8('<div id="sa-client-pole">'.sa_client_pole_spisok($type_id, $ids).'</div>');
		jsonSuccess($send);
		break;
	case 'sa_client_category_pole_add'://���������� ���������� ���� �������
		if(!$pole_id = _num($_POST['pole_id']))
			jsonError('������������ id ����');
		if(!$category_id = _num($_POST['category_id']))
			jsonError('������������ id ���������');

		$name_use = _txt($_POST['label']);
		$require = _bool($_POST['require']);

		$sql = "SELECT * FROM `_client_pole` WHERE `id`=".$pole_id;
		if(!$r = query_assoc($sql))
			jsonError('����������� ���� � ����');

		$sql = "INSERT INTO `_client_pole_use` (
					`app_id`,
					`category_id`,
					`pole_id`,
					`label`,
					`require`,
					`sort`
				) VALUES (
					".APP_ID.",
					".$category_id.",
					".$pole_id.",
					'".addslashes($name_use)."',
					".$require.",
					"._maxSql('_client_pole_use')."
				)";
		query($sql);
		$insert_id = query_insert_id('_client_pole_use');

//		xcache_unset(CACHE_PREFIX.'service');
		_globalJsValues();
		_appJsValues();

		define('CLIENT_CATEGORY_ID', $category_id);
		$send['html'] = utf8(sa_client_category_use($r['type_id'], $insert_id));
		$send['type_id'] = $r['type_id'];
		jsonSuccess($send);
		break;
	case 'sa_client_category_pole_edit'://�������������� ���������� ���� ������
		if(!$id = _num($_POST['id']))
			jsonError();

		$name_use = _txt($_POST['label']);
		$require = _bool($_POST['require']);

		$sql = "SELECT * FROM `_client_pole_use` WHERE `id`=".$id;
		if(!$u = query_assoc($sql))
			jsonError();

		$sql = "SELECT * FROM `_client_pole` WHERE `id`=".$u['pole_id'];
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_client_pole_use`
				SET `label`='".addslashes($name_use)."',
					`require`=".$require."
				WHERE `id`=".$id;
		query($sql);

//		xcache_unset(CACHE_PREFIX.'service');
		_globalJsValues();
		_appJsValues();

		define('CLIENT_CATEGORY_ID', $u['category_id']);
		$send['html'] = utf8(sa_client_category_use($r['type_id'], $id));
		$send['type_id'] = $r['type_id'];
		jsonSuccess($send);
		break;
	case 'sa_client_category_pole_del'://�������� ���������� ���� ������
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT * FROM `_client_pole_use` WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "DELETE FROM `_client_pole_use` WHERE `id`=".$id;
		query($sql);

//		xcache_unset(CACHE_PREFIX.'service');
		_globalJsValues();
		_appJsValues();

		jsonSuccess();
		break;



	case 'sa_service_add'://�������� ������ ���� ������������
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
		query($sql);

		$insert_id = query_insert_id('_zayav_service');

		$sql = "SELECT COUNT(*)
				FROM `_zayav_service`
				WHERE `app_id`=".APP_ID;
		if(query_value($sql) == 1) {
			$sql = "UPDATE `_zayav`
					SET `service_id`=".$insert_id."
					WHERE `app_id`=".APP_ID;
			query($sql);
			$sql = "UPDATE `_zayav_pole_use`
					SET `service_id`=".$insert_id."
					WHERE `app_id`=".APP_ID;
			query($sql);
		}

		xcache_unset(CACHE_PREFIX.'service');
		_globalJsValues();

		jsonSuccess();
		break;
	case 'sa_service_edit'://�������������� �������� ���� ������������
		if(!$id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);

		if(empty($name))
			jsonError();

		$sql = "UPDATE `_zayav_service`
				SET `name`='".addslashes($name)."'
				WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'service');
		_appJsValues();

		jsonSuccess();
		break;

	case 'sa_zayav_pole_add':
		if(!$type_id = _num($_POST['type_id']))
			jsonError('������������ ��� ����');
		if(!$name = _txt($_POST['name']))
			jsonError('�� ������� ��������');

		$about = _txt($_POST['about']);
		$task_use = _bool($_POST['task_use']);
		$param1 = _txt($_POST['param1']);
		$param2 = _txt($_POST['param2']);

		$sql = "INSERT INTO `_zayav_pole` (
					`type_id`,
					`name`,
					`about`,
					`task_use`,
					`param1`,
					`param2`
				) VALUES (
					".$type_id.",
					'".addslashes($name)."',
					'".addslashes($about)."',
					".$task_use.",
					'".addslashes($param1)."',
					'".addslashes($param2)."'
				)";
		query($sql);

		xcache_unset(CACHE_PREFIX.'service');
		_globalJsValues();

		$send['html'] = utf8(sa_zayav_pole_spisok($type_id));
		jsonSuccess($send);
		break;
	case 'sa_zayav_pole_edit'://�������������� ���� ������
		if(!$id = _num($_POST['id']))
			jsonError();
		if(!$name = _txt($_POST['name']))
			jsonError('�� ������� ��������');

		$about = _txt($_POST['about']);
		$task_use = _bool($_POST['task_use']);
		$param1 = _txt($_POST['param1']);
		$param2 = _txt($_POST['param2']);

		$sql = "SELECT * FROM `_zayav_pole` WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_zayav_pole`
				SET `name`='".addslashes($name)."',
					`about`='".addslashes($about)."',
					`task_use`=".$task_use.",
					`param1`='".addslashes($param1)."',
					`param2`='".addslashes($param2)."'
				WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'service');
		_globalJsValues();

		$send['html'] = utf8(sa_zayav_pole_spisok($r['type_id']));
		jsonSuccess($send);
		break;
	case 'sa_zayav_pole_del'://�������� ���� ������
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT * FROM `_zayav_pole` WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('id ���� �� ����������');

		$sql = "SELECT COUNT(*) FROM `_zayav_pole_use` WHERE `pole_id`=".$id;
		if(query_value($sql))
			jsonError('��� ���� ������������');

		$sql = "DELETE FROM `_zayav_pole` WHERE `id`=".$id;
		query($sql);

		$sql = "ALTER TABLE `_zayav_pole` AUTO_INCREMENT=0";
		query($sql);

		xcache_unset(CACHE_PREFIX.'service');
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
		$ids = query_ids($sql);
		
		$send['html'] = utf8(sa_zayav_pole_spisok($type_id, $ids));
		jsonSuccess($send);
		break;
	case 'sa_zayav_service_pole_add'://���������� ���������� ���� ������
		if(!$pole_id = _num($_POST['pole_id']))
			jsonError();

		$service_id = _num($_POST['service_id']);
		$name_use = _txt($_POST['label']);
		$require = _bool($_POST['require']);
		$param_v1 = _bool($_POST['param_v1']);
		$param_v2 = _bool($_POST['param_v2']);

		$sql = "SELECT * FROM `_zayav_pole` WHERE `id`=".$pole_id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "INSERT INTO `_zayav_pole_use` (
					`app_id`,
					`service_id`,
					`pole_id`,
					`label`,
					`require`,
					`param_v1`,
					`param_v2`,
					`sort`
				) VALUES (
					".APP_ID.",
					".$service_id.",
					".$pole_id.",
					'".addslashes($name_use)."',
					".$require.",
					".$param_v1.",
					".$param_v2.",
					"._maxSql('_zayav_pole_use')."
				)";
		query($sql);
		$insert_id = query_insert_id('_zayav_pole_use');

		xcache_unset(CACHE_PREFIX.'service');
		_globalJsValues();
		_appJsValues();

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
		$param_v2 = _bool($_POST['param_v2']);

		$sql = "SELECT * FROM `_zayav_pole_use` WHERE `id`=".$id;
		if(!$u = query_assoc($sql))
			jsonError();

		$sql = "SELECT * FROM `_zayav_pole` WHERE `id`=".$u['pole_id'];
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_zayav_pole_use`
				SET `label`='".addslashes($name_use)."',
					`require`=".$require.",
					`param_v1`=".$param_v1.",
					`param_v2`=".$param_v2."
				WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'service');
		_globalJsValues();
		_appJsValues();

		define('SERVICE_ID', _num($u['service_id']));
		$send['html'] = utf8(sa_zayav_service_use($r['type_id'], $id));
		$send['type_id'] = $r['type_id'];
		jsonSuccess($send);
		break;
	case 'sa_zayav_service_pole_del'://�������� ���������� ���� ������
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT * FROM `_zayav_pole_use` WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "DELETE FROM `_zayav_pole_use` WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'service');
		_globalJsValues();
		_appJsValues();

		jsonSuccess();
		break;

	case 'sa_tovar_cat_get':
		$app_id = _num($_POST['app_id']);
		$send['html'] = utf8(sa_tovar_cat_spisok($app_id));
		jsonSuccess($send);
		break;
	case 'sa_tovar_cat_copy':
		if(!$id = _num($_POST['id']))
			jsonError('������������ id ��������� ������');

		$sql = "SELECT *
				FROM `_tovar_category`
				WHERE `id`=".$id;
		if(!$cat = query_assoc($sql))
			jsonError('��������� �� ����������');

		if($cat['app_id'] == APP_ID)
			jsonError('��������� ����� ����������� ������ �� ������� ����������');

		if(!$cat['parent_id'])
			jsonError('���������� ����� ������ �������� ���������');

		$sql = "SELECT *
				FROM `_tovar_category`
				WHERE `app_id`=".$cat['app_id']."
				  AND `id`=".$cat['parent_id'];
		if(!$parent = query_assoc($sql))
			jsonError('������������ ��������� �� ����������');

		//��������� id ������������ ��������� � ������� ����������
		$sql = "SELECT `id`
				FROM `_tovar_category`
				WHERE `app_id`=".APP_ID."
				  AND `name`='".addslashes($parent['name'])."'
				LIMIT 1";
		if(!$parent_id = query_value($sql)) {
			$sql = "INSERT INTO `_tovar_category` (
						`app_id`,
						`name`
					) VALUES (
						".APP_ID.",
						'".addslashes($parent['name'])."'
					)";
			query($sql);
			$parent_id = query_insert_id('_tovar_category');
		}

		//��������� id �������� ��������� � ������� ����������
		$sql = "SELECT `id`
				FROM `_tovar_category`
				WHERE `app_id`=".APP_ID."
				  AND `parent_id`=".$parent_id."
				  AND `name`='".addslashes($cat['name'])."'
				LIMIT 1";
		if(!$child_id = query_value($sql)) {
			$sql = "INSERT INTO `_tovar_category` (
						`app_id`,
						`parent_id`,
						`name`
					) VALUES (
						".APP_ID.",
						".$parent_id.",
						'".addslashes($cat['name'])."'
					)";
			query($sql);
			$child_id = query_insert_id('_tovar_category');
		}

		//������� ������� � ������� ����������
		$sql = "SELECT *
				FROM `_tovar_bind`
				WHERE `app_id`=".$cat['app_id']."
				  AND `category_id`=".$id;
		if(!$arr = query_arr($sql))
			jsonError('��� ������� ��� ��������');

		foreach($arr as $r) {
			//���� ����� ��� ��� �����, �� �������
			$sql = "SELECT COUNT(*)
					FROM `_tovar_bind`
					WHERE `app_id`=".APP_ID."
					  AND `tovar_id`=".$r['tovar_id']."
					LIMIT 1";
			if(query_value($sql))
				continue;

			$sql = "INSERT INTO `_tovar_bind` (
						`app_id`,
						`category_id`,
						`tovar_id`,
						`articul`,
						`sum_buy`,
						`sum_sell`
					) VALUES (
						".APP_ID.",
						".$child_id.",
						".$r['tovar_id'].",
						'".$r['articul']."',
						"._cena($r['sum_buy']).",
						"._cena($r['sum_sell'])."
					)";
			query($sql);
		}


		jsonSuccess();
		break;

	case 'sa_tovar_measure_add'://�������� ������� ���������
		$short = _txt($_POST['short']);
		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);
		$fraction = _bool($_POST['fraction']);
		$area = _bool($_POST['area']);

		if(!$short)
			jsonError();

		$sql = "INSERT INTO `_tovar_measure` (
					`short`,
					`name`,
					`about`,
					`fraction`,
					`area`,
					`sort`
				) VALUES (
					'".addslashes($short)."',
					'".addslashes($name)."',
					'".addslashes($about)."',
					".$fraction.",
					".$area.",
					"._maxSql('_setup_rubric', 'sort')."
				)";
		query($sql);

		xcache_unset(CACHE_PREFIX.'tovar_measure');
		_globalJsValues();

		$send['html'] = utf8(sa_tovar_measure_spisok());
		jsonSuccess($send);
		break;
	case 'sa_tovar_measure_edit'://�������������� ������� ���������
		if(!$id = _num($_POST['id']))
			jsonError();
		$short = _txt($_POST['short']);
		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);
		$fraction = _bool($_POST['fraction']);
		$area = _bool($_POST['area']);

		if(!$short)
			jsonError();

		$sql = "UPDATE `_tovar_measure`
				SET `short`='".addslashes($short)."',
					`name`='".addslashes($name)."',
					`about`='".addslashes($about)."',
					`fraction`=".$fraction.",
					`area`=".$area."
				WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'tovar_measure');
		_globalJsValues();

		$send['html'] = utf8(sa_tovar_measure_spisok());
		jsonSuccess($send);
		break;
	case 'sa_tovar_measure_del'://�������� ������� ���������
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT * FROM `_tovar_measure` WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "SELECT COUNT(*) FROM `_tovar` WHERE `measure_id`=".$id;
		if(query_value($sql))
			jsonError('��� ������� ��������� ������������');

		$sql = "DELETE FROM `_tovar_measure` WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'tovar_measure');
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
		query($sql);

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
		query($sql);

		xcache_unset(CACHE_PREFIX.'setup_color');
		_appJsValues();

		$send['html'] = utf8(sa_color_spisok());
		jsonSuccess($send);
		break;
	case 'sa_color_del':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT * FROM `_setup_color` WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "SELECT COUNT(`id`) FROM `_zayav` WHERE `color_id`=".$id;
		if(query_value($sql))
			jsonError();

		$sql = "SELECT COUNT(`id`) FROM `_zayav` WHERE `color_dop`=".$id;
		if(query_value($sql))
			jsonError();

		$sql = "DELETE FROM `_setup_color` WHERE `id`=".$id;
		query($sql);

		xcache_unset(CACHE_PREFIX.'setup_color');
		_appJsValues();

		$send['html'] = utf8(sa_color_spisok());
		jsonSuccess($send);
		break;

	case 'sa_template_default_add'://����� ������ �� ���������
		if(!$name = _txt($_POST['name']))
			jsonError('�� ������� ��������');
		if(!$name_link = _txt($_POST['name_link']))
			jsonError('�� ������ ����� ������');
		if(!$name_file = _txt($_POST['name_file']))
			jsonError('�� ������� ��� ����� ���������');

		$attach_id = _num($_POST['attach_id']);
		$use = _txt($_POST['use']);

		$sql = "INSERT INTO `_template_default` (
					`name`,
					`attach_id`,
					`name_link`,
					`name_file`,
					`use`
				) VALUES (
					'".addslashes($name)."',
					".$attach_id.",
					'".addslashes($name_link)."',
					'".addslashes($name_file)."',
					'".addslashes($use)."'
				)";
		query($sql);

		$send['html'] = utf8(sa_template_default_spisok());
		jsonSuccess($send);
		break;
	case 'sa_template_default_edit'://�������������� ������� �� ���������
		if(!$id = _num($_POST['id']))
			jsonError();
		if(!$name = _txt($_POST['name']))
			jsonError('�� ������� ��������');
		if(!$name_link = _txt($_POST['name_link']))
			jsonError('�� ������ ����� ������');
		if(!$name_file = _txt($_POST['name_file']))
			jsonError('�� ������� ��� ����� ���������');

		$attach_id = _num($_POST['attach_id']);
		$use = _txt($_POST['use']);

		$sql = "UPDATE `_template_default`
				SET `name`='".addslashes($name)."',
					`attach_id`=".$attach_id.",
					`name_link`='".addslashes($name_link)."',
					`name_file`='".addslashes($name_file)."',
					`use`='".addslashes($use)."'
				WHERE `id`=".$id;
		query($sql);

		$send['html'] = utf8(sa_template_default_spisok());
		jsonSuccess($send);
		break;
	case 'sa_template_group_add'://����� ������ ���������� ��� ��������
		if(!$name = _txt($_POST['name']))
			jsonError('�� ������� �������� ������');
		if(!$table_name = _txt($_POST['table_name']))
			jsonError('�� ������� �������� �������');

		$sql = "SHOW TABLES LIKE '".$table_name."'";
		if(!query_value($sql))
			jsonError('������� "'.$table_name.'" �� ����������');

		$sql = "INSERT INTO `_template_var_group` (
					`name`,
					`table_name`,
					`sort`
				) VALUES (
					'".addslashes($name)."',
					'".addslashes($table_name)."',
					"._maxSql('_template_var_group')."
				)";
		query($sql);

		$send['html'] = utf8(sa_template_spisok());
		jsonSuccess($send);
		break;
	case 'sa_template_var_load':
		$send['group_id'] = 0;
		$send['name'] = '';
		$send['v'] = '{}';
		$send['col_name'] = '';

		if($id = _num($_POST['id'])) {
			$sql = "SELECT *
					FROM `_template_var`
					WHERE `id`=".$id;
			if($r = query_assoc($sql)) {
				$send['group_id'] = _num($r['group_id']);
				$send['name'] = utf8($r['name']);
				$send['v'] = $r['v'];
				$send['col_name'] = $r['col_name'];
			}
		}

		$sql = "SELECT `id`,`name`
				FROM `_template_var_group`
				ORDER BY `sort`";
		$send['group_spisok'] = query_selArray($sql);
		
		jsonSuccess($send);
		break;
	case 'sa_template_var_add'://����� ���������� ��� ��������
		if(!$group_id = _num($_POST['group_id']))
			jsonError('�� ������� ������');
		if(!$name = _txt($_POST['name']))
			jsonError('�� ������� ��������');
		if(!$v = _txt($_POST['v']))
			jsonError('�� ������ ���');
		if(!$col_name = _txt($_POST['col_name']))
			jsonError('�� ������� ������� �������');

		$sql = "SELECT *
				FROM `_template_var_group`
				WHERE `id`=".$group_id;
		if(!$group = query_assoc($sql))
			jsonError('������ id'.$group_id.' �� ����������');

		$sql = "SHOW TABLES LIKE '".$group['table_name']."'";
		if(!query_value($sql))
			jsonError('������� "'.$group['table_name'].'" �� ����������');

		$sql = "DESCRIBE `".$group['table_name']."` `".$col_name."`";
		if(!query_value($sql))
			jsonError('������� `'.$group['table_name'].'`.`'.$col_name.'` �� ����������');

		$sql = "INSERT INTO `_template_var` (
					`group_id`,
					`name`,
					`v`,
					`col_name`,
					`sort`
				) VALUES (
					".$group_id.",
					'".addslashes($name)."',
					'".addslashes($v)."',
					'".addslashes($col_name)."',
					"._maxSql('_template_var')."
				)";
		query($sql);

		$send['html'] = utf8(sa_template_spisok());
		jsonSuccess($send);
		break;
	case 'sa_template_var_edit'://�������������� ���������� ��� ��������
		if(!$id = _num($_POST['id']))
			jsonError();
		if(!$group_id = _num($_POST['group_id']))
			jsonError('�� ������� ������');
		if(!$name = _txt($_POST['name']))
			jsonError('�� ������� ��������');
		if(!$v = _txt($_POST['v']))
			jsonError('�� ������ ���');
		if(!$col_name = _txt($_POST['col_name']))
			jsonError('�� ������� ������� �������');

		$sql = "SELECT *
				FROM `_template_var`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('���������� id'.$id.' �� ����������');

		$sql = "SELECT *
				FROM `_template_var_group`
				WHERE `id`=".$group_id;
		if(!$group = query_assoc($sql))
			jsonError('������ id'.$group_id.' �� ����������');

		$sql = "SHOW TABLES LIKE '".$group['table_name']."'";
		if(!query_value($sql))
			jsonError('������� "'.$group['table_name'].'" �� ����������');

		$sql = "DESCRIBE `".$group['table_name']."` `".$col_name."`";
		if(!query_value($sql))
			jsonError('������� `'.$group['table_name'].'`.`'.$col_name.'` �� ����������');

		$sql = "UPDATE `_template_var`
				SET `group_id`=".$group_id.",
					`name`='".addslashes($name)."',
					`v`='".addslashes($v)."',
					`col_name`='".addslashes($col_name)."'
				WHERE `id`=".$id;
		query($sql);

		$send['html'] = utf8(sa_template_spisok());
		jsonSuccess($send);
		break;
	case 'sa_template_var_del'://�������� ���������� ��� ��������
		if(!$id = _num($_POST['id']))
			jsonError('������������ id ����������');

		$sql = "SELECT *
				FROM `_template_var`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('���������� id'.$id.' �� ����������');

		$sql = "DELETE FROM `_template_var` WHERE `id`=".$id;
		query($sql);

		$send['html'] = utf8(sa_template_spisok());
		jsonSuccess($send);
		break;

	case 'sa_count_client_load':
		set_time_limit(40);
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
				) - (
					SELECT IFNULL(SUM(`cena`),0)
					FROM `_zayav_gazeta_nomer`
					WHERE `client_id`=`c`.`id`
					  AND !`schet_id`
				) WHERE `app_id`=".APP_ID."
					AND !`deleted`";
		query($sql);

		$sql = "SELECT *
				FROM `_client`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `balans`!=`balans_test`";
		$client = query_arr($sql);

		$spisok = '';
		foreach($client as $r)
			$spisok .= '<a href="'.URL.'&p=42&id='.$r['id'].'"><b>'.$r['id'].'</b></a> '.
					   '<a class="client-balans-repair" val="'.$r['id'].'">���������</a>'.
					   '<br />';

		$send['html'] = utf8(
			'<div>app: '.APP_ID.' - '._app('app_name').'</div>'.
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

	case 'sa_zayav_load'://��������� ������, � ������� ����������� �����
		//����� ����� ��������
		$sql = "UPDATE `_zayav`
				SET `sum_test`=0
				WHERE `app_id`=".APP_ID;
		query($sql);

		//����������
		$sql = "UPDATE `_zayav` `z`
				SET `sum_test`=IF(
					(
						SELECT IFNULL(SUM(`sum`),0)
						FROM `_money_accrual`
						WHERE !`deleted`
						  AND `zayav_id`=`z`.`id`
					) + (
						SELECT IFNULL(SUM(`cena`),0)
						FROM `_zayav_gazeta_nomer`
						WHERE `zayav_id`=`z`.`id`
						  AND !`schet_id`
					) = `sum_accrual`,0,1
				)
				WHERE `app_id`=".APP_ID;
		query($sql);

		//�������
		$sql = "UPDATE `_zayav` `z`
				SET `sum_test`=IF(
					(
						SELECT IFNULL(SUM(`sum`),0)
						FROM `_money_income`
						WHERE `confirm` NOT IN (1,3)
						  AND !`deleted`
						  AND `zayav_id`=`z`.`id`
					) - (
						SELECT IFNULL(SUM(`sum`),0)
						FROM `_money_refund`
						WHERE !`deleted`
						  AND `zayav_id`=`z`.`id`
					) = `sum_pay`,0,2
				)
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND !`sum_test`";
		query($sql);

		//������� - �����
		$sql = "UPDATE `_zayav`
				SET `sum_test`=IF(`sum_dolg`=`sum_pay`-`sum_accrual`,0,3)
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND !`sum_test`";
		query($sql);

		//�������
		$sql = "UPDATE `_zayav` `z`
				SET `sum_test`=IF(
					(
						SELECT IFNULL(SUM(`sum`),0)
						FROM `_zayav_expense`
						WHERE `zayav_id`=`z`.`id`
					) = `sum_expense`,0,4
				)
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND !`sum_test`";
		query($sql);

		//�������
		$sql = "UPDATE `_zayav`
				SET `sum_test`=IF(`sum_profit`=`sum_accrual`-`sum_expense`,0,5)
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND !`sum_test`";
		query($sql);






		$sql = "SELECT COUNT(`id`)
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `sum_test`";
		$all = query_value($sql);

		$sql = "SELECT *
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `sum_test`
				LIMIT 1000";
		$zayav = query_arr($sql);

		$sumErr = array(
			1 => '����������',
			2 => '�����',
			3 => '����',
			4 => '������',
			5 => '�������'
		);

		$spisok = '';
		foreach($zayav as $r)
			$spisok .= '<a href="'.URL.'&p=45&id='.$r['id'].'">������ <b>#'.$r['id'].'</b></a> '.
					   '&nbsp;&nbsp;<span class="grey">('.$sumErr[$r['sum_test']].')</span>&nbsp;&nbsp; '.
					   '<a onclick="saZayavBalansRepair('.$r['id'].')" id="rep'.$r['id'].'">���������</a>'.
					   '<br />';

		$send['html'] = utf8(
			'<div>app: '.APP_ID.' - '._app('app_name').'</div>'.
			'<div>�����: <b>'.$all.'</b></div>'.
		(count($zayav) ?
			'<div>�������� '.count($zayav).':'.
				' <a onclick="saZayavBalansRepairAll(\''._idsGet($zayav).'\')" id="rep-all">��������� ��</a>'.
			'</div>'
		: '').
			'<br />'.
			$spisok
		);
		jsonSuccess($send);
		break;
	case 'sa_zayav_balans_repair':
		if(!$zayav_id = _num($_POST['zayav_id']))
			jsonError();

		_zayavBalansUpdate($zayav_id);

		jsonSuccess();
		break;
	case 'sa_zayav_balans_repair_all':
		if(!$ids = _ids($_POST['ids'], 1))
			jsonError();

		foreach($ids as $zayav_id)
			_zayavBalansUpdate($zayav_id);

		jsonSuccess();
		break;
	case 'sa_count_tovar_set_find_update':
		$start = _num(@$_POST['start']);
		
		$sql = "SELECT DISTINCT(`tovar_id_set`)
				FROM `_tovar`
				WHERE `tovar_id_set`";
		$ids = query_ids($sql);

		$sql = "SELECT *
				FROM `_tovar`
				WHERE `id` IN (".$ids.")";
		$tovar = query_arr($sql);

		$sql = "SELECT *
				FROM `_tovar`
				WHERE `tovar_id_set`
				LIMIT ".$start.",500";
		$q = query($sql);
		$count = mysql_num_rows($q);
		while($r = mysql_fetch_assoc($q)) {
			$t = $tovar[$r['tovar_id_set']];

			$find =
				_tovarVendor($t['vendor_id']).
				$t['name'];

			$sql = "UPDATE `_tovar`
					SET `find`='".addslashes($find)."'
					WHERE `id`=".$r['id'];
			query($sql);
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
		$q = query($sql);
		while($r = mysql_fetch_assoc($q)) {
			$sql = "SELECT COUNT(`id`)
					FROM `_tovar_avai`
					WHERE !LENGTH(`articul`)
					  AND `app_id`=".$r['app_id'];
			if(!$count = query_value($sql))
				continue;

			//��������� ������������� �������� ��������
			$sql = "SELECT MAX(`articul`)
					FROM `_tovar_avai`
					WHERE `app_id`=".$r['app_id'];
			$max = _num(query_value($sql)) + 1;

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
				query($sql);

				$max++;
			}
		}

		jsonSuccess();
		break;
	case 'sa_count_tovar_avai_load':
		//�������� ������: ����������e
		$sql = "SELECT
					`tovar_id` `id`,
					IFNULL(SUM(`count`),0) `count`
				FROM `_tovar_move`
				WHERE `app_id`=".APP_ID."
				  AND `type_id`=1
				GROUP BY `tovar_id`";
		$tovar = query_arr($sql);

		//�������� ������: ������
		$sql = "SELECT
					`tovar_id` `id`,
					IFNULL(SUM(`count`),0) `count`
				FROM `_tovar_move`
				WHERE `app_id`=".APP_ID."
				  AND `type_id`!=1
				GROUP BY `tovar_id`";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q)) {
			if(!isset($tovar[$r['id']]))
				$tovar[$r['id']] = array(
					'id' => $r['id'],
					'count' => 0
				);
			$tovar[$r['id']]['count'] -= $r['count'];
		}

		//���������� � �������� �� �������
		$sql = "SELECT
					`tovar_id` `id`,
					IFNULL(SUM(`tovar_count`),0) `count`
				FROM `_zayav_expense`
				WHERE `app_id`=".APP_ID."
				  AND `tovar_id`
				  AND `tovar_avai_id`
				GROUP BY `tovar_id`";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q))
			$tovar[$r['id']]['count'] -= $r['count'];

		//������� ������ - �������
		$sql = "SELECT
					`tovar_id` `id`,
					IFNULL(SUM(`tovar_count`),0) `count`
				FROM `_money_income`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `tovar_id`
				GROUP BY `tovar_id`";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q)) {
			if(!isset($tovar[$r['id']]))
				$tovar[$r['id']] = array(
					'id' => $r['id'],
					'count' => 0
				);
			$tovar[$r['id']]['count'] -= $r['count'];
		}

		//���������� �������� ��������
		foreach($tovar as $id => $r) {
			$tovar[$id]['tovar_id'] = $id;
			$tovar[$id]['diff'] = 0;
		}

		//������� �������, ���������� ������� ����������
		//�������� �� ������ �������, ���������� ������� ���������
		//� ������ ��������� ������, ������� ���� �������������, �� �� ���������� � �������
		$sql = "SELECT
					`tovar_id` `id`,
					IFNULL(SUM(`count`),0) `count`
				FROM `_tovar_avai`
				WHERE `app_id`=".APP_ID."
				GROUP BY `tovar_id`";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q)) {
			if(!$r['count'] && empty($tovar[$r['id']]))
				continue;

			if(!isset($tovar[$r['id']])) {
				$tovar[$r['id']] = array(
					'id' => $r['id'],
					'tovar_id' => $r['id'],
					'count' => 'count',
					'diff' => 'no-avai'
				);
				continue;
			}

			if($tovar[$r['id']]['count'] != $r['count'])
				$tovar[$r['id']]['diff'] = $r['count'];
			else unset($tovar[$r['id']]);
		}


		//�������� �������, ������� ������� �������
		foreach($tovar as $id => $r)
			if(!$r['count'])
				unset($tovar[$id]);

		$tovar = _tovarValToList($tovar);

		$spisok ='<table class="_spisok">';
		foreach($tovar as $r)
			$spisok .=
				'<tr>'.
					'<td>'.$r['tovar_set'].
					'<td'.($r['diff'] ? ' class="red"' : '').'>'.
						$r['count'].
						($r['diff'] ?
							' <span class="grey">('.$r['diff'].')</span>'.
							' <a class="tovar-avai-repair" val="'.$r['id'].'">���������</a>'
						: '');

		$spisok .= '</table>';

		$send['html'] = utf8(
			'<div>app: '.APP_ID.' - '._app('app_name').'</div>'.
			'<br />'.
			'<div>������ � ��������: <b>'.count($tovar).'</b></div>'.
			'<br />'.
			$spisok
		);
		jsonSuccess($send);
		break;
	case 'sa_count_tovar_avai_repair':
		if(!$tovar_id = _num($_POST['tovar_id']))
			jsonError();

		_tovarAvaiUpdate($tovar_id);

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
		query($sql);

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
		query($sql);

		xcache_unset(CACHE_PREFIX.'app');

		$send['html'] = utf8(sa_app_spisok());
		jsonSuccess($send);
		break;
	case 'sa_app_cache_clear':
		if(!$app_id = _num($_POST['app_id']))
			jsonError();

		_globalCacheClear($app_id);

		jsonSuccess();
		break;
	

	case 'sa_user_action':
		if(!$viewer_id = _num($_POST['viewer_id']))
			jsonError();

		$tab = '';
		$sql = "SHOW TABLES";
		$q = query($sql);
		while($r = mysql_fetch_row($q)) {
			$count = '';
			if(!$count = sa_user_tab_test($r[0], 'viewer_id_add', $viewer_id))
				if(!$count = sa_user_tab_test($r[0], 'viewer_id', $viewer_id))
					if(!$count = sa_user_tab_test($r[0], 'admin_id', $viewer_id))
						if(!$count = sa_user_tab_test($r[0], 'worker_id', $viewer_id))
							if(!$count = sa_user_tab_test($r[0], 'executer_id', $viewer_id))
								continue;
			$tab .= '<tr><td>'.$r[0].'<td class="c">'.$count;
		}

		$send['html'] = '<table class="action-res">'.$tab.'</table>';
		jsonSuccess($send);
		break;

	/*

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
		$client = query_arr($sql);

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
		$q = query($sql);
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
		$q = query($sql);
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
		$q = query($sql);
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
			query($sql);
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
		$q = query($sql);
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
		$q = query($sql);
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
		$q = query($sql);
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
		$q = query($sql);
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
		query($sql);
	}
}