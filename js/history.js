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
					'<tr><td class="label topi">Текст:<td><textarea id="txt"></textarea>' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:580,
				head:'Внесение новой константы для истории действий',
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
				dialog.err('Не указан текст константы');
				$('#txt').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('Внесено');
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
					'<tr><td><td>Если изменяется <b>type_id</b>, все записи со предыдущим значением будут изменены на новое.' +
					'<tr><td class="label topi">Текст:<td><textarea id="txt">' + $('#txt' + id).val() + '</textarea>' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:520,
				head:'Редактирование константы истории действий',
				content:html,
				butSubmit:'Изменить',
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
				dialog.err('Некорректно указан type_id');
				$('#type_id').focus();
			} else if(!send.txt) {
				dialog.err('Не указан текст константы');
				$('#txt').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('Изменено');
						$('#spisok').html(res.html);
						$('textarea').autosize();
					} else
						dialog.abort();
				}, 'json');
			}
		}
	})

	.ready(function() {
		if($('#sa-history').length)
			$('textarea').autosize();
	});
