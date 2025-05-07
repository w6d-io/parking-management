<?php

namespace ParkingManagement;

use Booking\ParkingType;

class controller
{
	public function __construct()
	{
		add_action('init', array($this, 'init'));
	}

	public function init(): void
	{
		$post = array_merge($_GET, $_POST);

		if (!empty($post) && array_key_exists('pkmgmt_action', $post) && (!str_starts_with($_SERVER['REQUEST_URI'], '/wp-json/pkmgmt')) ) {
			Logger::info("controller.init", $post);
			switch ($post['pkmgmt_action']) {
				case 'booking':
					$this->booking_record();
					break;
				case 'home-form':
					$this->home_form_redirect();
					break;
			}
		}
	}

	private function booking_record(): void
	{
		$pm = getParkingManagementInstance();
		if (!$pm) {
			Logger::error("controller.booking_record", "failed to find parking management instance");
			return;
		}
		$booking = new Booking($pm);
		$post = array_merge($_GET, $_POST);
		$kind = 'booking';
		if ($post['parking_type'] == ParkingType::VALET->value)
			$kind = 'valet';
		$booking->setKind($kind);
		$booking->record();
		exit(0);
	}
	private function home_form_redirect(): void
	{
		$post = array_merge($_GET, $_POST);
		$pm = getParkingManagementInstance();
		$form = $pm->prop('form');
		$kind = 'booking';
		if (isset($post['kind']) && !empty($post['kind']))
			$kind = $post['kind'];

		$page = match ($kind){
			'booking' => $form['booking_page']['value'],
			'valet' => $form['valet_page']['value'],
		};
		$url = sprintf($page."?parking_type=0&depart=%s 00:00&retour=%s 00:00", $post['depart'], $post['retour']);
		wp_redirect($url);
		exit(0);
	}
}

new controller();
