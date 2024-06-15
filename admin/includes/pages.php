<?php

namespace ParkingManagement\Admin;

use ParkingManagement\ParkingManagement;
use ParkingManagement\Template;

class Pages
{
	public static function management(): void
	{
		$pm = ParkingManagement::get_current();
		echo '<div class="wrap pkmgmt-parking-management-config">';
		echo '<h2>' . esc_html(__('Parking Management', 'parking-management')) . '</h2>';
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
			$message = esc_html(__('Configuration saved.', 'parking-management'));
		if (empty($message))
			return;

		echo sprintf('<div id="message" class="updated"><p>%s</p></div>', esc_html($message));

	}

	public static function array_to_html_attribute(array $attr): string
	{
		$result = '';
		foreach ($attr as $key => $value) {
			if (in_array($key, ['id', 'name', 'type']))
				continue;
			$result .= $key . '="' . $value . '" ';
		}
		return trim($result);
	}

	private static function config_form(ParkingManagement $pm): void
	{
		global $plugin_page;

		echo '<form action="" method="post">';
		self::config_form_hidden($plugin_page, $pm->id);
		echo '<div id="poststuff" class="metabox-holder">';
		self::config_form_header($pm);

		$payment_args = $pm->prop('payment');
//		$payment_args = Template::get_default('payment');
		do_meta_boxes(null, 'info', $pm->prop('info'));
		do_meta_boxes(null, 'database', $pm->prop('database'));
		do_meta_boxes(null, 'api', $pm->prop('api'));
		do_meta_boxes(null, 'payment', $payment_args);
		do_meta_boxes(null, 'form', $pm->prop('form'));
		do_meta_boxes(null, 'full_dates', $pm->prop('full_dates'));
		do_meta_boxes(null, 'sms', $pm->prop('sms'));

		echo '</div>';
		echo '</form>';
	}

