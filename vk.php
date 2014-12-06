<?php
/*
CREATE TABLE IF NOT EXISTS `vk_comment` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `table_name` varchar(20) DEFAULT '',
  `table_id` int unsigned DEFAULT 0,
  `parent_id` int unsigned DEFAULT 0,
  `txt` text,
  `status` tinyint unsigned DEFAULT 1,
  `viewer_id_add` int unsigned DEFAULT 0,
  `dtime_add` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `viewer_id_del` int unsigned DEFAULT 0,
  `dtime_del` datetime DEFAULT '0000-00-00 00:00:00',
  `child_del` text,
  KEY `i_table_id` (`table_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=cp1251;

CREATE TABLE IF NOT EXISTS `vk_user` (
  `viewer_id` int unsigned NOT NULL,
  PRIMARY KEY (`viewer_id`)
  `first_name` varchar(30) DEFAULT '',
  `last_name` varchar(30) DEFAULT '',
  `sex` tinyint(3) unsigned DEFAULT '0',
  `photo` varchar(300) DEFAULT '',
  `country_id` int unsigned DEFAULT 0,
  `country_name` varchar(100) DEFAULT '',
  `city_id` int unsigned DEFAULT 0,
  `city_name` varchar(100) DEFAULT '',
  `is_app_user` tinyint unsigned DEFAULT 0,
  `rule_menu_left` tinyint unsigned DEFAULT 0,
  `rule_notify` tinyint unsigned DEFAULT 0,
  `admin` tinyint unsigned DEFAULT 0,
  `enter_last` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `dtime_add` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

CREATE TABLE IF NOT EXISTS `pagehelp` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `page` varchar(50) DEFAULT '',
  `name` varchar(200) DEFAULT '',
  `txt` text default NULL,
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

Необходимые константы:
	NAMES
	API_ID
	SECRET
	SA
	VIEWER_ID
	VIEWER_ADMIN
	VALUES
*/

define('TIME', microtime(true));

setlocale(LC_ALL, 'ru_RU.CP1251');
setlocale(LC_NUMERIC, 'en_US');

define('REGEXP_NUMERIC', '/^[0-9]{1,20}$/i');
define('REGEXP_INTEGER', '/^-?[0-9]{1,20}$/i');
define('REGEXP_CENA', '/^[0-9]{1,10}(.[0-9]{1,2})?(,[0-9]{1,2})?$/i');
define('REGEXP_BOOL', '/^[0-1]$/');
define('REGEXP_DATE', '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/');
define('REGEXP_YEAR', '/^[0-9]{4}$/');
define('REGEXP_YEARMONTH', '/^[0-9]{4}-[0-9]{2}$/');
define('REGEXP_WORD', '/^[a-z0-9]{1,20}$/i');
define('REGEXP_MYSQLTABLE', '/^[a-z0-9_]{1,30}$/i');
define('REGEXP_WORDFIND', '/^[a-zA-Zа-яА-Я0-9,\.; ]{1,}$/i');

define('DOMAIN', $_SERVER['SERVER_NAME']);
define('LOCAL', DOMAIN != 'nyandoma.ru');
define('APP_START', !empty($_GET['referrer'])); //первый запуск приложения
define('VIEWER_ID', empty($_GET['viewer_id']) ? 0 : $_GET['viewer_id']);
define('VALUES', TIME.
				 '&viewer_id='.VIEWER_ID.
				 '&auth_key='.@$_GET['auth_key'].
				 '&access_token='.@$_GET['access_token']);
define('URL', APP_HTML.'/index.php?'.VALUES);

define('AJAX_MAIN', APP_HTML.'/ajax/main.php?'.VALUES);

if(!defined('CRON'))
	define('CRON', 0);

define('VIEWER_MAX', 2147000001);
define('CRON_MAIL', 'mihan_k@mail.ru');

$SA[982006] = 1; // Корнилов Михаил
define('SA', isset($SA[$_GET['viewer_id']]));

if(SA || CRON) {
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	ini_set('display_startup_errors', true);
}

if(!CRON) //Включает работу куков в IE через фрейм
	header('P3P: CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');

define('DEBUG', !empty($_COOKIE['debug']));

function _pre($v) {
	echo '<pre>';
	print_r($v);
	echo '</pre>';
}
function _api_scripts() {//скрипты и стили, которые вставляются в html
	$test = defined('TEST') ? 'test' : '';
	return
		//Отслеживание ошибок в скриптах
		(SA ? '<script type="text/javascript" src="/.vkapp/.js/errors.js?'.VERSION.'"></script>' : '').

		//Стороние скрипты
		'<script type="text/javascript" src="/.vkapp/.js/jquery-2.0.3.min.js"></script>'.
		'<script type="text/javascript" src="/.vkapp/.api/xd_connection.min.js?20"></script>'.

		//Установка начального значения таймера.
		(SA ? '<script type="text/javascript">var TIME=(new Date()).getTime();</script>' : '').

		//Установка стандартных значений для JS
		'<script type="text/javascript">'.
			(LOCAL ? 'for(var i in VK)if(typeof VK[i]=="function")VK[i]=function(){return false};' : '').
			'var VIEWER_ID='.VIEWER_ID.','.
				'APP_HTML="'.APP_HTML.'",'.
				'VALUES="'.VALUES.'";'.
		'</script>'.

		//Подключение api VK. Стили VK должны стоять до основных стилей сайта
		'<link rel="stylesheet" type="text/css" href="/.vkapp/.api/vk'.(DEBUG ? '' : '.min').'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="/.vkapp/.api/vk'.(DEBUG ? '' : '.min').'.js?'.VERSION.'"></script>';
}//_api_scripts()

