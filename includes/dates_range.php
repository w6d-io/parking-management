<?php

namespace ParkingManagement;

use DateTime;
use DateTimeZone;
use Exception;
use IntlDateFormatter;

class DatesRange
{

	public static function getDateRange(string $date, array $datesRange): array
	{
		try {
			$datesRange = self::datesSorted($datesRange);
			$date = new DateTime($date);
			foreach ($datesRange as $dateRange) {
				$start = new DateTime($dateRange['start']);
				$end = new DateTime($dateRange['end']);
				if ($date >= $start && $date <= $end)
					return $dateRange;
			}
			foreach ($datesRange as $dateRange) {
				$start = new DateTime($dateRange['start']);
				if ($date < $start)
					return $dateRange;
			}
		} catch (Exception $e) {
			Logger::error("datesRange.getDateRange", $e->getMessage());
		}
		return array();
	}

	public static function getDatesRangeSorted(array $dates): array
	{
		return self::datesSorted($dates);
	}

	public static function datesSorted(array $dateRanges): array
	{
		usort($dateRanges, function ($a, $b) {
			return self::compareDate($a, $b);
		});
		return $dateRanges;
	}

	public static function getDatesRangeAPI(array $dates): array
	{
		$datesRange = array();
		$sorted = self::getDatesRangeSorted($dates);
		foreach ($sorted as $date) {
			if ($date['start'] == $date['end']) {
				$datesRange[] = $date['start'];
				continue;
			}
			$datesRange[] = array($date['start'], $date['end']);
		}
		return $datesRange;
	}

	public static function isContain(string $search, array $dates): bool
	{
		try {
			$search = new DateTime($search);
			$sorted = self::getDatesRangeSorted($dates);
			foreach ($sorted as $date) {
				$start = new DateTime($date['start']);
				$end = new DateTime($date['end']);
				if ($search >= $start && $search <= $end)
					return true;
			}
		} catch (Exception $e) {
			Logger::error("DatesRange.isContain", $e->getMessage());
		}
		return false;
	}

	public static function getMessage(array $date, $locale = 'fr_FR'): string
	{
		if (empty($date) || empty($date['message']))
			return "";

		$date['start'] = self::convertDate($date['start']);
		$date['end'] = self::convertDate($date['end']);
		return replacePlaceholders($date['message'], $date);
	}

	public static function convertDate(string $date, $fromFormat = 'Y-m-d', $toFormat= 'd MMMM y', $locale = 'fr_FR'): string
	{
		$formatter = new IntlDateFormatter(
			$locale,
			IntlDateFormatter::FULL,
			IntlDateFormatter::FULL,
			'Europe/Paris',
			IntlDateFormatter::GREGORIAN,
			$toFormat,
		);
		$zone = new DateTimeZone("Europe/Paris");
		$date = DateTime::createFromFormat($fromFormat, $date, $zone);
		return $formatter->format($date);
	}

	private static function compareDate($a, $b): int
	{
		try {
			$date1 = new DateTime($a['start']);
			$date2 = new DateTime($b['start']);
			if ($date1 === $date2)
				return 0;
			return ($date1 < $date2) ? -1 : 1;
		} catch (Exception $e) {
			Logger::error("DatesRange.compareDate", $e->getMessage());
			return 0;
		}
	}
}
