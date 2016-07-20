var _tovarEditExtend = function(o) {
		return $.extend({
			id:0,
			category_id:0,
			name_id:0,
			vendor_id:0,
			name:'',
			set_position_id:0,
			tovar_id_set:0,
			measure_id:1,
			about:'',
			feature:[],
			callback:function(res) {
				location.href = URL + '&p=tovar&d=info&id=' + res.id;
			}
		}, o);
	},
	_tovarAdd = function(o) {
		o = _tovarEditExtend(o);
		var html =
			'<div class="_info" id="info-main">' +
				'Внимательно заполните поля!<br />' +
				'После внесения товара будет создана карточка товара.<br />' +
				'Поля, помеченные звёздочкой *, обязательны для заполнения.' +
//				'Для более подробного ознакомления с правилами внесения нового товара читайте в Мануале.' +
			'</div>' +
			_tovarEditLabelCat(o) +
			'<div id="ta-name"' + (o.category_id ? '' : ' class="dn"') + '>' +
/*				'<div class="_info dn">' +
					'<p>В поле <b>Название</b> выберите название товара.' +
						'<br />' +
						'Это слово или словосочетание, которое коротко описывает товар, то есть отвечает на вопрос: <u>что это?</u>' +
						'<br />' +
						'Примеры: <u>Пицца</u>, <u>Мобильный телефон</u>, <u>Парник</u>, <u>Пластиковое окно</u> и тп.' +
						'<br />' +
						'Если в выпадающем списке нет нужного названия, укажите своё.' +
					'<p><b>Производителя</b> товара указывать не обязательно.' +
					'<p>В поле <b>Подробно</b> пишется уточнение, модель, версия или короткое описание товара.' +
				'</div>' +
*/
				_tovarEditLabelName(o) +
				_tovarEditLabelSet(o) +
				_tovarEditLabelDop(o) +
			'</div>',

			dialog = _dialog({
				top:20,
				width:600,
				head:'Внесение нового товара',
				class:'tovar-add',
				content:html,
				butSubmit:'Внести товар в каталог',
				submit:submit
			});

	/*
		dialog.content.find('#ta-set .headName').after(
			'<div class="_info">' +
				'Укажите, применяется ли этот товар к другому товару. ' +
				'То есть является неотъемлемой частью.' +
				'<p><b>Например:</b>' +
				'<br />' +
				' - это <u>запчасть</u> (матрица от ноутбука);<br />' +
				' - это <u>комплектующее</u> (петля от двери);<br />' +
				' - это <u>аксессуар</u> (чехол для телефона);<br />' +
				' - это <u>ингредиент</u> (сыр для пиццы). И тп.' +
			'</div>'
		);
*/
		_tovarEditFunc(o, dialog);

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

	_tovarEditLabelCat = function(o) {//отображение поля Категория
		var cat = '';
		if(!o.category_id)
			for(var n = 0; n < TOVAR_CATEGORY_SPISOK.length; n++) {
				var sp = TOVAR_CATEGORY_SPISOK[n];
				cat += '<div class="cat-un" val="' + sp.uid + '">' + sp.title + '</div>';
			}

		return '<div class="headName">Основные данные товара:</div>' +
			'<table class="bs10">' +
				'<tr><td class="label w125 r topi">Категория:*' +
					'<td><input type="hidden" id="category_id-add" value="' + o.category_id + '" />' +
						cat +
			'</table>';
	},
	_tovarEditLabelName = function(o) {
		return  '<table id="tab-name">' +
					'<tr><td class="label w125 r">Название:*' +
						'<td><input type="hidden" id="name_id-add" value="' + o.name_id + '" />' +
				
					'<tr><td class="label r">Производитель:' +
						'<td><input type="hidden" id="vendor_id-add" value="' + o.vendor_id + '" />' +
			(!o.vendor_id ? '<span class="prim">(не указывайте производителя, если его нет)</span>' : '') +

					'<tr><td class="label r">Подробно:' +
						'<td><input type="text" id="name" value="' + o.name + '" placeholder="описание / модель / версия / уточнение" />' +
				'</table>';
	},
	_tovarEditLabelSet = function(o) {
		return '<div class="headName">Применение к другому товару:</div>' +
			'<table class="bs10 w100p" id="tab-set">' +
				'<tr><td class="label r w125 tdset2">Применение:' +
					'<td><input type="hidden" id="set_position_id" value="' + o.set_position_id + '" />' +
				'<tr class="tr-set' + (o.set_position_id ? '' : ' dn') + '">' +
					'<td class="label topi r tdset3">Для товара:*' +
					'<td><input type="hidden" id="te-tovar_id_set" value="' + o.tovar_id_set + '" />' +
			'</table>';
	},
	_tovarEditLabelDop = function(o) {
		return '<div class="headName">Дополнительные характеристики:</div>' +
			'<table class="bs10" id="tab-dop">' +
				'<tr><td class="label r w125">Единица изменения:*<td><input type="hidden" id="measure_id" value="' + o.measure_id + '" />' +
				'<tr><td class="label topi r">Описание товара:<td><textarea id="about">' + _br(o.about) + '</textarea>' +
			'</table>' +
			'<input type="hidden" id="feature" />';
	},

	_tovarEditCategorySelect = function(o) {//выпадающий список для категорий
		if(!o.category_id)
			return;

		$('#category_id-add')._select({
			width:300,
			title0:'категория не указана',
			spisok:TOVAR_CATEGORY_SPISOK,
			func:_tovarEditCategoryFunc
		});

		_tovarEditNameLoad(o.category_id, o.name_id);
	},
	_tovarEditNameLoad = function(v, name_id) {//загрузка наименований после выбора категории
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
	},
	_tovarEditCategoryFunc = function(v) {
		if(_num($('#name_id-add').val()) || $.trim($('#name_id-add')._select('inp')))
			return;
		_tovarEditNameLoad(v, 0);
		if(window.TI)
			return;
		if(!v)
			return;
		$('#info-main').slideUp();
		$('#ta-name').slideDown();
	},
	_tovarEditFunc = function(o, dialog) {
		$('#name_id-add')._select({
			width:180,
			title0:'не указано',
			spisok:[],
			write:1,
			write_save:1,
			func:function() {
				$('#name').focus();
			}
		});
		$('#vendor_id-add')._select({
			width:180,
			title0:'не выбран',
			spisok:TOVAR_VENDOR_SPISOK,
			write:1,
			write_save:1,
			func:function() {
				$('#name').focus();
			}
		});

		_tovarEditCategorySelect(o, dialog);
		$('.cat-un').click(function() {
			var t = $(this),
				id = t.attr('val');
			$('#category_id-add').val(id);
			o.category_id = id;
			$('.cat-un').slideUp(200, function() {
				_tovarEditCategorySelect(o, dialog);
				_tovarEditCategoryFunc(id);
			});
		});

		$('#set_position_id')._dropdown({
			title0:'нет',
			spisok:TOVAR_POSITION_SPISOK,
			func:function(v) {
				$('.tr-set')[(v ? 'remove' : 'add') + 'Class']('dn');
			}
		});
		$('#te-tovar_id_set').tovar({
			set:0,
			tovar_id_not:o.id
		});

		$('#measure_id')._select({
			width:70,
			spisok:TOVAR_MEASURE_SPISOK
		});
		$('#about').autosize();

		$('#feature').tovarFeature({spisok:o.feature});
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

			set_position_id:_num($('#set_position_id').val()),
			tovar_id_set:_num($('#te-tovar_id_set').val().split(':')[0]),

			measure_id:_num($('#measure_id').val()),
			about:$('#about').val(),
			feature:$('#feature').tovarFeature('get')
		};
		if(!send.category_id) {
			dialog.err('Не выбрана категория');
			return false;
		}
		if(!send.name_id && !send.name_name) {
			dialog.err('Не указано наименование товара');
			$('#name_id-add')._select('focus');
			return false;
		}

		if(send.set_position_id && !send.tovar_id_set) {
			dialog.err('Не выбран товар');
			$('.tdset3').addClass('tderr');
			return false;
		}

		return send;
	},

	_tovarCostSet = function(v) {//Изменение закупочной стоимости и продажи
		var html =  '<table class="bs10">' +
						'<tr><td class="label r w100">Закупка:' +
							'<td><input type="text" id="sum_buy" class="money" value="' + TI.sum_buy + '"> руб.' +
						'<tr><td class="label r">Продажа:' +
							'<td><input type="text" id="sum_sell" class="money" value="' + TI.sum_sell + '"> руб.' +
					'</table>',
			dialog = _dialog({
				head:'Изменение закупочной стоимости и продажи',
				content:html,
				butSubmit:'Применить',
				submit:submit
			});

		$('#sum_' + v).select();

		function submit() {
			var send = {
				op:'tovar_cost_set',
				tovar_id:TI.id,
				sum_buy:_cena($('#sum_buy').val()),
				sum_sell:_cena($('#sum_sell').val())
			};
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

	_tovarWriteOff = function() {//продажа товара из информации о заявке
		var dialog = _dialog({
				top:20,
				width:490,
				head:'Списание товара',
				class:'tovar-sell',
				load:1,
				butSubmit:'',
				submit:submit
			}),
			send = {
				op:'tovar_writeoff_load',
				tovar_id:TI.id
			},
			avai_id = 0,
			max = 0,
			arr;

		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				dialog.content.html(res.html);
				if(!res.count)
					return;
				arr = res.arr;
				$('#ta-articul')._radio(articulSel);
				if(res.count == 1) {
					for(var key in arr);
					$('#ta-articul')._radio(key);
					articulSel();
				}
			} else
				dialog.loadError();
		}, 'json');

		function articulSel() {
			avai_id = _num($('#ta-articul').val());
			max = arr[avai_id].count;
			$('#count').val(1).focus();
			$('#max b').html(max);
			$('#ts-tab').removeClass('dn');
			dialog.butSubmit('Применить');
			$('#count').val(1).select();
		}

		function submit() {
			var send = {
				op:'tovar_writeoff',
				avai_id:avai_id,
				count:_num($('#count').val()),
				about:$.trim($('#about').val())
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
			if(!send.about) {
				dialog.err('Не указана причина');
				$('#about').focus();
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
		if(id == 'category_id') {
			TOVAR.icon_id = v ? 4 : 2;
			_tovarIcon(v ? 4 : 2);
			TOVAR.name_id = 0;
			$('#name_id')._select(0);
			TOVAR.vendor_id = 0;
			$('#vendor_id')._select(0);
		}

		_filterSpisok(TOVAR, v, id);

		$('.div-but')[(TOVAR.icon_id == 5 ? 'add' : 'remove') + 'Class']('dn');
		$('.div-cat')[(TOVAR.icon_id == 2 || TOVAR.icon_id == 5 ? 'add' : 'remove') + 'Class']('dn');

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
/*
	Использование:
		1. редактирование товара (применение к другому товару)
		2. внесение заявки: один товар
		3. внесение заявки: несколько товаров
		4. фильтр заявок
		5. расход в заявке
		6. расход в заявке: наличие
		7. счёт на оплату
*/


	var t = $(this),
		attr_id = t.attr('id'),
		win = attr_id + '_tovarSelect';

	if(!attr_id)
		return;

	switch(typeof o) {
		case 'string':
			var s = window[win];
			switch(o) {
				case 'cancel': s.cancel(); break;
			}
			return t;
	}

	o = $.extend({
		title:'выбрать товар',//текст в кнопке
		tooltip:'',     //подсказка для кнопки
		open:0,         //автоматически открывать окно выбора товара
		ids:'none',     //выводить товары только из этого списка
		set:1,          //выводить товары, которые являются запчастью для других товаров
		image:1,        //показывать в результате изображение
		tovar_id_set:0, //по умолчанию показать список запчастей, которые устанавливаются на этот товар
		tovar_id_not:0, //исключать этот id товара при поиске
		several:0,      //возможность выбирать несколько товаров
		count_show:1,   //возможность указывать количество товаров
		avai:0,         /* варианты выбора товара:
							0 - любые товары (наличие не важно)
							1 - только из наличия (наличие списывается)
							2 - при выборе первого товара будет задан вопрос: выбор из наличия или нет
						*/
		avai_open:0,    //возможность выбирать наличие товара в окне поиска товаров
		del:1,          //возможность отменить выбранный товар
		func:function() {},
		funcSel:null    //функция, применяемая при выборе товара
	}, o);

	//Tovar Select Global
	if(!window['tsg'])
		window['tsg'] = {
			find:'', //последнее слово поиска
			avai:o.avai
		};

	var TOVAR_SEL = 0,  //id товара, который был выбран в окне поиска (для его подсветки)
		VAL = $.trim(t.val()),
		TSG = window['tsg'];

	//запоминание варианта выбора, если выбор требовался
	o.avai = TSG.avai;

	if(VAL == '0')
		VAL = 0;

	//если несколько товаров, то картинка не показывается
	if(o.several)
		o.image = 0;

	//если один товар, то количество не указывается
	if(!o.several)
		o.count_show = 0;

	t.after('<div class="tovar-select">' +
				'<table class="_spisok">' +
					'<tr class="tr-but">' +
						'<td class="td-but" colspan="3">' +
							'<button class="vk small' + (o.tooltip ? _tooltip(o.tooltip, -3, 'l') : '">') + o.title + '</button>' +
				'</table>' +
				'<div class="ts-avai dn">&nbsp;</div>' +
			'</div>');

	var ts = t.next(),
		trBut = ts.find('.tr-but'),
		but = ts.find('.vk'),
		tsDialog,   //диалог окна выбора товара
		tsArr;      //массив данных для выбора конкретного товара

	but.click(tsOpen);

	tsGet();

	if(o.open)
		but.trigger('click');

	function tsGet() {//вставка товаров, которые были выбраны (при редактировании)
		if(!VAL)
			return;
		
		var send = {
			op:'tovar_select_get',
			v:VAL
		};
		but.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			but.removeClass('_busy');
			if(res.success) {
				tsArr = res.arr;
				if(o.funcSel)
					o.funcSel(res.arr[VAL], attr_id);
				for(var i in tsArr)
					tsSel(i);
			}
		}, 'json');
	}
	function tsOpen() {//окно выбора товара
		if(but.hasClass('_busy'))
			return;

		var html =
			'<table class="w100p">' +
				'<tr><td><div id="tovar-find"></div>' +
		 (!o.avai ? '<td class="r"><button class="vk" id="ts-tovar-add">Добавить новый товар</button>' : '') +
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

		if(TSG.avai == 2) {
			tsAvaiOption();
			return;
		}

		$('#tovar-find')._search({
			width:300,
			focus:1,
			txt:'начните ввод для поиска товара...',
			v:TSG.find,
			func:tsFind
		});
		$('#ts-tovar-add').click(function() {
			_tovarAdd({
				callback:function(res) {
					tsArr = res.arr;
					tsSel(res.id);
					tsDialog.close();
				}
			});
		});
		tsFind(TSG.find);
	}
	function tsAvaiOption() {//варианты выбора товара: из наличия или нет
		var html =
			'<div class="_info">' +
				'При выборе <u>первого</u> товара необходимо указать <u>вид создаваемого счёта</u>:' +
				'<br />' +
				'<br />' +

				'1. <b>Предварительный счёт:</b>' +
				'<div class="grey">' +
					'Могут выбираться любые товары, независимо от того, есть они в наличии или нет.' +
					'<br />' +
					'Используется как информация для клиента, либо для заказа товара по счёту.' +
					'<br />' +
					'Данный вид счёта также может быть сформирован для оплаты.' +
				'</div>' +
				'<br />' +

				'2. <b>Счёт на оплату:</b>' +
				'<div class="grey">' +
					'Можно выбрать товары только <u>из наличия</u>.' +
					'<br />' +
					'После того, как счёт будет сформирован, выбранные товары будут списаны<br />из наличия.' +
				'</div>' +
			'</div>' +
			'<br />' +
			'<div class="headName">Выберите вид счёта:</div>' +
			'<input type="hidden" id="avai-option" value="-1" />';

		$('#tres').html(html);

		$('#avai-option')._radio({
			light:1,
			spisok:[
				{uid:0,title:'предварительный'},
				{uid:1,title:'на оплату'}
			],
			func:function(v) {
				tsDialog.close();
				o.avai = v;
				TSG.avai = v;
				tsOpen();
			}
		});
	}
	function tsFind(v) {//процесс поиска товара
		var send = {
			op:'tovar_select_find',
			v:v,
			tovar_id:TOVAR_SEL,
			tovar_id_set:o.tovar_id_set,
			tovar_id_not:o.tovar_id_not,
			set:o.set,
			ids:o.ids,
			avai:o.avai
		};
		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				$('#tres')
					.html(res.html)
					.find('.ts-unit').click(function() {
						var v = $(this).attr('val'),
							sp = tsArr[v];

						if(o.avai && o.avai_open) {
							$('#tres').html(sp.articul_full);
							$('#tres .vk.cancel').click(function() {
								TOVAR_SEL = v;
								tsFind(TSG.find);
							});
							$('#tres #ta-articul')._radio(function(art) {
								$('#tres .tsa-bottom').removeClass('dn');
								$('#tres #tsa-count').val(1).select();
								$('#tres .max').html(sp.articul_arr[art].count);
							});
							$('#tres .vk.submit').click(function() {
								if(o.funcSel) {
									sp.avai_id = _num($('#tres #ta-articul').val());
									sp.count = _num($('#tres #tsa-count').val());
									o.funcSel(sp, attr_id);
								}
								tsDialog.close();
							});
							return;							
						}
						tsSel(v);
						tsDialog.close();
						if(o.funcSel)
							o.funcSel(sp, attr_id);
					});
				tsArr = res.arr;
				TSG.find = v;
			}
		}, 'json');
	}
	function tsSel(v) {
		var sp = tsArr[v],
			html = '<tr>' +
			 (o.image ? '<td class="ts-image">' + sp.image_small : '') +
						'<td class="ts-name">' + sp.name_b +
						'<td class="td-cnt' + (o.count_show ? '' : ' dn') + '">' +
							'<input type="text" val="' + v + '" value="' + (sp.count || 1) + '" />' +
			   (o.del ? '<td class="ed"><div class="img_del' + _tooltip('Отменить выбор', -93, 'r') + '</div>' : '');
		trBut.before(html);
		trBut.prev().find('.img_del').click(tsCancel);
		trBut.prev().find('input').select().keyup(valueUpdate);

		if(!o.several)
			trBut.hide();

		valueUpdate();
		o.func(v, attr_id, sp);
	}
	function tsCancel() {
		_parent($(this)).remove();
		trBut.show();
		valueUpdate();
		o.func(0, attr_id, {});
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
		t.val(o.several ? v.join() : _num(v.length ? v[0].split(':')[0] : 0));
	}

	t.o = o;
	t.cancel = function() {
		t.val(0);
		trBut.show();
		trBut.prev().remove();
		o.func(0, attr_id, {});
	};
	
	window[win] = t;

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

	var num = 0,
		len = o.spisok.length;

	t.after('<table class="bs10" id="feature-tab"></table>' +
			'<div id="feature-add">Добавить характеристику</div>');

	$('#feature-add').click(function() {
		o.spisok_save.push({
			num:num,
			on:1
		});
		itemAdd(0, '');
	});

	for(var n = 0; n < len; n++) {
		var sp = o.spisok[n];
		o.spisok_save.push({
			uid:sp.uid,
			title:sp.title,
			on:1,
			num:num
		});
		itemAdd(sp.uid, sp.title);
	}

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
			name_id:_num($('#name_id').val())
		});
	})

	.on('click', '#_tovar #filter_clear', function() {
		$('#find')._search('clear');    TOVAR.find = '';
		_tovarIcon(2);                  TOVAR.icon_id = 2;
		$('#group')._radio(0);          TOVAR.group = 0;
		$('#category_id')._select(0);   TOVAR.category_id = 0;
		$('#name_id')._select(0);       TOVAR.name_id = 0;
		$('#vendor_id')._select(0);     TOVAR.vendor_id = 0;
		_tovarSpisok();
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
	.on('click', '.tovar-category-unit .sub-unit', function() {//действие при нажатии на название товара в категории товаров
		var t = $(this),
			cat_id = t.parent().attr('val'),
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
						'<tr><td class="label r">Количество:<td><input type="text" id="count" class="w50" value="1" /> ' + TI.measure_name +
						'<tr><td class="label r">Цена за ед.:' +
							'<td><input type="text" id="sum_buy" class="money" value="' + TI.sum_buy + '"> руб.' +
						'<tr><td class="label r">Примечание:<td><input type="text" id="about" class="w230" />' +
					'</table>',
			dialog = _dialog({
				head:'Внесение наличия товара',
				content:html,
				submit:submit
			});

		$('#count').focus();

		function submit() {
			var send = {
				op:'tovar_avai_add',
				tovar_id:TI.id,
				count:_num($('#count').val()),
				sum_buy:_cena($('#sum_buy').val()),
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
				top:20,
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
			max = 0,
			arr;

			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.content.html(res.html);
					if(!res.count)
						return;
					arr = res.arr;
					$('#invoice_id')._select({
						width:218,
						title0:'Не выбран',
						spisok:_invoiceIncomeInsert()
					});
					$('#client_id').clientSel({width:300,add:1});
					$('#ta-articul')._radio(articulSel);
					if(res.count == 1) {
						for(var key in arr);
						$('#ta-articul')._radio(key);
						articulSel();
					}
				} else
					dialog.loadError();
			},'json');

		function articulSel() {
			avai_id = _num($('#ta-articul').val());
			max = arr[avai_id].count;
			$('#max b').html(max);
			$('#count,#cena').keyup(sumCount);
			$('#ts-tab').removeClass('dn');
			dialog.butSubmit('Применить');
			$('#count').val(1).select();
			sumCount();
		}
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

	.on('click', '#tovar-info .move', function() {//удаление движения товара
		var t = $(this),
			p = _parent(t);
		_dialogDel({
			id:t.attr('val'),
			head:'записи',
			op:'tovar_move_del',
			func:function() {
				location.reload();
			}
		});
	})
	.on('click', '#tovar-info .mi', function() {//удаление продажи товара
		var t = $(this);
		_dialogDel({
			id:t.attr('val'),
			head:'записи',
			op:'income_del',
			func:function() {
				location.reload();
			}
		});
	})
	.on('click', '#tovar-info .ze', function() {//удаление расхода по заявке
		var t = $(this);
		_dialogDel({
			id:t.attr('val'),
			head:'записи',
			op:'zayav_expense_del',
			func:function() {
				location.reload();
			}
		});
	})

	.on('click', '.year-tab', function() {//показ списка движения товара за выбранный год
		$(this).next().slideToggle(300);
	})

	.ready(function() {
		if($('#_tovar').length) {
			$('#find')._search({
				width:138,
				focus:1,
				txt:'Быстрый поиск...',
				enter:1,
				func:_tovarSpisok
			}).inp(TOVAR.find);
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
				spisok:TOVAR.category_id ? NAME_SPISOK : [],
				func:_tovarSpisok
			});
			$('#vendor_id')._select({
				width:140,
				title0:'не выбран',
				spisok:TOVAR.category_id ? VENDOR_SPISOK : [],
				func:_tovarSpisok
			});
		}
	});

