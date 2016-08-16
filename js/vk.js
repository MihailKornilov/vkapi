var VK_SCROLL = 0,
	COOKIE_PREFIX = APP_ID + '_' + VIEWER_ID + '_',
	ZINDEX = 0,
	BC = 0,
	FB, // frameBody
	FH, // frameHidden
	FB_HEIGHT = 0,
	DIALOG_MAXHEIGHT = 0,
	FOTO_HEIGHT = 0,
	REGEXP_NUMERIC =       /^\d+$/,
	REGEXP_NUMERIC_MINUS = /^-?\d+$/,
	REGEXP_CENA =          /^[\d]+(.[\d]{1,2})?(,[\d]{1,2})?$/,
	REGEXP_CENA_MINUS =    /^-?[\d]+(.[\d]{1,2})?(,[\d]{1,2})?$/,
	REGEXP_MS =            /^[\d]+(.[\d]{1,3})?(,[\d]{1,3})?$/,
	REGEXP_DATE = /^(\d{4})-(\d{1,2})-(\d{1,2})$/,
	MONTH_DEF = {
		1:'������',
		2:'�������',
		3:'����',
		4:'������',
		5:'���',
		6:'����',
		7:'����',
		8:'������',
		9:'��������',
		10:'�������',
		11:'������',
		12:'�������'
	},
	MONTH_DAT = {
		1:'������',
		2:'�������',
		3:'�����',
		4:'������',
		5:'���',
		6:'����',
		7:'����',
		8:'�������',
		9:'��������',
		10:'�������',
		11:'������',
		12:'�������'
	},
	WEEK_NAME = {
		0:'��',
		1:'��',
		2:'��',
		3:'��',
		4:'��',
		5:'��',
		6:'��',
		7:'��'
	},
	hashLoc,
	hashSet = function(hash) {
		if(!hash && !hash.p)
			return;
		hashLoc = hash.p;
		var s = true;
		switch(hash.p) {
			case 'client':
				if(hash.d == 'info')
					hashLoc += '_' + hash.id;
				break;
			case 'zayav':
				if(hash.d == 'info')
					hashLoc += '_' + hash.id;
				else if(hash.d == 'add')
					hashLoc += '_add' + (_num(hash.id) ? '_' + hash.id : '');
				else if(!hash.d)
					s = false;
				break;
			case 'zp':
				if(hash.d == 'info')
					hashLoc += '_' + hash.id;
				else
					s = false;
				break;
			default:
				if(hash.d) {
					hashLoc += '_' + hash.d;
					if(hash.d1)
						hashLoc += '_' + hash.d1;
				}
		}
		if(s)
			VK.callMethod('setLocation', hashLoc);
	},

	scannerWord = '',
	scannerTime = 0,
	scannerTimer,
	scannerDialog,
	scannerDialogShow = false,
	charSpisok = {
		48:'0',
		49:1,
		50:2,
		51:3,
		52:4,
		53:5,
		54:6,
		55:7,
		56:8,
		57:9,
		65:'A',
		66:'B',
		67:'C',
		68:'D',
		69:'E',
		70:'F',
		71:'G',
		72:'H',
		73:'I',
		74:'J',
		75:'K',
		76:'L',
		77:'M',
		78:'N',
		79:'O',
		80:'P',
		81:'Q',
		82:'R',
		83:'S',
		84:'T',
		85:'U',
		86:'V',
		87:'W',
		88:'X',
		89:'Y',
		90:'Z',
		189:'-'
	},

	_scroll = function(action, unit) {//���������� � ������� ������� ��� ���������� ��������, ���� ����������
		var p = _cookie('p'),
			d = _cookie('d');
		if(!p)
			return 0;
		var key = COOKIE_PREFIX + 'scroll_' + p + (d || ''),
			u = _cookie(key + '_unit');

		if(action == 'set') {
			_cookie(key, VK_SCROLL);
			if(unit)
				_cookie(key + '_unit', unit);
			return;
		}

		if(action == 'page') {
			_cookie(key + '_page', unit);
			return;
		}

		if(action == 'clear') {
			_cookie(key, 0);
			_cookie(key + '_page', 1);
			return;
		}

		//������������� �������������� ��������
		if(u)
			$('#' + u)
				.css('position', 'relative')
				.append('<div id="unit-select-show"></div>')
				.find('#unit-select-show')
				.animate({opacity:.9}, 200)
				.animate({opacity:0}, 900, function() {
					$('#unit-select-show').remove()
				});

		return _num(_cookie(key));
	},
	pinLoad = function(i) {
		$('#pin')
			.focus()
			.keydown(function() {
				$('.red').html('&nbsp;');
			})
			.keyup(function() {
				if($(this).val().length * 7 == i)
					pinEnter();
			})
			.keyEnter(pinEnter);
	},
	pinEnter = function() {
		var send = {
			op:'pin_enter',
			pin:$.trim($('#pin').val())
		};
		if(send.pin && send.pin.length > 2) {
			$('.vk').addClass('_busy');
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success)
					location.href = URL +
									'&p=' + _cookie('p') +
									'&d=' + _cookie('d') +
									'&id=' + _cookie('id');
				else {
					$('.vk').removeClass('_busy');
					$('#pin').val('').focus();
					$('.red').html(res.text);
				}
			}, 'json');
		}
	},
	pinConfirm = function(req) {//������������� ���-���� ��� ajax-�������
		if(!req.pin)
			return false;

		var html =
				'<table id="setup-tab">' +
					'<tr><td colspan="2"><div class="_info">������� ����� �������� ���-����. ��������� �������������.</div>' +
					'<tr><td class="label">���-���:<td><input id="tpin" type="password" maxlength="10" />' +
				'</table>',
			dialog = _dialog({
				width:250,
				head:'������������� ���-����',
				content:html,
				butSubmit:'�����������',
				butCancel:'',
				submit:submitPinConfirm
			});
		$('#tpin').focus();
		return true;

		function submitPinConfirm() {
			var send = {
				op:'pin_enter',
				pin:$.trim($('#tpin').val())
			};
			if(!send.pin) {
				dialog.err('�� ��������� ����');
				$('#tpin').focus();
			} else if(send.pin.length < 3) {
				dialog.err('����� ���-���� �� 3 �� 10 ��������');
				$('#tpin').focus();
			} else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success)
						dialog.close();
					else if(res.max)
						location.reload();
					else {
						dialog.abort();
						dialog.err(res.text);
						$('#tpin').val('').focus();
					}
				}, 'json');
			}
		}
	},
	debugHeight = function(s) {
		var h = $('#_debug').height();
		FOTO_HEIGHT = s || h < FBH - 30 ? 0 : h + 30;
		_fbhs();
	},
	_cookie = function(name, value) {
		if(value !== undefined) {
			var exdate = new Date();
			exdate.setDate(exdate.getDate() + 1);
			document.cookie = name + '=' + value + '; path=/; expires=' + exdate.toGMTString();
			return '';
		}
		var r = document.cookie.split('; ');
		for(var i = 0; i < r.length; i++) {
			var k = r[i].split('=');
			if(k[0] == name)
				return k[1];
		}
		return '';

	},
	sortable = function() {
		$('._sort').sortable({
			axis:'y',
			update:function () {
				if($(this).hasClass('no'))
					return true;
				var dds = $(this).find('dd'),
					arr = [];
				for(var n = 0; n < dds.length; n++) {
					var v = _num(dds.eq(n).attr('val'));
					if(v)
						arr.push(v);
				}
				var send = {
					op:'sort',
					table:$(this).attr('val'),
					ids:arr.join()
				};
				$.post(AJAX_MAIN, send, function() {}, 'json');
			}
		});
	},
	_toSpisok = function(s) {
		var a=[];
		for(k in s)
			a.push({uid:k,title:s[k]});
		return a
	},
	_toAss = function(s) {
		var a=[];
		for(var n = 0; n < s.length; n++)
			a[s[n].uid] = s[n].title;
		return a
	},
	_end = function(count, arr) {
		if(arr.length == 2)
			arr.push(arr[1]);
		var send = arr[2];
		if(Math.floor(count / 10 % 10) != 1)
			switch(count % 10) {
				case 1: send = arr[0]; break;
				case 2: send = arr[1]; break;
				case 3: send = arr[1]; break;
				case 4: send = arr[1]; break;
			}
		return send;
	},
	_bool = function(v) {
		return v == 1 ? 1 : 0;
	},
	_num = function(v, minus) {
		var val = minus ? REGEXP_NUMERIC_MINUS.test(v) : REGEXP_NUMERIC.test(v);
		return val ? v * 1 : 0;
	},
	_cena = function(v, minus) {
		if(typeof v == 'string')
			v = v.replace(',', '.');
		if(v == 0)
			return 0;
		if(minus && REGEXP_CENA_MINUS.test(v))
			return v * 1;
		if(!REGEXP_CENA.test(v))
			return 0;
		return v * 1;
	},
	_ms = function(v) {//������� ��������� � ������� 0.000
		if(typeof v == 'string')
			v = v.replace(',', '.');
		if(v == 0)
			return 0;
		if(!REGEXP_MS.test(v))
			return 0;
		return v * 1;
	},
	_fbhs = function() {
		var h;
		if(FOTO_HEIGHT > 0) {
			h = FOTO_HEIGHT;
			FB.height(h);
		} else {
			if(DIALOG_MAXHEIGHT == 0)
				FB.height('auto');
			h = FB.height();
			if(h < DIALOG_MAXHEIGHT) {
				h = DIALOG_MAXHEIGHT;
				FB.height(h);
			}
		}
		if(FB_HEIGHT == h)
			return;
		FB_HEIGHT = h;
		VK.callMethod('resizeWindow', 625, h);
	},
	_backfon = function(add) {
		if(add === undefined)
			add = true;
		var body = $('body');
		if(add) {
			ZINDEX += 10;
			if(!BC) {
				body.find('._backfon').remove().end()
					.append('<div class="_backfon"></div>');
			}
			var backfon = body.find('._backfon');
			backfon.css({'z-index':ZINDEX});
			if(typeof add == 'object')
				backfon.click(function() {
					del();
					add.remove();
				});
			BC++;
		} else
			del();

		function del() {
			BC--;
			ZINDEX -= 10;
			var backfon = body.find('._backfon');
			if(!BC)
				backfon.remove();
			else
				backfon.css({'z-index':ZINDEX});
		}
	},
	_msg = function(txt, func) {//��������� � ���������� ����������� ��������
		if(!txt)
			txt = '���������';
		$('#_msg').remove();
		$('body').append('<div id="_msg">' + txt + '</div>');
		$('#_msg')
			.css('top', $(this).scrollTop() + 200 + VK_SCROLL)
			.delay(1200)
			.fadeOut(400, function() {
				$(this).remove();
				if(typeof func == 'function')
					func();
			});
	},
	_err = function(msg) {
		dialog.bottom.vkHint({
			msg:'<SPAN class="red">' + msg + '</SPAN>',
			top:-48,
			left:126,
			indent:40,
			show:1,
			remove:1
		});
	},
	_wait = function(v) {//�������� ���������� ��������
		$('#_wait').remove();
		if(v === false) {
			_backfon(false);
			return;
		}
		$('body').append('<div id="_wait" class="_busy"></div>');
		_backfon($('#_wait'));
		$('#_wait')
			.css('top', $(this).scrollTop() + 200 + VK_SCROLL);
	},
	_br = function(v) {
		return v.replace(new RegExp('<br />','g'), "\n");
	},
	_copySel = function(arr, id) {//����������� ������� ��� �������. ���� ������ id - ������������
		var send = [];
		for(var n = 0; n < arr.length; n++) {
			var sp = arr[n];
			if(sp.uid == id)
				continue;
			send.push(sp);
		}
		return send;
	},
	_dialog = function(o) {
		var t = $(this),
			id = t.attr('id');
		o = $.extend({
			width:360,
			top:100,
			mb:0,      //margin-bottom: ������ ����� �� ������� (��� ��������� ��� ���������� �������)
			padding:10,//������ ��� content
			head:'head: �������� ���������',
			load:0, // ����� �������� �������� �������� � ������ �������
			class:'',//�������������� ����� ��� content
			content:'content: ���������� ������������ ����',
			submit:function() {},
			cancel:function() {},
			butSubmit:'������',
			butCancel:'������'
		}, o);

		if(o.load)
			o.content = '<div class="load _busy"><div class="ms">� �������� �������� ��������� ������.</div></div>';
		var frameNum = $('.dFrame').length,
			html =
			'<div class="_dialog">' +
				'<div class="head">' +
					'<a class="img_del"></a>' +
					o.head +
				'</div>' +
				'<div class="dcntr">' +
					'<iframe class="dFrame" name="dFrame' + frameNum + '"></iframe>' +
					'<div class="content' + (o.class ? ' ' + o.class + '_dialog' : '') + '"' + (o.padding ? ' style="padding:' + o.padding + 'px"' : '') + '>' +
						o.content +
					'</div>' +
				'</div>' +
				'<div class="bottom">' +
					'<button class="vk submit' + (o.butSubmit ? '' : ' dn') + '">' + o.butSubmit + '</button>' +
					(o.butCancel ? '<button class="vk cancel">' + o.butCancel + '</button>' : '') +
				'</div>' +
			'</div>';

		// ���� ����������� ������ ������ �� ��������, ������������ ��������� ������������ ������ ��������
		if(frameNum == 0)
			DIALOG_MAXHEIGHT = 0;

		var dialog = $('body').append(html).find('._dialog:last'),
			content = dialog.find('.content'),
			bottom = dialog.find('.bottom'),
			butSubmit = bottom.find('.submit'),
			w2 = Math.round(o.width / 2); // ������/2. ��� ����������� ��������� �� ������
		dialog.find('.head .img_del').click(dialogClose);
		butSubmit.click(function() {
			o.submit();
		});
		bottom.find('.cancel').click(function(e) {
			e.stopPropagation();
			o.cancel();
			dialogClose();
		});

		//��� ���� input ��� ������� enter ����������� submit
		content.find('input').keyEnter(o.submit);

		_backfon();

		dialog.css({
			width:o.width + 'px',
			top:$(window).scrollTop() + VK_SCROLL + o.top + 'px',
			left:313 - w2 + 'px',
			'z-index':ZINDEX + 5
		});

		window['dFrame' + frameNum].onresize = function() {
			var fr = $('.dFrame'),
				max = 0;
			for(var n = 0; n < fr.length; n++) {
				var h = fr.eq(n).height();
				if(h > max)
					max = h;
			}
			var dh = max + VK_SCROLL + 80 + o.mb;
			if(DIALOG_MAXHEIGHT != dh) {
				DIALOG_MAXHEIGHT = dh;
				_fbhs();
			}
		};

		function dialogClose() {
			dialog.remove();
			_backfon(false);
			if(frameNum == 0)
				DIALOG_MAXHEIGHT = 0;
			_fbhs();
		}
		function dialogErr(msg) {
			bottom.vkHint({
				msg:'<span class="red">' + msg + '</span>',
				top:-48,
				left:w2 - 90,
				indent:40,
				show:1,
				remove:1
			});
		}

		return {
			close:dialogClose,
			process:function() {
				butSubmit.addClass('_busy');
			},
			abort:function(msg) {
				butSubmit.removeClass('_busy');
				if(msg)
					dialogErr(msg);
			},
			bottom:(function() {
				return bottom;
			})(),
			content:(function() {
				return content;
			})(),
			err:dialogErr,
			loadError:function() {
				dialog.find('.load').removeClass('_busy');
			},
			butSubmit:function(name) {
				butSubmit[(name ? 'remove' : 'add') + 'Class']('dn');
				butSubmit.html(name);
			},
			submit:function(func) {
				o.submit = func;
			}
		};
	},
	_dialogDel = function(o) {//����� ������� ��������
		o = $.extend({
			id:0,               //id, ������� ����� �������
			head:'������',      //��������� �������
			info:'',            //�������������� ���������� �� ��������
			op:'_del',          //���������� switch, �� ������� ����� ������������� ��������
			func:function() {}  //�������, ����������� ����� ��������� ��������
		}, o);
		var html = (o.info ? '<div class="_info">'+ o.info + '</div>' : '') +
				'<center class="red">����������� ��������<br /><b>' + o.head + '</b>.</center>',
			dialog = _dialog({
				head:'�������� ' + o.head,
				padding:30,
				content:html,
				butSubmit:'�������',
				submit:submit
			});
		function submit() {
			var send = {
				op:o.op,
				id:o.id
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg('�������');
					o.func(res);
				} else {
					dialog.abort();
					dialog.err(res.text);
				}
			}, 'json');
		}
	},
	_tooltip = function(msg, left, ugolSide) {
		return ' _tooltip">' +
		'<div class="ttdiv"' + (left ? ' style="left:' + left + 'px"' : '') + '>' +
			'<div class="ttmsg">' + msg + '</div>' +
			'<div class="ttug' + (ugolSide ? ' ' + ugolSide : '') + '"></div>' +
		'</div>';
	},
	_parent = function(t, tag) {//����� ������� ���� ������� parent()
		tag = tag || 'TR';
		var max = 10,
			e = 0,
			cls = tag[0] == '.';
		if(cls)
			tag = tag.substr(1);
		while(!e) {
			t = t.parent();
			e = cls ? t.hasClass(tag) : t[0].tagName == tag;
			if(!--max)
				e = 1;
		}
		return t;
	},
	_busy = function(v) {//����������� ��������� �������� � mainLinks
		var m = $('#_menu');
		if(v === 0) {
			m.removeClass('_busy');
			return;
		}
		if(m.hasClass('_busy'))
			return true;
		m.addClass('_busy');
	},
	_filterSpisok = function(arr, v, id) {//���������� ������ ��� �������� �� ������. ���������� � cookies
		if(id)
			arr[id] = v;
		arr.page = 1;

		//������� ���������� ��������� ������� � ������ ���������
		_scroll('clear');

		var name = arr.op.split('_spisok')[0],
			loc = '';

		for(var i in arr) {
			if(i == 'op')
				continue;
			if(i == 'page')
				continue;
			if(i == 'type_id')
				continue;
			_cookie(COOKIE_PREFIX + name + '_' + i, escape(arr[i]));
		}

/*
		if(v.find) loc += '.find=' + escape(v.find);
		else {
			if(v.dolg > 0) loc += '.dolg=' + v.dolg;
			if(v.active > 0) loc += '.active=' + v.active;
			if(v.comm > 0) loc += '.comm=' + v.comm;
			if(v.opl > 0) loc += '.opl=' + v.opl;
		}
		VK.callMethod('setLocation', hashLoc + loc);
*/
//		_cookie(VIEWER_ID + '_client_find', escape(v.find));
	},
	_nextCallback = function() {},//�������, ������� ����������� ����� ������ �� ����� ����������� ������
	_yearAss = function(start) {//��������� ������ �����
		var d = new Date(),
			yearStart = d.getFullYear(),//��������� �������� ����
			yearEnd = yearStart + 1,    //�������� �������� ����
			send = [];

		//���� �������� �������� ���� ����� ������, �� ����������� ������������
		start = _num(start);
		if(start > 2000 && start < yearStart)
			yearStart = start;

		if(start > yearEnd)
			yearEnd = start;

		for(var n = yearStart; n <= yearEnd; n++)
			send[n] = n;

		return send;
	},
	_checkAll = function(o, v) {
		var check = $('._check'),
			len = check.length,
			arr = [],
			sum = 0,
			type = []; //������ id � �����������. ������: accrual:140,deduct:504,expense:21. ����������� � val c ������� ch
		for(var n = 0; n < len; n++) {
			var eq = check.eq(n),
				id = _num(eq.attr('id').split('_check')[0].split('ch')[1]);
			if(!id)
				continue;
			if(o == 'change')
				$('#ch' + id)._check(v);
			else
				if(_num(eq.find('input').val())) {
					arr.push(id);
					if(o == 'sum')
						sum += _parent(eq).find('.sum').html() * 1;
					type.push(eq.parent().attr('val'));
				}
		}

		if(o == 'array')
			return arr;
		if(o == 'count')
			return arr.length;
		if(o == 'sum')
			return sum;
		if(o == 'type')
			return type.join();
		return arr.join();
	};

