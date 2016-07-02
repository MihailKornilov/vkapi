<?php
function sa_appCount() {
	$sql = "SELECT COUNT(`id`) FROM `_app`";
	return query_value($sql, GLOBAL_MYSQL_CONNECT);
}
function sa_userCount() {
	$sql = "SELECT COUNT(*)
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID;
	return query_value($sql, GLOBAL_MYSQL_CONNECT);
}

function sa_global_index() {//вывод ссылок суперадминистратора для всех приложений
	return
	'<div class="path">'.
		'<div id="app-id" val="'._app('app_name').'">'.APP_ID.'</div>'.
		sa_cookie_back().
		'Администрирование'.
	'</div>'.
	'<div id="sa-index">'.
		'<h1>Global:</h1>'.
		'<a href="'.URL.'&p=sa&d=menu">Разделы главного меню</a>'.
		'<a href="'.URL.'&p=sa&d=history">История действий</a>'.
		'<a href="'.URL.'&p=sa&d=rule">Права сотрудников</a>'.
		'<a href="'.URL.'&p=sa&d=balans">Балансы</a>'.
		'<a href="'.URL.'&p=sa&d=zayav">Заявки</a>'.
		'<a href="'.URL.'&p=sa&d=color">Цвета</a>'.
		'<a href="'.URL.'&p=sa&d=count">Счётчики</a>'.
		'<br />'.

		'<h1>App:</h1>'.
		'<a href="'.URL.'&p=sa&d=app">Приложения ('.sa_appCount().')</a>'.
		'<a href="'.URL.'&p=sa&d=user">Пользователи ('.sa_userCount().')</a>'.
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
			'<div id="app-id" val="'._app('app_name').'">'.APP_ID.'</div>'.
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
				`m`.`id` `menu_id`,
				`m`.`name`,
				`m`.`about`,
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
				'<th class="p">Link'.
				'<th class="show">App<br />show'.
				'<th class="ed">'.
		'</table>'.
		'<dl class="_sort" val="_menu_app">';
	foreach($spisok as $r)
		$send .= '<dd val="'.$r['id'].'">'.
		'<table class="_spisok">'.
			'<tr><td class="name" val="'.$r['menu_id'].'">'.
					'<span>'.$r['name'].'</span>'.
					'<div class="about">'.$r['about'].'</div>'.
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
				'<th>Админ'.
				'<th>Сотрудник'.
				'<th>';
	foreach($spisok as $r)
		$send .=
			'<tr val="'.$r['id'].'">'.
				'<td class="key">'.
					'<b>'.$r['key'].'</b>'.
					'<div class="about">'.$r['about'].'</div>'.
				'<td class="admin"><input type="text" id="admin'.$r['id'].'" value="'.$r['value_admin'].'" maxlength="3" />'.
				'<td class="worker"><input type="text" id="worker'.$r['id'].'" value="'.$r['value_worker'].'" maxlength="3" />'.
				'<td class="ed">'._iconEdit($r);
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



function sa_zayav() {//настройки заявок
	/*
		+ Поля для отображения информации о заявке
		- Какие поля участвуют в быстром поиске find
		+ Какие условия поиска выводить в списке заявок
		+ Что сохраняется в name заявки
		- Показывать или нет изображение
		+ Включение-выключение функций:
			- формирование договора
			- печать квитации
			- составление счёта на оплату
	*/
	switch(@$_GET['d1']) {
		case 'edit':   return sa_zayav_pole(1);
		case 'filter': return sa_zayav_pole(2);
		case 'info':   return sa_zayav_pole(3);
		case 'service': return sa_zayav_service();
	}

	return
		sa_path('Настройки заявок').
		'<div id="sa-zayav">'.
			'<div class="headName">Настройки заявок</div>'.
			'<a href="'.URL.'&p=sa&d=zayav&d1=edit">Поля - внесение/редактирование заявки</a>'.
			'<a href="'.URL.'&p=sa&d=zayav&d1=filter">Поля - фильтр заявок</a>'.
			'<a href="'.URL.'&p=sa&d=zayav&d1=info">Поля - информация о заявке</a>'.
			'<br />'.
			'<a href="'.URL.'&p=sa&d=zayav&d1=service">Виды деятельности заявок и использование полей</a>'.
		'</div>';
}
function sa_zayav_pole_type($type_id=0) {//типы полей заявок
	/*
		1 - edit: внесение/редактирование заявки
		2 - filter: фильтр заявок
		3 - info: информация о заявке
	*/
	$arr = array(
		1 => 'внесение/редактирование заявки',
		2 => 'фильтр заявок',
		3 => 'информация о заявке'
	);
	if($type_id)
		return $arr[$type_id];
	return $arr;
}
function sa_zayav_pole($type_id) {
	return
		'<script>'.
			'var SAZP_TYPE_ID='.$type_id.','.
				'SAZP_TYPE_NAME="'.sa_zayav_pole_type($type_id).'";'.
		'</script>'.
		sa_path('<a href="'.URL.'&p=sa&d=zayav">Настройки заявок</a>', sa_zayav_pole_type($type_id)).
		'<div id="sa-zayav-pole">'.
			'<div class="headName">'.
				'Настройки полей: '.sa_zayav_pole_type($type_id).
				'<a class="add" onclick="saZayavPoleEdit()">Новое поле</a>'.
			'</div>'.
			'<div id="spisok">'.sa_zayav_pole_spisok($type_id).'</div>'.
		'</div>';
}
function sa_zayav_pole_spisok($type_id, $sel=false) {//отображение списка всех полей заявки
	//$sel - возможность выбора для составления таблицы
	$sql = "SELECT *
			FROM `_zayav_pole`
			WHERE `type_id`=".$type_id."
			".($sel !== false ? " AND `id` NOT IN (".$sel.")" : '')."
			ORDER BY `id`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return 'Список пуст.';

	if($sel === false) {
		$sql = "SELECT
					`pole_id`,
					COUNT(`id`) `count`
				FROM `_zayav_pole_use`
				WHERE `pole_id` IN ("._idsGet($spisok).")
				GROUP BY `pole_id`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		while($r = mysql_fetch_assoc($q))
			$spisok[$r['pole_id']]['use'] = $r['count'];
	}

	$send =
		'<table class="_spisok">'.
			'<tr><th>'.
				'<th>Наименование'.
				'<th>Описание'.
	   ($sel === false ? '<th>use' : '').
	   ($sel === false ? '<th>' : '');
	foreach($spisok as $r)
		$send .=
			'<tr'.($sel !== false ? ' class="sel" val="'.$r['id'].'"' : '').'>'.
				'<td class="id">'.$r['id'].
				'<td class="name">'.$r['name'].
				'<td>'.
					'<div class="about">'.$r['about'].'</div>'.
   ($r['param1'] ? '<div class="param">'.$r['param1'].'</div>' : '').
	   ($sel === false ? '<td class="use">'.(@$r['use'] ? $r['use'] : '') : '').
	   ($sel === false ? '<td class="ed">'._iconEdit($r)._iconDel($r + array('nodel'=>_num(@$r['use']))) : '');
	$send .= '</table>';

	return $send;
}

function sa_zayav_service() {
	$link = sa_zayav_service_link();
	return
		sa_path('<a href="'.URL.'&p=sa&d=zayav">Настройки заявок</a>', 'Виды деятельности').
		'<div id="sa-zayav-service">'.
			'<div class="headName">'.
				'Виды деятельности заявок и использование полей'.
				'<a class="add" onclick="saServiceEdit()">Новый вид деятельности</a>'.
   (SERVICE_ID ?'<a class="add edit" val="'.SERVICE_ID.'">edit</a>' : '').
			'</div>'.
			$link.

			'<div class="zs-head">'.
				'Новая заявка'.
				'<button class="vk small" onclick="saZayavServicePoleAdd('.SERVICE_ID.',1)">Добавить поле</button>'.
				'<button class="vk small red" onclick="_zayavEdit('.SERVICE_ID.')">Предосмотр</button>'.
			'</div>'.
			'<table class="_spisok">'.
				'<tr><th class="pole">pole_id'.
					'<th class="head">Название поля'.
					'<th>'.
					'<th class="ed">'.
			'</table>'.
			'<dl id="spisok1" class="_sort" val="_zayav_pole_use">'.sa_zayav_service_use(1).'</dl>'.

			'<div class="zs-head">'.
				'Фильтр заявок'.
				'<button class="vk small" onclick="saZayavServicePoleAdd('.SERVICE_ID.',2)">Добавить поле</button>'.
			'</div>'.
			'<dl id="spisok2" class="_sort" val="_zayav_pole_use">'.sa_zayav_service_use(2).'</dl>'.

			'<div class="zs-head">'.
				'Информация о заявке'.
				'<button class="vk small" onclick="saZayavServicePoleAdd('.SERVICE_ID.',3)">Добавить поле</button>'.
			'</div>'.
			'<dl id="spisok3" class="_sort" val="_zayav_pole_use">'.sa_zayav_service_use(3).'</dl>'.
		'</div>';
}
function sa_zayav_service_link() {//меню списка видов заявок и получение SERVICE_ID
	$sql = "SELECT *
			FROM `_zayav_service`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT)) {
		define('SERVICE_ID', 0);
		return '';
	}

	if(!$id = _num(@$_GET['id']))
		$id = key($spisok);
	$exist = 0; //проверка, чтобы id вида заявки совпадал с существующими, иначе ставится по умолчанию
	foreach($spisok as $r)
		if($r['id'] == $id) {
			$exist = 1;
			break;
		}

	if(!$exist) {
		reset($spisok);
		$id = key($spisok);
	}

	$link = '';
	foreach($spisok as $r) {
		$sel = $r['id'] == $id ? ' sel' : '';
		$link .= '<a href="'.URL.'&p=sa&d=zayav&d1=service&id='.$r['id'].'" class="link'.$sel.'">'.$r['name'].'</a>';
	}

	define('SERVICE_ID', $id);

	return '<div id="dopLinks">'.$link.'</div>';
}
function sa_zayav_service_use($type_id, $show=0) {//использование полей для конкретного вида деятельности
	$sql = "SELECT
				`u`.`id`,
				`u`.`pole_id`,
				`zp`.`name`,
				`zp`.`about`,
				`u`.`label`,
				`u`.`require`,
				`zp`.`param1`,
				`u`.`param_v1`
			FROM
			    `_zayav_pole_use` `u`,
				`_zayav_pole` `zp`
			WHERE `app_id`=".APP_ID."
			  AND `service_id`=".SERVICE_ID."
			  AND `zp`.`id`=`u`.`pole_id`
			  AND `zp`.`type_id`=".$type_id."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return 'Поля не определены.';

	$send = '';
	foreach($spisok as $r)
		$send .=
		'<dd val="'.$r['id'].'">'.
			'<table class="_spisok'.($show == $r['id'] ? ' show' : '').'">'.
				'<tr><td class="pole">'.$r['pole_id'].
					'<td class="head">'.
						'<div class="name">'._br($r['label'] ? $r['label'] : $r['name']).($r['require'] ? ' *' : '').'</div>'.
						'<div class="label">'.($r['label'] ? $r['name'] : '').'</div>'.
						'<div class="about">'.$r['about'].'</div>'.
		($r['param1'] ? '<div class="param'.($r['param_v1'] ? ' on' : '').'">'.$r['param1'].'</div>' : '').
						'<input type="hidden" class="e-name" value="'.$r['name'].'" />'.
						'<input type="hidden" class="e-label" value="'.$r['label'].'" />'.
						'<input type="hidden" class="require" value="'.$r['require'].'" />'.
						'<input type="hidden" class="param1" value="'.$r['param1'].'" />'.
						'<input type="hidden" class="param_v1" value="'.$r['param_v1'].'" />'.
						'<input type="hidden" class="type_id" value="'.$type_id.'" />'.
					'<td>'.
					'<td class="ed">'._iconEdit($r)._iconDel($r).
			'</table>';

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





function sa_count() {
	return
		sa_path('Счётчики').
		'<div id="sa-count">'.
			'<div class="headName">Счётчики</div>'.
			'<button class="vk client">Клиенты</button>'.
			'<br />'.
			'<br />'.
			'<button class="vk zayav">Заявки</button>'.
			'<br />'.
			'<br />'.
			'<button class="vk tovar-set-find-update">Обновить find товаров-запчастей <em></em></button>'.
			'<br />'.
			'<br />'.
			'<button class="vk tovar-articul-update">Обновить артикулы товаров</button>'.
			'<br />'.
			'<br />'.
			'<button class="vk tovar-avai-check">Проверка корректности наличия товара</button>'.
		'</div>';
}





function sa_app() {
	return
		sa_path('Приложения').
		'<div id="sa-app">'.
			'<div class="headName">'.
				'Приложения'.
				'<a class="add">Новое приложение</a>'.
			'</div>'.
			'<div id="spisok">'.sa_app_spisok().'</div>'.
		'</div>';
}
function sa_app_spisok() {
	$sql = "SELECT
				*
			FROM `_app`
			ORDER BY `id`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return 'Приложений нет.';

	$send =
		'<table class="_spisok">'.
			'<tr><th>app_id'.
				'<th>Название'.
				'<th>title'.
				'<th>Дата создания'.
				'<th>';
	foreach($spisok as $r) {
		$send .=
			'<tr>'.
				'<td class="id">'.$r['id'].
					'<input type="hidden" class="secret" value="'.$r['secret'].'" />'.
				'<td class="app_name">'.(LOCAL ? '<a href="'.API_HTML.'/index.php'.'?api_id='.$r['id'].'&viewer_id='.VIEWER_ID.'">'.$r['app_name'].'</a>' : $r['app_name']).
				'<td class="title">'.$r['title'].
				'<td class="dtime">'._dtimeAdd($r).
				'<td class="ed">'._iconEdit($r);
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
		$c = query_value("SELECT COUNT(`id`) FROM `".$tab."`");
		if($c)
			$counts .= '<tr><td class="tb">'.$tab.':<td class="c">'.$c.'<td>';
	}

	$workers = '';
	if(!$ws['deleted']) {
		$sql = "SELECT *
				FROM `_vkuser`
				WHERE `app_id`=".APP_ID."
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
		(!$ws['deleted'] ?
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
