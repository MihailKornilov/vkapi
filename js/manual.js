$(document)
	.on('click', '#manual #part-add', function() {
		var html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">��������:<td><input type="text" id="name" />' +
				'</table>',
			dialog = _dialog({
				head:'�������� ������ �������',
				content:html,
				submit:submit
			});

		$('#name').focus().keyEnter(submit);

		function submit() {
			var send = {
				op:'manual_part_add',
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
					$('#part-spisok').html(res.html);
				} else
					dialog.abort();
			}, 'json');
		}
	})
	.on('click', '#manual #part-sub-add', function() {//�������� ������ ����������
		var t = $(this),
			html =
				'<table class="_dialog-tab">' +
					'<tr><td class="label">������:<td><input type="hidden" id="part_id" />' +
					'<tr><td class="label">���������:<td><input type="text" id="name" />' +
				'</table>',
			dialog = _dialog({
				head:'�������� ������ ����������',
				content:html,
				submit:submit
			});

		$('#part_id')._select({
			width:218,
			title0:'������ �� ������',
			spisok:MANUAL_PART_SPISOK
		});
		$('#name').focus().keyEnter(submit);

		function submit() {
			var send = {
				op:'manual_part_sub_add',
				id:$('#part_id').val(),
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
					$('#part-spisok').html(res.html);
				} else
					dialog.abort();
			}, 'json');
		}
	})
	.on('click', '#manual #page-add', function() {//�������� ����� ��������
		var t = $(this),
			html =
				'<table id="manual-page-add" class="_dialog-tab">' +
					'<tr><td class="label">������:<td><input type="hidden" id="part_id" />' +
					'<tr><td class="label">���������:<td><input type="hidden" id="part_sub_id" />' +
					'<tr><td class="label">��������:<td><input type="text" id="name" />' +
					'<tr><td class="label">����������:<td>' +
					'<tr><td colspan="2"><textarea id="content"></textarea>' +
				'</table>',
			dialog = _dialog({
				top:20,
				width:546,
				head:'�������� ����� �������� �������',
				content:html,
				submit:submit
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
		$('#name').focus().keyEnter(submit);
		$('#content').autosize();

		function submit() {
			var send = {
				op:'manual_page_add',
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
					$('#part-spisok').html(res.html);
				} else
					dialog.abort();
			}, 'json');
		}
	});

