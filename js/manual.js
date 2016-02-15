$(document)
	.on('click', '#manual .add', function() {
		var html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">��������:<td><input type="text" id="name" />' +
				'</table>',
			dialog = _dialog({
				head:'�������� ������ �������',
				content:html,
				submit:submit
			});

		$('#name').focus().keyEnter(submit);

		function submit() {
			var send = {
				op:'manual_part_add',
				name:$('#name').val()
			};
			if(!send.name) {
				dialog.err('�� ������� ��������');
				$('#name').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					$('#part-spisok').html(res.html);
				} else
					dialog.abort();
			}, 'json');
		}
	})
	.on('click', '.part-sub-add', function() {//�������� ������ ����������
		var t = $(this),
			html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">������:<td><b>' + t.parent().find('.name').text() + '</b>' +
					'<tr><td class="label">���������:<td><input type="text" id="name" />' +
				'</table>',
			dialog = _dialog({
				head:'�������� ������ ����������',
				content:html,
				submit:submit
			});

		$('#name').focus().keyEnter(submit);

		function submit() {
			var send = {
				op:'manual_part_sub_add',
				id:t.attr('val'),
				name:$('#name').val()
			};
			if(!send.name) {
				dialog.err('�� ������� ��������');
				$('#name').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					$('#part-spisok').html(res.html);
				} else
					dialog.abort();
			}, 'json');
		}
	});

