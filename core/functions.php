<?php

/**
 * Some utility function used alongside the plugin
 *
 * @link       codekraft.it
 * @since      0.0.1
 *
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 */

/**
 * If the user is behind a proxy, get the IP address from the HTTP_CF_CONNECTING_IP header, otherwise get the IP address
 * from the REMOTE_ADDR header
 *
 * @return mixed|string - The real ip address.
 */
function cf7a_get_real_ip() {
	// phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___HTTP_X_FORWARDED_FOR__
	$http_x_forwarded_for = ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) : false;
	if ( ! empty( $http_x_forwarded_for ) ) {
		return filter_var( trim( current( explode( ',', $http_x_forwarded_for ) ) ), FILTER_VALIDATE_IP );
	}

	$http_x_real_ip = ! empty( $_SERVER['HTTP_X_REAL_IP'] ) ? filter_var( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ), FILTER_VALIDATE_IP ) : false;
	if ( ! empty( $http_x_real_ip ) ) {
		return $http_x_real_ip;
	}

	// phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__
	$remote_addr = ! empty( $_SERVER['REMOTE_ADDR'] ) ? filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ) : false;
	if ( ! empty( $remote_addr ) ) {
		return $remote_addr;
	}

	$http_client_ip = ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ? filter_var( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ), FILTER_VALIDATE_IP ) : false;
	if ( ! empty( $http_client_ip ) ) {
		return $http_client_ip;
	}

	$http_cf_connecting_ip = ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? filter_var( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ), FILTER_VALIDATE_IP ) : false;
	if ( ! empty( $http_cf_connecting_ip ) ) {
		return $http_cf_connecting_ip;
	}
}

function cf7a_strip_weight( $str ) {
	$str = trim( $str );
	if ( strpos( $str, ';' ) !== false ) {
		$str = substr( $str, 0, strpos( $str, ';' ) );
	}
	return $str;
}

/**
 * Generate an array of languages and locales based on the accept language header.
 *
 * @param string $accept_language The accept language header.
 * @return string[] The array of language-locale codes.
 */
function cf7a_init_languages_locales_array( $accept_language ) {
	return array_reduce(
		explode( ',', $accept_language ),
		function( $res, $el ) {
			$res[] = cf7a_strip_weight( $el );
			return $res;
		},
		array()
	);
}


/**
 * It takes a string of comma-separated language codes, and returns an array of language codes
 *
 * @param string $languages_locales The Accept-Language header sent by the browser.
 *
 * @return array assoc array of languages and locales
 */
function cf7a_get_browser_languages_locales_array( $languages_locales ) {
	$result = array_reduce(
		explode( ',', $languages_locales ),
		function( $res, $el ) {
			$el = cf7a_strip_weight( $el );
			if ( strlen( $el ) >= 5 ) {
				/* split into key: language , value: locale */
				$l                  = explode( '-', $el );
				$res['languages'][] = $l[0];
				$res['locales'][]   = $l[1];
			} elseif ( strlen( $el ) === 2 && ctype_alpha( $el ) ) {
				/* otherwise keep key:language, value: '' (any locale) */
				if ( ctype_lower( $el ) ) {
					$res['languages'][] = $el;
				} elseif ( ctype_upper( $el ) ) {
					$res['locales'][] = $el;
				}
			}
			return $res;
		},
		array()
	);

	if ( ! empty( $result ) ) {
		$result['languages'] = array_values( array_unique( $result['languages'] ) );
		$result['locales']   = array_values( array_unique( $result['locales'] ) );
	}

	return $result;
}

/**
 *
 * Converts HTTP_ACCEPT_LANGUAGE into an array of languages
 * this is a modified version of https://stackoverflow.com/a/33748742/5735847
 *
 * It takes a string like
 * `en-US,en;q=0.9,de;q=0.8,es;q=0.7,fr;q=0.6,it;q=0.5,pt;q=0.4,ru;q=0.3,ja;q=0.2,zh-CN;q=0.1,zh-TW;q=0.1` and returns an
 * array like `[ 'en', 'de', 'es', 'fr', 'it', 'pt', 'ru', 'ja', 'zh']`
 *
 * @param string $languages The Accept-Language header from the browser.
 *
 * @return array An array of languages.
 */
