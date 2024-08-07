<?php

namespace ParkingManagement;

use Booking\Member;
use Booking\Order;
use Exception;
use Notification\Mail;
use ParkingManagement\interfaces\IShortcode;
use Notification\SMS;

require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "notification" . DS . "includes" . DS . "mail.php";
require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "notification" . DS . "includes" . DS . "sms.php";

class Notification implements IShortcode
{
	private ParkingManagement $pm;

	public function __construct(ParkingManagement $pm)
	{
		$this->pm = $pm;

	}

	public function shortcode(string $type): string
	{
		if (!array_key_exists('order_id', $_GET) || !is_numeric($_GET['order_id']))
			return '';
		if (Payment::validateOnPayment() && (!array_key_exists('from', $_GET) || $_GET['from'] != 'provider'))
			return '';
		try {
			$data = $this->getData();
		} catch (Exception $e) {
			Logger::error("notification.getData", $e->getMessage());
			return '';
		}
		$order = new Order;
		return match ($type) {
			'confirmation' => $this->confirmation($data, $order),
			'cancellation' => $this->cancellation($data, $order),
			default => ''
		};
	}

	private function confirmation(array $data,Order $order): string
	{
		try {
		$order->confirmed($_GET['order_id']);
		$mail = $this->pm->prop('notification')['mail'];
		$this->mail($data, esc_html__('Summary of your order', 'parking-management'), $mail['templates']['confirmation']['value']);
		$this->sms($data);
		} catch (Exception $e) {
			Logger::error("notification.confirmation", $e->getMessage());
		}
		return '';
	}

	private function cancellation(array $data, Order $order): string
	{
		try {
			$order->cancel($_GET['order_id']);
			$mail = $this->pm->prop('notification')['mail'];
			$this->mail($data, esc_html__('Order cancelled', 'parking-management'), $mail['templates']['cancellation']['value']);
		} catch (Exception $e) {
			Logger::error("notification.cancellation", $e->getMessage());
		}
		return '';
	}

	private function mail(array $data, string $subject, $template): void
	{
		if (empty($template))
			return;
		$message = replacePlaceholders($template, $data);
		if (Mail::send(
			$data['member_email'],
			$subject,
			nl2br($message)
		))
			Logger::info("notification.mail", "mail sent to {$data['member_email']}");
		else
			Logger::warming("notification.mail", "mail do not sent to {$data['member_email']}");
	}

	private function sms(array $data): void
	{
		$sms = $this->pm->prop('notification')['sms'];
		$template = $sms['template'];
		$message = replacePlaceholders($template, $data);
		if (SMS::send($data['order_telephone'], $message))
			Logger::info("notification.sms", "message sent to {$data['order_telephone']}");
		else
			Logger::warming("notification.sms", "message do not sent to {$data['order_telephone']}");
	}

	/**
	 * @throws Exception
	 */
	private function getData(): array
	{
		$orderInstance = new Order();
		$order = $orderInstance->read($_GET['order_id']);
		$memberInstance = new Member();
		$member = $memberInstance->read($order['membre_id']);

		$info = $this->pm->prop('info');
		$form = $this->pm->prop('form');
		$data = replacementData('member', $member);
		$data = array_merge($data, replacementData('order', $order));
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
}
