var _tovarEdit = function(o) {
		o = $.extend({
			id:0,
			category_id:0,
			sub_id:0,
			name:'',
			vendor_id:0,
			about:'',

			articul:'',

			measure_id:1,
			measure_length:0,
			measure_width:0,

			callback:function(res) {
				location.href = URL + '&p=46&id=' + res.id;
			}
		}, window.TI || o);

		var form_id = o.vendor_id || o.about ? 1 : 0,
			html =
				'<input type="hidden" id="form_id" value="' + form_id + '" />' +
				'<div class="pad15" style="background:#FFF9E9">' +
					'<div class="hd2">Основные данные товара:</div>' +
					'<table class="bs10">' +
						'<tr><td class="label w125 r topi">Категория:*' +
							'<td id="cat">' +
						'<tr><td class="label r b">Название:*' +
							'<td><input type="text" id="name-add" class="w400 b" value="' + o.name + '" />' +
					'</table>' +

					'<div class="extended dn">' +
						'<table class="bs10">' +
							'<tr><td class="label r">Артикул:' +
								'<td><input type="text" id="articul" class="w150" placeholder="автоматически" value="' + o.articul + '" />' +
							'<tr><td class="label r w125">Производитель:' +
								'<td><input type="hidden" id="vendor_id-add" value="' + o.vendor_id + '" />' +
							'<tr><td class="label r topi">Описание товара:' +
								'<td><textarea id="about" class="w400">' + _br(o.about) + '</textarea>' +
						'</table>' +
					'</div>' +
				'</div>' +

				'<div class="pad15" style="background:#FFEDBC">' +
					'<div class="hd2">Характеристики товара:</div>' +
					'<table class="bs10">' +
						'<tr><td class="label r w125">Ед. измерения:*<td><input type="hidden" id="measure_id" value="' + o.measure_id + '" />' +
						'<tr class="tr-area' + (TOVAR_MEASURE_AREA[o.measure_id] ? '' : ' dn') + '">' +
							'<td class="label r">Длина:*<td><input type="text" class="w50" id="measure_length" value="' + o.measure_length + '" /> м.' +
						'<tr class="tr-area' + (TOVAR_MEASURE_AREA[o.measure_id] ? '' : ' dn') + '">' +
							'<td class="label r">Ширина:*<td><input type="text" class="w50" id="measure_width" value="' + o.measure_width + '" /> м.' +
						'<tr class="tr-area' + (TOVAR_MEASURE_AREA[o.measure_id] ? '' : ' dn') + '">' +
							'<td class="label r">Площадь:<td><b id="measure_area"></b> <span id="measure_area_title">кв/м.</span>' +
					'</table>' +

/*					'<div class="extended dn">' +
						'<input type="hidden" id="feature" />' +
					'</div>' +
*/
				'</div>',
			dialog = _dialog({
				top:20,
				width:610,
				padding:0,
				head:o.id ? 'Редактирование данных товара' : 'Внесение нового товара',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести товар в каталог',
				submit:submit
			});

		$('#form_id')._menuDop({
			type:4,
			spisok:[
				{uid:0,title:'Простая форма товара'},
				{uid:1,title:'Расширенная форма'}
			],
			func:extendedSlide
		});
		$('#cat').tovarCategorySelect({
			category_id:o.category_id,
			sub_id:o.sub_id
		});
		$('#vendor_id-add')._select({
			width:200,
			title0:'не выбран',
			spisok:[],
			write_save:1
		});
		_tovarEditVendorLoad();
		$('#about').autosize();
		$('#measure_id')._select({
			width:200,
			spisok:TOVAR_MEASURE_SPISOK,
			func:function(v, id, item) {
				$('.tr-area')[(TOVAR_MEASURE_AREA[v] ? 'remove' : 'add') + 'Class']('dn');
				if(v)
					$('#measure_area_title').html(item.title);
			}
		});
		_tovarEditMeasureArea();
		$('#measure_length,#measure_width').keyup(_tovarEditMeasureArea);
//		$('#feature').tovarFeature({spisok:o.feature});

		extendedSlide(form_id);

		function extendedSlide(v) {
			$('.extended')['slide' + (v ? 'Down' : 'Up')]();
		}
		function submit() {
			var send = {
				op:'tovar_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				category_id:$('#category_id-add').val(),
				category_name:$('#category_id-add')._select('inp'),
				sub_id:$('#category_id-sub').val(),
				sub_name:$('#category_id-sub')._select('inp'),
				name:$('#name-add').val(),
				about:$('#about').val(),

				articul:$('#articul').val(),

				vendor_id:_num($('#vendor_id-add').val()),
				vendor_name:$.trim($('#vendor_id-add')._select('inp')),

				measure_id:_num($('#measure_id').val()),
				measure_length:_ms($('#measure_length').val()),
				measure_width:_ms($('#measure_width').val())
			};
			dialog.post(send, o.callback);
		}
	},
	_tovarEditVendorLoad = function() {//загрузка производителей
		var ven = $('#vendor_id-add'),
			send = {
				op:'tovar_vendor_load'
			};
		ven._select('process');
		$.post(AJAX_MAIN, send, function(res) {
			ven._select(res.success ? res.spisok : []);
//			ven._select(vendor_id);
		}, 'json');
	},
	_tovarEditMeasureArea = function(v) {//подсчёт площади при изменении длины и ширины
		var x = _ms($('#measure_length').val()),
			y = _ms($('#measure_width').val()),
			area = Math.round(x * y * 100) / 100;
		$('#measure_area').html(area);
	},

	_tovarSetup = function() {//окно выбора настроек товаров
		var html =
			'<div class="tsa-unit bg-gr1 bor-e8 over1 pad10 curP" val="1">' +
				'<b>Категории</b>' +
				'<div class="grey pad2-7">Настройка категорий товаров.</div>' +
			'</div>' +
			'<div class="tsa-unit bg-gr1 bor-e8 over1 pad10 curP mt10" val="2">' +
				'<b>Склады</b>' +
				'<div class="grey pad2-7">Управление складами товаров.</div>' +
			'</div>',
			dialog = _dialog({
				top:30,
				width:400,
				head:'Настройки товаров',
				content:html,
				butSubmit:'',
				butCancel:'Закрыть'
			});

		$('.tsa-unit').click(function() {
			var v = _num($(this).attr('val'));

			dialog.close();

			switch(v) {
				case 1: _tovarSetupCategory(); break;
				case 2: _tovarSetupStock(); break;
			}
		});
	},
	_tovarSetupCategory = function() {//настройка категорий товаров
		var dialog = _dialog({
			top:20,
			width:650,
			id:'tsc20650',
			head:'Управление категориями товаров',
			load:1,
			butSubmit:'',
			butCancel:'Закрыть',
			cancel:function() {
				location.reload();
			}
		});

		dialog.load({op:'tovar_setup_category_load'}, function(res) {
			dialog.content
				.find('.icon-edit').click(function() {
					var t = _parent($(this)),
						fName = t.find('.name');
					_tovarSetupCategoryEdit({
						id:t.attr('val'),
						name:fName.html(),
						callback:function(name) {
							fName.html(name);
							t.addClass('edtd');
						}
					});
				})
				.end()
				.find('.icon-del').click(function() {
					var p = _parent($(this));
					_dialogDel({
						id:p.attr('val'),
						head:'категории товаров',
						op:'tovar_setup_category_del',
						func:function() {
							p.remove();
						}
					});
				});
			sortable();
			$('.category-sub-open').click(function() {
				var p = _parent($(this), 'DD');
				p.find('.category-sub').slideToggle();
			});
		});
	},
	_tovarSetupCategoryEdit = function(o) {//создание/редактирование категории
		o = $.extend({
			id:0,
			name:'',
			callback:function() {
				_tovarSetupCategory();
			}
		}, o);

		var html = '<table class="bs10">' +
					'<tr><td class="label r">Название:' +
						'<td><input id="name" class="w230" type="text" value="' + o.name + '" />' +
				'</table>',
			dialog = _dialog({
				head:(o.id ? 'Редактирование' : 'Создание новой' ) + ' категории товаров',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();

		function submit() {
			var send = {
				op:'tovar_setup_category_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					o.callback(send.name);
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},

	_tovarSetupStock = function() {
		var dialog = _dialog({
			top:20,
			width:650,
			id:'tss20650',
			head:'Управление складами товаров',
			load:1,
			butSubmit:'',
			butCancel:'Закрыть'
		});

		dialog.load({op:'tovar_setup_stock_load'}, function(res) {
			dialog.content
				.find('.icon-edit').click(function() {
					var t = _parent($(this)),
						fName = t.find('.name');
					_tovarSetupStockEdit({
						id:t.attr('val'),
						name:fName.html()
					});
				})
				.end()
				.find('.icon-del').click(function() {
					var p = _parent($(this));
					_dialogDel({
						id:p.attr('val'),
						head:'склада для товаров',
						op:'tovar_setup_stock_del',
						func:function() {
							p.remove();
						}
					});
				});
		});
	},
	_tovarSetupStockEdit = function(o) {//создание/редактирование склада
		o = $.extend({
			id:0,
			name:''
		}, o);

		var html = '<table class="bs10">' +
					'<tr><td class="label r">Название:' +
						'<td><input id="name" class="w230" type="text" value="' + o.name + '" />' +
				'</table>',
			dialog = _dialog({
				head:(o.id ? 'Редактирование' : 'Создание нового' ) + ' склада для товаров',
				content:html,
				butSubmit:o.id ? 'Сохранить' : 'Внести',
				submit:submit
			});

		$('#name').focus();

		function submit() {
			var send = {
				op:'tovar_setup_stock_' + (o.id ? 'edit' : 'add'),
				id:o.id,
				name:$('#name').val()
			};
			dialog.post(send, function() {
				_tovarSetupStock();
			});
		}
	},

	_tovarStockMove = function() {
		var dialog = _dialog({
				top:20,
				width:550,
				head:'Перемещение товара с одного склада на другой',
				load:1,
				butSubmit:'',
				submit:submit
			}),
			send = {
				op:'tovar_stock_move_load',
				tovar_id:TI.id
			},
			avai_id = 0,
			arr;

		dialog.load(send, function(res) {
			arr = res.arr;
			$('#tovar-avai-id')._radio(articulSel);
			if(res.arr_count == 1)
				$('#tovar-avai-id')
					._radio(res.arr_first)
					._radio('click');
			$('#stock_id')._select({
				width:200,
				title0:'склад не выбран',
				spisok:TOVAR_STOCK_SPISOK
			});
		});
		function articulSel() {
			avai_id = _num($('#tovar-avai-id').val());
			$('#max').html(arr[avai_id].count);
			$('#stock-move-tab').removeClass('dn');
			$('#count').val(1).select();
			dialog.butSubmit('Применить');
		}

		function submit() {
			var send = {
				op:'tovar_stock_move',
				avai_id:avai_id,
				count:$('#count').val(),
				stock_id:$('#stock_id').val(),
				about:$('#about').val()
			};
			dialog.post(send, 'reload');
		}
	},

	_tovarSelectedIds = function() {//получение id выбранных товаров
		var check = $('.tovar-unit-check'),
			arr = [],
			n,
			inp;
		if(!check.length)
			return arr;
		for(n = 0; n < check.length; n++) {
			inp = check.eq(n).find('input');
			if(_bool(inp.val()))
				arr.push(_num(inp.attr('id').split('t')[1]));
		}
		return arr;
	},
	_tovarSelectedAction = function() {//окно с действиями с выбранными товарами
		var html =
			'<table class="mt5 mb10">' +
				'<tr><td class="label pr10">Выбрано товаров:' +
					'<td class="b">' + _tovarSelectedIds().length +
			'</table>' +
			'<div class="tsa-unit bg-gr1 bor-e8 over1 pad10 curP" val="1">' +
				'<b>Перенести товары в другую категорию</b>' +
				'<div class="grey pad2-7">Будет предложено выбрать категорию, либо создать новую.</div>' +
			'</div>',
			dialog = _dialog({
				top:30,
				width:440,
				head:'Выбор действия над товарами',
				content:html,
				butSubmit:'',
				butCancel:'Закрыть'
			});

		$('.tsa-unit').click(function() {
			var t = $(this),
				v = _num(t.attr('val'));

			dialog.close();

			switch(v) {
				case 1: _tovarSelectedCategory(); break;
			}
		});
	},
	_tovarSelectedCategory = function() {//перенос товаров в выбранную группу
		var category_id = _num($('#rightLinkMenu .sel').attr('val')),
			html =
			'<div class="_info">' +
				'Товары будут перенесены в новую <b>категорию</b>.' +
				'<br />' +
				'Выберите категорию и подкатегорию, либо введите вручную новую и она будет автоматически добавлена в базу.' +
//				'<br />' +
//				'Все категории, которые останутся без товаров, будут удалены.' +
			'</div>' +
			'<table class="bs10">' +
				'<tr><td class="label r w175">Выбрано товаров:<td class="b">' + _tovarSelectedIds().length +
				'<tr><td class="label r topi">Переместить в категорию:*' +
					'<td id="cat">' +
			'</table>',
			dialog = _dialog({
				width:550,
				head:'Перенос товаров в другую группу',
				content:html,
				butSubmit:'Применить',
				submit:submit
			});

		$('#cat').tovarCategorySelect({
			category_id:category_id
		});

		function submit() {
			var send = {
				op:'tovar_to_new_category',
				category_id:$('#category_id-add').val(),
				category_name:$('#category_id-add')._select('inp'),
				sub_id:$('#category_id-sub').val(),
				sub_name:$('#category_id-sub')._select('inp'),
				tovar_ids:_tovarSelectedIds().join()
			};
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					location.reload();
				} else
					dialog.abort(res.text);
			}, 'json');
		}
	},

	_tovarJoin = function() {
		var html =
			'<div class="_info">' +
				'Eсли в каталоге существуют два одинаковых товара, используется объединение.' +
				'<br />' +
				'Текущий товар будет <b>получателем</b>, выбранный товар <u>будет удалён</u>.' +
				'<div class="mt5">Все <u>движения</u> выбранного товара перейдут к получателю:</div>' +
				'<div class="mt3 ml20 i">' +
					'- наличие;<br />' +
					'- продажи;<br />' +
					'- списания;<br />' +
					'- использование в заявках и расходах по заявкам.' +
				'</div>' +
			'</div>' +
			'<table class="bs10">' +
				'<tr><td class="label w125 top r">Товар-получатель:' +
					'<td class="fs14 b">' + TI.name +
				'<tr><td class="label topi r">Объединенить с:' +
					'<td><input type="hidden" id="join_id" />' +
			'</table>',
			dialog = _dialog({
				top:30,
				width:550,
				head:'Объединеие товаров',
				content:html,
				butSubmit:'Применить',
				submit:submit
			});

		$('#join_id').tovar({
			small:1,
			tovar_id_no:TI.id
		});

		function submit() {
			var send = {
				op:'tovar_join',
				tovar_id:TI.id,
				join_id:$('#join_id').val()
			};
			dialog.post(send, 'reload');
		}
	},
 
	_tovarUse = function() {//применение товара
		var html =
			'<div class="_info">' +
				'Выберите товар, который будет применяться к' +
				'<br />' +
				'<b class="fs14">' + TI.name + '</b>:' +
			'</div>' +
			'<div class="mt20 mb10">' +
				'<input type="hidden" id="use_id" />' +
			'</div>',
			dialog = _dialog({
				head:'Добавление применения товара',
				content:html,
				butSubmit:'Применить',
				submit:submit
			});

		$('#use_id').tovar({tovar_id_no:TI.id});

		function submit() {
			var send = {
				op:'tovar_use',
				tovar_id:TI.id,
				use_id:$('#use_id').val()
			};
			dialog.post(send, 'reload');
		}
	},
	_tovarUseCancel = function(use_id) {//применение товара
		var html =
			'<div class="pad30 center red">' +
				'Подтвердите отмену применения товара..' +
			'</div>',
			dialog = _dialog({
				head:'Отмена применения товара',
				content:html,
				butSubmit:'Выполнить',
				submit:submit
			});

		function submit() {
			var send = {
				op:'tovar_use_cancel',
				tovar_id:TI.id,
				use_id:use_id
			};
			dialog.post(send, 'reload');
		}
	},

	_tovarCost = function(v) {//Изменение закупочной стоимости и продажи
		var html =  '<table class="bs10">' +
						'<tr><td class="label r w150">Закупочная стоимость:' +
							'<td><input type="text" id="sum_buy" class="money" value="' + TI.sum_buy + '"> руб.' +
						'<tr><td class="label r">Процент от закупки:' +
							'<td><input type="text" id="sum_procent" class="w50" maxlength="7"> %' +
								'<span id="sum_diff" class="grey ml10 fs12"></span>' +
						'<tr><td class="label r">Продажа:' +
							'<td><input type="text" id="sum_sell" class="money" value="' + TI.sum_sell + '"> руб.' +
					'</table>',
			dialog = _dialog({
				width:440,
				head:'Изменение закупочной стоимости и продажи',
				content:html,
				butSubmit:'Применить',
				submit:submit
			});

		$('#sum_' + v).select();

		//подсчёт стоимости продажи на основании процента
		$('#sum_buy,#sum_procent,#sum_sell').keyup(function() {
			var t = $(this),
				attr_id = t.attr('id').split('_')[1],
				buy = _cena($('#sum_buy').val()),
				procent = _cena($('#sum_procent').val()),
				sell = _cena($('#sum_sell').val()),
				diff = 0;

			switch(attr_id) {
				case 'buy':
				case 'procent':
					sell = _cena(buy + buy / 100 * procent);
					$('#sum_sell').val(sell);
					break;
				case 'sell':
					diff = _cena(sell - buy);
					$('#sum_procent').val(diff <= 0 ? 0 : _cena(diff / buy * 100));
					break;
			}

			//сумма, на которую увеличена стоимость
			diff = _cena(sell - buy);
			$('#sum_diff').html('+<b class="fs12">' + diff + '</b> руб.');
		});
		$('#sum_sell').trigger('keyup');

		function submit() {
			var send = {
				op:'tovar_cost',
				tovar_id:TI.id,
				sum_buy:$('#sum_buy').val(),
				sum_sell:$('#sum_sell').val()
			};
			dialog.post(send, 'reload');
		}
	},
	_tovarSale = function() {//продажа товара из информации о товаре
		var dialog = _dialog({
				top:20,
				width:510,
				head:'Продажа товара',
				load:1,
				butSubmit:'',
				submit:submit
			}),
			send = {
				op:'tovar_sale_load',
				tovar_id:TI.id
			},
			avai_id = 0,
			arr;

		dialog.load(send, loaded);

		function loaded(res) {
			arr = res.arr;
			incomeConfirmCheck();
			$('#invoice_id-add')._select(_invoiceIncomeInsert(1));
			$('#client_id').clientSel({width:300,add:1});
			$('#tovar-avai-id')._radio(articulSel);
			$('#sale-length,#sale-width').keyup(areaCalc);
			if(res.arr_count == 1)
				$('#tovar-avai-id')
					._radio(res.arr_first)
					._radio('click');
		}
		function articulSel() {
			avai_id = _num($('#tovar-avai-id').val());
			$('#max').html(arr[avai_id].count);
			$('#count,#cena').keyup(sumCount);
			$('#sale-tab').removeClass('dn');
			sumCount();
			$('#count').val(1).select();
			dialog.butSubmit('Применить');
		}
		function areaCalc() {
			var x = _ms($('#sale-length').val()),
				y = _ms($('#sale-width').val()),
				area = Math.round(x * y * 100) / 100;
			$('#count').val(area);
			sumCount();
		}
		function sumCount() {
			var count = _ms($('#count').val()),
				cena = _cena($('#cena').val()),
				sum = Math.round(count * cena);
			$('#summa').html(sum ? sum : '-');
		}
		function submit() {
			var send = {
				op:'tovar_sale',
				avai_id:avai_id,
				count:$('#count').val(),
				cena:$('#cena').val(),
				invoice_id:$('#invoice_id-add').val(),
				confirm:$('#confirm').val(),
				client_id:$('#client_id').val()
			};
			dialog.post(send, 'reload');
		}
	},
	_tovarWriteOff = function() {//списание
		var dialog = _dialog({
				top:20,
				width:490,
				head:'Списание товара',
				load:1,
				butSubmit:'',
				submit:submit
			}),
			send = {
				op:'tovar_writeoff_load',
				tovar_id:TI.id
			},
			avai_id = 0,
			arr;

		dialog.load(send, function(res) {
			arr = res.arr;
			$('#tovar-avai-id')._radio(articulSel);
			if(res.arr_count == 1)
				$('#tovar-avai-id')
					._radio(res.arr_first)
					._radio('click');
		});
		function articulSel() {
			avai_id = _num($('#tovar-avai-id').val());
			$('#max').html(arr[avai_id].count);
			$('#write-tab').removeClass('dn');
			$('#count').val(1).select();
			dialog.butSubmit('Применить');
		}

		function submit() {
			var send = {
				op:'tovar_writeoff',
				avai_id:avai_id,
				count:$('#count').val(),
				about:$('#about').val()
			};
			dialog.post(send, 'reload');
		}
	},
	_tovarAvaiAdd = function() {
		var stock_id = TOVAR_STOCK_SPISOK.length == 1 ? TOVAR_STOCK_SPISOK[0].uid : 0,
			html =  '<table class="bs10">' +
						'<tr' + (stock_id ? ' class="dn"' : '') + '>' +
							'<td class="label r">Склад:<td><input type="hidden" id="stock_id" value="' + stock_id + '" /> ' +
						'<tr><td class="label r">Количество:<td><input type="text" id="count" class="w50" value="1" /> ' + TI.measure_name +
						'<tr><td class="label r">Закуп. цена за 1 <b>' + TI.measure_name + '</b>:' +
							'<td><input type="text" id="sum_buy" class="money" value="' + TI.sum_buy + '"> руб.' +
						'<tr><td class="label r">Примечание:<td><input type="text" id="about" class="w230" />' +
					'</table>',
			dialog = _dialog({
				width:450,
				head:'Внесение наличия товара',
				content:html,
				submit:submit
			});

		$('#stock_id')._select({
			width:250,
			title0:'не выбран',
			spisok:TOVAR_STOCK_SPISOK
		});
		$('#count').focus();

		function submit() {
			var send = {
				op:'tovar_avai_add',
				tovar_id:TI.id,
				stock_id:$('#stock_id').val(),
				count:$('#count').val(),
				sum_buy:$('#sum_buy').val(),
				about:$('#about').val()
			};
			dialog.post(send, 'reload');
		}
	},
	_tovarDel = function() {
		_dialogDel({
			id:TI.id,
			head:'товара',
			op:'tovar_del',
			func:function(res) {
				location.reload();
			}
		});
	},

	_tovarZakaz = function() {//заказ товара
		var html =
			'<div class="_info">' +
				'Товар будет добавлен к заказу.' +
				'<br />' +
				'Клиента и комментарий указывать не обязательно.' +
				'<br />' +
				'После поступления товара он будет удалён из заказа.' +
			'</div>' +
			'<div class="fs18 mt15">' + TI.name + '</div>' +
			'<table class="bs10">' +
				'<tr><td class="label r w100">Количество:*' +
					'<td><input type="text" id="count" class="w50 b" value="1" /> ' + TI.measure_name +
				'<tr><td class="label r">Клиент:' +
					'<td><input type="hidden" id="client_id" />' +
				'<tr><td class="label r">Комментарий:' +
					'<td><input type="text" id="about" class="w300" placeholder="не обязательно" />' +
			'</table>',
			dialog = _dialog({
				width:500,
				head:'Добавление товара в заказ',
				content:html,
				butSubmit:'Добавить в заказ',
				submit:submit
			});

		$('#count').select();
		$('#client_id').clientSel();

		function submit() {
			var send = {
				op:'tovar_zakaz',
				tovar_id:TI.id,
				count:$('#count').val(),
				client_id:$('#client_id').val(),
				about:$('#about').val()
			};
			dialog.post(send, 'reload');
		}
	},
	_tovarZakazDel = function(tovar_id) {//удаление товара из заказа
		_dialogDel({
			id:tovar_id,
			head:'товара из заказа',
			op:'tovar_zakaz_del',
			func:function(res) {
				location.reload();
			}
		});
	},

	_tovarSpisok = function(v, id) {
		if(id == 'find' || id == 'zakaz')
			if(v) {
				if(TOVAR.category_id) {
					TOVAR.category_id_save = TOVAR.category_id;
					TOVAR.category_id = 0;
				}
				$('#rightLinkMenu .sel')
					.removeClass('sel')
					.addClass('save');
			} else {
				TOVAR.category_id = TOVAR.category_id_save;
				if($('#rightLinkMenu .sel').length)
					$('#rightLinkMenu .save').removeClass('save');
				else
					$('#rightLinkMenu .save')
						.addClass('sel')
						.removeClass('save');
				}

		if(id == 'avai') {
			$('#zakaz')._check(0);
			TOVAR.zakaz = 0;
		}

		if(id == 'zakaz') {
			$('#avai')._check(0);
			TOVAR.avai = 0;
		}

		_filterSpisok(TOVAR, v, id);

		$.post(AJAX_MAIN, TOVAR, function(res) {
			if(res.success) {
				$('#tovar-result').html(res.result);
				$('#tovar-spisok').html(res.spisok);

				//подстановка количества товаров в меню корневых категорий
				for(id in res.cc)
					$('#cat' + id).html(res.cc[id] || '');
			}
		}, 'json');
	},
	_tovarFilterClear = function() {
		$('#find')._search('clear');    TOVAR.find = '';

		$('#rightLinkMenu .save').removeClass('save');
		$('#rightLinkMenu .sel').removeClass('sel');
		var link = $('#rightLinkMenu a');
		for(var n = 0; n < link.length; n++) {
			var sp = link.eq(n);
			if(sp.attr('val') == (CATEGORY_ID || CATEGORY_ID_DEF)) {
				sp.addClass('sel');
				break;
			}
		}
		TOVAR.category_id = CATEGORY_ID || CATEGORY_ID_DEF;
		TOVAR.sub_id = SUB_ID;//подкатегория, полученная со статистики

		$('#fstock_id')._select(0);  TOVAR.fstock_id = 0;
		$('.filter-stock').slideUp();

		$('#avai')._check(SUB_ID ? 1 : 0);  TOVAR.avai = SUB_ID;
		$('#zakaz')._check(0);          TOVAR.zakaz = 0;

		_tovarSpisok();

		CATEGORY_ID = 0;
		SUB_ID = 0;
	};

$.fn.tovar = function(o) {
/*
	Использование:
		1. применение к другому товару - в информации о товаре
		2. внесение заявки: один товар
		3. внесение заявки: несколько товаров
		4. расход в заявке
		5. расход в заявке: наличие
		6. фильр списка заявок
		7. объединение товаров
*/
	var t = $(this),
		attr_id = t.attr('id'),
		win = attr_id + '_tovarSelect';

	if(!attr_id)
		return '';

	if(o === 0) {
		window[win].cancel();
		return t;
	}

	if(o == 'equip_ids_sel')
		return window[win].equipIdsSel();

	o = $.extend({
		title:'выбрать товар',  //текст в кнопке
		small:0,                //маленькая кнопка
		open:0,                 //автоматически открывать окно выбора товара
		add:0,                  //показ кнопки добавления нового товара
		several:0,              //возможность выбирать несколько товаров
		avai:0,                 //выбор товара только из наличия - для расходов по заявке. Наличие списывается.
		tovar_id_use:0,         //по умолчанию показать список товаров, которые применяются на этот tovar_id_use
		tovar_id_no:0,          //исключать в поиске tovar_id_no
		zayav_use:0,            //выводить только товары, которые использовались в заявках
		equip:false,            //Выбранные ids комплектации. Показывать комплектацию - если один товар и !== false
		func:function() {}
	}, o);

	t.before(
		(o.several ? '<table class="_spisokTab"></table>' : '') +
		'<button class="vk' + (o.small ? ' small mt5' : '') + '">' + o.title + '</button>'
	);

	var BUT = t.prev(),
		VAL = $.trim(t.val()),
		TS_TAB = o.several ? BUT.prev() : false, //таблица с товарами, если несколько
		TS_DIALOG,          //диалог окна выбора товара
		TS_VAL = '',        //введённое значение при поиске
		TS_ARR,             //массив загруженных товаров
		TS_EQUIP_SPISOK = []; //список комплектации для _select

	if(VAL == '0')
		VAL = 0;

	tsSelectedLoad();

	BUT.click(tsOpen);
	if(o.open)
		BUT.trigger('click');

	function tsOpen() {//окно выбора товара
		if(BUT.hasClass('_busy'))
			return;

		var html =
			'<div class="pad10">' +
				'<table class="w100p">' +
					'<tr><td><div id="tovar-find"></div>' +
			   (o.add ? '<td class="r"><button class="vk" id="tovar-add">Добавить новый товар</button>' : '') +
				'</table>' +
			'</div>' +
			'<div id="tres"></div>';
		TS_DIALOG = _dialog({
			top:20,
			width:500,
			padding:0,
			head:'Выбор товара',
			content:html,
			butSubmit:'',
			butCancel:'Закрыть'
		});

		$('#tovar-find')._search({
			width:300,
			focus:1,
			txt:'начните ввод для поиска товара...',
			v:TS_VAL,
			func:tsFind
		});
		$('#tovar-add').click(function() {
			_tovarEdit({
				callback:function(res) {
					TS_ARR = res.arr;
					TS_DIALOG.close();
					tsSelected(res.id);
				}
			});
		});

		tsFind(TS_VAL);
	}
	function tsFind(v) {//процесс поиска товара
		var send = {
			op:'tovar_select_find',
			find:v,
			tovar_id_use:o.tovar_id_use,
			tovar_id_no:o.tovar_id_no,
			zayav_use:o.zayav_use,
			avai:o.avai
		};
		$('#tovar-find')._search('process');
		$.post(AJAX_MAIN, send, function(res) {
			$('#tovar-find')._search('cancel');
			if(res.success) {
				TS_VAL = v;
				TS_ARR = res.arr;
				$('#tres')
					.html(res.html)
					.find('.tsu').click(function(e) {
						e.stopPropagation();
						var id = $(this).attr('val');
						tsSelected(id);
						TS_DIALOG.close();
					});

			}
		}, 'json');
	}
	function tsSelectedLoad() {//вставка товаров, которые были выбраны (при редактировании)
		if(!VAL)
			return;

		var send = {
			op:'tovar_selected_load',
			v:VAL
		};
		BUT.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			BUT.removeClass('_busy');
			if(res.success) {
				TS_ARR = res.arr;
				for(var i in TS_ARR)
					tsSelected(i);
			}
		}, 'json');
	}
	function tsSelected(id) {//вставка одного товара
		if(o.several)
			return tsSelectedSeveral(id);

		var sp = TS_ARR[id],
			html =
			'<table class="w100p bs5 bg-gr1 bor-e8">' +
				'<tr>' +
			        '<td class="top w35 h25">' + sp.img +
					'<td class="top b fs14">' +
						sp.name +
						'<div class="fs12 grey mt1">' + sp.about + '</div>' +
					'<td class="w15 top">' +
						'<div class="icon icon-del fr' + _tooltip('Отменить выбор', -91, 'r') + '</div>' +
			'</table>' +

  (o.avai ? '<div id="ts-avai" class="mtm1">' +
				'<div class="bor-e8 bg-ffd color-555 pad10 center">' +
                    'Получение наличия товара...' +
	                '<div class="_busy mt5">&nbsp;</div>' +
                '</div>' +
            '</div>'
  : '') +

 (o.equip !== false ?
            '<div id="ts-equip" class="bg-gr1 bor-e8 mtm1 pad5 dn">' +
				'<div class="center grey">Загрузка комплектации товара...</div>' +
                '<div class="_busy mt5">&nbsp;</div>' +
			'</div>'
 : '');

		BUT.after(html);
		BUT.hide();
		t.val(id);

		BUT.next().find('.icon-del').click(function() {
			tsOneCancel();
			o.func(0, attr_id, {});
		});

		//подгрузка наличия товара после выбора
		if(o.avai) {
			sp.avai_id = 0;     //id наличия товара
			sp.avai_count = 0;  //количество в наличии
			sp.avai_buy = 0;    //закупочная цена
			var send = {
				op:'tovar_selected_avai',
				tovar_id:id
			};
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#ts-avai').html(res.html);
					$('#tovar-avai-id')._radio(function(avai_id) {
						sp.avai_id = avai_id;
						sp.avai_count = res.arr[avai_id]['count'];
						sp.avai_buy = res.arr[avai_id]['sum_buy'];
						o.func(id, attr_id, sp);
					});
					if(res.arr_count == 1)
						$('#tovar-avai-id')
							._radio(res.arr_first)
							._radio('click');
				} else
					$('#ts-avai').html('<div class="pad10 center red">' + res.text + '</div>');
			}, 'json');
		} else
			o.func(id, attr_id, sp);

		if(o.equip !== false) {
			$('#ts-equip').slideDown();
			var send = {
				op:'tovar_equip_load',
				tovar_id:id,
				ids:o.equip
			};
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					$('#ts-equip').html(res.html);
					$('#equip-add').click(equipAddShow);
					TS_EQUIP_SPISOK = res.equip_js
				} else
					$('#ts-equip').html('<div class="center red">' + res.text + '</div>');
			}, 'json');
		}
	}
	function tsOneCancel() {//отмена выбора - если один товар
		if(t.val() == 0)
			return;
		BUT.next().remove();
		$('#ts-avai').remove();
		$('#ts-equip').remove();
		BUT.show();
		t.val(0);
	}
	function tsSelectedSeveral(id) {//вставка нескольких товаров
		var sp = TS_ARR[id],
			html =
				'<tr class="over1">' +
					'<td class="fs14">' + sp.name +
					'<td class="w35 bg-ffd">' +
						'<input type="text" class="w35 b r" id="ts' + id + '" value="' + (sp.count || 1) + '" />' +
					'<td class="w15">' +
						'<div class="icon icon-del fr' + _tooltip('Отменить выбор', -50) + '</div>';
		TS_TAB.append(html);
		TS_TAB.find('.icon-del:last').click(function() {
			_parent($(this)).remove();
			tsSelectedSeveralVal();
		});
		TS_TAB.find('input:last').select().keyup(tsSelectedSeveralVal);
		tsSelectedSeveralVal();

		return true;
	}
	function tsSelectedSeveralVal() {//составление переменной, если несколько товаров
		var inp = TS_TAB.find('input'),
			len = inp.length,
			v = [];
		BUT[(len ? 'add' : 'remove') + 'Class']('mt5');
		for(var n = 0; n < len; n++) {
			var sp = inp.eq(n),
				id = _num(sp.attr('id').split('ts')[1]),
				val = _num(sp.val());
			sp.parent()
				[(val ? 'remove' : 'add') + 'Class']('bg-err')
				[(!val ? 'remove' : 'add') + 'Class']('bg-ffd');
			if(!val)
				continue;
			v.push(id + ':' + val);
		}
		t.val(v.join());
	}

	function equipAddShow() {//выбор позиции комплектации для добавления
		var t = $(this);
		t.next().next().removeClass('dn').click(equipAdd);
		t.remove();
		$('#equip_id')
			._select({
				width:177,
				title0:'выберите или введите новое',
				write_save:1,
				spisok:TS_EQUIP_SPISOK
			})
			._select('focus');
	}
	function equipAdd() {
		var but = $(this),
			send = {
				op:'tovar_equip_add',
				tovar_id:t.val(),
				equip_id:$('#equip_id').val(),
				equip_name:$('#equip_id')._select('inp')
			};

		if(but.hasClass('_busy'))
			return;

		but.addClass('_busy');
		$.post(AJAX_MAIN, send, function(res) {
			but.removeClass('_busy');
			if(res.success) {
				but.parent().before(res.html);
				$('#equip_id')._select(0);
			} else
				but.vkHint({
					msg:'<span class="red">' + res.text + '</span>',
					show:1,
					remove:1,
					indent:60,
					top:-90,
					left:154
				});
		}, 'json');
	}

	t.cancel = tsOneCancel;
	t.equipIdsSel = function() {//получение id комплектаций. sel - только тех, у которых стоят галочки
		if(o.equip === false)
			return '';

		var check = $('#ts-equip ._check'),
			send = [];

		for(var n = 0; n < check.length; n++) {
			var eq = check.eq(n),
				inp = eq.find('input'),
				id = _num(inp.attr('id').split('eq')[1]),
				v = _num(inp.val());
			if(!v)
				continue;
			send.push(id);
		}

		return send.join();
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

	t.after('<table class="bs5" id="feature-tab"></table>' +
			'<div id="feature-add" class="pad10 center curP over0 grey">Добавить характеристику</div>');

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
				'<td><input type="text" class="w300" id="feature_val_' + num + '" value="' + title + '" />' +
				'<td><div val="' + num + '" class="icon icon-del mt1' + _tooltip('Отменить', -30) + '</div>';
		$('#feature-tab').append(tr);
		$('#feature-tab tr:last').find('.icon-del').click(function() {
			_parent($(this)).remove();
			for(var n = 0; n < o.spisok_save.length; n++)
				if(o.spisok_save[n].num == num) {
					o.spisok_save[n].on = 0;
					break;
				}
		});
		$('#feature_' + num)._select({
			title0:'Название характеристики',
			width:200,
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
$.fn.tovarCategorySelect = function(o) {//вывод _select категории и подкатегории товаров
	var t = $(this);

	o = $.extend({
		category_id:0,
		sub_id:0
	}, o);

	var html = '<input type="hidden" id="category_id-add" value="' + o.category_id + '" />' +
				'<div class="mt5' + (o.sub_id ? '' : ' dn') + '" id="div-sub">' +
					'<div class="icon icon-sub curD h20 mb1"></div>' +
					'<input type="hidden" id="category_id-sub" value="' + o.sub_id + '" />' +
				'</div>';
	t.append(html);

	$('#category_id-add')._select({
		width:300,
		title0:'не указана',
		spisok:TOVAR_CATEGORY_SPISOK,
		write_save:1,
		func:_subLoad
	});
	$('#category_id-sub')._select({
		width:280,
		title0:'подкатегория не указана',
		write_save:1
	});

	_subLoad(o.category_id);
	o.sub_id = 0;

	function _subLoad(cid) {
		if(!o.sub_id)
			$('#category_id-sub')
				._select([])
				._select('clear');
		var cinp = $('#category_id-add')._select('inp');
		$('#div-sub')['slide' + (cid || cinp ? 'Down' : 'Up')]();
		if(!cid)
			return;
		$('#category_id-sub')._select('process');
		var send = {
			op:'tovar_category_sub_for_select',
			category_id:cid
		};
		$.post(AJAX_MAIN, send, function(res) {
			$('#category_id-sub')._select(res.success ? res.spisok : 'cancel');
		}, 'json');
	}


};

$(document)
	.on('mouseover', '.tderr', function() {//отмена подсветки ошибки
		$(this).removeClass('tderr');
	})

	.on('click', '.tovar-info-go', function(e) {
		e.stopPropagation();
		location.href = URL + '&p=46&id=' + $(this).attr('val');
	})

	.on('click', '.tovar-unit', function(e) {
		e.stopPropagation();
		location.href = URL + '&p=46&id=' + $(this).attr('val');
	})
	.on('click', '.tovar-unit-check', function(e) {
		e.stopPropagation();

		var t = $(this),
			p = _parent(t, '.tovar-unit'),
			inp = t.find('input'),
			v = _bool(inp.val());

		//изменение калочки, если было клик был по краю
		if($(e.target).hasClass('tovar-unit-check'))
			$('#' + inp.attr('id'))._check(v ? 0 : 1);

		var arr = _tovarSelectedIds(),
			count = arr.length;
		v = _bool(inp.val());
		t[(v ? 'add' : 'remove') + 'Class']('selected');
		p[(v ? 'add' : 'remove') + 'Class']('bg-ffd');
		p[(!v ? 'add' : 'remove') + 'Class']('over1');

		$('#but-tovar-selected')
			[(count ? 'remove' : 'add') + 'Class']('dn')
			.html('Выбран' + _end(count, ['', 'о']) + ' ' + count + ' товар' + _end(count, ['', 'а', 'ов']));
	})
	.on('click', '.tovar-unit .icon-del', function(e) {//отмена применения товара
		e.stopPropagation();
		var p = _parent($(this), '.tovar-unit'),
			v = p.attr('val');
		_tovarUseCancel(v);
	})

	.on('click', '#tovar-info .avai-edit', function() {//редактирование наличия товара
		var t = $(this),
			avai_id = t.attr('val'),
			about = _parent(t).find('.about').html(),
			html =  '<table class="bs10">' +
						'<tr><td class="label r">Примечание:' +
							'<td><input type="text" id="about" class="w250" value="' + about + '">' +
					'</table>',
			dialog = _dialog({
				width:400,
				head:'Изменение данных наличия',
				content:html,
				butSubmit:'Сохранить',
				submit:submit
			});

		function submit() {
			var send = {
				op:'tovar_avai_edit',
				avai_id:avai_id,
				about:$('#about').val()
			};
			dialog.post(send, 'reload');
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

	.on('click', '.tovar-menu-dot', function() {//открытие меню редактирования товара
		var t = $(this),
			next = t.next();
		next.removeClass('dn');

		$(document).on('click.tmd', function() {
			next.addClass('dn');
			$(document).off('click.tmd');
		});
	})

	.on('click', '.year-tab', function() {//показ списка движения товара за выбранный год
		$(this).next().slideToggle(300);
	})

	.ready(function() {
		if($('#_tovar').length) {
			$('#find')._search({
				width:220,
				focus:1,
				txt:'быстрый поиск товара',
				enter:1,
				v:TOVAR.find,
				func:_tovarSpisok
			});
			$('#rightLinkMenu .main').click(function() {//нажатие на главное меню
				var t = $(this),
					p = t.parent(),
					v = t.attr('val');
				p.find('.sel').removeClass('sel');
				t.addClass('sel');
				TOVAR.sub_id = 0;
				_tovarSpisok(v, 'category_id');
			});
			$('#rightLinkMenu .main').mouseenter(function(e) {
			    var tsh = $('#tovar-spisok').height(),
				    y = e.pageY,
				    h = $(this).find('.sub').height();
				if(y + h - 100 > tsh)
			        $('#tovar-spisok').css('min-height', (y + h - 100) + 'px');
			});
			$('#rightLinkMenu .sub div').click(function(e) {//нажатие на подменю
				e.stopPropagation();
				var t = $(this),
					main = _parent(t, '.main'),
					pMain = main.parent(),
					p = t.parent(),
					v = t.attr('val');
				pMain.find('.sel').removeClass('sel');
				main.addClass('sel');
				_tovarSpisok(v, 'sub_id');
			});
			$('#avai')._check(function(v) {
				$('#fstock_id')._select(0);
				TOVAR.fstock_id = 0;
				_tovarSpisok(v, 'avai');
				$('.filter-stock')['slide' + (v && TOVAR_STOCK_SPISOK.length > 1 ? 'Down' : 'Up')]();
			});
			$('#zakaz')._check(_tovarSpisok);
			$('#fstock_id')._select({
				width:200,
				title0:'любой склад',
				spisok:TOVAR_STOCK_SPISOK,
				func:_tovarSpisok
			});

			if(SUB_ID)
				_tovarFilterClear();
		}
	});

