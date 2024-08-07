<?php

namespace ParkingManagement\API;

use Booking\Order;
use Exception;
use ParkingManagement\Logger;
use ParkingManagement\PaymentID;
use Payplug\Exception\PayplugException;
use Payplug\Notification;
use Payplug\Payplug;
use Payplug\Resource\APIResource;
use WP_Error;
use WP_Http;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class PayplugAPI extends API
{
	private const rest_base = '/payplug/ipn';

	public function __construct()
	{
		add_action('rest_api_init',[$this, 'register_routes']);
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
		Logger::info("payplug.api.create_item", ['request' =>
			[
				'body' => $request->get_body(),
				'params' => $request->get_params(),
				'method' => $request->get_method(),
			]
		]);

		$pm = getParkingManagementInstance();
		if (!$pm)
			return new WP_Error(
				'get-parking-management-instance',
				__('failed to get parking management instance', 'parking-management'),
				array('status' => WP_Http::BAD_REQUEST)
			);
		$payment = $pm->prop('payment');
		$provider = $payment['providers']['payplug'];
		$test_enabled = $provider['active-test'] === '1';
		$secretKey = $test_enabled ? $provider['properties']['secret_key_test']['value'] : $provider['properties']['secret_key']['value'];
		try {
			Payplug::init(array(
				'secretKey' => $secretKey
			));
			$resource = Notification::treat($request->get_body());
			if ($resource instanceof APIResource && $resource->is_paid) {
				$order_id = $resource->metadata['id_commande'];
				$payment_date = date('Y-m-d H:i:s', $resource->hosted_payment->paid_at);
				$amount = ($resource->amount / 100);
				$payment_id = PaymentID::PAYPLUG;
				$order = new Order();
				$order->update_payment((int)$order_id, $payment_date, $amount, $payment_id);
				Logger::info("payplug.api.create", "record payment status recorded");
				return new WP_REST_Response(null, WP_Http::NO_CONTENT);
			}
		} catch (PayplugException|Exception $e) {
			Logger::error("payplug.api.create", $e->getMessage());
			return new WP_Error(
				'payplug-notification-error',
				$e->getMessage(),
				array('status' => WP_Http::BAD_REQUEST)
			);
		}
		Logger::error("payplug.api.create", "record payment status not recorded");

		return new WP_Error(
			"record-payment-status",
			__('payment status not recorded', 'parking-management'),
			array('status' => WP_Http::BAD_REQUEST)
		);
	}
}
