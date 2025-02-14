<?php

namespace ParkingManagement;

class Template
{
	public static function get_default($prop = 'info')
	{
		match ($prop) {
			'info' => $template = self::info(),
			'database' => $template = self::database(),
			'payment' => $template = self::payment(),
			'form' => $template = self::form(),
			'booking' => $template = self::booking(),
			'booked_dates' => $template = self::booked_dates(),
			'high_season' => $template = self::high_season(),
			'notification' => $template = self::notification(),
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
			'vehicle_type' => [
				'car' => 0,
				'truck' => 0,
				'motorcycle' => 0,
			],
			'type' => [
				'ext' => 0,
				'int' => 0
			],
			'logs' => [
				'database' => '0',
				'file' => '1',
				'retention' => '30',
			]
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

	private static function payment(): array
	{
		return [
			'valid-booking-on-payment' => '0',
			'providers' => [
				'payplug' => [
					'name' => 'payplug',
					'enabled' => "0",
					'active-test' => '0',
					'properties' => [
						'secret_key' => [
							'title' => 'Secret Key',
							'type' => 'password',
							'value' => ''
						],
						'public_key' => [
							'title' => 'Public Key',
							'type' => 'text',
							'value' => ''
						],
						'secret_key_test' => [
							'title' => 'Secret Test Key',
							'type' => 'password',
							'value' => ''
						],
						'public_key_test' => [
							'title' => 'Public Test Key',
							'type' => 'text',
							'value' => ''
						],
						'success_page' => [
							'title' => 'Success Page',
							'type' => 'page',
							'value' => ''
						],
						'cancel_page' => [
							'title' => 'Cancel Page',
							'type' => 'page',
							'value' => ''
						],
						'notification_url' => [
							'title' => 'Notification URL',
							'type' => 'url',
							'value' => ''
						],
					]
				],
				'mypos' => [
					'name' => 'mypos',
					'enabled' => "0",
					'active-test' => '0',
					'properties' => [
						'configuration_package' => [
							'title' => 'Configuration Package',
							'type' => 'password',
							'value' => ''
						],
						'success_page' => [
							'title' => 'Success Page',
							'type' => 'page',
							'value' => ''
						],
						'cancel_page' => [
							'title' => 'Cancel Page',
							'type' => 'page',
							'value' => ''
						],
						'notification_url' => [
							'title' => 'Notification URL',
							'type' => 'url',
							'value' => ''
						],
					]
				],
				'paypal' => [
					'name' => 'paypal',
					'enabled' => "0",
					'active-test' => '0',
					'properties' => [
						'email' => [
							'title' => 'Email',
							'type' => 'email',
							'value' => ''
						],
						'login' => [
							'title' => 'Login',
							'type' => 'text',
							'value' => ''
						],
						'password' => [
							'title' => 'Password',
							'type' => 'password',
							'value' => ''
						],
						'signature' => [
							'title' => 'Signature',
							'type' => 'password',
							'value' => ''
						],
						'notification_url' => [
							'title' => 'Notification URL',
							'type' => 'url',
							'value' => ''
						],
					]
				],
			]
		];
	}

	private static function form(): array
	{
		return array(
			'indicative' => '',
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
		);
	}

	private static function booking(): array
	{
		return array(
			'options' => array(
				'terms_and_conditions' => '0',
				'dialog_confirmation' => '0',
			),
			'database' => [
				'name' => "",
				'host' => "",
				'port' => "",
				'user' => "",
				'password' => ""
			],
			'payment' => [
				'valid-on-payment' => '0',
				'redirect-to-provider' => '0',
				'name' => 'payplug',
				'enabled' => "0",
				'active-test' => '0',
				'properties' => [],
			],
			'validation_page' => [
				'title' => 'Validation Page',
				'value' => ''
			],
			'booking_page' => [
				'title' => 'Booking Page',
				'value' => ''
			],
		);
	}
	private static function valet(): array
	{
		return array(
			'options' => array(
				'terms_and_conditions' => '0',
				'dialog_confirmation' => '0',
			),
			'database' => [
				'name' => "",
				'host' => "",
				'port' => "",
				'user' => "",
				'password' => ""
			],
			'payment' => [
				'valid-on-payment' => '0',
				'redirect-to-provider' => '0',
				'name' => 'payplug',
				'enabled' => "0",
				'active-test' => '0',
				'properties' => [],
			],
			'validation_page' => [
				'title' => 'Validation Page',
				'value' => ''
			],
			'booking_page' => [
				'title' => 'Booking Page',
				'value' => ''
			],

		);
	}

	private static function booked_dates(): array
	{
		return array();
	}

	private static function high_season(): array
	{
		return array(
			'price' => 0,
			'dates' => array()
		);
	}

	private static function notification(): array
	{
		return [
			'mail' => [
				'host' => [
					'title' => 'Host',
					'type' => 'text',
					'value' => ''
				],
				'login' => [
					'title' => 'Login',
					'type' => 'text',
					'value' => ''
				],
				'password' => [
					'title' => 'Password',
					'type' => 'password',
					'value' => ''
				],
				'sender' => [
					'title' => 'Sender',
					'type' => 'email',
					'value' => ''
				],
				'templates' => [
					'confirmation' => [
						'title' => 'Confirmation',
						'type' => 'textarea',
						'value' => ''
					],
					'cancellation' => [
						'title' => 'Cancellation',
						'type' => 'textarea',
						'value' => ''
					],
				]
			],
			'valet' => [
				'host' => [
					'title' => 'Host',
					'type' => 'text',
					'value' => ''
				],
				'login' => [
					'title' => 'Login',
					'type' => 'text',
					'value' => ''
				],
				'password' => [
					'title' => 'Password',
					'type' => 'password',
					'value' => ''
				],
				'sender' => [
					'title' => 'Sender',
					'type' => 'email',
					'value' => ''
				],
				'templates' => [
					'confirmation' => [
						'title' => 'Confirmation',
						'type' => 'textarea',
						'value' => ''
					],
					'cancellation' => [
						'title' => 'Cancellation',
						'type' => 'textarea',
						'value' => ''
					],
				]
			],
			'sms' => [
				'type' => 'AWS',
				'user' => '',
				'password' => '',
				'sender' => '',
				'template' => ''
			]
		];
	}

}

