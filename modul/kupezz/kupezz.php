<?php
function kupezz_index() {
	//получения списка стран для select
	$sql = "SELECT
				`country_id`,
				`country_name`
			FROM `kupezz_ob`
			WHERE !`deleted`
			  AND `country_id`
			  AND `country_name`!=''
			  AND `day_active`>=DATE_FORMAT(NOW(), '%Y-%m-%d')
			GROUP BY `country_id`
			ORDER BY `country_name`";
	$country = query_ass($sql);

	//получения списка городов для select
	$sql = "SELECT
				`city_id`,
				`city_name`,
				`country_id`
			FROM `kupezz_ob`
			WHERE !`deleted`
			  AND `city_id`
			  AND `city_name`!=''
			  AND `day_active`>=DATE_FORMAT(NOW(), '%Y-%m-%d')
			GROUP BY `city_id`
			ORDER BY `city_name`";
	$q = query($sql);
	$sub = array();
	while($r = mysql_fetch_assoc($q)) {
		if(!isset($sub[$r['country_id']]))
			$sub[$r['country_id']] = array();
		$sub[$r['country_id']][] = '{uid:'.$r['city_id'].',title:"'.$r['city_name'].'"}';
	}
	$city = array();
	foreach($sub as $n => $sp)
		$city[] = $n.':['.implode(',', $sp).']';

	$js =
	'<script>'.
		'var VIEWER_LINK="'.addslashes(_viewer(VIEWER_ID, 'viewer_link')).'",'.
			'CITY_ID='._viewer(VIEWER_ID, 'viewer_city_id').','.
			'CITY_NAME="'.addslashes(_viewer(VIEWER_ID, 'viewer_city_name')).'",'.
			'COUNTRY_ID='._viewer(VIEWER_ID, 'viewer_country_id').','.
			'COUNTRIES='._selJson($country).','.
			'CITIES={'.implode(',', $city).'},'.
			'U={'.
				'photo:"'.addslashes(_viewer(VIEWER_ID, 'viewer_photo')).'"'.
			'};'.
	'</script>';

	define('COUNTRY_ID', count($country) == 1 ? key($country) : 0);

	if(@$_GET['d'] == 'my')
		return $js.kupezz_my();
	return $js.kupezz_ob();
}

function _kupezz_script() {
	if(APP_ID != 2881875)
		return '';

	return
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/kupezz/kupezz'.MIN.'.css?'.VERSION.'" />'.
		'<script src="'.API_HTML.'/modul/kupezz/kupezz'.MIN.'.js?'.VERSION.'"></script>';
}
function kupezzZayavObUpdate($zayav_id, $v) {//внесение/обновление обявления, которое вносится из редакции
	if(empty($v['zpu'][53]))
		return false;

	if(!$z = _zayavQuery($zayav_id, 1))
		return false;

	$sql = "SELECT IFNULL(MAX(`gazeta_nomer_id`),0)
			FROM `_zayav_gazeta_nomer`
			WHERE `zayav_id`=".$zayav_id;
	$gnMax = query_value($sql);



	//получение id рубрики на основании слова
	$sql = "SELECT `id`
			FROM `_setup_rubric`
			WHERE `app_id`=2881875
			  AND `name`='".addslashes(_rubric($z['rubric_id']))."'
			LIMIT 1";
	$rubric_id = query_value($sql);

	//получение id подрубрики на основании слова
	$sql = "SELECT `id`
			FROM `_setup_rubric_sub`
			WHERE `app_id`=2881875
			  AND `name`='".addslashes(_rubricSub($z['rubric_id_sub']))."'
			LIMIT 1";
	$rubric_id_sub = query_value($sql);



	$sql = "SELECT *
			FROM `kupezz_ob`
			WHERE `zayav_id`=".$zayav_id."
			LIMIT 1";
	if($r = query_assoc($sql)) {//обновление объявления
		$day_active = $gnMax ? "DATE_ADD('"._gn($gnMax, 'day_public')."',INTERVAL 30 DAY)" : "'0000-00-00'";
		$sql = "UPDATE `kupezz_ob`
		        SET `rubric_id`=".$rubric_id.",
					`rubric_id_sub`=".$rubric_id_sub.",
					`txt`='".addslashes($z['about'])."',
					`telefon`='".addslashes($z['phone'])."',
					`day_active`=".$day_active."
				WHERE `id`=".$r['id'];
		query($sql);

		return true;
	}


	//внесение нового объявления
	if(!$gnMax)
		return false;

	$sql = "INSERT INTO `kupezz_ob` (
				`rubric_id`,
				`rubric_id_sub`,
				`txt`,
				`telefon`,

				`country_id`,
				`country_name`,
				`city_id`,
				`city_name`,

				`day_active`,

				`zayav_id`
			) VALUES (
				".$rubric_id.",
				".$rubric_id_sub.",
				'".addslashes($z['about'])."',
				'".addslashes($z['phone'])."',

				1,
				'Россия',
				3644,
				'Няндома',

				DATE_ADD('"._gn($gnMax, 'day_public')."',INTERVAL 30 DAY),

				".$zayav_id."
			)";
	query($sql);

	return true;
}

