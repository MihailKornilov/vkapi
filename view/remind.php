<?php
function _remind($i='spisok', $v=array()) {
	switch($i) {
		case 'spisok':
			$data = _remind_spisok($v);
			return $data['spisok'];
		case 'right': return _remind_right();
	}
	return '';
}
function _remind_stat() {
	if(!SA)
		return '';

	//����� �������
	$sql = "SELECT COUNT(`id`)
			FROM `_remind`
			WHERE `app_id`=".APP_ID."
			  AND `dtime_add` LIKE '".TODAY."%'";
	$newToday = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//����� ������� ������
	$sql = "SELECT COUNT(`id`)
			FROM `_remind`
			WHERE `app_id`=".APP_ID."
			  "._period(0, 'sql');
	$newWeek = query_value($sql, GLOBAL_MYSQL_CONNECT);

	//����� ������� �����
	$sql = "SELECT COUNT(`id`)
			FROM `_remind`
			WHERE `app_id`=".APP_ID."
			  AND `dtime_add` LIKE '".strftime('%Y-%m-')."%'";
	$newMonth = query_value($sql, GLOBAL_MYSQL_CONNECT);

	$moveToday = _remind_stat_count(1) - $newToday;
	$moveWeek = _remind_stat_count(1, 'week') - $newWeek;
	$moveMonth = _remind_stat_count(1, strftime('%Y-%m-')) - $newMonth;
	return
	'<div class="mar8">'.
		'<table class="_spisok">'.
			'<tr><th>'.
				'<th>�������'.
				'<th>�������<br />������'.
				'<th>�������<br />�����'.
			'<tr><td>�����'.
				'<td class="w70 center">'.($newToday ? $newToday : '').
				'<td class="w70 center">'.($newWeek ? $newWeek : '').
				'<td class="w70 center">'.($newMonth ? $newMonth : '').
			'<tr><td>����������'.
				'<td class="w70 center">'.($moveToday ? $moveToday : '').
				'<td class="w70 center">'.($moveWeek ? $moveWeek : '').
				'<td class="w70 center">'.($moveMonth ? $moveMonth : '').
			'<tr><td>���������'.
				'<td class="w70 center">'._remind_stat_count(2).
				'<td class="w70 center">'._remind_stat_count(2, 'week').
				'<td class="w70 center">'._remind_stat_count(2, strftime('%Y-%m-')).
			'<tr><td>��������'.
				'<td class="w70 center">'._remind_stat_count(0).
				'<td class="w70 center">'._remind_stat_count(0, 'week').
				'<td class="w70 center">'._remind_stat_count(0, strftime('%Y-%m-')).
		'</table>'.
	'</div>';
}
function _remind_stat_count($status, $period=TODAY) {
	$cont = " AND `dtime_add` LIKE '".$period."%'";
	if($period == 'week')
		$cont = _period(0, 'sql');

	$sql = "SELECT COUNT(`id`)
			FROM `_remind_history`
			WHERE `app_id`=".APP_ID."
			  AND `status`=".$status."
			  ".$cont;
	$c = query_value($sql, GLOBAL_MYSQL_CONNECT);
	return $c ? $c : '';
}
function _remind_right() {
	$list = array(
		9 => '�������'.(_remindTodayCount() ? '<em>'._remindTodayCount().'</em>' : ''),
		1 => '��������<em>'._remindActiveCount().'</em>',
		2 => '���������',
		3 => '��������'
	);
	return
		'<div id="remind-filter">'.
			'<div class="findHead">��������� �����������</div>'.
			_radio('status', $list, 9, 1).
		'</div>';
}
function _remind_history_add($v) {
	$v = array(
		'remind_id' => $v['remind_id'],
		'status' => isset($v['status']) ? $v['status'] : 1,
		'day' => !empty($v['day']) ? $v['day'] : '0000-00-00',
		'reason' => !empty($v['reason']) ? $v['reason'] : ''
	);
	$sql = "INSERT INTO `_remind_history` (
				`app_id`,
				`remind_id`,
				`status`,
				`day`,
				`txt`,
				`viewer_id_add`
			) VALUES (
				".APP_ID.",
				".$v['remind_id'].",
				".$v['status'].",
				'".$v['day']."',
				'".addslashes($v['reason'])."',
				".VIEWER_ID."
			)";
	query($sql, GLOBAL_MYSQL_CONNECT);
}
function _remindTodayCount($plus_b=0) { //��������� ���������� ����������� �� �������
	if(defined('REMIND_ACTIVE_COUNT')) {
		if($plus_b)
			return REMIND_ACTIVE_COUNT ? ' <b>+'.REMIND_ACTIVE_COUNT.'</b>' : '';
		return REMIND_ACTIVE_COUNT;
	}
	$sql = "SELECT COUNT(`id`)
			FROM `_remind`
			WHERE `app_id`=".APP_ID."
			  AND `status`=1
			  AND `day`<=DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-%d')";
	define('REMIND_ACTIVE_COUNT', query_value($sql, GLOBAL_MYSQL_CONNECT));

	return _remindTodayCount($plus_b);
}
function _remindActiveCount() { //���������� �������� �����������
	$sql = "SELECT COUNT(`id`)
			FROM `_remind`
			WHERE `app_id`=".APP_ID."
			  AND `status`=1";
	$count = query_value($sql, GLOBAL_MYSQL_CONNECT);

	return $count ? $count : '';
}
function _remindDayLeft($status, $d) {//������� ���������� ����
	if($status == 2)
		return '���������';
	if($status == 3)
		return '��������';
	$dayLeft = floor((strtotime($d) - TODAY_UNIXTIME) / 3600 / 24);
	if($dayLeft < 0)
		return '���������'._end($dayLeft * -1, ' ', '� ').($dayLeft * -1)._end($dayLeft * -1, ' ����', ' ���', ' ����');
	if($dayLeft > 2)
		return '�����'._end($dayLeft, '�� ', '��� ').$dayLeft._end($dayLeft, ' ����', ' ���', ' ����').
		'<span class="oday">('.FullData($d, 1).')</span>';
	switch($dayLeft) {
		default:
		case 0: return '��������� �������';
		case 1: return '��������� ������';
		case 2: return '��������� �����������';
	}
}
function _remindDayLeftBg($status, $d) {//����� ��� ���������
	if($status == 2)
		return '9f9';
	if($status == 3)
		return 'ddd';
	$dayLeft = floor((strtotime($d) - TODAY_UNIXTIME) / 3600 / 24);
	if($dayLeft < 0)
		return 'faa';
	if($dayLeft == 0)
		return 'ffa';
	return 'ddf';
}
function _remindFilter($v) {
	return array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 20,
		'client_id' => _num(@$v['client_id']),
		'zayav_id' => _num(@$v['zayav_id']),
		'status' => _num(@$v['status']) ? _num($v['status']) : 9
	);
}
function _remind_spisok($v=array()) {
	$filter = _remindFilter($v);
	$filter = _filterJs('REMIND', $filter);

	define('CLIENT_OR_ZAYAV', $filter['client_id'] || $filter['zayav_id']);
	define('REMIND_TODAY', !CLIENT_OR_ZAYAV && $filter['status'] == 9);
	define('REMIND_TODAY_MSG', REMIND_TODAY ? ' �� �������' : '');
	define('REMIND_ACTIVE', !CLIENT_OR_ZAYAV && !REMIND_TODAY);

	$cond = "`app_id`=".APP_ID;

	if(REMIND_TODAY)
		$cond .= " AND `status`=1 AND `day`<=DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-%d')";
	if(REMIND_ACTIVE)
		$cond .= " AND `status`=".$filter['status'];
	if($filter['client_id'])
		$cond .= " AND `client_id`=".$filter['client_id'];
	if($filter['zayav_id'])
		$cond .= " AND `zayav_id`=".$filter['zayav_id'];


	$send = array(
		'all' => 0,
		'spisok' => $filter['js'],
		'active' => 0,
		'active_spisok' => array(),
		'hidden' => 0,
		'filter' => $filter
	);

	$sql = "SELECT COUNT(*)
			FROM `_remind`
			WHERE ".$cond;
	if(!$all = query_value($sql, GLOBAL_MYSQL_CONNECT)) {
		$send['spisok'] .= $filter['js'].'<div class="_empty">�����������'.REMIND_TODAY_MSG.' ���.</div>';
		return $send;
	}

	$send['all'] = $all;

	$sql = "SELECT *
			FROM `_remind`
			WHERE ".$cond."
			ORDER BY ".(CLIENT_OR_ZAYAV ? '`id` DESC' : '`day`')."
			LIMIT "._startLimit($filter);
	$remind = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	$remind = _remindHistory($remind);
	$remind = _clientValToList($remind);
	$remind = _zayavValToList($remind);
	$remind = _dogovorValToList($remind);

	foreach($remind as $r) {
		if($r['status'] == 1) {
			$send['active']++;
			$send['active_spisok'][] =
				'{'.
					'id:'.$r['id'].','.
					'txt:"'.addslashes($r['txt']).'",'.
					'about:"'.addslashes(_br($r['about'])).'"'.
				'}';
		}
	}

	$send['hidden'] = $all - $send['active'];//������� �����������

	if($filter['page'] == 1) {
		if(!$filter['zayav_id']) {
			$c = $filter['client_id'] ? $send['active'] : $all;
			$send['spisok'] .=
				'<div id="_remind-head">' .
					($c ? '�������� '.$c.' ����������'._end($c, '�', '�', '�').REMIND_TODAY_MSG : '�������� ����������� ���.').
					($filter['client_id'] && $send['hidden'] ? '<a id="_remind-show-all">�������� �������: '.$send['hidden'].'</a>' : '').
				'</div>';
		}
	}


	foreach($remind as $r)
		$send['spisok'] .= _remind_unit($r, $filter);


	$send['spisok'] .=
		_next($filter + array(
			'type' => 5,
			'all' => $all
		));

	return $send;
}
function _remindHistory($arr) {//���������� ������� ����������� � ������
	foreach($arr as $id => $r)
		$arr[$id]['history'] = array();

	$sql = "SELECT *
			FROM `_remind_history`
			WHERE `remind_id` IN ("._keys($arr).")
			ORDER BY `id` DESC";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$arr[$r['remind_id']]['history'][] = $r;

	return $arr;
}
function _remind_unit($r, $filter) {
	return
	'<div class="_remind-unit'.(($filter['zayav_id'] || $filter['client_id']) && $r['status'] != 1 ? ' dn' : '').'" val="'.$r['id'].'">'.
		'<input type="hidden" class="ruday" value="'.$r['day'].'" />'.
		'<div class="head'.($r['status'] == 1 ? ' hd-edit' : '').'" style="background-color:#'._remindDayLeftBg($r['status'], $r['day']).'">'.
			'<div class="hdtxt">'.$r['txt'].'</div>'.
			'<div class="hd-about">'.nl2br($r['about']).'</div>'.
		'</div>'.
		'<table class="to">'.
			($r['client_id'] && !$filter['client_id'] && !$filter['zayav_id'] ?
				'<tr><td class="label top">������:'.
					'<td>'.$r['client_link'].
						($r['client_phone'] ? ', '.$r['client_phone'] : '').
						($r['client_balans'] < 0 ? '<br />������: <b class="dolg">'.$r['client_balans'].'</b>' : '')
			: '').
			($r['zayav_id'] && !$filter['zayav_id'] ?
				'<tr><td class="label top">������:<td>'.$r['zayav_link_name'].
				($r['dogovor_id'] ? ', ������� '.$r['dogovor_line'] : '')
			: '').
		'</table>'.
		'<div class="day_left">'.
			_remindDayLeft($r['status'], $r['day']).
			'<a class="ruhist">�������</a>'.
			($r['status'] == 1 ? '<tt> :: </tt><a class="action"">��������</a>' : '').
		'</div>'.
		'<div class="hist">'._remind_history_show($r).'</div>'.
	'</div>';
}
function _remind_history_show($v) {//����������� ������� �����������
	$send = '<table>';

	if(empty($v['history']))
		$send .= '<tr><td>������� ���.';
	else {
		$count = count($v['history']);
		foreach ($v['history'] as $r) {
			if($r['txt_old'])
				$send .=
					'<tr><td>'.$r['txt_old'];
			else {
				$about = '';
				$count--;
				$sex1 = _viewer($r['viewer_id_add'], 'viewer_sex') == 1;
				if($r['status'] == 1 && !$count)
					$about = '������'.($sex1 ? '�' : '').' �����������.<br />���� ����������: <u>'.FullData($r['day']).'</u>';
				else
					switch($r['status']) {
						case 1:
							$about = '�����'.($sex1 ? '����' : '��').' ����������� �� <u>'.FullData($r['day']).'</u>'.
								($r['txt'] ? '.<br />�������: <b>'.$r['txt'].'</b>' : '');
							break;
						case 2: $about = '��������'.($sex1 ? '�' : '').' �����������'; break;
						case 3: $about = '�������'.($sex1 ? '�' : '').' �����������'; break;
					}
				$send .=
					'<tr><td>' .
					FullDataTime($r['dtime_add']) . ' ' .
					_viewer($r['viewer_id_add'], 'viewer_name') . ' ' .
					$about . '.';
			}
		}
	}

	$send .= '</table>';

	return $send;
}
function _remind_add($v) {
	if($v['zayav_id'] && !@$v['client_id']) {
		$sql = "SELECT `client_id`
				FROM `_zayav`
				WHERE `id`=".$v['zayav_id'];
		$v['client_id'] = query_value($sql, GLOBAL_MYSQL_CONNECT);
	}

	$sql = "INSERT INTO `_remind` (
					`app_id`,
					`client_id`,
					`zayav_id`,
					`txt`,
					`about`,
					`day`,
					`money_cut`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$v['client_id'].",
					".$v['zayav_id'].",
					'".addslashes($v['txt'])."',
					'".addslashes(@$v['about'])."',
					'".$v['day']."',
					".(empty($v['money_cut']) ? 0 : 1).",
					".VIEWER_ID."
				)";
	query($sql, GLOBAL_MYSQL_CONNECT);

	$insert_id = query_insert_id('_remind', GLOBAL_MYSQL_CONNECT);

	_remind_history_add(array(
		'remind_id' => $insert_id,
		'day' => $v['day']
	));
}
function _remind_zayav($zayav_id) {//����� ����������� � ������
	return '<div id="_remind-zayav">'._remind_zayav_spisok($zayav_id).'</div>';
}
function _remind_zayav_spisok($zayav_id) {//������ ����������� � ������
	$data = _remind_spisok(array('zayav_id'=>$zayav_id));

	$send =
		'<script type="text/javascript">'.
			'var ZAYAV_REMIND={'.
				'active:'.$data['active'].','.
				'active_spisok:['.implode(',', $data['active_spisok']).']'.
			'};'.
		'</script>';

	if(!$data['all'])
		return $send;

	return
		$send.
		'<div class="headBlue but">'.
			'<a href="'.URL.'&p=report&d=remind"><b>�����������</b></a>&nbsp;'.
			'<button class="vk small _remind-add">����� �����������</button>'.
			($data['hidden'] ? '<a id="_remind-show-all">�������� ���: '.$data['hidden'].'</a>' : '').
		'</div>'.
		'<div id="_remind-spisok">'.$data['spisok'].'</div>';
}
function _remind_active_to_ready($ids) {//������� ��������� �������� ����������� ������������
	$sql = "SELECT *
			FROM `_remind`
			WHERE `app_id`=".APP_ID."
			  AND `status`=1
			  AND `id` IN (".$ids.")";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q)) {
		$sql = "UPDATE `_remind` SET `status`=2 WHERE `id`=".$r['id'];
		query($sql, GLOBAL_MYSQL_CONNECT);

		_remind_history_add(array(
			'remind_id' => $r['id'],
			'status' => 2,
			'reason' => '����� ����� �� ������'
		));
	}
}
