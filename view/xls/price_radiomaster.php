<?php
$excel = PHPExcel_IOFactory::load(GLOBAL_DIR.'/view/xls/price.xls');
$excel->setActiveSheetIndex(0);
$aSheet = $excel->getActiveSheet();

// обнуление наличия
query("UPDATE `zp_price` SET `avai`=0 WHERE `org_id`=1");

$price = array();
$sql = "SELECT * FROM `zp_price`";
$q = query($sql);
while($r = mysql_fetch_assoc($q))
	$price[$r['articul']] = array(
		'id' => $r['id'],
		'name' => $r['name'],
		'cena' => $r['cena']
	);

$groups = array(
	'Дисплеи' => 1,
	'Тачскрины (сенсорные стекла)' => 1,
	'Шлейфы' => 1,
	'Подложки клавиатуры' => 1,
	'Платы' => 1,
	'Возвратные механизмы' => 1,
	'Держатели SIM-карт, карт памяти' => 1,
	'Джойстики, кнопки включения' => 1,
	'Динамики, звонки' => 1,
	'Камеры' => 1,
//	'Микросхемы' => 1,
	'Микрофоны' => 1,
	'Нижние разъемы, контакты' => 1,
	'Разъемы гарнитуры' => 1,
	'Разное' => 1,
	'Аккумуляторы' => 1,
	'Клавиатуры' => 1,
	'Корпуса, корпусные части' => 1,
	'Стекла' => 1,
	'Стилусы' => 1,
	'Матрицы для ноутбуков' => 1,
	'Раземы питания для ноутбуков' => 1,
	'Антенны' => 1
);

$values = array();
$upd = array();
$avai = array();
$insert = 0; // проверка, нужно ли вносить элемент
$cenaRow = 0;
$rowCount = 0; // подсчёт поличества обработанных строк
foreach($aSheet->getRowIterator() as $r => $row) {
	if($r < 10) {
		if($cenaRow)
			continue;
		// определение, в какой колонке находится 'Оптовая'
		foreach($row->getCellIterator() as $k => $cell) {
			$v = iconv('utf-8', 'cp1251', $cell->getCalculatedValue());
			if($v == 'Оптовая') {
				$cenaRow = $k;
				echo 'Номер колонки "Оптовая": '.$cenaRow.'<br />';
				break;
			}
		}
		continue;
	}

	if(!$cenaRow)
		die('Не определён номер колонки для "Оптовая"');

	$articul = 0;
	$name = '';
	$cena = '';
	foreach($row->getCellIterator() as $k => $cell) {
		if($k > 10)
			break;
		$v = $cell->getCalculatedValue();
		if(!$k && _num($v))
			$articul = $v;
		if($k == 2)
			$name = iconv('utf-8', 'cp1251', $v);
		if($k == $cenaRow)
			$cena = $v;
	}

	if(!$articul)
		$insert = isset($groups[$name]);

	if($insert && $articul) {
		if(!$name || !$cena)
			continue;
		$avai[] = $articul;
		$v = "(1,".$articul.",'".addslashes($name)."',".$cena.",CURRENT_TIMESTAMP)";
		if(empty($price[$articul]))
			$values[] = $v;
		elseif($price[$articul]['name'] != $name || $price[$articul]['cena'] != $cena) {
			$values[] = $v;
			if($price[$articul]['name'] != $name)
				$upd[] = "(".$price[$articul]['id'].",'name','".addslashes($price[$articul]['name'])."','".addslashes($name)."')";
			if($price[$articul]['cena'] != $cena)
				$upd[] = "(".$price[$articul]['id'].",'cena','".round($price[$articul]['cena'], 2)."','".round($cena, 2)."')";
		}
	}
	$rowCount++;
}

echo 'Обработано строк: '.$rowCount.'<br />';

if(!empty($values)) {
	$countBefore = query_value("SELECT COUNT(`id`) FROM `zp_price`");
	$sql = "INSERT INTO `zp_price` (`org_id`,`articul`,`name`,`cena`,`updated`)
			VALUES ".implode(',', $values)."
			ON DUPLICATE KEY UPDATE
				`name`=VALUES(`name`),
				`cena`=VALUES(`cena`),
				`updated`=CURRENT_TIMESTAMP";
	query($sql);
	$countAdded = query_value("SELECT COUNT(`id`) FROM `zp_price`") - $countBefore;
	if($countAdded)
		echo 'Добавлено: <b>'.$countAdded.'</b><br />';
}

if(!empty($avai)) {
	query("UPDATE `zp_price` SET `avai`=1,`updated`=CURRENT_TIMESTAMP WHERE `articul` IN (".implode(',', $avai).") AND `org_id`=1");
	echo 'Наличие: '.count($avai).'<br />';
}

if(!empty($upd)) {
	$sql = "INSERT INTO `zp_price_upd` (`price_id`,`row`,`old`,`new`) VALUES ".implode(',', $upd);
	query($sql);
	echo 'Изменено: <b>'.count($upd).'</b><br />';
}

echo 'Время: '.round(microtime(true) - TIME, 3);
