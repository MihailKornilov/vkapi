<?php
switch(@$_POST['op']) {
	case 'kupezz_ob_create':
		if(!$rubric_id = _num($_POST['rubric_id']))
			jsonError('Не указана рубрика');

		$rubric_id_sub = _num($_POST['rubric_id_sub']);
		$txt = _txt($_POST['txt']);
		$txt = preg_replace('/[ ]+/', ' ', $txt);
		$telefon = _txt($_POST['telefon']);
		$country_id = _num($_POST['country_id']);
		$country_name = _txt($_POST['country_name']);
		$city_id = _num($_POST['city_id']);
		$city_name = _txt($_POST['city_name']);
		$viewer_id_show = _bool($_POST['viewer_id_show']);
		$upload_url = trim($_POST['upload_url']);
		$album_id = _num($_POST['album_id']);
		$group_id = _num($_POST['group_id']);
		$rule = _num($_POST['rule']);

		if(empty($txt))
			jsonError('Не указан текст сообщения');

		$sql = "INSERT INTO `kupezz_ob` (
					`rubric_id`,
					`rubric_id_sub`,
					`txt`,
					`telefon`,

					`country_id`,
					`country_name`,
					`city_id`,
					`city_name`,
					`viewer_id_show`,

					`day_active`,

					`viewer_id_add`
				) VALUES (
					".$rubric_id.",
					".$rubric_id_sub.",
					'".addslashes($txt)."',
					'".addslashes($telefon)."',

					".$country_id.",
					'".addslashes($country_name)."',
					".$city_id.",
					'".addslashes($city_name)."',
					".$viewer_id_show.",

					DATE_ADD(CURRENT_TIMESTAMP,INTERVAL 30 DAY),

					".VIEWER_ID."
				)";
		query($sql);

		$insert_id = query_insert_id('kupezz_ob');

		_history(array(
			'type_id' => 129,
			'ob_id' => $insert_id
		));

/*
		//сохранение изображений
		$sql = "SELECT * FROM `images` WHERE !`deleted` AND `owner`='".VIEWER_ID."' ORDER BY `sort`";
		$q = query($sql);
		if(mysql_num_rows($q)) {
			query("UPDATE `images` SET `owner`='ob".$insert_id."' WHERE !`deleted` AND `owner`='".VIEWER_ID."'");
			$image_id = 0;
			$image_link = '';
			$n = 0;
			while($r = mysql_fetch_assoc($q)) {
				$small_name = str_replace(VIEWER_ID.'-', 'ob'.$insert_id.'-', $r['small_name']);
				$big_name = str_replace(VIEWER_ID.'-', 'ob'.$insert_id.'-', $r['big_name']);
				rename(APP_PATH.'/files/images/'.$r['small_name'], APP_PATH.'/files/images/'.$small_name);
				rename(APP_PATH.'/files/images/'.$r['big_name'], APP_PATH.'/files/images/'.$big_name);
				query("UPDATE `images` SET `small_name`='".$small_name."',`big_name`='".$big_name."' WHERE `id`=".$r['id']);
				if(!$n) {
					$image_id = $r['id'];
					$image_link = $r['path'].$small_name;
					$image_post_url = $r['path'].$big_name; //изображение для сохранения на стену
				}
				$n++;
			}
			query("UPDATE `vk_ob` SET `image_id`=".$image_id.",`image_link`='".$image_link."' WHERE `id`=".$insert_id);

			//получение изображения для прикрепления к посту на стену
			if(!empty($upload_url)) {
				$img = file_get_contents($image_post_url);
				$name = APP_PATH.'/files/'.VIEWER_ID.time().'.jpg';
				$f = fopen($name, 'w');
				fwrite($f, $img);
				fclose($f);

				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $upload_url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, array('file1'=>'@'.$name));
				$out = json_decode(curl_exec($curl), true);
				curl_close($curl);
				unlink($name);

				$send['server'] = $out['server'];
				$send['photos_list'] = $out['photos_list'];
				$send['hash'] = $out['hash'];
			}
		}
*/

		$sql = "SELECT
					*,
					1 `edited`
				FROM `kupezz_ob`
				WHERE `id`=".$insert_id;
		$r = query_assoc($sql);

		$send['ob'] = utf8(kupezz_ob_unit($r));
		$send['my'] = utf8(kupezz_my_unit($r));
		jsonSuccess($send);
		break;
	case 'kupezz_ob_edit':
		if(!$id = _num($_POST['id']))
			jsonError();
		if(!$rubric_id = _num($_POST['rubric_id']))
			jsonError();

		$rubric_id_sub = _num($_POST['rubric_id_sub']);
		$txt = _txt($_POST['txt']);
		$txt = preg_replace('/[ ]+/', ' ', $txt);
		$telefon = _txt($_POST['telefon']);
		$country_id = _num($_POST['country_id']);
		$country_name = _txt($_POST['country_name']);
		$city_id = _num($_POST['city_id']);
		$city_name = _txt($_POST['city_name']);
		$viewer_id_show = _bool($_POST['viewer_id_show']);
		$active = _bool($_POST['active']);

		$sql = "SELECT *
				FROM `kupezz_ob`
				WHERE !`deleted`
				  ".(SA ? '' : "AND `viewer_id_add`=".VIEWER_ID)."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$day_active = $active ? $r['day_active'] : '0000-00-00';

		if($active && (strtotime($r['day_active']) - strtotime(strftime('%Y-%m-%d'))) <= 0)
			$day_active = strftime('%Y-%m-%d', time() + 86400 * 30);

