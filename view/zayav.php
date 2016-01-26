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
		0 => 'Любой статус',
		1 => 'Ожидает выполнения',
		2 => 'Выполнено',
		3 => 'Завершить не удалось'
	);
	$color = array(
		0 => 'ffffff',
		1 => 'E8E8FF',
		2 => 'CCFFCC',
		3 => 'FFDDDD'
	);

	if($id === false)
		return $name;

	//неизвестный id статуса
	if(!isset($name[$id]))
		return '<span class="red">неизвестный id статуса: <b>'.$id.'</b></span>';

	switch($i) {
		case 'name': return $name[$id];
		case 'color': return $color[$id];
		case 'bg': return ' style="background-color:#'.$color[$id].'"';
		default: return '<span class="red">неизвестный ключ статуса: <b>'.$i.'</b></span>';
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
function _zayavValToList($arr) {//вставка данных заявок в массив по zayav_id
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
						'<span'.($r['deleted'] ? ' class="deleted"' : '').'>№'.$r['nomer'].'</span>'.
						'<div class="tooltip">'._zayavTooltip($r, $arr[$id]).'</div>'.
					'</a>',
				'zayav_color' => //подсветка заявки на основании статуса
					'<a href="'.URL.'&p=zayav&d=info&id='.$r['id'].'" class="zayav_link color"'._zayavStatus($r['status'], 'bg').'>'.
						'№'.$r['nomer'].
						'<div class="tooltip">'._zayavTooltip($r, $arr[$id]).'</div>'.
					'</a>',
				'zayav_dolg' => $dolg ? '<span class="zayav-dolg'._tooltip('Долг по заявке', -45).$dolg.'</span>' : '',
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
						' class="tstat'._tooltip('Статус заявки: '._zayavStatus($z['status']), -7, 'l').
					'</div>'.
					'<b>'.$z['name'].'</b>'.
			'<table>'.
				'<tr><td class="label top">Клиент:'.
					'<td>'.$v['client_name'].
						($v['client_phone'] ? '<br />'.$v['client_phone'] : '').
				'<tr><td class="label">Баланс:'.
					'<td><span class="bl" style=color:#'.($v['client_balans'] < 0 ? 'A00' : '090').'>'.$v['client_balans'].'</span>'.
			'</table>'.
		'</table>';
}
function _zayavCountToClient($spisok) {//прописывание квадратиков с количеством заявок в список клиентов
	$ids = implode(',', array_keys($spisok));

	//заявки, ожидающие выполнения
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

	//выполненные заявки
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

	//отменённые заявки
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
			$filter['clear'] = '<a class="clear">Очистить фильтр</a>';
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
		$status[1] .= '<div id="srok">Срок: '._zayavFinish($v['finish']).'</div>';

	return
	'<div id="_zayav">'.
		_service('menu').
		'<div class="result">'.$data['result'].'</div>'.
		'<table class="tabLR">'.
			'<tr><td id="spisok">'.$data['spisok'].
				'<td class="right">'.
					'<div id="buttonCreate">'.
						'<a id="_zayav-add">Новая заявка</a>'.
					'</div>'.
					'<div id="find"></div>'.
					'<div class="findHead">Порядок</div>'.
					_radio('sort', array(1=>'По дате добавления',2=>'По обновлению статуса'), $v['sort']).
					_check('desc', 'Обратный порядок', $v['desc']).
					'<div class="condLost'.(!empty($v['find']) ? ' dn' : '').'">'.
						'<div class="findHead">Статус заявки</div>'.
						_rightLink('status', $status, $v['status']).

  (ZAYAV_INFO_PAY_TYPE ? '<div class="findHead">Расчёт</div>'.
						  _radio('paytype', array(0=>'Не важно',1=>'Наличный',2=>'Безналиный'), $v['paytype'], 1)
  : '').

 (ZAYAV_INFO_DIAGNOST ? _check('diagnost', 'Диагностика', $v['diagnost']) : '').

(ZAYAV_FILTER_NOSCHET ? _check('noschet', 'Счёт не выписан', $v['noschet']) : '').
   (ZAYAV_FILTER_DIFF ? _check('diff', 'Неоплаченные заявки', $v['diff']) : '').

  (ZAYAV_INFO_PRODUCT ? '<div class="findHead">Изделия</div>'.
						'<input type="hidden" id="product_id" value="'.$v['product_id'].'" />'.
						'<input type="hidden" id="product_sub_id" value="'.$v['product_sub_id'].'" />'
  : '').

 (ZAYAV_INFO_EXECUTER ? '<div class="findHead">Исполнитель</div>'.
						'<input type="hidden" id="executer_id" value="'.$v['executer_id'].'" />'
 : '').

   (ZAYAV_INFO_DEVICE ? '<div class="findHead">Устройство</div>'.
						'<div id="dev"></div>'.

						'<div class="findHead">Заказаны запчасти</div>'.
						_radio('zpzakaz', array(0=>'Не важно',1=>'Да',2=>'Нет'), $v['zpzakaz'], 1).

						'<div class="findHead">Нахождение устройства</div>'.
						'<input type="hidden" id="place" value="'.$v['place'].'" />'
   : '').

		(VIEWER_ADMIN ? _check('deleted', '+ удалённые заявки', $v['deleted'], 1).
						'<div id="deleted-only-div"'.($v['deleted'] ? '' : ' class="dn"').'>'.
							_check('deleted_only', 'только удалённые', $v['deleted_only'], 1).
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

	//выделение номера заявки при быстром поиске
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
			'result' => 'Заявок не найдено'.$filter['clear'],
			'spisok' => $filter['js'].'<div class="_empty">Заявок не найдено</div>',
			'filter' => $filter
		);

	$send = array(
		'all' => $all,
		'result' => 'Показан'._end($all, 'а', 'о').' '.$all.' заяв'._end($all, 'ка', 'ки', 'ок').
					($count ? '<span id="z-count">('.$count.' шт.)</span>' : '').
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


	//Запчасти
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
//	(isset($zpZakaz[$id]) ? '<tr><td class="label">Заказаны з/п:<td class="zz">'.implode(', ', $zpZakaz[$id]) : '').
//					'<td class="image">'.$img.
 			  ($r['imei'] ? '<tr><td class="label">IMEI:<td>'.$r['imei'] : '').
		    ($r['serial'] ? '<tr><td class="label">Серийный номер:<td>'.$r['serial'] : '').
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
		$diff = $diff ? ($diff > 0 ? 'Недо' : 'Пере').'плата '.abs($diff).' руб.' : 'Оплачено';
		$deleted = $r['deleted'] ? ' deleted' : '';
		$statusColor = $r['deleted'] ? '' : _zayavStatus($r['status'], 'bg');
		$send['spisok'] .=
			'<div class="_zayav-unit'.$deleted.'" id="u'.$id.'"'.$statusColor.' val="'.$r['id'].'">'.
				'<div class="zd">'.
					'#'.$r['nomer'].
					'<div class="date-add">'.FullData($r['dtime_add'], 1).'</div>'.
($r['status'] == 2 ? '<div class="date-ready'._tooltip('Дата выполнения', -40).FullData($r['status_dtime'], 1, 1).'</div>' : '').
					($r['sum_accrual'] || $r['sum_pay'] ?
						'<div class="balans'.($r['sum_accrual'] != $r['sum_pay'] ? ' diff' : '').'">'.
							'<span class="acc'._tooltip('Начислено', -39).$r['sum_accrual'].'</span>/'.
							'<span class="pay'._tooltip($diff, -17, 'l').$r['sum_pay'].'</span>'.
						'</div>'
					: '').
				'</div>'.
				'<a class="name"><b>'.$r['name'].'</b></a>'.
				'<table class="tab">'.
			 ($r['count'] ? '<tr><td class="label">Количество:<td><b>'.$r['count'].'</b> шт.' : '').
		($r['dogovor_id'] ? '<tr><td class="label top">Договор:<td class="dog">'.$r['dogovor_line'] : '').
		   ($r['product'] ? '<tr><td class="label top">Изделия:<td>'.$r['product'] : '').
   (!$filter['client_id'] ? '<tr><td class="label">Клиент:<td>'.$r['client_go'] : '').
			 ($r['adres'] ? '<tr><td class="label top">Адрес:<td>'.$r['adres'] : '').
	         ($r['schet'] ? '<tr><td class="label topi">Счета:<td>'.$r['schet'] : '').
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
function _zayavNote($arr) {//прикрепление заметок или комментариев в массив заявок
	$ids = implode(',', array_keys($arr));

	$zn = array(); //ассоциация: id заметки -> id заявки

	//Прикрепление заметок
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
	$zayav_id = 0; //выбор только верхней заметки в заявке
	while($r = mysql_fetch_assoc($q)) {
		if($zayav_id != $r['page_id']) {
			$zayav_id = $r['page_id'];
			$arr[$r['page_id']]['note'] = $r['txt'];
			$zn[$r['id']] = $r['page_id'];
		}
	}

	if(empty($zn))
		return $arr;

	//Прикрепление комментариев
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
function _dogovorValToList($arr) {//вставка данных договора в массив по dogovor_id
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
				'dogovor_nomer' => '№'.$r['nomer'],
				'dogovor_data' => _dataDog($r['data_create']).' г.',
				'dogovor_sum' => _cena($r['sum']),
				'dogovor_avans' => _sumSpace(_cena($r['avans'])),
				'dogovor_line' => '<span class="'._tooltip('Сумма: '._cena($r['sum']).' руб.', -3).
									'<b>№'.$r['nomer'].'</b>'.
									' от '._dataDog($r['data_create']).
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
		return _err('Страницы не существует');

	if(!$z = _zayavQuery($zayav_id, 1))
		return _err('Заявки не существует.');

	_service('const_define', $z['type_id']);
	_service('current', $z['type_id']);//установка куки для возврата на соответствующий списов заявок

	if(!VIEWER_ADMIN && $z['deleted'])
		return _noauth('Заявка удалёна');

	$product = _zayav_product_html($zayav_id);

	$status = _zayavStatus();
	unset($status[0]);
	$history = _history(array('zayav_id'=>$zayav_id));

	$z['sum_cost'] = _cena($z['sum_cost']);
	$z['sum_accrual'] = _cena($z['sum_accrual']);
	$z['sum_pay'] = _cena($z['sum_pay']);

	//разница начислений и платежей
	$sum_diff = round($z['sum_accrual'] - $z['sum_pay'], 2);
	$sum_diff = $sum_diff ? ($sum_diff > 0 ? 'недоплачено ' : 'переплачено ').abs($sum_diff).' руб.' : '';

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
				'todel:'._zayavToDel($zayav_id).','.//показывать пункт для удаления заявки
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
			'<a class="link a-page sel">Информация</a>'.
			'<a class="link" id="edit">Редактирование</a>'.
			'<a class="link _accrual-add">Начислить</a>'.
			'<a class="link _income-add">Принять платёж</a>'.
			'<a class="link a-page">История</a>'.
			'<div id="nz" class="'._tooltip('Номер заявки', -74, 'r').'#'.$z['nomer'].'</div>'.
		'</div>'.

		($z['deleted'] ? '<div id="zayav-deleted">Заявка удалена.</div>' : '').

		'<table class="page">'.
			'<tr><td id="left">'.

					'<div class="headName">'.
						$z['name'].
						'<input type="hidden" id="zayav-action" />'.
					'</div>'.

	 ($z['about'] ? '<div class="_info">'._br($z['about']).'</div>' : '').

					'<table id="tab">'.
						'<tr><td class="label">Клиент:<td>'._clientVal($z['client_id'], 'go').
		 ($z['count'] ? '<tr><td class="label">Количество:<td><b>'.$z['count'].'</b> шт.' : '').
			($product ? '<tr><td class="label top">Изделия:<td>'.$product : '').
		 ($z['adres'] ? '<tr><td class="label">Адрес:<td>'.$z['adres'] : '').
	($z['dogovor_id'] ? '<tr><td class="label">Договор:<td>'._zayavDogovor($z) : '').
	  ($z['sum_cost'] ? '<tr><td class="label">Стоимость:<td><b>'.$z['sum_cost'].'</b> руб.' : '').
	  ($z['pay_type'] ? '<tr><td class="label">Расчёт:<td>'._payType($z['pay_type']) : '').

	 (ZAYAV_INFO_SROK ? '<tr><td class="label">Срок:<td>'._zayavFinish($z['day_finish']) : '').

 (ZAYAV_INFO_EXECUTER ? '<tr><td class="label">Исполнитель:'.
							'<td id="executer_td"><input type="hidden" id="executer_id" value="'.$z['executer_id'].'" />'
					: '').

  ($z['status'] == 1 && $z['diagnost'] ?
					'<tr><td colspan="2">'._button('diagnost-ready', 'Внести результаты диагностики', 300)
  : '').

						'<tr><td class="label">Статус:<td>'._zayavStatusButton($z).

 (ZAYAV_INFO_DOCUMENT ? '<tr><td class="label">Документ:<td><input type="hidden" id="attach_id" value="'.$z['attach_id'].'" />' : '').

   ($z['sum_accrual'] ? '<tr><td class="label">Начислено:<td><b class="acc">'._cena($z['sum_accrual']).'</b> руб.' : '').
	   ($z['sum_pay'] ? '<tr><td class="label">Оплачено:'.
							'<td><b class="pay">'._cena($z['sum_pay']).'</b> руб.'.
				   ($sum_diff ? '<span id="sum-diff">'.$sum_diff.'</span>' : '')
		: '').
					'</table>'.

					'<div id="added">'.
						'Заявку '._viewerAdded($z['viewer_id_add']).' '.
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
			'<div class="headName">'.$z['name'].' - история действий</div>'.
			$history['spisok'].
		'</div>'.

	'</div>';
}
function _zayavToDel($zayav_id) {//можно ли удалять заявку..
	if(!_zayavQuery($zayav_id))
		return 0;

	//проверка на наличие платежей
	$sql = "SELECT COUNT(`id`)
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 0;

	//проверка на наличие возвратов
	$sql = "SELECT COUNT(`id`)
			FROM `_money_refund`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 0;

	//проверка на наличие заключённых договоров
	$sql = "SELECT COUNT(`id`)
			FROM `_zayav_dogovor`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 0;

	//проверка на наличие счетов на оплату
	$sql = "SELECT COUNT(`id`)
			FROM `_schet`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	if(query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 0;

	//проверка на наличие начислений зп сотрудникам
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
function _zayavDogovor($z) {//отображение номера договора
	$sql = "SELECT *
			FROM `_zayav_dogovor`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$z['id'];
	if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$title = 'от '._dataDog($r['data_create']).' г. на сумму '._cena($r['sum']).' руб.';

	return '<b class="dogn'._tooltip($title, -7, 'l').'№'.$r['nomer'].'</b> '.
			'<a href="'.LINK_DOGOVOR.$r['link'].'.doc" class="img_word'._tooltip('Распечатать', -41).'</a>';
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
function _zayavDogovorFilter($v) {//проверка всех введённых данных по договору
	if(!_num($v['id']) && $v['id'] != 0)
		return 'Ошибка: некорректный идентификатор договора';
	if(!_num($v['zayav_id']))
		return 'Ошибка: неверный номер заявки';
	if(!_num($v['nomer']))
		return 'Ошибка: некорректно указан номер договора';
	if(!preg_match(REGEXP_DATE, $v['data_create']))
		return 'Ошибка: некорректно указана дата заключения договора';
	if(!_cena($v['sum']))
		return 'Ошибка: некорректно указана сумма по договору';
//	if(!empty($v['avans']) && !_cena($v['avans']))
//		return 'Ошибка: некорректно указан авансовый платёж';
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
		return 'Ошибка: договор с номером <b>'.$send['nomer'].'</b> уже был заключен';

	if(empty($send['fio']))
		return 'Ошибка: не указаны ФИО клиента';

	if($send['sum'] < $send['avans'])
		return 'Ошибка: авансовый платёж не может быть больше суммы договора';

	if($send['avans'] && !$send['invoice_id'])
		return 'Ошибка: не указан номер счёта для авансового платежа';

	$sql = "SELECT `client_id`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `id`=".$send['zayav_id'];
	if(!$send['client_id'] = query_value($sql, GLOBAL_MYSQL_CONNECT))
		return 'Ошибка: заявки id='.$send['zayav_id'].' не существует, либо она была удалена';

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

		$v['save'] = 1; //сохранить договор

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
	'<div class="head-name">ДОГОВОР №'.$v['nomer'].'</div>'.
	'<table class="city_data"><tr><td>Город Няндома<th>'._dataDog($v['data_create']).'</table>'.
	'<div class="paragraph">'.
		'<p>Общество с ограниченной ответственностью «Территория Комфорта», '.
		'в лице менеджера по продажам, '._viewer(VIEWER_ID, 'viewer_name_full').', действующей на основании доверенности, '.
		'с одной стороны, и '.$v['fio'].($adres ? ', '.$adres : '').', именуемый в дальнейшем «Заказчик», с другой стороны, '.
		'заключили настоящий договор, далее «Договор», о нижеследующем:'.
	'</div>'.
	'<div class="p-head">1. Предмет договора</div>'.
	'<div class="paragraph">'.
		'<p>1.1. Поставщик принимает на себя обязательство по исполнению ЗАКАЗА на изготовление и доставку изделий (оконных блоков, дверных блоков, защитных роллет, гаражных и промышленных ворот) в соответствии с индивидуальными характеристиками объекта и требованиями Заказчика (далее «Товар»). Работы по установке изделий и конструкций из них по адресу заказчика.'.
		'<p>1.2. Полная характеристика Заказа содержится в Спецификации, являющейся неотъемлемой частью настоящего договора.'.
	'</div>'.
	'<div class="p-head">2. Обязанности сторон</div>'.
	'<div class="paragraph">'.
		'<p>2.1. Поставщик обязуется исполнить заказ с соблюдением условий настоящего договора и требований, предъявляемых к продукции данного типа и указанных в ГОСТах №23166-99 «Блоки оконные ТУ», №30970-2002 «Блоки оконные из ПВХ» для оконных блоков, в рабочей документации разработчиков систем профилей для дверных блоков, в «Инструкции по изготовлению роллет», «Инструкции по изготовлению ворот», в ГОСТах №111-2001 «Стекло листовое», №24866-99 «Стеклопакеты клееные строительного назначения».'.
		'<p>2.2. Предварительный согласованный срок поставки товара и выполнения предусмотренных работ составляет 20 рабочих дней. Окончательный срок выполнения договора не более тридцати рабочих дней с момента поступления от Заказчика полной оплаты по договору и обеспечения Заказчиком условий пунктов 2.3. и 2.4. Данные сроки предусмотрены по стандартным изделиям. В случае заказа сложных и цветных изделий, срок договора увеличивается на количество дополнительных дней на изготовление сложной конструкции, указанное в Спецификации.'.
		'<p>2.3. Заказчик обязуется обеспечить доступ монтажников и подвод электропитания к оконным проемам, защиту личного имущества, напольных покрытий от пыли и повреждений, если договор предполагает выполнение работ по установке продукции по адресу заказчика. '.
		'Поставщик не отвечает за сохранность стеновых покрытий в зоне работ. Поставщик не несёт ответственности за нарушение элементов конструкций фасадов зданий при выполнении монтажных и отделочных работ, возникших в следствии ветхости строений и наличия скрытых строительных дефектов. Восстановительные работы проводятся по желанию и за счёт заказчика. В стоимость отделки откосов не входит герметизация наружного шва до 4 см шириной.'.
		'<p>2.4. Заказчик обязуется принять меры по обеспечению отсутствия автотранспорта на тротуаре под оконными проемами. В случае, если Заказчик не принял данные меры и монтаж осуществить невозможно, Заказчик оплачивает дополнительный выезд монтажной бригады из расчета 1000 руб./выезд, при этом окончательный срок выполнения договора составит 10 рабочих дней с момента обеспечения надлежащего доступа к месту установки.'.
		'<p>2.5. Все необходимые материалы доставляются Поставщиком на адрес Заказчика к моменту установки. В случае, отсутствия заказчика или его представителя на объекте в согласованный день поставки, повторная доставка оплачивается из расчёта 1000 руб./заказ, при этом окончательный срок выполнения договора составит 10 рабочих дней с момента обеспечения надлежащего доступа к месту установки.'.
		'<p>2.6. Поставщик обязуется собрать строительный мусор в мешки, если они присутствуют на объекте. Поставщик не несет ответственности за вывоз строительного мусора, образованного после выполнения работ по установке продукции. Заказчик обязуется осуществить вывоз и размещение мусора согласно действующим нормам. Поставщик обязуется вывезти строительный мусор на специализированную площадку (в соответствии с законом №239-29 от 29.05.2003), только в случае, если данная услуга была заказана Заказчиком и указана в Спецификации.'.
		'<p>2.7. Заказчик обязуется оплатить полную стоимость Заказа до начала установки изделий и конструкций из них в соответствии со Спецификацией.'.
		'<p>2.8. Право собственности на товар переходит к Заказчику в момент подписания им товаросопроводительных документов. В случае разногласия по количеству, комплектности и внешнему виду, суть разногласий отмечается в товаросопроводительных документах.'.
		'<p>2.9. Заказчик обязуется осуществить приёмку выполненных работ по монтажу изделий, по количеству, качеству, комплектности, внешнему виду и качеству отделки и заполнить две идентичные части «Приложения» к Акту сдачи – приёмки заказа и две идентичные части Акта сдачи - приёмки заказа. Отрывная часть №1 «Приложения» остаётся у заказчика, а часть №2 данного «Приложения» передается бригадиру установщиков. Акт приёма-передачи передаётся бригадиру мастеров по восстановлению откосов или бригадиру установщиков в случае, если отделка производится унифицированной бригадой. «Приложение» необходимо для оперативного обоснования претензии Заказчиком по качеству выполнения Поставщиком условий договора. В случае, если Заказчик отказывается подписывать «Акт сдачи-приемки заказа» и/или «Приложение», заказ считается автоматически выполненным.'.
		'<p>2.10. В случае подписания заказчиком товаросопроводительных документов или Акта выполненных работ с разногласиями, Поставщик обязуется рассмотреть данные разногласия в течение 5 дней, при этом срок выполнения договора автоматически продлевается на указанный срок.'.
		'<p>2.11. В случае согласия с претензией Заказчика Поставщик обязан заменить соответствующую часть товара или выполнить иные действия, предусмотренные настоящим договором для таких случаев в течение 14 рабочих дней, следующих за днем получения претензии Заказчика.'.
	'</div>'.
	'<div class="p-head">3. Цена товара и порядок расчетов</div>'.
	'<div class="paragraph">'.
		'<p>3.1. Полная стоимость заказа составляет: '.$v['sum'].' ('._numToWord($v['sum']).' рубл'._end($v['sum'], 'ь', 'я', 'ей').') указанные в спецификации, являются твердыми, и изменению без обоюдного согласия сторон не подлежат.'.
		($v['avans'] ?
			'<p>3.2. Оплата по настоящему договору осуществляется в следующем порядке:'.
			'<p>3.2.1. Авансовый платёж в размере '.$v['avans'].' ('._numToWord($v['avans']).' рубл'._end($v['avans'], 'ь', 'я', 'ей').') вносится Заказчиком в день заключения настоящего договора. В случае отсутствия работ по договору, авансовый платёж составляет 100% суммы договора.'.
			($dopl ?
				'<p>3.2.2. Доплата по договору, в сумме '.$dopl.' ('._numToWord($dopl).' рубл'._end($dopl, 'ь', 'я', 'ей').'), оплачивается в кассу до установки изделий: ______________________________________.'
			: '')
		: '').
	'</div>'.
	'<div class="p-head">4. Качество и гарантийные обязательства</div>'.
	'<div class="paragraph">'.
		'<p>4.1. Гарантийный срок на оконные блоки – три года, на монтажные и отделочные работы по оконным блокам – один год. Гарантийный срок на дверные блоки, роллетные системы и ворота - один год. На монтажные и отделочные работы по установке дверных блоков, роллетных систем и ворот – один год. Гарантийный срок действует с момента подписания сторонами отгрузочных документов (Акт сдачи – приемки заказа). Для климатических условий Северо-западного и Центрального региона России рекомендовано использовать двухкамерные стеклопакеты. Заказчик предупреждается, что при установке однокамерного стеклопакета, возможно образование конденсата и промерзание стеклопакета в зимний период при более высокой температуре окружающей среды, чем при установке двухкамерного стеклопакета. Заказчик предупреждён, что для исключения возможности выпадения конденсата и образования наледи на стеклопакетах, необходимо поддержание уровня температуры и влажности рекомендованного для жилого помещения.'.
		'<p>4.2. Поставщик обязуется заменить входящие в состав товара комплектующие за свой счёт, в случае выхода их из строя в течение Гарантийного срока. Срок выполнения гарантийных работ составляет не более 20 рабочих дней с момента поступления письменной претензии. Письменная претензия принимается в центральном офисе компании либо по почте.'.
		'<p>4.3. Гарантия не распространяется на случаи, когда товар (или его комплектующие) утратили свои качественные характеристики вследствие неправильной эксплуатации Товара, действий третьих лиц или в случае возникновения обстоятельств непреодолимой силы.'.
	'</div>'.
	'<div class="p-head">5. Ответственность сторон, форс-мажорные обстоятельства и ответственность сторон</div>'.
	'<div class="paragraph">'.
		'<p>5.1. Стороны освобождаются от ответственности за частичное или полное неисполнение обязательств по настоящему Договору, если это явилось следствием обстоятельств непреодолимой силы (форс-мажор), т.е. пожара, стихийных бедствий, войны, блокад, введение правительственных ограничений постфактум, объявления карантина и эпидемий. При этом срок исполнения обязательств по Договору продлевается на период действия указанных обстоятельств.'.
		'<p>5.2. За неисполнение или ненадлежащее исполнение обязательств стороны несут ответственность в соответствии с действующим законодательством Российской Федерации. В случае нарушения сроков выполнения договора поставщик выплачивает Заказчику неустойку в соответствии с Законом РФ "О защите прав потребителей" размере 3% в день от суммы недопоставленных комплектующих Заказа указанных в Спецификации и от суммы не оказанных услуг и работ, указанных в Спецификации.'.
	'</div>'.
	'<div class="p-head">6. Изменение условий договора и порядок разрешения споров</div>'.
	'<div class="paragraph">'.
		'<p>6.1. Все изменения и дополнения к настоящему договору действительны лишь в том случае, если они оформлены в письменном виде и подписаны обеими сторонами.'.
		'<p>6.2. Все споры и разногласия, которые могут возникнуть из настоящего договора будут по возможности разрешаться путём двусторонних переговоров.'.
		'<p>6.3. Споры, не получившие разрешения в результате переговоров, подлежат разрешению в соответствии с действующим законодательством РФ.'.
	'</div>'.
	'<div class="p-head">7. Срок действия договора</div>'.
	'<div class="paragraph">'.
		'<p>7.1. Настоящий договор вступает в силу с момента его подписания и действует до полного выполнения обязательств обеими сторонами.'.
	'</div>'.
	'<div class="p-head">8. Заключительные положения</div>'.
	'<div class="paragraph">'.
		'<p>8.1. Настоящий договор составлен в двух экземплярах по одному для каждой из сторон, имеющих равную юридическую силу.'.
	'</div>'.
	'<div class="p-head">9. Юридические адреса и банковские реквизиты сторон</div>'.
	'<table class="rekvisit">'.
		'<tr><td><b>Поставщик:</b><br />'.
				'ООО «'._ws('name').'»<br />'.
				'ОГРН '._ws('ogrn').'<br />'.
				'ИНН '._ws('inn').'<br />'.
				'КПП '._ws('kpp').'<br />'.
				str_replace("\n", '<br />', _ws('adres_yur')).'<br />'.
				'Тел. '._ws('phone').'<br /><br />'.
				'Адрес офиса: '._ws('adres_ofice').
			'<td><b>Заказчик:</b><br />'.
				$v['fio'].'<br />'.
				'Паспорт серии '.$v['pasp_seria'].' '.$v['pasp_nomer'].'<br />'.
				'выдан '.$v['pasp_ovd'].' '.$v['pasp_data'].'<br /><br />'.
				$adres.
	'</table>'.
	'<div class="podpis-head">Подписи сторон:</div>'.
	'<table class="podpis">'.
		'<tr><td>Поставщик ________________ '._viewer(VIEWER_ID, 'viewer_name_init').
			'<td>Заказчик ________________ '.$fioPodpis.
	'</table>'.
	'<div class="mp">М.П.</div>');

	$doc->newPage();

	$doc->addParagraph(
	'<div class="ekz">Экземпляр заказчика</div>'.
	'<div class="act-head">АКТ сдачи-приёмки заказа</div>'.
	'<table class="act-tab">'.
		'<tr><td class="label">По адресу:<td class="title">'.$v['adres'].'<td><td>'.
		'<tr><td class="label">Заказ:<td class="title">'.$v['nomer'].'<td class="label">Заказчик:<td>'.$fioPodpis.
	'</table>'.
	'<div class="act-inf">Экземпляр Заказчика является основанием для направления претензии.</div>'.
	'<div class="act-p">'.
		'<p>1. Оконные блоки принял без замечаний, со следующими замечаниями (ненужное зачеркнуть) по количеству, качеству, комплектности и внешнему виду:'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
	'</div>'.
	'<div class="act-p">'.
		'<p>2. Выполненные работы принял без замечаний, со следующими замечаниями (ненужное зачеркнуть):'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
	'</div>'.
	'<div class="act-p">От заказчика ___________________________________</div>'.
	'<div class="act-p">От поставщика /Бригадир монтажников/ ____________________________________</div>'.
	'<div class="act-p">Дата _______________</div>'.
	'<div class="cut-line">отрезать</div>'.
	'<div class="ekz">Экземпляр бригадира монтажников</div>'.
	'<div class="act-head">АКТ сдачи-приёмки заказа</div>'.
	'<table class="act-tab">'.
		'<tr><td class="label">По адресу:<td class="title">'.$v['adres'].'<td><td>'.
		'<tr><td class="label">Заказ:<td class="title">'.$v['nomer'].'<td class="label">Заказчик:<td>'.$fioPodpis.
	'</table>'.
	'<div class="time-dost">Время доставки _____________________</div>'.
	'<div class="act-p">'.
		'<p>1. Оконные блоки принял без замечаний, со следующими замечаниями (ненужное зачеркнуть) по количеству, качеству, комплектности и внешнему виду:'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
	'</div>'.
	'<div class="act-p">'.
		'<p>2. Выполненные работы принял без замечаний, со следующими замечаниями (ненужное зачеркнуть):'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
		'<p>__________________________________________________________________________'.
	'</div>'.
	'<div class="act-p">От заказчика ___________________________________</div>'.
	'<div class="act-p">От поставщика /Бригадир монтажников/ ____________________________________</div>'.
	'<div class="act-p">Дата _______________</div>'
	);

	if($income_id) {
		$doc->newPage();
		$doc->addParagraph(_incomeReceipt($income_id));
	}

	$doc->output($v['link'], @$v['save'] ? PATH_DOGOVOR : '');
}
function _zayavBalansUpdate($zayav_id) {//Обновление баланса заявки
	//начисления
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_accrual`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	$accrual = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//платежи
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	$income = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//возвраты
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_refund`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	$refund = query_value($sql, GLOBAL_MYSQL_CONNECT);

	$income -= $refund;

	//наличие счетов
	$sql = "SELECT COUNT(`id`)
			FROM `_schet`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `zayav_id`=".$zayav_id;
	$schet_count = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//расходы
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


/* Кеширование видов изделий */
function _product($product_id=false) {//Список изделий для заявок
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
function _productSub($product_id=false) {//Список изделий для заявок
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

function _zayav_product_query($zayav_id) {//sql-запрос для изделий
	$sql = "SELECT *
			FROM `_zayav_product`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `zayav_id`=".$zayav_id."
			ORDER BY `id`";
	return query_arr($sql, GLOBAL_MYSQL_CONNECT);
}
function _zayav_product_html($zayav_id) {//список изделий для информации о заявке
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
		'<td>&nbsp;'.$r['count'].' шт.';
}
function _zayav_product_js($zayav_id) {//список изделий для информации о заявке в формате JS
	if(!$spisok = _zayav_product_query($zayav_id))
		return '[[0,0,0]]';

	$send = array();
	foreach($spisok as $r)
		$send[] = '['.$r['product_id'].','.$r['product_sub_id'].','.$r['count'].']';

	return '['.implode(',', $send).']';
}
function _zayavProductValToList($arr) {//вставка данных изделий в массив заявок
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
			$r['count'].' шт.';
	}

	foreach($arr as $r)
		if($r['product']) {
			$arr[$r['id']]['product'] = '<table>'.$r['product'].'</table>';
			$arr[$r['id']]['product_report'] = implode("\n", $r['product_report']);
		}

	return $arr;
}

/* Срок выполнения заявки */
function _zayavFinish($day='0000-00-00') {
	return
		'<input type="hidden" id="day_finish" value="'.$day.'" />'.
		'<div class="day-finish-link">'.
			'<span>'.($day == '0000-00-00' ? 'не указан' : FullData($day, 1, 0, 1)).'</span>'.
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
					'<td>пн<td>вт<td>ср<td>чт<td>пт<td>сб<td>вс';

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
	$dayCount = date('t', $unix);   // Количество дней в месяце
	$week = date('w', $unix);       // Номер первого дня недели
	if(!$week)
		$week = 7;

	$curDay = strftime('%Y-%m-%d');
	$curUnix = strtotime($curDay); // Текущий день для выделения прошедших дней

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
			($zayav_spisok && $selDay != '0000-00-00' ? '<div id="fc-cancel" val="0000-00-00">День не указан</div>' : '').
		'</div>';

	return $send;
}

/* --- Расходы по заявке --- */
function _zayavExpense($id=0, $i='name') {//категории расходов заявки из кеша
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

	//все категории
	if(!$id)
		return $arr;

	//некорректный id
	if(!_num($id))
		die('Error: _zayav_expense_category id: <b>'.$id.'</b> not correct');

	//неизвестный id
	if(!isset($arr[$id]))
		die('Error: no _zayav_expense_category id: <b>'.$id.'</b>');

	switch($i) {
		case 'all': return $arr[$id];   //возврат данных конкретной категории
		case 'name':
		case 'txt':
		case 'worker':
		case 'zp': return $arr[$id][$i];
		default: return '<span class="red">неизвестный ключ категории заявки: <b>'.$i.'</b></span>';
	}
}
function _zayavExpenseDop($id=false) {//дополнительное условие для категории расхода по заявке
	$arr =  array(
		0 => 'нет',
		1 => 'текстовое поле',
		2 => 'список сотрудников',
		3 => 'список запчастей',
		4 => 'прикрепление файла'
	);
	return $id !== false ? $arr[$id] : $arr;
}
function _zayav_expense($zayav_id) {//вставка расходов по заявке в информацию о заявке
	return '<div id="_zayav-expense">'._zayav_expense_spisok($zayav_id).'</div>';
}
function _zayav_expense_spisok($zayav_id) {//вставка расходов по заявке в информацию о заявке
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

	//сумма начислений по заявке
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
			'Расходы по заявке'.
			'<div class="img_edit'._tooltip('Изменить расходы по заявке', -167, 'r').'</div>'.
		'</div>'.
		'<h1>'.($accrual_sum ? 'Общая сумма начислений: <b>'.round($accrual_sum, 2).'</b> руб.' : 'Начислений нет.').'</h1>'.
		_zayav_expense_html($arr, $accrual_sum);
}
function _zayav_expense_test($v) {// Проверка корректности данных расходов заявки при внесении в базу
	$v = trim($v);
	if(empty($v))
		return $v;

	$send = array();

	foreach(explode(',', $v) as $r) {
		$u = array();
		$ids = explode(':', $r);
		if($ids[0] != 0 && !_num($ids[0]))//id расхода
			return false;
		$u[] = _num($ids[0]);

		if(!$cat_id = _num($ids[1]))//категория
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
function _zayav_expense_html($arr, $accrual_sum=false, $diff=false, $new=false) {//вывод таблицы расходов по заявке
	$expense_sum = 0;
	$send = '<table class="ze-spisok">';
	foreach($arr as $arr_id => $r) {
		$tr = ''; // изначально ничего не менялось
		$changeSum = '';
		$changeDop = '';

		if(is_array($diff)) {
			$line = false; // изначально считаем, что строка была удалена или добавлена
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
				'<td class="sum'.$changeSum.'">'.$sum.' р.';
	}

	if($accrual_sum !== false) {
		$ost = $accrual_sum - $expense_sum;
		$send .= '<tr><td colspan="2" class="itog">Итог:<td class="sum"><b>'.$expense_sum.'</b> р.'.
				 '<tr><td colspan="2" class="itog">Остаток:<td class="sum '.($ost > 0 ? ' plus' : 'minus').'">'.$ost.' р.';

	}
	$send .= '</table>';

	return $send;
}
function _zayav_expense_json($arr) {//расходы по заявке в формате json
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
function _zayav_expense_array($v) {//расходы по заявке в формате array
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
function _zayav_expense_worker_balans($old, $new) {//внесение балансов сотрудников, если меняются
	$balansOld = array();
	foreach($old as $id => $r)
		if($r['worker_id'])
			$balansOld[$r['worker_id']][$id] = $r['sum'];

	//начисление изменилось
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

	//начиление было удалено
	foreach($balansOld as $worker_id => $worker)
		foreach($worker as $id => $sum)
			if(empty($new[$id]))
				_balans(array(
					'action_id' => 21,
					'worker_id' => $worker_id,
					'zayav_id' => $old[$id]['zayav_id'],
					'sum' => $sum
				));

	//начисление добавилось
	foreach($new as $id => $r)
		if($r['worker_id'] && empty($old[$id]))
			_balans(array(
				'action_id' => 19,
				'worker_id' => $r['worker_id'],
				'zayav_id' => $r['zayav_id'],
				'sum' => $r['sum']
			));
}

/* Виды деятельности */
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
function _serviceMenu($arr) {//меню для списка заявок
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
function _serviceCurrentId($spisok, $type_id=0) {//получение текущего type_id заявок
	if(!$spisok)
		return 0;

	//если есть только один вид деятельности, возвращение его, не важно, активен или нет
	if(count($spisok) == 1)
		return key($spisok);

	if(!$type_id)
		$spisok = _serviceActive($spisok);

	//если видов деятельности больше одного и не один не активен, то переход в настройки Видов деятельности
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
function _serviceActiveSet($arr) {//отметка активных видов деятельности
	$sql = "SELECT `type_id`
			FROM `_zayav_type_active`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID;
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$arr[$r['type_id']]['active'] = 1;
	return $arr;
}
function _serviceActive($arr) {//возврат активных видов деятельности
	$send = array();
	foreach($arr as $r)
		if($r['active'])
			$send[$r['id']] = $r;
	return $send;
}
function _serviceActiveCount($arr) {//количество активных видой деятельности
	$count = 0;
	foreach($arr as $r)
		$count += $r['active'];
	return $count;
}
function _serviceActiveJs($arr) {//список активных видов деятельности в формате JS - ассоциативный список
	$send = array();
	foreach(_serviceActive($arr) as $r)
		$send[$r['id']] = $r['name'];
	return _assJson($send);
}
function _serviceActiveJsClient($arr, $client_id) {//список видов деятельности, которые использовались в заявках клиента. В формате JS - ассоциативный список
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
function _serviceConstSet($arr) {//установка констант видам деятельности
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
function _serviceConstArr($arr) {//получение ассоциативного массива констант
	$send = array();
	foreach($arr as $key => $val)
		$send[$key] = $val;
	return $send;
}
function _serviceConstDefine($arr) {//установка констант для конкретного вида деятельности
	foreach($arr as $key => $val)
		define($key, $val);
	return true;
}
function _serviceConstJs($arr) {//получение констант в формате JS для конкретного вида деятельности
	$send = array();
	foreach($arr as $key => $val)
		$send[] = $key.'='.$val;
	return implode(",\n", $send);
}
