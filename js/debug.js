$(document)
	.on('click', '.debug_toggle', function() {
		_cookie('debug', _cookie('debug') == 1 ? 0 : 1);
		_msg('Debug включен.');
		location.reload();
	})
	.on('click', '#_debug .sql-un', function() {
		var t = $(this),
			txt = '<div class="sql-hd">' +
					'time: ' + t.next().html() +
					'<a>Обновить</a>' +
					'<a>NOCACHE</a>' +
					'<a>EXPLAIN</a>' +
					'<h3></h3>' +
				  '</div>' +
				  '<textarea>' + t.html() + '</textarea>' +
				  '<div class="exp"></div>';
		t.parent()
		 .html(txt)
		 .find('textarea').select().autosize({callback:function() { debugHeight(); }});
		debugHeight();
	})
	.on('click', '#_debug .sql-hd a', function() {
		var t = $(this),
			p = t.parent(),
			h3 = p.find('h3'),
			send = {
				op:'debug_sql',
				query:p.next().val(),
				nocache:t.html() == 'NOCACHE' ? 1 : 0,
				explain:t.html() == 'EXPLAIN' ? 1 : 0
			};
		if(p.hasClass('_busy'))
			return;
		h3.html('');
		p.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			p.removeClass('_busy');
			if(res.success) {
				h3.html(res.html);
				if(res.exp)
					p.next().next().html(res.exp);
			}
		}, 'json');
	})
	.on('click', '#cookie_clear', function() {
		$.post(AJAX_MAIN, {'op':'cookie_clear'}, function(res) {
			if(res.success) {
				_msg('Cookie очищены');
				document.location.reload();
			}
		}, 'json');
	})
	.on('click', '#cache_clear', function() {
		$.post(AJAX_MAIN, {'op':'cache_clear'}, function(res) {
			if(res.success) {
				_msg('Кэш очищен');
				document.location.reload();
			}
		}, 'json');
	})

	.ready(function() {
		window.FBH = FB.height();
		debugHeight();
		$('#_debug h1').click(function() {
			var t = $(this).parent(),
				s = t.hasClass('show');
			t[(s ? 'remove' : 'add') + 'Class']('show');
			$(this).html(s ? '+' : '—');
			_cookie('debug_show', s ? 0 : 1);
			debugHeight(s);
		});
		$('#_debug .dmenu a').click(function() {
			var t = $(this),
				sel = t.attr('val');
			t.parent().find('.sel').removeClass('sel');
			t.addClass('sel');
			t.parent().parent()
				.find('.pg').addClass('dn').end()
				.find('.' + sel).removeClass('dn');
			_cookie('debug_pg', sel);
			debugHeight();
		});
		$('#cookie_update').click(function() {
			var t = $(this);
			t.addClass('_busy');
			$.post(AJAX_MAIN, {op:'debug_cookie'}, function(res) {
				t.removeClass('_busy');
				if(res.success)
					$('#cookie_spisok').html(res.html);
			}, 'json');
		});

		if($('#admin').length)
			$('#admin em').html(((new Date().getTime()) - TIME) / 1000);
	});
