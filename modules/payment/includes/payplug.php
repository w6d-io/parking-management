<?php

namespace Payment;

use Booking\Member;
use Booking\Order;
use Exception;
use ParkingManagement\API\PayplugAPI;
use ParkingManagement\interfaces\IPayment;
use ParkingManagement\Logger;
use ParkingManagement\ParkingManagement;
use Payplug\Page;
use Payplug\Payment;

require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "payment" . DS . "includes" . DS . "payplug" . DS . "page.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "payment" . DS . "includes" . DS . "payplug" . DS . "api.php";

class Payplug implements IPayment
{

	private array $config;
	private int $order_id;
	private string $payment_url;
	private float $amount;

	public function __construct(ParkingManagement $pm)
	{

		$payment = $pm->prop('payment');
		$this->config = $payment['providers']['payplug'];
		$this->order_id = $_GET['order_id'];
		$this->payment_url = $this->initPayment();
	}

	/**
	 * @throws Exception
	 */
	public function pay(): string
	{
		if ($this->payment_url === '')
			return '';
		$this->redirect();
		return Page::form($this->amount, $this->payment_url);
	}

	public function redirect(): void
	{
		if ($this->config['redirect-to-provider'] == '1' && $this->payment_url != '') {
			wp_redirect($this->payment_url);
			exit(0);
		}
	}

	private function initPayment(): string
	{
		$data = array();
		try {
			$provider = $this->config;
			$order = new Order();
			$data['order'] = $order->read($this->order_id);
			$member = new Member();
			$data['member'] = $member->read($data['order']['membre_id']);
			$data['post'] = $_POST;
			$this->amount = $data['order']['total'];
			$test_enabled = $provider['active-test'] === '1';
			$secretKey = $test_enabled ? $provider['properties']['secret_key_test']['value'] : $provider['properties']['secret_key']['value'];
			\Payplug\Payplug::init(array(
				'secretKey' => $secretKey
			));
			$success_url = $provider['properties']['success_page']['value'] . "?from=provider&order_id=" . $this->order_id;
			$cancel_url = $provider['properties']['cancel_page']['value'] . '?order_id=' . $this->order_id;
			$notify_url = home_url() . "/wp-json/pkmgmt/v1/payplug/ipn";
			if ($provider['properties']['notification_url']['value'] !== '')
				$notify_url = $provider['properties']['notification_url']['value'];
			$payload = array(
				'title' => 'n/c',
				'first_name' => $data['post']['prenom'] ?? $data['member']['prenom'],
				'last_name' => $data['post']['nom'] ?? $data['member']['nom'],
				'email' => $data['post']['email'] ?? $data['member']['email'],
				'address1' => 'n/c',
				'postcode' => $data['post']['code_postal'] ?? (!empty($data['member']['code_postal']) ? $data['member']['code_postal']: 'n/c'),
				'city' => (!empty($data['member']['ville']) ? $data['member']['ville']: 'n/c'),
				'country' => 'FR',
				'language' => "fr"
			);
			$payment = Payment::create(array(
				'amount' => ($data['order']['total'] * 100),
				'currency' => 'EUR',
				'billing' => $payload,
				'shipping' => array_merge($payload, ['delivery_type' => 'BILLING']),
				'hosted_payment' => array(
					'return_url' => $success_url,
					'cancel_url' => $cancel_url
				),
				'notification_url' => $notify_url,
				'metadata' => array(
					'id_commande' => $this->order_id
				)
			));

			return $payment->hosted_payment->payment_url;
		} catch (Exception $e) {
			Logger::error("payplug.initPayment", ['data' => $data, 'payload' => $payload ?? 'n/c', 'exception' => $e->getMessage()]);
			return '';
		}
	}
}

new PayplugAPI();