/*
		$ob['image_id'] = 0;
		$ob['image_link'] = '';
		$sql = "SELECT * FROM `images` WHERE !`deleted` AND `owner`='ob".$ob['id']."' ORDER BY `sort` LIMIT 1";
		if($i = mysql_fetch_assoc(query($sql))) {
			$ob['image_id'] = $i['id'];
			$ob['image_link'] = $i['path'].$i['small_name'];
		}
*/
		$sql = "UPDATE `kupezz_ob`
		        SET `rubric_id`=".$rubric_id.",
					`rubric_id_sub`=".$rubric_id_sub.",
					`txt`='".addslashes($txt)."',
					`telefon`='".addslashes($telefon)."',
					`country_id`=".$country_id.",
					`country_name`='".addslashes($country_name)."',
					`city_id`=".$city_id.",
					`city_name`='".addslashes($city_name)."',
					`viewer_id_show`=".$viewer_id_show.",
					`day_active`='".$day_active."'
				WHERE `id`=".$id;
		query($sql);

//					`image_id`=".$ob['image_id'].",
//					`image_link`='".$ob['image_link']."'

		$region_old = $r['country_name'].($r['city_id'] ? ', '.$r['city_name'] : '');
		$region_new = $country_name.($city_id ? ', '.$city_name : '');
		$active_old = $r['day_active'] == '0000-00-00' || strtotime($r['day_active']) < time() ? 'в архиве' : 'до '.FullData($r['day_active']);
		$active_new = $day_active == '0000-00-00' ? 'в архиве' : 'до '.FullData($day_active);
		if($changes =
			_historyChange('Рубрика', _rubric($r['rubric_id'], $r['rubric_id_sub']), _rubric($rubric_id, $rubric_id_sub)).
			_historyChange('Текст', $r['txt'], $txt).
			_historyChange('Телефон', $r['telefon'], $telefon).
			_historyChange('Регион', $region_old, $region_new).
			_historyChange('Показывать имя из VK', _daNet($r['viewer_id_show']), _daNet($viewer_id_show)).
			_historyChange('Активность', $active_old, $active_new)
		)	_history(array(
				'type_id' => 123,
				'ob_id' => $id,
				'v1' => '<table>'.$changes.'</table>'
			));

		$sql = "SELECT
					*,
					1 `edited`
				FROM `kupezz_ob`
				WHERE `id`=".$id;
		$r = query_assoc($sql);

		$send['ob'] = utf8(kupezz_ob_unit($r));
		$send['my'] = utf8(kupezz_my_unit($r));
		jsonSuccess($send);
		break;
	case 'kupezz_ob_spisok':
		$_POST['find'] = win1251(@$_POST['find']);
		$data = kupezz_ob_spisok($_POST);
		if($data['filter']['page'] == 1)
			$send['result'] = utf8($data['result']);
		$send['spisok'] = utf8($data['spisok']);
		jsonSuccess($send);
		break;

	case 'kupezz_ob_load':
		if(!$id = _num($_POST['id']))
			jsonError();
		$sql = "SELECT *
				FROM `kupezz_ob`
				WHERE !`deleted` 
				  ".(SA ? '' : "AND `viewer_id_add`=".VIEWER_ID)."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$send = array(
			'id' => $id,
			'rubric_id' => _num($r['rubric_id']),
			'rubric_id_sub' => _num($r['rubric_id_sub']),
			'txt' => utf8($r['txt']),
			'telefon' => utf8($r['telefon']),
//			'images' => utf8(_imageAdd(array('owner'=>'ob'.$r['id']))),
			'country_id' => _num($r['country_id']),
			'city_id' => _num($r['city_id']),
			'city_name' => utf8($r['city_name']),
			'viewer_id_show' => _num($r['viewer_id_show']),
			'viewer_id_add' => _num($r['viewer_id_add']),
			'active' => strtotime($r['day_active']) - time() + 86400 < 0 ? 0 : 1
		);
		jsonSuccess($send);
		break;
	case 'kupezz_ob_post':
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `kupezz_ob`
				WHERE !`deleted`
				  AND `id`=".$id;
		if(!$ob = query_assoc($sql))
			jsonError();

		$send = array(
			'id' => _num($ob['id']),
			
			'rubric_id' => _num($ob['rubric_id']),
			'rubric_id_sub' => _num($ob['rubric_id_sub']),
			'viewer_id_show' => _bool($ob['viewer_id_show']),

			'dtime' => utf8(FullDataTime($ob['dtime_add'])),
			'rub' => utf8(_rubric($ob['rubric_id']).($ob['rubric_id_sub'] ? '<em>»</em>'._rubricSub($ob['rubric_id_sub']) : '').':'),
			'txt' => utf8($ob['txt']),
			'telefon' => utf8($ob['telefon']),
			'city' => $ob['city_name'] ? utf8($ob['country_name'].', '.$ob['city_name']) : '',
			'view' => _num(kupezz_ob_view_count($ob))
		);

		if(SA && $ob['viewer_id_add'] || $ob['viewer_id_show'] && $ob['viewer_id_add'])
			$send += array(
				'viewer_id' => _num($ob['viewer_id_add']),
				'viewer_photo' => _viewer($ob['viewer_id_add'], 'viewer_photo'),
				'viewer_name' => utf8(_viewer($ob['viewer_id_add'], 'viewer_name')),
				'viewer_link' => utf8(_viewer($ob['viewer_id_add'], 'viewer_link'))
			);


