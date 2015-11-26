var _accrualAdd = function() {
		var html =
			'<div id="_accrual-add">' +
				'<table class="tab">' +
	(window.ZAYAV ? '<tr><td class="label">Заявка:<td>' + ZAYAV.head: '') +
					'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money" /> руб.' +
					'<tr><td class="label">Примечание:<em>(не обязательно)</em><td><input type="text" id="about" />' +
				'</table>' +

				'<div id="status-div">' +
					'<table class="tab">' +
						'<tr><td class="label topi">Статус заявки: <td><input type="hidden" id="acc_status" />' +
					'</table>' +
				'</div>' +

				'<div id="remind-div">' +
					'<table class="tab">' +
						'<tr><td class="label topi">Добавить напоминание:<td><input type="hidden" id="acc_remind" />' +
						'<tr class="remind-tr"><td class="label">Содержание:<td><input type="text" id="remind-txt" value="Позвонить и сообщить о готовности" />' +
						'<tr class="remind-tr"><td class="label">Дата:<td><input type="hidden" id="remind-day" />' +
					'</table>' +
				'</div>' +
			'</div>';

		var dialog = _dialog({
			top:30,
			width:480,
			head:'Внесение начисления',
			padding:0,
			content:html,
			butSubmit:'Далее...',
			submit:toStatus
		});

		$('#sum').focus();
		$('#acc_status')._radio({
			light:1,
			spisok:STATUS,
			func:function() {
				$('#remind-div').slideDown(300);
			}
		});
		$('#acc_remind')._radio({
			light:1,
			block:0,
			spisok:[
				{uid:1,title:'да'},
				{uid:2,title:'нет'}
			],
			func:function(v) {
				$('.remind-tr')[v == 1 ? 'show' : 'hide']();
				dialog.submit(submit);
				dialog.butSubmit('Внести');
			}
		});
		$('#remind-day')._calendar();

		function toStatus() {
			if(!_cena($('#sum').val())) {
				dialog.err('Некорректно указана сумма');
				$('#sum').focus();
				return false;
			}
			if($('#status-div').is(':hidden')) {
				$('#status-div').slideDown(300);
				return false;
			}
			if(!_num($('#acc_status').val())) {
				dialog.err('Укажите статус заявки');
				return false;
			}
			if(!_num($('#acc_remind').val())) {
				dialog.err('Выберите, нужно ли добавлять напоминание');
				return false;
			}
		}
		function submit() {
			var send = {
					op:'accrual_add',
					zayav_id:window.ZAYAV ? ZAYAV.id : 0,
					sum:$('#sum').val(),
					about:$('#about').val(),
					zayav_status:$('#acc_status').val(),
					remind:_num($('#acc_remind').val()) == 1 ? 1 : 0,
					remind_txt:$('#remind-txt').val(),
					remind_day:$('#remind-day').val()
				};
			if(!_cena(send.sum)) {
				dialog.err('Некорректно указана сумма');
				$('#sum').focus();
			} else if(send.remind && !send.remind_txt) {
				dialog.err('Не указано содержание напоминания');
				$('#remind-txt').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					dialog.abort();
					if(res.success) {
						dialog.close();
						_msg('Начисление успешно произведено');
						location.reload();
					}
				}, 'json');
			}
		}
	},

	_incomeAdd = function() {
		var zayav = window.ZAYAV,
			about = zayav ? 'Примечаниие' : 'Описание',
			about_placeholder = zayav ? ' placeholder="не обязательно"' : '',
			html =
			'<div id="_income-add">' +
				'<table class="tab">' +
		   (zayav ? '<tr><td class="label">Клиент:<td>' + ZAYAV.client_link : '') +
		   (zayav ? '<tr><td class="label">Заявка:<td>' + ZAYAV.head : '') +
					'<tr><td class="label">Счёт:<td><input type="hidden" id="invoice_id-add" value="' + (INVOICE_SPISOK.length ? INVOICE_SPISOK[0].uid : 0) + '" />' +
					'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money" /> руб.' +
		   (zayav ? '<tr><td class="label">Предоплата:<td>' : '') +
						'<input type="hidden" id="prepay" />' +
					'<tr><td class="label">' + about + ':<td><input type="text" id="about"' + about_placeholder + ' />' +
				'</table>' +

		   (zayav ?
				'<div id="place-div">' +
					'<table class="tab">' +
						'<tr><td class="label topi">Местонахождение<br />устройства:<td><input type="hidden" id="place" value="-1" />' +
					'</table>' +
				'</div>' +

				(REMIND.active ?
					'<div id="remind-div">' +
						'<h1>Есть ' + REMIND.active + ' активн' + _end(REMIND.active, ['ое', 'ых']) + ' напоминани' + _end(REMIND.active, ['е', 'я', 'й']) + '.</h1>' +
						'<h2>Поставье галочку напоминанию, если его нужно отметить выполненным.</h2>' +
						incomeRemind() +
					'</div>'
		        : '')
		   : '') +

			'</div>';
		var dialog = _dialog({
			top:zayav ? 30 : 60,
			width:460,
			head:'Внесение платежа',
			padding:0,
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
					$('#place-div').slideDown(300);
				}
			});
			zayavPlace(function() {
				$('#remind-div').slideDown(300);
			});
			for(var n = 0; n < REMIND.active_spisok.length; n++) {
				var i = REMIND.active_spisok[n];
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
			for(var n = 0; n < REMIND.active_spisok.length; n++) {
				var i = REMIND.active_spisok[n];
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
				for(var n = 0; n < REMIND.active_spisok.length; n++) {
					var i = REMIND.active_spisok[n];
					if(_num($('#ui' + i.id).val()))
						remind.push(i.id);
				}
			var send = {
				op:'income_add',
				invoice_id:_num($('#invoice_id-add').val()),
				sum:_cena($('#sum').val()),
				prepay:_num($('#prepay').val()),
				about:$('#about').val(),
				zayav_id:zayav ? ZAYAV.id : 0,
				place:zayav ? $('#place').val() : 0,
				place_other:zayav ? $('#place_other').val() : '',
				remind_ids:remind.join()
			};
			if(!send.invoice_id)
				dialog.err('Не указан счёт');
			else if(!send.sum) {
				dialog.err('Некорректно указана сумма');
				$('#sum').focus();
			} else if(zayav && !send.prepay) {
				dialog.err('Укажите, является ли платёж предоплатой');
				$('#sum').focus();
			} else if(!zayav && !send.about) {
				dialog.err('Не указано описание');
				$('#about').focus();
			} else if(zayav && (send.place == -1 || send.place == 0 && ! send.place_other)) {
				dialog.err('Не указано местонахождение устройства');
				$('#place_other').focus()
			} else {
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
				'<tr><td class="label">Клиент:<td>' + ZAYAV.client_link +
				'<tr><td class="label">Со счёта:<td><input type="hidden" id="invoice_id" value="' + (INVOICE_SPISOK.length ? INVOICE_SPISOK[0].uid : 0) + '" />' +
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
			spisok:INVOICE_SPISOK,
			func:function(v) {
				$('#sum').focus();
			}
		});

		function submit() {
			var send = {
				op:'refund_add',
				zayav_id:ZAYAV.id,
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

	_expenseTab = function(dialog, arr) {//таблица для внесения или редактирования расхода
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
			spisok:arr.id ? EXPENSE_WORKER : WORKER_SPISOK
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
			spisok:_toSpisok(_yearAss(arr.year))
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
						_msg();
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
		_expenseTab(dialog);
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
	},

	_zayavExpenseEdit = function () {//вывод окна для редактирование расходов по заявке в информации о заявке
		var html =
			'<table id="zee-tab">' +
				'<tr><td class="label">Заявка:<td><b>' + ZAYAV.head + '</b>' +
				'<tr><td class="label">Список расходов:' +
				'<tr><td id="zee-spisok" colspan="2">' +
			'</table>',
			dialog = _dialog({
				top: 30,
				width: 510,
				head: 'Изменение расходов заявки',
				content: html,
				butSubmit: 'Сохранить',
				submit: submit
			});

		_zayavExpense();

		function submit() {
			var send = {
				op:'zayav_expense_edit',
				zayav_id:ZAYAV.id,
				expense:_zayavExpenseGet()
			};
			if(send.expense == 'sum_error')
				dialog.err('Некорректно указана сумма');
			else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
//						zayavMoneyUpdate();
						dialog.close();
						_msg('Сохранено');
						$('#_zayav-expense').html(res.html);
					} else
						dialog.abort();
				}, 'json');
			}
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
					'',//2 - описание, id сотрудника или id запчасти
					'' //3 - сумма
				];
			var html =
					'<table id="zee-tab'+ num + '" class="zee-tab" val="' + num + '">' +
						'<tr><td><input type="hidden" id="' + num + 'cat" value="' + v[1] + '" />' +
							'<td class="dop">' +
							'<td class="tdsum">' +
								'<input type="text" class="zee-sum" tabindex="' + (num * 10) + '" value="' + v[3] + '" />руб.' +
								'<input type="hidden" class="id" value="' + v[0] + '" />' +
					'</table>';

			$('#zee-spisok').append(html);
			itemDop(v[1], v[2], num);

			var tab = $('#zee-tab' + num);
			$('#' + num + 'cat')._select({
				width:130,
				disabled:0,
				title0:'Категория',
				spisok:ZAYAV_EXPENSE_SPISOK,
				func:function(id, attr) {
					tab.find('.id').val(0);
					itemDop(id, '', attr.split('cat')[0]);
//					sum.val(id == 1 ? ZAYAV.worker_zp : '');
					if(id && !tab.next().hasClass('zee-tab'))
						item();
				}
			});

			num++;
		}
		function itemDop(cat_id, val, num) {
			var tab = $('#zee-tab' + num),
				dop = tab.find('.dop'),
				sum = tab.find('.zee-sum');

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
					disabled:0,
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
					spisok:ZAYAV.zp_avai,
					func:function(v) {
						sum.focus();
					}
				});
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

			send.push(id + ':' +
					  cat_id + ':' +
					  dop + ':' +
					  sum);
		}
		return send.join();
	},

	_salarySpisok = function() {
		var send = {
			op:'salary_spisok',
			id:SALARY.worker_id,
			year:_num($('#year').val()),
			mon:_num($('#salmon').val())
		};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				SALARY.mon = send.mon;
				SALARY.year = send.year;
				$('.headName em').html(MONTH_DEF[send.mon] + ' ' + send.year);
				$('._balans-show').html(res.balans);
				$('#spisok-acc').html(res.acc);
				$('#spisok-zp').html(res.zp);
				$('#month-list').html(res.month);
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

		$('#sum').focus().keyEnter(submit);

		function submit() {
			var send = {
				op:'salary_balans_set',
				worker_id:SALARY.worker_id,
				sum:_cena($('#sum').val(), 1)
			};
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
					$('._balans-show').html(res.balans);
				} else
					dialog.abort();
			}, 'json');
		}
	},
	_salaryWorkerRateSet = function() {//установка ставки сотрудника
		var html =
			'<div class="_info">' +
				'После установки ставки сотруднику указанная сумма будет автоматически начисляться ' +
				'на его баланс в определённый день выбранной периодичностью. ' +
			'</div>' +
			'<table class="_dialog-tab" id="salary-rate-set-tab">' +
				'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money" value="' + (SALARY.rate_sum ? SALARY.rate_sum : '') + '" /> руб.' +
				'<tr><td class="label">Период:<td><input type="hidden" id="period" value="' + SALARY.rate_period + '" />' +
				'<tr class="tr-day' + (SALARY.rate_period == 3 ? ' dn' : '') + '">' +
					'<td class="label">День начисления:' +
					'<td><div class="div-day' + (SALARY.rate_period != 1 ? ' dn' : '') + '"><input type="text" id="day" maxlength="2" value="' + SALARY.rate_day + '" /></div>' +
						'<div class="div-week' + (SALARY.rate_period != 2 ? ' dn' : '') + '"><input type="hidden" id="day_week" value="' + SALARY.rate_day + '" /></div>' +
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
		$('#sum,#day').keyEnter(submit);
		$('#period')._select({
			width:70,
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
				'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money"> руб.' +
				'<tr><td class="label">Описание:<td><input type="text" id="about" maxlength="50">' +
				'<tr><td class="label">Месяц:' +
					'<td><input type="hidden" id="tabmon" value="' + SALARY.mon + '" /> ' +
						'<input type="hidden" id="tabyear" value="' + SALARY.year + '" />' +
			'</table>',
			dialog = _dialog({
				head:'Внесение начисления для сотрудника',
				content:html,
				submit:submit
			});

		$('#sum').focus();
		$('#sum,#about').keyEnter(submit);
		$('#tabmon')._select({
			width:80,
			spisok:_toSpisok(MONTH_DEF)
		});
		$('#tabyear')._select({
			width:60,
			spisok:_toSpisok(_yearAss(SALARY.year))
		});
		function submit() {
			var send = {
				op:'salary_accrual_add',
				worker_id:SALARY.worker_id,
				sum:_cena($('#sum').val()),
				about:$.trim($('#about').val()),
				mon:$('#tabmon').val(),
				year:$('#tabyear').val()
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
				'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money"> руб.' +
				'<tr><td class="label">Описание:<td><input type="text" id="about" maxlength="50">' +
				'<tr><td class="label">Месяц:' +
					'<td><input type="hidden" id="tabmon" value="' + SALARY.mon + '" /> ' +
						'<input type="hidden" id="tabyear" value="' + SALARY.year + '" />' +
			'</table>',
			dialog = _dialog({
				head:'Внесение вычета из зарплаты',
				content:html,
				submit:submit
			});

		$('#sum').focus();
		$('#sum,#about').keyEnter(submit);
		$('#tabmon')._select({
			width:80,
			spisok:_toSpisok(MONTH_DEF)
		});
		$('#tabyear')._select({
			width:60,
			spisok:_toSpisok(_yearAss(SALARY.year))
		});
		function submit() {
			var send = {
				op:'salary_deduct_add',
				worker_id:SALARY.worker_id,
				sum:_cena($('#sum').val()),
				about:$.trim($('#about').val()),
				mon:$('#tabmon').val(),
				year:$('#tabyear').val()
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
	_salaryWorkerZpAdd = function() {//внесение зп сотруднику
		var html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">Со счёта:<td><input type="hidden" id="invoice_id" />' +
					'<tr><td class="label">Сумма:<td><input type="text" id="sum" class="money"> руб.' +
					'<tr><td class="label">Месяц:' +
						'<td><input type="hidden" id="tabmon" value="' + SALARY.mon + '" /> ' +
							'<input type="hidden" id="tabyear" value="' + SALARY.year + '" />' +
					'<tr><td class="label">Описание:<td><input type="text" id="about">' +
				'</table>',
			dialog = _dialog({
				head:'Выдача зарплаты сотруднику',
				content:html,
				submit:submit
			});

		$('#sum').focus();
		$('#invoice_id')._select({
			title0:'Не выбран',
			spisok:INVOICE_SPISOK,
			func:function() {
				$('#sum').focus();
			}
		});
		$('#sum,#about').keyEnter(submit);
		$('#tabmon')._select({
			width:80,
			spisok:_toSpisok(MONTH_DEF)
		});
		$('#tabyear')._select({
			width:60,
			spisok:_toSpisok(_yearAss(SALARY.year))
		});

		function submit() {
			var send = {
				op:'expense_add',
				category_id:1,
				worker_id:SALARY.worker_id,
				invoice_id:_num($('#invoice_id').val()),
				sum:_cena($('#sum').val()),
				about:$('#about').val(),
				mon:$('#tabmon').val(),
				year:$('#tabyear').val()
			};
			if(!send.invoice_id)
				dialog.err('Укажите с какого счёта производится выдача');
			else if(!send.sum) {
				dialog.err('Некорректно указана сумма');
				$('#sum').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('Выдача зарплаты произведена');
						_salarySpisok();
					} else
						dialog.abort();
				}, 'json');
			}
		}
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
				id:o.id ? o.id : $(this).attr('val')
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
						'<tr><td class="label top">Плательщик:<td>' + res.client +
					'</table>' +
					'<h1>СЧЁТ № ' + res.nomer + res.ot + '</h1>' +
					res.html +
					'<h2>' + res.itog + '</h2>' +
					'<table id="dop">' +
						'<tr><td>' +
								(res.nakl ? '<div>&bull; Накладная</div>' : '') +
								(res.act ? '<div>&bull; Акт выполненных работ</div>' : '') +
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
					id:res.schet_id,
					head:'счёта № ' + res.nomer,
					op:'schet_del',
					func:function() {

					}
				});
			});
			$('#schet-history').click(function() {
				$('#hist').slideDown(300);
			});
		}
	},
	_schetEdit = function(res) {
		var spisok = '';
		for(var n = 0; n < res.arr.length; n++) {
			var sp = res.arr[n];
			sp.sum = sp.cost * sp.count;
			spisok += pole(sp);
		}
		var html =
				'<div id="_schet-info">' +
					'<table class="tab">' +
						'<tr><td class="label r top">Плательщик:<td>' + res.client +
						'<tr><td class="label r">Дата:<td><input type="hidden" id="date_create" value="' + res.date_create + '" />' +
						'<tr><td class="label r topi">Приложения:' +
							'<td><input id="nakl" type="hidden" value="' + res.nakl + '" />' +
								'<input id="act" type="hidden" value="' + res.act + '" />' +
					'</table>' +
					'<h1>СЧЁТ № ' + res.nomer + '</h1>' +
					'<table class="_spisok">' +
						'<tr><th>№' +
							'<th>Наименование товара' +
							'<th>Кол-во' +
							'<th>Цена' +
							'<th>Сумма' +
							'<th>' +
						spisok +
						'<tr><td colspan="6" class="_next" id="pole-add">Добавить позицию' +
					'</table>' +
					'<h3></h3>' +
				'</div>',
			dialog = _dialog({
				top:20,
				width:610,
				head:'Редактирование счёта',
				content:html,
				butSubmit:'Сохранить',
				submit:submit,
				cancel:function() {
					if(!res.edit)
						_schetInfo(res);
				}
			});

		poleNum();
		itog();
		$('#date_create')._calendar({lost:1});
		$('#nakl')._check({name:'Накладная',light:1});
		$('#act')._check({name:'Акт выполненных работ',light:1});
		$('#pole-add').click(function() {//добавление новой позиции счёта
			$(this)
				.parent()
				.before(pole)
				.parent()
				.find('.name:last input').focus();
			poleNum();
		});
		$(document)
			.on('click', '.pole-del', function() {
				_parent($(this)).remove();
				poleNum();
				itog();
			})
			.on('keyup', '.pole input', function() {
				var t = _parent($(this)),
					count = t.find('.count input').val(),
					cost = t.find('.cost input').val(),
					sum = count * _cena(cost);
				t.find('.sum').html(sum ? sum : '');
				itog();
			});

		function pole(sp) {
			sp = $.extend({
				name:'',
				count:1,
				cost:''
			}, sp);
			var sum = sp.count * _cena(sp.cost);
			return '<tr class="pole">' +
				'<td class="n">' +
				'<td class="name"><input type="text" value="' + sp.name + '" />' +
				'<td class="count"><input type="text" value="' + sp.count + '" />' +
				'<td class="cost"><input type="text" value="' + sp.cost + '" />' +
				'<td class="sum">' + (sum ? sum : '') +
				'<td class="ed"><div class="img_del pole-del' + _tooltip('Удалить', -29) + '</div>';
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
				arr = [],   //массив для возврата
				err = 0;    //найдена ли ошибка
			for(var n = 0; n < pole.length; n++) {
				var eq = pole.eq(n),
					name_inp = eq.find('.name input'),
					count_inp = eq.find('.count input'),
					cost_inp = eq.find('.cost input'),
					name = $.trim(name_inp.val()),
					count = _num(count_inp.val()),
					cost = _cena(cost_inp.val()),
					s = count * cost;
				if(s)
					num++;
				name_inp[(name ? 'remove' : 'add') + 'Class']('err');
				count_inp[(count ? 'remove' : 'add') + 'Class']('err');
				cost_inp[(cost ? 'remove' : 'add') + 'Class']('err');
				sum += s;
				if(!err && name && !count) {
					err = 1;
					arr = {
						error:1,
						msg:'Некорректно указано количество',
						place:count_inp
					};
				}
				if(!err && name && !cost) {
					err = 1;
					arr = {
						error:1,
						msg:'Некорректно указана сумма',
						place:cost_inp
					};
				}
				if(!err && !name && count && cost) {
					err = 1;
					arr = {
						error:1,
						msg:'Не указано наименование',
						place:name_inp
					};
				}
				if(!err)
					arr.push({
						name:name,
						count:count,
						cost:cost
					});
			}

			if(!err && !num)
				arr = {
					error:1,
					msg:'Не добавлено ни одной позиции'
				};

			$('#_schet-info h3').html('Всего наименований <b>' + num + '</b>, на сумму <b>' + sum + '</b> руб.');
			return arr;
		}
		function submit() {
			var send = {
				op:'schet_edit',
				schet_id:res.schet_id,
				client_id:res.client_id,
				zayav_id:res.zayav_id,
				spisok:itog(),
				date_create:$('#date_create').val(),
				nakl:_bool($('#nakl').val()),
				act:_bool($('#act').val())
			};
			if(send.spisok.error) {
				dialog.err(send.spisok.msg);
				if(send.spisok.place)
					send.spisok.place.focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg();
						_schetInfo({id:send.schet_id});
					} else
						dialog.abort();
				}, 'json');
			}
		}
	},
	_schetSpisok = function(v, id) {
		SCHET.page = 1;
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
					'<tr><td class="label">Расчётный счёт:<td><input type="hidden" id="invoice_id" value="4" />' +
				'</table>';
		var dialog = _dialog({
				head:'Оплата счёта',
				content:html,
				butSubmit:'Оплатить',
				submit:submit
			});

		$('#pay-day')._calendar({lost:1});
		$('#invoice_id')._select({
			width:200,
			spisok:INVOICE_SPISOK,
			func:function() {
				$('#sum').focus();
			}
		});
		$('#sum').focus().keyEnter(submit);
		function submit() {
			var send = {
				op:'schet_pay',
				schet_id:schet_id,
				invoice_id:_num($('#invoice_id').val()),
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
			func:expenseSpisok
		});
	})

	.on('click', '.schet-unit .info', _schetInfo)

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

	.on('click', '#_zayav-expense .img_edit', _zayavExpenseEdit)

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
	.on('click', '.worker-zp-add', _salaryWorkerZpAdd)
	.on('click', '.worker-zp-del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'зарплаты',
			op:'expense_del',
			func:_salarySpisok
		});
	})

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
		if($('#salary-worker').length) {
			$('#action')._dropdown({
				head:'Действие',
				nosel:1,
				spisok:[
					{uid:1, title:'Установить баланс'},
					{uid:2, title:'Изменить ставку'},
					{uid:3, title:'Начислить'},
					{uid:4, title:'Внести вычет'},
					{uid:5, title:'Выдать з/п'}
				],
				func:function(v) {
					switch(v) {
						case 1: _salaryWorkerBalansSet(); break;
						case 2: _salaryWorkerRateSet(); break;
						case 3: _salaryWorkerAccAdd(); break;
						case 4: _salaryWorkerDeductAdd(); break;
						case 5: _salaryWorkerZpAdd(); break;
					}
				}
			});
			$('#year').years({func:_salarySpisok});
			$('#salmon')._radio({func:_salarySpisok});
		}
	});
