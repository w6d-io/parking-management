<?php

/**
 * Switches translation locale, calls the callback, then switches back
 * to the original locale.
 *
 * @param string $locale Locale code.
 * @param callable $callback The callable to be called.
 * @param mixed $args Parameters to be passed to the callback.
 * @return mixed The return value of the callback.
 */
function pkmgmt_switch_locale(string $locale, callable $callback, ...$args): mixed
{
	static $available_locales = null;

	if (!isset($available_locales)) {
		$available_locales = array_merge(
			array('en_US'),
			get_available_languages()
		);
	}

	$previous_locale = determine_locale();
	$do_switch_locale = (
		$locale !== $previous_locale &&
		in_array($locale, $available_locales, true) &&
		in_array($previous_locale, $available_locales, true)
	);

	if ($do_switch_locale) {
		pkmgmt_unload_textdomain();
		switch_to_locale($locale);
		pkmgmt_load_textdomain($locale);
	}

	$result = call_user_func($callback, ...$args);

	if ($do_switch_locale) {
		pkmgmt_unload_textdomain(true);
		restore_previous_locale();
		pkmgmt_load_textdomain($previous_locale);
	}

	return $result;
}

add_action('plugins_loaded', 'pkmgmt_load_textdomain', 10, 0);

/**
 * Loads a translation file into the plugin's text domain.
 *
 * @param string $locale Locale code.
 * @return bool True on success, false on failure.
 */
function pkmgmt_load_textdomain(string $locale = 'fr_FR'): bool
{
	$domain = "parking-management";
	if ( is_textdomain_loaded( $domain ) )
		return true;

	$mofile = path_join(
		PKMGMT_LANGUAGES_DIR . DS,
		sprintf('%s-%s.mo', PKMGMT_TEXT_DOMAIN, $locale)
	);
	return load_textdomain(PKMGMT_TEXT_DOMAIN, $mofile, $locale);
}


/**
 * Unloads translations for the plugin's text domain.
 *
 * @param bool $reloadable Whether the text domain can be loaded
 *             just-in-time again.
 * @return bool True on success, false on failure.
 */
function pkmgmt_unload_textdomain(bool $reloadable = false): bool
{
	return unload_textdomain(PKMGMT_TEXT_DOMAIN, $reloadable);
}

/**
 * Returns true if the given locale code looks valid.
 *
 * @param string $locale Locale code.
 */
function is_valid_locale(string $locale): bool
{
	$pattern = '/^[a-z]{2,3}(?:_[a-zA-Z_]{2,})?$/';
	return (bool)preg_match($pattern, $locale);
}
