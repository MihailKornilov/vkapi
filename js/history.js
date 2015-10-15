$(document)
	.on('click', '#_hist-next', function() {
		var t = $(this);
		if(t.hasClass('busy'))
			return;
		HIST.op = 'history_spisok';
		HIST.page = $(this).attr('val');
		t.addClass('busy');
		$.post(AJAX_MAIN, HIST, function(res) {
			if(res.success)
				t.after(res.html).remove();
			else
				t.removeClass('busy');
		}, 'json');
	})

	.on('click', '#sa-history .add', function() {
		var html =
				'<table class="sa-tab" id="sa-history-tab">' +
					'<tr><td class="label">type_id:<td><input type="text" id="type_id" />' +
					'<tr><td class="label">�����:<td><input type="text" id="txt" />' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:580,
				head:'�������� ����� ��������� ��� ������� ��������',
				content:html,
				submit:submit
			});

		$('#txt').focus();

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
					} else
						dialog.abort();
				}, 'json');
			}
		}
	})

	.ready(function() {

	});
