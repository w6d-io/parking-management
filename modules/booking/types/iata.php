<?php

namespace Booking;

use InvalidArgumentException;

enum IATA: int
{
	case ORY = 1;
	case CDG = 2;

	case BRU = 3;

	public static function fromAirPort(Airport $value): ?self
	{
		foreach (self::cases() as $case) {
			if ($case->value === $value->value) {
				return $case;
			}
		}
		throw new InvalidArgumentException("Invalid value for IATA enum: $value");

	}
}
