<?php

namespace ParkingManagement;

use Booking\Form;
use ParkingManagement\interfaces\IParkingmanagement;
use ParkingManagement\interfaces\IShortcode;

require_once PKMGMT_PLUGIN_MODULES_DIR . DS . "booking" . DS . "includes" . DS . "form.php";

class Booking implements IShortcode, IParkingManagement
{

	private ParkingManagement $pm;

	public function __construct(ParkingManagement $pm)
	{
		$this->pm = $pm;
		wp_enqueue_style('parking-management-booking', pkmgmt_plugin_url('modules/booking/css/styles.css'));
		wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css', array(), '6.0.0-beta3');
		wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',array(), '5.3.3');
		wp_enqueue_style('parking-management-easepick', 'https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.1/dist/index.css');
		wp_enqueue_style('parking-management-jquery-ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css');
		wp_enqueue_style('parking-management-intl-tel-input', 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/css/intlTelInput.css');

		wp_enqueue_script('parking-management-jquery', 'https://code.jquery.com/jquery-3.6.0.min.js');
		wp_enqueue_script('parking-management-jquery-ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js', array('parking-management-jquery'));
		wp_enqueue_script('parking-management-jquery-validate', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js', array('parking-management-jquery'));
		wp_enqueue_script('parking-management-intl-tel-input', 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/js/intlTelInput.min.js', array('parking-management-jquery'));
		wp_enqueue_script('parking-management-easepick', 'https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.1/dist/index.umd.min.js');
		wp_enqueue_script('parking-management-luxon', 'https://cdn.jsdelivr.net/npm/luxon/build/global/luxon.min.js');
//		wp_enqueue_script('parking-management-booking', pkmgmt_plugin_url('modules/booking/js/scripts.js'), array(), PKMGMT_VERSION);
		wp_enqueue_script(
			'parking-management-booking',
			pkmgmt_plugin_url('modules/booking/js/scripts.js'),
			array(
				'parking-management-jquery',
				'parking-management-jquery-ui',
				'parking-management-jquery-validate',
				'parking-management-easepick',
				'parking-management-luxon',
				'parking-management-intl-tel-input',
				),
			PKMGMT_VERSION);
		wp_localize_script('parking-management-booking',
			'external_object',
			array('properties' => $pm->get_properties())
		);
	}

	public function shortcode($type): string
	{
		$form = new Form();
		$elements = array();
		$elements[] .= '<div class="container-sm col-md-6">';
		$elements[] .= '<div class="row">';
		$elements[] .= '<div class="col-12">';
		$elements[] .= '<form id="reservation" name="reservation" method="post" action="">';
		$elements[] .= '<div class="row">';
		$elements[] .= '<div class="col-12">';
		$elements[] .= $form->personal_information($this->pm);
		$elements[] .= $form->trip_information($this->pm);
//		$elements[] .= $form->departure_information($this->pm);
//		$elements[] .= $form->return_information($this->pm);
		$elements[] .= '</div>';
		$elements[] .= $form->cgv($this->pm);
		$elements[] .= $form->total();
		$elements[] .= $form->submit();
		$elements[] .= '</div>';
		$elements[] .= '</form>';
		$elements[] .= '</div>';
		$elements[] .= '</div>';
		$elements[] .= '</div>';

		return implode(PHP_EOL, $elements);
	}
}
