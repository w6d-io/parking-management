<?php

namespace ParkingManagement;

class Template
{
	public static function get_default($prop = 'info')
	{
		match ($prop) {
			'info' => $template = self::info(),
			'database' => $template = self::database(),
			'api' => $template = self::api(),
			'payment' => $template = self::payment(),
			'form' => $template = self::form(),
			'booked_dates' => $template = self::booked_dates(),
			'high_season' => $template = self::high_season(),
			'sms' => $template = self::sms(),
			'response' => $template = self::response(),
			default => $template = null
		};
		return apply_filters('pkmgmt_default_template', $template, $prop);
	}

	private static function info(): array
	{
		return array(
			'address' => '',
			'mobile' => '',
			'RCS' => '',
			'email' => '',
			'terminal' => '',
			'vehicle_type' => array(
				'car' => 0,
				'truck' => 0,
				'motorcycle' => 0,
			),
			'type' => array(
				'ext' => 0,
				'int' => 0
			)
		);
	}

	private static function database(): array
	{
		return array(
			'name' => "",
			'host' => "",
			'port' => "",
			'user' => "",
			'password' => ""
		);
	}

	private static function api(): array
	{
		return array(
			'host' => "",
			'port' => "",
			'user' => "",
			'password' => "",
			'zip_codes_endpoint' => "",
			'models_vehicle_endpoint' => "",
			'destinations_endpoint' => "",
			'price_endpoint' => "",
		);
	}

	private static function payment(): array
	{
		return array(
			'paypal' => array(
				'name'=> 'paypal',
				'enabled' => "0",
				'properties' => array(
					'email' => '',
					'login' => '',
					'password' => '',
					'signature' => '',
					'notification_url' => '',
					'ipn' => ''
				)
			),
			'payplug' => array(
				'name'=> 'payplug',
				'enabled' => "0",
				'properties' => array(
					'secret_key' => '',
					'public_key' => '',
					'secret_key_test' => '',
					'public_key_test' => '',
					'notification_url' => '',
					'ipn' => ''
				)
			),
			'mypos' => array(
				'name'=> 'mypos',
				'enabled' => "0",
				'properties' => array(
					'configuration_package' => '',
					'notification_url' => '',
				)
			)
		);
	}

	private static function form(): array
	{
		return array(
			'booking' => array(
				'terms_and_conditions' => '0',
				'valid_on_payment' => '0',
				'dialog_confirmation' => '0',
			),
			'options' => array(
				'night_extra_charge' => array(
					'enabled' => "0",
					'title' => 'Night extra charge',
					'price' => 0
				),
				'shuttle' => array(
					'enabled' => "0",
					'title' => 'Shuttle',
					'price' => 0
				),
				'late' => array(
					'enabled' => "0",
					'title' => 'Late',
					'price' => 0
				),
				'holiday' => array(
					'enabled' => "0",
					'title' => 'Holiday',
					'price' => 0
				),
				'forgetting' => array(
					'enabled' => "0",
					'title' => 'Forgetting',
					'price' => 0
				),
				'cancellation_insurance' => array(
					'enabled' => "0",
					'title' => 'Cancellation insurance',
					'price' => 0
				),
			),
			'indicative' => '',
		);
	}

	private static function booked_dates(): array
	{
		return array();
	}

	private static function high_season(): array
	{
		return array();
	}

	private static function sms(): array
	{
		return array(
			'type' => 'AWS',
			'user' => '',
			'password' => '',
			'sender' => '',
			'template' => ''
		);
	}

	private static function response(): array
	{
		return array();
	}
}

