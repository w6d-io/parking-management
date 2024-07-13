<?php

namespace ParkingManagement;

use DateTime;
use Exception;
use ParkingManagement\interfaces\IParkingmanagement;
use ParkingManagement\interfaces\IShortcode;

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
			$date = new DateTime();
			$hs = DatesRange::getDateRange($date->format('Y-m-d'), $high_season['dates']);
			$message = DatesRange::getMessage($hs, $pm->locale);
			if (empty($message)) {
				return '';
			}
			return inline_svg(PKMGMT_PLUGIN_DIR . DS . "images" . DS . "notify.svg")
				. Html::_div(
					array('class' => 'alert alert-primary d-flex align-items-center', 'role' => 'alert'),
					'<svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Info:"><use xlink:href="#info-fill"/></svg>',
					Html::_div(array(), $message)
				);

		} catch (Exception $e) {
			if (array_key_exists('DEBUG', $_GET) && $_GET['DEBUG'] == '1') {
				print_log($e->getMessage(), false);
			}
		}
		return '';
	}
}
