var _accrualAdd = function() {
		var html =
			'<div id="_accrual-add">' +
				'<table class="tab">' +
	(window.ZI ? '<tr><td class="label">������:<td><b>' + ZI.name + '</b>' : '') +
					'<tr><td class="label">�����:<td><input type="text" id="sum" class="money" /> ���.' +
					'<tr><td class="label">����������:<em>(�� �����������)</em><td><input type="text" id="about" />' +
				'</table>' +

				'<div id="status-div">' +
					'<table class="tab">' +
						'<tr><td class="label topi">������ ������: <td><input type="hidden" id="acc_status" />' +
					'</table>' +
				'</div>' +

				'<div id="remind-div">' +
					'<table class="tab">' +
						'<tr><td class="label topi">�������� �����������:<td><input type="hidden" id="acc_remind" />' +
						'<tr class="remind-tr"><td class="label">����������:<td><input type="text" id="remind-txt" value="��������� � �������� � ����������" />' +
						'<tr class="remind-tr"><td class="label">����:<td><input type="hidden" id="remind-day" />' +
					'</table>' +
				'</div>' +
			'</div>';

		var dialog = _dialog({
			top:30,
			mb:60,
			width:480,
			head:'�������� ����������',
			padding:0,
			content:html,
			butSubmit:'�����...',
			submit:toStatus
		});

		$('#sum').focus();
		$('#acc_status')._radio({
			light:1,
			spisok:ZI.status_sel,
			func:function() {
				$('#remind-div').slideDown(300);
			}
		});
		$('#acc_remind')._radio({
			light:1,
			block:0,
			spisok:[
				{uid:1,title:'��'},
				{uid:2,title:'���'}
			],
			func:function(v) {
				$('.remind-tr')[v == 1 ? 'show' : 'hide']();
				dialog.submit(submit);
				dialog.butSubmit('������');
			}
		});
		$('#remind-day')._calendar();

		function toStatus() {
			if(!_cena($('#sum').val())) {
				dialog.err('����������� ������� �����');
				$('#sum').focus();
				return false;
			}
			if($('#status-div').is(':hidden')) {
				$('#status-div').slideDown(300);
				return false;
			}
			if(!_num($('#acc_status').val())) {
				dialog.err('������� ������ ������');
				return false;
			}
			if(!_num($('#acc_remind').val())) {
				dialog.err('��������, ����� �� ��������� �����������');
				return false;
			}
		}
		function submit() {
			var send = {
					op:'accrual_add',
					zayav_id:window.ZI ? ZI.id : 0,
					sum:$('#sum').val(),
					about:$('#about').val(),
					zayav_status:$('#acc_status').val(),
					remind:_num($('#acc_remind').val()) == 1 ? 1 : 0,
					remind_txt:$('#remind-txt').val(),
					remind_day:$('#remind-day').val()
				};
			if(!_cena(send.sum)) {
				dialog.err('����������� ������� �����');
				$('#sum').focus();
			} else if(send.remind && !send.remind_txt) {
				dialog.err('�� ������� ���������� �����������');
				$('#remind-txt').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					dialog.abort();
					if(res.success) {
						dialog.close();
						_msg('���������� ������� �����������');
						location.reload();
					}
				}, 'json');
			}
		}
	},

	_incomeAdd = function() {
		var zayav = window.ZI,
			about = zayav ? '����������' : '��������',
			about_placeholder = zayav ? ' placeholder="�� �����������"' : '',
			html =
			'<div id="_income-add">' +
				'<table class="tab">' +
		   (zayav ? '<tr><td class="label">������:<td>' + ZI.client_link : '') +
		   (zayav ? '<tr><td class="label">������:<td><b>' + ZI.name + '</b>' : '') +
					'<tr><td class="label">����:<td><input type="hidden" id="invoice_id-add" value="' + (INVOICE_SPISOK.length ? INVOICE_SPISOK[0].uid : 0) + '" />' +
					'<tr class="tr_confirm dn"><td class="label">�������������:<td><input type="hidden" id="confirm" />' +
					'<tr><td class="label">�����:<td><input type="text" id="sum" class="money" /> ���.' +
		   (zayav ? '<tr><td class="label">����������:<td>' : '') +
						'<input type="hidden" id="prepay" />' +
					'<tr><td class="label">' + about + ':<td><input type="text" id="about"' + about_placeholder + ' />' +
				'</table>' +

		   (zayav ?
				(ZAYAV_INFO_DEVICE ?
					'<div id="place-div">' +
						'<table class="tab">' +
							'<tr><td class="label topi">���������������<br />����������:<td><input type="hidden" id="device-place" value="-1" />' +
						'</table>' +
					'</div>'
				: '') +

				(REMIND.active ?
					'<div id="remind-div">' +
						'<h1>���� ' + REMIND.active + ' ������' + _end(REMIND.active, ['��', '��']) + ' ����������' + _end(REMIND.active, ['�', '�', '�']) + '.</h1>' +
						'<h2>�������� ������� �����������, ���� ��� ����� �������� �����������.</h2>' +
						incomeRemind() +
					'</div>'
		        : '')
		   : '') +

			'</div>';
		var dialog = _dialog({
			top:zayav ? 30 : 60,
			width:460,
			head:'�������� �������',
			padding:0,
			content:html,
			submit:submit
		});
		$('#invoice_id-add')._select({
			width:218,
			title0:'�� ������',
			spisok:INVOICE_SPISOK,
			func:function(id) {
				$('#sum').focus();
				$('.tr_confirm')[(INVOICE_CONFIRM_INCOME[id] ? 'remove' : 'add') + 'Class']('dn');
				$('#confirm')._check(0);
			}
		});
		$('#confirm')._check();
		$('#confirm_check').vkHint({
			width:210,
			msg:'���������� �������, ���� ����� ����� ������, �� ��������� ������������� � ��� ����������� �� ����.',
			top:-96,
			left:-100
		});
		if(zayav) {
			$('#prepay')._radio({
				light:1,
				block:0,
				spisok:[
					{uid:1, title:'��'},
					{uid:2, title:'���'}
				],
				func:function() {
					$('#about').focus();
					if(ZAYAV_INFO_DEVICE)
						$('#place-div').slideDown(300);
					else
						$('#remind-div').slideDown(300);
				}
			});

			if(ZAYAV_INFO_DEVICE)
				zayavPlace(function() {
					$('#remind-div').slideDown(300);
				});
			for(var n = 0; n < REMIND.active_spisok.length; n++) {
				var i = REMIND.active_spisok[n];
				$('#ui' + i.id)._check({
					name:'���������',
					light:1
				});
			}
		}
		$('#sum').focus();
		$('#sum,#about').keyEnter(submit);

		function incomeRemind() {//�������� �����������
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
				confirm:_bool($('#confirm').val()),
				sum:_cena($('#sum').val()),
				prepay:_num($('#prepay').val()),
				about:$('#about').val(),
				zayav_id:zayav ? ZI.id : 0,
				place:0,
				place_other:'',
				remind_ids:remind.join()
			};

			if(!send.invoice_id) {
				dialog.err('�� ������ ����');
				return;
			}

			if(!send.sum) {
				dialog.err('����������� ������� �����');
				$('#sum').focus();
				return;
			}

			if(zayav && !send.prepay) {
				dialog.err('�������, �������� �� ����� �����������');
				$('#sum').focus();
				return;
			}

			if(!zayav && !send.about) {
				dialog.err('�� ������� ��������');
				$('#about').focus();
				return;
			}

			if(zayav && ZAYAV_INFO_DEVICE) {
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

			if(send.prepay == 2)
				send.prepay = 0;
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				dialog.abort();
				if(res.success) {
					dialog.close();
					_msg('����� ����� �����.');
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
		$('#schet_id')._check(incomeSpisok);
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

	_refundAdd = function() {//�������� ��������
		var html =
			'<div class="_info">' +
				'����� �������� �������� �� �������� ������� ����������, ����� ��������� ������ �������.' +
			'</div>' +
			'<table id="_refund-add-tab">' +
				'<tr><td class="label">������:<td>' + ZI.client_link +
				'<tr><td class="label">�� �����:<td><input type="hidden" id="invoice_id" value="' + (INVOICE_SPISOK.length ? INVOICE_SPISOK[0].uid : 0) + '" />' +
				'<tr><td class="label">�����:<td><input type="text" id="sum" class="money" /> ���.' +
				'<tr><td class="label">�������:<td><input type="text" id="about" />' +
			'</table>',
			dialog = _dialog({
				width:400,
				head:'�������',
				content:html,
				submit:submit
			});

		$('#sum').focus();
		$('#sum,#prim').keyEnter(submit);
		$('#invoice_id')._select({
			width:200,
			title0:'�� ������',
			spisok:INVOICE_SPISOK,
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
				dialog.err('�� ������ ����');
			else if(!send.sum) {
				dialog.err('����������� ������� �����');
				$('#sum').focus();
			} else if(!send.about) {
				dialog.err('������� ������� ��������');
				$('#about').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					dialog.abort();
					if(res.success) {
						dialog.close();
						_msg('������� ������� ���������');
						location.reload();
					}
				}, 'json');
			}
		}
	},
	_refundLoad = function() {
		$('#invoice_id')._radio({
			light:1,
			title0:'��� �����',
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

	_expenseTab = function(dialog, arr) {//������� ��� �������� ��� �������������� �������
		arr = $.extend({
			id:0,
			category_id:0,
			invoice_id:INVOICE_SPISOK.length ? INVOICE_SPISOK[0].uid : 0,
			worker_id:0,
			attach_id:0,
			sum:'',
			about:'',
			mon:(new Date).getMonth() + 1,
			year:(new Date).getFullYear()
		}, arr);

		ATTACH[arr.attach_id] = arr.attach;

		var html =
			'<table id="expense-add-tab">' +
				'<tr><td class="label">���������:<td><input type="hidden" id="category_id-add" value="' + arr.category_id + '" />' +
						'<a href="' + URL + '&p=setup&d=expense" class="img_edit' + _tooltip('��������� ��������� ��������', -95) + '</a>' +
				'<tr class="tr-work dn"><td class="label">���������:<td><input type="hidden" id="worker_id-add" value="' + arr.worker_id + '" />' +
				'<tr class="tr-work dn"><td class="label">�����:' +
					'<td><input type="hidden" id="tabmon" value="' + arr.mon + '" /> ' +
						'<input type="hidden" id="tabyear" value="' + arr.year + '" />' +
				'<tr><td class="label">��������:<td><input type="text" id="about" value="' + arr.about + '" />' +
				'<tr><td class="label">����:<td><input type="hidden" id="attach_id-add" value="' + arr.attach_id + '" />' +
				'<tr><td class="label">�� �����:<td><input type="hidden" id="invoice_id-add" value="' + arr.invoice_id + '" />' +
				'<tr><td class="label">�����:' +
					'<td><input type="text" id="sum" class="money" value="' + arr.sum + '"' + (arr.id ? ' disabled' : '') + ' /> ���.' +
			'</table>';
		dialog.content.html(html);
		dialog.submit(submit);

		$('#category_id-add')._select({
			width:200,
			title0:'�� �������',
			spisok:EXPENSE_SPISOK,
			func:function(id) {
				$('#worker_id')._select(0);
				$('.tr-work')[(EXPENSE_WORKER_USE[id] ? 'remove' : 'add') + 'Class']('dn');
			}
		});
		$('.tr-work')[(EXPENSE_WORKER_USE[arr.category_id] ? 'remove' : 'add') + 'Class']('dn');
		$('#worker_id-add')._select({
			width:200,
			title0:'�� ������',
			spisok:arr.id ? EXPENSE_WORKER : WORKER_SPISOK
		});
		$('#about').focus();
		$('#invoice_id-add')._select({
			width:200,
			title0:'�� ������',
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
		$('#attach_id-add')._attach();

		$('#sum').keyEnter(submit);

		function submit() {
			var send = {
				id:arr.id,
				op:arr.id ? 'expense_edit' : 'expense_add',
				category_id:_num($('#category_id-add').val()),
				worker_id:$('#worker_id-add').val(),
				attach_id:$('#attach_id-add').val(),
				about:$('#about').val(),
				invoice_id:_num($('#invoice_id-add').val()),
				sum:_cena($('#sum').val()),
				mon:$('#tabmon').val(),
				year:$('#tabyear').val()
			};
			if(!send.about && !send.category_id) {
				dialog.err('�������� ��������� ��� ������� ��������');
				$('#about').focus();
			} else if(!send.invoice_id)
				dialog.err('������� � ������ ����� ������������ ������');
			else if(!send.sum) {
				dialog.err('����������� ������� �����');
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
				head:'�������� �������'
			});
			_expenseTab(dialog);
		});
		$('#invoice_id')._select({
			width:140,
			title0:'��� �����',
			spisok:INVOICE_SPISOK,
			func:expenseSpisok
		});
		EXPENSE_SPISOK.push({
			uid:-1,
			title:'��� ���������',
			content:'<b>��� ���������</b>'
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
		$('#year').years({func:expenseSpisok});
		$('#mon')._radio({
			spisok:EXPENSE_MON,
			light:1,
			right:0,
			func:expenseSpisok
		});
		_expenseGraf();
	},
	_expenseGraf = function() {
		$('#container').highcharts({
	        chart: {
	            type: 'bar',
		        animation:false
	        },
	        title: {
	            text: '����� � ������ �� ����������'
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
							expenseSpisok(sel ? v ? v : -1 : 0, 'category_id');
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
	expenseSpisok = function(v, id) {
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
	_salaryWorkerBalansSet = function() {//��������� ������� �� ����������
		var html =
		'<table class="_dialog-tab">' +
			'<tr><td class="label">�����:<td><input type="text" id="sum" class="money"> ���.' +
		'</table>',
		dialog = _dialog({
			width:320,
			head:'��������� ����� ������� �/� ����������',
			content:html,
			butSubmit:'���������',
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
				dialog.err('����������� ������� �����');
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
	_salaryWorkerRateSet = function() {//��������� ������ ����������
		var html =
			'<div class="_info">' +
				'����� ��������� ������ ���������� ��������� ����� ����� ������������� ����������� ' +
				'�� ��� ������ � ����������� ���� ��������� ��������������. ' +
			'</div>' +
			'<table class="_dialog-tab" id="salary-rate-set-tab">' +
				'<tr><td class="label">�����:<td><input type="text" id="sum" class="money" value="' + (SALARY.rate_sum ? SALARY.rate_sum : '') + '" /> ���.' +
				'<tr><td class="label">������:<td><input type="hidden" id="period" value="' + SALARY.rate_period + '" />' +
				'<tr class="tr-day' + (SALARY.rate_period == 3 ? ' dn' : '') + '">' +
					'<td class="label">���� ����������:' +
					'<td><div class="div-day' + (SALARY.rate_period != 1 ? ' dn' : '') + '"><input type="text" id="day" maxlength="2" value="' + SALARY.rate_day + '" /></div>' +
						'<div class="div-week' + (SALARY.rate_period != 2 ? ' dn' : '') + '"><input type="hidden" id="day_week" value="' + SALARY.rate_day + '" /></div>' +
			'</table>',
			dialog = _dialog({
				top:30,
				width:320,
				head:'��������� ������ �/� ��� ����������',
				content:html,
				butSubmit:'����������',
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
				{uid:1,title:'�����������'},
				{uid:2,title:'�������'},
				{uid:3,title:'�����'},
				{uid:4,title:'�������'},
				{uid:5,title:'�������'},
				{uid:6,title:'�������'},
				{uid:7,title:'�����������'}
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
				dialog.err('����������� ������ ����');
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
	_salaryWorkerAccAdd = function() {//�������� ������������� ���������� �� ����������
		var html =
			'<table class="_dialog-tab">' +
				'<tr><td class="label">�����:<td><input type="text" id="sum" class="money"> ���.' +
				'<tr><td class="label">��������:<td><input type="text" id="about" maxlength="50">' +
				'<tr><td class="label">�����:' +
					'<td><input type="hidden" id="tabmon" value="' + SALARY.mon + '" /> ' +
						'<input type="hidden" id="tabyear" value="' + SALARY.year + '" />' +
			'</table>',
			dialog = _dialog({
				head:'�������� ���������� ��� ����������',
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
				dialog.err('����������� ������� �����');
				$('#sum').focus();
			} else if(!send.about) {
				dialog.err('�� ������� ��������');
				$('#about').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('���������� �����������');
						_salarySpisok();
					} else
						dialog.abort();
				}, 'json');
			}
		}

	},
	_salaryWorkerDeductAdd = function() {//�������� ������ �� �� ����������
		var html =
			'<table class="_dialog-tab">' +
				'<tr><td class="label">�����:<td><input type="text" id="sum" class="money"> ���.' +
				'<tr><td class="label">��������:<td><input type="text" id="about" maxlength="50">' +
				'<tr><td class="label">�����:' +
					'<td><input type="hidden" id="tabmon" value="' + SALARY.mon + '" /> ' +
						'<input type="hidden" id="tabyear" value="' + SALARY.year + '" />' +
			'</table>',
			dialog = _dialog({
				head:'�������� ������ �� ��������',
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
				dialog.err('����������� ������� �����');
				$('#sum').focus();
			} else if(!send.about) {
				dialog.err('�� ������� ��������');
				$('#about').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('���������� �����������');
						_salarySpisok();
					} else
						dialog.abort();
				}, 'json');
			}
		}
	},
	_salaryWorkerZpAdd = function() {//�������� �� ����������
		var html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">�� �����:<td><input type="hidden" id="invoice_id" />' +
					'<tr><td class="label">�����:<td><input type="text" id="sum" class="money"> ���.' +
					'<tr><td class="label">�����:' +
						'<td><input type="hidden" id="tabmon" value="' + SALARY.mon + '" /> ' +
							'<input type="hidden" id="tabyear" value="' + SALARY.year + '" />' +
					'<tr><td class="label">��������:<td><input type="text" id="about">' +
				'</table>',
			dialog = _dialog({
				head:'������ �������� ����������',
				content:html,
				submit:submit
			});

		$('#sum').focus();
		$('#invoice_id')._select({
			title0:'�� ������',
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
				dialog.err('������� � ������ ����� ������������ ������');
			else if(!send.sum) {
				dialog.err('����������� ������� �����');
				$('#sum').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('������ �������� �����������');
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
				head:'�������� �����',
				load:load,
				butSubmit:'',
				butCancel:'�������'
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
						'<tr><td class="label r top">����������:<td>' + res.client +
		(res.zayav_id ? '<tr><td class="label r">������:<td>' + res.zayav_link : '') +
					'</table>' +
					'<h1>�ר� � ' + res.nomer + res.ot + '</h1>' +
					res.html +
					'<h2>' + res.itog + '</h2>' +
					'<table id="dop">' +
						'<tr><td>' +
								(res.nakl ? '<div>&bull; ���������</div>' : '') +
								(res.act ? '<div>&bull; ��� ����������� �����</div>' : '') +
								(res.del ? '<div id="deleted">���� ��� �����</div>' : '') +
							'<th>' +
	   (!res.del && !res.paid ? '<a id="schet-edit">������������� ����</a>' : '') +
								'<a id="schet-print">�����������<div class="img_xls"></div></a>' +
	   (!res.del && !res.paid ?
					(res.pass ? '<a id="schet-pass-cancel">�������� ��������</a>'
							  : '<a id="schet-pass">�������� �������</a>'
					) +
				                '<a id="schet-pay"><b>��������</b></a>' +
				                '<a id="schet-del">�������</a>'
		: '') +
					(res.hist ? '<a id="schet-history">������� ��������</a>' : '') +
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
					head:'����� � ' + res.nomer,
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
		o = $.extend({
			edit:0,//0 - ������������ �� �������� ����� ��� ������, 1 - ������ ������� ����
			schet_id:0,
			client_id:0,
			zayav_id:0,
			arr:[],
			zayav_spisok:[],
			client:'',
			date_create:'',
			nakl:0,
			act:0,
			func:function() {
				location.reload();
			}
		}, o);

		o.nomer = o.schet_id ? '�ר� � ' + o.nomer : '����� �ר�';

		var spisok = '';
		for(var n = 0; n < o.arr.length; n++) {
			var sp = o.arr[n];
			sp.sum = sp.cost * sp.count;
			spisok += pole(sp);
		}
		var html =
				'<div id="_schet-info">' +
					'<table class="tab">' +
						'<tr><td class="label r top">����������:<td>' + o.client +
(o.zayav_spisok.length ? '<tr><td class="label r">������:<td><input type="hidden" id="zayav_id" value="' + o.zayav_id + '" />' : '') +
						'<tr><td class="label r">����:<td><input type="hidden" id="date_create" value="' + o.date_create + '" />' +
						'<tr><td class="label r topi">����������:' +
							'<td><input id="nakl" type="hidden" value="' + o.nakl + '" />' +
								'<input id="act" type="hidden" value="' + o.act + '" />' +
					'</table>' +
					'<h1>' + o.nomer + '</h1>' +
					'<table class="_spisok">' +
						'<tr><th>�' +
							'<th>������������ ������' +
							'<th>���-��' +
							'<th>����' +
							'<th>�����' +
							'<th>' +
						spisok +
						'<tr><td colspan="6" class="_next" id="pole-add">�������� �������' +
					'</table>' +
					'<h3></h3>' +
				'</div>',
			dialog = _dialog({
				top:20,
				width:610,
				head:o.schet_id ? '�������������� ����� �� ������' : '������������ ������ ����� �� ������',
				content:html,
				butSubmit:o.schet_id ? '���������' : '������',
				submit:submit,
				cancel:function() {
					if(!o.edit)
						_schetInfo(o);
				}
			});

		poleNum();
		itog();
		if(o.zayav_spisok.length)
			$('#zayav_id')._select({
				title0:'������ �� �������',
				spisok:o.zayav_spisok
			});
		$('#date_create')._calendar({lost:1});
		$('#nakl')._check({name:'���������',light:1});
		$('#act')._check({name:'��� ����������� �����',light:1});
		$('#pole-add').click(function() {//���������� ����� ������� �����
			$(this)
				.parent()
				.before(pole)
				.parent()
				.find('.name:last input').focus();
			poleNum();
		});

		if(!o.schet_id && !o.arr.length)//���� ����� ���� � ��� ��������, ��������� ���� ������ ����
			$('#pole-add').trigger('click');

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

		function pole(sp) {//���������� ������ ����
			sp = $.extend({
				name:'',
				count:1,
				cost:''
			}, sp);
			var sum = sp.count * _cena(sp.cost),
				readonly = sp.readonly ? ' readonly="readonly"' : '';
			return '<tr class="pole">' +
				'<td class="n">' +
				'<td class="name"><input type="text" value="' + sp.name + '" />' +
				'<td class="count"><input type="text"' + readonly + ' value="' + sp.count + '" />' +
				'<td class="cost"><input type="text"' + readonly + ' value="' + sp.cost + '" />' +
				'<td class="sum">' + (sum ? sum : '') +
				'<td class="ed">' +
					(!readonly ? '<div class="img_del pole-del' + _tooltip('�������', -29) + '</div>' : '');
		}
		function poleNum() {//���������� ��������� ������� �����
			var num = $('#_schet-info .n');
			for(var n = 0; n < num.length; n++)
				num.eq(n).html(n + 1);
		}
		function itog() {//���������� ����� � ��������� ��������� �����
			var pole = $('#_schet-info .pole'),
				num = 0,    //���������� ������������
				sum = 0,    //����� �����
				arr = [],   //������ ��� ��������
				err = 0;    //������� �� ������
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
						msg:'����������� ������� ����������',
						place:count_inp
					};
				}
				if(!err && name && !cost) {
					err = 1;
					arr = {
						error:1,
						msg:'����������� ������� �����',
						place:cost_inp
					};
				}
				if(!err && !name && count && cost) {
					err = 1;
					arr = {
						error:1,
						msg:'�� ������� ������������',
						place:name_inp
					};
				}
				if(!err)
					arr.push({
						name:name,
						count:count,
						cost:cost,
						readonly:count_inp.attr('readonly') == 'readonly'
					});
			}

			if(!err && !num)
				arr = {
					error:1,
					msg:'�� ��������� �� ����� �������'
				};

			$('#_schet-info h3').html('����� ������������ <b>' + num + '</b>, �� ����� <b>' + sum + '</b> ���.');
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
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg();
						o.func(res.schet_id);
					} else
						dialog.abort();
				}, 'json');
			}
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
						{uid:1,title:'���������� ����������'},
						{uid:2,title:'�������������'},
						{uid:3,title:'�����������<div class="img_xls"></div>'},
						{uid:4,title:'�������� �������'},
						{uid:5,title:'�������� ��������'},
						{uid:6,title:'<b>��������</b>'}
					],
					unit = eq.parent().parent(),
					pass = unit.hasClass('pass');
				spisok.splice(pass ? 3 : 4, 1);
				$('#' + eq.attr('id'))._dropdown({
					head:'��������',
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
	_schetPass = function(schet_id, nomer) {//�������� ����� �������
		var html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">� �����:<td><b>' + nomer + '</b>' +
					'<tr><td class="label">����� �������:<td><input type="hidden" id="pass-day" />' +
				'</table>';
		var dialog = _dialog({
				width:320,
				head:'�������� ����� �������',
				content:html,
				butSubmit:'���������',
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
					_msg('���� � ' + nomer + ' ������� �������');
				} else
					dialog.abort();
			}, 'json');
		}
	},
	_schetPassCancel = function(schet_id, nomer) {//������ �������� �����
		var html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">� �����:<td><b>' + nomer + '</b>' +
					'<tr><td class="label">����� �������:<td><input type="hidden" id="pass-day" />' +
				'</table>';
		var dialog = _dialog({
				width:320,
				padding:50,
				head:'������ �������� ����� �������',
				content:'<center>����������� ������ ��������<br />����� <b>' + nomer + '</b> �������.</center>',
				butSubmit:'�����������',
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
					'<tr><td class="label">� �����:<td><b>' + nomer + '</b>' +
					'<tr><td class="label">�����:<td><input type="text" class="money" id="sum" /> ���.' +
					'<tr><td class="label">���� ������:<td><input type="hidden" id="pay-day" />' +
					'<tr><td class="label">��������� ����:<td><input type="hidden" id="invoice_id-pay" value="4" />' +
				'</table>';
		var dialog = _dialog({
				head:'������ �����',
				content:html,
				butSubmit:'��������',
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
		$('#sum').focus().keyEnter(submit);
		function submit() {
			var send = {
				op:'schet_pay',
				schet_id:schet_id,
				invoice_id:_num($('#invoice_id-pay').val()),
				sum:_cena($('#sum').val()),
				day:$('#pay-day').val()
			};
			if(!send.sum) {
				dialog.err('����������� ������� �����');
				$('#sum').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if (res.success) {
						dialog.close();
						_msg('���� ' + nomer + ' �������');
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
			head:'����������',
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
			head:'�������',
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
			sum = p.find('.sum').html(),
			dtime = p.find('.refund-dtime').val(),
			html =
				'<div class="_info">' +
					'����� ����� ������� ��� <b>�������</b>. ����� ����� ������� ������ � ������� "��������".' +
				'</div>' +
				'<div id="income-refund-tab">' +
					'<p>������� ������� �� ����� <b>' + sum + '</b> ���.' +
					'<p>���� �������: <u>' + dtime + '</u>.' +
					'<p><b class="red">����������� ������ ��������.</b>' +
				'</div>',
			dialog = _dialog({
				head:'������� �������',
				content:html,
				butSubmit:'�����������',
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

	.on('click', '._refund-add', _refundAdd)
	.on('click', '._refund-del', function() {
		var t = $(this);
		_dialogDel({
			id:t.attr('val'),
			head:'��������',
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
				head:'�������������� �������',
				load:1,
				butSubmit:'���������'
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
			head:'�������',
			op:'expense_del',
			func:expenseSpisok
		});
	})

	.on('click', '.schet-unit .info, .schet-link', function(e) {
		e.stopPropagation();
		_schetInfo({id:$(this).attr('val')});
	})

	.on('click', '.invoice-set', function() {
		var t = $(this),
			invoice_id = t.attr('val'),
			html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">����:<td><b>' + INVOICE_ASS[invoice_id] + '</b>' +
					'<tr><td class="label">�����:<td><input type="text" class="money" id="sum" /> ���.' +
				'</table>';
		var dialog = _dialog({
			width:270,
			head:'��������� ������� ����� �����',
			content:html,
			butSubmit:'����������',
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
				dialog.err('����������� ������� �����');
				$('#sum').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						$('#invoice-spisok').html(res.html);
						dialog.close();
						_msg('��������� ����� �����������');
					} else
						dialog.abort();
				}, 'json');
			}
		}
	})
	.on('click', '.invoice-reset', function() {
		var t = $(this),
			invoice_id = t.attr('val'),
			html = '����� �� ����� <b>' + INVOICE_ASS[invoice_id] + '</b> ����� ��������.',
			dialog = _dialog({
				head:'����� ����� �����',
				content:html,
				butSubmit:'���������',
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
					_msg('����� ��������');
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
			head:'�������� ����� �������',
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
	.on('click', '._balans-show', function() {//����� ���� ������� ��������� ��������
		var dialog = _dialog({
				top:10,
				width:600,
				head:'�������� ������� ��������',
				load:1,
				butSubmit:'',
				butCancel:'�������'
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

	.on('click', '.go-report-salary', function() {//������� �� �������� �� ���������� � ��������� ������, � ������� ��� ������ �������
		var v = $(this).attr('val').split(':');
		location.href = URL + '&p=report&d=salary&id=' + v[0] + '&year=' + v[1] + '&mon=' + v[2] + '&acc_id=' + v[3];
	})
	.on('mouseenter', '.salary .show', function() {
		$(this).removeClass('show');
	})
	.on('click', '.worker-acc-del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'���������� �/�',
			op:'salary_accrual_del',
			func:_salarySpisok
		});
	})
	.on('click', '.worker-deduct-del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'������ �� �/�',
			op:'salary_deduct_del',
			func:_salarySpisok
		});
	})
	.on('click', '.worker-zp-add', _salaryWorkerZpAdd)
	.on('click', '.worker-zp-del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'��������',
			op:'expense_del',
			func:_salarySpisok
		});
	})

	.ready(function() {
		if($('#money-schet').length) {
			$('#find')._search({
				width: 137,
				focus: 1,
				txt: '����� �����',
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
							'<tr><td class="label">�� �����:<td><input type="hidden" id="from" value="' + from + '" />' +
							'<tr><td class="label">�� ����:<td><input type="hidden" id="to" value="' + to + '" />' +
							'<tr><td class="label">�����:<td><input type="text" id="sum" class="money" /> ���. ' +
							'<tr><td class="label">�����������:<td><input type="text" id="about" />' +
						'</table>',
					dialog = _dialog({
						width:350,
						head:'������� ����� �������',
						content:html,
						butSubmit:'���������',
						submit:submit
					});
				$('#from')._select({
					width:218,
					title0:'�� ������',
					spisok:INVOICE_SPISOK
				});
				$('#to')._select({
					width:218,
					title0:'�� ������',
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
					if(!send.from) dialog.err('�������� ����-�����������');
					else if(!send.to) dialog.err('�������� ����-����������');
					else if(send.from == send.to) dialog.err('�������� ������ ����');
					else if(!send.sum) {
						dialog.err('����������� ������� �����');
						$('#sum').focus();
					} else {
						dialog.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								$('#invoice-spisok').html(res.i);
								$('#transfer-spisok').html(res.t);
								dialog.close();
								_msg('������� ���������');
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
				head:'��������',
				nosel:1,
				spisok:[
					{uid:1, title:'���������� ������'},
					{uid:2, title:'�������� ������'},
					{uid:3, title:'���������'},
					{uid:4, title:'������ �����'},
					{uid:5, title:'������ �/�'}
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
