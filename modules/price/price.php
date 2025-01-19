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
	 * Gets the price calculation for a parking booking
	 * @param array|WP_REST_Request $data Request data containing booking details
	 * @return array Price calculation results or empty array on error
	 */
	public static function getPrice(array|WP_REST_Request $data): array
	{
		try {
			Logger::info("price.getPrice.start", [
				'request_data' => $data instanceof WP_REST_Request ? $data->get_params() : $data
			]);

			$pm = getParkingManagementInstance();
			$info = $pm->prop('info');
			$form = $pm->prop('form');
			$high_season = $pm->prop('high_season');
			$instance = new self($pm);

			// Extract and validate dates
			$start = substr($instance->getData($data, 'depart'), 0, 10);
			$start_hour = substr($instance->getData($data, 'depart'), 11, 5);
			$end = substr($instance->getData($data, 'retour'), 0, 10);
			$end_hour = substr($instance->getData($data, 'retour'), 11, 5);

			if (!$start || !$end) {
				Logger::error("price.getPrice.dates", [
					'start' => $start,
					'end' => $end,
					'error' => 'Invalid dates provided'
				]);
				return [];
			}

			Logger::info("price.getPrice.dates", [
				'start' => $start,
				'start_hour' => $start_hour,
				'end' => $end,
				'end_hour' => $end_hour
			]);

			$realNumberOfDay = $numberOfDay = Order::nbRealDay($start, $end);

			// Get lot availability
			$siteId = Order::getSiteID($info['terminal']);
			$maxLot = Booked::getMaxLot($start, $end, $siteId);
			$usedLot = Booked::usedLot($start, $end, $siteId);
			$maxUsedLot = max($usedLot);

			Logger::info("price.getPrice.lots", [
				'max_lot' => $maxLot,
				'used_lot' => $usedLot,
				'max_used_lot' => $maxUsedLot
			]);

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
				Logger::info("price.getPrice.booked", [
					'start_booked' => Booked::is($start),
					'end_booked' => Booked::is($end)
				]);
				$price['complet'] = 1;
				return $price;
			}

			// Process vehicle type
			$type_id = $instance->getData($data, 'type_id');
			$type_id = !empty($type_id) && is_numeric($type_id) ? $type_id : '1';
			if (!is_numeric($type_id)) {
				$type_id = '1';
			}
			$type_id = VehicleType::fromInt((int)$type_id);

			$site_id = Order::getSiteID($info['terminal'])->value;
			$parking_type = ParkingType::fromInt((int)$instance->getData($data, 'parking_type'));

			if ($type_id == VehicleType::TRUCK) {
				$parking_type = ParkingType::OUTSIDE;
			}
			if ($type_id == VehicleType::MOTORCYCLE) {
				$parking_type = ParkingType::INSIDE;
			}

			Logger::info("price.getPrice.types", [
				'vehicle_type' => $type_id->name,
				'parking_type' => $parking_type->name,
				'site_id' => $site_id
			]);

			// Get price grid
			try {
				$priceGrid = self::priceGrid(Order::getSiteID($info['terminal']), $numberOfDay, $start, $end, $type_id, $parking_type);
				$priceGrid = unserialize($priceGrid['grille']);
			} catch (Exception $e) {
				Logger::error("price.getPrice.priceGrid", [
					'error' => $e->getMessage()
				]);
				return [];
			}

			// Calculate base price
			if (!empty($priceGrid[$site_id][$type_id->value][$parking_type->value][$numberOfDay])) {
				$total = $priceGrid[$site_id][$type_id->value][$parking_type->value][$numberOfDay];
			} else {
				$latest = self::latestPrice($priceGrid[$site_id][$type_id->value][$parking_type->value]);
				$total = $priceGrid[$site_id][$type_id->value][$parking_type->value][$latest];
				$total += ($numberOfDay - $latest) * $priceGrid[$site_id][$type_id->value][$parking_type->value]['jour_supplementaire'];
			}

			$price['total'] = $price['total_reel'] = $total;

			// Apply holiday surcharge
			try {
				if (self::isHoliday($start)) {
					$price['holiday'] = 1;
					$price['total'] += self::get_extra_price($form['options']['holiday']);
					Logger::info("price.getPrice.holiday", ['start_date' => $start]);
				}
				if (self::isHoliday($end)) {
					$price['holiday'] = 1;
					$price['total'] += self::get_extra_price($form['options']['holiday']);
					Logger::info("price.getPrice.holiday", ['end_date' => $end]);
				}
			} catch (Exception $e) {
				Logger::error("price.getPrice.holiday", [
					'error' => $e->getMessage()
				]);
				// Continue without holiday pricing if check fails
			}

			if (HighSeason::is($start) || HighSeason::is($end)) {
				$price['high_season'] = 1;
				$price['total'] += $high_season['price'];
				Logger::info("price.getPrice.highSeason", [
					'start_high_season' => HighSeason::is($start),
					'end_high_season' => HighSeason::is($end)
				]);
			}

			$nb_pax = $instance->getData($data, 'nb_pax');
			if (!empty($nb_pax) && $nb_pax > 4) {
				$extra = ($nb_pax - 4) * self::get_extra_price($form['options']['shuttle']);
				$price['total'] += $extra;
				Logger::info("price.getPrice.passengers", [
					'passengers' => $nb_pax,
					'extra_charge' => $extra
				]);
			}

			$night_start = self::night_extra($start_hour, $form['options']['night_extra_charge']);
			$night_end = self::night_extra($end_hour, $form['options']['night_extra_charge']);
			$price['total'] += $night_start + $night_end;

			if ($night_start || $night_end) {
				Logger::info("price.getPrice.nightCharge", [
					'start_hour' => $start_hour,
					'end_hour' => $end_hour,
					'start_charge' => $night_start,
					'end_charge' => $night_end
				]);
			}

			$assurance_annulation = $instance->getData($data, 'assurance_annulation');
			if (!empty($assurance_annulation) && ($assurance_annulation == '1')) {
				$insurance_price = (int)$form['options']['cancellation_insurance']['price'];
				$price['total'] += $insurance_price;
				Logger::info("price.getPrice.insurance", ['price' => $insurance_price]);
			}

			$price['timing'] = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];

			Logger::info("price.getPrice.complete", [
				'final_price' => $price['total'],
				'calculation_time' => $price['timing']
			]);

			return $price;

		} catch (Exception $e) {
			Logger::error("price.getPrice.error", [
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString(),
				'request_data' => $data instanceof WP_REST_Request ? $data->get_params() : $data
			]);
			return [];
		}
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
