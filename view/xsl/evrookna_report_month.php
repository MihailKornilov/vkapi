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
			'size' => 5,
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
			'size' => 6
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
	$sheet->getColumnDimension('A')->setWidth(8);   // дата
	$sheet->getColumnDimension('B')->setWidth(8);   // № дог.
	$sheet->getColumnDimension('C')->setWidth(18);  // Заявка
	$sheet->getColumnDimension('D')->setWidth(35);  // ФИО
	$sheet->getColumnDimension('E')->setWidth(11);  // Начислено
	$sheet->getColumnDimension('F')->setWidth(30);  // № счёта
	$sheet->getColumnDimension('G')->setWidth(10);  // сумма
	$sheet->getColumnDimension('H')->setWidth(11);  // взнос нал. предоплата
	$sheet->getColumnDimension('I')->setWidth(11);  // взнос нал. долг
	$sheet->getColumnDimension('J')->setWidth(35);  // изделия
	$sheet->getColumnDimension('K')->setWidth(9);   // зар.плата дев.
	$sheet->getColumnDimension('L')->setWidth(9);   // зар.плата мал.
	$sheet->getColumnDimension('M')->setWidth(9);   // Замер
}
function aboutShow($sheet, $line) {
	$ex = explode('-', MON);
	$mon = '.'.$ex[1].'.'.$ex[0];

	$sheet->mergeCells('A'.$line.':'.COL_LAST.$line);
	$sheet->setCellValue('A'.$line, 'ОТЧЁТ за период с 01'.$mon.' по '.date('t', strtotime(MON.'-01')).$mon.' г.');
	$sheet->getStyle('A'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$sheet->getStyle('A'.$line)->getFont()->setBold(true);
	$line++;

	$sheet->mergeCells('A'.$line.':'.COL_LAST.$line);
	$sheet->setCellValue('A'.$line, 'маг. "Евроокна"');
	$sheet->getStyle('A'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$sheet->getStyle('A'.$line)->getFont()->setBold(true);
	$line++;

	return $line;
}
function headShow($sheet, $line) {// Рисование заголовка
	//Объединение ячеек в заголовке
	$sheet->mergeCells('A'.$line.':A'.($line + 1))  // дата
		->mergeCells('B'.$line.':B'.($line + 1))    // № дог.
		->mergeCells('C'.$line.':C'.($line + 1))    // Заявка
		->mergeCells('D'.$line.':D'.($line + 1))    // ФИО
		->mergeCells('E'.$line.':E'.($line + 1))    // Начислено
		->mergeCells('F'.$line.':F'.($line + 1))    // № счёта
		->mergeCells('G'.$line.':G'.($line + 1))    // сумма
		->mergeCells('H'.$line.':I'.$line)          // взнос нал.
		->mergeCells('J'.$line.':J'.($line + 1))    // изделия
		->mergeCells('K'.$line.':L'.$line)          // зар.плата
		->mergeCells('M'.$line.':M'.($line + 1));   // Замер

	$sheet->setCellValue('A'.$line, 'дата')
		->setCellValue('B'.$line, '№ дог.')
		->setCellValue('C'.$line, 'Заявка')
		->setCellValue('D'.$line, 'ФИО')
		->setCellValue('E'.$line, 'Начислено')
		->setCellValue('F'.$line, '№ счёта')
		->setCellValue('G'.$line, 'сумма')
		->setCellValue('H'.$line, 'взнос нал.')
		->setCellValue('H'.($line + 1), 'аванс')
		->setCellValue('I'.($line + 1), 'долг')
		->setCellValue('J'.$line, 'изделия')
		->setCellValue('K'.$line, 'зар.плата')
		->setCellValue('K'.($line + 1), 'дев.')
		->setCellValue('L'.($line + 1), 'мал.')
		->setCellValue('M'.$line, 'Замер');

	$sheet->setSharedStyle(styleHead(), 'A'.$line.':'.COL_LAST.($line + 1));
	$line += 2;

	return $line;
}
function contentShow($sheet, $line) {
	$sql = "SELECT
				*,
				'' `invoice_nomer`,
				'' `invoice_sum`,
				'' `zp_women`,
				'' `zp_men`,
				0 `zamer_sum`,
				'' `zamer_worker`
	        FROM `_zayav`
	        WHERE `app_id`=".APP_ID."
			  AND !`deleted`
	          AND `dtime_add` LIKE '".MON."-%'
	        ORDER BY `id`";
	if(!$zayav = query_arr($sql, GLOBAL_MYSQL_CONNECT))
		return $line;

	$zayav = _clientValToList($zayav);
	$zayav = _dogovorValToList($zayav);
	$zayav = _zayavTovarValToList($zayav);

	//Номер договора и сумма. Берутся из расходов по заявке.
	$sql = "SELECT *
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `zayav_id` IN ("._idsGet($zayav).")";
	$ze = query_arr($sql, GLOBAL_MYSQL_CONNECT);
	$ze = _attachValToList($ze);
	foreach($ze as $r) {
		$r['sum'] = _cena($r['sum']);
		switch(_zayavExpense($r['category_id'], 'dop')) {
			case 4:
				$zayav[$r['zayav_id']]['invoice_nomer'] = utf8($r['attach_id'] ? $r['attach_name'] : $r['txt']);
				$zayav[$r['zayav_id']]['invoice_sum'] = $r['sum'];
				break;
			case 2:
				if($r['worker_id'])
					$zayav[$r['zayav_id']]['zp_'.(_viewer($r['worker_id'], 'viewer_sex') == 1 ? 'wo' : '').'men'] += $r['sum'];
				break;
		}
		if($r['category_id'] == 10) {
			$zayav[$r['zayav_id']]['zamer_sum'] = $r['sum'];
			$zayav[$r['zayav_id']]['zamer_worker'] = utf8(_viewer($r['worker_id'], 'viewer_name'));
		}
	}

	$start = $line;
	$sheet->setSharedStyle(styleContent(), 'A'.$start.':'.COL_LAST.($start + count($zayav)));
	foreach($zayav as $r) {
		$sheet
			->setCellValueByColumnAndRow(0, $line, reportData($r['dtime_add']))
			->setCellValueByColumnAndRow(1, $line, $r['dogovor_id'] ? $r['dogovor_n'] : '')
			->setCellValueByColumnAndRow(2, $line, utf8($r['name']))
			->setCellValueByColumnAndRow(3, $line, utf8(htmlspecialchars_decode($r['client_name'])))
			->setCellValueByColumnAndRow(4, $line, $r['sum_accrual'])
			->setCellValueByColumnAndRow(5, $line, $r['invoice_nomer'])
			->setCellValueByColumnAndRow(6, $line, $r['invoice_sum'])
			->setCellValueByColumnAndRow(7, $line, $r['sum_pay'])
			->setCellValueByColumnAndRow(8, $line, $r['sum_dolg'] < 0 ? $r['sum_dolg'] : '')
			->setCellValueByColumnAndRow(9, $line, utf8($r['tovar_report']))
			->setCellValueByColumnAndRow(10, $line, $r['zp_women'])
			->setCellValueByColumnAndRow(11, $line, $r['zp_men'])
			->setCellValueByColumnAndRow(12, $line, $r['zamer_sum'])
			->setCellValueByColumnAndRow(13, $line, $r['zamer_worker']);

		$sheet->getStyle('A'.$line.':A'.$line)->getFill()->getStartColor()->setRGB(_zayavStatus($r['status_id'], 'color'));
		$sheet->getCellByColumnAndRow(0, $line)->getHyperlink()->setUrl((LOCAL ? 'http://nyandoma'.URL.'&p=zayav&d=info&&id=' : APP_URL.'#zayav_').$r['id']);         //Вставка ссылки для даты на заявку
		$sheet->getCellByColumnAndRow(3, $line)->getHyperlink()->setUrl((LOCAL ? 'http://nyandoma'.URL.'&p=client&d=info&&id=' : APP_URL.'#client_').$r['client_id']);//Вставка ссылки для клиента

		$line++;
	}

	//Стили для колонок содержимого
	$sheet->setSharedStyle(styleResult(), 'A'.$line.':'.COL_LAST.$line);
	$sheet->getStyle('A'.$start.':A'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$sheet->getStyle('A'.$start.':A'.$line)->getFont()->getColor()->setRGB('000088');
	$sheet->getStyle('B'.$start.':B'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//	$sheet->getStyle('C'.$start.':C'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$sheet->getStyle('D'.$start.':D'.$line)->getFont()->getColor()->setRGB('000088');
	$sheet->getStyle('E'.$start.':E'.$line)->getNumberFormat()->setFormatCode('#,#');
	$sheet->getStyle('E'.$start.':E'.$line)->getFont()->getColor()->setRGB('0077aa');
//	$sheet->getStyle('F'.$start.':F'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setWrapText(true);
	$sheet->getStyle('G'.$start.':G'.$line)->getNumberFormat()->setFormatCode('#,#');
	$sheet->getStyle('H'.$start.':H'.$line)->getNumberFormat()->setFormatCode('#,#');
	$sheet->getStyle('H'.$start.':H'.$line)->getFont()->getColor()->setRGB('229922');
	$sheet->getStyle('I'.$start.':I'.$line)->getNumberFormat()->setFormatCode('#,#');
	$sheet->getStyle('I'.$start.':I'.$line)->getFont()->getColor()->setRGB('dd0000');
	$sheet->getStyle('J'.$start.':J'.$line)->getAlignment()->setWrapText(true);
	$sheet->getStyle('K'.$start.':K'.$line)->getNumberFormat()->setFormatCode('#,#');
	$sheet->getStyle('L'.$start.':L'.$line)->getNumberFormat()->setFormatCode('#,#');
	$sheet->getStyle('M'.$start.':M'.$line)->getNumberFormat()->setFormatCode('#,#');

	$sheet->setCellValue('E'.$line, '=SUM(E'.$start.':E'.($line - 1).')');
	$sheet->setCellValue('G'.$line, '=SUM(G'.$start.':G'.($line - 1).')');
	$sheet->setCellValue('H'.$line, '=SUM(H'.$start.':H'.($line - 1).')');
	$sheet->setCellValue('I'.$line, '=SUM(I'.$start.':I'.($line - 1).')');
	$sheet->setCellValue('K'.$line, '=SUM(K'.$start.':K'.($line - 1).')');
	$sheet->setCellValue('L'.$line, '=SUM(L'.$start.':L'.($line - 1).')');
	$sheet->setCellValue('M'.$line, '=SUM(M'.$start.':M'.($line - 1).')');

	//$sheet->getStyle('A'.$line.':'.COL_LAST.$line)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
	freeLine($sheet, $line);

	return $line;
}


$mon = explode('-', @$_GET['mon']);
if(count($mon) > 1)
	$mon = $mon[0].'-'.$mon[1];
else
	$mon = strftime('%Y-%m');

define('MON', $mon);
$ex = explode('-', MON);
define('MONTH', _monthDef($ex[1]).' '.$ex[0]);
define('MON_FULL', utf8(_monthFull($ex[1])));
define('YEAR', $ex[0]);


$book = new PHPExcel();

$book->setActiveSheetIndex(0);
$sheet = $book->getActiveSheet();
$line = 1;      // Текущая линия
define('COL_LAST', 'M'); // Последняя колонка
$index = 1;     // Номер создаваемой страницы

pageSetup($book, 'Заявки');
colWidth($sheet);
$line = aboutShow($sheet, $line);
$line = headShow($sheet, $line);
$line = contentShow($sheet, $line);


header('Content-Type:application/vnd.ms-excel');
header('Content-Disposition:attachment;filename="report_month_'.strftime('%Y-%m-%d_%H-%M-%S').'.xls"');
$writer = PHPExcel_IOFactory::createWriter($book, 'Excel5');
$writer->save('php://output');

