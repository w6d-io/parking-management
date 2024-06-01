<?php

namespace ParkingManagement;

use WP_Error;

require_once(PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "l10n.php");
require_once(PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "shortcode.php");
require_once(PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "messages.php");
require_once(PKMGMT_PLUGIN_DIR . DS . "includes" . DS . "template.php");

class ParkingManagement
{

	const post_type = 'pkmgmt_parking_management';

	private static mixed $current = null;

	public string $id;
	public string $name;
	public string $title;
	public bool $locale = false;

	private array $properties = array();

	private mixed $hash = '';


	public function __construct($post = null)
	{
		$post = get_post($post);

		if ($post
			and self::post_type === get_post_type($post)) {
			$this->id = $post->ID;
			$this->name = $post->post_name;
			$this->title = $post->post_title;
			$this->locale = get_post_meta($post->ID, '_locale', true);
			$this->hash = get_post_meta($post->ID, '_hash', true);

			$this->construct_properties();
			$this->upgrade();
		} else {
			$this->construct_properties();
		}

		do_action('pkmgmt_parking_management', $this);

	}

	/**
	 * Returns true if this parking management is not yet saved to the database.
	 */
	public function initial(): bool
	{
		return empty($this->id);
	}


	private function construct_properties(): void
	{
		$builtin_properties = array(
			'info' => array(
				'address' => '',
				'mobile' => '',
				'RCS' => '',
				'email' => '',
				'terminal' => '',
				'type' => array(
					'ext' => 0,
					'int' => 0
				)
			),
			'database' => array(
				'name' => "",
				'host' => "",
				'port' => "",
				'user' => "",
				'password' => "",
			),
			'api' => array(
				'host' => "",
				'port' => "",
				'user' => "",
				'password' => "",
			),
			'payment' => array(
				'paypal' => array(
					'enabled' => false,
					'properties' => array()
				),
				'payplug' => array(
					'enabled' => false,
					'properties' => array()
				),
				'mypos' => array(
					'enabled' => false,
					'properties' => array()
				)
			),
			'form' => array(
				'booking' => array(
					'terms_and_conditions' => 0,
					'valid_on_payment' => 0
				)
			),
			'full_dates' => [],
			'sms' => array(
				'type' => '',
				'user' => '',
				'password' => '',
				'sender' => '',
				'template' => ''
			),
			'response' => array(),
		);

		$properties = apply_filters(
			'pkmgmt_pre_construct_parking_management_properties',
			$builtin_properties, $this
		);

		// Filtering out properties with invalid name
		$properties = array_filter(
			$properties,
			static function ($key) {
				$sanitized_key = sanitize_key($key);
				return $key === $sanitized_key;
			},
			ARRAY_FILTER_USE_KEY
		);

		foreach ($properties as $name => $val) {
			$prop = $this->retrieve_property($name);

			if (isset($prop)) {
				$properties[$name] = $prop;
			}
		}

		$this->properties = $properties;

		foreach ($properties as $name => $val) {
			$properties[$name] = apply_filters(
				"pkmgmt_parking_management_property_$name",
				$val, $this
			);
		}

		$this->properties = $properties;

		$properties = (array)apply_filters(
			'pkmgmt_parking_management_properties',
			$properties, $this
		);

		$this->properties = $properties;

	}

	/**
	 * Registers the post type for parking management.
	 */
	public static function register_post_type(): void
	{
		register_post_type(self::post_type, array(
			'labels' => array(
				'name' => __('Parking Management', 'parking-management'),
				'singular_name' => __('Parking Management', 'parking-management')),
			'rewrite' => false,
			'query_var' => false));
	}

