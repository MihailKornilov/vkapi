<?php
// --- Global ---
function _setup_global($i='const') {//получение констант-параметров для всех приложений
	$key = CACHE_PREFIX.'setup_global';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT *
				FROM `_setup_global`
				WHERE `app_id` IN (".APP_ID.",0)";
		$arr = query_arr($sql);
		xcache_set($key, $arr, 86400);
	}

	if($i == 'const') {
		foreach($arr as $r)
			define($r['key'], $r['value']);
		return true;
	}

	if($i == 'js') {
		$send = '';
		foreach($arr as $r) {
			if($r['app_id'] != APP_ID)
				continue;
			$send .= "\n".$r['key'].'='.$r['value'].',';
		}
		return $send;
	}

	return true;
}



// --- _setup --- раздел настроек приложения
function _setup() {
	$sub = array(
		'worker' => 'rule',
		'rubric' => 'sub',
		'expense' => 'sub',
		'product' => 'sub',
		'document_template' => 'info'
	);

	$d = empty($_GET['d']) ? 'my' : $_GET['d'];

	$id = _num(@$_GET['id']);
	$func = 'setup_'.$d.(isset($sub[$d]) && $id ? '_'.$sub[$d] : '');
	$left = function_exists($func) ? $func($id) : setup_my();


	$links = '';
	foreach(_menuCache('setup') as $r) {
		//если не определены виды деятельности
		if($r['p'] == 'service' && (!SA || _service('count') < 2))
			continue;
		$links .= '<a href="'.URL.'&p=setup&d='.$r['p'].'"'.($d == $r['p'] ? ' class="sel"' : '').'>'.$r['name'].'</a>';
	}

	return
		'<div id="setup">'.
			'<table class="tabLR">'.
				'<tr><td class="left">'.$left.
					'<td class="right"><div class="rightLink">'.$links.'</div>'.
			'</table>'.
		'</div>';
}

function _setup_script() {//скрипты и стили
	if(@$_GET['p'] != 'setup')
		return '';

	return
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/setup/setup'.MIN.'.css?'.VERSION.'" />'.
		'<script src="'.API_HTML.'/modul/setup/setup'.MIN.'.js?'.VERSION.'"></script>';
}
function setupPath($v) {//Верхняя строка: путь настроек
	$v = array(
		'name' => $v['name'],           // название настройки
		'link_name' => @$v['link_name'],// название ссылки на предыдущцю страницу
		'link_d' => @$v['link_d'],      // d предыдущей страницы
		'add_name' => @$v['add_name'],  // название ссылки добавления
		'add_func' => @$v['add_func']   // функция, по которой происходит добавление
	);

	if($v['add_func'])
		$v['add_func'] = ' onclick="'.$v['add_func'].'"';

	if($v['link_name'])
		$v['link_name'] = '<a class="link" href="'.URL.'&p=setup&d='.$v['link_d'].'">'.$v['link_name'].'</a> » ';

	if($v['add_name'])
		$v['add_name'] = '<a class="add"'.$v['add_func'].'>'.$v['add_name'].'</a>';

	return
	'<div class="hd1">'.
		$v['link_name'].
		$v['name'].
		$v['add_name'].
	'</div>';
}

function setup_my() {
	return
	setupPath(array(
		'name' => 'Мои настройки'
	)).
	'<div id="setup_my" class="mar20">'.
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

		'<div class="center mb20">'.
		(PIN ?
			'<button class="vk" id="pinchange">Изменить пин-код</button> '.
			'<button class="vk" id="pindel">Удалить пин-код</button>'
		:
			'<button class="vk" id="pinset">Установить пин-код</button>'
		).
		'</div>'.

		'<div class="headName">Дополнительно</div>'.
		'<table class="bs10">'.
			'<tr><td class="label">Показывать платежи:<td><input type="hidden" id="RULE_MY_PAY_SHOW_PERIOD" value="'._num(@RULE_MY_PAY_SHOW_PERIOD).'" />'.
		'</table>'.
	'</div>';
}

