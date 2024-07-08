<?php

namespace ParkingManagement;

use Booking\Order;
use DateTime;
use Exception;
use ParkingManagement\database\database;
use ParkingManagement\interfaces\IParkingmanagement;
use ParkingManagement\interfaces\IShortcode;
use PDO;

require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "booking" . DS . "includes" . DS . "order.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "database" . DS . "database.php";

class Booked implements IShortcode, IParkingManagement
{

	private ParkingManagement $pm;

	public function __construct(ParkingManagement $pm)
	{
		$this->pm = $pm;
	}

	public function shortcode(string $type)
	{
		// TODO: Implement shortcode() method.
	}

	/**
	 * @throws Exception
	 */
	public static function getMaxLot($start, $end, $parking_id = Order::ORLY, $field = NULL): array
	{
		$conn = database::connect();
		if (!$conn)
			throw new Exception("Database connection failed");
		$field = !empty($field) ? 'employe' : 'client';
		$maxLot = array();
		$nbDays = Order::nbRealDay($start, $end);
		$start = DateTime::createFromFormat('d/m/Y', $start);
		$end = DateTime::createFromFormat('d/m/Y', $end);

		$query = "SELECT `$field` FROM `tbl_remplissage` WHERE `date` >= :du AND `date` < DATE_ADD(:au, INTERVAL 1 DAY) ORDER BY `date`";
		$req = $conn->prepare($query);
		if (!$req->execute(['du' => $start->format('Y-m-d'), 'au' => $end->format('Y-m-d')])) {
			print_log($conn->errorInfo(), false);
			throw new Exception("Error executing query");
		}
		while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
			$deserialized = unserialize($row[$field]);
			$maxLot[] = $deserialized[$parking_id];
		}
		return (count($maxLot) > 0) ? $maxLot : range(0, $nbDays);
	}

	/**
	 * @throws Exception
	 */
	public static function usedLot($start, $end = NULL, $parking_id = Order::ORLY): array
	{
		$conn = database::connect();
		if (!$conn)
			throw new Exception("Database connection failed");
		$start = DateTime::createFromFormat('d/m/Y', $start);
		$end = !empty($end) ? DateTime::createFromFormat('d/m/Y', $end) : DateTime::createFromFormat('d/m/Y', $start);

		$used = array();
		$query = "SELECT `date`, `utilisee` FROM `tbl_remplissage` WHERE `date` >= :du AND `date` <= :au";
		$req = $conn->prepare($query);
		if (!$req->execute(['du' => $start->format('Y-m-d'), 'au' => $end->format('Y-m-d')])) {
			print_log($conn->errorInfo(), false);
			throw new Exception("Error executing query");
		}
		while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
			$deserialized = unserialize($row['utilisee']);
			$used[$row['date']] = abs($deserialized[$parking_id]);
		}
		ksort($used);
		$used = array_values($used);
		return !empty($used) ? $used : array(0);
	}

	/**
	 * @throws Exception
	 */
	public static function isBooked($date): bool
	{
		$date = new DateTime($date);
		foreach (self::getBooked() as $booked)
		{
			$start = new DateTime($booked['start']);
			$end = new DateTime($booked['end']);
			if ($date >= $start && $date <= $end)
				return true;
		}
		return false;
	}

	/**
	 * @throws Exception
	 */
	public static function getBooked(): array
	{
		$pm = getParkingManagementInstance();
		if ( !$pm)
			throw new Exception("ParkingManagement instance is not configured");
		return self::bookedSorted($pm->prop('booked_dates'));
	}

	/**
	 * @throws Exception
	 */
	public static function getBookedRange(): array
	{
		$dates = array();
		foreach (self::getBooked() as $booked)
		{
			if ($booked['start'] == $booked['end']) {
				$dates[] = $booked['start'];
				continue;
			}
			$dates[] = array($booked['start'], $booked['end']);
		}
		return $dates;
	}

	/**
	 * @throws Exception
	 */
	private static function compareBooked($a, $b): int
	{
		$date1 = new DateTime($a['start']);
		$date2 = new DateTime($b['start']);
		if ($date1 == $date2)
			return 0;
		return ($date1 < $date2) ? -1 : 1;
	}

	/**
	 * @throws Exception
	 */
	private static function bookedSorted(array $booked): array
	{
		return usort($booked, function ($a, $b) {
			return self::compareBooked($a, $b);
		});
	}

}
