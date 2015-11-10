var incomeLoad = function() {
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
		$('#prepay')._check(incomeSpisok);
		$('#deleted')._check(function(v, id) {
			$('#deleted_only_check')[v ? 'show' : 'hide']();
			MONEY.deleted_only = 0;
			$('#deleted_only')._check(0);
			incomeSpisok(v, id);
		});
		$('#deleted_only')._check(incomeSpisok);
	},
	incomeSpisok = function(v, id) {
		MONEY.op = 'income_spisok';
		MONEY.page = 1;
		MONEY[id] = v;
		if(_busy())
			return;
		$.post(AJAX_MAIN, MONEY, function(res) {
			_busy(0);
			if(res.success) {
				$('#path').html(res.path);
				$('#spisok').html(res.html);
			}
		}, 'json');
	},

	expenseTab = function(dialog, arr) {//таблица для внесения или редактирования расхода
		arr = $.extend({
			id:0,
			category_id:0,
			invoice_id:INVOICE_SPISOK.length ? INVOICE_SPISOK[0].uid : 0,
			worker_id:0,
			sum:'',
			about:'',
			mon:(new Date).getMonth() + 1,
			year:(new Date).getFullYear()
		}, arr);
		var html =
			'<table id="expense-add-tab">' +
				'<tr><td class="label">Категория:<td><input type="hidden" id="category_id-add" value="' + arr.category_id + '" />' +
						'<a href="' + URL + '&p=setup&d=expense" class="img_edit' + _tooltip('Настройка категорий расходов', -95) + '</a>' +
				'<tr class="tr-work dn"><td class="label">Сотрудник:<td><input type="hidden" id="worker_id-add" value="' + arr.worker_id + '" />' +
				'<tr class="tr-work dn"><td class="label">Месяц:' +
					'<td><input type="hidden" id="tabmon" value="' + arr.mon + '" /> ' +
						'<input type="hidden" id="tabyear" value="' + arr.year + '" />' +
				'<tr><td class="label">Описание:<td><input type="text" id="about" value="' + arr.about + '" />' +
				'<tr><td class="label">Со счёта:<td><input type="hidden" id="invoice_id-add" value="' + arr.invoice_id + '" />' +
				'<tr><td class="label">Сумма:' +
					'<td><input type="text" id="sum" class="money" value="' + arr.sum + '"' + (arr.id ? ' disabled' : '') + ' /> руб.' +
			'</table>';
		dialog.content.html(html);
		dialog.submit(submit);

		$('#category_id-add')._select({
			width:200,
			title0:'Не указана',
			spisok:EXPENSE_SPISOK,
			func:function(id) {
				$('#worker_id')._select(0);
				$('.tr-work')[(EXPENSE_WORKER_USE[id] ? 'remove' : 'add') + 'Class']('dn');
			}
		});
		$('.tr-work')[(EXPENSE_WORKER_USE[arr.category_id] ? 'remove' : 'add') + 'Class']('dn');
		$('#worker_id-add')._select({
			width:200,
			title0:'Не выбран',
			spisok:_toSpisok(WORKER_ASS)
		});
		$('#about').focus();
		$('#invoice_id-add')._select({
			width:200,
			title0:'Не выбран',
			spisok:INVOICE_SPISOK,
			func:function() {
				$('#sum').focus();
			},
			disabled:arr.id
		});
		$('#tabmon')._select({
			width:80,
			spisok:_toSpisok(MONTH_DEF)
		});
		$('#tabyear')._select({
			width:60,
			spisok:YEAR_SPISOK
		});

		$('#sum').keyEnter(submit);

		function submit() {
			var send = {
				id:arr.id,
				op:arr.id ? 'expense_edit' : 'expense_add',
				category_id:_num($('#category_id-add').val()),
				worker_id:$('#worker_id-add').val(),
				about:$('#about').val(),
				invoice_id:_num($('#invoice_id-add').val()),
				sum:_cena($('#sum').val()),
				mon:$('#tabmon').val(),
				year:$('#tabyear').val()
			};
			if(!send.about && !send.category_id) {
				dialog.err('Выберите категорию или укажите описание');
				$('#about').focus();
			} else if(!send.invoice_id)
				dialog.err('Укажите с какого счёта производится оплата');
			else if(!send.sum) {
				dialog.err('Некорректно указана сумма');
				$('#sum').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function (res) {
					if(res.success) {
						dialog.close();
						_msg('Выполнено');
						expenseSpisok();
					} else
						dialog.abort();
				}, 'json');
			}
		}
	},
	expenseLoad = function() {
		$('.add').click(function() {
			var dialog = _dialog({
				width:380,
				head:'Внесение расхода'
			});
		expenseTab(dialog);
		});
		$('#invoice_id')._select({
			width:140,
			title0:'Все счета',
			spisok:INVOICE_SPISOK,
			func:expenseSpisok
		});
		$('#category_id')._select({
			width:140,
			title0:'Любая категория',
			spisok:EXPENSE_SPISOK,
			func:expenseSpisok
		});
		$('#worker_id')._select({
			width:140,
			title0:'Все сотрудники',
			spisok:EXPENSE_WORKER,
			func:expenseSpisok
		});
		$('#year').years({
			func:expenseSpisok,
			center:function() {
				var inp = $('#mon-list input'),
					all = 0;
				for(var n = 1; n <= 12; n++)
					if(inp.eq(n - 1).val() == 0) {
						all = 1;
						break;
					}
				for(n = 1; n <= 12; n++)
					$('#c' + n)._check(all);
				expenseSpisok(all ? '1,2,3,4,5,6,7,8,9,10,11,12' : '', 'month');
			}
		});
	},
	expenseFilter = function() {
		var arr = [],
			inp = $('#monthList input');
		for(var n = 1; n <= 12; n++)
			if(inp.eq(n - 1).val() == 1)
				arr.push(n);
		return {
			op:'expense_spisok',
			category:$('#category').val(),
			worker:$('#worker').val(),
			year:$('#year').val(),
			month:arr.join()
		};
	},
	expenseSpisok = function(v, id) {
		EXPENSE.op = 'expense_spisok';
		EXPENSE.page = 1;
		EXPENSE[id] = v;
		$.post(AJAX_MAIN, EXPENSE, function(res) {
			if(res.success) {
				$('#spisok').html(res.html);
				$('#mon-list').html(res.mon);
			}
		}, 'json');
	};

