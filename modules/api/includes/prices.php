<?php

namespace ParkingManagement\API;

use Exception;
use ParkingManagement\Price;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Request;
use WP_REST_Server;

class Prices extends WP_REST_Controller
{

	public function __construct($namespace, $version)
	{
		$this->namespace = $namespace . $version;
		$this->version = $version;
		$this->rest_base = '/prices';
	}

	public function register_routes(): void
	{
		register_rest_route($this->namespace, $this->rest_base, array(
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
						'nb_pax' => array(
							'description' => "Number of passenger",
							'type' => "int",
							'required' => false,
						),
						'type_id' => array(
							'description' => "Parking type",
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
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item($request): WP_Error|WP_REST_Response
	{
		try {
			$data = Price::getPrice($request);
		} catch (Exception $e) {
			return new WP_Error('error', $e->getMessage());
		}
		return rest_ensure_response($data);
	}
}
