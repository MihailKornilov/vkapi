var setupOrgEdit = function(org_id) {
		org_id = _num(org_id);
		var dialog = _dialog({
				top:20,
				width:650,
				head:(org_id ? 'Редактирование' : 'Добавление новой' ) + ' организации',
				load:1,
				butSubmit:org_id ? 'Сохранить' : 'Внести',
				submit:submit
			}),
			send = {
				op:'setup_org_load',
				org_id:org_id
			};

		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				loaded(res);
			else
				dialog.abort(res.text);
		}, 'json');


		function loaded(res) {
			dialog.content.html(res.html);
			$('#name' + org_id).focus();
			dialog.content.find('textarea').autosize();
		}
		function submit() {
			var send = {
				op:'setup_org_' + (org_id ? 'edit' : 'add'),
				org_id:org_id,

				name:       $('#name' + org_id).val(),
				name_yur:   $('#name_yur' + org_id).val(),
				phone:      $('#phone' + org_id).val(),
				fax:        $('#fax' + org_id).val(),
				adres_yur:  $('#adres_yur' + org_id).val(),
				adres_ofice:$('#adres_ofice' + org_id).val(),
				time_work:  $('#time_work' + org_id).val(),

				ogrn:   $('#ogrn' + org_id).val(),
				inn:    $('#inn' + org_id).val(),
				kpp:    $('#kpp' + org_id).val(),
				okud:   $('#okud' + org_id).val(),
				okpo:   $('#okpo' + org_id).val(),
				okved:  $('#okved' + org_id).val(),

				post_boss:      $('#post_boss' + org_id).val(),
				post_accountant:$('#post_accountant' + org_id).val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					location.reload();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	setupBankEdit = function(org_id, bank_id) {
		bank_id = _num(bank_id);
		var dialog = _dialog({
				width:560,
				head:(bank_id ? 'Редактирование данных' : 'Добавление нового' ) + ' банка',
				load:1,
				butSubmit:bank_id ? 'Сохранить' : 'Внести',
				submit:submit
			}),
			send = {
				op:'setup_bank_load',
				bank_id:bank_id
			};

		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				loaded(res);
			else
				dialog.abort(res.text);
		}, 'json');


		function loaded(res) {
			dialog.content.html(res.html);
			$('#bik').focus();
			$('#bik-load').click(bikLoad);
		}
		function bikLoad() {//получение данных банка по БИК с сайта bik-info.ru
			var t = $(this),
				send = {
					op:'setup_bik_load',
					bik:$.trim($('#bik').val())
				};

			if(!REGEXP_NUMERIC.test(send.bik)) {
				bikLoadErr('Некорректно заполнено поле БИК');
				return;
			}

			t.addClass('_busy');
			$.post(AJAX_MAIN, send, function(res) {
				t.removeClass('_busy');
				if(res.success) {
					$('#name').val(res.name);
					$('#account_corr').val(res.ks);
				} else
					bikLoadErr(res.text);
			}, 'json');
		}
		function bikLoadErr(msg) {//ошибка заполнения поля БИК
			$('#bik-load').vkHint({
				msg:'<div class="red">' + msg + '</div>',
				top:-60,
				left:-2,
				indent:60,
				show:1,
				remove:1
			});
			$('#bik').focus();
		}
		function submit() {
			var send = {
				op:'setup_bank_' + (bank_id ? 'edit' : 'add'),
				org_id:org_id,
				bank_id:bank_id,

				bik:$('#bik').val(),
				name:$('#name').val(),
				account_corr:$('#account_corr').val(),
				account:$('#account').val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
//					$('#bank-spisok').html(res.html);
					location.reload();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	setupBankDel = function(bank_id) {
		_dialogDel({
			id:bank_id,
			head:'данных о банке',
			op:'setup_bank_del',
			func:function() {
				location.reload();
			}
		});
	},
	setupNalogEdit = function(org_id) {
		var html = '<table class="bs10">' +
					'<tr><td class="label r">Система налогообложения:' +
						'<td><input type="hidden" id="nalog_system" value="' + $('#nalog_system' + org_id).val() + '" />' +
					'<tr><td class="label r">НДС:' +
						'<td><input type="hidden" id="nds" value="' + $('#nds' + org_id).val() + '" />' +
				'</table>',
			dialog = _dialog({
				width:470,
				head:'Настройка налогового учёта',
				content:html,
				butSubmit:'Сохранить',
				submit:submit
			});

		$('#nalog_system')._select({
			width:240,
			spisok:NALOG_SYSTEM
		});
		$('#nds')._select({
			width:120,
			spisok:NDS
		});

		function submit() {
			var send = {
				op:'setup_org_nalog_edit',
				org_id:org_id,
				nalog_system:$('#nalog_system').val(),
				nds:$('#nds').val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					location.reload();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},

	setupVkFind = function() {//редактирование данных сотрудника
		$(document)
			.off('keyup', '#setup-vk-find #viewer_link')
			.on('keyup', '#setup-vk-find #viewer_link', user_find);

		function user_find() {
			if($('#msg').hasClass('_busy'))
				return;

			$('#viewer_id').val(0);
			$('#vkuser').html('');

			var send = {
				user_ids:$.trim($('#viewer_link').val()),
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
							'<table class="bs10">' +
								'<tr><td><img src=' + u.photo_50 + '>' +
									'<td>' + u.first_name + ' ' + u.last_name +
							'</table>';
					$('#vkuser').html(html);
					$('#viewer_id').val(u.id);
				} else
					$('#msg span').show();
			});
		}

		return '<table id="setup-vk-find" class="w100p">' +
			'<tr><td colspan="2">' +
				'<div class="headName">Укажите адрес страницы пользователя или его id ВКонтакте:</div>' +
				'<div class="_info">Формат адреса может быть следующих видов:<br />' +
					'<u>http://vk.com/id12345</u>, <u>http://vk.com/durov</u>.<br />' +
					'Либо используйте ID пользователя: <u>id12345</u>, <u>durov</u>, <u>12345</u>.' +
				'</div>' +
			'<tr><td><input type="text" id="viewer_link" class="w230" />' +
					'<input type="hidden" id="viewer_id" />' +
				'<td id="msg"><span>Пользователь не найден</span>' +
			'<tr><td colspan="2" id="vkuser">' +
		'</table>';
	},
	setupWorkerAdd = function() {//добавление нового сотрудника
		var html =
			setupVkFind() +
			'<div id="manual" class="mt20"><a>Или заполните данные вручную..</a></div>' +
			'<table class="bs10 dn">' +
				'<tr><td class="label r w100">Имя:<td><input type="text" id="first_name" class="w230" />' +
				'<tr><td class="label r">Фамилия:<td><input type="text" id="last_name" class="w230" />' +
				'<tr><td class="label r">Пол:<td><input type="hidden" id="sex" />' +
				'<tr><td class="label r">Должность:<td><input type="text" id="post" class="w230" />' +
			'</table>',
			dialog = _dialog({
				width:440,
				head:'Добавление нового сотрудника',
				content:html,
				butSubmit:'Добавить сотрудника',
				submit:submit
			});

		$('#manual').click(function() {
			$(this)
				.hide()
				.next().show();
			$('#setup-vk-find').remove();
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

		function submit() {
			var send = {
				op:'setup_worker_add',
				viewer_id:_num($('#viewer_id').val()),
				first_name:$('#first_name').val(),
				last_name:$('#last_name').val(),
				sex:_num($('#sex').val()),
				post:$('#post').val()
			};
			if(!send.viewer_id && !send.first_name && !send.last_name) {
				dialog.err('Произведите поиск пользователя или укажите данные вручную');
				return;
			}
			if(!send.viewer_id && !send.sex) {
				dialog.err('Не указан пол');
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					location.href = URL + '&p=setup&d=worker&id=' + res.id;
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	setupWorkerEdit = function() {//редактирование данных сотрудника
		var html = '<table class="bs10">' +
				'<tr><td class="label r">Фамилия:' +
					'<td><input type="text" id="last_name" class="w300" value="' + U.last_name + '" />' +
				'<tr><td class="label r">Имя:' +
					'<td><input type="text" id="first_name" class="w300" value="' + U.first_name + '" />' +
				'<tr><td class="label r">Отчество:' +
					'<td><input type="text" id="middle_name" class="w300" value="' + U.middle_name + '" />' +
				'<tr><td class="label r">Должность:' +
					'<td><input type="text" id="post" class="w300" value="' + U.post + '" />' +
				'</table>',
			dialog = _dialog({
				width:440,
				head:'Редактирование данных сотрудника',
				content:html,
				butSubmit:'Сохранить',
				submit:submit
			});

		$('#last_name').focus();

		function submit() {
			var send = {
					op:'setup_worker_edit',
					viewer_id:RULE_VIEWER_ID,
					first_name:$('#first_name').val(),
					last_name:$('#last_name').val(),
					middle_name:$('#middle_name').val(),
					post:$('#post').val()
				};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					location.reload();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	setupWorkerVkBind = function() {//связывание с учётной записью ВКонтакте
		var html =
			'<div class="_info">' +
				'После привязки сотрудника к его странице ВКонтакте, он получит доступ к приложению.' +
				'<br />' +
				'<p>Пожалуйста, внимательно отнеситесь к выбору учётной записи ВКонтакте. ' +
				'После применения привязки отменить данную операцию будет невозможно.' +
				'' +
				'' +
			'</div>' +

			setupVkFind() +

			'<table class="bs10">' +
			'</table>',
			dialog = _dialog({
				top:30,
				width:440,
				head:'Привязка сотрудника к учётной записи ВКонтакте',
				content:html,
				butSubmit:'Привязать',
				submit:submit
			});

		function submit() {
			var send = {
					op:'setup_worker_bind',
					worker_id:RULE_VIEWER_ID,
					viewer_id:_num($('#viewer_id').val())
				};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					location.href = URL + '&p=setup&d=worker&id=' + send.viewer_id;
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	setupRuleCheck = function(v, id) {
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

	setupExpenseEdit = function(o) {
		o = $.extend({
			id:0,
			name:'',
			about:''
		}, o);

		var t = $(this),
			html = '<table class="bs10">' +
					'<tr><td class="label r">Наименование*:' +
						'<td><input type="text" id="name" class="w300" value="' + o.name + '" />' +
					'<tr><td class="label r topi">Описание:' +
						'<td><textarea id="about" class="w300">' + _br(o.about) + '</textarea>' +
					'<tr' + (o.id ? '' : ' class="dn"') + '>' +
						'<td class="label r">Объединить:' +
						'<td><input type="hidden" id="join" />' +
					'<tr class="tr-join dn">' +
						'<td><td>' +
							'<div class="_info">' +
								'При объединении категорий расходов выбранная категория станет общей с той, которая редактируется в данный момент. ' +
								'Все записи перейдут в новую категорию, старая будет удалена.' +
								'<br /><br />' +
								'<b>Выберите категорию для объединения:</b>' +
							'</div>' +
					'<tr class="tr-join dn">' +
						'<td class="label topi">С категорией:' +
						'<td><input type="hidden" id="category_id-join" />' +
							'<input type="hidden" id="category_sub_id-join" />' +
				'</table>',
			dialog = _dialog({
				width:470,
				head:(o.id ? 'Редактирование' : 'Добавление новой' ) + ' категории расхода организации',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			}),
			catSpisok = _copySel(EXPENSE_SPISOK, o.id);

		$('#name').focus();
		$('#about').autosize();
		$('#join')._check({
			func:function(v) {
				$('.tr-join')[(v ? 'remove' : 'add') + 'Class']('dn');
				if(v)
					$('#category_id-join')._select({
						width:218,
						bottom:5,
						title0:' категория не выбрана',
						spisok:_copySel(catSpisok, 1),
						func:function(v) {
							_expenseSub(v, 0, '-join', 218);
						}
					});
			}
		});

		function submit() {
			var send = {
				op:'expense_category_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val(),
				about:$('#about').val(),
				category_id:_num($('#category_id-join').val()),
				category_sub_id:_num($('#category_sub_id-join').val())
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#spisok').html(res.html);
					dialog.close();
					_msg();
					sortable();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},
	setupExpenseSubEdit = function(o) {
		o = $.extend({
			id:0,
			name:''
		}, o);

		var t = $(this),
			html = '<table class="bs10">' +
					'<tr><td class="label r">Категория:<td><b>' + CAT_NAME + '</b>' +
					'<tr><td class="label r">Подкатегория:<td><input type="text" id="name" class="w250" value="' + o.name + '" />' +
					'<tr' + (o.id ? '' : ' class="dn"') + '>' +
						'<td class="label r">Объединить:' +
						'<td><input type="hidden" id="join" />' +
					'<tr class="tr-join dn">' +
						'<td><td>' +
							'<div class="_info">' +
								'При объединении категорий расходов выбранная категория станет общей с той, которая редактируется в данный момент. ' +
								'Все записи перейдут в новую категорию, старая будет удалена.' +
								'<br /><br />' +
								'<b>Выберите категорию для объединения:</b>' +
							'</div>' +
					'<tr class="tr-join dn">' +
						'<td class="label topi">С категорией:' +
						'<td><input type="hidden" id="category_id-join" />' +
							'<input type="hidden" id="category_sub_id-join" />' +
				'</table>',
			dialog = _dialog({
				width:430,
				head:(o.id ? 'Редактирование' : 'Добавление новой' ) + ' подкатегории расхода организации',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();
		$('#join')._check({
			func:function(v) {
				$('.tr-join')[(v ? 'remove' : 'add') + 'Class']('dn');
				if(v)
					$('#category_id-join')._select({
						width:218,
						bottom:5,
						title0:' категория не выбрана',
						spisok:_copySel(EXPENSE_SPISOK, 1),
						func:function(v) {
							_expenseSub(v, 0, '-join', 218);
						}
					});
			}
		});

		function submit() {
			var send = {
				op:'expense_category_sub_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				category_id:CAT_ID,
				name:$('#name').val(),
				category_id_join:_num($('#category_id-join').val()),
				category_sub_id_join:_num($('#category_sub_id-join').val())
			};
			if(!send.name) {
				dialog.err('Не указано наименование');
				$('#name').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#spisok').html(res.html);
					dialog.close();
					_msg();
				} else
					dialog.abort();
			}, 'json');
		}
	},

	setupRubricEdit = function(o) {
		o = $.extend({
			id:0,
			name:''
		}, o);

		var t = $(this),
			html = '<table class="bs10">' +
					'<tr><td class="label">Наименование:' +
						'<td><input id="name" type="text" class="w250" value="' + o.name + '" />' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:(o.id ? 'Редактирование' : 'Добавление новой' ) + ' рубрики объявлений',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();

		function submit() {
			var send = {
				op:'setup_rubric_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val()
			};
			if(!send.name) {
				dialog.err('Не указано наименование');
				$('#name').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#spisok').html(res.html);
					dialog.close();
					_msg();
					sortable();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	setupRubricSubEdit = function(o) {
		o = $.extend({
			id:0,
			name:''
		}, o);

		var t = $(this),
			html = '<table class="bs10">' +
					'<tr><td class="label r">Рубрика:<td><b>' + RUBRIC_ASS[RUBRIC_ID] + '</b>' +
					'<tr><td class="label r">Подрубрика:' +
						'<td><input id="name" type="text" class="w250" value="' + o.name + '" />' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:(o.id ? 'Редактирование' : 'Добавление новой' ) + ' подрубрики',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();

		function submit() {
			var send = {
				op:'setup_rubric_sub_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				rubric_id:RUBRIC_ID,
				name:$('#name').val()
			};
			if(!send.name) {
				dialog.err('Не указана подрубрика');
				$('#name').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#spisok').html(res.html);
					dialog.close();
					_msg();
					sortable();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	setupPolosaCostEdit = function(o) {
		o = $.extend({
			id:0,
			name:'',
			cena:'',
			polosa:0
		}, o);

		var html = '<table class="bs10">' +
				'<tr><td class="label r w175">Наименование:<td><input type="text" id="name" class="w250" value="' + o.name + '" />' +
				'<tr><td class="label r">Цена за см&sup2;:<td><input type="text" id="cena" class="money" value="' + o.cena + '" /> руб.' +
				'<tr><td class="label r">Указывать номер полосы:<td><input type="hidden" id="polosa" value="' + o.polosa + '" />' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:500,
				head:o.id ? 'Редактирование данных полосы' : 'Внесение новой полосы',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();
		$('#polosa')._check();

		function submit() {
			var send = {
				op:'setup_polosa_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val(),
				cena:_cena($('#cena').val()),
				polosa:$('#polosa').val()
			};
			if(!send.name) {
				dialog.err('Не указано наименование');
				$('#name').focus();
				return;
			}
			if(!send.cena) {
				dialog.err('Некорректно указана цена');
				$('#cena').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#spisok').html(res.html);
					dialog.close();
					_msg();
					sortable();
				} else
					dialog.abort();
			}, 'json');
		}
	},
	setupObLenEdit = function() {
		var but = $('#setup_oblen .vk'),
			send = {
				op:'setup_oblen_edit',
				txt_len_first:_num($('#txt_len_first').val()),
				txt_cena_first:_num($('#txt_cena_first').val()),
				txt_len_next:_num($('#txt_len_next').val()),
				txt_cena_next:_num($('#txt_cena_next').val())
			};

		if(!send.txt_len_first) {
			err(-2, 98);
			$('#txt_len_first').focus();
			return;
		}
		if(!send.txt_cena_first) {
			err(-2, 191);
			$('#txt_cena_first').focus();
			return;
		} else if(!send.txt_len_next) {
			err(25, 98);
			$('#txt_len_next').focus();
			return;
		} else if(!send.txt_cena_next) {
			err(25, 191);
			$('#txt_cena_next').focus();
			return;
		}
		but.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			but.removeClass('_busy');
			if(res.success)
				_msg('Сохранено!');
		}, 'json');
		function err(top, left) {
			$('#setup_oblen').vkHint({
				msg:'<SPAN class="red">Некорректный ввод</SPAN>',
				top:top,
				left:left,
				indent:50,
				show:1,
				remove:1
			});
		}
	},
	setupGnEdit = function(o) {
		o = $.extend({
			id:0,
			week:'',
			general:'',
			print:'',
			public:'',
			pc:8
		}, o);
		var html = '<table class="setup-gn-tab bs10">' +
				'<tr><td class="label r">Номер выпуска:' +
					'<td><input type="text" id="week_nomer" maxlength="2" value="' + o.week + '" />' +
						'<input type="text" id="general_nomer" maxlength="4" value="' + o.general + '" />' +
				'<tr><td class="label r">День отправки в печать:<td><input type="hidden" id="day_print" value="' + o.print + '" />' +
				'<tr><td class="label r">День выхода:<td><input type="hidden" id="day_public" value="' + o.public + '" />' +
				'<tr><td class="label r">Количество полос:<td><input type="hidden" id="polosa_count" value="' + o.pc + '" />' +
				'</table>',
			dialog = _dialog({
				width:320,
				head:(o.id ? 'Редактирование' : 'Добавление') + ' номера газеты',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});
		$('#week_nomer').focus();
		$('#week_nomer,#general_nomer').keyEnter(submit);
		$('#day_print')._calendar({lost:1});
		$('#day_public')._calendar({lost:1});
		$('#polosa_count')._select({
			width:50,
			spisok:[{uid:4,title:"4"},{uid:6,title:"6"},{uid:8,title:"8"},{uid:10,title:"10"},{uid:12,title:"12"}]
		});
		function submit() {
			var send = {
				op:'setup_gn_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				week_nomer:_num($('#week_nomer').val()),
				general_nomer:_num($('#general_nomer').val()),
				day_print:$('#day_print').val(),
				day_public:$('#day_public').val(),
				polosa_count:$('#polosa_count').val(),
				year:$('#dopLinks .sel').html()
			};
			if(!send.week_nomer) {
				dialog.err('Некорректно указан номер недели выпуска');
				$('#week_nomer').focus();
				return;
			}
			if(!send.general_nomer) {
				dialog.err('Некорректно указан общий номер выпуска');
				$('#general_nomer').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#dopLinks').html(res.year);
					$('#spisok').html(res.html);
					dialog.close();
					_msg();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},

	setupZayavExpense = function(o) {
		o = $.extend({
			id:0,
			name:'',
			dop:0,
			param:0
		}, o);

		var html =
				'<table class="bs10">' +
					'<tr><td class="label r">Наименование:' +
						'<td><input type="text" id="name" value="' + o.name + '" />' +
					'<tr><td class="label r topi">Дополнительное поле:' +
						'<td><input type="hidden" id="dop" value="' + o.dop + '" />' +
					'<tr class="tr-param' + (o.dop == 4 ? '' : ' dn') + '">' +
						'<td>' +
						'<td><input id="param" type="hidden" value="' + o.param + '" />' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:(o.id ? 'Редактирование' : 'Добавление новой' ) + ' категории расхода заявки',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();
		$('#dop')._radio({
			light:1,
			spisok:ZAYAV_EXPENSE_DOP,
			func:function(v) {
				$('.tr-param')[(v == 4 ? 'remove' : 'add') + 'Class']('dn');
				$('#param')._check(0);
			}
		});
		$('#param')._check({
			name:'ведение прикреплённых счетов'
		});

		function submit() {
			var send = {
				op:'setup_zayav_expense_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val(),
				dop:$('#dop').val(),
				param:$('#param').val()
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
						_msg();
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
			day_fact:0,
			accrual:0,
			remind:0,
			hide:0
		}, o);

		var html =
				'<table class="setup-status-tab bs10">' +
					'<tr><td class="label r w175">Название:<td><input type="text" id="name" class="w250" value="' + o.name + '" />' +
					'<tr><td class="label r topi">Описание:<td><textarea id="about" class="w250">' + o.about + '</textarea>' +
					'<tr><td class="label r topi">Цвет:<td><div id="color" val="' + o.color + '" style="background-color:#' + o.color + '"></div>' +
					'<tr><td class="label r">По умолчанию:<td><input type="hidden" id="default" value="' + o.default + '" />' +
					'<tr class="tr-nouse' + (o.default ? '' : ' dn') + '">' +
						'<td class="label r">Не использовать повторно:' +
						'<td><input type="hidden" id="nouse" value="' + o.nouse + '" />' +
					'<tr><td class="label r topi">Следующие статусы:<td><input type="hidden" id="next" value="' + o.next + '" />' +
					'<tr class="tr-next-ids' + (o.next ? '' : ' dn') + '"><td class="label topi"><td><input type="hidden" id="next_ids" value="' + o.next_ids + '" />' +
					'<tr><td><td><input type="hidden" id="hide" value="' + o.hide + '" />' +
					'<tr><td><td>' +
					'<tr><td><td><b>Действия при выборе статуса</b>' +
					'<tr><td><td><input type="hidden" id="executer" value="' + o.executer + '" />' +
					'<tr><td><td><input type="hidden" id="srok" value="' + o.srok + '" />' +
					'<tr><td class="label">' +
						'<td><input type="hidden" id="day_fact" value="' + o.day_fact + '" />' +
					'<tr><td><td><input type="hidden" id="accrual" value="' + o.accrual + '" />' +
					'<tr><td><td><input type="hidden" id="remind" value="' + o.remind + '" />' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:520,
				head:(o.id ? 'Редактирование' : 'Добавление нового' ) + ' статуса заявки',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();
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

		$('#hide')._check({
			name:'скрывать заявку из общего списка',
			light:1
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
			name:'предлагать вносить начисление',
			light:1
		});
		$('#remind')._check({
			name:'предлагать добавлять напоминание',
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
				day_fact:$('#day_fact').val(),
				accrual:$('#accrual').val(),
				remind:$('#remind').val(),
				hide:$('#hide').val()
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
	},

	setupSalaryListSave = function() {//сохранение настройки листа выдачи
		var t = $(this);
		if(t.hasClass('_busy'))
			return;

		var send = {
			op:'setup_salary_list',
			ids:_checkAll()
		};
		t.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			t.removeClass('_busy');
			if(res.success)
				_msg();
		}, 'json');
	},
	
	setupTovarCategoryEdit = function(o) {
		o = $.extend({
			id:0,
			name:''
		}, o);

		var t = $(this),
			html = '<table class="bs10">' +
					'<tr><td class="label r">Наименование:<td><input id="name" type="text" value="' + o.name + '" />' +
				'</table>',
			dialog = _dialog({
				head:(o.id ? 'Редактирование' : 'Создание новой' ) + ' категории товаров',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();

		function submit() {
			var send = {
				op:'setup_tovar_category_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val()
			};
			if(!send.name) {
				dialog.err('Не указано наименование');
				$('#name').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#spisok').html(res.html);
					sortable();
					dialog.close();
					_msg();
				} else
					dialog.abort();
			}, 'json');
		}
	},

	setupTemplateEdit = function(o) {//создание/редактирование шаблона документов
		o = $.extend({
			id:0,
			name:''
		}, o);

		var t = $(this),
			html = '<table class="bs10">' +
					'<tr><td class="label r">Название:' +
						'<td><input type="text" id="name" class="w230" value="' + o.name + '" />' +
				'</table>',
			dialog = _dialog({
				head:(o.id ? 'Редактирование' : 'Создание нового' ) + ' шаблона',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();

		function submit() {
			var send = {
				op:'setup_template_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					location.href = URL + '&p=setup&d=document_template&id=' + res.id;
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	};

$(document)
	.on('click', '.history-view-worker-all', function() {//изменение прав истории действий сразу для всех сотрудников
		var spisok = '';
		for(var n = 0; n < WORKER_SPISOK.length; n++) {
			var sp = WORKER_SPISOK[n];
			if(sp.uid >= VIEWER_MAX)
				continue;
			spisok += '<tr><td class="label w150">' + sp.title + ':' +
						  '<td><input type="hidden" id="hv' + sp.uid + '" value="' + RULE_HISTORY_ALL[sp.uid] + '" />';
		}
		var html =
				'<center><b>Видимость истории действий:</b></center>' +
				'<table class="bs10">' + spisok + '</table>',
			dialog = _dialog({
				head:'Права истории действий',
				content:html,
				butSubmit:'Сохранить',
				submit:submit
			}),
			inp = dialog.content.find('input');

		for(n = 0; n < inp.length; n++)
			inp.eq(n)._dropdown({
				spisok:[
					{uid:0,title:'нет'},
					{uid:1,title:'только свою'},
					{uid:2,title:'всю историю'}
				]
			});

		function submit() {
			var v = [];
			for(n = 0; n < inp.length; n++) {
				var eq = inp.eq(n),
					id = eq.attr('id').split('hv')[1];
				v.push(id + ':' + eq.val());
			}

			var send = {
				op:'setup_history_view_worker_all',
				v:v.join()
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

	.on('click', '#setup_expense .img_edit', function() {
		var t = _parent($(this), 'DD');
		setupExpenseEdit({
			id:t.attr('val'),
			name:t.find('.name').html().replace(/\"/g, '&quot;'),
			about:t.find('.about').html().replace(/\"/g, '&quot;')
		});

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

	.on('click', '#setup_expense_sub .img_edit', function() {
		var t = _parent($(this));
		setupExpenseSubEdit({
			id:t.attr('val'),
			name:t.find('.name').html().replace(/\"/g, '&quot;')
		});

	})
	.on('click', '#setup_expense_sub .img_del', function() {
		_dialogDel({
			id:_parent($(this)).attr('val'),
			head:'подкатегории расхода организации',
			op:'expense_category_sub_del',
			func:function(res) {
				$('#spisok').html(res.html);
			}
		});
	})

	.on('click', '#setup_rubric .img_edit', function() {
		var t = _parent($(this), 'DD');
		setupRubricEdit({
			id:t.attr('val'),
			name:t.find('.name a').html().replace(/\"/g, '&quot;')
		});

	})
	.on('click', '#setup_rubric .img_del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'рубрики объявлений',
			op:'setup_rubric_del',
			func:function(res) {
				$('#spisok').html(res.html);
				sortable();
			}
		});
	})

	.on('click', '#setup_rubric_sub .img_edit', function() {
		var t = _parent($(this), 'DD');
		setupRubricSubEdit({
			id:t.attr('val'),
			name:t.find('.name').html().replace(/\"/g, '&quot;')
		});
	})
	.on('click', '#setup_rubric_sub .img_del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'подрубрики',
			op:'setup_rubric_sub_del',
			func:function(res) {
				$('#spisok').html(res.html);
				sortable();
			}
		});
	})

	.on('click', '#setup_polosa .img_edit', function() {
		var t = _parent($(this), 'DD');
		setupPolosaCostEdit({
			id:t.attr('val'),
			name:t.find('.name').html().replace(/\"/g, '&quot;'),
			cena:t.find('.cena').html(),
			polosa:t.find('.pn').html() ? 1 : 0

		});
	})

	.on('click', '#setup_obdop .img_edit', function() {
		var t = _parent($(this)),
			id = t.attr('val'),
			name = t.find('.name').html(),
			cena = t.find('.cena').html(),
			html = '<table class="bs10">' +
				'<tr><td class="label">Наименование:<td><b>' + name + '</b>' +
				'<tr><td class="label">Стоимость:<td><input type="text" id="cena" class="money" value="' + cena + '" /> руб.' +
				'</table>',
			dialog = _dialog({
				head:'Редактирование параметра',
				content:html,
				butSubmit:'Сохранить',
				submit:submit
			});

		function submit() {
			var send = {
				op:'setup_obdop_edit',
				id:id,
				cena:_num($('#cena').val())
			};
			if(!send.cena) {
				dialog.err('Некорректно указана цена');
				$('#cena').focus();
				return;
			}
			
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#spisok').html(res.html);
					dialog.close();
					_msg();
				} else
					dialog.abort();
			}, 'json');
		}
	})

	.on('click', '#setup_gn .link', function() {//список номеров за выбранный год
		var t = $(this),
			send = {
				op:'setup_gn_spisok_get',
				year:t.html()
			};
		t.parent().find('.sel').removeClass('sel');
		t.addClass('sel');
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				$('#spisok').html(res.html);
		}, 'json');
	})
	.on('click', '#setup_gn .vk', function() {
		var t = $(this),
			year = $('#dopLinks .sel').html(),
			html = '<table class="setup-gn-tab bs10">' +
				'<tr><td colspan="2">' +
					'<div class="gn-info">' +
						'Для создания списка номеров газет <b>' + year + '</b> года ' +
						'укажите данные <b>первого номера</b>, который будет выходить в этом году.<br />' +
						'Все поля обязательны для заполнения.' +
					'</div>' +
				'<tr><td class="label r">Первый номер выпуска:' +
					'<td><input type="text" id="week_nomer" maxlength="2" value="1" />' +
						'<input type="text" id="general_nomer" maxlength="4" value="' + GN_MAX + '" />' +
				'<tr><td class="label r">Дни отправки в печать:<td><input type="hidden" id="day_print" value="1" />' +
				'<tr><td class="label r">Дни выхода:<td><input type="hidden" id="day_public" value="4" />' +
				'<tr><td class="label r">Количество полос:<td><input type="hidden" id="polosa_count" value="8" />' +
				'<tr><td class="label r">Первый день выхода:<td><input type="hidden" id="day_first" value="' + year + '-01-01" />' +
				'</table>',
			dialog = _dialog({
				width:320,
				head:'Создание списка номеров газеты',
				content:html,
				butSubmit:'Создать',
				submit:submit
			}),
			weeks = [
				{uid:0,title:'Понедельник'},
				{uid:1,title:'Вторник'},
				{uid:2,title:'Среда'},
				{uid:3,title:'Четверг'},
				{uid:4,title:'Пятница'},
				{uid:5,title:'Суббота'},
				{uid:6,title:'Воскресенье'}
			];
		$('#week_nomer').focus();
		$('#week_nomer,#general_nomer').keyEnter(submit);
		$('#day_print')._select({width:100, spisok:weeks});
		$('#day_public')._select({width:100, spisok:weeks});
		$('#polosa_count')._select({
			width:50,
			spisok:[{uid:4,title:"4"},{uid:6,title:"6"},{uid:8,title:"8"},{uid:10,title:"10"},{uid:12,title:"12"}]
		});
		$('#day_first')._calendar({lost:1});
		function submit() {
			var send = {
				op:'setup_gn_spisok_create',
				year:year,
				week_nomer:_num($('#week_nomer').val()),
				general_nomer:_num($('#general_nomer').val()),
				day_print:$('#day_print').val(),
				day_public:$('#day_public').val(),
				polosa_count:$('#polosa_count').val(),
				day_first:$('#day_first').val()
			};
			if(!send.week_nomer) {
				dialog.err('Некорректно указан номер недели выпуска');
				$('#week_nomer').focus();
				return;
			}
			if(!send.general_nomer) {
				dialog.err('Некорректно указан общий номер выпуска');
				$('#general_nomer').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#dopLinks').html(res.year);
					$('#spisok').html(res.html);
					dialog.close();
					_msg();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	})
	.on('click', '#setup_gn #gn-clear', function() {
		var t = $(this),
			year = t.attr('val'),
			dialog = _dialog({
				top:90,
				width:300,
				head:'Очищение списка номеров газеты',
				content:'<center>Подтвердите удаление списка номеров газеты<br />за ' + year + ' год.</center>',
				butSubmit:'Очистить',
				submit:submit
			});
		function submit() {
			var send = {
				op:'setup_gn_clear',
				year:year
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#dopLinks').html(res.year);
					$('#spisok').html(res.html);
					dialog.close();
					_msg();
				} else
					dialog.abort();
			}, 'json');
		}
	})
	.on('click', '#setup_gn .img_edit', function() {
		var t = $(this),
			p = _parent(t);
		setupGnEdit({
			id:t.attr('val'),
			week:p.find('.nomer b').html(),
			general:p.find('.nomer span').html(),
			print:p.find('.print s').html(),
			public:p.find('.pub s').html(),
			pc:p.find('.pc').html()
		});
	})
	.on('click', '#setup_gn .img_del', function() {
		var t = $(this);
		_dialogDel({
			id:t.attr('val'),
			head:'номера газеты',
			op:'setup_gn_del',
			func:function() {
				_parent(t).remove();
			}
		});
	})

	.on('click', '#setup_zayav_expense .img_edit', function() {
		var t = _parent($(this), 'DD');
		setupZayavExpense({
			id:t.attr('val'),
			name:t.find('.name').html(),
			dop:_num(t.find('.hdop').val()),
			param:_num(t.find('.param').val())
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
			hide:t.find('.hide').val(),
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

	.on('click', '#setup-cartridge .img_edit', function() {
		var t = $(this),
			id = t.attr('val'),
			p = _parent(t),
			name = p.find('.name').html(),
			type_id = p.find('.type_id').val(),
			filling = p.find('.filling').val(),
			restore = p.find('.restore').val(),
			chip = p.find('.chip').val(),
			html = '<table class="bs10">' +
				'<tr><td class="label r">Вид:<td><input type="hidden" id="type_id" value="' + type_id + '" />' +
				'<tr><td class="label r"><b>Модель картриджа:</b><td><input type="text" id="name" class="w200" value="' + name + '" />' +
				'<tr><td class="label r">Заправка:<td><input type="text" id="cost_filling" class="money" maxlength="11" value="' + filling + '" /> руб.' +
				'<tr><td class="label r">Восстановление:<td><input type="text" id="cost_restore" class="money" maxlength="11" value="' + restore + '" /> руб.' +
				'<tr><td class="label r">Замена чипа:<td><input type="text" id="cost_chip" class="money" maxlength="11" value="' + chip + '" /> руб.' +
				'<tr><td><td>' +
				'<tr><td class="label r">Объединить:<td><input type="hidden" id="join" />' +
				'<tr class="tr-join dn"><td class="label r">С картриджем:<td><input type="hidden" id="join_id" />' +
				'</table>',
			dialog = _dialog({
				top:40,
				width:410,
				head:'Редактирование данных картриджа',
				content:html,
				butSubmit:'Сохранить',
				submit:submit
			});
		$('#type_id')._select({
			spisok:CARTRIDGE_TYPE
		});
		$('#name').focus();
		$('#join')._check({
			func:function(v) {
				$('.tr-join')[(v ? 'remove' : 'add') + 'Class']('dn');
			}
		});
		var spisok = [];
		for(var n = 0; n < CARTRIDGE_SPISOK.length; n++) {
			var sp = CARTRIDGE_SPISOK[n];
			if(sp.uid == id)
				continue;
			spisok.push(sp);
		}
		$('#join_id')._select({
			width:218,
			write:1,
			title0:'Не выбрано',
			spisok:spisok
		});
		function submit() {
			var join = _num($('#join').val()),
				send = {
					op:'cartridge_edit',
					id:id,
					type_id:$('#type_id').val(),
					name:$('#name').val(),
					cost_filling:_num($('#cost_filling').val()),
					cost_restore:_num($('#cost_restore').val()),
					cost_chip:_num($('#cost_chip').val()),
					join_id:join ? _num($('#join_id').val()) : 0
				};
			if(!send.name) {
				dialog.err('Не указано наименование');
				$('#name').focus();
				return;
			}
			if(join && !send.join_id) {
				dialog.err('Не выбран картридж для объединения');
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#spisok').html(res.html);
					CARTRIDGE_SPISOK = res.cart;
					dialog.close();
					_msg();
				} else
					dialog.abort();
			}, 'json');
		}
	})
	.on('mouseleave', '#setup-cartridge .edited', function() {//удаление подсветки отредактированного картриджа
		$(this).css('background-color', '#fff');
	})

	.on('click', '#setup_tovar_category .img_edit', function() {
		var t = _parent($(this));
		setupTovarCategoryEdit({
			id:t.attr('val'),
			name:t.find('.name').html()
		});
	})
	.on('click', '#setup_tovar_category .img_del', function() {
		var p = _parent($(this));
		_dialogDel({
			id:p.attr('val'),
			head:'категории товаров',
			op:'setup_tovar_category_del',
			func:function() {
				p.remove();
			}
		});
	})
	.on('click', '#setup_tovar_category #join', function() {//подключение категории из готовых каталогов
		var dialog = _dialog({
			top:30,
			width:420,
			head:'Подключение категорий товаров из готовых каталогов',
			load:1,
			butSubmit:'Готово',
			submit:submit
		});


		$.post(AJAX_MAIN, {op:'setup_tovar_category_join_load'}, function(res) {
			if(res.success)
				dialog.content.html(res.html);
			else
				dialog.loadError();
		}, 'json');


		function submit() {
			var send = {
				op:'setup_tovar_category_join_save',
				ids:_checkAll()
			};
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#spisok').html(res.html);
					sortable();
					dialog.close();
					_msg();
				} else
					dialog.loadError();
			}, 'json');
		}
	})

	.ready(function() {
		if($('#setup_my').length) {
			$('#pinset').click(function() {
				var html =
						'<table class="bs10">' +
							'<tr><td class="label">Новый пин-код:<td><input id="pin" type="password" />' +
						'</table>',
					dialog = _dialog({
						width:350,
						head:'Установка нового пин-кода',
						content:html,
						butSubmit:'Установить',
						submit:submit
					});
				$('#pin').focus();
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
				var html = '<table class="bs10">' +
						'<tr><td class="label r">Текущий пин-код:<td><input id="oldpin" type="password" maxlength="10" />' +
						'<tr><td class="label r">Новый пин-код:<td><input id="pin" type="password" maxlength="10" />' +
						'</table>',
					dialog = _dialog({
						width:350,
						head:'Изменение пин-кода',
						content:html,
						butSubmit:'Изменить',
						submit:submit
					});
				$('#oldpin').focus();
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
						'<table class="bs10">' +
							'<tr><td class="label">Текущий пин-код:<td><input id="oldpin" type="password" />' +
						'</table>',
					dialog = _dialog({
						width:350,
						head:'Удаление пин-кода',
						content:html,
						butSubmit:'Применить',
						submit:submit
					});
				$('#oldpin').focus();
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
								_msg();
								location.reload();
							} else
								dialog.abort(res.text);
						}, 'json');
					}
				}
			});
			$('#RULE_MY_PAY_SHOW_PERIOD')._select({
				spisok:[
					{uid:1,title:'за текущий день'},
					{uid:2,title:'за текущую неделю'},
					{uid:3,title:'за текущий месяц'}
				],
				func:setupRuleCheck
			});
		}
		if($('#setup_org').length) {
			$('#org-menu')._menuDop({
				spisok:ORG_MENU
			});
		}
		if($('#setup_rule').length) {
			$('.icon-del').click(function() {
				_dialogDel({
					id:RULE_VIEWER_ID,
					head:'сотрудника',
					op:'setup_worker_del',
					func:function() {
						location.href = URL + '&p=setup&d=worker';
					}
				});
			});
			$('#RULE_SALARY_SHOW')._check(setupRuleCheck);
			$('#RULE_EXECUTER')._check(setupRuleCheck);
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


			$('#td-rule-menu ._check,#td-rule-setup ._check').click(function() {
				var ch = $(this).find('input'),
					send = {
						op:'setup_menu_access',
						viewer_id:window.RULE_VIEWER_ID || VIEWER_ID,
						menu_id:_num(ch.attr('id').split('RULE_MENU_')[1]),
						v:_bool(ch.val()) ? 0 : 1
					};
				if(!send.menu_id)
					return;
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success)
						_msg('Сохранено');
				}, 'json');
			});
			//показ-скрытие прав в заявках
			$('#RULE_MENU_2')._check(function(v) {
				$('#tr-rule-zayav')[(v ? 'remove' : 'add') + 'Class']('dn');
			});
			$('#RULE_ZAYAV_EXECUTER')._check(setupRuleCheck);

			//показ-скрытие прав с подразделами настроек
			$('#RULE_MENU_5')._check(function(v) {
				$('#tr-rule-setup')[(v ? 'remove' : 'add') + 'Class']('dn');
			});

			$('#RULE_MENU_15')._check(function(v, id) {
				$('#div-worker-rule')[v ? 'show' : 'hide']();
				setupRuleCheck(v, id);
				$('#RULE_SETUP_RULES')._check(0);
			});
			$('#RULE_SETUP_RULES')._check(setupRuleCheck);
			$('#RULE_SETUP_INVOICE')._check(setupRuleCheck);
			$('#RULE_HISTORY_VIEW')._dropdown({
				spisok:RULE_HISTORY_SPISOK,
				func:setupRuleCheck
			});
			$('#RULE_WORKER_SALARY_VIEW')._dropdown({
				spisok:[{uid:0,title:"нет"},{uid:1,title:"только свою"},{uid:2,title:"всех сотрудников"}],
				func:setupRuleCheck
			});
			$('#RULE_INVOICE_HISTORY')._check(setupRuleCheck);
			$('#RULE_INVOICE_TRANSFER')._dropdown({
				spisok:RULE_INVOICE_TRANSFER_SPISOK,
				func:setupRuleCheck
			});
			$('#RULE_INCOME_VIEW')._check(setupRuleCheck);
			$('#pin-clear').click(function() {
				var send = {
						op:'setup_worker_pin_clear',
						viewer_id:RULE_VIEWER_ID
					},
					but = $(this);
				if(but.hasClass('_busy'))
					return;
				but.addClass('_busy');
				$.post(AJAX_MAIN, send, function(res) {
					but.removeClass('_busy');
					if(res.success) {
						_msg('Пин-код сброшен');
						but.prev().remove();
						but.remove();
					}
				}, 'json');
			});
		}
		if($('#setup_document_template_info').length) {
			$('#attach_id')._attach({
				type:'button',
				title:'загрузить шаблон',
				format:'xls,xlsx,docx',
				table_name:'_template',
				table_row:window.TEMPLATE_ID
			});
			$('.save').click(function() {
				var t = $(this),
					send = {
						op:'setup_template_save',
						id:TEMPLATE_ID,
						name:$('#name').val(),
						name_link:$('#name_link').val(),
						name_file:$('#name_file').val()
					};
				t.addClass('_busy');
				$.post(AJAX_MAIN, send, function(res) {
					t.removeClass('_busy');
					if(res.success)
						_msg('Информация сохранена.');
					else
						t.vkHint({
							msg:'<span class="red">' + res.text + '</span>',
							show:1,
							indent:40,
							top:-58,
							left:-6,
							remove:1
						});
				}, 'json');
			});
		}
	});
