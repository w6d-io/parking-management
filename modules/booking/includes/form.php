<?php

namespace Booking;

use ParkingManagement\Html;
use ParkingManagement\ParkingManagement;

class Form
{
	public function __construct(ParkingManagement $pm)
	{
		$this->enqueue($pm);
	}

	private static function _radio_parking_type_field($div_class, $id, $name, array $elements, $value): string
	{
		$contents = array();
		foreach ($elements as $element) {
			$contents[] = Html::_div(
				array(
					'class' => 'radio ' . $div_class,
				),
				Html::_radio($id . '_' . $element['id'], $name, $element['value'], array('class'=>'parking-type','tabindex' => "9"), $value == $element['value']),
				Html::_label_with_attr(
					array(
						'class' => 'px-md-5 px-3',
					),
					$id . '_' . $element['id'], $element['label']),
			);
		}

		return implode(PHP_EOL, $contents);
	}

	private static function _radio_type_field($div_class, $id, $name, array $elements, $value): string
	{
		$contents = array();
		foreach ($elements as $element) {
			$contents[] = Html::_div(
				array(
					'class' => 'radio ' . $div_class,
				),
				Html::_radio($id . '_' . $element['value'], $name, $element['value'], array('class'=> 'type-id', 'tabindex' => "6",), $value == $element['value']),
				Html::_label_with_attr(array(
					'class' => 'label px-3 px-md-5'
				), $id . '_' . $element['value'],

					$element['label']

				),
			);
		}

		return implode(PHP_EOL, $contents);
	}

	private static function _row_field($class, ...$contents): string
	{
		return Html::_div(
			array('class' => 'row mb-3'),
			Html::_div(array('class' => 'col ' . $class), ...$contents),
		);
	}

	private function get_parking_type(ParkingManagement $pm): array
	{
		$types = array();
		$info = $pm->prop('info');
		if (!empty($info) && is_array($info) && array_key_exists('type', $info)) {
			if (array_key_exists('ext', $info['type']) && $info['type']['ext'] === '1')
				$types[] = array(
					'id' => '1',
					'label' => esc_html__('Outside', 'parking-management'),
					'value' => '0'
				);
			if (array_key_exists('int', $info['type']) && $info['type']['int'] === '1')
				$types[] = array(
					'id' => '2',
					'label' => esc_html__('Inside', 'parking-management'),
					'value' => '1'
				);
		}
		return $types;
	}

	private function get_vehicle_type(ParkingManagement $pm): array
	{
		$types = array();
		$info = $pm->prop('info');
		if (!empty($info) && is_array($info) && array_key_exists('vehicle_type', $info)) {
			if (array_key_exists('car', $info['vehicle_type']) && $info['vehicle_type']['car'] === '1')
				$types[] = array(
					'id' => '1',
					'label' => '<i class="fa fa-car fa-lg"></i>',
					'value' => '1'
				);
			if (array_key_exists('motorcycle', $info['vehicle_type']) && $info['vehicle_type']['motorcycle'] === '1')
				$types[] = array(
					'id' => '2',
					'label' => '<i class="fa fa-motorcycle fa-lg"></i>',
					'value' => '2'
				);
			if (array_key_exists('truck', $info['vehicle_type']) && $info['vehicle_type']['truck'] === '1')
				$types[] = array(
					'id' => '3',
					'label' => '<i class="fa fa-truck fa-lg"></i>',
					'value' => '3'
				);
		}
		return $types;
	}


	private static function get_terminal(ParkingManagement $pm): array
	{
		$info = $pm->prop('info');
		$terminal = 'Orly';
		if (!empty($info) && is_array($info) && array_key_exists('terminal', $info))
			$terminal = $info['terminal'];

		return match ($terminal) {
			'Roissy', 'roissy' => array(
				'group' => array(
					'Roissy' => array(
						array(
							'value' => "3",
							'label' => 'Terminal 1',
						),
						array(
							'value' => "4",
							'label' => 'Terminal 3'
						),
						array(
							'value' => "5",
							'label' => 'Terminal 2A'
						),
						array(
							'value' => "6",
							'label' => 'Terminal 2B'
						),
						array(
							'value' => "7",
							'label' => 'Terminal 2C'
						),
						array(
							'value' => "8",
							'label' => 'Terminal 2D'
						),
						array(
							'value' => "9",
							'label' => 'Terminal 2E'
						),
						array(
							'value' => "10",
							'label' => 'Terminal 2F'
						),
						array(
							'value' => "11",
							'label' => 'Terminal 2G'
						)
					)
				)
			),
			default => array(
				'group' => array(
					'Orly' => array(
						array(
							'value' => "1",
							'label' => 'Terminal 4',
						),
						array(
							'value' => "2",
							'label' => 'Terminal 1,2,3'
						)
					)
				)
			)
		};
	}

