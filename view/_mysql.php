<?php
function _dbConnect($prefix='') {
	global $sqlQuery;
	$sqlQuery = array();
	$conn = mysql_connect(
				constant($prefix.'MYSQL_HOST'),
				constant($prefix.'MYSQL_USER'),
				constant($prefix.'MYSQL_PASS'),
				1
			) or die("Can't connect to database");
	mysql_select_db(constant($prefix.'MYSQL_DATABASE'), $conn) or die("Can't select database");
	query('SET NAMES `'.constant($prefix.'MYSQL_NAMES').'`', $conn);
	define($prefix.'MYSQL_CONNECT', $conn);
}
function query($sql, $resource_id=MYSQL_CONNECT) {
	global $sqlQuery, $sqlTime;
	$t = microtime(true);
	$res = mysql_query($sql, $resource_id ? $resource_id : MYSQL_CONNECT) or die($sql.'<br />'.mysql_error());
	$t = microtime(true) - $t;

	$sqlTime += $t;
	$t = round($t, 3);
	$sqlQuery[] = '<li><a class="sql-un">'.trim(str_replace ('	', '',  $sql)).'</a><b class="t'.($t > 0.05 ? ' long' : '').'">'.$t.'</b>';
	if(mysql_insert_id() && strpos(strtoupper($sql), 'INSERT INTO') !== false)
		return mysql_insert_id();
	return $res;
}
function query_value($sql, $resource_id=MYSQL_CONNECT) {
	$q = query($sql, $resource_id);
	if(!$r = mysql_fetch_row($q))
		return 0;
	return $r[0];
}
function query_assoc($sql, $resource_id=MYSQL_CONNECT) {
	$q = query($sql, $resource_id);
	if(!$r = mysql_fetch_assoc($q))
		return array();
	return $r;
}
function query_ass($sql, $resource_id=MYSQL_CONNECT) {//Ассоциативный массив
	$send = array();
	$q = query($sql, $resource_id);
	while($r = mysql_fetch_row($q))
		$send[$r[0]] = preg_match(REGEXP_NUMERIC, $r[1]) ? intval($r[1]) : $r[1];
	return $send;
}
function query_arr($sql, $resource_id=MYSQL_CONNECT) {//Массив, где ключами является id
	$send = array();
	$q = query($sql, $resource_id);
	while($r = mysql_fetch_assoc($q))
		$send[$r['id']] = $r;
	return $send;
}
function query_selJson($sql, $resource_id=MYSQL_CONNECT) {
	$send = array();
	$q = query($sql, $resource_id);
	while($sp = mysql_fetch_row($q))
		$send[] = '{uid:'.$sp[0].',title:"'.addslashes(htmlspecialchars_decode($sp[1])).'"}';
	return '['.implode(',',$send).']';
}
function query_workerSelJson($sql, $resource_id=MYSQL_CONNECT) {//список сотрудников в формате json для _select
	$send = array();
	$q = query($sql, $resource_id);
	while($r = mysql_fetch_array($q))
		$send[] = '{'.
			'uid:'.$r[0].','.
			'title:"'._viewer($r[0], 'viewer_name').'"'.
		'}';
	return '['.implode(',',$send).']';
}
function query_selArray($sql, $resource_id=MYSQL_CONNECT) {//список для _select при отправке через ajax
	$send = array();
	$q = query($sql, $resource_id);
	while($sp = mysql_fetch_row($q))
		$send[] = array(
			'uid' => $sp[0],
			'title' => utf8(addslashes(htmlspecialchars_decode($sp[1])))
		);
	return $send;
}
function query_assJson($sql, $resource_id=MYSQL_CONNECT) {//Ассоциативный массив js
	$q = query($sql, $resource_id);
	$send = array();
	while($sp = mysql_fetch_row($q))
		$send[] = $sp[0].':'.(preg_match(REGEXP_NUMERIC, $sp[1]) ? $sp[1] : '"'.$sp[1].'"');
	return '{'.implode(',', $send).'}';
}
function query_ids($sql, $resource_id=MYSQL_CONNECT) {//Список идентификаторов
	$q = query($sql, $resource_id);
	$send = array();
	while($sp = mysql_fetch_row($q))
		$send[] = $sp[0];
	return empty($send) ? 0 : implode(',', array_unique($send));
}
function query_insert_id($tab, $resource_id=MYSQL_CONNECT) {//id последнего внесённого элемента
	$sql = "SELECT `id` FROM `".$tab."` ORDER BY `id` DESC LIMIT 1";
	return query_value($sql, $resource_id);
}
