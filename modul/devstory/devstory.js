var devStoryMainEdit = function(o) {//создание, редактирование основного раздела
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
	devStoryTaskEdit = function(o) {//создание, редактирование задачи
		o = $.extend({
			id:0,
			part_id:0,
			part_name:'<span class="red">раздел не определён</span>',
			part_sub_id:0,
			name:'',
			about:''
		}, o);

		var html =
				'<table class="bs10">' +
					'<tr><td class="label r">Раздел:<td><b>' + o.part_name + '</b>' +
					'<tr><td class="label r">Подраздел:' +
						'<td><input type="hidden" id="part_sub_id" value="' + o.part_sub_id + '" />' +
					'<tr><td class="label r">Задача:' +
						'<td><input type="text" id="name" class="w250" value="' + o.name + '" />' +
					'<tr><td class="label r topi">Описание:' +
						'<td><textarea id="about" class="w250">' + o.about + '</textarea>' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:400,
				head:(o.id ? 'Редактирование' : 'Внесение новой') + ' задачи',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#part_sub_id')._select({
			width:220,
			title0:'подраздел не указан',
			write_save:1,
			spisok:DEVSTORY_PART_SPISOK[o.part_id]
		});
		$('#about').autosize();

		function submit() {
			var send = {
				op:'devstory_task_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				part_id:o.part_id,
				part_sub_id:_num($('#part_sub_id').val()),
				part_sub_name:$('#part_sub_id')._select('inp'),
				name:$('#name').val(),
				about:$('#about').val()
			};
			if(!send.part_sub_id && !send.part_sub_name) {
				dialog.err('Не указан подраздел');
				return;
			}
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
					location.href = URL + '&p=devstory&d=task';
				} else
					dialog.abort();
			}, 'json');
		}
	},
	devStoryTaskAction = function(p, o) {//действие над задачей
		var html =
				'<table class="bs10">' +
					'<tr><td class="label r w70">Раздел:' +
						'<td>' + p.find('.part_name').val() +
							' » ' + p.find('.part_sub_name').val() +
					'<tr><td class="label r">Задача:<td><b>' + p.find('.name').html() + '</b>' +
					'<tr><td class="label r">Время:' +
						'<td><input type="text" id="time" class="w125" value="' + DTIME + '" />' +
				'</table>',
			dialog = _dialog({
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
					$('#spisok').html(res.html);
				} else
					dialog.abort();
			}, 'json');
		}
	};


$(document)
	.on('click', '#devstory .img_add', function() {
		var t = $(this),
			p = _parent(t, 'DD');
		devStoryTaskEdit({
			part_id:p.attr('val'),
			part_name:p.find('.name').html()
		});
	})
	.on('click', '#devstory .part-u .img_edit', function() {
		var t = $(this),
			p = _parent(t, 'DD');
		devStoryMainEdit({
			id:t.attr('val'),
			name:p.find('.name').html()
		});
	})
	.on('click', '#devstory .task-u .img_edit', function() {
		var t = $(this),
			p = _parent(t, '.task-u');
		devStoryTaskEdit({
			id:t.attr('val'),
			part_id:p.find('.part_id').val(),
			part_name:p.find('.part_name').val(),
			part_sub_id:p.find('.part_sub_id').val(),
			name:p.find('.name').html(),
			about:_br(p.find('.about').html())
		});
	})
	.on('click', '#devstory .st-action.start', function() {
		var t = $(this),
			p = _parent(t, '.task-u'),
			o = {
				head:'Старт выполнения задачи',
				but:'Начать',
				op:'start'
			};
			devStoryTaskAction(p, o);
	})
	.on('click', '#devstory .st-action.pause', function() {
		var t = $(this),
			p = _parent(t, '.task-u'),
			o = {
				head:'Приостановка выполнения',
				but:'Приостановить',
				op:'pause'
			};
			devStoryTaskAction(p, o);
	})
	.on('click', '#devstory .st-action.next', function() {
		var t = $(this),
			p = _parent(t, '.task-u'),
			o = {
				head:'Продолжение выполнения',
				but:'Продолжить',
				op:'start'
			};
			devStoryTaskAction(p, o);
	})
	.on('click', '#devstory .st-action.ready', function() {
		var t = $(this),
			p = _parent(t, '.task-u'),
			o = {
				head:'Задача выполнена',
				but:'Выполнено',
				op:'ready'
			};
			devStoryTaskAction(p, o);
	})
	.on('click', '#devstory .st-action.cancel', function() {
		var t = $(this),
			p = _parent(t, '.task-u'),
			o = {
				head:'Отмена задачи',
				but:'Отменить',
				op:'cancel'
			};
			devStoryTaskAction(p, o);
	});



