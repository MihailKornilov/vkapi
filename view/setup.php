<?php
// --- vk Global ---
function _setupApp() {//применение настроек приложения и проверка на присутствие обязательных настроек
	//получение данных настроек приложения из кеша
	$key = CACHE_PREFIX.'setup'.WS_ID;
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT `key`,`value`
				FROM `_setup`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID;
		$arr = query_ass($sql, GLOBAL_MYSQL_CONNECT);
		xcache_set($key, $arr, 86400);
	}
	foreach($arr as $key => $value)
		define($key, $value);

	// Проверка наличия обязательных настроек:
	_setupValue('VERSION', 0, 'Версия скриптов и стилей');
	_setupValue('GLOBAL_VALUES', 0, 'Версия файла global_values.js');
	_setupValue('WS_VALUES', 0, 'Версия файла ws_'.WS_ID.'_values.js');
	_setupValue('G_VALUES', 0, 'Версия файла G_values.js');
}
function _setupValue($key, $v='', $about='') {//получение значения настройки и внесение, если её нет в таблице базы
	if(defined($key))
		return true;
	$sql = "SELECT `value`
			FROM `_setup`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `key`='".$key."'
			LIMIT 1";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	if(!mysql_num_rows($q)) {
		$sql = "INSERT INTO `_setup` (
					`app_id`,
					`ws_id`,
					`key`,
					`value`,
					`about`
				) VALUES (
					".APP_ID.",
					".WS_ID.",
					'".strtoupper($key)."',
					'".addslashes($v)."',
					'".addslashes($about)."'
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);
		return _setupValue($key);
	}
	$r = mysql_fetch_assoc($q);
	define($key, $r['value']);

	return $r['value'];
}





// --- _setup --- раздел настроек приложения
function _setup() {
/*
	$sub:   подстраницы основых страниц настроек.
			Передаётся в виде:
			array(
				'worker' => 'rule'
			);
			где далее формируется название функции: setup_worker_rule()
			Вызывается при условии наличия $_GET['id'], которое intval и не 0.
*/
	$page = array(
		'my' => 'Мои настройки',
		'worker' => 'Сотрудники',
		'rekvisit' => 'Реквизиты организации',
		'service' => 'Виды деятельности',
		'invoice' => 'Расчётные счета',
		'expense' => 'Категории расходов',
		'zayav_expense' => 'SA: Расходы по заявке',
		'product' => 'Виды изделий'
	) + (function_exists('setup') ? setup() : array());

	$sub = array(
		'worker' => 'rule',
		'service' => 'cartridge',
		'product' => 'sub'
	);

	if(!RULE_SETUP_WORKER)
		unset($page['worker']);

	if(!RULE_SETUP_REKVISIT)
		unset($page['rekvisit']);

	if(_service('count') < 2)
		unset($page['service']);

	if(!RULE_SETUP_INVOICE)
		unset($page['invoice']);

	if(!SA)
		unset($page['zayav_expense']);

	$d = empty($_GET['d']) || empty($page[$_GET['d']]) ? 'my' : $_GET['d'];

	$id = _num(@$_GET['id']);
	$func = 'setup_'.$d.(isset($sub[$d]) && $id ? '_'.$sub[$d] : '');
	$left = function_exists($func) ? $func($id) : setup_my();

	$links = '';
	foreach($page as $p => $name)
		$links .= '<a href="'.URL.'&p=setup&d='.$p.'"'.($d == $p ? ' class="sel"' : '').'>'.$name.'</a>';

	return
		'<div id="setup">'.
			'<table class="tabLR">'.
				'<tr><td class="left">'.$left.
					'<td class="right"><div class="rightLink">'.$links.'</div>'.
			'</table>'.
		'</div>';
}

