<?php
function _clientCase($v=array()) {//вывод информации с клиентами для приложения
	$filterDef = $v + array(
		'CLIENT_FILTER_DOLG' => 0,//галочка-фильтр "должники"
		'CLIENT_FILTER_OPL' => 0  //галочка-фильтр "предоплата"
	);
	foreach($filterDef as $name => $key)
		define($name, $key);
	switch(@$_GET['d']) {
		case 'info':
			if(!_num($_GET['id']))
				return 'Страницы не существует';
			return _clientInfo($_GET['id']);
		default:
			$v = array();
			if(HASH_VALUES) {
				$ex = explode('.', HASH_VALUES);
				foreach($ex as $r) {
					$arr = explode('=', $r);
					$v[$arr[0]] = $arr[1];
				}
			} else {
				foreach($_COOKIE as $k => $val) {
					$arr = explode(VIEWER_ID.'_client_', $k);
					if(isset($arr[1]))
						$v[$arr[1]] = $val;
				}
			}
			$v['find'] = unescape(@$v['find']);
			return _client($v);
	}
}//_clientCase()

function _client($v) {
	return client_list($v);
}//_client()
function client_list($v) {// страница со списком клиентов
	$data = client_data($v);
	$v = $data['filter'];
	return
		'<div id="client">'.
			'<table id="find-tab"><tr>'.
				'<td><div id="find"></div>'.
				'<td><div id="buttonCreate"><a>Новый клиент</a></div>'.
			'</table>'.
			'<div class="result">'.$data['result'].'</div>'.
			'<table class="tabLR">'.
				'<tr><td class="left">'.$data['spisok'].
					'<td class="right">'.
						'<div class="filter'.($v['find'] ? ' dn' : '').'">'.
	  (CLIENT_FILTER_DOLG ? _check('dolg', 'Должники', $v['dolg']) : '').
//							_check('active', 'С активными заявками', $v['active']).
//							_check('comm', 'Есть заметки', $v['comm']).
//							_check('opl', 'Внесена предоплата', $v['opl']).
						'</div>'.
			'</table>'.
		'</div>'.
		'<script type="text/javascript">'.
			'var C={'.
				'find:"'.$v['find'].'"'.
			'};'.
		'</script>';
}//client_list()
function clientFilter($v) {
	$default = array(
		'page' => 1,
		'find' => '',
		'dolg' => 0,
		'active' => 0,
		'comm' => 0,
		'opl' => 0
	);
	$filter = array(
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'find' => strtolower(trim(@$v['find'])),
		'dolg' => _bool(@$v['dolg']),
		'active' => _bool(@$v['active']),
		'comm' => _bool(@$v['comm']),
		'opl' => _bool(@$v['opl']),
		'clear' => ''
	);
	foreach($default as $k => $r)
		if($r != $filter[$k]) {
			$filter['clear'] = '<a id="filter_clear">Очистить фильтр</a>';
			break;
		}
	return $filter;
}//clientFilter()
function client_data($v=array()) {// список клиентов
	$filter = clientFilter($v);
	$cond = "`app_id`=".APP_ID." AND `ws_id`=".WS_ID." AND !`deleted`";
	$dolg = 0;
	$plus = 0;

	define('FIND', !empty($filter['find']));

	if(FIND) {
		$find = array();

		$reg = '/(\')/'; // одинарная кавычка '
		if(!preg_match($reg, $filter['find']))
			$find[] = "`find` LIKE '%".$filter['find']."%'";

		$engRus = _engRusChar($filter['find']);
		if($engRus)
			$find[] = "`find` LIKE '%".$engRus."%'";

		$cond .= " AND ".(empty($find) ? " !`id` " : "(".implode(' OR ', $find).")");
	} else {
		if($filter['dolg']) {
			$cond .= " AND `balans`<0";
			$sql = "SELECT SUM(`balans`)
					FROM `_client`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND !`deleted`
					  AND `balans`<0";
			$dolg = abs(query_value($sql, GLOBAL_MYSQL_CONNECT));
		}
		if($filter['active']) {
			$ids = query_ids("SELECT DISTINCT `client_id`
							FROM `zayav`
							WHERE `ws_id`=".WS_ID."
							  AND `zayav_status`=1");
			$cond .= " AND `id` IN (".$ids.")";
		}
		if($filter['comm']) {
			$ids = query_ids("SELECT DISTINCT `table_id`
							FROM `vk_comment`
							WHERE `status` AND `table_name`='client'");
			$cond .= " AND `id` IN (".$ids.")";
		}
		if($filter['opl']) {
			$cond .= " AND `balans`>0";
			$sql = "SELECT SUM(`balans`)
					FROM `_client`
					WHERE `app_id`=".APP_ID."
					  AND `ws_id`=".WS_ID."
					  AND !`deleted`
					  AND `balans`>0";
			$plus = abs(query_value($sql, GLOBAL_MYSQL_CONNECT));
		}
	}

	if(!$all = query_value("SELECT COUNT(`id`) AS `all` FROM `_client` WHERE ".$cond, GLOBAL_MYSQL_CONNECT))
		return array(
			'all' => 0,
			'result' => 'Клиентов не найдено.'.$filter['clear'],
			'spisok' => '<div class="_empty">Клиентов не найдено.</div>',
			'filter' => $filter
		);

	$send['all'] = $all;
	$send['result'] = 'Найден'._end($all, ' ', 'о ').$all.' клиент'._end($all, '', 'а', 'ов').
					  ($dolg ? '<em>(Общая сумма долга = <b id="dolg">'._sumSpace($dolg).'</b> руб.)</em>' : '').
					  ($plus ? '<em>(Сумма = '.$plus.' руб.)</em>' : '').
					  $filter['clear'];
	$send['filter'] = $filter;
	$send['spisok'] = '';

	$page = $filter['page'];
	$limit = 20;
	$start = ($page - 1) * $limit;
	$spisok = array();
	$sql = "SELECT *,
				   0 `zayav_count`,
				   0 `zayav_wait`,
				   0 `zayav_ready`,
				   0 `zayav_fail`,
				   '' `comm`,
				   '' `fio`,
				   '' `phone`
			FROM `_client`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT ".$start.",".$limit;
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q)) {
		if(FIND) {
			$r['org_name'] = _findRegular($filter['find'], $r['org_name']);
			$r['org_phone'] = _findRegular($filter['find'], $r['org_phone']);
			$r['org_fax'] = _findRegular($filter['find'], $r['org_fax'], 1);
			$r['org_adres'] = _findRegular($filter['find'], $r['org_adres'], 1);
			$r['org_inn'] = _findRegular($filter['find'], $r['org_inn'], 1);
			$r['org_kpp'] = _findRegular($filter['find'], $r['org_kpp'], 1);
		}
		$r['person'] = array();
		$spisok[$r['id']] = $r;
	}

	// фио и телефоны клиентов
	$sql = "SELECT *
			FROM `_client_person`
			WHERE `client_id` IN (".implode(',', array_keys($spisok)).")
			ORDER BY `client_id`,`id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$k = 0;
	$client_id = key($spisok);
	while($r = mysql_fetch_assoc($q)) {
		if($client_id != $r['client_id']) {
			$client_id = $r['client_id'];
			$k = 0;
		}

		// совпало ли регулярное выражение в условии быстрого поиска
		$regOk = FIND && (_findRegular($filter['find'], $r['fio'], 1) || _findRegular($filter['find'], $r['phone'], 1) || _findRegular($filter['find'], $r['adres'], 1));

		if(!$k) {
			$spisok[$r['client_id']]['fio'] = (FIND ? _findRegular($filter['find'], $r['fio']) : $r['fio']);
			$spisok[$r['client_id']]['phone'] = (FIND ? _findRegular($filter['find'], $r['phone']) : $r['phone']);
			$spisok[$r['client_id']]['adres'] = (FIND ? _findRegular($filter['find'], $r['adres'], 1) : $r['adres']);
			$spisok[$r['client_id']]['post'] = $r['post'];
		} else {
			if($regOk) // дополнительные доверенные лица отображаются только при совпадении в быстром поиске
				$spisok[$r['client_id']]['person'][] = array(
					'fio' => _findRegular($filter['find'], $r['fio']),
					'phone' => _findRegular($filter['find'], $r['phone'], 1),
					'adres' => _findRegular($filter['find'], $r['adres'], 1),
					'post' => $r['post']
				);
		}
		$k++;
	}
/*
	// общее количество заявок
	$sql = "SELECT
				`client_id` AS `id`,
				COUNT(`id`) AS `count`
			FROM `zayav`
			WHERE `ws_id`=".WS_ID."
			  AND `zayav_status`
			  AND `client_id` IN (".implode(',', array_keys($spisok)).")
			GROUP BY `client_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']]['zayav_count'] = $r['count'];

	//заявки, ожидающие выполнения
	$sql = "SELECT
				`client_id` AS `id`,
				COUNT(`id`) AS `count`
			FROM `zayav`
			WHERE `ws_id`=".WS_ID."
			  AND `zayav_status`=1
			  AND `client_id` IN (".implode(',', array_keys($spisok)).")
			GROUP BY `client_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']]['zayav_wait'] = $r['count'];

	//выполненные заявки
	$sql = "SELECT
				`client_id` AS `id`,
				COUNT(`id`) AS `count`
			FROM `zayav`
			WHERE `ws_id`=".WS_ID."
			  AND `zayav_status`=2
			  AND `client_id` IN (".implode(',', array_keys($spisok)).")
			GROUP BY `client_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']]['zayav_ready'] = $r['count'];

	//отменённые заявки
	$sql = "SELECT
				`client_id` AS `id`,
				COUNT(`id`) AS `count`
			FROM `zayav`
			WHERE `ws_id`=".WS_ID."
			  AND `zayav_status`=3
			  AND `client_id` IN (".implode(',', array_keys($spisok)).")
			GROUP BY `client_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']]['zayav_fail'] = $r['count'];
*/
	//комментарии по клиентам
	$sql = "SELECT
				`table_id` `id`,
				`txt`
			FROM `vk_comment`
			WHERE `status`
			  AND `table_name`='client'
			  AND `table_id` IN (".implode(',', array_keys($spisok)).")
			GROUP BY `table_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['id']]['comm'] = $r['txt'];

	foreach($spisok as $r) {
		$org = $r['category_id'] != 1;
		// список доверенных лиц
		$person = '';
		if(FIND)
			foreach($r['person'] as $p)
				$person .=
					'<tr><td class="label top">'.($p['post'] ? $p['post'] : 'Дов. лицо').':'.
						'<td>'.$p['fio'].
							($p['phone'] ? '<br />'.$p['phone'] : '').
							($p['adres'] ? '<br />'.$p['adres'] : '');

		$phone = $org ? $r['org_phone'] : $r['phone'];

		$left =
			'<table class="l-tab">'.
				'<tr><td class="label top">'._clientCategory($r['category_id']).':'.
					'<td><a href="'.URL.'&p=client&d=info&id='.$r['id'].'">'.($org ? $r['org_name'] : $r['fio']).'</a>'.
	  ($phone ? '<tr><td class="label top">Телефоны:<td>'.$phone : '').
		   (FIND && $r['adres'] ? '<tr><td class="label top">Адрес:<td>'.$r['adres'] : '').
		 (FIND && $r['org_fax'] ? '<tr><td class="label">Факс:<td>'.$r['org_fax'] : '').
	   (FIND && $r['org_adres'] ? '<tr><td class="label top">Адрес:<td>'.$r['org_adres'] : '').
		 (FIND && $r['org_inn'] ? '<tr><td class="label">ИНН:<td>'.$r['org_inn'] : '').
		 (FIND && $r['org_kpp'] ? '<tr><td class="label">КПП:<td>'.$r['org_kpp'] : '').
		     ($org && $r['fio'] ? // отображение первого доверенного лица в организации
				'<tr><td class="label top">'.($r['post'] ? $r['post'] : 'Дов. лицо').':'.
					'<td>'.$r['fio'].
						($r['phone'] ? '<br />'.$r['phone'] : '').
						($r['adres'] ? '<br />'.$r['adres'] : '')
			 : '').
						$person.
			'</table>';


		$send['spisok'] .=
			'<div class="unit">'.
				'<table class="g-tab">'.
					'<tr><td>'.$left.
						'<td class="r-td">'.
							($r['comm'] ? '<div class="comm" val="'.$r['comm'].'"></div>' : '').
							($r['zayav_wait'] ? '<div class="z-wait'._tooltip('Ожидающие заявки', -60).$r['zayav_wait'].'</div>' : '').
							($r['zayav_ready'] ? '<div class="z-ready'._tooltip('Выполненные заявки', -63).$r['zayav_ready'].'</div>' : '').
							($r['zayav_fail'] ? '<div class="z-fail'._tooltip('Отменённые заявки', -59).$r['zayav_fail'].'</div>' : '').
							(round($r['balans'], 2) ?
								'<div style="color:#'.($r['balans'] < 0 ? 'A00' : '090').'" class="balans'.
									_tooltip('Баланс', -15).
									round($r['balans'], 2).
								'</div>'
							: '').
				'</table>'.
			'</div>';
	}
	if($start + $limit < $send['all']) {
		$c = $send['all'] - $start - $limit;
		$c = $c > $limit ? $limit : $c;
		$send['spisok'] .= '<div class="_next" val="'.($page + 1).'"><span>Показать ещё '.$c.' клиент'._end($c, 'а', 'а', 'ов').'</span></div>';
	}
	return $send;
}//client_data()








