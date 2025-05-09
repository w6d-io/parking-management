<?php

namespace ParkingManagement;

class Html
{

	public static function array_to_html_attribute(array $attr): string
	{
		$result = '';
		foreach ($attr as $key => $value) {
			$result .= $key . '="' . $value . '" ';
		}
		return trim($result);
	}

	public static function _index(string $type, string $id, string $name, array $args, bool $disabled = false, bool $readonly = false, bool $checked = false): string
	{
		return '<input type="' . $type . '" id="' . $id . '" name="' . $name . '" ' . self::array_to_html_attribute($args) . ($disabled ? ' disabled' : '') . ($checked ? ' checked' : '') . ($readonly ? ' readonly' : '') . ' />';
	}

	public static function _p(array $args, ...$contents): string
	{
		return '<p ' . self::array_to_html_attribute($args) . '>' . implode("", $contents) . '</p>';
	}

	public static function _div($args, ...$contents): string
	{
		return '<div ' . self::array_to_html_attribute($args) . '>' . implode("\n", $contents) . '</div>';
	}
	public static function _button($args, ...$contents): string
	{
		return '<button ' . self::array_to_html_attribute($args) . '>' . implode("\n", $contents) . '</button>';
	}
	public static function _nav($args, ...$contents): string
	{
		return '<nav ' . self::array_to_html_attribute($args) . '>' . implode("\n", $contents) . '</nav>';
	}
	public static function _ul($args, ...$contents): string
	{
		return '<ul ' . self::array_to_html_attribute($args) . '>' . implode(PHP_EOL, $contents) . '</ul>';
	}

	public static function _li($args, ...$contents): string
	{
		return '<li ' . self::array_to_html_attribute($args) . '>' . implode(PHP_EOL, $contents) . '</li>';
	}

	public static function _password(string $id, string $name, array $div_args, array $args): string
	{
		return Html::_div($div_args,
			Html::_index("password", $id, $name, $args),
			'<span class="togglePassword password-toggle">',
			'<i class="fas fa-eye"></i>',
			'<i class="fas fa-eye-slash" style="display: none;"></i>',
			'</span>'
		);
	}

	public static function _fieldset(...$contents): string
	{
		return '<fieldset>' . implode("", $contents) . '</fieldset>';
	}

	public static function _fieldset_with_attr(array $attr, ...$contents): string
	{
		return '<fieldset ' . self::array_to_html_attribute($attr) . '>' . implode("", $contents) . '</fieldset>';
	}

	public static function _label($for, ...$contents): string
	{
		return '<label for="' . esc_attr($for) . '">' . implode(PHP_EOL, $contents) . '</label>';
	}

	public static function _label_with_attr(array $args, $for, ...$contents): string
	{
		return '<label ' . self::array_to_html_attribute($args) . ' for="' . esc_attr($for) . '">' . implode("", $contents) . "\n</label>";
	}

	public static function _span(array $attr, ...$contents): string
	{
		return '<span ' . self::array_to_html_attribute($attr) . '>' . implode(PHP_EOL, $contents) . '</span>';
	}

	public static function _checkbox($id, $name, array $args, $key, $value): string
	{
		$args = array_merge(array('value' => '1'), $args);
		$contents = array();
		$contents[] = Html::_index('hidden', '', $name . '[' . $key . ']', array('value' => '0'));
		$contents[] = Html::_index("checkbox", $id . '-' . $key, $name . '[' . $key . ']',
			$args,
			false,
			false,
			$value == '1'
		);
		$contents[] = Html::_label_with_attr(array('class' => 'form-check-label'), $id . '-' . $key, $key);

		return implode(PHP_EOL, $contents);
	}

	public static function _radio(string $id, string $name, mixed $value, array $args, bool $checked = false): string
	{
		return '<input type="radio" ' . self::array_to_html_attribute($args) . ' id="' . $id . '" name="' . $name . '" value="' . $value . '"' . ($checked ? ' checked' : '') . '/>';
	}


	public static function _select(string $id, string $name, array $args, array $options, string $value): string
	{

		$select = '<select name="' . $name . '" id="' . $id . '" ' . self::array_to_html_attribute($args) . ' >';
		foreach ($options as $key => $option) {
			if ($key === 'group') {
				foreach ($option as $group_name => $opts) {
					$select .= '<optgroup label="' . $group_name . '">' . PHP_EOL;
					foreach ($opts as $o) {
						$select .= self::_option($o['value'], $o['label'], $o['value'] == $value) . PHP_EOL;
					}
					$select .= '</optgroup>' . PHP_EOL;
				}
				break;
			} else
				$select .= self::_option($option['value'], $option['label'], $option['value'] == $value);
		}
		$select .= '</select>';
		return $select;
	}

	private static function _option($value, $label, $checked = false): string
	{
		return '<option value="' . $value . '"' . ($checked ? 'selected' : '') . '>' . $label . '</option>';
	}

	public static function _textarea(string $id, string $name, $content, string $cols, string $rows): string
	{
		return '<textarea ' . 'id="' . $id . '" name="' . $name . '"' . ' cols="' . $cols . '"' . ' rows="' . $rows . '"' . '>' . $content . '</textarea>';
	}

	public static function _form(string $id, string $name, string $method, string $action, array $args, ...$contents): string
	{
		return '<form id="' . $id . '" name="' . $name . '" method="' . $method . '" action="' . $action . '">' . implode(PHP_EOL, $contents) . '</form>';
	}

	public static function _alert($type, $message): string
	{
		$span_id = match ($type) {
			'success' => 'check-circle',
			'danger','error','warning' => 'exclamation-triangle',
			default => 'info'
		};
		return inline_svg(PKMGMT_PLUGIN_DIR . DS . "images" . DS . "notify.svg").
			self::_div(['class' => 'alert alert-' . $type . ' d-flex align-items-center alert-dismissible fade show', 'role'=>'alert'],
			'<svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="'.lcfirst($type).':"><use xlink:href="#'.$span_id.'-fill"/></svg>',
			self::_div(['class'=> 'message'],
				$message,
			),

		);
	}

	public static function _switch($id, $name, array $args, $label, $value): string
	{
		$args = array_merge(array('value' => '1'), $args);
		$contents = array();
		$args['class'] = 'options-checkbox form-check-input';
		$contents[] = self::_div(
			['class'=> 'options-switch form-check'],
			self::_index('hidden', "", $name, array('value' => '0')),
			self::_index(
				'checkbox',
				$id ,
				$name,
				$args,
				false,
				false,
				$value == '1'
			),
			self::_label_with_attr(['class' => 'form-check-label black'], $id, $label),
		);
		return implode(PHP_EOL, $contents);
	}

}
