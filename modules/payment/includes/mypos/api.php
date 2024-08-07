<?php

namespace ParkingManagement\API;

use Booking\Order;
use Exception;
use Mypos\IPC\Config;
use Mypos\IPC\Defines;
use Mypos\IPC\IPC_Exception;
use Mypos\IPC\Response;
use ParkingManagement\Logger;
use ParkingManagement\PaymentID;
use Payment\MyPos;
use WP_Error;
use WP_Http;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class MyPosAPI extends API
{
	private const rest_base = '/mypos/ipn';

	public function __construct()
	{
		add_action('rest_api_init', [$this, 'register_routes']);
	}

	public function register_routes(): void
	{
		register_rest_route($this->namespace, self::rest_base, [
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => [$this, 'create_item'],
			'permission_callback' => '__return_true',
			'args' => [
				'any_param' => ['required' => false]
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
		Logger::info("mypos.api.create_item", ['request' =>
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
		$provider = $payment['providers']['mypos'];
		$test_enabled = $provider['active-test'] === '1';
		$configPackage = $test_enabled ? MyPos::configTest : $provider['properties']['configuration_package']['value'];
		$ipcURL = $test_enabled ? MyPos::ipcTestURL : MyPos::ipcURL;

		try {
			$cnf = new Config();
			$cnf->setIpcURL($ipcURL);
			$cnf->setLang('en');
			$cnf->setVersion('1.4');
			$cnf->loadConfigurationPackage($configPackage);

			$response = Response::getInstance($cnf, $request->get_params(), Defines::COMMUNICATION_FORMAT_POST);
			$data = $response->getData();
			if ($data['IPCmethod'] === 'IPCPurchaseNotify') {
				$order_id = $data['OrderID'];
				if (!is_numeric($order_id) && $test_enabled) {
					echo "OK";
					exit(0);
				}
				$payment_date = $data['RequestDateTime'];
				$amount = $data['Amount'];
				$payment_id = PaymentID::MYPOS;
				$order = new Order();
				$order->update_payment((int)$order_id, $payment_date, $amount, $payment_id);
				Logger::info("mypos.api.create", "record payment status recorded");
				echo "OK";
				exit(0);
			}
		} catch (IPC_Exception|Exception $e) {
			Logger::error("mypos.api.create", $e->getMessage());
			return new WP_Error(
				'mypos-notification-error',
				$e->getMessage(),
				array('status' => WP_Http::BAD_REQUEST)
			);
		}
		return new WP_Error(
			"record-payment-status",
			'record payment status not recorded'
		);
	}
}
