<?php
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
//			'<a href="http://'.DOMAIN.APP_HTML.'/_sxdump" target="_blank">sxd</a> :: '.
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

function _pre($v) {// ����� � debug ������������ �������
	if(empty($v))
		return '';

	if(defined('ARRAY_PRE'))
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