function setup_worker() {
	if(!_viewerMenuAccess(15))
		return _err('Недостаточно прав: управление сотрудниками');

	return
	'<div id="setup_worker">'.
		setupPath(array(
			'name' => 'Управление сотрудниками',
			'add_name' => 'Новый сотрудник',
			'add_func' => 'setupWorkerAdd()'
		)).
		'<div id="spisok" class="mar20">'.setup_worker_spisok().'</div>'.
	'</div>';
}
function setup_worker_spisok() {
	$sql = "SELECT *
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `worker`
			  AND !`hidden`
			ORDER BY `dtime_add`";
	$q = query($sql);
	$send = '';
	while($r = mysql_fetch_assoc($q)) {
		$send .=
			'<table class="unit bs10">'.
				'<tr><td class="photo w50 h50">'.
						'<a href="'.URL.'&p=setup&d=worker&id='.$r['viewer_id'].'">'.
							'<img src="'.$r['photo'].'">'.
						'</a>'.
					'<td class="top">'.
						setup_worker_link_vk($r['viewer_id']).
						setup_worker_link_zp($r['viewer_id']).
						setup_worker_link_client($r['viewer_id']).
						'<a class="name b" href="'.URL.'&p=setup&d=worker&id='.$r['viewer_id'].'">'.
							$r['first_name'].' '.$r['last_name'].
						'</a>'.
						'<div>'.$r['post'].'</div>'.
						setup_worker_last_seen($r['viewer_id']).
			'</table>';
	}
	return $send;
}
function setup_worker_last_seen($viewer_id) {//время последнего посещения сотрудником
	if($viewer_id >= VIEWER_MAX)
		return '';

	$u = _viewer($viewer_id);

	if($u['viewer_last_seen'] == '0000-00-00 00:00:00')
		return '';

	return '<div class="grey mt5 fs11">Заходил'.($u['viewer_sex'] == 1 ? 'a' : '').' в приложение '.FullDataTime($u['viewer_last_seen']).'</div>';
}
function setup_worker_link_client($viewer_id) {//ссылка-иконка на страницу Клиента
	$sql = "SELECT `id`
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`=".$viewer_id."
			  AND !`deleted`
			LIMIT 1";
	if(!$client_id = query_value($sql))
		return '<div class="icon-empty fr"></div>';

	return '<a href="'.URL.'&p=client&d=info&&id='.$client_id.'" class="icon icon-client fr'._tooltip('Клиентская страница', -64).'</a>';
}
function setup_worker_link_vk($viewer_id) {//ссылка-иконка на страницу ВКонтакте
	if($viewer_id >= VIEWER_MAX)
		return '<div class="icon-empty fr"></div>';

	return '<a href="//vk.com/id'.$viewer_id.'" target="_blank" class="icon icon-vk fr'._tooltip('Страница ВКонтакте', -62).'</a>';
}
function setup_worker_link_zp($viewer_id) {//ссылка-иконка на страницу З/п
	return '<a href="'.URL.'&p=report&d=salary&id='.$viewer_id.'" class="icon icon-zp fr'._tooltip('Страница з/п', -40).'</a>';
}
function setup_worker_link_del($viewer_id) {//ссылка-иконка для удаления сотрудника
	$u = _viewer($viewer_id);

	if($u['viewer_admin'])
		return '';

	return '<a class="icon icon-del fr'._tooltip('Удалить сотрудника', -62).'</a>';
}
function setup_worker_rule($viewer_id) {
	if(!_viewerMenuAccess(15))
		return _err('Недостаточно прав: управление сотрудниками.');

	$u = _viewer($viewer_id);
	if(!$u['viewer_worker'])
		return _err('Пользователь <b>'.$u['viewer_name'].'</b><br />уже не является сотрудником.');

	$rule = _viewerRule($viewer_id);

	//видимость истории действий всех сотрудников
	$sql = "SELECT `viewer_id`,`value`
			FROM `_vkuser_rule`
			WHERE `app_id`=".APP_ID."
			  AND `key`='RULE_HISTORY_VIEW'
			  AND `viewer_id`<".VIEWER_MAX;
	$hist_worker_all = query_assJson($sql);

	return
	'<script>'.
		'var RULE_VIEWER_ID='.$viewer_id.','.
			'RULE_HISTORY_ALL='.$hist_worker_all.','.
			'U={'.
				'last_name:"'.addslashes($u['viewer_last_name']).'",'.
				'first_name:"'.addslashes($u['viewer_first_name']).'",'.
				'middle_name:"'.addslashes($u['viewer_middle_name']).'",'.
				'post:"'.addslashes($u['viewer_post']).'"'.
			'};'.
	'</script>'.

	setupPath(array(
		'name' => $u['viewer_name'],
		'link_name' => 'Управление сотрудниками',
		'link_d' => 'worker'
	)).

	'<div id="setup_rule">'.
		'<table class="bs10 w100p">'.
			'<tr><td class="w50 h50">'.$u['viewer_photo'].
				'<td class="top">'.
					setup_worker_link_del($viewer_id).
					'<a onclick="setupWorkerEdit()" class="icon icon-edit fr'._tooltip('Редактировать данные', -67).'</a>'.
					setup_worker_link_vk($viewer_id).
					setup_worker_link_zp($viewer_id).
					setup_worker_link_client($viewer_id).
					'<div class="b fs14 mb5">'.$u['viewer_name_full'].'</div>'.
					'<div>'.$u['viewer_post'].'</div>'.
			'<tr><td colspan="2">'.
				setup_worker_last_seen($viewer_id).
			($viewer_id >= VIEWER_MAX ?
				'<a class="red" onclick="setupWorkerVkBind()">Привязать сотрудника к учётной записи ВКонтакте</a>'
			: '').
		'</table>'.

//		setup_worker_rule_val($viewer_id).

	(RULE_SETUP_RULES ?
		'<div class="headName">Дополнительные настройки</div>'.
		'<table class="rtab">'.
			'<tr><td class="lab"><td>'._check('RULE_SALARY_SHOW', 'Показывать в списке з/п сотрудников', $rule['RULE_SALARY_SHOW']).
			'<tr><td class="lab"><td>'._check('RULE_EXECUTER', 'Может быть исполнителем заявок', $rule['RULE_EXECUTER']).
			'<tr><td class="lab"><td>'._check('RULE_SALARY_ZAYAV_ON_PAY', 'Начислять з/п по заявке при отсутствии долга', $rule['RULE_SALARY_ZAYAV_ON_PAY']).
/*
			'<tr><td class="lab">Начислять бонусы:'.
				'<td>'._check('RULE_SALARY_BONUS', '', $rule['RULE_SALARY_BONUS']).
					'<span'.($rule['RULE_SALARY_BONUS'] ? '' : ' class="vh"').'>'.
						'<input type="text" id="salary_bonus_sum" maxlength="5" value="'.$u['bonus_sum'].'" />%'.
					'<span>'.
*/
		'</table>'.

	(!$u['viewer_admin'] && $viewer_id < VIEWER_MAX ?

	($u['pin'] ?
		'<div class="headName">Пин-код</div>'.
		'<button class="vk" id="pin-clear">Сбросить пин-код</button>'
	: '').

/*		'<div class="headName">Дополнительно</div>'.
			'<table class="rtab">'.
				'<tr><td class="lab">Процент от платежей:<td><input type="text" id="rules_money_procent" value="'.$rule['RULES_MONEY_PROCENT'].'" maxlength="2" />'.
				'<tr><td><td><div class="vkButton dop-save"><button>Сохранить</button></div>'.
			'</table.
*/

		'<div class="headName">Права в приложении</div>'.
			_check('RULE_APP_ENTER', 'Разрешать вход в приложение', $rule['RULE_APP_ENTER'], 1).
			'<table class="rtab'.($rule['RULE_APP_ENTER'] ? '' : ' dn').'" id="div-app-enter">'.

				'<tr><td class="label top"><b>Доступ к основным разделам меню:</b>'.
					'<td id="td-rule-menu">'._setup_worker_rule_menu($viewer_id).

				'<tr id="tr-rule-zayav"'.(_viewerMenuAccess(2, $viewer_id) ? '' : ' class="dn"').'>'.
					'<td class="label top"><b>Права в заявках:</b>'.
					'<td id="td-rule-zayav">'.
						_check('RULE_ZAYAV_EXECUTER', 'Видит только те заявки,<br />в которых является исполнителем', $rule['RULE_ZAYAV_EXECUTER']).

				'<tr id="tr-rule-setup"'.(_viewerMenuAccess(5, $viewer_id) ? '' : ' class="dn"').'>'.
					'<td class="label top"><b>Доступ к настройкам:</b>'.
					'<td id="td-rule-setup">'._setup_worker_rule_menu_setup($viewer_id, $rule).
				'<tr><td class="label"><a class="history-view-worker-all'._tooltip('Изменить для всех сотрудников', -20).'Видит историю действий</a>:'.
					'<td><input type="hidden" id="RULE_HISTORY_VIEW" value="'.$rule['RULE_HISTORY_VIEW'].'" />'.
				'<tr><td class="label">Видит з/п:'.
					'<td><input type="hidden" id="RULE_WORKER_SALARY_VIEW" value="'.$rule['RULE_WORKER_SALARY_VIEW'].'" />'.
				'<tr><td><td>'.

				'<tr><td><td><b>Деньги</b>'.
				'<tr><td class="label">Управление расчётными счетами:'.
					'<td>'._check('RULE_SETUP_INVOICE', '', $rule['RULE_SETUP_INVOICE']).
				'<tr><td class="label">Видит историю операций<br />по расчётным счетам:'.
					'<td>'._check('RULE_INVOICE_HISTORY', '', $rule['RULE_INVOICE_HISTORY']).
				'<tr><td class="label">Видит переводы<br />по расчётным счетам:'.
					'<td><input type="hidden" id="RULE_INVOICE_TRANSFER" value="'.$rule['RULE_INVOICE_TRANSFER'].'" />'.
//				'<tr><td class="label">Может видеть платежи:<td>'._check('RULE_INCOME_VIEW', '', $rule['RULE_INCOME_VIEW']).
			'</table>'.
		'</div>'

	: '')

	: '').

	'</div>';

}
function setup_worker_rule_val($viewer_id) {//список всех возможных переменных прав сотрудника
	if(!SA)
		return '';

	$sql = "SELECT *
			FROM `_vkuser_rule`
			WHERE `app_id`=".APP_ID."
			  AND `viewer_id`=".$viewer_id."
			ORDER BY `key`";
	$spisok = '<table class="_spisok dn l">';
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		$spisok .=
			'<tr><td>'.$r['key'].
				'<td class="w50 center">'.($r['value'] ? $r['value'] : '');
	}
	$spisok .= '</table>';

	return
	'<div class="mt10">'.
		'<a class="fr mb10" onclick="$(this).next().toggle()">SA: переменные прав</a>'.
		$spisok.
	'</div>';
}
function _setup_worker_rule_menu($viewer_id) {//вывод разделов меню с галочками
	$send = '';
	foreach(_menuCache() as $r) {
		if($r['p'] == 'main')
			continue;
		if($r['p'] == 'manual')
			continue;
		$send .= _check('RULE_MENU_'.$r['id'], $r['name'], _viewerMenuAccess($r['id'], $viewer_id));
	}

	return $send;
}
function _setup_worker_rule_menu_setup($viewer_id, $rule) {//вывод разделов меню настроек с галочками
	$send = '';
	foreach(_menuCache('setup') as $r) {
		if($r['p'] == 'my')
			continue;

		$send .= _check('RULE_MENU_'.$r['id'], $r['name'], _viewerMenuAccess($r['id'], $viewer_id));
		if($r['p'] == 'worker')
			$send .=
				'<div id="div-worker-rule"'.(_viewerMenuAccess(15, $viewer_id) ? '' : ' style="display:none"').'>'.
					_check('RULE_SETUP_RULES', 'Права сотрудников', $rule['RULE_SETUP_RULES']).
				'</div>';
	}

	return $send;
}
function setup_worker_rule_save($post) {//сохранение настройки права сотрудника
	if(!RULE_SETUP_RULES)
		return false;

	if(!$viewer_id = _num($post['viewer_id']))
		return false;

	$u = _viewer($viewer_id);
	if($u['viewer_admin'] && $post['op'] != 'RULE_SALARY_SHOW')
		return false;

	$r = _viewerRule($viewer_id);
	if(!isset($r[$post['op']]))
		return false;

	$key = $post['op'];
	$old = $r[$post['op']];
	$v = $post['v'];
	if($old != $v) {
		_workerRuleQuery($viewer_id, $key, $v);

		if(!empty($post['h' . $v]))
			_history(array(
				'type_id' => $post['h' . $v],
				'worker_id' => $viewer_id
			));
	}
	return true;
}
function _workerRuleQuery($viewer_id, $key, $v) {//изменение значения права сотрудника в базе
	$sql = "SELECT COUNT(*)
			FROM `_vkuser_rule`
			WHERE `app_id`=".APP_ID."
			  AND `key`='".$key."'
			  AND `viewer_id`=".$viewer_id;
	if(!query_value($sql)) {
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
		query($sql);

		xcache_unset(CACHE_PREFIX.'viewer_'.$viewer_id);
		xcache_unset(CACHE_PREFIX.'viewer_rule_'.$viewer_id);

		return;
	}

	$sql = "UPDATE `_vkuser_rule`
			SET `value`=".$v."
			WHERE `app_id`=".APP_ID."
			  AND `viewer_id`=".$viewer_id."
			  AND `key`='".$key."'";
	query($sql);

	xcache_unset(CACHE_PREFIX.'viewer_'.$viewer_id);
	xcache_unset(CACHE_PREFIX.'viewer_rule_'.$viewer_id);
}
function _ruleHistoryView($id=false) {
	$arr = array(
		0 => 'нет',
		1 => 'только свою',
		2 => 'всю историю'
	);

	if($id === false)
		return $arr;

	if(!isset($arr[$id]))
		return 'неизвестный id';

	return $arr[$id];
}
function _ruleInvoiceTransfer($id=false) {
	$arr = array(
		0 => 'нет',
		1 => 'только свои',
		2 => 'все'
	);

	if($id === false)
		return $arr;

	if(!isset($arr[$id]))
		return 'неизвестный id';

	return $arr[$id];
}

function setup_bik_insert() {//обновление базы банков по БИК
	$xml = simplexml_load_file(API_PATH.'/!/base.xml');
	$insert = array();
	foreach($xml->bik as $r)
		$insert[] = "(
				'".addslashes(win1251($r['bik']))."',
				'".addslashes(win1251($r['ks']))."',
				'".addslashes(win1251($r['name']))."',
				'".addslashes(win1251($r['namemini']))."',
				'".addslashes(win1251($r['index']))."',
				'".addslashes(win1251($r['city']))."',
				'".addslashes(win1251($r['address']))."',
				'".addslashes(win1251($r['phone']))."',
				'".addslashes(win1251($r['okato']))."',
				'".addslashes(win1251($r['okpo']))."',
				'".addslashes(win1251($r['regnum']))."',
				'".addslashes(win1251($r['srok']))."',
				'".addslashes(win1251($r['dateadd']))."',
				'".addslashes(win1251($r['datechange']))."'
			)";

	$sql = "INSERT INTO `_setup_biks` (
				`bik`,
				`ks`,
				`name`,
				`namemini`,
				`index`,
				`city`,
				`address`,
				`phone`,
				`okato`,
				`okpo`,
				`regnum`,
				`srok`,
				`dateadd`,
				`datechange`
			) VALUES ".implode(',', $insert);
	query($sql);
}
function setup_org() {
	if(!_viewerMenuAccess(13))
		return _err('Недостаточно прав: Реквизиты организации');

	$sql = "SELECT *
			FROM `_setup_org`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	$spisok = query_arr($sql);

	foreach($spisok as $id => $g)
		$spisok[$id]['bank'] = array();

	$sql = "SELECT *
			FROM `_setup_org_bank`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['org_id']]['bank'][] = $r;

	$send =
	'<script>var ORG_MENU='._app('org_menu_js').';</script>'.
	'<div id="setup_org">'.
		setupPath(array(
			'name' => 'Настройки организации',
			'add_name' => 'Добавить организацию',
			'add_func' => 'setupOrgEdit()'
		)).
		setup_org_empty($spisok).
		setup_org_menu($spisok);

	reset($spisok);
	$first = key($spisok);
	foreach($spisok as $id => $g) {
		$dn = $first == $id ? '' : ' dn';
		$send .=
		'<div class="mar20 org-menu-'.$id.$dn.'">'.
			setup_org_main($g).
			setup_org_rekvisit($g).
			setup_org_post($g).
			setup_org_bank($id, $g['bank']).
			setup_org_nalog($g).
		'</div>';
	}

	$send .= '</div>';

	return $send;
}
function setup_org_empty($arr) {//Не создано ни одной организации
	if(!empty($arr))
		return '';

	return
	'<div class="_empty mar20">'.
		'Организации не созданы.'.
		'<br />'.
		'<br />'.
		'<button class="vk" onclick="setupOrgEdit()">Добавить организацию</button>'.
	'</div>';
}
function setup_org_menu($spisok) {//Меню оргаризаций, если больше двух
	if(count($spisok) < 2)
		return '';

	reset($spisok);

	return
	'<input type="hidden" id="org-menu" value="'.key($spisok).'" />';
}
function setup_org_main($g) {//Основная информация об организации
	if(empty($g))
		return '';

	return
	'<div class="hd2">'.
		'Основная информация'.
		'<div onclick="setupOrgEdit('.$g['id'].')" class="icon icon-edit fr'._tooltip('Редактировать<br />данные организации', -60, '', 1).'</div>'.
	'</div>'.

	'<table class="t">'.
					 '<tr><td class="label top w175">Название организации:<td>'.$g['name'].
   ($g['name_yur'] ? '<tr><td class="label top">Наименование юр. лица:<td>'.$g['name_yur'] : '').
	  ($g['phone'] ? '<tr><td class="label">Контактные телефоны:<td>'.$g['phone'] : '').
		($g['fax'] ? '<tr><td class="label">Факс:<td>'.$g['fax'] : '').
  ($g['adres_yur'] ? '<tr><td class="label top">Юридический адрес:<td>'._br($g['adres_yur']) : '').
($g['adres_ofice'] ? '<tr><td class="label top">Адрес офиса:<td>'._br($g['adres_ofice']) : '').
  ($g['time_work'] ? '<tr><td class="label">Режим работы:<td>'.$g['time_work'] : '').
	'</table>';
}
function setup_org_rekvisit($g) {//Реквизиты организации
	if(empty($g))
		return '';

	if(!$g['ogrn']
	&& !$g['inn']
	&& !$g['kpp']
	&& !$g['okud']
	&& !$g['okpo']
	&& !$g['okved']
	)	return '';

	return
			'<div class="hd2 mt20">Реквизиты</div>'.
			'<table class="t">'.

			($g['ogrn'] ?
	            '<tr><td class="label w175">'.
	                    'ОГРН:'.
	                    ' <div class="icon icon-hint" val="1"></div>'.
	                '<td>'.$g['ogrn']
			: '').

   ($g['inn'] ? '<tr><td class="label w175">ИНН:<td>'.$g['inn'] : '').
   ($g['kpp'] ? '<tr><td class="label w175">КПП:<td>'.$g['kpp'] : '').
  ($g['okud'] ? '<tr><td class="label w175">ОКУД:<td>'.$g['okud'] : '').
  ($g['okpo'] ? '<tr><td class="label w175">ОКПО:<td>'.$g['okpo'] : '').
 ($g['okved'] ? '<tr><td class="label w175 top">Вид деятельности<br />по ОКВЭД:'.
	                '<td class="top">'.$g['okved']
 : '').
			'</table>';
}
function setup_org_post($g) {//Должностные лица организации
	if(empty($g))
		return '';

	if(!$g['post_boss'] && !$g['post_accountant'])
		return '';

	return
		'<div class="hd2 mt20">Должностные лица</div>'.
		'<table class="t">'.
		  ($g['post_boss'] ? '<tr><td class="label w175">Руководитель:<td>'.$g['post_boss'] : '').
	($g['post_accountant'] ? '<tr><td class="label w175">Главный бухгалтер:<td>'.$g['post_accountant'] : '').
		'</table>';
}
function setup_org_bank($org_id, $bank) {//Банки организации
	$spisok = 'Банки не определены.';

	if(!empty($bank)) {
		$spisok = '';
		foreach($bank as $r) {
			$spisok .=
			'<table class="t bank w100p">'.
				'<tr><td class="label w175">БИК:'.
					'<td>'.$r['bik'].
						'<div onclick="setupBankDel('.$r['id'].')" class="icon icon-del fr'._tooltip('Удалить банк', -40).'</div>'.
						'<div onclick="setupBankEdit('.$org_id.','.$r['id'].')" class="icon icon-edit fr'._tooltip('Редактировать данные банка', -88).'</div>'.
				'<tr><td class="label top">Наименование банка:<td>'.$r['name'].
				'<tr><td class="label">Корреспондентский счёт:<td>'.$r['account_corr'].
				'<tr><td class="label">Расчётный счёт:<td>'.$r['account'].
			'</table>';
		}
	}

	return
	'<div class="hd2 mt20">'.
		'Банки'.
		'<button class="vk small fr" onclick="setupBankEdit('.$org_id.')">Добавить банк</button>'.
	'</div>'.
	'<div id="bank-spisok">'.$spisok.'</div>';

}
function setupNalogSystem($i='all') {//системы налогообложения
	$arr = array(
		0 => 'не указана',
		1 => 'ОСН',
		2 => 'УСН доходы (6%)',
		3 => 'УСН доходы-расходы (15%)',
		4 => 'ЕНВД',
		5 => 'Патент'
	);

	if($i == 'all')
		return $arr;

	if($i == 'js')
		return _selJson($arr);

	if(!isset($arr[$i]))
		return _cacheErr('неизвестный ключ системы налогообложения', $i);

	return $arr[$i];
}
function setupNds($i='all') {//НДС
	$arr = array(
		0 => 'не выбран',
		1 => 'без НДС',
		2 => '10%',
		3 => '18%'
	);

	if($i == 'all')
		return $arr;

	if($i == 'js')
		return _selJson($arr);

	if(!isset($arr[$i]))
		return _cacheErr('неизвестный ключ НДС', $i);

	return $arr[$i];
}
function setup_org_nalog($g) {//Налоговый учёт организации
	return
	'<script>'.
		'var NALOG_SYSTEM='.setupNalogSystem('js').','.
			'NDS='.setupNds('js').';'.
	'</script>'.
	'<div class="hd2 mt20">'.
		'Налоговый учёт'.
		'<div onclick="setupNalogEdit('.$g['id'].')" class="icon icon-edit fr'._tooltip('Настроить налоговый учёт', -80).'</div>'.
	'</div>'.
	'<table class="t">'.
		'<tr><td class="label w175">Система налогообложения:'.
			'<td>'.setupNalogSystem($g['nalog_system']).
				'<input type="hidden" id="nalog_system'.$g['id'].'" value="'.$g['nalog_system'].'" />'.
		'<tr><td class="label ">НДС:'.
			'<td>'.setupNds($g['nds']).
				'<input type="hidden" id="nds'.$g['id'].'" value="'.$g['nds'].'" />'.
	'</table>';
}

function setup_service() {
	$sql = "SELECT
				*,
				0 `active`
			FROM `_zayav_service`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	if(!$spisok = query_arr($sql))
		return 'Виды деятельности не определены.';

	$send = '';
	foreach($spisok as $r) {
		$send .=
		'<div class="unit" val="'.$r['id'].'">'.
			(SA ? _iconEdit() : '').
			'<input type="hidden" class="name" value="'.$r['name'].'">'.
			'<h1>'.$r['head'].'</h1>'.
			'<h2>'.$r['about'].'</h2>'.
		'</div>';
	}

	return
	'<div id="setup-service">'.
		'<div class="headName">Виды деятельности</div>'.
		$send.
	'</div>';
}

function setup_expense() {
	if(!_viewerMenuAccess(19))
		return _err('Недостаточно прав: категории расходов.');

	return
	'<div id="setup_expense">'.
		setupPath(array(
			'name' => 'Категории расходов организации',
			'add_name' => 'Новая категория',
			'add_func' => 'setupExpenseEdit()'
		)).
		'<div id="spisok" class="mar10">'.setup_expense_spisok().'</div>'.
	'</div>';
}
function setup_expense_spisok() {
	$sql = "SELECT
				*,
				0 `sub`,
				0 `count`,
				0 `deleted`
			FROM `_money_expense_category`
			WHERE `app_id`=".APP_ID." OR !`app_id`
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return 'Список пуст.';

	//количество подкатегорий
	$sql = "SELECT
				DISTINCT `category_id`,
				COUNT(`id`) `sub`
			FROM `_money_expense_category_sub`
			WHERE `app_id`=".APP_ID."
			GROUP BY `category_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['category_id']]['sub'] = $r['sub'];

	$sql = "SELECT
				DISTINCT `category_id`,
				COUNT(`id`) `count`
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `category_id`
			GROUP BY `category_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['category_id']]['count'] = $r['count'];

	$sql = "SELECT
				DISTINCT `category_id`,
				COUNT(`id`) `count`
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `deleted`
			  AND `category_id`
			GROUP BY `category_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['category_id']]['deleted'] = $r['count'];

	$send =
		'<table class="_spisok">'.
			'<tr><th class="name">Наименование'.
				'<th class="w70">Кол-во<br />под-<br />категорий'.
		  (SA ? '<th class="w50">Кол-во<br />записей' : '').
				'<th class="w35">'.
		'</table>'.
		'<dl class="_sort" val="_money_expense_category">';

	foreach($spisok as $id => $r)
		$send .= '<dd val="'.$id.'">'.
			'<table class="_spisok">'.
				'<tr><td class="curM">'.
						($id == 1 ? '<span class="name">'.$r['name'].'</span>' :
									'<a class="name" href="'.URL.'&p=setup&d=expense&id='.$id.'">'.$r['name'].'</a>'
						).
						'<div class="about">'._br($r['about']).'</div>'.
					'<td class="w70 center">'.($r['sub'] ? $r['sub'] : '').

			(SA ?	'<td class="w50 center">'.
						($r['count'] ? $r['count'] : '').
						($r['deleted'] ? '<em class="'._tooltip('удалено', -30).'('.$r['deleted'].')</em>' : '')
			: '').

					'<td class="w35 topi">'.
						($id != 1 ? _iconEdit($r) : '').
						($id != 1 && !$r['count'] && !$r['deleted'] ? _iconDel($r) : '').
			'</table>';
	$send .= '</dl>';
	return $send;
}
function setup_expense_sub($id) {
	if(!_viewerMenuAccess(19))
		return _err('Недостаточно прав: категории расходов.');

	$sql = "SELECT *
			FROM `_money_expense_category`
			WHERE `app_id`=".APP_ID."
			  AND `id`!=1
			  AND `id`=".$id;
	if(!$cat = query_assoc($sql))
		return 'Категории id = '.$id.' не существует.';

	return
	'<script>'.
		'var CAT_ID='.$id.','.
			'CAT_NAME="'.$cat['name'].'";'.
	'</script>'.
	'<div id="setup_expense_sub">'.
		setupPath(array(
			'name' => $cat['name'],
			'link_name' => 'Категории расходов',
			'link_d' => 'expense',
			'add_name' => 'Добавить подкатегорию',
			'add_func' => 'setupExpenseSubEdit()',
		)).
		'<div id="spisok" class="mar10">'.setup_expense_sub_spisok($id).'</div>'.
	'</div>';
}
function setup_expense_sub_spisok($id) {
	$sql = "SELECT
				*,
				0 `count`,
				0 `deleted`
			FROM `_money_expense_category_sub`
			WHERE `app_id`=".APP_ID."
			  AND `category_id`=".$id."
			ORDER BY `name`";
	$arr = query_arr($sql);
	if(empty($arr))
		return 'Список пуст.';

	$sql = "SELECT
				DISTINCT `category_sub_id`,
				COUNT(`id`) `count`
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `category_id`=".$id."
			  AND `category_sub_id`
			GROUP BY `category_sub_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$arr[$r['category_sub_id']]['count'] = $r['count'];

	$sql = "SELECT
				DISTINCT `category_sub_id`,
				COUNT(`id`) `count`
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `deleted`
			  AND `category_id`=".$id."
			  AND `category_sub_id`
			GROUP BY `category_sub_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$arr[$r['category_sub_id']]['deleted'] = $r['count'];

	$send = '<table class="_spisok">'.
				'<tr><th>Наименование'.
				'<th class="count">Кол-во<br />записей'.
				'<th>';
	foreach($arr as $r)
		$send .= '<tr val="'.$r['id'].'">'.
			'<td class="name">'.$r['name'].
			'<td class="count">'.
				($r['count'] ? $r['count'] : '').
				($r['deleted'] ? '<em class="'._tooltip('удалено', -30).'('.$r['deleted'].')</em>' : '').
			 '<td class="ed">'._iconEdit($r)._iconDel($r);

	$send .= '</table>';

	return $send;
}


