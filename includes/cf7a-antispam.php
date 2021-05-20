<?php

class CF7_AntiSpam_filters {

	protected $b8;

	public function __construct() {
		$this->b8 = $this->cf7a_b8_init();
	}

	// expand IPv6 address
	public function cf7a_expand_ipv6( $ip ) {
		$hex = unpack( "H*hex", inet_pton( $ip ) );
		return substr( preg_replace( "/([A-f0-9]{4})/", "$1:", $hex['hex'] ), 0, - 1 );
	}

	public function cf7a_reverse_ipv4( $ip ) {
		return implode( ".", array_reverse( explode( ".", $ip ) ) );
	}

	public function cf7a_reverse_ipv6( $ip ) {
		$ip = $this->cf7a_expand_ipv6( $ip );
		// remove ":" and reverse the string then
		// add a dot for each digit
		return implode( '.', str_split( strrev( str_replace( ":", "", $ip ) ) ) );
	}

	public function cf7a_check_dnsbl( $reverse_ip, $dnsbl ) {

		if ( checkdnsrr( $reverse_ip . "." . $dnsbl . ".", "A" ) ) {
			return $dnsbl;
		}

		return false;
	}

	private function cf7a_b8_init() {
		// the database
		global $wpdb;

		// B8 config
		$mysql = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );

		$config_b8      = array( 'storage' => 'mysql' );
		$config_storage = array(
			'resource' => $mysql,
			'table'    => $wpdb->prefix . 'cf7a_wordlist'
		);

		// We use the default lexer settings
		$config_lexer = array();

		// We use the default degenerator configuration
		$config_degenerator = array();

		// Include the b8 code
		require_once CF7ANTISPAM_PLUGIN_DIR . '/vendor/b8/b8.php';

