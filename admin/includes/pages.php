<?php

namespace ParkingManagement\Admin;

use ParkingManagement\ParkingManagement;

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

	public static function info_box($info, $box): void
	{
		if ( ! isset( $box['args'] ) || ! is_array( $box['args'] ) )
			$args = array();
		else
			$args = $box['args'];
		print_log($info, false);
		print_log($args, false);
		print_log($box);
		echo '<div class="info-fields">';
		echo '</div>';
	}

	private static function config_form(ParkingManagement $pm): void
	{
		global $plugin_page;

		echo '<form action="" method="post">';
		self::config_form_hidden($plugin_page, $pm->id);
		echo '<div id="poststuff" class="metabox-holder">';
		self::config_form_header($pm);

		do_meta_boxes(null, 'info', $pm->retrieve_property('info'));

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

	private static function _index(string $type, string $id, string $name, array $args, bool $disabled = false, bool $readonly = false): string
	{
		return '<input type="' . $type . '" id="' . $id . '" name="' . $name . '" ' . self::array_to_html_attribute($args) . ($disabled ? ' disabled' : '') . ($readonly ? ' readonly' : '') . ' />';
	}

	private static function _div($class, ...$contents): string {
		return sprintf('<div class="%s">%s</div>', esc_attr($class), implode("", $contents));
	}

	private static function _label($for, ...$contents): string {
		return sprintf('<lablel for="%s">%s</lablel>', esc_attr($for), implode("", $contents));
	}

	private static function _field($div_class, $name, $label_content,  $value): string {
		return self::_div($div_class,
			self::_label($name, $label_content),
			'<br/>',
			self::_index("text", $name, $name, array(
				'class' => 'wide',
				'size' => 80,
				'value' => $value
			))
		);
	}
}
