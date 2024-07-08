<?php

namespace Booking;

use DateTime;
use Exception;
use ParkingManagement\database\database;
use ParkingManagement\Price;
use PDO;

require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "database" . DS . "database.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "price" . DS . "price.php";

class Order
{

	private string $terminal;
	private int $site_id;
	private array $data;
	private bool|PDO $conn;
	public const ORLY = 1;
	public const ROISSY = 2;
	public const ZAVENTEM = 3;

	public const PhoneCountry = array(
		1 => array('id' => 1, 'initial' => 'FR', 'pays' => 'France', 'prefix' => '0033', 'size' => 13, 'min' => '0', 'before' => '0'),
		2 => array('id' => 2, 'initial' => 'BE', 'pays' => 'Belgium', 'prefix' => '0032', 'size' => 13, 'min' => '0', 'before' => ''),
		3 => array('id' => 3, 'initial' => 'CH', 'pays' => 'Switzerland', 'prefix' => '0041', 'size' => 13, 'min' => '0', 'before' => ''),
		4 => array('id' => 4, 'initial' => 'LU', 'pays' => 'Luxembourg', 'prefix' => '00352', 'size' => 11, 'min' => 0, 'before' => ''),
		5 => array('id' => 5, 'initial' => 'MC', 'pays' => 'Monaco', 'prefix' => '00377', 'size' => 13, 'min' => 0, 'before' => ''),
		6 => array('id' => 6, 'initial' => 'DE', 'pays' => 'Germany', 'prefix' => '0049', 'size' => 14, 'min' => 0, 'before' => ''),
		7 => array('id' => 7, 'initial' => 'NL', 'pays' => 'Netherlands', 'prefix' => '0031', 'size' => 13, 'min' => 0, 'before' => '')
	);

	/**
	 * @throws Exception
	 */
	public function __construct()
	{
		$pm = getParkingManagementInstance();
		if (!$conn = database::connect()) {
			database::getError();
			return;
		}
		$this->conn = $conn;
		$info = $pm->prop('info');
		if (empty($info)) {
			throw new Exception('Parking management info property is empty.');
		}
		$this->terminal = $info['terminal'];
		$this->site_id = self::getSiteID($info['terminal']);
		$this->data = array_merge($_GET, $_POST);
		date_default_timezone_set('Europe/Paris');
	}

	/**
	 * @throws Exception
	 */
	public function create(string $member_id): int
	{
		$query = "SELECT `grille_tarifaire` FROM `tbl_remplissage` WHERE `date` = ?";
		$req = $this->conn->prepare($query);
		$req->execute(array(substr($this->getData('navette'), 0, 10)));
		$row = $req->fetch(PDO::FETCH_ASSOC);

		$unserialize = unserialize($row['grille_tarifaire']);

		$price_grid = $unserialize[$this->site_id];

		if (!$member_id)
			throw new Exception("order creation failed");
		$billing = implode(" ", array(ucwords($this->getData('nom')), ucwords($this->getData('prenom')))) . "\n";
		$billing .=
			implode(
				" ",
				array(
					ucwords(
						$this->getData('code_postal')
					),
					ucwords(
						$this->getData('ville')
					)
				)
			);
		$billing .= ',  ' . mb_strtoupper(self::PhoneCountry[$this->getData('pays')]['pays'], 'UTF-8');
		$start = substr($this->getData('depart'), 0, 10);
		$start_hour = substr($this->getData('depart'), 11, 5);
		$end = substr($this->getData('retour'), 0, 10);
		$end_hour = substr($this->getData('retour'), 11, 5);


		$search = implode(" ",
			array(ucwords($this->getData('prenom')), ucwords($this->getData('nom')), ucwords($this->getData('email')),
				str_replace('+', '00', $this->getData('tel_port'))
			)
		);
		$referer = parse_url($_SESSION['HTTP_REFERER']);
		$referer_host = array_key_exists('host', $referer) ? $referer['host'] : NULL;
		$price = Price::getPrice($this->data);
		$params = array(
			'resauuid' => uniqid(),
			'site_id' => $this->site_id,
			'parking_id' => $this->site_id,
			'date' => date('Y-m-d H:i:s'),
			'membre_id' => $member_id,
			'telephone' => $this->formatPhone($this->getData('tel_port')),
			'facturation' => $billing,
			'depart' => $start,
			'depart_heure' => $start_hour,
			'arrivee' => $end,
			'arrivee_heure' => $end_hour,
			'nb_jour' => self::nbRealDay($start, $end),
			'nb_jour_offert' => 0,
			'nb_personne' => serialize(array(
				'aller' => $this->getData('nb_pax')
			, 'retour' => $this->getData('nb_pax')
			)),
			'destination_id' => $this->getData('destination_id'),
			'terminal' => serialize($this->getData('terminal')),
			'remarque' => '',
			'total' => $price['total'],
			'grille_tarifaire' => $price_grid,
			'tva' => 20,
			'tva_transport' => 10,
			'coupon_id' => 0,
			'recherche' => utf8_decode($search),
			'status' => 1,
			'nb_retard' => 0,
			'ip' => $_SERVER['REMOTE_ADDR'],
			'host' => $_SERVER['HTTP_USER_AGENT'],
			'referer' => $referer_host
		);
		$query = "
		INSERT INTO `tbl_commande`
		(
		    `resauuid`, `site_id`, `parking_id`,  `date`, `membre_id`
		    , `telephone`, `facturation`, `depart`, `depart_heure`, `arrivee`
		    , `arrivee_heure`, `nb_jour`, `nb_jour_offert`, `nb_personne`
		    , `destination_id`, `terminal`, `remarque`, `total`, `grille_tarifaire`, `tva`
		    , `tva_transport`, `coupon_id`, `recherche`, `status`
		    , `nb_retard`
		    , `ip`, `host`, `referer`
		)
		VALUES
		(
		    :resauuid, :site_id , :parking_id , :date , :membre_id
		    , :telephone , :facturation , :depart , :depart_heure , :arrivee
		    , :arrivee_heure , :nb_jour , :nb_jour_offert , :nb_personne
		    , :destination_id , :terminal , :remarque, :total , :grille_tarifaire, :tva
		    , :tva_transport , :coupon_id , :recherche , :status
		    , :nb_retard
		    , :ip, :host, :referer
		)
		";
		$req = $this->conn->prepare($query);
		if (!$req->execute($params))
			throw new Exception("order creation failed");
		$id = $this->conn->lastInsertId();
		if (!$id)
			throw new Exception("order creation failed");
		$query = 'UPDATE `tbl_commande` SET remarque = :remarque, facture_id = :facture_id WHERE `id_commande` = :id';
		$req = $this->conn->prepare($query);
		if (!$req->execute(array(
			'id' => $id,
			'remarque' => "Commande Parking " . $this->terminal . " / Destination : " . utf8_decode($this->getData('destination')) . " / Reference : " . $id,
			'facture_id' => $this->getBillID($id)
		)))
			throw new Exception("order update failed");
		return (int)$id;
	}

