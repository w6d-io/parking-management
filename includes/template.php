<?php

namespace ParkingManagement;

class Template
{
	public static function get_default($prop = 'info')
	{
		match ($prop) {
			'info' => $template = self::info(),
			'form' => $template = self::form(),
			'booked_dates' => $template = self::booked_dates(),
			'high_season' => $template = self::high_season(),
			'notification' => $template = self::notification(),
			'booking', 'valet' => $template = self::booking(),
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
				'int' => 0,
				'valet' => 0,
			],
			'logs' => [
				'database' => '0',
				'file' => '1',
				'retention' => '30',
			]
		);
	}

	public static function payment_properties(): array
	{
		return [
			'payplug' => [
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
			],
			'mypos' => [
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
			],
			'stripe' => [
				'secret_key' => [
					'title' => 'Secret Key',
					'type' => 'password',
					'value' => ''
				],
				'webhook_secret_key' => [
					'title' => 'Webhook Secret Key',
					'type' => 'password',
					'value' => ''
				],
				'secret_key_test' => [
					'title' => 'Secret Test Key',
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
			],
		];
	}

	private static function form(): array
	{
		return array(
			'indicative' => '',
			'booking_page' => [
				'title' => 'Booking Page',
				'value' => ''
			],
			'valet_page' => [
				'title' => 'Valet Page',
				'value' => ''
			],
			'options' => array(
				'night_extra_charge' => array(
					'enabled' => "0",
					'checked' => "0",
					'title' => 'Night extra charge',
					'price' => 0
				),
				'shuttle' => array(
					'enabled' => "0",
					'checked' => "0",
					'title' => 'Shuttle',
					'price' => 0
				),
				'keep_keys' => array(
					'enabled' => "0",
					'checked' => "0",
					'title' => 'Keep keys',
					'price' => 0,
					'label' => esc_html__('I will keep my keys for %s €', 'parking-management')
				),
				'ev_charging' => array(
					'enabled' => "0",
					'checked' => "0",
					'title' => 'EV Charging',
					'price' => 0,
					'label' => esc_html__('Electric Vehicle charging for %s €', 'parking-management')
				),
				'late' => array(
					'enabled' => "0",
					'checked' => "0",
					'title' => 'Late',
					'price' => 0
				),
				'holiday' => array(
					'enabled' => "0",
					'checked' => "0",
					'title' => 'Holiday',
					'price' => 0
				),
				'forgetting' => array(
					'enabled' => "0",
					'checked' => "0",
					'title' => 'Forgetting',
					'price' => 0
				),
				'extra_baggage' => [
					'enabled' => "0",
					'checked' => "0",
					'title' => 'Extra baggage',
					'price' => 0
				],
				'oversize_baggage' => [
					'enabled' => "0",
					'checked' => "0",
					'title' => 'Oversize baggage',
					'price' => 0
				],
				'cancellation_insurance' => [
					'enabled' => "0",
					'checked' => "0",
					'title' => 'Cancellation insurance',
					'price' => 0,
					'label' => esc_html__('I hereby subscribe to the cancellation insurance for %s €', 'parking-management')
				],
			),
		);
	}

	private static function booking(): array
	{
		return array(
			'validation_page' => [
				'title' => 'Validation Page',
				'value' => ''
			],
			'options' => [
				'terms_and_conditions' => '0',
				'dialog_confirmation' => '0',
			],
			'database' => [
				'name' => "",
				'host' => "",
				'port' => "",
				'user' => "",
				'password' => ""
			],
			'payment' => [
				'enabled' => "0",
				'valid-on-payment' => '0',
				'redirect-to-provider' => '0',
				'name' => 'payplug',
				'active-test' => '0',
				'properties' => self::payment_properties(),
			],
			'mail_templates' => [
				'confirmation' => [
					'title' => 'Confirmation',
					'type' => 'textarea',
					'value' => ''
				],
				'cancellation' => [
					'title' => 'Cancellation',
					'type' => 'textarea',
					'value' => ''
				]
			],
			'sms_template' => '',
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

	private static function mail_properties(): array
	{
		return [
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
			]
		];
	}

	private static function notification(): array
	{
		return [
			'mail' => self::mail_properties(),
			'valet' => self::mail_properties(),
			'sms' => [
				'type' => 'AWS',
				'user' => '',
				'password' => '',
				'sender' => '',
			]
		];
	}

}

