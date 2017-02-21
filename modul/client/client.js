var 
/*	
	clientPeopleTab = function(v, p) {// таблица: частное лицо
		// отображать ли паспортные данные
		var pasp = v.pasp_seria || v.pasp_nomer || v.pasp_adres || v.pasp_ovd || v.pasp_data ? '' : ' class="dn"',
			prefix = p ? 'person-' : '',
			worker = !p && v.id;//показывать пункт: связан с сотрудником
		return '' +
		'<table class="ca-table' + (p || v.category_id == 1 ? '' : ' dn') + '" id="people">' +
			'<tr><td class="label"><b>Ф.И.О.:</b><td><input type="text" id="' + prefix + 'fio" value="' + v.fio + '" />' +
			'<tr><td class="label">Телефон:      <td><input type="text" id="' + prefix + 'phone" value="' + v.phone + '" />' +
			'<tr><td class="label topi">Адрес:   <td><textarea id="' + prefix + 'adres">' + _br(v.adres) + '</textarea>' +
  (worker ? '<tr><td class="label">Связан с сотрудником:<td><input type="hidden" id="worker_id" value="' + v.worker_id + '">' : '') +
	   (p ? '<tr><td class="label">Должность:<td><input type="text" id="person-post" value="' + v.post + '" />' : '') +

	(pasp ? '<tr><td><td><a class="client-pasp-show">Заполнить паспортные данные</a>' : '') +
			'<tr' + pasp + '><td><td><b>Паспортные данные:</b>' +
			'<tr' + pasp + '><td class="label">Серия:' +
				'<td><input type="text" class="focus" id="' + prefix + 'pasp_seria" value="' + v.pasp_seria + '" />' +
					'<span class="label">Номер:</span><input type="text" id="' + prefix + 'pasp_nomer" value="' + v.pasp_nomer + '" />' +
			'<tr' + pasp + '><td class="label">Прописка:<td><input type="text" id="' + prefix + 'pasp_adres" value="' + v.pasp_adres + '" />' +
			'<tr' + pasp + '><td class="label">Кем выдан:<td><input type="text" id="' + prefix + 'pasp_ovd" value="' + v.pasp_ovd + '" />' +
			'<tr' + pasp + '><td class="label">Когда выдан:<td><input type="text" id="' + prefix + 'pasp_data" value="' + v.pasp_data + '" />' +
		'</table>';
	},
	clientEdit = function(o) {
		o = $.extend({
			id:0,
			category_id:1,
			worker_id:0,
			from_id:0,

			org_category_id:0,
			org_name:'',
			org_phone:'',
			org_fax:'',
			org_adres:'',
			org_inn:'',
			org_kpp:'',
			org_email:'',

			fio:'',
			phone:'',
			adres:'',
			post:'',
			pasp_seria:'',
			pasp_nomer:'',
			pasp_adres:'',
			pasp_ovd:'',
			pasp_data:'',

			callback:null
		}, window.CI || o);

		var cat = '';
		for(var i in CLIENT_CATEGORY_ASS)
			cat += '<a class="link' + (i == o.category_id ? ' sel' : '') + '" val="' + i + '">' + CLIENT_CATEGORY_ASS[i] + '</a>';

		var html =
			'<div id="client-add-tab">' +
				'<div id="dopLinks">' + cat + '</div>' +
				clientPeopleTab(o) +
				'<table class="ca-table' + (o.category_id == 2 ? '' : ' dn') + '" id="org">' +
					'<tr><td class="label"><b>Название организации:</b><td><input type="text" id="org_name" value="' + o.org_name + '" />' +
					'<tr><td class="label">Факс<td><input type="text" id="org_fax" value="' + o.org_fax + '" />' +
					'<tr><td class="label topi">Адрес:<td><textarea id="org_adres">' + _br(o.org_adres) + '</textarea>' +
					'<tr><td class="label">ИНН:<td><input type="text" id="org_inn" value="' + o.org_inn + '" />' +
					'<tr><td class="label">КПП:<td><input type="text" id="org_kpp"  value="' + o.org_kpp + '"/>' +
				'</table>' +

				'<table class="ca-table' + (CLIENT_FROM_USE ? '' : ' dn') + '">' +
					'<tr><td class="label"><span id="td-from">Откуда клиент нашёл нас:</span>' +
						'<td><input type="hidden" id="from_id" value="' + o.from_id + '" />' +
				'</table>' +

				'<table class="ca-table join-table' + (o.id ? '' : ' dn') + '">' +
					'<tr><td class="label">Объединить:<td><input type="hidden" id="join" />' +
					'<tr id="tr_join" class="dn"><td class="label">с клиентом:<td><input type="hidden" id="client2" />' +
				'</table>' +

			'</div>';
		var dialog = _dialog({
			width:480,
			top:30,
			padding:0,
			head:(o.id ? 'Редактирование данных' : 'Добавление нoвого') + ' клиента',
			content:html,
			submit:submit,
			butSubmit:o.id ? 'Сохранить' : 'Внести'
		});
		$('#fio').focus();
		$('#adres,#org_adres').autosize();
		$('#dopLinks .link').click(function() {
			var t = $(this),
				p = t.parent();
			o.category_id = _num(t.attr('val'));
			p.find('.sel').removeClass('sel');
			t.addClass('sel');
			$('#people')[(o.category_id != 1 ? 'add' : 'remove') + 'Class']('dn');
			$('#org')[(o.category_id == 1 ? 'add' : 'remove') + 'Class']('dn');
			$(o.category_id == 1 ? '#fio' : '#org_name').focus();
			$('.join-table').addClass('dn');
			$('#join')._check(0);
		});

		if(o.id) {
			$('#client2').clientSel({
				width:258,
				category_id:CI.category_id,
				not_client_id:CI.id
			});
			$('#join')
				._check()
				._check(function(v) {
					$('#tr_join')[(v ? 'remove' : 'add') + 'Class']('dn');
				});
			$('#join_check').vkHint({
				msg:'<b>Объединение клиентов.</b><br />' +
				'Необходимо, если один клиент был внесён в базу дважды.<br /><br />' +
				'Текущий клиент будет получателем.<br />Выберите второго клиента.<br />' +
				'Все заявки, начисления, платежи и доверенные лица<br />станут общими после объединения.<br /><br />' +
				'Внимание, операция необратима!',
				width:330,
				delayShow:1500,
				top:-162,
				left:-80,
				indent:80
			});
		}

		function submit() {
			var send = {
				op:o.id ? 'client_edit' : 'client_add',
				id:o.id,
				category_id:o.category_id,
				worker_id:_num($('#worker_id').val()),

				fio:$('#fio').val(),
				phone:$('#phone').val(),
				adres:$('#adres').val(),
				post:'',
				pasp_seria:$('#pasp_seria').val(),
				pasp_nomer:$('#pasp_nomer').val(),
				pasp_adres:$('#pasp_adres').val(),
				pasp_ovd:$('#pasp_ovd').val(),
				pasp_data:$('#pasp_data').val(),

				org_name:$('#org_name').val(),
				org_phone:$('#org_phone').val(),
				org_fax:$('#org_fax').val(),
				org_adres:$('#org_adres').val(),
				org_inn:$('#org_inn').val(),
				org_kpp:$('#org_kpp').val(),

				from_id:_num($('#from_id').val()),
				from_name:$('#from_id')._select('inp'),

				join:_num($('#join').val()),
				client2:_num($('#client2').val())
			};

			if(o.category_id == 1 && !send.fio) {
				dialog.err('Не указаны ФИО');
				$('#fio').focus();
				return;
			}
			if(o.category_id > 1 && !send.org_name) {
				dialog.err('Не указано название организации');
				$('#org_name').focus();
				return;
			}

			if(CLIENT_FROM_REQUIRE && !o.id && !send.from_id && !send.from_name) {
				dialog.err('Не указан источник, откуда пришёл клиент');
				return;
			}

			if(!send.join)
				send.client2 = 0;
			if(send.join && !send.client2) {
				dialog.err('Укажите второго клиента');
				return;
			}
			if(send.join && send.client2 == CI.id) {
				dialog.err('Выберите другого клиента');
				return;
			}

			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					if(o.id) {
						location.reload();
						return;
					}

					if(o.callback)
						o.callback(res);
					else
						document.location.href = URL + '&p=42&id=' + res.uid;
				} else
					dialog.abort().err(res.text);
			}, 'json');
		}
	},
*/
	_clientEdit = function(category_id, callback) {
		var CI = window.CI || {},
			client_id = _num(CI.id),
			category_id = _num(category_id),
			dialog = _dialog({
				width:550,
				top:30,
				padding:0,
				class:'client-edit',
				head:client_id ? 'Редактирование данных клиента' : 'Внесение нового клиента',
				load:1,
				butSubmit:client_id ? 'Сохранить' : 'Внести',
				submit:submit
			}),
			send = {
				op:'client_edit_load',
				category_id:category_id,
				client_id:client_id
			};

		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				dialog.content.html(res.html);
				loaded();
			} else
				dialog.loadError();
		}, 'json');

		function loaded() {
			dialog.content.find('#dopLinks .link').click(function() {
				var t = $(this),
					p = t.parent(),
					v = _num(t.attr('val'));

				p.find('.sel').removeClass('sel');
				t.addClass('sel');

				dialog.content.find('.tabs').addClass('dn');
				dialog.content.find('.tab' + v).removeClass('dn');

				$('#ce-category_id').val(v);
			});
			dialog.content.find('textarea').autosize();

			var skidka = $('.ce-skidka');
			for(var n = 0; n < skidka.length; n++)
				skidka.eq(n)._select({
					spisok:SKIDKA_SPISOK,
					title0:'Скидка не выбрана'
				});

			$('#ce-worker_id')._select({
				width:220,
				title0:'Сотрудник не выбран',
				spisok:CI.workers
			});

			$('#from_id')._select({
				width:220,
				title0:'источник не указан',
				write:1,
				write_save:1,
				spisok:CLIENT_FROM_SPISOK
			});
			$('#td-from').vkHint({
				msg:'Укажите источник, из которого клиент узнал о нашей организации.',
				width:140,
				top:-80,
				left:20
			});
		}

		function submit() {
			var cid = _num($('#ce-category_id').val()), //категория
				send = {
					op:client_id ? 'client_edit' : 'client_add',
					id:client_id,
					category_id:cid,

					name:$('#ce-name' + cid).val(),
					phone:$('#ce-phone' + cid).val(),
					adres:$('#ce-adres' + cid).val(),
					post:$('#ce-post' + cid).val(),

					pasp_seria:$('#pasp-seria' + cid).val(),
					pasp_nomer:$('#pasp-nomer' + cid).val(),
					pasp_adres:$('#pasp-adres' + cid).val(),
					pasp_ovd:$('#pasp-ovd' + cid).val(),
					pasp_data:$('#pasp-data' + cid).val(),

					fax:$('#ce-fax' + cid).val(),
					email:$('#ce-email' + cid).val(),
					inn:$('#ce-inn' + cid).val(),
					kpp:$('#ce-kpp' + cid).val(),
					ogrn:$('#ce-ogrn' + cid).val(),

					skidka:$('#ce-skidka' + cid).val(),

					worker_id:_num($('#ce-worker_id').val()),

					from_id:_num($('#from_id').val()),
					from_name:$('#from_id')._select('inp')

	//				join:_num($('#join').val()),
	//				client2:_num($('#client2').val())
				};

			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					if(callback) {
						callback(res.id);
						return;
					}
					location.href = URL + '&p=42&id=' + res.id;
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},

	_clientPersonAdd = function(o) {
		var html =
				'<div class="headName">Выберите доверенное лицо из существующих клиентов:</div>' +
				'<input type="hidden" id="person_id" />' +
				'<br />' +
				'<br />' +
				'<br />' +
				'<div class="headName">или введите данные нового доверенного лица:</div>' +
				'<div class="person-new _busy">&nbsp;</div>',
			dialog = _dialog({
				top:40,
				width:400,
				head:'Нoвое доверенное лицо',
				class:'person-add',
				content:html,
				butSubmit:'Добавить',
				submit:submit
			}),
			category_id = 0;

		$('#person_id').clientSel({
			width:355,
			not_client_id:CI.id
		});

		$.post(AJAX_MAIN, {op:'client_person_load'}, function(res) {
			$('.person-new').removeClass('_busy');
			if(res.success) {
				$('.person-new').html(res.html);
				category_id = res.category_id;
			} else
				$('.person-new').html('Не удалось загрузить форму для доверенного лица');
		}, 'json');

		function submit() {
			var send = {
				op:'client_person_add',
				category_id:category_id,
				client_id:CI.id,
				person_id:_num($('#person_id').val()),

				name:$('#ce-name' + category_id).val(),
				phone:$('#ce-phone' + category_id).val(),
				adres:$('#ce-adres' + category_id).val(),
				post:$('#ce-post' + category_id).val(),

				pasp_seria:$('#pasp-seria' + category_id).val(),
				pasp_nomer:$('#pasp-nomer' + category_id).val(),
				pasp_adres:$('#pasp-adres' + category_id).val(),
				pasp_ovd:$('#pasp-ovd' + category_id).val(),
				pasp_data:$('#pasp-data' + category_id).val()
			};

			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#ci-person').html(res.html);
					CI.person = res.array;
					dialog.close();
					_msg();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},

	clientDel = function(client_id) {
		_dialogDel({
			id:client_id,
			head:'клиента',
			op:'client_del',
			func:function() {
				location.href = URL + '&p=1';
			}
		});
	},
	
	_clientSpisok = function(v, id) {
		_filterSpisok(CLIENT, v, id);
		$('.filter')[CLIENT.find ? 'hide' : 'show']();
		$.post(AJAX_MAIN, CLIENT, function(res) {
			if(res.success) {
				$('.result').html(res.all);
				$('.left').html(res.spisok);
			}
		}, 'json');
	},
	_clientZayavSpisok = function(v, id) {
		var send = {
			op:'zayav_spisok',
			service_id:v,
			client_id:CI.id
		};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				$('#zayav-spisok').html(res.spisok);
		}, 'json');
	},
	clientFromEdit = function(o) {
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
				width:370,
				head:(o.id ? 'Редактирование' : 'Добавление нового' ) + ' источника',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();

		function submit() {
			var send = {
				op:'client_from_' + (o.id ? 'edit' : 'add'),
				from_id:o.id,
				from_name:$('#name').val()
			};
			if(!send.from_name) {
				dialog.err('Не указано название');
				$('#name').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#spisok').html(res.spisok);
					dialog.close();
					_msg();
				} else
					dialog.abort();
			}, 'json');
		}
	};

