<?php

namespace Booking;

use InvalidArgumentException;

enum ParkingType: int
{
	case OUTSIDE = 0;
	case INSIDE = 1;
	case VALET = 2;

	public static function fromInt(int $value): ?self
	{
		foreach (self::cases() as $case) {
			if ($case->value === $value) {
				return $case;
			}
		}
		throw new InvalidArgumentException("Invalid value for ParkingType enum: $value");
	}
}