function setup_zayav_status() {
	if(!_viewerMenuAccess(16))
		return _err('Недостаточно прав: статусы заявок.');

	return
	'<div id="setup_zayav_status">'.
		setupPath(array(
			'name' => 'Статусы заявок',
			'add_name' => 'Новый статус',
			'add_func' => 'setupZayavStatus()'
		)).
		'<div id="status-spisok" class="mar10">'.setup_zayav_status_spisok().'</div>'.
	'</div>';
}
function setup_zayav_status_spisok() {
	$sql = "SELECT
	            *,
	            0 `next`
			FROM `_zayav_status`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		$spisok = setup_zayav_status_default();

	$spisok = setup_zayav_status_next($spisok);

	$send =
		'<table class="_spisok">'.
			'<tr><th class="name">Наименование'.
				'<th class="ed">'.
		'</table>'.
		'<dl class="_sort" val="_zayav_status">';

	foreach($spisok as $r)
		$send .= '<dd val="'.$r['id'].'">'.
			'<table class="_spisok">'.
				'<tr><td class="name curM'.($r['default'] ? ' b' : '').'" style="background-color:#'.$r['color'].'" val="'.$r['color'].'">'.
						'<span>'.$r['name'].'</span>'.
						'<div class="about">'.$r['about'].'</div>'.
		 ($r['nouse'] ? '<div class="dop">Не использовать повторно</div>' : '').
		  ($r['hide'] ? '<div class="dop">Скрывать заявку из общего списка</div>' : '').
	  ($r['executer'] ? '<div class="dop">Указывать исполнителя</div>' : '').
		  ($r['srok'] ? '<div class="dop">Уточнять срок</div>' : '').
	  ($r['day_fact'] ? '<div class="dop">Уточнять фактический день</div>' : '').
	   ($r['accrual'] ? '<div class="dop">Вносить начисление</div>' : '').
		($r['remind'] ? '<div class="dop">Добавлять напоминание</div>' : '').
					'<td class="ed">'.
						_iconEdit($r + array('class'=>'status-edit')).
						_iconDel($r).
			'</table>'.
			'<input type="hidden" class="nouse" value="'.$r['nouse'].'" />'.
			'<input type="hidden" class="hide" value="'.$r['hide'].'" />'.
			'<input type="hidden" class="next" value="'.$r['next'].'" />'.
			'<input type="hidden" class="executer" value="'.$r['executer'].'" />'.
			'<input type="hidden" class="srok" value="'.$r['srok'].'" />'.
			'<input type="hidden" class="accrual" value="'.$r['accrual'].'" />'.
			'<input type="hidden" class="remind" value="'.$r['remind'].'" />'.
			'<input type="hidden" class="day_fact" value="'.$r['day_fact'].'" />';
	$send .= '</dl>';
	setup_zayav_status_next_js();
	return $send;
}
function setup_zayav_status_default() {//формирование списка статусов по умолчанию
	$sql = "SELECT *
			FROM `_zayav_status_default`
			ORDER BY `id`";
	$spisok = query_arr($sql);

	$values = array();
	foreach($spisok as $id => $r)
		$values[] = "(
			".APP_ID.",
			'".$r['name']."',
			'".$r['about']."',
			'".$r['color']."',
			".$r['default'].",
			".$id.",
			".$id."
		)";

	$sql = "INSERT INTO `_zayav_status` (
				`app_id`,
				`name`,
				`about`,
				`color`,
				`default`,
				`sort`,
				`id_old`
			) VALUES ".implode(',', $values);
	query($sql);

	//применение новых статусов к заявкам
	$sql = "UPDATE `_zayav` `z`
			SET `status_id`=(

				SELECT `id`
				FROM `_zayav_status`
				WHERE `app_id`=".APP_ID."
				  AND `id_old`=`z`.`status_id`
				LIMIT 1

			)
			WHERE `app_id`=".APP_ID."
			  AND `status_id`";
	query($sql);

	//применение новых статусов к заявкам
	$sql = "UPDATE `_history` `h`
			SET `v1`=(
					SELECT `id`
					FROM `_zayav_status`
					WHERE `app_id`=".APP_ID."
					  AND `id_old`=`h`.`v1`
					LIMIT 1
				),
				`v2`=(
					SELECT `id`
					FROM `_zayav_status`
					WHERE `app_id`=".APP_ID."
					  AND `id_old`=`h`.`v2`
					LIMIT 1
				)
			WHERE `app_id`=".APP_ID."
			  AND `type_id`=71";
	query($sql);

	xcache_unset(CACHE_PREFIX.'zayav_status');
	_appJsValues();

	$sql = "SELECT
	            *,
	            0 `next`
			FROM `_zayav_status`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			ORDER BY `sort`";
	return query_arr($sql);
}
function setup_zayav_status_next($spisok) {//получение ids следующих статусов
	$sql = "SELECT *
			FROM `_zayav_status_next`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['status_id']]['next'] .= ','.$r['next_id'];

	foreach($spisok as $id => $r)
		if($r['next'])
			$spisok[$id]['next'] = substr($r['next'], 2, strlen($r['next']) - 2);

	return $spisok;
}
function setup_zayav_status_next_js() {//получение ids следующих статусов для values
	$spisok = array();
	$sql = "SELECT *
			FROM `_zayav_status_next`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['status_id']][$r['next_id']] = 1;

	if(!$spisok)
		return '{}';

	return str_replace('"', '', json_encode($spisok));
}
function setupZayavStatusDefaultDrop($default) {//сброс статуса по умолчанию, если устанавливается новое умолчание
	if(!$default)
		return false;

	$sql = "UPDATE `_zayav_status`
			SET `default`=0,
				`nouse`=0
			WHERE `app_id`=".APP_ID."
			  AND `default`";
	query($sql);

	return true;
}


