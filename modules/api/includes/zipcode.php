<?php

namespace ParkingManagement\API;

use Booking\Order;
use Exception;
use ParkingManagement\database\database;
use PDO;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

class Zipcode extends WP_REST_Controller
{
	public function __construct($namespace, $version)
	{
		$this->namespace = $namespace . $version;
		$this->version = $version;
		$this->rest_base = '/zipcode';
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
			$conn = database::connect();
			if (!$conn)
				return new WP_Error("database_connection_failed", __("Database connection failed."));
			$data = array();
			$query = "SELECT `id_code_postal`, `pays_id`, `code_postal`, `ville` FROM `tbl_code_postal` WHERE `code_postal` LIKE ? ORDER BY `pays_id` ASC, `code_postal` ASC";
			$req = $conn->prepare($query);
			if (!$req->execute(array(addslashes($request['term']) . "%"))) {
				return new WP_Error("database_error", __("Database error."));
			}
			while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
				$data[] = array(
					'id' => $row['id_code_postal'],
					'pays_id' => $row['pays_id'],
					'value' => $row['code_postal'],
					'label' => $row['code_postal'] . ' (' . stripslashes($row['ville']) . ')',
					'ville' => stripslashes($row['ville']),
					'pays' => stripslashes(Order::PhoneCountry[$row['pays_id']]['pays']),
					'category' => stripslashes(Order::PhoneCountry[$row['pays_id']]['pays'])
				);
			}
			return rest_ensure_response($data);
		} catch (Exception $e) {
			return new WP_Error('error', $e->getMessage());
		}
	}
}
