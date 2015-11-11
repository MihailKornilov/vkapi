var balansCategory = function(arr) {
			arr = $.extend({
				id:0,
				name:''
			}, arr);

			var html =
				'<table class="sa-tab">' +
					'<tr><td class="label">��������:<td><input type="text" id="name" value="' + arr.name + '" />' +
				'</table>',
			dialog = _dialog({
				head:(arr.id ? '��������������' : '�������� �����' ) + ' ��������� ��������',
				content:html,
				butSubmit:arr.id ? '���������' : '������',
				submit:submit
			});

		$('#name').focus().keyEnter(submit);

		function submit() {
			var send = {
				op:'sa_balans_category_' + (arr.id ? 'edit' : 'add'),
				id:arr.id,
				name:$('#name').val()
			};
			if(!send.name) {
				dialog.err('�� ������� ��������');
				$('#name').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('���������');
						$('#category-spisok').html(res.html);
					} else
						dialog.abort();
				}, 'json');
			}
		}
	},
	balansAction = function(arr) {
		arr = $.extend({
			id:0,
			name:'',
			minus:0
		}, arr);

		var html =
				'<table class="sa-tab">' +
					'<tr><td class="label">��������:<td><input type="text" id="name" value="' + arr.name + '" />' +
					'<tr><td class="label">�����:<td><input type="hidden" id="minus" value="' + arr.minus + '" />' +
				'</table>',
			dialog = _dialog({
				head:(arr.id ? '��������������' : '�������� ������' ) + ' ��������',
				content:html,
				butSubmit:arr.id ? '���������' : '������',
				submit:submit
			});

		$('#name').focus().keyEnter(submit);
		$('#minus')._check();

		function submit() {
			var send = {
				op:'sa_balans_action_' + (arr.id ? 'edit' : 'add'),
				id:arr.id,
				name:$('#name').val(),
				minus:$('#minus').val()
			};
			if(!send.name) {
				dialog.err('�� ������� ��������');
				$('#name').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('���������');
						$('#action-spisok').html(res.html);
					} else
						dialog.abort();
				}, 'json');
			}
		}
	};

