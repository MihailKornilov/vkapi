var _tovarAdd = function(o) {
		o = _tovarEditExtend(o);
		var html =
			'<div class="_info" id="info-main">' +
				'Внимательно заполните поля! ' +
				'После внесения товара будет создана карточка товара. ' +
				'Поля, помеченные звёздочкой, обязательны для заполнения.<br />' +
//				'Для более подробного ознакомления с правилами внесения нового товара читайте в Мануале.' +
			'</div>' +
			_tovarEditLabelCat(o) +

			'<div id="ta-name" class="dn">' +
				'<div class="_info">' +
					'<p>В поле <b>Наименование</b> выберите название товара.' +
						'<br />' +
						'Это слово или словосочетание, которое коротко описывает товар, то есть отвечает на вопрос: <u>что это?</u>' +
						'<br />' +
						'Примеры: <u>Пицца</u>, <u>Мобильный телефон</u>, <u>Парник</u>, <u>Пластиковое окно</u> и тп.' +
						'<br />' +
						'Если в выпадающем списке нет нужного названия, укажите своё.' +
					'<p><b>Производителя</b> товара указывать не обязательно.' +
					'<p>В поле <b>Подробно</b> пишется уточнение, модель, версия или короткое описание товара.' +
				'</div>' +
				_tovarEditLabelName(o) +
			'</div>' +

			'<div id="ta-set" class="dn">' + _tovarEditLabelSet(o) + '</div>' +
			'<div id="ta-dop" class="dn">' + _tovarEditLabelDop(o) + '</div>',

			dialog = _dialog({
				top:20,
				width:600,
				head:'Внесение нового товара',
				class:'tovar-add',
				content:html,
				butSubmit:''
			});

		dialog.content.find('#ta-set .headName').after(
			'<div class="_info">' +
				'Укажите, применяется ли этот товар к другому товару. ' +
				'То есть является неотъемлемой частью.' +
				'<p><b>Например:</b>' +
				'<br />' +
				' - это <u>запчасть</u> (матрица от ноутбука);<br />' +
				' - это <u>комплектующее</u> (петля от двери);<br />' +
				' - это <u>аксессуар</u> (чехол для телефона);<br />' +
				' - это <u>ингридиент</u> (сыр для пиццы). И тп.' +
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
				head:'Редактирование товара',
				class:'tovar-add',
				content:html,
				butSubmit:'Сохранить',
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
		return '<div class="headName">Основные данные товара:</div>' +
			'<table class="bs5">' +
				'<tr><td class="label w70 r">Категория:*' +
					'<td><input type="hidden" id="category_id-add" value="' + o.category_id + '" />' +
			'</table>';
	},
	_tovarEditLabelName = function(o) {
		return  '<table id="tab-name">' +
					'<tr><td class="label">Наименование:*' +
						'<td class="label td-vendor">' +
							'Производитель:' +
							'<div id="no-vendor" class="img_del' + _tooltip('Производителя нет', -61) + '</div>' +
						'<td class="label">Подробно:' +
					'<tr><td><input type="hidden" id="name_id-add" value="' + o.name_id + '" />' +
						'<td class="td-vendor"><input type="hidden" id="vendor_id-add" value="' + o.vendor_id + '" />' +
						'<td><input type="text" id="name" value="' + o.name + '" />' +
				'</table>';
	},
	_tovarEditLabelSet = function(o) {
		var dn = o.set == 1 ? '' : ' dn';
		return '<div class="headName">Применение к другому товару:</div>' +
			'<table class="bs10" id="tab-set">' +
				'<tr><td class="label r">Применяется к другому товару:*' +
					'<td id="td-set"><input type="hidden" id="tovar_set-add" value="' + o.set + '" />' +
				'<tr class="tr-set' + dn + '"><td class="label r tdset2">Чем является:*' +
					'<td><input type="hidden" id="set_position_id" value="' + o.set_position_id + '" />' +
				'<tr class="tr-set' + dn + '"><td class="label topi r tdset3">Применяется к товару:*' +
					'<td><input type="hidden" id="te-tovar_id_set" value="' + o.tovar_id_set + '" />' +
			'</table>';
	},
	_tovarEditLabelDop = function(o) {
		return '<div class="headName" id="head-dop">Дополнительные характеристики:</div>' +
			'<table class="bs10" id="tab-dop">' +
				'<tr><td class="label r">Единица изменения:*<td><input type="hidden" id="measure_id" value="' + o.measure_id + '" />' +
				'<tr><td class="label r">Закупочная стоимость:<td><input type="text" class="money" id="cost_buy" value="' + o.cost_buy + '" /> руб.' +
				'<tr><td class="label r">Цена продажи:<td><input type="text" class="money" id="cost_sell" value="' + o.cost_sell + '" /> руб.' +
				'<tr><td class="label topi r">Описание товара:<td><textarea id="about">' + _br(o.about) + '</textarea>' +
			'</table>' +
			'<input type="hidden" id="feature" />';
	},
	_tovarEditFunc = function(o, dialog, submit) {
		$('#category_id-add')._select({
			width:300,
			title0:'категория не указана',
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
				dialog.butSubmit('Далее');
				dialog.submit(step2);
			}
		});

		$('#name_id-add')._select({
			width:180,
			title0:'не указано',
			spisok:[],
			write:1,
			write_save:1
		});
		if(o.category_id)
			categoryLoad(o.category_id, o.name_id);
		$('#vendor_id-add')._select({
			width:150,
			title0:'не выбран',
			spisok:TOVAR_VENDOR_SPISOK,
			write:1,
			write_save:1,
			func:function() {
				$('#name').focus();
			}
		});
		$('#no-vendor').click(function() {//скрытие производителя, если нет
			$('#vendor_id-add')._select(0);
			$('.td-vendor').hide();
			$('#name').width(370).focus();
		});

		$('#tovar_set-add')._radio({
			light:1,
			block:0,
			spisok:[
				{uid:1,title:'да'},
				{uid:0,title:'нет'}
			],
			func:step3
		});
		$('#set_position_id')._select({
			title0:'не выбрано',
			spisok:[
				{uid:1,title:'запчастью'},
				{uid:2,title:'комплектующим'},
				{uid:3,title:'аксессуаром'},
				{uid:4,title:'ингридиентом'}
			]
		});
		$('#te-tovar_id_set').tovar({
			set:0
		});

		$('#measure_id')._select({
			width:170,
			spisok:[
				{uid:1,title:'шт.',content:'шт. - количество'},
				{uid:2,title:'м.',content:'м. - длина в метрах'},
				{uid:3,title:'мм.',content:'мм. - длина в миллиметрах'}
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
		function step2() {//шаг 2: выбор названия товара и производителя
			if(!_tovarEditValues(dialog))
				return;
			$('#ta-name ._info').slideUp();
			$('#ta-set').slideDown();
			dialog.butSubmit('');
		}
		function step3(v) {//шаг 3: применение товара к другому товару
			$('.tr-set')[(v ? 'remove' : 'add') + 'Class']('dn');
			if(window.TI)
				return;
			$('#ta-set ._info').slideUp();
			$('#ta-dop').slideDown();
			dialog.butSubmit('Внести товар в каталог');
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
			dialog.err('Не указана категория');
			return false;
		}
		if(!send.name_id && !send.name_name) {
			dialog.err('Не указано наименование товара');
			$('#name_id-add')._select('focus');
			return false;
		}
		if(send.set) {
			if(!send.set_position_id) {
				dialog.err('Укажите отношение товара');
				$('.tdset2').addClass('tderr');
				return false;
			}
			if(!send.tovar_id_set) {
				dialog.err('Не выбран товар');
				$('.tdset3').addClass('tderr');
				return false;
			}
		} else {
			send.set_position_id = 0;
			send.tovar_id_set = 0;
		}

		return send;
	},
	_tovarIcon = function(v) {//установка вида отображения товаров
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
		open:0,         //автоматически открывать окно выбора товара
		set:1,          //выводить товары, которые являются запчастью для других товаров
		image:1,        //показывать в результате изображение
		tovar_id_set:0, //по умолчанию показать список запчастей, которые устанавливаются на этот товар
		several:0,      //возможность выбирать несколько товаров
		count_show:1,   //возможность указывать количество товаров
		avai:0,         //выбор товара только из наличия
		del:1,          //возможность отменить выбранный товар
		func:function() {}
	}, o);

	//если несколько товаров, то картинка не показывается
	if(o.several)
		o.image = 0;

	//если один товар, то количество не указывается
	if(!o.several)
		o.count_show = 0;

	t.after('<div class="tovar-select">' +
				'<table class="_spisok">' +
					'<tr class="tr-but">' +
						'<td class="td-but" colspan="3"><button class="vk small">выбрать товар</button>' +
				'</table>' +
				'<div class="ts-avai dn">&nbsp;</div>' +
			'</div>');

	var ts = t.next(),
		trBut = ts.find('.tr-but'),
		but = ts.find('.vk'),
		tsDialog,   //диалог окна выбора товара
		tsAvai = ts.find('.ts-avai'),//поле для показа наличия товара
		tsArr,      //массив данных для выбора конкретного товара
		TSV = '';   //последнее слово поиска

	but.click(selOpen);

	tovarSelected();

	if(o.open)
		but.trigger('click');

	function tovarSelected() {//вставка товара, который был выбран (при редактировании)
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
	function selOpen() {//окно выбора товара
		if(but.hasClass('_busy'))
			return;

		var html =
			'<table id="tovar-select-tab" class="w100p">' +
				'<tr><td><div id="tovar-find"></div>' +
		 (!o.avai ? '<td class="r"><button class="vk">Добавить новый товар</button>' : '') +
			'</table>' +
			'<div id="tres"></div>';
		tsDialog = _dialog({
			top:40,
			width:500,
			head:'Выбор товара',
			content:html,
			butSubmit:'',
			butCancel:'Закрыть'
		});

		$('#tovar-find')._search({
			width:300,
			focus:1,
			txt:'начните ввод для поиска товара...',
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
			   (o.del ? '<td class="ed"><div class="img_del' + _tooltip('Отменить выбор', -93, 'r') + '</div>' : '');
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
	function valueUpdate() {//обновление выбранных значений товаров
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
	function avaiGet(tovar_id) {//вставка таблицы с наличием после выбора товара
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
$.fn.tovarFeature = function(o) {//управление характеристиками при внесении нового товара и редактировании
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
		spisok_save:[]//сохранение исходного списка характеристик при отмене редактирования
	}, o);

	var num = 0;

	t.after('<table class="bs10" id="feature-tab"></table>' +
			'<div id="feature-add">Добавить характеристику</div>');

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
					'<div val="' + num + '" class="img_del' + _tooltip('Отменить', -32) + '</div>';
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
			title0:'Название характеристики',
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
	.on('mouseover', '.tderr', function() {//отмена подсветки ошибки
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

	.on('click', '#_tovar #icon .img', function() {//переключение вида списка товаров
		var v = $(this).attr('val');
		_tovarIcon(v);
		_tovarSpisok(v, 'icon_id');
	})
	.on('click', '.tovar-category-unit .hd', function() {//действие при нажатии на категорию товара
		var v = $(this).parent().attr('val');
		$('#category_id')._select(v);
		_tovarSpisok(v, 'category_id');
		_tovarIcon(4);
	})
	.on('click', '.tovar-category-unit .sub-unit a', function() {//действие при нажатии на название товара в категории товаров
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
						'<tr><td class="label r">Количество:<td><input type="text" id="count" value="1" /> шт.' +
						'<tr><td class="label r">Цена за ед.:' +
							'<td><input type="text" id="cost_buy" class="money" value="' + TI.cost_buy + '"> руб.' +
						'<tr><td class="label r">Б/у:<td><input type="hidden" id="bu" />' +
						'<tr><td class="label r">Примечание:<td><input type="text" id="about" />' +
					'</table>',
			dialog = _dialog({
				head:'Внесение наличия товара',
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
				dialog.err('Некорректно указано количество');
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
	.on('click', '#tovar-sell', function() {//продажа товара из информации о заявке
		var dialog = _dialog({
				top:100,
				width:490,
				head:'Продажа товара',
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
						title0:'Не выбран',
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
						dialog.butSubmit('Применить');
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
				dialog.err('Некорректно указано количество');
				$('#count').focus();
				return;
			}
			if(send.count > max) {
				dialog.err('Указано количество больше допустимого');
				$('#count').focus();
				return;
			}
			if(!send.cena) {
				dialog.err('Некорректно указана цена');
				$('#cena').focus();
				return;
			}
			if(!send.invoice_id) {
				dialog.err('Не выбран расчётный счёт');
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
				txt:'Быстрый поиск...',
				enter:1,
				func:_tovarSpisok
			});
			$('#group')._radio(_tovarSpisok);
			$('#category_id')._select({
				width:140,
				title0:'не указана',
				spisok:TOVAR_CATEGORY_SPISOK,
				func:_tovarSpisok
			});
			$('#name_id')._select({
				width:140,
				title0:'не выбрано',
				spisok:[],
				func:_tovarSpisok
			});
			$('#vendor_id')._select({
				width:140,
				title0:'не выбран',
				spisok:[],
				func:_tovarSpisok
			});
		}
	});