function _debug() {
	if(!SA)
		return '';

	global $sqlQuery, $sqlTime;

	$pre = '&pre_p='.@$_GET['p'].
			(empty($_GET['d']) ? '' : '&pre_d='.$_GET['d']).
			(empty($_GET['d1']) ? '' : '&pre_d1='.$_GET['d1']).
			(empty($_GET['id']) ? '' : '&pre_id='.$_GET['id']);

	$send =
		'<div id="admin">'.
			(@$_GET['p'] != 'sa' ? '<a href="'.URL.'&p=sa'.$pre.'">SA</a> :: ' : '').
			'<a class="debug_toggle'.(DEBUG ? ' on' : '').'">'.(DEBUG ? 'От' : 'В').'ключить Debug</a> :: '.
			'<a id="cookie_clear">Очисить cookie</a> :: '.
			'<a id="cache_clear">Очисить кэш ('.VERSION.')</a> :: '.
			'<a href="http://'.DOMAIN.APP_HTML.'/_sxdump" target="_blank">sxd</a> :: '.
			'sql <b>'.count($sqlQuery).'</b> ('.round($sqlTime, 3).') :: '.
			'php '.round(microtime(true) - TIME, 3).' :: '.
			'js <em></em>'.
		'</div>';
	if(DEBUG) {
		$get = '';
		ksort($_GET);
		foreach($_GET as $i => $v)
			$get .= '<b>'.$i.'</b>='.$v.'<br />';
		$get .= '<textarea>http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'</textarea>';

		$send .=
		'<div id="_debug"'.(empty($_COOKIE['debug_show']) ? '' : ' class="show"').'>'.
			'<h1>+</h1>'.
			'<h2><div class="dmenu">'.
					'<a'.(empty($_COOKIE['debug_pg']) || $_COOKIE['debug_pg'] == 'sql' ? ' class="sel"' : '').' val="sql">sql <b>'.count($sqlQuery).'</b> ('.round($sqlTime, 3).')</a>'.
					'<a'.(@$_COOKIE['debug_pg'] == 'cookie' ? ' class="sel"' : '').' val="cookie">cookie <b>'._debug_cookie_count().'</b></a>'.
					'<a'.(@$_COOKIE['debug_pg'] == 'get' ? ' class="sel"' : '').' val="get">$_GET</a>'.
					'<a'.(@$_COOKIE['debug_pg'] == 'ajax' ? ' class="sel"' : '').' val="ajax">ajax</a>'.
				'</div>'.
				'<ul class="pg sql'.(empty($_COOKIE['debug_pg']) || $_COOKIE['debug_pg'] == 'sql' ? '' : ' dn').'">'.implode('', $sqlQuery).'</ul>'.
				'<div class="pg cookie'.(@$_COOKIE['debug_pg'] == 'cookie' ? '' : ' dn').'">'.
					'<a id="cookie_update">Обновить</a>'.
					'<div id="cookie_spisok">'._debug_cookie().'</div>'.
				'</div>'.
				'<div class="pg get'.(@$_COOKIE['debug_pg'] == 'get' ? '' : ' dn').'">'.$get.'</div>'.
				'<div class="pg ajax'.(@$_COOKIE['debug_pg'] == 'ajax' ? '' : ' dn').'">&nbsp;</div>'.
			'</h2>'.
		'</div>';
	}
	return $send;
}//_debug()
function _debug_cookie_count() {
	$count = 0;
	if(!empty($_COOKIE))
		foreach($_COOKIE as $key => $val)
			if(strpos($key, 'debug') !== 0)
				$count++;
	return $count ? $count : '';
}
function _debug_cookie() {
	$cookie = '';
	if(!empty($_COOKIE))
		foreach($_COOKIE as $key => $val)
			if(strpos($key, 'debug') !== 0)
				$cookie .= '<p><b>'.$key.'</b> '.$val;
	return $cookie;
}
function _footer() {
	global $html;
	$getArr = array(
		'start' => 1,
		'api_url' => 1,
		'api_id' => 1,
		'api_settings' => 1,
		'viewer_id' => 1,
		'viewer_type' => 1,
		'sid' => 1,
		'secret' => 1,
		'access_token' => 1,
		'user_id' => 1,
		'group_id' => 1,
		'is_app_user' => 1,
		'auth_key' => 1,
		'language' => 1,
		'parent_language' => 1,
		'ad_info' => 1,
		'is_secure' => 1,
		'referrer' => 1,
		'lc_name' => 1,
		'hash' => 1
	);
	$v = array();
	foreach($_GET as $k => $val) {
		if(isset($getArr[$k]) || empty($_GET[$k]))
			continue;
		$v[] = '"'.$k.'":"'.$val.'"';
	}
	$html .= _debug().
			 '<script type="text/javascript">hashSet({'.implode(',', $v).'});</script>'.
		'</div></body></html>';
}//_footer()

function _vkapi($method, $param=array()) {
	$param += array(
		'v' => 5.21,
		'lang' => 'ru',
		'access_token' => isset($param['access_token']) ? $param['access_token'] : @$_GET['access_token']
	);

	$url = 'https://api.vk.com/method/'.$method.'?'.http_build_query($param);
	$res = file_get_contents($url);
	$res = json_decode($res, true);
	if(SA && DEBUG)
		$res['url'] = $url;
	return $res;
}

