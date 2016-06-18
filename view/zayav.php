<?php
function _zayav() {
	if(function_exists('zayavCase'))
		return zayavCase();
	if(@$_GET['d'] == 'info')
		return _zayav_info();
	return _zayav_list(_hashFilter('zayav'._service('current')));
}

function _zayavStatus($id=false, $i='name') {//����������� �������� ������
	$key = CACHE_PREFIX.'zayav_status'.APP_ID;
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					`id`,
					`name`,
					`color`,
					`default`,
					`executer`,
					`hide`,
					`srok`,
					`day_fact`,
					0 `count`
				FROM `_zayav_status`
				WHERE `app_id`=".APP_ID."
				ORDER BY `sort`";
		$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT);
		xcache_set($key, $arr, 86400);
	}

	//������ ��� ������ ������
	if($i == 'filter') {
		$sql = "SELECT
					`status_id`,
					COUNT(*) `count`
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND `service_id`="._service('current')."
				  AND `status_id`
				  AND !`deleted`
				GROUP BY `status_id`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		while($r = mysql_fetch_assoc($q))
			$arr[$r['status_id']]['count'] = $r['count'];

		$filter = '';
		foreach($arr as $r) {
			if(!$r['count'])
				continue;
			$filter .=
				'<tr style="background-color:#'.$r['color'].'">'.
					'<td val="'.$r['id'].'">'.
						$r['name'].
						'<em>'.$r['count'].'</em>';
		}
		return
			'<div id="zayav-status-filter"'.($id ? ' class="us"' : '').'">'.
				'<div id="any">����� ������</div>'.
				'<div id="sel"'.($id ? ' style="background:#'.$arr[$id]['color'].'"' : '').'>'.($id ? $arr[$id]['name'] : '').'</div>'.
				'<div id="status-tab">'.
					'<table>'.
						'<tr><td val="0"><b>����� ������</b>'.
						$filter.
					'</table>'.
				'</div>'.
			'</div>';
	}

	if($id == 'all')
		return $arr;

	if($id == 'select') {
		$send = array();
		foreach($arr as $r)
			$send[$r['id']] = $r['name'];
		return _selJson($send);
	}

	//����������� ������� �� ���������
	if($id == 'default')
		foreach($arr as $r)
			if($r['default'])
				return _num($r['id']);

	//id ��������, ������� ���������� �� ������ ������
	if($id == 'hide_ids') {
		$ids = array();
		foreach($arr as $r)
			if($r['hide'])
				$ids[] = $r['id'];
		if(empty($ids))
			return 0;
		return implode(',', $ids);
	}

	//id ��������, � ������� ����� ��������� ���� ����������
	if($id == 'srok_ids') {
		$ids = array();
		foreach($arr as $r)
			if($r['srok'])
				$ids[] = $r['id'];
		if(empty($ids))
			return 0;
		return implode(',', $ids);
	}

	if($id && !isset($arr[$id])) {
		if($i == 'bg')
			return '';
		return '<span class="red">����������� id �������: <b>'.$id.'</b></span>';
	}

	if(!$id) {
		if($i == 'bg')
			return '';
		return '<span class="red">������ �����������</span>';
	}

	if($i == 'name')
		return $arr[$id]['name'];

	if($i == 'color') {
		$c = $arr[$id]['color'];
		if(strlen($c) != 6)
			return $c[0].$c[0].$c[1].$c[1].$c[2].$c[2];
		return $c;
	}

	if($i == 'executer')
		return _bool($arr[$id]['executer']);

	if($i == 'srok')
		return _bool($arr[$id]['srok']);

	if($i == 'day_fact')
		return _bool($arr[$id]['day_fact']);

	if($i == 'bg')
		return ' style="background-color:#'.$arr[$id]['color'].'"';

	return '<span class="red">����������� ���� �������: <b>'.$i.'</b></span>';
}
function _zayavStatusButton($z) {
	if($z['status_day'] == '0000-00-00')
		$z['status_day'] = $z['status_dtime'];

	return
		'<div id="zayav-status-button"'._zayavStatus($z['status_id'], 'bg').'>'.
			'<b class="hd">'._zayavStatus($z['status_id']).'</b> '.
			(_zayavStatus($z['status_id'], 'day_fact') ? FullData($z['status_day'], 1) : '').
		'</div>';
}
function _zayavValToList($arr) {//������� ������ ������ � ������ �� zayav_id
	$ids = array();
	$arrIds = array();
	foreach($arr as $key => $r)
		if(!empty($r['zayav_id'])) {
			$ids[$r['zayav_id']] = 1;
			$arrIds[$r['zayav_id']][] = $key;
		}
	if(empty($ids))
		return $arr;

	$sql = "SELECT *
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `id` IN (".implode(',', array_keys($ids)).")";
	$zayav = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	if(!isset($r['client_phone'])) {
		foreach($zayav as $r)
			foreach($arrIds[$r['id']] as $id)
				$arr[$id]['client_id'] = $r['client_id'];
		$arr = _clientValToList($arr);
	}

	foreach($zayav as $r) {
		foreach($arrIds[$r['id']] as $id) {
			$dolg = $r['sum_accrual'] - $r['sum_pay'];
			$dolg = $dolg > 0 ? $dolg : 0;
			$arr[$id] += array(
				'zayav_name' => $r['name'],
				'zayav_n' => $r['nomer'],
				'zayav_link' =>
					'<a href="'.URL.'&p=zayav&d=info&id='.$r['id'].'" class="zayav_link">'.
						'<span'.($r['deleted'] ? ' class="deleted"' : '').'>�'.$r['nomer'].'</span>'.
						'<div class="tooltip">'._zayavTooltip($r, $arr[$id]).'</div>'.
					'</a>',
				'zayav_link_name' =>
					'<a href="'.URL.'&p=zayav&d=info&id='.$r['id'].'">'.
						'<b'.($r['deleted'] ? ' class="deleted"' : '').'>'.$r['name'].'</b>'.
					'</a>',
				'zayav_color' => //��������� ������ �� ��������� �������
					'<a href="'.URL.'&p=zayav&d=info&id='.$r['id'].'" class="zayav_link color"'._zayavStatus($r['status_id'], 'bg').'>'.
						'�'.$r['nomer'].
						'<div class="tooltip">'._zayavTooltip($r, $arr[$id]).'</div>'.
					'</a>',
				'zayav_dolg_sum' => $dolg,
				'zayav_dolg' => $dolg ? '<span class="zayav-dolg'._tooltip('���� �� ������', -45).$dolg.'</span>' : '',
				'zayav_status_day' => $r['status_day'],
				'zayav_adres' => $r['adres'],
				'dogovor_id' => $r['dogovor_id']
			);
		}
	}

	return $arr;
}
function _zayavTooltip($z, $v) {
	return $html =
		'<table>'.
			'<tr><td>'.
				'<td class="inf">'.
					'<div'._zayavStatus($z['status_id'], 'bg').
						' class="tstat'._tooltip('������ ������: '._zayavStatus($z['status_id']), -7, 'l').
					'</div>'.
					'<b>'.$z['name'].'</b>'.
	($z['client_id'] ?
			'<table>'.
				'<tr><td class="label top">������:'.
					'<td>'.$v['client_name'].
						($v['client_phone'] ? '<br />'.$v['client_phone'] : '').
				'<tr><td class="label">������:'.
					'<td><span class="bl" style=color:#'.($v['client_balans'] < 0 ? 'A00' : '090').'>'.$v['client_balans'].'</span>'.
			'</table>'
	: '').
		'</table>';
}
function _zayavCountToClient($spisok) {//������������ ����������� � ����������� ������ � ������ ��������
	//��������� �������� ������, ������� ���� � ������� ��������
	$sql = "SELECT DISTINCT `status_id` `id`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `status_id`
			  AND `client_id` IN ("._keys($spisok).")";
	if(!$status_ids = query_ids($sql, GLOBAL_MYSQL_CONNECT))
		return $spisok;

	//���������� ������� �� �������� ���������� ������, ������� ������������� ������� �������
	foreach(_ids($status_ids, 1) as $id) {
		$sql = "SELECT
					`id`,
					`client_id`,
					COUNT(`id`) AS `count`
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND `status_id`=".$id."
				  AND !`deleted`
				  AND `client_id` IN ("._keys($spisok).")
				GROUP BY `client_id`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		while($r = mysql_fetch_assoc($q)) {
			$link = $r['count'] == 1 ? ' link' : '';
			$href = $r['count'] == 1 ? ' href="'.URL.'&p=zayav&d=info&id='.$r['id'].'"' : '';
			$spisok[$r['client_id']]['zayav'] .=
				'<a'.$href._zayavStatus($id, 'bg').' class="z-count'.$link._tooltip(_zayavStatus($id), -8, 'l').
					$r['count'].
				'</a>';
		}
	}

	return $spisok;
}
function _zayavExecuterToList($zayav) {//������������ ������������ � ������ ������
	if(empty($zayav))
		return array();

	$ids = _idsGet($zayav);

	foreach($zayav as $r)
		$zayav[$r['id']]['executer_spisok'] = array();

	$sql = "SELECT *
			FROM `_zayav_expense`
			WHERE `zayav_id` IN (".$ids.")
			  AND `worker_id`
			ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q)) {
		$zayav[$r['zayav_id']]['executer_spisok'][] = _viewer($r['worker_id'], 'viewer_link_zp');
	}

	foreach($zayav as $r)
		$zayav[$r['id']]['executer_spisok'] = implode('<br />',$r['executer_spisok']);


	return $zayav;
}

