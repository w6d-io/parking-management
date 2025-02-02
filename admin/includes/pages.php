<?php

namespace ParkingManagement\Admin;

use ParagonIE\Sodium\Core\Curve25519\H;
use ParkingManagement\Html;
use ParkingManagement\ParkingManagement;
use ParkingManagement\Template;

class Pages
{
	public static function management(): void
	{
		$pm = ParkingManagement::get_current();
		echo '<div class="wrap pkmgmt-parking-management-config">';
		echo '<h2>' . esc_html__('Parking Management', 'parking-management') . '</h2>';
		echo '<br class="clear"/>';
		if ($pm === null) {
			$_REQUEST['message'] = 'Failed to get config';
			do_action('pkmgmt_admin_notices');
		} else {
			do_action('pkmgmt_admin_notices');
			self::config_form($pm);
		}
		echo '</div>';
	}

	public static function notices_message(): void
	{
		$page = $_REQUEST['page'] ?? '';
		if ($page != 'pkmgmt') {
			return;
		}
		if (empty($_REQUEST['message']))
			return;
		$message = $_REQUEST['message'];
		if ('saved' == $message)
			$message = __('Configuration saved.', 'parking-management');
		if (empty($message))
			return;

		echo sprintf('<div id="message" class="updated"><p>%s</p></div>', esc_html($message));

	}

	private static function config_form(ParkingManagement $pm): void
	{
		global $plugin_page;

		echo '<form id="pkmgmt-admin-config" method="post">';
		settings_fields(ParkingManagement::post_type);
		do_settings_sections(ParkingManagement::post_type);
		self::config_form_hidden($plugin_page, $pm->id);
		echo '<div id="poststuff" class="metabox-holder">';
		self::config_form_header($pm);

		foreach (ParkingManagement::properties_available as $property => $config)
			do_meta_boxes(null, $property, $pm->prop($property));

		echo '</div>';
		echo '</form>';
		self::dialog_page_list();

	}

	private static function config_form_hidden(string $page, int $id): void
	{
		echo sprintf('
		<input type="hidden" id="page" name="page" value="%s" />
		<input type="hidden" id="post_ID" name="post_ID" value="%d" />
		<input type="hidden" id="hidden-action" name="action" value="save" />
		<input type="hidden" id="pkmgmt-locale" name="pkmgmt-locale" value="fr_FR">
		',
			$page,
			$id
		);
	}

