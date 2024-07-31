<?php

namespace Payplug;

use ParkingManagement\Html;

class Page
{
	private static function enqueue($payment_url): void
	{
		wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css', array(), '6.0.0-beta3');
		wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', array(), '5.3.3');
		wp_enqueue_script('payplug', 'https://api.payplug.com/js/1/form.latest.js');
		wp_enqueue_script('parking-management-payplug', pkmgmt_plugin_url('modules/payment/includes/payplug/js/payplug.js'), array('payplug'));
		wp_localize_script('parking-management-payplug',
			'external_object',
			array('payplug_url' => $payment_url)
		);
	}
	public static function form(int $amount, string $payment_url): string
	{
		self::enqueue($payment_url);
		return Html::_div(array('id' => 'payplug-form'),
			Html::_div(array('class' => 'container'),
				Html::_div(array('class' => 'row'),
					Html::_div(array('class' => 'col-md-12'),
						Html::_div(array('class' => 'form-group'),
							'<h3 class="text-center"><i class="fa-regular fa-credit-card"></i> ' . esc_html__("Last step: payment") . '</h3>',
						),
						'<p class="text-center">',
						esc_html__('Amount of the reservation :') . $amount . ' €',
						'</p>',
						Html::_form('payplug-form', 'payplug-form', 'post', '',
							array(
								'target' => '_top',
							),
							Html::_div(array('class' => 'text-center'),
								'<button type="submit" class="btn btn-primary">' . esc_html__('Checkout', 'parking-management') . '</button>',
							),
						)
					)
				)
			)
		);
	}
}
