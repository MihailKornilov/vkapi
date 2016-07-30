<?php
define('CRON_KEY', 'jern32n32Md93J83hs');
if(@$_GET['cron_key'] != CRON_KEY)
	exit;

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('display_startup_errors', true);

require_once 'view/_vk.php';

//define('BR', '<br />');
define('BR', "\n");
define('VIEWER_ID', 0);
define('CRON_MAIL', 'mihan_k@mail.ru');




_cronAppParse();
_cronSubmit();


function zp_accrual() {//начисление ставки сотрудникам
	$year = strftime('%Y');
	$mon = strftime('%m');
	$day = intval(strftime('%d'));
	$w = date('w', time()); //день недели
	$week = !$w ? 7 : $w;   //если день недели 0 - это воскресенье
	$about = '—тавка за '._monthDef($mon).' '.$year;

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

		$send .= 'Ќачисление ставки дл€ сотрудника '._viewer($r['viewer_id'], 'viewer_name').' в сумме '._cena($r['salary_rate_sum']).' руб.'.BR;
	}
	return $send;
}

















function _cronMailSend() {
	if($content = ob_get_contents()) {
		$content .=
			BR.BR.'----'.BR.
			'¬рем€ выполнени€: '.round(microtime(true) - TIME, 3);
		mail(CRON_MAIL, 'Cron', $content);
	}
}
function _cronAppParse() {//прохождение по всем приложени€м
	if(!empty($_GET['api_id']))
		return false;

	ob_start();
	register_shutdown_function('_cronMailSend');

	$sql = "SELECT
				*
			FROM `_app`
			ORDER BY `id`";
	if(!$spisok = query_arr($sql))
		die('ѕриложений нет.');

	$send = '';
	foreach($spisok as $r) {
		if($content = file_get_contents('http://'.DOMAIN.API_HTML.'/cron.php?cron_key='.CRON_KEY.'&api_id='.$r['id']))
			$send .= $r['id'].' - '.$r['name'].BR.$content;
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
		die('ѕриложение '.APP_ID.' не зарегистрировано.');

	define('CACHE_PREFIX', 'CACHE_'.APP_ID.'_');

	echo  zp_accrual();
}
