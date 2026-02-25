<?php
/**
 * Antispam rules and utility functions.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core;

/**
 * Class CF7_AntiSpam_Rules
 *
 * Contains static utility methods used by the anti-spam filters.
 */
class CF7_AntiSpam_Rules {

	/**
	 * It takes an IPv6 address and expands it to its full length
	 *
	 * @param string $ip The IP address to expand.
	 *
	 * @return string The IP address in hexadecimal format.
	 */
	public static function cf7a_expand_ipv6( $ip ) {
		$hex = unpack( 'H*hex', inet_pton( $ip ) );

		return substr( preg_replace( '/([A-f0-9]{4})/', '$1:', $hex['hex'] ), 0, - 1 );
	}

	/**
	 * It takes an IPv4 address, splits it into an array, reverses the order of the array, and then joins the array back
	 * together with periods
	 *
	 * @param string $ip The IP address to reverse.
	 *
	 * @return string
	 */
	public static function cf7a_reverse_ipv4( $ip ) {
		return implode( '.', array_reverse( explode( '.', $ip ) ) );
	}

	/**
	 * It takes an IPv6 address and reverses it.
	 * remove ":" and reverse the string then add a dot for each digit
	 *
	 * @param string $ip The IP address to be converted.
	 *
	 * @return string
	 */
	public static function cf7a_reverse_ipv6( $ip ) {
		$ip = self::cf7a_expand_ipv6( $ip );

		return implode( '.', str_split( strrev( str_replace( ':', '', $ip ) ) ) );
	}

	/**
	 * It checks the DNSBL for the IP address.
	 *
	 * @param string $reverse_ip The IP address in reverse order.
	 * @param string $dnsbl The DNSBL url to check against.
	 *
	 * @return bool if true returns the dnsbl says it is spam otherwise false
	 */
	public static function cf7a_check_dnsbl( $reverse_ip, $dnsbl ) {
		return checkdnsrr( $reverse_ip . '.' . $dnsbl . '.', 'A' );
	}

	/**
	 * Checks the length of a string and returns a specific part of it based on a given index.
	 *
	 * @param string $el The input string to be checked.
	 * @param int    $n The index used to retrieve a specific part of the string.
	 * @return string The extracted part of the string based on the given index, or an empty string if the conditions are not met.
	 */
	public static function cf7a_check_length_exclusive( $el, $n ) {
		if ( strlen( $el ) >= 5 ) {
			$l = explode( '-', $el );
			if ( 0 === $n ) {
				return strtolower( $l[0] );
			} elseif ( 1 === $n ) {
				return strtoupper( $l[1] );
			}
		} elseif ( strlen( $el ) === 2 && ctype_alpha( $el ) ) {
			if ( 0 === $n && ctype_lower( $el ) ) {
				return $el;
			} elseif ( 1 === $n && ctype_upper( $el ) ) {
				return $el;
			}
		}
		return '';
	}

	/**
	 * Retrieves the list of languages or locales from the given options array by key.
	 *
	 * @param array  $option An array of options.
	 * @param string $key The key of the option to retrieve.
	 *
	 * @return array The list of unique languages or locales extracted from the options array.
	 */
	public static function cf7a_get_languages_or_locales( $option, $key ) {
		$languages = array();
		foreach ( $option as $item ) {
			if ( 'languages' === $key ) {
				$l = self::cf7a_check_length_exclusive( $item, 0 );
			} elseif ( 'locales' === $key ) {
				$l = self::cf7a_check_length_exclusive( $item, 1 );
			}
			if ( ! empty( $l ) ) {
				$languages[] = $l;
			}
		}
		return array_values( array_unique( $languages ) );
	}

