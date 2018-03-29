<?php
define('CRON_KEY', 'jern32n32Md93J83hs');
if(@$_GET['cron_key'] != CRON_KEY)
	exit;

set_time_limit(300);

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('display_startup_errors', true);

require_once 'modul/vk/vk.php';

//define('BR', '<br />');
define('BR', "\n");
define('VIEWER_ID', 0);
define('VIEWER_ID_ADMIN', 0);
define('CRON_MAIL', 'mihan_k@mail.ru');




_cronAppParse();
_cronSubmit();


function zp_accrual() {//начисление ставки сотрудникам
	$year = strftime('%Y');
	$mon = strftime('%m');
	$day = intval(strftime('%d'));
	$w = date('w', time()); //день недели
	$week = !$w ? 7 : $w;   //если день недели 0 - это воскресенье
	$about = 'Ставка за '._monthDef($mon).' '.$year;

	$send = '';

	$sql = "SELECT *
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `worker`
			  AND `salary_rate_sum`>0";
	$q = query($sql);
	while($r = mysql_fetch_assoc($q)) {
		$insert = 0;
		switch($r['salary_rate_period']) {
			case 1:	$insert = $r['salary_rate_day'] == $day; break;
			case 2:	$insert = $r['salary_rate_day'] == $week; break;
			case 3: $insert = 1; break;
		}
		if(!$insert)
			continue;
		$sql = "INSERT INTO `_salary_accrual` (
					`app_id`,
					`worker_id`,
					`sum`,
					`about`,
					`year`,
					`mon`
				) VALUES (
					".APP_ID.",
					".$r['viewer_id'].",
					".$r['salary_rate_sum'].",
					'".$about."',
					".$year.",
					".$mon."
				)";
		query($sql);

		_balans(array(
			'action_id' => 19,
			'worker_id' => $r['viewer_id'],
			'sum' => $r['salary_rate_sum'],
			'about' => $about
		));

		_history(array(
			'type_id' => 46,
			'worker_id' => $r['viewer_id'],
			'v1' => _cena($r['salary_rate_sum']),
			'v2' => $about
		));

		$send .= 'Начисление ставки для сотрудника '._viewer($r['viewer_id'], 'viewer_name').' в сумме '._cena($r['salary_rate_sum']).' руб.'.BR;
	}
	return $send;
}
function zp_image_attach($worker_id) {//начисление зп сотруднику за загруженные картинки (пока только Даше)
	if(APP_ID != 2031819)
		return '';

	$year = strftime('%Y');
	$mon = strftime('%m');

	//позавчера
	$beforeYesterday = strftime('%Y-%m-%d', time() - 3600 * 48);

	//вчера
	$yesterday = strftime('%Y-%m-%d', time() - 3600 * 24);

	$sql = "SELECT COUNT(DISTINCT `unit_id`)
			FROM `_image`
			WHERE `app_id`=".APP_ID."
			  AND `viewer_id_add`=".$worker_id."
			  AND !`deleted`
			  AND `unit_name`='tovar'
			  AND `dtime_add` LIKE '".$yesterday." %'";
	if(!$count = query_value($sql))
		return '';

	$sql = "SELECT COUNT(DISTINCT `unit_id`)
			FROM `_image`
			WHERE `app_id`=".APP_ID."
			  AND `viewer_id_add`=".$worker_id."
			  AND !`deleted`
			  AND `unit_name`='tovar'
			  AND `dtime_add` LIKE '".$beforeYesterday." %'";
	$countBYD = query_value($sql);//количество изображений за позавчера

	$about = 'Прикреплены изображения к '.$count.' товар'._end($count, 'у', 'ам');
	$sum = round($count * ($countBYD >= 100 ? 1.5 : 1)); //1-1.5 рубль за один товар

	$worker_id = $worker_id == 418627813 ? 139400639 : $worker_id;//старая страница Даши

	$sql = "INSERT INTO `_salary_accrual` (
				`app_id`,
				`worker_id`,
				`sum`,
				`about`,
				`year`,
				`mon`
			) VALUES (
				".APP_ID.",
				".$worker_id.",
				".$sum.",
				'".$about."',
				".$year.",
				".$mon."
			)";
	query($sql);

	_balans(array(
		'action_id' => 19,
		'worker_id' => $worker_id,
		'sum' => $sum,
		'about' => $about
	));

	return BR.$about.BR;
}
function smena_zp_accrual() {//начисление ставки сотрудникам за смены
	if(APP_ID != 3978722)//пока только для евроокон
		return '';

	$day = _num(strftime('%d'));
	if($day != 1)//начисление только 1 числа каждого месяца
		return '';

	$year = _num(strftime('%Y'));
	$mon = _num(strftime('%m'));
	if(!--$mon) {
		$year--;
		$mon = 12;
	}

	$YM = $year.'-'.($mon < 10 ? 0 : '').$mon;

	//текущий бюджет за месяц по сменам
	$sql = "SELECT `value`
			FROM `_setup_global`
			WHERE `app_id`=".APP_ID."
			  AND `key`='SMENA_MON_BUDGET'
			LIMIT 1";
	$budget = _num(query_value($sql));

	$sql = "SELECT COUNT(`id`)
			FROM `_smena`
			WHERE `app_id`=".APP_ID."
			  AND `started`
			  AND `dtime_add` LIKE '".$YM."%'";
	if(!$smena_count = _num(query_value($sql)))
		return '';

	//сумма за одну смену
	$smena_cena = $budget / $smena_count;

	//сотрудники и количество смен по каждому
	$sql = "SELECT 
				`worker_id`,
				COUNT(`id`)
			FROM `_smena`
			WHERE `app_id`=".APP_ID."
			  AND `started`
			  AND `dtime_add` LIKE '".$YM."%'
			GROUP BY `worker_id`";
	$ass = query_ass($sql);

	$send = '';
	foreach($ass as $worker_id => $c) {
		$sum = round($smena_cena * $c);
		$about = 'ставка: '.$c.' смен'._end($c, 'а', 'ы', '').' за '._monthDef($mon).' '.$year;
		$sql = "INSERT INTO `_salary_accrual` (
					`app_id`,
					`worker_id`,
					`sum`,
					`about`,
					`year`,
					`mon`
				) VALUES (
					".APP_ID.",
					".$worker_id.",
					".$sum.",
					'".$about."',
					".$year.",
					".$mon."
				)";
		query($sql);

		_balans(array(
			'action_id' => 19,
			'worker_id' => $worker_id,
			'sum' => $sum,
			'about' => $about
		));

		_history(array(
			'type_id' => 46,
			'worker_id' => $worker_id,
			'v1' => $sum,
			'v2' => $about
		));

		$send .= 'Начисление ставки за смены сотруднику '._viewer($worker_id, 'viewer_name').' в сумме '.$sum.' руб.'.BR;
	}

	return $send;
}















