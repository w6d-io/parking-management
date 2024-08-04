<?php

namespace ParkingManagement;

use Booking\AirPort;
use Booking\Order;
use Booking\ParkingType;
use Booking\VehicleType;
use DateTime;
use Exception;
use ParkingManagement\API\PriceAPI;
use ParkingManagement\database\database;
use ParkingManagement\interfaces\IParkingmanagement;
use ParkingManagement\interfaces\IShortcode;
use PDO;
use Price\Page;
use WP_REST_Request;

require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "price" . DS . "includes" . DS . "page.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "price" . DS . "includes" . DS . "api.php";


class Price implements IShortcode, IParkingmanagement
{
	private ParkingManagement $pm;

	public function __construct(ParkingManagement $pm)
	{
		$this->pm = $pm;
	}

	public function shortcode(string $type): string
	{
		try {
			$this->enqueue();
			$page = new Page($this->pm);
			return Html::_div(array('class' => 'container-md mt-5'),
				$page->table(),
			);
		} catch (Exception $e)
		{
			Logger::error("price.shortcode", $e->getMessage());
			return '';
		}
	}

	/**
	 * @throws Exception
	 */
	public static function getPrice(array|WP_REST_Request $data): array
	{
		$pm = getParkingManagementInstance();
		$info = $pm->prop('info');
		$form = $pm->prop('form');
		$high_season = $pm->prop('high_season');
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
			'du' => !empty($start) ? $start : NULL,
			'au' => !empty($end) ? $end : NULL,
			'complet' => 0,
			'toolong' => 0,
			'holiday' => 0,
			'high_season' => 0,
			'total' => 0,
			'total_reel' => 0,
			'timing' => 0,
			'max' => max($maxLot),
			'utilise' => $maxUsedLot,
			'nb_jour_reel' => $realNumberOfDay,
			'nb_jour' => $numberOfDay,
			'pourcentage' => max($percentage),
			'nb_vehicule' => 1,
			'promo' => 0,
			'options' => array(),
		);
		if (Booked::is($start) || Booked::is($end)) {
			$price['complet'] = 1;
			return $price;
		}

		$type_id = $instance->getData($data, 'type_id');
		$type_id = !empty($type_id) && is_numeric($type_id) ? $type_id : '1';
		if (!is_numeric($type_id))
			$type_id = '1';
		$type_id = VehicleType::fromInt((int)$type_id);
		$site_id = Order::getSiteID($info['terminal'])->value;
		$parking_type = ParkingType::fromInt((int)$instance->getData($data, 'parking_type'));
		if ( $type_id == VehicleType::TRUCK)
			$parking_type = ParkingType::OUTSIDE;
		if ( $type_id == VehicleType::MOTORCYCLE)
			$parking_type = ParkingType::INSIDE;
		$priceGrid = self::priceGrid(Order::getSiteID($info['terminal']), $numberOfDay, $start, $end, $type_id, $parking_type);
		$priceGrid = unserialize($priceGrid['grille']);

		if (!empty($priceGrid[$site_id][$type_id->value][$parking_type->value][$numberOfDay])) {
			$total = $priceGrid[$site_id][$type_id->value][$parking_type->value][$numberOfDay];
		} else {
			// Price isn't set
			$latest = self::latestPrice($priceGrid[$site_id][$type_id->value][$parking_type->value]);
			$total = $priceGrid[$site_id][$type_id->value][$parking_type->value][$latest];
			$total += ($numberOfDay - $latest) * $priceGrid[$site_id][$type_id->value][$parking_type->value]['jour_supplementaire'];
		}
		$price['total'] = $price['total_reel'] = $total;
		if (self::isHoliday($start)) {
			$price['holiday'] = 1;
			$price['total'] += self::get_extra_price($form['options']['holiday']);
		}
		if (self::isHoliday($end)) {
			$price['holiday'] = 1;
			$price['total'] += self::get_extra_price($form['options']['holiday']);
		}
		if (HighSeason::is($start) || HighSeason::is($end)) {
			$price['high_season'] = 1;
			$price['total'] += $high_season['price'];
		}
		$nb_pax = $instance->getData($data, 'nb_pax');
		if (!empty($nb_pax) && $nb_pax > 4)
			$price['total'] += ($nb_pax - 4) * self::get_extra_price($form['options']['shuttle']);
		$price['timing'] = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
		$price['total'] += self::night_extra($start_hour, $form['options']['night_extra_charge']);
		$price['total'] += self::night_extra($end_hour, $form['options']['night_extra_charge']);
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
	public static function priceGrid(AirPort $site = AirPort::ORLY, $numberOfDay = 0, $start = NULL, $end = NULL, VehicleType $type_id = VehicleType::CAR, ParkingType $parking_type = ParkingType::OUTSIDE): array
	{
		$site_id = $site->value;
		$conn = database::connect();
		if (!$conn) {
			Logger::error("price.priceGrid", database::getError());
			throw new Exception("Database connection failed");
		}
		$start = !empty($start) ? $start : date('Y-m-d');
		$end = !empty($end) ? $end : date('Y-m-d');
		$start = DateTime::createFromFormat('Y-m-d', $start);
		$end = DateTime::createFromFormat('Y-m-d', $end);

		$priceGrid = $price = array();
		$query = "SELECT `date`, `grille_tarifaire` FROM `tbl_remplissage` WHERE (`date` BETWEEN :start AND :end)";
		$req = $conn->prepare($query);
		if (!$req->execute(['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')]))
			throw new Exception("get price grid failed");
		while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
			$priceGrid[$row['date']] = $row['grille_tarifaire'];
			$deserialized = unserialize($row['grille_tarifaire']);
			$latest = self::latestPrice($deserialized[$site_id][$type_id->value][$parking_type->value]);
			$price[$row['date']] = !empty($deserialized[$site_id][$type_id->value][$parking_type->value][$numberOfDay]) && !empty($latest) ? $deserialized[$site_id][$type_id->value][$parking_type->value][$numberOfDay] : $deserialized[$site_id][$type_id->value][$parking_type->value][$latest];
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

	private static function night_extra($hour, $night_extra_charge): int
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
		wp_enqueue_style('parking-management-booking', pkmgmt_plugin_url('modules/price/css/price.css'));
		wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', array(), '5.3.3');
	}
}

new PriceAPI();
