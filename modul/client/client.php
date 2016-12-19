<?php
function _client_script() {//������� ��� ��������
	return
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/client/client'.MIN.'.css?'.VERSION.'" />'.
		'<script src="'.API_HTML.'/modul/client/client'.MIN.'.js?'.VERSION.'"></script>';
}
function _clientCase() {//����� ���������� � ��������� ��� ����������
	switch(@$_GET['d']) {
		case 'info': return _clientInfo();
		case 'poa': return _clientPoa();
		case 'from': return _client_from();
		default: return _client(_hashFilter('client'));
	}
}



function _clientPole($v=array()) {//��������� �������� �����
	$category_id = _num(@$v['category_id']);
	$type_id = _num(@$v['type_id']);
	$pole_id = _num(@$v['pole_id']);
	$i = _txt(@$v['i']);
	
	
	$sql = "SELECT * FROM `_client_pole` ORDER BY `id`";
	$cp = query_arr($sql);

	//������������� ����� ��� ������ ��������� ��������
	$use = array();
	$sql = "SELECT *
			FROM `_client_pole_use`
			WHERE `app_id`=".APP_ID."
			ORDER BY `sort`";
	$cpu = query_arr($sql);
	foreach($cpu as $r) {
		$name = $r['label'] ? $r['label'] : $cp[$r['pole_id']]['name'];
		$label = $name.':';
		$label .= $r['require'] ? '*' : '';
		$use[$r['category_id']][] = array(
			'pole_id' => $r['pole_id'],
			'type_id' => $cp[$r['pole_id']]['type_id'],
			'name' => $name,
			'label' => $label,
			'require' => $r['require']
		);
	}

	//��������������� ������ html ��� ��������, ��������������
	if($i == 'edit') {
		if(empty($use[$category_id]))
			return '';

		$send = '';
		foreach($use[$category_id] as $r) {
			if($r['type_id'] != $type_id)
				continue;
			$send .= str_replace('{label}', $r['label'], _clientPoleEdit($r['pole_id'], $v));
		}
		return $send;
	}

	//��������������� ������ html ��� ����������
	if($i == 'info') {
		if(empty($use[$category_id]))
			return '';

		$send = '';
		$ass = array();
		foreach($use[$category_id] as $r) {
			$send .= str_replace('{label}', $r['name'], _clientPoleInfo($r['pole_id'], $v));
			$ass[$r['pole_id']] = 1;
		}

		//������� �����, ������� �� ��������, �� ���� ��������� �����
		foreach($cp as $r) {
			if(isset($ass[$r['id']]))
				continue;
			$send .= str_replace('{label}', $r['name'], _clientPoleInfo($r['id'], $v));
		}

		return $send;
	}

	//������ ���������� ��������� � ������� �� ����� (��� ��������/��������������)
	if($i == 'use') {
		if(empty($use[$category_id]))
			return '';

		$send = array();
		foreach($use[$category_id] as $r)
			$send[$r['pole_id']] = $r;
		
		return $send;
	}
	
	//������ ���� �����. ���� ������� ���������, �� �������� ����� ����������� �� ���� ���������
	if($i == 'cp') {
		if($category_id = _num($v['category_id']))
			if(!empty($use[$category_id]))
				foreach($use[$category_id] as $r)
					$cp[$r['pole_id']]['name'] = $r['name'];

		return $cp;
	}

	//�������� ������������� ����������� ���� � ����� ��������� �������
	if($i = 'pole_use') {
		if(!$pole_id)
			return false;

		foreach($cpu as $r)
			if($r['pole_id'] == $pole_id)
				return $r['category_id'];

		return false;
	}

	return $use;
}
function _clientPoleEdit($id, $v=array()) {//������������� ������ HTML ������������ ����� ��� ��������/�������������� �������
	$category_id = _num(@$v['category_id']);
	switch($id) {
		case 1:
			return
			'<tr><td class="label r">{label}'.
				'<td><input type="text" id="ce-name'.$category_id.'" class="w250" value="'.@$v['name'].'" />';

		case 2:
			return
			'<tr><td class="label r">{label}'.
				 '<td><input type="text" id="ce-phone'.$category_id.'" class="w250" value="'.@$v['phone'].'" />';

		case 3:
			return
			'<tr><td class="label r topi">{label}'.
				 '<td><textarea id="ce-adres'.$category_id.'" class="w250">'.@$v['adres'].'</textarea>';

		case 4:
			return
			'<tr><td class="label r">{label}'.
				 '<td><input type="text" id="ce-post'.$category_id.'" class="w250" value="'.@$v['post'].'" />';

		case 5:
			$paspDn = @$v['pasp_seria'] || @$v['pasp_nomer'] || @$v['pasp_adres'] || @$v['pasp_ovd'] || @$v['pasp_data'] ? '' : ' dn';
			return
 ($paspDn ? '<tr><td><td><a class="client-pasp-show" val="'.$category_id.'">��������� ���������� ������</a>' : '').
			'<tr class="client-pasp'.$category_id.$paspDn.'"><td><td><b>���������� ������:</b>'.
			'<tr class="client-pasp'.$category_id.$paspDn.'"><td class="label r">�����:'.
				'<td><input type="text" id="pasp-seria'.$category_id.'" class="w50" value="'.@$v['pasp_seria'].'" />'.
					'<span class="label r">�����:</span>'.
					'<input type="text" id="pasp-nomer'.$category_id.'" class="w100" value="'.@$v['pasp_nomer'].'" />'.
			'<tr class="client-pasp'.$category_id.$paspDn.'">'.
				'<td class="label r">��������:'.
				'<td><input type="text" id="pasp-adres'.$category_id.'" class="w250" value="'.@$v['pasp_adres'].'" />'.
			'<tr class="client-pasp'.$category_id.$paspDn.'">'.
				'<td class="label r">��� �����:'.
				'<td><input type="text" id="pasp-ovd'.$category_id.'" class="w250" value="'.@$v['pasp_ovd'].'" />'.
			'<tr class="client-pasp'.$category_id.$paspDn.'">'.
				'<td class="label r topi">����� �����:'.
				'<td><input type="text" id="pasp-data'.$category_id.'" class="w175 mb20" value="'.@$v['pasp_data'].'" />';

		case 6:
			return
			'<tr><td class="label r">{label}'.
				 '<td><input type="text" id="ce-fax'.$category_id.'" class="w250" value="'.@$v['fax'].'" />';

		case 7:
			return
			'<tr><td class="label r">{label}'.
				 '<td><input type="text" id="ce-email'.$category_id.'" class="w250" value="'.@$v['email'].'" />';

		case 8:
			return
			'<tr><td class="label r">{label}'.
				 '<td><input type="text" id="ce-inn'.$category_id.'" class="w250" value="'.@$v['inn'].'" />';

		case 9:
			return
			'<tr><td class="label r">{label}'.
				 '<td><input type="text" id="ce-kpp'.$category_id.'" class="w250" value="'.@$v['kpp'].'" />';

		case 10:
			return
			'<tr><td class="label r">{label}'.
				'<td><input type="hidden" id="ce-skidka'.$category_id.'" class="ce-skidka" value="'.@$v['skidka'].'" />';

		case 11:
			if(!@$v['client_id'])
				return '';
			return
			'<tr><td class="label r">{label}'.
				'<td><input type="hidden" id="ce-worker_id" value="'.@$v['worker_id'].'" />';

		case 13:
			return
			'<tr><td class="label r">{label}'.
				 '<td><input type="text" id="ce-ogrn'.$category_id.'" class="w250" value="'.@$v['ogrn'].'" />';
	}
	return '';
}
function _clientPoleInfo($id, $v=array()) {//������������� ������ HTML ������������ ����� � ���������� � �������
	switch($id) {
		case 2://�������
			if(empty($v['phone']))
				return '';
			return '<tr><td class="label">{label}:<td>'.$v['phone'];

		case 3://�����
			if(empty($v['adres']))
				return '';
			return '<tr><td class="label top">{label}:<td>'._br($v['adres']);

		case 4://���������
			if(empty($v['post']))
				return '';
			return '<tr><td class="label">{label}:<td>'.$v['post'];

		case 5://���������� ������
			if(!$v['pasp_seria'] && !$v['pasp_nomer'] && !$v['pasp_adres'] && !$v['pasp_ovd'] && !$v['pasp_data'])
				return '';

			return
				'<tr><td class="label top r">����������<br />������:'.
					'<td><table id="pasp-tab">'.
							'<tr><td class="label">�����, �����:<td>'.$v['pasp_seria'].' '.$v['pasp_nomer'].
							'<tr><td class="label">��������:<td>'.$v['pasp_adres'].
							'<tr><td class="label">�����:<td>'.$v['pasp_ovd'].', '.$v['pasp_data'].
						'</table>';

		case 6://����
			if(empty($v['fax']))
				return '';
			return '<tr><td class="label">{label}:<td>'.$v['fax'];

		case 7://email
			if(empty($v['email']))
				return '';
			return '<tr><td class="label wsnw">{label}:<td>'.$v['email'];

		case 8://���
			if(empty($v['inn']))
				return '';
			return '<tr><td class="label">{label}:<td>'.$v['inn'];

		case 9://���
			if(empty($v['kpp']))
				return '';
			return '<tr><td class="label">{label}:<td>'.$v['kpp'];

		case 10://������
			if(!$v['skidka'])
				return '';
			return '<tr><td class="label">{label}:<td><b>'.$v['skidka'].'</b>%';

		case 13://����
			if(empty($v['ogrn']))
				return '';
			return '<tr><td class="label">{label}:<td>'.$v['ogrn'];
	}

	return '';
}



