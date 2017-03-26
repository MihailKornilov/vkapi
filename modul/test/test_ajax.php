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
}










