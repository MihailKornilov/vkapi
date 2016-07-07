var setupRuleCheck = function(v, id) {
	var send = {
		op:id,
		viewer_id:window.RULE_VIEWER_ID || VIEWER_ID,
		v:v
	};
	$.post(AJAX_MAIN, send, function(res) {
		if(res.success)
			_msg('���������');
	}, 'json');
},
	setupExpenseEdit = function(o) {
		o = $.extend({
			id:0,
			name:''
		}, o);

		var t = $(this),
			html = '<table id="setup-tab">' +
					'<tr><td class="label r">������������:' +
						'<td><input id="name" type="text" value="' + o.name + '" />' +
					'<tr' + (o.id ? '' : ' class="dn"') + '>' +
						'<td class="label r">����������:' +
						'<td><input type="hidden" id="join" />' +
					'<tr class="tr-join dn">' +
						'<td><td>' +
							'<div class="_info">' +
								'��� ����������� ��������� �������� ��������� ��������� ������ ����� � ���, ������� ������������� � ������ ������. ' +
								'��� ������ �������� � ����� ���������, ������ ����� �������.' +
								'<br /><br />' +
								'<b>�������� ��������� ��� �����������:</b>' +
							'</div>' +
					'<tr class="tr-join dn">' +
						'<td class="label topi">� ����������:' +
						'<td><input type="hidden" id="category_id-join" />' +
							'<input type="hidden" id="category_sub_id-join" />' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:(o.id ? '��������������' : '���������� �����' ) + ' ��������� ������� �����������',
				content:html,
				butSubmit:o.id ? '���������' : '������',
				submit:submit
			}),
			catSpisok = _copySel(EXPENSE_SPISOK, o.id);

		$('#name').focus();
		$('#join')._check({
			func:function(v) {
				$('.tr-join')[(v ? 'remove' : 'add') + 'Class']('dn');
				if(v)
					$('#category_id-join')._select({
						width:218,
						bottom:5,
						title0:' ��������� �� �������',
						spisok:_copySel(catSpisok, 1),
						func:function(v) {
							_expenseSub(v, 0, '-join', 218);
						}
					});
			}
		});

		function submit() {
			var send = {
				op:'expense_category_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val(),
				category_id:_num($('#category_id-join').val()),
				category_sub_id:_num($('#category_sub_id-join').val())
			};
			if(!send.name) {
				dialog.err('�� ������� ������������');
				$('#name').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#spisok').html(res.html);
					dialog.close();
					_msg();
					sortable();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	setupExpenseSubEdit = function(o) {
		o = $.extend({
			id:0,
			name:''
		}, o);

		var t = $(this),
			html = '<table id="setup-tab">' +
					'<tr><td class="label r">���������:<td><b>' + $('#cat-name').html() + '</b>' +
					'<tr><td class="label r">������������:<td><input id="name" type="text" value="' + o.name + '" />' +
					'<tr' + (o.id ? '' : ' class="dn"') + '>' +
						'<td class="label r">����������:' +
						'<td><input type="hidden" id="join" />' +
					'<tr class="tr-join dn">' +
						'<td><td>' +
							'<div class="_info">' +
								'��� ����������� ��������� �������� ��������� ��������� ������ ����� � ���, ������� ������������� � ������ ������. ' +
								'��� ������ �������� � ����� ���������, ������ ����� �������.' +
								'<br /><br />' +
								'<b>�������� ��������� ��� �����������:</b>' +
							'</div>' +
					'<tr class="tr-join dn">' +
						'<td class="label topi">� ����������:' +
						'<td><input type="hidden" id="category_id-join" />' +
							'<input type="hidden" id="category_sub_id-join" />' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:(o.id ? '��������������' : '���������� �����' ) + ' ������������ ������� �����������',
				content:html,
				butSubmit:o.id ? '���������' : '������',
				submit:submit
			});

		$('#name').focus();
		$('#join')._check({
			func:function(v) {
				$('.tr-join')[(v ? 'remove' : 'add') + 'Class']('dn');
				if(v)
					$('#category_id-join')._select({
						width:218,
						bottom:5,
						title0:' ��������� �� �������',
						spisok:_copySel(EXPENSE_SPISOK, 1),
						func:function(v) {
							_expenseSub(v, 0, '-join', 218);
						}
					});
			}
		});

		function submit() {
			var send = {
				op:'expense_category_sub_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				category_id:CAT_ID,
				name:$('#name').val(),
				category_id_join:_num($('#category_id-join').val()),
				category_sub_id_join:_num($('#category_sub_id-join').val())
			};
			if(!send.name) {
				dialog.err('�� ������� ������������');
				$('#name').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#spisok').html(res.html);
					dialog.close();
					_msg();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	setupRubricEdit = function(o) {
		o = $.extend({
			id:0,
			name:''
		}, o);

		var t = $(this),
			html = '<table class="bs10">' +
					'<tr><td class="label">������������:' +
						'<td><input id="name" type="text" class="w250" value="' + o.name + '" />' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:(o.id ? '��������������' : '���������� �����' ) + ' ������� ����������',
				content:html,
				butSubmit:o.id ? '���������' : '������',
				submit:submit
			});

		$('#name').focus();

		function submit() {
			var send = {
				op:'setup_rubric_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val()
			};
			if(!send.name) {
				dialog.err('�� ������� ������������');
				$('#name').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#spisok').html(res.html);
					dialog.close();
					_msg();
					sortable();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	setupRubricSubEdit = function(o) {
		o = $.extend({
			id:0,
			name:''
		}, o);

		var t = $(this),
			html = '<table class="bs10">' +
					'<tr><td class="label r">�������:<td><b>' + RUBRIC_ASS[RUBRIC_ID] + '</b>' +
					'<tr><td class="label r">����������:' +
						'<td><input id="name" type="text" class="w250" value="' + o.name + '" />' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:(o.id ? '��������������' : '���������� �����' ) + ' ����������',
				content:html,
				butSubmit:o.id ? '���������' : '������',
				submit:submit
			});

		$('#name').focus();

		function submit() {
			var send = {
				op:'setup_rubric_sub_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				rubric_id:RUBRIC_ID,
				name:$('#name').val()
			};
			if(!send.name) {
				dialog.err('�� ������� ����������');
				$('#name').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#spisok').html(res.html);
					dialog.close();
					_msg();
					sortable();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	setupZayavExpense = function(o) {
		o = $.extend({
			id:0,
			name:'',
			dop:0
		}, o);

		var html =
				'<table id="setup-tab">' +
					'<tr><td class="label">������������:<td><input id="name" type="text" value="' + o.name + '" />' +
					'<tr><td class="label topi">�������������� ����:<td><input id="dop" type="hidden" value="' + o.dop + '" />' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:(o.id ? '��������������' : '���������� �����' ) + ' ��������� ������� ������',
				content:html,
				butSubmit:o.id ? '���������' : '������',
				submit:submit
			});

		$('#name').focus();
		$('#dop')._radio({light:1,spisok:ZAYAV_EXPENSE_DOP});

		function submit() {
			var send = {
				op:'setup_zayav_expense_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val(),
				dop:$('#dop').val()
			};
			if(!send.name) {
				dialog.err('�� ������� ������������');
				$('#name').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						$('#spisok').html(res.html);
						dialog.close();
						_msg();
						sortable();
					} else
						dialog.abort();
				}, 'json');
			}
		}
	},
	setupZayavStatus = function(o) {
		o = $.extend({
			id:0,
			name:'',
			about:'',
			color:'fff',
			default:0,
			nouse:0,
			next:0,
			next_ids:0,
			srok:0,
			executer:0,
			day_fact:0,
			accrual:0,
			remind:0,
			hide:0
		}, o);

		var html =
				'<table class="setup-status-tab bs10">' +
					'<tr><td class="label">��������:<td><input id="name" type="text" value="' + o.name + '" />' +
					'<tr><td class="label topi">��������:<td><textarea id="about">' + o.about + '</textarea>' +
					'<tr><td class="label topi">����:<td><div id="color" val="' + o.color + '" style="background-color:#' + o.color + '"></div>' +
					'<tr><td class="label">�� ���������:<td><input type="hidden" id="default" value="' + o.default + '" />' +
					'<tr class="tr-nouse' + (o.default ? '' : ' dn') + '">' +
						'<td class="label">�� ������������ ��������:' +
						'<td><input type="hidden" id="nouse" value="' + o.nouse + '" />' +
					'<tr><td class="label topi">��������� �������:<td><input type="hidden" id="next" value="' + o.next + '" />' +
					'<tr class="tr-next-ids' + (o.next ? '' : ' dn') + '"><td class="label topi"><td><input type="hidden" id="next_ids" value="' + o.next_ids + '" />' +
					'<tr><td><td><input type="hidden" id="hide" value="' + o.hide + '" />' +
					'<tr><td><td>' +
					'<tr><td><td><b>�������� ��� ������ �������</b>' +
					'<tr><td><td><input type="hidden" id="executer" value="' + o.executer + '" />' +
					'<tr><td><td><input type="hidden" id="srok" value="' + o.srok + '" />' +
					'<tr><td class="label">' +
						'<td><input type="hidden" id="day_fact" value="' + o.day_fact + '" />' +
					'<tr><td><td><input type="hidden" id="accrual" value="' + o.accrual + '" />' +
					'<tr><td><td><input type="hidden" id="remind" value="' + o.remind + '" />' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:480,
				head:(o.id ? '��������������' : '���������� ������' ) + ' ������� ������',
				content:html,
				butSubmit:o.id ? '���������' : '������',
				submit:submit
			});

		$('#name').focus();
		$('#about').autosize();
		$('#color').click(setupZayavStatusColor);
		$('#default')._check({
			func:function(v) {
				$('#nouse')._check(0);
				$('.tr-nouse')[(v ? 'remove' : 'add') + 'Class']('dn');
			}
		});
		$('#default_check').vkHint({
			top:-70,
			left:-100,
			width:210,
			msg:'������������� ����������� ������<br />' +
				'������ ��� �������� ����� ������.'
		});
		$('#nouse')._check();
		$('#nouse_check').vkHint({
			top:-83,
			left:-86,
			width:180,
			msg:'����� ������ ������� �������<br />' +
				'������ ������ ������ �����<br />' +
				'������� �����.'
		});
		$('#next')._radio({
			light:1,
			spisok:[
				{uid:0, title:'���'},
				{uid:1, title:'����������'}
			],
			func:function(v) {
				$('.tr-next-ids')[v ? 'show' : 'hide']();
			}
		});

		var spisok = [];
		for(var i = 0; i < ZAYAV_STATUS_NAME_SPISOK.length; i++) {
			var sp = ZAYAV_STATUS_NAME_SPISOK[i];
			if(sp.uid == o.id)
				continue;
			if(ZAYAV_STATUS_NOUSE_ASS[sp.uid])
				continue;
			spisok.push(sp);
		}
		$('#next_ids')._select({
			width:258,
			title0:'��������� ������� �� �������',
			spisok:spisok,
			multiselect:1
		});

		$('#hide')._check({
			name:'�������� ������ �� ������ ������',
			light:1
		});

		$('#day_fact')._check({
			name:'�������� ����������� ����',
			light:1
		});
		$('#executer')._check({
			name:'��������� �����������',
			light:1
		});
		$('#srok')._check({
			name:'�������� ����',
			light:1
		});
		$('#accrual')._check({
			name:'���������� ������� ����������',
			light:1
		});
		$('#remind')._check({
			name:'���������� ��������� �����������',
			light:1
		});

		function submit() {
			var send = {
				op:'setup_zayav_status_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val(),
				about:$('#about').val(),
				color:$('#color').attr('val'),
				default:$('#default').val(),
				nouse:$('#nouse').val(),
				next_ids:$('#next_ids').val(),
				executer:$('#executer').val(),
				srok:$('#srok').val(),
				day_fact:$('#day_fact').val(),
				accrual:$('#accrual').val(),
				remind:$('#remind').val(),
				hide:$('#hide').val()
			};
			if(!send.name) {
				dialog.err('�� ������� ��������');
				$('#name').focus();
				return;
			}
			if(!_num($('#next').val()))
				send.next_ids = 0;
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#status-spisok').html(res.html);
					dialog.close();
					_msg();
					sortable();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	setupZayavStatusColor = function() {//����� ����� ��� ������� ������
		//8 9 a b c d e f
		var
		//	col = ['f', 'c', '9', '6'],
		//	col = ['f', 'd', 'b', '9'],
			col = ['f', 'c', '9'],
			bg = '',
			i = 0;
		for(var r = 0; r < col.length; r++)
			for(var g = 0; g < col.length; g++)
				for(var b = 0; b < col.length; b++) {
					var rgb = col[r] + col[g] + col[b];
					bg += '<div class="bg" val="' + rgb + '" style="background-color:#' + rgb + '"></div>';
				}

		var html =
			'<div id="setup-status-color-tab">' +
				bg +
			'</div>',
		dialog = _dialog({
			width:600,
			head:'����� ����� ��� �������',
			content:html,
			butSubmit:'',
			butCancel:'�������'
		});
		$('.bg').click(function() {
			dialog.close();
			var color = $(this).attr('val');
			$('#color')
				.css('background-color', '#' +color)
				.attr('val', color);
		});
	},
	setupTovarCategoryEdit = function(o) {
		o = $.extend({
			id:0,
			name:''
		}, o);

		var t = $(this),
			html = '<table id="setup-tab">' +
					'<tr><td class="label r">������������:<td><input id="name" type="text" value="' + o.name + '" />' +
				'</table>',
			dialog = _dialog({
				head:(o.id ? '��������������' : '�������� �����' ) + ' ��������� �������',
				content:html,
				butSubmit:o.id ? '���������' : '������',
				submit:submit
			});

		$('#name').focus();

		function submit() {
			var send = {
				op:'setup_tovar_category_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val()
			};
			if(!send.name) {
				dialog.err('�� ������� ������������');
				$('#name').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#spisok').html(res.html);
					sortable();
					dialog.close();
					_msg();
				} else
					dialog.abort();
			}, 'json');
		}
	};