function setup_zayav_expense() {//категории расходов по заявке
	if(!_viewerMenuAccess(17))
		return _err('Недостаточно прав: расходы по заявке.');

	return
	'<div id="setup_zayav_expense">'.
		setupPath(array(
			'name' => 'Настройка категорий расходов по заявке',
			'add_name' => 'Добавить',
			'add_func' => 'setupZayavExpense()'
		)).
		'<div id="spisok" class="mar10">'.setup_zayav_expense_spisok().'</div>'.
	'</div>';
}
function setup_zayav_expense_spisok() {
	$sql = "SELECT
				*,
				0 `use`
			FROM `_zayav_expense_category`
			WHERE `app_id`=".APP_ID."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return 'Список пуст.';

	$sql = "SELECT
				DISTINCT `category_id`,
				COUNT(`id`) `use`
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `category_id`
			GROUP BY `category_id`";
	$q = query($sql);
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
	foreach($spisok as $id => $r) {
		$param = '';
		if($r['dop'] == 4 && $r['param'])
			$param = 'Ведение прикреплённых счетов';
		$send .=
		'<dd val="'.$id.'">'.
			'<table class="_spisok">'.
				'<tr><td class="name curM">'.$r['name'].
					'<td class="dop">'.
						($r['dop'] ? _zayavExpenseDop($r['dop']) : '').
						'<div class="param-info">'.$param.'</div>'.
						'<input type="hidden" class="hdop" value="'.$r['dop'].'" />'.
						'<input type="hidden" class="param" value="'.$r['param'].'" />'.
					'<td class="use">'.($r['use'] ? $r['use'] : '').
					'<td class="ed">'.
						_iconEdit().
						(!$r['use'] ? _iconDel() : '').
			'</table>';

	}
	$send .= '</dl>';
	return $send;
}

