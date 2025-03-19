<?php

namespace ParkingManagement;

enum PaymentID: int
{
	case UNKNOWN = 0;
	case MERCANET = 1;
	case PAYPAL = 2;
	case PAYPLUG = 3;
	case CASH = 4;
	case MYPOS = 5;
	case STRIPE = 12;

}
