<?php
/*
//[a-zA-Z_0-9]*\(\)$
*/

define('TIME', microtime(true));
define('GLOBAL_DIR', dirname(dirname(dirname(__FILE__))));

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
require_once GLOBAL_DIR.'/modul/debug/debug.php';

require_once GLOBAL_DIR.'/modul/client/client.php';
require_once GLOBAL_DIR.'/modul/zayav/zayav.php';
require_once GLOBAL_DIR.'/modul/tovar/tovar.php';
require_once GLOBAL_DIR.'/modul/money/money.php';
require_once GLOBAL_DIR.'/modul/history/history.php';
require_once GLOBAL_DIR.'/modul/remind/remind.php';
require_once GLOBAL_DIR.'/view/salary.php';
require_once GLOBAL_DIR.'/modul/setup/setup.php';
require_once GLOBAL_DIR.'/modul/manual/manual.php';
require_once GLOBAL_DIR.'/modul/sa/sa.php';
require_once GLOBAL_DIR.'/modul/devstory/devstory.php';

require_once GLOBAL_DIR.'/modul/kupezz/kupezz.php';

require_once GLOBAL_DIR.'/modul/test/test.php';//todo

_dbConnect('GLOBAL_');  //����������� � ���� ������


function _const() {
	if(!$app_id = _num(@$_GET['api_id']))
		_appError();
	if(!$viewer_id = _num(@$_GET['viewer_id']))
		_appError();


	define('VIEWER_ID', $viewer_id);
	define('VIEWER_ID_ADMIN', _num(@$_GET['viewer_id_admin']));//�������������, �������� �� ����� ����������
	define('VIEWER_ONPAY', 2147000001);
	define('APP_ID', $app_id);
	define('CACHE_PREFIX', 'CACHE_'.APP_ID.'_');

	session_name('app'.APP_ID);
	session_start();

	define('APP_NAME', _app('app_name'));

	define('TODAY', strftime('%Y-%m-%d'));
	define('TODAY_UNIXTIME', strtotime(TODAY));

	define('APP_FIRST_LOAD', !empty($_GET['referrer'])); //������ ������ ����������

	$SA[982006] = 1;    // �������� ������
//	$SA[1382858] = 1;   // ����� �.
//	$SA[166424274] = 1; // �������� ������
//	$SA[162549339] = 1; // ���� ���������
	define('SA', isset($SA[VIEWER_ID]));

	define('VALUES', TIME.
					 '&api_id='.APP_ID.
					 '&viewer_id='.VIEWER_ID.
  (VIEWER_ID_ADMIN ? '&viewer_id_admin='.VIEWER_ID_ADMIN : '').
					 '&auth_key='.@$_GET['auth_key']
		  );
	//'&access_token='.@$_GET['access_token'] todo �������� ��������

	define('URL', API_HTML.'/index.php?'.VALUES);
	define('AJAX_MAIN', API_HTML.'/ajax.php?'.VALUES);

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
	define('LIST_VYDACI', APP_ID == 3978722 ? '��� ����������� �����' : '���� ������ �/�');//todo �������. ��� ��������: ��� ����������� �����

	_debugLoad('��������� �����������');
}

