<?php

add_filter( 'map_meta_cap', 'pkmgmt_map_meta_cap', 10, 4 );

function pkmgmt_map_meta_cap( $caps, $cap, $user_id, $args ): array
{
	$meta_caps = array(
		'pkmgmt_edit'   => PKMGMT_ADMIN_READ_WRITE_CAPABILITY,
		'pkmgmt_read'   => PKMGMT_ADMIN_READ_CAPABILITY,
		'pkmgmt_delete' => PKMGMT_ADMIN_READ_WRITE_CAPABILITY,
		'pkmgmt_manage_option'  => PKMGMT_ADMIN_MANAGE_INTEGRATION );

	$meta_caps = apply_filters( 'pkmgmt_map_meta_cap', $meta_caps );

	$caps = array_diff( $caps, array_keys( $meta_caps ) );

	if ( isset( $meta_caps[$cap] ) )
		$caps[] = $meta_caps[$cap];

	return $caps;
}
