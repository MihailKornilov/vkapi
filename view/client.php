<?php
function _client($v) {
	return client_list($v);
}//_client()
function client_list($v) {// �������� �� ������� ��������
	$data = client_data($v);
	$v = $data['filter'];
	return
		'<div id="client">'.
			'<div id="find"></div>'.
			'<div class="result">'.$data['result'].'</div>'.
			'<table class="tabLR">'.
				'<tr><td class="left">'.$data['spisok'].
					'<td class="right">'.
						'<div id="buttonCreate"><a>����� ������</a></div>'.
						'<div class="filter'.($v['find'] ? ' dn' : '').'">'.
							_check('dolg', '��������', $v['dolg']).
							_check('active', '� ��������� ��������', $v['active']).
							_check('comm', '���� �������', $v['comm']).
							_check('opl', '������� ����������', $v['opl']).
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
		'dolg' => _isbool(@$v['dolg']),
		'active' => _isbool(@$v['active']),
		'comm' => _isbool(@$v['comm']),
		'opl' => _isbool(@$v['opl']),
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
	$cond = "`ws_id`=".WS_ID." AND !`deleted`";
	$reg = '';
	$regEngRus = '';
	$dolg = 0;
	$plus = 0;
	define('FIND', !empty($filter['find']));
	if(FIND) {
		$engRus = _engRusChar($filter['find']);
		$cond .= " AND (`find` LIKE '%".$filter['find']."%'
						".($engRus ? "OR `find` LIKE '%".$engRus."%'": '')."
					   )";
		$reg = _regFilter($filter['find']);
		if(!empty($engRus))
			$regEngRus = _regFilter($engRus);
	} else {
		if($filter['dolg']) {
			$cond .= " AND `balans`<0";
			$dolg = abs(query_value("SELECT SUM(`balans`) FROM `_client` WHERE `ws_id`=".WS_ID." AND !`deleted` AND `balans`<0", GLOBAL_MYSQL_CONNECT));
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
			$plus = abs(query_value("SELECT SUM(`balans`) FROM `_client` WHERE !`deleted` AND `balans`>0", GLOBAL_MYSQL_CONNECT));
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
					  ($dolg ? '<em>(����� ����� ����� = '.$dolg.' ���.)</em>' : '').
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
			$r['org_name'] = _findMatch($reg, $r['org_name']);
			$r['org_phone'] = _findMatch($reg, $r['org_phone']);
			$r['org_fax'] = _findMatch($reg, $r['org_fax']);
			$r['org_adres'] = _findMatch($reg, $r['org_adres'], 1);
			$r['org_inn'] = _findMatch($reg, $r['org_inn'], 1);
			$r['org_kpp'] = _findMatch($reg, $r['org_kpp'], 1);

			$r['org_name'] = _findMatch($regEngRus, $r['org_name']);
			$r['org_phone'] = _findMatch($regEngRus, $r['org_phone']);
			$r['org_fax'] = _findMatch($regEngRus, $r['org_fax']);
			$r['org_adres'] = _findMatch($regEngRus, $r['org_adres'], 1);
			$r['org_inn'] = _findMatch($regEngRus, $r['org_inn'], 1);
			$r['org_kpp'] = _findMatch($regEngRus, $r['org_kpp'], 1);
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

		$regOk = FIND && (_findMatch($reg, $r['fio'], 1) || _findMatch($reg, $r['phone'], 1));// ������� �� ���������� ��������� � ������� �������� ������

		if(!$k) {
			$spisok[$r['client_id']]['fio'] = (FIND ? _findMatch($reg, $r['fio']) : $r['fio']);
			$spisok[$r['client_id']]['phone'] = (FIND ? _findMatch($reg, $r['phone']) : $r['phone']);
			$spisok[$r['client_id']]['post'] = $r['post'];
		} else {
			if($regOk) // �������������� ���������� ���� ������������ ������ ��� ���������� � ������� ������
				$spisok[$r['client_id']]['person'][] = array(
					'fio' => _findMatch($reg, $r['fio']),
					'phone' => _findMatch($reg, $r['phone']),
					'post' => $r['post']
				);
		}
		$k++;
	}
_pre($spisok);
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
		// ������ ���������� ���
		$person = '';
		if(FIND)
			foreach($r['person'] as $p)
				$person .= '<tr><td class="label top">'.($p['post'] ? $p['post'] : '���. ����').':<td>'.$p['fio'].'<br />'.$p['phone'];

		$left =
			'<table class="l-tab">'.
				'<tr><td class="label top">'._clientCategory($r['category_id']).':'.
					'<td><a href="'.URL.'&p=client&d=info&id='.$r['id'].'">'._clientName($r).'</a>'.
 ($r['phone'] ? '<tr><td class="label top">�������:<td>'.$r['phone'] : '').
		 (FIND && $r['org_fax'] ? '<tr><td class="label">����:<td>'.$r['org_fax'] : '').
	   (FIND && $r['org_adres'] ? '<tr><td class="label top">�����:<td>'.$r['org_adres'] : '').
		 (FIND && $r['org_inn'] ? '<tr><td class="label">���:<td>'.$r['org_inn'] : '').
		 (FIND && $r['org_kpp'] ? '<tr><td class="label">���:<td>'.$r['org_kpp'] : '').
		     ($r['category_id'] != 1 && $r['fio'] ? '<tr><td class="label top">'.($r['post'] ? $r['post'] : '���. ����').':<td>'.$r['fio'] : '').
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
							($r['balans'] ?
								'<div style="color:#'.($r['balans'] < 0 ? 'A00' : '090').'" class="balans'.
									_tooltip('������', -15).
									$r['balans'].
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
		2 => '�����������',
		3 => '��',
		4 => '���',
		5 => '���',
		6 => '���'
	);
	return $i ? $arr[$i] : $arr;
}//_clientCategory()
function _clientName($r) {//���������� ����������� ����� �������
	return $r['category_id'] == 1 ? $r['fio'] : $r['org_name'];
}//_clientName()
function _clientTelefon($r, $post=0) {//���������� ����������� �������� �������
	//$post - ���������� ���������� ����
	$phone = $r['category_id'] == 1 ? $r['phone'] : $r['org_phone'];
	if(!$phone && $r['category_id'] > 1 && $r['phone'])
		$phone = $r['phone'].($r['fio'] && $post ? '<span class="post">('.$r['fio'].')</span>' : '');
	return $phone;
}//_clientTelefon()
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
						'<a val="' . $r['id'] . '" class="go-client-info' .
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
function _clientValues($arr) {//������ � �������, ������������� � ������
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
		        FROM `client`
				WHERE `ws_id`=".WS_ID."
				  AND `id` IN (".implode(',', array_keys($ids)).")";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		foreach($arrIds[$r['id']] as $id) {
			$arr[$id] += array(
				'client_link' => '<a val="'.$r['id'].'" class="go-client-info'.($r['deleted'] ? ' deleted' : '').'">'._clientName($r).'</a>',
				'client_telefon' => _clientTelefon($r),
				'client_fio' => _clientName($r)
			);
		}
	return $arr;
}//_clientValues()
function _findMatch($reg, $v, $empty=0) {//��������� ��� ������� ������
	//$empty - ���������� ������ ��������, ���� ��� ����������
	if(empty($reg))
		return $empty ? '': $v;
	$reg = utf8($reg);
	$v = utf8($v);
	$v = preg_match($reg, $v) ? preg_replace($reg, '<em>\\1</em>', $v, 1) : ($empty ? '': $v);
	return win1251($v);
}//_findMatch()
function _regFilter($v) {//�������� ����������� ��������� �� ������������ �������
	$reg = '/(\[)/';
	if(preg_match($reg, $v))
		return '';
	return '/('.$v.')/iu';
}//_regFilter()


