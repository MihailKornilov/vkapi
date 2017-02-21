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

	$sql = "SET NAMES `".constant($prefix.'MYSQL_NAMES')."`";
//	query($sql, $conn);

	mysql_query($sql, $conn) or die($sql.'<br />'.mysql_error());

	define($prefix.'MYSQL_CONNECT', $conn);

	_debugLoad('���� ������ ����������');
}
function query($sql, $resource_id=GLOBAL_MYSQL_CONNECT) {
	global $sqlQuery, $sqlTime;
	$t = microtime(true);
	$res = mysql_query($sql, $resource_id ? $resource_id : GLOBAL_MYSQL_CONNECT) or die($sql.'<br />'.mysql_error());
	$t = microtime(true) - $t;

	$sqlTime += $t;
	$t = round($t, 3);
	$sqlQuery[] = '<li><a class="sql-un">'.trim(str_replace ('	', '',  $sql)).'</a><b class="t'.($t > 0.05 ? ' long' : '').'">'.$t.'</b>';
	if(mysql_insert_id() && strpos(strtoupper($sql), 'INSERT INTO') !== false)
		return mysql_insert_id();
	return $res;
}
function query_value($sql, $resource_id=GLOBAL_MYSQL_CONNECT) {
	$q = query($sql, $resource_id);
	if(!$r = mysql_fetch_row($q))
		return 0;
	return $r[0];
}
function query_assoc($sql, $resource_id=GLOBAL_MYSQL_CONNECT) {
	$q = query($sql, $resource_id);
	if(!$r = mysql_fetch_assoc($q))
		return array();
	return $r;
}
function query_ass($sql, $resource_id=GLOBAL_MYSQL_CONNECT) {//������������� ������
	$send = array();
	$q = query($sql, $resource_id);
	while($r = mysql_fetch_row($q))
		$send[$r[0]] = preg_match(REGEXP_NUMERIC, $r[1]) ? intval($r[1]) : $r[1];
	return $send;
}
function query_arr($sql, $resource_id=GLOBAL_MYSQL_CONNECT) {//������, ��� ������� �������� id
	$send = array();
	$q = query($sql, $resource_id);
	while($r = mysql_fetch_assoc($q))
		$send[$r['id']] = $r;
	return $send;
}
function query_selJson($sql, $resource_id=GLOBAL_MYSQL_CONNECT) {
	$send = array();
	$q = query($sql, $resource_id);
	while($sp = mysql_fetch_row($q))
		$send[] = '{'.
			'uid:'.$sp[0].','.
			'title:"'.addslashes(htmlspecialchars_decode(trim($sp[1]))).'"'.
		'}';
	return '['.implode(',',$send).']';
}
function query_workerSelJson($sql, $resource_id=GLOBAL_MYSQL_CONNECT) {//������ ����������� � ������� json ��� _select
	$send = array();
	$q = query($sql, $resource_id);
	while($r = mysql_fetch_array($q))
		$send[] = '{'.
			'uid:'.$r[0].','.
			'title:"'._viewer($r[0], 'viewer_name').'"'.
		'}';
	return '['.implode(',',$send).']';
}
function query_selArray($sql, $resource_id=GLOBAL_MYSQL_CONNECT) {//������ ��� _select ��� �������� ����� ajax
	$send = array();
	$q = query($sql, $resource_id);
	while($sp = mysql_fetch_row($q))
		$send[] = array(
			'uid' => $sp[0],
			'title' => utf8(htmlspecialchars_decode(trim($sp[1])))
		);
	return $send;
}
function query_selMultiArray($sql) {//������������� ������ ��� _select ��� �������� ����� ajax � ���� ������������
	/*
		���������� ������� �� �����.
		������ �������:
			SELECT `id`,`name`,`org_id`
		���� ������ ���� ������ �������

		{
			1:[{uid:1,title:'name111'},{uid:2,title:'name22'}],
			2:[{uid:5,title:'name33'}]
		}
	*/
	$send = array();
	$q = query($sql);
	while($r = mysql_fetch_row($q))
		$send[$r[2]][] = array(
			'uid' => $r[0],
			'title' => utf8(htmlspecialchars_decode(trim($r[1])))
		);
	return $send;
}
function query_assJson($sql, $resource_id=GLOBAL_MYSQL_CONNECT) {//������������� ������ js
	$q = query($sql, $resource_id);
	$send = array();
	while($sp = mysql_fetch_row($q))
		$send[] = $sp[0].':'.(preg_match(REGEXP_NUMERIC, $sp[1]) ? $sp[1] : '"'.$sp[1].'"');
	return '{'.implode(',', $send).'}';
}
function query_ids($sql, $resource_id=GLOBAL_MYSQL_CONNECT) {//������ ���������������
	$q = query($sql, $resource_id);
	$send = array();
	while($sp = mysql_fetch_row($q))
		$send[] = $sp[0];
	return empty($send) ? 0 : implode(',', array_unique($send));
}
function query_insert_id($tab, $resource_id=GLOBAL_MYSQL_CONNECT) {//id ���������� ��������� ��������
	$sql = "SELECT `id` FROM `".$tab."` ORDER BY `id` DESC LIMIT 1";
	return query_value($sql, $resource_id);
}

