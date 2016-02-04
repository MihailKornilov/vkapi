<?php
/*

//[a-zA-Z_]*\(\)$

CREATE TABLE IF NOT EXISTS `pagehelp` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  `page` varchar(50) DEFAULT '',
  `name` varchar(200) DEFAULT '',
  `txt` text default NULL,
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
*/

define('TIME', microtime(true));
define('GLOBAL_DIR', dirname(dirname(__FILE__)));
define('GLOBAL_DIR_AJAX', GLOBAL_DIR.'/ajax');

setlocale(LC_ALL, 'ru_RU.CP1251');
setlocale(LC_NUMERIC, 'en_US');

define('DOMAIN', $_SERVER['SERVER_NAME']);
define('LOCAL', DOMAIN != 'nyandoma.ru');

require_once GLOBAL_DIR.'/syncro.php';

require_once GLOBAL_DIR.'/view/_value_regexp.php';

require_once GLOBAL_DIR.'/view/_mysql.php';
require_once GLOBAL_DIR.'/view/_vkuser.php';
require_once GLOBAL_DIR.'/view/_note.php';
require_once GLOBAL_DIR.'/view/_image.php';
require_once GLOBAL_DIR.'/view/_date.php';
require_once GLOBAL_DIR.'/view/_attach.php';
require_once GLOBAL_DIR.'/view/_calendar.php';
require_once GLOBAL_DIR.'/view/_debug.php';

require_once GLOBAL_DIR.'/view/client.php';
require_once GLOBAL_DIR.'/view/zayav.php';
require_once GLOBAL_DIR.'/view/money.php';
require_once GLOBAL_DIR.'/view/history.php';
require_once GLOBAL_DIR.'/view/remind.php';
require_once GLOBAL_DIR.'/view/salary.php';
require_once GLOBAL_DIR.'/view/setup.php';
require_once GLOBAL_DIR.'/view/sa.php';


function _const() {
	if(!$app_id = _num(@$_GET['api_id']))
		_appError();
	if(!$viewer_id = _num(@$_GET['viewer_id']))
		_appError();

	define('VIEWER_ID', $viewer_id);
	define('APP_ID', $app_id);
	define('CACHE_PREFIX', 'CACHE_'.$app_id.'_');

	session_name('app'.APP_ID);
	session_start();

	define('APP_NAME', _app('name'));

	define('VIEWER_MAX', 2147000001);
	define('CRON_MAIL', 'mihan_k@mail.ru');

	define('TODAY', strftime('%Y-%m-%d'));
	define('TODAY_UNIXTIME', strtotime(TODAY));

	define('APP_FIRST_LOAD', !empty($_GET['referrer'])); //первый запуск приложения

	$SA[982006] = 1;    // Корнилов Михаил
	//$SA[1382858] = 1; // Серёга Ш.
	$SA[166424274] = 1; // тестовая запись
	define('SA', isset($SA[VIEWER_ID]));

	define('VALUES', TIME.
					 '&api_id='.APP_ID.
					 '&viewer_id='.VIEWER_ID.
					 '&auth_key='.@$_GET['auth_key']
		  );
	//'&access_token='.@$_GET['access_token'] todo временно отключен
	//define('URL', API_HTML.'/index.php?'.VALUES);

	if(!defined('SCRIPT_NAME'))
		define('SCRIPT_NAME', 'index.php');
	define('URL', API_HTML.'/'.SCRIPT_NAME.'?'.VALUES);

	if(!defined('SCRIPT_AJAX'))
		define('SCRIPT_AJAX', 'ajax.php');
	define('AJAX_MAIN', API_HTML.'/'.SCRIPT_AJAX.'?'.VALUES);

	define('APP_URL', 'http://vk.com/app'.APP_ID);

	if(SA) {
		error_reporting(E_ALL);
		ini_set('display_errors', true);
		ini_set('display_startup_errors', true);
	}

	define('DEBUG', SA && !empty($_COOKIE['debug']));
	define('COOKIE_PREFIX', APP_ID.'_'.VIEWER_ID.'_');

	define('ATTACH_PATH', GLOBAL_PATH.'/.attach/'.APP_ID);
	define('ATTACH_HTML', '/.vkapp/.attach/'.APP_ID);

	define('PATH_DOGOVOR', ATTACH_PATH.'/dogovor');
	define('LINK_DOGOVOR', ATTACH_HTML.'/dogovor');
}

function _header() {
	return
		'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'.
		'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">'.

		'<head>'.
			'<meta http-equiv="content-type" content="text/html; charset=windows-1251" />'.
			'<title>'.APP_NAME.'</title>'.

			_api_scripts().
			(PIN_ENTER ? '' : _appScripts()).

		'</head>'.
		'<body>'.
			'<div id="frameBody">'.
				'<iframe id="frameHidden" name="frameHidden"></iframe>';
}
function _api_scripts() {//скрипты и стили, которые вставляются в html
	define('MIN', DEBUG ? '' : '.min');
	return
		//Отслеживание ошибок в скриптах
		(SA ? '<script type="text/javascript" src="/.vkapp/.js/errors.js"></script>' : '').

		//Стороние скрипты
		'<script type="text/javascript" src="/.vkapp/.js/jquery-2.1.4.min.js"></script>'.
		'<script type="text/javascript" src="/.vkapp/.js/jquery-ui.min.js"></script>'.
		'<script type="text/javascript" src="'.API_HTML.'/js/xd_connection.min.js?20"></script>'.

		//Установка начального значения таймера.
		(SA ? '<script type="text/javascript">var TIME=(new Date()).getTime();</script>' : '').

		//Установка стандартных значений для JS
		'<script type="text/javascript">'.
			(LOCAL ? 'for(var i in VK)if(typeof VK[i]=="function")VK[i]=function(){return false};' : '').
			'var VIEWER_ID='.VIEWER_ID.','.
				'VIEWER_ADMIN='.VIEWER_ADMIN.','.
				'APP_ID='.APP_ID.','.
				'WS_ID='.WS_ID.','.
				'URL="'.URL.'",'.
				'AJAX_MAIN="'.AJAX_MAIN.'",'.
				'VALUES="'.VALUES.'";'.
		'</script>'.

		//Подключение api VK. Стили VK должны стоять до основных стилей сайта
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/vk'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/vk'.MIN.'.js?'.VERSION.'"></script>'.

		//Переменные _global для всех приложений
		'<script type="text/javascript" src="'.API_HTML.'/js/values/global.js?'.GLOBAL_VALUES.'"></script>'.

		(WS_ID ? '<script type="text/javascript" src="'.API_HTML.'/js/values/ws_'.WS_ID.'.js?'.WS_VALUES.'"></script>' : '').

(PIN_ENTER ? '' :

		//Клиенты
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/client'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/client'.MIN.'.js?'.VERSION.'"></script>'.

		//Заявки
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/zayav'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/zayav'.MIN.'.js?'.VERSION.'"></script>'.

		//Деньги
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/money'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/money'.MIN.'.js?'.VERSION.'"></script>'.

		//История действий
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/history'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/history'.MIN.'.js?'.VERSION.'"></script>'.

		//Напоминания
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/remind'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/remind'.MIN.'.js?'.VERSION.'"></script>'.

		//З/п сотрудников
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/salary'.MIN.'.css?'.VERSION.'" />'.
//		'<script type="text/javascript" src="'.API_HTML.'/js/salary'.MIN.'.js?'.VERSION.'"></script>'.

		//Настройки
	(@$_GET['p'] == 'setup' ?
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/setup'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/setup'.MIN.'.js?'.VERSION.'"></script>'
	: '').

		//Суперадмин (SA)
	(@$_GET['p'] == 'sa' ?
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/sa'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/sa'.MIN.'.js?'.VERSION.'"></script>'
	: '')
).

	//debug
	(SA ?
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/debug'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/debug'.MIN.'.js?'.VERSION.'"></script>'
	: '');
}
function _global_index() {//пути переходов по ссылкам глобальных разделов
	switch(@$_GET['p']) {
		case 'client': return _clientCase();
		case 'zayav':  return _zayav();
		case 'money':  return _money();
		case 'report': return _report();
		case 'setup':  return _setup();
		case 'print':  _print_document(); exit;
		case 'sa':
			switch(@$_GET['d']) {
				default:            return sa_global_index();
				case 'menu':        return sa_menu();
				case 'history':     return sa_history();
				case 'historycat':  return sa_history_cat();
				case 'rule':        return sa_rule();
				case 'balans':      return sa_balans();
				case 'zayav':       return sa_zayav();
				case 'color':       return sa_color();

				case 'app':         return sa_app();
			}
	}

	return '';
}

