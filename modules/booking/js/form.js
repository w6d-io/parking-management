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
				// $('#footer').html('Error : get price');
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
			&& (external_object.properties.form.booking.terms_and_conditions == '0' || $('#cgv_reservation').is(':checked'))
		)
			$("#submit").removeAttr("disabled");
		else
			$("#submit").attr("disabled", "disabled");
	}

	$('#cgv_reservation').on('change', function () {
		switchSubmitBtn($(this).is(':checked'))
	});
	$('#type_id, #depart, #return, #nb_pax, #assurance_annulation').on('change', function () {
		getPrice();
	})

	// Date picker
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

	function syncValues(source, target) {
		$(target).val($(source).val());
	}

	function easepickCreate(startDateInput, endDateInput, calendars, callback) {
		new easepick.create({
			element: startDateInput,
			lang: 'fr-FR',
			autoApply: false,
			calendars: calendars,
			grid: calendars,
			plugins: ['RangePlugin', 'LockPlugin', 'TimePlugin'],
			css: [
				'https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.1/dist/index.css',
			],
			format: 'DD/MM/YYYY HH:mm',
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
					if (external_object.properties.form.booking.dialog_confirmation === '1') {
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
	const tel_input = document.querySelector('#tel_port');
	const iti = window.intlTelInput(tel_input, {
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

	// Confirmation dialog
	if (external_object.properties.form.booking.dialog_confirmation === '1') {

		$('#email').on('change', function (){
			$('#email2').val(this.value);
		})
		$('#email2').on('change', function (){
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
			console.log('tel validation');
			return !!iti.isValidNumber();
		},
		"Vérifiez le numéro de téléphone"
	);

	$.validator.addMethod(
		"greaterThan",
		function (value) {
			console.log("value", value);
			return this.optional(element) || value != 0;
		}
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
				greaterThan: true,
			}
		},

		submitHandler: function () {
			if (external_object.properties.form.booking.dialog_confirmation === '1') {
				console.log("confirmation dialog opening");
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
				console.log("submit");
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


