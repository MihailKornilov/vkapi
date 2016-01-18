<?php
function sa_global_index() {//вывод ссылок суперадминистратора для всех приложений
	$sql = "SELECT COUNT(`viewer_id`)
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID;
	$userCount = query_value($sql, GLOBAL_MYSQL_CONNECT);

	$sql = "SELECT COUNT(`id`) FROM `_ws` WHERE `app_id`=".APP_ID;
	$wsCount = query_value($sql, GLOBAL_MYSQL_CONNECT);

	return
	'<div class="path">'.sa_cookie_back().'Администрирование</div>'.
	'<div id="sa-index">'.
		'<h1>Global:</h1>'.
		'<a href="'.URL.'&p=sa&d=menu">Разделы главного меню</a>'.
		'<a href="'.URL.'&p=sa&d=history">История действий</a>'.
		'<a href="'.URL.'&p=sa&d=rule">Права сотрудников</a>'.
		'<a href="'.URL.'&p=sa&d=balans">Балансы</a>'.
		'<a href="'.URL.'&p=sa&d=zayav">Заявки</a>'.
		'<a href="'.URL.'&p=sa&d=color">Цвета</a>'.
		'<br />'.

		'<div><b>Организации и сотрудники:</b></div>'.
		'<a href="'.URL.'&p=sa&d=user">Пользователи ('.$userCount.')</a>'.
		'<a href="'.URL.'&p=sa&d=ws">Организации ('.$wsCount.')</a>'.
		'<br />'.

		(function_exists('sa_index') ? sa_index() : '').
	'</div>';
}
function sa_cookie_back() {//сохранение пути для возвращения на прежнюю страницу после посещения суперадмина
	if(!empty($_GET['pre_p'])) {
		$_COOKIE['pre_p'] = $_GET['pre_p'];
		$_COOKIE['pre_d'] = empty($_GET['pre_d']) ? '' : $_GET['pre_d'];
		$_COOKIE['pre_d1'] = empty($_GET['pre_d1']) ? '' : $_GET['pre_d1'];
		$_COOKIE['pre_id'] = empty($_GET['pre_id']) ? '' : $_GET['pre_id'];
		setcookie('pre_p', $_COOKIE['pre_p'], time() + 2592000, '/');
		setcookie('pre_d', $_COOKIE['pre_d'], time() + 2592000, '/');
		setcookie('pre_d1', $_COOKIE['pre_d1'], time() + 2592000, '/');
		setcookie('pre_id', $_COOKIE['pre_id'], time() + 2592000, '/');
	}
	$d = empty($_COOKIE['pre_d']) ? '' :'&d='.$_COOKIE['pre_d'];
	$d1 = empty($_COOKIE['pre_d1']) ? '' :'&d1='.$_COOKIE['pre_d1'];
	$id = empty($_COOKIE['pre_id']) ? '' :'&id='.$_COOKIE['pre_id'];
	return '<a href="'.URL.'&p='.@$_COOKIE['pre_p'].$d.$d1.$id.'">Назад</a> » ';
}
function sa_path($v1, $v2='') {
	return
		'<div class="path">'.
			sa_cookie_back().
			'<a href="'.URL.'&p=sa">Администрирование</a> » '.
			$v1.($v2 ? ' » ' : '').
			$v2.
		'</div>';
}


function sa_menu() {//управление историей действий
	return
		sa_path('Разделы главного меню').
		'<div id="sa-menu">'.
			'<div class="headName">Разделы меню<a class="add">Добавить</a></div>'.
			'<div id="spisok">'.sa_menu_spisok().'</div>'.
		'</div>';
}
function sa_menu_spisok() {
	$sql = "SELECT
				`ma`.`id`,
				`m`.`name`,
				`m`.`p`,
				`ma`.`show`
			FROM
				`_menu` `m`,
				`_menu_app` `ma`
			WHERE `m`.`id`=`ma`.`menu_id`
			  AND `ma`.`app_id`=".APP_ID."
			ORDER BY `ma`.`sort`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	if(!mysql_num_rows($q))
		return 'Список пуст.';

	$spisok = array();
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']] = $r;

	$send =
		'<table class="_spisok">'.
			'<tr><th class="name">Название'.
				'<th class="p">Текст для ссылки'.
				'<th class="show">Показывать<br />в приложении'.
				'<th class="ed">'.
		'</table>'.
		'<dl class="_sort" val="_menu_app">';
	foreach($spisok as $r)
		$send .= '<dd val="'.$r['id'].'">'.
		'<table class="_spisok">'.
			'<tr><td class="name">'.$r['name'].
				'<td class="p">'.$r['p'].
				'<td class="show">'._check('show'.$r['id'], '', $r['show']).
				'<td class="ed">'._iconEdit($r).
		'</table>';

	return $send;
}


