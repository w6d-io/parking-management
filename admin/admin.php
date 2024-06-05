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
		add_action( 'pkmgmt_admin_notices', array('ParkingManagement\Admin\Pages', 'notices_message'), 10, 3 );

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
					'post' => $pm ? $pm->id:0,
					'active-tab' => (int) ( $_POST['active-tab'] ?? 0 ),
				);
				if ( ! $pm ) {
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
		$id = $_POST['post_ID'] ?? '-1';
		if ( ! current_user_can('pkmgmt_edit', $id))
			wp_die(__("You are not allowed to edit this page.", 'parking-management'));

		$args = array_merge(
			$_REQUEST,
			array(
				'id' => $id,
				'title' => $_POST['post_title'] ?? null,
				'locale' => $_POST['pkmgmt-locale'] ?? null,
				'info' => $_POST['pkmgmt-info'] ?? array(),
				'database' => $_POST['pkmgmt-database'] ?? array(),
				'api' => $_POST['pkmgmt-api'] ?? array(),
				'form' => $_POST['pkmgmt-form'] ?? array(),
				'sms' => $_POST['pkmgmt-sms'] ?? array(),
			)
		);

		$args = wp_unslash( $args );

		$args['id'] = (int) $args['id'];

		if ( -1 == $args['id'] ) {
			$pm = ParkingManagement::get_template();
		} else {
			$pm = ParkingManagement::get_instance($args['id']);
		}

		if ( null !== $args['title']) {
			$pm->set_title($args['title']);
		}
		if ( null !== $args['locale']) {
			$pm->set_locale($args['locale']);
		}

		$properties = array();
		if ( null !== $args['info']) {
			$properties['info'] = $args['info'];
		}
		$pm->set_properties($properties);
		$pm->save();

		return $pm;
	}

	private function load_default(): void
	{
		$id = $_POST['post_ID'] ?? null;
		$args = array_merge(
			$_REQUEST,
			array(
				'id' => $id,
			)
		);

		$args = wp_unslash( $args );

		$args['id'] = (int) $args['id'];

		if ( 0 == $args['id'] ) {
			$pm = ParkingManagement::get_template();
		} else {
			$pm = ParkingManagement::get_instance($args['id']);
		}
	}
	private function current_action()
	{
		if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
			return $_REQUEST['action'];

		if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
			return $_REQUEST['action2'];

		return false;
	}
}

new Admin();
