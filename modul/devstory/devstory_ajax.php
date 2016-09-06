<?php
switch(@$_POST['op']) {
	case 'devstory_part_add'://внесение нового раздела
		if(!SA)
			jsonError();

		$name = _txt($_POST['name']);

		if(!$name)
			jsonError();

		$sql = "INSERT INTO `_devstory_part` (
					`name`,
					`sort`
				) VALUES (
					'".addslashes($name)."',
					"._maxSql('_devstory_part')."
				)";
		query($sql);

		$send['part'] = utf8(_devstory_part_spisok());
		jsonSuccess($send);
		break;
	case 'devstory_part_edit'://редактирование раздела
		if(!SA)
			jsonError();

		if(!$id = _num($_POST['id']))
			jsonError();

		$name = win1251(trim($_POST['name']));

		if(!$name)
			jsonError();

		$sql = "SELECT *
				FROM `_devstory_part`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_devstory_part`
				SET `name`='".addslashes($name)."'
				WHERE `id`=".$id;
		query($sql);

		$send['part'] = utf8(_devstory_part_spisok());
		jsonSuccess($send);
		break;
	case 'devstory_task_add'://внесение нового раздела
		if(!SA)
			jsonError();

		if(!$part_id = _num($_POST['part_id']))
			jsonError();

		if(!$part_sub_id = _devstoryTaskEditPartSubId($part_id))
			jsonError();

		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);

		if(!$name)
			jsonError();

		$sql = "INSERT INTO `_devstory_task` (
					`part_id`,
					`name`,
					`about`
				) VALUES (
					".$part_sub_id.",
					'".addslashes($name)."',
					'".addslashes($about)."'
				)";
		query($sql);

		jsonSuccess();
		break;
	case 'devstory_task_edit'://редактирование задачи
		if(!SA)
			jsonError();

		if(!$id = _num($_POST['id']))
			jsonError();

		if(!$part_id = _num($_POST['part_id']))
			jsonError();

		if(!$part_sub_id = _devstoryTaskEditPartSubId($part_id))
			jsonError();

		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);

		if(!$name)
			jsonError();

		$sql = "SELECT *
				FROM `_devstory_task`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_devstory_task`
				SET `part_id`=".$part_sub_id.",
					`name`='".addslashes($name)."',
					`about`='".addslashes($about)."'
				WHERE `id`=".$id;
		query($sql);

		jsonSuccess();
		break;
	case 'devstory_task_start'://запуск задачи в работу
		if(!SA)
			jsonError();

		if(!$task_id = _num($_POST['id']))
			jsonError();

		$time = _txt($_POST['time']);

		$sql = "SELECT *
				FROM `_devstory_task`
				WHERE `id`=".$task_id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "INSERT INTO `_devstory_time` (
					`task_id`,
					`time_start`
				) VALUES (
					".$task_id.",
					'".addslashes($time)."'
				)";
		query($sql);

		$sql = "UPDATE `_devstory_task`
				SET `status_id`=1
				WHERE `id`=".$task_id;
		query($sql);

		$send['html'] = utf8(_devstory_task_spisok());
		jsonSuccess($send);
		break;
	case 'devstory_task_pause'://постановка задачи на паузу
		if(!SA)
			jsonError();

		if(!$task_id = _num($_POST['id']))
			jsonError();

		$time = _txt($_POST['time']);

		$sql = "SELECT *
				FROM `_devstory_task`
				WHERE `id`=".$task_id."
				  AND `status_id`=1";
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "SELECT *
				FROM `_devstory_time`
				WHERE `task_id`=".$task_id."
				  AND `time_end`='0000-00-00 00:00:00'";
		if(!$tm = query_assoc($sql))
			jsonError();

		$spent = round((strtotime($time) - strtotime($tm['time_start'])) / 60);

		$sql = "UPDATE `_devstory_time`
				SET `time_end`='".addslashes($time)."',
					`spent`=".$spent."
				WHERE `id`=".$tm['id'];
		query($sql);

		$sql = "UPDATE `_devstory_task` `dt`
				SET `status_id`=2
				WHERE `id`=".$task_id;
		query($sql);

		_devstoryTaskSpentUpdate($task_id);

		$send['html'] = utf8(_devstory_task_spisok());
		jsonSuccess($send);
		break;
	case 'devstory_task_ready'://задача выполнена
		if(!SA)
			jsonError();

		if(!$task_id = _num($_POST['id']))
			jsonError();

		$time = _txt($_POST['time']);

		$sql = "SELECT *
				FROM `_devstory_task`
				WHERE `id`=".$task_id."
				  AND `status_id`=1";
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "SELECT *
				FROM `_devstory_time`
				WHERE `task_id`=".$task_id."
				  AND `time_end`='0000-00-00 00:00:00'";
		if(!$tm = query_assoc($sql))
			jsonError();

		$spent = round((strtotime($time) - strtotime($tm['time_start'])) / 60);

		$sql = "UPDATE `_devstory_time`
				SET `time_end`='".addslashes($time)."',
					`spent`=".$spent."
				WHERE `id`=".$tm['id'];
		query($sql);

		$sql = "UPDATE `_devstory_task`
				SET `status_id`=3
				WHERE `id`=".$task_id;
		query($sql);

		_devstoryTaskSpentUpdate($task_id);

		$send['html'] = utf8(_devstory_task_spisok());
		jsonSuccess($send);
		break;
	case 'devstory_task_cancel'://задача отменена
		if(!SA)
			jsonError();

		if(!$task_id = _num($_POST['id']))
			jsonError();

		$time = _txt($_POST['time']);

		$sql = "SELECT *
				FROM `_devstory_task`
				WHERE `id`=".$task_id."
				  AND `status_id` IN (1,2)";
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "SELECT *
				FROM `_devstory_time`
				WHERE `task_id`=".$task_id."
				  AND `time_end`='0000-00-00 00:00:00'";
		if($tm = query_assoc($sql)) {
			$spent = round((strtotime($time) - strtotime($tm['time_start'])) / 60);

			$sql = "UPDATE `_devstory_time`
					SET `time_end`='".addslashes($time)."',
						`spent`=".$spent."
					WHERE `id`=".$tm['id'];
			query($sql);
		}

		$sql = "UPDATE `_devstory_task`
				SET `status_id`=4
				WHERE `id`=".$task_id;
		query($sql);

		_devstoryTaskSpentUpdate($task_id);

		$send['html'] = utf8(_devstory_task_spisok());
		jsonSuccess($send);
		break;
}

