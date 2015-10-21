<?php
/*
1
����� ����� ������ {client_link}.

2
�������� ������ ������� {client_link}:#v1#

3
���������� ������� <i>{v1}</i> � {client_link}.

4
����� ������ <i>{client_name}</i>.

5
��������� ���������� ���� <u>{v1}</u> ��� ������� {client_link}.

6
������ {client_link}: �������� ������ ����������� ���� <u>{v1}</u>:#v2#

7
������ {client_link}: ������� ���������� ���� <i>{v1}</i>.



������
29
��������� ��������������� ���������� �� ������ {zayav_link}:#v1#

30
��������� �������� {zayav_link}:<div class="changes z">{v1}</div>

52
��������� ����� ���������� ������ {zayav_link}:#v1#

58
��������� ����������� �� ������ {zayav_link}:#v1#

62
������� ���������� ����������� �� ������ {zayav_link}.

71
������ ������ ������ {zayav_link}<br />{v1} � {v2}

72
��������������� ������ ������ {zayav_link}:#v1#

73
������� ����� ������ {zayav_link} ��� ������� {client_link}.

74
����������� ���������� �� ����� <b>{v1}</b> ���. ��� ������ {zayav_link}.



������
21
����� ������ �� ����� <b>{v1}</b> ���.




��������
13
����������� ��������� �������� {zp_link} �� ������ {zayav_link}.



17
����������� �������� {zp_link}.



���������
1001
� ����������: ��������� ������ ���������� {worker_link}:#v1#

1002
� ����������: ���������� {worker_link} �������� ������ � ����������.

1003
� ����������: ���������� {worker_link} �������� ������ � ����������.

1004
� ����������: ���������� {worker_link} ��������� �������� ������ ������ �����������.

1005
� ����������: ���������� {worker_link} ��������� �������� ������ ������ �����������.

1006
� ����������: ���������� {worker_link} ��������� ����������� ����� ������ �����������.

1007
� ����������: ���������� {worker_link} ��������� ����������� ����� ������ �����������.

1008
� ����������: ���������� {worker_link} ��������� �������� ��������� �����������.

1009
� ����������: ���������� {worker_link} ��������� �������� ��������� �����������.

1010
� ����������: ���������� {worker_link} ��������� ��������� ���������� �������.

1011
� ����������: ���������� {worker_link} ��������� ��������� ���������� �������.

1012
� ����������: ���������� {worker_link} ��������� ������������� ������� ��������.

1013
� ����������: ���������� {worker_link} ��������� ������������� ������� ��������.

1014
� ����������: ���������� {worker_link} ��������� ������ ������� ��������� �� ��������� ������.

1015
� ����������: ���������� {worker_link} ��������� ������ ������� ��������� �� ��������� ������.

1016
� ����������: ���������� {worker_link} ��������� ������ ������� ��������.

1017
� ����������: ���������� {worker_link} ��������� ������ ������� ��������.

1018
� ����������: ������� ���-��� � ���������� {worker_link}.




�������
999
{v1}



��� ���������
37
������ �/� �� ����� <b>{v1}</b> <em>({v2})</em> ��� ���������� <u>{worker_name}</u>.

45
��������� ������� �/� � ����� <b>{v1}</b> ���. ��� ���������� <u>{worker_name}</u>.

*/
function _history($v=array()) {
	if(isset($v['type_id']))
		return _history_insert($v);

	return _history_spisok($v);
}//_history()