$(document)
	.on('click', '#sa-menu .add', function() {
		var html =
				'<table class="sa-tab" id="sa-menu-tab">' +
					'<tr><td class="label">��������:<td><input type="text" id="name" />' +
					'<tr><td class="label">p:<td><input type="text" id="p" />' +
				'</table>',
			dialog = _dialog({
				head:'�������� ������ ������� ����',
				content:html,
				submit:submit
			});

		$('#name').focus();
		$('#name,#p').keyEnter(submit);

		function submit() {
			var send = {
				op:'sa_menu_add',
				name:$('#name').val(),
				p:$('#p').val()
			};
			if(!send.name) {
				dialog.err('�� ������� ��������');
				$('#name').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('�������');
						$('#spisok').html(res.html);
						sortable();
					} else
						dialog.abort();
				}, 'json');
			}
		}
	})
	.on('click', '#sa-menu ._check', function() {//�������-����� �������� ����
		var t = $(this),
			send = {
			op:'sa_menu_show',
			id:_parent(t, 'DD').attr('val'),
			v:t.find('input').val()
		};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				_msg('���������');
		}, 'json');
	})

	.on('click', '#sa-history .img_edit', function() {
		var id = _num($(this).attr('val')),
			html =
				'<table class="sa-tab" id="sa-history-tab">' +
					'<tr><td class="label">type_id:<td><input type="text" id="type_id" value="' + id + '" />' +
					'<tr><td><td>���� ���������� <b>type_id</b>, ��� ������ �� ���������� ��������� ����� �������� �� �����.' +
					'<tr><td class="label topi">�����:<td><textarea id="txt">' + $('#txt' + id).val() + '</textarea>' +
					'<tr><td class="label">���������:<td><input type="hidden" id="category_ids" value="' + $('#ids' + id).val() + '" />' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:520,
				head:'�������������� ��������� ������� ��������',
				content:html,
				butSubmit:'��������',
				submit:submit
			});

		$('#txt').focus().autosize();
		$('#category_ids')._select({
			width:250,
			title0:'�� �������',
			spisok:CAT,
			multiselect:1
		});

		function submit() {
			var send = {
				op:'sa_history_type_edit',
				type_id_current:id,
				type_id:_num($('#type_id').val()),
				txt:$('#txt').val(),
				category_ids:$('#category_ids').val()
			};
			if(!send.type_id) {
				dialog.err('����������� ������ type_id');
				$('#type_id').focus();
			} else if(!send.txt) {
				dialog.err('�� ������ ����� ���������');
				$('#txt').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('��������');
						$('#spisok').html(res.html);
						$('textarea').autosize();
					} else
						dialog.abort();
				}, 'json');
			}
		}
	})
	.on('click', '#sa-history-cat .img_edit', function() {
		var t = _parent($(this), 'DD'),
			html =
				'<table class="sa-tab">' +
					'<tr><td class="label">Name:<td><input type="text" id="name" value="' + t.find('.name b').html() + '" />' +
					'<tr><td class="label topi">About:<td><textarea id="about">' + t.find('.about').html() + '</textarea>' +
					'<tr><td class="label topi">js_use:<td><input type="hidden" id="js_use"  value="' + (t.find('.js').html() ? 1 : 0) + '" />' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:360,
				head:'�������������� ��������� ������� ��������',
				content:html,
				butSubmit:'��������',
				submit:submit
			});

		$('#name').focus();
		$('#about').autosize();
		$('#js_use')._check();

		function submit() {
			var send = {
				op:'sa_history_cat_edit',
				id:t.attr('val'),
				name:$('#name').val(),
				about:$('#about').val(),
				js_use:$('#js_use').val()
			};
			if(!send.name) {
				dialog.err('�� ������� ������������');
				$('#name').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('��������');
						$('#spisok').html(res.html);
						sortable();
					} else
						dialog.abort();
				}, 'json');
			}
		}
	})

	.on('click', '#sa-rule .img_edit', function() {
		var t = _parent($(this)),
			html =
				'<table class="sa-tab" id="sa-rule-tab">' +
					'<tr><td class="label">���������:<td><input type="text" id="key" value="' + t.find('.key b').html() + '" />' +
					'<tr><td class="label topi">��������:<td><textarea id="about">' + t.find('.about').html() + '</textarea>' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:380,
				head:'�������������� ��������� ���� �����������',
				content:html,
				butSubmit:'��������',
				submit:submit
			});

		$('#key').focus();
		$('#about').autosize();

		function submit() {
			var send = {
				op:'sa_rule_edit',
				id: t.attr('val'),
				key:$('#key').val(),
				about:$('#about').val()
			};
			if(!send.key) {
				dialog.err('�� ������ ����� ���������');
				$('#key').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('��������');
						$('#spisok').html(res.html);
					} else {
						dialog.abort();
						dialog.err(res.text);
					}
				}, 'json');
			}
		}
	})
	.on('click', '#sa-rule ._check', function() {
		var t = $(this),
			td = t.parent(),
			check = $('#' + t.find('input').attr('id')),
			send = {
				op:'sa_rule_flag',
				id:td.parent().attr('val'),
				value_name: td.attr('class'),
				v:_num(check.val())
			};
		if(td.hasClass('_busy'))
			return;
		td.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			td.removeClass('_busy');
			if(res.error)
				check._check(send.v ? 0 : 1);

		}, 'json');
	})

	.on('click', '.balans-category-edit', function() {
		var t = _parent($(this));
		balansCategory({
			id:t.attr('val'),
			name:t.find('.name').html()
		});
	})
	.on('click', '.balans-category-del', function() {
		_dialogDel({
			id:_parent($(this)).attr('val'),
			head:'��������� ��������',
			op:'sa_balans_category_del',
			func:function(res) {
				$('#category-spisok').html(res.html);
			}
		});
	})
	.on('click', '.balans-action-edit', function() {
		var t = _parent($(this));
		balansAction({
			id:t.attr('val'),
			name:t.find('.name').html()
		});
	})
	.on('click', '.balans-action-del', function() {
		_dialogDel({
			id:_parent($(this)).attr('val'),
			head:'�������� ��� ��������',
			op:'sa_balans_action_del',
			func:function(res) {
				$('#action-spisok').html(res.html);
			}
		});
	})

	.ready(function() {
		if($('#sa-history').length) {
			$('textarea').autosize();
			$('.add.const').click(function() {
				var html =
						'<table class="sa-tab" id="sa-history-tab">' +
							'<tr><td class="label">type_id:<td><input type="text" id="type_id" />' +
							'<tr><td class="label topi">�����:<td><textarea id="txt"></textarea>' +
							'<tr><td class="label">���������:<td><input type="hidden" id="category_ids" />' +
						'</table>',
					dialog = _dialog({
						top:30,
						width:520,
						head:'�������� ����� ��������� ��� ������� ��������',
						content:html,
						submit:submit
					});

				$('#txt').focus().autosize();
				$('#category_ids')._select({
					width:250,
					title0:'�� �������',
					spisok:CAT,
					multiselect:1
				});

				function submit() {
					var send = {
						op:'sa_history_type_add',
						type_id:$('#type_id').val(),
						txt:$('#txt').val(),
						category_ids:$('#category_ids').val()
					};
					if(!send.txt) {
						dialog.err('�� ������ ����� ���������');
						$('#txt').focus();
					} else {
						dialog.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								dialog.close();
								_msg('�������');
								$('#spisok').html(res.html);
								$('textarea').autosize();
							} else
								dialog.abort();
						}, 'json');
					}
				}
			});
		}
		if($('#sa-history-cat').length) {
			$('.add').click(function() {
				var html =
						'<table class="sa-tab">' +
							'<tr><td class="label">Name:<td><input type="text" id="name" />' +
							'<tr><td class="label topi">About:<td><textarea id="about"></textarea>' +
							'<tr><td class="label topi">js_use:<td><input type="hidden" id="js_use" />' +
						'</table>',
					dialog = _dialog({
						top:30,
						width:360,
						head:'�������� ����� ��������� ������� ��������',
						content:html,
						submit:submit
					});

				$('#name').focus();
				$('#about').autosize();
				$('#js_use')._check();

				function submit() {
					var send = {
						op:'sa_history_cat_add',
						name:$('#name').val(),
						about:$('#about').val(),
						js_use:$('#js_use').val()
					};
					if(!send.name) {
						dialog.err('�� ������� ������������');
						$('#name').focus();
					} else {
						dialog.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								dialog.close();
								_msg('�������');
								$('#spisok').html(res.html);
								sortable();
							} else
								dialog.abort();
						}, 'json');
					}
				}
			});
		}

		if($('#sa-rule').length) {
			$('.add').click(function() {
				var html =
						'<table class="sa-tab" id="sa-rule-tab">' +
							'<tr><td class="label">���������:<td><input type="text" id="key" />' +
							'<tr><td class="label topi">��������:<td><textarea id="about"></textarea>' +
						'</table>',
					dialog = _dialog({
						top:30,
						width:380,
						head:'�������� ����� ��������� ���� �����������',
						content:html,
						submit:submit
					});

				$('#key').focus();
				$('#about').autosize();

				function submit() {
					var send = {
						op:'sa_rule_add',
						key:$('#key').val(),
						about:$('#about').val()
					};
					if(!send.key) {
						dialog.err('�� ������ ����� ���������');
						$('#key').focus();
					} else {
						dialog.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								dialog.close();
								_msg('�������');
								$('#spisok').html(res.html);
							} else {
								dialog.abort();
								dialog.err(res.text);
							}
						}, 'json');
					}
				}
			});
		}

		if($('#sa-balans').length) {
			$('.link').click(function() {//������������ �����������
				$('.link.sel').removeClass('sel');
				var i = $(this).addClass('sel').index();
				$('.div').hide();
				$('.d' + i).show();
			});
			$('#category-add').click(balansCategory);
			$('#action-add').click(balansAction);
		}
	});
