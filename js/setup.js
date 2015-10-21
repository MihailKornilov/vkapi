var setupRuleCheck = function(v, id) {
	var send = {
		op:id,
		viewer_id:RULE_VIEWER_ID,
		v:v
	};
	$.post(AJAX_MAIN, send, function(res) {
		if(res.success)
			_msg('���������');
	}, 'json');
};

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
		if($('#setup_rule').length) {
			$('#w-save').click(function() {
				var send = {
						op:'setup_worker_save',
						viewer_id:RULE_VIEWER_ID,
						first_name:$('#first_name').val(),
						last_name:$('#last_name').val(),
						middle_name:$('#middle_name').val(),
						post:$('#post').val()
					},
					but = $(this);
				if(!send.first_name) {
					err('�� ������� ���');
					$('#first_name').focus();
				} else if(!send.last_name) {
					err('�� ������� �������');
					$('#last_name').focus();
				} else {
					but.addClass('busy');
					$.post(AJAX_MAIN, send, function(res) {
						but.removeClass('busy');
						if(res.success)
							_msg('���������');
					}, 'json');
				}
				function err(msg) {
					but.vkHint({
						msg:'<SPAN class="red">' + msg + '</SPAN>',
						top:-57,
						left:-6,
						indent:40,
						show:1,
						remove:1
					});
				}
			});
			$('#RULE_APP_ENTER')._check(function(v, id) {
				$('#div-app-enter')[(v ? 'add' : 'remove') + 'Class']('dn');
				setupRuleCheck(v, id);
			});
			$('#RULE_SETUP_WORKER')._check(function(v, id) {
				$('#div-w-rule')[v ? 'show' : 'hide']();
				setupRuleCheck(v, id);
				$('#RULE_SETUP_RULES')._check(0);
			});
			$('#RULE_SETUP_RULES')._check(setupRuleCheck);
			$('#RULE_SETUP_REKVISIT')._check(setupRuleCheck);
			$('#RULE_SETUP_INVOICE')._check(setupRuleCheck);
			$('#RULE_HISTORY_VIEW')._check(setupRuleCheck);
			$('#RULE_INVOICE_TRANSFER')._check(setupRuleCheck);
			$('#RULE_INCOME_VIEW')._check(setupRuleCheck);
			$('#pin-clear').click(function() {
				var send = {
						op:'setup_worker_pin_clear',
						viewer_id:RULE_VIEWER_ID
					},
					but = $(this);
				if(but.hasClass('busy'))
					return;
				but.addClass('busy');
				$.post(AJAX_MAIN, send, function(res) {
					but.removeClass('busy');
					if(res.success) {
						_msg('���-��� �������');
						but.prev().remove();
						but.remove();
					}
				}, 'json');
			});
		}
	});