function setup_my() {
	return
	'<div id="setup_my">'.
		'<div class="headName">Пин-код</div>'.
		'<div class="_info">'.
			'<p><b>Пин-код</b> необходим для дополнительного подтверждения вашей личности, '.
			'если другой пользователь получит доступ к вашей странице ВКонтакте.'.
			'<br />'.
			'<p>Пин-код нужно будет вводить каждом новом входе в приложение, '.
			'а также при отсутсвии действий в программе в течение <b>1-го часа</b>.'.
			'<br />'.
			'<p>Если вы забыли пин-код, обратитесь к руководителю, чтобы сбросить его.'.
		'</div>'.
	(PIN ?
		'<div class="vkButton" id="pinchange"><button>Изменить пин-код</button></div>'.
		'<div class="vkButton" id="pindel"><button>Удалить пин-код</button></div>'
	:
		'<div class="vkButton" id="pinset"><button>Установить пин-код</button></div>'
	).


		'<div class="headName" id="dop" >Дополнительно</div>'.
		'<table class="bs10">'.
			'<tr><td class="label">Показывать платежи:<td><input type="hidden" id="RULE_MY_PAY_SHOW_PERIOD" value="'._num(@RULE_MY_PAY_SHOW_PERIOD).'" />'.
		'</table>'.

	'</div>';
}

