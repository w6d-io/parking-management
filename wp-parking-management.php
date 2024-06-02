<?php
/**
 * Plugin Name: Parking management
 * Description: Plugin to manage park booking
 * Author: David ALEXANDRE
 * License: GPL2 license
 * URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Version: 3.0.0
 * Requires at least: 6.3
 * Requires PHP: 7.4
 */

if ( ! defined( '_PKMGMT' ) )
{
	define( 'DS', DIRECTORY_SEPARATOR );

	define( '_PKMGMT', 1 );
	define( 'PKMGMT_VERSION', '3.0.0' );
	define( 'PKMGMT_REQUIRED_WP_VERSION', '6.3' );
	define( 'PKMGMT_TEXT_DOMAIN', 'parking-management' );
	define( 'PKMGMT_PLUGIN', __FILE__ );
	define( 'PKMGMT_PLUGIN_BASENAME', plugin_basename( PKMGMT_PLUGIN ) );
	define( 'PKMGMT_PLUGIN_NAME', trim( dirname( PKMGMT_PLUGIN_BASENAME ), DS ) );
	define( 'PKMGMT_PLUGIN_DIR', untrailingslashit( dirname( PKMGMT_PLUGIN ) ) );
	define( 'PKMGMT_PLUGIN_MODULES_DIR', PKMGMT_PLUGIN_DIR . DS . 'modules' );

	define( 'PKMGMT_LOAD_JS', true );
	define( 'PKMGMT_LOAD_CSS', true );
	define( 'PKMGMT_USE_PIPE', true );

	define( 'PKMGMT_ADMIN_READ_CAPABILITY', 'edit_posts' );
	define( 'PKMGMT_ADMIN_READ_WRITE_CAPABILITY', 'publish_pages' );
	define( 'PKMGMT_ADMIN_MANAGE_INTEGRATION', 'manage_options' );
	define( 'PKMGMT_VERIFY_NONCE', true );

	define( 'PKMGMT_PLUGIN_URL', untrailingslashit( plugins_url( '', PKMGMT_PLUGIN ) ) );

	define( 'PKMGMT_PLUGIN_INCLUDES_DIR', PKMGMT_PLUGIN_DIR . DS . 'includes' );
	define( 'PKMGMT_LANGUAGES_DIR', PKMGMT_PLUGIN_DIR . DS . 'languages' );
	define( 'PKMGMT_PLUGIN_TEMPLATES', PKMGMT_PLUGIN_DIR.DS."templates");
}

require_once(PKMGMT_PLUGIN_DIR . DS . "load.php");

