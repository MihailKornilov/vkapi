var saMenuEdit = function(o) {
		o = $.extend({
			tp:'main',
			id:0,
			name:'',
			about:'',
			p:''
		}, o);

		var html =
				'<table class="sa-tab" id="sa-menu-tab">' +
					'<tr><td class="label">��������:<td><input type="text" id="name" value="' + o.name + '" />' +
					'<tr><td class="label topi">��������:<td><textarea id="about">' + o.about + '</textarea>' +
					'<tr><td class="label">p:<td><input type="text" id="p" value="' + o.p + '" />' +
				'</table>',
			dialog = _dialog({
				head:(o.id ? '��������������' : '�������� ������') + ' ������� ����',
				content:html,
				butSubmit:o.id ? '���������' : '������',
				submit:submit
			});

		$('#name').focus();
		$('#about').autosize();

		function submit() {
			var send = {
				op:'sa_menu_' + (o.id ? 'edit' : 'add'),
				type:o.tp,
				id:o.id,
				name:$('#name').val(),
				about:$('#about').val(),
				p:$('#p').val()
			};
			if(!send.name) {
				dialog.err('�� ������� ��������');
				$('#name').focus();
				return;
			}
			if(!send.p) {
				dialog.err('�� ������ link');
				$('#p').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					$('#spisok').html(res.main);
					$('#setup-spisok').html(res.setup);
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
					'<tr><td class="label">��������:<td><input type="text" id="name" value="' + arr.name + '" />' +
				'</table>',
			dialog = _dialog({
				head:(arr.id ? '��������������' : '�������� �����' ) + ' ��������� ��������',
				content:html,
				butSubmit:arr.id ? '���������' : '������',
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
				dialog.err('�� ������� ��������');
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
					'<tr><td class="label">���������:<td><b>' + arr.category + '</b>' +
					'<tr><td class="label">��������:<td><input type="text" id="name" value="' + arr.name + '" />' +
					'<tr><td class="label">�����:<td><input type="hidden" id="minus" value="' + arr.minus + '" />' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:(arr.id ? '��������������' : '�������� ������' ) + ' ��������',
				content:html,
				butSubmit:arr.id ? '���������' : '������',
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
				dialog.err('�� ������� ��������');
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

	saClientPoleEdit = function(o) {
		o = $.extend({
			id:0,
			name:'',
			about:''
		}, o);

		var html =
				'<table class="bs10">' +
					'<tr><td class="label">��� ����:<td><b>' + SA_CLIENT_POLE_TYPE_NAME + '</b>' +
					'<tr><td class="label">��������:<td><input type="text" id="name" class="w250" value="' + o.name + '" />' +
					'<tr><td class="label top">��������:<td><textarea id="about" class="w250">' + o.about + '</textarea>' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:(o.id ? '���������' : '����������') + ' ���� �������',
				content:html,
				butSubmit:o.id ? '���������' : '������',
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
	saClientCategoryEdit = function(o) {//����������/�������������� �������� ���� ������������
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
				head:(o.id ? '���������' : '�������� �����') + ' ��������� �������',
				content:html,
				butSubmit:o.id ? '���������' : '������',
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
				dialog.err('�� ������� ��������');
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
				head:'���������� ���� ������� - �����',
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
					'<tr><td class="label w150">�������� ��������:<td><b>' + o.name + '</b>' +
		 (o.about ? '<tr><td class="label topi">��������:<td><div class="_info">' + o.about + '</div>' : '') +
					'<tr><td class="label topi">�������������� ��������:' +
						'<td><textarea id="label" class="w250">' + o.label + '</textarea>' +
					'<tr' + (o.type_id == 1 ? '' : ' class="dn"') + '>' +
						'<td class="label">������������ ����������:' +
						'<td><input type="hidden" id="require" value="' + o.require + '" />' +
				'</table>',
			dialog = _dialog({
				width:490,
				head:(o.id ? '���������' : '����������') + ' ���� �������',
				content:html,
				butSubmit:o.id ? '���������' : '������',
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

	saServiceEdit = function(o) {//����������/�������������� �������� ���� ������������
		o = $.extend({
			id:0,
			name:''
		}, o);

		var html =
	   (!o.id ? '<div class="_info">���� ��� ������ ��� ������, �� ��� ������� ������ ����� �������� � ���� ���.</div>' : '') +
				'<table class="bs10">' +
					'<tr><td class="label">��������:' +
						'<td><input type="text" id="name" class="w250" value="' + o.name + '" />' +
				'</table>',
			dialog = _dialog({
				head:(o.id ? '���������' : '�������� ������') + ' ���� ������',
				content:html,
				butSubmit:o.id ? '���������' : '������',
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
				dialog.err('�� ������� ��������');
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
			param1:'',
			param2:''
		}, o);

		var html =
				'<table class="sa-tab">' +
					'<tr><td class="label">��� ����:<td><b>' + SAZP_TYPE_NAME + '</b>' +
					'<tr><td class="label">��������:<td><input type="text" id="name" value="' + o.name + '" />' +
					'<tr><td class="label top">��������:<td><textarea id="about">' + o.about + '</textarea>' +
					'<tr><td class="label">�������� 1:<td><input type="text" id="param1" class="w250" placeholder="�������� ���������" value="' + o.param1 + '" />' +
					'<tr><td class="label">�������� 2:<td><input type="text" id="param2" class="w250" placeholder="�������� ���������" value="' + o.param2 + '" />' +
				'</table>',
			dialog = _dialog({
				width:400,
				head:(o.id ? '���������' : '����������') + ' ���� ������',
				content:html,
				butSubmit:o.id ? '���������' : '������',
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
				head:'���������� ���� ������ - �����',
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
				'<table class="sa-tab">' +
					'<tr><td class="label w150">�������� ��������:<td><b>' + o.name + '</b>' +
		 (o.about ? '<tr><td class="label topi">��������:<td><div class="_info">' + o.about + '</div>' : '') +
					'<tr><td class="label topi">�������������� ��������:' +
						'<td><textarea id="label" class="w250">' + o.label + '</textarea>' +
					'<tr' + (o.type_id == 1 ? '' : ' class="dn"') + '>' +
						'<td class="label">������������ ����������:' +
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
				head:(o.id ? '���������' : '����������') + ' ���� ������',
				content:html,
				butSubmit:o.id ? '���������' : '������',
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
				'<table class="sa-tab">' +
					'<tr><td class="label">�������� ��������:<td><input type="text" id="short" class="w50" value="' + o.short + '" />' +
					'<tr><td class="label">������ ��������:<td><input type="text" id="name" value="' + o.name + '" />' +
					'<tr><td class="label top">��������:<td><textarea id="about">' + o.about + '</textarea>' +
					'<tr><td class="label">�����:<td><input type="hidden" id="fraction" value="' + o.fraction + '" />' +
					'<tr><td class="label">�������:<td><input type="hidden" id="area" value="' + o.area + '" />' +
				'</table>',
			dialog = _dialog({
				width:420,
				head:(o.id ? '���������' : '����������') + ' ������� ���������',
				content:html,
				butSubmit:o.id ? '���������' : '������',
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

	saZayavBalans = function(id) {//������ ������� ��������� ������
		var dialog = _dialog({
			top:20,
			width:500,
			head:'���������� �������� ������',
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
	saZayavBalansRepair = function(id) {//������ ������� ��������� ������
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
	saZayavBalansRepairAll = function(ids) {//������ ������� ���������� ������
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
				'<table class="sa-tab">' +
					'<tr><td class="label">�������:<td><input id="predlog" type="text" value="' + o.predlog + '" />' +
					'<tr><td class="label">����:<td><input id="name" type="text" value="' + o.name + '" />' +
				'</table>',
			dialog = _dialog({
				head:(o.id ? '���������' : '���������� ������') + ' �����',
				content:html,
				butSubmit:o.id ? '���������' : '������',
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
				dialog.err('�� ������ �������');
				$('#predlog').focus();
				return;
			}
			if(!send.name) {
				dialog.err('�� ������ ����');
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
					'<tr><td class="label">��������:<td><input type="text" id="app_name" class="w230" value="' + o.app_name + '" />' +
					'<tr><td class="label">secret:<td><input type="text" id="secret" class="w230" value="' + o.secret + '" />' +
				'</table>',
			dialog = _dialog({
				head:(o.id ? '���������' : '���������� ������') + ' ����������',
				content:html,
				butSubmit:o.id ? '���������' : '������',
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
				dialog.err('�� ���������� app_id');
				$('#app_id').focus();
				return;
			}
			if(!send.title) {
				dialog.err('�� ������ title');
				$('#title').focus();
				return;
			}
			if(!send.app_name) {
				dialog.err('�� ������� ��������');
				$('#app_name').focus();
				return;
			}
			if(!send.secret) {
				dialog.err('�� ������ secret');
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
	.on('click', '#sa-menu .img_edit', function() {
		var t = _parent($(this), 'TR');
		saMenuEdit({
			id:t.find('.name').attr('val'),
			name:t.find('.name span').html(),
			about:t.find('.about').html(),
			p:t.find('.p').html()
		});
	})
	.on('click', '#sa-menu .show ._check', function() {//�������-����� �������� ����
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
				$('#setup-spisok').html(res.setup);
				sortable();
			}
		}, 'json');
	})
	.on('click', '#sa-menu .access ._check', function() {//������ ��� ������������� �� ���������
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

	.on('click', '#sa-history .img_edit', function() {
		var id = _num($(this).attr('val')),
			html =
				'<table class="sa-tab" id="sa-history-tab">' +
					'<tr><td class="label">type_id:<td><input type="text" id="type_id" value="' + id + '" />' +
					'<tr><td><td>���� ���������� <b>type_id</b>, ��� ������ �� ���������� ��������� ����� �������� �� �����.' +
					'<tr><td class="label topi">�����:<td><textarea id="txt">' + $('#txt' + id).val() + '</textarea>' +
					'<tr><td class="label">���������:<td><input type="hidden" id="category_ids" value="' + $('#ids' + id).val() + '" />' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:520,
				head:'�������������� ��������� ������� ��������',
				content:html,
				butSubmit:'��������',
				submit:submit
			});

		$('#txt').focus().autosize();
		$('#category_ids')._select({
			width:250,
			title0:'�� �������',
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
				dialog.err('����������� ������ type_id');
				$('#type_id').focus();
			} else if(!send.txt) {
				dialog.err('�� ������ ����� ���������');
				$('#txt').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('��������');
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
				head:'�������������� ��������� ������� ��������',
				content:html,
				butSubmit:'��������',
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
				dialog.err('�� ������� ������������');
				$('#name').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('��������');
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
					'<tr><td class="label">���������:<td><input type="text" id="key" value="' + t.find('.key b').html() + '" />' +
					'<tr><td class="label topi">��������:<td><textarea id="about">' + t.find('.about').html() + '</textarea>' +
				'</table>',
			dialog = _dialog({
				top:30,
				width:380,
				head:'�������������� ��������� ���� �����������',
				content:html,
				butSubmit:'��������',
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
				dialog.err('�� ������ ����� ���������');
				$('#key').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('��������');
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
			head:'��������� ��������',
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
			head:'�������� ��� ��������',
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
			head:'����',
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
			head:'����',
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
			head:'����',
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
			head:'����',
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
			head:'������� ���������',
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
			head:'�����',
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
			head:'���������� �������� ��������',
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
			head:'������������ ������� ������',
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
				t.html('��������').removeClass('_busy');
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
							'<tr><td class="label">type_id:<td><input type="text" id="type_id" value="' + TYPE_ID_MAX + '" />' +
							'<tr><td class="label topi">�����:<td><textarea id="txt"></textarea>' +
							'<tr><td class="label">���������:<td><input type="hidden" id="category_ids" />' +
						'</table>',
					dialog = _dialog({
						top:30,
						width:520,
						head:'�������� ����� ��������� ��� ������� ��������',
						content:html,
						submit:submit
					});

				$('#txt').focus().autosize();
				$('#category_ids')._select({
					width:250,
					title0:'�� �������',
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
						dialog.err('�� ������ ����� ���������');
						$('#txt').focus();
					} else {
						dialog.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								dialog.close();
								_msg('�������');
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
						head:'�������� ����� ��������� ������� ��������',
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
						dialog.err('�� ������� ������������');
						$('#name').focus();
					} else {
						dialog.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								dialog.close();
								_msg('�������');
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
							'<tr><td class="label">���������:<td><input type="text" id="key" />' +
							'<tr><td class="label topi">��������:<td><textarea id="about"></textarea>' +
						'</table>',
					dialog = _dialog({
						top:30,
						width:380,
						head:'�������� ����� ��������� ���� �����������',
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
						dialog.err('�� ������ ����� ���������');
						$('#key').focus();
					} else {
						dialog.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								dialog.close();
								_msg('�������');
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
