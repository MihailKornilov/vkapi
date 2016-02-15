<?php
function _manual() {
	return _manual_main();
}

function _manual_path() {//����
	return
	'<div class="path">'.
		'<a href="'.URL.'">'._app('name').'</a>'.
		' � '.
		'����������� ������������'.
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
	$sql = "SELECT
				*,
				'' `sub`
			FROM `_manual_part`
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '�������� ���.';

	$spisok = _manual_part_sub($spisok);

	$send = '';
	foreach($spisok as $r) {
		$send .=
			'<div class="part">'.
				'<a class="part-head">'.
					'<span class="name">'.$r['name'].'</span>'.
					'<div val="'.$r['id'].'" class="img_add m30 part-sub-add'._tooltip('����� ���������', -58).'</div>'.
				'</a>'.
				'<div class="part-sub">'.
					$r['sub'].
				'</div>'.
			'</div>';
	}

	return $send;
}
function _manual_part_sub($spisok) {//���������� ����������� � ��������
	$sql = "SELECT * FROM `_manual_part_sub` ORDER BY `sort`";
	if(!$sub = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return $spisok;

	foreach($sub as $r) {
		$spisok[$r['part_id']]['sub'] .=
			'<a>'.
				$r['name'].
			'</a>';
	}

	return $spisok;
}
