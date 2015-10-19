$(document)

	.ready(function() {
		if($('#setup_my').length) {
			$('#pinset').click(function() {
				var html =
						'<table id="setup-tab">' +
							'<tr><td class="label">����� ���-���:<td><input id="pin" type="password" maxlength="10" />' +
						'</table>',
					dialog = _dialog({
						width:300,
						head:'��������� ������ ���-����',
						content:html,
						butSubmit:'����������',
						submit:submit
					});
				$('#pin').focus().keyEnter(submit);
				function submit() {
					var send = {
						op:'setup_my_pinset',
						pin:$.trim($('#pin').val())
					};
					if(!send.pin) {
						dialog.err('������� ���-���');
						$('#pin').focus();
					} else if(send.pin.length < 3) {
						dialog.err('����� ���-���� �� 3 �� 10 ��������');
						$('#pin').focus();
					} else {
						dialog.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								dialog.close();
								_msg('���-��� ����������');
								document.location.reload();
							} else
								dialog.abort();
						}, 'json');
					}
				}
			});
			$('#pinchange').click(function() {
				var html = '<table id="setup-tab">' +
						'<tr><td class="label">������� ���-���:<td><input id="oldpin" type="password" maxlength="10" />' +
						'<tr><td class="label">����� ���-���:<td><input id="pin" type="password" maxlength="10" />' +
						'</table>',
					dialog = _dialog({
						width:300,
						head:'��������� ���-����',
						content:html,
						butSubmit:'��������',
						submit:submit
					});
				$('#oldpin').focus().keyEnter(submit);
				$('#pin').keyEnter(submit);
				function submit() {
					var send = {
						op:'setup_my_pinchange',
						oldpin: $.trim($('#oldpin').val()),
						pin: $.trim($('#pin').val())
					};
					if(!send.oldpin || !send.pin)
						dialog.err('��������� ��� ����');
					else if(send.oldpin.length < 3 || send.pin.length < 3)
						dialog.err('����� ���-���� �� 3 �� 10 ��������');
					else {
						dialog.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								dialog.close();
								_msg('���-��� ������.');
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
						'<table id="setup-tab">' +
							'<tr><td class="label">������� ���-���:<td><input id="oldpin" type="password" maxlength="10" />' +
						'</table>',
					dialog = _dialog({
						width:300,
						head:'�������� ���-����',
						content:html,
						butSubmit:'���������',
						submit:submit
					});
				$('#oldpin').focus().keyEnter(submit);
				function submit() {
					var send = {
						op:'setup_my_pindel',
						oldpin:$.trim($('#oldpin').val())
					};
					if(!send.oldpin) {
						dialog.err('���� �� ���������');
						$('#oldpin').focus();
					} else {
						dialog.process();
						$.post(AJAX_MAIN, send, function(res) {
							if(res.success) {
								dialog.close();
								_msg('���-��� �����');
								document.location.reload();
							} else {
								dialog.abort();
								dialog.err(res.text);
							}
						}, 'json');
					}
				}
			});
		}
	});