function setup_worker() {
	if(!RULE_SETUP_WORKER)
		return _err('Недостаточно прав: управление сотрудниками');

	return
		'<div id="setup_worker">'.
			'<div class="headName">Управление сотрудниками<a class="add">Новый сотрудник</a></div>'.
			'<div id="spisok">'.setup_worker_spisok().'</div>'.
		'</div>';
}
function setup_worker_spisok() {
	$sql = "SELECT *
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `worker`
			  AND `viewer_id`!=982006
			ORDER BY `dtime_add`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$send = '';
	while($r = mysql_fetch_assoc($q)) {
		$send .=
			'<table class="unit">'.
				'<tr><td class="photo"><img src="'.$r['photo'].'">'.
					'<td>'.
						'<a class="name" href="'.URL.'&p=setup&d=worker&id='.$r['viewer_id'].'">'.$r['first_name'].' '.$r['last_name'].'</a>'.
						'<div>'.$r['post'].'</div>'.
					  ($r['last_seen'] != '0000-00-00 00:00:00' ? '<div class="activity">Заходил'.($r['sex'] == 1 ? 'a' : '').' в приложение '.FullDataTime($r['last_seen']).'</div>' : '').
			'</table>';
	}
	return $send;
}
function setup_worker_rule($viewer_id) {
	if(!RULE_SETUP_WORKER)
		return _err('Недостаточно прав: управление сотрудниками.');

	$u = _viewer($viewer_id);
	if($u['viewer_ws_id'] != WS_ID)
		return _err('Сотрудника не существует.');

	if(!$u['viewer_worker'])
		return _err('Пользователь <b>'.$u['viewer_name'].'</b><br />уже не является сотрудником.');

	$rule = _viewerRule($viewer_id);
	return
	'<script type="text/javascript">var RULE_VIEWER_ID='.$viewer_id.';</script>'.
	'<div id="setup_rule">'.
		(!$u['viewer_admin'] ? '<div class="img_del'._tooltip('Удалить сотрудника', -119, 'r').'</div>' : '').
		'<table class="utab">'.
			'<tr><td>'.$u['viewer_photo'].
				'<td>'.
					'<div class="name">'.$u['viewer_name'].'</div>'.
					($viewer_id < VIEWER_MAX ? '<a href="http://vk.com/id'.$viewer_id.'" class="vklink" target="_blank">Перейти на страницу VK</a>' : '').
					'<a href="'.URL.'&p=report&d=salary&id='.$viewer_id.'" class="vklink">Страница з/п</a>'.
		'</table>'.

		'<div class="headName">Данные сотрудника</div>'.
		'<table class="rtab">'.
			'<tr><td class="lab">Фамилия:<td><input type="text" id="last_name" value="'.$u['viewer_last_name'].'" />'.
			'<tr><td class="lab">Имя:<td><input type="text" id="first_name" value="'.$u['viewer_first_name'].'" />'.
			'<tr><td class="lab">Отчество:<td><input type="text" id="middle_name" value="'.$u['viewer_middle_name'].'" />'.
			'<tr><td class="lab">Должность:<td><input type="text" id="post" value="'.$u['viewer_post'].'" />'.
			'<tr><td><td><div class="vkButton" id="w-save"><button>Сохранить</button></div>'.
		'</table>'.

		'<div class="headName">Дополнительные настройки</div>'.
		'<table class="rtab">'.
			'<tr><td class="lab"><td>'._check('RULE_SALARY_SHOW', 'Показывать в списке з/п сотрудников', $rule['RULE_SALARY_SHOW']).
			'<tr><td class="lab">Начислять бонусы:'.
				'<td>'._check('RULE_SALARY_BONUS', '', $rule['RULE_SALARY_BONUS']).
					'<span'.($rule['RULE_SALARY_BONUS'] ? '' : ' class="vh"').'>'.
						'<input type="text" id="salary_bonus_sum" maxlength="5" value="'.$u['bonus_sum'].'" />%'.
					'<span>'.
		'</table>'.


	(!$u['viewer_admin'] && $u['pin'] ?
		'<div class="headName">Пин-код</div>'.
		'<div class="vkButton" id="pin-clear"><button>Сбросить пин-код</button></div>'
	: '').

/*		'<div class="headName">Дополнительно</div>'.
			'<table class="rtab">'.
				'<tr><td class="lab">Процент от платежей:<td><input type="text" id="rules_money_procent" value="'.$rule['RULES_MONEY_PROCENT'].'" maxlength="2" />'.
				'<tr><td><td><div class="vkButton dop-save"><button>Сохранить</button></div>'.
			'</table.
*/

	(!$u['viewer_admin'] && $viewer_id < VIEWER_MAX && RULE_SETUP_RULES ?
		'<div class="headName">Права в приложении</div>'.
			_check('RULE_APP_ENTER', 'Разрешать вход в приложение', $rule['RULE_APP_ENTER'], 1).
			'<table class="rtab'.($rule['RULE_APP_ENTER'] ? '' : ' dn').'" id="div-app-enter">'.
				'<tr><td class="label top"><b>Доступ к настройкам:</b>'.
					'<td id="td-rule-setup">'.
						_check('RULE_SETUP_WORKER', 'Сотрудники', $rule['RULE_SETUP_WORKER']).
						'<div id="div-w-rule"'.($rule['RULE_SETUP_WORKER'] ? '' : ' style="display:none"').'>'.
							_check('RULE_SETUP_RULES', 'Права сотрудников', $rule['RULE_SETUP_RULES']).
						'</div>'.
						_check('RULE_SETUP_REKVISIT', 'Реквизиты организации', $rule['RULE_SETUP_REKVISIT']).
						_check('RULE_SETUP_INVOICE', 'Расчётные счета', $rule['RULE_SETUP_INVOICE']).
				'<tr><td class="label">Видит историю действий:<td>'._check('RULE_HISTORY_VIEW', '', $rule['RULE_HISTORY_VIEW']).
				'<tr><td class="label">Видит историю переводов по расчётным счетам:<td>'._check('RULE_INVOICE_TRANSFER', '', $rule['RULE_INVOICE_TRANSFER']).
				'<tr><td class="label">Может видеть платежи:<td>'._check('RULE_INCOME_VIEW', '', $rule['RULE_INCOME_VIEW']).
			'</table>'.
		'</div>'
	: '').

	'</div>';

}
function setup_worker_rule_save($post) {//сохранение настройки права сотрудника
	if(!RULE_SETUP_RULES)
		return false;

	if(!$viewer_id = _num($post['viewer_id']))
		return false;

	$u = _viewer($viewer_id);
	if($u['viewer_admin'] && $post['op'] != 'RULE_SALARY_SHOW')
		return false;

	if($u['viewer_ws_id'] != WS_ID)
		return false;

	$r = _viewerRule($viewer_id);
	if(!isset($r[$post['op']]))
		return false;

	$key = $post['op'];
	$old = $r[$post['op']];
	$v = $post['v'];
	if($old != $v) {
		_workerRuleQuery($viewer_id, $key, $v);

		_history(array(
			'type_id' => $post['h' . $v],
			'worker_id' => $viewer_id
		));

		xcache_unset(CACHE_PREFIX.'viewer_'.$viewer_id);
		xcache_unset(CACHE_PREFIX.'viewer_rule_'.$viewer_id);
	}
	return true;
}
function _workerRuleQuery($viewer_id, $key, $v) {//изменение значения права сотрудника в базе
	$sql = "SELECT COUNT(*)
			FROM `_vkuser_rule`
			WHERE `app_id`=".APP_ID."
			  AND `key`='".$key."'
			  AND `viewer_id`=".$viewer_id;
	if(!query_value($sql, GLOBAL_MYSQL_CONNECT)) {
		$sql = "INSERT INTO `_vkuser_rule` (
					`app_id`,
					`viewer_id`,
					`key`,
					`value`
				) VALUES (
					".APP_ID.",
					".$viewer_id.",
					'".strtoupper($key)."',
					'".$v."'
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);
		return;
	}

	$sql = "UPDATE `_vkuser_rule`
			SET `value`=".$v."
			WHERE `app_id`=".APP_ID."
			  AND `viewer_id`=".$viewer_id."
			  AND `key`='".$key."'";
	query($sql, GLOBAL_MYSQL_CONNECT);
}

