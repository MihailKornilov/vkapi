<?php
function _imageValToList($arr, $type_id) {//������� ����������� � ������ �� ��������� ���������
	$sql = "SELECT *
			FROM `_image`
			WHERE !`deleted`
			  AND !`sort`
			  AND `".$type_id."`
			  AND `".$type_id."` IN ("._idsGet($arr).")";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$arr[$r[$type_id]]['image_small'] = '<img class="_iview" val="'.$r['id'].'" src="'.$r['path'].$r['small_name'].'">';

	foreach($arr as $id => $r)
		if(!isset($r['image_small']))
			$arr[$id]['image_small'] = '<img src="'.API_HTML.'/img/nofoto-s.gif">';

	return $arr;
}
function _imageValToZayav($arr) {//������� ����������� � ������ ������
	//������� ������������� ����������� ����� ������
	$sql = "SELECT *
			FROM `_image`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND !`sort`
			  AND `zayav_id` IN ("._idsGet($arr).")";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$arr[$r['zayav_id']]['image_small'] = '<img class="_iview" val="'.$r['id'].'" src="'.$r['path'].$r['small_name'].'">';

	//����� ����������� ������� ���������
	if($ids = _idsGet($arr, 'base_model_id')) {
		$sql = "SELECT *
				FROM `_image`
				WHERE !`deleted`
				  AND !`sort`
				  AND `model_id` IN (".$ids.")";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q)) {
			foreach($arr as $z) {
				if(isset($z['image_small']))
					continue;
				if($r['model_id'] == $z['base_model_id'])
					$arr[$z['id']]['image_small'] = '<img class="_iview" val="'.$r['id'].'" src="'.$r['path'].$r['small_name'].'">';
			}
		}
	}

	foreach($arr as $r)
		if(!isset($r['image_small']))
			$arr[$r['id']]['image_small'] = '<img src="'.API_HTML.'/img/nofoto-s.gif">';

	return $arr;
}
function _image200($v) {//����� ����������� ������� 200
	$cond = "!`deleted` AND !`sort`";
	$js = ''; //��������, ������� ������� �� ���� ����� �������� �����������

	//��������
	if($tovar_id = _num(@$v['tovar_id'])) {
		$cond .= " AND `tovar_id`=".$tovar_id;
		$js = 'tovar_id:'.$tovar_id;
	}

	$sql = "SELECT *
			FROM `_image`
			WHERE ".$cond."
			LIMIT 1";
	if(!$r = query_assoc($sql))
		return _imageNoFoto($js);

	$size = _imageResize($r['big_x'], $r['big_y'], 200, 320);
	return
	'<img class="_iview" '.
		 'val="'.$r['id'].'" '.
		 'width="'.$size['x'].'" '.
		 'height="'.$size['y'].'" '.
		 'src="'.$r['path'].$r['big_name'].'" '.
	'/>'.
	_imageBut200($js);
}
function _imageSmall($v) {//��������� ������ ���������� �����������
	$cond = "!`deleted` AND !`sort`";

	//������
	if($tovar_id = _num(@$v['tovar_id']))
		$cond .= " AND `tovar_id`=".$tovar_id;

	//������ ���������
	if($model_id = _num(@$v['model_id']))
		$cond .= " AND `model_id`=".$model_id;

	$sql = "SELECT *
			FROM `_image`
			WHERE ".$cond."
			LIMIT 1";
	if(!$r = query_assoc($sql))
		return _imageNoFotoSmall();

	return
	'<img class="_iview" '.
		 'val="'.$r['id'].'" '.
		 'src="'.$r['path'].$r['small_name'].'" '.
	'/>';
}
function _imageBut200($js) {//������ �������� ����������� ������� 200
	return '<a id="_image-but-200" onclick="_imageAdd({'.$js.'})">��������� �����������</a>';
}
function _imageNoFoto($js) {//������ ����������� 200�200 � ������������ ������ �������� �����
	return
	'<div id="_image-no-foto" onclick="_imageAdd({'.$js.'})">'.
		'<a id="_image-but">��������� �����������</a>'.
		'<img src="'.API_HTML.'/img/nofoto-b.gif" />'.
	'</div>';
}
function _imageNoFotoSmall() {//������ ����������� 80�80
	return '<img src="'.API_HTML.'/img/nofoto-s.gif" />';
}
function _imageX($x_cur, $y_cur, $x_new, $y_new) {//��������� ������ �������� �� ��������� �������� ������
	$arr = _imageResize($x_cur, $y_cur, $x_new, $y_new);
	return $arr['x'];
}
function _imageY($x_cur, $y_cur, $x_new, $y_new) {//��������� ������ �������� �� ��������� �������� ������
	$arr = _imageResize($x_cur, $y_cur, $x_new, $y_new);
	return $arr['y'];
}
function _imageResize($x_cur, $y_cur, $x_new, $y_new) {//��������� ������� ����������� � ����������� ���������
	$x = $x_new;
	$y = $y_new;
	// ���� ������ ������ ��� ����� ������
	if ($x_cur >= $y_cur) {
		if ($x > $x_cur) { $x = $x_cur; } // ���� ����� ������ ������, ��� ��������, �� X ������� ��������
		$y = round($y_cur / $x_cur * $x);
		if ($y > $y_new) { // ���� ����� ������ � ����� �������� ������ ��������, �� ������������� �� Y
			$y = $y_new;
			$x = round($x_cur / $y_cur * $y);
		}
	}

	// ���� ������ ������ ������
	if ($y_cur > $x_cur) {
		if ($y > $y_cur) { $y = $y_cur; } // ���� ����� ������ ������, ��� ��������, �� Y ������� ��������
		$x = round($x_cur / $y_cur * $y);
		if ($x > $x_new) { // ���� ����� ������ � ����� �������� ������ ��������, �� ������������� �� X
			$x = $x_new;
			$y = round($y_cur / $x_cur * $x);
		}
	}

	return array(
		'x' => $x,
		'y' => $y
	);
}
function _imageCookie($code) {//��������� cookie ����� �������� ����������� � �����
	/*
	���� ������:
		0 - �������� � ��������
		1 - �������� ������ �����
		2 - ������� ��������� �����������
		3 - ��������� ���������� ����������� �����������
		4 - �������� ������ �����
	*/

	setcookie('_uploaded', $code, time() + 3600, '/');
	if($code < 7)
		exit;

	exit;
}
function _imageNameCreate() {//������������ ����� ����� �� ��������� ��������
	$arr = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','1','2','3','4','5','6','7','8','9','0');
	$name = '';
	for($i = 0; $i < 10; $i++)
		$name .= $arr[rand(0,35)];
	return $name;
}
function _imageImCreate($im, $x_cur, $y_cur, $x_new, $y_new, $name) {//������ �����������
	$send = _imageResize($x_cur, $y_cur, $x_new, $y_new);

	$im_new = imagecreatetruecolor($send['x'], $send['y']);
	imagecopyresampled($im_new, $im, 0, 0, 0, 0, $send['x'], $send['y'], $x_cur, $y_cur);
	imagejpeg($im_new, $name, 80);
	imagedestroy($im_new);

	$send['size'] = filesize($name);

	return $send;
}

function _imageQuery($id, $withDel=0) {//������ ������ ������ �����������
	$withDel = $withDel ? '' : ' AND !`deleted`';
	$sql = "SELECT *
			FROM `_image`
			WHERE `id`=".$id.$withDel;
	return query_assoc($sql);
}
function _imageArr($id, $withDel=0) {//������ ����������� �� ��������
	if(!$im = _imageQuery($id, $withDel))
		return array();

	$withDel = $withDel ? '' : ' AND !`deleted`';
	$sql = "SELECT *
			FROM `_image`
			WHERE `model_id`=".$im['model_id']."
			  AND `zayav_id`=".$im['zayav_id']."
			  AND `tovar_id`=".$im['tovar_id']."
			  AND `manual_id`=".$im['manual_id']."
			  AND `note_id`=".$im['note_id']."
			  AND `comment_id`=".$im['comment_id']."
			  ".($im['key'] ? " AND `key`='".$im['key']."'" : '')."
			  ".$withDel."
			ORDER BY `sort`";
	return query_arr($sql);
}
