<?php

class CF7_AntiSpam_filters {

	protected $b8;

	/**
	 * CF7_AntiSpam_filters constructor.
	 */
	public function __construct() {

		$this->b8 = $this->cf7a_b8_init();

	}



	/**
	 * CF7_AntiSpam_filters Tools
	 */

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

	public function cf7a_get_mail_additional_data($form_post_id) {

		// get the additional setting of the form
		$form_additional_settings = get_post_meta( $form_post_id, '_additional_settings', true) ;

		if (!empty($form_additional_settings)) {
			$lines = explode( "\n", $form_additional_settings);

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




	/**
	 * CF7_AntiSpam_filters b8
	 */
	private function cf7a_b8_init() {
		// the database
		global $wpdb;

		$db = explode( ":", DB_HOST );
		$db_address = $db[0];
		$db_port = !empty($db[1]) ? intval($db[1]) : 3306;

		// B8 config
		$mysql = new mysqli( $db_address, DB_USER, DB_PASSWORD, DB_NAME, $db_port );

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
			error_log( CF7ANTISPAM_LOG_PREFIX . 'error message: ' . $e->getMessage() );
			exit();
		}
	}

	public function cf7a_b8_classify($message) {
		$time_elapsed = cf7a_microtimeFloat();

		$rating = $this->b8->classify( $message );

		if ( CF7ANTISPAM_DEBUG_EXTENDED ) {
			error_log( CF7ANTISPAM_LOG_PREFIX .'d8 email classification: ' . $rating );

			$mem_used      = round( memory_get_usage() / 1048576, 5 );
			$peak_mem_used = round( memory_get_peak_usage() / 1048576, 5 );
			$time_taken    = round( cf7a_microtimeFloat() - $time_elapsed, 5 );

			error_log( CF7ANTISPAM_LOG_PREFIX . "stats : Memory: $mem_used - Peak memory: $peak_mem_used - Time Elapsed: $time_taken" );
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


	/**
	 * CF7_AntiSpam_filters blacklists
	 */
	public function cf7a_blacklist_get_ip($ip) {

		if (false === ($ip = filter_var($ip, FILTER_VALIDATE_IP))) return false;

		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}cf7a_blacklist WHERE ip = %s", $ip ) );
	}


	public function cf7a_blacklist_get_id($id) {

		if ( ! is_int( $id ) ) return false;

		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}cf7a_blacklist WHERE id = %s", $id ) );
	}


	public function cf7a_ban_ip($ip, $reason = array(), $spam_score = 1) {


		if (false === ($ip = filter_var($ip, FILTER_VALIDATE_IP) )) return false;

		$ip_row = self::cf7a_blacklist_get_ip($ip);

		global $wpdb;

		$r = $wpdb->replace(
			$wpdb->prefix . "cf7a_blacklist",
			array(
				'ip' => $ip,
				'status' => isset($ip_row->status) ? intval($ip_row->status) + intval($spam_score) : 1,
				'meta' => serialize(array('reason'=> $reason, 'meta'=> null ))
			),
			array( '%s', '%d', '%s' )
		);

		return true;
	}

	public function cf7a_unban_by_ip($ip) {

		if (false == ($ip = filter_var($ip, FILTER_VALIDATE_IP) ) ) return false;

		global $wpdb;

		$r = $wpdb->delete(
			$wpdb->prefix . "cf7a_blacklist",
			array(
				'ip' => $ip
			),
			array(
				'%s'
			)
		);

		return (!is_wp_error($r)) ? $r : $wpdb->last_error;
	}

	public function cf7a_unban_by_id($id) {

		$id = intval($id);

		global $wpdb;

		$r = $wpdb->delete(
			$wpdb->prefix . "cf7a_blacklist",
			array(
				'id' => $id
			),
			array(
				'%d'
			)
		);

		return (!is_wp_error($r)) ? $r : $wpdb->last_error;

	}

	public function cf7a_clean_blacklist() {
		global $wpdb;
		$r = $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}cf7a_blacklist" );
		return !is_wp_error($r) ? true : false;
	}

