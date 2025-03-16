function switchSubmitBtn() {
	if (
		parseInt($('#total_amount').val()) > 0
		&& (external_object.form_options.terms_and_conditions == '0' || $('#cgv_reservation').is(':checked'))
	)
		$("#submit").removeAttr("disabled");
	else
		$("#submit").attr("disabled", "disabled");
}

function priceSuccess(data) {
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
		console.log(data);
		$('#total_amount').val('0');
		$('div.total span').html('0 €');
	}
	switchSubmitBtn();
}

function showDropdown(buttonId) {
	const dtb = document.getElementById(buttonId);
	bootstrap.Dropdown.getOrCreateInstance(dtb).show();
}

document.addEventListener('DOMContentLoaded', function () {

	getPrice('reservation', {onSuccess: priceSuccess});

	$('#cgv_reservation').on('change', function () {
		switchSubmitBtn($(this).is(':checked'))
	});

	$('#depart, #return, #nb_pax, #assurance_annulation, input.type-id, input.parking-type').on('change', function () {
		getPrice('reservation', {onSuccess: priceSuccess});
	})

	const retourConfig = {
		dateField: '#return-date',
		timeField: '#return-time',
		targetField: '#retour'
	};
	const departConfig = {
		dateField: '#departure-date',
		timeField: '#departure-time',
		targetField: '#depart'
	};


    // Date picker
	const dateTime = easepick.DateTime;
	const highSeason = getHighSeason();
	const bookedDates = getBookedDates();

	function syncValues(source, target) {
		$(target).val($(source).val());
	}

	function easepickCreate2(DateInput, calendars, config = {}) {
		const defaults = {
			onSelect: function () {
			},
			onClear: function () {
			},
			minDate: null
		}
		const minDate = defaults.minDate || new Date();
		const settings = {...defaults, ...config}

		return new easepick.create({
			element: DateInput,
			lang: 'fr-FR',
			autoApply: true,
			locale: {
				apply: "OK"
			},
			calendars: calendars,
			grid: calendars,
			plugins: ['LockPlugin'],
			css: [
				'https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.1/dist/index.css',
				external_object.form_css,
			],
			format: 'YYYY-MM-DD',
			zIndex: 50,
			setup(picker) {
				picker.on('select', () => {
					settings.onSelect();
				});
				picker.on('view', (evt) => {
					const {view, date, target} = evt.detail;
					if (view === 'CalendarDay' && date.inArray(highSeason, '[]')) {
						target.classList.add('high-season');
					}
				});
				picker.on('clear', () => {
					settings.onClear();
				});
			},
			LockPlugin: {
				minDate: minDate,
				inseparable: false,
				filter(date) {
					return date.inArray(bookedDates, '[]');
				},
			}
		});
	}

	function initializeDateTimePickers() {
		const retourPick = easepickCreate2(
			$('.return').get(0),
			1,
			{
				onSelect: function () {
					$('#return-date').attr('data-status', 'updated');
					updateCombinedDateTime(retourConfig);
					getPrice('reservation', {onSuccess: priceSuccess});
					if (external_object.form_options.dialog_confirmation === '1') {
						syncValues('#depart', '#depart2');
						syncValues('#retour', '#retour2');
					}
					if ($('#return-time').attr('data-status') !== 'updated') {
						setTimeout(function () {
							showDropdown('return-time-button')
						}, 500);
					}
				}
			}
		);
		const departure = easepickCreate2(
			$('.departure').get(0),
			1,
			{
				onSelect: function () {
					updateCombinedDateTime(departConfig);
					const lp = retourPick.PluginManager.getInstance('LockPlugin');
					const departDateTime = new dateTime($('#depart').val(), 'YYYY-MM-DD HH:mm');
					console.log('previous return lockplugin options', lp.options);
					lp.options.minDate = departDateTime.toJSDate();
					console.log('set min in return', departDateTime.toJSDate());
					console.log('after return lockplugin options', lp.options);
					if (external_object.form_options.dialog_confirmation === '1') {
						syncValues('#depart', '#depart2');
						syncValues('#retour', '#retour2');
					}
					if ($('#departure-time').attr('data-status') !== 'updated') {
						setTimeout(function () {
							showDropdown('departure-time-button')
						}, 500);
					}
				}
			}
		);
		const retourCustom = initCustomDropdown('div-return-time', 'return-time', {
			onChange: (value, text) => {
				$('#return-time').attr('data-status', 'updated');
				updateCombinedDateTime(departConfig);
				getPrice('reservation', {onSuccess: priceSuccess});
				if (external_object.form_options.dialog_confirmation === '1') {
					syncValues('#depart', '#depart2');
					syncValues('#retour', '#retour2');
				}
			}
		});
		initCustomDropdown('div-departure-time', 'departure-time', {
			onChange: (value, text) => {
				$('#departure-time').attr('data-status', 'updated');
				updateCombinedDateTime(departConfig);
				getPrice('reservation', {onSuccess: priceSuccess});
				if (external_object.form_options.dialog_confirmation === '1') {
					syncValues('#depart', '#depart2');
					syncValues('#retour', '#retour2');
				}

				const departDateTime = new dateTime($('#depart').val(), 'YYYY-MM-DD HH:mm');
				const retourDateTime = new dateTime($('#retour').val(), 'YYYY-MM-DD HH:mm');
				if (retourDateTime.isBefore(departDateTime))
				{
					console.log('return is before departure');
					// const lp = retourPick.PluginManager.getInstance('LockPlugin');
					// lp.options.minDate = departDateTime.toJSDate();
					$('#return-date').attr('data-status', '');
					retourCustom.setValue(departDateTime.format('HH:mm'))
				}
				if ($('#return-date').attr('data-status') !== 'updated') {
					retourPick.show();
				}

			}
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

	const locale = external_object.locale;
	if (['fr_FR', 'de_DE', 'nl_NL'].includes(locale)) {
		const localeCode = locale.split('_')[0]; // Extract 'fr', 'de', 'nl'
		loadScript(`https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/localization/messages_${localeCode}.min.js`);
	}

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
			// if (external_object.form_options.dialog_confirmation === '1') {
			// 	easepickCreate(
			// 		$('#depart2').get(0),
			// 		$('#retour2').get(0),
			// 		1,
			// 		function () {
			// 			syncValues('#depart2', '#depart');
			// 			syncValues('#retour2', '#retour');
			// 			getPrice('reservation', {onSuccess: priceSuccess});
			// 		}
			// 	);
			// 	dialogConfirm.dialog("open");
			// } else {
				$('#spinner-container').css('display', 'flex');
				form.submit();
			// }
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


