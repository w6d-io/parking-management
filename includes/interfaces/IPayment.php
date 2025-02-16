<?php

namespace ParkingManagement\interfaces;

interface IPayment
{
	public function __construct(array $config, string $kind, int $order_id);
	public function pay(): string;
	public function redirect(): void;
}
