var _zayavReady = function() {//�������� �� ������� ������ ���������
		$('#find')
			._search({
				width:178,
				focus:1,
				txt:'������� �����...',
				enter:1,
				v:ZAYAV.find,
				func:_zayavSpisok
			});
		$('#sort')._radio(_zayavSpisok);
		$('#desc')._check(_zayavSpisok);
		$('#ob_onpay')._check(_zayavSpisok);
//		$('#status_').rightLink(_zayavSpisok);

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
		$('#f56')._check(_zayavSpisok);
		$('#f57')._check(_zayavSpisok);
		$('#f59')._check(_zayavSpisok);
		$('#f60')._check(_zayavSpisok);
		WORKER_SPISOK.push({uid:-1, title:'�� ��������', content:'<b>�� ��������</b>'});
		$('#executer_id')._select({
			width:180,
			title0:'�� ������',
			spisok:WORKER_SPISOK,
			func:function(v, id) {
				$('#finish').zayavSrok('executer_id', v);
				_zayavSpisok(v, id);
			}
		});

		if($('#tovar_cat_id').length)
			$('#tovar_cat_id')._select({
				width:180,
				write:1,
				title0:'��� ���������',
				spisok:ZAYAV_TOVAR_CAT,
				func:_zayavSpisok
			});

		if($('#tovar_id').length)
			$('#tovar_id').tovar({
				title:'�������',
				small:1,
				zayav_use:1,
				func:_zayavSpisok
			});

		$('#tovar_place_id')._select({
			width:180,
			title0:'����� ���������������',
			spisok:ZAYAV_TOVAR_PLACE_SPISOK,
			func:_zayavSpisok
		});

		$('#gn_year')._yearLeaf({func:_zayavSpisok});
		/*			$('#gn_nomer_id')._select({
		 width:155,
		 spisok:ZAYAV_GN_YEAR_SPISOK,
		 func:_zayavSpisok
		 });
		 */
		if($('#gn_nomer_id').length)
			$('#gn_nomer_id')._radio({
				right:0,
				light:1,
				spisok:ZAYAV_GN_YEAR_SPISOK,
				func:_zayavSpisok
			});
		_zayavPolosa();
		$('#gn_polosa_color')._radio(_zayavSpisok);

		$('#deleted')._check(_zayavSpisok);
		$('#deleted_only')._check(_zayavSpisok);

		_zayavPolosaNomerDropdown();
		_zayavObWordNomer();
		_nextCallback = _zayavPolosaNomerDropdown;

	},
	_zayavSpisok = function(v, id) {
		ZAYAV.op = 'zayav' + ZAYAV.service_id + '_spisok';
		_filterSpisok(ZAYAV, v, id);
		ZAYAV.op = 'zayav_spisok';
		$('.filter-after-unit-check')._dn(!ZAYAV.f60);
		$('.nofind')[(ZAYAV.find ? 'add' : 'remove') + 'Class']('dn');
		$('#deleted-only-div')[(ZAYAV.deleted ? 'remove' : 'add') + 'Class']('dn');
		$.post(AJAX_MAIN, ZAYAV, function(res) {
			if(res.success) {
				$('.result').html(res.all);
				$('#spisok').html(res.spisok);
				if(id == 'gn_year')
					$('#gn_nomer_id')._radio('spisokUpdate', res.gn_year_spisok);
				_zayavObWordNomer();
				//�����/������� ��������� ������
				if($('#gn_polosa').length && GN_ASS[ZAYAV.gn_nomer_id])
					$('#gn_polosa_color_filter')[ZAYAV.gn_polosa > 1 && ZAYAV.gn_polosa < GN_ASS[ZAYAV.gn_nomer_id].pc ? 'show' : 'hide']();
				_zayavPolosaNomerDropdown();
			}
		}, 'json');
	},
	_zayavObWordNomer = function() {//����������� ���������� ������ ������ � ���������
		if(!$('#obWordPrint').length)
			return;
		var gn = GN_ASS[ZAYAV.gn_nomer_id];
		if(!gn)
			return;
		$('#obWordPrint .ttmsg').html('����� ' + gn.week + '(' + gn.gen + ')');

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
			zayav_param = ZAYAV_POLE_PARAM[service_id] || {},//�������������� ��������� ����� ������
			client_adres = '', //����� ������� ��� ����������� � ������ �����
			dialog = _dialog({
				width:700,
				top:20,
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

		dialog.load(send, zLoaded);

		function zLoaded() {
			$('#ze-about')
				.autosize()
				.keyup(_zayavObCalc);

			// 5 - ������
			if(!$('#ze-client-name').length)
				$('#ze-client_id').clientSel({
					width:300,
					add:1,
					func:function(uid, id, item) {
						client_adres = uid ? item.adres : '';
						if(_num($('#client-adres').val()))
							$('#ze-adres').val(client_adres);
						if($('#ze-skidka').length) {
							$('#ze-skidka')._select(item.skidka);
							$('#ze-gn').gnGet('skidka', item.skidka);
						}
					}
				});

			// 4 - ���� �����
			var zp4 = zayav_param[4];
			$('#ze-tovar-one').tovar({
				add:1,
				equip:zp4 && zp4[0] ? zayav_id ? ZI.equip_ids : 0 : false
			});
			// 11 - ��������� �������
			$('#ze-tovar-several').tovar({several:1});

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
			
			// 15 - ���������
			$('#ze-sum_cost_manual')._check(function(v) {//�������� ��������� ������� ��� ���
				$('#ze-sum_cost')
					.attr('readonly', !v)
					.focus();
				var sum = _cena($('#ze-sum_cost').val());
				$('#ze-gn').gnGet('manual', v);
				$('#ze-gn').gnGet('summa', sum);
				$('#ze-gn').gnGet('update');
			});
			$('#ze-sum_cost').keyup(function(v) {
				$('#ze-gn').gnGet('summa', _cena($(this).val()));
				$('#ze-gn').gnGet('update');
			});

			// 16 - ������
			$('#ze-pay_type')._radio({
				light:1,
				spisok:PAY_TYPE
			});

			// 36 - ������ ����� ���������� �������
			$('#ze-size_x,#ze-size_y').keyup(function() {
				var t = $(this),
					x = _size($('#ze-size_x').val()),
					y = _size($('#ze-size_y').val()),
					kv_sm = $('#ze-kv_sm'),
					xy = Math.round(x * y);

				t[(_size(t.val()) ? 'remove' : 'add') + 'Class']('err');

				if(!xy) {
					kv_sm.val('');
					return;
				}

				kv_sm.val(xy);

				if(!$('#ze-gn').length)
					return;

				$('#ze-gn').gnGet('cena', xy);
			});

			// 38 - ������ �������
			var zp38 = zayav_param[38],
				manual = $('#ze-sum_cost_manual').length;
			$('#ze-gn').gnGet({
				gns:zayav_id ? ZI.gns : {},
				dop_title0:zp38 ? (
							zp38[0] ? '���. �������� �� ������' :
							zp38[1] ? '������ �� �������' : ''
						  ) : '',
				dop_spisok:zp38 ? (
							zp38[0] ? GAZETA_OBDOP_SPISOK :
							zp38[1] ? GAZETA_POLOSA_SPISOK : []
						  ) : [],
				four_free:zp38 ? zp38[0] : 0,
				pn_show:zp38 ? zp38[1] : 0,
				manual:manual ? _num($('#ze-sum_cost_manual').val()) : 1,
				summa:_cena($('#ze-sum_cost').val()),
				skidka:_num($('#ze-skidka').val()),
				func:function(v) {
					$('#ze-skidka_sum').html('');
					if(_num(manual ? $('#ze-sum_cost_manual').val() : 1))
						return;
					$('#ze-sum_cost').val(v.summa);
					$('#ze-skidka_sum').html(v.skidka_sum ? '����� ������: ' + v.skidka_sum + ' ���.' : '');
				}
			});
			_zayavObCalc();
			$('#ze-size_x').trigger('keyup');

			// 39 - ������
			$('#ze-skidka')._select({
				width:70,
				title0:'���',
				spisok:SKIDKA_SPISOK,
				func:function(v) {
					$('#ze-gn').gnGet('skidka', v);
				}
			});

			// 40 - ������� � ����������
			$('#ze-rubric_id')._rubric();
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
				rubric_id:$('#ze-rubric_id').val(),     // 40
				rubric_id_sub:$('#ze-rubric_id_sub').val(),// 40
				size_x:$('#ze-size_x').val(),           // 31
				size_y:$('#ze-size_y').val(),           // 31
				place_id:$('#tovar-place').val(),          // 12
				place_other:$('#tovar-place').attr('val'), // 12
				srok:$('#ze-srok').val(),               // 13
				note:$('#ze-note').val(),               // 14
				skidka:$('#ze-skidka').val(),           // 39
				sum_manual:_bool($('#ze-sum_cost_manual').val()),// 15:v1
				sum_cost:$('#ze-sum_cost').val(),       // 15
				pay_type:$('#ze-pay_type').val(),       // 16
				tovar_equip_ids:$('#ze-tovar-one').tovar('equip_ids_sel'),// 4:v1
				gn:$('#ze-gn').val()                    // 38
			};
			dialog.post(send, function(res) {
				_scroll('set', 'u' + res.id);
				location.href = URL + '&p=45&id=' + res.id;
			});
		}
	},
	_zayavObCalc = function() {// ���������� ��������� ����������
		var CALC = $('#ze-about-calc');
		if(!CALC.length || !$('#ze-gn').length || !$('#ze-about').length)
			return;

		var txt_sum = 0, // ����� ������ �� �����
			podr_about = '', // ��������� ������������ ����� ����������
			txt = $('#ze-about').val()
					.replace(/\./g, '')    // �����
					.replace(/,/g, '')     // �������
					.replace(/\//g, '')    // ���� /
					.replace(/\"/g, '')    // ������� �������
					.replace(/( +)/g, ' ') // ������ �������
					.replace( /^\s+/g, '') // ������� � ������
					.replace( /\s+$/g, '');// ������� � �����

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
			var html = '�����: <b>' + txt.length + '</b>' + podr_about + '<br />' +
					   '����: <b>' + txt_sum + '</b> ���.<span>(��� ����� ���. ����������)</span>';
			CALC.html(html);
		}
		$('#ze-gn').gnGet('cena', txt_sum);
	},
	_zayavPolosa = function() {
		if(!$('#gn_polosa').length)
			return;
		var gn = GN_ASS[ZAYAV.gn_nomer_id],
			pc = [{uid:1,title:'������'}];
		if(gn) {
			for(var n = 2; n < gn.pc; n++)
				pc.push({uid:n, title:n + '-�'});
			pc.push({uid:102, title:'��������� ' + n + '-�'});
		}
		pc.push({uid:103,title:'���������� �����-�����'});
		pc.push({uid:104,title:'���������� �������'});
		pc.push({uid:105,title:'���������� (����� �� ������)'});
		$('#gn_polosa')._select({
			title0:'����� ������',
			spisok:pc,
			func:_zayavSpisok
		});
	},
	_zayavPolosaNomerDropdown = function() {
		var pnc = $('.zayav-polosa-nomer'),
			pc = [],
			n;

		if(!pnc.length)
			return;

		for(n = 2; n < GN_ASS[ZAYAV.gn_nomer_id].pc; n++)
			pc.push({uid:n,title:n + '-�'});

		for(n = 0; n < pnc.length; n++) {
			var sp = pnc.eq(n);

			$('#' + sp.attr('id'))._dropdown({
				title0:'??',
				spisok:pc,
				func:function(v, attr_id) {
					var send = {
						op:'zayav_gn_polosa_nomer_change',
						zgn_id:_num($('#' + attr_id).attr('val')),
						polosa:v
					};
					$.post(AJAX_MAIN, send, function(res) {
						if(res.success)
							_msg();
					}, 'json');
				}
			});
			sp.removeClass('zayav-polosa-nomer');
		}
	},
	_zayavStatusSpisok = function(zi) {//������ ��������
		var spisok = '';
		for(var i = 0; i < ZAYAV_STATUS_NAME_SPISOK.length; i++) {
			var sp = ZAYAV_STATUS_NAME_SPISOK[i];
			if(zi && sp.uid == zi.status_id)
				continue;
			if(ZAYAV_STATUS_NOUSE_ASS[sp.uid])
				continue;
			if(zi && ZAYAV_STATUS_NEXT[zi.status_id] && !ZAYAV_STATUS_NEXT[zi.status_id][sp.uid])
				continue;
			spisok += '<div class="sp curP pad10 bor-e8 fs14 blue mb5" val="' + sp.uid + '" style="background-color:#' + ZAYAV_STATUS_COLOR_ASS[sp.uid] + '">' +
						 sp.title +
						'<div class="about fs11 mt5 grey">' + ZAYAV_STATUS_ABOUT_ASS[sp.uid] + '</div>' +
					  '</div>'
		}
		return spisok;
	},
	_zayavStatus = function() {//��������� ������� ������

		var html =
			'<div id="zayav-status">' +
				'<table class="bs10 w100p">' +
					'<tr><td class="label">������� ������:' +
						'<td><div class="pad10 bor-e8 fs14 blue" style="background-color:#' + ZAYAV_STATUS_COLOR_ASS[ZI.status_id] + '">' +
								ZAYAV_STATUS_NAME_ASS[ZI.status_id] +
							'</div>' +
				'</table>' +

				'<div id="new-tab">' +
					'<input type="hidden" id="status-new" />' +
					'<table class="bs10 w100p">' +
						'<tr><td class="label topi">����� ������:<td>' + _zayavStatusSpisok(ZI) +
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
		$('#remind-day')._calendar({tomorrow:1});
		$('.sp').click(function() {
			var t = $(this),
				v = t.attr('val');

			$('#status-new').val(v);

			t.removeClass('sp curP');
			t.find('div').slideUp(300);
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
	
	_zayavGo = function(zayav_id) {//������� �� ������ ��� href
		location.href = URL + '&p=45&id=' + zayav_id;
	},

	_zayavOnpayPublic = function() {
		var html =
				'<div>' +
					'������ ��� ��������� ���������� ����������, ��������� ��������� ���������:<br /><br />' +
					'1. ���������� �� ������ ��������� ������.<br />' +
					'2. ���������� ������ ��������� ������� ����������.<br />' +
					'3. ������ ���� �������� ����� �� Onpay.' +
					'' +
				'</div>',
			dialog = _dialog({
				head:'�������� ��������-����������',
				content:html,
				butSubmit:'��������� ����������',
				submit:submit
			});

		function submit() {
			var send = {
				op:'zayav_onpay_public',
				zayav_id:ZI.id
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg('���������� ���������');
					location.reload();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	_zayavOnpayPublicNo = function() {
		var html =
				'<div>' +
					'���������� �� ����� �������.' +
					'<br />' +
					'��� �� ������ �� � ���� �� ������� ������, ���� ���������� �� ����� ���������.' +
				'</div>',
			dialog = _dialog({
				head:'�������� ��������-����������',
				content:html,
				butSubmit:'��������� ����������',
				submit:submit
			});

		function submit() {
			var send = {
				op:'zayav_onpay_public_no',
				zayav_id:ZI.id
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

	_zayavDogovor2 = function() {//������� ��� �������� �����
		DOG.template_id = 2;
		_zayavDogovorCreate();
	},
	_zayavDogovor1 = function() {//������� ��� ������� ������
		DOG.template_id = 1;
		_zayavDogovorCreate();
	},
	_zayavDogovorCreate = function() {
		var o = $.extend({
			id:0,
			template_id:255,
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

		var html = '<table id="_zayav-dog-tab" class="bs10 w100p">' +
				'<tr><td class="label r w150">�.�.�. �������:<td><input type="text" id="fio" class="w300" value="' + o.fio + '" />' +
				'<tr><td class="label r">�����:<td><input type="text" id="adres" value="' + o.adres + '" class="w300" />' +
				'<tr><td class="label r topi">�������:' +
					'<td class="color-555">&nbsp;�����: <input type="text" id="pasp_seria" class="w50 mr20" maxlength="8" value="' + o.pasp_seria + '" />' +
						'�����: <input type="text" id="pasp_nomer" class="w100" maxlength="10" value="' + o.pasp_nomer + '" />' +
						'<table class="bs5">' +
							'<tr><td>��������:<td><input type="text" id="pasp_adres" class="w200" value="' + o.pasp_adres + '" />' +
							'<tr><td>��� �����:<td><input type="text" id="pasp_ovd" class="w200" value="' + o.pasp_ovd + '" />' +
							'<tr><td>����� �����:<td><input type="text" id="pasp_data" class="w200" value="' + o.pasp_data + '" />' +
						'</table>' +
				'<tr><td class="label r">����� ��������:' +
					'<td><input type="text" id="nomer" class="w50" value="' + o.nomer + '" placeholder="' + o.nomer_next + '" />' +
				'<tr><td class="label r">���� ����������:' +
					'<td><input type="hidden" id="data_create" value="' + o.data_create + '" />' +
				'<tr><td class="label r">����� �� ��������:<td><input type="text" id="sum" class="money" maxlength="11" value="' + (o.sum ? o.sum : '') + '" /> ���.' +
				'<tr' + (o.avans_hide && !o.avans_invoice_id ? ' class="dn"' : '') + '>' +
					'<td class="label r topi">��������� �����:' +
					'<td><input type="hidden" id="avans_check" />' +
						'<div id="avans_div"' + (!o.id ? ' class="dn"' : '') + '>' +
							'<input type="hidden" id="invoice_id-add" value="' + o.avans_invoice_id + '" />' +
							'<div class="tr_confirm mt5 dn"><input type="hidden" id="confirm" /></div>' +
							'<input type="text" id="avans_sum" class="money mt10" value="' + o.avans_sum + '"' + (o.avans_hide ? ' disabled' : '') + ' /> ���. ' +
						'</div>' +
				'</table>' +

			(APP_ID != 6044422 ?
				'<div id="preview" class="center pad10 mt10 over1 curP color-555">��������������� �������� ��������</div>' +
				'<form action="' + AJAX_MAIN + '" method="post" id="preview-form" target="_blank"></form>'
			: ''),

			dialog = _dialog({
				width:550,
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
					$('#invoice_id-add')._check(_invoiceIncomeInsert(1));
				}
			});

		incomeConfirmCheck(o.avans_hide);
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
				template_id:o.template_id,
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
				invoice_id:_num($('#invoice_id-add').val()),
				confirm:_bool($('#confirm').val()),
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
			dialog.post(send, 'reload');
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

				'<tr><td><td>' +
				'<tr id="tr-expense-dub" class="dn">' +
					'<td class="label">' +
					'<td><input type="hidden" id="ze-expense-dub" />' +
				'<tr id="tr-expense-cat" class="dn">' +
					'<td class="label r topi">��������� ������� �����������:' +
					'<td><input type="hidden" id="ze-cat-id-add" />' +
						'<input type="hidden" id="category_sub_id-add" />' +
				'<tr id="tr-invoice" class="dn">' +
					'<td class="label r topi">��������� ����:' +
					'<td><input type="hidden" id="invoice_id" />' +
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
		$('#ze-expense-dub')._check({
			name:'�������������� � �������� �����������',
			light:1,
			func:function(v) {
				$('#tr-expense-cat')[(v ? 'remove' : 'add') + 'Class']('dn');
				$('#tr-invoice')[(v ? 'remove' : 'add') + 'Class']('dn');
				if(!v)
					return;
				
				var cat_id = _num($('#ze-cat').val()),
					sp = ZE_DUB_ASS[cat_id].split('_'),
					id = _num(sp[0]),
					id_sub = _num(sp[1]);
				
				$('#ze-cat-id-add').val(id)._select({
					width:258,
					bottom:5,
					title0:'�� �������',
					spisok:_copySel(EXPENSE_SPISOK, 1),
					func:function(v, id) {
						_expenseSub(v, 0, '-add');
					}
				});
				_expenseSub(id, id_sub, '-add');

				$('#invoice_id')._select({
					width:270,
					title0:'�� ������',
					spisok:_invoiceExpenseInsert()
				});
			}
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
			$('#tr-expense-dub')[(ZE_DUB_ASS[id] ? 'remove' : 'add') + 'Class']('dn');
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
					$('#ze-dop').tovar({
						open:1,
						add:1,
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
					$('#ze-dop').tovar({
						open:1,
						avai:1,
						tovar_id_use:ZI.tovar_id,
						func:function(v, attr_id, sp) {
							$('#ze-dop').val(sp.avai_id);               //��������� id �������
							$('.tr-count')[(v ? 'remove' : 'add') + 'Class']('dn');//����� input-����������
							$('#ze-sum').val('');

							if(!sp.avai_id)
								return;

							$('#ze-count-max b').html(sp.avai_count);   //��������� ������������� ���������� � �������
							$('#ze-measure').html(sp.measure);          //��������� ��.���������
							$('#ze-count')
								.val(1)
								.select()
								.keyup(function() {
									$('#ze-sum').val(Math.round(_ms($(this).val()) * sp.avai_buy));
								});
							$('#ze-count').trigger('keyup');
						}
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
				count:_ms($('#ze-count').val()),
				sum:_cena($('#ze-sum').val()),
				expense_dub:$('#ze-expense-dub').val(),
				expense_cat_id:$('#ze-cat-id-add').val(),
				expense_cat_id_sub:$('#category_sub_id-add').val(),
				invoice_id:$('#invoice_id').val()
			};
			dialog.post(send, function(res) {
				$('#_zayav-expense').html(res.html);
				_zayavExpenseEdit();
			});
		}
	},

	_zayavExpenseAttachSchetSpisok = function(v, id) {
		ZE_ATTACH_SCHET.page = 1;
		ZE_ATTACH_SCHET[id] = v;
		$.post(AJAX_MAIN, ZE_ATTACH_SCHET, function(res) {
			if(res.success)
				$('#ze-attach-schet').html(res.spisok);
		}, 'json');
	},
	_zayavExpenseAttachSchetPay = function(id) {
		var html =
				'<center>������������� ������ �����.</center>',
			dialog = _dialog({
				head:'������������� ������ �����',
				content:html,
				padding:30,
				butSubmit:'���� �������',
				submit:submit
			});

		function submit() {
			var send = {
				op:'ze_attach_schet_pay',
				id:id
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
		window.open(URL + '&p=75&d=kvit_html&id=' + id, 'kvit', params);
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
	_zayavCartridgeSchetPay = function(t) {
		if(!_checkAll()) {
			t.vkHint({
				top:-80,
				left:440,
				msg:'<span class="red">�� ������� ���������</span>',
				show:1,
				remove:1
			});
			return false;
		}

		window.CARTRIDGE_IDS = _checkAll();

		schetPayEdit();
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

	_zayavGNLostShow = function() {//����� �������� ������� ������
		var t = $('tr.gn-lost');
		if(!t.length)
			return;
		t.parent().find('.bg-gr1').show();
		t.remove();
	},
	_zayavSchetPayKupez = function(t) {
		if(!_checkAll()) {
			t.vkHint({
				top:-75,
				left:323,
				msg:'<span class="red">�� ������� ������ �������</span>',
				show:1,
				remove:1
			});
			return false;
		}

		window.GN_IDS = _checkAll();

		schetPayEdit();
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
	},

	_zayavReportKupez = function() {//��������� ����� �� ������� ��� �����
		$('#gnyear')._yearLeaf({func:_zayavReportKupezSpisok});
		$('#nomer')._select({
			width:180,
			spisok:GN_SEL,
			func:_zayavReportKupezSpisok
		});
	},
	_zayavReportKupezSpisok = function(v, id) {
		var send = {
			op:'zayav_nomer_report'
		};
		send[id] = v;
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				$('.left').html(res.spisok);
				$('#nomer')
					._select(res.nomer_spisok)
					._select(res.nomer);
			}
		}, 'json');
	},

	_zayavTovarZakazAdd = function(t, tovar_id) {//���������� ������ � ����� � ������
		if(t.hasClass('_busy'))
			return;

		t.addClass('_busy');

		var send = {
			op:'tovar_zakaz',
			tovar_id:tovar_id,
			count:1,
			zayav_id:ZI.id
		};
		$.post(AJAX_MAIN, send, function(res) {
			t.removeClass('_busy');
			if(res.success)
				t.addClass('dn')
				 .prev().removeClass('dn')
				 .attr('val', res.id);
		}, 'json');
	},
	_zayavTovarZakazRemove = function(t) {//�������� ������ �� ������ � ������
		var p = t.parent();

		if(t.hasClass('vh'))
			return;

		t.addClass('vh');
		p.addClass('_busy');

		var send = {
			op:'tovar_zakaz_del',
			id:p.attr('val'),
			zayav_id:ZI.id
		};
		$.post(AJAX_MAIN, send, function(res) {
			t.removeClass('vh');
			p.removeClass('_busy');
			if(res.success) {
				p.addClass('dn');
				p.next().removeClass('dn');
			}
		}, 'json');
	},
	
	_zayavAttachCancel = function(v) {//������ ������������ ���������
		var html =
				'<div class="_info">' +
					'������������ ��������� ����� �������� ��� ��������������. ' +
					'��� ������ ������, ���� ����������� ������� <u>�������� �� ���������</u>, ������ ������ ���������� �� �����.' +
					'<br />' +
					'����� ����������� <b>������� �������</b> �������.' +
				'</div>' +
				'<div class="mt15 center fs16">��������� ��������������<br />������������ ���������.</div>' +
				'<table class="bs10">' +
					'<tr><td class="label">�������:*' +
						'<td><input type="hidden" id="reason" />' +
				'</table>',
			dialog = _dialog({
				width:430,
				head:'�������������� ������������ ���������',
				content:html,
				butSubmit:'���������',
				submit:submit
			});

		$('#reason')
			._select({
				width:320,
				write_save:1
			})
			._select('process');
		
		//�������� �������� ������
		var send = {
			op:'zayav_attach_cancel_reason_load',
			v:'attach' + (v || '')
		};
		$.post(AJAX_MAIN, send, function(res) {
			$('#reason')._select('cancel');
			if(res.success)
				$('#reason')
					._select(res.spisok)
					._select('focus');
		}, 'json');

		function submit() {
			var send = {
				op:'zayav_attach_cancel',
				zayav_id:ZI.id,
				v:'attach' + (v || ''),
				reason:$('#reason')._select('inp')
			};
			dialog.post(send);
		}
	},
	
	mainMenuZayavChart = function() {
		$('#zayav-chart-container')
			.height(300)
			.highcharts({
				chart:{
					type:'column'
				},
				title:{
					text:'����� ������ �� ��������� 30 ����'
				},
				xAxis:{
					categories:ZAYAV_CATEGORIES,
					labels:{
						style:{
							color:'#333'
						}
					}
				},
				yAxis:{
					title:{
						text:'����������'
					},
			        stackLabels:{
				        enabled:true,
				        style:{
					        fontWeight:'bold',
					        color:'black'
				        }
			        }
				},
				plotOptions:{
					column:{
						stacking:'normal',
						dataLabels:{
							enabled:false
						}
					}
				},
				series:ZAYAV_SERIES
			});
	},

	_zayavStat = function(service_id) {//������ ������ �� ����������� ���� ������������
		var dialog = _dialog({
				top:20,
				width:750,
				head:'���������� �� �������',
				load:1,
				butSubmit:'',
				butCancel:'�������'
			}),
			SERVICE_ID = service_id,
			YEAR = 0,
			YEAR_COMPARE = 0,
			TOVAR_CAT_ID = 0,
			TOVAR_ID = 0;

		statLoad();

		function statLoad() {
			var send = {
				op:'zayav_stat_load',
				service_id:SERVICE_ID,
				year:YEAR,
				year_compare:YEAR_COMPARE,
				tovar_cat_id:TOVAR_CAT_ID,
				tovar_id:TOVAR_ID
			};
			$('#zayav-stat').css('opacity', .4);
			dialog.load(send, loaded);
		}
		function loaded(res) {
			dialog.content.html(res.html);
			$('#filter-service')._menuDop({
				type:2,
				spisok:res.filterService,
				func:function(v) {
					SERVICE_ID = v;
					YEAR = 0;
					YEAR_COMPARE = 0;
					TOVAR_CAT_ID = 0;
					statLoad();
				}
			});
			$('#filter-year')._menuDop({
				type:2,
				spisok:res.filterYears,
				func:function(v) {
					YEAR = v;
					statLoad();
				}
			});
			$('#filter-year-compare')._menuDop({
				type:2,
				cancel:1,
				spisok:res.filterYears,
				func:function(v) {
					YEAR_COMPARE = v;
					statLoad();
				}
			});
			$('#filter-tovar-cat')._select({
				width:200,
				title0:'�� �������',
				spisok:res.filterTovarCat,
				func:function(v) {
					TOVAR_CAT_ID = v;
					statLoad();
				}
			});
			$('#filter-tovar-id').tovar({
				small:1,
				zayav_use:1,
				func:function(v) {
					TOVAR_ID = v;
					statLoad();
				}
			});
			$('#zayav-stat')
				.height(250)
				.highcharts({
					chart:{
						type:'column'
					},
					title:{
						text:'����� ������ �� ������� �� ' + (res.year_compare && res.year_compare != res.year ? '<b>' + res.year_compare + '</b>, ' : '') + '<b>' + res.year + '</b> ���'
					},
					xAxis:{
						categories:MONTH_CUT,
						labels:{
							style:{
								color:'#333'
							}
						},
                        crosshair: true
					},
					yAxis:{
						title:{
							text:'����������'
						},
						stackLabels:{
							enabled:true,
							style:{
								fontWeight:'bold',
								color:'black'
							}
						}
					},
					plotOptions:{
						series:{
							animation: {
				                duration:300
                            },
							dataLabels:{
								enabled:true,
								formatter:function() {
									return this.y || ''
								}
							}
						}
					},
					tooltip:{
						enabled:true,
						shared:true
					},
					series:res.series
				});
		}
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
$.fn.gnGet = function(o, o1) {//������ �����
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
		if(o == 'summa')
			window[win].summa(o1);
		if(o == 'manual')
			window[win].manual(o1);
		if(o == 'skidka')
			window[win].skidka(o1);

		return t;
	}

	o = $.extend({
		show:4,     // ���������� �������, ������� ������������ ����������, � ����� ������ �� ��� ���������
		add:8,      // ���������� �������, ������������� � ������
		gns:{},   // ��������� ������ (��� ��������������)
		dop_title0:'',//���. �������� �� ������           ������ �� �������
		dop_spisok:[],
		four_free:0,// ������ 4-� ����� ���������
		pn_show:0,  // ���������� ����� ������� �����
		skidka:0,
		manual:0,   // ����������� �� ������� ��� ����� ����� ����� �������
		summa:0,    // ����� ���� ����������� ������� manual: ����� ��������� ���� ����������, ���������� �������. ����� ��� ������� �� ��� �������� ������.
		func:function() {}
	}, o);

	var pix = 21, // ������ ���� ������ � ��������
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
		.off('click', '#darr')
		.on('click', '#darr', function() {// �������������� ������
			var t = $(this),
				begin = _num(t.prev().find('.gns-week').attr('val'));

			if(!begin)
				return;

			gnsPrint(begin + 1, o.add);
		})
		.off('click', '.gns-week')
		.on('click', '.gns-week', function() {// �������� �� ������� �� ����� ������
			dopMenuA.removeClass('sel'); //�������� ��������� �������, ���� ��� ������
			var th = $(this),
				sel = !th.hasClass('gnsel'),
				v = th.attr('val');

			if(th.hasClass('schet'))
				return;

			th[(sel ? 'add': 'remove') + 'Class']('gnsel');
			th.removeClass('prev');
			th.find('.gnid').val(0);

			gnsDop(v, sel);
			cenaSet();
			gnsValUpdate();
		});

	var gnGet = $('#gnGet'),                 // �������� �����
		gns = gnGet.find('#gns'),            // ������ �������
		dopMenuA = gnGet.find('#dopLinks a'),// ������ ���� � ���������
		dopDef = gnGet.find("#dopDef"),      // ����� �������������� ���������� �� ���������
		selCount = gnGet.find('#selCount'),  // ���������� ��������� �������
		gnCena = 0;   // ���� �� ���� �����

	// ��������� ��������� ������� ��� ��������������
	var gn_sel_end = 0,
		count = 0;
	for(var n in o.gns)
		gn_sel_end = _num(n);

	if(gn_sel_end)
		for(n in GN_ASS) {
			n = _num(n);
			if(n > gn_sel_end)
				break;
			if(n < GN_FIRST)
				continue;
			count++;
		}
	gnsPrint(GN_FIRST, count + o.show);
	gnsDopAll();
	gnsValUpdate();

//	o.gns = {};

	dopMenuA.click(function() {// ����� ������� �� �����, 3 ������, ������� � ���, ������� �������
		var t = $(this),
			sel = !t.hasClass('sel'),
			v = sel ? _num(t.attr('val')) : 0;

		dopMenuA.removeClass('sel');
		if(sel)
			t.addClass('sel');

		gnsPrint(GN_FIRST, v + o.show);

		$('.gns-week').addClass(function(i) {
			return i < v ? 'gnsel' : '';
		});

		gnsAA(function(sp, nn) {
			gnsDop(nn, 1);
		});

		cenaSet();
		gnsValUpdate();
	});
	function gnsPrint(start, count) {// ����� ������ �������
		/*
			start - ������ �����, � �������� ��������
			count - ���������� ������� � ������
		*/

		//���� ������ ����� �� ������, ������ ������ ����� ��������� � ���������� ����������
		if(!start)
			start = GN_FIRST;

		if(!count)
			count = o.show;

		var polosa = _toAss(GAZETA_POLOSA_SPISOK),
			html = '';

		gnGet.find('#darr').remove();

		for(var n in GN_ASS) {
			if(!count)
				break;

			var sp = GN_ASS[n],
				prev = ' curP',
				schet = 0,
				dop = 0,
				pn = 0,
				skidka = o.skidka,
				cena = '',
				gnid = 0;

			if(n < start)
				continue;

			if(o.gns[n]) {
				schet = o.gns[n][5];
				prev = ' gnsel ' + (schet ? 'schet' : 'prev curP');
				dop = o.gns[n][0];
				pn = o.gns[n][1];
				skidka = o.gns[n][2];
				cena = o.gns[n][3];
				gnid = o.gns[n][4];
			}

			count--;

			html +=
				'<table><tr>' +
					'<td><table class="gns-week' + prev + '" val="' + n + '">' +
							'<tr><td class="td"><b>' + sp.week + '</b><span class="g">(' + sp.gen + ')</span>' +
								'<td class="td r">' +
									'<span class="g">�����</span> ' + sp.txt +
									'<input type="hidden" id="skidka' + n + '" value="' + skidka + '" />' + //������ � ���������
									'<input type="hidden" id="exact' + n + '" value="' + cena + '" />' + //������ ����: ���������� ����
									'<input type="hidden" class="gnid" id="gnid' + n + '" value="' + gnid + '" />' +    //id ������, ���� �������������
								'<td class="cena" id="cena' + n + '">' + (Math.round(cena * 100) / 100) +
						'</table>' +
					'<td class="vdop">' +
						(schet && polosa[dop] ? polosa[dop] : '') +
						'<input type="hidden" id="vdop' + n + '" value="' + dop + '" /> ' +
						'<input type="hidden" id="pn' + n + '" value="' + pn + '" />' +
				'</table>';
		}
		html += n != GN_LAST ? '<div id="darr">&darr; &darr; &darr;</div>' : '';
		gns[start == GN_FIRST ? 'html' : 'append'](html);
		gns.animate({height:(gns.find('.gns-week').length * pix) + 'px'}, 300);
		gnsAA(function(sp, nn) {
			gnsDop(nn, 1);
			gnsDopPolosa(nn);
		});
	}
	function gnsDopAll() {//���������� ������ � �������������� ���������� ��� ��������� ���� ��������� �������
		dopDef._dropdown(!o.dop_spisok.length ? 'remove' : {
			head:'����...',
			headgrey:1,
			title0:o.dop_title0,
			nosel:1,
			spisok:o.dop_spisok,
			func:function(id) {
				gnsAA(function(sp, nn, prev) {
					if(!prev) {
						$('#vdop' + nn)._dropdown(id);
						gnsDopPolosa(nn);
					}
				});
				cenaSet();
				gnsValUpdate();
			}
		});
	}
	function gnsDop(nn, sel) {//���������� ������ � �������������� ���������� ��� ����������� ������ ������
		if(!o.dop_spisok.length)
			return;
		if(!sel)
			$('#vdop' + nn).val(0);
		$('#vdop' + nn)._dropdown(!sel ? 'remove' : {
			title0:o.dop_title0,
			spisok:o.dop_spisok,
			func:function() {
				gnsDopPolosa(nn);
				cenaSet();
				gnsValUpdate();
			}
		});
	}
	function gnsDopPolosa(nn) {//���������� ������ � �������� �����, ���� �����
		if(!o.pn_show)
			return;
		var dop_id = _num($('#vdop' + nn).val());
		if(!dop_id || !GAZETA_POLOSA_POLOSA[dop_id]) {
			$('#pn' + nn).val(0)._dropdown('remove');
			return;
		}
		var pc = [];
		for(n = 2; n < GN_ASS[nn].pc; n++)
			pc.push({uid:n,title:n + '-�'});
		$('#pn' + nn)._dropdown({
			title0:'??',
			spisok:pc,
			func:gnsValUpdate
		});
	}
	function gnsAA(func, all) {// gnsActionActive: ���������� �������� � ��������� �������
		var week = $('.gns-week'),
			gnsel = [];
		for(var n = 0; n < week.length; n++) {
			var sp = week.eq(n),
				sel = sp.hasClass('gnsel'),
				prev = sp.hasClass('prev'),
				schet = sp.hasClass('schet'),
				v = _num(sp.attr('val'));

			if(schet)
				continue;

			if(all || sel)
				func(sp, v, prev);
		}
	}
	function gnsClear() {// ������� ��������� �������
		gnGet.find('.gnsel').removeClass('gnsel');
		gnsValUpdate();
	}
	function cenaSet() {// ��������� ���� � ��������� ������
		var four = o.four_free ? 4 : 1000,
			count = 0,
			cena = 0;
		//������� ���������� ����������, � ������� ����� ������� ��������� (� ������ ����������� ������)
		if(o.manual) {
			gnsAA(function(sp, nn, prev) {
				if(!prev) {
					four--;
					if(!four)
						four = 4;
					else
						count++;
				}
			});
			cena = Math.round((o.summa / count) * 1000000) / 1000000;
		}

		four = o.four_free ? 4 : 1000;
		gnsAA(function(sp, nn, prev) {
			if(!prev) {
				var c = 0,
					dop = _num($('#vdop' + nn).val());
				four--;
				if(!four) {
					four = 4;
					c = 0;
				} else
					if(o.manual)
						c = cena;
					else {
						//����������� ���������� ��� ��� ������� ������������ �� ��������� pn_show
						if(o.pn_show) {
							c = gnCena && dop ? gnCena * GAZETA_POLOSA_CENA[dop] : 0;
							if(o.skidka)
								c = c - c / 100 * o.skidka
						} else
							c = gnCena ? gnCena + (dop ? GAZETA_OBDOP_CENA[dop] : 0) : 0;
					}
				$('#cena' + nn).html(Math.round(c * 100) / 100);
				$('#exact' + nn).val(c);
			}
		});
	}
	function gnsValUpdate() {//���������� ��������� �������� �������
		var arr = [],
			sum = 0,
			count = 0;
		gnsAA(function(sp, v, prev) {
			var dop = _num($('#vdop' + v).val()),
				pn = _num($('#pn' + v).val()),
				skidka = _num($('#skidka' + v).val()),
				c = $('#exact' + v).val() * 1;
//				gnid = _num($('#gnid' + v).val());//todo id ������ ��� ��������������. ���� �� ������������
			arr.push(v + ':' + dop + ':' + pn + ':' + skidka + ':' + c);

			if(!prev)
				sum += c;

			count++;
		});

		t.val(arr.join('###'));
//		$('#ze-note').val(t.val()); //todo �������

		o.func({
			summa:Math.round(sum * 100) / 100,
			skidka_sum:o.skidka ? Math.round((sum / (100 - o.skidka) * 100 - sum) * 100) / 100 : 0
		});

		//����� ���������� ��������� �������
		var countHtml = '������' + _end(count, ['', '�']) + ' ' +
						 count + ' �����' + _end(count, ['', 'a', '��']) +
						 '<a>��������</a>';
		selCount
			.html(count ? countHtml : '')
			.find('a').click(function() {
				gnsClear();
				gnsPrint();
				dopMenuA.removeClass('sel');
			});
	}

	t.cenaSet = function(c) {
		gnCena = c || 0;
		cenaSet();
		gnsValUpdate();
	};
	t.update = function() {
		cenaSet();
		gnsValUpdate();
	};
	t.summa = function(v) {
		o.summa = v;
	};
	t.manual = function(v) {
		o.manual = v;
	};
	t.skidka = function(v) {
		o.skidka = v;
		gnsAA(function(sp, nn, prev) {
			if(!prev)
				$('#skidka' + nn).val(v);
		});
		t.update();
	};

	window[win] = t;
	return t;
};

$(document)
	.on('click', '#_zayav .vk.red', function() {//������� ������� ������
		$('#find')._search('clear');    ZAYAV.find = '';
		$('#sort')._radio(1);           ZAYAV.sort = 1;
		$('#desc')._check(0);           ZAYAV.desc = 0;
		$('#ob_onpay')._check(0);       ZAYAV.ob_onpay = 0;

		$('#sel')
			.html('��������� �������')
			.css('background', '#fff');
		ZAYAV.status_ids = ZAYAV.status_def;

		$('#finish').zayavSrok('0000-00-00');ZAYAV.finish = '0000-00-00';
		$('#finish').zayavSrok('executer_id', 0);

		$('#paytype')._radio(0);		ZAYAV.paytype = 0;
		$('#noschet')._check(0);		ZAYAV.noschet = 0;
		$('#nofile')._check(0);		    ZAYAV.nofile = 0;
		$('#noattach')._check(0);		ZAYAV.noattach = 0;
		$('#noattach1')._check(0);		ZAYAV.noattach1 = 0;
		$('#executer_id')._select(0);	ZAYAV.executer_id = 0;
		$('#tovar_cat_id')._select(0);	ZAYAV.tovar_cat_id = 0;
		$('#tovar_id').tovar(0);	    ZAYAV.tovar_id = 0;
		$('#tovar_place_id')._select(0);ZAYAV.tovar_place_id = 0;

		$('#gn_year')._yearLeaf('cur');     ZAYAV.gn_year = (new Date()).getFullYear();
		$('#gn_nomer_id')._radio(GN_FIRST); ZAYAV.gn_nomer_id = GN_FIRST;
		$('#gn_polosa')._select(0);         ZAYAV.gn_polosa = 0;
		$('#gn_polosa_color')._radio(0);    ZAYAV.gn_polosa_color = 0;

		$('#deleted')._check(0);		ZAYAV.deleted = 0;
		$('#deleted_only')._check(0);	ZAYAV.deleted_only = 0;

		$('#f56')._check(0);		ZAYAV.f56 = 0;
		$('#f57')._check(0);		ZAYAV.f57 = 0;
		$('#f59')._check(0);		ZAYAV.f59 = 0;
		$('#f60')._check(0);		ZAYAV.f60 = 0;

		_zayavSpisok();
	})

	.on('click', '#zayav-status-filter', function(e) {//�������� ��� ������� �� ������-������
		var t = $(this),
			et = $(e.target),
			tab = t.find('#status-tab');

		//��������� ������
		if(et.attr('id') == 'sel') {
			tab.show();
			$(document).on('click.status_tab', function() {
				tab.hide();
				$(document).off('click.status_tab');
			});
			return;
		}

		//������� �� ��� � ��������
		if(et.hasClass('td-check')) {
			e.stopPropagation();
			var inp = et.find('input'),
				attr_id = inp.attr('id'),
				v = _bool(inp.val()),
				sel = false;
			$('#' + attr_id)._check(v ? 0 : 1);
			if(attr_id == 'st0')
				sel = v ? 'no' : 'all';
			zsfCheck(sel);
			return;
		}

		//������� �� �������
		if(et.hasClass('_check')) {
			e.stopPropagation();
			var inp = et.find('input'),
				attr_id = inp.attr('id'),
				v = _bool(inp.val()),
				sel = false;
			$('#' + attr_id)._check(v ? 1 : 0);
			if(attr_id == 'st0')
				sel = v ? 'all' : 'no';
			zsfCheck(sel);
			return;
		}

		//������� �� �������� ��� ����������
		if(et.hasClass('td-name')) {
			var inp = _parent(et).find('input'),
				id = inp.attr('id').split('st')[1];
			$('#sel')
				.html(ZAYAV_STATUS_NAME_ASS[id])
				.css('background', '#' + ZAYAV_STATUS_COLOR_ASS[id]);
			zsfCheck(id);
			return;
		}

		//����������� ������ id ��������� ���������
		function zsfCheck(sel_id) {
			var check = tab.find('._check'),
				arr = 0;
			for(var n = 0; n < check.length; n++) {
				var sp = check.eq(n),
					inp = sp.find('input'),
					id = _num(inp.attr('id').split('st')[1]);
				if(sel_id)
					$('#st' + id)._check(sel_id == id || sel_id == 'all' ? 1 : 0);
				if(!_num(inp.val()))
					continue;
				if(!id)
					continue;
				arr = arr + ',' + id;
			}
			ZAYAV.status_ids = arr;
			_zayavSpisok();
		}
	})
/*
	.on('click', '#zayav-status-filter tr', function() {
		var id = _num($(this).attr('val'));
		ZAYAV.status = id;
		_zayavSpisok();
		$('#zayav-status-filter')[(id ? 'add' : 'remove') + 'Class']('us');

		$('#sel')
			.html(ZAYAV_STATUS_NAME_ASS[id])
			.css('background', '#' + ZAYAV_STATUS_COLOR_ASS[id]);
	})
*/
	.on('click', '.zayav-expense-toggle', function() {//������������/�������������� ������ �������� �� ������
		var t = $(this),
			tab = t.next(),
			v;
		tab.slideToggle(200, function() {
			v = tab.css('display') == 'none' ? 1 : 0;
			t.find('a').html(!v ? '��������' : '����������');
			_cookie('zet', v);
		});
	})
	.on('click', '._zayav-unit', function() {
		var id = $(this).attr('val');
		_scroll('set', 'u' + id);
		location.href = URL + '&p=45&id=' + id;
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
	.on('mouseenter', '.zayav-tovar-avai', function() {//����� ������� ������ ��� ���������
		var t = $(this);

		t.vkHint({
			top:-170,
			left:-421,
			width:530,
			ugol:'right',
			indent:150,
			msg:'<div class="zta-hint mh150 _busy">&nbsp;</div>',
			show:1,
			delayHide:500,
			remove:1
		});

		var tovar_id = _parent($(this), '.unit').attr('val'),
			send = {
				op:'zayav_tovar_avai_load',
				tovar_id:tovar_id
			};
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('.zta-hint')
						.removeClass('_busy')
						.html(res.html);
					$('.zta-but').click(submit);
				}
			},'json');

		function submit() {
			var but = $(this),
				send = {
					op:'zayav_tovar_set',
					zayav_id:ZI.id,
					avai_id:_num($('#tovar-avai-id').val())
				};

			if(but.hasClass('_busy'))
				return;

			if(but.hasClass('grey'))
				return;

			but.addClass('_busy');
			$.post(AJAX_MAIN, send, function(res) {
				but.removeClass('_busy');
				if(res.success) {
					but.addClass('grey');
					_msg();
					location.reload();
				}
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
		if($('#_zayav-info').length) {
			$('.a-page').click(function() {
				var t = $(this);
				t.parent().find('.link').removeClass('sel');
				var i = t.addClass('sel').index();
				$('.page:first')[(i ? 'add' : 'remove') + 'Class']('dn');
				$('.page:last')[(!i ? 'add' : 'remove') + 'Class']('dn');
			});
			var name = [0], action = [0];

			if(SERVICE_ACTIVE_COUNT > 1) {
				name.push('�������� ��������� ������');
				action.push(_zayavTypeChange);
			}

			name.push('������������� ������ ������'); action.push(_zayavEdit);

			if(ZI.pole[23]) {
				name.push('�������� ���������');
				action.push(_zayavCartridgeAdd);
			}
			if(ZI.pole[20]) {
				name.push('����������� ���������');
				action.push(function() {
					if(APP_ID == 3798718) {
						if(ZI.pole[23])
							location.href = URL + '&p=75&d=kvit_cartridge&id=' + ZI.id;
						else
							location.href = URL + '&p=75&d=kvit_comtex&id=' + ZI.id;
					} else
						_zayavKvit();
				});
			}
			if(ZI.pole[19]) {
				if(!DOG.id) {
					name.push('������� �� �������� �����'); //template_id = 2
					action.push(_zayavDogovor2);
					name.push('������� ��� ������� ������');//template_id = 1
					action.push(_zayavDogovor1);
				} else {
					name.push('�������� ������ ��������');
					action.push(_zayavDogovorCreate);
					name.push('����������� �������');
					action.push(_zayavDogovorTerminate);
				}
			}
			if(ZI.pole[58]) {
				name.push(DOG.id ? '�������� ������ ��������' : '��������� �������');
				action.push(_zayavDogovorCreate);
				if(DOG.id) {
					name.push('����������� �������');
					action.push(_zayavDogovorTerminate);
				}
			}
			if(SCHET_PAY_USE) {
				name.push('������������ ���� �� ������');
				action.push(schetPayEdit);
			}
			if(ZI.pole[45]) {
				name.push('�������� ������ ������');
				action.push(_zayavStatus);
			}
			name.push('���������');                     action.push(_accrualAdd);
			name.push('������� �����'); action.push(_incomeAdd);
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
				type:'icon',
				title:'���������� ��������',
				table_name:'_zayav',
				table_row:ZI.id
			});
			$('#attach1_id')._attach({
				type:'icon',
				title:'���������� ��������',
				table_name:'_zayav',
				table_row:ZI.id,
				col_name:'attach1_id'
			});
			$('.attach-canceled').mouseover(function() {
				var t = $(this),
					v = t.attr('val');
				t.vkHint({
					show:1,
					msg:v,
					indent:60,
					top:-88,
					left:-14
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
				var t = $(this);
				if(t.hasClass('lena')) {
					location.href = URL + '&p=75&d=erm_lena&mon=' + $(this).attr('val');
					return;
				}
				location.href = URL + '&p=75&d=erm&mon=' + $(this).attr('val');
			});
		}
		if($('#ze-attach-schet').length) {
			$('#find')._search({
				width:178,
				focus:1,
				txt:'������� �����...',
				enter:1,
				func:_zayavExpenseAttachSchetSpisok
			});
			$('#no_pay')._check(_zayavExpenseAttachSchetSpisok);
			$('#no_attach')._check(_zayavExpenseAttachSchetSpisok);
		}
	});
