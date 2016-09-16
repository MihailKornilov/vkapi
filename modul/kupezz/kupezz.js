var cityGet = function(val, city_id, city_name) {
		var country_id = _num($('#add-country_id').val());
		if(!country_id)
			return;
		if(!val)
			val = '';
		if(city_id == undefined || city_id == '0')
			city_id = 0;
		$('#add-city_id')._select('process');
		VK.api('places.getCities',{country:country_id, q:val}, function(data) {
			var insert = 1; // ������� ������ ��� ��������������, ���� ����������� � ������
			for(var n = 0; n < data.response.length; n++) {
				var sp = data.response[n];
				sp.uid = sp.cid;
				sp.content = sp.title + (sp.area ? '<span>' + sp.area + '</span>' : '');
				if(city_id == sp.uid)
					insert = 0;
			}
			if(city_id && insert)
				data.response.unshift({uid:city_id,title:city_name});
			if(val.length == 0)
				data.response[0].content = '<B>' + data.response[0].title + '</B>';
			$('#add-city_id')._select(data.response);
			if(city_id)
				$('#add-city_id')._select(city_id);
		});
	},
	cityShow = function() {//����������� select �������, ���� ������� ������
		var country_id = _num($('#add-country_id').val());
		if(!country_id) {
			$('#add-city_id')._select('remove');
			return;
		}
		if($('#add-city_id_select').length)
			return;
		$('#add-city_id')._select({
			width:180,
			block:1,
			title0:'����� �� ������',
			spisok:[],
			write:1,
			func:kupezzObEditPreview,
			funcKeyup:cityGet
		});
		$('#add-city_id_select').vkHint({
			width:180,
			msg:'<div style="text-align:justify">' +
					'����������� ���������� �����, ' +
					'���� ���� ���������� ������������� ������ �� ����, ' +
					'����� ���������� ����� ������������ ������ � ����� ������.' +
				'</div>',
			ugol:'left',
			top:-17,
			left:211,
			indent:15
		});
	},
	kupezzObEdit = function(o) {
		o = $.extend({
			id:0,
			rubric_id:0,
			rubric_id_sub:0,
			txt:'',
			telefon:'',
			viewer_id_show:0,
			viewer_id:VIEWER_ID,
			viewer_link:VIEWER_LINK,
			active:1
		}, o);

		var html =
		(!o.id ?
			'<div class="_info">' +
				'<p>����������, ��������� ��� ����������� ����. ����� ���������� ���������� ����� ���������� �������� ��� ������ ������������� ���������.' +
				'<p>���������� ���������� ������ ��������� �� ����� ����� �������� ��� ��������� � ������ ����������, ���� ��� �������� <a onclick="kupezzObEditRule()">�������</a>.' +
				'<p>���������� ����� ��������� ������ �� 1 �����, � ���������� �� ������� �������� ���� ����.' +
			'</div>'
		: '') +
			'<table class="bs10">' +
				'<tr><td class="label r w150">�������:' +
					'<td><input type="hidden" id="add-rubric_id" value="' + o.rubric_id + '" />' +
						'<input type="hidden" id="add-rubric_id_sub" value="' + o.rubric_id_sub + '" />' +
				'<tr><td class="label r top">�����:<td><textarea id="add-txt" class="w300">' + _br(o.txt) + '</textarea>' +
				'<tr><td class="label r">���������� ��������:' +
					'<td><input type="text" id="telefon" class="w300" maxlength="200" value="' + o.telefon + '" />' +
//				'<tr><td><td>' +_imageAdd(array('owner'=>VIEWER_ID)).
				'<tr><td class="label r topi">������:' +
					'<td><input type="hidden" id="add-country_id" value="' + COUNTRY_ID + '" />' +
						'<input type="hidden" id="add-city_id" />' +
				'<tr' + (o.id && !o.viewer_id ? ' class="dn"' : '') + '>' +
					'<td class="label r">���������� ��� �� VK:' +
					'<td><input type="hidden" id="viewer_id_show" value="' + o.viewer_id_show + '" />' +
						'<div id="viewer_link" class="dn">' + o.viewer_link + '</div>' +
			
				'<tr' + (!o.id ? ' class="dn"' : '') + '>' +
					'<td class="label r topi">����������:' +
					'<td><input type="hidden" id="active" value="' + o.active + '" />' +

			'</table>' +
			'<div class="headName mt20">���������� ����������</div>' +
			'<div id="preview"></div>' +
			'<br />' +
			'<br />',
			dialog = _dialog({
				top:20,
				width:550,
				head:(o.id ? '��������������' : '�������� ������') + ' ����������',
				content:html,
				butSubmit:o.id ? '���������' : '���������� ����������',
				submit:submit
			});

		$('#add-rubric_id')._rubric({
			func:kupezzObEditPreview
		});
		$('#add-txt').autosize().focus().keyup(kupezzObEditPreview);
		$("#telefon").keyup(kupezzObEditPreview);
		$('#viewer_id_show')._check({
			func:kupezzObEditPreview
		});
		if(!COUNTRY_ASS[COUNTRY_ID]) // �������� ������� ������ � ������
			$('#add-country_id').val(0); //���� ���, ������ ������������
		cityShow();
		cityGet('', CITY_ID, CITY_NAME);
		$('#add-country_id')._select({
			width:180,
			bottom:5,
			title0:'������ �� �������',
			spisok:COUNTRY_SPISOK,
			func:function(id) {
				cityShow();
				if(id) {
					$('#city_id')._select(0)._select('process');
					VK.api('places.getCities',{country:id}, function(data) {
						var d = data.response;
						for(n = 0; n < d.length; n++)
							d[n].uid = d[n].cid;
						d[0].content = '<b>' + d[0].title + '</b>';
						$('#city_id')._select(d);
					});
				}
				kupezzObEditPreview();
			}
		});
		$('#active')._radio({
			spisok:[
				{uid:1,title:'���������� ����� ����'},
				{uid:0,title:'� ������'}
			],
			light:1
		});

		kupezzObEditPreview();

		function submit() {
			var send = kupezzObEditVal();
			send.id = o.id;
			send.op = 'kupezz_ob_' + (o.id ? 'edit' : 'create');
			if(!send.rubric_id) {
				dialog.err('�� ������� �������');
				return;
			}
			if(!send.txt) {
				dialog.err('������� ����� ����������');
				$('#add-txt').focus();
				return;
			}
			dialog.process();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					dialog.close();
					_msg();
					if(o.id) {
						var ob = $('#ob' + o.id);
						if(send.active) {
							if($('#kupezz-ob').length)
								ob.after(res.ob);
							if($('#kupezz-my').length)
								ob.after(res.my);
						}
						ob.remove();
						return;
					}
					if($('#kupezz-ob').length)
						$('.left').prepend(res.ob);
					if($('#kupezz-my').length)
						$('.left').prepend(res.my);
				} else
					dialog.abort(res.text);
			}, 'json');
		}

	},
	kupezzObEditVal = function() {
		return {
			rubric_id:_num($('#add-rubric_id').val()),
			rubric_id_sub:_num($('#add-rubric_id_sub').val()),
			txt:$.trim($('#add-txt').val()),
			telefon:$('#telefon').val(),
			country_id:_num($('#add-country_id').val()),
			country_name:$('#add-country_id')._select('title'),
			city_id:_num($('#add-city_id').val()),
			city_name:$('#add-city_id')._select('title'),
			viewer_id_show:_bool($('#viewer_id_show').val()),
			active:_bool($('#active').val()),
			upload_url:'',
			group_id:72078602, //������ ������
			album_id:195528889, //�������� ������
			rule:0
		};
	},
	kupezzObEditRule = function() {
		var html =
			'<div id="ob-create-rules">' +
				'<div class="headName">������������ ��� �������� ����������:</div>' +
				'<ul><li>����� �������� ���������� ���� �����;' +
					'<li>�� ����������� ���������� ����������, ����� ������� ������������� ����� ��������� ������� ���������� ��, ��� �� �����������; ' +
						'���������� ��������� ��������� �� <u>8-� �����������</u> �� ���� ����������;' +
					'<li>����������� ���������� �������� ����;' +
					'<li>�� ��������� ���� � �� �� ���������� ��������, ��� ����� ���� ����������� ��������� ������� �������. ' +
						'��������� ���������� ����� ���������;' +
					'<li>�� ������ ���������� � ������� ��������;' +
					'<li>���������� ����� ����������� �������� � ��������������� ����;' +
					'<li>���� ���� ��������� ��� �� ���������, ������� ��� ��� ���������� � ����� � ������� "��� ����������".' +
				'</ul>' +

				'<div class=headName>������, ������� ������� �� �����������:</div>' +
				'<ul><li>�������, ������������ � (���) ���������� ������� ��������� ����������������� ���������� ���������;' +
					'<li>������������� �������, ������������ ������� � �����������;' +
					'<li>���������� ������� � ����������, �� ����������� ��������������� �������;' +
					'<li>������� � (���) ������ �������� � �������� �������� �����-�������;' +
					'<li>�������, ���������� ��������������� �����������, � ������ ���������� ����� �����������;' +
					'<li>�������, ���������� ������������ ������������ ��� ����� ������������� ������������� ' +
						'������������ ����������� ����������� �����������, � ������ ���������� ����� ������������ ' +
						'��� ������������� ������ ������������;' +
					'<li>������, �� ������������ � (���) ���������� ������� ��������� ��������� �������� ' +
						'��� ���� ����������� ����������, � ������ ���������� ����� ����������.' +
				'</ul>' +
			'</div>';
		_dialog({
			top:10,
			width:500,
			head:'������� ���������� ����������',
			content:html,
			butSubmit:'',
			butCancel:'�������'
		});
	},
	kupezzObEditPreview = function() {
		var v = kupezzObEditVal(),
			html =
				'<div class="ob-unit preview">' +
					'<table class="utab">' +
						'<tr><td class="txt">' +
//				  (img_id ? '<img src="' + img_url + '" class="_iview" val="' + img_id + '" />' : '') +
			 (v.rubric_id ? '<span class="rub">' + RUBRIC_ASS[v.rubric_id] + '</span><u>�</u>' : '') +
		 (v.rubric_id_sub ? '<span class="rubsub">' + $('#add-rubric_id_sub')._select('title') + '</span><u>�</u>' : '') +
							_br(v.txt, 1) +
			  (v.telefon ? '<div class="tel">' + v.telefon + '</div>' : '') +
					'<tr><td class="adres" colspan="2">' +
						(v.country_id ? v.country_name : '') +
						(v.city_id ? ', ' + v.city_name : '') +
						(v.viewer_id_show ? $('#viewer_link').html()  : '') +
					'</table>' +
				'</div>';
		$('#preview').html(html);
	},
	kupezzObSpisok = function(v, id) {
		if(id == 'country_id') {
			$('.city-sel')[(v ? 'remove' : 'add') + 'Class']('dn');
			$('#city_id')._select(0);
			$('#city_id')._select(CITIES[v]);
		}

		_filterSpisok(KUPEZZ_OB, v, id);
		$.post(AJAX_MAIN, KUPEZZ_OB, function(res) {
			if(res.success) {
				$('.result').html(res.result);
				$('.left').html(res.spisok);
			}
		}, 'json');

	},
	_post = function(o) {
		o = $.extend({
			id:0,
			viewer_id:0,
			viewer_id_show:0,
			viewer_name:'',
			viewer_photo:'',
			dtime:'���� � �����',
			sa_zayav_id:0,
			sa_viewer_id:0,
			sa_name:''
		}, o);

		var html =
			'<div id="_post">' +
				'<div class="head">' +
					'<table>' +
						'<tr>' +
		(o.viewer_id_show ? '<td class="im"><a href="//vk.com/id' + o.viewer_id + '" target="_blank">' + o.viewer_photo + '</a>' : '') +
							'<td>' +
								'<a class="close">�������</a>' +
			(o.viewer_id_show ? '<a class="uname" href="//vk.com/id' + o.viewer_id + '" target="_blank">' + o.viewer_name + '</a>' : '') +
								'<div class="dtime">' + o.dtime + '</div>' +
					'</table>' +
				'</div>' +
			(o.sa ?
				'<div class="psa">' +
					(o.sa_zayav_id ? '������' : '') +
					(o.sa_viewer_id ? '<a href="' + URL + '&p=admin&id=' + o.sa_viewer_id + '">' + o.sa_name + '</a>' : '') +
					'<div class="ed">' +
						'<a class="to-arch">� �����</a>' +
						'<div class="img_edit"></div>' +
					'</div>' +
				'</div>'
			: '') +
				'<div class="pcont">' +
					'<div class="rub">' + o.rub + '</div>' +
					'<div class="txt">' + _br(o.txt, 1) + '</div>' +
		(o.images ? '<div class="images">' + o.images + '</div>' : '') +
	   (o.telefon ? '<div class="tel">' + o.telefon + '</div>' : '') +
		  (o.city ? '<div class="city">' + o.city + '</div>' : '') +
					'<div class="meter">���������: ' + o.view + '</div>' +
				'</div>' +
				'<div class="foot">' +
					'<div class="msg">' + (o.msg ? o.msg : '') + '</div>' +
					'<input type="text" id="inp" placeholder="��������� ��������� ������ ����������.." />' +
					'<table class="dn">' +
						'<tr><td class="photo"><a href="http://vk.com/id' + VIEWER_ID + '" target="_blank">' + U.photo + '</a>' +
							'<td><textarea></textarea>' +
						'<tr><td class="photo">' +
							'<td class="send"><button class="vk">���������</button>' +
								'<input type="hidden" id="anon" />' +
								'<input type="hidden" id="only_author" />' +
					'</table>' +
				'</div>' +
			'</div>';

		if($('#_post').length)
			close();

		var post = $('body').append(html).find('#_post'),
			h,
			area = post.find('textarea');
		_backfon(post);
		if(o.images)
			h = 10;
		else {
			h = 540 - post.height();
			h = Math.round(h < 0 ? 10 : h / 2);
		}
		post.css('top', $(this).scrollTop() + (VK_SCROLL > 60 ? VK_SCROLL - 60 : 0) + h);
		post.find('.close').click(close);
		post.find('.img_edit').click(function() {
			close();
			kupezzObEdit(o);
		});
		post.find('.to-arch').click(function() {
			var t = $(this),
				send = {
					op:'kupezz_ob_archive',
					id:o.id
				};
			t.hide();
			$.post(AJAX_MAIN, send, function(res) {
				if(res.success) {
					close();
					$('#ob' + o.id).remove();
				} else
					t.fadeIn(700);
			}, 'json');
		});
		post.find('#inp').focus(function() {
			$(this).addClass('dn');
			post.find('.foot table').removeClass('dn');
			area.focus().autosize();
			$('#anon')._check({
				name:'��������',
				func:function(v) {
					post.find('.photo')[(v ? 'add' : 'remove') + 'Class']('dn');
					area.width(v ? 496 : 436).focus();
				}
			});
			$('#only_author')._check({
				name:'������ ��� ����������'
			});
			$('#only_author_check').vkHint({
				msg:'���� ������� �����������,<br />�� ��������� ����� �����<br />������ ������ ����������.',
				top:-103,
				left:108
			});
			post.find('.foot .vkButton').click(function() {
				var t = $(this),
					send = {
						op:'ob_post_msg',
						id:o.id,
						txt:$.trim(area.val()),
						anon:$('#anon').val(),
						only_author:$('#only_author').val()
					};
				if(!send.txt || t.hasClass('_busy'))
					return;
				t.addClass('_busy');
				$.post(AJAX_MAIN, send, function(res) {
					t.removeClass('_busy');
					if(res.success) {
						area.val('');
						post.find('.msg').append(res.msg);
					}
				}, 'json');
			});
		});
		function close() {
			$('#_post').remove();
			_backfon(false);
		}
	};

