var setupRuleCheck = function(v, id) {
	var send = {
		op:id,
		viewer_id:window.RULE_VIEWER_ID || VIEWER_ID,
		v:v
	};
	$.post(AJAX_MAIN, send, function(res) {
		if(res.success)
			_msg('Сохранено');
	}, 'json');
},
	setupZayavExpense = function(o) {
		o = $.extend({
			id:0,
			name:'',
			dop:0
		}, o);

		var html =
				'<table id="setup-tab">' +
					'<tr><td class="label">Наименование:<td><input id="name" type="text" value="' + o.name + '" />' +
					'<tr><td class="label topi">Дополнительное поле:<td><input id="dop" type="hidden" value="' + o.dop + '" />' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:(o.id ? 'Редактирование' : 'Добавление новой' ) + ' категории расхода заявки',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus().keyEnter(submit);
		$('#dop')._radio({light:1,spisok:ZAYAV_EXPENSE_DOP});

		function submit() {
			var send = {
				op:'setup_zayav_expense_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val(),
				dop:$('#dop').val()
			};
			if(!send.name) {
				dialog.err('Не указано наименование');
				$('#name').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						$('#spisok').html(res.html);
						dialog.close();
						_msg('Внесено!');
						sortable();
					} else
						dialog.abort();
				}, 'json');
			}
		}
	},
	setupZayavStatus = function(o) {
		o = $.extend({
			id:0,
			name:'',
			about:'',
			color:'fff',
			default:0,
			nouse:0,
			next:0,
			next_ids:0,
			srok:0,
			executer:0,
			accrual:0,
			remind:0,
			day_fact:0
		}, o);

		var html =
				'<table class="setup-status-tab bs10">' +
					'<tr><td class="label">Название:<td><input id="name" type="text" value="' + o.name + '" />' +
					'<tr><td class="label topi">Описание:<td><textarea id="about">' + o.about + '</textarea>' +
					'<tr><td class="label topi">Цвет:<td><div id="color" val="' + o.color + '" style="background-color:#' + o.color + '"></div>' +
					'<tr><td class="label">По умолчанию:<td><input type="hidden" id="default" value="' + o.default + '" />' +
					'<tr class="tr-nouse' + (o.default ? '' : ' dn') + '">' +
						'<td class="label">Не использовать повторно:' +
						'<td><input type="hidden" id="nouse" value="' + o.nouse + '" />' +
					'<tr><td class="label topi">Следующие статусы:<td><input type="hidden" id="next" value="' + o.next + '" />' +
					'<tr class="tr-next-ids' + (o.next ? '' : ' dn') + '"><td class="label topi"><td><input type="hidden" id="next_ids" value="' + o.next_ids + '" />' +
					'<tr><td><td>' +
					'<tr><td><td><b>Действия при выборе статуса</b>' +
					'<tr><td><td><input type="hidden" id="executer" value="' + o.executer + '" />' +
					'<tr><td><td><input type="hidden" id="srok" value="' + o.srok + '" />' +
					'<tr' + (ZAYAV_INFO_STATUS_DAY ? '' : ' class="dn"') + '>' +
						'<td class="label">' +
						'<td><input id="day_fact" type="hidden" value="' + o.day_fact + '" />' +
					'<tr><td><td><input type="hidden" id="accrual" value="' + o.accrual + '" />' +
					'<tr><td><td><input type="hidden" id="remind" value="' + o.remind + '" />' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:480,
				head:(o.id ? 'Редактирование' : 'Добавление нового' ) + ' статуса заявки',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus().keyEnter(submit);
		$('#about').autosize();
		$('#color').click(setupZayavStatusColor);
		$('#default')._check({
			func:function(v) {
				$('#nouse')._check(0);
				$('.tr-nouse')[(v ? 'remove' : 'add') + 'Class']('dn');
			}
		});
		$('#default_check').vkHint({
			top:-70,
			left:-100,
			width:210,
			msg:'Автоматически присваивать данный<br />' +
				'статус при внесении новой заявки.'
		});
		$('#nouse')._check();
		$('#nouse_check').vkHint({
			top:-83,
			left:-86,
			width:180,
			msg:'После выбора другого статуса<br />' +
				'данный статус нельзя будет<br />' +
				'выбрать снова.'
		});
		$('#next')._radio({
			light:1,
			spisok:[
				{uid:0, title:'Все'},
				{uid:1, title:'Выборочные'}
			],
			func:function(v) {
				$('.tr-next-ids')[v ? 'show' : 'hide']();
			}
		});

		var spisok = [];
		for(var i = 0; i < ZAYAV_STATUS_NAME_SPISOK.length; i++) {
			var sp = ZAYAV_STATUS_NAME_SPISOK[i];
			if(sp.uid == o.id)
				continue;
			if(ZAYAV_STATUS_NOUSE_ASS[sp.uid])
				continue;
			spisok.push(sp);
		}
		$('#next_ids')._select({
			width:258,
			title0:'следующие статусы не выбраны',
			spisok:spisok,
			multiselect:1
		});

		$('#day_fact')._check({
			name:'уточнять фактический день',
			light:1
		});
		$('#executer')._check({
			name:'указывать исполнителя',
			light:1
		});
		$('#srok')._check({
			name:'уточнять срок',
			light:1
		});
		$('#accrual')._check({
			name:'вносить начисление',
			light:1
		});
		$('#remind')._check({
			name:'добавлять напоминание',
			light:1
		});

		function submit() {
			var send = {
				op:'setup_zayav_status_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val(),
				about:$('#about').val(),
				color:$('#color').attr('val'),
				default:$('#default').val(),
				nouse:$('#nouse').val(),
				next_ids:$('#next_ids').val(),
				executer:$('#executer').val(),
				srok:$('#srok').val(),
				accrual:$('#accrual').val(),
				remind:$('#remind').val(),
				day_fact:$('#day_fact').val()
			};
			if(!send.name) {
				dialog.err('Не указано название');
				$('#name').focus();
				return;
			}
			if(!_num($('#next').val()))
				send.next_ids = 0;
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#status-spisok').html(res.html);
					dialog.close();
					_msg();
					sortable();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	setupZayavStatusColor = function() {//Выбор цвета для статуса заявки
		//8 9 a b c d e f
		var
		//	col = ['f', 'c', '9', '6'],
		//	col = ['f', 'd', 'b', '9'],
			col = ['f', 'c', '9'],
			bg = '',
			i = 0;
		for(var r = 0; r < col.length; r++)
			for(var g = 0; g < col.length; g++)
				for(var b = 0; b < col.length; b++) {
					var rgb = col[r] + col[g] + col[b];
					bg += '<div class="bg" val="' + rgb + '" style="background-color:#' + rgb + '"></div>';
				}

		var html =
			'<div id="setup-status-color-tab">' +
				bg +
			'</div>',
		dialog = _dialog({
			width:600,
			head:'Выбор цвета для статуса',
			content:html,
			butSubmit:'',
			butCancel:'Закрыть'
		});
		$('.bg').click(function() {
			dialog.close();
			var color = $(this).attr('val');
			$('#color')
				.css('background-color', '#' +color)
				.attr('val', color);
		});
	};

$(document)
	.on('click', '#setup_worker .add', function() {
		var html =
			'<div id="worker-add">' +
				'<h1>Укажите адрес страницы пользователя или его id ВКонтакте:</h1>' +
				'<div class="_info">Формат адреса может быть следующих видов:<br />' +
					'<u>http://vk.com/id12345</u>, <u>http://vk.com/durov</u>.<br />' +
					'Либо используйте ID пользователя: <u>id12345</u>, <u>durov</u>, <u>12345</u>.' +
				'</div>' +

				'<table id="wa-find">' +
					'<tr><td><input type="text" id="viewer_id" />' +
						'<td id="msg"><span>Пользователь не найден</span>' +
					'<tr><td colspan="2" id="vkuser">' +
				'</table>' +

				'<div id="manual"><a>Или заполните данные вручную..</a></div>' +
				'<table id="manual-tab">' +
					'<tr><td class="label r">Имя:<td><input type="text" id="first_name" />' +
					'<tr><td class="label r">Фамилия:<td><input type="text" id="last_name" />' +
					'<tr><td class="label r">Пол:<td><input type="hidden" id="sex" />' +
					'<tr><td class="label r">Должность:<td><input type="text" id="post" />' +
				'</table>' +
			'</div>',
			dialog = _dialog({
				top:50,
				width:440,
				head:'Добавление нового сотрудника',
				content:html,
				butSubmit:'Добавить',
				submit:submit
			}),
			viewer_id = 0;

		$('#viewer_id')
			.focus()
			.keyEnter(user_find)
			.keyup(user_find);
		$('#manual').click(function() {
			$(this)
				.hide()
				.next().show();
			$('#wa-find').remove();
			viewer_id = 0;
			$('#viewer_id').val('');
			$('#first_name').focus();
		});
		$('#sex')._radio({
			light:1,
			block:0,
			spisok:[
				{uid:2, title:'М'},
				{uid:1, title:'Ж'}
			],
			func:function() {
				$('#post').focus();
			}
		});

		function user_find() {
			if($('#msg').hasClass('_busy'))
				return;

			viewer_id = 0;
			$('#vkuser').html('');

			var send = {
				user_ids:$.trim($('#viewer_id').val()),
				fields:'photo_50',
				v:5.2
			};

			if(!send.user_ids)
				return;
			if(/vk.com/.test(send.user_ids))
				send.user_ids = send.user_ids.split('vk.com/')[1];
			if(/\?/.test(send.user_ids))
				send.user_ids = send.user_ids.split('?')[0];
			if(/#/.test(send.user_ids))
				send.user_ids = send.user_ids.split('#')[0];

			$('#msg')
				.addClass('_busy')
				.find('span').hide();

			VK.api('users.get', send, function(data) {
				$('#msg').removeClass('_busy');
				if(data.response) {
					var u = data.response[0],
						html =
							'<table>' +
								'<tr><td><img src=' + u.photo_50 + '>' +
									'<td>' + u.first_name + ' ' + u.last_name +
							'</table>';
					$('#vkuser').html(html);
					viewer_id = u.id;
				} else
					$('#msg span').show();
			});
		}
		function submit() {
			var send = {
				op:'setup_worker_add',
				viewer_id:viewer_id,
				first_name:$('#first_name').val(),
				last_name:$('#last_name').val(),
				sex:$('#sex').val(),
				post:$('#post').val()
			};
			if(!send.viewer_id && !send.first_name && !send.last_name) dialog.err('Произведите поиск пользователя или укажите данные вручную');
			else if(send.first_name && send.last_name && send.sex == 0) dialog.err('Не указан пол');
			else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('Новый сотрудник успешно добавлен.');
						$('#spisok').html(res.html);
					} else {
						dialog.abort();
						dialog.err(res.text);
					}
				}, 'json');
			}
		}
	})

	.on('click', '.service-toggle', function() {
		var t = $(this),
			p = _parent(t, '.unit'),
			h1 = p.find('h1'),
			send = {
				op:'setup_service_toggle',
				id:p.attr('val')
			};
		if(h1.hasClass('_busy'))
			return;
		h1.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			h1.removeClass('_busy');
			if(res.success) {
				p[(res.on ? 'add' : 'remove') + 'Class']('on');
				_msg();
			}
		}, 'json');
	})
	.on('click', '#setup-service .img_edit', function() {
		var t = $(this),
			p = _parent(t, '.unit'),
			html = '<table id="setup-service-edit">' +
				'<tr><td class="label r">Название:<td><input id="name" type="text" value="' + p.find('.name').val() + '" />' +
				'<tr><td class="label r">Заголовок:<td><input id="head" type="text" value="' + p.find('h1').html() + '" />' +
				'<tr><td class="label r topi">Описание:<td><textarea id="about">' + p.find('h2').html() + '</textarea>' +
				'</table>',
			dialog = _dialog({
				top:20,
				width:520,
				head:'Редактирование вида деятельности',
				content:html,
				butSubmit:'Сохранить',
				submit:submit
			});
		$('#name').focus();
		$('#name,#head').keyEnter(submit);
		$('#about').autosize();
		function submit() {
			var send = {
				op:'setup_service_edit',
				id:p.attr('val'),
				name:$('#name').val(),
				head:$('#head').val(),
				about:$('#about').val()
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
	})

	.on('click', '#setup_expense .add', function() {
		var t = $(this),
			html = '<table id="setup-tab">' +
				'<tr><td class="label r">Наименование:<td><input id="name" type="text" />' +
				'<tr><td class="label r">Список сотрудников:<td><input id="worker_use" type="hidden" />' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:'Добавление новой категории расхода организации',
				content:html,
				submit:submit
			});
		$('#name').focus().keyEnter(submit);
		$('#worker_use')._check();
		function submit() {
			var send = {
				op:'expense_category_add',
				name:$('#name').val(),
				worker_use:$('#worker_use').val()
			};
			if(!send.name) {
				dialog.err('Не указано наименование');
				$('#name').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						$('#spisok').html(res.html);
						dialog.close();
						_msg('Внесено');
						sortable();
					} else
						dialog.abort();
				}, 'json');
			}
		}
	})
	.on('click', '#setup_expense .img_edit', function() {
		var t = _parent($(this), 'DD'),
			id = t.attr('val'),
			name = t.find('.name').html(),
			worker_use = t.find('.worker_use').html() ? 1 : 0,
			html = '<table id="setup-tab">' +
				'<tr><td class="label r">Наименование:<td><input id="name" type="text" value="' + name + '" />' +
				'<tr><td class="label r">Список сотрудников:<td><input id="worker_use" type="hidden" value="' + worker_use + '" />' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:'Редактирование категории расхода организации',
				content:html,
				butSubmit:'Сохранить',
				submit:submit
			});
		$('#name').focus().keyEnter(submit);
		$('#worker_use')._check();
		function submit() {
			var send = {
				op:'expense_category_edit',
				id:id,
				name:$('#name').val(),
				worker_use:$('#worker_use').val()
			};
			if(!send.name) {
				dialog.err('Не указано наименование');
				$('#name').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						$('#spisok').html(res.html);
						dialog.close();
						_msg('Сохранено!');
						sortable();
					} else
						dialog.abort();
				}, 'json');
			}
		}
	})
	.on('click', '#setup_expense .img_del', function() {
		_dialogDel({
			id:_parent($(this), 'DD').attr('val'),
			head:'категории расходов организации',
			op:'expense_category_del',
			func:function(res) {
				$('#spisok').html(res.html);
				sortable();
			}
		});
	})

	.on('click', '#setup_zayav_expense .add', setupZayavExpense)
	.on('click', '#setup_zayav_expense .img_edit', function() {
		var t = _parent($(this), 'DD');
		setupZayavExpense({
			id:t.attr('val'),
			name:t.find('.name').html(),
			dop:t.find('.hdop').val()
		});
	})
	.on('click', '#setup_zayav_expense .img_del', function() {
		_dialogDel({
			id:_parent($(this), 'DD').attr('val'),
			head:'категории расхода заявки',
			op:'setup_zayav_expense_del',
			func:function(res) {
				$('#spisok').html(res.html);
				sortable();
			}
		});
	})

	.on('click', '#setup_zayav_status .status-add', setupZayavStatus)
	.on('click', '#setup_zayav_status .status-edit', function() {
		var t = _parent($(this), 'DD');
		setupZayavStatus({
			id:t.attr('val'),
			name:t.find('.name span').html(),
			about:t.find('.about').html(),
			color:t.find('.name').attr('val'),
			default:t.find('.name').hasClass('b') ? 1 : 0,
			nouse:t.find('.nouse').val(),
			next:t.find('.next').val().length > 1 ? 1 : 0,
			next_ids:t.find('.next').val(),
			srok:t.find('.srok').val(),
			executer:t.find('.executer').val(),
			accrual:t.find('.accrual').val(),
			remind:t.find('.remind').val(),
			day_fact:t.find('.day_fact').val()
		});
	})
	.on('click', '#setup_zayav_status .status-del', function() {
		_dialogDel({
			id:_parent($(this), 'DD').attr('val'),
			head:'статуса заявки',
			op:'setup_zayav_status_del',
			func:function(res) {
				$('#status-spisok').html(res.html);
				sortable();
			}
		});
	})

	.on('click', '#setup_product .add', function() {
		var t = $(this),
			html = '<table style="border-spacing:10px">' +
				'<tr><td class="label r">Наименование:<td><input id="name" type="text" maxlength="100" style="width:250px" />' +
				'</table>',
			dialog = _dialog({
				top:60,
				width:390,
				head:'Добавление нового наименования изделия',
				content:html,
				submit:submit
			});
		$('#name').focus().keyEnter(submit);
		function submit() {
			var send = {
				op:'setup_product_add',
				name:$('#name').val()
			};
			if(!send.name) {
				dialog.err('Не указано наименование');
				$('#name').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						$('.spisok').html(res.html);
						dialog.close();
						_msg('Внесено!');
					} else
						dialog.abort();
				}, 'json');
			}
		}
	})
	.on('click', '#setup_product .img_edit', function() {
		var t = $(this);
		while(t[0].tagName != 'TR')
			t = t.parent();
		var id = t.attr('val'),
			name = t.find('.name a'),
			dog = t.find('.dog').html() ? 1 : 0,
			html = '<table style="border-spacing:10px">' +
				'<tr><td class="label">Наименование:<td><input id="name" type="text" maxlength="100" style="width:250px" value="' + name.html() + '" />' +
				'</table>',
			dialog = _dialog({
				top:60,
				width:390,
				head:'Редактирование наименования изделия',
				content:html,
				butSubmit:'Сохранить',
				submit:submit
			});
		$('#name').focus().keyEnter(submit);
		function submit() {
			var send = {
				op:'setup_product_edit',
				id:id,
				name:$('#name').val()
			};
			if(!send.name) {
				dialog.err('Не указано наименование');
				$('#name').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						$('.spisok').html(res.html);
						dialog.close();
						_msg('Сохранено!');
					} else
						dialog.abort();
				}, 'json');
			}
		}
	})
	.on('click', '#setup_product .img_del', function() {
		var p = _parent($(this));
		_dialogDel({
			id:p.attr('val'),
			head:'изделия',
			op:'setup_product_del',
			func:function() {
				p.remove();
			}
		});
	})

	.on('click', '#setup_product_sub .add', function() {
		var t = $(this),
			html = '<table style="border-spacing:10px">' +
				'<tr><td class="label">Наименование:<td><input id="name" type="text" maxlength="100" style="width:250px" />' +
				'</table>',
			dialog = _dialog({
				width:390,
				head:'Добавление нового подвида изделия',
				content:html,
				submit:submit
			});
		$('#name').focus().keyEnter(submit);
		function submit() {
			var send = {
				op:'setup_product_sub_add',
				product_id:PRODUCT_ID,
				name:$('#name').val()
			};
			if(!send.name) {
				dialog.err('Не указано наименование');
				$('#name').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						$('.spisok').html(res.html);
						dialog.close();
						_msg('Внесено!');
					} else
						dialog.abort();
				}, 'json');
			}
		}
	})
	.on('click', '#setup_product_sub .img_edit', function() {
		var t = $(this);
		while(t[0].tagName != 'TR')
			t = t.parent();
		var name = t.find('.name'),
			html = '<table style="border-spacing:10px">' +
				'<tr><td class="label">Наименование:<td><input id="name" type="text" style="width:250px" value="' + name.html() + '" />' +
				'</table>',
			dialog = _dialog({
				width:390,
				head:'Редактирование подвида изделия',
				content:html,
				butSubmit:'Сохранить',
				submit:submit
			});
		$('#name').focus().keyEnter(submit);
		function submit() {
			var send = {
				op:'setup_product_sub_edit',
				id:t.attr('val'),
				name:$('#name').val()
			};
			if(!send.name) {
				dialog.err('Не указано наименование');
				$('#name').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						name.html(send.name);
						dialog.close();
						_msg('Сохранено!');
					} else
						dialog.abort();
				}, 'json');
			}
		}
	})
	.on('click', '#setup_product_sub .img_del', function() {
		var p = _parent($(this));
		_dialogDel({
			id:p.attr('val'),
			head:'подвида изделия',
			op:'setup_product_sub_del',
			func:function() {
				p.remove();
			}
		});
	})

	.ready(function() {
		if($('#setup_my').length) {
			$('#pinset').click(function() {
				var html =
						'<table id="setup-tab">' +
							'<tr><td class="label">Новый пин-код:<td><input id="pin" type="password" maxlength="10" />' +
						'</table>',
					dialog = _dialog({
						width:300,
						head:'Установка нового пин-кода',
						content:html,
						butSubmit:'Установить',
						submit:submit
					});
				$('#pin').focus().keyEnter(submit);
				function submit() {
					var send = {
						op:'setup_my_pinset',
						pin:$.trim($('#pin').val())
					};
					if(!send.pin) {
						dialog.err('Введите пин-код');
						$('#pin').focus();
					} else if(send.pin.length < 3) {
						dialog.err('Длина пин-кода от 3 до 10 символов');
						$('#pin').focus();
					} else {
						dialog.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								dialog.close();
								_msg('Пин-код установлен');
								document.location.reload();
							} else
								dialog.abort();
						}, 'json');
					}
				}
			});
			$('#pinchange').click(function() {
				var html = '<table id="setup-tab">' +
						'<tr><td class="label">Текущий пин-код:<td><input id="oldpin" type="password" maxlength="10" />' +
						'<tr><td class="label">Новый пин-код:<td><input id="pin" type="password" maxlength="10" />' +
						'</table>',
					dialog = _dialog({
						width:300,
						head:'Изменение пин-кода',
						content:html,
						butSubmit:'Изменить',
						submit:submit
					});
				$('#oldpin').focus().keyEnter(submit);
				$('#pin').keyEnter(submit);
				function submit() {
					var send = {
						op:'setup_my_pinchange',
						oldpin: $.trim($('#oldpin').val()),
						pin: $.trim($('#pin').val())
					};
					if(!send.oldpin || !send.pin)
						dialog.err('Заполните оба поля');
					else if(send.oldpin.length < 3 || send.pin.length < 3)
						dialog.err('Длина пин-кода от 3 до 10 символов');
					else {
						dialog.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								dialog.close();
								_msg('Пин-код изменён.');
								document.location.reload();
							} else {
								dialog.abort();
								dialog.err(res.text);
							}
						}, 'json');
					}
				}
			});
			$('#pindel').click(function() {
				var html =
						'<table id="setup-tab">' +
							'<tr><td class="label">Текущий пин-код:<td><input id="oldpin" type="password" maxlength="10" />' +
						'</table>',
					dialog = _dialog({
						width:300,
						head:'Удаление пин-кода',
						content:html,
						butSubmit:'Применить',
						submit:submit
					});
				$('#oldpin').focus().keyEnter(submit);
				function submit() {
					var send = {
						op:'setup_my_pindel',
						oldpin:$.trim($('#oldpin').val())
					};
					if(!send.oldpin) {
						dialog.err('Поле не заполнено');
						$('#oldpin').focus();
					} else {
						dialog.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								dialog.close();
								_msg('Пин-код удалён');
								document.location.reload();
							} else {
								dialog.abort();
								dialog.err(res.text);
							}
						}, 'json');
					}
				}
			});
			$('#RULE_MY_PAY_SHOW_PERIOD')._select({
				spisok:[
					{uid:1,title:'за текущий день'},
					{uid:0,title:'за текущую неделю'},
					{uid:2,title:'за текущий месяц'}
				],
				func:setupRuleCheck
			});
		}
		if($('#setup_rule').length) {
			$('.img_del').click(function() {
				_dialogDel({
					id:RULE_VIEWER_ID,
					head:'сотрудника',
					op:'setup_worker_del',
					func:function() {
						location.href = URL + '&p=setup&d=worker';
					}
				});
			});
			$('#w-save').click(function() {
				var send = {
						op:'setup_worker_save',
						viewer_id:RULE_VIEWER_ID,
						first_name:$('#first_name').val(),
						last_name:$('#last_name').val(),
						middle_name:$('#middle_name').val(),
						post:$('#post').val()
					},
					but = $(this);
				if(!send.first_name) {
					err('Не указано имя');
					$('#first_name').focus();
				} else if(!send.last_name) {
					err('Не указана фамилия');
					$('#last_name').focus();
				} else {
					but.addClass('busy');
					$.post(AJAX_MAIN, send, function(res) {
						but.removeClass('busy');
						if(res.success)
							_msg('Сохранено');
					}, 'json');
				}
				function err(msg) {
					but.vkHint({
						msg:'<SPAN class="red">' + msg + '</SPAN>',
						top:-57,
						left:-6,
						indent:40,
						show:1,
						remove:1
					});
				}
			});
			$('#RULE_SALARY_SHOW')._check(setupRuleCheck);
			$('#RULE_SALARY_ZAYAV_ON_PAY')._check(setupRuleCheck);
			$('#RULE_SALARY_BONUS')._check(function(v, id) {
				var t = $(this);
				$('#' + id + '_check').next()[(v ? 'remove' : 'add') + 'Class']('vh');
				setupRuleCheck(v, id);
				$('#salary_bonus_sum').focus()
			});
			$('#salary_bonus_sum').keyEnter(function() {
				var o = $('#salary_bonus_sum'),
					send = {
						op:'salary_bonus_sum',
						worker_id:RULE_VIEWER_ID,
						sum:_cena(o.val())
					};

				if(!send.sum || send.sum > 100)
					return err('Некорректно введено значение');

				o.attr('disabled', 'disabled');

				$.post(AJAX_MAIN, send, function(res) {
					o.attr('disabled', false);
					if(res.success)
						_msg();
					else {
						err(res.text);
						o.focus();
					}
				}, 'json');

				function err(msg) {
					o.vkHint({
						msg:'<span class="red">' + msg + '</span>',
						top:-80,
						left:-9,
						indent:40,
						show:1,
						remove:1
					});
					return false;
				}
			});
			$('#RULE_APP_ENTER')._check(function(v, id) {
				$('#div-app-enter')[(v ? 'remove' : 'add') + 'Class']('dn');
				setupRuleCheck(v, id);
			});
			$('#RULE_SETUP_WORKER')._check(function(v, id) {
				$('#div-w-rule')[v ? 'show' : 'hide']();
				setupRuleCheck(v, id);
				$('#RULE_SETUP_RULES')._check(0);
			});
			$('#RULE_SETUP_RULES')._check(setupRuleCheck);
			$('#RULE_SETUP_REKVISIT')._check(setupRuleCheck);
			$('#RULE_SETUP_INVOICE')._check(setupRuleCheck);
			$('#RULE_HISTORY_VIEW')._check(setupRuleCheck);
			$('#RULE_INVOICE_TRANSFER')._check(setupRuleCheck);
			$('#RULE_INCOME_VIEW')._check(setupRuleCheck);
			$('#pin-clear').click(function() {
				var send = {
						op:'setup_worker_pin_clear',
						viewer_id:RULE_VIEWER_ID
					},
					but = $(this);
				if(but.hasClass('busy'))
					return;
				but.addClass('busy');
				$.post(AJAX_MAIN, send, function(res) {
					but.removeClass('busy');
					if(res.success) {
						_msg('Пин-код сброшен');
						but.prev().remove();
						but.remove();
					}
				}, 'json');
			});
		}
		if($('#setup_rekvisit').length) {
			$('textarea').autosize();
			$('.vkButton').click(function() {
				var t = $(this),
					send = {
						op:'setup_rekvisit',
						name:$('#name').val(),
						name_yur:$('#name_yur').val(),
						ogrn:$('#ogrn').val(),
						inn:$('#inn').val(),
						kpp:$('#kpp').val(),
						phone:$('#phone').val(),
						fax:$('#fax').val(),
						adres_yur:$('#adres_yur').val(),
						adres_ofice:$('#adres_ofice').val(),
						time_work:$('#time_work').val(),
						bank_name:$('#bank_name').val(),
						bank_bik:$('#bank_bik').val(),
						bank_account:$('#bank_account').val(),
						bank_account_corr:$('#bank_account_corr').val()
					};
				t.addClass('busy');
				$.post(AJAX_MAIN, send, function(res) {
					t.removeClass('busy');
					if(res.success)
						_msg('Информация сохранена.');
				}, 'json');
			});
		}
	});