function _zayavFilter($v) {
	$default = array(
		'page' => 1,
		'limit' => 20,
		'client_id' => 0,
		'find' => '',
		'sort' => 1,
		'desc' => 0,
		'status' => 0,
		'finish' => '0000-00-00',
		'executer_id' => 0,
		'zpzakaz' => 0,
		'tovar_place_id' => 0,
		'paytype' => 0,
		'noschet' => 0,
		'nofile' => 0,
		'tovar_name_id' => 0,
		'deleted' => 0,
		'deleted_only' => 0
	);
	$filter = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 20,
		'client_id' => _num(@$v['client_id']),
		'find' => trim(@$v['find']),
		'sort' => _num(@$v['sort']) ? _num(@$v['sort']) : 1,
		'desc' => _bool(@$v['desc']),
		'status' => _num(@$v['status']),
		'finish' => preg_match(REGEXP_DATE, @$v['finish']) ? $v['finish'] : $default['finish'],
		'executer_id' => intval(@$v['executer_id']),
		'zpzakaz' => _num(@$v['zpzakaz']),
		'tovar_place_id' => _num(@$v['tovar_place_id']),
		'paytype' => _num(@$v['paytype']),
		'noschet' => _bool(@$v['noschet']),
		'nofile' => _bool(@$v['nofile']),
		'tovar_name_id' => _num(@$v['tovar_name_id']),
		'deleted' => _bool(@$v['deleted']),
		'deleted_only' => _bool(@$v['deleted_only']),
		'clear' => ''
	);
	foreach($default as $k => $r)
		if($r != $filter[$k]) {
			$filter['clear'] = '<a class="clear">�������� ������</a>';
			break;
		}

	$filter['service_id'] = _service('current', _num(@$v['service_id']));

	return $filter;
}
function _zayav_list($v=array()) {
	$data = _zayav_spisok($v);
	$v = $data['filter'];

	return
	'<div id="_zayav">'.
		_service('menu').
		'<div class="result">'.$data['result'].'</div>'.
		'<table class="tabLR">'.
			'<tr><td id="spisok">'.$data['spisok'].
				'<td class="right">'.
					'<button class="vk fw" onclick="_zayavEdit('.$v['service_id'].')">����� ������</button>'.
					_zayavPoleFilter($v).
	(VIEWER_ADMIN ? _check('deleted', '+ �������� ������', $v['deleted'], 1).
					'<div id="deleted-only-div"'.($v['deleted'] ? '' : ' class="dn"').'>'.
						_check('deleted_only', '������ ��������', $v['deleted_only'], 1).
					'</div>'
	: '').
		'</table>'.
	'</div>';
}
function _zayav_spisok($v) {
	$filter = _zayavFilter($v);
	$filter = _filterJs('ZAYAV', $filter);

	$cond = "`app_id`=".APP_ID."
		 AND `service_id`=".$filter['service_id'];
	if(!VIEWER_ADMIN)
		$cond .= " AND !`deleted`";

	define('SROK', $filter['finish'] != '0000-00-00');

	$nomer = 0;

	$FIND = !empty($filter['find']);

	if($FIND) {
		$engRus = _engRusChar($filter['find']);
		$cond .= " AND (`find` LIKE '%".$filter['find']."%'".
			($engRus ? " OR `find` LIKE '%".$engRus."%'" : '').")";
		if($filter['page'] == 1)
			$nomer = _num($filter['find']);
	} else {
		if($filter['client_id'])
			$cond .= " AND `client_id`=".$filter['client_id'];
		if($filter['status'])
			$cond .= " AND `status_id`=".$filter['status'];
		if(SROK)
			$cond .= " AND `srok`='".$filter['finish']."' AND `status_id` IN ("._zayavStatus('srok_ids').")";
		if($filter['paytype'])
			$cond .= " AND `pay_type`=".$filter['paytype'];
		if($filter['noschet'])
			$cond .= " AND !`schet_count`";
		if($filter['executer_id'])
			$cond .= " AND `executer_id`=".($filter['executer_id'] < 0 ? 0 : $filter['executer_id']);
		if($filter['nofile']) {
			//��������� id �������� �� ������ �� ��������, � ������� ������������� ����
			$sql = "SELECT `id`
					FROM `_zayav_expense_category`
					WHERE `app_id`=".APP_ID."
					  AND `dop`=4";
			if($zeIds = query_ids($sql, GLOBAL_MYSQL_CONNECT)) {
				//id ������, � ������� ����������� �����
				$sql = "SELECT DISTINCT(`zayav_id`)
						FROM `_zayav_expense`
						WHERE `app_id`=".APP_ID."
						  AND `category_id` IN (".$zeIds.")";
				$zayav_ids = query_ids($sql, GLOBAL_MYSQL_CONNECT);

				//��������
				$cond .= " AND `id` NOT IN (".$zayav_ids.")";
			}
		}

		if($filter['tovar_name_id']) {
			$sql = "SELECT DISTINCT `zayav_id`
					FROM `_zayav_tovar` `zt`,
						`_tovar` `t`
					WHERE `zt`.`app_id`=".APP_ID."
					  AND `t`.`id`=`zt`.`tovar_id`
					  AND `t`.`name_id`=".$filter['tovar_name_id'];
			$zayav_ids = query_ids($sql, GLOBAL_MYSQL_CONNECT);
			$cond .= " AND `id` IN (".$zayav_ids.")";
		}

		if($filter['tovar_place_id'])
			$cond .= " AND `tovar_place_id`=".$filter['tovar_place_id'];


		if(VIEWER_ADMIN) {
			if($filter['deleted']) {
				if($filter['deleted_only'])
					$cond .= " AND `deleted`";
			} else
				$cond .= " AND !`deleted`";
		}

		if(!SROK && _zayavStatus('hide_ids') && !$filter['client_id'] && !$filter['status'])
			$cond .= " AND `status_id` NOT IN ("._zayavStatus('hide_ids').")";
	}

	$sql = "SELECT
				COUNT(*) `all`,
				SUM(`count`) `count`
			FROM `_zayav`
			WHERE ".$cond;
	$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT);
	$all = $r['all'];
	$count = $r['count'];

	$zayav = array();

	//��������� ������ ������ � ������ �������� ��� ������� ������
	$dogNomerId = 0;//id ������, ������� ����� ���������� ��� ������ ��������
	if($nomer) {
		$sql = "SELECT *
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `status_id`
				  AND `nomer`=".$nomer."
				LIMIT 1";
		if($r = query_assoc($sql, GLOBAL_MYSQL_CONNECT)) {
			$all++;
			$filter['limit']--;
			$r['nomer'] = '<em>'.$r['nomer'].'</em>';
			$r['product'] = '';
			$r['note'] = '';
			$r['schet'] = '';
			$r['sum_accrual'] = round($r['sum_accrual']);
			$r['sum_pay'] = round($r['sum_pay']);
			$zayav[$r['id']] = $r;
		}

		$sql = "SELECT `zayav_id`
				FROM `_zayav_dogovor`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `nomer`=".$nomer."
				LIMIT 1";
		if($id = query_value($sql, GLOBAL_MYSQL_CONNECT)) {
			$dogNomerId = $id;
			$sql = "SELECT *
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `id`=".$id;
			if($r = query_assoc($sql, GLOBAL_MYSQL_CONNECT)) {
				$all++;
				$filter['limit']--;
				$r['dogovor_nomer'] = '<em>'.$r['nomer'].'</em>';
				$r['product'] = '';
				$r['note'] = '';
				$r['schet'] = '';
				$r['sum_accrual'] = round($r['sum_accrual']);
				$r['sum_pay'] = round($r['sum_pay']);
				$zayav[$r['id']] = $r;
			}
		}
	}

	if(!$all)
		return array(
			'all' => 0,
			'result' => '������ �� �������'.$filter['clear'],
			'spisok' => $filter['js'].'<div class="_empty">������ �� �������</div>',
			'filter' => $filter
		);

	$send = array(
		'all' => $all,
		'result' => '�������'._end($all, '�', '�').' '.$all.' ����'._end($all, '��', '��', '��').
					($count ? '<span id="z-count">('.$count.' ��.)</span>' : '').
					$filter['clear'],
		'spisok' => $filter['js'],
		'filter' => $filter
	);

	$sql = "SELECT
	            *,
	            '' `product`,
	            '' `note`,
				'' `schet`
			FROM `_zayav`
			WHERE ".$cond."
			ORDER BY `".($filter['sort'] == 2 ? 'status_dtime' : 'dtime_add')."` ".($filter['desc'] ? 'ASC' : 'DESC')."
			LIMIT "._startLimit($filter);
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q)) {
		if($nomer == $r['nomer'])
			continue;
		if($dogNomerId == $r['id'])
			continue;
		$r['sum_accrual'] = round($r['sum_accrual']);
		$r['sum_pay'] = round($r['sum_pay']);
		$r['name'] = $FIND ? _findRegular($filter['find'], $r['name']) : $r['name'];

		$zayav[$r['id']] = $r;
	}

	if(!$filter['client_id'])
		$zayav = _clientValToList($zayav);

	$zayav = _dogovorValToList($zayav);
	$zayav = _schetToZayav($zayav);
	$zayav = _zayavNote($zayav);
//	if(ZAYAV_INFO_DEVICE)
//		$zayav = _imageValToZayav($zayav);

/*
	//��������
	$sql = "SELECT `zayav_id`,`zp_id` FROM `zp_zakaz` WHERE `zayav_id` IN (".$zayavIds.")";
	$q = query($sql);
	$zp = array();
	$zpZakaz = array();
	while($r = mysql_fetch_assoc($q)) {
		$zp[$r['zp_id']] = $r['zp_id'];
		$zpZakaz[$r['zayav_id']][] = $r['zp_id'];
	}
	if(!empty($zp)) {
		$sql = "SELECT `id`,`name_id` FROM `zp_catalog` WHERE `id` IN (".implode(',', $zp).")";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q))
			$zp[$r['id']] = $r['name_id'];
		foreach($zpZakaz as $id => $zz)
			foreach($zz as $i => $zpId)
				$zpZakaz[$id][$i] = _zpName($zp[$zpId]);
	}
//	(isset($zpZakaz[$id]) ? '<tr><td class="label">�������� �/�:<td class="zz">'.implode(', ', $zpZakaz[$id]) : '').
//					'<td class="image">'.$img.
 			  ($r['imei'] ? '<tr><td class="label">IMEI:<td>'.$r['imei'] : '').
		    ($r['serial'] ? '<tr><td class="label">�������� �����:<td>'.$r['serial'] : '').
		$img = $images['zayav'.$id]['id'] ? $images['zayav'.$id]['img'] : $images['dev'.$r['base_model_id']]['img'];
		if($filter['find']) {
			if(preg_match($reg, $r['model']))
				$r['model'] = preg_replace($reg, "<em>\\1</em>", $r['model'], 1);
			if($regEngRus && preg_match($regEngRus, $r['model']))
				$r['model'] = preg_replace($regEngRus, '<em>\\1</em>', $r['model'], 1);
			$r['imei'] = preg_match($reg, $r['imei']) ? preg_replace($reg, "<em>\\1</em>", $r['imei'], 1) : '';
			$r['serial'] = preg_match($reg, $r['serial']) ? preg_replace($reg, "<em>\\1</em>", $r['serial'], 1) : '';
		} else {
			$r['imei'] = '';
			$r['serial'] = '';
		}

*/


	foreach($zayav as $id => $r) {
		$diff = $r['sum_dolg'] ? ($r['sum_dolg'] < 0 ? '����' : '����').'����� '.abs($r['sum_dolg']).' ���.' : '��������';
		$deleted = $r['deleted'] ? ' deleted' : '';
		$statusColor = $r['deleted'] ? '' : _zayavStatus($r['status_id'], 'bg');

		//��������� ������ �������� ��� ������� ������
		if($FIND && $r['dogovor_id'])
			$r['dogovor_line'] = _findRegular($filter['find'], $r['dogovor_line']);

		$send['spisok'] .=
			'<div class="_zayav-unit'.$deleted.'" id="u'.$id.'"'.$statusColor.' val="'.$r['id'].'">'.
				'<table class="zu-main">'.
					'<tr><td class="zu-td1">'.

				'<div class="zd">'.
					'#'.$r['nomer'].
					'<div class="date-add">'.FullData($r['dtime_add'], 1).'</div>'.
//($r['status_id'] == 2 ? '<div class="date-ready'._tooltip('���� ����������', -40).FullData($r['status_dtime'], 1, 1).'</div>' : '').
					($r['sum_accrual'] || $r['sum_pay'] ?
						'<div class="balans'.($r['sum_accrual'] != $r['sum_pay'] ? ' diff' : '').'">'.
							'<span class="acc'._tooltip('���������', -39).$r['sum_accrual'].'</span>/'.
							'<span class="pay'._tooltip($diff, -17, 'l').$r['sum_pay'].'</span>'.
						'</div>'
					: '').
				'</div>'.

				'<a class="name"><b>'.$r['name'].'</b></a>'.
				'<table class="tab">'.
			 ($r['count'] ? '<tr><td class="label">����������:<td><b>'.$r['count'].'</b> ��.' : '').
		($r['dogovor_id'] ? '<tr><td class="label top">�������:<td class="dog">'.$r['dogovor_line'] : '').
		   ($r['product'] ? '<tr><td class="label top">�������:<td>'.$r['product'] : '').
   (!$filter['client_id'] && $r['client_id'] ? '<tr><td class="label">������:<td>'.$r['client_go'] : '').
			 ($r['adres'] ? '<tr><td class="label top">�����:<td>'.$r['adres'] : '').
	         ($r['schet'] ? '<tr><td class="label topi">�����:<td>'.$r['schet'] : '').
				'</table>'.
				'<div class="note">'.$r['note'].'</div>'.
				'<div class="status"'.($r['status_id'] ? ' style="color:#'._zayavStatus($r['status_id'], 'color').'"' : '').'>'._zayavStatus($r['status_id']).'</div>'.

//			(ZAYAV_INFO_DEVICE ?
//					'<td class="image"'.$statusColor.'>'.
//						'<span>'.$r['image_small'].'</span>'
//			: '').
				'</table>'.
			'</div>';
	}

	 $send['spisok'] .= _next($filter + array(
			'type' => 2,
			'all' => $all
		));
	return $send;
}
function _zayavNote($arr) {//������������ ������� ��� ������������ � ������ ������
	$ids = implode(',', array_keys($arr));

	$zn = array(); //����������: id ������� -> id ������

	//������������ �������
	$sql = "SELECT
				`id`,
				`page_id`,
				`txt`
			FROM `_note`
			WHERE `page_name`='zayav'
			  AND `page_id` IN (".$ids.")
			  AND !`deleted`
			ORDER BY `page_id`,`id` DESC";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$zayav_id = 0; //����� ������ ������� ������� � ������
	while($r = mysql_fetch_assoc($q)) {
		if($zayav_id != $r['page_id']) {
			$zayav_id = $r['page_id'];
			$arr[$r['page_id']]['note'] = $r['txt'];
			$zn[$r['id']] = $r['page_id'];
		}
	}

	if(empty($zn))
		return $arr;

	//������������ ������������
	$note_ids = implode(',', array_keys($zn));
	$sql = "SELECT
				`note_id`,
				`txt`
			FROM `_note_comment`
			WHERE `note_id` IN (".$note_ids.")
			  AND !`deleted`
			ORDER BY `id` ASC";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q)) {
		$zayav_id = $zn[$r['note_id']];
		$arr[$zayav_id]['note'] = $r['txt'];
	}

	return $arr;
}
function _dogovorValToList($arr) {//������� ������ �������� � ������ �� dogovor_id
	$ids = array();
	$arrIds = array();
	foreach($arr as $key => $r)
		if(!empty($r['dogovor_id'])) {
			$ids[$r['dogovor_id']] = 1;
			$arrIds[$r['dogovor_id']][] = $key;
		}
	if(empty($ids))
		return $arr;

	$sql = "SELECT *
			FROM `_zayav_dogovor`
			WHERE `app_id`=".APP_ID."
			  AND `id` IN (".implode(',', array_keys($ids)).")";
	$dog = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	foreach($dog as $r) {
		foreach($arrIds[$r['id']] as $id) {
			$arr[$id] += array(
				'dogovor_n' => $r['nomer'],
				'dogovor_nomer' => '�'.$r['nomer'],
				'dogovor_data' => _dataDog($r['data_create']).' �.',
				'dogovor_sum' => _cena($r['sum']),
				'dogovor_avans' => _sumSpace(_cena($r['avans'])),
				'dogovor_line' => '<span class="dogovor_line '._tooltip('�����: '._sumSpace($r['sum']).' ���.', -3).
									'<b>�'.$r['nomer'].'</b>'.
									' �� '._dataDog($r['data_create']).
								  '</span>',
				'dogovor_min' => '<span class="dogovor_line '._tooltip('�����: '._sumSpace($r['sum']).' ���. �� '._dataDog($r['data_create']), -3, 'l').
									'<b>�'.$r['nomer'].'</b>'.
								  '</span>'
			);
		}
	}

	return $arr;
}



