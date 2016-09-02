var _zayavSpisok = function(v, id) {
		ZAYAV.op = 'zayav' + ZAYAV.service_id + '_spisok';
		_filterSpisok(ZAYAV, v, id);
		ZAYAV.op = 'zayav_spisok';
		$('.nofind')[(ZAYAV.find ? 'add' : 'remove') + 'Class']('dn');
		$('#deleted-only-div')[(ZAYAV.deleted ? 'remove' : 'add') + 'Class']('dn');
		$.post(AJAX_MAIN, ZAYAV, function(res) {
			if(res.success) {
				$('.result').html(res.all);
				$('#spisok').html(res.spisok);
			}
		}, 'json');
	},
	_zayavSpisokTovarNameFilter = function(dis) {//фильтр виды товаров (связан с фильтром конктерный товар)
		if(!$('#tovar_name_id').length)
			return;

		$('#tovar_name_id')._select({
			width:155,
			title0:'не выбрано',
			disabled:dis,
			spisok:ZAYAV_TOVAR_NAME_SPISOK,
			write:1,
			func:_zayavSpisok
		});
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
			client_adres = '', //адрес клиента для подстановки в строку Адрес
			equip_js = [],     //список комплектации для select, которые были не выбраны для конкретного товара
			equip_tovar_id = 0,//id товара, по которому будет формироваться комплектация
			dialog = _dialog({
				width:550,
				top:30,
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

		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				dialog.content.html(res.html);
				zLoaded();
			} else
				dialog.loadError();
		}, 'json');

		function zLoaded() {
			$('#ze-about')
				.autosize()
				.keyup(_zayavObCalc);

			// 5 - Клиент
			if(!$('#ze-client-name').length)
				$('#ze-client_id').clientSel({
					width:258,
					add:1,
					func:function(uid, id, item) {
						client_adres = uid ? item.adres : '';
						if(_num($('#client-adres').val()))
							$('#ze-adres').val(client_adres);
					}
				});

			// 4 - один товар
			$('#ze-tovar-one').tovar({
				set:0,
				func:equip
			});
			// 11 - несколько товаров
			$('#ze-tovar-several').tovar({set:0,several:1});

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
				if(!v)
					$('#ze-gn').gnGet('update');
			});

			// 16 - Расчёт
			$('#ze-pay_type')._radio({
				light:1,
				spisok:PAY_TYPE
			});

			// 38 - Номера выпуска
			$('#ze-gn').gnGet({
				dop_title0:'Доп. параметр не указан',
				dop_spisok:GAZETA_OBDOP_SPISOK,
				func:function(v) {
					if(_num($('#ze-sum_cost_manual').val()))
						return;
					$('#ze-sum_cost').val(v.summa);
				}
			});

			// 39 - Скидка
			$('#ze-skidka')._select({
				width:70,
				title0:'нет',
				spisok:ZAYAV_SKIDKA_SPISOK
			});

			// 40 - Рубрика и подрубрика
			$('#ze-rubric_id')._rubric();
		}

		function equip(v) {//вставка комплектации при выборе товара
			if(!$('.tr-equip').length)
				return;
			$('.tr-equip')[(v ? 'remove' : 'add') + 'Class']('dn');
			$('#ze-equip-spisok').html('&nbsp;');

			if(!v)
				return;

			var send = {
				op:'tovar_equip_load',
				tovar_id:v,
				ids_sel:zayav_id ? ZI.equip : ''
			};
			$('#ze-equip-spisok').addClass('_busy');
			$.post(AJAX_MAIN, send, function(res) {
				$('#ze-equip-spisok').removeClass('_busy');
				if(res.success) {
					$('#ze-equip-spisok').html(res.check);
					$('#equip-add').click(equipSel);
					equip_js = res.equip_js;
					equip_tovar_id = v;
				}
			}, 'json');
		}
		function equipSel() {//выбор новой комплектации для добавления
			$(this).remove();
			$('#ze-equip-spisok .vk')
				.removeClass('dn')
				.click(equipAdd);
			$('#equip_id')._select({
				width:177,
				title0:'выберите или введите новое',
				write:1,
				write_save:1,
				spisok:equip_js
			})._select('focus');
		}
		function equipAdd() {
			var t = $(this),
				send = {
					op:'tovar_equip_add',
					tovar_id:equip_tovar_id,
					equip_id:_num($('#equip_id').val()),
					equip_name:$('#equip_id')._select('inp'),
					ids_sel:equipGet(1)
				};

			if(!send.equip_id && !send.equip_name)
				return;

			if(t.hasClass('_busy'))
				return;
			t.addClass('_busy');
			$.post(AJAX_MAIN, send, function(res) {
				t.removeClass('_busy');
				if(res.success) {
					$('#ze-equip-spisok').html(res.check);
					$('#equip-add').click(equipSel);
					equip_js = res.equip_js;
				}
			}, 'json');
		}
		function equipGet(sel) {//получение id комплектаций. sel - только тех, у которых стоят галочки
			var check = $('#ze-equip-spisok ._check'),
				send = [];
			for(var n = 0; n < check.length; n++) {
				var eq = check.eq(n),
					inp = eq.find('input'),
					id = _num(inp.attr('id').split('eq')[1]),
					v = _num(inp.val());
				if(sel && !v)
					continue;
				send.push(id);
			}
			return send.join();
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
				rubric_id:$('#ze-rubric_id').val(),        // 40
				rubric_id_sub:$('#ze-rubric_id_sub').val(),// 40
				place_id:$('#tovar-place').val(),          // 12
				place_other:$('#tovar-place').attr('val'), // 12
				srok:$('#ze-srok').val(),               // 13
				note:$('#ze-note').val(),               // 14
				sum_cost:$('#ze-sum_cost').val(),       // 15
				pay_type:$('#ze-pay_type').val(),       // 16
				equip:equipGet(1),                      // 4:v1
				gn:$('#ze-gn').val()                    // 38
			};

			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					_scroll('set', 'u' + res.id);
					location.href = URL + '&p=zayav&d=info&id=' + res.id;
				} else {
					dialog.abort();
					dialog.err(res.text);
				}
			}, 'json');
		}
	},
	_zayavObCalc = function() {// Вычисление стоимости объявления
		var CALC = $('#ze-about-calc');
		if(!CALC.length)
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
	_zayavStatus = function() {//Изменение статуса заявки
		var spisok = '';
		for(var i = 0; i < ZAYAV_STATUS_NAME_SPISOK.length; i++) {
			var sp = ZAYAV_STATUS_NAME_SPISOK[i];
			if(sp.uid == ZI.status_id)
				continue;
			if(ZAYAV_STATUS_NOUSE_ASS[sp.uid])
				continue;
			if(ZAYAV_STATUS_NEXT[ZI.status_id] && !ZAYAV_STATUS_NEXT[ZI.status_id][sp.uid])
				continue;
			spisok += '<div class="st sp" val="' + sp.uid + '" style="background-color:#' + ZAYAV_STATUS_COLOR_ASS[sp.uid] + '">' +
						 sp.title +
						'<div class="about">' + ZAYAV_STATUS_ABOUT_ASS[sp.uid] + '</div>' +
					  '</div>'
		}

		var html =
			'<div id="zayav-status">' +
				'<table class="bs10">' +
					'<tr><td class="label">Текущий статус:' +
						'<td><div class="current" style="background-color:#' + ZAYAV_STATUS_COLOR_ASS[ZI.status_id] + '">' +
								ZAYAV_STATUS_NAME_ASS[ZI.status_id] +
							'</div>' +
				'</table>' +

				'<div id="new-tab">' +
					'<input type="hidden" id="status-new" />' +
					'<table class="bs10">' +
						'<tr><td class="label topi">Новый статус:<td>' + spisok +
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
		$('#remind-day')._calendar();
		$('.st').click(function() {
			var t = $(this),
				v = t.attr('val');

			$('#status-new').val(v);

			t.removeClass('sp');
			t.find('.about').slideUp(300);
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
			template_id:0,
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

		var html = '<table id="_zayav-dog-tab">' +
				'<tr><td class="label">Ф.И.О. клиента:<td><input type="text" id="fio" value="' + o.fio + '" />' +
				'<tr><td class="label">Адрес:<td><input type="text" id="adres" value="' + o.adres + '" />' +
				'<tr><td class="label">Паспорт:' +
					'<td>Серия:<input type="text" id="pasp_seria" maxlength="8" value="' + o.pasp_seria + '" />' +
						'Номер:<input type="text" id="pasp_nomer" maxlength="10" value="' + o.pasp_nomer + '" />' +
				'<tr><td><td><span class="l">Прописка:</span><input type="text" id="pasp_adres" value="' + o.pasp_adres + '" />' +
				'<tr><td><td><span class="l">Кем выдан:</span><input type="text" id="pasp_ovd" value="' + o.pasp_ovd + '" />' +
				'<tr><td><td><span class="l">Когда выдан:</span><input type="text" id="pasp_data" value="' + o.pasp_data + '" />' +
				'<tr><td class="label">Номер договора:<td><input type="text" id="nomer" maxlength="6" value="' + o.nomer + '" placeholder="' + o.nomer_next + '" />' +
				'<tr><td class="label">Дата заключения:<td><input type="hidden" id="data_create" value="' + o.data_create + '" />' +
				'<tr><td class="label">Сумма по договору:<td><input type="text" id="sum" class="money" maxlength="11" value="' + (o.sum ? o.sum : '') + '" /> руб.' +
				'<tr' + (o.avans_hide && !o.avans_invoice_id ? ' class="dn"' : '') + '>' +
					'<td class="label top">Авансовый платёж:' +
					'<td><input type="hidden" id="avans_check" />' +
						'<div id="avans_div"' + (!o.id ? ' class="dn"' : '') + '>' +
							'<input type="hidden" id="avans_invoice_id" value="' + o.avans_invoice_id + '" />' +
							'<input type="text" id="avans_sum" class="money" maxlength="11" value="' + o.avans_sum + '"' + (o.avans_hide ? ' disabled' : '') + ' /> руб. ' +
						'</div>' +
				'<tr><td colspan="2">' +
					'<a id="preview">Предварительный просмотр договора</a>' +
					'<form action="' + AJAX_MAIN + '" method="post" id="preview-form" target="_blank"></form>' +
				'</table>',
			dialog = _dialog({
				width:480,
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
				}
			});

		$('#avans_invoice_id')._select({
			width:190,
			block:1,
			disabled:o.avans_hide,
			title0:'Счёт не выбран',
			bottom:7,
			spisok:_invoiceIncomeInsert(),
			func:function() {
				$('#avans_sum').focus();
			}
		});
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
				template_id:DOG.template_id,
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
				invoice_id:_num($('#avans_invoice_id').val()),
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
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg('Договор заключен');
					document.location.reload();
				} else {
					dialog.abort();
					dialog.err(res.text);
				}
			}, 'json');
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

		sumFocus();

		function sumFocus() {
			$('#ze-sum').focus();
		}
		function catSelect(id) {
			$('.tr-count').addClass('dn');
			$('#ze-count-max').show();
			sumFocus();
			$('.tr-dop')[(id ? 'remove' : 'add') + 'Class']('dn');
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
					window.tsg = false;
					$('#ze-dop').tovar({
						open:1,
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
					window.tsg = false;
					$('#ze-dop').tovar({
						open:1,
						tovar_id_set:ZI.tovar_id,
						func:function(v, attr_id, sp) {
							$('#td-input').append(sp.articul);
							var avai_id = _num($('#td-input #ta-articul').val()),
								buy = avai_id ? sp.articul_arr[avai_id].sum_buy : 0;
							$('.tovar-avai-articul').css('margin-top', '-1px');
							$('#ze-measure').html(sp.measure);
							$('.tr-count').removeClass('dn');
							$('#td-input #ta-articul')._radio(function(aid) {
								$('#ze-dop').val(aid);
								$('#ze-count-max b').html(sp.articul_arr[aid].count);
								$('#ze-count').val(1);
								buy = sp.articul_arr[aid].sum_buy;
								$('#ze-sum').val(buy);
							});
							$('#ze-dop').val(avai_id);//id наличия

							$('#ze-count')
								.val(1)
								.select()
								.off('keyup')
								.keyup(function() {
									$('#ze-sum').val(Math.round(_ms($(this).val()) * buy));
								});

							$('#ze-count-max b').html(avai_id ? sp.articul_arr[avai_id].count : 1);
							$('#ze-sum').val(_cena(sp.sum_buy));
						},
						avai:1,
						del:0
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
				sum:_cena($('#ze-sum').val())
			};
			if(!send.cat_id) {
				dialog.err('Не указана категория');
				return;
			}

			switch(ZE_DOP_ASS[send.cat_id]) {
				case 1: break;
				case 2:
					send.dop = _num(send.dop);
					break;
				case 5:
					send.dop = _num(send.dop.split(':')[0]);
					if(!send.dop) {
						dialog.err('Не выбран товар');
						return;
					}
					if(!send.count) {
						dialog.err('Некорректно указано количество');
						$('#ze-count').focus();
						return;
					}
					break;
				case 3:
					send.dop = _num(send.dop);
					if(!send.dop) {
						dialog.err('Не выбран товар');
						return;
					}
					if(!send.count) {
						dialog.err('Некорректно указано количество');
						$('#ze-count').focus();
						return;
					}
					break;
				case 4: break;
			}

			if(!send.sum) {
				dialog.err('Некорректная сумма');
				sumFocus();
				return;
			}

			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					$('#_zayav-expense').html(res.html);
					_zayavExpenseEdit();
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
		window.open(URL + '&p=print&d=kvit_html&id=' + id, 'kvit', params);
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
	_zayavCartridgeSchet = function() {
		if(!_checkAll())
			return false;

		var	dialog = _dialog({
				head:'Получение информации о картриджах',
				load:1,
				butSubmit:''
			}),
			send = {
				op:'zayav_cartridge_ids',
				ids:_checkAll()
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				dialog.close();
				_schetEdit({
					edit:1,
					client_id:ZI.client_id,
					client:ZI.client_link,
					zayav_id:ZI.id,
					arr:res.arr,
					func:_zayavCartridgeSchetSet
				});
			} else
				dialog.loadError();
		}, 'json');
		return true;
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
$.fn.gnGet = function(o, o1) {
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

		return true;
	}


	o = $.extend({
		show:4,     // количество номеров, которые показываются изначально, а также отступ от уже выбранных
		add:8,      // количество номеров, добавляющихся к показу
		dop_title0:'',//Доп. параметр не указан           Полоса не указана
		dop_spisok:[],
		pn_show:0,  // показывать выбор номеров полос
		gns:null,   // выбранные номера (для редактирования)
		skidka:0,
		manual:0,   // установлена ли галочка для ввода общей суммы вручную
		func:function() {}
	}, o);

	var pix = 21, // высота поля номера в пикселях
		gns_begin = GN_FIRST,
		gns_end = gns_begin + o.show,
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
			gns_begin = gns_end;
			gns_end += o.add;
			gnsPrint();
		})
		.off('click', '.gns-week')
		.on('click', '.gns-week', function() {// Действие по нажатию на номер газеты
			dopMenuA.removeClass('sel'); //очищение выделения периода, если был выбран
			var th = $(this),
				sel = !th.hasClass('gnsel'),
				v = th.attr('val');
			th[(sel ? 'add': 'remove') + 'Class']('gnsel');
			th.removeClass('prev');

			gnsDopPrint(v, sel);
			cenaSet();
			gnsValUpdate();
/*
			GN_ASS[v].prev = 0;
			GN_ASS[v].sel = sel;
			GN_ASS[v].dop = 0;
			GN_ASS[v].pn = 0;
			if(o.dop_spisok.length) {
				if(o.pn_show)
					$('#pn' + v).val(0)._dropdown('remove');
			}
*/
		});

	var gnGet = $('#gnGet'),                 // Основная форма
		gns = gnGet.find('#gns'),            // Список номеров
		dopMenuA = gnGet.find('#dopLinks a'),// Список меню с периодами
		dopDef = gnGet.find("#dopDef"),      // Выбор дополнительных параметров по умолчанию
		selCount = gnGet.find('#selCount'),  // Количество выбранных номеров
		gnCena = 0,   // Цена за один номер
		gnSumma = 0,  // Итоговая сумма
		summa_manual = 0,
		skidka_sum = 0;

//	gnsClear();
/*
	if(o.gns) {// Выделение выбранных номеров при редактировании
		var max = 0;
		for(var n in o.gns) {
			if(n > GN_LAST)
				break;
			if(!GN_ASS[n])
				continue;
			var sp = GN_ASS[n];
			sp.sel = 1;
			sp.prev = 1;
			sp.cena = o.gns[n][0];
			sp.dop = o.gns[n][1];
			sp.pn = o.gns[n][2];
			max = n;
		}
		gnsPrint(1, max - GN_FIRST + 1);
	} else
*/
	gnsPrint();
	gnsDopAll();

	dopMenuA.click(function() {// выбор номеров на месяц, 3 месяца, полгода и год начиная сначала
		var t = $(this),
			sel = !t.hasClass('sel'),
			v = sel ? _num(t.attr('val')) : 0;

		dopMenuA.removeClass('sel');
		if(sel)
			t.addClass('sel');

		gnsPrint(1, v);

		$('.gns-week').addClass(function(i) {
			return i < v ? 'gnsel' : '';
		});

		if(o.dop_spisok.length)
			gnsAA(function(sp, nn) {
				gnsDopPrint(nn, 1);
/*
				if(o.pn_show && POLOSA_NUM[sp.dop]) {
					var pc = [];
					for(var n = 2; n < GN_ASS[sp.n].pc; n++)
						pc.push({uid:n,title:n + '-я'});
					$('#pn' + sp.n)._dropdown({
						title0:'??',
						spisok:pc,
						func:function(pn) {
							GN_ASS[sp.n].pn = pn;
						}
					});
				}
*/
			});

		cenaSet();
		gnsValUpdate();
	});
	function gnsPrint(first, count) {// Вывод списка номеров
		if(first) {// Список номеров выводится с самого начала, а не догружается
			gns_begin = GN_FIRST;
			gns_end = gns_begin + (count || 0) + o.show;
		}
		gnGet.find('#darr').remove();
		var html = '';
		for(var n = gns_begin; n < gns_end; n++) {
			if(n > GN_LAST)
				break;
			var sp = GN_ASS[n];
			if(!sp) { // если номер пропущен, тогда не выводится
				//end++;
				continue;
			}
			//(sp.sel ? ' gnsel' : '') + для редактирования
			//(sp.prev ? ' prev' : '') + для редактирования
			html +=
				'<table><tr>' +
					'<td><table class="gns-week" val="' + n + '">' +
							'<tr><td class="td"><b>' + sp.week + '</b><span class="g">(' + sp.gen + ')</span>' +
								'<td class="td"><span class="g">выход</span> ' + sp.txt +
								'<td class="cena" id="cena' + n + '">' +
						'</table>' +
					'<td class="vdop">' +
						'<input type="hidden" id="vdop' + n + '" value="' + sp.dop + '" /> ' +
						'<input type="hidden" id="pn' + n + '" value="' + sp.pn + '" />' +
				'</table>';
		}
		html += gns_end < GN_LAST ? '<div id="darr">&darr; &darr; &darr;</div>' : '';
		gns[first ? 'html' : 'append'](html);
		gns.animate({height:(gns.find('.gns-week').length * pix) + 'px'}, 300);
	}
	function gnsDopAll() {//выпадающий список с дополнительным параметром для установки всем выбранным номерам
		dopDef._dropdown(!o.dop_spisok.length ? 'remove' : {
			head:'всем...',
			headgrey:1,
			title0:o.dop_title0,
			nosel:1,
			spisok:o.dop_spisok,
			func:function(id) {
				gnsAA(function(sp, nn) {
					$('#vdop' + nn)._dropdown(id);
/*
					if(!sp.prev) {
						sp.dop = id;
						if(o.pn_show) {
							sp.pn = 0;
							if(POLOSA_NUM[id]) {
								var pc = [];
								for(var n = 2; n < GN_ASS[sp.n].pc; n++)
									pc.push({uid:n,title:n + '-я'});
							}
							$('#pn' + sp.n).val(0)._dropdown(!POLOSA_NUM[id] ? 'remove' : {
								title0:'??',
								spisok:pc,
								func:function(pn) {
									GN_ASS[sp.n].pn = pn;
								}
							});
						}
					}
*/
				});
				cenaSet();
				gnsValUpdate();
			}
		});
	}
	function gnsDopPrint(v, sel) {//выпадающий список с дополнительным параметром для конкретного номера газеты
		$('#vdop' + v)
			.val(0)
			._dropdown(!sel ? 'remove' : {
				title0:o.dop_title0,
				spisok:o.dop_spisok,
				func:function(id) {
					cenaSet();
					gnsValUpdate();
	/*					if(o.pn_show) {
						GN_ASS[v].pn = 0;
						$('#pn' + v).val(0)._dropdown('remove');
						if(POLOSA_NUM[id]) {
							var pc = [];
							for(n = 2; n < GN_ASS[v].pc; n++)
								pc.push({uid:n,title:n + '-я'});
							$('#pn' + v)._dropdown({
								title0:'??',
								spisok:pc,
								func:function(pn) {
									GN_ASS[v].pn = pn;
								}
							});
						}
					}
	*/
				}
			});
	}
	function gnsAA(func, all) {// gnsActionActive: Применение действия к выбранным номерам
		var week = $('.gns-week'),
			gnsel = [];
		for(var n = 0; n < week.length; n++) {
			var sp = week.eq(n),
				sel = sp.hasClass('gnsel'),
				v = _num(sp.attr('val'));
			if(all || sel)
				func(sp, v);
		}
	}
	function gnsClear() {// Очистка выбранных номеров
		gnGet.find('.gnsel').removeClass('gnsel');
		gnsValUpdate();
	}
	function cenaSet() {// Установка цены в выбранные номера
		var four = 0;
		gnsAA(function(sp, nn) {
			var c = 0,
				dop = _num($('#vdop' + nn).val());
			four++;
			if(four == 4) {
				four = 0;
				c = 0;
			} else
				c = gnCena ? gnCena + (dop ? GAZETA_OBDOP_CENA[dop] : 0) : 0;
			$('#cena' + nn).html(Math.round(c * 100) / 100);
		});


		return;
		var sum = 0,
			count = 0;
		switch(o.category1) {
			case 1:
				
				if(o.manual) {
					gnsActionActive(function(sp) {
						if(!sp.prev) {
							four++;
							if (four == 4)
								four = 0;
							else
								count++;
						}
					});
					four = 0;
					sum = Math.round((summa_manual / count) * 1000000) / 1000000;
				}
				gnsActionActive(function(sp) {
					if(!sp.prev) {
						four++;
						if(four == 4) {
							four = 0;
							sp.cena = 0;
						} else
						if(o.manual)
							sp.cena = sum;
						else
							sp.cena = gnCena ? gnCena + (sp.dop ? OBDOP_CENA_ASS[sp.dop] : 0) : 0;
					}
					gnGet.find('#cena' + sp.n).html(Math.round(sp.cena * 100) / 100);
				});
				break;
			case 2:
				if(o.manual) {
					gnsActionActive(function(sp) {
						if(sp.dop > 0 && !sp.prev)
							count++;
					});
					sum = Math.round((summa_manual / count) * 1000000) / 1000000;
				}
				skidka_sum = 0;
				gnsActionActive(function(sp) {
					if(!sp.prev) {
						sp.cena = 0;
						if(sp.dop) {
							if(o.manual)
								sp.cena = sum;
							else {
								sp.cena = cena * POLOSA_CENA_ASS[sp.dop];
								var sk = sp.cena * o.skidka / 100;
								sp.cena -= sk;
								skidka_sum += sk;
							}
						}
					}
					gnGet.find('#cena' + sp.n).html(Math.round(sp.cena * 100) / 100);
				});
				skidka_sum = Math.round(skidka_sum * 100) / 100;
				break;
			default:
				gnsActionActive(function(sp) {
					if(!sp.prev)
						count++;
				});
				sum = Math.round((summa_manual / count) * 1000000) / 1000000;
				gnsActionActive(function(sp) {
					if(!sp.prev)
						sp.cena = sum;
					gnGet.find('#cena' + sp.n).html(Math.round(sp.cena * 100) / 100);
				});
		}
	}
	function gnsValUpdate() {//обновление выбранных значений номеров
		var arr = [];
		gnSumma = 0;
		gnsAA(function(sp, v) {
			var dop = _num($('#vdop' + v).val()),
				c = _cena($('#cena' + v).html());
			arr.push(v + ':' + dop + ':' + c);
			gnSumma += c;
		});

		t.val(arr.join('###'));
		$('#ze-note').val(t.val()); //todo удалить

		o.func({
			summa:gnSumma
		});

		//вывод количества выбранных номеров
		var countHtml = 'Выбран' + _end(arr.length, ['', 'о']) + ' ' +
						 arr.length + ' номер' + _end(arr.length, ['', 'a', 'ов']) +
						 '<a>очистить</a>';
		selCount
			.html(arr.length ? countHtml : '')
			.find('a').click(function() {
				gnsClear();
				gnsPrint(1);
				dopMenuA.removeClass('sel');
			});
	}

	t.cenaSet = function(c) {
		gnCena = c || 0;
		cenaSet();
		gnsValUpdate();
	};
	t.update = gnsValUpdate;

	window[win] = t;
	return t;
};

