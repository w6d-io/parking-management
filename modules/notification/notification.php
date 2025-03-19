<?php

namespace ParkingManagement;

use Booking\Member;
use Booking\Order;
use Booking\Vehicle;
use Exception;
use Notification\Mail;
use ParkingManagement\interfaces\IShortcode;
use Notification\SMS;

require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "notification" . DS . "includes" . DS . "mail.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "notification" . DS . "includes" . DS . "sms.php";

class Notification implements IShortcode
{
	private ParkingManagement $pm;
	private string $kind;

	public function __construct(ParkingManagement $pm)
	{
		$this->pm = $pm;

	}

	public function shortcode(string $type): string
	{
		if (!array_key_exists('order_id', $_GET) || !is_numeric($_GET['order_id']))
			return '';
		// TODO add query string into payment validation redirect
		if (Payment::validateOnPayment($this->kind) && (!array_key_exists('from', $_GET) || $_GET['from'] != 'provider'))
			return '';
		try {

			$data = $this->getData($this->kind);
			$order = new Order($this->kind);
			return match ($type) {
				'confirmation' => $this->confirmation($data, $order),
				'cancellation' => $this->cancellation($data, $order),
				default => ''
			};
		} catch (Exception $e) {
			Logger::error("notification.getData", $e->getMessage());
			return '';
		}
	}

	private function confirmation(array $data, Order $order): string
	{
		try {
			$payment = new Payment($this->pm);
			$payment->setKind($this->kind);
			$payment->setOrderId($_GET['order_id']);
			$payment->updatePaymentStatus();
			$order->confirmed($_GET['order_id']);
			$config = match ($this->kind) {
				'booking', 'valet' => $this->pm->prop($this->kind),
				default => [],
			};
			if (empty($config)) {
				Logger::error("notification.confirmation", "get config failed");
				return '';
			}
			$this->mail($data, esc_html__('Summary of your order', 'parking-management'), $config['mail_templates']['confirmation']['value']);
			$this->sms($data, $config['sms_template']);
		} catch (Exception $e) {
			Logger::error("notification.confirmation", $e->getMessage());
		}
		return '';
	}

	private function cancellation(array $data, Order $order): string
	{
		try {
			$order->cancel($_GET['order_id']);
			$config = match ($this->kind) {
				'booking', 'valet' => $this->pm->prop($this->kind),
				default => [],
			};
			if (empty($config)) {
				Logger::error("notification.confirmation", "get config failed");
				return '';
			}
			$this->mail($data, esc_html__('Cancellation of your order', 'parking-management'), $config['mail_templates']['cancellation']['value']);
		} catch (Exception $e) {
			Logger::error("notification.cancellation", $e->getMessage());
		}
		return '';
	}

	private function mail(array $data, string $subject, $template): void
	{
		if (empty($template)) {
			Logger::warning("notification.mail", "mail do not sent to {$data['member_email']} : missing template");
			return;
		}
		$message = replacePlaceholders($template, $data);
		if (Mail::send(
			$this->kind,
			$data['member_email'],
			$subject,
			nl2br($message)
		))
			Logger::info("notification.mail", "mail sent to {$data['member_email']}");
		else
			Logger::warning("notification.mail", "mail do not sent to {$data['member_email']}");
	}

	private function sms(array $data, $template): void
	{
		$message = replacePlaceholders($template, $data);
		if (SMS::send($data['order_telephone'], $message))
			Logger::info("notification.sms", "message sent to {$data['order_telephone']}");
		else
			Logger::warning("notification.sms", "message do not sent to {$data['order_telephone']}");
	}

	/**
	 * @throws Exception
	 */
	private function getData($kind): array
	{
		$orderInstance = new Order($kind);
		$order = $orderInstance->read($_GET['order_id']);
		$memberInstance = new Member($kind);
		$member = $memberInstance->read($order['membre_id']);
		$vehicleInstance = new Vehicle($kind);
		$vehicle = $vehicleInstance->read($_GET['order_id']);

		$info = $this->pm->prop('info');
		$form = $this->pm->prop('form');
		$data = replacementData('member', $member);
		$data = array_merge($data, replacementData('order', $order));
		$data = array_merge($data, replacementData('vehicle', $vehicle));
		$data['order_depart_formated'] = DatesRange::convertDate($data['order_depart'] . ' ' . $data['order_depart_heure'], 'Y-m-d H:i:s', 'd MMMM y H:mm');
		$data['order_arrivee_formated'] = DatesRange::convertDate($data['order_arrivee'] . ' ' . $data['order_arrivee_heure'], 'Y-m-d H:i:s', 'd MMMM y H:mm');
		$data['order_id'] = $_GET['order_id'];
		$data['member_id'] = $order['membre_id'];
		$data['info_name'] = $this->pm->title;
		$data['info_address'] = $info['address'];
		$data['info_mobile'] = $info['mobile'];
		$data['info_rcs'] = $info['RCS'];
		$data['info_email'] = $info['email'];
		$data['form_options_late_price'] = $form['options']['late']['price'];
		$data['url_access'] = home_url();
		$data['home_url'] = home_url();
		return $data;
	}

	public function setKind(string $kind): void
	{
		$this->kind = $kind;
	}
}
