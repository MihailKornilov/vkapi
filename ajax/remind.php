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

		jsonSuccess();
		break;
	case 'remind_action':
		if(!$id = _num($_POST['id']))
			jsonError();
		if(!$status = _num($_POST['status']))
			jsonError();
		if(!preg_match(REGEXP_DATE, $_POST['day']) && !$_POST['day'])
			jsonError();

		$day = $_POST['day'];
		$reason = _txt($_POST['reason']);

		//Изменять можно только активные напоминания
		$sql = "SELECT *
				FROM `_remind`
				WHERE `app_id`=".APP_ID."
				  AND `status`=1
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		if($r['status'] != $status || $status == 1 && $r['day'] != $day) {
			$sql = "UPDATE `_remind`
			        SET `status`=".$status.
				($status == 1 ? ",`day`='".$day."'" : '')."
			        WHERE `id`=".$id;
			query($sql);

			_remind_history_add(array(
				'remind_id' => $r['id'],
				'status' => $status,
				'day' => ($status == 1 ? $day : ''),
				'reason' => $reason
			));

			//Обновление списка причин
			if($status == 1) {
				$sql = "SELECT IFNULL(`id`,0)
				        FROM `_remind_reason`
						WHERE `app_id`=".APP_ID."
						  AND `txt`='".addslashes($reason)."'
						LIMIT 1";
				$reason_id = query_value($sql);

				$sql = "INSERT INTO `_remind_reason` (
							`id`,
							`app_id`,
							`txt`
						) VALUES (
							".$reason_id.",
							".APP_ID.",
							'".addslashes($reason)."'
						) ON DUPLICATE KEY UPDATE
							`count`=`count`+1";
				query($sql);
			}
		}
		jsonSuccess();
		break;
	case 'remind_reason_spisok':
		$sql = "SELECT `id`,`txt`
				FROM `_remind_reason`
				WHERE `app_id`=".APP_ID."
				ORDER BY `count` DESC, `txt`";
		$send['spisok'] = query_selArray($sql);
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
				  AND `status`=1
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		if($txt == $r['txt'] && $about == $r['about'])
			jsonError();

		$sql = "UPDATE `_remind`
				SET `txt`='".addslashes($txt)."',
					`about`='".addslashes($about)."'
				WHERE `id`=".$id;
		query($sql);

		jsonSuccess();
		break;
	case 'remind_spisok':
		$send['spisok'] = utf8(_remind('spisok', $_POST));
		jsonSuccess($send);
		break;
}