		# Create a new b8 instance
		try {
			return new b8\b8( $config_b8, $config_storage, $config_lexer, $config_degenerator );
		} catch ( Exception $e ) {
			error_log( 'CF7 Antispam error message: ' . $e->getMessage() );
			exit();
		}
	}

	public function cf7a_b8_classify($message, $verbose = false) {
		$time_elapsed = cf7a_microtimeFloat();

		$rating = $this->b8->classify( $message );

		if ( $verbose || CF7ANTISPAM_DEBUG ) {
			error_log( 'CF7 Antispam - Classification: ' . $rating );

			$mem_used      = round( memory_get_usage() / 1048576, 5 );
			$peak_mem_used = round( memory_get_peak_usage() / 1048576, 5 );
			$time_taken    = round( cf7a_microtimeFloat() - $time_elapsed, 5 );

			error_log( "CF7 Antispam stats : Memory: $mem_used - Peak memory: $peak_mem_used - Time Elapsed: $time_taken" );
		}

		return $rating;
	}

	public function cf7a_b8_learn_spam($message) {
		$this->b8->learn( $message, b8\b8::SPAM );
	}

	public function cf7a_b8_unlearn_spam($message) {
		$this->b8->unlearn( $message, b8\b8::SPAM );
	}

	public function cf7a_b8_learn_ham($message) {
		$this->b8->learn( $message, b8\b8::HAM );
	}

	public function cf7a_b8_unlearn_ham($message) {
		$this->b8->unlearn( $message, b8\b8::HAM );
	}

	public function cf7a_blacklist_get_ip($ip) {
		if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) return false;

		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}cf7a_blacklist WHERE ip = %s", $ip ) );

	}

	public function cf7a_ban_ip($ip, $reason = "", $spam_score = 1) {

		if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) return false;

		$ip_row = self::cf7a_blacklist_get_ip($ip);

		global $wpdb;

		$r = $wpdb->replace(
			$wpdb->prefix . "cf7a_blacklist",
			array(
				'ip' => $ip,
				'status' => isset($ip_row->status) ? intval($ip_row->status) + intval($spam_score) : 1,
				'reason' => is_array($reason) ? compress_reasons_array($reason) : $reason,
			),
			array(
				'%s',
				'%d',
				'%s'
			)

		);

		return $r ? true : error_log(printf(__("AntiSpam for Contact Form 7 - unable to blacklist %s", "cf7-antispam" ), $ip ));
	}

	public function cf7a_unban_ip($ip, $status_remove = 1) {

		if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) return false;
		$status_remove = intval($status_remove);

		// get ip data (if any)
		$ip_row = self::cf7a_blacklist_get_ip($ip);

		// calc the new rating
		$new_status = isset($ip_row->status) ? $ip_row->status - $status_remove : 0;

		global $wpdb;
		$r = $wpdb->replace(
			$wpdb->prefix . "cf7a_blacklist",
			array(
				'ip' => $ip,
				'status' => $new_status < 0 ? 0 : $new_status
			),
			array(
				'%s',
				'%d'
			)
		);

		return $r ? true : error_log(printf(__("AntiSpam for Contact Form 7 - unable to unban %s", "cf7-antispam" ), $ip ));
	}


	public function cf7a_d8_flamingo_message($before, $after) {
		echo sprintf(
			'<div id="message" class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html( sprintf( __( "I learned this was spam - score before/after: %s/%s", 'cf7-antispam'), $before, $after) )
		);
	}

	public function cf7a_get_mail_additional_data($form_post_id) {

		// get the additional setting of the form
		$form_additional_settings = get_post_meta( $form_post_id, '_additional_settings', true) ;

		if ($form_additional_settings !== '') {
			$lines = explode( "\n", $form_additional_settings); // TODO: best practice is to explode using EOL (End Of Line).

			$additional_settings = array();

			// extract the flamingo_key = value;
			foreach ($lines as $line) {
				$matches = array();
				preg_match('/flamingo_(.*)(?=:): "\[(.*)]"/', $line , $matches);
				$additional_settings[$matches[1]] = $matches[2];
			}

			return $additional_settings;
		}
	}

	public function cf7a_d8_flamingo_classify() {

		if ( !isset($_REQUEST['action'] ) || $_REQUEST['action'] !== 'spam' && $_REQUEST['action'] !== 'unspam' && $_REQUEST['action'] !== 'save' ) {
			return;
		}

		if ( $_REQUEST['action'] === 'save' && $_REQUEST['save'] === 'Update' ) {
			$action = $_REQUEST['inbound']['status'] == 'spam' ? 'spam' : 'ham'; // spam / ham
		} else if ($_REQUEST['action'] === 'spam' ){
			$action = 'spam';
		} else if ($_REQUEST['action'] === 'unspam' ){
			$action = 'ham';
		}

		foreach ( (array) $_REQUEST['post'] as $post ) {

			$flamingo_post = new Flamingo_Inbound_Message( $post );

			// get the form tax using the slug we find in the flamingo message
			$form = get_term_by('slug', $flamingo_post->channel,'flamingo_inbound_channel');

			// get the post where are stored the form data
			$form_post = get_page_by_path($form->slug, '', 'wpcf7_contact_form');

			// get the additional setting of the form
			$additional_settings = $this->cf7a_get_mail_additional_data($form_post->ID);

			if ( isset($additional_settings) && isset( $flamingo_post->fields[$additional_settings['message']] ) ) {

				$text = stripslashes($flamingo_post->fields[$additional_settings['message']]);
				$rating = $text != '' ? $this->cf7a_b8_classify($text) : "none" ;

				if ( $action == 'spam' ) {

					$this->cf7a_b8_unlearn_ham($text);
					$this->cf7a_b8_learn_spam($text);

					$this->cf7a_ban_ip($flamingo_post->meta['remote_ip'], __("flamingo ban"));


				} else if ( $action == 'ham' ) {

					$this->cf7a_b8_unlearn_spam($text);
					$this->cf7a_b8_learn_ham($text);

					//TODO: this ip and the one gathered by plugin can change
					$this->cf7a_unban_ip($flamingo_post->meta['remote_ip'], 9999 );

				} else {
					return;
				}

				$rating_after = $text != '' ? $this->cf7a_b8_classify($text) : "none" ;

				update_post_meta( $flamingo_post->id(), '_cf7a_b8_classification', $rating_after );

				if (CF7ANTISPAM_DEBUG) error_log( sprintf( __( "I learned {$flamingo_post->id()} {$flamingo_post->from_email} was $action - score before/after: %f/%f", 'cf7-antispam'), $rating, $rating_after) );
			}

			$this->cf7a_d8_flamingo_message($rating,$rating_after);
		}
	}

	public function cf7a_d8_flamingo_classify_first( $result ) {

		$submission = WPCF7_Submission::get_instance();

		if ( ! $submission
		     or ! $posted_data = $submission->get_posted_data() ) {
			return;
		}

		$additional_settings = $this->cf7a_get_mail_additional_data($result['contact_form_id']);

		if ( isset($additional_settings) && isset( $posted_data[$additional_settings['message']] ) ) {

			$text   = stripslashes( $posted_data[$additional_settings['message']] );
			$rating = $text != '' ? $this->cf7a_b8_classify( $text ) : "none";

			update_post_meta( $result['flamingo_inbound_id'], '_cf7a_b8_classification', $rating );
		}
	}

	public function flamingo_columns($columns) {
		return array_merge( $columns, array(
			'd8' => __( 'D8 classification', 'cf7-antispam' )
		));
	}

	public function flamingo_d8_column( $column, $post_id ) {
		$classification = get_post_meta($post_id, '_cf7a_b8_classification', true);
		if ( 'd8' === $column ) {
			echo cf7a_formatRating( $classification );
		}
	}


	public function cf7a_spam_filter( $spam ) {

		// Get the submitted data
		$submission = WPCF7_Submission::get_instance();
		// error_log( print_r($submission, true) );

		if ( ! $submission
		     or ! $posted_data = $submission->get_posted_data() ) {
			return;
		}

		// Get the contact form additional data
		$contact_form = $submission->get_contact_form();

		// get the tag used in the form
		$mail_tags=$contact_form->scan_form_tags();

		// the the email and the message from the email
		$email_tag   = substr($contact_form->pref( 'flamingo_email' ), 2, -2);
		$message_tag = substr($contact_form->pref( 'flamingo_message' ), 2, -2);

		$email = isset($posted_data[$email_tag]) ? $posted_data[$email_tag] : false;
		$message = isset($posted_data[$message_tag]) ? $posted_data[$message_tag] : false;

		// let developers hack the message
		apply_filters('cf7a_message_before_processing', $message, $posted_data);


		// the sender data
		$real_remote_ip = cf7a_decrypt( sanitize_text_field($_POST['_wpcf7a_real_sender_ip']) );
		$remote_ip = filter_var( $real_remote_ip, FILTER_VALIDATE_IP ) ? $real_remote_ip : '';

		$user_agent = $submission->get_meta( 'user_agent' );

		// this plugin options
		$options = get_option( 'cf7a_options', array() );

		// check the timestamp
		$timestamp                       = isset($_POST['_wpcf7a_form_creation_timestamp']) ? intval( cf7a_decrypt( sanitize_text_field($_POST['_wpcf7a_form_creation_timestamp']) ) ) : 0;
		$timestamp_submitted             = $submission->get_meta( 'timestamp' );
		$submission_minimum_time_elapsed = 3;
		$submission_maximum_time_elapsed = 3600;

		// Checks sender has a blacklisted ip address
		$bad_ip_list = $options['bad_ip_list'];

		// Checks if the mail contains bad words
		$bad_words = $options['bad_words_list'];

		// Checks if the mail contains bad user agent
		$bad_user_agent_list = $options['bad_user_agent_list'];

		// Check sender mail has prohibited string
		$bad_email_strings = $options['bad_email_strings_list'];

		// b8 threshold
		$b8_threshold = floatval( $options['b8_threshold'] );
		$b8_threshold = ( $b8_threshold > 0 && $b8_threshold < 1 ) ? $b8_threshold : 1;

		// collect data
		$reason  = array();
		$spam_score  = 0;

		/**
		 * Checks if the ip is already banned - no mercy :)
		 */
		$ip_data = self::cf7a_blacklist_get_ip($remote_ip);
		if ( $ip_data && $ip_data->status != 0 && !CF7ANTISPAM_DEBUG_EXTENDED) {

			$spam_score += 1;
			$reason['blacklisted'] = "status " . ($ip_data->status + 1);

		} else {

			/**
			 * Checks if the emails IP is filtered by user
			 */
			if ( $options['check_bad_ip'] ) {

				if ($remote_ip == '') {

					$spam_score += 1;
					$reason['ip'] = $remote_ip;

					if (CF7ANTISPAM_DEBUG) error_log( "The ip is empty, look like a spambot" );

					$submission->add_spam_log( array(
						'agent'  => 'no_ip_address',
						'reason' => "The sender has no ip address set, look like a spambot",
					) );
				}

				foreach ( $bad_ip_list as $bad_ip ) {

					if ( false !== stripos( $remote_ip , $bad_ip ) ) {
						error_log(print_r("$remote_ip contains $bad_ip", true));

						$bad_ip = filter_var($bad_ip, FILTER_VALIDATE_IP);

						$spam_score += 1;
						$reason['ip'] = $bad_ip;

						if (CF7ANTISPAM_DEBUG) error_log( "The ip address is listed into bad ip list - $remote_ip contains $bad_ip" );
					}
				}

				if (isset($reason['ip'])) {
					$reason['ip'] = implode(", ", $reason['ip']);

					$submission->add_spam_log( array(
						'agent'  => 'blacklisted_ip_address',
						'reason' => "The sender ip address contains prohibited address strings {$reason['ip']}",
					) );
				}
			}


			/**
			 * if enabled fingerprints bots
			 */
			if ( $options['check_bot_fingerprint'] ) {
				$bot_fingerprint = array(
					"timezone"             => isset( $_POST['_wpcf7a_timezone'] ) ? sanitize_text_field( $_POST['_wpcf7a_timezone'] ) : '',
					"platform"             => isset( $_POST['_wpcf7a_platform'] ) ? sanitize_text_field( $_POST['_wpcf7a_platform'] ) : '',
					"hardware_concurrency" => isset( $_POST['_wpcf7a_hardware_concurrency'] ) ? intval( $_POST['_wpcf7a_hardware_concurrency'] ) : '',
					"screens"              => isset( $_POST['_wpcf7a_screens'] ) ? sanitize_text_field( $_POST['_wpcf7a_screens'] ) : '',
					"memory"               => isset( $_POST['_wpcf7a_memory'] ) ? intval( $_POST['_wpcf7a_memory'] ) : '',
					"user_agent"           => isset( $_POST['_wpcf7a_user_agent'] ) ? sanitize_text_field( $_POST['_wpcf7a_user_agent'] ) : '',
					"app_version"          => isset( $_POST['_wpcf7a_app_version'] ) ? sanitize_text_field( $_POST['_wpcf7a_app_version'] ) : '',
					"webdriver"            => isset( $_POST['_wpcf7a_webdriver'] ) ? sanitize_text_field( $_POST['_wpcf7a_webdriver'] ) : '',
					"session_storage"      => isset( $_POST['_wpcf7a_session_storage'] ) ? sanitize_text_field( $_POST['_wpcf7a_session_storage'] ) : '',
					"plugins"              => isset( $_POST['_wpcf7a_plugins'] ) ? intval( $_POST['_wpcf7a_plugins'] ) : '',
					"fingerprint"          => isset( $_POST['_wpcf7a_bot_fingerprint'] ) ? sanitize_text_field( $_POST['_wpcf7a_bot_fingerprint'] ) : '',
				);

				$fails = array();
				if (!$bot_fingerprint["timezone"] != '') $fails[] = "timezone";
				if (!$bot_fingerprint["platform"] != '') $fails[] = "platform";
				if (!$bot_fingerprint["hardware_concurrency"] == 4) $fails[] = "hardware_concurrency";
				if (!$bot_fingerprint["screens"] != '') $fails[] = "screens";
				if (!$bot_fingerprint["memory"] > 4)  $fails[] = "memory";
				if (!$bot_fingerprint["user_agent"] != '') $fails[] = "user_agent";
				if (!$bot_fingerprint["app_version"] != '') $fails[] = "app_version";
				if (!$bot_fingerprint["webdriver"] != '') $fails[] = "webdriver";
				if (!$bot_fingerprint["session_storage"] != '') $fails[] = "session_storage";
				if (!$bot_fingerprint["plugins"] > 0) $fails[] = "plugins";
				if (!strlen($bot_fingerprint["fingerprint"]) == 5) $fails[] = "fingerprint";

				if (!empty($fails)) {
					$spam_score                += count( $fails ) * .5;
					$reason['bot_fingerprint'] = implode( ",", $fails );

					if ( CF7ANTISPAM_DEBUG ) {
						error_log( "CF7 Antispam - the submitter hasn't passed " . count( $fails ) . " / " . count( $bot_fingerprint ) . " of the bot fingerprint extra test ({$reason['bot_fingerprint']})" );
					}

					$submission->add_spam_log( array(
						'agent'  => 'fingerprint_test',
						'reason' => "fingerprint test not passed (" . count( $fails ) . " failed / " . count( $bot_fingerprint ) . ")",
					) );
				}

			}


			/**
			 * Bot fingerprints extras
			 */
			if ( $options['check_bot_fingerprint_extras'] ) {
				$bot_fingerprint = array(
					"activity"           => isset( $_POST['_wpcf7a_activity']) ? intval( $_POST['_wpcf7a_activity'] ) : '',
					"mousemove_activity" => isset( $_POST['_wpcf7a_mousemove_activity'] ) && sanitize_text_field( $_POST['_wpcf7a_mousemove_activity'] ) === 'passed' ? 'passed' : 0,
					"webgl"              => isset( $_POST['_wpcf7a_mousemove_activity'] ) && sanitize_text_field( $_POST['_wpcf7a_webgl'] ) === 'passed' ? 'passed' : 0,
					"webgl_render"       => isset( $_POST['_wpcf7a_mousemove_activity'] ) && sanitize_text_field( $_POST['_wpcf7a_webgl_render'] ) === 'passed' ? 'passed' : 0,
					"extras"             => isset( $_POST['_wpcf7a_bot_fingerprint_extras']) ? sanitize_text_field( $_POST['_wpcf7a_bot_fingerprint_extras'] ) : '',
				);

				$fails = array();
				if (!$bot_fingerprint["activity"] > 2) $fails[] = "activity";
				if (!$bot_fingerprint["mousemove_activity"] == true) $fails[] = "mousemove_activity";
				if (!$bot_fingerprint["webgl"] == "passed") $fails[] = "webgl";
				if (!$bot_fingerprint["webgl_render"] == "passed") $fails[] = "webgl_render";
				if (!empty($bot_fingerprint["extras"])) $fails[] = "extras";

				if (!empty($fails)) {
					$spam_score += count($fails) * .5;
					$reason['bot_fingerprint_extras'] = implode(", ", $fails);

					if (CF7ANTISPAM_DEBUG) error_log( "CF7 Antispam - the submitter hasn't passed ".count($fails)." / " . count( $bot_fingerprint ) . " of the bot fingerprint extra test ({$reason['bot_fingerprint_extras']})" );

					$submission->add_spam_log( array(
						'agent'  => 'fingerprint_extra tests',
						'reason' => "fingerprint extra tests not passed (".count($fails)." failed / " . count( $bot_fingerprint ) . ")",
					) );
				}

			}


			/**
			 * Check if the time to submit the email il lower than expected
			 */
			if ( $options['check_time'] ) {

				if ($timestamp == 0) {

					$spam_score += 5;
					$reason['timestamp'] = 'undefined';

					if (CF7ANTISPAM_DEBUG) error_log( "_wpcf7a_timestamp field is missing, probable form hacking attempt" );

					$submission->add_spam_log( array(
						'agent'  => 'timestamp_issue',
						'reason' => "_wpcf7a_timestamp field is missing, probable form hacking attempt",
					) );
				}

				if ( $timestamp_submitted <= ( $timestamp + $submission_minimum_time_elapsed ) ) {

					$time_elapsed = $timestamp_submitted - $timestamp;

					$spam_score += 5;
					$reason['min_time_elapsed'] = $time_elapsed;

					if (CF7ANTISPAM_DEBUG) error_log( "It took too little time to fill in the form - ($time_elapsed)" );

					$submission->add_spam_log( array(
						'agent'  => 'timestamp_issue',
						'reason' => "Sender send the email in $time_elapsed. Too little to complete this form!",
					) );
				}

				/**
				 * Check if the time to submit the email il higher than expected
				 */
				if ( $timestamp_submitted >= ( $timestamp + $submission_maximum_time_elapsed ) ) {

					$time_elapsed = $timestamp_submitted - $timestamp;

					$spam_score += 5;
					$reason['max_time_elapsed'] = $time_elapsed;

					if (CF7ANTISPAM_DEBUG) error_log( "It took too much time to fill in the form - ($time_elapsed)" );

					$submission->add_spam_log( array(
						'agent'  => 'timestamp_issue',
						'reason' => "Sender send the email in $time_elapsed. Too much time to complete this form or the timestamp was hacked!",
					) );
				}
			}


			/**
			 * Checks if the emails contains prohibited words
			 * for example it check if the sender mail is the same than the website domain because it is an attempt to bypass controls,
			 * because emails client can't blacklists the email itself, we must prevent it
			 */
			if ( $options['check_bad_email_strings'] && $email ) {

				foreach ( $bad_email_strings as $bad_email_string ) {

					if ( false !== stripos( strtolower( $email ), strtolower( $bad_email_string ) ) ) {

						$spam_score += 1;
						$reason['email_blackilisted'][] = $email;

						if (CF7ANTISPAM_DEBUG) error_log( "The sender mail domain is the same of the website - {$email} contains $bad_email_string" );

					}
				}
				if (isset($reason['email_blackilisted'])) {
					$reason['email_blackilisted'] = implode(",", $reason['email_blackilisted']);

					$submission->add_spam_log( array(
						'agent'  => 'bad_email_strings',
						'reason' => "The sender email was listed into denied strings ({$reason['email_blackilisted']})",
					) );
				}
			}


			/**
			 * Checks if the emails user agent is denied
			 */
			if ( $options['check_bad_user_agent'] ) {

				if ($user_agent == '') {

					$spam_score += 5;
					$reason['user_agent_empty'] = "undefined";

					if (CF7ANTISPAM_DEBUG) error_log( "The email user agent is empty, look like a spambot");

					$submission->add_spam_log( array(
						'agent'  => 'no_user_agent',
						'reason' => "The sender has no user agent set, look like a spambot",
					) );
				}

				foreach ( $bad_user_agent_list as $bad_user_agent ) {

					if ( false !== stripos( strtolower( $user_agent ), strtolower( $bad_user_agent ) ) ) {

						$spam_score += 1;
						$reason['user_agent_blacklisted'][] = $user_agent;

						if (CF7ANTISPAM_DEBUG) error_log( "The email user agent was listed into bad user agent list - $user_agent contains $bad_user_agent" );
					}

					if (isset($reason['user_agent_blacklisted'])) {
						$reason['user_agent_blacklisted'] = implode(", ", $reason['user_agent_blacklisted']);

						$submission->add_spam_log( array(
							'agent'  => 'bad_user_agent',
							'reason' => "The email user agent was listed into bad user agent list ({$reason['user_agent_blacklisted']})",
						) );
					}
				}
			}


			/**
			 * Search for prohibited words
			 */
			if ( $options['check_bad_words'] && $message != '' ) {

				// to search strings into message without space and case unsensitive
				$message_compressed = str_replace( " ", "", strtolower( $message ) );

				foreach ( $bad_words as $bad_word ) {
					if ( false !== stripos( $message_compressed, str_replace( " ", "", strtolower( $bad_word ) ) ) ) {

						$spam_score += 3;
						$reason['bad_word'][] = $bad_word;

						if (CF7ANTISPAM_DEBUG) error_log( "Detected a bad word ($bad_word)" );
					}
				}

				if (isset($reason['bad_word'])) {
					$reason['bad_word'] = implode(",", $reason['bad_word']);

					$submission->add_spam_log( array(
						'agent'  => 'bad_words',
						'reason' => "Detected a bad word ({$reason['bad_word']})",
					) );
				}
			}


			/**
			 * Check the remote ip if is listed into Domain Name System Blacklists
			 * DNS blacklist are spam blocking DNS like lists that allow to block messages from specific systems that have a history of sending spam
			 * inspiration taken from https://gist.github.com/tbreuss/74da96ff5f976ce770e6628badbd7dfc
			 *
			 * TODO: enhance the performance using curl or threading. break after threshold reached
			 */
			if ( $options['check_dnsbl'] && $remote_ip ) {

				$dsnbl_listed = array();
				$reverse_ip = '';

				if ( filter_var( $remote_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {

					$reverse_ip = $this->cf7a_reverse_ipv4( $remote_ip );

				} else if ( filter_var( $remote_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {

					$reverse_ip = $this->cf7a_reverse_ipv6( $remote_ip );
				}

				$performance_test = array();
				foreach ($options['dnsbl_list'] as $dnsbl) {
					$microtime = cf7a_microtimeFloat();
					if ( false !== ( $listed = $this->cf7a_check_dnsbl( $reverse_ip, $dnsbl ) ) ) {
						$dsnbl_listed[] = $listed;
						$spam_score += 0.5;
					}
					$time_taken = round( cf7a_microtimeFloat() - $microtime, 5 );
					$performance_test[$dnsbl] = $time_taken;
				}

				if (CF7ANTISPAM_DEBUG_EXTENDED) {
					error_log( "DNSBL performance test" );
					error_log( print_r($performance_test, true) );
				}

				if (isset($dsnbl_listed)) {
					if (CF7ANTISPAM_DEBUG_EXTENDED) error_log( "The $remote_ip has tried to send an email but is listed ".count($dsnbl_listed)." times in the Domain Name System Blacklists ("  . implode(", ", $dsnbl_listed) .")" );

					$reason['dsnbl'] = implode(", ",$dsnbl_listed);

					$submission->add_spam_log( array(
						'agent'  => 'dnsbl_listed',
						'reason' => "$remote_ip listed in the dnsbl ({$reason['dsnbl']})",
					) );
				}
			}


			/**
			 * Checks Honeypots input if they are filled
			 */
			if ( $options['check_honeypot'] ) {

				// we need only the text tags of the form
				foreach ( $mail_tags as $mail_tag ) {
					if ( $mail_tag['type'] == 'text' || $mail_tag['type'] == 'text*' ) {
						$mail_tag_text[] = $mail_tag['name'];
					}
				}

				if (isset($mail_tag_text)) {

					// faked input name used into honeypots
					$input_names = $options['honeypot_input_names'];

					for ( $i = 0; $i < count( $mail_tag_text ); $i ++ ) {

						// check only if it's set and if it is different from ""
						if ( isset( $_POST[ $input_names[ $i ] ] ) && $_POST[ $input_names[ $i ]] != '' ) {

							$spam_score += 3;
							$reason['honeypot'][] = $input_names[ $i ];

							if (CF7ANTISPAM_DEBUG) error_log( "Detected a honeypot filled ({$input_names[ $i ]})" );
						}

					}

					if ( isset( $reason['honeypot']) ) {
						$reason['honeypot'] = implode( ", ", $reason['honeypot'] );

						$submission->add_spam_log( array(
							'agent' => 'honeypot',
							'reason' => "the bot has filled honeypot input ({$reason['honeypot']})",
						) );
					}
				}
			}

		}


		// hook to add some filters before d8
		do_action('cf7a_before_b8', $message, $submission, $spam);

		/**
		 * B8 is a statistical "Bayesian" spam filter
		 * https://nasauber.de/opensource/b8/
		 */
		if ( $options['enable_b8'] && $message ) {

			$text   = stripslashes( $message );

			$rating = $this->cf7a_b8_classify($text);

			if ( $spam_score >= 1 || $rating >= $b8_threshold ) {

				$spam = true;
				error_log( "Antispam for Contact Form 7: $remote_ip will be rejected because suspected of spam! (score $spam_score / 1)" );

				if (!defined( 'FLAMINGO_VERSION' )) $this->cf7a_b8_learn_spam($text);

				if ($rating > $b8_threshold) {

					$reason['b8'] = $rating;

					if (CF7ANTISPAM_DEBUG) error_log( "CF7 Antispam - D8 detect spamminess of $rating while the minimum is > $b8_threshold so this mail will be marked as spam" );

					$submission->add_spam_log( array(
						'agent'  => 'd8_spam_detected',
						'reason' => "d8 spam detected with ration of $rating",
					) );
				}

			} else if ( $rating < ( $b8_threshold * .5 ) ) {

				// the mail was classified as ham so we let learn to d8 what is considered (a probable) ham
				if (!defined( 'FLAMINGO_VERSION' )) $this->cf7a_b8_learn_ham($text);

				if (CF7ANTISPAM_DEBUG) error_log( "CF7 Antispam - D8 detect spamminess of $rating (below the half of the threshold of $b8_threshold) so this mail will be marked as ham" );
			}
		}

		// hook to add some filters after d8
		do_action('cf7a_additional_spam_filters', $message, $submission, $spam);

		if ($options['autostore_bad_ip'] && $spam) {
			if (false == $this->cf7a_ban_ip($remote_ip, $reason, round($spam_score) ) && CF7ANTISPAM_DEBUG)
				error_log( "Antispam for Contact Form 7: unable to ban $remote_ip" );
		}

		return $spam; // case closed
	}

}