function _clientDolgSum() {//����� ����� ����� ���� �������� � ������� ������ ����
	if(APP_ID != 3978722)// ������ ��� ��������
		return '';

	$sql = "SELECT SUM(`balans`)
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `balans`<0";
	if(!$dolg = abs(query_value($sql)))
		return '';

	return '<div id="client-dolg-sum"><b>'._sumSpace($dolg).'</b> ���.</div>';
}

function _client($v) {
	_clientCategoryOneCheck();
	return client_list($v);
}
function client_list($v) {// �������� �� ������� ��������
	$data = _client_spisok($v);
	$v = $data['filter'];


	//����� ������� ��������
	$sql = "SELECT COUNT(*)
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `balans`<0";
	$dolgShow = query_value($sql);

	//����� ������� ������� ����������
	$sql = "SELECT COUNT(*)
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `balans`>0";
	$oplShow = query_value($sql);

	//����� ������� �������� � ����������
	$sql = "SELECT COUNT(*)
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `worker_id`";
	$workerShow = query_value($sql);

	//����� ������� ������
	$sql = "SELECT
				DISTINCT `skidka` `id`,
				CONCAT(`skidka`,'%') `name`
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `skidka`
			ORDER BY `skidka`";
	$skidka = query_ass($sql);

	return
		'<script>var CLIENT_SKIDKA='._selJson($skidka).';</script>'.
		'<div id="client">'.
			'<table id="find-tab"><tr>'.
				'<td><div id="find"></div>'.
				'<td><button class="vk" onclick="_clientEdit()">����� ������</button>'.
			'</table>'.
			'<div class="result">'.$data['result'].'</div>'.
			'<table class="tabLR">'.
				'<tr><td class="left">'.$data['spisok'].
					'<td class="right">'.
						'<div class="filter'.($v['find'] ? ' dn' : '').'">'.
							_clientCategory('filter', $v['category_id']).
			   ($dolgShow ? _check('dolg', '��������', $v['dolg']) : '').
				($oplShow ? _check('opl', '������� ����������', $v['opl']) : '').
			 ($workerShow ? _check('worker', '�������� � ����������', $v['worker']) : '').
							'<div class="f-label mt20">�����������</div>'.
							'<input type="hidden" id="remind" value="'.$v['remind'].'" />'.

		  (count($skidka) ? '<div class="f-label mt20">������</div>'.
							'<input type="hidden" id="skidka" value="'.$v['skidka'].'" />'
		  : '').
						'</div>'.
			'</table>'.
		'</div>';
}
function clientFilter($v) {
	$default = array(
		'limit' => 20,
		'page' => 1,
		'find' => '',
		'dolg' => 0,
		'worker' => 0,
		'opl' => 0,
		'category_id' => 0,
		'remind' => 0,
		'skidka' => 0
	);
	$filter = array(
		'limit' => _num(@$v['limit']) ? $v['limit'] : 20,
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'find' => strtolower(trim(@$v['find'])),
		'dolg' => _bool(@$v['dolg']),
		'worker' => _bool(@$v['worker']),
		'opl' => _bool(@$v['opl']),
		'category_id' => _num(@$v['category_id']),
		'remind' => _num(@$v['remind']),
		'skidka' => _num(@$v['skidka']),
		'clear' => ''
	);
	foreach($default as $k => $r)
		if($r != $filter[$k]) {
			$filter['clear'] = '<button class="vk small red fr">�������� ������</button>';
			break;
		}
	return $filter;
}
function _client_spisok($v=array()) {// ������ ��������
	$filter = clientFilter($v);
	$filter = _filterJs('CLIENT', $filter);

	$cond = "`app_id`=".APP_ID."
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
		if($filter['worker'])
			$cond .= " AND `worker_id`";
		if($filter['remind']) {
			$not = $filter['remind'] == 2 ? ' NOT' : '';
			$sql = "SELECT `client_id`
					FROM `_remind`
					WHERE `app_id`=".APP_ID."
					 AND `client_id`
					 AND `status`=1";
			$cond .= " AND `id`".$not." IN (".query_ids($sql).")";
		}
		if($filter['dolg']) {
			$cond .= " AND `balans`<0";
			$sql = "SELECT SUM(`balans`)
					FROM `_client`
					WHERE ".$cond."
					  AND `balans`<0";
			$dolg = abs(query_value($sql));
		}
		if($filter['opl']) {
			$cond .= " AND `balans`>0";
			$sql = "SELECT SUM(`balans`)
					FROM `_client`
					WHERE ".$cond."
					  AND `balans`>0";
			$plus = abs(query_value($sql));
		}
		if($filter['skidka'])
			$cond .= " AND `skidka`=".$filter['skidka'];
	}

	$sql = "SELECT COUNT(`id`) AS `all` FROM `_client` WHERE ".$cond;
	if(!$all = query_value($sql))
		return array(
			'all' => 0,
			'result' => '�������� �� �������.'.$filter['clear'],
			'spisok' => $filter['js'].'<div class="_empty">�������� �� �������.</div>',
			'filter' => $filter
		);

	$newMonth = '';
	$newToday = '';
	if(empty($filter['clear'])) {
		//����� ������� �� ������� �����
		$sql = "SELECT COUNT(`id`)
				FROM `_client`
				WHERE ".$cond."
				  AND `dtime_add` LIKE '".strftime('%Y-%m')."-%'";
		$c = query_value($sql);
		$newMonth = $c ? '<b class="'._tooltip('����� �� '._monthDef(strftime('%m')), -5, 'l').'+'.$c.'</b>' : '';

		if($newMonth) {
			//����� ������� �� �������
			$sql = "SELECT COUNT(`id`)
					FROM `_client`
					WHERE ".$cond."
					  AND `dtime_add` LIKE '".TODAY." %'";
			$c = query_value($sql);
			$newToday = $c ? '<span class="'._tooltip('����� �� �������', -10, 'l').'+'.$c.'</span>' : '';
		}
	}

	$send['all'] = $all;
	$send['result'] = '������'._end($all, ' ', '� ').$all.' ������'._end($all, '', '�', '��').
					  ($dolg ? '<em>(����� ����� ����� = <b id="dolg-sum">'._sumSpace($dolg).'</b> ���.)</em>' : '').
					  ($plus ? '<em>(����� = '.$plus.' ���.)</em>' : '').
					  ($newMonth ? '<em>'.$newToday.$newMonth.'</em>' : '').