$.fn.clientSel = function(o) {
	var t = $(this);
	o = $.extend({
		width:260,
		add:null,
		client_id:t.val() || 0,
		not_client_id:0,    // исключать клиента с данным id
		category_id:0,      // возвращать только данную категорию
		func:function() {}
	}, o);

	if(o.add)
		o.add = function() {
			_clientEdit(0, function(client_id) {
					o.client_id = client_id;
					clientsGet();
			});
		};

	t._select({
		width:o.width,
		title0:'Начните вводить данные клиента...',
		spisok:[],
		write:1,
		nofind:'Клиентов не найдено',
		func:o.func,
		funcAdd:o.add,
		funcKeyup:clientsGet
	});
	clientsGet();

	function clientsGet(val) {
		var send = {
			op:'client_sel',
			val:val || '',
			client_id:o.client_id,
			not_client_id:o.not_client_id,
			category_id:o.category_id
		};
		t._select('process');
		$.post(AJAX_MAIN, send, function(res) {
			t._select('cancel');
			if(res.success) {
				t._select(res.spisok);
				if(_num(o.client_id)) {
					t._select(o.client_id);

					//todo переделать
					var item;
					for(var n = 0; n < res.spisok.length; n++) {
						var sp = res.spisok[n];
						if(sp.uid == o.client_id) {
							item = sp;
							break;
						}
					}

					o.func(o.client_id, '', item);
					o.client_id = 0;
				}
			}
		}, 'json');
	}
	return t;
};

