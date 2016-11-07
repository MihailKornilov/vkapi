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

	case 'devstory_task_load'://загрузка данных задачи для внесения или редактирования
		if(!SA)
			jsonError();

		$part_id = _num($_POST['part_id']);
		$keyword_ids = 0;
		if($task_id = _num($_POST['task_id'])) {
			$sql = "SELECT *
					FROM `_devstory_task`
					WHERE `id`=".$task_id;
			if(!$r = query_assoc($sql))
				jsonError();
			$part_id = $r['part_id'];

			$sql = "SELECT `keyword_id`
					FROM `_devstory_keyword_use`
					WHERE `task_id`=".$task_id;
			$keyword_ids = query_ids($sql);
		}

		$sql = "SELECT
					DISTINCT `word`.`id`,
					`name`
				FROM
					`_devstory_keyword` `word`,
					`_devstory_keyword_use` `use`
				WHERE `word`.`id`=`keyword_id`
				  AND `part_id`=".$part_id."
				ORDER BY `name`";
		$keyword_spisok = query_selArray($sql);

		$send = array(
			'part_name' => utf8(_devstoryPart($part_id)),
			'keyword_ids' => $keyword_ids,
			'keyword_spisok' => $keyword_spisok,
			'name' => isset($r) ? utf8($r['name']) : '',
			'about' => isset($r) ? utf8($r['about']) : ''
		);

		jsonSuccess($send);
		break;
	case 'devstory_task_add'://внесение новой задачи
		if(!SA)
			jsonError();

		if(!$part_id = _num($_POST['part_id']))
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
					".$part_id.",
					'".addslashes($name)."',
					'".addslashes($about)."'
				)";
		query($sql);

		$task_id = query_insert_id('_devstory_task');

		_devstoryTaskKeywordUpdate($task_id, $part_id);

		$send['task_id'] = $task_id;
		jsonSuccess($send);
		break;
	case 'devstory_task_edit'://редактирование задачи
		if(!SA)
			jsonError();

		if(!$task_id = _num($_POST['id']))
			jsonError();

		$name = _txt($_POST['name']);
		$about = _txt($_POST['about']);

		if(!$name)
			jsonError();

		$sql = "SELECT *
				FROM `_devstory_task`
				WHERE `id`=".$task_id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `_devstory_task`
				SET `name`='".addslashes($name)."',
					`about`='".addslashes($about)."'
				WHERE `id`=".$task_id;
		query($sql);

		_devstoryTaskKeywordUpdate($task_id, $r['part_id']);

		$send['task_id'] = $task_id;
		jsonSuccess($send);
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
				SET `status_id`=1,
					`dtime_start`=CURRENT_TIMESTAMP
				WHERE `id`=".$task_id;
		query($sql);

		jsonSuccess();
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

		jsonSuccess();
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
				  AND `status_id` IN(1,2)";
		if(!$r = query_assoc($sql))
			jsonError();

		if($r['status_id'] == 1) {
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
		}

		$sql = "UPDATE `_devstory_task`
				SET `status_id`=3,
					`dtime_end`=CURRENT_TIMESTAMP
				WHERE `id`=".$task_id;
		query($sql);

		_devstoryTaskSpentUpdate($task_id);

		jsonSuccess();
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
				SET `status_id`=4,
					`dtime_end`=CURRENT_TIMESTAMP
				WHERE `id`=".$task_id;
		query($sql);

		_devstoryTaskSpentUpdate($task_id);

		jsonSuccess();
		break;
}

function _devstoryTaskKeywordUpdate($task_id, $part_id) {//обновление ключевых слов задачи
	$ids = _ids($_POST['keyword_ids']);
	$keyword = _txt($_POST['keyword']);
	if($keyword) {
		$sql = "SELECT `id`
				FROM `_devstory_keyword`
				WHERE `name`='".addslashes($keyword)."'
				LIMIT 1";
		if(!$keyword_id = query_value($sql)) {
			$sql = "INSERT INTO `_devstory_keyword`
						(`name`)
					VALUES 
						('".addslashes($keyword)."')";
			query($sql);
			xcache_unset(CACHE_PREFIX.'devstory_keyword');
			$keyword_id = query_insert_id('_devstory_keyword');
		}
		$ids = $ids ? $ids.','.$keyword_id : $keyword_id;
	} else {
		$sql = "DELETE FROM `_devstory_keyword_use`
				WHERE `task_id`=".$task_id;
		query($sql);
	}

	if($ids) {
		$insert = array();
		foreach(_ids($ids ,1) as $r)
			$insert[] = "(".$part_id.",".$task_id.",".$r.")";
		$sql = "INSERT INTO `_devstory_keyword_use` (
					`part_id`,
					`task_id`,
					`keyword_id`
				) VALUES ".implode(',', $insert);
		query($sql);
	}
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


