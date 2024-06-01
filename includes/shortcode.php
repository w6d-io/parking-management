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
			'scope' => 'form' // scope available form, home-form, payplug, mypos, mypos-payment
		), $atts, 'parking-management'
	);

	$id = trim( $atts['id'] );
	$title = trim( $atts['title'] );
	$scope = trim( $atts['scope'] );

	return match ($scope) {
		'form' => '[parking-management "form"]',
		'home-form' => '[parking-management "home-form"]',
		'payplug' => '[parking-management "payplug"]',
		'mypos' => '[parking-management "mypos"]',
		'mypos-payment' => '[parking-management "mypos-payment"]',
		default => '[parking-management "not found"]',
	};
}