function setup_rekvisit() {
	if(!RULE_SETUP_REKVISIT)
		return _err('Недостаточно прав: Реквизиты организации');

	$sql = "SELECT *
			FROM `_ws`
			WHERE `app_id`=".APP_ID."
			  AND `id`=".WS_ID;
	$g = query_assoc($sql, GLOBAL_MYSQL_CONNECT);
	return
		'<div id="setup_rekvisit">'.
			'<div class="headName">Реквизиты организации</div>'.
			'<table class="t">'.
				'<tr><td class="label topi">Название организации:<td><textarea id="name">'.$g['name'].'</textarea>'.
				'<tr><td class="label topi">Наименование юридического лица:<td><textarea id="name_yur">'.$g['name_yur'].'</textarea>'.
				'<tr><td class="label">ОГРН:<td><input type="text" id="ogrn" value="'.$g['ogrn'].'" />'.
				'<tr><td class="label">ИНН:<td><input type="text" id="inn" value="'.$g['inn'].'" />'.
				'<tr><td class="label">КПП:<td><input type="text" id="kpp" value="'.$g['kpp'].'" />'.
				'<tr><td class="label">Контактные телефоны:<td><input type="text" id="phone" value="'.$g['phone'].'" />'.
				'<tr><td class="label">Факс:<td><input type="text" id="fax" value="'.$g['fax'].'" />'.
				'<tr><td class="label topi">Юридический адрес:<td><textarea id="adres_yur">'.$g['adres_yur'].'</textarea>'.
				'<tr><td class="label topi">Адрес офиса:<td><textarea id="adres_ofice">'.$g['adres_ofice'].'</textarea>'.
				'<tr><td class="label">Режим работы:<td><input type="text" id="time_work" value="'.$g['time_work'].'" />'.
			'</table>'.
			'<div class="headName">Банк получателя</div>'.
			'<table class="t">'.
				'<tr><td class="label topi">Наименование банка:<td><textarea id="bank_name">'.$g['bank_name'].'</textarea>'.
				'<tr><td class="label">БИК:<td><input type="text" id="bank_bik" value="'.$g['bank_bik'].'" />'.
				'<tr><td class="label">Расчётный счёт:<td><input type="text" id="bank_account" value="'.$g['bank_account'].'" />'.
				'<tr><td class="label">Корреспондентский счёт:<td><input type="text" id="bank_account_corr" value="'.$g['bank_account_corr'].'" />'.
				'<tr><td><td><div class="vkButton"><button>Сохранить</button></div>'.
			'</table>'.
		'</div>';
}

function setup_service() {
	$sql = "SELECT
				*,
				0 `active`
			FROM `_zayav_type`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return '';

	$sql = "SELECT `type_id`
			FROM `_zayav_type_active`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID;
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['type_id']]['active'] = 1;

	$send = '';
	foreach($spisok as $r) {
		$send .=
		'<div class="unit'.($r['active'] ? ' on' : '').'" val="'.$r['id'].'">'.
			(SA ? _iconEdit() : '').
			'<input type="hidden" class="name" value="'.$r['name'].'">'.
			'<h1>'.$r['head'].'</h1>'.
			'<h2>'.$r['about'].'</h2>'.
			'<h3><a class="service-toggle">Включить</a></h3>'.
			'<h4>'.
				($r['id'] == 2 ? '<a href="'.URL.'&p=setup&d=service&d1=cartridge&id=1">Настроить</a> :: ' : '').
				'<a class="service-toggle">Отключить</a>'.
			'</h4>'.
		'</div>';
	}

	return
	'<div id="setup-service">'.
		'<div class="headName">Виды деятельности</div>'.
		$send.
	'</div>';
}

