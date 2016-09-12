<?php
/*
//[a-zA-Z_0-9]*\(\)$
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

require_once GLOBAL_DIR.'/modul/client/client.php';
require_once GLOBAL_DIR.'/modul/zayav/zayav.php';
require_once GLOBAL_DIR.'/modul/tovar/tovar.php';
require_once GLOBAL_DIR.'/view/money.php';
require_once GLOBAL_DIR.'/view/history.php';
require_once GLOBAL_DIR.'/view/remind.php';
require_once GLOBAL_DIR.'/view/salary.php';
require_once GLOBAL_DIR.'/view/setup.php';
require_once GLOBAL_DIR.'/view/manual.php';
require_once GLOBAL_DIR.'/view/sa.php';
require_once GLOBAL_DIR.'/modul/devstory/devstory.php';

_dbConnect('GLOBAL_');  //����������� � ���� ������


function _const() {
	if(!$app_id = _num(@$_GET['api_id']))
		_appError();
	if(!$viewer_id = _num(@$_GET['viewer_id']))
		_appError();

	define('VIEWER_ID', $viewer_id);
	define('APP_ID', $app_id);
	define('CACHE_PREFIX', 'CACHE_'.APP_ID.'_');

	session_name('app'.APP_ID);
	session_start();

	define('APP_NAME', _app('app_name'));

	define('VIEWER_MAX', 2147000001);

	define('TODAY', strftime('%Y-%m-%d'));
	define('TODAY_UNIXTIME', strtotime(TODAY));

	define('APP_FIRST_LOAD', !empty($_GET['referrer'])); //������ ������ ����������

	$SA[982006] = 1;    // �������� ������
//	$SA[1382858] = 1; // ����� �.
//	$SA[166424274] = 1; // �������� ������
	define('SA', isset($SA[VIEWER_ID]));

	define('VALUES', TIME.
					 '&api_id='.APP_ID.
					 '&viewer_id='.VIEWER_ID.
					 '&auth_key='.@$_GET['auth_key']
		  );
	//'&access_token='.@$_GET['access_token'] todo �������� ��������

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

	define('IMAGE_PATH', GLOBAL_PATH.'/.image/'.APP_ID);
	define('IMAGE_HTML', '/.vkapp/.image/'.APP_ID);

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
		'</head>'.
		'<body>'.
			'<div id="frameBody">'.
				'<iframe id="frameHidden" name="frameHidden"></iframe>';
}
function _api_scripts() {//������� � �����, ������� ����������� � html
	define('MIN', DEBUG ? '' : '.min');
	return
		//������������ ������ � ��������
		(SA ? '<script type="text/javascript" src="/.vkapp/.js/errors.js"></script>' : '').

		//�������� �������
		'<script type="text/javascript" src="/.vkapp/.js/jquery-2.1.4.min.js"></script>'.
		'<script type="text/javascript" src="/.vkapp/.js/jquery-ui.min.js"></script>'.
		'<script type="text/javascript" src="'.API_HTML.'/js/xd_connection.min.js?20"></script>'.

		//��������� ���������� �������� �������.
		(SA ? '<script type="text/javascript">var TIME=(new Date()).getTime();</script>' : '').

		//��������� ����������� �������� ��� JS
		'<script type="text/javascript">'.
			(LOCAL ? 'for(var i in VK)if(typeof VK[i]=="function")VK[i]=function(){return false};' : '').
			'var VIEWER_ID='.VIEWER_ID.','.
				'VIEWER_ADMIN='.VIEWER_ADMIN.','.
				'VIEWER_INVOICE_ID='._viewer(VIEWER_ID, 'invoice_id_default').','.
				'APP_ID='.APP_ID.','.
				'APP_TYPE=['.APP_TYPE.'],'.
				'URL="'.URL.'",'.
				'AJAX_MAIN="'.AJAX_MAIN.'",'.
				'VALUES="'.VALUES.'";'.
		'</script>'.

		//����������� api VK. ����� VK ������ ������ �� �������� ������ �����
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/vk'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/vk'.MIN.'.js?'.VERSION.'"></script>'.

		//���������� _global ��� ���� ����������
		'<script type="text/javascript" src="'.API_HTML.'/js/values/global.js?'.GLOBAL_VALUES.'"></script>'.

		'<script type="text/javascript" src="'.API_HTML.'/js/values/app_'.APP_ID.'.js?'.APP_VALUES.'"></script>'.

(PIN_ENTER ? '' :

		_client_script().//�������
		_zayav_script(). //������
		_tovar_script(). //������

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

		//�����������
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/image'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/image'.MIN.'.js?'.VERSION.'"></script>'.

		//���������
	(@$_GET['p'] == 'setup' ?
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/setup'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/setup'.MIN.'.js?'.VERSION.'"></script>'
	: '').

		_devstory_script(). //������� ����������

		//�����������
	(@$_GET['p'] == 'manual' ?
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/manual'.MIN.'.css?'.VERSION.'" />'.
		'<script type="text/javascript" src="'.API_HTML.'/js/manual'.MIN.'.js?'.VERSION.'"></script>'
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
		case 'main': return _menuMain();
		case 'client': _menuAccess(1); return _clientCase();
		case 'zayav':  _menuAccess(2); return _zayav();
		case 'tovar':  _menuAccess(8); return _tovar();
		case 'money':  _menuAccess(3); return _money();
		case 'report': _menuAccess(6); return _report();
		case 'setup':  _menuAccess(5); return _setup();
		case 'manual': _menuAccess(10); return _manual();
		case 'devstory': return _devstory();
		case 'print':  _print_document(); exit;
		case 'sa':
			if(!SA) {
				header('Location:'.URL);
				exit;
			}
			if(!empty($_GET['d'])) {
				$f = 'sa_'.$_GET['d'];
				if(function_exists($f))
					return $f();
			}
			switch(@$_GET['d']) {
				default:            return sa_global_index();
				case 'historycat':  return sa_history_cat();
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

	return
			_devstory_footer().
			_debug().
			'<script type="text/javascript">hashSet({'.implode(',', $v).'});</script>'.
		'</div>'.
//		_footerYandexMetrika().
		_footerGoogleAnalytics().
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
function _footerGoogleAnalytics() {
	if(LOCAL || SA)
		return '';

	return
	'<script>'.
		"(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){".
		"(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),".
		"m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)".
		"})(window,document,'script','//www.google-analytics.com/analytics.js','ga');".

		"ga('create', 'UA-73713608-1', 'auto');".
		"ga('send', 'pageview');".
	"</script>";
}

function _app($i='all') {//��������� ������ � ����������
	$key = CACHE_PREFIX.'app';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT *
				FROM `_app`
				WHERE `id`=".APP_ID;
		if(!$arr = query_assoc($sql))
			_appError('���������� ��������� ������ ���������� ��� ����.');
		xcache_set($key, $arr, 86400);
	}

	if(!defined('APP_VALUES')) {
		define('APP_VALUES', $arr['js_values']);
		define('APP_TYPE', $arr['type_id']);
	}

	if($i == 'all')
		return $arr;

	if(!isset($arr[$i]))
		return '_app: ����������� ���� <b>'.$i.'</b>';

	return $arr[$i];
}
function _appAuth() {//�������� ����������� � ����������
	_app();
	_getVkUser(); //��������� ������ � ������������, �������� � ����, ���� ���, ���������� ���� �������

	if(!VIEWER_WORKER)
		_appError('���������� ��������� ���� � ����������.');

	if(!RULE_APP_ENTER)
		_appError('���������� ��������� ���� � ����������.');

	if(LOCAL)
		return;

	if(@$_GET['auth_key'] != md5(APP_ID.'_'.VIEWER_ID.'_'._app('secret')))
		_appError('������ ����������� ����������.');
}
function _appError($msg='���������� �� ���� ���������.') {//����� ��������� �� ������ ���������� � �����
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
function _appType($i=false, $p=1) {//��� �����������
	/*  $p - �����
			1 - ������������ ���? ���?
			2 - ����������� (���) ���? ����?
			3 - ��������� (����) ����? ����?
			4 - ����������� (����) ����? ���?
			5 - ������������ (�������) ���? ���?
			6 - ���������� (�����) � ���? � ���?

			7 - ���������� ���?
	*/
	$arr[1] = array(
		1 => '��������� �����',
		2 => '����������',
		3 => '�������'
	);

	$arr[2] = array(
		1 => '���������� ������',
		2 => '����������',
		3 => '��������'
	);

	$arr[4] = array(
		1 => '��������� �����',
		2 => '����������',
		3 => '�������'
	);

	$arr[7] = array(
		1 => '� ��������� ������',
		2 => '� ����������',
		3 => '� ��������'
	);

	if($i === false)
		return $arr[$p];

	if(!isset($arr[$p]))
		return '';

	if(!$i)
		return '';

	return $arr[$p][$i];
}