	private static function config_form_hidden(string $page, int $id): void
	{
		echo sprintf('
		<input type="hidden" name="page" value="%s" />
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

		echo self::_index("text", "pkmgmt-title", "pkmgmt-title", array(
			'class' => "wide",
			'placeholder' => esc_html(__("Title", 'parking-management')),
			'size' => 80,
			'value' => esc_attr($pm->title)
		),
			!(current_user_can('pkmgmt_edit', $pm->id))
		);

		echo self::_p(
			esc_html(__("Name", 'parking-management')) . '<br/>',
			self::_index("text", "pkmgmt-name", "pkmgmt-name", array(
				'class' => "wide",
				'size' => 80,
				'value' => esc_attr($pm->name)
			),
				!(current_user_can('pkmgmt_edit', $pm->id))
			)
		);
		echo self::_p(
			esc_html(__("Copy and paste this code into your page to include booking part.", 'parking-management')),
			'<br/>',
			'<div class="input-container wide">',
			self::_index(
				"text",
				"pkmgmt-anchor-text",
				"pkmgmt-anchor-text",
				array(
					'class' => "wide",
					'size' => 80,
					'value' => sprintf("[parking-management id='%s']", $pm->id)
				),
				false,
				true,
			),
			'<div class="tooltip">',
			'<button id="shortcodeCopy" type="button">',
			'<span class="tooltiptext">Copy to clipboard</span>',
			'<i class="fas fa-copy darkgray"></i>',
			'</button>',
			'</div>',
			'<span id="shortcodeCopyMessage">copied</span>',
			'</div>'
		);

		echo '<div class="save-pkmgmt">';
		echo self::_index("submit", "pkmgmt-save", "pkmgmt-save", array(
			'class' => 'button-primary',
			'value' => esc_html(__("Save", 'parking-management')),
		));
		echo '</div>';
		echo '</div>';
	}

	private static function _p(...$contents): string
	{
		return sprintf('<p class="tagcode">%s</p>', implode("", $contents));
	}

	private static function _index(string $type, string $id, string $name, array $args, bool $disabled = false, bool $readonly = false, bool $checked = false): string
	{
		return '<input type="' . $type . '" id="' . $id . '" name="' . $name . '" ' . self::array_to_html_attribute($args) . ($disabled ? ' disabled' : '') . ($readonly ? ' readonly' : '') . ($checked ? ' checked' : '') . ' />';
	}

	private static function _password(string $id, string $name, array $args): string
	{
		return self::_div('password-container',
			self::_index("password", $id, $name, $args),
			'<span class="togglePassword password-toggle">',
			'<i class="fas fa-eye"></i>',
			'<i class="fas fa-eye-slash" style="display: none;"></i>',
			'</span>'
		);
	}

	private static function _select(string $id, string $name, array $options, string $value): string
	{
		$select = '<select name="' . $name . '" id="' . $id . '">';
		foreach ($options as $option) {
			$select .= '<option value="' . $option . '"' . ($option == $value ? 'selected' : '') . '>' . $option . '</option>';
		}
		$select .= '</select>';
		return $select;
	}

	private static function _textarea(string $id, string $name, $content, string $cols, string $rows): string
	{
		return '<textarea ' . 'id="' . $id . '" name="' . $name . '"' . ' cols="' . $cols . '"' . ' rows="' . $rows . '"' . '>' . $content . '</textarea>';
	}

	private static function _div($class, ...$contents): string
	{
		return sprintf('<div class="%s">%s</div>', esc_attr($class), implode("", $contents));
	}

	private static function _label($for, ...$contents): string
	{
		return sprintf('<label for="%s">%s</label>', esc_attr($for), implode("", $contents));
	}

	private static function _fieldset($for, $args, ...$contents): string
	{
		return sprintf('<fieldset ' . self::array_to_html_attribute($args) . ' >%s</fieldset>', implode("", $contents));
	}

	private static function _checkbox($id, $name, $key, $value): string
	{
		$contents = array();
		$contents[] .= self::_index('hidden', '', $name . '[' . $key . ']', array('value' => '0'));
		$contents[] .= self::_index("checkbox", $id . '-' . $key, $name . '[' . $key . ']',
			array('value' => '1'),
			false,
			false,
			$value == '1'
		);
		$contents[] .= self::_label($id . '-' . $key, $key);
		$contents[] .= '<br/>';

		return implode("\n", $contents);
	}

	private static function _radio($id, $name, $key, $value): string
	{
		$contents = array();
		$contents[] .= self::_index('hidden', '', $name . '[' . $key . ']', array('value' => '0'));
		$contents[] .= self::_index("radio", $id . '-' . $key, $name . '[' . $key . ']',
			array('value' => '1'),
			false,
			false,
			$value == '1'
		);
		$contents[] .= self::_label($id . '-' . $key, $key);
		$contents[] .= '<br/>';

		return implode("\n", $contents);
	}

	private static function _field($id, $div_class, $name, $label_content, $value): string
	{
		return self::_div($div_class,
			self::_label($name, $label_content),
			'<br/>',
			self::_index("text", $id, $name, array(
				'class' => 'wide',
				'size' => 80,
				'value' => $value
			))
		);
	}

	private static function _field_password($id, $div_class, $name, $label_content, $value): string
	{
		return self::_div($div_class,
			self::_label($name, $label_content),
			'<br/>',
			self::_password($id, $name, array(
				'class' => 'wide password-input',
				'size' => 80,
				'value' => $value
			))
		);
	}

	private static function _field_checkbox($id, $div_class, $name, $label_content, $values): string
	{
		if (!is_array($values)) {
			return self::_div($div_class, "bad values type");
		}

		$contents = array();
		$contents[] .= self::_label('', $label_content);
		$contents[] .= '<br/>';
		foreach ($values as $key => $value) {
			$contents[] .= self::_checkbox($id, $name, $key, $value);
		}
		return self::_div($div_class,
			...$contents
		);
	}

	private static function _field_select($id, $div_class, $name, $label_content, $options, $value): string
	{
		if (!is_array($options)) {
			return self::_div($div_class, "bad values type");
		}

		return self::_div($div_class,
			self::_label('', $label_content),
			'<br/>',
			self::_select($id, $name, $options, $value)
		);
	}

	private static function _field_textarea($id, $div_class, $name, $label_content, $value, $args = array()): string
	{
		$cols = array_key_exists('cols', $args) ? $args['cols'] : "100";
		$rows = array_key_exists('rows', $args) ? $args['rows'] : "12";
		return self::_div($div_class,
			self::_label($id, $label_content),
			'<br/>',
			self::_textarea($id, $name, $value, $cols, $rows),
		);
	}

	private static function _field_payment($id, $div_class, $label_content, $name, $payment): string
	{

		$contents = array();
		foreach ($payment['properties'] as $key => $value) {
			if (in_array($key, array("password", "secret", "secret_key", "signature", "configuration_package")))
				$contents[] .= self::_field_password($id . '-' . $key, $div_class, $name . '[properties]' . '[' . $key . ']', $key, $value);
			else
				$contents[] .= self::_field($id . '-' . $key, $div_class, $name . '[properties]' . '[' . $key . ']', $key, $value);
		}
		return self::_div($div_class,
			self::_fieldset($id,
				array(),
				'<legend>'.$label_content.'</legend>',
				self::_checkbox($id, $name, 'enabled', $payment['enabled']),
				...$contents
			)
		);
	}

	public static function info_box($info, $box): void
	{
		self::getArgs($box);
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('info-address', 'info_field', 'pkmgmt-info[address]', esc_html(__('Address', 'parking-management')), $info['address']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('info-mobile', 'info_field', 'pkmgmt-info[mobile]', esc_html(__('Mobile', 'parking-management')), $info['mobile']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('info-RCS', 'info_field', 'pkmgmt-info[RCS]', esc_html(__('RCS', 'parking-management')), $info['RCS']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('info-email', 'info_field', 'pkmgmt-info[email]', esc_html(__('Email', 'parking-management')), $info['email']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('info-terminal', 'info_field', 'pkmgmt-info[terminal]', esc_html(__('Terminal', 'parking-management')), $info['terminal']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_checkbox('info-type', 'info_field', 'pkmgmt-info[type]', esc_html(__('Vehicle storage mode', 'parking-management')), $info['type']);
		echo '</div>';
	}

	public static function database_box($database, $box): void
	{
		self::getArgs($box);
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('database-name', 'database_field', 'pkmgmt-database[name]', esc_html(__('Name', 'parking-management')), $database['name']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('database-host', 'database_field', 'pkmgmt-database[host]', esc_html(__('Host', 'parking-management')), $database['host']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('database-port', 'database_field', 'pkmgmt-database[port]', esc_html(__('Port', 'parking-management')), $database['port']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('database-user', 'database_field', 'pkmgmt-database[user]', esc_html(__('Username', 'parking-management')), $database['user']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_password('database-password', 'database_field', 'pkmgmt-database[password]', esc_html(__('Password', 'parking-management')), $database['password']);
		echo '</div>';
	}

	public static function api_box($api, $box): void
	{
		self::getArgs($box);
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('api-host', 'api_field', 'pkmgmt-api[host]', esc_html(__('Host', 'parking-management')), $api['host']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('api-port', 'api_field', 'pkmgmt-api[port]', esc_html(__('Port', 'parking-management')), $api['port']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('api-user', 'api_field', 'pkmgmt-api[user]', esc_html(__('Username', 'parking-management')), $api['user']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_password('api-password', 'api_field', 'pkmgmt-api[password]', esc_html(__('Password
		', 'parking-management')), $api['password']);
		echo '</div>';
	}

	public static function payment_box($payment, $box): void
	{
		self::getArgs($box);
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_payment('payment-paypal', 'payment_field', 'Paypal', 'pkmgmt-payment[paypal]', $payment['paypal']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_payment('payment-payplug', 'payment_field', 'Payplug', 'pkmgmt-payment[payplug]', $payment['payplug']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_payment('payment-mypos', 'payment_field', 'MyPOS', 'pkmgmt-payment[mypos]', $payment['mypos']);
		echo '</div>';
	}

	public static function form_box($form, $box): void
	{
		self::getArgs($box);
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_checkbox('form-booking', 'form_field', 'pkmgmt-form[booking]', esc_html(__('Booking', 'parking-management')), $form['booking']);
		echo '</div>';

	}

	public static function full_dates_box($full_dates, $box): void
	{
		echo '<div id="full_date_global">';
		echo '<div id="full_date_header">';
		echo '<span>'.esc_html(__('Add a date', 'parking-management')).'</span>';
		echo '<button id="add-element" type="button"><i class="fas fa-plus"></i></button>';
		echo '</div>';
		echo '<div id="full_date_body">';
		echo '</div>';
		echo '</div>';
	}

	public static function sms_box($sms_box, $box): void
	{
		self::getArgs($box);
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_select('sms-type', 'sms_field', 'pkmgmt-sms[type]', esc_html(__('Type', 'parking-management')), array('AWS', 'OVH'), $sms_box['type']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('sms-user', 'sms_field', 'pkmgmt-sms[user]', esc_html(__('Username', 'parking-management')), $sms_box['user']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_password('sms-password', 'sms_field', 'pkmgmt-sms[password]', esc_html(__('Password', 'parking-management')), $sms_box['password']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field('sms-sender', 'sms_field', 'pkmgmt-sms[sender]', esc_html(__('Sender', 'parking-management')), $sms_box['sender']);
		echo '</div>';
		echo '<div class="' . $box['id'] . '-fields">';
		echo self::_field_textarea('sms-template', 'sms_field', 'pkmgmt-sms[template]', esc_html(__('Template', 'parking-management')), $sms_box['template'], array('cols' => "0"));
		echo '</div>';
	}

	/**
	 * @param $box
	 * @return array
	 */
	public static function getArgs($box): array
	{
		if (!isset($box['args']) || !is_array($box['args']))
			$args = array();
		else
			$args = $box['args'];
		if (array_key_exists('debug', $_POST) && $_POST['debug'] == 1) {
			print_log($args, false);
			print_log($box, false);
		}
		return $args;
	}
}
