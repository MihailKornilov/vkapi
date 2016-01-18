<?php
function pageNum($n) {
	$arr = array(
		1 => 'A',
		2 => 'B',
		3 => 'C',
		4 => 'D',
		5 => 'E',
		6 => 'F',
		7 => 'G',
		8 => 'H',
		9 => 'I',
		10 => 'J',
		11 => 'K',
		12 => 'L',
		13 => 'M',
		14 => 'N',
		15 => 'O',
		16 => 'P',
		17 => 'Q',
		18 => 'R',
		19 => 'S',
		20 => 'T',
		21 => 'U',
		22 => 'V',
		23 => 'W',
		24 => 'X',
		25 => 'Y',
		26 => 'Z'
	);

	$res = '';
	if($n > 26) {
		$res = 'A';
		$n -= 26;
	}

	return $res.$arr[$n];
}//pageNum()
function pageSetup($book, $title) {
	$sheet = $book->getActiveSheet();

	//Глобальные стили для ячеек
	$book->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

	//Ориентация страницы и  размер листа
	$sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT)
		->SetPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);

	//Поля документа
	$sheet->getPageMargins()->setTop(0.9)
		->setRight(0.2)
		->setLeft(0.5)
		->setBottom(0.2);

	//Масштаб страницы
	$sheet->getSheetView()->setZoomScale(100);

	//Название страницы
	$sheet->setTitle($title);
}