$(document)
	.on('click', '#client .unit', function() {
		_scroll('set', $(this).attr('id'));
	})
	.on('click', '#client .vk.red', function() {
		$('#find')._search('clear');    CLIENT.find = '';
		$('#category_id')._radio(0);    CLIENT.category_id = 0;
		$('#dolg')._check(0);           CLIENT.dolg = 0;
		$('#opl')._check(0);            CLIENT.opl = 0;
		$('#worker')._check(0);         CLIENT.worker = 0;
		$('#remind')._select(0);        CLIENT.remind = 0;
		$('#skidka')._select(0);        CLIENT.skidka = 0;
		_clientSpisok();
	})

	.on('click', '.client-pasp-show', function() {//показ полей для заполнения паспортных данных
		var t = $(this),
			cid = _num(t.attr('val'));
		t.parent().parent().remove();
		$('.client-pasp' + cid).removeClass('dn');
		$('#pasp-seria' + cid).focus();
	})

	.on('click', '.client-info-go', function(e) {
		e.stopPropagation();
		location.href = URL + '&p=42&id=' + $(this).attr('val');
	})

	.on('click', '#client-info .person-poa', function() {//внесение доверенности
		var id = $(this).attr('val'),
			person = CI.person[id],
			html = '<table class="_dialog-tab">' +
					'<tr><td class="label w125">Организация:<td><b>' + CI.name + '</b>' +
					'<tr><td class="label">Доверенное лицо:<td>' + person.name +
					'<tr><td class="label">Номер доверенности:<td><input type="text" id="nomer" class="money" value="' + person.poa_nomer + '" />' +
					'<tr><td class="label">Дата выдачи:<td><input type="hidden" id="date_begin" value="' + person.poa_date_begin + '" />' +
					'<tr><td class="label">Дата окончания:<td><input type="hidden" id="date_end" value="' + person.poa_date_end + '" />' +
					'<tr><td class="label">Файл:<td><input type="hidden" id="attach_id-add" value="' + person.poa_attach_id + '" />' +
					'<tr><td><td>' +
					'<tr><td><td><input type="hidden" id="poa-del" />' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:400,
				head:'Информация о доверенности',
				content:html,
				butSubmit:'Сохранить',
				submit:submit
			});

		$('#nomer').focus();
		$('#date_begin')._calendar({lost:1});
		$('#date_end')._calendar({lost:1});
		$('#attach_id-add')._attach();
		if(person.poa_nomer)
			$('#poa-del')._check({
				light:1,
				name:'удалить доверенность',
				func:function(v) {
					dialog.butSubmit(v ? 'Удалить доверенность' : 'Сохранить');
				}
			});

		function submit() {
			var del = _num($('#poa-del').val()),
				send = {
					op:'client_poa_' + (del ? 'del' : 'add'),
					person_id:id,
					nomer:$('#nomer').val(),
					date_begin:$('#date_begin').val(),
					date_end:$('#date_end').val(),
					attach_id:$('#attach_id-add').val()
				};
			if(!send.nomer && !del) {
				dialog.err('Не указан номер доверенности');
				$('#nomer').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#ci-person').html(res.html);
					CI.person = res.array;
					dialog.close();
					_msg();
				} else
					dialog.abort();
			}, 'json');
		}
	})
	.on('click', '#client-info .person-del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'доверенного лица',
			op:'client_person_del',
			func:function(res) {
				$('#ci-person').html(res.html);
				CI.person = res.array;			}
		});
	})

	.on('click', '#client-from .add', clientFromEdit)
	.on('click', '#client-from .img_edit', function() {
		var t = _parent($(this));
		clientFromEdit({
			id:t.attr('val'),
			name:t.find('.name').html()
		});
	})
	.on('click', '#client-from .img_del', function() {
		var p = _parent($(this));
		_dialogDel({
			id:p.attr('val'),
			head:'источника, откуда пришёл клиент',
			op:'client_from_del',
			func:function() {
				p.remove();
			}
		});
	})

	.ready(function() {
		if($('#client').length) {
			$('#find')._search({
				width:458,
				focus:1,
				enter:1,
				txt:'Введите текст и нажмите Enter',
				v:CLIENT.find,
				func:_clientSpisok
			});
			$('#category_id')._radio(_clientSpisok);
			$('#dolg')._check(function(v, id) {
				$('#opl')._check(0);
				CLIENT.opl = 0;
				_clientSpisok(v, id);
			});
			$('#opl')._check(function(v, id) {
				$('#dolg')._check(0);
				CLIENT.dolg = 0;
				_clientSpisok(v, id);
			});
			$('#worker')._check(_clientSpisok);
			$('#dolg_check').vkHint({
				msg:'<b>Список должников.</b><br /><br />' +
					'Выводятся клиенты, у которых баланс менее 0. Также в результате отображается общая сумма долга.',
				ugol:'right',
				width:150,
				top:-25,
				left:-183,
				indent:20,
				delayShow:1000
			});
			$('#remind')._select({
				width: 140,
				title0: 'не важно',
				spisok: [
					{uid:1,title:'есть'},
					{uid:2,title:'нет'}
				],
				func: _clientSpisok
			});
			$('#skidka')._select({
				width: 140,
				spisok:CLIENT_SKIDKA,
				title0:'не выбрана',
				func: _clientSpisok
			});
		}
		if($('#client-info').length) {
			$('#dopLinks a.link:first').addClass('sel');
			$('.ci-cont:first').show();
			$('.ci-right:first').show();
			$('#dopLinks .link').click(function() {
				$('#dopLinks .link').removeClass('sel');
				var i = $(this).addClass('sel').index();
				$('.ci-cont').hide().eq(i).show();
				$('.ci-right').hide().eq(i).show();
			});

			$('#zayav-type-id')._radio({
				light:1,
				right:0,
				spisok:_toSpisok(CI.service_client),
				func:_clientZayavSpisok
			});
		}
		if($('#client-from').length) {
			$('#client_from_use')._check(function(v) {
				$('.tr-require')[(v ? 'remove' : 'add') + 'Class']('dn');
				$('.tr-submit').removeClass('dn');
			});
			$('#client_from_require')._check(function() {
				$('.tr-submit').removeClass('dn');
			});
			$('.setup-submit').click(function() {
				var t = $(this);
				if(t.hasClass('_busy'))
					return;

				var send = {
					op:'client_from_setup',
					use:_bool($('#client_from_use').val()),
					require:_bool($('#client_from_require').val())
				};
				
				t.addClass('_busy');
				$.post(AJAX_MAIN, send, function(res) {
					t.removeClass('_busy');
					if(res.success) {
						$('.tr-submit').addClass('dn');
						_msg();
					}
				}, 'json');
			});
		}
	});
