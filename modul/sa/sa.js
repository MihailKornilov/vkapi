var saMenuEdit = function(o) {
		o = $.extend({
			id:0,
			parent_id:0,
			dop_id:0,
			name:'',
			about:'',
			hidden:0,
			norule:0,
			func_menu:'',
			func_page:'',
			dop_menu_type:0,
			def:0
		}, o);

		var html =
				'<table class="bs10">' +
					'<tr><td class="label r b">main:<td><input type="hidden" id="parent_id" value="' + o.parent_id + '" />' +
					'<tr class="tr-dop dn">' +
						'<td class="label r">dop:' +
						'<td><input type="hidden" id="dop_id" value="' + o.dop_id + '" />' +
					'<tr><td class="label r w150">Название:' +
						'<td><input type="text" id="name" class="w250" value="' + o.name + '" />' +
					'<tr><td class="label r topi">Описание:<td><textarea id="about" class="w250">' + o.about + '</textarea>' +
					'<tr><td class="label r">Скрывать страницу:<td><input type="hidden" id="hidden" value="' + o.hidden + '" />' +
					'<tr><td class="label r">Всегда доступна<br />(без настроек прав):' +
						'<td><input type="hidden" id="norule" value="' + o.norule + '" />' +
					'<tr><td class="label r">func menu:<td><input type="text" id="func_menu" value="' + o.func_menu + '" />' +
					'<tr><td class="label r">func page:<td><input type="text" id="func_page" class="b" value="' + o.func_page + '" />' +
					'<tr><td class="label r">dop menu type:<td><input type="hidden" id="dop_menu_type" value="' + o.dop_menu_type + '" />' +
					'<tr' + (o.id ? '' : ' class="dn"') + '>' +
						'<td class="label r">APP:' +
						'<td><input type="hidden" id="def" value="' + o.def + '" />' +
				'</table>',
			dialog = _dialog({
				width:480,
				head:(o.id ? 'Редактирование' : 'Внесение нового') + ' раздела меню',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#parent_id')._select({
			title0:'---',
			spisok:MENU_MAIN,
			func:parentSet
		});
		$('#dop_id')._select({
			title0:'---',
			spisok:[]
		});
		parentSet(o.parent_id);
		$('#name').focus();
		$('#about').autosize();
		$('#hidden')._check();
		$('#norule')._check();
		$('#dop_menu_type')._select({
			width:200,
			title0:'Без меню',
			spisok:[
				{uid:1,title:'справа - setup'},
				{uid:2,title:'справа - обычное'},
				{uid:3,title:'горизонтальное - dopLinks'}
			]
		});
		$('#def')._check({
			name:'использовать по умолчанию'
		});

		function parentSet(parent_id) {
			$('#dop_id')
				._select(MENU_DOP[parent_id] ? MENU_DOP[parent_id] : [])
				._select(o.dop_id);
			$('.tr-dop')[(MENU_DOP[parent_id] ? 'remove' : 'add') + 'Class']('dn');
			o.dop_id = 0
		}
		function submit() {
			var send = {
				op:'sa_menu_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				parent_id:$('#parent_id').val(),
				dop_id:$('#dop_id').val(),
				name:$('#name').val(),
				about:$('#about').val(),
				hidden:$('#hidden').val(),
				norule:$('#norule').val(),
				func_menu:$('#func_menu').val(),
				func_page:$('#func_page').val(),
				dop_menu_type:$('#dop_menu_type').val(),
				def:$('#def').val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					$('#spisok').html(res.main);
				} else
					dialog.abort();
			}, 'json');
		}
	},
	saMenuSort = function(parent_id) {
		var dialog = _dialog({
				width:420,
				head:'Сортировнка разделов',
				load:1,
				butSubmit:'',
				butCancel:'Закрыть',
				cancel:cancel
			}),
			send = {
				op:'sa_menu_sort',
				parent_id:parent_id
			};
		$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.content.html(res.html);
					sortable();
				} else
					dialog.loadError(res.text);
			}, 'json');

		function cancel() {
			location.reload();
		}
	},
	balansCategory = function(arr) {
			arr = $.extend({
				id:0,
				name:''
			}, arr);

			var html =
				'<table class="bs10">' +
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
				'<table class="bs10">' +
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

	saHistoryEdit = function(o) {//внесение/редактирование истории действий
		o = $.extend({
			id:0,
			txt:'',
			txt_client:'',
			txt_zayav:'',
			txt_schet:'',
			ids:''
		}, o);

		var html =
				'<table class="bs10">' +
					'<tr><td class="label r w125">type_id:<td><input type="text" class="w70" id="type_id" value="' + (o.id || TYPE_ID_MAX) + '" />' +
			(o.id ? '<tr><td><td><div class="_info">Если изменяется <b>type_id</b>, все записи со предыдущим значением будут изменены на новое.</div>' : '') +
					'<tr><td class="label r topi">Категория:<td><input type="hidden" id="category_ids" value="' + o.ids + '" />' +
					'<tr><td><td>' +
					'<tr><td class="label r topi b">Основной текст:<td><textarea class="w400 min" id="txt">' + o.txt + '</textarea>' +
					'<tr><td class="label r topi">Текст для клиента:<td><textarea class="w400 min" id="txt_client">' + o.txt_client + '</textarea>' +
					'<tr><td class="label r topi">Текст для заявки:<td><textarea class="w400 min" id="txt_zayav">' + o.txt_zayav + '</textarea>' +
					'<tr><td class="label r topi">Текст для счёта:<td><textarea class="w400 min" id="txt_schet">' + o.txt_schet + '</textarea>' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:600,
				head:(o.id ? 'Редактирование' : 'Внесение') + ' константы истории действий',
				content:html,
				butSubmit:o.id ? 'Изменить' : 'Внести',
				submit:submit
			});

		$('#txt').focus();
		dialog.content.find('textarea').autosize();
		$('#category_ids')._select({
			width:350,
			title0:'Не указана',
			spisok:CAT,
			multiselect:1
		});

		function submit() {
			var send = {
				op:'sa_history_type_' + (o.id ? 'edit' : 'add'),
				type_id_current:o.id,
				type_id:$('#type_id').val(),
				category_ids:$('#category_ids').val(),
				txt:$('#txt').val(),
				txt_client:$('#txt_client').val(),
				txt_zayav:$('#txt_zayav').val(),
				txt_schet:$('#txt_schet').val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					$('#spisok').html(res.html);
					$('textarea').autosize();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},

	saRuleEdit = function(o) {
		o = $.extend({
			id:0,
			key:'',
			name:'',
			about:''
		}, o);
		
		var html =
				'<table class="bs10">' +
					'<tr><td class="label r">Константа:<td><input type="text" id="key" class="w230" value="' + o.key + '" />' +
					'<tr><td class="label r">Имя:<td><input type="text" id="name" class="w230" value="' + o.name + '" />' +
					'<tr><td class="label r topi">Описание:<td><textarea id="about" class="w230">' + o.about + '</textarea>' +
				'</table>',
			dialog = _dialog({
				head:(o.id ? 'Редактирование' : 'Внесение новой') + ' константы прав сотрудников',
				content:html,
				butSubmit:(o.id ? 'Изменить' : 'Внести'),
				submit:submit
			});

		$('#key').focus();
		$('#about').autosize();

		function submit() {
			var send = {
				op:'sa_rule_edit',
				id:o.id,
				key:$('#key').val(),
				name:$('#name').val(),
				about:$('#about').val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					$('#spisok').html(res.html);
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	
	saClientPoleEdit = function(o) {
		o = $.extend({
			id:0,
			name:'',
			about:''
		}, o);

		var html =
				'<table class="bs10">' +
					'<tr><td class="label">Тип поля:<td><b>' + SA_CLIENT_POLE_TYPE_NAME + '</b>' +
					'<tr><td class="label">Название:<td><input type="text" id="name" class="w250" value="' + o.name + '" />' +
					'<tr><td class="label top">Описание:<td><textarea id="about" class="w250">' + o.about + '</textarea>' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:(o.id ? 'Изменение' : 'Добавление') + ' поля клиента',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();
		$('#about').autosize();

		function submit() {
			var send = {
				op:'sa_client_pole_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				type_id:SA_CLIENT_POLE_TYPE_ID,
				name:$('#name').val(),
				about:$('#about').val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					$('#spisok').html(res.html);
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	saClientCategoryEdit = function(o) {//добавление/редактирование названия вида деятельности
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
				head:(o.id ? 'Изменение' : 'Внесение новой') + ' категории клиенов',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();

		function submit() {
			var send = {
				op:'sa_client_category_' + (o.id ? 'edit' : 'add'),
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
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	saClientCategoryPoleLoad = function(category_id, type_id) {
		var dialog = _dialog({
				top:20,
				width:600,
				head:'Добавление поля клиента - выбор',
				load:1,
				butSubmit:''
			}),
			send = {
				op:'sa_client_category_pole_load',
				category_id:category_id,
				type_id:type_id
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				dialog.content.html(res.html);
				$('.sel').click(function() {
					dialog.close();
					var t = $(this);
					saClientCategoryPoleEdit({
						pole_id:t.find('.id').html(),
						category_id:category_id,
						type_id:type_id,
						name:t.find('.name').html(),
						about:t.find('.about').html()
					});
				});
			} else
				dialog.loadError(res.text);
		}, 'json');
	},
	saClientCategoryPoleEdit = function(o) {
		o = $.extend({
			id:0,
			category_id:0,
			type_id:0,
			pole_id:0,
			name:'',
			about:'',
			label:'',
			require:0
		}, o);

		var html =
				'<table class="bs10">' +
					'<tr><td class="label w150">Исходное название:<td><b>' + o.name + '</b>' +
		 (o.about ? '<tr><td class="label topi">Описание:<td><div class="_info">' + o.about + '</div>' : '') +
					'<tr><td class="label topi">Альтернативное название:' +
						'<td><textarea id="label" class="w250">' + o.label + '</textarea>' +
					'<tr' + (o.type_id == 1 ? '' : ' class="dn"') + '>' +
						'<td class="label">Обязательное заполнение:' +
						'<td><input type="hidden" id="require" value="' + o.require + '" />' +
				'</table>',
			dialog = _dialog({
				width:490,
				head:(o.id ? 'Изменение' : 'Добавление') + ' поля клиента',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#label').focus();
		$('#require')._check();

		function submit() {
			var send = {
				op:'sa_client_category_pole_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				category_id:o.category_id,
				pole_id:o.pole_id,
				label:$('#label').val(),
				require:$('#require').val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					$('#spisok' + res.type_id).html(res.html);
				} else
					dialog.abort(res.text);
			}, 'json');
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
			dialog.post(send, 'reload');
		}
	},
	saZayavPoleEdit = function(o) {
		o = $.extend({
			id:0,
			name:'',
			about:'',
			param1:'',
			param2:''
		}, o);

		var html =
				'<table class="bs10">' +
					'<tr><td class="label">Тип поля:<td><b>' + SAZP_TYPE_NAME + '</b>' +
					'<tr><td class="label">Название:<td><input type="text" id="name" class="w350" value="' + o.name + '" />' +
					'<tr><td class="label topi">Описание:<td><textarea id="about" class="w350">' + o.about + '</textarea>' +
					'<tr><td class="label">Параметр 1:<td><input type="text" id="param1" class="w350" placeholder="название параметра" value="' + o.param1 + '" />' +
					'<tr><td class="label">Параметр 2:<td><input type="text" id="param2" class="w350" placeholder="название параметра" value="' + o.param2 + '" />' +
				'</table>',
			dialog = _dialog({
				width:500,
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
				param1:$('#param1').val(),
				param2:$('#param2').val()
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
						param1:t.find('.param1').html(),
						param2:t.find('.param2').html()
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
			param_v1:0,
			param2:'',
			param_v2:0
		}, o);

		var html =
				'<table class="bs10">' +
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
					'<tr' + (o.param2 ? '' : ' class="dn"') + '>' +
						'<td class="label"><b>' + o.param2 + ':</b>' +
						'<td><input type="hidden" id="param_v2" value="' + o.param_v2 + '" />' +
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
		$('#param_v2')._check();

		function submit() {
			var send = {
				op:'sa_zayav_service_pole_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				service_id:o.service_id,
				pole_id:o.pole_id,
				label:$('#label').val(),
				require:$('#require').val(),
				param_v1:$('#param_v1').val(),
				param_v2:$('#param_v2').val()
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

	saTovarMeasureEdit = function(o) {
		o = $.extend({
			id:0,
			short:'',
			name:'',
			about:'',
			fraction:0,
			area:0
		}, o);

		var html =
				'<table class="bs10">' +
					'<tr><td class="label">Короткое название:<td><input type="text" id="short" class="w50" value="' + o.short + '" />' +
					'<tr><td class="label">Полное название:<td><input type="text" id="name" value="' + o.name + '" />' +
					'<tr><td class="label top">Описание:<td><textarea id="about">' + o.about + '</textarea>' +
					'<tr><td class="label">Дробь:<td><input type="hidden" id="fraction" value="' + o.fraction + '" />' +
					'<tr><td class="label">Площадь:<td><input type="hidden" id="area" value="' + o.area + '" />' +
				'</table>',
			dialog = _dialog({
				width:420,
				head:(o.id ? 'Изменение' : 'Добавление') + ' единицы измерения',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#short').focus();
		$('#about').autosize();
		$('#fraction')._check();
		$('#area')._check();

		function submit() {
			var send = {
				op:'sa_tovar_measure_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				short:$('#short').val(),
				name:$('#name').val(),
				about:$('#about').val(),
				fraction:$('#fraction').val(),
				area:$('#area').val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					$('#spisok').html(res.html);
					sortable();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},

	saZayavBalans = function(id) {//правка баланса избранной заявки
		var dialog = _dialog({
			top:20,
			width:500,
			head:'Обновление балансов заявок',
			load:1,
			butSubmit:''
		});

		$.post(AJAX_MAIN, {op:'sa_zayav_load'}, function(res) {
			if(res.success) {
				dialog.content.html(res.html);
			} else
				dialog.loadError();
		}, 'json');
		return dialog;
	},
	saZayavBalansRepair = function(id) {//правка баланса избранной заявки
		var send = {
				op:'sa_zayav_balans_repair',
				zayav_id:id
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				_msg();
				$('#rep' + id).remove();
			}
		}, 'json');
	},
	saZayavBalansRepairAll = function(ids) {//правка баланса нескольких заявок
		var send = {
			op:'sa_zayav_balans_repair_all',
			ids:ids
		};
		$('#rep-all').addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			$('#rep-all').removeClass('_busy');
			if(res.success) {
				_msg();
				zbDialog.close();
				zbDialog = saZayavBalans();
			}
		}, 'json');
	},

	saColorEdit = function(o) {
		o = $.extend({
			id:0,
			predlog:'',
			name:''
		}, o);

		var html =
				'<table class="bs10">' +
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

	saTemplateDefaultEdit = function(o) {//Внесение/редактирование шаблонов по умолчанию
		o = $.extend({
			id:0,
			name:'',
			attach_id:0,
			name_link:'',
			name_file:'',
			use:''
		}, o);
		var html =
				'<table class="bs10">' +
					'<tr><td class="label r">Название:' +
						'<td><input type="text" id="name" class="w250" value="' + o.name + '" />' +
					'<tr><td class="label r">Файл шаблона:' +
						'<td><input type="hidden" id="attach_id" value="' + o.attach_id + '" />' +
					'<tr><td class="label r">Текст ссылки:' +
						'<td><input type="text" id="name_link" class="w250" value="' + o.name_link + '" />' +
					'<tr><td class="label r">Имя файла документа:' +
						'<td><input type="text" id="name_file" class="w250" value="' + o.name_file + '" />' +
					'<tr><td class="label r">Применение:' +
						'<td><input type="text" id="use" class="w100" value="' + o.use + '" />' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:440,
				head:(o.id ? 'Изменение' : 'Добавление нового') + ' шаблона',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#attach_id')._attach({
			type:'button',
			title:'загрузить шаблон',
			format:'docx,xls,xlsx',
			noapp:1
		});

		function submit() {
			var send = {
				op:'sa_template_default_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val(),
				attach_id:$('#attach_id').val(),
				name_link:$('#name_link').val(),
				name_file:$('#name_file').val(),
				use:$('#use').val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					$('#spisok-def').html(res.html);
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	saTemplateGroupEdit = function(o) {//Внесение/редактирование группы шаблонов документов
		o = $.extend({
			id:0,
			name:'',
			table_name:''
		}, o);

		var html =
				'<table class="bs10">' +
					'<tr><td class="label">Название:' +
						'<td><input type="text" id="name" class="w230" value="' + o.name + '" />' +
					'<tr><td class="label">Таблица:' +
						'<td><input type="text" id="table_name" class="w230" value="' + o.table_name + '" />' +
				'</table>',
			dialog = _dialog({
				head:(o.id ? 'Изменение' : 'Добавление новой') + ' группы шаблонов',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();

		function submit() {
			var send = {
				op:'sa_template_group_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val(),
				table_name:$('#table_name').val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					$('#spisok').html(res.html);
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	saTemplateVarEdit = function(id) {//Внесение/редактирование переменной шаблонов документов
		var dialog = _dialog({
				width:400,
				head:(id ? 'Изменение' : 'Добавление новой') + ' переменной для шаблонов',
				load:1,
				butSubmit:id ? 'Сохранить' : 'Внести',
				submit:submit
			}),
			send = {
				op:'sa_template_var_load',
				id:_num(id)
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				loaded(res);
			else
				dialog.loadError();
		}, 'json');

		function loaded(res) {
			var html =
				'<table class="bs10">' +
					'<tr><td class="label r">Группа:' +
						'<td><input type="hidden" id="group_id" value="' + res.group_id + '" />' +
					'<tr><td class="label r">Название:' +
						'<td><input type="text" id="name" class="w230" value="' + res.name + '" />' +
					'<tr><td class="label r">Код:' +
						'<td><input type="text" id="v" class="w150 b" value="' + res.v + '" />' +
					'<tr><td class="label r">Колонка в таблице:' +
						'<td><input type="text" id="col_name" class="w150" value="' + res.col_name + '" />' +
				'</table>';
			dialog.content.html(html);

			$('#group_id')._select({
				width:200,
				title0:'Группа не выбрана',
				spisok:res.group_spisok
			});
		}
		function submit() {
			var send = {
				op:'sa_template_var_' + (id ? 'edit' : 'add'),
				id:id,
				group_id:$('#group_id').val(),
				name:$('#name').val(),
				v:$('#v').val(),
				col_name:$('#col_name').val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					$('#spisok-var').html(res.html);
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	saTemplateVarDel = function(id) {//удаление переменной шаблонов документов
		_dialogDel({
			id:id,
			head:'переменной шаблона',
			op:'sa_template_var_del',
			func:function(res) {
				$('#spisok-var').html(res.html);
			}
		});
	},

	saAppEdit = function(o) {
		o = $.extend({
			id:'',
			title:'',
			app_name:'',
			secret:''
		}, o);

		var html =
				'<table class="bs10">' +
					'<tr><td class="label"><b>app_id</b>:<td><input id="app_id" type="text" value="' + o.id + '"' + (o.id ? ' disabled' : '') + ' />' +
					'<tr><td class="label">title:<td><input id="title" type="text" value="' + o.title + '" />' +
					'<tr><td class="label">Название:<td><input type="text" id="app_name" class="w230" value="' + o.app_name + '" />' +
					'<tr><td class="label">secret:<td><input type="text" id="secret" class="w230" value="' + o.secret + '" />' +
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
	},
	saAppCacheClear = function(app_id) {
		var send = {
				op:'sa_app_cache_clear',
				app_id:app_id
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				_msg();
		}, 'json');
	};

$(document)
	.on('click', '#sa-menu .icon-edit', function() {
		var t = $(this),
			p = _parent(t, 'TR');
		saMenuEdit({
			id:t.attr('val'),
			parent_id:p.find('.parent_id').val(),
			dop_id:p.find('.dop_id').val(),
			name:p.find('.name').html(),
			about:p.find('.about').html(),
			hidden:p.find('.hidden').val(),
			norule:p.find('.norule').val(),
			func_menu:p.find('.func_menu').html(),
			func_page:p.find('.func_page').html(),
			dop_menu_type:_num(p.find('.dop_menu_type').html()),
			def:_bool(p.find('.def').val())
		});
	})
	.on('click', '#sa-menu .show ._check', function() {//скрытие-показ разделов меню
		var t = $(this),
			inp = t.find('input'),
			send = {
				op:'sa_menu_show',
				id:inp.attr('id').split('show')[1],
				v:inp.val()
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				_msg();
				$('#spisok').html(res.main);
				sortable();
			}
		}, 'json');
	})
	.on('click', '#sa-menu .access ._check', function() {//доступ для пользователей по умолчанию
		var t = $(this),
			inp = t.find('input'),
			send = {
				op:'sa_menu_access',
				id:inp.attr('id').split('access')[1],
				v:t.find('input').val()
			};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				_msg();
		}, 'json');
	})

	.on('click', '#sa-history .icon-edit', function() {
		var t = $(this),
			p = _parent(t);
		saHistoryEdit({
			id:_num(t.attr('val')),
			txt:p.find('.txt').val(),
			txt_client:p.find('.txt_client').val(),
			txt_zayav:p.find('.txt_zayav').val(),
			txt_schet:p.find('.txt_schet').val(),
			ids:p.find('.ids').val()

		});
	})
	.on('click', '#sa-history-cat .icon-edit', function() {
		var t = _parent($(this), 'DD'),
			html =
				'<table class="bs10">' +
					'<tr><td class="label r">Name:' +
						'<td><input type="text" id="name" class="w230" value="' + t.find('.name b').html() + '" />' +
					'<tr><td class="label r topi">About:' +
						'<td><textarea id="about" class="w230">' + t.find('.about').html() + '</textarea>' +
					'<tr><td class="label r topi">js_use:<td><input type="hidden" id="js_use"  value="' + (t.find('.js').html() ? 1 : 0) + '" />' +
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
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg('Изменено');
					$('#spisok').html(res.html);
					sortable();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	})

	.on('click', '#sa-rule .icon-edit', function() {
		var t = _parent($(this));
		saRuleEdit({
			id:_num(t.attr('val')),
			key:t.find('.key').html(),
			name:t.find('.name').html(),
			about:t.find('.about').html()
		});
	})
	.on('click', '#sa-rule .icon-del', function() {
		var p = _parent($(this));
		_dialogDel({
			id:p.attr('val'),
			head:'констатны права',
			op:'sa_rule_del',
			func:function() {
				p.remove();
			}
		});
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

	.on('click', '#sa-client-pole .img_edit', function() {
		var t = $(this),
			p = _parent(t);
		saClientPoleEdit({
			id:t.attr('val'),
			name:p.find('.name').html(),
			about:p.find('.about').html()
		});
	})
	.on('click', '#sa-client-pole .img_del', function() {
		var t = $(this),
			p = _parent(t);
		_dialogDel({
			id:t.attr('val'),
			head:'поля',
			op:'sa_client_pole_del',
			func:function() {
				p.remove();
			}
		});
	})
	.on('mouseover', '#sa-client-category .show', function() {
		$(this).removeClass('show');
	})
	.on('click', '#sa-client-category .edit', function() {
		var t = $(this);
		saClientCategoryEdit({
			id:t.attr('val'),
			name:$('.link.sel').html()
		});
	})
	.on('click', '#sa-client-category .img_edit', function() {
		var t = $(this),
			p = _parent(t);
		saClientCategoryPoleEdit({
			id:t.attr('val'),
			name:p.find('.e-name').val(),
			about:p.find('.about').html(),
			label:p.find('.e-label').val(),
			type_id:p.find('.type_id').val(),
			require:p.find('.require').val()
		});
	})
	.on('click', '#sa-client-category .img_del', function() {
		var t = $(this),
			p = _parent(t, 'DD');
		_dialogDel({
			id:t.attr('val'),
			head:'поля',
			op:'sa_client_category_pole_del',
			func:function() {
				p.remove();
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
			param1:p.find('.param1').html(),
			param2:p.find('.param2').html()
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
			param_v1:p.find('.param_v1').val(),
			param2:p.find('.param2').val(),
			param_v2:p.find('.param_v2').val()
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

	.on('click', '#sa-measure .img_edit', function() {
		var t = $(this),
			p = _parent(t, 'DD');
		saTovarMeasureEdit({
			id:t.attr('val'),
			short:p.find('.short').html(),
			name:p.find('.name').html(),
			about:p.find('.about').html(),
			fraction:p.find('.fraction').html() ? 1 : 0,
			area:p.find('.area').html() ? 1 : 0
		});
	})
	.on('click', '#sa-measure .img_del', function() {
		var t = $(this),
			p = _parent(t, 'DD');
		_dialogDel({
			id:t.attr('val'),
			head:'единицы измерения',
			op:'sa_tovar_measure_del',
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

	.on('click', '#sa-template #spisok-def .img_edit', function() {
		var t = $(this),
			p = _parent(t);
		saTemplateDefaultEdit({
			id:t.attr('val'),
			name:p.find('.name').html(),
			attach_id:p.find('.attach_id').val(),
			name_link:p.find('.name_link').html(),
			name_file:p.find('.name_file').html(),
			use:p.find('.use').html()
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
			app_name:p.find('.name').val(),
			secret:p.find('.secret').val()
		});
	})

	.on('click', '#sa-user .action', function() {
		var t = $(this),
			un = t;
		while(!un.hasClass('un'))
			un = un.parent();
		var send = {
			op:'sa_user_action',
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

	.ready(function() {
		if($('#sa-history').length)
			$('textarea')
				.autosize()
				.click(function() {
					$(this).select();
				});
		if($('#sa-history-cat').length) {
			$('.add').click(function() {
				var html =
						'<table class="bs10">' +
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

		if($('#sa-balans').length)
			$('#category-add').click(balansCategory);
	});
