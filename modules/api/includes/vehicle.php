<?php

namespace ParkingManagement\API;

use Exception;
use ParkingManagement\database\database;
use ParkingManagement\Logger;
use PDO;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

class Vehicle extends API
{
	protected $rest_base = '/vehicle';

	public function __construct()
	{
		add_action('rest_api_init', array($this, 'register_routes'));
	}

	public function register_routes(): void
	{
		register_rest_route($this->namespace, $this->rest_base, [
			[
				'methods' => WP_REST_Server::READABLE,
				'callback' => [$this, 'get_item'],
				'permission_callback' => '__return_true',
				'args' => [
					'term' => [
						'description' => "Term to search",
						'type' => "string",
						'required' => true,
					],
					'any_params' => [
						'required' => false,
					],
				],
			]
		]);
	}

	public function get_item($request): WP_Error|WP_REST_Response
	{
		try {
			$conn = database::connect();
			if (!$conn)
				return new WP_Error("database_connection", __("Database connection failed.", 'parking-management'));
			$data = array();
			$query = "SELECT `tbl_modele`.`id_modele`, `tbl_modele`.`titre`, `tbl_marque`.`titre` as `marque` FROM `tbl_modele` LEFT JOIN `tbl_marque` ON `tbl_marque`.`id_marque` = `tbl_modele`.`marque_id` WHERE (`tbl_modele`.`titre` LiKE :term OR `tbl_modele`.`titre` LiKE :term2) ORDER BY `tbl_marque`.`titre` ASC, `tbl_modele`.`titre` ASC";
			$req = $conn->prepare($query);
			if (!$req->execute([
					'term' => "%" . sansAccent($request['term']) . "%",
					'term2' => "%" . slug($request['term']) . "%"
				]
			)) {
				return new WP_Error("database_error", __("Database error.", 'parking-management'));
			}
			while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
				$data[] = array(
					'category' => stripslashes($row['marque']),
					'label' => stripslashes($row['titre']),
					'value' => stripslashes($row['titre'])
				);
			}
			return rest_ensure_response($data);
		} catch (Exception $e) {
			Logger::error("vehicle.get_item", ['request'=>$request,'error'=>$e->getMessage()]);
			return new WP_Error('error', $e->getMessage());
		}
	}
}
