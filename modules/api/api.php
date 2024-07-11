<?php

namespace ParkingManagement;

use ParkingManagement\API\Destination;
use ParkingManagement\API\Prices;
use ParkingManagement\API\Vehicle;
use ParkingManagement\API\Zipcode;

include_once PKMGMT_PLUGIN_MODULES_DIR . DS . 'api' . DS . 'includes' . DS . 'prices.php';
include_once PKMGMT_PLUGIN_MODULES_DIR . DS . 'api' . DS . 'includes' . DS . 'zipcode.php';
include_once PKMGMT_PLUGIN_MODULES_DIR . DS . 'api' . DS . 'includes' . DS . 'vehicle.php';
include_once PKMGMT_PLUGIN_MODULES_DIR . DS . 'api' . DS . 'includes' . DS . 'destination.php';

$namespace = "/pkmgmt/v";
$version = "1";

$instances = array(
	new Prices($namespace, $version),
	new Zipcode($namespace, $version),
	new Vehicle($namespace, $version),
	new Destination($namespace, $version),
);
foreach ($instances as $instance) {
	add_action('rest_api_init', array($instance, 'register_routes'));
}
