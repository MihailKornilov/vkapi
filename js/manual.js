$(document)
	.on('click', '#manual .add', function() {
		var html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">Название:<td><input type="text" id="name" />' +
				'</table>',
			dialog = _dialog({
				head:'Внесение нового раздела',
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
				dialog.err('Не указано название');
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

