<?php
define('DOCUMENT_ROOT', dirname(dirname(__FILE__)).'/mobile');
require_once(DOCUMENT_ROOT.'/syncro.php');
require_once(DOCUMENT_ROOT.'/view/main.php');
require_once 'view/_vk.php';
require_once(DOCUMENT_ROOT.'/view/ws.php');
require_once(DOCUMENT_ROOT.'/view/ws_zp.php');
require_once(DOCUMENT_ROOT.'/view/ws_report.php');
require_once(DOCUMENT_ROOT.'/view/ws_setup.php');
require_once 'view/_nofunc.php';



//�������� ������ ����� � IE ����� �����
header('P3P: CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');

_dbConnect('GLOBAL_');  //����������� � ���� ������
_dbConnect();  //����������� � ���� mobile
_const();               //��������� �������� ��������
_appAuth();             //��������� ������ � ����������, �������� �����������
_getVkUser();           //��������� ������ � ������������, �������� � ����, ���� ���, ���������� ���� �������
_ws();
_setup_global();
_pinCheck();
_hashRead();

//���������� ��������� ��� ���������� �����������
$sql = "SELECT * FROM `setup` WHERE `ws_id`=".WS_ID." LIMIT 1";
$setup = query_assoc($sql);
define('WS_DEVS', $setup['devs']);
define('WS_TYPE', $setup['ws_type_id']);


$html = _header();
$html .= _menu();
$html .= _global_index();


switch($_GET['p']) {
	case 'zp':
		switch(@$_GET['d']) {
			case 'info':
				if(!preg_match(REGEXP_NUMERIC, $_GET['id'])) {
					$html .= '�������� �� ����������';
					break;
				}
				$html .= zp_info(intval($_GET['id']));
				break;
			default:
				$v = array();
				if(HASH_VALUES) {
					$ex = explode('.', HASH_VALUES);
					foreach($ex as $r) {
						$arr = explode('=', $r);
						$v[$arr[0]] = $arr[1];
					}
				} else
					foreach($_COOKIE as $k => $val) {
						$arr = explode(VIEWER_ID.'_zp_', $k);
						if(isset($arr[1]))
							$v[$arr[1]] = $val;
					}

				$v = zpfilter($v);
				$v['find'] = unescape(@$v['find']);
				$html .= zp_list($v);
		}
		break;

	case 'sa':
		if(!SA)
			header('Location:'.URL.'&p=zayav');
		switch(@$_GET['d']) {
			case 'user': $html .= sa_user(); break;
			case 'ws':
				if(isset($_GET['id']) && preg_match(REGEXP_NUMERIC, $_GET['id'])) {
					$html .= sa_ws_info(intval($_GET['id']));
					break;
				}
				$html .= sa_ws();
				break;
			case 'tovar_category': $html .= sa_tovar_category(); break;
			case 'device': $html .= sa_device(); break;
			case 'vendor': $html .= sa_vendor(); break;
			case 'model': $html .= sa_model(); break;
			case 'equip': $html .= sa_equip(); break;
			case 'fault': $html .= sa_fault(); break;
			case 'zpname': $html .= sa_zpname(); break;
		}
		break;
}


$html .= _footer();

die($html);
