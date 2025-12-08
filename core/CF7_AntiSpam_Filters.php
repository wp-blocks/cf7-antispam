<?php

/**
 * Antispam functions.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core;

use CF7_AntiSpam\Admin\CF7_AntiSpam_Admin_Tools;
use Exception;
use WPCF7_Submission;

/**
 * A class that is used to filter out spam.
 */
class CF7_AntiSpam_Filters {

	/**
	 * CF7_AntiSpam_Filters constructor.
	 * Registers the individual spam checks to the custom filter hook.
	 */
	public function __construct() {
		// Priority 5: Whitelist checks (should run first to stop processing if safe)
		add_filter( 'cf7a_spam_check_chain', array( $this, 'filter_ip_whitelist' ), 5 );

		// Priority 10: Standard checks
		add_filter( 'cf7a_spam_check_chain', array( $this, 'filter_empty_ip' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( $this, 'filter_bad_ip' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( $this, 'filter_ip_blacklist_history' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( $this, 'filter_honeyform' ), 10 );

		// Checks that originally ran only if score < 1 (See logic inside methods)
		add_filter( 'cf7a_spam_check_chain', array( $this, 'filter_referrer_protocol' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( $this, 'filter_plugin_version' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( $this, 'filter_bot_fingerprint' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( $this, 'filter_bot_fingerprint_extras' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( $this, 'filter_language' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( $this, 'filter_geoip' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( $this, 'filter_time_submission' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( $this, 'filter_bad_email_strings' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( $this, 'filter_user_agent' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( $this, 'filter_bad_words' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( $this, 'filter_dnsbl' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( $this, 'filter_honeypot' ), 10 );

		// Priority 20: Bayesian filter
		add_filter( 'cf7a_spam_check_chain', array( $this, 'filter_b8_bayesian' ), 20 );
	}

	// ---------------------
	// STATIC HELPER METHODS
	// ---------------------

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
	public function cf7a_check_length_exclusive( $el, $n ) {
		if ( strlen( $el ) >= 5 ) {
			$l = explode( '-', $el );
			if ( 0 == $n ) {
				return strtolower( $l[0] );
			} elseif ( 1 == $n ) {
				return strtoupper( $l[1] );
			}
		} elseif ( strlen( $el ) === 2 && ctype_alpha( $el ) ) {
			if ( 0 == $n && ctype_lower( $el ) ) {
				return $el;
			} elseif ( 1 == $n && ctype_upper( $el ) ) {
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
	public function cf7a_get_languages_or_locales( $option, $key ) {
		$languages = array();
		foreach ( $option as $item ) {
			if ( 'languages' === $key ) {
				$l = $this->cf7a_check_length_exclusive( $item, 0 );
			} elseif ( 'locales' === $key ) {
				$l = $this->cf7a_check_length_exclusive( $item, 1 );
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
	public function cf7a_check_languages_locales_allowed( $languages_locales, $disalloweds = array(), $alloweds = array() ) {
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


	public function scan_email_tags( $fields ) {
		$validEmails = array();

		foreach ( $fields as $value ) {
			if ( filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
				$validEmails[] = sanitize_email( $value );
			}
		}

		return $validEmails;
	}

	/**
	 * Simplify a text removing spaces and converting it to lowercase
	 *
	 * @param $text string Text to simplify
	 *
	 * @return string Simplified text
	 */
	public function cf7a_simplify_text( $text ) {
		return str_replace( ' ', '', strtolower( $text ) );
	}

	// ------------------------
	// MAIN FILTER ORCHESTRATOR
	// ------------------------

	/**
	 * CF7_AntiSpam_Filters The antispam filter
	 *
	 * @param boolean $spam - spam or not.
	 *
	 * @return boolean
	 */
	/**
	 * CF7_AntiSpam_Filters The antispam filter
	 * Refactored to use a filter chain pipeline.
	 *
	 * @param boolean $spam - spam or not.
	 *
	 * @return boolean
	 */
	public function cf7a_spam_filter( $spam ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing

		/* Get the submitted data */
		$submission = WPCF7_Submission::get_instance();

		if ( ! $submission ) {
			return true;
		}

		/* Get the contact form additional data */
		$posted_data  = $submission->get_posted_data();
		$contact_form = $submission->get_contact_form();

		/* Get plugin options */
		$options = get_option( 'cf7a_options', array() );

		/* Check the period of grace and, if it is expired, reset the error count */
		if ( !empty( $options['last_update_data']['errors'] ) ) {
			$period_of_grace = apply_filters('cf7a_period_of_grace', WEEK_IN_SECONDS);
			if ( time() - $options['last_update_data']['time'] > $period_of_grace ) {
				$options['last_update_data']['errors'] = array();
			}
			// then save the updated options to the database
			update_option( 'cf7a_options', $options );
		}

		/* Get basic submission details */
		$mail_tags = $contact_form->scan_form_tags();
		$email_tag = sanitize_title( cf7a_get_mail_meta( $contact_form->pref( 'flamingo_email' ) ) );
		$emails    = isset( $posted_data[ $email_tag ] ) ? array( $posted_data[ $email_tag ] ) : $this->scan_email_tags( $mail_tags );

		/**
		 * Get the message from the contact form
		 */
		$message = $this->get_email_message(
			sanitize_text_field( $contact_form->pref( 'flamingo_message' ) ),
			$posted_data,
			$mail_tags
		);

		/**
		 * Let developers hack the message
		 */
		$message = apply_filters( 'cf7a_message_before_processing', $message, $posted_data );

		/* Prepare IP and basic user data */
		$prefix  = sanitize_text_field( $options['cf7a_customizations_prefix'] );
		// The right way to do this is BEFORE decrypting and THEN sanitize, because sanitized data are stripped of any special characters
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$real_remote_ip = isset( $_POST[ $prefix . 'address' ] ) ? sanitize_text_field( wp_unslash( cf7a_decrypt( $_POST[ $prefix . 'address' ], $options['cf7a_cipher'] ) ) ) : false;
		$remote_ip      = $real_remote_ip ? filter_var( $real_remote_ip, FILTER_VALIDATE_IP ) : false;
		$cf7_remote_ip  = filter_var( $submission->get_meta( 'remote_ip' ), FILTER_VALIDATE_IP );
		$user_agent     = sanitize_text_field( $submission->get_meta( 'user_agent' ) );

		// -------------------------------------------------------------
		// BUILD THE DATA OBJECT (Context)
		// -------------------------------------------------------------
		$spam_data = array(
			'submission'    => $submission,
			'options'       => $options,
			'posted_data'   => $posted_data,
			'remote_ip'     => $remote_ip,
			'cf7_remote_ip' => $cf7_remote_ip,
			'emails'        => $emails,
			'message'       => $message,
			'mail_tags'     => $mail_tags,
			'user_agent'    => $user_agent,
			// State trackers
			'spam_score'    => 0,
			'is_spam'       => $spam,
			'reasons'       => array(),
			'is_whitelisted'=> false, // Flag to stop processing
		);

		if (CF7ANTISPAM_DEBUG_EXTENDED) {
			cf7a_log( "New submission from " . $remote_ip . " will be processed", 1 );
		}

		/**
		 * RUN THE FILTER CHAIN
		 * This triggers all the checks registered in __construct
		 */
		$spam_data = apply_filters( 'cf7a_spam_check_chain', $spam_data );

		/**
		 * BAYESIAN FILTER (B8)
		 * Placed explicitly here to ensure it runs at the end of the function,
		 * regardless of previous spam detection (unless whitelisted).
		 */
		$spam_data = apply_filters( 'cf7a_check_b8', $spam_data );

		// Extract results
		$spam_score = $spam_data['spam_score'];
		$reason     = $spam_data['reasons'];
		$spam       = $spam_data['is_spam'];
		$remote_ip  = $spam_data['remote_ip'] ? $spam_data['remote_ip'] : $spam_data['cf7_remote_ip'];

		/**
		 * Final filter before the ban
		 *
		 * @param bool $spam
		 * @param string $message
		 * @param WPCF7_Submission $submission
		 */
		$spam = apply_filters( 'cf7a_additional_spam_filters', $spam, $message, $submission );

		/* If the spam score is lower than 1 the mail is ham */
		if ( $spam_score < 1 && ! $spam ) {
			return $spam; // Usually false
		}

		/* Prepare for ban/logging */
		$reasons_for_ban = cf7a_compress_array( $reason );

		/* If the auto-store ip is enabled */
		if ( isset($options['autostore_bad_ip']) && $options['autostore_bad_ip'] ) {
			if ( CF7_Antispam_Blacklist::cf7a_ban_by_ip( $remote_ip, $reason, round( $spam_score ) ) ) {
				cf7a_log( "Ban for $remote_ip - results - " . $reasons_for_ban, 2 );
			} else {
				cf7a_log( "Unable to ban $remote_ip" );
			}
		}

		/* Store the ban reason into mail post-metadata */
		$submission->add_spam_log(
			array(
				'agent'  => 'CF7-AntiSpam',
				'reason' => $reasons_for_ban,
			)
		);

		return true;
	}

	// -------------------------
	// INDIVIDUAL FILTER METHODS
	// -------------------------

	/**
	 * Checks for IP whitelist.
	 */
	public function filter_ip_whitelist( $data ) {
		$ip_whitelist = $data['options']['ip_whitelist'] ?? array();

		if ( ! empty( $ip_whitelist ) && $data['remote_ip'] ) {
			foreach ( $ip_whitelist as $good_ip ) {
				$good_ip = filter_var( $good_ip, FILTER_VALIDATE_IP );
				// Use strict equality to avoid partial matches (e.g., 1.2.3.4 matching 1.2.3.40)
				if ( $good_ip && $data['remote_ip'] === $good_ip ) {
					$data['is_whitelisted'] = true;
					return $data;
				}
			}
		}
		return $data;
	}

	/**
	 * Checks if IP is empty.
	 */
	public function filter_empty_ip( $data ) {
		if ( $data['is_whitelisted'] ) return $data;

		if ( ! $data['remote_ip'] ) {
			// Fallback to CF7 IP if main is missing, but flag as spam
			$data['remote_ip'] = $data['cf7_remote_ip'] ? $data['cf7_remote_ip'] : null;

			$data['spam_score']++;
			$data['is_spam'] = true;
			$data['reasons']['no_ip'] = 'Address field empty';

			cf7a_log( "ip address field of {$data['remote_ip']} is empty, this means it has been modified, removed or hacked!", 1 );
		}
		return $data;
	}

	/**
	 * Checks against local bad IP list.
	 */
	public function filter_bad_ip( $data ) {
		if ( $data['is_whitelisted'] ) return $data;

		$options = $data['options'];
		$bad_ip_list = isset( $options['bad_ip_list'] ) ? $options['bad_ip_list'] : array();

		if ( intval( $options['check_bad_ip'] ) === 1 && $data['remote_ip'] ) {
			foreach ( $bad_ip_list as $bad_ip ) {
				$bad_ip = filter_var( $bad_ip, FILTER_VALIDATE_IP );
				// Use strict equality to avoid partial matches (e.g., 1.2.3.4 matching 1.2.3.40)
				if ( $bad_ip && $data['remote_ip'] === $bad_ip ) {
					$data['spam_score']++;
					$data['is_spam'] = true;
					$data['reasons']['bad_ip'][] = $bad_ip;
				}
			}

			if ( ! empty( $data['reasons']['bad_ip'] ) && is_array($data['reasons']['bad_ip']) ) {
				$ip_string = implode( ', ', $data['reasons']['bad_ip'] );
				$data['reasons']['bad_ip'] = $ip_string; // Flatten for log
				cf7a_log( "The ip address {$data['remote_ip']} is listed into bad ip list (contains $ip_string)", 1 );
			}
		}
		return $data;
	}

	/**
	 * Checks if IP is already in the database blocklist history.
	 */
	public function filter_ip_blacklist_history( $data ) {
		if ( $data['is_whitelisted'] ) return $data;

		$options = $data['options'];
		if ( $data['remote_ip'] && $options['max_attempts'] ) {
			$ip_data        = CF7_Antispam_Blacklist::cf7a_blacklist_get_ip( $data['remote_ip'] );
			$ip_data_status = isset( $ip_data->status ) ? intval( $ip_data->status ) : 0;
			$max_attempts   = intval( $options['max_attempts'] );

			if ( $ip_data_status >= $max_attempts ) {
				$data['spam_score']++;
				$data['is_spam'] = true;
				$data['reasons']['blocklisted'] = $ip_data_status;

				cf7a_log( "The {$data['remote_ip']} has reached max attempts threshold (status: $ip_data_status, max: $max_attempts)", 1 );
			} elseif ( defined('CF7ANTISPAM_DEBUG') && CF7ANTISPAM_DEBUG && $ip_data_status > 0 ) {
				cf7a_log( sprintf( "The {$data['remote_ip']} has prior history (score $ip_data_status) but still has %d attempts left before reaching max (%d)", $max_attempts - $ip_data_status, $max_attempts ), 1 );
			}
		}
		return $data;
	}

	/**
	 * Checks the HoneyForm (CSS hidden field).
	 */
	public function filter_honeyform( $data ) {
		if ( $data['is_whitelisted'] ) return $data;

		$options = $data['options'];
		if ( intval( $options['check_honeyform'] ) === 1 ) {
			$form_class = sanitize_html_class( $options['cf7a_customizations_class'] );

			if ( isset( $_POST[ '_wpcf7_' . $form_class ] ) ) {
				$data['spam_score']++;
				$data['is_spam'] = true;
				$data['reasons']['honeyform'] = 'true';
			}
		}
		return $data;
	}

	/**
	 * Checks Referrer and Protocol.
	 * Note: In original code, this only runs if spam_score < 1.
	 */
	public function filter_referrer_protocol( $data ) {
		if ( $data['is_whitelisted'] ) return $data;
		if ( $data['is_spam'] ) return $data;

		$options = $data['options'];
		$prefix  = sanitize_text_field( $options['cf7a_customizations_prefix'] );
		$score_warn = floatval( $options['score']['_warn'] );

		if ( intval( $options['check_refer'] ) === 1 ) {
			// The right way to do this is BEFORE decrypting and THEN sanitize, because sanitized data are stripped of any special characters
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$cf7a_referer  = isset( $_POST[ $prefix . 'referer' ] ) ?  sanitize_text_field( wp_unslash( cf7a_decrypt($_POST[ $prefix . 'referer' ], $options['cf7a_cipher'] ) ) ) : false;
			if ( ! $cf7a_referer ) {
				$data['spam_score'] += $score_warn;
				$data['reasons']['no_referrer'] = 'client has referrer address';
				cf7a_log( "the {$data['remote_ip']} has reached the contact form page without any referrer", 1 );
			}
		}

		// The right way to do this is BEFORE decrypting and THEN sanitize, because sanitized data are stripped of any special characters
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$cf7a_protocol = isset( $_POST[ $prefix . 'protocol' ] ) ? sanitize_text_field( wp_unslash( cf7a_decrypt( $_POST[ $prefix . 'protocol' ], $options['cf7a_cipher'] ) ) ) : false;
		if ( $cf7a_protocol ) {
			if ( in_array( $cf7a_protocol, array( 'HTTP/1.0', 'HTTP/1.1', 'HTTP/1.2' ) ) ) {
				$data['spam_score'] += $score_warn;
				$data['reasons']['no_protocol'] = 'client has a bot-like connection protocol';
				cf7a_log( "the {$data['remote_ip']} has a bot-like connection protocol (HTTP/1.X)", 1 );
			}
		}
		return $data;
	}

	/**
	 * Checks Plugin Version match.
	 */
	public function filter_plugin_version( $data ) {
		if ( $data['is_whitelisted'] ) return $data;
		if ( $data['is_spam'] ) return $data;

		$options = $data['options'];
		$prefix  = sanitize_text_field( $options['cf7a_customizations_prefix'] );
		$score_fingerprinting = floatval( $options['score']['_fingerprinting'] );

		// The right way to do this is BEFORE decrypting and THEN sanitize, because sanitized data are stripped of any special characters
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$cf7a_version = isset( $_POST[ $prefix . 'version' ] ) ? sanitize_text_field( wp_unslash( cf7a_decrypt( $_POST[ $prefix . 'version' ], $options['cf7a_cipher'] ) ) ) : false;

		// CASE A: Version field is completely missing or empty -> SPAM
		if ( ! $cf7a_version ) {
			$data['spam_score'] += $score_fingerprinting;
			$data['reasons']['data_mismatch'] = sprintf( "Version mismatch (empty) != '%s'", CF7ANTISPAM_VERSION );
			cf7a_log( sprintf( "The 'version' field submitted by %s is empty", $data['remote_ip'] ), 1 );

			return $data;
		}

		// CASE B: Version matches current version -> OK
		if ( $cf7a_version === CF7ANTISPAM_VERSION ) {
			return $data;
		}

		// CASE C: Version Mismatch logic (Cache vs Spam)
		// Retrieve update data stored during the last plugin update
		$last_update_data = $options['last_update_data'] ?? null;

		// Check if we have update data and if the submitted version matches the PREVIOUS version
		$is_old_version_match = ( $last_update_data && isset( $last_update_data['old_version'] ) && $cf7a_version === $last_update_data['old_version'] );

		// Check if the update happened less than a week ago
		$period_of_grace = apply_filters('cf7a_period_of_grace', WEEK_IN_SECONDS);
		$is_within_grace_period = ( $last_update_data && isset( $last_update_data['time'] ) && ( time() - $last_update_data['time'] ) < $period_of_grace );

		if ( $is_old_version_match && $is_within_grace_period ) {

			// --- CACHE ISSUE DETECTED (FALLBACK) ---
			// Do NOT mark as spam. This is likely a cached user.

			cf7a_log( "Cache mismatch detected for IP {$data['remote_ip']}. Submitted: $cf7a_version. Expected: " . CF7ANTISPAM_VERSION, 1 );

			// Record the error
			if ( ! isset( $options['last_update_data']['errors'] ) ) {
				$options['last_update_data']['errors'] = array();
			}

			// Add error details
			$options['last_update_data']['errors'][] = array(
				'ip'   => $data['remote_ip'],
				'time' => time(),
			);

			$error_count = count( $options['last_update_data']['errors'] );

			// Check trigger for email notification (Exactly on the 5th error)
			$cf7a_period_of_grace_max_attempts = intval(apply_filters( 'cf7a_period_of_grace_max_attempts', 5));
			if ( $cf7a_period_of_grace_max_attempts === $error_count || $error_count * 3 === $cf7a_period_of_grace_max_attempts ) {
				$this->send_cache_warning_email( $options['last_update_data'] );
				cf7a_log( "Cache warning email sent to admin.", 1 );
			}

			// SAVE OPTIONS: We must save the error count to the database
			// Update the local $options variable first so subsequent filters use it if needed (though unlikely)
			$data['options'] = $options;

			// Persist to DB
			update_option( 'cf7a_options', $options );

		} else {

			// --- REAL SPAM / INVALID VERSION ---
			// Either the grace period expired, or the version is completely random

			$data['spam_score'] += $score_fingerprinting;
			$data['reasons']['data_mismatch'] = "Version mismatch '$cf7a_version' != '" . CF7ANTISPAM_VERSION . "'";
			cf7a_log( "The 'version' field submitted by {$data['remote_ip']} is mismatching (expired grace period or invalid)", 1 );
		}

		return $data;
	}

	/**
	 * Checks Browser Fingerprint (JS based).
	 */
	public function filter_bot_fingerprint( $data ) {
		if ( $data['is_whitelisted'] ) return $data;
		if ( $data['is_spam'] ) return $data;

		$options = $data['options'];
		if ( intval( $options['check_bot_fingerprint'] ) !== 1 ) return $data;

		$prefix  = sanitize_text_field( $options['cf7a_customizations_prefix'] );
		$score_fingerprinting = floatval( $options['score']['_fingerprinting'] );

		$bot_fingerprint = array(
			'timezone'        => ! empty( $_POST[ $prefix . 'timezone' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $prefix . 'timezone' ] ) ) : null,
			'platform'        => ! empty( $_POST[ $prefix . 'platform' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $prefix . 'platform' ] ) ) : null,
			'screens'         => ! empty( $_POST[ $prefix . 'screens' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $prefix . 'screens' ] ) ) : null,
			'memory'          => ! empty( $_POST[ $prefix . 'memory' ] ) ? intval( $_POST[ $prefix . 'memory' ] ) : null,
			'user_agent'      => ! empty( $_POST[ $prefix . 'user_agent' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $prefix . 'user_agent' ] ) ) : null,
			'app_version'     => ! empty( $_POST[ $prefix . 'app_version' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $prefix . 'app_version' ] ) ) : null,
			'webdriver'       => ! empty( $_POST[ $prefix . 'webdriver' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $prefix . 'webdriver' ] ) ) : null,
			'session_storage' => ! empty( $_POST[ $prefix . 'session_storage' ] ) ? intval( $_POST[ $prefix . 'session_storage' ] ) : null,
			'bot_fingerprint' => ! empty( $_POST[ $prefix . 'bot_fingerprint' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $prefix . 'bot_fingerprint' ] ) ) : null,
			'touch'           => ! empty( $_POST[ $prefix . 'touch' ] ),
		);

		$fails = array();
		if ( ! $bot_fingerprint['timezone'] ) $fails[] = 'timezone';
		if ( ! $bot_fingerprint['platform'] ) $fails[] = 'platform';
		if ( ! $bot_fingerprint['screens'] ) $fails[] = 'screens';
		if ( ! $bot_fingerprint['user_agent'] ) $fails[] = 'user_agent';
		if ( ! $bot_fingerprint['app_version'] ) $fails[] = 'app_version';
		if ( ! $bot_fingerprint['webdriver'] ) $fails[] = 'webdriver';
		if ( $bot_fingerprint['session_storage'] === null ) $fails[] = 'session_storage';
		if ( 5 !== strlen( $bot_fingerprint['bot_fingerprint'] ) ) $fails[] = 'bot_fingerprint';

		if ( isset( $_POST[ $prefix . 'isIos' ] ) || isset( $_POST[ $prefix . 'isFFox' ] ) || isset( $_POST[ $prefix . 'isIE' ] ) ) {
			if ( $bot_fingerprint['memory'] ) $fails[] = 'memory_supported';
		} elseif ( ! $bot_fingerprint['memory'] ) {
			$fails[] = 'memory';
		}

		if ( isset( $_POST[ $prefix . 'isIos' ] ) || isset( $_POST[ $prefix . 'isAndroid' ] ) ) {
			if ( ! $bot_fingerprint['touch'] ) $fails[] = 'touch';
		}

		if ( ! empty( $fails ) ) {
			$data['spam_score'] += count( $fails ) * $score_fingerprinting;
			$data['reasons']['bot_fingerprint'] = implode( ', ', $fails );
			cf7a_log( "The {$data['remote_ip']} ip hasn't passed fingerprint test ({$data['reasons']['bot_fingerprint']})", 1 );
		}

		return $data;
	}

	/**
	 * Checks Bot Fingerprint Extras (User activity).
	 */
	public function filter_bot_fingerprint_extras( $data ) {
		if ( $data['is_whitelisted'] ) return $data;
		if ( $data['is_spam'] ) return $data;

		$options = $data['options'];
		if ( intval( $options['check_bot_fingerprint_extras'] ) !== 1 ) return $data;

		$prefix  = sanitize_text_field( $options['cf7a_customizations_prefix'] );
		$score_fingerprinting = floatval( $options['score']['_fingerprinting'] );

		$extras = array(
			'activity'               => ! empty( $_POST[ $prefix . 'activity' ] ) ? intval( $_POST[ $prefix . 'activity' ] ) : 0,
			'mouseclick_activity'    => ! empty( $_POST[ $prefix . 'mouseclick_activity' ] ) && sanitize_text_field( wp_unslash( $_POST[ $prefix . 'mouseclick_activity' ] ) ) === 'passed',
			'mousemove_activity'     => ! empty( $_POST[ $prefix . 'mousemove_activity' ] ) && sanitize_text_field( wp_unslash( $_POST[ $prefix . 'mousemove_activity' ] ) ) === 'passed',
			'webgl'                  => ! empty( $_POST[ $prefix . 'webgl' ] ) && sanitize_text_field( wp_unslash( $_POST[ $prefix . 'webgl' ] ) ) === 'passed',
			'webgl_render'           => ! empty( $_POST[ $prefix . 'webgl_render' ] ) && sanitize_text_field( wp_unslash( $_POST[ $prefix . 'webgl_render' ] ) ) === 'passed',
			'bot_fingerprint_extras' => empty( $_POST[ $prefix . 'bot_fingerprint_extras' ] ),
		);

		$fails = array();
		if ( $extras['activity'] < 3 ) $fails[] = "activity {$extras["activity"]}";
		if ( empty( $extras['mouseclick_activity'] ) ) $fails[] = 'mouseclick_activity';
		if ( empty( $extras['mousemove_activity'] ) ) $fails[] = 'mousemove_activity';
		if ( empty( $extras['webgl'] ) ) $fails[] = 'webgl';
		if ( empty( $extras['webgl_render'] ) ) $fails[] = 'webgl_render';
		if ( empty( $extras['bot_fingerprint_extras'] ) ) $fails[] = 'bot_fingerprint_extras';

		if ( ! empty( $fails ) ) {
			$data['spam_score'] += count( $fails ) * $score_fingerprinting;
			$data['reasons']['bot_fingerprint_extras'] = implode( ', ', $fails );
			cf7a_log( "The {$data['remote_ip']} ip hasn't passed fingerprint extra test", 1 );
		}

		return $data;
	}

	/**
	 * Checks Language consistency.
	 */
	public function filter_language( $data ) {
		if ( $data['is_whitelisted'] ) return $data;
		if ( $data['is_spam'] ) return $data;

		$options = $data['options'];
		if ( intval( $options['check_language'] ) !== 1 ) return $data;

		$prefix  = sanitize_text_field( $options['cf7a_customizations_prefix'] );
		$score_detection = floatval( $options['score']['_detection'] );

		$languages = array();
		$languages['browser_language'] = ! empty( $_POST[ $prefix . 'browser_language' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $prefix . 'browser_language' ] ) ) : null;

		// The right way to do this is BEFORE decrypting and THEN sanitize, because sanitized data are stripped of any special characters
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$languages['accept_language']  = isset( $_POST[ $prefix . '_language' ] ) ? sanitize_text_field( wp_unslash( cf7a_decrypt( $_POST[ $prefix . '_language' ], $options['cf7a_cipher'] ) ) ) : null;

		if ( empty( $languages['browser_language'] ) ) {
			$data['spam_score'] += $score_detection;
			$data['reasons']['browser_language'] = 'missing browser language';
		} else {
			$languages_locales    = cf7a_get_browser_languages_locales_array( $languages['browser_language'] );
			$languages['browser'] = $languages_locales['languages'];
		}

		if ( empty( $languages['accept_language'] ) ) {
			$data['spam_score'] += $score_detection;
			$data['reasons']['language_field'] = 'missing language field';
		} else {
			$languages['accept'] = cf7a_get_accept_language_array( $languages['accept_language'] );
		}

		if ( ! empty( $languages['accept'] ) && ! empty( $languages['browser'] ) ) {
			if ( ! array_intersect( $languages['browser'], $languages['accept'] ) ) {
				$data['spam_score'] += $score_detection;
				$data['reasons']['language_incoherence'] = 'languages detected not coherent';
			}

			$client_languages = array_unique( array_merge( $languages['browser'], $languages['accept'] ) );
			$languages_allowed    = isset( $options['languages_locales']['allowed'] ) ? $this->cf7a_get_languages_or_locales( $options['languages_locales']['allowed'], 'languages' ) : array();
			$languages_disallowed = isset( $options['languages_locales']['disallowed'] ) ? $this->cf7a_get_languages_or_locales( $options['languages_locales']['disallowed'], 'languages' ) : array();

			$language_disallowed = $this->cf7a_check_languages_locales_allowed( $client_languages, $languages_disallowed, $languages_allowed );

			if ( false === $language_disallowed ) {
				$data['spam_score'] += $score_detection;
				$data['reasons']['disallowed_language'] = implode( ', ', $client_languages );
			}
		}
		return $data;
	}

	/**
	 * Checks GeoIP Location.
	 */
	public function filter_geoip( $data ) {
		if ( $data['is_whitelisted'] ) return $data;
		if ( $data['is_spam'] ) return $data;

		$options = $data['options'];
		if ( intval( $options['check_geo_location'] ) !== 1 ) return $data;

		$geoip = new CF7_Antispam_Geoip();
		$score_warn = floatval( $options['score']['_warn'] );
		$locales_allowed    = $this->cf7a_get_languages_or_locales( $options['languages_locales']['allowed'], 'locales' );
		$locales_disallowed = $this->cf7a_get_languages_or_locales( $options['languages_locales']['disallowed'], 'locales' );

		if ( ! empty( $geoip ) ) {
			try {
				$geoip_data      = $geoip->check_ip( $data['remote_ip'] );
				$geoip_continent = isset( $geoip_data['continent'] ) ? ( $geoip_data['continent'] ) : false;
				$geoip_country   = isset( $geoip_data['country'] ) ? ( $geoip_data['country'] ) : false;
				$geo_data        = array_filter( array( $geoip_continent, $geoip_country ) );

				if ( ! empty( $geo_data ) ) {
					if ( false === $this->cf7a_check_languages_locales_allowed( $geo_data, $locales_disallowed, $locales_allowed ) ) {
						$data['reasons']['geo_ip'] = $geoip_continent . '-' . $geoip_country;
						$data['spam_score'] += $score_warn;
						cf7a_log( "The {$data['remote_ip']} is not allowed by geoip" . $data['reasons']['geo_ip'], 1 );
					}
				} else {
					// Don't add to reasons if GeoIP lookup returned no data - just log it
					cf7a_log( "GeoIP lookup returned no data for {$data['remote_ip']}", 1 );
				}
			} catch ( Exception $e ) {
				cf7a_log( "unable to check geoip for {$data['remote_ip']} - " . $e->getMessage(), 1 );
			}
		}
		return $data;
	}

	/**
	 * Checks Time of submission.
	 */
	public function filter_time_submission( $data ) {
		if ( $data['is_whitelisted'] ) return $data;
		if ( $data['is_spam'] ) return $data;

		$options = $data['options'];
		if ( intval( $options['check_time'] ) !== 1 ) return $data;

		$prefix  = sanitize_text_field( $options['cf7a_customizations_prefix'] );

		$score_time = floatval( $options['score']['_time'] );
		$score_detection = floatval( $options['score']['_detection'] );

		// The right way to do this is BEFORE decrypting and THEN sanitize, because sanitized data are stripped of any special characters
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$timestamp = isset( $_POST[ $prefix . '_timestamp' ] ) ? intval( cf7a_decrypt( $_POST[ $prefix . '_timestamp' ], $options['cf7a_cipher'] ) ) : 0;
		$time_now         = time();
		$time_elapsed_min = intval( $options['check_time_min'] );
		$time_elapsed_max = intval( $options['check_time_max'] );

		if ( ! $timestamp ) {
			$data['spam_score'] += $score_detection;
			$data['reasons']['timestamp'] = 'missing field';
			cf7a_log( "The {$data['remote_ip']} ip _timestamp field is missing", 1 );
		} else {
			$time_elapsed = $time_now - $timestamp;

			if ( 0 !== $time_elapsed_min && $time_elapsed < $time_elapsed_min ) {
				$data['spam_score'] += $score_time;
				$data['reasons']['min_time_elapsed'] = $time_elapsed;
				cf7a_log( "The {$data['remote_ip']} ip took too little time ($time_elapsed s)", 1 );
			}

			if ( 0 !== $time_elapsed_max && $time_elapsed > $time_elapsed_max ) {
				$data['spam_score'] += $score_time;
				$data['reasons']['max_time_elapsed'] = $time_elapsed;
				cf7a_log( "The {$data['remote_ip']} ip took too much time ($time_elapsed s)", 1 );
			}
		}
		return $data;
	}

	/**
	 * Checks for bad strings inside the email address.
	 */
	public function filter_bad_email_strings( $data ) {
		if ( $data['is_whitelisted'] ) return $data;
		if ( $data['is_spam'] ) return $data;

		$options = $data['options'];
		if ( intval( $options['check_bad_email_strings'] ) !== 1 || empty( $data['emails'] ) ) return $data;

		$score_bad_string = floatval( $options['score']['_bad_string'] );
		$bad_email_strings = isset( $options['bad_email_strings_list'] ) ? $options['bad_email_strings_list'] : array();

		foreach ( $data['emails'] as $email ) {
			foreach ( $bad_email_strings as $bad_email_string ) {
				if ( false !== stripos( strtolower( $email ), strtolower( $bad_email_string ) ) ) {
					$data['spam_score'] += $score_bad_string;
					$data['reasons']['email_blacklisted'][] = $bad_email_string;
				}
			}
		}

		if ( isset( $data['reasons']['email_blacklisted'] ) && is_array($data['reasons']['email_blacklisted']) ) {
			$data['reasons']['email_blacklisted'] = implode( ',', $data['reasons']['email_blacklisted'] );
			cf7a_log( "The ip address {$data['remote_ip']} sent a mail using bad string {$data['reasons']['email_blacklisted']}", 1 );
		}

		return $data;
	}

	/**
	 * Checks User Agent.
	 */
	public function filter_user_agent( $data ) {
		if ( $data['is_whitelisted'] ) return $data;
		if ( $data['is_spam'] ) return $data;

		$options = $data['options'];
		if ( intval( $options['check_bad_user_agent'] ) !== 1 ) return $data;

		$score_detection = floatval( $options['score']['_detection'] );
		$score_bad_string = floatval( $options['score']['_bad_string'] );
		$bad_user_agent_list = isset( $options['bad_user_agent_list'] ) ? $options['bad_user_agent_list'] : array();

		if ( ! $data['user_agent'] ) {
			$data['spam_score'] += $score_detection;
			$data['reasons']['user_agent'] = 'empty';
			cf7a_log( "The {$data['remote_ip']} ip user agent is empty", 1 );
		} else {
			foreach ( $bad_user_agent_list as $bad_user_agent ) {
				if ( false !== stripos( strtolower( $data['user_agent'] ), strtolower( $bad_user_agent ) ) ) {
					$data['spam_score'] += $score_bad_string;
					$data['reasons']['user_agent'][] = $bad_user_agent;
				}
			}

			if ( isset( $data['reasons']['user_agent'] ) && is_array( $data['reasons']['user_agent'] ) ) {
				$data['reasons']['user_agent'] = implode( ', ', $data['reasons']['user_agent'] );
				cf7a_log( "The {$data['remote_ip']} ip user agent was listed into bad user agent list", 1 );
			}
		}
		return $data;
	}

	/**
	 * Checks for bad words in message.
	 */
	public function filter_bad_words( $data ) {
		if ( $data['is_whitelisted'] ) return $data;
		if ( $data['is_spam'] ) return $data;

		$options = $data['options'];
		if ( intval( $options['check_bad_words'] ) !== 1 || '' === $data['message'] ) return $data;

		$score_bad_string = floatval( $options['score']['_bad_string'] );
		$bad_words = $options['bad_words_list'] ?? array();
		$message_compressed = $this->cf7a_simplify_text( $data['message'] );

		foreach ( $bad_words as $bad_word ) {
			if ( false !== stripos( $message_compressed, $this->cf7a_simplify_text( $bad_word ) ) ) {
				$data['spam_score'] += $score_bad_string;
				$data['reasons']['bad_word'][] = $bad_word;
			}
		}

		if ( ! empty( $data['reasons']['bad_word'] ) && is_array($data['reasons']['bad_word']) ) {
			$data['reasons']['bad_word'] = implode( ',', $data['reasons']['bad_word'] );
			cf7a_log( "{$data['remote_ip']} has bad word in message " . $data['reasons']['bad_word'], 1 );
		}
		return $data;
	}

	/**
	 * Checks DNS Blocklist.
	 */
	public function filter_dnsbl( $data ) {
		if ( $data['is_whitelisted'] ) return $data;
		if ( $data['is_spam'] ) return $data;

		$options = $data['options'];
		if ( intval( $options['check_dnsbl'] ) !== 1 || ! $data['remote_ip'] ) return $data;

		$score_dnsbl = floatval( $options['score']['_dnsbl'] );
		$reverse_ip = '';

		if ( filter_var( $data['remote_ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$reverse_ip = $this->cf7a_reverse_ipv4( $data['remote_ip'] );
		} elseif ( filter_var( $data['remote_ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$reverse_ip = $this->cf7a_reverse_ipv6( $data['remote_ip'] );
		}

		foreach ( $options['dnsbl_list'] as $dnsbl ) {
			if ( $this->cf7a_check_dnsbl( $reverse_ip, $dnsbl ) ) {
				$data['reasons']['dnsbl'][] = $dnsbl;
				$data['spam_score'] += $score_dnsbl;
			}
		}

		if ( isset( $data['reasons']['dnsbl'] ) && is_array( $data['reasons']['dnsbl'] ) ) {
			$data['reasons']['dnsbl'] = implode( ', ', $data['reasons']['dnsbl'] );
			cf7a_log( "{$data['remote_ip']} is listed in DNSBL ({$data['reasons']['dnsbl']})", 1 );
		}
		return $data;
	}

	/**
	 * Checks visible honeypot fields.
	 */
	public function filter_honeypot( $data ) {
		if ( $data['is_whitelisted'] ) return $data;
		if ( $data['is_spam'] ) return $data;

		$options = $data['options'];
		if ( ! $options['check_honeypot'] ) return $data;

		$mail_tag_text = array();
		foreach ( $data['mail_tags'] as $mail_tag ) {
			if ( 'text' === $mail_tag['type'] || 'text*' === $mail_tag['type'] ) {
				$mail_tag_text[] = $mail_tag['name'];
			}
		}

		if ( ! empty( $mail_tag_text ) ) {
			$input_names = cf7a_get_honeypot_input_names( $options['honeypot_input_names'] );
			$mail_tag_count = count( $input_names );
			$score_honeypot = floatval( $options['score']['_honeypot'] );

			for ( $i = 0; $i < $mail_tag_count; $i++ ) {
				$has_honeypot = ! empty( $_POST[ $input_names[ $i ] ] );
				if ( $has_honeypot ) {
					$data['spam_score'] += $score_honeypot;
					$data['reasons']['honeypot'][] = $input_names[ $i ];
				}
			}

			if ( ! empty( $data['reasons']['honeypot'] ) && is_array($data['reasons']['honeypot']) ) {
				$data['reasons']['honeypot'] = implode( ', ', $data['reasons']['honeypot'] );
				cf7a_log( "The {$data['remote_ip']} has filled the input honeypot(s) {$data['reasons']['honeypot']}", 1 );
			}
		}
		return $data;
	}

	/**
	 * Checks B8 Bayesian Filter.
	 * Now hooks into 'cf7a_check_b8'.
	 */
	public function filter_b8_bayesian( $data ) {
		// Even if requested "at the end", we usually skip B8 if the user is explicitly Whitelisted.
		if ( $data['is_whitelisted'] ) return $data;

		$options = $data['options'];
		$text = stripslashes( $data['message'] );
		\assert( \is_string( $text ) );

		// log the result of the pre-checks
		if ($data['is_spam']) {
			cf7a_log( "Submission failed for {$data['remote_ip']} spam detected with score {$data['spam_score']} - message: {$data['message']}", 1 );
			cf7a_log( "log enabled " . CF7ANTISPAM_DEBUG_EXTENDED . "standard log enabled " . CF7ANTISPAM_DEBUG, 1 );
		}

		// Ensure B8 is enabled and there is a message to check
		if ( $options['enable_b8'] && $data['message'] ) {
			$b8_threshold = floatval( $options['b8_threshold'] );
			$b8_threshold = $b8_threshold > 0 && $b8_threshold < 1 ? $b8_threshold : 1;
			$score_detection = floatval( $options['score']['_detection'] );

			// Store the spam score before B8
			$was_spam_before_b8 = $data['spam_score'] >= 1;

			$cf7a_b8 = new CF7_AntiSpam_B8();
			$rating  = round( $cf7a_b8->cf7a_b8_classify( $text ), 2 );


			// If the rating is high, add to spam score
			if ( $rating >= $b8_threshold ) {
				$data['reasons']['b8'] = $rating;
				$data['spam_score'] += $score_detection;
				$data['is_spam'] = true;
				cf7a_log( "B8 rating $rating / 1", 1 );
			}

			// LEARNING LOGIC:
			// Use the accumulated spam_score from previous filters to decide how to teach B8.
			if ( $was_spam_before_b8 ) {
				// Only learn spam if OTHER filters flagged it (not B8 itself)
				cf7a_log( "{$data['remote_ip']} detected as spam by filters (score {$data['spam_score']}), learning as SPAM.", 1 );
				$cf7a_b8->cf7a_b8_learn_spam( $text );
			} elseif ( $rating < $b8_threshold * 0.5 && $data['spam_score'] == 0 ) {
				// Only learn as ham if COMPLETELY clean (no warnings at all)
				cf7a_log( "B8 detected spamminess of $rating (below threshold) and no filter warnings, learning as HAM.", 1 );
				$cf7a_b8->cf7a_b8_learn_ham( $text );
			}
		}
		return $data;
	}

	/**
	 * Sends an email to the admin, warning them to clear the cache.
	 * @param array $update_data the array of data to be sent to the admin
	 * @return void
	 */
	private function send_cache_warning_email( $update_data ): void {
		$tools = new CF7_AntiSpam_Admin_Tools();
		$recipient = get_option( 'admin_email' );
		$body = sprintf(
			"Hello Admin,\n\nWe detected 5 users trying to submit forms with the old version (%s) instead of the new one (%s).\n\nThis usually means your website cache (or CDN) hasn't been cleared after the last update.\n\nPlease purge your site cache immediately to prevent legitimate users from being flagged as spam.\n\nTime of update: %s",
			$update_data['old_version'],
			$update_data['new_version'],
			gmdate( 'Y-m-d H:i:s', $update_data['time'] )
		);
		$subject = 'CF7 AntiSpam - Cache Warning Alert';

		$tools->send_email_to_admin( $subject, $recipient, $body, $recipient );
	}

	/**
	 * Search for the message field in the mail tags.
	 * @param array $mail_tags the array of mail tags
	 * @return string the name of the message field or false if not found
	 */
	private function search_for_message_field( array $mail_tags ) {
		foreach ($mail_tags as $tag) {
			// if we are lucky and the message tag wasn't changed by the user
			if ($tag->name == 'message' || $tag->name == 'your-message' ) {
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
	private function create_message_from_posted_data( ?array $posted_data ): string {
		if (empty($posted_data)) {
			return '';
		}
		/**
		 * Filters the minimum field length for the auto message.
		 * @param int $minimum_field_length the minimum field length
		 * @return int the minimum field length
		 */
		$minimum_field_length = apply_filters('cf7a_auto_message_minimum_field_length', 20);
		$message = '';

		/**
		 * Loops through the posted data and creates a message from it removing:
		 * - the fields that are too short
		 * - the fields that match an email address.
		 * - the fields that match a phone number.
		 *
		 * @param array $posted_data the array of posted data
		 * @return string the message created from the posted data
		 */
		foreach ($posted_data as $key => $value) {
			// is email?
			if (is_email($value)) {
				continue;
			}
			// is phone?
			if ($this->is_phone($value)) {
				continue;
			}
			// is too short?
			if (strlen($value) >= $minimum_field_length) {
				$message .= $value . "\n";
			}
		}
		return $message;
	}

	/**
	 * Checks if the value is a phone number.
	 *
	 * @param string $value the value to check
	 *
	 * @return bool true if the value is a phone number, false otherwise
	 */
	private function is_phone( string $value ): bool {
		return preg_match('/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/', $value);
	}

	/**
	 * Gets the message from the contact form.
	 *
	 * @param string $contact_form the contact form object
	 * @param array $posted_data the array of posted data
	 * @param array $mail_tags the array of mail tags
	 *
	 * @return string the message
	 */
	private function get_email_message( $message_tag, array $posted_data, array $mail_tags ): string {
		/* Getting the message field(s) */
		if ( ! empty( $message_tag ) ) {
			$message_meta = cf7a_get_mail_meta( $message_tag );
			return cf7a_maybe_split_mail_meta( $posted_data, $message_meta );
		}

		// fallback and search for the message field
		$found_tag = $this->search_for_message_field( $mail_tags );
		if ( $found_tag ) {
			return cf7a_maybe_split_mail_meta( $posted_data, $found_tag );
		}

		// in this case we will create a message from the posted data removing the "short" fields (because may contain sensitive data e.g. emails, phone numbers, etc.)
		return $this->create_message_from_posted_data( $posted_data );
	}
}
