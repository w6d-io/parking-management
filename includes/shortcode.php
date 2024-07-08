<?php

use ParkingManagement\Booking;
use ParkingManagement\Booked;
use ParkingManagement\Price;
use ParkingManagement\interfaces\IShortcode;

function pkmgmt_parking_management_shortcode_func(array $atts, $content = null, $code = ''): string
{
	if (is_feed()) {
		return '[parking-management]';
	}
	if ('parking-management' !== $code) {
		return esc_html(__('Error: not a Parking Management shortcode', 'parking-management'));
	}
	$atts = shortcode_atts(
		array(
			'type' => 'form', // type supported form, home-form, payment, price, booked
			'payment_provider' => '' // page supported payplug, mypos, mypos-payment
		), $atts, 'parking-management'
	);

	$type = trim(array_key_exists('type', $atts) ? $atts['type'] : '');
	$payment_provider = trim(array_key_exists('payment_provider', $atts) ? $atts['payment_provider'] : '');

	return match ($type) {
		'form', 'home-form', 'price', 'booked' => pkmgmt_parking_management_shortcode_router($type, $payment_provider),
		'payment' => sprintf('[parking-management type="payment" payment_provider="%s"]', $payment_provider),
		default => '[parking-management "not found"]',
	};
}

function pkmgmt_parking_management_shortcode_router(string $type, $payment_provider): string
{
	$pm = getParkingManagementInstance();
	if (!$pm)
		return '[parking-management "not found"]';

	/** @var IShortcode $instance */
	$instance =  match ($type) {
		'form', 'home-form' => new Booking($pm),
		'price' => new Price($pm),
		'booked' => new Booked($pm),
	};

	return $instance->shortcode($type);
}
