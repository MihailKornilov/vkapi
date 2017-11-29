<?php
function sa_appCount() {
	$sql = "SELECT COUNT(`id`) FROM `_app`";
	return query_value($sql);
}
function sa_userCount() {
	$sql = "SELECT COUNT(*)
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `last_seen`!='0000-00-00 00:00:00'";
	return query_value($sql);
}

function _sa_script() {
	$id = _num(@$_GET['p']);
	if($id != 9 && _menuCache('parent_main_id', $id) != 9)
		return '';

	return
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/sa/sa'.MIN.'.css?'.VERSION.'" />'.
		'<script src="'.API_HTML.'/modul/sa/sa'.MIN.'.js?'.VERSION.'"></script>';
}
function sa_global_index() {//вывод ссылок суперадминистратора для всех приложений
	return
	saPath().

	'<div id="sa-index">'.
		'<h1>Global:</h1>'.
		'<a href="'.URL.'&p=30">Разделы меню</a>'.
		'<a href="'.URL.'&p=31">История действий</a>'.
		'<a href="'.URL.'&p=32">Права сотрудников</a>'.
		'<a href="'.URL.'&p=33">Балансы</a>'.
		'<a href="'.URL.'&p=34">Клиенты</a>'.
		'<a href="'.URL.'&p=35">Заявки</a>'.
		'<a href="'.URL.'&p=83">Товары и категории</a>'.
		'<a href="'.URL.'&p=36">Товары: единицы измерения</a>'.
		'<a href="'.URL.'&p=37">Цвета</a>'.
		'<a href="'.URL.'&p=38">Шаблоны документов</a>'.
		'<a href="'.URL.'&p=39">Счётчики</a>'.
		'<br />'.

		'<h1>App:</h1>'.
		'<a href="'.URL.'&p=40">Приложения ('.sa_appCount().'): <b>'._app('app_name').'</b></a>'.
		'<a href="'.URL.'&p=41">Пользователи ('.sa_userCount().')</a>'.
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
	return '<a class="link" href="'.URL.'&p='.@$_COOKIE['pre_p'].$d.$d1.$id.'">'._app('app_name').'</a> » ';
}
function saPath($v=array()) {//Верхняя строка: путь настроек
	$v = array(
		'name' => @$v['name'],          // название раздела
		'name_d' => @$v['name_d'],      // название подраздела
		'link_d' => _num(@$v['link_d']) // ссылка на предыдущий раздел
	);

	if($v['name_d'])
		$v['name_d'] = '<a class="link" href="'.URL.'&p='.$v['link_d'].'">'.$v['name_d'].'</a> » ';

	return
	'<div class="hd1">'.
		sa_cookie_back().
		($v['name'] ? '<a class="link" href="'.URL.'&p=9">Администрирование</a> » ' : 'Администрирование').
		$v['name_d'].
		$v['name'].
	'</div>';
}



