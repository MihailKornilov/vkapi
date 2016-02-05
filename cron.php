<?php
require_once 'view/_vk.php';

define('VIEWER_ID', 0);
define('CRON_MAIL', 'mihan_k@mail.ru');




_cronAppParse();
_cronSubmit();


function _cronMailSend() {
	echo "\n\n----\nExecution time: ".round(microtime(true) - TIME, 3);
	mail(CRON_MAIL, 'Cron mobile: zp_accrual.php', ob_get_contents());
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
	if(!$spisok = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return 'ѕриложений нет.';

	foreach($spisok as $r) {
		$content = file_get_contents("http://nyandoma/.vkapp/.api/cron.php?api_id=".$r['id']);
		echo 'ѕолучен контент: '.$content.'<br />';
	}

	exit;
}
function _cronSubmit() {//применение
	define('APP_ID', _num(@$_GET['api_id']));

	if(!APP_ID)
		return false;

	$sql = "SELECT *
			FROM `_app`
			WHERE `id`=".APP_ID;
	if(!$app = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		die('ѕриложение '.APP_ID.' не зарегистрировано.');

	define('CACHE_PREFIX', 'CACHE_'.APP_ID.'_');

//	zp_accrual();
	echo  APP_ID;
}

function zp_accrual() {//начисление ставки сотрудникам
	$year = strftime('%Y');
	$mon = strftime('%m');
	$day = intval(strftime('%d'));
	$w = date('w', time()); //день недели
	$week = !$w ? 7 : $w;   //если день недели 0 - это воскресенье
	$about = '—тавка за '._monthDef($mon).' '.$year;

	$sql = "SELECT *
			FROM `_vkuser`
			WHERE `app_id`=".APP_ID."
			  AND `worker`
			  AND `salary_rate_sum`>0";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
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
					`ws_id`,
					`worker_id`,
					`sum`,
					`about`,
					`year`,
					`mon`
				) VALUES (
					".APP_ID.",
					".$r['ws_id'].",
					".$r['viewer_id'].",
					".$r['salary_rate_sum'].",
					'".$about."',
					".$year.",
					".$mon."
				)";
		query($sql, GLOBAL_MYSQL_CONNECT);

		_balans(array(
			'ws_id' => $r['ws_id'],
			'action_id' => 19,
			'worker_id' => $r['viewer_id'],
			'sum' => $r['salary_rate_sum'],
			'about' => $about
		));

		_history(array(
			'ws_id' => $r['ws_id'],
			'type_id' => 46,
			'worker_id' => $r['viewer_id'],
			'v1' => _cena($r['salary_rate_sum']),
			'v2' => $about
		));
	}
}









