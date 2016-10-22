<?php
$excel = PHPExcel_IOFactory::load(GLOBAL_DIR.'/view/xls/price.xls');
$excel->setActiveSheetIndex(0);
$aSheet = $excel->getActiveSheet();

// ��������� �������
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
	'�������' => 1,
	'��������� (��������� ������)' => 1,
	'������' => 1,
	'�������� ����������' => 1,
	'�����' => 1,
	'���������� ���������' => 1,
	'��������� SIM-����, ���� ������' => 1,
	'���������, ������ ���������' => 1,
	'��������, ������' => 1,
	'������' => 1,
//	'����������' => 1,
	'���������' => 1,
	'������ �������, ��������' => 1,
	'������� ���������' => 1,
	'������' => 1,
	'������������' => 1,
	'����������' => 1,
	'�������, ��������� �����' => 1,
	'������' => 1,
	'�������' => 1,
	'������� ��� ���������' => 1,
	'������ ������� ��� ���������' => 1,
	'�������' => 1
);

$values = array();
$upd = array();
$avai = array();
$insert = 0; // ��������, ����� �� ������� �������
$cenaRow = 0;
$rowCount = 0; // ������� ���������� ������������ �����
foreach($aSheet->getRowIterator() as $r => $row) {
	if($r < 10) {
		if($cenaRow)
			continue;
		// �����������, � ����� ������� ��������� '�������'
		foreach($row->getCellIterator() as $k => $cell) {
			$v = iconv('utf-8', 'cp1251', $cell->getCalculatedValue());
			if($v == '�������') {
				$cenaRow = $k;
				echo '����� ������� "�������": '.$cenaRow.'<br />';
				break;
			}
		}
		continue;
	}

	if(!$cenaRow)
		die('�� �������� ����� ������� ��� "�������"');

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

echo '���������� �����: '.$rowCount.'<br />';

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
		echo '���������: <b>'.$countAdded.'</b><br />';
}

if(!empty($avai)) {
	query("UPDATE `zp_price` SET `avai`=1,`updated`=CURRENT_TIMESTAMP WHERE `articul` IN (".implode(',', $avai).") AND `org_id`=1");
	echo '�������: '.count($avai).'<br />';
}

if(!empty($upd)) {
	$sql = "INSERT INTO `zp_price_upd` (`price_id`,`row`,`old`,`new`) VALUES ".implode(',', $upd);
	query($sql);
	echo '��������: <b>'.count($upd).'</b><br />';
}

echo '�����: '.round(microtime(true) - TIME, 3);