$.fn._check = function(o) {
	var t = $(this),
		id = t.attr('id');

	switch(typeof o){
		case 'number':
		case 'string':
		case 'boolean':
			o = o == 1 ? 1 : 0;
			if(t.val() == o)
				return t;
			t.val(o);
			t.parent()
				.removeClass('check' + (o == 1 ? 0 : 1))
				.addClass('check' + o);
			return t;
		case 'function': _click(o);	return t;
	}

	o = $.extend({
		name:'',
		disabled:0,
		light:0,
		mt:0,//margin-top
		block:0,
		func:function() {}
	}, o);

	var val = t.val() == 1 ? 1 : 0;
	t.val(val);
	t.wrap('<div class="_check' +
					(o.disabled ? ' disabled' : '') +
					' check' + val + (o.name ? '' : ' e') +
					(o.light ? ' l' : '') +
					(o.block ? ' block' : '') +
				'"' +
				' id="' + id + '_check"' +
				(o.mt ? ' style="margin-top:' + o.mt + 'px"' : '') +
		'>');
	t.after(o.name);
	_click(o.func);

	function _click(func) {
		if(!$('#' + id + '_check').hasClass('disabled'))
			$(document).on('click', '#' + id + '_check', function() {
			func(parseInt(t.val()), id);
		});
	}
	return t;
};
$.fn._radio = function(o) {
	var t = $(this),
		n,
		id = t.attr('id');

	switch(typeof o){
		case 'number':
		case 'string':
			var p = t.parent();
			if(p.hasClass('_radio')) {
				p.find('.on').removeClass('on').addClass('off');
				var div = p.find('div');
				for(n = 0; n < div.length; n++) {
					var eq = div.eq(n);
					if(o == eq.attr('val')) {
						eq.addClass('on').removeClass('off');
						break;
					}
				}
				t.val(o);
			}
			return t;
		case 'function': _click(o);	return t;
		case 'object':
			if('length' in o) {
				var div = $('#' + id + '_radio').find('div'),
					len = div.length;
				for(n = 0; n < div.length; n++) {
					var eq = div.eq(n);
					eq.html('<s></s>' + o[n].title);
				}
				return t;
			}
	}

	o = $.extend({
		title0:'',
		spisok:[],
		light:0,
		right:15,
		block:1,
		func:function() {}
	}, o);
	var list = '',
		val = t.val(),
		spisok = [];

	if(o.title0)
		spisok = [{uid:0,title:o.title0}];
	for(n = 0; n < o.spisok.length; n++)
		spisok.push(o.spisok[n]);
	for(n = 0; n < spisok.length; n++) {
		var sp = spisok[n],
			sel = val == sp.uid ? 'on' : 'off',
			l = o.light ? ' l' : '';
		list += '<div class="' + sel + l + '" ' +
					 'val="' + sp.uid + '"' +
					(o.right ? ' style="margin-right:' + o.right + 'px"' : '') +
				'>' +
						'<s></s>' +
						sp.title +
				'</div>';
	}
	t.wrap('<div class="_radio' + (o.block ? ' block' : '') + '" id="' + id + '_radio">');
	t.after(list);
	_click(o.func);

	function _click(func) {
		$(document).off('click', '#' + id + '_radio .on,#' + id + '_radio .off');
		$(document).on('click', '#' + id + '_radio .on,#' + id + '_radio .off', function() {
			func(_num(t.val()), id);
		});
	}

	return t;
};
$.fn._search = function(o, v) {
	var t = $(this),
		id = t.attr('id');

	switch(typeof o) {
		case 'number':
		case 'string':
			if(o == 'val') {
				if(v) {
					window[id + '_search'].inp(v);
					return;
				}
				return window[id + '_search'].inp();
			}
			if(o == 'clear')
				window[id + '_search'].clear();
			return t;
	}
	o = $.extend({
		width:126,
		focus:0,//����� ������������� �����
		txt:'', //�����-���������
		func:function() {},
		enter:0,//��������� �������� ����� ������ ����� ������� �����
		v:''    //�������� ��������
	}, o);
	var html =
			'<div class="_search" style="width:' + o.width + 'px">' +
				'<div class="img_del dn"></div>' +
				'<div class="hold">' + o.txt + '</div>' +
				'<input type="text" style="width:' + (o.width - 45) + 'px" />' +
			'</div>';
	t.html(html);
	var _s = t.find('._search'),
		inp = t.find('input'),
		hold = t.find('.hold'),
		del = t.find('.img_del');

	if(o.focus) {
		inp.focus();
		holdFocus()
	}

	inp .focus(holdFocus)
		.blur(holdBlur)
		.keyup(function() {
			var c = $(this).val().length > 0;
			hold[(c ? 'add' : 'remove') + 'Class']('dn');
			del[(c ? 'remove' : 'add') + 'Class']('dn');
			if(!o.enter)
				o.func(inp.val(), id);
		});

	if(o.enter)
		inp.keydown(function(e) {
			if(e.which == 13)
				o.func($(this).val(), id);
		});

	t.clear = function() {
		inp.val('');
		del.addClass('dn');
		hold.removeClass('dn');
	};

	del.click(function() {
		t.clear();
		o.func('', id);
	});

	_s.click(function() {
		inp.focus();
		holdFocus();
	});

	t.inp = function(v) {
		if(!v)
			return $.trim(inp.val());
		inp.val(v);
		del.removeClass('dn');
		hold.addClass('dn');
		return $(this);
	};
	t.clear = function() {
		inp.val('');
		del.addClass('dn');
		hold.removeClass('dn');
	};
	window[id + '_search'] = t;

	t.inp(o.v);

	return t;

	function holdFocus() { hold.css('color', '#ccc'); }
	function holdBlur() { hold.css('color', '#777'); }
};
$.fn._calendar = function(o) {
	var t = $(this),
		id = t.attr('id'),
		val = t.val(),
		d = new Date();

	o = $.extend({
		year:d.getFullYear(),	// ���� ��� �� ������, �� ������� ���
		mon:d.getMonth() + 1,   // ���� ����� �� ������, �� ������� �����
		day:d.getDate(),		// �� �� � ���
		lost:0,                 // ���� �� 0, �� ����� ������� ��������� ���
		func:function () {},    // ����������� ������� ��� ������ ���
		place:'right'           // ������������ ��������� ������������ ������
	}, o);

	// ���� input hidden ������� ����, ���������� �
	if(REGEXP_DATE.test(val) && val != '0000-00-00') {
		var r = val.split('-');
		o.year = r[0];
		o.mon = Math.abs(r[1]);
		o.day = Math.abs(r[2]);
	}

	t.wrap('<div class="_calendar" id="' + id + '_calendar">');
	t.after('<div class="calinp">' + o.day + ' ' + MONTH_DAT[o.mon] + ' ' + o.year + '</div>' +
		'<div class="calabs"></div>');

	var	curYear = o.year,//����,
		curMon = o.mon,  //�������������
		curDay = o.day,  //� input hidden
		inp = t.next(),
		calabs = inp.next(),//����� ��� ���������
		calmon,             //����� ��� ������ � ����
		caldays;            //����� ��� ����

	t.val(dataForm());
	inp.click(calPrint);

	function calPrint(e) {
		if(!calabs.html()) {
			e.stopPropagation();

			// ���� ���� ������� ������ ���������, �� �����������, ����� ��������
			var cals = $('.calabs');
			for(var n = 0; n < cals.length; n++) {
				var sp = cals.eq(n);
				if(sp.parent().attr('id').split('_calendar')[0] == id)
					continue;
				sp.html('');
			}

			// �������� �������� ��������� ��� ������� �� ����� ����� ������
			$(document).on('click.calendar' + id, function () {
				calabs.html('');
				$(document).off('click.calendar' + id);
			});

			o.year = curYear;
			o.mon = curMon;
			o.day = curDay;

			var html =
				'<div class="calcal" style="left:' + (o.place == 'right' ? 0 : -64) + 'px">' +
					'<table class="calhead">'+
						'<tr><td class="calback">' +
							'<td class="calmon">' + MONTH_DEF[curMon] + ' ' + curYear +
							'<td class="calnext">' +
					'</table>' +
					'<table class="calweeks"><tr><td>��<td>��<td>��<td>��<td>��<td>��<td>��</table>' +
					'<table class="caldays"></table>' +
				'</div>';
			calabs.html(html);
			calabs.find('.calback').click(back);
			calabs.find('.calnext').click(next);
			calmon = calabs.find('.calmon');
			caldays = calabs.find('.caldays');
			daysPrint();
		}
	}
	function daysPrint() {//����� ������ ����
		var n,
			html = '<tr>',
			year = d.getFullYear(),
			mon = d.getMonth() + 1,
			today = d.getDate(),
			df = dayFirst(o.year, o.mon),
			cur = year == o.year && mon == o.mon,// ��������� �������� ���, ���� ������� ������� ��� � �����
			lost = o.lost == 0, // ���������� ��������� ����
			st = o.year == curYear && o.mon == curMon, // ��������� ���������� ���
			dc = dayCount(o.year, o.mon);

		//��������� ������ �����
		if(df > 1)
			for(n = 0; n < df - 1; n++)
				html += '<td>';

		for(n = 1; n <= dc; n++) {
			var l = '';
			if(o.year < year) l = ' lost';
			else if(o.year == year && o.mon < mon) l = ' lost';
			else if(o.year == year && o.mon == mon && n < today) l = ' lost';
			html +=
				'<td class="' + (!l || l && !lost ? ' sel' : '') +
								(cur && n == today ? ' b' : '') +
								(st && n == curDay ? ' set' : '') +
								l + '"' +
							(!l || l && !lost ? ' val="' + n + '"' : '') +
					'>' + n;
			df++;
			if(df == 8 && n != dc) {
				html += "<tr>";
				df = 1;
			}
		}
		caldays
			.html(html)
			.find('.sel').click(daySel)
	}
	function daySel() {
		curYear = o.year;
		curMon = o.mon;
		curDay = $(this).attr('val');
		inp.html(curDay + ' ' + MONTH_DAT[curMon] + ' ' + curYear);
		t.val(dataForm());
		o.func(dataForm());
	}
	function dataForm() {//������������ ���� � ���� 2012-12-03
		return curYear +
			'-' + (curMon < 10 ? '0' : '') + curMon +
			'-' + (curDay < 10 ? '0' : '') + curDay;
	}
	function dayFirst(year, mon) {//����� ������ ������ � ������
		var first = new Date(year, mon - 1, 1).getDay();
		return first == 0 ? 7 : first;
	}
	function dayCount(year, mon) {//���������� ���� � ������
		mon--;
		if(mon == 0) {
			mon = 12;
			year--;
		}
		return 32 - new Date(year, mon, 32).getDate();
	}
	function back(e) {//������������� ��������� �����
		e.stopPropagation();
		o.mon--;
		if(o.mon == 0) {
			o.mon = 12;
			o.year--;
		}

		calmon.html(MONTH_DEF[o.mon] + ' ' + o.year);
		daysPrint();
	}
	function next(e) {//������������� ��������� �����
		e.stopPropagation();
		o.mon++;
		if(o.mon == 13) {
			o.mon = 1;
			o.year++;
		}
		calmon.html(MONTH_DEF[o.mon] + ' ' + o.year);
		daysPrint();
	}
};
$.fn.years = function(o) {// �������������� �����
	var t = $(this),
		id = t.attr('id'),
		val = t.val();

	if(!id)
		return;

	o = $.extend({
		year:(new Date()).getFullYear(),
		start:function() {},
		func:function() {},
		center:function() {}
	}, o);

	if(val)
		o.year = val * 1;

	var html =
		'<div class="years" id="years_' + id + '">' +
			'<TABLE>' +
				'<TR><TD class="but">&laquo;' +
					'<TD id="ycenter"><SPAN>' + o.year + '</SPAN>' +
					'<TD class="but">&raquo;' +
			'</TABLE>' +
		'</div>';
	t.after(html);
	t.val(o.year);

	var years = {
		left:0,
		speed:2,
		span:$('#years_' + id + ' #ycenter SPAN'),
		width:Math.round($('#years_' + id + ' #ycenter').css('width').split(/px/)[0] / 2),  // ������ ����������� �����, ��� ���
		ismove:0
	};
	years.next = function(side) {
		o.start();
		var y = years;
		if(!y.ismove) {
			y.ismove = 1;
			var changed = 0,
				timer = setInterval(function () {
				var span = y.span;
				y.left -= y.speed * side;

				if (y.left > 0 && changed && side == -1 ||
					y.left < 0 && changed && side == 1) {
					y.left = 0;
					y.ismove = 0;
					y.speed = 0;
					clearInterval(timer);
				}

				span[0].style.left = y.left + 'px';
				y.speed += 2;

				if (y.left > y.width && !changed && side == -1 ||
					y.left < -y.width && !changed && side == 1) {
					changed = 1;
					o.year += side;
					span.html(o.year);
					y.left = y.width * side;
					t.val(o.year);
					o.func(o.year, id);
				}
			}, 25);
		}
	};

	$('#years_' + id + ' #ycenter').click(function() {
		o.center(o.year, id);
	});

	$('#years_' + id + ' .but:first').mousedown(function () { allmon = 1; years.next(-1); });
	$('#years_' + id + ' .but:eq(1)').mousedown(function () { allmon = 1; years.next(1); });
};
$.fn.keyEnter = function(func) {
	$(this).keydown(function(e) {
		if(e.keyCode == 13)
			func();
	});
	return $(this);
};
$.fn.rightLink = function(o) {
	var t = $(this),
		id = t.attr('id'),
		p,
		n;
	if(typeof o == 'number' || typeof o == 'string') {
		p = t.parent();
		if(p.hasClass('rightLink')) {
			p.find('.sel').removeClass('sel');
			var a = p.find('a');
			for(n = 0; n < a.length; n++) {
				var eq = a.eq(n);
				if(o == eq.attr('val')) {
					eq.addClass('sel');
					break;
				}
			}
			t.val(o);
		}
		return t;
	}

	if(typeof o == 'function') {
		_click(t, o);
		return t;
	}

	o = $.extend({
		spisok:[],
		func:function() {}
	}, o);
	var list = '',
		val = t.val();
	for(n = 0; n < o.spisok.length; n++) {
		var sp = o.spisok[n];
		list += '<a ' + (val == sp.uid ? 'class="sel"' : '') + ' val="' + sp.uid + '">' + sp.title + '</a>';
	}
	t.wrap('<div class="rightLink">');
	t.after(list);
	_click(t, o.func);

	function _click(t, func) {
		var p = t.parent();
		p.find('a').click(function() {
			p.find('.sel').removeClass('sel');
			$(this).addClass('sel');
			var v = $(this).attr('val');
			t.val(v);
			func(v, id);
		});
	}
	return t;
};
$.fn._dropdown = function(o) {
	var t = $(this),
		id = t.attr('id');

	if(typeof o == 'number' || typeof o == 'string') {
		switch(o) {
			case 'remove':t.next().remove('._dropdown'); break;
			default: window[id + '_dropdown'].value(o);
		}
		return t;
	}

	o = $.extend({
		head:'',    // ���� �������, �� �������� � �������� ������, � ������ �� spisok
		headgrey:0,
		disabled:0,
		title0:'',
		spisok:[],
		func:function() {},
		nosel:0 // �� ��������� �������� ��� ������ ��������
	}, o);
	var n,
		val = t.val() * 1 || 0,
		ass = assCreate(),
		head = o.head || o.title0,
		len = o.spisok.length,
		spisok = o.title0 && !o.disabled ? '<a class="ddu grey' + (!len ? ' last' : '') + (!val ? ' seld' : '') + '" val="0">' + o.title0 + '</a>' : '',
		delay = 0;
	t.val(val);
	for(n = 0; n < len; n++) {
		var sp = o.spisok[n];
		spisok += '<a class="ddu' + (n == len - 1 ? ' last' : '') + (val == sp.uid ? ' seld' : '') + '" val="' + sp.uid + '">' + sp.title + '</a>';
		if(val == sp.uid)
			head = sp.title;
	}
	t.next().remove('._dropdown');
	t.after(
		'<div class="_dropdown' + (o.disabled ? ' disabled' : '') + '" id="' + id + '_dropdown">' +
			(o.disabled ?
				'<span>' + head + '</span>'
				:
				'<a class="ddhead' + (!val && (o.headgrey || o.title0) ? ' grey' : '') + '">' + head + '</a>'
			) +
			'<div class="ddlist">' +
				'<div class="ddsel">' + head + '</div>' +
				spisok +
			'</div>' +
		'</div>');

	if(!o.disabled) {
		var dropdown = t.next(),
			aHead = dropdown.find('.ddhead'),
			list = dropdown.find('.ddlist'),
			ddsel = list.find('.ddsel'),
			ddu = list.find('.ddu');
		aHead.click(function(e) {
			e.stopPropagation();
			delayClear();
			list.show();
		});
		ddsel.click(function(e) {
			e.stopPropagation();
			delayClear();
			list.hide();
		});
		ddu.click(function(e) {
			e.stopPropagation();
			var th = $(this),
				v = parseInt(th.attr('val'));
			setVal(v);
			if(!o.nosel)
				th.addClass('seld');
			list.hide();
			o.func(v, id);
		})
		   .mouseenter(function() {
				ddu.removeClass('seld');
		   });
		list.on({
			mouseleave:function () {
				delay = setTimeout(function() {
					list.fadeOut(200);
				}, 500);
			},
			mouseenter:delayClear
		});
	}

	function assCreate() {//�������� �������������� �������
		var arr = o.title0 ? {0:o.title0} : {};
		for (var n = 0; n < o.spisok.length; n++) {
			var sp = o.spisok[n];
			arr[sp.uid] = sp.title;
		}
		return arr;
	}
	function setVal(v) {
		delayClear();
		if(!o.nosel) {
			t.val(v);
			aHead.html(ass[v])[(o.title0 && !v ? 'add' : 'remove') + 'Class']('grey');
			ddsel.html(ass[v]);
		}
	}
	function delayClear() {
		if(delay) {
			clearTimeout(delay);
			delay = 0;
		}
	}

	t.value = function(v) {
		setVal(v);
		list.find('.seld').removeClass('seld');
		for(n = 0; n < ddu.length; n++) {
			var eq = ddu.eq(n);
			if(eq.attr('val') == v) {
				eq.addClass('seld');
				break;
			}
		}
	};
	window[id + '_dropdown'] = t;
	return t;
};
$.fn._tooltip = function(msg, left, ugolSide) {
	var t = $(this);

	t.addClass('_tooltip');
	t.append(
		'<div class="ttdiv"' + (left ? ' style="left:' + left + 'px"' : '') + '>' +
			'<div class="ttmsg">' + msg + '</div>' +
			'<div class="ttug' + (ugolSide ? ' ' + ugolSide : '') + '"></div>' +
		'</div>'
	);
};
$.fn._attach = function(o) {//�������� � ������: ��������, ��������, ��������

	// ._attach: ����, � ������� ���������� ��� ����������� � ������
	// ._attach-add: ������ ��� ���������� ���� ���������� �����

	if(!window.ATTACH)
		ATTACH = {};

	var t = $(this),
		attach_id = _num(t.val());

	o = $.extend({
		title:'���������� ����',
		icon:0,//���������� ������ � ���� ������ ���������
		zayav_id:0,
		zayav_save:0,//����� ����������� � ������, ��� ������ ����� ��������
		func:function() {}
	}, o);

	t.wrap('<div class="_attach">');

	var html = '<a class="_attach-add">' + o.title + '</a>';
	if(o.icon)
		html = '<a class="_attach-add img_doc' + _tooltip(o.title, -13, 'l') + '</a>';

	t.parent().append(html);

	var attach = t.parent(),
		attachAdd = attach.find('._attach-add');

	if(attach_id)
		attach.addClass('ex');

	attachAdd.click(add);
	menu(attach_id);

	function add() {//���� ��� �������� �����
		var html =
			'<div id="attach-add-tab">' +
				'<div class="_info">' +
					'<u>�������������� ���� ������:</u>' +
					'<br />' +
					'<br /><b>xls</b> - Microsoft Excel 97-2003' +
					'<br /><b>xlsx</b> - Excel 2007' +
					'<br /><b>doc</b> - Microsoft Word 97-2003' +
					'<br /><b>docx</b> - Microsoft Word 2007' +
					'<br /><b>rtf</b> - ��������� Word' +
					'<br /><b>pdf</b> - Adobe Acrobat Reader' +
					'<br /><b>jpg</b> - ����������� � ������� JPG' +
					'<br /><b>png</b> - ����������� � ������� PNG' +
				'</div>' +
				'<h1>' +
					'<form method="post" action="' + AJAX_MAIN + '" enctype="multipart/form-data" target="attach-frame">' +
						'<input type="hidden" name="op" value="attach_upload" />' +
						'<input type="file" name="f1" id="file" />' +
					'</form>' +
					'<button class="vk">������� ����</button>' +
				'</h1>' +
				'<iframe name="attach-frame"></iframe>' +
				'<table id="tab">' +
					'<tr><td class="label r">�������� �����:<td><input type="text" id="name" />' +
					'<tr><td class="label r">������:<td><b id="size">0</b> ��' +
				'</table>' +
			'</div>',
			dialog = _dialog({
				top:40,
				width:400,
				head:'�������� ������ �����',
				content:html,
				butSubmit:'',
				submit:submit
			}),
			tab = $('#attach-add-tab'),
			but = tab.find('.vk'),
			form = tab.find('form'),
			file = $('#file'),
			timer;



		file.change(function() {
			file.css('visibility', 'hidden');
			but.addClass('_busy');
			_cookie('_attached', 0);
			_cookie('_attached_id', 0);
			timer = setInterval(upload_start, 500);
			form.submit();
		});
		function upload_start() {
			var c = _cookie('_attached');
			if(c == 0)
				return;
			clearInterval(timer);
			but.removeClass('_busy');
			file.css('visibility', 'visible');
			if(c == 1) {
				attach_id = _cookie('_attached_id');
				var send = {
					op:'attach_get',
					id:attach_id
				};
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						$('#name').val(res.name);
						$('#size').html(res.size);
						tab.addClass('uploaded');
						dialog.butSubmit('���������');
					}
				}, 'json');
				return;
			}
		}
		function submit() {
			var send = {
				op:'attach_save',
				id:attach_id,
				zayav_id:o.zayav_id,
				zayav_save:o.zayav_save,
				name:$.trim($('#name').val())
			};
			if(!send.name)
				dialog.err('�� ������� �������� �����');
			else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					dialog.abort();
					if(res.success) {
						dialog.close();
						attach.addClass('ex');
						t.val(attach_id);
						ATTACH[attach_id] = res.arr;
						menu();
						o.func(attach_id);
					}
				}, 'json');
			}
		}
	}
	function menu() {
		if(!attach_id)
			return;

		var at = ATTACH[attach_id];

		t._dropdown({
			head:at.name,
			nosel:1,
			spisok:[
				{uid:1,title:'������� (' + Math.round(at.size / 1024) + ' ��.)'},
				{uid:2,title:'��������'},
				{uid:3,title:'�������'}
			],
			func:function(v) {
				switch(v) {
					case 1: location.href = at.link; break;
					case 2: edit(); break;
					case 3: del(); break;
				}
			}
		});
	}
	function edit() {
		var at = ATTACH[attach_id];
		var html =
			'<div id="attach-add-tab" class="uploaded">' +
				'<table id="tab">' +
					'<tr><td class="label r">�������� �����:<td><input type="text" id="name" value="' + at.name + '" />' +
					'<tr><td class="label r">������:<td><b id="size">' + Math.round(at.size / 2014) + '</b> ��' +
				'</table>' +
			'</div>',
			dialog = _dialog({
				top:40,
				width:400,
				head:'�������������� ������ �����',
				content:html,
				butSubmit:'���������',
				submit:submit
			});
		function submit() {
			var send = {
				op:'attach_edit',
				id:attach_id,
				name:$.trim($('#name').val())
			};
			if(!send.name)
				dialog.err('�� ������� �������� �����');
			else {
				dialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					dialog.abort();
					if(res.success) {
						dialog.close();
						ATTACH[attach_id].name = send.name;
						menu();
					}
				}, 'json');
			}
		}

	}
	function del() {
		_dialogDel({
			id:attach_id,
			head:'�����',
			op:'attach_del',
			func:function() {
				t.val(0)._dropdown('remove');
				attach.removeClass('ex');
				o.func(0);
			}
		});
	}
};

