<?php
function _clientCase($v=array()) {//����� ���������� � ��������� ��� ����������
	$filterDef = $v + array(
		'CLIENT_FILTER_DOLG' => 0,//�������-������ "��������"
		'CLIENT_FILTER_OPL' => 0  //�������-������ "����������"
	);
	foreach($filterDef as $name => $key)
		define($name, $key);
	switch(@$_GET['d']) {
		case 'info':
			if(!_num($_GET['id']))
				return '�������� �� ����������';
			return _clientInfo($_GET['id']);
		default:
			$v = array();
			if(HASH_VALUES) {
				$ex = explode('.', HASH_VALUES);
				foreach($ex as $r) {
					$arr = explode('=', $r);
					$v[$arr[0]] = $arr[1];
				}
			} else {
				foreach($_COOKIE as $k => $val) {
					$arr = explode(VIEWER_ID.'_client_', $k);
					if(isset($arr[1]))
						$v[$arr[1]] = $val;
				}
			}
			$v['find'] = unescape(@$v['find']);
			return _client($v);
	}
}//_clientCase()

function _client($v) {
	return client_list($v);
}//_client()
function client_list($v) {// �������� �� ������� ��������
	$data = client_data($v);
	$v = $data['filter'];
	return
		'<div id="client">'.
			'<table id="find-tab"><tr>'.
				'<td><div id="find"></div>'.
				'<td><div id="buttonCreate"><a>����� ������</a></div>'.
			'</table>'.
			'<div class="result">'.$data['result'].'</div>'.
			'<table class="tabLR">'.
				'<tr><td class="left">'.$data['spisok'].
					'<td class="right">'.
						'<div class="filter'.($v['find'] ? ' dn' : '').'">'.
	  (CLIENT_FILTER_DOLG ? _check('dolg', '��������', $v['dolg']) : '').
//							_check('active', '� ��������� ��������', $v['active']).
//							_check('comm', '���� �������', $v['comm']).
//							_check('opl', '������� ����������', $v['opl']).
						'</div>'.
			'</table>'.
		'</div>'.
		'<script type="text/javascript">'.
			'var C={'.
				'find:"'.$v['find'].'"'.
			'};'.
		'</script>';
}//client_list()
function clientFilter($v) {
	$default = array(
		'page' => 1,
		'find' => '',
		'dolg' => 0,
		'active' => 0,
		'comm' => 0,
		'opl' => 0
	);
	$filter = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'find' => strtolower(trim(@$v['find'])),
		'dolg' => _bool(@$v['dolg']),
		'active' => _bool(@$v['active']),
		'comm' => _bool(@$v['comm']),
		'opl' => _bool(@$v['opl']),
		'clear' => ''
	);
	foreach($default as $k => $r)
		if($r != $filter[$k]) {
			$filter['clear'] = '<a id="filter_clear">�������� ������</a>';
			break;
		}
	return $filter;
}//clientFilter()
function client_data($v=array()) {// ������ ��������
	$filter = clientFilter($v);
	$cond = "`app_id`=".APP_ID." AND `ws_id`=".WS_ID." AND !`deleted`";
	$dolg = 0;
	$plus = 0;

	define('FIND', !empty($filter['find']));

	if(FIND) {
		$find = array();

		$reg = '/(\')/'; // ��������� ������� '
		if(!preg_match($reg, $filter['find']))
			$find[] = "`find` LIKE '%".$filter['find']."%'";

		$engRus = _engRusChar($filter['find']);
		if($engRus)
			$find[] = "`find` LIKE '%".$engRus."%'";

		$cond .= " AND ".(empty($find) ? " !`id` " : "(".implode(' OR ', $find).")");
	} else {
		if($filter['dolg']) {
			$cond .= " AND `balans`<0";
			$sql = "SELECT SUM(`balans`)
					FROM `_client`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND !`deleted`
					  AND `balans`<0";
			$dolg = abs(query_value($sql, GLOBAL_MYSQL_CONNECT));
		}
		if($filter['active']) {
			$ids = query_ids("SELECT DISTINCT `client_id`
							FROM `zayav`
							WHERE `ws_id`=".WS_ID."
							  AND `zayav_status`=1");
			$cond .= " AND `id` IN (".$ids.")";
		}
		if($filter['comm']) {
			$ids = query_ids("SELECT DISTINCT `table_id`
							FROM `vk_comment`
							WHERE `status` AND `table_name`='client'");
			$cond .= " AND `id` IN (".$ids.")";
		}
		if($filter['opl']) {
			$cond .= " AND `balans`>0";
			$sql = "SELECT SUM(`balans`)
					FROM `_client`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND !`deleted`
					  AND `balans`>0";
			$plus = abs(query_value($sql, GLOBAL_MYSQL_CONNECT));
		}
	}

	if(!$all = query_value("SELECT COUNT(`id`) AS `all` FROM `_client` WHERE ".$cond, GLOBAL_MYSQL_CONNECT))
		return array(
			'all' => 0,
			'result' => '�������� �� �������.'.$filter['clear'],
			'spisok' => '<div class="_empty">�������� �� �������.</div>',
			'filter' => $filter
		);

	$send['all'] = $all;
	$send['result'] = '������'._end($all, ' ', '� ').$all.' ������'._end($all, '', '�', '��').
					  ($dolg ? '<em>(����� ����� ����� = <b id="dolg">'._sumSpace($dolg).'</b> ���.)</em>' : '').
					  ($plus ? '<em>(����� = '.$plus.' ���.)</em>' : '').
					  $filter['clear'];
	$send['filter'] = $filter;
	$send['spisok'] = '';

	$page = $filter['page'];
	$limit = 20;
	$start = ($page - 1) * $limit;
	$spisok = array();
	$sql = "SELECT *,
				   0 `zayav_count`,
				   0 `zayav_wait`,
				   0 `zayav_ready`,
				   0 `zayav_fail`,
				   '' `comm`,
				   '' `fio`,
				   '' `phone`
			FROM `_client`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT ".$start.",".$limit;
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q)) {
		if(FIND) {
			$r['org_name'] = _findRegular($filter['find'], $r['org_name']);
			$r['org_phone'] = _findRegular($filter['find'], $r['org_phone']);
			$r['org_fax'] = _findRegular($filter['find'], $r['org_fax'], 1);
			$r['org_adres'] = _findRegular($filter['find'], $r['org_adres'], 1);
			$r['org_inn'] = _findRegular($filter['find'], $r['org_inn'], 1);
			$r['org_kpp'] = _findRegular($filter['find'], $r['org_kpp'], 1);
		}
		$r['person'] = array();
		$spisok[$r['id']] = $r;
	}

	// ��� � �������� ��������
	$sql = "SELECT *
			FROM `_client_person`
			WHERE `client_id` IN (".implode(',', array_keys($spisok)).")
			ORDER BY `client_id`,`id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$k = 0;
	$client_id = key($spisok);
	while($r = mysql_fetch_assoc($q)) {
		if($client_id != $r['client_id']) {
			$client_id = $r['client_id'];
			$k = 0;
		}

		// ������� �� ���������� ��������� � ������� �������� ������
		$regOk = FIND && (_findRegular($filter['find'], $r['fio'], 1) || _findRegular($filter['find'], $r['phone'], 1) || _findRegular($filter['find'], $r['adres'], 1));

		if(!$k) {
			$spisok[$r['client_id']]['fio'] = (FIND ? _findRegular($filter['find'], $r['fio']) : $r['fio']);
			$spisok[$r['client_id']]['phone'] = (FIND ? _findRegular($filter['find'], $r['phone']) : $r['phone']);
			$spisok[$r['client_id']]['adres'] = (FIND ? _findRegular($filter['find'], $r['adres'], 1) : $r['adres']);
			$spisok[$r['client_id']]['post'] = $r['post'];
		} else {
			if($regOk) // �������������� ���������� ���� ������������ ������ ��� ���������� � ������� ������
				$spisok[$r['client_id']]['person'][] = array(
					'fio' => _findRegular($filter['find'], $r['fio']),
					'phone' => _findRegular($filter['find'], $r['phone'], 1),
					'adres' => _findRegular($filter['find'], $r['adres'], 1),
					'post' => $r['post']
				);
		}
		$k++;
	}
/*
	// ����� ���������� ������
	$sql = "SELECT
				`client_id` AS `id`,
				COUNT(`id`) AS `count`
			FROM `zayav`
			WHERE `ws_id`=".WS_ID."
			  AND `zayav_status`
			  AND `client_id` IN (".implode(',', array_keys($spisok)).")
			GROUP BY `client_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']]['zayav_count'] = $r['count'];

	//������, ��������� ����������
	$sql = "SELECT
				`client_id` AS `id`,
				COUNT(`id`) AS `count`
			FROM `zayav`
			WHERE `ws_id`=".WS_ID."
			  AND `zayav_status`=1
			  AND `client_id` IN (".implode(',', array_keys($spisok)).")
			GROUP BY `client_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']]['zayav_wait'] = $r['count'];

	//����������� ������
	$sql = "SELECT
				`client_id` AS `id`,
				COUNT(`id`) AS `count`
			FROM `zayav`
			WHERE `ws_id`=".WS_ID."
			  AND `zayav_status`=2
			  AND `client_id` IN (".implode(',', array_keys($spisok)).")
			GROUP BY `client_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']]['zayav_ready'] = $r['count'];

	//��������� ������
	$sql = "SELECT
				`client_id` AS `id`,
				COUNT(`id`) AS `count`
			FROM `zayav`
			WHERE `ws_id`=".WS_ID."
			  AND `zayav_status`=3
			  AND `client_id` IN (".implode(',', array_keys($spisok)).")
			GROUP BY `client_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']]['zayav_fail'] = $r['count'];
*/
	//����������� �� ��������
	$sql = "SELECT
				`table_id` `id`,
				`txt`
			FROM `vk_comment`
			WHERE `status`
			  AND `table_name`='client'
			  AND `table_id` IN (".implode(',', array_keys($spisok)).")
			GROUP BY `table_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']]['comm'] = $r['txt'];

	foreach($spisok as $r) {
		$org = $r['category_id'] != 1;
		// ������ ���������� ���
		$person = '';
		if(FIND)
			foreach($r['person'] as $p)
				$person .=
					'<tr><td class="label top">'.($p['post'] ? $p['post'] : '���. ����').':'.
						'<td>'.$p['fio'].
							($p['phone'] ? '<br />'.$p['phone'] : '').
							($p['adres'] ? '<br />'.$p['adres'] : '');

		$phone = $org ? $r['org_phone'] : $r['phone'];

		$left =
			'<table class="l-tab">'.
				'<tr><td class="label top">'._clientCategory($r['category_id']).':'.
					'<td><a href="'.URL.'&p=client&d=info&id='.$r['id'].'">'.($org ? $r['org_name'] : $r['fio']).'</a>'.
	  ($phone ? '<tr><td class="label top">��������:<td>'.$phone : '').
		   (FIND && $r['adres'] ? '<tr><td class="label top">�����:<td>'.$r['adres'] : '').
		 (FIND && $r['org_fax'] ? '<tr><td class="label">����:<td>'.$r['org_fax'] : '').
	   (FIND && $r['org_adres'] ? '<tr><td class="label top">�����:<td>'.$r['org_adres'] : '').
		 (FIND && $r['org_inn'] ? '<tr><td class="label">���:<td>'.$r['org_inn'] : '').
		 (FIND && $r['org_kpp'] ? '<tr><td class="label">���:<td>'.$r['org_kpp'] : '').
		     ($org && $r['fio'] ? // ����������� ������� ����������� ���� � �����������
				'<tr><td class="label top">'.($r['post'] ? $r['post'] : '���. ����').':'.
					'<td>'.$r['fio'].
						($r['phone'] ? '<br />'.$r['phone'] : '').
						($r['adres'] ? '<br />'.$r['adres'] : '')
			 : '').
						$person.
			'</table>';


		$send['spisok'] .=
			'<div class="unit">'.
				'<table class="g-tab">'.
					'<tr><td>'.$left.
						'<td class="r-td">'.
							($r['comm'] ? '<div class="comm" val="'.$r['comm'].'"></div>' : '').
							($r['zayav_wait'] ? '<div class="z-wait'._tooltip('��������� ������', -60).$r['zayav_wait'].'</div>' : '').
							($r['zayav_ready'] ? '<div class="z-ready'._tooltip('����������� ������', -63).$r['zayav_ready'].'</div>' : '').
							($r['zayav_fail'] ? '<div class="z-fail'._tooltip('��������� ������', -59).$r['zayav_fail'].'</div>' : '').
							(round($r['balans'], 2) ?
								'<div style="color:#'.($r['balans'] < 0 ? 'A00' : '090').'" class="balans'.
									_tooltip('������', -15).
									round($r['balans'], 2).
								'</div>'
							: '').
				'</table>'.
			'</div>';
	}
	if($start + $limit < $send['all']) {
		$c = $send['all'] - $start - $limit;
		$c = $c > $limit ? $limit : $c;
		$send['spisok'] .= '<div class="_next" val="'.($page + 1).'"><span>�������� ��� '.$c.' ������'._end($c, '�', '�', '��').'</span></div>';
	}
	return $send;
}//client_data()








