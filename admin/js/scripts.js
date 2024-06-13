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
});
