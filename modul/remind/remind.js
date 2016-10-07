var _remindAdd = function() {
		var html =
				'<table class="_remind-add-tab">' +
					 (window.ZI ? '<tr><td class="label">Заявка:<td><b>' + ZI.name + '</b>' : '') +
					(window.CI ? '<tr><td class="label">Клиент:<td>' + CI.name : '') +
					'<tr><td class="label">Задача:<td><input type="text" id="txt" />' +
					'<tr><td class="label top">Подробно:<td><textarea id="about"></textarea>' +
					'<tr><td class="label">День выполнения:<td><input type="hidden" id="day" />' +
				'</table>' +
				'<input type="hidden" id="client_id" value="' + (window.CI ? CI.id : 0) + '">' +
				'<input type="hidden" id="zayav_id" value="' + (window.ZI ? ZI.id : 0) + '">',
			dialog = _dialog({
				top:40,
				width:480,
				head:'Внесение нового напоминания',
				content:html,
				submit:submit
			});

		$('#txt').focus();
		$('#about').autosize();
		$('#day')._calendar();

		function submit() {
			var send = {
				op:'remind_add',
				client_id:$('#client_id').val(),
				zayav_id:$('#zayav_id').val(),
				txt:$.trim($('#txt').val()),
				about:$('#about').val(),
				day:$('#day').val()
			};
			if(!send.txt) {
				dialog.err('Не указано содержание задачи');
				$('#txt').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					if(window.ZI && !window.REMIND)
						location.reload();
					else
						_remindSpisok();
				} else
					dialog.abort();
			}, 'json');
		}

		return false;
	},
	_remindSpisok = function(v, id) {
		_filterSpisok(REMIND, v, id);
		$.post(AJAX_MAIN, REMIND, function(res) {
			if(res.success)
				$('#_remind-spisok').html(res.spisok);
		}, 'json');
	};


$(document)
	.on('click', '._remind-add', _remindAdd)
	.on('click', '._remind-unit .action', function() {
		var t = $(this),
			p = t;
		while(!p.hasClass('_remind-unit'))
			p = p.parent();
		var id = p.attr('val'),
			head = p.find('.hdtxt').html(),
			day = p.find('.ruday').val(),
			html =
				'<div id="_remind-action-tab">' +
					'<div id="hd">' + head + '</div>' +
					'<div class="st c1" val="1">' +
						'Указать другой день' +
						'<div class="about">Перенести напоминание на другой день.</div>' +
					'</div>' +
					'<div class="st c2" val="2">' +
						'Выполнено' +
						'<div class="about">Задание выполнено успешно.</div>' +
					'</div>' +
					'<div class="st c3" val="3">' +
						'Отмена' +
						'<div class="about">Отмена напоминания по какой-либо причине.</div>' +
					'</div>' +
					'<table id="ra-tab">' +
						'<tr><td class="label">Новый день:<td><input type="hidden" id="remind_day" value="' + day + '" />' +
						'<tr><td class="label">Причина:<td><input type="hidden" id="reason" />' +
					'</table>' +
				'</div>',
			dialog = _dialog({
				top:30,
				head:'Изменение статуса напоминания',
				content:html,
				butSubmit:'',
				butCancel:'Закрыть',
				submit:function() {
					submit(1);
				}
			});
		$('#remind_day')._calendar();
		$('#reason')._select({
			width:225,
			spisok:[],
			write:1,
			write_save:1
		});

		// загрузка списка причин
		$('#reason')._select('process');
		$.post(AJAX_MAIN, {op:'remind_reason_spisok'}, function(res) {
			$('#reason')._select('cancel');
			if(res.success)
				$('#reason')._select(res.spisok);
		}, 'json');

		$('.st').click(function() {
			var	v = $(this).attr('val');
			if(v == 1) {
				$('.c2,.c3').hide();
				$('#ra-tab').show();
				dialog.butSubmit('Применить');
			} else {
				$('#_remind-action-tab').addClass('busy');
				submit(v);
			}
		});
		function submit(status) {
			var send = {
				op:'remind_action',
				from:window.ZI ? 'zayav' : (window.CI ? 'client' : ''),
				id:id,
				status:status,
				day:$('#remind_day').val(),
				reason:$('#reason')._select('inp')
			};
			if(status == 1 && send.day == day) dialog.err('Выберите другой день');
			else if(status == 1 && !send.reason) dialog.err('Не указана причина');
			else
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg();
						_remindSpisok();
					}
				}, 'json');
		}
	})
	.on('click', '._remind-unit .hd-edit', function() {
		var t = $(this),
			p = _parent(t, '._remind-unit'),
			id = p.attr('val'),
			head = p.find('.hdtxt'),
			about = p.find('.hd-about'),
			html =
				'<table id="_remind-head-edit">' +
					'<tr><td class="label">Задача:<td><input type="text" id="hdtxt" value="' + head.html() + '" />' +
					'<tr><td class="label top">Подробно:<td><textarea id="hd-about">' + about.html().replace(/<br>/g, '') + '</textarea>' +
				'</table>',
			dialog = _dialog({
				width:420,
				head:'Редактирование содержания напоминания',
				content:html,
				butSubmit:'Сохранить',
				submit:submit
			});

		$('#hdtxt').focus();
		$('#hd-about').autosize();

		function submit() {
			var send = {
				op:'remind_head_edit',
				id:id,
				txt:$('#hdtxt').val(),
				about:$('#hd-about').val()
			};

			if(!send.txt) {
				dialog.err('Не указано содержание задачи');
				head.focus();
				return;
			}

			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if (res.success) {
					dialog.close();
					_msg();
					head.html(send.txt);
					about.html(send.about.replace(/\n/g, '<br>'));
				} else
					dialog.abort();
			}, 'json');
		}
	})
	.on('click', '._remind-unit .ruhist', function() {
		$(this).parent().next().slideToggle(300);
	})
	.on('click', '#_remind-show-all', function() {
		$('._remind-unit').slideDown(200);
		$(this).remove();
	})

	.ready(function() {
		if($('#remind-filter').length)
			$('#status')._radio(_remindSpisok);
	});