function _clientCategory($i=0, $menu=0) {//��������� ��������
	$arr = array(
		1 => $menu ? '������� ����' : '�.�.�.',
		2 => '�����������'
	);
	return $i ? $arr[$i] : $arr;
}//_clientCategory()
/*
function _clientCategory($i=0, $menu=0) {//��������� ��������
	$arr = array(
		1 => $menu ? '������� ����' : '�.�.�.',
		2 => '�����������',
		3 => '��',
		4 => '���',
		5 => '���',
		6 => '���'
	);
	return $i ? $arr[$i] : $arr;
}//_clientCategory()
*/
/*
function _clientTelefon($r, $post=0) {//���������� ����������� �������� �������
	//$post - ���������� ���������� ����
	$phone = $r['category_id'] == 1 ? $r['phone'] : $r['org_phone'];
	if(!$phone && $r['category_id'] > 1 && $r['phone'])
		$phone = $r['phone'].($r['fio'] && $post ? '<span class="post">('.$r['fio'].')</span>' : '');
	return $phone;
}//_clientTelefon()
*/
/*
function _clientLink($arr, $fio=0, $tel=0) {//���������� ����� � ������ ������� � ������ ��� ������� �� id
	$clientArr = array(is_array($arr) ? 0 : $arr);
	if(is_array($arr)) {
		$ass = array();
		foreach($arr as $r) {
			$clientArr[$r['client_id']] = $r['client_id'];
			if($r['client_id'])
				$ass[$r['client_id']][] = $r['id'];
		}
		unset($clientArr[0]);
	}
	if(!empty($clientArr)) {
		$sql = "SELECT *
		        FROM `client`
				WHERE `ws_id`=".WS_ID."
				  AND `id` IN (".implode(',', $clientArr).")";
		$q = query($sql);
		if(!is_array($arr)) {
			if($r = mysql_fetch_assoc($q)) {
				$phone = _clientTelefon($r);
				return
					$fio ? _clientName($r)
						:
						'<a val="'.$r['id'].'" class="go-client-info' .
						($r['deleted'] ? ' deleted' : '') .
						($tel && $phone ? _tooltip($phone, -2, 'l') : '">') .
						_clientName($r) .
						'</a>';
			}
			return '';
		}
		while($r = mysql_fetch_assoc($q))
			foreach($ass[$r['id']] as $id) {
				$phone = _clientTelefon($r);
				$arr[$id]['client_link'] =
					'<a val="'.$r['id'].'" class="go-client-info'.
					($r['deleted'] ? ' deleted' : '').
					($tel && $phone ? _tooltip($phone, -2, 'l') : '">').
					_clientName($r).
					'</a>';
				$arr[$id]['client_fio'] = _clientName($r);
			}
	}
	return $arr;
}//_clientLink()
*/
function _clientVal($client_id, $i=0) {//��������� ������ �� ���� �� ����� �������
	$prefix = 'CLIENT_'.$client_id.'_';
	if(!defined($prefix.'LOADED')) {
		if(!$c = _clientQuery($client_id, 1))
			return 0;

		$org = $c['category_id'] != 1;

		// ������������ ������ ���������� ���
		$sql = "SELECT * FROM `_client_person` WHERE `client_id`=".$client_id." ORDER BY `id`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		$c['person'] = array();
		while($r = mysql_fetch_assoc($q))
			$c['person'][] = $r;

		$person_id = empty($c['person']) ? 0 : $c['person'][0]['id'];// id ������� ����������� ���� (��� �������� ����)

		define($prefix.'LOADED', 1);
		define($prefix.'ORG', $org);
		define($prefix.'PERSON_ID', $person_id);
		define($prefix.'FIO', $person_id ? $c['person'][0]['fio'] : '');

		define($prefix.'PASP_SERIA', $person_id ? $c['person'][0]['pasp_seria'] : '');
		define($prefix.'PASP_NOMER', $person_id ? $c['person'][0]['pasp_nomer'] : '');
		define($prefix.'PASP_ADRES', $person_id ? $c['person'][0]['pasp_adres'] : '');
		define($prefix.'PASP_OVD', $person_id ? $c['person'][0]['pasp_ovd'] : '');
		define($prefix.'PASP_DATA', $person_id ? $c['person'][0]['pasp_data'] : '');

		define($prefix.'NAME', $org ? $c['org_name'] : constant($prefix.'FIO'));
		define($prefix.'PHONE', $org ? $c['org_phone'] : $c['person'][0]['phone']);
		define($prefix.'ADRES', $org ? $c['org_adres'] : $c['person'][0]['adres']);
		define($prefix.'LINK', '<a href="'.URL.'&p=client&d=info&id='.$client_id.'">'.constant($prefix.'NAME').'</a>');
	}

	$send = array(
		'org' => constant($prefix.'ORG'),
		'name' => constant($prefix.'NAME'),
		'person_id' => constant($prefix.'PERSON_ID'),
		'fio' => constant($prefix.'FIO'),

		'pasp_seria' => constant($prefix.'PASP_SERIA'),
		'pasp_nomer' => constant($prefix.'PASP_NOMER'),
		'pasp_adres' => constant($prefix.'PASP_ADRES'),
		'pasp_ovd' => constant($prefix.'PASP_OVD'),
		'pasp_data' => constant($prefix.'PASP_DATA'),

		'phone' => constant($prefix.'PHONE'),
		'adres' => constant($prefix.'ADRES'),
		'link' => constant($prefix.'LINK')
	);

	return $i ? $send[$i] : $send;
}//_clientVal()
function _clientValToList($arr) {//������� ������ �������� � ������ �� client_id
	$ids = array();
	$arrIds = array();
	foreach($arr as $key => $r)
		if(!empty($r['client_id'])) {
			$ids[$r['client_id']] = 1;
			$arrIds[$r['client_id']][] = $key;
		}
	if(empty($ids))
		return $arr;

	$sql = "SELECT *
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `id` IN (".implode(',', array_keys($ids)).")";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q)) {
		$c = _clientVal($r['id']);
		foreach($arrIds[$r['id']] as $id) {
			$arr[$id] += array(
				'client_name' => $c['name'],
				'client_phone' => $c['phone'],
				'client_link' => '<a val="'.$r['id'].'" class="go-client-info'.($r['deleted'] ? ' deleted' : '').'">'.$c['name'].'</a>'
			//	'client_link' => '<a val="'.$r['id'].'" class="go-client-info'.($r['deleted'] ? ' deleted' : '').'">'._clientName($r).'</a>',
			);
		}
	}
	return $arr;
}//_clientValToList()
function _findMatch($reg, $v, $empty=0) {//��������� ��� ������� ������ �� ����������� ����������� ���������
	//$empty - ���������� ������ ��������, ���� ��� ����������
	if(empty($reg))
		return $empty ? '': $v;
	$reg = utf8($reg);
	$v = utf8($v);
	$v = preg_match($reg, $v) ? preg_replace($reg, '<em>\\1</em>', $v, 1) : ($empty ? '': $v);
	return win1251($v);
}//_findMatch()
function _regFilter($v) {//�������� ����������� ��������� �� ������������ �������
	$reg = '/(\[)/'; // ������ [
	if(preg_match($reg, $v))
		return '';
	return '/('.$v.')/iu';
}//_regFilter()
function _findRegular($find, $v, $empty=0) {//�������� � ��������� ��� ������� ������ �� ������� � ���������� ������
	$engRus = _engRusChar($find);
	$reg = _regFilter($find);

	$regEngRus = empty($engRus) ? '' : _regFilter($engRus);

	$send = _findMatch($reg, $v, 1);
	if(!$send)
		$send = _findMatch($regEngRus, $v, 1);

	if(!$empty && !$send)
		return $v;

	return $send;
}//_findRegular()

