<?php

namespace ParkingManagement\interfaces;
interface IShortcode
{
	public function shortcode(string $type): string;

	public function setKind(string $kind): void;
}
