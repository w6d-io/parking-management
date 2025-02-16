document.addEventListener('DOMContentLoaded', function () {

	function loadScript(url) {
		const script = document.createElement("script");
		script.type = "text/javascript";
		script.src = url;
		document.getElementsByTagName("head")[0].appendChild(script);
	}

	// Price
	function getPrice() {
		if (
			$('#depart').val().length === 0
			|| $('#retour').val().length === 0
		)
			return;
		// On fait la liste des options dans une variables javascript
		$.ajax({
			type: 'GET',
			url: '/wp-json/pkmgmt/v1/prices',
			data: $('#reservation').serialize(),
			processData: true,
			dataType: 'json',
			async: false,
			error: function (e, f, g) {
				console.error("get price failed", f);
			},
			success: function (data) {
				if (data.toolong === 0) {
					if (data.complet === 0) {
						const total = parseInt(!isNaN(parseFloat(data.total)) ? data.total : '0');
						$('#total_amount').val(total);
						$('div.total span').html(total + ' €');

					} else {
						console.error(data.complet);
						$('#total_amount').val('0');
						$('div.total span').html('0 €');
					}
				} else {
					console.error(data.toolong);
					$('#total_amount').val('0');
					$('div.total span').html('0 €');
				}
				switchSubmitBtn();
			}
		});
	}
	getPrice();
	function switchSubmitBtn() {
		if (
			parseInt($('#total_amount').val()) > 0
			&& (external_object.form_options.terms_and_conditions == '0' || $('#cgv_reservation').is(':checked'))
		)
			$("#submit").removeAttr("disabled");
		else
			$("#submit").attr("disabled", "disabled");
	}

	$('#cgv_reservation').on('change', function () {
		switchSubmitBtn($(this).is(':checked'))
	});
	$('#depart, #return, #nb_pax, #assurance_annulation, input.type-id, input.parking-type').on('change', function () {
		getPrice();
	})

	// Date picker
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
			if (data instanceof Array)
			{
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
			if (data instanceof Array)
			{
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
	function syncValues(source, target) {
		$(target).val($(source).val());
	}

	function easepickCreate(startDateInput, endDateInput, calendars, callback) {
		new easepick.create({
			element: startDateInput,
			lang: 'fr-FR',
			autoApply: false,
			locale: {
				apply: "OK"
			},
			calendars: calendars,
			grid: calendars,
			plugins: ['RangePlugin', 'LockPlugin', 'TimePlugin'],
			css: [
				'https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.1/dist/index.css',
				external_object.form_css,
			],
			format: 'YYYY-MM-DD HH:mm',
			zIndex: 50,
			setup(picker) {
				picker.on('select', () => {
					callback();
				});
				picker.on('view', (evt) => {
					const {view, date, target} = evt.detail;
					if ( view === 'CalendarDay' && date.inArray(highSeason, '[]') ) {
						target.classList.add('high-season');
					}
					// target.append();
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
			},
			TimePlugin: {
				format: 'HH:mm',
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
					if (external_object.form_options.dialog_confirmation === '1') {
						syncValues('#depart', '#depart2');
						syncValues('#retour', '#retour2');
					}
				}
			);
		});
	}

	initializeDateTimePickers();


	// Zip Codes
	$("#code_postal").catcomplete({
		source: '/wp-json/pkmgmt/v1/zipcode',
		dataType: "json",
		minLength: 2,
		select: function (event, ui) {
			$('#ville').val(ui.item.ville);
			$('#pays').val(ui.item.pays_id);
		}
	});

	// Models vehicle
	$("#modele").catcomplete({
		source: '/wp-json/pkmgmt/v1/vehicle',
		dataType: "json",
		minLength: 2,
		select: function (event, ui) {
			$('#marque').val(ui.item.category);
		}
	});

	// Destination
	$('#destination').catcomplete({
		source: '/wp-json/pkmgmt/v1/destination',
		dataType: "json",
		minLength: 2,
		select: function (event, ui) {
			$('#destination_id').val(ui.item.id);
		}
	});

	// Phone lib
	const indicative = external_object.properties.form.indicative ? external_object.properties.form.indicative : "fr";
	const mobileInput = document.querySelector('#mobile');
	const iti = window.intlTelInput(mobileInput, {
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

	$('#mobile').on('change', function () {
		$('#tel_port').val(iti.getNumber());
	})
	// Confirmation dialog
	let dialogConfirm;
	if (external_object.form_options.dialog_confirmation === '1') {

		$('#email').on('change', function () {
			$('#email2').val(this.value);
		})
		$('#email2').on('change', function () {
			$('#email').val(this.value);
		})
		dialogConfirm = $('#dialog_booking_confirmation').dialog({
			autoOpen: false,
			height: 530,
			width: 380,
			modal: true
		});
		$('#confirmation').validate({
			submitHandler: function (form) {
				$('#spinner-container').css('display', 'flex');
				$.each($('#reservation').serializeArray(), function (i, field) {
					$('<input>').attr({
						type: 'hidden',
						name: field.name,
						value: field.value
					}).appendTo(form);
				})
				form.submit();
			}
		})
	}

	// Validation
	$.validator.addMethod(
		"customMobile",
		function () {
			return !!iti.isValidNumber();
		},
		"Vérifiez le numéro de téléphone"
	);

	if (external_object.locale === 'fr_FR')
		loadScript("https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/localization/messages_fr.min.js");
	if (external_object.locale === 'de_DE')
		loadScript("https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/localization/messages_de.min.js");
	if (external_object.locale === 'nl_NL')
		loadScript("https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/localization/messages_nl.min.js");
	$("#reservation").validate({
		rules: {
			nom: {
				minlength: 2,
				required: true
			},
			prenom: {
				minlength: 2,
				required: true
			},
			code_postal: {
				digits: true,
				required: true
			},
			mobile: {
				customMobile: true,
				required: true
			},
			email: {
				required: true,
				email: true
			},
			modele: {
				required: true,
				minlength: 2
			},
			immatriculation: {
				required: true,
				minlength: 2
			},
			destination: {
				required: true,
				minlength: 2
			},
			depart: {
				required: true,
			},
			retour: {
				required: true,
			},
			np_pax: {
				required: true,
			}
		},

		submitHandler: function (form) {
			if (external_object.form_options.dialog_confirmation === '1') {
				easepickCreate(
					$('#depart2').get(0),
					$('#retour2').get(0),
					1,
					function () {
						syncValues('#depart2', '#depart');
						syncValues('#retour2', '#retour');
						getPrice();
					}
				);
				dialogConfirm.dialog("open");
			} else {
				$('#spinner-container').css('display', 'flex');
				form.submit();
			}
		}
	})
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


