document.addEventListener('DOMContentLoaded', function () {

	// Price
	function getPrice() {
		console.log("depart", $('#depart').val());
		console.log("retour", $('#retour').val());
		if (
			$('#depart').val().length === 0
			|| $('#retour').val().length === 0
		)
			return;
		// On fait la liste des options dans une variables javascript
		$.ajax({
			type: 'GET',
			url: external_object.properties.api.price_endpoint,
			data: $('#reservation').serialize(),
			processData: true,
			dataType: 'json',
			async: false,
			error: function (e, f, g) {
				console.error("get price", e, f, g);
				// $('#footer').html('Error : get price');
			},
			success: function (data) {
				console.log('data', data);
				if (data.toolong === 0) {
					if (data.complet === 0) {
						const total = parseInt(!isNaN(parseFloat(data.total)) ? data.total: '0');
						$('#submit').attr('disabled', (0 >= total) ? "true" : "false");
						$('div.total span').html(total + ' €');

					} else {
						console.error(data.complet);
						$('#submit').attr('disabled', "true");
						$('div.total span').html('0 €');
					}
				} else {
					console.error(data.toolong);
					$('#submit').attr('disabled', "true");
					$('div.total span').html('0 €');
				}
			}
		});
	}

	$('#type_id, #depart, #return, #nb_pax').on('change', function () {
		getPrice();
	})
	// Date picker
	const DateTime = easepick.DateTime;

	function initializeDateTimePickers() {
		$('.departure').each(function () {
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
			const endDateInput = $('#retour').get(0);
			new easepick.create({
				element: this,
				lang: 'fr-FR',
				autoApply: false,
				plugins: ['RangePlugin', 'LockPlugin', 'TimePlugin'],
				css: [
					'https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.1/dist/index.css',
				],
				format: 'DD/MM/YYYY HH:mm',
				zIndex: 50,
				setup(picker) {
					picker.on('select', () => {
						getPrice();
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
				},
				TimePlugin: {
					format: 'HH:mm',
				}
			});
		});

	}

	initializeDateTimePickers();

	// Zip Codes
	$("#code_postal").catcomplete({
		source: external_object.properties.api.zip_codes_endpoint,
		dataType: "json",
		minLength: 2,
		select: function (event, ui) {
			$('#ville').val(ui.item.ville);
			$('#pays').val(ui.item.pays_id);
		}
	});

	// Models vehicle
	$("#modele").catcomplete({
		source: external_object.properties.api.models_vehicle_endpoint,
		dataType: "json",
		minLength: 2,
		select: function (event, ui) {
			$('#marque').val(ui.item.category);
		}
	});

	// Destination
	$('#destination').catcomplete({
		source: external_object.properties.api.destinations_endpoint,
		dataType: "json",
		minLength: 2,
		select: function (event, ui) {
			$('#destination_id').val(ui.item.id);
		}
	});

	// Phone lib
	const indicative = external_object.properties.form.indicative ? external_object.properties.form.indicative : "fr";
	const tel_input = document.querySelector('#tel_port');
	window.intlTelInput(tel_input, {
		utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/js/utils.js",
		formatOnDisplay: false,
		separateDialCode: false,
		autoPlaceholder: "polite",
		autoHideDialCode: true,
		nationalMode: true,
		initialCountry: "auto",
		strictMode: true,
		geoIpLookup: callback => {
			fetch("https://ipapi.co/json")
				.then(res => res.json())
				.then(data => callback(data.country_code))
				.catch(() => callback("fr"));
		},
		onlyCountries: ["fr", "be", "ch", "de", "nl"],
		preferredCountries: [indicative]
	});

});

// Widget auto completion
$.widget("custom.catcomplete", $.ui.autocomplete, {
	_create: function () {
		this._super();
		this.widget().menu("option", "items", "> :not(.ui-autocomplete-category)");
	},
	_renderMenu: function (ul, items) {
		let that = this,
			currentCategory = "";
		$.each(items, function (index, item) {
			let li;
			if (item.category !== currentCategory) {
				ul.append("<li class='ui-autocomplete-category'>" + item.category + "</li>");
				currentCategory = item.category;
			}
			li = that._renderItemData(ul, item);
			if (item.category) {
				li.attr("aria-label", item.category + " : " + item.label);
			}
		});
	}
});


