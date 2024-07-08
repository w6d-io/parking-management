document.addEventListener('DOMContentLoaded', function () {

	const DateTime = easepick.DateTime;
	const bookedDates = [
		'2024-06-02',
		['2024-06-18', '2024-06-20'],
		'2024-06-25',
		'2024-06-28',
	].map(d => {
		if (d instanceof Array) {
			const start = new DateTime(d[0], 'YYYY-MM-DD');
			const end = new DateTime(d[1], 'YYYY-MM-DD');
			return [start, end];
		}
		return new DateTime(d, 'YYYY-MM-DD');
	});
	function easepickCreate(startDateInput, endDateInput, calendars, callback) {
		new easepick.create({
			element: startDateInput,
			lang: 'fr-FR',
			autoApply: true,
			calendars: calendars,
			grid: calendars,
			plugins: ['RangePlugin', 'LockPlugin'],
			css: [
				'https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.1/dist/index.css',
			],
			format: 'DD/MM/YYYY',
			zIndex: 50,
			setup(picker) {
				picker.on('select', () => {
					callback();
				});
			},
			RangePlugin: {
				elementEnd: endDateInput,
				minDate: new Date(),
			},
			LockPlugin: {
				minDate: new Date(),
				inseparable: false,
				filter(date, picked) {
					if (picked.length === 1) {
						const incl = date.isBefore(picked[0]) ? '[)' : '(]';
						return !picked[0].isSame(date, 'day') && date.inArray(bookedDates, incl);
					}
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

	// Price
	function getPrice() {
		if (
			$('#depart').val().length === 0
			|| $('#retour').val().length === 0
		)
			return;
		$.ajax({
			type: 'GET',
			url: external_object.properties.api.price_endpoint,
			data: $('#quote').serialize(),
			processData: true,
			dataType: 'json',
			async: false,
			error: function (e, f, g) {
				console.error("get price", e, f, g);
			},
			success: function (data) {
				console.log("data",data);

				if (data.toolong === 0) {
					if (data.complet === 0) {
						const total = parseInt(!isNaN(parseFloat(data.total)) ? data.total : '0');
						$('#submit').html('<i class="fa fa-jet-fighter"></i> '+'Your reservation for ' + total + ' €');

					} else {
						console.log(data.complet);
						$('#submit').html('<i class="fa-solid fa-hand-point-up"></i> Votre devis en 2 click');
					}
				} else {
					console.log(data.toolong);
					$('#submit').html('<i class="fa-solid fa-hand-point-up"></i> Votre devis en 2 click');
				}
			}
		});
	}
	getPrice();

});
