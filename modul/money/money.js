var _accrualAdd = function(o) {
		o = $.extend({
			id:0,
			sum:'',
			about:''
		}, o);

		var client = window.CI,
			client_id = client ? CI.id : 0,
			zayav = window.ZI,
			zayav_id = zayav ? ZI.id : 0,
			html =
			'<table class="bs10">' +
	  (client ? '<tr><td class="label r">Клиент:<td><b>' + CI.name + '</b>' : '') +
	   (zayav ? '<tr><td class="label r">Заявка:<td><b>' + ZI.name + '</b>' : '') +
				'<tr><td class="label r w70">Сумма:<td><input type="text" id="sum" class="money" value="' + o.sum.split(' ').join('') + '" /> руб.' +
				'<tr><td class="label r">Описание:<td><input type="text" id="about" class="w300" value="' + o.about + '" />' +
			'</table>';

		var dialog = _dialog({
			width:450,
			head:(o.id ? 'Редактироваие' : 'Внесение') + ' начисления',
			content:html,
			butSubmit:o.id ? 'Сохранить' : 'Внести',
			submit:submit
		});

		$('#sum').focus();

		function submit() {
			var send = {
				op:'accrual_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				client_id:client_id,
				zayav_id:zayav_id,
				sum:$('#sum').val(),
				about:$('#about').val()
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

	_incomeAdd = function() {
		var client = window.CI,
			zayav = window.ZI,
			client_id = client ? CI.id : zayav ? zayav.client_id : 0,
			client_name = client ? '<b>' + CI.name + '</b>' : zayav && zayav.client_id ? ZI.client_link : '',
			place = zayav && ZI.pole[12],
			about = zayav ? 'Примечание' : 'Описание',
			about_placeholder = zayav ? ' placeholder="не обязательно"' : '',
			html =
			'<table class="bs10 w100p mt10 mb10">' +
				'<tr' + (APP_ID == 3495523 ? '' : ' class="dn"') + '><td class="label r">День внесения:<td><input type="hidden" id="dtime_add" />' + //todo временно
 (client_name ? '<tr><td class="label r">Клиент:<td>' + client_name : '') +
	   (zayav ? '<tr><td class="label r">Заявка:<td><b>' + ZI.name + '</b>' : '') +
				'<tr><td class="label r w175">Счёт:<td><input type="hidden" id="invoice_id-add" value="' + _invoiceIncomeInsert(1) + '" />' +
				'<tr class="tr_confirm dn"><td class="label">Подтверждение:<td><input type="hidden" id="confirm" />' +
				'<tr><td class="label r">Сумма:<td><input type="text" id="sum" class="money" /> руб.' +
	   (zayav ? '<tr><td class="label r">Предоплата:<td>' : '') +
					'<input type="hidden" id="prepay" />' +
				'<tr><td class="label r">' + about + ':<td><input type="text" id="about" class="w250"' + about_placeholder + ' />' +
			'</table>' +

	   (zayav ?
			(place ?
				'<div class="income-add-place dn pt10 pb10">' +
					'<table class="bs10 w100p">' +
						'<tr><td class="label r w175">Текущее местонахождение:' +
							'<td><a class="place-set b' + _tooltip('Нажмите для подтверждения', -55) + _toAss(ZAYAV_TOVAR_PLACE_SPISOK)[ZI.place_id] + '</a>' +
						'<tr><td class="label r topi">Новое местонахождение:<td><input type="hidden" id="tovar-place" />' +
					'</table>' +
				'</div>'
			: '') +

			(ZAYAV_REMIND.active ?
				'<div class="income-add-remind dn pad15">' +
					'<div class="b">Есть ' + ZAYAV_REMIND.active + ' активн' + _end(ZAYAV_REMIND.active, ['ое', 'ых']) + ' напоминани' + _end(ZAYAV_REMIND.active, ['е', 'я', 'й']) + '.</div>' +
					'<div class="grey mt5">Поставье галочку напоминанию, если его нужно отметить выполненным.</div>' +
					incomeRemind() +
				'</div>'
	        : '')
	   : ''),
			dialog = _dialog({
				top:zayav ? 30 : 60,
				width:550,
				head:'Внесение платежа',
				padding:0,
				content:html,
				submit:submit
			});

		$('#dtime_add')._calendar({
			lost:1
		});
		$('#invoice_id-add')._select({
			width:268,
			title0:'Не выбран',
			spisok:_invoiceIncomeInsert(),
			func:function(id) {
				$('#sum').focus();
				$('.tr_confirm')[(INVOICE_INCOME_CONFIRM[id] ? 'remove' : 'add') + 'Class']('dn');
				$('#confirm')._check(0);
			}
		});
		$('#confirm')._check({
			func:function() {
				$('#sum').focus();
			}
		});
		$('#confirm_check').vkHint({
			width:210,
			msg:'Установите галочку, если платёж нужно внести, но требуется подтверждение о его поступлении на счёт.',
			top:-96,
			left:-100
		});
		if(zayav) {
			$('#prepay')._radio({
				light:1,
				block:0,
				spisok:[
					{uid:1, title:'да'},
					{uid:2, title:'нет'}
				],
				func:function() {
					$('#about').focus();
					if(place)
						$('.income-add-place').slideDown(300);
					else
						$('.income-add-remind').slideDown(300);
				}
			});

			if(place) {
				$('#tovar-place').zayavTovarPlace({
					func:function() {
						$('.income-add-remind').slideDown(300);
					}
				});
				$('.place-set').click(function() {
					$('#tovar-place')._radio(ZI.place_id);
					$('.income-add-remind').slideDown(300);
				});
			}
			for(var n = 0; n < ZAYAV_REMIND.active_spisok.length; n++) {
				var i = ZAYAV_REMIND.active_spisok[n];
				$('#ui' + i.id)._check({
					name:'выполнено',
					light:1
				});
			}
		}
		$('#sum').focus();
		$('#sum,#about').keyEnter(submit);

		function incomeRemind() {//активные напоминания
			var send = '';
			for(var n = 0; n < ZAYAV_REMIND.active_spisok.length; n++) {
				var i = ZAYAV_REMIND.active_spisok[n];
				send +=
					'<div class="income-add-remind-iu">' +
						'<div class="b fs14">' + i.txt + '</div>' +
						'<div class="mb20 ml20 mt5 fs12">' + i.about + '</div>' +
						'<input type="hidden" id="ui' + i.id + '" />' +
					'</div>';
			}
			return send;
		}
		function submit() {
			var remind = [];
			if(zayav)
				for(var n = 0; n < ZAYAV_REMIND.active_spisok.length; n++) {
					var i = ZAYAV_REMIND.active_spisok[n];
					if(_num($('#ui' + i.id).val()))
						remind.push(i.id);
				}
			var send = {
				op:'income_add',
				dtime_add:$('#dtime_add').val(),
				invoice_id:_num($('#invoice_id-add').val()),
				confirm:_bool($('#confirm').val()),
				sum:_cena($('#sum').val()),
				prepay:_num($('#prepay').val()),
				about:$('#about').val(),
				client_id:client_id,
				zayav_id:zayav ? ZI.id : 0,
				place_id:$('#tovar-place').val(),
				place_other:$('#tovar-place').attr('val'),
				remind_ids:remind.join()
			};

			if(!send.invoice_id) {
				dialog.err('Не указан счёт');
				return;
			}

			if(!send.sum) {
				dialog.err('Некорректно указана сумма');
				$('#sum').focus();
				return;
			}

			if(zayav && !send.prepay) {
				dialog.err('Укажите, является ли платёж предоплатой');
				$('#sum').focus();
				return;
			}

			if(!zayav && !send.about) {
				dialog.err('Не указано описание');
				$('#about').focus();
				return;
			}

			if(place && (send.place_id == -1 || !send.place_id && !send.place_other)) {
				dialog.err('Не указано местонахождение устройства');
				$('#place_other').focus();
				return;
			}

			if(send.prepay == 2)
				send.prepay = 0;

			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					if(client || zayav)
						location.reload();
					else
						incomeSpisok();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	incomeLoad = function() {
		window._calendarFilter = incomeSpisok;
		$('#invoice_id')._radio({
			light:1,
			title0:'Все счета',
			spisok:INVOICE_SPISOK,
			func:incomeSpisok
		});
		$('#worker_id')._select({
			width:190,
			title0:'Все сотрудники',
			spisok:INCOME_WORKER,
			func:incomeSpisok
		});
		$('#schet')._check(incomeSpisok);
		$('#prepay')._check(incomeSpisok);
		$('#deleted')._check(function(v, id) {
			$('#deleted_only_check')[v ? 'show' : 'hide']();
			INCOME.deleted_only = 0;
			$('#deleted_only')._check(0);
			incomeSpisok(v, id);
		});
		$('#deleted_only')._check(incomeSpisok);
	},
	incomeSpisok = function(v, id) {
		INCOME.page = 1;
		INCOME[id] = v;
		$.post(AJAX_MAIN, INCOME, function(res) {
			if(res.success) {
				$('#path').html(res.path);
				$('#spisok').html(res.spisok);
			}
		}, 'json');
	},
	incomeUnbind = function(income_id) {//отвязка платежа
		var dialog = _dialog({
				width:500,
				head:'Отвязка платежа',
				load:1,
				butSubmit:'Применить',
				submit:submit
			}),
			send = {
				op:'income_unbind_load',
				income_id:income_id
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				loaded(res);
			else
				dialog.loadError(res.text);
		}, 'json');

		function loaded(res) {
			dialog.content.html(res.html);
		}
		function submit() {
			var send = {
				op:'income_unbind',
				income_id:income_id,
				schet_pay:_num($('#schet_pay_unbind').val())
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					incomeUnbind(income_id);
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},

	_refundAdd = function() {//внесение возврата
		var zayav = window.ZI,
			zayav_id = zayav ? ZI.id : 0,
			client = window.CI,
			client_id = client ? CI.id : 0,
			client_name = client ? '<b>' + CI.name + '</b>' : zayav && zayav.client_id ? ZI.client_link : '',
			html =
			'<div class="_info">' +
				'После внесения возврата не забудьте удалить начисление, чтобы выровнять баланс клиента.' +
			'</div>' +
			'<table class="bs10">' +
                '<tr><td class="label r">Клиент:<td>' + client_name +
	   (zayav ? '<tr><td class="label r">Заявка:<td><b>' + ZI.name + '</b>' : '') +
				'<tr><td class="label r w100">Со счёта:<td><input type="hidden" id="invoice_id" value="' + _invoiceIncomeInsert(1) + '" />' +
				'<tr><td class="label r">Сумма:<td><input type="text" id="sum" class="money" /> руб.' +
				'<tr><td class="label r">Причина:<td><input type="text" id="about" class="w300" />' +
			'</table>',
			dialog = _dialog({
				top:30,
				width:480,
				head:'Возврат денежных средств',
				content:html,
				submit:submit
			});

		$('#sum').focus();
		$('#invoice_id')._select({
			width:250,
			title0:'Не выбран',
			spisok:_invoiceIncomeInsert(),
			func:function(v) {
				$('#sum').focus();
			}
		});

		function submit() {
			var send = {
				op:'refund_add',
				client_id:client_id,
				zayav_id:zayav_id,
				invoice_id:$('#invoice_id').val(),
				sum:$('#sum').val(),
				about:$('#about').val()
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
	_refundLoad = function() {
		$('#invoice_id')._radio({
			light:1,
			title0:'Все счета',
			spisok:INVOICE_SPISOK,
			func:_refundSpisok
		});
	},
	_refundSpisok = function(v, id) {
		REFUND.page = 1;
		REFUND[id] = v;
		$.post(AJAX_MAIN, REFUND, function(res) {
			if(res.success)
				$('#spisok').html(res.spisok);
		}, 'json');
	},

	_expenseSub = function(id, sub_id, add, width) {//показ-скрытие подкатегории расхода
		add = add || '';
		var sub = EXPENSE_SUB_SPISOK[id];
		$('#category_sub_id' + add).val(_num(sub_id) || 0)._select(!sub ? 'remove' : {
			width:width || (add ? 258 : 140),
			title0:add ? 'Подкатегория не указана' : 'Любая подкатегория',
			spisok:sub,
			func:function(v, id) {
				if(add)
					return;
				_expenseSpisok(v, id);
			}
		});
	},
	_expenseTab = function(dialog, o) {//таблица для внесения или редактирования расхода
		o = $.extend({
			id:0,
			dtime_add:'',
			category_id:0,
			category_sub_id:0,
			invoice_id:_invoiceExpenseInsert(1),
			attach_id:0,
			sum:'',
			sum_edit:1,
			about:''
		}, o);

		ATTACH[o.attach_id] = o.attach;

		var html =
			'<table class="bs10">' +
				'<tr' + (APP_ID == 3495523 ? '' : ' class="dn"') + '><td class="label r">День внесения:<td><input type="hidden" id="dtime_add" value="' + o.dtime_add + '" />' + //todo временно
				'<tr><td class="label r topi w100">Категория:' +
					'<td><input type="hidden" id="category_id-add" value="' + o.category_id + '" />' +
						'<input type="hidden" id="category_sub_id-add" value="' + o.category_sub_id + '" />' +
				'<tr><td class="label r">Описание:<td><input type="text" id="about" class="w300" value="' + o.about + '" />' +
				'<tr><td class="label r">Файл:<td><input type="hidden" id="attach_id-add" value="' + o.attach_id + '" />' +
				'<tr><td class="label r">Со счёта:<td><input type="hidden" id="invoice_id-add" value="' + o.invoice_id + '" />' +
				'<tr><td class="label r">Сумма:' +
					'<td><input type="text" id="sum" class="money" value="' + o.sum + '"' + (o.sum_edit ? '' : ' ') + ' /> руб.' +
			'</table>';
		dialog.content.html(html);
		dialog.submit(submit);

		$('#dtime_add')._calendar({
			lost:1
		});
		$('#category_id-add')._select({
			width:270,
			bottom:5,
			title0:'Не указана',
			spisok:_copySel(EXPENSE_SPISOK, 1),
			func:function(v, id) {
				_expenseSub(v, 0, '-add');
			}
		});
		_expenseSub(o.category_id, o.category_sub_id, '-add');
		$('#about').focus();
		$('#invoice_id-add')._select({
			width:270,
			title0:'Не выбран',
			spisok:o.id ? INVOICE_SPISOK : _invoiceExpenseInsert(),
			func:function() {
				$('#sum').focus();
			},
			disabled:o.id
		});
		$('#attach_id-add')._attach();

		$('#sum').keyEnter(submit);

		function submit() {
			var send = {
				id:o.id,
				op:o.id ? 'expense_edit' : 'expense_add',
				dtime_add:$('#dtime_add').val(),
				category_id:_num($('#category_id-add').val()),
				category_sub_id:_num($('#category_sub_id-add').val()),
				attach_id:$('#attach_id-add').val(),
				about:$('#about').val(),
				invoice_id:_num($('#invoice_id-add').val()),
				sum:_cena($('#sum').val())
			};
			if(!send.about && !send.category_id) {
				dialog.err('Выберите категорию или укажите описание');
				$('#about').focus();
				return;
			}
			if(!send.invoice_id) {
				dialog.err('Укажите с какого счёта производится оплата');
				return;
			}
			if(!send.sum) {
				dialog.err('Некорректно указана сумма');
				$('#sum').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					_expenseSpisok();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	_expenseLoad = function() {
		$('.add').click(function() {
			var dialog = _dialog({
				width:480,
				head:'Внесение расхода'
			});
			_expenseTab(dialog);
		});
		$('#invoice_id')._select({
			width:140,
			title0:'Все счета',
			spisok:INVOICE_SPISOK,
			func:_expenseSpisok
		});
		var spisok = _copySel(EXPENSE_SPISOK);
		spisok.push({
			uid:-1,
			title:'Без категории',
			content:'<b>Без категории</b>'
		});
		$('#category_id')._select({
			width:140,
			bottom:3,
			title0:'Любая категория',
			spisok:spisok,
			func:function(v, id) {
				_expenseSub(v);
				_expenseSpisok(v, id);
			}
		});
		$('#year')._yearLeaf({func:_expenseSpisok});
		$('#mon')._radio({
			spisok:EXPENSE_MON,
			light:1,
			right:0,
			func:_expenseSpisok
		});
		_expenseGraf();
	},
	_expenseGraf = function() {
//		if(!VIEWER_ADMIN)
//			return;
		$('#container').highcharts({
	        chart: {
	            type: 'bar',
		        animation:false
	        },
	        title: {
	            text: 'Сумма в рублях по категориям'
	        },
	        xAxis: {
	            categories: GRAF.categories,
	            title: {
	                text: null
	            }
	        },
	        yAxis: {
	            min: 0,
	            title: {
	                text: '',
	                align: 'high'
	            },
	            labels: {
	                overflow: 'justify'
	            }
	        },
	        tooltip: {
	            enabled:false
	        },
	        plotOptions: {
		        series:{
			        cursor:'pointer'
		        },
	            bar:{
		            dataLabels:{
			            enabled:true
		            },
		            events:{
			            click:function(e) {
				            var i = e.point.index,
						        v = GRAF.index[i],
						        sel = e.point.color != '#ff7777',
						        color0 = Highcharts.getOptions().colors[0],
								color = sel ? '#ff7777' : color0,
						        chart = $('#container').highcharts(),
						        data = chart.series[0].data,
						        len = data.length;

				            for(var n = 0; n < len; n++)
					            data[n].update({color:color0})

							data[i].update({color:color});

				            $('#category_id')._select(sel ? v : 0);
							_expenseSpisok(sel ? v ? v : -1 : 0, 'category_id');
			            }
		            }
	            }
	        },
	        credits: {
	            enabled: false
	        },
	        series: [{
	            showInLegend: false,
	            data: GRAF.sum
	        }]
	    });
	},
	_expenseSpisok = function(v, id) {
		EXPENSE.op = 'expense_spisok';
		EXPENSE.page = 1;
		EXPENSE[id] = v;
		$.post(AJAX_MAIN, EXPENSE, function(res) {
			if(res.success) {
				$('#spisok').html(res.spisok);
				$('#mon')._radio(res.mon);
				if(id != 'category_id') {
					GRAF = res.graf;
					_expenseGraf();
				}
			}
		}, 'json');
	},

	_invoiceEdit = function(o) {
		o = $.extend({
			id:0,
			name:'',
			about:'',
			visible:'',
			income_confirm:0,
			transfer_confirm:0,
			income_insert:'',
			expense_insert:''
		}, o);

		var html =
			'<table id="invoice-edit-tab">' +
				'<tr><td class="label">Наименование:<td><input id="name" type="text" value="' + o.name + '" />' +
				'<tr><td class="label topi">Описание:<td><textarea id="about">' + o.about + '</textarea>' +

				'<tr><td class="label topi">' +
						'Видимость для сотрудников:' +
						'<em>Сотрудники, которые могут видеть этот счёт в списке расчётных счетов.</em>' +
					'<td><input type="hidden" id="visible" value="' + o.visible + '" />' +

				'<tr><td class="label">' +
						'Подтверждение поступления:' +
						'<em>Предлагать подтверждение поступления средств на расчётный счёт.</em>' +
					'<td><input type="hidden" id="income_confirm" value="' + o.income_confirm + '" />' +

				'<tr><td class="label">' +
						'Подтверждение перевода:' +
						'<em>Требовать подтверждение, если по этому расчётному счёту был совершён перевод.</em>' +
					'<td><input type="hidden" id="transfer_confirm" value="' + o.transfer_confirm + '" />' +

				'<tr><td class="label topi">' +
						'Внесение платежей и возвратов:' +
						'<em>Сотрудники, которые могут производить платежи и возвраты по этому счёту.</em>' +
					'<td><input type="hidden" id="income_insert" value="' + o.income_insert + '" />' +

				'<tr><td class="label topi">' +
						'Внесение расходов и выдача з/п:' +
						'<em>Сотрудники, которые могут вносить расходы и выдавать з/п с этого счёта.</em>' +
					'<td><input type="hidden" id="expense_insert" value="' + o.expense_insert + '" />' +
			'</table>',
		dialog = _dialog({
			top:40,
			width:500,
			content:html,
			head:(o.id ? 'Редактирование' : 'Создание нового') + ' расчётного счёта',
			butSubmit:o.id ? 'Сохранить' : 'Внести',
			submit:submit
		});

		$('#name').focus();
		$('#about').autosize();
		$('#visible')._select({
			width:218,
			title0:'Сотрудники не выбраны',
			multiselect:1,
			spisok:_toSpisok(WORKER_ASS)
		});
		$('#income_confirm')._check();
		$('#transfer_confirm')._check();
		$('#income_insert')._select({
			width:218,
			title0:'Сотрудники не выбраны',
			multiselect:1,
			spisok:_toSpisok(WORKER_ASS)
		});
		$('#expense_insert')._select({
			width:218,
			title0:'Сотрудники не выбраны',
			multiselect:1,
			spisok:_toSpisok(WORKER_ASS)
		});
		function submit() {
			var send = {
				op:o.id ? 'invoice_edit' : 'invoice_add',
				id:o.id,
				name:$('#name').val(),
				about:$('#about').val(),
				visible:$('#visible').val(),
				income_confirm:$('#income_confirm').val(),
				transfer_confirm:$('#transfer_confirm').val(),
				income_insert:$('#income_insert').val(),
				expense_insert:$('#expense_insert').val()
			};
			if(!send.name) {
				dialog.err('Не указано наименование');
				$('#name').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#invoice-spisok').html(res.html);
					dialog.close();
					_msg();
					location.reload();
				} else {
					dialog.abort();
					dialog.err(res.text);
				}
			}, 'json');
		}
	},
	_invoiceTransfer = function(o) {
		o = $.extend({
			id:0,
			from:0,
			to:0,
			sum:'',
			about:''
		}, o);
		var t = $(this),
			html = '<table class="_dialog-tab">' +
					'<tr><td class="label">Со счёта:<td><input type="hidden" id="from" value="' + o.from + '" />' +
					'<tr><td class="label">На счёт:<td><input type="hidden" id="to" value="' + o.to + '" />' +
					'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money"' + (o.id ? ' disabled' : '') + ' value="' + o.sum + '" /> руб. ' +
					'<tr><td class="label">Комментарий:<td><input type="text" id="about" value="' + o.about + '" />' +
				'</table>',
			dialog = _dialog({
				head:'Перевод между счетами',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Перевести',
				submit:submit
			});
		$('#from')._select({
			width:218,
			title0:'Не выбран',
			disabled:o.id,
			spisok:INVOICE_SPISOK
		});
		$('#to')._select({
			width:218,
			title0:'Не выбран',
			disabled:o.id,
			spisok:INVOICE_SPISOK,
			func:function() {
				$('#sum').focus();
			}
		});
		$(o.id ? '#about' : '#sum').focus();
		function submit() {
			var send = {
				op:'invoice_transfer_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				from:_num($('#from').val()),
				to:_num($('#to').val()),
				sum:_cena($('#sum').val()),
				about:$('#about').val()
			};
			if(!send.from) {
				dialog.err('Выберите счёт-отправитель');
				return;
			}
			if(!send.to) {
				dialog.err('Выберите счёт-получатель');
				return;
			}
			if(send.from == send.to) {
				dialog.err('Счета не могут совпадать');
				return;
			}
			if(!send.sum) {
				dialog.err('Некорректно введена сумма');
				$('#sum').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#invoice-spisok').html(res.i);
					$('#transfer-spisok').html(res.t);
					dialog.close();
					_msg();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	_invoiceIn = function(invoice_id, balans) {
		var t = $(this),
			html = '<table class="_dialog-tab">' +
					'<tr><td class="label">Счёт:<td><input type="hidden" id="invoice_id" value="' + _num(invoice_id) + '" />' +
		  (balans ? '<tr><td class="label">Баланс:<td><b>' + balans + '</b> руб.' : '') +
					'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money" /> руб. ' +
					'<tr><td class="label">Комментарий:<td><input type="text" id="about" />' +
				'</table>',
			dialog = _dialog({
				top:30,
				head:'Внесение денег на счёт',
				content:html,
				submit:submit
			});

		$('#invoice_id')._select({
			width:218,
			title0:'Не выбран',
			spisok:INVOICE_SPISOK,
			func:function() {
				$('#sum').focus();
			}
		});
		$('#sum').focus();
		function submit() {
			var send = {
				op:'invoice_in_add',
				invoice_id:$('#invoice_id').val(),
				sum:_cena($('#sum').val()),
				about:$('#about').val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#invoice-spisok').html(res.i);
					$('#inout-spisok').html(res.io);
					dialog.close();
					_msg();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	_invoiceOut = function(invoice_id, balans) {
		var t = $(this),
			html = '<table class="_dialog-tab">' +
					'<tr><td class="label">Счёт:<td><input type="hidden" id="invoice_id" value="' + _num(invoice_id) + '" />' +
		  (balans ? '<tr><td class="label">Баланс:<td><b>' + balans + '</b> руб.' : '') +
					'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money" /> руб. ' +
					'<tr><td class="label">Получатель:<td><input type="hidden" id="worker_id" />' +
					'<tr><td class="label">Комментарий:<td><input type="text" id="about" />' +
				'</table>',
			dialog = _dialog({
				top:30,
				head:'Вывод денег со счёта',
				content:html,
				butSubmit:'Вывести',
				submit:submit
			});

		$('#invoice_id')._select({
			width:218,
			title0:'Не выбран',
			spisok:INVOICE_SPISOK,
			func:function() {
				$('#sum').focus();
			}
		});
		$('#sum').focus();
		$('#worker_id')._select({
			width:218,
			title0:'Сотрудник не выбран',
			spisok:_toSpisok(WORKER_ASS)
		});

		function submit() {
			var send = {
				op:'invoice_out_add',
				invoice_id:$('#invoice_id').val(),
				sum:_cena($('#sum').val()),
				worker_id:_num($('#worker_id').val()),
				about:$('#about').val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#invoice-spisok').html(res.i);
					$('#inout-spisok').html(res.io);
					dialog.close();
					_msg();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	_invoiceBalansSet = function(invoice_id) {
		var html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">Счёт:<td><b>' + INVOICE_ASS[invoice_id] + '</b>' +
					'<tr><td class="label">Сумма:<td><input type="text" class="money" id="sum" /> руб.' +
				'</table>';
		var dialog = _dialog({
			width:270,
			head:'Установка текущей суммы счёта',
			content:html,
			butSubmit:'Установить',
			submit:submit
		});

		$('#sum').focus();

		function submit() {
			var send = {
				op:'invoice_set',
				invoice_id:invoice_id,
				sum:$('#sum').val()
			};
			if(send.sum != 0 && !_cena(send.sum)) {
				dialog.err('Некорректно указана сумма');
				$('#sum').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#invoice-spisok').html(res.html);
					dialog.close();
					_msg();
					sortable();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	_invoiceReset = function(invoice_id) {
		var html = 'Сумма на счёте <b>' + INVOICE_ASS[invoice_id] + '</b> будет сброшена.',
			dialog = _dialog({
				head:'Сброс суммы счёта',
				content:html,
				butSubmit:'Применить',
				submit:submit
			});

		function submit() {
			var send = {
				op:'invoice_reset',
				invoice_id:invoice_id
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#invoice-spisok').html(res.html);
					dialog.close();
					_msg();
					sortable();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	_invoiceClose = function(invoice_id, ost) {
		var html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">Счёт:<td><b>' + INVOICE_ASS[invoice_id] + '</b>' +
				(ost ?
					'<tr><td class="label">Остаток:<td><b>' + ost + '</b> руб.' +
					'<tr><td class="label">Перевести остаток на счёт:<td><input type="hidden" id="invoice_to" />'
				: '') +
				'</table>',
			dialog = _dialog({
				width:420,
				head:'Закрытие счёта',
				content:html,
				butSubmit:'Закрыть счёт',
				submit:submit
			});

		$('#invoice_to')._select({
			width:200,
			title0:'Счёт не выбран',
			spisok:_copySel(INVOICE_SPISOK, invoice_id)
		});

		function submit() {
			var send = {
				op:'invoice_close',
				invoice_id:invoice_id,
				invoice_to:ost ? _num($('#invoice_to').val()) : 0
			};
			if(ost && !send.invoice_to) {
				dialog.err('Не указан номер счёта-получателя');
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#invoice-spisok').html(res.html);
					dialog.close();
					_msg();
					sortable();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	_invoiceIncomeInsert = function(def) {//составление списка счетов, которые может выбрать сотрудник
		if(window.IIID) {//INVOICE_INCOME_INSERT_DEFINED
			if(def) {
				var ass = _toAss(IIID);
				return ass[VIEWER_INVOICE_ID] ? VIEWER_INVOICE_ID : 0;
			}
			return IIID;
		}
		var send = [];
		for(var n = 0; n < INVOICE_SPISOK.length; n++) {
			var sp = INVOICE_SPISOK[n];
			if(!INVOICE_INCOME_INSERT[sp.uid][VIEWER_ID])
				continue;
			send.push(sp);
		}
		window.IIID = send;
		return _invoiceIncomeInsert(def);
	},
	_invoiceExpenseInsert = function(def) {//составление списка счетов, которые может выбрать сотрудник
		if(window.IEID) {//INVOICE_EXPENSE_INSERT_DEFINED
			if(def) {
				var ass = _toAss(IEID);
				return ass[VIEWER_INVOICE_ID] ? VIEWER_INVOICE_ID : 0;
			}
			return IEID;
		}
		var send = [];
		for(var n = 0; n < INVOICE_SPISOK.length; n++) {
			var sp = INVOICE_SPISOK[n];
			if(!INVOICE_EXPENSE_INSERT[sp.uid][VIEWER_ID])
				continue;
			send.push(sp);
		}
		window.IEID = send;
		return _invoiceExpenseInsert(def);
	},

	_balansShow = function(category_id, unit_id) {
		var dialog = _dialog({
				top:10,
				width:650,
				padding:0,
				head:'Просмотр истории операций',
				load:1,
				butSubmit:'',
				butCancel:'Закрыть'
			}),
			send = {
				op:'balans_show',
				category_id:category_id,
				unit_id:unit_id
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				loaded(res);
			 else
				dialog.loadError();
		}, 'json');

		function loaded(res) {
			dialog.content.html(res.html);
			$('#menu_id')._menuDop({
				spisok:[
					{uid:1, title:'Подробно'},
					{uid:2, title:'Ежедневно'}
				]
			});
			$('#menu_year')._menuDop({
				type:2,
				spisok:YEAR_SPISOK,
				func:function(v) {
					BALANS.op = 'balans_everyday';
					BALANS.everyday_year = v;
					$.post(AJAX_MAIN, BALANS, function(res) {
						if(res.success)
							$('#spisok2').html(res.html);
					}, 'json');
				}
			});
			$('#menu_mon')._menuDop({
				type:2,
				spisok:[
					{uid:1, title:'Янв'},
					{uid:2, title:'Фев'},
					{uid:3, title:'Мар'},
					{uid:4, title:'Апр'},
					{uid:5, title:'Май'},
					{uid:6, title:'Июн'},
					{uid:7, title:'Июл'},
					{uid:8, title:'Авг'},
					{uid:9, title:'Сен'},
					{uid:10, title:'Окт'},
					{uid:11, title:'Ноя'},
					{uid:12, title:'Дек'}
				],
				func:function(v) {
					BALANS.op = 'balans_everyday';
					BALANS.everyday_mon = v;
					$.post(AJAX_MAIN, BALANS, function(res) {
						if(res.success)
							$('#spisok2').html(res.html);
					}, 'json');
				}
			});
		}
	},
	_balansSpisok = function(v, id) {
		_filterSpisok(BALANS, v, id);
		$.post(AJAX_MAIN, BALANS, function(res) {
			if(res.success) {
				$('.menu_id-1').html(res.spisok);
				$('#menu_id')._menuDop(1);
			}
		}, 'json');
	},

	_salaryNoAccRecalcHint = function() {
		$('#noacc-recalc').vkHint({
			width:330,
			top:-48,
			left:16,
			ugol:'right',
			indent:30,
			msg:'<b>Произвести перерасчёт начислений з/п по заявкам.</b>' +
				'<br />' +
				'Перерасчёт будет произведёт с учётом настройки <u>Начислять з/п по заявке при отсутствии долга.</u>' +
				'<br />' +
				'<br />' +
				'Если галочка <b>установлена</b>, будут найдены все начисления по неоплаченным заявкам за весь период, ' +
				'которые не зафиксированы, и помещены в данный список.' +
				'<br />' +
				'<br />' +
				'Если галочка <b>не установлена</b>, все начисления из неактивного списка будут перенесены в текущий месяц.' +
				'<br />' +
				'<br />' +
				'Начисления з/п по заявкам, которые были оплачены, будут перемещены из неактивного списка в текущий месяц <b>в любом случае</b>.' +
				'<br />' +
				'<br />' +
				'Все начисления з/п в удалённых заявках будут удалены, если таковые будут найдены.' +
				'<br />' +
				'<br />' +
				'Будет сделан перерасчёт баланса з/п сотрудника, и если сумма изменится, будет сделана запись в истории балансов.'
		});
	},
	_salarySpisok = function(v, id) {
		if(id == 'year') {
			v = SALARY.year > v ? 12 : 1;
			$('#salmon')._radio(v);
			SALARY.mon = v;
		}
		var send = {
			op:'salary_spisok',
			id:SALARY.worker_id,
			acc_show:$('#spisok-acc ._spisok').hasClass('dn') ? 0 : 1,
			year:_num($('#year').val()),
			mon:_num($('#salmon').val())
		};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				SALARY.year = send.year;
				SALARY.mon = send.mon;
				$('.headName em').html(MONTH_DEF[send.mon] + ' ' + send.year);
				$('.balans').html(res.balans);
				$('#spisok-list').html(res.list);
				SALARY.list = res.list_array;
				$('#spisok-acc').html(res.acc);
				$('#spisok-noacc').html(res.noacc);
				$('#spisok-zp').html(res.zp);
				$('#month-list').html(res.month);
				_salaryNoAccRecalcHint();
			}
		}, 'json');
	},
	_salaryWorkerBalansSet = function() {//установка баланса зп сотрудника
		var html =
		'<table class="_dialog-tab">' +
			'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money"> руб.' +
		'</table>',
		dialog = _dialog({
			width:320,
			head:'Установка суммы баланса з/п сотрудника',
			content:html,
			butSubmit:'Применить',
			submit:submit
		});

		$('#sum').focus();

		function submit() {
			var send = {
				op:'salary_balans_set',
				worker_id:SALARY.worker_id,
				sum:$('#sum').val()
			};
			if(!_cena(send.sum, 1) && send.sum != 0) {
				dialog.err('Некорректно указана сумма');
				$('#sum').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					$('.balans').html(res.balans);
				} else
					dialog.abort();
			}, 'json');
		}
	},
	_salaryWorkerRateSet = function() {//установка ставки сотрудника
		var period = SALARY.rate_period ? SALARY.rate_period : 1,
			html =
			'<div class="_info">' +
				'После установки ставки сотруднику указанная сумма будет автоматически начисляться ' +
				'на его баланс в определённый день выбранной периодичностью. ' +
			'</div>' +
			'<table class="_dialog-tab" id="salary-rate-set-tab">' +
				'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money" value="' + (SALARY.rate_sum ? SALARY.rate_sum : '') + '" /> руб.' +
				'<tr><td class="label">Период:<td><input type="hidden" id="period" value="' + period + '" />' +
				'<tr class="tr-day' + (period > 2 ? ' dn' : '') + '">' +
					'<td class="label">День начисления:' +
					'<td><div class="div-day' + (period != 1 ? ' dn' : '') + '"><input type="text" id="day" maxlength="2" value="' + SALARY.rate_day + '" /></div>' +
						'<div class="div-week' + (period != 2 ? ' dn' : '') + '"><input type="hidden" id="day_week" value="' + SALARY.rate_day + '" /></div>' +
			'</table>',
			dialog = _dialog({
				top:30,
				width:320,
				head:'Установка ставки з/п для сотрудника',
				content:html,
				butSubmit:'Установить',
				submit:submit
			});

		$('#sum').focus();
		$('#period')._select({
			width:100,
			spisok:SALARY_PERIOD_SPISOK,
			func:function(id) {
				$('#day_week')._select(1);
				$('.tr-day')[(id == 3 ? 'add' : 'remove') + 'Class']('dn');
				$('.div-day')[(id != 1 ? 'add' : 'remove') + 'Class']('dn');
				$('.div-week')[(id != 2 ? 'add' : 'remove') + 'Class']('dn');
			}
		});
		$('#day_week')._select({
			spisok:[
				{uid:1,title:'Понедельник'},
				{uid:2,title:'Вторник'},
				{uid:3,title:'Среда'},
				{uid:4,title:'Четверг'},
				{uid:5,title:'Пятница'},
				{uid:6,title:'Суббота'},
				{uid:7,title:'Воскресенье'}
			]
		});

		function submit() {
			var send = {
				op:'salary_rate_set',
				worker_id:SALARY.worker_id,
				sum:_cena($('#sum').val()),
				period:_num($('#period').val()),
				day:_num($('#day').val())
			};
			if(send.period == 2)
				send.day = _num($('#day_week').val());
			if(send.period == 1 && (!send.day || send.day > 28)) {
				dialog.err('Некорректно указан день');
				$('#day').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						SALARY.rate_sum = send.sum;
						SALARY.rate_period = send.period;
						SALARY.rate_day = send.day;
						dialog.close();
						_msg();
						$('h1 em').html(res.rate);
					} else
						dialog.abort();
				}, 'json');
			}
		}
	},
	_salaryWorkerAccAdd = function() {//внесение произвольного начисления зп сотрудника
		var html =
			'<table class="_dialog-tab">' +
				'<tr><td class="label">Сотрудник:<td><u>' + WORKER_ASS[SALARY.worker_id] + '</u>' +
				'<tr><td class="label">Месяц:<td><b>' + MONTH_DEF[SALARY.mon] + ' ' + SALARY.year + '</b>' +
				'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money"> руб.' +
				'<tr><td class="label">Описание:<td><input type="text" id="about" maxlength="50">' +
			'</table>',
			dialog = _dialog({
				head:'Внесение начисления для сотрудника',
				content:html,
				submit:submit
			});

		$('#sum').focus();
		function submit() {
			var send = {
				op:'salary_accrual_add',
				worker_id:SALARY.worker_id,
				sum:_cena($('#sum').val()),
				about:$.trim($('#about').val()),
				mon:SALARY.mon,
				year:SALARY.year
			};
			if(!send.sum) {
				dialog.err('Некорректно указана сумма');
				$('#sum').focus();
			} else if(!send.about) {
				dialog.err('Не указано описание');
				$('#about').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('Начисление произведено');
						_salarySpisok();
					} else
						dialog.abort();
				}, 'json');
			}
		}

	},
	_salaryWorkerDeductAdd = function() {//внесение вычета из зп сотрудника
		var html =
			'<table class="_dialog-tab">' +
				'<tr><td class="label">Сотрудник:<td><u>' + WORKER_ASS[SALARY.worker_id] + '</u>' +
				'<tr><td class="label">Месяц:<td><b>' + MONTH_DEF[SALARY.mon] + ' ' + SALARY.year + '</b>' +
				'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money" /> руб.' +
				'<tr><td class="label">Описание:<td><input type="text" id="about" />' +
			'</table>',
			dialog = _dialog({
				head:'Внесение вычета из зарплаты',
				content:html,
				submit:submit
			});

		$('#sum').focus();
		function submit() {
			var send = {
				op:'salary_deduct_add',
				worker_id:SALARY.worker_id,
				sum:_cena($('#sum').val()),
				about:$.trim($('#about').val()),
				mon:SALARY.mon,
				year:SALARY.year
			};
			if(!send.sum) {
				dialog.err('Некорректно указана сумма');
				$('#sum').focus();
			} else if(!send.about) {
				dialog.err('Не указано описание');
				$('#about').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('Начисление произведено');
						_salarySpisok();
					} else
						dialog.abort();
				}, 'json');
			}
		}
	},
	_salaryWorkerListNoSelect = function() {
		var html =
				'<div id="salary-list-tab">' +
					'<div class="_info">' +
						'Чтобы сформировать <b>' + LIST_VYDACI + '</b>, необходимо выбрать начисления.' +
					'</div>' +
				'</div>',
			dialog = _dialog({
				padding:30,
				width:305,
				head:LIST_VYDACI + ': создание',
				content:html,
				butSubmit:'',
				butCancel:'Закрыть'
			});
	},
	_salaryWorkerListCreate = function() {
		if(!_checkAll())
			return _salaryWorkerListNoSelect();

		var html =
				'<div id="salary-list-tab">' +
					'<div class="_info">' +
						'<h1>' + LIST_VYDACI + ': создание</h1>' +
						'После формирования все ' +
						'выделенные галочками начисления и вычеты станут фиксированными, ' +
						'то есть их нельзя будет изменить.' +
					'</div>' +
					'<table>' +
						'<tr><td class="label">Сотрудник:<td><u>' + WORKER_ASS[SALARY.worker_id] + '</u>' +
						'<tr><td class="label">Месяц:<td><b>' + MONTH_DEF[SALARY.mon] + ' ' + SALARY.year + '</b>' +
						'<tr><td class="label">Выбрано записей:<td>' + _checkAll('count') +
						'<tr><td class="label">Сумма:<td><b>' + _checkAll('sum') + '</b> руб.' +
					'</table>' +
					'<div class="_info">' +
						'Все ранее выданные з/п в этом месяце, которые не были привязаны, попадут в <b>текущий документ</b> и будут отмечены как <b>авансы</b>.' +
					'</div>' +
					'<a href="' + URL + '&p=22" id="list-set">Настроить ' + LIST_VYDACI + '</a>' +
				'</div>',
			dialog = _dialog({
				width:330,
				head:LIST_VYDACI + ': создание',
				content:html,
				butSubmit:'Сформировать',
				submit:submit
			});
		function submit() {
			var send = {
				op:'salary_list_create',
				worker_id:SALARY.worker_id,
				ids:_checkAll('type'),
				mon:SALARY.mon,
				year:SALARY.year
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					_salarySpisok();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	_salaryWorkerZpAdd = function(o) {//внесение/редактировыние зп сотрудника
		o = $.extend({
			id:0,
			dtime_add:'',
			invoice_id:_invoiceExpenseInsert(1),
			sum:'',
			about:'',
			mon:SALARY.mon,
			year:SALARY.year,
			salary_avans:0,
			salary_list_id:0
		}, o);

		var html =
				'<table class="bs10">' +
//					'<tr' + (APP_ID == 3495523 && !o.id ? '' : ' class="dn"') + '><td class="label r">День внесения:<td><input type="hidden" id="dtime_add" value="' + o.dtime_add + '" />' + //todo временно
					'<tr><td class="label r w125">Сотрудник:<td><u>' + WORKER_ASS[SALARY.worker_id] + '</u>' +
					'<tr><td class="label r">Месяц:' +
						'<td><input type="hidden" id="salary_mon" value="' + o.mon + '" /> ' +
							'<input type="hidden" id="salary_year" value="' + o.year + '" />' +
					'<tr><td class="label r">Со счёта:<td><input type="hidden" id="invoice_id" value="' + o.invoice_id + '" />' +
					'<tr><td class="label r">Сумма:<td><input type="text" id="sum" class="money" value="' + o.sum + '"' + (o.id ? ' disabled' : '') + ' /> руб.' +
					'<tr><td class="label r">Аванс:<td><input type="hidden" id="salary_avans" value="' + o.salary_avans + '" />' +
					'<tr><td class="label r">Комментарий:<td><input type="text" id="about" class="w200" placeholder="не обязательно" value="' + o.about + '" />' +
					'<tr' + (SALARY.list.length ? '' : ' class="dn"') + '>' +
						'<td class="label r">' + LIST_VYDACI + ':' +
						'<td><input type="hidden" id="salary_list_id" value="' + o.salary_list_id + '" />' +
				'</table>',
			dialog = _dialog({
				width:450,
				padding:20,
				head:(o.id ? 'Редактирование' : 'Выдача') + ' зарплаты',
				content:html,
				submit:submit,
				butSubmit:o.id ? 'Сохранить' : 'Выдать'
			});

		$('#dtime_add')._calendar({
			lost:1
		});
		$('#sum').focus();
		$('#invoice_id')._select({
			width:218,
			title0:'счёт не выбран',
			spisok:o.id ? INVOICE_SPISOK : _invoiceExpenseInsert(),
			disabled:o.id,
			func:function() {
				$('#sum').focus();
			}
		});
		$('#salary_mon')._select({
			width:90,
			spisok:_toSpisok(MONTH_DEF)
		});
		$('#salary_year')._select({
			width:70,
			spisok:_yearSpisok()
		});
		$('#salary_avans')._check();
		$('#salary_list_id')._select({
			width:218,
			title0:'не выбран',
			spisok:SALARY.list
		});

		function submit() {
			var send = {
				op:'expense_' + (o.id ? 'edit' : 'add'),
				id:o.id,
//				dtime_add:$('#dtime_add').val(),
				category_id:1,
				worker_id:SALARY.worker_id,
				invoice_id:$('#invoice_id').val(),
				sum:$('#sum').val(),
				about:$('#about').val(),
				salary_avans:_bool($('#salary_avans').val()),
				salary_list_id:_num($('#salary_list_id').val()),
				mon:$('#salary_mon').val(),
				year:$('#salary_year').val()
			};

			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					_salarySpisok();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	_salaryWorkerNoAccRecalc = function() {//перерасчёт начислений з/п по заявкам
		var send = {
				op:'salary_noacc_recalc',
				worker_id:SALARY.worker_id
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				_msg();
				location.reload();
			}
		}, 'json');
	},

	schetPayEdit = function(schet_id) {
		schet_id = _num(schet_id);
		var dialog = _dialog({
				top:20,
				width:740,
				head:(schet_id ? 'Редактирование' : 'Создание') + ' счёта на оплату',
				load:1,
				butSubmit:schet_id ? 'Сохранить' : '',
				submit:submit,
				cancel:cancel
			}),
			send = {
				op:'schet_pay_load',
				schet_id:schet_id,
				client_id:window.CI ? CI.id : 0,
				zayav_id:window.ZI ? ZI.id : 0,
				cartridge_ids:window.CARTRIDGE_IDS,//id картриджей
				gn_ids:window.GN_IDS//id номеров газеты
			};

		window.CARTRIDGE_IDS = 0;//очистка id картриджей, чтобы не подставлялись в другие счета
		window.GN_IDS = 0;//очистка id номеров картриджей, чтобы не подставлялись в другие счета

		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				loaded(res);
			else
				dialog.loadError(res.text);
		}, 'json');
		
		function loaded(res) {
			dialog.content.html(res.html);
			$('#date-create')._calendar({lost:1});
			$('#act-date')._calendar({lost:1});

			if(!res.client_id)
				$('#client_id').clientSel({
					width:300,
					add:!$('#client-info').length
				});
			else
				$('#zayav_id')._select({
					width:300,
					write:1,
					title0:'Заявка не выбрана',
					spisok:res.zayav_spisok
				});

			$('#org_id')._select({
				width:300,
				spisok:res.org,
				func:function(v) {
					if(res.bank[v]) {
						var len = res.bank[v].length;
						$('#bank_id')
							._select(res.bank[v])
							._select('first');
						$('.bank-0').hide();
						$('.bank-1')[len == 1 ? 'show' : 'hide']();
						$('.bank-2')[len > 1 ? 'show' : 'hide']();
					} else {
						$('#bank_id')._select(0);
						$('.bank-0').show();
						$('.bank-1').hide();
						$('.bank-2').hide();
					}
				}
			});
			$('#bank_id')._select({
				width:300,
				spisok:res.bank[res.org_id]
			});
			$('#schet-pay-content').schetPayContent({
				spisok:res.content,
				noedit:res.noedit
			});
			$('#schet-pay-type')._radio(function(v) {
				$('#schet-pay-type-select').hide();
				$('#schet-pay-edit').show();
				if(v == 2)
					$('#schet-pay-head').html('ОЗНАКОМИТЕЛЬНЫЙ СЧЁТ');
				dialog.butSubmit(v == 1 ? 'Сформировать счёт на оплату' : 'Сохранить ознакомительный счёт');
				if(v == 2)
					$('#nomer').val('-');
			});
			if(!schet_id && !$('#schet-pay-type').length)
				dialog.butSubmit('Сформировать счёт на оплату');
		}
		function submit() {
			var send = {
				op:'schet_pay_' + (schet_id ? 'edit' : 'add'),
				schet_id:schet_id,
				type_id:$('#schet-pay-type').val() || 1,
				org_id:$('#org_id').val(),
				bank_id:$('#bank_id').val(),
				date_create:$('#date-create').val(),
				act_date:$('#act-date').val(),
				client_id:$('#client_id').val(),
				zayav_id:$('#zayav_id').val(),
				content:$('#schet-pay-content').val(),
				cartridge_ids:$('#cartridge_ids').val(),
				gn_ids:$('#gn_ids').val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if (res.success) {
					dialog.close();
					schetPayShow(res.schet_id, 1);
				} else
					dialog.abort(res.text);
			}, 'json');
		}
		function cancel() {
			if(!schet_id)
				return;
			schetPayShow(schet_id);
		}
	},
	schetPayShow = function(schet_id, edited, e) {//просмотр счёта на оплату
		if(e)
			e.stopPropagation();
		edited = _num(edited);
		var dialog = _dialog({
				top:20,
				width:740,
				head:'Просмотр счёта на оплату',
				load:1,
				butSubmit:'',
				butCancel:'Закрыть',
				cancel:function() {
					if(!edited)
						return;
					if($('#schet-pay-menu').length)
						schetPaySpisok();
					if(window.ZI || window.CI)
						location.reload();
				}
			}),
			send = {
				op:'schet_pay_show',
				schet_id:schet_id
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				loaded(res);
			else
				dialog.loadError(res.text);
		}, 'json');

		function loaded(res) {
			dialog.content
				.html(res.html)
				.find('.icon-edit').parent().click(function() {
					dialog.close();
					schetPayEdit(schet_id);
				});

			dialog.content.find('.bg-link').click(function() {//раскрытие истории действий
				$(this)
					.slideUp(200)
					.next().slideDown(200);
			});

			$('#schet-pay-to-pay').click(function() {
				schetPayToPay(schet_id, dialog);
			});

			dialog.content.find('.icon-out').parent().click(function() {//передача счёта клиенту
				var html =
						'<table class="bs10">' +
							'<tr><td class="label r w125">№ счёта:<td><b>' + res.nomer + '</b>' +
							'<tr><td class="label r">Когда передан:<td><input type="hidden" id="pass-day" />' +
						'</table>',
					dPass = _dialog({
						width:400,
						padding:30,
						head:'Передача счёта клиенту',
						content:html,
						butSubmit:'Применить',
						submit:submit
					});

				$('#pass-day')._calendar({lost:1});
				function submit() {
					var send = {
						op:'schet_pay_pass',
						schet_id:schet_id,
						day:$('#pass-day').val()
					};
					dPass.process();
					$.post(AJAX_MAIN, send, function(res) {
						if(res.success) {
							dPass.close();
							_msg();
							dialog.close();
							schetPayShow(schet_id, 1);
						} else
							dPass.abort(res.text);
					}, 'json');
				}
			});

			dialog.content.find('.pass-cancel').click(function() {//отмена передачи счёта клиенту
				var dPass = _dialog({
					padding:50,
					head:'Отмена передачи счёта клиенту',
					content:'<div class="center">Подтвердите отмену передачи<br />счёта <b>' + res.nomer + '</b> клиенту.</div>',
					butSubmit:'Подтвердить',
					submit:function() {
						var send = {
							op:'schet_pay_pass_cancel',
							schet_id:schet_id
						};
						dPass.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								dPass.close();
								_msg();
								dialog.close();
								schetPayShow(schet_id, 1);
							} else
								dPass.abort(res.text);
						}, 'json');
					}
				});
			});

			dialog.content.find('.icon-rub').parent().click(function() {//оплата счёта
				var html =
						'<table class="bs10">' +
							'<tr><td class="label r w125">№ счёта:<td><b>' + res.nomer + '</b>' +
							'<tr><td class="label r">Расчётный счёт:<td><input type="hidden" id="pay-invoice_id" value="' + res.invoice_id + '" />' +
							'<tr><td class="label r">Сумма:<td><input type="text" class="money" id="sum" value="' + res.sum_to_pay +'" /> руб.' +
							'<tr><td class="label r">День оплаты:<td><input type="hidden" id="pay-day" />' +
						'</table>',
					dPay = _dialog({
						width:450,
						head:'Оплата счёта',
						content:html,
						butSubmit:'Оплатить',
						submit:submit
					});

				$('#pay-day')._calendar({lost:1});
				$('#pay-invoice_id')._select({
					width:250,
					title0:'не указан',
					spisok:INVOICE_SPISOK,
					func:function() {
						$('#sum').focus();
					}
				});
				$('#sum').focus();
				function submit() {
					var send = {
						op:'schet_pay_pay',
						schet_id:schet_id,
						invoice_id:$('#pay-invoice_id').val(),
						sum:$('#sum').val(),
						day:$('#pay-day').val()
					};
					dPay.process();
					$.post(AJAX_MAIN, send, function(res) {
						if(res.success) {
							dPay.close();
							_msg();
							dialog.close();
							schetPayShow(schet_id, 1);
						} else
							dPay.abort(res.text);
					}, 'json');
				}

			});

			dialog.content.find('.icon-del-red').parent().click(function() {
				_dialogDel({
					id:schet_id,
					head:'счёта на оплату',
					op:'schet_pay_del',
					func:function() {
						dialog.close();
						schetPayShow(schet_id, 1);
					}
				});
			});
		}
	},
	schetPayToPay = function(schet_id, schet_dialog) {//перевод ознакомительного счёта на счёт на оплату
		var html =
				'<b>Будут выполнены следующие действия:</b>' +
				'<div class="mt5 ml20">1. Счёту будет присвоен порядковый номер.</div>' +
				'<div class="mt5 ml20">2. Клиенту будет произведено начисление на суммy, указанную в счёте.</div>' +
				'<div class="mt5 ml20">3. Счёт можно будет передавать клиенту</div>' +
				'<div class="mt5 ml20">4. По счёту можно будет производить платежи.</div>',
			dialog = _dialog({
				width:540,
				padding:30,
				head:'Изменение статуса счёта',
				content:html,
				butSubmit:'Применить',
				submit:submit
			});

		function submit() {
			var send = {
				op:'schet_pay_to_pay',
				schet_id:schet_id
			};
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					schet_dialog.close();
					schetPayShow(schet_id, 1);
				} else
					dialog.err(res.text);
			}, 'json');
		}
	},
	schetPaySpisok = function(v, id) {
		_filterSpisok(SCHET_PAY, v, id);
		SCHET_PAY.page = 1;
		if(id)
			SCHET_PAY[id] = v;

		$('#td-group')[(SCHET_PAY.find ? 'add' : 'remove') + 'Class']('dn');
		$.post(AJAX_MAIN, SCHET_PAY, function(res) {
			if(res.success) {
				$('#schet-pay-spisok').html(res.spisok);
			}
		}, 'json');
	},
	schetPayFilterClear = function() {//очистка фильтра поиска счетов на оплату
		$('#find')._search('clear');    SCHET_PAY.find = '';
		$('#group_id')._dropdown(0);    SCHET_PAY.group_id = 0;
		SCHET_PAY.mon = '';
		schetPaySpisok();
	},
	schetPayAllRemove = function(but) {
		var html =
				'<div class="_info">' +
					'1. Все счета на оплату будут удалены.' +
					'<br />' +
					'2. Также будет удалена история действий по счетам на оплату.' +
					'<br />' +
					'3. Счётчик номеров будет сброшен согласно настройке.' +
					'<br />' +
					'4. Начисления по счетам будут удалены.' +
					'<br />' +
					'5. История балансов будет отвязана от счетов.' +
					'<br />' +
					'6. Платежи будут отвязаны от счетов.' +
					'<br />' +
					'7. Картриджи будут отвязаны от счетов.' +
					'<br />' +
					'8. Номера газет будут отвязаны от счетов.' +
					'<br />' +
					'<br />' +
					'<div class="red b center">Операция необратима.</div>' +
				'</div>' +
				'<div class="mt20 mb10 center">Подтвердите данное действие.</div>',
			dialog = _dialog({
				width:450,
				head:'Удаление всех счетов на оплату',
				content:html,
				submit:submit,
				butSubmit:'Удалить все счета на оплату'
			});
		function submit() {
			$.post(AJAX_MAIN, {op:'schet_pay_all_remove'}, function(res) {
				if(res.success) {
					_msg();
					location.reload();
				}
			}, 'json');
		}
	};

$.fn.schetPayContent = function(o) {//составление списка содержания для расчётного счёта
	var t = $(this),
		attr_id = t.attr('id');

	if(!attr_id)
		return;

	o = $.extend({
		spisok:[],
		noedit:0,//возможность добавлять поля
		func:function() {}
	}, o);

	var html =
		'<div class="sp-content">' +
			'<dl></dl>' +
			'<a class="bg-link mt5 mb20' + (o.noedit ? ' dn' : '') + '">Добавить позицию</a>' +
			'<div class="no-correct red fr dn">Не все поля заполнены корректно.</div>' +
			'<div class="itog b mt5"></div>' +
		'</div>';

	t.after(html);

	var CONT = t.next(),
		SORT = CONT.find('dl'),
		BGL = CONT.find('.bg-link'), //кнопка Добавить позицию
		NO_CORRECT = CONT.find('.no-correct'),  //строка вывода сообщения об ошибке красным цветом
		ITOG = CONT.find('.itog'),              //строка подитога
		MEASURE_ASS = _toAss(TOVAR_MEASURE_SPISOK);

	SORT.sortable({
		axis:'y',
		update:function() {
			numSet();
			itogPrint();
		}
	});
	BGL.click(poleAdd);

	if(o.spisok.length)
		for(var n = 0; n < o.spisok.length; n++)
			poleAdd(o.spisok[n]);
	else
		poleAdd();

	function poleAdd(sp) {//добавление нового поля
		sp = $.extend({
			id:0,
			name:'',
			count:1,
			measure_id:1,
			cena:'',
			summa:'',
			readonly:0
		}, sp);

		var html = '<dd>' +
			'<table class="_spisokTab mt1">' +
				'<tr><td class="w15 r grey topi curM">' +
					'<td class="top">' +
						'<input type="hidden" class="sp-id" value="' + sp.id + '" />' +
						'<textarea class="min w300">' + sp.name + '</textarea>' +
					'<td class="w70 center top"><input type="text" class="sp-count w35 center"' + (sp.readonly ? ' readonly="readonly"' : '') + ' value="' + sp.count + '" />' +
					'<td class="w50 center topi">' +
						MEASURE_ASS[sp.measure_id] +
						'<input type="hidden" class="sp-measure_id" value="' + sp.measure_id + '" />' +
					'<td class="w70 top"><input type="text" class="sp-cena w50 r"' + (sp.readonly ? ' readonly="readonly"' : '') + ' value="' + sp.cena + '" />' +
					'<td class="w70 topi prel">' +
		(!sp.readonly ? '<div class="icon icon-del out' + _tooltip('Удалить позицию', -95, 'r') + '</div>' : '') +
						'<div class="sp-summa r b">' + sp.summa + '</div>' +
			'</table>';
		SORT.append(html);
		numSet();

		var last = SORT.find('._spisokTab:last'),
			spCount = last.find('.sp-count'),
			spCena = last.find('.sp-cena'),
			spSumma = last.find('.sp-summa');

		//установка фокуса на первое поле
		last.find('textarea.min')
			.autosize()
			.focus()
			.keyup(itogPrint);

		//удаление поля
		last.find('.icon-del').click(function() {
			last.remove();
			numSet();
			itogPrint();
		});

		//подсчёт суммы поля
		last.find('.sp-count,.sp-cena').keyup(function() {
			var summa = _num(spCount.val()) * _cena(spCena.val());
			spSumma.html(Math.round(summa * 100) / 100);
			itogPrint();
		});

		itogPrint();
	}
	function numSet() {//расстановка порядковых номеров
		var tab = SORT.find('._spisokTab'),
			len = tab.length;

		if(!len)
			return;

		for(var n = 0; n < len; n++)
			tab.eq(n).find('.curM').html(n + 1);
	}
	function itogPrint() {
		var tab = SORT.find('._spisokTab'),
			len = tab.length,
			posCount = 0,
			summa = 0, //общая сумма;
			correct = true,
			val = []; //переменная для отправки

		if(!len) {
			ITOG.html('');
			t.val('');
			return;
		}

		for(var n = 0; n < len; n++) {
			var sp = tab.eq(n),
				spArea = sp.find('textarea'),
				spCount = sp.find('.sp-count'),
				spCena = sp.find('.sp-cena'),

				area = $.trim(spArea.val()),                //проверка правильности заполнения Наименования
				count = _num(spCount.val()),                //проверка правильности заполнения Количества
				cena_test = REGEXP_CENA.test(spCena.val()), //проверка правильности заполнения Суммы
				cena = _cena(spCena.val());

			spArea[(area ? 'remove' : 'add') + 'Class']('err');
			spCount[(count ? 'remove' : 'add') + 'Class']('err');
			spCena[(cena_test ? 'remove' : 'add') + 'Class']('err');

			if(!area || !count || !cena_test) {
				correct = false;
				continue;
			}

			summa += _cena(sp.find('.sp-summa').html());
			posCount++;

			val.push(
				area + '&&&' + 
				count + '&&&' + 
				_num(sp.find('.sp-measure_id').val()) + '&&&' +
				cena + '&&&' + 
				_num(sp.find('.sp-id').val()) + '&&&' +
				(spCount.attr('readonly') ? 1 : 0)
			);
		}

		NO_CORRECT[(correct ? 'add' : 'remove') + 'Class']('dn');
		ITOG.html('Всего наименований ' + posCount + ', на сумму ' + Math.round(summa * 100) / 100 + ' руб.');
		t.val(val.join('###'));
	}

	return t;
};

$(document)
	.on('click', '._accrual-edit', function() {
		var t = $(this),
			p = _parent(t);

		_accrualAdd({
			id:t.attr('val'),
			sum:p.find('.sum').html(),
			about:p.find('.about').html()
		});
	})
	.on('click', '._accrual-del', function() {
		var t = $(this);
		_dialogDel({
			id:t.attr('val'),
			head:'начисления',
			op:'accrual_del',
			func:function() {
//				_parent(t).remove();
				location.reload();
			}
		});
	})

	.on('click', '.income-del', function() {
		var t = $(this);
		_dialogDel({
			id:t.attr('val'),
			head:'платежа',
			op:'income_del',
			func:function() {
				_parent(t).remove();
			}
		});
	})
	.on('click', '._income-unit .refund', function() {
		var t = $(this),
			id = t.attr('val'),
			p = _parent(t),
			sum = p.attr('val'),
			dtime = p.find('.refund-dtime').val(),
			html =
				'<div class="_info">' +
					'Платёж будет отмечен как <b>возврат</b>. Также будет сделана запись в разделе "Возвраты".' +
				'</div>' +
				'<div id="income-refund-tab">' +
					'<p>Возврат платежа на сумму <b>' + sum + '</b> руб.' +
					'<p>Дата платежа: <u>' + dtime + '</u>.' +
					'<p><b class="red">Подтвердите данное действие.</b>' +
				'</div>',
			dialog = _dialog({
				head:'Возврат платежа',
				content:html,
				butSubmit:'Подтвердить',
				submit:submit
			});
		function submit() {
			var send = {
				op:'income_refund',
				id:id,
				dtime:dtime
			};
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					p.addClass('ref');
					p.find('.refund').remove();
					_msg();
				} else
					dialog.abort();
			}, 'json');
		}
	})
	.on('click', '._income-unit .vk.small', function() {
		var t = $(this),
			p = _parent(t),
			o = t.attr('val').split('#'),
			html =
				'<div class="_info">' +
					'После подтверждения платёж будет считаться поступившим на расчётный счёт.' +
				'</div>' +
				'<table class="_dialog-tab">' +
					'<tr><td class="label">Расчётный счёт:<td><u>' + INVOICE_ASS[o[1]] + '</u>' +
					'<tr><td class="label">Сумма:<td><b>' + o[2] + '</b> руб.' +
					'<tr><td class="label">Дата платежа:<td>' + o[3] +
				'</table>',
			dialog = _dialog({
				width:320,
				head:'Подтверждение поступления на счёт',
				content:html,
				butSubmit:'Подтвердить',
				submit:submit
			});
		function submit() {
			var send = {
				op:'income_confirm',
				id:o[0]
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					p.find('button').remove();
					p.find('.confirm')
						.after('<div class="confirmed">Подтверждён ' + res.dtime + '</div>')
						.remove();
				} else
					dialog.abort();
			}, 'json');
		}
	})

	.on('click', '._refund-del', function() {
		var t = $(this);
		_dialogDel({
			id:t.attr('val'),
			head:'возврата',
			op:'refund_del',
			func:function() {
				_parent(t).remove();
			}
		});
	})

	.on('click', '#money-expense .img_edit', function() {
		var dialog = _dialog({
				width:480,
				head:'Редактирование расхода',
				load:1,
				butSubmit:'Сохранить'
			}),
			send = {
				op:'expense_load',
				id:$(this).attr('val')
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				res.arr.id = send.id;
				_expenseTab(dialog, res.arr);
			} else
				dialog.loadError();
		}, 'json');
	})
	.on('click', '#money-expense .img_del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'расхода',
			op:'expense_del',
			func:_expenseSpisok
		});
	})

	.on('click', '#money-invoice .img_setup', function() {
		var t = $(this),
			p = _parent(t),
			id = _num(t.attr('val')),
			name = p.find('.name b').html(),
			balans = p.find('.balans b').html(),
			html = '<div id="invoice-setup-tab">' +
						'<table>' +
							'<tr><td class="label">Расчётный счёт:<td><b>' + name + '</b>' +
							'<tr><td class="label">Текущий баланс:<td>' + (balans ? '<b>' + balans + '</b> руб.' : 'не установлен') +
						'</table>' +
						'<div class="u" val="1">' +
							'<h1>Перевод между счетами</h1>' +
							'<h2>Перевести денежные средства на другой расчётный счёт.</h2>' +
						'</div>' +
				(VIEWER_ADMIN || RULE_SETUP_INVOICE ?
					(balans ?
						'<div class="u" val="2">' +
							'<h1>Внести деньги на счёт</h1>' +
							'<h2>Внести произвольную сумму на расчётный счёт. Данная сумма не будет являться платежом.</h2>' +
						'</div>' +
						'<div class="u" val="7">' +
							'<h1>Вывести деньги со счёта</h1>' +
							'<h2>Произвести вывод денежных средств из организации.</h2>' +
						'</div>'
					: '') +
						'<div class="u" val="3">' +
							'<h1>Установить текущую сумму</h1>' +
							'<h2>Установить сумму, которая соответствует фактической сумме на расчётном счёте или наличию денег в кассе.</h2>' +
						'</div>' +
					(balans ?
						'<div class="u" val="4">' +
							'<h1>Сброс суммы</h1>' +
							'<h2>Текущая сумма на счёте будет сброшена, но все операции по счёту, история действий будут доступны. Нужно если вы не хотите контролировать данный расчётный счёт.</h2>' +
						'</div>'
					: '') +
						'<div class="u" val="5">' +
							'<h1>Редактировать счёт</h1>' +
							'<h2>Изменить название счёта и его описание. Настроить видимость для сотрудников и права.</h2>' +
						'</div>' +
						'<div class="u" val="6">' +
							'<h1>Закрыть счёт</h1>' +
							'<h2>При закрытии счёта остаток будет переведён на другой расчётный счёт.</h2>' +
						'</div>'
				: '') +
					'</div>',
			dialog = _dialog({
				top:20,
				width:460,
				head:'Выполнение операции над счётом',
				content:html,
				butSubmit:'',
				butCancel:'Закрыть'
			});

		$('#invoice-setup-tab .u').click(function() {
			dialog.close();
			switch(_num($(this).attr('val'))) {
				case 1: _invoiceTransfer({from:id}); break;
				case 2: _invoiceIn(id, balans); break;
				case 7: _invoiceOut(id, balans); break;
				case 3: _invoiceBalansSet(id); break;
				case 4: _invoiceReset(id); break;
				case 5:
					_invoiceEdit({
						id:id,
						name:name,
						about:p.find('.about').html(),
						visible:p.find('.visible').val(),
						income_confirm:p.find('.income_confirm').val(),
						transfer_confirm:p.find('.transfer_confirm').val(),
						income_insert:p.find('.income_insert').val(),
						expense_insert:p.find('.expense_insert').val()
					});
					break;
				case 6: _invoiceClose(id, balans == '0' ? 0 : balans); break;
			}
		});
	})
	.on('click', '#invoice-spisok ._check', function() {
		var ch = $('#invoice-spisok ._check input'),
			inp = $(this).find('input'),
			sel = inp.attr('id');

		for(var n = 0; n < ch.length; n++) {
			var sp = ch.eq(n),
				id = sp.attr('id');
			if(sel == id)
				continue;
			$('#' + id)._check(0);
		}

		if(!_num(inp.val())) {
			$('#' + sel)._check(1);
			return;
		}

		var send = {
				op:'invoice_default',
				invoice_id:sel.split('def')[1]
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				_msg();
			else
				$('#' + sel)._check(0);
		}, 'json');

	})
	.on('click', '#transfer-spisok .img_edit', function() {
		var t = $(this),
			p = _parent(t),
			v = p.attr('val').split('###');
		_invoiceTransfer({
			id:v[0],
			from:v[1],
			to:v[2],
			sum:v[3],
			about:v[4]
		});
	})
	.on('click', '#transfer-spisok .img_del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'перевода между счетами',
			op:'invoice_transfer_del',
			func:function(res) {
				$('#invoice-spisok').html(res.i);
				$('#transfer-spisok').html(res.t);
			}
		});
	})
	.on('click', '#transfer-spisok ._next', function() {
		var next = $(this),
			send = {
				op:'invoice_transfer_spisok',
				page:next.attr('val')
			};
		if(next.hasClass('busy'))
			return;
		next.addClass('busy');
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				next.after(res.html).remove();
			else
				next.removeClass('busy');
		}, 'json');
	})
	.on('click', '#transfer-spisok .vk.small', function() {
		var t = $(this),
			p = _parent(t),
			o = t.attr('val').split('#'),
			html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">Со счёта:<td><u>' + INVOICE_ASS[o[1]] + '</u>' +
					'<tr><td class="label">На счёт:<td><u>' + INVOICE_ASS[o[2]] + '</u>' +
					'<tr><td class="label">Сумма:<td><b>' + o[3] + '</b> руб.' +
					'<tr><td class="label">Дата перевода:<td>' + o[4] +
				'</table>',
			dialog = _dialog({
				width:320,
				head:'Подтверждение перевода',
				content:html,
				butSubmit:'Подтвердить',
				submit:submit
			});
		function submit() {
			var send = {
				op:'invoice_transfer_confirm',
				id:o[0]
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#transfer-spisok').html(res.t);
					dialog.close();
					_msg();
				} else
					dialog.abort();
			}, 'json');
		}
	})
	.on('click', '#inout-spisok .img_del', function() {
		var t = $(this),
			p = _parent(t);
		_dialogDel({
			id:t.attr('val'),
			head:'записи',
			op:'invoice_' + p.attr('class') + '_del',
			func:function() {
				p.remove();
			}
		});
	})

	.on('click', '#balans-show .podrobno', function() {
		var t = $(this),
			v = t.attr('val');

		BALANS.op = 'balans_spisok';
		_balansSpisok(v, 'podrobno_day');
	})
	.on('click', '#balans-show .day-clear', function() {//очистка фильтра Выбранный день
		BALANS.op = 'balans_spisok';
		_balansSpisok('', 'podrobno_day');
	})

	.on('click', '.go-report-salary', function() {//переход на страницу зп сотрудника и выделение записи, с которой был сделан переход
		var v = $(this).attr('val').split(':');
		location.href = URL + '&p=65&id=' + v[0] + '&year=' + v[1] + '&mon=' + v[2] + '&acc_id=' + v[3];
	})
	.on('mouseenter', '.salary .show', function() {
		$(this).removeClass('show');
	})
	.on('click', '.worker-acc-del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'начисления з/п',
			op:'salary_accrual_del',
			func:_salarySpisok
		});
	})
	.on('click', '.worker-deduct-del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'вычета из з/п',
			op:'salary_deduct_del',
			func:_salarySpisok
		});
	})
	.on('click', '#salary-worker #spisok-zp .img_edit', function() {
		var dialog = _dialog({
				width:380,
				head:'Редактирование выданной з/п сотрудника',
				load:1,
				butSubmit:''
			}),
			send = {
				op:'expense_load',
				id:$(this).attr('val')
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				dialog.close();
				_salaryWorkerZpAdd(res.arr);
			} else
				dialog.loadError();
		}, 'json');
	})
	.on('click', '#salary-worker #spisok-zp .img_del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'зарплаты',
			op:'expense_del',
			func:_salarySpisok
		});
	})
	.on('click', '#salary-worker h4', function() {//показ-скрытие списка с начислениями
		var t = $(this),
			p = t.parent(),
			sp = p.find('#sp'),
			v = p.hasClass('acc-show');
			p[(v ? 'remove' : 'add') + 'Class']('acc-show');
		if(v)
			sp.show().slideUp();
		else
			sp.hide().slideDown();
	})
	.on('click', '#salary-worker #spisok-list .img_xls', function() {//открытие листа выдачи з/п
		var v = $(this).attr('val');
		location.href = URL + '&p=75&d=salary_list&id=' + v;
	})
	.on('click', '.salary-list-del', function() {
		var t = $(this);
		_dialogDel({
			id:t.attr('val'),
			head:LIST_VYDACI + ':удаление',
			op:'salary_list_del',
			func:_salarySpisok
		});
	})
	.on('click', '#noacc-recalc', _salaryWorkerNoAccRecalc)

	.ready(function() {
		if($('#schet-pay-menu').length) {
			$('#schet-pay-menu')._menuDop({
				spisok:[
					{uid:1,title:'Реестр счетов на оплату'},
					{uid:2,title:'История действий'},
					{uid:3,title:'Сводка по месяцам'}
				]
			});
			$('#find')._search({
				width:380,
				focus:1,
				txt:'поиск по номеру, плательщику, содержанию и сумме',
				enter:1,
				v:SCHET_PAY.find,
				func:schetPaySpisok
			});
			$('#group_id')._dropdown({
				title0:'Все счета',
				spisok:[
					//		{uid:1,title:'Все счета'},
					{uid:2,title:'Ознакомительные'},
					{uid:3,title:'Не переданы'},
					{uid:4,title:'Не оплачены'},
					{uid:5,title:'Переданы, не оплачены'},
					{uid:6,title:'Оплачены'}
				],
				func:schetPaySpisok
			});
			$('.schet-stat-tr').click(function() {
				var t = $(this),
					mon = t.attr('val');
				$('#find')._search('clear');    SCHET_PAY.find = '';
				$('#group_id')._dropdown(0);    SCHET_PAY.group_id = 0;
				schetPaySpisok(mon, 'mon');
				$('#schet-pay-menu')._menuDop(1);
			});
		}

		if($('#money-invoice').length) {
			var spisok = [{uid:1, title:'Расчётные счета'}];
			if(RULE_INVOICE_TRANSFER)
				spisok.push({uid:2, title:'Переводы между счетами'});
			if(RULE_INVOICE_TRANSFER == 2)
				spisok.push({uid:3, title:'Внесения и выводы'});
			$('#invoice_menu')._menuDop({
				spisok:spisok
			});
		}
		if($('#salary-worker').length) {
			var sp = [
					{uid:1, title:'Установить баланс'},
					{uid:2, title:'Изменить ставку'},
					{uid:3, title:'Начислить'},
					{uid:4, title:'Внести вычет'},
					{uid:5, title:'Сформировать ' + LIST_VYDACI},
					{uid:6, title:'Выдать з/п'},
					{uid:7, title:'Пересчитать начисления по заявкам'}
				];
			if(!VIEWER_ADMIN)
				sp.pop();
			$('#action')._dropdown({
				head:'Действие',
				nosel:1,
				spisok:sp,
				func:function(v) {
					switch(v) {
						case 1: _salaryWorkerBalansSet(); break;
						case 2: _salaryWorkerRateSet(); break;
						case 3: _salaryWorkerAccAdd(); break;
						case 4: _salaryWorkerDeductAdd(); break;
						case 5: _salaryWorkerListCreate(); break;
						case 6: _salaryWorkerZpAdd(); break;
						case 7: _salaryWorkerNoAccRecalc(); break;
					}
				}
			});
			$('#year')._yearLeaf({func:_salarySpisok});
			$('#salmon')._radio({func:_salarySpisok});
			_salaryNoAccRecalcHint();
		}
	});