function jsonError($values=null) {
	$send['error'] = 1;
	if(empty($values))
		$send['text'] = utf8('Произошла неизвестная ошибка.');
	elseif(is_array($values))
		$send += $values;
	else
		$send['text'] = utf8($values);
	die(json_encode($send));
}//jsonError()
function jsonSuccess($send=array()) {
	$send['success'] = 1;
	if(SA && DEBUG) {
		global $sqlQuery, $sqlTime;
		$send['post'] = $_POST;
		$send['link'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		$send['php_time'] = round(microtime(true) - TIME, 3);
		$send['sql_count'] = count($sqlQuery);
		$send['sql_time'] = round($sqlTime, 3);
		$send['sql'] = utf8(implode('', $sqlQuery));
	}
	die(json_encode($send));
}//jsonSuccess()

function _appAuth() {
	if(LOCAL || CRON)
		return;
	if(@$_GET['auth_key'] != md5(API_ID.'_'.VIEWER_ID.'_'.SECRET))
		die('Ошибка авторизации. Попробуйте снова: <a href="//vk.com/app'.API_ID.'">vk.com/app'.API_ID.'</a>.');
}//_appAuth()
function _noauth($msg='Недостаточно прав.') {
	return '<div class="noauth"><div>'.$msg.'</div></div>';
}//_noauth()

function _dbConnect() {
	global $mysql, $sqlQuery;
	$dbConnect = mysql_connect($mysql['host'], $mysql['user'], $mysql['pass'], 1) or die("Can't connect to database");
	mysql_select_db($mysql['database'], $dbConnect) or die("Can't select database");
	$sqlQuery = array();
	query('SET NAMES `'.NAMES.'`', $dbConnect);
}//_dbConnect()
function query($sql, $debug=0) {
	global $sqlQuery, $sqlTime;
	$t = microtime(true);
	$res = mysql_query($sql) or die($sql.'<br />'.mysql_error());
	$t = microtime(true) - $t;
	if($debug)
		return array(
			'time' => round($t, 3),
			'res' => $res
			//'rows' => mysql_num_rows($res)
		);
	$sqlTime += $t;
	$t = round($t, 3);
	$sqlQuery[] = '<li><a class="sql-un">'.trim(str_replace ('	', '',  $sql)).'</a><b class="t'.($t > 0.05 ? ' long' : '').'">'.$t.'</b>';
	if(mysql_insert_id() && strpos(strtoupper($sql), 'INSERT INTO') !== false)
		return mysql_insert_id();
	return $res;
}//query()
function query_value($sql) {
	if(!$r = mysql_fetch_row(query($sql)))
		return false;
	return $r[0];
}//query_value()
function query_assoc($sql) {
	if(!$r = mysql_fetch_assoc(query($sql)))
		return array();
	return $r;
}//query_assoc()
function query_ass($sql) {//Ассоциативный массив
	$send = array();
	$q = query($sql);
	while($r = mysql_fetch_row($q))
		$send[$r[0]] = $r[1];
	return $send;
}//query_ass()
function query_selJson($sql) {
	$send = array();
	$q = query($sql);
	while($sp = mysql_fetch_row($q))
		$send[] = '{uid:'.$sp[0].',title:"'.addslashes(htmlspecialchars_decode($sp[1])).'"}';
	return '['.implode(',',$send).']';
}//query_selJson()
function query_ptpJson($sql) {//Ассоциативный массив
	$q = query($sql);
	$send = array();
	while($sp = mysql_fetch_row($q))
		$send[] = $sp[0].':'.(preg_match(REGEXP_NUMERIC, $sp[1]) ? $sp[1] : '"'.$sp[1].'"');
	return '{'.implode(',', $send).'}';
}//query_ptpJson()
function query_ids($sql) {//Список идентификаторов
	$q = query($sql);
	$send = array();
	while($sp = mysql_fetch_row($q))
		$send[] = $sp[0];
	return empty($send) ? 0 : implode(',', $send);
}//query_ids()

function _isnum($v) {//проверка на целое число
	if(empty($v) || is_array($v) || !preg_match(REGEXP_NUMERIC, $v))
		return 0;
	return intval($v);
}//_isnum()
function _isbool($v) {//проверка на булево число
	if(empty($v) || is_array($v) || !preg_match(REGEXP_BOOL, $v))
		return 0;
	return intval($v);
}//_isbool()
function _cena($v) {//проверка на цену
	if(empty($v) || is_array($v) || !preg_match(REGEXP_CENA, $v))
		return 0;
	$v = str_replace(',', '.', $v);
	return round($v, 2);
}//_cena()

function _maxSql($table, $pole='sort') {
	return query_value("SELECT IFNULL(MAX(`".$pole."`)+1,1) FROM `".$table."`");
}//getMaxSql()

function _selJson($arr) {
	$send = array();
	foreach($arr as $uid => $title) {
		$content = '';
		if(is_array($title)) {
			$r = $title;
			$title = $r['title'];
			$content = isset($r['content']) ? $r['content'] : '';
		}
		$send[] = '{'.
			'uid:'.$uid.','.
			'title:"'.addslashes($title).'"'.
			($content ? ',content:"'.addslashes($content).'"' : '').
		'}';
	}
	return '['.implode(',',$send).']';
}//_selJson()
function _assJson($arr) {//Ассоциативный массив
	foreach($arr as $id => $v)
		$send[] =
			(preg_match(REGEXP_NUMERIC, $id) ? $id : '"'.$id.'"').
			':'.
			(preg_match(REGEXP_CENA, $v) ? $v : '"'.$v.'"');
	return '{'.implode(',', $send).'}';
}//_assJson()

function pageHelpIcon() {
	$page[] = $_GET['p'];
	if(!empty($_GET['d']))
		$page[] = $_GET['d'];
	if(!empty($_GET['d1']))
		$page[] = $_GET['d1'];
	if(!empty($_GET['id']))
		$page[] = 'id';
	$page = implode('_', $page);
	$id = query_value("SELECT `id` FROM `pagehelp` WHERE `page`='".$page."' LIMIT 1");
	return
		($id ? '<div class="img_pagehelp" val="'.$id.'"></div>' : '').
		(SA && !$id ? '<div class="pagehelp_create" val="'.$page.'">Добавить подсказку</div>' : '');
}

function _check($id, $txt='', $v=0, $light=false) {
	$v = $v ? 1 : 0;
	return
	'<div class="_check check'.$v.($light ? ' l' : '').($txt ? '' : ' e').'" id="'.$id.'_check">'.
		'<input type="hidden" id="'.$id.'" value="'.$v.'" />'.
		$txt.
	'</div>';
}//_check()
function _radio($id, $list, $value=0, $light=false) {
	$spisok = '';
	foreach($list as $uid => $title) {
		$sel = $uid == $value ? 'on' : 'off';
		$l = $light ? ' l' : '';
		$spisok .= '<div class="'.$sel.$l.'" val="'.$uid.'"><s></s>'.$title.'</div>';
	}
	return
	'<div class="_radio" id="'.$id.'_radio">'.
		'<input type="hidden" id="'.$id.'" value="'.$value.'">'.
		$spisok.
	'</div>';
}//_radio()

function _end($count, $o1, $o2, $o5=false) {
	if($o5 === false) $o5 = $o2;
	if($count / 10 % 10 == 1)
		return $o5;
	else
		switch($count % 10) {
			case 1: return $o1;
			case 2: return $o2;
			case 3: return $o2;
			case 4: return $o2;
		}
	return $o5;
}//_end()

function _sumSpace($sum) {//Приведение суммы к удобному виду с пробелами
	$znak = $sum < 0 ? -1 : 1;
	$sum *= $znak;
	$send = '';
	$floor = floor($sum);
	$drob = round($sum - $floor, 2) * 100;
	while($floor > 0) {
		$del = $floor % 1000;
		$floor = floor($floor / 1000);
		if(!$del) $send = ' 000'.$send;
		elseif($del < 10) $send = ($floor ? ' 00' : '').$del.$send;
		elseif($del < 100) $send = ($floor ? ' 0' : '').$del.$send;
		else $send = ' '.$del.$send;
	}
	$send = $send ? trim($send) : 0;
	$send = $drob ? $send.'.'.($drob < 10 ? 0 : '').$drob : $send;
	return ($znak < 0 ? '-' : '').$send;
}//_sumSpace()

function _tooltip($msg, $left=0, $ugolSide='') {
	return
		' _tooltip">'.
		'<div class="ttdiv"'.($left ? ' style="left:'.$left.'px"' : '').'>'.
			'<div class="ttmsg">'.$msg.'</div>'.
			'<div class="ttug'.($ugolSide ? ' '.$ugolSide : '').'"></div>'.
		'</div>';
}//_tooltip()

function win1251($txt) { return iconv('UTF-8', 'WINDOWS-1251//TRANSLIT', $txt); }
function utf8($txt) { return iconv('WINDOWS-1251', 'UTF-8', $txt); }
function curTime() { return strftime('%Y-%m-%d %H:%M:%S'); }

function _rightLink($id, $spisok, $val=0) {
	$a = '';
	foreach($spisok as $uid => $title)
		$a .= '<a'.($val == $uid ? ' class="sel"' : '').' val="'.$uid.'">'.$title.'</a>';
	return
	'<div class="rightLink" id="'.$id.'_rightLink">'.
		'<input type="hidden" id="'.$id.'" value="'.$val.'">'.
		$a.
	'</div>';
}//_rightLink()

function _viewer($viewer_id=VIEWER_ID, $val=false) {
	if(is_array($viewer_id))
		return _viewerArray($viewer_id);

	if(!_isnum($viewer_id))
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

	if(APP_START && $viewer_id == VIEWER_ID && !defined('ENTER_LAST_UPDATE')) {
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
function _viewerUpdate($viewer_id=VIEWER_ID) {//Обновление пользователя из Контакта
	$res = _vkapi('users.get', array(
		'user_ids' => $viewer_id,
		'access_token' => '',
		'fields' => 'photo,'.
					'sex,'.
					'country,'.
					'city'
	));

	if(empty($res['response'])) {
		print_r($res);
		die('Do not get user from VK: '.$viewer_id);
	}
	$res = $res['response'][0];
	$u = array(
		'viewer_id' => $viewer_id,
		'first_name' => win1251($res['first_name']),
		'last_name' => win1251($res['last_name']),
		'sex' => $res['sex'],
		'photo' => $res['photo'],
		'country_id' => empty($res['country']) ? 0 : $res['country']['id'],
		'country_name' => empty($res['country']) ? '' : win1251($res['country']['title']),
		'city_id' => empty($res['city']) ? 0 : $res['city']['id'],
		'city_name' => empty($res['city']) ? '' : win1251($res['city']['title'])
	);

	$sql = "INSERT INTO `vk_user` (
				`viewer_id`,
				`first_name`,
				`last_name`,
				`sex`,
				`photo`,
				`country_id`,
				`country_name`,
				`city_id`,
				`city_name`
			) VALUES (
				".$viewer_id.",
				'".addslashes($u['first_name'])."',
				'".addslashes($u['last_name'])."',
				".$u['sex'].",
				'".addslashes($u['photo'])."',
				".$u['country_id'].",
				'".addslashes($u['country_name'])."',
				".$u['city_id'].",
				'".addslashes($u['city_name'])."'
			) ON DUPLICATE KEY UPDATE
				`first_name`=VALUES(`first_name`),
				`last_name`=VALUES(`last_name`),
				`sex`=VALUES(`sex`),
				`photo`=VALUES(`photo`),
				`country_id`=VALUES(`country_id`),
				`country_name`=VALUES(`country_name`),
				`city_id`=VALUES(`city_id`),
				`city_name`=VALUES(`city_name`)";
	query($sql);

	return query_assoc("SELECT * FROM `vk_user` WHERE `viewer_id`=".$viewer_id);
}//_viewerUpdate()
function _viewerArray($arr) {
	$viewer_ids = array();  //Сбор id пользователей
	$ass = array();         //Присвоение каждому id пользователя списка элементов, которые относятся к нему
	foreach($arr as $r) {
		$viewer_ids[$r['viewer_id_add']] = 1;
		$ass[$r['viewer_id_add']][] = $r['id'];
	}
	unset($viewer_ids[0]);
	if(!empty($viewer_ids)) {
		$sql = "SELECT * FROM `vk_user` WHERE `viewer_id` IN (".implode(',', array_keys($viewer_ids)).")";
		$q = query($sql);
		while($u = mysql_fetch_assoc($q))
			foreach($ass[$u['viewer_id']] as $id)
				$arr[$id] += _viewerFormat($u);
	}
	return $arr;
}//_viewerArray()
function _viewerFormat($u) {
	$u['id'] = $u['viewer_id'];
	$u['name'] = $u['first_name'].' '.$u['last_name'];
	$u['name_init'] = $u['last_name'].
					 ($u['first_name'] ? ' '.strtoupper($u['first_name'][0]).'.' : '').
					(!empty($u['middle_name']) ? ' '.strtoupper($u['middle_name'][0]).'.' : '');
	$u['name_full'] = $u['last_name'].' '.$u['first_name'].(!empty($u['middle_name']) ? ' '.$u['middle_name'] : '');
	$u['link'] = '<a href="//vk.com/id'.$u['viewer_id'].'" target="_blank">'.$u['name'].'</a>';
	$u['photo'] = '<img src="'.$u['photo'].'" />';
	$u['photo_link'] = '<a href="//vk.com/id'.$u['viewer_id'].'" target="_blank">'.$u['photo'].'</a>';
	$u['viewer_name'] = $u['name'];
	$u['viewer_link'] = $u['link'];
	$u['viewer_photo'] = $u['photo'];
	return $u;
}//_viewerFormat()


function _historyInsert($type, $v=array(), $table='history') {
	/*
	Поля, которые отличные от обязательных, также вносятся. Их тип строго integer.
	Необходимо не забывать, что имя таблицы может быть отличной.
	*/
	$keys = '';
	$values = '';
	foreach($v as $key => $value) {
		if($key == 'value' ||
		   $key == 'value1' ||
		   $key == 'value2' ||
		   $key == 'value3')
			continue;
		$keys .= '`'.$key.'`,';
		$values .= intval($value).',';
	}
	$sql = "INSERT INTO `".$table."` (
			   `type`,
			   ".$keys."
			   `value`,
			   `value1`,
			   `value2`,
			   `value3`,
			   `viewer_id_add`
			) VALUES (
				".$type.",
				".$values."
				'".(!empty($v['value']) ? addslashes($v['value']) : '')."',
				'".(!empty($v['value1']) ? addslashes($v['value1']) : '')."',
				'".(!empty($v['value2']) ? addslashes($v['value2']) : '')."',
				'".(!empty($v['value3']) ? addslashes($v['value3']) : '')."',
				".VIEWER_ID."
			)";
	query($sql);
}//_historyInsert()
function _historyFilter($v) {
	return array(
		'page' => !empty($v['page']) && _isnum($v['page']) ? intval($v['page']) : 1,
		'limit' => !empty($v['limit']) && _isnum($v['limit']) ? intval($v['limit']) : 30,
		'table' => !empty($v['table']) ? $v['table'] : 'history',
		'type' => !empty($v['type']) && _isnum($v['type']) ? intval($v['type']) : 0,
		'value' => !empty($v['value']) ? $v['value'] : '',
		'value1' => !empty($v['value1']) ? $v['value1'] : '',
		'value2' => !empty($v['value2']) ? $v['value2'] : '',
		'value3' => !empty($v['value3']) ? $v['value3'] : '',
		'viewer_id_add' => !empty($v['viewer_id_add']) && _isnum($v['viewer_id_add']) ? intval($v['viewer_id_add']) : 0,
		'action' => !empty($v['action']) && _isnum($v['action']) ? intval($v['action']) : 0
	);
}//_historyFilter()
function _history($types, $functions=array(), $v=array(), $filter_dop) {
	$filter = $filter_dop + _historyFilter($v);

	$filterNoUse = array(
		'page' => 1,
		'type' => 1,
		'value' => 1,
		'value1' => 1,
		'value2' => 1,
		'value3' => 1
	);

	$page = $filter['page'];
	$limit = $filter['limit'];
	$start = ($page - 1) * $limit;

	$spisok = '';
	$js = array();
	if($page == 1) {
		foreach($filter as $i => $r)
			if(empty($filterNoUse[$i]))
				$js[] = $i.':'.(preg_match(REGEXP_NUMERIC, $r) ? $r : '"'.$r.'"');
		$spisok = '<script type="text/javascript">var HIST={'.implode(',', $js).'};</script>';
	}

	$filterNoUse['limit'] = 1;
	$filterNoUse['action'] = 1;
	$filterNoUse['table'] = 1;
	$cond = "`id`";
	foreach($filter as $i => $r)
		if(empty($filterNoUse[$i]) && $r)
			$cond .= " AND `".$i."`=".$r;

	if($filter['action'] && function_exists('history_group'))
		$cond .= " AND `type` IN(".history_group($filter['action']).")";

	$sql = "SELECT COUNT(`id`) AS `all` FROM `".$filter['table']."` WHERE ".$cond;
	$all = query_value($sql);
	if(!$all)
		return array(
			'all' => 0,
			'result' => 'Истории по указанным условиям нет',
			'spisok' => $spisok.'<div class="_empty">Истории по указанным условиям нет</div>',
			'filter' => $filter
		);

	$send = array(
		'all' => $all,
		'result' => 'Показан'._end($all, 'а ', 'о ').$all.' запис'._end($all, 'ь', 'и', 'ей'),
		'spisok' => $spisok,
		'filter' => $filter
	);

	$sql = "SELECT *
			FROM `".$filter['table']."`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT ".$start.",".$limit;
	$q = query($sql);
	$history = array();
	while($r = mysql_fetch_assoc($q))
		$history[$r['id']] = $r;

	$history = _viewer($history);
	foreach($functions as $func)
		$history = $func($history);

	$txt = '';
	end($history);
	$keyEnd = key($history);
	reset($history);
	foreach($history as $r) {
		if(!$txt) {
			$time = strtotime($r['dtime_add']);
			$viewer_id = $r['viewer_id_add'];
		}
		$txt .= '<li>'.(SA ? '<h4>'.$r['type'].'</h4>' : '').
					   '<div class="li">'.$types($r, $filter).'</div>';
		$key = key($history);
		if(!$key ||
			$key == $keyEnd ||
			$time - strtotime($history[$key]['dtime_add']) > 900 ||
			$viewer_id != $history[$key]['viewer_id_add']) {
			$send['spisok'] .=
				'<div class="_hist-un">'.
					'<table><tr>'.
		  ($viewer_id ? '<td class="hist-img">'.$r['photo'] : '').
						'<td>'.
			  ($viewer_id ? '<h5>'.$r['viewer_name'].'</h5>' : '').
							'<h6>'.FullDataTime($r['dtime_add']).(!$viewer_id ? '<span>cron</span>' : '').'</h6>'.
					'</table>'.
					'<ul>'.$txt.'</ul>'.
				'</div>';
			$txt = '';
		}
		next($history);
	}
	if($start + $limit < $all) {
		$c = $all - $start - $limit;
		$c = $c > $limit ? $limit : $c;
		$send['spisok'] .=
			'<div class="_next" id="_hist-next" val="'.($page + 1).'">'.
				'<span>Показать ещё '.$c.' запис'._end($c, 'ь', 'и', 'ей').'</span>'.
			'</div>';
	}
	return $send;
}//_history_spisok()


function _vkComment($table, $id=0) {
	$sql = "SELECT *
			FROM `vk_comment`
			WHERE `status`=1
			  AND `table_name`='".$table."'
			  AND `table_id`=".intval($id)."
			ORDER BY `dtime_add` ASC";
	$count = 'Заметок нет';
	$units = '';
	$q = query($sql);
	if(mysql_num_rows($q)) {
		$v = array();
		while($r = mysql_fetch_assoc($q))
			$arr[$r['id']] = $r;
		$arr = _viewer($arr);

		$comm = array();
		foreach($arr as $r)
			if(!$r['parent_id'])
				$comm[$r['id']] = $r;
			elseif(isset($comm[$r['parent_id']]))
				$comm[$r['parent_id']]['childs'][] = $r;

		$count = count($comm);
		$count = 'Всего '.$count.' замет'._end($count, 'ка', 'ки','ок');
		$comm = array_reverse($comm);
		foreach($comm as $n => $r) {
			$childs = array();
			if(!empty($r['childs']))
				foreach($r['childs'] as $c)
					$childs[] = _vkCommentChild($c);
			$units .= _vkCommentUnit($r, $childs, ($n+1));
		}
	}
	return
	'<div class="vkComment" val="'.$table.'_'.$id.'">'.
		'<div class="hb"><div class="count">'.$count.'</div>Заметки</div>'.
		'<div class="add">'.
			'<textarea>Добавить заметку...</textarea>'.
			'<div class="vkButton"><button>Добавить</button></div>'.
		'</div>'.
		$units.
	'</div>';
}//_vkComment()
function _vkCommentUnit($r, $childs=array(), $n=0) {
	return
	'<div class="cunit" val="'.$r['id'].'">'.
		'<table class="t">'.
			'<tr><td class="ava">'.$r['viewer_photo'].
				'<td class="i">'.str_replace('a href', 'a class="vlink" href', $r['viewer_link']).
					($r['viewer_id_add'] == VIEWER_ID || VIEWER_ADMIN ? '<div class="img_del unit_del" title="Удалить заметку"></div>' : '').
					'<div class="ctxt">'.$r['txt'].'</div>'.
					'<div class="cdat">'.FullDataTime($r['dtime_add'], 1).
						'<SPAN'.($n == 1  && !empty($childs) ? ' class="hide"' : '').'> | '.
							'<a>'.(empty($childs) ? 'Комментировать' : 'Комментарии ('.count($childs).')').'</a>'.
						'</SPAN>'.
					'</div>'.
					'<div class="cdop'.(empty($childs) ? ' empty' : '').($n == 1 && !empty($childs) ? '' : ' hide').'">'.
						implode('', $childs).
						'<div class="cadd">'.
							'<textarea>Комментировать...</textarea>'.
							'<div class="vkButton"><button>Добавить</button></div>'.
						'</div>'.
					'</div>'.
		'</table>'.
	'</div>';
}//_vkCommentUnit()
function _vkCommentChild($r) {
	return
	'<div class="child" val="'.$r['id'].'">'.
		'<table class="t">'.
			'<tr><td class="dava">'.$r['viewer_photo'].
				'<td class="di">'.$r['viewer_link'].
					($r['viewer_id_add'] == VIEWER_ID || VIEWER_ADMIN ? '<div class="img_del child_del" title="Удалить комментарий"></div>' : '').
					'<div class="dtxt">'.$r['txt'].'</div>'.
					'<div class="ddat">'.FullDataTime($r['dtime_add'], 1).'</div>'.
		'</table>'.
	'</div>';
}//_vkCommentChild()
function _vkCommentAdd($table, $id, $txt) {
	if(empty($txt))
		return;
	$parent_id = 0;
	$sql = "SELECT `id`,`parent_id`
				FROM `vk_comment`
				WHERE `table_name`='".$table."'
				  AND `table_id`=".$id."
				  AND `status`=1
				ORDER BY `id` DESC
				LIMIT 1";
	if($r = mysql_fetch_assoc(query($sql)))
		$parent_id = $r['parent_id'] ? $r['parent_id'] : $r['id'];
	$sql = "INSERT INTO `vk_comment` (
					`table_name`,
					`table_id`,
					`txt`,
					`parent_id`,
					`viewer_id_add`
				) VALUES (
					'".$table."',
					".$id.",
					'".addslashes($txt)."',
					".$parent_id.",
					".VIEWER_ID."
				)";
	query($sql);

}//_vkCommentAdd()