/*
		//изображения
		$img = array();
		$sql = "SELECT * FROM `images` WHERE !`deleted` AND `owner`='ob".$ob['id']."' ORDER BY `sort`";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q))
			$img[] = $r;

		$images = '';
		switch(count($img)) {
			case 1: $images = obImgBuild($img, 1); break;
			case 2: $images = obImgBuild($img, 2); break;
			case 3: $images = obImgBuild($img, 3); break;
			case 4: $images = obImgBuild($img, 4); break;
			case 5:
				$images = obImgBuild($img, 3);
				array_shift($img);
				array_shift($img);
				array_shift($img);
				$images .= obImgBuild($img, 2);
				break;
			case 6:
				$images = obImgBuild($img, 3);
				array_shift($img);
				array_shift($img);
				array_shift($img);
				$images .= obImgBuild($img, 3);
				break;
			case 7:
				$images = obImgBuild($img, 4);
				array_shift($img);
				array_shift($img);
				array_shift($img);
				array_shift($img);
				$images .= obImgBuild($img, 3);
				break;
			case 8:
				$images = obImgBuild($img, 3);
				array_shift($img);
				array_shift($img);
				array_shift($img);
				$images .= obImgBuild($img, 2);
				array_shift($img);
				array_shift($img);
				$images .= obImgBuild($img, 3);
				break;
		}

		if($images)
			$send['o']['images'] = $images;
*/


