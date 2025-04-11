document.addEventListener('DOMContentLoaded', function() {
	console.info('external_object', external_object);
	const form = document.getElementById('stripe-form');
	if (form) {
		form.addEventListener('submit', function(event) {
			event.preventDefault();

			// Check if external_object and stripe_url exist
			if (typeof external_object !== 'undefined' && external_object.stripe_url) {
				console.info('Stripe URL redirection', external_object.stripe_url);
				window.location.href = external_object.stripe_url;
			} else {
				console.error('Stripe URL not found in external_object');
			}
		});
	}
});
