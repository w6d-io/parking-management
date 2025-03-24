<?php

namespace ParkingManagement\API;

use Booking\Order;
use ParkingManagement\Logger;
use ParkingManagement\PaymentID;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use WP_Error;
use WP_Http;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class StripeAPI extends API
{
	private const rest_base = '/stripe/webhook';

	public function __construct()
	{
		add_action('rest_api_init', [$this, 'register_routes']);
	}

	public function register_routes(): void
	{
		register_rest_route($this->namespace, self::rest_base, [
			[
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => [$this, 'create_item'],
				'permission_callback' => '__return_true',
				'args' => [
					'any_param' => ['required' => false]
				]
			]
		]);
	}

	/**
	 * Creates a single user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item($request): WP_Error|WP_REST_Response
	{
		$kind = $request->get_param('kind');
		if ($kind === null) {
			Logger::error("stripe.api.create_item", ["message" => "missing kind"]);
			return new WP_Error(
				'stripe-notification-error',
				'Missing parameter',
				array('status' => WP_Http::BAD_REQUEST)
			);
		}
		$pm = getParkingManagementInstance();
		if (!$pm)
			return new WP_Error(
				'get-parking-management-instance',
				__('failed to get parking management instance', 'parking-management'),
				array('status' => WP_Http::BAD_REQUEST)
			);
		$payment = $pm->prop($kind)['payment'];
		$provider = $payment['properties']['stripe'];
		$test_enabled = ($payment['active-test'] == '1');
		$secretKey = $test_enabled ? $provider['secret_key_test']['value'] : $provider['secret_key']['value'];
		$webhook_secret = $provider['webhook_secret_key']['value'];
		if (empty($secretKey)) {
			Logger::warning("stripe.api.create_item", ["message" => "missing secret key"]);
			return new WP_REST_Response(null, WP_Http::NO_CONTENT);
		}
		if (empty($webhook_secret)) {
			Logger::warning("stripe.api.create_item", ["message" => "missing webhook secret key"]);
			return new WP_REST_Response(null, WP_Http::NO_CONTENT);
		}
		try {
			Stripe::setApiKey($secretKey);
			$stripe_signature = $request->get_header('stripe_signature');
			$event = Webhook::constructEvent(
				$request->get_body(),
				$stripe_signature,
				$webhook_secret
			);
			Logger::warning('stripe.create_item', [
				'event' => $event,
				'kind' => $kind,
			]);
			if ( $event->type === 'checkout.session.completed')
			{
				$session = $event->data->object;

				$session = Session::constructFrom($session->toArray());
				$order_id = (int)$session->metadata['id_commande'];
				$order = new Order($kind);
				if ($session->payment_status == 'paid') {
					$payment_date = $date = date('Y-m-d H:i:s');
					$amount = $session->amount_total /100;
					$payment_id = PaymentID::STRIPE;
					$order->update_payment((int)$order_id, $payment_date, $amount, $payment_id);
					Logger::info("stripe.api.create_item", "record payment status recorded");
				}
			}
			return new WP_REST_Response(null, WP_Http::NO_CONTENT);
		} catch (\UnexpectedValueException|SignatureVerificationException $e) {
			Logger::warning("stripe.api.create_item", $e->getMessage());
			if ( $e->getMessage() == "The resource you requested could not be found.")
				new WP_REST_Response(null, WP_Http::NO_CONTENT);
			return new WP_Error(
				'stripe-notification-error',
				$e->getMessage(),
				array('status' => WP_Http::BAD_REQUEST)
			);
		}
	}
}
