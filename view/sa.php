<?php
function _sa_global() {//вывод ссылок суперадминистратора для всех приложений
	return
		'<div><b>Global:</b></div>'.
		'<a href="'.URL.'&p=sa&d=history">История действий</a><br />'.
		'<a href="'.URL.'&p=sa&d=rule">Права сотрудников</a><br />'.
		'<a href="'.URL.'&p=sa&d=balans">Балансы</a><br />'.
		'<br />';
}//_sa_global()
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
	return '<a href="'.URL.'&p='.$_COOKIE['pre_p'].$d.$d1.$id.'">Назад</a> » ';
}//sa_cookie_back()
function sa_path($v1, $v2='') {
	return
		'<div class="path">'.
			sa_cookie_back().
			'<a href="'.URL.'&p=sa">Администрирование</a> » '.
			$v1.($v2 ? ' » ' : '').
			$v2.
		'</div>';
}//sa_path()




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
}//sa_history()
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
}//sa_history_spisok()
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
}//sa_history_spisok_page()
function sa_history_cat() {//настройка категорий истории действий
	return
		sa_path('<a href="'.URL.'&p=sa&d=history">История действий</a>', 'Настройка категорий').
		'<div id="sa-history-cat">'.
			'<div class="headName">Категории истории действий<a class="add">Добавить</a></div>'.
			'<div id="spisok">'.sa_history_cat_spisok().'</div>'.
		'</div>';
}//sa_history_cat()
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
}//sa_history_cat_spisok()


function sa_rule() {//управление историей действий
	return
		sa_path('Права сотрудников').
		'<div id="sa-rule">'.
			'<div class="headName">Права сотрудников<a class="add">Добавить</a></div>'.
			'<div id="spisok">'.sa_rule_spisok().'</div>'.
		'</div>';
}//sa_rule()
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
}//sa_rule_spisok()


function sa_balans() {//управление балансами
	return
		sa_path('Балансы').
		'<div id="sa-balans">'.
			'<div class="headName">Управление балансами</div>'.
			'<div id="dopLinks">'.
				'<a class="link sel">Категории</a>'.
				'<a class="link">Действия</a>'.
				'<div id="category-add" class="img_add m30 div d0'._tooltip('Новая категория', -96, 'r').'</div>'.
				'<div id="action-add"   class="img_add m30 div d1'._tooltip('Новое действие', -90, 'r').'</div>'.
			'</div>'.
			'<div id="category-spisok" class="div d0">'.sa_balans_category_spisok().'</div>'.
			'<div id="action-spisok" class="div d1">'.sa_balans_action_spisok().'</div>'.
		'</div>';
}//sa_balans()
function sa_balans_category_spisok() {
	$sql = "SELECT * FROM `_balans_category` ORDER BY `id`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return 'Список пуст.';

	$send =
		'<table class="_spisok">'.
		'<tr><th>id'.
			'<th>Название'.
			'<th>';
	foreach($spisok as $r)
		$send .=
			'<tr val="'.$r['id'].'">'.
				'<td class="id">'.$r['id'].
				'<td class="name">'.$r['name'].
				'<td class="ed">'.
					'<div class="img_edit balans-category-edit" val="'.$r['id'].'"></div>'.
					'<div class="img_del balans-category-del" val="'.$r['id'].'"></div>';
	$send .= '</table>';

	return $send;
}//sa_balans_category_spisok()
function sa_balans_action_spisok() {
	$sql = "SELECT * FROM `_balans_action` ORDER BY `id`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return 'Список пуст.';

	$send =
		'<table class="_spisok">'.
		'<tr><th>id'.
		'<th>Название'.
		'<th>';
	foreach($spisok as $r)
		$send .=
			'<tr val="'.$r['id'].'">'.
			'<td class="id">'.$r['id'].
			'<td class="name">'.$r['name'].
			'<td class="ed">'.
				'<div class="img_edit balans-action-edit" val="'.$r['id'].'"></div>'.
				'<div class="img_del balans-action-del" val="'.$r['id'].'"></div>';
	$send .= '</table>';

	return $send;
}//sa_balans_action_spisok()