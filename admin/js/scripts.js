document.addEventListener('DOMContentLoaded', function () {
	// shortcode copy
	// $('#shortcodeCopy').on('click', function (event) {
	// 	event.preventDefault();
	// 	const copyText = $('#pkmgmt-anchor-text').val();
	// 	console.log("value", copyText);
	// 	// Get the text from the input field
	//
	// 	// Use the Clipboard API to copy the text
	// 	navigator.clipboard.writeText(copyText).then(function () {
	// 		// Show the copy message
	// 		const copyMessage = $('#shortcodeCopyMessage');
	// 		copyMessage.show();
	//
	// 		// Hide the message after 2 seconds
	// 		setTimeout(function () {
	// 			copyMessage.hide();
	// 		}, 2000);
	// 	}).catch(function (error) {
	// 		console.error('Could not copy text: ', error);
	// 	});
	// });

	document.addEventListener('keydown', function(event) {
		if ((event.ctrlKey && event.key === 's')||(event.metaKey && event.key === 's')) {
			event.preventDefault();
			document.getElementById('pkmgmt-save').click();
		}
	});

	document.addEventListener('DOMContentLoaded', function() {
		// Restaurer la position de défilement à partir de localStorage
		const scrollPosition = localStorage.getItem('scrollPosition');
		if (scrollPosition) {
			window.scrollTo(0, parseInt(scrollPosition, 10));
		}
	});

	window.addEventListener('beforeunload', function() {
		// Sauvegarder la position de défilement dans localStorage
		console.log('window.scrollY',window.scrollY);
		localStorage.setItem('scrollPosition', window.scrollY);
	});

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
	const DateTime = luxon.DateTime;

	function generateRandomId() {
		return Math.floor(Math.random() * 1000000);
	}

	function getToday() {
		const today = new Date();
		return today.toISOString().slice(0, 10);
	}

	$('#full-dates-add-element').on("click",function (event) {
		event.preventDefault();
		const id = generateRandomId();
		const newElement = `
            <div class="dates-element">
                <input type="date" name="pkmgmt-booked_dates[${id}][start]" class="start-date" value="${getToday()}">
                <input type="date" name="pkmgmt-booked_dates[${id}][end]" class="end-date" value="${getToday()}">
                <input type="text" name="pkmgmt-booked_dates[${id}][message]" class="message" placeholder="Message">
                <i class="fas fa-trash delete"></i>
            </div>
        `;
		$('#booked_dates_body').append(newElement);

		initializeDateTimePickers();
	});
	$('#high-season-add-element').on("click",function (event) {
		event.preventDefault();
		const id = generateRandomId();
		const newElement = `
            <div class="dates-element">
                <input type="date" name="pkmgmt-high_season[${id}][start]" class="start-date" value="${getToday()}">
                <input type="date" name="pkmgmt-high_season[${id}][end]" class="end-date" value="${getToday()}">
                <input type="text" name="pkmgmt-high_season[${id}][message]" class="message" placeholder="Message">
                <i class="fas fa-trash delete"></i>
            </div>
        `;
		$('#high_season_dates_body').append(newElement);

		initializeDateTimePickers();
	});

	$('.shortcode-copy').on('click', function (event) {
		event.preventDefault();
		const shortdiv = $(this).closest('.shortcode-div');
		const copyText = shortdiv.find('.shortcode').val();
		console.log("value", copyText);
		const copyMessage = shortdiv.find('.shortcode-copy-message');
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

	$('.tab-links a').on('click', function (e) {
		e.preventDefault();
		var currentAttrValue = $(this).attr('href');

		// Show/Hide Tabs
		$('.tab').removeClass('active');
		$(currentAttrValue).addClass('active');

		// Change/remove current tab to active
		$('.tab-links li').removeClass('active');
		$(this).parent('li').addClass('active');
	});

	// Initialize the first tab
	$('.tab-links li:first-child a').click();

});