function _clientCategory($i=0, $menu=0) {//Категории клиентов
	$arr = array(
		1 => $menu ? 'Частное лицо' : 'Ф.И.О.',
		2 => 'Организация'
	);
	return $i ? $arr[$i] : $arr;
}//_clientCategory()
/*
function _clientCategory($i=0, $menu=0) {//Категории клиентов
	$arr = array(
		1 => $menu ? 'Частное лицо' : 'Ф.И.О.',
		2 => 'Организация',
		3 => 'ИП',
		4 => 'ООО',
		5 => 'ОАО',
		6 => 'ЗАО'
	);
	return $i ? $arr[$i] : $arr;
}//_clientCategory()
*/
/*
function _clientTelefon($r, $post=0) {//правильное отображение телефона клиента
	//$post - показывать доверенное лицо
	$phone = $r['category_id'] == 1 ? $r['phone'] : $r['org_phone'];
	if(!$phone && $r['category_id'] > 1 && $r['phone'])
		$phone = $r['phone'].($r['fio'] && $post ? '<span class="post">('.$r['fio'].')</span>' : '');
	return $phone;
}//_clientTelefon()
*/
/*
function _clientLink($arr, $fio=0, $tel=0) {//Добавление имени и ссылки клиента в массив или возврат по id
	$clientArr = array(is_array($arr) ? 0 : $arr);
	if(is_array($arr)) {
		$ass = array();
		foreach($arr as $r) {
			$clientArr[$r['client_id']] = $r['client_id'];
			if($r['client_id'])
				$ass[$r['client_id']][] = $r['id'];
		}
		unset($clientArr[0]);
	}
	if(!empty($clientArr)) {
		$sql = "SELECT *
		        FROM `client`
				WHERE `ws_id`=".WS_ID."
				  AND `id` IN (".implode(',', $clientArr).")";
		$q = query($sql);
		if(!is_array($arr)) {
			if($r = mysql_fetch_assoc($q)) {
				$phone = _clientTelefon($r);
				return
					$fio ? _clientName($r)
						:
						'<a val="'.$r['id'].'" class="go-client-info' .
						($r['deleted'] ? ' deleted' : '') .
						($tel && $phone ? _tooltip($phone, -2, 'l') : '">') .
						_clientName($r) .
						'</a>';
			}
			return '';
		}
		while($r = mysql_fetch_assoc($q))
			foreach($ass[$r['id']] as $id) {
				$phone = _clientTelefon($r);
				$arr[$id]['client_link'] =
					'<a val="'.$r['id'].'" class="go-client-info'.
					($r['deleted'] ? ' deleted' : '').
					($tel && $phone ? _tooltip($phone, -2, 'l') : '">').
					_clientName($r).
					'</a>';
				$arr[$id]['client_fio'] = _clientName($r);
			}
	}
	return $arr;
}//_clientLink()
*/
function _clientVal($client_id, $i=0) {//получение данных из базы об одном клиенте
	$prefix = 'CLIENT_'.$client_id.'_';
	if(!defined($prefix.'LOADED')) {
		if(!$c = _clientQuery($client_id, 1))
			return 0;

		$org = $c['category_id'] != 1;

		// формирование списка доверенных лиц
		$sql = "SELECT * FROM `_client_person` WHERE `client_id`=".$client_id." ORDER BY `id`";
		$q = query($sql, GLOBAL_MYSQL_CONNECT);
		$c['person'] = array();
		while($r = mysql_fetch_assoc($q))
			$c['person'][] = $r;

		$person_id = empty($c['person']) ? 0 : $c['person'][0]['id'];// id первого доверенного лица (или частного лица)

		define($prefix.'LOADED', 1);
		define($prefix.'ORG', $org);
		define($prefix.'PERSON_ID', $person_id);
		define($prefix.'FIO', $person_id ? $c['person'][0]['fio'] : '');

		define($prefix.'PASP_SERIA', $person_id ? $c['person'][0]['pasp_seria'] : '');
		define($prefix.'PASP_NOMER', $person_id ? $c['person'][0]['pasp_nomer'] : '');
		define($prefix.'PASP_ADRES', $person_id ? $c['person'][0]['pasp_adres'] : '');
		define($prefix.'PASP_OVD', $person_id ? $c['person'][0]['pasp_ovd'] : '');
		define($prefix.'PASP_DATA', $person_id ? $c['person'][0]['pasp_data'] : '');

		define($prefix.'NAME', $org ? $c['org_name'] : constant($prefix.'FIO'));
		define($prefix.'PHONE', $org ? $c['org_phone'] : $c['person'][0]['phone']);
		define($prefix.'ADRES', $org ? $c['org_adres'] : $c['person'][0]['adres']);
		define($prefix.'LINK', '<a href="'.URL.'&p=client&d=info&id='.$client_id.'">'.constant($prefix.'NAME').'</a>');
	}

	$send = array(
		'org' => constant($prefix.'ORG'),
		'name' => constant($prefix.'NAME'),
		'person_id' => constant($prefix.'PERSON_ID'),
		'fio' => constant($prefix.'FIO'),

		'pasp_seria' => constant($prefix.'PASP_SERIA'),
		'pasp_nomer' => constant($prefix.'PASP_NOMER'),
		'pasp_adres' => constant($prefix.'PASP_ADRES'),
		'pasp_ovd' => constant($prefix.'PASP_OVD'),
		'pasp_data' => constant($prefix.'PASP_DATA'),

		'phone' => constant($prefix.'PHONE'),
		'adres' => constant($prefix.'ADRES'),
		'link' => constant($prefix.'LINK')
	);

	return $i ? $send[$i] : $send;
}//_clientVal()
function _clientValToList($arr) {//вставка данных клиентов в массив по client_id
	$ids = array();
	$arrIds = array();
	foreach($arr as $key => $r)
		if(!empty($r['client_id'])) {
			$ids[$r['client_id']] = 1;
			$arrIds[$r['client_id']][] = $key;
		}
	if(empty($ids))
		return $arr;

	$sql = "SELECT *
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `id` IN (".implode(',', array_keys($ids)).")";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	while($r = mysql_fetch_assoc($q)) {
		$c = _clientVal($r['id']);
		foreach($arrIds[$r['id']] as $id) {
			$arr[$id] += array(
				'client_name' => $c['name'],
				'client_phone' => $c['phone'],
				'client_link' => '<a val="'.$r['id'].'" class="go-client-info'.($r['deleted'] ? ' deleted' : '').'">'.$c['name'].'</a>'
			//	'client_link' => '<a val="'.$r['id'].'" class="go-client-info'.($r['deleted'] ? ' deleted' : '').'">'._clientName($r).'</a>',
			);
		}
	}
	return $arr;
}//_clientValToList()
function _findMatch($reg, $v, $empty=0) {//выделение при быстром поиске по конкретному регулярному выражению
	//$empty - возвращать пустое значение, если нет совпадения
	if(empty($reg))
		return $empty ? '': $v;
	$reg = utf8($reg);
	$v = utf8($v);
	$v = preg_match($reg, $v) ? preg_replace($reg, '<em>\\1</em>', $v, 1) : ($empty ? '': $v);
	return win1251($v);
}//_findMatch()
function _regFilter($v) {//проверка регулярного выражения на недопустимые символы
	$reg = '/(\[)/'; // скобка [
	if(preg_match($reg, $v))
		return '';
	return '/('.$v.')/iu';
}//_regFilter()
function _findRegular($find, $v, $empty=0) {//проверка и выделение при быстром поиске на русском и английском языках
	$engRus = _engRusChar($find);
	$reg = _regFilter($find);

	$regEngRus = empty($engRus) ? '' : _regFilter($engRus);

	$send = _findMatch($reg, $v, 1);
	if(!$send)
		$send = _findMatch($regEngRus, $v, 1);

	if(!$empty && !$send)
		return $v;

	return $send;
}//_findRegular()

