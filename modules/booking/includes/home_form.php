<?php

namespace Booking;

use ParkingManagement\Html;
use ParkingManagement\ParkingManagement;

class HomeForm
{

	private function enqueue(ParkingManagement $pm): void
	{
		wp_enqueue_style('parking-management-home-form', pkmgmt_plugin_url('modules/booking/css/home_form.css'));
		wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css', array(), '6.0.0-beta3');
		wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', array(), '5.3.3');
		wp_enqueue_style('parking-management-easepick', 'https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.1/dist/index.css');
		wp_enqueue_style('parking-management-jquery-ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css');

		wp_enqueue_script('parking-management-jquery', 'https://code.jquery.com/jquery-3.6.0.min.js');
		wp_enqueue_script('parking-management-jquery-ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js', array('parking-management-jquery'));
		wp_enqueue_script('parking-management-easepick', 'https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.1/dist/index.umd.min.js');
		wp_enqueue_script(
			'parking-management-home-form',
			pkmgmt_plugin_url('modules/booking/js/home_form.js'),
			array(
				'parking-management-jquery',
				'parking-management-jquery-ui',
				'parking-management-easepick',
			),
			PKMGMT_VERSION
		);
		$properties = $pm->get_properties();
		unset($properties['payment']);
		unset($properties['database']);
		unset($properties['api']['user']);
		unset($properties['api']['password']);
		unset($properties['sms']);
		unset($properties['response']);
		wp_localize_script('parking-management-home-form',
			'external_object',
			array(
				'locale' => $pm->locale,
				'site_id' => Order::getSiteID($properties['info']['terminal']),
				'properties' => $properties,
			)
		);
	}

	public function __construct(ParkingManagement $pm)
	{
		$this->enqueue($pm);
	}

	public function hidden(ParkingManagement $pm): string
	{
		$info = $pm->prop('info');
		$contents = array();
		$contents[] .= Html::_index('hidden', 'pkmgmt_action', 'pkmgmt_action', array('value' => 'home-form'));
		$contents[] .= Html::_index('hidden', 'type_id', 'type_id', array('value' => '1'));
		$contents[] .= Html::_index('hidden', 'site_id', 'site_id', array('value' => Order::getSiteID($info['terminal'])));
		$contents[] .= Html::_index('hidden', 'aeroport', 'aeroport', array('value' => Order::getSiteID($info['terminal'])));
		return implode("", $contents);

	}
	public function quote(ParkingManagement $pm): string
	{
		$info = $pm->prop('info');
		$post = array_merge($_GET, $_POST);
		return
			Html::_div(
				array('class' => 'col-6',),
				Html::_label_with_attr(
					array('class' => 'form-label'),
					'depart',
					esc_html__('Dropping off at', 'parking-management')
				),
				Html::_index('text', 'depart', 'depart', array(
					'class' => 'departure regular required border rounded form-control py-2',
					'autocomplete' => 'off',
					'tabindex' => "12",
					'value' => $post['depart'] ?? '',
				)),

			).
			Html::_div(
				array('class' => 'col-6',),
				Html::_label_with_attr(
					array(
						'class' => 'form-label'
					),
					'retour',
					esc_html__('Landing at the airport', 'parking-management')
				),
				Html::_index('text', 'retour', 'retour', array(
					'class' => 'return regular required border rounded form-control py-2',
					'autocomplete' => 'off',
					'tabindex' => "-1",
					'value' => $post['retour'] ?? '',
				)),
			).
			Html::_div(
				array('class' => 'col-12 d-flex justify-content-around pt-3'),
				'<button id="submit" type="submit" class="btn btn-success btn-lg"><i class="fa-solid fa-hand-point-up"></i> Votre devis en 2 click</button>',
			);
	}
}
