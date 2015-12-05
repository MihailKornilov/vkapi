<?php
function _clientCase($v=array()) {//����� ���������� � ��������� ��� ����������
	$filterDef = $v + array(
		'CLIENT_FILTER_DOLG' => 1,//�������-������ "��������"
		'CLIENT_FILTER_OPL' => 1  //�������-������ "����������"
	);
	foreach($filterDef as $name => $key)
		define($name, $key);
	switch(@$_GET['d']) {
		case 'info': return _clientInfo();
		default: return _client(_hashFilter('client'));
	}
}//_clientCase()

function _client($v) {
	return client_list($v);
}//_client()
function client_list($v) {// �������� �� ������� ��������
	$data = client_data($v);
	$v = $data['filter'];

	//���� ������� ������� ������ ����� ���������, �� ������� �� �������� �� ����� ����������
	$sql = "SELECT COUNT(DISTINCT `category_id`)
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`";
	$categoryShow = query_value($sql, GLOBAL_MYSQL_CONNECT) > 1;
	$category = array(
		0 => '����� ���������',
		1 => '������� ����',
		2 => '�����������'
	);
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
		   ($categoryShow ? '<div class="f-label">���������</div>'.
							_radio('category_id', $category, $v['category_id'], 1)
			: '').
	  (CLIENT_FILTER_DOLG ? _check('dolg', '��������', $v['dolg']) : '').
//							_check('active', '� ��������� ��������', $v['active']).
//							_check('comm', '���� �������', $v['comm']).
							_check('opl', '������� ����������', $v['opl']).
						'</div>'.
			'</table>'.
		'</div>';
}//client_list()
function clientFilter($v) {
	$default = array(
		'limit' => 20,
		'page' => 1,
		'find' => '',
		'dolg' => 0,
		'active' => 0,
		'comm' => 0,
		'opl' => 0,
		'category_id' => 0
	);
	$filter = array(
		'limit' => _num(@$v['limit']) ? $v['limit'] : 20,
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'find' => strtolower(trim(@$v['find'])),
		'dolg' => _bool(@$v['dolg']),
		'active' => _bool(@$v['active']),
		'comm' => _bool(@$v['comm']),
		'opl' => _bool(@$v['opl']),
		'category_id' => _num(@$v['category_id']),
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
	$filter = _filterJs('CLIENT', $filter);

	$cond = "`app_id`=".APP_ID."
		 AND `ws_id`=".WS_ID."
		 AND !`deleted`";
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
		if($filter['category_id'])
			$cond .= " AND `category_id`=".$filter['category_id'];
		if($filter['dolg']) {
			$cond .= " AND `balans`<0";
			$sql = "SELECT SUM(`balans`)
					FROM `_client`
					WHERE ".$cond."
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
			'spisok' => $filter['js'].'<div class="_empty">�������� �� �������.</div>',
			'filter' => $filter
		);

	$send['all'] = $all;
	$send['result'] = '������'._end($all, ' ', '� ').$all.' ������'._end($all, '', '�', '��').
					  ($dolg ? '<em>(����� ����� ����� = <b id="dolg">'._sumSpace($dolg).'</b> ���.)</em>' : '').
					  ($plus ? '<em>(����� = '.$plus.' ���.)</em>' : '').
					  $filter['clear'];
	$send['filter'] = $filter;
	$send['spisok'] = $filter['js'];

	$spisok = array();
	$sql = "SELECT *,
				   0 `zayav_count`,
				   0 `zayav_wait`,
				   0 `zayav_ready`,
				   0 `zayav_fail`,
				   '' `comm`,
				   '' `fio`,
				   '' `phone`,
				   '' `adres`
			FROM `_client`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT "._startLimit($filter);
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

	// ��� � �������� �������� (���������� ���)
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
			$spisok[$r['client_id']]['fio'] =   FIND ? _findRegular($filter['find'], $r['fio']) : $r['fio'];
			$spisok[$r['client_id']]['phone'] = FIND ? _findRegular($filter['find'], $r['phone']) : $r['phone'];
			$spisok[$r['client_id']]['adres'] = FIND ? _findRegular($filter['find'], $r['adres'], 1) : $r['adres'];
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

	$spisok = _zayavCountToClient($spisok);
/*
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
*/
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
							(FIND && $p['adres'] ? '<br />'.$p['adres'] : '');

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
			'<div class="unit" id="cu'.$r['id'].'">'.
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

	 $send['spisok'] .= _next($filter + array(
			'type' => 1,
			'all' => $all
		));

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
		define($prefix.'BALANS', round($c['balans']), 2);

		define($prefix.'PASP_SERIA', $person_id ? $c['person'][0]['pasp_seria'] : '');
		define($prefix.'PASP_NOMER', $person_id ? $c['person'][0]['pasp_nomer'] : '');
		define($prefix.'PASP_ADRES', $person_id ? $c['person'][0]['pasp_adres'] : '');
		define($prefix.'PASP_OVD', $person_id ? $c['person'][0]['pasp_ovd'] : '');
		define($prefix.'PASP_DATA', $person_id ? $c['person'][0]['pasp_data'] : '');

		define($prefix.'NAME', $org ? $c['org_name'] : constant($prefix.'FIO'));
		define($prefix.'PHONE', $org ? $c['org_phone'] : $c['person'][0]['phone']);
		define($prefix.'ADRES', $org ? $c['org_adres'] : $c['person'][0]['adres']);
		define($prefix.'LINK', '<a href="'.URL.'&p=client&d=info&id='.$client_id.'">'.constant($prefix.'NAME').'</a>');
		define($prefix.'GO',
			'<a val="'.$c['id'].'" class="client-info-go'.($c['deleted'] ? ' deleted' : '').
				(constant($prefix.'PHONE') ? _tooltip(constant($prefix.'PHONE'), -1, 'l') : '">').
				constant($prefix.'NAME').
			'</a>'
		);

		define($prefix.'INN', $org ? $c['org_inn'] : '');
		define($prefix.'KPP', $org ? $c['org_kpp'] : '');
	}

	$send = array(
		'org' => constant($prefix.'ORG'),
		'name' => constant($prefix.'NAME'),
		'person_id' => constant($prefix.'PERSON_ID'),
		'fio' => constant($prefix.'FIO'),
		'balans' => constant($prefix.'BALANS'),

		'pasp_seria' => constant($prefix.'PASP_SERIA'),
		'pasp_nomer' => constant($prefix.'PASP_NOMER'),
		'pasp_adres' => constant($prefix.'PASP_ADRES'),
		'pasp_ovd' => constant($prefix.'PASP_OVD'),
		'pasp_data' => constant($prefix.'PASP_DATA'),

		'phone' => constant($prefix.'PHONE'),
		'adres' => constant($prefix.'ADRES'),
		'link' => constant($prefix.'LINK'),
		'go' => constant($prefix.'GO'),

		'inn' => constant($prefix.'INN'),
		'kpp' => constant($prefix.'KPP')
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
	$client = array();
	while($r = mysql_fetch_assoc($q))
		$client[$r['id']] = $r;

	// ��� � �������� �������� (���������� ���)
	$sql = "SELECT *
			FROM `_client_person`
			WHERE `client_id` IN (".implode(',', array_keys($ids)).")
			ORDER BY `client_id`,`id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$k = 0;
	$client_id = key($client);
	while($r = mysql_fetch_assoc($q)) {
		if($client_id != $r['client_id']) {
			$client_id = $r['client_id'];
			$k = 0;
		}

		if(!$k) {
			$client[$r['client_id']]['fio'] = $r['fio'];
			$client[$r['client_id']]['phone'] = $r['phone'];
		}
		$k++;
	}

	foreach($client as $r) {
		$org = $r['category_id'] != 1;
		foreach($arrIds[$r['id']] as $id) {
			$name = $org ? $r['org_name'] : $r['fio'];
			$phone = $org ? $r['org_phone'] : $r['phone'];
			$arr[$id] += array(
				'client_name' => $name,
				'client_phone' => $phone,
				'client_balans' => round($r['balans'], 2),
				'client_link' => '<a href="'.URL.'&p=client&d=info&id='.$r['id'].'"'.($r['deleted'] ? ' class="deleted"' : '').'>'.$name.'</a>',
				'client_go' =>
					'<a val="'.$r['id'].'" class="client-info-go'.($r['deleted'] ? ' deleted' : '').
						($phone ? _tooltip($phone, -1, 'l') : '">').
						$name.
					'</a>'
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
}//_clientQuery()
function _clientDopLink($name, $arr) {//���� � ��������������� �������� (������, ����������, �������, �������...)
	return
		$arr['all'] ?
			'<a class="link">'.$name.' <b class="count">'.$arr['all'].'</b></a>'
			: '';
}//_clientDopLink()
function _clientDopContent($name, $arr) {//���������� �������������� �������� (����������, �������, �������...)
	return
		$arr['all'] ?
			'<div class="ci-cont" id="'.$name.'-spisok">'.$arr['spisok'].'</div>'
			: '';
}//_clientDopContent()
function _clientDopContentZayav($name, $arr, $arr2) {//���������� �������������� ������� (������)
	if(!$arr['all']) //���� ������ �� ������������ ���, �� ����� ��������� ������ � �����������
		$arr = $arr2;
	$arr['all'] += $arr2['all'];
	return
		$arr['all'] ?
			'<div class="ci-cont" id="'.$name.'-spisok">'.
				'<div id="spisok1">'.$arr['spisok'].'</div>'.
				'<div id="spisok2" class="dn">'.$arr2['spisok'].'</div>'.
			'</div>'
			: '';
}//_clientDopContentZayav()
function _clientDopRight($name, $arr, $filterContent) {//������ ������� (������� ������) ��� �������������� ������� (������, ����������, �������, �������...)
	return
		$arr['all'] ?
			'<div class="ci-right" id="'.$name.'-right">'.$filterContent.'</div>'
			: '';
}//_clientDopRight()
function _clientInfoZayavRight($zayav, $cartridge) {
	if(!$zayav['all'] || !$cartridge['all'])
		return '';

	$list = array(
		1 => '������������<em>'.$zayav['all'].'</em>',
		2 => '���������<em>'.$cartridge['all'].'</em>'
	);
	return
	'<div class="f-label">��� ������</div>'.
	_radio('zayav-type', $list, 1, 1);
}//_clientInfoZayavRight()
function _clientInfo() {//����� ���������� � �������
	if(!$client_id = _num(@$_GET['id']))
		return _err('�������� �� ����������');

	if(!$c = _clientQuery($client_id, 1))
		return _err('������� �� ����������');

	if($c['deleted'])
		if($c['join_id'])
			return _noauth('������ <b>'._clientVal($client_id, 'name').'</b><br /><br />'.
						   '��� ��������<br /><br />'.
						   '� �������� '._clientVal($c['join_id'], 'link').'.');
		else
			return _noauth('������ ��� �����.');

	define('ORG', $c['category_id'] > 1);

	$zayav = zayav_spisok(array(
			'client_id' => $client_id,
			'limit' => 10
	));
	$cartridge = zayav_cartridge_spisok(array(
			'client_id' => $client_id,
			'limit' => 15
	));

	$accrual = _accrual_spisok(array('client_id'=>$client_id));
	$income = income_spisok(array('client_id'=>$client_id));
	$remind = _remind_spisok(array('client_id'=>$client_id));

	/*


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


	*/

	$hist = _history(array('client_id'=>$client_id,'limit'=>20));

	return
		'<script type="text/javascript">'.
			'var CLIENT={'.
				'id:'.$client_id.','.
				'category_id:'.$c['category_id'].','.
				'name:"'._clientVal($client_id, 'name').'",'.

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
							'������� '._viewerAdded($c['viewer_id_add']).' '.
							FullData($c['dtime_add'], 1).
						'</div>'.

					'<td class="right">'.
						'<div class="rightLink">'.
							'<a id="zayav-add"'.(SERVIVE_CARTRIDGE ? ' class="cartridge"' : '').'val="client_'.$client_id.'"><b>����� ������</b></a>'.
							'<a class="_remind-add">����� �����������</a>'.
							'<a id="client-schet-add">���� �� ������</a>'.
							'<a id="client-edit">�������������</a>'.
							'<a id="client-del">������� �������</a>'.
						'</div>'.
			'</table>'.

			'<div id="dopLinks">'.
				_clientDopLink('������', array('all' => $zayav['all'] + $cartridge['all'])).
				_clientDopLink('����������', $accrual).
				_clientDopLink('�������', $income).
				_clientDopLink('�����������', $remind).
				_clientDopLink('�������', $hist).
			'</div>'.

			'<table class="tabLR">'.
				'<tr><td class="left">'.
						_clientDopContentZayav('zayav', $zayav, $cartridge).
						_clientDopContent('accrual', $accrual).
						_clientDopContent('income', $income).
						_clientDopContent('remind', $remind).
						_clientDopContent('history', $hist).
					'<td class="right">'.
						_clientDopRight('zayav', array('all' => $zayav['all'] + $cartridge['all']), _clientInfoZayavRight($zayav, $cartridge)).
						_clientDopRight('accrual', $accrual, '').
						_clientDopRight('income', $income, '').
						_clientDopRight('remind', $remind, '').
						_clientDopRight('history', $hist, _history_right()).
				'</div>'.
			'</table>'.

		'</div>';
}//_clientInfo()
function _clientInfoBalans($r) {//����������� �������� ������� �������
	return
		'<a style="color:#'.($r['balans'] < 0 ? 'A00' : '090').'"'.
		  ' val="2:'.$r['id'].'"'.
		  ' class="ci-balans _balans-show'._tooltip('������', -19).
			round($r['balans'], 2).
		'</a>';
}//_clientInfoBalans()
function _clientInfoContent($r) {//�������� ���������� � �������
	return
		'<div id="ci-name">'._clientVal($r['id'], 'name').'</div>'.
		'<table id="ci-tab">'.
			(_clientVal($r['id'], 'phone') ? '<tr><td class="label">�������:<td>'._clientVal($r['id'], 'phone') : '').
			(_clientVal($r['id'], 'adres') ? '<tr><td class="label">�����:<td>'._clientVal($r['id'], 'adres') : '').
			(ORG && $r['org_fax'] ? '<tr><td class="label">����:<td>'.$r['org_fax'] : '').
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

function clientBalansUpdate($client_id) {//���������� ������� �������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_accrual`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `client_id`=".$client_id;
	$accrual = query_value($sql, GLOBAL_MYSQL_CONNECT);

	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`zp_id`
			  AND !`deleted`
			  AND `client_id`=".$client_id;
	$income = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//��������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_refund`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `client_id`=".$client_id;
	$refund = query_value($sql, GLOBAL_MYSQL_CONNECT);

	$balans = $income - $accrual - $refund;

	$sql = "UPDATE `_client`
			SET `balans`=".$balans."
			WHERE `id`=".$client_id;
	query($sql, GLOBAL_MYSQL_CONNECT);

	return $balans;
}//clientBalansUpdate()



