document.addEventListener('DOMContentLoaded', function() {
	[].forEach.call(document.querySelectorAll("form#payplug-form"), function(el) {
		el.addEventListener('submit', function(event) {
			Payplug.showPayment(external_object.payplug_url);
			event.preventDefault();
		})
	})
})
