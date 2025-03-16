function getPrice(formId, config = {}) {
	const defaults = {
		onSuccess: null,
		onError: function (e, f, g) {
			console.error("get price failed", f);
		}
	};
	const settings = {...defaults, ...config};
	if (
		$('#depart').val().length === 0
		|| $('#retour').val().length === 0
	)
		return;
	$.ajax({
		type: 'GET',
		url: '/wp-json/pkmgmt/v1/prices',
		data: $(`#${formId}`).serialize(),
		processData: true,
		dataType: 'json',
		async: false,
		error: settings.onError,
		success: settings.onSuccess
	});
}

function getBookedDates() {
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
	return bookedDates;
}
function getHighSeason() {
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
	return highSeason;
}
function loadTranslation(lang) {
	const translations = {};

	// Return a promise to handle the asynchronous nature of $.getJSON
	return new Promise((resolve, reject) => {
		$.getJSON(external_object.i18n_path + '/' + `${lang}.json`)
			.done(function(data) {
				translations[lang] = data;

				// Resolve with the translator object
				resolve({
					translate: (key) => {
						// Fixed: Return the translation or fallback to key
						return translations[lang]?.[key] || key;
					}
				});
			})
			.fail(function(error) {
				console.error(`Failed to load ${lang}.json`, error);
				reject(error);
			});
	});
}

function loadScript(url) {
	const script = document.createElement("script");
	script.type = "text/javascript";
	script.src = url;
	document.getElementsByTagName("head")[0].appendChild(script);
}

function updateCombinedDateTime(options) {
	const defaults = {
		dateField: '',
		timeField: '',
		targetField: '',
		separator: ' '
	};

	const settings = {...defaults, ...options};
	if (!settings.dateField || !settings.timeField || !settings.targetField) {
		console.error("All field selectors are required");
		return;
	}
	const dateVal = $(settings.dateField).val();
	const timeVal = $(settings.timeField).val();
	console.log('date', dateVal,'time', timeVal);
	if (dateVal && timeVal) {
		const combinedValue = dateVal + settings.separator + timeVal;
		$(settings.targetField).val(combinedValue);
	}
}

/**
 * Custom Dropdown Factory
 * Creates a height-limited dropdown with specified options
 *
 * @param {string} containerId - ID of the container element
 * @param {string} inputId - ID of the input element
 * @param {string} config.placeholder - Text to display when nothing is selected
 * @param {boolean} config.scrollToActive - Boolean for autoscroll activation
 * @param {Function} config.onChange - Callback function when selection changes
 */
function initCustomDropdown(containerId, inputId, config = {}) {
	// Default configuration
	const defaults = {
		scrollToActive: true,
		onChange: null,
	};

	// Merge defaults with provided config
	const settings = {...defaults, ...config};

	function scrollToActiveItem() {
		const $dropdown = $(`#${containerId} > div > ul`);
		const $activeItem = $(`#${containerId} .dropdown-item.active`);

		if ($dropdown.length && $activeItem.length && settings.scrollToActive) {
			const dropdownHeight = $dropdown.height();
			const itemTop = $activeItem.position().top;
			const itemHeight = $activeItem.outerHeight();
			if ( itemTop < 100)
				return;

			const scrollPosition = itemTop - (dropdownHeight / 2) + (itemHeight / 2);
			$dropdown.scrollTop(scrollPosition);
		}
	}

	$(`#${containerId}`).on('shown.bs.dropdown', function() {
		scrollToActiveItem();
	});

	const initialValue = $(`#${inputId}`).val();
	if (initialValue) {
		const $item = $(`#${containerId} .dropdown-item[data-value="${initialValue}"]`);
		if ($item.length) {
			$item.addClass('active');
		}
	}
	// Attach event handlers
	$(`#${containerId} .dropdown-item`).on('click', function (e) {
		e.preventDefault();

		const selectedValue = $(this).data('value');
		const selectedText = $(this).text();

		// Update displayed text
		$(`#${inputId + '-span'}`).text(selectedText);

		// Update hidden input value
		$(`#${inputId}`).val(selectedValue);

		// Add 'active' class to selected item and remove from others
		$(`#${containerId} .dropdown-item`).removeClass('active');
		$(this).addClass('active');
		$(`#${containerId} > ul`).removeClass('show');

		// Call onChange callback if provided
		if (typeof settings.onChange === 'function') {
			settings.onChange(selectedValue, selectedText);
		}
	});

	// Return methods to interact with this dropdown
	return {
		getValue: () => $(`#${inputId}`).val(),
		setValue: (value) => {
			const item = $(`#${containerId} .dropdown-item[data-value="${value}"]`);
			if (item.length) {
				item.click();
			}
		},
		getText: () => $(`#${inputId + '-span'}`).text(),
		reset: () => {
			// $(`#${inputId+'-span'}`).text(settings.placeholder);
			// $(`#${inputId}`).val('');
			$(`#${containerId} .dropdown-item`).removeClass('active');
		},
		scrollToActive: scrollToActiveItem,
	};
}

function generateTimeOptions() {
	const options = [];
	for (let h = 0; h < 24; h++) {
		for (let m = 0; m < 60; m += 15) {
			const hour = h.toString().padStart(2, '0');
			const minute = m.toString().padStart(2, '0');
			const timeStr = `${hour}:${minute}`;
			options.push({value: timeStr, label: timeStr});
		}
	}
	return options;
}
