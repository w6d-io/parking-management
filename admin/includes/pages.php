<?php

namespace ParkingManagement\Admin;

use ParkingManagement\Html;
use ParkingManagement\ParkingManagement;

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

		$payment_args = $pm->prop('payment');
		do_meta_boxes(null, 'info', $pm->prop('info'));
		do_meta_boxes(null, 'database', $pm->prop('database'));
		do_meta_boxes(null, 'api', $pm->prop('api'));
		do_meta_boxes(null, 'payment', $payment_args);
		do_meta_boxes(null, 'form', $pm->prop('form'));
		do_meta_boxes(null, 'booked_dates', $pm->prop('booked_dates'));
		do_meta_boxes(null, 'high_season', $pm->prop('high_season'));
		do_meta_boxes(null, 'sms', $pm->prop('sms'));

		echo '</div>';
		echo '</form>';
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
		return Html::_div(array('class'=>$div_class),
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
		return Html::_div(array('class'=>$div_class),
			Html::_label($id, $label_content),
			'<br/>',
			Html::_password($id, $name, array('class'=>'password-container'), array(
				'class' => 'wide password-input',
				'size' => 80,
				'value' => $value
			))
		);
	}

	private static function _field_checkbox($id, $div_class, $name, $span_content, $values): string
	{
		if (!is_array($values)) {
			return Html::_div(array('class'=>$div_class), "bad values type");
		}
		$contents = array();
		$contents[] .= '<span>'.$span_content.'</span>';
		$contents[] .= '<br/>';
		foreach ($values as $key => $value) {
			$contents[] .= Html::_div(
				array('class'=>'form-check form-switch  '),
				Html::_checkbox($id, $name, array('class'=> $id . ' form-check-input'), $key, $value),
			);
		}
		return Html::_div(array('class'=>$div_class),
			...$contents
		);
	}

	private static function _field_select($id, $div_class, $name, $label_content, $options, $value): string
	{
		if (!is_array($options)) {
			return Html::_div(array('class'=>$div_class), "bad values type");
		}

		return Html::_div(array('class'=>$div_class),
			Html::_label($id, $label_content),
			'<br/>',
			Html::_select($id, $name, array(), $options, $value)
		);
	}

	private static function _field_textarea($id, $div_class, $name, $label_content, $value, $args = array()): string
	{
		$cols = array_key_exists('cols', $args) ? $args['cols'] : "100";
		$rows = array_key_exists('rows', $args) ? $args['rows'] : "12";
		return Html::_div(array('class'=>$div_class),
			Html::_label($id, $label_content),
			'<br/>',
			Html::_textarea($id, $name, $value, $cols, $rows),
		);
	}

	private static function _field_payment($id, $div_class, $label_content, $name, $payment): string
	{

		$contents = array();
		foreach ($payment['properties'] as $key => $value) {
			if (in_array($key, array("password", "secret", "secret_key", "signature", "configuration_package")))
				$contents[] .= self::_field_password($id . '-' . $key, $div_class . " form-control", $name . '[properties]' . '[' . $key . ']', $key, $value);
			else
				$contents[] .= self::_field($id . '-' . $key, $div_class . " form-control", $name . '[properties]' . '[' . $key . ']', $key, $value);
		}
		return Html::_fieldset(
			'<legend>' . $label_content . '</legend>',
			'<div class="form-control">',
			Html::_checkbox($id, $name, array(), 'enabled', $payment['enabled']),
			'</div>',
			...$contents
		);
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
		echo self::_field_checkbox('info-vehicle-type', 'info_field', 'pkmgmt-info[vehicle_type]', esc_html__('Vehicle Type supported', 'parking-management'), $info['vehicle_type']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_checkbox('info-type', 'info_field', 'pkmgmt-info[type]', esc_html__('Vehicle storage mode', 'parking-management'), $info['type']);
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

	public static function api_box($api, $box): void
	{
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('api-host', 'api_field', 'pkmgmt-api[host]', esc_html__('Host', 'parking-management'), $api['host']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('api-port', 'api_field', 'pkmgmt-api[port]', esc_html__('Port', 'parking-management'), $api['port']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('api-user', 'api_field', 'pkmgmt-api[user]', esc_html__('Username', 'parking-management'), $api['user']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_password('api-password', 'api_field', 'pkmgmt-api[password]', esc_html__('Password
		', 'parking-management'), $api['password']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('api-zip_codes_endpoint', 'api_field', 'pkmgmt-api[zip_codes_endpoint]', esc_html__('Zip codes endpoint', 'parking-management'), $api['zip_codes_endpoint']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('api-models-vehicle-endpoint', 'api_field', 'pkmgmt-api[models_vehicle_endpoint]', esc_html__('Model vehicle endpoint', 'parking-management'), $api['models_vehicle_endpoint']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('api-destinations-endpoint', 'api_field', 'pkmgmt-api[destinations_endpoint]', esc_html__('Destinations endpoint', 'parking-management'), $api['destinations_endpoint']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('api-price-endpoint', 'api_field', 'pkmgmt-api[price_endpoint]', esc_html__('Price endpoint', 'parking-management'), $api['price_endpoint']);
		echo '</div>';
	}

	public static function payment_box($payment, $box): void
	{
		echo '<div class="tabs">';
		echo '<ul class="tab-links">';
		echo '<li class="active"><a href="#payment-paypal-tab">Paypal</a></li>';
		echo '<li><a href="#payment-payplug-tab">Payplug</a></li>';
		echo '<li><a href="#payment-mypos-tab">MyPOS</a></li>';
		echo '</ul>';
		echo '<div class="tab-content">';
		echo '<div id="payment-paypal-tab" class="tab active">';
		echo self::_field_payment($box['id'] . '-paypal', $box['id'] . '_field', 'Paypal', 'pkmgmt-payment[paypal]', $payment['paypal']);
		echo '</div>';
		echo '<div id="payment-payplug-tab" class="tab">';
		echo self::_field_payment($box['id'] . '-payplug', $box['id'] . '_field', 'Payplug', 'pkmgmt-payment[payplug]', $payment['payplug']);
		echo '</div>';
		echo '<div id="payment-mypos-tab" class="tab">';
		echo self::_field_payment($box['id'] . '-mypos', $box['id'] . '_field', 'MyPOS', 'pkmgmt-payment[mypos]', $payment['mypos']);
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	public static function form_box($form, $box): void
	{
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_checkbox('form-booking', 'form_field', 'pkmgmt-form[booking]', esc_html__('Booking', 'parking-management'), $form['booking']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('form-indicatif', 'form_field', 'pkmgmt-form[indicatif]', esc_html__('Indicative', 'parking-management'), $form['indicatif']);
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
			echo '<label for="pkmgmt-booked-dates-start-'.$id.'">start</label>';
			echo '<input type="date" id="pkmgmt-booked-dates-start-'.$id.'" name="pkmgmt-booked_dates[' . $id . '][start]" class="start-date" value="' . $date['start'] . '">';
			echo '<label for="pkmgmt-booked-dates-end-'.$id.'">end</label>';
			echo '<input type="date" id="pkmgmt-booked-dates-end-'.$id.'" name="pkmgmt-booked_dates[' . $id . '][end]" class="end-date" value="' . $date['end'] . '">';
			echo '<label for="pkmgmt-booked-dates-message-'.$id.'">message</label>';
			echo '<input type="text" id="pkmgmt-booked-dates-message-'.$id.'" name="pkmgmt-booked_dates[' . $id . '][message]" class="message" placeholder="Message" value="' . $date['message'] . '">';
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
		echo '<div class="dates-header">';
		echo '<span>' . esc_html__('Add a date', 'parking-management') . '</span>';
		echo '<button id="high-season-add-element" type="button"><i class="fas fa-plus"></i></button>';
		echo '</div>';
		echo '<div id="high_season_dates_body" class="dates-body">';
		foreach ($high_season as $id => $date) {
			echo '<div class="dates-element">';
			echo '<label for="pkmgmt-high-season-start-'.$id.'">start</label>';
			echo '<input type="date" id="pkmgmt-high-season-start-'.$id.'" name="pkmgmt-high_season[' . $id . '][start]" class="start-date" value="' . $date['start'] . '">';
			echo '<label for="pkmgmt-high-season-end-'.$id.'">end</label>';
			echo '<input type="date" id="pkmgmt-high-season-end-'.$id.'" name="pkmgmt-high_season[' . $id . '][end]" class="end-date" value="' . $date['end'] . '">';
			echo '<label for="pkmgmt-high-season-message-'.$id.'">message</label>';
			echo '<input type="text" id="pkmgmt-high-season-message-'.$id.'" name="pkmgmt-high_season[' . $id . '][message]" class="message" placeholder="Message" value="' . $date['message'] . '">';
			echo '<i class="fas fa-trash delete"></i>';
			echo '</div>';
		}
		echo '</div>';
		echo '</div>';
	}

	public static function sms_box($sms_box, $box): void
	{
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_select('sms-type', 'sms_field', 'pkmgmt-sms[type]', esc_html__('Type', 'parking-management'),
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
			$sms_box['type']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('sms-user', 'sms_field', 'pkmgmt-sms[user]', esc_html__('Username', 'parking-management'), $sms_box['user']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_password('sms-password', 'sms_field', 'pkmgmt-sms[password]', esc_html__('Password', 'parking-management'), $sms_box['password']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('sms-sender', 'sms_field', 'pkmgmt-sms[sender]', esc_html__('Sender', 'parking-management'), $sms_box['sender']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_textarea('sms-template', 'sms_field', 'pkmgmt-sms[template]', esc_html__('Template', 'parking-management'), $sms_box['template'], array('cols' => "0"));
		echo '</div>';
	}

}
