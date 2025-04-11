<?php

namespace Stripe;

use ParkingManagement\Html;

class Page
{
	private static function enqueue(string $payment_url): void
	{
		wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css', array(), '6.0.0-beta3');
		wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', array(), '5.3.3');
		wp_enqueue_script('parking-management-stripe', pkmgmt_plugin_url('modules/payment/includes/stripe/js/stripe.js'));
		wp_localize_script('parking-management-stripe',
			'external_object',
			array('stripe_url' => $payment_url)
		);
	}

	public static function form(int $amount, string $payment_url): string
	{
		self::enqueue($payment_url);
		return Html::_div(['id' => 'stripe-form'],
			Html::_div(['class' => 'container'],
				Html::_div(['class' => 'row'],
					Html::_div(['class' => 'col-md-12'],
						Html::_div(['class' => 'form-group'],
							'<h3 class="text-center"><i class="fa-regular fa-credit-card"></i>' . esc_html__("Last step: payment", 'parking-management') . '</h3>'
						),
						'<p class="text-center">',
						esc_html__('Amount of the reservation :', 'parking-management') . $amount . ' €',
						'</p>',
						Html::_form('stripe-form', 'stripe-form', 'post', '',
							['target' => '_top',],
							Html::_div(['class' => 'text-center'],
								'<button type="submit" class="btn btn-primary">' . esc_html__("Checkout", 'parking-management') . '</button>',
							)
						),
					)
				)
			)
		);
	}
}
