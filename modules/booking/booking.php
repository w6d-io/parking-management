<?php

namespace ParkingManagement;

use Booking\Form;
use Booking\HomeForm;
use Booking\Member;
use Booking\Order;
use Booking\ParkingType;
use Booking\Vehicle;
use Exception;
use ParkingManagement\interfaces\IParkingmanagement;
use ParkingManagement\interfaces\IShortcode;

require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "booking" . DS . "types" . DS . "parking_type.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "booking" . DS . "types" . DS . "vehicle_type.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "booking" . DS . "types" . DS . "iata.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "booking" . DS . "types" . DS . "airport.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "booking" . DS . "types" . DS . "order_status.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "booking" . DS . "includes" . DS . "form.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "booking" . DS . "includes" . DS . "home_form.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "booking" . DS . "includes" . DS . "member.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "booking" . DS . "includes" . DS . "order.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "booking" . DS . "includes" . DS . "vehicle.php";

class Booking implements IShortcode, IParkingManagement
{

	private ParkingManagement $pm;

	private string $kind = 'booking';

	public function __construct(ParkingManagement $pm)
	{
		$this->pm = $pm;
	}

	public function shortcode($type): string
	{
		return match ($type) {
			'form' => $this->shortcode_form(),
			'home-form' => $this->shortcode_home_form(),
			'default' => '',
		};
	}

	private function shortcode_form(): string
	{
		$form = new Form($this->pm);
		$form->setKind($this->kind);
		return $this->message() .
			Html::_div(array('class' => 'form container-md col mt-5'),
				Html::_div(array('class' => 'row'),
					Html::_div(array('class' => 'col-12'),
						Html::_form('reservation', 'reservation', 'post', '', array(),
							Html::_div(array('class' => 'row'),
								Html::_div(array('class' => 'col-12'),
									$form->personal_information(),
									$form->trip_information()
								),
								$form->cancellation_insurance(),
								$form->cgv(),
								$form->total(),
								$form->submit(),
							)
						)
					)
				),
				$form->dialog_booking_confirmation(),
			)
			. $form->spinner();
	}

	private function message(): string
	{
		$post = array_merge($_GET, $_POST);
		if (array_key_exists("message", $post)) {
			foreach ($post['message'] as $key => $value) {
				return match ($key) {
					'error' => Html::_alert('danger', $value),
					'info' => Html::_alert('info', $value),
					default => ''
				};
			}
		}
		return '';
	}


	private function shortcode_home_form(): string
	{
		$home_form = new HomeForm($this->pm);
		return Html::_div(array('class' => 'home-form container-md col-xl-6 my-4'),
			Html::_div(array('class' => 'row'),
				Html::_div(array('class' => 'col-12'),
					Html::_form('quote', 'quote', 'post', '', array(),
						$home_form->hidden($this->pm),
						Html::_div(array('class' => 'row row-cols-3'),
							$home_form->quote($this->pm),
						)
					)
				)
			)
		);
	}

	public function record(): void
	{
		global $current_shortcode_page;
		$post = array_merge($_GET, $_POST);
		$kind = 'booking';
		if ($post['parking_type'] == ParkingType::VALET->value)
			$kind = 'valet';
		$page = home_url();
		try {
			$member = new Member($kind);
			$member_id = $member->isMemberExists($post['email']);
			if (!$member_id)
				$member_id = $member->create();
			else
				$member->patch($member_id, $post);
			$order = new Order($kind);
			$order_id = $order->create($member_id);
			if ($order_id === 0)
				exit(0);
			$vehicle = new Vehicle($kind);
			$vehicle->create($order_id);

			$payment = new Payment($this->pm);
			$payment->setOrderId($order_id);
			$payment->setKind($kind);
			if ($payment->isEnabled() && $payment->doRedirect($kind)) {
				$_GET['order_id'] = $order_id;
				Logger::debug('booking.record', ["source" => $kind, "order_id" => $order_id]);
				$payment->redirect($kind);
			}
			$form = $this->pm->prop('form');
			if ($form['validation_page']['value'] != '' && $post['parking_type'] != ParkingType::VALET->value)
				$page = $form['validation_page']['value'];
			if ($post['parking_type'] == ParkingType::VALET->value && $form['valet']['validation_page']['value'] != '')
				$page = $form['valet']['validation_page']['value'];

			wp_redirect($page . '?order_id=' . $order_id);
			exit(0);

		} catch (Exception $e) {
			Logger::error("booking.record", $e->getMessage());
			unset($post['pkmgmt_action'], $post['submit2']);
			wp_redirect($current_shortcode_page . '?' . http_build_query($post) . '&message[error]=' . $e->getMessage());
			return;
		}
	}

	public function setKind(string $kind): void
	{
		$this->kind = $kind;
	}
}
