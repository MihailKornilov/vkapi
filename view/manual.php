<?php
function _manual() {
	return _manual_main();
}

function _manual_path() {//путь
	return
	'<div class="path">'.
		'<a href="'.URL.'">'._app('name').'</a>'.
		' » '.
		'Руководство'.
	'</div>';
}
function _manual_main() {//главная страница
	return
	_manual_path().
	'<div id="manual">'.
		'<h1>Содержание'.
			(SA ? '<a class="add">Новый раздел</a>' : '').
		'</h1>'.
		'<div id="part-spisok">'._manual_part().'</div>'.
	'</div>';
}
function _manual_part() {//главная страница
	$sql = "SELECT * FROM `_manual_part` ORDER BY `sort`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return 'Разделов нет.';

	$send = '';
	foreach($spisok as $r) {
		$send .= '<a class="part">'.$r['name'].'</a>';
	}

	return $send;
}
