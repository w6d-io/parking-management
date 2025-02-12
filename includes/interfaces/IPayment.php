<?php

namespace ParkingManagement\interfaces;

use ParkingManagement\ParkingManagement;

interface IPayment
{
	public function __construct(ParkingManagement $pm);
	public function pay(string $kind): string;
	public function redirect(string $kind): void;
}
