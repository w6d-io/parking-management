<?php

namespace Payment;

use Booking\Member;
use Booking\Order;
use Booking\OrderStatus;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use ParkingManagement\API\StripeAPI;
use ParkingManagement\interfaces\IPayment;
use ParkingManagement\Logger;
use ParkingManagement\PaymentID;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Page;
use Stripe\StripeClient;

require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "payment" . DS . "includes" . DS . "stripe" . DS . "page.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "payment" . DS . "includes" . DS . "stripe" . DS . "api.php";

class Stripe implements IPayment
{
	private int $order_id;
	private array $config;
	private array $properties;
	private string $kind;
	private string $payment_url;
	private float $amount;


	public function __construct(array $config, string $kind, int $order_id)
	{
		$this->order_id = $order_id;
		$this->config = $config;
		$this->properties = $config['properties']['stripe'];
		$this->kind = $kind;
		$this->payment_url = $this->initPayment();
	}

	public function pay(): string
	{
		if ($this->payment_url === '')
			return '';
		return Page::form($this->amount, $this->payment_url);
	}

	#[NoReturn] public function redirect(): void
	{
		wp_redirect($this->payment_url);
		exit(0);
	}

	public static function updatePaymentStatus($config, $kind, $order_id): bool
	{
		try {
			$properties = $config['properties']['stripe'];
			$session_id = $_GET['session_id'];
			$test_enabled = $config['active-test'] === '1';
			$secretKey = $test_enabled ? $properties['secret_key_test']['value'] : $properties['secret_key']['value'];
			$stripe = new StripeClient($secretKey);
			$session = $stripe->checkout->sessions->retrieve($session_id);
			$amount = $session->amount_total / 100;
			$payment_id = PaymentID::STRIPE;
			$status = OrderStatus::PAID;
			if ($session->payment_status != 'paid') {
				$payment_id = PaymentID::UNKNOWN;
				$amount = 0;
				$status = OrderStatus::PENDING;
			}
			Logger::debug('stripe.updatePaymentStatus', [
				'session' => $session,
				'amount' => $amount,
				'payment_id' => $payment_id,
				'status' => $status,
			]);
			$order = new Order($kind);
			$order->update_payment($order_id, date('Y-m-d H:i:s'), $amount, $payment_id, $status);
			Logger::info("stripe.updatePaymentStatus", "update payment status recorded");
			return true;
		} catch (Exception|ApiErrorException $e) {
			$message = [
				'payload' => $payload ?? 'n/c',
				'exception' => $e->getMessage()
			];
			if ($e instanceof ApiErrorException) {
				$error = $e->getError(); // Using getError() instead of getStripeError()
				$message["request_id"] = $e->getRequestId();
				$message["http_status"] = $e->getHttpStatus();
				$message["stripe_code"] = $e->getStripeCode();
				if ($error) {
					$message["Error Type"] = $error->type;
					$message["Error Code"] = $error->code;
				}
			}
			Logger::error("stripe.initPayment", $message);
		}
		return false;
	}

	private function initPayment(): string
	{
		$data = [];
		try {
			$member = new Member($this->kind);
			$order = new Order($this->kind);
			$data['post'] = $_POST;
			$data['order'] = $order->read($this->order_id);
			$data['member'] = $member->read($data['order']['membre_id']);
			$this->amount = $data['order']['total'];
			$test_enabled = $this->config['active-test'] === '1';
			$secretKey = $test_enabled ? $this->properties['secret_key_test']['value'] : $this->properties['secret_key']['value'];
			$customer = $this->getOrCreateClient($secretKey, $data);
			Logger::debug("stripe.initPayment", ['customer' => $customer]);
			$success_url = $this->properties['success_page']['value'] . "?from=provider&kind={$this->kind}&order_id={$this->order_id}" . '&session_id={CHECKOUT_SESSION_ID}';
			$cancel_url = $this->properties['cancel_page']['value'] . "?kind={$this->kind}&order_id={$this->order_id}";
			$stripe = new StripeClient($secretKey);
			$checkout_session = $stripe->checkout->sessions->create([
				'customer' => $customer->id,
				'customer_update' => ['address' => 'auto'],
				'line_items' => [[
					'price_data' => [
						'currency' => 'eur',
						'tax_behavior' => 'inclusive',
						'product_data' => [
							'name' => "Reservation du {$data['order']['depart']} au {$data['order']['arrivee']}",
							'tax_code' => 'txcd_20030000',
						],
						'unit_amount' => $data['order']['total'] * 100,
					],
					'quantity' => 1,
				]],
				'mode' => 'payment',
				'success_url' => $success_url,
				'cancel_url' => $cancel_url,
				'automatic_tax' => ['enabled' => true],
				'metadata' => [
					'kind' => $this->kind,
					'id_commande' => $this->order_id,
				]
			]);
			return $checkout_session->url;
		} catch (Exception|ApiErrorException $e) {
			$message = [
				'data' => $data,
				'payload' => $payload ?? 'n/c',
				'exception' => $e->getMessage()
			];
			if ($e instanceof ApiErrorException) {
				$error = $e->getError(); // Using getError() instead of getStripeError()
				$message["http_status"] = $e->getHttpStatus();
				$message["request_id"] = $e->getRequestId();
				$message["stripe_code"] = $e->getStripeCode();

				if ($error) {
					$message["Error Type"] = $error->type;
					$message["Error Code"] = $error->code;
				}
			}
			Logger::error("stripe.initPayment", $message);
			return '';
		}

	}

	/**
	 * @throws ApiErrorException
	 */
	private function getOrCreateClient($secret_key, $data): Customer
	{
		$stripe = new StripeClient($secret_key);
		$customers = $stripe->customers->all([
			'email' => $data['member']['email'],
			'limit' => 1
		]);
		if (!empty($customers->data)) {
			return $customers->data[0];
		} else {
			$customerData = [
				'email' => $data['member']['email'],
				'name' => $data['member']['nom'] . " " . $data['member']['prenom'],
				'address' => [
					'country' => 'FR',
					'postal_code' => $data['member']['code_postal'],
				]
			];
			return $stripe->customers->create($customerData);
		}
	}

}

new StripeAPI();
