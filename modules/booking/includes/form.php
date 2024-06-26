<?php

namespace Booking;

use http\Encoding\Stream\Inflate;
use ParkingManagement\Html;
use ParkingManagement\ParkingManagement;

class Form
{
	private static function _radio_field($div_class, $id, $name, array $elements, $value): string
	{
		$contents = array();
		foreach ($elements as $element) {
			$contents[] .= Html::_div(
				array(
					'class' => 'radio ' . $div_class,
				),
				Html::_radio($id . '_' . $element['id'], $name, $element['value'], $value == $element['value']),
				Html::_label_with_attr(
					array(
						'class' => 'px-md-5 px-sm-3',
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
			$contents[] .= Html::_div(
				array(
					'class' => 'radio ' . $div_class,
				),
				Html::_radio('type_id_' . $element['value'], $name, $element['value'], $value == $element['value']),
				Html::_label_with_attr(array(
					'class' => 'label px-3 px-md-5'
				), 'type_id_' . $element['value'],

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
					'label' => esc_html(__('Outside', 'parking-management')),
					'value' => '0'
				);
			if (array_key_exists('int', $info['type']) && $info['type']['int'] === '1')
				$types[] = array(
					'id' => '2',
					'label' => esc_html(__('Inside', 'parking-management')),
					'value' => '1'
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

	public function personal_information(ParkingManagement $pm): string
	{
		$post = array_merge($_POST, $_GET);
		$parking_type = $this->get_parking_type($pm);
		return Html::_div(
			array('class' => 'personal-information col-12'),
			Html::_fieldset(
				'<legend>' . __('Personal Information', 'parking-management') . '</legend>',
				self::_row_field('name',
					self::_label('nom', esc_html(__('Name', 'parking-management'))),
					Html::_index('text', 'nom', 'nom',
						array(
							'class' => 'name regular required col-5 border rounded py-2 px-3 form-control',
							'value' => $post['nom'] ?? '',
						)
					),
				),
				self::_row_field('firstname',
					self::_label('prenom', esc_html(__('Firstname', 'parking-management'))),
					Html::_index('text', 'prenom', 'prenom',
						array(
							'class' => 'firstname regular required col-5 border rounded py-2 px-3 form-control',
							'value' => $post['prenom'] ?? '',
						)
					),
				),
				self::_row_field('zip-code',
					self::_label('code_postal', esc_html(__('Zip code', 'parking-management'))),
					Html::_index('text', 'code_postal', 'code_postal',
						array(
							'class' => 'zip-code regular required col-5 border rounded py-2 px-3 form-control',
							'autocomplete' => 'off',
							'value' => $post['code_postal'] ?? '',
						)
					),
					Html::_index('text', 'ville', 'ville',
						array(
							'class' => 'ville regular required',
							'value' => $post['ville'] ?? '',
						)
					),
					Html::_index('text', 'pays', 'pays',
						array(
							'class' => 'pays regular required',
							'value' => $post['pays'] ?? '',
						)
					),
				),
				self::_row_field('mobile',
					self::_label('tel_port', esc_html(__('Mobile phone', 'parking-management'))),
					Html::_index('tel', 'tel_port', 'tel_port',
						array(
							'class' => 'mobile regular required col-5 border rounded py-2 form-control',
							'value' => $post['tel_port'] ?? '',
						)
					),
				),
				self::_row_field('email',
					self::_label('email', esc_html(__('Email', 'parking-management'))),
					Html::_index('email', 'email', 'email',
						array(
							'class' => 'email regular required col-5 border rounded py-2 px-3 form-control',
							'value' => $post['email'] ?? '',
						)
					),
				),
				self::_row_field('type-id',
					self::_label('type_id', esc_html(__('Type of vehicle', 'parking-management'))),
					Html::_div(
						array(
							'class' => 'row col col-sm-5 col-md-5 gx-sm-4 gx-md-5 justify-content-around',
						),
						self::_radio_type_field(
							'col col-4 d-flex justify-content-around',
							'type_id',
							'type_id',
							array(
								array(
									'id' => '1',
									'label' => '<i class="fa fa-car fa-lg"></i>',
									'value' => '1'
								),
								array(
									'id' => '2',
									'label' => '<i class="fa fa-motorcycle fa-lg"></i>',
									'value' => '2'

								),
								array(
									'id' => '3',
									'label' => '<i class="fa fa-truck fa-lg"></i>',
									'value' => '3'

								)
							),
							($post['type_id'] ?? '1'))
					)
				),
				self::_row_field('modele',
					self::_label('modele', esc_html(__('Model', 'parking-management'))),
					Html::_index('text', 'marque', 'marque',
						array(
							'class' => 'marque regular required',
							'value' => $post['marque'] ?? '',
						)
					),
					Html::_index('text', 'modele', 'modele',
						array(
							'class' => 'modele regular required col-5 border rounded py-2 px-3 form-control',
							'value' => $post['modele'] ?? '',
						)
					)
				),
				self::_row_field('immatriculation',
					self::_label('immatriculation', esc_html(__('Immatriculation', 'parking-management'))),
					Html::_index('text', 'immatriculation', 'immatriculation',
						array(
							'class' => 'immatriculation regular required col-5 border rounded py-2 px-3 form-control',
							'value' => $post['immatriculation'] ?? '',
						)
					)
				),
				self::_row_field('parking_type row',
					self::_label('parking_type', esc_html(__('Car Park', 'parking-management'))),
					Html::_div(
						array(
							'class' => 'col col-sm-6 col-md-5 row justify-content-around',
						),
						self::_radio_field(
							'col-md-2 col-sm d-flex justify-content-around',
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
		$post = array_merge($_POST, $_GET);
		return Html::_div(
			array('class' => 'trip_information'),
			Html::_fieldset(
				'<legend>' . __('Trip Information', 'parking-management') . '</legend>',

				self::_row_field('destination',
					self::_label('destination', esc_html(__('Destination', 'parking-management'))),
					Html::_index('text', 'destination', 'destination',
						array(
							'class' => 'destination regular required border rounded py-2 form-control',
							'value' => $post['destination'] ?? '',
						)
					),
					Html::_index('text', 'destination_id', 'destination_id',
						array(
							'class' => 'destination_id regular required',
							'value' => $post['destination_id'] ?? '',
						)
					),
				),
				self::_row_field('',
					Html::_div(
						array(
							'class' => 'row border mx-2 pb-3',
						),
						Html::_div(
							array(
								'class' => 'row',
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
							array(
								'class' => 'row',
							),
							Html::_div(
								array(
									'class' => 'col',
								),
								Html::_label_with_attr(
									array(
										'class' => 'form-label'
									),
									'terminal_depart',
									esc_html(__('Terminal', 'parking-management'))
								),
								Html::_select('terminal_depart', 'terminal[depart]',
									array('class' => 'required border form-select py-2'),
									self::get_terminal($pm),
									(array_key_exists('terminal', $post) && $post['terminal']['depart'] ?? '')),

							),
							Html::_div(
								array(
									'class' => 'col',
								),
								Html::_label_with_attr(
									array(
										'class' => 'form-label'
									),
									'terminal_arrivee',
									esc_html(__('Terminal', 'parking-management'))
								),
								Html::_select('terminal_arrivee', 'terminal[arrivee]',
									array('class' => 'required border form-select py-2'),
									self::get_terminal($pm),
									(array_key_exists('terminal', $post) && $post['terminal']['arrivee'] ?? '')),
							),
						),
						Html::_div(
							array(
								'class' => 'row',
							),
							Html::_div(
								array(
									'class' => 'col',
								),
								Html::_label_with_attr(
									array(
										'class' => 'form-label'
									),
									'depart',
									esc_html(__('Dropping off at', 'parking-management'))
								),
								Html::_index('text', 'depart', 'depart', array(
									'class' => 'departure regular required border rounded form-control py-2',
									'autocomplete' => 'off',
								)),

							),
							Html::_div(
								array(
									'class' => 'col',
								),
								Html::_label_with_attr(
									array(
										'class' => 'form-label'
									),
									'retour',
									esc_html(__('Landing at the airport', 'parking-management'))
								),
								Html::_index('text', 'retour', 'retour', array(
									'class' => 'return regular required border rounded form-control py-2',
									'autocomplete' => 'off',
								)),
							),
						),
					),
				),
				self::_row_field('nb_pax',
					self::_label('nb_pax', esc_html(__('Number of pax', 'parking-management'))),
					Html::_select('nb_pax', 'nb_pax', array('class' => 'required border col-5 rounded py-2 px-3 form-select'),
						array(
							array(
								'value' => '0',
								'label' => '0'
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
		$post = array_merge($_POST, $_GET);
		$warning_msg = "Merci de valider les conditions générales de vente";
		$msg = <<<EOT
En cochant cette case (obligatoire), j'accèpte les <a href="/cgv" target="_blank">conditions générales de vente</a>, atteste la validation de commande et mon obligation de paiement, <strong>en vert
u de l'article L.121-19-3</strong>
EOT;
		$form = $pm->prop('form');
		if ($form['booking']['terms_and_conditions'] !== '1')
			return '';

		return Html::_div(
			array('class' => 'cgv input-group'),
			Html::_index('hidden', '', 'cgv_reservation', array('value' => '0')),
			Html::_index(
				'checkbox',
				'cgv_reservation',
				'cgv_reservation',
				array(
					'class' => 'cgv_reservation required',
					'value' => "1",
					'data-msg-required' => $warning_msg,
				),
				false,
				false,
				(array_key_exists('cgv_reservation', $post) ? $post['cgv_reservation'] : '0') == '1'
			),
			Html::_label('cgv_reservation', $msg),
		);
	}

	public function total(): string
	{
		return Html::_div(
			array(),
			Html::_div(
				array(
					'class' => 'total form-control',
					'id' => 'total',
				),
				esc_html(__('Order amount', 'parking-management')),
				'<span>0€</span>'
			)
		);
	}

	public function submit(): string
	{
		return Html::_div(
			array(
				'class' => 'mt-4 row justify-content-md-center',
			),
			Html::_div(array(
				'class' => 'col-sm-4',
			),
				Html::_index('hidden', 'nb_jour_offert', 'nb_jour_offert', array('value' => '0')),
				Html::_index('hidden', 'total_amount', 'total_amount', array('value' => '0')),
				'<button type="submit" id="submit" name="submit" class="form-control text-center" disabled>'
				. esc_html(__('Validate your order', 'parking-management'))
				. ' <i class="fa-regular fa-circle-right"></i>'
				. '</button>'

			),

		);
	}
}
