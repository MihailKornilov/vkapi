<?php
switch(@$_POST['op']) {
	case 'kupezz_ob_spisok':
		$_POST['find'] = win1251(@$_POST['find']);
		$data = kupezz_ob_spisok($_POST);
		if($data['filter']['page'] == 1)
			$send['result'] = utf8($data['result']);
		$send['spisok'] = utf8($data['spisok']);
		jsonSuccess($send);
		break;
}