	public function cf7a_d8_flamingo_message($before, $after) {
		echo sprintf(
			'<div id="message" class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html( sprintf( __( CF7ANTISPAM_LOG_PREFIX . "d8 has learned this was spam - score before/after: %s/%s", 'cf7-antispam'), $before, $after) )
		);
	}


	/**
	 * CF7_AntiSpam_filters Flamingo
	 */

	public function cf7a_flamingo_on_install() {
		// get all the flamingo inbound post and classify them
		$args = array(
			'post_type' => 'flamingo_inbound',
			'posts_per_page' => -1
		);

		$query = new WP_Query($args);
		if ($query->have_posts() ) :

			$post_storage = array();

			while ( $query->have_posts() ) : $query->the_post();
				$post_id = get_the_ID();
				$post_status = get_post_status();
				$content = get_the_content();

				if (get_post_status( $post_id ) == 'flamingo-spam') {
					$this->cf7a_b8_learn_spam($content);
				} else if ( $post_status == 'publish'){
					$this->cf7a_b8_learn_ham($content);
				};

				$post_storage[$post_id] = $content;

			endwhile;

			foreach ($post_storage as $id => $post) {
				update_post_meta( $id, '_cf7a_b8_classification', $this->cf7a_b8_classify($post) );
			};

		endif;
	}

	public static function cf7a_flamingo_on_uninstall() {
		// get all the flamingo inbound post and delete the custom meta created with this plugin
		$args = array(
			'post_type' => 'flamingo_inbound',
			'posts_per_page' => -1
		);

		$query = new WP_Query($args);
		if ($query->have_posts() ) :
			while ( $query->have_posts() ) : $query->the_post();
				delete_post_meta( get_the_ID(), '_cf7a_b8_classification');
			endwhile;
		endif;
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
					$this->cf7a_unban_by_ip($flamingo_post->meta['remote_ip'] );

				} else {
					return;
				}

				$rating_after = !empty($text) ? $this->cf7a_b8_classify($text) : "none";

				update_post_meta( $flamingo_post->id(), '_cf7a_b8_classification', $rating_after );

				if (CF7ANTISPAM_DEBUG) error_log( sprintf( __( "%sD8 learned %s %s was %s - score before/after: %f/%f", 'cf7-antispam'),
					CF7ANTISPAM_LOG_PREFIX,
					$flamingo_post->id(),
					$flamingo_post->from_email,
					$action,
					$rating,
					$rating_after)
				);
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


