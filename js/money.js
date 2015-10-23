var incomeSpisok = function(v, id) {
		MONEY.op = 'income_spisok';
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
	incomeTop = function() {
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
		$('#deleted')._check(incomeSpisok);
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
		var next = $(this),
			send = {
				op:'income_next',
				page:next.attr('val'),
				day:$('.selected').val(),
				del:$('#del').val()
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
		var t = $(this);
		while(t[0].tagName != 'TR')
			t = t.parent();
		if(t.hasClass('deleting'))
			return;
		t.addClass('deleting');
		var send = {
			op:'income_del',
			id:t.attr('val')
		};
		$.post(AJAX_WS, send, function(res) {
			t.removeClass('deleting');
			if(res.success) {
				t.addClass('deleted');
				if(window.ZAYAV)
					zayavMoneyUpdate();
			}
		}, 'json');
	})
	.on('click', '.income-rest', function() {
		var t = $(this);
		while(t[0].tagName != 'TR')
			t = t.parent();
		var send = {
			op:'income_rest',
			id:t.attr('val')
		};
		$.post(AJAX_WS, send, function(res) {
			if(res.success) {
				t.removeClass('deleted');
				if(window.ZAYAV)
					zayavMoneyUpdate();
			}
		}, 'json');
	})

	.ready(function() {
	});
