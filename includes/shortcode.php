<?php

function pkmgmt_parking_management_shortcode_func(array $atts): string
{
	if (is_feed()) {
		return '[parking-management]';
	}
	$atts = shortcode_atts(
		array(
			'type' => 'form', // type supported form, home-form, payment, price, booked
			'payment_provider' => '' // page supported payplug, mypos, mypos-payment
		), $atts, 'parking-management'
	);

	$type = trim( array_key_exists('type', $atts) ? $atts['type']: '' );
	$payment_provider = trim( array_key_exists('payment_provider', $atts) ? $atts['payment_provider']: '' );

	return match ($type) {
		'form' => '[parking-management type="form"]',
		'home-form' => '[parking-management type="home-form"]',
		'price' => '[parking-management type="price"]',
		'booked' => '[parking-management type="booked"]',
		'payment' => sprintf('[parking-management type="payment" payment_provider="%s"]', $payment_provider),
		default => '[parking-management "not found"]',
	};
}
