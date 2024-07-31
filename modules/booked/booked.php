<?php

namespace ParkingManagement;

use Booking\AirPort;
use Booking\Order;
use DateTime;
use Exception;
use ParkingManagement\API\BookedAPI;
use ParkingManagement\database\database;
use ParkingManagement\interfaces\IParkingmanagement;
use ParkingManagement\interfaces\IShortcode;
use PDO;

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
		return self::HTMLMessage($this->pm);
	}

	/**
	 * @throws Exception
	 */
	public static function getMaxLot($start, $end, $parking_id = AirPort::ORLY, $field = NULL): array
	{
		$conn = database::connect();
		if (!$conn)
			throw new Exception("Database connection failed");
		$field = !empty($field) ? 'employe' : 'client';
		$maxLot = array();
		$nbDays = Order::nbRealDay($start, $end);
		$start = DateTime::createFromFormat('Y-m-d', $start);
		$end = DateTime::createFromFormat('Y-m-d', $end);

		$query = "SELECT `$field` FROM `tbl_remplissage` WHERE `date` >= :du AND `date` < DATE_ADD(:au, INTERVAL 1 DAY) ORDER BY `date`";
		$req = $conn->prepare($query);
		if (!$req->execute(['du' => $start->format('Y-m-d'), 'au' => $end->format('Y-m-d')])) {
			Logger::error("booked.getMaxLot", ['errorInfo' => $conn->errorInfo()]);
			throw new Exception("Error executing query");
		}
		while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
			$deserialized = unserialize($row[$field]);
			$maxLot[] = $deserialized[$parking_id->value];
		}
		return (count($maxLot) > 0) ? $maxLot : range(0, $nbDays);
	}

	/**
	 * @throws Exception
	 */
	public static function usedLot($start, $end = NULL, $parking_id = AirPort::ORLY): array
	{
		$conn = database::connect();
		if (!$conn)
			throw new Exception("Database connection failed");
		$start = DateTime::createFromFormat('Y-m-d', $start);
		$end = !empty($end) ? DateTime::createFromFormat('Y-m-d', $end) : DateTime::createFromFormat('Y-m-d', $start);

		$used = array();
		$query = "SELECT `date`, `utilisee` FROM `tbl_remplissage` WHERE `date` >= :du AND `date` <= :au";
		$req = $conn->prepare($query);
		if (!$req->execute(['du' => $start->format('Y-m-d'), 'au' => $end->format('Y-m-d')])) {
			Logger::error("booked.usedLot", ['errorInfo' => $conn->errorInfo()]);
			throw new Exception("Error executing query");
		}
		while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
			$deserialized = unserialize($row['utilisee']);
			$used[$row['date']] = abs($deserialized[$parking_id->value]);
		}
		ksort($used);
		$used = array_values($used);
		return !empty($used) ? $used : array(0);
	}

	private static function HTMLMessage(ParkingManagement $pm) : string
	{
		try {
			$date = new DateTime();
			$booked = DatesRange::getDateRange($date->format('Y-m-d'), $pm->prop('booked_dates'));
			$message = DatesRange::getMessage($booked, $pm->locale);
			if (empty($message)) {
				return '';
			}
			return inline_svg(PKMGMT_PLUGIN_DIR . DS . "images" . DS . "notify.svg")
				.Html::_div(
					array('class'=>'alert alert-warning d-flex align-items-center', 'role'=>'alert'),
					'<svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Warning:"><use xlink:href="#exclamation-triangle-fill"/></svg>',
					Html::_div(array(), $message)
				);

		} catch (Exception $e) {
			Logger::error("booked.HTMLMessage", $e->getMessage());
		}
		return '';
	}

}

new BookedAPI();
