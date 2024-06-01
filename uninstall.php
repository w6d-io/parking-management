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
			'post_type' => 'pkmgmt_parking_management',
			'post_status' => 'any',
		)
	);

	foreach ($posts as $post) {
		wp_delete_post($post->ID, true);
	}

	$wpdb->query( sprintf(
		"DROP TABLE IF EXISTS %s",
		$wpdb->prefix . 'parking_management'
	));
}

if ( ! defined( 'PKMGMT_VERSION' ) ) {
	pkmgmt_delete_plugin();
}