//					  '<a href="'.URL.'&p=print&d=template&template_id=client-spisok" class="img_xls'._tooltip('������� � Excel', -12, 'l').'</a>'.
					  $filter['clear'];
	$send['filter'] = $filter;
	$send['spisok'] = $filter['js'];

	$spisok = array();
	$sql = "SELECT *,
				   '' `zayav`
			FROM `_client`
			WHERE ".$cond."
			ORDER BY `dtime_add` DESC
			LIMIT "._startLimit($filter);
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		if(FIND) {
			$r['name'] = _findRegular($filter['find'], $r['name']);
			$r['phone'] = _findRegular($filter['find'], $r['phone']);
			$r['adres'] = _findRegular($filter['find'], $r['adres'], 1);

			$r['fax'] = _findRegular($filter['find'], $r['fax'], 1);
			$r['email'] = _findRegular($filter['find'], $r['email'], 1);
			$r['inn'] = _findRegular($filter['find'], $r['inn'], 1);
			$r['kpp'] = _findRegular($filter['find'], $r['kpp'], 1);
			$r['ogrn'] = _findRegular($filter['find'], $r['ogrn'], 1);
		}
		$spisok[$r['id']] = $r;
	}

	$spisok = _zayavCountToClient($spisok);

	foreach($spisok as $r) {
		$cp = _clientPole(array(
			'i' => 'cp',
			'category_id' => $r['category_id']
		));
		$left =
			'<table class="l-tab">'.
				'<tr><td class="label top">'.$cp[1]['name'].':'.
					'<td><a href="'.URL.'&p=client&d=info&id='.$r['id'].'">'.$r['name'].'</a>'.
 ($r['phone'] ? '<tr><td class="label top">'.$cp[2]['name'].':<td>'.$r['phone'] : '').
	   (FIND && $r['adres'] ? '<tr><td class="label top">'.$cp[3]['name'].':<td>'._br($r['adres']) : '').
		 (FIND && $r['fax'] ? '<tr><td class="label">'.$cp[6]['name'].':<td>'.$r['fax'] : '').
	   (FIND && $r['email'] ? '<tr><td class="label">'.$cp[7]['name'].':<td>'.$r['email'] : '').
		 (FIND && $r['inn'] ? '<tr><td class="label">'.$cp[8]['name'].':<td>'.$r['inn'] : '').
		 (FIND && $r['kpp'] ? '<tr><td class="label">'.$cp[9]['name'].':<td>'.$r['kpp'] : '').
		 (FIND && $r['ogrn'] ? '<tr><td class="label">'.$cp[13]['name'].':<td>'.$r['ogrn'] : '').
			'</table>';


		$send['spisok'] .=
			'<div class="unit" id="cu'.$r['id'].'">'.
				'<table class="g-tab">'.
					'<tr><td>'.$left.
						'<td class="r-td">'.
							$r['zayav'].
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
}








