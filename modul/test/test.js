var testBookUpdate = function(t) {
		var send = {
			op:'test_book_update',
			name:t.prev().val()
		};
		t.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				location.reload();
			else
				t.removeClass('_busy');
		}, 'json');
	},
	testWordFind = function(id) {//поиск слова в книге
		var send = {
				op:'test_word_find',
				id:id
			},
			str = $('#book-str');
		
		str.addClass('grey');
		$.post(AJAX_MAIN, send, function(res) {
			str.removeClass('grey')
			   .html('');
			if(res.success)
				str.html(res.str);
		}, 'json');
	},
	testWordSave = function(id) {//сохранение слова в словарь
		var send = {
			op:'test_word_save',
			id:id
		};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				location.reload();
		}, 'json');
	},
	testWordDel = function(id) {//удаление слова из книги
		var send = {
			op:'test_word_del',
			id:id
		};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success)
				location.reload();
		}, 'json');
	};