function _devstoryTaskEditPartSubId($part_id) {//получение id подраздела при внесении/редактировании задачи
	if(!$part_sub_id = _num($_POST['part_sub_id'])) {
		$part_sub_name = _txt($_POST['part_sub_name']);
		if(!$part_sub_name)
			return 0;

		$sql = "SELECT `id`
				FROM `_devstory_part`
				WHERE `parent_id`=".$part_id."
				  AND `name`='".addslashes($part_sub_name)."'
				LIMIT 1";
		if(!$part_sub_id = query_value($sql)) {
			$sql = "INSERT INTO `_devstory_part` (
						`parent_id`,
						`name`
					) VALUES (
						".$part_id.",
						'".addslashes($part_sub_name)."'
					)";
			query($sql);
			$part_sub_id = query_insert_id('_devstory_part');
			xcache_unset(CACHE_PREFIX.'devstory_part');
		}
	}
	return $part_sub_id;
}
function _devstoryTaskSpentUpdate($task_id) {//обновление количества минут, потраченых на задачу
	$sql = "SELECT IFNULL(SUM(`spent`),0)
			FROM `_devstory_time`
			WHERE `task_id`=".$task_id;
	$spent = query_value($sql);

	$sql = "UPDATE `_devstory_task` `dt`
			SET `spent`=".$spent."
			WHERE `id`=".$task_id;
	query($sql);
}


