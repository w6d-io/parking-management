function priceSuccess(data) {
	loadTranslation('en')
		.then(i18n => {
				if (data.toolong === 0) {
					if (data.complet === 0) {
						const total = parseInt(!isNaN(parseFloat(data.total)) ? data.total : '0');
						$('#submit').html(
							'<i class="fa fa-jet-fighter"></i> ' +
							i18n.translate('reservation-for')
								.replace('{total}', total + ' €')
						);
					} else {
						$('#submit').html(
							'<i class="fa-solid fa-hand-point-up"></i> ' +
							i18n.translate('quote_in_two_clicks')
						);
					}
				} else {
					$('#submit').html(
						'<i class="fa-solid fa-hand-point-up"></i> ' +
						i18n.translate('quote_in_two_clicks')
					);
				}
			}
		)
		.catch(error => {
			console.error('Translation loading failed:', error);
		});

}

document.addEventListener('DOMContentLoaded', function () {
	let highSeason = getHighSeason();
	let bookedDates = getBookedDates();

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
					getPrice('quote', {onSuccess: priceSuccess});
				}
			);
		});
	}

	initializeDateTimePickers();

	getPrice('quote', {onSuccess: priceSuccess});
});
