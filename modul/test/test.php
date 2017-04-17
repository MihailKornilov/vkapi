<?php
function test_page() {//страница отображения
	return test_word();
}

function _test_script() {//скрипты для test
	return
//		'<link rel="stylesheet" type="text/css" href="'.API_HTML.'/modul/tovar/tovar'.MIN.'.css?'.VERSION.'" />'.
		'<script src="'.API_HTML.'/modul/test/test'.MIN.'.js?'.VERSION.'"></script>';
}



function test_word() {//подсчёт количества слов в книге
	//слов в текущей книге
	$sql = "SELECT COUNT(*) FROM `test_word_book`";
	$wordCount = query_value($sql);

	//уникальных слов в книге
	$sql = "SELECT COUNT(DISTINCT `word`) FROM `test_word_book`";
	$wordUnic = query_value($sql);

	//сохранено из книги
	$sql = "SELECT COUNT(DISTINCT `book`.`word`)
			FROM
				`test_word_book` `book`,
				`test_word_save` `save`
			WHERE `save`.`word`=`book`.`word`";
	$bookSave = query_value($sql);

	//сохранено слов
	$sql = "SELECT COUNT(*) FROM `test_word_save`";
	$wordSave = query_value($sql);

	//сохранено слов сегодня
	$sql = "SELECT COUNT(*)
			FROM `test_word_save`
			WHERE `dtime_add`>DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 12 HOUR)";
	$wordSaveToday = query_value($sql);


	$sql = "SELECT
				`book`.`id`,
				`book`.`word`,
				COUNT(`book`.`id`) `c`,
				IFNULL(`save`.`id`,0) `saved`
			FROM `test_word_book` `book`
			
				LEFT JOIN `test_word_save` `save`
				ON `save`.`word`=`book`.`word`
			
			GROUP BY `book`.`word`
			ORDER BY RAND()
			LIMIT 25";
	$words = '<table class="_spisokTab w250 mt10">';
	$n = 1;
	foreach(query_arr($sql) as $id => $r)
		$words .= '<tr class="over1">'.
			'<td class="w15 fs12 grey r">'.($n++).
			'<td><a href="https://translate.yandex.ru/?lang=tr-ru&text='.test_word_trans($r['word']).'"'.
				  ' target="_blank"'.
				  ' onmouseenter="testWordFind('.$id.')"'.
				  ' class="fs15 '.($r['saved'] ? 'color-pay' : 'color-555').'">'.
						$r['word'].
				'</a>'.
			'<td class="w15 center">'.$r['c'].
			'<td class="w15 wsnw">'.
				(!$r['saved'] ?
					'<div onclick="testWordSave('.$id.')" class="icon icon-add'._tooltip('Добавить в словарь', -61).'</div>'.
					'<div onclick="testWordDel('.$id.')" class="icon icon-del-red'._tooltip('Удалить', -26).'</div>'
				: '');
	$words .= '</table>';

	return
	'<div class="mar10">'.
		'<input type="text" class="w200" /> '.
		'<button class="vk" onclick="testBookUpdate($(this))">обновить книгу</button>'.

		'<div class="fr color-pay mr20">'.
			'<p>Сохранено: <b>'.$wordSave.'</b>'.
			'<p>Сегодня: <b>'.$wordSaveToday.'</b>'.
		'</div>'.

		'<p class="mt20">Слов в книге: <b>'._sumSpace($wordCount).'</b>'.
		'<p>Уникальных: '.
			'<b>'._sumSpace($wordUnic).'</b>: '.
			'<span class="color-ref">'.($wordUnic - $bookSave).'</span>'.
			':'.
			'<span class="color-pay">'.$bookSave.'</span>'.

		'<div id="book-str" class="fs15 w450 pad10 bor-e8 fr" style="max-height:700px;overflow:hidden"></div>'.
		$words.
	'</div>';
}