$(document)
	.on('click', '.accrual-add', function() {
		var html =
			'<table id="accrual-add-tab">' +
				(window.ZAYAV ? '<tr><td class="label">Заявка:<td>' + ZAYAV.head: '') +
				'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money" /> руб.' +
				'<tr><td class="label">Примечание:<em>(не обязательно)</em><td><input type="text" id="about" />' +
//				'<tr><td class="label">Статус заявки: <td><input type="hidden" id="acc_status" value="2" />' +
//				'<tr><td class="label">Добавить напоминание:<td><input type="hidden" id="acc_remind" />' +
			'</table>';
/*
			'<table class="zayav_accrual_add remind">' +
			'<tr><td class="label">Содержание:<td><input type="text" id="reminder_txt" value="Позвонить и сообщить о готовности.">' +
			'<tr><td class="label">Дата:<td><input type="hidden" id="reminder_day">' +
			'</table>';
*/
		var dialog = _dialog({
			width:420,
			head:'Внесение начисления',
			content:html,
			submit:submit
		});
		$('#sum').focus();
		$('#sum,#about').keyEnter(submit);
//		$('#acc_status')._dropdown({spisok:STATUS});
//		$('#acc_remind')._check();
//		$('#acc_remind_check').click(function(id) {
//			$('.zayav_accrual_add.remind').toggle();
//		});
//		$('#reminder_day')._calendar();

		function submit() {
			var send = {
					op:'accrual_add',
					zayav_id:window.ZAYAV ? ZAYAV.id : 0,
					sum:$('#sum').val(),
					about:$('#about').val()
//					status:$('#acc_status').val(),
//					remind:$('#acc_remind').val(),
//					remind_txt:$('#reminder_txt').val(),
//					remind_day:$('#reminder_day').val()
				};
			if(!_cena(send.sum)) {
				dialog.err('Некорректно указана сумма');
				$('#sum').focus();
			} //else if(send.remind == 1 && !send.remind_txt) { msg = 'Не указан текст напоминания'; $('#reminder_txt').focus(); }
			else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					dialog.abort();
					if(res.success) {
						dialog.close();
						_msg('Начисление успешно произведено');
/*
//						$('#money_spisok').html(res.html);
//						zayavMoneyUpdate();
						if(res.status) {
							$('#status')
								.html(res.status.name)
								.css('background-color', '#' + res.status.color);
							$('#status_dtime').html(res.status.dtime);
						}
						if(res.remind)
							$('#remind-spisok').html(res.remind);
*/
					}
				}, 'json');
			}
		}
	})

	.on('click', '#income-next', function() {
		var next = $(this);
		if(next.hasClass('busy'))
			return;
		next.addClass('busy');
		MONEY.op = 'income_spisok';
		MONEY.page = next.attr('val');
		$.post(AJAX_MAIN, MONEY, function(res) {
			if(res.success)
				next.after(res.html).remove();
			else
				next.removeClass('busy');
		}, 'json');
	})
	.on('click', '.income-add', function() {
		var html =
			'<table id="income-add-tab">' +
				'<tr><td class="label">Счёт:<td><input type="hidden" id="invoice_id-add" value="' + (INVOICE_SPISOK.length ? INVOICE_SPISOK[0].uid : 0) + '" />' +
				'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money" /> руб.' +
				'<tr><td class="label">Описание:<td><input type="text" id="about" />' +
			'</table>';
		var dialog = _dialog({
			width:380,
			head:'Внесение платежа',
			content:html,
			submit:submit
		});
		$('#invoice_id-add')._select({
			width:218,
			title0:'Не выбран',
			spisok:INVOICE_SPISOK,
			func:function() {
				$('#sum').focus();
			}
		});
		$('#sum').focus();
		$('#sum,#about').keyEnter(submit);

		function submit() {
			var send = {
				op:'income_add',
				invoice_id:_num($('#invoice_id-add').val()),
				sum:$('#sum').val(),
				about:$('#about').val()
			};
			if(!send.invoice_id) dialog.err('Не указан счёт');
			else if(!_cena(send.sum)) { dialog.err('Некорректно указана сумма'); $('#sum').focus(); }
			else if(!send.about) { dialog.err('Не указано описание'); $('#about').focus(); }
			else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('Новый платёж внесён.');
						incomeSpisok();
					}
				}, 'json');
			}
		}
	})