function _monthFull($n=0) {
	$mon = array(
		1 => 'января',
		2 => 'февраля',
		3 => 'марта',
		4 => 'апреля',
		5 => 'мая',
		6 => 'июня',
		7 => 'июля',
		8 => 'августа',
		9 => 'сентября',
		10 => 'октября',
		11 => 'ноября',
		12 => 'декабря'
	);
	return $n ? $mon[intval($n)] : $mon;
}//_monthFull()
function _monthDef($n=0, $firstUp=false) {
	$mon = array(
		1 => 'январь',
		2 => 'февраль',
		3 => 'март',
		4 => 'апрель',
		5 => 'май',
		6 => 'июнь',
		7 => 'июль',
		8 => 'август',
		9 => 'сентябрь',
		10 => 'октябрь',
		11 => 'ноябрь',
		12 => 'декабрь'
	);
	if(!$n) {
		if($firstUp)
			foreach($mon as $k => $m)
				$mon[$k][0] = strtoupper($m);
		return $mon;
	}
	$send = $mon[intval($n)];
	if($firstUp)
		$send[0] = strtoupper($send[0]);
	return $send;
}//_monthFull()
function _monthCut($n) {
	$mon = array(
		0 => '',
		1 => 'янв',
		2 => 'фев',
		3 => 'мар',
		4 => 'апр',
		5 => 'май',
		6 => 'июн',
		7 => 'июл',
		8 => 'авг',
		9 => 'сен',
		10 => 'окт',
		11 => 'ноя',
		12 => 'дек'
	);
	return $mon[intval($n)];
}//_monthCut()
function _week($n) {
	$week = array(
		1 => 'пн',
		2 => 'вт',
		3 => 'ср',
		4 => 'чт',
		5 => 'пт',
		6 => 'сб',
		0 => 'вс'
	);
	return $week[intval($n)];
}//_week()
function FullData($v=0, $noyear=0, $cut=0, $week=0) {//пт. 14 апреля 2010
	if(!$v)
		$v = curTime();
	$d = explode('-', $v);
	return
		($week ? _week(date('w', strtotime($v))).'. ' : '').
		abs($d[2]).' '.
		($cut ? _monthCut($d[1]) : _monthFull($d[1])).
		(!$noyear || date('Y') != $d[0] ? ' '.$d[0] : '');
}//FullData()
function FullDataTime($v=0, $cut=0) {//14 апреля 2010 в 12:45
	if(!$v)
		$v = curTime();
	$arr = explode(' ', $v);
	$d = explode('-', $arr[0]);
	if(!intval($arr[0]) || empty($arr[1]) || empty($d[1]) || empty($d[2]))
		return '';
	$t = explode(':',$arr[1]);
	if(empty($t[1]) || empty($t[2]))
		return '';
	return
		abs($d[2]).' '.
		($cut ? _monthCut($d[1]) : _monthFull($d[1])).
		(date('Y') == $d[0] ? '' : ' '.$d[0]).
		' в '.$t[0].':'.$t[1];
}//FullDataTime()
function _curMonday() { //Понедельник в текущей неделе
	// Номер текущего дня недели
	$time = time();
	$curDay = date("w", $time);
	if($curDay == 0) $curDay = 7;
	// Приведение дня к понедельнику
	$time -= 86400 * ($curDay - 1);
	return strftime('%Y-%m-%d', $time);
}//_curMonday()
function _curSunday() { //Воскресенье в текущей неделе
	$time = time();
	$curDay = date("w", $time);
	if($curDay == 0) $curDay = 7;
	$time += 86400 * (7 - $curDay);
	return strftime('%Y-%m-%d', $time);

}//_curSunday()

