<?php

namespace ParkingManagement\Admin;

use ParkingManagement\ParkingManagement;

require_once PKMGMT_PLUGIN_DIR . DS . "admin" . DS . "includes" . DS . "pages.php";


class Admin
{
	public function __construct()
	{
		add_action('admin_init',
			static function () {
				do_action('pkmgmt_admin_init');
			},
			10, 0
		);

		add_action('admin_menu', array($this, 'menu'), 9, 0);
		add_action('pkmgmt_admin_notices', array('ParkingManagement\Admin\Pages', 'notices_message'), 10, 3);

		// CSS and Javascript
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

	}

	public static function enqueue_scripts($hook_suffix): void
	{
		if (!str_contains($hook_suffix, 'pkmgmt')) {
			return;
		}
		// CSS
		wp_enqueue_style('parking-management-admin', pkmgmt_plugin_url('admin/css/styles.css'));
		wp_enqueue_style('parking-management-admin-rtl', pkmgmt_plugin_url('admin/css/styles-rtl.css'));
		wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css', array(), '6.0.0-beta3');
		wp_enqueue_script('parking-management-jquery', 'https://code.jquery.com/jquery-3.6.0.min.js');
		wp_enqueue_script('parking-management-admin', pkmgmt_plugin_url('admin/js/scripts.js'), array('parking-management-jquery'), PKMGMT_VERSION);
	}

	public function menu(): void
	{
		global $_wp_last_object_menu;

		$_wp_last_object_menu++;

		add_menu_page(
			__("Parking Management", 'parking-management'),
			__("Car park", 'parking-management'),
			"pkmgmt_read",
			'pkmgmt',
			array('ParkingManagement\Admin\Pages', 'management'),
			'dashicons-car',
			$_wp_last_object_menu
		);

		$management = add_submenu_page('pkmgmt',
			__('Configure Site', 'parking-management'),
			__('Configure Site', 'parking-management'),
			"pkmgmt_read",
			"pkmgmt",
			array('ParkingManagement\Admin\Pages', 'management')
		);

		add_action("load-" . $management,
			array(&$this, "load"),
			10,
			0);


	}

	public function load(): void
	{
		switch ($this->current_action()) {
			case "save":
				$pm = $this->load_save();
				$query = array(
					'active-tab' => (int)($_POST['active-tab'] ?? 0),
				);
				if (!$pm) {
					$query['message'] = 'failed';
				} else {
					$query['message'] = 'saved';
				}
				$redirect_to = add_query_arg($query, menu_page_url('pkmgmt', false));
				wp_safe_redirect($redirect_to);
				exit();
			default:
				$this->load_default();
				break;
		}
	}

	private function load_save(): false|ParkingManagement
	{
		$id = get_post_id_by_post_type(ParkingManagement::post_type);
		if (!current_user_can('pkmgmt_edit', $id))
			wp_die(__("You are not allowed to edit this page.", 'parking-management'));

		$args = array_merge(
			$_REQUEST,
			array(
				'id' => $id,
				'title' => $_POST['pkmgmt-title'] ?? null,
				'name' => $_POST['pkmgmt-name'] ?? null,
				'locale' => $_POST['pkmgmt-locale'] ?? null,
				'info' => $_POST['pkmgmt-info'] ?? array(),
				'database' => $_POST['pkmgmt-database'] ?? array(),
				'api' => $_POST['pkmgmt-api'] ?? array(),
				'form' => $_POST['pkmgmt-form'] ?? array(),
				'sms' => $_POST['pkmgmt-sms'] ?? array(),
			)
		);
		$args = wp_unslash($args);

		$args['id'] = (int)$args['id'];

		if (-1 == $args['id']) {
			$pm = ParkingManagement::get_template();
		} else {
			$pm = ParkingManagement::get_instance($args['id']);
		}


		if (null !== $args['title']) {
			$pm->set_title($args['title']);
		}
		if (null !== $args['name']) {
			$pm->set_name($args['name']);
		}
		if (null !== $args['locale']) {
			$pm->set_locale($args['locale']);
		}

		$properties = array();
		if (null !== $args['info']) {
			$properties['info'] = $args['info'];
		}
		$pm->set_properties($properties);

		$pm->save();
		return $pm;
	}

	private function load_default(): void
	{
		$id = get_post_id_by_post_type(ParkingManagement::post_type);
		$args = array_merge(
			array(
				'id' => $id,
			),
			$_REQUEST
		);

		$args = wp_unslash($args);

		$args['id'] = (int)$args['id'];

		if (0 == $args['id']) {
			ParkingManagement::get_template();
		} else {
			ParkingManagement::get_instance($args['id']);
		}
		self::meta_box();

	}

	private function current_action()
	{
		if (isset($_REQUEST['action']) && -1 != $_REQUEST['action'])
			return $_REQUEST['action'];

		if (isset($_REQUEST['action2']) && -1 != $_REQUEST['action2'])
			return $_REQUEST['action2'];

		return false;
	}

	private static function meta_box(): void
	{
		// MetaBox
		add_meta_box(
			'info',
			__('Information', 'parking-management'),
			array('ParkingManagement\Admin\Pages', 'info_box'),
			null,
			'info',
			'core'
		);

	}
}

new Admin();
