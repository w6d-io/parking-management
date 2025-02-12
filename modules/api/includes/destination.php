<?php

namespace ParkingManagement\API;

use Booking\ParkingType;
use Exception;
use ParkingManagement\database\database;
use ParkingManagement\Logger;
use WP_Error;
use WP_REST_Response;
use WP_REST_Server;

class Destination extends API
{
	protected $rest_base = '/destination';

	public function __construct()
	{
		add_action('rest_api_init', array($this, 'register_routes'));
	}

	public function register_routes(): void
	{
		register_rest_route($this->namespace, $this->rest_base, array(
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array($this, 'get_item'),
					'permission_callback' => '__return_true',
					'args' => array(
						'term' => array(
							'description' => "Term to search",
							'type' => "string",
							'required' => true,
						),
						'any_params' => array(
							'required' => false,
						),
					),
				)
			)
		);
	}

	public function get_item($request): WP_Error|WP_REST_Response
	{
		try {
			$kind = 'booking';
			if ($request->has_param('parking_type') && $request['parking_type'] == ParkingType::VALET->value)
				$kind = 'valet';
			$conn = database::connect($kind);
			if (!$conn)
				return new WP_Error("database_connection_failed", __("Database connection failed.", 'parking-management'));
			$data = array();
			if (! $results = $conn->get_results(
				$conn->prepare(
					"SELECT `id_destination`, `iata`, `titre`, `pays` FROM `tbl_destination` WHERE `iata` LiKE %s OR `oaci` LiKE %s OR `titre` LiKE %s ORDER BY `pays` , `titre` ",
					["%" . $request['term'] . "%", "%" . $request['term'] . "%", "%" . $request['term'] . "%"]
				), ARRAY_A)
			) {
				return new WP_Error("database_error", __("Database error.", 'parking-management'));
			}
			foreach ($results as $row) {
				$data[] = array(
					'id' => $row['id_destination'],
					'category' => stripslashes(iconv('utf-8', 'latin1', $row['pays'])),
					'label' => stripslashes(iconv('utf-8', 'latin1', $row['titre'])) . ' (' . $row['iata'] . ')',
					'value' => stripslashes(iconv('utf-8', 'latin1', $row['titre'])));
			}
			return rest_ensure_response($data);
		} catch (Exception $e) {
			Logger::error("destination.get_item", ['request' => $request, 'error' => $e->getMessage()]);
			return new WP_Error('error', $e->getMessage());
		}
	}
}
