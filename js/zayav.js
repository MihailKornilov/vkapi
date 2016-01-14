var _zayavSpisok = function(v, id) {
		_filterSpisok(ZAYAV, v, id);
		$('.condLost')[(ZAYAV.find ? 'add' : 'remove') + 'Class']('hide');
		$.post(AJAX_MAIN, ZAYAV, function(res) {
			if(res.success) {
				$('.result').html(res.all);
				$('#spisok').html(res.spisok);
			}
		}, 'json');
	},
	_zayavEdit = function() {
		var c = $.extend({//���� ������ �������� �� �������, �� ��������� ������ � �������
				id:0,
				name:''
			}, window.CLIENT || {}),
			o = $.extend({
				id:0,
				client_id:c.id,
				client_name:c.name,
				name:'',
				about:'',
				count:'',
				product:[[0,0,0]],
				adres:'',
				imei:'',
				serial:'',
				color_id:0,
				color_dop:0,
				day_finish:'0000-00-00',
				place:0,
				diagnost:0,
				sum_cost:''
			}, ZAYAV);

		var html =
				'<table id="_zayav-add-tab">' +
					'<tr><td class="label">������:' +
						'<td><input type="hidden" id="client_id" value="' + o.client_id + '" />' +
							'<b>' + o.client_name + '</b>' +
					'<tr' + (ZAYAV_USE_INFO_NAME ?    '' : ' class="dn"') + '><td class="label">��������:<td><input type="text" id="name" value="' + o.name + '" />' +
					'<tr' + (ZAYAV_USE_INFO_ABOUT ?   '' : ' class="dn"') + '><td class="label topi">��������:<td><textarea id="about">' + o.about + '</textarea>' +
					'<tr' + (ZAYAV_USE_INFO_COUNT ?   '' : ' class="dn"') + '><td class="label">����������:<td><input type="text" id="count" value="' + o.count + '" /> ��.' +
					'<tr' + (ZAYAV_USE_INFO_PRODUCT ? '' : ' class="dn"') + '><td class="label topi">�������:<td id="product">' +
					'<tr' + (ZAYAV_USE_INFO_ADRES ?   '' : ' class="dn"') + '><td class="label">�����:<td><input type="text" id="adres" value="' + o.adres + '" />' +
					'<tr' + (ZAYAV_USE_INFO_DEVICE ?  '' : ' class="dn"') + '><td class="label">����������:<td>' +
					'<tr' + (ZAYAV_USE_INFO_IMEI ?    '' : ' class="dn"') + '><td class="label">IMEI:<td><input type="text" id="imei" value="' + o.imei + '" />' +
					'<tr' + (ZAYAV_USE_INFO_SERIAL ?  '' : ' class="dn"') + '><td class="label">�������� �����:<td><input type="text" id="serial" value="' + o.serial + '" />' +
					'<tr' + (ZAYAV_USE_INFO_COLOR ?   '' : ' class="dn"') + '><td class="label">����:<td id="color">' +
					'<tr' + (ZAYAV_USE_INFO_EQUIP ?   '' : ' class="dn"') + '><td class="label">������������:<td>' +
					'<tr' + (ZAYAV_USE_INFO_SERIAL ?  '' : ' class="dn"') + '>' +
						'<td class="label">��������������� ����������<br />����� �������� ������:' +
						'<td><input type="hidden" id="place" value="' + o.place + '" />' +
					'<tr' + (ZAYAV_USE_INFO_DIAGNOST ?'' : ' class="dn"') + '><td class="label">�����������:<td><input type="hidden" id="diagnost" value="' + o.diagnost + '" />' +
					'<tr><td class="label">��������������� ���������:<td><input type="text" class="money" id="sum_cost" value="' + (o.sum_cost ? o.sum_cost : '') + '" /> ���.' +

				(!o.id && ZAYAV_USE_INFO_NOTE ?
					'<tr><td class="label top">�������:<td><textarea id="note"></textarea>'
				: '') +

				(!o.id && ZAYAV_USE_INFO_SROK ?
					'<tr><td class="label top">����:' +
						'<td><input type="hidden" id="day_finish" value="' + o.day_finish + '" />' +
							'<div class="day-finish-link">' +
								'<span>�� ������</span>' +
							'</div>'
				: '') +

				'</table>',
			dialog = _dialog({
				width:550,
				top:30,
				head:o.id ? '�������������� ������' : '�������� ����� ������',
				content:html,
				butSubmit:o.id ? '���������' : '������',
				submit:submit
			});

		if(!o.client_name)
			$('#client_id').clientSel({add:1});
		$('#name').focus();
		$('#about').autosize();
		$('#product').product(o.product);
		$('#color')._selectColor({
			color_id:o.color_id,
			color_dop:o.color_dop
		});
		$('#diagnost')._check();
		$('#note').autosize();

		function submit() {
			var send = {
					op:o.id ? 'zayav_edit' : 'zayav_add',
					zayav_id:o.id,
					client_id:_num($('#client_id').val()),
					name:$('#name').val(),
					about:$('#about').val(),
					product_id:$('#item').val(),
					product_sub_id:$('#item-sub').val(),
					product_count:$('#item-count').val(),
					count:$('#count').val(),
					adres:$('#adres').val(),
					imei:$('#imei').val(),
					serial:$('#serial').val(),
					color_id:$('#color_id').val(),
					color_dop:$('#color_dop').val(),
					place:$('#place').val(),
					diagnost:$('#diagnost').val(),
					sum_cost:$('#sum_cost').val(),
					note:$('#note').val(),
					day_finish:'0000-00-00'
				};

			if(!o.id && ZAYAV_USE_INFO_SROK) {
				send.day_finish = $('#day_finish').val();
				if(send.day_finish == '0000-00-00') {
					dialog.err('�� ������ ����');
					return;
				}
			}

			if(!send.client_id) {
				dialog.err('�� ������ ������');
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
			avans_hide:0,//�������� ��������� �����, ���� �������� ��������� ���� ����� ���������� ��������
			avans_invoice_id:0,
			avans_sum:''
		}, DOG);

		var html = '<table id="_zayav-dog-tab">' +
				'<tr><td class="label">�.�.�. �������:<td><input type="text" id="fio" value="' + o.fio + '" />' +
				'<tr><td class="label">�����:<td><input type="text" id="adres" value="' + o.adres + '" />' +
				'<tr><td class="label">�������:' +
					'<td>�����:<input type="text" id="pasp_seria" maxlength="8" value="' + o.pasp_seria + '" />' +
						'�����:<input type="text" id="pasp_nomer" maxlength="10" value="' + o.pasp_nomer + '" />' +
				'<tr><td><td><span class="l">��������:</span><input type="text" id="pasp_adres" value="' + o.pasp_adres + '" />' +
				'<tr><td><td><span class="l">��� �����:</span><input type="text" id="pasp_ovd" value="' + o.pasp_ovd + '" />' +
				'<tr><td><td><span class="l">����� �����:</span><input type="text" id="pasp_data" value="' + o.pasp_data + '" />' +
				'<tr><td class="label">����� ��������:<td><input type="text" id="nomer" maxlength="6" value="' + o.nomer + '" placeholder="' + o.nomer_next + '" />' +
				'<tr><td class="label">���� ����������:<td><input type="hidden" id="data_create" value="' + o.data_create + '" />' +
				'<tr><td class="label">����� �� ��������:<td><input type="text" id="sum" class="money" maxlength="11" value="' + (o.sum ? o.sum : '') + '" /> ���.' +
				'<tr' + (o.avans_hide && !o.avans_invoice_id ? ' class="dn"' : '') + '>' +
					'<td class="label top">��������� �����:' +
					'<td><input type="hidden" id="avans_check" />' +
						'<div id="avans_div"' + (!o.id ? ' class="dn"' : '') + '>' +
							'<input type="hidden" id="avans_invoice_id" value="' + o.avans_invoice_id + '" />' +
							'<input type="text" id="avans_sum" class="money" maxlength="11" value="' + o.avans_sum + '"' + (o.avans_hide ? ' disabled' : '') + ' /> ���. ' +
						'</div>' +
				'<tr><td colspan="2">' +
					'<a id="preview">��������������� �������� ��������</a>' +
					'<form action="' + AJAX_MAIN + '" method="post" id="preview-form" target="_blank"></form>' +
				'</table>',
			dialog = _dialog({
				width:480,
				top:10,
				head:(o.id ? '��������� ������' : '����������') + ' ��������',
				content:html,
				butSubmit:o.id ? '���������' : '��������� �������',
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
			title0:'���� �� ������',
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
				zayav_id:ZAYAV.id,
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
				dialog.err('�� ������� ��� �������');
				$('#fio').focus();
				return false;
			}
			if(!send.nomer) {
				dialog.err('����������� ������ ����� ��������');
				$('#nomer').focus();
				return false;
			}
			if(!send.sum) {
				dialog.err('����������� ������� ����� �� ��������');
				$('#sum').focus();
				return false;
			}
			if(!send.avans_hide && send.avans && !_cena(send.avans)) {
				dialog.err('����������� ������ ��������� �����');
				$('#avans_sum').focus();
				return false;
			}
			if(!send.avans_hide && send.avans && !send.invoice_id) {
				dialog.err('�� ������ ���� ���������� ������');
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
					_msg('������� ��������');
					document.location.reload();
				} else {
					dialog.abort();
					dialog.err(res.text);
				}
			}, 'json');
		}
	},

	_zayavExpenseEdit = function () {//����� ���� ��� �������������� �������� �� ������ � ���������� � ������
		var html =
			'<table id="zee-tab">' +
				'<tr><td class="label">������:<td><b>' + ZAYAV.name + '</b>' +
				'<tr><td class="label">������ ��������:' +
				'<tr><td id="zee-spisok" colspan="2">' +
			'</table>',
			dialog = _dialog({
				top: 30,
				width: 510,
				head: '��������� �������� ������',
				content: html,
				butSubmit: '���������',
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
				dialog.err('����������� ������� �����');
			else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
//						zayavMoneyUpdate();
						dialog.close();
						_msg('���������');
						$('#_zayav-expense').html(res.html);
					} else
						dialog.abort();
				}, 'json');
			}
		}
	},
	_zayavExpense = function() {//������� �������������� �������� �� ������
		var num = 0;

		for(var n = 0; n < ZAYAV_EXPENSE.length; n++)
			item(ZAYAV_EXPENSE[n])

		item();

		function item(v) {
			if(!v)
				v = [
					0, //0 - id
					0, //1 - ���������
					'',//2 - ��������, id ����������, id ��������, id �����
					'' //3 - �����
				];
			var html =
					'<table id="zee-tab'+ num + '" class="zee-tab" val="' + num + '">' +
						'<tr><td><input type="hidden" id="' + num + 'cat" value="' + v[1] + '" />' +
							'<td class="dop">' +
							'<td class="tdsum">' +
								'<input type="text" class="zee-sum" tabindex="' + (num * 10) + '" value="' + v[3] + '" />���.' +
								'<input type="hidden" class="id" value="' + v[0] + '" />' +
					'</table>';

			$('#zee-spisok').append(html);
			itemDop(v[1], v[2], num);

			var tab = $('#zee-tab' + num);
			$('#' + num + 'cat')._select({
				width:130,
				disabled:0,
				title0:'���������',
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
				dop.html('<input type="text" class="zee-txt" placeholder="�������� �� �������" tabindex="' + (num * 10 - 1) + '" value="' + val + '" />');
				dop.find('input').focus();
			}

			if(ZAYAV_EXPENSE_WORKER[cat_id]) {
				dop.html('<input type="hidden" id="' + num + 'worker" value="' + val + '" />');
				$('#' + num + 'worker')._select({
					width:240,
					disabled:0,
					title0:'���������',
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
					title0:'�������� �� �������',
					spisok:ZAYAV.zp_avai,
					func:function(v) {
						sum.focus();
					}
				});
			}

			if(ZAYAV_EXPENSE_ATTACH[cat_id]) {
				dop.html('<input type="hidden" id="' + num + 'attach" value="' + _num(val) + '" />');
				$('#' + num + 'attach')._attach({
					zayav_id:ZAYAV.id,
					func:function() {
						sum.focus();
					}
				});
				sum.focus();
			}
		}
	},
	_zayavExpenseGet = function() {//��������� �������� ������ �������� ������ ��� ��������������
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
			else if(ZAYAV_EXPENSE_ATTACH[cat_id])
				dop = $('#' + num + 'attach').val();

			send.push(id + ':' +
					  cat_id + ':' +
					  dop + ':' +
					  sum);
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
						'<input type="text" id="item-count" maxlength="4" value="' + o[0][2] + '" /> ��.' +
			'</table>' +
		'</div>';

	t.html(html);
	var list = t.find('#product-list');

	$('#item')._select({
		width:119,
		title0:'�� �������',
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
			title0:'������ �� ������',
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
		$('#find')._search('clear');
		$('#sort')._radio(1);
		$('#desc')._check(0);
		$('#status').rightLink(0);

		ZAYAV.find = '';
		ZAYAV.sort = 1;
		ZAYAV.desc = 0;
		ZAYAV.status = 0;
		_zayavSpisok();
	})
	.on('click', '._zayav-unit', function() {
		var id = $(this).attr('val');
		_scroll('set', 'u' + id);
		location.href = URL + '&p=zayav&d=info&id=' + id;
	})
	.on('click', '#_zayav-add,#_zayav-info #edit', _zayavEdit)

	.on('click', '.day-finish-link', function(e) {//�������� ��������� ��������
		e.stopPropagation();
		var t = $(this),
			save = t.hasClass('no-save') ? 0 : 1;
		if(t.hasClass('_busy'))
			return;
		var dialog = _dialog({
				top:40,
				width:480,
				head:'��������� ��������',
				load:1,
				butSubmit:''
			}),
			send = {
				op:'zayav_day_finish',
				day:$('#day_finish').val(),
				zayav_spisok:$('#zayav').length
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				dialog.content.html(res.html);
			else
				dialog.loadError();
		}, 'json');
		$(document)
			.off('click', '#zayav-finish-calendar td.d:not(.old),#fc-cancel,.fc-old-sel')
			.on('click', '#zayav-finish-calendar td.d:not(.old),#fc-cancel,.fc-old-sel', function() {
				if(t.hasClass('_busy'))
					return;
				dialog.close();
				t.addClass('_busy');
				send = {
					op:'zayav_day_finish_save',
					day:$(this).attr('val'),
					zayav_id:window.ZAYAV ? ZAYAV.id : 0,
					save:save
				};
				$.post(AJAX_MAIN, send, function(res) {
					t.removeClass('_busy');
					if(res.success) {
						t.prev('input').val(send.day);
						t.find('span').html(res.data);
						if($('#zayav').length)
							zayavSpisok(send.day, 'finish');
					}
				}, 'json');
			});
	})
	.on('click', '#zayav-finish-calendar .ch', function() {//��������� ��������� ��������
		if($('#fc-head').hasClass('_busy'))
			return;
		$('#fc-head').addClass('_busy');
		var send = {
			op:'zayav_day_finish_next',
			mon:$(this).attr('val'),
			day:$('#day_finish').val(),
			zayav_spisok:$('#zayav').length
		};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				$('#zayav-finish-calendar').after(res.html).remove();
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
					txt:'������� �����...',
					enter:1,
					func:_zayavSpisok
				})
				.inp(ZAYAV.find);
			$('#sort')._radio(_zayavSpisok);
			$('#desc')._check(_zayavSpisok);
			$('#status').rightLink(_zayavSpisok);
		}
		if($('#_zayav-info').length) {
			$('.a-page').click(function() {
				var t = $(this);
				t.parent().find('.link').removeClass('sel');
				var i = t.addClass('sel').index();
				$('.page:first')[(i ? 'add' : 'remove') + 'Class']('dn');
				$('.page:last')[(!i ? 'add' : 'remove') + 'Class']('dn');
			});
			$('#zayav-action')._dropdown({
				head:'��������',
				nosel:1,
				spisok:[
					{uid:1, title:'������������� ������ ������'},
//					{uid:2, title:'<b>����������� ���������</b>'},
					{uid:20, title:DOG.id ? '�������� ������ ��������' : '��������� �������'},
					{uid:3, title:'������������ ���� �� ������'},
//					{uid:4, title:'�������� ������ ������'},
					{uid:5, title:'���������'},
					{uid:6, title:'<b>������� �����</b>'},
					{uid:7, title:'�������'},
					{uid:8, title:'�������� ������� �� ������'},
					{uid:9, title:'����� �����������'}
				],
				func:function(v) {
					switch(v) {
						case 1: _zayavEdit(); break;
						case 20: _zayavDogovorCreate(); break;
						case 3:
							_schetEdit({
								edit:1,
								client_id:ZAYAV.client_id,
								client:ZAYAV.client_link,
								zayav_id:ZAYAV.id
							});
							break;
						case 5: _accrualAdd(); break;
						case 6: _incomeAdd(); break;
						case 7: _refundAdd(); break;
						case 8: _zayavExpenseEdit(); break;
						case 9: _remindAdd(); break;
					}
				}
			});
		}
	});
