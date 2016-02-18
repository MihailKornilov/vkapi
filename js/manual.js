var _manualPartEdit = function(o) {
		o = $.extend({
			id:0,
			access:0,
			name:''
		}, o);
		var html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">Название:<td><input type="text" id="name" value="' + o.name + '" />' +
					'<tr><td><td><input type="hidden" id="access" value="' + o.access + '" />' +
				'</table>',
			dialog = _dialog({
				head:(o.id ? 'Редактирование' : 'Внесение нового') + ' раздела',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus().keyEnter(submit);
		$('#access')._check({
			light:1,
			name:'раздел доступен для просмотра'
		});

		function submit() {
			var send = {
				op:'manual_part_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val(),
				access:$('#access').val()
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
					location.reload();
				} else
					dialog.abort();
			}, 'json');
		}
},
	_manualPageEdit = function(o) {//внесение новой страницы
		o = $.extend({
			id:0,
			access:0,
			part_id:0,
			part_sub_id:0,
			name:'',
			content:''
		}, o);

		var t = $(this),
			html =
				'<table id="manual-page-add" class="_dialog-tab">' +
					'<tr><td><td><input type="hidden" id="access" value="' + o.access + '" />' +
					'<tr><td class="label">Раздел:<td><input type="hidden" id="part_id" value="' + o.part_id + '" />' +
					'<tr><td class="label">Подраздел:<td><input type="hidden" id="part_sub_id" value="' + o.part_sub_id + '" />' +
					'<tr><td class="label">Название:<td><input type="text" id="name" value="' + o.name + '" />' +
					'<tr><td class="label top">Содержание:' +
						'<td><b>&lt;div class="_info"></b> - информационный блок жёлтого цвета<br />' +
							'<b>&lt;p></b> - параграф с отступами<br />' +
							'<b>&lt;b></b> - жирный шрифт<br />' +
							'<b>&lt;ul>&lt;li> &lt;/ul></b> - маркированный список<br />' +
							'<b>&lt;h6></b> - текст в сером блоке<br />' +
					'<tr><td colspan="2"><textarea id="content">' + o.content + '</textarea>' +
				'</table>',
			dialog = _dialog({
				top:20,
				width:546,
				head:(o.id ? 'Редактирование' : 'Внесение новой') + ' страницы мануала',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#access')._check({
			light:1,
			name:'страница доступна для просмотра'
		});
		$('#part_id')._select({
			width:218,
			title0:'Раздел не выбран',
			spisok:MANUAL_PART_SPISOK,
			func:function(v) {
				$('#part_sub_id')
					._select(0)
					._select(MANUAL_PART_SUB_SPISOK[v] || []);
				$('#name').focus()
			}
		});
		$('#part_sub_id')._select({
			width:218,
			title0:'Подраздел не выбран',
			spisok:[],
			func:function() {
				$('#name').focus()
			}
		});
		$('#name').focus().keyEnter(submit);
		$('#content').autosize();

		function submit() {
			var send = {
				op:'manual_page_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				access:$('#access').val(),
				part_id:_num($('#part_id').val()),
				part_sub_id:$('#part_sub_id').val(),
				name:$('#name').val(),
				content:$('#content').val()
			};
			if(!send.part_id) {
				dialog.err('Не выбран раздел');
				return;
			}
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
					location.href = URL + '&p=manual&d=part&page_id=' + res.id;
				} else
					dialog.abort();
			}, 'json');
		}
	};

$(document)
	.on('click', '#manual #part-add', _manualPartEdit)
	.on('click', '.manual-part-edit', function() {
		var o = $(this).attr('val').split('#');
		_manualPartEdit({
			id:o[0],
			name:o[1],
			access:o[2]
		});
	})

	.on('click', '#manual #part-sub-add', function() {//внесение нового подраздела
		var t = $(this),
			html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">Раздел:<td><input type="hidden" id="s-part_id" />' +
					'<tr><td class="label">Подраздел:<td><input type="text" id="name" />' +
				'</table>',
			dialog = _dialog({
				head:'Внесение нового подраздела',
				content:html,
				submit:submit
			});

		$('#s-part_id')._select({
			width:218,
			title0:'Раздел не выбран',
			spisok:MANUAL_PART_SPISOK,
			func:function() {
				$('#name').focus()
			}
		});
		$('#name').focus().keyEnter(submit);

		function submit() {
			var send = {
				op:'manual_part_sub_add',
				part_id:_num($('#s-part_id').val()),
				name:$('#name').val()
			};
			if(!send.part_id) {
				dialog.err('Не выбран раздел');
				return;
			}
			if(!send.name) {
				dialog.err('Не указано название подраздела');
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
	})
	.on('click', '.manual-part-del', function() {
		var t = $(this);
		_dialogDel({
			id:t.attr('val'),
			head:'раздела мануала',
			op:'manual_part_del',
			func:function(res) {
				location.href = URL + '&p=manual';
			}
		});
	})

	.on('click', '#manual #page-add', _manualPageEdit)
	.on('click', '.manual-page-edit', function() {
		var o = $(this).attr('val').split('#'),
			p = $('#manual-part');
		_manualPageEdit({
			id:o[0],
			access:o[1],
			part_id:o[2],
			part_sub_id:o[3],
			name:p.find('h1').html(),
			content:p.find('textarea').html()
		});
	})
	.on('click', '.manual-page-del', function() {
		var t = $(this);
		_dialogDel({
			id:t.attr('val'),
			head:'страницы мануала',
			op:'manual_page_del',
			func:function(res) {
				location.href = URL + '&p=manual&d=part&part_id=' + res.part_id;
			}
		});
	})

	.on('click', '#manual-part #page-but .vk', function() {//внесение нового подраздела
		var t = $(this),
			p = t.parent(),
			o = t.attr('val').split('#'),
			send = {
				op:'manual_answer',
				manual_id:o[0],
				val:o[1]
			};

		if(p.hasClass('busy'))
			return;

		p.addClass('busy');
		$.post(AJAX_MAIN, send, function() {
			p.removeClass('busy');
			if(res.success) {
				p.parent().addClass('answered');
				p.before(
					'<b id="answer-ok">Спасибо за ваш ответ!</b>' +
					(send.val == 4 ? '<br />Пожалуйста, укажите в заметках, что именно вам не понятно.' : '')
				);
			}
		}, 'json');
	});

