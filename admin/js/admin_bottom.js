jQuery(document).ready(function($) {
	// Initialize postboxes
	$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
	postboxes.add_postbox_toggles(config.screen_id);
});
