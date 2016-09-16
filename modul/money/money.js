var _accrualAdd = function(o) {
		o = $.extend({
			id:0,
			sum:'',
			about:''
		}, o);

		var html =
			'<table id="_accrual-add">' +
                '<tr><td class="label">Заявка:<td><b>' + ZI.name + '</b>' +
				'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money" value="' + o.sum.split(' ').join('') + '" /> руб.' +
				'<tr><td class="label">Примечание:<td><input type="text" id="about" placeholder="не обязательно" value="' + o.about + '" />' +
			'</table>';

		var dialog = _dialog({
			width:480,
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
				zayav_id:ZI.id,
				sum:_cena($('#sum').val()),
				about:$('#about').val()
			};
			if(!send.sum) {
				dialog.err('Некорректно указана сумма');
				$('#sum').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				dialog.abort();
				if(res.success) {
					dialog.close();
					_msg();
					location.reload();
				}
			}, 'json');
		}
	},

	_incomeAdd = function() {
		var zayav = window.ZI,
			place = zayav && ZI.pole[12],
			about = zayav ? 'Примечание' : 'Описание',
			about_placeholder = zayav ? ' placeholder="не обязательно"' : '',
			html =
			'<div id="_income-add">' +
				'<table class="tab">' +
		   (zayav ? '<tr><td class="label">Клиент:<td>' + ZI.client_link : '') +
		   (zayav ? '<tr><td class="label">Заявка:<td><b>' + ZI.name + '</b>' : '') +
					'<tr><td class="label">Счёт:<td><input type="hidden" id="invoice_id-add" value="' + _invoiceIncomeInsert(1) + '" />' +
					'<tr class="tr_confirm dn"><td class="label">Подтверждение:<td><input type="hidden" id="confirm" />' +
					'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money" /> руб.' +
		   (zayav ? '<tr><td class="label">Предоплата:<td>' : '') +
						'<input type="hidden" id="prepay" />' +
					'<tr><td class="label">' + about + ':<td><input type="text" id="about"' + about_placeholder + ' />' +
				'</table>' +

		   (zayav ?
				(place ?
					'<div id="place-div">' +
						'<table class="tab">' +
							'<tr><td class="label r">Текущее местонахождение:<td><b>' + _toAss(ZAYAV_TOVAR_PLACE_SPISOK)[ZI.place_id] + '</b>' +
							'<tr><td class="label topi">Новое местонахождение:<td><input type="hidden" id="tovar-place" />' +
						'</table>' +
					'</div>'
				: '') +

				(ZAYAV_REMIND.active ?
					'<div id="remind-div">' +
						'<h1>Есть ' + ZAYAV_REMIND.active + ' активн' + _end(ZAYAV_REMIND.active, ['ое', 'ых']) + ' напоминани' + _end(ZAYAV_REMIND.active, ['е', 'я', 'й']) + '.</h1>' +
						'<h2>Поставье галочку напоминанию, если его нужно отметить выполненным.</h2>' +
						incomeRemind() +
					'</div>'
		        : '')
		   : '') +

			'</div>';
		var dialog = _dialog({
				top:zayav ? 30 : 60,
				width:490,
				head:'Внесение платежа',
				padding:0,
				content:html,
				submit:submit
			});

		$('#invoice_id-add')._select({
			width:218,
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
						$('#place-div').slideDown(300);
					else
						$('#remind-div').slideDown(300);
				}
			});

			if(place)
				$('#tovar-place').zayavTovarPlace({
					func:function() {
						$('#remind-div').slideDown(300);
					}
				});
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
					'<div class="remind-iu">' +
						'<h3>' + i.txt + '</h3>' +
						'<h4>' + i.about + '</h4>' +
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
				invoice_id:_num($('#invoice_id-add').val()),
				confirm:_bool($('#confirm').val()),
				sum:_cena($('#sum').val()),
				prepay:_num($('#prepay').val()),
				about:$('#about').val(),
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
				dialog.abort();
				if(res.success) {
					dialog.close();
					_msg('Новый платёж внесён.');
					if(zayav)
						location.reload();
					else
						incomeSpisok();
				}
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

	_refundAdd = function() {//внесение возврата
		var html =
			'<div class="_info">' +
				'После внесения возврата не забудьте удалить начисление, чтобы выровнять баланс клиента.' +
			'</div>' +
			'<table id="_refund-add-tab">' +
(ZI.client_id ? '<tr><td class="label">Клиент:<td>' + ZI.client_link : '') +
                '<tr><td class="label">Заявка:<td><b>' + ZI.name + '</b>' +
				'<tr><td class="label">Со счёта:<td><input type="hidden" id="invoice_id" value="' + _invoiceIncomeInsert(1) + '" />' +
				'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money" /> руб.' +
				'<tr><td class="label">Причина:<td><input type="text" id="about" />' +
			'</table>',
			dialog = _dialog({
				width:400,
				head:'Возврат',
				content:html,
				submit:submit
			});

		$('#sum').focus();
		$('#sum,#prim').keyEnter(submit);
		$('#invoice_id')._select({
			width:200,
			title0:'Не выбран',
			spisok:_invoiceIncomeInsert(),
			func:function(v) {
				$('#sum').focus();
			}
		});

		function submit() {
			var send = {
				op:'refund_add',
				zayav_id:ZI.id,
				invoice_id:_num($('#invoice_id').val()),
				sum:_cena($('#sum').val()),
				about:$.trim($('#about').val())
			};
			if(!send.invoice_id)
				dialog.err('Не указан счёт');
			else if(!send.sum) {
				dialog.err('Некорректно указана сумма');
				$('#sum').focus();
			} else if(!send.about) {
				dialog.err('Укажите причину возврата');
				$('#about').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					dialog.abort();
					if(res.success) {
						dialog.close();
						_msg('Возврат успешно произведён');
						location.reload();
					}
				}, 'json');
			}
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
			category_id:0,
			category_sub_id:0,
			invoice_id:_invoiceExpenseInsert(1),
			attach_id:0,
			sum:'',
			about:''
		}, o);

		ATTACH[o.attach_id] = o.attach;

		var html =
			'<table id="expense-add-tab">' +
				'<tr><td class="label topi">Категория:' +
					'<td><input type="hidden" id="category_id-add" value="' + o.category_id + '" />' +
						'<input type="hidden" id="category_sub_id-add" value="' + o.category_sub_id + '" />' +
				'<tr><td class="label">Описание:<td><input type="text" id="about" value="' + o.about + '" />' +
				'<tr><td class="label">Файл:<td><input type="hidden" id="attach_id-add" value="' + o.attach_id + '" />' +
				'<tr><td class="label">Со счёта:<td><input type="hidden" id="invoice_id-add" value="' + o.invoice_id + '" />' +
				'<tr><td class="label">Сумма:' +
					'<td><input type="text" id="sum" class="money" value="' + o.sum + '"' + (o.id ? ' disabled' : '') + ' /> руб.' +
			'</table>';
		dialog.content.html(html);
		dialog.submit(submit);

		$('#category_id-add')._select({
			width:258,
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
			width:200,
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
					dialog.abort();
			}, 'json');
		}
	},
	_expenseLoad = function() {
		$('.add').click(function() {
			var dialog = _dialog({
				width:380,
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
		if(!VIEWER_ADMIN)
			return;
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
				$('#spisok').html(res.html);
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
			head:(o.id ? 'Редактирование' : 'Внесение нового') + ' расчётного счёта',
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
					'<tr><td class="label">Счёт:<td><b>' + INVOICE_ASS[invoice_id] + '</b>' +
					'<tr><td class="label">Баланс:<td><b>' + balans + '</b> руб.' +
					'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money" /> руб. ' +
					'<tr><td class="label">Комментарий:<td><input type="text" id="about" />' +
				'</table>',
			dialog = _dialog({
				head:'Внесение денег на счёт',
				content:html,
				submit:submit
			});
		$('#sum').focus();
		function submit() {
			var send = {
				op:'invoice_in_add',
				invoice_id:invoice_id,
				sum:_cena($('#sum').val()),
				about:$('#about').val()
			};
			if(!send.sum) {
				dialog.err('Некорректно введена сумма');
				$('#sum').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#invoice-spisok').html(res.i);
					$('#inout-spisok').html(res.io);
					dialog.close();
					_msg();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	_invoiceOut = function(invoice_id, balans) {
		var t = $(this),
			html = '<table class="_dialog-tab">' +
					'<tr><td class="label">Счёт:<td><b>' + INVOICE_ASS[invoice_id] + '</b>' +
					'<tr><td class="label">Баланс:<td><b>' + balans + '</b> руб.' +
					'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money" /> руб. ' +
					'<tr><td class="label">Получатель:<td><input type="hidden" id="worker_id" />' +
					'<tr><td class="label">Комментарий:<td><input type="text" id="about" />' +
				'</table>',
			dialog = _dialog({
				head:'Вывод денег со счёта',
				content:html,
				butSubmit:'Вывести',
				submit:submit
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
				invoice_id:invoice_id,
				sum:_cena($('#sum').val()),
				worker_id:_num($('#worker_id').val()),
				about:$('#about').val()
			};
			if(!send.sum) {
				dialog.err('Некорректно введена сумма');
				$('#sum').focus();
				return;
			}
			if(!send.worker_id) {
				dialog.err('Не указан сотрудник-получатель');
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#invoice-spisok').html(res.i);
					$('#inout-spisok').html(res.io);
					dialog.close();
					_msg();
				} else
					dialog.abort();
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
				'которые не внесены в листы выдачи зп, и помещены в данный список.' +
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
				$('._balans-show').html(res.balans);
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
					$('._balans-show').html(res.balans);
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
						'Для формирования листа выдачи з/п сначала необходимо выбрать начисления.' +
					'</div>' +
				'</div>',
			dialog = _dialog({
				padding:30,
				width:305,
				head:'Создание листа выдачи з/п',
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
						'<h1>Создание листа выдачи з/п</h1>' +
						'После формирования листа выдачи з/п все ' +
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
						'Все ранее выданные з/п в этом месяце, которые не привязаны к листам выдачи, попадут в <b>текущий лист</b> и будут отмечены как <b>авансы</b>.' +
					'</div>' +
					'<a href="' + URL + '&p=setup&d=salary_list" id="list-set">Настроить лист выдачи</a>' +
				'</div>',
			dialog = _dialog({
				width:330,
				head:'Создание листа выдачи з/п',
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
			invoice_id:_invoiceExpenseInsert(1),
			sum:'',
			about:'',
			salary_avans:0,
			salary_list_id:0
		}, o);

		var html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">Сотрудник:<td><u>' + WORKER_ASS[SALARY.worker_id] + '</u>' +
					'<tr><td class="label">Месяц:<td><b>' + MONTH_DEF[SALARY.mon] + ' ' + SALARY.year + '</b>' +
					'<tr><td class="label">Со счёта:<td><input type="hidden" id="invoice_id" value="' + o.invoice_id + '" />' +
					'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money" value="' + o.sum + '"' + (o.id ? ' disabled' : '') + ' /> руб.' +
					'<tr><td class="label">Аванс:<td><input type="hidden" id="salary_avans" value="' + o.salary_avans + '" />' +
					'<tr><td class="label">Комментарий:<td><input type="text" id="about" placeholder="не обязательно" value="' + o.about + '" />' +
					'<tr' + (SALARY.list.length ? '' : ' class="dn"') + '>' +
						'<td class="label">Лист выдачи:' +
						'<td><input type="hidden" id="salary_list_id" value="' + o.salary_list_id + '" />' +
				'</table>',
			dialog = _dialog({
				width:380,
				padding:20,
				head:(o.id ? 'Редактирование' : 'Выдача') + ' зарплаты',
				content:html,
				submit:submit,
				butSubmit:o.id ? 'Сохранить' : 'Выдать'
			});

		$('#sum').focus();
		$('#invoice_id')._select({
			width:218,
			title0:'счёт не выбран',
			spisok:_invoiceExpenseInsert(),
			disabled:o.id,
			func:function() {
				$('#sum').focus();
			}
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
				category_id:1,
				worker_id:SALARY.worker_id,
				invoice_id:_num($('#invoice_id').val()),
				sum:_cena($('#sum').val()),
				about:$('#about').val(),
				salary_avans:_bool($('#salary_avans').val()),
				salary_list_id:_num($('#salary_list_id').val()),
				mon:SALARY.mon,
				year:SALARY.year
			};
			if(!send.invoice_id) {
				dialog.err('Укажите с какого счёта производится выдача');
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

	_schetInfo = function(o) {
		o = $.extend({
			id:0,
			edit:0
		}, o);
		var load = !o.html,
			dialog = _dialog({
				top:20,
				width:580,
				head:'Просмотр счёта',
				load:load,
				butSubmit:'',
				butCancel:'Закрыть'
			});

		if(load) {
			var send = {
				op:'schet_load',
				id:o.id
			};
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					if(o.edit) {
						dialog.close();
						res.edit = 1;
						_schetEdit(res);
					} else
						schetPrint(res);
				} else
					dialog.loadError();
			}, 'json');
		} else
			schetPrint(o);

		function schetPrint(res) {
			var html =
				'<div id="_schet-info">' +
					'<table class="tab">' +
						'<tr><td class="label r top">Плательщик:<td>' + res.client +
		(res.zayav_id ? '<tr><td class="label r">Заявка:<td>' + res.zayav_link : '') +
					'</table>' +
					'<h1>СЧЁТ № ' + res.nomer + res.ot + '</h1>' +
					res.html +
					'<h2>' + res.itog + '</h2>' +
					'<table id="dop">' +
						'<tr><td id="dop-td">' +
								(res.nakl ? '<div class="doc">&bull; Накладная</div>' : '') +
								(res.act ? '<div class="doc">&bull; Акт выполненных работ</div>' : '') +
								'<div id="income">' + res.income + '</div>' +
								(res.del ? '<div id="deleted">Счёт был удалён</div>' : '') +
							'<th>' +
	   (!res.del && !res.paid ? '<a id="schet-edit">Редактировать счёт</a>' : '') +
								'<a id="schet-print">Распечатать<div class="img_xls"></div></a>' +
	   (!res.del && !res.paid ?
					(res.pass ? '<a id="schet-pass-cancel">Отменить передачу</a>'
							  : '<a id="schet-pass">Передать клиенту</a>'
					) +
				                '<a id="schet-pay"><b>Оплатить</b></a>' +
				                '<a id="schet-del">Удалить</a>'
		: '') +
					(res.hist ? '<a id="schet-history">История действий</a>' : '') +
					'</table>' +
		(res.hist ? '<div id="hist">' + res.hist_spisok + '</div>' : '') +
				'</div>';
			dialog.content.html(html);
			$('#schet-edit').click(function() {
				dialog.close();
				_schetEdit(res);
			});
			$('#schet-print').click(function() {
				_schetPrintXsl(res.schet_id);
			});
			$('#schet-pass').click(function() {
				_schetPass(res.schet_id, res.nomer);
			});
			$('#schet-pass-cancel').click(function() {
				_schetPassCancel(res.schet_id, res.nomer);
			});
			$('#schet-pay').click(function() {
				_schetPay(res.schet_id, res.nomer);
			});
			$('#schet-del').click(function() {
				_dialogDel({
					id:o.id,
					head:'счёта № ' + res.nomer,
					op:'schet_del',
					func:function() {
						dialog.close();
						if(window.SCHET)
							_schetSpisok();
						else
							location.reload();
					}
				});
			});
			$('#schet-history').click(function() {
				$('#hist').slideDown(300);
			});
		}
	},
	_schetEdit = function(o) {
		window['tsg'] = false;
		o = $.extend({
			edit:0,         // 0 - возвращаться на просмотр счёта при отмене, 1 - просто закрыть окно
			schet_id:0,
			client_id:0,
			zayav_id:0,
			avai:2,         // варианты выбора товара (из tovar-select)
			arr:[],         // контент счёта
			zayav_spisok:[],// список заявок конкретного клиента для прикрепления счёта к другой заявке
			client:'',
			date_create:'', // дата создания счёта
			nakl:0,         // на печать будет выводиться накладная
			act:0,          // на мечать будет выводиться акт выполненных работ
			noedit:0,
			func:function() {
				location.reload();
			}
		}, o);

		var spisok = '';
		for(var n = 0; n < o.arr.length; n++) {
			var sp = o.arr[n];
			sp.sum = sp.cost * sp.count;
			spisok += pole(sp);
		}
		var html =
				'<div id="_schet-info">' +
		(o.noedit ? '<div class="_info">' +
						'<b>Внимание!</b> Редактирование данного счёта ограничено.<br />' +
						'Вы не можете изменить сумму счёта, удалить или добавить новые позиции.' +
					'</div>'
		: '') +
					'<table class="tab">' +
						'<tr><td class="label r top">Плательщик:<td>' + o.client +
(o.zayav_spisok.length ?'<tr><td class="label r">Заявка:<td><input type="hidden" id="zayav_id" value="' + o.zayav_id + '" />' : '') +
						'<tr><td class="label r">Дата:<td><input type="hidden" id="date_create" value="' + o.date_create + '" />' +
						'<tr><td class="label r topi">Приложения:' +
							'<td><input id="nakl" type="hidden" value="' + o.nakl + '" />' +
								'<input id="act" type="hidden" value="' + o.act + '" />' +
					'</table>' +
					'<h1>' + (o.schet_id ? 'СЧЁТ № ' + o.nomer : 'НОВЫЙ СЧЁТ') + '</h1>' +
					'<table class="_spisok">' +
						'<tr><th>№' +
							'<th>Наименование товара' +
							'<th>Кол-во' +
							'<th>Цена' +
							'<th>Сумма' +
							'<th>' +
						spisok +
		  (!o.noedit ? '<tr><td colspan="6" class="_next" id="pole-add">Добавить позицию' : '') +
					'</table>' +
					'<h3></h3>' +
				'</div>',
			dialog = _dialog({
				top:20,
				width:610,
				head:o.schet_id ? 'Редактирование счёта на оплату' : 'Формирование нового счёта на оплату',
				content:html,
				butSubmit:o.schet_id ? 'Сохранить счёт' : 'Сформировать счёт',
				submit:submit,
				cancel:function() {
					if(!o.edit)
						_schetInfo(o);
				}
			}),
			tovar_num = 0;// порядковый номер input товара

		$('#_schet-info textarea').autosize();
		poleNum();
		itog();
		if(o.zayav_spisok.length)
			$('#zayav_id')._select({
				title0:'Заявка не выбрана',
				spisok:o.zayav_spisok
			});
		$('#date_create')._calendar({lost:1});
		$('#nakl')._check({name:'Накладная',light:1});
		$('#act')._check({name:'Акт выполненных работ',light:1});
		$('#pole-add').click(function() {//добавление новой позиции счёта
			$(this)
				.parent()
				.before(pole)
				.parent()
				.find('.name:last textarea').autosize().focus();
			$('#tovar' + tovar_num).tovar({
				title:'...',
				tooltip:'Выбрать из товаров',
				avai:o.avai,
				avai_open:1,
				funcSel:function(res, attr_id) {
					var p = _parent($('#' + attr_id), '.pole'),
						countInp = p.find('.count input'),
						costInp = p.find('.cost input');

					p.find('.name textarea')
					 .after('<textarea>' + res.name + '</textarea>')
					 .remove();

					p.find('.name textarea')
						.autosize()
						.next().val(res.id);
					p.find('.tovar-avai').val(res.avai_id);

					countInp.val(res.count);
					costInp
					 .val(res.sum_sell)
					 .trigger('keyup');

					itog();

					if(!p.next().hasClass('pole'))
						$('#pole-add').trigger('click');

					$('#' + attr_id).next().remove();

					if(res.avai_id) {
						countInp
							.attr('readonly', 'readonly')
							.css('font-weight', 'bold');
						costInp.select();
					} else
						countInp.select();
				}
			});
			poleNum();
		});

		if(!o.schet_id && !o.arr.length)//если новый счёт и нет контента, выводится одно пустое поле
			$('#pole-add').trigger('click');

		$(document)
			.off('click', '.pole-del')
			.on('click', '.pole-del', function() {
				_parent($(this)).remove();
				poleNum();
				itog();
			})
			.off('keyup', '.pole input,.pole textarea')
			.on('keyup', '.pole input,.pole textarea', function() {
				var t = _parent($(this)),
					count = t.find('.count input').val(),
					cost = t.find('.cost input').val(),
					sum = count * _cena(cost);
				t.find('.sum').html(sum);
				itog();
			});

		function pole(sp) {//добавление нового поля
			sp = $.extend({
				name:'',
				tovar_id:0,
				tovar_avai_id:0,
				count:1,
				cost:''
			}, sp);
			var sum = sp.count * _cena(sp.cost),
				readonly = sp.readonly ? ' readonly="readonly"' : '';
			return '<tr class="pole">' +
				'<td class="n r top">' +
				'<td class="name">' +
					'<textarea>' + sp.name + '</textarea>' +
					'<input type="hidden" id="tovar' + (++tovar_num) + '" value="' + sp.tovar_id + '" />' +
					'<input type="hidden" class="tovar-avai" value="' + sp.tovar_avai_id + '" />' +
				'<td class="count top"><input type="text"' + (sp.tovar_avai_id || readonly ? ' readonly="readonly"' : '') + ' value="' + sp.count + '" />' +
				'<td class="cost top"><input type="text"' + readonly + ' value="' + sp.cost + '" />' +
				'<td class="sum top">' + (sum ? sum : '') +
				'<td class="ed top">' +
					(!readonly ? '<div class="img_del pole-del' + _tooltip('Удалить', -29) + '</div>' : '');
		}
		function poleNum() {//порядковая нумерация позиций счёта
			var num = $('#_schet-info .n');
			for(var n = 0; n < num.length; n++)
				num.eq(n).html(n + 1);
		}
		function itog() {//подведение итога и подсветка ошибочных полей
			var pole = $('#_schet-info .pole'),
				num = 0,    //количество наименований
				sum = 0,    //общая сумма
				arr = [];   //массив для возврата
			for(var n = 0; n < pole.length; n++) {
				var eq = pole.eq(n),
					name_inp = eq.find('.name textarea'),
					count_inp = eq.find('.count input'),
					cost_inp = eq.find('.cost input'),
					name = $.trim(name_inp.val()),
					count = _num(count_inp.val()),
					cost = $.trim(cost_inp.val()),
					s = count * cost;
				if(s)
					num++;
				name_inp[(!name ? 'add' : 'remove') + 'Class']('err');
				count_inp[(!count ? 'add' : 'remove') + 'Class']('err');
				cost_inp[(!_cena(cost) && cost != '0'  ? 'add' : 'remove') + 'Class']('err');
				sum += s;
				arr.push({
					tovar_id:_num(name_inp.next().val()),
					tovar_avai_id:_num(name_inp.next().next().val()),
					name:name,
					count:count,
					cost:cost,
					readonly:cost_inp.attr('readonly') == 'readonly'
				});
			}

			if(!pole.length)
				$('#_schet-info h3').html('');

			if(!arr.length)
				return {
					error:1,
					msg:'Не добавлено ни одной позиции'
				};

			for(n = 0; n < arr.length; n++) {
				var sp = arr[n];
				if(sp.name && !sp.count)
					return {
						error:1,
						msg:'Некорректно указано количество',
						place:$('input.err:first')
					};
				if(sp.name && !_cena(sp.cost) && sp.cost != '0')
					return {
						error:1,
						msg:'Некорректно указана сумма',
						place:$('input.err:first')
					};
				if(!sp.name && sp.count && sp.cost)
					return {
						error:1,
						msg:'Не указано наименование',
						place:$('input.err:first')
					};
			}

			$('#_schet-info h3').html('Всего наименований <b>' + num + '</b>, на сумму <b>' + sum + '</b> руб.');
			return arr;
		}
		function submit() {
			var send = {
				op:'schet_edit',
				schet_id:o.schet_id,
				client_id:o.client_id,
				zayav_id:o.zayav_spisok.length ? $('#zayav_id').val() : o.zayav_id,
				spisok:itog(),
				date_create:$('#date_create').val(),
				nakl:_bool($('#nakl').val()),
				act:_bool($('#act').val())
			};
			if(send.spisok.error) {
				dialog.err(send.spisok.msg);
				if(send.spisok.place)
					send.spisok.place.focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					o.func(res.schet_id);
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	_schetSpisok = function(v, id) {
		SCHET.page = 1;
		if(id)
			SCHET[id] = v;
		$.post(AJAX_MAIN, SCHET, function(res) {
			if(res.success) {
				$('#spisok').html(res.spisok);
				_schetAction();
			}
		}, 'json');
	},
	_schetAction = function() {
		var action = $('.schet-action');
		if(action.length) {
			for(var n = 0; n < action.length; n++) {
				var eq = action.eq(n),
					spisok = [
						{uid:1,title:'Посмотреть содержание'},
						{uid:2,title:'Редактировать'},
						{uid:3,title:'Распечатать<div class="img_xls"></div>'},
						{uid:4,title:'Передать клиенту'},
						{uid:5,title:'Отменить передачу'},
						{uid:6,title:'<b>Оплатить</b>'}
					],
					unit = eq.parent().parent(),
					pass = unit.hasClass('pass');
				spisok.splice(pass ? 3 : 4, 1);
				$('#' + eq.attr('id'))._dropdown({
					head:'действие',
					nosel:1,
					spisok:spisok,
					func:function(v, id) {
						var schet_id = id.split('act')[1],
							nomer = $('#schet-unit' + schet_id + ' .pay-nomer').html();
						switch(v) {
							case 1: _schetInfo({id:schet_id}); break;
							case 2: _schetInfo({id:schet_id,edit:1}); break;
							case 3: _schetPrintXsl(schet_id); break;
							case 4: _schetPass(schet_id, nomer); break;
							case 5: _schetPassCancel(schet_id, nomer); break;
							case 6: _schetPay(schet_id, nomer); break;
						}
					}
				});
				eq.removeClass('schet-action');
			}
		}
	},
	_schetPrintXsl = function(schet_id) {
		location.href = URL + '&p=print&d=schet&schet_id=' + schet_id;
	},
	_schetPass = function(schet_id, nomer) {//передача счёта клиенту
		var html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">№ счёта:<td><b>' + nomer + '</b>' +
					'<tr><td class="label">Когда передан:<td><input type="hidden" id="pass-day" />' +
				'</table>';
		var dialog = _dialog({
				width:320,
				head:'Передача счёта клиенту',
				content:html,
				butSubmit:'Применить',
				submit:submit
			});

		$('#pass-day')._calendar({lost:1});
		function submit() {
			var send = {
				op:'schet_pass',
				schet_id:schet_id,
				day:$('#pass-day').val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg('Счёт № ' + nomer + ' передан клиенту');
				} else
					dialog.abort();
			}, 'json');
		}
	},
	_schetPassCancel = function(schet_id, nomer) {//отмена передачи счёта
		var html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">№ счёта:<td><b>' + nomer + '</b>' +
					'<tr><td class="label">Когда передан:<td><input type="hidden" id="pass-day" />' +
				'</table>';
		var dialog = _dialog({
				width:320,
				padding:50,
				head:'Отмена передачи счёта клиенту',
				content:'<center>Подтвердите отмену передачи<br />счёта <b>' + nomer + '</b> клиенту.</center>',
				butSubmit:'Подтвердить',
				submit:submit
			});

		function submit() {
			var send = {
				op:'schet_pass_cancel',
				schet_id:schet_id
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	_schetPay = function(schet_id, nomer) {
		var html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">№ счёта:<td><b>' + nomer + '</b>' +
					'<tr><td class="label">Сумма:<td><input type="text" class="money" id="sum" /> руб.' +
					'<tr><td class="label">День оплаты:<td><input type="hidden" id="pay-day" />' +
					'<tr><td class="label">Расчётный счёт:<td><input type="hidden" id="invoice_id-pay" value="4" />' +
				'</table>';
		var dialog = _dialog({
				head:'Оплата счёта',
				content:html,
				butSubmit:'Оплатить',
				submit:submit
			});

		$('#pay-day')._calendar({lost:1});
		$('#invoice_id-pay')._select({
			width:200,
			spisok:INVOICE_SPISOK,
			func:function() {
				$('#sum').focus();
			}
		});
		$('#sum').focus();
		function submit() {
			var send = {
				op:'schet_pay',
				schet_id:schet_id,
				invoice_id:_num($('#invoice_id-pay').val()),
				sum:_cena($('#sum').val()),
				day:$('#pay-day').val()
			};
			if(!send.sum) {
				dialog.err('Некорректно указана сумма');
				$('#sum').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if (res.success) {
						dialog.close();
						_msg('Счёт ' + nomer + ' оплачен');
					} else
						dialog.abort();
				}, 'json');
			}
		}
	};

$(document)
	.on('click', '._accrual-add', _accrualAdd)
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
				_parent(t).remove();
			}
		});
	})

	.on('click', '._income-add', _incomeAdd)
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

	.on('click', '._refund-add', _refundAdd)
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

	.on('click', '#money-expense ._next', function() {
		var next = $(this);
		if(next.hasClass('busy'))
			return;
		next.addClass('busy');
		EXPENSE.op = 'expense_spisok';
		EXPENSE.page = next.attr('val');
		$.post(AJAX_MAIN, EXPENSE, function(res) {
			if(res.success)
				next.after(res.html).remove();
			else
				next.removeClass('busy');
		}, 'json');
	})
	.on('click', '#money-expense .img_edit', function() {
		var dialog = _dialog({
				width:380,
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

	.on('click', '.schet-unit .info, .schet-link', function(e) {
		e.stopPropagation();
		_schetInfo({id:$(this).attr('val')});
	})

	.on('click', '#money-invoice .add', _invoiceEdit)
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
				(VIEWER_ADMIN ?
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

	.on('click', '#_balans_next', function() {
		var next = $(this);
		if(next.hasClass('busy'))
			return;
		next.addClass('busy');
		BALANS.op = 'balans_spisok';
		BALANS.page = next.attr('val');
		$.post(AJAX_MAIN, BALANS, function(res) {
			if(res.success)
				next.after(res.html).remove();
			else
				next.removeClass('busy');
		}, 'json');
	})
	.on('click', '._balans-show', function() {//вывод окна истории изменения балансов
		var dialog = _dialog({
				top:10,
				width:600,
				head:'Просмотр истории операций',
				load:1,
				butSubmit:'',
				butCancel:'Закрыть'
			}),
			v = $(this).attr('val').split(':')
			send = {
				op:'balans_show',
				category_id:v[0],
				unit_id:_num(v[1])
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				dialog.content.html(res.html);
			} else
				dialog.loadError();
		}, 'json');
	})

	.on('click', '.go-report-salary', function() {//переход на страницу зп сотрудника и выделение записи, с которой был сделан переход
		var v = $(this).attr('val').split(':');
		location.href = URL + '&p=report&d=salary&id=' + v[0] + '&year=' + v[1] + '&mon=' + v[2] + '&acc_id=' + v[3];
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
		location.href = URL + '&p=print&d=salary_list&id=' + v;
	})
	.on('click', '.salary-list-del', function() {
		var t = $(this);
		_dialogDel({
			id:t.attr('val'),
			head:'листа выдачи з/п',
			op:'salary_list_del',
			func:_salarySpisok
		});
	})
	.on('click', '#noacc-recalc', _salaryWorkerNoAccRecalc)

	.ready(function() {
		if($('#money-schet').length) {
			$('#find')._search({
				width: 137,
				focus: 1,
				txt: 'номер счёта',
				enter: 1,
				func: _schetSpisok
			});
			$('#passpaid')._radio(_schetSpisok);
			_schetAction();
			_nextCallback = _schetAction;
		}
		if($('#salary-worker').length) {
			var sp = [
					{uid:1, title:'Установить баланс'},
					{uid:2, title:'Изменить ставку'},
					{uid:3, title:'Начислить'},
					{uid:4, title:'Внести вычет'},
					{uid:5, title:'Сформировать лист выдачи з/п'},
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
