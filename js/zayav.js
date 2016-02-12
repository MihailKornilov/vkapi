var _zayavSpisok = function(v, id) {
		ZAYAV.op = 'zayav' + ZAYAV.type_id + '_spisok';
		_filterSpisok(ZAYAV, v, id);
		ZAYAV.op = 'zayav_spisok';
		$('.condLost')[(ZAYAV.find ? 'add' : 'remove') + 'Class']('dn');
		$('#deleted-only-div')[(ZAYAV.deleted ? 'remove' : 'add') + 'Class']('dn');
		$.post(AJAX_MAIN, ZAYAV, function(res) {
			if(res.success) {
				$('.result').html(res.all);
				$('#spisok').html(res.spisok);
			}
		}, 'json');
	},
	_zayavDeviceFilter = function() {//установка или обновление фильтров устройства #mobile
		if(!ZAYAV_INFO_DEVICE)
			return;
		$('#dev').device({
			width:155,
			type_no:1,
			device_id:ZAYAV.device,
			vendor_id:ZAYAV.vendor,
			model_id:ZAYAV.model,
//			device_ids:ZAYAV.device_ids,
//			vendor_ids:ZAYAV.vendor_ids,
//			model_ids:ZAYAV.model_ids,
			device_multiselect:1,
			func:function(i) {
				ZAYAV.device = i.device_id;
				ZAYAV.vendor = i.vendor_id;
				ZAYAV.model = i.model_id;
				_zayavSpisok();
			}
		});
	},
	_zayavProductSubFilter = function(product_id) {//показ-скрытие подвида изделия
		if(!ZAYAV_INFO_PRODUCT)
			return;
		var sub = PRODUCT_SUB_SPISOK[product_id];
		$('#product_sub_id').val(0)._select(!sub ? 'remove' : {
			width:155,
			title0:'Любые изделия',
			spisok:sub,
			func:_zayavSpisok
		});
	},
	_zayavAddMenu = function() {
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
			var t = $(this);
			if(t.parent().find('._busy').length)
				return;
			t.addClass('_busy');
			var send = {
				op:'zayav_type_js',
				type_id:_num(t.attr('val'))
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				t.removeClass('_busy');
				if(res.success) {
					dialog.close();
					for(var i in res.js)
						window[i] = res.js[i];
					_zayavEdit();
				}
			}, 'json');
		});
	},
	_zayavEdit = function() {
		var c = $.extend({//если заявка вносится из клиента, то получение данных о клиенте
				id:0,
				name:'',
				adres:''
			}, window.CLIENT || {}),
			o = $.extend({
				id:0,
				type_id:window.ZAYAV_TYPE_ID || 0,
				client_id:c.id,
				client_name:c.name,
				client_adres:c.adres,
				name:'',
				about:'',
				count:1,
				product:[[0,0,0]],
				adres:'',
				device_id:0,
				vendor_id:0,
				model_id:0,
				equip:'',
				imei:'',
				serial:'',
				color_id:0,
				color_dop:0,
				srok:'0000-00-00',
				place:-1,
				sum_cost:'',
				pay_type:0
			}, window.ZI || {});

		var html =
				'<table id="_zayav-add-tab">' +
					'<tr><td class="label">Клиент:' +
						'<td><input type="hidden" id="client_id" value="' + o.client_id + '" />' +
							'<b>' + o.client_name + '</b>' +
					'<tr' + (ZAYAV_INFO_NAME ?    '' : ' class="dn"') + '><td class="label">Название:<td><input type="text" id="name" value="' + o.name + '" />' +
					'<tr' + (ZAYAV_INFO_COUNT ?   '' : ' class="dn"') + '><td class="label">Количество:<td><input type="text" class="money" id="count" value="' + o.count + '" /> шт.' +
					'<tr' + (ZAYAV_INFO_PRODUCT ? '' : ' class="dn"') + '><td class="label topi">Изделие:<td id="product">' +
					'<tr' + (ZAYAV_INFO_ABOUT ?   '' : ' class="dn"') + '><td class="label topi">Описание:<td><textarea id="about">' + o.about + '</textarea>' +
					'<tr' + (ZAYAV_INFO_ADRES ?   '' : ' class="dn"') + '>' +
						'<td class="label">Адрес:<td>' +
							'<input type="text" id="adres" value="' + o.adres + '" />' +
							'<input type="hidden" id="client-adres" />' +
					'<tr' + (ZAYAV_INFO_DEVICE ?  '' : ' class="dn"') + '>' +
						'<td class="label topi">Устройство:' +
						'<td><table><td id="za-dev"><td id="device_image"></table>' +
					'<tr' + (!ZAYAV_INFO_DEVICE || !o.equip ? ' class="dn"' : '') + ' id="equip-tr">' +
						'<td class="label top">Комплектация:' +
						'<td id="equip-spisok">' + o.equip +
					'<tr' + (ZAYAV_INFO_IMEI ?    '' : ' class="dn"') + '><td class="label">IMEI:<td><input type="text" id="imei" value="' + o.imei + '" />' +
					'<tr' + (ZAYAV_INFO_SERIAL ?  '' : ' class="dn"') + '><td class="label">Серийный номер:<td><input type="text" id="serial" value="' + o.serial + '" />' +
					'<tr' + (ZAYAV_INFO_COLOR ?   '' : ' class="dn"') + '><td class="label">Цвет:<td id="color">' +
					'<tr' + (!o.id && ZAYAV_INFO_DEVICE ?  '' : ' class="dn"') + '>' +
						'<td class="label top">Местонахождение устройства<br />после внесения заявки:' +
						'<td><input type="hidden" id="device-place" value="' + o.place + '" />' +
					'<tr' + (ZAYAV_INFO_SUM_COST ?'' : ' class="dn"') + '><td class="label">Предварительная стоимость:<td><input type="text" class="money" id="sum_cost" value="' + (o.sum_cost ? o.sum_cost : '') + '" /> руб.' +
					'<tr' + (ZAYAV_INFO_PAY_TYPE ?'' : ' class="dn"') + '><td class="label topi">Расчёт:<td><input type="hidden" id="pay_type" value="' + o.pay_type + '" />' +

				(!o.id && ZAYAV_INFO_NOTE ?
					'<tr><td class="label top">Заметка:<td><textarea id="note"></textarea>'
				: '') +

				(!o.id && ZAYAV_INFO_SROK ?
					'<tr><td class="label top">Срок:' +
						'<td><input type="hidden" id="za-srok" value="' + o.srok + '" />' +
							'<div class="srok-link">' +
								'<span>не указан</span>' +
							'</div>'
				: '') +

				'</table>',
			dialog = _dialog({
				width:550,
				top:30,
				head:o.id ? 'Редактирование заявки' : 'Внесение новой заявки',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		if(!o.client_name)
			$('#client_id').clientSel({
				add:1,
				func:function(uid, id, item) {
					o.client_adres = uid ? item.adres : '';
					if($('#client-adres').val() == 1)
						$('#adres').val(o.client_adres);
				}
			});
		$('#name').focus();
		$('#about').autosize();
		$('#product').product(o.product);

		$('#client-adres')._check({
			func:function(v) {
				$('#adres').val(v ? o.client_adres : '');
			}
		});
		$('#client-adres_check').vkHint({
			msg:'Совпадает с адресом клиента',
			top:-76,
			left:184,
			indent:60,
			delayShow:500
		});
		$('#adres').keyup(function() {
			$('#client-adres')._check(0);
		});

		if(ZAYAV_INFO_DEVICE) {
			$('#za-dev').device({
				width:190,
				device_ids:WS_DEVS,
				device_id:o.device_id,
				vendor_id:o.vendor_id,
				model_id:o.model_id,
				add:1,
				func:zayavDevSelect
			});
			modelImageGet();
			if(!o.id)
				zayavPlace();
		}
		$('#color')._selectColor({
			color_id:o.color_id,
			color_dop:o.color_dop
		});
		$('#pay_type')._radio({
			light:1,
			spisok:PAY_TYPE
		});
		$('#note').autosize();

		function submit() {
			var send = {
					op:o.id ? 'zayav_edit' : 'zayav_add',
					type_id:o.type_id,
					zayav_id:o.id,
					client_id:_num($('#client_id').val()),
					name:$('#name').val(),
					about:$('#about').val(),
					product_id:$('#item').val(),
					product_sub_id:$('#item-sub').val(),
					product_count:$('#item-count').val(),
					count:_num($('#count').val()),
					adres:$('#adres').val(),
					device_id:_num($('#za-dev_device').val()),
					vendor_id:_num($('#za-dev_vendor').val()),
					model_id:_num($('#za-dev_model').val()),
					equip:'',
					imei:$('#imei').val(),
					serial:$('#serial').val(),
					color_id:$('#color_id').val(),
					color_dop:$('#color_dop').val(),
					place:$('#device-place').val(),
					place_other:$.trim($('#place_other').val()),
					sum_cost:$('#sum_cost').val(),
					pay_type:_num($('#pay_type').val()),
					note:$('#note').val(),
					srok:'0000-00-00'
				};

			if(!send.client_id) {
				dialog.err('Не выбран клиент');
				return;
			}

			if(ZAYAV_INFO_DEVICE) {
				if(!send.device_id) {
					dialog.err('Не выбрано устройство');
					return;
				}

				if(!$('#equip-tr').hasClass('dn')) {
					var inp = $('#equip-spisok input'),
						arr = [];
					for(var n = 0; n < inp.length; n++) {
						var eq = inp.eq(n);
						if(eq.val() == 1)
							arr.push(eq.attr('id').split('_')[1]);
					}
					send.equip = arr.join();
				}

				if(!o.id && (send.place == '-1' || send.place == 0 && !send.place_other)) {
					dialog.err('Не указано местонахождение устройства');
					return;
				}
			}

			if(!o.id && ZAYAV_INFO_SROK) {
				send.srok = $('#za-srok').val();
				if(send.srok == '0000-00-00') {
					dialog.err('Не указан срок');
					return;
				}
			}

			if(ZAYAV_INFO_COUNT && !send.count) {
				dialog.err('Некорректно указано количество');
				return;
			}

			if(ZAYAV_INFO_PAY_TYPE && !send.pay_type) {
				dialog.err('Укажите вид расчёта');
				return;
			}

			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					_scroll('set', 'u' + res.id);
					location.href = URL + '&p=zayav&d=info&id=' + res.id;
				} else
					dialog.abort();
			}, 'json');
		}
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
					(ZAYAV_INFO_STATUS_DAY ?
						'<tr class="tr-day-fact dn">' +
							'<td class="label">Фактический день:' +
							'<td><input type="hidden" id="day" value="' + ZI.status_day + '" />'
					: '') +

						'<tr class="tr-executer dn"><td class="label">Назначить исполнителя:<td><input type="hidden" id="zs-executer_id" />' +

						'<tr class="tr-srok dn">' +
							'<td class="label">Указать срок выполнения:' +
							'<td><input type="hidden" id="za-srok" value="0000-00-00" />' +
								'<div class="srok-link no-save"><span>не указан</span></div>' +

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

		$('#zs-executer_id')._select({
				width:170,
				title0:'не назначен',
				spisok:_zayavExecuter()
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
				srok:'0000-00-00',
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

			if(ZAYAV_INFO_STATUS_DAY && ZAYAV_STATUS_DAY_FACT_ASS[send.status_id])
				send.status_day = $('#day').val();

			if(ZAYAV_STATUS_EXECUTER_ASS[send.status_id] && !send.executer_id) {
				dialog.err('Не назначен исполнитель');
				return;
			}

			if(ZAYAV_STATUS_SROK_ASS[send.status_id]) {
				send.srok = $('#za-srok').val();
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
						'<td><input type="hidden" id="type_id" value="' + ZAYAV_TYPE_ID + '" />' +
				'</table>',
			dialog = _dialog({
				head:'Изменение категории заявки',
				content:html,
				butSubmit:'Применить',
				submit:submit
			});

		$('#type_id')._select({
			width:200,
			spisok:_toSpisok(SERVICE_ACTIVE_ASS)
		});

		function submit() {
			var send = {
				op:'zayav_type_change',
				zayav_id:ZI.id,
				type_id:$('#type_id').val()
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

	_zayavDogovorCreate = function() {
		var o = $.extend({
			id:0,
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
			spisok:INVOICE_SPISOK,
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
				avans:$('#avans_sum').val()
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
			if(!send.avans_hide && send.avans && !_cena(send.avans)) {
				dialog.err('Некорректно указан авансовый платёж');
				$('#avans_sum').focus();
				return false;
			}
			if(!send.avans_hide && send.avans && !send.invoice_id) {
				dialog.err('Не указан счёт авансового платёжа');
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

	_zayavExpenseEdit = function () {//вывод окна для редактирование расходов по заявке в информации о заявке
		var html =
			'<table id="zee-tab">' +
				'<tr><td class="label">Заявка:<td><b>' + ZI.name + '</b>' +
				'<tr><td class="label">Список расходов:' +
				'<tr><td id="zee-spisok" colspan="2">' +
			'</table>',
			dialog = _dialog({
				top: 30,
				width: 550,
				head: 'Изменение расходов заявки',
				content: html,
				butSubmit: 'Сохранить',
				submit: submit
			});

		_zayavExpense();

		function submit() {
			var send = {
				op:'zayav_expense_edit',
				zayav_id:ZI.id,
				expense:_zayavExpenseGet()
			};
			if(send.expense == 'sum_error') {
				dialog.err('Некорректно указана сумма');
				return;
			}
			if(send.expense == 'file_error') {
				dialog.err('Прикрепите файл или укажите описание');
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg('Сохранено');
					$('#_zayav-expense').html(res.html);
				} else
					dialog.abort();
			}, 'json');
		}
	},
	_zayavExpense = function() {//процесс редактирования расходов по заявке
		var num = 0;

		for(var n = 0; n < ZAYAV_EXPENSE.length; n++)
			item(ZAYAV_EXPENSE[n])

		item();

		function item(v) {
			if(!v)
				v = [
					0, //0 - id
					0, //1 - категория
					'',//2 - описание, id сотрудника, id запчасти, id файла
					'',//3 - сумма
					0  //4 - лист выдачи зп
				];
			var html =
					'<table id="zee-tab'+ num + '" class="zee-tab" val="' + num + '">' +
						'<tr><td><input type="hidden" id="' + num + 'cat" value="' + v[1] + '" />' +
							'<td class="dop">' +
							'<td class="tdsum">' +
								'<input type="text" class="zee-sum" tabindex="' + (num * 10) + '"' + (v[4] ? ' disabled' : '') + ' value="' + v[3] + '" />руб.' +
								'<input type="hidden" class="id" value="' + v[0] + '" />' +
					'</table>';

			$('#zee-spisok').append(html);
			itemDop(v[1], v[2], num, v[4]);

			var tab = $('#zee-tab' + num);
			$('#' + num + 'cat')._select({
				width:130,
				disabled:v[4],
				title0:'Категория',
				spisok:ZAYAV_EXPENSE_SPISOK,
				func:function(id, attr) {
					tab.find('.id').val(0);
					itemDop(id, '', attr.split('cat')[0]);
					if(id && !tab.next().hasClass('zee-tab'))
						item();
				}
			});

			num++;
		}
		function itemDop(cat_id, val, num, list_id) {
			var tab = $('#zee-tab' + num),
				dop = tab.find('.dop'),
				sum = tab.find('.zee-sum');

			list_id = list_id || 0;

			dop.html('');
			tab.find('.tdsum')[(cat_id ? 'remove' : 'add') + 'Class']('dn');

			if(!cat_id)
				return;

			if(ZAYAV_EXPENSE_TXT[cat_id]) {
				dop.html('<input type="text" class="zee-txt" placeholder="описание не указано" tabindex="' + (num * 10 - 1) + '" value="' + val + '" />');
				dop.find('input').focus();
			}

			if(ZAYAV_EXPENSE_WORKER[cat_id]) {
				dop.html('<input type="hidden" id="' + num + 'worker" value="' + val + '" />');
				$('#' + num + 'worker')._select({
					width:240,
					disabled:list_id,
					title0:'Сотрудник',
					spisok:WORKER_SPISOK,
					func:function(v) {
						sum.focus();
					}
				});
			}

			if(ZAYAV_EXPENSE_ZP[cat_id]) {
				dop.html('<input type="hidden" id="' + num + 'zp" value="' + val + '" />');
				$('#' + num + 'zp')._select({
					width:240,
					title0:'Запчасть не выбрана',
					spisok:ZI.zp_avai,
					func:function(v) {
						sum.focus();
					}
				});
			}

			if(ZAYAV_EXPENSE_ATTACH[cat_id]) {
				var html = '<table class="tab-attach">' +
						'<tr><td><input type="text" id="' + num + 'attach-txt" class="attach-txt" value="' + $.trim(val) + '" />' +
							'<td><input type="hidden" id="' + num + 'attach" value="' + _num(val) + '" />' +
					'</table>';
				dop.html(html);
				$('#' + num + 'attach')._attach({
					zayav_id:ZI.id,
					func:function(v) {
						$('#' + num + 'attach-txt')[v ? 'hide' : 'show']().val('');
						sum.focus();
					}
				});
				if(ATTACH[val])
					$('#' + num + 'attach-txt').hide();

				sum.focus();
			}
		}
	},
	_zayavExpenseGet = function() {//получение значения списка расходов заявки при редактировании
		var tab = $('.zee-tab'),
			send = [];
		for(var n = 0; n < tab.length; n++) {
			var eq = tab.eq(n),
				num = eq.attr('val'),
				id = eq.find('.id').val(),
				cat_id = _num($('#' + num + 'cat').val()),
				sum = eq.find('.zee-sum').val(),
				dop = '';
			if(!cat_id)
				continue;
			if(!_cena(sum) && sum != '0')
				return 'sum_error';
			if(ZAYAV_EXPENSE_TXT[cat_id])
				dop = eq.find('.zee-txt').val();
			else if(ZAYAV_EXPENSE_WORKER[cat_id])
				dop = $('#' + num + 'worker').val();
			else if(ZAYAV_EXPENSE_ZP[cat_id])
				dop = $('#' + num + 'zp').val();
			else if(ZAYAV_EXPENSE_ATTACH[cat_id]) {
				dop = $('#' + num + 'attach').val();
				if(!_num(dop)) {
					var txt = $('#' + num + 'attach-txt').val();
					if(!txt)
						return 'file_error';
					dop = '.' + txt;
				}

			}

			send.push(
				id + ':' +
				cat_id + ':' +
				dop + ':' +
				_cena(sum)
			);
		}
		return send.join();
	};

$.fn.product = function(o) {
	var t = $(this),
		html = '<div id="product-list">' +
			'<table>' +
				'<tr><td class="td">' +
						'<input type="hidden" id="item" value="' + o[0][0] + '" />' +
						'<input type="hidden" id="item-sub" value="' + o[0][1] + '" />' +
					'<td class="td">' +
						'<input type="text" id="item-count" maxlength="4" value="' + o[0][2] + '" /> шт.' +
			'</table>' +
		'</div>';

	t.html(html);
	var list = t.find('#product-list');

	$('#item')._select({
		width:119,
		title0:'Не указано',
		spisok:PRODUCT_SPISOK,
		func:function(id) {
			$('#item-sub')
				._select('remove')
				.val(0);
			subSel(id);
			$('#item-count').val(id ? 1 : '').focus();
		}
	});

	subSel(o[0][0]);

	function subSel(id) {
		if(!id || !PRODUCT_SUB_SPISOK[id])
			return;
		$('#item-sub')._select({
			width:120,
			title0:'Подвид не указан',
			spisok:PRODUCT_SUB_SPISOK[id],
			func:function() {
				$('#item-count').focus();
			}
		});
	}
	return t;
};

$(document)
	.on('click', '#_zayav .clear', function() {
		$('#find')._search('clear');    ZAYAV.find = '';
		$('#sort')._radio(1);           ZAYAV.sort = 1;
		$('#desc')._check(0);           ZAYAV.desc = 0;
		$('#status').rightLink(0);		ZAYAV.status = 0;

		$('#srok').val('0000-00-00');
		$('.srok-link span').html('не указан');
		ZAYAV.finish = '0000-00-00';

		$('#diff')._check(0);			ZAYAV.diff = 0;
		$('#paytype')._radio(0);		ZAYAV.paytype = 0;
		$('#noschet')._check(0);		ZAYAV.noschet = 0;
		$('#executer_id')._select(0);	ZAYAV.executer_id = 0;
		$('#product_id')._select(0);	ZAYAV.product_id = 0;
										ZAYAV.product_sub_id = 0;
		_zayavProductSubFilter(0);

		$('#zpzakaz')._radio(0);		ZAYAV.zpzakaz = 0;
		ZAYAV.device = 0;
		ZAYAV.vendor = 0;
		ZAYAV.model = 0;
		_zayavDeviceFilter();
		$('#place')._select(0);			ZAYAV.place = 0;

		$('#deleted')._check(0);		ZAYAV.deleted = 0;
		$('#deleted_only')._check(0);	ZAYAV.deleted_only = 0;

		_zayavSpisok();
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
	.on('click', '#_zayav-add,#_zayav-info #edit', _zayavEdit)
	.on('click', '#_client-zayav-add', function() {
		if(SERVICE_ACTIVE_COUNT > 1)
			return _zayavAddMenu();
		_zayavEdit();
	})

	.on('click', '#zayav-status-button', _zayavStatus)

	.on('click', '.srok-link', function(e) {//открытие календаря заявок
		e.stopPropagation();
		var t = $(this),
			save = t.hasClass('no-save') ? 0 : 1;
		if(t.hasClass('_busy'))
			return;
		var dialog = _dialog({
				top:40,
				width:480,
				head:'Календарь заявок',
				load:1,
				butSubmit:''
			}),
			send = {
				op:'zayav_srok',
				day:$('#srok').val() || $('#za-srok').val(),
				zayav_spisok:$('#_zayav').length
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				dialog.content.html(res.html);
			else
				dialog.loadError();
		}, 'json');
		$(document)
			.off('click', '#zayav-srok-calendar td.d:not(.old),#fc-cancel,.fc-old-sel')
			.on('click', '#zayav-srok-calendar td.d:not(.old),#fc-cancel,.fc-old-sel', function() {
				if(t.hasClass('_busy'))
					return;
				dialog.close();
				t.addClass('_busy');
				send = {
					op:'zayav_srok_save',
					day:$(this).attr('val'),
					zayav_id:window.ZI ? ZI.id : 0,
					save:save
				};
				$.post(AJAX_MAIN, send, function(res) {
					t.removeClass('_busy');
					if(res.success) {
						t.prev('input').val(send.day);
						t.find('span').html(res.data);
						if($('#_zayav').length)
							_zayavSpisok(send.day, 'finish');
					}
				}, 'json');
			});
	})
	.on('click', '#zayav-srok-calendar .ch', function() {//перемотка календаря заявок
		if($('#fc-head').hasClass('_busy'))
			return;
		$('#fc-head').addClass('_busy');
		var send = {
			op:'zayav_srok_next',
			mon:$(this).attr('val'),
			day:$('#srok').val(),
			zayav_spisok:$('#_zayav').length
		};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				$('#zayav-srok-calendar').after(res.html).remove();
			else
				$('#fc-head').removeClass('_busy');
		}, 'json');
	})

	.on('click', '#_zayav-expense .img_edit', _zayavExpenseEdit)

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
			$('#diff')._check(_zayavSpisok);
			$('#paytype')._radio(_zayavSpisok);
			$('#noschet')._check(_zayavSpisok);
			WORKER_SPISOK.push({uid: -1, title: 'Не назначен', content: '<b>Не назначен</b>'});
			$('#executer_id')._select({
				width: 155,
				title0: 'не указан',
				spisok: WORKER_SPISOK,
				func: _zayavSpisok
			});
			$('#product_id')._select({
				width:155,
				bottom:3,
				title0:'Любые изделия',
				spisok:PRODUCT_SPISOK,
				func:function(v, id) {
					_zayavProductSubFilter(v);
					_zayavSpisok(v, id);
				}
			});

			if(ZAYAV_INFO_DEVICE) {
				$('#zpzakaz')._radio(_zayavSpisok);
				_zayavDeviceFilter();
				$('#place')._select({
					width:155,
					title0:'Любое местонахождение',
					spisok:DEVPLACE_SPISOK,
					func:_zayavSpisok
				});
			}

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

			if(ZAYAV_INFO_CARTRIDGE) {
				name.push('Добавить картриджи');
				action.push(zayavCartridgeAdd);
			}

			if(ZAYAV_INFO_KVIT) {
				name.push('<b>Распечатать квитанцию</b>');
				action.push(function() {
					if(WS_ID == 3) {
						if(ZAYAV_INFO_CARTRIDGE)
							location.href = URL + '&p=print&d=kvit_cartridge&id=' + ZI.id;
						else
							location.href = URL + '&p=print&d=kvit_comtex&id=' + ZI.id;
					} else
						zayavKvit();
				});
			}

			if(ZAYAV_INFO_DOGOVOR) {
				name.push(DOG.id ? 'Изменить данные договора' : 'Заключить договор');
				action.push(_zayavDogovorCreate);
			}

			if(ZAYAV_INFO_SCHET) {
				name.push('Сформировать счёт на оплату');
				action.push(function() {
					if(ZAYAV_INFO_CARTRIDGE && zayavCartridgeSchet())
						return;

					_schetEdit({
						edit:1,
						client_id:ZI.client_id,
						client:ZI.client_link,
						zayav_id:ZI.id
					})
				});
			}

			name.push('Изменить статус заявки');        action.push(_zayavStatus);
			name.push('Начислить');                     action.push(_accrualAdd);
			name.push('<b>Принять платёж</b>');         action.push(_incomeAdd);
			name.push('Возврат');                       action.push(_refundAdd);
			name.push('Изменить расходы по заявке');    action.push(_zayavExpenseEdit);
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
						if(res.success)
							_msg('Исполнитель изменён');
					}, 'json');
				}
			});
			$('#attach_id')._attach({
				zayav_id:ZI.id,
				zayav_save:1
			});
		}
	});