function setup_salary_list() {
	if(!_viewerMenuAccess(22))
		return _err('Недостаточно прав: '.LIST_VYDACI.'.');

	return
	setupPath(array(
		'name' => LIST_VYDACI.': настройка'
	)).
	'<div class="spisok mar10">'.setup_salary_list_spisok().'</div>'.
	'<div class="center mt20"><button class="vk" onclick="setupSalaryListSave()">Сохранить</button></div>';
}
function setup_salary_list_spisok() {
	$spisok = salary_list_head();
	$pole = array();
	$check = array();

	if(_app('salary_list_setup'))
		foreach(explode(',', _app('salary_list_setup')) as $k) {
			$pole[$k] = $spisok[$k];
			$check[$k] = 1;
			unset($spisok[$k]);
		}

	foreach($spisok as $id => $name) {
		$pole[$id] = $spisok[$id];
		$check[$id] = 0;
	}

	$send =
	'<table class="_spisok">'.
		'<tr><th class="name">Название столбца в документе XLS'.
	'</table>'.
	'<dl class="_sort no">';
	foreach($pole as $id => $name)
		$send .=
		'<dd val="'.$id.'">'.
			'<table class="_spisok">'.
				'<tr><td class="ch">'._check('ch'.$id, '', $check[$id]).
					'<td class="name" style="cursor:move">'.$name.
			'</table>';
	$send .= '</dl>';
	return $send;
}
function salary_list_head() {
	return array(
		1 => '№ заявки',
		2 => 'Название заявки',
		3 => '№ дог.',
		4 => 'Адрес',
		5 => 'Дата выполнения',
		6 => 'Сумма',
		7 => 'Описание',
		8 => 'Дата начисления'
	);
}






