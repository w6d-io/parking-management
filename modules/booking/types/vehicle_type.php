<?php

namespace Booking;

use InvalidArgumentException;

enum VehicleType: int
{
	case CAR = 1;
	case MOTORCYCLE = 2;
	case TRUCK = 3;

	public function label(): string
	{
		return match($this) {
			self::CAR => "Car",
			self::MOTORCYCLE => "Motorcycle",
			self::TRUCK => "Truck",
		};
	}

	public function key(): string
	{
		return match($this) {
			self::CAR => "car",
			self::MOTORCYCLE => "motorcycle",
			self::TRUCK => "truck",
		};
	}

	public static function fromInt(int $value): ?self
	{
		foreach (self::cases() as $case) {
			if ($case->value === $value) {
				return $case;
			}
		}
		throw new InvalidArgumentException("Invalid value for VehicleType enum: $value");
	}
}
