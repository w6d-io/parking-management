<?php

namespace Booking;

use Exception;
use ParkingManagement\database\database;
use ParkingManagement\Price;
use PDO;

require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "database" . DS . "database.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "price" . DS . "price.php";

class Vehicle
{

	private array $data;
	private PDO $conn;

	public function __construct()
	{
		if (!$conn = database::connect()) {
			database::getError();
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
		if(!$req->execute($params))
		{
			if ($this->getData('DEBUG') === '1') {
				print_log($this->conn->errorInfo(), false);
			}
			throw new Exception("vehicle creation failed");
		}
		return (int)$this->conn->lastInsertId();
	}

	private function getData(string|null $field = null)
	{
		if (is_null($field) || !array_key_exists($field, $this->data))
			return '';
		return $this->data[$field];
	}

}
