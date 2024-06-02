<?php

function pkmgmt_parking_management_shortcode_func(array $atts): string
{
	if (is_feed()) {
		return '[parking-management]';
	}
	$atts = shortcode_atts(
		array(
			'id' => 0,
			'title' => '',
			'type' => 'form', // type supported form, home-form, payment
			'payment_provider' => '' // page supported payplug, mypos, mypos-payment
		), $atts, 'parking-management'
	);

	$id = trim( $atts['id'] );
	$title = trim( $atts['title'] );
	$type = trim( $atts['type'] );
	$payment_provider = trim( $atts['payment_provider'] );

	return match ($type) {
		'form' => '[parking-management "form"]',
		'home-form' => '[parking-management "home-form"]',
		'payment' => sprintf('[parking-management payment provider="%s"]', $payment_provider),
		default => '[parking-management "not found"]',
	};
}
