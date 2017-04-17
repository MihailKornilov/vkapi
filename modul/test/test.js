var testBookUpdate = function(t) {
		var send = {
			op:'test_book_update',
			name:t.prev().val()
		};
		t.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				location.reload();
			else
				t.removeClass('_busy');
		}, 'json');
	},
	testWordFind = function(id) {//поиск слова в книге
		var send = {
				op:'test_word_find',
				id:id
			},
			str = $('#book-str');
		
		str.addClass('grey');
		$.post(AJAX_MAIN, send, function(res) {
			str.removeClass('grey')
			   .html('');
			if(res.success)
				str.html(res.str);
		}, 'json');
	},
	testWordSave = function(id) {//сохранение слова в словарь
		var send = {
			op:'test_word_save',
			id:id
		};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				location.reload();
		}, 'json');
	},
	testWordDel = function(id) {//удаление слова из книги
		var send = {
			op:'test_word_del',
			id:id
		};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				location.reload();
		}, 'json');
	},



	_taskEdit = function(task_id) {//формирование|редактирование задачи
		task_id = _num(task_id);
		var html = '<table class="bs10">' +
					'<tr><td class="label w100 r">Название:' +
						'<td><input type="text" id="task-name" class="w250" />' +
					'<tr><td class="label r">Раздел:' +
						'<td class="b">Заявки' +
					'<tr><td class="label r topi">Условия:' +
						'<td id="cond" class="_busy">' +
					'<tr><td class="label r">Результат:' +
						'<td id="result">' +
				'</table>',
			dialog = _dialog({
				top:20,
				width:450,
				head:task_id ? 'Редактирование задачи' : 'Новая задача',
				content:html,
				submit:submit,
				butSubmit:task_id ? 'Сохранить' : 'Внести'
			});

		var send = {
			op:'task_cond_load',
			task_id:task_id
		};
		$.post(AJAX_MAIN, send, function(res) {
			$('#cond').removeClass('_busy');
			if(res.success) {
				$('#task-name').val(res.name);
				$('#cond').html(res.html);
				ZAYAV = res.filter;
				_zayavSpisok = _taskZayavCount;
				_zayavReady();
				_taskZayavCount();
			} else
				$('#cond')
					.addClass('red')
					.html('Ошибка: ' + res.text);
		}, 'json');

		function submit() {
			var send = ZAYAV;
			send.op = 'task_' + (task_id ? 'edit' : 'add');
			send.task_id = task_id;
			send.task_name = $('#task-name').val();
			dialog.post(send, 'reload');
		}
	},
	_taskZayavCount = function(v, id) {//получение количества заявок при изменении фильтра
		if(id)
			ZAYAV[id] = v;

		ZAYAV.op = 'task_zayav_count';

		$('#result').addClass('_busy');
		$.post(AJAX_MAIN, ZAYAV, function(res) {
			$('#result').removeClass('_busy');
			if(res.success) {
				$('#result').html(res.count);
			} else
				$('#result')
					.addClass('red')
					.html('Ошибка: ' + res.text);
		}, 'json');
	},
	_taskDel = function(id) {
		_dialogDel({
			id:id,
			head:'задачи',
			op:'task_del',
			func:function(res) {
				location.reload();
			}
		});
	};


