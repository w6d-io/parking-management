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
			'full_date' => $template = self::full_date(),
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
		);
	}

	private static function payment(): array
	{
		return array(
			'paypal' => array(
				'enabled' => false,
				'properties' => array()
			),
			'payplug' => array(
				'enabled' => false,
				'properties' => array()
			),
			'mypos' => array(
				'enabled' => false,
				'properties' => array()
			)
		);
	}

	private static function form(): array
	{
		return array(
			'booking' => array(
				'terms_and_conditions' => 0,
				'valid_on_payment' => 0
			)
		);
	}

	private static function full_date(): array
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

