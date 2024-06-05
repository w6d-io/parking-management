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
		if ($pm === null) {
			$_REQUEST['message'] = 'Failed to get config';
			do_action('pkmgmt_admin_notices');
		} else {
			do_action('pkmgmt_admin_notices');
			self::config_form($pm);
		}
		echo '</div>';
	}

	public static function notices_message($page): void
	{
		if ($page != 'pkmgmt') {
			return;
		}
		if (empty($_REQUEST['message']))
			return;
		$message = $_REQUEST['message'];
		if ('saved' == $message)
			$message = esc_html(__('Site saved.', 'parking-management'));
		if (empty($message))
			return;

		echo sprintf('<div id="message" class="updated"><p>%s</p></div>', esc_html($message));

	}

	private static function config_form(ParkingManagement $pm): void
	{
		global $plugin_page;

		echo '<form action="" method="post">';
		self::config_form_hidden($plugin_page, $pm->id);
		self::config_form_header($pm);

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
			esc_html(__("Name", 'parking-management')) . "<br/",
			self::_index("text", "pkmgmt-name", "pkmgmt-name", array(
				'size' => 80,
				'value' => esc_attr($pm->name)
			),
				!(current_user_can('pkmgmt_edit', $pm->id))
			)
		);
		echo '<div class="save-pkmgmt">';
		echo self::_index("submit", "pkmgmt-save", "pkmgmt-save", array(
			'class' => 'button-primary',
			'value' => esc_html(__("Save", 'parking-management')),
		));
		echo '</div>';
		echo '</div>';
	}

	private static function _p(string ...$contents): string
	{
		$content = "";
		foreach ($contents as $elem) {
			$content .= $elem . "\n";
		}
		return sprintf('<p class="tagcode">%s</p>', $content);
	}

	private static function _index(string $type, string $id, string $name, array $args, bool $disabled = false): string
	{
		return '<input type="' . $type . '" id="' . $id . '" name="' . $name . '" ' . self::array_to_html_attribute($args) . ($disabled ?  ' disabled' : '') . ' />';
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
}