$(document)
	.on('click', '#kupezz-ob .vk.red', function() {//������� ������� ����������
		$('#find')._search('clear');    KUPEZZ_OB.find = '';
		$('#country_id')._select(1);	KUPEZZ_OB.country_id = 1;
										KUPEZZ_OB.city_id = 0;
		$('#rub').rightLink(0);         KUPEZZ_OB.rubric_id = 0;
										KUPEZZ_OB.rubric_id_sub = 0;
		$('#withfoto')._check(0);		KUPEZZ_OB.withfoto = 0;
		$('#nokupez')._check(0);		KUPEZZ_OB.nokupez = 0;

		kupezzObSpisok(1, 'country_id');
	})
	.on('mouseover', '#kupezz-ob .edited,#kupezz-my .edited', function() {
		$(this).removeClass('edited');
	})
	.on('click', '.ob-unit a.rub', function(e) {
		e.stopPropagation();
		var v = _num($(this).attr('val'));
		$('#rub').rightLink(v);
		$('#rubsub').val(0);
		kupezzObSpisok(v, 'rubric_id');
	})
	.on('click', '.ob-unit a.rubsub', function(e) {
		e.stopPropagation();
		var v = $(this).attr('val').split('_'),
			rub_id = _num(v[0]),
			sub_id = _num(v[1]);
		$('#rub').rightLink(rub_id);
		$('#rubsub').val(sub_id);
		KUPEZZ_OB.rubric_id_sub = sub_id;
		kupezzObSpisok(v, 'rubric_id');
	})
	.on('click', '#kupezz-ob .ob-unit', function() {
		var t = $(this),
			full = t.find('.full');
		if(full.length) {
			full.next().removeClass('dn');
			full.remove();
			return;
		}
		var send = {
			op:'kupezz_ob_post',
			id:t.attr('val')
		};
		_wait();
		$.post(AJAX_MAIN, send, function(res) {
			_wait(false);
			if(res.success)
				_post(res);
		}, 'json');
	})
	.on('click', '#kupezz-my .img_edit', function() {//�������������� ���������� �� ���� ����������
		var t = $(this),
			send = {
				op:'kupezz_ob_load',
				id:t.attr('val')
			},
			dialog = _dialog({
				top:20,
				width:550,
				head:'�������������� ����������',
				load:1,
				butSubmit:''
			});

		$.post(AJAX_MAIN, send, function(res) {
			if(res.success) {
				dialog.close();
				kupezzObEdit(res);
			} else
				dialog.loadError();
		}, 'json');
	})

	.ready(function() {
		if($('#kupezz-ob').length) {
			_busy('set', $('.region'));
			$('#find')._search({
				width:300,
				focus:1,
				enter:1,
				txt:'������� ����� ����������',
				func:kupezzObSpisok
			});
			$('#country_id')._select({
				width:140,
				title0:'������ �� �������',
				spisok:COUNTRIES,
				func:kupezzObSpisok
			});
			var v = $('#country_id').val();
			$('#city_id')._select({
				width:140,
				title0:'����� �� ������',
				spisok:v ? CITIES[v] : [],
				func:kupezzObSpisok
			});
			$('#rub').rightLink(function(v) {
				$('#rubsub').val(0);
				kupezzObSpisok(v, 'rubric_id');
			});
			$('#withfoto')._check(kupezzObSpisok);
			$('#nokupez')._check(kupezzObSpisok);
		}
	});
