
var paymentParams = {
	sid: '000000000000010',
	ipcLanguage: 'fr',
	walletNumber: '61938166610',
	amount: external_object.amount,
	currency: 'EUR',
	orderID: external_object.order_id,
	urlNotify: external_object.notify_url,
	urlOk: external_object.success_url,
	urlCancel: external_object.cancel_url,
	keyIndex: 1,
	cartItems: [
		{
			article: external_object.article,
			quantity: 1,
			price: external_object.amount,
			currency: 'EUR',
		},
	]
};

var callbackParams = {
	isSandbox: external_object.test_enabled,

	onMessageReceived: function (messages) {
		//Error and info messages.
		console.log(messages)
	},

	onSuccess: function (data) {
		console.log('success callback');
		console.log(data);
	},

	onError: function () {
		console.error('error');
	}
};

MyPOSEmbedded.createPaymentForm(
	'embeddedCheckout',
	paymentParams,
	callbackParams
).then(function (payment) {
	var button = document.getElementById('btn-pay');
	button.disabled = false;
	button.addEventListener("click", function () {
		payment.processPayment();
	}, false);
});
