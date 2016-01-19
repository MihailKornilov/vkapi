var balansCategory = function(arr) {
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

		$('#name').focus().keyEnter(submit);

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

		$('#name').focus().keyEnter(submit);
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

	saZayavPoleEdit = function(o) {
		o = $.extend({
			id:0,
			name:'',
			about:'',
			const:'ZAYAV_'
		}, o);

		var html =
				'<table class="sa-tab" id="zayav-pole-tab">' +
					'<tr><td class="label">Наименование:<td><input type="text" id="name" value="' + o.name + '" />' +
					'<tr><td class="label topi">Структура:<td><textarea id="about">' + o.about + '</textarea>' +
					'<tr><td class="label">Константа:<td><input type="text" id="const" value="' + o.const + '" />' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:(o.id ? 'Изменение' : 'Внесение нового') + ' поля заявки',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name')
			.focus()
			.keyEnter(submit);
		$('#about').autosize();

		function submit() {
			var send = {
				op:'sa_zayav_pole_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val(),
				about:$('#about').val(),
				const:$('#const').val()
			};
			if(!send.name) {
				dialog.err('Не указано наименование');
				$('#name').focus();
				return;
			}
			if(!send.const) {
				dialog.err('Не указана константа');
				$('#const').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					$('#pole-spisok').html(res.html);
				} else {
					dialog.abort();
					dialog.err(res.text);
				}
			}, 'json');
		}
	},
	saZayavTypeEdit = function(o) {
		o = $.extend({
			id:0,
			name:''
		}, o);

		var html =
				'<table class="sa-tab" id="zayav-pole-tab">' +
					'<tr><td colspan="2">' +
							'<div class="_info">' +
								'Если это первый вид заявок, то все текущие заявки будут помещены в этот вид.' +
							'</div>' +
					'<tr><td class="label">Название:<td><input type="text" id="name" value="' + o.name + '" />' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:(o.id ? 'Изменение' : 'Внесение нового') + ' вида заявки',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name')
			.focus()
			.keyEnter(submit);

		function submit() {
			var send = {
				op:'sa_zayav_type_' + (o.id ? 'edit' : 'add'),
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

		$('#name,#predlog').keyEnter(submit);
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
	};

$(document)
	.on('click', '#sa-menu .add', function() {
		var html =
				'<table class="sa-tab" id="sa-menu-tab">' +
					'<tr><td class="label">Название:<td><input type="text" id="name" />' +
					'<tr><td class="label">p:<td><input type="text" id="p" />' +
				'</table>',
			dialog = _dialog({
				head:'Внесение нового раздела меню',
				content:html,
				submit:submit
			});

		$('#name').focus();
		$('#name,#p').keyEnter(submit);

		function submit() {
			var send = {
				op:'sa_menu_add',
				name:$('#name').val(),
				p:$('#p').val()
			};
			if(!send.name) {
				dialog.err('Не указано название');
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
	.on('click', '#sa-rule ._check', function() {
		var t = $(this),
			td = t.parent(),
			check = $('#' + t.find('input').attr('id')),
			send = {
				op:'sa_rule_flag',
				id:td.parent().attr('val'),
				value_name: td.attr('class'),
				v:_num(check.val())
			};
		if(td.hasClass('_busy'))
			return;
		td.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			td.removeClass('_busy');
			if(res.error)
				check._check(send.v ? 0 : 1);

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


	.on('click', '#sa-zayav #pole-add', saZayavPoleEdit)
	.on('click', '#sa-zayav .img_edit', function() {
		var t = $(this),
			p = _parent(t);
		saZayavPoleEdit({
			id:t.attr('val'),
			name:p.find('.name').html(),
			about:p.find('.about').html(),
			const:p.find('.const').html()
		});
	})
	.on('click', '#sa-zayav #pole-spisok ._check', function() {
		var t = $(this),
			inp = t.find('input'),
			send = {
				op:'sa_zayav_setup_use_change',
				type_id:SA_ZAYAV_TYPE_ID,
				id:_num(inp.attr('id').split('use')[1]),
				v:inp.val()
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				_msg();
		}, 'json');
	})

	.on('click', '#sa-zayav #type-add', saZayavTypeEdit)

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
