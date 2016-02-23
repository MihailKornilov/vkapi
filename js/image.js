var _imageAdd = function(o) {
		o = $.extend({
			zayav_id:0,
			zp_id:0,
			manual_id:0,
			func:function() {
				location.reload();
			}
		}, o);

		var html =
			'<div id="_image-add-tab">' +
				'<div class="_info">' +
					'Вы можете загрузить изображения в форматах ' +
					'<b>JPG</b>, ' +
					'<b>PNG</b>, ' +
					'<b>GIF</b>, ' +
					'<b>TIFF</b>.' +
					'<br />' +
					'Минимальный размер изображения <b>100</b>x<b>100</b> пикселей.' +
					'<br />' +
					'Pазмер файла изображения не более <b>15</b> мб.' +
				'</div>' +
				'<h1>' +
					'<form method="post" action="' + AJAX_MAIN + '" enctype="multipart/form-data" target="image-frame">' +
						'<input type="hidden" name="op" value="image_upload" />' +
						'<input type="file" name="f1" id="file" />' +
						'<input type="hidden" name="zayav_id" value="' + o.zayav_id + '" />' +
						'<input type="hidden" name="zp_id" value="' + o.zp_id + '" />' +
						'<input type="hidden" name="manual_id" value="' + o.manual_id + '" />' +
					'</form>' +
					'<button class="vk">Выбрать изображение</button>' +
				'</h1>' +
				'<div id="image-error">&nbsp;</div>' +
				'<iframe name="image-frame"></iframe>' +
			'</div>',
			dialog = _dialog({
				top:40,
				width:410,
				head:'Загрузка нового изображения',
				content:html,
				butSubmit:''
			}),
			tab = $('#_image-add-tab'),
			but = tab.find('.vk'),
			form = tab.find('form'),
			file = $('#file'),
			error = $('#image-error'),
			timer,
			timer_count; //количество обращений, после которых ожидание загрузки файла прерывается

		file.change(file_change);

		function file_change() {
			file.css('visibility', 'hidden');
			but.addClass('_busy');
			_cookie('_uploaded', 0);
			_cookie('_uploaded_id', 0);
			timer = setInterval(upload_start, 500);
			timer_count = 120;
			form.submit();
		}
		function upload_start() {
			var c = _num(_cookie('_uploaded')),
				msg = 'неизвестная ошибка ';

			if(!--timer_count)
				c = 5;

			if(!c)
				return;

			clearInterval(timer);
			but.removeClass('_busy');
			file.css('visibility', 'visible');

			switch(c) {
				case 1: msg = 'неверный формат файла'; break;
				case 2: msg = 'слишком маленькое изображение'; break;
				case 4: msg = 'превышен размер файла'; break;
				case 5: msg = 'время ожидания истекло'; break;
				default: msg = msg + c;
			}

			if(c != 7) {
				error.html('<b>Ошибка</b>: ' + msg + '.')
					 .delay(3000)
					 .fadeOut(1000, function() {
						$(this).html('&nbsp;').show();
					 });
				return;
			}

			_msg('Изображение загружено');
			dialog.close();
			o.func();
		}
};

$.fn._image = function(o) {
	o = $.extend({
		op:'image_obj_get',
		zayav_id:0,
		zp_id:0,
		manual_id:0
	}, o);

	var t = $(this),
		html = '<div id="_image-obj">' +
			'<div id="im-spisok"></div>' +
			'<a id="im-add" class="_busy"><span>загрузить изображение</span></a>' +
		'</div>';

	t.html(html);

	var imAdd = t.find('a#im-add'),
		imSpisok = $('#im-spisok');

	imAdd.click(function() {
		o.func = spisokLoad;
		_imageAdd(o);
	});

	spisokLoad();

	function spisokLoad() {
		o.func = undefined;
		$.post(AJAX_MAIN, o, function(res) {
			imAdd.removeClass('_busy');
			if(res.success) {
				imSpisok.html(res.img);
				imSpisok.find('a')
					.off('contextmenu')
					.contextmenu(function() {
						$('#content').insertAtCaret('{img' + $(this).attr('val') + '}');
						return false;
					});
				imSpisok.find('.img_minidel').click(function(e) {
					e.stopPropagation();
					var p = _parent($(this), 'A'),
						send = {
							op:'image_del',
							id:p.attr('val')
						};

					if(p.hasClass('busy'))
						return;
					p.addClass('busy');

					$.post(AJAX_MAIN, send, function(res) {
						p.removeClass('busy');
						if(res.success)
							p.remove();
					}, 'json');
				});
			}
		}, 'json');
	}

	return t;
};