function _clientCategory($id=0, $i='name') {//��������� ��������
	$key = CACHE_PREFIX.'client_category';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT *
				FROM `_client_category`
				WHERE `app_id`=".APP_ID."
				ORDER BY `id`";
		$arr = query_arr($sql);
		xcache_set($key, $arr, 86400);
	}

	//��� ���������
	if($id == 'all')
		return $arr;

	//���������� ���������
	if($id == 'count')
		return count($arr);

	//��������� �� ��������� - ������ �� ������
	if($id == 'default')
		return key($arr);

	//��������� �� ��������� - ������ �� ������
	if($id == 'filter') {
		//���� ������� ������� ������ ����� ���������, �� ������� �� �������� �� ����� ����������
		$sql = "SELECT
					DISTINCT `category_id`,
					COUNT(`id`) `c`
				FROM `_client`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				GROUP BY `category_id`";
		$cat = query_ass($sql);
		if(count($cat) < 2)
			return '';

		$category = array(0 => '����� ���������');
		foreach($cat as $cid => $count)
			$category[$cid] = _clientCategory($cid).'<em>'.$count.'</em>';

		return
			'<div class="f-label">���������</div>'.
			_radio('category_id', $category, $i, 1);
	}

	//����������� id
	if(!isset($arr[$id]))
		return _cacheErr('����������� id ��������� �������', $id);
	
	//����������� ����
	if(!isset($arr[$id][$i]))
		return _cacheErr('����������� ���� ��������� �������', $i);

	//���������� �������
	return $arr[$id][$i];

}
function _clientCategoryOneCheck() {//�������� ������� ������� ����� ��������� �������� ��� ����������
	//���� ��������� ���, �� �������� �� ���������: ������� ����
	$sql = "SELECT COUNT(*)
			FROM `_client_category`
			WHERE `app_id`=".APP_ID;
	if(query_value($sql))
		return;

	$sql = "INSERT INTO `_client_category` (
				`app_id`,
				`name`
			) VALUES (
				".APP_ID.",
				'������� ����'
			)";
	query($sql);
}
function _clientVal($client_id, $i=0) {//��������� ������ �� ���� �� ����� �������
	$prefix = 'CLIENT_'.$client_id.'_';
	if(!defined($prefix.'LOADED')) {
		if(!$c = _clientQuery($client_id, 1))
			return 0;

		define($prefix.'LOADED', 1);
		define($prefix.'ID', $c['id']);
		define($prefix.'BALANS', _cena($c['balans'], 1));

		define($prefix.'PASP_SERIA', $c['pasp_seria']);
		define($prefix.'PASP_NOMER', $c['pasp_nomer']);
		define($prefix.'PASP_ADRES', $c['pasp_adres']);
		define($prefix.'PASP_OVD', $c['pasp_ovd']);
		define($prefix.'PASP_DATA', $c['pasp_data']);

		define($prefix.'NAME', $c['name']);
		define($prefix.'PHONE', $c['phone']);
		define($prefix.'ADRES', $c['adres']);
		define($prefix.'WORKER',
			$c['worker_id'] ?
				'<a href="'.URL.'&p=report&d=salary&id='.$c['worker_id'].'" class="'._tooltip('������� �� �������� �/� ����������', -70).
					_viewer($c['worker_id'], 'viewer_name').
				'</a>'
			: '');
		define($prefix.'LINK', '<a href="'.URL.'&p=client&d=info&id='.$client_id.'">'.constant($prefix.'NAME').'</a>');
		define($prefix.'GO',
			'<a val="'.$c['id'].'" class="client-info-go'.($c['deleted'] ? ' deleted' : '').
				(constant($prefix.'PHONE') ? _tooltip(constant($prefix.'PHONE'), -1, 'l') : '">').
				constant($prefix.'NAME').
			'</a>'
		);

		define($prefix.'INN', $c['inn']);
		define($prefix.'KPP', $c['kpp']);
		define($prefix.'SKIDKA', $c['skidka']);
	}

	$send = array(
		'id' => constant($prefix.'ID'),
		'name' => constant($prefix.'NAME'),
		'balans' => constant($prefix.'BALANS'),

		'pasp_seria' => constant($prefix.'PASP_SERIA'),
		'pasp_nomer' => constant($prefix.'PASP_NOMER'),
		'pasp_adres' => constant($prefix.'PASP_ADRES'),
		'pasp_ovd' => constant($prefix.'PASP_OVD'),
		'pasp_data' => constant($prefix.'PASP_DATA'),

		'phone' => constant($prefix.'PHONE'),
		'adres' => constant($prefix.'ADRES'),
		'worker' => constant($prefix.'WORKER'),
		'link' => constant($prefix.'LINK'),
		'go' => constant($prefix.'GO'),

		'inn' => constant($prefix.'INN'),
		'kpp' => constant($prefix.'KPP'),
		'skidka' => constant($prefix.'SKIDKA')
	);

	return $i ? $send[$i] : $send;
}
function _clientValToList($arr, $key='client_id') {//������� ������ �������� � ������ �� client_id
	if(empty($arr))
		return array();

	foreach($arr as $r)
		$arr[$r['id']] += array(
			'client_name' => '',
			'client_phone' => '',
			'client_adres' => '',
			'client_post' => '',
			'client_balans' => 0,
			'client_link' => '',
			'client_go' => ''
		);

	if(!$client_ids = _idsGet($arr, $key))
		return $arr;

	$sql = "SELECT *
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND `id` IN (".$client_ids.")";
	if(!$client = query_arr($sql))
		return $arr;


	foreach($arr as $id => $r) {
		if(!$client_id = _num(@$r[$key]))
			continue;

		$c = $client[$r[$key]];

		$arr[$id] = array(
			'client_name' => $c['name'],
			'client_phone' => $c['phone'],
			'client_adres' => $c['adres'],
			'client_post' => $c['post'],
			'client_balans' => round($c['balans'], 2),
			'client_link' => '<a href="'.URL.'&p=client&d=info&id='.$c['id'].'"'.($c['deleted'] ? ' class="deleted"' : '').'>'.$c['name'].'</a>',
			'client_go' =>
				'<a val="'.$c['id'].'" class="client-info-go'.($c['deleted'] ? ' deleted' : '').
					($c['phone'] ? _tooltip($c['phone'], -1, 'l') : '">').
					$c['name'].
				'</a>'
		) + $arr[$id];
	}
	return $arr;
}
function _findMatch($reg, $v, $empty=0) {//��������� ��� ������� ������ �� ����������� ����������� ���������
	//$empty - ���������� ������ ��������, ���� ��� ����������
	if(empty($reg))
		return $empty ? '': $v;
	$reg = utf8($reg);
	$v = utf8($v);
	$v = preg_match($reg, $v) ? preg_replace($reg, '<em>\\1</em>', $v, 1) : ($empty ? '': $v);
	return win1251($v);
}
function _regFilter($v) {//�������� ����������� ��������� �� ������������ �������
	$reg = '/(\[)/'; // ������ [
	if(preg_match($reg, $v))
		return '';
	return '/('.$v.')/iu';
}
function _findRegular($find, $v, $empty=0, $noSpace=0) {//�������� � ��������� ��� ������� ������ �� ������� � ���������� ������
	$engRus = _engRusChar($find);
	$reg = _regFilter($find);

	$regEngRus = empty($engRus) ? '' : _regFilter($engRus);

	$send = _findMatch($reg, $v, 1);
	if(!$send)
		$send = _findMatch($regEngRus, $v, 1);

	if(!$send && $noSpace)
		if(@preg_match($reg, preg_replace( '/\s+/', '', $v)))
			$send = $v;

	if(!$empty && !$send)
		return $v;

	return $send;
}
function _clientDelAccess($client_id) {//���������� �� �������� �������
	//������� ������
	$sql = "SELECT COUNT(*) FROM `_zayav` WHERE !`deleted` AND `client_id`=".$client_id;
	if(query_value($sql))
		return '�� ������� ������� ������';

	//������� ����������
	$sql = "SELECT COUNT(*) FROM `_money_accrual` WHERE !`deleted` AND `client_id`=".$client_id;
	if(query_value($sql))
		return '� ������� ���� ����������';

	//������� ������ �� ������
	$sql = "SELECT COUNT(*) FROM `_schet` WHERE !`deleted` AND `client_id`=".$client_id;
	if(query_value($sql))
		return '� ������� ���� ����� �� ������';

	//������� ��������
	$sql = "SELECT COUNT(*) FROM `_money_income` WHERE !`deleted` AND `client_id`=".$client_id;
	if(query_value($sql))
		return '� ������� ������������� �������';

	//������� ���������
	$sql = "SELECT COUNT(*) FROM `_money_refund` WHERE !`deleted` AND `client_id`=".$client_id;
	if(query_value($sql))
		return '� ������� ���� ��������';

	//�������� ���������� �����
	$sql = "SELECT COUNT(*) FROM `_client_person` WHERE `client_id`=".$client_id;
	if(query_value($sql))
		return '������ �������� ���������� �����';

	return true;
}