/* ���� ������ */
function _zayavPole($service_id, $type_id=0, $i='') {
	$sql = "SELECT `id`,`name`
			FROM `_zayav_pole`
			".($type_id ? " WHERE `type_id`=".$type_id : '');
	$zpn = query_ass($sql, GLOBAL_MYSQL_CONNECT);

	$send = array();
	$sql = "SELECT *
			FROM `_zayav_pole_use`
			WHERE `app_id`=".APP_ID."
			  AND `service_id`=".$service_id."
			  ".($type_id ? " AND `pole_id` IN ("._idsGet($zpn, 'key').")" : '')."
			ORDER BY `sort`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q)) {
		$name = $r['label'] ? $r['label'] : $zpn[$r['pole_id']];
		$label = $name.':';
		$label .= $r['require'] ? '*' : '';
		$send[$r['pole_id']] = array(
			'label' => $label,
			'name' => $name,
			'require' => $r['require']
		);
	}

	if($i == 'js') {//� ������� 21:1 ��� ���������� � ������
		$ids = _idsGet($send, 'key');
		$ids = _idsAss($ids);
		return _assJson($ids);
	}

	return $send;
}
function _zayavPoleEdit($v=array()) {//��������/�������������� ������
	$service_id = _num(@$v['service_id']);
	$client_id = _num(@$v['client_id']);
	$zayav_id = _num(@$v['zayav_id']);

	if(!$z = _zayavQuery($zayav_id))
		$z['client_id'] = $client_id;

	$tovar = _zayavTovarValue($zayav_id);

	$pole = array(
		1 => '<tr><td class="label">{label}'.
				 '<td><input type="text" id="ze-name" value="'.@$z['name'].'" />',

		2 => '<tr><td class="label topi">{label}'.
				 '<td><textarea id="ze-about">'.@$z['about'].'</textarea>',
		
		3 => '<tr><td class="label">{label}'.
				 '<td><input type="text" class="money" id="ze-count" value="'.@$z['count'].'" /> ��.',
		
		4 => '<tr><td class="label topi">{label}'.
				 '<td><input type="hidden" id="ze-tovar-one" value="'.$tovar.'" />',

		5 => '<tr><td class="label">{label}'.
				 '<td><input type="hidden" id="ze-client_id" value="'.@$z['client_id'].'" />'.
					($client_id ? '<b id="ze-client-name">'._clientVal($client_id, 'name').'</b>' : ''),

		6 => '<tr><td class="label">{label}'.
				 '<td><input type="text" id="ze-adres" value="'.@$z['adres'].'" />'.
					 '<input type="hidden" id="client-adres" />',

		7 => '<tr><td class="label">{label}'.
				 '<td><input type="text" id="ze-imei" value="'.@$z['imei'].'" />',
		
		8 => '<tr><td class="label">{label}'.
				 '<td><input type="text" id="ze-serial" value="'.@$z['serial'].'" />',
		
		9 => '<tr><td class="label">{label}<td id="ze-color">',
		
		10 => '<tr><td class="label">{label}'.
				  '<td><input type="hidden" id="ze-executer_id" value="'.@$z['executer_id'].'" />',

		11 => '<tr><td class="label topi">{label}'.
				 '<td><input type="hidden" id="ze-tovar-several" value="'.$tovar.'" />',

		12 => '<tr><td class="label top">{label}'.
				  '<td><input type="hidden" id="tovar-place" />',
		
		13 => '<tr><td class="label">{label}'.
				  '<td><input type="hidden" id="ze-srok" value="'.@$z['srok'].'" />',

		14 => '<tr><td class="label top">{label}<td><textarea id="ze-note"></textarea>',
		
		15 => '<tr><td class="label">{label}'.
				  '<td><input type="text" class="money" id="ze-sum_cost" value="'.(_cena(@$z['sum_cost']) ? _cena($z['sum_cost']) : '').'" /> ���.',

		16 => '<tr><td class="label topi">{label}'.
				  '<td><input type="hidden" id="ze-pay_type" value="'.@$z['pay_type'].'" />',

		31 => '<tr class="tr-equip'.($tovar ? '' : ' dn').'">'.
				  '<td class="label top">{label}'.
				  '<td><input type="hidden" id="ze-equip" value="'.@$z['equip'].'" />'.
						'<div id="ze-equip-spisok"></div>'
	);

	$send = '';
	foreach(_zayavPole($service_id, 1) as $pole_id => $r) {
		if(empty($pole[$pole_id]))
			continue;
		if($zayav_id && ($pole_id == 10 || $pole_id == 12 || $pole_id == 13 || $pole_id == 14))
			continue;
		$send .= str_replace('{label}', $r['label'], $pole[$pole_id]);
	}

	return
	'<table class="bs10">'.$send.'</table>';
}
/*
					''.

					'<div class="condLost'.(!empty($v['find']) ? ' dn' : '').'">'.

						'<div class="findHead">�������� ��������</div>'.
						_radio('zpzakaz', array(0=>'�� �����',1=>'��',2=>'���'), $v['zpzakaz'], 1).

					'</div>'.
*/
function _zayavPoleFilter($v=array()) {
	$pole = array(
		17 => '<div id="find"></div>',

		18 => '<div class="findHead">{label}</div>'.
			   _radio('sort', array(1=>'�� ���� ����������',2=>'�� ���������� �������'), $v['sort']).
			   _check('desc', '�������� �������', $v['desc']),

		24 => '<div class="findHead">{label}</div>'.
			  _zayavStatus($v['status'], 'filter'),

		25 => '<div class="findHead">{label}<input type="hidden" id="finish" value="'.$v['finish'].'" /></div>',

		26 => '<div class="findHead">{label}</div>'.
			  _radio('paytype', array(0=>'�� �����',1=>'��������',2=>'����������'), $v['paytype'], 1),

		27 => '<div class="findHead">{label}</div>'.
			  '<input type="hidden" id="executer_id" value="'.$v['executer_id'].'" />',

		28 => '<div class="findHead">{label}</div>'.
			  '<input type="hidden" id="tovar_place_id" value="'.$v['tovar_place_id'].'" />',

		29 => _check('noschet', '���� �� �������', $v['noschet']),

		30 => _check('nofile', '{label}', $v['nofile'], 1),

		32 => '<script>var ZAYAV_TOVAR_NAME_SPISOK='._zayavTovarName().';</script>'.
			  '<div class="findHead">{label}</div>'.
			  '<input type="hidden" id="tovar_name_id" value="'.$v['tovar_name_id'].'" />'
	);

	$send = '';
	foreach(_zayavPole($v['service_id'], 2) as $pole_id => $r) {
		if(empty($pole[$pole_id]))
			continue;
		$send .= str_replace('{label}', $r['name'], $pole[$pole_id]);
	}

	return $send;
}
function _zayavTovarName() {
	$sql = "SELECT DISTINCT `tovar_id`
			FROM `_zayav_tovar`
			WHERE `app_id`=".APP_ID;
	if(!$tovar_ids = query_ids($sql, GLOBAL_MYSQL_CONNECT))
		return '[]';

	$sql = "SELECT DISTINCT `name_id`
			FROM `_tovar`
			WHERE `id` IN (".$tovar_ids.")";
	$name_ids = query_ids($sql, GLOBAL_MYSQL_CONNECT);

	$sql = "SELECT `id`,`name`
			FROM `_tovar_name`
			WHERE `id` IN (".$name_ids.")";
	return query_selJson($sql, GLOBAL_MYSQL_CONNECT);
}