function _dbDump() {
	define('INSERT_COUNT_MAX', 500); //������� � ����� INSERT
	define('DUMP_NAME', GLOBAL_MYSQL_DATABASE.'_'.strftime('%Y-%m-%d_%H-%M-%S').'.sql');//������ �������� �����
	define('DUMP_FILE', API_PATH.'/'.DUMP_NAME); //������ ���� � ���������
	define('DUMP_FILE_ZIP', DUMP_FILE.'.zip');   //������ ���� ������������� ����� � ���������
	define('DUMP_NAME_ZIP', DUMP_NAME.'.zip');   //�������� ������������� �����

	$spisok = array();
	$sql = "SHOW TABLES";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_row($q))
		$spisok[] = $r[0];

	if(empty($spisok))
		return false;

	$fp = fopen(DUMP_FILE, 'w+');
	fwrite($fp, "                                                  \n\n");
	fwrite($fp, "SET NAMES `cp1251`;\n\n");

	foreach($spisok as $r)
		_dbDumpTable($fp, $r);

	fclose($fp);

	_dbDumpTime();
	_dbDumpZip();
	_dbDumpMail();

	unlink(DUMP_FILE);

	return true;
}
function _dbDumpTable($fp, $table) {
	fwrite($fp, "DROP TABLE IF EXISTS `".$table."`;\n");

	$sql = "SHOW CREATE TABLE `".$table."`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$r = mysql_fetch_row($q);
	fwrite($fp, $r[1].";\n");

	//��������� �������� ��������
	$sql = "DESCRIBE `".$table."`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$desc = array();
	while($r = mysql_fetch_assoc($q))
		array_push($desc, $r['Type']);

	$values = array();
	$sql = "SELECT * FROM `".$table."`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$count = 0;
	while($row = mysql_fetch_row($q)) {
		$count++;

		$cols = array();
		foreach($row as $n => $col)
			switch($desc[$n]) {
				case 'tinyint(3) unsigned': $cols[] = intval($col); break;
				case 'smallint(5) unsigned': $cols[] = intval($col); break;
				case 'int(10) unsigned': $cols[] = intval($col); break;
				case 'decimal(11,2)': $cols[] = round($col, 2); break;
				case 'decimal(11,2) unsigned': $cols[] = round($col, 2); break;
				default: $cols[] = '\'' . addslashes($col) . '\'';
			}

		$values[] = '('.implode(',', $cols).')';

		if($count >= INSERT_COUNT_MAX) {
			$count = _dbDumpInsert($fp, $table, $values);
			$values = array();
		}
	}
	_dbDumpInsert($fp, $table, $values);
	fwrite($fp, "\n\n\n");
}
function _dbDumpInsert($fp, $table, $values) {//�������� ����� INSERT � ����
	if(empty($values))
		return 0;

	$insert = "INSERT INTO `".$table."` VALUES \n".implode(",\n", $values).";\n";
	fwrite($fp, $insert);
	return 0;
}
function _dbDumpTime() {//������� ���� � ������� ���������� � ������ �����
	$fp = fopen(DUMP_FILE, 'r+');
	fwrite($fp, "#Dump created ".curTime()."\n");
	fwrite($fp, "#Time: ".round(microtime(true) - TIME, 3)."\n\n");
	fclose($fp);
	return true;
}
function _dbDumpZip() {//�������� ������ ����
	$zip = new ZipArchive();
	if($zip->open(DUMP_FILE_ZIP, ZIPARCHIVE::CREATE) !== true) {
	    echo 'Error while creating archive file';
	    return false;
	}
	$zip->addFile(DUMP_FILE, DUMP_NAME);
	$zip->close();

	return true;
}
function _dbDumpMail() {//�������� ������ �� �����
	//������ ���������� ������
	$file = fopen(DUMP_FILE_ZIP, 'r');
	$size = filesize(DUMP_FILE_ZIP);//��������� ������� �����
	$text = fread($file, $size);
	fclose($file);

	$from = 'global@dump';
	$subject = GLOBAL_MYSQL_DATABASE.' dump'; //����
	$boundary = '---'; //�����������

	$headers = "From: $from\nReply-To: $from\n".
			   'Content-Type: multipart/mixed; boundary="'.$boundary.'"';
	$body =
		"--$boundary\n".
		"Content-type: text/html; charset='windows-1251'\n".
		"Content-Transfer-Encoding: quoted-printablenn".
		"Content-Disposition: attachment;filename==?windows-1251?B?".base64_encode(DUMP_NAME_ZIP)."?=\n\n".

		//����� ���������
		"Size: "._sumSpace($size)." bytes.\n".
		"Time: ".round(microtime(true) - TIME, 3)."\n".

		"--$boundary\n".
		"Content-Type: application/octet-stream;name==?windows-1251?B?".base64_encode(DUMP_NAME_ZIP)."?=\n".
		"Content-Transfer-Encoding: base64\n".
		"Content-Disposition: attachment;filename==?windows-1251?B?".base64_encode(DUMP_NAME_ZIP)."?=\n\n".
		chunk_split(base64_encode($text))."\n".
		'--'.$boundary ."--\n";
	if(mail(CRON_MAIL, $subject, $body, $headers))
		unlink(DUMP_FILE_ZIP);
}
