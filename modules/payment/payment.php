<?php

namespace ParkingManagement;

use ParkingManagement\interfaces\IParkingmanagement;
use ParkingManagement\interfaces\IPayment;
use ParkingManagement\interfaces\IShortcode;
use Payment\MyPos;
use Payment\Payplug;

require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "payment" . DS . "payment_id.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "payment" . DS . "includes" . DS . "payplug.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "payment" . DS . "includes" . DS . "mypos.php";

class Payment implements IShortcode, IParkingManagement
{

	private ParkingManagement $pm;
	private string $provider = '';

	private function getInstanceProvider(): IPayment
	{
		return match ($this->provider) {
			'payplug' => new Payplug($this->pm),
			'mypos' => new MyPos($this->pm),
		};
	}

	private function getEnabledProvider($form = 'booking'): string
	{
		$form_payment = match ($form) {
			'valet' => $this->pm->prop('form')['valet']['payment'],
			default => $this->pm->prop('form')['payment'],
		};
		if (self::isEnabled($form_payment))
			return $form_payment;
		return '';
	}

	private function getSupportedProvider(): array
	{
		$payment = $this->pm->prop('payment');
		return array_keys($payment['providers']);
	}

	public function __construct(ParkingManagement $pm, $source = 'booking')
	{
		$this->pm = $pm;
		$provider = $this->getEnabledProvider($source);
		if (empty($provider))
			return;
		$this->provider = $provider;
	}

	public function shortcode(string $type): string
	{

		if (!array_key_exists('order_id', $_GET) || !is_numeric($_GET['order_id'])
			|| (array_key_exists('from', $_GET) && $_GET['from'] === 'provider')
		)
			return '';
//		if ($type === '')
//			return '';
//		$this->provider = $type;
		if (!in_array($this->provider, $this->getSupportedProvider()))
			return '';
		return match ($this->provider) {
			'payplug', 'mypos' => $this->run_provider(),
			default => sprintf('[parking-management type="payment" payment_provider="%s"]', $this->provider)
		};

	}

	public function run_provider(): string
	{
		$instance = $this->getInstanceProvider();
		return $instance->pay();
	}

	public function redirect(): void
	{
		$instance = $this->getInstanceProvider();
		$instance->redirect();
	}

	public static function isEnabled(string $name = ''): bool
	{
		$pm = getParkingManagementInstance();
		if (!$pm) {
			return false;
		}

		$providers = $pm->prop('payment')['providers'] ?? [];

		return array_reduce($providers, function($carry, $provider) use ($name) {
			if ($provider['enabled'] !== '1') {
				return $carry;
			}

			return $carry || $name === '' || $name === $provider['name'];
		}, false);
	}

	public static function validateOnPayment(): bool
	{
		$pm = getParkingManagementInstance();
		if (!$pm)
			return false;
		$payment = $pm->prop('payment');
		return $payment['valid-booking-on-payment'] === '1';
	}
}