$(document)
	.on('click', '#setup_worker .add', function() {
		var html =
			'<div id="worker-add">' +
				'<h1>������� ����� �������� ������������ ��� ��� id ���������:</h1>' +
				'<div class="_info">������ ������ ����� ���� ��������� �����:<br />' +
					'<u>http://vk.com/id12345</u>, <u>http://vk.com/durov</u>.<br />' +
					'���� ����������� ID ������������: <u>id12345</u>, <u>durov</u>, <u>12345</u>.' +
				'</div>' +

				'<table id="wa-find">' +
					'<tr><td><input type="text" id="viewer_id" />' +
						'<td id="msg"><span>������������ �� ������</span>' +
					'<tr><td colspan="2" id="vkuser">' +
				'</table>' +

				'<div id="manual"><a>��� ��������� ������ �������..</a></div>' +
				'<table id="manual-tab">' +
					'<tr><td class="label r">���:<td><input type="text" id="first_name" />' +
					'<tr><td class="label r">�������:<td><input type="text" id="last_name" />' +
					'<tr><td class="label r">���:<td><input type="hidden" id="sex" />' +
					'<tr><td class="label r">���������:<td><input type="text" id="post" />' +
				'</table>' +
			'</div>',
			dialog = _dialog({
				top:50,
				width:440,
				head:'���������� ������ ����������',
				content:html,
				butSubmit:'��������',
				submit:submit
			}),
			viewer_id = 0;

		$('#viewer_id')
			.focus()
			.keyEnter(user_find)
			.keyup(user_find);
		$('#manual').click(function() {
			$(this)
				.hide()
				.next().show();
			$('#wa-find').remove();
			viewer_id = 0;
			$('#viewer_id').val('');
			$('#first_name').focus();
		});
		$('#sex')._radio({
			light:1,
			block:0,
			spisok:[
				{uid:2, title:'�'},
				{uid:1, title:'�'}
			],
			func:function() {
				$('#post').focus();
			}
		});

		function user_find() {
			if($('#msg').hasClass('_busy'))
				return;

			viewer_id = 0;
			$('#vkuser').html('');

			var send = {
				user_ids:$.trim($('#viewer_id').val()),
				fields:'photo_50',
				v:5.2
			};

			if(!send.user_ids)
				return;
			if(/vk.com/.test(send.user_ids))
				send.user_ids = send.user_ids.split('vk.com/')[1];
			if(/\?/.test(send.user_ids))
				send.user_ids = send.user_ids.split('?')[0];
			if(/#/.test(send.user_ids))
				send.user_ids = send.user_ids.split('#')[0];

			$('#msg')
				.addClass('_busy')
				.find('span').hide();

			VK.api('users.get', send, function(data) {
				$('#msg').removeClass('_busy');
				if(data.response) {
					var u = data.response[0],
						html =
							'<table>' +
								'<tr><td><img src=' + u.photo_50 + '>' +
									'<td>' + u.first_name + ' ' + u.last_name +
							'</table>';
					$('#vkuser').html(html);
					viewer_id = u.id;
				} else
					$('#msg span').show();
			});
		}
		function submit() {
			var send = {
				op:'setup_worker_add',
				viewer_id:viewer_id,
				first_name:$('#first_name').val(),
				last_name:$('#last_name').val(),
				sex:$('#sex').val(),
				post:$('#post').val()
			};
			if(!send.viewer_id && !send.first_name && !send.last_name) dialog.err('����������� ����� ������������ ��� ������� ������ �������');
			else if(send.first_name && send.last_name && send.sex == 0) dialog.err('�� ������ ���');
			else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('����� ��������� ������� ��������.');
						$('#spisok').html(res.html);
					} else {
						dialog.abort();
						dialog.err(res.text);
					}
				}, 'json');
			}
		}
	})

	.on('click', '.history-view-worker-all', function() {//��������� ���� ������� �������� ����� ��� ���� �����������
		var spisok = '';
		for(var n = 0; n < WORKER_SPISOK.length; n++) {
			var sp = WORKER_SPISOK[n];
			if(sp.uid >= VIEWER_MAX)
				continue;
			spisok += '<tr><td class="label w150">' + sp.title + ':' +
						  '<td><input type="hidden" id="hv' + sp.uid + '" value="' + RULE_HISTORY_ALL[sp.uid] + '" />';
		}
		var html =
				'<center><b>��������� ������� ��������:</b></center>' +
				'<table id="setup-tab">' +
					spisok +
				'</table>',
			dialog = _dialog({
				head:'����� ������� ��������',
				content:html,
				butSubmit:'���������',
				submit:submit
			}),
			inp = $('#setup-tab').find('input');

		for(n = 0; n < inp.length; n++)
			inp.eq(n)._dropdown({
				spisok:[
					{uid:0,title:'���'},
					{uid:1,title:'������ ����'},
					{uid:2,title:'��� �������'}
				]
			});

		function submit() {
			var v = [];
			for(n = 0; n < inp.length; n++) {
				var eq = inp.eq(n),
					id = eq.attr('id').split('hv')[1];
				v.push(id + ':' + eq.val());
			}

			var send = {
				op:'setup_history_view_worker_all',
				v:v.join()
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
	})

	.on('click', '.service-toggle', function() {
		var t = $(this),
			p = _parent(t, '.unit'),
			h1 = p.find('h1'),
			send = {
				op:'setup_service_toggle',
				id:p.attr('val')
			};
		if(h1.hasClass('_busy'))
			return;
		h1.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			h1.removeClass('_busy');
			if(res.success) {
				p[(res.on ? 'add' : 'remove') + 'Class']('on');
				_msg();
			}
		}, 'json');
	})
	.on('click', '#setup-service .img_edit', function() {
		var t = $(this),
			p = _parent(t, '.unit'),
			html = '<table id="setup-service-edit">' +
				'<tr><td class="label r">��������:<td><input id="name" type="text" value="' + p.find('.name').val() + '" />' +
				'<tr><td class="label r">���������:<td><input id="head" type="text" value="' + p.find('h1').html() + '" />' +
				'<tr><td class="label r topi">��������:<td><textarea id="about">' + p.find('h2').html() + '</textarea>' +
				'</table>',
			dialog = _dialog({
				top:20,
				width:520,
				head:'�������������� ���� ������������',
				content:html,
				butSubmit:'���������',
				submit:submit
			});
		$('#name').focus();
		$('#about').autosize();
		function submit() {
			var send = {
				op:'setup_service_edit',
				id:p.attr('val'),
				name:$('#name').val(),
				head:$('#head').val(),
				about:$('#about').val()
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
	})

	.on('click', '#setup_expense .add', setupExpenseEdit)
	.on('click', '#setup_expense .img_edit', function() {
		var t = _parent($(this), 'DD');
		setupExpenseEdit({
			id:t.attr('val'),
			name:t.find('.name').html().replace(/\"/g, '&quot;')
		});

	})
	.on('click', '#setup_expense .img_del', function() {
		_dialogDel({
			id:_parent($(this), 'DD').attr('val'),
			head:'��������� �������� �����������',
			op:'expense_category_del',
			func:function(res) {
				$('#spisok').html(res.html);
				sortable();
			}
		});
	})

	.on('click', '#setup_expense_sub .add', setupExpenseSubEdit)
	.on('click', '#setup_expense_sub .img_edit', function() {
		var t = _parent($(this));
		setupExpenseSubEdit({
			id:t.attr('val'),
			name:t.find('.name').html().replace(/\"/g, '&quot;')
		});

	})
	.on('click', '#setup_expense_sub .img_del', function() {
		_dialogDel({
			id:_parent($(this)).attr('val'),
			head:'������������ ������� �����������',
			op:'expense_category_sub_del',
			func:function(res) {
				$('#spisok').html(res.html);
			}
		});
	})

	.on('click', '#setup_rubric .img_edit', function() {
		var t = _parent($(this), 'DD');
		setupRubricEdit({
			id:t.attr('val'),
			name:t.find('.name a').html().replace(/\"/g, '&quot;')
		});

	})
	.on('click', '#setup_rubric .img_del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'������� ����������',
			op:'setup_rubric_del',
			func:function(res) {
				$('#spisok').html(res.html);
				sortable();
			}
		});
	})

	.on('click', '#setup_rubric_sub .img_edit', function() {
		var t = _parent($(this), 'DD');
		setupRubricSubEdit({
			id:t.attr('val'),
			name:t.find('.name').html().replace(/\"/g, '&quot;')
		});

	})
	.on('click', '#setup_rubric_sub .img_del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'����������',
			op:'setup_rubric_sub_del',
			func:function(res) {
				$('#spisok').html(res.html);
				sortable();
			}
		});
	})

	.on('click', '#setup_zayav_expense .add', setupZayavExpense)
	.on('click', '#setup_zayav_expense .img_edit', function() {
		var t = _parent($(this), 'DD');
		setupZayavExpense({
			id:t.attr('val'),
			name:t.find('.name').html(),
			dop:t.find('.hdop').val()
		});
	})
	.on('click', '#setup_zayav_expense .img_del', function() {
		_dialogDel({
			id:_parent($(this), 'DD').attr('val'),
			head:'��������� ������� ������',
			op:'setup_zayav_expense_del',
			func:function(res) {
				$('#spisok').html(res.html);
				sortable();
			}
		});
	})

	.on('click', '#setup_zayav_status .status-add', setupZayavStatus)
	.on('click', '#setup_zayav_status .status-edit', function() {
		var t = _parent($(this), 'DD');
		setupZayavStatus({
			id:t.attr('val'),
			name:t.find('.name span').html(),
			about:t.find('.about').html(),
			color:t.find('.name').attr('val'),
			default:t.find('.name').hasClass('b') ? 1 : 0,
			nouse:t.find('.nouse').val(),
			next:t.find('.next').val().length > 1 ? 1 : 0,
			next_ids:t.find('.next').val(),
			hide:t.find('.hide').val(),
			srok:t.find('.srok').val(),
			executer:t.find('.executer').val(),
			accrual:t.find('.accrual').val(),
			remind:t.find('.remind').val(),
			day_fact:t.find('.day_fact').val()
		});
	})
	.on('click', '#setup_zayav_status .status-del', function() {
		_dialogDel({
			id:_parent($(this), 'DD').attr('val'),
			head:'������� ������',
			op:'setup_zayav_status_del',
			func:function(res) {
				$('#status-spisok').html(res.html);
				sortable();
			}
		});
	})

	.on('click', '#setup-cartridge .img_edit', function() {
		var t = $(this),
			id = t.attr('val'),
			p = _parent(t),
			name = p.find('.name').html(),
			type_id = p.find('.type_id').val(),
			filling = p.find('.filling').val(),
			restore = p.find('.restore').val(),
			chip = p.find('.chip').val(),
			html = '<table id="setup-tab">' +
				'<tr><td class="label">���:<td><input type="hidden" id="type_id" value="' + type_id + '" />' +
				'<tr><td class="label"><b>������ ���������:</b><td><input type="text" id="name" value="' + name + '" />' +
				'<tr><td class="label">��������:<td><input type="text" id="cost_filling" class="money" maxlength="11" value="' + filling + '" /> ���.' +
				'<tr><td class="label">��������������:<td><input type="text" id="cost_restore" class="money" maxlength="11" value="' + restore + '" /> ���.' +
				'<tr><td class="label">������ ����:<td><input type="text" id="cost_chip" class="money" maxlength="11" value="' + chip + '" /> ���.' +
				'<tr><td><td>' +
				'<tr><td class="label">����������:<td><input type="hidden" id="join" />' +
				'<tr class="tr-join dn"><td class="label">� ����������:<td><input type="hidden" id="join_id" />' +
				'</table>',
			dialog = _dialog({
				top:40,
				width:400,
				head:'�������������� ������ ���������',
				content:html,
				butSubmit:'���������',
				submit:submit
			});
		$('#type_id')._select({
			spisok:CARTRIDGE_TYPE
		});
		$('#name').focus();
		$('#join')._check({
			func:function(v) {
				$('.tr-join')[(v ? 'remove' : 'add') + 'Class']('dn');
			}
		});
		var spisok = [];
		for(var n = 0; n < CARTRIDGE_SPISOK.length; n++) {
			var sp = CARTRIDGE_SPISOK[n];
			if(sp.uid == id)
				continue;
			spisok.push(sp);
		}
		$('#join_id')._select({
			width:218,
			write:1,
			title0:'�� �������',
			spisok:spisok
		});
		function submit() {
			var join = _num($('#join').val()),
				send = {
					op:'cartridge_edit',
					id:id,
					type_id:$('#type_id').val(),
					name:$('#name').val(),
					cost_filling:_num($('#cost_filling').val()),
					cost_restore:_num($('#cost_restore').val()),
					cost_chip:_num($('#cost_chip').val()),
					join_id:join ? _num($('#join_id').val()) : 0
				};
			if(!send.name) {
				dialog.err('�� ������� ������������');
				$('#name').focus();
				return;
			}
			if(join && !send.join_id) {
				dialog.err('�� ������ �������� ��� �����������');
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#spisok').html(res.html);
					CARTRIDGE_SPISOK = res.cart;
					dialog.close();
					_msg();
				} else
					dialog.abort();
			}, 'json');
		}
	})
	.on('mouseleave', '#setup-cartridge .edited', function() {//�������� ��������� ������������������ ���������
		$(this).css('background-color', '#fff');
	})

	.on('click', '#setup_tovar_category #add', setupTovarCategoryEdit)
	.on('click', '#setup_tovar_category .img_edit', function() {
		var t = _parent($(this));
		setupTovarCategoryEdit({
			id:t.attr('val'),
			name:t.find('.name').html()
		});
	})
	.on('click', '#setup_tovar_category .img_del', function() {
		var p = _parent($(this));
		_dialogDel({
			id:p.attr('val'),
			head:'��������� �������',
			op:'setup_tovar_category_del',
			func:function() {
				p.remove();
			}
		});
	})
	.on('click', '#setup_tovar_category #join', function() {//����������� ��������� �� ������� ���������
		var dialog = _dialog({
			top:30,
			width:420,
			head:'����������� ��������� ������� �� ������� ���������',
			load:1,
			butSubmit:'������',
			submit:submit
		});


		$.post(AJAX_MAIN, {op:'setup_tovar_category_join_load'}, function(res) {
			if(res.success)
				dialog.content.html(res.html);
			else
				dialog.loadError();
		}, 'json');


		function submit() {
			var send = {
				op:'setup_tovar_category_join_save',
				ids:_checkAll()
			};
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#spisok').html(res.html);
					sortable();
					dialog.close();
					_msg();
				} else
					dialog.loadError();
			}, 'json');
		}
	})

	.on('click', '#setup_salary_list .vk', function() {//���������� ��������� ����� ������
		var t = $(this);
		if(t.hasClass('_busy'))
			return;

		var send = {
			op:'setup_salary_list',
			ids:_checkAll()
		};
		t.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			t.removeClass('_busy');
			if(res.success)
				_msg();
		}, 'json');
	})

	.ready(function() {
		if($('#setup_my').length) {
			$('#pinset').click(function() {
				var html =
						'<table id="setup-tab">' +
							'<tr><td class="label">����� ���-���:<td><input id="pin" type="password" maxlength="10" />' +
						'</table>',
					dialog = _dialog({
						width:300,
						head:'��������� ������ ���-����',
						content:html,
						butSubmit:'����������',
						submit:submit
					});
				$('#pin').focus();
				function submit() {
					var send = {
						op:'setup_my_pinset',
						pin:$.trim($('#pin').val())
					};
					if(!send.pin) {
						dialog.err('������� ���-���');
						$('#pin').focus();
					} else if(send.pin.length < 3) {
						dialog.err('����� ���-���� �� 3 �� 10 ��������');
						$('#pin').focus();
					} else {
						dialog.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								dialog.close();
								_msg('���-��� ����������');
								document.location.reload();
							} else
								dialog.abort();
						}, 'json');
					}
				}
			});
			$('#pinchange').click(function() {
				var html = '<table id="setup-tab">' +
						'<tr><td class="label">������� ���-���:<td><input id="oldpin" type="password" maxlength="10" />' +
						'<tr><td class="label">����� ���-���:<td><input id="pin" type="password" maxlength="10" />' +
						'</table>',
					dialog = _dialog({
						width:300,
						head:'��������� ���-����',
						content:html,
						butSubmit:'��������',
						submit:submit
					});
				$('#oldpin').focus();
				function submit() {
					var send = {
						op:'setup_my_pinchange',
						oldpin: $.trim($('#oldpin').val()),
						pin: $.trim($('#pin').val())
					};
					if(!send.oldpin || !send.pin)
						dialog.err('��������� ��� ����');
					else if(send.oldpin.length < 3 || send.pin.length < 3)
						dialog.err('����� ���-���� �� 3 �� 10 ��������');
					else {
						dialog.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								dialog.close();
								_msg('���-��� ������.');
								document.location.reload();
							} else {
								dialog.abort();
								dialog.err(res.text);
							}
						}, 'json');
					}
				}
			});
			$('#pindel').click(function() {
				var html =
						'<table id="setup-tab">' +
							'<tr><td class="label">������� ���-���:<td><input id="oldpin" type="password" maxlength="10" />' +
						'</table>',
					dialog = _dialog({
						width:300,
						head:'�������� ���-����',
						content:html,
						butSubmit:'���������',
						submit:submit
					});
				$('#oldpin').focus();
				function submit() {
					var send = {
						op:'setup_my_pindel',
						oldpin:$.trim($('#oldpin').val())
					};
					if(!send.oldpin) {
						dialog.err('���� �� ���������');
						$('#oldpin').focus();
					} else {
						dialog.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								dialog.close();
								_msg('���-��� �����');
								document.location.reload();
							} else {
								dialog.abort();
								dialog.err(res.text);
							}
						}, 'json');
					}
				}
			});
			$('#RULE_MY_PAY_SHOW_PERIOD')._select({
				spisok:[
					{uid:1,title:'�� ������� ����'},
					{uid:2,title:'�� ������� ������'},
					{uid:3,title:'�� ������� �����'}
				],
				func:setupRuleCheck
			});
		}
		if($('#setup_rule').length) {
			$('.img_del').click(function() {
				_dialogDel({
					id:RULE_VIEWER_ID,
					head:'����������',
					op:'setup_worker_del',
					func:function() {
						location.href = URL + '&p=setup&d=worker';
					}
				});
			});
			$('#w-save').click(function() {
				var send = {
						op:'setup_worker_save',
						viewer_id:RULE_VIEWER_ID,
						first_name:$('#first_name').val(),
						last_name:$('#last_name').val(),
						middle_name:$('#middle_name').val(),
						post:$('#post').val()
					},
					but = $(this);
				if(!send.first_name) {
					err('�� ������� ���');
					$('#first_name').focus();
					return;
				}
				if(!send.last_name) {
					err('�� ������� �������');
					$('#last_name').focus();
					return;
				}
				but.addClass('_busy');
				$.post(AJAX_MAIN, send, function(res) {
					but.removeClass('_busy');
					if(res.success)
						_msg('���������');
				}, 'json');
				function err(msg) {
					but.vkHint({
						msg:'<SPAN class="red">' + msg + '</SPAN>',
						top:-57,
						left:-6,
						indent:40,
						show:1,
						remove:1
					});
				}
			});
			$('#RULE_SALARY_SHOW')._check(setupRuleCheck);
			$('#RULE_EXECUTER')._check(setupRuleCheck);
			$('#RULE_SALARY_ZAYAV_ON_PAY')._check(setupRuleCheck);
			$('#RULE_SALARY_BONUS')._check(function(v, id) {
				var t = $(this);
				$('#' + id + '_check').next()[(v ? 'remove' : 'add') + 'Class']('vh');
				setupRuleCheck(v, id);
				$('#salary_bonus_sum').focus()
			});
			$('#salary_bonus_sum').keyEnter(function() {
				var o = $('#salary_bonus_sum'),
					send = {
						op:'salary_bonus_sum',
						worker_id:RULE_VIEWER_ID,
						sum:_cena(o.val())
					};

				if(!send.sum || send.sum > 100)
					return err('����������� ������� ��������');

				o.attr('disabled', 'disabled');

				$.post(AJAX_MAIN, send, function(res) {
					o.attr('disabled', false);
					if(res.success)
						_msg();
					else {
						err(res.text);
						o.focus();
					}
				}, 'json');

				function err(msg) {
					o.vkHint({
						msg:'<span class="red">' + msg + '</span>',
						top:-80,
						left:-9,
						indent:40,
						show:1,
						remove:1
					});
					return false;
				}
			});
			$('#RULE_APP_ENTER')._check(function(v, id) {
				$('#div-app-enter')[(v ? 'remove' : 'add') + 'Class']('dn');
				setupRuleCheck(v, id);
			});
			$('#RULE_SETUP_WORKER')._check(function(v, id) {
				$('#div-w-rule')[v ? 'show' : 'hide']();
				setupRuleCheck(v, id);
				$('#RULE_SETUP_RULES')._check(0);
			});
			$('#RULE_SETUP_RULES')._check(setupRuleCheck);
			$('#RULE_SETUP_REKVISIT')._check(setupRuleCheck);
			$('#RULE_SETUP_INVOICE')._check(setupRuleCheck);
			$('#RULE_SETUP_ZAYAV_STATUS')._check(setupRuleCheck);
			$('#RULE_HISTORY_VIEW')._dropdown({
				spisok:RULE_HISTORY_SPISOK,
				func:setupRuleCheck
			});
			$('#RULE_INVOICE_HISTORY')._check(setupRuleCheck);
			$('#RULE_INVOICE_TRANSFER')._dropdown({
				spisok:RULE_INVOICE_TRANSFER_SPISOK,
				func:setupRuleCheck
			});
			$('#RULE_INCOME_VIEW')._check(setupRuleCheck);
			$('#pin-clear').click(function() {
				var send = {
						op:'setup_worker_pin_clear',
						viewer_id:RULE_VIEWER_ID
					},
					but = $(this);
				if(but.hasClass('_busy'))
					return;
				but.addClass('_busy');
				$.post(AJAX_MAIN, send, function(res) {
					but.removeClass('_busy');
					if(res.success) {
						_msg('���-��� �������');
						but.prev().remove();
						but.remove();
					}
				}, 'json');
			});
		}
		if($('#setup_rekvisit').length) {
			$('#type_id')._select({width:245,spisok:APP_TYPE});
			$('textarea').autosize();
			$('.vk').click(function() {
				var t = $(this),
					send = {
						op:'setup_rekvisit',
						type_id:$('#type_id').val(),
						name:$('#name').val(),
						name_yur:$('#name_yur').val(),
						ogrn:$('#ogrn').val(),
						inn:$('#inn').val(),
						kpp:$('#kpp').val(),
						phone:$('#phone').val(),
						fax:$('#fax').val(),
						adres_yur:$('#adres_yur').val(),
						adres_ofice:$('#adres_ofice').val(),
						time_work:$('#time_work').val(),
						bank_name:$('#bank_name').val(),
						bank_bik:$('#bank_bik').val(),
						bank_account:$('#bank_account').val(),
						bank_account_corr:$('#bank_account_corr').val()
					};
				t.addClass('_busy');
				$.post(AJAX_MAIN, send, function(res) {
					t.removeClass('_busy');
					if(res.success)
						_msg('���������� ���������.');
				}, 'json');
			});
		}
	});