/* ���������� � ������ */
function _zayavQuery($zayav_id, $withDel=0) {
	$sql = "SELECT *
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  ".($withDel ? '' : ' AND !`deleted`')."
			  AND `id`=".$zayav_id;
	if(!$z = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		return array();
	return $z;
}
function _zayav_info() {
	if(!$zayav_id = _num(@$_GET['id']))
		return _err('�������� �� ����������');

	if(!$z = _zayavQuery($zayav_id, 1))
		return _err('������ �� ����������.');

	_service('current', $z['service_id']);//��������� ���� ��� �������� �� ��������������� ������ ������

	if(!VIEWER_ADMIN && $z['deleted'])
		return _noauth('������ ������');

	$zpu = _zayavPole($z['service_id']);
	$z['zpu'] = $zpu;

	$history = _history(array('zayav_id'=>$zayav_id));

	$z['sum_cost'] = _cena($z['sum_cost']);
	$z['sum_accrual'] = _cena($z['sum_accrual']);
	$z['sum_pay'] = _cena($z['sum_pay']);

	//������� ���������� � ��������
	$sum_diff = abs($z['sum_dolg']) ? ($z['sum_dolg'] < 0 ? '���' : '����').'�������� <b>'._sumSpace(abs($z['sum_dolg'])).'</b> ���.' : '';

	return
	_attachJs(array('zayav_id'=>$zayav_id)).
	'<script>'.
		'var ZI={'.
				'id:'.$zayav_id.','.
				'service_id:'.$z['service_id'].','.
				'pole:'._zayavPole($z['service_id'], 0, 'js').','.
				'nomer:'.$z['nomer'].','.
				'client_id:'.$z['client_id'].','.
				'client_link:"'.addslashes(_clientVal($z['client_id'], 'link')).'",'.
				'name:"'.addslashes($z['name']).'",'.
				'about:"'.addslashes(str_replace("\n", '', $z['about'])).'",'.
				'count:'.$z['count'].','.
				'status_id:'.$z['status_id'].','.
				'status_day:"'.($z['status_day'] == '0000-00-00' ? '' : $z['status_day']).'",'.
				'status_sel:'._zayavStatus('select').','.
				'executer_id:'.$z['executer_id'].','.
				'srok:"'.$z['srok'].'",'.
				'adres:"'.addslashes($z['adres']).'",'.
				'tovar_id:'._zayavTovarOneId($z).','.
				'tovar:"'._zayavTovarValue($zayav_id).'",'.
				'place_id:'.$z['tovar_place_id'].','.
				'equip:"'.$z['equip'].'",'.
				'imei:"'.addslashes($z['imei']).'",'.
				'serial:"'.addslashes($z['serial']).'",'.
				'color_id:'.$z['color_id'].','.
				'color_dop:'.$z['color_dop'].','.
				'sum_cost:'.$z['sum_cost'].','.
				'pay_type:'.$z['pay_type'].','.
				'todel:'._zayavToDel($zayav_id).','.//���������� ����� ��� �������� ������
				'deleted:'.$z['deleted'].
			'},'.
			'DOG={'._zayavDogovorJs($z).'};'.
			'KVIT={'.
				'dtime:"'.FullDataTime($z['dtime_add']).'",'.
				'color:"'._color($z['color_id'], $z['color_dop']).'",'.
				'phone:"'._clientVal($z['client_id'], 'phone').'",'.
				'defect:"'.addslashes(str_replace("\n", ' ', _note(array('last'=>1)))).'"'.
			'};'.

	'</script>'.
	'<div id="_zayav-info">'.
		'<div id="dopLinks">'.
			'<a class="link a-page sel">����������</a>'.
			'<a class="link" onclick="_zayavEdit('.$z['service_id'].')">��������������</a>'.
			'<a class="link _accrual-add">���������</a>'.
			'<a class="link _income-add">������� �����</a>'.
			'<a class="link a-page">�������</a>'.
			'<div id="nz" class="'._tooltip('����� ������', -74, 'r').'#'.$z['nomer'].'</div>'.
		'</div>'.

		($z['deleted'] ? '<div id="zayav-deleted">������ �������.</div>' : '').

		'<table class="page">'.
			'<tr><td id="left">'.

					'<div class="headName">'.
						_zayav_info_category($z).
						$z['name'].
						'<input type="hidden" id="zayav-action" />'.
					'</div>'.

	 ($z['about'] ? '<div class="_info">'._br($z['about']).'</div>' : '').

					'<table id="tab">'.
	 ($z['client_id'] ? '<tr><td class="label">������:<td>'._clientVal($z['client_id'], 'go') : '').
		 ($z['count'] ? '<tr><td class="label">����������:<td><b>'.$z['count'].'</b> ��.' : '').
						_zayav_tovar_several($z).
		 ($z['adres'] ? '<tr><td class="label">�����:<td>'.$z['adres'] : '').
	($z['dogovor_id'] ? '<tr><td class="label">�������:<td>'._zayavDogovor($z) : '').
	  ($z['sum_cost'] ? '<tr><td class="label">���������:<td><b>'.$z['sum_cost'].'</b> ���.' : '').
	  ($z['pay_type'] ? '<tr><td class="label">������:<td>'._payType($z['pay_type']) : '').

						'<tr><td class="label">������:<td>'._zayavStatusButton($z).

					(isset($zpu[10]) || $z['executer_id'] ?
						'<tr><td class="label r">�����������:'.
							'<td id="executer_td"><input type="hidden" id="executer_id" value="'.$z['executer_id'].'" />'
					: '').

					(isset($zpu[13]) || $z['srok'] != '0000-00-00' ?
		                '<tr><td class="label">����:<td><input type="hidden" id="srok" value="'.$z['srok'].'" />'
					: '').

					(isset($zpu[22]) || $z['attach_id'] ?
						'<tr><td class="label">'.(isset($zpu[22]) ? $zpu[22]['name'] : '��������').':<td><input type="hidden" id="attach_id" value="'.$z['attach_id'].'" />'
					: '').

   ($z['sum_accrual'] ? '<tr><td class="label">���������:<td><b class="acc">'._sumSpace($z['sum_accrual']).'</b> ���.' : '').
	   ($z['sum_pay'] ? '<tr><td class="label">��������:'.
							'<td><b class="pay">'._sumSpace($z['sum_pay']).'</b> ���.'.
				   ($sum_diff ? '<span id="sum-diff">'.$sum_diff.'</span>' : '')
		: '').
					'</table>'.

					'<div id="added">'.
						'������ '._viewerAdded($z['viewer_id_add']).' '.
						FullDataTime($z['dtime_add']).
					'</div>'.

					_zayavInfoCartridge($z).
					_zayavKvit($zayav_id).
					_zayavInfoAccrual($zayav_id).
					_zayav_expense($zayav_id).
					_remind_zayav($zayav_id).
					_zayavInfoMoney($zayav_id).
					_note().

				'<td id="right">'._zayavInfoTovar($z).
		'</table>'.

		'<div class="page dn">'.
			'<div class="headName">'._zayav_info_category($z).$z['name'].' - ������� ��������</div>'.
			$history['spisok'].
		'</div>'.

	'</div>';
}
function _zayav_info_category($z) {//����������� �������� ��������� ������
	if(!$z['service_id'])
		return '';

	return
		'<a id="category" onclick="_zayavTypeChange()">'.
			_service('name', $z['service_id']).
		'</a>'.
		'<br />';
}
function _zayavToDel($zayav_id) {//����� �� ������� ������..
	if(!_zayavQuery($zayav_id))
		return 0;

	//�������� �� ������� ��������
	$sql = "SELECT COUNT(`id`)
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 0;

	//�������� �� ������� ���������
	$sql = "SELECT COUNT(`id`)
			FROM `_money_refund`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 0;

	//�������� �� ������� ����������� ���������
	$sql = "SELECT COUNT(`id`)
			FROM `_zayav_dogovor`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 0;

	//�������� �� ������� ������ �� ������
	$sql = "SELECT COUNT(`id`)
			FROM `_schet`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 0;

	//�������� �� ������� ���������� �� �����������
	$sql = "SELECT COUNT(`id`)
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`
			  AND `zayav_id`=".$zayav_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 0;

	return 1;
}
function _zayavDogovor($z) {//����������� ������ ��������
	$sql = "SELECT *
			FROM `_zayav_dogovor`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$z['id'];
	if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$title = '�� '._dataDog($r['data_create']).' �. �� ����� '._cena($r['sum']).' ���.';

	return '<b class="dogn'._tooltip($title, -7, 'l').'�'.$r['nomer'].'</b> '.
			'<a href="'.LINK_DOGOVOR.'/'.$r['link'].'.doc" class="img_word'._tooltip('�����������', -41).'</a>';
}
function _zayavDogovorJs($z) {
	if(!$z['dogovor_id']) {
		$c = _clientVal($z['client_id']);
		return
			'nomer_next:'._maxSql('_zayav_dogovor', 'nomer', 1).','.
			'fio:"'.addslashes($c['fio']).'",'.
			'adres:"'.addslashes($c['adres']).'",'.
			'pasp_seria:"'.addslashes($c['pasp_seria']).'",'.
			'pasp_nomer:"'.addslashes($c['pasp_nomer']).'",'.
			'pasp_adres:"'.addslashes($c['pasp_adres']).'",'.
			'pasp_ovd:"'.addslashes($c['pasp_ovd']).'",'.
			'pasp_data:"'.addslashes($c['pasp_data']).'"';
	}

	$sql = "SELECT *
			FROM `_zayav_dogovor`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `id`=".$z['dogovor_id'];
	if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$sql = "SELECT `invoice_id`
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `dogovor_id`=".$r['id']."
			LIMIT 1";
	$invoice_id = query_value($sql, GLOBAL_MYSQL_CONNECT);

	return
		'id:'.$r['id'].','.
		'nomer:'.$r['nomer'].','.
		'fio:"'.addslashes($r['fio']).'",'.
		'adres:"'.addslashes($r['adres']).'",'.
		'pasp_seria:"'.addslashes($r['pasp_seria']).'",'.
		'pasp_nomer:"'.addslashes($r['pasp_nomer']).'",'.
		'pasp_adres:"'.addslashes($r['pasp_adres']).'",'.
		'pasp_ovd:"'.addslashes($r['pasp_ovd']).'",'.
		'pasp_data:"'.addslashes($r['pasp_data']).'",'.
		'data_create:"'.$r['data_create'].'",'.
		'sum:'._cena($r['sum']).','.
		'avans_hide:'.(TODAY == substr($r['dtime_add'], 0, 10) ? 0 : 1).','.
		'avans_invoice_id:'.$invoice_id.','.
		'avans_sum:'.($invoice_id ? _cena($r['avans']) : '""');
}
function _zayavDogovorFilter($v) {//�������� ���� �������� ������ �� ��������
	if(!_num($v['id']) && $v['id'] != 0)
		return '������: ������������ ������������� ��������';
	if(!_num($v['zayav_id']))
		return '������: �������� ����� ������';
	if(!_num($v['nomer']))
		return '������: ����������� ������ ����� ��������';
	if(!preg_match(REGEXP_DATE, $v['data_create']))
		return '������: ����������� ������� ���� ���������� ��������';
	if(!_cena($v['sum']))
		return '������: ����������� ������� ����� �� ��������';
//	if(!empty($v['avans']) && !_cena($v['avans']))
//		return '������: ����������� ������ ��������� �����';
	$send = array(
		'id' => _num($v['id']),
		'zayav_id' => _num($v['zayav_id']),
		'nomer' => _num($v['nomer']),
		'fio' => trim($v['fio']),
		'adres' => trim($v['adres']),
		'sum' => _cena($v['sum']),
		'invoice_id' => _num($v['invoice_id']),
		'avans' => _cena($v['avans']),
		'data_create' => $v['data_create'],
		'link' => time().'_dogovor_'.intval($v['nomer']).'_'.$v['data_create'],
		'pasp_seria' => trim($v['pasp_seria']),
		'pasp_nomer' => trim($v['pasp_nomer']),
		'pasp_adres' => trim($v['pasp_adres']),
		'pasp_ovd' => trim($v['pasp_ovd']),
		'pasp_data' => trim($v['pasp_data'])
	);

	$sql = "SELECT COUNT(`id`)
			FROM `_zayav_dogovor`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `id`!=".$send['id']."
			  AND `nomer`=".$send['nomer'];
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return '������: ������� � ������� <b>'.$send['nomer'].'</b> ��� ��� ��������';

	if(empty($send['fio']))
		return '������: �� ������� ��� �������';

//	if($send['sum'] < $send['avans'])
//		return '������: ��������� ����� �� ����� ���� ������ ����� ��������';

	if($send['avans'] && !$send['invoice_id'])
		return '������: �� ������ ����� ����� ��� ���������� �������';

	$sql = "SELECT `client_id`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `id`=".$send['zayav_id'];
	if(!$send['client_id'] = query_value($sql, GLOBAL_MYSQL_CONNECT))
		return '������: ������ id='.$send['zayav_id'].' �� ����������, ���� ��� ���� �������';

	return $send;
}
function _zayavDogovorPrint($v) {
	require_once(GLOBAL_DIR.'/word/clsMsDocGenerator.php');

	$income_id = 0;
	if(!is_array($v)) {
		$sql = "SELECT *
				FROM `_zayav_dogovor`
				WHERE `app_id`=".APP_ID."
				  AND !`deleted`
				  AND `id`=".$v;
		$v = query_assoc($sql, GLOBAL_MYSQL_CONNECT);

		$v['save'] = 1; //��������� �������

		if($v['avans']) {
			$sql = "SELECT `id`
					FROM `_money_income`
					WHERE `app_id`=".APP_ID."
					  AND !`deleted`
					  AND `dogovor_id`=".$v['id']."
					LIMIT 1";
			$income_id = query_value($sql, GLOBAL_MYSQL_CONNECT);
		}
	}

	$ex = explode(' ', $v['fio']);
	$fioPodpis = $ex[0].' '.
				 (isset($ex[1]) ? ' '.$ex[1][0].'.' : '').
				 (isset($ex[2]) ? ' '.$ex[2][0].'.' : '');

	$doc = new clsMsDocGenerator(
		$pageOrientation = 'PORTRAIT',
		$pageType = 'A4',
		$cssFile = GLOBAL_DIR.'/css/dogovor.css',
		$topMargin = 1,
		$rightMargin = 2,
		$bottomMargin = 1,
		$leftMargin = 1
	);

	$v['sum'] = _cena($v['sum']);
	$v['avans'] = _cena($v['avans']);
	$dopl = $v['sum'] - $v['avans'];
	$dopl = $dopl < 0 ? 0 : $dopl;
	$adres = $v['pasp_adres'] ? $v['pasp_adres'] : $v['adres'];

	$doc->addParagraph(
	'<div class="head-name">������� �'.$v['nomer'].'</div>'.
	'<table class="city_data"><tr><td>����� �������<th>'._dataDog($v['data_create']).'</table>'.
	'<div class="paragraph">'.
		'<p>�������� � ������������ ���������������� ����������� ��������, '.
		'� ���� ��������� �� ��������, '._viewer(VIEWER_ID, 'viewer_name_full').', ����������� �� ��������� ������������, '.
		'� ����� �������, � '.$v['fio'].($adres ? ', '.$adres : '').', ��������� � ���������� ���������, � ������ �������, '.
		'��������� ��������� �������, ����� ��������, � �������������:'.
	'</div>'.
	'<div class="p-head">1. ������� ��������</div>'.
	'<div class="paragraph">'.
		'<p>1.1. ��������� ��������� �� ���� ������������� �� ���������� ������ �� ������������ � �������� ������� (������� ������, ������� ������, �������� ������, �������� � ������������ �����) � ������������ � ��������������� ���������������� ������� � ������������ ��������� (����� ������). ������ �� ��������� ������� � ����������� �� ��� �� ������ ���������.'.
		'<p>1.2. ������ �������������� ������ ���������� � ������������, ���������� ������������ ������ ���������� ��������.'.
	'</div>'.
	'<div class="p-head">2. ����������� ������</div>'.
	'<div class="paragraph">'.
		'<p>2.1. ��������� ��������� ��������� ����� � ����������� ������� ���������� �������� � ����������, ������������� � ��������� ������� ���� � ��������� � ������ �23166-99 ������ ������� �ӻ, �30970-2002 ������ ������� �� ��ջ ��� ������� ������, � ������� ������������ ������������� ������ �������� ��� ������� ������, � ����������� �� ������������ ������, ����������� �� ������������ �����, � ������ �111-2001 ������� ��������, �24866-99 ������������� ������� ������������� �����������.'.
		'<p>2.2. ��������������� ������������� ���� �������� ������ � ���������� ��������������� ����� ���������� 20 ������� ����. ������������� ���� ���������� �������� �� ����� �������� ������� ���� � ������� ����������� �� ��������� ������ ������ �� �������� � ����������� ���������� ������� ������� 2.3. � 2.4. ������ ����� ������������� �� ����������� ��������. � ������ ������ ������� � ������� �������, ���� �������� ������������� �� ���������� �������������� ���� �� ������������ ������� �����������, ��������� � ������������.'.
		'<p>2.3. �������� ��������� ���������� ������ ����������� � ������ �������������� � ������� �������, ������ ������� ���������, ��������� �������� �� ���� � �����������, ���� ������� ������������ ���������� ����� �� ��������� ��������� �� ������ ���������. '.
		'��������� �� �������� �� ����������� �������� �������� � ���� �����. ��������� �� ���� ��������������� �� ��������� ��������� ����������� ������� ������ ��� ���������� ��������� � ���������� �����, ��������� � ��������� �������� �������� � ������� ������� ������������ ��������. ����������������� ������ ���������� �� ������� � �� ���� ���������. � ��������� ������� ������� �� ������ ������������ ��������� ��� �� 4 �� �������.'.
		'<p>2.4. �������� ��������� ������� ���� �� ����������� ���������� �������������� �� �������� ��� �������� ��������. � ������, ���� �������� �� ������ ������ ���� � ������ ����������� ����������, �������� ���������� �������������� ����� ��������� ������� �� ������� 1000 ���./�����, ��� ���� ������������� ���� ���������� �������� �������� 10 ������� ���� � ������� ����������� ����������� ������� � ����� ���������.'.
		'<p>2.5. ��� ����������� ��������� ������������ ����������� �� ����� ��������� � ������� ���������. � ������, ���������� ��������� ��� ��� ������������� �� ������� � ������������� ���� ��������, ��������� �������� ������������ �� ������� 1000 ���./�����, ��� ���� ������������� ���� ���������� �������� �������� 10 ������� ���� � ������� ����������� ����������� ������� � ����� ���������.'.
		'<p>2.6. ��������� ��������� ������� ������������ ����� � �����, ���� ��� ������������ �� �������. ��������� �� ����� ��������������� �� ����� ������������� ������, ������������� ����� ���������� ����� �� ��������� ���������. �������� ��������� ����������� ����� � ���������� ������ �������� ����������� ������. ��������� ��������� ������� ������������ ����� �� ������������������ �������� (� ������������ � ������� �239-29 �� 29.05.2003), ������ � ������, ���� ������ ������ ���� �������� ���������� � ������� � ������������.'.
		'<p>2.7. �������� ��������� �������� ������ ��������� ������ �� ������ ��������� ������� � ����������� �� ��� � ������������ �� �������������.'.
		'<p>2.8. ����� ������������� �� ����� ��������� � ��������� � ������ ���������� �� ���������������������� ����������. � ������ ����������� �� ����������, ������������� � �������� ����, ���� ����������� ���������� � ���������������������� ����������.'.
		'<p>2.9. �������� ��������� ����������� ������ ����������� ����� �� ������� �������, �� ����������, ��������, �������������, �������� ���� � �������� ������� � ��������� ��� ���������� ����� ������������ � ���� ����� � ������ ������ � ��� ���������� ����� ���� ����� - ������ ������. �������� ����� �1 ������������ ������� � ���������, � ����� �2 ������� ������������ ���������� ��������� ������������. ��� �����-�������� ��������� ��������� �������� �� �������������� ������� ��� ��������� ������������ � ������, ���� ������� ������������ ��������������� ��������. ����������� ���������� ��� ������������ ����������� ��������� ���������� �� �������� ���������� ����������� ������� ��������. � ������, ���� �������� ������������ ����������� ���� �����-������� ������ �/��� �����������, ����� ��������� ������������� �����������.'.
		'<p>2.10. � ������ ���������� ���������� ���������������������� ���������� ��� ���� ����������� ����� � �������������, ��������� ��������� ����������� ������ ����������� � ������� 5 ����, ��� ���� ���� ���������� �������� ������������� ������������ �� ��������� ����.'.
		'<p>2.11. � ������ �������� � ���������� ��������� ��������� ������ �������� ��������������� ����� ������ ��� ��������� ���� ��������, ��������������� ��������� ��������� ��� ����� ������� � ������� 14 ������� ����, ��������� �� ���� ��������� ��������� ���������.'.
	'</div>'.
	'<div class="p-head">3. ���� ������ � ������� ��������</div>'.
	'<div class="paragraph">'.
		'<p>3.1. ������ ��������� ������ ����������: '.$v['sum'].' ('._numToWord($v['sum']).' ����'._end($v['sum'], '�', '�', '��').') ��������� � ������������, �������� ��������, � ��������� ��� ��������� �������� ������ �� ��������.'.
		($v['avans'] ?
			'<p>3.2. ������ �� ���������� �������� �������������� � ��������� �������:'.
			'<p>3.2.1. ��������� ����� � ������� '.$v['avans'].' ('._numToWord($v['avans']).' ����'._end($v['avans'], '�', '�', '��').') �������� ���������� � ���� ���������� ���������� ��������. � ������ ���������� ����� �� ��������, ��������� ����� ���������� 100% ����� ��������.'.
			($dopl ?
				'<p>3.2.2. ������� �� ��������, � ����� '.$dopl.' ('._numToWord($dopl).' ����'._end($dopl, '�', '�', '��').'), ������������ � ����� �� ��������� �������: ______________________________________.'
			: '')
		: '').
	'</div>'.
	'<div class="p-head">4. �������� � ����������� �������������</div>'.
	'<div class="paragraph">'.
		'<p>4.1. ����������� ���� �� ������� ����� � ��� ����, �� ��������� � ���������� ������ �� ������� ������ � ���� ���. ����������� ���� �� ������� �����, ��������� ������� � ������ - ���� ���. �� ��������� � ���������� ������ �� ��������� ������� ������, ��������� ������ � ����� � ���� ���. ����������� ���� ��������� � ������� ���������� ��������� ����������� ���������� (��� ����� � ������� ������). ��� ������������� ������� ������-��������� � ������������ ������� ������ ������������� ������������ ������������ ������������. �������� ���������������, ��� ��� ��������� ������������� ������������, �������� ����������� ���������� � ����������� ������������ � ������ ������ ��� ����� ������� ����������� ���������� �����, ��� ��� ��������� ������������� ������������. �������� �����������, ��� ��� ���������� ����������� ��������� ���������� � ����������� ������ �� �������������, ���������� ����������� ������ ����������� � ��������� ���������������� ��� ������ ���������.'.
		'<p>4.2. ��������� ��������� �������� �������� � ������ ������ ������������� �� ���� ����, � ������ ������ �� �� ����� � ������� ������������ �����. ���� ���������� ����������� ����� ���������� �� ����� 20 ������� ���� � ������� ����������� ���������� ���������. ���������� ��������� ����������� � ����������� ����� �������� ���� �� �����.'.
		'<p>4.3. �������� �� ���������������� �� ������, ����� ����� (��� ��� �������������) �������� ���� ������������ �������������� ���������� ������������ ������������ ������, �������� ������� ��� ��� � ������ ������������� ������������� ������������� ����.'.
	'</div>'.
	'<div class="p-head">5. ��������������� ������, ����-�������� �������������� � ��������������� ������</div>'.
	'<div class="paragraph">'.
		'<p>5.1. ������� ������������� �� ��������������� �� ��������� ��� ������ ������������ ������������ �� ���������� ��������, ���� ��� ������� ���������� ������������� ������������� ���� (����-�����), �.�. ������, ��������� ��������, �����, ������, �������� ����������������� ����������� ����������, ���������� ��������� � ��������. ��� ���� ���� ���������� ������������ �� �������� ������������ �� ������ �������� ��������� �������������.'.
		'<p>5.2. �� ������������ ��� ������������ ���������� ������������ ������� ����� ��������������� � ������������ � ����������� ����������������� ���������� ���������. � ������ ��������� ������ ���������� �������� ��������� ����������� ��������� ��������� � ������������ � ������� �� "� ������ ���� ������������" ������� 3% � ���� �� ����� ���������������� ������������� ������ ��������� � ������������ � �� ����� �� ��������� ����� � �����, ��������� � ������������.'.
	'</div>'.
	'<div class="p-head">6. ��������� ������� �������� � ������� ���������� ������</div>'.
	'<div class="paragraph">'.
		'<p>6.1. ��� ��������� � ���������� � ���������� �������� ������������� ���� � ��� ������, ���� ��� ��������� � ���������� ���� � ��������� ������ ���������.'.
		'<p>6.2. ��� ����� � �����������, ������� ����� ���������� �� ���������� �������� ����� �� ����������� ����������� ���� ������������ �����������.'.
		'<p>6.3. �����, �� ���������� ���������� � ���������� �����������, �������� ���������� � ������������ � ����������� ����������������� ��.'.
	'</div>'.
	'<div class="p-head">7. ���� �������� ��������</div>'.
	'<div class="paragraph">'.
		'<p>7.1. ��������� ������� �������� � ���� � ������� ��� ���������� � ��������� �� ������� ���������� ������������ ������ ���������.'.
	'</div>'.
	'<div class="p-head">8. �������������� ���������</div>'.
	'<div class="paragraph">'.
		'<p>8.1. ��������� ������� ��������� � ���� ����������� �� ������ ��� ������ �� ������, ������� ������ ����������� ����.'.
	'</div>'.
	'<div class="p-head">9. ����������� ������ � ���������� ��������� ������</div>'.
	'<table class="rekvisit">'.
		'<tr><td><b>���������:</b><br />'.
				'��� �'._app('name').'�<br />'.
				'���� '._app('ogrn').'<br />'.
				'��� '._app('inn').'<br />'.
				'��� '._app('kpp').'<br />'.
				str_replace("\n", '<br />', _app('adres_yur')).'<br />'.
				'���. '._app('phone').'<br /><br />'.
				'����� �����: '._app('adres_ofice').
			'<td><b>��������:</b><br />'.
				$v['fio'].'<br />'.
				'������� ����� '.$v['pasp_seria'].' '.$v['pasp_nomer'].'<br />'.
				'����� '.$v['pasp_ovd'].' '.$v['pasp_data'].'<br /><br />'.
				$adres.
	'</table>'.
	'<div class="podpis-head">������� ������:</div>'.
	'<table class="podpis">'.
		'<tr><td>��������� ________________ '._viewer(VIEWER_ID, 'viewer_name_init').
			'<td>�������� ________________ '.$fioPodpis.
	'</table>'.
	'<div class="mp">�.�.</div>');

	$doc->newPage();

	$doc->addParagraph(
	'<div class="ekz">��������� ���������</div>'.
	'<div class="act-head">��� �����-������ ������</div>'.
	'<table class="act-tab">'.
		'<tr><td class="label">�� ������:<td class="title">'.$v['adres'].'<td><td>'.
		'<tr><td class="label">�����:<td class="title">'.$v['nomer'].'<td class="label">��������:<td>'.$fioPodpis.
	'</table>'.
	'<div class="act-inf">��������� ��������� �������� ���������� ��� ����������� ���������.</div>'.
	'<div class="act-p">'.
		'<p>1. ������� ����� ������ ��� ���������, �� ���������� ����������� (�������� ����������) �� ����������, ��������, ������������� � �������� ����:'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
	'</div>'.
	'<div class="act-p">'.
		'<p>2. ����������� ������ ������ ��� ���������, �� ���������� ����������� (�������� ����������):'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
	'</div>'.
	'<div class="act-p">�� ��������� ___________________________________</div>'.
	'<div class="act-p">�� ���������� /�������� �����������/ ____________________________________</div>'.
	'<div class="act-p">���� _______________</div>'.
	'<div class="cut-line">��������</div>'.
	'<div class="ekz">��������� ��������� �����������</div>'.
	'<div class="act-head">��� �����-������ ������</div>'.
	'<table class="act-tab">'.
		'<tr><td class="label">�� ������:<td class="title">'.$v['adres'].'<td><td>'.
		'<tr><td class="label">�����:<td class="title">'.$v['nomer'].'<td class="label">��������:<td>'.$fioPodpis.
	'</table>'.
	'<div class="time-dost">����� �������� _____________________</div>'.
	'<div class="act-p">'.
		'<p>1. ������� ����� ������ ��� ���������, �� ���������� ����������� (�������� ����������) �� ����������, ��������, ������������� � �������� ����:'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
	'</div>'.
	'<div class="act-p">'.
		'<p>2. ����������� ������ ������ ��� ���������, �� ���������� ����������� (�������� ����������):'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
	'</div>'.
	'<div class="act-p">�� ��������� ___________________________________</div>'.
	'<div class="act-p">�� ���������� /�������� �����������/ ____________________________________</div>'.
	'<div class="act-p">���� _______________</div>'
	);

	if($income_id) {
		$doc->newPage();
		$doc->addParagraph(_incomeReceipt($income_id));
	}

	if(!is_dir(PATH_DOGOVOR))
		mkdir(PATH_DOGOVOR, 0777, true);

	$doc->output($v['link'], @$v['save'] ? PATH_DOGOVOR : '');
}
function _zayavBalansUpdate($zayav_id) {//���������� ������� ������
	//����������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_accrual`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	$accrual = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//�������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `confirm` NOT IN (1,3)
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	$income = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//��������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_refund`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	$refund = query_value($sql, GLOBAL_MYSQL_CONNECT);

	$income -= $refund;

	//������� ������
	$sql = "SELECT COUNT(`id`)
			FROM `_schet`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	$schet_count = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//�������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `zayav_id`=".$zayav_id;
	$expense = query_value($sql, GLOBAL_MYSQL_CONNECT);

	$sql = "UPDATE `_zayav`
			SET `sum_accrual`=".$accrual.",
				`sum_pay`=".$income.",
				`sum_dolg`=`sum_pay`-`sum_accrual`,
				`sum_expense`=".$expense.",
				`sum_profit`=".($accrual - $expense).",
				`schet_count`=".$schet_count."
			WHERE `id`=".$zayav_id;
	query($sql, GLOBAL_MYSQL_CONNECT);
}
function _zayavExecuterJs() {//������ �����������, ������� ����� ���� �������������
	$sql = "SELECT `viewer_id`
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `worker`";
	$ids = query_ids($sql, GLOBAL_MYSQL_CONNECT);

	$sql = "SELECT `viewer_id`,1
			FROM `_vkuser_rule`
			WHERE `app_id`=".APP_ID."
			  AND `key`='RULE_EXECUTER'
			  AND `value`
			  AND `viewer_id` IN (".$ids.")";
	return query_assJson($sql, GLOBAL_MYSQL_CONNECT);
}
function _zayavTovarOneId($z) {//��������� id ������, ���� ������������ ���� �����
	if(!isset($z['zpu'][4]))
		return 0;

	$sql = "SELECT `tovar_id`
			FROM `_zayav_tovar`
			WHERE `zayav_id`=".$z['id']."
			LIMIT 1";
	return query_value($sql, GLOBAL_MYSQL_CONNECT);
}
function _zayavTovarValue($zayav_id) {//��������� �������� ������� ��� js � �������: tovar_id:count,4345:1
	if(!$zayav_id)
		return '';

	$sql = "SELECT *
			FROM `_zayav_tovar`
			WHERE `zayav_id`=".$zayav_id;
	if(!$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$send = array();
	foreach($arr as $r)
		$send[] = $r['tovar_id'].':'.$r['count'];

	return implode(',', $send);
}
function _zayavInfoTovar($z) {//���������� � ������
	if(!isset($z['zpu'][4]))
		return '';

	$sql = "SELECT `tovar_id`
			FROM `_zayav_tovar`
			WHERE `zayav_id`=".$z['id']."
			LIMIT 1";
	if(!$tovar_id = query_value($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	if(!$tovar = _tovarQuery($tovar_id))
		return '';

	return
	'<div id="zayav-tovar">'.
		'<div class="center">'._zayavImg($z['id'], $tovar_id).'</div>'.
		'<div class="headBlue">���������� � ������</div>'.

		'<div id="content">'.
			'<div id="tovar-name">'.
				_tovarName($tovar['name_id']).
				'<br />'.
				'<a href="'.URL.'&p=tovar&d=info&id='.$tovar['id'].'">'._tovarVendor($tovar['vendor_id']).$tovar['name'].'</a>'.
			'</div>'.
			'<table id="info">'.
	($z['imei'] ? '<tr><th>imei:	<td>'.$z['imei'] : '').
  ($z['serial'] ? '<tr><th>serial:	<td>'.$z['serial'] : '').
   ($z['equip'] ? '<tr><th valign="top">��������:<td>'._tovarEquip('spisok', $z['equip']) : '').
($z['color_id'] ? '<tr><th>����:	<td>'._color($z['color_id'], $z['color_dop']) : '').
(isset($z['zpu'][12]) ? '<tr><th>����������:<td><a id="zayav-tovar-place-change">'._zayavTovarPlace($z['tovar_place_id']).'</a>' : '').
			'</table>'.
		'</div>'.
		_zayavInfoTovarSet($tovar_id).
	'</div>';
}
function _zayavInfoTovarSet($tovar_id) {//������ ��������� ��� ������ ������
	$sql = "SELECT *
			FROM `_tovar`
			WHERE `tovar_id_set`=".$tovar_id;
	if(!$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$arr = _tovarAvaiToList($arr);

	$spisok = '';
	foreach($arr as $r)
		$spisok .=
			'<div class="unit" val="'.$r['id'].'">'.
//				'<div class="image"><div>'.$r['image_small'].'</div></div>'.
				'<a href="'.URL.'&p=tovar&d=info&id='.$r['id'].'"><b>'._tovarName($r['name_id']).'</b> '.$r['name'].'</a>'.
//				($r['version'] ? '<div class="version">'.$r['version'].'</div>' : '').
//				($r['color_id'] ? '<div class="color">����: '._color($r['color_id']).'</div>' : '').
				'<div>'.
					(isset($r['zakaz']) ? '<a class="zakaz_ok">��������!</a>' : '<a class="zakaz">��������</a>').
					($r['avai_count'] ? '<b class="avai">�������: '.$r['avai_count'].'</b> <a class="set">����������</a>' : '').
				'</div>'.
			'</div>';



	return
	'<div class="headBlue">'.
		'������ ���������'.
		'<a class="add">��������</a>'.
	'</div>'.
	'<div id="zp-spisok">'.$spisok.'</div>';
}
function _zayavImg($zayav_id, $tovar_id) {
	$sql = "SELECT *
			FROM `_image`
			WHERE !`deleted`
			  AND !`sort`
			  AND (`app_id` AND `zayav_id`=".$zayav_id." OR !`app_id` AND `tovar_id`=".$tovar_id.")
			ORDER BY `zayav_id` DESC";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		if($r['zayav_id'] || $r['tovar_id'])
			break;

	if(!$r['id'])
		return _imageNoFoto('zayav_id:'.$zayav_id);

	$size = _imageResize($r['big_x'], $r['big_y'], 200, 320);
	return
	'<img class="_iview" '.
		'val="'.$r['id'].'" '.
		'width="'.$size['x'].'" '.
		'height="'.$size['y'].'" '.
		'src="'.$r['path'].$r['big_name'].'" '.
	'/>'.
	_imageBut200('zayav_id:'.$zayav_id);
}
function _zayavKvit($zayav_id) {
	$sql = "SELECT *
			FROM `_zayav_kvit`
			WHERE `app_id`=".APP_ID."
			  AND `active`
			  AND `zayav_id`=".$zayav_id."
			ORDER BY `id`";
	if(!$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$send = '<div class="headBlue">���������</div>'.
			'<table class="_spisok _money">';
	$n = 1;
	foreach($arr as $r)
		$send .=
			'<tr><td><a onclick="_zayavKvitHtml('.$r['id'].')">��������� '.($n++).'</a>. '.
					'<span class="kvit_defect">'.$r['defect'].'</span>'.
				'<td class="dtime">'._dtimeAdd($r);
	$send .= '</table>';

	return $send;
}



/* ��������������� ������ � ������ */
function _zayavTovarPlace($place_id=false, $type=APP_TYPE) {
	$arr = array(
		1 => _appType($type, 7),
		2 => '� �������'
	);

	$sql = "SELECT `id`,`place`
			FROM `_zayav_tovar_place`
			WHERE `app_id`=".APP_ID."
			ORDER BY `place`";
	$arr += query_ass($sql, GLOBAL_MYSQL_CONNECT);

	if($place_id === false)
		return $arr;

	return isset($arr[$place_id]) ? $arr[$place_id] : '';
}
function _zayavTovarPlaceUpdate($zayav_id, $place_id, $place_name) {// ���������� ��������������� ������
	// - �������� ������ ���������������, ���� place_id = 0
	// - ���������� place_id, ���� ���������� �� �������� � ������
	
	if(!$place_id && empty($place_name))
		return false;

	$z = _zayavQuery($zayav_id);
	$placeNew = 0;

	if(!$place_id && !empty($place_name)) {
		$sql = "SELECT `id`
				FROM `_zayav_tovar_place`
				WHERE `app_id`=".APP_ID."
				  AND `place`='".$place_name."'
				LIMIT 1";
		if(!$place_id = query_value($sql, GLOBAL_MYSQL_CONNECT)) {
			$sql = "INSERT INTO `_zayav_tovar_place` (
						`app_id`,
						`place`
					) VALUES (
						".APP_ID.",
						'".addslashes($place_name)."'
					)";
			query($sql, GLOBAL_MYSQL_CONNECT);
			$place_id = query_insert_id('_zayav_tovar_place', GLOBAL_MYSQL_CONNECT);
			$placeNew++;
		}
	}
	
	if($place_id != $z['tovar_place_id']) {
		$sql = "UPDATE `_zayav`
				SET `tovar_place_id`=".$place_id.",
					`tovar_place_dtime`=CURRENT_TIMESTAMP
				WHERE `id`=".$zayav_id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		if($z['tovar_place_id']) { //������� ��������, ���� ������ ����������
			_history(array(
				'type_id' => 29,
				'client_id' => $z['client_id'],
				'zayav_id' => $zayav_id,
				'v1' => '<table>'._historyChange('', _zayavTovarPlace($z['tovar_place_id']), _zayavTovarPlace($place_id)).'</table>'
			));

			if($place_id == 2)
				_note(array(
					'add' => 1,
					'comment' => 1,
					'p' => 'zayav',
					'id' => $zayav_id,
					'txt' => '�������� �������.'
				));

			//�������� ������ ���������������
			$sql = "SELECT DISTINCT `tovar_place_id` FROM `_zayav` WHERE `tovar_place_id`";
			if($ids = query_ids($sql, GLOBAL_MYSQL_CONNECT)) {
				$sql = "DELETE FROM `_zayav_tovar_place` WHERE `id` NOT IN (".$ids.")";
				query($sql, GLOBAL_MYSQL_CONNECT);
			}

			$placeNew += mysql_affected_rows();
		}

		if($placeNew)
			_appJsValues();
	}
	return true;
}


function _zayav_tovar_several($z) {//������ ���������� ������� ��� ���������� � ������
	if(empty($z['zpu'][11]))
		return '';

	$sql = "SELECT *
			FROM `_zayav_tovar`
			WHERE `zayav_id`=".$z['id']."
			ORDER BY `id`";
	if(!$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$arr =  _tovarValToList($arr);

	$send = '<table id="tsev">';
	$n = 1;
	foreach($arr as $r)
		$send .=
			'<tr><td class="n r">'.($n++).
				'<td>'.$r['tovar_set'].
				'<td class="r">'.$r['count'].' ��.';

	$send .= '</table>';

	return 	'<tr><td class="label topi">'.$z['zpu'][11]['name'].':<td>'.$send;
}
function _zayavTovarValToList($arr) {//������ ���������� ������� ��� ���������� � ������
	$sql = "SELECT *
			FROM `_zayav_tovar`
			WHERE `zayav_id` IN ("._idsGet($arr).")
			ORDER BY `id`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return $arr;

	foreach($arr as $r)
		$arr[$r['id']]['tovar_report'] = array();
	
	$spisok =  _tovarValToList($spisok);

	foreach($spisok as $r)
		$arr[$r['zayav_id']]['tovar_report'][] = $r['tovar_name'].': '.$r['count'].' ��.';

	foreach($arr as $r)
		$arr[$r['id']]['tovar_report'] = implode("\n", $r['tovar_report']);

	return $arr;
}



/* ��������� */
function _cartridgeName($item_id) {
	if(!defined('CARTRIDGE_NAME_LOADED')) {
		$key = CACHE_PREFIX.'cartridge';
		$arr = xcache_get($key);
		if(empty($arr)) {
			$sql = "SELECT `id`,`name` FROM `_setup_cartridge`";
			$arr = query_ass($sql, GLOBAL_MYSQL_CONNECT);
			xcache_set($key, $arr, 86400);
		}
		foreach($arr as $id => $name)
			define('CARTRIDGE_NAME_'.$id, $name);
		define('CARTRIDGE_NAME_LOADED', true);
	}
	return constant('CARTRIDGE_NAME_'.$item_id);
}
function _cartridgeType($type_id=0) {
	$arr = array(
		1 => '��������',
		2 => '��������'
	);
	return $type_id ? $arr[$type_id] : $arr;
}

function _zayavInfoCartridge($z) {
	if(!isset($z['zpu'][23]))
		return '';
	return
	'<div id="zayav-cartridge">'.
		'<div class="headBlue but">'.
			'������ ����������'.
			'<button class="vk small" onclick="_zayavCartridgeAdd()">�������� ���������</button>'.
		'</div>'.
		'<div id="zc-spisok">'._zayavInfoCartridge_spisok($z['id']).'</div>'.
	'</div>';
}
function _zayavInfoCartridge_spisok($zayav_id) {//������ ���������� � ���� �� ������
	$sql = "SELECT *
 			FROM `_zayav_cartridge`
 			WHERE `zayav_id`=".$zayav_id."
 			ORDER BY `id`";
	$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	$spisok = _schetValToList($spisok);

	$send = '<table class="_spisok">'.
		'<tr>'.
			'<th>'.
			'<th>������������'.
			'<th>���������'.
			'<th>����<br />����������'.
			'<th>����������'.
			'<th>'.
			'<th>'._check('check_all');

	$n = 1;
	foreach($spisok as $r) {
		$prim = array();
		if($r['filling'])
			$prim[] = '���������';
		if($r['restore'])
			$prim[] = '������������';
		if($r['chip'])
			$prim[] = '������ ���';
		$prim = !empty($prim) ? implode(', ', $prim) : '';
		$prim .= ($prim && $r['prim'] ? ', ' : '').'<u>'.$r['prim'].'</u>';

		$ready = $r['filling'] || $r['restore'] || $r['chip'];

		$send .=
			'<tr val="'.$r['id'].'"'.($ready ? ' class="ready"' : '').'>'.
				'<td class="n">'.($n++).
				'<td class="cart-name"><b>'._cartridgeName($r['cartridge_id']).'</b>'.
				'<td class="cost">'.(_cena($r['cost']) || $ready ? _cena($r['cost']) : '').
				'<td class="dtime">'.($r['dtime_ready'] != '0000-00-00 00:00:00' ? FullDataTime($r['dtime_ready']) : '').
				'<td class="cart-prim">'.$prim.
				'<td class="ed">'.
					($r['schet_id'] ?
						'<div class="nomer">'.$r['schet_nomer'].'</div>'
						:
						'<div class="img_edit cart-edit'._tooltip('��������', -33).'</div>'.
						'<div class="img_del cart-del'._tooltip('�������', -29).'</div>'.
						'<input type="hidden" class="cart_id" value="'.$r['cartridge_id'].'" />'.
						'<input type="hidden" class="filling" value="'.$r['filling'].'" />'.
						'<input type="hidden" class="restore" value="'.$r['restore'].'" />'.
						'<input type="hidden" class="chip" value="'.$r['chip'].'" />'
					).
				'<td class="ch">'.($ready && !$r['schet_id'] ? _check('ch'.$r['id']) : '');

	}

	$send .= '</table>';

	return $send;
}
function _zayavInfoCartridgeForSchet($ids) {//��������� ������ ���������� ��� ���������� � ����
	$sql = "SELECT *
			FROM `_zayav_cartridge`
			WHERE `id` IN (".$ids.")
			  AND (`filling` OR `restore` OR `chip`)
			  AND `cost`
			  AND !`schet_id`
			ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$schet = array();
	$n = 1;
	while($r = mysql_fetch_assoc($q)) {
		$same = 0;//��� ����� �����, � ������� ����� ������� ����������
		foreach($schet as $sn => $unit) {
			$diff = 0; // ���� �������� �� ����������
			foreach($unit as $key => $val) {
				if($key == 'count')
					continue;
				if($r[$key] != $val) {
					$diff = 1;
					break;
				}
			}
			if(!$diff) { //���� �������� ���, �� ����������� ������ � �����
				$same = $sn;
				break;
			}
		}

		if($same)
			$schet[$same]['count']++;
		else {
			$schet[$n] = array(
				'cartridge_id' => $r['cartridge_id'],
				'filling' => $r['filling'],
				'restore' => $r['restore'],
				'chip' => $r['chip'],
				'cost' => $r['cost'],
				'prim' => $r['prim'],
				'count' => 1
			);
			$n++;
		}
	}

	$spisok = array();
	foreach($schet as $r) {
		$prim = array();
		if($r['filling'])
			$prim[] = '��������';
		if($r['restore'])
			$prim[] = '��������������';
		if($r['chip'])
			$prim[] = '������ ���� �';

		$txt = implode(', ', $prim).' ��������� '._cartridgeName($r['cartridge_id']).($r['prim'] ? ', '.$r['prim'] : '');
		$txt = mb_ucfirst($txt);

		$spisok[] = array(
			'name' => utf8($txt),
			'count' => $r['count'],
			'cost' => $r['cost'],
			'readonly' => 1
		);
	}
	return $spisok;
}






/* ��������� ������ */
function _zayavSrokCalendar($v=array()) {
	// ����������:
	//      day
	//      mon
	//      zayav_spisok

	//����: ������ ���� ��� ���
	define('SROK_NOSEL', empty($v['day']) || $v['day'] == '0000-00-00' || !preg_match(REGEXP_DATE, $v['day']));

	//���� ���������� ��� � ������� 2016-04-21
	define('SROK_DAY', SROK_NOSEL ? '0000-00-00' : $v['day']);

	//������������ �����. ���� ������ ����, �� ������������ �����, � ������� ���� ����
	$mon = SROK_NOSEL ? strftime('%Y-%m') : substr(SROK_DAY, 0, 7);
	define('SROK_MON', empty($v['mon']) ? $mon : $v['mon']);

	//������ ��������� �� ������ ������.
	define('SROK_ZS', empty($v['zayav_spisok']) ? 0 : 1);

	$service_id = _num(@$v['service_id']);
	$executer_id = _num(@$v['executer_id']);

	$day = SROK_MON.'-01';
	$ex = explode('-', $day);
	$SHOW_YEAR = $ex[0];
	$SHOW_MON = $ex[1];

	$back = $SHOW_MON - 1;
	$back = !$back ? ($SHOW_YEAR - 1).'-12' : $SHOW_YEAR.'-'.($back < 10 ? 0 : '').$back;
	$next = $SHOW_MON + 1;
	$next = $next > 12 ? ($SHOW_YEAR + 1).'-01' : $SHOW_YEAR.'-'.($next < 10 ? 0 : '').$next;

	$sql = "SELECT COUNT(`id`)
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `service_id`=".$service_id."
			  AND !`deleted`
			  AND `status_id` IN ("._zayavStatus('srok_ids').")
			  AND `srok`!='0000-00-00'
			  AND `srok`<'".$day."'
			  ".($executer_id ? " AND `executer_id`=".$executer_id : '');
	$countBack = query_value($sql, GLOBAL_MYSQL_CONNECT);

	$sql = "SELECT COUNT(`id`)
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `service_id`=".$service_id."
			  AND !`deleted`
			  AND `status_id` IN ("._zayavStatus('srok_ids').")
			  AND `srok`!='0000-00-00'
			  AND `srok`>'".SROK_MON."-31'
			  ".($executer_id ? " AND `executer_id`=".$executer_id : '');
	$countNext = query_value($sql, GLOBAL_MYSQL_CONNECT);

	$send =
		'<div id="zayav-srok-calendar">'.
			'<table class="filter bs10">'.
				'<tr><td class="label">�����������:'.
					'<td><input type=hidden id="fc-executer_id" value="'.$executer_id.'" />'.
			'</table>'.
			'<table id="fc-head">'.
				'<tr><td class="ch" val="'.$back.'">&laquo;'.
						($countBack ? '<tt>'.$countBack.'</tt>' : '').
					'<td><span>'._monthDef($SHOW_MON).' '.$SHOW_YEAR.'</span> '.
					'<td class="ch r" val="'.$next.'">'.
						($countNext ? '<tt>'.$countNext.'</tt>' : '').
						'&raquo;'.
			'</table>'.
			'<table id="fc-mon">'.
				'<tr id="week-name">'.
					'<td>��<td>��<td>��<td>��<td>��<td>��<td>��';

	$sql = "SELECT
				DATE_FORMAT(`srok`,'%Y-%m-%d') AS `day`,
				COUNT(`id`) AS `count`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `service_id`=".$service_id."
			  AND !`deleted`
			  AND `status_id` IN ("._zayavStatus('srok_ids').")
			  AND `srok` LIKE ('".SROK_MON."%')
			  ".($executer_id ? " AND `executer_id`=".$executer_id : '')."
			GROUP BY DATE_FORMAT(`srok`,'%d')";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$days = array();
	while($r = mysql_fetch_assoc($q))
		$days[$r['day']] = $r['count'];

	$unix = strtotime($day);
	$dayCount = date('t', $unix);   // ���������� ���� � ������
	$week = date('w', $unix);       // ����� ������� ��� ������
	if(!$week)
		$week = 7;

	$send .= '<tr>'.($week - 1 ? '<td colspan="'.($week - 1).'">' : '');

	for($n = 1; $n <= $dayCount; $n++) {
		$day = SROK_MON.'-'.($n < 10 ? '0' : '').$n;
		$cur = TODAY == $day ? ' cur' : '';
		$sel = SROK_DAY == $day ? ' sel' : '';
		$old = $unix + $n * 86400 <= TODAY_UNIXTIME ? ' old' : '';
		$val = $old ? '' : ' val="'.$day.'"';
		$send .=
			'<td class="d '.$cur.$old.$sel.'"'.$val.'>'.
				($cur ? '<u>'.$n.'</u>' : $n).
				(isset($days[$day]) ? ': <b'.($old && SROK_ZS ? ' class="fc-old-sel" val="'.$day.'"' : '').'>'.$days[$day].'</b>' : '');
		$week++;
		if($week > 7)
			$week = 1;
		if($week == 1 && $n < $dayCount)
			$send .= '<tr>';
	}
	$send .= '</table>'.
			(SROK_ZS && !SROK_NOSEL ? '<div id="fc-cancel" val="0000-00-00">���� �� ������</div>' : '').
		'</div>';

	return $send;
}






/* --- ������� �� ������ --- */
function _zayavExpense($id=false, $i='name') {//��������� �������� ������ �� ����
	$key = CACHE_PREFIX.'zayav_expense'.APP_ID;
	$arr = xcache_get($key);
	if(empty($arr)) {
		$sql = "SELECT
					`id`,
					`name`,
					`dop`
				FROM `_zayav_expense_category`
				WHERE `app_id`=".APP_ID."
				ORDER BY `sort`";
		$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT);
		xcache_set($key, $arr, 86400);
	}

	if($i == 'sort')//������������� ������ ������� ���������
		return array_keys($arr);

	//��� ���������
	if($id === false)
		return $arr;

	//������������ id
	if(!_num($id))
		return _cacheErr('������������ id ������� �� ������', $id);

	//����������� id
	if(!isset($arr[$id]))
		return _cacheErr('����������� id ������� �� ������', $id);

	//������� ������ ���������� ���������
	if($i == 'all')
		return $arr[$id];

	//����������� ����
	if(!isset($arr[$id][$i]))
		return _cacheErr('����������� ���� ������� �� ������', $i);

	return $arr[$id][$i];
}
function _zayavExpenseDop($id=false) {//�������������� ������� ��� ��������� ������� �� ������
	$arr =  array(
		0 => '���',
		1 => '��������',
		2 => '���������',
		3 => '�����',
		4 => '����'
	);
	return $id !== false ? $arr[$id] : $arr;
}
function _zayav_expense($zayav_id) {//������� �������� �� ������ � ���������� � ������
	return
	'<div id="_zayav-expense">'.
		_zayav_expense_spisok($zayav_id).
		_zayav_bonus_spisok($zayav_id).
	'</div>';
}
function _zayav_expense_spisok($zayav_id, $insert_id=0) {//������� �������� �� ������ � ���������� � ������
	$sql = "SELECT *
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `zayav_id`=".$zayav_id."
			ORDER BY `id`";
	$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	if(empty($arr))
		return '';

	$arr = _zayav_expense_sort($arr);
	$arr = _attachValToList($arr);
	$arr = _tovarValToList($arr);

	//����� ���������� �� ������
	$sql = "SELECT SUM(`sum`)
			FROM `_money_accrual`
			WHERE `app_id`=".APP_ID."
			  AND `zayav_id`=".$zayav_id."
			  AND !`deleted`";
	$accrual_sum = query_value($sql, GLOBAL_MYSQL_CONNECT);

	$send =
		'<div class="headBlue but">'.
			'������� �� ������'.
			'<button class="vk small" onclick="_zayavExpenseEdit()">�������� ������</button>'.
		'</div>'.
		'<h1>'.($accrual_sum ? '����� ����� ����������: <b>'.round($accrual_sum, 2).'</b> ���.' : '���������� ���.').'</h1>';

	$expense_sum = 0;
	$send .= '<table>';
	foreach($arr as $r) {
		$sum = _cena($r['sum']);
		$expense_sum += $sum;
		$ze = _zayavExpense($r['category_id'], 'all');

		$dop = '';
		$count = 0;
		switch($ze['dop']) {
			case 1: $dop = $r['txt']; break;
			case 2:
				if($r['worker_id'])
					$dop = '<a class="go-report-salary" val="'.$r['worker_id'].':'.$r['year'].':'.$r['mon'].':'.$r['id'].'">'.
								_viewer($r['worker_id'], 'viewer_name').
							'</a>';
				break;
			case 3:
				if($r['tovar_id'])
					$dop = $r['tovar_set'];
				$count = $r['tovar_avai_count'];
				break;
			case 4:
				$dop = !$r['attach_id'] && $r['txt'] ?  $r['txt'] : $r['attach_link'];
				break;
		}

		$send .=
			'<tr class="l'.($insert_id == $r['id'] ? ' inserted' : '').'">'.
				'<td class="name">'.$ze['name'].
				'<td'.(!$count ? ' colspan="2"' : '').'>'.$dop.
	  ($count ? '<td class="count">'.$count : '').
				'<td class="sum">'.
					'<em>'._sumSpace($sum).' �.</em>'.
					'<div val="'.$r['id'].'" class="img_del m15'._tooltip('�������', -46, 'r').'</div>';
	}

	$ost = $accrual_sum - $expense_sum;
	$send .= '<tr><td colspan="3" class="r">����:<td class="sum"><b>'._sumSpace($expense_sum).'</b> �.'.
			 '<tr><td colspan="3" class="r">�������:<td class="sum '.($ost > 0 ? ' plus' : 'minus').'">'._sumSpace($ost).' �.'.
			'</table>';

	return $send;
}
function _zayav_expense_sort($arr) {
	$send = array();
	foreach(_zayavExpense(0, 'sort') as $i)
		foreach($arr as $id => $r)
			if($i == $r['category_id'])
				$send[$id] = $r;
	return $send;
}
function _zayav_bonus_spisok($zayav_id) {
	$sql = "SELECT *
			FROM `_salary_bonus`
			WHERE `app_id`=".APP_ID."
			  AND `zayav_id`=".$zayav_id;
	if(!$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$send = '<table class="ze-spisok">';
	foreach($arr as $r) {
		$send .=
			'<tr><td class="name">����� '._cena($r['procent']).'%'.
				'<td><a class="go-report-salary" val="'.$r['worker_id'].':'.$r['year'].':'.$r['mon'].':'.$r['id'].'">'.
						_viewer($r['worker_id'], 'viewer_name').
					'</a>'.
				'<td class="sum">'._cena($r['sum']).' �.';
	}

	$send .= '</table>';

	return $send;
}






/* ���� ������������ */
function _service($i=false, $id=0) {
	$key = CACHE_PREFIX.'service'.APP_ID;
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					`id`,
					`name`
				FROM `_zayav_service`
				WHERE `app_id`=".APP_ID."
				ORDER BY `id`";
		if($arr = query_arr($sql, GLOBAL_MYSQL_CONNECT)) {
			foreach($arr as $k => $r) {
				$arr[$k]['active'] = 0;
				$arr[$k]['const'] = array();
			}
			$arr = _serviceActiveSet($arr);
		} else
			$arr[0] = array(
				'id' => 0,
				'name' => '',
				'active' => 0
			);
		xcache_set($key, $arr, 86400);
	}

	if($i == 'menu')
		return _serviceMenu($arr);

	if($i == 'count')
		return count($arr);

	if($i == 'active_count')
		return _serviceActiveCount($arr);

	if($i == 'current')
		return _serviceCurrentId($arr, $id);

	if($i == 'js')
		return _serviceActiveJs($arr);

	if($i == 'js_client')
		return _serviceActiveJsClient($arr, $id);

	if($i == 'const_arr')
		return _serviceConstArr($arr[$id]['const']);

	if($i == 'name') {
		if(!$id)
			return '';
		return $arr[$id]['name'];
	}

	return false;
}
function _serviceMenu($arr) {//���� ��� ������ ������
	if(_serviceActiveCount($arr) < 2)
		return '';

	$id = _serviceCurrentId($arr);

	$link = '';
	foreach(_serviceActive($arr) as $r) {
		$sel = $r['id'] == $id ? ' sel' : '';
		$link .= '<a href="'.URL.'&p=zayav&type_id='.$r['id'].'" class="link'.$sel.'">'.$r['name'].'</a>';
	}

	return '<div id="dopLinks">'.$link.'</div>';
}
function _serviceCurrentId($spisok, $type_id=0) {//��������� �������� type_id ������
	if(!$spisok)
		return 0;

	//���� ���� ������ ���� ��� ������������, ����������� ���, �� �����, ������� ��� ���
	if(count($spisok) == 1)
		return key($spisok);

	if(!$type_id)
		$spisok = _serviceActive($spisok);

	//���� ����� ������������ ������ ������ � �� ���� �� �������, �� ������� � ��������� ����� ������������
	if(!$spisok)
		header('Location:'.URL.'&p=setup&d=service');

	$cookie_key = COOKIE_PREFIX.'zayav-type';

	if($type_id)
		foreach($spisok as $r)
			if($r['id'] == $type_id) {
				setcookie($cookie_key, $type_id, time() + 3600, '/');
				return $type_id;
			}

	reset($spisok);
	if(!$id = _num(@$_GET['type_id'])) {
		if(_num(@$_COOKIE[$cookie_key]))
			foreach($spisok as $r)
				if($r['id'] == $_COOKIE[$cookie_key])
					return $_COOKIE[$cookie_key];
		reset($spisok);
		return key($spisok);
	}

	foreach($spisok as $r)
		if($r['id'] == $id) {
			setcookie($cookie_key, $id, time() + 3600, '/');
			return $id;
		}

	reset($spisok);
	return key($spisok);
}
function _serviceActiveSet($arr) {//������� �������� ����� ������������
	$sql = "SELECT `service_id`
			FROM `_zayav_service_use`
			WHERE `app_id`=".APP_ID;
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$arr[$r['service_id']]['active'] = 1;
	return $arr;
}
function _serviceActive($arr) {//������� �������� ����� ������������
	$send = array();
	foreach($arr as $r)
		if($r['active'])
			$send[$r['id']] = $r;
	return $send;
}
function _serviceActiveCount($arr) {//���������� �������� ����� ������������
	$count = 0;
	foreach($arr as $r)
		$count += $r['active'];
	return $count;
}
function _serviceActiveJs($arr) {//������ �������� ����� ������������ � ������� JS - ������������� ������
	$send = array();
	foreach(_serviceActive($arr) as $r)
		$send[$r['id']] = $r['name'];
	return _assJson($send);
}
function _serviceActiveJsClient($arr, $client_id) {//������ ����� ������������, ������� �������������� � ������� �������. � ������� JS - ������������� ������
	if(count($arr) < 2)
		return '{}';

	$ass = array();
	foreach($arr as $r)
		$ass[$r['id']] = $r['name'];

	$send = array();
	$sql = "SELECT
				DISTINCT `service_id`,
				COUNT(`id`) `count`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `client_id`=".$client_id."
			GROUP BY `service_id`
			ORDER BY `service_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$send[$r['service_id']] = $ass[$r['service_id']].'<em>'.$r['count'].'</em>';

	return _assJson($send);
}
function _serviceConstArr($arr) {//��������� �������������� ������� ��������
	$send = array();
	foreach($arr as $key => $val)
		$send[$key] = $val;
	return $send;
}






/* ������ �� ������� */
function _zayav_report() {
	$filter = _zayav_reportFilter();
	return
	'<div id="zayav-report">'.
		_zayav_report_filter($filter['period']).
		'<div id="status-count">'._zayav_report_status_count($filter).'</div>'.
		'<div id="executer-count">'._zayav_report_executer($filter).'</div>'.
		_zayav_report_cols_set().
		'<div id="spisok">'._zayav_report_spisok().'</div>'.
	'</div>';
}
function _zayav_report_status_count($v) {//������� � ����������� ������
	$filter = _zayav_reportFilter($v);

	$cond = "`app_id`=".APP_ID;
	$cond .= _period($filter['period'], 'sql');

	$sql = "SELECT
				`status_id`,
				COUNT(`id`)
			FROM `_zayav`
			WHERE ".$cond."
			GROUP BY `status_id`
			ORDER BY `status_id`";
	if(!$spisok = query_ass($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$all = 0;
	$send = '';
//	foreach($spisok as $id => $r) {
	foreach(_zayavStatus('all') as $id => $r) {
		if(!isset($spisok[$id]))
			continue;
		$send .= '<div'._zayavStatus($id, 'bg').' class="cub'._tooltip(_zayavStatus($id), 2, 'l').$spisok[$id].'</div>';
		$all += $spisok[$id];
	}

	return
	'<div class="cub all">'.
		'����� <b>'.$all.'</b> ����'._end($all, '��', '��', '��').':'.
	'</div>'.
	$send;
}
function _zayav_report_executer($v) {//����������� � ����������� ������ � ������ ������������ �����
	$ass = _idsAss(_viewer(VIEWER_ID, 'zayav_report_cols_show'));
	if(empty($ass[7]))
		return '';

	$filter = _zayav_reportFilter($v);

	$cond = "`app_id`=".APP_ID;
	$cond .= _period($filter['period'], 'sql');

	$sql = "SELECT `id`
			FROM `_zayav`
			WHERE ".$cond;
	if(!$zayav_ids = query_ids($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$sql = "SELECT
				`worker_id` `id`,
				COUNT(`id`) `count`,
				SUM(`sum`) `sum`
			FROM `_zayav_expense`
			WHERE `zayav_id` IN (".$zayav_ids.")
			  AND `worker_id`
			GROUP BY `worker_id`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$summa = 0;
	$send =
		'<div class="headName">���������� �� ������� ����������</div>'.
		'<div class="_info">������ ������ ����� ������ � ���������� �� ��� �� ��������� ������ �������, ���� ���� ���������� ���� ����������� � ������ ������.</div>'.
		'<table class="_spisok">'.
			'<tr><th>���������'.
				'<th>������'.
				'<th>���������� �/�';
	foreach($spisok as $id => $r) {
		$send .= '<tr><td>'._viewer($id, 'viewer_name').
					 '<td class="center">'.$r['count'].
					 '<td class="r">'._sumSpace($r['sum']).' ���.';

		$summa += $r['sum'];
	}

	$send .= '</table>';

	return $send;
}
function _zayav_report_filter($sel) {
	return
	'<div id="filter">'.
		_calendarFilter(array(
			'days' => _zayav_report_days(),
			'func' => '_zayav_report_days',
			'sel' => $sel
		)).
		(APP_ID == 3978722 || SA ?
			'<br /><a href="'.URL.'&p=print&d=erm">����� XLS �� ������� �����</a>'
		: '').
	'</div>';
}
function _zayav_report_days($mon=0) {//������� ���� � ���������, � ������� ��������� ����� ������
	$sql = "SELECT DATE_FORMAT(`dtime_add`,'%Y-%m-%d') AS `day`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `dtime_add` LIKE ('".($mon ? $mon : strftime('%Y-%m'))."%')
			GROUP BY DATE_FORMAT(`dtime_add`,'%d')";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$days = array();
	while($r = mysql_fetch_assoc($q))
		$days[$r['day']] = 1;
	return $days;
}
function _zayav_report_cols_set() {
	$ass = _idsAss(_viewer(VIEWER_ID, 'zayav_report_cols_show'));
	$spisok = '';
	foreach(_zayav_report_cols() as $id => $r) {
		$spisok .= _checkNew(array(
						'id' => 'ch'.$id,
						'txt' => $r,
						'value' => $id == 1 ? 1 : isset($ass[$id]),
						'light' => 1,
						'disabled' => $id == 1,
						'block' => 1
					));
	}
	return
	'<div class="cols-div">'.
		'<a>��������� ����������� �������</a>'.
		'<div id="sp">'.
			'<a>��������� ����������� �������</a>'.
			$spisok.
		'</div>'.
	'</div>';
}
function _zayav_report_cols($id=false) {//������������ ���� ������ ������
	$arr = array(
		1 => '����',
		2 => '����� ������',
		3 => '��������',
		4 => '� ���.',
		5 => '������',
		6 => '�����',
		7 => '�����������',
		8 => '���������',
		9 => '���������',
		10 => '��������',
		11 => '������',
		12 => '�������',
		13 => '�������'
	);
	if($id === false)
		return $arr;

	if(!isset($arr[$id]))
		return '';

	return $arr[$id];
}
function _zayav_reportFilter($v=array()) {
	$send = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 500,
		'period' => _period(empty($v['period']) ? strftime('%Y-%m') : $v['period'])
//		'period' => _period(empty($v['period']) ? '2016-04' : $v['period'])
	);
	return $send;
}
function _zayav_report_spisok($v=array()) {
	$filter = _zayav_reportFilter($v);
	$filter = _filterJs('ZAYAV_REPORT', $filter);

	$cond = "`app_id`=".APP_ID;
	$cond .= _period($filter['period'], 'sql');

	$sql = "SELECT *
			FROM `_zayav`
			WHERE ".$cond."
			ORDER BY `id`";
	$zayav = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	$zayav = _clientValToList($zayav);
	$zayav = _dogovorValToList($zayav);
	$zayav = _zayavExecuterToList($zayav);

	$send =
		$filter['js'].
		'<table class="_spisok"><tr>';

	$colsAss = _idsAss(_viewer(VIEWER_ID, 'zayav_report_cols_show'));
	foreach($colsAss as $id => $r)
		$send .= '<th>'._zayav_report_cols($id);

	foreach($zayav as $id => $r) {
		$sum_cost = _cena($r['sum_cost']) ? _sumSpace($r['sum_cost']) : '';
		$sum_accrual = _cena($r['sum_accrual']) ? _sumSpace($r['sum_accrual']) : '';
		$sum_pay = _cena($r['sum_pay']) ? _sumSpace($r['sum_pay']) : '';
		$sum_dolg = _cena($r['sum_dolg'], 1) ? _sumSpace($r['sum_dolg']) : '';
		$sum_expense = _cena($r['sum_expense']) ? _sumSpace($r['sum_expense']) : '';
		$sum_profit = _cena($r['sum_profit'], 1) ? _sumSpace($r['sum_profit']) : '';

		$send .= '<tr class="unit">'.
			'<td'._zayavStatus($r['status_id'], 'bg').' class="dtime'._tooltip(_zayavStatus($r['status_id']), 10, 'l').FullData($r['dtime_add'], 1, 1);
		if(isset($colsAss[2]))
			$send .= '<td class="nomer r"><a href="'.URL.'&p=zayav&d=info&id='.$id.'">#'.$r['nomer'].'</a>';
		if(isset($colsAss[3]))
			$send .= '<td class="name">'.$r['name'];
		if(isset($colsAss[4]))
			$send .= '<td class="dog r">'.($r['dogovor_id'] ? $r['dogovor_min'] : '');
		if(isset($colsAss[5]))
			$send .= '<td>'.$r['client_link'];
		if(isset($colsAss[6]))
			$send .= '<td class="adres">'.$r['adres'];
		if(isset($colsAss[7]))
			$send .= '<td class="executer">'.$r['executer_spisok'];
		if(isset($colsAss[8]))
			$send .= '<td class="sum-cost r">'.$sum_cost;
		if(isset($colsAss[9]))
			$send .= '<td class="sum-accrual r">'.$sum_accrual;
		if(isset($colsAss[10]))
			$send .= '<td class="sum-pay r">'.$sum_pay;
		if(isset($colsAss[11]))
			$send .= '<td class="sum-dolg r'.($r['sum_dolg'] < 0 ? ' minus' : '').'">'.$sum_dolg;
		if(isset($colsAss[12]))
			$send .= '<td class="sum-expense r">'.$sum_expense;
		if(isset($colsAss[13]))
			$send .= '<td class="sum-profit r'.($r['sum_profit'] < 0 ? ' minus' : '').'">'.$sum_profit;
	}
	$send .= _next($filter + array(
		'all' => count($zayav),
		'tr' => 1
	));

	return $send;
}






