function test_word_insert($fileName) {//внесение книги в базу
	/*

//загруженные книги
DROP TABLE IF EXISTS `_global`.`test_book`;
CREATE TABLE `test_book` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` text,
  `content` MEDIUMTEXT,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

DROP TABLE IF EXISTS `_global`.`test_word_book`;
CREATE TABLE `test_word_book` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `word` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

DROP TABLE IF EXISTS `_global`.`test_word_save`;
CREATE TABLE `test_word_save` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `word` text,
  `dtime_add` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

	*/

	if(!$fp = @fopen(API_PATH.'/!/tr/'.$fileName, 'r'))
		return false;

	$sql = "DELETE FROM `test_word_book`";
	query($sql);

	$content = array(); //содержание книги для загрузки в базу
	while (!feof($fp)) {
		$str = trim(fgets($fp));

		if(!strlen($str))
			continue;

		$vowels = array(
			'.',
			',',
			'!',
			'?',
			'-',
			"'",
			'"',
			'«',
			'»',
			':',
			';',
			'–',
			'—',
			'_',
			'/',
			'\\',
			'(',
			')',
			'<',
			'>',
			'^',
			'|',
			'~',
			'*',
			'%',
//			'“',
			'ї',
			'п',
			'$',
			'вЂњ',//“
			'вЂќ',//”
			'вЂў',
			'вЂћ',
			'вЂ”',
//			'вЂ¦',
			'	'
		);

		$str = str_replace('Г–', '&ouml', $str);//? - &Ouml;
		$str = str_replace('Г¶', '&ouml', $str);//?
		$str = str_replace('Гј', '&uuml', $str);//?
		$str = str_replace('Гњ', '&uuml', $str);//? - &Uuml;
		$str = str_replace('Д±', '&#305', $str);//?
		$str = str_replace('Г§', '&ccedil', $str);//?
		$str = str_replace('Г‡', '&ccedil', $str);//? - &ccedil;
		$str = str_replace('Еџ', '&#351', $str);//?
		$str = str_replace('Ећ', '&#351', $str);//? - &#350;
		$str = str_replace('Дџ', '&#287', $str);//?
		$str = str_replace('Дћ', '&#287', $str);//?
		$str = str_replace('вЂ™', '’', $str);//’
		$str = str_replace('вЂ?', '‘', $str);//‘
		$str = str_replace('Д°', 'i', $str);//? - &#304;
		$str = str_replace('Гў', '&#226', $str);//?
		$str = str_replace('Г‚', '&#226', $str);//? - &#194;
		$str = str_replace('Г®', '&#238', $str);//?
		$str = str_replace('ГЋ', '&#238', $str);//? - &#206;

		$content[] = $str;

		$str = str_replace($vowels, ' ', $str);
		$str = strtolower($str);

		$words = array();
		foreach(explode(' ', $str) as $word) {
			$word = trim($word);
			if(empty($word))
				continue;
			if(_num($word))
				continue;
			$words[] = "('".addslashes($word)."')";
		}

		if(!empty($words)) {
			$sql = "INSERT INTO `test_word_book` (`word`) VALUES ".implode(',', $words);
			query($sql);
		}
	}

	$sql = "DELETE FROM `test_book`";
	query($sql);
	if($content) {
		$sql = "INSERT INTO `test_book` (
					`name`,
					`content`
				) VALUES (
					'".addslashes($fileName)."',
					'".addslashes(implode("\n", $content))."'
				)";
		query($sql);
	}


	return true;
}
function test_word_trans($str) {//транслитерация для отправки переводчику
	$str = str_replace('&ouml', '%C3%B6', $str);//?
	$str = str_replace('&uuml', '%C3%BC', $str);//?
	$str = str_replace('&#305', '%C4%B1', $str);//?
	$str = str_replace('&ccedil', '%C3%A7', $str);//?
	$str = str_replace('&#351', '%C5%9F', $str);//?
	$str = str_replace('&#287', '%C4%9F', $str);//?
	$str = str_replace('&#226', '%C3%A2', $str);//?
	$str = str_replace('&#238', '%C3%AE', $str);//?

	return $str;
}















/* ---=== ЗАДАЧИ ===--- */
function _task() {
	return
	'<div class="mar10">'.
		'<div class="hd2">'.
			'Текущие задачи'.
(VIEWER_ADMIN ? '<button class="vk small green fr" onclick="_taskEdit()">Новая задача</button>' : '').
		'</div>'.
		'<div class="_info mt10">Показаны задачи, которые считаются незавершёнными на текущий момент времени.</div>'.
		'<div class="fs15 mt15">Заявки</div>'.
		_task_spisok().
	'</div>';
}
function _task_spisok() {//список задач
	$sql = "SELECT *
			FROM `_task`
			WHERE `app_id`=".APP_ID."
			ORDER BY `id`";
	if(!$spisok = query_arr($sql))
		return '<div class="mar10 grey">Задач нет.</div>';

	$n = 1;
	$send = '<table class="_spisokTab mt5">';
	foreach($spisok as $id => $r) {
		$sql = "SELECT `k`,`v`
				FROM `_task_filter`
				WHERE `app_id`=".APP_ID."
				  AND `task_id`=".$id;
		$filter = query_ass($sql);
		$data = _zayav_spisok($filter);

		$edit = array('onclick' => '_taskEdit('.$id.')');
		$del = array(
			'del' => 1,
			'onclick' => '_taskDel('.$id.')'
		);
		$send .= '<tr class="over1">'.
			'<td class="w15 grey r">'.($n++).
			'<td>'.$r['name'].
			'<td class="w70 center"><a href="'.URL.'&p=2&task_id='.$id.'" class="b">'.$data['all'].'</a>'.
		(VIEWER_ADMIN ?
			'<td class="w15 wsnw">'.
				_iconEditNew($r + $edit).
				_iconDelNew($r + $del)
		: '');
	}
	$send .= '</table>';

	return $send;
}