function setup_rubric() {
	if(!_viewerMenuAccess(18))
		return _err('Недостаточно прав: рубрики объявлений.');

	return
	'<div id="setup_rubric">'.
		setupPath(array(
			'name' => 'Рубрики объявлений',
			'add_name' => 'Новая рубрика',
			'add_func' => 'setupRubricEdit()'
		)).
		'<div id="spisok" class="mar10">'.setup_rubric_spisok().'</div>'.
	'</div>';
}
function setup_rubric_spisok() {
	$sql = "SELECT
				*,
				0 `sub`,
				0 `zayav`
			FROM `_setup_rubric`
			WHERE `app_id`=".APP_ID."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return 'Список пуст.';


	//подрубрики
	$sql = "SELECT
				DISTINCT `rubric_id`,
				COUNT(`id`) `count`
			FROM `_setup_rubric_sub`
			WHERE `app_id`=".APP_ID."
			  AND `rubric_id`
			GROUP BY `rubric_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['rubric_id']]['sub'] = $r['count'];


	//использование в заявках
	$sql = "SELECT
				DISTINCT `rubric_id`,
				COUNT(`id`) `count`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `rubric_id`
			GROUP BY `rubric_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['rubric_id']]['zayav'] = $r['count'];

	$send =
		'<table class="_spisok">'.
			'<tr><th class="name">Наименование'.
				'<th class="w100">Подрубрики'.
				'<th class="w100">Кол-во<br />объявлений'.
				'<th class="w35">'.
		'</table>'.
		'<dl class="_sort" val="_setup_rubric">';
	foreach($spisok as $id => $r) {
		$nodel = $r['sub'] || $r['zayav'];
		$send .='<dd val="'.$id.'">'.
			'<table class="_spisok">'.
				'<tr><td class="name curM"><a href="'.URL.'&p=setup&d=rubric&id='.$id.'">'.$r['name'].'</a>'.
					'<td class="w100 center">'.($r['sub'] ? $r['sub'] : '').
					'<td class="w100 center">'.($r['zayav'] ? $r['zayav'] : '').
					'<td class="w35">'.
						_iconEdit($r).
						_iconDel(array('nodel' => $nodel) + $r).
			'</table>';
	}

	$send .= '</dl>';
	return $send;
}

function setup_rubric_sub($id) {
	if(!_viewerMenuAccess(18))
		return _err('Недостаточно прав: рубрики объявлений.');

	$sql = "SELECT *
			FROM `_setup_rubric`
			WHERE `app_id`=".APP_ID."
			  AND `id`=".$id;
	if(!$rub = query_assoc($sql))
		return 'Рубрики id = '.$id.' не существует. ';

	return
	'<script>var RUBRIC_ID='.$id.';</script>'.
		setupPath(array(
			'link_name' => 'Рубрики объявлений',
			'link_d' => 'rubric',
			'name' => $rub['name'],
			'add_name' => 'Новая подрубрика',
			'add_func' => 'setupRubricSubEdit()'
		)).
	'<div id="setup_rubric_sub">'.
		'<div id="spisok" class="mar10">'.setup_rubric_sub_spisok($id).'</div>'.
	'</div>';
}
function setup_rubric_sub_spisok($rubric_id) {
	$sql = "SELECT
				*,
				0 `zayav`
			FROM `_setup_rubric_sub`
			WHERE `app_id`=".APP_ID."
			  AND `rubric_id`=".$rubric_id."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return 'Список пуст.';

	//использование в заявках
	$sql = "SELECT
				DISTINCT `rubric_id_sub`,
				COUNT(`id`) `count`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `rubric_id`=".$rubric_id."
			  AND `rubric_id_sub`
			GROUP BY `rubric_id_sub`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['rubric_id_sub']]['zayav'] = $r['count'];

	$send =
		'<table class="_spisok">'.
			'<tr><th class="name">Наименование'.
				'<th class="zayav w100">Кол-во<br />объявлений'.
				'<th class="w35">'.
		'</table>'.
		'<dl class="_sort" val="_setup_rubric_sub">';
	foreach($spisok as $id => $r)
		$send .='<dd val="'.$id.'">'.
			'<table class="_spisok">'.
				'<tr><td class="name curM">'.$r['name'].
					'<td class="w100 center">'.($r['zayav'] ? $r['zayav'] : '').
					'<td class="w35">'.
						_iconEdit($r).
						_iconDel(array('nodel' => $r['zayav']) + $r).
			'</table>';

	$send .= '</dl>';
	return $send;
}






function setup_cartridge() {
	if(!_viewerMenuAccess(21))
		return _err('Недостаточно прав: картриджи.');

	return
	'<div id="setup-cartridge">'.
		setupPath(array(
			'name' => 'Управление заправкой картриджей',
			'add_name' => 'Внести новый картридж',
			'add_func' => 'cartridgeNew()'
		)).
		'<div id="spisok" class="mar10">'.setup_cartridge_spisok().'</div>'.
	'</div>';
}
function setup_cartridge_spisok($edit_id=0) {
	$send = '';
	foreach(_cartridgeType() as $type_id => $name) {
		$sql = "SELECT `s`.*,
				   COUNT(`z`.`id`) `count`
			FROM `_setup_cartridge` `s`
				LEFT JOIN `_zayav_cartridge` AS `z`
				ON `s`.`id`=`z`.`cartridge_id`
			WHERE `type_id`=".$type_id."
			GROUP BY `s`.`id`
			ORDER BY `name`";
		if(!$spisok = query_arr($sql))
			continue;

		$send .=
			'<div class="type">'.$name.':</div>'.
			'<table class="_spisok">' .
				'<tr><th class="n">№' .
					'<th class="name">Модель' .
					'<th class="cost">Вид услуги:<br />Запр./восст./чип' .
					'<th class="count">Кол-во' .
					'<th class="set">';
		$n = 1;
		foreach ($spisok as $id => $r) {
			$cost = array();
			if($r['cost_filling'])
				$cost[] = '<span class="'._tooltip('Заправка', -30).$r['cost_filling'].'</span>';
			if($r['cost_restore'])
				$cost[] = '<span class="'._tooltip('Восстановление', -48).$r['cost_restore'].'</span>';
			if($r['cost_chip'])
				$cost[] = '<span class="'._tooltip('Замена чипа', -40).$r['cost_chip'].'</span>';
			$send .=
				'<tr'.($edit_id == $r['id'] ? ' class="edited"' : '').'>' .
					'<td class="n">'.($n++) .
					'<td class="name">'.$r['name'] .
					'<td class="cost">'.implode(' / ', $cost) .
						'<input type="hidden" class="type_id" value="'.$r['type_id'].'" />' .
						'<input type="hidden" class="filling" value="'.$r['cost_filling'].'" />' .
						'<input type="hidden" class="restore" value="'.$r['cost_restore'].'" />' .
						'<input type="hidden" class="chip" value="'.$r['cost_chip'].'" />' .
					'<td class="count">'.($r['count'] ? $r['count'] : '') .
					'<td class="set">' .
						'<div val="'.$id.'" class="img_edit'._tooltip('Изменить', -33).'</div>';
		}
		$send .= '</table>';
	}
	return $send ? $send : 'Список пуст.';
}













function setup_tovar() {
	if(!_viewerMenuAccess(20))
		return _err('Недостаточно прав: товары.');

	return setup_tovar_category();
	switch(@$_GET['d1']) {
		case 'category': return setup_tovar_category();
		case 'name': return setup_tovar_name();
		case 'vendor': return setup_tovar_vendor();
	}

	return
	'<div id="setup_tovar">'.
		'<div class="headName">Настройки товаров</div>'.
		'<a href="'.URL.'&p=setup&d=tovar&d1=category">Настроить категории товаров</a>'.
		'<br />'.
		'<a href="'.URL.'&p=setup&d=tovar&d1=name">Названия товаров</a>'.
		'<br />'.
		'<a href="'.URL.'&p=setup&d=tovar&d1=vendor">Производители</a>'.
	'</div>';
}

