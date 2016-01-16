<?php
/*
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
define('GLOBAL_DIR', dirname(__FILE__));
define('GLOBAL_DIR_AJAX', GLOBAL_DIR.'/ajax');

require_once GLOBAL_DIR.'/syncro.php';
require_once GLOBAL_DIR.'/view/vkuser.php';
require_once GLOBAL_DIR.'/view/client.php';
require_once GLOBAL_DIR.'/view/zayav.php';
require_once GLOBAL_DIR.'/view/money.php';
require_once GLOBAL_DIR.'/view/history.php';
require_once GLOBAL_DIR.'/view/remind.php';
require_once GLOBAL_DIR.'/view/salary.php';
require_once GLOBAL_DIR.'/view/setup.php';
require_once GLOBAL_DIR.'/view/sa.php';
require_once GLOBAL_DIR.'/view/note.php';


setlocale(LC_ALL, 'ru_RU.CP1251');
setlocale(LC_NUMERIC, 'en_US');

define('REGEXP_NUMERIC',    '/^[0-9]{1,20}$/i');
define('REGEXP_INTEGER',    '/^-?[0-9]{1,20}$/i');
define('REGEXP_CENA',       '/^[0-9]{1,10}(.[0-9]{1,2})?(,[0-9]{1,2})?$/i');
define('REGEXP_CENA_MINUS', '/^-?[0-9]{1,10}(.[0-9]{1,2})?(,[0-9]{1,2})?$/i');
define('REGEXP_BOOL',       '/^[0-1]$/');
define('REGEXP_DATE',       '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/');
define('REGEXP_YEAR',       '/^[0-9]{4}$/');
define('REGEXP_YEARMONTH',  '/^[0-9]{4}-[0-9]{2}$/');
define('REGEXP_WORD',       '/^[a-z0-9]{1,20}$/i');
define('REGEXP_MYSQLTABLE', '/^[a-z0-9_]{1,30}$/i');
define('REGEXP_WORDFIND',   '/^[a-zA-Z�-��-�0-9,\.; ]{1,}$/i');

define('DOMAIN', $_SERVER['SERVER_NAME']);
define('LOCAL', DOMAIN != 'nyandoma.ru');
define('APP_FIRST_LOAD', !empty($_GET['referrer'])); //������ ������ ����������

if(!defined('CRON'))
	define('CRON', 0);

$SA[982006] = 1; // �������� ������
//$SA[1382858] = 1; // ����� �.
$SA[166424274] = 1; // �������� ������
define('SA', isset($SA[@$_GET['viewer_id']]));

//setcookie('sa_viewer_id', '', time() - 3600, '/');
//���� �� ������ � ������ ���������� �� ����� ������� ������������
define('SA_VIEWER_ID', SA && _num(@$_COOKIE['sa_viewer_id']) ? $_COOKIE['sa_viewer_id'] : 0);
define('VIEWER_ID', SA_VIEWER_ID ? SA_VIEWER_ID : _num(@$_GET['viewer_id']));

if(!VIEWER_ID && !CRON)
	die('Error: not correct viewer_id.');

define('COOKIE_PREFIX', APP_ID.'_'.VIEWER_ID.'_');
define('VALUES', TIME.
				 '&viewer_id='.@$_GET['viewer_id'].
				 '&auth_key='.@$_GET['auth_key'].
				 '&access_token='.@$_GET['access_token']);
define('URL', APP_HTML.'/index.php?'.VALUES);
define('TODAY', strftime('%Y-%m-%d'));
define('TODAY_UNIXTIME', strtotime(TODAY));

define('AJAX_MAIN', APP_HTML.'/ajax/main.php?'.VALUES.'&ajax=1');
define('AJAX', !empty($_GET['ajax']));//������������ �� ������ �jax
define('APP_URL', 'http://vk.com/app'.APP_ID);

define('VIEWER_MAX', 2147000001);
define('CRON_MAIL', 'mihan_k@mail.ru');

if(SA || CRON) {
	error_reporting(E_ALL);
	ini_set('display_errors', true);
	ini_set('display_startup_errors', true);
}

if(!CRON) //�������� ������ ����� � IE ����� �����
	header('P3P: CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');

define('DEBUG', SA && !empty($_COOKIE['debug']));

session_name('app'.APP_ID);
session_start();

_appAuth();
_dbConnect('GLOBAL_');
_dbConnect();
if(!CRON) {
	_getVkUser();
	_ws();
	_setupApp();
	_pinCheck();
	_hashRead();
	_zayavPoleUseInfoConst();
} else
	define('WS_ID', 0);

define('ATTACH_PATH', GLOBAL_PATH.'.attach/'.APP_ID.'/ws_'.WS_ID.'/');
define('ATTACH_HTML', '/.vkapp/.attach/'.APP_ID.'/ws_'.WS_ID.'/');

define('PATH_DOGOVOR', APP_PATH.'/files/dogovor/');
define('LINK_DOGOVOR', APP_HTML.'/files/dogovor/');

function _pre($v) {// ����� � debug ������������ �������
	if(empty($v))
		return '';
	$pre = '';
	foreach($v as $k => $r)
		$pre .= '<div class="un"><b>'.$k.':</b>'._pre_arr($r).'</div>';
	define('ARRAY_PRE', $pre);
	return $pre;
}
function _pre_arr($v) {// ��������, �������� �� ���������� ��������. ���� ��, �� ��������� �������.
	if(is_array($v)) {
		$send = '';
		foreach($v as $k => $r)
			$send .= '<div class="el"><b>'.$k.':</b>'._pre_arr($r).'</div>';
		return $send;
	}
	return $v;
}


function _header() {
	if(AJAX)
		return '';
	return
		'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'.
		'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">'.

		'<head>'.
			'<meta http-equiv="content-type" content="text/html; charset=windows-1251" />'.
			'<title>'.APP_NAME.' - ���������� '.APP_ID.'</title>'.

			_api_scripts().
			(PIN_ENTER ? '' : _appScripts()).

		'</head>'.
		'<body>'.
			'<div id="frameBody">'.
				'<iframe id="frameHidden" name="frameHidden"></iframe>';
//			(SA_VIEWER_ID ? '<div class="sa_viewer_msg">�� ����� ��� ������������� '._viewer(SA_VIEWER_ID, 'link').'. <a class="leave">�����</a></div>' : '');
}
function _api_scripts() {//������� � �����, ������� ����������� � html
	define('MIN', DEBUG ? '' : '.min');
	return
		//������������ ������ � ��������
		(SA ? '<script type="text/javascript" src="/.vkapp/.js/errors.js?'.VERSION.'"></script>' : '').

		//�������� �������
		'<script type="text/javascript" src="/.vkapp/.js/jquery-2.1.4.min.js"></script>'.
		'<script type="text/javascript" src="/.vkapp/.js/jquery-ui.min.js"></script>'.
		'<script type="text/javascript" src="/.vkapp/.js/highcharts.js"></script>'.
		'<script type="text/javascript" src="'.API_HTML.'/js/xd_connection.min.js?20"></script>'.

		//��������� ���������� �������� �������.
		(SA ? '<script type="text/javascript">var TIME=(new Date()).getTime();</script>' : '').

		//��������� ����������� �������� ��� JS
		'<script type="text/javascript">'.
			(LOCAL ? 'for(var i in VK)if(typeof VK[i]=="function")VK[i]=function(){return false};' : '').
			'var VIEWER_ID='.VIEWER_ID.','.
				'APP_ID='.APP_ID.','.
				'WS_ID='.WS_ID.','.
				'APP_HTML="'.APP_HTML.'",'.
				'VALUES="'.VALUES.'";'.
		'</script>'.

		//����������� api VK. ����� VK ������ ������ �� �������� ������ �����
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/vk'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/vk'.MIN.'.js?'.VERSION.'"></script>'.

		//���������� _global ��� ���� ����������
		'<script type="text/javascript" src="'.API_HTML.'/js/global_values.js?'.GLOBAL_VALUES.'"></script>'.

		'<script type="text/javascript" src="'.APP_HTML.'/js/ws_'.WS_ID.'_values.js?'.WS_VALUES.'"></script>'.

(PIN_ENTER ? '' :

		//�������
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/client'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/client'.MIN.'.js?'.VERSION.'"></script>'.

		//������
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/zayav'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/zayav'.MIN.'.js?'.VERSION.'"></script>'.

		//������
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/money'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/money'.MIN.'.js?'.VERSION.'"></script>'.

		//������� ��������
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/history'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/history'.MIN.'.js?'.VERSION.'"></script>'.

		//�����������
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/remind'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/remind'.MIN.'.js?'.VERSION.'"></script>'.

		//�/� �����������
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/salary'.MIN.'.css?'.VERSION.'" />'.
//		'<script type="text/javascript" src="'.API_HTML.'/js/salary'.MIN.'.js?'.VERSION.'"></script>'.

		//���������
	(@$_GET['p'] == 'setup' ?
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/setup'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/setup'.MIN.'.js?'.VERSION.'"></script>'
	: '').

		//���������� (SA)
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
function _global_index() {//���� ��������� �� ������� ���������� ��������
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
			}
	}

	return '';
}

function _ws($i='all') {//��������� ������ �� �����������
	_wsOneCheck();

	if(!WS_ID) {
		define('WS_ACCESS', 0);
		return false;
	}

	$key = CACHE_PREFIX.'ws_'.WS_ID;
	$ws = xcache_get($key);
	if(empty($ws)) {
		$sql = "SELECT *
				FROM `_ws`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".WS_ID."
				  AND !`deleted`";
		if(!$ws = query_assoc($sql, GLOBAL_MYSQL_CONNECT)) {
			define('WS_ACCESS', 0);
			return false;
		}
		xcache_set($key, $ws, 86400);
	}

	if(!defined('WS_ACCESS'))
		define('WS_ACCESS', 1);

	if($i == 'all')
		return $ws;

	if(!isset($ws[$i]))
		return '_ws: ����������� ���� <b>'.$i.'</b>';

	return $ws[$i];
}
function _wsOneCheck() {
	// �������� ������� � ���� ������ ���� �� �� ����� �����������.
	// ���� ��� � ���� ������������ �������� ��������������������, �� �������� �����������.
	// ���������� �������� ������������ id �������� ����������� � ���������� ��� ���������������.
	if(!WS_ID && SA) {
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
		}
	}
}


/* ������� �������� ���� */
function _menuCache() {//��������� ������ �������� ���� �� ����
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
function _menu() {//������� ��������� ����
	if(@$_GET['p'] == 'sa')
		return '';

	_remindActiveSet(); //REMIND_ACTIVE

	$link = '';
	foreach(_menuCache() as $r)
		if($r['show']) {
			$sel = $r['p'] == $_GET['p'] ? ' class="sel"' : '';
//			$cart = $r['p'] == 'zayav' && @$_GET['from'] == 'cartridge' ? '&d=cartridge' : '';//������� �� �������� � ����������� �� ������
			$link .=
				'<a href="'.URL.'&p='.$r['p'].'"'.$sel.'>'.
					$r['name'].
				'</a>';
		}

	return
		'<div id="_menu">'.
			$link.
			pageHelpIcon().
		'</div>';
}


/* ������ ������� - report */
function _report() {
	$d = empty($_GET['d']) ? 'history' : $_GET['d'];
	$d1 = '';
	$pages = array(
		'history' => '������� ��������',
		'remind' => '�����������'.REMIND_ACTIVE.'<div class="img_add _remind-add"></div>',
		'salary' => '�/� �����������'
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
			$remind = _remind();
			$left = $remind['spisok'];
			$right .= $remind['right'];
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
			'<a class="debug_toggle'.(DEBUG ? ' on' : '').'">'.(DEBUG ? '��' : '�').'������� Debug</a> :: '.
			'<a id="cookie_clear">������� cookie</a> :: '.
			'<a id="cache_clear">������� ��� ('.VERSION.')</a> :: '.
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
					(defined('ARRAY_PRE') ? '<a'.(@$_COOKIE['debug_pg'] == 'pre' ? ' class="sel"' : '').' val="pre">pre</a>' : '').
				'</div>'.
				'<ul class="pg sql'.(empty($_COOKIE['debug_pg']) || $_COOKIE['debug_pg'] == 'sql' ? '' : ' dn').'">'.implode('', $sqlQuery).'</ul>'.
				'<div class="pg cookie'.(@$_COOKIE['debug_pg'] == 'cookie' ? '' : ' dn').'">'.
					'<a id="cookie_update">��������</a>'.
					'<div id="cookie_spisok">'._debug_cookie().'</div>'.
				'</div>'.
				'<div class="pg get'.(@$_COOKIE['debug_pg'] == 'get' ? '' : ' dn').'">'.$get.'</div>'.
				'<div class="pg ajax'.(@$_COOKIE['debug_pg'] == 'ajax' ? '' : ' dn').'">&nbsp;</div>'.
				(defined('ARRAY_PRE') ? '<div class="pg pre'.(@$_COOKIE['debug_pg'] == 'pre' ? '' : ' dn').'">'.ARRAY_PRE.'</div>' : '').
			'</h2>'.
		'</div>';
	}
	return $send;
}
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
//		$_SESSION[PIN_TIME_KEY].
		'</div></body></html>';
}