	private static function _label($for, $contents): string
	{
		return Html::_label_with_attr(
			array(
				'class' => 'label form-label'
			),
			$for,
			$contents
		);
	}

	private function enqueue(ParkingManagement $pm): void
	{
		wp_enqueue_style('parking-management-booking', pkmgmt_plugin_url('modules/booking/css/form.css'));
		wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css', array(), '6.0.0-beta3');
		wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', array(), '5.3.3');
		wp_enqueue_style('parking-management-easepick', 'https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.1/dist/index.css');
		wp_enqueue_style('parking-management-jquery-ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css');
		wp_enqueue_style('parking-management-intl-tel-input', 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/css/intlTelInput.css');

		wp_enqueue_script('parking-management-jquery', 'https://code.jquery.com/jquery-3.6.0.min.js');
		wp_enqueue_script('parking-management-jquery-ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js', array('parking-management-jquery'));
		wp_enqueue_script('parking-management-jquery-validate', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js', array('parking-management-jquery'));
		wp_enqueue_script('parking-management-intl-tel-input', 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.1.0/build/js/intlTelInput.min.js', array('parking-management-jquery'));
		wp_enqueue_script('parking-management-easepick', 'https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.1/dist/index.umd.min.js');
		wp_enqueue_script('parking-management-luxon', 'https://cdn.jsdelivr.net/npm/luxon/build/global/luxon.min.js');
		wp_enqueue_script(
			'parking-management-booking',
			pkmgmt_plugin_url('modules/booking/js/form.js'),
			array(
				'parking-management-jquery',
				'parking-management-jquery-ui',
				'parking-management-jquery-validate',
				'parking-management-easepick',
				'parking-management-luxon',
				'parking-management-intl-tel-input',
			),
			PKMGMT_VERSION);
		$properties = $pm->get_properties();
		unset($properties['payment']);
		unset($properties['database']);
		unset($properties['sms']);
		unset($properties['response']);
		wp_localize_script('parking-management-booking',
			'external_object',
			array(
				'locale' => $pm->locale,
				'home_url' => home_url(),
				'form_css' => pkmgmt_plugin_url('modules/booking/css/form.css'),
				'properties' => $properties
			)
		);

	}

	public function personal_information(ParkingManagement $pm): string
	{
		$post = array_merge($_GET, $_POST);
		$parking_type = $this->get_parking_type($pm);
		$vehicle_type = $this->get_vehicle_type($pm);
		return Html::_div(
			array('class' => 'personal-information col-12'),
			Html::_fieldset(
				'<legend>' . __('Personal Information', 'parking-management') . '</legend>',
				self::_row_field('name',
					self::_label('nom', esc_html__('Name', 'parking-management')),
					Html::_index('text', 'nom', 'nom',
						array(
							'class' => 'name regular required col-5 border rounded py-2 px-3 form-control',
							'value' => $post['nom'] ?? '',
							'tabindex' => "1",
							'autofocus' => 'autofocus',
						)
					),
				),
				self::_row_field('firstname',
					self::_label('prenom', esc_html__('Firstname', 'parking-management')),
					Html::_index('text', 'prenom', 'prenom',
						array(
							'class' => 'firstname regular required col-5 border rounded py-2 px-3 form-control',
							'value' => $post['prenom'] ?? '',
							'tabindex' => "2",
						)
					),
				),
				self::_row_field('zip-code',
					self::_label('code_postal', esc_html__('Zip code', 'parking-management')),
					Html::_index('text', 'code_postal', 'code_postal',
						array(
							'class' => 'zip-code regular required col-5 border rounded py-2 px-3 form-control',
							'autocomplete' => 'off',
							'value' => $post['code_postal'] ?? '',
							'tabindex' => "3",
						)
					),
					Html::_index('text', 'ville', 'ville',
						array(
							'class' => 'ville regular',
							'value' => $post['ville'] ?? '',
							'tabindex' => "-1",
						)
					),
					Html::_index('text', 'pays', 'pays',
						array(
							'class' => 'pays regular',
							'value' => $post['pays'] ?? '',
							'tabindex' => "-1",
						)
					),
				),
				self::_row_field('mobile input-group align-items-center',
					Html::_index('hidden', 'tel_port', 'tel_port', []),
					Html::_label_with_attr(
						array('class' => 'mobile col-sm-3'),
						'mobile',
						esc_html__('Mobile phone', 'parking-management')
					),
					Html::_index('tel', 'mobile', 'mobile',
						array(
							'class' => 'mobile regular required col-5 border rounded py-2 form-control',
							'value' => $post['tel_port'] ?? '',
							'tabindex' => "4",
						)
					),
				),
				self::_row_field('email',
					self::_label('email', esc_html__('Email', 'parking-management')),
					Html::_index('email', 'email', 'email',
						array(
							'class' => 'email regular required col-5 border rounded py-2 px-3 form-control',
							'value' => $post['email'] ?? '',
							'tabindex' => "5",
						)
					),
				),
				self::_row_field('type-id input-group align-items-center',
					Html::_label_with_attr(
						array('class' => 'type_id col-sm-3'),
						'type_id',
						esc_html__('Type of vehicle', 'parking-management')
					),
					Html::_div(
						array(
							'class' => 'row col col-sm-5 col-md-8 gx-sm-4 gx-md-5 justify-content-around',
						),
						self::_radio_type_field(
							'col col-sm d-flex justify-content-around p-0',
							'type_id',
							'type_id',
							$vehicle_type,
							($post['type_id'] ?? $vehicle_type[0]['value']))
					)
				),
				self::_row_field('modele',
					self::_label('modele', esc_html__('Car model', 'parking-management')),
					Html::_index('text', 'marque', 'marque',
						array(
							'class' => 'marque regular',
							'value' => $post['marque'] ?? '',
							'tabindex' => "-1",
						)
					),
					Html::_index('text', 'modele', 'modele',
						array(
							'class' => 'modele regular required col-5 border rounded py-2 px-3 form-control',
							'value' => $post['modele'] ?? '',
							'tabindex' => "7",
						)
					)
				),
				self::_row_field('immatriculation',
					self::_label('immatriculation', esc_html__('Immatriculation', 'parking-management')),
					Html::_index('text', 'immatriculation', 'immatriculation',
						array(
							'class' => 'immatriculation regular required col-5 border rounded py-2 px-3 form-control',
							'value' => $post['immatriculation'] ?? '',
							'tabindex' => "8",
						)
					)
				),
				self::_row_field('parking_type input-group align-items-center',
					Html::_label_with_attr(
						array('class' => 'parking_type col-sm-3'),
						'parking_type',
						esc_html__('Car Park', 'parking-management')
					),
					Html::_div(
						array(
							'class' => 'col col-sm-5 col-md-8 row justify-content-around',
						),
						self::_radio_parking_type_field(
							'col-sm d-flex justify-content-around',
							'parking_type',
							'parking_type',
							$parking_type,
							($post['parking_type'] ?? $parking_type[0]['value']))
					)
				),
			)
		);
	}

	public function trip_information(ParkingManagement $pm): string
	{
		$post = array_merge($_GET, $_POST);
		return Html::_div(
			array('class' => 'trip_information'),
			Html::_fieldset(
				'<legend>' . __('Trip Information', 'parking-management') . '</legend>',

				self::_row_field('destination',
					self::_label('destination', esc_html__('Destination', 'parking-management')),
					Html::_index('text', 'destination', 'destination',
						array(
							'class' => 'destination regular required border rounded py-2 form-control',
							'value' => $post['destination'] ?? '',
							'tabindex' => "10",
						)
					),
					Html::_index('text', 'destination_id', 'destination_id',
						array(
							'class' => 'destination_id regular',
							'value' => $post['destination_id'] ?? '',
							'tabindex' => "-1",
						)
					),
				),
				self::_row_field('',
					Html::_div(
						array('class' => 'row border mx-2 pb-3'),
						Html::_div(
							array(
								'class' => 'row d-none d-md-flex',
							),
							Html::_div(
								array(
									'class' => 'col',
								),
								'<h1 class="title">Departure</h1>',
							),
							Html::_div(
								array(
									'class' => 'col',
								),
								'<h1 class="title">Return</h1>',
							),
						),
						Html::_div(
							array('class' => 'row'),
							Html::_div(
								array('class' => 'col',),
								Html::_label_with_attr(
									array('class' => 'form-label'),
									'terminal_depart',
									esc_html__('Terminal departure', 'parking-management')
								),
								Html::_select('terminal_depart', 'terminal[depart]',
									array(
										'class' => 'required border form-select py-2',
										'tabindex' => "11",
									),
									self::get_terminal($pm),
									(array_key_exists('terminal', $post) && $post['terminal']['depart'] ?? '')),

							),
							Html::_div(
								array('class' => 'col'),
								Html::_label_with_attr(
									array('class' => 'form-label'),
									'terminal_arrivee',
									esc_html__('Terminal return', 'parking-management')
								),
								Html::_select('terminal_arrivee', 'terminal[arrivee]',
									array(
										'class' => 'required border form-select py-2',
										'tabindex' => "13",
									),
									self::get_terminal($pm),
									(array_key_exists('terminal', $post) && $post['terminal']['arrivee'] ?? '')),
							),
						),
						Html::_div(
							array('class' => 'row',),
							Html::_div(
								array('class' => 'col',),
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

							),
							Html::_div(
								array('class' => 'col',),
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
							),
						),
					),
				),
				self::_row_field('nb_pax',
					self::_label('nb_pax', esc_html__('Number of pax', 'parking-management')),
					Html::_select('nb_pax', 'nb_pax', array(
						'class' => 'required border col-5 rounded py-2 px-3 form-select',
						'tabindex' => "14",
					),
						array(
							array(
								'value' => '',
								'label' => '-'
							),
							array(
								'value' => '1',
								'label' => '1'
							),
							array(
								'value' => '2',
								'label' => '2'
							),
							array(
								'value' => '3',
								'label' => '3'
							),
							array(
								'value' => '4',
								'label' => '4'
							),
							array(
								'value' => '5',
								'label' => '5  (+7€)'
							),
							array(
								'value' => '6',
								'label' => '6 (+14€)'
							),
						),
						array_key_exists('nb_pax', $post) ? $post['nb_pax'] : ''
					),
				)

			),
		);
	}

	public function cgv(ParkingManagement $pm): string
	{
		$form = $pm->prop('form');
		if ($form['booking']['terms_and_conditions'] !== '1')
			return '';
		$post = array_merge($_GET, $_POST);
		$warning_msg = "Merci de valider les conditions générales de vente";
		$msg = <<<EOT
En cochant cette case (obligatoire), j'accèpte les <a href="/cgv" target="_blank">conditions générales de vente</a>, atteste la validation de commande et mon obligation de paiement, <strong>en vertu de l'article L.121-19-3</strong>
EOT;

		return Html::_div(
			array('class' => 'cgv form-check'),
			Html::_index('hidden', '', 'cgv_reservation', array('value' => '0')),
			Html::_index(
				'checkbox',
				'cgv_reservation',
				'cgv_reservation',
				array(
					'class' => 'cgv_reservation required form-check-input',
					'value' => "1",
					'tabindex' => "16",
					'data-msg-required' => $warning_msg,
				),
				false,
				false,
				(array_key_exists('cgv_reservation', $post) ? $post['cgv_reservation'] : '0') == '1'
			),
			Html::_label_with_attr(array('class'=> 'form-check-label'),'cgv_reservation', $msg),
		);
	}

	public function total(): string
	{
		return Html::_div(
			array(),
			Html::_index('hidden', 'total_amount', 'total_amount',
				array('value' => '0')
			),
			Html::_div(
				array(
					'class' => 'total form-control',
					'id' => 'total',
				),
				esc_html__('Order amount', 'parking-management'),
				'<span>0€</span>'
			)
		);
	}

	public function submit(ParkingManagement $pm): string
	{
		$info = $pm->prop('info');
		return Html::_div(
			array(
				'class' => 'mt-4 row justify-content-md-center',
			),
			Html::_div(array(
				'class' => 'col-sm-4',
			),
				Html::_index('hidden', 'aeroport', 'aeroport', array('value' => Order::getSiteID($info['terminal'])->value)),
				Html::_index('hidden', 'pkmgmt_action', 'pkmgmt_action', array('value' => 'booking')),
				'<button type="submit" tabindex="17" id="submit" name="submit" class="form-control btn btn-primary text-center" disabled>'
				. esc_html__('Validate your order', 'parking-management')
				. ' <i class="fa-regular fa-circle-right"></i>'
				. '</button>'

			),

		);
	}

	public function dialog_booking_confirmation(ParkingManagement $pm): string
	{
		$post = array_merge($_GET, $_POST);
		$form = $pm->prop('form');
		if (!empty($form) &&
			isset($form['booking']['dialog_confirmation']) &&
			$form['booking']['dialog_confirmation'] === '0') {
			return '';
		}
		return '<div id="dialog_booking_confirmation" title="' . esc_html__('Confirmation', 'parking-management') . '">
	<form id="confirmation" name="confirmation" method="post" action="">
		<div class="row">
			<div>
				<label class="form-label" for="depart2">' . esc_html__('Dropping off at', 'parking-management') . '</label>
				<input tabindex="19" type="text" id="depart2" name="depart" class="departure regular required border rounded form-control py-2" autocomplete="off" value="' . ($post['depart'] ?? '') . '">
			</div>
			<div>
					<label class="form-label" for="retour2">' . esc_html__('Landing at the airport', 'parking-management') . '</label>
					<input tabindex="-1" type="text" id="retour2" name="retour" class="return regular required border rounded form-control py-2" autocomplete="off" value="' . ($post['retour'] ?? '') . '">
			</div>
			<div class="mb-3">
				<div class="col email">
					<label class="label form-label" for="email2">' . esc_html__('Email', 'parking-management') . '</label>
					<input tabindex="18" type="email" id="email2" name="email" class="email regular required col-5 border rounded py-2 px-3 form-control" value="' . ($post['email'] ?? '') . '">
				</div>
			</div>
		</div>
        <div class="row my-3 position-absolute bottom-0 start-50 translate-middle-x">
			<div class="col">
                <button tabindex="20" id="submit2" class="form-control btn btn-primary text-center gradient-e97445" name="submit2" type="submit">
                    ' . esc_html__('Confirm your order', 'parking-management') . '
                    <i class="fa-regular fa-circle-right"></i>
				</button>
			</div>
		</div>
	</form>
</div>';
	}

	public function cancellation_insurance(ParkingManagement $pm): string
	{
		$form = $pm->prop('form');
		if ($form['options']['cancellation_insurance']['enabled'] !== '1' || $form['options']['cancellation_insurance']['price'] === '0' )
			return '';
		// Cocher cette case si le client souscrit l'assurance annulation à
		$post = array_merge($_GET, $_POST);
		$msg = esc_html__('I hereby subscribe to the cancellation insurance for '.$form['options']['cancellation_insurance']['price'].' €', 'parking-management');
		return Html::_div(
			array('class' => 'cancellation-insurance form-check'),
			Html::_index('hidden', '', 'assurance_annulation', array('value' => '0')),
			Html::_index(
				'checkbox',
				'assurance_annulation',
				'assurance_annulation',
				array(
					'class' => 'cancellation-insurance form-check-input',
					'value' => "1",
					'tabindex' => "15",
				),
				false,
				false,
				(array_key_exists('assurance_annulation', $post) ? $post['assurance_annulation'] : '0') == '1'
			),
			Html::_label_with_attr(array('class' => 'form-check-label'),'assurance_annulation', $msg),
		);
	}

	public function spinner(): string
	{
		return Html::_div(array('class' => 'spinner-container', 'id' => 'spinner-container'),
			Html::_div(array('class' => 'spinner-border text-primary', 'role'=>'status'),
				Html::_span(array('class' => 'sr-only'),
					__('Loading...', 'parking-management')
				),
			)
		);
	}
}
