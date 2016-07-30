<?php
function _noteQuery($note_id, $withDeleted=false) {//запрос данных по заметке
	$withDeleted = $withDeleted ? '' : ' AND !`deleted`';
	$sql = "SELECT *
			FROM `_note`
			WHERE `id`=".$note_id.$withDeleted;
	return query_assoc($sql);
}
function _noteFilter($v) {
	return array(
		'p' => empty($v['p']) ? @$_GET['p'] : _txt($v['p']),
		'id' => empty($v['id']) ? _num(@$_GET['id']) : _num($v['id']),
		'noapp' => _bool(@$v['noapp']), //независимо от приложения - заметка будет показываться во всех приложениях
		'last' => _bool(@$v['last']),
		'add' => _bool(@$v['add']),
		'empty' => _num(@$v['empty']), //возможность оставлять пустые комментарии (если присутсвует картинка)
		'comment' => _bool(@$v['comment']),
		'txt' => @$v['txt']
	);
}
function _noteArr($v) {//запрос массива заметок (для общего списка, либо отдельно для количества)
	$v = _noteFilter($v);

	$sql = "SELECT
				*,
				'' `comment`,
				'' `image`
			FROM `_note`
			WHERE !`deleted`
			  ".($v['noapp'] ? '' : "AND `app_id`=".APP_ID)."
			  AND `page_name`='".$v['p']."'
			  AND `page_id`=".$v['id']."
			ORDER BY `id` DESC";
	return query_arr($sql);
}
function _note($v=array()) {
	$v = _noteFilter($v);

	if($v['add'])
		return _noteAdd($v);

	if($v['last'])
		return _noteLast($v);

	$arr = _noteArr($v);

	return
	'<div class="_note" val="'.$v['p'].'#'.$v['id'].'">'.
		'<div class="_note-head">Заметки<tt>'._noteCount($arr).'</tt></div>'.
		_noteArea().
		_noteSpisok($arr).
	'</div>';
}
function _noteArea($note_id=0, $ph='Добавить заметку...') {
	return
	'<div class="_note-area-add" id="_note-area-'.$note_id.'" val="'.$note_id.'">'.
		'<div class="area">'.
			'<div class="area-image" val="'._imageNameCreate().'"></div>'.
			'<textarea class="_note-area" placeholder="'.$ph.'"></textarea>'.
		'</div>'.
		'<div class="_note-img"></div>'.
		'<button class="vk">Добавить</button>'.
	'</div>';
}
function _noteCount($arr) {//количество заметок
	if(!empty($arr['p']))//получение списка заметок (для обновления количества при внесении или удалении)
		$arr = _noteArr($arr);
	$count = count($arr);
	return $count ? 'Всего '.$count.' замет'._end($count, 'ка', 'ки','ок') : 'Заметок нет';
}
function _noteSpisok($arr) {//список заметок
	if(empty($arr))
		return '';

	$arr = _viewerValToList($arr);
	$arr = _noteImage($arr);
	$arr = _noteCommentSpisok($arr);

	$send = '';
	$n = 0;
	foreach($arr as $r) {
		$r['n'] = $n++;
		$send .= _noteUnit($r);
	}
	return $send;
}
function _noteUnit($r) {
	$n = _num(@$r['n']);//порядковый номер. Для определения первого элемента списка.
	$goComm = $r['comment_count'] ? 'Комментарии ('.$r['comment_count'].')' : 'Комментировать';
	return
	'<div class="nu" val="'.$r['id'].'">'.
		'<div class="nu-rest">Заметка удалена. <a>Восстановить</a></div>'.
		'<table class="nu-tab">'.
			'<tr><td class="nu-photo">'.$r['viewer_photo'].
				'<td class="nu-i">'.
					'<div class="img_del nu-del'._tooltip('Удалить заметку', -98, 'r').'</div>'.
					'<h3>'.$r['viewer_link'].'</h3>'.
//					'<h4>'.wordwrap(_br($r['txt']), 45, '<br />', true).$r['image'].'</h4>'.
					'<h4>'._br($r['txt']).$r['image'].'</h4>'.
					'<h5>'.
						FullDataTime($r['dtime_add'], 1).
						($n ? '<a class="nu-go-comm'.($r['comment_count'] ? ' ex' : '').'">'.$goComm.'</a>' : '').
					'</h5>'.
					'<h2'.($n ? ' class="dn"' : '').'>'.@$r['comment'].'</h2>'.
					'<div'.($n ? ' class="dn"' : '').'>'._noteArea($r['id'], 'Комментировать...').'</div>'.
		'</table>'.
	'</div>';
}

