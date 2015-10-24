var incomeLoad = function() {
		window._calendarFilter = incomeSpisok;
		$('#invoice_id')._radio({
			light:1,
			title0:'��� �����',
			spisok:INVOICE_SPISOK,
			func:incomeSpisok
		});
		$('#worker_id')._select({
			width:190,
			title0:'��� ����������',
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

	expenseLoad = function() {
		$('.add').click(function() {
			console.log(INVOICE_SPISOK);
			var html =
					'<table id="expense-add-tab">' +
					'<tr><td class="label">���������:<td><input type="hidden" id="category_id-add" />' +
					'<a href="' + URL + '&p=setup&d=expense" class="img_edit' + _tooltip('��������� ��������� ��������', -95) + '</a>' +
					'<tr class="tr-work dn"><td class="label">���������:<td><input type="hidden" id="worker_id-add" />' +
					'<tr class="tr-work dn"><td class="label">�����:' +
					'<td><input type="hidden" id="tabmon" value="' + ((new Date).getMonth() + 1) + '" /> ' +
					'<input type="hidden" id="tabyear" value="' + (new Date).getFullYear() + '" />' +
					'<tr><td class="label">��������:<td><input type="text" id="about" maxlength="100">' +
					'<tr><td class="label">�� �����:<td><input type="hidden" id="invoice_id-add" value="' + (INVOICE_SPISOK.length ? INVOICE_SPISOK[0].uid : 0) + '" />' +
					'<tr><td class="label">�����:<td><input type="text" id="sum" class="money" maxlength="11" /> ���.' +
					'</table>',
				dialog = _dialog({
					width:380,
					head:'�������� �������',
					content:html,
					submit:submit
				});

			$('#category_id-add')._select({
				width:200,
				title0:'�� �������',
				spisok:EXPENSE_SPISOK,
				func:function(id) {
					$('#worker_id')._select(0);
					$('.tr-work')[(EXPENSE_WORKER[id] ? 'remove' : 'add') + 'Class']('dn');
				}
			});
			$('#worker_id-add')._select({
				width:200,
				title0:'�� ������',
				spisok:_toSpisok(WORKER_ASS)
			});
			$('#about').focus();
			$('#invoice_id-add')._select({
				width:200,
				title0:'�� ������',
				spisok:INVOICE_SPISOK,
				func:function() {
					$('#sum').focus();
				}
			});
			$('#tabmon')._select({
				width:80,
				spisok:_toSpisok(MONTH_DEF)
			});
			$('#tabyear')._select({
				width:60,
				spisok:YEAR_SPISOK
			});

			function submit() {
				var send = {
					op:'expense_add',
					category_id:_num($('#category_id-add').val()),
					worker_id:$('#worker_id-add').val(),
					about:$('#about').val(),
					invoice_id:_num($('#invoice_id-add').val()),
					sum:_cena($('#sum').val()),
					mon:$('#tabmon').val(),
					year:$('#tabyear').val()
				};
				if(!send.about && !send.category_id) {
					dialog.err('�������� ��������� ��� ������� ��������.');
					$('#about').focus();
				} else if(!send.invoice_id)
					dialog.err('������� � ������ ����� ������������ ������.');
				else if(!send.sum) {
					dialog.err('����������� ������� �����.');
					$('#sum').focus();
				} else {
					dialog.process();
					$.post(AJAX_MAIN, send, function (res) {
						if(res.success) {
							dialog.close();
							_msg('����� ������ �����');
							expenseSpisok();
						} else
							dialog.abort();
					}, 'json');
				}
			}
		});
		$('#invoice_id')._select({
			width:140,
			title0:'��� �����',
			spisok:INVOICE_SPISOK,
			func:expenseSpisok
		});
		$('#category_id')._select({
			width:140,
			title0:'����� ���������',
			spisok:EXPENSE_SPISOK,
			func:expenseSpisok
		});
		$('#worker_id')._select({
			width:140,
			title0:'��� ����������',
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
				expenseSpisok();
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
		_busy();
		$.post(AJAX_MAIN, EXPENSE, function(res) {
			_busy(0);
			if(res.success) {
				$('#spisok').html(res.html);
				$('#mont-list').html(res.mon);
			}
		}, 'json');
	};

$(document)
	.on('click', '.accrual-add', function() {
		var html =
			'<table id="accrual-add-tab">' +
				(window.ZAYAV ? '<tr><td class="label">������:<td>' + ZAYAV.head: '') +
				'<tr><td class="label">�����:<td><input type="text" id="sum" class="money" /> ���.' +
				'<tr><td class="label">����������:<em>(�� �����������)</em><td><input type="text" id="about" />' +
//				'<tr><td class="label">������ ������: <td><input type="hidden" id="acc_status" value="2" />' +
//				'<tr><td class="label">�������� �����������:<td><input type="hidden" id="acc_remind" />' +
			'</table>';
/*
			'<table class="zayav_accrual_add remind">' +
			'<tr><td class="label">����������:<td><input type="text" id="reminder_txt" value="��������� � �������� � ����������.">' +
			'<tr><td class="label">����:<td><input type="hidden" id="reminder_day">' +
			'</table>';
*/
		var dialog = _dialog({
			width:420,
			head:'�������� ����������',
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
				dialog.err('����������� ������� �����');
				$('#sum').focus();
			} //else if(send.remind == 1 && !send.remind_txt) { msg = '�� ������ ����� �����������'; $('#reminder_txt').focus(); }
			else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					dialog.abort();
					if(res.success) {
						dialog.close();
						_msg('���������� ������� �����������');
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
				'<tr><td class="label">����:<td><input type="hidden" id="invoice_id-add" value="' + (INVOICE_SPISOK.length ? INVOICE_SPISOK[0].uid : 0) + '" />' +
				'<tr><td class="label">�����:<td><input type="text" id="sum" class="money" /> ���.' +
				'<tr><td class="label">��������:<td><input type="text" id="about" />' +
			'</table>';
		var dialog = _dialog({
			width:380,
			head:'�������� �������',
			content:html,
			submit:submit
		});
		$('#invoice_id-add')._select({
			width:218,
			title0:'�� ������',
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
			if(!send.invoice_id) dialog.err('�� ������ ����');
			else if(!_cena(send.sum)) { dialog.err('����������� ������� �����'); $('#sum').focus(); }
			else if(!send.about) { dialog.err('�� ������� ��������'); $('#about').focus(); }
			else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('����� ����� �����.');
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
				(window.ZAYAV ? '<tr><td class="label">������:<td><b>�' + ZAYAV.nomer + '</b>' : '') +
				'<tr><td class="label">����:<td><input type="hidden" id="invoice_id" value="' + (INVOICE_SPISOK.length ? INVOICE_SPISOK[0].uid : 0) + '" />' +
				'<tr><td class="label">�����:<td><input type="text" id="sum" class="money" /> ���.' +
				'<tr><td class="label">��������:' +
					'<td><input type="hidden" id="prepay" />' +
						'<input type="text" id="about" />' +
			(window.ZAYAV && !ZAYAV.cartridge ?
				'<tr><td class="label topi">���������������<br />����������:<td><input type="hidden" id="place" value="-1" />' +
			(REMIND.active ?
				'<tr><td><td>' +
					'<div class="_info">' +
						'<b>���� ' + REMIND.active + ' ������' + _end(REMIND.active, ['��', '��']) + ' ����������' + _end(REMIND.active, ['�', '�', '�']) + '!</b>' +
						'<br />' +
						'<br />' +
						'<input type="hidden" id="remind_active" value="0" />' +
				'</div>'
			: '')
			: '') +
			'</table>';
		var dialog = _dialog({
			width:380,
			head:'�������� �������',
			content:html,
			submit:submit
		});
		$('#invoice_id')._select({
			width:218,
			title0:'�� ������',
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
					$('#prim').val(v ? '����������' : '');
				}
			});
			$('#prepay_check')._tooltip('����������', -39);
			zayavPlace();
			$('#remind_active')._check({
				name:'�������� �����������' + _end(REMIND.active, ['', '�'])
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
			if(!send.invoice_id) dialog.err('�� ������ ����');
			else if(!REGEXP_CENA.test(send.sum)) { dialog.err('����������� ������� �����'); $('#sum').focus(); }
			else if(!window.ZAYAV && !send.prim) { dialog.err('�� ������� ��������'); $('#prim').focus(); }
			else if(window.ZAYAV && !send.cartridge && send.place == -1) dialog.err('�� ������� ��������������� ����������');
			else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('����� ����� �����.');
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
			head:'�������',
			op:'income_del',
			func:incomeSpisok
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
	.on('click', '#money-expense .img_del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'�������',
			op:'expense_del',
			func:expenseSpisok
		});
	})




;
