<?php

namespace ParkingManagement\Admin;

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

		foreach (ParkingManagement::properties_available as $property => $config) {
			do_meta_boxes(null, $property, $pm->prop($property));
		}
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
			"[parking-management type='form' kind='booking']"
		);
		echo self::_shortcode_field(
			'shortcode-form-valet',
			'shortcode-form-valet',
			esc_html__("Copy and paste this code into your page to include valet booking form.", 'parking-management'),
			"[parking-management type='form' kind='valet']"
		);
		echo self::_shortcode_field(
			'shortcode-home-form',
			'shortcode-home-form',
			esc_html__("Copy and paste this code into your page to include home booking form.", 'parking-management'),
			"[parking-management type='home-form']"
		);
		echo self::_shortcode_field(
			'shortcode-price-booking',
			'shortcode-price-booking',
			esc_html__("Copy and paste this code into your page to include price booking table.", 'parking-management'),
			"[parking-management type='price' kind='booking']"
		);
		echo self::_shortcode_field(
			'shortcode-price-valet',
			'shortcode-price-valet',
			esc_html__("Copy and paste this code into your page to include price valet table.", 'parking-management'),
			"[parking-management type='price' kind='valet']"
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
		echo '<div class="save-pkmgmt">';
		echo Html::_index("submit", "pkmgmt-save", "pkmgmt-save", array(
			'class' => 'button-primary',
			'value' => esc_html__("Save", 'parking-management'),
		));
		echo '</div>';
		echo '</div>';
	}

	private static function _fieldset(string $legend, ...$contents): string
	{
		return Html::_fieldset_with_attr(
			['class' => "border border-dark p-3 position-relative my-4"],
			'<legend class="w-auto mx-2 bg-white position-absolute top-0 start-0 translate-middle-y fs-6">' . $legend . '</legend>',
			...$contents);
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

	private static function _field_payment($id, $div_class, $name, $properties): string
	{
		$contents = array();
		foreach ($properties as $key => $params) {
			$contents[] = Html::_index('hidden', $id . '-' . $key . '-title', $name . '[' . $key . '][title]', array('value' => $params['title']));
			$contents[] = Html::_index('hidden', $id . '-' . $key . '-type', $name . '[' . $key . '][type]', array('value' => $params['type']));
			$contents[] = match ($params['type']) {
				'password' => self::_field_password(
					$id . '-' . $key,
					$div_class . " form-control",
					$name . '[' . $key . '][value]',
					$params['title'],
					$params['value']),
				'text' => self::_field(
					$id . '-' . $key,
					$div_class . " form-control",
					$name . '[' . $key . '][value]',
					$params['title'],
					$params['value']),
				'url' => self::_field_url(
					$id . '-' . $key,
					$div_class . " form-control",
					$name . '[' . $key . '][value]',
					$params['title'],
					$params['value']),
				'page' => self::_field_page(
					$id . '-' . $key,
					$div_class . " form-control",
					$name . '[' . $key . '][value]',
					$params['title'],
					$params['value']),
				default => ''
			};
		}
		return self::_fieldset(
			'Properties',
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

	private static function notification_template($id, $name, array|string $templates): void
	{
		echo '<div class="' . $id . '-fields">';
		if (is_array($templates)) {
			if (empty($templates)) {
				$templates = [
					'confirmation' => [
						'title' => 'Confirmation',
						'type' => 'textarea',
						'value' => ''
					],
					'cancellation' => [
						'title' => 'Cancellation',
						'type' => 'textarea',
						'value' => ''
					],
				];
			}
			echo Html::_label("nav-$id-tab", 'Templates');
			echo '<nav>';
			echo '<div class="nav nav-tabs" id="nav-' . $id . '-tab" role="tablist">';
			foreach ($templates as $key => $template) {
				echo Html::_button(array(
					'class' => 'nav-link',
					'id' => "nav-$id-" . $key . '-tab',
					'data-bs-toggle' => 'tab',
					'data-bs-target' => "#nav-${id}-" . $key,
					'type' => 'button',
					'role' => 'tab',
					'aria-controls' => "nav-${id}-" . $key,
				),
					$template['title']
				);
			}
			echo '</div>';
			echo '</nav>';
			echo '<div class="tab-content" id="nav-' . $id . '-tab-content">';
			foreach ($templates as $key => $template) {
				echo '<div class="tab-pane fade" id="nav-' . $id . '-' . $key . '" role="tabpanel" aria-labelledby="nav-' . $id . '-' . $key . '-tab" tabindex="0">';
				echo Html::_index('hidden', $id . '-templates-' . $key . '-title', $name . "[" . $key . '][title]', array('value' => $template['title']));
				echo Html::_index('hidden', $id . '-templates-' . $key . '-type', $name . "[" . $key . '][type]', array('value' => $template['type']));
				wp_editor($template['value'], $id . '-' . $key . '-value', [
					'textarea_name' => $name . "[" . $key . '][value]',
					'textarea_rows' => 30,
					'tabindex' => "0",
					'teeny' => true,
					'media_buttons' => false,
				]);
				echo '</div>';
			}
			echo '</div>';
		} else {
			echo self::_field_textarea($id . '-template', $id . '_field', $name, esc_html__('Template', 'parking-management'), $templates, array('cols' => "0"));
		}
		echo '</div>';
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

	private static function database($database, $box, $name): void
	{
		$contents = [];
		$contents[] = '<div class="' . $box['id'] . '-fields">';
		$contents[] = self::_field($name . '-database-name', 'database_field', 'pkmgmt-' . $name . '[database][name]', esc_html__('Name', 'parking-management'), $database['name']);
		$contents[] = self::_field($name . '-database-host', 'database_field', 'pkmgmt-' . $name . '[database][host]', esc_html__('Host', 'parking-management'), $database['host']);
		$contents[] = self::_field($name . '-database-port', 'database_field', 'pkmgmt-' . $name . '[database][port]', esc_html__('Port', 'parking-management'), $database['port']);
		$contents[] = self::_field($name . '-database-user', 'database_field', 'pkmgmt-' . $name . '[database][user]', esc_html__('Username', 'parking-management'), $database['user']);
		$contents[] = self::_field_password($name . '-database-password', 'database_field', 'pkmgmt-' . $name . '[database][password]', esc_html__('Password', 'parking-management'), $database['password']);
		$contents[] = '</div>';
		echo self::_fieldset('Database', ...$contents);
	}

	private static function payment($id, $payment, $name): void
	{
		$payment_name = $payment['name'] ?? 'payplug';
		if (!array_key_exists('properties', $payment) || empty($payment['properties'])) {
			$payment['properties'] = Template::payment_properties();
		}
		$contents = [];
		$contents[] = Html::_div(
			array('class' => 'form-check form-switch form-check-inline'),
			Html::_checkbox($id, $name, array('class' => 'form-check-input'), 'enabled', $payment['enabled'])
		);
		$contents[] = Html::_div(
			array('class' => 'form-check form-switch form-check-inline'),
			Html::_checkbox($id, $name, array('class' => 'valid-on-payment form-check-input'), 'valid-on-payment', $payment['valid-on-payment']),
		);
		$contents[] = Html::_div(
			array('class' => 'form-check form-switch form-check-inline'),
			Html::_checkbox($id, $name, array('class' => 'redirect-to-provider form-check-input'), 'redirect-to-provider', $payment['redirect-to-provider']),
		);
		$contents[] = Html::_div(
			array('class' => 'form-check form-switch form-check-inline'),
			Html::_checkbox($id, $name, array('class' => 'form-check-input'), 'active-test', $payment['active-test'])
		);

		$contents_radio[] = '<div class="mt-1" id="' . $id . '-providers-select' . '">';
		foreach ($payment['properties'] as $provider => $property) {
			$contents_radio[] = Html::_div(
				['class' => 'form-check form-check-inline'],

				Html::_radio(
					$id . "-{$provider}-radio",
					$name . '[name]',
					"$provider",
					[
						'class' => 'form-check-input',
//						'data-bs-toggle' => "tab",
						'data-bs-target' => "#nav-{$id}-{$provider}",
						'data-bs-content' => "#nav-{$id}-tab-content",
					],
					$payment_name == $provider
				),
				Html::_label_with_attr(
					[
						'class' => 'form-check-label',
					],
					$id . "-{$provider}-radio",
					ucfirst($provider))
			);
		}
		$contents_radio[] = '</div>';
		$contents[] = self::_fieldset('Providers', ...$contents_radio);

		$contents[] = '<div class="tab-content" id="nav-' . $id . '-tab-content">';
		foreach ($payment['properties'] as $provider => $property) {

			$contents[] = Html::_div(
				[
					'class' => 'tab-pane fade' . (($payment_name == $provider) ? ' show active' : ''),
					'id' => "nav-{$id}-{$provider}",
					'role' => "tabpanel",
				],
				self::_field_payment(
					"{$id}-{$provider}",
					"{$id}_field",
					"{$name}[properties][${provider}]",
					$payment['properties'][$provider]
				)
			);
		}
		$contents[] = '</div>';

		echo self::_fieldset('Payment', ...$contents);

	}

	private static function mail($mail, $box, $name = 'mail'): void
	{
		echo Html::_div(array('class' => $box['id'] . '-fields'),
			Html::_index('hidden', $name . '-host-title', "pkmgmt-notification[${name}][host][title]", array('value' => $mail['host']['title'])),
			Html::_index('hidden', $name . '-host-type', "pkmgmt-notification[${name}][host][type]", array('value' => $mail['host']['type'])),
			self::_field($name . '-host', $name . '-field', "pkmgmt-notification[${name}][host][value]", $mail['host']['title'], $mail['host']['value'])
		);
		echo Html::_div(array('class' => $box['id'] . '-fields'),
			Html::_index('hidden', $name . '-login-title', "pkmgmt-notification[${name}][login][title]", array('value' => $mail['login']['title'])),
			Html::_index('hidden', $name . '-login-type', "pkmgmt-notification[${name}][login][type]", array('value' => $mail['login']['type'])),
			self::_field($name . '-login', $name . '-field', "pkmgmt-notification[${name}][login][value]", $mail['login']['title'], $mail['login']['value'])
		);
		echo Html::_div(array('class' => $box['id'] . '-fields'),
			Html::_index('hidden', $name . '-password-title', "pkmgmt-notification[${name}][password][title]", array('value' => $mail['password']['title'])),
			Html::_index('hidden', $name . '-password-type', "pkmgmt-notification[${name}][password][type]", array('value' => $mail['password']['type'])),
			self::_field_password($name . '-login', $name . '-field', "pkmgmt-notification[${name}][password][value]", $mail['password']['title'], $mail['password']['value']),
		);
		echo Html::_div(array('class' => $box['id'] . '-fields'),
			Html::_index('hidden', $name . '-sender-title', "pkmgmt-notification[${name}][sender][title]", array('value' => $mail['sender']['title'])),
			Html::_index('hidden', $name . '-sender-type', "pkmgmt-notification[${name}][sender][type]", array('value' => $mail['sender']['type'])),
			Html::_div(array('class' => $name . '-field'),
				Html::_label($name . '-sender', $mail['sender']['title']),
				'<br/>',
				Html::_index("email", $name . '-sender', "pkmgmt-notification[${name}][sender][value]", array(
					'class' => 'wide',
					'size' => 80,
					'value' => $mail['sender']['value']
				))
			),
		);
	}

	private static function sms($sms, $box): void
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
	}


	public static function form_box($form, $box): void
	{
		echo '<div class="col ' . $box['id'] . '-fields">';
		echo Html::_index('hidden', 'form-booking-page-title', 'pkmgmt-form[booking_page][title]', array('value' => $form['booking_page']['title']));
		echo self::_field_page('form-booking-page', "form-control", 'pkmgmt-form[booking_page][value]', $form['booking_page']['title'], $form['booking_page']['value']);
		echo '</div>';
		echo '<div class="col ' . $box['id'] . '-fields">';
		echo Html::_index('hidden', 'form-valet-page-title', 'pkmgmt-form[valet_page][title]', array('value' => $form['valet_page']['title']));
		echo self::_field_page('form-valet-page', "form-control", 'pkmgmt-form[valet_page][value]', $form['valet_page']['title'], $form['valet_page']['value']);
		echo '</div>';

		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('form-indicative', 'form_field', 'pkmgmt-form[indicative]', esc_html__('Indicative', 'parking-management'), $form['indicative']);
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
				'id' => 'nav-notification-valet-tab',
				'data-bs-toggle' => 'tab',
				'data-bs-target' => '#nav-notification-valet',
				'type' => 'button',
				'role' => 'tab',
				'aria-controls' => 'nav-notification-valet',
			), 'Valet'
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
		self::mail($notification['mail'], $box);
		echo '</div>';

		echo '<div class="tab-pane fade" id="nav-notification-valet" role="tabpanel" aria-labelledby="nav-notification-valet-tab" tabindex="0">';
		self::mail($notification['valet'], $box, 'valet');
		echo '</div>';

		echo '<div class="tab-pane fade" id="nav-notification-sms" role="tabpanel" aria-labelledby="nav-notification-sms-tab" tabindex="0">';
		self::sms($notification['sms'], $box);
		echo '</div>';

		echo '</div>';
		echo '</div>';
	}

	public static function booking_box($booking, $box): void
	{
		echo self::_shortcode_field(
			'shortcode-payment-payplug-booking',
			'shortcode-payment-payplug-booking',
			esc_html__("Copy and paste this code into your page to include payplug payment form.", 'parking-management'),
			"[parking-management type='payment' payment_provider='payplug' kind='booking']"
		);
		echo self::_shortcode_field(
			'shortcode-payment-mypos-booking',
			'shortcode-payment-mypos-booking',
			esc_html__("Copy and paste this code into your page to include mypos payment form.", 'parking-management'),
			"[parking-management type='payment' payment_provider='mypos' kind='booking']"
		);
		echo self::_shortcode_field(
			'shortcode-payment-paypal-booking',
			'shortcode-payment-paypal-booking',
			esc_html__("Copy and paste this code into your page to include paypal payment form.", 'parking-management'),
			"[parking-management type='payment' payment_provider='paypal' kind='booking']"
		);
		echo self::_shortcode_field(
			'shortcode-notification-confirmation-booking',
			'shortcode-notification-confirmation-booking',
			esc_html__("Copy and paste this code into your page where you want a notification confirmation after an order.", 'parking-management'),
			"[parking-management type='notification' action='confirmation' kind='booking']"
		);
		echo self::_shortcode_field(
			'shortcode-notification-cancellation-booking',
			'shortcode-notification-cancellation-booking',
			esc_html__("Copy and paste this code into your page where you want a notification cancellation after an order.", 'parking-management'),
			"[parking-management type='notification' action='cancellation' kind='booking']"
		);
		$booking['validation_page'] = [
			'title' => 'Validation Page',
			'value' => ''
		];
		echo self::_fieldset('Pages',
			Html::_div(
				[
					'class' => 'col' . $box['id'] . '-fields',
				],
				Html::_index('hidden', 'booking-validation-page-title', 'pkmgmt-booking[validation_page][title]', array('value' => $booking['validation_page']['title'])),
				self::_field_page('booking-validation-page', "", 'pkmgmt-booking[validation_page][value]', $booking['validation_page']['title'], $booking['validation_page']['value']),
			)
		);

		echo '<div class="' . $box['id'] . '-fields">';
		echo '<div class="my-3">';
		echo self::_field_checkbox('booking', 'form-control booking_field', 'pkmgmt-booking[options]', 'Options', $booking['options']);
		echo '</div>';
		echo '</div>';

		self::database($booking['database'], $box, 'booking');

		self::payment($box['id'] . '-payment', $booking['payment'], 'pkmgmt-booking[payment]');

		echo '<fieldset class="border border-dark p-3 position-relative my-4">';
		echo '<legend class="w-auto mx-2 bg-white position-absolute top-0 start-0 translate-middle-y fs-6">Mail</legend>';
		self::notification_template($box['id'] . '-mail', 'pkmgmt-booking[mail_templates]', $booking['mail_templates']);
		echo '</fieldset>';
		echo '<fieldset class="border border-dark p-3 position-relative my-4">';
		echo '<legend class="w-auto mx-2 bg-white position-absolute top-0 start-0 translate-middle-y fs-6">SMS</legend>';
		self::notification_template($box['id'] . '-sms', 'pkmgmt-booking[sms_template]', $booking['sms_template']);
		echo '</fieldset>';
	}

	public static function valet_box($valet, $box): void
	{
		echo self::_shortcode_field(
			'shortcode-payment-payplug-valet',
			'shortcode-payment-payplug-valet',
			esc_html__("Copy and paste this code into your page to include payplug payment form.", 'parking-management'),
			"[parking-management type='payment' payment_provider='payplug' kind='valet']"
		);
		echo self::_shortcode_field(
			'shortcode-payment-mypos-valet',
			'shortcode-payment-mypos-valet',
			esc_html__("Copy and paste this code into your page to include mypos payment form.", 'parking-management'),
			"[parking-management type='payment' payment_provider='mypos' kind='valet']"
		);
		echo self::_shortcode_field(
			'shortcode-payment-paypal-valet',
			'shortcode-payment-paypal-valet',
			esc_html__("Copy and paste this code into your page to include paypal payment form.", 'parking-management'),
			"[parking-management type='payment' payment_provider='paypal' kind='valet']"
		);
		echo self::_shortcode_field(
			'shortcode-notification-confirmation-valet',
			'shortcode-notification-confirmation-valet',
			esc_html__("Copy and paste this code into your page where you want a notification confirmation after an order.", 'parking-management'),
			"[parking-management type='notification' action='confirmation' kind='valet']"
		);
		echo self::_shortcode_field(
			'shortcode-notification-cancellation-valet',
			'shortcode-notification-cancellation-valet',
			esc_html__("Copy and paste this code into your page where you want a notification cancellation after an order.", 'parking-management'),
			"[parking-management type='notification' action='cancellation' kind='valet']"
		);

		$valet['validation_page'] = [
			'title' => 'Validation Page',
			'value' => ''
		];
		echo self::_fieldset('Pages',
			Html::_div(
				[
					'class' => 'col' . $box['id'] . '-fields',
				],
				Html::_index('hidden', 'valet-validation-page-title', 'pkmgmt-valet[validation_page][title]', array('value' => $valet['validation_page']['title'])),
				self::_field_page('valet-validation-page', "", 'pkmgmt-valet[validation_page][value]', $valet['validation_page']['title'], $valet['validation_page']['value']),
			)
		);

		echo '<div class="' . $box['id'] . '-fields">';
		echo '<div class="my-3">';
		echo self::_field_checkbox('valet', 'form-control valet_field', 'pkmgmt-valet[options]', 'Options', $valet['options']);
		echo '</div>';
		echo '</div>';

		self::database($valet['database'], $box, 'valet');

		self::payment($box['id'] . '-payment', $valet['payment'], 'pkmgmt-valet[payment]');

		echo '<fieldset class="border border-dark p-3 position-relative my-4">';
		echo '<legend class="w-auto mx-2 bg-white position-absolute top-0 start-0 translate-middle-y fs-6">Mail</legend>';
		self::notification_template($box['id'] . '-mail', 'pkmgmt-valet[mail_templates]', $valet['mail_templates']);
		echo '</fieldset>';
		echo '<fieldset class="border border-dark p-3 position-relative my-4">';
		echo '<legend class="w-auto mx-2 bg-white position-absolute top-0 start-0 translate-middle-y fs-6">SMS</legend>';
		self::notification_template($box['id'] . '-sms', 'pkmgmt-valet[sms_template]', $valet['sms_template']);
		echo '</fieldset>';

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
