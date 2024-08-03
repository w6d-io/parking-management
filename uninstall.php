<?php

use ParkingManagement\ParkingManagement;

if (!defined('PKMGMT_PLUGIN'))
	define('PKMGMT_PLUGIN', __FILE__);
if (!defined('DS'))
	define('DS', DIRECTORY_SEPARATOR);

require_once __DIR__ . DS .'define.php';
require_once PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "parking-management.php";

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit();
}

function pkmgmt_delete_plugin(): void
{
	global $wpdb;

	delete_option('pkmgmt');

	$posts = get_posts(
		array(
			'numberposts' => -1,
			'post_type' => 'parking_management',
			'post_status' => 'any',
		)
	);

	foreach ($posts as $post) {
		wp_delete_post($post->ID, true);
		foreach (ParkingManagement::properties_available as $prop => $config) {
			delete_post_meta($post->ID, 'pkmgmt_' . $prop);
		}
	}

//	$wpdb->query(sprintf(
//		"DROP TABLE IF EXISTS %s",
//		$wpdb->prefix . 'parking_management'
//	));
}

if (defined('PKMGMT_VERSION')) {
	pkmgmt_delete_plugin();
}