function sa_history() {//управление историей действий
	$sql = "SELECT `id`,`name` FROM `_history_category` ORDER BY `sort`";
	$category = query_selJson($sql, GLOBAL_MYSQL_CONNECT);
	return
		'<script type="text/javascript">'.
			'var CAT='.$category.
		'</script>'.
		sa_path('История действий').
		'<div id="sa-history">'.
			'<div class="headName">'.
				'Константы истории действий'.
				'<a class="add const">Добавить константу</a>'.
				'<span> :: </span>'.
				'<a class="add" href="'.URL.'&p=sa&d=historycat">Настроить категории</a>'.
			'</div>'.
			'<div id="spisok">'.sa_history_spisok().'</div>'.
		'</div>';
}
function sa_history_spisok() {
	$sql = "SELECT * FROM `_history_type` ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	if(!mysql_num_rows($q))
		return 'Список пуст.';

	$spisok = array();
	while($r = mysql_fetch_assoc($q)) {
		$r['ids'] = array(); //список id категорий для _select
		$r['cats'] = array();//список категорий в словах (не основные категории)
		$spisok[$r['id']] = $r;
	}

	//количество внесенных записей для каждого типа истории действий
	$sql = "SELECT
				`type_id`,
				COUNT(`id`) `count`
			FROM `_history`
			WHERE `type_id`
			  AND `app_id`=".APP_ID."
			GROUP BY `type_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		if(isset($spisok[$r['type_id']]))
			$spisok[$r['type_id']]['count'] = $r['count'];


	//ассоциации для категорий
	$sql = "SELECT `id`,`name` FROM `_history_category`";
	$cat = query_ass($sql, GLOBAL_MYSQL_CONNECT);

	//деление списка на категории
	$sql = "SELECT `id`,`name`
			FROM `_history_category`
			ORDER BY `sort`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$category = array();
	while($r = mysql_fetch_assoc($q)) {
		$r['type'] = array();//список элементов $spisok, которые привязаны к категориям
		$category[$r['id']] = $r;
	}

	//внесение списка категорий
	$sql = "SELECT *
			FROM `_history_ids`
			ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q)) {
		$spisok[$r['type_id']]['ids'][] = $r['category_id'];
		if(!$r['main'])
			$spisok[$r['type_id']]['cats'][] = $cat[$r['category_id']];
	}

	//выделение основых категорий
	$sql = "SELECT *
			FROM `_history_ids`
			WHERE `main`
			ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q)) {
		$category[$r['category_id']]['type'][$r['type_id']] = $spisok[$r['type_id']];
		unset($spisok[$r['type_id']]);
	}

	$send =
		'<table class="_spisok">'.
			'<tr><th class="type_id">type_id'.
				'<th class="txt">Наименование'.
				'<th class="count">Кол-во'.
		'</table>';
	foreach($category as $r)
		$send .= sa_history_spisok_page($r['name'], $r['type']);

	$send .= sa_history_spisok_page('Без категории', $spisok);

	return $send;
}
function sa_history_spisok_page($name, $spisok) {//вывод списка конкретной категории
	if(empty($spisok))
		return '';

	ksort($spisok);

	$send =
		'<div class="cat-name">'.$name.'</div>'.
		'<table class="_spisok">';

	foreach($spisok as $r)
		$send .=
			'<tr><td class="type_id">'.$r['id'].
				'<td class="txt">'.
					'<textarea readonly id="txt'.$r['id'].'">'.$r['txt'].'</textarea>'.
					(!empty($r['cats']) ? '<div class="cats">'.implode('&nbsp;&nbsp;&nbsp;', $r['cats']).'</div>' : '').
				'<td class="count">'.(empty($r['count']) ? '' : $r['count']).
				'<td class="set">'.
					'<div class="img_edit" val="'.$r['id'].'"></div>'.
					//'<div class="img_del"></div>'.
					'<input type="hidden" id="ids'.$r['id'].'" value="'.implode(',', $r['ids']).'" />';
	$send .= '</table>';
	return $send;
}
function sa_history_cat() {//настройка категорий истории действий
	return
		sa_path('<a href="'.URL.'&p=sa&d=history">История действий</a>', 'Настройка категорий').
		'<div id="sa-history-cat">'.
			'<div class="headName">Категории истории действий<a class="add">Добавить</a></div>'.
			'<div id="spisok">'.sa_history_cat_spisok().'</div>'.
		'</div>';
}
function sa_history_cat_spisok() {
	$sql = "SELECT * FROM `_history_category` ORDER BY `sort`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	if(!mysql_num_rows($q))
		return 'Список пуст.';

	$spisok = array();
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']] = $r;

	$send =
		'<table class="_spisok">'.
			'<tr><th class="name">Наименование'.
				'<th class="js">use_js'.
				'<th class="set">'.
		'</table>'.
		'<dl class="_sort" val="_history_category">';
	foreach($spisok as $r)
		$send .=
			'<dd val="'.$r['id'].'">'.
				'<table class="_spisok">'.
					'<tr><td class="name">'.
							'<b>'.$r['name'].'</b>'.
							'<div class="about">'.$r['about'].'</div>'.
						'<td class="js">'.($r['js_use'] ? '+' : '').
						'<td class="set">'.
							'<div class="img_edit"></div>'.
							'<div class="img_del"></div>'.
				'</table>';
	$send .= '</dl>';
	return $send;
}


function sa_rule() {//управление историей действий
	return
		sa_path('Права сотрудников').
		'<div id="sa-rule">'.
			'<div class="headName">Права сотрудников<a class="add">Добавить</a></div>'.
			'<div id="spisok">'.sa_rule_spisok().'</div>'.
		'</div>';
}
function sa_rule_spisok() {
	$sql = "SELECT * FROM `_vkuser_rule_default` ORDER BY `key`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	if(!mysql_num_rows($q))
		return 'Список пуст.';

	$spisok = array();
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']] = $r;

	$send =
		'<table class="_spisok">'.
			'<tr><th>Константа'.
				'<th>Значение<br />для админа'.
				'<th>Значение<br />для сотрудника'.
				'<th>';
	foreach($spisok as $r)
		$send .=
			'<tr val="'.$r['id'].'">'.
				'<td class="key">'.
					'<b>'.$r['key'].'</b>'.
					'<div class="about">'.$r['about'].'</div>'.
				'<td class="admin">'._check('admin'.$r['id'], '', $r['value_admin']).
				'<td class="worker">'._check('worker'.$r['id'], '', $r['value_worker']).
				'<td class="set">'.
					'<div class="img_edit" val="'.$r['id'].'"></div>';
	//'<div class="img_del"></div>';
	$send .= '</table>';

	return $send;
}


function sa_balans() {//управление балансами
	return
		sa_path('Балансы').
		'<div id="sa-balans">'.
			'<div class="headName">'.
				'Управление балансами'.
				'<a class="add" id="category-add">Новая категория</a>'.
			'</div>'.
			'<div id="spisok">'.sa_balans_spisok().'</div>'.
		'</div>';
}
function sa_balans_spisok() {
	$sql = "SELECT * FROM `_balans_category` ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	if(!mysql_num_rows($q))
		return 'Список пуст.';

	$spisok = array();
	while($r = mysql_fetch_assoc($q)) {
		$r['action'] = array(); //список действий для каждой категории
		$spisok[$r['id']] = $r;
	}

	$sql = "SELECT * FROM `_balans_action`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['category_id']]['action'][] = $r;


	//количество внесенных записей для каждого действия
	$sql = "SELECT
				`action_id`,
				COUNT(`id`) `count`
			FROM `_balans`
			WHERE `app_id`=".APP_ID."
			GROUP BY `action_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$actionCount = array();
	while($r = mysql_fetch_assoc($q))
		$actionCount[$r['action_id']] = $r['count'];

	$send = '';
	foreach($spisok as $r) {
		$send .=
			'<table class="_spisok">'.
				'<tr><td colspan="4" class="head">'.
						'<b>'.$r['id'].'.</b> '.
						'<b class="c-name">'.$r['name'].'</b>'.
						_iconDel($r).
						'<div val="'.$r['id'].'" class="img_add m30'._tooltip('Добавить действие', -63).'</div>'.
						_iconEdit($r).
				sa_balans_action_spisok($r['action'], $actionCount).
			'</table>';
	}

	return $send;
}
function sa_balans_action_spisok($arr, $count) {
	if(empty($arr))
		return '';

	$send = '';
	foreach($arr as $r)
		$send .=
			'<tr val="'.$r['id'].'">'.
				'<td class="id">'.$r['id'].
				'<td class="name'.($r['minus'] ? ' minus' : '').'">'.$r['name'].
				'<td class="count">'.(empty($count[$r['id']]) ? '' : $count[$r['id']]).
				'<td class="ed">'.
					'<div class="img_edit balans-action-edit"></div>'.
					'<div class="img_del balans-action-del"></div>';

	return $send;
}



