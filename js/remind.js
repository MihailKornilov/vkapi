var _remindAdd = function() {
		var html =
				'<table class="_remind-add-tab">' +
					 (window.ZAYAV ? '<tr><td class="label">������:<td><b>' + ZAYAV.name + '</b>' : '') +
					(window.CLIENT ? '<tr><td class="label">������:<td>' + CLIENT.fio : '') +
					'<tr><td class="label">������:<td><input type="text" id="txt" />' +
					'<tr><td class="label top">��������:<td><textarea id="about"></textarea>' +
					'<tr><td class="label">���� ����������:<td><input type="hidden" id="day" />' +
				'</table>' +
				'<input type="hidden" id="client_id" value="' + (window.CLIENT ? CLIENT.id : 0) + '">' +
				'<input type="hidden" id="zayav_id" value="' + (window.ZAYAV ? ZAYAV.id : 0) + '">',
			dialog = _dialog({
				top:40,
				width:480,
				head:'�������� ������ �����������',
				content:html,
				submit:submit
			});

		$('#txt').focus();
		$('#about').autosize();
		$('#day')._calendar();

		function submit() {
			var send = {
				op:'remind_add',
				from:window.ZAYAV ? 'zayav' : (window.CLIENT ? 'client' : ''),
				client_id:$('#client_id').val(),
				zayav_id:$('#zayav_id').val(),
				txt:$.trim($('#txt').val()),
				about:$('#about').val(),
				day:$('#day').val()
			};
			if(!send.txt) {
				dialog.err('�� ������� ���������� ������');
				$('#txt').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('����������� �������');
						switch(send.from) {
							case 'zayav':
								$('#_remind-zayav').html(res.html);
								REMIND.active++;
								break;
							case 'client':
								$('#remind-spisok').html(res.html);
								break;
							default: $('td.left').html(res.html);
						}

					} else
						dialog.abort();
				}, 'json');
			}
		}
		return false;
	},
	remindFilter = function(v, id) {
		return {
			op:'remind_spisok',
			status:id == '_remind-status' ? $('#_remind-status').val() : $('#remind_filter_status').val()
		};
	},
	remindSpisok = function(v, id) {
		$('#mainLinks').addClass('busy');
		$.post(AJAX_MAIN, remindFilter(v, id), function(res) {
			$('#mainLinks').removeClass('busy');
			if(res.success)
				$('.left').html(res.html);
		}, 'json');
	};


$(document)
	.on('click', '._remind-add', _remindAdd)
	.on('click', '._remind-unit .action', function() {
		var t = $(this),
			p = t;
		while(!p.hasClass('_remind-unit'))
			p = p.parent();
		var id = p.attr('val'),
			head = p.find('.hdtxt').html(),
			day = p.find('.ruday').val(),
			html =
				'<div id="_remind-action-tab">' +
					'<div id="hd">' + head + '</div>' +
					'<div class="st c1" val="1">' +
						'������� ������ ����' +
						'<div class="about">��������� ����������� �� ������ ����.</div>' +
					'</div>' +
					'<div class="st c2" val="2">' +
						'���������' +
						'<div class="about">������� ��������� �������.</div>' +
					'</div>' +
					'<div class="st c0" val="0">' +
						'������' +
						'<div class="about">������ ����������� �� �����-���� �������.</div>' +
					'</div>' +
					'<table id="ra-tab">' +
						'<tr><td class="label">����� ����:<td><input type="hidden" id="remind_day" value="' + day + '" />' +
						'<tr><td class="label">�������:<td><input type="hidden" id="reason" />' +
					'</table>' +
				'</div>',
			dialog = _dialog({
				top:30,
				head:'��������� ������� �����������',
				content:html,
				butSubmit:'',
				butCancel:'�������',
				submit:function() {
					submit(1);
				}
			});
		$('#remind_day')._calendar();
		$('#reason')._select({
			width:225,
			spisok:[],
			write:true
		});

		// �������� ������ ������
		$('#reason')._select('process');
		$.post(AJAX_MAIN, {op:'remind_reason_spisok'}, function(res) {
			$('#reason')._select('cancel');
			if(res.success)
				$('#reason')._select(res.spisok);
		}, 'json');

		$('.st').click(function() {
			var	v = $(this).attr('val');
			if(v == 1) {
				$('.c2,.c0').hide();
				$('#ra-tab').show();
				dialog.butSubmit('���������');
			} else {
				$('#_remind-action-tab').addClass('busy');
				submit(v);
			}
		});
		function submit(status) {
			var send = {
				op:'remind_action',
				from:window.ZAYAV ? 'zayav' : (window.CLIENT ? 'client' : ''),
				id:id,
				status:status,
				day:$('#remind_day').val(),
				reason:$('#reason')._select('inp')
			};
			if(status == 1 && send.day == day) dialog.err('�������� ������ ����');
			else if(status == 1 && !send.reason) dialog.err('�� ������� �������');
			else
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						dialog.close();
						_msg('������ ��������!');
						$(send.from ? '#remind-spisok' : 'td.left').html(res.html);
					}
				}, 'json');
		}
	})
	.on('click', '._remind-unit .hd-edit', function() {
		var t = $(this),
			p = t;
		while(!p.hasClass('_remind-unit'))
			p = p.parent();
		var id = p.attr('val'),
			head = p.find('.hdtxt').html(),
			about = p.find('.hd-about').html().replace(/<br>/g, ''),
			html =
				'<table id="_remind-head-edit">' +
					'<tr><td class="label">������:<td><input type="text" id="hdtxt" value="' + head + '" />' +
					'<tr><td class="label top">��������:<td><textarea id="hd-about">' + about + '</textarea>' +
				'</table>',
			dialog = _dialog({
				width:420,
				head:'�������������� ���������� �����������',
				content:html,
				butSubmit:'���������',
				submit:submit
			});
		$('#hdtxt').focus().keyEnter(submit);
		$('#hd-about').autosize();
		function submit() {
			var send = {
				op:'remind_head_edit',
				from:window.ZAYAV ? 'zayav' : (window.CLIENT ? 'client' : ''),
				id:id,
				txt:$('#hdtxt').val(),
				about:$('#hd-about').val()
			};
			if(!send.txt) dialog.err('�� ������� ���������� ������');
			else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if (res.success) {
						dialog.close();
						_msg('������ ��������');
						$(send.from ? '#remind-spisok' : 'td.left').html(res.html);
					} else
						dialog.abort();
				}, 'json');
			}
		}
	})
	.on('click', '._remind-unit .ruhist', function() {
		$(this).parent().next().slideToggle(300);
	})
	.on('click', '#_remind-next', function() {
		var next = $(this),
			send = remindFilter();
			send.page = $(this).attr('val');
		if(next.hasClass('busy'))
			return;
		next.addClass('busy');
		$.post(AJAX_MAIN, send, function (res) {
			if(res.success)
				next.after(res.html).remove();
			else
				next.removeClass('busy');
		}, 'json');
	})
	.on('click', '#_remind-show-all', function() {
		$('._remind-unit').slideDown(200);
		$(this).remove();
	})

	.ready(function() {
		if($('#_remind-status').length)
			$('#_remind-status')._radio(remindSpisok);
	});