/*
	.on('click', '.income-add', function() {
		var html =
			'<table id="income-add-tab">' +
				'<input type="hidden" id="zayav_id" value="' + (window.ZAYAV ? ZAYAV.id : 0) + '" />' +
				(window.ZAYAV ? '<tr><td class="label">Заявка:<td><b>№' + ZAYAV.nomer + '</b>' : '') +
				'<tr><td class="label">Счёт:<td><input type="hidden" id="invoice_id" value="' + (INVOICE_SPISOK.length ? INVOICE_SPISOK[0].uid : 0) + '" />' +
				'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money" /> руб.' +
				'<tr><td class="label">Описание:' +
					'<td><input type="hidden" id="prepay" />' +
						'<input type="text" id="about" />' +
			(window.ZAYAV && !ZAYAV.cartridge ?
				'<tr><td class="label topi">Местонахождение<br />устройства:<td><input type="hidden" id="place" value="-1" />' +
			(REMIND.active ?
				'<tr><td><td>' +
					'<div class="_info">' +
						'<b>Есть ' + REMIND.active + ' активн' + _end(REMIND.active, ['ое', 'ых']) + ' напоминани' + _end(REMIND.active, ['е', 'я', 'й']) + '!</b>' +
						'<br />' +
						'<br />' +
						'<input type="hidden" id="remind_active" value="0" />' +
				'</div>'
			: '')
			: '') +
			'</table>';
		var dialog = _dialog({
			width:380,
			head:'Внесение платежа',
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
		$('#sum,#about').keyEnter(submit);
		if(window.ZAYAV && !ZAYAV.cartridge) {
			$('#prepay')._check({
				func:function(v) {
					$('#prim').val(v ? 'предоплата' : '');
				}
			});
			$('#prepay_check')._tooltip('Предоплата', -39);
			zayavPlace();
			$('#remind_active')._check({
				name:'отметить выполненным' + _end(REMIND.active, ['', 'и'])
			});

		}

		function submit() {
			var send = {
				op:'income_add',
				zayav_id:$('#zayav_id').val(),
				cartridge:window.ZAYAV && ZAYAV.cartridge ? 1 : 0,
				invoice_id:_num($('#invoice_id').val()),
				sum:$('#sum').val(),
				prepay:_num($('#prepay').val()),
				prim:$('#prim').val(),
				place:window.ZAYAV && !ZAYAV.cartridge ? $('#place').val() : 0,
				place_other:window.ZAYAV && !ZAYAV.cartridge ? $('#place_other').val() : '',
				remind_active:window.REMIND ? _num($('#remind_active').val()) : 0
			};
			if(!send.invoice_id) dialog.err('Не указан счёт');
			else if(!REGEXP_CENA.test(send.sum)) { dialog.err('Некорректно указана сумма'); $('#sum').focus(); }
			else if(!window.ZAYAV && !send.prim) { dialog.err('Не указано описание'); $('#prim').focus(); }
			else if(window.ZAYAV && !send.cartridge && send.place == -1) dialog.err('Не указано местонахождение устройства');
			else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('Новый платёж внесён.');
						if(window.ZAYAV) {
							$('#money_spisok').html(res.html);
							if(res.comment)
								$('.vkComment').after(res.comment).remove();
							zayavMoneyUpdate();
							if(res.remind)
								$('#remind-spisok').html(res.remind);
						} else
							incomeSpisok();
					}
				}, 'json');
			}
		}
	})
*/
	.on('click', '.income-del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'платежа',
			op:'income_del',
			func:incomeSpisok
		});
	})

	.on('click', '#money-expense #mon-list div', function() {
		var arr = [],
			inp = $('#mon-list input');
		for(var n = 1; n <= 12; n++)
			if(inp.eq(n - 1).val() == 1)
				arr.push(n);
		expenseSpisok(arr.join(), 'month');
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
				expenseTab(dialog, res.arr);
			} else
				dialog.loadError();
		}, 'json');
	})
	.on('click', '#money-expense .img_del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'расхода',
			op:'expense_del',
			func:expenseSpisok
		});
	})

	.on('click', '.invoice-set', function() {
		var t = $(this),
			invoice_id = t.attr('val'),
			html =
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

		$('#sum').focus().keyEnter(submit);

		function submit() {
			var send = {
				op:'invoice_set',
				invoice_id:invoice_id,
				sum:$('#sum').val()
			};
			if(send.sum != 0 && !_cena(send.sum)) {
				dialog.err('Некорректно указана сумма');
				$('#sum').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						$('#invoice-spisok').html(res.html);
						dialog.close();
						_msg('Начальная сумма установлена');
					} else
						dialog.abort();
				}, 'json');
			}
		}
	})
	.on('click', '.invoice-reset', function() {
		var t = $(this),
			invoice_id = t.attr('val'),
			html = 'Сумма на счёте <b>' + INVOICE_ASS[invoice_id] + '</b> будет сброшена.',
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
					_msg('Сумма сброшена');
				} else
					dialog.abort();
			}, 'json');
		}
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

	.ready(function() {
		if($('#money-invoice').length) {
			$('#transfer-add').click(function() {
				var t = $(this),
					from = INVOICE_SPISOK[0] ? INVOICE_SPISOK[0].uid : 0,
					to = INVOICE_SPISOK[1] ? INVOICE_SPISOK[1].uid : 0,
					html = '<table class="_dialog-tab">' +
							'<tr><td class="label">Со счёта:<td><input type="hidden" id="from" value="' + from + '" />' +
							'<tr><td class="label">На счёт:<td><input type="hidden" id="to" value="' + to + '" />' +
							'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money" /> руб. ' +
							'<tr><td class="label">Комментарий:<td><input type="text" id="about" />' +
						'</table>',
					dialog = _dialog({
						width:350,
						head:'Перевод между счетами',
						content:html,
						butSubmit:'Применить',
						submit:submit
					});
				$('#from')._select({
					width:218,
					title0:'Не выбран',
					spisok:INVOICE_SPISOK
				});
				$('#to')._select({
					width:218,
					title0:'Не выбран',
					spisok:INVOICE_SPISOK
				});
				$('#sum').focus();
				$('#sum,#about').keyEnter(submit);
				function submit() {
					var send = {
						op:'invoice_transfer_add',
						from:_num($('#from').val()),
						to:_num($('#to').val()),
						sum:_cena($('#sum').val()),
						about:$('#about').val()
					};
					if(!send.from) dialog.err('Выберите счёт-отправитель');
					else if(!send.to) dialog.err('Выберите счёт-получатель');
					else if(send.from == send.to) dialog.err('Выберите другой счёт');
					else if(!send.sum) {
						dialog.err('Некорректно введена сумма');
						$('#sum').focus();
					} else {
						dialog.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								$('#invoice-spisok').html(res.i);
								$('#transfer-spisok').html(res.t);
								dialog.close();
								_msg('Перевод произведён');
							} else
								dialog.abort();
						}, 'json');
					}
				}
			});
		}
		if($('#invoice-info').length) {
			$('#invoice-info .link').click(function() {
				$('#invoice-info .link').removeClass('sel');
				var i = $(this).addClass('sel').index() - 1;
				$('.ih-cont').hide().eq(i).show();
			});
		}
	});
