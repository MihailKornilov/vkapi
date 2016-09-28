<?php
if(!SA)
	jsonError();

switch(@$_POST['op']) {
	case 'cache_clear':
		_globalCacheClear();
		_globalJsValues();
		_appJsValues();

		//обновление глобальных значений
		$sql = "UPDATE `_setup_global`
				SET `value`=`value`+1
				WHERE !`app_id`";
		query($sql);

		//обновление значений js всех приложений по отдельности
		$sql = "UPDATE `_app` SET `js_values`=`js_values`+1";
		query($sql);

		jsonSuccess();
		break;
	case 'cookie_clear':
		if(!empty($_COOKIE))
			foreach($_COOKIE as $key => $val)
				setcookie($key, '', time() - 3600, '/');
		jsonSuccess();
		break;

	case 'debug_sql':
		$nocache = _bool($_POST['nocache']);
		$explain = _bool($_POST['explain']);

		$sql = ($explain ? 'EXPLAIN ' : '').trim($_POST['query']);
		$q = query($sql);

		if($nocache)
			$sql = preg_replace('/SELECT/', 'SELECT NO_SQL_CACHE', $sql);

		if($explain) {
			$exp = '<table>';
			$n = 1;
			while($r = mysql_fetch_assoc($q)) {
				$exp .= '<tr>';
				if($n++ == 1) {
					foreach($r as $i => $v)
						$exp .= '<th>'.$i;
					$exp .= '<tr>';
				}
				foreach($r as $v)
					$exp .= '<td>'.$v;
			}
			$exp .= '<table>';
			$send['exp'] = $exp;
		}

		$send['query'] = $sql;
		$send['html'] =
			//'rows: <b>'.$q['rows'].'</b>, '.
			'time: '.$q['time'];
		jsonSuccess($send);
		break;
	case 'debug_cookie':
		$send['html'] = _debug_cookie();
		jsonSuccess($send);
		break;
}
