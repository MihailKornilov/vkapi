<?php
function _imageAdd($v=array()) {
	$v = array(
		'txt' => empty($v['txt']) ? 'Добавить изображение' : $v['txt'],
		'owner' => empty($v['owner']) || !preg_match(REGEXP_WORD, $v['owner']) ? '' : $v['owner'],
		'max' => empty($v['max']) || !_num($v['owner']) ? 8 : $v['max'] // максимальное количество закружаемых изображений
	);
	return
		'<div class="_image-spisok">'._imageSpisok($v['owner']).'</div>'.
		'<div class="_image-error"></div>'.
		'<div class="_image-add">'.
			'<div class="_busy">&nbsp;</div>'.
			'<form method="post" action="'.AJAX_MAIN.'" enctype="multipart/form-data" target="_image-frame">'.
				'<input type="file" name="f1" />'.
				'<input type="file" name="f2" class="f2" />'.
				'<input type="file" name="f3" class="f3" />'.
				'<input type="hidden" name="op" value="image_add" />'.
				'<input type="hidden" name="owner" value="'.$v['owner'].'" />'.
				'<input type="hidden" name="max" value="'.$v['max'].'" />'.
			'</form>'.
			'<span>'.$v['txt'].'</span>'.
			'<iframe name="_image-frame"></iframe>'.
		'</div>';
}
function _imageSpisok($owner) {
	if(!$owner)
		return '';
	$sql = "SELECT * FROM `images` WHERE !`deleted` AND `owner`='".$owner."' ORDER BY `sort`";
	$q = query($sql);
	$send = '';
	while($r = mysql_fetch_assoc($q))
		$send .= '<a class="_iview" val="'.$r['id'].'">'.
					'<div class="del'._tooltip('Удалить', -29).'<em></em></div>'.
					'<img src="'.$r['path'].$r['small_name'].'" />'.
				'</a>';
	return $send;
}
function _imageCookie($v) {//Установка cookie после загрузки изображения и выход
	if(isset($v['error']))
		$cookie = 'error_'.$v['error'];
	else {
		$cookie = 'uploaded_';
		setcookie('_param', $v['link'].'_'.$v['id'].'_'.$v['max'], time() + 3600, '/');
	}
	setcookie('_upload', $cookie, time() + 3600, '/');
	exit;
}
function _imageNameCreate() {
	$arr = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','1','2','3','4','5','6','7','8','9','0');
	$name = '';
	for($i = 0; $i < 10; $i++)
		$name .= $arr[rand(0,35)];
	return $name;
}
function _imageResize($x_cur, $y_cur, $x_new, $y_new) {//изменение размера изображения с сохранением пропорций
	$x = $x_new;
	$y = $y_new;
	// если ширина больше или равна высоте
	if ($x_cur >= $y_cur) {
		if ($x > $x_cur) { $x = $x_cur; } // если новая ширина больше, чем исходная, то X остаётся исходным
		$y = round($y_cur / $x_cur * $x);
		if ($y > $y_new) { // если новая высота в итоге осталась меньше исходной, то подравнивание по Y
			$y = $y_new;
			$x = round($x_cur / $y_cur * $y);
		}
	}

	// если выстоа больше ширины
	if ($y_cur > $x_cur) {
		if ($y > $y_cur) { $y = $y_cur; } // если новая высота больше, чем исходная, то Y остаётся исходным
		$x = round($x_cur / $y_cur * $y);
		if ($x > $x_new) { // если новая ширина в итоге осталась меньше исходной, то подравнивание по X
			$x = $x_new;
			$y = round($y_cur / $x_cur * $x);
		}
	}

	return array(
		'x' => $x,
		'y' => $y
	);
}
function _imageImCreate($im, $x_cur, $y_cur, $x_new, $y_new, $name) {//сжатие изображения
	$send = _imageResize($x_cur, $y_cur, $x_new, $y_new);

	$im_new = imagecreatetruecolor($send['x'], $send['y']);
	imagecopyresampled($im_new, $im, 0, 0, 0, 0, $send['x'], $send['y'], $x_cur, $y_cur);
	imagejpeg($im_new, $name, 80);
	imagedestroy($im_new);

	return $send;
}
function _imageGet($v) {
	$v = array(
		'owner' => $v['owner'],
		'size' => isset($v['size']) ? $v['size'] : 's',
		'x' => isset($v['x']) ? $v['x'] : 10000,
		'y' => isset($v['y']) ? $v['y'] : 10000,
		'view' => isset($v['view']),
		'class' => isset($v['class']) ? $v['class'] : ''
	);

	$ownerArray = is_array($v['owner']);
	if(!$ownerArray)
		$v['owner'] = array($v['owner']);

	if(empty($v['owner']))
		return array();

	$v['owner'] = array_unique($v['owner']);
	$owner = array();
	foreach($v['owner'] as $val)
		$owner[] = preg_replace('/(\w+)/', '"$1"', $val, 1);

	$size = $v['size'] == 's' ? 'small' : 'big';
	$sql = "SELECT *
			FROM `images`
			WHERE !`deleted`
			  AND !`sort`
			  AND `owner` IN (".implode(',', $owner).")";
	$q = query($sql);
	$img = array();
	while($r = mysql_fetch_assoc($q)) {
		$s = 0;
		if($v['x'] != 10000 || $v['y'] != 10000)
			$s = _imageResize($r[$size.'_x'], $r[$size.'_y'], $v['x'], $v['y']);
		$img[$r['owner']] = array(
			'id' => $r['id'],
			'img' => '<img src="'.$r['path'].$r[$size.'_name'].'" '.
						($v['view'] ? 'class="_iview" val="'.$r['id'].'" ' : '').
						($s ? 'width="'.$s['x'].'" height="'.$s['y'].'" ' : '').
					 '/>'
		);
	}
	$s = 0;
	if($v['x'] != 10000 || $v['y'] != 10000)
		$s = _imageResize(!$size ? 80 : 200, !$size ? 80 : 200, $v['x'], $v['y']);
	foreach($v['owner'] as $val)
		if(empty($img[$val]))
			$img[$val] = array(
				'id' => 0,
				'img' => '<img src="'.API_HTML.'/img/nofoto-'.$v['size'].'.gif" '.($s ? 'width="'.$s['x'].'" height="'.$s['y'].'" ' : '').' />'
			);

	if($ownerArray)
		return $img;

	$img = array_shift($img);
	return $img['img'];
}