/* ������� �������� ���� */
function _menuCache($type='main') {//��������� ������ �������� ���� �� ����
	$key = CACHE_PREFIX.'menu';
	if(!$menu = xcache_get($key)) {
		$sql = "SELECT `m`.*
				FROM
					`_menu` `m`,
					`_menu_app` `ma`
				WHERE `m`.`id`=`ma`.`menu_id`
				  AND `ma`.`app_id`=".APP_ID."
				ORDER BY `sort`";
		$menu = query_arr($sql);
		xcache_set($key, $menu, 86400);
	}

	foreach($menu as $id => $r)
		if($r['type'] != $type || !_viewerMenuAccess($id))
			unset($menu[$id]);

	return $menu;
}
function _menu() {//������� ��������� ����
	if(@$_GET['p'] == 'sa') return '';
	if(@$_GET['p'] == 'manual') return '';
	if(@$_GET['p'] == 'devstory') return '';

	$link = '';
	foreach(_menuCache() as $r) {
		if($r['p'] == 'manual')
			continue;

		$sel = $r['p'] == $_GET['p'] ? ' sel' : '';
		$main = $r['p'] == 'main' ? ' main' : '';
		if($r['p'] == 'report')
			$r['name'] .= _remindTodayCount(1);
		if($r['p'] == 'money')
			$r['name'] .= _invoiceTransferConfirmCount(1);
		$link .=
			'<a class="p'.$main.$sel.'" href="'.URL.'&p='.$r['p'].'">'.
				($r['p'] == 'main' ? '&nbsp;' : $r['name']).
			'</a>';
	}

	return
	_menuInfoTop().
	'<div id="_menu">'.
		$link.
		_clientDolgSum().
	'</div>';
}
function _menuMain() {//������ ������ ������� ��������
	$send = '';
	foreach(_menuCache() as $r) {
		if($r['p'] == 'main')
			continue;

		if($r['p'] == 'client')
			$r['about'] .=
				'<a href="'.URL.'&p=client&d=poa">������������</a>'.
				'<br />'.
				'<a href="'.URL.'&p=client&d=from">������ ������ ������</a>';
		if($r['p'] == 'zayav')
			$r['about'] .= _menuMainZayav();
		$send .=
		'<div class="mu">'.
			'<a href="'.URL.'&p='.$r['p'].'" class="name">'.$r['name'].'</a>'.
			'<div class="about">'.$r['about'].'</div>'.
		'</div>';
	}

	return '<div id="_menu-main">'.$send.'</div>';
}
function _menuMainZayav() {//����� �� ���������� ������ �� ���� � ������
	$sql = "SELECT COUNT(*)
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `dtime_add` LIKE '".TODAY." %'";
	$today = query_value($sql);

	$sql = "SELECT COUNT(*)
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  "._period(0, 'sql');
	$week = query_value($sql);

	$sql = "SELECT COUNT(*)
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `dtime_add` LIKE '".strftime('%Y-%m')."-%'";
	$mon = query_value($sql);

	return
	'<table class="menu-count">'.
		'<tr><td class="label r">�������:<td><b>'.($today ? $today : '-').'</b>'.
		'<tr><td class="label r">������� ������:<td><b>'.($week ? $week : '-').'</b>'.
		'<tr><td class="label r">'._monthDef(strftime('%m'), true).':<td><b>'.($mon ? $mon : '-').'</b>'.
	'</table>';
}
function _menuAccess($menu_id) {//�������� ������� � ������� ����. ���� ���, �� ��������������� �� ������ ��������
	if(!_viewerMenuAccess($menu_id)) {
		header('Location:'.URL.'&p=main');
		exit;
	}
};

