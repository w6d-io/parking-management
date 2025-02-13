<?php

namespace Booking;

use InvalidArgumentException;

enum VehicleType: int
{
	case CAR = 1;
	case MOTORCYCLE = 2;
	case TRUCK = 3;

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
