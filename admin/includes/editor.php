<?php

namespace ParkingManagement\Admin;

use ParkingManagement\ParkingManagement;

class Editor {

	private $parking_management;

	public function __construct(ParkingManagement $parking_management) {
		$this->parking_management = $parking_management;
	}
}
