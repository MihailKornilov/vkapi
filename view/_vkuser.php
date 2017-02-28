<?php
/*
function _viewer($viewer_id=VIEWER_ID, $val=false) {
	if(is_array($viewer_id))
		return _viewerArray($viewer_id);

	if(!_num($viewer_id))
		die('Viewer: '.$viewer_id.' is not correct.');

	$new = false;
	$key = CACHE_PREFIX.'viewer_'.$viewer_id;
	$u = xcache_get($key);
	if(empty($u)) {
		$sql = "SELECT * FROM `vk_user` WHERE `viewer_id`=".$viewer_id;
		if(!$u = query_assoc($sql)) {
			$u = _viewerUpdate($viewer_id);
			$new = true;
		}
		$u = _viewerFormat($u);
		xcache_set($key, $u, 86400);
	}
	if($val)
		return isset($u[$val]) ? $u[$val] : false;

	if(APP_FIRST_LOAD && $viewer_id == VIEWER_ID && !defined('ENTER_LAST_UPDATE')) {
		$nu = array(
			'id' => VIEWER_ID,
			'is_app_user' => empty($_GET['is_app_user']) ? 0 : 1,
			'rule_menu_left' => intval(@$_GET['api_settings'])&256 ? 1 : 0,
			'rule_notify' => intval(@$_GET['api_settings'])&1 ? 1 : 0
		);
		query("UPDATE `vk_user`
			   SET `enter_last`=CURRENT_TIMESTAMP,
				   `is_app_user`=".$nu['is_app_user'].",
				   `rule_menu_left`=".$nu['rule_menu_left'].",
				   `rule_notify`=".$nu['rule_notify'].",
				   `access_token`='".$_GET['access_token']."'
			   WHERE `viewer_id`=".VIEWER_ID);
		if(!$new && function_exists('viewerSettingsHistory')) {
			viewerSettingsHistory($u, $nu);
			xcache_unset($key);
		}
		define('ENTER_LAST_UPDATE', true);
	}

	$u['new'] = $new;
	return $u;
}
*/

/*
					if(APP_FIRST_LOAD) { //учёт посещений
						$day = strftime('%Y-%m-%d');
						$sql = "SELECT `id` FROM `vk_visit` WHERE `viewer_id`=".VIEWER_ID." AND `day`='".$day."' LIMIT 1";
						$id = query_value($sql);
						$sql = "INSERT INTO `vk_visit` (
								`id`,
								`viewer_id`,
								`day`,
								`is_secure`
							 ) VALUES (
								".($id === false ? 0 : $id).",
								".VIEWER_ID.",
								'".$day."',
								"._bool($_GET['is_secure'])."
							 ) ON DUPLICATE KEY UPDATE
								`count_day`=`count_day`+1,
								`is_secure`="._bool($_GET['is_secure']);
						query($sql);
						query("UPDATE `vk_user` SET `count_day`=".($id === false ? 1 : "`count_day`+1")." WHERE `viewer_id`=".VIEWER_ID);
					}
*/

