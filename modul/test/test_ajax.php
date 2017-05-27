<?php
switch(@$_POST['op']) {
	case 'test_book_update':
		if(!$name = _txt($_POST['name']))
			jsonError('Не указано имя файла');
			
		if(!test_word_insert($name))
			jsonError('Файла не существует');
		
		jsonSuccess();
		break;
	case 'test_word_find'://поиск слова в книге
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT `word`
				FROM `test_word_book`
				WHERE `id`=".$id;
		if(!$word = query_value($sql))
			jsonError('Слова нет в книге');

		$sql = "SELECT `content`
				FROM `test_book`
				LIMIT 1";
		$book = query_value($sql);
		$pos = stripos($book, $word);
		$start = _num($pos - 300);
		$end = $start + 300;
		$str = substr($book, $start, 600);

		$reg = '/('.$word.')/iu';
		$str = preg_replace($reg, '<span class="fs15 fndd">\\1</span>', $str, 1);

		$send['str'] =
			($start ? '...' : '')
			._br($str)
			.($end < strlen($book) - $start ? '...' : '');
		
		jsonSuccess($send);
		break;
	case 'test_word_save'://сохранение слова в словарь
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `test_word_book`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "SELECT COUNT(*)
				FROM `test_word_save`
				WHERE `word`='".addslashes($r['word'])."'";
		if(query_value($sql))
			jsonError();

		$sql = "INSERT INTO `test_word_save` (
					`word`
				) VALUES (
					'".addslashes($r['word'])."'
				)";
		query($sql);

		jsonSuccess();
		break;
	case 'test_word_del'://удаление слова из книги
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `test_word_book`
				WHERE `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "SELECT COUNT(*)
				FROM `test_word_save`
				WHERE `word`='".addslashes($r['word'])."'";
		if(query_value($sql))
			jsonError();

		$sql = "DELETE FROM `test_word_book`
				WHERE `word`='".addslashes($r['word'])."'";
		query($sql);

		jsonSuccess();
		break;

	case 'dialog_edit_load':
		$dialog = array(
			'button_submit' => 'Внести',
			'button_cancel' => 'Отмена'
		);

		if($dialog_id = _num($_POST['dialog_id'])) {
			$sql = "SELECT *
					FROM `_dialog`
					WHERE `app_id`=".APP_ID."
					  AND `id`=".$dialog_id;
			$dialog = query_assoc($sql);
		}

		$html =
			'<div class="pad10 line-b1">'.
				'<dl id="dialog-base" class="_sort pad5" val="_dialog_element">'.
					_dialogElementSpisok($dialog_id, 'html_edit').
				'</dl>'.
			'</div>'.

			'<div id="dialog-but" class="pad20 center bg-ffd">'.
				'<button class="vk small green" onclick="_dialogEditElement()">Добавить элемент</button>'.
  ($dialog_id ? '<button class="vk small ml5" onclick="_dialogEditWidth()">Изменить ширину окна</button>' : '').

				'<table class="bs5 mt20">'.
					'<tr><td class="label">Текст кнопки применения:<td><input type="text" id="button_submit" class="w230" maxlength="100" value="'.$dialog['button_submit'].'" />'.
					'<tr><td class="label r">Текст кнопки отмены:<td><input type="text" id="button_cancel" class="w230" maxlength="100" value="'.$dialog['button_cancel'].'" />'.
				'</table>'.

			'</div>'.

			'<div id="dialog-width" class="pad20 bg-ffd dn">'.
				'<div class="ml20 fs14">Допустимые значения ширины: от <b>480</b> до <b>780</b> пикселей.</div>'.
				'<table class="bs5 mt10">'.
					'<tr><td class="label w100 r">Ширина окна:'.
						'<td><input type="text" id="dialog-width-inp" class="w50 r" value="'._num(@$dialog['width']).'" /> px'.
						'<td><button id="dialog-width-ok" class="vk small ml10 mr5">Применить</button>'.
							'<button id="dialog-width-cancel" class="vk small grey">Отмена</button>'.
				'</table>'.
			'</div>'
			;

		$send['width'] = $dialog_id ? _num($dialog['width']) : 500;
		$send['head'] = utf8(@$dialog['head']);
		$send['button_submit'] = utf8($dialog['button_submit']);
		$send['button_cancel'] = utf8($dialog['button_cancel']);
		$send['element'] = _dialogElementSpisok($dialog_id, 'arr');
		$send['html'] = utf8($html);
		jsonSuccess($send);
		break;
	case 'dialog_add'://создание нового диалогового окна
		if(!$head = _txt($_POST['head']))
			jsonError('Не указано название диалога');
		if(!$button_cancel = _txt($_POST['button_cancel']))
			jsonError('Не указан текст кнопки отмены');

		$button_submit = _txt($_POST['button_submit']);
		
		
		_dialogElementUpdate();

		$sql = "INSERT INTO `_dialog` (
					`app_id`,
					`head`,
					`button_submit`,
					`button_cancel`
				) VALUES (
					".APP_ID.",
					'".addslashes($head)."',
					'".addslashes($button_submit)."',
					'".addslashes($button_cancel)."'
				)";
		query($sql);

		$dialog_id = query_insert_id('_dialog');

		_dialogElementUpdate($dialog_id);

		$send['dialog_id'] = $dialog_id;
		jsonSuccess($send);
		break;
	case 'dialog_edit'://сохранение диалогового окна
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('Некорректный ID диалогового окна');
		if(!$head = _txt($_POST['head']))
			jsonError('Не указано название диалога');
		if(!$button_cancel = _txt($_POST['button_cancel']))
			jsonError('Не указан текст кнопки отмены');

		$button_submit = _txt($_POST['button_submit']);

		_dialogElementUpdate();

		$sql = "SELECT COUNT(*)
				FROM `_dialog`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$dialog_id;
		if(!query_value($sql))
			jsonError('Диалога не существует');

		$sql = "UPDATE `_dialog`
				SET `head`='".addslashes($head)."',
					`button_submit`='".addslashes($button_submit)."',
					`button_cancel`='".addslashes($button_cancel)."'
				WHERE `id`=".$dialog_id;
		query($sql);

		_dialogElementUpdate($dialog_id);

		$send['dialog_id'] = $dialog_id;
		jsonSuccess($send);
		break;
	case 'dialog_width_set'://установка ширины диалога
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('Некорректный ID диалогового окна');
		if(!$width = _num($_POST['width']))
			jsonError('Указана некорректная ширина');

		if($width < 480 || $width > 780)
			jsonError('Указана недопустимая ширина');

		$sql = "SELECT *
				FROM `_dialog`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$dialog_id;
		if(!$dialog = query_assoc($sql))
			jsonError('Диалога не существует');

		$sql = "UPDATE `_dialog`
				SET `width`=".$width."
				WHERE `id`=".$dialog_id;
		query($sql);

		jsonSuccess();
		break;

	case 'dialog_open_load'://получение данных для диалогового окна
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('Некорректный ID диалогового окна');

		$sql = "SELECT *
				FROM `_dialog`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$dialog_id;
		if(!$dialog = query_assoc($sql))
			jsonError('Диалога не существует');

		$html = '<table class="bs10">'._dialogElementSpisok($dialog_id, 'html').'</table>';

		$send['width'] = _num($dialog['width']);
		$send['head'] = utf8($dialog['head']);
		$send['button_submit'] = utf8($dialog['button_submit']);
		$send['button_cancel'] = utf8($dialog['button_cancel']);
		$send['element'] = _dialogElementSpisok($dialog_id, 'arr');
		$send['html'] = utf8($html);
		jsonSuccess($send);
		break;
	
	case 'spisok_add'://внесение данных диалога в _spisok
		if(!$dialog_id = _num($_POST['dialog_id']))
			jsonError('Некорректный ID диалогового окна');

		_dialogSpisokUpdate($dialog_id);

		jsonSuccess();
		break;
	case 'spisok_edit_load'://получение данных записи для диалога
		if(!$id = _num($_POST['id']))
			jsonError('Некорректный идентификатор');

		$sql = "SELECT *
				FROM `_spisok`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('Записи не существует');

		if($r['deleted'])
			jsonError('Запись была удалена');

		$sql = "SELECT *
				FROM `_dialog`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$r['dialog_id'];
		if(!$dialog = query_assoc($sql))
			jsonError('Диалога не существует');

		$html = '<table class="bs10">'._dialogElementSpisok($r['dialog_id'], 'html', $r).'</table>';

		$send['width'] = _num($dialog['width']);
		$send['element'] = _dialogElementSpisok($r['dialog_id'], 'arr');
		$send['html'] = utf8($html);
		jsonSuccess($send);
		break;
	case 'spisok_edit'://сохранение данных записи для диалога
		if(!$id = _num($_POST['id']))
			jsonError('Некорректный идентификатор');

		$sql = "SELECT *
				FROM `_spisok`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('Записи не существует');

		if($r['deleted'])
			jsonError('Запись была удалена');

		$sql = "SELECT *
				FROM `_dialog`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$r['dialog_id'];
		if(!$dialog = query_assoc($sql))
			jsonError('Диалога не существует');

		_dialogSpisokUpdate($r['dialog_id'], $id);

		jsonSuccess();
		break;
	case 'spisok_del'://удаление записи из _spisok
		if(!$id = _num($_POST['id']))
			jsonError('Некорректный идентификатор');

		$sql = "SELECT *
				FROM `_spisok`
				WHERE `app_id`=".APP_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError('Записи не существует');

		if($r['deleted'])
			jsonError('Запись уже была удалена');

		$sql = "UPDATE `_spisok`
				SET `deleted`=1
				WHERE `id`=".$id;
		query($sql);

		jsonSuccess();
		break;
}




function _dialogElementUpdate($dialog_id=0) {//проверка/внесение элементов диалога
	if(!$arr = @$_POST['element'])
		jsonError('Отсутствуют элементы диалога');
	if(!is_array($arr))
		jsonError('Некорректный массив элементов диалога');

	foreach($arr as $r) {
		if(!$type_id = _num($r['type_id']))
			jsonError('Некорректный тип элемента');
		if($type_id == 5 && empty($r['v']))
			jsonError('Отсутствуют значения элемента Radio');
	}

	//первый запуск - тестирование
	if(!$dialog_id)
		return;

	//удаление удалённых элементов
	$sql = "DELETE FROM `_dialog_element`
			WHERE `dialog_id`=".$dialog_id."
			  AND `id` NOT IN ("._idsGet($arr).")";
	query($sql);

	//удаление значений удалённых элементов
	$sql = "DELETE FROM `_dialog_element_v`
			WHERE `dialog_id`=".$dialog_id."
			  AND `element_id` NOT IN ("._idsGet($arr).")";
	query($sql);

	$sort = 0;
	foreach($arr as $r) {
		$type_id = _num($r['type_id']);

		$spisok_pole = '';
		if(!$element_id = _num(@$r['id'])) {
			//формирование названия поля на основании типа элемента
			$pole = array(
				1 => 'bool',
				2 => 'num',
				3 => 'txt',
				4 => 'txt',
				5 => 'num',
				6 => 'date'
			);
			$n = 1;
			$sql = "SELECT `spisok_pole`,1
					FROM `_dialog_element`
					WHERE `app_id`=".APP_ID."
					  AND `dialog_id`=".$dialog_id."
					  AND `spisok_pole` LIKE '".$pole[$type_id]."_%'";
			$ass = query_ass($sql);
			for($n = 1; $n <= 5; $n++) {
				$spisok_pole = $pole[$type_id].'_'.$n;
				if(!isset($ass[$spisok_pole]))
					break;
			}
		}

		$label_name = _txt($r['label_name']);
		$require = _bool($r['require']);
		$hint = _txt($r['hint']);
		$hint_top = intval($r['hint_top']);
		$hint_left = intval($r['hint_left']);
		$param_txt_1 = _txt($r['param_txt_1']);

		$sql = "INSERT INTO `_dialog_element` (
					`id`,
					`app_id`,
					`dialog_id`,
					`type_id`,
					`label_name`,
					`require`,
					`hint`,
					`hint_top`,
					`hint_left`,
					`param_txt_1`,
					`param_bool_1`,
					`param_bool_2`,
					`spisok_pole`,
					`sort`
				) VALUES (
					".$element_id.",
					".APP_ID.",
					".$dialog_id.",
					".$type_id.",
					'".addslashes($label_name)."',
					".$require.",
					'".addslashes($hint)."',
					".$hint_top.",
					".$hint_left.",
					'".addslashes($param_txt_1)."',
					"._bool($r['param_bool_1']).",
					"._bool($r['param_bool_2']).",
					'".$spisok_pole."',
					".($sort++)."
				)
				ON DUPLICATE KEY UPDATE
					`label_name`=VALUES(`label_name`),
					`require`=VALUES(`require`),
					`hint`=VALUES(`hint`),
					`hint_top`=VALUES(`hint_top`),
					`hint_left`=VALUES(`hint_left`),
					`param_txt_1`=VALUES(`param_txt_1`),
					`param_bool_1`=VALUES(`param_bool_1`),
					`param_bool_2`=VALUES(`param_bool_2`)";
		query($sql);

		if(!$element_id)
			$element_id = query_insert_id('_dialog_element');

		if(empty($r['v']))
			continue;

		//удаление удалённых значений элемента
		if($ids = _idsGet($r['v'])) {
			$sql = "DELETE FROM `_dialog_element_v`
					WHERE `element_id`=".$element_id."
					  AND `id` NOT IN (".$ids.")";
			query($sql);
		}

		//внесение дополнительных значений элемента
		$sort_v = 0;
		foreach($r['v'] as $v) {
			$sql = "INSERT INTO `_dialog_element_v` (
						`id`,
						`app_id`,
						`dialog_id`,
						`element_id`,
						`v`,
						`sort`
					) VALUES (
						"._num(@$v['id']).",
						".APP_ID.",
						".$dialog_id.",
						".$element_id.",
						'".addslashes(_txt($v['title']))."',
						".($sort_v++)."
					)
					ON DUPLICATE KEY UPDATE
						`v`=VALUES(`v`)";
			query($sql);
		}
	}
}
function _dialogElementSpisok($dialog_id, $i, $data=array()) {//список элементов диалога в формате массива и html
/*
	Форматы возврата данных:
		arr
		html
		html_edit

	Элементы и их характеристики
		1: check - bool
		2: select - num
		3: input - text
		4: textarea - text
		5: radio - num
		6: calendar - date
*/

	$arr = array();
	$html = '';
	$edit = $i == 'html_edit';//редактирование + сортировка элементов

	$sql = "SELECT *
			FROM `_dialog_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=".$dialog_id."
			ORDER BY `sort`";
	if($spisok = query_arr($sql)) {
		foreach($spisok as $r) {
			$val = '';

			//установка значения при редактировании данных диалога
			if(!empty($data))
				$val = $data[$r['spisok_pole']];

			$attr_id = 'elem'.$r['id'];
			$inp = '<input type="hidden" id="'.$attr_id.'" value="'.$val.'" />';

			$html .=
				($edit ?
					'<dd class="over1 curM prel" val="'.$r['id'].'">'.
						'<div class="element-del icon icon-del'._tooltip('Удалить элемент', -53).'</div>'.
						'<div class="element-edit icon icon-edit'._tooltip('Изменить', -29).'</div>'.
						'<table class="bs5">'
				: '').
				'<tr><td class="label r w125'.($edit ? ' w125 pr5' : '').'">'.
						($r['label_name'] ? $r['label_name'].':' : '').
						($r['require'] ? '<div class="dib red fs15 mtm2">*</div>' : '').
						($r['hint'] ? ' <div class="icon icon-hint dialog-hint" val="'.addslashes(_br(htmlspecialchars_decode($r['hint']))).'###'.$r['hint_top'].'###'.$r['hint_left'].'"></div>' : '').
					'<td>';

			switch($r['type_id']) {
				case 1://check
				case 2://select
				default: break;
				case 3://input
					$inp = '<input type="text" id="'.$attr_id.'" placeholder="'.$r['param_txt_1'].'" value="'.$val.'" />';
					break;
				case 4://textarea
					$inp = '<textarea id="'.$attr_id.'" class="w250" placeholder="'.$r['param_txt_1'].'">'.$val.'</textarea>';
					break;
			}

			$html .= $inp.($edit ? '</table></dd>' : '');

			$arr[] = array(
				'id' => _num($r['id']),
				'type_id' => _num($r['type_id']),
				'label_name' => utf8($r['label_name']),
				'require' => _bool($r['require']),
				'hint' => utf8(htmlspecialchars_decode(htmlspecialchars_decode($r['hint']))),
				'hint_top' => intval($r['hint_top']),
				'hint_left' => intval($r['hint_left']),
				'param_txt_1' => utf8($r['param_txt_1']),
				'param_bool_1' => _bool($r['param_bool_1']),
				'param_bool_2' => _bool($r['param_bool_2']),

				'attr_id' => '#'.$attr_id,

				'v' => array()
			);
		}

		$sql = "SELECT *
				FROM `_dialog_element_v`
				WHERE `element_id` IN ("._idsGet($spisok).")
				ORDER BY `sort`";
		$element_v = array();
		if($spisok = query_arr($sql)) {
			foreach($spisok as $r) {
				$element_v[$r['element_id']][] = array(
					'id' => _num($r['id']),
					'uid' => _num($r['id']),
					'title' => utf8($r['v'])
				);
			}
		}
		
		foreach($arr as $n => $r)
			if(isset($element_v[$r['id']]))
				$arr[$n]['v'] = $element_v[$r['id']];
	}

	if($i == 'arr')
		return $arr;

	return $html;
}

