<?php
function _zayav() {
	if(function_exists('zayavCase'))
		return zayavCase();
	if(@$_GET['d'] == 'info')
		return _zayav_info();
	return _zayav_list(_hashFilter('zayav'._service('current')));
}

function _zayavStatus($id=false, $i='name') {
	$name = array(
		0 => '����� ������',
		1 => '������� ����������',
		2 => '���������',
		3 => '��������� �� �������'
	);
	$color = array(
		0 => 'ffffff',
		1 => 'E8E8FF',
		2 => 'CCFFCC',
		3 => 'FFDDDD'
	);

	if($id === false)
		return $name;

	//����������� id �������
	if(!isset($name[$id]))
		return '<span class="red">����������� id �������: <b>'.$id.'</b></span>';

	switch($i) {
		case 'name': return $name[$id];
		case 'color': return $color[$id];
		case 'bg': return ' style="background-color:#'.$color[$id].'"';
		default: return '<span class="red">����������� ���� �������: <b>'.$i.'</b></span>';
	}
}
function _zayavStatusButton($z, $class='status') {
	if($z['status_day'] == '0000-00-00')
		$z['status_day'] = $z['status_dtime'];
	return
		'<div id="zayav-status-button">'.
			'<h1'._zayavStatus($z['status'], 'bg').' class="'.$class.'">'.
				_zayavStatus($z['status']).' '.
				($z['status'] == 2 ? FullData($z['status_'.(ZAYAV_INFO_STATUS_DAY ? 'day' : 'dtime')], 1) : '').
			'</h1>'.
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
			  AND `ws_id`=".WS_ID."
			  AND `id` IN (".implode(',', array_keys($ids)).")";
	$zayav = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	if(!isset($r['client_phone'])) {
		foreach($zayav as $r)
			foreach($arrIds[$r['id']] as $id)
				$arr[$id] += array('client_id' => $r['client_id']);
		$arr = _clientValToList($arr);
	}

	foreach($zayav as $r) {
		foreach($arrIds[$r['id']] as $id) {
			$dolg = $r['sum_accrual'] - $r['sum_pay'];
			$arr[$id] += array(
				'zayav_name' => $r['name'],
				'zayav_link' =>
					'<a href="'.URL.'&p=zayav&d=info&id='.$r['id'].'" class="zayav_link">'.
						'<span'.($r['deleted'] ? ' class="deleted"' : '').'>�'.$r['nomer'].'</span>'.
						'<div class="tooltip">'._zayavTooltip($r, $arr[$id]).'</div>'.
					'</a>',
				'zayav_color' => //��������� ������ �� ��������� �������
					'<a href="'.URL.'&p=zayav&d=info&id='.$r['id'].'" class="zayav_link color"'._zayavStatus($r['status'], 'bg').'>'.
						'�'.$r['nomer'].
						'<div class="tooltip">'._zayavTooltip($r, $arr[$id]).'</div>'.
					'</a>',
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
					'<div'._zayavStatus($z['status'], 'bg').
						' class="tstat'._tooltip('������ ������: '._zayavStatus($z['status']), -7, 'l').
					'</div>'.
					'<b>'.$z['name'].'</b>'.
			'<table>'.
				'<tr><td class="label top">������:'.
					'<td>'.$v['client_name'].
						($v['client_phone'] ? '<br />'.$v['client_phone'] : '').
				'<tr><td class="label">������:'.
					'<td><span class="bl" style=color:#'.($v['client_balans'] < 0 ? 'A00' : '090').'>'.$v['client_balans'].'</span>'.
			'</table>'.
		'</table>';
}
function _zayavCountToClient($spisok) {//������������ ����������� � ����������� ������ � ������ ��������
	$ids = implode(',', array_keys($spisok));

	//������, ��������� ����������
	$sql = "SELECT
				`client_id` AS `id`,
				COUNT(`id`) AS `count`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `status`=1
			  AND !`deleted`
			  AND `client_id` IN (".$ids.")
			GROUP BY `client_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']]['zayav_wait'] = $r['count'];

	//����������� ������
	$sql = "SELECT
				`client_id` AS `id`,
				COUNT(`id`) AS `count`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `status`=2
			  AND !`deleted`
			  AND `client_id` IN (".$ids.")
			GROUP BY `client_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']]['zayav_ready'] = $r['count'];

	//��������� ������
	$sql = "SELECT
				`client_id` AS `id`,
				COUNT(`id`) AS `count`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `status`=3
			  AND !`deleted`
			  AND `client_id` IN (".$ids.")
			GROUP BY `client_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']]['zayav_fail'] = $r['count'];

	return $spisok;
}
function _zayavStatusChange($zayav_id, $status) {
	$z = _zayavQuery($zayav_id);

	if($z['status'] != $status) {
		$sql = "UPDATE `_zayav`
				SET `status`=".$status.",
					`status_dtime`=CURRENT_TIMESTAMP
				WHERE `id`=".$zayav_id;
		query($sql, GLOBAL_MYSQL_CONNECT);
		_history(array(
			'type_id' => 71,
			'client_id' => $z['client_id'],
			'zayav_id' => $zayav_id,
			'v1' => $z['status'],
			'v2' => $status,
		));
	}
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
		'diagnost' => 0,
		'diff' => 0,
		'executer_id' => 0,
		'product_id' => 0,
		'product_sub_id' => 0,
		'zpzakaz' => 0,
		'device' => 0,
		'vendor' => 0,
		'model' => 0,
		'place' => 0,
		'paytype' => 0,
		'noschet' => 0,
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
		'diagnost' => _bool(@$v['diagnost']),
		'diff' => _bool(@$v['diff']),
		'executer_id' => intval(@$v['executer_id']),
		'product_id' => _num(@$v['product_id']),
		'product_sub_id' => _num(@$v['product_sub_id']),
		'zpzakaz' => _num(@$v['zpzakaz']),
		'device' => @$v['device'],
		'vendor' => _num(@$v['vendor']),
		'model' => _num(@$v['model']),
		'place' => _num(@$v['place']),
		'paytype' => _num(@$v['paytype']),
		'noschet' => _bool(@$v['noschet']),
		'deleted' => _bool(@$v['deleted']),
		'deleted_only' => _bool(@$v['deleted_only']),
		'clear' => ''
	);
	foreach($default as $k => $r)
		if($r != $filter[$k]) {
			$filter['clear'] = '<a class="clear">�������� ������</a>';
			break;
		}

	$filter['type_id'] = _service('current', _num(@$v['type_id']));

	return $filter;
}
function _zayav_list($v=array()) {
	$data = _zayav_spisok($v);
	$v = $data['filter'];

	$status = _zayavStatus();
	if(ZAYAV_INFO_SROK)
		$status[1] .= '<div id="srok">����: '._zayavFinish($v['finish']).'</div>';

	return
	'<div id="_zayav">'.
		_service('menu').
		'<div class="result">'.$data['result'].'</div>'.
		'<table class="tabLR">'.
			'<tr><td id="spisok">'.$data['spisok'].
				'<td class="right">'.
					'<div id="buttonCreate">'.
						'<a id="_zayav-add">����� ������</a>'.
					'</div>'.
					'<div id="find"></div>'.
					'<div class="findHead">�������</div>'.
					_radio('sort', array(1=>'�� ���� ����������',2=>'�� ���������� �������'), $v['sort']).
					_check('desc', '�������� �������', $v['desc']).
					'<div class="condLost'.(!empty($v['find']) ? ' dn' : '').'">'.
						'<div class="findHead">������ ������</div>'.
						_rightLink('status', $status, $v['status']).

  (ZAYAV_INFO_PAY_TYPE ? '<div class="findHead">������</div>'.
						  _radio('paytype', array(0=>'�� �����',1=>'��������',2=>'����������'), $v['paytype'], 1)
  : '').

 (ZAYAV_INFO_DIAGNOST ? _check('diagnost', '�����������', $v['diagnost']) : '').

(ZAYAV_FILTER_NOSCHET ? _check('noschet', '���� �� �������', $v['noschet']) : '').
   (ZAYAV_FILTER_DIFF ? _check('diff', '������������ ������', $v['diff']) : '').

  (ZAYAV_INFO_PRODUCT ? '<div class="findHead">�������</div>'.
						'<input type="hidden" id="product_id" value="'.$v['product_id'].'" />'.
						'<input type="hidden" id="product_sub_id" value="'.$v['product_sub_id'].'" />'
  : '').

 (ZAYAV_INFO_EXECUTER ? '<div class="findHead">�����������</div>'.
						'<input type="hidden" id="executer_id" value="'.$v['executer_id'].'" />'
 : '').

   (ZAYAV_INFO_DEVICE ? '<div class="findHead">����������</div>'.
						'<div id="dev"></div>'.

						'<div class="findHead">�������� ��������</div>'.
						_radio('zpzakaz', array(0=>'�� �����',1=>'��',2=>'���'), $v['zpzakaz'], 1).

						'<div class="findHead">���������� ����������</div>'.
						'<input type="hidden" id="place" value="'.$v['place'].'" />'
   : '').

		(VIEWER_ADMIN ? _check('deleted', '+ �������� ������', $v['deleted'], 1).
						'<div id="deleted-only-div"'.($v['deleted'] ? '' : ' class="dn"').'>'.
							_check('deleted_only', '������ ��������', $v['deleted_only'], 1).
						'</div>'
		: '').

					'</div>'.
		'</table>'.
	'</div>'.
	'<script type="text/javascript">'.
		'var '._service('const_js', $v['type_id']).';'.
	'</script>';

//		'var Z={'.
//			'device_ids:['._zayavBaseDeviceIds().'],'.
//			'vendor_ids:['._zayavBaseVendorIds().'],'.
//			'model_ids:['._zayavBaseModelIds().']'.
//		'};'.

}
function _zayav_spisok($v) {
	$filter = _zayavFilter($v);
	$filter = _filterJs('ZAYAV', $filter);

	_service('const_define', $filter['type_id']);

	$cond = "`app_id`=".APP_ID."
		 AND `ws_id`=".WS_ID."
		 AND `type_id`=".$filter['type_id'];
	if(!VIEWER_ADMIN)
		$cond .= " AND !`deleted`";

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
		if($filter['status']) {
			$cond .= " AND `status`=".$filter['status'];
			if($filter['status'] == 1 && $filter['finish'] != '0000-00-00')
				$cond .= " AND `day_finish`='".$filter['finish']."'";
		}
		if($filter['diagnost'])
			$cond .= " AND `status`=1 AND `diagnost`";
		if($filter['paytype'])
			$cond .= " AND `pay_type`=".$filter['paytype'];
		if($filter['noschet'])
			$cond .= " AND !`schet_count`";
		if($filter['diff'])
			$cond .= " AND `sum_accrual`-`sum_pay`>0";
		if($filter['executer_id'])
			$cond .= " AND `executer_id`=".($filter['executer_id'] < 0 ? 0 : $filter['executer_id']);
		if($filter['product_id']) {
			if($filter['product_sub_id']) {
				$sql = "SELECT `zayav_id`
						FROM `_zayav_product`
						WHERE `app_id`=".APP_ID."
						  AND `ws_id`=".WS_ID."
						  AND `product_id`=".$filter['product_id']."
						  AND `product_sub_id`=".$filter['product_sub_id'];
				$ids = query_ids($sql, GLOBAL_MYSQL_CONNECT);
				$cond .= " AND `id` IN (".$ids.")";
			} else {
				$sql = "SELECT `zayav_id`
						FROM `_zayav_product`
						WHERE `app_id`=".APP_ID."
						  AND `ws_id`=".WS_ID."
						  AND `product_id`=".$filter['product_id'];
				$ids = query_ids($sql, GLOBAL_MYSQL_CONNECT);
				$cond .= " AND `id` IN (".$ids.")";
			}
		}

		if(ZAYAV_INFO_DEVICE) {
			if($filter['zpzakaz']) {
				$sql = "SELECT `zayav_id` FROM `zp_zakaz` WHERE `ws_id`=".WS_ID;
				$ids = query_ids($sql);
				$not = $filter['zpzakaz'] == 2 ? 'NOT' : '';
				$cond .= " AND `id` ".$not." IN (".$ids.")";
			}
			if($filter['device'])
				$cond .= " AND `base_device_id` IN (".$filter['device'].")";
			if($filter['vendor'])
				$cond .= " AND `base_vendor_id`=".$filter['vendor'];
			if($filter['model'])
				$cond .= " AND `base_model_id`=".$filter['model'];
			if($filter['place'])
				$cond .= " AND `device_place`=".$filter['place'];
		}

		if(VIEWER_ADMIN) {
			if($filter['deleted']) {
				if($filter['deleted_only'])
					$cond .= " AND `deleted`";
			} else
				$cond .= " AND !`deleted`";
		}

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

	//��������� ������ ������ ��� ������� ������
	if($nomer) {
		$sql = "SELECT *
				FROM `_zayav`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `status`
				  AND `nomer`=".$nomer;
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
		$r['sum_accrual'] = round($r['sum_accrual']);
		$r['sum_pay'] = round($r['sum_pay']);
		$r['name'] = $FIND ? _findRegular($filter['find'], $r['name']) : $r['name'];

		$zayav[$r['id']] = $r;
	}

	if(!$filter['client_id'])
		$zayav = _clientValToList($zayav);

	$zayav = _dogovorValToList($zayav);
	$zayav = _zayavProductValToList($zayav);
	$zayav = _schetToZayav($zayav);
	$zayav = _zayavNote($zayav);

/*
	$images = _imageGet(array(
		'owner' => $images,
		'view' => 1
	));


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
		$diff = $r['sum_accrual'] - $r['sum_pay'];
		$diff = $diff ? ($diff > 0 ? '����' : '����').'����� '.abs($diff).' ���.' : '��������';
		$deleted = $r['deleted'] ? ' deleted' : '';
		$statusColor = $r['deleted'] ? '' : _zayavStatus($r['status'], 'bg');
		$send['spisok'] .=
			'<div class="_zayav-unit'.$deleted.'" id="u'.$id.'"'.$statusColor.' val="'.$r['id'].'">'.
				'<div class="zd">'.
					'#'.$r['nomer'].
					'<div class="date-add">'.FullData($r['dtime_add'], 1).'</div>'.
($r['status'] == 2 ? '<div class="date-ready'._tooltip('���� ����������', -40).FullData($r['status_dtime'], 1, 1).'</div>' : '').
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
   (!$filter['client_id'] ? '<tr><td class="label">������:<td>'.$r['client_go'] : '').
			 ($r['adres'] ? '<tr><td class="label top">�����:<td>'.$r['adres'] : '').
	         ($r['schet'] ? '<tr><td class="label topi">�����:<td>'.$r['schet'] : '').
				'</table>'.
				'<div class="note">'.$r['note'].'</div>'.
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
			  AND `ws_id`=".WS_ID."
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
				'dogovor_line' => '<span class="'._tooltip('�����: '._cena($r['sum']).' ���.', -3).
									'<b>�'.$r['nomer'].'</b>'.
									' �� '._dataDog($r['data_create']).
								  '</span>'
			);
		}
	}

	return $arr;
}



function _zayavQuery($zayav_id, $withDel=0) {
	$sql = "SELECT *
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  ".($withDel ? '' : ' AND !`deleted`')."
			  AND `id`=".$zayav_id;
	return query_assoc($sql, GLOBAL_MYSQL_CONNECT);
}
function _zayav_info() {
	if(!$zayav_id = _num(@$_GET['id']))
		return _err('�������� �� ����������');

	if(!$z = _zayavQuery($zayav_id, 1))
		return _err('������ �� ����������.');

	_service('const_define', $z['type_id']);
	_service('current', $z['type_id']);//��������� ���� ��� �������� �� ��������������� ������ ������

	if(!VIEWER_ADMIN && $z['deleted'])
		return _noauth('������ ������');

	$product = _zayav_product_html($zayav_id);

	$status = _zayavStatus();
	unset($status[0]);
	$history = _history(array('zayav_id'=>$zayav_id));

	$z['sum_cost'] = _cena($z['sum_cost']);
	$z['sum_accrual'] = _cena($z['sum_accrual']);
	$z['sum_pay'] = _cena($z['sum_pay']);

	//������� ���������� � ��������
	$sum_diff = round($z['sum_accrual'] - $z['sum_pay'], 2);
	$sum_diff = $sum_diff ? ($sum_diff > 0 ? '����������� ' : '����������� ').abs($sum_diff).' ���.' : '';

	return
	_attachJs(array('zayav_id'=>$zayav_id)).
	'<script type="text/javascript">'.
		'var ZI={'.
				'id:'.$zayav_id.','.
				'nomer:'.$z['nomer'].','.
				'client_id:'.$z['client_id'].','.
				'client_link:"'.addslashes(_clientVal($z['client_id'], 'link')).'",'.
				'name:"'.addslashes($z['name']).'",'.
				'about:"'.addslashes(str_replace("\n", '', $z['about'])).'",'.
				'count:'.$z['count'].','.
				'product:'._zayav_product_js($zayav_id).','.
				'status:'.$z['status'].','.
				'status_day:"'.($z['status_day'] == '0000-00-00' ? '' : $z['status_day']).'",'.
				'status_sel:'._selJson($status).','.
				'adres:"'.addslashes($z['adres']).'",'.
				'device_id:'.$z['base_device_id'].','.
				'vendor_id:'.$z['base_vendor_id'].','.
				'model_id:'.$z['base_model_id'].','.
(ZAYAV_INFO_DEVICE ? 'equip:"'.addslashes(devEquipCheck($z['base_device_id'], $z['equip'])).'",' : '').
				'imei:"'.addslashes($z['imei']).'",'.
				'serial:"'.addslashes($z['serial']).'",'.
				'color_id:'.$z['color_id'].','.
				'color_dop:'.$z['color_dop'].','.
				'diagnost:'.$z['diagnost'].','.
				'sum_cost:'.$z['sum_cost'].','.
				'pay_type:'.$z['pay_type'].','.
				'todel:'._zayavToDel($zayav_id).','.//���������� ����� ��� �������� ������
				'deleted:'.$z['deleted'].
			'},'.
			_service('const_js', $z['type_id']).','.
			'DOG={'._zayavDogovorJs($z).'};'.
			'KVIT={'.
				'dtime:"'.FullDataTime($z['dtime_add']).'",'.
(ZAYAV_INFO_DEVICE ?
				'device:"'._deviceName($z['base_device_id']).'<b>'._vendorName($z['base_vendor_id'])._modelName($z['base_model_id']).'</b>",'.
				($z['equip'] ? 'equip:"'.zayavEquipSpisok($z['equip']).'",' : '')
: '').
				'color:"'._color($z['color_id'], $z['color_dop']).'",'.
				'phone:"'._clientVal($z['client_id'], 'phone').'",'.
				'defect:"'.addslashes(str_replace("\n", ' ', _note(array('last'=>1)))).'"'.
			'};'.

	'</script>'.
	'<div id="_zayav-info">'.
		'<div id="dopLinks">'.
			'<a class="link a-page sel">����������</a>'.
			'<a class="link" id="edit">��������������</a>'.
			'<a class="link _accrual-add">���������</a>'.
			'<a class="link _income-add">������� �����</a>'.
			'<a class="link a-page">�������</a>'.
			'<div id="nz" class="'._tooltip('����� ������', -74, 'r').'#'.$z['nomer'].'</div>'.
		'</div>'.

		($z['deleted'] ? '<div id="zayav-deleted">������ �������.</div>' : '').

		'<table class="page">'.
			'<tr><td id="left">'.

					'<div class="headName">'.
						$z['name'].
						'<input type="hidden" id="zayav-action" />'.
					'</div>'.

	 ($z['about'] ? '<div class="_info">'._br($z['about']).'</div>' : '').

					'<table id="tab">'.
						'<tr><td class="label">������:<td>'._clientVal($z['client_id'], 'go').
		 ($z['count'] ? '<tr><td class="label">����������:<td><b>'.$z['count'].'</b> ��.' : '').
			($product ? '<tr><td class="label top">�������:<td>'.$product : '').
		 ($z['adres'] ? '<tr><td class="label">�����:<td>'.$z['adres'] : '').
	($z['dogovor_id'] ? '<tr><td class="label">�������:<td>'._zayavDogovor($z) : '').
	  ($z['sum_cost'] ? '<tr><td class="label">���������:<td><b>'.$z['sum_cost'].'</b> ���.' : '').
	  ($z['pay_type'] ? '<tr><td class="label">������:<td>'._payType($z['pay_type']) : '').

	 (ZAYAV_INFO_SROK ? '<tr><td class="label">����:<td>'._zayavFinish($z['day_finish']) : '').

 (ZAYAV_INFO_EXECUTER ? '<tr><td class="label">�����������:'.
							'<td id="executer_td"><input type="hidden" id="executer_id" value="'.$z['executer_id'].'" />'
					: '').

  ($z['status'] == 1 && $z['diagnost'] ?
					'<tr><td colspan="2">'._button('diagnost-ready', '������ ���������� �����������', 300)
  : '').

						'<tr><td class="label">������:<td>'._zayavStatusButton($z).

 (ZAYAV_INFO_DOCUMENT ? '<tr><td class="label">��������:<td><input type="hidden" id="attach_id" value="'.$z['attach_id'].'" />' : '').

   ($z['sum_accrual'] ? '<tr><td class="label">���������:<td><b class="acc">'._cena($z['sum_accrual']).'</b> ���.' : '').
	   ($z['sum_pay'] ? '<tr><td class="label">��������:'.
							'<td><b class="pay">'._cena($z['sum_pay']).'</b> ���.'.
				   ($sum_diff ? '<span id="sum-diff">'.$sum_diff.'</span>' : '')
		: '').
					'</table>'.

					'<div id="added">'.
						'������ '._viewerAdded($z['viewer_id_add']).' '.
						FullDataTime($z['dtime_add']).
					'</div>'.

(ZAYAV_INFO_CARTRIDGE ? zayavInfoCartridge($zayav_id) : '').
 (ZAYAV_INFO_KVIT ? zayav_kvit($zayav_id) : '').
					_zayavInfoAccrual($zayav_id).
					_zayav_expense($zayav_id).
					_remind_zayav($zayav_id).
					_zayavInfoMoney($zayav_id).
					_note().

				'<td id="right">'.
(ZAYAV_INFO_DEVICE ? zayavInfoDevice($z) : '').

		'</table>'.

		'<div class="page dn">'.
			'<div class="headName">'.$z['name'].' - ������� ��������</div>'.
			$history['spisok'].
		'</div>'.

	'</div>';
}
function _zayavToDel($zayav_id) {//����� �� ������� ������..
	if(!_zayavQuery($zayav_id))
		return 0;

	//�������� �� ������� ��������
	$sql = "SELECT COUNT(`id`)
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 0;

	//�������� �� ������� ���������
	$sql = "SELECT COUNT(`id`)
			FROM `_money_refund`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 0;

	//�������� �� ������� ����������� ���������
	$sql = "SELECT COUNT(`id`)
			FROM `_zayav_dogovor`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 0;

	//�������� �� ������� ������ �� ������
	$sql = "SELECT COUNT(`id`)
			FROM `_schet`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 0;

	//�������� �� ������� ���������� �� �����������
	$sql = "SELECT COUNT(`id`)
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
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
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$z['id'];
	if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$title = '�� '._dataDog($r['data_create']).' �. �� ����� '._cena($r['sum']).' ���.';

	return '<b class="dogn'._tooltip($title, -7, 'l').'�'.$r['nomer'].'</b> '.
			'<a href="'.LINK_DOGOVOR.$r['link'].'.doc" class="img_word'._tooltip('�����������', -41).'</a>';
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
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `id`=".$z['dogovor_id'];
	if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$sql = "SELECT `invoice_id`
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
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
		'avans_sum:'._cena($r['avans']);
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
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `id`!=".$send['id']."
			  AND `nomer`=".$send['nomer'];
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return '������: ������� � ������� <b>'.$send['nomer'].'</b> ��� ��� ��������';

	if(empty($send['fio']))
		return '������: �� ������� ��� �������';

	if($send['sum'] < $send['avans'])
		return '������: ��������� ����� �� ����� ���� ������ ����� ��������';

	if($send['avans'] && !$send['invoice_id'])
		return '������: �� ������ ����� ����� ��� ���������� �������';

	$sql = "SELECT `client_id`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
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
				  AND `ws_id`=".WS_ID."
				  AND !`deleted`
				  AND `id`=".$v;
		$v = query_assoc($sql, GLOBAL_MYSQL_CONNECT);

		$v['save'] = 1; //��������� �������

		if($v['avans']) {
			$sql = "SELECT `id`
					FROM `_money_income`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
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
		$cssFile = DOCUMENT_ROOT.'/css/dogovor.css',
		$topMargin = 1,
		$rightMargin = 2,
		$bottomMargin = 1,
		$leftMargin = 1
	);

	$v['sum'] = _cena($v['sum']);
	$v['avans'] = _cena($v['avans']);
	$dopl = $v['sum'] - $v['avans'];
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
				'��� �'._ws('name').'�<br />'.
				'���� '._ws('ogrn').'<br />'.
				'��� '._ws('inn').'<br />'.
				'��� '._ws('kpp').'<br />'.
				str_replace("\n", '<br />', _ws('adres_yur')).'<br />'.
				'���. '._ws('phone').'<br /><br />'.
				'����� �����: '._ws('adres_ofice').
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

	$doc->output($v['link'], @$v['save'] ? PATH_DOGOVOR : '');
}
function _zayavBalansUpdate($zayav_id) {//���������� ������� ������
	//����������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_accrual`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	$accrual = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//�������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	$income = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//��������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_refund`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	$refund = query_value($sql, GLOBAL_MYSQL_CONNECT);

	$income -= $refund;

	//������� ������
	$sql = "SELECT COUNT(`id`)
			FROM `_schet`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	$schet_count = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//�������
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `zayav_id`=".$zayav_id;
	$expense = query_value($sql, GLOBAL_MYSQL_CONNECT);

	$sql = "UPDATE `_zayav`
			SET `sum_accrual`=".$accrual.",
				`sum_pay`=".$income.",
				`sum_expense`=".$expense.",
				`sum_profit`=".($accrual - $expense).",
				`schet_count`=".$schet_count."
			WHERE `id`=".$zayav_id;
	query($sql, GLOBAL_MYSQL_CONNECT);
}


/* ����������� ����� ������� */
function _product($product_id=false) {//������ ������� ��� ������
	if(!defined('PRODUCT_LOADED') || $product_id === false) {
		$key = CACHE_PREFIX.'product'.WS_ID;
		if(!$arr = xcache_get($key)) {
			$sql = "SELECT `id`,`name`
					FROM `_product`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					ORDER BY `name`";
			$arr = query_ass($sql, GLOBAL_MYSQL_CONNECT);
			xcache_set($key, $arr, 86400);
		}
		if(!defined('PRODUCT_LOADED')) {
			foreach($arr as $id => $name)
				define('PRODUCT_'.$id, $name);
			define('PRODUCT_0', '');
			define('PRODUCT_LOADED', true);
		}
	}
	return $product_id !== false ? constant('PRODUCT_'.$product_id) : $arr;
}
function _productSub($product_id=false) {//������ ������� ��� ������
	if(!defined('PRODUCT_SUB_LOADED') || $product_id === false) {
		$key = CACHE_PREFIX.'product_sub'.WS_ID;
		$arr = xcache_get($key);
		if(empty($arr)) {
			$sql = "SELECT `id`,`name`
					FROM `_product_sub`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					ORDER BY `product_id`,`name`";
			$q = query($sql, GLOBAL_MYSQL_CONNECT);
			while($r = mysql_fetch_assoc($q))
				$arr[$r['id']] = $r['name'];
			xcache_set($key, $arr, 86400);
		}
		if(!defined('PRODUCT_SUB_LOADED')) {
			foreach($arr as $id => $name)
				define('PRODUCT_SUB_'.$id, $name);
			define('PRODUCT_SUB_0', '');
			define('PRODUCT_SUB_LOADED', true);
		}
	}
	return $product_id !== false ? constant('PRODUCT_SUB_'.$product_id) : $arr;
}

function _zayav_product_query($zayav_id) {//sql-������ ��� �������
	$sql = "SELECT *
			FROM `_zayav_product`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `zayav_id`=".$zayav_id."
			ORDER BY `id`";
	return query_arr($sql, GLOBAL_MYSQL_CONNECT);
}
function _zayav_product_html($zayav_id) {//������ ������� ��� ���������� � ������
	if(!$spisok = _zayav_product_query($zayav_id))
		return '';

	$send = '<table>';
	foreach($spisok as $r)
		$send .= _zayav_product_unit($r);

	$send .= '</table>';

	return $send;
}
function _zayav_product_unit($r) {
	return
	'<tr><td>'._product($r['product_id']).
			($r['product_sub_id'] ? ' '._productSub($r['product_sub_id']) : '').':'.
		'<td>&nbsp;'.$r['count'].' ��.';
}
function _zayav_product_js($zayav_id) {//������ ������� ��� ���������� � ������ � ������� JS
	if(!$spisok = _zayav_product_query($zayav_id))
		return '[[0,0,0]]';

	$send = array();
	foreach($spisok as $r)
		$send[] = '['.$r['product_id'].','.$r['product_sub_id'].','.$r['count'].']';

	return '['.implode(',', $send).']';
}
function _zayavProductValToList($arr) {//������� ������ ������� � ������ ������
	$sql = "SELECT *
			FROM `_zayav_product`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `zayav_id` IN (".implode(',', array_keys($arr)).")
			ORDER BY `id`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return $arr;

	foreach($arr as $r) {
		$arr[$r['id']]['product'] = '';
		$arr[$r['id']]['product_report'] = array();
	}


	foreach($spisok as $r) {
		$arr[$r['zayav_id']]['product'] .= _zayav_product_unit($r);
		$arr[$r['zayav_id']]['product_report'][] =
			_product($r['product_id']).
			($r['product_sub_id'] ? ' '._productSub($r['product_sub_id']) : '').': '.
			$r['count'].' ��.';
	}

	foreach($arr as $r)
		if($r['product']) {
			$arr[$r['id']]['product'] = '<table>'.$r['product'].'</table>';
			$arr[$r['id']]['product_report'] = implode("\n", $r['product_report']);
		}

	return $arr;
}

/* ���� ���������� ������ */
function _zayavFinish($day='0000-00-00') {
	return
		'<input type="hidden" id="day_finish" value="'.$day.'" />'.
		'<div class="day-finish-link">'.
			'<span>'.($day == '0000-00-00' ? '�� ������' : FullData($day, 1, 0, 1)).'</span>'.
		'</div>';
}
function _zayavFinishCalendar($selDay='0000-00-00', $mon='', $zayav_spisok=0) {
	if(!$mon)
		$mon = $selDay != '0000-00-00' ? substr($selDay, 0, 7) : strftime('%Y-%m');
	$day = $mon.'-01';
	$ex = explode('-', $day);
	$SHOW_YEAR = $ex[0];
	$SHOW_MON = $ex[1];

	$back = $SHOW_MON - 1;
	$back = !$back ? ($SHOW_YEAR - 1).'-12' : $SHOW_YEAR.'-'.($back < 10 ? 0 : '').$back;
	$next = $SHOW_MON + 1;
	$next = $next > 12 ? ($SHOW_YEAR + 1).'-01' : $SHOW_YEAR.'-'.($next < 10 ? 0 : '').$next;

	$send =
		'<div id="zayav-finish-calendar">'.
			'<table id="fc-head">'.
				'<tr><td class="ch" val="'.$back.'">&laquo;'.
					'<td><span>'._monthDef($SHOW_MON).' '.$SHOW_YEAR.'</span> '.
					'<td class="ch" val="'.$next.'">&raquo;'.
			'</table>'.
			'<table id="fc-mon">'.
				'<tr id="week-name">'.
					'<td>��<td>��<td>��<td>��<td>��<td>��<td>��';

	$sql = "SELECT
				DATE_FORMAT(`day_finish`,'%Y-%m-%d') AS `day`,
				COUNT(`id`) AS `count`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `status`=1
			  AND `day_finish` LIKE ('".$mon."%')
			GROUP BY DATE_FORMAT(`day_finish`,'%d')";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$days = array();
	while($r = mysql_fetch_assoc($q))
		$days[$r['day']] = $r['count'];

	$unix = strtotime($day);
	$dayCount = date('t', $unix);   // ���������� ���� � ������
	$week = date('w', $unix);       // ����� ������� ��� ������
	if(!$week)
		$week = 7;

	$curDay = strftime('%Y-%m-%d');
	$curUnix = strtotime($curDay); // ������� ���� ��� ��������� ��������� ����

	$send .= '<tr>'.($week - 1 ? '<td colspan="'.($week - 1).'">' : '');

	for($n = 1; $n <= $dayCount; $n++) {
		$day = $mon.'-'.($n < 10 ? '0' : '').$n;
		$cur = $curDay == $day ? ' cur' : '';
		$sel = $selDay == $day ? ' sel' : '';
		$old = $unix + $n * 86400 <= $curUnix ? ' old' : '';
		$val = $old ? '' : ' val="'.$day.'"';
		$send .=
			'<td class="d '.$cur.$old.$sel.'"'.$val.'>'.
				($cur ? '<u>'.$n.'</u>' : $n).
				(isset($days[$day]) ? ': <b'.($old && $zayav_spisok ? ' class="fc-old-sel" val="'.$day.'"' : '').'>'.$days[$day].'</b>' : '');
		$week++;
		if($week > 7)
			$week = 1;
		if($week == 1 && $n < $dayCount)
			$send .= '<tr>';
	}
	$send .= '</table>'.
			($zayav_spisok && $selDay != '0000-00-00' ? '<div id="fc-cancel" val="0000-00-00">���� �� ������</div>' : '').
		'</div>';

	return $send;
}

/* --- ������� �� ������ --- */
function _zayavExpense($id=0, $i='name') {//��������� �������� ������ �� ����
	$key = CACHE_PREFIX.'zayav_expense'.APP_ID;
	$arr = xcache_get($key);
	if(empty($arr)) {
		$sql = "SELECT *
				FROM `_zayav_expense_category`
				WHERE `app_id`=".APP_ID."
				ORDER BY `sort`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		$arr = array();
		while($r = mysql_fetch_assoc($q)) {
			$r['txt'] =     $r['dop'] == 1;
			$r['worker'] =  $r['dop'] == 2;
			$r['zp'] =      $r['dop'] == 3;
			$r['attach'] =  $r['dop'] == 4;
			$arr[$r['id']] = $r;
		}
		xcache_set($key, $arr, 86400);
	}

	//��� ���������
	if(!$id)
		return $arr;

	//������������ id
	if(!_num($id))
		die('Error: _zayav_expense_category id: <b>'.$id.'</b> not correct');

	//����������� id
	if(!isset($arr[$id]))
		die('Error: no _zayav_expense_category id: <b>'.$id.'</b>');

	switch($i) {
		case 'all': return $arr[$id];   //������� ������ ���������� ���������
		case 'name':
		case 'txt':
		case 'worker':
		case 'zp': return $arr[$id][$i];
		default: return '<span class="red">����������� ���� ��������� ������: <b>'.$i.'</b></span>';
	}
}
function _zayavExpenseDop($id=false) {//�������������� ������� ��� ��������� ������� �� ������
	$arr =  array(
		0 => '���',
		1 => '��������� ����',
		2 => '������ �����������',
		3 => '������ ���������',
		4 => '������������ �����'
	);
	return $id !== false ? $arr[$id] : $arr;
}
function _zayav_expense($zayav_id) {//������� �������� �� ������ � ���������� � ������
	return '<div id="_zayav-expense">'._zayav_expense_spisok($zayav_id).'</div>';
}
function _zayav_expense_spisok($zayav_id) {//������� �������� �� ������ � ���������� � ������
	$sql = "SELECT *
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `zayav_id`=".$zayav_id."
			ORDER BY `id`";
	$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	$arr = _attachValToList($arr);

	$send =
		'<script type="text/javascript">'.
			'var ZAYAV_EXPENSE=['._zayav_expense_json($arr).'];'.
		'</script>';

	if(empty($arr))
		return $send;

	//����� ���������� �� ������
	$sql = "SELECT SUM(`sum`)
			FROM `_money_accrual`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `zayav_id`=".$zayav_id."
			  AND !`deleted`";
	$accrual_sum = query_value($sql, GLOBAL_MYSQL_CONNECT);

	return
		$send.
		'<div class="headBlue">'.
			'������� �� ������'.
			'<div class="img_edit'._tooltip('�������� ������� �� ������', -167, 'r').'</div>'.
		'</div>'.
		'<h1>'.($accrual_sum ? '����� ����� ����������: <b>'.round($accrual_sum, 2).'</b> ���.' : '���������� ���.').'</h1>'.
		_zayav_expense_html($arr, $accrual_sum);
}
function _zayav_expense_test($v) {// �������� ������������ ������ �������� ������ ��� �������� � ����
	$v = trim($v);
	if(empty($v))
		return $v;

	$send = array();

	foreach(explode(',', $v) as $r) {
		$u = array();
		$ids = explode(':', $r);
		if($ids[0] != 0 && !_num($ids[0]))//id �������
			return false;
		$u[] = _num($ids[0]);

		if(!$cat_id = _num($ids[1]))//���������
			return false;
		$u[] = $cat_id;

		$ze = _zayavExpense($cat_id, 'all');
		if($ze['zp'] && !_num($ids[2]))
			return false;
		if($ze['attach'] && !preg_match(REGEXP_NUMERIC, $ids[2])) {
			$txt = substr($ids[2], 1);
			if(empty($txt))
				return false;
		}
		$u[] = $ids[2];

		if(!_cena($ids[3]) && $ids[3] != 0)
			return false;
		$u[] = _cena($ids[3]);

		$send[] = implode(':', $u);
	}
	return implode(',', $send);
}
function _zayav_expense_html($arr, $accrual_sum=false, $diff=false, $new=false) {//����� ������� �������� �� ������
	$expense_sum = 0;
	$send = '<table class="ze-spisok">';
	foreach($arr as $arr_id => $r) {
		$tr = ''; // ���������� ������ �� ��������
		$changeSum = '';
		$changeDop = '';

		if(is_array($diff)) {
			$line = false; // ���������� �������, ��� ������ ���� ������� ��� ���������
 			foreach($diff as $diff_id => $d) {
				if($arr_id == $diff_id) {
					$line = true;
					if($r['sum'] != $d['sum'])
						$changeSum = ' change';
					if($r['txt'] != $d['txt'] || $r['worker_id'] != $d['worker_id'] || $r['zp_id'] != $d['zp_id'] || $r['attach_id'] != $d['attach_id'])
						$changeDop = ' class="change"';
					break;
				}
			}
			if(!$line)
				$tr = ' class="'.($new ? 'new' : 'del').'"';
		}

		$sum = round($r['sum'], 2);
		$expense_sum += $sum;
		$ze = _zayavExpense($r['category_id'], 'all');
		$send .=
			'<tr'.$tr.'>'.
				'<td class="name">'.$ze['name'].
				'<td'.$changeDop.'>'.
					($ze['txt'] ? $r['txt'] : '').
					($ze['worker'] && $r['worker_id'] ?
						'<a class="go-report-salary" val="'.$r['worker_id'].':'.$r['year'].':'.$r['mon'].':'.$r['id'].'">'.
							_viewer($r['worker_id'], 'viewer_name').
						'</a>'
					: '').
					($ze['attach'] && $r['attach_id'] ? $r['attach_link'] : '').
					($ze['attach'] && !$r['attach_id'] && $r['txt'] ? $r['txt'] : '').
				'<td class="sum'.$changeSum.'">'.$sum.' �.';
	}

	if($accrual_sum !== false) {
		$ost = $accrual_sum - $expense_sum;
		$send .= '<tr><td colspan="2" class="itog">����:<td class="sum"><b>'.$expense_sum.'</b> �.'.
				 '<tr><td colspan="2" class="itog">�������:<td class="sum '.($ost > 0 ? ' plus' : 'minus').'">'.$ost.' �.';

	}
	$send .= '</table>';

	return $send;
}
function _zayav_expense_json($arr) {//������� �� ������ � ������� json
	$json = array();
	foreach($arr as $r) {
		$ze = _zayavExpense($r['category_id'], 'all');
		$json[] = '['.
			$r['id'].','.
			$r['category_id'].','.
			($ze['txt'] ? '"'.trim($r['txt']).'"' : '').
			($ze['worker'] ? _num($r['worker_id']) : '').
			($ze['zp'] ? _num($r['zp_id']) : '').
			($ze['attach'] ? (_num($r['attach_id']) ? _num($r['attach_id']) : '"'.trim($r['txt']).' "') : '').','.
			round($r['sum'], 2).','.
			$r['salary_list_id'].
		']';
	}
	return implode(',', $json);
}
function _zayav_expense_array($v) {//������� �� ������ � ������� array
	if(empty($v))
		return array();
	$array = array();
	foreach(explode(',', $v) as $r) {
		$ex = explode(':', $r);
		$array[] = array(
			_num($ex[0]),
			_num($ex[1]),
			$ex[2],
			_cena($ex[3])
		);
	}

	return $array;
}
function _zayav_expense_worker_balans($old, $new) {//�������� �������� �����������, ���� ��������
	$balansOld = array();
	foreach($old as $id => $r)
		if($r['worker_id'])
			$balansOld[$r['worker_id']][$id] = $r['sum'];

	//���������� ����������
	foreach($new as $id => $r)
		if($r['worker_id'] && isset($balansOld[$r['worker_id']][$id]))
			if($r['sum'] != $balansOld[$r['worker_id']][$id]) {
				_balans(array(
					'action_id' => 40,
					'worker_id' => $r['worker_id'],
					'zayav_id' => $r['zayav_id'],
					'sum' => $r['sum'],
					'sum_old' => $balansOld[$r['worker_id']][$id]
				));
				unset($balansOld[$r['worker_id']][$id]);
			}

	//��������� ���� �������
	foreach($balansOld as $worker_id => $worker)
		foreach($worker as $id => $sum)
			if(empty($new[$id]))
				_balans(array(
					'action_id' => 21,
					'worker_id' => $worker_id,
					'zayav_id' => $old[$id]['zayav_id'],
					'sum' => $sum
				));

	//���������� ����������
	foreach($new as $id => $r)
		if($r['worker_id'] && empty($old[$id]))
			_balans(array(
				'action_id' => 19,
				'worker_id' => $r['worker_id'],
				'zayav_id' => $r['zayav_id'],
				'sum' => $r['sum']
			));
}

/* ���� ������������ */
function _service($i=false, $id=0) {
	$key = CACHE_PREFIX.'service'.WS_ID;
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					`id`,
					`name`
				FROM `_zayav_type`
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
				'active' => 0,
				'const' => array()
			);
		$arr = _serviceConstSet($arr);
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

	if($i == 'const_define')
		return _serviceConstDefine($arr[$id]['const']);

	if($i == 'const_js')
		return _serviceConstJs($arr[$id]['const']);

	if($i == 'name')
		return $arr[$id]['name'];

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
	$sql = "SELECT `type_id`
			FROM `_zayav_type_active`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID;
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$arr[$r['type_id']]['active'] = 1;
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
				DISTINCT `type_id`,
				COUNT(`id`) `count`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `client_id`=".$client_id."
			GROUP BY `type_id`
			ORDER BY `type_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$send[$r['type_id']] = $ass[$r['type_id']].'<em>'.$r['count'].'</em>';

	return _assJson($send);
}
function _serviceConstSet($arr) {//��������� �������� ����� ������������
	$sql = "SELECT
				`id`,
				`const`
			FROM `_zayav_const`";
	$const = query_ass($sql, GLOBAL_MYSQL_CONNECT);

	foreach($arr as $id => $r) {
		foreach($const as $val)
			$arr[$id]['const'][$val] = 0;
		$arr[$id]['const']['ZAYAV_TYPE_ID'] = $id;
	}

	$sql = "SELECT *
			FROM `_zayav_const_use`
			WHERE `app_id`=".APP_ID;
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$arr[$r['type_id']]['const'][$const[$r['pole_id']]] = 1;

	return $arr;
}
function _serviceConstArr($arr) {//��������� �������������� ������� ��������
	$send = array();
	foreach($arr as $key => $val)
		$send[$key] = $val;
	return $send;
}
function _serviceConstDefine($arr) {//��������� �������� ��� ����������� ���� ������������
	foreach($arr as $key => $val)
		define($key, $val);
	return true;
}
function _serviceConstJs($arr) {//��������� �������� � ������� JS ��� ����������� ���� ������������
	$send = array();
	foreach($arr as $key => $val)
		$send[] = $key.'='.$val;
	return implode(",\n", $send);
}
