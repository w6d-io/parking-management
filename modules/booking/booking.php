<?php

namespace ParkingManagement;

use Booking\Form;
use Booking\HomeForm;
use Booking\Member;
use Booking\Order;
use Booking\Vehicle;
use Exception;
use ParkingManagement\interfaces\IParkingmanagement;
use ParkingManagement\interfaces\IShortcode;
use PDOException;

require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "booking" . DS . "includes" . DS . "form.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "booking" . DS . "includes" . DS . "home_form.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "booking" . DS . "includes" . DS . "member.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "booking" . DS . "includes" . DS . "order.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "booking" . DS . "includes" . DS . "vehicle.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "database" . DS . "database.php";

class Booking implements IShortcode, IParkingManagement
{

	private ParkingManagement $pm;

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
		return Html::_div(array('class' => 'form container-md col-xl-6'),
			Html::_div(array('class' => 'row'),
				Html::_div(array('class' => 'col-12'),
					Html::_form('reservation', 'reservation', 'post', '', array(),
						Html::_div(array('class' => 'row'),
							Html::_div(array('class' => 'col-12'),
								$form->personal_information($this->pm),
								$form->trip_information($this->pm)
							),
							$form->cgv($this->pm),
							$form->total(),
							$form->submit($this->pm),
						)
					)
				)
			),
			$form->dialog_booking_confirmation($this->pm)
		);
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
		$post = array_merge($_GET, $_POST);
		try {
			$member = new Member();
			$member_id = $member->isMemberExists($post['email']);
			if (!$member_id)
				$member_id = $member->create();
			$order = new Order();
			$order_id = $order->isExists($member_id);
			if (!$order_id)
				$order_id = $order->create($member_id);
			$vehicle = new Vehicle();
			$vehicle->create($order_id);

		} catch (Exception|PDOException $e) {
			if ($post && array_key_exists('DEBUG', $post) && $post['DEBUG'] === 1) {
				print_log($e->getMessage(), false);
			}
			return;
		}
	}

}
