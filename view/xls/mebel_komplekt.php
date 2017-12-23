<?php
function styleHead() {//Рамка для заголовка таблицы
	$style = new PHPExcel_Style();
	$style->applyFromArray(array(
		'borders' => array(
			'allborders' => array(
				'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
				'color' => array('rgb' => '444444')
			)
		),
		'font' => array(
			'name' => 'Tahoma',
			'size' => 7,
			'bold' => true
		),
		'alignment' => array(
			'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
			'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
			'wrap' => true
		)
	));
	return $style;
}
function styleContent() {//Рамки для содержимого
	$style = new PHPExcel_Style();
	$style->applyFromArray(array(
		'borders' => array(
			'allborders' => array(
				'style' => PHPExcel_Style_Border::BORDER_THIN,
				'color' => array('rgb' => '777777')
			)
		),
		'font' => array(
			'name' => 'Tahoma',
			'size' => 6
		),
		'alignment' => array(
			'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
		),
		'fill' => array(
			'type' => PHPExcel_Style_Fill::FILL_SOLID
		)
	));
	return $style;
}
function styleResult() {//Рамка для заголовка таблицы
	$style = new PHPExcel_Style();
	$style->applyFromArray(array(
		'borders' => array(
			'allborders' => array(
				'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
				'color' => array('rgb' => '444444')
			)
		),
		'font' => array(
			'name' => 'Tahoma',
			'size' => 9
		),
		'alignment' => array(
			'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
			'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
		),
		'numberformat' => array('code' => '#,#')
	));
	return $style;
}
function reportData($data) {
	$ex = explode(' ', $data);
	$d = explode('-', $ex[0]);
	return $d[2].'.'.$d[1].'.';
}
function freeLine($sheet, $line) {
	$sheet->getStyle('A'.($line + 2).':A'.($line + 2));
}
function pageSetup($book, $title, $zoom=140) {
	$sheet = $book->getActiveSheet();

	//Глобальные стили для ячеек
	$book->getDefaultStyle()->getFont()->setName('Arial')
									   ->setSize(6);

	//Ориентация страницы и  размер листа
	$sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT)
						  ->SetPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);

	//Поля документа
	$sheet->getPageMargins()->setTop(0.2)
							->setRight(0.2)
							->setLeft(0.2)
							->setBottom(0.2);

	//Масштаб страницы
	$sheet->getSheetView()->setZoomScale($zoom);

	//Название страницы
	$sheet->setTitle($title);
}
function colWidth($sheet) {// Установка размеров колонок
	$sheet->getColumnDimension('A')->setWidth(5);   //порядковый номер
	$sheet->getColumnDimension('B')->setWidth(41);  //категория
	$sheet->getColumnDimension('C')->setWidth(65);  //Комплектующее
	$sheet->getColumnDimension('D')->setWidth(11);  //кол-во
	$sheet->getColumnDimension('E')->setWidth(14);  //Цена закупка
	$sheet->getColumnDimension('F')->setWidth(16);  //Сумма
	$sheet->getColumnDimension('G')->setWidth(13);  //текущее наличие
}
function aboutShow($sheet, $line) {
	$sheet->mergeCells('A'.$line.':'.COL_LAST.$line);
	$sheet->setCellValue('A'.$line, 'Список комплектующих по заявкам');
	$sheet->getStyle('A'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$sheet->getStyle('A'.$line)->getFont()->setBold(true)->setSize(10);
	$line++;

	return $line;
}
function headShow($sheet, $line) {// Рисование заголовка
	$sheet->setCellValue('B'.$line, 'Категория')
		->setCellValue('C'.$line, 'Комплектующее')
		->setCellValue('D'.$line, 'Кол-во')
		->setCellValue('E'.$line, "Цена\nзакупка")
		->setCellValue('F'.$line, 'Сумма')
		->setCellValue('G'.$line, "Текущее\nналичие");

	$sheet->setSharedStyle(styleHead(), 'A'.$line.':'.COL_LAST.$line);
	$line++;

	return $line;
}
function contentShow($sheet, $line) {
	$sql = "SELECT `zayav_id`
			FROM `_unit_check`
			WHERE `app_id`=".APP_ID."
			  AND `zayav_id`
			  AND `viewer_id_add`=".VIEWER_ID;
	if(!$zayav_ids = query_ids($sql))
		return $line;

	$sql = "SELECT
				`id`,
				`tovar_id`,
				SUM(`count`) `tovar_count`
			FROM `_zayav_tovar`
			WHERE `zayav_id` IN (".$zayav_ids.")
			GROUP BY `tovar_id`";
	if(!$arr =  query_arr($sql))
		return $line;

	$arr =  _tovarValToList($arr);

	//сортировка по категориям товаров
	$sort = _tovarCategory('all_sort');
	foreach($arr as $id => $r) {
		if(!$i = @$sort[$r['tovar_category_id']])
			$i = 999999998;
		$arr[$id]['sort'] = $i;
	}

	uasort($arr, 'cmp');

	$start = $line;
	$sheet->setSharedStyle(styleContent(), 'A'.$start.':'.COL_LAST.($start + count($arr) - 1));
	$n = 1;
	$sumItog = 0;
	foreach($arr as $r) {
		$sum = _cena($r['tovar_count'] * $r['tovar_sum_buy']);
		$sumItog += $sum;
		$sheet
			->setCellValueByColumnAndRow(0, $line, $n++)
			->setCellValueByColumnAndRow(1, $line, $r['tovar_category_id'] ? utf8(_tovarCategory($r['tovar_category_id'], 'path')) : '')
			->setCellValueByColumnAndRow(2, $line, utf8($r['tovar_name']))
			->setCellValueByColumnAndRow(3, $line, $r['tovar_count'].' '.utf8($r['tovar_measure_name']).' ')
			->setCellValueByColumnAndRow(4, $line, $r['tovar_sum_buy'] ? $r['tovar_sum_buy'].' руб.' : '')
			->setCellValueByColumnAndRow(5, $line, $sum ? $sum.' руб.' : '')
			->setCellValueByColumnAndRow(6, $line, $r['tovar_avai'] ? $r['tovar_avai'].' '.utf8($r['tovar_measure_name']).' ' : '');

		$sheet->getStyle('D'.$start.':D'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$sheet->getStyle('E'.$start.':E'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$sheet->getStyle('F'.$start.':F'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$sheet->getStyle('G'.$start.':G'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

		$line++;
	}

	$sheet->setCellValueByColumnAndRow(5, $line, $sumItog);
	$sheet->getStyle('F'.$line)->getFont()->setBold(true)->setSize(8);

	return $line;
}
function cmp($a, $b) {//функция сортировки для uasort
	if($a['sort'] == $b['sort'])
		return 0;
	return $a['sort'] < $b['sort'] ? -1 : 1;
}

$book = new PHPExcel();

$book->setActiveSheetIndex(0);
$sheet = $book->getActiveSheet();
$line = 1;               // Текущая линия
define('COL_LAST', 'G'); // Последняя колонка

pageSetup($book, 'Заявки');
colWidth($sheet);
$line = aboutShow($sheet, $line);
$line = headShow($sheet, $line);
$line = contentShow($sheet, $line);


header('Content-Type:application/vnd.ms-excel');
header('Content-Disposition:attachment;filename="mebel_komplekt_'.strftime('%Y-%m-%d_%H-%M-%S').'.xls"');
$writer = PHPExcel_IOFactory::createWriter($book, 'Excel5');
$writer->save('php://output');

