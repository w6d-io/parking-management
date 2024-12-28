<?php

namespace ParkingManagement\API;

use ParkingManagement\Logger;
use ParkingManagement\ParkingManagement;

class external
{
	private array $_api;
	private ParkingManagement $pm;

	public function __construct(ParkingManagement $pm)
	{
		$this->pm = $pm;
		$this->_api = $pm->prop('data')['api'];
	}

	public static function isAPI(): bool
	{
		$pm = getParkingManagementInstance();
		if (!$pm)
			return false;
		return $pm->prop('data')['from'] == 'api';
	}

	public function get_endpoint($name): string
	{
		if ( array_key_exists($name, $this->_api['endpoint']))
			return $this->_api['endpoint'][$name];
		return '';
	}
}

