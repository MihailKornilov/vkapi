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
  `app_setup` tinyint unsigned DEFAULT 0,
  `menu_left_set` tinyint unsigned DEFAULT 0,
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

����������� ���������:
	NAMES
	API_ID
	SECRET
	SA
	VIEWER_ID
	VIEWER_ADMIN
	REGEXP_NUMERIC
*/

function _appAuth() {
	if(LOCAL)
		return;
	if(@$_GET['auth_key'] != md5(@$_GET['api_id']."_".VIEWER_ID."_".SECRET)) {
		echo '������ �����������. ���������� �����: <a href="http://vk.com/app'.API_ID.'">http://vk.com/app'.API_ID.'</a>.';
		exit;
	}
}//_appAuth()

function _dbConnect() {
	global $mysql, $sqlQuery;
	$dbConnect = mysql_connect($mysql['host'], $mysql['user'], $mysql['pass'], 1) or die("Can't connect to database");
	mysql_select_db($mysql['database'], $dbConnect) or die("Can't select database");
	$sqlQuery = 0;
	query('SET NAMES `'.NAMES.'`', $dbConnect);
}//_dbConnect()
function query($sql) {
	global $sqlQuery, $sqlCount, $sqlTime;
	$t = microtime(true);
	$res = mysql_query($sql) or die($sql);
	$t = microtime(true) - $t;
	$sqlTime += $t;
	$t = round($t, 3);
	$sqlQuery .= $sql.' = <b style="color:#'.($t < 0.05 ? '999' : 'd22').'">'.$t.'</b><br /><br />';
	$sqlCount++;
	return $res;
}
function query_value($sql) {
	if(!$r = mysql_fetch_row(query($sql)))
		return false;
	return $r[0];
}
function query_selJson($sql) {
	$send = array();
	$q = query($sql);
	while($sp = mysql_fetch_row($q))
		$send[] = '{uid:'.$sp[0].',title:"'.$sp[1].'"}';
	return '['.implode(',',$send).']';
}
function query_ptpJson($sql) {//������������� ������
	$q = query($sql);
	$send = array();
	while($sp = mysql_fetch_row($q))
		$send[] = $sp[0].':'.(preg_match(REGEXP_NUMERIC, $sp[1]) ? $sp[1] : '"'.$sp[1].'"');
	return '{'.implode(',', $send).'}';
}

function _selJson($arr) {
	$send = array();
	foreach($arr as $uid => $title)
		$send[] = '{uid:'.$uid.',title:"'.$title.'"}';
	return '['.implode(',',$send).']';
}//end of _selJson()

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
		(SA && !$id ? '<div class="pagehelp_create" val="'.$page.'">�������� ���������</div>' : '');
}

