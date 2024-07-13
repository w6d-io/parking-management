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

class Destination extends WP_REST_Controller
{
	public function __construct($namespace, $version)
	{
		$this->namespace = $namespace . $version;
		$this->version = $version;
		$this->rest_base = '/destination';
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
			$query = "SELECT `id_destination`, `iata`, `titre`, `pays` FROM `tbl_destination` WHERE `iata` LiKE :term OR `oaci` LiKE :term OR `titre` LiKE :term ORDER BY `pays` ASC, `titre` ASC";
			$req = $conn->prepare($query);
			if (!$req->execute(array('term' => "%".$request['term'] . "%"))) {
				return new WP_Error("database_error", __("Database error."));
			}
			while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
				$data[] = array(
					'id' => $row['id_destination'],
					'category' => stripslashes(iconv('utf-8', 'latin1',$row['pays'])),
					'label' => stripslashes(iconv('utf-8', 'latin1',$row['titre'])).' ('.$row['iata'].')',
					'value' => stripslashes(iconv('utf-8', 'latin1',$row['titre'])));;
			}
			return rest_ensure_response($data);
		} catch (Exception $e) {
			return new WP_Error('error', $e->getMessage());
		}
	}
}