function _engRusChar($word) { //Перевод символов раскладки с английского на русский
	$char = array(
		'`' => 'ё',
		'ё' => 'е',
		'q' => 'й',
		'w' => 'ц',
		'e' => 'у',
		'r' => 'к',
		't' => 'е',
		'y' => 'н',
		'u' => 'г',
		'i' => 'ш',
		'o' => 'щ',
		'p' => 'з',
		'[' => 'х',
		'{' => 'х',
		']' => 'ъ',
		'}' => 'ъ',
		'a' => 'ф',
		's' => 'ы',
		'd' => 'в',
		'f' => 'а',
		'g' => 'п',
		'h' => 'р',
		'j' => 'о',
		'k' => 'л',
		'l' => 'д',
		';' => 'ж',
		"'" => 'э',
		'z' => 'я',
		'x' => 'ч',
		'c' => 'с',
		'v' => 'м',
		'b' => 'и',
		'n' => 'т',
		'm' => 'ь',
		',' => 'б',
		'.' => 'ю',
		'0' => '0',
		'1' => '1',
		'2' => '2',
		'3' => '3',
		'4' => '4',
		'5' => '5',
		'6' => '6',
		'7' => '7',
		'8' => '8',
		'9' => '9',
		'а' => 'а',
		'б' => 'б',
		'в' => 'в',
		'г' => 'г',
		'д' => 'д',
		'е' => 'ё',
		'ж' => 'ж',
		'з' => 'з',
		'и' => 'и',
		'й' => 'й',
		'к' => 'к',
		'л' => 'л',
		'м' => 'м',
		'н' => 'н',
		'о' => 'о',
		'п' => 'п',
		'р' => 'р',
		'с' => 'с',
		'т' => 'т',
		'у' => 'у',
		'ф' => 'ф',
		'х' => 'х',
		'ч' => 'ч',
		'ц' => 'ц',
		'ы' => 'ы',
		'ш' => 'ш',
		'щ' => 'щ',
		'ъ' => 'ъ',
		'ь' => 'ь',
		'э' => 'э',
		'ю' => 'ю',
		'я' => 'я'
	);
	$send = '';
	for($n = 0; $n < strlen($word); $n++)
		if(isset($char[$word[$n]]))
			$send .= $char[$word[$n]];
	return $send;
}
function unescape($str){
	$escape_chars = '0410 0430 0411 0431 0412 0432 0413 0433 0490 0491 0414 0434 0415 0435 0401 0451 0404 0454 '.
		'0416 0436 0417 0437 0418 0438 0406 0456 0419 0439 041A 043A 041B 043B 041C 043C 041D 043D '.
		'041E 043E 041F 043F 0420 0440 0421 0441 0422 0442 0423 0443 0424 0444 0425 0445 0426 0446 '.
		'0427 0447 0428 0448 0429 0449 042A 044A 042B 044B 042C 044C 042D 044D 042E 044E 042F 044F';
	$russian_chars = 'А а Б б В в Г г Ґ ґ Д д Е е Ё ё Є є Ж ж З з И и І і Й й К к Л л М м Н н О о П п Р р С с Т т У у Ф ф Х х Ц ц Ч ч Ш ш Щ щ Ъ ъ Ы ы Ь ь Э э Ю ю Я я';
	$e = explode(' ', $escape_chars);
	$r = explode(' ', $russian_chars);
	$rus_array = explode('%u', $str);
	$new_word = str_replace($e, $r, $rus_array);
	$new_word = str_replace('%20', ' ', $new_word);
	return implode($new_word);
}
function translit($str) {
	$list = array(
		'А' => 'A',
		'Б' => 'B',
		'В' => 'V',
		'Г' => 'G',
		'Д' => 'D',
		'Е' => 'E',
		'Ж' => 'J',
		'З' => 'Z',
		'И' => 'I',
		'Й' => 'Y',
		'К' => 'K',
		'Л' => 'L',
		'М' => 'M',
		'Н' => 'N',
		'О' => 'O',
		'П' => 'P',
		'Р' => 'R',
		'С' => 'S',
		'Т' => 'T',
		'У' => 'U',
		'Ф' => 'F',
		'Х' => 'H',
		'Ц' => 'TS',
		'Ч' => 'CH',
		'Ш' => 'SH',
		'Щ' => 'SCH',
		'Ъ' => '',
		'Ы' => 'YI',
		'Ь' => '',
		'Э' => 'E',
		'Ю' => 'YU',
		'Я' => 'YA',
		'а' => 'a',
		'б' => 'b',
		'в' => 'v',
		'г' => 'g',
		'д' => 'd',
		'е' => 'e',
		'ж' => 'j',
		'з' => 'z',
		'и' => 'i',
		'й' => 'y',
		'к' => 'k',
		'л' => 'l',
		'м' => 'm',
		'н' => 'n',
		'о' => 'o',
		'п' => 'p',
		'р' => 'r',
		'с' => 's',
		'т' => 't',
		'у' => 'u',
		'ф' => 'f',
		'х' => 'h',
		'ц' => 'ts',
		'ч' => 'ch',
		'ш' => 'sh',
		'щ' => 'sch',
		'ъ' => 'y',
		'ы' => 'yi',
		'ь' => '',
		'э' => 'e',
		'ю' => 'yu',
		'я' => 'ya',
		' ' => '_',
		'№' => 'N'
	);
	return strtr($str, $list);
}

