<?php

namespace ParkingManagement;

class Controller
{
	public function __construct()
	{
		add_action('init', array($this, 'init'));
	}

	public function init(): void
	{
		$post = array_merge($_GET, $_POST);

		if (!empty($post) && array_key_exists('pkmgmt_action', $post)) {
			$post = array_merge($_POST, $_GET);
			if (array_key_exists('DEBUG', $post) && $post['DEBUG'] === '1') {
				print_log($post, false);
			}
			switch ($post['pkmgmt_action']) {
				case 'booking':
					$this->booking_record();
				case 'home-form':
					$this->home_form_redirect();

			}
		}
	}

	private function booking_record(): void
	{
		$pm = getParkingManagementInstance();
		if (!$pm) {
			console_log("controller.booking_record", "failed to find parking management instance");
			return;
		}

	}
	private function home_form_redirect(): void
	{
		$post = array_merge($_GET, $_POST);
//		print_log($post, false);

		$url = sprintf(home_url()."/reservation?depart=%s 00:00&retour=%s 00:00", $post['depart'], $post['retour']);
//		print_log($url, false);
		wp_redirect($url);
		exit(0);
	}
}

new Controller();
