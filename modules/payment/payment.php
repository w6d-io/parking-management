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

	private array $config;

	public function __construct(ParkingManagement $pm)
	{
		$this->pm = $pm;
		$this->config = $pm->prop('payment');
	}


	private function getInstanceProvider(): IPayment
	{
		Logger::debug('payment.getInstanceProvider', ["provider" => $this->provider]);
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
		$this->provider = $form_payment;

		if ($this->isEnabled())
			return $form_payment;
		return '';
	}
	public function setProviderBySource($source): void
	{
		$provider = $this->getEnabledProvider($source);
		if (empty($provider))
			return;
		$this->provider = $provider;
	}

	public function setProvider($provider): void
	{
		$this->provider = match ($provider) {
			'payplug', 'mypos', 'paypal' => $provider,
			default => ''
		};
	}
	public function shortcode(string $type): string
	{
		if (!array_key_exists('order_id', $_GET) || !is_numeric($_GET['order_id'])
			|| (array_key_exists('from', $_GET) && $_GET['from'] === 'provider')
		)
			return '';
		$this->setProvider($type);
		if (!$this->isEnabled())
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

	public function isEnabled(): bool
	{
		if (!empty($this->provider))
			return $this->config['providers'][$this->provider]['enabled'] == '1';
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