function setup_tovar_category() {
	//Категории можно вносить свои, либо подключать из существующих
	//При подключении существующих категорий все товары становятся доступными для текущего приложения
	return
	'<div id="setup_tovar_category">'.
		setupPath(array(
			'name' => 'Категории товаров'
		)).
		'<div class="mar10">'.
			'<div class="_info">'.
				'<u>Категории</u> предназначены для разделения товаров по похожим свойствам или характеристикам. '.
				'<br />'.
				'Возможно создавать собственные категории товаров, либо подключать готовые категории из каталога.'.
			'</div>'.
			'<table id="but">'.
				'<tr><td><button class="vk" onclick="setupTovarCategoryEdit()">Создать новую категорию</button>'.
					'<td><button class="vk" id="join">Подключить категории из каталога</button>'.
			'</table>'.
			'<div id="spisok">'.setup_tovar_category_spisok().'</div>'.
		'</div>'.
	'</div>';
}
function setup_tovar_category_spisok() {//категории товаров
	$sql = "SELECT *
			FROM `_tovar_category_use`
			WHERE `app_id`=".APP_ID."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return 'Список пуст.';

	$sql = "SELECT *
			FROM `_tovar_category`
			WHERE `id` IN ("._idsGet($spisok, 'category_id').")";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		foreach($spisok as $sp)
			if($r['id'] == $sp['category_id']) {
				$spisok[$sp['id']]['name'] = $r['name'];
				continue;
			}

	$send = '<table class="_spisok">'.
				'<tr><th class="name">Наименование'.
					'<th class="ed">'.
			'</table>'.
			'<dl class="_sort" val="_tovar_category_use">';
	foreach($spisok as $r)
		$send .= '<dd val="'.$r['id'].'">'.
			'<table class="_spisok">'.
				'<tr val="'.$r['category_id'].'">'.
					'<td class="name">'.$r['name'].
					'<td class="ed">'._iconEdit($r)._iconDel($r).
			'</table>';
	$send .= '</dl>';

	return $send;
}

function setup_tovar_name() {
	return
	'<div id="setup_tovar_name">'.
		'<div class="headName">Названия товаров<a class="add">Добавить</a></div>'.
		'<div class="_info">'.
			'<u>Названия товаров</u> предназначены для краткого и точного описания товара. '.
			'<br />'.
			'Обратите внимание, что одно и то же название может содержаться в разных категориях товаров. '.
			'<br />'.
			'Добавляйте новое название только в случае, если его точно нет в этом списке. '.
		'</div>'.
		'<div id="spisok">'.setup_tovar_name_spisok().'</div>'.
	'</div>';
}
function setup_tovar_name_spisok() {
	$sql = "SELECT
				*,
				0 `tovar`
			FROM `_tovar_name`
			ORDER BY `name`";
	if(!$spisok = query_arr($sql))
		return 'Список пуст.';

	$sql = "SELECT
				`name_id`,
				COUNT(`id`) `count`
			FROM `_tovar`
			WHERE `category_id`
			GROUP BY `name_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['name_id']]['tovar'] = $r['count'];

	$send = '<table class="_spisok">'.
				'<tr><th>Название'.
					'<th>Кол-во<br />товаров'.
					'<th>';
	foreach($spisok as $id => $r)
		$send .= '<tr val="'.$id.'">'.
					'<td class="name">'.$r['name'].' <b>'.$r['id'].'</b>'.
					'<td class="tovar center">'.($r['tovar'] ? $r['tovar'] : '').
					'<td class="ed">'._iconEdit($r)._iconDel($r);
	$send .= '</table>';

	return $send;
}

function setup_tovar_vendor() {
	return
	'<div id="setup_tovar_vendor">'.
		'<div class="headName">Производители товаров<a class="add">Новый производитель</a></div>'.
		'<div class="_info">'.
			'<u>Производители товаров</u>'.
			'<br />'.
		'</div>'.
		'<div id="spisok">'.setup_tovar_vendor_spisok().'</div>'.
	'</div>';
}
function setup_tovar_vendor_spisok() {
	$sql = "SELECT
				*,
				0 `tovar`
			FROM `_tovar_vendor`
			ORDER BY `name`";
	if(!$spisok = query_arr($sql))
		return 'Список пуст.';

		$sql = "SELECT
				`vendor_id`,
				COUNT(`id`) `count`
			FROM `_tovar`
			WHERE `vendor_id`
			GROUP BY `vendor_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['vendor_id']]['tovar'] = $r['count'];

	$send = '<table class="_spisok">'.
				'<tr><th>Название'.
					'<th>Кол-во<br />товаров'.
					'<th>';
	foreach($spisok as $id => $r)
		$send .= '<tr val="'.$id.'">'.
					'<td class="name">'.$r['name'].' <b>'.$r['id'].'</b>'.
					'<td class="tovar center">'.($r['tovar'] ? $r['tovar'] : '').
					'<td class="ed">'._iconEdit($r)._iconDel($r);
	$send .= '</table>';

	return $send;
}













function setup_polosa() {//Купец: стоимость см2 для каждой полосы
	return
	'<div id="setup_polosa">'.
		setupPath(array(
			'name' => 'Стоимость см&sup2; рекламы для каждой полосы',
			'add_name' => 'Новая полоса',
			'add_func' => 'setupPolosaCostEdit()'
		)).
		'<div id="spisok" class="mar10">'.setup_polosa_spisok().'</div>'.
	'</div>';
}
function setup_polosa_spisok() {
	$sql = "SELECT *
			FROM `_setup_gazeta_polosa_cost`
			WHERE `app_id`=".APP_ID."
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return 'Список пуст.';

	$send =
		'<table class="_spisok">'.
			'<tr><th class="name">Полоса'.
				'<th class="cena">Цена за см&sup2;<br />руб.'.
				'<th class="pn">Указывать<br />номер<br />полосы'.
				'<th class="ed">'.
		'</table>'.
		'<dl class="_sort" val="_setup_gazeta_polosa_cost">';
	foreach($spisok as $id => $r)
		$send .='<dd val="'.$id.'">'.
			'<table class="_spisok">'.
				'<tr><td class="name curM">'.$r['name'].
					'<td class="cena center">'.round($r['cena'], 2).
					'<td class="pn center">'.($r['polosa'] ? 'да' : '').
					'<td class="ed">'._iconEdit($r).
			'</table>';
	$send .= '</dl>';
	return $send;
}




function setup_obdop() {//Купец: дополнительные параметры объявлений
	return
	'<div id="setup_obdop">'.
		setupPath(array(
			'name' => 'Дополнительные параметры объявлений'
		)).
		'<div id="spisok" class="mar10">'.setup_obdop_spisok().'</div>'.
	'</div>';
}
function setup_obdop_spisok() {
	$sql = "SELECT *
			FROM `_setup_gazeta_ob_dop`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	if(!$spisok = query_arr($sql))
		return 'Список пуст.';

	$send =
		'<table class="_spisok">'.
			'<tr><th>Наименование'.
				'<th>Стоимость<br />руб.'.
				'<th>';
	foreach($spisok as $r)
		$send .= '<tr val="'.$r['id'].'">'.
			'<td class="name">'.$r['name'].
			'<td class="cena w100 center">'._cena($r['cena']).
			'<td class="ed">'._iconEdit($r);
	$send .= '</table>';
	return $send;
}



function setup_oblen() {//Купец: стоимость длины объявления
	return
	'<div id="setup_oblen">'.
		setupPath(array(
			'name' => 'Настройка стоимости длины объявления'
		)).
		'<table>'.
            '<tr><td>Первые'.
				'<td><input type="text" value="'.TXT_LEN_FIRST.'" id="txt_len_first" />'.
				'<td>символов:'.
                '<td><input type="text" value="'.TXT_CENA_FIRST.'" id="txt_cena_first" /> руб.'.
            '<tr><td>Последующие'.
				'<td><input type="text" value="'.TXT_LEN_NEXT.'" id="txt_len_next" />'.
				'<td>символов:'.
                '<td><input type="text" value="'.TXT_CENA_NEXT.'" id="txt_cena_next" /> руб.'.
        '</table>'.
		'<button class="vk" onclick="setupObLenEdit()">Сохранить</button>'.
	'</div>';
}