function sa_zayav() {//управление балансами
	/*
		Поля для отображения информации о заявке
		Какие поля участвуют в быстром поиске find
		Какие условия поиска выводить в списке заявок
		Что сохраняется в name заявки
		Показывать или нет изображение
		Включение-выключение функций:
			- формирование договора
			- печать квитации
			- составление счёта на оплату
	*/
	return
		sa_path('Настройки заявок').
		'<div id="sa-zayav">'.
			'<div class="headName">'.
				'Используемые поля'.
				'<a class="add" id="pole-add">Новое поле</a>'.
				'<tt>::</tt>'.
				'<a class="add" id="type-add">Новый вид заявки</a>'.
			'</div>'.
			'<div id="dopLinks">'.
				'<a class="link sel">Оборудование</a>'.
				'<a class="link">Картриджи</a>'.
			'</div>'.
			'<div id="pole-spisok">'.sa_zayav_pole_spisok().'</div>'.
		'</div>';
}
function sa_zayav_pole_spisok() {
	$sql = "SELECT
				*,
				0 `use_info`
			FROM `_zayav_setup`
			ORDER BY `id`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return 'Список пуст.';

	$sql = "SELECT *
			FROM `_zayav_setup_use`
			WHERE `app_id`=".APP_ID."
			  AND `type_id`=0";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['pole_id']]['use_info'] = 1;

	$send =
		'<table class="_spisok">'.
			'<tr><th>'.
				'<th>Наименование'.
				'<th>Константа'.
				'<th>use'.
				'<th>';
	foreach($spisok as $r)
		$send .=
			'<tr><td class="id">'.$r['id'].
				'<td><div class="name">'.$r['name'].'</div>'.
					'<div class="about">'.$r['about'].'</div>'.
				'<td class="const">'.$r['const'].
				'<td class="use">'._check('use'.$r['id'], '', $r['use_info']).
				'<td class="ed">'._iconEdit($r);
	$send .= '</table>';

	return $send;
}



