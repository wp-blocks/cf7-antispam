<?php

class CF7_AntiSpam_Filters {

	/**
	 * CF7_AntiSpam_Filters constructor.
	 */
	public function __construct() {

	}

	/* CF7_AntiSpam_Filters Tools */

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


	/* CF7_AntiSpam_Filters blacklists */

	/**
	 * It takes an IP address as a parameter, validates it, and then returns the row from the database that matches that IP
	 * address
	 *
	 * @param string $ip - The IP address to check.
	 *
	 * @return array|false|object|stdClass|null - the row from the database that matches the IP address.
	 */
	public function cf7a_blacklist_get_ip( $ip ) {
		$ip = filter_var( $ip, FILTER_VALIDATE_IP );
		if ( $ip ) {
			global $wpdb;
			$r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}cf7a_blacklist WHERE ip = %s", $ip ) );
			if ( $r ) {
				return $r;
			}
		}
		return false;
	}

	/**
	 * It gets the row from the database where the id is equal to the id passed to the function
	 *
	 * @param int $id The ID of the blacklist item.
	 *
	 * @return object|false the row from the database that matches the id.
	 */
	public function cf7a_blacklist_get_id( $id ) {
		if ( is_int( $id ) ) {
			global $wpdb;
			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}cf7a_blacklist WHERE id = %s", $id ) );
		}
	}

	/**
	 * It adds an IP address to the blacklist.
	 *
	 * @param string $ip The IP address to ban.
	 * @param array  $reason The reason why the IP is being banned.
	 * @param float  $spam_score This is the number of points that will be added to the IP's spam score.
	 *
	 * @return bool true if the given id was banned
	 */
	public function cf7a_ban_by_ip( $ip, $reason = array(), $spam_score = 1 ) {

		$ip = filter_var( $ip, FILTER_VALIDATE_IP );

		if ( $ip ) {

			$ip_row = self::cf7a_blacklist_get_ip( $ip );

			global $wpdb;

			$r = $wpdb->replace(
				$wpdb->prefix . 'cf7a_blacklist',
				array(
					'ip'     => $ip,
					'status' => isset( $ip_row->status ) ? floatval( $ip_row->status ) + floatval( $spam_score ) : 1,
					'meta'   => serialize(
						array(
							'reason' => $reason,
							'meta'   => null,
						)
					),
				),
				array( '%s', '%d', '%s' )
			);

			if ( $r > -1 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * It deletes the IP address from the database
	 *
	 * @param string $ip The IP address to unban.
	 *
	 * @return int|false The number of rows deleted.
	 */
	public function cf7a_unban_by_ip( $ip ) {

		$ip = filter_var( $ip, FILTER_VALIDATE_IP );

		if ( $ip ) {

			global $wpdb;

			$r = $wpdb->delete(
				$wpdb->prefix . 'cf7a_blacklist',
				array(
					'ip' => $ip,
				),
				array(
					'%s',
				)
			);

			return ! is_wp_error( $r ) ? $r : $wpdb->last_error;
		}

		return false;
	}

	/**
	 * It deletes a row from the database table
	 *
	 * @param int $id The ID of the entry to delete.
	 *
	 * @return int The number of rows affected by the query.
	 */
	public function cf7a_unban_by_id( $id ) {

		$id = intval( $id );

		global $wpdb;

		$r = $wpdb->delete(
			$wpdb->prefix . 'cf7a_blacklist',
			array(
				'id' => $id,
			),
			array(
				'%d',
			)
		);

		return ! is_wp_error( $r ) ? $r : $wpdb->last_error;

	}

	/**
	 * It updates the status of all the users in the blacklist table by subtracting 1 from the status column.
	 *
	 * Then it deletes all the users whose status is 0.
	 * The status column is the number of days the user is banned for.
	 * So if the user is banned for 3 days, the status column will be 3. After the first day, the status column will be 2. After the second day, the status column will be 1. After the third day, the status column will be 0.
	 * When the status column is 0, the user is unbanned.
	 *
	 * The function returns true if the user is unbanned.
	 *
	 * @return true.
	 */
	public function cf7a_cron_unban() {
		global $wpdb;
		$rows_updated = $wpdb->query( "UPDATE {$wpdb->prefix}cf7a_blacklist SET `status` = `status` - 1 WHERE 1" );
		$unbanned     = $wpdb->query( "DELETE FROM {$wpdb->prefix}cf7a_blacklist WHERE `status` =  0" );
		cf7a_log( "Unbanned $unbanned users (rows updated $rows_updated)" );
		return true;
	}


	/**
	 * If the language is not allowed, return the language.
	 * TODO: actually this function is case-sensitive, but maybe this is not wanted
	 *
	 * @param array $languages The languages to check.
	 * @param array $disalloweds An array of languages that are not allowed.
	 * @param array $alloweds An array of allowed languages. If the user's language is in this array, the form will be shown.
	 */
	public function cf7a_check_language_allowed( $languages, $disalloweds = array(), $alloweds = array() ) {

		if ( ! is_array( $languages ) ) {
			$languages = array( $languages );
		}

		if ( ! empty( $alloweds ) ) {
			foreach ( $alloweds as $allowed ) {
				if ( in_array( $allowed, $languages, true ) ) {
					return true;
				}
			}
		}

		if ( ! empty( $disalloweds ) ) {
			foreach ( $disalloweds as $disallowed ) {
				if ( in_array( $disallowed, $languages, true ) ) {
					return false;
				}
			}
		}

		return true;
	}


	/**
	 * CF7_AntiSpam_Filters The antispam filter
	 *
	 * @param boolean $spam - spam or not.
	 *
	 * @return boolean
	 */
	public function cf7a_spam_filter( $spam ) {

		/* Get the submitted data */
		$submission = WPCF7_Submission::get_instance();

		if ( ! $submission ) {
			return true;
		}

		/* Get the contact form additional data */
		$posted_data  = $submission->get_posted_data();
		$contact_form = $submission->get_contact_form();

		/* get the tag used in the form */
		$mail_tags = $contact_form->scan_form_tags();

		/* the email and the message from the email */
		$email_tag   = substr( $contact_form->pref( 'flamingo_email' ), 2, -2 );
		$message_tag = substr( $contact_form->pref( 'flamingo_message' ), 2, -2 );

		$email   = isset( $posted_data[ $email_tag ] ) ? $posted_data[ $email_tag ] : false;
		$message = isset( $posted_data[ $message_tag ] ) ? $posted_data[ $message_tag ] : false;

		/**
		 * Let developers hack the message
		 *
		 * @param string  $message  the mail message content
		 * @param array $posted_data the email metadata
		 */
		$message = apply_filters( 'cf7a_message_before_processing', $message, $posted_data );

		/* this plugin options */
		$options = get_option( 'cf7a_options', array() );
		$prefix  = sanitize_html_class( $options['cf7a_customizations_prefix'] );

		/**
		 * The data of the user who sent this email
		 */

		/* IP */
		$real_remote_ip = isset( $_POST[ $prefix . 'address' ] ) ? cf7a_decrypt( sanitize_text_field( $_POST[ $prefix . 'address' ] ), $options['cf7a_cipher'] ) : false;
		$remote_ip      = $real_remote_ip ? filter_var( $real_remote_ip, FILTER_VALIDATE_IP ) : false;
		$cf7_remote_ip  = filter_var( $submission->get_meta( 'remote_ip' ), FILTER_VALIDATE_IP );

		/* CF7A version */
		$cf7a_version = isset( $_POST[ $prefix . 'version' ] ) ? cf7a_decrypt( sanitize_text_field( $_POST[ $prefix . 'version' ] ), $options['cf7a_cipher'] ) : false;

		/* client referer */
		$cf7a_referer = isset( $_POST[ $prefix . 'referer' ] ) ? cf7a_decrypt( sanitize_text_field( $_POST[ $prefix . 'referer' ] ), $options['cf7a_cipher'] ) : false;

		/* CF7 user agent */
		$user_agent = sanitize_text_field( $submission->get_meta( 'user_agent' ) );

		/* Timestamp checks */
		$timestamp = isset( $_POST[ $prefix . '_timestamp' ] ) ? intval( cf7a_decrypt( sanitize_text_field( $_POST[ $prefix . '_timestamp' ] ), $options['cf7a_cipher'] ) ) : 0;

		/* Can be cached so isn't safe to use -> $submission->get_meta( 'timestamp' ); */
		$timestamp_submitted             = time();
		$submission_minimum_time_elapsed = intval( $options['check_time_min'] );
		$submission_maximum_time_elapsed = intval( $options['check_time_max'] );

		/* Checks sender has a blacklisted ip address */
		$bad_ip_list = isset( $options['bad_ip_list'] ) ? $options['bad_ip_list'] : array();

		/* Checks if the mail contains bad words */
		$bad_words = isset( $options['bad_words_list'] ) ? $options['bad_words_list'] : array();

		/* Checks if the mail contains bad user agent */
		$bad_user_agent_list = isset( $options['bad_user_agent_list'] ) ? $options['bad_user_agent_list'] : array();

		/* Check sender mail has prohibited string */
		$bad_email_strings = isset( $options['bad_email_strings_list'] ) ? $options['bad_email_strings_list'] : array();

		/**
		 * Scoring
		 */

		/* b8 threshold */
		$b8_threshold = floatval( $options['b8_threshold'] );
		$b8_threshold = $b8_threshold > 0 && $b8_threshold < 1 ? $b8_threshold : 1;

		/* cf7-antispam version check, fingerprinting, fingerprints extras (for each failed test) */
		$score_fingerprinting = floatval( $options['score']['_fingerprinting'] );

		/* time lower or higher than the limits entered */
		$score_time = floatval( $options['score']['_time'] );

		/* blacklisted ip (with bad ip list), bad string in email or in message fields, bad user agent */
		$score_bad_string = floatval( $options['score']['_bad_string'] );

		/* dsnbl score (for each server found) */
		$score_dnsbl = floatval( $options['score']['_dnsbl'] );

		/* honeypot */
		$score_honeypot = floatval( $options['score']['_honeypot'] );

		/* no http refer, language check fail */
		$score_warn = floatval( $options['score']['_warn'] );

		/* already blacklisted, language check fail, ip or user agent or timestamp fields missing */
		$score_detection = floatval( $options['score']['_detection'] );

		/* initialize the spam data collection */
		$reason     = array();
		$spam_score = 0;

		/**
		 * Checking if the IP address is empty. If it is empty, it will add a score of 10 to the spam score and add a reason to the reason array.
		*/
		if ( ! $remote_ip ) {

			$remote_ip = $cf7_remote_ip ? $cf7_remote_ip : null;

			$spam_score     += $score_detection;
			$reason['no_ip'] = 'Address field empty';

			cf7a_log( "ip address field of $remote_ip is empty, this means it has been modified, removed or hacked! (i'm getting the real ip from http header)", 1 );
		}

		/**
		 * Checks if the emails IP is filtered by user
		 */
		if ( intval( $options['check_bad_ip'] ) === 1 ) {

			foreach ( $bad_ip_list as $bad_ip ) {

				$bad_ip = filter_var( $bad_ip, FILTER_VALIDATE_IP );

				if ( false !== stripos( (string) $remote_ip, (string) $bad_ip ) ) {

					$spam_score        += $score_bad_string;
					$reason['bad_ip'][] = $bad_ip;

				}
			}

			if ( ! empty( $reason['bad_ip'] ) ) {
				$reason['bad_ip'] = implode( ', ', $reason['bad_ip'] );

				if ( CF7ANTISPAM_DEBUG ) {
					cf7a_log( "The ip address $remote_ip is listed into bad ip list (contains {$reason['bad_ip']})" );
				}
			}
		}

		/**
		 * Checking if the IP address was already blacklisted - no mercy 😎
		 */
		if ( $remote_ip && $options['max_attempts'] ) {

			$ip_data        = self::cf7a_blacklist_get_ip( $remote_ip );
			$ip_data_status = isset( $ip_data->status ) ? intval( $ip_data->status ) : 0;

			if ( $ip_data_status >= $options['max_attempts'] ) {

				$spam                  = true;
				$spam_score           = $ip_data_status + 1;
				$reason['blacklisted'] = "Score: $spam_score";

				cf7a_log( "The $remote_ip is already blacklisted, status $ip_data_status", 1 );
			} elseif ( $ip_data_status > 0 ) {
				cf7a_log(
					sprintf(
						"The $remote_ip is already blacklisted (score $ip_data_status) but still has %d attempts left",
						$options['max_attempts'] - $ip_data_status
					),
					1
				);
			}
		}

		/**
		 * Checking if the honeypot field is empty. If it is not empty, then it is a bot.
		 */
		if ( intval( $options['check_honeyform'] ) !== 0 ) {

			$form_class = sanitize_html_class( $options['cf7a_customizations_class'] );

			/* get the "marker" field */
			if ( ! empty( $_POST[ '_wpcf7_' . $form_class ] ) ) {
				$spam_score               += $score_warn;
				$reason['bot_fingerprint'] = 'honeyform';
			}
		}

		/**
		 * If the mail was marked as spam no more checks are needed.
		 * This will save server computing power, this ip has already been banned so there's no reason for further processing
		 */
		if ( $spam_score < 1 && ! $spam ) {

			/**
			 * Check the client http refer
			 * it is much more likely that it is a bot that lands on the page without a referrer than a human that pastes in the address bar the url of the contact form.
			 */
			if ( intval( $options['check_refer'] ) === 1 ) {
				if ( ! $cf7a_referer ) {

					$spam_score           += $score_warn;
					$reason['no_referrer'] = 'client has referrer address';

					cf7a_log( "the $remote_ip has reached the contact form page without any referrer", 1 );
				}
			}

			/**
			 * Check the CF7 AntiSpam version field
			 */
			if ( ! $cf7a_version || CF7ANTISPAM_VERSION !== $cf7a_version ) {

				$spam_score             += $score_fingerprinting;
				$reason['data_mismatch'] = "Version mismatch '$cf7a_version' != '" . CF7ANTISPAM_VERSION . "'";

				cf7a_log( "Incorrect data submitted by $remote_ip in the hidden field _version, may have been modified, removed or hacked", 1 );
			}

			/**
			 * If enabled fingerprints bots
			 */
			if ( intval( $options['check_bot_fingerprint'] ) === 1 ) {
				$bot_fingerprint = array(
					'timezone'             => ! empty( $_POST[ $prefix . 'timezone' ] ) ? sanitize_text_field( $_POST[ $prefix . 'timezone' ] ) : null,
					'platform'             => ! empty( $_POST[ $prefix . 'platform' ] ) ? sanitize_text_field( $_POST[ $prefix . 'platform' ] ) : null,
					'screens'              => ! empty( $_POST[ $prefix . 'screens' ] ) ? sanitize_text_field( $_POST[ $prefix . 'screens' ] ) : null,
					'hardware_concurrency' => ! empty( $_POST[ $prefix . 'hardware_concurrency' ] ) ? intval( $_POST[ $prefix . 'hardware_concurrency' ] ) : null,
					'memory'               => ! empty( $_POST[ $prefix . 'memory' ] ) ? floatval( $_POST[ $prefix . 'memory' ] ) : null,
					'user_agent'           => ! empty( $_POST[ $prefix . 'user_agent' ] ) ? sanitize_text_field( $_POST[ $prefix . 'user_agent' ] ) : null,
					'app_version'          => ! empty( $_POST[ $prefix . 'app_version' ] ) ? sanitize_text_field( $_POST[ $prefix . 'app_version' ] ) : null,
					'webdriver'            => ! empty( $_POST[ $prefix . 'webdriver' ] ) ? sanitize_text_field( $_POST[ $prefix . 'webdriver' ] ) : null,
					'session_storage'      => ! empty( $_POST[ $prefix . 'session_storage' ] ) ? intval( $_POST[ $prefix . 'session_storage' ] ) : null,
					'bot_fingerprint'      => ! empty( $_POST[ $prefix . 'bot_fingerprint' ] ) ? sanitize_text_field( $_POST[ $prefix . 'bot_fingerprint' ] ) : null,
					'touch'                => ! empty( $_POST[ $prefix . 'touch' ] ) ? true : null,
				);

				$fails = array();
				if ( ! $bot_fingerprint['timezone'] ) {
					$fails[] = 'timezone';
				}
				if ( ! $bot_fingerprint['platform'] ) {
					$fails[] = 'platform';
				}
				if ( ! $bot_fingerprint['screens'] ) {
					$fails[] = 'screens';
				}
				if ( ! $bot_fingerprint['user_agent'] ) {
					$fails[] = 'user_agent';
				}
				if ( ! $bot_fingerprint['app_version'] ) {
					$fails[] = 'app_version';
				}
				if ( ! $bot_fingerprint['webdriver'] ) {
					$fails[] = 'webdriver';
				}
				if ( ! $bot_fingerprint['session_storage'] ) {
					$fails[] = 'session_storage';
				}
				if ( 5 !== strlen( $bot_fingerprint['bot_fingerprint'] ) ) {
					$fails[] = 'bot_fingerprint';
				}

				/* navigator hardware_concurrency isn't available under Ios - https://developer.mozilla.org/en-US/docs/Web/API/Navigator/hardwareConcurrency */
				if ( empty( $_POST[ $prefix . 'isIos' ] ) ) {
					/* hardware concurrency need to be an integer > 1 to be valid */
					if ( ! $bot_fingerprint['hardware_concurrency'] >= 1 ) {
						$fails[] = 'hardware_concurrency';
					}
				} else {
					/* but in ios isn't provided, so we expect a null value */
					if ( null !== $bot_fingerprint['hardware_concurrency'] ) {
						$fails[] = 'hardware_concurrency_Ios';
					}
				}

				if ( ! empty( $_POST[ $prefix . 'isIos' ] ) || ! empty( $_POST[ $prefix . 'isAndroid' ] ) ) {
					if ( ! $bot_fingerprint['touch'] ) {
						$fails[] = 'touch';
					}
				}

				/* navigator deviceMemory isn't available with Ios and firefox - https://developer.mozilla.org/en-US/docs/Web/API/Navigator/deviceMemory */
				if ( empty( $_POST[ $prefix . 'isIos' ] ) && empty( $_POST[ $prefix . 'isFFox' ] ) ) {
					/* memory need to be a float > 0.25 to be valid */
					if ( ! $bot_fingerprint['memory'] >= 0.25 ) {
						$fails[] = 'memory';
					}
				} else {
					/* but in ios and firefox isn't provided, so we expect a null value */
					if ( null !== $bot_fingerprint['memory'] ) {
						$fails[] = 'memory_Ios';
					}
				}

				/* increment the spam score if needed, then log the result */
				if ( ! empty( $fails ) ) {
					$spam_score               += count( $fails ) * $score_fingerprinting;
					$reason['bot_fingerprint'] = implode( ', ', $fails );

					cf7a_log( "The $remote_ip ip hasn't passed " . count( $fails ) . ' / ' . count( $bot_fingerprint ) . " of the bot fingerprint test ({$reason['bot_fingerprint']})", 1 );
					cf7a_log( $bot_fingerprint, 2 );
				}
			}

			/**
			 * Bot fingerprints extras
			 */
			if ( intval( $options['check_bot_fingerprint_extras'] ) === 1 ) {

				$bot_fingerprint_extras = array(
					'activity'               => ! empty( $_POST[ $prefix . 'activity' ] ) ? intval( $_POST[ $prefix . 'activity' ] ) : 0,
					'mouseclick_activity'    => ! empty( $_POST[ $prefix . 'mouseclick_activity' ] ) && sanitize_text_field( $_POST[ $prefix . 'mouseclick_activity' ] ) === 'passed' ? 'passed' : 0,
					'mousemove_activity'     => ! empty( $_POST[ $prefix . 'mousemove_activity' ] ) && sanitize_text_field( $_POST[ $prefix . 'mousemove_activity' ] ) === 'passed' ? 'passed' : 0,
					'webgl'                  => ! empty( $_POST[ $prefix . 'webgl' ] ) && sanitize_text_field( $_POST[ $prefix . 'webgl' ] ) === 'passed' ? 'passed' : 0,
					'webgl_render'           => ! empty( $_POST[ $prefix . 'webgl_render' ] ) && sanitize_text_field( $_POST[ $prefix . 'webgl_render' ] ) === 'passed' ? 'passed' : 0,
					'bot_fingerprint_extras' => ! empty( $_POST[ $prefix . 'bot_fingerprint_extras' ] ) ? sanitize_text_field( $_POST[ $prefix . 'bot_fingerprint_extras' ] ) : 0,
				);

				$fails = array();
				if ( $bot_fingerprint_extras['activity'] < 3 ) {
					$fails[] = "activity {$bot_fingerprint_extras["activity"]}";
				}
				if ( 'passed' !== $bot_fingerprint_extras['mouseclick_activity'] ) {
					$fails[] = 'mouseclick_activity';
				}
				if ( 'passed' !== $bot_fingerprint_extras['mousemove_activity'] ) {
					$fails[] = 'mousemove_activity';
				}
				if ( 'passed' !== $bot_fingerprint_extras['webgl'] ) {
					$fails[] = 'webgl';
				}
				if ( 'passed' !== $bot_fingerprint_extras['webgl_render'] ) {
					$fails[] = 'webgl_render';
				}
				if ( ! empty( $bot_fingerprint_extras['bot_fingerprint_extras'] ) ) {
					$fails[] = 'bot_fingerprint_extras';
				}

				if ( ! empty( $fails ) ) {

					$spam_score                      += count( $fails ) * $score_fingerprinting;
					$reason['bot_fingerprint_extras'] = implode( ', ', $fails );

					cf7a_log( "The $remote_ip ip hasn't passed " . count( $fails ) . ' / ' . count( $bot_fingerprint_extras ) . " of the bot fingerprint extra test ({$reason['bot_fingerprint_extras']})", 1 );
					cf7a_log( $bot_fingerprint_extras, 2 );

				}
			}

			/**
			 * Bot fingerprints extras
			 */
			if ( intval( $options['check_language'] ) === 1 ) {

				/* Checks sender has a blacklisted ip address */
				$languages_allowed    = isset( $options['languages']['allowed'] ) ? $options['languages']['allowed'] : array();
				$languages_disallowed = isset( $options['languages']['disallowed'] ) ? $options['languages']['disallowed'] : array();

				$languages                     = array();
				$languages['browser_language'] = ! empty( $_POST[ $prefix . 'browser_language' ] ) ? sanitize_text_field( $_POST[ $prefix . 'browser_language' ] ) : null;
				$languages['accept_language']  = isset( $_POST[ $prefix . '_language' ] ) ? cf7a_decrypt( sanitize_text_field( $_POST[ $prefix . '_language' ] ), $options['cf7a_cipher'] ) : null;

				/**
				 * Language checks
				 */
				if ( empty( $languages['browser_language'] ) ) {
					$spam_score                += $score_detection;
					$reason['browser_language'] = 'missing browser language';
				} else {
					$languages['browser'] = cf7a_get_browser_language_array( $languages['browser_language'] );
				}

				if ( empty( $languages['accept_language'] ) ) {
					$spam_score              += $score_detection;
					$reason['language_field'] = 'missing language field';
				} else {
					$languages['accept'] = cf7a_get_accept_language_array( $languages['accept_language'] );
				}

				if ( ! empty( $languages['accept'] ) && ! empty( $languages['browser'] ) ) {

					if ( ! array_intersect( $languages['browser'], $languages['accept'] ) ) {
						$spam_score += $score_detection;

						/* checks if http accept language is the same of javascript navigator.languages */
						$reason['language_incoherence'] = 'languages detected not coherent (' . implode( '-', $languages['browser'] ) . ' vs ' . implode( '-', $languages['accept'] ) . ')';
					}

					/* check if the language is allowed and if is disallowed */
					$client_languages = array_unique( array_merge( $languages['browser'], $languages['accept'] ) );

					$language_disallowed = $this->cf7a_check_language_allowed( $client_languages, $languages_disallowed, $languages_allowed );

					if ( false === $language_disallowed ) {
						$spam_score                += $score_detection;
						$reason['browser_language'] = implode( ', ', $client_languages );
					}
				}

				/**
				 * Geo-ip verification
				 */
				if ( 1 === intval( $options['check_geo_location'] ) ) {

					$geoip = new CF7_Antispam_Geoip();

					if ( ! empty( $geoip ) ) {

						try {
							/* check if the ip is available into geo-ip database, then create an array with county and continent */
							$geoip_data      = $geoip->cf7a_geoip_check_ip( $remote_ip );
							$geoip_continent = isset( $geoip_data['continent'] ) ? strtolower( $geoip_data['continent'] ) : false;
							$geoip_country   = isset( $geoip_data['country'] ) ? strtolower( $geoip_data['country'] ) : false;
							$geo_data        = array_filter( array( $geoip_continent, $geoip_country ) );

							if ( ! empty( $geo_data ) ) {
								/* then check if the detected country is among the allowed and disallowed languages */
								if ( false === $this->cf7a_check_language_allowed( $geo_data, $languages_disallowed, $languages_allowed ) ) {
									$reason['geo_ip'] = $geoip_continent . '-' . $geoip_country;
									$spam_score      += $score_warn;

									cf7a_log( "The $remote_ip is not allowed by geoip" . $reason['geo_ip'], 1 );
								}
							} else {
								$reason['no_geo_ip'] = 'unknown ip';
							}
						} catch ( Exception $e ) {
							cf7a_log( "unable to check geoip for $remote_ip - " . $e->getMessage(), 1 );
						}
					}
				}
			}

			/**
			 * Check if the time to submit the email il lower than expected
			 */
			if ( intval( $options['check_time'] ) === 1 ) {

				if ( ! $timestamp ) {

					$spam_score         += $score_detection;
					$reason['timestamp'] = 'undefined';

					cf7a_log( "The $remote_ip ip _timestamp field is missing, probable form hacking attempt from $remote_ip", 1 );

				} else {

					$time_now = $timestamp_submitted;

					$time_elapsed = $time_now - $timestamp;

					if ( $time_elapsed < $submission_minimum_time_elapsed ) {

						$spam_score                += $score_time;
						$reason['min_time_elapsed'] = $time_elapsed;

						cf7a_log( "The $remote_ip ip took too little time to fill in the form - elapsed $time_elapsed seconds < $submission_minimum_time_elapsed seconds expected", 1 );
					}

					/**
					 * Check if the time to submit the email il higher than expected
					 */
					if ( $time_elapsed > $submission_maximum_time_elapsed ) {

						$spam_score                += $score_time;
						$reason['max_time_elapsed'] = $time_elapsed;

						if ( CF7ANTISPAM_DEBUG ) {
							cf7a_log( "The $remote_ip ip took too much time to fill in the form - elapsed $time_elapsed seconds > $submission_maximum_time_elapsed seconds expected", 1 );
						}
					}
				}
			}

			/**
			 * Check if e-mails contain prohibited words, for instance, check if the sender is the same as the website domain,
			 * because it is an attempt to circumvent the controls, because the e-mail client cannot blacklist the e-mail itself,
			 * we must prevent this.
			 */
			if ( intval( $options['check_bad_email_strings'] ) === 1 && $email ) {

				foreach ( $bad_email_strings as $bad_email_string ) {

					if ( false !== stripos( strtolower( $email ), strtolower( $bad_email_string ) ) ) {

						$spam_score                    += $score_bad_string;
						$reason['email_blackilisted'][] = $bad_email_string;
					}
				}

				if ( isset( $reason['email_blackilisted'] ) ) {

					$reason['email_blackilisted'] = implode( ',', $reason['email_blackilisted'] );

					cf7a_log( "The ip address $remote_ip sent a mail using the email address {$email} that contains the bad string {$reason['email_blackilisted']}", 1 );
				}
			}

			/**
			 * Checks if the emails user agent is denied
			 */
			if ( intval( $options['check_bad_user_agent'] ) === 1 ) {

				if ( ! $user_agent ) {

					$spam_score          += $score_detection;
					$reason['user_agent'] = 'empty';

					cf7a_log( "The $remote_ip ip user agent is empty, look like a spambot", 1 );
				} else {

					foreach ( $bad_user_agent_list as $bad_user_agent ) {

						if ( false !== stripos( strtolower( $user_agent ), strtolower( $bad_user_agent ) ) ) {
							$spam_score          += $score_bad_string;
							$reason['user_agent'] = $bad_user_agent;
						}
					}

					if ( ! empty( $user_agent_found ) ) {
						$reason['user_agent'] = implode( ', ', $reason['user_agent'] );
						cf7a_log( "The $remote_ip ip user agent was listed into bad user agent list - $user_agent contains " . $reason['user_agent'], 1 );
					}
				}
			}

			/**
			 * Search for prohibited words
			 */
			if ( 1 === intval( $options['check_bad_words'] ) && '' !== $message ) {

				/* to search strings into message without space and case-insensitive */
				$message_compressed = str_replace( ' ', '', strtolower( $message ) );

				foreach ( $bad_words as $bad_word ) {
					if ( false !== stripos( $message_compressed, str_replace( ' ', '', strtolower( $bad_word ) ) ) ) {

						$spam_score          += $score_bad_string;
						$reason['bad_word'][] = $bad_word;
					}
				}

				if ( ! empty( $reason['bad_word'] ) ) {
					$reason['bad_word'] = implode( ',', $reason['bad_word'] );

					cf7a_log( "$remote_ip has bad word in message " . $reason['bad_word'], 1 );
				}
			}

			/**
			 * Check the remote ip if is listed into Domain Name System Blacklists
			 * DNS blacklist are spam blocking DNS like lists that allow to block messages from specific systems that have a history of sending spam
			 * inspiration taken from https://gist.github.com/tbreuss/74da96ff5f976ce770e6628badbd7dfc
			 */
			if ( intval( $options['check_dnsbl'] ) === 1 && $remote_ip ) {

				$reverse_ip = '';

				if ( filter_var( $remote_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {

					$reverse_ip = $this->cf7a_reverse_ipv4( $remote_ip );

				} elseif ( filter_var( $remote_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {

					$reverse_ip = $this->cf7a_reverse_ipv6( $remote_ip );
				}

				foreach ( $options['dnsbl_list'] as $dnsbl ) {
					if ( $this->cf7a_check_dnsbl( $reverse_ip, $dnsbl ) ) {
						$reason['dsnbl'][] = $dnsbl;
						$spam_score       += $score_dnsbl;
					}
				}

				if ( isset( $reason['dsnbl'] ) ) {

					$dsnbl_count     = count( $reason['dsnbl'] );
					$reason['dsnbl'] = implode( ', ', $reason['dsnbl'] );

					cf7a_log( "The $remote_ip has tried to send an email but is listed $dsnbl_count times in the Domain Name System Blacklists ({$reason['dsnbl']})", 1 );

				}
			}

			/**
			 * Checks Honeypots input if they are filled
			 */
			if ( $options['check_honeypot'] ) {

				/* we need only the text tags of the form */
				foreach ( $mail_tags as $mail_tag ) {
					if ( 'text' === $mail_tag['type'] || 'text*' === $mail_tag['type'] ) {
						$mail_tag_text[] = $mail_tag['name'];
					}
				}

				if ( isset( $mail_tag_text ) ) {

					/* faked input name used into honeypots */
					$input_names = get_honeypot_input_names( $options['honeypot_input_names'] );

					$mail_tag_count = count( $input_names );

					for ( $i = 0; $i < $mail_tag_count; $i ++ ) {

						$has_honeypot = ! empty( $_POST[ $input_names[ $i ] ] );

						/* check only if it's set and if it is different from "" */
						if ( $has_honeypot ) {
							$spam_score          += $score_honeypot;
							$reason['honeypot'][] = $input_names[ $i ];
						}
					}

					if ( ! empty( $reason['honeypot'] ) ) {
						$reason['honeypot'] = implode( ', ', $reason['honeypot'] );

						cf7a_log( "The $remote_ip has filled the input honeypot(s) {$reason['honeypot']}", 1 );
					}
				}
			}
		}

		/**
		 * Filter before Bayesian filter B8
		 *
		 * @param bool $spam true if the mail was detected as spam
		 * @param array  $message  the mail message content
		 * @param null|WPCF7_Submission  $submission  the mail message submission instance
		 */
		$spam = apply_filters( 'cf7a_before_b8', $spam, $message, $submission );

		/**
		 * B8 is a statistical "Bayesian" spam filter
		 * https://nasauber.de/opensource/b8/
		 */

		$text = stripslashes( $message );

		if ( $options['enable_b8'] && $message && ! isset( $reason['blacklisted'] ) ) {

			$cf7a_b8 = new CF7_AntiSpam_B8();
			$rating  = $cf7a_b8->cf7a_b8_classify( $text );

			/* Checking the rating of the message and if it is greater than the threshold */
			if ( $rating >= $b8_threshold ) {

				$reason['b8'] = $rating;
				$spam_score  += $score_detection;

				cf7a_log( "$remote_ip will be rejected because suspected of spam! (score $spam_score / 1 - B8 rating $rating / 1)" );
			}

			/* Checking if the spam score is greater than or equal to 1. If it is, it sets the spam variable to true. */
			if ( $spam_score >= 1 ) {
				/* if d8 isn't enabled we only need to mark as spam and leave a log */
				cf7a_log( "$remote_ip will be rejected because suspected of spam! (score $spam_score / 1)", 1 );
				$cf7a_b8->cf7a_b8_learn_spam( $text );
			} else {
				/* the mail was classified as ham, so we let learn to d8 what is considered (a probable) ham */
				cf7a_log( "D8 detect spamminess of $rating (below the half of the threshold of $b8_threshold) so the mail from $remote_ip will be marked as ham", 1 );
				$cf7a_b8->cf7a_b8_learn_ham( $text );
			}
		}

		/**
		 * Filter with the antispam results (before ban).
		 *
		 * @param boolean $spam true if the mail was detected as spam
		 * @param string  $message  the mail message content
		 * @param null|WPCF7_Submission  $submission  the mail message submission instance
		 */
		$spam = apply_filters( 'cf7a_additional_spam_filters', $spam, $message, $submission );

		/* if the spam score is lower than 1 the mail is ham so return the value as this is a filter */
		if ( $spam_score < 1 ) {
			return $spam;
		}

		/* ...otherwise the mail is spam, taking the array $reason and compressing it into a string. */
		$reasons_for_ban = cf7a_compress_array( $reason );

		/* If the auto-store ip is enabled (and NOT in extended debug mode) */
		if ( $options['autostore_bad_ip'] ) {
			if ( self::cf7a_ban_by_ip( $remote_ip, $reason, round( $spam_score ) ) ) {
				/* Log the antispam result in extended debug mode */
				cf7a_log( "Ban for $remote_ip - results - " . $reasons_for_ban, 2 );
			} else {
				cf7a_log( "Unable to ban $remote_ip" );
			}
		}

		/* Store the ban reason into mail post metadata */
		$submission->add_spam_log(
			array(
				'agent'  => 'CF7-AntiSpam',
				'reason' => $reasons_for_ban,
			)
		);

		/* case closed */
		return true;
	}

}