function sa_menu() {//управление разделами меню
	return
	saPath(array('name'=>'Разделы меню')).

	'<div class="mar10">'.
		'<a href="'.URL.'&p=29" class="grey">'._app('app_name').': разделы в настройках</a>'.
	'</div>'.

	'<div id="sa-menu" class="mar10">'.
		'<div class="hd2">Главное меню'.
			'<button class="vk small fr" onclick="saMenuEdit()">Добавить раздел</button>'.
			'<div class="icon icon-empty fr"></div>'.
			'<div onclick="saMenuSort(0)" class="icon icon-sort fr'._tooltip('Сортировать корневые разделы', -98).'</div>'.
		'</div>'.
		'<div id="spisok">'.sa_menu_spisok().'</div>'.
	'</div>'.
	'<script>'.
		'var MENU_MAIN='._menuCache('main_js').','.
			'MENU_DOP='._menuCache('dop_js').';'.
	'</script>';
}
function sa_menu_spisok($edited_id=0) {
	$sql = "SELECT
				`m`.*,
				IFNULL(`ma`.`id`,0) `ma_id`,
				IFNULL(`ma`.`def`,0) `def`
			FROM `_menu` `m`

			LEFT JOIN `_menu_app` `ma`
			ON `m`.`id`=`ma`.`menu_id`
			AND `ma`.`app_id`=".APP_ID."
			
			ORDER BY `parent_id`,`m`.`sort`";
	$spisok = query_arr($sql);

	$send =
		'<table class="_spisokTab">'.
			'<tr><th class="w15">id'.
				'<th class="name">Название'.
				'<th class="w70">func<br />menu'.
				'<th class="w100">func<br />page'.
				'<th class="w50">dop<br />menu<br />type'.
				'<th class="w50">App<br />show'.
				'<th class="w50">user<br />access<br />default'.
				'<th class="w35">';
	foreach($spisok as $r) {
		if(!$r['parent_id'])
			$send .= sa_menu_unit($r, $edited_id);
		foreach($spisok as $dop) {
			if($r['id'] != $dop['parent_id'])
				continue;
			if(!_menuCache('parent_main', $dop['id']))
				continue;
			$send .= sa_menu_unit($dop, $edited_id);
			foreach($spisok as $three) {
				if($dop['id'] != $three['parent_id'])
					continue;
				$send .= sa_menu_unit($three, $edited_id, 1);
			}
		}
	}

	$send .= '</table>';

	return $send;
}
function sa_menu_unit($r, $edited_id, $three=0) {
	//подсветка наличия функции menu
	$fme = 'color-pay';
	if($r['func_menu'] && !function_exists($r['func_menu']))
		$fme = 'color-ref';

	//подсветка наличия функции страницы
	$fpe = 'color-pay';
	if($r['func_page'] && !function_exists($r['func_page']))
		$fpe = 'color-ref';

	$nameClass = 'fs15 b';
	if($r['parent_id'])
		$nameClass = 'fs12 ml20';
	if($three)
		$nameClass = 'fs11 ml40 grey';

	$norule = $r['norule'] ? ' bg-dfd' : '';
	
	return
		'<tr '.($edited_id == $r['id'] ? 'class="bge'.$norule.'" onmouseover="$(this).removeClass(\'bge\')"' : 'class="over1'.$norule.'"').'>'.
			'<td class="r grey fs11">'.$r['id'].
			'<td'.($r['hidden'] ? ' class="bg-eee"' : '').'>'.
				($r['def'] ? '<div class="icon icon-ok fr'._tooltip('Раздел по умолчанию', -68).'</div>' : '').
				'<div class="name '.$nameClass.'">'.$r['name'].'</div>'.
				'<div class="about grey fs11 '.($r['parent_id'] ? 'ml30' : '').'">'.$r['about'].'</div>'.
			'<td class="func_menu '.$fme.'">'.$r['func_menu'].
			'<td class="func_page b '.$fpe.'">'.$r['func_page'].
			'<td class="dop_menu_type center">'.($r['dop_menu_type'] ? $r['dop_menu_type'] : '').
			'<td class="show center">'._check('show'.$r['id'], '', $r['ma_id']).
			'<td class="access center">'.($norule ? '' : _check('access'.$r['id'], '', $r['viewer_access_default'])).
			'<td class="wsnw r top">'.
				(!$r['parent_id'] ? '<div onclick="saMenuSort('.$r['id'].')" class="icon icon-sort'._tooltip('Сортировка подразделов', -145, 'r').'</div>' : '').
				'<div class="icon icon-edit" val="'.$r['id'].'"></div>'.
		
			'<input type="hidden" class="parent_id" value="'._menuCache('parent_main_id', $r['id']).'" />'.
			'<input type="hidden" class="dop_id" value="'.(_menuCache('parent_main', $r['id']) ? 0 : $r['parent_id']).'" />'.
			'<input type="hidden" class="hidden" value="'.$r['hidden'].'" />'.
			'<input type="hidden" class="norule" value="'.$r['norule'].'" />'.
			'<input type="hidden" class="def" value="'.$r['def'].'" />';
}


function sa_history() {// 31 управление историей действий
	$sql = "SELECT `id`,`name` FROM `_history_category` ORDER BY `sort`";
	$category = query_selJson($sql);
	$sql = "SELECT MAX(`id`)+1 FROM `_history_type` WHERE `id`<500";
	$max = query_value($sql);
	return
		'<script>'.
			'var CAT='.$category.','.
				'TYPE_ID_MAX='.$max.';'.
		'</script>'.

		saPath(array('name'=>'История действий')).

		'<div id="sa-history">'.
			'<div class="hd2 mt20">'.
				'Константы истории действий'.
				'<button class="vk small fr" onclick="saHistoryEdit()">Добавить константу</button>'.
				'<span class="fr w15 center">::</span>'.
				'<a class="add" href="'.URL.'&p=69">Настройка категорий</a>'.
			'</div>'.
			'<div id="spisok" class="mar20">'.sa_history_spisok().'</div>'.
		'</div>';
}
function sa_history_spisok() {
	$sql = "SELECT * FROM `_history_type` ORDER BY `id`";
	$q = query($sql);
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
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		if(isset($spisok[$r['type_id']]))
			$spisok[$r['type_id']]['count'] = $r['count'];


	//ассоциации для категорий
	$sql = "SELECT `id`,`name` FROM `_history_category`";
	$cat = query_ass($sql);

	//деление списка на категории
	$sql = "SELECT `id`,`name`
			FROM `_history_category`
			ORDER BY `sort`";
	$q = query($sql);
	$category = array();
	while($r = mysql_fetch_assoc($q)) {
		$r['type'] = array();//список элементов $spisok, которые привязаны к категориям
		$category[$r['id']] = $r;
	}

	//внесение списка категорий
	$sql = "SELECT *
			FROM `_history_ids`
			ORDER BY `id`";
	$q = query($sql);
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
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		$category[$r['category_id']]['type'][$r['type_id']] = $spisok[$r['type_id']];
		unset($spisok[$r['type_id']]);
	}

	$send = '';
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
		'<div class="curP b mt10 pad10 over1" onclick="$(this).next().slideToggle(200)">'.
			$name.' '.
			'<span class="grey">('.count($spisok).')</span>'.
		'</div>'.
		'<div class="dn">'.
			'<table class="_spisokTab">'.
				'<tr><th class="w50">type_id'.
					'<th class="txt">Наименование'.
					'<th class="w50">Кол-во'.
					'<th class="w50">';

	foreach($spisok as $r)
		$send .=
			'<tr class="over3">'.
				'<td class="r grey">'.$r['id'].
				'<td><textarea class="txt w450 min curP" readonly>'.$r['txt'].'</textarea>'.
