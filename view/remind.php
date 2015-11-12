<?php
function _remind() {
	$data = _remind_spisok();
	return array(
		'list' => _remind_list($data),
		'spisok' => $data['spisok'],
		'right' => _remind_right()
	);
}//_remind()
function _remind_list($data) {
	return
		'<div id="_remind">'.
			'<table class="tabLR">'.
				'<tr><td class="left">'.$data['spisok'].
					'<td class="right">'.
						'<div id="buttonCreate" class="_remind-add"><a>Новое напоминание</a></div>'.
						_remind_right().
			'</table>'.
		'</div>';
}//_remind_list()
function _remind_right() {
	return
		'<div class="findHead">Категории напоминаний</div>'.
		_radio('_remind-status', array(1=>'Активные',2=>'Выполнены',0=>'Отменены'), 1, 1);
}//_remind_right()
function _remind_history_add($v) {
	$v = array(
		'remind_id' => $v['remind_id'],
		'status' => isset($v['status']) ? $v['status'] : 1,
		'day' => !empty($v['day']) ? $v['day'] : '0000-00-00',
		'reason' => !empty($v['reason']) ? $v['reason'] : ''
	);
	$sql = "INSERT INTO `remind_history` (
				`remind_id`,
				`status`,
				`day`,
				`txt`,
				`viewer_id_add`
			) VALUES (
				".$v['remind_id'].",
				".$v['status'].",
				'".$v['day']."',
				'".addslashes($v['reason'])."',
				".VIEWER_ID."
			)";
	query($sql, GLOBAL_MYSQL_CONNECT);
}//_remind_history_add()
function _remindActiveSet() { //Получение количества активных напоминаний
	$sql = "SELECT COUNT(`id`)
			FROM `remind`
			WHERE `app_id`=".APP_ID."
			  ".(defined('WS_ID') ? " AND `ws_id`=".WS_ID : '')."
			  AND `status`=1
			  AND `day`<=DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-%d')";
	$count = query_value($sql, GLOBAL_MYSQL_CONNECT);
	define('REMIND_ACTIVE', $count > 0 ? ' <b>+'.$count.'</b>' : '');
}//_remindActiveSet()
function _remindDayLeft($status, $d) {//подсчёт оставшихся дней
	if($status == 2)
		return 'Выполнено';
	if($status == 0)
		return 'Отменено';
	$dayLeft = floor((strtotime($d) - TODAY_UNIXTIME) / 3600 / 24);
	if($dayLeft < 0)
		return 'Просрочен'._end($dayLeft * -1, ' ', 'о ').($dayLeft * -1)._end($dayLeft * -1, ' день', ' дня', ' дней');
	if($dayLeft > 2)
		return 'Остал'._end($dayLeft, 'ся ', 'ось ').$dayLeft._end($dayLeft, ' день', ' дня', ' дней').
		'<span class="oday">('.FullData($d, 1).')</span>';
	switch($dayLeft) {
		default:
		case 0: return 'Выполнить сегодня';
		case 1: return 'Выполнить завтра';
		case 2: return 'Выполнить послезавтра';
	}
}//_remindDayLeft()
function _remindDayLeftBg($status, $d) {//цвета для подсветки
	if($status == 2)
		return '9f9';
	if($status == 0)
		return 'ddd';
	$dayLeft = floor((strtotime($d) - TODAY_UNIXTIME) / 3600 / 24);
	if($dayLeft < 0)
		return 'faa';
	if($dayLeft == 0)
		return 'ffa';
	return 'ddf';
}//_remindDayLeftBg()
function _remindFilter($v) {
	return array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 20,
		'client_id' => _num(@$v['client_id']) ? $v['client_id'] : 0,
		'zayav_id' => _num(@$v['zayav_id']),
		'day' => !empty($v['day']) ? $v['day'] : '',
		'status' => isset($v['status']) && preg_match(REGEXP_NUMERIC, $v['status']) ? intval($v['status']) : 1
	);
}//_remindFilter()
function _remind_spisok($v=array(), $i='all') {
	$filter = _remindFilter($v);

	$page = $filter['page'];
	$limit = $filter['limit'];
	$start = ($page - 1) * $limit;

	define('CLIENT_OR_ZAYAV', $filter['client_id'] || $filter['zayav_id']);

	$cond = "`app_id`=".APP_ID.
			(defined('WS_ID') ? " AND `ws_id`=".WS_ID : '').
			(CLIENT_OR_ZAYAV ? '' : " AND `status`=".$filter['status']);

	if($filter['day'])
		$cond .= " AND `day` LIKE '".$filter['day']."%'";
	if($filter['client_id'])
		$cond .= " AND `client_id`=".$filter['client_id'];
	if($filter['zayav_id'])
		$cond .= " AND `zayav_id`=".$filter['zayav_id'];


	$send = array(
		'spisok' => '',
		'filter' => $filter,
		'active' => 0,
		'hidden' => 0
	);

	$send['all'] = query_value("SELECT COUNT(`id`) AS `all` FROM `remind` WHERE ".$cond, GLOBAL_MYSQL_CONNECT);
	if(!$send['all']) {
		$send += array(
			'spisok' => ($filter['zayav_id'] ? '&nbsp;&nbsp;Напоминаний нет.' : '<div class="_empty">Напоминаний не найдено.</div>')
		);
		if($i == 'all')
			return $send;
		return $send[$i];
	}

	$all = $send['all'];

	$sql = "SELECT *
			FROM `remind`
			WHERE ".$cond."
			ORDER BY ".(CLIENT_OR_ZAYAV ? '`id` DESC' : '`day`')."
			LIMIT ".$start.",".$limit;
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	if(!mysql_num_rows($q))
		return 'Напоминаний нет.';
	$remind = array();
	while($r = mysql_fetch_assoc($q)) {
		$r['history'] = array();
		$remind[$r['id']] = $r;
	}

	$remind = _clientValToList($remind);

	if(function_exists('_zayavValues'))
		$remind = _zayavValues($remind);

	//внесение истории
	$sql = "SELECT *
			FROM `remind_history`
			WHERE `remind_id` IN (".implode(',', array_keys($remind)).")
			ORDER BY `id` DESC";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$remind[$r['remind_id']]['history'][] = $r;

	foreach($remind as $r) {
		if($r['status'] == 1)
			$send['active']++;
		$send['spisok'] .=
			'<div class="_remind-unit'.(($filter['zayav_id'] || $filter['client_id']) && $r['status'] != 1 ? ' dn' : '').'" val="'.$r['id'].'">'.
				'<input type="hidden" class="ruday" value="'.$r['day'].'" />'.
				'<div class="head'.($r['status'] == 1 ? ' hd-edit' : '').'" style="background-color:#'._remindDayLeftBg($r['status'], $r['day']).'">'.
					'<div class="hdtxt">'.$r['txt'].'</div>'.
					'<div class="hd-about">'.nl2br($r['about']).'</div>'.
//					($r['status'] == 1 ? '<div class="img_edit'._tooltip('Редактировать', -50).'</div>' : '').
				'</div>'.
				'<table class="to">'.
					($r['client_id'] && !$filter['client_id'] && !$filter['zayav_id'] ?
						'<tr><td class="label">Клиент:<td>'.$r['client_link'].($r['client_phone'] ? ', '.$r['client_phone'] : '')
					: '').
					($r['zayav_id'] && !$filter['zayav_id'] ?
						'<tr><td class="label">Заявка:<td>'.$r['zayav_link']
					: '').
				'</table>'.
				'<div class="day_left">'.
					_remindDayLeft($r['status'], $r['day']).
					'<a class="ruhist">История</a>'.
					($r['status'] == 1 ? '<tt> :: </tt><a class="action"">Действие</a>' : '').
				'</div>'.
				'<div class="hist">'._remind_history_show($r).'</div>'.
			'</div>';
	}

	$send['hidden'] = $send['all'] - $send['active'];//скрытые напоминания

	if($page == 1) {
		$send['spisok'] .=
			'<input type="hidden" id="remind_filter_status" value="'.$filter['status'].'" />'.
			'<input type="hidden" id="remind_filter_zayav_id" value="'.$filter['zayav_id'].'" />';
		if(!$filter['zayav_id']) {
			$c = $filter['client_id'] ? $send['active'] : $all;
			$send['spisok'] =
				'<div id="_remind-head">' .
					($c ? 'Показано '.$c.' напоминани'._end($c, 'е', 'я', 'й') : 'Активных напоминаний нет.').
					($filter['client_id'] && $send['hidden'] ? '<a id="_remind-show-all">Показать скрытые: '.$send['hidden'].'</a>' : '').
				'</div>'.$send['spisok'];
		}
	}

	if($start + $limit < $all) {
		$c = $all - $start - $limit;
		$c = $c > $limit ? $limit : $c;
		$send['spisok'] .=
			'<div class="_next" id="_remind-next" val="'.($page + 1).'">'.
				'<span>Показать ещё '.$c.' напоминани'._end($c, 'е', 'я', 'й').'</span>'.
			'</div>';
	}

	if($i == 'all')
		return $send;
	return $send[$i];
}//_remind_spisok()
function _remind_history_show($v) {//отображение истории напоминаний
	$send = '<table>';

	if(empty($v['history']))
		$send .= '<tr><td>Истории нет.';
	else {
		$count = count($v['history']);
		foreach ($v['history'] as $r) {
			if($r['txt_old'])
				$send .=
					'<tr><td>'.$r['txt_old'];
			else {
				$about = '';
				$count--;
				$sex1 = _viewer($r['viewer_id_add'], 'sex') == 1;
				if($r['status'] == 1 && !$count)
					$about = 'создал'.($sex1 ? 'а' : '').' напоминание.<br />День выполнения: <u>'.FullData($r['day']).'</u>';
				else
					switch($r['status']) {
						case 1:
							$about = 'перен'.($sex1 ? 'есла' : 'ёс').' напоминание на <u>'.FullData($r['day']).'</u>'.
								($r['txt'] ? '.<br />Причина: <b>'.$r['txt'].'</b>' : '');
							break;
						case 2: $about = 'выполнил'.($sex1 ? 'а' : '').' напоминание'; break;
						case 0: $about = 'отменил'.($sex1 ? 'а' : '').' напоминание'; break;
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
}//_remind_history_show()
function _remind_add($v) {
	if($v['zayav_id'] && !@$v['client_id'])
		$v['client_id'] = query_value("SELECT `client_id` FROM `zayav` WHERE `id`=".$v['zayav_id']);

	$sql = "INSERT INTO `remind` (
					`app_id`,
					`ws_id`,
					`client_id`,
					`zayav_id`,
					`txt`,
					`about`,
					`day`,
					`money_cut`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".(defined('WS_ID') ? WS_ID : 0).",
					".$v['client_id'].",
					".$v['zayav_id'].",
					'".addslashes($v['txt'])."',
					'".addslashes(@$v['about'])."',
					'".$v['day']."',
					".(empty($v['money_cut']) ? 0 : 1).",
					".VIEWER_ID."
				)";
	query($sql, GLOBAL_MYSQL_CONNECT);

	$sql = "SELECT `id` FROM `remind` WHERE `app_id`=".APP_ID." ORDER BY `id` DESC LIMIT 1";
	$insert_id = query_value($sql, GLOBAL_MYSQL_CONNECT);

	_remind_history_add(array(
		'remind_id' => $insert_id,
		'day' => $v['day']
	));
}//_remind_add()
function _remind_zayav($zayav_id, $link_values='&p=remind') {//вывод напоминаний в заявке
	$data = _remind_spisok(array('zayav_id'=>$zayav_id));
	return
		'<script type="text/javascript">'.
			'var REMIND={'.
				'active:'.$data['active'].
			'};'.
		'</script>'.
		'<div class="headBlue">'.
			'<a href="'.URL.$link_values.'"><b>Напоминания</b></a>'.
			'<div class="img_add _remind-add'._tooltip('Новое напоминание', -60).'</div>'.
			($data['hidden'] ? '<a id="_remind-show-all">Показать все: '.$data['hidden'].'</a>' : '').
		'</div>'.
		'<div id="remind-spisok">'.$data['spisok'].'</div>';
}//_remind_zayav()
function _remind_active_to_ready_in_zayav($zayav_id) {//отметка активных напоминаний выполненными в заявках
	$sql = "SELECT * FROM `remind` WHERE `app_id`=".APP_ID." AND `status`=1 AND `zayav_id`=".$zayav_id;
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q)) {
		$sql = "UPDATE `remind` SET `status`=2 WHERE `id`=".$r['id'];
		query($sql, GLOBAL_MYSQL_CONNECT);

		_remind_history_add(array(
			'remind_id' => $r['id'],
			'status' => 2
		));
	}
}//_remind_active_to_ready_in_zayav()