function _check($id, $txt='', $value=0) {
	return
	'<div class="check'.$value.'" id="'.$id.'_check">'.
		'<input type="hidden" id="'.$id.'" value="'.$value.'" />'.
		$txt.
	'</div>';
}//_check()
function _radio($id, $list, $value=0, $light=false) {
	$spisok = '';
	foreach($list as $uid => $title) {
		$sel = $uid == $value ? 'on' : 'off';
		$light = $light ? ' l' : '';
		$spisok .= '<div class="'.$sel.$light.'" val="'.$uid.'"><s></s>'.$title.'</div>';
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

function win1251($txt) { return iconv('UTF-8','WINDOWS-1251',$txt); }
function utf8($txt) { return iconv('WINDOWS-1251','UTF-8',$txt); }
function curTime() { return strftime('%Y-%m-%d %H:%M:%S',time()); }

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

function _vkUserUpdate($uid=VIEWER_ID) {//���������� ������������ �� ��������
	require_once('vkapi.class.php');
	$VKAPI = new vkapi(API_ID, SECRET);
	$res = $VKAPI->api('users.get',array('uids' => $uid, 'fields' => 'photo,sex,country,city'));
	$u = $res['response'][0];
	$u['first_name'] = win1251($u['first_name']);
	$u['last_name'] = win1251($u['last_name']);
	$u['country_id'] = isset($u['country']) ? $u['country'] : 0;
	$u['city_id'] = isset($u['city']) ? $u['city'] : 0;
	$u['menu_left_set'] = 0;

	// ��������� �� ����������
	$app = $VKAPI->api('isAppUser', array('uid'=>$uid));
	$u['app_setup'] = $app['response'];

	// �������� �� � ����� ����
	//$mls = $VKAPI->api('getUserSettings', array('uid'=>$uid));
	$u['menu_left_set'] = 0;//($mls['response']&256) > 0 ? 1 : 0;

	$sql = 'INSERT INTO `vk_user` (
				`viewer_id`,
				`first_name`,
				`last_name`,
				`sex`,
				`photo`,
				`app_setup`,
				`menu_left_set`,
				`country_id`,
				`city_id`
			) VALUES (
				'.$uid.',
				"'.$u['first_name'].'",
				"'.$u['last_name'].'",
				'.$u['sex'].',
				"'.$u['photo'].'",
				'.$u['app_setup'].',
				'.$u['menu_left_set'].',
				'.$u['country_id'].',
				'.$u['city_id'].'
			) ON DUPLICATE KEY UPDATE
				`first_name`="'.$u['first_name'].'",
				`last_name`="'.$u['last_name'].'",
				`sex`='.$u['sex'].',
				`photo`="'.$u['photo'].'",
				`app_setup`='.$u['app_setup'].',
				`menu_left_set`='.$u['menu_left_set'].',
				`country_id`='.$u['country_id'].',
				`city_id`='.$u['city_id'];
	query($sql);
	$u['viewer_id'] = $uid;
	return $u;
}//_vkUserUpdate()
function _viewer($id=VIEWER_ID, $val=false) {
	if(is_array($id)) {
		if(empty($id))
			return array();
		$sql = "SELECT * FROM `vk_user` WHERE `viewer_id` IN (".implode(',', $id).")";
		$q = query($sql);
		$users = array();
		while($u = mysql_fetch_assoc($q)) {
			$u['id'] = $u['viewer_id'];
			$u['name'] = $u['first_name'].' '.$u['last_name'];
			$u['link'] = '<a href="http://vk.com/id'.$u['viewer_id'].'" target="_blank">'.$u['name'].'</a>';
			$u['photo'] = '<img src="'.$u['photo'].'">';
			$users[$u['viewer_id']] = $u;
		}
		return $users;
	}

	$key = CACHE_PREFIX.'viewer_'.$id;
	$u = xcache_get($key);
	if(empty($u)) {
		$sql = "SELECT * FROM `vk_user` WHERE `viewer_id`=".$id." LIMIT 1";
		if(!$u = mysql_fetch_assoc(query($sql)))
			$u = _vkUserUpdate($id);
		$u['id'] = $u['viewer_id'];
		$u['name'] = $u['first_name'].' '.$u['last_name'];
		$u['link'] = '<a href="http://vk.com/id'.$u['viewer_id'].'" target="_blank">'.$u['name'].'</a>';
		$u['photo'] = '<img src="'.$u['photo'].'">';
		xcache_set($key, $u, 86400);
	}
	if($val)
		return isset($u[$val]) ? $u[$val] : false;
	return $u;
}//_viewer()

function _vkComment($table, $id=0) {
	$sql = "SELECT *
			FROM `vk_comment`
			WHERE `status`=1
			  AND `table_name`='".$table."'
			  AND `table_id`=".intval($id)."
			ORDER BY `dtime_add` ASC";
	$count = '������� ���';
	$units = '';
	$q = query($sql);
	if(mysql_num_rows($q)) {
		$comm = array();
		$v = array();
		while($r = mysql_fetch_assoc($q)) {
			if(!$r['parent_id'])
				$comm[$r['id']] = $r;
			elseif(isset($comm[$r['parent_id']]))
				$comm[$r['parent_id']]['childs'][] = $r;
			$v[$r['viewer_id_add']] = $r['viewer_id_add'];
		}
		$count = count($comm);
		$count = '����� '.$count.' �����'._end($count, '��', '��','��');
		$v = _viewer($v);
		$comm = array_reverse($comm);
		foreach($comm as $n => $r) {
			$childs = array();
			if(!empty($r['childs']))
				foreach($r['childs'] as $c)
					$childs[] = _vkCommentChild($c['id'], $v[$c['viewer_id_add']], $c['txt'], $c['dtime_add']);
			$units .= _vkCommentUnit($r['id'], $v[$r['viewer_id_add']], $r['txt'], $r['dtime_add'], $childs, ($n+1));
		}
	}
	return
	'<div class="vkComment" val="'.$table.'_'.$id.'">'.
		'<div class="headBlue"><div class="count">'.$count.'</div>�������</div>'.
		'<div class="add">'.
			'<textarea>�������� �������...</textarea>'.
			'<div class="vkButton"><button>��������</button></div>'.
		'</div>'.
		$units.
	'</div>';
}//_vkComment
function _vkCommentUnit($id, $viewer, $txt, $dtime, $childs=array(), $n=0) {
	return
	'<div class="cunit" val="'.$id.'">'.
		'<table class="t">'.
			'<tr><td class="ava">'.$viewer['photo'].
				'<td class="i">'.str_replace('a href', 'a class="vlink" href', $viewer['link']).
					($viewer['id'] == VIEWER_ID || VIEWER_ADMIN ? '<div class="img_del unit_del" title="������� �������"></div>' : '').
					'<div class="ctxt">'.$txt.'</div>'.
					'<div class="cdat">'.FullDataTime($dtime, 1).
						'<SPAN'.($n == 1  && !empty($childs) ? ' class="hide"' : '').'> | '.
							'<a>'.(empty($childs) ? '��������������' : '����������� ('.count($childs).')').'</a>'.
						'</SPAN>'.
					'</div>'.
					'<div class="cdop'.(empty($childs) ? ' empty' : '').($n == 1 && !empty($childs) ? '' : ' hide').'">'.
						implode('', $childs).
						'<div class="cadd">'.
							'<textarea>��������������...</textarea>'.
							'<div class="vkButton"><button>��������</button></div>'.
						'</div>'.
					'</div>'.
		'</table>'.
	'</div>';
}//_vkCommentUnit()
function _vkCommentChild($id, $viewer, $txt, $dtime) {
	return
	'<div class="child" val="'.$id.'">'.
		'<table class="t">'.
			'<tr><td class="dava">'.$viewer['photo'].
				'<td class="di">'.$viewer['link'].
					($viewer['id'] == VIEWER_ID || VIEWER_ADMIN ? '<div class="img_del child_del" title="������� �����������"></div>' : '').
					'<div class="dtxt">'.$txt.'</div>'.
					'<div class="ddat">'.FullDataTime($dtime, 1).'</div>'.
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
}//_monthFull
function _monthDef($n=0) {
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
	return $n ? $mon[intval($n)] : $mon;
}//_monthFull
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
}//_monthCut
function FullData($value, $noyear=false) {//14 ������ 2010
	$d = explode('-', $value);
	return
		abs($d[2]).' '.
		_monthFull($d[1]).
		(!$noyear || date('Y') != $d[0] ? ' '.$d[0] : '');
}//FullData()
function FullDataTime($value, $cut=false) {//14 ������ 2010 � 12:45
	$arr = explode(' ',$value);
	$d = explode('-',$arr[0]);
	$t = explode(':',$arr[1]);
	return
		abs($d[2]).' '.
		($cut ? _monthCut($d[1]) : _monthFull($d[1])).
		(date('Y') == $d[0] ? '' : ' '.$d[0]).
		' � '.$t[0].':'.$t[1];
}//FullDataTime()
function _curMonday() { //����������� � ������� ������
	// ����� �������� ��� ������
	$time = time();
	$curDay = date("w", $time);
	if($curDay == 0) $curDay = 7;
	// ���������� ��� � ������������
	$time -= 86400 * ($curDay - 1);
	return strftime('%Y-%m-%d', $time);
}//_curMonday()
function _curSunday() { //����������� � ������� ������
	$time = time();
	$curDay = date("w", $time);
	if($curDay == 0) $curDay = 7;
	$time += 86400 * (7 - $curDay);
	return strftime('%Y-%m-%d', $time);

}//_curSunday()

function _engRusChar($word) { //������� �������� ��������� � ����������� �� �������
	$char = array(
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
		']' => '�',
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
		'9' => '9'
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