	private static function config_form_header(ParkingManagement $pm): void
	{
		if (current_user_can('pkmgmt_edit', $pm->id)) {
			wp_nonce_field('pkmgmt-save_' . $pm->id);
		}
		echo '<div id="titlediv">';

		echo Html::_index("text", "pkmgmt-title", "pkmgmt-title", array(
			'class' => "wide",
			'placeholder' => esc_html__("Title", 'parking-management'),
			'size' => 80,
			'value' => esc_attr($pm->title)
		),
			!(current_user_can('pkmgmt_edit', $pm->id))
		);

		echo Html::_p(array(),
			esc_html__("Name", 'parking-management') . '<br/>',
			Html::_index("text", "pkmgmt-name", "pkmgmt-name", array(
				'class' => "wide",
				'size' => 80,
				'value' => esc_attr($pm->name)
			),
				!(current_user_can('pkmgmt_edit', $pm->id))
			)
		);
		echo self::_shortcode_field(
			'shortcode-form',
			'shortcode-form',
			esc_html__("Copy and paste this code into your page to include booking form.", 'parking-management'),
			"[parking-management type='form']"
		);
		echo self::_shortcode_field(
			'shortcode-valet',
			'shortcode-valet',
			esc_html__("Copy and paste this code into your page to include valet booking form.", 'parking-management'),
			"[parking-management type='valet']"
		);
		echo self::_shortcode_field(
			'shortcode-home-form',
			'shortcode-home-form',
			esc_html__("Copy and paste this code into your page to include home booking form.", 'parking-management'),
			"[parking-management type='home-form']"
		);
		echo self::_shortcode_field(
			'shortcode-price',
			'shortcode-price',
			esc_html__("Copy and paste this code into your page to include price table.", 'parking-management'),
			"[parking-management type='price']"
		);
		echo self::_shortcode_field(
			'shortcode-booked',
			'shortcode-booked',
			esc_html__("Copy and paste this code into your page to include booked message.", 'parking-management'),
			"[parking-management type='booked']"
		);
		echo self::_shortcode_field(
			'shortcode-high-season',
			'shortcode-high-season',
			esc_html__("Copy and paste this code into your page to include high-season message.", 'parking-management'),
			"[parking-management type='high-season']"
		);
		echo self::_shortcode_field(
			'shortcode-notification-confirmation',
			'shortcode-notification-confirmation',
			esc_html__("Copy and paste this code into your page where you want a notification confirmation after an order.", 'parking-management'),
			"[parking-management type='notification' action='confirmation']"
		);
		echo self::_shortcode_field(
			'shortcode-notification-cancellation',
			'shortcode-notification-cancellation',
			esc_html__("Copy and paste this code into your page where you want a notification cancellation after an order.", 'parking-management'),
			"[parking-management type='notification' action='cancellation']"
		);
		echo self::_shortcode_field(
			'shortcode-payment-paypal',
			'shortcode-payment-paypal',
			esc_html__("Copy and paste this code into your page to include paypal payment form.", 'parking-management'),
			"[parking-management type='payment' payment_provider='paypal']"
		);
		echo self::_shortcode_field(
			'shortcode-payment-payplug',
			'shortcode-payment-payplug',
			esc_html__("Copy and paste this code into your page to include payplug payment form.", 'parking-management'),
			"[parking-management type='payment' payment_provider='payplug']"
		);
		echo self::_shortcode_field(
			'shortcode-payment-mypos',
			'shortcode-payment-mypos',
			esc_html__("Copy and paste this code into your page to include mypos payment form.", 'parking-management'),
			"[parking-management type='payment' payment_provider='mypos']"
		);

		echo '<div class="save-pkmgmt">';
		echo Html::_index("submit", "pkmgmt-save", "pkmgmt-save", array(
			'class' => 'button-primary',
			'value' => esc_html__("Save", 'parking-management'),
		));
		echo '</div>';
		echo '</div>';
	}

	private static function _shortcode_field($id, $name, $title, $shortcode): string
	{
		return Html::_p(array('class' => 'mb-0'),
			$title,
			'<br/>',
			'<div class="input-container wide shortcode-div">',
			Html::_index(
				"text",
				$id,
				$name,
				array(
					'class' => "wide shortcode",
					'size' => 80,
					'value' => $shortcode
				),
				false,
				true,
			),
			'<div class="tooltip">',
			'<button class="shortcode-copy" type="button">',
			'<span class="tooltiptext">Copy to clipboard</span>',
			'<i class="fas fa-copy darkgray"></i>',
			'</button>',
			'</div>',
			'<span class="shortcode-copy-message">copied</span>',
			'</div>'
		);
	}

	private static function _label($for, ...$contents): string
	{
		return sprintf('<label for="%s">%s</label>', esc_attr($for), implode("", $contents));
	}


	private static function _field($id, $div_class, $name, $label_content, $value): string
	{
		return Html::_div(array('class' => $div_class),
			Html::_label($id, $label_content),
			'<br/>',
			Html::_index("text", $id, $name, array(
				'class' => 'wide',
				'size' => 80,
				'value' => $value
			))
		);
	}

	private static function _field_url($id, $div_class, $name, $label_content, $value): string
	{
		return Html::_div(array('class' => $div_class),
			Html::_label($id, $label_content),
			'<br/>',
			Html::_index("text", $id, $name, array(
				'class' => 'wide',
				'size' => 80,
				'value' => $value
			))
		);
	}

	private static function _field_password($id, $div_class, $name, $label_content, $value): string
	{
		return Html::_div(array('class' => $div_class),
			Html::_label($id, $label_content),
			'<br/>',
			Html::_password($id, $name, array('class' => 'password-container'), array(
				'class' => 'wide password-input',
				'size' => 80,
				'value' => $value
			))
		);
	}

