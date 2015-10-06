var clientAdd = function(callback) {
		var html =
			'<div id="client-add-tab">' +
				'<div id="dopLinks">';
//					'<a class="link sel" val="1">Частное лицо</a>' +
//					'<a class="link" val="2">Организация</a><br />' +
		for(var i in CLIENT_CATEGORY_ASS)
			html += '<a class="link' + (i == 1 ? ' sel' : '') + '" val="' + i + '">' + CLIENT_CATEGORY_ASS[i] + '</a>';
		html += '</div>' +
				'<table class="ca-table" id="people">' +
					'<tr><td class="label"><b>Ф.И.О.:</b><td><input type="text" id="fio" />' +
					'<tr><td class="label">Телефон:<td><input type="text" id="phone" />' +
					'<tr><td class="label top">Дополнительная<br />информация:<td><textarea id="info_people"></textarea>' +
				'</table>' +
				'<table class="ca-table dn" id="org">' +
					'<tr><td class="label"><b>Название организации:</b><td><input type="text" id="org_name" />' +
					'<tr><td class="label">Телефон:<td><input type="text" id="org_phone" />' +
					'<tr><td class="label">Факс<td><input type="text" id="org_fax" />' +
					'<tr><td class="label topi">Адрес:<td><textarea id="org_adres"></textarea>' +
					'<tr><td class="label">ИНН:<td><input type="text" id="org_inn" />' +
					'<tr><td class="label">КПП:<td><input type="text" id="org_kpp" />' +
					'<tr><td class="label top">Дополнительная<br />информация:<td><textarea id="info_org"></textarea>' +
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
		$('#info_people,#info_org,#org_adres').autosize();
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
				phone = $('#phone').val(),
				send = {
					op:'client_add',
					category_id:category_id,
					org_name:$('#org_name').val(),
					org_phone:$('#org_phone').val(),
					org_fax:$('#org_fax').val(),
					org_adres:$('#org_adres').val(),
					org_inn:$('#org_inn').val(),
					org_kpp:$('#org_kpp').val(),
					info_dop:$(category_id == 1 ? '#info_people' : '#info_org').val(),
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
						phone:phone,
						post:''
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
					} else dialog.abort();
				}, 'json');
			}
		}
	},
	clientEdit = function() {
		var org = CLIENT.category_id > 1,
			html =
			'<div id="client-add-tab">' +
			(!org ?
				'<table class="ca-table">' +
					'<tr><td class="label">Ф.И.О.:<td><input type="text" id="fio" value="' + CLIENT.fio + '" />' +
					'<tr><td class="label">Телефон:<td><input type="text" id="phone" value="' + CLIENT.phone + '" />' +
					'<tr><td class="label top">Дополнительная<br />информация:<td><textarea id="info_dop">' + $('#info-dop').val() + '</textarea>' +
				'</table>'
			: '') +
			(org ?
				'<table class="ca-table">' +
					'<tr><td class="label">Название организации:<td><input type="text" id="org_name" value="' + CLIENT.org_name + '" />' +
					'<tr><td class="label">Телефон:<td><input type="text" id="org_phone" value="' + CLIENT.org_phone + '" />' +
					'<tr><td class="label">Факс:<td><input type="text" id="org_fax" value="' + CLIENT.org_fax + '" />' +
					'<tr><td class="label top">Адрес:<td><textarea id="org_adres">' + CLIENT.org_adres + '</textarea>' +
					'<tr><td class="label">ИНН:<td><input type="text" id="org_inn" value="' + CLIENT.org_inn + '" />' +
					'<tr><td class="label">КПП:<td><input type="text" id="org_kpp" value="' + CLIENT.org_kpp + '" />' +
					'<tr><td class="label top">Дополнительная<br />информация:<td><textarea id="info_dop">' + $('#info-dop').val() + '</textarea>' +
				'</table>'
			: '') +
//				'<a id="post-add">Добавить доверенное лицо</a>' +
				'<table class="ca-table">' +
					'<tr><td class="label">Объединить:<td><input type="hidden" id="join" />' +
					'<tr id="tr_join"><td class="label">с клиентом:<td><input type="hidden" id="client2" />' +
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
		$('#info_dop,#org_adres').autosize();
//		$('#client2').clientSel({width:258});
		$('#join')
			._check()
			._check(function() {
				$('#tr_join').toggle();
			});
		$('#join_check').vkHint({
			msg:'<b>Объединение клиентов.</b><br />' +
				'Необходимо, если один клиент был внесён в базу дважды.<br /><br />' +
				'Текущий клиент будет получателем.<br />Выберите второго клиента.<br />' +
				'Все заявки, начисления и платежи станут общими после<br />объединения.<br /><br />' +
				'Внимание, операция необратима!',
			width:330,
			delayShow:1500,
			top:-162,
			left:-81,
			indent:80
		});
		function submit() {
			var send = {
				op:'client_edit',
				id:CLIENT.id,
				info_dop:$('#info_dop').val()
//				join:_num($('#join').val()),
//				client2:_num($('#client2').val())
			};

			if(!org) {
				send.person_id = CLIENT.person_id;
				send.fio = $.trim($('#fio').val());
				send.phone = $.trim($('#phone').val());
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
			}// else if(send.join && !send.client2)
//				dialog.err('Укажите второго клиента');
//			else if(send.join && send.client2 == CLIENT.id)
//				dialog.err('Выберите другого клиента');
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

	clientPersonAdd = function(person) {
		var txt =
			'<table id="client-person-add">' +
				'<tr><td class="label r">Ф.И.О.:<td><input type="text" id="person-fio" />' +
				'<tr><td class="label r">Телефон:<td><input type="text" id="person-phone" />' +
				'<tr><td class="label r">Должность:<td><input type="text" id="person-post" />' +
			'</table>',
			dialog = _dialog({
				top:120,
				head:'Нoвое доверенное лицо',
				content:txt,
				butSubmit:'Добавить',
				submit:submit
			});
		$('#person-fio').focus();

		function submit() {
			var send = {
				fio:$.trim($('#person-fio').val()),
				phone:$.trim($('#person-phone').val()),
				post:$.trim($('#person-post').val())
			};
			if(!send.fio) {
				dialog.err('Не указано ФИО');
				$('#person-fio').focus();
			} else {
				if($('#client-info').length) {
					send.op = 'client_person_add';
					send.client_id = CLIENT.id;
					dialog.process();
					$.post(AJAX_MAIN, send, function(res) {
						if(res.success) {
							$('#person-spisok').html(res.html);
							dialog.close();
							_msg('Новое доверенное лицо внесено');
						} else
							dialog.abort();
					}, 'json');
					return;
				}

				dialog.close();
				person.push(send);
				var html = '';
				for (var i in person)
					html +=
						(i * 1 + 1) + '. ' +
						(person[i].post ? '<u>' + person[i].post + ':</u> ' : '') +
						person[i].fio +
						(person[i].phone ? ', ' + person[i].phone : '') +
						'<br />';
				$('#person-list').html(html);
			}
		}
	},

	clientFilter = function() {
		var v = {
				op:'client_spisok',
				find:$('#find')._search('val'),
				dolg:$('#dolg').val(),
				active:$('#active').val(),
				comm:$('#comm').val(),
				opl:$('#opl').val()
			},
			loc = '';
		$('.filter')[v.find ? 'hide' : 'show']();

		if(v.find) loc += '.find=' + escape(v.find);
		else {
			if(v.dolg > 0) loc += '.dolg=' + v.dolg;
			if(v.active > 0) loc += '.active=' + v.active;
			if(v.comm > 0) loc += '.comm=' + v.comm;
			if(v.opl > 0) loc += '.opl=' + v.opl;
		}
		VK.callMethod('setLocation', hashLoc + loc);

		_cookie(VIEWER_ID + '_client_find', escape(v.find));
		_cookie(VIEWER_ID + '_client_dolg', v.dolg);
		_cookie(VIEWER_ID + '_client_active', v.active);
		_cookie(VIEWER_ID + '_client_comm', v.comm);
		_cookie(VIEWER_ID + '_client_opl', v.opl);

		return v;
	},
	clientSpisok = function() {
		var result = $('.result');
		if(result.hasClass('busy'))
			return;
		result.addClass('busy');
		$.post(AJAX_MAIN, clientFilter(), function (res) {
			result.removeClass('busy');
			if(res.success) {
				result.html(res.all);
				$('.left').html(res.spisok);
			}
		}, 'json');
	},
	clientZayavFilter = function() {
		return {
			op:'client_zayav_spisok',
			client_id:CLIENT.id,
			status:$('#status').val(),
			diff:$('#diff').val(),
			device:$('#dev_device').val(),
			vendor:$('#dev_vendor').val(),
			model:$('#dev_model').val()
		};
	},
	clientZayavSpisok = function() {
		$('#dopLinks').addClass('busy');
		$.post(AJAX_MAIN, clientZayavFilter(), function (res) {
			$('#dopLinks').removeClass('busy');
			$('#zayav_result').html(res.all);
			$('#zayav_spisok').html(res.html);
		}, 'json');
	};

$.fn.clientSel = function(o) {
	var t = $(this);
	o = $.extend({
		width:260,
		add:null,
		client_id:t.val() || 0,
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
			client_id:o.client_id
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
	.on('click', '#client ._next', function() {
		if($(this).hasClass('busy'))
			return;
		var next = $(this),
			send = clientFilter();
		send.page = next.attr('val');
		next.addClass('busy');
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				next.after(res.spisok).remove();
			else
				next.removeClass('busy');
		}, 'json');
	})
	.on('click', '#client #filter_clear', function() {
		$('#find')._search('clear');
		$('#dolg')._check(0);
		$('#active')._check(0);
		$('#comm')._check(0);
		$('#opl')._check(0);
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

	.on('click', '#clientInfo #zayav_spisok ._next', function() {
		if($(this).hasClass('busy'))
			return;
		var next = $(this),
			send = clientZayavFilter();
		send.page = $(this).attr('val');
		next.addClass('busy');
		$.post(AJAX_MAIN, send, function (res) {
			if(res.success)
				next.after(res.html).remove();
			else
				next.removeClass('busy');
		}, 'json');
	})
	.on('click', '.go-client-info', function(e) {
		e.stopPropagation();
		location.href = URL + '&p=client&d=info&id=' + $(this).attr('val');
	})

	.on('click', '#client-info #person-add', clientPersonAdd)
	.on('click', '#client-info .person-del', function() {
		var t = $(this),
			dialog = _dialog({
				top:90,
				width:300,
				head:'Удаление доверенного лица',
				content:'<center><br /><span class="red">Подтвердите удаление<br />доверенного лица.</span><br /><br /></center>',
				butSubmit:'Удалить',
				submit:submit
			});
		function submit() {
			var send = {
				op:'client_person_del',
				id:t.attr('val')
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#person-spisok').html(res.html);
					dialog.close();
					_msg('Удалено!');
				} else
					dialog.abort();
			}, 'json');
		}
	})


	.ready(function() {
		if($('#client').length) {
			$('#find')._search({
				width:602,
				focus:1,
				enter:1,
				txt:'Начните вводить данные клиента',
				func:clientSpisok
			}).inp(C.find);
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
			$('#dolg')._check(clientSpisok);
			$('#active')._check(clientSpisok);
			$('#comm')._check(clientSpisok);
			$('#opl')._check(clientSpisok);
			$('#dolg_check').vkHint({
				msg:'<b>Список должников.</b><br /><br />' +
					'Выводятся клиенты, у которых баланс менее 0. Также в результате отображается общая сумма долга.',
				ugol:'right',
				width:150,
				top:-6,
				left:-185,
				indent:20,
				delayShow:1000
			});
		}
		if($('#client-info').length) {
			$('#client-edit').click(clientEdit);
/*
			$('#dopLinks .link').click(function() {
				$('#dopLinks .link').removeClass('sel');
				$(this).addClass('sel');
				var val = $(this).attr('val');
				$('.res').css('display', val == 'zayav' ? 'block' : 'none');
				$('#zayav_filter').css('display', val == 'zayav' ? 'block' : 'none');
				$('#zayav_spisok').css('display', val == 'zayav' ? 'block' : 'none');
				$('#schet_spisok').css('display', val == 'schet' ? 'block' : 'none');
				$('#money_spisok').css('display', val == 'money' ? 'block' : 'none');
				$('#remind-spisok').css('display', val == 'remind' ? 'block' : 'none');
				//$('#remind_spisok').css('display', val == 'remind' ? 'block' : 'none');
				$('#comments').css('display', val == 'comm' ? 'block' : 'none');
				$('#histories').css('display', val == 'hist' ? 'block' : 'none');
			});
			$('#status').rightLink(clientZayavSpisok);
			$('#diff')._check(clientZayavSpisok);
			$('#dev').device({
				width:145,
				type_no:1,
				device_ids:DEVICE_IDS,
				vendor_ids:VENDOR_IDS,
				model_ids:MODEL_IDS,
				func:clientZayavSpisok
			});
*/
		}
	});