	public function isExists(string $member_id): int
	{
		$start = substr($this->getData('depart'), 0, 10);
		$start_hour = substr($this->getData('depart'), 11, 5);
		$end = substr($this->getData('retour'), 0, 10);
		$end_hour = substr($this->getData('retour'), 11, 5);
		$query = "SELECT `id_commande` FROM `tbl_commande` WHERE `membre_id` = :member_id AND `depart` = :depart AND `depart_heure` = :depart_heure AND `arrivee` = :arrivee AND `arrivee_heure` = :arrivee_heure";
		$req = $this->conn->prepare($query);
		$req->execute(array(
			'membre_id' => $member_id,
			'depart' => $start,
			'depart_heure' => $start_hour,
			'arrivee' => $end,
			'arrivee_heure' => $end_hour
		));
		if ($row = $req->fetch(PDO::FETCH_ASSOC)) {
			if ($row['id_commande'] != '')
				return (int)$row['id_commande'];
		}
		return 0;
	}

	private function getData($field = null)
	{
		if (is_null($field) || !array_key_exists($field, $this->data))
			return '';
		return $this->data[$field];
	}

	private function getBillID($id)
	{
		$query = "SELECT `facture_id` FROM `tbl_commande` WHERE `id_commande` = :id";
		$req = $this->conn->prepare($query);
		$req->execute(array('id' => $id));
		if ($row = $req->fetch(PDO::FETCH_ASSOC)) {
			if ($row['facture_id'] != '')
				return $row['facture_id'];
		}

		$site = $this->terminal;
		$query = "SELECT max(`facture_id`) AS facture_id FROM `tbl_commande` WHERE `facture_id` LIKE '$site%'";
		$req = $this->conn->prepare($query);
		$req->execute();
		$row = $req->fetch(PDO::FETCH_ASSOC);
		return ($site . str_pad((int)(substr($row['facture_id'], 3)) + 1, 9, 0, STR_PAD_LEFT));
	}

	public static function nbRealDay($start, $end): int
	{

		$start = DateTime::createFromFormat('d/m/Y', $start);
		$end = DateTime::createFromFormat('d/m/Y', $end);
		$interval = $start->diff($end);

		return $interval->days + 1;

	}

	private function formatPhone(string $phone): string|null
	{
		return !empty($phone) ? str_replace('+', '00', str_replace('+330', '+33', $phone)) : NULL;
	}

	public static function getSiteID($terminal): int
	{
		return match (strtolower($terminal)) {
			"roissy" => self::ROISSY,
			"zaventem" => self::ZAVENTEM,
			default => self::ORLY,
		};
	}
}