function kupezz_ob() {//Главная страница с объявлениями
	$rubric = array(0 => 'Все объявления') + _rubric('ass');
	//Количество объявлений для каждой рубрики
	$sql = "SELECT
				`rubric_id`,
				COUNT(`id`) AS `count`
			FROM `kupezz_ob`
			WHERE !`deleted`
			  AND `day_active`>=DATE_FORMAT(NOW(), '%Y-%m-%d')
			GROUP BY `rubric_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$rubric[$r['rubric_id']] .= '<em>'.$r['count'].'</em>';

	$data = kupezz_ob_spisok();

	return
	'<div id="kupezz-ob">'.

		'<table class="td-find bs10 w100p">'.
			'<tr><td><div id="find"></div>'.
				'<td class="r"><button class="vk" onclick="kupezzObEdit()">Разместить объявление</button>'.
		'</table>'.

		'<div class="result">'.@$data['result'].'</div>'.

		'<table class="tabLR">'.
			'<tr><td class="left">'.@$data['spisok'].
				'<td class="right">'.
					'<div class="findHead region">Регион</div>'.
					'<input type="hidden" id="country_id" value="'.COUNTRY_ID.'" />'.
					'<div class="city-sel'.(COUNTRY_ID ? '' : ' dn').'">'.
						'<input type="hidden" id="city_id" />'.
					'</div>'.
					'<div class="findHead">Рубрики</div>'.
					_rightLink('rub', $rubric).
					'<input type="hidden" id="rubsub" value="0" />'.
					'<div class="findHead">Дополнительно</div>'.
					_check('withfoto', 'Только с фото').
			  (SA ? _check('nokupez', 'Не КупецЪ') : '').

		'</table>'.
	'</div>';
}
function kupezz_obFilter($v=array()) {
	$default = array(
		'page' => 1,
		'limit' =>20,
		'find' => '',
		'find_query' => 0,
		'country_id' => defined('COUNTRY_ID') ? COUNTRY_ID : 1,
		'city_id' => 0,
		'rubric_id' => 0,
		'rubric_id_sub' => 0,
		'withfoto' => 0,
		'nokupez' => 0
	);
	$filter = array(
		'page' => _num(@$v['page']) ? $v['page'] : $default['page'],
		'limit' => _num(@$v['limit']) ? $v['limit'] : $default['limit'],
		'find' => trim(@$v['find']),
		'find_query' => _num(@$v['find_query']),
		'country_id' => isset($v['country_id']) ? _num($v['country_id']) : $default['country_id'],
		'city_id' => _num(@$v['city_id']),
		'rubric_id' => _num(@$v['rubric_id']),
		'rubric_id_sub' => _num(@$v['rubric_id_sub']),
		'withfoto' => _num(@$v['withfoto']),
		'nokupez' => _num(@$v['nokupez']),
		'clear' => ''
	);
	foreach($default as $k => $r)
		if($r != $filter[$k]) {
			$filter['clear'] = '<button class="vk small red">Очистить фильтр</button>';
			break;
		}

	return $filter;
}
function kupezz_ob_spisok($v=array()) {
	$filter = kupezz_obFilter($v);
	$filter = _filterJs('KUPEZZ_OB', $filter);

	$cond = "!`deleted` AND `day_active`>=DATE_FORMAT(NOW(), '%Y-%m-%d')";

	if($filter['find'])
		$cond .= " AND `txt` LIKE '%".$filter['find']."%'";
	if($filter['country_id'])
		$cond .= " AND `country_id`=".$filter['country_id'];
	if($filter['city_id'])
		$cond .= " AND `city_id`=".$filter['city_id'];
	if($filter['rubric_id'])
		$cond .= " AND `rubric_id`=".$filter['rubric_id'];
	if($filter['rubric_id_sub'])
		$cond .= " AND `rubric_id_sub`=".$filter['rubric_id_sub'];
	if($filter['withfoto'])
		$cond .= " AND `image_id`";
	if(SA && $filter['nokupez'])
		$cond .= " AND !`zayav_id`";

	$sql = "SELECT COUNT(`id`) FROM `kupezz_ob` WHERE ".$cond;
	$all = query_value($sql);

/*
	//внесение поискового запроса
	if($page == 1 && $filter['find_query'] && $filter['find']) {
		$sql = "INSERT INTO `vk_ob_find_query` (
						`txt`,
						`rows`,
						`viewer_id_add`
					) VALUES (
						'".addslashes($filter['find'])."',
						".$all.",
						".VIEWER_ID."
					)";
		query($sql);
	}
*/

	$links = '<a href="'.URL.'&p=kupezz&d=my" class="my">Мои объявления</a>'.$filter['clear'];
	if(!$all)
		return array(
			'all' => 0,
			'result' => 'Объявлений не найдено.'.$links,
			'spisok' => $filter['js'].'<div class="_empty">Объявлений не найдено.</div>',
			'filter' => $filter
		);

	$send = array(
		'all' => $all,
		'result' => 'Показано '.$all.' объявлен'._end($all, 'ие', 'ия', 'ий').$links,
		'spisok' => $filter['js'],
		'filter' => $filter
	);

	$sql = "SELECT *
			FROM `kupezz_ob`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT "._startLimit($filter);
	$ob = query_arr($sql);

	foreach($ob as $r) {
		if($filter['find'])
			$r['txt'] = _findRegular($filter['find'], $r['txt']);

		$send['spisok'] .= kupezz_ob_unit($r);
	}

	$send['spisok'] .= _next($filter + array(
		'all' => $all
	));

	return $send;
}
function kupezz_ob_unit($r) {
	$r['txt'] = wordwrap($r['txt'], 40, ' ', 1);
	$r['txt'] = nl2br($r['txt']);
	$ex = explode('<br />', $r['txt']);
	$count = count($ex);
	$txt = array();
	for($n = 0; $n < ($count > 7 ? 7 : $count); $n++)
		$txt[] = $ex[$n];
	$txt = implode('<br />', $txt);

	$hidden = '';
	if($count > 7) {
		$txt_hidden = array();
		for($n = 7; $n < $count; $n++)
			$txt_hidden[] = $ex[$n];
		$hidden .= implode('<br />', $txt_hidden);
	}

	$ex = explode(' ', $txt);
	$count = count($ex);
	$txt = array();
	for($n = 0; $n < ($count > 40 ? 40 : $count); $n++)
		$txt[] = $ex[$n];
	$txt = implode(' ', $txt);
	if($count > 40) {
		$txt_hidden = array();
		for($n = 40; $n < $count; $n++)
			$txt_hidden[] = $ex[$n];
		$hidden = $hidden.' '.implode(' ', $txt_hidden);
	}

	if($hidden)
		$txt .= '<a class="full">Показать полностью..</a>'.
				'<span class="dop dn">'.$hidden.'</span>';

	return
	'<div class="ob-unit'.(isset($r['edited']) ? ' edited' : '').'" id="ob'.$r['id'].'" val="'.$r['id'].'">'.
		'<table class="utab">'.
			'<tr><td class="txt">'.
  ($r['image_id'] ? '<img src="'.$r['image_link'].'" />': '').
					'<a class="rub" val="'.$r['rubric_id'].'">'._rubric($r['rubric_id']).'</a><u>»</u>'.
					($r['rubric_id_sub'] ? '<a class="rubsub" val="'.$r['rubric_id'].'_'.$r['rubric_id_sub'].'">'._rubricSub($r['rubric_id_sub']).'</a><u>»</u>' : '').
					$txt.
					($r['telefon'] ? '<div class="tel">'.$r['telefon'].'</div>' : '').
			'<tr><td class="adres" colspan="2">'.
				($r['city_name'] ? $r['country_name'].', '.$r['city_name']  : '').
				($r['viewer_id_show'] ? _viewer($r['viewer_id_add'], 'viewer_link')  : '').
		'</table>'.
	'</div>';
}

function kupezz_my() {
	$data = kupezz_my_spisok();

	//активные объявления
	$sql = "SELECT COUNT(*)
			FROM `kupezz_ob`
			WHERE `viewer_id_add`=".VIEWER_ID."
			  AND !`deleted`
			  AND `day_active`>=DATE_FORMAT(NOW(),'%Y-%m-%d')";
	$active_count = query_value($sql);

	//архивные объявления
	$sql = "SELECT COUNT(*)
			FROM `kupezz_ob`
			WHERE `viewer_id_add`=".VIEWER_ID."
			  AND !`deleted`
			  AND `day_active`<DATE_FORMAT(NOW(),'%Y-%m-%d')";
	$archive_count = query_value($sql);

	$status = array(
		0 => 'Все объявления',
		1 => 'Активные<em>'.($active_count ? $active_count : '').'</em>',
		2 => 'В архиве<em>'.($archive_count ? $archive_count : '').'</em>',
	);
	return
	'<div id="kupezz-my">'.
		'<div class="path"><a href="'.URL.'&p=kupezz">КупецЪ</a> » Мои объявления</div>'.
		'<div class="result">'.$data['result'].'</div>'.
		'<table class="tabLR">'.
			'<tr><td class="left">'.$data['spisok'].
				'<td class="right">'.
					'<button class="vk w100p" onclick="kupezzObEdit()">Новое объявление</button>'.
					'<div id="find"></div>'.
					'<div class="findHead region">Статус объявления</div>'.
					_radio('status', $status, 0, 'l').
		'</table>'.
	'</div>';
}
function kupezz_myFilter($v=array()) {
	return array(
		'page' => _num(@$v['page']) ? _num($v['page']) : 1,
		'limit' => _num(@$v['limit']) ? _num($v['limit']) : 20,
		'find' => trim(@$v['find']),
		'status' => _num(@$v['status']),
		'viewer_id' => SA && _num(@$v['viewer_id']) ? _num($v['viewer_id']) : VIEWER_ID,
		'deleted' => SA ? _bool(@$v['deleted']) : 0
	);
}
function kupezz_my_spisok($v=array()) {
	$filter = kupezz_myFilter($v);
	$filter = _filterJs('KUPEZZ_MY', $filter);

	$cond = (SA && $filter['deleted'] ? '' : "!`deleted` AND ").
			"`viewer_id_add`=".$filter['viewer_id'];

	if($filter['find'])
		$cond .= " AND `txt` LIKE '%".$filter['find']."%'";

	switch($filter['status']) {
		case 1: $cond .= " AND !`deleted` AND `day_active`>=DATE_FORMAT(NOW(),'%Y-%m-%d')"; break;
		case 2: $cond .= " AND !`deleted` AND `day_active`<DATE_FORMAT(NOW(),'%Y-%m-%d')"; break;
		case 3: $cond .= " AND `deleted`"; break;
	}

	$sql = "SELECT COUNT(`id`) FROM `kupezz_ob` WHERE ".$cond;
	$all = query_value($sql);

	if(!$all)
		return array(
			'all' => 0,
			'result' => 'Объявлений не найдено.',
			'spisok' => $filter['js'].'<div class="_empty">Объявлений не найдено.</div>',
			'filter' => $filter
		);

	$send['all'] = $all;
	$send['result'] = 'Показан'._end($all, '', 'о').' '.$all.' объявлен'._end($all, 'ие', 'ия', 'ий');
	$send['filter'] = $filter;
	$send['spisok'] = $filter['js'];

	$sql = "SELECT *
			FROM `kupezz_ob`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT "._startLimit($filter);
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		if($filter['find'])
			$r['txt'] = _findRegular($filter['find'], $r['txt']);
		$send['spisok'] .= kupezz_my_unit($r);
	}

	$send['spisok'] .= _next($filter + array(
		'all' => $all
	));

	return $send;
}
function kupezz_my_unit($r) {
	$dayTime = !$r['deleted'] ? strtotime($r['day_active']) - strtotime(strftime('%Y-%m-%d')) + 86400 : 0;
	$dayLast = $dayTime > 0 ? floor($dayTime / 86400) : 0;
	return
	'<div class="ob-unit'.
			($r['deleted'] || $dayLast ? '' : ' archive').
			(isset($r['edited']) ? ' edited' : '').
			($r['deleted'] ? ' deleted' : '').'"'.
			' id="ob'.$r['id'].'">'.
		'<div class="edit">'.
			FullData($r['dtime_add'], 0, 1).
			'<span class="last">'.
				($r['deleted'] ?
					'удалено' :
					($dayLast ? 'Остал'._end($dayLast, 'ся ', 'ось ').$dayLast._end($dayLast, ' день', ' дня', ' дней') : 'в архиве')
				).
			'</span>'.
		(!$r['deleted'] ?
			'<div class="icon">'.
				'<div val="'.$r['id'].'" class="img_edit ob-edit'._tooltip('Редактировать', -48).'</div>'.
				(!SA || $r['viewer_id_add'] == VIEWER_ID ? '<div onclick="kupezzObMyDel('.$r['id'].')" class="img_del'._tooltip('Удалить', -27).'</div>' : '').
			'</div>'
		: '').
		'</div>'.
		'<table class="utab">'.
			'<tr><td class="txt">'.
					($r['image_id'] ? '<img src="'.$r['image_link'].'" class="_iview" val="'.$r['image_id'].'" />' : '').
					'<span class="rub">'._rubric($r['rubric_id']).'</span><u>»</u>'.
					($r['rubric_id_sub'] ? '<span class="rubsub">'._rubricSub($r['rubric_id_sub']).'</span><u>»</u>' : '').
					nl2br($r['txt']).
					($r['telefon'] ? '<div class="tel">'.$r['telefon'].'</div>' : '').
			'<tr><td class="adres" colspan="2">'.
				($r['city_name'] ? $r['country_name'].', '.$r['city_name']  : '').
				($r['viewer_id_show'] ? _viewer($r['viewer_id_add'], 'viewer_link')  : '').
		'</table>'.
	'</div>';
}