function xls_schet_width($sheet) {//установка ширины столбцов
	$sheet->getColumnDimension('A')->setWidth(3);
	$sheet->getColumnDimension('B')->setWidth(40);
	$sheet->getColumnDimension('C')->setWidth(9);
	$sheet->getColumnDimension('D')->setWidth(8);
	$sheet->getColumnDimension('E')->setWidth(14);
	$sheet->getColumnDimension('F')->setWidth(14);
}//xls_schet_width()
function xls_schet_top($sheet, $ws) {
	$sheet->setCellValue('A1', utf8(htmlspecialchars_decode($ws['name'])));
	$sheet->getStyle('A1')->getFont()->setBold(true)->setUnderline(true);

	$sheet->setCellValue('A3', 'Адрес: '.utf8(htmlspecialchars_decode($ws['adres_yur'])));
	$sheet->getStyle('A3')->getFont()->setBold(true);

	$sheet->setCellValue('A4', 'Телефон: '.utf8(htmlspecialchars_decode($ws['phone'])));
	$sheet->getStyle('A4')->getFont()->setBold(true);
}//xls_schet_top()
function xls_schet_rekvisit($sheet, $ws) {
	$sheet->setCellValue('A6', 'Образец заполнения платежного поручения');
	$sheet->getStyle('A6')->getFont()->setBold(true);
	$sheet->mergeCells('A6:F6');
	$sheet->getStyle('A6')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


	$ram = array(
		'borders' => array(
			'outline' => array(
				'style' => PHPExcel_Style_Border::BORDER_THIN,
				'color' => array('argb' => 'FF000000'),
			),
		),
	);
	$sheet->getStyle('A7:F11')->applyFromArray($ram); //общая рамка

	$sheet->setCellValue('A7', 'ИНН '.utf8($ws['inn']).'                      КПП');
	$sheet->getStyle('A7:C7')->applyFromArray($ram);

	$sheet->setCellValue('A8', 'Получатель');
	$sheet->setCellValue('A9', utf8(htmlspecialchars_decode($ws['name'])));
	$sheet->getStyle('A8:C9')->applyFromArray($ram);

	$sheet->setCellValue('A10', 'Банк получателя');
	$sheet->setCellValue('A11', utf8(htmlspecialchars_decode($ws['bank_name'])));
	$sheet->setCellValue('D9', 'Сч. №');
	$sheet->setCellValue('E9', utf8($ws['bank_account']).' ');
	$sheet->getStyle('E7:F9')->applyFromArray($ram);

	$sheet->setCellValue('D10', 'БИК');
	$sheet->getStyle('D10:D10')->applyFromArray($ram);

	$sheet->setCellValue('E10', utf8($ws['bank_bik']).' ');
	$sheet->setCellValue('D11', 'Сч. №');
	$sheet->getStyle('D11:D11')->applyFromArray($ram);
	$sheet->setCellValue('E11', utf8($ws['bank_account_corr']).' ');

	$sheet->getStyle('D9:D11')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
}//xls_schet_rekvisit()
function xls_schet_head($sheet, $s) {
	$sheet->mergeCells('A13:F13');
	$sheet->setCellValue('A13', 'СЧЕТ № СЦ'.$s['nomer'].' от '.utf8(FullData($s['date_create'])).' г.');
	$sheet->getStyle('A13')->getFont()->setBold(true)->setSize(14);
	$sheet->getStyle('A13')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	$line = 16;

	$c = _clientVal($s['client_id']);
	$sheet->setCellValue('A'.$line, 'Плательщик:        '.utf8(htmlspecialchars_decode($c['name'])));
	$line++;
	if($c['inn'] || $c['kpp']) {
		$sheet->setCellValue('B'.$line,
			'                  '.
			($c['inn'] ? '    ИНН: '.$c['inn'] : '') .
			($c['kpp'] ? '    КПП: '.$c['kpp'] : '')
		);
		$line++;
	}
	if($c['adres']) {
		$sheet->setCellValue('B'.$line, '                      Адрес: '.utf8(htmlspecialchars_decode($c['adres'])));;
		$line++;
	}
	$sheet->setCellValue('A'.$line, 'Грузополучатель: '.utf8(htmlspecialchars_decode($c['name'])));
	$line++;
	if($c['adres']) {
		$sheet->setCellValue('B'. $line, '                      Адрес: '.utf8(htmlspecialchars_decode($c['adres'])));;
		$line++;
	}

	return $line + 1;
}//xls_schet_head()
function xls_schet_tabHead($sheet, $line) {//заголовок колонок таблицы
	$ram = array(
		'borders' => array(
			'allborders' => array(
				'style' => PHPExcel_Style_Border::BORDER_THIN,
				'color' => array('rgb' => '000000')
			)
		),
		'alignment' => array(
			'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
			'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
			'wrap' => true
		)
	);
	$sheet->getStyle('A'.$line.':F'.$line)->applyFromArray($ram);

	$sheet->getRowDimension($line)->setRowHeight(51);

	$sheet->setCellValue('A'.$line, '№');
	$sheet->setCellValue('B'.$line, "Наименование\nтовара");
	$sheet->setCellValue('C'.$line, "Единица\nизме-\nрения");
	$sheet->setCellValue('D'.$line, "Коли-\nчество");
	$sheet->setCellValue('E'.$line, 'Цена');
	$sheet->setCellValue('F'.$line, 'Сумма');
}//xls_schet_tabHead()
function xls_tabContent($sheet, $line) {
	$sql = "SELECT *
			FROM `_schet_content`
			WHERE `schet_id`=".SCHET_ID."
			ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$start = $line;
	$n = 1;
	$sum = 0;
	while($r = mysql_fetch_assoc($q)) {
		$s = $r['count'] * $r['cost'];
		$sheet->getCell('A'.$line)->setValue($n);
		$sheet->getCell('B'.$line)->setValue(utf8($r['name']));
		$sheet->getCell('C'.$line)->setValue('шт');
		$sheet->getCell('D'.$line)->setValue($r['count']);
		$sheet->getCell('E'.$line)->setValue(round($r['cost']).',00');
		$sheet->getCell('F'.$line)->setValue($s.',00');
		$line++;
		$n++;
		$sum += $s;
	}

	$sheet->getStyle('B'.$start.':B'.($line - 1))->getAlignment()->setWrapText(true);

	//рамка для списка
	$ram = array(
		'borders' => array(
			'allborders' => array(
				'style' => PHPExcel_Style_Border::BORDER_THIN,
				'color' => array('rgb' => '000000')
			)
		),
		'alignment' => array(
			'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
		)
	);
	$sheet->getStyle('A'.$start.':F'.($line - 1))->applyFromArray($ram);

	//центрирование шт в списке
	$sheet->getStyle('C'.$start.':C'.($line - 1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	//цены и стоимость вправо
	$sheet->getStyle('E'.$start.':F'.($line - 1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

	return array(
		'line' => $line,
		'n' => $n,
		'sum' => $sum
	);
}//xls_tabContent()
function xls_schet_tab($sheet, $line) {
	$start = $line + 1;
	$arr = xls_tabContent($sheet, $start);
	$line = $arr['line'];
	$n = $arr['n'];
	$sum = $arr['sum'];

	//итог
	$sheet->getCell('E'.$line)->setValue('Итого:');
	$sheet->getCell('F'.$line)->setValue($sum.',00');

	$sheet->getCell('E'.($line + 1))->setValue('Без налога (НДС).');
	$sheet->getCell('F'.($line + 1))->setValue('-              ');

	$sheet->getCell('E'.($line + 2))->setValue('Всего к оплате:');
	$sheet->getCell('F'.($line + 2))->setValue($sum.',00');

	$sheet->getStyle('E'.$line.':F'.($line + 2))->getFont()->setBold(true);

	//цены, сумма и итог вправо
	$sheet->getStyle('E'.$start.':F'.($line + 2))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

	//рамка для итога
	$sheet->getStyle('F'.$line.':F'.($line + 2))->applyFromArray(array(
		'borders' => array(
			'allborders' => array(
				'style' => PHPExcel_Style_Border::BORDER_THIN,
				'color' => array('rgb' => '000000')
			)
		)
	));

	$line += 4;
	$sheet->getCell('A'.$line)->setValue('Всего наименований '.($n - 1).', на сумму '.$sum.'.00');
	$line++;
	$sheet->getCell('A'.$line)->setValue(mb_ucfirst(utf8(_numToWord($sum))).' рубл'._end($sum, 'ь', 'я', 'ей').' 00 копеек');
	$sheet->getStyle('A'.$line)->getFont()->setBold(true);

	return $line;
}//xls_schet_tab()
function xls_schet_podpis($sheet, $line) {
	$line += 3;
	$sheet->getCell('A'.$line)->setValue('Руководитель предприятия_____________________ (Шерстянников С.Ю.)');
	$line += 3;
	$sheet->getCell('A'.$line)->setValue('Главный бухгалтер____________________________ (Шерстянникова Я.В.)');
}//xls_schet_podpis()



function xls_nakl_razmer($sheet) {
	//Размеры ячеек
	for($n = 1; $n <= 31; $n++)
		$sheet->getColumnDimension(pageNum($n))->setWidth(3);
	for($n = 1; $n <= 31; $n++)
		$sheet->getRowDimension($n)->setRowHeight(12);
}//xls_nakl_razmer()
function xls_nakl_head($sheet, $s) {
	$sheet->setCellValue('A1', 'Накладная № СЦ'.$s['nomer'].' от '.utf8(FullData($s['date_create'])).' г.');
	$sheet->getStyle('A1')
		->getFont()->setBold(true)
				   ->setName('Arial')
				   ->setSize(14);

	$sheet->mergeCells('A1:'.pageNum(31).'1');
	$sheet->getRowDimension(1)->setRowHeight(25);

	$ram = array(
		'borders' => array(
			'bottom' => array(
				'style' => PHPExcel_Style_Border::BORDER_THICK,
				'color' => array('rgb' => '000000')
			)
		),
		'alignment' => array(
			'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
		)
	);
	$sheet->getStyle('A1:'.pageNum(31).'1')->applyFromArray($ram);
}//xls_nakl_head()
function xls_nakl_rekvisit($sheet, $ws, $s, $line=3) {
	$x = pageNum(5);

	$sheet->setCellValue('A'.$line, 'Поставщик:');
	$sheet->setCellValue($x.$line, utf8(htmlspecialchars_decode($ws['name'])));
	$sheet->getStyle($x.$line)->getFont()->setBold(true);
	if($ws['adres_yur']) {
		$line++;
		$sheet->setCellValue($x.$line, utf8(htmlspecialchars_decode($ws['adres_yur'])));
		$sheet->getStyle($x.$line)->getFont()->setBold(true);
	}
	if($ws['phone']) {
		$line++;
		$sheet->setCellValue($x.$line, 'Телефон: '.utf8(htmlspecialchars_decode($ws['phone'])));
		$sheet->getStyle($x.$line)->getFont()->setBold(true);
	}

	$line += 2;
	$sheet->setCellValue('A'.$line, 'Покупатель:');
	$sheet->setCellValue($x.$line, utf8(htmlspecialchars_decode(_clientVal($s['client_id'], 'name'))));
	$sheet->getStyle($x.$line)->getFont()->setBold(true);

	$line += 2;
	$sheet->setCellValue('A'.$line, 'Склад:');

	$line += 2;
	return $line;
}//xls_nakl_rekvisit()
function xls_nakl_table($sheet, $line) {
	$ram = array(
		'borders' => array(
			'allborders' => array(
				'style' => PHPExcel_Style_Border::BORDER_THIN,
				'color' => array('rgb' => '000000')
			)
		),
		'alignment' => array(
			'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
			'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
		),
		'font' => array(
			'bold' => true
		)
	);
	$sheet->getStyle('A'.$line.':'.pageNum(31).$line)->applyFromArray($ram);

	$sheet->getRowDimension($line)->setRowHeight(25);

	$sheet->setCellValue('A'.$line, '№');
	$sheet->mergeCells('A'.$line.':B'.$line);

	$sheet->setCellValue(pageNum(3).$line, 'Товар');
	$sheet->mergeCells(pageNum(3).$line.':'.pageNum(18).$line);

	$sheet->setCellValue(pageNum(19).$line, 'Кол-во');
	$sheet->mergeCells(pageNum(19).$line.':'.pageNum(21).$line);

	$sheet->setCellValue(pageNum(22).$line, 'Ед.');
	$sheet->mergeCells(pageNum(22).$line.':'.pageNum(23).$line);

	$sheet->setCellValue(pageNum(24).$line, 'Цена');
	$sheet->mergeCells(pageNum(24).$line.':'.pageNum(27).$line);

	$sheet->setCellValue(pageNum(28).$line, 'Сумма');
	$sheet->mergeCells(pageNum(28).$line.':'.pageNum(31).$line);

	return ++$line;
}//xls_nakl_table()
function xls_nakl_tovar_spisok($sheet, $line) {
	$sql = "SELECT *
			FROM `_schet_content`
			WHERE `schet_id`=".SCHET_ID."
			ORDER BY `id`";
	$q = query($sql, GLOBAL_MYSQL_CONNECT);
	$start = $line;
	$n = 1;
	$sum = 0;
	while($r = mysql_fetch_assoc($q)) {
		$sheet->getCell('A'.$line)->setValue($n);
		$sheet->mergeCells('A'.$line.':B'.$line);

		$sheet->getCell(pageNum(3).$line)->setValue(utf8($r['name']));
		$sheet->mergeCells(pageNum(3).$line.':'.pageNum(18).$line);

		$sheet->getCell(pageNum(19).$line)->setValue($r['count']);
		$sheet->mergeCells(pageNum(19).$line.':'.pageNum(21).$line);

		$sheet->getCell(pageNum(22).$line)->setValue('шт');
		$sheet->mergeCells(pageNum(22).$line.':'.pageNum(23).$line);

		$sheet->getCell(pageNum(24).$line)->setValue(round($r['cost'], 2).',00');
		$sheet->mergeCells(pageNum(24).$line.':'.pageNum(27).$line);

		$s = $r['count'] * $r['cost'];
		$sheet->getCell(pageNum(28).$line)->setValue($s.',00');
		$sheet->mergeCells(pageNum(28).$line.':'.pageNum(31).$line);

		$line++;
		$n++;
		$sum += $s;
	}

	$sheet->getStyle('B'.$start.':B'.($line - 1))->getAlignment()->setWrapText(true);

	//рамка для списка
	$ram = array(
		'borders' => array(
			'allborders' => array(
				'style' => PHPExcel_Style_Border::BORDER_THIN,
				'color' => array('rgb' => '000000')
			)
		),
		'alignment' => array(
			'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
		),
		'font' => array(
			'size' => 8
		)
	);
	$sheet->getStyle('A'.$start.':'.pageNum(31).($line - 1))->applyFromArray($ram);


	//центрирование № в списке
	$sheet->getStyle('A'.$start.':A'.($line - 1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	//центрирование Кол-во в списке
	$sheet->getStyle(pageNum(19).$start.':'.pageNum(19).($line - 1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	//центрирование шт в списке
	$sheet->getStyle(pageNum(22).$start.':'.pageNum(22).($line - 1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	//цены и стоимость вправо
	$sheet->getStyle(pageNum(24).$start.':'.pageNum(31).($line - 1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

	//жирная рамка вокруг всей таблицы
	$ram = array(
		'borders' => array(
			'outline' => array(
				'style' => PHPExcel_Style_Border::BORDER_THICK,
				'color' => array('argb' => 'FF000000'),
			),
		),
	);
	$sheet->getStyle('A'.($start - 1).':'.pageNum(31).($line - 1))->applyFromArray($ram);

	return array(
		'line' => $line,
		'n' => $n,
		'sum' => $sum
	);
}//xls_nakl_tovar_spisok()
function xls_nakl_itogo($sheet, $r) {
	$line = $r['line'];
	$sum = $r['sum'];

	$line++;
	$sheet->setCellValue(pageNum(27).$line, 'Итого:');
	$sheet->getStyle(pageNum(27).$line)->getFont()->setBold(true);

	$sheet->setCellValue(pageNum(31).$line, _sumSpace($sum).',00');
	$sheet->getStyle(pageNum(31).$line)->getFont()->setBold(true);

	$sheet->getStyle(pageNum(24).$line.':'.pageNum(31).$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

	$line += 2;
	$sheet->getCell('A'.$line)->setValue('Всего наименований '.($r['n'] - 1).', на сумму '.$sum.'.00');

	$line++;
	$sheet->getCell('A'.$line)->setValue(mb_ucfirst(utf8(_numToWord($sum))).' рубл'._end($sum, 'ь', 'я', 'ей').' 00 копеек');
	$sheet->getStyle('A'.$line)->getFont()->setBold(true);
	$sheet->getRowDimension($line)->setRowHeight(18);
	$ram = array(
		'borders' => array(
			'bottom' => array(
				'style' => PHPExcel_Style_Border::BORDER_THICK,
				'color' => array('rgb' => '000000')
			)
		),
		'alignment' => array(
			'vertical' => PHPExcel_Style_Alignment::VERTICAL_TOP
		)
	);
	$sheet->getStyle('A'.$line.':'.pageNum(31).$line)->applyFromArray($ram);

	return $line + 3;
}//xls_nakl_itogo()
function xls_nakl_podpis($sheet, $line) {
	$sheet->setCellValue(pageNum(1).$line, 'Отпустил');
	$sheet->getStyle(pageNum(1).$line)->getFont()->setBold(true);

	$sheet->setCellValue(pageNum(17).$line, 'Получил');
	$sheet->getStyle(pageNum(17).$line)->getFont()->setBold(true);

	$line++;
	$ram = array(
		'borders' => array(
			'top' => array(
				'style' => PHPExcel_Style_Border::BORDER_THIN,
				'color' => array('rgb' => '000000')
			)
		),
		'alignment' => array(
			'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
			'vertical' => PHPExcel_Style_Alignment::VERTICAL_TOP
		),
		'font' => array(
			'size' => 7
		)
	);
	$xy = pageNum(5).$line.':'.pageNum(9).$line;
	$sheet->mergeCells($xy);
	$sheet->getStyle($xy)->applyFromArray($ram);
	$sheet->setCellValue(pageNum(5).$line, 'должность');

	$xy = pageNum(11).$line.':'.pageNum(15).$line;
	$sheet->mergeCells($xy);
	$sheet->getStyle($xy)->applyFromArray($ram);
	$sheet->setCellValue(pageNum(11).$line, 'подпись');

	$xy = pageNum(21).$line.':'.pageNum(25).$line;
	$sheet->mergeCells($xy);
	$sheet->getStyle($xy)->applyFromArray($ram);
	$sheet->setCellValue(pageNum(21).$line, 'должность');

	$xy = pageNum(27).$line.':'.pageNum(31).$line;
	$sheet->mergeCells($xy);
	$sheet->getStyle($xy)->applyFromArray($ram);
	$sheet->setCellValue(pageNum(27).$line, 'подпись');
}//xls_nakl_podpis()

function xls_act_width($sheet) {//установка ширины столбцов
	$sheet->getColumnDimension('A')->setWidth(3);
	$sheet->getColumnDimension('B')->setWidth(39);
	$sheet->getColumnDimension('C')->setWidth(9);
	$sheet->getColumnDimension('D')->setWidth(12);
	$sheet->getColumnDimension('E')->setWidth(12);
	$sheet->getColumnDimension('F')->setWidth(13);
}//xls_act_width()
function xls_act_top($sheet, $ws) {
	$sheet->setCellValue('A1', utf8(htmlspecialchars_decode($ws['name'])));
	$sheet->getStyle('A1')->getFont()->setBold(true)->setUnderline(true);

	$sheet->setCellValue('A2', 'Адрес: '.utf8(htmlspecialchars_decode($ws['adres_yur'])));
	$sheet->getStyle('A2')->getFont()->setBold(true);
}//xls_act_top()
function xls_act_head($sheet, $s) {
	$sheet->mergeCells('A4:F4');
	$sheet->setCellValue('A4', 'Акт № СЦ'.$s['nomer'].' от '.utf8(FullData($s['date_create'])).' г.');
	$sheet->getStyle('A4')->getFont()->setBold(true)->setSize(14);
	$sheet->getStyle('A4')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	$sheet->setCellValue('A6', 'Заказчик: '.utf8(htmlspecialchars_decode(_clientVal($s['client_id'], 'name'))));
}//xls_act_head()
function xls_act_tabHead($sheet) {//заголовок колонок таблицы
	$line = 8;

	$ram = array(
		'borders' => array(
			'allborders' => array(
				'style' => PHPExcel_Style_Border::BORDER_THIN,
				'color' => array('rgb' => '000000')
			)
		),
		'alignment' => array(
			'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
			'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
			'wrap' => true
		)
	);
	$sheet->getStyle('A'.$line.':F'.$line)->applyFromArray($ram);

	$sheet->setCellValue('A'.$line, '№');
	$sheet->setCellValue('B'.$line, 'Наименование работы (услуги)');
	$sheet->setCellValue('C'.$line, 'Ед. изм.');
	$sheet->setCellValue('D'.$line, 'Количество');
	$sheet->setCellValue('E'.$line, 'Цена');
	$sheet->setCellValue('F'.$line, 'Сумма');
}//xls_act_tabHead()
function xls_act_tab($sheet) {
	$start = 9;
	$arr = xls_tabContent($sheet, $start);
	$line = $arr['line'];
	$sum = $arr['sum'];

	//итого
	$sheet->getCell('E'.$line)->setValue('Итого:');
	$sheet->getCell('F'.$line)->setValue($sum.',00');

	$sheet->getStyle('E'.$line.':F'.$line)->getFont()->setBold(true);

	//цены, сумма и итог вправо
	$sheet->getStyle('E'.$start.':F'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

	//рамка для итога
	$sheet->getStyle('F'.$line.':F'.$line)->applyFromArray(array(
		'borders' => array(
			'allborders' => array(
				'style' => PHPExcel_Style_Border::BORDER_THIN,
				'color' => array('rgb' => '000000')
			)
		)
	));

	$line += 2;
	$sheet->getCell('A'.$line)->setValue('Всего оказано услуг на сумму: '.mb_ucfirst(utf8(_numToWord($sum))).' рубл'._end($sum, 'ь', 'я', 'ей').' 00 копеек.');
	$sheet->getStyle('A'.$line)->getFont()->setItalic(true);

	return $line;
}//xls_act_tab()
function xls_act_podpis($sheet, $line) {
	$line++;
	$sheet->getCell('A'.$line)->setValue(
		'Вышеперечисленные услуги выполнены полностью и в срок. '.
		'Заказчик претензий по объему, качеству и срокам оказания услуг не имеет.'
	);
	$sheet->mergeCells('A'.$line.':F'.$line);
	$sheet->getRowDimension($line)->setRowHeight(40);
	$sheet->getStyle('A'.$line)->getAlignment()->setWrapText(true);

	$line += 2;
	$sheet->getCell('A'.$line)->setValue('Исполнитель:');
	$sheet->getCell('C'.$line)->setValue('Заказчик:');

	$line++;
	$sheet->getRowDimension($line)->setRowHeight(9);
	$sheet->getCell('B'.$line)->setValue('подпись');
	$sheet->getStyle('B'.$line)->getFont()->setSize(6);
	$sheet->getStyle('B'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	$sheet->getCell('E'.$line)->setValue('подпись');
	$sheet->getStyle('E'.$line)->getFont()->setSize(6);

	$line += 2;
	$sheet->getCell('B'.$line)->setValue('                   М.П.');
	$sheet->getCell('D'.$line)->setValue('      М.П.');
}//xls_act_podpis()

function xls_act_podpis2($sheet, $line) {
	$line++;
	$sheet->getCell('A'.$line)->setValue(
		'Вышеперечисленные услуги выполнены полностью и в срок. '.
		'Заказчик претензий по объему, качеству и срокам оказания услуг не имеет.'
	);
	$sheet->mergeCells('A'.$line.':F'.$line);
	$sheet->getRowDimension($line)->setRowHeight(40);
	$sheet->getStyle('A'.$line)->getAlignment()->setWrapText(true);

	$line += 2;
	$sheet->getCell('A'.$line)->setValue('Исполнитель:');
	$sheet->getCell('C'.$line)->setValue('Заказчик:');

	$line++;
	$sheet->getRowDimension($line)->setRowHeight(9);
	$sheet->getCell('B'.$line)->setValue('подпись');
	$sheet->getStyle('B'.$line)->getFont()->setSize(6);
	$sheet->getStyle('B'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

	$sheet->getCell('E'.$line)->setValue('подпись');
	$sheet->getStyle('E'.$line)->getFont()->setSize(6);

	$line += 2;
	$sheet->getCell('B'.$line)->setValue('                   М.П.');
	$sheet->getCell('C'.$line)->setValue('Расшифровка подписи (ФИО): __________________');
	$line++;
	$sheet->getCell('C'.$line)->setValue('_____________________________________________');
	$line += 2;
	$sheet->getCell('C'.$line)->setValue('Дата и время выдачи: ___/___/______      ____:____');
	$line += 2;
	$sheet->getCell('D'.$line)->setValue('      М.П.');
}//xls_act_podpis2()


define('SCHET_ID', _num(@$_GET['schet_id']));
if(!SCHET_ID)
	die(win1251('Неверный id счёта.'));

if(!$s = _schetQuery(SCHET_ID))
	die(win1251('Счёта не существует.'));

define('NAKL', $s['nakl']);
define('ACT', $s['act']);

if($s['zayav_id']) {
	$sql = "SELECT *
			FROM `_zayav`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `id`=".$s['zayav_id'];
	if(!query_assoc($sql, GLOBAL_MYSQL_CONNECT))
		die(win1251('Заявки не существует.'));
}

$sql = "SELECT *
		FROM `_ws`
		WHERE `app_id`=".APP_ID."
		  AND `id`=".WS_ID;
$ws = query_assoc($sql, GLOBAL_MYSQL_CONNECT);

$page = 0;

$book = new PHPExcel();
$book->setActiveSheetIndex($page++);
$sheet = $book->getActiveSheet();

pageSetup($book, 'Счёт');

xls_schet_width($sheet);
xls_schet_top($sheet, $ws);
xls_schet_rekvisit($sheet, $ws);
$line = xls_schet_head($sheet, $s);
xls_schet_tabHead($sheet, $line);
$line = xls_schet_tab($sheet, $line);
xls_schet_podpis($sheet, $line);

if(NAKL) {
	$book->createSheet();
	$book->setActiveSheetIndex($page++);
	$sheet = $book->getActiveSheet();
	pageSetup($book, 'Накладная');
	xls_nakl_razmer($sheet);
	xls_nakl_head($sheet, $s);
	$line = xls_nakl_rekvisit($sheet, $ws, $s);
	$line = xls_nakl_table($sheet, $line);
	$r = xls_nakl_tovar_spisok($sheet, $line);
	$line = xls_nakl_itogo($sheet, $r);
	xls_nakl_podpis($sheet, $line);
}

if(ACT) {
	$book->createSheet();
	$book->setActiveSheetIndex($page++);
	$sheet = $book->getActiveSheet();
	pageSetup($book, 'Акт выполненных работ 1');
	xls_act_width($sheet);
	xls_act_top($sheet, $ws);
	xls_act_head($sheet, $s);
	xls_act_tabHead($sheet);
	$line = xls_act_tab($sheet);
	xls_act_podpis($sheet, $line);

	$book->createSheet();
	$book->setActiveSheetIndex($page++);
	$sheet = $book->getActiveSheet();
	pageSetup($book, 'Акт выполненных работ 2');
	xls_act_width($sheet);
	xls_act_top($sheet, $ws);
	xls_act_head($sheet, $s);
	xls_act_tabHead($sheet);
	$line = xls_act_tab($sheet);
	xls_act_podpis($sheet, $line);

	$book->createSheet();
	$book->setActiveSheetIndex($page++);
	$sheet = $book->getActiveSheet();
	pageSetup($book, 'Акт 3 (для бухгалтерии)');
	xls_act_width($sheet);
	xls_act_top($sheet, $ws);
	xls_act_head($sheet, $s);
	xls_act_tabHead($sheet);
	$line = xls_act_tab($sheet);
	xls_act_podpis2($sheet, $line);
}


$book->setActiveSheetIndex(0);






header('Content-Type:application/vnd.ms-excel');
header('Content-Disposition:attachment;filename="schet'.SCHET_ID.'_'.TODAY.'.xls"');
$writer = PHPExcel_IOFactory::createWriter($book, 'Excel5');
$writer->save('php://output');

mysql_close();
exit;


