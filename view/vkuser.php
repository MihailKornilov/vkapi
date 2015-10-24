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
}//_viewer()
*/
function _viewer($viewer_id=VIEWER_ID, $i='') {//получение данных о пользовате из контакта
	if(is_array($viewer_id))
		return _viewerValToList($viewer_id);

	if(!_num($viewer_id))
		die('_viewer: '.$viewer_id.' is not correct.');

	$u = _viewerCache($viewer_id);
	$u = _viewerFormat($u);

	if($i && !isset($u[$i]))
		return 'viewer: неизвестный ключ <b>'.$i.'</b>';

	return $i ? $u[$i] : $u;
}//_viewer()
function _viewerCache($viewer_id=VIEWER_ID) {//получение данных пользователя из кеша
	$key = CACHE_PREFIX.'viewer_'.$viewer_id;
	$u = xcache_get($key);
	if(empty($u)) {
		$sql = "SELECT *
				FROM `_vkuser`
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$viewer_id;
		if(!$u = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			$u = _viewerUpdate($viewer_id);
		xcache_set($key, $u, 86400);
	}
	return $u;
}//_viewerCache()
function _viewerUpdate($viewer_id=VIEWER_ID) {//Обновление пользователя из Контакта
	if(LOCAL)
		die('Error: not load vk user <b>'.$viewer_id.'</b> in LOCAL version.');

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
	$id = query_value($sql, GLOBAL_MYSQL_CONNECT);

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
				".$u['sex'].",
				'".addslashes($u['photo'])."',
				".$u['country_id'].",
				'".addslashes($u['country_title'])."',
				".$u['city_id'].",
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
	query($sql, GLOBAL_MYSQL_CONNECT);

	return _viewerCache($viewer_id);
}//_viewerUpdate()
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
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		while($u = mysql_fetch_assoc($q)) {
			if(isset($viewer_ass[$u['viewer_id']]))
				foreach($viewer_ass[$u['viewer_id']] as $id)
					$arr[$id] += _viewerFormat($u);

			if(isset($worker_ass[$u['viewer_id']]))
				foreach($worker_ass[$u['viewer_id']] as $id) {
					$w = _viewerFormat($u);
					$arr[$id] += array(
						'worker_name' => $w['viewer_name'],
						'worker_link' => '<a href="'.URL.'&p=setup&d=worker&id='.$u['viewer_id'].'">'.$w['viewer_name'].'</a>'
					);
				}
		}
	}

	return $arr;
}//_viewerValToList()
function _viewerFormat($u) {//формирование данных пользователя
	$send = array(
		'viewer_ws_id' => $u['ws_id'],
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

		'pin' => $u['pin']
	);

	$send['viewer_link'] = '<a href="//vk.com/id'.$u['viewer_id'].'" target="_blank">'.$send['viewer_name'].'</a>';
	$send['viewer_link_photo'] = '<a href="//vk.com/id'.$u['viewer_id'].'" target="_blank">'.$send['viewer_photo'].'</a>';
	$send['viewer_link_zp'] = '<a href="'.URL.'&p=report&d=salary&id='.$u['viewer_id'].'">'.$send['viewer_name'].'</a>';//страница с зарплатой

	return $send;
}//_viewerFormat()

function _viewerAdded($viewer_id) {//Вывод сотрудника, который вносил запись с учётом пола
	return 'вн'.(_viewer($viewer_id, 'sex') == 1 ? 'есла' : 'ёс').' '._viewer($viewer_id, 'viewer_name');
}//_viewerAdded();
function _viewerDeleted($viewer_id) {//Вывод сотрудника, который вносил запись с учётом пола
	return 'удалил'.(_viewer($viewer_id, 'sex') == 1 ? 'а' : '').' '._viewer($viewer_id, 'viewer_name');
}//_viewerDeleted();



function _getVkUser() {//Получение данных о пользователе при запуске приложения
	$u = _viewer();

	define('WS_ID', $u['viewer_ws_id']);
	define('VIEWER_NAME', $u['viewer_name']);
	define('VIEWER_ADMIN', $u['viewer_admin']);
	define('VIEWER_WORKER', $u['viewer_worker']);
	define('VIEWER_COUNTRY_ID', $u['viewer_country_id']);
	define('VIEWER_CITY_ID', $u['viewer_city_id']);

	define('PIN', !empty($u['pin']));
	define('PIN_TIME_KEY', APP_ID.'pin_time_'.VIEWER_ID);
	define('PIN_TIME_LEN', 3600); // длительность в секундах действия пинкода
	define('PIN_TIME', empty($_SESSION[PIN_TIME_KEY]) ? 0 : $_SESSION[PIN_TIME_KEY]);
	define('PIN_ENTER', PIN && APP_FIRST_LOAD || PIN && (PIN_TIME - time() < 0));//требуется ли ввод пин-кода

	_viewerRule();//формирование констант прав

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
}//_getVkUser()


