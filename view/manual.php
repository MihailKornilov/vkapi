<?php
function _manual() {
	return _manual_main();
}

function _manual_path() {//����
	return
	'<div class="path">'.
		'<a href="'.URL.'">'._app('name').'</a>'.
		' � '.
		'�����������'.
	'</div>';
}
function _manual_main() {//������� ��������
	return
	_manual_path().
	'<div id="manual">'.
		'<h1>����������'.
			(SA ? '<a class="add">����� ������</a>' : '').
		'</h1>'.
		'<div id="part-spisok">'._manual_part().'</div>'.
	'</div>';
}
function _manual_part() {//������� ��������
	$sql = "SELECT * FROM `_manual_part` ORDER BY `sort`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '�������� ���.';

	$send = '';
	foreach($spisok as $r) {
		$send .= '<a class="part">'.$r['name'].'</a>';
	}

	return $send;
}
