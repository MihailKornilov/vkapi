var clientPeopleTab = function(v, p) {// �������: ������� ����
		// ���������� �� ���������� ������
		var pasp = v.pasp_seria || v.pasp_nomer || v.pasp_adres || v.pasp_ovd || v.pasp_data ? '' : ' class="dn"',
			prefix = p ? 'person-' : '',
			worker = !p && v.id;//���������� �����: ������ � �����������
		return '' +
		'<table class="ca-table' + (p || v.category_id == 1 ? '' : ' dn') + '" id="people">' +
			'<tr><td class="label"><b>�.�.�.:</b><td><input type="text" id="' + prefix + 'fio" value="' + v.fio + '" />' +
			'<tr><td class="label">�������:      <td><input type="text" id="' + prefix + 'phone" value="' + v.phone + '" />' +
			'<tr><td class="label topi">�����:   <td><textarea id="' + prefix + 'adres">' + _br(v.adres) + '</textarea>' +
  (worker ? '<tr><td class="label">������ � �����������:<td><input type="hidden" id="worker_id" value="' + v.worker_id + '">' : '') +
	   (p ? '<tr><td class="label">���������:<td><input type="text" id="person-post" value="' + v.post + '" />' : '') +

	(pasp ? '<tr><td><td><a class="client-pasp-show">��������� ���������� ������</a>' : '') +
			'<tr' + pasp + '><td><td><b>���������� ������:</b>' +
			'<tr' + pasp + '><td class="label">�����:' +
				'<td><input type="text" class="focus" id="' + prefix + 'pasp_seria" value="' + v.pasp_seria + '" />' +
					'<span class="label">�����:</span><input type="text" id="' + prefix + 'pasp_nomer" value="' + v.pasp_nomer + '" />' +
			'<tr' + pasp + '><td class="label">��������:<td><input type="text" id="' + prefix + 'pasp_adres" value="' + v.pasp_adres + '" />' +
			'<tr' + pasp + '><td class="label">��� �����:<td><input type="text" id="' + prefix + 'pasp_ovd" value="' + v.pasp_ovd + '" />' +
			'<tr' + pasp + '><td class="label">����� �����:<td><input type="text" id="' + prefix + 'pasp_data" value="' + v.pasp_data + '" />' +
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
					'<tr><td class="label"><b>�������� �����������:</b><td><input type="text" id="org_name" value="' + o.org_name + '" />' +
					'<tr><td class="label">�������:<td><input type="text" id="org_phone" value="' + o.org_phone + '" />' +
					'<tr><td class="label">����<td><input type="text" id="org_fax" value="' + o.org_fax + '" />' +
					'<tr><td class="label topi">�����:<td><textarea id="org_adres">' + _br(o.org_adres) + '</textarea>' +
					'<tr><td class="label">���:<td><input type="text" id="org_inn" value="' + o.org_inn + '" />' +
					'<tr><td class="label">���:<td><input type="text" id="org_kpp"  value="' + o.org_kpp + '"/>' +
				'</table>' +

				'<table class="ca-table' + (CLIENT_FROM_USE ? '' : ' dn') + '">' +
					'<tr><td class="label"><span id="td-from">������ ������ ����� ���:</span>' +
						'<td><input type="hidden" id="from_id" value="' + o.from_id + '" />' +
				'</table>' +

				'<table class="ca-table join-table' + (o.id ? '' : ' dn') + '">' +
					'<tr><td class="label">����������:<td><input type="hidden" id="join" />' +
					'<tr id="tr_join" class="dn"><td class="label">� ��������:<td><input type="hidden" id="client2" />' +
				'</table>' +

			'</div>';
		var dialog = _dialog({
			width:480,
			top:30,
			padding:0,
			head:(o.id ? '�������������� ������' : '���������� �o����') + ' �������',
			content:html,
			submit:submit,
			butSubmit:o.id ? '���������' : '������'
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
			$('#worker_id')._select({
				width:220,
				title0:'��������� �� ������',
				spisok:CI.workers
			});
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
				msg:'<b>����������� ��������.</b><br />' +
				'����������, ���� ���� ������ ��� ����� � ���� ������.<br /><br />' +
				'������� ������ ����� �����������.<br />�������� ������� �������.<br />' +
				'��� ������, ����������, ������� � ���������� ����<br />������ ������ ����� �����������.<br /><br />' +
				'��������, �������� ����������!',
				width:330,
				delayShow:1500,
				top:-162,
				left:-80,
				indent:80
			});
		}

		$('#from_id')._select({
			width:220,
			title0:'�������� �� ������',
			write:1,
			write_save:1,
			spisok:CLIENT_FROM_SPISOK
		});
		$('#td-from').vkHint({
			msg:'������� ��������, �� �������� ������ ����� � ����� �����������.',
			width:140,
			top:-80,
			left:20
		});

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
				dialog.err('�� ������� ���');
				$('#fio').focus();
				return;
			}
			if(o.category_id > 1 && !send.org_name) {
				dialog.err('�� ������� �������� �����������');
				$('#org_name').focus();
				return;
			}

			if(CLIENT_FROM_REQUIRE && !o.id && !send.from_id && !send.from_name) {
				dialog.err('�� ������ ��������, ������ ������ ������');
				return;
			}

			if(!send.join)
				send.client2 = 0;
			if(send.join && !send.client2) {
				dialog.err('������� ������� �������');
				return;
			}
			if(send.join && send.client2 == CI.id) {
				dialog.err('�������� ������� �������');
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
						document.location.href = URL + '&p=client&d=info&id=' + res.uid;
				} else
					dialog.abort().err(res.text);
			}, 'json');
		}
	},

	clientPersonEdit = function(o) {
		o = $.extend({
			id:0,
			fio:'',
			phone:'',
			adres:'',
			post:'',
			pasp_seria:'',
			pasp_nomer:'',
			pasp_adres:'',
			pasp_ovd:'',
			pasp_data:''
		}, o);
		var html = '<div id="client-add-tab">' + clientPeopleTab(o, 1) + '</div>',
			dialog = _dialog({
				top:80,
				width:400,
				head:o.id ? '�������������� ����������� ����' : '�o��� ���������� ����',
				content:html,
				butSubmit:o.id ? '���������' : '��������',
				submit:submit
			});
		$('#person-fio').focus();
		$('#person-adres').autosize();

		function submit() {
			var send = {
				op:'client_person_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				client_id:CI.id,
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
			if(!send.fio) {
				dialog.err('�� ������� ���');
				$('#person-fio').focus();
				return;
			}

			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#person-spisok').html(res.html);
					CI.person = res.array;
					dialog.close();
					_msg();
				} else
					dialog.abort();
			}, 'json');
		}
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
			type_id:v,
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
					'<tr><td class="label">��������:' +
						'<td><input type="text" id="name" class="w250" value="' + o.name + '" />' +
				'</table>',
			dialog = _dialog({
				width:370,
				head:(o.id ? '��������������' : '���������� ������' ) + ' ���������',
				content:html,
				butSubmit:o.id ? '���������' : '������',
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
				dialog.err('�� ������� ��������');
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
		not_client_id:0,    // ��������� ������� � ������ id
		category_id:0,      // ���������� ������ ������ ���������
		func:function() {}
	}, o);

	if(o.add)
		o.add = function() {
			clientEdit({
				callback:function(res) {
					o.client_id = res.uid;
					clientsGet();
				}
			});
		};

	t._select({
		width:o.width,
		title0:'������� ������� ������ �������...',
		spisok:[],
		write:1,
		nofind:'�������� �� �������',
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

					//todo ����������
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
	.on('click', '#client #filter_clear', function() {
		$('#find')._search('clear');    CLIENT.find = '';
		$('#category_id')._radio(0);    CLIENT.category_id = 0;
		$('#dolg')._check(0);           CLIENT.dolg = 0;
		$('#opl')._check(0);            CLIENT.opl = 0;
		$('#worker')._check(0);         CLIENT.worker = 0;
		$('#remind')._select(0);        CLIENT.remind = 0;
		_clientSpisok();
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
	.on('click', '.client-pasp-show', function() {//����� ����� ��� ���������� ���������� ������
		var p = $(this).parent().parent();
		p.parent().find('.dn').removeClass('dn');
		p.parent().find('.focus').focus();
		p.remove();
	})

	.on('click', '.client-info-go', function(e) {
		e.stopPropagation();
		location.href = URL + '&p=client&d=info&id=' + $(this).attr('val');
	})

	.on('click', '#client-info #person-add', clientPersonEdit)
	.on('click', '#client-info .person-edit', function() {
		var id = $(this).attr('val');
		clientPersonEdit(CI.person[id]);
	})
	.on('click', '#client-info .person-poa', function() {//�������� ������������
		var id = $(this).attr('val'),
			person = CI.person[id],
			html = '<table class="_dialog-tab">' +
					'<tr><td class="label w125">�����������:<td><b>' + CI.name + '</b>' +
					'<tr><td class="label">���������� ����:<td>' + person.fio +
					'<tr><td class="label">����� ������������:<td><input type="text" id="nomer" class="money" value="' + person.poa_nomer + '" />' +
					'<tr><td class="label">���� ������:<td><input type="hidden" id="date_begin" value="' + person.poa_date_begin + '" />' +
					'<tr><td class="label">���� ���������:<td><input type="hidden" id="date_end" value="' + person.poa_date_end + '" />' +
					'<tr><td class="label">����:<td><input type="hidden" id="attach_id-add" value="' + person.poa_attach_id + '" />' +
					'<tr><td><td>' +
					'<tr><td><td><input type="hidden" id="poa-del" />' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:400,
				head:'���������� � ������������',
				content:html,
				butSubmit:'���������',
				submit:submit
			});

		$('#nomer').focus();
		$('#date_begin')._calendar({lost:1});
		$('#date_end')._calendar({lost:1});
		$('#attach_id-add')._attach();
		if(person.poa_nomer)
			$('#poa-del')._check({
				light:1,
				name:'������� ������������',
				func:function(v) {
					dialog.butSubmit(v ? '������� ������������' : '���������');
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
				dialog.err('�� ������ ����� ������������');
				$('#nomer').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#person-spisok').html(res.html);
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
			head:'����������� ����',
			op:'client_person_del',
			func:function(res) {
				$('#person-spisok').html(res.html);
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
			head:'���������, ������ ������ ������',
			op:'client_from_del',
			func:function() {
				p.remove();
			}
		});
	})

	.ready(function() {
		$('#client-dolg-sum').vkHint({
			msg:'������� ����� ����� ��������',
			ugol:'top',
			width:190,
			top:16,
			left:404,
			indent:'right'
		});

		if($('#client').length) {
			$('#find')._search({
				width:458,
				focus:1,
				enter:1,
				txt:'������� ����� � ������� Enter',
				func:_clientSpisok
			}).inp(CLIENT.find);
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
				msg:'<b>������ ���������.</b><br /><br />' +
					'��������� �������, � ������� ������ ����� 0. ����� � ���������� ������������ ����� ����� �����.',
				ugol:'right',
				width:150,
				top:-25,
				left:-183,
				indent:20,
				delayShow:1000
			});
			$('#remind')._select({
				width: 140,
				title0: '�� �����',
				spisok: [
					{uid:1,title:'����'},
					{uid:2,title:'���'}
				],
				func: _clientSpisok
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
					client_id:CI.id,
					client:CI.name
				});
			});
			$('#client-edit').click(clientEdit);
			$('#client-del').click(function() {
				_dialogDel({
					id:CI.id,
					head:'�������',
					op:'client_del',
					func:function() {
						location.href = URL + '&p=client';
					}
				});
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