function _calendarFilter($data=array()) {
	$data = array(
		'upd' => empty($data['upd']), // Обновлять существующий календать? (при перемотке масяцев)
		'month' => empty($data['month']) ? strftime('%Y-%m') : $data['month'],
		'sel' => empty($data['sel']) ? '' : $data['sel'],
		'days' => empty($data['days']) ? array() : $data['days'],
		'func' => empty($data['func']) ? '' : $data['func'],
		'noweek' => empty($data['noweek']) ? 0 : 1,
		'norewind' => !empty($data['norewind'])
	);
	$ex = explode('-', $data['month']);
	$SHOW_YEAR = $ex[0];
	$SHOW_MON = $ex[1];
	$days = $data['days'];

	$back = $SHOW_MON - 1;
	$back = !$back ? ($SHOW_YEAR - 1).'-12' : $SHOW_YEAR.'-'.($back < 10 ? 0 : '').$back;
	$next = $SHOW_MON + 1;
	$next = $next > 12 ? ($SHOW_YEAR + 1).'-01' : $SHOW_YEAR.'-'.($next < 10 ? 0 : '').$next;

	$send =
	($data['upd'] ?
		'<div class="_calendarFilter">'.
			'<input type="hidden" class="func" value="'.$data['func'].'" />'.
			'<input type="hidden" class="noweek" value="'.$data['noweek'].'" />'.
			'<input type="hidden" class="selected" value="'.$data['sel'].'" />'.
		'<div class="content">'
	: '').
		'<table class="data">'.
			'<tr>'.($data['norewind'] ? '' : '<td class="ch" val="'.$back.'">&laquo;').
				'<td><a val="'.$data['month'].'"'.($data['month'] == $data['sel'] ? ' class="sel"' : '').'>'._monthDef($SHOW_MON).'</a> '.
					($data['norewind'] ? '' :
						'<a val="'.$SHOW_YEAR.'"'.($SHOW_YEAR == $data['sel'] ? ' class="sel"' : '').'>'.$SHOW_YEAR.'</a>'.
					'<td class="ch" val="'.$next.'">&raquo;').
		'</table>'.
		'<table class="month">'.
			'<tr class="week-name">'.
				($data['noweek'] ? '' :'<th>&nbsp;').
				'<td>пн<td>вт<td>ср<td>чт<td>пт<td>сб<td>вс';

	$unix = strtotime($data['month'].'-01');
	$dayCount = date('t', $unix);   // Количество дней в месяце
	$week = date('w', $unix);       // Номер первого дня недели
	if(!$week)
		$week = 7;

	$curDay = strftime('%Y-%m-%d');
	$curUnix = strtotime($curDay); // Текущий день для выделения прошедших дней
	$weekNum = intval(date('W', $unix));    // Номер недели с начала месяца

	$range = _calendarWeek($data['month'].'-01');
	$send .= '<tr'.($range == $data['sel'] ? ' class="sel"' : '').'>'.
		($data['noweek'] ? '' : '<td class="week-num" val="'.$range.'">'.$weekNum);
	for($n = $week; $n > 1; $n--, $send .= '<td>'); // Вставка пустых полей, если первый день недели не понедельник
	for($n = 1; $n <= $dayCount; $n++) {
		$day = $data['month'].'-'.($n < 10 ? '0' : '').$n;
		$cur = $curDay == $day ? ' cur' : '';
		$on = empty($days[$day]) ? '' : ' on';
		$old = $unix + $n * 86400 <= $curUnix ? ' old' : '';
		$sel = $day == $data['sel'] ? ' sel' : '';
		$val = $on ? ' val="'.$day.'"' : '';
		$send .= '<td class="d '.$cur.$on.$old.$sel.'"'.$val.'>'.$n;
		$week++;
		if($week > 7)
			$week = 1;
		if($week == 1 && $n < $dayCount) {
			$range = _calendarWeek($data['month'].'-'.($n + 1 < 10 ? 0 : '').($n + 1));
			$send .= '<tr'.($range == $data['sel'] ? ' class="sel"' : '').'>'.
				($data['noweek'] ? '' : '<td class="week-num" val="'.$range.'">'.(++$weekNum));
		}
	}
	if($week > 1)
		for($n = $week; $n <= 7; $n++, $send .= '<td>'); // Вставка пустых полей, если день заканчивается не воскресеньем
	$send .= '</table>'.($data['upd'] ? '</div></div>' : '');

	return $send;
}//_calendarFilter()
function _calendarDataCheck($data) {
	if(empty($data))
		return false;
	if(preg_match(REGEXP_DATE, $data) || preg_match(REGEXP_YEARMONTH, $data) || preg_match(REGEXP_YEAR, $data))
		return true;
	$ex = explode(':', $data);
	if(preg_match(REGEXP_DATE, $ex[0]) && preg_match(REGEXP_DATE, @$ex[1]))
		return true;
	return false;
}//_calendarDataCheck()
function _calendarPeriod($data) {// Формирование периода для элементов массива запросившего фильтра
	$send['period'] = $data;
	if(!_calendarDataCheck($data))
		return $send;
	$ex = explode(':', $data);
	if(empty($ex[1]))
		return $send + array('day'=>$ex[0]);
	return $send + array(
		'from' => $ex[0],
		'to' => $ex[1]
	);
}//_calendarPeriod()
function _calendarWeek($day=0) {// Формирование периода за неделю недели
	if(!$day)
		$day = strftime('%Y-%m-%d');
	$d = explode('-', $day);
	$month = $d[0].'-'.$d[1];

	$unix = strtotime($day);
	$dayCount = date('t', $unix);   // Количество дней в месяце
	$week = date('w', $unix);
	if(!$week)
		$week = 7;

	$dayStart = $d[2] - $week + 1; // Номер первого дня недели
	if($dayStart < 1) {
		$back = $d[1] - 1;
		$back = !$back ? ($d[0] - 1).'-12' : $d[0].'-'.($back < 10 ? 0 : '').$back;
		$start = $back.'-'.(date('t', strtotime($back.'-01')) + $dayStart);
	} else
		$start = $month.'-'.($dayStart < 10 ? 0 : '').$dayStart;

	$dayEnd = 7 - $week + $d[2]; // Номер последнего дня недели
	if($dayEnd > $dayCount) {
		$next = $d[1] + 1;
		$next = $next > 12 ? ($d[0] + 1).'-01' : $d[0].'-'.($next < 10 ? 0 : '').$next;
		$end = $next.'-0'.($dayEnd - $dayCount);
	} else
		$end = $month.'-'.($dayEnd < 10 ? 0 : '').$dayEnd;

	return $start.':'.$end;
}//_calendarPeriod()