function _vkapi($method, $param=array()) {//��������� ������ �� api ���������
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
		$send['text'] = utf8('��������� ����������� ������.');
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
function jsonDebugParam() {//����������� �������������� ���������� json, ���� ������� debug
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
	if(AJAX)
		return;

	if(PIN_ENTER) { // ���� ��������� ���-���, hash ����������� � cookie
		setcookie('hash', empty($_GET['hash']) ? @$_COOKIE['hash'] : $_GET['hash'], time() + 2592000, '/');
		return;
	}

	$_GET['p'] = isset($_GET['p']) ? $_GET['p'] : 'zayav';
	if(empty($_GET['hash'])) {
		define('HASH_VALUES', false);
		if(APP_FIRST_LOAD) {// �������������� ��������� ���������� ��������
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
	setcookie('p', $_GET['p'], time() + 2592000, '/');
	setcookie('d', isset($_GET['d']) ? $_GET['d'] : '', time() + 2592000, '/');
	setcookie('d1', isset($_GET['d1']) ? $_GET['d1'] : '', time() + 2592000, '/');
	setcookie('id', isset($_GET['id']) ? $_GET['id'] : '', time() + 2592000, '/');
}
function _hashFilter($name) {//������������ ��������� ������� �� cookie ��� �������� ������
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

function _appAuth() {//�������� ����������� � ����������
	if(LOCAL || CRON || SA_VIEWER_ID)
		return;
	if(@$_GET['auth_key'] != md5(APP_ID.'_'.VIEWER_ID.'_'.SECRET))
		die('������ ����������� ����������. ���������� �����: <a href="//vk.com/app'.APP_ID.'">vk.com/app'.APP_ID.'</a>.');
}
function _noauth($msg='�� ������� ��������� ���� � ����������.') {
	return '<div class="noauth"><div>'.$msg.'</div></div>';
}
function _err($msg='������') {
	return '<div class="_err">'.$msg.'</div>';
}
function _pinCheck() {//����� �������� � ������ ���-����, ���� ��� ���������
	if(!PIN)
		return;
	if(AJAX)
		return;
	if(!PIN_ENTER) {
		$_SESSION[PIN_TIME_KEY] = time() + PIN_TIME_LEN;
		return;
	}

	unset($_SESSION[PIN_TIME_KEY]);

	$html =
		_header().
		'<div id="pin-enter">'.
			'���: '.
			'<input type="password" id="pin" maxlength="10"> '.
			'<div class="vkButton"><button>Ok</button></div>'.
			'<div class="red">&nbsp;</div>'.
		'</div>'.
		_footer();

	die($html);
}

function _dbConnect($prefix='') {
	global $sqlQuery;
	$sqlQuery = array();
	$conn = mysql_connect(
				constant($prefix.'MYSQL_HOST'),
				constant($prefix.'MYSQL_USER'),
				constant($prefix.'MYSQL_PASS'),
				1
			) or die("Can't connect to database");
	mysql_select_db(constant($prefix.'MYSQL_DATABASE'), $conn) or die("Can't select database");
	query('SET NAMES `'.constant($prefix.'MYSQL_NAMES').'`', $conn);
	define($prefix.'MYSQL_CONNECT', $conn);
}
function query($sql, $resource_id=MYSQL_CONNECT) {
	global $sqlQuery, $sqlTime;
	$t = microtime(true);
	$res = mysql_query($sql, $resource_id ? $resource_id : MYSQL_CONNECT) or die($sql.'<br />'.mysql_error());
	$t = microtime(true) - $t;

	$sqlTime += $t;
	$t = round($t, 3);
	$sqlQuery[] = '<li><a class="sql-un">'.trim(str_replace ('	', '',  $sql)).'</a><b class="t'.($t > 0.05 ? ' long' : '').'">'.$t.'</b>';
	if(mysql_insert_id() && strpos(strtoupper($sql), 'INSERT INTO') !== false)
		return mysql_insert_id();
	return $res;
}
function query_value($sql, $resource_id=MYSQL_CONNECT) {
	$q = query($sql, $resource_id);
	if(!$r = mysql_fetch_row($q))
		return 0;
	return $r[0];
}
function query_assoc($sql, $resource_id=MYSQL_CONNECT) {
	$q = query($sql, $resource_id);
	if(!$r = mysql_fetch_assoc($q))
		return array();
	return $r;
}
function query_ass($sql, $resource_id=MYSQL_CONNECT) {//������������� ������
	$send = array();
	$q = query($sql, $resource_id);
	while($r = mysql_fetch_row($q))
		$send[$r[0]] = $r[1];
	return $send;
}
function query_arr($sql, $resource_id=MYSQL_CONNECT) {//������, ��� ������� �������� id
	$send = array();
	$q = query($sql, $resource_id);
	while($r = mysql_fetch_assoc($q))
		$send[$r['id']] = $r;
	return $send;
}
function query_selJson($sql, $resource_id=MYSQL_CONNECT) {
	$send = array();
	$q = query($sql, $resource_id);
	while($sp = mysql_fetch_row($q))
		$send[] = '{uid:'.$sp[0].',title:"'.addslashes(htmlspecialchars_decode($sp[1])).'"}';
	return '['.implode(',',$send).']';
}
function query_workerSelJson($sql, $resource_id=MYSQL_CONNECT) {//������ ����������� � ������� json ��� _select
	$send = array();
	$q = query($sql, $resource_id);
	while($r = mysql_fetch_array($q))
		$send[] = '{'.
			'uid:'.$r[0].','.
			'title:"'._viewer($r[0], 'viewer_name').'"'.
		'}';
	return '['.implode(',',$send).']';
}
function query_selArray($sql, $resource_id=MYSQL_CONNECT) {//������ ��� _select ��� �������� ����� ajax
	$send = array();
	$q = query($sql, $resource_id);
	while($sp = mysql_fetch_row($q))
		$send[] = array(
			'uid' => $sp[0],
			'title' => utf8(addslashes(htmlspecialchars_decode($sp[1])))
		);
	return $send;
}
function query_assJson($sql, $resource_id=MYSQL_CONNECT) {//������������� ������ js
	$q = query($sql, $resource_id);
	$send = array();
	while($sp = mysql_fetch_row($q))
		$send[] = $sp[0].':'.(preg_match(REGEXP_NUMERIC, $sp[1]) ? $sp[1] : '"'.$sp[1].'"');
	return '{'.implode(',', $send).'}';
}
function query_ids($sql, $resource_id=MYSQL_CONNECT) {//������ ���������������
	$q = query($sql, $resource_id);
	$send = array();
	while($sp = mysql_fetch_row($q))
		$send[] = $sp[0];
	return empty($send) ? 0 : implode(',', array_unique($send));
}
function query_insert_id($tab, $resource_id=MYSQL_CONNECT) {//id ���������� ��������� ��������
	$sql = "SELECT `id` FROM `".$tab."` ORDER BY `id` DESC LIMIT 1";
	return query_value($sql, $resource_id);
}

function _num($v) {
	if(empty($v) || is_array($v) || !preg_match(REGEXP_NUMERIC, $v))
		return 0;
	return intval($v);
}
function _bool($v) {//�������� �� ������ �����
	if(empty($v) || is_array($v) || !preg_match(REGEXP_BOOL, $v))
		return 0;
	return intval($v);
}
function _cena($v, $minus=0) {//�������� �� ����. $minus - ����� �� ���� ���� ���������
	if(empty($v) || is_array($v) || !preg_match($minus ? REGEXP_CENA_MINUS : REGEXP_CENA, $v))
		return 0;
	$v = str_replace(',', '.', $v);
	return round($v, 2);
}
function _txt($v, $utf8=0) {
	$v = htmlspecialchars(trim($v));
	return $utf8 ? $v : win1251($v);
}//_txt
function _br($v) {//������� br � ����� ��� ���������� enter
	return str_replace("\n", '<br />', $v);
}//_br
function _daNet($v) {//$v: 1 -> ��, 0 -> ���
	return $v ? '��' : '���';
}//_daNet
function _iconEdit($v=array()) {//������ �������������� ������ � �������
	$v = array(
		'id' => _num(@$v['id']) ? ' val="'.$v['id'].'"' : ''//id ������
	);

	return '<div'.$v['id'].' class="img_edit'._tooltip('��������', -52, 'r').'</div>';
}
function _iconDel($v=array()) {//������ �������� ������ � �������
	//���� ����������� ���� �������� ������ � ��� �� �������� ����������� ����, �� �������� ����������
	if(!empty($v['nodel']) || !empty($v['dtime_add']) && TODAY != substr($v['dtime_add'], 0, 10))
		return '';

	$v = array(
		'id' => _num(@$v['id']) ? 'val="'.$v['id'].'" ' : '',//id ������
		'class' => !empty($v['class']) ? ' '.$v['class'] : ''//�������������� �����
	);

	return '<div '.$v['id'].'class="img_del'.$v['class']._tooltip('�������', -46, 'r').'</div>';
}
function _dtimeAdd($v=array()) {//���� � ����� �������� ������ � ���������� ����������, ������� ������ ������
	return
		'<div class="'._tooltip(_viewerAdded($v['viewer_id_add']), -40).FullDataTime($v['dtime_add']).'</div>'.
	(@$v['viewer_id_del'] ?
		'<div class="ddel '._tooltip(_viewerDeleted($v['viewer_id_del']), -40).
			FullDataTime($v['dtime_del']).
		'</div>'
	: '');
}

function _ids($ids, $return_arr=0) {//�������� ������������ ������ id, ������������ ����� �������
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
}//_ids
function _idsGet($arr, $i='id') {//����������� �� ������� ������ id ����� �������
	$ids = array();
	foreach($arr as $r)
		if(!empty($r[$i]))
			$ids[] = $r[$i];
	return empty($ids) ? 0 : implode(',', array_unique($ids));
}
function _mon($v) {//�������� ���� � ������� 2015-10, ���� �� �������������, ������� ������� ����
	if(empty($v) || is_array($v) || !preg_match(REGEXP_YEARMONTH, $v))
		return strftime('%Y-%m');
	return $v;
}
function _year($v) {//�������� ����, ���� �� �������������, ������� �������� ����
	if(empty($v) || is_array($v) || !preg_match(REGEXP_YEAR, $v))
		return strftime('%Y');
	return intval($v);
}
function _numToWord($num, $firstSymbolUp=false) {
	$num = intval($num);
	$one = array(
		0 => '����',
		1 => '����',
		2 => '���',
		3 => '���',
		4 => '������',
		5 => '����',
		6 => '�����',
		7 => '����',
		8 => '������',
		9 => '������',
		10 => '��c���',
		11 => '�����������',
		12 => '����������',
		13 => '����������',
		14 => '������������',
		15 => '����������',
		16 => '�����������',
		17 => '����������',
		18 => '������������',
		19 => '������������'
	);
	$ten = array(
		2 => '��������',
		3 => '��������',
		4 => '�����',
		5 => '���������',
		6 => '����������',
		7 => '���������',
		8 => '�����������',
		9 => '���������'
	);
	$hundred = array(
		1 => '���',
		2 => '������',
		3 => '������',
		4 => '���������',
		5 => '�������',
		6 => '��������',
		7 => '�������',
		8 => '���������',
		9 => '���������'
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
		$word = ' �����'._end($t, '�', '�', '').' '.$word;
		if($t % 100 > 2 && $t % 100 < 20)
			$word = $one[$t % 100].$word;
		else {
			if($t % 10 == 1)
				$word = '����'.$word;
			elseif($t % 10 == 2)
				$word = '���'.$word;
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
		$ws: ��������� ���������� � �����������
	*/
	$sql = "SELECT IFNULL(MAX(`".$pole."`)+1,1)
			FROM `".$table."`
			WHERE `id`".
			($ws ? " AND `app_id`=".APP_ID." AND `ws_id`=".WS_ID : '');
	return query_value($sql, $resource_id);
}
function _arrayTimeGroup($arr, $spisok=array()) {//����������� ������� �� ����� ���� ����������
	$send = $spisok;
	foreach($arr as $r) {
		$key = strtotime($r['dtime_add']);
		while(isset($send[$key]))
			$key++;
		$send[$key] = $r;
	}
	return $send;
}

function _filterJs($name, $filter) {//������������ ������� ������ � ������� js
	$filter += array(
		'js_name' => $name,
		'op' => strtolower($name).'_spisok',
		'js' => ''
	);

	//���������� �������, ������� ����� ���������� � ������
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
function _start($v) {//���������� ������ ������� � ���� ������
	return ($v['page'] - 1) * $v['limit'];
}
function _next($v) {//����� ������ �� �������� ������
	$send = '';
	$start = _start($v);
	$page_count = empty($v['page_count']) ? 1 : $v['page_count'];
	if($start + $v['limit'] * $page_count < $v['all']) {
		$c = $v['all'] - $start - ($v['limit'] * $page_count);
		$c = $c > $v['limit'] ? $v['limit'] : $c;

		$type = ' �����'._end($c, '�', '�', '��');
		switch(@$v['type']) {
			case 1: $type = ' ������'._end($c, '�', '�', '��'); break;
			case 2: $type = ' ����'._end($c, '��', '��', '��'); break;
			case 3: $type = ' ������'._end($c, '', '�', '��'); break;
			case 4: $type = ' ��'._end($c, '��', '���', '����'); break;
			case 5: $type = ' ����������'._end($c, '�', '�', '�'); break;
		}

		$show = '<span>�������� ��� '.$c.$type.'</span>';
		$id = empty($v['id']) ? '' : ' id="'.$v['id'].'"';
		$page = $v['page'] + $page_count;
		$js_name = empty($v['js_name']) ? '' : $v['js_name'].':';//���������� ���������� js, ���������� ������� ������. ����� ��������� ��� ����� ��������
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

function _arr($arr, $i=false) {//���������������� ������
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
function _assJson($arr) {//������������� ������
	$send = array();
	foreach($arr as $id => $v)
		$send[] =
			(preg_match(REGEXP_NUMERIC, $id) ? $id : '"'.$id.'"').
			':'.
			(preg_match(REGEXP_CENA, $v) ? $v : '"'.$v.'"');
	return '{'.implode(',', $send).'}';
}
function _arrJson($arr, $i=false) {//���������������� ������
	$send = array();
	foreach($arr as $r) {
		$v = $i === false ? $r : $r[$i];
		$send[] = preg_match(REGEXP_CENA, $v) ? $v : '"'.addslashes(htmlspecialchars_decode($v)).'"';
	}
	return '['.implode(',', $send).']';
}

function Gvalues_obj($table, $sort='name', $category_id='category_id', $resource_id=MYSQL_CONNECT, $ws=0) {//������������� ������ ������������
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
function _globalValuesJS($ws_id=WS_ID) {//����������� ����� global_values.js, ������������ �� ���� �����������
	//���������� ��� ���� ����������:
	$save =
		 'var CLIENT_CATEGORY_ASS='._assJson(_clientCategory(0,1)).','.
 		"\n".'COLOR_SPISOK='.query_selJson("SELECT `id`,`name` FROM `_setup_color` ORDER BY `name`", GLOBAL_MYSQL_CONNECT).','.
		"\n".'COLORPRE_SPISOK='.query_selJson("SELECT `id`,`predlog` FROM `_setup_color` ORDER BY `predlog`", GLOBAL_MYSQL_CONNECT).','.
		"\n".'COUNTRY_SPISOK=['.
				'{uid:1,title:"������"},'.
				'{uid:2,title:"�������"},'.
				'{uid:3,title:"��������"},'.
				'{uid:4,title:"���������"},'.
				'{uid:5,title:"�����������"},'.
				'{uid:6,title:"�������"},'.
				'{uid:7,title:"������"},'.
				'{uid:8,title:"�������"},'.
				'{uid:11,title:"����������"},'.
				'{uid:12,title:"������"},'.
				'{uid:13,title:"�����"},'.
				'{uid:14,title:"�������"},'.
				'{uid:15,title:"�������"},'.
				'{uid:16,title:"�����������"},'.
				'{uid:17,title:"���������"},'.
				'{uid:18,title:"����������"}],'.
		"\n".'COUNTRY_ASS=_toAss(COUNTRY_SPISOK),'.
		"\n".'CITY_SPISOK=['.
				'{uid:1,title:"������",content:"<b>������</b>"},'.
				'{uid:2,title:"�����-���������",content:"<b>�����-���������</b>"},'.
				'{uid:35,title:"������� ��������"},'.
				'{uid:10,title:"���������"},'.
				'{uid:49,title:"������������"},'.
				'{uid:60,title:"������"},'.
				'{uid:61,title:"�����������"},'.
				'{uid:72,title:"���������"},'.
				'{uid:73,title:"����������"},'.
				'{uid:87,title:"��������"},'.
				'{uid:95,title:"������ ��������"},'.
				'{uid:99,title:"�����������"},'.
				'{uid:104,title:"����"},'.
				'{uid:110,title:"�����"},'.
				'{uid:119,title:"������-��-����"},'.
				'{uid:123,title:"������"},'.
				'{uid:125,title:"�������"},'.
				'{uid:151,title:"���"},'.
				'{uid:158,title:"���������"}];';
	$fp = fopen(API_PATH.'/js/global_values.js', 'w+');
	fwrite($fp, $save);
	fclose($fp);

	//���������� �������� ������ ������ global_values.js
	$sql = "UPDATE `_setup`
			SET `value`=`value`+1
			WHERE `app_id`=".APP_ID."
			  AND `key`='GLOBAL_VALUES'";
	query($sql, GLOBAL_MYSQL_CONNECT);

	//��� ����������� ����������
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
		_zayavPoleUseInfoConst(1).
		"\n".'ZAYAV_EXPENSE_DOP='._selJson(_zayavExpenseDop()).','.
		"\n".'ZAYAV_EXPENSE_SPISOK='.query_selJson("SELECT `id`,`name` FROM `_zayav_expense_category` WHERE `app_id`=".APP_ID." ORDER BY `sort`", GLOBAL_MYSQL_CONNECT).','.
		"\n".'ZAYAV_EXPENSE_TXT='.   query_assJson("SELECT `id`,1 FROM `_zayav_expense_category` WHERE `app_id`=".APP_ID." AND `dop`=1", GLOBAL_MYSQL_CONNECT).','.
		"\n".'ZAYAV_EXPENSE_WORKER='.query_assJson("SELECT `id`,1 FROM `_zayav_expense_category` WHERE `app_id`=".APP_ID." AND `dop`=2", GLOBAL_MYSQL_CONNECT).','.
		"\n".'ZAYAV_EXPENSE_ZP='.    query_assJson("SELECT `id`,1 FROM `_zayav_expense_category` WHERE `app_id`=".APP_ID." AND `dop`=3", GLOBAL_MYSQL_CONNECT).','.
		"\n".'ZAYAV_EXPENSE_ATTACH='.query_assJson("SELECT `id`,1 FROM `_zayav_expense_category` WHERE `app_id`=".APP_ID." AND `dop`=4", GLOBAL_MYSQL_CONNECT).','.
		"\n".'PRODUCT_SPISOK='.query_selJson("SELECT `id`,`name` FROM `_product` WHERE `app_id`=".APP_ID." AND `ws_id`=".$ws_id." ORDER BY `name`", GLOBAL_MYSQL_CONNECT).','.
		"\n".'PRODUCT_ASS=_toAss(PRODUCT_SPISOK),'.
		"\n".'PRODUCT_SUB_SPISOK='.Gvalues_obj('_product_sub', '`product_id`,`name`', 'product_id', GLOBAL_MYSQL_CONNECT, 1).';';


	$fp = fopen(APP_PATH.'/js/ws_'.$ws_id.'_values.js', 'w+');
	fwrite($fp, $save);
	fclose($fp);


	//���������� �������� ������ ������ global_values.js � ws_values.js
	$sql = "UPDATE `_setup`
			SET `value`=`value`+1
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".$ws_id."
			  AND `key`='WS_VALUES'";
	query($sql, GLOBAL_MYSQL_CONNECT);

	xcache_unset(CACHE_PREFIX.'setup'.$ws_id);
}

function _globalCacheClear($ws_id=WS_ID) {//������� ���������� �������� ����
	xcache_unset(CACHE_PREFIX.'menu');  //������ �������� ����
	xcache_unset(CACHE_PREFIX.'menu_app');//�������� ��� �������� ���� ��� ����������� ����������
	xcache_unset(CACHE_PREFIX.'menu_sort');//��������������� ������ �������� ���� � �����������
	xcache_unset(CACHE_PREFIX.'setup'.$ws_id);//���������� ��������� ���������� ���������� �����������
	xcache_unset(CACHE_PREFIX.'setup_color');//�����
	xcache_unset(CACHE_PREFIX.'viewer_rule_default_admin');//��������� ���� �� ��������� ��� ������������
	xcache_unset(CACHE_PREFIX.'viewer_rule_default_worker');//��������� ���� �� ��������� ��� �����������
	xcache_unset(CACHE_PREFIX.'balans_action');//�������� ��� ��������� �������
	xcache_unset(CACHE_PREFIX.'ws'.$ws_id);//������ �����������
	xcache_unset(CACHE_PREFIX.'invoice'.$ws_id);//��������� �����
	xcache_unset(CACHE_PREFIX.'expense'.$ws_id);//��������� �������� �����������
	xcache_unset(CACHE_PREFIX.'zayav_expense'.$ws_id);//��������� �������� ������
	xcache_unset(CACHE_PREFIX.'product'.$ws_id);
	xcache_unset(CACHE_PREFIX.'product_sub'.$ws_id);


	//����� ������� �������� ��������� �������
//		unset($_SESSION[PIN_TIME_KEY]);

	//������� ���� �������� ������������
	xcache_unset(CACHE_PREFIX.'viewer_'.VIEWER_ID);
	xcache_unset(CACHE_PREFIX.'viewer_rule_'.VIEWER_ID);

	//������� ���� ����������� ����������
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

function pageHelpIcon() {
	return '';
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
		(SA && !$id ? '<div class="pagehelp_create" val="'.$page.'">�������� ���������</div>' : '');
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
function _sumSpace($sum) {//���������� ����� � �������� ���� � ���������
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
function mb_ucfirst($txt) {//������� ��������� ������ ����� ������
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

function _monthFull($n=0) {
	$mon = array(
		1 => '������',
		2 => '�������',
		3 => '�����',
		4 => '������',
		5 => '���',
		6 => '����',
		7 => '����',
		8 => '�������',
		9 => '��������',
		10 => '�������',
		11 => '������',
		12 => '�������'
	);
	return $n ? $mon[intval($n)] : $mon;
}
function _monthDef($n=0, $firstUp=false) {
	$mon = array(
		1 => '������',
		2 => '�������',
		3 => '����',
		4 => '������',
		5 => '���',
		6 => '����',
		7 => '����',
		8 => '������',
		9 => '��������',
		10 => '�������',
		11 => '������',
		12 => '�������'
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
}
function _monthCut($n) {
	$mon = array(
		0 => '',
		1 => '���',
		2 => '���',
		3 => '���',
		4 => '���',
		5 => '���',
		6 => '���',
		7 => '���',
		8 => '���',
		9 => '���',
		10 => '���',
		11 => '���',
		12 => '���'
	);
	return $mon[intval($n)];
}
function _week($n) {
	$week = array(
		1 => '��',
		2 => '��',
		3 => '��',
		4 => '��',
		5 => '��',
		6 => '��',
		0 => '��'
	);
	return $week[intval($n)];
}
function FullData($v=0, $noyear=0, $cut=0, $week=0) {//��. 14 ������ 2010
	if(!$v)
		$v = curTime();
	$d = explode('-', $v);
	return
		($week ? _week(date('w', strtotime($v))).'. ' : '').
		abs($d[2]).' '.
		($cut ? _monthCut($d[1]) : _monthFull($d[1])).
		(!$noyear || date('Y') != $d[0] ? ' '.$d[0] : '');
}
function FullDataTime($v=0, $cut=0) {//14 ������ 2010 � 12:45
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
		' � '.$t[0].':'.$t[1];
}
function _curMonday() { //����������� � ������� ������
	// ����� �������� ��� ������
	$time = time();
	$curDay = date("w", $time);
	if($curDay == 0) $curDay = 7;
	// ���������� ��� � ������������
	$time -= 86400 * ($curDay - 1);
	return strftime('%Y-%m-%d', $time);
}
function _curSunday() { //����������� � ������� ������
	$time = time();
	$curDay = date("w", $time);
	if($curDay == 0) $curDay = 7;
	$time += 86400 * (7 - $curDay);
	return strftime('%Y-%m-%d', $time);

}
function _dataDog($v) {//������ ���� ��� ��� ��������
	$d = explode('-', $v);
	return $d[2].'/'.$d[1].'/'.$d[0];
}

function _engRusChar($word) { //������� �������� ��������� � ����������� �� �������
	$char = array(
		'`' => '�',
		'�' => '�',
		'q' => '�',
		'w' => '�',
		'e' => '�',
		'r' => '�',
		't' => '�',
		'y' => '�',
		'u' => '�',
		'i' => '�',
		'o' => '�',
		'p' => '�',
		'[' => '�',
		'{' => '�',
		']' => '�',
		'}' => '�',
		'a' => '�',
		's' => '�',
		'd' => '�',
		'f' => '�',
		'g' => '�',
		'h' => '�',
		'j' => '�',
		'k' => '�',
		'l' => '�',
		';' => '�',
		"'" => '�',
		'z' => '�',
		'x' => '�',
		'c' => '�',
		'v' => '�',
		'b' => '�',
		'n' => '�',
		'm' => '�',
		',' => '�',
		'.' => '�',
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
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�',
		'�' => '�'
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
	$russian_chars = '� � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � � �';
	$e = explode(' ', $escape_chars);
	$r = explode(' ', $russian_chars);
	$rus_array = explode('%u', $str);
	$new_word = str_replace($e, $r, $rus_array);
	$new_word = str_replace('%20', ' ', $new_word);
	return implode($new_word);
}
function translit($str) {
	$list = array(
		'�' => 'A',
		'�' => 'B',
		'�' => 'V',
		'�' => 'G',
		'�' => 'D',
		'�' => 'E',
		'�' => 'J',
		'�' => 'Z',
		'�' => 'I',
		'�' => 'Y',
		'�' => 'K',
		'�' => 'L',
		'�' => 'M',
		'�' => 'N',
		'�' => 'O',
		'�' => 'P',
		'�' => 'R',
		'�' => 'S',
		'�' => 'T',
		'�' => 'U',
		'�' => 'F',
		'�' => 'H',
		'�' => 'TS',
		'�' => 'CH',
		'�' => 'SH',
		'�' => 'SCH',
		'�' => '',
		'�' => 'YI',
		'�' => '',
		'�' => 'E',
		'�' => 'YU',
		'�' => 'YA',
		'�' => 'a',
		'�' => 'b',
		'�' => 'v',
		'�' => 'g',
		'�' => 'd',
		'�' => 'e',
		'�' => 'j',
		'�' => 'z',
		'�' => 'i',
		'�' => 'y',
		'�' => 'k',
		'�' => 'l',
		'�' => 'm',
		'�' => 'n',
		'�' => 'o',
		'�' => 'p',
		'�' => 'r',
		'�' => 's',
		'�' => 't',
		'�' => 'u',
		'�' => 'f',
		'�' => 'h',
		'�' => 'ts',
		'�' => 'ch',
		'�' => 'sh',
		'�' => 'sch',
		'�' => 'y',
		'�' => 'yi',
		'�' => '',
		'�' => 'e',
		'�' => 'yu',
		'�' => 'ya',
		' ' => '_',
		'�' => 'N',
		'�' => ''
	);
	return strtr($str, $list);
}

function _calendarFilter($data=array()) {
	$data = array(
		'upd' => empty($data['upd']), // ��������� ������������ ���������? (��� ��������� �������)
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
				'<td>��<td>��<td>��<td>��<td>��<td>��<td>��';

	$unix = strtotime($data['month'].'-01');
	$dayCount = date('t', $unix);   // ���������� ���� � ������
	$week = date('w', $unix);       // ����� ������� ��� ������
	if(!$week)
		$week = 7;

	$curDay = strftime('%Y-%m-%d');
	$curUnix = strtotime($curDay); // ������� ���� ��� ��������� ��������� ����
	$weekNum = intval(date('W', $unix));    // ����� ������ � ������ ������

	$range = _calendarWeek($data['month'].'-01');
	$send .= '<tr'.($range == $data['sel'] ? ' class="sel"' : '').'>'.
		($data['noweek'] ? '' : '<td class="week-num" val="'.$range.'">'.$weekNum);
	for($n = $week; $n > 1; $n--, $send .= '<td>'); // ������� ������ �����, ���� ������ ���� ������ �� �����������
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
		for($n = $week; $n <= 7; $n++, $send .= '<td>'); // ������� ������ �����, ���� ���� ������������� �� ������������
	$send .= '</table>'.($data['upd'] ? '</div></div>' : '');

	return $send;
}
function _calendarDataCheck($data) {
	if(empty($data))
		return false;
	if(preg_match(REGEXP_DATE, $data) || preg_match(REGEXP_YEARMONTH, $data) || preg_match(REGEXP_YEAR, $data))
		return true;
	$ex = explode(':', $data);
	if(preg_match(REGEXP_DATE, $ex[0]) && preg_match(REGEXP_DATE, @$ex[1]))
		return true;
	return false;
}
function _calendarPeriod($data) {// ������������ ������� ��� ��������� ������� ������������ �������
	$send = array(
		'period' => $data,
		'day' => '',
		'from' => '',
		'to' => ''
	);
	if(!_calendarDataCheck($data))
		return $send;
	$ex = explode(':', $data);
	if(empty($ex[1]))
		return array('day'=>$ex[0]) + $send;
	return array(
		'from' => $ex[0],
		'to' => $ex[1]
	) + $send;
}
function _calendarWeek($day=0) {// ������������ ������� �� ������ ������
	if(!$day)
		$day = strftime('%Y-%m-%d');
	$d = explode('-', $day);
	$month = $d[0].'-'.$d[1];

	$unix = strtotime($day);
	$dayCount = date('t', $unix);   // ���������� ���� � ������
	$week = date('w', $unix);
	if(!$week)
		$week = 7;

	$dayStart = $d[2] - $week + 1; // ����� ������� ��� ������
	if($dayStart < 1) {
		$back = $d[1] - 1;
		$back = !$back ? ($d[0] - 1).'-12' : $d[0].'-'.($back < 10 ? 0 : '').$back;
		$start = $back.'-'.(date('t', strtotime($back.'-01')) + $dayStart);
	} else
		$start = $month.'-'.($dayStart < 10 ? 0 : '').$dayStart;

	$dayEnd = 7 - $week + $d[2]; // ����� ���������� ��� ������
	if($dayEnd > $dayCount) {
		$next = $d[1] + 1;
		$next = $next > 12 ? ($d[0] + 1).'-01' : $d[0].'-'.($next < 10 ? 0 : '').$next;
		$end = $next.'-0'.($dayEnd - $dayCount);
	} else
		$end = $month.'-'.($dayEnd < 10 ? 0 : '').$dayEnd;

	return $start.':'.$end;
}
function _period($v=0, $action='get') {// ������������ ������� ��� ��������� ������� ������������ �������
	/*
		$i: get, sql
	*/

	if(empty($v))
		$v = _calendarWeek();

	switch($action) {
		case 'get': return $v;
		case 'sql':
			$ex = explode(':', $v);
			if(empty($ex[1]))
				return " AND `dtime_add` LIKE '".$v."%'";
			return " AND `dtime_add`>='".$ex[0]." 00:00:00' AND `dtime_add`<='".$ex[1]." 23:59:59'";
		default: return '';
	}
}

function _imageAdd($v=array()) {
	$v = array(
		'txt' => empty($v['txt']) ? '�������� �����������' : $v['txt'],
		'owner' => empty($v['owner']) || !preg_match(REGEXP_WORD, $v['owner']) ? '' : $v['owner'],
		'max' => empty($v['max']) || !_num($v['owner']) ? 8 : $v['max'] // ������������ ���������� ����������� �����������
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
}
function _imageSpisok($owner) {
	if(!$owner)
		return '';
	$sql = "SELECT * FROM `images` WHERE !`deleted` AND `owner`='".$owner."' ORDER BY `sort`";
	$q = query($sql);
	$send = '';
	while($r = mysql_fetch_assoc($q))
		$send .= '<a class="_iview" val="'.$r['id'].'">'.
					'<div class="del'._tooltip('�������', -29).'<em></em></div>'.
					'<img src="'.$r['path'].$r['small_name'].'" />'.
				'</a>';
	return $send;
}
function _imageCookie($v) {//��������� cookie ����� �������� ����������� � �����
	if(isset($v['error']))
		$cookie = 'error_'.$v['error'];
	else {
		$cookie = 'uploaded_';
		setcookie('_param', $v['link'].'_'.$v['id'].'_'.$v['max'], time() + 3600, '/');
	}
	setcookie('_upload', $cookie, time() + 3600, '/');
	exit;
}
function _imageNameCreate() {
	$arr = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','1','2','3','4','5','6','7','8','9','0');
	$name = '';
	for($i = 0; $i < 10; $i++)
		$name .= $arr[rand(0,35)];
	return $name;
}
function _imageResize($x_cur, $y_cur, $x_new, $y_new) {//��������� ������� ����������� � ����������� ���������
	$x = $x_new;
	$y = $y_new;
	// ���� ������ ������ ��� ����� ������
	if ($x_cur >= $y_cur) {
		if ($x > $x_cur) { $x = $x_cur; } // ���� ����� ������ ������, ��� ��������, �� X ������� ��������
		$y = round($y_cur / $x_cur * $x);
		if ($y > $y_new) { // ���� ����� ������ � ����� �������� ������ ��������, �� ������������� �� Y
			$y = $y_new;
			$x = round($x_cur / $y_cur * $y);
		}
	}

	// ���� ������ ������ ������
	if ($y_cur > $x_cur) {
		if ($y > $y_cur) { $y = $y_cur; } // ���� ����� ������ ������, ��� ��������, �� Y ������� ��������
		$x = round($x_cur / $y_cur * $y);
		if ($x > $x_new) { // ���� ����� ������ � ����� �������� ������ ��������, �� ������������� �� X
			$x = $x_new;
			$y = round($y_cur / $x_cur * $x);
		}
	}

	return array(
		'x' => $x,
		'y' => $y
	);
}
function _imageImCreate($im, $x_cur, $y_cur, $x_new, $y_new, $name) {//������ �����������
	$send = _imageResize($x_cur, $y_cur, $x_new, $y_new);

	$im_new = imagecreatetruecolor($send['x'], $send['y']);
	imagecopyresampled($im_new, $im, 0, 0, 0, 0, $send['x'], $send['y'], $x_cur, $y_cur);
	imagejpeg($im_new, $name, 80);
	imagedestroy($im_new);

	return $send;
}
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

	if(empty($v['owner']))
		return array();

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
}

function _attachValToList($arr) {//������� ������ �� ����� � ������ �� attach_id
	$ids = array();
	$arrIds = array();
	foreach($arr as $key => $r)
		if(!empty($r['attach_id'])) {
			$ids[$r['attach_id']] = 1;
			$arrIds[$r['attach_id']][] = $key;
		}
	if(empty($ids))
		return $arr;

	$sql = "SELECT *
			FROM `_attach`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `id` IN (".implode(',', array_keys($ids)).")";
	$attach = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	foreach($attach as $r) {
		foreach($arrIds[$r['id']] as $id) {
			$arr[$id] += array(
				'attach_link' => '<a href="'.$r['link'].'" class="_attach-link" val="'.$r['id'].'">'.$r['name'].'</a>',
			);
		}
	}

	return $arr;
}
function _attachJs($v=array()) {//��������� ������ �� ����� � javascript
	$v = array(
		'id' => _num(@$v['id']),
		'array' => _num(@$v['array']), //�������� ������ �������� ����� ajax
		'zayav_id' => _num(@$v['zayav_id'])
	);

	$sql = "SELECT *
			FROM `_attach`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID.
			($v['zayav_id'] ? " AND `zayav_id`=".$v['zayav_id'] : '').
			($v['id'] ? " AND `id`=".$v['id'] : '');
	$attach = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	$send = array();
	$array = array();
	foreach($attach as $r) {
		$send[] =
			$r['id'].':{'.
				'name:"'.addslashes($r['name']).'",'.
				'link:"'.addslashes($r['link']).'",'.
				'size:'.$r['size'].
			'}';
		$array[intval($r['id'])] = array(
			'name' => utf8($r['name']),
			'link' => $r['link'],
			'size' => $r['size']
		);
	}

	if($v['array'])
		return $array;

	return
	'<script type="text/javascript">'.
		'var ATTACH={'.implode(',', $send).'};'.
	'</script>';
}
function _attachArr($id) {//��������� ������ � ����� � ������� json ��� ajax
	$sql = "SELECT *
			FROM `_attach`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `id`=".$id;
	if($r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		return array(
			'name' => utf8($r['name']),
			'link' => $r['link'],
			'size' => $r['size']
		);

	return array();
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

function _print_document() {//����� �� ������ ����������
	set_time_limit(10);
	require_once GLOBAL_DIR.'/excel/PHPExcel.php';
	require_once GLOBAL_DIR.'/word/clsMsDocGenerator.php';

	switch(@$_GET['d']) {
		case 'schet':
			require_once GLOBAL_DIR.'/view/xsl/schet_xsl.php';
			break;
		case 'receipt': _incomeReceiptPrint(); break;
		default: die('�������� �� ������.');
	}
	exit;
}