($r['txt_client'] ? '<div class="color-pay b mt10 ml8">Клиент:</div><textarea class="txt_client w450 min grey curP" readonly>'.$r['txt_client'].'</textarea>' : '').
($r['txt_zayav'] ? '<div class="color-pay b mt10 ml8">Заявка:</div><textarea class="txt_zayav w450 min grey curP" readonly>'.$r['txt_zayav'].'</textarea>' : '').
($r['txt_schet'] ? '<div class="color-pay b mt10 ml8">Счёт на оплату:</div><textarea class="txt_schet w450 min grey curP" readonly>'.$r['txt_schet'].'</textarea>' : '').
					(!empty($r['cats']) ? '<div class="fr fs11 color-vin">'.implode('&nbsp;&nbsp;&nbsp;', $r['cats']).'</div>' : '').
				'<td class="center">'.(empty($r['count']) ? '' : $r['count']).
				'<td>'.
					'<div class="icon icon-edit" val="'.$r['id'].'"></div>'.
					'<input type="hidden" class="ids" value="'.implode(',', $r['ids']).'" />';
	$send .= '</table>'.
		'</div>';
	return $send;
}
function sa_history_cat() {//настройка категорий истории действий
	return
		saPath(array(
			'name' => 'Категории истории действий',
			'name_d' => 'История действий',
			'link_d' => 31
		)).
		'<div id="sa-history-cat">'.
			'<div class="hd2 mt20">'.
				'Категории истории действий'.
				'<button class="vk small fr" onclick="saHistoryEdit()">Добавить категорию</button>'.
			'</div>'.
			'<div id="spisok">'.sa_history_cat_spisok().'</div>'.
		'</div>';
}
function sa_history_cat_spisok() {
	$sql = "SELECT * FROM `_history_category` ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return 'Список пуст.';

	$send =
		'<table class="_spisokTab">'.
			'<tr><th class="w15">id'.
				'<th>Наименование'.
				'<th class="w50">use_js'.
				'<th class="w50">'.
		'</table>'.
		'<dl class="_sort mb30" val="_history_category">';
	foreach($spisok as $r)
		$send .=
			'<dd val="'.$r['id'].'">'.
				'<table class="_spisokTab mt1">'.
					'<tr><td class="w15 grey r">'.$r['id'].
						'<td class="name curM">'.
							'<b>'.$r['name'].'</b>'.
							'<div class="about ml10 grey">'.$r['about'].'</div>'.
						'<td class="w50 js center">'.($r['js_use'] ? '+' : '').
						'<td class="w50">'.
							'<div class="icon icon-edit"></div>'.
							'<div class="icon icon-del"></div>'.
				'</table>';
	$send .= '</dl>';
	return $send;
}



function sa_rule() {// 32 Права сотрудников
	return
		saPath(array('name'=>'Права сотрудников')).
		'<div id="sa-rule" class="mar10">'.
			'<div class="hd2 mt20">'.
				'Права сотрудников'.
				'<button class="vk small fr" onclick="saRuleEdit()">Добавить</button>'.
			'</div>'.
			'<div id="spisok">'.sa_rule_spisok().'</div>'.
		'</div>';
}
function sa_rule_spisok() {
	$sql = "SELECT * FROM `_vkuser_rule_default` ORDER BY `key`";
	if(!$spisok = query_arr($sql))
		return 'Список пуст.';

	$send =
		'<table class="_spisokTab">'.
			'<tr><th class="w15">id'.
				'<th class="w200">Константа'.
				'<th>Имя<div class="fs14 grey">Описание</div>'.
				'<th class="w70">Админ'.
				'<th class="w70">Сотрудник'.
				'<th class="w15">';
	foreach($spisok as $r)
		$send .=
			'<tr class="over2" val="'.$r['id'].'">'.
				'<td class="r grey topi">'.$r['id'].
				'<td class="key b topi">'.$r['key'].
				'<td class="topi">'.
					'<div class="name b">'.$r['name'].'</div>'.
					'<div class="about grey ml10 mt5">'.$r['about'].'</div>'.
				'<td class="admin center"><input type="text" id="admin'.$r['id'].'"  class="w35 center b color-pay" value="'.$r['value_admin'].'" maxlength="3" />'.
				'<td class="worker center"><input type="text" id="worker'.$r['id'].'" class="w35 center b color-pay" value="'.$r['value_worker'].'" maxlength="3" />'.
				'<td class="wsnw">'.
					'<div class="icon icon-edit"></div>'.
					'<div class="icon icon-del"></div>';
	$send .= '</table>';

	return $send;
}