function _footer() {
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

	mysql_close();

	return
				_debug().
				'<script type="text/javascript">hashSet({'.implode(',', $v).'});</script>'.
			'</div>'.
			_footerYandexMetrika().
		'</body></html>';
}
function _footerYandexMetrika() {
	if(LOCAL || SA)
		return '';

	return
	'<!-- Yandex.Metrika counter -->'.
		'<script type="text/javascript">'.
		    '(function (d, w, c) {'.
		        '(w[c] = w[c] || []).push(function() {'.
		            'try {'.
		                'w.yaCounter35023590 = new Ya.Metrika({'.
		                    'id:35023590,'.
		                    'clickmap:true,'.
		                    'trackLinks:true,'.
		                    'accurateTrackBounce:true,'.
		                    'webvisor:true,'.
		                    'trackHash:true,'.
		                    'ut:"noindex"'.
		                '});'.
		            '} catch(e) { }'.
		        '});'.

		        'var n = d.getElementsByTagName("script")[0],'.
		            's = d.createElement("script"),'.
		            'f = function () { n.parentNode.insertBefore(s, n); };'.
		        's.type = "text/javascript";'.
		        's.async = true;'.
		        's.src = "https://mc.yandex.ru/metrika/watch.js";'.

		        'if (w.opera == "[object Opera]") {'.
		            'd.addEventListener("DOMContentLoaded", f, false);'.
		        '} else { f(); }'.
		    '})(document, window, "yandex_metrika_callbacks");'.
		'</script>'.
		'<noscript><div><img src="https://mc.yandex.ru/watch/35023590?ut=noindex" style="position:absolute; left:-9999px;" /></div></noscript>'.
	'<!-- /Yandex.Metrika counter -->';
}

function _app($i='all') {//Получение данных о приложении
	$key = CACHE_PREFIX.'app'.APP_ID;
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT *
				FROM `_app`
				WHERE `id`=".APP_ID;
		if(!$arr = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			_appError('Невозможно прочитать данные приложения для кеша.');
		xcache_set($key, $arr, 86400);
	}

	if($i == 'all')
		return $arr;

	if(!isset($arr[$i]))
		return '_app: неизвестный ключ <b>'.$i.'</b>';

	return $arr[$i];
}
function _appAuth() {//Проверка авторизации в приложении
	if(LOCAL)
		return;
	if(@$_GET['auth_key'] != md5(APP_ID.'_'.VIEWER_ID.'_'._app('secret')))
		_appError('Ошибка авторизации приложения.');
}
function _appError($msg='Приложение не было загружено.') {//вывод сообщения об ошибке приложения и выход
	$html =
		'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'.
		'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">'.
			'<head>'.
				'<meta http-equiv="content-type" content="text/html; charset=windows-1251" />'.
				'<title>Error</title>'.


				'<script type="text/javascript" src="/.vkapp/.js/jquery-2.1.4.min.js"></script>'.
				'<script type="text/javascript" src="'.API_HTML.'/js/xd_connection.min.js?20"></script>'.

				'<script type="text/javascript">'.
					'var VIEWER_ID='.VIEWER_ID.','.
						'APP_ID='.APP_ID.','.
						'API_HTML="'.API_HTML.'",'.
						'VALUES="'.VALUES.'";'.
				'</script>'.

				'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/vk.min.css" />'.
				'<script type="text/javascript" src="'.API_HTML.'/js/vk.min.js"></script>'.

			'</head>'.
			'<body>'.
				'<div id="frameBody">'.
					'<iframe id="frameHidden" name="frameHidden"></iframe>'.
					_noauth($msg).
				'</div>'.
			'</body>'.
		'</html>';
	die($html);
}

