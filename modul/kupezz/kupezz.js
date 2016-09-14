var kupezzObSpisok = function(v, id) {
	_filterSpisok(KUPEZZ_OB, v, id);
	$.post(AJAX_MAIN, KUPEZZ_OB, function(res) {
		if(res.success) {
			$('.result').html(res.result);
			$('.left').html(res.spisok);
		}
	}, 'json');

};

$(document)
	.ready(function() {
		if($('#kupezz-ob').length) {
			_busy('set', $('.region'));
			$('#find')._search({
				width:300,
				focus:1,
				enter:1,
				txt:'Быстрый поиск объявлений',
				func:kupezzObSpisok
			});
			$('#country_id')._select({
				width:140,
				title0:'Страна не выбрана',
				spisok:COUNTRIES,
				func:function(id) {
					$('#city_id')._select(0);
					$('#city_id')._select(CITIES[id]);
					$('.city-sel')[(id ? 'remove' : 'add') + 'Class']('dn');
					kupezzObSpisok();
				}
			});
			var v = $('#country_id').val();
			$('#city_id')._select({
				width:140,
				title0:'Город не выбран',
				spisok:v ? CITIES[v] : [],
				func:kupezzObSpisok
			});
			$('#rub').rightLink(function() {
				$('#rubsub').val(0);
				kupezzObSpisok();
			});
			$('#withfoto')._check(kupezzObSpisok);
			$('#nokupez')._check(kupezzObSpisok);
		}
	});
