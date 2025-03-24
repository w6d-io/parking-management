<?php

namespace ParkingManagement;

use JetBrains\PhpStorm\NoReturn;
use ParkingManagement\interfaces\IParkingmanagement;
use ParkingManagement\interfaces\IPayment;
use ParkingManagement\interfaces\IShortcode;
use Payment\MyPos;
use Payment\Payplug;
use Payment\Stripe;

require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "payment" . DS . "payment_id.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "payment" . DS . "includes" . DS . "payplug.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "payment" . DS . "includes" . DS . "mypos.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "payment" . DS . "includes" . DS . "stripe.php";

class Payment implements IShortcode, IParkingManagement
{

	private ParkingManagement $pm;
	private string $provider = '';

	private array $config;

	private string $kind = 'booking';

	private int $order_id = 0;

	public function __construct(ParkingManagement $pm)
	{
		$this->pm = $pm;
	}


	private function getInstanceProvider(): IPayment
	{
		Logger::debug('payment.getInstanceProvider', ["provider" => $this->provider]);
		return match ($this->provider) {
			'mypos' => new MyPos($this->config, $this->kind, $this->order_id),
			'stripe' => new Stripe($this->config, $this->kind, $this->order_id),
			default => new Payplug($this->config, $this->kind, $this->order_id),
		};
	}

	public function shortcode(string $type): string
	{
		if (!array_key_exists('order_id', $_GET) || !is_numeric($_GET['order_id'])
			|| (array_key_exists('from', $_GET) && $_GET['from'] === 'provider')
		)
			return '';
		$this->order_id = $_GET['order_id'];
		if (!$this->isEnabled())
			return '';
		return match ($this->provider) {
			'payplug', 'mypos', 'stripe' => $this->run_provider(),
			default => sprintf('[parking-management type="payment" payment_provider="%s" kind="%s"]', $this->provider, $this->kind)
		};

	}
	public function run_provider(): string
	{
		$instance = $this->getInstanceProvider();
		return $instance->pay();
	}

	#[NoReturn] public function redirect(): void
	{
		$instance = $this->getInstanceProvider();
		$instance->redirect();
	}

	public function isEnabled(): bool
	{
		return $this->config['enabled'] == '1';
	}

	public function doRedirect(): bool
	{
		return $this->config['redirect-to-provider'] == '1';
	}

	public static function validateOnPayment($kind): bool
	{
		$pm = getParkingManagementInstance();
		if (!$pm)
			return false;
		return match ($kind) {
			'valet', 'booking' => ($pm->prop($kind)['payment']['valid-booking-on-payment'] === '1'),
			default => false
		};
	}

	public function setKind(string $kind): void {
		$this->kind = $kind;
		$this->config = $this->pm->prop($kind)['payment'];
		$this->provider = $this->config['name'];
	}

	public function setOrderId(int $order_id): void
	{
		$this->order_id = $order_id;
	}

	public function updatePaymentStatus(): bool
	{
		if ( ($this->provider !== 'stripe') || (empty($_GET['session_id']))) return false;
		return Stripe::updatePaymentStatus($this->config, $this->kind, $this->order_id);
	}
}