/* ������ ������� - report */
function _report() {
	$d = empty($_GET['d']) ? 'history' : $_GET['d'];
	$pages = array(
		'history' => '������� ��������',
		'remind' => '�����������'._remindTodayCount(1).'<div class="img_add _remind-add"></div>',
		'salary' => '�/� �����������',
		'zayav' => '������',
		'attach' => '�����'
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
		default:
		case 'history':
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
		case 'zayav': return _zayav_report();
		case 'attach':
			$left = _attach_list();
			break;
	}

	return
		'<table class="tabLR" id="report">'.
			'<tr><td class="left">'.$left.
				'<td class="right">'.
					$rightLink.
					$right.
		'</table>';
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
	if(@$_GET['p'] == 'print')
		return;
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

function _noauth($msg='�� ������� ��������� ���� � ����������.') {
	return '<div class="noauth"><div>'.$msg.'</div></div>';
}
function _err($msg='������') {
	return '<div class="_err">'.$msg.'</div>';
}
function _pinCheck() {//����� �������� � ������ ���-����, ���� ��� ���������
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
			'���: '.
			'<input type="password" id="pin" maxlength="10">'.
			'<button class="vk" onclick="pinEnter()">����</button>'.
			'<div class="red">&nbsp;</div>'.
			'<script type="text/javascript">pinLoad('.(PIN_LEN * 7).')</script>'.
		'</div>'.
		_footer();

	die($html);
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
function _cena($v, $minus=0, $kop=0, $del='.') {//�������� �� ����.
	/*
		$minus - ����� �� ���� ���� ���������.
		$kop - ���������� � ���������, ���� ���� 00
		$del - ���� ����� �������
	*/
	if(empty($v) || is_array($v) || !preg_match($minus ? REGEXP_CENA_MINUS : REGEXP_CENA, $v))
		return 0;

	$v = str_replace(',', '.', $v);
	$v = round($v, 2);

	if(!$kop)
		return $v;

	if(!$ost = round($v - floor($v), 2))
		$v .= '.00';
	else
		if(!(($ost * 100) % 10))
			$v .= 0;

	if($del == ',')
		$v = str_replace('.', ',', $v);

	return $v;
}
function _ms($v, $del='.') {//�������� �� ������� ��������� � ������� 0.000
	/*
		$del - ���� ����� �������
	*/
	if(empty($v) || is_array($v) || !preg_match(REGEXP_MS, $v))
		return 0;

	$v = str_replace(',', '.', $v);
	$v = round($v, 3);

	$v = str_replace(',', $del, $v);
	$v = str_replace('.', $del, $v);

	return $v;
}
function _txt($v, $utf8=0) {
	$v = htmlspecialchars(trim($v));
	return $utf8 ? $v : win1251($v);
}
function _br($v) {//������� br � ����� ��� ���������� enter
	return str_replace("\n", '<br />', $v);
}
function _daNet($v) {//$v: 1 -> ��, 0 -> ���
	return $v ? '��' : '���';
}
function _iconEdit($v=array()) {//������ �������������� ������ � �������
	$v = array(
		'id' => _num(@$v['id']) ? ' val="'.$v['id'].'"' : '',//id ������
		'class' => !empty($v['class']) ? ' '.$v['class'] : ''//�������������� �����
	);

	return '<div'.$v['id'].' class="img_edit'.$v['class']._tooltip('��������', -52, 'r').'</div>';
}
function _iconAdd($v=array()) {//������ ���������� ������
	$v = array(
		'id' => _num(@$v['id']) ? ' val="'.$v['id'].'"' : '',//id ������
		'class' => !empty($v['class']) ? ' '.$v['class'] : ''//�������������� �����
	);

	return '<div'.$v['id'].' class="img_add'.$v['class']._tooltip('��������', -51, 'r').'</div>';
}
function _iconDel($v=array()) {//������ �������� ������ � �������
	//���� ����������� ���� �������� ������ � ��� �� �������� ����������� ���, �� �������� ����������
	if(!empty($v['nodel']) || empty($v['del']) && !empty($v['dtime_add']) && TODAY != substr($v['dtime_add'], 0, 10))
		return '';

	$v = array(
		'id' => _num(@$v['id']) ? 'val="'.$v['id'].'" ' : '',//id ������
		'class' => !empty($v['class']) ? ' '.$v['class'] : ''//�������������� �����
	);

	return '<div '.$v['id'].'class="img_del'.$v['class']._tooltip('�������', -46, 'r').'</div>';
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
}
function _idsGet($arr, $i='id') {//����������� �� ������� ������ id ����� �������
/*
	key: ������ id �� �����
*/
	$ids = array();
	foreach($arr as $id => $r) {
		if($i == 'key') {
			$ids[] = $id;
			continue;
		}
		if(!empty($r[$i]))
			$ids[] = $r[$i];
	}
	return empty($ids) ? 0 : implode(',', array_unique($ids));
}
function _idsAss($v) {//��������� ������ id ����: $v[25] = 1; - ��������� ������
	$send = array();
	foreach(_ids($v, 1) as $id)
		$send[$id] = 1;
	return $send;
}

function _keys($arr) {//����������� ������ ����� �������
	return implode(',', array_keys($arr));
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
function _kop($v) {//��������� ������ �� �����
	$v = _cena($v);
	if(!$ost = $v - floor($v))
		return '00 ������';


	$ost = floor($ost * 100);
	return $ost.' ����'._end($ost, '���', '���', '��');
}
function _maxSql($table, $pole='sort', $app=0, $resource_id=GLOBAL_MYSQL_CONNECT) {
	/*
		$ws: ��������� ���������� � �����������
	*/
	$sql = "SELECT IFNULL(MAX(`".$pole."`)+1,1)
			FROM `".$table."`
			WHERE `id`".
			($app ? " AND `app_id`=".APP_ID : '');
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
			'title' => utf8(trim($title))
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
function _selArray($arr) {//������ ��� _select ��� �������� ����� ajax
	$send = array();
	foreach($arr as $uid => $title) {
		$send[] = array(
			'uid' => $uid,
			'title' => utf8(addslashes(htmlspecialchars_decode(trim($title))))
		);
	}
	return $send;
}
function _assJson($arr) {//������������� ������
	$send = array();
	foreach($arr as $id => $v)
		$send[] =
			(preg_match(REGEXP_NUMERIC, $id) ? $id : '"'.$id.'"').
			':'.
			(preg_match(REGEXP_NUMERIC, $v) ? $v : '"'.$v.'"');
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

function _fileSize($v) {//���������� ������� ����� � ������, ��, ��
	if($v < 1000)
		return $v.'b';

	$v = round($v / 1024);
	if($v < 1000)
		return $v.'K';
	
	$v = round($v / 1024);
	if($v < 1000)
		return '<b>'.$v.'M</b>';
	
	$v = round($v / 1024);
	return '<b class="red">'.$v.'G</b>';
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

function Gvalues_obj($table, $sort='name', $category_id='category_id', $resource_id=GLOBAL_MYSQL_CONNECT, $app=0) {//������������� ������ ������������
	$cond = $app ? " AND `app_id`=".APP_ID : '';
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
function _globalJsValues() {//����������� ����� global.js, ������������ �� ���� �����������
	//���������� ��� ���� ����������:
	$save =
		 'var VIEWER_MAX='.VIEWER_MAX.','.
		"\n".'CLIENT_CATEGORY_ASS='._assJson(_clientCategory(0,1)).','.
 		"\n".'COLOR_SPISOK='.query_selJson("SELECT `id`,`name` FROM `_setup_color` ORDER BY `name`").','.
		"\n".'COLORPRE_SPISOK='.query_selJson("SELECT `id`,`predlog` FROM `_setup_color` ORDER BY `predlog`").','.
		"\n".'PAY_TYPE='._selJson(_payType()).','.
		"\n".'ZAYAV_SKIDKA_SPISOK='._selJson(_zayavSkidka()).','.
		"\n".'ZE_DOP_NAME='._assJson(_zayavExpenseDop()).','.
		"\n".'RULE_HISTORY_SPISOK='._selJson(_ruleHistoryView()).','.
		"\n".'RULE_INVOICE_TRANSFER_SPISOK='._selJson(_ruleInvoiceTransfer()).','.
		"\n".'TOVAR_MEASURE_SPISOK='._tovarMeasure('js').','.
		"\n".'TOVAR_MEASURE_FRACTION='._tovarMeasure('js_fraction').','.
		"\n".'TOVAR_MEASURE_AREA='._tovarMeasure('js_area').','.
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
	$fp = fopen(API_PATH.'/js/values/global.js', 'w+');
	fwrite($fp, $save);
	fclose($fp);

	$sql = "UPDATE `_setup_global`
			SET `value`=`value`+1
			WHERE `key`='GLOBAL_VALUES'";
	query($sql);
}
function _appJsValues() {//��� ����������� ����������
	$save = 'var'.
		"\n".'INVOICE_SPISOK='._invoice('js').','.
		"\n".'INVOICE_ASS=_toAss(INVOICE_SPISOK),'.
		"\n".'INVOICE_INCOME_INSERT='._invoice('income_insert_js').','.
		"\n".'INVOICE_INCOME_CONFIRM='._invoice('income_confirm_js').','.
		"\n".'INVOICE_EXPENSE_INSERT='._invoice('expense_insert_js').','.
		"\n".'WORKER_SPISOK='._viewer('worker_js').','.
		"\n".'WORKER_ASS=_toAss(WORKER_SPISOK),'.
		"\n".'WORKER_EXECUTER='._zayavExecuterJs().','.
		"\n".'SALARY_PERIOD_SPISOK='._selJson(_salaryPeriod()).','.
		"\n".'EXPENSE_SPISOK='._expense('js').','.
		"\n".'EXPENSE_SUB_SPISOK='._expenseSub('js').','.
		"\n".'SERVICE_ACTIVE_COUNT='._service('active_count').','.  //���������� �������� ������ � �����������
		"\n".'SERVICE_ACTIVE_ASS='._service('js').','.              //���� �������� ������ � �����������
		"\n".'CLIENT_FROM_SPISOK='._clientFromJs().','.
		"\n".'CLIENT_FROM_USE='._app('client_from_use').','.
		"\n".'CLIENT_FROM_REQUIRE='._app('client_from_require').','.
		"\n".'ZAYAV_EXPENSE_DOP='._selJson(_zayavExpenseDop()).','.
		"\n".'ZAYAV_EXPENSE_SPISOK='._zayavExpense('js').','.
		"\n".'ZE_DOP_ASS='._zayavExpense('dop_ass').','.
		"\n".'ZAYAV_STATUS_NAME_SPISOK='._zayavStatus('js_name').','.
		"\n".'ZAYAV_STATUS_NAME_ASS=_toAss(ZAYAV_STATUS_NAME_SPISOK),'.
		"\n".'ZAYAV_STATUS_COLOR_ASS='._zayavStatus('js_color_ass').','.
		"\n".'ZAYAV_STATUS_ABOUT_ASS='._zayavStatus('js_about_ass').','.
		"\n".'ZAYAV_STATUS_NOUSE_ASS='._zayavStatus('js_nouse_ass').','.
		"\n".'ZAYAV_STATUS_NEXT='.setup_zayav_status_next_js().','.
		"\n".'ZAYAV_STATUS_EXECUTER_ASS='._zayavStatus('js_executer_ass').','.
		"\n".'ZAYAV_STATUS_SROK_ASS='._zayavStatus('js_srok_ass').','.
		"\n".'ZAYAV_STATUS_ACCRUAL_ASS='._zayavStatus('js_accrual_ass').','.
		"\n".'ZAYAV_STATUS_REMIND_ASS='._zayavStatus('js_remind_ass').','.
		"\n".'ZAYAV_STATUS_DAY_FACT_ASS='._zayavStatus('js_day_fact_ass').','.
		"\n".'ZAYAV_TOVAR_PLACE_SPISOK='._selJson(_zayavTovarPlace()).','.
		"\n".'ZAYAV_POLE_PARAM='._zayavPoleParamJs().','.        //������������ ���.��������� ����� ������
		
		_setup_global('js').
		"\n".'RUBRIC_SPISOK='._rubric('js').','.
		"\n".'RUBRIC_ASS=_toAss(RUBRIC_SPISOK),'.
		"\n".'RUBRIC_SUB_SPISOK='._rubricSub('js').','.
		"\n".'GN_ASS='._gn('js_ass').','.
		"\n".'GN_FIRST='._gn('first').','.
		"\n".'GN_LAST='._gn('last').','.
		"\n".'GAZETA_OBDOP_SPISOK='._obDop('js_name').','.
		"\n".'GAZETA_OBDOP_CENA='._obDop('js_cena').','.
		"\n".'GAZETA_POLOSA_SPISOK='._polosa('js_name').','.
		"\n".'GAZETA_POLOSA_CENA='._polosa('js_cena').','.
		"\n".'GAZETA_POLOSA_POLOSA='._polosa('js_polosa').','.

		"\n".'CARTRIDGE_TYPE='._selJson(_cartridgeType()).','.
		"\n".'CARTRIDGE_SPISOK='.query_selJson("SELECT `id`,`name` FROM `_setup_cartridge` ORDER BY `name`").','.
		"\n".'CARTRIDGE_FILLING='.query_assJson("SELECT `id`,`cost_filling` FROM `_setup_cartridge`").','.
		"\n".'CARTRIDGE_RESTORE='.query_assJson("SELECT `id`,`cost_restore` FROM `_setup_cartridge`").','.
		"\n".'CARTRIDGE_CHIP='.query_assJson("SELECT `id`,`cost_chip` FROM `_setup_cartridge`").','.

		"\n".'TOVAR_CATEGORY_SPISOK='._tovarCategoryJs().','.
		"\n".'TOVAR_VENDOR_SPISOK='._tovarVendorJs().','.
		"\n".'TOVAR_FEATURE_SPISOK='._tovarFeatureJs().','.
		"\n".'TOVAR_POSITION_SPISOK='._selJson(_tovarPosition()).';';


	$fp = fopen(API_PATH.'/js/values/app_'.APP_ID.'.js', 'w+');
	fwrite($fp, $save);
	fclose($fp);

	//���������� �������� ������ ����� app_N.js
	$sql = "UPDATE `_app`
			SET `js_values`=`js_values`+1
			WHERE `id`=".APP_ID;
	query($sql);

	xcache_unset(CACHE_PREFIX.'app');
}

function _globalCacheClear() {//������� ���������� �������� ����
	xcache_unset(CACHE_PREFIX.'app');  //������ ����������
	xcache_unset(CACHE_PREFIX.'setup_global');  //������ �������� ����
	xcache_unset(CACHE_PREFIX.'menu');  //������ �������� ����
	xcache_unset(CACHE_PREFIX.'manual_part');//������� �������
	xcache_unset(CACHE_PREFIX.'manual_part_sub');//���������� �������
	xcache_unset(CACHE_PREFIX.'setup_color');//�����
	xcache_unset(CACHE_PREFIX.'viewer_rule_default_admin');//��������� ���� �� ��������� ��� ������������
	xcache_unset(CACHE_PREFIX.'viewer_rule_default_worker');//��������� ���� �� ��������� ��� �����������
	xcache_unset(CACHE_PREFIX.'balans_action');//�������� ��� ��������� �������
	xcache_unset(CACHE_PREFIX.'service');//���� ������������
	xcache_unset(CACHE_PREFIX.'invoice');//��������� �����
	xcache_unset(CACHE_PREFIX.'expense');//��������� �������� �����������
	xcache_unset(CACHE_PREFIX.'expense_sub');//������������ �������� �����������
	xcache_unset(CACHE_PREFIX.'client_from');//���������, ������ ������ ������
	xcache_unset(CACHE_PREFIX.'zayav_expense');//��������� �������� ������
	xcache_unset(CACHE_PREFIX.'zayav_status');//������� ������
	xcache_unset(CACHE_PREFIX.'tovar_name');
	xcache_unset(CACHE_PREFIX.'tovar_vendor');
	xcache_unset(CACHE_PREFIX.'tovar_category');
	xcache_unset(CACHE_PREFIX.'tovar_feature_name');
	xcache_unset(CACHE_PREFIX.'tovar_equip');
	xcache_unset(CACHE_PREFIX.'tovar_measure');
	xcache_unset(CACHE_PREFIX.'cartridge');
	xcache_unset(CACHE_PREFIX.'rubric');
	xcache_unset(CACHE_PREFIX.'rubric_sub');
	xcache_unset(CACHE_PREFIX.'gn');
	xcache_unset(CACHE_PREFIX.'gazeta_obdop');
	xcache_unset(CACHE_PREFIX.'gazeta_polosa');
	xcache_unset(CACHE_PREFIX.'devstory_part');
	xcache_unset(CACHE_PREFIX.'devstory_keyword');


	//����� ������� �������� ��������� �������
//		unset($_SESSION[PIN_TIME_KEY]);

	//������� ���� �������� ������������
	xcache_unset(CACHE_PREFIX.'viewer_'.VIEWER_ID);
	xcache_unset(CACHE_PREFIX.'viewer_rule_'.VIEWER_ID);
	xcache_unset(CACHE_PREFIX.'viewer_menu_access_'.VIEWER_ID);

	//������� ���� ����������� ����������
	$sql = "SELECT `viewer_id`
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `worker`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		xcache_unset(CACHE_PREFIX.'viewer_'.$r['viewer_id']);
		xcache_unset(CACHE_PREFIX.'viewer_rule_'.$r['viewer_id']);
		xcache_unset(CACHE_PREFIX.'viewer_menu_access_'.$r['viewer_id']);
		xcache_unset(CACHE_PREFIX.'pin_enter_count'.$r['viewer_id']);
	}
}
function _cacheErr($txt='����������� ��������', $i='') {//
	if($i != '')
		$i = ': <b>'.$i.'</b>';
	return '<span class="red">'.$txt.$i.'.</span>';
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

function _engRusChar($word) { //������� �������� ��������� � ����������� �� �������
	$char = array(
		'`' => '�',
		'�' => '�',
		'q' => '�',
		'Q' => '�',
		'w' => '�',
		'W' => '�',
		'e' => '�',
		'E' => '�',
		'r' => '�',
		'R' => '�',
		't' => '�',
		'T' => '�',
		'y' => '�',
		'Y' => '�',
		'u' => '�',
		'U' => '�',
		'i' => '�',
		'I' => '�',
		'o' => '�',
		'O' => '�',
		'p' => '�',
		'P' => '�',
		'[' => '�',
		'{' => '�',
		']' => '�',
		'}' => '�',
		'a' => '�',
		'A' => '�',
		's' => '�',
		'S' => '�',
		'd' => '�',
		'D' => '�',
		'f' => '�',
		'F' => '�',
		'g' => '�',
		'G' => '�',
		'h' => '�',
		'H' => '�',
		'j' => '�',
		'J' => '�',
		'k' => '�',
		'K' => '�',
		'l' => '�',
		'L' => '�',
		';' => '�',
		"'" => '�',
		'z' => '�',
		'Z' => '�',
		'x' => '�',
		'X' => '�',
		'c' => '�',
		'C' => '�',
		'v' => '�',
		'V' => '�',
		'b' => '�',
		'B' => '�',
		'n' => '�',
		'N' => '�',
		'm' => '�',
		'M' => '�',
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
function _check($id, $txt='', $v=0, $light=false) {
	$v = $v ? 1 : 0;
	$light = $light ? ' l' : '';
	$e = $txt ? '' : ' e';
	return
	'<div class="_check check'.$v.$light.$e.'" id="'.$id.'_check">'.
		'<input type="hidden" id="'.$id.'" value="'.$v.'" />'.
		$txt.
	'</div>';
}
function _checkNew($v=array()) {
	$v = array(
		'id' => @$v['id'],
		'txt' => @$v['txt'],
		'value' => _bool(@$v['value']),
		'light' => _bool(@$v['light']) ? ' l' : '',
		'disabled' => _bool(@$v['disabled']) ? ' disabled' : '',
		'block' => _bool(@$v['block']) ? ' block' : ''
	);
	return
	'<div class="_check check'.$v['value'].$v['block'].$v['disabled'].$v['light'].($v['txt'] ? '' : ' e').'" id="'.$v['id'].'_check">'.
		'<input type="hidden" id="'.$v['id'].'" value="'.$v['value'].'" />'.
		$v['txt'].
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
		'<input type="hidden" id="'.$id.'" value="'.$value.'" />'.
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
function mb_ucfirst($txt) {//������� ��������� ������ ����� ������
	mb_internal_encoding('UTF-8');
	$txt = utf8($txt);
	$txt = mb_strtoupper(mb_substr($txt, 0, 1)).mb_substr($txt, 1);
	return win1251($txt);
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

function _payType($type_id=false) {//��� �������
	$arr = array(
		1 => '��������',
		2 => '�����������'
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
			$arr = query_arr($sql);
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
	set_time_limit(300);
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
		case 'zp_zakaz':
			require_once GLOBAL_DIR.'/view/xsl/zp_zakaz.php';
			break;
		case 'schet':
			require_once GLOBAL_DIR.'/view/xsl/schet_xsl.php';
			break;
		case 'receipt': _incomeReceiptPrint(); break;
		case 'salary_list':
			require_once GLOBAL_DIR.'/view/xsl/salary_list.php';
			break;
		case 'radiomaster':
			require_once GLOBAL_DIR.'/view/xsl/price_radiomaster.php';
			break;
		case 'erm':
			require_once GLOBAL_DIR.'/view/xsl/evrookna_report_month.php';
			break;
		default: die('�������� �� ������.');
	}
	exit;
}
