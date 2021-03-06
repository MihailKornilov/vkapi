var _manualPartEdit = function(o) {
		o = $.extend({
			id:0,
			access:0,
			name:''
		}, o);
		var html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">��������:<td><input type="text" id="name" value="' + o.name + '" />' +
					'<tr><td><td><input type="hidden" id="access" value="' + o.access + '" />' +
				'</table>',
			dialog = _dialog({
				head:(o.id ? '��������������' : '�������� ������') + ' �������',
				content:html,
				butSubmit:o.id ? '���������' : '������',
				submit:submit
			});

		$('#access')._check({
			light:1,
			name:'������ �������� ��� ���������'
		});

		function submit() {
			var send = {
				op:'manual_part_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val(),
				access:$('#access').val()
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
					location.reload();
				} else
					dialog.abort();
			}, 'json');
		}
},
	_manualPageEdit = function(o) {//�������� ����� ��������
		o = $.extend({
			id:0,
			access:0,
			part_id:0,
			part_sub_id:0,
			name:'',
			content:''
		}, o);

		var t = $(this),
			paste = '',
			html =
				'<table id="manual-page-add" class="bs10">' +
					'<tr><td><td><input type="hidden" id="access" value="' + o.access + '" />' +
					'<tr><td class="label r">������:<td><input type="hidden" id="part_id" value="' + o.part_id + '" />' +
					'<tr><td class="label r">���������:<td><input type="hidden" id="part_sub_id" value="' + o.part_sub_id + '" />' +
					'<tr><td class="label r">��������:<td><input type="text" id="name" value="' + o.name + '" />' +
			(o.id ? '<tr><td class="label r top">�����������:<td id="img">' : '') +
					'<tr><td class="label r top">����������:' +
						'<td><a class="decor __info">&lt;div class="_info"></a> - �������������� ���� ������ �����<br />' +
							'<a class="decor __p">&lt;p></a> - �������� � ���������<br />' +
							'<a class="decor __b">&lt;b></a> - ������ �����<br />' +
							'<a class="decor __u">&lt;u></a> - �������������<br />' +
							'<a class="decor __ul">&lt;ul>&lt;li> &lt;/ul></a> - ������������� ������<br />' +
							'<a class="decor __h6">&lt;h6></a> - ����� � ����� �����<br />' +
					'<tr><td colspan="2"><textarea id="content">' + o.content + '</textarea>' +
				'</table>',
			dialog = _dialog({
				top:20,
				width:546,
				head:(o.id ? '��������������' : '�������� �����') + ' �������� �������',
				content:html,
				butSubmit:o.id ? '���������' : '������',
				submit:submit
			});

		$('#access')._check({
			light:1,
			name:'�������� �������� ��� ���������'
		});
		$('#part_id')._select({
			width:218,
			title0:'������ �� ������',
			spisok:MANUAL_PART_SPISOK,
			func:function(v) {
				$('#part_sub_id')
					._select(0)
					._select(MANUAL_PART_SUB_SPISOK[v] || []);
				$('#name').focus()
			}
		});
		$('#part_sub_id')._select({
			width:218,
			title0:'��������� �� ������',
			spisok:[],
			func:function() {
				$('#name').focus()
			}
		});
		$('#img')._image({
			unit_name:'manual',
			unit_id:o.id
		});

		$('#content').autosize();
		
		$('.__info').mouseover(function() { paste = '<div class="_info">' + window.getSelection() + '</div>'; });
		$('.__p').mouseover(function() {    paste = '<p>'; });
		$('.__b').mouseover(function() {    paste = '<b>' + window.getSelection() + '</b>'; });
		$('.__u').mouseover(function() {    paste = '<u>' + window.getSelection() + '</u>'; });
		$('.__ul').mouseover(function() {	paste = "\n<ul>\n<li> " + window.getSelection() + "\n<li> \n<li> \n</ul>\n"; });
		$('.__h6').mouseover(function() {   paste = "\n<h6>" + window.getSelection() + "</h6>\n"; });

		$('.decor').click(function() {
			$('#content').insertAtCaret(paste);
		});


		function submit() {
			var send = {
				op:'manual_page_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				access:$('#access').val(),
				part_id:_num($('#part_id').val()),
				part_sub_id:$('#part_sub_id').val(),
				name:$('#name').val(),
				content:$('#content').val()
			};
			if(!send.part_id) {
				dialog.err('�� ������ ������');
				return;
			}
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
					location.href = URL + '&p=57&page_id=' + res.id;
				} else
					dialog.abort();
			}, 'json');
		}
	};

$(document)
	.on('click', '#manual #part-add', _manualPartEdit)
	.on('click', '.manual-part-edit', function() {
		var o = $(this).attr('val').split('#');
		_manualPartEdit({
			id:o[0],
			name:o[1],
			access:o[2]
		});
	})

	.on('click', '#manual #part-sub-add', function() {//�������� ������ ����������
		var t = $(this),
			html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">������:<td><input type="hidden" id="s-part_id" />' +
					'<tr><td class="label">���������:<td><input type="text" id="name" />' +
				'</table>',
			dialog = _dialog({
				head:'�������� ������ ����������',
				content:html,
				submit:submit
			});

		$('#s-part_id')._select({
			width:218,
			title0:'������ �� ������',
			spisok:MANUAL_PART_SPISOK,
			func:function() {
				$('#name').focus()
			}
		});

		function submit() {
			var send = {
				op:'manual_part_sub_add',
				part_id:_num($('#s-part_id').val()),
				name:$('#name').val()
			};
			if(!send.part_id) {
				dialog.err('�� ������ ������');
				return;
			}
			if(!send.name) {
				dialog.err('�� ������� �������� ����������');
				$('#name').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					$('#part-spisok').html(res.html);
				} else
					dialog.abort();
			}, 'json');
		}
	})
	.on('click', '.manual-part-del', function() {
		var t = $(this);
		_dialogDel({
			id:t.attr('val'),
			head:'������� �������',
			op:'manual_part_del',
			func:function(res) {
				location.href = URL + '&p=10';
			}
		});
	})

	.on('click', '#manual #page-add', _manualPageEdit)
	.on('click', '.manual-page-edit', function() {
		var o = $(this).attr('val').split('#'),
			p = $('#manual-part');
		_manualPageEdit({
			id:o[0],
			access:o[1],
			part_id:o[2],
			part_sub_id:o[3],
			name:$('#mp-edit-name').val(),
			content:p.find('textarea').html()
		});
	})
	.on('click', '.manual-page-del', function() {
		var t = $(this);
		_dialogDel({
			id:t.attr('val'),
			head:'�������� �������',
			op:'manual_page_del',
			func:function(res) {
				location.href = URL + '&p=57&part_id=' + res.part_id;
			}
		});
	})

	.on('click', '#manual-part #page-but .vk', function() {//�������� ������ ����������
		var t = $(this),
			p = t.parent(),
			o = t.attr('val').split('#'),
			send = {
				op:'manual_answer',
				manual_id:o[0],
				val:o[1]
			};

		if(p.hasClass('busy'))
			return;

		p.addClass('busy');
		$.post(AJAX_MAIN, send, function(res) {
			p.removeClass('busy');
			if(res.success) {
				p.parent().addClass('answered');
				p.before(
					'<b id="answer-ok">������� �� ��� �����!</b>' +
					(send.val == 4 ? '<br />����������, ������� � ��������, ��� ������ ��� �� �������.' : '')
				);
			}
		}, 'json');
	});

