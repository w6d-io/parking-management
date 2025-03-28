<?php

use ParkingManagement\Booking;
use ParkingManagement\Booked;
use ParkingManagement\HighSeason;
use ParkingManagement\Notification;
use ParkingManagement\Payment;
use ParkingManagement\Price;
use ParkingManagement\interfaces\IShortcode;

$current_shortcode_page = "";
function pkmgmt_parking_management_shortcode_func(array $atts, $content = null, $code = ''): string
{
	global $wp, $current_shortcode_page;
	if (is_feed()) {
		return '[parking-management]';
	}
	if ('parking-management' !== $code) {
		return esc_html__('Error: not a Parking Management shortcode', 'parking-management');
	}

	$atts = shortcode_atts(
		array(
			'type' => 'form', // type supported form, valet, home-form, payment, price, booked
			'payment_provider' => '', // payment supported payplug, mypos, mypos-payment
			'action' => '', // action supported confirmation, cancellation
			'kind' => '',
		), $atts, 'parking-management'
	);
	$current_shortcode_page = home_url(add_query_arg(array(), $wp->request));
	$type = trim(array_key_exists('type', $atts) ? $atts['type'] : '');
	$payment_provider = trim(array_key_exists('payment_provider', $atts) ? $atts['payment_provider'] : '');
	$action = trim(array_key_exists('action', $atts) ? $atts['action'] : '');
	$kind = trim(array_key_exists('kind', $atts) ? $atts['kind'] : '');

	return match ($type) {
		'form', 'valet',
		'home-form', 'price',
		'booked', 'high-season', 'payment',
		'notification' => pkmgmt_parking_management_shortcode_router($type, ['payment_provider' => $payment_provider, 'action' => $action, 'kind' => $kind]),
		default => '[parking-management "not found"]',
	};
}

function pkmgmt_parking_management_shortcode_router(string $type, $atts): string
{
	$pm = getParkingManagementInstance();
	if (!$pm)
		return '[parking-management "not found"]';

	/** @var IShortcode $instance */
	$instance = match ($type) {
		'form', 'home-form' => new Booking($pm),
		'price' => new Price($pm),
		'booked' => new Booked($pm),
		'high-season' => new HighSeason($pm),
		'payment' => new Payment($pm),
		'notification' => new Notification($pm),
	};

	$kind = array_key_exists('kind', $atts) ? $atts['kind'] : 'booking';
	if ($type === 'payment') {
		$type = $atts['payment_provider'];
	}

	if ($type === 'notification') {
		$type = $atts['action'];
	}

	$instance->setKind($kind);

	return $instance->shortcode($type);
}
