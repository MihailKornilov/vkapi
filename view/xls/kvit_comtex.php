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
function pageSetup($title, $book) {
	$sheet = $book->getActiveSheet();

	//Глобальные стили для ячеек
	$book->getDefaultStyle()->getFont()->setName('Arial')
		->setSize(9);

	//Ориентация страницы и  размер листа
	$sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE)
		->SetPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);

	//Поля документа
	$sheet->getPageMargins()->setTop(0.2)
		->setRight(0.2)
		->setLeft(0.2)
		->setBottom(0.2);

	//Масштаб страницы
	$sheet->getSheetView()->setZoomScale(90);

	//Название страницы
	$sheet->setTitle($title);

	//Размеры ячеек
	for($n = 1; $n <= 46; $n++)
		$sheet->getColumnDimension(pageNum($n))->setWidth(3.5);
	for($n = 1; $n <= 46; $n++)
		$sheet->getRowDimension($n)->setRowHeight(13);
}
function xls_comtex_head($sheet, $n) {//заголовок с реквизитами
	$y = pageNum($n);

	$sheet->setCellValue($y.'2', 'Сервисный центр "КОМТЕКС"');
	$sheet->getStyle($y.'2')->getFont()->setBold(true)->setSize(11);

	$sheet->setCellValue($y.'3', 'Телефоны: 8 (81838) 6 39 91, 8 911 657 86 63');
	$sheet->getStyle($y.'3')->getFont()->setSize(8);

	$sheet->setCellValue($y.'4', 'Адрес: г.Няндома, ул.60 лет Октября 18');
	$sheet->getStyle($y.'4')->getFont()->setSize(8);

	$sheet->setCellValue($y.'5', 'Время работы: Пн-Пт 9-18, без обеда       Сб 10-14');
	$sheet->getStyle($y.'5')->getFont()->setSize(8);

	$sheet->getStyle($y.'6:'.pageNum($n + 18).'6')->applyFromArray(
		array(
			'borders' => array(
				'bottom'     => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		)
	);

	$sheet->setCellValue($y.'8', 'АКТ ПРИЁМА-ПЕРЕДАЧИ ОБОРУДОВАНИЯ № '.Z_NOMER);
	$sheet->getStyle($y.'8')->getFont()->setBold(true)->setSize(10);

}//xls_comtex_head()
function xls_comtex_center($sheet) {//разделительная центральная линия
	$y = pageNum(21);

	$sheet->getStyle($y.'1:'.$y.'46')->applyFromArray(
		array(
			'borders' => array(
				'right'     => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		)
	);

	$sheet->getStyle('A1:'.pageNum(46).'46')->applyFromArray(array(
		'borders' => array(
			'outline' => array(
				'style' => PHPExcel_Style_Border::BORDER_THIN,
				'color' => array('argb' => 'FF000000'),
			),
		),
	));
}//xls_comtex_center()
function xls_comtex_content($sheet, $z, $col, $row) {//левая сторона
	$tovar = array();
	$sql = "SELECT `tovar_id`
			FROM `_zayav_tovar`
			WHERE `zayav_id`=".$z['id']."
			LIMIT 1";
	if($tovar_id = query_value($sql))
		$tovar = _tovarQuery($tovar_id);

	$colLabel = pageNum($col);
	$colItem = pageNum($col + 7);

	$sheet->setCellValue($colLabel.$row[0], 'Изделие');
	$sheet->setCellValue($colItem.$row[0], utf8($tovar_id ? _tovarName($tovar['name_id']) : ''));
	$sheet->setCellValue($colLabel.$row[1], 'Модель');
	$sheet->setCellValue($colItem.$row[1], utf8($tovar_id ? _tovarVendor($tovar['vendor_id']).$tovar['name'] : ''));
	$sheet->setCellValue($colLabel.$row[2], 'Серийный номер');
	$sheet->setCellValue($colItem.$row[2], utf8($z['imei'] ? $z['imei'] : $z['serial']));
	$sheet->setCellValue($colLabel.$row[3], 'Дата приёма в ремонт');
	$sheet->setCellValue($colItem.$row[3], utf8(FullData($z['dtime_add'])));
	$sheet->setCellValue($colLabel.$row[4], 'Комплектность');
	$sheet->setCellValue($colItem.$row[4], utf8(trim($tovar_id ? _tovarName($tovar['name_id']) : '').($z['tovar_equip_ids'] ? ', '._tovarEquip('spisok', $z['tovar_equip_ids']) : '')));
	$sheet->setCellValue($colLabel.$row[5], 'Владелец');
	$sheet->setCellValue($colItem.$row[5], utf8(htmlspecialchars_decode(_clientVal($z['client_id'], 'name'))));
	$sheet->setCellValue($colLabel.$row[6], 'Телефоны');
	$sheet->setCellValue($colItem.$row[6], utf8(htmlspecialchars_decode(_clientVal($z['client_id'], 'phone'))));
	$sheet->setCellValue($colLabel.$row[7], 'Внешний вид');
	$sheet->setCellValue($colItem.$row[7], 'б/у');

	xls_comtex_item_border($sheet, $col, $row[0]);
	xls_comtex_item_border($sheet, $col, $row[1]);
	xls_comtex_item_border($sheet, $col, $row[2]);
	xls_comtex_item_border($sheet, $col, $row[3]);
	xls_comtex_item_border($sheet, $col, $row[4]);
	xls_comtex_item_border($sheet, $col, $row[5]);
	xls_comtex_item_border($sheet, $col, $row[6]);
	xls_comtex_item_border($sheet, $col, $row[7]);

	$sheet->setCellValue($colLabel.$row[8], 'Неисправность со слов владельца:');
	$sheet->setCellValue($colLabel.$row[9], utf8($z['defect']));
	$sheet->getStyle($colLabel.$row[9])->getFont()->setSize(7);

	$sheet->getStyle('B29:T29')->applyFromArray(
		array(
			'borders' => array(
				'bottom'     => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		)
	);
}//xls_comtex_left();
function xls_comtex_right($sheet) {//правая сторона
	$sheet->getStyle('W21:'.pageNum(45).'21')->applyFromArray(
		array(
			'borders' => array(
				'bottom'     => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		)
	);

	$sheet->setCellValue('X23', 'В результате проверки уставлено:');
	$sheet->setCellValue('X26', 'Произведены работы:');
	$sheet->setCellValue('X29', 'Стоимость работы: ________________       Работу выполнил: ________________');

	$sheet->getStyle('W30:'.pageNum(45).'30')->applyFromArray(
		array(
			'borders' => array(
				'bottom'     => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN
				)
			)
		)
	);

}//xls_comtex_left();
function xls_comtex_item_border($sheet, $col, $row) {//бордюры для элементов-значений
	$colStart = pageNum($col + 7);
	$colEnd = pageNum($col + 18);

	$adr = $colStart.$row.':'.$colEnd.$row;

	$sheet->mergeCells($adr); //объединение ячеек
	$sheet->getStyle($adr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT); //сдвиг вправо
	$sheet->getStyle($adr)->applyFromArray(array( //рисование рамки
		'borders' => array(
			'outline' => array(
				'style' => PHPExcel_Style_Border::BORDER_THIN,
				'color' => array('argb' => 'FF000000'),
			),
		),
	));
}//xls_comtex_item_border()
function xls_comtex_rules($sheet, $x=2, $y=31) {
	$sheet->setCellValue(pageNum($x).$y, '1. ');
	//$sheet->mergeCells('C31:T33');
	$sheet->setCellValue(pageNum($x + 1).$y, 'В случае подтверждения гарантийности оборудования Исполнитель');
		$sheet->getStyle(pageNum($x + 1).$y)->getFont()->setSize(8);
	$sheet->setCellValue(pageNum($x + 1).($y + 1), 'приступает к выполнению работ без согласования сроков');
		$sheet->getStyle(pageNum($x + 1).($y + 1))->getFont()->setSize(8);
	$sheet->setCellValue(pageNum($x + 1).($y + 2), 'с Заказчиком. Срок ремонта может составлять до 45 дней.');
		$sheet->getStyle(pageNum($x + 1).($y + 2))->getFont()->setSize(8);

	$sheet->setCellValue(pageNum($x).($y + 3), '2. ');
	$sheet->setCellValue(pageNum($x + 1).($y + 3), 'Исполнитель не несёт ответственности за неуказанные неисправности.');
		$sheet->getStyle(pageNum($x + 1).($y + 3))->getFont()->setSize(8);

	$sheet->setCellValue(pageNum($x).($y + 4), '3. ');
	$sheet->setCellValue(pageNum($x + 1).($y + 4), 'По окончании ремонта Исполнитель сообщает заказчику о готовности.');
		$sheet->getStyle(pageNum($x + 1).($y + 4))->getFont()->setSize(8);

	$sheet->setCellValue(pageNum($x).($y + 5), '4. ');
	$sheet->setCellValue(pageNum($x + 1).($y + 5), 'В случае отказа от ремонта стоимость диагностики составляет 300 рублей.');
		$sheet->getStyle(pageNum($x + 1).($y + 5))->getFont()->setSize(8);

	$sheet->setCellValue(pageNum($x).($y + 6), '5. ');
	$sheet->setCellValue(pageNum($x + 1).($y + 6), 'Стороны договорились, что после 3 месяцев хранения');
		$sheet->getStyle(pageNum($x + 1).($y + 6))->getFont()->setSize(8);
	$sheet->setCellValue(pageNum($x + 1).($y + 7), 'изделие поступает в полное распоряжение сервисного центра.');
		$sheet->getStyle(pageNum($x + 1).($y + 7))->getFont()->setSize(8);

	$sheet->setCellValue(pageNum($x).($y + 8), '6. ');
	$sheet->setCellValue(pageNum($x + 1).($y + 8), 'Исполнитель не отвечает за сохранность информации.');
		$sheet->getStyle(pageNum($x + 1).($y + 8))->getFont()->setSize(8);

	$sheet->setCellValue(pageNum($x).($y + 9), '7. ');
	$sheet->setCellValue(pageNum($x + 1).($y + 9), 'Своей подписью Заказчик подтверждает, что согласен');
		$sheet->getStyle(pageNum($x + 1).($y + 9))->getFont()->setSize(8);
	$sheet->setCellValue(pageNum($x + 1).($y + 10), 'с вышеизложенным.');
		$sheet->getStyle(pageNum($x + 1).($y + 10))->getFont()->setSize(8);

	$sheet->setCellValue(pageNum($x + 17).($y + 9), 'М.П.');
		$sheet->getStyle(pageNum($x + 17).($y + 9))->getFont()->setBold(true);

	$sheet->setCellValue(pageNum($x).($y + 12), '_____________(подпись клиента)      ____________(подпись приёмщика)');
	$sheet->setCellValue(pageNum($x).($y + 14), 'Изделие получил: ______________________(подпись)                      ___/___/______');

}//xls_comtex_rules()

if(!$id = _num($_GET['id']))
	die(win1251('Неверный id заявки.'));

if(!$z = _zayavQuery($id))
	die(win1251('Заявки не существует.'));

define('Z_NOMER', $z['nomer']);

$z['defect'] = _note(array(
	'last' => 1,
	'p' => 45,
	'id' => $id
));

$book = new PHPExcel();
$book->setActiveSheetIndex(0);
$sheet = $book->getActiveSheet();

pageSetup('Квитанция', $book);
xls_comtex_head($sheet, 2);
xls_comtex_head($sheet, 23);
xls_comtex_center($sheet);
xls_comtex_content($sheet, $z, 2,  array(10,12,14,16,18,20,22,24,26,27));
xls_comtex_content($sheet, $z, 23, array(10,11,12,13,14,15,16,17,18,19));
xls_comtex_right($sheet);
xls_comtex_rules($sheet);
xls_comtex_rules($sheet, 23, 31);

header('Content-Type:application/vnd.ms-excel');
header('Content-Disposition:attachment;filename="kvit_'.$z['nomer'].'_'.strftime('%Y-%m-%d').'.xls"');
$writer = PHPExcel_IOFactory::createWriter($book, 'Excel5');
$writer->save('php://output');

exit;