function _viewer($viewer_id=VIEWER_ID, $i='') {//получение данных о пользовате из контакта
	//список сотрудников для _select
	if($viewer_id == 'worker_js') {
		$sql = "SELECT
					`viewer_id`,
					CONCAT(`first_name`,' ',`last_name`)
				 FROM `_vkuser`
				 WHERE `app_id`=".APP_ID."
				   AND `worker`
				   AND !`hidden`
				 ORDER BY `dtime_add`";
		return query_selJson($sql);
	}

	if(is_array($viewer_id))
		return _viewerValToList($viewer_id);

	if(!_num($viewer_id))
		die('_viewer: '.$viewer_id.' is not correct.');

	$u = _viewerCache($viewer_id);
	$u = _viewerFormat($u);

	if($i && !isset($u[$i]))
		return 'viewer: неизвестный ключ <b>'.$i.'</b>';

	return $i ? $u[$i] : $u;
}
function _viewerCache($viewer_id=VIEWER_ID) {//получение данных пользователя из кеша
	$key = CACHE_PREFIX.'viewer_'.$viewer_id;
	if(!$u = xcache_get($key)) {
		$sql = "SELECT *
				FROM `_vkuser`
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$viewer_id;
		if(!$u = query_assoc($sql))
			$u = _viewerUpdate($viewer_id);
		else
			_debugLoad('Загружены данные пользователя из Базы');
		xcache_set($key, $u, 86400);
	} else
		_debugLoad('Получены данные пользователя из Кеша');

	return $u;
}
function _viewerUpdate($viewer_id=VIEWER_ID) {//Обновление пользователя из Контакта
	if(LOCAL)
		_appError('Not load vk user <b>'.$viewer_id.'</b> in LOCAL version.');

	$res = _vkapi('users.get', array(
		'user_ids' => $viewer_id,
		'access_token' => '',
		'fields' => 'photo,'.
					'sex,'.
					'country,'.
					'city'
	));

	if(empty($res['response']))
		die('Do not get user from VK: '.$viewer_id);

	$res = $res['response'][0];
	$u = array(
		'viewer_id' => $viewer_id,
		'first_name' => win1251($res['first_name']),
		'last_name' => win1251($res['last_name']),
		'sex' => $res['sex'],
		'photo' => $res['photo'],
		'country_id' => empty($res['country']) ? 0 : $res['country']['id'],
		'country_title' => empty($res['country']) ? '' : win1251($res['country']['title']),
		'city_id' => empty($res['city']) ? 0 : $res['city']['id'],
		'city_title' => empty($res['city']) ? '' : win1251($res['city']['title'])
	);

	$sql = "SELECT `id`
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `viewer_id`=".$viewer_id."
			LIMIT 1";
	$id = query_value($sql);

	$sql = "INSERT INTO `_vkuser` (
				`id`,
				`app_id`,
				`viewer_id`,
				`first_name`,
				`last_name`,
				`sex`,
				`photo`,
				`country_id`,
				`country_title`,
				`city_id`,
				`city_title`
			) VALUES (
				".$id.",
				".APP_ID.",
				".$viewer_id.",
				'".addslashes($u['first_name'])."',
				'".addslashes($u['last_name'])."',
				"._num($u['sex']).",
				'".addslashes($u['photo'])."',
				"._num($u['country_id']).",
				'".addslashes($u['country_title'])."',
				"._num($u['city_id']).",
				'".addslashes($u['city_title'])."'
			) ON DUPLICATE KEY UPDATE
				`first_name`=VALUES(`first_name`),
				`last_name`=VALUES(`last_name`),
				`sex`=VALUES(`sex`),
				`photo`=VALUES(`photo`),
				`country_id`=VALUES(`country_id`),
				`country_title`=VALUES(`country_title`),
				`city_id`=VALUES(`city_id`),
				`city_title`=VALUES(`city_title`)";
	query($sql);

	_debugLoad($id ? 'Обновлены данные пользователя из Контакта' : 'Внесён новый пользователь');

	return _viewerCache($viewer_id);
}
function _viewerValToList($arr) {//вставка данных о пользователях контакта в массив по viewer_id_add и worker_id
	$viewer_ids = array(); //Сбор id пользователей
	$viewer_ass = array(); //Присвоение каждому id пользователя списка элементов, которые относятся к нему
	$worker_ids = array();
	$worker_ass = array();
	foreach($arr as $r) {
		$viewer_ids[$r['viewer_id_add']] = 1;
		$viewer_ass[$r['viewer_id_add']][] = $r['id'];
		if(!empty($r['worker_id'])) {
			$worker_ids[$r['worker_id']] = 1;
			$worker_ass[$r['worker_id']][] = $r['id'];
		}
	}

	unset($viewer_ids[0]);

	$ids = $viewer_ids + $worker_ids;
	if(!empty($ids)) {
		$sql = "SELECT *
				FROM `_vkuser`
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id` IN (".implode(',', array_unique(array_keys($ids))).")";
		$q = query($sql);
		while($u = mysql_fetch_assoc($q)) {
			if(isset($viewer_ass[$u['viewer_id']]))
				foreach($viewer_ass[$u['viewer_id']] as $id)
					$arr[$id] += _viewerFormat($u);

			if(isset($worker_ass[$u['viewer_id']]))
				foreach($worker_ass[$u['viewer_id']] as $id) {
					$w = _viewerFormat($u);
					$arr[$id] += array(
						'worker_name' => $w['viewer_name'],
						'worker_link' => '<a href="'.URL.'&p=74&id='.$u['viewer_id'].'">'.$w['viewer_name'].'</a>',
						'worker_setup' => '<a href="'.URL.'&p=74&id='.$u['viewer_id'].'">'.$w['viewer_name'].'</a>',
						'worker_salary' => '<a href="'.URL.'&p=65&id='.$u['viewer_id'].'">'.$w['viewer_name'].'</a>'
					);
				}
		}
	}

	return $arr;
}
function _viewerFormat($u) {//формирование данных пользователя
	$send = array(
		'viewer_app_id' => $u['app_id'],
		'viewer_first_name' => $u['first_name'],
		'viewer_last_name' => $u['last_name'],
		'viewer_middle_name' => $u['middle_name'],
		'viewer_name' => $u['first_name'].' '.$u['last_name'],
		'viewer_name_full' => $u['last_name'].' '.$u['first_name'].(!empty($u['middle_name']) ? ' '.$u['middle_name'] : ''),
		//Фамилия И.О.
		'viewer_name_init' =>
			$u['last_name'].
			($u['first_name'] ? ' '.strtoupper($u['first_name'][0]).'.' : '').
			(!empty($u['middle_name']) ? ' '.strtoupper($u['middle_name'][0]).'.' : ''),

		'viewer_post' => $u['post'],
		'viewer_sex' => $u['sex'],

		'viewer_photo' => '<img src="'.$u['photo'].'" />',

		'viewer_admin' => $u['admin'],
		'viewer_worker' => $u['worker'],

		'viewer_country_id' => $u['country_id'],
		'viewer_city_id' => $u['city_id'],
		'viewer_city_name' => $u['city_title'],

		'viewer_last_seen' => $u['last_seen'],

		'pin' => $u['pin'],

		'balans_start' => round($u['salary_balans_start'], 2),
		'rate_sum' => _cena($u['salary_rate_sum']),
		'rate_period' => $u['salary_rate_period'],
		'rate_day' => $u['salary_rate_day'],
		'bonus_sum' => _cena($u['salary_bonus_sum']),
		'zayav_report_cols_show' => $u['zayav_report_cols_show'],
		'invoice_id_default' => _num(@$u['invoice_id_default'])
	);

	$send['viewer_link'] = '<a href="//vk.com/id'.$u['viewer_id'].'" target="_blank">'.$send['viewer_name'].'</a>';
	$send['viewer_link_photo'] = '<a href="//vk.com/id'.$u['viewer_id'].'" target="_blank">'.$send['viewer_photo'].'</a>';
	$send['viewer_link_zp'] = '<a href="'.@URL.'&p=65&id='.$u['viewer_id'].'">'.$send['viewer_name'].'</a>';//страница с зарплатой
	$send['viewer_link_my'] = '<a href="'.@URL.'&p=12" class="setup-my'._tooltip('Мои настройки', -10).$send['viewer_name'].'</a>';//страница с моими настройками

	return $send;
}

function _viewerWorkerQuery($viewer_id=VIEWER_ID) {//получение данных сотрудника конкретной организации, проверка на существование
	$sql = "SELECT *
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `worker`
			  AND `viewer_id`=".$viewer_id;
	return query_assoc($sql);
}


function _viewerAdded($viewer_id) {//Вывод сотрудника, который вносил запись с учётом пола
	if(!$viewer_id)
		return 'внесено автоматически';
	return 'вн'.(_viewer($viewer_id, 'viewer_sex') == 1 ? 'есла' : 'ёс').' '._viewer($viewer_id, 'viewer_name');
}
function _viewerDeleted($viewer_id) {//Вывод сотрудника, который вносил запись с учётом пола
	return 'удалил'.(_viewer($viewer_id, 'sex') == 1 ? 'а' : '').' '._viewer($viewer_id, 'viewer_name');
}



function _viewerAuth() {//Получение данных о пользователе при запуске приложения
	$u = _viewer();

	define('VIEWER_NAME', $u['viewer_name']);
	define('VIEWER_ADMIN', $u['viewer_admin']);
	define('VIEWER_WORKER', $u['viewer_worker']);
	define('VIEWER_COUNTRY_ID', $u['viewer_country_id']);
	define('VIEWER_CITY_ID', $u['viewer_city_id']);

	define('PIN', !empty($u['pin']) && !VIEWER_ID_ADMIN);
	define('PIN_LEN', strlen($u['pin']));
	define('PIN_TIME_KEY', APP_ID.'pin_time_'.VIEWER_ID);
	define('PIN_TIME_LEN', 3600); // длительность в секундах действия пинкода
	define('PIN_TIME', empty($_SESSION[PIN_TIME_KEY]) ? 0 : $_SESSION[PIN_TIME_KEY]);
	define('PIN_ENTER', PIN && (APP_FIRST_LOAD || (PIN_TIME - time() < 0)));//требуется ли ввод пин-кода

	_viewerRule();      //формирование констант прав

	_debugLoad('Пользователь авторизирован и прописаны его константы');

	if(APP_FIRST_LOAD) {
		//обновление даты посещения приложения сотрудником
		$sql = "UPDATE `_vkuser`
				SET `last_seen`=CURRENT_TIMESTAMP
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".VIEWER_ID;
		query($sql);
		_debugLoad('Обновлено время посещения пользователя в Базе');
	}
}


function _ruleCache($i='all', $v='name') {//кеширование констант прав по умолчанию
	$key = CACHE_PREFIX.'rule_default';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					`key` `id`,
					`name`,
					`value_admin`,
					`value_worker`
				FROM `_vkuser_rule_default`
				ORDER BY `key`";
		$arr = query_arr($sql);
		foreach($arr as $k => $r)
			unset($arr[$k]['id']);
		xcache_set($key, $arr, 86400);
	}

	if($i == 'all')
		return $arr;

	//список констант через пробел
	if($i == 'const')
		return implode(' ', array_keys($arr));

	if(!isset($arr[$i]))
		return _cacheErr('неизвестная константа права', $v);

	if(!isset($arr[$i][$v]))
		return _cacheErr('неизвестный ключ права', $i);

	return $arr[$i][$v];
	
}
function _viewerRule($viewer_id=VIEWER_ID, $i=false) {
	// 1. Проверка на правильность внесённых прав в базе для выбранного пользователя
	// 2. Формирование констант прав, если это текущий пользователь
	// 3. Получение конкретной константы

	$key = CACHE_PREFIX.'viewer_rule_'.$viewer_id;
	if(!$rule = xcache_get($key)) {
		$sql = "SELECT `key`,`value`
				FROM `_vkuser_rule`
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$viewer_id."
				ORDER BY `key`";
		$rule = query_ass($sql);

		if($i === false)
			_debugLoad('Загружены права пользователя из Базы');

		xcache_set($key, $rule, 86400);
	} elseif($i === false)
		_debugLoad('Получены права пользоваеля из Кеша');

	if(_ruleCache('const') != implode(' ', array_keys($rule))) {
		$insert = array();
		$defQ = array();//обведение ключей в кавычки
		foreach(_ruleCache() as $k => $v) {
			$defQ[] = "'".$k."'";
			if(isset($rule[$k]))
				continue;
			$insert[] = "(
				".APP_ID.",
				".$viewer_id.",
				'".$k."',
				'".(_viewer($viewer_id, 'viewer_admin') ? $v['value_admin'] : $v['value_worker'])."'
			)";
		}
		if(!empty($insert)) {
			$sql = "INSERT INTO `_vkuser_rule` (
						`app_id`,
						`viewer_id`,
						`key`,
						`value`
					) VALUES ".implode(',', $insert);
			query($sql);
			_debugLoad('Добавлены новые переменные прав пользователя в Базу');
		}

		if(!empty($defQ)) {
			$sql = "DELETE FROM `_vkuser_rule`
					WHERE `app_id`=".APP_ID."
					  AND `viewer_id`=".$viewer_id."
					  AND `key` NOT IN (".implode(',', $defQ).")";
			query($sql);
			_debugLoad('Удалены лишние переменные прав пользователя из Базы');
		}
		xcache_unset($key);

		_debugLoad('Обновлены права пользователя');

		return _viewerRule($viewer_id, $i);
	}

	if(!defined('RULE_DEFINED')) {
		foreach($rule as $k => $v)
			define($k, $v);
		_debugLoad('Установлены константы прав пользователя');
		define('RULE_DEFINED', true);
	}

	return $i && isset($rule[$i]) ? $rule[$i] : $rule;
}

function _viewerMenuAccess($menu_id, $viewer_id=VIEWER_ID) {//права доступа к разделам меню
	if($viewer_id >= VIEWER_MAX)
		return 0;
	
	if(_viewer($viewer_id, 'viewer_admin'))
		return 1;

	if(_menuCache('norule', $menu_id))
		return 1;

	$key = CACHE_PREFIX.'viewer_menu_'.$viewer_id;
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT `menu_id`,1
				FROM `_menu_viewer`
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$viewer_id;
		$arr = query_ass($sql);
		xcache_set($key, $arr, 86400);
	}

	//установка доступа по умолчанию, если пусто
	if(empty($arr)) {
		$values = array();
		$sql = "SELECT * FROM `_menu` WHERE `viewer_access_default`";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q)) {
			$values[] = "(".
				APP_ID.",".
				$viewer_id.",".
				$r['id'].
			")";
		}

		$sql = "INSERT INTO `_menu_viewer` (
					`app_id`,
					`viewer_id`,
					`menu_id`
				) VALUES ".implode(',', $values);
		query($sql);

		xcache_unset($key);

		_debugLoad('Установлены права доступа в разделы по умолчанию');

		return _viewerMenuAccess($menu_id, $viewer_id);
	}
	
	return _num(@$arr[$menu_id]);
}