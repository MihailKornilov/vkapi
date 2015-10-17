var _history = function(v, id) {
	HIST[id] = v;
	HIST.op = 'history_spisok';
	$('#mainLinks').addClass('busy');
	$.post(AJAX_MAIN, HIST, function(res) {
		$('#mainLinks').removeClass('busy');
		if(res.success)
			$('.left').html(res.html);
	}, 'json');
};
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
	.on('click', '._hist-un h4', function() {//�������� ������ ������� (��� SA)
		var t = $(this),
			dialog = _dialog({
				top:90,
				width:300,
				head:'�������� ������ � ������� ��������',
				content:'<center class="red">����������� �������� ������.</center>',
				butSubmit:'�������',
				submit:submit
			});
		function submit() {
			var send = {
				op:'history_del',
				id:t.attr('val')
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg('�������');
					t.parent().remove();
				} else
					dialog.abort();
			}, 'json');
		}
	})

	.on('click', '#sa-history .add', function() {
		var html =
				'<table class="sa-tab" id="sa-history-tab">' +
					'<tr><td class="label">type_id:<td><input type="text" id="type_id" />' +
					'<tr><td class="label topi">�����:<td><textarea id="txt"></textarea>' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:580,
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
	})
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

	.ready(function() {
		if($('#report.history').length) {
			$('#viewer_id_add')._select({
				width:140,
				title0:'��� ����������',
				spisok:HIST_WORKER,
				func:_history
			});
		}
		if($('#sa-history').length)
			$('textarea').autosize();
	});
