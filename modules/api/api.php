<?php

namespace ParkingManagement\API;

use WP_REST_Controller;

include_once PKMGMT_PLUGIN_MODULES_DIR . DS . 'api' . DS . 'includes' . DS . 'zipcode.php';
include_once PKMGMT_PLUGIN_MODULES_DIR . DS . 'api' . DS . 'includes' . DS . 'vehicle.php';
include_once PKMGMT_PLUGIN_MODULES_DIR . DS . 'api' . DS . 'includes' . DS . 'destination.php';

class API extends WP_REST_Controller
{
	protected $namespace =  "pkmgmt/v1";
}

new Zipcode();
new Vehicle();
new Destination();