function _clientQuery($client_id, $withDeleted=0) {//������ ������ �� ����� �������
	$sql = "SELECT *
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID.
			  ($withDeleted ? '' : " AND !`deleted`")."
			  AND `id`=".$client_id;
	return query_assoc($sql, GLOBAL_MYSQL_CONNECT);
}//_clientSql()
function _clientInfo($client_id) {//����� ���������� � �������
	if(!$c = _clientQuery($client_id, 1))
		return _noauth('������� �� ����������');

	if($c['deleted'])
		if($c['join_id'])
			return _noauth('������ <b>'._clientVal($client_id, 'name').'</b><br /><br />'.
						   '��� ��������<br /><br />'.
						   '� �������� '._clientVal($c['join_id'], 'link').'.');
		else
			return _noauth('������ ��� �����.');

	define('ORG', $c['category_id'] > 1);

	/*
		$zayavData = zayav_spisok(array(
			'client_id' => $client_id,
			'limit' => 10
		));

		$zayavCartridge = zayav_cartridge_spisok(array(
			'client_id' => $client_id,
			'limit' => 10
		));

		$schet = report_schet_spisok(array('client_id'=>$client_id));

		$commCount = query_value("SELECT COUNT(`id`)
								  FROM `vk_comment`
								  WHERE `status`
									AND !`parent_id`
									AND `table_name`='client'
									AND `table_id`=".$client_id);

		$moneyCount = query_value("SELECT COUNT(`id`)
								   FROM `money`
								   WHERE `ws_id`=".WS_ID."
									 AND `deleted`=0
									 AND `client_id`=".$client_id);
		$money = '<div class="_empty">�������� ���.</div>';
		if($moneyCount) {
			$money = '<table class="_spisok _money">'.
				'<tr><th class="sum">�����'.
				'<th>��������'.
				'<th class="data">����';
			$sql = "SELECT *
					FROM `money`
					WHERE `ws_id`=".WS_ID."
					  AND !`deleted`
					  AND `client_id`=".$client_id;
			$q = query($sql);
			$moneyArr = array();
			while($r = mysql_fetch_assoc($q))
				$moneyArr[$r['id']] = $r;
			$moneyArr = _zayavNomerLink($moneyArr);
			foreach($moneyArr as $r) {
				$about = '';
				if($r['zayav_id'])
					$about .= '������ '.$r['zayav_link'].'. ';
				if($r['zp_id'])
					$about = '������� �������� '.$r['zp_id'].'. ';
				$about .= $r['prim'];
				$money .= '<tr><td class="sum"><b>'.round($r['sum'], 2).'</b>'.
					'<td>'.$about.
					'<td class="dtime" title="���: '._viewer($r['viewer_id_add'], 'name').'">'.FullDataTime($r['dtime_add']);
			}
			$money .= '</table>';
		}

		$remind = _remind_spisok(array('client_id'=>$client_id));

		$history = history(array('client_id'=>$client_id,'limit'=>15));
	*/

	return
		'<script type="text/javascript">'.
			'var CLIENT={'.
				'id:'.$client_id.','.
				'category_id:'.$c['category_id'].','.

				'person_id:'._clientVal($client_id, 'person_id').','.
				'fio:"'.addslashes(_clientVal($client_id, 'fio')).'",'.
				'phone:"'.addslashes(_clientVal($client_id, 'phone')).'",'.
				'adres:"'.addslashes(_clientVal($client_id, 'adres')).'",'.
				'pasp_seria:"'.addslashes(_clientVal($client_id, 'pasp_seria')).'",'.
				'pasp_nomer:"'.addslashes(_clientVal($client_id, 'pasp_nomer')).'",'.
				'pasp_adres:"'.addslashes(_clientVal($client_id, 'pasp_adres')).'",'.
				'pasp_ovd:"'.addslashes(_clientVal($client_id, 'pasp_ovd')).'",'.
				'pasp_data:"'.addslashes(_clientVal($client_id, 'pasp_data')).'",'.

				'org_name:"'.addslashes($c['org_name']).'",'.
				'org_phone:"'.addslashes($c['org_phone']).'",'.
				'org_fax:"'.addslashes($c['org_fax']).'",'.
				'org_adres:"'.addslashes($c['org_adres']).'",'.
				'org_inn:"'.addslashes($c['org_inn']).'",'.
				'org_kpp:"'.addslashes($c['org_kpp']).'",'.
				'person:'._clientInfoPerson($client_id, 'json').
			'};'.
	//		'DEVICE_IDS=['._zayavBaseDeviceIds($client_id).'],'.
	//		'VENDOR_IDS=['._zayavBaseVendorIds($client_id).'],'.
	//		'MODEL_IDS=['._zayavBaseModelIds($client_id).'];'.
		'</script>'.

		'<div id="client-info">'.
			'<table class="tabLR">'.
				'<tr><td class="left">'.
						_clientInfoBalans($c).
						_clientInfoContent($c).
						_clientInfoPasp($client_id).
						'<div id="person-spisok">'._clientInfoPerson($client_id).'</div>'.
						'<div class="dtime">'.
							'������� ��'.(_viewer($c['viewer_id_add'], 'sex') == 1 ? '����' : '��').' '.
							_viewer($c['viewer_id_add'], 'name').' '.
							FullData($c['dtime_add'], 1).
						'</div>'.

					'<td class="right">'.
						'<div class="rightLink">'.
							'<a id="zayav-add" val="client_'.$client_id.'"><b>����� ������</b></a>'.//'.(SERVIVE_CARTRIDGE ? ' class="cartridge"' : '').'todo
							'<a class="_remind-add">����� �����������</a>'.
							'<a id="client-edit">�������������</a>'.
						'</div>'.
			'</table>'.

			'<div id="dopLinks">'.
				'<a class="link sel" val="zayav">������</a>'.
	/*			'<a class="link sel" val="zayav">������'.($zayavData['all'] ? ' <b class="count">'.$zayavData['all'].'</b>' : '').'</a>'.
				'<a class="link" val="schet">�����'.($schet['all'] ? ' <b class="count">'.$schet['all'].'</b>' : '').'</a>'.
				'<a class="link" val="money">�������'.($moneyCount ? ' <b class="count">'.$moneyCount.'</b>' : '').'</a>'.
				'<a class="link" val="remind">�����������'.($remind['all'] ? ' <b class="count">'.$remind['all'].'</b>' : '').'</a>'.
				'<a class="link" val="comm">�������'.($commCount ? ' <b class="count">'.$commCount.'</b>' : '').'</a>'.
				'<a class="link" val="hist">�������'.($history['all'] ? ' <b class="count">'.$history['all'].'</b>' : '').'</a>'.
	*/
			'</div>'.

/*
		'<table class="tabLR">'.
		'<tr><td class="left">'.
		'<div id="zayav_spisok">'.
		($zayavData['all'] ? $zayavData['spisok'] : '').
		(!$zayavCartridge['all'] && !$zayavData['all'] ? $zayavData['spisok'] : '').
		($zayavCartridge['all'] ? $zayavCartridge['spisok'] : '').
		'</div>'.
		'<div id="schet_spisok">'.$schet['spisok'].'</div>'.
		'<div id="money_spisok">'.$money.'</div>'.
		'<div id="remind-spisok">'.$remind['spisok'].'</div>'.
		'<div id="comments">'._vkComment('client', $client_id).'</div>'.
		'<div id="histories">'.$history['spisok'].'</div>'.
		'<td class="right">'.
		'<div id="zayav_filter">'.
		'<div id="zayav_result">'.$zayavData['result'].'</div>'.
		'<div class="findHead">������ ������</div>'.
		_rightLink('status', _zayavStatusName()).
		_check('diff', '������������ ������').
		'<div class="findHead">����������</div><div id="dev"></div>'.
		'</div>'.
		'</table>'.
*/
		'</div>';
}//_clientInfo()
function _clientInfoBalans($r) {//����������� �������� ������� �������
	return
		'<div style="color:#'.($r['balans'] < 0 ? 'A00' : '090').'" class="ci-balans'._tooltip('������', -19).
			round($r['balans'], 2).
		'</div>';
}//_clientInfoBalans()
function _clientInfoContent($r) {//�������� ���������� � �������
	return
		'<div id="ci-name">'._clientVal($r['id'], 'name').'</div>'.
		'<table id="ci-tab">'.
			(_clientVal($r['id'], 'phone') ? '<tr><td class="label">�������:<td>'._clientVal($r['id'], 'phone') : '').
			(_clientVal($r['id'], 'adres') ? '<tr><td class="label">�����:<td>'._clientVal($r['id'], 'adres') : '').
			(ORG && $r['org_fax'] ? '<tr><td class="label">����:<td>'.$r['org_fax'] : '').
			(ORG && $r['org_adres'] ? '<tr><td class="label top">�����:<td>'.$r['org_adres'] : '').
			(ORG && $r['org_inn'] ? '<tr><td class="label">���:<td>'.$r['org_inn'] : '').
			(ORG && $r['org_kpp'] ? '<tr><td class="label">���:<td>'.$r['org_kpp'] : '').
		'</table>';
}//_clientInfoContent()
function _clientInfoPerson($client_id, $type='html') {// ������������ ������ ���������� ���
	$sql = "SELECT * FROM `_client_person` WHERE `client_id`=".$client_id." ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$person = array();
	while($r = mysql_fetch_assoc($q))
		$person[] = $r;

	if(!_clientVal($client_id, 'org'))
		array_shift($person);

	$html = '<table class="_spisok">';
	$json = array();
	$array = array();
	foreach($person as $n => $r) {
		$html .=
			'<tr><td class="n">'.($n + 1).
				'<td>'.$r['fio'].
					  ($r['phone'] ? ', '.$r['phone'] : '').
					  ($r['adres'] ? '<br /><span class="adres"><tt>�����:</tt> '.$r['adres'].'</span>' : '').
				'<td>'.($r['post'] ? '<u>'.$r['post'].'</u>' : '').
				'<td class="td-person-ed">'.
					'<div val="'.$r['id'].'" class="person-edit img_edit'._tooltip('��������', -33).'</div>'.
					'<div val="'.$r['id'].'" class="person-del img_del'._tooltip('�������', -29).'</div>';
		$json[] =
			$r['id'].':{'.
				'fio:"'.addslashes($r['fio']).'",'.
				'phone:"'.addslashes($r['phone']).'",'.
				'adres:"'.addslashes($r['adres']).'",'.
				'post:"'.addslashes($r['post']).'",'.
				'pasp_seria:"'.addslashes($r['pasp_seria']).'",'.
				'pasp_nomer:"'.addslashes($r['pasp_nomer']).'",'.
				'pasp_adres:"'.addslashes($r['pasp_adres']).'",'.
				'pasp_ovd:"'.addslashes($r['pasp_ovd']).'",'.
				'pasp_data:"'.addslashes($r['pasp_data']).'"'.
			'}';
		$array[$r['id']] = array(
			'fio' => utf8($r['fio']),
			'phone' => utf8($r['phone']),
			'adres' => utf8($r['adres']),
			'post' => utf8($r['post']),
			'pasp_seria' => utf8($r['pasp_seria']),
			'pasp_nomer' => utf8($r['pasp_nomer']),
			'pasp_adres' => utf8($r['pasp_adres']),
			'pasp_ovd' => utf8($r['pasp_ovd']),
			'pasp_data' => utf8($r['pasp_data'])
		);
	}
	$html .= '</table>';

	switch($type) {
		default:
		case 'html':
			return
				'<div id="ci-person">'.
					'<h1>���������� ����:<a id="person-add" class="'._tooltip('�������� ���������� ����', -70).'��������</a></h1>'.
					$html.
				'</div>';
		case 'json': return '{'.implode(',', $json).'}';
		case 'array': return $array;
	}
}//_clientInfoPerson()
function _clientInfoPasp($client_id) {//���������� ������
	$r = _clientVal($client_id);

	if($r['org'] || !$r['pasp_seria'] && !$r['pasp_nomer'] && !$r['pasp_adres'] && !$r['pasp_ovd'] && !$r['pasp_data'])
		return '';

	return
		'<div id="pasp-head">���������� ������:</div>'.
		'<table id="pasp-tab">'.
			'<tr><td class="label">����� � �����:<td>'.$r['pasp_seria'].' '.$r['pasp_nomer'].
			'<tr><td class="label">��������:<td>'.$r['pasp_adres'].
			'<tr><td class="label">�����:<td>'.$r['pasp_ovd'].', '.$r['pasp_data'].
		'</table>';
}//_clientInfoPasp()

function clientBalansUpdate($client_id, $ws_id=WS_ID) {//���������� ������� �������
	if(!$client_id)
		return 0;
	$prihod = query_value("SELECT IFNULL(SUM(`sum`),0)
						   FROM `money`
						   WHERE `ws_id`=".$ws_id."
							 AND !`deleted`
							 AND `client_id`=".$client_id."
							 AND !`zp_id`
							 AND `sum`>0");
	$acc = query_value("SELECT IFNULL(SUM(`sum`),0)
						FROM `accrual`
						WHERE `ws_id`=".$ws_id."
						  AND !`deleted`
						  AND `client_id`=".$client_id);
	$balans = $prihod - $acc;
	query("UPDATE `client` SET `balans`=".$balans." WHERE `id`=".$client_id);
	return $balans;
}//clientBalansUpdate()



