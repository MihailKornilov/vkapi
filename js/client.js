var clientPeopleTab = function(v, p) {// таблица: частное лицо
		v = v || {
			fio:'',
			phone:'',
			adres:'',
			post:'',
			pasp_seria:'',
			pasp_nomer:'',
			pasp_adres:'',
			pasp_ovd:'',
			pasp_data:''
		};
		// отображать ли паспортные данные
		var pasp = v.pasp_seria || v.pasp_nomer || v.pasp_adres || v.pasp_ovd || v.pasp_data ? '' : ' class="dn"',
			prefix = p ? 'person-' : '';
		return '' +
		'<table class="ca-table" id="people">' +
			'<tr><td class="label"><b>Ф.И.О.:</b><td><input type="text" id="' + prefix + 'fio" value="' + v.fio + '" />' +
			'<tr><td class="label">Телефон:      <td><input type="text" id="' + prefix + 'phone" value="' + v.phone + '" />' +
			'<tr><td class="label topi">Адрес:   <td><textarea id="' + prefix + 'adres">' + v.adres + '</textarea>' +
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
	clientAdd = function(callback) {
		var html =
			'<div id="client-add-tab">' +
				'<div id="dopLinks">';
		for(var i in CLIENT_CATEGORY_ASS)
			html += '<a class="link' + (i == 1 ? ' sel' : '') + '" val="' + i + '">' + CLIENT_CATEGORY_ASS[i] + '</a>';
		html += '</div>' +
				clientPeopleTab() +
				'<table class="ca-table dn" id="org">' +
					'<tr><td class="label"><b>Название организации:</b><td><input type="text" id="org_name" />' +
					'<tr><td class="label">Телефон:<td><input type="text" id="org_phone" />' +
					'<tr><td class="label">Факс<td><input type="text" id="org_fax" />' +
					'<tr><td class="label topi">Адрес:<td><textarea id="org_adres"></textarea>' +
					'<tr><td class="label">ИНН:<td><input type="text" id="org_inn" />' +
					'<tr><td class="label">КПП:<td><input type="text" id="org_kpp" />' +
				'</table>' +
				'<div id="person-head">Доверенные лица:</div>' +
				'<div id="person-list"></div>' +
				'<a id="person-add">Добавить доверенное лицо</a>' +
			'</div>';
		var category_id = 1,
			person = [],
			dialog = _dialog({
				width:480,
				top:30,
				padding:0,
				head:'Добавление нoвого клиента',
				content:html,
				submit:submit
			});
		$('#fio').focus();
		$('#adres,#org_adres').autosize();
		$('#person-add').click(function() {
			clientPersonAdd(person);
		});
		$('#dopLinks .link').click(function() {
			var t = $(this),
				p = t.parent();
			category_id = _num(t.attr('val'));
			p.find('.sel').removeClass('sel');
			t.addClass('sel');
			$('#people')[(category_id != 1 ? 'add' : 'remove') + 'Class']('dn');
			$('#org')[(category_id == 1 ? 'add' : 'remove') + 'Class']('dn');
			$(category_id == 1 ? '#fio' : '#org_name').focus();
		});
		function submit() {
			var fio = $('#fio').val(),
				send = {
					op:'client_add',
					category_id:category_id,
					org_name:$('#org_name').val(),
					org_phone:$('#org_phone').val(),
					org_fax:$('#org_fax').val(),
					org_adres:$('#org_adres').val(),
					org_inn:$('#org_inn').val(),
					org_kpp:$('#org_kpp').val(),
					person:person
				};

			if(category_id == 1 && !fio) {
				dialog.err('Не указаны ФИО');
				$('#fio').focus();
			} else if(category_id > 1 && !send.org_name) {
				dialog.err('Не указано название организации');
				$('#org_name').focus();
			} else {
				if(category_id == 1) // если выбрано частное лицо, то помещается в доверенные лица на первое место
					send.person.unshift({
						fio:fio,
						phone:$('#phone').val(),
						adres:$('#adres').val(),
						post:'',
						pasp_seria:$('#pasp_seria').val(),
						pasp_nomer:$('#pasp_nomer').val(),
						pasp_adres:$('#pasp_adres').val(),
						pasp_ovd:$('#pasp_ovd').val(),
						pasp_data:$('#pasp_data').val()
					});
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('Новый клиент внесён.');
//						if(typeof callback == 'function')
//							callback(res);
//						else
							document.location.href = URL + '&p=client&d=info&id=' + res.uid;
					} else {
						dialog.abort();
						send.person.shift();
					}
				}, 'json');
			}
		}
	},
	clientEdit = function() {
		var org = CLIENT.category_id > 1,
			html =
			'<div id="client-add-tab">' +
			(!org ? clientPeopleTab(CLIENT) : '') +
			(org ?
				'<table class="ca-table">' +
					'<tr><td class="label">Название организации:<td><input type="text" id="org_name" value="' + CLIENT.org_name + '" />' +
					'<tr><td class="label">Телефон:<td><input type="text" id="org_phone" value="' + CLIENT.org_phone + '" />' +
					'<tr><td class="label">Факс:<td><input type="text" id="org_fax" value="' + CLIENT.org_fax + '" />' +
					'<tr><td class="label top">Адрес:<td><textarea id="org_adres">' + CLIENT.org_adres + '</textarea>' +
					'<tr><td class="label">ИНН:<td><input type="text" id="org_inn" value="' + CLIENT.org_inn + '" />' +
					'<tr><td class="label">КПП:<td><input type="text" id="org_kpp" value="' + CLIENT.org_kpp + '" />' +
				'</table>'
			: '') +
				'<table class="ca-table">' +
					'<tr><td class="label">Объединить:<td><input type="hidden" id="join" />' +
					'<tr id="tr_join" class="dn"><td class="label">с клиентом:<td><input type="hidden" id="client2" />' +
				'</table>' +
			'</div>',
			dialog = _dialog({
				width:500,
				top:30,
				head:'Редактирование данных клиента',
				content:html,
				butSubmit:'Сохранить',
				submit:submit
			});
		$('#' + (org ? 'org_name' : 'fio')).focus();
		$('#adres,#org_adres').autosize();
		$('#client2').clientSel({
			width:258,
			category_id:CLIENT.category_id,
			not_client_id:CLIENT.id
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
		function submit() {
			var send = {
				op:'client_edit',
				id:CLIENT.id,
				join:_num($('#join').val()),
				client2:_num($('#client2').val())
			};

			if(!org) {
				send.person_id = CLIENT.person_id;
				send.fio = $.trim($('#fio').val());
				send.phone = $.trim($('#phone').val());
				send.adres = $.trim($('#adres').val());
				send.pasp_seria = $.trim($('#pasp_seria').val());
				send.pasp_nomer = $.trim($('#pasp_nomer').val());
				send.pasp_adres = $.trim($('#pasp_adres').val());
				send.pasp_ovd = $.trim($('#pasp_ovd').val());
				send.pasp_data = $.trim($('#pasp_data').val());
			}

			if(org) {
				send.org_name = $('#org_name').val();
				send.org_phone = $('#org_phone').val();
				send.org_fax = $('#org_fax').val();
				send.org_adres = $('#org_adres').val();
				send.org_inn = $('#org_inn').val();
				send.org_kpp = $('#org_kpp').val();
			}

			if(!send.join)
				send.client2 = 0;

			if(!org && !send.fio) {
				dialog.err('Не указаны ФИО');
				$('#fio').focus();
			} else if(org && !send.org_name) {
				dialog.err('Не указано название организации');
				$('#org_name').focus();
			} else if(send.join && !send.client2)
				dialog.err('Укажите второго клиента');
			else if(send.join && send.client2 == CLIENT.id)
				dialog.err('Выберите другого клиента');
			else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('Данные клиента изменены');
						document.location.reload();
					} else
						dialog.abort();
				}, 'json');
			}
		}
	},

	clientPersonVal = function() {
		return {
			op:'client_person_add',
			fio:$('#person-fio').val(),
			phone:$('#person-phone').val(),
			adres:$('#person-adres').val(),
			post:$('#person-post').val(),
			pasp_seria:$('#person-pasp_seria').val(),
			pasp_nomer:$('#person-pasp_nomer').val(),
			pasp_adres:$('#person-pasp_adres').val(),
			pasp_ovd:$('#person-pasp_ovd').val(),
			pasp_data:$('#person-pasp_data').val()
		};
	},
	clientPersonAdd = function(person) {
		var html = '<div id="client-add-tab">' + clientPeopleTab(0, 1) + '</div>',
			dialog = _dialog({
				top:80,
				width:400,
				head:'Нoвое доверенное лицо',
				content:html,
				butSubmit:'Добавить',
				submit:submit
			});
		$('#person-fio').focus();
		$('#person-adres').autosize();

		function submit() {
			var send = clientPersonVal();
			if(!send.fio) {
				dialog.err('Не указано ФИО');
				$('#person-fio').focus();
			} else {
				if($('#client-info').length) {
					send.client_id = CLIENT.id;
					dialog.process();
					$.post(AJAX_MAIN, send, function(res) {
						if(res.success) {
							$('#person-spisok').html(res.html);
							CLIENT.person = res.array;
							dialog.close();
							_msg('Новое доверенное лицо внесено');
						} else
							dialog.abort();
					}, 'json');
					return;
				}

				dialog.close();
				person.push(send);
				personPrint(person);
			}
		}
		function personPrint(person) {
			var html = '<table class="_spisok">';
			for(var i in person)
				html += '<tr>' +
				'<td>' + (i * 1 + 1) +
				'<td>' + person[i].fio + (person[i].phone ? ', ' + person[i].phone : '') +
				'<td>' + (person[i].post ? '<u>' + person[i].post + '</u> ' : '') +
				'<td><div val="' + i + '" class="img_del' + _tooltip('Удалить', -29) + '</div>';
			html += '</table>';
			$('#person-list')
				.html(html)
				.find('.img_del').click(function() {
					var v = $(this).attr('val');
					person.splice(v, 1);
					personPrint(person);
				});

		}
	},

	clientSpisok = function(v, id) {
		_filterSpisok(CLIENT, v, id);
		$('.filter')[CLIENT.find ? 'hide' : 'show']();
		$.post(AJAX_MAIN, CLIENT, function(res) {
			if(res.success) {
				$('.result').html(res.all);
				$('.left').html(res.spisok);
			}
		}, 'json');
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
			clientAdd(function(res) {
				var arr = [];
				arr.push(res);
				t._select(arr);
				t._select(res.uid);
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
				if(o.client_id) {
					t._select(o.client_id);
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
	.on('click', '#client #filter_clear', function() {
		$('#find')._search('clear');
		$('#category_id')._radio(0);
		$('#dolg')._check(0);
//		$('#active')._check(0);
//		$('#comm')._check(0);
		$('#opl')._check(0);
		CLIENT.find = '';
		CLIENT.dolg = 0;
		CLIENT.category_id = 0;
//		CLIENT.active = 0;
//		CLIENT.comm = 0;
		CLIENT.opl = 0;
		clientSpisok();
	})
	.on('mouseenter', '#client .comm', function() {
		var t = $(this),
			v = t.attr('val');
		t.vkHint({
			msg:v,
			width:200,
			ugol:'right',
			top:-2,
			left:-227,
			indent:'top',
			show:1
		})
	})
	.on('click', '.client-pasp-show', function() {//показ полей для заполнения паспортных данных
		var p = $(this).parent().parent();
		p.parent().find('.dn').removeClass('dn');
		p.parent().find('.focus').focus();
		p.remove();
	})

	.on('click', '.client-info-go', function(e) {
		e.stopPropagation();
		location.href = URL + '&p=client&d=info&id=' + $(this).attr('val');
	})

	.on('click', '#client-info #person-add', clientPersonAdd)
	.on('click', '#client-info .person-edit', function() {
		var id = $(this).attr('val'),
			html = '<div id="client-add-tab">' + clientPeopleTab(CLIENT.person[id], 1) + '</div>',
			dialog = _dialog({
				width:400,
				head:'Редактирование доверенного лица',
				content:html,
				butSubmit:'Изменить',
				submit:submit
			});
		$('#person-fio').focus();
		$('#person-adres').autosize();

		function submit() {
			var send = clientPersonVal();
			send.person_id = id;
			if(!send.fio) {
				dialog.err('Не указано ФИО');
				$('#person-fio').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						$('#person-spisok').html(res.html);
						CLIENT.person = res.array;
						dialog.close();
						_msg('Изменено');
					} else
						dialog.abort();
				}, 'json');
			}
		}
	})
	.on('click', '#client-info .person-del', function() {
		_dialogDel({
			id:$(this).attr('val'),
			head:'доверенного лица',
			op:'client_person_del',
			func:function(res) {
				$('#person-spisok').html(res.html);
				CLIENT.person = res.array;			}
		});
	})

	.ready(function() {
		if($('#client').length) {
			$('#find')._search({
				width:458,
				focus:1,
				enter:1,
				txt:'Начните вводить данные клиента',
				func:clientSpisok
			}).inp(CLIENT.find);
			$('#buttonCreate').vkHint({
				msg:'<B>Внесение нового клиента в базу.</B><br /><br />' +
					'После внесения Вы попадаете на страницу с информацией о клиенте для дальнейших действий.<br /><br />' +
					'Клиентов также можно добавлять при <A href="' + URL + '&p=zayav&d=add&back=client">создании новой заявки</A>.',
				ugol:'right',
				width:215,
				top:-38,
				left:-250,
				indent:40,
				delayShow:1000
			}).click(clientAdd);
			$('#category_id')._radio(clientSpisok);
			$('#dolg')._check(clientSpisok);
//			$('#active')._check(clientSpisok);
//			$('#comm')._check(clientSpisok);
			$('#opl')._check(clientSpisok);
			$('#dolg_check').vkHint({
				msg:'<b>Список должников.</b><br /><br />' +
					'Выводятся клиенты, у которых баланс менее 0. Также в результате отображается общая сумма долга.',
				ugol:'right',
				width:150,
				top:-5,
				left:-185,
				indent:20,
				delayShow:1000
			});
		}
		if($('#client-info').length) {
			$('a.link:first').addClass('sel');
			$('.ci-cont:first').show();
			$('.ci-right:first').show();
			$('.link').click(function() {
				$('.link').removeClass('sel');
				var i = $(this).addClass('sel').index();
				$('.ci-cont').hide().eq(i).show();
				$('.ci-right').hide().eq(i).show();
			});

			$('#client-schet-add').click(function() {
				_schetEdit({
					edit:1,
					client_id:CLIENT.id,
					client:CLIENT.name
				});
			});
			$('#client-edit').click(clientEdit);
			$('#client-del').click(function() {
				_dialogDel({
					id:CLIENT.id,
					head:'клиента',
					op:'client_del',
					func:function() {
						location.href = URL + '&p=client';
					}
				});
			});
			$('#zayav-type')._radio(function(v) {
				$('#spisok1')[(v == 1 ? 'remove' : 'add') + 'Class']('dn');
				$('#spisok2')[(v == 2 ? 'remove' : 'add') + 'Class']('dn');
			});
		}
	});