function sa_balans() {// 33 управление балансами
	return
		saPath(array('name'=>'Балансы')).
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
	$q = query($sql);
	if(!mysql_num_rows($q))
		return 'Список пуст.';

	$spisok = array();
	while($r = mysql_fetch_assoc($q)) {
		$r['action'] = array(); //список действий для каждой категории
		$spisok[$r['id']] = $r;
	}

	$sql = "SELECT * FROM `_balans_action`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['category_id']]['action'][] = $r;


	//количество внесенных записей для каждого действия
	$sql = "SELECT
				`action_id`,
				COUNT(`id`) `count`
			FROM `_balans`
			WHERE `app_id`=".APP_ID."
			GROUP BY `action_id`";
	$q = query($sql);
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




function sa_client() {// 34 настройки клиентов
	switch(@$_GET['d1']) {
		case 'edit':   return sa_client_pole(1);
		case 'info':   return sa_client_pole(3);
	}

	return
		saPath(array('name'=>'Настройки клиентов')).
		'<div id="sa-client">'.
			'<div class="hd2">Настройки клиентов</div>'.
			'<br />'.
			'<a href="'.URL.'&p=70&d1=edit">Поля - внесение/редактирование клиента</a>'.
			'<a href="'.URL.'&p=70&d1=info">Поля - информация о клиенте</a>'.
			'<br />'.
			'<a href="'.URL.'&p=71"><b>Категории клиентов и использование полей</b></a>'.
		'</div>';
}
function sa_client_pole_type($type_id=0) {//типы полей клиентов
	$arr = array(
		1 => 'внесение/редактирование клиента',
		3 => 'информация о клиенте'
	);
	if($type_id)
		return $arr[$type_id];
	return $arr;
}
function sa_client_pole($type_id) {
	return
		'<script>'.
			'var SA_CLIENT_POLE_TYPE_ID='.$type_id.','.
				'SA_CLIENT_POLE_TYPE_NAME="'.sa_client_pole_type($type_id).'";'.
		'</script>'.
		saPath(array(
			'name' => sa_client_pole_type($type_id),
			'name_d' => 'Настройки клиентов',
			'link_d' => 34
		)).
		'<div id="sa-client-pole">'.
			'<div class="headName">'.
				'Настройки полей: '.sa_client_pole_type($type_id).
				'<a class="add" onclick="saClientPoleEdit()">Новое поле</a>'.
			'</div>'.
			'<div id="spisok">'.sa_client_pole_spisok($type_id).'</div>'.
		'</div>';
}
function sa_client_pole_spisok($type_id, $sel=false) {//отображение списка всех полей клиента
	//$sel - возможность выбора для составления таблицы
	$sql = "SELECT *
			FROM `_client_pole`
			WHERE `type_id`=".$type_id."
			".($sel !== false ? " AND `id` NOT IN (".$sel.")" : '')."
			ORDER BY `id`";
	if(!$spisok = query_arr($sql))
		return 'Список пуст.';

	if($sel === false) {
		$sql = "SELECT
					`pole_id`,
					COUNT(`id`) `count`
				FROM `_client_pole_use`
				WHERE `pole_id` IN ("._idsGet($spisok).")
				GROUP BY `pole_id`";
		$q = query($sql);
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
	   ($sel === false ? '<td class="use">'.(@$r['use'] ? $r['use'] : '') : '').
	   ($sel === false ? '<td class="ed">'._iconEdit($r)._iconDel($r + array('nodel'=>_num(@$r['use']))) : '');
	$send .= '</table>';

	return $send;
}

function sa_client_category() {
	$link = sa_client_category_link();
	return
		saPath(array(
			'name' => 'Категории и поля',
			'name_d' => 'Настройки клиентов',
			'link_d' => 34
		)).
		'<div id="sa-client-category">'.
			'<div class="headName">'.
				'Категории клиентов и использование полей'.
				'<a class="add" onclick="saClientCategoryEdit()">Новая категория клиентов</a>'.
				'<a class="add edit" val="'.CLIENT_CATEGORY_ID.'">edit</a>'.
			'</div>'.

			$link.

			'<div class="hd">'.
				'Новый клиент'.
				'<button class="vk small" onclick="saClientCategoryPoleLoad('.CLIENT_CATEGORY_ID.',1)">Добавить поле</button>'.
				'<button class="vk small red" onclick="_clientEdit('.CLIENT_CATEGORY_ID.')">Предосмотр</button>'.
			'</div>'.
			'<table class="_spisok mb1">'.
				'<tr><th class="w50">pole_id'.
					'<th class="w250">Название поля'.
					'<th>Доп. параметры'.
					'<th class="ed">'.
			'</table>'.
			'<dl id="spisok1" class="_sort" val="_client_pole_use">'.sa_client_category_use(1).'</dl>'.

			'<div class="hd">'.
				'Информация о клиенте'.
				'<button class="vk small" onclick="saClientCategoryPoleLoad('.CLIENT_CATEGORY_ID.',3)">Добавить поле</button>'.
			'</div>'.
			'<dl id="spisok3" class="_sort" val="_client_pole_use">'.sa_client_category_use(3).'</dl>'.
		'</div>';
}
function sa_client_category_link() {//меню списка видов заявок и получение CLIENT_CATEGORY_ID
	_clientCategoryOneCheck();

	$sql = "SELECT *
			FROM `_client_category`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	$spisok = query_arr($sql);

	if(!$id = _num(@$_GET['id']))
		$id = key($spisok);

	$exist = 0; //проверка, чтобы id категории клиента совпадал с существующими, иначе ставится по умолчанию
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
		$link .= '<a href="'.URL.'&p=71&id='.$r['id'].'" class="link'.$sel.'">'.$r['name'].'</a>';
	}

	define('CLIENT_CATEGORY_ID', $id);

	return '<div id="dopLinks">'.$link.'</div>';
}
function sa_client_category_use($type_id, $show=0) {//использование полей для конкретного вида деятельности
	$sql = "SELECT
				`u`.`id`,
				`u`.`pole_id`,
				`cp`.`name`,
				`cp`.`about`,
				`u`.`label`,
				`u`.`require`
			FROM
			    `_client_pole_use` `u`,
				`_client_pole` `cp`
			WHERE `app_id`=".APP_ID."
			  AND `category_id`=".CLIENT_CATEGORY_ID."
			  AND `cp`.`id`=`u`.`pole_id`
			  AND `cp`.`type_id`=".$type_id."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return 'Поля не определены.';

	$send = '';
	foreach($spisok as $r)
		$send .=
		'<dd val="'.$r['id'].'">'.
			'<table class="_spisok mb1'.($show == $r['id'] ? ' show' : '').'">'.
				'<tr><td class="w50 r">'.$r['pole_id'].
					'<td class="w250 curM">'.
						'<div class="name">'._br($r['label'] ? $r['label'] : $r['name']).($r['require'] ? ' *' : '').'</div>'.
						'<div class="label">'.($r['label'] ? $r['name'] : '').'</div>'.
						'<div class="about">'.$r['about'].'</div>'.
						'<input type="hidden" class="e-name" value="'.$r['name'].'" />'.
						'<input type="hidden" class="e-label" value="'.$r['label'].'" />'.
						'<input type="hidden" class="require" value="'.$r['require'].'" />'.
						'<input type="hidden" class="type_id" value="'.$type_id.'" />'.
					'<td>'.
					'<td class="ed">'._iconEdit($r)._iconDel($r).
			'</table>';

	return $send;
}





function sa_zayav() {// 35 настройки заявок
	/*
		+ Поля для отображения информации о заявке
		- Какие поля участвуют в быстром поиске find
		+ Какие условия поиска выводить в списке заявок
		+ Что сохраняется в name заявки
		- Показывать или нет изображение
		+ Включение-выключение функций:
			+ формирование договора
			+ печать квитации
			+ составление счёта на оплату
	*/
	switch(@$_GET['d1']) {
		case 'edit':   return sa_zayav_pole(1);
		case 'unit': return sa_zayav_pole(4);
		case 'filter': return sa_zayav_pole(2);
		case 'info':   return sa_zayav_pole(3);
	}

	return
		saPath(array('name'=>'Настройки заявок')).
		'<div id="sa-zayav">'.
			'<div class="headName">Настройки заявок</div>'.
			'<a href="'.URL.'&p=72&d1=edit">Поля - внесение/редактирование заявки</a>'.
			'<a href="'.URL.'&p=72&d1=unit">Поля - единица списка заявок</a>'.
			'<a href="'.URL.'&p=72&d1=filter">Поля - фильтр заявок</a>'.
			'<a href="'.URL.'&p=72&d1=info">Поля - информация о заявке</a>'.
			'<br />'.
			'<a href="'.URL.'&p=73">Виды деятельности заявок и использование полей</a>'.
		'</div>';
}
function sa_zayav_pole_type($type_id=0) {//типы полей заявок
	/*
		1 - edit: внесение/редактирование заявки
		2 - filter: фильтр заявок
		3 - info: информация о заявке
		4 - unit: единица списка заявок
	*/
	$arr = array(
		1 => 'внесение/редактирование заявки',
		2 => 'фильтр заявок',
		3 => 'информация о заявке',
		4 => 'единица списка заявок'
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
		saPath(array(
			'name' => sa_zayav_pole_type($type_id),
			'name_d' => 'Настройки заявок',
			'link_d' => 35
		)).
		'<div id="sa-zayav-pole">'.
			'<div class="hd2 mar10">'.
				'Настройки полей: '.sa_zayav_pole_type($type_id).
				'<button class="vk small green fr" onclick="saZayavPoleEdit()">Новое поле</button>'.
			'</div>'.
			'<div id="spisok" class="mar10">'.sa_zayav_pole_spisok($type_id).'</div>'.
		'</div>';
}
function sa_zayav_pole_spisok($type_id, $sel=false) {//отображение списка всех полей заявки
	//$sel - возможность выбора для составления таблицы
	$sql = "SELECT *
			FROM `_zayav_pole`
			WHERE `type_id`=".$type_id."
			".($sel !== false ? " AND `id` NOT IN (".$sel.")" : '')."
			ORDER BY `id`";
	if(!$spisok = query_arr($sql))
		return 'Список пуст.';

	if($sel === false) {
		$sql = "SELECT
					`pole_id`,
					COUNT(`id`) `count`
				FROM `_zayav_pole_use`
				WHERE `pole_id` IN ("._idsGet($spisok).")
				GROUP BY `pole_id`";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q))
			$spisok[$r['pole_id']]['use'] = $r['count'];
	}

	$send =
		'<table class="_spisokTab">'.
			'<tr><th>'.
				'<th class="w200">Наименование'.
				'<th>Описание'.
	($sel === false ? '<th class="w70">task use' : '').
	($sel === false ? '<th class="w35">use' : '').
	($sel === false ? '<th class="">' : '');
	foreach($spisok as $r)
		$send .=
			'<tr class="'.($sel !== false ? 'over3 curP' : 'over1').'" val="'.$r['id'].'">'.
				'<td class="top r grey">'.$r['id'].
				'<td class="top name b">'.$r['name'].
				'<td><div class="top about grey fs11 wspl">'.$r['about'].'</div>'.
   ($r['param1'] ? '<div class="param1 fs11 red mt5">'.$r['param1'].'</div>' : '').
   ($r['param2'] ? '<div class="param2 fs11 red mt5">'.$r['param2'].'</div>' : '').
	   ($sel === false ? '<td'.($r['task_use'] ? ' class="bg-dfd"' : '').'>' : '').
	   ($sel === false ? '<td class="center">'.(@$r['use'] ? $r['use'] : '') : '').
	   ($sel === false ? '<td class="wsnw">'._iconEditNew($r)._iconDelNew($r + array('nodel'=>_num(@$r['use']))) : '').
					'<input type="hidden" class="task_use" value="'.$r['task_use'].'">';
	$send .= '</table>';

	return $send;
}

function sa_zayav_service() {
	$link = sa_zayav_service_link();
	return
		saPath(array(
			'name' => 'Виды деятельности',
			'name_d' => 'Настройки заявок',
			'link_d' => 35
		)).
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
				'Единица списка заявок'.
				'<button class="vk small" onclick="saZayavServicePoleAdd('.SERVICE_ID.',4)">Добавить поле</button>'.
			'</div>'.
			'<dl id="spisok4" class="_sort" val="_zayav_pole_use">'.sa_zayav_service_use(4).'</dl>'.

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
	if(!$spisok = query_arr($sql)) {
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
		$link .= '<a href="'.URL.'&p=73&id='.$r['id'].'" class="link'.$sel.'">'.$r['name'].'</a>';
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
				`u`.`param_v1`,
				`zp`.`param2`,
				`u`.`param_v2`
			FROM
			    `_zayav_pole_use` `u`,
				`_zayav_pole` `zp`
			WHERE `app_id`=".APP_ID."
			  AND `service_id`=".SERVICE_ID."
			  AND `zp`.`id`=`u`.`pole_id`
			  AND `zp`.`type_id`=".$type_id."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
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
		($r['param2'] ? '<div class="param'.($r['param_v2'] ? ' on' : '').'">'.$r['param2'].'</div>' : '').
						'<input type="hidden" class="e-name" value="'.$r['name'].'" />'.
						'<input type="hidden" class="e-label" value="'.$r['label'].'" />'.
						'<input type="hidden" class="require" value="'.$r['require'].'" />'.
						'<input type="hidden" class="param1"   value="'.$r['param1'].'" />'.
						'<input type="hidden" class="param_v1" value="'.$r['param_v1'].'" />'.
						'<input type="hidden" class="param2"   value="'.$r['param2'].'" />'.
						'<input type="hidden" class="param_v2" value="'.$r['param_v2'].'" />'.
						'<input type="hidden" class="type_id" value="'.$type_id.'" />'.
					'<td>'.
					'<td class="ed">'._iconEdit($r)._iconDel($r).
			'</table>';

	return $send;
}



function sa_tovar_cat() {
	$sql = "SELECT DISTINCT(`app_id`)
			FROM `_tovar_category`
			WHERE `app_id`";
	$ids = query_ids($sql);

	$sql = "SELECT `id`,`app_name`
			FROM `_app`
			WHERE `id` IN (".$ids.",".APP_ID.")";
	$app_json = query_selJson($sql);

	return
		saPath(array('name'=>'Товары и категории')).
		'<div id="sa-tovar-cat">'.
			'<div class="headName">Товары и категории</div>'.
			'<table class="ml20 bs5">'.
				'<tr><td>Приложения:'.
					'<td><input type="hidden" id="app-tovar" value="'.APP_ID.'" />'.
			'</table>'.
			'<div id="spisok" class="mh150 mar20">'.sa_tovar_cat_spisok().'</div>'.
		'</div>'.
		'<script>'.
			'var APP_JSON='.$app_json.';'.
		'</script>';
}
function sa_tovar_cat_spisok($app_id=APP_ID) {
	$sql = "SELECT
				*,
				0 `count`
			FROM `_tovar_category`
			WHERE `app_id`=".$app_id."
			ORDER BY `parent_id`,`sort`";
	if(!$arr = query_arr($sql))
		return 'категорий нет';

	$sql = "SELECT
				`category_id`,
				COUNT(*)
			FROM `_tovar_bind`
			WHERE `app_id`=".$app_id."
			GROUP BY `category_id`";
	$ct = query_ass($sql);

	$spisok = array();
	foreach($arr as $id => $r) {
		$count = _num(@$ct[$id]);
		if(!$r['parent_id']) {
			$spisok[$id] = array(
				'name' => $r['name'],
				'child' => array(),
				'count' => $count
			);
			continue;
		}
		if(!isset($spisok[$r['parent_id']]))
			continue;
		$spisok[$r['parent_id']]['child'][$id] = array(
			'name' => $r['name'],
			'count' => $count
		);
		$spisok[$r['parent_id']]['count'] += $count;
	}


	$send =// _pr($spisok).
		'<table class="_spisok">'.
			'<tr><th class="">Название'.
				'<th class="w50">Товары'.
				'<th class="w100">';
	foreach($spisok as $r) {
		$send .=
			'<tr><td class="b fs15">'.
					$r['name'].
				'<td class="center b">'.($r['count'] ? $r['count'] : '').
				'<td>';
		foreach($r['child'] as $child_id => $c) {
			$send .=
				'<tr><td>'.
						'<div class="ml30">'.$c['name'].'</div>'.
					'<td class="center grey">'.($c['count'] ? $c['count'] : '').
					'<td>'.
				($app_id != APP_ID ? '<div val="'.$child_id.'" class="icon icon-add'._tooltip('Добавить категорию с товарами<br>в текущее приложение', -99, '', 1).'</div>' : '');
		}
	}

	$send .= '</table>';

	return $send;
}


function sa_tovar_measure() {// 36 единицы измерения товаров
	return
		saPath(array('name'=>'Товары: единицы измерения')).
		'<div id="sa-measure">'.
			'<div class="headName">'.
				'Товары: единицы измерения'.
				'<a class="add" onclick="saTovarMeasureEdit()">Новая единица измерения</a>'.
			'</div>'.
			'<div id="spisok" class="mar20">'.sa_tovar_measure_spisok().'</div>'.
		'</div>';
}
function sa_tovar_measure_spisok() {
	$sql = "SELECT
				*,
				0 `tovar`
			FROM `_tovar_measure`
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return 'Список пуст.';

	$sql = "SELECT
				DISTINCT `measure_id`,
				COUNT(`id`) `count`
			FROM `_tovar`
			GROUP BY `measure_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['measure_id']]['tovar'] = $r['count'];

	$send =
		'<table class="_spisok mb1">'.
			'<tr><th class="td-name">Название'.
				'<th class="fraction w50">Дробь'.
				'<th class="area w70">Площадь'.
				'<th class="tovar w50">Товары'.
				'<th class="ed">'.
		'</table>'.
		'<dl class="_sort" val="_tovar_measure">';
	foreach($spisok as $r)
		$send .= '<dd val="'.$r['id'].'">'.
		'<table class="_spisok mb1">'.
			'<tr><td class="td-name curM" val="'.$r['id'].'">'.
					'<b class="short">'.$r['short'].'</b>'.
					($r['name'] ? ' - ' : '').
					'<span class="name">'.$r['name'].'</span>'.
					'<div class="about">'.$r['about'].'</div>'.
				'<td class="fraction center w50">'.($r['fraction'] ? 'да' : '').
				'<td class="area center w70">'.($r['area'] ? 'да' : '').
				'<td class="tovar center w50">'.($r['tovar'] ? $r['tovar'] : '').
				'<td class="ed">'.
					_iconEdit($r).
					_iconDel($r + array('nodel'=>$r['tovar'])).
		'</table>';

	$send .= '</dl>';

	return $send;
}




function sa_color() {// 37
	return
		saPath(array('name'=>'Цвета')).
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
	if(!$spisok = query_arr($sql))
		return 'Цвета не внесены.';

	$sql = "SELECT
				`color_id`,
				COUNT(`id`) `c`
			FROM `_zayav`
			WHERE `color_id`
			GROUP BY `color_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['color_id']]['zayav'] = $r['c'];

	$sql = "SELECT
				`color_dop`,
				COUNT(`id`) `c`
			FROM `_zayav`
			WHERE `color_dop`
			GROUP BY `color_dop`";
	$q = query($sql);
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




function sa_template() {// 38 управление переменными шаблонов документов
	return
		saPath(array('name'=>'Шаблоны документов')).
		'<div id="sa-template">'.
			'<div class="headName">'.
				'Шаблоны по умолчанию'.
				'<a class="add" onclick="saTemplateDefaultEdit()">добавить</a>'.
			'</div>'.
			'<div id="spisok-def">'.sa_template_default_spisok().'</div>'.

			'<div class="headName">'.
				'Переменные для шаблонов'.
				'<button class="vk small red fr" onclick="saTemplateVarEdit()">+ переменная</button>'.
				'<button class="vk small fr mr5" onclick="saTemplateGroupEdit()">Новая группа</button>'.
			'</div>'.
			'<div id="spisok-var">'.sa_template_spisok().'</div>'.
		'</div>';
}
function sa_template_default_spisok() {
	$sql = "SELECT *
			FROM `_template_default`
			ORDER BY `id`";
	if(!$spisok = query_arr($sql))
		return 'Шаблонов нет.';

	$spisok = _attachValToList($spisok);

	$n = 1;
	$send =
		_attachJs(array('id'=>_idsGet($spisok, 'attach_id'))).
		'<table class="_spisok">';
	foreach($spisok as $r) {
		$send .=
			'<tr><td class="w15 r grey">'.($n++).
				'<td class="w150">'.
					'<b class="name">'.$r['name'].'</b>'.
					'<br />'.
					$r['attach_link'].
					'<input type="hidden" class="attach_id" value="'.$r['attach_id'].'" />'.
				'<td><span class="grey">Текст ссылки:</span> '.
					'<span class="name_link">'.$r['name_link'].'</span>'.
					'<br />'.
					'<span class="grey">Имя файла:</span> '.
					'<span class="name_file">'.$r['name_file'].'</span>'.
				'<td class="use w100 grey">'.$r['use'].
				'<td class="ed">'._iconEdit($r);
	}

	$send .= '</table>';

	return $send;
}
function sa_template_spisok() {
	$sql = "SELECT *
			FROM `_template_var_group`
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return 'Данные не вносились.';

	foreach($spisok as $id => $r)
		$spisok[$id]['var'] = array();

	$sql = "SELECT *
			FROM `_template_var`
			ORDER BY `sort`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['group_id']]['var'][] = $r;

	$send = '';
	foreach($spisok as $r)
		$send .=
			'<div class="b mt20">'.$r['name'].':</div>'.
			sa_template_var($r['var'], $r['table_name']);

	return $send;
}
function sa_template_var($spisok, $table_name) {
	if(empty($spisok))
		return '';

	$send = '<table class="_spisokTab mar8">';
	foreach($spisok as $r) {
		$send .=
			'<tr><td class="w175 b">'.$r['v'].
				'<td>'.$r['name'].
				'<td class="w200">`'.$table_name.'`.`'.$r['col_name'].'`'.
				'<td class="w35">'.
					_iconEdit($r + array('onclick'=>'saTemplateVarEdit('.$r['id'].')')).
					_iconDel($r + array('onclick'=>'saTemplateVarDel('.$r['id'].')'));
	}

	$send .= '</table>';

	return $send;
}




function sa_count() {//39
	return
	saPath(array('name'=>'Счётчики')).
	'<div id="sa-count">'.
		'<div class="headName">Счётчики</div>'.
		'<button class="vk client">Клиенты</button>'.
		'<br />'.
		'<br />'.
		'<button class="vk" onclick="zbDialog=saZayavBalans()">Заявки</button>'.
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




function sa_app() {//40
	return
		saPath(array('name'=>'Приложения')).
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
	if(!$spisok = query_arr($sql))
		return 'Приложений нет.';

	$send =
		'<table class="_spisok">'.
			'<tr><th>app_id'.
				'<th>Название'.
				'<th>title'.
				'<th class="w50">Кеш'.
				'<th>Дата создания'.
				'<th>';
	foreach($spisok as $r) {
		$name = APP_ID == $r['id'] ? '<b>'.$r['app_name'].'</b>' : $r['app_name'];
		$name = LOCAL ? '<a href="'.API_HTML.'/index.php'.'?api_id='.$r['id'].'&viewer_id='.VIEWER_ID.'&p=40">'.$name.'</a>' : $name;
		$send .=
			'<tr>'.
				'<td class="id">'.$r['id'].
					'<input type="hidden" class="name" value="'.$r['app_name'].'" />'.
					'<input type="hidden" class="secret" value="'.$r['secret'].'" />'.
				'<td class="app_name">'.$name.
				'<td class="title">'.$r['title'].
				'<td class="center">'.
					'<a onclick="saAppCacheClear('.$r['id'].')" class="'._tooltip('Очистить', -30).$r['js_values'].'</a>'.
				'<td class="dtime">'._dtimeAdd($r).
				'<td class="ed">'._iconEdit($r);
	}
	$send .= '</table>';
	return $send;
}




function sa_user() {// 41
	$data = sa_user_spisok();
	return
	saPath(array('name'=>'Пользователи')).
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
			  AND `last_seen`!='0000-00-00 00:00:00'
			ORDER BY `last_seen` DESC";
	$q = query($sql);
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
			WHERE TABLE_SCHEMA='".GLOBAL_MYSQL_DATABASE."'
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