function _cronMailSend() {
	if($content = ob_get_contents()) {
		$content .=
			BR.BR.'----'.BR.
			'Время выполнения: '.round(microtime(true) - TIME, 3);
		mail(CRON_MAIL, 'Cron', $content);
	}
}
function _cronAppParse() {//прохождение по всем приложениям
	if(!empty($_GET['api_id']))
		return false;

	ob_start();
	register_shutdown_function('_cronMailSend');

	$sql = "SELECT
				*
			FROM `_app`
			ORDER BY `id`";
	if(!$spisok = query_arr($sql))
		die('Приложений нет.');

	$send = '';
	foreach($spisok as $r) {
		if($content = file_get_contents('http://'.DOMAIN.API_HTML.'/cron.php?cron_key='.CRON_KEY.'&api_id='.$r['id']))
			$send .= $r['id'].' - '.$r['title'].BR.$content;
	}

	_dbDump();

	echo  $send;
	exit;
}
function _cronSubmit() {//выполнение задач
	define('APP_ID', _num(@$_GET['api_id']));

	if(!APP_ID)
		return false;

	$sql = "SELECT *
			FROM `_app`
			WHERE `id`=".APP_ID;
	if(!$app = query_assoc($sql))
		die('Приложение '.APP_ID.' не зарегистрировано.');

	define('CACHE_PREFIX', 'CACHE_'.APP_ID.'_');

	echo
		zp_accrual().
		zp_image_attach(418627813).//Даша новая
		zp_image_attach(228890122).//Татьяна
		zp_image_attach(163178453).//Олеся
		smena_zp_accrual();
}