function _imageAdd($v=array()) {
	$v = array(
		'txt' => empty($v['txt']) ? 'Добавить изображение' : $v['txt'],
		'owner' => empty($v['owner']) || !preg_match(REGEXP_WORD, $v['owner']) ? '' : $v['owner'],
		'max' => empty($v['max']) || !preg_match(REGEXP_NUMERIC, $v['owner']) ? 8 : $v['max'] // максимальное количество закружаемых изображений
	);
	return
		'<div class="_image-spisok">'._imageSpisok($v['owner']).'</div>'.
		'<div class="_image-error"></div>'.
		'<div class="_image-add">'.
			'<div class="_busy">&nbsp;</div>'.
			'<form method="post" action="'.AJAX_MAIN.'" enctype="multipart/form-data" target="_image-frame">'.
				'<input type="file" name="f1" />'.
				'<input type="file" name="f2" class="f2" />'.
				'<input type="file" name="f3" class="f3" />'.
				'<input type="hidden" name="op" value="image_add" />'.
				'<input type="hidden" name="owner" value="'.$v['owner'].'" />'.
				'<input type="hidden" name="max" value="'.$v['max'].'" />'.
			'</form>'.
			'<span>'.$v['txt'].'</span>'.
			'<iframe name="_image-frame"></iframe>'.
		'</div>';
}//_imageAdd()
function _imageSpisok($owner) {
	if(!$owner)
		return '';
	$sql = "SELECT * FROM `images` WHERE !`deleted` AND `owner`='".$owner."' ORDER BY `sort`";
	$q = query($sql);
	$send = '';
	while($r = mysql_fetch_assoc($q))
		$send .= '<a class="_iview" val="'.$r['id'].'">'.
					'<div class="del'._tooltip('Удалить', -29).'<em></em></div>'.
					'<img src="'.$r['path'].$r['small_name'].'" />'.
				'</a>';
	return $send;
}
function _imageCookie($v) {//Установка cookie после загрузки изображения и выход
	if(isset($v['error']))
		$cookie = 'error_'.$v['error'];
	else {
		$cookie = 'uploaded_';
		setcookie('_param', $v['link'].'_'.$v['id'].'_'.$v['max'], time() + 3600, '/');
	}
	setcookie('_upload', $cookie, time() + 3600, '/');
	exit;
}//_imageCookie()
function _imageNameCreate() {
	$arr = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','1','2','3','4','5','6','7','8','9','0');
	$name = '';
	for($i = 0; $i < 10; $i++)
		$name .= $arr[rand(0,35)];
	return $name;
}//_imageNameCreate()
function _imageResize($x_cur, $y_cur, $x_new, $y_new) {//изменение размера изображения с сохранением пропорций
	$x = $x_new;
	$y = $y_new;
	// если ширина больше или равна высоте
	if ($x_cur >= $y_cur) {
		if ($x > $x_cur) { $x = $x_cur; } // если новая ширина больше, чем исходная, то X остаётся исходным
		$y = round($y_cur / $x_cur * $x);
		if ($y > $y_new) { // если новая высота в итоге осталась меньше исходной, то подравнивание по Y
			$y = $y_new;
			$x = round($x_cur / $y_cur * $y);
		}
	}

	// если выстоа больше ширины
	if ($y_cur > $x_cur) {
		if ($y > $y_cur) { $y = $y_cur; } // если новая высота больше, чем исходная, то Y остаётся исходным
		$x = round($x_cur / $y_cur * $y);
		if ($x > $x_new) { // если новая ширина в итоге осталась меньше исходной, то подравнивание по X
			$x = $x_new;
			$y = round($y_cur / $x_cur * $x);
		}
	}

	return array(
		'x' => $x,
		'y' => $y
	);
}//_imageResize()
function _imageImCreate($im, $x_cur, $y_cur, $x_new, $y_new, $name) {//сжатие изображения
	$send = _imageResize($x_cur, $y_cur, $x_new, $y_new);

	$im_new = imagecreatetruecolor($send['x'], $send['y']);
	imagecopyresampled($im_new, $im, 0, 0, 0, 0, $send['x'], $send['y'], $x_cur, $y_cur);
	imagejpeg($im_new, $name, 80);
	imagedestroy($im_new);

	return $send;
}//_imageImCreate()
function _imageGet($v) {
	$v = array(
		'owner' => $v['owner'],
		'size' => isset($v['size']) ? $v['size'] : 's',
		'x' => isset($v['x']) ? $v['x'] : 10000,
		'y' => isset($v['y']) ? $v['y'] : 10000,
		'view' => isset($v['view']),
		'class' => isset($v['class']) ? $v['class'] : ''
	);

	$ownerArray = is_array($v['owner']);
	if(!$ownerArray)
		$v['owner'] = array($v['owner']);

	$v['owner'] = array_unique($v['owner']);
	$owner = array();
	foreach($v['owner'] as $val)
		$owner[] = preg_replace('/(\w+)/', '"$1"', $val, 1);

	$size = $v['size'] == 's' ? 'small' : 'big';
	$sql = "SELECT *
			FROM `images`
			WHERE !`deleted`
			  AND !`sort`
			  AND `owner` IN (".implode(',', $owner).")";
	$q = query($sql);
	$img = array();
	while($r = mysql_fetch_assoc($q)) {
		$s = 0;
		if($v['x'] != 10000 || $v['y'] != 10000)
			$s = _imageResize($r[$size.'_x'], $r[$size.'_y'], $v['x'], $v['y']);
		$img[$r['owner']] = array(
			'id' => $r['id'],
			'img' => '<img src="'.$r['path'].$r[$size.'_name'].'" '.
						($v['view'] ? 'class="_iview" val="'.$r['id'].'" ' : '').
						($s ? 'width="'.$s['x'].'" height="'.$s['y'].'" ' : '').
					 '/>'
		);
	}
	$s = 0;
	if($v['x'] != 10000 || $v['y'] != 10000)
		$s = _imageResize(!$size ? 80 : 200, !$size ? 80 : 200, $v['x'], $v['y']);
	foreach($v['owner'] as $val)
		if(empty($img[$val]))
			$img[$val] = array(
				'id' => 0,
				'img' => '<img src="'.API_HTML.'/img/nofoto-'.$v['size'].'.gif" '.($s ? 'width="'.$s['x'].'" height="'.$s['y'].'" ' : '').' />'
			);

	if($ownerArray)
		return $img;

	$img = array_shift($img);
	return $img['img'];
}//_imageGet()
