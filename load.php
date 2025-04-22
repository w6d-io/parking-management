<?php
//defined('_PKMGMT') or die('Restricted access');

require_once PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "logs.php";
require_once PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "interfaces.php";
require_once PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "capabilities.php";
require_once PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "dates_range.php";
require_once PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "functions.php";
require_once PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "html.php";
require_once PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "l10n.php";
require_once PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "parking-management.php";
require_once PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "shortcode.php";
require_once PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "template.php";
require_once PKMGMT_PLUGIN_DIR . DS . "vendor" . DS . "autoload.php";


if (is_admin()) {
	require_once PKMGMT_PLUGIN_DIR . DS . "admin" . DS . "admin.php";
} else {
	require_once PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "controller.php";
}

use ParkingManagement\Logger;
use ParkingManagement\ParkingManagement;
use ParkingManagement\Template;

class PKMGMT
{

	/**
	 * Loads modules from the modules directory.
	 */
	public static function load_modules(): void
	{
		self::load_module('api');
		self::load_module('payment');
		self::load_module('booked');
		self::load_module('booking');
		self::load_module('database');
		self::load_module('price');
		self::load_module('high_season');
		self::load_module('notification');
	}

	/**
	 * Loads the specified module.
	 *
	 * @param string $mod Name of module.
	 * @return bool True on success, false on failure.
	 */
	protected static function load_module(string $mod): bool
	{
		return pkmgmt_include_module_file($mod . '/' . $mod . '.php')
			|| pkmgmt_include_module_file($mod . '.php');
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
	public static function get_option(string $name, bool $default_value = false): mixed
	{
		$option = get_option('pkmgmt');

		if (false === $option) {
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
	public static function update_option(string $name, mixed $value): void
	{
		$old_option = get_option('pkmgmt');
		$old_option = (false === $old_option) ? array() : (array)$old_option;

		update_option('pkmgmt',
			array_merge($old_option, array($name => $value))
		);

		do_action('pkmgmt_update_option', $name, $value, $old_option);
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
}


add_action('init', 'pkmgmt_init', 2);
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
function pkmgmt_upgrade(): void
{
	$old_version = PKMGMT::get_option('version', '0');
	$new_version = PKMGMT_VERSION;

	if (version_compare($old_version, $new_version, '==')) {
		return;
	}
	if (version_compare($old_version, '3.0.0', '<')) {
		pkmgmt_migrate_2_to_3();
	}
	if (version_compare($old_version, '3.1.0', '<')) {
		pkmgmt_migrate_3_0_to_3_1();
	}
	if (version_compare($old_version, '3.6.0', '<')) {
		pkmgmt_migrate_3_1_to_3_6();
	}
	if (version_compare($old_version, '3.7.0', '<')) {
		pkmgmt_migrate_3_6_to_3_7();
	}
	if (version_compare($old_version, '4.0.0', '<')) {
		pkmgmt_migrate_3_7_to_4_0();
	}
	if (version_compare($old_version, '4.2.1', '<')) {
		pkmgmt_migrate_to_4_2_1();
	}
	if (version_compare($old_version, '4.3.0', '<')) {
		pkmgmt_migrate_to_4_3_0();
	}
	if (version_compare($old_version, '4.3.5', '<')) {
		pkmgmt_migrate_to_4_3_5();
	}
	if (version_compare($old_version, '4.5.0', '<')) {
		pkmgmt_migrate_to_4_5_0();
	}

	do_action('pkmgmt_upgrade', $old_version, $new_version);
	PKMGMT::update_option('version', $new_version);
}

function pkmgmt_migrate_2_to_3(): void
{
	global $wpdb;
	$post_id = get_post_id_by_post_type('pkmgmt');
	if (!$post_id)
		return;
	$post = get_post($post_id);
	wp_update_post(array(
		'ID' => $post_id,
		'post_type' => ParkingManagement::post_type,
		'post_status' => 'publish',
		'post_title' => $post->post_title,
		'post_name' => $post->post_name,
	));
	$pm = ParkingManagement::get_template([
		'title' => $post->post_title,
		'name' => $post->post_name,
	]);
	$pm->id = $post_id;
	$properties = $pm->get_properties();
	if (metadata_exists('post', $post_id, '_info')) {
		$prop = get_post_meta($post_id, '_info', true);
		$properties['info']['address'] = $prop['adresse'];
		$properties['info']['mobile'] = $prop['telephone'];
		$properties['info']['RCS'] = $prop['RCS'];
		$properties['info']['email'] = $prop['email'];
		$properties['info']['terminal'] = $prop['terminal'];
		$properties['info']['vehicle_type']['car'] = '1';
		$properties['info']['vehicle_type']['truck'] = '1';
		$properties['info']['type']['ext'] = $prop['type']['ext'];
		$properties['info']['type']['int'] = $prop['type']['int'];
		$properties['form']['booking']['dialog_confirmation'] = $prop['gestion']['autovalid'] === '1' ? '0' : '1';
		$properties['form']['booking']['terms_and_conditions'] = $prop['cg'];
		$properties['form']['booking']['options']['holiday']['enabled'] = $prop['frais']['ferie'];
		$properties['form']['booking']['options']['night_extra_charge']['enabled'] = $prop['frais']['nuit'];
		$properties['form']['indicative'] = $prop['indicatif'];
	}
	if (metadata_exists('post', $post_id, '_database')) {
		$prop = get_post_meta($post_id, '_database', true);
		$properties['database']['name'] = $prop['dbname'];
		$properties['database']['host'] = $prop['dbhost'];
		$properties['database']['port'] = $prop['dbport'];
		$properties['database']['user'] = $prop['dbuser'];
		$properties['database']['password'] = $prop['dbpassword'];

	}
	if (metadata_exists('post', $post_id, '_divers')) {
		$prop = get_post_meta($post_id, '_divers', true);
		if (strtoupper($prop['smstype']) === 'OVH') {
			$properties['notification']['sms']['type'] = 'OVH';
			$properties['notification']['sms']['user'] = $prop['smsuser'];
			$properties['notification']['sms']['password'] = $prop['smspasswd'];
			$properties['notification']['sms']['sender'] = $prop['smssender'];
			$properties['notification']['sms']['template'] = $prop['smsmessage'];
		}
	}
	if (metadata_exists('post', $post_id, '_response')) {
		$prop = get_post_meta($post_id, '_response', true);
		$properties['notification']['mail']['templates']['confirmation']['value'] = $prop;
	}

	$pm->set_properties($properties);
	try {
		$pm->save();
		foreach (['_info', '_database', '_divers', '_response', '_paypal', '_template', '_name', '_locale'] as $prop) {
			delete_post_meta($pm->id(), $prop);
		};
	} catch (Exception $e) {
		error_log("migration.save: " . $e->getMessage());
	}
}

function pkmgmt_migrate_3_0_to_3_1(): void
{
	$pm = getParkingManagementInstance();
	if (!$pm)
		return;
	$props = $pm->get_properties();
	$props['notification'] = Template::get_default('notification');
	$sms = $pm->retrieve_property('sms');
	if (!empty($sms)) {
		$props['notification']['sms'] = $sms;
		delete_post_meta($pm->id, 'pkmgmt_sms');
	}
	$mail = $pm->retrieve_property('mail');
	if (!empty($mail)) {
		$props['notification']['mail']['host'] = $mail['host'];
		$props['notification']['mail']['login'] = $mail['login'];
		$props['notification']['mail']['password'] = $mail['password'];
		$props['notification']['mail']['sender'] = $mail['sender'];
		delete_post_meta($pm->id, 'pkmgmt_mail');
	}

	$pm->set_properties($props);
	try {
		$pm->save();
	} catch (Exception $e) {
		Logger::error("migrate.3.0.to.3.1", $e->getMessage());
	}
}

function pkmgmt_migrate_3_1_to_3_6(): void
{
	$pm = getParkingManagementInstance();
	if (!$pm)
		return;
	$props = $pm->get_properties();
	$props['payment']['providers']['mypos']['redirect-to-provider'] = '0';
	$props['form']['payment'] = '';
	$props['form']['valet'] = [
		'validation_page' => [
			'title' => 'Validation Page',
			'value' => ''
		],
		'payment' => ''
	];
	$props['form']['booking_page'] = [
		'title' => 'Booking Page',
		'value' => ''
	];
	$pm->set_properties($props);
	try {
		$pm->save();
	} catch (Exception $e) {
		Logger::error("migrate.3.1.to.3.6", $e->getMessage());
	}
}

function pkmgmt_migrate_3_6_to_3_7(): void
{
	$pm = getParkingManagementInstance();
	if (!$pm)
		return;
	$props = $pm->get_properties();
	unset($props['payment']['providers']['mypos']['redirect-to-provider']);
	unset($props['payment']['providers']['payplug']['redirect-to-provider']);
	unset($props['payment']['providers']['paypal']['redirect-to-provider']);
	$props['form']['redirect-to-provider'] = '0';
	$props['form']['valet']['database'] = [
		'name' => "",
		'host' => "",
		'port' => "",
		'user' => "",
		'password' => ""
	];
	$props['form']['valet']['redirect-to-provider'] = '0';
	$props['notification']['valet'] = [
		'host' => [
			'title' => 'Host',
			'type' => 'text',
			'value' => ''
		],
		'login' => [
			'title' => 'Login',
			'type' => 'text',
			'value' => ''
		],
		'password' => [
			'title' => 'Password',
			'type' => 'password',
			'value' => ''
		],
		'sender' => [
			'title' => 'Sender',
			'type' => 'email',
			'value' => ''
		],
		'templates' => [
			'confirmation' => [
				'title' => 'Confirmation',
				'type' => 'textarea',
				'value' => ''
			],
			'cancellation' => [
				'title' => 'Cancellation',
				'type' => 'textarea',
				'value' => ''
			],
		]
	];
	$pm->set_properties($props);
	try {
		$pm->save();
	} catch (Exception $e) {
		Logger::error("migrate.3.1.to.3.6", $e->getMessage());
	}
}

function pkmgmt_migrate_3_7_to_4_0(): void
{
	$pm = getParkingManagementInstance();
	if (!$pm)
		return;
	$props = $pm->get_properties();
	$props['booking'] = Template::get_default('booking');
	$props['booking']['validation_page'] = $props['form']['validation_page'];
	$props['booking']['options'] = $props['form']['booking'];
	$props['booking']['database'] = $props['database'];
	$props['booking']['payment'] = [
		'valid-on-payment' => $props['payment']['valid-booking-on-payment'],
		'redirect-to-provider' => $props['form']['redirect-to-provider'],
		'name' => 'payplug',
		'enabled' => $props['payment']['providers']['payplug']['enabled'],
		'active-test' => $props['payment']['providers']['payplug']['active-test'],
		'properties' => [
			'payplug' => $props['payment']['providers']['payplug']['properties'],
			'mypos' => $props['payment']['providers']['mypos']['properties'],
			'paypal' => $props['payment']['providers']['paypal']['properties'],
		]
	];
	$props['booking']['mail_templates'] = [
		'confirmation' => [
			'title' => 'Confirmation',
			'type' => 'textarea',
			'value' => $props['notification']['mail']['templates']['confirmation'],
		],
		'cancellation' => [
			'title' => 'Cancellation',
			'type' => 'textarea',
			'value' => $props['notification']['mail']['templates']['cancellation'],
		]
	];
	$props['booking']['sms_template'] = $props['notification']['sms']['template'];

	$props['valet'] = Template::get_default('valet');
	$props['valet']['validation_page'] = $props['form']['valet']['validation_page'];
	$props['valet']['options'] = $props['form']['booking'];
	$props['valet']['database'] = $props['database'];
	$props['valet']['payment'] = [
		'valid-on-payment' => $props['payment']['valid-valet-on-payment'],
		'redirect-to-provider' => $props['form']['valet']['redirect-to-provider'],
		'name' => 'payplug',
		'enabled' => $props['payment']['providers']['payplug']['enabled'],
		'active-test' => $props['payment']['providers']['payplug']['active-test'],
		'properties' => [
			'payplug' => $props['payment']['providers']['payplug']['properties'],
			'mypos' => $props['payment']['providers']['mypos']['properties'],
			'paypal' => $props['payment']['providers']['paypal']['properties'],
		]
	];
	$props['valet']['mail_templates'] = [
		'confirmation' => [
			'title' => 'Confirmation',
			'type' => 'textarea',
			'value' => $props['notification']['valet']['templates']['confirmation'],
		],
		'cancellation' => [
			'title' => 'Cancellation',
			'type' => 'textarea',
			'value' => $props['notification']['valet']['templates']['cancellation'],
		]
	];
	$props['valet']['sms_template'] = $props['notification']['sms']['template'];
	$props['form']['valet_page'] = [
		'title' => 'Valet Page',
		'value' => ''
	];
	unset($props['payment']);
	unset($props['database']);
	unset($props['form']['booking']);
	unset($props['form']['payment']);
	unset($props['form']['redirect-to-provider']);
	unset($props['form']['valet']);
	unset($props['form']['validation_page']);
	unset($props['notification']['mail']['templates']);
	unset($props['notification']['valet']['templates']);
	unset($props['notification']['sms']['template']);
	$pm->set_properties($props);
	try {
		$pm->save();
	} catch (Exception $e) {
		Logger::error("migrate.3.7.to.4.0", $e->getMessage());
	}

}

function pkmgmt_migrate_to_4_2_1(): void
{
	$pm = getParkingManagementInstance();
	if (!$pm)
		return;
	$props = $pm->get_properties();
	if (!array_key_exists('valet', $props['form']['type']))
		$props['form']['type']['valet'] = 0;
	$pm->set_properties($props);
	try {
		$pm->save();
	} catch (Exception $e) {
		Logger::error("migrate.to.4.2.1", $e->getMessage());
	}
}

function pkmgmt_migrate_to_4_3_0(): void
{
	try {
		$pm = getParkingManagementInstance();
		if (!$pm)
			return;
		$payment = Template::payment_properties();
		$props = $pm->get_properties();
		$props['booking']['payment']['properties']['stripe'] = $payment['stripe'];
		$props['valet']['payment']['properties']['stripe'] = $payment['stripe'];
		unset($props['booking']['payment']['properties']['paypal']);
		unset($props['valet']['payment']['properties']['paypal']);

		$pm->set_properties($props);
		$pm->save();
	} catch (Exception $e) {
		Logger::error("migrate.to.4.3.0", $e->getMessage());
	}
}

function pkmgmt_migrate_to_4_3_5(): void
{
	try {
		$pm = getParkingManagementInstance();
		if (!$pm)
			return;
		$payment = Template::payment_properties();
		$props = $pm->get_properties();
		$props['booking']['payment']['properties']['stripe']['webhook_secret_key'] = $payment['stripe']['webhook_secret_key'];
		$props['valet']['payment']['properties']['stripe']['webhook_secret_key'] = $payment['stripe']['webhook_secret_key'];

		$pm->set_properties($props);
		$pm->save();
	} catch (Exception $e) {
		Logger::error("migrate.to.4.3.5", $e->getMessage());
	}
}

function pkmgmt_migrate_to_4_5_0(): void
{
	try {
		$pm = getParkingManagementInstance();
		if (!$pm)
			return;
		$payment = Template::payment_properties();
		$props = $pm->get_properties();
		$props['form']['options']['keep_keys'] =  array(
			'enabled' => "0",
			'title' => 'Keep keys',
			'price' => 0,
			'label' => 'I will keep my keep for %s €'
		);
		$props['form']['options']['ev_charging'] = [
			'enabled' => "0",
			'title' => 'EV Charging',
			'price' => 0,
			'label' => 'Electric Vehicle charging for %s €'
		];
		$props['form']['options']['cancellation_insurance']['label'] = 'I hereby subscribe to the cancellation insurance for %s €';

		$pm->set_properties($props);
		$pm->save();
	} catch (Exception $e) {
		Logger::error("migrate.to.4.5.0", $e->getMessage());
	}
}

add_action('activate_' . PKMGMT_PLUGIN_BASENAME, 'pkmgmt_install', 9, 0);


/**
 * Callback tied to plugin activation action hook. Attempts to create
 * initial user dataset.
 * @throws Exception
 */
function pkmgmt_install(): void
{
	if (get_option('pkmgmt')) {
		return;
	}
	ParkingManagement::register_post_type();
	pkmgmt_upgrade();

	if (get_posts(array('post_type' => ParkingManagement::post_type))) {
		return;
	}

	$pm = ParkingManagement::get_template(
		array(
			'title' => sprintf('Parking %d', 1),
			'name' => sprintf('Parking %d', 1)
		)
	);
	$pm->save();

	PKMGMT::update_option('bulk_validate',
		array(
			'timestamp' => time(),
			'version' => PKMGMT_VERSION,
			'count_valid' => 1,
			'count_invalid' => 0,
		)
	);
}

function enable_svg_upload($mimes)
{
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
}

add_filter('upload_mimes', 'enable_svg_upload');