function _clientQuery($client_id, $withDeleted=0) {//������ ������ �� ����� �������
	if(!$client_id)
		return array();

	$sql = "SELECT *
			FROM `_client`
			WHERE `app_id`=".APP_ID.
			  ($withDeleted ? '' : " AND !`deleted`")."
			  AND `id`=".$client_id;
	return query_assoc($sql);
}
function _clientDopLink($name, $arr) {//���� � ��������������� �������� (������, ����������, �������, �������...)
	return
		$arr['all'] ?
			'<a class="link">'.$name.' <b class="count">'.$arr['all'].'</b></a>'
			: '';
}
function _clientDopContent($name, $arr) {//���������� �������������� �������� (����������, �������, �������...)
	return
		$arr['all'] ?
			'<div class="ci-cont" id="'.$name.'-spisok">'.$arr['spisok'].'</div>'
			: '';
}
function _clientDopRight($name, $arr, $filterContent) {//������ ������� (������� ������) ��� �������������� ������� (������, ����������, �������, �������...)
	return
		$arr['all'] ?
			'<div class="ci-right" id="'.$name.'-right">'.$filterContent.'</div>'
			: '';
}
function _clientZayavServiceId($client_id) {//��������� ������� id ���� ������ �������
	$sql = "SELECT DISTINCT `service_id`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			   AND !`deleted`
			  AND `client_id`=".$client_id."
			ORDER BY `service_id`
			LIMIT 1";
	if(!$service_id = query_value($sql))
		return _service('current');

	return $service_id;
}
function _clientInfoZayavCount($client_id) {//����� ���������� ������ ���� ����� � �������
	$sql = "SELECT COUNT(*)
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `client_id`=".$client_id;
	$send['all'] = query_value($sql);
	return $send;
}
function _clientInfoZayavRight($client_id) {
	$sql = "SELECT DISTINCT `service_id`,1
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `client_id`=".$client_id."
			ORDER BY `service_id`";
	$arr = query_ass($sql);

	if(count($arr) < 2)
		return '';

	return
	'<div class="f-label">��������� ������</div>'.
	'<input type="hidden" id="zayav-type-id" value="'.key($arr).'" />';
}
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

	$zayav = _zayav_spisok(array(
		'service_id' => _clientZayavServiceId($client_id),
		'client_id' => $client_id,
		'limit' => 10
	));

	$accrual = _accrual_spisok(array('client_id'=>$client_id));
	$income = income_spisok(array('client_id'=>$client_id));
	$remind = _remind_spisok(array('client_id'=>$client_id));
	$hist = _history(array('client_id'=>$client_id,'limit'=>20));

	$sql = "SELECT `poa_attach_id`
			FROM `_client_person`
			WHERE `person_id`=".$client_id."
			  AND `poa_attach_id`";
	$attach_ids = query_ids($sql);

	return
		'<script>'.
			'var CI={'.
				'id:'.$client_id.','.
				'name:"'._clientVal($client_id, 'name').'",'.
				'workers:'._clientInfoWorker($client_id).','.
				'service_client:'._service('js_client', $client_id).','.//���� ������, ������� ��������� ��� ������� (��� ������� ������)
				'person:'._clientInfoPersonJson($client_id).
