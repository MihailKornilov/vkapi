var saMenuEdit = function(o) {
		o = $.extend({
			id:0,
			name:'',
			about:'',
			p:''
		}, o);

		var html =
				'<table class="sa-tab" id="sa-menu-tab">' +
					'<tr><td class="label">Название:<td><input type="text" id="name" value="' + o.name + '" />' +
					'<tr><td class="label topi">Описание:<td><textarea id="about">' + o.about + '</textarea>' +
					'<tr><td class="label">p:<td><input type="text" id="p" value="' + o.p + '" />' +
				'</table>',
			dialog = _dialog({
				head:(o.id ? 'Редактирование' : 'Внесение нового') + ' раздела меню',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();
		$('#about').autosize();

		function submit() {
			var send = {
				op:'sa_menu_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val(),
				about:$('#about').val(),
				p:$('#p').val()
			};
			if(!send.name) {
				dialog.err('Не указано название');
				$('#name').focus();
				return;
			}
			if(!send.p) {
				dialog.err('Не указан link');
				$('#p').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg('Внесено');
					$('#spisok').html(res.html);
					sortable();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	balansCategory = function(arr) {
			arr = $.extend({
				id:0,
				name:''
			}, arr);

			var html =
				'<table class="sa-tab">' +
					'<tr><td class="label">Название:<td><input type="text" id="name" value="' + arr.name + '" />' +
				'</table>',
			dialog = _dialog({
				head:(arr.id ? 'Редактирование' : 'Внесение новой' ) + ' категории балансов',
				content:html,
				butSubmit:arr.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();

		function submit() {
			var send = {
				op:'sa_balans_category_' + (arr.id ? 'edit' : 'add'),
				id:arr.id,
				name:$('#name').val()
			};
			if(!send.name) {
				dialog.err('Не указано название');
				$('#name').focus();
			} else {
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
		}
	},
	balansAction = function(arr) {
		arr = $.extend({
			id:0,
			category_id:0,
			name:'',
			minus:0
		}, arr);

		var html =
				'<table class="sa-tab">' +
					'<tr><td class="label">Категория:<td><b>' + arr.category + '</b>' +
					'<tr><td class="label">Название:<td><input type="text" id="name" value="' + arr.name + '" />' +
					'<tr><td class="label">Минус:<td><input type="hidden" id="minus" value="' + arr.minus + '" />' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:(arr.id ? 'Редактирование' : 'Внесение нового' ) + ' действия',
				content:html,
				butSubmit:arr.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();
		$('#minus')._check();

		function submit() {
			var send = {
				op:'sa_balans_action_' + (arr.id ? 'edit' : 'add'),
				id:arr.id,
				category_id:arr.category_id,
				name:$('#name').val(),
				minus:$('#minus').val()
			};
			if(!send.name) {
				dialog.err('Не указано название');
				$('#name').focus();
			} else {
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
		}
	},

	saServiceEdit = function(o) {//добавление/редактирование названия вида деятельности
		o = $.extend({
			id:0,
			name:''
		}, o);

		var html =
	   (!o.id ? '<div class="_info">Если это первый вид заявок, то все текущие заявки будут помещены в этот вид.</div>' : '') +
				'<table class="bs10">' +
					'<tr><td class="label">Название:' +
						'<td><input type="text" id="name" class="w250" value="' + o.name + '" />' +
				'</table>',
			dialog = _dialog({
				head:(o.id ? 'Изменение' : 'Внесение нового') + ' вида заявки',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();

		function submit() {
			var send = {
				op:'sa_service_' + (o.id ? 'edit' : 'add'),
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
					document.location.reload();
				} else {
					dialog.abort();
					dialog.err(res.text);
				}
			}, 'json');
		}
	},

	saZayavPoleEdit = function(o) {
		o = $.extend({
			id:0,
			name:'',
			about:'',
			param1:''
		}, o);

		var html =
				'<table class="sa-tab">' +
					'<tr><td class="label">Тип поля:<td><b>' + SAZP_TYPE_NAME + '</b>' +
					'<tr><td class="label">Название:<td><input type="text" id="name" value="' + o.name + '" />' +
					'<tr><td class="label top">Описание:<td><textarea id="about">' + o.about + '</textarea>' +
					'<tr><td class="label">Параметр 1:<td><input type="text" id="param1" class="w250" placeholder="название параметра" value="' + o.param1 + '" />' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:(o.id ? 'Изменение' : 'Добавление') + ' поля заявки',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();
		$('#about').autosize();

		function submit() {
			var send = {
				op:'sa_zayav_pole_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				type_id:SAZP_TYPE_ID,
				name:$('#name').val(),
				about:$('#about').val(),
				param1:$('#param1').val()
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
	},
	saZayavServicePoleAdd = function(service_id, type_id) {
		var dialog = _dialog({
				top:20,
				width:600,
				head:'Добавление поля заявки - выбор',
				load:1,
				butSubmit:''
			}),
			send = {
				op:'sa_zayav_service_pole_load',
				service_id:service_id,
				type_id:type_id
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				dialog.content.html(res.html);
				$('.sel').click(function() {
					dialog.close();
					var t = $(this);
					saZayavServicePoleEdit({
						service_id:service_id,
						pole_id:t.find('.id').html(),
						type_id:type_id,
						name:t.find('.name').html(),
						about:t.find('.about').html(),
						param1:t.find('.param').html()
					});
				});
			} else
				dialog.loadError();
		}, 'json');
	},
	saZayavServicePoleEdit = function(o) {
		o = $.extend({
			id:0,
			service_id:0,
			type_id:0,
			pole_id:0,
			name:'',
			about:'',
			label:'',
			require:0,
			param1:'',
			param_v1:0
		}, o);

		var html =
				'<table class="sa-tab">' +
					'<tr><td class="label w150">Исходное название:<td><b>' + o.name + '</b>' +
		 (o.about ? '<tr><td class="label topi">Описание:<td><div class="_info">' + o.about + '</div>' : '') +
					'<tr><td class="label topi">Альтернативное название:' +
						'<td><textarea id="label" class="w250">' + o.label + '</textarea>' +
					'<tr' + (o.type_id == 1 ? '' : ' class="dn"') + '>' +
						'<td class="label">Обязательное заполнение:' +
						'<td><input type="hidden" id="require" value="' + o.require + '" />' +
					'<tr' + (o.param1 ? '' : ' class="dn"') + '>' +
						'<td class="label"><b>' + o.param1 + ':</b>' +
						'<td><input type="hidden" id="param_v1" value="' + o.param_v1 + '" />' +
				'</table>',
			dialog = _dialog({
				width:490,
				head:(o.id ? 'Изменение' : 'Добавление') + ' поля заявки',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#label').focus();
		$('#require')._check();
		$('#param_v1')._check();

		function submit() {
			var send = {
				op:'sa_zayav_service_pole_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				service_id:o.service_id,
				pole_id:o.pole_id,
				label:$('#label').val(),
				require:$('#require').val(),
				param_v1:$('#param_v1').val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					$('#spisok' + res.type_id).html(res.html);
				} else
					dialog.abort();
			}, 'json');
		}
	},

	saColorEdit = function(o) {
		o = $.extend({
			id:0,
			predlog:'',
			name:''
		}, o);

		var html =
				'<table class="sa-tab">' +
					'<tr><td class="label">Предлог:<td><input id="predlog" type="text" value="' + o.predlog + '" />' +
					'<tr><td class="label">Цвет:<td><input id="name" type="text" value="' + o.name + '" />' +
				'</table>',
			dialog = _dialog({
				head:(o.id ? 'Изменение' : 'Добавление нового') + ' цвета',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#predlog').focus();

		function submit() {
			var send = {
				op:'sa_color_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				predlog:$('#predlog').val(),
				name:$('#name').val()
			};
			if(!send.predlog) {
				dialog.err('Не указан предлог');
				$('#predlog').focus();
				return;
			}
			if(!send.name) {
				dialog.err('Не указан цвет');
				$('#name').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					$('#spisok').html(res.html);
				} else {
					dialog.abort();
					dialog.err(res.text);
				}
			}, 'json');
		}
	},
	saAppEdit = function(o) {
		o = $.extend({
			id:'',
			title:'',
			app_name:'',
			secret:''
		}, o);

		var html =
				'<table class="sa-tab">' +
					'<tr><td class="label"><b>app_id</b>:<td><input id="app_id" type="text" value="' + o.id + '"' + (o.id ? ' disabled' : '') + ' />' +
					'<tr><td class="label">title:<td><input id="title" type="text" value="' + o.title + '" />' +
					'<tr><td class="label">Название:<td><input id="app_name" type="text" value="' + o.app_name + '" />' +
					'<tr><td class="label">secret:<td><input id="secret" type="text" value="' + o.secret + '" />' +
				'</table>',
			dialog = _dialog({
				head:(o.id ? 'Изменение' : 'Добавление нового') + ' приложения',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#app_name').focus();

		function submit() {
			var send = {
				op:'sa_app_' + (o.id ? 'edit' : 'add'),
				id:_num(o.id ? o.id : $('#app_id').val()),
				title:$('#title').val(),
				app_name:$('#app_name').val(),
				secret:$('#secret').val()
			};
			if(!send.id) {
				dialog.err('Не корректный app_id');
				$('#app_id').focus();
				return;
			}
			if(!send.title) {
				dialog.err('Не указан title');
				$('#title').focus();
				return;
			}
			if(!send.app_name) {
				dialog.err('Не указано название');
				$('#app_name').focus();
				return;
			}
			if(!send.secret) {
				dialog.err('Не указан secret');
				$('#secret').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					$('#spisok').html(res.html);
				} else {
					dialog.abort();
					dialog.err(res.text);
				}
			}, 'json');
		}
	};

$(document)
	.on('click', '#sa-menu .add', saMenuEdit)
	.on('click', '#sa-menu .img_edit', function() {
		var t = _parent($(this), 'DD');
		saMenuEdit({
			id:t.find('.name').attr('val'),
			name:t.find('.name span').html(),
			about:t.find('.about').html(),
			p:t.find('.p').html()
		});
	})
	.on('click', '#sa-menu ._check', function() {//скрытие-показ разделов меню
		var t = $(this),
			send = {
			op:'sa_menu_show',
			id:_parent(t, 'DD').attr('val'),
			v:t.find('input').val()
		};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				_msg();
		}, 'json');
	})

	.on('click', '#sa-history .img_edit', function() {
		var id = _num($(this).attr('val')),
			html =
				'<table class="sa-tab" id="sa-history-tab">' +
					'<tr><td class="label">type_id:<td><input type="text" id="type_id" value="' + id + '" />' +
					'<tr><td><td>Если изменяется <b>type_id</b>, все записи со предыдущим значением будут изменены на новое.' +
					'<tr><td class="label topi">Текст:<td><textarea id="txt">' + $('#txt' + id).val() + '</textarea>' +
					'<tr><td class="label">Категория:<td><input type="hidden" id="category_ids" value="' + $('#ids' + id).val() + '" />' +
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
		$('#category_ids')._select({
			width:250,
			title0:'Не указана',
			spisok:CAT,
			multiselect:1
		});

		function submit() {
			var send = {
				op:'sa_history_type_edit',
				type_id_current:id,
				type_id:_num($('#type_id').val()),
				txt:$('#txt').val(),
				category_ids:$('#category_ids').val()
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
	.on('click', '#sa-history-cat .img_edit', function() {
		var t = _parent($(this), 'DD'),
			html =
				'<table class="sa-tab">' +
					'<tr><td class="label">Name:<td><input type="text" id="name" value="' + t.find('.name b').html() + '" />' +
					'<tr><td class="label topi">About:<td><textarea id="about">' + t.find('.about').html() + '</textarea>' +
					'<tr><td class="label topi">js_use:<td><input type="hidden" id="js_use"  value="' + (t.find('.js').html() ? 1 : 0) + '" />' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:360,
				head:'Редактирование категории истории действий',
				content:html,
				butSubmit:'Изменить',
				submit:submit
			});

		$('#name').focus();
		$('#about').autosize();
		$('#js_use')._check();

		function submit() {
			var send = {
				op:'sa_history_cat_edit',
				id:t.attr('val'),
				name:$('#name').val(),
				about:$('#about').val(),
				js_use:$('#js_use').val()
			};
			if(!send.name) {
				dialog.err('Не указано наименование');
				$('#name').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('Изменено');
						$('#spisok').html(res.html);
						sortable();
					} else
						dialog.abort();
				}, 'json');
			}
		}
	})

	.on('click', '#sa-rule .img_edit', function() {
		var t = _parent($(this)),
			html =
				'<table class="sa-tab" id="sa-rule-tab">' +
					'<tr><td class="label">Константа:<td><input type="text" id="key" value="' + t.find('.key b').html() + '" />' +
					'<tr><td class="label topi">Описание:<td><textarea id="about">' + t.find('.about').html() + '</textarea>' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:380,
				head:'Редактирование константы прав сотрудников',
				content:html,
				butSubmit:'Изменить',
				submit:submit
			});

		$('#key').focus();
		$('#about').autosize();

		function submit() {
			var send = {
				op:'sa_rule_edit',
				id: t.attr('val'),
				key:$('#key').val(),
				about:$('#about').val()
			};
			if(!send.key) {
				dialog.err('Не указан текст константы');
				$('#key').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('Изменено');
						$('#spisok').html(res.html);
					} else {
						dialog.abort();
						dialog.err(res.text);
					}
				}, 'json');
			}
		}
	})
	.on('keyup', '#sa-rule input', function(e) {
		if(e.keyCode != 13)
			return;

		var t = $(this),
			td = t.parent(),
			send = {
				op:'sa_rule_flag',
				id:td.parent().attr('val'),
				value_name: td.attr('class'),
				v:_num(t.val())
			};
		if(td.hasClass('_busy'))
			return;
		td.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			td.removeClass('_busy');
			if(res.success) {
				_msg();
				t.val(res.v);
			}
		}, 'json');
	})

	.on('click', '#sa-balans .head .img_edit', function() {
		var t = _parent($(this));
		balansCategory({
			id:$(this).attr('val'),
			name:t.find('b.c-name').html()
		});
	})
	.on('click', '#sa-balans .head .img_del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'категории балансов',
			op:'sa_balans_category_del',
			func:function(res) {
				$('#spisok').html(res.html);
			}
		});
	})
	.on('click', '#sa-balans .head .img_add', function() {
		balansAction({
			category_id:$(this).attr('val'),
			category:_parent($(this), 'TABLE').find('b.c-name').html()
		});
	})
	.on('click', '.balans-action-edit', function() {
		var t = _parent($(this));
		balansAction({
			id:t.attr('val'),
			category:_parent(t, 'TABLE').find('b.c-name').html(),
			name:t.find('.name').html(),
			minus:t.find('.minus').length
		});
	})
	.on('click', '.balans-action-del', function() {
		_dialogDel({
			id:_parent($(this)).attr('val'),
			head:'действия для балансов',
			op:'sa_balans_action_del',
			func:function(res) {
				$('#spisok').html(res.html);
			}
		});
	})

	.on('click', '#sa-zayav-pole .img_edit', function() {
		var t = $(this),
			p = _parent(t);
		saZayavPoleEdit({
			id:t.attr('val'),
			name:p.find('.name').html(),
			about:p.find('.about').html(),
			param1:p.find('.param').html()
		});
	})
	.on('click', '#sa-zayav-pole .img_del', function() {
		var t = $(this),
			p = _parent(t);
		_dialogDel({
			id:t.attr('val'),
			head:'поля',
			op:'sa_zayav_pole_del',
			func:function() {
				p.remove();
			}
		});
	})

	.on('click', '#sa-zayav-service .edit', function() {
		var t = $(this);
		saServiceEdit({
			id:t.attr('val'),
			name:$('.link.sel').html()
		});
	})
	.on('mouseover', '#sa-zayav-service .show', function() {
		$(this).removeClass('show');
	})
	.on('click', '#sa-zayav-service .img_edit', function() {
		var t = $(this),
			p = _parent(t);
		saZayavServicePoleEdit({
			id:t.attr('val'),
			name:p.find('.e-name').val(),
			about:p.find('.about').html(),
			label:p.find('.e-label').val(),
			type_id:p.find('.type_id').val(),
			require:p.find('.require').val(),
			param1:p.find('.param1').val(),
			param_v1:p.find('.param_v1').val()
		});
	})
	.on('click', '#sa-zayav-service .img_del', function() {
		var t = $(this),
			p = _parent(t, 'DD');
		_dialogDel({
			id:t.attr('val'),
			head:'поля',
			op:'sa_zayav_service_pole_del',
			func:function() {
				p.remove();
			}
		});
	})

	.on('click', '#sa-color .add', saColorEdit)
	.on('click', '#sa-color .img_edit', function() {
		var t = $(this),
			p = _parent(t);
		saColorEdit({
			id:t.attr('val'),
			predlog:p.find('.predlog').html(),
			name:p.find('.name').html()
		});
	})
	.on('click', '#sa-color .img_del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'цвета',
			op:'sa_color_del',
			func:function(res) {
				$('#spisok').html(res.html);
			}
		});
	})

	.on('click', '#sa-count .client', function() {
		var dialog = _dialog({
			top:20,
			width:500,
			head:'Обновление балансов клиентов',
			load:1,
			butSubmit:''
		});

		var send = {
			op:'sa_count_client_load'
		};

		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				dialog.content.html(res.html);
			} else
				dialog.loadError();
		}, 'json');

	})
	.on('click', '.client-balans-repair', function() {
		var t = $(this),
			send = {
				op:'sa_count_client_balans_repair',
				client_id:t.attr('val')
			};

		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				_msg();
				t.remove();
			}
		}, 'json');

	})

	.on('click', '#sa-count .zayav', function() {
		var dialog = _dialog({
			top:20,
			width:500,
			head:'Обновление балансов заявок',
			load:1,
			butSubmit:''
		});

		var send = {
			op:'sa_count_zayav_load'
		};

		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				dialog.content.html(res.html);
			} else
				dialog.loadError();
		}, 'json');

	})
	.on('click', '.zayav-balans-repair', function() {
		var t = $(this),
			send = {
				op:'sa_count_zayav_balans_repair',
				zayav_id:t.attr('val')
			};

		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				_msg();
				t.remove();
			}
		}, 'json');

	})
	.on('click', '#sa-count .tovar-set-find-update', function() {
		var t = $(this),
			send = {
				op:'sa_count_tovar_set_find_update',
				start:t.find('em').html()
			};
		t.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			t.removeClass('_busy');
			if(res.success) {
				_msg();
				t.find('em').html(res.start);
			}
		}, 'json');

	})
	.on('click', '#sa-count .tovar-articul-update', function() {
		var t = $(this),
			send = {
				op:'sa_count_tovar_articul_update'
			};
		t.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			t.removeClass('_busy');
			if(res.success)
				_msg();
		}, 'json');

	})
	.on('click', '.tovar-avai-check', function() {
		var dialog = _dialog({
			top:20,
			width:500,
			head:'Корректность наличия товара',
			load:1,
			butSubmit:''
		});

		var send = {
			op:'sa_count_tovar_avai_load'
		};

		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				dialog.content.html(res.html);
			} else
				dialog.loadError();
		}, 'json');
	})
	.on('click', '.tovar-avai-repair', function() {
		var t = $(this),
			send = {
				op:'sa_count_tovar_avai_repair',
				tovar_id:t.attr('val')
			};

		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				_msg();
				t.remove();
			}
		}, 'json');

	})


	.on('click', '#sa-app .add', saAppEdit)
	.on('click', '#sa-app .img_edit', function() {
		var t = $(this),
			p = _parent(t);
		saAppEdit({
			id:t.attr('val'),
			title:p.find('.title').html(),
			app_name:p.find('.app_name').html(),
			secret:p.find('.secret').val()
		});
	})

	.on('click', '#sa-user .action', function() {
		var t = $(this),
			un = t;
		while(!un.hasClass('un'))
			un = un.parent();
		var send = {
			op:'user_action',
			viewer_id:un.attr('val')
		};
		t.html('&nbsp;').addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				t.after(res.html).remove();
			else
				t.html('Действие').removeClass('_busy');
		}, 'json');
	})

	.on('click', '#sa-ws-info .ws_status_change', function() {
		var t = $(this),
			send = {
				op:'ws_status_change',
				ws_id:t.attr('val')
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				document.location.reload();
			else
				t.vkHint({
					msg:'<SPAN class=red>' + res.text + '</SPAN>',
					top:-47,
					left:27,
					indent:50,
					show:1,
					remove:1
				});
		}, 'json');
	})

	.on('click', '#sa-ws-info .ws_enter', function() {
		_cookie('sa_viewer_id', $(this).attr('val'));
		document.location.reload();
	})
	.on('click', '#sa-ws-info .ws_del', function() {
		var t = $(this);
		var dialog = _dialog({
			top:110,
			width:250,
			head:'Удаление организации',
			content:'<center>Подтвердите удаление организации.</center>',
			butSubmit:'Удалить',
			submit:submit
		});
		function submit() {
			var send = {
				op:'ws_del',
				ws_id:t.attr('val')
			};
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success)
					document.location.reload();
			}, 'json');
		}
	})
	.on('click', '#sa-ws-info .ws_client_balans', function() {
		var t = $(this),
			send = {
				op:'sa_ws_client_balans',
				ws_id:t.attr('val')
			};
		t.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			t.removeClass('_busy');
			if(res.success) {
				t.next().remove('span');
				t.after('<span> Изменено: ' + res.count + '. Время: ' + res.time + '</span>');
			}
		}, 'json');
	})
	.on('click', '#sa-ws-info .ws_zayav_balans', function() {
		var t = $(this),
			send = {
				op:'sa_ws_zayav_balans',
				ws_id:t.attr('val')
			};
		t.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			t.removeClass('_busy');
			if(res.success) {
				t.next().remove('span');
				t.after('<span> Изменено: ' + res.count + '. Время: ' + res.time + '</span>');
			}
		}, 'json');
	})

	.ready(function() {
		$('#app-id').vkHint({
			msg:$('#app-id').attr('val'),
			ugol:'top',
			width:120,
			indent:'right',
			top:32,
			left:484
		});

		if($('#sa-history').length) {
			$('textarea').autosize();
			$('.add.const').click(function() {
				var html =
						'<table class="sa-tab" id="sa-history-tab">' +
							'<tr><td class="label">type_id:<td><input type="text" id="type_id" />' +
							'<tr><td class="label topi">Текст:<td><textarea id="txt"></textarea>' +
							'<tr><td class="label">Категория:<td><input type="hidden" id="category_ids" />' +
						'</table>',
					dialog = _dialog({
						top:30,
						width:520,
						head:'Внесение новой константы для истории действий',
						content:html,
						submit:submit
					});

				$('#txt').focus().autosize();
				$('#category_ids')._select({
					width:250,
					title0:'Не указана',
					spisok:CAT,
					multiselect:1
				});

				function submit() {
					var send = {
						op:'sa_history_type_add',
						type_id:$('#type_id').val(),
						txt:$('#txt').val(),
						category_ids:$('#category_ids').val()
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
			});
		}
		if($('#sa-history-cat').length) {
			$('.add').click(function() {
				var html =
						'<table class="sa-tab">' +
							'<tr><td class="label">Name:<td><input type="text" id="name" />' +
							'<tr><td class="label topi">About:<td><textarea id="about"></textarea>' +
							'<tr><td class="label topi">js_use:<td><input type="hidden" id="js_use" />' +
						'</table>',
					dialog = _dialog({
						top:30,
						width:360,
						head:'Внесение новой категории истории действий',
						content:html,
						submit:submit
					});

				$('#name').focus();
				$('#about').autosize();
				$('#js_use')._check();

				function submit() {
					var send = {
						op:'sa_history_cat_add',
						name:$('#name').val(),
						about:$('#about').val(),
						js_use:$('#js_use').val()
					};
					if(!send.name) {
						dialog.err('Не указано наименование');
						$('#name').focus();
					} else {
						dialog.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								dialog.close();
								_msg('Внесено');
								$('#spisok').html(res.html);
								sortable();
							} else
								dialog.abort();
						}, 'json');
					}
				}
			});
		}

		if($('#sa-rule').length) {
			$('.add').click(function() {
				var html =
						'<table class="sa-tab" id="sa-rule-tab">' +
							'<tr><td class="label">Константа:<td><input type="text" id="key" />' +
							'<tr><td class="label topi">Описание:<td><textarea id="about"></textarea>' +
						'</table>',
					dialog = _dialog({
						top:30,
						width:380,
						head:'Внесение новой константы прав сотрудников',
						content:html,
						submit:submit
					});

				$('#key').focus();
				$('#about').autosize();

				function submit() {
					var send = {
						op:'sa_rule_add',
						key:$('#key').val(),
						about:$('#about').val()
					};
					if(!send.key) {
						dialog.err('Не указан текст константы');
						$('#key').focus();
					} else {
						dialog.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								dialog.close();
								_msg('Внесено');
								$('#spisok').html(res.html);
							} else {
								dialog.abort();
								dialog.err(res.text);
							}
						}, 'json');
					}
				}
			});
		}

		if($('#sa-balans').length)
			$('#category-add').click(balansCategory);
	});
