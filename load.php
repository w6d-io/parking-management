<?php
//defined('_PKMGMT') or die('Restricted access');

require_once PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "functions.php";
require_once(PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "parking-management.php");

if (is_admin()) {
	require_once PKMGMT_PLUGIN_DIR . DS . "admin" . DS . "admin.php";
} else {
	require_once PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "controller.php";
}
use ParkingManagement\ParkingManagement;

class PKMGMT {

	/**
	 * Loads modules from the modules directory.
	 */
	public static function load_modules(): void
	{
		self::load_module( 'book' );
	}

	/**
	 * Loads the specified module.
	 *
	 * @param string $mod Name of module.
	 * @return bool True on success, false on failure.
	 */
	protected static function load_module(string $mod ): bool
	{
		return pkmgmt_include_module_file( $mod . '/' . $mod . '.php' )
			|| pkmgmt_include_module_file( $mod . '.php' );
	}

	/**
	 * Retrieves a named entry from the option array of Parking Management.
	 *
	 * @param string $name Array item key.
	 * @param bool $default_value Optional. Default value to return if the entry
	 *                             does not exist. Default false.
	 * @return mixed Array value tied to the $name key. If nothing found,
	 *               the $default_value value will be returned.
	 */
	public static function get_option(string $name, bool $default_value = false ): mixed
	{
		$option = get_option( 'pkmgmt' );

		if ( false === $option ) {
			return $default_value;
		}

		return $option[$name] ?? $default_value;
	}


	/**
	 * Update an entry value on the option array of Parking Management.
	 *
	 * @param string $name Array item key.
	 * @param mixed $value Option value.
	 */
	public static function update_option(string $name, mixed $value ): void
	{
		$old_option = get_option( 'pkmgmt' );
		$old_option = ( false === $old_option ) ? array() : (array) $old_option;

		update_option( 'pkmgmt',
			array_merge( $old_option, array( $name => $value ) )
		);

		do_action( 'pkmgmt_update_option', $name, $value, $old_option );
	}
}

add_action('plugins_loaded', 'pkmgmt', 10, 0);

/**
 * Loads modules and registers WordPress shortcodes.
 */
function pkmgmt(): void
{
	PKMGMT::load_modules();

	add_shortcode('parking-management', 'pkmgmt_parking_management_shortcode_func');
//	add_shortcode('parking-management-home', 'pkmgmt_parking_management_form_tag_func');
//	add_shortcode('parking-management-paypal', 'pkmgmt_parking_management_payment_func');
//	add_shortcode('parking-management-payplug', 'pkmgmt_parking_management_payment_func');
//	add_shortcode('parking-management-mypos', 'pkmgmt_parking_management_payment_func');
//	add_shortcode('parking-management-mypos-payment', 'pkmgmt_parking_management_payment_func');
}


add_action( 'init', 'pkmgmt_init', 2);
/**
 * Registers post types for parking management.
 */
function pkmgmt_init(): void
{
	pkmgmt_get_request_uri();
	pkmgmt_register_post_type();

	do_action('pkmgmt_init');
}

add_action('admin_init', 'pkmgmt_upgrade', 10, 0);
function pkmgmt_upgrade(): void {
	$old_version = PKMGMT::get_option('version', '0');
	$new_version = PKMGMT_VERSION;

	if (version_compare($old_version, $new_version, '==')) {
		return;
	}

	do_action('pkmgmt_upgrade', $old_version, $new_version);
	PKMGMT::update_option('version', $new_version);
}

add_action( 'activate_' . PKMGMT_PLUGIN_BASENAME, 'pkmgmt_install', 10, 0 );


/**
 * Callback tied to plugin activation action hook. Attempts to create
 * initial user dataset.
 */
function pkmgmt_install(): void
{
	if (get_option( 'pkmgmt' )) {
		return;
	}
	ParkingManagement::register_post_type();
	pkmgmt_upgrade();

	if ( get_posts( array( 'post_type' => ParkingManagement::post_type ) ) ) {
		return;
	}

	$pm = ParkingManagement::get_template(
		array(
			'title' => sprintf( __( 'Parking %d', 'parking-management' ), 1),
			'name' => sprintf( __( 'Parking %d', 'parking-management' ), 1)
		)
	);

	$pm->save();

	PKMGMT::update_option( 'bulk_validate',
		array(
			'timestamp' => time(),
			'version' => PKMGMT_VERSION,
			'count_valid' => 1,
			'count_invalid' => 0,
		)
	);
}