function setup_invoice() {
	if(!RULE_SETUP_INVOICE)
		return _err('Недостаточно прав: настройки счетов или видов платежей');
	return
		'<div id="setup_invoice">'.
			'<div class="headName">Управление счетами<a class="add">Новый счёт</a></div>'.
			'<div class="spisok">'.setup_invoice_spisok().'</div>'.
		'</div>';
}
function setup_invoice_spisok() {
	$sql = "SELECT *
			FROM `_money_invoice`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	if(!mysql_num_rows($q))
		return 'Список пуст.';

	$spisok = array();
	while($r = mysql_fetch_assoc($q)) {
		$r['worker'] = array();
		if($r['visible'])
			foreach(explode(',', $r['visible']) as $i)
				$r['worker'][] = _viewer($i, 'viewer_name');
		$spisok[$r['id']] = $r;
	}

	$send =
		'<table class="_spisok">'.
			'<tr><th>Наименование'.
			'<th>Видимость<br />для сотрудников'.
			'<th>';
	foreach($spisok as $id => $r)
		$send .=
			'<tr val="'.$id.'">'.
				'<td class="name">'.
					'<div>'.$r['name'].'</div>'.
					'<pre>'.$r['about'].'</pre>'.
					($r['confirm_income'] ? '<h6>Подтверждения поступления на счёт</h6>' : '').
					($r['confirm_transfer'] ? '<h6>Требовать подтверждение переводов</h6>' : '').
				'<td class="visible">'.
					implode('<br />', $r['worker']).
				'<td class="ed">'.
					'<div class="img_edit"></div>'.
					'<div class="img_del"></div>'.

			'<input type="hidden" class="confirm_income" value="'.$r['confirm_income'].'" />'.
			'<input type="hidden" class="confirm_transfer" value="'.$r['confirm_transfer'].'" />'.
			'<input type="hidden" class="visible_id" value="'.(empty($r['worker']) ? 0 : $r['visible']).'" />';

	$send .= '</table>';

	return $send;
}

function setup_expense() {
	return
		'<div id="setup_expense">'.
			'<div class="headName">Категории расходов организации<a class="add">Новая категория</a></div>'.
			'<div id="spisok">'.setup_expense_spisok().'</div>'.
		'</div>';
}
function setup_expense_spisok() {
	$sql = "SELECT *,
				0 `count`
			FROM `_money_expense_category`
			WHERE (`app_id`=".APP_ID." OR !`app_id`)
			  AND (`ws_id`=".WS_ID." OR !`ws_id`)
			ORDER BY `sort`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	if(!mysql_num_rows($q))
		return 'Список пуст.';

	$spisok = array();
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']] = $r;

	$sql = "SELECT
				DISTINCT `category_id`,
				COUNT(`id`) `count`
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `category_id`
			GROUP BY `category_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['category_id']]['count'] = $r['count'];

	$send =
		'<table class="_spisok">'.
			'<tr><th class="name">Наименование'.
				'<th class="worker_use">Показывать<br />список<br />сотрудников'.
				'<th class="count">Кол-во<br />записей'.
				'<th class="ed">'.
		'</table>'.
		'<dl class="_sort" val="_money_expense_category">';

	foreach($spisok as $r)
		$send .= '<dd val="'.$r['id'].'">'.
			'<table class="_spisok">'.
				'<tr><td class="name">'.$r['name'].
					'<td class="worker_use">'.($r['worker_use'] ? 'да' : '').
					'<td class="count">'.($r['count'] ? $r['count'] : '').
					'<td class="ed">'.
						($r['ws_id'] ? '<div class="img_edit'._tooltip('Изменить', -33).'</div>' : '').
						($r['ws_id'] && !$r['count'] ? '<div class="img_del"></div>' : '').
			'</table>';
	$send .= '</dl>';
	return $send;
}

function setup_product() {
	return
	'<div id="setup_product">'.
		'<div class="headName">Настройки видов изделий<a class="add">Добавить</a></div>'.
		'<div class="spisok">'.setup_product_spisok().'</div>'.
	'</div>';
}
function setup_product_spisok() {
	$sql = "SELECT
				*,
				0 `sub`,
				0 `zayav`
			FROM `_product`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			ORDER BY `name`";
	$product = query_arr($sql, GLOBAL_MYSQL_CONNECT);
	if(empty($product))
		return 'Список пуст.';

	$sql = "SELECT `product_id`,
				   COUNT(`id`) AS `sub`
			FROM `_product_sub`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			GROUP BY `product_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$product[$r['product_id']]['sub'] = $r['sub'];

	$sql = "SELECT `product_id`,
				   COUNT(`id`) AS `zayav`
			FROM `_zayav_product`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			GROUP BY `product_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$product[$r['product_id']]['zayav'] = $r['zayav'];

	$send = '<table class="_spisok">'.
				'<tr><th>Наименование'.
					'<th>Подвиды'.
					'<th>Кол-во<br />заявок'.
					'<th>';
	foreach($product as $id => $r)
		$send .= '<tr val="'.$id.'">'.
					'<td class="name"><a href="'.URL.'&p=setup&d=product&id='.$id.'">'.$r['name'].'</a>'.
					'<td class="sub">'.($r['sub'] ? $r['sub'] : '').
					'<td class="zayav">'.($r['zayav'] ? $r['zayav'] : '').
					'<td><div class="img_edit"></div>'.
						($r['sub'] || $r['zayav'] ? '' :'<div class="img_del"></div>');
	$send .= '</table>';
	return $send;
}
function setup_product_sub($product_id) {
	$sql = "SELECT *
			FROM `_product`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `id`=".$product_id;
	if(!$pr = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		return 'Изделия id = '.$product_id.' не существует.';

	return
	'<script type="text/javascript">var PRODUCT_ID='.$product_id.';</script>'.
	'<div id="setup_product_sub">'.
		'<a href="'.URL.'&p=setup&d=product"><< назад к видам изделий</a>'.
		'<div class="headName">Список подвидов изделий для "'.$pr['name'].'"<a class="add">Добавить</a></div>'.
		'<div class="spisok">'.setup_product_sub_spisok($product_id).'</div>'.
	'</div>';
}
function setup_product_sub_spisok($product_id) {
	$sql = "SELECT
				*,
				0 `zayav`
			FROM `_product_sub`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `product_id`=".$product_id."
			ORDER BY `name`";
	$arr = query_arr($sql, GLOBAL_MYSQL_CONNECT);
	if(empty($arr))
		return 'Список пуст.';

	$sql = "SELECT
				`product_sub_id`,
				COUNT(`id`) `zayav`
			FROM `_zayav_product`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `product_id`=".$product_id."
			  AND `product_sub_id`
			GROUP BY `product_sub_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$arr[$r['product_sub_id']]['zayav'] = $r['zayav'];

	$send = '<table class="_spisok">'.
				 '<tr><th>Наименование'.
					 '<th>Кол-во<br />заявок'.
					 '<th>';
	foreach($arr as $id => $r)
		$send .= '<tr val="'.$r['id'].'">'.
			 '<td class="name">'.$r['name'].
			 '<td class="zayav">'.($r['zayav'] ? $r['zayav'] : '').
			 '<td><div class="img_edit"></div>'.
					($r['zayav'] ? '' : '<div class="img_del"></div>');
		$send .= '</table>';
	return $send;
}


