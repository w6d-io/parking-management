<?php

namespace Booking;

enum OrderStatus: int
{
	case PENDING = 0;
	case CONFIRMED = 1;
	case PAID = 2;
	case COMPLETED = 3;
}