/*
				'category_id:'.$c['category_id'].','.

*/
			'};'.
		'</script>'.
		_attachJs(array('id'=>$attach_ids)).
		'<div id="client-info">'.
			'<table class="tabLR">'.
				'<tr><td class="left">'.
						_clientInfoTop($c).
						'<div id="ci-name" class="mar10 b fs14">'.$c['name'].'</div>'.
						'<table class="bs5 ml20">'._clientPole(array('i'=>'info') + $c).'</table>'.
						_clientInfoToPerson($client_id).
						'<div id="ci-person">'._clientInfoPerson($client_id).'</div>'.
						'<div class="added">'.
							'������� '._viewerAdded($c['viewer_id_add']).' '.FullData($c['dtime_add'], 1).
		   ($c['from_id'] ? '<br />��������: <u>'._clientFrom($c['from_id']).'</u>.' : '').
						'</div>'.

					'<td class="right">'.
						'<div class="rightLink">'.
							'<a onclick="_zayavAddMenu()"><b>����� ������</b></a>'.
							'<a class="_remind-add">����� �����������</a>'.
							'<a id="client-schet-add">���� �� ������</a>'.
	   (_app('schet_pay') ? '<a class="b" onclick="schetPayEdit()">����� ���� �� ������</a>' : '').
						'</div>'.
			'</table>'.

			'<div id="dopLinks">'.
				_clientDopLink('������', _clientInfoZayavCount($client_id)).
				_clientDopLink('����������', $accrual).
				_clientDopLink('�������', $income).
				_clientDopLink('�����������', $remind).
				_clientDopLink('�������', $hist).
			'</div>'.

			'<table class="tabLR">'.
				'<tr><td class="left">'.
						_clientDopContent('zayav', $zayav).
						_clientDopContent('accrual', $accrual).
						_clientDopContent('income', $income).
						_clientDopContent('_remind', $remind).
						_clientDopContent('history', $hist).
					'<td class="right">'.
						_clientDopRight('zayav', $zayav, _clientInfoZayavRight($client_id)).
						_clientDopRight('accrual', $accrual, '').
						_clientDopRight('income', $income, '').
						_clientDopRight('remind', $remind, '').
						_clientDopRight('history', $hist, _history_right(array('client_id'=>$client_id))).
				'</div>'.
			'</table>'.

		'</div>';
}
function _clientInfoTop($r) {
	/*  ����������� �������� ���������
		�������� ������� �������
		����� ����������
		����� �����
		������ ��� ������ �������� �����������
		������ ��������������
		������ ��������
	*/

	//����� ����������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_accrual`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `client_id`=".$r['id'];
	$accrual = _cena(query_value($sql));

	//����� �������� ��� ����� ��������� �������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND !`tovar_id`
			  AND `confirm` NOT IN (1,3)
			  AND !`deleted`
			  AND `client_id`=".$r['id'];
	$income = _cena(query_value($sql));

	//����� ���������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_refund`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `client_id`=".$r['id'];
	$refund = _cena(query_value($sql));

	//����� ���������� �� ������� �����
	$sql = "SELECT IFNULL(SUM(`cena`),0)
			FROM `_zayav_gazeta_nomer`
			WHERE `app_id`=".APP_ID."
			  AND `client_id`=".$r['id'];
	$accrual += round(query_value($sql), 2);


	return
	'<table class="w100p collaps">'.
		'<tr><td><div class="grey fs12 mt10 ml10">'.
					_clientCategory($r['category_id']).
				'</div>'.
($accrual ? '<td class="ci-acc w50 center curD wsnw fs12'._tooltip('����� ����� ����������', -55)._sumSpace($accrual) : '').
 ($income || $accrual ? '<td class="ci-pay w50 center curD wsnw fs12'._tooltip('����� ����� ��������', -50)._sumSpace($income) : '').
 ($refund ? '<td class="ci-refund w50 center curD wsnw fs12'._tooltip('����� ����� ���������', -53)._sumSpace($refund) : '').
			'<td'.
			   ' style="color:#'.($r['balans'] < 0 ? 'A00' : '090').'"'.
			   ' class="ci-balans w50 wsnw'._tooltip('������� ������', -26).
					_clientMoneyLink($r).
					'<div class="center b curD fs14">'._sumSpace(_cena($r['balans'], 1)).'</div>'.
			'<td class="w100">'.
				'<div class="mt5 mr5 r">'.
					($r['worker_id'] ? '<a href="'.URL.'&p=report&d=salary&id='.$r['worker_id'].'" class="icon icon-worker center'._tooltip('������ �������� �����������<br />������� �� �������� �/� ����������', -109, '', 1).'</a>' : '').
					'<div onclick="_clientEdit('.$r['category_id'].')" class="icon icon-edit'._tooltip('������������� ������ �������', -94).'</div>'.
					(_clientDelAccess($r['id']) === true ? '<div onclick="clientDel('.$r['id'].')" class="icon icon-del-red'._tooltip('������� �������', -52).'</div>' : '').
				'</div>'.
	'</table>';
}
function _clientMoneyLink($r) {//������ �������� ��������� � ��������
	return
	'<div class="_menuDop3">'.
		'<a class="link black" onclick="_balansShow(2,'.$r['id'].')">�������� �������� ��������</a>'.
		'<a class="link color-acc" onclick="_accrualAdd()">���������</a>'.
		'<a class="link color-pay" onclick="_incomeAdd()">������� �����</a>'.
		'<a class="link color-ref" onclick="_refundAdd()">������� �������</a>'.
	'</div>';
}
function _clientInfoToPerson($client_id) {//��� ���� ���� ������ �������� ���������� �����
	$sql = "SELECT `client_id`
			FROM `_client_person`
			WHERE `person_id`=".$client_id."
			LIMIT 1";
	if(!$person_id = query_value($sql))
		return '';

	$c = _clientVal($person_id);
	return
		'<div id="ci-to-person">'.
			'<b>���������� ����</b> ��� ������� '.$c['link'].'.'.
		'</div>';
}
function _clientInfoPerson($client_id) {//������������ ������ ���������� ���
	//��������, �������� �� ���� ������ ���������� �����
	$sql = "SELECT COUNT(*)
			FROM `_client_person`
			WHERE `person_id`=".$client_id;
	if(query_value($sql))
		return '';

	//��������, ���� �� ���� �� � ����� ��������� �������� ����������� ���������� ���������� ���
	$but = '';
	if(_clientPole(array('i'=>'pole_use','pole_id'=>12)))
		$but = '<button class="vk small" onclick="_clientPersonAdd()">�������� ���������� ����</button>';

	$sql = "SELECT *
			FROM `_client_person`
			WHERE `client_id`=".$client_id."
			ORDER BY `id`";
	if(!$person = query_arr($sql))
		return $but;
	$person = _clientValToList($person, 'person_id');
	$person = _attachValToList($person, 'poa_attach_id');

	$html = '<table class="_spisok mb10">';
	$n = 1;
	foreach($person as $r) {
		$poaLost = _dateLost($r['poa_date_end']) ? ' lost' : '';
		$html .=
			'<tr><td class="n top">'.($n++).
				'<td>'.$r['client_link'].
					  ($r['client_phone'] ? ', '.$r['client_phone'] : '').
					  ($r['client_adres'] ? '<br /><span class="adres"><tt>�����:</tt> '.$r['client_adres'].'</span>' : '').
					  ($r['poa_nomer'] ?
						  '<div class="poa'.$poaLost.'">'.
							  '������������ � <b>'.$r['poa_nomer'].'</b>.'.
							  ($r['poa_attach_id'] ? '<br />'.$r['attach_link'] : '').
							  '<br />������������� �� <u>'.FullData($r['poa_date_end']).'</u>.'.
							  ($poaLost ? '<br /><b>����������.</b>' : '').
						  '</div>'
					  : '').
				'<td>'.($r['client_post'] ? '<u>'.$r['client_post'].'</u>' : '').
				'<td class="ed top">'.
					'<div val="'.$r['id'].'" class="person-poa img_doc'._tooltip('������������� ������������', -91).'</div>'.
					'<div val="'.$r['id'].'" class="person-del img_del'._tooltip('������� ���������� ����', -79).'</div>';
	}
	$html .= '</table>';

	return '<h1>���������� ����:</h1>'.$html.$but;
}
function _clientInfoPersonJson($client_id) {//������ ���������� ��� � ������� JSON
	$sql = "SELECT *
			FROM `_client_person`
			WHERE `client_id`=".$client_id."
			ORDER BY `id`";
	if(!$person = query_arr($sql))
		return '{}';
	$person = _clientValToList($person, 'person_id');
	$person = _attachValToList($person, 'poa_attach_id');

	$json = array();
	foreach($person as $r) {
		$json[] =
			$r['id'].':{'.
				'name:"'.addslashes($r['client_name']).'",'.
				'phone:"'.addslashes($r['client_phone']).'",'.
				'adres:"'.addslashes($r['client_adres']).'",'.
				'post:"'.addslashes($r['client_post']).'",'.
//				'pasp_seria:"'.addslashes($r['pasp_seria']).'",'.
//				'pasp_nomer:"'.addslashes($r['pasp_nomer']).'",'.
//				'pasp_adres:"'.addslashes($r['pasp_adres']).'",'.
//				'pasp_ovd:"'.addslashes($r['pasp_ovd']).'",'.
//				'pasp_data:"'.addslashes($r['pasp_data']).'",'.
				'poa_nomer:"'.addslashes($r['poa_nomer']).'",'.
				'poa_date_begin:"'.addslashes($r['poa_date_begin']).'",'.
				'poa_date_end:"'.addslashes($r['poa_date_end']).'",'.
				'poa_attach_id:'.$r['poa_attach_id'].
			'}';
	}

	return '{'.implode(',', $json).'}';
}
function _clientInfoPersonArr($client_id) {//������ ���������� ��� � ������� Array
	$sql = "SELECT *
			FROM `_client_person`
			WHERE `client_id`=".$client_id."
			ORDER BY `id`";
	if(!$person = query_arr($sql))
		return array();
	$person = _clientValToList($person, 'person_id');
	$person = _attachValToList($person, 'poa_attach_id');

	$array = array();
	foreach($person as $r) {
		$array[$r['id']] = array(
			'name' => utf8($r['client_name']),
			'phone' => utf8($r['client_phone']),
			'adres' => utf8($r['client_adres']),
			'post' => utf8($r['client_post']),
//			'pasp_seria' => utf8($r['pasp_seria']),
//			'pasp_nomer' => utf8($r['pasp_nomer']),
//			'pasp_adres' => utf8($r['pasp_adres']),
//			'pasp_ovd' => utf8($r['pasp_ovd']),
//			'pasp_data' => utf8($r['pasp_data']),
			'poa_nomer' => utf8($r['poa_nomer']),
			'poa_date_begin' => $r['poa_date_begin'],
			'poa_date_end' => $r['poa_date_end'],
			'poa_attach_id' => $r['poa_attach_id']
		);
	}

	return $array;
}