(function () {// ��������� vkHint 2013-02-14 14:43
	var Hint = function (t, o) { this.create(t, o); return t; };

	Hint.prototype.create = function (t, o) {
		o = $.extend({
			msg:'��������� ���������',
			width:0,
			event:'mouseenter', // �������, ��� ������� ���������� �������� ���������
			ugol:'bottom',
			indent:'center',
			top:0,
			left:0,
			show:0,	  // �������� �� ��������� ����� �������� ��������
			delayShow:0, // �������� ����� ���������
			delayHide:0, // �������� ����� ��������
			correct:0,   // ��������� top � left
			remove:0	 // ������� ��������� ����� ������
		}, o);

		var correct = o.correct == 1 ? "<div class=correct>top: <SPAN id=correct_top>" + o.top + "</SPAN> left: <SPAN id=correct_left>" + o.left + "</SPAN></div>" : '';

		var html = "<TABLE class=cont_table>" +
			"<TR><TD class=ugttd colspan=3>" + (o.ugol == 'top' ? "<div class=ugt></div>" : '') +
			"<TR><TD class=ugltd>" + (o.ugol == 'left' ? "<div class=ugl></div>" : '') +
			"<TD class=cont>" + correct + o.msg +
			"<TD class=ugrtd>" + (o.ugol == 'right' ? "<div class=ugr></div>" : '') +
			"<TR><TD class=ugbtd colspan=3>" + (o.ugol == 'bottom' ? "<div class=ugb></div>" : '') +
			"</TABLE>";

		html = "<TABLE>" +
			"<TR><TD class=side012><TD>" + html + "<TD class=side012>" +
			"<TR><TD class=b012 colspan=3>" +
			"</TABLE>";

		html = "<TABLE class=hint_table>" +
			"<TR><TD class=side005><TD>" + html + "<TD class=side005>" +
			"<TR><TD class=b005 colspan=3>" +
			"</TABLE>";

		t.prev().remove('.hint'); // �������� ���������� ����� �� ���������
		t.before("<div class=hint>" + html + "</div>"); // ������� ����� ���������

		var hi = t.prev(); // ���� absolute ��� ���������
		var hintTable = hi.find('.hint_table:first'); // ���� ���������
		if (o.width > 0) { hintTable.find('.cont_table:first').width(o.width); }

		var hint_width = hintTable.width();
		var hint_height = hintTable.height();

		hintTable.hide().css('visibility','visible');

		// ��������� ����������� �������� � ������� ��� ������
		var top = o.top; // ��������� ��������� ���������
		var left = o.left;
		switch (o.ugol) {
			case 'top':
				top = o.top - 15;
				var ugttd = hintTable.find('.ugttd:first');
				if (o.indent == 'center') { ugttd.css('text-align', 'center'); }
				else if (o.indent == 'right') { ugttd.css('text-align', 'right'); }
				else if (o.indent == 'left') { ugttd.css('text-align', 'left'); }
				else if (!isNaN(o.indent)) {
					ugttd.css('text-align', 'left');
					if (o.indent < 10) { o.indent = 10; }
					if (o.indent > hint_width) { o.indent = hint_width - 28; }
					hintTable.find('.ugt:first').css('margin-left', o.indent + 'px');
				}
				break;

			case 'right':
				left = o.left + 25;
				var ugrtd = hintTable.find('.ugrtd:first');
				if (o.indent == 'center') { ugrtd.css('vertical-align', 'middle'); }
				else if (o.indent == 'bottom') { ugrtd.css('vertical-align', 'bottom'); }
				else if (!isNaN(o.indent)) {
					if (o.indent < 3) { o.indent = 3; }
					if (o.indent > hint_height) { o.indent = hint_height - 31; }
					hintTable.find('.ugr:first').css('margin-top', o.indent + 'px');
				}
				break;

			case 'bottom':
				top = o.top + 15;
				var ugbtd = hintTable.find('.ugbtd:first');
				if (o.indent == 'center') { ugbtd.css('text-align', 'center'); }
				else if (o.indent == 'right') { ugbtd.css('text-align', 'right'); }
				else if (o.indent == 'left') { ugbtd.css('text-align', 'left'); }
				else if (!isNaN(o.indent)) {
					ugbtd.css('text-align', 'left');
					if (o.indent < 10) { o.indent = 10; }
					if (o.indent > hint_width) { o.indent = hint_width - 28; }
					hintTable.find('.ugb:first').css('margin-left', o.indent + 'px');
				}
				break;

			case 'left':
				left = o.left - 25;
				var ugltd = hintTable.find('.ugltd:first');
				if (o.indent == 'center') { ugltd.css('vertical-align', 'middle'); }
				else if (o.indent == 'bottom') { ugltd.css('vertical-align', 'bottom'); }
				else if (!isNaN(o.indent)) {
					if (o.indent < 3) { o.indent = 3; }
					if (o.indent > hint_height) { o.indent = hint_height - 31; }
					hintTable.find('.ugl:first').css('margin-top', o.indent + 'px');
				}
				break;
		}




		// ���������� ������� �� ���������� ����� �� ���������
		t.off(o.event + '.hint');
		t.off('mouseleave.hint');

		// ��������� �������
		t.on(o.event + '.hint', show);
		t.on('mouseleave.hint', hide);
		hintTable.on('mouseenter.hint', show);
		hintTable.on('mouseleave.hint', hide);



		// �������� �������� ���������:
		// - wait_to_showind - ������� ������ (���� ���� ��������)
		// - showing - ���������
		// - show - ��������
		// - wait_to_hidding - ������� ������� (���� ���� ��������)
		// - hidding - ����������
		// - hidden - ������
		var process = 'hidden';

		var timer = 0;

		// �������������� ����� ���������, ���� �����
		if (o.show != 0) { show(); }

		// �������� ���������
		function show() {
			if (o.correct != 0) { $(document).off('keydown.hint'); }
			switch (process) {
				case 'wait_to_hidding': clearTimeout(timer); process = 'show'; break;
				case 'hidding':
					process = 'showing';
					hintTable
						.stop()
						.animate({top:top, left:left, opacity:1}, 200, showed);
					break;
				case 'hidden':
					if (o.delayShow > 0) {
						process = 'wait_to_showing';
						timer = setTimeout(action, o.delayShow);
					} else { action(); }
					break;
			}
			// �������� �������� ���������
			function action() {
				process = 'showing';
				hintTable
					.css({top:o.top, left:o.left})
					.animate({top:top, left:left, opacity:'show'}, 200, showed);
			}
			// �������� �� ���������� ��������
			function showed() {
				process = 'show';
				if (o.correct != 0) {
					$(document).on('keydown.hint', function (e) {
						e.preventDefault();
						switch (e.keyCode) {
							case 38: o.top--; top--; break; // �����
							case 40: o.top++; top++; break; // ����
							case 37: o.left--; left--; break; // �����
							case 39: o.left++; left++; break; // ������
						}
						hintTable.css({top:top, left:left});
						hintTable.find('#correct_top').html(o.top);
						hintTable.find('#correct_left').html(o.left);
					});
				}
			}
		} // end show




		// ������� ���������
		function hide() {
			if (o.correct != 0) { $(document).off('keydown.hint'); }
			if (process == 'wait_to_showing') { clearTimeout(timer); process = 'hidden'; }
			if (process == 'showing') { hintTable.stop(); action(); }
			if (process == 'show') {
				if (o.delayHide > 0) {
					process = 'wait_to_hidding';
					timer = setTimeout(action, o.delayHide);
				} else { action(); }
			}
			function action() {
				process = 'hidding';
				hintTable.animate({opacity:'hide'}, 200, function () {
					process = 'hidden';
					if (o.remove != 0) {
						hi.remove();
						t.off(o.event + '.hint');
						t.off('mouseleave.hint');
					}
				});
			}
		} // end hide
	};// end Hint.prototype.create

	$.fn.vkHint = function (o) { return new Hint($(this), o); };
})();

