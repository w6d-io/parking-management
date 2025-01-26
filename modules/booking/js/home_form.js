const translations = {};
function loadTranslation(lang) {
	$.getJSON(external_object.i18n_path + '/' + `${lang}.json`, function (data) {
		translations[lang] = data;
	}).fail(function () {
		console.error(`Fail to load ${lang}.json`);
	});
}

function translate(key, lang) {
	return translations?.[lang]?.[key] || key;
}
document.addEventListener('DOMContentLoaded', function () {
	loadTranslation(external_object.locale);
	const DateTime = easepick.DateTime;

	let highSeason = [];
	$.ajax({
		type: 'GET',
		url: '/wp-json/pkmgmt/v1/high-season',
		dataType: 'json',
		async: false,
		error: function (e, f, g) {
			console.error("get high season failed", f);
		},
		success: function (data) {
			if (data instanceof Array) {
				highSeason = data.map(d => {
					if (d instanceof Array) {
						const start = new DateTime(d[0], 'YYYY-MM-DD');
						const end = new DateTime(d[1], 'YYYY-MM-DD');
						return [start, end];
					}
					return new DateTime(d, 'YYYY-MM-DD');
				});
			}
		}
	});

	let bookedDates = [];
	$.ajax({
		type: 'GET',
		url: '/wp-json/pkmgmt/v1/booked',
		dataType: 'json',
		async: false,
		error: function (e, f, g) {
			console.error("get booked failed", f);
		},
		success: function (data) {
			if (data instanceof Array) {
				bookedDates = data.map(d => {
					if (d instanceof Array) {
						const start = new DateTime(d[0], 'YYYY-MM-DD');
						const end = new DateTime(d[1], 'YYYY-MM-DD');
						return [start, end];
					}
					return new DateTime(d, 'YYYY-MM-DD');
				});
			}
		}
	});

	function easepickCreate(startDateInput, endDateInput, calendars, callback, format = 'YYYY-MM-DD') {
		new easepick.create({
			element: startDateInput,
			lang: 'fr-FR',
			autoApply: true,
			calendars: calendars,
			grid: calendars,
			plugins: ['RangePlugin', 'LockPlugin'],
			css: [
				'https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.1/dist/index.css',
				external_object.home_form_css,
			],
			format: format,
			zIndex: 50,
			setup(picker) {
				picker.on('select', () => {
					callback();
				});
				picker.on('view', (evt) => {
					const {view, date, target} = evt.detail;
					if (view === 'CalendarDay' && date.inArray(highSeason, '[]')) {
						target.classList.add('high-season');
					}
				});
			},
			RangePlugin: {
				elementEnd: endDateInput,
				minDate: new Date(),
				locale: {
					one: "jour",
					other: "jours"
				}
			},
			LockPlugin: {
				minDate: new Date(),
				inseparable: false,
				filter(date) {
					return date.inArray(bookedDates, '[]');
				},
			}
		});
	}

	function initializeDateTimePickers() {
		$('#depart').each(function () {
			easepickCreate(
				$('#depart').get(0),
				$('#retour').get(0),
				2,
				function () {
					getPrice();
				}
			);
		});
	}

	initializeDateTimePickers();

	function getPrice() {
		if (
			$('#depart').val().length === 0
			|| $('#retour').val().length === 0
		)
			return;
		$.ajax({
			type: 'GET',
			url: '/wp-json/pkmgmt/v1/prices',
			data: $('#quote').serialize(),
			processData: true,
			dataType: 'json',
			async: false,
			error: function (e, f, g) {
				console.error("get price", e, f, g);
			},
			success: function (data) {
				if (data.toolong === 0) {
					if (data.complet === 0) {
						const total = parseInt(!isNaN(parseFloat(data.total)) ? data.total : '0');
						$('#submit').html(
							'<i class="fa fa-jet-fighter"></i> ' +
							translate('reservation-for', external_object.locale)
								.replace('{total}', total + ' €')
						);
					} else {
						$('#submit').html(
							'<i class="fa-solid fa-hand-point-up"></i> ' +
							translate('quote_in_two_clicks', external_object.locale)
						);
					}
				} else {
					$('#submit').html(
						'<i class="fa-solid fa-hand-point-up"></i> ' +
						translate('quote_in_two_clicks', external_object.locale)
					);
				}
			}
		});
	}
	getPrice();
});