function _clientInfoWorker($client_id) {//������ ����������� ��� ������ � ���������
	//id �����������, ������� ��� ��������� � ��������
	$sql = "SELECT `worker_id`
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`
			  AND !`deleted`
			  AND `id`!=".$client_id;
	$ids = query_ids($sql);

	$sql = "SELECT `viewer_id`
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `worker`
			  AND `hidden`";
	$hidden = query_ids($sql);

	$sql = "SELECT
				`viewer_id`,
				CONCAT(`first_name`,' ',`last_name`)
	        FROM `_vkuser`
	        WHERE `app_id`=".APP_ID."
	          AND `worker`
	          AND `viewer_id` NOT IN (".$ids.",".$hidden.")";
	return query_selJson($sql);
}

function _clientBalansUpdate($client_id) {//���������� ������� �������
	if(empty($client_id))
		return 0;

	//����������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_accrual`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `client_id`=".$client_id;
	$accrual = query_value($sql);

	//������� ��� ����� ��������� ������� �������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND !`tovar_id`
			  AND `confirm` NOT IN (1,3)
			  AND !`deleted`
			  AND `client_id`=".$client_id;
	$income = query_value($sql);

	//��������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_refund`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `client_id`=".$client_id;
	$refund = query_value($sql);

	//������ �����
	$sql = "SELECT IFNULL(SUM(`cena`),0)
			FROM `_zayav_gazeta_nomer`
			WHERE `app_id`=".APP_ID."
			  AND `client_id`=".$client_id;
	$gn = query_value($sql);

	$balans = $income - $accrual - $refund - $gn;

	$sql = "UPDATE `_client`
			SET `balans`=".$balans."
			WHERE `id`=".$client_id;
	query($sql);

	return $balans;
}


