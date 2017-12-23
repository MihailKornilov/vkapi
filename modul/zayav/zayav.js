var _zayavReady = function() {//страница со списком заявок загружена
		$('#find')
			._search({
				width:178,
				focus:1,
				txt:'Быстрый поиск...',
				enter:1,
				v:ZAYAV.find,
				func:_zayavSpisok
			});
		$('#sort')._radio(_zayavSpisok);
		$('#desc')._check(_zayavSpisok);
		$('#ob_onpay')._check(_zayavSpisok);
//		$('#status_').rightLink(_zayavSpisok);

		$('#finish').zayavSrok({
			service_id:ZAYAV.service_id,
			executer_id:ZAYAV.executer_id,
			zayav_spisok:1,
			func:_zayavSpisok,
			func_executer:function(v) {
				$('#executer_id')._select(v);
				ZAYAV.executer_id = v;
				_zayavSpisok();
			}
		});

		$('#paytype')._radio(_zayavSpisok);
		$('#noschet')._check(_zayavSpisok);
		$('#nofile')._check(_zayavSpisok);
		$('#noattach')._check(_zayavSpisok);
		$('#noattach1')._check(_zayavSpisok);
		$('#f56')._check(_zayavSpisok);
		$('#f57')._check(_zayavSpisok);
		$('#f59')._check(_zayavSpisok);
		$('#f60')._check(_zayavSpisok);
		WORKER_SPISOK.push({uid:-1, title:'Не назначен', content:'<b>Не назначен</b>'});
		$('#executer_id')._select({
			width:180,
			title0:'не указан',
			spisok:WORKER_SPISOK,
			func:function(v, id) {
				$('#finish').zayavSrok('executer_id', v);
				_zayavSpisok(v, id);
			}
		});

		if($('#tovar_cat_id').length)
			$('#tovar_cat_id')._select({
				width:180,
				write:1,
				title0:'Все категории',
				spisok:ZAYAV_TOVAR_CAT,
				func:_zayavSpisok
			});

		if($('#tovar_id').length)
			$('#tovar_id').tovar({
				title:'выбрать',
				small:1,
				zayav_use:1,
				func:_zayavSpisok
			});

		$('#tovar_place_id')._select({
			width:180,
			title0:'Любое местонахождение',
			spisok:ZAYAV_TOVAR_PLACE_SPISOK,
			func:_zayavSpisok
		});

		$('#gn_year')._yearLeaf({func:_zayavSpisok});
		/*			$('#gn_nomer_id')._select({
		 width:155,
		 spisok:ZAYAV_GN_YEAR_SPISOK,
		 func:_zayavSpisok
		 });
		 */
		if($('#gn_nomer_id').length)
			$('#gn_nomer_id')._radio({
				right:0,
				light:1,
				spisok:ZAYAV_GN_YEAR_SPISOK,
				func:_zayavSpisok
			});
		_zayavPolosa();
		$('#gn_polosa_color')._radio(_zayavSpisok);

		$('#deleted')._check(_zayavSpisok);
		$('#deleted_only')._check(_zayavSpisok);

		_zayavPolosaNomerDropdown();
		_zayavObWordNomer();
		_nextCallback = _zayavPolosaNomerDropdown;

	},
	_zayavSpisok = function(v, id) {
		ZAYAV.op = 'zayav' + ZAYAV.service_id + '_spisok';
		_filterSpisok(ZAYAV, v, id);
		ZAYAV.op = 'zayav_spisok';
		$('.filter-after-unit-check')._dn(!ZAYAV.f60);
		$('.nofind')[(ZAYAV.find ? 'add' : 'remove') + 'Class']('dn');
		$('#deleted-only-div')[(ZAYAV.deleted ? 'remove' : 'add') + 'Class']('dn');
		$.post(AJAX_MAIN, ZAYAV, function(res) {
			if(res.success) {
				$('.result').html(res.all);
				$('#spisok').html(res.spisok);
				if(id == 'gn_year')
					$('#gn_nomer_id')._radio('spisokUpdate', res.gn_year_spisok);
				_zayavObWordNomer();
				//показ/скрытие цветности полосы
				if($('#gn_polosa').length && GN_ASS[ZAYAV.gn_nomer_id])
					$('#gn_polosa_color_filter')[ZAYAV.gn_polosa > 1 && ZAYAV.gn_polosa < GN_ASS[ZAYAV.gn_nomer_id].pc ? 'show' : 'hide']();
				_zayavPolosaNomerDropdown();
			}
		}, 'json');
	},
	_zayavObWordNomer = function() {//подстановка выбранного номера газеты в подсказку
		if(!$('#obWordPrint').length)
			return;
		var gn = GN_ASS[ZAYAV.gn_nomer_id];
		if(!gn)
			return;
		$('#obWordPrint .ttmsg').html('Номер ' + gn.week + '(' + gn.gen + ')');

	},
	_zayavAddMenu = function() {
		if(!SERVICE_ACTIVE_COUNT)
			return _zayavEdit(0);

		var sp = '';
		for(var i in SERVICE_ACTIVE_ASS)
			sp +=   '<div class="u" val="' + i + '">' +
						'<span>' + SERVICE_ACTIVE_ASS[i] + '</span>' +
					'</div>';

		var html = '<div id="_client-zayav-add-tab">' +
						'<div class="_info">Выберите категорию новой заявки:</div>' +
						sp +
					'</div>',
			dialog = _dialog({
				top:30,
				width:300,
				padding:20,
				head:'Внесение новой заявки',
				content:html,
				butSubmit:''
			});

		$('#_client-zayav-add-tab .u').click(function() {
			dialog.close();
			_zayavEdit($(this).attr('val'));
		});
	},
	_zayavEdit = function(sid) {
		var zayav_id = window.ZI ? ZI.id : 0,
			service_id = zayav_id ? ZI.service_id : sid || 0,
			zayav_param = ZAYAV_POLE_PARAM[service_id] || {},//подолнительные параметры полей заявки
			client_adres = '', //адрес клиента для подстановки в строку Адрес
			dialog = _dialog({
				width:700,
				top:20,
				class:'zayav-edit',
				head:zayav_id ? 'Редактирование заявки' : 'Внесение новой заявки' + (service_id ? ' - ' + SERVICE_ACTIVE_ASS[service_id] : ''),
				load:1,
				butSubmit:zayav_id ? 'Сохранить' : 'Внести',
				submit:submit
			}),
			send = {
				op:'zayav_edit_load',
				service_id:service_id,
				client_id:window.CI ? CI.id : 0,
				zayav_id:zayav_id
			};

		dialog.load(send, zLoaded);

		function zLoaded() {
			$('#ze-about')
				.autosize()
				.keyup(_zayavObCalc);

			// 5 - Клиент
			if(!$('#ze-client-name').length)
				$('#ze-client_id').clientSel({
					width:300,
					add:1,
					func:function(uid, id, item) {
						client_adres = uid ? item.adres : '';
						if(_num($('#client-adres').val()))
							$('#ze-adres').val(client_adres);
						if($('#ze-skidka').length) {
							$('#ze-skidka')._select(item.skidka);
							$('#ze-gn').gnGet('skidka', item.skidka);
						}
					}
				});

			// 4 - один товар
			var zp4 = zayav_param[4];
			$('#ze-tovar-one').tovar({
				add:1,
				equip:zp4 && zp4[0] ? zayav_id ? ZI.equip_ids : 0 : false
			});
			// 11 - несколько товаров
			$('#ze-tovar-several').tovar({several:1});

			// 6 - Адрес
			$('#client-adres')._check({
				func:function(v) {
					$('#ze-adres').val(v ? client_adres : '');
				}
			});
			$('#client-adres_check').vkHint({
				msg:'Совпадает с адресом клиента',
				top:-76,
				left:184,
				indent:60,
				delayShow:500
			});
			$('#ze-adres').keyup(function() {
				$('#client-adres')._check(0);
			});

			// 9 - Цвет
			$('#ze-color')._selectColor(zayav_id ? ZI : {});
			
			// 10 - Исполнитель
			$('#ze-executer_id')._dropdown({
				title0:'не назначен',
				spisok:_zayavExecuter()
			});
			
			// 12 - Местонахождение товара
			$('#tovar-place').zayavTovarPlace();

			// 13 - Срок
			$('#ze-srok').zayavSrok({
				service_id:service_id
			});

			// 14 - Заметка
			$('#ze-note').autosize();
			
			// 15 - Стоимость
			$('#ze-sum_cost_manual')._check(function(v) {//указание стоимости вручную или нет
				$('#ze-sum_cost')
					.attr('readonly', !v)
					.focus();
				var sum = _cena($('#ze-sum_cost').val());
				$('#ze-gn').gnGet('manual', v);
				$('#ze-gn').gnGet('summa', sum);
				$('#ze-gn').gnGet('update');
			});
			$('#ze-sum_cost').keyup(function(v) {
				$('#ze-gn').gnGet('summa', _cena($(this).val()));
				$('#ze-gn').gnGet('update');
			});

			// 16 - Расчёт
			$('#ze-pay_type')._radio({
				light:1,
				spisok:PAY_TYPE
			});

			// 36 - Размер блока рекламного выпуска
			$('#ze-size_x,#ze-size_y').keyup(function() {
				var t = $(this),
					x = _size($('#ze-size_x').val()),
					y = _size($('#ze-size_y').val()),
					kv_sm = $('#ze-kv_sm'),
					xy = Math.round(x * y);

				t[(_size(t.val()) ? 'remove' : 'add') + 'Class']('err');

				if(!xy) {
					kv_sm.val('');
					return;
				}

				kv_sm.val(xy);

				if(!$('#ze-gn').length)
					return;

				$('#ze-gn').gnGet('cena', xy);
			});

			// 38 - Номера выпуска
			var zp38 = zayav_param[38],
				manual = $('#ze-sum_cost_manual').length;
			$('#ze-gn').gnGet({
				gns:zayav_id ? ZI.gns : {},
				dop_title0:zp38 ? (
							zp38[0] ? 'Доп. параметр не указан' :
							zp38[1] ? 'Полоса не указана' : ''
						  ) : '',
				dop_spisok:zp38 ? (
							zp38[0] ? GAZETA_OBDOP_SPISOK :
							zp38[1] ? GAZETA_POLOSA_SPISOK : []
						  ) : [],
				four_free:zp38 ? zp38[0] : 0,
				pn_show:zp38 ? zp38[1] : 0,
				manual:manual ? _num($('#ze-sum_cost_manual').val()) : 1,
				summa:_cena($('#ze-sum_cost').val()),
				skidka:_num($('#ze-skidka').val()),
				func:function(v) {
					$('#ze-skidka_sum').html('');
					if(_num(manual ? $('#ze-sum_cost_manual').val() : 1))
						return;
					$('#ze-sum_cost').val(v.summa);
					$('#ze-skidka_sum').html(v.skidka_sum ? 'Сумма скидки: ' + v.skidka_sum + ' руб.' : '');
				}
			});
			_zayavObCalc();
			$('#ze-size_x').trigger('keyup');

			// 39 - Скидка
			$('#ze-skidka')._select({
				width:70,
				title0:'нет',
				spisok:SKIDKA_SPISOK,
				func:function(v) {
					$('#ze-gn').gnGet('skidka', v);
				}
			});

			// 40 - Рубрика и подрубрика
			$('#ze-rubric_id')._rubric();
		}
		function submit() {
			var send = {
				op:zayav_id ? 'zayav_edit' : 'zayav_add',
				service_id:service_id,
				zayav_id:zayav_id,

				name:$('#ze-name').val(),               // 1
				about:$('#ze-about').val(),             // 2
				count:$('#ze-count').val(),             // 3
				tovar:$('#ze-tovar-one').length ? $('#ze-tovar-one').val() : $('#ze-tovar-several').val(), // 4, 11
				client_id:$('#ze-client_id').val(),     // 5
				phone:$('#ze-phone').val(),             // 37
				adres:$('#ze-adres').val(),             // 6
				imei:$('#ze-imei').val(),               // 7
				serial:$('#ze-serial').val(),           // 8
				color_id:$('#color_id').val(),          // 9
				color_dop:$('#color_dop').val(),        // 9
				executer_id:$('#ze-executer_id').val(), // 10
				rubric_id:$('#ze-rubric_id').val(),     // 40
				rubric_id_sub:$('#ze-rubric_id_sub').val(),// 40
				size_x:$('#ze-size_x').val(),           // 31
				size_y:$('#ze-size_y').val(),           // 31
				place_id:$('#tovar-place').val(),          // 12
				place_other:$('#tovar-place').attr('val'), // 12
				srok:$('#ze-srok').val(),               // 13
				note:$('#ze-note').val(),               // 14
				skidka:$('#ze-skidka').val(),           // 39
				sum_manual:_bool($('#ze-sum_cost_manual').val()),// 15:v1
				sum_cost:$('#ze-sum_cost').val(),       // 15
				pay_type:$('#ze-pay_type').val(),       // 16
				tovar_equip_ids:$('#ze-tovar-one').tovar('equip_ids_sel'),// 4:v1
				gn:$('#ze-gn').val()                    // 38
			};
			dialog.post(send, function(res) {
				_scroll('set', 'u' + res.id);
				location.href = URL + '&p=45&id=' + res.id;
			});
		}
	},
	_zayavObCalc = function() {// Вычисление стоимости объявления
		var CALC = $('#ze-about-calc');
		if(!CALC.length || !$('#ze-gn').length || !$('#ze-about').length)
			return;

		var txt_sum = 0, // сумма только за текст
			podr_about = '', // подробное расписывание длины объявления
			txt = $('#ze-about').val()
					.replace(/\./g, '')    // точки
					.replace(/,/g, '')     // запятые
					.replace(/\//g, '')    // слеш /
					.replace(/\"/g, '')    // двойные кавычки
					.replace(/( +)/g, ' ') // вторые пробелы
					.replace( /^\s+/g, '') // пробелы в начале
					.replace( /\s+$/g, '');// пробелы в конце

		CALC['slide' + (txt.length ? 'Down' : 'Up')](150);

		if(!txt.length)
			CALC.html('');
		else {
			txt_sum += TXT_CENA_FIRST * 1;
			if(txt.length > TXT_LEN_FIRST) {
				podr_about = ' = ';
				var CEIL = Math.ceil((txt.length - TXT_LEN_FIRST) / TXT_LEN_NEXT);
				podr_about += TXT_LEN_FIRST;
				var LAST = txt.length - TXT_LEN_FIRST - (CEIL - 1) * TXT_LEN_NEXT;
				txt_sum += CEIL * TXT_CENA_NEXT;
				if(TXT_LEN_NEXT == LAST) CEIL++;
				if(CEIL > 1) podr_about += ' + ' + TXT_LEN_NEXT;
				if(CEIL > 2) podr_about += 'x' + (CEIL - 1);
				if(TXT_LEN_NEXT > LAST) podr_about += ' + ' + LAST;
			}
			var html = 'Длина: <b>' + txt.length + '</b>' + podr_about + '<br />' +
					   'Цена: <b>' + txt_sum + '</b> руб.<span>(без учёта доп. параметров)</span>';
			CALC.html(html);
		}
		$('#ze-gn').gnGet('cena', txt_sum);
	},
	_zayavPolosa = function() {
		if(!$('#gn_polosa').length)
			return;
		var gn = GN_ASS[ZAYAV.gn_nomer_id],
			pc = [{uid:1,title:'Первая'}];
		if(gn) {
			for(var n = 2; n < gn.pc; n++)
				pc.push({uid:n, title:n + '-я'});
			pc.push({uid:102, title:'Последняя ' + n + '-я'});
		}
		pc.push({uid:103,title:'Внутренняя чёрно-белая'});
		pc.push({uid:104,title:'Внутренняя цветная'});
		pc.push({uid:105,title:'Внутренняя (номер не указан)'});
		$('#gn_polosa')._select({
			title0:'Любая полоса',
			spisok:pc,
			func:_zayavSpisok
		});
	},
	_zayavPolosaNomerDropdown = function() {
		var pnc = $('.zayav-polosa-nomer'),
			pc = [],
			n;

		if(!pnc.length)
			return;

		for(n = 2; n < GN_ASS[ZAYAV.gn_nomer_id].pc; n++)
			pc.push({uid:n,title:n + '-я'});

		for(n = 0; n < pnc.length; n++) {
			var sp = pnc.eq(n);

			$('#' + sp.attr('id'))._dropdown({
				title0:'??',
				spisok:pc,
				func:function(v, attr_id) {
					var send = {
						op:'zayav_gn_polosa_nomer_change',
						zgn_id:_num($('#' + attr_id).attr('val')),
						polosa:v
					};
					$.post(AJAX_MAIN, send, function(res) {
						if(res.success)
							_msg();
					}, 'json');
				}
			});
			sp.removeClass('zayav-polosa-nomer');
		}
	},
	_zayavStatusSpisok = function(zi) {//список статусов
		var spisok = '';
		for(var i = 0; i < ZAYAV_STATUS_NAME_SPISOK.length; i++) {
			var sp = ZAYAV_STATUS_NAME_SPISOK[i];
			if(zi && sp.uid == zi.status_id)
				continue;
			if(ZAYAV_STATUS_NOUSE_ASS[sp.uid])
				continue;
			if(zi && ZAYAV_STATUS_NEXT[zi.status_id] && !ZAYAV_STATUS_NEXT[zi.status_id][sp.uid])
				continue;
			spisok += '<div class="sp curP pad10 bor-e8 fs14 blue mb5" val="' + sp.uid + '" style="background-color:#' + ZAYAV_STATUS_COLOR_ASS[sp.uid] + '">' +
						 sp.title +
						'<div class="about fs11 mt5 grey">' + ZAYAV_STATUS_ABOUT_ASS[sp.uid] + '</div>' +
					  '</div>'
		}
		return spisok;
	},
	_zayavStatus = function() {//Изменение статуса заявки

		var html =
			'<div id="zayav-status">' +
				'<table class="bs10 w100p">' +
					'<tr><td class="label">Текущий статус:' +
						'<td><div class="pad10 bor-e8 fs14 blue" style="background-color:#' + ZAYAV_STATUS_COLOR_ASS[ZI.status_id] + '">' +
								ZAYAV_STATUS_NAME_ASS[ZI.status_id] +
							'</div>' +
				'</table>' +

				'<div id="new-tab">' +
					'<input type="hidden" id="status-new" />' +
					'<table class="bs10 w100p">' +
						'<tr><td class="label topi">Новый статус:<td>' + _zayavStatusSpisok(ZI) +
					'</table>' +
				'</div>' +

				'<div id="zs-tab" class="dn">' +
					'<table class="bs10">' +
						'<tr class="tr-day-fact dn">' +
							'<td class="label">Фактический день:' +
							'<td><input type="hidden" id="day" value="' + ZI.status_day + '" />' +

						'<tr class="tr-executer dn"><td class="label">Исполнитель:<td><input type="hidden" id="zs-executer_id" value="' + ZI.executer_id + '" />' +

						'<tr class="tr-srok dn">' +
							'<td class="label">Срок выполнения:' +
							'<td><input type="hidden" id="zs-srok" value="' + ZI.srok + '" />' +

						'<tr><td class="label topi">Комментарий:' +
							'<td><textarea id="zs-comm" placeholder="не обязательно"></textarea>' +

						'<tr class="tr-accrual dn"><td class="label">Начислить:<td><input type="text" class="money" id="accrual-sum" /> руб.' +

						'<tr class="tr-rem dn"><td class="label">Добавить напоминание:<td><input type="hidden" id="zs-remind" />' +
						'<tr class="tr-remind"><td class="label">Содержание:<td><input type="text" id="remind-txt" value="Позвонить и сообщить о результате" />' +
						'<tr class="tr-remind"><td class="label">Дата:<td><input type="hidden" id="remind-day" />' +
					'</table>' +
				'</div>' +

			'</div>',

/*
			(ZAYAV_INFO_DEVICE ?
					'<tr><td class="label r topi">Местонахождение устройства:<td><input type="hidden" id="device-place" value="-1" />'
			: '') +

		if(ZAYAV_INFO_DEVICE)
			zayavPlace();
*/

			dialog = _dialog({
				top:30,
				width:500,
				padding:0,
				head:'Изменение статуса заявки',
				content:html,
				butSubmit:'',
				submit:submit
			});

		$('#new-tab').slideDown(300);

		$('#zs-executer_id')._dropdown({
			title0:'не назначен',
			spisok:_zayavExecuter()
		});
		$('#zs-srok').zayavSrok({
			type_id:ZI.type_id,
			executer_id:ZI.executer_id
		});
		$('#zs-comm').autosize();
		$('#zs-remind')._check({
			func:function(v) {
				$('.tr-remind')[v ? 'show' : 'hide']();
			}
		});
		$('#remind-day')._calendar({tomorrow:1});
		$('.sp').click(function() {
			var t = $(this),
				v = t.attr('val');

			$('#status-new').val(v);

			t.removeClass('sp curP');
			t.find('div').slideUp(300);
			t.parent().find('.sp').slideUp(300, function() {
				$('#zs-tab').slideDown();
				$('#zs-comm').focus();
			});

			if(ZAYAV_STATUS_DAY_FACT_ASS[v]) {
				$('#day')._calendar({lost:1});
				$('.tr-day-fact').removeClass('dn');
			}

			if(ZAYAV_STATUS_EXECUTER_ASS[v])
				$('.tr-executer').removeClass('dn');

			if(ZAYAV_STATUS_SROK_ASS[v])
				$('.tr-srok').removeClass('dn');

			if(ZAYAV_STATUS_ACCRUAL_ASS[v])
				$('.tr-accrual').removeClass('dn');

			if(ZAYAV_STATUS_REMIND_ASS[v])
				$('.tr-rem').removeClass('dn');

			dialog.butSubmit('Применить');
		});

		function submit() {
			var send = {
				op:'zayav_status',
				zayav_id:ZI.id,
				status_id:_num($('#status-new').val()),
				status_day:'0000-00-00',
				place:0,
				place_other:'',
				executer_id:_num($('#zs-executer_id').val()),
				srok:'',
				comm:$('#zs-comm').val(),
				accrual_sum:$('#accrual-sum').val(),
				remind:_bool($('#zs-remind').val()),
				remind_txt:$('#remind-txt').val(),
				remind_day:$('#remind-day').val()
			};
/*
			if(ZAYAV_INFO_DEVICE) {
				send.place = $('#device-place').val() * 1;
				send.place_other = $('#place_other').val();
				if(send.place > 0)
					send.place_other = '';
				if(send.place == -1 || !send.place && !send.place_other) {
					dialog.err('Не указано местонахождение устройства');
					$('#place_other').focus();
					return;
				}
			}

*/

			if(ZAYAV_STATUS_DAY_FACT_ASS[send.status_id])
				send.status_day = $('#day').val();

			if(ZAYAV_STATUS_EXECUTER_ASS[send.status_id] && !send.executer_id) {
				dialog.err('Не назначен исполнитель');
				return;
			}

			if(ZAYAV_STATUS_SROK_ASS[send.status_id]) {
				send.srok = $('#zs-srok').val();
				if(send.srok == '0000-00-00') {
					dialog.err('Не указан срок выполнения');
					return;
				}
			}

			if(ZAYAV_STATUS_ACCRUAL_ASS[send.status_id])
				if(send.accrual_sum && send.accrual_sum != 0 && !_cena(send.accrual_sum)) {
					dialog.err('Некорректно указано начисление');
					$('#accrual-sum').focus();
					return;
				}

			if(ZAYAV_STATUS_REMIND_ASS[send.status_id])
				if(send.remind && !send.remind_txt) {
					dialog.err('Не указано содержание напоминания');
					$('#remind-txt').focus();
					return;
				}

			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					document.location.reload();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	_zayavTypeChange = function() {//Изменение категории заявки
		var html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">Категория заявки:' +
						'<td><input type="hidden" id="service_id" value="' + ZI.service_id + '" />' +
				'</table>',
			dialog = _dialog({
				head:'Изменение категории заявки',
				content:html,
				butSubmit:'Применить',
				submit:submit
			});

		$('#service_id')._select({
			width:200,
			spisok:_toSpisok(SERVICE_ACTIVE_ASS)
		});

		function submit() {
			var send = {
				op:'zayav_service_change',
				zayav_id:ZI.id,
				service_id:$('#service_id').val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					location.reload();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	_zayavExecuter = function() {//составление списка исполнителей
		if(window.RE)//RULE_EXECUTER
			return RE;

		var send = [];
		for(var n = 0; n < WORKER_SPISOK.length; n++) {
			var sp = WORKER_SPISOK[n];
			if(WORKER_EXECUTER[sp.uid])
				send.push(sp);
		}
		window.RE = send;
		return _zayavExecuter();
	},
	
	_zayavGo = function(zayav_id) {//переход на заявку без href
		location.href = URL + '&p=45&id=' + zayav_id;
	},

	_zayavOnpayPublic = function() {
		var html =
				'<div>' +
					'Прежде чем разрешить публикацию объявления, проверьте следующие параметры:<br /><br />' +
					'1. Объявление не должно содержать ошибок.<br />' +
					'2. Объявление должно выполнять условия размещения.<br />' +
					'3. Должен быть зачислен платёж от Onpay.' +
					'' +
				'</div>',
			dialog = _dialog({
				head:'Проверка интернет-объявления',
				content:html,
				butSubmit:'Разрешить публикацию',
				submit:submit
			});

		function submit() {
			var send = {
				op:'zayav_onpay_public',
				zayav_id:ZI.id
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg('Объявление проверено');
					location.reload();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	_zayavOnpayPublicNo = function() {
		var html =
				'<div>' +
					'Объявление не будет удалено.' +
					'<br />' +
					'Оно не попадёт ни в один из номеров газеты, пока публикация не будет разрешена.' +
				'</div>',
			dialog = _dialog({
				head:'Проверка интернет-объявления',
				content:html,
				butSubmit:'Запретить публикацию',
				submit:submit
			});

		function submit() {
			var send = {
				op:'zayav_onpay_public_no',
				zayav_id:ZI.id
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					location.reload();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},

	_zayavDogovor2 = function() {//договор для оказания услуг
		DOG.template_id = 2;
		_zayavDogovorCreate();
	},
	_zayavDogovor1 = function() {//договор для продажи товара
		DOG.template_id = 1;
		_zayavDogovorCreate();
	},
	_zayavDogovorCreate = function() {
		var o = $.extend({
			id:0,
			template_id:255,
			fio:'',
			adres:'',
			pasp_seria:'',
			pasp_nomer:'',
			pasp_adres:'',
			pasp_ovd:'',
			pasp_data:'',
			nomer:'',
			nomer_next:'',
			data_create:'',
			sum:'',
			avans_hide:0,//скрывать авансовый платёж, если наступил следующий день после заключения договора
			avans_invoice_id:0,
			avans_sum:''
		}, DOG);

		var html = '<table id="_zayav-dog-tab" class="bs10 w100p">' +
				'<tr><td class="label r w150">Ф.И.О. клиента:<td><input type="text" id="fio" class="w300" value="' + o.fio + '" />' +
				'<tr><td class="label r">Адрес:<td><input type="text" id="adres" value="' + o.adres + '" class="w300" />' +
				'<tr><td class="label r topi">Паспорт:' +
					'<td class="color-555">&nbsp;Серия: <input type="text" id="pasp_seria" class="w50 mr20" maxlength="8" value="' + o.pasp_seria + '" />' +
						'Номер: <input type="text" id="pasp_nomer" class="w100" maxlength="10" value="' + o.pasp_nomer + '" />' +
						'<table class="bs5">' +
							'<tr><td>Прописка:<td><input type="text" id="pasp_adres" class="w200" value="' + o.pasp_adres + '" />' +
							'<tr><td>Кем выдан:<td><input type="text" id="pasp_ovd" class="w200" value="' + o.pasp_ovd + '" />' +
							'<tr><td>Когда выдан:<td><input type="text" id="pasp_data" class="w200" value="' + o.pasp_data + '" />' +
						'</table>' +
				'<tr><td class="label r">Номер договора:' +
					'<td><input type="text" id="nomer" class="w50" value="' + o.nomer + '" placeholder="' + o.nomer_next + '" />' +
				'<tr><td class="label r">Дата заключения:' +
					'<td><input type="hidden" id="data_create" value="' + o.data_create + '" />' +
				'<tr><td class="label r">Сумма по договору:<td><input type="text" id="sum" class="money" maxlength="11" value="' + (o.sum ? o.sum : '') + '" /> руб.' +
				'<tr' + (o.avans_hide && !o.avans_invoice_id ? ' class="dn"' : '') + '>' +
					'<td class="label r topi">Авансовый платёж:' +
					'<td><input type="hidden" id="avans_check" />' +
						'<div id="avans_div"' + (!o.id ? ' class="dn"' : '') + '>' +
							'<input type="hidden" id="invoice_id-add" value="' + o.avans_invoice_id + '" />' +
							'<div class="tr_confirm mt5 dn"><input type="hidden" id="confirm" /></div>' +
							'<input type="text" id="avans_sum" class="money mt10" value="' + o.avans_sum + '"' + (o.avans_hide ? ' disabled' : '') + ' /> руб. ' +
						'</div>' +
				'</table>' +

			(APP_ID != 6044422 ?
				'<div id="preview" class="center pad10 mt10 over1 curP color-555">Предварительный просмотр договора</div>' +
				'<form action="' + AJAX_MAIN + '" method="post" id="preview-form" target="_blank"></form>'
			: ''),

			dialog = _dialog({
				width:550,
				top:10,
				head:(o.id ? 'Изменение данных' : 'Заключение') + ' договора',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Заключить договор',
				submit:submit
			}),
			send;

		if(!o.id)
			$('#avans_check')._check({
				func:function() {
					$('#avans_check_check').remove();
					$('#avans_div').removeClass('dn');
					$('#invoice_id-add')._check(_invoiceIncomeInsert(1));
				}
			});

		incomeConfirmCheck(o.avans_hide);
		$('#data_create')._calendar({lost:1});
		$('#preview').click(function() {
			if(!(send = valuesTest()))
				return;
			send.op = 'dogovor_preview';
			var form = '';
			for(var i in send)
				form += '<input type="hidden" name="' + i + '" value="' + send[i] + '">';
			$('#preview-form').html(form).submit();
		});

		function valuesTest() {
			var send = {
				op:'dogovor_' + (o.id ? 'edit' : 'create'),
				id:o.id,
				zayav_id:ZI.id,
				template_id:o.template_id,
				fio:$('#fio').val(),
				adres:$('#adres').val(),
				pasp_seria:$('#pasp_seria').val(),
				pasp_nomer:$('#pasp_nomer').val(),
				pasp_adres:$('#pasp_adres').val(),
				pasp_ovd:$('#pasp_ovd').val(),
				pasp_data:$('#pasp_data').val(),
				nomer:_num($('#nomer').val()),
				data_create:$('#data_create').val(),
				sum:_cena($('#sum').val()),
				invoice_id:_num($('#invoice_id-add').val()),
				confirm:_bool($('#confirm').val()),
				avans:_cena($('#avans_sum').val())
			};
			if(!send.fio) {
				dialog.err('Не указано Фио клиента');
				$('#fio').focus();
				return false;
			}
			if(!send.nomer) {
				dialog.err('Некорректно указан номер договора');
				$('#nomer').focus();
				return false;
			}
			if(!send.sum) {
				dialog.err('Некорректно указана сумма по договору');
				$('#sum').focus();
				return false;
			}
			if(!send.avans_hide && send.avans && !send.invoice_id) {
				dialog.err('Не указан счёт авансового платёжа');
				return false;
			}
			if(!send.avans_hide && send.invoice_id && !send.avans) {
				dialog.err('Некорректно указан авансовый платёж');
				$('#avans_sum').focus();
				return false;
			}
			return send;
		}
		function submit() {
			if(!(send = valuesTest()))
				return;
			dialog.post(send, 'reload');
		}
	},
	_zayavDogovorTerminate = function() {
		var html =
				'<div class="center">' +
					'Договор №<b>' + DOG.nomer + '</b>:<br /><br />' +
					'<b class="red">Подтвердите расторжение договора.</b>' +
				'</div>',
			dialog = _dialog({
				head:'Расторжение договора',
				padding:60,
				content:html,
				butSubmit:'Применить',
				submit:submit
			});

		function submit() {
			var send = {
				op:'dogovor_terminate',
				dogovor_id:DOG.id
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					location.reload();
				} else
					dialog.abort();
			}, 'json');
		}
		
	},

	_zayavExpenseEdit = function () {//внесение/редактирование расхода по заявке
		var prev = $('#_zayav-expense table'),
			html =
			(prev.length ? '<table id="ze-prev">' + prev.html() + '</table>' : '') +
			'<table class="bs10 w100p">' +
				'<tr><td class="label r top w100">Заявка:<td><b>' + ZI.name + '</b>' +
				'<tr><td class="label r">Категория:<td><input type="hidden" id="ze-cat" />' +
				'<tr class="tr-dop dn">' +
					'<td class="label r" id="td-label">' +
					'<td id="td-input">' +

				'<tr class="tr-count dn">' +
					'<td class="label r">Количество:' +
					'<td><input type="text" id="ze-count" class="w50" /> ' +
						'<em id="ze-measure"></em>' +
						'<span id="ze-count-max">(max: <b></b>)</span>' +

				'<tr><td class="label r">Сумма:<td><input type="text" id="ze-sum" class="money" /> руб.' +

				'<tr><td><td>' +
				'<tr id="tr-expense-dub" class="dn">' +
					'<td class="label">' +
					'<td><input type="hidden" id="ze-expense-dub" />' +
				'<tr id="tr-expense-cat" class="dn">' +
					'<td class="label r topi">Категория расхода организации:' +
					'<td><input type="hidden" id="ze-cat-id-add" />' +
						'<input type="hidden" id="category_sub_id-add" />' +
				'<tr id="tr-invoice" class="dn">' +
					'<td class="label r topi">Расчётный счёт:' +
					'<td><input type="hidden" id="invoice_id" />' +
			'</table>',
			dialog = _dialog({
				top:30,
				width:490,
				head:'Новый расход заявки',
				content:html,
				butCancel:'Закрыть',
				submit:submit,
				cancel:function() {
					$('.inserted').removeClass('inserted');
					$('#ze-cat')._select('remove');
				}
			});

		$('#ze-cat')._select({
				width:200,
				disabled:0,
				title0:'категория не выбрана',
				spisok:ZAYAV_EXPENSE_SPISOK,
				func:catSelect
			});
		$('#ze-expense-dub')._check({
			name:'продублировать в расходах организации',
			light:1,
			func:function(v) {
				$('#tr-expense-cat')[(v ? 'remove' : 'add') + 'Class']('dn');
				$('#tr-invoice')[(v ? 'remove' : 'add') + 'Class']('dn');
				if(!v)
					return;
				
				var cat_id = _num($('#ze-cat').val()),
					sp = ZE_DUB_ASS[cat_id].split('_'),
					id = _num(sp[0]),
					id_sub = _num(sp[1]);
				
				$('#ze-cat-id-add').val(id)._select({
					width:258,
					bottom:5,
					title0:'Не указана',
					spisok:_copySel(EXPENSE_SPISOK, 1),
					func:function(v, id) {
						_expenseSub(v, 0, '-add');
					}
				});
				_expenseSub(id, id_sub, '-add');

				$('#invoice_id')._select({
					width:270,
					title0:'Не выбран',
					spisok:_invoiceExpenseInsert()
				});
			}
		});

		sumFocus();

		function sumFocus() {
			$('#ze-sum').focus();
		}
		function catSelect(id) {
			$('.tr-count').addClass('dn');
			$('#ze-count-max').show();
			sumFocus();
			$('.tr-dop')[(id ? 'remove' : 'add') + 'Class']('dn');
			$('#tr-expense-dub')[(ZE_DUB_ASS[id] ? 'remove' : 'add') + 'Class']('dn');
			if(!id)
				return;
			var dop_id = ZE_DOP_ASS[id];
			$('#td-label')
				.html(ZE_DOP_NAME[dop_id] + ':')
				[(dop_id == 4 ? 'remove' : 'add') + 'Class']('topi');
			$('#td-input').html('<input type="hidden" id="ze-dop" />');

			switch(dop_id) {
				case 1: //описание
					$('#td-input').html('<input type="text" id="ze-dop" class="w250" />');
					$('#ze-dop').focus();
					break;
				case 2: //сотрудник
					$('#ze-dop')._select({
						width:200,
						disabled:0,
						title0:'Сотрудник',
						spisok:WORKER_SPISOK,
						func:sumFocus
					});
					break;
				case 5: //товар
					$('#ze-count-max').hide();
					$('#ze-dop').tovar({
						open:1,
						add:1,
						func:function(v, attr_id, sp) {
							$('.tr-count')[(v ? 'remove' : 'add') + 'Class']('dn');
							$(v ? '#ze-count' : '#ze-sum').focus();
							$('#ze-count')
								.val(1)
								.select()
								.off('keyup')
								.keyup(function() {
									$('#ze-sum').val(_cena(_num($(this).val()) * sp.sum_buy));
								});
							$('#ze-sum').val(v ? sp.sum_buy : '');
						}
					});
					break;
				case 3: //товар наличие
					$('#ze-dop').tovar({
						open:1,
						avai:1,
						tovar_id_use:ZI.tovar_id,
						func:function(v, attr_id, sp) {
							$('#ze-dop').val(sp.avai_id);               //установка id наличия
							$('.tr-count')[(v ? 'remove' : 'add') + 'Class']('dn');//показ input-количества
							$('#ze-sum').val('');

							if(!sp.avai_id)
								return;

							$('#ze-count-max b').html(sp.avai_count);   //установка максимального количества в наличии
							$('#ze-measure').html(sp.measure);          //установка ед.измерения
							$('#ze-count')
								.val(1)
								.select()
								.keyup(function() {
									$('#ze-sum').val(Math.round(_ms($(this).val()) * sp.avai_buy));
								});
							$('#ze-count').trigger('keyup');
						}
					});
					break;
				case 4: //файл
					$('#ze-dop')._attach({
						zayav_id:ZI.id,
						func:sumFocus
					});
					break;
				default://нет
					$('.tr-dop').addClass('dn');
					$('#td-input').html('');
			}
		}

		function submit() {
			var send = {
				op:'zayav_expense_add',
				zayav_id:ZI.id,
				cat_id:_num($('#ze-cat').val()),
				dop:$('#ze-dop').length ? $('#ze-dop').val() : '',
				count:_ms($('#ze-count').val()),
				sum:_cena($('#ze-sum').val()),
				expense_dub:$('#ze-expense-dub').val(),
				expense_cat_id:$('#ze-cat-id-add').val(),
				expense_cat_id_sub:$('#category_sub_id-add').val(),
				invoice_id:$('#invoice_id').val()
			};
			dialog.post(send, function(res) {
				$('#_zayav-expense').html(res.html);
				_zayavExpenseEdit();
			});
		}
	},

	_zayavExpenseAttachSchetSpisok = function(v, id) {
		ZE_ATTACH_SCHET.page = 1;
		ZE_ATTACH_SCHET[id] = v;
		$.post(AJAX_MAIN, ZE_ATTACH_SCHET, function(res) {
			if(res.success)
				$('#ze-attach-schet').html(res.spisok);
		}, 'json');
	},
	_zayavExpenseAttachSchetPay = function(id) {
		var html =
				'<center>Подтверждение оплаты счёта.</center>',
			dialog = _dialog({
				head:'Подтверждение оплаты счёта',
				content:html,
				padding:30,
				butSubmit:'Счёт оплачен',
				submit:submit
			});

		function submit() {
			var send = {
				op:'ze_attach_schet_pay',
				id:id
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					location.reload();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},

	_zayavKvit = function() {//формирование квитанции
		var html = '<table class="zayav-print bs10">' +
				'<tr><td class="label">Дата приёма:<td>' + KVIT.dtime +
				'<tr><td class="label top">Устройство:<td>' + KVIT.device +
				'<tr><td class="label">Цвет:<td>' + (KVIT.color ? KVIT.color : '<i>не указан</i>') +
				'<tr><td class="label">IMEI:<td>' + (ZI.imei ? ZI.imei : '<i>не указан</i>') +
				'<tr><td class="label">Серийный номер:<td>' + (ZI.serial ? ZI.serial : '<i>не указан</i>') +
				'<tr><td class="label">Комплектация:<td>' + (KVIT.equip ? KVIT.equip : '<i>не указана</i>') +
				'<tr><td class="label">Заказчик:<td><b>' + ZI.client_link + '</b>' +
				'<tr><td class="label">Телефон:<td>' + (KVIT.phone ? KVIT.phone : '<i>не указан</i>') +
				'<tr><td class="label top">Неисправность:<td><textarea id="defect">' + KVIT.defect + '</textarea>' +
				'<tr><td colspan="2"><a id="preview"><span>Предварительный просмотр квитанции</span></a>' +
				'</table>',
			dialog = _dialog({
				width: 380,
				top: 30,
				head: 'Заявка №' + ZI.nomer + ' - Формирование квитанции',
				content: html,
				butSubmit: 'Сохранить квитанцию',
				submit: submit
			});
		$('#defect').focus().autosize();
		$('#preview').click(function () {
			var t = $(this),
				send = {
					op:'zayav_kvit',
					zayav_id:ZI.id,
					defect:$.trim($('#defect').val())
				};
			if(t.hasClass('_busy'))
				return;
			if(!send.defect) {
				dialog.err('Не указана неисправность');
				$('#defect').focus();
				return;
			}
			t.addClass('_busy');
			$.post(AJAX_MAIN, send, function(res) {
				t.removeClass('_busy');
				if(res.success)
					_zayavKvitHtml(res.id);
			}, 'json');
		});
		function submit() {
			var send = {
				op: 'zayav_kvit',
				zayav_id: ZI.id,
				defect: $.trim($('#defect').val()),
				active: 1
			};
			if(!send.defect) {
				dialog.err('Не указана неисправность');
				$('#defect').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg('Квитанция сохранена');
					document.location.reload();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	_zayavKvitHtml = function(id) {
		var params =
			'scrollbars=yes,' +
			'resizable=yes,' +
			'status=no,' +
			'location=no,' +
			'toolbar=no,' +
			'menubar=no,' +
			'width=680,' +
			'height=500,' +
			'left=20,' +
			'top=20';
		window.open(URL + '&p=75&d=kvit_html&id=' + id, 'kvit', params);
	},

	cartridgeNew = function(id, callback) {
		var t = $(this),
			html = '<table class="bs10">' +
				'<tr><td class="label r">Вид:<td><input type="hidden" id="type_id" value="1" />' +
				'<tr><td class="label r"><b>Модель картриджа:</b><td><input type="text" id="name" class="w150" />' +
				'<tr><td class="label r">Заправка:<td><input type="text" id="cost_filling" class="money" /> руб.' +
				'<tr><td class="label r">Восстановление:<td><input type="text" id="cost_restore" class="money" /> руб.' +
				'<tr><td class="label r">Замена чипа:<td><input type="text" id="cost_chip" class="money" /> руб.' +
				'</table>',
			dialog = _dialog({
				top:20,
				head:'Добавление нового картриджа',
				content:html,
				submit:submit
			});
		$('#type_id')._select({
			spisok:CARTRIDGE_TYPE
		});
		$('#name').focus();
		function submit() {
			var send = {
				op:'cartridge_new',
				type_id:$('#type_id').val(),
				name:$('#name').val(),
				cost_filling:_num($('#cost_filling').val()),
				cost_restore:_num($('#cost_restore').val()),
				cost_chip:_num($('#cost_chip').val()),
				from:$('#setup-cartridge').length ? 'setup' : ''
			};
			if(!send.name) {
				dialog.err('Не указано наименование');
				$('#name').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					if(send.from == 'setup')
						$('#spisok').html(res.spisok);
					else {
						CARTRIDGE_SPISOK = res.spisok;
						$('#' + id)._select(res.spisok);
						$('#' + id)._select(res.insert_id);
						callback(res.insert_id);
					}
					dialog.close();
					_msg();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	_zayavCartridgeAdd = function() {//добавление картриджей к заявке
		var html =
				'<table class="bs10">' +
					'<tr><td class="label topi w150 r">Список картриджей:<td id="crt">' +
				'</table>',
			dialog = _dialog({
				width:470,
				top:30,
				head:'Добавление картриджей к заявке',
				content:html,
				submit:submit
			});
		$('#crt').cartridge();
		function submit() {
			var send = {
				op:'zayav_cartridge_add',
				zayav_id:ZI.id,
				ids:$('#crt').cartridge('get')
			};
			if(!send.ids) {
				dialog.err('Не выбрано ни одного картриджа');
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					$('#zc-spisok').html(res.html);
					_msg();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	_zayavCartridgeSchetPay = function(t) {
		if(!_checkAll()) {
			t.vkHint({
				top:-80,
				left:440,
				msg:'<span class="red">Не выбраны картриджи</span>',
				show:1,
				remove:1
			});
			return false;
		}

		window.CARTRIDGE_IDS = _checkAll();

		schetPayEdit();
	},
	_zayavCartridgeSchetSet = function(schet_id) {
		var send = {
			op:'zayav_cartridge_schet_set',
			schet_id:schet_id,
			ids:_checkAll()
		};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				location.reload();
		}, 'json');
	},

	_zayavGNLostShow = function() {//показ вышедших номеров газеты
		var t = $('tr.gn-lost');
		if(!t.length)
			return;
		t.parent().find('.bg-gr1').show();
		t.remove();
	},
	_zayavSchetPayKupez = function(t) {
		if(!_checkAll()) {
			t.vkHint({
				top:-75,
				left:323,
				msg:'<span class="red">Не выбраны номера выпуска</span>',
				show:1,
				remove:1
			});
			return false;
		}

		window.GN_IDS = _checkAll();

		schetPayEdit();
	},

	_zayavReportSpisok = function(v, id) {
		ZAYAV_REPORT.page = 1;
		if(v)
			ZAYAV_REPORT[id] = v;
		$.post(AJAX_MAIN, ZAYAV_REPORT, function(res) {
			if(res.success) {
				$('#status-count').html(res.status);
				$('#executer-count').html(res.executer);
				$('#spisok').html(res.spisok);
			}
		}, 'json');
	},

	_zayavReportKupez = function() {//временный отчёт по заявкам для Купца
		$('#gnyear')._yearLeaf({func:_zayavReportKupezSpisok});
		$('#nomer')._select({
			width:180,
			spisok:GN_SEL,
			func:_zayavReportKupezSpisok
		});
	},
	_zayavReportKupezSpisok = function(v, id) {
		var send = {
			op:'zayav_nomer_report'
		};
		send[id] = v;
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				$('.left').html(res.spisok);
				$('#nomer')
					._select(res.nomer_spisok)
					._select(res.nomer);
			}
		}, 'json');
	},

	_zayavTovarZakazAdd = function(t, tovar_id) {//добавление товара в заказ в заявке
		if(t.hasClass('_busy'))
			return;

		t.addClass('_busy');

		var send = {
			op:'tovar_zakaz',
			tovar_id:tovar_id,
			count:1,
			zayav_id:ZI.id
		};
		$.post(AJAX_MAIN, send, function(res) {
			t.removeClass('_busy');
			if(res.success)
				t.addClass('dn')
				 .prev().removeClass('dn')
				 .attr('val', res.id);
		}, 'json');
	},
	_zayavTovarZakazRemove = function(t) {//удаление товара из заказа в заявке
		var p = t.parent();

		if(t.hasClass('vh'))
			return;

		t.addClass('vh');
		p.addClass('_busy');

		var send = {
			op:'tovar_zakaz_del',
			id:p.attr('val'),
			zayav_id:ZI.id
		};
		$.post(AJAX_MAIN, send, function(res) {
			t.removeClass('vh');
			p.removeClass('_busy');
			if(res.success) {
				p.addClass('dn');
				p.next().removeClass('dn');
			}
		}, 'json');
	},
	
	_zayavAttachCancel = function(v) {//отмена прикрепления документа
		var html =
				'<div class="_info">' +
					'Прикрепление документа будет отмечено как необязательное. ' +
					'При поиске заявок, если установлена галочка <u>Документ не прикреплён</u>, данная заявка выводиться не будет.' +
					'<br />' +
					'Нужно обязательно <b>указать причину</b> отметки.' +
				'</div>' +
				'<div class="mt15 center fs16">Применить необязательное<br />прикрепление документа.</div>' +
				'<table class="bs10">' +
					'<tr><td class="label">Причина:*' +
						'<td><input type="hidden" id="reason" />' +
				'</table>',
			dialog = _dialog({
				width:430,
				head:'Необязательное прикрепление документа',
				content:html,
				butSubmit:'Применить',
				submit:submit
			});

		$('#reason')
			._select({
				width:320,
				write_save:1
			})
			._select('process');
		
		//загрузка введённых причин
		var send = {
			op:'zayav_attach_cancel_reason_load',
			v:'attach' + (v || '')
		};
		$.post(AJAX_MAIN, send, function(res) {
			$('#reason')._select('cancel');
			if(res.success)
				$('#reason')
					._select(res.spisok)
					._select('focus');
		}, 'json');

		function submit() {
			var send = {
				op:'zayav_attach_cancel',
				zayav_id:ZI.id,
				v:'attach' + (v || ''),
				reason:$('#reason')._select('inp')
			};
			dialog.post(send);
		}
	},
	
	mainMenuZayavChart = function() {
		$('#zayav-chart-container')
			.height(300)
			.highcharts({
				chart:{
					type:'column'
				},
				title:{
					text:'Новые заявки за последние 30 дней'
				},
				xAxis:{
					categories:ZAYAV_CATEGORIES,
					labels:{
						style:{
							color:'#333'
						}
					}
				},
				yAxis:{
					title:{
						text:'Количество'
					},
			        stackLabels:{
				        enabled:true,
				        style:{
					        fontWeight:'bold',
					        color:'black'
				        }
			        }
				},
				plotOptions:{
					column:{
						stacking:'normal',
						dataLabels:{
							enabled:false
						}
					}
				},
				series:ZAYAV_SERIES
			});
	},

	_zayavStat = function(service_id) {//график заявок по конкретному виду деятельности
		var dialog = _dialog({
				top:20,
				width:750,
				head:'Статистика по заявкам',
				load:1,
				butSubmit:'',
				butCancel:'Закрыть'
			}),
			SERVICE_ID = service_id,
			YEAR = 0,
			YEAR_COMPARE = 0,
			TOVAR_CAT_ID = 0,
			TOVAR_ID = 0;

		statLoad();

		function statLoad() {
			var send = {
				op:'zayav_stat_load',
				service_id:SERVICE_ID,
				year:YEAR,
				year_compare:YEAR_COMPARE,
				tovar_cat_id:TOVAR_CAT_ID,
				tovar_id:TOVAR_ID
			};
			$('#zayav-stat').css('opacity', .4);
			dialog.load(send, loaded);
		}
		function loaded(res) {
			dialog.content.html(res.html);
			$('#filter-service')._menuDop({
				type:2,
				spisok:res.filterService,
				func:function(v) {
					SERVICE_ID = v;
					YEAR = 0;
					YEAR_COMPARE = 0;
					TOVAR_CAT_ID = 0;
					statLoad();
				}
			});
			$('#filter-year')._menuDop({
				type:2,
				spisok:res.filterYears,
				func:function(v) {
					YEAR = v;
					statLoad();
				}
			});
			$('#filter-year-compare')._menuDop({
				type:2,
				cancel:1,
				spisok:res.filterYears,
				func:function(v) {
					YEAR_COMPARE = v;
					statLoad();
				}
			});
			$('#filter-tovar-cat')._select({
				width:200,
				title0:'не указана',
				spisok:res.filterTovarCat,
				func:function(v) {
					TOVAR_CAT_ID = v;
					statLoad();
				}
			});
			$('#filter-tovar-id').tovar({
				small:1,
				zayav_use:1,
				func:function(v) {
					TOVAR_ID = v;
					statLoad();
				}
			});
			$('#zayav-stat')
				.height(250)
				.highcharts({
					chart:{
						type:'column'
					},
					title:{
						text:'Новые заявки по месяцам за ' + (res.year_compare && res.year_compare != res.year ? '<b>' + res.year_compare + '</b>, ' : '') + '<b>' + res.year + '</b> год'
					},
					xAxis:{
						categories:MONTH_CUT,
						labels:{
							style:{
								color:'#333'
							}
						},
                        crosshair: true
					},
					yAxis:{
						title:{
							text:'Количество'
						},
						stackLabels:{
							enabled:true,
							style:{
								fontWeight:'bold',
								color:'black'
							}
						}
					},
					plotOptions:{
						series:{
							animation: {
				                duration:300
                            },
							dataLabels:{
								enabled:true,
								formatter:function() {
									return this.y || ''
								}
							}
						}
					},
					tooltip:{
						enabled:true,
						shared:true
					},
					series:res.series
				});
		}
	};

$.fn.zayavTovarPlace = function(o) {//местонахождение товара
	var t = $(this),
		spisok = _copySel(ZAYAV_TOVAR_PLACE_SPISOK);

	t.val(-1);

	o = $.extend({
		func:function() {}
	}, o);

	spisok.push({
		uid:0,
		title:'<div id="place-other-div">' +
				'другое:<input type="text" id="place_other" class="dn" />' +
			  '</div>'
	});

	t._radio({
		spisok:spisok,
		light:1,
		func:function(v) {
			$('#place_other')[(v ? 'add' : 'remove') + 'Class']('dn');
			if(!v)
				$('#place_other').val('').focus();
			t.val(v);
			t.attr('val', '');
			o.func();
		}
	});

	$('#place_other').keyup(function() {
		t.attr('val', $(this).val());
	});

	return t;
};
$.fn.zayavSrok = function(o, v) {
	var t = $(this),
		attr_id = t.attr('id'),
		win = attr_id + '_srok',
		day = t.val(),
		mon,
		dialog,
		content;

	if(!attr_id)
		return;

	if(!day) {
		day = '0000-00-00';
		t.val(day);
	}

	if(typeof o == 'string') {
		if(o == 'executer_id') {
			window[win].executerSet(v);
			return true;
		}
		window[win].val(o);
		window[win].dSet(o);
		return true;
	}

	o = $.extend({
		service_id:0,
		zayav_spisok:0,//если список заявок
		executer_id:0,
		func:function() {},
		func_executer:function() {} //функция, которая выполняется при изменении исполнителя в календаре
	}, o);

	t.after('<div class="zayav-srok"><a></a></div>');

	var TA = t.next().find('a');
	TA.click(function() {//открытие календаря
		dialog = _dialog({
			top:20,
			width:580,
			head:'Календарь заявок',
			load:1,
			butSubmit:''
		});
		content = dialog.content;
		calendarUpdate();
	});

	daySet();

	function daySet() {//установка названия дня в ссылку выбора
		var name = 'не указан';
		if(day != '0000-00-00') {
			var d = day.split('-'),
				year = _num(d[0]),
				mon = _num(d[1]),
				dd = _num(d[2]),
				week = new Date(year, mon - 1, dd).getDay();
			name = WEEK_NAME[week] + '. ' + dd + ' ' + MONTH_DAT[mon];
		}
		TA.html(name);
	}
	function calendarUpdate() {//обновление календаря
		var head = content.find('#fc-head');
		if(head.hasClass('_busy'))
			return;
		head.addClass('_busy');
		var send = {
			op:'zayav_srok_open',
			service_id:o.service_id,
			mon:mon,
			day:day,
			executer_id:o.executer_id,
			zayav_spisok:o.zayav_spisok
		};
		$.post(AJAX_MAIN, send, function(res) {
			head.removeClass('_busy');
			if(res.success) {
				content.html(res.html);
				calendarFunc();
			}
		}, 'json');
	}
	function calendarFunc() {//функции, которые применяются при обновлении календаря
		content.find('.ch').click(function() {
			mon = $(this).attr('val');
			calendarUpdate();
		});
		content.find('.d:not(.old),#fc-cancel,.fc-old-sel').click(function() {
			day = $(this).attr('val');
			t.val(day);
			daySet();
			dialog.close();
			o.func(day, attr_id);
		});
		content.find('#fc-executer_id')._select({
			title0:'все сотрудники',
			spisok:_zayavExecuter(),
			func:function(v) {
				o.executer_id = v;
				o.func_executer(v);
				calendarUpdate();
			}
		});
	}

	t.dSet = function(v) {
		day = v;
		daySet();
	};
	t.executerSet = function(v) {
		o.executer_id = v;
	};
	window[win] = t;
	return t;
};
$.fn.cartridge = function(o) {
	var t = $(this),
		id = t.attr('id'),
		num = 1,
		n;

	if(typeof o == 'string') {
		if(o == 'get') {
			var units = t.find('.icar'),
				send = [],
				v;
			for(n = 0; n < units.length; n++) {
				v = units.eq(n).val();
				if(v == 0)
					continue;
				send.push(v);
			}
			return send.join();
		}
	}
	if(typeof o == 'object')
		for(var i = 0; i < o.length; i++) {
			add(o[i]);
			num++;
		}

	add();
	function add(v) {
		t.append('<input type="hidden" class="icar" id="car' + num + '" ' + (v ? 'value="' + v + '" ' : '') + '/>');
		$('#car' + num)._select({
			width:170,
			bottom:4,
			title0:'картридж не выбран',
			write:1,
			spisok:CARTRIDGE_SPISOK,
			func:add_test,
			funcAdd:function(id) {
				cartridgeNew(id, add_test);
			}
		});
	}
	function add_test(v) {//проверка, все ли картриджи выбраны, затем добавлять новое поле
		if(!v)
			return;
		var units = t.find('.icar');
		for(n = 0; n < units.length; n++)
			if(units.eq(n).val() == 0)
				return;
		num++;
		add();
	}
};
$.fn.gnGet = function(o, o1) {//номера газет
	var t = $(this),
		attr_id = t.attr('id'),
		win = attr_id + '_gnGet';

	if(!attr_id)
		return;

	if(typeof o == 'string') {
		if(o == 'cena')
			window[win].cenaSet(o1);
		if(o == 'update')
			window[win].update();
		if(o == 'summa')
			window[win].summa(o1);
		if(o == 'manual')
			window[win].manual(o1);
		if(o == 'skidka')
			window[win].skidka(o1);

		return t;
	}

	o = $.extend({
		show:4,     // количество номеров, которые показываются изначально, а также отступ от уже выбранных
		add:8,      // количество номеров, добавляющихся к показу
		gns:{},   // выбранные номера (для редактирования)
		dop_title0:'',//Доп. параметр не указан           Полоса не указана
		dop_spisok:[],
		four_free:0,// каждый 4-й номер бесплатно
		pn_show:0,  // показывать выбор номеров полос
		skidka:0,
		manual:0,   // установлена ли галочка для ввода общей суммы вручную
		summa:0,    // Нужно если установлена галочка manual: общая стоимость всех объявлений, получаемая снаружи. Затем она делится на все активные номера.
		func:function() {}
	}, o);

	var pix = 21, // высота поля номера в пикселях
		html =
			'<div id="gnGet">' +
				'<table>' +
					'<tr><td><div id="dopLinks">' +
								'<a class="link" val="4">Месяц</a>' +
								'<a class="link" val="13">3 месяца</a>' +
								'<a class="link" val="26">Полгода</a>' +
								'<a class="link" val="52">Год</a>' +
							'</div>' +
						'<td><input type="hidden" id="dopDef">' +
				'</table>' +
				'<table class="gn-spisok">' +
					'<tr><td id="selCount">' +
						'<td><div id="gns"></div>' +
				'</table>' +
			'</div>';
	t.after(html);

	$(document)
		.off('click', '#darr')
		.on('click', '#darr', function() {// Разворачивание списка
			var t = $(this),
				begin = _num(t.prev().find('.gns-week').attr('val'));

			if(!begin)
				return;

			gnsPrint(begin + 1, o.add);
		})
		.off('click', '.gns-week')
		.on('click', '.gns-week', function() {// Действие по нажатию на номер газеты
			dopMenuA.removeClass('sel'); //очищение выделения периода, если был выбран
			var th = $(this),
				sel = !th.hasClass('gnsel'),
				v = th.attr('val');

			if(th.hasClass('schet'))
				return;

			th[(sel ? 'add': 'remove') + 'Class']('gnsel');
			th.removeClass('prev');
			th.find('.gnid').val(0);

			gnsDop(v, sel);
			cenaSet();
			gnsValUpdate();
		});

	var gnGet = $('#gnGet'),                 // Основная форма
		gns = gnGet.find('#gns'),            // Список номеров
		dopMenuA = gnGet.find('#dopLinks a'),// Список меню с периодами
		dopDef = gnGet.find("#dopDef"),      // Выбор дополнительных параметров по умолчанию
		selCount = gnGet.find('#selCount'),  // Количество выбранных номеров
		gnCena = 0;   // Цена за один номер

	// Выделение выбранных номеров при редактировании
	var gn_sel_end = 0,
		count = 0;
	for(var n in o.gns)
		gn_sel_end = _num(n);

	if(gn_sel_end)
		for(n in GN_ASS) {
			n = _num(n);
			if(n > gn_sel_end)
				break;
			if(n < GN_FIRST)
				continue;
			count++;
		}
	gnsPrint(GN_FIRST, count + o.show);
	gnsDopAll();
	gnsValUpdate();

//	o.gns = {};

	dopMenuA.click(function() {// выбор номеров на месяц, 3 месяца, полгода и год, начиная сначала
		var t = $(this),
			sel = !t.hasClass('sel'),
			v = sel ? _num(t.attr('val')) : 0;

		dopMenuA.removeClass('sel');
		if(sel)
			t.addClass('sel');

		gnsPrint(GN_FIRST, v + o.show);

		$('.gns-week').addClass(function(i) {
			return i < v ? 'gnsel' : '';
		});

		gnsAA(function(sp, nn) {
			gnsDop(nn, 1);
		});

		cenaSet();
		gnsValUpdate();
	});
	function gnsPrint(start, count) {// Вывод списка номеров
		/*
			start - первый номер, с которого выводить
			count - количество номеров к показу
		*/

		//если первый номер не указан, значит первым будет указанный в глобальных настройках
		if(!start)
			start = GN_FIRST;

		if(!count)
			count = o.show;

		var polosa = _toAss(GAZETA_POLOSA_SPISOK),
			html = '';

		gnGet.find('#darr').remove();

		for(var n in GN_ASS) {
			if(!count)
				break;

			var sp = GN_ASS[n],
				prev = ' curP',
				schet = 0,
				dop = 0,
				pn = 0,
				skidka = o.skidka,
				cena = '',
				gnid = 0;

			if(n < start)
				continue;

			if(o.gns[n]) {
				schet = o.gns[n][5];
				prev = ' gnsel ' + (schet ? 'schet' : 'prev curP');
				dop = o.gns[n][0];
				pn = o.gns[n][1];
				skidka = o.gns[n][2];
				cena = o.gns[n][3];
				gnid = o.gns[n][4];
			}

			count--;

			html +=
				'<table><tr>' +
					'<td><table class="gns-week' + prev + '" val="' + n + '">' +
							'<tr><td class="td"><b>' + sp.week + '</b><span class="g">(' + sp.gen + ')</span>' +
								'<td class="td r">' +
									'<span class="g">выход</span> ' + sp.txt +
									'<input type="hidden" id="skidka' + n + '" value="' + skidka + '" />' + //скидка в процентах
									'<input type="hidden" id="exact' + n + '" value="' + cena + '" />' + //точная цена: миллионные доли
									'<input type="hidden" class="gnid" id="gnid' + n + '" value="' + gnid + '" />' +    //id номера, если редактируется
								'<td class="cena" id="cena' + n + '">' + (Math.round(cena * 100) / 100) +
						'</table>' +
					'<td class="vdop">' +
						(schet && polosa[dop] ? polosa[dop] : '') +
						'<input type="hidden" id="vdop' + n + '" value="' + dop + '" /> ' +
						'<input type="hidden" id="pn' + n + '" value="' + pn + '" />' +
				'</table>';
		}
		html += n != GN_LAST ? '<div id="darr">&darr; &darr; &darr;</div>' : '';
		gns[start == GN_FIRST ? 'html' : 'append'](html);
		gns.animate({height:(gns.find('.gns-week').length * pix) + 'px'}, 300);
		gnsAA(function(sp, nn) {
			gnsDop(nn, 1);
			gnsDopPolosa(nn);
		});
	}
	function gnsDopAll() {//выпадающий список с дополнительным параметром для установки всем выбранным номерам
		dopDef._dropdown(!o.dop_spisok.length ? 'remove' : {
			head:'всем...',
			headgrey:1,
			title0:o.dop_title0,
			nosel:1,
			spisok:o.dop_spisok,
			func:function(id) {
				gnsAA(function(sp, nn, prev) {
					if(!prev) {
						$('#vdop' + nn)._dropdown(id);
						gnsDopPolosa(nn);
					}
				});
				cenaSet();
				gnsValUpdate();
			}
		});
	}
	function gnsDop(nn, sel) {//выпадающий список с дополнительным параметром для конкретного номера газеты
		if(!o.dop_spisok.length)
			return;
		if(!sel)
			$('#vdop' + nn).val(0);
		$('#vdop' + nn)._dropdown(!sel ? 'remove' : {
			title0:o.dop_title0,
			spisok:o.dop_spisok,
			func:function() {
				gnsDopPolosa(nn);
				cenaSet();
				gnsValUpdate();
			}
		});
	}
	function gnsDopPolosa(nn) {//выпадающий список с номерами полос, если нужен
		if(!o.pn_show)
			return;
		var dop_id = _num($('#vdop' + nn).val());
		if(!dop_id || !GAZETA_POLOSA_POLOSA[dop_id]) {
			$('#pn' + nn).val(0)._dropdown('remove');
			return;
		}
		var pc = [];
		for(n = 2; n < GN_ASS[nn].pc; n++)
			pc.push({uid:n,title:n + '-я'});
		$('#pn' + nn)._dropdown({
			title0:'??',
			spisok:pc,
			func:gnsValUpdate
		});
	}
	function gnsAA(func, all) {// gnsActionActive: Применение действия к выбранным номерам
		var week = $('.gns-week'),
			gnsel = [];
		for(var n = 0; n < week.length; n++) {
			var sp = week.eq(n),
				sel = sp.hasClass('gnsel'),
				prev = sp.hasClass('prev'),
				schet = sp.hasClass('schet'),
				v = _num(sp.attr('val'));

			if(schet)
				continue;

			if(all || sel)
				func(sp, v, prev);
		}
	}
	function gnsClear() {// Очистка выбранных номеров
		gnGet.find('.gnsel').removeClass('gnsel');
		gnsValUpdate();
	}
	function cenaSet() {// Установка цены в выбранные номера
		var four = o.four_free ? 4 : 1000,
			count = 0,
			cena = 0;
		//подсчёт количества объявлений, в которые нужно вписать стоимость (с учётом бесплатного номера)
		if(o.manual) {
			gnsAA(function(sp, nn, prev) {
				if(!prev) {
					four--;
					if(!four)
						four = 4;
					else
						count++;
				}
			});
			cena = Math.round((o.summa / count) * 1000000) / 1000000;
		}

		four = o.four_free ? 4 : 1000;
		gnsAA(function(sp, nn, prev) {
			if(!prev) {
				var c = 0,
					dop = _num($('#vdop' + nn).val());
				four--;
				if(!four) {
					four = 4;
					c = 0;
				} else
					if(o.manual)
						c = cena;
					else {
						//Определение объявление это или реклама производится на основании pn_show
						if(o.pn_show) {
							c = gnCena && dop ? gnCena * GAZETA_POLOSA_CENA[dop] : 0;
							if(o.skidka)
								c = c - c / 100 * o.skidka
						} else
							c = gnCena ? gnCena + (dop ? GAZETA_OBDOP_CENA[dop] : 0) : 0;
					}
				$('#cena' + nn).html(Math.round(c * 100) / 100);
				$('#exact' + nn).val(c);
			}
		});
	}
	function gnsValUpdate() {//обновление выбранных значений номеров
		var arr = [],
			sum = 0,
			count = 0;
		gnsAA(function(sp, v, prev) {
			var dop = _num($('#vdop' + v).val()),
				pn = _num($('#pn' + v).val()),
				skidka = _num($('#skidka' + v).val()),
				c = $('#exact' + v).val() * 1;
//				gnid = _num($('#gnid' + v).val());//todo id номера при редактировании. пока не используется
			arr.push(v + ':' + dop + ':' + pn + ':' + skidka + ':' + c);

			if(!prev)
				sum += c;

			count++;
		});

		t.val(arr.join('###'));
//		$('#ze-note').val(t.val()); //todo удалить

		o.func({
			summa:Math.round(sum * 100) / 100,
			skidka_sum:o.skidka ? Math.round((sum / (100 - o.skidka) * 100 - sum) * 100) / 100 : 0
		});

		//вывод количества выбранных номеров
		var countHtml = 'Выбран' + _end(count, ['', 'о']) + ' ' +
						 count + ' номер' + _end(count, ['', 'a', 'ов']) +
						 '<a>очистить</a>';
		selCount
			.html(count ? countHtml : '')
			.find('a').click(function() {
				gnsClear();
				gnsPrint();
				dopMenuA.removeClass('sel');
			});
	}

	t.cenaSet = function(c) {
		gnCena = c || 0;
		cenaSet();
		gnsValUpdate();
	};
	t.update = function() {
		cenaSet();
		gnsValUpdate();
	};
	t.summa = function(v) {
		o.summa = v;
	};
	t.manual = function(v) {
		o.manual = v;
	};
	t.skidka = function(v) {
		o.skidka = v;
		gnsAA(function(sp, nn, prev) {
			if(!prev)
				$('#skidka' + nn).val(v);
		});
		t.update();
	};

	window[win] = t;
	return t;
};

$(document)
	.on('click', '#_zayav .vk.red', function() {//очистка фильтра заявок
		$('#find')._search('clear');    ZAYAV.find = '';
		$('#sort')._radio(1);           ZAYAV.sort = 1;
		$('#desc')._check(0);           ZAYAV.desc = 0;
		$('#ob_onpay')._check(0);       ZAYAV.ob_onpay = 0;

		$('#sel')
			.html('Некоторые статусы')
			.css('background', '#fff');
		ZAYAV.status_ids = ZAYAV.status_def;

		$('#finish').zayavSrok('0000-00-00');ZAYAV.finish = '0000-00-00';
		$('#finish').zayavSrok('executer_id', 0);

		$('#paytype')._radio(0);		ZAYAV.paytype = 0;
		$('#noschet')._check(0);		ZAYAV.noschet = 0;
		$('#nofile')._check(0);		    ZAYAV.nofile = 0;
		$('#noattach')._check(0);		ZAYAV.noattach = 0;
		$('#noattach1')._check(0);		ZAYAV.noattach1 = 0;
		$('#executer_id')._select(0);	ZAYAV.executer_id = 0;
		$('#tovar_cat_id')._select(0);	ZAYAV.tovar_cat_id = 0;
		$('#tovar_id').tovar(0);	    ZAYAV.tovar_id = 0;
		$('#tovar_place_id')._select(0);ZAYAV.tovar_place_id = 0;

		$('#gn_year')._yearLeaf('cur');     ZAYAV.gn_year = (new Date()).getFullYear();
		$('#gn_nomer_id')._radio(GN_FIRST); ZAYAV.gn_nomer_id = GN_FIRST;
		$('#gn_polosa')._select(0);         ZAYAV.gn_polosa = 0;
		$('#gn_polosa_color')._radio(0);    ZAYAV.gn_polosa_color = 0;

		$('#deleted')._check(0);		ZAYAV.deleted = 0;
		$('#deleted_only')._check(0);	ZAYAV.deleted_only = 0;

		$('#f56')._check(0);		ZAYAV.f56 = 0;
		$('#f57')._check(0);		ZAYAV.f57 = 0;
		$('#f59')._check(0);		ZAYAV.f59 = 0;
		$('#f60')._check(0);		ZAYAV.f60 = 0;

		_zayavSpisok();
	})

	.on('click', '#zayav-status-filter', function(e) {//действие при нажатии на фильтр-статус
		var t = $(this),
			et = $(e.target),
			tab = t.find('#status-tab');

		//раскрытие списка
		if(et.attr('id') == 'sel') {
			tab.show();
			$(document).on('click.status_tab', function() {
				tab.hide();
				$(document).off('click.status_tab');
			});
			return;
		}

		//нажатие на таб с галочкой
		if(et.hasClass('td-check')) {
			e.stopPropagation();
			var inp = et.find('input'),
				attr_id = inp.attr('id'),
				v = _bool(inp.val()),
				sel = false;
			$('#' + attr_id)._check(v ? 0 : 1);
			if(attr_id == 'st0')
				sel = v ? 'no' : 'all';
			zsfCheck(sel);
			return;
		}

		//нажатие на чекбокс
		if(et.hasClass('_check')) {
			e.stopPropagation();
			var inp = et.find('input'),
				attr_id = inp.attr('id'),
				v = _bool(inp.val()),
				sel = false;
			$('#' + attr_id)._check(v ? 1 : 0);
			if(attr_id == 'st0')
				sel = v ? 'all' : 'no';
			zsfCheck(sel);
			return;
		}

		//нажатие на название или количество
		if(et.hasClass('td-name')) {
			var inp = _parent(et).find('input'),
				id = inp.attr('id').split('st')[1];
			$('#sel')
				.html(ZAYAV_STATUS_NAME_ASS[id])
				.css('background', '#' + ZAYAV_STATUS_COLOR_ASS[id]);
			zsfCheck(id);
			return;
		}

		//составление списка id выбранных чекбоксов
		function zsfCheck(sel_id) {
			var check = tab.find('._check'),
				arr = 0;
			for(var n = 0; n < check.length; n++) {
				var sp = check.eq(n),
					inp = sp.find('input'),
					id = _num(inp.attr('id').split('st')[1]);
				if(sel_id)
					$('#st' + id)._check(sel_id == id || sel_id == 'all' ? 1 : 0);
				if(!_num(inp.val()))
					continue;
				if(!id)
					continue;
				arr = arr + ',' + id;
			}
			ZAYAV.status_ids = arr;
			_zayavSpisok();
		}
	})
/*
	.on('click', '#zayav-status-filter tr', function() {
		var id = _num($(this).attr('val'));
		ZAYAV.status = id;
		_zayavSpisok();
		$('#zayav-status-filter')[(id ? 'add' : 'remove') + 'Class']('us');

		$('#sel')
			.html(ZAYAV_STATUS_NAME_ASS[id])
			.css('background', '#' + ZAYAV_STATUS_COLOR_ASS[id]);
	})
*/
	.on('click', '.zayav-expense-toggle', function() {//сворачивание/разворачивание списка расходов по заявке
		var t = $(this),
			tab = t.next(),
			v;
		tab.slideToggle(200, function() {
			v = tab.css('display') == 'none' ? 1 : 0;
			t.find('a').html(!v ? 'свернуть' : 'развернуть');
			_cookie('zet', v);
		});
	})
	.on('click', '._zayav-unit', function() {
		var id = $(this).attr('val');
		_scroll('set', 'u' + id);
		location.href = URL + '&p=45&id=' + id;
	})
	.on('mouseenter', '._zayav-unit', function() {
		var t = $(this),
			msg = t.find('.note').html();
		if(msg)
			t.vkHint({
				width:150,
				msg:msg,
				ugol:'left',
				top:10,
				left:t.width() + 43,
				show:1,
				indent:5,
				delayShow:500
			});
	})

	.on('click', '#zayav-status-button', _zayavStatus)

	.on('click', '#zayav-tovar-place-change', function() {
		var html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label r w175">Текущее местонахождение:<td><b>' + _toAss(ZAYAV_TOVAR_PLACE_SPISOK)[ZI.place_id] + '</b>' +
					'<tr><td class="label r topi">Новое местонахождение:<td><input type="hidden" id="tovar-place" />' +
				'</table>',

			dialog = _dialog({
				width:420,
				head:'Изменение местонахождения товара',
				content:html,
				butSubmit:'Сохранить',
				submit:submit
			});

		$('#tovar-place').zayavTovarPlace();

		function submit() {
			var send = {
				op:'zayav_tovar_place_change',
				zayav_id:ZI.id,
				place_id:$('#tovar-place').val(),        // 12
				place_other:$('#tovar-place').attr('val')// 12
			};

			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					document.location.reload();
				} else
					dialog.abort();
			}, 'json');
		}
	})
	.on('mouseenter', '.zayav-tovar-avai', function() {//показ наличия товара при наведении
		var t = $(this);

		t.vkHint({
			top:-170,
			left:-421,
			width:530,
			ugol:'right',
			indent:150,
			msg:'<div class="zta-hint mh150 _busy">&nbsp;</div>',
			show:1,
			delayHide:500,
			remove:1
		});

		var tovar_id = _parent($(this), '.unit').attr('val'),
			send = {
				op:'zayav_tovar_avai_load',
				tovar_id:tovar_id
			};
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('.zta-hint')
						.removeClass('_busy')
						.html(res.html);
					$('.zta-but').click(submit);
				}
			},'json');

		function submit() {
			var but = $(this),
				send = {
					op:'zayav_tovar_set',
					zayav_id:ZI.id,
					avai_id:_num($('#tovar-avai-id').val())
				};

			if(but.hasClass('_busy'))
				return;

			if(but.hasClass('grey'))
				return;

			but.addClass('_busy');
			$.post(AJAX_MAIN, send, function(res) {
				but.removeClass('_busy');
				if(res.success) {
					but.addClass('grey');
					_msg();
					location.reload();
				}
			},'json');
		}

	})

	.on('click', '#_zayav-expense .img_del', function() {
		var t = $(this);
		_dialogDel({
			id:t.attr('val'),
			head:'расхода заявки',
			op:'zayav_expense_del',
			func:function(res) {
				$('#_zayav-expense').html(res.html);
			}
		});
	})

	.on('click', '#zayav-cartridge .cart-edit', function() {//выполнение действия надо картриджем
		var t = $(this);
		while(t[0].tagName != 'TR')
			t = t.parent();
		var id = t.attr('val'),
			cart_id = t.find('.cart_id').val(),
			filling = t.find('.filling').val(),
			restore = t.find('.restore').val(),
			chip = t.find('.chip').val(),
			cost = _cena(t.find('.cost').html()),
			prim = t.find('u').html(),
			html =
				'<table class="bs10">' +
					'<tr><td class="label w100 r">Картридж:<td><input type="hidden" id="cart_id" value="' + cart_id + '" />' +
					'<tr><td class="label topi r">Действие:' +
						'<td><input type="hidden" id="filling" value="' + filling + '" />' +
							'<input type="hidden" id="restore" value="' + restore + '" />' +
							'<input type="hidden" id="chip" value="' + chip + '" />' +
					'<tr><td class="label r">Стоимость работ:<td><input type="text" class="money" id="cost" value="' + cost + '" /> руб.' +
					'<tr><td class="label r">Примечание:<td><input type="text" id="prim" class="w250" value="' + prim + '" />' +
				'</table>',
			dialog = _dialog({
				width:430,
				top:30,
				head:'Действия по картриджу',
				content:html,
				butSubmit:'Сохранить',
				submit:submit
			});
		$('#cart_id')._select({
			width:170,
			write:1,
			spisok:CARTRIDGE_SPISOK,
			func:costSet
		});
		$('#filling')._check({
			block:1,
			mt:4,
			name:'Заправка',
			func:costSet
		});
		$('#restore')._check({
			block:1,
			mt:4,
			name:'Восстановление',
			func:costSet
		});
		$('#chip')._check({
			block:1,
			mt:4,
			name:'Замена чипа',
			func:costSet
		});
		function costSet() {
			var c = 0,
				cart_id = _num($('#cart_id').val());
			if($('#filling').val() == 1)
				c = CARTRIDGE_FILLING[cart_id];
			if($('#restore').val() == 1)
				c += CARTRIDGE_RESTORE[cart_id];
			if($('#chip').val() == 1)
				c += CARTRIDGE_CHIP[cart_id];
			$('#cost').val(c);
		}
		function submit() {
			var send = {
				op:'zayav_cartridge_edit',
				id:id,
				cart_id:_num($('#cart_id').val()),
				filling:$('#filling').val(),
				restore:$('#restore').val(),
				chip:$('#chip').val(),
				cost:$('#cost').val(),
				prim:$('#prim').val()
			};
			if(!send.cart_id) {
				dialog.err('Не выбран картридж');
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					$('#zc-spisok').html(res.html);
					_msg();
				} else
					dialog.abort();
			}, 'json');
		}
	})
	.on('click', '#zayav-cartridge .cart-del', function() {
		var t = _parent($(this));
		_dialogDel({
			id:t.attr('val'),
			head:'картриджа',
			op:'zayav_cartridge_del',
			func:function() {
				t.remove();
			}
		});
	})

	.on('click', '#zayav-report .cols-div a', function() {//открытие и скрытие настройки колонок отчёта по заявкам
		var t = _parent($(this), '.cols-div'),
			show = t.hasClass('show');
		t.addClass('show');
		$(document).on('click.zr-cols-show', function() {
			t.removeClass('show');
			$(document).off('click.zr-cols-show');
		})
	})
	.on('click', '#zayav-report .cols-div ._check', function(e) {//отображение, скрытие колонок отчёта по заявкам
		e.stopPropagation();
		var send = {
			op:'zayav_report_cols_set',
			ids:_checkAll()
		};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				_zayavReportSpisok();
		}, 'json');
	})

	.ready(function() {
		if($('#_zayav-info').length) {
			$('.a-page').click(function() {
				var t = $(this);
				t.parent().find('.link').removeClass('sel');
				var i = t.addClass('sel').index();
				$('.page:first')[(i ? 'add' : 'remove') + 'Class']('dn');
				$('.page:last')[(!i ? 'add' : 'remove') + 'Class']('dn');
			});
			var name = [0], action = [0];

			if(SERVICE_ACTIVE_COUNT > 1) {
				name.push('Изменить категорию заявки');
				action.push(_zayavTypeChange);
			}

			name.push('Редактировать данные заявки'); action.push(_zayavEdit);

			if(ZI.pole[23]) {
				name.push('Добавить картриджи');
				action.push(_zayavCartridgeAdd);
			}
			if(ZI.pole[20]) {
				name.push('Распечатать квитанцию');
				action.push(function() {
					if(APP_ID == 3798718) {
						if(ZI.pole[23])
							location.href = URL + '&p=75&d=kvit_cartridge&id=' + ZI.id;
						else
							location.href = URL + '&p=75&d=kvit_comtex&id=' + ZI.id;
					} else
						_zayavKvit();
				});
			}
			if(ZI.pole[19]) {
				if(!DOG.id) {
					name.push('Договор на оказание услуг'); //template_id = 2
					action.push(_zayavDogovor2);
					name.push('Договор для продажи товара');//template_id = 1
					action.push(_zayavDogovor1);
				} else {
					name.push('Изменить данные договора');
					action.push(_zayavDogovorCreate);
					name.push('Расторгнуть договор');
					action.push(_zayavDogovorTerminate);
				}
			}
			if(ZI.pole[58]) {
				name.push(DOG.id ? 'Изменить данные договора' : 'Заключить договор');
				action.push(_zayavDogovorCreate);
				if(DOG.id) {
					name.push('Расторгнуть договор');
					action.push(_zayavDogovorTerminate);
				}
			}
			if(SCHET_PAY_USE) {
				name.push('Сформировать счёт на оплату');
				action.push(schetPayEdit);
			}
			if(ZI.pole[45]) {
				name.push('Изменить статус заявки');
				action.push(_zayavStatus);
			}
			name.push('Начислить');                     action.push(_accrualAdd);
			name.push('Принять платёж'); action.push(_incomeAdd);
			name.push('Возврат');                       action.push(_refundAdd);
			name.push('Добавить расход по заявке');     action.push(_zayavExpenseEdit);
			name.push('Новое напоминание');             action.push(_remindAdd);

			if(ZI.todel) {
				name.push('<tt>Удалить заявку</tt>');
				action.push(function() {
					_dialogDel({
						id:ZI.id,
						head:'заявки',
						info:'<u>Заявку можно удалить, если отсутствуют:</u><br />' +
							  '- платежи;<br />' +
							  '- возвраты;<br />' +
							  '- начисления з/п сотрудникам;<br />' +
							  '- заключённые договора;<br />' +
							  '- сформированные счета на оплату.',
						op:'zayav_del',
						func:function() {
							document.location.reload();
						}
					});
				});
			}

			var spisok = _toSpisok(name);
			spisok.splice(0,1);
			if(!ZI.deleted)
				$('#zayav-action')._dropdown({
					head:'Действие',
					nosel:1,
					spisok:spisok,
					func:function(v) {
						action[v]();
					}
				});
			$('#executer_id')._dropdown({
				title0:'не назначен',
				spisok: _zayavExecuter(),
				func: function (v, id) {
					var td = $('#' + id).parent(),
						send = {
							op: 'zayav_executer_change',
							zayav_id: ZI.id,
							executer_id: v
						};
					td.addClass('_busy');
					$.post(AJAX_MAIN, send, function (res) {
						td.removeClass('_busy');
						if(res.success) {
							_msg('Исполнитель изменён');
							ZI.executer_id = v;
						}
					}, 'json');
				}
			});

			if($('#srok').length)
				$('#srok').zayavSrok({
					service_id:ZI.service_id,
					executer_id:ZI.executer_id,
					func:function(day) {
						var send = {
							op:'zayav_srok_save',
							day:day,
							zayav_id:ZI.id
						};
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
							}
						}, 'json');
					}
				});
			$('#attach_id')._attach({
				type:'icon',
				title:'Прикрепить документ',
				table_name:'_zayav',
				table_row:ZI.id
			});
			$('#attach1_id')._attach({
				type:'icon',
				title:'Прикрепить документ',
				table_name:'_zayav',
				table_row:ZI.id,
				col_name:'attach1_id'
			});
			$('.attach-canceled').mouseover(function() {
				var t = $(this),
					v = t.attr('val');
				t.vkHint({
					show:1,
					msg:v,
					indent:60,
					top:-88,
					left:-14
				})
			});
		}
		if($('#zayav-report').length) {
			window._calendarFilter = function(v, id) {
				var erm = $('.zayav-erm'),
					year = v.split('-')[0],
					mon = v.split('-')[1];
				if(mon) {
					erm.attr('val', v);
					erm.find('b').html(MONTH_DEF[_num(mon)] + ' ' + year);
				}
				_zayavReportSpisok(v, id);
			};
			$('.zayav-erm').click(function() {
				var t = $(this);
				if(t.hasClass('lena')) {
					location.href = URL + '&p=75&d=erm_lena&mon=' + $(this).attr('val');
					return;
				}
				location.href = URL + '&p=75&d=erm&mon=' + $(this).attr('val');
			});
		}
		if($('#ze-attach-schet').length) {
			$('#find')._search({
				width:178,
				focus:1,
				txt:'Быстрый поиск...',
				enter:1,
				func:_zayavExpenseAttachSchetSpisok
			});
			$('#no_pay')._check(_zayavExpenseAttachSchetSpisok);
			$('#no_attach')._check(_zayavExpenseAttachSchetSpisok);
		}
	});
