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
use WP_REST_Request;

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
	public static function getPrice(array|WP_REST_Request $data): array
	{
		$pm = getParkingManagementInstance();
		$info = $pm->prop('info');
		$form = $pm->prop('form');
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
		$type_id = $instance->getData($data, 'type_id');
		$type_id = !empty($type_id) && is_numeric($type_id) ? $type_id : '1';
		if (!is_numeric($type_id))
			$type_id = '1';
		$type_id = (int)$type_id;
		$site_id = Order::getSiteID($info['terminal']);
		$parking_type = $type_id - 1;
		$priceGrid = self::priceGrid($site_id, $numberOfDay, $start, $end, $type_id, $parking_type);
		$priceGrid = unserialize($priceGrid['grille']);

		if (!empty($priceGrid[$site_id][$type_id][$parking_type][$numberOfDay])) {
			$total = $priceGrid[$site_id][$type_id][$parking_type][$numberOfDay];
		} else {
			// Price isn't set
			$latest = self::latestPrice($priceGrid[$site_id][$type_id][$parking_type]);
			$total = $priceGrid[$site_id][$type_id][$parking_type][$latest];
			$total += ($numberOfDay - $latest) * $priceGrid[$site_id][$type_id][$parking_type]['jour_supplementaire'];
		}
		$price['total'] = $price['total_reel'] = $total;
		if (self::isHoliday($start))
			$price['total'] += self::get_extra_price($form['options']['holiday']);
		if (self::isHoliday($end))
			$price['total'] += self::get_extra_price($form['options']['holiday']);
		$nb_pax = $instance->getData($data, 'nb_pax');
		if (!empty($nb_pax) && $nb_pax > 4)
			$price['total'] += ($nb_pax - 4) * self::get_extra_price($form['options']['shuttle']);
		$price['timing'] = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
		$price['total'] += self::nigth_extra($start_hour, $form['options']['night_extra_charge']);
		$price['total'] += self::nigth_extra($end_hour, $form['options']['night_extra_charge']);
		$assurance_annulation = $instance->getData($data, 'assurance_annulation');
		if (!empty($assurance_annulation) && ($assurance_annulation == '1')) {
			$price['total'] += (int)$form['options']['cancellation_insurance']['price'];
		}
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
		if ($req->execute(['date' => $date])) {
			$row = $req->fetch(PDO::FETCH_ASSOC);
			return !empty($row);
		}
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

	private static function nigth_extra($hour, $night_extra_charge): int
	{
		if ($night_extra_charge['enabled'] === '1') {
			$hour = (int)str_replace(":", "", $hour);
			if (($hour < 600) || ($hour > 2200))
				return $night_extra_charge['price'];
		}
		return 0;
	}

	private static function get_extra_price($option): int
	{
		if ($option['enabled'] === '1') {
				return $option['price'];
		}
		return 0;
	}

	private function getData(WP_REST_Request|array $data, $field = null)
	{
		if (($data instanceof WP_REST_Request)) {
			if ($data->has_param($field))
				return $data->get_param($field);
			return '';
		}
		if (is_null($field) || !array_key_exists($field, $data))
			return '';
		return $data[$field];
	}

	private function enqueue(): void
	{
		wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', array(), '5.3.3');
	}
}
