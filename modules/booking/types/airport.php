<?php

namespace Booking;

use InvalidArgumentException;

enum Airport: int
{
	case ORLY = 1;
	case ROISSY = 2;
	case ZAVENTEM = 3;

	public static function fromInt(int $value): ?self
	{
		foreach (self::cases() as $case) {
			if ($case->value === $value) {
				return $case;
			}
		}
		throw new InvalidArgumentException("Invalid value for Airport enum: $value");
	}
}
