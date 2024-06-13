<?php

/**
 * Include a file under PKMGMT_PLUGIN_MODULES_DIR.
 *
 * @param string $path File path relative to the module dir.
 * @return bool True on success, false on failure.
 */
function pkmgmt_include_module_file(string $path ): bool
{
	$dir = PKMGMT_PLUGIN_MODULES_DIR;

	if ( empty( $dir ) or ! is_dir( $dir ) ) {
		return false;
	}

	$path = path_join( $dir, ltrim( $path, DS ) );

	if ( file_exists( $path ) ) {
		include_once $path;
		return true;
	}

	return false;
}

function pkmgmt_get_request_uri(): string
{
	static $request_uri = '';

	if ( empty( $request_uri ) )
		$request_uri = add_query_arg( array() );
	return sanitize_url( $request_uri );
}

function pkmgmt_register_post_type(): bool
{
	if ( class_exists( 'ParkingManagement' ) ) {
		ParkingManagement::register_post_type();
		return true;
	} else {
		return false;
	}

}


/**
 * Converts multi-dimensional array to a flat array.
 *
 * @param mixed $input Array or item of array.
 * @return array Flatten array.
 */
function pkmgmt_array_flatten(mixed $input ): array
{
	if ( ! is_array( $input ) ) {
		return array( $input );
	}

	$output = array();

	foreach ( $input as $value ) {
		$output = array_merge( $output, pkmgmt_array_flatten( $value ) );
	}

	return $output;
}


/**
 * Navigates through an array, object, or scalar, and
 * normalizes newline characters in the value.
 *
 * @param mixed $input The array or string to be processed.
 * @param string $to Optional. The newline character that is used in the output.
 * @return string|array Processed value.
 */
function pkmgmt_normalize_newline_deep(mixed $input, string $to = "\n" ): string|array
{
	if ( is_array( $input ) ) {
		$result = array();

		foreach ( $input as $key => $text ) {
			$result[$key] = pkmgmt_normalize_newline_deep( $text, $to );
		}

		return $result;
	}

	return pkmgmt_normalize_newline( $input, $to );
}

/**
 * Normalizes newline characters.
 *
 * @param string $text Input text.
 * @param string $to Optional. The newline character that is used in the output.
 * @return string Normalized text.
 */
function pkmgmt_normalize_newline(string $text, string $to = "\n" ): string
{
	$nls = array( "\r\n", "\r", "\n" );

	if ( ! in_array( $to, $nls ) ) {
		return $text;
	}

	return str_replace( $nls, $to, $text );
}

/**
 * Print the object wrapped into <pre></pre> and call exit by default
 *
 * @param mixed $object
 * @param bool $out Default: true
 * @return void
 */
function print_log(mixed $object, bool $out = true): void
{
	print "<pre>";
	print_r($object);
	print "</pre>";
	if ($out)
		exit(1);
}

function get_post_id_by_post_type( string $post_type ): int {
	$post_ids = get_posts( array(
		'post_type' => $post_type,
		'numberposts' => 1,
		'fields' => 'ids',
	));
	if ( empty( $post_ids ) )
		return 0;
	return $post_ids[0];
}

function pkmgmt_plugin_url(string $path): string
{
	$url = plugins_url($path, PKMGMT_PLUGIN);
	if ( is_ssl() and str_starts_with($url, 'http:')) {
		$url = 'https:' . substr( $url, 5 );
	}

	return $url;
}