function _header() {
	return
		'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'.
		'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">'.

		'<head>'.
			'<meta http-equiv="content-type" content="text/html; charset=windows-1251" />'.
//			'<meta http-equiv="content-type" content="text/html; charset=utf-8" />'.
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
		(SA ? '<script src="/.vkapp/.js/errors.js"></script>' : '').

		//�������� �������
		'<script src="/.vkapp/.js/jquery-2.1.4.min.js"></script>'.
		'<script src="/.vkapp/.js/jquery-ui.min.js"></script>'.

		(LOCAL ?
			'<script src="'.API_HTML.'/js/xd_connection.min.js?21"></script>'
			: 
			'<script src="https://vk.com/js/api/xd_connection.js?2"></script>'.
			'<script>VK.init(function() {},function() {},"5.60");</script>'
		).

		//��������� ���������� �������� �������.
		(SA ? '<script>var TIME=(new Date()).getTime();</script>' : '').

		//��������� ����������� �������� ��� JS
		'<script>'.
			(LOCAL ? 'for(var i in VK)if(typeof VK[i]=="function")VK[i]=function(){return false};' : '').
			'var VIEWER_ID='.VIEWER_ID.','.
				'VIEWER_ADMIN='.VIEWER_ADMIN.','.
				'VIEWER_INVOICE_ID='._viewer(VIEWER_ID, 'invoice_id_default').','.
				'APP_ID='.APP_ID.','.
				'APP_TYPE=['.APP_TYPE.'],'.
				'COOKIE_PREFIX="'.APP_ID.'_'.VIEWER_ID.'_",'.
				'URL="'.URL.'",'.
				'AJAX_MAIN="'.AJAX_MAIN.'",'.
				'VALUES="'.VALUES.'";'.
		'</script>'.

		_global_script().

		//���������� _global ��� ���� ����������
		'<script src="'.API_HTML.'/js/values/global.js?'.GLOBAL_VALUES.'"></script>'.

		'<script src="'.API_HTML.'/js/values/app_'.APP_ID.'.js?'.APP_VALUES.'"></script>'.

		_debug_script().    // debug

(PIN_ENTER ? '' :


		_manual_script().   // ������
		_client_script().   // �������
		_zayav_script().    // ������
		_money_script().    // ������
		_history_script().  // ������� ��������
		_remind_script().   // �����������
		_tovar_script().    // ������
		_setup_script().    // ���������
		_sa_script().       // ���������� (SA)
		_kupezz_script().   // ����� - ���������� ����������

		//�/� �����������
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/salary'.MIN.'.css?'.VERSION.'" />'.
//		'<script src="'.API_HTML.'/js/salary'.MIN.'.js?'.VERSION.'"></script>'.

		//�����������
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/css/image'.MIN.'.css?'.VERSION.'" />'.
		'<script src="'.API_HTML.'/js/image'.MIN.'.js?'.VERSION.'"></script>'.

		_devstory_script(). // ������� ����������

		_test_script()      // �����
);
}
function _global_script() {//������� � �����
	return
		//����� Global
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/global/global'.MIN.'.css?'.VERSION.'" />'.
		'<script src="'.API_HTML.'/modul/global/global'.MIN.'.js?'.VERSION.'"></script>'.

		//����������� api VK. ����� VK ������ ������ �� �������� ������ �����
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/vk/vk'.MIN.'.css?'.VERSION.'" />'.
		'<script src="'.API_HTML.'/modul/vk/vk'.MIN.'.js?'.VERSION.'"></script>';
}
function _global_index() {//���� ��������� �� ������� ���������� ��������
	$menu_id = _num($_GET['p']);

	$sql = "SELECT *
			FROM `_menu`
			WHERE `id`=".$menu_id;
	if(!$menu = query_assoc($sql))
		return _err(!SA ? '�������� �� ����������.' : '�������� �� ������� � ����.');

	if(empty($menu['func_page']))
		return _err(!SA ? '�������� �� ����������.' : '������ �������.');

	if(!function_exists($menu['func_page']))
		return _err(!SA ? '�������� �� ����������.' : '������� �� ����������.');

	//������� ��� �������
	$v = array();
	if($menu_id == 60)
		$v['spisok'] = 1;

	$funcPage = $menu['func_page']($v);
	$noRules = _err('<b>'.$menu['name'].'</b>: ��� ����.');
	if(_menuCache('l3', $menu_id)) {//������ �������� ������: ���� � �������� ��� �������, �� ��� �������
		$parent_id = _menuCache('parent_id', $menu_id);
		if(!_viewerMenuAccess($parent_id))
			$funcPage = $noRules;
	} elseif($menu['parent_id']) {//������ ������� ������
		if(!_viewerMenuAccess($menu['parent_id']) || !_viewerMenuAccess($menu_id))
			$funcPage = $noRules;
	} elseif(!_viewerMenuAccess($menu_id))//������ �������
			$funcPage = $noRules;


	//����������� ��������������� ����
	if($dmt_id = $menu['dop_menu_type']) {
		$dopMenu = '';

		//���� ���������� �� �������� 3-�� ������, �� �������� ���� ������� ������
		if($menu['parent_id'])
			if(_menuCache('parent_main', $menu['parent_id']))
				$menu_id = $menu['parent_id'];

		foreach(_menuCache('dop', $menu_id) as $r) {
			if($r['id'] == 29 && !SA)//��������� - �������
				continue;
			if($r['hidden'])
				continue;
			if(!$r['app_id'])
				continue;
			if(!_viewerMenuAccess($r['id']))
				continue;

			$dopMenu .=
				'<a class="link'.($menu_id == $r['id'] ? ' sel' : '').'" href="'.URL.'&p='.$r['id'].'">'.
					$r['name'].
					($r['id'] == 61 ? _remindTodayCount(1).'<div class="img_add _remind-add"></div>' : '').
				'</a>';
		}
		switch($dmt_id) {
			case 1:
			case 2:
				//���������� ������� � ������ ������� ����� ��� ����
				$filterRight = '';
				if(_viewerMenuAccess($menu_id) && function_exists($menu['func_page'].'_right')) {
					$filterRight = $menu['func_page'] . '_right';
					$filterRight = $filterRight();
				}

				$funcPage =
				'<table class="tabLR">'.
					'<tr><td class="left">'.$funcPage.
						'<td class="right'.($dmt_id == 1 ? ' setup' : '').'">'.
							'<div class="rightLink">'.$dopMenu.'</div>'.
							$filterRight.
				'</table>';
				break;
			case 3:
				$funcPage = '<div id="dopLinks">'.$dopMenu.'</div>'.$funcPage;
				break;
		}

	}	
	
	return
	//����������� ��������� ����
	(!empty($menu['func_menu']) && function_exists($menu['func_menu']) ? $menu['func_menu']() : '').
	$funcPage;
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
			'<script>hashSet({'.implode(',', $v).'});</script>'.
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
		'<script>'.
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

function _app($i='all', $item_id=0, $k='') {//��������� ������ � ����������
	$key = CACHE_PREFIX.'app';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT *
				FROM `_app`
				WHERE `id`=".APP_ID;
		if(!$arr = query_assoc($sql))
			_appError('���������� ��������� ������ ���������� ��� ����.');

		$arr = _app_org($arr);
		$arr = _app_bank($arr);

		//�����������
		$org = array(
			'name' => '',
			'name_yur' => '',
			'phone' => '',
			'fax' => '',
			'adres_yur' => '',
			'adres_ofice' => '',
			'time_work' => '',
			'ogrn' => '',
			'inn' => '',
			'kpp' => '',
			'okud' => '',
			'okpo' => '',
			'okved' => '',
			'post_boss' => '',
			'post_accountant' => ''
		);

		$sql = "SELECT *
				FROM `_setup_org`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$arr['org_default'];
		if($org_assoc = query_assoc($sql)) {
			unset($org_assoc['id']);
			unset($org_assoc['app_id']);
			unset($org_assoc['default']);
			$org = $org_assoc;
		}

		//��������� ����� �� ������
		$schet_pay = array(
			'schet_pay' => 0,
			'schet_prefix' => '',
			'schet_act_date_set' => 0,
			'schet_nomer_start' => 0,
			'schet_invoice_id' => 0
		);

		$sql = "SELECT *
				FROM `_schet_pay_setup`
				WHERE `app_id`=".APP_ID."
				LIMIT 1";
		if($r = query_assoc($sql))
			$schet_pay = array(
				'schet_pay' => _bool($r['use']),
				'schet_prefix' => $r['prefix'],
				'schet_act_date_set' => _bool($r['act_date_set']),
				'schet_nomer_start' => _num($r['nomer_start']),
				'schet_invoice_id' => _num($r['invoice_id_default'])
			);

		$arr += $org + $schet_pay;
		xcache_set($key, $arr, 86400);
	}

	if(!defined('APP_VALUES')) {
		define('APP_VALUES', $arr['js_values']);
		define('APP_TYPE', $arr['type_id']);
	}

	if($i == 'all') {
		_debugLoad('�������� ������ ����������');
		return $arr;
	}
	
	if($i == 'org_menu_js') {//������ ������� ���� � ������� JS
		$sql = "SELECT `id`,`name`
				FROM `_setup_org`
				WHERE `app_id`=".APP_ID."
				ORDER BY `id`";
		return query_selJson($sql);
	}

	if($i == 'org') {//�������� ������  �����������
		if(!$item_id)
			return _cacheErr('_app: �� ������ id �����������');

		if(!$org = @$arr['org_spisok'][$item_id])
			return _cacheErr('_app: id ����������� �� ����������', $item_id);
		
		if(empty($k))
			return _cacheErr('_app: ����������� ���� �����������');
		
		if($k == 'all')
			return $org;
		
		if(!isset($org[$k]))
			return _cacheErr('_app: ����������� ���� �����������', $k);
		
		return $org[$k];
	}

	if($i == 'bank') {//�������� ������  �����������
		if(!$item_id)
			return _cacheErr('_app: �� ������ id �����');

		if(!$bank = @$arr['bank_spisok'][$item_id])
			return _cacheErr('_app: id ����� �� ����������', $item_id);

		if(empty($k))
			return _cacheErr('_app: ����������� ���� �����');

		if($k == 'all')
			return $bank;
		
		if(!isset($bank[$k]))
			return _cacheErr('_app: ����������� ���� �����', $k);

		return $bank[$k];
	}

	if(!isset($arr[$i]))
		return _cacheErr('_app: ����������� ����', $i);

	return $arr[$i];
}
function _app_org($arr) {//��������� ������ �� ������������
	$arr['org_default'] = 0;
	$arr['org_spisok'] = array();

	// 1. ��������� ���������� �����������
	$sql = "SELECT COUNT(*)
			FROM `_setup_org`
			WHERE `app_id`=".APP_ID;
	if(!$arr['org_count'] = _num(query_value($sql)))
		return $arr;

	// 2. ��������� ������ �����������
	$sql = "SELECT *
			FROM `_setup_org`
			WHERE `app_id`=".APP_ID;
	$arr['org_spisok'] = query_arr($sql);

	// 3. ��������� id ����������� �� ���������
	$sql = "SELECT `id`
			FROM `_setup_org`
			WHERE `app_id`=".APP_ID."
			  AND `default`";
	if($arr['org_default'] = _num(query_value($sql)))
		return $arr;

	$sql = "UPDATE `_setup_org`
			SET `default`=1
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`
			LIMIT 1";
	query($sql);

	$sql = "SELECT `id`
			FROM `_setup_org`
			WHERE `app_id`=".APP_ID."
			  AND `default`";
	$arr['org_default'] = _num(query_value($sql));

	return $arr;
}
function _app_bank($arr) {//��������� ����� �� ��������� � ����������� �� ���������. E��� ���, �� ���������
	$arr['bank_default'] = 0;
	$arr['bank_spisok'] = array();
	$arr['bank_bik'] = '';
	$arr['bank_name'] = '';
	$arr['bank_account'] = '';
	$arr['bank_account_corr'] = '';

	if(!$arr['org_count'])
		return $arr;

	if(!$arr['org_default'])
		return $arr;

	// 1. ��������� ������ ������ ���� �����������
	$sql = "SELECT *
			FROM `_setup_org_bank`
			WHERE `app_id`=".APP_ID;
	$arr['bank_spisok'] = query_arr($sql);


	// ���������� ������ � ����������� �� ���������
	$sql = "SELECT COUNT(*)
			FROM `_setup_org_bank`
			WHERE `app_id`=".APP_ID."
			  AND `org_id`=".$arr['org_default'];
	if(!query_value($sql))
		return $arr;


	// 2. ��������� id ����� �� ��������� ����������� �� ���������
	$sql = "SELECT `id`
			FROM `_setup_org_bank`
			WHERE `app_id`=".APP_ID."
			  AND `org_id`=".$arr['org_default']."
			  AND `default`";
	if(!$arr['bank_default'] = _num(query_value($sql))) {
		$sql = "UPDATE `_setup_org_bank`
				SET `default`=1
				WHERE `app_id`=".APP_ID."
				  AND `org_id`=".$arr['org_default']."
				ORDER BY `id`
				LIMIT 1";
		query($sql);

		$sql = "SELECT `id`
				FROM `_setup_org_bank`
				WHERE `app_id`=".APP_ID."
				  AND `org_id`=".$arr['org_default']."
				  AND `default`";
		$arr['bank_default'] =  _num(query_value($sql));
	}

	$bank = $arr['bank_spisok'][$arr['bank_default']];
	$arr['bank_bik'] = $bank['bik'];
	$arr['bank_name'] = $bank['name'];
	$arr['bank_account'] = $bank['account'];
	$arr['bank_account_corr'] = $bank['account_corr'];

	return $arr;
}
function _appAuth() {//�������� ����������� � ����������
	_app();
	_viewerAuth(); //��������� ������ � ������������, �������� � ����, ���� ���, ���������� ���� �������

	if(!_app('enter'))
		_appError('���� � ���������� ������.');

	//��������� �������� �� ���������
	if(!$menu_id = _num(@$_GET['p'])) {
		$menu_id = _menuCache('app_def');
		$_GET['p'] = $menu_id;
	}

	if(!VIEWER_WORKER && !_menuCache('norule', $menu_id))
		_appError('���������� ��������� ���� � ����������.');

	if(!RULE_APP_ENTER && !_menuCache('norule', $menu_id))
		_appError('���� � ���������� ����������.');

	if(LOCAL) {
		_debugLoad('�������� ��������� ����������� ����������');
		return;
	}

	if(@$_GET['auth_key'] != md5(APP_ID.'_'.(VIEWER_ID_ADMIN ? VIEWER_ID_ADMIN : VIEWER_ID).'_'._app('secret')))
		_appError('������ ����������� ����������.');

	_debugLoad('�������� ����������� ����������');
}
function _appError($msg='���������� �� ���� ���������.') {//����� ��������� �� ������ ���������� � �����
	if(!defined('VERSION')) {
		define('VERSION', 141);
		define('MIN', defined('DEBUG') ? '' : '.min');
	}
	$html =
		'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'.
		'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">'.
			'<head>'.
				'<meta http-equiv="content-type" content="text/html; charset=windows-1251" />'.
				'<title>Error</title>'.

				'<script src="/.vkapp/.js/jquery-2.1.4.min.js"></script>'.
				'<script src="https://vk.com/js/api/xd_connection.js?2"></script>'.
				'<script>VK.init(function() {},function() {},"5.60");</script>'.

				_global_script().

			'</head>'.
			'<body>'.
				'<div id="frameBody">'.
					'<iframe id="frameHidden" name="frameHidden"></iframe>'.
					setup_workerEnterMsg(1).
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
		3 => '�������',
		4 => '��������',
		5 => '��������� ���',
		6 => '����'
	);

	$arr[2] = array(
		1 => '���������� ������',
		2 => '����������',
		3 => '��������',
		4 => '��������',
		5 => '���������� ����',
		6 => '�����'
	);

	$arr[4] = array(
		1 => '��������� �����',
		2 => '����������',
		3 => '�������',
		4 => '��������',
		5 => '��������� ���',
		6 => '����'
	);

	$arr[7] = array(
		1 => '� ��������� ������',
		2 => '� ����������',
		3 => '� ��������',
		4 => '� ��������',
		5 => '� ��������� ����',
		6 => '� �����'
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
function _menuCache($i='all', $v=0) {//��������� ������ �������� ���� �� ����
	$key = CACHE_PREFIX.'menu';
	if(!$menu = xcache_get($key)) {
		$sql = "SELECT
					`m`.*,
					IFNULL(`ma`.`app_id`,0) `app_id`,
					IFNULL(`ma`.`def`,0) `def`
				FROM `_menu` `m`
				
					LEFT JOIN `_menu_app` `ma`
					ON `m`.`id`=`ma`.`menu_id`
					AND `ma`.`app_id`=".APP_ID."

				ORDER BY `m`.`sort`";
		$menu = query_arr($sql);
		xcache_set($key, $menu, 86400);
	}

	//�����������, �������� �� �������� �������� ��������
	if($i == 'parent_main') {
		if(!$v = _num($v))
			return false;
		if(!isset($menu[$v]))
			return false;
		//��� �������� ������
		if(!$parent_id = _num($menu[$v]['parent_id']))
			return false;
		//��� ������ 3-�� ������
		if($menu[$parent_id]['parent_id'])
			return false;
		return true;
	}

	//�����������, �������� �� �������� 3-�� ������
	if($i == 'l3') {
		if(!$v = _num($v))
			return false;
		if(!isset($menu[$v]))
			return false;
		//��� �������� ������
		if(!$parent_id = _num($menu[$v]['parent_id']))
			return false;
		//��� ������ 3-�� ������
		if($menu[$parent_id]['parent_id'])
			return true;
		return false;
	}

	//����������� id ��������� �������
	if($i == 'parent_main_id') {
		if(!$v = _num($v))
			return 0;
		if(!isset($menu[$v]))
			return 0;
		//��� �������� ������
		if(!$parent_id = _num($menu[$v]['parent_id']))
			return 0;
		//��� ������ 2-�� ������
		if(!$pp_id = $menu[$parent_id]['parent_id'])
			return $parent_id;

		return $pp_id;
	}

	//��������� ������ ���� ����������� �������
	if($i == 'parent') {
		foreach($menu as $id => $r)
			if(!$r['app_id'] ||
			   $r['parent_id'] != $v ||
			   !_viewerMenuAccess($id)
			) unset($menu[$id]);
		return $menu;
	}

	//������ �������� ��������
	if($i == 'main') {
		$send = array();
		foreach($menu as $id => $r)
			if(!$r['parent_id'])
				$send[$id] = $r;
		return $send;
	}

	//������ �������� ��������
	if($i == 'dop') {
		if(!$v = _num($v))
			return array();
		if(!isset($menu[$v]))
			return false;

		$parent_id = $menu[$v]['parent_id'] ? $menu[$v]['parent_id'] : $v;

		$send = array();
		foreach($menu as $id => $r)
			if($parent_id == $r['parent_id'])
				$send[$id] = $r;

		return $send;
	}

	//������ �������� �������� � ������� JS ��� select
	if($i == 'main_js') {
		$send = array();
		foreach($menu as $id => $r)
			if(!$r['parent_id'])
				$send[$id] = $r['name'];
		return _selJson($send);
	}

	//������ �������������� (2-� �������) �������� � ������� JS ��� select
	if($i == 'dop_js') {
		$send = array();
		foreach($menu as $id => $r) {
			if(!$r['parent_id'])
				continue;
			if($menu[$r['parent_id']]['parent_id'])
				continue;
			if(!isset($send[$r['parent_id']]))
				$send[$r['parent_id']] = array();
			$send[$r['parent_id']][] = $r;
		}
		return _selJsonSub($send);
	}

	//������������� ������� � ���������� ����������
	if($i == 'app_use') {
		if($menu[$v]['app_id'] == APP_ID)
			return true;
		return false;
	}
	
	//������� id ������� �� ���������
	if($i == 'app_def') {
		$menu_id = 0;
		foreach($menu as $id => $r)
			if($r['def']) {
				$menu_id = $id;
				break;
			}

		//��������� �������� �� ���������, ���� ������� �� �����������
		if(!$menu_id) {
			$sql = "SELECT `menu_id`
					FROM `_menu_app`
					WHERE `app_id`=".APP_ID."
					ORDER BY `id`
					LIMIT 1";
			if($id = query_value($sql)) {
				$sql = "UPDATE `_menu_app`
						SET `def`=0
						WHERE `app_id`=".APP_ID;
				query($sql);

				$sql = "UPDATE `_menu_app`
						SET `def`=1
						WHERE `app_id`=".APP_ID."
						  AND `menu_id`=".$id;
				query($sql);

				xcache_unset(CACHE_PREFIX.'menu');

				$menu_id = $id;
			}
		}

		//���� ������ �� ��������� ���������� ����������, �� ����� ������� ���������� �������
		if(!_viewerMenuAccess($menu_id))
			foreach($menu as $id => $r)
				if(_viewerMenuAccess($id)) {
					$menu_id = $id;
					break;
				}

		return $menu_id;
	}

	//������� ���������� ��� �������� ���� �������������
	if($i == 'app_setup') {
		$send = array();
		foreach($menu as $id => $r) {
			if(!$r['app_id'])
				continue;
			if($r['parent_id'])
				continue;
			if($id == 10) //manual
				continue;
			if($id == 11) //main
				continue;
			$send[$id] = array(
				'id' => $id,
				'name' => $r['name'],
				'sub' => array()
			);
		}
		foreach($menu as $id => $r) {
			if(!$r['app_id'])
				continue;
			if(!$r['parent_id'])
				continue;
			if(!isset($send[$r['parent_id']]))
				continue;
			if($id == 12) //��� ���������
				continue;
			$send[$r['parent_id']]['sub'][] = array(
				'id' => $id,
				'name' => $r['name']
			);
		}
		return $send;
	}

	if($i == 'all')
		return $menu;
	
	if(!_num($v))
		return _cacheErr('������������ id �������', $v);

	if(!isset($menu[$v]))
		return _cacheErr('����������� id �������', $v);

	if(!isset($menu[$v][$i]))
		return _cacheErr('����������� ���� �������', $i);

	return $menu[$v][$i];
}
function _menu() {//������� ��������� ����
	//��������� ���������� �������, � ��� �����, ���� �������� ��������
	if($sel_id = _num($_GET['p']))
		if($id = _menuCache('parent_main_id', $sel_id))
			$sel_id = $id;

	$link = '';
	foreach(_menuCache() as $id => $r) {
		if($r['parent_id'])
			continue;
		if($r['hidden'])
			continue;
		if($r['func_menu'] != '_menu')
			continue;
		if(!$r['app_id'])
			continue;
		if(!_viewerMenuAccess($r['id']))
			continue;

		$sel = $id == $sel_id ? ' sel' : '';
		$main = $id == 11 ? ' main' : '';
		if($id == 6)//������
			$r['name'] .= _remindTodayCount(1);
		if($id == 3)//������
			$r['name'] .= _invoiceTransferConfirmCount(1);
		$link .=
			'<a class="p'.$main.$sel.'" href="'.URL.'&p='.$id.'">'.
				($id == 11 ? '&nbsp;' : $r['name']).//main
			'</a>';
	}

	return
	setup_workerEnterMsg().
	_manualMsg().
	'<div id="_menu">'.
		$link.
		_clientDolgSum().
	'</div>';
}
function _menuMain() {//������ ������ ������� ��������
	//��������� ���������� ������������� ��������
	$sql = "SELECT COUNT(`id`)
			FROM `_client_person`
			WHERE `app_id`=".APP_ID."
		      AND `poa_nomer`";
	$poaCount = query_value($sql);

	$send = '';
	foreach(_menuCache() as $r) {
		if($r['id'] == 11)//main
			continue;
		if($r['parent_id'])
			continue;
		if($r['hidden'])
			continue;
		if(!$r['app_id'])
			continue;
		if(!_viewerMenuAccess($r['id']))
			continue;

		if($r['id'] == 1) //client
			$r['about'] .=
				(_viewerMenuAccess(43) && $poaCount ? '<a href="'.URL.'&p=43">������������</a><br />' : '').
				(_viewerMenuAccess(44) ? '<a href="'.URL.'&p=44">������ ������ ������</a>' : '');
		if($r['id'] == 2) //zayav
			$r['about'] .= _menuMainZayav();
		$send .=
		'<div class="mu">'.
			'<a href="'.URL.'&p='.$r['id'].'" class="fs14 b">'.$r['name'].'</a>'.
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
	'<script src="/.vkapp/.js/highcharts.js"></script>'.
//	'<script src="http://code.highcharts.com/highcharts.js"></script>'.
	'<table class="bs5">'.
		'<tr><td class="label r">�������:<td><b>'.($today ? $today : '-').'</b>'.
		'<tr><td class="label r">������� ������:<td><b>'.($week ? $week : '-').'</b>'.
		'<tr><td class="label r">'._monthDef(strftime('%m'), true).':<td><b>'.($mon ? $mon : '-').'</b>'.
	'</table>'.
	_menuMainZayavGrafik();
}
function _menuMainZayavGrafik() {
	$days = array();//30 ��������� ���� ������
	$d30 = TODAY_UNIXTIME - 60*60*24*29;
	$dayLast = 100; //��������� ���������� ���� (��� ������� ������)
	$w = date('w', $d30);//���� ������
	while($d30 <= TODAY_UNIXTIME) {
		$day = _num(strftime('%d', $d30));
		$mon = $day < $dayLast ? _monthCut(strftime('%m', $d30)).' ' : '';
		$days[strftime('%Y-%m-%d', $d30)] = '"'.$mon.'<tspan'.(!$w || $w == 6 ? ' style=\"color:#d55\"' : '').'>'.$day.' '._week($w++).'</tspan>"';
		$d30 += 60*60*24;
		$dayLast = $day;
		if($w > 6)
			$w = 0;
	}

	$series = array();
	foreach(_service() as $s) {
		if(!$data = _menuMainZayavGrafikService($s['id'], $days))
			continue;
		$series[] = '{'.
			'name:"'.($s['name'] ? $s['name'] : '��� ������').'",'.
			'data:['.$data.']'.
		'}';
	}

	return
	'<div id="zayav-chart-container"></div>'.
	'<script>var ZAYAV_CATEGORIES=['.implode(',', $days).'],'.
				'ZAYAV_SERIES=['.implode(',', $series).'];'.
	'</script>'.
	'<script>mainMenuZayavChart()</script>';
}
function _menuMainZayavGrafikService($service_id, $days) {//������ ��� ������� �� ���������� ���� ������������
	$sql = "SELECT
				DATE_FORMAT(`dtime_add`,'%Y-%m-%d') AS `day`,
				COUNT(`id`) `count`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `service_id`=".$service_id."
			  AND `dtime_add`>DATE_SUB(NOW(), INTERVAL 30 DAY)
			GROUP BY `day`
			ORDER BY `day`";
	$q = query($sql);
	$spisok = array();
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['day']] = $r['count'];

	if(empty($spisok))
		return false;

	$send = array();
	foreach($days as $day => $d) {
		if(!isset($spisok[$day])) {
			$send[$day] = '""';
			continue;
		}

		$send[$day] = $spisok[$day];
	}

	return implode(',', $send);
}

function _menuAccess($menu_id) {//�������� ������� � ������� ����. ���� ���, �� ��������������� �� ������ ��������
	if(!_viewerMenuAccess($menu_id)) {
		header('Location:'.URL.'&p=11');
		exit;
	}
};
function _menuParentGo() {//������� �� ������ ��������� �������� ��������
	if($p = _menuCache('parent', $_GET['p'])) {
		$key = key($p);
		header('Location:'.URL.'&p='.$p[$key]['id']);
		exit;
	}

	return _err('���������� �����������.');
}



function _vkapi($method, $param=array()) {//��������� ������ �� api ���������
	$param += array(
		'v' => 5.60,
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
	if(@$_GET['p'] == 75)
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
	return
	'<div class="noauth pad30 bg-gr1">'.
		'<div class="center grey bg-fff">'.$msg.'</div>'.
	'</div>';
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

	$html =
		_header().
		'<div id="pin-enter">'.
			'���: '.
			'<input type="password" id="pin" maxlength="10">'.
			'<button class="vk" onclick="pinEnter()">����</button>'.
			'<div class="red">&nbsp;</div>'.
			'<script>pinLoad('.(PIN_LEN * 7).')</script>'.
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

function _iconAdd($v=array()) {//������ ���������� ������
	$v = array(
		'id' => _num(@$v['id']) ? ' val="'.$v['id'].'"' : '',//id ������
		'class' => !empty($v['class']) ? ' '.$v['class'] : ''//�������������� �����
	);

	return '<div'.$v['id'].' class="img_add'.$v['class']._tooltip('��������', -51, 'r').'</div>';
}
function _iconEdit($v=array()) {//������ �������������� ������ � �������
	$v = array(
		'id' => _num(@$v['id']) ? ' val="'.$v['id'].'"' : '',       //id ������
		'class' => !empty($v['class']) ? ' '.$v['class'] : '',      //�������������� �����
		'onclick' => !empty($v['onclick']) ? ' onclick="'.$v['onclick'].'"' : '' //������ �� �������
	);

	return '<div'.$v['id'].$v['onclick'].' class="img_edit'.$v['class']._tooltip('��������', -52, 'r').'</div>';
}
function _iconDel($v=array()) {//������ �������� ������ � �������
	//���� ����������� ���� �������� ������ � ��� �� �������� ����������� ���, �� �������� ����������
	if(!empty($v['nodel']) || empty($v['del']) && !empty($v['dtime_add']) && TODAY != substr($v['dtime_add'], 0, 10))
		return '';

	$v = array(
		'id' => _num(@$v['id']) ? 'val="'.$v['id'].'" ' : '',//id ������
		'class' => !empty($v['class']) ? ' '.$v['class'] : '',//�������������� �����
		'onclick' => !empty($v['onclick']) ? ' onclick="'.$v['onclick'].'"' : '' //������ �� �������
	);

	return '<div '.$v['id'].$v['onclick'].' class="img_del'.$v['class']._tooltip('�������', -46, 'r').'</div>';
}

function _iconAddNew($v=array()) {//������ ���������� ������
	$v = array(
		'id' => _num(@$v['id']) ? ' val="'.$v['id'].'"' : '',//id ������
		'class' => !empty($v['class']) ? ' '.$v['class'] : ''//�������������� �����
	);

	return '<div'.$v['id'].' class="icon icon-add'.$v['class']._tooltip('��������', -51, 'r').'</div>';
}
function _iconEditNew($v=array()) {//������ �������������� ������ � �������
	$v = array(
		'id' => _num(@$v['id']) ? ' val="'.$v['id'].'"' : '',       //id ������
		'class' => !empty($v['class']) ? ' '.$v['class'] : '',      //�������������� �����
		'onclick' => !empty($v['onclick']) ? ' onclick="'.$v['onclick'].'"' : '', //������ �� �������
		'tt_name' => !empty($v['tt_name']) ? $v['tt_name'] : '��������',
		'tt_left' => !empty($v['tt_left']) ? $v['tt_left'] : -48,
		'tt_side' => !empty($v['tt_side']) ? $v['tt_side'] : 'r'
	);

	return '<div'.$v['id'].$v['onclick'].' class="icon icon-edit'.$v['class']._tooltip($v['tt_name'], $v['tt_left'], $v['tt_side']).'</div>';
}
function _iconDelNew($v=array()) {//������ �������� ������ � �������
	if(!empty($v['nodel']))
		return '';

	//���� ����������� ���� �������� ������ � ��� �� �������� ����������� ���, �� �������� ����������
	if(empty($v['del']) && !empty($v['dtime_add']) && TODAY != substr($v['dtime_add'], 0, 10))
		return '';

	$v = array(
		'id' => _num(@$v['id']) ? 'val="'.$v['id'].'" ' : '',//id ������
		'class' => !empty($v['class']) ? ' '.$v['class'] : '',//�������������� �����
		'onclick' => !empty($v['onclick']) ? ' onclick="'.$v['onclick'].'"' : '' //������ �� �������
	);

	return '<div '.$v['id'].$v['onclick'].' class="icon icon-del'.$v['class']._tooltip('�������', -42, 'r').'</div>';
}

function _dn($v) {//�����/������� ����� �� ��������� �������
	if(empty($v))
		return ' dn';
	return '';
}

function _ids($ids, $return_arr=0) {//�������� ������������ ������ id, ������������ ����� �������
	$arr = array();
	foreach(explode(',', $ids) as $i => $id) {
		if(!preg_match(REGEXP_NUMERIC, $id))
			return false;
		$arr[$i] = _num($id);
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

	$ost = round($ost * 100);

	return ($ost < 10 ? 0 : '').$ost.' ����'._end($ost, '���', '���', '��');
}
function _maxSql($table, $pole='sort', $app=0, $resource_id=GLOBAL_MYSQL_CONNECT) {
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
//		$r['id'] = $key;
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

function _pr($arr) {//������ ������� print_r
	if(empty($arr))
		return _prMsg('������ ����');

	if(!is_array($arr))
		return _prMsg('�� �������� ��������');

	return
	'<div class="dib pad5 bor-e8">'.
		_prFor($arr).
	'</div>';
}
function _prMsg($msg) {
	return '<div class="dib grey i pad5 bor-e8">'.$msg.'</div>';
}
function _prFor($arr, $sub=0) {//������� �������
	$send = '';
	foreach($arr as $id => $r) {
		$send .=
			'<div class="'.($sub ? 'ml20' : '').(is_array($r) ? '' : ' mtm2').'">'.
				'<span class="'.($sub ? 'fs11 color-acc' : 'fs12 black').(is_array($r) ? ' b u curP' : '').'"'.(is_array($r) ? ' onclick="$(this).next().slideToggle(300)"' : '').'>'.
					$id.':'.
				'</span> '.
				'<span class="grey fs11">'.
					(is_array($r) ? _prFor($r, 1) : $r).
				'</span>'.
			'</div>';
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
function _selJsonSub($arr, $uidName='id', $titleName='name') {//������������� ������ ��� _select 2-�� ������
	/*
		� ����:
		{1:[{uid:3,title:'�������� 3'},{uid:5,title:'�������� 5'}],
		 2:[{uid:3,title:'�������� 3'},{uid:5,title:'�������� 5'}]
		}

	*/
	$send = array();
	foreach($arr as $id => $sub) {
		if(!isset($send[$id]))
			$send[$id] = array();
		foreach($sub as $r)
			$send[$id][] = '{'.
				'uid:'.$r[$uidName].','.
				'title:"'.addslashes($r[$titleName]).'"'.
			'}';
	}

	$json = array();
	foreach($send as $id => $r)
		$json[] = $id.':['.implode(',', $r).']';

	return '{'.implode(',',$json).'}';
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
		if(!empty($val) && $val[0] == 0 ||  !is_numeric($val))
			$val = '"'.addslashes(_br($val)).'"';
		$spisok[] = $key.':'.$val;
	}

	$filter['js'] =
		'<script>'.
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
//		"\n".'CLIENT_CATEGORY_ASS='._assJson(_clientCategory(0,1)).','.
 		"\n".'COLOR_SPISOK='.query_selJson("SELECT `id`,`name` FROM `_setup_color` ORDER BY `name`").','.
		"\n".'COLORPRE_SPISOK='.query_selJson("SELECT `id`,`predlog` FROM `_setup_color` ORDER BY `predlog`").','.
		"\n".'PAY_TYPE='._selJson(_payType()).','.
		"\n".'SKIDKA_SPISOK='._selJson(_zayavSkidka()).','.
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
		"\n".'ZE_DUB_ASS='._zayavExpense('ze_dub_ass').','.
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

		"\n".'LIST_VYDACI="'.LIST_VYDACI.'",'. //todo

		"\n".'SCHET_PAY_USE='._app('schet_pay').','.

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

		"\n".'TOVAR_CATEGORY_SPISOK='._tovarCategory('main_js').','.
		"\n".'TOVAR_VENDOR_SPISOK='._tovarVendorJs().','.
		"\n".'TOVAR_STOCK_SPISOK='._tovarStock('js_name').','.
		"\n".'TOVAR_FEATURE_SPISOK='._tovarFeatureJs().';';


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

function _globalCacheClear($app_id=0) {//������� ���������� �������� ����
	$prefix = $app_id ? 'CACHE_'.$app_id.'_' : CACHE_PREFIX;
	$app_id = $app_id ? $app_id : APP_ID;

	xcache_unset($prefix.'app');            //������ ����������
	xcache_unset($prefix.'setup_global');   //��������� ����������
	xcache_unset($prefix.'setup_color');    //�����
	xcache_unset($prefix.'menu');           //������ �������� ����
	xcache_unset($prefix.'manual_part');    //������� �������
	xcache_unset($prefix.'manual_part_sub');//���������� �������
	xcache_unset($prefix.'rule_default');   //��������� ���� �� ���������
	xcache_unset($prefix.'balans_action');  //�������� ��� ��������� �������
	xcache_unset($prefix.'client_category');//��������� ��������
	xcache_unset($prefix.'service');        //���� ������������
	xcache_unset($prefix.'invoice');        //��������� �����
	xcache_unset($prefix.'expense');        //��������� �������� �����������
	xcache_unset($prefix.'expense_sub');    //������������ �������� �����������
	xcache_unset($prefix.'client_from');    //���������, ������ ������ ������
	xcache_unset($prefix.'zayav_expense');  //��������� �������� ������
	xcache_unset($prefix.'zayav_status');   //������� ������

	xcache_unset($prefix.'tovar_category');
	xcache_unset($prefix.'tovar_vendor');
	xcache_unset($prefix.'tovar_feature_name');
	xcache_unset($prefix.'tovar_equip');
	xcache_unset($prefix.'tovar_measure');
	xcache_unset($prefix.'tovar_stock');

	xcache_unset($prefix.'cartridge');
	xcache_unset($prefix.'rubric');
	xcache_unset($prefix.'rubric_sub');
	xcache_unset($prefix.'gn');
	xcache_unset($prefix.'gazeta_obdop');
	xcache_unset($prefix.'gazeta_polosa');
	xcache_unset($prefix.'devstory_part');
	xcache_unset($prefix.'devstory_keyword');


	//����� ������� �������� ��������� �������
//		unset($_SESSION[PIN_TIME_KEY]);

	//������� ���� �������� ������������
	xcache_unset($prefix.'viewer_'.VIEWER_ID);
	xcache_unset($prefix.'viewer_rule_'.VIEWER_ID);
	xcache_unset($prefix.'viewer_menu_'.VIEWER_ID);

	//������� ���� ����������� ����������
	$sql = "SELECT `viewer_id`
			FROM `_vkuser`
			WHERE `app_id`=".$app_id."
			  AND `worker`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		xcache_unset($prefix.'viewer_'.$r['viewer_id']);
		xcache_unset($prefix.'viewer_rule_'.$r['viewer_id']);
		xcache_unset($prefix.'viewer_menu_'.$r['viewer_id']);
		xcache_unset($prefix.'pin_enter_count'.$r['viewer_id']);
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
function _sumSpace($sum, $oo=0, $znak=',') {//���������� ����� � �������� ���� � ���������
	$minus = $sum < 0 ? -1 : 1;
	$sum *= $minus;
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
	$send = $drob ? $send.$znak.($drob < 10 ? 0 : '').$drob : $send;
	$send = $oo && !$drob ? $send.$znak.'00' : $send;
	return ($minus < 0 ? '-' : '').$send;
}
function _tooltip($msg, $left=0, $ugolSide='', $x2=0) {
	//x2: � ��� ������
	$x2 = $x2 ? ' x2' : '';
	return
		' _tooltip">'.
		'<div class="ttdiv'.$x2.'"'.($left ? ' style="left:'.$left.'px"' : '').'>'.
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
	require_once GLOBAL_DIR.'/inc/excel/vendor/autoload.php';
	require_once GLOBAL_DIR.'/inc/clsMsDocGenerator.php';

	switch(@$_GET['d']) {
		case 'kvit_html':
			require_once GLOBAL_DIR.'/view/xls/kvit_html.php';
			break;
		case 'kvit_comtex':
			require_once GLOBAL_DIR.'/view/xls/kvit_comtex.php';
			break;
		case 'kvit_cartridge':
			require_once GLOBAL_DIR.'/view/xls/kvit_cartridge.php';
			break;
		case 'zp_zakaz':
			require_once GLOBAL_DIR.'/view/xls/zp_zakaz.php';
			break;
		case 'salary_list':
			require_once GLOBAL_DIR.'/view/xls/salary_list.php';
			break;
		case 'radiomaster':
			require_once GLOBAL_DIR.'/view/xls/price_radiomaster.php';
			break;
		case 'erm':
			require_once GLOBAL_DIR.'/view/xls/evrookna_report_month.php';
			break;
		case 'erm_lena'://��������� ����� ��� ����
			require_once GLOBAL_DIR.'/view/xls/evrookna_report_month_lena.php';
			break;
		case 'mebel_komplekt'://������ ������������� ��� ��������� �������
			require_once GLOBAL_DIR.'/view/xls/mebel_komplekt.php';
			break;
		case 'ob_word': _zayavObWord(); break;
		case 'template': _template(); break;
		default: die('�������� �� ������.');
	}
	exit;
}





function _templateVerify($use) {//�������� ������� ������� �� ����� �������������
	if(empty($use))
		return false;

	//���������� ��������
	$sql = "SELECT COUNT(*)
			FROM `_template`
			WHERE `app_id`=".APP_ID."
			  AND `attach_id`
			  AND `use`='".addslashes($use)."'";
	if(!query_value($sql))
		return false;
	
	return true;
}
function _template() {//������������ �������
	if(!$template_id = _num(@$_GET['template_id']))
		die('������������ ������������� �������.');

	//��������� ������ � �������
	$sql = "SELECT *
			FROM `_template`
			WHERE `app_id`=".APP_ID."
			  AND `id`=".$template_id;
	if(!$tmp = query_assoc($sql))
		die('������� id'.$template_id.' �� ����������.');

	if(!$tmp['attach_id'])
		die('�� �������� ���� �������.');

	//��������� ������ � ����� �������
	$sql = "SELECT *
			FROM `_attach`
			WHERE `id`=".$tmp['attach_id'];
	if(!$attach = query_assoc($sql))
		die('����� ������� id'.$tmp['attach_id'].' �� ����������.');

	if(!file_exists(GLOBAL_PATH.'/..'.$attach['link']))
		die('���� ������� ����������� �� �������.');

	//�������� ���������� �����: xls, xlsx, docx
	$ex = explode('.', $attach['link']);
	$kLast = count($ex) - 1;
	if(!$kLast)
		die('������������ ��� ����� �������.');

	switch($ex[$kLast]) {
		case 'xls':
		case 'xlsx': _templateXls($ex[$kLast], $tmp, $attach);
		case 'docx': _templateWord($tmp, $attach);
		default: die('������������ ������ ����� �������. ��������� �������: xls, xlsx, docx.');
	}
}
function _templateVar() {//������������ ���������� �������
	$sql = "SELECT
				`var`.*,
				`gr`.`table_name`,
				'' `text`
			FROM
				`_template_var` `var`,
				`_template_var_group` `gr`
			WHERE `var`.`group_id`=`gr`.`id`";
	$varSpisok = query_arr($sql);

	//��������� �����������
	foreach($varSpisok as $id => $r)
		if($r['table_name'] == '_app')
			$varSpisok[$id]['text'] = htmlspecialchars_decode(_app($r['col_name']));

	$varSpisok = _templateIncome($varSpisok);   //�����
	$varSpisok = _templateClient($varSpisok);   //������
	$varSpisok = _templateSchetPay($varSpisok); //���� �� ������: �������� ��������� ������ �����������, ����� � �������
	$varSpisok = _templateDogovor($varSpisok);  //�������

	$var = array();
	foreach($varSpisok as $r)
		$var[$r['v']] = $r['text'];

	return $var;
}
function _templateIncome($arr) {//������� ���������� ������ �������
	if(!$income_id = _num(@$_GET['income_id']))
		return $arr;

	$sql = "SELECT *
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `id`=".$income_id;
	if(!$income = query_assoc($sql))
		return $arr;

	foreach($arr as $id => $r)
		if($r['table_name'] == '_money_income') {
			if($r['v'] == '{INCOME_SUM_PROPIS}') {
				$arr[$id]['text'] =
					_numToWord($income[$r['col_name']], 1).
					' ����'._end($income[$r['col_name']], '�', '�', '��').
					' '._kop($income[$r['col_name']]);
				continue;
			}
			if($r['v'] == '{INCOME_DATE_ADD}') {
				$arr[$id]['text'] = FullData($income[$r['col_name']]);
				continue;
			}
			$arr[$id]['text'] = $income[$r['col_name']];
		}

	_templateClientIdDefine($income['client_id']);

	return $arr;
}
function _templateSchetPay($arr) {//������� ���������� ����� �� ������
	if(!$schet_id = _num(@$_GET['schet_id']))
		return $arr;

	$sql = "SELECT *
			FROM `_schet_pay`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `id`=".$schet_id;
	if(!$schet = query_assoc($sql))
		return $arr;

	foreach($arr as $id => $r) {
		if( $r['v'] == '{ORG_NAME_YUR}' ||
			$r['v'] == '{ORG_ADRES_YUR}' ||
			$r['v'] == '{ORG_INN}' ||
			$r['v'] == '{ORG_KPP}')
			$arr[$id]['text'] = htmlspecialchars_decode($schet['org_'.$r['col_name']]);

		if($r['v'] == '{ORG_BOSS}')
			$arr[$id]['text'] = htmlspecialchars_decode($schet['org_boss']);

		if($r['v'] == '{ORG_ACCOUNTANT}')
			$arr[$id]['text'] = htmlspecialchars_decode($schet['org_accountant']);

		if( $r['v'] == '{BANK_NAME}' ||
			$r['v'] == '{BANK_BIK}' ||
			$r['v'] == '{BANK_ACCOUNT}' ||
			$r['v'] == '{BANK_ACCOUNT_CORR}')
			$arr[$id]['text'] = htmlspecialchars_decode($schet[$r['col_name']]);

		if( $r['v'] == '{CLIENT_NAME}' ||
			$r['v'] == '{CLIENT_ADRES}' ||
			$r['v'] == '{CLIENT_INN}' ||
			$r['v'] == '{CLIENT_KPP}')
			$arr[$id]['text'] = htmlspecialchars_decode($schet['client_'.$r['col_name']]);

		if($r['table_name'] != '_schet_pay')
			continue;

		if($r['v'] == '{SCHET_NOMER}') {
			$arr[$id]['text'] = $schet['prefix'].$schet['nomer'];
			continue;
		}
		if($r['v'] == '{SCHET_SUM_PROPIS}') {
			$arr[$id]['text'] =
				_numToWord($schet[$r['col_name']], 1).
				' ����'._end($schet[$r['col_name']], '�', '�', '��').
				' '._kop($schet[$r['col_name']]);
			continue;
		}
		if($r['v'] == '{SCHET_DATE_CREATE}') {
			$arr[$id]['text'] = FullData($schet[$r['col_name']]);
			continue;
		}
		if($r['v'] == '{SCHET_ACT_DATE}') {
			$arr[$id]['text'] = FullData($schet[$r['col_name']]);
			continue;
		}
		if($r['v'] == '{SCHET_SUM}') {
			$arr[$id]['text'] = _sumSpace($schet[$r['col_name']], 1);
			continue;
		}
		if($r['v'] == '{SCHET_CONTENT}') {
			$sql = "SELECT *
					FROM `_schet_pay_content`
					WHERE `schet_id`=".$schet_id."
					ORDER BY `id`";
			if(!$spisok = query_arr($sql))
				continue;

			$content = array();
			$n = 1;
			foreach($spisok as $i)
				$content[] = array(
					$n++,
					htmlspecialchars_decode($i['name']),
					$i['count'],
					_tovarMeasure($i['measure_id']),
					_sumSpace($i['cena'], 1),
					_sumSpace($i['count'] * $i['cena'], 1)
				);

			$arr[$id]['text'] = $content;
			continue;
		}
		if($r['v'] == '{SCHET_CONTENT_COUNT}') {
			$sql = "SELECT COUNT(*)
					FROM `_schet_pay_content`
					WHERE `schet_id`=".$schet_id;
			$arr[$id]['text'] = query_value($sql);
			continue;
		}
		if($r['v'] == '{SCHET_FAKTURA_CONTENT}') {
			$sql = "SELECT *
					FROM `_schet_pay_content`
					WHERE `schet_id`=".$schet_id."
					ORDER BY `id`";
			if(!$spisok = query_arr($sql))
				continue;

			$content = array();
			$n = 1;
			foreach($spisok as $i)
				$content[] = array(
					htmlspecialchars_decode($i['name']),
					false,
					_tovarMeasure($i['measure_id']),
					$i['count'],
					_sumSpace($i['cena'], 1),
					_sumSpace($i['count'] * $i['cena'], 1),
					false,
					false,
					false,
					_sumSpace($i['count'] * $i['cena'], 1)
				);

			$arr[$id]['text'] = $content;
			continue;
		}
		$arr[$id]['text'] = $schet[$r['col_name']];
	}

	_templateClientIdDefine($schet['client_id']);

	return $arr;
}
function _templateClientIdDefine($client_id) {//��������� id �������, ���� �� ��� �������
	if(!$client_id)
		return;

	if(!defined('TEMPLATE_CLIENT_ID'))
		define('TEMPLATE_CLIENT_ID', $client_id);
}
function _templateClient($arr) {//������� ���������� �������

	if(!defined('TEMPLATE_CLIENT_ID'))
		return $arr;

	$sql = "SELECT *
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `id`=".TEMPLATE_CLIENT_ID;
	if(!$client = query_assoc($sql))
		return $arr;

	foreach($arr as $id => $r) {
		if($r['table_name'] != '_client')
			continue;

		$arr[$id]['text'] = htmlspecialchars_decode($client[$r['col_name']]);
	}

	return $arr;
}
function _templateDogovor($arr) {//������� ���������� ��������
	if(!$dogovor_id = _num(@$_GET['dogovor_id']))
		return $arr;
		
	$sql = "SELECT *
			FROM `_zayav_dogovor`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `id`=".$dogovor_id;
	if(!$dog = query_assoc($sql))
		return $arr;

	foreach($arr as $id => $r) {
		if($r['table_name'] != '_zayav_dogovor')
			continue;

		if($r['v'] == '{DOGOVOR_CREATE}') {
			$ex = explode('-', $dog[$r['col_name']]);
			$dog[$r['col_name']] = '�'.$ex[2].'� '._monthFull($ex[1]).' '.$ex[0];
		}

		$arr[$id]['text'] = htmlspecialchars_decode($dog[$r['col_name']]);
	}

	return $arr;
}

function _templateWord($tmp, $attach) {//������������ ������� Word
	require_once GLOBAL_DIR.'/inc/word/vendor/autoload.php';

	$phpWord = new \PhpOffice\PhpWord\PhpWord();
	$document = $phpWord->loadTemplate(GLOBAL_PATH.'/..'.$attach['link']);

	foreach(_templateVar() as $key => $name)
		$document->setValue($key, utf8($name));

	header('Content-type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
	header('Content-Disposition: attachment; filename="'.$tmp['name_file'].'.docx"');
	$document->saveAs('php://output');

	exit;
}
function _templateXls($version, $tmp, $attach) {//������������ ������� Excel
	$type = array(
		'xls' => 'Excel5',
		'xlsx' => 'Excel2007'
	);

	$reader = PHPExcel_IOFactory::createReader($type[$version]);
	$book = $reader->load(GLOBAL_PATH.'/..'.$attach['link']);

	$sheetCount = $book->getSheetCount();//���������� ������� � ���������
	while($sheetCount--) {
		$book->setActiveSheetIndex($sheetCount);//��������� ������� ��������
		$sheet = $book->getActiveSheet();
		_templateXlsProcess($sheet);
	}

	header('Content-type: application/vnd.ms-excel');
	header('Content-Disposition: attachment; filename="'.$tmp['name_file'].'.'.$version.'"');
	$writer = PHPExcel_IOFactory::createWriter($book, $type[$version]);
	$writer->save('php://output');

	exit;
}
function _templateXlsProcess($sheet) {//������� ���������� � �������
	$rowMax = $sheet->getHighestRow(); //������������ ���������� ������������ ����� � ���������
	$�olMax = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn()); //������������ ���������� ������������ ������� � ���������

	$var = _templateVar();

	$varSpisok = 0;//������� ���������� ���������� �� �������

	//������� ����������, �� ���������� ��������
    for($row = 0; $row < $rowMax; $row++)
        for($col = 0; $col < $�olMax; $col++) {
	        //��������� �������� ������. ���� ������, �� �������
	        if(!$txt = $sheet->getCellByColumnAndRow($col, $row + 1)->getValue())
		        continue;

	        foreach($var as $v => $i) {
		        if(is_array($i)) {//���� ������, �� �������
					if($v == $txt)
						$varSpisok++;
			        continue;
		        }

		        $txt = str_replace($v, utf8($i), $txt);
	        }

	        $sheet->setCellValueByColumnAndRow($col, $row + 1, $txt);
        }


	//������� �������
    while($varSpisok) {
		$rowMax = $sheet->getHighestRow(); //������������ ���������� ������������ ����� � ���������
		$�olMax = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn()); //������������ ���������� ������������ ������� � ���������
	    for($row = 0; $row < $rowMax; $row++)
	        for($col = 0; $col < $�olMax; $col++) {
		        //��������� �������� ������. ���� ������, �� �������
		        if(!$txt = $sheet->getCellByColumnAndRow($col, $row + 1)->getValue())
			        continue;

		        foreach($var as $v => $i) {
			        if(!is_array($i)) //���� �� ������, �� �������
				        continue;

			        if($v != $txt)//���� � ������ ��� ����������, ������� ������������� ������� ������, �� �������
				        continue;

			        if(count($i) > 1)//������� ���������� ����� �������� ������
						$sheet->insertNewRowBefore($row + 2, count($i) - 1);

					foreach($i as $num => $stroka)
						foreach($stroka as $n => $sp) {
							//������� � ������ �������� �� ��������� � ����������� ������ (������ �� ������ ������)
							if($sp === false) {
								$vv = $sheet->getCellByColumnAndRow($col + $n, $row + 1)->getValue();
								$sheet->setCellValueByColumnAndRow($col + $n, $row + $num + 1, $vv);
								continue;
							}
							$sheet->setCellValueByColumnAndRow($col + $n, $row + $num + 1, utf8($sp));
			        }
		        }
	        }
		$varSpisok--;
    }
}



