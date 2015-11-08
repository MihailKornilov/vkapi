var _history = function(v, id) {
		if(id)
			HIST[id] = v;
		HIST.op = 'history_spisok';
		$('#mainLinks').addClass('busy');
		$.post(AJAX_MAIN, HIST, function(res) {
			$('#mainLinks').removeClass('busy');
			if(res.success)
				$($('#client-info').length ? '#history-spisok' : '.left').html(res.html);
		}, 'json');
	},
	_historyRight = function() {
		$('#viewer_id_add')._select({
			width:140,
			title0:'��� ����������',
			spisok:HIST_WORKER,
			func:_history
		});
		if(HIST_CAT.length)
			$('#category_id')._select({
				width:140,
				title0:'����� ���������',
				spisok:HIST_CAT,
				func:_history
			});
	};
$(document)
	.on('click', '#history-add', function() {
		var html =
				'<table id="history-add-tab">' +
					'<tr><td class="label topi">�����:<td><textarea id="txt"></textarea>' +
				'</table>',
			dialog = _dialog({
				width:480,
				head:'�������� ������ ������� ��� �������',
				content:html,
				submit:submit
			});

		$('#txt').focus().autosize();

		function submit() {
			var send = {
				op:'history_add',
				txt:$('#txt').val()
			};
			if(!send.txt) {
				dialog.err('�� ������ �����');
				$('#txt').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('�������');
						_history(1, 'page');
					} else
						dialog.abort();
				}, 'json');
			}
		}
	})

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
		var t = $(this);
		_dialogDel({
			id:t.attr('val'),
			head:'������ � ������� ��������',
			op:'history_del',
			func:function(res) {
				var ul = t.parent().parent();
				t.parent().remove();
				if(!ul.html())
					ul.parent().remove();
			}
		});
	});

