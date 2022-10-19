<?php

/**
 * Get the real ip address
 *
 * @return mixed|string - the real ip address
 */
function cf7a_get_real_ip() {
	if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) && filter_var( $_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP ) ) {
		return $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && filter_var( $_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP ) ) {
		return (string) rest_is_ip_address( trim( current( preg_split( '/,/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) );
	} elseif ( isset( $_SERVER['HTTP_X_REAL_IP'] ) && filter_var( $_SERVER['HTTP_X_REAL_IP'], FILTER_VALIDATE_IP ) ) {
		return $_SERVER['HTTP_X_REAL_IP'];
	} elseif ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) && filter_var( $_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP ) ) {
		return $_SERVER['HTTP_CF_CONNECTING_IP'];
	} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) && filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP ) ) {
		return $_SERVER['REMOTE_ADDR'];
	}
}


/**
 * It takes a string of comma-separated language codes, and returns an array of language codes
 *
 * @param string languages The Accept-Language header sent by the browser.
 *
 * @return array the array of language codes
 */
function cf7a_get_browser_language_array( $languages ) {
	return array_values(
		array_reduce(
			explode( ',', $languages ),
			function( $res, $el ) {
				if ( strlen( $el ) === 5 ) {
					$l                          = explode( '-', $el );
					$res[ strtolower( $l[0] ) ] = $l[0];
					$res[ strtolower( $l[1] ) ] = strtolower( $l[1] );
				} else {
					$l = preg_split( '/(\-|\_)/', $el );
					if ( ctype_alnum( $l[0] ) ) {
						$res[ strtolower( $l[0] ) ] = $l[0];
					}
				}
				return $res;
			},
			array()
		)
	);
}

/**
 *
 * Converts HTTP_ACCEPT_LANGUAGE into an array of languages and nations
 *
 * It takes a string like
 * `en-US,en;q=0.9,de;q=0.8,es;q=0.7,fr;q=0.6,it;q=0.5,pt;q=0.4,ru;q=0.3,ja;q=0.2,zh-CN;q=0.1,zh-TW;q=0.1` and returns an
 * array like `[ 'en', 'us', 'de', 'es', 'fr', 'it', 'pt', 'ru', 'ja', 'zh', 'cn', 'tw' ]`
 *
 * @param $string languages The Accept-Language header from the browser.
 *
 * @return array An array of languages.
 */
function cf7a_get_accept_language_array( $languages ) {

	// a modified version of https://stackoverflow.com/a/33748742/5735847
	return array_values(
		array_reduce(
			explode( ',', str_replace( ' ', '', $languages ) ),
			function ( $res, $el ) {
				if ( strlen( $el ) === 5 ) {
					$l                          = explode( '-', $el );
					$res[ strtolower( $l[0] ) ] = $l[0];
					$res[ strtolower( $l[1] ) ] = strtolower( $l[1] );
				} else {
					$l = explode( ';q=', $el );
					if ( ctype_alnum( $l[0] ) ) {
						$res[ strtolower( $l[0] ) ] = $l[0];
					}
				}
				return $res;
			},
			array()
		)
	);
}

/**
 * It adds a bunch of common honeypot input names to the list of honeypot input names
 *
 * @param array $options The array of input names to check for.
 *
 * @return array An array of possible input names.
 */
function get_honeypot_input_names( $options ) {
	return array_merge(
		$options,
		array(
			'name',
			'email',
			'address',
			'zip',
			'town',
			'phone',
			'credit-card',
			'ship-address',
			'billing_company',
			'billing_city',
			'billing_country',
			'email-address',
		)
	);
}


/**
 * It adds two new cron schedules to WordPress
 *
 * @param array $schedules This is the name of the hook that we're adding a schedule to.
 */
function cf7a_add_cron_steps( $schedules ) {
	$schedules = array(
		'5min'  => array(
			'interval' => 300,
			'display'  => __( 'Every 5 Minutes', 'cf7-antispam' ),
		),
		'60sec' => array(
			'interval' => 60,
			'display'  => __( 'Every 60 seconds', 'cf7-antispam' ),
		),
	);

	return $schedules;
}
add_filter( 'cron_schedules', 'cf7a_add_cron_steps' );

/**
 * It encrypts a string using the WordPress salt as the key
 *
 * @param string $value The value to encrypt.
 * @param string $cipher The cipher method to use.
 *
 * @return string The encrypted value.
 */
function cf7a_crypt( $value, $cipher = 'aes-256-cbc' ) {
	if ( ! extension_loaded( 'openssl' ) ) {
		return $value;
	}
	return openssl_encrypt( $value, $cipher, wp_salt( 'nonce' ), $options = 0, substr( wp_salt( 'nonce' ), 0, 16 ) );
}

/**
 * It decrypts the data.
 *
 * @param string $value The value to be encrypted.
 * @param string $cipher The cipher method to use.
 *
 * @return string The decrypted value.
 */
function cf7a_decrypt( $value, $cipher = 'aes-256-cbc' ) {
	if ( ! extension_loaded( 'openssl' ) ) {
		return $value;
	}
	return openssl_decrypt( $value, $cipher, wp_salt( 'nonce' ), $options = 0, substr( wp_salt( 'nonce' ), 0, 16 ) );
}

/**
 * It returns the current time in microseconds.
 *
 * @return float The current time in microseconds.
 */
function cf7a_microtimeFloat() {
	list($usec, $sec) = explode( ' ', microtime() );
	return (float) $usec + (float) $sec;
}

/**
 * Used to display formatted d8 rating into flamingo inbound
 * @param $rating int - the raw rating
 *
 * @return string - html formatted rating
 */
function cf7a_formatRating( $rating ) {

	if ( ! is_numeric( $rating ) ) {
		return '<span class="flamingo-rating-label" style="background-color:rgb(100,100,100)"><b>' . __( 'none' ) . '</b></span>';
	}

	$red   = floor( 200 * $rating );
	$green = floor( 200 * ( 1 - $rating ) );
	$color = "rgb($red,$green,0)";
	return '<span class="flamingo-rating-label" style="background-color:' . $color . '" ><b>' . round( $rating * 100 ) . '% </b></span>';
}


/**
 * It takes an array and returns a string with the array's keys and values separated by a colon and a space, and each
 * key/value pair separated by a semicolon and a space
 *
 * @param array $array - the array of reasons to ban
 * @param string $is_html - true to return a html string
 *
 * @return false|string Compress arrays into "key:value; " pair
 *
 */
function cf7a_compress_array( $array, $is_html = 0 ) {

	if ( ! is_array( $array ) ) {
		return false;
	}
	$is_html = intval( $is_html );

	return implode(
		'; ',
		array_map(
			function ( $v, $k ) use ( $is_html ) {
				if ( $is_html ) {
					return sprintf( '<b>%s</b>: %s', $k, $v );
				} else {
					return sprintf( '%s: %s', $k, $v );
				}
			},
			$array,
			array_keys( $array )
		)
	);
}
