<?php

namespace Booking;

use DateTime;
use Exception;
use InvalidArgumentException;
use ParkingManagement\database\database;
use ParkingManagement\Logger;
use ParkingManagement\Payment;
use ParkingManagement\PaymentID;
use ParkingManagement\Price;
use PDO;

enum OrderStatus: int
{
	case PENDING = 0;
	case CONFIRMED = 1;
	case PAID = 2;
	case COMPLETED = 3;
}

enum AirPort: int
{
	case ORLY = 1;
	case ROISSY = 2;
	case ZAVENTEM = 3;
}

enum VehicleType: int
{
	case CAR = 1;
	case MOTORCYCLE = 2;
	case TRUCK = 3;

	public static function fromInt(int $value): ?self
	{
		foreach (self::cases() as $case) {
			if ($case->value === $value) {
				return $case;
			}
		}
		throw new InvalidArgumentException("Invalid value for VehicleType enum: $value");
	}
}

enum ParkingType: int
{
	case OUTSIDE = 0;
	case INSIDE = 1;

	public static function fromInt(int $value): ?self
	{
		foreach (self::cases() as $case) {
			if ($case->value === $value) {
				return $case;
			}
		}
		throw new InvalidArgumentException("Invalid value for ParkingType enum: $value");
	}
}

class Order
{
	private string $terminal;
	private int $site_id;
	private array $data;

	private array $order;

	public function getOrder(): array
	{
		return $this->order;
	}