function _noteCommentSpisok($arr) {//прикрепление к списку заметок список комментариев
	$sql = "SELECT
				*,
				'' `image`
			FROM `_note_comment`
			WHERE !`deleted`
			  AND `note_id` IN ("._idsGet($arr).")
			ORDER BY `id` ASC";
	$comment = query_arr($sql);
	$comment = _viewerValToList($comment);
	$comment = _noteCommentImage($comment);

	foreach($comment as $r)
		$arr[$r['note_id']]['comment'] .= _noteCommentUnit($r);

	return $arr;
}
function _noteCommentUnit($r) {
	return
	'<div class="cu" val="'.$r['id'].'">'.
		'<div class="cu-rest">Комментарий удалён. <a>Восстановить</a></div>'.
		'<table class="cu-tab">'.
			'<tr><td class="cu-photo">'.$r['viewer_photo'].
				'<td class="cu-i">'.$r['viewer_link'].
					'<div class="img_del cu-del'._tooltip('Удалить комментарий', -126, 'r').'</div>'.
//					'<h4>'.wordwrap(_br($r['txt']), 40, '<br />', true).$r['image'].'</h4>'.
					'<h4>'._br($r['txt']).$r['image'].'</h4>'.
					'<h5>'.FullDataTime($r['dtime_add'], 1).'</h5>'.
		'</table>'.
	'</div>';
}
function _noteCommentCountUpdate($note_id) {//обновление количества комментариев к заметке
	$sql = "SELECT COUNT(`id`)
			FROM `_note_comment`
			WHERE !`deleted`
			  AND `note_id`=".$note_id;
	$count = query_value($sql);

	$sql = "UPDATE `_note`
			SET `comment_count`=".$count."
			WHERE `id`=".$note_id;
	query($sql);

	return $count;
}

function _noteAdd($v) {//внесение новой заметки
	if(empty($v['p']))
		return false;
	if(strlen($v['p']) > 100)
		return false;
	if(!$v['empty'] && empty($v['txt']))
		return false;

	//если разрешён комментарий, то попытка внесения комментария
	if(!empty($v['comment']) && _noteCommentAdd($v))
		return true;

	$sql = "INSERT INTO `_note` (
				`app_id`,
				`page_name`,
				`page_id`,
				`txt`,
				`viewer_id_add`
			) VALUES (
				".APP_ID.",
				'".$v['p']."',
				".$v['id'].",
				'".addslashes($v['txt'])."',
				".VIEWER_ID."
			)";
	query($sql);

	return true;
}
function _noteCommentAdd($v) {//внесение комментария к заметке
	$sql = "SELECT `id`
			FROM `_note`
			WHERE `app_id`=".APP_ID."
			  AND `page_name`='".$v['p']."'
			  AND `page_id`="._num(@$v['id'])."
			  AND !`deleted`
			ORDER BY `id` DESC
			LIMIT 1";
	if(!$note_id = query_value($sql))
		return false;

	$sql = "INSERT INTO `_note_comment` (
				`app_id`,
				`note_id`,
				`txt`,
				`viewer_id_add`
			) VALUES (
				".APP_ID.",
				".$note_id.",
				'".addslashes($v['txt'])."',
				".VIEWER_ID."
			)";
	query($sql);

	_noteCommentCountUpdate($note_id);

	return true;
}

function _noteLast($v) {
	$sql = "SELECT `txt`
			FROM `_note`
			WHERE `app_id`=".APP_ID."
			  AND `page_name`='".$v['p']."'
			  AND `page_id`="._num(@$v['id'])."
			  AND !`deleted`
			ORDER BY `id` DESC
			LIMIT 1";
	$txt = query_value($sql);
	return $txt ? htmlspecialchars_decode($txt) : '';
}

function _noteImageCount($key) {//получение количества изображений по ключу
	$sql = "SELECT COUNT(`id`)
			FROM `_image`
			WHERE !`deleted`
			  AND `key`='".$key."'";
	return query_value($sql);
}
function _noteImage($arr) {//вставка изображений в массив заметок
	$sql = "SELECT *
			FROM `_image`
			WHERE !`deleted`
			  AND `note_id` IN ("._idsGet($arr).")
			ORDER BY `sort`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$arr[$r['note_id']]['image'] .= '<img class="_iview" val="'.$r['id'].'" src="'.$r['path'].$r['big_name'].'">';

	return $arr;
}
function _noteCommentImage($arr) {//вставка изображений в массив комментариев
	if(empty($arr))
		return array();

	$sql = "SELECT *
			FROM `_image`
			WHERE !`deleted`
			  AND `comment_id` IN ("._idsGet($arr).")
			ORDER BY `sort`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$arr[$r['comment_id']]['image'] .= '<img class="_iview" val="'.$r['id'].'" src="'.$r['path'].$r['big_name'].'">';

	return $arr;
}

function _noteImageOne($note_id) {//получение изображений для конкретной заметки
	$sql = "SELECT *
			FROM `_image`
			WHERE !`deleted`
			  AND `note_id`=".$note_id."
			ORDER BY `sort`";
	$q = query($sql);
	$image = '';
	while($r = mysql_fetch_assoc($q))
		$image .= '<img class="_iview" val="'.$r['id'].'" src="'.$r['path'].$r['big_name'].'">';

	return array('image'=>$image);

}
function _noteCommentImageOne($comment_id) {//получение изображений для конкретного комментария
	$sql = "SELECT *
			FROM `_image`
			WHERE !`deleted`
			  AND `comment_id`=".$comment_id."
			ORDER BY `sort`";
	$q = query($sql);
	$image = '';
	while($r = mysql_fetch_assoc($q))
		$image .= '<img class="_iview" val="'.$r['id'].'" src="'.$r['path'].$r['big_name'].'">';

	return array('image'=>$image);

}
