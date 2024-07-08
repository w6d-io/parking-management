<?php

namespace ParkingManagement;

use Booking\Order;
use DateTime;
use Exception;
use ParkingManagement\database\database;
use ParkingManagement\interfaces\IParkingmanagement;
use ParkingManagement\interfaces\IShortcode;
use PDO;
use Price\Page;

require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "booking" . DS . "includes" . DS . "order.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "database" . DS . "database.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "price" . DS . "includes" . DS . "page.php";


class Price implements IShortcode, IParkingmanagement
{
	private ParkingManagement $pm;

	public function __construct(ParkingManagement $pm)
	{
		$this->pm = $pm;
	}

	public function shortcode(string $type): string
	{
		$this->enqueue();
		$page = new Page($this->pm);
		return Html::_div(array('class' => 'container-md mt-5'),
			$page->table(),
		);
	}

	/**
	 * @throws Exception
	 */
	public static function getPriceFromAPI($data): mixed
	{
		try {
			$pm = getParkingManagementInstance();
			if (!$pm) {
				throw new Exception("failed to get parking management instance");
			}
			$api = $pm->prop('api');
			$endpoint = $api['price_endpoint'];
			$auth = '';
			if (isset($api['username'])) {
				$auth .= $api['username'];
			}
			if (isset($api['password'])) {
				$auth .= ':' . $api['password'];
			}
			if (!empty($auth))
				$auth = "Authorization: Basic " . base64_encode($auth) . "\r\n";
			$url = $endpoint;
			$options = array(
				'http' => array(
					'header' => "Content-Type: application/json\r\n$auth",
					'method' => 'POST',
					'content' => json_encode($data)
				),
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false
				)
			);
			$context = stream_context_create($options);

			$result_raw = file_get_contents($url, false, $context);
			return json_decode($result_raw);
		} catch (Exception $e) {
			print_log($e->getMessage(), false);
		}
		return false;
	}

	/**
	 * @throws Exception
	 */
	public static function getPrice(array $data): array
	{
		$pm = getParkingManagementInstance();
		$info = $pm->prop('info');
		$form = $pm->prop('$form');
		$instance = new self($pm);
		$start = substr($instance->getData($data, 'depart'), 0, 10);
		$start_hour = substr($instance->getData($data, 'depart'), 11, 5);
		$end = substr($instance->getData($data, 'retour'), 0, 10);
		$end_hour = substr($instance->getData($data, 'retour'), 11, 5);

		$realNumberOfDay = $numberOfDay = Order::nbRealDay($start, $end);
		$maxLot = Booked::getMaxLot($start, $end, Order::getSiteID($info['terminal']));
		$usedLot = Booked::usedLot($start, $end, Order::getSiteID($info['terminal']));
		$maxUsedLot = max($usedLot);
		$percentage = array();
		foreach ($maxLot as $k => $v) {
			$percentage[] = !empty($usedLot[$k]) ? $v / $usedLot[$k] : 0;
		}
		$price = array(
			'complet' => 0,
			'toolong' => 0,
			'max' => max($maxLot),
			'utilise' => $maxUsedLot,
			'nb_jour_reel' => $realNumberOfDay,
			'nb_jour' => $numberOfDay,
			'pourcentage' => max($percentage),
			'promo' => 0,
			'options' => array()
		);
		$price['du'] = !empty($start) ? $start : NULL;
		$price['au'] = !empty($end) ? $end : NULL;
		$price['nb_vehicule'] = 1;

		$priceGrid = self::priceGrid(Order::getSiteID($info['terminal']), $numberOfDay, $start, $end, $instance->getData($data, 'type_id'), $instance->getData($data, 'parking_type'));
		$priceGrid = unserialize($priceGrid['grille']);

		$parking_type = $instance->getData($data, 'type_id') - 1;
		if (!empty($priceGrid[Order::getSiteID($info['terminal'])][$instance->getData($data, 'type_id')][$parking_type][$numberOfDay])) {
			$total = $priceGrid[Order::getSiteID($info['terminal'])][$instance->getData($data, 'type_id')][$parking_type][$numberOfDay];
		} else {
			// Price isn't set
			$latest = self::latestPrice($priceGrid[Order::getSiteID($info['terminal'])][$instance->getData($data, 'type_id')][$parking_type]);
			$total = $priceGrid[Order::getSiteID($info['terminal'])][$instance->getData($data, 'type_id')][$parking_type][$latest];
			$total += ($numberOfDay - $latest) * $priceGrid[Order::getSiteID($info['terminal'])][$instance->getData($data, 'type_id')][$parking_type]['jour_supplementaire'];
		}

		$price['total'] = $price['total_reel'] = $total;
		$extraCharge = self::extraCharge();
		if (self::isHoliday($start))
			$price['total'] += $extraCharge['ferie'];
		if (self::isHoliday($end))
			$price['total'] += $extraCharge['ferie'];
		$nb_pax = $instance->getData($data, 'nb_pax');
		if (!empty($nb_pax) && $nb_pax > 4)
			$price['total'] += ($nb_pax - 4) * $extraCharge['pax_charge'];
		$price['timing'] = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
		$price['total'] += self::nigth_extra($start_hour, $form['night_extra_charge'], $extraCharge);
		$price['total'] += self::nigth_extra($end_hour, $form['night_extra_charge'], $extraCharge);
		return $price;
	}

	/**
	 * @throws Exception
	 */
	public static function isHoliday($date): bool
	{
		$conn = database::connect();
		if (!$conn)
			throw new Exception("Database connection failed");
		$query = "SELECT DATE_FORMAT(`date`, '%d/%m/%Y') as `date`, `titre` FROM `tbl_ferie` WHERE `date` = :date";
		$req = $conn->prepare($query);
		if ($req->execute(['date' => $date])) return true;
		return false;
	}


	/**
	 * @throws Exception
	 */
	public static function priceGrid(int $parking_id = 1, $numberOfDay = 0, $start = NULL, $end = NULL, $type_id = 1, $parking = 0): array
	{
		$conn = database::connect();
		if (!$conn) {
			print_log(database::getError());
			throw new Exception("Database connection failed");
		}
		$start = !empty($start) ? $start : date('d/m/Y');
		$end = !empty($end) ? $end : date('d/m/Y');
		$start = DateTime::createFromFormat('d/m/Y', $start);
		$end = DateTime::createFromFormat('d/m/Y', $end);

		$priceGrid = $price = array();
		$query = "SELECT `date`, `grille_tarifaire` FROM `tbl_remplissage` WHERE (`date` BETWEEN :start AND :end)";
		$req = $conn->prepare($query);
		if (!$req->execute(['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')]))
			throw new Exception("get price grid failed");
		while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
			$priceGrid[$row['date']] = $row['grille_tarifaire'];
			$deserialized = unserialize($row['grille_tarifaire']);
			$latest = self::latestPrice($deserialized[$parking_id][$type_id][$parking]);
			$price[$row['date']] = !empty($deserialized[$parking_id][$type_id][$parking][$numberOfDay]) && !empty($latest) ? $deserialized[$parking_id][$type_id][$parking][$numberOfDay] : $deserialized[$parking_id][$type_id][$parking][$latest];
		}
		if (empty($price) || empty($priceGrid))
			throw new Exception("price grid empty");
		$max = max($price);    // Get the higher price
		$flip = array_flip($price);
		return array('grille' => $priceGrid[$flip[$max]], 'date' => $flip[$max]);
	}

	/**
	 * @throws Exception
	 */
	public static function extraCharge(): array
	{
		$conn = database::connect();
		if (!$conn)
			throw new Exception("Database connection failed");
		$query = "SELECT `name`, `price` FROM tbl_extra_charge";
		$req = $conn->prepare($query);
		if (!$req->execute())
			throw new Exception("get price extra charge failed");
		$extraCharge = array();
		while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
			$extraCharge[$row['name']] = $row['price'];
		}
		return $extraCharge;
	}

	public static function latestPrice($grid): int
	{
		$day = 1;
		foreach ($grid as $k => $v) {
			if (empty($v)) {
				$day = $k;
				break;
			}
		}
		return $day - 1;
	}

	private static function nigth_extra($hour, $night_extra_charge, $extraCharge): int
	{
		if ($night_extra_charge === '1') {
			$hour = (int)str_replace(":", "", $hour);
			if (($hour < 600) || ($hour > 2200))
				return $extraCharge['arrivee_de_nuit'];
		}
		return 0;
	}

	private function getData(array $data, $field = null)
	{
		if (is_null($field) || !array_key_exists($field, $data))
			return '';
		return $data[$field];
	}

	private function enqueue(): void
	{
		wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', array(), '5.3.3');
	}
}