	private static function _field_checkbox($id, $div_class, $name, $span_content, $values): string
	{
		if (!is_array($values)) {
			return Html::_div(array('class' => $div_class), "bad values type");
		}
		$contents = array();
		$contents[] = '<div class="pb-2">' . $span_content . '</div>';
		foreach ($values as $key => $value) {
			$contents[] = Html::_div(
				array('class' => 'form-check form-switch form-check-inline'),
				Html::_checkbox($id, $name, array('class' => $id . ' form-check-input'), $key, $value),
			);
		}
		return Html::_div(array('class' => $div_class),
			...$contents
		);
	}

	private static function _field_radio($id, $div_class, $name, array $elements, $value): string
	{
		$contents = array();
		foreach ($elements as $element) {
			$contents[] = Html::_div(
				array('class' => 'form-check form-check-inline'),
				Html::_radio($id . '_' . $element['id'], $name, $element['value'], array('class' =>' form-check-input'), $value == $element['value']),
				Html::_label_with_attr(
					array('class' => 'form-check-label'),
					$id . '_' . $element['id'], $element['label']),
			);
		}
		return Html::_div(array('class' => $div_class),
			...$contents
		);
	}

	private static function _field_select($id, $div_class, $name, $label_content, $options, $value): string
	{
		if (!is_array($options)) {
			return Html::_div(array('class' => $div_class), "bad values type");
		}

		return Html::_div(array('class' => $div_class),
			Html::_label($id, $label_content),
			'<br/>',
			Html::_select($id, $name, array(), $options, $value)
		);
	}

	private static function _field_textarea($id, $div_class, $name, $label_content, $value, $args = array()): string
	{
		$cols = array_key_exists('cols', $args) ? $args['cols'] : "100";
		$rows = array_key_exists('rows', $args) ? $args['rows'] : "12";
		return Html::_div(array('class' => $div_class),
			Html::_label($id, $label_content),
			'<br/>',
			Html::_textarea($id, $name, $value, $cols, $rows),
		);
	}

	private static function _field_page($id, $div_class, $name, $label_content, $value): string
	{
		return Html::_div(array('class' => $div_class),
			Html::_label($id, $label_content),
//			'<br/>',
			Html::_div(array('class' => 'input-group mb-3'),
				Html::_div(array('class' => 'input-group-prepend'),
					'<button type="button" class="btn btn-outline-primary pages-list " data-bs-toggle="modal" data-bs-target="#dialog-pages-list">' . esc_html__('Browse page', 'parking-management') . '</button>'
				),
				Html::_index("text", $id, $name, array(
					'class' => 'form-control pages-list',
					'size' => 80,
					'value' => $value
				))
			)
		);
	}

	private static function _field_payment($id, $div_class, $label_content, $name, $payment): string
	{
		$contents = array();
		$contents[] = '<div class="payment_field form-control pt-3">';
		$contents[] = '<div class="form-check form-switch form-check-inline">';
		$contents[] = Html::_checkbox($id, $name, array('class'=>'form-check-input'), 'enabled', $payment['enabled']);
		$contents[] = '</div>';
		if (array_key_exists('active-test', $payment)) {
			$contents[] = '<div class="form-check form-switch form-check-inline">';
			$contents[] = Html::_checkbox($id, $name, array('class'=>'form-check-input'), 'active-test', $payment['active-test']);
			$contents[] = '</div>';
		}
		if (array_key_exists('redirect-to-provider', $payment)) {
			$contents[] = '<div class="form-check form-switch form-check-inline">';
			$contents[] = Html::_checkbox($id, $name, array('class'=>'form-check-input'), 'redirect-to-provider', $payment['redirect-to-provider']);
			$contents[] = '</div>';
		}

		$contents[] = '</div>';
		foreach ($payment['properties'] as $key => $params) {
			$contents[] = Html::_index('hidden', $id . '-' . $key . '-' . $params['title'], $name . '[properties]' . '[' . $key . '][title]', array('value' => $params['title']));
			$contents[] = Html::_index('hidden', $id . '-' . $key . '-' . $params['type'], $name . '[properties]' . '[' . $key . '][type]', array('value' => $params['type']));
			$contents[] = match ($params['type']) {
				'password' => self::_field_password(
					$id . '-' . $key,
					$div_class . " form-control",
					$name . '[properties]' . '[' . $key . '][value]',
					$params['title'],
					$params['value']),
				'text' => self::_field(
					$id . '-' . $key,
					$div_class . " form-control",
					$name . '[properties]' . '[' . $key . '][value]',
					$params['title'],
					$params['value']),
				'url' => self::_field_url($id . '-' . $key, $div_class . " form-control", $name . '[properties]' . '[' . $key . '][value]', $params['title'], $params['value']),
				'page' => self::_field_page($id . '-' . $key, $div_class . " form-control", $name . '[properties]' . '[' . $key . '][value]', $params['title'], $params['value']),
				default => ''
			};
		}
		return Html::_fieldset(
			'<legend>' . $label_content . '</legend>',
			...$contents
		);
	}