	/**
	 * Check the languages or locales list for allowed and not allowed.
	 * If the language or locale is not allowed, return the false.
	 * This function is case-sensitive, but maybe this is not wanted
	 *
	 * @param array $languages_locales The languages or locales to check.
	 * @param array $disalloweds An array of languages or locales that are not allowed.
	 * @param array $alloweds An array of allowed languages or locales (has the precedence over the not allowed if specified).
	 */
	public static function cf7a_check_languages_locales_allowed( $languages_locales, $disalloweds = array(), $alloweds = array() ) {
		if ( ! is_array( $languages_locales ) ) {
			$languages_locales = array( $languages_locales );
		}

		if ( ! empty( $alloweds ) ) {
			foreach ( $alloweds as $allowed ) {
				if ( in_array( $allowed, $languages_locales, true ) ) {
					return true;
				}
			}
		}

		if ( ! empty( $disalloweds ) ) {
			foreach ( $disalloweds as $disallowed ) {
				if ( in_array( $disallowed, $languages_locales, true ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Scans the submitted data for email addresses.
	 *
	 * @param array $fields The submitted data.
	 *
	 * @return array An array of valid email addresses.
	 */
	public static function scan_email_tags( array $fields ): array {
		$valid_emails = array();

		foreach ( $fields as $value ) {
			if ( filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
				$valid_emails[] = sanitize_email( $value );
			}
		}

		return $valid_emails;
	}

	/**
	 * Simplify a text removing spaces and converting it to lowercase
	 *
	 * @param string $text Text to simplify
	 *
	 * @return string Simplified text
	 */
	public static function cf7a_simplify_text( string $text ) {
		return str_replace( ' ', '', strtolower( $text ) );
	}

	/**
	 * Search for the message field in the mail tags.
	 *
	 * @param array $mail_tags the array of mail tags
	 *
	 * @return string the name of the message field or false if not found
	 */
	public static function search_for_message_field( array $mail_tags ) {
		foreach ( $mail_tags as $tag ) {
			// if we are lucky and the message tag wasn't changed by the user
			if ( 'message' === $tag->name || 'your-message' === $tag->name ) {
				return $tag->name;
			}
		}
		// if we are unlucky and the message tag was changed by the user
		return false;
	}

	/**
	 * Creates a message from the posted data.
	 *
	 * @param array|null $posted_data the array of posted data
	 *
	 * @return string the message created from the posted data
	 */
	public static function create_message_from_posted_data( ?array $posted_data ): string {
		if ( empty( $posted_data ) ) {
			return '';
		}
		/**
		 * Filters the minimum field length for the auto message.
		 *
		 * @param int $minimum_field_length the minimum field length
		 * @return int the minimum field length
		 */
		$minimum_field_length = apply_filters( 'cf7a_auto_message_minimum_field_length', 20 );
		$message              = '';

		/**
		 * Loops through the posted data and creates a message from it removing:
		 * - the fields that are too short
		 * - the fields that match an email address.
		 * - the fields that match a phone number.
		 *
		 * @param array $posted_data the array of posted data
		 * @return string the message created from the posted data
		 */
		foreach ( $posted_data as $key => $value ) {
			// Handle array values (e.g., checkboxes, multi-selects)
			if ( is_array( $value ) ) {
				$value = implode( ' ', array_filter( $value ) );
			}

			// Skip empty values or non-string values
			if ( ! is_string( $value ) || empty( trim( $value ) ) ) {
				continue;
			}

			// is email?
			if ( is_email( $value ) ) {
				continue;
			}

			// is phone?
			if ( self::is_phone( $value ) ) {
				continue;
			}

			// is too short?
			if ( strlen( $value ) >= $minimum_field_length ) {
				$message .= $value . "\n";
			}
		}//end foreach
		return $message;
	}

	/**
	 * Checks if the value is a phone number.
	 *
	 * @param string $value the value to check
	 *
	 * @return bool true if the value is a phone number, false otherwise
	 */
	public static function is_phone( string $value ): bool {
		return preg_match( '/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/', $value );
	}

	/**
	 * Gets the message from the contact form.
	 *
	 * @param string $message_tag the name of the message tag
	 * @param array  $posted_data the array of posted data
	 * @param array  $mail_tags the array of mail tags
	 *
	 * @return string the message
	 */
	public static function get_email_message( $message_tag, array $posted_data, array $mail_tags ): string {
		/* Getting the message field(s) */
		if ( ! empty( $message_tag ) ) {
			$message_meta = cf7a_get_mail_meta( $message_tag );
			return cf7a_maybe_split_mail_meta( $posted_data, $message_meta );
		}

		// fallback and search for the message field
		$found_tag = self::search_for_message_field( $mail_tags );
		if ( $found_tag ) {
			return cf7a_maybe_split_mail_meta( $posted_data, $found_tag );
		}

		// in this case we will create a message from the posted data removing the "short" fields (because may contain sensitive data e.g. emails, phone numbers, etc.)
		return self::create_message_from_posted_data( $posted_data );
	}
}