$(document)
	.on('click', '._iview', function(e) {
		e.stopPropagation();
		$('#_image-view').remove();
		var t = $(this),
			id = t.attr('val'),
			scroll = VK_SCROLL;
		if(t[0].tagName != 'IMG')
			t = t.find('img');
		var html = '<div id="_image-view">' +
					'<div class="head"><em class="_busy">&nbsp;</em><a>Закрыть</a></div>' +
					'<table class="image">' +
						'<tr><td><img src="' + t.attr('src').replace('-s.', '-b.') + '" />' +
					'</table>' +
					'<div class="about">' +
						'<a id="del"><tt>Удалить</tt></a>' +
						'<div id="del-ok">Фотография удалена</div>' +
						'<div class="dtime"></div>' +
					'</div>' +
					'<div class="hide"></div>' +
				'</div>';
		FB.append(html);

		var iv = $('#_image-view'),
			ivDel = iv.find('#del'),
			spisok = [],
			num = 1,
			id_cur = id; //id текущего изображения
		iHeightSet();
		iv.find('.head a').click(iclose);
		iv.find('img:first').on('load', iHeightSet);
		ivDel.click(idel);
		var send = {
			op:'image_view',
			id:id
		};
		$.post(AJAX_MAIN, send, function(res) {
			iv.find('._busy').removeClass('_busy');
			if(res.success) {
				spisok = res.img;
				num = res.n;
				ishow();
				iclick();
			}
		}, 'json');
/*
		function iNumGet() {//получение порядокового номера на основании текущего id
			for(var n = 0; n < spisok.length; n++)
				if(spisok[n].id == id_cur)
					return n;
			return 0;
		}
*/
		function ishow() {
			var len = spisok.length,
				numNext = num + 1 >= len ? 0 : num + 1,
				img = spisok[num];
			iv.find('.head em').html(len > 1 ? 'Фотография ' + (num + 1) + ' из ' + len : 'Просмотр фотографии');
			iv.find('.dtime').html('Добавлена ' + img.dtime);
			iv.find('.image img')
				.attr('src', img.link)
				.attr('width', img.x)
				.attr('height', img.y)
				.on('load', iHeightSet);
			iv.find('.hide').html('<img src="' + spisok[numNext].link + '">');
			iv[(img.deleted ? 'add' : 'remove') + 'Class']('deleted');
			id_cur = img.id;
		}
		function iclick() {
			iv.find('.image').on('click', function() {
				var len = spisok.length;
				if(len == 1)
					iclose();
				else {
					num++;
					if(num >= len)
						num = 0;
					ishow();
				}
			});
		}
		function iclose() {
			iv.remove();
			FOTO_HEIGHT = 0;
			_fbhs();
			VK.callMethod('scrollWindow', scroll);
		}
		function iHeightSet() {
			FOTO_HEIGHT = iv.height();
			_fbhs();
		}
		function idel() {//удаление изображения
			var send = {
				op:'image_del',
				id:id_cur
			};

			if(ivDel.hasClass('_busy'))
				return;
			ivDel.addClass('_busy');

			$.post(AJAX_MAIN, send, function(res) {
				ivDel.removeClass('_busy');
				if(res.success) {
					iv.addClass('deleted');
					spisok[num].deleted = 1;
				}
			}, 'json');
		}
	});


