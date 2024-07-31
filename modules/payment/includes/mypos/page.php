<?php

namespace Mypos;

use ParkingManagement\Html;

class Page
{
	private static function enqueue(): void
	{
		wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css', array(), '6.0.0-beta3');
		wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', array(), '5.3.3');
	}
	//<div %s>
	//  <div class="container">
	//    <div class="row">
	//      <div class='col-md-12'>
	//        <h1 style="text-align: center;"><i class="fa fa-flask"></i> Dernière étape: paiement </h1>
	//        <p style="text-align: center;">
	//          Montant de la réservation : %s €
	//        </p>
	//        <div class='col-md-4'>
	//          <form name="pkmgmt-payplug" id="pkmgmt-payplug" class="validation reservation" action="%s" method="get" target="_top" novalidate>
	//            <p style="text-align: center;">
	//      			<input type="hidden" name="resaid" value="%s" />
	//              	<button type="submit" class="btn btn-default">Paiement</button>
	//            </p>
	//          </form>
	//        </div>
	//      </div>
	//    </div>
	//  </div>
	//</div>
	public static function form(int $amount, $order_id): string
	{
		self::enqueue();
		return Html::_div(array('id' => 'mypos-form'),
			Html::_div(array('class' => 'container'),
				Html::_div(array('class' => 'row'),
					Html::_div(array('class' => 'col-sm-12'),
						Html::_div(array('class' => 'form-group'),
							'<h3 class="text-center"><i class="fa-regular fa-credit-card"></i> ' . esc_html__("Last step: payment") . '</h3>',
						),
						'<p class="text-center">',
						esc_html__('Amount of the reservation : ') . $amount . ' €',
						'</p>',
						Html::_form('mypos-form', 'mypos-form', 'post', "",
							array(
								'target' => '_top',
							),
							'<input type="hidden" name="order_id" value="'.$order_id.'" />',
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