function _history_insert($v=array()) {//�������� ������� ��������
	$sql = "INSERT INTO `_history` (
				`app_id`,
				`ws_id`,

				`type_id`,

				`client_id`,
				`zayav_id`,
				`zp_id`,
				`tovar_id`,
				`worker_id`,

				`v1`,
				`v2`,
				`v3`,
				`v4`,

				`viewer_id_add`
			) VALUES (
				".APP_ID.",
				".WS_ID.",

				".$v['type_id'].",

				"._num(@$v['client_id']).",
				"._num(@$v['zayav_id']).",
				"._num(@$v['zp_id']).",
				"._num(@$v['tovar_id']).",
				"._num(@$v['worker_id']).",

				'".addslashes(@$v['v1'])."',
				'".addslashes(@$v['v2'])."',
				'".addslashes(@$v['v3'])."',
				'".addslashes(@$v['v4'])."',

				".(_num(@$v['viewer_id']) ? $v['viewer_id'] : VIEWER_ID)."
			)";
	query($sql, GLOBAL_MYSQL_CONNECT);
	return true;
}//_history_insert()
function _historyFilter($v) {
	return array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'limit' => _num(@$v['limit']) ? $v['limit'] : 30,
		'viewer_id_add' => _num(@$v['viewer_id_add']),
		'category_id' => _num(@$v['category_id']),
		'client_id' => _num(@$v['client_id'])
	);
}//_historyFilter()
function _history_spisok($v=array()) {
	$filter = _historyFilter($v);

	define('PAGE1', $filter['page'] == 1);
	$spisok =
		PAGE1 ?
			'<script type="text/javascript">'.
				'var HIST={'.
						'limit:'.$filter['limit'].','.
						'viewer_id_add:'.$filter['viewer_id_add'].','.
						'category_id:'.$filter['category_id'].','.
						'client_id:'.$filter['client_id'].
					'};'.
			'</script>'
		: '';

	$cond = "`app_id`=".APP_ID.
	   " AND `type_id` IN (45)".//todo �������
	   " AND `ws_id`=".WS_ID;

	if($filter['viewer_id_add'])
		$cond .= " AND `viewer_id_add`=".$filter['viewer_id_add'];
	if($filter['category_id']) {
		$sql = "SELECT `type_id` FROM `_history_ids` WHERE `category_id`=".$filter['category_id'];
		$ids = query_ids($sql, GLOBAL_MYSQL_CONNECT);
		$cond .= " AND `type_id` IN (".$ids.")";
	}
	if($filter['client_id'])
		$cond .= " AND `client_id`=".$filter['client_id'];

	$add = $filter['client_id'] ? '' : '<div id="history-add" class="img_add m30'._tooltip('�������� �������', -60).'</div>';

	$sql = "SELECT COUNT(`id`) `all` FROM `_history` WHERE ".$cond;
	$all = query_value($sql, GLOBAL_MYSQL_CONNECT);
	if(!$all)
		return array(
			'all' => 0,
			'spisok' =>
				$spisok.
				(PAGE1 ? '<div class="result">������� �� ��������� �������� ���'.$add.'</div>' : '').
				'<div class="_empty">������� �� ��������� �������� ���</div>',
			'filter' => $filter
		);

	$send = array(
		'all' => $all,
		'spisok' =>
			(PAGE1 ? '<div class="result">�������'._end($all, '� ', '� ').$all.' �����'._end($all, '�', '�', '��').$add.'</div>' : '').
			$spisok,
		'filter' => $filter
	);

	$sql = "SELECT *
			FROM `_history`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT "._start($filter).",".$filter['limit'];
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$history = array();
	while($r = mysql_fetch_assoc($q))
		$history[$r['id']] = $r;

	$history = _viewerValToList($history);
	$history = _clientValToList($history);
	$history = _history_types($history);

	$txt = '';
	end($history);
	$keyEnd = key($history);
	reset($history);
	foreach($history as $r) {
		if(!$txt) {
			$time = strtotime($r['dtime_add']);
			$viewer_id = $r['viewer_id_add'];
		}
		$txt .= '<li class="light">'.(SA ? '<h4 val="'.$r['id'].'">'.($r['type_id_old'] ? $r['type_id_old'].'-' : '').$r['type_id'].'</h4>' : '').//todo ������� type_id_old ����� �����������
					'<div class="li">'.$r['txt'].'</div>';
		$key = key($history);
		if(!$key ||
			$key == $keyEnd ||
			$time - strtotime($history[$key]['dtime_add']) > 900 ||
			$viewer_id != $history[$key]['viewer_id_add']) {
			$send['spisok'] .=
				'<div class="_hist-un">'.
					'<table><tr>'.
				  ($viewer_id ? '<td class="hist-img">'.$r['viewer_photo'] : '').
								'<td>'.
					  ($viewer_id ? '<h5>'.$r['viewer_name'].'</h5>' : '').
									'<h6>'.FullDataTime($r['dtime_add']).(!$viewer_id ? '<span>cron</span>' : '').'</h6>'.
					'</table>'.
					'<ul>'.$txt.'</ul>'.
				'</div>';
			$txt = '';
		}
		next($history);
	}

	$send['spisok'] .= _next($filter + array(
			'all' => $all,
			'id' => '_hist-next'
		));
	return $send;
}//_history_spisok()
function _history_types($history) {//������� type_id � �����
	$types = array();
	foreach($history as $r)
		$types[$r['type_id']] = $r['type_id'];

	$sql = "SELECT * FROM `_history_type` WHERE `id` IN (".implode(',', $types).")";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$types[$r['id']] = $r['txt'];

	$str = array(
		'client_name',
		'client_link',
		'worker_name',
		'worker_link',
		'v1',
		'v2',
		'v3',
		'v4'
	);

	foreach($history as $id => $r) {
		$txt = $types[$r['type_id']];
		foreach($str as $v) {
			if(strpos($txt, '{'.$v.'}') !== false)
				$txt = str_replace('{'.$v.'}', $r[$v], $txt);
			if(strpos($txt, '#'.$v.'#') !== false)
				$txt = str_replace('#'.$v.'#', '<div class="changes">'.$r[$v].'</div>', $txt);
		}
		$history[$id]['txt'] = $txt;
	}

	return $history;
}//_history_types()



function _historyChange($name, $old, $new) {//������������ ������� �������, ���� ���� ��������� ��� �������������� ������
	if($old != $new)
		return '<tr><th>'.$name.':<td>'.$old.'<td>�<td>'.$new;
	return '';
}//_historyChange()

function _history_right() {//����� ������� ������ ��� ������� ��������
	$sql = "SELECT DISTINCT `viewer_id_add`
			FROM `_history`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `viewer_id_add`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$worker = array();
	while($r = mysql_fetch_assoc($q))
		$worker[] = '{'.
			'uid:'.$r['viewer_id_add'].','.
			'title:"'._viewer($r['viewer_id_add'], 'viewer_name').'"'.
		'}';

	$sql = "SELECT
	            `cat`.`id`,
				`cat`.`name`
			FROM
			 	`_history_category` `cat`,
				`_history_ids` `ids`,
				`_history` `h`
			WHERE `h`.`app_id`=".APP_ID."
			  AND `h`.`ws_id`=".WS_ID."
			  AND `cat`.`id`=`ids`.`category_id`
			  AND `h`.`type_id`=`ids`.`type_id`
			  AND `cat`.`js_use`
			GROUP BY `cat`.`id`
			ORDER BY `cat`.`sort`";
	$category = query_selJson($sql, GLOBAL_MYSQL_CONNECT);
	return
		'<div class="findHead">�������� ����������</div>'.
		'<input type="hidden" id="viewer_id_add" />'.

		(strlen($category) > 2 ? '<div class="findHead">���������</div>' : '').
		'<input type="hidden" id="category_id" />'.

		'<script type="text/javascript">'.
			'var HIST_WORKER=['.implode(',', $worker).'],'.
				'HIST_CAT='.$category.';'.
			'_historyRight();'.
		'</script>';
}//_history_right()