	/**
	 * CF7_AntiSpam_filters The antispam filter
	 *
	 * @param $spam bool - if is spam or not
	 *
	 * @return bool
	 */
	public function cf7a_spam_filter( $spam ) {
		// Get the submitted data
		$submission = WPCF7_Submission::get_instance();

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

		// this plugin options
		$options = get_option( 'cf7a_options', array() );
		$prefix = sanitize_html_class($options['cf7a_customizations_prefix']);

		// the data of the user who sent this email
		// IP
		$real_remote_ip = isset( $_POST[ $prefix . 'address' ] ) ? cf7a_decrypt( sanitize_text_field( $_POST[ $prefix . 'address' ] ) ) : null;
		$remote_ip = $real_remote_ip ? filter_var( $real_remote_ip, FILTER_VALIDATE_IP ) : null;
		$cf7_remote_ip = sanitize_text_field($submission->get_meta( 'remote_ip' ));

		// CF7A version
		$cf7a_version = isset( $_POST[ $prefix . '_version' ] ) ? cf7a_decrypt( sanitize_text_field( $_POST[ $prefix . '_version' ] ) ) : null;

		// CF7 user agent
		$user_agent = sanitize_text_field($submission->get_meta( 'user_agent' ));

		// Timestamp checks
		$timestamp                       = isset($_POST[$prefix.'_timestamp']) ? intval( cf7a_decrypt( sanitize_text_field($_POST[$prefix.'_timestamp']) ) ) : 0;
		$timestamp_submitted             = time(); // can be cached so isn't safe to use -> $submission->get_meta( 'timestamp' );
		$submission_minimum_time_elapsed = intval($options['check_time_min']);
		$submission_maximum_time_elapsed = intval($options['check_time_max']);

		// Checks sender has a blacklisted ip address
		$bad_ip_list = isset($options['bad_ip_list']) ? $options['bad_ip_list'] : array();

		// Checks if the mail contains bad words
		$bad_words = isset($options['bad_words_list']) ? $options['bad_words_list'] : array();

		// Checks if the mail contains bad user agent
		$bad_user_agent_list = isset($options['bad_user_agent_list']) ? $options['bad_user_agent_list'] : array();

		// Check sender mail has prohibited string
		$bad_email_strings = isset($options['bad_email_strings_list']) ? $options['bad_email_strings_list'] : array();

		// b8 threshold
		$b8_threshold = floatval( $options['b8_threshold'] );
		$b8_threshold = ( $b8_threshold > 0 && $b8_threshold < 1 ) ? $b8_threshold : 1;

		// Scoring
		$score_fingerprinting = floatval( $options['score']['_fingerprinting'] );
		$score_time = floatval( $options['score']['_time'] );
		$score_bad_string = floatval( $options['score']['_bad_string'] );
		$score_dnsbl = floatval( $options['score']['_dnsbl'] );
		$score_honeypot = floatval( $options['score']['_honeypot'] );
		$score_honeyform = floatval( $options['score']['_honeyform'] );
		$score_warn = floatval( $options['score']['_warn'] );
		$score_detection = floatval( $options['score']['_detection'] );

		// collect data
		$reason  = array();
		$spam_score  = 0;

		/**
		 * Checks if the ip is already banned - no mercy :)
		 * TODO: add also all the ip of the ip strings (that are valid ip)
		 */
		if ( !$remote_ip ) {

			$remote_ip = $cf7_remote_ip ? $cf7_remote_ip : null;

			$spam_score += $score_detection;
			$reason['no_ip'] = "Address field empty";

			if (CF7ANTISPAM_DEBUG)
				error_log( CF7ANTISPAM_LOG_PREFIX . "ip address field of $remote_ip is empty, this means it has been modified, removed or hacked! (used php data to get the real ip)" );
		}


		if ($remote_ip && $options['autostore_bad_ip'] && !CF7ANTISPAM_DEBUG_EXTENDED) {

			$ip_data = self::cf7a_blacklist_get_ip($remote_ip);
			$ip_data_status = isset($ip_data->status) ? intval($ip_data->status) : 0;

			if ($ip_data_status != 0) {

				$spam_score += $score_detection;
				$reason['blacklisted'] = "Score: " . ($ip_data_status + $score_warn);

				if (CF7ANTISPAM_DEBUG)
					error_log( CF7ANTISPAM_LOG_PREFIX . "The $remote_ip is already blacklisted, status $ip_data_status" );
			}
		}

		/**
		 * Check the CF7 AntiSpam version field
		 */
		if ( !$cf7a_version || $cf7a_version != CF7ANTISPAM_VERSION ) {

			$spam_score += $score_warn;
			$reason['data_mismatch'] = "Version mismatch $cf7a_version/".CF7ANTISPAM_VERSION;

			if (CF7ANTISPAM_DEBUG)
				error_log( CF7ANTISPAM_LOG_PREFIX . "Incorrect data submitted by $remote_ip in the hidden field _version, may have been modified, removed or hacked" );
		}


		if ( intval( $options['check_honeyform'] ) !== 0 ) {

			$form_class = sanitize_html_class( $options['cf7a_customizations_class'] );

			// get the "marker" field
			if ( isset( $_POST[ '_wpcf7_' . $form_class ] ) ) {
				$spam_score                += $score_honeyform;
				$reason['bot_fingerprint'] = "honeyform";
			}
		}


		/**
		 * if the mail was marked as spam no more checks are needed.
		 * This will save server computing power, this ip has already been banned so there's no need to push it.
		 */
		if ($spam_score < 1) {


			/**
			 * if enabled fingerprints bots
			 */
			if ( intval( $options['check_bot_fingerprint'] ) == 1 ) {
				$bot_fingerprint = array(
					"timezone"             => !empty( $_POST[$prefix.'timezone'] ) ? sanitize_text_field( $_POST[$prefix.'timezone'] ) : null,
					"platform"             => !empty( $_POST[$prefix.'platform'] ) ? sanitize_text_field( $_POST[$prefix.'platform'] ) : null,
					"hardware_concurrency" => !empty( $_POST[$prefix.'hardware_concurrency'] ) ? intval( $_POST[$prefix.'hardware_concurrency'] ) : null,
					"screens"              => !empty( $_POST[$prefix.'screens'] ) ? sanitize_text_field( $_POST[$prefix.'screens'] ) : null,
					"memory"               => !empty( $_POST[$prefix.'memory'] ) ? intval( $_POST[$prefix.'memory'] ) : null,
					"user_agent"           => !empty( $_POST[$prefix.'user_agent'] ) ? sanitize_text_field( $_POST[$prefix.'user_agent'] ) : null,
					"app_version"          => !empty( $_POST[$prefix.'app_version'] ) ? sanitize_text_field( $_POST[$prefix.'app_version'] ) : null,
					"webdriver"            => !empty( $_POST[$prefix.'webdriver'] ) ? sanitize_text_field( $_POST[$prefix.'webdriver'] ) : null,
					"session_storage"      => !empty( $_POST[$prefix.'session_storage'] ) ? sanitize_text_field( $_POST[$prefix.'session_storage'] ) : null,
					"bot_fingerprint"      => !empty( $_POST[$prefix.'bot_fingerprint'] ) ? sanitize_text_field( $_POST[$prefix.'bot_fingerprint'] ) : null,
					"isSafari"             => !empty( $_POST[$prefix.'isSafari'] ) ? intval( $_POST[$prefix.'isSafari'] ) : null,
					"isIOS"                => !empty( $_POST[$prefix.'isIOS'] ) ? intval( $_POST[$prefix.'isIOS'] ) : null,
				);

				$fails = array();
				if (!$bot_fingerprint["timezone"]) $fails[] = "timezone";
				if (!$bot_fingerprint["platform"]) $fails[] = "platform";
				if ($bot_fingerprint["isSafari"] && !$bot_fingerprint["hardware_concurrency"] >= 2) $fails[] = "hardware_concurrency";
				if (!$bot_fingerprint["screens"]) $fails[] = "screens";
				if ($bot_fingerprint["isSafari"] && (!$bot_fingerprint["memory"] || $bot_fingerprint["memory"] == 1))  $fails[] = "memory";
				if (!$bot_fingerprint["user_agent"]) $fails[] = "user_agent";
				if (!$bot_fingerprint["app_version"]) $fails[] = "app_version";
				if (!$bot_fingerprint["webdriver"]) $fails[] = "webdriver";
				if (!$bot_fingerprint["session_storage"]) $fails[] = "session_storage";
				if (strlen($bot_fingerprint["bot_fingerprint"]) != 5) $fails[] = "bot_fingerprint";
				if ($bot_fingerprint["isIOS"] && !$bot_fingerprint["isSafari"]) $fails[] = "safari_not_ios";

				if (!empty($fails)) {
					$spam_score                += count( $fails ) * $score_fingerprinting;
					$reason['bot_fingerprint'] = implode( ", ", $fails );

					if (CF7ANTISPAM_DEBUG_EXTENDED) error_log( CF7ANTISPAM_LOG_PREFIX . print_r($bot_fingerprint, true) );

					if (CF7ANTISPAM_DEBUG)
						error_log( CF7ANTISPAM_LOG_PREFIX . "The $remote_ip ip hasn't passed " . count( $fails ) . " / " . count( $bot_fingerprint ) . " of the bot fingerprint test ({$reason['bot_fingerprint']})" );
				}

			}


			/**
			 * Bot fingerprints extras
			 */
			if ( intval( $options['check_bot_fingerprint_extras'] ) == 1 ) {
				$bot_fingerprint_extras = array(
					"activity"            => !empty( $_POST[ $prefix . 'activity' ] ) ? intval( $_POST[ $prefix . 'activity' ] ) : 0,
					"mouseclick_activity" => !empty( $_POST[ $prefix . 'mouseclick_activity' ] ) && sanitize_text_field( $_POST[ $prefix . 'mouseclick_activity' ] ) === 'passed' ? 'passed' : 0,
					"mousemove_activity"  => !empty( $_POST[ $prefix . 'mousemove_activity' ] ) && sanitize_text_field( $_POST[ $prefix . 'mousemove_activity' ] ) === 'passed' ? 'passed' : 0,
					"webgl"               => !empty( $_POST[ $prefix . 'webgl' ] ) && sanitize_text_field( $_POST[ $prefix . 'webgl' ] ) === 'passed' ? 'passed' : 0,
					"webgl_render"        => !empty( $_POST[ $prefix . 'webgl_render' ] ) && sanitize_text_field( $_POST[ $prefix . 'webgl_render' ] ) === 'passed' ? 'passed' : 0,
					"bot_fingerprint_extras" => !empty( $_POST[ $prefix . 'bot_fingerprint_extras' ] ) ? sanitize_text_field( $_POST[ $prefix . 'bot_fingerprint_extras' ] ) : 0,
				);

				$fails = array();
				if ($bot_fingerprint_extras["activity"] < 3 ) $fails[] = "activity {$bot_fingerprint_extras["activity"]}"; // todo: the click value need to be global
				if ($bot_fingerprint_extras["mouseclick_activity"] !== "passed" ) $fails[] = "mouseclick_activity";
				if ($bot_fingerprint_extras["mousemove_activity"] !== "passed" ) $fails[] = "mousemove_activity";
				if ($bot_fingerprint_extras["webgl"] !== "passed" ) $fails[] = "webgl";
				if ($bot_fingerprint_extras["webgl_render"] !== "passed" ) $fails[] = "webgl_render";
				if (!empty($bot_fingerprint_extras["bot_fingerprint_extras"]) ) $fails[] = "bot_fingerprint_extras";

				if (!empty($fails)) {

					$spam_score += count($fails) * $score_fingerprinting;
					$reason['bot_fingerprint_extras'] = implode(", ", $fails);

					if (CF7ANTISPAM_DEBUG_EXTENDED) error_log( CF7ANTISPAM_LOG_PREFIX . print_r($bot_fingerprint_extras, true) );

					if (CF7ANTISPAM_DEBUG)
						error_log( CF7ANTISPAM_LOG_PREFIX . "The $remote_ip ip hasn't passed ".count($fails)." / " . count( $bot_fingerprint_extras ) . " of the bot fingerprint extra test ({$reason['bot_fingerprint_extras']})" );
				}

			}


			/**
			 * Check if the time to submit the email il lower than expected
			 */
			if ( intval( $options['check_time'] ) == 1 ) {

				if ( !$timestamp || $timestamp == 0 ) {

					$spam_score += $score_detection;
					$reason['timestamp'] = 'undefined';

					if (CF7ANTISPAM_DEBUG)
						error_log( CF7ANTISPAM_LOG_PREFIX . "The $remote_ip ip _timestamp field is missing, probable form hacking attempt from $remote_ip" );

				} else {

					$time_now = $timestamp_submitted;

					$time_elapsed = $time_now - $timestamp;

					if ( $time_elapsed < $submission_minimum_time_elapsed ) {

						$spam_score += $score_time;
						$reason['min_time_elapsed'] = $time_elapsed;

						if (CF7ANTISPAM_DEBUG)
							error_log( CF7ANTISPAM_LOG_PREFIX . "The $remote_ip ip took too little time to fill in the form - ($time_elapsed)" );
					}

					/**
					 * Check if the time to submit the email il higher than expected
					 */
					if ( $time_elapsed > $submission_maximum_time_elapsed ) {

						$spam_score += $score_time;
						$reason['max_time_elapsed'] = $time_elapsed;

						if (CF7ANTISPAM_DEBUG)
							error_log( CF7ANTISPAM_LOG_PREFIX . "The $remote_ip ip took too much time to fill in the form - ($time_elapsed)" );
					}
				}
			}


			/**
			 * Checks if the emails IP is filtered by user
			 */
			if ( intval( $options['check_bad_ip'] ) == 1 ) {

				foreach ( $bad_ip_list as $bad_ip ) {

					if ( false !== stripos( $remote_ip , $bad_ip ) ) {

						$bad_ip = filter_var($bad_ip, FILTER_VALIDATE_IP);

						$spam_score += $score_bad_string;
						$reason['ip'][] = $bad_ip;


					}
				}

				if (isset($reason['ip'])) {
					$reason['ip'] = implode(", ", $reason['ip']);

					if (CF7ANTISPAM_DEBUG)
						error_log( CF7ANTISPAM_LOG_PREFIX . "The ip address $remote_ip is listed into bad ip list (contains {$reason['ip']})" );
				}


			}


			/**
			 * Checks if the emails contains prohibited words
			 * for example it check if the sender mail is the same than the website domain because it is an attempt to bypass controls,
			 * because emails client can't blacklists the email itself, we must prevent it
			 */
			if ( intval( $options['check_bad_email_strings'] ) == 1 && $email ) {

				foreach ( $bad_email_strings as $bad_email_string ) {

					if ( false !== stripos( strtolower( $email ), strtolower( $bad_email_string ) ) ) {

						$spam_score += $score_bad_string;
						$reason['email_blackilisted'][] = $email;
					}
				}

				if (isset($reason['email_blackilisted'])) {

					$reason['email_blackilisted'] = implode(",", $reason['email_blackilisted']);

					if (CF7ANTISPAM_DEBUG)
						error_log( CF7ANTISPAM_LOG_PREFIX . "The ip address $remote_ip  sent a mail from {$email} but contains {$reason['email_blackilisted']} (blacklisted email string)" );
				}


			}


			/**
			 * Checks if the emails user agent is denied
			 */
			if ( intval( $options['check_bad_user_agent'] ) == 1 ) {

				if (!$user_agent) {

					$spam_score += $score_detection;
					$reason['user_agent'] = "empty";

					if (CF7ANTISPAM_DEBUG) error_log( CF7ANTISPAM_LOG_PREFIX . "The $remote_ip ip user agent is empty, look like a spambot");

				} else {

					foreach ( $bad_user_agent_list as $bad_user_agent ) {

						if ( false !== stripos( strtolower( $user_agent ), strtolower( $bad_user_agent ) ) ) {

							$spam_score += $score_bad_string;
							$reason['user_agent_blacklisted'][] = $user_agent;
						}

						if (isset($reason['user_agent_blacklisted'])) {
							$reason['user_agent_blacklisted'] = implode(", ", $reason['user_agent_blacklisted']);

							if (CF7ANTISPAM_DEBUG) error_log( CF7ANTISPAM_LOG_PREFIX . "The $remote_ip ip user agent was listed into bad user agent list - $user_agent contains {$reason['user_agent_blacklisted']}" );
						}
					}
				}
			}


			/**
			 * Search for prohibited words
			 */
			if ( intval( $options['check_bad_words'] ) == 1 && $message != '' ) {

				// to search strings into message without space and case unsensitive
				$message_compressed = str_replace( " ", "", strtolower( $message ) );

				foreach ( $bad_words as $bad_word ) {
					if ( false !== stripos( $message_compressed, str_replace( " ", "", strtolower( $bad_word ) ) ) ) {

						$spam_score += $score_bad_string;
						$reason['bad_word'][] = $bad_word;
					}
				}

				if (isset($reason['bad_word'])) {
					$reason['bad_word'] = implode(",", $reason['bad_word']);

					if (CF7ANTISPAM_DEBUG)
						error_log( CF7ANTISPAM_LOG_PREFIX . "$remote_ip has bad word in message ($bad_word)" );
				}
			}



			/**
			 * Check the remote ip if is listed into Domain Name System Blacklists
			 * DNS blacklist are spam blocking DNS like lists that allow to block messages from specific systems that have a history of sending spam
			 * inspiration taken from https://gist.github.com/tbreuss/74da96ff5f976ce770e6628badbd7dfc
			 *
			 * TODO: enhance the performance using curl or threading. break after threshold reached
			 */
			if ( intval( $options['check_dnsbl'] ) == 1 && $remote_ip ) {

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
						$reason['dsnbl'][] = $listed;
						$spam_score += $score_dnsbl;
					}
					$time_taken = round( cf7a_microtimeFloat() - $microtime, 5 );
					$performance_test[$dnsbl] = $time_taken;
				}

				if (CF7ANTISPAM_DEBUG_EXTENDED) {
					error_log( CF7ANTISPAM_LOG_PREFIX . "DNSBL performance test" );
					error_log( print_r($performance_test, true) );
				}

				if (isset($reason['dsnbl'])) {

					$dsnbl_count = count($reason['dsnbl']);
					$reason['dsnbl'] = implode(", ",$reason['dsnbl']);

					if (CF7ANTISPAM_DEBUG) error_log( CF7ANTISPAM_LOG_PREFIX . "The $remote_ip has tried to send an email but is listed $dsnbl_count times in the Domain Name System Blacklists ({$reason['dsnbl']})" );
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
							$spam_score += $score_honeypot;
							$reason['honeypot'][] = $input_names[ $i ];
						}
					}

