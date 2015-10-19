$(document)
	.on('click', '#sa-history .img_edit', function() {
		var id = _num($(this).attr('val')),
			html =
				'<table class="sa-tab" id="sa-history-tab">' +
					'<tr><td class="label">type_id:<td><input type="text" id="type_id" value="' + id + '" />' +
					'<tr><td><td>���� ���������� <b>type_id</b>, ��� ������ �� ���������� ��������� ����� �������� �� �����.' +
					'<tr><td class="label topi">�����:<td><textarea id="txt">' + $('#txt' + id).val() + '</textarea>' +
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

		function submit() {
			var send = {
				op:'sa_history_type_edit',
				type_id_current:id,
				type_id:_num($('#type_id').val()),
				txt:$('#txt').val()
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

	.ready(function() {
		if($('#sa-history').length) {
			$('textarea').autosize();
			$('.add').click(function() {
				var html =
						'<table class="sa-tab" id="sa-history-tab">' +
							'<tr><td class="label">type_id:<td><input type="text" id="type_id" />' +
							'<tr><td class="label topi">�����:<td><textarea id="txt"></textarea>' +
						'</table>',
					dialog = _dialog({
						top:30,
						width:520,
						head:'�������� ����� ��������� ��� ������� ��������',
						content:html,
						submit:submit
					});

				$('#txt').focus().autosize();

				function submit() {
					var send = {
						op:'sa_history_type_add',
						type_id:$('#type_id').val(),
						txt:$('#txt').val()
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
	});