function _dialogSpisokUpdate($dialog_id, $spisok_id=0) {//внесение/редактирование записи списка
	//проверка на корректность данных элементов диалога
	$elem = $_POST['elem'];
	if(!is_array($elem))
		jsonError('Некорректный формат данных');
	if(empty($elem))
		jsonError('Нет данных для внесения');
	foreach($elem as $id => $v)
		if(!_num($id))
			jsonError('Некорректный идентификатор поля');

	//получение информации об элементах и составление списка для внесения в таблицу
	$sql = "SELECT *
			FROM `_dialog_element`
			WHERE `app_id`=".APP_ID."
			  AND `dialog_id`=".$dialog_id;
	$de = query_arr($sql);

	$elemUpdate = array();
	foreach($de as $id => $r) {
		$v = _txt($elem[$id]);

		if($r['require'] && empty($v))
			jsonError('Не заполнено поле <b>'.$r['label_name'].'</b>');

		$elemUpdate[] = "`".$r['spisok_pole']."`='".addslashes($v)."'";
	}


	if(!$spisok_id) {
		$sql = "INSERT INTO `_spisok` (
					`app_id`,
					`dialog_id`,
					`viewer_id_add`
				) VALUES (
					".APP_ID.",
					".$dialog_id.",
					".VIEWER_ID."
				)";
		query($sql);
		$spisok_id = query_insert_id('_spisok');
	}

	$sql = "UPDATE `_spisok`
			SET ".implode(',', $elemUpdate)."
			WHERE `id`=".$spisok_id;
	query($sql);

	return $spisok_id;
}