function _clientPoaFilter($v) {
	return array(
		'limit' => _num(@$v['limit']) ? $v['limit'] : 30,
		'page' => _num(@$v['page']) ? $v['page'] : 1,
	);
}
function _clientPoa() {
	$data = _clientPoaSpisok();
	return
	'<div id="client-poa">'.
		'<table class="tabLR">'.
			'<tr><td class="left">'.
					'<div class="headName">������������ �� �����������</div>'.
					$data['spisok'].
				'<td class="right">'.
		'</table>'.
	'</div>';
}
function _clientPoaSpisok($v=array()) {
	$filter = clientFilter($v);
	$filter = _filterJs('CLIENT_POA', $filter);

	$cond = "`app_id`=".APP_ID."
		 AND `poa_nomer`";

	$sql = "SELECT COUNT(`id`) FROM `_client_person` WHERE ".$cond;
	if(!$all = query_value($sql))
		return array(
			'all' => 0,
			'result' => '������������� ���.',
			'spisok' => $filter['js'].'<div class="_empty">������������� ���.</div>',
			'filter' => $filter
		);

	$send['all'] = $all;
	$send['result'] = '������'._end($all, ' ', '� ').$all.' �����������'._end($all, '�', '�', '��');
	$send['filter'] = $filter;
	$send['spisok'] = $filter['js'];

	$sql = "SELECT *
			FROM `_client_person`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT "._startLimit($filter);
	$spisok = query_arr($sql);

	$spisok = _clientValToList($spisok);
	$spisok = _attachValToList($spisok, 'poa_attach_id');
	$person = _clientValToList($spisok, 'person_id');

	foreach($spisok as $r) {
		$send['spisok'] .=
			'<div class="client-poa-unit'.(_dateLost($r['poa_date_end']) ? ' lost' : '').'">'.
				'<b class="name">������������ � '.$r['poa_nomer'].'</b>'.
				'<table class="tab">'.
					'<tr><td class="label">�����������:<td>'.$r['client_link'].
					'<tr><td class="label">���������� ����:<td class="fio">'.$person[$r['id']]['client_link'].
					'<tr><td class="label">���� ������:<td>'.FullData($r['poa_date_begin'], 1).
					'<tr><td class="label">���� ���������:<td>'.FullData($r['poa_date_end'], 1).
					($r['poa_attach_id'] ? '<tr><td class="label">����:<td>'.$r['attach_link'] : '').
				'</table>'.
			'</div>';
	}

	$send['spisok'] .= _next($filter + array(
		'all' => $all
	));

	return $send;
}


/* ---=== ������ ������ ������ ===--- */
function _clientFromJs() {//������ ����������, �� ������� �������� ������
	$sql = "SELECT `id`,`name`
			FROM `_client_from`
			WHERE `app_id`=".APP_ID."
			ORDER BY `name`";
	return query_selJson($sql);
}
function _clientFrom($id) {
	$key = CACHE_PREFIX.'client_from';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					`id`,
					`name`
				FROM `_client_from`
				WHERE `app_id`=".APP_ID."
				ORDER BY `name`";
		$arr = query_ass($sql);
		xcache_set($key, $arr, 86400);
	}

	if(!isset($arr[$id]))
		_cacheErr('����������� id ��������� �������', $id);

	return $arr[$id];
}
function _client_from() {//����� �������� ������ ������ ������
	return
	'<div id="client-from">'.
		'<div class="headName">'.
			'���������, �� ������� �������� �������'.
			'<a class="add">�������� ����� ��������</a>'.
		'</div>'.
		_client_from_setup().
		'<div id="spisok">'._client_from_spisok().'</div>'.
	'</div>';
}
function _client_from_spisok() {//������ ����������
	$sql = "SELECT
				*,
				0 `count`
			FROM `_client_from`
			WHERE `app_id`=".APP_ID."
			ORDER BY `name`";
	if(!$spisok = query_arr($sql))
		return '��������� �� ����������.';

	$sql = "SELECT
				`from_id`,
				COUNT(`id`) `count`
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND `from_id`
			GROUP BY `from_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['from_id']]['count'] = $r['count'];

	$send = '<table class="_spisok">'.
				'<tr><th>�������� ���������'.
					'<th>���-�� ��������<br />�� �� �����'.
					'<th>';
	foreach($spisok as $id => $r)
		$send .= '<tr val="'.$id.'">'.
					'<td class="name">'.$r['name'].
					'<td class="count center">'.($r['count'] ? $r['count'] : '').
					'<td class="ed">'.
						_iconEdit($r).
						_iconDel($r + array('nodel'=>$r['count']));
	$send .= '</table>';

	return $send;
}
function _client_from_setup() {//��������� ������������� ���������� ��������
	if(!VIEWER_ADMIN)
		return '';

	return
	'<table id="cf-setup" class="bs10">'.
		'<tr><td class="label r w150">�������� �������������:<td>'._check('client_from_use', '', _app('client_from_use')).
		'<tr class="tr-require'.(_app('client_from_use') ? '' : ' dn').'">'.
			'<td class="label r">��������� ����������� ��������� �������� ��� �������� ������ �������:'.
			'<td>'._check('client_from_require', '', _app('client_from_require')).
		'<tr class="tr-submit dn"><td><td><button class="vk setup-submit">��������� ���������</button>'.
	'</table>';
}

