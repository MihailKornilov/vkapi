/* ���������� �������������� ���������:
 - DOMAIN ��� fotoUpload
 - IMAGE_UPLOAD_PATH ��� fotoUpload
 - G (��������)
*/
var VK_SCROLL = 0,
	ZINDEX = 0,
	BC = 0,
	FB, // frameBody
	FH, // frameHidden
	FB_HEIGHT = 0,
	DIALOG_MAXHEIGHT = 0,
	FOTO_HEIGHT = 0,
	REGEXP_NUMERIC = /^\d+$/,
	REGEXP_CENA = /^[\d]+(.[\d]{1,2})?$/,
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
	URL = 'http://' + DOMAIN + '/index.php?' + VALUES,
	AJAX_MAIN = 'http://' + DOMAIN + '/ajax/main.php?' + VALUES,
	setCookie = function(name, value) {
		var exdate = new Date();
		exdate.setDate(exdate.getDate() + 1);
		document.cookie = name + '=' + value + '; path=/; expires=' + exdate.toGMTString();
	},
	getCookie = function(name) {
		var arr1 = document.cookie.split(name);
		if(arr1.length > 1) {
			var arr2 = arr1[1].split(/;/);
			var arr3 = arr2[0].split(/=/);
			return arr3[0] ? arr3[0] : arr3[1];
		} else
			return null;
	},
	delCookie = function(name) {
		var exdate = new Date();
		exdate.setDate(exdate.getDate()-1);
		document.cookie = name + '=; path=/; expires=' + exdate.toGMTString();
	},
	sortable = function() {
		$('._sort').sortable({
			axis:'y',
			update:function () {
				var dds = $(this).find('dd'),
					arr = [];
				for(var n = 0; n < dds.length; n++)
					arr.push(dds.eq(n).attr('val'));
				var send = {
					op:'sort',
					table:$(this).attr('val'),
					ids:arr.join()
				};
				$('#mainLinks').addClass('busy');
				$.post(AJAX_MAIN, send, function(res) {
					$('#mainLinks').removeClass('busy');
				}, 'json');
			}
		});
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
			if(BC == 0) {
				body.find('._backfon').remove().end()
					.append('<div class="_backfon"></div>');
			}
			body.find('._backfon').css({'z-index':ZINDEX});
			BC++;
		} else {
			BC--;
			ZINDEX -= 10;
			if(BC == 0)
				body.find('._backfon').remove();
			else
				body.find('._backfon').css({'z-index':ZINDEX});
		}
	},
	_msg = function(txt) {//��������� � �������� ����������� ��������
		var obj = $('#_msg');
		if(obj.length > 0)
			obj.remove();
		$('body').append('<div id=_msg>' + txt + '</div>');
		$('#_msg')
			.css('top', $(this).scrollTop() + 200 + VK_SCROLL)
			.delay(1200)
			.fadeOut(400, function() {
				$(this).remove();
			});
	},
	_dialog = function(obj) {
		var t = $(this),
			id = t.attr('id');
		obj = $.extend({
			width:360,
			top:100,
			head:'head: �������� ���������',
			load:0, // ����� �������� �������� �������� � ������ �������
			content:'content: ���������� ������������ ����',
			submit:function() {},
			cancel:function() {},
			butSubmit:'������',
			butCancel:'������'
		}, obj);

		if(obj.load)
			obj.content = '<div class="load busy"><div class="ms">� �������� �������� ��������� ������.</div></div>';
		var frameNum = $('.dFrame').length,
			html = '<div class="_dialog">' +
			'<div class="head"><div><A class="img_del"></A>' + obj.head + '</div></div>' +
			'<div class="dcntr">' +
				'<iframe class="dFrame" name="dFrame' + frameNum + '"></iframe>' +
				'<div class="content">' + obj.content + '</div>' +
			'</div>' +
			'<div class="bottom">' +
			(obj.butSubmit ? '<div class="vkButton"><button>' + obj.butSubmit + '</button></div>' : '') +
			(obj.butCancel ? '<div class="vkCancel"><button>' + obj.butCancel + '</button></div>' : '') +
			'</div>' +
		'</div>';

		// ���� ����������� ������ ������ �� ��������, ������������ ��������� ������������ ������ ��������
		if(frameNum == 0)
			DIALOG_MAXHEIGHT = 0;

		var dialog = $('body').append(html).find('._dialog:last'),
			butSubmit = dialog.find('.vkButton:last'),
			content = dialog.find('.content'),
			frame = dialog.find('.dFrame');
		dialog.find('.img_del').click(dialogClose);
		butSubmit.find('button').click(obj.submit);
		dialog.find('.vkCancel').click(function() { obj.cancel(); dialogClose(); });

		_backfon();

		dialog.css({
			width:obj.width + 'px',
			top:$(window).scrollTop() + VK_SCROLL + obj.top + 'px',
			left:313 - Math.round(obj.width / 2) + 'px',
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
			var dh = max + VK_SCROLL + 80;
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

		return {
			close:dialogClose,
			process:function() {
				butSubmit.addClass('busy');
			},
			abort:function() {
				butSubmit.removeClass('busy');
			},
			bottom:(function() {
				return dialog.find('.bottom');
			})(),
			content:(function() {
				return content;
			})(),
			loadError:function() {
				dialog.find('.load').removeClass('busy');
			}
		};
	};

$.fn.fotoUpload = function(obj) {
	obj = $.extend({
		owner:false,
		func:function() {}
	}, obj);

	if(!obj.owner)
		throw new Error('�� ������ �������� ����������� - <b>owner</b>');

	IMAGE_UPLOAD_PATH += '?' + VALUES + "&owner=" + obj.owner;
	var t = $(this),
		dialog,
		webDialog,
		timer,
		choose,
		direct,
		direct_a,
		webcam = {
			screen:null, // ���, � ������� ���������� ����������� � ������
			show:function(width, height) { // ����� ����������� �� �����
				var flashvars = 'shutter_enabled=0&width=' + width + '&height=' + height + '&server_width=' + width + '&server_height=' + height;
				var html = '<embed ' +
					'id="webcam_movie" ' +
					'width="' + width + '" ' +
					'height="' + height + '" ' +
					'src="http://' + DOMAIN + '/vk/webcam.swf" ' +
					'loop="false" ' +
					'menu="false" ' +
					'quality="best" ' +
					'bgcolor="#ffffff" ' +
					'name="webcam_movie" ' +
					'align="middle" ' +
					'allowScriptAccess="always" ' +
					'allowFullScreen="false" ' +
					'type="application/x-shockwave-flash" ' +
					'pluginspage="http://www.macromedia.com/go/getflashplayer" ' +
					'flashvars="' + flashvars + '" />';
				this.screen.html(html);
			},
			reset:function() { this.screen.html(''); }
		};

	t.on('click', function() {
		var html = '<div id="fotoUpload">' +
			'<div class="info">�������������� ������� JPG, PNG � GIF.</div>' +
			'<FORM method="post" action="' + IMAGE_UPLOAD_PATH + '" enctype="multipart/form-data" target="upload_frame">' +
			'<INPUT type="file" id="file_name" name="file_name" />' +
			'<INPUT type="hidden" name="op" value="file" />' +
			'</FORM>' +

			'<div id="choose_file">�������� ����</div>' +
			'<IFRAME name="upload_frame"></IFRAME>' +
			'<div id="direct"><INPUT type="text" id="direct_input" placeholder="��� ������� ������ ������ �� �����������.."><a><span>o��������</span></a></div>' +
			'<div class="webcam">�� ����� ������ <A>������� ���������� � ��������� �</A></div>' +
			'</div>';
		dialog = _dialog({
			top:80,
			head:"�������� �����������",
			content:html,
			butSubmit:null,
			butCancel:'�������'
		});
		var form = $("#fotoUpload form"),
			name = $("#file_name");
		choose = $("#choose_file");
		direct = $('#direct_input');
		direct_a = direct.next();

		if(/MSIE/.test(window.navigator.userAgent)) {
			name.on({
				mouseenter:function () { choose.css('background-color','#e9edf1'); },
				mouseleave:function () { choose.css('background-color','#eff1f3'); }
			});
		} else {
			choose
				.addClass('no_msie')
				.on('click', function() { name.click(); });
			form.hide();
		}

		name.change(function () {
			choose.html('&nbsp;<IMG src=/img/upload.gif>');
			setCookie('fotoUpload', 'process');
			timer = setInterval(uploadStart, 500);
			form.submit();
		});

		// �������� ��� �������� ����������� �� ������ ������
		direct.keyEnter(fotoLinkSend);
		direct_a.click(fotoLinkSend);

		$('#fotoUpload .webcam a').click(camera);
	});

	function uploadStart() {
		var cookie = getCookie('fotoUpload');
		if(cookie != 'process') {
			if(webDialog)
				webDialog.close();
			choose.html("�������� ����");
			clearInterval(timer);
			var arr = cookie.split('_');
			switch(arr[0]) {
				case 'uploaded':
					var param = getCookie('fotoParam').split('_');
					uploaded(param[0].replace(/%3A/, ':').replace(/%2F/g, '/'), param[1], param[2]);
					break;
				case 'error': error_print(arr[1]); break;
			}
		}
	}
	// �������� ��� �������� ���������� ����������� �� ������
	function uploaded(link, x, y) {
		dialog.close();
		_msg("����������� ������� ���������!");
		window.fotoViewImages = false;
		var send = {
			link:link,
			x:x,
			y:y,
			dtime:'�������'
		};
		if(obj.max_x && x > obj.max_x) {
			x = obj.max_x;
			y = Math.round(send.y / send.x * obj.max_x);
		}
		if(obj.max_y && y > obj.max_y) {
			y = obj.max_y;
			x = Math.round(send.x / send.y * obj.max_y);
		}
		send.img = '<IMG src="' + send.link + '-big.jpg" width="' + x + '" height="' + y + '">';
		obj.func(send);
	}
	// ����� ���������� �� ������ � ���������� ����
	function error_print(num) {
		$("#error_msg").remove();
		var cause = "�� ��������";
		if(num == 1) cause = '�������� ������ �����';
		if(num == 2) cause = '������� ��������� ������ �����������.<BR>���������� ������ �� ����� 100x100 px';
		$('#fotoUpload .webcam').after('<div id="error_msg">�� ������� ��������� �����������.<BR>�������: ' + cause + '.</div>');
	}

	function fotoLinkSend() {
		if(direct_a.hasClass('busy'))
			return;
		var link = direct.val();
		if(!link)
			return;
		var send = {
			op:'link',
			link:link
		};
		direct_a.addClass('busy');
		$.post(IMAGE_UPLOAD_PATH, send, function (res) {
			direct_a.removeClass('busy');
			if(res.error)
				error_print(res.error);
			else
				uploaded(res.link, res.x, res.y);
		}, 'json');
	}
	// ������ � ����������
	function camera() {
		webDialog = _dialog({
			top:20,
			width:610,
			head:"�������� ������ � ���������",
			content:'<div id="screen"></div>',
			butSubmit:'������� ������',
			butCancel:'�������',
			submit:submit
		});
		webDialog.content.css({
			padding:0,
			height:457 + 'px'
		});
		webcam.screen = $('#screen');
		webcam.show(608, 457);
		webDialog.content.resizable({
			minWidth: 322,
			maxWidth: 608,
			minHeight: 240,
			maxHeight: 457,
			resize:function(b, a) {
				var w = a.size.width;
				var diff = a.originalSize.width - w;
				if(diff != 0) {
					w -= diff;
					if(w < 322) w = 322;
					if(w > 608) w = 608;
					a.size.width = w;
					webDialog.content.parent().css({
						left:(313 - Math.round(a.size.width / 2)) + 'px',
						width:w + 'px'
					});
					$(this).width('auto');
				}
			},
			start:function() { webcam.reset(); },
			stop:function() {
				var h = webDialog.content.height();
				var w = webDialog.content.width();
				webcam.screen.height(h);
				webcam.show(w, h);
			}
		});

		function submit() {
			webDialog.process();
			setCookie('fotoUpload', 'process');
			timer = setInterval(uploadStart, 500);
			document.getElementById('webcam_movie')._snap(IMAGE_UPLOAD_PATH, 100, 0, 0);
		}
	}
};

$.fn._check = function(o) {
	var t = $(this);
	if(typeof o == 'number' || typeof o == 'string') {
		t.val(o).parent().attr('class', 'check' + o);
		return t;
	}

	if(typeof o == 'function') {
		_click(t, o);
		return t;
	}

	o = $.extend({
		name:'',
		func:function() {}
	}, o);

	var id = t.attr('id'),
		val = t.val() == 1 ? 1 : 0;
	t.val(val);
	t.wrap('<div class="check' + val + '" id="' + id + '_check">');
	t.after(o.name);
	_click(t, o.func);

	function _click(t, func) {
		var id = t.parent().attr('id');
		$(document).on('click', '#' + id, function() {
			func(t.val(), t.attr('id'));
		});
	}
	return t;
};
$.fn._radio = function(o) {
	var t = $(this), n;
	if(typeof o == 'number' || typeof o == 'string') {
		var p = t.parent();
		if(p.hasClass('_radio')) {
			p.find('div.on').attr('class', 'off');
			var div = p.find('div');
			for(n = 0; n < div.length; n++) {
				var eq = div.eq(n);
				if(o == eq.attr('val')) {
					eq.addClass('on');
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
		light:0,
		func:function() {}
	}, o);
	var id = t.attr('id'),
		list = '',
		val = t.val();
	for(n = 0; n < o.spisok.length; n++) {
		var sp = o.spisok[n],
			sel = val == sp.uid ? 'on' : 'off',
			l = o.light ? ' l' : '';
		list += '<div class="' + sel + l + '" val="' + sp.uid + '"><s></s>' + sp.title + '</div>';
	}
	t.wrap('<div class="_radio" id="' + id + '_radio">');
	t.after(list);
	_click(t, o.func);

	function _click(t, func) {
		var id = t.parent().attr('id');
		$(document).on('click', '#' + id, function() {
			func(t.val(), t.attr('id'));
		});
	}

	return t;
};

$.fn._search = function(o) {
	o = $.extend({
		width:126,
		focus:0,
		txt:'',
		func:function(){},
		enter:0
	}, o);
	var t = $(this),
		html = '<div class="_search" style="width:' + o.width + 'px">' +
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
				o.func(inp.val());
		});

	if(o.enter)
		inp.keydown(function(e) {
			if(e.which == 13)
				o.func($(this).val());
		});

	t.clear = function() {
		inp.val('');
		del.addClass('dn');
		hold.removeClass('dn');
	};

	del.click(function() {
		t.clear();
		o.func('');
	});

	_s.click(function() {
		inp.focus();
		holdFocus();
	});

	function holdFocus() { hold.css('color', '#ccc'); }
	function holdBlur() { hold.css('color', '#777'); }

	t.inp = function(v) {
		if(!v)
			return $.trim(inp.val());
		inp.val(v);
		del.removeClass('dn');
		hold.addClass('dn');
		return $(this);
	};
	return t;
};

$.fn._calendar = function(o) {
	var t = $(this),
		id = t.attr('id'),
		val = t.val(),
		d = new Date();

	// ���� input hidden ������� ����, ���������� �
	if (REGEXP_DATE.test(val)) {
		var r = val.split('-');
		o.year = r[0];
		o.mon = Math.abs(r[1]);
		o.day = Math.abs(r[2]);
	}

	o = $.extend({
		year:d.getFullYear(),	// ���� ��� �� ������, �� ������� ���
		mon:d.getMonth() + 1,   // ���� ����� �� ������, �� ������� �����
		day:d.getDate(),		// �� �� � ���
		lost:0,                 // ���� �� 0, �� ����� ������� ��������� ���
		func:function () {},    // ����������� ������� ��� ������ ���
		place:'right'           // ������������ ��������� ������������ ������
	}, o);

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

$.fn.years = function(obj) {// �������������� �����
	obj = $.extend({
		year:(new Date()).getFullYear(),
		start:function () {},
		func:function () {}
	}, obj);

	var t = $(this);
	var id = t.attr('id');

	var html = "<div class=years id=years_" + id + ">" +
		"<TABLE>" +
		"<TR><TD class=but>&laquo;<TD id=ycenter><SPAN>" + obj.year + "</SPAN><TD class=but>&raquo;" +
		"</TABLE></div>";
	t.after(html);
	t.val(obj.year);

	var years = {
		left:0,
		speed:2,
		span:$("#years_" + id + " #ycenter SPAN"),
		width:Math.round($("#years_" + id + " #ycenter").css('width').split(/px/)[0] / 2),  // ������ ����������� �����, ��� ���
		ismove:0
	};
	years.next = function (side) {
		obj.start();
		var y = years;
		if (y.ismove == 0) {
			y.ismove = 1;
			var changed = 0;
			var timer = setInterval(function () {
				var span = y.span;
				y.left -= y.speed * side;

				if (y.left > 0 && changed == 1 && side == -1 ||
					y.left < 0 && changed == 1 && side == 1) {
					y.left = 0;
					y.ismove = 0;
					y.speed = 0;
					clearInterval(timer);
				}

				span[0].style.left = y.left + 'px';
				y.speed += 2;

				if (y.left > y.width && changed == 0 && side == -1 ||
					y.left < -y.width && changed == 0 && side == 1) {
					changed = 1;
					obj.year += side;
					span.html(obj.year);
					y.left = y.width * side;
					t.val(obj.year);
					obj.func(obj.year);
				}
			}, 25);
		}
	};

	$("#years_" + id + " .but:first").mousedown(function () { allmon = 1; years.next(-1); });
	$("#years_" + id + " .but:eq(1)").mousedown(function () { allmon = 1; years.next(1); });
};
$.fn.keyEnter = function(func) {
	$(this).keydown(function(e) {
		if(e.keyCode == 13)
			func();
	});
	return $(this);
};

$.fn.rightLink = function(o) {
	var t = $(this), p;
	if(typeof o == 'number' || typeof o == 'string') {
		p = t.parent();
		if(p.hasClass('rightLink')) {
			p.find('.sel').removeClass('sel');
			var a = p.find('a');
			for(var n = 0; n < a.length; n++) {
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
	var id = t.attr('id'),
		list = '',
		val = t.val();
	for(var n = 0; n < o.spisok.length; n++) {
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
			func(v);
		});
	}
	return t;
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

	$.fn.vkHint = function (obj) { return new Hint($(this), obj); };
})();

$.fn.vkSel = function(obj) {
	var t = $(this);
	var id = t.attr('id');

	$("#vkSel_" + id).remove();	// �������� select ���� ����������

	$(document).off('click.results_hide').on('click.results_hide', function () {
		$(".vkSel")
			.find(".results").html('').end()
			.find(".ugol").css({'border-left':'#FFF solid 1px', 'background-color':'#FFF'});
		$(this)
			.off('keyup.vksel_esc')
			.off('keydown.vksel');
	});



	var obj = $.extend({
		width:150,			// ������
		bottom:0,			 // ������ �����
		display:'block',	 // ������������ �������
		title0:'',				 // ���� � ������� ���������
		spisok:[],			 // ���������� � ������� json
		spisok_new:null, // ����������� ������ ������, ���� ������������ ����� � ��������
		limit:0,				  // ����������� �� ����� ���������� �������. ���� 0 - ��� �����������
		value:$(this).val() || 0, // ������� ��������
		ro:1,					 // ������ ����� � ���� INPUT
		nofind:'�� �������',  // ���������, ��������� ��� ������ ������
		func:null,			  // �������, ����������� ��� ������ ��������
		funcAdd:null,		// ������� ���������� ������ ��������. ���� �� ������, �� ��������� ������. ������� ������� ������ ���� ���������, ����� ����� ���� �������� �����
		funcKeyup:null	 // �������, ����������� ��� ����� � INPUT � �������. ����� ��� ������ ������ �� ���, ��������, Ajax-�������, ���� �� vk api. ��� ���� ro ������ ���� = 0.
	}, obj);




	// ������������� ������ ����������� ������
	var ass; ass_create();




	var html = "<div class=vkSel id=vkSel_" + id + " style=width:" + obj.width + "px;display:" + obj.display + ";>";

	html += "<TABLE class=main style=width:" + obj.width + "px;>";
	var sel_width = obj.width - 17 - 4;
	if (obj.funcAdd) { sel_width -= 17; }
	html += "<TD class=selected style=width:" + sel_width + "px; val=inp_>";
	html += "<INPUT type=text class=inp style=width:" + (sel_width - 5) + "px;" + (obj.ro ? "cursor:default; readonly" : '') + " val=inp_>";
	if (obj.funcAdd) { html += "<TD class=add val=add_>"; }
	html += "<TD class=ugol val=ugol_>";
	html += "</TABLE>";
	html += "<div class=results style=width:" + obj.width + "px;></div>";
	html += "</div>";

	$(this).after(html);



	var vksel = $("#vkSel_" + id);	   // ���������� �������� �������
	var results = vksel.find(".results"); // ���������� ������ �� ���������
	var inp = vksel.find('.inp');			  // ���������� ������ �� ���� ��� �����
	var keyup = 0;							   // ������������ ������� ������� (�����, ����� �� ����������� ������ ��� ��� ������ ��� �������) ��� keyupFunc
	var keyup_val;							  // �������� ����������� �����. ���� ����������, �� ������ �����������.

	// ������ �����, ���� ����������
	if (obj.bottom > 0) { vksel.css('margin-bottom', obj.bottom + 'px'); }

	// ��������� �������� � INPUT
	var inp_set = function (val) {
		if (val !== undefined) { obj.value = val }
		if (obj.title0 && obj.value == 0) {
			inp.val(obj.title0).css('color', '#888');
		} else {
			inp.val(ass[obj.value]).css('color', '#000');
		}
		t.val(obj.value);
		return this;
	};

	// ��������� �������� � INPUT
	inp_set();

	// ���� �������� ���� � INPUT, ����������� ����� �� ������
	if (!obj.ro) {
		inp
			.on('keyup', function (e) {
				if(e.keyCode != 38 && e.keyCode != 40 && e.keyCode != 13) {
					if (obj.funcKeyup) {
						var val = inp.val();
						if (keyup == 0 && keyup_val != val) {
							keyup_val = val;
							vksel.find(".process_inp").remove();
							inp.before("<div class=process_inp style=width:" + (sel_width - 5) + "px;><IMG src=/img/upload.gif></div>");
							keyup = 1; // ������� ���� ������. ������ ���������� �����.
							obj.funcKeyup(val);
						}
					} else { inp_write(); }
				}
			})
			.on('blur', function () { inp_set(); });
	}


	// ����������� � ����� ��������
	vksel.on({
		mouseenter:function () { $(this).find('.ugol:first').css({'border-left':'#d2dbe0 solid 1px', 'background-color':'#e1e8ed'}); }, // ��������� �������������
		mouseleave:function () { if (results.find('DL').length == 0) { $(this).find('.ugol:first').css({'border-left':'#FFF solid 1px', 'background-color':'#FFF'}); } },
		click:function (e) {
			var val = $(e.target).attr('val');
			if (val) {
				var arr = val.split('_');
				switch (arr[0]) {
					case 'ugol': // ���� �� ������
						$(document).off('keyup.vksel_esc').off('keydown.vksel'); // ���������� �������� ���� ������ � ����� ������
						vksels_hide(e);
						if (!results.find('DL').length) {
							if (obj.spisok_new != null && obj.spisok_new.length == 0) { obj.spisok_new = null; } // ���� ����� �� �������� ������ ������ ������ � ���������� ���� �������, �� ������������ ���� ������
							dd_create();
						} else { results.html(''); } // ���� ������ ��� ������, �� ��������
						break;

					case 'add': // ���� �� �������.
						obj.spisok_new = null; // ������� ������, ���� ������������ ����� �� ������
						obj.funcAdd(obj.spisok, t.o);
						break;

					case 'inp': // ���� �� ������
						vksels_hide(e);
						if (obj.ro != 1 && obj.title0 && obj.value == 0) { inp.val('').css('color', '#000'); }
						if (results.find('DL:first').length == 0) {
							if (obj.spisok_new != null && obj.spisok_new.length == 0) { obj.spisok_new = null; } // ���� ����� �� �������� ������ ������ ������ � ���������� ���� �������, �� ������������ ���� ������
							dd_create();
						} else if (obj.ro != 1) { inp_write(); }
						break;

					case 'title0':
						inp_set(0);
						if (obj.func) { obj.func(obj.value); }
						break;

					case 'dd':
						inp_set(arr[1]);
						if (obj.func) { obj.func(obj.value); }
						break;
				}
			}
		}
	});






	// �������� ������ � ����� � ���������
	function dd_create() {
		var spisok = obj.spisok_new != null ? obj.spisok_new : obj.spisok;
		var dd = "<DL>";
		var len = (obj.limit > 0 && spisok.length > obj.limit) ? obj.limit : spisok.length;
		if (obj.title0 && obj.ro == 1) { dd += "<DD class='" + (obj.value == 0 ? 'over' : 'out') + " title0' val=title0_0>" + obj.title0; }
		if (len > 0) {
			var reg = new RegExp(">", "ig");
			for (var n = 0; n < len; n++) {
				var sp = spisok[n];
				var c = sp.uid == obj.value ? 'over' : 'out'; // ��������� ���������� ��������
				var cont = null; // ������� val � �������������� ���� ��������
				if (sp.content) { cont = sp.content.replace(reg," val=dd_" + sp.uid + ">"); }
				dd += "<DD class=" + c + " val=dd_" + sp.uid + ">" + (cont ? cont : sp.title);
			}
		} else if (obj.ro != 1) { dd += "<DT class=nofind>" + obj.nofind; }
		dd += "</DL>";
		results.html(dd);

		dd = results.find("DD");
		len = dd.length;
		if (len > 0) {
			// ���������� ������ ����������� ������
			var dl = results.find("DL");
			var over;
			var results_h = results.css('height').split(/px/)[0]; // ������ ������ ����������� �� ������� ������ ���������
			if (results_h > 250) {
				dl.css({height:250 + 'px', 'border-bottom':'#CCC solid 1px'});
				// ����������� ���������� ���� � ���� ���������
				over = results.find('.over:first')[0];
				if (over) {
					var top = over.offsetTop + over.offsetHeight;
					if(top > 170) {
						var dl_h = 250;
						if (results_h > top) { dl_h -= results_h - top > 120 ? 120 : results_h - top; }
						dl[0].scrollTop = top - dl_h;
					}
				}
			} else { results.find("DD:last").addClass('last'); }

			// ��������� ��������� ����� �������� ��� ��������� ����
			dd.on('mouseenter', function () {
				$(this).parent().find('.over:first').removeClass('over').addClass('out');
				$(this).addClass('over');
			});

			// ���� ���������� �������, �� ��������� ESC ��� ������� �����������
			$(document).on('keyup.vksel_esc', function (ev) {
				if (ev.keyCode == 27) {
					$(document).off('keyup.vksel_esc').off('keydown.vksel');
					results.html('');
				}
			});

			dl = dl[0];
			$(document).on('keydown.vksel',function (e) {
				for (var n = 0; n < len; n++) { if(dd.eq(n).hasClass('over')) break; }
				switch (e.keyCode) {
					case 38: // ����������� �����
						e.preventDefault();
						if (n == len) { n = 1; }
						if (n > 0) {
							if (len > 1) { dd.eq(n).removeClass('over').addClass('out'); } // ���� � ������ ������ ����� ��������
							over = dd.eq(n-1);
						} else { over = dd.eq(0); }
						over.removeClass('out').addClass('over');
						over = over[0];
						if (dl.scrollTop > over.offsetTop) { dl.scrollTop = over.offsetTop; } // ���� ������� ���� ����� ���� ���������, �������� � ����� ����
						if (over.offsetTop - 250 - dl.scrollTop + over.offsetHeight > 0) { dl.scrollTop = over.offsetTop - 250 + over.offsetHeight; } // ���� ����, �� ����
						break;

					case 40: // ����������� ����
						e.preventDefault();
						if (n == len) { dd.eq(0).removeClass('out').addClass('over'); dl.scrollTop = 0; }
						if (n < len - 1) {
							dd.eq(n).removeClass('over').addClass('out');
							over = dd.eq(n+1);
							over.removeClass('out').addClass('over');
							over = over[0];
							if (over.offsetTop + over.offsetHeight - dl.scrollTop > 250) { dl.scrollTop = over.offsetTop + over.offsetHeight - 250; } // ���� ������� ���� ���������, �������� � ������ �������
							if (over.offsetTop < dl.scrollTop) { dl.scrollTop = over.offsetTop; } // ���� ����, �� � �������
						}
						break;

					case 13: // �����
						e.preventDefault();
						if (n < len) {
							inp_set(dd.eq(n).attr('val').split('_')[1]);
							results.html('');
							if (obj.func) { obj.func(obj.value); }
						}
						break;
				}
			}); // end keydown.vksel
		} // end len > 0
	}










	// �������� �������������� �������
	function ass_create() {
		var arr = [];
		for (var n = 0; n < obj.spisok.length; n++) {
			var sp = obj.spisok[n];
			arr[sp.uid] = sp.title;
		}
		ass = arr;
	}







	// ������� ����������� ���� �������� ����� ��������
	function vksels_hide(e) {
		e.stopPropagation();
		var s = $(".vkSel");
		for (var n = 0; n < s.length; n++) {
			var sp = s.eq(n);
			if (sp.attr('id').split('vkSel_')[1] != id) {
				sp
					.find('.results').html('').end()
					.find(".ugol").css({'border-left':'#FFF solid 1px', 'background-color':'#FFF'});
			}
		}
	}

	// �������� ������ �� ����������� ��������� ��� ����� � INPUT
	function inp_write() {
		obj.value = 0;
		var val = inp.val();
		if (val.length > 0) {
			obj.spisok_new = [];
			var tag = new RegExp("(<[\/]?[_a-zA-Z0-9=\"' ]*>)", 'i'); // ����� ���� �����
			var reg = new RegExp(val, 'i'); // ��� ������ ���������� ��������
			for (var n = 0; n < obj.spisok.length; n++) {
				var sp = obj.spisok[n];
				var replaced = 0; // ���������� � �������� �� ������������� ������
				var find = sp.content || sp.title; // ��� ����� ������������� �����
				var arr = find.split(tag); // �������� �� ������ �������� �����
				for (var k = 0; k < arr.length; k++) {
					var r = arr[k];
					if(r.length > 0) { // ���� ������ �� ������
						if (!tag.test(r)) { // ���� ��� �� ���
							if (reg.test(r)) { // ���� ���� ����������
								arr[k] = r.replace(reg, "<EM>$&</EM>"); // ������������ ������
								replaced = 1; // ������� � ������
								break; // � ����� ����� �� �������
							}
						}
					}
				}
				if (replaced == 1) { // ���� ������ ����, �� ����������� ����� ������
					obj.spisok_new.push({
						uid:sp.uid,
						title:sp.title,
						content:arr.join('')
					});
				}
				if (obj.limit > 0 && obj.spisok_new.length >= obj.limit) break;
			}
		} else { obj.spisok_new = null; }
		dd_create();
	}




	// �������� � ������ ������ ��������. (��� �������� �������������)
	var item_add = function (item) {
		obj.spisok.unshift(item);
		ass[item.uid] = item.title;
		return this;
	};

	// ������������ ������ ��� ���������� ����������� � ��������
	t.o = {
		spisok:function (spisok) { // ��������� ���� ��������� ������
			if (spisok != undefined) {
				obj.spisok = spisok;
				ass_create();
				vksel.find(".process:first").remove();
				if (obj.funcKeyup) { // ���������� ������, ���� �������� ������� ��� ����� � inp
					vksel.find(".process_inp:first").remove();
					if (keyup == 1) {
						inp_write();
						keyup = 0;
					} else { inp_set(0); }
				} else { inp_set(0); }
				return this;
			} else { return obj.spisok; }
		},

		val:function(val) { // ��������� ���� ��������� ��������
			if(val != undefined) {
				inp_set(val);
				return this;
			} else { return obj.value; }
		},

		title:function() {
			return inp.val();
		},

		add:item_add, // ���������� ������ ��������

		process:function () { // ��������� � ������ �������� �������� ��������� ������ ������. ��� ���� ������ ������ ���������. �������� �������� = 0.
			inp_set(0);
			inp.val('');
			obj.spisok = [];
			vksel.find(".process").remove();
			inp.before("<div class=process><IMG src=/img/upload.gif></div>");
		},

		remove:function () { vksel.remove(); return this; },
		item:function(uid) {
			for(var n = 0; n < obj.spisok.length; n++) {
				var i = obj.spisok[n];
				if(i.uid == uid)
					return i;
			}
			return false;
		}
	};

	return t;
};

$.fn.linkMenu = function (obj) {
	/* ���������� ���� �� ������
	 * id ����������� �� INPUT hidden
	 */
	var obj = $.extend({
		head:'',	// ���� �������, �� �������� � �������� ������, � ������ �� spisok
		spisok:[],
		func:null,
		right:0	// ��������� ������ ��� ���
	},obj);

	var T = $(this);
	var idSel = T.val(); // ������� �������� � INPUT
	var selA = obj.head;  // ��������� ��� �� id
	var dl = '';
	var len = obj.spisok.length;
	for (var n = 0; n < len; n++) {
		dl += "<DD" + (n == len -1 ? ' class=last' : '') + " val=" + obj.spisok[n].uid + ">" + obj.spisok[n].title;
		if (idSel == obj.spisok[n].uid) {
			selA = obj.spisok[n].title;
		}
	}

	var attrId = "linkMenu_" + T.attr('id');
	var html = "<div class=linkMenu id=" + attrId + ">";
	html += "<A href='javascript:'>" + selA + "</A>";
	html += "<div class=fordl><DL><DT><EM>" + selA + "</EM>" + dl + "</DL></div>";
	html += "</div>";

	T.after(html);

	var ID = $("#" + attrId);
	var leftDl =  parseInt(ID.find('DL:first').css('left').split('px')[0]);

	ID.find("A:first").click(function () {
		var dd = getDD(T.val());
		if(dd) { dd.addClass('hover'); }
		$(this).next().show();
		if (obj.right) {
			var wDt = parseInt(ID.find("DT:first").css('text-align','right').css('width').split('px')[0]);
			var wEm = parseInt(ID.find('EM:first').css('width').split('px')[0]);
			ID.find('DL').css('left', (wEm - wDt + leftDl) + 'px');
		}
	});

	ID.find("DL").bind({
		mouseleave:function () {
			var forDL = $(this).parent();
			if(forDL.is(':visible')) {
				window.linkMenuDelay = window.setTimeout(function () { forDL.fadeOut(150); },500);
			}
		},
		mouseenter:function () {
			if (typeof window.linkMenuDelay == 'number') {
				window.clearTimeout(window.linkMenuDelay);
			}
		}
	});

	ID.find("DT").click(dlHide);

	ID.find("DD").bind({
		mouseenter:function () {
			ID.find(".hover").removeClass('hover');
			$(this).addClass('hover');
		},
		mouseleave:function () { $(this).removeClass('hover'); },
		click:function () {
			dlHide();
			var uid = $(this).attr('val');
			if (obj.func) { obj.func(uid); }
			// ���� head �� ������, �� ����� ������ ��� ��� ������
			if(!obj.head) {
				T.val(uid);
				var name = getDD(uid).html();
				ID.find("A:first").html(name);
				ID.find("DT:first").html(name);
			}
		}
	});

	function dlHide() { ID.find(".fordl").hide(); }

	function getDD (sel) {
		var dd = ID.find("DD");
		for (var n = 0; n < len; n++) {
			if (sel == obj.spisok[n].uid) {
				return dd.eq(n);
			}
		}
		return false;
	}
};

$(document)
	.ajaxError(function(event, request, settings) {
		if(!request.responseText)
			return;
		alert('������:\n\n' + request.responseText);
		//var txt = request.responseText;
		//throw new Error('<br />AJAX:<br /><br />' + txt + '<br />');
	})
	.on('click', '.debug_toggle', function() {
		var d = getCookie('debug');
		setCookie('debug', d == 1 ? 0 : 1);
		_msg('Debug �������.');
		document.location.reload();
	})
	.on('click', '#cache_clear', function() {
		$.post(AJAX_MAIN, {'op':'cache_clear'}, function(res) {
			if(res.success) {
				_msg('��� ������.');
				document.location.reload();
			}
		}, 'json');
	})

	.on('click focus', '.vkComment .add textarea,.vkComment .cadd textarea', function() {
		var t = $(this),
			but = t.next(),
			val = t.val();
		if(but.is(':hidden')) {
			t.val('')
				.attr('val', val)
				.css('color','#000')
				.height(26)
				.autosize();
			but.show()
				.css('display','inline-block');
		}
	})
	.on('blur', '.vkComment .add TEXTAREA,.vkComment .cadd TEXTAREA', function() {
		var t = $(this);
		if(!t.val()) {
			if(t.parent().parent().hasClass('empty')) {
				t.parent().parent().hide()
					.parent().find('span').show();
				return;
			}
			var val = t.attr('val');
			t.val(val)
				.css('color','#777')
				.height(13)
				.next().hide();
		}
	})
	.on('click', '.vkComment span a', function() {
		var t = $(this),
			cdop = t.parent().parent().next();
		t.parent().hide();
		cdop.show();
		if(cdop.hasClass('empty'))
			cdop.find('textarea').focus()
	})
	.on('click', '.vkComment .add .vkButton', function() {
		var t = $(this);
		if(t.hasClass('busy'))
			return;
		var val = t.parent().parent().attr('val').split('_'),
			send = {
				op:'vkcomment_add',
				table:val[0],
				id:val[1],
				txt:$.trim(t.prev().val())
			};
		if(!send.txt)
			return;
		t.addClass('busy');
		$.post(AJAX_MAIN, send, function(res) {
			t.removeClass('busy').hide();
			var val = t.prev().attr('val');
			t.prev()
				.val(val)
				.css('color', '#777')
				.height(13);
			t.parent().after(res.html);
		}, 'json');
	})
	.on('click', '.vkComment .cadd .vkButton', function() {
		var t = $(this);
		if(t.hasClass('busy'))
			return;
		var p = t.parent(),
			pid,
			val;
		for(var n = 0; n < 10; n++) {
			p = p.parent();
			if(p.hasClass('cunit'))
				pid = p.attr('val');
			if(p.hasClass('vkComment')) {
				val = p.attr('val').split('_');
				break;
			}
		}
		var send = {
			op:'vkcomment_add_child',
			table:val[0],
			id:val[1],
			txt:$.trim(t.prev().val()),
			parent:pid
		};
		if(!send.txt)
			return;
		t.addClass('busy');
		$.post(AJAX_MAIN, send, function(res) {
			t.removeClass('busy').hide();
			var val = t.prev().attr('val');
			t.prev()
				.val(val)
				.css('color', '#777')
				.height(13);
			t.parent().before(res.html)
				.parent().removeClass('empty');
		}, 'json');
	})
	.on('click', '.vkComment .unit_del', function() {
		var u = $(this);
		while(!u.hasClass('cunit'))
			u = u.parent();
		if(u.hasClass('busy'))
			return;
		var id = u.attr('val'),
			send = {
				op:'vkcomment_del',
				id:id
			};
		u.addClass('busy');
		$.post(AJAX_MAIN, send, function(res) {
			u.removeClass('busy');
			if(res.success)
				u.find('table:first').hide()
					.before('<div class="deleted">������� �������. <a class="unit_rest" val="' + id + '">������������</a></div>');
		}, 'json');
	})
	.on('click', '.vkComment .unit_rest,.vkComment .child_rest', function() {
		var t = $(this);
		if(t.hasClass('busy'))
			return;
		var send = {
			op:'vkcomment_rest',
			id:t.attr('val')
		};
		t.addClass('busy');
		$.post(AJAX_MAIN, send, function(res) {
			t.parent().next().show();
			t.parent().remove()
		}, 'json');
	})
	.on('click', '.vkComment .child_del', function() {
		var p = $(this);
		while(!p.hasClass('child'))
			p = p.parent();
		if(p.hasClass('busy'))
			return;
		var id = p.attr('val'),
			send = {
				op:'vkcomment_del',
				id:id
			};
		p.addClass('busy');
		$.post(AJAX_MAIN, send, function(res) {
			p.removeClass('busy');
			if(res.success)
				p.find('table:first').hide()
					.before('<div class="deleted">����������� �����. <a class="child_rest" val="' + id + '">������������</a></div>');
		}, 'json');
	})

	.on('click', '.fotoView', function() {
		$('#foto_view').remove();
		var t = $(this),
			html ='<div id="foto_view">' +
				'<div class="head"><EM><img src="/img/upload.gif"></EM><A>�������</A></div>' +
				'<table class="image"><tr><td><img src="' + t.attr('src').replace('small', 'big') + '"></table>' +
				'<div class="about"><div class="dtime"></div></div>' +
				'<div class="hide"></div>' +
				'</div>';
		FB.append(html);

		var f = $('#foto_view');
		fotoHeightSet();
		f.find('.head a').on('click', fotoClose);

		var owner = t.attr('val'),
			send = {
				op:'foto_load',
				owner:owner
			};
		if(!window.fotoViewImages || window.fotoViewOwner != owner) {
			$.post(AJAX_MAIN, send, function(res) {
				window.fotoViewImages = res.img;
				window.fotoViewNum = 0;
				window.fotoViewOwner = owner;
				fotoShow();
				fotoClick();
			}, 'json');
		} else {
			fotoShow();
			fotoClick();
		}


		function fotoShow() {
			var len = window.fotoViewImages.length,
				num = window.fotoViewNum,
				nextNum = num + 1 >= len ? 0 : num + 1,
				img = window.fotoViewImages[num];
			f.find('.head em').html(len > 1 ? '���������� ' + (num + 1) + ' �� ' + len : '�������� ����������');
			f.find('.dtime').html('��������� ' + img.dtime);
			f.find('.image img')
				.attr('src', img.link)
				.attr('width', img.x)
				.attr('height', img.y)
				.on('load', fotoHeightSet);
			f.find('.hide').html('<img src="' + window.fotoViewImages[nextNum].link + '">');
		}
		function fotoClick() {
			f.find('.image').on('click', function() {
				var len = window.fotoViewImages.length;
				if(len == 1)
					fotoClose();
				else {
					window.fotoViewNum++;
					if(window.fotoViewNum >= len)
						window.fotoViewNum = 0;
					fotoShow();
				}
			});
		}
		function fotoClose() {
			window.fotoViewNum = 0;
			f.remove();
			FOTO_HEIGHT = 0;
			_fbhs();
		}
		function fotoHeightSet() {
			FOTO_HEIGHT = f.height();
			_fbhs();
		}
	})

	.on('click', '.check0,.check1', function() {
		var t = $(this),
			inp = t.find('input'),
			v = inp.val() == 1 ? 0 : 1;
		t.attr('class', 'check' + v);
		inp.val(v);
	})
	.on('click', '._radio .on,._radio .off', function() {
		var t = $(this),
			p = t.parent(),
			v = t.attr('val');
		p.find('div.on').removeClass('on').addClass('off');
		t.removeClass('off').addClass('on');
		p.find('input:first').val(v);
	})

	.ready(function() {
		VK.callMethod('scrollWindow', 0);
		VK.callMethod('scrollSubscribe');
		VK.addCallback('onScroll', function(top) { VK_SCROLL = top; });

		FB = $('#frameBody');
		FH = $('#frameHidden');
		_fbhs();
		sortable();

		$('.pagehelp_create').click(function() {
			var t = $(this),
				page = t.attr('val'),
				html =
					'<TABLE class="pagehelp_tab">' +
						'<TR><TD class="label">��������:<TD><b>' + page + '</b>' +
						'<TR><TD class="label">��������:<TD><input type="text" id="name" maxlength="200">' +
						'<TR><TD class="label">����������:<TD>' +
						'<TR><td colspan="2"><textarea id="pagehelp_txt"></textarea>' +
						'</TABLE>';
			var dialog = _dialog({
				top:10,
				width:610,
				head:'�������� ����� ���������',
				content:html,
				submit:submit
			});
			$('#name').focus();
			$('#pagehelp_txt').autosize();
			function submit() {
				var send = {
					op:'pagehelp_add',
					page:page,
					name:$('#name').val(),
					txt:$('#pagehelp_txt').val()
				};
				if(!send.name) {
					dialog.bottom.vkHint({
						msg:'<SPAN class="red">�� ������� ��������</SPAN>',
						remove:1,
						indent:40,
						show:1,
						top:-48,
						left:217
					});
					$('#name').focus();
				} else {
					dialog.process();
					$.post(AJAX_MAIN, send, function (res) {
						if(res.success) {
							dialog.close();
							_msg('������!');
							document.location.reload();
						} else
							dialog.abort();
					}, 'json');
				}
			}
		});
		$('#mainLinks .img_pagehelp').click(function() {
			var t = $(this),
				id = t.attr('val'),
				dialog,
				send = {
					op:'pagehelp_get',
					id:id
				};
			pagehelp_get();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.content
						.html(html_create(res))
						.find('.add').click(function() {
							edit(res);
						});
				} else
					dialog.loadError();
			}, 'json');

			function html_create(res) {
				return '<div class="headName">' + res.name + res.edit + '</div>' +
					'<div class="pagehelp_show_txt">' + res.txt + res.dtime + '</div>';
			}
			function pagehelp_get(html) {
				dialog = _dialog({
					top:10,
					width:610,
					head:'���������� � ��������',
					load:html ? 0 : 1,
					content:html,
					butSubmit:'',
					butCancel:'�������'
				});
			}
			function edit(res) {
				dialog.close();
				var html =
					'<TABLE class="pagehelp_tab">' +
						'<TR><TD class="label">��������:<TD><b>' + res.page + '</b>' +
						'<TR><TD class="label">��������:<TD><input type="text" id="name" maxlength="200" value="' + res.name + '">' +
						'<TR><TD class="label">����������:<TD>' +
						'<TR><td colspan="2"><textarea id="pagehelp_txt">' + res.txt + '</textarea>' +
						'</TABLE>';
				dialog = _dialog({
					top:10,
					width:610,
					head:'�������������� ���������',
					content:html,
					butSubmit:'���������',
					submit:function() {
						var send = {
							op:'pagehelp_edit',
							id:id,
							name:$('#name').val(),
							txt:$('#pagehelp_txt').val()
						};
						if(!send.name) {
							dialog.bottom.vkHint({
								msg:'<SPAN class="red">�� ������� ��������</SPAN>',
								remove:1,
								indent:40,
								show:1,
								top:-48,
								left:217
							});
							$('#name').focus();
						} else {
							dialog.process();
							$.post(AJAX_MAIN, send, function() {
								if(res.success) {
									dialog.close();
									res.name = send.name;
									res.txt = send.txt;
									pagehelp_get(html_create(res));
									dialog.content.find('.add').click(function() {
										edit(res);
									});
								} else
									dialog.abort();
							}, 'json');
						}
					}
				});
				$('#name').focus();
				$('#pagehelp_txt').autosize();
			}
		});

		window.frameHidden.onresize = _fbhs;

		if($('#admin').length > 0)
			$('#admin em').html(((new Date().getTime()) - TIME) / 1000);
	});