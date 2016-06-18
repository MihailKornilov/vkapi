var _tovarAdd = function(o) {
		o = _tovarEditExtend(o);
		var html =
			'<div class="_info" id="info-main">' +
				'����������� ��������� ����! ' +
				'����� �������� ������ ����� ������� �������� ������. ' +
				'����, ���������� ���������, ����������� ��� ����������.<br />' +
//				'��� ����� ���������� ������������ � ��������� �������� ������ ������ ������� � �������.' +
			'</div>' +
			_tovarEditLabelCat(o) +

			'<div id="ta-name" class="dn">' +
				'<div class="_info">' +
					'<p>� ���� <b>������������</b> �������� �������� ������.' +
						'<br />' +
						'��� ����� ��� ��������������, ������� ������� ��������� �����, �� ���� �������� �� ������: <u>��� ���?</u>' +
						'<br />' +
						'�������: <u>�����</u>, <u>��������� �������</u>, <u>������</u>, <u>����������� ����</u> � ��.' +
						'<br />' +
						'���� � ���������� ������ ��� ������� ��������, ������� ���.' +
					'<p><b>�������������</b> ������ ��������� �� �����������.' +
					'<p>� ���� <b>��������</b> ������� ���������, ������, ������ ��� �������� �������� ������.' +
				'</div>' +
				_tovarEditLabelName(o) +
			'</div>' +

			'<div id="ta-set" class="dn">' + _tovarEditLabelSet(o) + '</div>' +
			'<div id="ta-dop" class="dn">' + _tovarEditLabelDop(o) + '</div>',

			dialog = _dialog({
				top:20,
				width:600,
				head:'�������� ������ ������',
				class:'tovar-add',
				content:html,
				butSubmit:''
			});

		dialog.content.find('#ta-set .headName').after(
			'<div class="_info">' +
				'�������, ����������� �� ���� ����� � ������� ������. ' +
				'�� ���� �������� ������������ ������.' +
				'<p><b>��������:</b>' +
				'<br />' +
				' - ��� <u>��������</u> (������� �� ��������);<br />' +
				' - ��� <u>�������������</u> (����� �� �����);<br />' +
				' - ��� <u>���������</u> (����� ��� ��������);<br />' +
				' - ��� <u>����������</u> (��� ��� �����). � ��.' +
			'</div>'
		);

		_tovarEditFunc(o, dialog, submit);

		function submit() {
			var send = _tovarEditValues(dialog);
			if(!send)
				return;
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					o.callback(res);
				} else
					dialog.abort();
			}, 'json');
		}
	},
	_tovarEditExtend = function(o) {
		return $.extend({
			id:0,
			category_id:0,
			name_id:0,
			vendor_id:0,
			name:'',
			set:-1,
			set_position_id:0,
			tovar_id_set:0,
			measure_id:1,
			cost_buy:'',
			cost_sell:'',
			about:'',
			feature:[],
			callback:function(res) {
				location.href = URL + '&p=tovar&d=info&id=' + res.id;
			}
		}, o);
	},
	_tovarEdit = function() {
		TI.set = TI.tovar_id_set ? 1 : 0;
		var o = _tovarEditExtend(TI),
			html =
			_tovarEditLabelCat(o) +
			_tovarEditLabelName(o) +
			_tovarEditLabelSet(o) +
			_tovarEditLabelDop(o),

			dialog = _dialog({
				top:20,
				width:600,
				head:'�������������� ������',
				class:'tovar-add',
				content:html,
				butSubmit:'���������',
				submit:submit
			});

		_tovarEditFunc(o, dialog);

		function submit() {
			var send = _tovarEditValues(dialog);
			if(!send)
				return;
			send.op = 'tovar_edit';
			send.id = TI.id;
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
	_tovarEditLabelCat = function(o) {
		return '<div class="headName">�������� ������ ������:</div>' +
			'<table class="bs5">' +
				'<tr><td class="label w70 r">���������:*' +
					'<td><input type="hidden" id="category_id-add" value="' + o.category_id + '" />' +
			'</table>';
	},
	_tovarEditLabelName = function(o) {
		return  '<table id="tab-name">' +
					'<tr><td class="label">������������:*' +
						'<td class="label td-vendor">' +
							'�������������:' +
							'<div id="no-vendor" class="img_del' + _tooltip('������������� ���', -61) + '</div>' +
						'<td class="label">��������:' +
					'<tr><td><input type="hidden" id="name_id-add" value="' + o.name_id + '" />' +
						'<td class="td-vendor"><input type="hidden" id="vendor_id-add" value="' + o.vendor_id + '" />' +
						'<td><input type="text" id="name" value="' + o.name + '" />' +
				'</table>';
	},
	_tovarEditLabelSet = function(o) {
		var dn = o.set == 1 ? '' : ' dn';
		return '<div class="headName">���������� � ������� ������:</div>' +
			'<table class="bs10" id="tab-set">' +
				'<tr><td class="label r">����������� � ������� ������:*' +
					'<td id="td-set"><input type="hidden" id="tovar_set-add" value="' + o.set + '" />' +
				'<tr class="tr-set' + dn + '"><td class="label r tdset2">��� ��������:*' +
					'<td><input type="hidden" id="set_position_id" value="' + o.set_position_id + '" />' +
				'<tr class="tr-set' + dn + '"><td class="label topi r tdset3">����������� � ������:*' +
					'<td><input type="hidden" id="te-tovar_id_set" value="' + o.tovar_id_set + '" />' +
			'</table>';
	},
	_tovarEditLabelDop = function(o) {
		return '<div class="headName" id="head-dop">�������������� ��������������:</div>' +
			'<table class="bs10" id="tab-dop">' +
				'<tr><td class="label r">������� ���������:*<td><input type="hidden" id="measure_id" value="' + o.measure_id + '" />' +
				'<tr><td class="label r">���������� ���������:<td><input type="text" class="money" id="cost_buy" value="' + o.cost_buy + '" /> ���.' +
				'<tr><td class="label r">���� �������:<td><input type="text" class="money" id="cost_sell" value="' + o.cost_sell + '" /> ���.' +
				'<tr><td class="label topi r">�������� ������:<td><textarea id="about">' + _br(o.about) + '</textarea>' +
			'</table>' +
			'<input type="hidden" id="feature" />';
	},
	_tovarEditFunc = function(o, dialog, submit) {
		$('#category_id-add')._select({
			width:300,
			title0:'��������� �� �������',
			spisok:TOVAR_CATEGORY_SPISOK,
			func:function(v) {
				if(_num($('#name_id-add').val()) || $.trim($('#name_id-add')._select('inp')))
					return;
				categoryLoad(v, 0);
				if(window.TI)
					return;
				if(!v)
					return;
				$('#info-main').slideUp();
				$('#ta-name').slideDown();
				dialog.butSubmit('�����');
				dialog.submit(step2);
			}
		});

		$('#name_id-add')._select({
			width:180,
			title0:'�� �������',
			spisok:[],
			write:1,
			write_save:1
		});
		if(o.category_id)
			categoryLoad(o.category_id, o.name_id);
		$('#vendor_id-add')._select({
			width:150,
			title0:'�� ������',
			spisok:TOVAR_VENDOR_SPISOK,
			write:1,
			write_save:1,
			func:function() {
				$('#name').focus();
			}
		});
		$('#no-vendor').click(function() {//������� �������������, ���� ���
			$('#vendor_id-add')._select(0);
			$('.td-vendor').hide();
			$('#name').width(370).focus();
		});

		$('#tovar_set-add')._radio({
			light:1,
			block:0,
			spisok:[
				{uid:1,title:'��'},
				{uid:0,title:'���'}
			],
			func:step3
		});
		$('#set_position_id')._select({
			title0:'�� �������',
			spisok:[
				{uid:1,title:'���������'},
				{uid:2,title:'�������������'},
				{uid:3,title:'�����������'},
				{uid:4,title:'������������'}
			]
		});
		$('#te-tovar_id_set').tovar({
			set:0
		});

		$('#measure_id')._select({
			width:170,
			spisok:[
				{uid:1,title:'��.',content:'��. - ����������'},
				{uid:2,title:'�.',content:'�. - ����� � ������'},
				{uid:3,title:'��.',content:'��. - ����� � �����������'}
			]
		});
		$('#about').autosize();

		$('#feature').tovarFeature({spisok:o.feature});

		function categoryLoad(v, name_id) {
			$('#name_id-add')._select(0);
			if(!v) {
				$('#name_id-add')._select([]);
				return;
			}
			var send = {
				op:'tovar_name_load',
				category_id:v
			};
			$('#name_id-add')._select('process');
			$.post(AJAX_MAIN, send, function(res) {
				$('#name_id-add')
					._select(res.success ? res.spisok : [])
					._select(name_id);
			}, 'json');
		}
		function step2() {//��� 2: ����� �������� ������ � �������������
			if(!_tovarEditValues(dialog))
				return;
			$('#ta-name ._info').slideUp();
			$('#ta-set').slideDown();
			dialog.butSubmit('');
		}
		function step3(v) {//��� 3: ���������� ������ � ������� ������
			$('.tr-set')[(v ? 'remove' : 'add') + 'Class']('dn');
			if(window.TI)
				return;
			$('#ta-set ._info').slideUp();
			$('#ta-dop').slideDown();
			dialog.butSubmit('������ ����� � �������');
			dialog.submit(submit);
		}
	},
	_tovarEditValues = function(dialog) {
		var send = {
			op:'tovar_add',
			category_id:_num($('#category_id-add').val()),
			name_id:_num($('#name_id-add').val()),
			name_name:$.trim($('#name_id-add')._select('inp')),
			vendor_id:_num($('#vendor_id-add').val()),
			vendor_name:$.trim($('#vendor_id-add')._select('inp')),
			name:$('#name').val(),

			set:_num($('#tovar_set-add').val()),
			set_position_id:_num($('#set_position_id').val()),
			tovar_id_set:_num($('#te-tovar_id_set').val().split(':')[0]),

			measure_id:_num($('#measure_id').val()),
			cost_buy:_cena($('#cost_buy').val()),
			cost_sell:_cena($('#cost_sell').val()),
			about:$('#about').val(),
			feature:$('#feature').tovarFeature('get')
		};
		if(!send.category_id) {
			dialog.err('�� ������� ���������');
			return false;
		}
		if(!send.name_id && !send.name_name) {
			dialog.err('�� ������� ������������ ������');
			$('#name_id-add')._select('focus');
			return false;
		}
		if(send.set) {
			if(!send.set_position_id) {
				dialog.err('������� ��������� ������');
				$('.tdset2').addClass('tderr');
				return false;
			}
			if(!send.tovar_id_set) {
				dialog.err('�� ������ �����');
				$('.tdset3').addClass('tderr');
				return false;
			}
		} else {
			send.set_position_id = 0;
			send.tovar_id_set = 0;
		}

		return send;
	},
	_tovarIcon = function(v) {//��������� ���� ����������� �������
		var icon = $('#_tovar #icon'),
			img = icon.find('.img');
		icon.find('.sel').removeClass('sel');
		for(var n = 0; n < img.length; n++) {
			var sp = img.eq(n),
				val = sp.attr('val');
			if(val == v) {
				sp.addClass('sel');
				TOVAR['icon_id'] = v;
				if(v == 2) {
					TOVAR['category_id'] = 0;
					$('#category_id')._select(0);
					TOVAR['name_id'] = 0;
					$('#name_id')._select(0);
					TOVAR['vendor_id'] = 0;
					$('#vendor_id')._select(0);
				}
				return;
			}
		}
	},
	_tovarSpisok = function(v, id) {
		_filterSpisok(TOVAR, v, id);

		if(id == 'category_id') {
			TOVAR['icon_id'] = v ? 4 : 2;
			_tovarIcon(v ? 4 : 2);
			TOVAR['name_id'] = 0;
			$('#name_id')._select(0);
			TOVAR['vendor_id'] = 0;
			$('#vendor_id')._select(0);
		}

		$('.div-cat')[(TOVAR['icon_id'] == 2 ? 'add' : 'remove') + 'Class']('dn');

		$.post(AJAX_MAIN, TOVAR, function(res) {
			if(res.success) {
				$('.result').html(res.result);
				$('#spisok').html(res.spisok);
				$('#name_id')._select(res.name_spisok);
				$('#name_id')._select(res.name_id);
				$('#vendor_id')._select(res.vendor_spisok);
			}
		}, 'json');
	};

$.fn.tovar = function(o) {
	var t = $(this),
//		attr_id = t.attr('id'),
		tovar_id = 0,
		ts_value = $.trim(t.val());
//		win = attr_id + '_tovarSelect';

	o = $.extend({
		open:0,         //������������� ��������� ���� ������ ������
		set:1,          //�������� ������, ������� �������� ��������� ��� ������ �������
		image:1,        //���������� � ���������� �����������
		tovar_id_set:0, //�� ��������� �������� ������ ���������, ������� ��������������� �� ���� �����
		several:0,      //����������� �������� ��������� �������
		count_show:1,   //����������� ��������� ���������� �������
		avai:0,         //����� ������ ������ �� �������
		del:1,          //����������� �������� ��������� �����
		func:function() {}
	}, o);

	//���� ��������� �������, �� �������� �� ������������
	if(o.several)
		o.image = 0;

	//���� ���� �����, �� ���������� �� �����������
	if(!o.several)
		o.count_show = 0;

	t.after('<div class="tovar-select">' +
				'<table class="_spisok">' +
					'<tr class="tr-but">' +
						'<td class="td-but" colspan="3"><button class="vk small">������� �����</button>' +
				'</table>' +
				'<div class="ts-avai dn">&nbsp;</div>' +
			'</div>');

	var ts = t.next(),
		trBut = ts.find('.tr-but'),
		but = ts.find('.vk'),
		tsDialog,   //������ ���� ������ ������
		tsAvai = ts.find('.ts-avai'),//���� ��� ������ ������� ������
		tsArr,      //������ ������ ��� ������ ����������� ������
		TSV = '';   //��������� ����� ������

	but.click(selOpen);

	tovarSelected();

	if(o.open)
		but.trigger('click');

	function tovarSelected() {//������� ������, ������� ��� ������ (��� ��������������)
		if(!ts_value)
			return;
		
		var send = {
			op:'tovar_selected',
			v:ts_value
		};
		but.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			but.removeClass('_busy');
			if(res.success) {
				tsArr = res.arr;
				for(var i in tsArr)
					tsSel(i);
			}
		}, 'json');

	}
	function selOpen() {//���� ������ ������
		if(but.hasClass('_busy'))
			return;

		var html =
			'<table id="tovar-select-tab" class="w100p">' +
				'<tr><td><div id="tovar-find"></div>' +
		 (!o.avai ? '<td class="r"><button class="vk">�������� ����� �����</button>' : '') +
			'</table>' +
			'<div id="tres"></div>';
		tsDialog = _dialog({
			top:40,
			width:500,
			head:'����� ������',
			content:html,
			butSubmit:'',
			butCancel:'�������'
		});

		$('#tovar-find')._search({
			width:300,
			focus:1,
			txt:'������� ���� ��� ������ ������...',
			v:TSV,
			func:selFind
		});
		$('#tovar-select-tab .vk').click(function() {
			_tovarAdd({
				callback:function(res) {
					tsArr = res.arr;
					tsSel(res.id);
					tsDialog.close();
				}
			});
		});
		selFind(TSV);
	}
	function selFind(v) {
		var send = {
			op:'tovar_select',
			v:v,
			tovar_id:tovar_id,
			tovar_id_set:o.tovar_id_set,
			set:o.set,
			avai:o.avai,
			avai_radio:function() {}
		};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				$('#tres')
					.html(res.html)
					.find('.ts-unit').click(function() {
						var v = $(this).attr('val');
						tsSel(v);
						tsDialog.close();
					});
				tsArr = res.arr;
				TSV = v;
			}
		}, 'json');
	}
	function tsSel(v) {
		var sp = tsArr[v],
			html = '<tr>' +
						'<td class="ts-name">' +
							(o.image ? sp.image_small : '') +
							sp.name +
						'<td class="td-cnt' + (o.count_show ? '' : ' dn') + '">' +
							'<input type="text" val="' + v + '" value="' + (sp.count || 1) + '" />' +
			   (o.del ? '<td class="ed"><div class="img_del' + _tooltip('�������� �����', -93, 'r') + '</div>' : '');
		trBut.before(html);
		trBut.prev().find('.img_del').click(tsCancel);
		trBut.prev().find('input').select().keyup(valueUpdate);

		if(!o.several)
			trBut.hide();

		if(o.avai)
			avaiGet(v);

		valueUpdate();
		o.func(v);
	}
	function tsCancel() {
		_parent($(this)).remove();
		trBut.show();
		valueUpdate();
		o.func(0);
	}
	function valueUpdate() {//���������� ��������� �������� �������
		var inp = ts.find('input'),
			v = [];
		for(var n = 0; n < inp.length; n++) {
			var sp = inp.eq(n),
				id = _num(sp.attr('val')),
				val = _num(sp.val());
			sp.parent()[(val ? 'remove' : 'add') + 'Class']('err');
			if(!val)
				continue;
			v.push(id + ':' + val);
		}
		t.val(v.join());
	}
	function avaiGet(tovar_id) {//������� ������� � �������� ����� ������ ������
		if(tsAvai.hasClass('_busy'))
			return;

		tsAvai
			.html('&nbsp;')
			.removeClass('dn')
			.addClass('_busy');

		var send = {
			op:'tovar_select_avai',
			tovar_id:tovar_id
		};
		$.post(AJAX_MAIN, send, function(res) {
			tsAvai.removeClass('_busy');
			if(res.success) {
				tsAvai.html(res.html);
				tsAvai.find('#ta-articul')._radio(function(id) {
					o.avai_radio(res.arr[id]);
				});
			}
		}, 'json');
	}

	return t;
};
$.fn.tovarFeature = function(o) {//���������� ���������������� ��� �������� ������ ������ � ��������������
	var t = $(this),
		attr_id = t.attr('id'),
		win = attr_id + '_tovarFeature';

	switch(o) {
		case 'get':
			var spk = window[win].o.spisok_save,
				send = [];

			for(var n = 0; n < spk.length; n++) {
				var sp = spk[n],
					uid = _num($('#feature_' + sp.num).val()),
					inp = $.trim($('#feature_' + sp.num)._select('inp')),
					val = $.trim($('#feature_val_' + sp.num).val());
				if(!sp.on)
					continue;
				if(!uid && !inp || !val)
					continue;
				send.push([uid,inp,val]);
			}
			return send;
	}

	o = $.extend({
		spisok:[],
		spisok_save:[]//���������� ��������� ������ ������������� ��� ������ ��������������
	}, o);

	var num = 0;

	t.after('<table class="bs10" id="feature-tab"></table>' +
			'<div id="feature-add">�������� ��������������</div>');

	for(var n = 0; n < o.spisok.length; n++) {
		var sp = o.spisok[n];
		o.spisok_save.push({
			uid:sp.uid,
			title:sp.title,
			on:1,
			num:num
		});
		itemAdd(sp.uid, sp.title);
	}

	$('#feature-add').click(function() {
		o.spisok_save.push({
			num:num,
			on:1
		});
		itemAdd(0, '');
	});

	function itemAdd(uid, title) {
		var tr =
			'<tr><td><input type="hidden" id="feature_' + num + '" value="' + uid + '" />' +
				'<td><input type="text" class="feature_val" id="feature_val_' + num + '" value="' + title + '" />' +
					'<div val="' + num + '" class="img_del' + _tooltip('��������', -32) + '</div>';
		$('#feature-tab').append(tr);
		$('#feature-tab tr:last').find('.img_del').click(function() {
			_parent($(this)).remove();
			for(var n = 0; n < o.spisok_save.length; n++)
				if(o.spisok_save[n].num == num) {
					o.spisok_save[n].on = 0;
					break;
				}
		});
		$('#feature_' + num)._select({
			title0:'�������� ��������������',
			width:180,
			write:1,
			write_save:1,
			spisok:TOVAR_FEATURE_SPISOK,
			func:function() {
				$('#feature_val_' + num).focus();
			}
		});
		num++;
	}

	t.o = o;
	window[win] = t;
	return t;
};