$.fn._select = function(o) {
	var t = $(this),
		n,
		s,
		id = t.attr('id'),
		val = t.val() || 0;

	if(!id)
		return;

	switch(typeof o) {
		default:
		case 'number':
		case 'string':
			s = window[id + '_select'];
			switch(o) {
				case 'process': s.process(); break;
				case 'cancel': s.cancel(); break;
				case 'title': return s.title();
				case 'inp': return s.inp();
				case 'focus': s.focus(); break;
				case 'remove':
					$('#' + id + '_select').remove();
					window[id + '_select'] = null;
					break;
				default:
					if(REGEXP_NUMERIC_MINUS.test(o)) {
						var write_save = s.o.write_save;
						s.o.write_save = 0;
						s.value(o);
						s.o.write_save = write_save;
					}
			}
			return t;
		case 'object':
			if('length' in o) {
				s = window[id + '_select'];
				s.spisok(o);
				return t;
			}
			if(!('spisok' in o))
				return t;
	}

	o = $.extend({
		width:150,			// ������
		disabled:0,
		block:false,       	// ������������ �������
		bottom:0,           // ������ �����
		title0:'',			// ���� � ������� ���������
		spisok:[],			// ���������� � ������� json
		limit:0,
		write:0,            // ����������� ������� ��������
		write_save:0,       // ��������� �����, ���� ���� �� ������ �������
		nofind:'������ ����',
		multiselect:0,      // ����������� �������� ��������� ��������. �������������� ������������� ����� �������
		func:function() {},	// �������, ����������� ��� ������ ��������
		funcAdd:null,		// ������� ���������� ������ ��������. ���� �� ������, �� ��������� ������. ������� ������� ������ ���� ���������, ����� ����� ���� �������� �����
		funcKeyup:funcKeyup	// �������, ����������� ��� ����� � INPUT � �������. ����� ��� ������ ������ �� ���, ��������, Ajax-�������, ���� �� vk api.
	}, o);

	o.clear = o.write && !o.multiselect;

	if(o.multiselect)
		o.write = true;

	var inpWidth = o.width - 17 - 5 - 4;
	if(o.funcAdd)
		inpWidth -= 18;
	if(o.clear) {
		inpWidth -= 18;
		val = _num(val);
	}
	var html =
		'<div class="_select' + (o.disabled ? ' disabled' : '') + '" ' +
			 'id="' + id + '_select" ' +
			 'style="width:' + o.width + 'px' +
				(o.block ? ';display:block' : '') +
				(o.bottom ? ';margin-bottom:' + o.bottom + 'px' : '') +
		'">' +
			'<div class="title0bg">' + o.title0 + '</div>' +
			'<table class="seltab">' +
				'<tr><td class="selsel">' +
						'<input type="text" ' +
							   'class="selinp" ' +
							   'style="width:' + inpWidth + 'px' +
									(o.write && !o.disabled? '' : ';cursor:default') + '"' +
									(o.write && !o.disabled? '' : ' readonly') + ' />' +
					(o.clear ? '<div' + (val ? '' : ' style="display:none"') + ' class="clear' + _tooltip('��������', -49, 'r') + '</div>' : '') +
	   (o.funcAdd ? '<td class="seladd">' : '') +
					'<td class="selug">' +
			'</table>' +
			'<div class="selres" style="width:' + o.width + 'px"></div>' +
		'</div>';
	t.next().remove('._select');
	t.after(html);

	var select = t.next(),
		inp = select.find('.selinp'),
		inpClear = select.find('.clear'),
		sel = select.find('.selsel'),
		res = select.find('.selres'),
		resH, //������ ������ �� ���������
		title0bg = select.find('.title0bg'), //������� title ��� background
		ass,            //������������� ������ � ����������
		save = [],      //���������� ��������� ������
		assHide = {},   //������������� ������ � ������������ � ������
		multiCount = 0, //���������� ��������� ������-��������
		tag = /(<[\/]?[_a-zA-Z0-9=\"' ]*>)/i, // ����� ���� �����
		keys = {38:1,40:1,13:1,27:1,9:1};

	assCreate();

	if(o.multiselect) {
		if(val != 0) {
			var arr = val.split(',');
			for(n = 0; n < arr.length; n++) {
				assHide[arr[n]] = true;
				inp.before('<div class="multi">' + ass[arr[n]] + '<span class="x" val="' + arr[n] + '"></span></div>');
			}
		}
		multiCorrect();
	}
	if(o.funcAdd && !o.disabled)
		select.find('.seladd').click(function() {
			o.funcAdd(id);
		});

	spisokPrint();
	setVal(val);

	var keyVal = inp.val();//�������� �������� �� inp

	if(!o.disabled) {
		$(document)
			.off('click', '#' + id + '_select .selug')
			.on('click', '#' + id + '_select .selug', hideOn)

			.off('click', '#' + id + '_select .selsel')
			.on('click', '#' + id + '_select .selsel', function() { inp.focus(); })

			.off('click', '#' + id + '_select .selun')
			.on('click', '#' + id + '_select .selun', function() { unitSel($(this)); })

			.off('mouseenter', '#' + id + '_select .selun')
			.on('mouseenter', '#' + id + '_select .selun', function() {
				res.find('.ov').removeClass('ov');
				$(this).addClass('ov');
			})

			.off('click', '#' + id + '_select .x')
			.on('click', '#' + id + '_select .x', function(e) {
				e.stopPropagation();
				var v = $(this).attr('val');
				$(this).parent().remove();
				multiCorrect(v, false);
				setVal(v);
				o.func(v, id);
			});

		inp	.focus(function() {
				hideOn();
				if(o.write)
					title0bg.css('color', '#ccc');
			})
			.blur(function() {
				if(o.write)
					title0bg.css('color', '#888');
			})
			.keyup(function(e) {
				if(keys[e.keyCode])
					return;
				title0bg[inp.val() || multiCount ? 'hide' : 'show']();
				inpClear[inp.val() ? 'show' : 'hide']();
				if(keyVal != inp.val()) {
					keyVal = inp.val();
					o.funcKeyup(keyVal);
					t.val(0);
					val = 0;
				}
			});

		inpClear.click(function(e) {
			e.stopPropagation();
			setVal(0);
			inp.val('');
			title0bg.show();
			o.func(0, id);
		});
	}

	function spisokPrint() {
		if(!o.spisok.length) {
			res.html('<div class="nofind">' + o.nofind + '</div>')
			   .removeClass('h250');
			return;
		}
		if(o.write)
			findEm();
		var spisok = o.title0 && !o.write ? '<div class="selun title0" val="0">' + o.title0 + '</div>' : '',
			len = o.spisok.length;
		if(o.limit && len > o.limit)
			len = o.limit;
		for(n = 0; n < len; n++) {
			var sp = o.spisok[n];
			if(assHide[sp.uid])
				continue;
			spisok += '<div class="selun" val="' + sp.uid + '">' + (sp.content || sp.title) + '</div>';
		}
		res.removeClass('h250')
		   .html(spisok)
		   .find('.selun:last').addClass('last');
		resH = res.height();
		if(resH > 250)
			res.addClass('h250');
	}
	function spisokMove(e) {
		if(!keys[e.keyCode])
			return;
		e.preventDefault();
		var u = res.find('.selun'),
			res0 = res[0],
			len = u.length,
			ov;
		for(n = 0; n < len; n++)
			if(u.eq(n).hasClass('ov'))
				break;
		switch(e.keyCode) {
			case 38: //�����
				if(n == len)
					n = 1;
				if(n > 0) {
					if(len > 1) // ���� � ������ ������ ����� ��������
						u.eq(n).removeClass('ov');
					ov = u.eq(n - 1);
				} else
					ov = u.eq(0);
				ov.addClass('ov');
				ov = ov[0];
				if(res0.scrollTop > ov.offsetTop)// ���� ������� ���� ����� ���� ���������, �������� � ����� ����
					res0.scrollTop = ov.offsetTop;
				if(ov.offsetTop - 250 - res0.scrollTop + ov.offsetHeight > 0) // ���� ����, �� ����
					res0.scrollTop = ov.offsetTop - 250 + ov.offsetHeight;
				break;
			case 40: //����
				if(n == len) {
					u.eq(0).addClass('ov');
					res0.scrollTop = 0;
				}
				if(n < len - 1) {
					u.eq(n).removeClass('ov');
					ov = u.eq(n+1);
					ov.addClass('ov');
					ov = ov[0];
					if(ov.offsetTop + ov.offsetHeight - res0.scrollTop > 250) // ���� ������� ���� ���������, �������� � ������ �������
						res0.scrollTop = ov.offsetTop + ov.offsetHeight - 250;
					if(ov.offsetTop < res0.scrollTop) // ���� ����, �� � �������
						res0.scrollTop = ov.offsetTop;
				}
				break;
			case 13: //Enter
				if(n < len) {
					inp.blur();
					unitSel(u.eq(n));
					hideOff();
				}
				break;
			case 27: //ESC
			case 9: //Tab
				inp.blur();
				hideOff();
		}
	}
	function unitSelShow() {//��������� ���������� ���� � ����������� ��� � ���� ���������
		var u = res.find('.selun'),
			res0 = res[0];
		u.removeClass('ov');
		for(n = 0; n < u.length; n++) {
			var ov = u.eq(n);
			if(ov.attr('val') == val) {
				ov.addClass('ov');
				ov = ov[0];
				var top = ov.offsetTop + ov.offsetHeight;
				if(top > 170) {
					var resMax = 250;
					if(resH > top)
						resMax -= resH - top > 120 ? 120 : resH - top;
					res0.scrollTop = top - resMax;
				}
				break;
			}
		}
	}
	function assCreate() {//�������� �������������� �������
		ass = o.title0 ? {0:''} : {};
		for(n = 0; n < o.spisok.length; n++) {
			var sp = o.spisok[n];
			ass[sp.uid] = sp.title;
			if(!sp.content)
				sp.content = sp.title;
			save.push({
				uid:sp.uid,
				title:sp.title,
				content:sp.content
			});
		}
	}
	function funcKeyup() {
		o.spisok = [];
		for(n = 0; n < save.length; n++) {
			var sp = save[n];
			o.spisok.push({
				uid:sp.uid,
				title:sp.title,
				content:sp.content
			});
		}
		spisokPrint();
	}
	function setVal(v) {
		if(o.multiselect) {
			if(!multiCount) {
				t.val(0);
				return;
			}
			var x = sel.find('.x'),
				arr = [];
			for(n = 0; n < x.length; n++)
				arr.push(x.eq(n).attr('val'));
			t.val(arr.join());
			return;
		}
		val = v;
		t.val(v);
		inpClear[v ? 'show' : 'hide']();
		if(v || !v && !o.write_save) {
			inp.val(ass[v] ? ass[v].replace(/&quot;/g,'"') : '');
			title0bg[v == 0 ? 'show' : 'hide']();
		}
	}
	function unitSel(t) {
		var v = parseInt(t.attr('val')),
			item;
		if(o.multiselect) {
			if(!o.title0 && !v || v > 0)
				inp.before('<div class="multi">' + ass[v] + '<span class="x" val="' + v + '"></span></div>');
			multiCorrect(v, true);
		}
		setVal(v);
		for(n = 0; n < o.spisok.length; n++) {
			var sp = o.spisok[n];
			if(sp.uid == v) {
				item = sp;
				break;
			}
		}
		o.func(v, id, item);
		keyVal = inp.val();
	}
	function multiCorrect(v, ch) {//������������ �������� ������ multi
		var multi = sel.find('.multi'),
			w = 0;
		multiCount = multi.length;
		for(n = 0; n < multiCount; n++) {
			var mw = multi.eq(n).width();
			if(w + mw > inpWidth + 4)
				w = 0;
			w += mw + 5 + 2;
		}
		w = inpWidth - w;
		inp.width(w < 25 ? inpWidth : w);
		if(v !== undefined) {
			assHide[v] = ch;
			spisokPrint();
			if(!o.title0 && v == 0 || v > 0)
				inp.val('');
		}
		title0bg[multiCount ? 'hide' : 'show']();
	}
	function findEm() {
		var v = inp.val();
		if(v && v.length) {
			var find = [];
				reg = new RegExp(v, 'i'); // ��� ������ ���������� ��������
			for(n = 0; n < o.spisok.length; n++) {
				var sp = o.spisok[n],
					arr = sp.content.split(tag); // �������� �� ������ �������� �����
				for(var k = 0; k < arr.length; k++) {
					var r = arr[k];
					if(r.length) // ���� ������ �� ������
						if(!tag.test(r)) // ���� ��� �� ���
							if(reg.test(r)) { // ���� ���� ����������
								arr[k] = r.replace(reg, '<em>$&</em>'); // ������������ ������
								sp.content = arr.join('');
								find.push(sp);
								break; // � ����� ����� �� �������
							}
				}
				if(o.limit && find.length == o.limit)
					break;
			}
			o.spisok = find;
		}
	}
	function hideOn() {
		if(!select.hasClass('rs')) {
			select.addClass('rs');
			unitSelShow();
			$(document)
				.on('click.' + id + '_select', hideOff)
				.on('keydown.' + id + '_select', spisokMove);
		}
	}
	function hideOff() {
		if(!inp.is(':focus')) {
			select.removeClass('rs');
			if(o.write && !val) {
				if(inp.val() && !o.write_save) {
					inp.val('');
					o.funcKeyup('');
				}
				setVal(0);
				o.func(0, id);
			}
			$(document)
				.off('click.' + id + '_select')
				.off('keydown.' + id + '_select');
		}
	}

	t.o = o;
	t.value = setVal;
	t.process = function() {//����� �������� �������� � selinp
		inp.addClass('_busy');
	};
	t.cancel = function() {//������ �������� �������� � selinp
		inp.removeClass('_busy');
	};
	t.spisok = function(v) {
		o.spisok = v;
		assCreate();
		spisokPrint();
		t.cancel();
	};
	t.title = function() {//��������� ����������� �������������� ��������
		return ass[t.val()];
	};
	t.inp = function() {//��������� ����������� ��������� ��������
		return inp.val();
	};
	t.focus = function() {//��������� ������ �� input
		inp.focus();
	};

	window[id + '_select'] = t;
	return t;
};
$.fn._selectColor = function(o) {//����� �������� ��� ������ ������
	var t = $(this),
		id = t.attr('id');

	o = $.extend({
		color_id:0,
		color_dop:0,
		func:function() {}
	}, o);


	var html =  '<input type="hidden" id="color_id" value="' + o.color_id + '" />' +
				'<span class="dn">' +
					'<tt>-</tt>' +
					'<input type="hidden" id="color_dop" value="' + o.color_dop + '" />' +
				'</span>';

	t.html(html).addClass('_select-color');

	var c1 = $('#color_id'),
		c2 = $('#color_dop'),
		dopSpan = t.find('span');

	c1._select({
		width:122,
		title0:'���� �� ������',
		spisok:COLOR_SPISOK,
		func:function(v) {
			dop(v);
			c2._select(0);
			c1._select(COLOR_SPISOK)
			  ._select(v);

		}
	});

	dop(o.color_id);

	c2._select({
		width:123,
		title0:'���� �� ������',
		spisok:COLOR_SPISOK,
		func:function(id) {
			c1._select(id ? COLORPRE_SPISOK : COLOR_SPISOK)
			  ._select(c1.val());
		}
	});


	function dop(v) {
		dopSpan[v ? 'show' : 'hide']();
	}
};
$.fn._rubric = function() {//����� �������� ��� ������ ������� � ����������
/*
	������������ � ������� input
	������ ������ ���� � ����� �� id + _sub
*/
	var t = $(this),
		attr_id = t.attr('id');

	if(!attr_id)
		return;

	var rub = $('#' + attr_id),
		sub = $('#' + attr_id + '_sub');

	rub._select({
		width:100,
		title0:'�� �������',
		spisok:RUBRIC_SPISOK,
		func:function(id) {
			sub.val(0)._select('remove');
			subPrint(id);
		}
	});

	$('#' + attr_id + '_select').css('margin-right', 5);

	subPrint(rub.val());

	function subPrint(id) {
		if(RUBRIC_SUB_SPISOK[id]) {
			sub._select({
				width:153,
				title0:'���������� �� �������',
				spisok:RUBRIC_SUB_SPISOK[id]
			});
		}
	}

};
$.fn.insertAtCaret = function(myValue){//������� ������ � ����� �������
    return this.each(function(i) {
        if (document.selection) {
            // ��� ��������� ���� Internet Explorer
            this.focus();
            var sel = document.selection.createRange();
            sel.text = myValue;
            this.focus();
        }
        else if (this.selectionStart || this.selectionStart == '0') {
            // ��� ��������� ���� Firefox � ������ Webkit-��
            var startPos = this.selectionStart;
            var endPos = this.selectionEnd;
            var scrollTop = this.scrollTop;
            this.value = this.value.substring(0, startPos)+myValue+this.value.substring(endPos,this.value.length);
            this.focus();
            this.selectionStart = startPos + myValue.length;
            this.selectionEnd = startPos + myValue.length;
            this.scrollTop = scrollTop;
        } else {
            this.value += myValue;
            this.focus();
        }
    })
};

$(document)
	.ajaxStart(_busy)
	.ajaxSuccess(function(event, request, settings) {
		_busy(0);
		var req = request.responseJSON;

		if(pinConfirm(req))
			return;

		if(!$('#_debug').length)
			return;

		var html = '',
			post =
				'<div class="hd ' + (req.success ? 'res1' : '') + (req.error ? 'res0' : '') + '">' +
					'<b>post</b>' +
					'<a id="repeat">������</a>' +
	 (req.success ? '<b id="res-success">success</b>' : '') +
	   (req.error ? '<b id="res-error">error</b>' : '') +
				'</div>',
			link = '<div class="hd"><b>link</b></div><textarea>' + req.link + '</textarea>',
			sql = '<div class="hd">sql <b>' + req.sql_count + '</b> (' + req.sql_time + ') :: php ' + req.php_time + '</div>';

		for(var i in req) {
			switch(i) {
				case 'success': break;
				case 'error': break;
				case 'php_time': break;
				case 'sql_count': break;
				case 'sql_time': break;
				case 'link': break;
				case 'post':
					for(var k in req.post)
						post += '<p><b>' + k + '</b>: ' + req.post[k];
					break;
				case 'sql':
					sql += '<ul>' + req[i] + '</ul>';
					break;
				default:
					var len = req[i].length ? '<tt>' + req[i].length + '</tt>' : '';
					html += '<div class="hd"><b>' + i + '</b>' + len + '<em>' + typeof req[i] + '</em></div>';
					if(typeof req[i] == 'object') {
						html += obj(req[i]);
						break;
					}
					html += '<textarea>' + req[i] + '</textarea>';
			}
		}
		$('#_debug .ajax').html(post + link + sql + html);
		$('#_debug .ajax textarea').autosize();
		$('#_debug #repeat').click(function() {
			var t = $(this).parent();
			if(t.hasClass('_busy'))
				return;
			t.addClass('_busy');
			$.post(req.link, req.post, function() {}, 'json');
		});
		window.FBH = FB.height();
		debugHeight();
		function obj(v) {
			var send = '<table>',
				i;
			for(i in v)
				send += '<tr><td class="val"><b>' + i + '</b>: ' +
							'<td>' + (typeof v[i] == 'object' ? obj(v[i]) : v[i]);
			send += '</table>';
			return send;
		}
	})
	.ajaxError(function(event, request, settings) {
		_busy(0);
		if(!request.responseText)
			return;
		var d = _dialog({
			width:600,
			top:10,
			head:'������ AJAX-�������',
			content:'<textarea style="width:570px;background-color:#fdd">' + request.responseText + '</textarea>',
			butSubmit:'',
			butCancel:'�������'
		});
		d.content.find('textarea').autosize();
	})

	.keydown(function(e) {//�������� �� ������
		if(scannerDialogShow)
			return;
//		if($('#scanner').length < 1)$('body').prepend('<div id="scanner"></div>');window.sc = $('#scanner');
		if(e.keyCode == 13) {
			var d = (new Date()).getTime(),
				time = d - scannerTime;
			if(scannerWord.length > 5 && time < 300) {
				scannerDialogShow = true;
				scannerTimer = setTimeout(timeStop, 500);
				scannerDialog = _dialog({
					head:'������ �����-����',
					width:250,
					content:'������� ���: <b>' + scannerWord + '</b>',
					butSubmit:'�����'
				});
				var send = {
					op:'scanner_word',
					word:scannerWord
				};
				scannerDialog.process();
				$.post(AJAX_MAIN, send, function(res) {
					if(res.success) {
						if(e.target.localName == 'input') {
							scannerDialog.close();
							return;
						}
						if(_cookie('p') == 'zayav' && _cookie('d') == 'add') {
							$('#' + (res.imei ? 'imei' : 'serial')).val(send.word);
							scannerDialog.close();
							return;
						}
						if(res.zayav_id)
							document.location.href = URL + '&p=zayav&d=info&id=' + res.zayav_id;
						else {
							var client_id = _cookie('p') == 'client' && _cookie('d') == 'info' ? _cookie('id') : 0;
							document.location.href =
								URL +
								'&p=zayav&d=add&' + (res.imei ? 'imei' : 'serial') + '=' + send.word +
								(client_id ? '&back=client&id=' + client_id : '');
						}
					} else
						scannerDialog.abort();
				}, 'json');
			}
//			sc.append('<br /> - Enter<br />len = ' + scannerWord.length + '<br />time = ' + time + '<br />');
		} else {
			if(scannerDialog) {
				scannerDialog.close();
				scannerDialog = undefined;
			}
			if(scannerTimer)
				clearTimeout(scannerTimer);
			scannerTimer = setTimeout(timeStop, 500);
			if(!scannerWord)
				scannerTime = (new Date()).getTime();
			scannerWord += charSpisok[e.keyCode] ||  '';
//			sc.append((charSpisok[e.keyCode] ||  '') + ' = ' + e.keyCode + ' - ' + ((new Date()).getTime() - scannerTime) + '<br />');
		}
		function timeStop() {
			scannerWord = '';
			scannerTime = 0;
			if(scannerTimer)
				clearTimeout(scannerTimer);
			scannerTimer = undefined;
			scannerDialogShow = false;
//			sc.append('<br /> - Clear<br />');
		}
	})

	.on('click', '#check_all_check', function() {//������� ���� ���������, ������� ����� ����
		/*
			check_all - ������� _check
			ch1, ch2, ... - ����������
		*/
		var v = !_num($(this).find('input').val());
		_checkAll('change', v);
	})

	.on('click', '#_info-top .img_del', function() {//�������� ��������� � ������������
		$(this).parent().slideUp();

		var send = {
				op:'manual_answer',
				manual_id:$(this).attr('val'),
				val:2
			};

		$.post(AJAX_MAIN, send, function() {}, 'json');

	})

	.on('click focus', '._note-area', function(e) {//�������������� ���� ������ � ����� ������
		e.stopPropagation();
		var t = $(this),
			p = t.parent().parent();
		if(p.hasClass('active'))
			return;

		p.addClass('active');

		var ph = t.attr('placeholder'),
			id = p.attr('id');

		t.attr('placeholder', '')
		 .height(28)
		 .autosize()
		 .keydown(function(e) {
			if(e.ctrlKey && e.keyCode == 13)
				p.find('.vk').trigger('click');
		 });

		$(document).on('click.' + id, function(e) {//������������ ���� ������ � ������� ������
			if(t.val())
				return;
			if(p.find('._note-img').html())
				return;

			$(document).off('click.' + id);

			t.attr('placeholder', ph).height(14);
			p.removeClass('active');
		});
	})
	.on('click', '._note-area-add .area-image', function(e) {//������� �� ������ ������ �����������
		e.stopPropagation();
		var t = $(this),
			img = t.parent().next(),
			key = t.attr('val'),
			send = {
				op:'image_obj_get',
				key:key
			};
		_imageAdd({
			key:key,
			func:function() {
				img.addClass('_busy').html('&nbsp;');
				$.post(AJAX_MAIN, send, function(res) {
					img.removeClass('_busy').html(res.success ? res.img : '');
					t.next().focus();
				}, 'json');
			}
		});
	})
	.on('click', '._note-area-add .vk', function(e) {//�������� ����� ������� ��� �����������
		e.stopPropagation();
		var t = $(this),
			p = t.parent(),
			area = p.find('._note-area'),
			note_id = _num(p.attr('val'));

		area.focus();

		if(t.hasClass('_busy'))
			return;

		var note = _parent(t, '._note').attr('val').split('#'),
			send = {
				op:note_id ? 'note_comment_add' : 'note_add',
				page_name:note[0],
				page_id:note[1],
				note_id:note_id,
				txt:$.trim(area.val()),
				key:area.prev().attr('val')
			};

		t.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			t.removeClass('_busy');
			if(res.success) {
				p.find('._note-img').html('');
				area.val('').focus();
				p[note_id ? 'before' : 'after'](res.html);
				$('.nu.deleted,.cu.deleted').remove();
				if(!note_id)
					p.parent().find('tt').html(res.count);
			}
		}, 'json');
	})
	.on('click', '.nu-go-comm', function() {//����� ���� ��� �����������
		var t = $(this),
			td = _parent(t, 'TD');
		td.find('h2').removeClass('dn')
		  .next().removeClass('dn');
		td.find('textarea').focus().autosize();
		t.remove();
	})

	.on('click', '._note .nu-del', function() {//�������� �������
		var t = $(this),
			nu = _parent(t, '.nu-tab').parent();

		if(nu.hasClass('busy'))
			return;

		var send = {
				op:'note_del',
				note_id:nu.attr('val')
			};
		nu.addClass('busy');
		$.post(AJAX_MAIN, send, function(res) {
			nu.removeClass('busy');
			if(res.success)
				nu.addClass('deleted');
		}, 'json');
	})
	.on('click', '._note .nu-rest a', function() {//�������������� �������
		var t = $(this),
			nu = t.parent().parent();

		if(t.hasClass('_busy'))
			return;

		var send = {
				op:'note_rest',
				note_id:nu.attr('val')
			};
		t.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			t.removeClass('_busy');
			if(res.success)
				nu.removeClass('deleted');
		}, 'json');
	})
	.on('click', '._note .cu-del', function() {//�������� �����������
		var t = $(this),
			cu = _parent(t, '.cu-tab').parent();

		if(cu.hasClass('busy'))
			return;

		var send = {
				op:'note_comment_del',
				id:cu.attr('val')
			};
		cu.addClass('busy');
		$.post(AJAX_MAIN, send, function(res) {
			cu.removeClass('busy');
			if(res.success)
				cu.addClass('deleted');
		}, 'json');
	})
	.on('click', '._note .cu-rest a', function() {//�������������� �����������
		var t = $(this),
			cu = t.parent().parent();

		if(t.hasClass('_busy'))
			return;

		var send = {
				op:'note_comment_rest',
				id:cu.attr('val')
			};
		t.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			t.removeClass('_busy');
			if(res.success)
				cu.removeClass('deleted');
		}, 'json');
	})

	.on('click', '.dlink.js .link', function() {//������������ ��������������� ������ ������
		var t = $(this),
			p = t.parent();

		p.find('.sel').removeClass('sel');
		var i = t.addClass('sel').index(),
			dlp = $('.dlink-page');

		dlp.addClass('dn');
		dlp.eq(i).removeClass('dn');
	})

	.on('click', '._check:not(.disabled)', function() {
		var t = $(this),
			inp = t.find('input'),
			prev = inp.val(),
			v = prev == 1 ? 0 : 1;
		t.removeClass('check' + prev);
		t.addClass('check' + v);
		inp.val(v);
	})
	.on('click', '._radio .on,._radio .off', function() {
		var t = $(this),
			p = _parent(t, '._radio'),
			v = t.attr('val');
		p.find('div.on').removeClass('on').addClass('off');
		t.removeClass('off').addClass('on');
		p.find('input:first').val(v);
	})

	.on('click', '._calendarFilter .week-num,._calendarFilter .on,._calendarFilter .data a', function() {
		var t = $(this),
			p = t.hasClass('week-num') ? t.parent() : t,
			sel = p.hasClass('sel'),
			cal = p,
			val = t.attr('val');
		while(!cal.hasClass('_calendarFilter'))
			cal = cal.parent();
		if(!sel)
			cal.find('.sel').removeClass('sel');
		p.addClass('sel');
		cal.find('.selected').val(val);
		if(window._calendarFilter)
			_calendarFilter(val, 'period');
	})
	.on('click', '._calendarFilter .ch', function() {
		var t = $(this),
			send = {
				op:'calendar_filter_rewind',
				month:t.attr('val')
			};
		while(!t.hasClass('_calendarFilter'))
			t = t.parent();
		if(t.hasClass('busy'))
			return;
		t.addClass('busy');
		send.func = t.find('.func').val();
		send.sel = t.find('.selected').val();
		send.noweek = t.find('.noweek').val();
		$.post(AJAX_MAIN, send, function(res) {
			t.removeClass('busy');
			if(res.success)
				t.find('.content').html(res.html);
		}, 'json');
	})

	.on('click', '._next', function() {//����������� ������. ��������������� ������� � PHP: _filterJs()
		var t = $(this),
			v = t.attr('val');

		if(!v)
			return;

		v = v.split(':');

		if(!v[1])
			return;

		if(t.hasClass('_busy'))
			return;
		t.addClass('_busy');

		window[v[0]].page = v[1];
		_scroll('page', _num(v[1])); //���������� ������ �������� ��� ������ ����� ����� ��� �����������
		$.post(AJAX_MAIN, window[v[0]], function(res) {
			if(res.success) {
				t.after(res.spisok).remove();
				_nextCallback();
			} else
				t.removeClass('_busy');
		}, 'json');
	})

	.ready(function() {
		FB = $('#frameBody');
		FH = $('#frameHidden');
		_fbhs();

		window.frameHidden.onresize = _fbhs;

		sortable();

		VK.callMethod('scrollWindow', _scroll());

		VK.callMethod('scrollSubscribe');
		VK.addCallback('onScroll', function(top) {
			VK_SCROLL = top;
		});
	});
