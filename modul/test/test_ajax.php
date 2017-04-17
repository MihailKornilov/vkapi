<?php
switch(@$_POST['op']) {
	case 'test_book_update':
		if(!$name = _txt($_POST['name']))
			jsonError('Не указано имя файла');
			
		if(!test_word_insert($name))
			jsonError('Файла не существует');
		
		jsonSuccess();
		break;
	case 'test_word_find'://поиск слова в книге
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT `word`
				FROM `test_word_book`
				WHERE `id`=".$id;
		if(!$word = query_value($sql))
			jsonError('Слова нет в книге');

		$sql = "SELECT `content`
				FROM `test_book`
				LIMIT 1";
		$book = query_value($sql);
		$pos = stripos($book, $word);
		$start = _num($pos - 300);
		$end = $start + 300;
		$str = substr($book, $start, 600);

		$reg = '/('.$word.')/iu';
		$str = preg_replace($reg, '<span class="fs15 fndd">\\1</span>', $str, 1);

		$send['str'] =
			($start ? '...' : '')
			._br($str)
			.($end < strlen($book) - $start ? '...' : '');
		
		jsonSuccess($send);
		break;
	case 'test_word_save'://сохранение слова в словарь
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `test_word_book`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "SELECT COUNT(*)
				FROM `test_word_save`
				WHERE `word`='".addslashes($r['word'])."'";
		if(query_value($sql))
			jsonError();

		$sql = "INSERT INTO `test_word_save` (
					`word`
				) VALUES (
					'".addslashes($r['word'])."'
				)";
		query($sql);

		jsonSuccess();
		break;
	case 'test_word_del'://удаление слова из книги
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `test_word_book`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "SELECT COUNT(*)
				FROM `test_word_save`
				WHERE `word`='".addslashes($r['word'])."'";
		if(query_value($sql))
			jsonError();

		$sql = "DELETE FROM `test_word_book`
				WHERE `word`='".addslashes($r['word'])."'";
		query($sql);

		jsonSuccess();
		break;
	
	case 'task_cond_load'://загрузка условий фильтра (пока) заявок
		$v['task'] = 1;
		$send['name'] = '';

		if($task_id = _num($_POST['task_id'])) {
			$sql = "SELECT `name`
					FROM `_task`
					WHERE `id`=".$task_id;
			$send['name'] = utf8(query_value($sql));

			$sql = "SELECT `k`,`v`
					FROM `_task_filter`
					WHERE `task_id`=".$task_id;
			$v = query_ass($sql) + $v;
		}

		if(empty($v))
			$v['service_id'] = _service('first');

		$v = _zayavFilter($v) + $v;

		$html =
			'<div class="pad10 bg-gr3 bor-e8 w200">'.
				_zayavPoleFilter($v).
			'</div>';
		$send['html'] = utf8($html);
		$send['filter'] = $v;

		jsonSuccess($send);
		break;
	case 'task_zayav_count'://получение количества заявок при изменении фильтра
		$data = _zayav_spisok($_POST);

		$html = '<div class="grey">Заявок не найдено</div>';
		if($all = $data['all'])
			$html = '<b>'.$all.'</b> заяв'._end($all, 'ка', 'ки', 'ок');

		$send['count'] = utf8($html);

		jsonSuccess($send);
		break;
	case 'task_add':
		if(!$name = _txt($_POST['task_name']))
			jsonError('Не указано название задачи');

		$sql = "INSERT INTO `_task` (
					`app_id`,
					`name`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					'".addslashes($name)."',
					".VIEWER_ID."
				)";
		query($sql);
		$task_id = query_insert_id('_task');

		_task_filter_insert($task_id);

		jsonSuccess();
		break;
	case 'task_edit'://редактирование задачи
		if(!$task_id = _num($_POST['task_id']))
			jsonError('Некорректный ID задачи');
		if(!$name = _txt($_POST['task_name']))
			jsonError('Не указано название задачи');

		$sql = "SELECT *
				FROM `_task`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$task_id;
		if(!$r = query_assoc($sql))
			jsonError('Задачи не существует');

		$sql = "UPDATE `_task`
				SET `name`='".addslashes($name)."'
				WHERE `id`=".$task_id;
		query($sql);

		_task_filter_insert($task_id);

		jsonSuccess();
		break;
	case 'task_del'://удаление задачи
		if(!$id = _num($_POST['id']))
			jsonError('Некорректный ID задачи');

		$sql = "SELECT *
				FROM `_task`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('Задачи не существует');

		$sql = "DELETE FROM `_task` WHERE `id`=".$id;
		query($sql);

		$sql = "DELETE FROM `_task_filter` WHERE `task_id`=".$id;
		query($sql);

		jsonSuccess();
		break;
}


function _task_filter_insert($task_id) {
	$sql = "DELETE FROM `_task_filter` WHERE `task_id`=".$task_id;
	query($sql);

	$values = array();
	foreach($_POST as $k => $v) {
		if($k == 'task')
			continue;
		if($k == 'task_id')
			continue;
		if($k == 'task_name')
			continue;
		if($k == 'op')
			continue;
		if($k == 'page')
			continue;
		if($k == 'limit')
			continue;
		if($k == 'sort')
			continue;
		if($k == 'desc')
			continue;
		if($k == 'find')
			continue;
		if($k == 'clear')
			continue;
		$values[] = "(
			".APP_ID.",
			".$task_id.",
			'".$k."',
			'".addslashes($v)."'
		)";
	}
	if(!empty($values)) {
		$sql = "INSERT INTO `_task_filter` (
					`app_id`,
					`task_id`,
					`k`,
					`v`
				) VALUES ".implode(',', $values);
		query($sql);
	}
}







