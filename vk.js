/* Необходимо предварительно указывать:
 - DOMAIN для fotoUpload
 - IMAGE_UPLOAD_PATH для fotoUpload
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
	REGEXP_CENA = /^[\d]+(.[\d]{1,2})?(,[\d]{1,2})?$/,
	REGEXP_DATE = /^(\d{4})-(\d{1,2})-(\d{1,2})$/,
	MONTH_DEF = {
		1:'Январь',
		2:'Февраль',
		3:'Март',
		4:'Апрель',
		5:'Май',
		6:'Июнь',
		7:'Июль',
		8:'Август',
		9:'Сентябрь',
		10:'Октябрь',
		11:'Ноябрь',
		12:'Декабрь'
	},
	MONTH_DAT = {
		1:'января',
		2:'февраля',
		3:'марта',
		4:'апреля',
		5:'мая',
		6:'июня',
		7:'июля',
		8:'августа',
		9:'сентября',
		10:'октября',
		11:'ноября',
		12:'декабря'
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
	_msg = function(txt) {//Сообщение о результе выполненных действий
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
			head:'head: Название заголовка',
			load:0, // Показ процесса ожидания загрузки в центре диалога
			content:'content: содержимое центрального поля',
			submit:function() {},
			cancel:function() {},
			butSubmit:'Внести',
			butCancel:'Отмена'
		}, obj);

		if(obj.load)
			obj.content = '<div class="load _busy"><div class="ms">В процессе загрузки произошла ошибка.</div></div>';
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

		// Если открывается первый диалог на странице, запоминается стартовая максимальная высота диалогов
		if(frameNum == 0)
			DIALOG_MAXHEIGHT = 0;

		var dialog = $('body').append(html).find('._dialog:last'),
			content = dialog.find('.content'),
			bottom = dialog.find('.bottom'),
			butSubmit = bottom.find('.vkButton');
		dialog.find('.head .img_del').click(dialogClose);
		butSubmit.find('button').click(obj.submit);
		bottom.find('.vkCancel').click(function() {
			obj.cancel();
			dialogClose();
		});

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
				butSubmit.addClass('_busy');
			},
			abort:function() {
				butSubmit.removeClass('_busy');
			},
			bottom:(function() {
				return bottom;
			})(),
			content:(function() {
				return content;
			})(),
			loadError:function() {
				dialog.find('.load').removeClass('_busy');
			}
		};
	};

$.fn.fotoUpload = function(obj) {
	obj = $.extend({
		owner:false,
		func:function() {}
	}, obj);

	if(!obj.owner)
		throw new Error('Не указан владелец изображения - <b>owner</b>');

	IMAGE_UPLOAD_PATH += '?' + VALUES + "&owner=" + obj.owner;
	var t = $(this),
		dialog,
		webDialog,
		timer,
		choose,
		direct,
		direct_a,
		webcam = {
			screen:null, // тег, в который помещается изображение с камеры
			show:function(width, height) { // вывод изображения на экран
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
				//this.screen.html(html);
			},
			reset:function() { //this.screen.html('');
			}
		};

	t.on('click', function() {
		var html = '<div id="fotoUpload">' +
			'<div class="info">Поддерживаются форматы JPG, PNG и GIF.</div>' +
			'<FORM method="post" action="' + IMAGE_UPLOAD_PATH + '" enctype="multipart/form-data" target="upload_frame">' +
			'<INPUT type="file" id="file_name" name="file_name" />' +
			'<INPUT type="hidden" name="op" value="file" />' +
			'</FORM>' +

			'<div id="choose_file">Выберите файл</div>' +
			'<IFRAME name="upload_frame"></IFRAME>' +
			'<div id="direct"><INPUT type="text" id="direct_input" placeholder="или укажите прямую ссылку на изображение.."><a><span>oтправить</span></a></div>' +
			'<div class="webcam">Вы также можете <A>сделать фотографию с вебкамеры »</A></div>' +
			'</div>';
		dialog = _dialog({
			top:80,
			head:"Загрузка изображения",
			content:html,
			butSubmit:null,
			butCancel:'Закрыть'
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

		// действие при загрузке изображения по прямой ссылке
		direct.keyEnter(fotoLinkSend);
		direct_a.click(fotoLinkSend);

		$('#fotoUpload .webcam a').click(camera);
	});

	function uploadStart() {
		var cookie = getCookie('fotoUpload');
		if(cookie != 'process') {
			if(webDialog)
				webDialog.close();
			choose.html("Выберите файл");
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
	// действие при успешном сохранении изображения на сервер
	function uploaded(link, x, y) {
		dialog.close();
		_msg("Изображение успешно загружено!");
		window.fotoViewImages = false;
		var send = {
			link:link,
			x:x,
			y:y,
			dtime:'сегодня'
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
	// вывод информации об ошибке в диалоговом окне
	function error_print(num) {
		$("#error_msg").remove();
		var cause = "не известна";
		if(num == 1) cause = 'неверный формат файла';
		if(num == 2) cause = 'слишком маленький размер изображения.<BR>Допустимый размер не менее 100x100 px';
		$('#fotoUpload .webcam').after('<div id="error_msg">Не удалось загрузить изображение.<BR>Причина: ' + cause + '.</div>');
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
	// диалог с вебкамерой
	function camera() {
		webDialog = _dialog({
			top:20,
			width:610,
			head:"Создание снимка с вебкамеры",
			content:'<div id="screen"></div>',
			butSubmit:'Сделать снимок',
			butCancel:'Закрыть',
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
	if(typeof o == 'number' || typeof o == 'string' || typeof o == 'boolean') {
		o = o ? 1 : 0;
		if(t.val() == o)
			return t;
		var prev = o == 1 ? 0 : 1;
		t.val(o);
		t.parent()
			.removeClass('check' + prev)
			.addClass('check' + o);
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
			func(parseInt(t.val()), t.attr('id'));
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

	o = $.extend({
		year:d.getFullYear(),	// если год не указан, то текущий год
		mon:d.getMonth() + 1,   // если месяц не указан, то текущий месяц
		day:d.getDate(),		// то же с днём
		lost:0,                 // если не 0, то можно выбрать прошедшие дни
		func:function () {},    // исполняемая функция при выборе дня
		place:'right'           // расположение календаря относительно выбора
	}, o);

	// если input hidden содежит дату, применение её
	if (REGEXP_DATE.test(val)) {
		var r = val.split('-');
		o.year = r[0];
		o.mon = Math.abs(r[1]);
		o.day = Math.abs(r[2]);
	}

	t.wrap('<div class="_calendar" id="' + id + '_calendar">');
	t.after('<div class="calinp">' + o.day + ' ' + MONTH_DAT[o.mon] + ' ' + o.year + '</div>' +
		'<div class="calabs"></div>');

	var	curYear = o.year,//дата,
		curMon = o.mon,  //установленная
		curDay = o.day,  //в input hidden
		inp = t.next(),
		calabs = inp.next(),//место для календаря
		calmon,             //место для месяца и года
		caldays;            //место для дней

	t.val(dataForm());
	inp.click(calPrint);

	function calPrint(e) {
		if(!calabs.html()) {
			e.stopPropagation();

			// если были открыты другие календари, то закрываются, кроме текущего
			var cals = $('.calabs');
			for(var n = 0; n < cals.length; n++) {
				var sp = cals.eq(n);
				if(sp.parent().attr('id').split('_calendar')[0] == id)
					continue;
				sp.html('');
			}

			// закрытие текущего календаря при нажатии на любое место экрана
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
					'<table class="calweeks"><tr><td>Пн<td>Вт<td>Ср<td>Чт<td>Пт<td>Сб<td>Вс</table>' +
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
	function daysPrint() {//вывод списка дней
		var n,
			html = '<tr>',
			year = d.getFullYear(),
			mon = d.getMonth() + 1,
			today = d.getDate(),
			df = dayFirst(o.year, o.mon),
			cur = year == o.year && mon == o.mon,// выделение текущего дня, если показан текущий год и месяц
			lost = o.lost == 0, // затемнение прошедших дней
			st = o.year == curYear && o.mon == curMon, // выделение выбранного дня
			dc = dayCount(o.year, o.mon);

		//установка пустых ячеек
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
	function dataForm() {//формирование даты в виде 2012-12-03
		return curYear +
			'-' + (curMon < 10 ? '0' : '') + curMon +
			'-' + (curDay < 10 ? '0' : '') + curDay;
	}
	function dayFirst(year, mon) {//номер первой недели в месяце
		var first = new Date(year, mon - 1, 1).getDay();
		return first == 0 ? 7 : first;
	}
	function dayCount(year, mon) {//количество дней в месяце
		mon--;
		if(mon == 0) {
			mon = 12;
			year--;
		}
		return 32 - new Date(year, mon, 32).getDate();
	}
	function back(e) {//пролистывание календаря назад
		e.stopPropagation();
		o.mon--;
		if(o.mon == 0) {
			o.mon = 12;
			o.year--;
		}

		calmon.html(MONTH_DEF[o.mon] + ' ' + o.year);
		daysPrint();
	}
	function next(e) {//пролистывание календаря вперёд
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

$.fn.years = function(obj) {// перелистывание годов
	obj = $.extend({
		year:(new Date()).getFullYear(),
		start:function () {},
		func:function () {},
		center:function () {}
	}, obj);

	var t = $(this);
	var id = t.attr('id');

	var html =
		'<div class="years" id="years_' + id + '">' +
			'<TABLE>' +
				'<TR><TD class="but">&laquo;' +
					'<TD id="ycenter"><SPAN>' + obj.year + '</SPAN>' +
					'<TD class="but">&raquo;' +
			'</TABLE>' +
		'</div>';
	t.after(html);
	t.val(obj.year);

	var years = {
		left:0,
		speed:2,
		span:$('#years_' + id + ' #ycenter SPAN'),
		width:Math.round($('#years_' + id + ' #ycenter').css('width').split(/px/)[0] / 2),  // ширина центральной части, где год
		ismove:0
	}
	years.next = function(side) {
		obj.start();
		var y = years;
		if(y.ismove == 0) {
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

	$('#years_' + id + ' #ycenter').click(obj.center);

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
	var t = $(this), p, n;
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
	var id = t.attr('id'),
		list = '',
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
			func(v);
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
		head:'',    // если указано, то ставится в название ссылки, а список из spisok
		headgrey:0,
		title0:'',
		spisok:[],
		func:function() {},
		nosel:0 // не вставлять название при выборе значения
	}, o);
	var n,
		val = t.val() * 1 || 0,
		ass = assСreate(),
		head = o.head || o.title0,
		len = o.spisok.length,
		spisok = o.title0 ? '<a class="ddu grey' + (!len ? ' last' : '') + (!val ? ' seld' : '') + '" val="0">' + o.title0 + '</a>' : '',
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
		'<div class="_dropdown" id="' + id + '_dropdown">' +
			'<a class="ddhead' + (o.headgrey || o.title0 ? ' grey' : '') + '">' + head + '</a>' +
			'<div class="ddlist">' +
				'<div class="ddsel">' + head + '</div>' +
				spisok +
			'</div>' +
		'</div>');
	var dropdown = t.next(),
		aHead = dropdown.find('.ddhead'),
		list = dropdown.find('.ddlist'),
		ddsel = list.find('.ddsel'),
		ddu = list.find('.ddu');
	aHead.click(function() {
		delayClear();
		list.show();
	});
	ddsel.click(function() {
		delayClear();
		list.hide();
	});
	ddu.click(function() {
		var th = $(this),
			v = parseInt(th.attr('val'));
		setVal(v);
		if(!o.nosel)
			th.addClass('seld');
		list.hide();
		o.func(v);
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

	function assСreate() {//Создание ассоциативного массива
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

(function () {// Подсказки vkHint 2013-02-14 14:43
	var Hint = function (t, o) { this.create(t, o); return t; };

	Hint.prototype.create = function (t, o) {
		o = $.extend({
			msg:'Сообщение подсказки',
			width:0,
			event:'mouseenter', // событие, при котором происходит всплытие подсказки
			ugol:'bottom',
			indent:'center',
			top:0,
			left:0,
			show:0,	  // выводить ли подсказку после загрузки страницы
			delayShow:0, // задержка перед всплытием
			delayHide:0, // задержка перед скрытием
			correct:0,   // настройка top и left
			remove:0	 // удалить подсказку после показа
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

		t.prev().remove('.hint'); // удаление предыдущей такой же подсказки
		t.before("<div class=hint>" + html + "</div>"); // вставка перед элементом

		var hi = t.prev(); // поле absolute для подсказки
		var hintTable = hi.find('.hint_table:first'); // сама подсказка
		if (o.width > 0) { hintTable.find('.cont_table:first').width(o.width); }

		var hint_width = hintTable.width();
		var hint_height = hintTable.height();

		hintTable.hide().css('visibility','visible');

		// установка направления всплытия и отступа для уголка
		var top = o.top; // установка конечного положения
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




		// отключение событий от предыдущей такой же подсказки
		t.off(o.event + '.hint');
		t.off('mouseleave.hint');

		// установка событий
		t.on(o.event + '.hint', show);
		t.on('mouseleave.hint', hide);
		hintTable.on('mouseenter.hint', show);
		hintTable.on('mouseleave.hint', hide);



		// процессы всплытия подсказки:
		// - wait_to_showind - ожидает показа (мышь была наведена)
		// - showing - выплывает
		// - show - показана
		// - wait_to_hidding - ожидает скрытия (мышь была отведена)
		// - hidding - скрывается
		// - hidden - скрыта
		var process = 'hidden';

		var timer = 0;

		// автоматический показ подсказки, если нужно
		if (o.show != 0) { show(); }

		// всплытие подсказки
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
			// действие всплытия подсказки
			function action() {
				process = 'showing';
				hintTable
					.css({top:o.top, left:o.left})
					.animate({top:top, left:left, opacity:'show'}, 200, showed);
			}
			// действие по завершению всплытия
			function showed() {
				process = 'show';
				if (o.correct != 0) {
					$(document).on('keydown.hint', function (e) {
						e.preventDefault();
						switch (e.keyCode) {
							case 38: o.top--; top--; break; // вверх
							case 40: o.top++; top++; break; // вниз
							case 37: o.left--; left--; break; // влево
							case 39: o.left++; left++; break; // вправо
						}
						hintTable.css({top:top, left:left});
						hintTable.find('#correct_top').html(o.top);
						hintTable.find('#correct_left').html(o.left);
					});
				}
			}
		} // end show




		// скрытие подсказки
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

$.fn._select = function(o) {
	var t = $(this),
		n,
		s,
		id = t.attr('id'),
		val = t.val() || 0;

	switch(typeof o) {
		default:
		case 'undefined': return t;
		case 'number':
		case 'string':
			s = window[id + '_select'];
			switch(o) {
				case 'process': s.process(); break;
				case 'cancel': s.cancel(); break;
				case 'title': return s.title();
				case 'remove': t.next().remove('._select'); break;
				default:
					if(REGEXP_NUMERIC.test(o))
						s.value(o);
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
		width:150,			// ширина
		block:false,       	// расположение селекта
		bottom:0,
		title0:'',			// поле с нулевым значением
		spisok:[],			// результаты в формате json
		write:false,        // возможность вводить значения
		nofind:'Список пуст',
		multiselect:0,      // возможность выбирать несколько значений. Идентификаторы перечисляются через запятую
		func:function() {},	// функция, выполняемая при выборе элемента
		funcAdd:null,		// функция добавления нового значения. Если не пустая, то выводится плюсик. Функция передаёт список всех элементов, чтобы можно было добавить новый
		funcKeyup:function() {}	// функция, выполняемая при вводе в INPUT в селекте. Нужна для вывода списка из вне, например, Ajax-запроса, либо из vk api.
	}, o);

	if(o.multiselect)
		o.write = true;

	var inpWidth = o.width - 17 - 5 - 4;
	if(o.funcAdd)
		inpWidth -= 18;
	var html =
		'<div class="_select" id="' + id + '_select" style="width:' + o.width + 'px' + (o.block ? ';display:block' : '') + (o.bottom ? ';margin-bottom:' + o.bottom + 'px' : '') + '">' +
			'<div class="title0bg">' + o.title0 + '</div>' +
			'<table class="seltab">' +
				'<tr><td class="selsel">' +
						'<input type="text" class="selinp" style="width:' + inpWidth + 'px' + (o.write ? '' : ';cursor:default') + '"' + (o.write ? '' : ' readonly') + ' />' +
	   (o.funcAdd ? '<td class="seladd">' : '') +
					'<td class="selug">' +
			'</table>' +
			'<div class="selres" style="width:' + o.width + 'px"></div>' +
		'</div>';
	t.next().remove('._select');
	t.after(html);

	var select = t.next(),
		res = select.find('.selres'),
		inp = select.find('.selinp'),
		sel = select.find('.selsel'),
		title0bg = select.find('.title0bg'),
		ass,            //Ассоциативный массив с названиями
		assHide = {},   //Ассоциативный массив с отображением в списке
		multiCount = 0, //Количество выбранных мульти-значений
		blur = 1,       //Разрешено ли выполнение blur
		keyVal = '';    //Вводимое значение из inp

	assСreate();

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
	if(o.funcAdd)
		select.find('.seladd').click(o.funcAdd);

	spisokPrint();
	setVal(val);

	$(document)
		.on('click', '#' + id + '_select .selug', hideOn)
		.on('click', '#' + id + '_select .selsel', hideOn)
		.on('click', '#' + id + '_select .selun', function() {
			var v = parseInt($(this).attr('val')),
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
		})
		.on('click', '#' + id + '_select .x', function(e) {
			e.stopPropagation();
			var v = $(this).attr('val');
			$(this).parent().remove();
			multiCorrect(v, false);
			setVal(v);
			o.func(v, id);
		});


	inp	.click(function(e) {
			e.stopPropagation();
			hideOn();
		})
		.blur(function() {
			if(o.write)
				title0bg.css('color', '#888');
		})
		.focus(function() {
			if(o.write)
				title0bg.css('color', '#ccc');
		})
		.keyup(function() {
			title0bg[inp.val() || multiCount ? 'hide' : 'show']();
			if(keyVal != inp.val()) {
				keyVal = inp.val();
				o.funcKeyup(keyVal);
				t.val(0);
			}
		});

	function spisokPrint() {
		if(!o.spisok.length) {
			res.html('<div class="nofind">' + o.nofind + '</div>');
			return;
		}
		if(o.write)
			findEm();
		var spisok = o.title0 && !o.write ? '<div class="selun title0" val="0">' + o.title0 + '</div>' : '';
		for(n = 0; n < o.spisok.length; n++) {
			var sp = o.spisok[n];
			if(assHide[sp.uid])
				continue;
			spisok +=
				'<div class="selun" val="' + sp.uid + '">' +
					(sp.content || sp.title) +
				'</div>';
		}
		res.removeClass('h250')
		   .html(spisok)
		   .find('.selun:last').addClass('last');
		if(res.height() > 250)
			res.addClass('h250');
	}
	function assСreate() {//Создание ассоциативного массива
		ass = o.title0 ? {0:''} : {};
		for(n = 0; n < o.spisok.length; n++) {
			var sp = o.spisok[n];
			ass[sp.uid] = sp.title;
			if(!sp.content)
				sp.content = sp.title;
		}
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
		} else {
			t.val(v);
			inp.val(ass[v]);
			title0bg[v == 0 ? 'show' : 'hide']();
		}
	}
	function multiCorrect(v, ch) {//Выравнивание значений списка multi
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
				inp.val('').focus();
		}
		title0bg[multiCount ? 'hide' : 'show']();
	}
	function findEm() {
		var v = inp.val();
		if(v.length) {
			var tag = /(<[\/]?[_a-zA-Z0-9=\"' ]*>)/i, // поиск всех тегов
				reg = new RegExp(v, 'i'); // для замены найденного значения
			for (n = 0; n < o.spisok.length; n++) {
				var sp = o.spisok[n],
					arr = sp.content.split(tag); // разбивка на массив согласно тегам
				for(var k = 0; k < arr.length; k++) {
					var r = arr[k];
					if(r.length) // если строка не пустая
						if(!tag.test(r)) // если это не тег
							if(reg.test(r)) { // если есть совпадение
								arr[k] = r.replace(reg, '<em>$&</em>'); // производится замена
								sp.content = arr.join('');
								break; // и сразу выход из массива
							}
				}
			}
		}
	}
	function hideOn() {
		if(!select.hasClass('rs')) {
			select.addClass('rs');
			$(document).on('click.' + id + '_select', hideOff);
		}
	}
	function hideOff() {
		select.removeClass('rs');
		if(o.write && t.val() == 0) {
			if(inp.val()) {
				inp.val('');
				o.funcKeyup('');
			}
			title0bg.show();
		}
		$(document).off('click.' + id + '_select');
	}

	t.value = setVal;
	t.process = function() {//Показ ожидания загрузки в selinp
		inp.addClass('_busy');
	};
	t.cancel = function() {//Отмена ожидания загрузки в selinp
		inp.removeClass('_busy');
	};
	t.spisok = function(v) {
		o.spisok = v;
		assСreate();
		spisokPrint();
		t.cancel();
	};
	t.title = function() {//Получение наименования установленного значения
		return ass[t.val()];
	};

	window[id + '_select'] = t;
	return t;
};

$(document)
	.ajaxError(function(event, request, settings) {
		if(!request.responseText)
			return;
		alert('Ошибка:\n\n' + request.responseText);
		//var txt = request.responseText;
		//throw new Error('<br />AJAX:<br /><br />' + txt + '<br />');
	})
	.on('click', '.debug_toggle', function() {
		var d = getCookie('debug');
		setCookie('debug', d == 1 ? 0 : 1);
		_msg('Debug включен.');
		document.location.reload();
	})
	.on('click', '#cache_clear', function() {
		$.post(AJAX_MAIN, {'op':'cache_clear'}, function(res) {
			if(res.success) {
				_msg('Кэш очищен.');
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
					.before('<div class="deleted">Заметка удалена. <a class="unit_rest" val="' + id + '">Восстановить</a></div>');
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
					.before('<div class="deleted">Комментарий удалён. <a class="child_rest" val="' + id + '">Восстановить</a></div>');
		}, 'json');
	})

	.on('click', '.fotoView', function() {
		$('#foto_view').remove();
		var t = $(this),
			html ='<div id="foto_view">' +
				'<div class="head"><EM><img src="/img/upload.gif"></EM><A>Закрыть</A></div>' +
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
			f.find('.head em').html(len > 1 ? 'Фотография ' + (num + 1) + ' из ' + len : 'Просмотр фотографии');
			f.find('.dtime').html('Добавлена ' + img.dtime);
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
			prev = inp.val(),
			v = prev == 1 ? 0 : 1;
		t.removeClass('check' + prev);
		t.addClass('check' + v);
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
		p[(sel ? 'remove' : 'add') + 'Class']('sel');
		cal.find('.selected').val(sel ? '' : val);
		if(window._calendarFilter)
			_calendarFilter(sel ? '' : val);
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
						'<TR><TD class="label">Страница:<TD><b>' + page + '</b>' +
						'<TR><TD class="label">Название:<TD><input type="text" id="name" maxlength="200">' +
						'<TR><TD class="label">Содержание:<TD>' +
						'<TR><td colspan="2"><textarea id="pagehelp_txt"></textarea>' +
						'</TABLE>';
			var dialog = _dialog({
				top:10,
				width:610,
				head:'Создание новой подсказки',
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
						msg:'<SPAN class="red">Не введено название</SPAN>',
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
							_msg('Внесёно!');
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
					head:'Информация о странице',
					load:html ? 0 : 1,
					content:html,
					butSubmit:'',
					butCancel:'Закрыть'
				});
			}
			function edit(res) {
				dialog.close();
				var html =
					'<TABLE class="pagehelp_tab">' +
						'<TR><TD class="label">Страница:<TD><b>' + res.page + '</b>' +
						'<TR><TD class="label">Название:<TD><input type="text" id="name" maxlength="200" value="' + res.name + '">' +
						'<TR><TD class="label">Содержание:<TD>' +
						'<TR><td colspan="2"><textarea id="pagehelp_txt">' + res.txt + '</textarea>' +
						'</TABLE>';
				dialog = _dialog({
					top:10,
					width:610,
					head:'Редактирование подсказки',
					content:html,
					butSubmit:'Сохранить',
					submit:function() {
						var send = {
							op:'pagehelp_edit',
							id:id,
							name:$('#name').val(),
							txt:$('#pagehelp_txt').val()
						};
						if(!send.name) {
							dialog.bottom.vkHint({
								msg:'<SPAN class="red">Не введено название</SPAN>',
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