function client_info($client_id) {
	$sql = "SELECT * FROM `client` WHERE `ws_id`=".WS_ID." AND `id`=".$client_id;
	if(!$c = query_assoc($sql))
		return _noauth('������� �� ����������');
	if($c['deleted'])
		if($c['join_id'])
			return _noauth('������ <b>'.$c['fio'].'</b> ��� �������� � �������� '._clientLink($c['join_id']).'.');
		else
			return _noauth('������ ��� �����.');

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

	define('ORG', $c['category_id'] > 1);
	$catName = $c['category_id'] > 1 ? _clientCategory($c['category_id']).' ' : '';
	$phone = _clientTelefon($c, 1);

	$post = '';
	if(ORG && ($c['fio1'] || $c['fio2'] || $c['fio3'] || $c['telefon1'] || $c['telefon2'] || $c['telefon3'])) {
		$post = '<tr><td class="label" colspan="2">���������� ����:';
		for($n = 1; $n <= 3; $n++)
			$post .= ($c['fio'.$n] || $c['telefon'.$n] ?
				'<tr><td><td>'.
				$c['fio'.$n].
				($c['post'.$n] ? '<span class="post">('.$c['post'.$n].')</span>' : '').
				($c['telefon'.$n] ? ', '.$c['telefon'.$n] : '')
				: '');
	}

	return
		'<script type="text/javascript">'.
		'var CLIENT={'.
		'id:'.$client_id.','.
		'fio:"'.addslashes($catName._clientName($c)).'",'.//��� remind
		'category_id:'.$c['category_id'].','.
		'org_name:"'.addslashes($c['org_name']).'",'.
		'org_telefon:"'.addslashes($c['org_telefon']).'",'.
		'org_fax:"'.addslashes($c['org_fax']).'",'.
		'org_adres:"'.addslashes($c['org_adres']).'",'.
		'org_inn:"'.addslashes($c['org_inn']).'",'.
		'org_kpp:"'.addslashes($c['org_kpp']).'",'.
		'fio1:"'.addslashes($c['fio1']).'",'.
		'fio2:"'.addslashes($c['fio2']).'",'.
		'fio3:"'.addslashes($c['fio3']).'",'.
		'telefon1:"'.addslashes($c['telefon1']).'",'.
		'telefon2:"'.addslashes($c['telefon2']).'",'.
		'telefon3:"'.addslashes($c['telefon3']).'",'.
		'post1:"'.addslashes($c['post1']).'",'.
		'post2:"'.addslashes($c['post2']).'",'.
		'post3:"'.addslashes($c['post3']).'"'.
		'},'.
		'DEVICE_IDS=['._zayavBaseDeviceIds($client_id).'],'.
		'VENDOR_IDS=['._zayavBaseVendorIds($client_id).'],'.
		'MODEL_IDS=['._zayavBaseModelIds($client_id).'];'.
		'</script>'.

		'<input type="hidden" id="info-dop" value="'.addslashes($c['info_dop']).'"/>'.

		'<div id="clientInfo">'.
		'<table class="tabLR">'.
		'<tr><td class="left">'.
		'<div style="color:#'.($c['balans'] < 0 ? 'A00' : '090').'" class="ci-balans'._tooltip('������', -19).$c['balans'].'</div>'.
		'<div class="fio">'.$catName._clientName($c).'</div>'.
		'<table id="ci-tab">'.
		($phone ? '<tr><td class="label">�������:<td>'.$phone : '').
		(ORG && $c['org_fax'] ? '<tr><td class="label">����:<td>'.$c['org_fax'] : '').
		(ORG && $c['org_adres'] ? '<tr><td class="label">�����:<td>'.$c['org_adres'] : '').
		(ORG && $c['org_inn'] ? '<tr><td class="label">���:<td>'.$c['org_inn'] : '').
		(ORG && $c['org_kpp'] ? '<tr><td class="label">���:<td>'.$c['org_kpp'] : '').
		$post.
		($c['info_dop'] ? '<tr><td class="label top">�������������:<td>'.nl2br($c['info_dop']) : '').
		'</table>'.
		'<div class="dtime">������� ��'.(_viewer($c['viewer_id_add'], 'sex') == 1 ? '����' : '��').' '._viewer($c['viewer_id_add'], 'name').' '.FullData($c['dtime_add'], 1).'</div>'.

		'<td class="right">'.
		'<div class="rightLink">'.
		'<a id="zayav-add" val="client_'.$client_id.'"'.(SERVIVE_CARTRIDGE ? ' class="cartridge"' : '').'><b>����� ������</b></a>'.
		'<a class="_remind-add">����� �����������</a>'.
		'<a id="client-edit">�������������</a>'.
		'</div>'.
		'</table>'.

		'<div id="dopLinks">'.
		'<a class="link sel" val="zayav">������'.($zayavData['all'] ? ' <b class="count">'.$zayavData['all'].'</b>' : '').'</a>'.
		'<a class="link" val="schet">�����'.($schet['all'] ? ' <b class="count">'.$schet['all'].'</b>' : '').'</a>'.
		'<a class="link" val="money">�������'.($moneyCount ? ' <b class="count">'.$moneyCount.'</b>' : '').'</a>'.
		'<a class="link" val="remind">�����������'.($remind['all'] ? ' <b class="count">'.$remind['all'].'</b>' : '').'</a>'.
		'<a class="link" val="comm">�������'.($commCount ? ' <b class="count">'.$commCount.'</b>' : '').'</a>'.
		'<a class="link" val="hist">�������'.($history['all'] ? ' <b class="count">'.$history['all'].'</b>' : '').'</a>'.
		'</div>'.

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
		'</div>';
}//client_info()
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



