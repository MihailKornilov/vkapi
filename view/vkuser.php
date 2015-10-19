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
	if(!_num($viewer_id))
		die('_viewer: '.$viewer_id.' is not correct.');

	$u = _viewerCache($viewer_id);
	$u = _viewerFormat($u);

	return $i && isset($u[$i]) ? $u[$i] : $u;
}//_viewer()
function _viewerCache($viewer_id=VIEWER_ID) {//получение данных пользовател€ из кеша
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
function _viewerUpdate($viewer_id=VIEWER_ID) {//ќбновление пользовател€ из  онтакта
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
function _viewerValToList($arr) {//вставка данных о пользовател€х контакта в массив по viewer_id_add
	$viewer_ids = array(); //—бор id пользователей
	$ass = array();        //ѕрисвоение каждому id пользовател€ списка элементов, которые относ€тс€ к нему
	foreach($arr as $r) {
		$viewer_ids[$r['viewer_id_add']] = 1;
		$ass[$r['viewer_id_add']][] = $r['id'];
	}
	unset($viewer_ids[0]);
	if(!empty($viewer_ids)) {
		$sql = "SELECT *
				FROM `_vkuser`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `viewer_id` IN (".implode(',', array_keys($viewer_ids)).")";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		while($u = mysql_fetch_assoc($q))
			foreach($ass[$u['viewer_id']] as $id)
				$arr[$id] += _viewerFormat($u);
	}
	return $arr;
}//_viewerValToList()
function _viewerFormat($u) {//формирование данных пользовател€
	$send = array(
		'viewer_ws_id' => $u['ws_id'],
		'viewer_name' => $u['first_name'].' '.$u['last_name'],
		'viewer_name_full' => $u['last_name'].' '.$u['first_name'].(!empty($u['middle_name']) ? ' '.$u['middle_name'] : ''),
		//‘амили€ ».ќ.
		'viewer_name_init' =>
			$u['last_name'].
			($u['first_name'] ? ' '.strtoupper($u['first_name'][0]).'.' : '').
			(!empty($u['middle_name']) ? ' '.strtoupper($u['middle_name'][0]).'.' : ''),

		'viewer_sex' => $u['sex'],

		'viewer_photo' => '<img src="'.$u['photo'].'" />',

		'viewer_admin' => $u['admin'],
		'viewer_worker' => $u['worker'],

		'viewer_country_id' => $u['country_id'],
		'viewer_city_id' => $u['city_id'],

		'pin' => $u['pin']
	);

	$send['viewer_link'] = '<a href="//vk.com/id'.$u['viewer_id'].'" target="_blank">'.$send['viewer_name'].'</a>';
	$send['viewer_photo_link'] = '<a href="//vk.com/id'.$u['viewer_id'].'" target="_blank">'.$send['viewer_photo'].'</a>';

	return $send;
}//_viewerFormat()




function _getVkUser() {//ѕолучение данных о пользователе при запуске приложени€
	$u = _viewer();

	define('WS_ID', $u['viewer_ws_id']);
	define('VIEWER_NAME', $u['viewer_name']);
	define('VIEWER_ADMIN', $u['viewer_admin']);
	define('VIEWER_WORKER', $u['viewer_worker']);
	define('VIEWER_COUNTRY_ID', $u['viewer_country_id']);
	define('VIEWER_CITY_ID', $u['viewer_city_id']);

	define('PIN', !empty($u['pin']));
	define('PIN_TIME_KEY', APP_ID.'pin_time_'.VIEWER_ID);
	define('PIN_TIME_LEN', 5); // длительность в секундах действи€ пинкода
	define('PIN_TIME', empty($_SESSION[PIN_TIME_KEY]) ? 0 : $_SESSION[PIN_TIME_KEY]);
	define('PIN_ENTER', PIN && APP_FIRST_LOAD || PIN && (PIN_TIME - time() < 0));//требуетс€ ли ввод пин-кода

/*
					if(APP_FIRST_LOAD) { //учЄт посещений
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



function _pinCheck() {//вывод страницы с вводом пин-кода, если это требуетс€
	if(AJAX)
		return;
	if(!PIN_ENTER) {
		$_SESSION[PIN_TIME_KEY] = time() + PIN_TIME_LEN;
		return;
	}

	unset($_SESSION[PIN_TIME_KEY]);

	global $html;

	_header();

	$html .=
		'<div id="pin-enter">'.
			'ѕин: '.
			'<input type="password" id="pin" maxlength="10"> '.
			'<div class="vkButton"><button>Ok</button></div>'.
			'<div class="red">&nbsp;</div>'.
		'</div>';

	_footer();
	mysql_close();
	die($html);
}//_pinCheck()
