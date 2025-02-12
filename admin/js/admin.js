document.addEventListener('DOMContentLoaded', function () {
	window.addEventListener('beforeunload', function () {
		localStorage.setItem('scrollPosition', window.scrollY.toString());
	});
	window.addEventListener('keydown', function (event) {
		if ((event.ctrlKey && event.key === 's') || (event.metaKey && event.key === 's')) {
			event.preventDefault();
			document.getElementById('pkmgmt-save').click();
		}
	});

	const scrollPosition = localStorage.getItem('scrollPosition');
	if (scrollPosition) {
		window.scrollTo(0, parseInt(scrollPosition, 10));
	}


	// Password toggle
	$('.togglePassword').on('click', function () {
		const passwordInput = $(this).siblings('.password-input');
		const eyeOpen = $(this).find('.fa-eye');
		const eyeClosed = $(this).find('.fa-eye-slash');

		if (passwordInput.attr('type') === 'password') {
			passwordInput.attr('type', 'text');
			eyeOpen.hide();
			eyeClosed.show();
		} else {
			passwordInput.attr('type', 'password');
			eyeOpen.show();
			eyeClosed.hide();
		}
	});


	// Date picker
	function generateRandomId() {
		return Math.floor(Math.random() * 1000000);
	}

	function getToday() {
		const today = new Date();
		return today.toISOString().slice(0, 10);
	}

	$('#full-dates-add-element').on("click", function (event) {
		event.preventDefault();
		const id = generateRandomId();
		const newElement = `
            <div class="dates-element">
            	<label for="pkmgmt-booked-dates-start--${id}">start</label>
                <input type="date" id="pkmgmt-booked-dates-start--${id}" name="pkmgmt-booked_dates[${id}][start]" class="start-date" value="${getToday()}">
            	<label for="pkmgmt-booked-dates-end--${id}">end</label>
                <input type="date" id="pkmgmt-booked-dates-end--${id}" name="pkmgmt-booked_dates[${id}][end]" class="end-date" value="${getToday()}">
            	<label for="pkmgmt-booked-dates-message-${id}">message</label>
                <input type="text" id="pkmgmt-booked-dates-message-${id}" name="pkmgmt-booked_dates[${id}][message]" class="message" placeholder="Message">
                <i class="fas fa-trash delete"></i>
            </div>
        `;
		$('#booked_dates_body').append(newElement);

		initializeDateTimePickers();
	});
	$('#high-season-add-element').on("click", function (event) {
		event.preventDefault();
		const id = generateRandomId();
		const newElement = `
            <div class="dates-element">
            	<label for="pkmgmt-high-season-start-${id}">start</label>
                <input type="date" id="pkmgmt-high-season-dates-start-${id}" name="pkmgmt-high_season[dates][${id}][start]" class="start-date" value="${getToday()}">
            	<label for="pkmgmt-high-season-end-${id}">end</label>
                <input type="date" id="pkmgmt-high-season-dates-end-${id}" name="pkmgmt-high_season[dates][${id}][end]" class="end-date" value="${getToday()}">
            	<label for="pkmgmt-high-season-message-${id}">message</label>
                <input type="text" id="pkmgmt-high-season-dates-message-${id}" name="pkmgmt-high_season[dates][${id}][message]" class="message" placeholder="Message">
                <i class="fas fa-trash delete"></i>
            </div>
        `;
		$('#high_season_dates_body').append(newElement);

		initializeDateTimePickers();
	});

	$('.shortcode-copy').on('click', function (event) {
		event.preventDefault();
		const short_div = $(this).closest('.shortcode-div');
		const copyText = short_div.find('.shortcode').val();
		const copyMessage = short_div.find('.shortcode-copy-message');
		// Get the text from the input field

		// Use the Clipboard API to copy the text
		navigator.clipboard.writeText(copyText).then(function () {
			// Show the copy message
			// const copyMessage = $('#shortcodeCopyMessage');
			copyMessage.show();

			// Hide the message after 2 seconds
			setTimeout(function () {
				copyMessage.hide();
			}, 2000);
		}).catch(function (error) {
			console.error(error);
		});
	});

	function initializeDateTimePickers() {
		$('.start-date').each(function () {
			const endDateInput = $(this).closest('.dates-element').find('.end-date').get(0);
			new easepick.create({
				element: this,
				plugins: ['RangePlugin', 'LockPlugin'],
				css: ['https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.1/dist/index.css'],
				format: 'YYYY-MM-DD',
				zIndex: 50,
				RangePlugin: {
					elementEnd: endDateInput,
					minDate: new Date(),
				},
				LockPlugin: {
					minDate: new Date(),
				}
			});
		});

	}

	initializeDateTimePickers();
	$('.dates-body').on('click', '.delete', function () {
		$(this).parent('.dates-element').remove();
	});

	// tabs

	// payment
	$('.tab-links a').on('click', function (e) {
		e.preventDefault();
		const currentAttrValue = $(this).attr('href');

		// Show/Hide Tabs
		$('.tab').removeClass('active');
		$(currentAttrValue).addClass('active');

		// Change/remove current tab to active
		$('.tab-links li').removeClass('active');
		$(this).parent('li').addClass('active');
	});

	// mail template
	const mailButtons = $('#nav-mail-tab button');
	mailButtons.each(function() {
		var tabTrigger = new bootstrap.Tab(this);
		$(this).on('click', function(event) {
			event.preventDefault();
			tabTrigger.show();
		});
	});

	// valet template
	const valetButtons = $('#nav-valet-tab button');
	valetButtons.each(function() {
		var tabTrigger = new bootstrap.Tab(this);
		$(this).on('click', function(event) {
			event.preventDefault();
			tabTrigger.show();
		});
	});

	const notificationMailButtons = $('#nav-notification-tab button');
	notificationMailButtons.each(function() {
		var tabTrigger = new bootstrap.Tab(this);

		$(this).on('click', function(event) {
			event.preventDefault();
			tabTrigger.show();
		})
		;
	});

	bootstrap.Tab.getInstance(mailButtons[0]).show();
	bootstrap.Tab.getInstance(valetButtons[0]).show();
	bootstrap.Tab.getInstance(notificationMailButtons[0]).show();

	// Initialize the first tab
	$('.tab-links li:first-child a').trigger('click');

	$('#pkmgmt-admin-config').validate({
		rules: {
			'pkmgmt-info[type][ext]': {
				require_from_group: [1, '.info-type']
			},
			'pkmgmt-info[type][int]': {
				require_from_group: [1, '.info-type']
			},
			'pkmgmt-info[vehicle_type][car]': {
				require_from_group: [1, '.info-vehicle-type']
			},
			'pkmgmt-info[vehicle_type][truck]': {
				require_from_group: [1, '.info-vehicle-type']
			},
			'pkmgmt-info[vehicle_type][motorcycle]': {
				require_from_group: [1, '.info-vehicle-type']
			},
		}
	})

	// Page List dialog
	let currentInput;
	$('#dialog-pages-list').on('shown.bs.modal', function (e) {
		currentInput = $(e.relatedTarget).closest('.input-group').find('input');
		console.log("currentInput", currentInput);

	})
	$('li.page').on('click', function (e) {
		if (currentInput)
			currentInput.val(e.target.getAttribute('data-url'));
		$('.btn-close').trigger('click');
	})

});
