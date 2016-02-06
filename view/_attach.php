<?php
function _attachValToList($arr) {//������� ������ �� ����� � ������ �� attach_id
	$ids = array();
	$arrIds = array();
	foreach($arr as $key => $r)
		if(!empty($r['attach_id'])) {
			$ids[$r['attach_id']] = 1;
			$arrIds[$r['attach_id']][] = $key;
		}
	if(empty($ids))
		return $arr;

	$sql = "SELECT *
			FROM `_attach`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `id` IN (".implode(',', array_keys($ids)).")";
	$attach = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	foreach($attach as $r) {
		foreach($arrIds[$r['id']] as $id) {
			$arr[$id] += array(
				'attach_link' => '<a href="'.$r['link'].'" class="_attach-link" val="'.$r['id'].'" target="_blank">'.$r['name'].'</a>',
			);
		}
	}

	return $arr;
}
function _attachJs($v=array()) {//��������� ������ �� ����� � javascript
	$v = array(
		'id' => _num(@$v['id']),
		'array' => _num(@$v['array']), //�������� ������ �������� ����� ajax
		'zayav_id' => _num(@$v['zayav_id'])
	);

	$sql = "SELECT *
			FROM `_attach`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID.
			($v['zayav_id'] ? " AND `zayav_id`=".$v['zayav_id'] : '').
			($v['id'] ? " AND `id`=".$v['id'] : '');
	$attach = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	$send = array();
	$array = array();
	foreach($attach as $r) {
		$send[] =
			$r['id'].':{'.
				'name:"'.addslashes($r['name']).'",'.
				'link:"'.addslashes($r['link']).'",'.
				'size:'.$r['size'].
			'}';
		$array[intval($r['id'])] = array(
			'name' => utf8($r['name']),
			'link' => $r['link'],
			'size' => $r['size']
		);
	}

	if($v['array'])
		return $array;

	return
	'<script type="text/javascript">'.
		'var ATTACH={'.implode(',', $send).'};'.
	'</script>';
}
function _attachArr($id) {//��������� ������ � ����� � ������� json ��� ajax
	$sql = "SELECT *
			FROM `_attach`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `id`=".$id;
	if($r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		return array(
			'name' => utf8($r['name']),
			'link' => $r['link'],
			'size' => $r['size']
		);

	return array();
}