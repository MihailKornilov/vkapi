<?php
function _imageValToList($arr, $type_id) {//вставка изображений в массив на основании параметра
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
function _imageValToZayav($arr) {//вставка изображений в массив заявок
	//сначала присваиваются изображения самих заявок
	$sql = "SELECT *
			FROM `_image`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND !`sort`
			  AND `zayav_id` IN ("._idsGet($arr).")";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$arr[$r['zayav_id']]['image_small'] = '<img class="_iview" val="'.$r['id'].'" src="'.$r['path'].$r['small_name'].'">';

	//далее изображения моделей устройств
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
function _image200($v) {//показ изображения шириной 200
	$cond = "!`deleted` AND !`sort`";
	$js = ''; //параметр, который передаёт на кого будет загрузка изображения

	//запчасти
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
function _imageSmall($v) {//получение одного маленького изображения
	$cond = "!`deleted` AND !`sort`";

	//товары
	if($tovar_id = _num(@$v['tovar_id']))
		$cond .= " AND `tovar_id`=".$tovar_id;

	//модель устройств
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
function _imageBut200($js) {//кнопка загрузки изображения шириной 200
	return '<a id="_image-but-200" onclick="_imageAdd({'.$js.'})">Загрузить изображение</a>';
}
function _imageNoFoto($js) {//пустое изображение 200х200 с возможностью выбора загрузки файла
	return
	'<div id="_image-no-foto" onclick="_imageAdd({'.$js.'})">'.
		'<a id="_image-but">Загрузить изображение</a>'.
		'<img src="'.API_HTML.'/img/nofoto-b.gif" />'.
	'</div>';
}
function _imageNoFotoSmall() {//пустое изображение 80х80
	return '<img src="'.API_HTML.'/img/nofoto-s.gif" />';
}
function _imageX($x_cur, $y_cur, $x_new, $y_new) {//получение ширины картинки на основании исходных данных
	$arr = _imageResize($x_cur, $y_cur, $x_new, $y_new);
	return $arr['x'];
}
function _imageY($x_cur, $y_cur, $x_new, $y_new) {//получение высоты картинки на основании исходных данных
	$arr = _imageResize($x_cur, $y_cur, $x_new, $y_new);
	return $arr['y'];
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

	// если высота больше ширины
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
function _imageCookie($code) {//Установка cookie после загрузки изображения и выход
	/*
	Коды ошибок:
		0 - загрузка в процессе
		1 - неверный формат файла
		2 - слишком маленькое изображение
		3 - превышено количество загружаемых изображений
		4 - превышен размер файла
	*/

	setcookie('_uploaded', $code, time() + 3600, '/');
	if($code < 7)
		exit;

	exit;
}
function _imageNameCreate() {//формирование имени файла из случайных символов
	$arr = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','1','2','3','4','5','6','7','8','9','0');
	$name = '';
	for($i = 0; $i < 10; $i++)
		$name .= $arr[rand(0,35)];
	return $name;
}
function _imageImCreate($im, $x_cur, $y_cur, $x_new, $y_new, $name) {//сжатие изображения
	$send = _imageResize($x_cur, $y_cur, $x_new, $y_new);

	$im_new = imagecreatetruecolor($send['x'], $send['y']);
	imagecopyresampled($im_new, $im, 0, 0, 0, 0, $send['x'], $send['y'], $x_cur, $y_cur);
	imagejpeg($im_new, $name, 80);
	imagedestroy($im_new);

	$send['size'] = filesize($name);

	return $send;
}

function _imageQuery($id, $withDel=0) {//запрос данных одного изображения
	$withDel = $withDel ? '' : ' AND !`deleted`';
	$sql = "SELECT *
			FROM `_image`
			WHERE `id`=".$id.$withDel;
	return query_assoc($sql);
}
function _imageArr($id, $withDel=0) {//массив изображений по критению
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
