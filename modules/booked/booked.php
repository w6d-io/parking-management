<?php

namespace ParkingManagement;

use Booking\Airport;
use Booking\Order;
use DateTime;
use DateTimeZone;
use Exception;
use ParkingManagement\API\BookedAPI;
use ParkingManagement\database\database;
use ParkingManagement\interfaces\IParkingmanagement;
use ParkingManagement\interfaces\IShortcode;

include_once PKMGMT_PLUGIN_MODULES_DIR . DS . 'booked' . DS . 'api.php';

class Booked implements IShortcode, IParkingManagement
{

	private ParkingManagement $pm;

	public function __construct(ParkingManagement $pm)
	{
		$this->pm = $pm;
	}

	public function shortcode(string $type): string
	{
		$this->enqueue();
		return self::HTMLMessage($this->pm);
	}

	private function enqueue(): void
	{
		wp_enqueue_style('parking-management-booked', pkmgmt_plugin_url('modules/booked/css/style.css'));
	}

	/**
	 * @throws Exception
	 */
	public static function getMaxLot($kind, $start, $end, $parking_id = Airport::ORLY, $field = NULL): array
	{
		$conn = database::connect($kind);
		if (!$conn)
			throw new Exception("Database connection failed");
		$field = !empty($field) ? 'employe' : 'client';
		$maxLot = array();
		$nbDays = Order::nbRealDay($start, $end);
		$start = DateTime::createFromFormat('Y-m-d', $start);
		$end = DateTime::createFromFormat('Y-m-d', $end);

		if (! $results = $conn->get_results(
			$conn->prepare(
				"SELECT $field FROM `tbl_remplissage` WHERE `date` >= %s AND `date` < DATE_ADD(%s, INTERVAL 1 DAY) ORDER BY `date`",
				[$start->format('Y-m-d'), $end->format('Y-m-d')]
			), ARRAY_A)
		) {
			Logger::error("booked.getMaxLot", ['errorInfo' => $conn->last_error, 'results' => $results]);
			throw new Exception("Error executing query");
		}
		foreach ($results as $row) {
			$deserialized = unserialize($row[$field]);
			$maxLot[] = $deserialized[$parking_id->value];
		}
		return (count($maxLot) > 0) ? $maxLot : range(0, $nbDays);
	}

	/**
	 * @throws Exception
	 */
	public static function usedLot($kind, $start, $end = NULL, $parking_id = Airport::ORLY): array
	{
		$conn = database::connect($kind);
		if (!$conn)
			throw new Exception("Database connection failed");
		$start = DateTime::createFromFormat('Y-m-d', $start);
		$end = !empty($end) ? DateTime::createFromFormat('Y-m-d', $end) : DateTime::createFromFormat('Y-m-d', $start);

		$used = array();
		if (! $results = $conn->get_results(
			$conn->prepare(
				"SELECT `date`, `utilisee` FROM `tbl_remplissage` WHERE `date` >= %s AND `date` <= %s",
				[$start->format('Y-m-d'), $end->format('Y-m-d')]
			), ARRAY_A)
		) {
			Logger::error("booked.usedLot", ['errorInfo' => $conn->last_error]);
			throw new Exception("Error executing query");
		}
		foreach ($results as $row) {
			$deserialized = unserialize($row['utilisee']);
			$used[$row['date']] = abs($deserialized[$parking_id->value]);
		}
		ksort($used);
		$used = array_values($used);
		return !empty($used) ? $used : array(0);
	}


	private static function HTMLMessage(ParkingManagement $pm): string
	{
		try {
			$zone = new DateTimeZone("Europe/Paris");
			$date = new DateTime('now', $zone);
			$booked = DatesRange::getDateRange($date->format('Y-m-d'), $pm->prop('booked_dates'));
			$message = DatesRange::getMessage($booked, $pm->locale);
			if (empty($message)) {
				return '';
			}
			return Html::_div(
				['class' => 'booked'],
				Html::_alert('danger', $message)
			);

		} catch (Exception $e) {
			Logger::error("booked.HTMLMessage", $e->getMessage());
		}
		return '';
	}

	public static function is($date): bool
	{
		$pm = getParkingManagementInstance();
		if (!$pm)
			return false;
		$booked_dates = $pm->prop('booked_dates');
		if (!array_key_exists('dates', $booked_dates))
			return false;
		return DatesRange::isContain($date, $booked_dates['dates']);
	}

	public function setKind(string $kind): void
	{
		// TODO: Implement setKind() method.
	}
}

new BookedAPI();
