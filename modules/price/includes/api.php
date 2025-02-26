<?php

namespace ParkingManagement\API;

use Exception;
use ParkingManagement\Logger;
use ParkingManagement\Price;
use WP_Error;
use WP_REST_Response;
use WP_REST_Request;
use WP_REST_Server;

class PriceAPI extends API
{

	private const rest_base = '/prices';

	public function __construct()
	{
		add_action('rest_api_init', array($this, 'register_routes'));
	}

	public function register_routes(): void
	{
		register_rest_route($this->namespace, self::rest_base, array(
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array($this, 'get_item'),
					'permission_callback' => '__return_true',
					'args' => array(
						'depart' => array(
							'description' => "Starting date for the getting price",
							'type' => "string",
							'required' => true,
						),
						'retour' => array(
							'description' => "Ending date for the getting price",
							'type' => "string",
							'required' => true,
						),
						'kind' => [
							'description' => "The kind of the price",
							'type' => "string",
							'required' => true,
						],
						'nb_pax' => array(
							'description' => "Number of passenger",
							'type' => "int",
							'required' => false,
						),
						'type_id' => array(
							'description' => "Vehicle type",
							'type' => "int",
							'required' => false,
						),
						'any_param' => array(
							'required' => false,
						),
					),

				)
			)
		);
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 * @since 4.7.0
	 *
	 */
	public function get_item($request): WP_Error|WP_REST_Response
	{
		Logger::info("price.api.get_item", ['request' =>
			[
				'params' => $request->get_params(),
				'method' => $request->get_method(),
			]
		]);
		try {
			$kind = $request->get_param('kind');
			if ($kind === null) {
				Logger::error("price.api.create_item", ["message" => "missing kind"]);
				return new WP_Error(
					'price-notification-error',
					'Missing parameter',
					array('status' => WP_Http::BAD_REQUEST)
				);
			}
			$pm = getParkingManagementInstance();
			$price = new Price($pm);
			$price->setKind($kind);
			$data = $price->getPrice($request);
		} catch (Exception $e) {
			Logger::error("price.api.get_item", $e->getMessage());
			return new WP_Error('error', $e->getMessage());
		}
		return rest_ensure_response($data);
	}
}
