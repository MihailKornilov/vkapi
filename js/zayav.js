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
	_zayavSpisokTovarNameFilter = function(dis) {//������ ���� ������� (������ � �������� ���������� �����)
		if(!$('#tovar_name_id').length)
			return;

		$('#tovar_name_id')._select({
			width:155,
			title0:'�� �������',
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
						'<div class="_info">�������� ��������� ����� ������:</div>' +
						sp +
					'</div>',
			dialog = _dialog({
				top:30,
				width:300,
				padding:20,
				head:'�������� ����� ������',
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
			client_adres = '', //����� ������� ��� ����������� � ������ �����
			equip_js = [],     //������ ������������ ��� select, ������� ���� �� ������� ��� ����������� ������
			equip_tovar_id = 0,//id ������, �� �������� ����� ������������� ������������
			dialog = _dialog({
				width:550,
				top:30,
				class:'zayav-edit',
				head:zayav_id ? '�������������� ������' : '�������� ����� ������' + (service_id ? ' - ' + SERVICE_ACTIVE_ASS[service_id] : ''),
				load:1,
				butSubmit:zayav_id ? '���������' : '������',
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
			$('#ze-about').autosize();

			// 5 - ������
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

			// 4 - ���� �����
			$('#ze-tovar-one').tovar({
				set:0,
				func:equip
			});
			// 11 - ��������� �������
			$('#ze-tovar-several').tovar({set:0,several:1});

			// 6 - �����
			$('#client-adres')._check({
				func:function(v) {
					$('#ze-adres').val(v ? client_adres : '');
				}
			});
			$('#client-adres_check').vkHint({
				msg:'��������� � ������� �������',
				top:-76,
				left:184,
				indent:60,
				delayShow:500
			});
			$('#ze-adres').keyup(function() {
				$('#client-adres')._check(0);
			});

			// 9 - ����
			$('#ze-color')._selectColor(zayav_id ? ZI : {});
			
			// 10 - �����������
			$('#ze-executer_id')._dropdown({
				title0:'�� ��������',
				spisok:_zayavExecuter()
			});
			
			// 12 - ��������������� ������
			$('#tovar-place').zayavTovarPlace();

			// 13 - ����
			$('#ze-srok').zayavSrok({
				service_id:service_id
			});

			// 14 - �������
			$('#ze-note').autosize();
			
			// 16 - ������
			$('#ze-pay_type')._radio({
				light:1,
				spisok:PAY_TYPE
			});

			// 38 - ������ �������
			$('#ze-gn').gnGet({
				func:function() {
//					if($('#summa').attr('readonly')) {
//						$('#summa').val(window.gnGet.summa());
//						$('#skidka-txt').html(window.gnGet.skidka());
//					}
				}
			});

			// 39 - ������
			$('#ze-skidka')._select({
				width:70,
				title0:'���',
				spisok:ZAYAV_SKIDKA_SPISOK
			});

			// 40 - ������� � ����������
			$('#ze-rubric_id')._rubric();
		}

		function equip(v) {//������� ������������ ��� ������ ������
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
		function equipSel() {//����� ����� ������������ ��� ����������
			$(this).remove();
			$('#ze-equip-spisok .vk')
				.removeClass('dn')
				.click(equipAdd);
			$('#equip_id')._select({
				width:177,
				title0:'�������� ��� ������� �����',
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
		function equipGet(sel) {//��������� id ������������. sel - ������ ���, � ������� ����� �������
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
				equip:equipGet(1)                       // 4:v1
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
	_zayavStatus = function() {//��������� ������� ������
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
					'<tr><td class="label">������� ������:' +
						'<td><div class="current" style="background-color:#' + ZAYAV_STATUS_COLOR_ASS[ZI.status_id] + '">' +
								ZAYAV_STATUS_NAME_ASS[ZI.status_id] +
							'</div>' +
				'</table>' +

				'<div id="new-tab">' +
					'<input type="hidden" id="status-new" />' +
					'<table class="bs10">' +
						'<tr><td class="label topi">����� ������:<td>' + spisok +
					'</table>' +
				'</div>' +

				'<div id="zs-tab" class="dn">' +
					'<table class="bs10">' +
						'<tr class="tr-day-fact dn">' +
							'<td class="label">����������� ����:' +
							'<td><input type="hidden" id="day" value="' + ZI.status_day + '" />' +

						'<tr class="tr-executer dn"><td class="label">�����������:<td><input type="hidden" id="zs-executer_id" value="' + ZI.executer_id + '" />' +

						'<tr class="tr-srok dn">' +
							'<td class="label">���� ����������:' +
							'<td><input type="hidden" id="zs-srok" value="' + ZI.srok + '" />' +

						'<tr><td class="label topi">�����������:' +
							'<td><textarea id="zs-comm" placeholder="�� �����������"></textarea>' +

						'<tr class="tr-accrual dn"><td class="label">���������:<td><input type="text" class="money" id="accrual-sum" /> ���.' +

						'<tr class="tr-rem dn"><td class="label">�������� �����������:<td><input type="hidden" id="zs-remind" />' +
						'<tr class="tr-remind"><td class="label">����������:<td><input type="text" id="remind-txt" value="��������� � �������� � ����������" />' +
						'<tr class="tr-remind"><td class="label">����:<td><input type="hidden" id="remind-day" />' +
					'</table>' +
				'</div>' +

			'</div>',

/*
			(ZAYAV_INFO_DEVICE ?
					'<tr><td class="label r topi">��������������� ����������:<td><input type="hidden" id="device-place" value="-1" />'
			: '') +

		if(ZAYAV_INFO_DEVICE)
			zayavPlace();
*/

			dialog = _dialog({
				top:30,
				width:500,
				padding:0,
				head:'��������� ������� ������',
				content:html,
				butSubmit:'',
				submit:submit
			});

		$('#new-tab').slideDown(300);

		$('#zs-executer_id')._dropdown({
			title0:'�� ��������',
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

			dialog.butSubmit('���������');
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
					dialog.err('�� ������� ��������������� ����������');
					$('#place_other').focus();
					return;
				}
			}

*/

			if(ZAYAV_STATUS_DAY_FACT_ASS[send.status_id])
				send.status_day = $('#day').val();

			if(ZAYAV_STATUS_EXECUTER_ASS[send.status_id] && !send.executer_id) {
				dialog.err('�� �������� �����������');
				return;
			}

			if(ZAYAV_STATUS_SROK_ASS[send.status_id]) {
				send.srok = $('#zs-srok').val();
				if(send.srok == '0000-00-00') {
					dialog.err('�� ������ ���� ����������');
					return;
				}
			}

			if(ZAYAV_STATUS_ACCRUAL_ASS[send.status_id])
				if(send.accrual_sum && send.accrual_sum != 0 && !_cena(send.accrual_sum)) {
					dialog.err('����������� ������� ����������');
					$('#accrual-sum').focus();
					return;
				}

			if(ZAYAV_STATUS_REMIND_ASS[send.status_id])
				if(send.remind && !send.remind_txt) {
					dialog.err('�� ������� ���������� �����������');
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
	_zayavTypeChange = function() {//��������� ��������� ������
		var html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">��������� ������:' +
						'<td><input type="hidden" id="service_id" value="' + ZI.service_id + '" />' +
				'</table>',
			dialog = _dialog({
				head:'��������� ��������� ������',
				content:html,
				butSubmit:'���������',
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
	_zayavExecuter = function() {//����������� ������ ������������
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
			if(!send.avans_hide && send.avans && !send.invoice_id) {
				dialog.err('�� ������ ���� ���������� ������');
				return false;
			}
			if(!send.avans_hide && send.invoice_id && !send.avans) {
				dialog.err('����������� ������ ��������� �����');
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
					_msg('������� ��������');
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
					'������� �<b>' + DOG.nomer + '</b>:<br /><br />' +
					'<b class="red">����������� ����������� ��������.</b>' +
				'</div>',
			dialog = _dialog({
				head:'����������� ��������',
				padding:60,
				content:html,
				butSubmit:'���������',
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

	_zayavExpenseEdit = function () {//��������/�������������� ������� �� ������
		var prev = $('#_zayav-expense table'),
			html =
			(prev.length ? '<table id="ze-prev">' + prev.html() + '</table>' : '') +
			'<table class="bs10 w100p">' +
				'<tr><td class="label r top w100">������:<td><b>' + ZI.name + '</b>' +
				'<tr><td class="label r">���������:<td><input type="hidden" id="ze-cat" />' +
				'<tr class="tr-dop dn">' +
					'<td class="label r" id="td-label">' +
					'<td id="td-input">' +

				'<tr class="tr-count dn">' +
					'<td class="label r">����������:' +
					'<td><input type="text" id="ze-count" class="w50" /> ' +
						'<em id="ze-measure"></em>' +
						'<span id="ze-count-max">(max: <b></b>)</span>' +

				'<tr><td class="label r">�����:<td><input type="text" id="ze-sum" class="money" /> ���.' +
			'</table>',
			dialog = _dialog({
				top:30,
				width:490,
				head:'����� ������ ������',
				content:html,
				butCancel:'�������',
				submit:submit,
				cancel:function() {
					$('.inserted').removeClass('inserted');
					$('#ze-cat')._select('remove');
				}
			});

		$('#ze-cat')._select({
				width:200,
				disabled:0,
				title0:'��������� �� �������',
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
				case 1: //��������
					$('#td-input').html('<input type="text" id="ze-dop" class="w250" />');
					$('#ze-dop').focus();
					break;
				case 2: //���������
					$('#ze-dop')._select({
						width:200,
						disabled:0,
						title0:'���������',
						spisok:WORKER_SPISOK,
						func:sumFocus
					});
					break;
				case 5: //�����
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
				case 3: //����� �������
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
							$('#ze-dop').val(avai_id);//id �������

							$('#ze-count')
								.val(1)
								.select()
								.off('keyup')
								.keyup(function() {
									$('#ze-sum').val(_cena(_num($(this).val()) * buy));
								});

							$('#ze-count-max b').html(avai_id ? sp.articul_arr[avai_id].count : 1);
							$('#ze-sum').val(_cena(sp.sum_buy));
						},
						avai:1,
						del:0
					});
					break;
				case 4: //����
					$('#ze-dop')._attach({
						zayav_id:ZI.id,
						func:sumFocus
					});
					break;
				default://���
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
				count:_num($('#ze-count').val()),
				sum:_cena($('#ze-sum').val())
			};
			if(!send.cat_id) {
				dialog.err('�� ������� ���������');
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
						dialog.err('�� ������ �����');
						return;
					}
					if(!send.count) {
						dialog.err('����������� ������� ����������');
						$('#ze-count').focus();
						return;
					}
					break;
				case 3:
					send.dop = _num(send.dop);
					if(!send.dop) {
						dialog.err('�� ������ �����');
						return;
					}
					if(!send.count) {
						dialog.err('����������� ������� ����������');
						$('#ze-count').focus();
						return;
					}
					break;
				case 4: break;
			}

			if(!send.sum) {
				dialog.err('������������ �����');
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

	_zayavKvit = function() {//������������ ���������
		var html = '<table class="zayav-print bs10">' +
				'<tr><td class="label">���� �����:<td>' + KVIT.dtime +
				'<tr><td class="label top">����������:<td>' + KVIT.device +
				'<tr><td class="label">����:<td>' + (KVIT.color ? KVIT.color : '<i>�� ������</i>') +
				'<tr><td class="label">IMEI:<td>' + (ZI.imei ? ZI.imei : '<i>�� ������</i>') +
				'<tr><td class="label">�������� �����:<td>' + (ZI.serial ? ZI.serial : '<i>�� ������</i>') +
				'<tr><td class="label">������������:<td>' + (KVIT.equip ? KVIT.equip : '<i>�� �������</i>') +
				'<tr><td class="label">��������:<td><b>' + ZI.client_link + '</b>' +
				'<tr><td class="label">�������:<td>' + (KVIT.phone ? KVIT.phone : '<i>�� ������</i>') +
				'<tr><td class="label top">�������������:<td><textarea id="defect">' + KVIT.defect + '</textarea>' +
				'<tr><td colspan="2"><a id="preview"><span>��������������� �������� ���������</span></a>' +
				'</table>',
			dialog = _dialog({
				width: 380,
				top: 30,
				head: '������ �' + ZI.nomer + ' - ������������ ���������',
				content: html,
				butSubmit: '��������� ���������',
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
				dialog.err('�� ������� �������������');
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
				dialog.err('�� ������� �������������');
				$('#defect').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg('��������� ���������');
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
				'<tr><td class="label r">���:<td><input type="hidden" id="type_id" value="1" />' +
				'<tr><td class="label r"><b>������ ���������:</b><td><input type="text" id="name" class="w150" />' +
				'<tr><td class="label r">��������:<td><input type="text" id="cost_filling" class="money" /> ���.' +
				'<tr><td class="label r">��������������:<td><input type="text" id="cost_restore" class="money" /> ���.' +
				'<tr><td class="label r">������ ����:<td><input type="text" id="cost_chip" class="money" /> ���.' +
				'</table>',
			dialog = _dialog({
				top:20,
				head:'���������� ������ ���������',
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
				dialog.err('�� ������� ������������');
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
	_zayavCartridgeAdd = function() {//���������� ���������� � ������
		var html =
				'<table class="bs10">' +
					'<tr><td class="label topi w150 r">������ ����������:<td id="crt">' +
				'</table>',
			dialog = _dialog({
				width:470,
				top:30,
				head:'���������� ���������� � ������',
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
				dialog.err('�� ������� �� ������ ���������');
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
				head:'��������� ���������� � ����������',
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

$.fn.zayavTovarPlace = function(o) {//��������������� ������
	var t = $(this),
		spisok = _copySel(ZAYAV_TOVAR_PLACE_SPISOK);

	t.val(-1);

	o = $.extend({
		func:function() {}
	}, o);

	spisok.push({
		uid:0,
		title:'<div id="place-other-div">' +
				'������:<input type="text" id="place_other" class="dn" />' +
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
		zayav_spisok:0,//���� ������ ������
		executer_id:0,
		func:function() {},
		func_executer:function() {} //�������, ������� ����������� ��� ��������� ����������� � ���������
	}, o);

	t.after('<div class="zayav-srok"><a></a></div>');

	var TA = t.next().find('a');
	TA.click(function() {//�������� ���������
		dialog = _dialog({
			top:20,
			width:580,
			head:'��������� ������',
			load:1,
			butSubmit:''
		});
		content = dialog.content;
		calendarUpdate();
	});

	daySet();

	function daySet() {//��������� �������� ��� � ������ ������
		var name = '�� ������';
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
	function calendarUpdate() {//���������� ���������
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
	function calendarFunc() {//�������, ������� ����������� ��� ���������� ���������
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
			title0:'��� ����������',
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
			title0:'�������� �� ������',
			write:1,
			spisok:CARTRIDGE_SPISOK,
			func:add_test,
			funcAdd:function(id) {
				cartridgeNew(id, add_test);
			}
		});
	}
	function add_test(v) {//��������, ��� �� ��������� �������, ����� ��������� ����� ����
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
$.fn.gnGet = function(o) {
	var t = $(this),
		attr_id = t.attr('id');

	if(!attr_id)
		return;

	o = $.extend({
		show:4,     // ���������� �������, ������� ������������ ����������, � ����� ������ �� ��� ���������
		add:8,      // ���������� �������, ������������� � ������
		dop_title0:'',//���. �������� �� ������           ������ �� �������
		dop_spisok:[],
		pn_show:0,  //���������� ����� ������� �����
		category:1, //todo �������
		gns:null,   // ��������� ������ (��� ��������������)
		skidka:0,
		manual:0,   // ����������� �� ������� ��� ����� ����� ����� �������
		func:function() {}
	}, o);

	var pix = 21, // ������ ���� ������ � ��������
		gns_begin = GN_FIRST,
		gns_end = gns_begin + o.show,
		html =
			'<div id="gnGet">' +
				'<table>' +
					'<tr><td><div id="dopLinks">' +
								'<a class="link" val="4">�����</a>' +
								'<a class="link" val="13">3 ������</a>' +
								'<a class="link" val="26">�������</a>' +
								'<a class="link" val="52">���</a>' +
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
		.on('click', '#darr', function() {// �������������� ������
			gns_begin = gns_end;
			gns_end += o.add;
			gnsPrint();
		})
		.on('click', '.gns-week', function() {// �������� �� ������� �� ����� ������
			dopMenuA.removeClass('sel');
			var th = $(this),
				sel = !th.hasClass('gnsel'),
				v = th.attr('val');
			th[(sel ? 'add': 'remove') + 'Class']('gnsel');
			th.removeClass('prev');
			GN_ASS[v].prev = 0;
			GN_ASS[v].sel = sel;
			GN_ASS[v].dop = 0;
			GN_ASS[v].pn = 0;
			if(o.dop_spisok.length) {
				if(o.pn_show)
					$('#pn' + v).val(0)._dropdown('remove');
				$('#vdop' + v).val(0)._dropdown(!sel ? 'remove' : {
					title0:o.dop_title0,
					spisok:o.dop_spisok,
					func:function(id) {
						GN_ASS[v].dop = id;
						cenaSet();
						o.func();
						if(o.pn_show) {
							GN_ASS[v].pn = 0;
							$('#pn' + v).val(0)._dropdown('remove');
							if(POLOSA_NUM[id]) {
								var pc = [];
								for(n = 2; n < GN_ASS[v].pc; n++)
									pc.push({uid:n,title:n + '-�'});
								$('#pn' + v)._dropdown({
									title0:'??',
									spisok:pc,
									func:function(pn) {
										GN_ASS[v].pn = pn;
									}
								});
							}
						}
					}
				});
			}
			gnsCount();
			cenaSet();
			o.func();
		});

	var gnGet = $('#gnGet'),                 // �������� �����
		gns = gnGet.find('#gns'),            // ������ �������
		dopMenuA = gnGet.find('#dopLinks a'),// ������ ���� � ���������
		dopDef = gnGet.find("#dopDef"),      // ����� �������������� ���������� �� ���������
		selCount = gnGet.find('#selCount'),  // ���������� ��������� �������
		cena = 0,   // ���� �� ���� �����
		summa_manual = 0,
		skidka_sum = 0;

	gnsClear();
	if(o.gns) {// ��������� ��������� ������� ��� ��������������
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
		gnsCount();
	} else
		gnsPrint();
	dopMenu();

	dopMenuA.click(function() {// ����� ������� �� �����, 3 ������, ������� � ��� ������� �������
		var t = $(this),
			v = t.attr('val') * 1;
		gnsClear();
		if(t.hasClass('sel')) {
			v = 0;
			t.removeClass('sel');
		} else {
			dopMenuA.removeClass('sel');
			t.addClass('sel');
			n = GN_FIRST;
			var c = v;
			while(c) {
				if(n > GN_LAST)
					break;
				if(!GN_ASS[n])
					continue;
				GN_ASS[n].sel = 1;
				c--;
				n++;
			}
		}
		gnsCount();
		gnsPrint(1, v);
		o.func();
	});
	function gnsPrint(first, count) {// ����� ������ �������
		if(first) {// ������ ������� ��������� � ������ ������, � �� �����������
			gns_begin = GN_FIRST;
			gns_end = gns_begin + (count || 0) + o.show;
		}
		gnGet.find('#darr').remove();
		var html = '';
		for(n = gns_begin; n < gns_end; n++) {
			if(n > GN_LAST)
				break;
			var sp = GN_ASS[n];
			if(!sp) { // ���� ����� ��������, ����� �� ���������
				//end++;
				continue;
			}
			html +=
				'<table><tr>' +
					'<td><table class="gns-week' + (sp.sel ? ' gnsel' : '') + (sp.prev ? ' prev' : '') + '" val="' + n + '">' +
							'<tr><td class="td"><b>' + sp.week + '</b><span class="g">(' + n + ')</span>' +
								'<td class="td"><span class="g">�����</span> ' + sp.txt +
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
		if(first && o.dop_spisok.length)
			gnsActionActive(function(sp) {
				$('#vdop' + sp.n)._dropdown({
					title0:o.dop_title0,
					spisok:o.dop_spisok,
					func:function(v) {
						GN_ASS[sp.n].dop = v;
						cenaSet();
						o.func();
						if(o.pn_show) {
							GN_ASS[sp.n].pn = 0;
							$('#pn' + sp.n).val(0)._dropdown('remove');
							if(POLOSA_NUM[v]) {
								var pc = [];
								for(n = 2; n < GN_ASS[sp.n].pc; n++)
									pc.push({uid:n,title:n + '-�'});
								$('#pn' + sp.n)._dropdown({
									title0:'??',
									spisok:pc,
									func:function(pn) {
										GN_ASS[sp.n].pn = pn;
									}
								});
							}
						}
					}
				});
				if(o.pn_show && POLOSA_NUM[sp.dop]) {
					var pc = [];
					for(var n = 2; n < GN_ASS[sp.n].pc; n++)
						pc.push({uid:n,title:n + '-�'});
					$('#pn' + sp.n)._dropdown({
						title0:'??',
						spisok:pc,
						func:function(pn) {
							GN_ASS[sp.n].pn = pn;
						}
					});
				}
			});
		cenaSet();
	}
	function dopMenu() {
		dopDef._dropdown(!o.dop_spisok.length ? 'remove' : {
			head:'����...',
			headgrey:1,
			title0:o.dop_title0,
			nosel:1,
			spisok:o.dop_spisok,
			func:function(id) {
				gnsActionActive(function(sp) {
					if(!sp.prev) {
						$('#vdop' + sp.n)._dropdown(id);
						sp.dop = id;
						if(o.pn_show) {
							sp.pn = 0;
							if(POLOSA_NUM[id]) {
								var pc = [];
								for(var n = 2; n < GN_ASS[sp.n].pc; n++)
									pc.push({uid:n,title:n + '-�'});
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
				});
				cenaSet();
				o.func();
			}
		});
	}
	function gnsActionActive(func, all) {// ���������� �������� � ��������� �������
		for(var n = GN_FIRST; n <= GN_LAST; n++) {
			var sp = GN_ASS[n];
			if(!sp)
				continue; // E��� ����� ��������, ����� ��� ��������
			if(all || sp.sel)
				func(sp, n);
		}
	}
	function gnsCount() {// ����� ���������� ��������� �������
		var count = 0;
		gnsActionActive(function() {
			count++;
		});
		if(count) {
			var html = '������' + _end(count, ['', '�']) + ' ' +
						count + ' �����' + _end(count, ['', 'a', '��']) +
						'<a>��������</a>';
			selCount
				.html(html)
				.find('a').click(function() {
					gnsClear();
					gnsPrint(1);
					selCount.html('');
					dopMenuA.removeClass('sel');
					o.func();
				});
		} else
			selCount.html('');
	}
	function gnsClear() {// ������� ��������� �������
		gnsActionActive(function(sp, n) {
			sp.n = n;
			sp.sel = 0;
			sp.prev = 0;
			sp.cena = 0;
			sp.dop = 0;
			sp.pn = 0;
		}, 1);
	}
	function cenaSet() {// ��������� ���� � ��������� ������
		var sum = 0,
			count = 0;
		switch(o.category) {
			case 1:
				var four = 0;
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
							sp.cena = cena ? cena + (sp.dop ? OBDOP_CENA_ASS[sp.dop] : 0) : 0;
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
	function summaGet() {
		var sum = 0;
		gnsActionActive(function(sp) {
			if(!sp.prev)
				sum += sp.cena;
		});
		return Math.round(sum * 100) / 100;
	}
	t.cena = function(c) {
		cena = c || 0;
		cenaSet();
	};
	t.skidka = function(v) {
		if(v != undefined) {
			o.skidka = v;
			cenaSet();
			return '';
		}
		return o.category == 2 && skidka_sum ? '����� ������: <b>' + skidka_sum + '</b> ���.' : '';
	};
	t.summa = summaGet;
	t.clear = function(v) {
		o.category = v;
		o.manual = 0;
		o.skidka = 0;
		skidka_sum = 0;
		summa_manual = 0;
		dopMenuA.removeClass('sel');
		gnsClear();
		gnsPrint(1);
		dopMenu();
	};
	t.manual = function(v, sum) {
		o.manual = v;
		summa_manual = summaGet();
		cenaSet();
		o.func();
	};
	t.manualSumma = function(sum) {
		summa_manual = REGEXP_CENA.test(sum) ? sum.replace(',', '.') * 1 : 0;
		cenaSet();
	};
	t.result = function() {
		var spisok = [],
			no_polosa = 0; // ��������, ��� �� ������ �������
		gnsActionActive(function(sp) {
			if(o.category == 2 && !sp.dop)
				no_polosa = 1;
			spisok.push(sp.n + ':' + sp.cena + ':' + sp.dop + ':' + sp.pn);
		});
		return no_polosa ? 'no_polosa' : spisok.join();
	};
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
					'<tr><td class="label r w175">������� ���������������:<td><b>' + _toAss(ZAYAV_TOVAR_PLACE_SPISOK)[ZI.place_id] + '</b>' +
					'<tr><td class="label r topi">����� ���������������:<td><input type="hidden" id="tovar-place" />' +
				'</table>',

			dialog = _dialog({
				width:420,
				head:'��������� ��������������� ������',
				content:html,
				butSubmit:'���������',
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
				  .html('��������');
			}
		}, 'json');
	})
	.on('click', '#_zayav-info .set', function() {//���������� ������ � ������� �� ������
		var tovar_id = _parent($(this), '.unit').attr('val'),
			dialog = _dialog({
				top:100,
				width:420,
				head:'���������� ������ � �������',
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
						dialog.butSubmit('���������');
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
			head:'������� ������',
			op:'zayav_expense_del',
			func:function(res) {
				$('#_zayav-expense').html(res.html);
			}
		});
	})

	.on('click', '#zayav-cartridge .cart-edit', function() {//���������� �������� ���� ����������
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
					'<tr><td class="label w100 r">��������:<td><input type="hidden" id="cart_id" value="' + cart_id + '" />' +
					'<tr><td class="label topi r">��������:' +
						'<td><input type="hidden" id="filling" value="' + filling + '" />' +
							'<input type="hidden" id="restore" value="' + restore + '" />' +
							'<input type="hidden" id="chip" value="' + chip + '" />' +
					'<tr><td class="label r">��������� �����:<td><input type="text" class="money" id="cost" value="' + cost + '" /> ���.' +
					'<tr><td class="label r">����������:<td><input type="text" id="prim" class="w250" value="' + prim + '" />' +
				'</table>',
			dialog = _dialog({
				width:430,
				top:30,
				head:'�������� �� ���������',
				content:html,
				butSubmit:'���������',
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
			name:'��������',
			func:costSet
		});
		$('#restore')._check({
			block:1,
			mt:4,
			name:'��������������',
			func:costSet
		});
		$('#chip')._check({
			block:1,
			mt:4,
			name:'������ ����',
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
				dialog.err('�� ������ ��������');
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
			head:'���������',
			op:'zayav_cartridge_del',
			func:function() {
				t.remove();
			}
		});
	})

	.on('click', '#zayav-report .cols-div a', function() {//�������� � ������� ��������� ������� ������ �� �������
		var t = _parent($(this), '.cols-div'),
			show = t.hasClass('show');
		t.addClass('show');
		$(document).on('click.zr-cols-show', function() {
			t.removeClass('show');
			$(document).off('click.zr-cols-show');
		})
	})
	.on('click', '#zayav-report .cols-div ._check', function(e) {//�����������, ������� ������� ������ �� �������
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
					txt:'������� �����...',
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
			WORKER_SPISOK.push({uid: -1, title: '�� ��������', content: '<b>�� ��������</b>'});
			$('#executer_id')._select({
				width: 155,
				title0: '�� ������',
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
				title0:'����� ���������������',
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

			name.push('������������� ������ ������'); action.push(_zayavEdit);

			if(SERVICE_ACTIVE_COUNT > 1) {
				name.push('�������� ��������� ������');
				action.push(_zayavTypeChange);
			}
			if(ZI.pole[23]) {
				name.push('�������� ���������');
				action.push(_zayavCartridgeAdd);
			}
			if(ZI.pole[20]) {
				name.push('<b>����������� ���������</b>');
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
				name.push(DOG.id ? '�������� ������ ��������' : '��������� �������');
				action.push(_zayavDogovorCreate);
				if(DOG.id) {
					name.push('����������� �������');
					action.push(_zayavDogovorTerminate);
				}
			}
			if(ZI.pole[21]) {
				name.push('������������ ���� �� ������');
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

			name.push('�������� ������ ������');        action.push(_zayavStatus);
			name.push('���������');                     action.push(_accrualAdd);
			name.push('<b>������� �����</b>');         action.push(_incomeAdd);
			name.push('�������');                       action.push(_refundAdd);
			name.push('�������� ������ �� ������');     action.push(_zayavExpenseEdit);
			name.push('����� �����������');             action.push(_remindAdd);

			if(ZI.todel) {
				name.push('<tt>������� ������</tt>');
				action.push(function() {
					_dialogDel({
						id:ZI.id,
						head:'������',
						info:'<u>������ ����� �������, ���� �����������:</u><br />' +
							  '- �������;<br />' +
							  '- ��������;<br />' +
							  '- ���������� �/� �����������;<br />' +
							  '- ����������� ��������;<br />' +
							  '- �������������� ����� �� ������.',
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
					head:'��������',
					nosel:1,
					spisok:spisok,
					func:function(v) {
						action[v]();
					}
				});
			$('#executer_id')._dropdown({
				title0:'�� ��������',
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
							_msg('����������� ������');
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
				title:'���������� ��������',
				icon:1,
				zayav_id:ZI.id,
				zayav_save:1
			});
			$('#attach1_id')._attach({
				title:'���������� ��������',
				icon:1,
				zayav_id:ZI.id,
				zayav_save:2
			});
			$('#attach_cancel,#attach1_cancel').click(function() {//������ ������������ ���������
				var t = $(this),
					html =
						'<div class="_info">' +
							'������������ ��������� ����� �������� ��� ��������������. ' +
							'��� ������ ������, ���� ����������� ������� <u>�������� �� ���������</u>, ������ ������ ���������� �� �����.' +
						'</div>' +
						'<center class="mar8"><b>��������� ��������������<br />������������ ���������.</br></center>',
					dialog = _dialog({
						width:370,
						head:'�������������� ������������ ���������',
						content:html,
						butSubmit:'���������',
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