	private bool|PDO $conn;
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
			Logger::error("member.database.connect", database::getError());
			return;
		}
		$this->conn = $conn;
		$info = $pm->prop('info');
		if (empty($info))
			throw new Exception('Parking management info property is empty.');
		$this->terminal = $info['terminal'];
		$this->site_id = self::getSiteID($info['terminal'])->value;
		$this->data = $_POST;
		date_default_timezone_set('Europe/Paris');
	}

	/**
	 * @throws Exception
	 */
	public function create(string $member_id): int
	{
		if (!$member_id)
			throw new Exception("order creation failed");
		if ($order_id = $this->isExists($member_id))
			return $order_id;
		$query = "SELECT `grille_tarifaire` FROM `tbl_remplissage` WHERE `date` = ?";
		$date = substr($this->getData('depart'), 0, 10);
		$req = $this->conn->prepare($query);
		$req->execute(array($date));
		$row = $req->fetch(PDO::FETCH_ASSOC);

		$unserialize = unserialize($row['grille_tarifaire']);

		$price_grid = $unserialize[$this->site_id];

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
		$end = substr($this->getData('retour'), 0, 10);
		$start = DateTime::createFromFormat('Y-m-d', $start);
		$end = DateTime::createFromFormat('Y-m-d', $end);
		$start_hour = substr($this->getData('depart'), 11, 5);
		$end_hour = substr($this->getData('retour'), 11, 5);


		$search = implode(" ",
			array(ucwords($this->getData('prenom')), ucwords($this->getData('nom')), ucwords($this->getData('email')),
				str_replace('+', '00', $this->getData('tel_port'))
			)
		);
		$referer = parse_url($_SERVER['HTTP_REFERER']);
		$referer_host = array_key_exists('host', $referer) ? $referer['host'] : NULL;
		$price = Price::getPrice($this->data);
		$this->order = array(
			'resauuid' => uniqid(),
			'site_id' => $this->site_id,
			'parking_id' => $this->site_id,
			'date' => date('Y-m-d H:i:s'),
			'membre_id' => $member_id,
			'telephone' => $this->formatPhone($this->getData('tel_port')),
			'facturation' => $billing,
			'depart' => $start->format('Y-m-d'),
			'depart_heure' => $start_hour,
			'arrivee' => $end->format('Y-m-d'),
			'arrivee_heure' => $end_hour,
			'nb_jour' => $price['nb_jour'],
			'nb_jour_offert' => '0',
			'nb_personne' => serialize(array(
					'aller' => $this->getData('nb_pax'),
					'retour' => $this->getData('nb_pax')
				)
			),
			'destination_id' => $this->getData('destination_id'),
			'terminal' => serialize($this->getData('terminal')),
			'remarque' => '',
			'total' => $price['total'],
			'grille_tarifaire' => serialize($price_grid),
			'tva' => 20,
			'tva_transport' => 10,
			'coupon_id' => 0,
			'recherche' => mb_convert_encoding($search, 'ISO-8859-1', 'UTF-8'),
			'status' => Payment::validateOnPayment() && Payment::isEnabled() ? OrderStatus::PENDING->value : OrderStatus::CONFIRMED->value,
			'nb_retard' => 0,
			'ip' => $_SERVER['REMOTE_ADDR'],
			'host' => $_SERVER['HTTP_USER_AGENT'],
			'referer' => $referer_host
		);
		Logger::info("order.create", ['params' => $this->order]);

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
		if (!$req->execute($this->order))
			throw new Exception("order creation failed");
		$id = $this->conn->lastInsertId();
		if (!$id)
			throw new Exception("order creation failed");
		Logger::info("order.create", ['id' => $id]);
		$query = 'UPDATE `tbl_commande` SET remarque = :remarque, facture_id = :facture_id WHERE `id_commande` = :id';
		$req = $this->conn->prepare($query);
		$this->order['id'] = (int)$id;
		$this->order['remarque'] = "Commande Parking " . $this->terminal . " / Destination : " . mb_convert_encoding($this->getData('destination'), 'ISO-8859-1', 'UTF-8') . " / Reference : " . $id;
		$this->order['facture_id'] = $this->getBillID($id);
		if (!$req->execute(array(
			'id' => $id,
			'remarque' => $this->order['remarque'],
			'facture_id' => $this->order['facture_id']
		)))
			throw new Exception("order update failed");
		return (int)$id;
	}

	/**
	 * @throws Exception
	 */
	public function read(int $order_id): array
	{
		$query = 'SELECT `resauuid`, `site_id`, `parking_id`,  `date`, `membre_id`
		    , `telephone`, `facture_id`, `facturation`, `depart`, `depart_heure`, `arrivee`
		    , `arrivee_heure`, `nb_jour`, `nb_jour_offert`, `nb_personne`
		    , `destination_id`, `terminal`, `remarque`, `total`, `grille_tarifaire`, `tva`
		    , `tva_transport`, `coupon_id`, `recherche`, `status`
		    , `nb_retard`
		    , `ip`, `host`, `referer` FROM `tbl_commande` WHERE `id_commande` = :id';
		$req = $this->conn->prepare($query);
		if (!$req->execute(array('id' => $order_id)))
			throw new Exception("order read failed");
		return $req->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * @throws Exception
	 */
	public function update_payment(int $order_id, string $payment_date, float $amount, PaymentID $payment_id): void
	{
//		$payment_date = date('Y-m-d H:i:s', $payment_date);
		$bill_id = $this->getBillID($order_id);
		$date = date('Y-m-d H:i:s');
		$query = "UPDATE `tbl_commande` SET
                          `facture_id` = :bill_id,
                          `paye` = :paid,
                          `date_paiement` = :payment_date,
                          `paiement_id` = :paiement_id,
                          `status` = :status,
                          `date` = :date
                      WHERE
                          `id_commande` = :order_id";
		$req = $this->conn->prepare($query);
		if (!$req->execute(array(
			'bill_id' => $bill_id,
			'paid' => $amount,
			'payment_date' => $payment_date,
			'paiement_id' => $payment_id->value,
			'status' => OrderStatus::PAID->value,
			'date' => $date,
			'order_id' => $order_id
		)))
			throw new Exception("payment order update failed");

	}

	public function isExists(string $member_id): int
	{

		$start = substr($this->getData('depart'), 0, 10);
		$start_hour = substr($this->getData('depart'), 11, 5);
		$end = substr($this->getData('retour'), 0, 10);
		$end_hour = substr($this->getData('retour'), 11, 5);
		$start = DateTime::createFromFormat('Y-m-d', $start);
		$end = DateTime::createFromFormat('Y-m-d', $end);

		$query = "SELECT `id_commande` FROM `tbl_commande` WHERE `membre_id` = :member_id AND `depart` = :depart AND `depart_heure` = :depart_heure AND `arrivee` = :arrivee AND `arrivee_heure` = :arrivee_heure";
		$req = $this->conn->prepare($query);
		$req->execute(array(
			'member_id' => $member_id,
			'depart' => $start->format('Y-m-d'),
			'depart_heure' => $start_hour,
			'arrivee' => $end->format('Y-m-d'),
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

		$site = strtoupper($this->terminal);
		$query = "SELECT count(*) AS num FROM `tbl_commande` WHERE `facture_id` LIKE '$site%'";
		$req = $this->conn->prepare($query);
		$req->execute();
		$row = $req->fetch(PDO::FETCH_ASSOC);
		return ($site . str_pad((int)($row['num']) + 1, 9, 0, STR_PAD_LEFT));
	}

	public static function nbRealDay($start, $end): int
	{
		$start = DateTime::createFromFormat('Y-m-d', $start);
		$end = DateTime::createFromFormat('Y-m-d', $end);
		$interval = $start->diff($end);
		return ($interval->days + 1);

	}

	private function formatPhone(string $phone): string|null
	{
		return !empty($phone) ? str_replace('+', '00', str_replace('+330', '+33', $phone)) : NULL;
	}

	public static function getSiteID($terminal): AirPort
	{
		return match (strtolower($terminal)) {
			"roissy" => AirPort::ROISSY,
			"zaventem" => AirPort::ZAVENTEM,
			default => AirPort::ORLY,
		};
	}
}