function cf7a_get_accept_language_array( $languages ) {
	return array_values(
		array_reduce(
			explode( ',', str_replace( ' ', '', $languages ) ),
			function ( $res, $el ) {
				if ( strlen( $el ) === 5 ) {
					$l                          = explode( '-', $el );
					$res[ strtolower( $l[0] ) ] = $l[0];
				} else {
					$l = cf7a_strip_weight( $el );
					if ( ctype_alpha( $l[0] ) && ctype_lower( $l[0] ) ) {
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
 * Generates an array of accepted locales based on the given input.
 *
 * @param string $locales A comma-separated list of locales.
 * @return array An array of accepted locales.
 */
function cf7a_get_accept_locales_array( $locales ) {
	return array_values(
		array_reduce(
			explode( ',', str_replace( ' ', '', $locales ) ),
			function ( $res, $el ) {
				if ( strlen( $el ) === 5 ) {
					$l                          = explode( '-', $el );
					$res[ strtolower( $l[1] ) ] = $l[1];
				} else {
					$l = cf7a_strip_weight( $el );
					if ( ctype_alpha( $l[0] ) && ctype_upper( $l[0] ) ) {
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
 * It adds two new cron schedules to WordPress
 *
 * @param array $schedules This is the name of the hook that we're adding a schedule to.
 */
function cf7a_add_cron_steps( $schedules ) {
	return array_merge(
		$schedules,
		array(
			'5min'  => array(
				'interval' => 300,
				'display'  => __( 'Every 5 Minutes', 'cf7-antispam' ),
			),
			'60sec' => array(
				'interval' => 60,
				'display'  => __( 'Every 60 seconds', 'cf7-antispam' ),
			),
		)
	);
}

add_filter( 'cron_schedules', 'cf7a_add_cron_steps' );

/**
 * It adds a bunch of common honeypot input names to the list of honeypot input names
 *
 * @param array $custom_names The array of input names to check for.
 *
 * @return array An array of possible input names.
 */
function get_honeypot_input_names( $custom_names = array() ) {
	$defaults = array(
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
	);

	return array_unique(
		array_merge(
			$defaults,
			(array) $custom_names
		)
	);
}

/**
 * It encrypts a string using the WordPress salt as the key
 *
 * @param string|int $value The value to encrypt.
 * @param string     $cipher The cipher method to use.
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
function cf7a_microtime_float() {
	$time = explode( ' ', microtime() );

	return (float) $time[0] + (float) $time[1];
}

/**
 * It takes three numbers (red, green, and blue) and returns a hexadecimal color code
 *
 * @param int $r red.
 * @param int $g green.
 * @param int $b blue.
 *
 * @return string A hexadecimal color code.
 */
function cf7a_rgb2hex( $r, $g, $b ) {
	return sprintf( '#%02x%02x%02x', (int) $r, (int) $g, (int) $b );
}


/**
 * Used to display formatted d8 rating into flamingo inbound
 *
 * @param numeric $rating - the raw rating.
 *
 * @return string - html formatted rating
 */
function cf7a_format_rating( $rating ) {
	if ( ! is_numeric( $rating ) ) {
		return '<span class="flamingo-rating-label cf7a-tag-none" style="background-color: #999"><b>' . __( 'none' ) . '</b></span>';
	}

	$red   = floor( 200 * $rating );
	$green = floor( 200 * ( 1 - $rating ) );

	$color = cf7a_rgb2hex( $red, $green, 0 );

	return '<span class="flamingo-rating-label" style="background-color: ' . $color . '"><b>' . round( $rating * 100 ) . '% </b></span>';
}

/**
 * It takes a number and returns a color based on that number.
 *
 * @param numeric $rank The rank of the page.
 *
 * @return string an icon with a red color, that becomes greener when the rank is high
 */
function cf7a_format_status( $rank ) {
	$rank = intval( $rank );
	switch ( true ) {
		case $rank < 0:
			/* translators: warn because not yet banned but already listed */
			$rank_clean = esc_html__( '⚠️' );
			break;
		case $rank > 100:
			/* translators: champion of spammer (>100 mail) */
			$rank_clean = esc_html__( '🏆' );
			break;
		default:
			$rank_clean = $rank;
	}

	if ( $rank > 0 ) {
		$green = intval( max( 200 - ( $rank * 2 ), 0 ) );
		$color = cf7a_rgb2hex( 250, $green, 0 );
	} else {
		$color = '#aaa';
	}

	return sprintf( '<span class="ico" style="background-color: %s">%s</span>', $color, $rank_clean );
}

/**
 * It takes an array and returns a string with the array's keys and values separated by a colon and a space, and each
 * key/value pair separated by a semicolon and a space
 *
 * @param array $array - the array of reasons to ban.
 * @param bool  $is_html - true to return a html string.
 *
 * @return false|string Compress arrays into "key:value; " pair
 */
function cf7a_compress_array( $array, $is_html = false ) {
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

/**
 * If the string is not empty, and the log level is 0 or 1 and debug is on, or the log level is 2 and extended debug is
 * on, then log the string
 *
 * @param string|array $log_data - The string/array to log.
 * @param numeric      $log_level 0 = log always, 1 = logging, 2 = only extended logging.
 *
 * @return void
 */
function cf7a_log( $log_data, $log_level = 0 ) {
	if ( ! empty( $log_data ) ) {
		if ( 0 === $log_level || 1 >= $log_level && CF7ANTISPAM_DEBUG || 2 >= $log_level && CF7ANTISPAM_DEBUG_EXTENDED ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				is_string( $log_data )
					? CF7ANTISPAM_LOG_PREFIX . $log_data
					// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r
					: CF7ANTISPAM_LOG_PREFIX . print_r( $log_data, true )
			);
		}
	}
}

/**
 * It takes a string of metadata and unquote it
 * the part between the first two characters and the last two characters.
 *
 * @param string $tag The raw message string.
 *
 * @return string the clean tag string
 */
function cf7a_get_mail_meta( $tag ) {
	return is_string( $tag ) ? substr( $tag, 2, - 2 ) : '';
}


/**
 * If the message tag contains a space, it's a multiple meta tag,
 * so split it up and return the value of the meta tag
 *
 * @param array  $posted_data The form data array.
 * @param string $message_tag The tag of the field you want to retrieve.
 * @param string $explode_pattern Used to split multiple cf7 user tags .
 *
 * @return string|false the field requested
 */
function cf7a_maybe_split_mail_meta( $posted_data, $message_tag, $explode_pattern = '] [' ) {
	if ( strpos( $message_tag, $explode_pattern ) !== false ) {
		$message = '';
		foreach ( explode( $explode_pattern, $message_tag ) as $message_tag_chunk ) {
			$tag_chunk = sanitize_title( $message_tag_chunk );
			if ( ! empty( $posted_data[ $tag_chunk ] ) ) {
				$message .= sanitize_title( $tag_chunk ) . ': ' . sanitize_textarea_field( $posted_data[ $tag_chunk ] ) . "\r\n";
			}
		}

		return $message;
	} else {
		return isset( $posted_data[ $message_tag ] ) ? sanitize_textarea_field( $posted_data[ $message_tag ] ) : false;
	}
}

/**
 * This is a PHP function that takes in a string array and returns an unsigned integer array.
 *
 * @param array $str_array The string array to be converted.
 *
 * @return array $num_array The unsigned integer array.
 */
function cf7a_str_array_to_uint_array( $str_array ) {
	return array_unique(
		array_filter(
			$str_array,
			function ( $value ) {
				return is_int( $value ) || is_numeric( $value ) && $value > 0 && intval( $value ) == $value;
			}
		)
	);
}