function setup_zayav_expense() {//категории расходов по заявке
	if(!SA)
		return '';

	return
	'<div id="setup_zayav_expense">'.
		'<div class="headName">Настройки категорий расходов по заявке<a class="add">Добавить</a></div>'.
		'<div id="spisok">'.setup_zayav_expense_spisok().'</div>'.
	'</div>';
}
function setup_zayav_expense_spisok() {
	if(!SA)
		return '';

	$sql = "SELECT
				*,
				0 `use`
			FROM `_zayav_expense_category`
			WHERE `app_id`=".APP_ID."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return 'Список пуст.';

	$sql = "SELECT
				DISTINCT `category_id`,
				COUNT(`id`) `use`
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `category_id`
			GROUP BY `category_id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['category_id']]['use'] = $r['use'];

	$send =
	'<table class="_spisok">'.
		'<tr><th class="name">Наименование'.
			'<th class="dop">Дополнительное поле'.
			'<th class="use">Кол-во<br />записей'.
			'<th class="ed">'.
	'</table>'.
	'<dl class="_sort" val="_zayav_expense_category">';
	foreach($spisok as $id => $r)
		$send .=
		'<dd val="'.$id.'">'.
			'<table class="_spisok">'.
				'<tr><td class="name">'.$r['name'].
					'<td class="dop">'.
						($r['dop'] ? _zayavExpenseDop($r['dop']) : '').
						'<input class="hdop" type="hidden" value="'.$r['dop'].'" />'.
					'<td class="use">'.($r['use'] ? $r['use'] : '').
					'<td class="ed">'.
						_iconEdit().
						(!$r['use'] ? _iconDel() : '').
			'</table>';
	$send .= '</dl>';
	return $send;
}