	/**
	 * Returns a ParkingManagement data filled by default contents.
	 *
	 * @param array|string $args Optional.
	 * @return ParkingManagement A new ParkingManagement object
	 */
	public static function get_template(array|string $args = ''): ParkingManagement
	{
		$args = wp_parse_args($args, array(
			'locale' => '',
			'title' => '',
			'name' => ''
		));

		if (!isset($args['locale'])) {
			$args['locale'] = determine_locale();
		}
		if (!isset($args['title'])) {
			$args['title'] = __('Untitled', 'parking-management');
		}
		if (!isset($args['name'])) {
			$args['name'] = __('Untitled', 'parking-management');
		}

		$callback = static function ($args) {
			$pm = new self;
			$pm->locale = $args['locale'];
			$pm->title = $args['title'];
			$pm->name = $args['name'];

			$properties = $pm->get_properties();

			foreach ($properties as $key => $value) {
				$default_template = Template::get_default($key);

				if (isset($default_template)) {
					$properties[$key] = $default_template;
				}
			}

			$pm->set_properties($properties);
			return $pm;
		};

		$pm = pkmgmt_switch_locale($args['locale'],
			$callback,
			$args
		);


		self::$current = apply_filters('pkmgmt_parking_management_default_pack',
			$pm, $args
		);
		return self::$current;
	}


	/**
	 * Retrieves parking management property of the specified name from the database.
	 *
	 * @param string $name Property name.
	 * @return array|string|null Property value. Null if property does not exist.
	 */
	private function retrieve_property(string $name): array|string|null
	{
		$property = null;

		if (!$this->initial()) {
			$post_id = $this->id;

			if (metadata_exists('post', $post_id, '_' . $name)) {
				$property = get_post_meta($post_id, '_' . $name, true);
			} else if (metadata_exists('post', $post_id, $name)) {
				$property = get_post_meta($post_id, $name, true);
			}
		}

		return $property;
	}

	/**
	 * Returns the value for the given property name.
	 *
	 * @param string $name Property name.
	 * @return array|string|null Property value. Null if property does not exist.
	 */
	public function prop(string $name): array|string|null
	{
		$props = $this->get_properties();
		return $props[$name] ?? null;
	}


	/**
	 * Returns all the properties.
	 *
	 * @return array This parking management properties.
	 */
	public function get_properties(): array
	{
		return $this->properties;
	}


	/**
	 * Updates properties.
	 *
	 * @param array $properties New properties.
	 */
	public function set_properties(array $properties): void
	{
		$defaults = $this->get_properties();

		$properties = wp_parse_args($properties, $defaults);
		$properties = array_intersect_key($properties, $defaults);

		$this->properties = $properties;
	}


	/**
	 * Returns ID of this parking management.
	 *
	 * @return int|string The ID.
	 */
	public function id(): int|string
	{
		return $this->id;
	}

	/**
	 * Retrieves the random hash string tied to this parking management.
	 *
	 * @param int $length Length of hash string.
	 * @return string Hash string unique to this parking management.
	 */
	public function hash(int $length = 7): string
	{
		return substr($this->hash, 0, absint($length));
	}

	/**
	 * Upgrades this contact form properties.
	 */
	private function upgrade(): void
	{
		$mail = $this->prop('mail');

		if (is_array($mail)
			and !isset($mail['recipient'])) {
			$mail['recipient'] = get_option('admin_email');
		}

		$this->properties['mail'] = $mail;

		$messages = $this->prop('messages');

		if (is_array($messages)) {
			foreach (Messages::pkmgmt_messages() as $key => $arr) {
				if (!isset($messages[$key])) {
					$messages[$key] = $arr['default'];
				}
			}
		}

		$this->properties['messages'] = $messages;
	}

	public function save(): WP_Error|int
	{
		$title = wp_slash($this->title);
		$props = wp_slash($this->get_properties());


		$post_content = implode("\n", pkmgmt_array_flatten($props));


		if ($this->initial()) {
			$post_id = wp_insert_post(array(
				'post_type' => self::post_type,
				'post_status' => 'publish',
				'post_title' => $title,
				'post_content' => trim($post_content)));
		} else {
			$post_id = wp_update_post(array(
				'ID' => (int)$this->id,
				'post_status' => 'publish',
				'post_title' => $this->title,
				'post_content' => trim($post_content)));
		}

		if ($post_id) {
			foreach ($props as $prop => $value)
				update_post_meta($post_id, '_' . $prop, pkmgmt_normalize_newline_deep($value));

			if (!empty($this->locale))
				update_post_meta($post_id, '_locale', $this->locale);

			if ($this->initial()) {
				$this->id = $post_id;
				do_action('pkmgmt_after_create', $this);
			} else {
				do_action('pkmgmt_after_update', $this);
			}

			do_action('pkmgmt_after_save', $this);
		}

		return $post_id;
	}


}