	private static function _option_card(string $id, array $option): string
	{
		return '<div class="col-sm-3">'
			. '<div class="card">'
			. '<div class="card-body">'
			. '<h5 class="card-title">' . $option['title'] . '</h5>'
			. '<input type="hidden" value="' . $option['title'] . '" name="pkmgmt-form[options][' . $id . '][title]"/>'
			. '<div class="input-group mb-3">'
			. '<div class="input-group-text">'
			. '<input type="hidden" value="0" name="pkmgmt-form[options][' . $id . '][enabled]"/>'
			. Html::_index('checkbox', 'pkmgmt-form-options-' . $id . '-enabled', 'pkmgmt-form[options][' . $id . '][enabled]',
				array(
					'aria-label' => 'Enable the ' . $option['title'],
					"value" => '1',
					'class' => 'form-check-input mt-0'
				),
				false,
				false,
				$option['enabled'] === '1'

			)
			. '</div>'
			. '<span class="input-group-text">€</span>'
			. '<input type="number" class="form-control" name="pkmgmt-form[options][' . $id . '][price]" aria-label="Price for ' . $option['title'] . '" value="' . $option['price'] . '">'
			. '</div>'
			. '</div>'
			. '</div>'
			. '</div>';
	}

	public static function info_box($info, $box): void
	{
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('info-address', 'info_field', 'pkmgmt-info[address]', esc_html__('Address', 'parking-management'), $info['address']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('info-mobile', 'info_field', 'pkmgmt-info[mobile]', esc_html__('Mobile', 'parking-management'), $info['mobile']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('info-RCS', 'info_field', 'pkmgmt-info[RCS]', esc_html__('RCS', 'parking-management'), $info['RCS']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('info-email', 'info_field', 'pkmgmt-info[email]', esc_html__('Email', 'parking-management'), $info['email']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('info-terminal', 'info_field', 'pkmgmt-info[terminal]', esc_html__('Terminal', 'parking-management'), $info['terminal']);
		echo '</div>';


		echo '<div class="' . $box['id'] . '-fields">';

		echo '<div class="row">';
		echo '<div class="col">';
		echo self::_field_checkbox('info-vehicle-type', 'info_field', 'pkmgmt-info[vehicle_type]', esc_html__('Vehicle Type supported', 'parking-management'), $info['vehicle_type']);
		echo '</div>';
		echo '<div class="col">';
		echo self::_field_checkbox('info-type', 'info_field', 'pkmgmt-info[type]', esc_html__('Vehicle storage mode', 'parking-management'), $info['type']);
		echo '</div>';
		echo '</div>';

		echo '<div class="row">';
		echo '<div class="col">';
		echo self::_field_checkbox('info-logs', 'info_field', 'pkmgmt-info[logs]', 'Logs', $info['logs']);
		echo '</div>';
		echo '</div>';

		echo '</div>';
	}

	public static function database_box($database, $box): void
	{
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('database-name', 'database_field', 'pkmgmt-database[name]', esc_html__('Name', 'parking-management'), $database['name']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('database-host', 'database_field', 'pkmgmt-database[host]', esc_html__('Host', 'parking-management'), $database['host']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('database-port', 'database_field', 'pkmgmt-database[port]', esc_html__('Port', 'parking-management'), $database['port']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('database-user', 'database_field', 'pkmgmt-database[user]', esc_html__('Username', 'parking-management'), $database['user']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_password('database-password', 'database_field', 'pkmgmt-database[password]', esc_html__('Password', 'parking-management'), $database['password']);
		echo '</div>';
	}

	public static function payment_box($payment, $box): void
	{
		echo Html::_div(
			array('class' => 'form-check form-switch m-4'),
			Html::_checkbox('valid-booking-on-payment', 'pkmgmt-payment', array('class' => 'valid-booking-on-payment form-check-input'), 'valid-booking-on-payment', $payment['valid-booking-on-payment']),
		);
		echo '<div class="tabs">';
		echo '<ul class="tab-links">';
		echo '<li class="active"><a href="#payment-payplug-tab">Payplug</a></li>';
		echo '<li><a href="#payment-mypos-tab">MyPOS</a></li>';
		echo '<li><a href="#payment-paypal-tab">Paypal</a></li>';
		echo '</ul>';
		echo '<div class="tab-content">';
		echo '<div id="payment-payplug-tab" class="tab">';
		echo self::_field_payment($box['id'] . '-payplug', $box['id'] . '_field', 'Payplug', 'pkmgmt-payment[providers][payplug]', $payment['providers']['payplug']);
		echo '</div>';
		echo '<div id="payment-mypos-tab" class="tab">';
		echo self::_field_payment($box['id'] . '-mypos', $box['id'] . '_field', 'MyPOS', 'pkmgmt-payment[providers][mypos]', $payment['providers']['mypos']);
		echo '</div>';
		echo '<div id="payment-paypal-tab" class="tab active">';
		echo self::_field_payment($box['id'] . '-paypal', $box['id'] . '_field', 'Paypal', 'pkmgmt-payment[providers][paypal]', $payment['providers']['paypal']);
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	public static function form_box($form, $box): void
	{
		$payment_elements = [
			[
				'id' => 0,
				'label' => 'None',
				'value' => '',
			],
			[
				'id' => 1,
				'label' => 'PayPlug',
				'value' => 'payplug',
			],
			[
				'id' => 2,
				'label' => 'MyPOS',
				'value' => 'mypos',
			],
			[
				'id' => 3,
				'label' => 'PayPal',
				'value' => 'paypal',
			]
		];
		echo '<div class="text-center">';

		echo '<div class="form-control">';
		echo '<h6>Booking form</h6>';
		echo '<div class="form-control">';
		echo '<div class="row text-start">';
		echo '<div class="pb-2">Redirect payment</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_radio(
			'form-payment',
			'col form_field',
			'pkmgmt-form[payment]',
			$payment_elements,
			 $form['payment']);
		echo '</div>';
		echo '</div>';
		echo '</div>';

		echo '<div class="row">';

		echo '<div class="col ' . $box['id'] . '-fields">';
		echo Html::_index('hidden', 'form-booking-page-title', 'pkmgmt-form[booking_page][title]', array('value' => $form['booking_page']['title']));
		echo self::_field_page('form-booking-page', "form-control", 'pkmgmt-form[booking_page][value]', $form['booking_page']['title'], $form['booking_page']['value']);
		echo '</div>';
		echo '<div class="col ' . $box['id'] . '-fields">';
		echo Html::_index('hidden', 'form-validation-page-title', 'pkmgmt-form[validation_page][title]', array('value' => $form['validation_page']['title']));
		echo self::_field_page('form-validation-page', "form-control", 'pkmgmt-form[validation_page][value]', $form['validation_page']['title'], $form['validation_page']['value']);
		echo '</div>';

		echo '</div>';
		echo '</div>';
		echo '</div>';



		echo '<div class="text-center">';
		echo '<div class="form-control">';
		echo '<h6>Valet form</h6>';
		echo '<div class="form-control">';
		echo '<div class="row text-start">';
		echo '<div class="pb-2">Redirect payment</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_radio(
			'form-valet-payment',
			'col form_field',
			'pkmgmt-form[valet][payment]',
			$payment_elements,
			$form['valet']['payment']);
		echo '</div>';
		echo '</div>';
		echo '</div>';

		echo '<div class="row">';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_page('form-valet-validation-page', "form-control", 'pkmgmt-form[valet][validation_page][value]', $form['valet']['validation_page']['title'], $form['valet']['validation_page']['value']);
		echo '</div>';

		echo '</div>';
		echo '</div>';
		echo '</div>';

		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('form-indicative', 'form_field', 'pkmgmt-form[indicative]', esc_html__('Indicative', 'parking-management'), $form['indicative']);
		echo '</div>';


		echo '<div class="' . $box['id'] . '-fields">';
		echo '<div class="my-3">';
		echo self::_field_checkbox('form-booking', 'form-control form_field', 'pkmgmt-form[booking]', esc_html__('Booking', 'parking-management'), $form['booking']);
		echo '</div>';
		echo '</div>';

		echo '<div class="' . $box['id'] . '-fields">';
		echo '<div class="row">';

		foreach ($form['options'] as $id => $option) {
			echo self::_option_card($id, $option);
		}

		echo '</div>';
		echo '</div>';

	}

	public static function booked_dates_box($booked_dates, $box): void
	{
		echo '<div ' . $box['id'] . 'class="dates-global">';
		echo '<div class="dates-header">';
		echo '<span>' . esc_html__('Add a date', 'parking-management') . '</span>';
		echo '<button id="full-dates-add-element" type="button"><i class="fas fa-plus"></i></button>';
		echo '</div>';
		echo '<div id="booked_dates_body" class="dates-body">';
		foreach ($booked_dates as $id => $date) {
			echo '<div>';
			echo '<div class="dates-element">';
			echo '<label for="pkmgmt-booked-dates-start-' . $id . '">start</label>';
			echo '<input type="date" id="pkmgmt-booked-dates-start-' . $id . '" name="pkmgmt-booked_dates[' . $id . '][start]" class="start-date" value="' . $date['start'] . '">';
			echo '<label for="pkmgmt-booked-dates-end-' . $id . '">end</label>';
			echo '<input type="date" id="pkmgmt-booked-dates-end-' . $id . '" name="pkmgmt-booked_dates[' . $id . '][end]" class="end-date" value="' . $date['end'] . '">';
			echo '<label for="pkmgmt-booked-dates-message-' . $id . '">message</label>';
			echo '<input type="text" id="pkmgmt-booked-dates-message-' . $id . '" name="pkmgmt-booked_dates[' . $id . '][message]" class="message" placeholder="Message" value="' . $date['message'] . '">';
			echo '<i class="fas fa-trash delete"></i>';
			echo '</div>';
			echo '</div>';
		}
		echo '</div>';
		echo '</div>';
	}

	public static function high_season_box($high_season, $box): void
	{
		echo '<div ' . $box['id'] . 'class="dates-global">';
		echo '<div class="dates-header justify-content-between">';
		echo '<div class="col-sm-1">';
		echo '<div class="input-group">';
		echo '<span class="input-group-text m-0">€</span>';
		echo '<input type="number" class="form-control" name="pkmgmt-high_season[price]" aria-label="Price for High season" value="' . $high_season['price'] . '">';
		echo '</div>';
		echo '</div>';
		echo '<div>';
		echo '<span>' . esc_html__('Add a date', 'parking-management') . '</span>';
		echo '<button id="high-season-add-element" type="button"><i class="fas fa-plus"></i></button>';
		echo '</div>';
		echo '</div>';
		echo '<div id="high_season_dates_body" class="dates-body">';
		if (array_key_exists('dates', $high_season)) {
			foreach ($high_season['dates'] as $id => $date) {
				echo '<div class="dates-element">';
				echo '<label for="pkmgmt-high-season-dates-start-' . $id . '">start</label>';
				echo '<input type="date" id="pkmgmt-high-season-dates-start-' . $id . '" name="pkmgmt-high_season[dates][' . $id . '][start]" class="start-date" value="' . $date['start'] . '">';
				echo '<label for="pkmgmt-high-season-dates-end-' . $id . '">end</label>';
				echo '<input type="date" id="pkmgmt-high-season-dates-end-' . $id . '" name="pkmgmt-high_season[dates][' . $id . '][end]" class="end-date" value="' . $date['end'] . '">';
				echo '<label for="pkmgmt-high-season-dates-message-' . $id . '">message</label>';
				echo '<input type="text" id="pkmgmt-high-season-dates-message-' . $id . '" name="pkmgmt-high_season[dates][' . $id . '][message]" class="message" placeholder="Message" value="' . $date['message'] . '">';
				echo '<i class="fas fa-trash delete"></i>';
				echo '</div>';
			}
		}
		echo '</div>';
		echo '</div>';
	}

	public static function notification_box($notification, $box): void
	{

		echo '<div class="' . $box['id'] . '-fields">';
		echo '<nav>';
		echo '<div class="nav nav-tabs" id="nav-notification-tab" role="tablist">';

		echo Html::_button(
			array(
				'class' => 'nav-link',
				'id' => 'nav-notification-mail-tab',
				'data-bs-toggle' => 'tab',
				'data-bs-target' => '#nav-notification-mail',
				'type' => 'button',
				'role' => 'tab',
				'aria-controls' => 'nav-notification-mail',
			), 'Mail'
		);
		echo Html::_button(
			array(
				'class' => 'nav-link',
				'id' => 'nav-notification-sms-tab',
				'data-bs-toggle' => 'tab',
				'data-bs-target' => '#nav-notification-sms',
				'type' => 'button',
				'role' => 'tab',
				'aria-controls' => 'nav-notification-sms',
			), 'SMS'
		);

		echo '</div>';
		echo '</nav>';

		echo '<div class="tab-content" id="nav-notification-tab-content">';

		echo '<div class="tab-pane fade" id="nav-notification-mail" role="tabpanel" aria-labelledby="nav-notification-mail-tab" tabindex="0">';
		self::mail_box($notification['mail'], $box);
		echo '</div>';
		echo '<div class="tab-pane fade" id="nav-notification-sms" role="tabpanel" aria-labelledby="nav-notification-sms-tab" tabindex="0">';
		self::sms_box($notification['sms'], $box);
		echo '</div>';

		echo '</div>';
		echo '</div>';
	}

	private static function mail_box($mail, $box): void
	{
		echo Html::_div(array('class' => $box['id'] . '-fields'),
			Html::_index('hidden', 'mail-host-title', 'pkmgmt-notification[mail][host][title]', array('value' => $mail['host']['title'])),
			Html::_index('hidden', 'mail-host-type', 'pkmgmt-notification[mail][host][type]', array('value' => $mail['host']['type'])),
			self::_field('mail-host', 'mail-field', 'pkmgmt-notification[mail][host][value]', $mail['host']['title'], $mail['host']['value'])
		);
		echo Html::_div(array('class' => $box['id'] . '-fields'),
			Html::_index('hidden', 'mail-login-title', 'pkmgmt-notification[mail][login][title]', array('value' => $mail['login']['title'])),
			Html::_index('hidden', 'mail-login-type', 'pkmgmt-notification[mail][login][type]', array('value' => $mail['login']['type'])),
			self::_field('mail-login', 'mail-field', 'pkmgmt-notification[mail][login][value]', $mail['login']['title'], $mail['login']['value'])
		);
		echo Html::_div(array('class' => $box['id'] . '-fields'),
			Html::_index('hidden', 'mail-password-title', 'pkmgmt-notification[mail][password][title]', array('value' => $mail['password']['title'])),
			Html::_index('hidden', 'mail-password-type', 'pkmgmt-notification[mail][password][type]', array('value' => $mail['password']['type'])),
			self::_field_password('mail-login', 'mail-field', 'pkmgmt-notification[mail][password][value]', $mail['password']['title'], $mail['password']['value']),
		);
		echo Html::_div(array('class' => $box['id'] . '-fields'),
			Html::_index('hidden', 'mail-sender-title', 'pkmgmt-notification[mail][sender][title]', array('value' => $mail['sender']['title'])),
			Html::_index('hidden', 'mail-sender-type', 'pkmgmt-notification[mail][sender][type]', array('value' => $mail['sender']['type'])),
			Html::_div(array('class' => 'mail-field'),
				Html::_label('mail-sender', $mail['sender']['title']),
				'<br/>',
				Html::_index("email", 'mail-sender', 'pkmgmt-notification[mail][sender][value]', array(
					'class' => 'wide',
					'size' => 80,
					'value' => $mail['sender']['value']
				))
			),
		);
		if (empty($mail['templates'])) return;
		echo '<div class="' . $box['id'] . '-fields">';
		echo Html::_label('nav-mail-tab', 'Templates');
		echo '<nav>';
		echo '<div class="nav nav-tabs" id="nav-mail-tab" role="tablist">';
		foreach ($mail['templates'] as $id => $template) {
			echo Html::_button(array(
				'class' => 'nav-link',
				'id' => 'nav-mail-' . $id . '-tab',
				'data-bs-toggle' => 'tab',
				'data-bs-target' => '#nav-mail-' . $id,
				'type' => 'button',
				'role' => 'tab',
				'aria-controls' => 'nav-mail-' . $id,
			),
				$template['title']
			);
		}
		echo '</div>';
		echo '</nav>';
		echo '<div class="tab-content" id="nav-mail-tab-content">';
		foreach ($mail['templates'] as $id => $template) {
			echo '<div class="tab-pane fade" id="nav-mail-' . $id . '" role="tabpanel" aria-labelledby="nav-mail-' . $id . '-tab" tabindex="0">';
			echo Html::_index('hidden', 'mail-templates-' . $id . '-title', 'pkmgmt-notification[mail][templates][' . $id . '][title]', array('value' => $mail['templates'][$id]['title']));
			echo Html::_index('hidden', 'mail-templates-' . $id . '-type', 'pkmgmt-notification[mail][templates][' . $id . '][type]', array('value' => $mail['templates'][$id]['type']));
			wp_editor($template['value'], 'mail-' . $id . '-value', [
				'textarea_name' => 'pkmgmt-notification[mail][templates][' . $id . '][value]',
				'textarea_rows' => 30,
				'tabindex' => "0",
				'teeny' => true,
				'media_buttons' => false,
			]);
			echo '</div>';
		}
		echo '</div>';
		echo '</div>';
	}

	private static function sms_box($sms, $box): void
	{
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_select('sms-type', 'sms_field', 'pkmgmt-notification[sms][type]', esc_html__('Type', 'parking-management'),
			array(
				array(
					'value' => 'AWS',
					'label' => 'AWS',
				),
				array(
					'value' => 'OVH',
					'label' => 'OVH',
				)
			),
			$sms['type']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('sms-user', 'sms_field', 'pkmgmt-notification[sms][user]', esc_html__('Username', 'parking-management'), $sms['user']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_password('sms-password', 'sms_field', 'pkmgmt-notification[sms][password]', esc_html__('Password', 'parking-management'), $sms['password']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('sms-sender', 'sms_field', 'pkmgmt-notification[sms][sender]', esc_html__('Sender', 'parking-management'), $sms['sender']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_textarea('sms-template', 'sms_field', 'pkmgmt-notification[sms][template]', esc_html__('Template', 'parking-management'), $sms['template'], array('cols' => "0"));
		echo '</div>';
	}

	public static function dialog_page_list(): void
	{
		$contents = array();
		$contents[] = '<div id="dialog-pages-list" class="modal fade" tabindex="-1" aria-labelledby="pageList" aria-hidden="true">';
		$contents[] = '<div class="modal-dialog modal-lg modal-dialog-centered">';
		$contents[] = '<div class="modal-content">';
		$contents[] = '<div class="modal-header">';
		$contents[] = '<h1 class="modal-title fs-5" id="pageList">' . esc_html__("Select a page", 'parking-management') . '</h1>';
		$contents[] = '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
		$contents[] = '</div>';
		$contents[] = '<div class="modal-body">';
		$contents[] = '<ul id="pageList" class="list-group overflow-auto">';
		$pages = get_pages();
		foreach ($pages as $page) {
			$contents[] = '<li class="page list-group-item list-group-item-action" data-url="' . get_page_link($page->ID) . '">' . $page->post_title . '</li>';
		}

		$contents[] = '</ul>';
		$contents[] = '</div>';
		$contents[] = '</div>';
		$contents[] = '</div>';
		$contents[] = '</div>';
		echo implode(PHP_EOL, $contents);
	}

}
