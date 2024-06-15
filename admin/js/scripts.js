document.addEventListener('DOMContentLoaded', function() {
	$('#shortcodeCopy').on('click', function(event) {
		event.preventDefault();
		const copyText = $('#pkmgmt-anchor-text').val();
		console.log("value",copyText);
		// Get the text from the input field

		// Use the Clipboard API to copy the text
		navigator.clipboard.writeText(copyText).then(function() {
			// Show the copy message
			const copyMessage = $('#shortcodeCopyMessage');
			copyMessage.show();

			// Hide the message after 2 seconds
			setTimeout(function() {
				copyMessage.hide();
			}, 2000);
		}).catch(function(error) {
			console.error('Could not copy text: ', error);
		});
	});
	$('.togglePassword').on('click', function() {
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
});
