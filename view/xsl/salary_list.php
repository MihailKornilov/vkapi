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
	$pole = explode(',', _app('salary_list_setup'));
	$buk = 'ABCDEFGH';
	$width = array(7,25,7,15,12,8,20,16);
	$head = salary_list_head();

	$n = 0;
	foreach($pole as $id) {
		if($id == 6)
			break;
		$n++;
	}
	define('ITOGBUK', $buk[$n - 1]);//буква где стоит итог
	define('SUMBUK', $buk[$n]);//буква где стоит сумма

	define('COLLAST', $buk[count($pole) - 1]);//буква последней колонки
	define('ABOUTBUK', $buk[count($pole)]);//буква - описание вычетов и авансов

	$line = 1;

	$sheet->setCellValue('A'.$line, LIST_VYDACI.' №'.$list['nomer'].': '.utf8(_monthDef(LIST_MON)).' '.LIST_YEAR);
	$sheet->mergeCells('A'.$line.':'.COLLAST.$line);
	$sheet->getStyle('A'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$sheet->getStyle('A'.$line)->getFont()->setBold(true);
	$line++;

	$n = 0;
	foreach($pole as $id)
		$sheet->getColumnDimension($buk[$n++])->setWidth($width[$id - 1]);

	//колонка с описанием вычетов и авансов
	$sheet->getColumnDimension($buk[count($pole)])->setWidth(15);

	$sheet->setCellValue('A'.$line, WORKER_NAME);
	$sheet->setCellValue(COLLAST.$line, 'Дата: '._dataDog($list['dtime_add']));
	$sheet->getStyle(COLLAST.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$sheet->getStyle('A'.$line.':'.COLLAST.$line)->getFont()->setBold(true);
	$line += 2;

	$n = 0;
	foreach($pole as $id)
		$sheet->setCellValue($buk[$n++].$line, utf8($head[$id]));

	$sheet->setSharedStyle(styleHead(), 'A'.$line.':'.COLLAST.$line);
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
			  AND `salary_list_id`=".LIST_ID;
	$accrual = query_arr($sql);

	$sql = "SELECT
				`id`,
				`sum`,
				'' `about`,
				`category_id`,
				`zayav_id`,
				`dtime_add`
			FROM `_zayav_expense`
			WHERE `app_id`=".APP_ID."
			  AND `salary_list_id`=".LIST_ID;
	$zayav = query_arr($sql);

	$spisok = _arrayTimeGroup($accrual);
	$spisok += _arrayTimeGroup($zayav, $spisok);

	$spisok = _zayavValToList($spisok);
	$spisok = _dogovorValToList($spisok);


	krsort($spisok);
	$start = $line;
	$sum = 0;
	foreach($spisok as $r) {
		$txt = array(
			$r['zayav_id'] ? $r['zayav_n'] : '',
			$r['zayav_id'] ? htmlspecialchars_decode($r['zayav_name']) : '',
			$r['zayav_id'] && $r['dogovor_id'] ? $r['dogovor_n'] : '',
			$r['zayav_id'] ? htmlspecialchars_decode($r['zayav_adres']) : '',
			$r['zayav_id'] && $r['zayav_status_day'] != '0000-00-00' ? _dataDog($r['zayav_status_day']) : '',
			$r['sum'],
			$r['category_id'] ? _zayavExpense($r['category_id']) : $r['about'],
			FullDataTime($r['dtime_add'], 0, 1)
		);

		$n = 0;
		foreach($pole as $id)
			$sheet->setCellValue($buk[$n++].$line, utf8($txt[$id - 1]));

//		$sheet->getStyle('C'.$line.':C'.$line)->getFill()->getStartColor()->setRGB('4444FF');
//		$sheet->getCellByColumnAndRow(2, $line)->getHyperlink()->setUrl((LOCAL ? 'http://'.DOMAIN.URL.'&p=zayav&d=info&&id=' : APP_URL.'#zayav_').$r['zayav_id']); //Вставка ссылки на заявку

		$line++;
		$sum += $r['sum'];
	}




	//рисование рамки
	$sheet->setSharedStyle(styleContent(), 'A'.$start.':'.COLLAST.($line + 1));

	//Выравнивание влево договоров
//	$sheet->getStyle('A'.$start.':A'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

	//Выравнивание вправо дат
//	$sheet->getStyle('D'.$start.':D'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

	$sheet->mergeCells('A'.$line.':'.ITOGBUK.$line);
	$sheet->setCellValue('A'.$line, 'Всего начислено:');
	$sheet->getStyle('A'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$sheet->setCellValue(SUMBUK.$line, $sum);
	$line += 2;

	//Вычеты
	$sql = "SELECT
				`id`,
				`sum`,
				`about`
			FROM `_salary_deduct`
			WHERE `app_id`=".APP_ID."
			  AND `salary_list_id`=".LIST_ID;
	if($deduct = query_arr($sql)) {
		$sheet->setCellValue('A'.$line, 'Вычеты:');

		//рисование рамки
		$sheet->setSharedStyle(styleContent(), 'A'.$line.':'.COLLAST.($line + count($deduct)));

		foreach($deduct as $r) {
			$sheet->mergeCells('A'.$line.':'.ITOGBUK.$line);
			$sheet->getStyle('A'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
			$sheet->setCellValue(SUMBUK.$line, $r['sum']);
			$sheet->setCellValue(ABOUTBUK.$line, utf8($r['about']));
			$sum -= $r['sum'];
			$line++;
		}
		$line++;
	}

	//Выдан аванс
	$sql = "SELECT *
			FROM `_money_expense`
			WHERE `app_id`=".APP_ID."
			  AND !`deleted`
			  AND `salary_avans`
			  AND `salary_list_id`=".LIST_ID."
			ORDER BY `id` DESC";
	if($avans = query_arr($sql)) {
		$sheet->setCellValue('A'.$line, 'Выдан аванс:');

		//рисование рамки
		$sheet->setSharedStyle(styleContent(), 'A'.$line.':'.COLLAST.($line + count($avans)));

		foreach($avans as $r) {
			$sheet->mergeCells('A'.$line.':'.ITOGBUK.$line);
			$sheet->getStyle('A'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
			$sheet->setCellValue(SUMBUK.$line, $r['sum']);
			$sheet->setCellValue(ABOUTBUK.$line, utf8($r['about']));
			$line++;
			$sum -= $r['sum'];
		}
		$line++;
	}

	//рисование рамки
	$sheet->setSharedStyle(styleContent(), 'A'.$line.':'.COLLAST.$line);


	$sheet->mergeCells('A'.$line.':'.ITOGBUK.$line);
	$sheet->setCellValue('A'.$line, 'Итого к выдаче:');
	$sheet->getStyle('A'.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$sheet->getStyle('C'.$start.':C'.$line)->getAlignment()->setWrapText(true);
	$sheet->setCellValue(SUMBUK.$line, $sum);
	$sheet->getStyle(COLLAST.$start.':'.COLLAST.$line)->getAlignment()->setWrapText(true);
	$sheet->getStyle(ABOUTBUK.$start.':'.ABOUTBUK.$line)->getAlignment()->setWrapText(true);

	$line += 2;

	$sheet->setSharedStyle(stylePodpis(), 'A'.$line.':'.COLLAST.($line + 2));
	$sheet->getRowDimension($line)->setRowHeight(21);
	$sheet->setCellValue('A'.$line, 'Утвердил:');
	$sheet->setCellValue(COLLAST.$line, '');
	$sheet->getStyle(COLLAST.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$line++;
	$sheet->getRowDimension($line)->setRowHeight(21);
	$sheet->setCellValue('A'.$line, 'Выдал:');
	$sheet->setCellValue(COLLAST.$line, '');
	$sheet->getStyle(COLLAST.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
	$line++;
	$sheet->getRowDimension($line)->setRowHeight(21);
	$sheet->setCellValue('A'.$line, 'Получил:');
	$sheet->setCellValue(COLLAST.$line, WORKER_NAME);
	$sheet->getStyle(COLLAST.$line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

	$sheet->getStyle('B'.$line)->getFont()->setBold(true);

}

define('LIST_ID', _num(@$_GET['id']));

if(!LIST_ID)
	die(win1251('Некорректный id листа выдачи.'));

$sql = "SELECT *
		FROM `_salary_list`
		WHERE `app_id`=".APP_ID."
		  AND `id`=".LIST_ID;
if(!$r = query_assoc($sql))
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
