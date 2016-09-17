<?php
function _client_script() {//скрипты для клиентов
	return
		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/client/client'.MIN.'.css?'.VERSION.'" />'.
		'<script src="'.API_HTML.'/modul/client/client'.MIN.'.js?'.VERSION.'"></script>';
}
function _clientCase($v=array()) {//вывод информации с клиентами для приложения
	switch(@$_GET['d']) {
		case 'info': return _clientInfo();
		case 'poa': return _clientPoa();
		case 'from': return _client_from();
		default: return _client(_hashFilter('client'));
	}
}

function _clientDolgSum() {//показ суммы долга всех клиентов в верхнем правом углу
	if(APP_ID != 3978722)// только для Евроокон
		return '';

	$sql = "SELECT SUM(`balans`)
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `balans`<0";
	if(!$dolg = abs(query_value($sql)))
		return '';

	return '<div id="client-dolg-sum"><b>'._sumSpace($dolg).'</b> руб.</div>';
}

function _client($v) {
	return client_list($v);
}
function client_list($v) {// страница со списком клиентов
	$data = _client_spisok($v);
	$v = $data['filter'];

	//Если внесены клиенты только одной категории, то условие по катериям не будет выводиться
	$sql = "SELECT COUNT(DISTINCT `category_id`)
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`";
	$categoryShow = query_value($sql) > 1;
	$category = array(
		0 => 'Любая категория',
		1 => 'Частные лица',
		2 => 'Организации'
	);
	return
		'<div id="client">'.
			'<table id="find-tab"><tr>'.
				'<td><div id="find"></div>'.
				'<td><button class="vk" onclick="clientEdit()">Новый клиент</button>'.
			'</table>'.
			'<div class="result">'.$data['result'].'</div>'.
			'<table class="tabLR">'.
				'<tr><td class="left">'.$data['spisok'].
					'<td class="right">'.
						'<div class="filter'.($v['find'] ? ' dn' : '').'">'.
		   ($categoryShow ? '<div class="f-label">Категория</div>'.
							_radio('category_id', $category, $v['category_id'], 1)
			: '').
	                        _check('dolg', 'Должники', $v['dolg']).
							_check('opl', 'Внесена предоплата', $v['opl']).
							_check('worker', 'Привязка к сотруднику', $v['worker']).
							'<div class="f-label mt20">Напоминания</div>'.
							'<input type="hidden" id="remind" value="'.$v['remind'].'" />'.
						'</div>'.
			'</table>'.
		'</div>';
}
function clientFilter($v) {
	$default = array(
		'limit' => 20,
		'page' => 1,
		'find' => '',
		'dolg' => 0,
		'worker' => 0,
		'opl' => 0,
		'category_id' => 0,
		'remind' => 0
	);
	$filter = array(
		'limit' => _num(@$v['limit']) ? $v['limit'] : 20,
		'page' => _num(@$v['page']) ? $v['page'] : 1,
		'find' => strtolower(trim(@$v['find'])),
		'dolg' => _bool(@$v['dolg']),
		'worker' => _bool(@$v['worker']),
		'opl' => _bool(@$v['opl']),
		'category_id' => _num(@$v['category_id']),
		'remind' => _num(@$v['remind']),
		'clear' => ''
	);
	foreach($default as $k => $r)
		if($r != $filter[$k]) {
			$filter['clear'] = '<button class="vk small red">Очистить фильтр</button>';
			break;
		}
	return $filter;
}
function _client_spisok($v=array()) {// список клиентов
	$filter = clientFilter($v);
	$filter = _filterJs('CLIENT', $filter);

	$cond = "`app_id`=".APP_ID."
		 AND !`deleted`
		 AND !`client_id_person`";
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
		if($filter['category_id'])
			$cond .= " AND `category_id`=".$filter['category_id'];
		if($filter['worker'])
			$cond .= " AND `worker_id`";
		if($filter['remind']) {
			$not = $filter['remind'] == 2 ? ' NOT' : '';
			$sql = "SELECT `client_id`
					FROM `_remind`
					WHERE `app_id`=".APP_ID."
					 AND `client_id`
					 AND `status`=1";
			$cond .= " AND `id`".$not." IN (".query_ids($sql).")";
		}
		if($filter['dolg']) {
			$cond .= " AND `balans`<0";
			$sql = "SELECT SUM(`balans`)
					FROM `_client`
					WHERE ".$cond."
					  AND `balans`<0";
			$dolg = abs(query_value($sql));
		}
		if($filter['opl']) {
			$cond .= " AND `balans`>0";
			$sql = "SELECT SUM(`balans`)
					FROM `_client`
					WHERE ".$cond."
					  AND `balans`>0";
			$plus = abs(query_value($sql));
		}
	}

	$sql = "SELECT COUNT(`id`) AS `all` FROM `_client` WHERE ".$cond;
	if(!$all = query_value($sql))
		return array(
			'all' => 0,
			'result' => 'Клиентов не найдено.'.$filter['clear'],
			'spisok' => $filter['js'].'<div class="_empty">Клиентов не найдено.</div>',
			'filter' => $filter
		);

	$newMonth = '';
	$newToday = '';
	if(empty($filter['clear'])) {
		//новые клиенты за текущий месяц
		$sql = "SELECT COUNT(`id`)
				FROM `_client`
				WHERE ".$cond."
				  AND `dtime_add` LIKE '".strftime('%Y-%m')."-%'";
		$c = query_value($sql);
		$newMonth = $c ? '<b class="'._tooltip('Новые за '._monthDef(strftime('%m')), -5, 'l').'+'.$c.'</b>' : '';

		if($newMonth) {
			//новые клиенты за сегодня
			$sql = "SELECT COUNT(`id`)
					FROM `_client`
					WHERE ".$cond."
					  AND `dtime_add` LIKE '".TODAY." %'";
			$c = query_value($sql);
			$newToday = $c ? '<span class="'._tooltip('Новые за сегодня', -10, 'l').'+'.$c.'</span>' : '';
		}
	}

	$send['all'] = $all;
	$send['result'] = 'Найден'._end($all, ' ', 'о ').$all.' клиент'._end($all, '', 'а', 'ов').
					  ($dolg ? '<em>(Общая сумма долга = <b id="dolg-sum">'._sumSpace($dolg).'</b> руб.)</em>' : '').
					  ($plus ? '<em>(Сумма = '.$plus.' руб.)</em>' : '').
					  ($newMonth ? '<em>'.$newToday.$newMonth.'</em>' : '').
					  $filter['clear'];
	$send['filter'] = $filter;
	$send['spisok'] = $filter['js'];

	$spisok = array();
	$sql = "SELECT *,
				   '' `zayav`,
				   '' `comm`
			FROM `_client`
			WHERE ".$cond."
			ORDER BY `dtime_add` DESC
			LIMIT "._startLimit($filter);
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		if(FIND) {
			$r['fio'] = _findRegular($filter['find'], $r['fio']);
			$r['phone'] = _findRegular($filter['find'], $r['phone']);
			$r['adres'] = _findRegular($filter['find'], $r['adres'], 1);

			$r['org_name'] = _findRegular($filter['find'], $r['org_name']);
			$r['org_phone'] = _findRegular($filter['find'], $r['org_phone']);
			$r['org_fax'] = _findRegular($filter['find'], $r['org_fax'], 1);
			$r['org_adres'] = _findRegular($filter['find'], $r['org_adres'], 1);
			$r['org_inn'] = _findRegular($filter['find'], $r['org_inn'], 1);
			$r['org_kpp'] = _findRegular($filter['find'], $r['org_kpp'], 1);
		}
		$spisok[$r['id']] = $r;
	}

	$spisok = _zayavCountToClient($spisok);

	foreach($spisok as $r) {
		$org = $r['category_id'] != 1;
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
			'</table>';


		$send['spisok'] .=
			'<div class="unit" id="cu'.$r['id'].'">'.
				'<table class="g-tab">'.
					'<tr><td>'.$left.
						'<td class="r-td">'.
							($r['comm'] ? '<div class="comm" val="'.$r['comm'].'"></div>' : '').
							$r['zayav'].
							(round($r['balans'], 2) ?
								'<div style="color:#'.($r['balans'] < 0 ? 'A00' : '090').'" class="balans'.
									_tooltip('Баланс', -15).
									round($r['balans'], 2).
								'</div>'
							: '').
				'</table>'.
			'</div>';
	}

	$send['spisok'] .= _next($filter + array(
			'type' => 1,
			'all' => $all
		));

	return $send;
}








function _clientCategory($i=0, $menu=0) {//Категории клиентов
	$arr = array(
		1 => $menu ? 'Частное лицо' : 'Ф.И.О.',
		2 => 'Организация'
	);
	return $i ? $arr[$i] : $arr;
}
function _clientVal($client_id, $i=0) {//получение данных из базы об одном клиенте
	$prefix = 'CLIENT_'.$client_id.'_';
	if(!defined($prefix.'LOADED')) {
		if(!$c = _clientQuery($client_id, 1))
			return 0;

		$org = $c['category_id'] != 1;

		define($prefix.'LOADED', 1);
		define($prefix.'ID', $c['id']);
		define($prefix.'ORG', $org);
		define($prefix.'FIO', $c['fio']);
		define($prefix.'BALANS', _cena($c['balans'], 1));

		define($prefix.'PASP_SERIA', $c['pasp_seria']);
		define($prefix.'PASP_NOMER', $c['pasp_nomer']);
		define($prefix.'PASP_ADRES', $c['pasp_adres']);
		define($prefix.'PASP_OVD', $c['pasp_ovd']);
		define($prefix.'PASP_DATA', $c['pasp_data']);

		define($prefix.'NAME', $org ? $c['org_name'] : constant($prefix.'FIO'));
		define($prefix.'PHONE', $org ? $c['org_phone'] : $c['phone']);
		define($prefix.'ADRES', $org ? $c['org_adres'] : $c['adres']);
		define($prefix.'WORKER',
			$c['worker_id'] ?
				'<a href="'.URL.'&p=report&d=salary&id='.$c['worker_id'].'" class="'._tooltip('Перейти на страницу з/п сотрудника', -70).
					_viewer($c['worker_id'], 'viewer_name').
				'</a>'
			: '');
		define($prefix.'LINK', '<a href="'.URL.'&p=client&d=info&id='.$client_id.'">'.constant($prefix.'NAME').'</a>');
		define($prefix.'GO',
			'<a val="'.$c['id'].'" class="client-info-go'.($c['deleted'] ? ' deleted' : '').
				(constant($prefix.'PHONE') ? _tooltip(constant($prefix.'PHONE'), -1, 'l') : '">').
				constant($prefix.'NAME').
			'</a>'
		);

		define($prefix.'INN', $org ? $c['org_inn'] : '');
		define($prefix.'KPP', $org ? $c['org_kpp'] : '');
	}

	$send = array(
		'id' => constant($prefix.'ID'),
		'org' => constant($prefix.'ORG'),
		'name' => constant($prefix.'NAME'),
		'fio' => constant($prefix.'FIO'),
		'balans' => constant($prefix.'BALANS'),

		'pasp_seria' => constant($prefix.'PASP_SERIA'),
		'pasp_nomer' => constant($prefix.'PASP_NOMER'),
		'pasp_adres' => constant($prefix.'PASP_ADRES'),
		'pasp_ovd' => constant($prefix.'PASP_OVD'),
		'pasp_data' => constant($prefix.'PASP_DATA'),

		'phone' => constant($prefix.'PHONE'),
		'adres' => constant($prefix.'ADRES'),
		'worker' => constant($prefix.'WORKER'),
		'link' => constant($prefix.'LINK'),
		'go' => constant($prefix.'GO'),

		'inn' => constant($prefix.'INN'),
		'kpp' => constant($prefix.'KPP')
	);

	return $i ? $send[$i] : $send;
}
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

	$ids = implode(',', array_keys($ids));

	$sql = "SELECT *
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND `id` IN (".$ids.")";
	$client = query_arr($sql);

	foreach($client as $r) {
		$org = $r['category_id'] != 1;
		foreach($arrIds[$r['id']] as $id) {
			$name = $org ? $r['org_name'] : $r['fio'];
			$phone = $org ? $r['org_phone'] : $r['phone'];
			$arr[$id] += array(
				'client_name' => $name,
				'client_phone' => $phone,
				'client_balans' => round($r['balans'], 2),
				'client_link' => '<a href="'.URL.'&p=client&d=info&id='.$r['id'].'"'.($r['deleted'] ? ' class="deleted"' : '').'>'.$name.'</a>',
				'client_go' =>
					'<a val="'.$r['id'].'" class="client-info-go'.($r['deleted'] ? ' deleted' : '').
						($phone ? _tooltip($phone, -1, 'l') : '">').
						$name.
					'</a>'
			);
		}
	}
	return $arr;
}
function _findMatch($reg, $v, $empty=0) {//выделение при быстром поиске по конкретному регулярному выражению
	//$empty - возвращать пустое значение, если нет совпадения
	if(empty($reg))
		return $empty ? '': $v;
	$reg = utf8($reg);
	$v = utf8($v);
	$v = preg_match($reg, $v) ? preg_replace($reg, '<em>\\1</em>', $v, 1) : ($empty ? '': $v);
	return win1251($v);
}
function _regFilter($v) {//проверка регулярного выражения на недопустимые символы
	$reg = '/(\[)/'; // скобка [
	if(preg_match($reg, $v))
		return '';
	return '/('.$v.')/iu';
}
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
}
function _clientDelAccess($client_id) {//разрешение на удаление клиента
	//Наличие заявок
	$sql = "SELECT COUNT(*) FROM `_zayav` WHERE !`deleted` AND `client_id`=".$client_id;
	if(query_value($sql))
		return false;

	//Наличие начислений
	$sql = "SELECT COUNT(*) FROM `_money_accrual` WHERE !`deleted` AND `client_id`=".$client_id;
	if(query_value($sql))
		return false;

	//Наличие счетов на оплату
	$sql = "SELECT COUNT(*) FROM `_schet` WHERE !`deleted` AND `client_id`=".$client_id;
	if(query_value($sql))
		return false;

	//Наличие платежей
	$sql = "SELECT COUNT(*) FROM `_money_income` WHERE !`deleted` AND `client_id`=".$client_id;
	if(query_value($sql))
		return false;

	//Наличие возвратов
	$sql = "SELECT COUNT(*) FROM `_money_refund` WHERE !`deleted` AND `client_id`=".$client_id;
	if(query_value($sql))
		return false;

	return true;
}

function _clientQuery($client_id, $withDeleted=0) {//запрос данных об одном клиенте
	$sql = "SELECT *
			FROM `_client`
			WHERE `app_id`=".APP_ID.
			  ($withDeleted ? '' : " AND !`deleted`")."
			  AND `id`=".$client_id;
	return query_assoc($sql);
}
function _clientDopLink($name, $arr) {//меню с дополнительными списками (заявки, начисления, платежи, история...)
	return
		$arr['all'] ?
			'<a class="link">'.$name.' <b class="count">'.$arr['all'].'</b></a>'
			: '';
}
function _clientDopContent($name, $arr) {//содержание дополнительных списоков (начисления, платежи, история...)
	return
		$arr['all'] ?
			'<div class="ci-cont" id="'.$name.'-spisok">'.$arr['spisok'].'</div>'
			: '';
}
function _clientDopRight($name, $arr, $filterContent) {//правая сторона (условия поиска) для дополнительных списков (заявки, начисления, платежи, история...)
	return
		$arr['all'] ?
			'<div class="ci-right" id="'.$name.'-right">'.$filterContent.'</div>'
			: '';
}
function _clientZayavTypeId($client_id) {//получение первого id вида заявки клиента
	$sql = "SELECT DISTINCT `service_id`
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			   AND !`deleted`
			  AND `client_id`=".$client_id."
			ORDER BY `service_id`
			LIMIT 1";
	return query_value($sql);
}
function _clientInfoZayavCount($client_id) {//общее количество заявок всех видов у клиента
	$sql = "SELECT COUNT(*)
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `client_id`=".$client_id;
	$send['all'] = query_value($sql);
	return $send;
}
function _clientInfoZayavRight($client_id) {
	$sql = "SELECT DISTINCT `service_id`,1
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `client_id`=".$client_id."
			ORDER BY `service_id`";
	$arr = query_ass($sql);

	if(count($arr) < 2)
		return '';

	return
	'<div class="f-label">Категория заявок</div>'.
	'<input type="hidden" id="zayav-type-id" value="'.key($arr).'" />';
}
function _clientInfo() {//вывод информации о клиенте
	if(!$client_id = _num(@$_GET['id']))
		return _err('Страницы не существует');

	if(!$c = _clientQuery($client_id, 1))
		return _err('Клиента не существует');

	if($c['deleted'])
		if($c['join_id'])
			return _noauth('Клиент <b>'._clientVal($client_id, 'name').'</b><br /><br />'.
						   'был объединён<br /><br />'.
						   'с клиентом '._clientVal($c['join_id'], 'link').'.');
		else
			return _noauth('Клиент был удалён.');

	define('ORG', $c['category_id'] > 1);

	if(!$type_id = _clientZayavTypeId($client_id))
		$type_id = _service('current');

	$zayav = _zayav_spisok(array(
		'type_id' => $type_id,
		'client_id' => $client_id,
		'limit' => 10
	));

	$accrual = _accrual_spisok(array('client_id'=>$client_id));
	$income = income_spisok(array('client_id'=>$client_id));
	$remind = _remind_spisok(array('client_id'=>$client_id));
	$hist = _history(array('client_id'=>$client_id,'limit'=>20));

	$sql = "SELECT `poa_attach_id`
			FROM `_client`
			WHERE !`deleted`
			  AND `client_id_person`=".$client_id."
			  AND `poa_attach_id`";
	$attach_ids = query_ids($sql);

	return
		'<script>'.
			'var CI={'.
				'id:'.$client_id.','.
				'category_id:'.$c['category_id'].','.
				'name:"'._clientVal($client_id, 'name').'",'.
				'worker_id:'.$c['worker_id'].','.
				'workers:'._clientInfoWorker($client_id).','.

//				'person_id:'._clientVal($client_id, 'person_id').','.
				'fio:"'.addslashes(_clientVal($client_id, 'fio')).'",'.
				'phone:"'.addslashes(_clientVal($client_id, 'phone')).'",'.
				'adres:"'._br(addslashes(_clientVal($client_id, 'adres'))).'",'.
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

				'from_id:'.$c['from_id'].','.

				'person:'._clientInfoPerson($client_id, 'json').','.
				'service_client:'._service('js_client', $client_id).//виды заявок, которые вносились для клиента (для фильтра заявок)
			'};'.
		'</script>'.
		_attachJs(array('id'=>$attach_ids)).
		'<div id="client-info">'.
			'<table class="tabLR">'.
				'<tr><td class="left">'.
						_clientInfoBalans($c).
						_clientInfoContent($c).
						_clientInfoPasp($client_id).
						'<div id="person-spisok">'._clientInfoPerson($client_id).'</div>'.
						'<div class="added">'.
							'Клиента '._viewerAdded($c['viewer_id_add']).' '.FullData($c['dtime_add'], 1).
		   ($c['from_id'] ? '<br />Источник: <u>'._clientFrom($c['from_id']).'</u>.' : '').
						'</div>'.

					'<td class="right">'.
						'<div class="rightLink">'.
							'<a onclick="_zayavAddMenu()"><b>Новая заявка</b></a>'.
							'<a class="_remind-add">Новое напоминание</a>'.
							'<a id="client-schet-add">Счёт на оплату</a>'.
							'<a id="client-edit">Редактировать</a>'.
							(_clientDelAccess($client_id) ? '<a id="client-del">Удалить клиента</a>' : '').
						'</div>'.
			'</table>'.

			'<div id="dopLinks">'.
				_clientDopLink('Заявки', _clientInfoZayavCount($client_id)).
				_clientDopLink('Начисления', $accrual).
				_clientDopLink('Платежи', $income).
				_clientDopLink('Напоминания', $remind).
				_clientDopLink('История', $hist).
			'</div>'.

			'<table class="tabLR">'.
				'<tr><td class="left">'.
						_clientDopContent('zayav', $zayav).
						_clientDopContent('accrual', $accrual).
						_clientDopContent('income', $income).
						_clientDopContent('_remind', $remind).
						_clientDopContent('history', $hist).
					'<td class="right">'.
						_clientDopRight('zayav', $zayav, _clientInfoZayavRight($client_id)).
						_clientDopRight('accrual', $accrual, '').
						_clientDopRight('income', $income, '').
						_clientDopRight('remind', $remind, '').
						_clientDopRight('history', $hist, _history_right(array('client_id'=>$client_id))).
				'</div>'.
			'</table>'.

		'</div>';
}
function _clientInfoBalans($r) {//отображение текущего баланса клиента
	return
		'<a style="color:#'.($r['balans'] < 0 ? 'A00' : '090').'"'.
		  ' val="2:'.$r['id'].'"'.
		  ' class="ci-balans _balans-show'._tooltip('Баланс', -19).
			_sumSpace(_cena($r['balans'], 1)).
		'</a>';
}
function _clientInfoContent($r) {//основная информация о клиенте
	return
		'<div id="ci-cat">'._clientCategory($r['category_id'], 1).'</div>'.
		'<div id="ci-name">'._clientVal($r['id'], 'name').'</div>'.
		'<table id="ci-tab">'.
			(_clientVal($r['id'], 'phone') ? '<tr><td class="label">Телефон:<td>'._clientVal($r['id'], 'phone') : '').
			(_clientVal($r['id'], 'adres') ? '<tr><td class="label top">Адрес:<td>'._br(_clientVal($r['id'], 'adres')) : '').
			(_clientVal($r['id'], 'worker') ? '<tr><td class="label">Связь с сотрудником:<td>'._clientVal($r['id'], 'worker') : '').
			(ORG && $r['org_fax'] ? '<tr><td class="label">Факс:<td>'.$r['org_fax'] : '').
			(ORG && $r['org_inn'] ? '<tr><td class="label">ИНН:<td>'.$r['org_inn'] : '').
			(ORG && $r['org_kpp'] ? '<tr><td class="label">КПП:<td>'.$r['org_kpp'] : '').
		'</table>';
}
function _clientInfoPerson($client_id, $type='html') {// формирование списка доверенных лиц
	$sql = "SELECT *
			FROM `_client`
			WHERE `id`=".$client_id."
			  OR `client_id_person`=".$client_id."
			ORDER BY `id`";
	$q = query($sql);
	$person = array();
	while($r = mysql_fetch_assoc($q)) {
		if(!$r['fio'])
			continue;
		$person[] = $r;
	}

	if(!_clientVal($client_id, 'org'))
		array_shift($person);

	$person = _attachValToList($person, 'poa_attach_id');

	$html = '<table class="_spisok">';
	$json = array();
	$array = array();
	foreach($person as $n => $r) {
		$poaLost = _dateLost($r['poa_date_end']) ? ' lost' : '';
		$html .=
			'<tr><td class="n">'.($n + 1).
				'<td>'.$r['fio'].
					  ($r['phone'] ? ', '.$r['phone'] : '').
					  ($r['adres'] ? '<br /><span class="adres"><tt>Адрес:</tt> '.$r['adres'].'</span>' : '').
					  ($r['poa_nomer'] ?
						  '<div class="poa'.$poaLost.'">'.
							  'Доверенность № <b>'.$r['poa_nomer'].'</b>.'.
							  ($r['poa_attach_id'] ? '<br />'.$r['attach_link'] : '').
							  '<br />Действительна до <u>'.FullData($r['poa_date_end']).'</u>.'.
							  ($poaLost ? '<br /><b>Просрочена.</b>' : '').
						  '</div>'
					  : '').
				'<td>'.($r['post'] ? '<u>'.$r['post'].'</u>' : '').
				'<td class="td-person-ed">'.
					'<div val="'.$r['id'].'" class="person-poa img_doc'._tooltip('Доверенность', -48).'</div>'.
					'<div val="'.$r['id'].'" class="person-edit img_edit'._tooltip('Изменить', -33).'</div>'.
					'<div val="'.$r['id'].'" class="person-del img_del'._tooltip('Удалить', -29).'</div>';
		$json[] =
			$r['id'].':{'.
				'id:'.$r['id'].','.
				'fio:"'.addslashes($r['fio']).'",'.
				'phone:"'.addslashes($r['phone']).'",'.
				'adres:"'.addslashes($r['adres']).'",'.
				'post:"'.addslashes($r['post']).'",'.
				'pasp_seria:"'.addslashes($r['pasp_seria']).'",'.
				'pasp_nomer:"'.addslashes($r['pasp_nomer']).'",'.
				'pasp_adres:"'.addslashes($r['pasp_adres']).'",'.
				'pasp_ovd:"'.addslashes($r['pasp_ovd']).'",'.
				'pasp_data:"'.addslashes($r['pasp_data']).'",'.
				'poa_nomer:"'.addslashes($r['poa_nomer']).'",'.
				'poa_date_begin:"'.addslashes($r['poa_date_begin']).'",'.
				'poa_date_end:"'.addslashes($r['poa_date_end']).'",'.
				'poa_attach_id:'.$r['poa_attach_id'].
			'}';
		$array[$r['id']] = array(
			'id' => $r['id'],
			'fio' => utf8($r['fio']),
			'phone' => utf8($r['phone']),
			'adres' => utf8($r['adres']),
			'post' => utf8($r['post']),
			'pasp_seria' => utf8($r['pasp_seria']),
			'pasp_nomer' => utf8($r['pasp_nomer']),
			'pasp_adres' => utf8($r['pasp_adres']),
			'pasp_ovd' => utf8($r['pasp_ovd']),
			'pasp_data' => utf8($r['pasp_data']),
			'poa_nomer' => utf8($r['poa_nomer']),
			'poa_date_begin' => $r['poa_date_begin'],
			'poa_date_end' => $r['poa_date_end'],
			'poa_attach_id' => $r['poa_attach_id']
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
}
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
}
function _clientInfoWorker($client_id) {//список сотрудников для связки с клиентами
	//id сотрудников, которые уже привязаны к клиентам
	$sql = "SELECT `worker_id`
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND `worker_id`
			  AND !`deleted`
			  AND `id`!=".$client_id;
	$ids = query_ids($sql);

	$sql = "SELECT `viewer_id`
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `worker`
			  AND `hidden`";
	$hidden = query_ids($sql);

	$sql = "SELECT
				`viewer_id`,
				CONCAT(`first_name`,' ',`last_name`)
	        FROM `_vkuser`
	        WHERE `app_id`=".APP_ID."
	          AND `worker`
	          AND `viewer_id` NOT IN (".$ids.",".$hidden.")";
	return query_selJson($sql);
}

function _clientBalansUpdate($client_id) {//обновление баланса клиента
	if(empty($client_id))
		return 0;

	//начисления
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_accrual`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `client_id`=".$client_id;
	$accrual = query_value($sql);

	//платежи без учёта проданных товаров клиенту
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_income`
			WHERE `app_id`=".APP_ID."
			  AND !`tovar_id`
			  AND `confirm` NOT IN (1,3)
			  AND !`deleted`
			  AND `client_id`=".$client_id;
	$income = query_value($sql);

	//Возвраты
	$sql = "SELECT IFNULL(SUM(`sum`),0)
			FROM `_money_refund`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `client_id`=".$client_id;
	$refund = query_value($sql);

	//Номера газет
	$sql = "SELECT `id`
			FROM `_zayav`
			WHERE `client_id`=".$client_id."
			  AND !`deleted`";
	if($ids = query_ids($sql)) {
		$sql = "SELECT IFNULL(SUM(`cena`),0)
				FROM `_zayav_gazeta_nomer`
				WHERE `app_id`=".APP_ID."
				  AND `zayav_id` IN (".$ids.")";
		$gn = query_value($sql);
	}

	$balans = $income - $accrual - $refund - $gn;

	$sql = "UPDATE `_client`
			SET `balans`=".$balans."
			WHERE `id`=".$client_id;
	query($sql);

	return $balans;
}


function _clientPoaFilter($v) {
	return array(
		'limit' => _num(@$v['limit']) ? $v['limit'] : 30,
		'page' => _num(@$v['page']) ? $v['page'] : 1,
	);
}
function _clientPoa() {
	$data = _clientPoaSpisok();
	return
	'<div id="client-poa">'.
		'<table class="tabLR">'.
			'<tr><td class="left">'.
					'<div class="headName">Доверенности от организаций</div>'.
					$data['spisok'].
				'<td class="right">'.
		'</table>'.
	'</div>';
}
function _clientPoaSpisok($v=array()) {
	$filter = clientFilter($v);
	$filter = _filterJs('CLIENT_POA', $filter);

	$cond = "`app_id`=".APP_ID."
		 AND `poa_nomer`";

	$sql = "SELECT COUNT(`id`) AS `all` FROM `_client` WHERE ".$cond;
	if(!$all = query_value($sql))
		return array(
			'all' => 0,
			'result' => 'Доверенностей нет.',
			'spisok' => $filter['js'].'<div class="_empty">Доверенностей нет.</div>',
			'filter' => $filter
		);

	$send['all'] = $all;
	$send['result'] = 'Найден'._end($all, ' ', 'о ').$all.' доверенност'._end($all, 'ь', 'и', 'ей');
	$send['filter'] = $filter;
	$send['spisok'] = $filter['js'];

	$sql = "SELECT
				*,
				IF(`client_id_person`,`client_id_person`,`id`) `client_id`
			FROM `_client`
			WHERE ".$cond."
			ORDER BY `id` DESC
			LIMIT "._startLimit($filter);
	$spisok = query_arr($sql);

	$spisok = _clientValToList($spisok);
	$spisok = _attachValToList($spisok, 'poa_attach_id');

	foreach($spisok as $r) {
		$org = $r['category_id'] != 1;
		$send['spisok'] .=
			'<div class="client-poa-unit'.(_dateLost($r['poa_date_end']) ? ' lost' : '').'">'.
				'<b class="name">Доверенность № '.$r['poa_nomer'].'</b>'.
				'<table class="tab">'.
					'<tr><td class="label">Организация:<td>'.$r['client_link'].
					'<tr><td class="label">Доверенное лицо:<td class="fio">'.$r['fio'].
					'<tr><td class="label">Дата выдачи:<td>'.FullData($r['poa_date_begin'], 1).
					'<tr><td class="label">Дата окончания:<td>'.FullData($r['poa_date_end'], 1).
					($r['poa_attach_id'] ? '<tr><td class="label">Файл:<td>'.$r['attach_link'] : '').
				'</table>'.
			'</div>';
	}

	 $send['spisok'] .= _next($filter + array(
			'all' => $all
		));

	return $send;
}


/* ---=== Откуда пришёл клиент ===--- */
function _clientFromJs() {//список источников, из которых приходил клиент
	$sql = "SELECT `id`,`name`
			FROM `_client_from`
			WHERE `app_id`=".APP_ID."
			ORDER BY `name`";
	return query_selJson($sql);
}
function _clientFrom($id) {
	$key = CACHE_PREFIX.'client_from';
	if(!$arr = xcache_get($key)) {
		$sql = "SELECT
					`id`,
					`name`
				FROM `_client_from`
				WHERE `app_id`=".APP_ID."
				ORDER BY `name`";
		$arr = query_ass($sql);
		xcache_set($key, $arr, 86400);
	}

	if(!isset($arr[$id]))
		_cacheErr('Неизвестный id источника клиента', $id);

	return $arr[$id];
}
function _client_from() {//показ страницы Откуда пришёл клиент
	return
	'<div id="client-from">'.
		'<div class="headName">'.
			'Источники, из которых приходят клиенты'.
			'<a class="add">Добавить новый источник</a>'.
		'</div>'.
		_client_from_setup().
		'<div id="spisok">'._client_from_spisok().'</div>'.
	'</div>';
}
function _client_from_spisok() {//список источников
	$sql = "SELECT
				*,
				0 `count`
			FROM `_client_from`
			WHERE `app_id`=".APP_ID."
			ORDER BY `name`";
	if(!$spisok = query_arr($sql))
		return 'Источники не определены.';

	$sql = "SELECT
				`from_id`,
				COUNT(`id`) `count`
			FROM `_client`
			WHERE `app_id`=".APP_ID."
			  AND `from_id`
			GROUP BY `from_id`";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q))
		$spisok[$r['from_id']]['count'] = $r['count'];

	$send = '<table class="_spisok">'.
				'<tr><th>Название источника'.
					'<th>Кол-во клиентов<br />за всё время'.
					'<th>';
	foreach($spisok as $id => $r)
		$send .= '<tr val="'.$id.'">'.
					'<td class="name">'.$r['name'].
					'<td class="count center">'.($r['count'] ? $r['count'] : '').
					'<td class="ed">'.
						_iconEdit($r).
						_iconDel($r + array('nodel'=>$r['count']));
	$send .= '</table>';

	return $send;
}
function _client_from_setup() {//настройки использования источников клиентов
	if(!VIEWER_ADMIN)
		return '';

	return
	'<table id="cf-setup" class="bs10">'.
		'<tr><td class="label r w150">Включить использование:<td>'._check('client_from_use', '', _app('client_from_use')).
		'<tr class="tr-require'.(_app('client_from_use') ? '' : ' dn').'">'.
			'<td class="label r">Требовать обязательно указывать источник при внесении нового клиента:'.
			'<td>'._check('client_from_require', '', _app('client_from_require')).
		'<tr class="tr-submit dn"><td><td><button class="vk setup-submit">Применить настройки</button>'.
	'</table>';
}