function _clientQuery($client_id, $withDeleted=0) {//запрос данных об одном клиенте
	$sql = "SELECT *
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID.
			  ($withDeleted ? '' : " AND !`deleted`")."
			  AND `id`=".$client_id;
	return query_assoc($sql, GLOBAL_MYSQL_CONNECT);
}//_clientSql()
function _clientInfo($client_id) {//вывод информации о клиенте
	if(!$c = _clientQuery($client_id, 1))
		return _noauth('Клиента не существует');

	if($c['deleted'])
		if($c['join_id'])
			return _noauth('Клиент <b>'._clientVal($client_id, 'name').'</b><br /><br />'.
						   'был объединён<br /><br />'.
						   'с клиентом '._clientVal($c['join_id'], 'link').'.');
		else
			return _noauth('Клиент был удалён.');

	define('ORG', $c['category_id'] > 1);

	/*
		$zayavData = zayav_spisok(array(
			'client_id' => $client_id,
			'limit' => 10
		));

		$zayavCartridge = zayav_cartridge_spisok(array(
			'client_id' => $client_id,
			'limit' => 10
		));

		$schet = report_schet_spisok(array('client_id'=>$client_id));

		$commCount = query_value("SELECT COUNT(`id`)
								  FROM `vk_comment`
								  WHERE `status`
									AND !`parent_id`
									AND `table_name`='client'
									AND `table_id`=".$client_id);

		$moneyCount = query_value("SELECT COUNT(`id`)
								   FROM `money`
								   WHERE `ws_id`=".WS_ID."
									 AND `deleted`=0
									 AND `client_id`=".$client_id);
		$money = '<div class="_empty">Платежей нет.</div>';
		if($moneyCount) {
			$money = '<table class="_spisok _money">'.
				'<tr><th class="sum">Сумма'.
				'<th>Описание'.
				'<th class="data">Дата';
			$sql = "SELECT *
					FROM `money`
					WHERE `ws_id`=".WS_ID."
					  AND !`deleted`
					  AND `client_id`=".$client_id;
			$q = query($sql);
			$moneyArr = array();
			while($r = mysql_fetch_assoc($q))
				$moneyArr[$r['id']] = $r;
			$moneyArr = _zayavNomerLink($moneyArr);
			foreach($moneyArr as $r) {
				$about = '';
				if($r['zayav_id'])
					$about .= 'Заявка '.$r['zayav_link'].'. ';
				if($r['zp_id'])
					$about = 'Продажа запчасти '.$r['zp_id'].'. ';
				$about .= $r['prim'];
				$money .= '<tr><td class="sum"><b>'.round($r['sum'], 2).'</b>'.
					'<td>'.$about.
					'<td class="dtime" title="Внёс: '._viewer($r['viewer_id_add'], 'name').'">'.FullDataTime($r['dtime_add']);
			}
			$money .= '</table>';
		}

		$remind = _remind_spisok(array('client_id'=>$client_id));

		$history = history(array('client_id'=>$client_id,'limit'=>15));
	*/

	return
		'<script type="text/javascript">'.
			'var CLIENT={'.
				'id:'.$client_id.','.
				'category_id:'.$c['category_id'].','.

				'person_id:'._clientVal($client_id, 'person_id').','.
				'fio:"'.addslashes(_clientVal($client_id, 'fio')).'",'.
				'phone:"'.addslashes(_clientVal($client_id, 'phone')).'",'.
				'adres:"'.addslashes(_clientVal($client_id, 'adres')).'",'.
				'pasp_seria:"'.addslashes(_clientVal($client_id, 'pasp_seria')).'",'.
				'pasp_nomer:"'.addslashes(_clientVal($client_id, 'pasp_nomer')).'",'.
				'pasp_adres:"'.addslashes(_clientVal($client_id, 'pasp_adres')).'",'.
				'pasp_ovd:"'.addslashes(_clientVal($client_id, 'pasp_ovd')).'",'.
				'pasp_data:"'.addslashes(_clientVal($client_id, 'pasp_data')).'",'.

				'org_name:"'.addslashes($c['org_name']).'",'.
				'org_phone:"'.addslashes($c['org_phone']).'",'.
				'org_fax:"'.addslashes($c['org_fax']).'",'.
				'org_adres:"'.addslashes($c['org_adres']).'",'.
				'org_inn:"'.addslashes($c['org_inn']).'",'.
				'org_kpp:"'.addslashes($c['org_kpp']).'",'.
				'person:'._clientInfoPerson($client_id, 'json').
			'};'.
	//		'DEVICE_IDS=['._zayavBaseDeviceIds($client_id).'],'.
	//		'VENDOR_IDS=['._zayavBaseVendorIds($client_id).'],'.
	//		'MODEL_IDS=['._zayavBaseModelIds($client_id).'];'.
		'</script>'.

		'<div id="client-info">'.
			'<table class="tabLR">'.
				'<tr><td class="left">'.
						_clientInfoBalans($c).
						_clientInfoContent($c).
						_clientInfoPasp($client_id).
						'<div id="person-spisok">'._clientInfoPerson($client_id).'</div>'.
						'<div class="dtime">'.
							'Клиента вн'.(_viewer($c['viewer_id_add'], 'sex') == 1 ? 'есла' : 'ёс').' '.
							_viewer($c['viewer_id_add'], 'name').' '.
							FullData($c['dtime_add'], 1).
						'</div>'.

					'<td class="right">'.
						'<div class="rightLink">'.
							'<a id="zayav-add" val="client_'.$client_id.'"><b>Новая заявка</b></a>'.//'.(SERVIVE_CARTRIDGE ? ' class="cartridge"' : '').'todo
							'<a class="_remind-add">Новое напоминание</a>'.
							'<a id="client-edit">Редактировать</a>'.
						'</div>'.
			'</table>'.

			'<div id="dopLinks">'.
				'<a class="link sel" val="zayav">Заявки</a>'.
	/*			'<a class="link sel" val="zayav">Заявки'.($zayavData['all'] ? ' <b class="count">'.$zayavData['all'].'</b>' : '').'</a>'.
				'<a class="link" val="schet">Счета'.($schet['all'] ? ' <b class="count">'.$schet['all'].'</b>' : '').'</a>'.
				'<a class="link" val="money">Платежи'.($moneyCount ? ' <b class="count">'.$moneyCount.'</b>' : '').'</a>'.
				'<a class="link" val="remind">Напоминания'.($remind['all'] ? ' <b class="count">'.$remind['all'].'</b>' : '').'</a>'.
				'<a class="link" val="comm">Заметки'.($commCount ? ' <b class="count">'.$commCount.'</b>' : '').'</a>'.
				'<a class="link" val="hist">История'.($history['all'] ? ' <b class="count">'.$history['all'].'</b>' : '').'</a>'.
	*/
			'</div>'.

/*
		'<table class="tabLR">'.
		'<tr><td class="left">'.
		'<div id="zayav_spisok">'.
		($zayavData['all'] ? $zayavData['spisok'] : '').
		(!$zayavCartridge['all'] && !$zayavData['all'] ? $zayavData['spisok'] : '').
		($zayavCartridge['all'] ? $zayavCartridge['spisok'] : '').
		'</div>'.
		'<div id="schet_spisok">'.$schet['spisok'].'</div>'.
		'<div id="money_spisok">'.$money.'</div>'.
		'<div id="remind-spisok">'.$remind['spisok'].'</div>'.
		'<div id="comments">'._vkComment('client', $client_id).'</div>'.
		'<div id="histories">'.$history['spisok'].'</div>'.
		'<td class="right">'.
		'<div id="zayav_filter">'.
		'<div id="zayav_result">'.$zayavData['result'].'</div>'.
		'<div class="findHead">Статус заявки</div>'.
		_rightLink('status', _zayavStatusName()).
		_check('diff', 'Неоплаченные заявки').
		'<div class="findHead">Устройство</div><div id="dev"></div>'.
		'</div>'.
		'</table>'.
*/
		'</div>';
}//_clientInfo()
function _clientInfoBalans($r) {//отображение текущего баланса клиента
	return
		'<div style="color:#'.($r['balans'] < 0 ? 'A00' : '090').'" class="ci-balans'._tooltip('Баланс', -19).
			round($r['balans'], 2).
		'</div>';
}//_clientInfoBalans()
function _clientInfoContent($r) {//основная информация о клиенте
	return
		'<div id="ci-name">'._clientVal($r['id'], 'name').'</div>'.
		'<table id="ci-tab">'.
			(_clientVal($r['id'], 'phone') ? '<tr><td class="label">Телефон:<td>'._clientVal($r['id'], 'phone') : '').
			(_clientVal($r['id'], 'adres') ? '<tr><td class="label">Адрес:<td>'._clientVal($r['id'], 'adres') : '').
			(ORG && $r['org_fax'] ? '<tr><td class="label">Факс:<td>'.$r['org_fax'] : '').
			(ORG && $r['org_adres'] ? '<tr><td class="label top">Адрес:<td>'.$r['org_adres'] : '').
			(ORG && $r['org_inn'] ? '<tr><td class="label">ИНН:<td>'.$r['org_inn'] : '').
			(ORG && $r['org_kpp'] ? '<tr><td class="label">КПП:<td>'.$r['org_kpp'] : '').
		'</table>';
}//_clientInfoContent()
function _clientInfoPerson($client_id, $type='html') {// формирование списка доверенных лиц
	$sql = "SELECT * FROM `_client_person` WHERE `client_id`=".$client_id." ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$person = array();
	while($r = mysql_fetch_assoc($q))
		$person[] = $r;

	if(!_clientVal($client_id, 'org'))
		array_shift($person);

	$html = '<table class="_spisok">';
	$json = array();
	$array = array();
	foreach($person as $n => $r) {
		$html .=
			'<tr><td class="n">'.($n + 1).
				'<td>'.$r['fio'].
					  ($r['phone'] ? ', '.$r['phone'] : '').
					  ($r['adres'] ? '<br /><span class="adres"><tt>Адрес:</tt> '.$r['adres'].'</span>' : '').
				'<td>'.($r['post'] ? '<u>'.$r['post'].'</u>' : '').
				'<td class="td-person-ed">'.
					'<div val="'.$r['id'].'" class="person-edit img_edit'._tooltip('Изменить', -33).'</div>'.
					'<div val="'.$r['id'].'" class="person-del img_del'._tooltip('Удалить', -29).'</div>';
		$json[] =
			$r['id'].':{'.
				'fio:"'.addslashes($r['fio']).'",'.
				'phone:"'.addslashes($r['phone']).'",'.
				'adres:"'.addslashes($r['adres']).'",'.
				'post:"'.addslashes($r['post']).'",'.
				'pasp_seria:"'.addslashes($r['pasp_seria']).'",'.
				'pasp_nomer:"'.addslashes($r['pasp_nomer']).'",'.
				'pasp_adres:"'.addslashes($r['pasp_adres']).'",'.
				'pasp_ovd:"'.addslashes($r['pasp_ovd']).'",'.
				'pasp_data:"'.addslashes($r['pasp_data']).'"'.
			'}';
		$array[$r['id']] = array(
			'fio' => utf8($r['fio']),
			'phone' => utf8($r['phone']),
			'adres' => utf8($r['adres']),
			'post' => utf8($r['post']),
			'pasp_seria' => utf8($r['pasp_seria']),
			'pasp_nomer' => utf8($r['pasp_nomer']),
			'pasp_adres' => utf8($r['pasp_adres']),
			'pasp_ovd' => utf8($r['pasp_ovd']),
			'pasp_data' => utf8($r['pasp_data'])
		);
	}
	$html .= '</table>';

	switch($type) {
		default:
		case 'html':
			return
				'<div id="ci-person">'.
					'<h1>Доверенные лица:<a id="person-add" class="'._tooltip('Добавить доверенное лицо', -70).'добавить</a></h1>'.
					$html.
				'</div>';
		case 'json': return '{'.implode(',', $json).'}';
		case 'array': return $array;
	}
}//_clientInfoPerson()
function _clientInfoPasp($client_id) {//паспортные данные
	$r = _clientVal($client_id);

	if($r['org'] || !$r['pasp_seria'] && !$r['pasp_nomer'] && !$r['pasp_adres'] && !$r['pasp_ovd'] && !$r['pasp_data'])
		return '';

	return
		'<div id="pasp-head">Паспортные данные:</div>'.
		'<table id="pasp-tab">'.
			'<tr><td class="label">Серия и номер:<td>'.$r['pasp_seria'].' '.$r['pasp_nomer'].
			'<tr><td class="label">Прописка:<td>'.$r['pasp_adres'].
			'<tr><td class="label">Выдан:<td>'.$r['pasp_ovd'].', '.$r['pasp_data'].
		'</table>';
}//_clientInfoPasp()

function clientBalansUpdate($client_id, $ws_id=WS_ID) {//обновление баланса клиента
	if(!$client_id)
		return 0;
	$prihod = query_value("SELECT IFNULL(SUM(`sum`),0)
						   FROM `money`
						   WHERE `ws_id`=".$ws_id."
							 AND !`deleted`
							 AND `client_id`=".$client_id."
							 AND !`zp_id`
							 AND `sum`>0");
	$acc = query_value("SELECT IFNULL(SUM(`sum`),0)
						FROM `accrual`
						WHERE `ws_id`=".$ws_id."
						  AND !`deleted`
						  AND `client_id`=".$client_id);
	$balans = $prihod - $acc;
	query("UPDATE `client` SET `balans`=".$balans." WHERE `id`=".$client_id);
	return $balans;
}//clientBalansUpdate()