/*
	$rules = array(
		'RULES_NOSALARY' => array(	// Не отображать в начислениях з/п
			'def' => 0
		),
		'RULES_ZPZAYAVAUTO' => array(	// Начислять бонус по заявке при отсутствии долга
			'def' => 0
		),
		'RULES_APPENTER' => array(	// Разрешать вход в приложение
			'def' => 0,
			'admin' => 1,
			'childs' => array(
				'RULES_WORKER' => array(	// Сотрудники
					'def' => 0,
					'admin' => 1
				),
				'RULES_RULES' => array(	    // Настройка прав сотрудников
					'def' => 0,
					'admin' => 1
				),
				'RULES_REKVISIT' => array(	// Реквизиты организации
					'def' => 0,
					'admin' => 1
				),
				'RULES_PRODUCT' => array(	// Виды изделий
					'def' => 0,
					'admin' => 1
				),
				'RULES_INVOICE' => array(	// Счета
					'def' => 0,
					'admin' => 1
				),
				'RULES_ZAYAVRASHOD' => array(// Расходы по заявке
					'def' => 0,
					'admin' => 1
				),
				'RULES_HISTORYSHOW' => array(// Видит историю действий
					'def' => 0,
					'admin' => 1
				),
				'RULES_MONEY' => array(	    // Может видеть платежи: только свои, все платежи
					'def' => 0,
					'admin' => 1
				)
			)
		)
	);
	$ass = array();
	foreach($rules as $i => $r) {
		$ass[$i] = $admin && isset($r['admin']) ? $r['admin'] : (isset($rls[$i]) ? $rls[$i] : $r['def']);
		//$parent = $ass[$i];
		if(isset($r['childs']))
			foreach($r['childs'] as $ci => $cr)
				$ass[$ci] = $admin && isset($cr['admin']) ? $cr['admin'] : (isset($rls[$ci]) ? $rls[$ci] : $cr['def']);
	}
	return $ass;
*/
function _viewerRuleDefault($viewer_id=VIEWER_ID) {
	// получение всех возможных значений прав пользователя (по умолчанию),
	// с учётом того, является пользователь админом или сотрудником

	// получение значений прав для руководителя
	$key = CACHE_PREFIX.'viewer_rule_default_admin';
	$rule_admin = xcache_get($key);
	if(empty($rule_admin)) {
		$sql = "SELECT `key`,`value_admin` FROM `_vkuser_rule_default` ORDER BY `key`";
		$rule_admin = query_ass($sql, GLOBAL_MYSQL_CONNECT);
		xcache_set($key, $rule_admin, 86400);
	}

	// получение значений прав для сотрудников
	$key = CACHE_PREFIX.'viewer_rule_default_worker';
	$rule_worker = xcache_get($key);
	if(empty($rule_worker)) {
		$sql = "SELECT `key`,`value_worker` FROM `_vkuser_rule_default` ORDER BY `key`";
		$rule_worker = query_ass($sql, GLOBAL_MYSQL_CONNECT);
		xcache_set($key, $rule_worker, 86400);
	}

	return _viewer($viewer_id, 'viewer_admin') ? $rule_admin : $rule_worker;
}//_viewerRuleDefault()
function _viewerRule($viewer_id=VIEWER_ID, $i=false) {
	// 1. Проверка на правильность внесённых прав в базе для выбранного пользователя
	// 2. Формирование констант прав, если это текущий пользователь
	// 3. Получение конкретной константы

	if($viewer_id >= VIEWER_MAX)
		return false;

	$key = CACHE_PREFIX.'viewer_rule_'.$viewer_id;
	$rule = xcache_get($key);
	if(empty($rule)) {
		$sql = "SELECT `key`,`value`
				FROM `_vkuser_rule`
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$viewer_id."
				ORDER BY `key`";
		$rule = query_ass($sql, GLOBAL_MYSQL_CONNECT);
		xcache_set($key, $rule, 86400);
	}

	$def = _viewerRuleDefault($viewer_id);
	$defKey = implode(' ', array_keys($def));
	$ruleKey = implode(' ', array_keys($rule));
	if($defKey != $ruleKey) {
		$insert = array();
		$defQ = array();//обведение ключей в кавычки
		foreach($def as $k => $value) {
			$defQ[] = "'".$k."'";
			if(isset($rule[$k]))
				continue;
			$insert[] = "(".APP_ID.",".$viewer_id.",'".$k."','".$value."')";
		}
		if(!empty($insert)) {
			$sql = "INSERT INTO `_vkuser_rule` (
						`app_id`,`viewer_id`,`key`,`value`
					) VALUES ".implode(',', $insert);
			query($sql, GLOBAL_MYSQL_CONNECT);
		}

		if(!empty($defQ)) {
			$sql = "DELETE FROM `_vkuser_rule`
				WHERE `app_id`=".APP_ID."
				  AND `viewer_id`=".$viewer_id."
				  AND `key` NOT IN (".implode(',', $defQ).")";
			query($sql, GLOBAL_MYSQL_CONNECT);
		}
		xcache_unset($key);
		return _viewerRule($viewer_id, $i);
	}

	if(!defined('RULE_DEFINED')) {
		foreach($rule as $k => $v)
			define($k, $v);
		define('RULE_DEFINED', true);
	}

	return $i && isset($rule[$i]) ? $rule[$i] : $rule;
}//_viewerRule()