$(document)
	.on('mouseover', '.tderr', function() {//������ ��������� ������
		$(this).removeClass('tderr');
	})

	.on('click', '.tovar-info-go', function(e) {
		e.stopPropagation();
		location.href = URL + '&p=tovar&d=info&id=' + $(this).attr('val');
	})

	.on('click', '#tovar-add', function() {
		_tovarAdd({
			category_id:_num($('#category_id').val()),
			name_id:_num($('#name_id').val()),
			set:-1
		});
	})

	.on('click', '#_tovar #icon .img', function() {//������������ ���� ������ �������
		var v = $(this).attr('val');
		_tovarIcon(v);
		_tovarSpisok(v, 'icon_id');
	})
	.on('click', '.tovar-category-unit .hd', function() {//�������� ��� ������� �� ��������� ������
		var v = $(this).parent().attr('val');
		$('#category_id')._select(v);
		_tovarSpisok(v, 'category_id');
		_tovarIcon(4);
	})
	.on('click', '.tovar-category-unit .sub-unit a', function() {//�������� ��� ������� �� �������� ������ � ��������� �������
		var t = $(this),
			cat_id = t.parent().parent().attr('val'),
			name_id = t.attr('val');

		$('#category_id')._select(cat_id);
		TOVAR['category_id'] = cat_id;
		_tovarIcon(4);
		_tovarSpisok(name_id, 'name_id');
	})

	.on('click', '.tovar-info-go', function() {
		var t = $(this),
			old = t.hasClass('old') ? 1 : 0;
		location.href = URL + '&p=tovar&d=info&id=' + t.attr('val') + '&old=' + old;
	})

	.on('click', '.tovar-avai-add', function() {
		var html =  '<table class="bs10">' +
						'<tr><td class="label r">����������:<td><input type="text" id="count" value="1" /> ��.' +
						'<tr><td class="label r">���� �� ��.:' +
							'<td><input type="text" id="cost_buy" class="money" value="' + TI.cost_buy + '"> ���.' +
						'<tr><td class="label r">�/�:<td><input type="hidden" id="bu" />' +
						'<tr><td class="label r">����������:<td><input type="text" id="about" />' +
					'</table>',
			dialog = _dialog({
				head:'�������� ������� ������',
				class:'tovar-avai-add',
				content:html,
				submit:submit
			});

		$('#count').focus();
		$('#bu')._check();

		function submit() {
			var send = {
				op:'tovar_avai_add',
				tovar_id:TI.id,
				count:_num($('#count').val()),
				cost_buy:_cena($('#cost_buy').val()),
				bu:_bool($('#bu').val()),
				about:$('#about').val()
			};
			if(!send.count) {
				dialog.err('����������� ������� ����������');
				$('#count').focus();
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
	})
	.on('click', '#tovar-sell', function() {//������� ������ �� ���������� � ������
		var dialog = _dialog({
				top:100,
				width:490,
				head:'������� ������',
				class:'tovar-sell',
				load:1,
				butSubmit:'',
				submit:submit
			}),
			send = {
				op:'tovar_sell_load',
				tovar_id:TI.id
			},
			avai_id = 0,
			max = 0;
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.content.html(res.html);
					if(!res.count)
						return;
					$('#invoice_id')._select({
						width:218,
						title0:'�� ������',
						spisok:_invoiceIncomeInsert()
					});
					$('#client_id').clientSel({width:300,add:1});
					$('#ta-articul')._radio(function(o) {
						avai_id = _num($('#ta-articul').val());
						max = res.arr[avai_id].count;
						$('#count').val(1).focus();
						$('#max b').html(max);
						sumCount();
						$('#count,#cena').keyup(sumCount);
						$('#ts-tab').removeClass('dn');
						dialog.butSubmit('���������');
					});
				} else
					dialog.loadError();
			},'json');

		function sumCount() {
			var count = _num($('#count').val()),
				cena = _cena($('#cena').val()),
				sum = _cena(count * cena);
			$('#summa').html(sum ? sum : '-');
		}
		function submit() {
			var send = {
				op:'tovar_sell',
				avai_id:avai_id,
				count:_num($('#count').val()),
				cena:_cena($('#cena').val()),
				invoice_id:_num($('#invoice_id').val()),
				client_id:_num($('#client_id').val())
			};

			if(!send.count) {
				dialog.err('����������� ������� ����������');
				$('#count').focus();
				return;
			}
			if(send.count > max) {
				dialog.err('������� ���������� ������ �����������');
				$('#count').focus();
				return;
			}
			if(!send.cena) {
				dialog.err('����������� ������� ����');
				$('#cena').focus();
				return;
			}
			if(!send.invoice_id) {
				dialog.err('�� ������ ��������� ����');
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
			},'json');
		}
	})

	.ready(function() {
		if($('#_tovar').length) {
			$('#find')._search({
				width:138,
				focus:1,
				txt:'������� �����...',
				enter:1,
				func:_tovarSpisok
			});
			$('#group')._radio(_tovarSpisok);
			$('#category_id')._select({
				width:140,
				title0:'�� �������',
				spisok:TOVAR_CATEGORY_SPISOK,
				func:_tovarSpisok
			});
			$('#name_id')._select({
				width:140,
				title0:'�� �������',
				spisok:[],
				func:_tovarSpisok
			});
			$('#vendor_id')._select({
				width:140,
				title0:'�� ������',
				spisok:[],
				func:_tovarSpisok
			});
		}
	});

