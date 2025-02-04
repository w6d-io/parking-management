<?php

namespace Mypos;

use ParkingManagement\Html;

class Page
{
	private static function enqueue($amount, $order_id, $provider): void
	{
		$test_enabled = $provider['active-test'] === '1';
		$success_url = $provider['properties']['success_page']['value'] . '?from=provider&order_id=' . $order_id;
		$cancel_url = $provider['properties']['cancel_page']['value'] . '?order_id=' . $order_id;
		$notify_url = home_url() . "/wp-json/pkmgmt/v1/mypos/ipn";
		if ($provider['properties']['notification_url']['value'] !== '')
			$notify_url = $provider['properties']['notification_url']['value'];
		wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css', array(), '6.0.0-beta3');
		wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', array(), '5.3.3');
		wp_enqueue_script('mypos-sdk', "https://developers.mypos.com/repository/mypos-embedded-sdk.js");
		wp_enqueue_script('parking-management-mypos', pkmgmt_plugin_url('modules/payment/includes/mypos/js/mypos.js'), ['mypos-sdk']);
		wp_localize_script('parking-management-mypos', 'external_object',
			array(
				'success_url' => $success_url,
				'cancel_url' => $cancel_url,
				'notify_url' => $notify_url,
				'order_id' => $order_id,
				'amount' => $amount,
				'test_enabled' => $test_enabled,
				'article' => 'reservation du '
			)
		);
	}

	public static function form(float $amount, $order_id, $provider): string
	{
		self::enqueue($amount, $order_id, $provider);
		return Html::_div(array('id' => 'mypos-form'),
			Html::_div(array('class' => 'container'),
				Html::_div(array('class' => 'row'),
					Html::_div(array('class' => 'col-sm-12'),
						Html::_div(array('class' => 'form-group'),
							'<h3 class="text-center"><i class="fa-regular fa-credit-card"></i> ' . esc_html__("Last step: payment", 'parking-management') . '</h3>',
						),
						Html::_div(['id'=>'embeddedCheckout']),
					)
				)
			)
		);
	}
}