function _ws($i='all') {//Получение данных об организации
	_wsOneCheck();

	if(!WS_ID)
		_appError('Нет привязки к организации.');

	$key = CACHE_PREFIX.'ws'.WS_ID;
	if(!$ws = xcache_get($key)) {
		$sql = "SELECT *
				FROM `_ws`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".WS_ID."
				  AND !`deleted`";
		if(!$ws = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			_appError('Невозможно прочитать данные организации для кеша.');
		xcache_set($key, $ws, 86400);
	}

	if(!defined('WS_VALUES'))
		define('WS_VALUES', $ws['js_values']);

	if($i == 'all')
		return $ws;

	if(!isset($ws[$i]))
		return '_ws: неизвестный ключ <b>'.$i.'</b>';

	return $ws[$i];
}
function _wsOneCheck() {
	// Проверка наличия в базе данных хотя бы об одной организации.
	// Если нет и если пользователь является суперадминистратором, то внесение организации.
	// Применение текущему пользователю id внесённой организации и назначение его администратором.
	if(!SA)
		return;

	if(!WS_ID) {
		$sql = "SELECT COUNT(`id`)
				FROM `_ws`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`";
		if(!query_value($sql, GLOBAL_MYSQL_CONNECT)) {
			$sql = "INSERT INTO `_ws` (
						`app_id`,
						`admin_id`
					) VALUES (
						".APP_ID.",
						".VIEWER_ID."
					)";
			query($sql, GLOBAL_MYSQL_CONNECT);

			$insert_id = query_insert_id('_ws', GLOBAL_MYSQL_CONNECT);

			$sql = "UPDATE `_vkuser`
					SET `ws_id`=".$insert_id.",
						`admin`=1,
						`worker`=1
					WHERE `app_id`=".APP_ID."
					  AND `viewer_id`=".VIEWER_ID;
			query($sql, GLOBAL_MYSQL_CONNECT);

			xcache_unset(CACHE_PREFIX.'viewer_'.VIEWER_ID);
			xcache_unset(CACHE_PREFIX.'viewer_rule_'.VIEWER_ID);

			$sql = "DELETE FROM `_vkuser_rule`
					WHERE `app_id`=".APP_ID."
					  AND `viewer_id`=".VIEWER_ID;
			query($sql, GLOBAL_MYSQL_CONNECT);
			_appError('SA: Создана новая организация.<br />Перезайдите в приложение.');
		}
		_appError('SA: нет привязки к организации при проверке.');
	}
}



/* Разделы главного меню */
function _menuCache() {//получение списка разделов меню из кеша
	$key = CACHE_PREFIX.'menu';
	if(!$menu = xcache_get($key)) {
		$sql = "SELECT
					*,
					0 `show`
				FROM `_menu`";
		$menu = query_arr($sql, GLOBAL_MYSQL_CONNECT);
		xcache_set($key, $menu, 86400);
	}

	$key = CACHE_PREFIX.'menu_app';
	if(!$app = xcache_get($key)) {
		$sql = "SELECT `menu_id` `id`
				FROM `_menu_app`
				WHERE `app_id`=".APP_ID;
		$app = query_arr($sql, GLOBAL_MYSQL_CONNECT);
		xcache_set($key, $app, 86400);
	}

	$key = CACHE_PREFIX.'menu_sort';
	if(!$sort = xcache_get($key)) {
		$sort = array();
		$sql = "SELECT
					`menu_id` `id`,
					`show`
				FROM `_menu_app`
				WHERE `app_id`=".APP_ID."
				ORDER BY `sort`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		while($r = mysql_fetch_assoc($q))
			$sort[] = array('show'=>$r['show']) + $menu[$r['id']];
		xcache_set($key, $sort, 86400);
	}


	foreach($menu as $id => $r) {
		if(empty($app[$id])) {
			$sql = "INSERT INTO `_menu_app` (
					`app_id`,
					`menu_id`,
					`sort`
				) VALUES (
					".APP_ID.",
					'".$id."',
					'"._maxSql('_menu_app')."'
				)";
			query($sql, GLOBAL_MYSQL_CONNECT);

			xcache_unset(CACHE_PREFIX.'menu_app');
			xcache_unset(CACHE_PREFIX.'menu_sort');

			$sort[] = $menu[$id];
		}
	}

	return $sort;
}
function _menu() {//разделы основного меню
	if(@$_GET['p'] == 'sa')
		return '';

	$link = '';
	foreach(_menuCache() as $r)
		if($r['show']) {
			$sel = $r['p'] == $_GET['p'] ? ' class="sel"' : '';
			if($r['p'] == 'report')
				$r['name'] .= _remindTodayCount(1);
			$link .=
				'<a href="'.URL.'&p='.$r['p'].'"'.$sel.'>'.
					$r['name'].
				'</a>';
		}

	return '<div id="_menu">'.$link.'</div>';
}

/* Секция отчётов - report */
function _report() {
	$d = empty($_GET['d']) ? 'history' : $_GET['d'];
	$d1 = '';
	$pages = array(
		'history' => 'История действий',
		'remind' => 'Напоминания'._remindTodayCount(1).'<div class="img_add _remind-add"></div>',
		'salary' => 'З/п сотрудников'
	);


	if(!RULE_HISTORY_VIEW) {
		unset($pages['history']);
		if($d == 'history')
			$d = 'remind';
	}

	$rightLink = '<div class="rightLink">';
	if($pages)
		foreach($pages as $p => $name)
			$rightLink .= '<a href="'.URL.'&p=report&d='.$p.'"'.($d == $p ? ' class="sel"' : '').'>'.$name.'</a>';
	$rightLink .= '</div>';

	$left = '';
	$right = '';
	switch($d) {
		default: $d = 'history';
		case 'histoty':
			if(!RULE_HISTORY_VIEW)
				break;
			$data = _history();
			$left = $data['spisok'];
			$right .= _history_right();
			break;
		case 'remind':
			$left =
				_remind_stat().
				'<div id="_remind-spisok">'._remind('spisok').'</div>';
			$right .= _remind('right');
			break;
		case 'salary':
			$left = _salary();
			if(defined('WORKER_OK')) {
				$filter = salaryFilter($_GET);
				$right =
					'<div id="salary-filter">'.
						'<input type="hidden" id="year" value="'.$filter['year'].'" />'.
						'<div id="month-list">'.salary_month_list($filter).''.
					'</div>';
			}
			break;
	}

	return
		'<table class="tabLR '.($d1 ? $d1 : $d).'" id="report">'.
			'<tr><td class="left">'.$left.
				'<td class="right">'.
					$rightLink.
					$right.
		'</table>';
}



function _vkapi($method, $param=array()) {//получение данных из api вконтакте
	$param += array(
		'v' => 5.21,
		'lang' => 'ru',
		'access_token' => isset($param['access_token']) ? $param['access_token'] : @$_GET['access_token']
	);

	$url = 'https://api.vk.com/method/'.$method.'?'.http_build_query($param);
	$res = file_get_contents($url);
	$res = json_decode($res, true);
	if(DEBUG)
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
	die(json_encode($send + jsonDebugParam()));
}
function jsonSuccess($send=array()) {
	$send['success'] = 1;
	die(json_encode($send + jsonDebugParam()));
}
function jsonDebugParam() {//возвращение дополнительных параметров json, если включен debug
	if(DEBUG) {
		global $sqlQuery, $sqlTime;
		$d = debug_backtrace();
		return array(
			'post' => $_POST,
			'link' => 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'],
			'php_time' => round(microtime(true) - TIME, 3),
			'sql_count' => count($sqlQuery),
			'sql_time' => round($sqlTime, 3),
			'sql' => utf8(implode('', $sqlQuery)),
			'php_file' => $d[1]['file'],
			'php_line' => $d[1]['line']
		);
	}
	return array();
}

function _hashRead() {
	if(PIN_ENTER) { // Если требуется пин-код, hash сохраняется в cookie
		setcookie('hash', empty($_GET['hash']) ? @$_COOKIE['hash'] : $_GET['hash'], time() + 2592000, '/');
		return;
	}

	$_GET['p'] = isset($_GET['p']) ? $_GET['p'] : 'zayav';
	if(empty($_GET['hash'])) {
		define('HASH_VALUES', false);
		if(APP_FIRST_LOAD) {// восстановление последней посещённой страницы
			$_GET['p'] = isset($_COOKIE['p']) ? $_COOKIE['p'] : $_GET['p'];
			$_GET['d'] = isset($_COOKIE['d']) ? $_COOKIE['d'] : '';
			$_GET['d1'] = isset($_COOKIE['d1']) ? $_COOKIE['d1'] : '';
			$_GET['id'] = isset($_COOKIE['id']) ? $_COOKIE['id'] : '';
		} else
			_hashCookieSet();
		return;
	}
	$ex = explode('.', $_GET['hash']);
	$r = explode('_', $ex[0]);
	unset($ex[0]);
	define('HASH_VALUES', empty($ex) ? false : implode('.', $ex));
	$_GET['p'] = $r[0];
	unset($_GET['d']);
	unset($_GET['d1']);
	unset($_GET['id']);
	switch($_GET['p']) {
		case 'client':
			if(isset($r[1]))
				if(preg_match(REGEXP_NUMERIC, $r[1])) {
					$_GET['d'] = 'info';
					$_GET['id'] = intval($r[1]);
				}
			break;
		case 'zayav':
			if(isset($r[1]))
				if(preg_match(REGEXP_NUMERIC, $r[1])) {
					$_GET['d'] = 'info';
					$_GET['id'] = intval($r[1]);
				} else {
					$_GET['d'] = $r[1];
					if(isset($r[2]))
						$_GET['id'] = intval($r[2]);
				}
			break;
		case 'zp':
			if(isset($r[1]))
				if(preg_match(REGEXP_NUMERIC, $r[1])) {
					$_GET['d'] = 'info';
					$_GET['id'] = intval($r[1]);
				}
			break;
		default:
			if(isset($r[1])) {
				$_GET['d'] = $r[1];
				if(isset($r[2]))
					$_GET['d1'] = $r[2];
			}
	}
	_hashCookieSet();
}
function _hashCookieSet() {
	if(@$_GET['p'] == 'print')
		return;
	setcookie('p', $_GET['p'], time() + 2592000, '/');
	setcookie('d', isset($_GET['d']) ? $_GET['d'] : '', time() + 2592000, '/');
	setcookie('d1', isset($_GET['d1']) ? $_GET['d1'] : '', time() + 2592000, '/');
	setcookie('id', isset($_GET['id']) ? $_GET['id'] : '', time() + 2592000, '/');
}
function _hashFilter($name) {//формирование элементов фильтра из cookie или адресной строки
	$v = array();
	if(HASH_VALUES) {
		$ex = explode('.', HASH_VALUES);
		foreach($ex as $r) {
			$arr = explode('=', $r);
			$v[$arr[0]] = $arr[1];
		}
	} else
		foreach($_COOKIE as $k => $val) {
			$arr = explode(COOKIE_PREFIX.$name.'_', $k);
			if(isset($arr[1]))
				$v[$arr[1]] = $val;
		}

	$v['find'] = unescape(@$v['find']);

	return $v;
}

function _noauth($msg='Не удалось выполнить вход в приложение.') {
	return '<div class="noauth"><div>'.$msg.'</div></div>';
}
function _err($msg='Ошибка') {
	return '<div class="_err">'.$msg.'</div>';
}
function _pinCheck() {//вывод страницы с вводом пин-кода, если это требуется
	if(!PIN)
		return;
	if(!PIN_ENTER) {
		$_SESSION[PIN_TIME_KEY] = time() + PIN_TIME_LEN;
		return;
	}

	unset($_SESSION[PIN_TIME_KEY]);

	$html = _service().
		_header().
		'<div id="pin-enter">'.
			'Пин: '.
			'<input type="password" id="pin" maxlength="10">'.
			'<button class="vk" onclick="pinEnter()">Вход</button>'.
			'<div class="red">&nbsp;</div>'.
			'<script type="text/javascript">pinLoad()</script>'.
		'</div>'.
		_footer();

	die($html);
}

function _num($v) {
	if(empty($v) || is_array($v) || !preg_match(REGEXP_NUMERIC, $v))
		return 0;
	return intval($v);
}
function _bool($v) {//проверка на булево число
	if(empty($v) || is_array($v) || !preg_match(REGEXP_BOOL, $v))
		return 0;
	return intval($v);
}
function _cena($v, $minus=0) {//проверка на цену. $minus - может ли цена быть минусовой
	if(empty($v) || is_array($v) || !preg_match($minus ? REGEXP_CENA_MINUS : REGEXP_CENA, $v))
		return 0;
	$v = str_replace(',', '.', $v);
	return round($v, 2);
}
function _txt($v, $utf8=0) {
	$v = htmlspecialchars(trim($v));
	return $utf8 ? $v : win1251($v);
}//_txt
function _br($v) {//вставка br в текст при нахождении enter
	return str_replace("\n", '<br />', $v);
}
function _daNet($v) {//$v: 1 -> да, 0 -> нет
	return $v ? 'да' : 'нет';
}
function _iconEdit($v=array()) {//иконка редактирования записи в таблице
	$v = array(
		'id' => _num(@$v['id']) ? ' val="'.$v['id'].'"' : ''//id записи
	);

	return '<div'.$v['id'].' class="img_edit'._tooltip('Изменить', -52, 'r').'</div>';
}
function _iconDel($v=array()) {//иконка удаления записи в таблице
	//если указывается дата внесения записи и она не является сегодняшним днём, то удаление невозможно
	if(!empty($v['nodel']) || !empty($v['dtime_add']) && TODAY != substr($v['dtime_add'], 0, 10))
		return '';

	$v = array(
		'id' => _num(@$v['id']) ? 'val="'.$v['id'].'" ' : '',//id записи
		'class' => !empty($v['class']) ? ' '.$v['class'] : ''//дополнительный класс
	);

	return '<div '.$v['id'].'class="img_del'.$v['class']._tooltip('Удалить', -46, 'r').'</div>';
}
function _dtimeAdd($v=array()) {//дата и время внесения записи с подсказкой сотрудника, который вносил запись
	return
		'<div class="'._tooltip(_viewerAdded($v['viewer_id_add']), -40).FullDataTime($v['dtime_add']).'</div>'.
	(@$v['viewer_id_del'] ?
		'<div class="ddel '._tooltip(_viewerDeleted($v['viewer_id_del']), -40).
			FullDataTime($v['dtime_del']).
		'</div>'
	: '');
}

function _ids($ids, $return_arr=0) {//проверка корректности списка id, составленные через запятую
	$ids = trim($ids);
	$arr = array();
	if(!empty($ids)) {
		$arr = explode(',', $ids);
		if(!empty($arr))
			foreach($arr as $i => $id)
				if(!$arr[$i] = _num(trim($id)))
					return false;
	}
	return $return_arr ? $arr : implode(',', $arr);
}
function _idsGet($arr, $i='id') {//возвращение из массива списка id через запятую
	$ids = array();
	foreach($arr as $r)
		if(!empty($r[$i]))
			$ids[] = $r[$i];
	return empty($ids) ? 0 : implode(',', array_unique($ids));
}
function _keys($arr) {//возвращение ключей через запятую
	return implode(',', array_keys($arr));
}
function _mon($v) {//проверка даты в формате 2015-10, если не соответствует, возврат текущей даты
	if(empty($v) || is_array($v) || !preg_match(REGEXP_YEARMONTH, $v))
		return strftime('%Y-%m');
	return $v;
}
function _year($v) {//проверка года, если не соответствует, возврат текущего года
	if(empty($v) || is_array($v) || !preg_match(REGEXP_YEAR, $v))
		return strftime('%Y');
	return intval($v);
}
function _numToWord($num, $firstSymbolUp=false) {
	$num = intval($num);
	$one = array(
		0 => 'ноль',
		1 => 'один',
		2 => 'два',
		3 => 'три',
		4 => 'четыре',
		5 => 'пять',
		6 => 'шесть',
		7 => 'семь',
		8 => 'восемь',
		9 => 'девять',
		10 => 'деcять',
		11 => 'одиннадцать',
		12 => 'двенадцать',
		13 => 'тринадцать',
		14 => 'четырнадцать',
		15 => 'пятнадцать',
		16 => 'шестнадцать',
		17 => 'семнадцать',
		18 => 'восемнадцать',
		19 => 'девятнадцать'
	);
	$ten = array(
		2 => 'двадцать',
		3 => 'тридцать',
		4 => 'сорок',
		5 => 'пятьдесят',
		6 => 'шестьдесят',
		7 => 'семьдесят',
		8 => 'восемьдесят',
		9 => 'девяносто'
	);
	$hundred = array(
		1 => 'сто',
		2 => 'двести',
		3 => 'триста',
		4 => 'четыреста',
		5 => 'пятьсот',
		6 => 'шестьсот',
		7 => 'семьсот',
		8 => 'восемьсот',
		9 => 'девятьсот'
	);

	if($num < 20)
		return $one[$num];

	$word = '';
	if($num % 100 > 0)
		if($num % 100 < 20)
			$word = $one[$num % 100];
		else
			$word = $ten[floor($num / 10) % 10].($num % 10 > 0 ? ' '.$one[$num % 10] : '');

	if($num % 1000 >= 100)
		$word = $hundred[floor($num / 100) % 10].' '.$word;

	if($num >= 1000) {
		$t = floor($num / 1000) % 1000;
		$word = ' тысяч'._end($t, 'а', 'и', '').' '.$word;
		if($t % 100 > 2 && $t % 100 < 20)
			$word = $one[$t % 100].$word;
		else {
			if($t % 10 == 1)
				$word = 'одна'.$word;
			elseif($t % 10 == 2)
				$word = 'две'.$word;
			elseif($t % 10 != 0)
				$word = $one[$t % 10].' '.$word;
			if($t % 100 >= 20)
				$word = $ten[floor($t / 10) % 10].' '.$word;
		}
		if($t >= 100)
			$word = $hundred[floor($t / 100) % 10].' '.$word;
	}
	if($firstSymbolUp)
		$word[0] = strtoupper($word[0]);
	return $word;
}
function _maxSql($table, $pole='sort', $ws=0, $resource_id=GLOBAL_MYSQL_CONNECT) {
	/*
		$ws: учитывать приложение и организацию
	*/
	$sql = "SELECT IFNULL(MAX(`".$pole."`)+1,1)
			FROM `".$table."`
			WHERE `id`".
			($ws ? " AND `app_id`=".APP_ID." AND `ws_id`=".WS_ID : '');
	return query_value($sql, $resource_id);
}
function _arrayTimeGroup($arr, $spisok=array()) {//группировка массива по ключу даты добавления
	$send = $spisok;
	foreach($arr as $r) {
		$key = strtotime($r['dtime_add']);
		while(isset($send[$key]))
			$key++;
		$send[$key] = $r;
	}
	return $send;
}

function _arr($arr, $i=false) {//Последовательный массив
	$send = array();
	foreach($arr as $r) {
		$v = $i === false ? $r : $r[$i];
		$send[] = preg_match(REGEXP_CENA, $v) ? _cena($v) : utf8(htmlspecialchars_decode($v));
	}
	return $send;
}

function _sel($arr) {
	$send = array();
	foreach($arr as $uid => $title) {
		$send[] = array(
			'uid' => $uid,
			'title' => utf8($title)
		);
	}
	return $send;
}
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
}
function _assJson($arr) {//Ассоциативный массив
	$send = array();
	foreach($arr as $id => $v)
		$send[] =
			(preg_match(REGEXP_NUMERIC, $id) ? $id : '"'.$id.'"').
			':'.
			(preg_match(REGEXP_CENA, $v) ? $v : '"'.$v.'"');
	return '{'.implode(',', $send).'}';
}
function _arrJson($arr, $i=false) {//Последовательный массив
	$send = array();
	foreach($arr as $r) {
		$v = $i === false ? $r : $r[$i];
		$send[] = preg_match(REGEXP_CENA, $v) ? $v : '"'.addslashes(htmlspecialchars_decode($v)).'"';
	}
	return '['.implode(',', $send).']';
}

function _filterJs($name, $filter) {//формирование условий поиска в формате js
	$filter += array(
		'js_name' => $name,
		'op' => strtolower($name).'_spisok',
		'js' => ''
	);

	//количество страниц, которое будет выводиться в списке
	$filter['page_count'] = 1;
	if(!empty($_GET['p'])) {
		$key = APP_ID.'_'.VIEWER_ID.'_scroll_'.$_GET['p'].'_page';
		$filter['page_count'] = _num(@$_COOKIE[$key]);
		$filter['page_count'] = $filter['page_count'] && $filter['page'] == 1 ? $filter['page_count'] : 1;
	}

	if($filter['page'] != 1)
		return $filter;

	$arr = $filter;
	unset($arr['page']);
	unset($arr['clear']);
	unset($arr['js']);
	unset($arr['js_name']);

	$spisok = array();
	foreach($arr as $key => $val) {
		if(!is_numeric($val))
			$val = '"'.addslashes(_br($val)).'"';
		$spisok[] = $key.':'.$val;
	}

	$filter['js'] =
		'<script type="text/javascript">'.
			'var '.$name.'={'.implode(',', $spisok).'};'.
		'</script>';

	return $filter;
}
function _startLimit($filter) {
	return _start($filter).','.($filter['limit'] * $filter['page_count']);
}
function _start($v) {//вычисление первой позиции в базе данных
	return ($v['page'] - 1) * $v['limit'];
}
function _next($v) {//вывод ссылки на догрузку списка
	$send = '';
	$start = _start($v);
	$page_count = empty($v['page_count']) ? 1 : $v['page_count'];
	if($start + $v['limit'] * $page_count < $v['all']) {
		$c = $v['all'] - $start - ($v['limit'] * $page_count);
		$c = $c > $v['limit'] ? $v['limit'] : $c;

		$type = ' запис'._end($c, 'ь', 'и', 'ей');
		switch(@$v['type']) {
			case 1: $type = ' клиент'._end($c, 'а', 'а', 'ов'); break;
			case 2: $type = ' заяв'._end($c, 'ку', 'ки', 'ок'); break;
			case 3: $type = ' платеж'._end($c, '', 'а', 'ей'); break;
			case 4: $type = ' сч'._end($c, 'ёт', 'ёта', 'етов'); break;
			case 5: $type = ' напоминани'._end($c, 'е', 'я', 'й'); break;
		}

		$show = '<span>Показать ещё '.$c.$type.'</span>';
		$id = empty($v['id']) ? '' : ' id="'.$v['id'].'"';
		$page = $v['page'] + $page_count;
		$js_name = empty($v['js_name']) ? '' : $v['js_name'].':';//глобальная переменная js, содержащая условия поиска. После двоеточия идёт номер страницы
		$send = empty($v['tr']) ?
			'<div class="_next" val="'.$js_name.$page.'"'.$id.'>'.$show.'</div>'
				:
			'<tr class="_next" val="'.$js_name.$page.'"'.$id.'>'.
				'<td colspan="10">'.$show;
	}
	return
		$send.
		($v['page'] == 1 && !empty($v['tr']) ? '</table>' : '');
}

function Gvalues_obj($table, $sort='name', $category_id='category_id', $resource_id=MYSQL_CONNECT, $ws=0) {//ассоциативный список подкатегорий
	$cond = $ws ? " AND `app_id`=".APP_ID." AND `ws_id`=".WS_ID : '';
	$sql = "SELECT *
			FROM `".$table."`
			WHERE `id`".$cond."
			ORDER BY ".$sort;
	$q = query($sql, $resource_id);
	$sub = array();
	while($r = mysql_fetch_assoc($q)) {
		if(!isset($sub[$r[$category_id]]))
			$sub[$r[$category_id]] = array();
		$sub[$r[$category_id]][] = '{'.
				'uid:'.$r['id'].','.
				'title:"'.$r['name'].'"'.
				(!empty($r['bold']) ? ','.'content:"<b>'.$r['name'].'</b>"' : '').
			'}';
	}
	$v = array();
	foreach($sub as $n => $sp)
		$v[] = $n.':['.implode(',', $sp).']';
	return '{'.implode(',', $v).'}';
}
function _globalJsValues() {//Составление файла global.js, используемый во всех приложениях
	//одинаковые для всех приложений:
	$save =
		 'var CLIENT_CATEGORY_ASS='._assJson(_clientCategory(0,1)).','.
 		"\n".'COLOR_SPISOK='.query_selJson("SELECT `id`,`name` FROM `_setup_color` ORDER BY `name`", GLOBAL_MYSQL_CONNECT).','.
		"\n".'COLORPRE_SPISOK='.query_selJson("SELECT `id`,`predlog` FROM `_setup_color` ORDER BY `predlog`", GLOBAL_MYSQL_CONNECT).','.
		"\n".'PAY_TYPE='._selJson(_payType()).','.
		"\n".'COUNTRY_SPISOK=['.
				'{uid:1,title:"Россия"},'.
				'{uid:2,title:"Украина"},'.
				'{uid:3,title:"Беларусь"},'.
				'{uid:4,title:"Казахстан"},'.
				'{uid:5,title:"Азербайджан"},'.
				'{uid:6,title:"Армения"},'.
				'{uid:7,title:"Грузия"},'.
				'{uid:8,title:"Израиль"},'.
				'{uid:11,title:"Кыргызстан"},'.
				'{uid:12,title:"Латвия"},'.
				'{uid:13,title:"Литва"},'.
				'{uid:14,title:"Эстония"},'.
				'{uid:15,title:"Молдова"},'.
				'{uid:16,title:"Таджикистан"},'.
				'{uid:17,title:"Туркмения"},'.
				'{uid:18,title:"Узбекистан"}],'.
		"\n".'COUNTRY_ASS=_toAss(COUNTRY_SPISOK),'.
		"\n".'CITY_SPISOK=['.
				'{uid:1,title:"Москва",content:"<b>Москва</b>"},'.
				'{uid:2,title:"Санкт-Петербург",content:"<b>Санкт-Петербург</b>"},'.
				'{uid:35,title:"Великий Новгород"},'.
				'{uid:10,title:"Волгоград"},'.
				'{uid:49,title:"Екатеринбург"},'.
				'{uid:60,title:"Казань"},'.
				'{uid:61,title:"Калининград"},'.
				'{uid:72,title:"Краснодар"},'.
				'{uid:73,title:"Красноярск"},'.
				'{uid:87,title:"Мурманск"},'.
				'{uid:95,title:"Нижний Новгород"},'.
				'{uid:99,title:"Новосибирск"},'.
				'{uid:104,title:"Омск"},'.
				'{uid:110,title:"Пермь"},'.
				'{uid:119,title:"Ростов-на-Дону"},'.
				'{uid:123,title:"Самара"},'.
				'{uid:125,title:"Саратов"},'.
				'{uid:151,title:"Уфа"},'.
				'{uid:158,title:"Челябинск"}];';
	$fp = fopen(API_PATH.'/js/values/global.js', 'w+');
	fwrite($fp, $save);
	fclose($fp);

	$sql = "UPDATE `_setup_global`
			SET `value`=`value`+1
			WHERE `key`='GLOBAL_VALUES'";
	query($sql, GLOBAL_MYSQL_CONNECT);
}
function _wsJsValues($ws_id=WS_ID) {//для конкретного организации
	$save = 'var'.
		"\n".'INVOICE_SPISOK='.query_selJson("SELECT `id`,`name`
											  FROM `_money_invoice`
											  WHERE `app_id`=".APP_ID."
												AND `ws_id`=".$ws_id."
												AND !`deleted`
											  ORDER BY `id`", GLOBAL_MYSQL_CONNECT).','.
		"\n".'INVOICE_ASS=_toAss(INVOICE_SPISOK),'.
		"\n".'INVOICE_CONFIRM_INCOME='.query_assJson("SELECT `id`,1
													  FROM `_money_invoice`
													  WHERE `app_id`=".APP_ID."
														AND `ws_id`=".$ws_id."
														AND `confirm_income`
														AND !`deleted`", GLOBAL_MYSQL_CONNECT).','.
		"\n".'INVOICE_CONFIRM_TRANSFER='.query_assJson("SELECT `id`,1
														FROM `_money_invoice`
														WHERE `app_id`=".APP_ID."
														  AND `ws_id`=".$ws_id."
												          AND `confirm_transfer`
												          AND !`deleted`", GLOBAL_MYSQL_CONNECT).','.
		"\n".'WORKER_ASS='.query_assJson("SELECT `viewer_id`,CONCAT(`first_name`,' ',`last_name`)
											 FROM `_vkuser`
											 WHERE `app_id`=".APP_ID."
											   AND `ws_id`=".$ws_id."
											   AND `worker`
											   AND `viewer_id`!=982006
											 ORDER BY `dtime_add`", GLOBAL_MYSQL_CONNECT).','.
		"\n".'WORKER_SPISOK=_toSpisok(WORKER_ASS),'.
		"\n".'SALARY_PERIOD_SPISOK='._selJson(_salaryPeriod()).','.
		"\n".'EXPENSE_SPISOK='.query_selJson("SELECT `id`,`name`
											  FROM `_money_expense_category`
											  WHERE `app_id` IN (".APP_ID.",0)
											    AND `ws_id` IN (".$ws_id.",0)
											  ORDER BY `sort` ASC", GLOBAL_MYSQL_CONNECT).','.
		"\n".'EXPENSE_WORKER_USE='.query_assJson("SELECT `id`,1
												  FROM `_money_expense_category`
												  WHERE `app_id` IN (".APP_ID.",0)
													AND `ws_id` IN (".$ws_id.",0)
													AND `worker_use`", GLOBAL_MYSQL_CONNECT).','.
//		_service('const_js').','.
		"\n".'SERVICE_ACTIVE_COUNT='._service('active_count').','.  //количество активных заявок в организации
		"\n".'SERVICE_ACTIVE_ASS='._service('js').','.              //виды активных заявок в организации
		"\n".'ZAYAV_EXPENSE_DOP='._selJson(_zayavExpenseDop()).','.
		"\n".'ZAYAV_EXPENSE_SPISOK='.query_selJson("SELECT `id`,`name` FROM `_zayav_expense_category` WHERE `app_id`=".APP_ID." ORDER BY `sort`", GLOBAL_MYSQL_CONNECT).','.
		"\n".'ZAYAV_EXPENSE_TXT='.   query_assJson("SELECT `id`,1 FROM `_zayav_expense_category` WHERE `app_id`=".APP_ID." AND `dop`=1", GLOBAL_MYSQL_CONNECT).','.
		"\n".'ZAYAV_EXPENSE_WORKER='.query_assJson("SELECT `id`,1 FROM `_zayav_expense_category` WHERE `app_id`=".APP_ID." AND `dop`=2", GLOBAL_MYSQL_CONNECT).','.
		"\n".'ZAYAV_EXPENSE_ZP='.    query_assJson("SELECT `id`,1 FROM `_zayav_expense_category` WHERE `app_id`=".APP_ID." AND `dop`=3", GLOBAL_MYSQL_CONNECT).','.
		"\n".'ZAYAV_EXPENSE_ATTACH='.query_assJson("SELECT `id`,1 FROM `_zayav_expense_category` WHERE `app_id`=".APP_ID." AND `dop`=4", GLOBAL_MYSQL_CONNECT).','.
		"\n".'PRODUCT_SPISOK='.query_selJson("SELECT `id`,`name` FROM `_product` WHERE `app_id`=".APP_ID." AND `ws_id`=".$ws_id." ORDER BY `name`", GLOBAL_MYSQL_CONNECT).','.
		"\n".'PRODUCT_ASS=_toAss(PRODUCT_SPISOK),'.
		"\n".'PRODUCT_SUB_SPISOK='.Gvalues_obj('_product_sub', '`product_id`,`name`', 'product_id', GLOBAL_MYSQL_CONNECT, 1).';';


	$fp = fopen(API_PATH.'/js/values/ws_'.$ws_id.'.js', 'w+');
	fwrite($fp, $save);
	fclose($fp);

	//обновление значения версии файла ws_N.js
	$sql = "UPDATE `_ws`
			SET `js_values`=`js_values`+1
			WHERE `id`=".$ws_id;
	query($sql, GLOBAL_MYSQL_CONNECT);

	xcache_unset(CACHE_PREFIX.'ws'.$ws_id);
}

function _globalCacheClear($ws_id=WS_ID) {//очистка глобальных значений кеша
	xcache_unset(CACHE_PREFIX.'app'.APP_ID);  //данные приложения
	xcache_unset(CACHE_PREFIX.'setup_global');  //список разделов меню
	xcache_unset(CACHE_PREFIX.'menu');  //список разделов меню
	xcache_unset(CACHE_PREFIX.'menu_app');//значения для разделов меню для конкретного приложения
	xcache_unset(CACHE_PREFIX.'menu_sort');//отсортированный список разделов меню с настройками
	xcache_unset(CACHE_PREFIX.'setup_color');//цвета
	xcache_unset(CACHE_PREFIX.'viewer_rule_default_admin');//настройки прав по умолчанию для руководителя
	xcache_unset(CACHE_PREFIX.'viewer_rule_default_worker');//настройки прав по умолчанию для сотрудников
	xcache_unset(CACHE_PREFIX.'balans_action');//действие при изменении баланса
	xcache_unset(CACHE_PREFIX.'ws'.$ws_id);//данные организации
	xcache_unset(CACHE_PREFIX.'service'.$ws_id);//виды деятельности
	xcache_unset(CACHE_PREFIX.'invoice'.$ws_id);//расчётные счета
	xcache_unset(CACHE_PREFIX.'expense'.$ws_id);//категории расходов организации
	xcache_unset(CACHE_PREFIX.'zayav_expense'.APP_ID);//категории расходов заявки
	xcache_unset(CACHE_PREFIX.'product'.$ws_id);
	xcache_unset(CACHE_PREFIX.'product_sub'.$ws_id);


	//сброс времени действия введённого пинкода
//		unset($_SESSION[PIN_TIME_KEY]);

	//очистка кеша текущего пользователя
	xcache_unset(CACHE_PREFIX.'viewer_'.VIEWER_ID);
	xcache_unset(CACHE_PREFIX.'viewer_rule_'.VIEWER_ID);

	//очистка кеша сотрудников приложения
	$sql = "SELECT `viewer_id`
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".$ws_id."
			  AND `worker`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q)) {
		xcache_unset(CACHE_PREFIX.'viewer_'.$r['viewer_id']);
		xcache_unset(CACHE_PREFIX.'viewer_rule_'.$r['viewer_id']);
		xcache_unset(CACHE_PREFIX.'pin_enter_count'.$r['viewer_id']);
	}
}

function _check($id, $txt='', $v=0, $light=false) {
	$v = $v ? 1 : 0;
	return
	'<div class="_check check'.$v.($light ? ' l' : '').($txt ? '' : ' e').'" id="'.$id.'_check">'.
		'<input type="hidden" id="'.$id.'" value="'.$v.'" />'.
		$txt.
	'</div>';
}
function _radio($id, $list, $value=0, $light=0, $block=1) {
	$spisok = '';
	foreach($list as $uid => $title) {
		$sel = $uid == $value ? 'on' : 'off';
		$l = $light ? ' l' : '';
		$spisok .= '<div class="'.$sel.$l.'" val="'.$uid.'"><s></s>'.$title.'</div>';
	}
	return
	'<div class="_radio'.($block ? ' block' : '').'" id="'.$id.'_radio">'.
		'<input type="hidden" id="'.$id.'" value="'.$value.'">'.
		$spisok.
	'</div>';
}

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
}
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
}
function _tooltip($msg, $left=0, $ugolSide='') {
	return
		' _tooltip">'.
		'<div class="ttdiv"'.($left ? ' style="left:'.$left.'px"' : '').'>'.
			'<div class="ttmsg">'.$msg.'</div>'.
			'<div class="ttug'.($ugolSide ? ' '.$ugolSide : '').'"></div>'.
		'</div>';
}

function win1251($txt) { return iconv('UTF-8', 'WINDOWS-1251//TRANSLIT', $txt); }
function utf8($txt) { return iconv('WINDOWS-1251', 'UTF-8', $txt); }
function curTime() { return strftime('%Y-%m-%d %H:%M:%S'); }
function mb_ucfirst($txt) {//делание заклавной первую букву текста
	mb_internal_encoding('UTF-8');
	$txt = utf8($txt);
	$txt = mb_strtoupper(mb_substr($txt, 0, 1)).mb_substr($txt, 1);
	return win1251($txt);
}

function _rightLink($id, $spisok, $val=0) {
	$a = '';
	foreach($spisok as $uid => $title)
		$a .= '<a'.($val == $uid ? ' class="sel"' : '').' val="'.$uid.'">'.$title.'</a>';
	return
	'<div class="rightLink" id="'.$id.'_rightLink">'.
		'<input type="hidden" id="'.$id.'" value="'.$val.'">'.
		$a.
	'</div>';
}

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
		'№' => 'N',
		'¦' => ''
	);
	return strtr($str, $list);
}

function _button($id, $name, $width=0) {
	return
	'<div class="vkButton" id="'.$id.'">'.
		'<button'.($width ? ' style="width:'.$width.'px"' : '').'>'.$name.'</button>'.
	'</div>';
}
function _payType($type_id=false) {//вид расчёта
	$arr = array(
		1 => 'Наличный',
		2 => 'Безналичный'
	);
	if($type_id === false)
		return $arr;
	return isset($arr[$type_id]) ? $arr[$type_id] : '';
}

function _color($color_id, $color_dop=0) {
	if(!defined('COLOR_LOADED')) {
		$key = CACHE_PREFIX.'setup_color';
		if(!$arr = xcache_get($key)) {
			$sql = "SELECT * FROM `_setup_color`";
			$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT);
			xcache_set($key, $arr, 86400);
		}
		foreach($arr as $id => $r) {
			define('COLORPRE_'.$id, $r['predlog']);
			define('COLOR_'.$id, $r['name']);
		}
		define('COLORPRE_0', '');
		define('COLOR_0', '');
		define('COLOR_LOADED', true);
	}
	if($color_dop)
		return constant('COLORPRE_'.$color_id).' - '.strtolower(constant('COLOR_'.$color_dop));;
	return constant('COLOR_'.$color_id);
}

function _print_document() {//вывод на печать документов
	set_time_limit(60);
	require_once GLOBAL_DIR.'/excel/PHPExcel.php';
	require_once GLOBAL_DIR.'/word/clsMsDocGenerator.php';

	switch(@$_GET['d']) {
		case 'kvit_html':
			require_once GLOBAL_DIR.'/view/xsl/kvit_html.php';
			break;
		case 'kvit_comtex':
			require_once GLOBAL_DIR.'/view/xsl/kvit_comtex.php';
			break;
		case 'kvit_cartridge':
			require_once GLOBAL_DIR.'/view/xsl/kvit_cartridge.php';
			break;
		case 'schet':
			require_once GLOBAL_DIR.'/view/xsl/schet_xsl.php';
			break;
		case 'receipt': _incomeReceiptPrint(); break;
		case 'salary_list':
			require_once GLOBAL_DIR.'/view/xsl/salary_list.php';
			break;
		default: die('Документ не найден.');
	}
	exit;
}
