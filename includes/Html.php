<?php

namespace ParkingManagement;

class Html {

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

	public static function _index(string $type, string $id, string $name, array $args, bool $disabled = false, bool $readonly = false, bool $checked = false): string
	{
		return '<input type="' . $type . '" id="' . $id . '" name="' . $name . '" ' . self::array_to_html_attribute($args) . ($disabled ? ' disabled' : '') . ($checked ? ' checked' : '') . ($readonly ? ' readonly' : '') . ' />';
	}

	public static function _p(array $args, ...$contents): string
	{
		return '<p '. self::array_to_html_attribute($args) .'>'.implode("", $contents).'</p>';
	}

	public static function _div($args, ...$contents): string
	{
		return '<div '.self::array_to_html_attribute($args).'>'.implode(PHP_EOL, $contents).'</div>';
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
		return '<fieldset>'.implode("", $contents).'</fieldset>';
	}

	public static function _label($for, ...$contents): string
	{
		return '<label for="'.esc_attr($for).'">'.implode(PHP_EOL, $contents).'</label>';
	}

	public static function _label_with_attr(array $args, $for, ...$contents): string
	{
		return '<label '. self::array_to_html_attribute($args).' for="'.esc_attr($for).'">'.implode("", $contents)."\n</label>";
	}

	public static function _span(array $attr, ...$contents): string
	{
		return '<span '.self::array_to_html_attribute($attr).'>'.implode(PHP_EOL, $contents).'</span>';
	}

	public static function _checkbox($id, $name, array $args, $key, $value): string
	{
		$args = array_merge(array('value' => '1'),$args);
		$contents = array();
		$contents[] .= Html::_index('hidden', '', $name . '[' . $key . ']', array('value' => '0'));
		$contents[] .= Html::_index("checkbox", $id . '-' . $key, $name . '[' . $key . ']',
			$args,
			false,
			false,
			$value == '1'
		);
		$contents[] .= Html::_label($id . '-' . $key, $key);
		$contents[] .= '<br/>';

		return implode(PHP_EOL, $contents);
	}
	public static function _radio($id, $name, $value, bool $checked = false): string
	{
		return '<input type="radio" id="'.$id.'" name="'.$name.'" value="'.$value.'"'. ($checked ? ' checked' : '') .'/>';
	}

	public static function _select(string $id, string $name, array $args, array $options, string $value): string
	{

		$select = '<select name="' . $name . '" id="' . $id . '" '. self::array_to_html_attribute($args).' >';
		foreach ($options as $key => $option) {
			if ($key === 'group') {
				foreach ($option as $group_name => $opts) {
					$select .= '<optgroup label="' . $group_name . '">'. PHP_EOL;
					foreach ($opts as $o) {
						$select .= self::_option($o['value'], $o['label'], $o['value'] == $value). PHP_EOL;
					}
					$select .= '</optgroup>'. PHP_EOL;
				}
				break;
			}
			else
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


}