function setup_gn() {
	define('CURRENT_YEAR', strftime('%Y', time()));
	return
	'<script>var GN_MAX="'._gn('gn_max').'";</script>'.
	'<div id="setup_gn">'.
		setupPath(array(
			'name' => 'Номера выпусков газеты',
			'add_name' => 'Новый номер',
			'add_func' => 'setupGnEdit()'
		)).
		'<div class="mar10">'.
			'<div id="dopLinks">'.setup_gn_year().'</div>'.
			'<div id="spisok">'.setup_gn_spisok().'</div>'.
		'</div>'.
	'</div>';
}
function setup_gn_year($y=CURRENT_YEAR) {
	$sql = "SELECT
            	SUBSTR(MIN(`day_public`),1,4) AS `begin`,
            	SUBSTR(MAX(`day_public`),1,4) AS `end`,
            	MAX(`general_nomer`) AS `max`
            FROM `_setup_gazeta_nomer`
   			WHERE `app_id`=".APP_ID."
            LIMIT 1";
	$r = mysql_fetch_assoc(query($sql));
	if(!$r['begin'])
		$r = array(
			'begin' => CURRENT_YEAR,
			'end' => CURRENT_YEAR
		);
	$send = '';
	for($n = $r['begin']; $n <= $r['end'] + 1; $n++)
		$send .= '<a class="link'.($n == $y ? ' sel' : '').'">'.$n.'</a>';
	return $send;
}
function setup_gn_spisok($y=CURRENT_YEAR, $gnedit=0) {
	$sql = "SELECT *
			FROM `_setup_gazeta_nomer`
			WHERE `app_id`=".APP_ID."
			  AND `day_public` LIKE '".$y."-%'
			ORDER BY `general_nomer`";
	$q = query($sql);
	if(!mysql_num_rows($q))
		return
			'Номера газет, которые будут выходить в '.$y.' году, не определены.'.
			'<button class="vk">Создать список</button>';
	$send =
		'<a id="gn-clear" val="'.$y.'">Очистить список за '.$y.' год</a>'.
		'<table class="_spisok">'.
			'<tr><th>Номер<br />выпуска'.
				'<th>День отправки<br />в печать'.
				'<th>День выхода'.
				'<th>Кол-во<br />полос'.
				'<th>';
	$cur = time() - 86400;
	while($r = mysql_fetch_assoc($q)) {
		$grey = _gn($r['id'], 'lost') ? 'grey' : '';
		$edit = $gnedit == $r['general_nomer'] ? ' edit' : '';
		$class = $grey || $edit ? ' class="'.$grey.$edit.'"' : '';
		$send .= '<tr'.$class.'>'.
			'<td class="nomer center"><b>'.$r['week_nomer'].'</b> (<span>'.$r['general_nomer'].'</span>)'.
			'<td class="print r">'.FullData($r['day_print'], 0, 1, 1).'<s>'.$r['day_print'].'</s>'.
			'<td class="pub r">'.FullData($r['day_public'], 0, 1, 1).'<s>'.$r['day_public'].'</s>'.
			'<td class="pc">'.$r['polosa_count'].
			'<td class="ed">'._iconEdit($r)._iconDel($r);
	}
	$send .= '</table>';
	return $send;
}









function setup_schet_pay() {//счёт на оплату
	return
	setupPath(array(
		'name' => 'Настройки счёта на оплату'
	));
}









function setup_document_template() {//шаблоны документов
	setup_document_template_default_test();
	return
	setupPath(array(
		'name' => 'Шаблоны документов для печати'
	)).
	'<div class="mar10">'.
		'<div class="_info">Настройка формирования документов из шаблонов в формате <b>Word</b> и <b>Excel</b>.</div>'.
		'<div id="spisok" class="mt20">'.setup_document_template_spisok().'</div>'.
	'</div>';
}
function setup_document_template_default_test() {//проверка на наличие шаблонов по умолчанию
	$sql = "SELECT `use`,1
			FROM `_template`
			WHERE `app_id`=".APP_ID."
			  AND LENGTH(`use`)";
	$tmp = query_ass($sql);

	$sql = "SELECT *
			FROM `_template_default`
			ORDER BY `id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		if(isset($tmp[$r['use']]))
			continue;
		$sql = "INSERT INTO `_template` (
					`app_id`,
					`name`,
					`attach_id`,
					`name_link`,
					`name_file`,
					`use`
				) VALUES (
					".APP_ID.",
					'".addslashes($r['name'])."',
					".$r['attach_id'].",
					'".addslashes($r['name_link'])."',
					'".addslashes($r['name_file'])."',
					'".addslashes($r['use'])."'
				)";
		query($sql);
	}
}
function setup_document_template_spisok() {
	$but = '<button class="vk small fr" onclick="setupTemplateEdit()">Создать новый шаблон</button>';

	$sql = "SELECT *
			FROM `_template`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	if(!$spisok = query_arr($sql))
		return '<div>Шаблонов нет.'.$but.'</div>';

	$count = count($spisok);
	$send =
		'<div>'.
			'<u>Доступ'._end($count, 'ен', 'но').' '.$count.' шаблон'._end($count, '', 'а', 'ов').':</u>'.
			$but.
		'</div>';
	$n = 1;
	foreach($spisok as $r)
		$send .=
			'<div class="mt'.($n == 1 ? 10 : 5).'">'.($n++).'. '.
				'<a href="'.URL.'&p=setup&d=document_template&id='.$r['id'].'">'.$r['name'].'</a>'.
			'</div>';

	return $send;
}
function setup_document_template_info() {
	$send = '<div id="setup_document_template_info">';

	if(!$id = _num(@$_GET['id']))
		return $send._err('Некорректный идентификатор шаблона.').'</div>';

	$sql = "SELECT *
			FROM `_template`
			WHERE `app_id`=".APP_ID."
			  AND `id`=".$id;
	if(!$r = query_assoc($sql))
		return $send._err('Шаблона не существует.').'</div>';

	$send .=
	_attachJs(array('id'=>$r['attach_id'])).
	'<script>var TEMPLATE_ID='.$id.';</script>'.
	setupPath(array(
		'name' => $r['name'],
		'link_name' => 'Шаблоны документов',
		'link_d' => 'document_template'
	)).
	'<div class="mar10">'.
		'<table class="bs10 mb20">'.
			'<tr><td class="label r">Название:'.
				'<td><input type="text" id="name" class="w250" value="'.$r['name'].'" />'.
			'<tr><td class="label r">Файл шаблона:'.
				'<td><input type="hidden" id="attach_id" value="'.$r['attach_id'].'" />'.
			'<tr><td class="label r">Текст ссылки:'.
				'<td><input type="text" id="name_link" class="w250" value="'.$r['name_link'].'" />'.
			'<tr><td class="label r">Имя файла документа:'.
				'<td><input type="text" id="name_file" class="w250" value="'.$r['name_file'].'" />'.
			'<tr><td>'.
				'<td><button class="vk save">Сохранить</button>'.
		'</table>'.

		'<div class="headName">Данные для шаблона</div>'.
		'<div class="_info">'.
			'<p><b>Данные для шаблона</b> используются для вставки в <b>документ</b> (файл шаблона), который затем загружается в программу.'.
		//	'<p>Переменные подменяются на данные, которые будут извлечены из базы данных при формировании документа.'.
			'<p>Ниже представлены все возможные данные, разделённые по группам.'.
		'</div>'.
		setup_document_template_group().
	'</div>';

	return $send;
}
function setup_document_template_group() {
	$sql = "SELECT *
			FROM `_template_var_group`
			ORDER BY `sort`";
	if(!$spisok = query_arr($sql))
		return 'Переменных нет.';

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
			'<div class="headBlue curP" onclick="$(this).next().slideToggle()">'.$r['name'].':</div>'.
			'<div class="mb20 dn">'.setup_document_template_var($r['var']).'</div>';

	return $send;
}
function setup_document_template_var($spisok) {
	if(empty($spisok))
		return '';

	$send = '<table class="bs5">';
	foreach($spisok as $r)
		$send .=
		'<tr><td><input type="text" class="b w200" readonly onclick="$(this).select()" value="'.$r['v'].'" />'.
			'<td>'.$r['name'];

	$send .= '</table>';

	return $send;
}