function sa_color() {
	return
		sa_path('Цвета').
		'<div id="sa-color">'.
			'<div class="headName">'.
				'Цвета'.
				'<a class="add">Новый цвет</a>'.
			'</div>'.
			'<div id="spisok">'.sa_color_spisok().'</div>'.
		'</div>';
}
function sa_color_spisok() {
	$sql = "SELECT
				*,
				0 `zayav`,
				0 `zp`
			FROM `_setup_color`
			ORDER BY `name`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return 'Цвета не внесены.';

	$sql = "SELECT
				`color_id`,
				COUNT(`id`) `c`
			FROM `_zayav`
			WHERE `color_id`
			GROUP BY `color_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['color_id']]['zayav'] = $r['c'];

	$sql = "SELECT
				`color_dop`,
				COUNT(`id`) `c`
			FROM `_zayav`
			WHERE `color_dop`
			GROUP BY `color_dop`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['color_dop']]['zayav'] += $r['c'];

	$send =
		'<table class="_spisok">'.
			'<tr><th>Предлог'.
				'<th>Цвет'.
				'<th>Кол-во<br />заявок'.
				'<th>Кол-во<br />запчастей'.
				'<th>';
	foreach($spisok as $r) {
		$r['nodel'] = $r['zayav'] || $r['zp'];
		$send .=
			'<tr>'.
				'<td class="predlog">'.$r['predlog'].
				'<td class="name">'.$r['name'].
				'<td class="zayav">'.($r['zayav'] ? $r['zayav'] : '').
				'<td class="zp">'.($r['zp'] ? $r['zp'] : '').
				'<td class="ed">'._iconEdit($r)._iconDel($r);
	}
	$send .= '</table>';
	return $send;
}



function sa_user() {
	$data = sa_user_spisok();
	return
	sa_path('Пользователи').
	'<div id="sa-user">'.
		'<div class="result">'.$data['result'].'</div>'.
		'<table class="tabLR">'.
			'<tr><td class="left">'.$data['spisok'].
				'<td class="right">'.
		'</table>'.
	'</div>';
}
function sa_user_spisok() {
	$sql = "SELECT *
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			ORDER BY `dtime_add` DESC";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$all = mysql_num_rows($q);
	$send = array(
		'all' => $all,
		'result' => 'Показано '.$all.' пользовател'._end($all, 'ь', 'я', 'ей'),
		'spisok' => ''
	);
	while($r = mysql_fetch_assoc($q))
		$send['spisok'] .=
			'<div class="un" val="'.$r['viewer_id'].'">'.
				'<table class="tab">'.
					'<tr><td class="img"><a href="http://vk.com/id'.$r['viewer_id'].'" target="_blank"><img src="'.$r['photo'].'"></a>'.
						'<td class="inf">'.
							'<div class="dtime">'.
								'<div class="added'._tooltip('Дата добавления', 10).FullDataTime($r['dtime_add']).'</div>'.
								(substr($r['last_seen'], 0, 16) != substr($r['dtime_add'], 0, 16) ?
									'<div class="enter'._tooltip('Активность', 40).FullDataTime($r['last_seen']).'</div>'
								: '').
							'</div>'.
							'<a href="http://vk.com/id'.$r['viewer_id'].'" target="_blank"><b>'.$r['first_name'].' '.$r['last_name'].'</b></a>'.
							($r['ws_id'] ? '<a class="ws_id" href="'.URL.'&p=sa&d=ws&id='.$r['ws_id'].'">ws: <b>'.$r['ws_id'].'</b></a>' : '').
							($r['admin'] ? '<b class="adm">Админ</b>' : '').
							'<div class="city">'.$r['city_title'].($r['country_title'] ? ', '.$r['country_title'] : '').'</div>'.
							'<a class="action">Действия</a>'.
				'</table>'.
			'</div>';
	return $send;
}
function sa_user_tab_test($tab, $col, $viewer_id) {//проверка количества записей для пользователя в определённой таблице
	$sql = "SELECT COUNT(*)
			FROM information_schema.COLUMNS
			WHERE TABLE_SCHEMA='".MYSQL_DATABASE."'
			  AND TABLE_NAME='".$tab."'
			  AND COLUMN_NAME='".$col."'";
	if(query_value($sql)) {
		$sql = "SELECT COUNT(*)
				FROM `".$tab."`
				WHERE `".$col."`=".$viewer_id;
		return query_value($sql);
	}
	return 0;
}

function sa_ws() {
	$wsSpisok =
		'<tr><th>id'.
			'<th>Наименование'.
			'<th>Админ'.
			'<th>Дата создания';
	$sql = "SELECT * FROM `_ws` WHERE `app_id`=".APP_ID." ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$count = mysql_num_rows($q);
	while($r = mysql_fetch_assoc($q))
		$wsSpisok .=
			'<tr><td class="id">'.$r['id'].
				'<td class="name'.($r['deleted'] ? ' del' : '').'">'.
					'<a href="'.URL.'&p=sa&d=ws&id='.$r['id'].'">'.$r['name'].'</a>'.
					'<div class="city">'.$r['city_name'].($r['country_id'] != 1 ? ', '.$r['country_name'] : '').'</div>'.
				'<td>'._viewer($r['admin_id'], 'viewer_link').
				'<td class="dtime">'.FullDataTime($r['dtime_add']);

	return
	sa_path('Организации').
	'<div id="sa-ws">'.
		'<div class="count">Всего <b>'.$count.'</b> организац'._end($count, 'ая', 'ии', 'ий').'.</div>'.
		'<table class="_spisok">'.$wsSpisok.'</table>'.
	'</div>';
}
function sa_ws_tables() {//Таблицы, которые задействуются в мастерских
	$sql = "SHOW TABLES";
	$q = query($sql);
	$send = array();
	while($r = mysql_fetch_assoc($q)) {
		$v = $r[key($r)];
		if(query_value("SHOW COLUMNS FROM `".$v."` WHERE Field='ws_id'"))
			$send[$v] = $v;
	}

//	unset($send['vk_user']);
	return $send;
}
function sa_ws_info($id) {
	$sql = "SELECT * FROM `_ws` WHERE `app_id`=".APP_ID." AND `id`=".$id;
	if(!$ws = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		return sa_ws();

	$counts = '';
	foreach(sa_ws_tables() as $tab) {
		$c = query_value("SELECT COUNT(`id`) FROM `".$tab."` WHERE `ws_id`=".$ws['id']);
		if($c)
			$counts .= '<tr><td class="tb">'.$tab.':<td class="c">'.$c.'<td>';
	}

	$workers = '';
	if(!$ws['deleted']) {
		$sql = "SELECT *
				FROM `_vkuser`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".$ws['id']."
				  AND `worker`
				  AND `viewer_id`!=".$ws['admin_id'];
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		while($r = mysql_fetch_assoc($q))
			$workers .= _viewer($r['viewer_id'], 'viewer_link').'<br />';
	}

	return
	sa_path('Организации', $ws['name']).
	'<div id="sa-ws-info">'.
		'<div class="headName">Информация об организации</div>'.
		'<table class="tab">'.
			'<tr><td class="label">Наименование:<td><b>'.$ws['name'].'</b>'.
			'<tr><td class="label">Город:<td>'.$ws['city_name'].', '.$ws['country_name'].
			'<tr><td class="label">Дата создания:<td>'.FullDataTime($ws['dtime_add']).
			'<tr><td class="label">Статус:<td><div class="status'.($ws['deleted'] ? ' off' : '').'">'.($ws['deleted'] ? 'не ' : '').'активна</div>'.
			($ws['deleted'] ? '<tr><td class="label">Дата удаления:<td>'.FullDataTime($ws['dtime_del']) : '').
			'<tr><td class="label">Администратор:<td>'._viewer($ws['admin_id'], 'viewer_link').
			(!$ws['deleted'] && $workers ? '<tr><td class="label top">Сотрудники:<td>'.$workers : '').
		'</table>'.
		'<div class="headName">Действия</div>'.
		'<div class="vkButton ws_status_change" val="'.$ws['id'].'"><button>'.($ws['deleted'] ? 'Восстановить' : 'Деактивировать').' организацию</button></div>'.
		'<br />'.
		(!$ws['deleted'] && $ws['id'] != WS_ID ?
			'<div class="vkButton ws_enter" val="'.$ws['admin_id'].'"><button>Выполнить вход в эту организацию</button></div><br />'
		: '').
		'<div class="vkCancel ws_del" val="'.$ws['id'].'"><button style="color:red">Физическое удаление организации</button></div>'.
		'<div class="headName">Записи в базе</div>'.
		'<table class="counts">'.$counts.'</table>'.
		'<div class="headName">Счётчики</div>'.
		'<div class="vkButton ws_client_balans" val="'.$ws['id'].'"><button>Обновить балансы клиентов</button></div>'.
		'<br />'.
		'<div class="vkButton ws_zayav_balans" val="'.$ws['id'].'"><button>Обновить в заявках: начисления, платежи, наличие счетов</button></div>'.
	'</div>';
}