					if ( isset( $reason['honeypot']) ) {
						$reason['honeypot'] = implode( ", ", $reason['honeypot'] );

						if (CF7ANTISPAM_DEBUG) error_log( CF7ANTISPAM_LOG_PREFIX . "The $remote_ip has filled the input honeypot {$reason['honeypot']}" );
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
		if ( $options['enable_b8'] && $message && !isset( $reason['blacklisted'] ) ) {

			$text   = stripslashes( $message );

			$rating = $this->cf7a_b8_classify($text);


			if ( $spam_score >= 1 || $rating >= $b8_threshold ) {

				$spam = true;
				error_log( CF7ANTISPAM_LOG_PREFIX . "$remote_ip will be rejected because suspected of spam! (score $spam_score / 1)" );

				$this->cf7a_b8_learn_spam($text);

				if ($rating > $b8_threshold) {

					$reason['b8'] = $rating;

					if (CF7ANTISPAM_DEBUG) error_log( CF7ANTISPAM_LOG_PREFIX . "D8 detect spamminess of $rating while the minimum is > $b8_threshold so the mail from $remote_ip will be marked as spam" );
				}

			} else if ( $rating < ( $b8_threshold * .5 ) ) {

				// the mail was classified as ham so we let learn to d8 what is considered (a probable) ham
				$this->cf7a_b8_learn_ham($text);

				if (CF7ANTISPAM_DEBUG) error_log( CF7ANTISPAM_LOG_PREFIX . "D8 detect spamminess of $rating (below the half of the threshold of $b8_threshold) so the mail from $remote_ip will be marked as ham" );
			}


		} else if ($spam = $spam_score >= 1 ? true : $spam) {

			// if d8 isn't enabled we only need to mark as spam and leave a log
			if (CF7ANTISPAM_DEBUG) error_log( CF7ANTISPAM_LOG_PREFIX . "$remote_ip will be rejected because suspected of spam! (score $spam_score / 1)" );
		}

		// hook to add some filters after d8
		do_action('cf7a_additional_spam_filters', $message, $submission, $spam);

		if ($options['autostore_bad_ip'] && $spam && !CF7ANTISPAM_DEBUG_EXTENDED) {
			if ( false === ($this->cf7a_ban_ip($remote_ip, $reason, round($spam_score) ) ) )
				error_log( CF7ANTISPAM_LOG_PREFIX . "unable to ban $remote_ip / CF7ANTISPAM_LOG_PREFIX enabled" );
		}

		if ($spam) {

			$submission->add_spam_log( array(
				'agent'  => 'CF7-AntiSpam',
				'reason' => cf7a_compress_array($reason),
			) );

		}

		return $spam; // case closed
	}

}
