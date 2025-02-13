<?php

namespace ParkingManagement;

use DateTime;
use DateTimeZone;
use Exception;
use ParkingManagement\API\HighSeasonApi;
use ParkingManagement\interfaces\IParkingmanagement;
use ParkingManagement\interfaces\IShortcode;

include_once PKMGMT_PLUGIN_MODULES_DIR . DS . 'high_season' . DS . 'api.php';

class HighSeason implements IShortcode, IParkingmanagement
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

	private static function HTMLMessage(ParkingManagement $pm): string
	{
		try {
			$high_season = $pm->prop('high_season');
			if (!array_key_exists('dates', $high_season))
				return '';
			$zone = new DateTimeZone("Europe/Paris");
			$date = new DateTime( 'now', $zone);
			$hs = DatesRange::getDateRange($date->format('Y-m-d'), $high_season['dates']);
			$message = DatesRange::getMessage($hs, $pm->locale);
			if (empty($message)) {
				return '';
			}
			return Html::_alert('info', $message);

		} catch (Exception $e) {
			Logger::error("database.connect", $e->getMessage());
		}
		return '';
	}

	public static function is($date): bool
	{
		$pm = getParkingManagementInstance();
		if (!$pm)
			return false;
		$high_season = $pm->prop('high_season');
		if (!array_key_exists('dates', $high_season))
			return false;
		return DatesRange::isContain($date, $high_season['dates']);
	}

	public function setKind(string $kind): void
	{
		// TODO: Implement setKind() method.
	}
}

new HighSeasonAPI();