/*
		//сообщения
		$msg = '';
		$sql = "SELECT * FROM `vk_ob_msg` WHERE `ob_id`=".$id." AND (!`only_author` OR `only_author` AND `viewer_id_add`=".VIEWER_ID.") ORDER BY `id`";
		$q = query($sql);
		while($r = mysql_fetch_assoc($q))
			$msg .= ob_post_msg_unit($r);
		if($msg)
			$send['o']['msg'] = utf8($msg);
*/


		if(SA)
			$send += array(
				'sa' => 1,
				'sa_viewer_id' => _num($ob['viewer_id_add']),
				'sa_name' => $ob['viewer_id_add'] ? utf8(_viewer($ob['viewer_id_add'], 'viewer_name')) : '',
				'sa_zayav_id' => _num($ob['zayav_id'])
			);

		jsonSuccess($send);
		break;
	case 'kupezz_ob_archive'://отправка объявления в архив
		if(!SA)
			jsonError();

		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `kupezz_ob`
				WHERE !`deleted`
				  AND `day_active`!='0000-00-00'
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `kupezz_ob`
				SET `day_active`='0000-00-00'
				WHERE `id`=".$id;
		query($sql);

		_history(array(
			'type_id' => 130,
			'ob_id' => $id
		));

		jsonSuccess();
		break;
	case 'kupezz_ob_del'://удаление объявления
		if(!$id = _num($_POST['id']))
			jsonError();

		$sql = "SELECT *
				FROM `kupezz_ob`
				WHERE !`deleted`
				  AND `viewer_id_add`=".VIEWER_ID."
				  AND `id`=".$id;
		if(!$r = query_assoc($sql))
			jsonError();

		$sql = "UPDATE `kupezz_ob` SET `deleted`=1 WHERE`id`=".$id;
		query($sql);

		_history(array(
			'type_id' => 124,
			'ob_id' => $id
		));

		jsonSuccess();
		break;

	case 'kupezz_my_spisok':
		$_POST['find'] = win1251(@$_POST['find']);
		$data = kupezz_my_spisok($_POST);
		if($data['filter']['page'] == 1)
			$send['result'] = utf8($data['result']);
		$send['spisok'] = utf8($data['spisok']);
		jsonSuccess($send);
		break;
}

function kupezz_ob_view_count($ob) {//получение количества просмотров конкретного объявления
	//общее количество просмотров
	$sql = "SELECT COUNT(*)
			FROM `kupezz_ob_view`
			WHERE `ob_id`=".$ob['id'];
	$view = query_value($sql);

	if(SA)
		return $view;

	//просмотры владельца объявления не учитываются
	if($ob['viewer_id_add'] == VIEWER_ID)
		return $view;

	$sql = "SELECT COUNT(*)
			FROM `kupezz_ob_view`
			WHERE `ob_id`=".$ob['id']."
			  AND `viewer_id`=".VIEWER_ID."
			  AND `day`='".strftime('%Y-%m-%d')."'";
	if(query_value($sql))
		return $view;

	$sql = "INSERT INTO `kupezz_ob_view` (
				`ob_id`,
				`viewer_id`,
				`day`
			) VALUES (
				".$ob['id'].",
				".VIEWER_ID.",
				CURRENT_TIMESTAMP
			)";
	query($sql);

	return $view + 1;
}
