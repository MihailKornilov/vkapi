<?php
switch(@$_POST['op']) {
	case 'remind_add':
		if(!preg_match(REGEXP_DATE, $_POST['day']))
			jsonError();

		$_POST['client_id'] = _num($_POST['client_id']);
		$_POST['zayav_id'] = _num($_POST['zayav_id']);
		$_POST['txt'] = _txt($_POST['txt']);
		$_POST['about'] = _txt($_POST['about']);

		if(!$_POST['txt'])
			jsonError();

		_remind_add($_POST);

		switch($_POST['from']) {
			case 'zayav': $send['html'] = utf8(_remind_zayav($_POST['zayav_id'])); break;
			case 'client':
				$remind = _remind_spisok(array('client_id'=>$_POST['client_id']));
				$send['html'] = utf8($remind['spisok']);
				break;
			default: $send['html'] = utf8(_remind_spisok(array(), 'spisok'));

		}

		jsonSuccess($send);
		break;
	case 'remind_action':
		if(!$id = _num($_POST['id']))
			jsonError();
		if(!preg_match(REGEXP_NUMERIC, $_POST['status']))
			jsonError();
		if(!preg_match(REGEXP_DATE, $_POST['day']) && !$_POST['day'])
			jsonError();

		$day = $_POST['day'];
		$status = intval($_POST['status']);
		$reason = _txt($_POST['reason']);

		//Изменять можно только активные напоминания
		$sql = "SELECT *
				FROM `_remind`
				WHERE `app_id`=".APP_ID."
				  ".(defined('WS_ID') ? " AND `ws_id`=".WS_ID : '')."
				  AND `status`=1
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		if($r['status'] != $status || $status == 1 && $r['day'] != $day) {
			$sql = "UPDATE `_remind`
			        SET `status`=".$status.
				($status == 1 ? ",`day`='".$day."'" : '')."
			        WHERE `id`=".$id;
			query($sql, GLOBAL_MYSQL_CONNECT);

			_remind_history_add(array(
				'remind_id' => $r['id'],
				'status' => $status,
				'day' => ($status == 1 ? $day : ''),
				'reason' => $reason
			));

			//Обновление списка причин
			if($status == 1) {
				$sql = "SELECT `id`
				        FROM `_remind_reason`
						WHERE `app_id`=".APP_ID."
						  ".(defined('WS_ID') ? " AND `ws_id`=".WS_ID : '')."
						  AND `txt`='".addslashes($reason)."'";
				$reason_id = query_value($sql, GLOBAL_MYSQL_CONNECT);

				if(!$reason_id)
					$reason_id = 0;

				$sql = "INSERT INTO `_remind_reason` (
							`id`,
							`app_id`,
							`ws_id`,
							`txt`
						) VALUES (
							".$reason_id.",
							".APP_ID.",
							".(defined('WS_ID') ? WS_ID : 0).",
							'".addslashes($reason)."'
						) ON DUPLICATE KEY UPDATE
							`count`=`count`+1";
				query($sql, GLOBAL_MYSQL_CONNECT);
			}
		}

		$v = array();
		if($_POST['from'] == 'client')
			$v['client_id'] = $r['client_id'];
		if($_POST['from'] == 'zayav')
			$v['zayav_id'] = $r['zayav_id'];
		$send['html'] = utf8(_remind_spisok($v, 'spisok'));

		jsonSuccess($send);
		break;
	case 'remind_reason_spisok':
		$sql = "SELECT `id`,`txt`
				FROM `_remind_reason`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				ORDER BY `count` DESC, `txt`";
		$send['spisok'] = query_selArray($sql, GLOBAL_MYSQL_CONNECT);
		jsonSuccess($send);
		break;
	case 'remind_head_edit':
		if(!$id = _num($_POST['id']))
			jsonError();
		if(!$txt = _txt($_POST['txt']))
			jsonError();

		$about = _txt($_POST['about']);

		$sql = "SELECT *
				FROM `_remind`
				WHERE `app_id`=".APP_ID."
				  AND `ws_id`=".WS_ID."
				  AND `status`=1
				  AND `id`=".$id;
		if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
			jsonError();

		if($txt == $r['txt'] && $about == $r['about'])
			jsonError();

		$sql = "UPDATE `_remind`
				SET `txt`='".addslashes($txt)."',
					`about`='".addslashes($about)."'
				WHERE `id`=".$id;
		query($sql, GLOBAL_MYSQL_CONNECT);

		$v = array();
		if($_POST['from'] == 'client')
			$v['client_id'] = $r['client_id'];
		if($_POST['from'] == 'zayav')
			$v['zayav_id'] = $r['zayav_id'];
		$send['html'] = utf8(_remind_spisok($v, 'spisok'));

		jsonSuccess($send);
		break;
	case 'remind_spisok':
		$send['html'] = utf8(_remind_spisok($_POST, 'spisok'));
		jsonSuccess($send);
		break;
}