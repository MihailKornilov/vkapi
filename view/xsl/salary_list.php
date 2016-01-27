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
			'size' => 9,
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
			'size' => 11
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
function stylePodpis() {//Рамки для содержимого
	$style = new PHPExcel_Style();
	$style->applyFromArray(array(
		'borders' => array(
			'bottom' => array(
				'style' => PHPExcel_Style_Border::BORDER_THIN
			)
		),
		'font' => array(
			'name' => 'Tahoma',
			'size' => 11
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
function pageSetup($book, $title) {
	$sheet = $book->getActiveSheet();

	//Глобальные стили для ячеек
	$book->getDefaultStyle()->getFont()->setName('Arial')
		->setSize(11);

	//Ориентация страницы и  размер листа
	$sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT)
		->SetPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);

	//Поля документа
	$sheet->getPageMargins()
		->setTop(0.2)
		->setRight(0.2)
		->setLeft(0.2)
		->setBottom(0.2);

	//Масштаб страницы
	$sheet->getSheetView()->setZoomScale(90);

	//Название страницы
	$sheet->setTitle($title);
}
function zpPrint($sheet, $list) {
	$line = 1;

	$sheet->setCellValue('A'.$line, 'Лист выдачи зп: '.utf8(_monthDef(LIST_MON)).' '.LIST_YEAR);
	$sheet->mergeCells('A'.$line.':F'.$line);
	$sheet->getStyle('A'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$sheet->getStyle('A'.$line)->getFont()->setBold(true);
	$line++;

	$sheet->getColumnDimension('A')->setWidth(9);
	$sheet->getColumnDimension('B')->setWidth(22);
	$sheet->getColumnDimension('C')->setWidth(20);
	$sheet->getColumnDimension('D')->setWidth(11);
	$sheet->getColumnDimension('E')->setWidth(8);
	$sheet->getColumnDimension('F')->setWidth(21);

	$sheet->setCellValue('A'.$line, WORKER_NAME);
	$sheet->setCellValue('F'.$line, 'Дата: '._dataDog($list['dtime_add']));
	$sheet->getStyle('F'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$sheet->getStyle('A'.$line.':F'.$line)->getFont()->setBold(true);
	$line += 2;

	$sheet->setCellValue('A'.$line, '№ дог.');
	$sheet->setCellValue('B'.$line, 'Адрес');
	$sheet->setCellValue('C'.$line, 'Заявка');
	$sheet->setCellValue('D'.$line, 'Дата вып.');
	$sheet->setCellValue('E'.$line, 'Сумма');
	$sheet->setCellValue('F'.$line, 'Примечание');
	$sheet->setSharedStyle(styleHead(), 'A'.$line.':F'.$line);
	$line++;

	$sql = "SELECT
				`id`,
				`sum`,
				`about`,
				0 `category_id`,
				0 `zayav_id`,
				`dtime_add`
			FROM `_salary_accrual`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `salary_list_id`=".LIST_ID;
	$accrual = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	$sql = "SELECT
				`id`,
				`sum`,
				'' `about`,
				`category_id`,
				`zayav_id`,
				`dtime_add`
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `salary_list_id`=".LIST_ID;
	$zayav = query_arr($sql, GLOBAL_MYSQL_CONNECT);

	$spisok = _arrayTimeGroup($accrual);
	$spisok += _arrayTimeGroup($zayav, $spisok);

	$spisok = _zayavValToList($spisok);
	$spisok = _dogovorValToList($spisok);


	krsort($spisok);
	$start = $line;
	$sum = 0;
	foreach($spisok as $r) {
		$sheet->setCellValue('A'.$line, $r['zayav_id'] && $r['dogovor_id'] ? $r['dogovor_n'] : '');
		$sheet->setCellValue('B'.$line, $r['zayav_id'] ? utf8(htmlspecialchars_decode($r['zayav_adres'])) : '');
		$sheet->setCellValue('C'.$line, $r['zayav_id'] ? utf8(htmlspecialchars_decode($r['zayav_name'])) : '');
//		$sheet->getStyle('C'.$line.':C'.$line)->getFill()->getStartColor()->setRGB('4444FF');
		$sheet->getCellByColumnAndRow(2, $line)->getHyperlink()->setUrl((LOCAL ? 'http://'.DOMAIN.URL.'&p=zayav&d=info&&id=' : APP_URL.'#zayav_').$r['zayav_id']); //Вставка ссылки на заявку
		$sheet->setCellValue('D'.$line, $r['zayav_id'] && $r['zayav_status_day'] != '0000-00-00' ? _dataDog($r['zayav_status_day']) : '');
		$sheet->setCellValue('E'.$line, $r['sum']);
		$sheet->setCellValue('F'.$line, utf8($r['category_id'] ? _zayavExpense($r['category_id']) : $r['about']));
		$line++;
		$sum += $r['sum'];
	}




	//рисование рамки
	$sheet->setSharedStyle(styleContent(), 'A'.$start.':F'.($line + 1));

	//Выравнивание влево договоров
	$sheet->getStyle('A'.$start.':A'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

	//Выравнивание вправо дат
	$sheet->getStyle('D'.$start.':D'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

	$sheet->mergeCells('A'.$line.':D'.$line);
	$sheet->setCellValue('A'.$line, 'Всего начислено:');
	$sheet->getStyle('A'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$sheet->setCellValue('E'.$line, $sum);
	$line += 2;

	//Вычеты
	$sql = "SELECT
				`id`,
				`sum`,
				`about`
			FROM `_salary_deduct`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND `salary_list_id`=".LIST_ID;
	if($deduct = query_arr($sql, GLOBAL_MYSQL_CONNECT)) {
		$sheet->setCellValue('A'.$line, 'Вычеты:');

		//рисование рамки
		$sheet->setSharedStyle(styleContent(), 'A'.$line.':F'.($line + count($deduct)));

		foreach($deduct as $r) {
			$sheet->mergeCells('A'.$line.':D'.$line);
			$sheet->getStyle('A'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
			$sheet->setCellValue('E'.$line, $r['sum']);
			$sheet->setCellValue('F'.$line, utf8($r['about']));
			$sum -= $r['sum'];
			$line++;
		}
		$line++;
	}

	//Выдан аванс
	$sql = "SELECT *
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND `ws_id`=".WS_ID."
			  AND !`deleted`
			  AND `salary_avans`
			  AND `salary_list_id`=".LIST_ID."
			ORDER BY `id` DESC";
	if($avans = query_arr($sql, GLOBAL_MYSQL_CONNECT)) {
		$sheet->setCellValue('A'.$line, 'Выдан аванс:');

		//рисование рамки
		$sheet->setSharedStyle(styleContent(), 'A'.$line.':F'.($line + count($avans)));

		foreach($avans as $r) {
			$sheet->mergeCells('A'.$line.':D'.$line);
			$sheet->getStyle('A'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
			$sheet->setCellValue('E'.$line, $r['sum']);
			$sheet->setCellValue('F'.$line, utf8($r['about']));
			$line++;
			$sum -= $r['sum'];
		}
		$line++;
	}

	//рисование рамки
	$sheet->setSharedStyle(styleContent(), 'A'.$line.':F'.$line);


	$sheet->mergeCells('A'.$line.':D'.$line);
	$sheet->setCellValue('A'.$line, 'Итого к выдаче:');
	$sheet->getStyle('A'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$sheet->getStyle('C'.$start.':C'.$line)->getAlignment()->setWrapText(true);
	$sheet->setCellValue('E'.$line, $sum);
	$sheet->getStyle('F'.$start.':F'.$line)->getAlignment()->setWrapText(true);

	$line += 2;

	$sheet->setSharedStyle(stylePodpis(), 'A'.$line.':F'.($line + 2));
	$sheet->getRowDimension($line)->setRowHeight(21);
	$sheet->setCellValue('A'.$line, 'Утвердил:');
	$sheet->setCellValue('F'.$line, 'Губинский Р.Е.');
	$sheet->getStyle('F'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$line++;
	$sheet->getRowDimension($line)->setRowHeight(21);
	$sheet->setCellValue('A'.$line, 'Выдал:');
	$sheet->setCellValue('F'.$line, 'Губинская В.В.');
	$sheet->getStyle('F'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$line++;
	$sheet->getRowDimension($line)->setRowHeight(21);
	$sheet->setCellValue('A'.$line, 'Получил:');
	$sheet->setCellValue('F'.$line, WORKER_NAME);
	$sheet->getStyle('F'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

	$sheet->getStyle('B'.$line)->getFont()->setBold(true);

}

define('LIST_ID', _num(@$_GET['id']));

if(!LIST_ID)
	die(win1251('Некорректный id листа выдачи.'));

$sql = "SELECT *
		FROM `_salary_list`
		WHERE `app_id`=".APP_ID."
		  AND `ws_id`=".WS_ID."
		  AND `id`=".LIST_ID;
if(!$r = query_assoc($sql, GLOBAL_MYSQL_CONNECT))
	die(win1251('Листа выдачи не существует.'));

define('WORKER_ID', _num($r['worker_id']));
define('WORKER_NAME', utf8(_viewer(WORKER_ID, 'viewer_name_init')));
define('LIST_YEAR', _num($r['year']));
define('LIST_MON', _num($r['mon']));


$book = new PHPExcel();
$book->setActiveSheetIndex(0);
$sheet = $book->getActiveSheet();

pageSetup($book, 'Лист зп');
zpPrint($sheet, $r);

header('Content-Type:application/vnd.ms-excel');
header('Content-Disposition:attachment;filename="'.time().'_salary_list.xls"');
$writer = PHPExcel_IOFactory::createWriter($book, 'Excel5');
$writer->save('php://output');

exit;
