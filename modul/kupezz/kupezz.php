<?php
function kupezz_index() {
	return kupezz_ob();
}

function _kupezz_script() {
	if(APP_ID != 2881875)
		return '';

	return
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/kupezz/kupezz'.MIN.'.css?'.VERSION.'" />'.
		'<script src="'.API_HTML.'/modul/kupezz/kupezz'.MIN.'.js?'.VERSION.'"></script>';
}

function kupezz_ob() {//Главная страница с объявлениями
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
		$rubric[$r['rubric_id']] .= '<b>'.$r['count'].'</b>';

	$data = kupezz_ob_spisok(array(
		'country_id' => count($country) == 1 ? key($country) : 0
	));

	return
	'<script>'.
		'var VIEWER_LINK="'.addslashes(_viewer(VIEWER_ID, 'viewer_link')).'",'.
			'CITY_ID='._viewer(VIEWER_ID, 'viewer_city_id').','.
			'CITY_NAME="'.addslashes(_viewer(VIEWER_ID, 'viewer_city_name')).'",'.
			'COUNTRY_ID='._viewer(VIEWER_ID, 'viewer_country_id').','.
			'COUNTRIES='._selJson($country).','.
			'CITIES={'.implode(',', $city).'},'.
			'RUBRIC_SUB_ASS='._rubricSub('js_ass').','.
			'U={'.
				'photo:"'.addslashes(_viewer(VIEWER_ID, 'viewer_photo')).'"'.
			'};'.
	'</script>'.
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
					'<input type="hidden" id="country_id"'.(count($country) == 1 ? ' value="'.key($country).'"' : '').' />'.
					'<div class="city-sel'.(count($country) == 1 ? '' : ' dn').'">'.
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
		'country_id' => 1,
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
		'country_id' => _num(@$v['country_id']),
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
		$cond .= " AND !`gazeta_id`";

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

	$links = '<a href="'.URL.'&p=ob&d=my" class="my">Мои объявления</a>'.$filter['clear'];
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
