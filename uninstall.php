<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
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
		foreach (array('info', 'database', 'api', 'payment', 'form', 'full_dates', 'sms', 'response') as $meta_key) {
			delete_post_meta($post->ID, 'pkmgmt_'.$meta_key);
		}
	}


	$wpdb->query( sprintf(
		"DROP TABLE IF EXISTS %s",
		$wpdb->prefix . 'parking_management'
	));
}

if ( ! defined( 'PKMGMT_VERSION' ) ) {
	pkmgmt_delete_plugin();
}
