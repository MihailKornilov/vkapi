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
	.on('click', '._hist-un h4', function() {//удаление записи истории (для SA)
		var t = $(this),
			dialog = _dialog({
				top:90,
				width:300,
				head:'Удаление записи в истории действий',
				content:'<center class="red">Подтвердите удаление записи.</center>',
				butSubmit:'Удалить',
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
					_msg('Удалено');
					var ul = t.parent().parent();
					t.parent().remove();
					if(!ul.html())
						ul.parent().remove();
				} else
					dialog.abort();
			}, 'json');
		}
	})

	.ready(function() {
		if($('#report.history').length) {
			$('#viewer_id_add')._select({
				width:140,
				title0:'Все сотрудники',
				spisok:HIST_WORKER,
				func:_history
			});
		}
	});
