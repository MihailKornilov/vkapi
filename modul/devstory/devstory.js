var devStoryPartEdit = function(o) {//создание, редактирование основного раздела
		o = $.extend({
			id:0,
			name:''
		}, o);

		var html =
				'<table class="bs10">' +
					'<tr><td class="label">Название:' +
						'<td><input type="text" id="name" class="w250" value="' + o.name + '" />' +
				'</table>',
			dialog = _dialog({
				head:(o.id ? 'Редактирование' : 'Внесение нового') + ' раздела',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();

		function submit() {
			var send = {
				op:'devstory_part_' + (o.id ? 'edit' : 'add'),
				id:o.id,
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
					$('#part-spisok').html(res.part);
					sortable();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	devStoryTaskEdit = function(part_id, task_id) {//создание, редактирование задачи
		task_id = _num(task_id);
		part_id = _num(part_id);
		var dialog = _dialog({
				top:30,
				width:550,
				head:(task_id ? 'Редактирование' : 'Внесение новой') + ' задачи',
				load:1,
				butSubmit:task_id ? 'Сохранить' : 'Внести',
				submit:submit
			}),
			send = {
				op:'devstory_task_load',
				part_id:part_id,
				task_id:task_id
			};

		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				loaded(res);
			} else
				dialog.loadError(res.text);
		}, 'json');

		function loaded(o) {
			dialog.content.html(
				'<table class="bs10">' +
					'<tr><td class="label r">Раздел:' +
						'<td><input type="hidden" id="part_id" value="' + o.part_id + '" />' +
							(o.part_id ? '<b>' + o.part_name + '</b>' : '') +
					'<tr' + (o.part_id ? '' : ' class="dn"') + '>' +
						'<td class="label r">Ключевые слова:' +
						'<td><input type="hidden" id="keyword_ids" value="' + o.keyword_ids + '" />' +
					'<tr><td class="label r">Задача:' +
						'<td><input type="text" id="name" class="w400 b" value="' + o.name + '" />' +
					'<tr><td class="label r topi">Описание:' +
						'<td><textarea id="about" class="w400">' + o.about + '</textarea>' +
				'</table>'
			);

			if(!o.part_id)
				$('#part_id')._select({
					width:200,
					title0:'не выбран',
					spisok:o.part_spisok
				});
			$('#keyword_ids')._select({
				width:350,
				title0:'ключевые слова',
				multiselect:1,
				write_save:1,
				spisok:o.keyword_spisok
			});
			$('#about').autosize();
		}
		function submit() {
			var send = {
				op:'devstory_task_' + (task_id ? 'edit' : 'add'),
				id:task_id,
				part_id:$('#part_id').val(),
				keyword_ids:$('#keyword_ids').val(),
				keyword:$('#keyword_ids')._select('inp'),
				name:$('#name').val(),
				about:$('#about').val()
			};
			if(!send.name) {
				dialog.err('Не указано название задачи');
				$('#name').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					location.href = URL + '&p=devstory&d=task&id=' + res.task_id;
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	devStoryTaskAction = function(p, o) {//действие над задачей
		var d = new Date(),
			mon = d.getMonth() + 1,
			day = d.getDate(),
			hour = d.getHours(),
			min = d.getMinutes(),
			sec = d.getSeconds(),
			dtime = d.getFullYear() + '-' +
					(mon < 10 ? '0' : '') + mon + '-' +
					(day < 10 ? '0' : '') + day + ' ' +
					(hour < 10 ? '0' : '') + hour + ':' +
					(min < 10 ? '0' : '') + min + ':' +
					(sec < 10 ? '0' : '') + sec,
			html =
				'<table class="bs10">' +
					'<tr><td class="label r w70">Раздел:' +
						'<td><u>' + p.find('.part_name').html() + '</u>' +
					'<tr><td class="label r top">Задача:<td><b>' + p.find('.name').html() + '</b>' +
					'<tr' + (o.from_pause ? ' class="dn"' : '') + '>' +
						'<td class="label r">Время:' +
						'<td><input type="text" id="time" class="w125" value="' + dtime + '" />' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:420,
				head:o.head,
				content:html,
				butSubmit:o.but,
				submit:submit
			});

		function submit() {
			var send = {
				op:'devstory_task_' + o.op,
				id:p.attr('val'),
				time:$('#time').val()
			};

			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					location.reload();
				} else
					dialog.abort();
			}, 'json');
		}
	};


$(document)
	.on('click', '#devstory .part-u .img_edit', function() {
		var t = $(this),
			p = _parent(t, 'DD');
		devStoryPartEdit({
			id:t.attr('val'),
			name:p.find('.name').html()
		});
	})
	.on('click', '#devstory-task-info .st-action.start', function() {
		var t = $(this),
			p = _parent(t, '.task-u'),
			o = {
				head:'Старт выполнения задачи',
				but:'Начать',
				op:'start'
			};
			devStoryTaskAction(p, o);
	})
	.on('click', '#devstory-task-info .st-action.pause', function() {
		var t = $(this),
			p = _parent(t, '.task-u'),
			o = {
				head:'Приостановка выполнения',
				but:'Приостановить',
				op:'pause'
			};
			devStoryTaskAction(p, o);
	})
	.on('click', '#devstory-task-info .st-action.next', function() {
		var t = $(this),
			p = _parent(t, '.task-u'),
			o = {
				head:'Продолжение выполнения',
				but:'Продолжить',
				op:'start'
			};
			devStoryTaskAction(p, o);
	})
	.on('click', '#devstory-task-info .st-action.ready', function() {
		var t = $(this),
			p = _parent(t, '.task-u'),
			o = {
				head:'Задача выполнена',
				but:'Выполнено',
				op:'ready',
				from_pause:t.hasClass('from-pause')
			};
		devStoryTaskAction(p, o);
	})
	.on('click', '#devstory-task-info .st-action.cancel', function() {
		var t = $(this),
			p = _parent(t, '.task-u'),
			o = {
				head:'Отмена задачи',
				but:'Отменить',
				op:'cancel'
			};
			devStoryTaskAction(p, o);
	});
