<?php

namespace Booking;

use Exception;
use ParkingManagement\database\database;
use ParkingManagement\Logger;
use ParkingManagement\Price;
use PDO;

class Vehicle
{

	private array $data;
	private PDO $conn;

	public function __construct()
	{
		if (!$conn = database::connect()) {
			Logger::error("member.database.connect", database::getError());
			return;
		}
		$this->conn = $conn;
		$this->data = array_merge($_GET, $_POST);
	}

	/**
	 * @throws Exception
	 */
	public function create(int $order_id = 0): int
	{
		if (!$order_id)
			throw new Exception("vehicle creation failed: order_id required");
		if ($vehicule_id = $this->isExists($order_id))
			return $vehicule_id;
		$price = Price::getPrice($this->data);
		$params = array(
			'commande_id' => $order_id
		, 'type_id' => $this->getData('type_id')
		, 'parking_type' => $this->getData('parking_type')
		, 'options' => serialize(array())
		, 'marque' => $this->getData('marque') ?? 'Unknown'
		, 'modele' => $this->getData('modele')
		, 'immatriculation' => $this->getData('immatriculation')
		, 'nb_personne' => serialize(
				array(
					'aller' => $this->getData('nb_pax'),
					'retour' => $this->getData('nb_pax')
				)
			)
		, 'tarif' => $price['total']
		, 'commentaire' => ''
		, 'status' => serialize(array(
				0 => array('encours' => '00:00', 'fait' => '00:00')
			, 1 => array('encours' => '00:00', 'fait' => '00:00')
			))
		);
		$query = "
INSERT INTO `tbl_vehicule`(`commande_id`, `type_id`, `parking_type`, `options`, `marque`, `modele`, `immatriculation`, `nb_personne`, `tarif`, `status`, `commentaire`)
VALUES (:commande_id, :type_id, :parking_type, :options, :marque, :modele, :immatriculation, :nb_personne, :tarif, :status, :commentaire)";
		$req = $this->conn->prepare($query);
		Logger::info("vehicule.create", ['params' => $params]);
		if (!$req->execute($params)) {
			Logger::error("vehicule.create", ['error' => $req->errorInfo()]);
			throw new Exception(esc_html__("vehicle creation failed", 'parking-management'));
		}
		return (int)$this->conn->lastInsertId();
	}

	public function isExists(string $order_id): int
	{

		$query = "SELECT `id_vehicule` FROM `tbl_vehicule`
                     WHERE
                	`commande_id` = :order_id";
		$req = $this->conn->prepare($query);
		$req->execute(array(
			'order_id' => $order_id,
		));
		if ($row = $req->fetch(PDO::FETCH_ASSOC)) {
			if ($row['id_vehicule'] != '')
				return (int)$row['id_vehicule'];
		}
		return 0;
	}

	public function read(string $order_id): array
	{
		$query = "SELECT `id_vehicule` FROM `tbl_vehicule`
                     WHERE
                	`commande_id` = :order_id";
		$req = $this->conn->prepare($query);
		if(!$req->execute(array(
			'order_id' => $order_id,
		))){
			Logger::error("vehicle.read", ['error' => $req->errorInfo()]);
			return [];
		}
		return $req->fetch(PDO::FETCH_ASSOC);

	}

	private function getData(string|null $field = null)
	{
		if (is_null($field) || !array_key_exists($field, $this->data))
			return '';
		return $this->data[$field];
	}

}