$(document)
	.on('click', '#_zayav .clear', function() {
		$('#find')._search('clear');    ZAYAV.find = '';
		$('#sort')._radio(1);           ZAYAV.sort = 1;
		$('#desc')._check(0);           ZAYAV.desc = 0;
		$('#zayav-status-filter').removeClass('us');ZAYAV.status = 0;

		$('#finish').zayavSrok('0000-00-00');ZAYAV.finish = '0000-00-00';
		$('#finish').zayavSrok('executer_id', 0);

		$('#paytype')._radio(0);		ZAYAV.paytype = 0;
		$('#noschet')._check(0);		ZAYAV.noschet = 0;
		$('#nofile')._check(0);		    ZAYAV.nofile = 0;
		$('#noattach')._check(0);		ZAYAV.noattach = 0;
		$('#noattach1')._check(0);		ZAYAV.noattach1 = 0;
		$('#executer_id')._select(0);	ZAYAV.executer_id = 0;
		$('#tovar_name_id')._select(0);	ZAYAV.tovar_name_id = 0;
		$('#tovar_id').tovar('cancel');	ZAYAV.tovar_id = 0;
		$('#tovar_place_id')._select(0);ZAYAV.tovar_place_id = 0;

		$('#deleted')._check(0);		ZAYAV.deleted = 0;
		$('#deleted_only')._check(0);	ZAYAV.deleted_only = 0;

		_zayavSpisok();
	})

	.on('click', '#zayav-status-filter #sel,#zayav-status-filter #any', function() {
		var t = $(this).parent(),
			tab = t.find('#status-tab');

		tab.show();

		$(document).on('click.status_tab', function() {
			tab.hide();
			$(document).off('click.status_tab');
		});
	})
	.on('click', '#zayav-status-filter td', function() {
		var id = _num($(this).attr('val'));
		ZAYAV.status = id;
		_zayavSpisok();
		$('#zayav-status-filter')[(id ? 'add' : 'remove') + 'Class']('us');

		$('#sel')
			.html(ZAYAV_STATUS_NAME_ASS[id])
			.css('background', '#' + ZAYAV_STATUS_COLOR_ASS[id]);
	})
	.on('click', '._zayav-unit', function() {
		var id = $(this).attr('val');
		_scroll('set', 'u' + id);
		location.href = URL + '&p=zayav&d=info&id=' + id;
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
	.on('click', '#_zayav-info .zakaz', function() {
		var t = $(this),
			p = _parent(t, '.unit');

		if(t.hasClass('_busy'))
			return;

		t.addClass('_busy');

		var send = {
			op:'zayav_tovar_zakaz',
			zayav_id:ZI.id,
			tovar_id:p.attr('val')
		};
		$.post(AJAX_MAIN, send, function(res) {
			t.removeClass('_busy');
			if(res.success) {
				t.removeClass('zakaz')
				  .addClass('zakaz-ok')
				  .html('Заказано');
			}
		}, 'json');
	})
	.on('click', '#_zayav-info .set', function() {//применение товара в расходы по заявке
		var tovar_id = _parent($(this), '.unit').attr('val'),
			dialog = _dialog({
				top:100,
				width:420,
				head:'Применение товара в расходы',
				class:'zayav-tovar-set',
				load:1,
				butSubmit:'',
				submit:submit
			}),
			send = {
				op:'zayav_tovar_set_load',
				tovar_id:tovar_id
			};
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.content.html(res.html);
					$('#ta-articul')._radio(function() {
						dialog.butSubmit('Применить');
					});
				} else
					dialog.loadError();
			},'json');

		function submit() {
			var send = {
				op:'zayav_tovar_set',
				zayav_id:ZI.id,
				avai_id:_num($('#ta-articul').val())
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					location.reload();
				} else
					dialog.abort();
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
		if($('#_zayav').length) {
			$('#find')
				._search({
					width:153,
					focus:1,
					txt:'Быстрый поиск...',
					enter:1,
					func:_zayavSpisok
				})
				.inp(ZAYAV.find);
			$('#sort')._radio(_zayavSpisok);
			$('#desc')._check(_zayavSpisok);
			$('#status').rightLink(_zayavSpisok);

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
			WORKER_SPISOK.push({uid: -1, title: 'Не назначен', content: '<b>Не назначен</b>'});
			$('#executer_id')._select({
				width: 155,
				title0: 'не указан',
				spisok: WORKER_SPISOK,
				func:function(v, id) {
					$('#finish').zayavSrok('executer_id', v);
					_zayavSpisok(v, id);
				}
			});

			_zayavSpisokTovarNameFilter();

			if($('#tovar_id').length)
				$('#tovar_id').tovar({
					set:0,
					image:0,
					ids:ZAYAV_TOVAR_IDS,
					func:function(v, id) {
						$('#tovar_name_id')._select(0);
						ZAYAV.tovar_name_id = 0;
						_zayavSpisokTovarNameFilter(v);
						_zayavSpisok(v, id);
					}
				});

			$('#tovar_place_id')._select({
				width:155,
				title0:'Любое местонахождение',
				spisok:ZAYAV_TOVAR_PLACE_SPISOK,
				func:_zayavSpisok
			});

			$('#deleted')._check(_zayavSpisok);
			$('#deleted_only')._check(_zayavSpisok);
		}
		if($('#_zayav-info').length) {
			$('.a-page').click(function() {
				var t = $(this);
				t.parent().find('.link').removeClass('sel');
				var i = t.addClass('sel').index();
				$('.page:first')[(i ? 'add' : 'remove') + 'Class']('dn');
				$('.page:last')[(!i ? 'add' : 'remove') + 'Class']('dn');
			});
			var name = [0], action = [0];

			name.push('Редактировать данные заявки'); action.push(_zayavEdit);

			if(SERVICE_ACTIVE_COUNT > 1) {
				name.push('Изменить категорию заявки');
				action.push(_zayavTypeChange);
			}
			if(ZI.pole[23]) {
				name.push('Добавить картриджи');
				action.push(_zayavCartridgeAdd);
			}
			if(ZI.pole[20]) {
				name.push('<b>Распечатать квитанцию</b>');
				action.push(function() {
					if(APP_ID == 3798718) {
						if(ZI.pole[23])
							location.href = URL + '&p=print&d=kvit_cartridge&id=' + ZI.id;
						else
							location.href = URL + '&p=print&d=kvit_comtex&id=' + ZI.id;
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
			if(ZI.pole[21]) {
				name.push('Сформировать счёт на оплату');
				action.push(function() {
					if(ZI.pole[23] && _zayavCartridgeSchet())
						return;

					_schetEdit({
						edit:1,
						client_id:ZI.client_id,
						client:ZI.client_link,
						zayav_id:ZI.id
					})
				});
			}
			if(ZI.pole[45]) {
				name.push('Изменить статус заявки');
				action.push(_zayavStatus);
			}
			name.push('Начислить');                     action.push(_accrualAdd);
			name.push('<b>Принять платёж</b>');         action.push(_incomeAdd);
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
				title:'Прикрепить документ',
				icon:1,
				zayav_id:ZI.id,
				zayav_save:1
			});
			$('#attach1_id')._attach({
				title:'Прикрепить документ',
				icon:1,
				zayav_id:ZI.id,
				zayav_save:2
			});
			$('#attach_cancel,#attach1_cancel').click(function() {//отмена прикрепления документа
				var t = $(this),
					html =
						'<div class="_info">' +
							'Прикрепление документа будет отмечено как необязательное. ' +
							'При поиске заявок, если установлена галочка <u>Документ не прикреплён</u>, данная заявка выводиться не будет.' +
						'</div>' +
						'<center class="mar8"><b>Применить необязательное<br />прикрепление документа.</br></center>',
					dialog = _dialog({
						width:370,
						head:'Необязательное прикрепление документа',
						content:html,
						butSubmit:'Применить',
						submit:submit
					});

				function submit() {
					var send = {
						op:'zayav_attach_cancel',
						zayav_id:ZI.id,
						v:t.attr('id').split('_cancel')[0]
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
			});
			$('.attach-canceled').mouseover(function() {
				var t = $(this),
					v = t.attr('val');
				t.vkHint({
					show:1,
					msg:v,
					top:-82,
					left:-24
				})
			});
			$('#lost-count').click(function() {//отображение прошедших номеров газеты
				$(this).parent().find('.lost').show()
				$(this).remove();
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
				location.href = URL + '&p=print&d=erm&mon=' + $(this).attr('val');
			});
		}
	});
