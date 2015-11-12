var setupRuleCheck = function(v, id) {
	var send = {
		op:id,
		viewer_id:RULE_VIEWER_ID,
		v:v
	};
	$.post(AJAX_MAIN, send, function(res) {
		if(res.success)
			_msg('���������');
	}, 'json');
},
	setupInvoiceTab = function(dialog, arr) {
		arr = $.extend({
			id:0,
			name:'',
			about:'',
			income:0,
			transfer:0,
			visible:''
		}, arr);

		var html =
			'<table id="setup-tab">' +
				'<tr><td class="label">������������:<td><input id="name" type="text" value="' + arr.name + '" />' +
				'<tr><td class="label topi">��������:<td><textarea id="about">' + arr.about + '</textarea>' +
				'<tr><td class="label">������������� �����������:<td><input type="hidden" id="income" value="' + arr.income + '" />' +
				'<tr><td class="label">������������� ��������:<td><input type="hidden" id="transfer" value="' + arr.transfer + '" />' +
				'<tr><td class="label topi">��������� ��� �����������:<td><input type="hidden" id="visible" value="' + arr.visible + '" />' +
			'</table>';

		dialog.content.html(html);
		dialog.submit(submit);

		$('#name').focus().keyEnter(submit);
		$('#about').autosize();
		$('#income')._check();
		$('#income_check').vkHint({
			msg:'����������� ��������� ������������� ����������� ������� �� ����',
			width:180,
			top:-84,
			left:-85,
			delayShow:500
		});
		$('#transfer')._check();
		$('#visible')._select({
			width:218,
			title0:'���������� �� �������',
			multiselect:1,
			spisok:_toSpisok(WORKER_ASS)
		});
		function submit() {
			var send = {
				op:arr.id ? 'setup_invoice_edit' : 'setup_invoice_add',
				id:arr.id,
				name:$('#name').val(),
				about:$('#about').val(),
				income:$('#income').val(),
				transfer:$('#transfer').val(),
				visible:$('#visible').val()
			};
			if(!send.name) {
				dialog.err('�� ������� ������������');
				$('#name').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						$('.spisok').html(res.html);
						dialog.close();
						_msg('���������');
					} else {
						dialog.abort();
						dialog.err(res.text);
					}
				}, 'json');
			}
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

	.on('click', '#setup_invoice .add', function() {
		var dialog = _dialog({
			top:40,
			width:430,
			head:'���������� ������ �����'
		});
		setupInvoiceTab(dialog);
	})
	.on('click', '#setup_invoice .img_edit', function() {
		var t = _parent($(this)),
			dialog = _dialog({
				top:40,
				width:430,
				head:'�������������� �����',
				butSubmit:'���������'
			}),
			arr = {
				id:t.attr('val'),
				name:t.find('.name div').html(),
				about:t.find('.name pre').html(),
				income:t.find('.confirm_income').val(),
				transfer:t.find('.confirm_transfer').val(),
				visible:t.find('.visible_id').val()
			};
		setupInvoiceTab(dialog, arr);
	})
	.on('click', '#setup_invoice .img_del', function() {
		var t = $(this),
			dialog = _dialog({
				top:90,
				width:300,
				head:'�������� �����',
				content:'<center><b>����������� �������� �����.</b></center>',
				butSubmit:'�������',
				submit:submit
			});
		function submit() {
			while(t[0].tagName != 'TR')
				t = t.parent();
			var send = {
				op:'setup_invoice_del',
				id:t.attr('val')
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('.spisok').html(res.html);
					dialog.close();
					_msg('�������!');
				} else
					dialog.abort();
			}, 'json');
		}
	})

	.on('click', '#setup_expense .add', function() {
		var t = $(this),
			html = '<table id="setup-tab">' +
				'<tr><td class="label r">������������:<td><input id="name" type="text" />' +
				'<tr><td class="label r">������ �����������:<td><input id="worker_use" type="hidden" />' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:'���������� ����� ��������� ������� �����������',
				content:html,
				submit:submit
			});
		$('#name').focus().keyEnter(submit);
		$('#worker_use')._check();
		function submit() {
			var send = {
				op:'expense_category_add',
				name:$('#name').val(),
				worker_use:$('#worker_use').val()
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
						_msg('�������');
						sortable();
					} else
						dialog.abort();
				}, 'json');
			}
		}
	})
	.on('click', '#setup_expense .img_edit', function() {
		var t = _parent($(this), 'DD'),
			id = t.attr('val'),
			name = t.find('.name').html(),
			worker_use = t.find('.worker_use').html() ? 1 : 0,
			html = '<table id="setup-tab">' +
				'<tr><td class="label r">������������:<td><input id="name" type="text" value="' + name + '" />' +
				'<tr><td class="label r">������ �����������:<td><input id="worker_use" type="hidden" value="' + worker_use + '" />' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:'�������������� ��������� ������� �����������',
				content:html,
				butSubmit:'���������',
				submit:submit
			});
		$('#name').focus().keyEnter(submit);
		$('#worker_use')._check();
		function submit() {
			var send = {
				op:'expense_category_edit',
				id:id,
				name:$('#name').val(),
				worker_use:$('#worker_use').val()
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
						_msg('���������!');
						sortable();
					} else
						dialog.abort();
				}, 'json');
			}
		}
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
				$('#pin').focus().keyEnter(submit);
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
				$('#oldpin').focus().keyEnter(submit);
				$('#pin').keyEnter(submit);
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
				$('#oldpin').focus().keyEnter(submit);
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
				} else if(!send.last_name) {
					err('�� ������� �������');
					$('#last_name').focus();
				} else {
					but.addClass('busy');
					$.post(AJAX_MAIN, send, function(res) {
						but.removeClass('busy');
						if(res.success)
							_msg('���������');
					}, 'json');
				}
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
			$('#RULE_HISTORY_VIEW')._check(setupRuleCheck);
			$('#RULE_INVOICE_TRANSFER')._check(setupRuleCheck);
			$('#RULE_INCOME_VIEW')._check(setupRuleCheck);
			$('#pin-clear').click(function() {
				var send = {
						op:'setup_worker_pin_clear',
						viewer_id:RULE_VIEWER_ID
					},
					but = $(this);
				if(but.hasClass('busy'))
					return;
				but.addClass('busy');
				$.post(AJAX_MAIN, send, function(res) {
					but.removeClass('busy');
					if(res.success) {
						_msg('���-��� �������');
						but.prev().remove();
						but.remove();
					}
				}, 'json');
			});
		}
	});
