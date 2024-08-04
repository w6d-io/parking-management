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

	private function getEnabledProvider(): array
	{
		$providers = array();
		$payment = $this->pm->prop('payment');
		foreach ($payment['providers'] as $name => $provider) {
			if ($provider['enabled'] === '1')
				$providers[] = $name;
		}
		return $providers;
	}

	private function getSupportedProvider(): array
	{
		$payment = $this->pm->prop('payment');
		return array_keys($payment['providers']);
	}

	public function __construct(ParkingManagement $pm)
	{
		$this->pm = $pm;
		$providers = $this->getEnabledProvider();
		if (empty($providers))
			return;
		$key = array_rand($providers);
		$this->provider = $providers[$key];
	}

	public function shortcode(string $type): string
	{

		if (!array_key_exists('order_id', $_GET) || !is_numeric($_GET['order_id'])
			|| (in_array($_GET['from'], $_GET) && $_GET['from'] === 'provider')
		)
			return '';
		if ($type !== '')
			$this->provider = $type;
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

	public static function isEnabled(): bool
	{
		$pm = getParkingManagementInstance();
		if (!$pm)
			return false;
		$payment = $pm->prop('payment');
		foreach ($payment['providers'] as $provider) {
			if ($provider['enabled'] === '1')
				return true;
		}
		return false;
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
