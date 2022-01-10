<?php

class CF7_AntiSpam_filters {

	private $b8;

	/**
	 * CF7_AntiSpam_filters constructor.
	 */
	public function __construct() {
		$this->b8 = $this->cf7a_b8_init();

		add_action( 'cf7a_cron', array($this, 'cron_unban') );
	}


	/**
	 * CF7_AntiSpam_filters Tools
	 */
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
				if (substr( trim($line), 0, 9 ) === "flamingo_") {
					$matches = array();
					preg_match('/flamingo_(.*)(?=:): "\[(.*)]"/', $line , $matches);
					$additional_settings[$matches[1]] = $matches[2];
				}
			}

			return $additional_settings;
		}

		return false;
	}

	/**
	 * @param $id - a flamingo post object
	 *
	 * @return false|string
	 */
	private function cf7a_get_mail_content($flamingo_post_id) {

		$flamingo_post = new Flamingo_Inbound_Message( $flamingo_post_id );

		// get the form tax using the slug we find in the flamingo message
		$form = get_term_by( 'slug', $flamingo_post->channel, 'flamingo_inbound_channel' );

		// get the post where are stored the form data
		$form_post = get_page_by_path($form->slug, '', 'wpcf7_contact_form');

		// get the additional setting of the form
		$additional_settings = isset($form_post->ID) ? $this->cf7a_get_mail_additional_data($form_post->ID) : null;

		// if the message field was find return it
		if ( isset($additional_settings) && isset( $flamingo_post->fields[$additional_settings['message']] ) ) {
			return stripslashes( $flamingo_post->fields[ $additional_settings['message'] ] );
		}

		return false;

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

		if (empty($message)) return false;

		$time_elapsed = cf7a_microtimeFloat();

		$rating = $this->b8->classify( htmlspecialchars( $message, ENT_QUOTES, get_option( 'blog_charset' ) ) );

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
		if (!empty($message)) $this->b8->learn( htmlspecialchars( $message, ENT_QUOTES, get_option( 'blog_charset' ) ), b8\b8::SPAM );
	}

	public function cf7a_b8_unlearn_spam($message) {
		if (!empty($message)) $this->b8->unlearn( htmlspecialchars( $message, ENT_QUOTES, get_option( 'blog_charset' ) ), b8\b8::SPAM );
	}

	public function cf7a_b8_learn_ham($message) {
		if (!empty($message)) $this->b8->learn( htmlspecialchars( $message, ENT_QUOTES, get_option( 'blog_charset' ) ), b8\b8::HAM );
	}

	public function cf7a_b8_unlearn_ham($message) {
		if (!empty($message)) $this->b8->unlearn( htmlspecialchars( $message, ENT_QUOTES, get_option( 'blog_charset' ) ), b8\b8::HAM );
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

	public function cf7a_ban_by_ip($ip, $reason = array(), $spam_score = 1) {

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

	public function cron_unban() {
		global $wpdb;
		$rows_updated = $wpdb->query( "UPDATE {$wpdb->prefix}cf7a_blacklist SET `status` = `status` - 1 WHERE 1" );
		$unbanned = $wpdb->query( "DELETE FROM {$wpdb->prefix}cf7a_blacklist WHERE `status` =  0" );
		error_log( CF7ANTISPAM_LOG_PREFIX . "Unbanned $unbanned users (rows updated $rows_updated)" );
		return true;
	}


	// Database management Flamingo

	public function cf7a_clean_blacklist() {
		global $wpdb;
		$r = $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}cf7a_blacklist" );
		return !is_wp_error($r);
	}

	public function cf7a_reset_dictionary() {
		global $wpdb;
		$r = $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}cf7a_wordlist" );

		if (!is_wp_error($r)) {
			$wpdb->query( "INSERT INTO " . $wpdb->prefix . "cf7a_wordlist (`token`, `count_ham`) VALUES ('b8*dbversion', '3');" );
			$wpdb->query( "INSERT INTO " . $wpdb->prefix . "cf7a_wordlist (`token`, `count_ham`, `count_spam`) VALUES ('b8*texts', '0', '0');" );
			return true;
		}
		return false;
	}

	public static function cf7a_reset_b8_classification() {
		global $wpdb;
		$wpdb->query( "DELETE FROM " . $wpdb->prefix . "postmeta WHERE `meta_key` = '_cf7a_b8_classification'" );
	}

	public function cf7a_rebuild_dictionary() {
		$this->cf7a_reset_dictionary();
		$this->cf7a_reset_b8_classification();
		return $this->cf7a_flamingo_analyze_stored_mails();
	}

	public function cf7a_full_reset() {
		require_once CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-uninstall.php';
		CF7_AntiSpam_Uninstaller::uninstall(true);

		require_once CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-activator.php';
		CF7_AntiSpam_Activator::install();
		return true;
	}

	// CF7_AntiSpam_filters Flamingo

	public function cf7a_flamingo_on_install() {
		$this->cf7a_flamingo_analyze_stored_mails();
	}

	private function cf7a_flamingo_analyze_stored_mails() {

		// get all the flamingo inbound post and classify them
		$args = array(
			'post_type' => 'flamingo_inbound',
			'posts_per_page' => -1,
			'post_status' => array('publish', 'flamingo-spam')
		);

		$query = new WP_Query($args);

		if ($query->have_posts() ) :

			$post_storage = array();

			while ( $query->have_posts() ) : $query->the_post();

				$post_id = get_the_ID();
				$post_status = get_post_status();

				$message = $this->cf7a_get_mail_content($post_id);

				if (!empty($message)) {

					if ( $post_status == 'flamingo-spam' ) {
						$this->cf7a_b8_learn_spam( $message );
					} else if ( $post_status == 'publish' ) {
						$this->cf7a_b8_learn_ham( $message );
					}

					$post_storage[$post_id] = $message;

				} else {
					error_log( CF7ANTISPAM_LOG_PREFIX . "Flamingo post $post_id seems empty, so can't be analyzed" );
				}

			endwhile;

			// we need to teach to b8 what is spam or not before classify mails
			foreach ($post_storage as $post_id => $message) {
				update_post_meta( $post_id, '_cf7a_b8_classification', $this->cf7a_b8_classify($message) );
			}

        endif;

		return true;
	}

	public function cf7a_d8_flamingo_classify() {

		if ( !isset($_REQUEST['action'] ) || $_REQUEST['action'] !== 'spam' && $_REQUEST['action'] !== 'unspam' && $_REQUEST['action'] !== 'save' ) {
			return;
		}

		if ( $_REQUEST['action'] === 'save' && $_REQUEST['save'] === 'Update' ) {
			$action = $_REQUEST['inbound']['status'] == 'spam' ? 'spam' : 'ham'; // spam / ham
		} else if ($_REQUEST['action'] === 'spam' ) {
			$action = 'spam';
		} else if ($_REQUEST['action'] === 'unspam' ) {
			$action = 'ham';
		}

		if (isset($action)) {
			foreach ( (array) $_REQUEST['post'] as $post_id ) {

				// get the message from flamingo mail
				$message = $this->cf7a_get_mail_content($post_id);

				$flamingo_post = new Flamingo_Inbound_Message( $post_id );

				if (empty($message)) {

					update_post_meta( $flamingo_post->id(), '_cf7a_b8_classification', "none" );

					if (CF7ANTISPAM_DEBUG) error_log( CF7ANTISPAM_LOG_PREFIX . sprintf( __( "%s has no message text so can't be analyzed", 'cf7-antispam'), $post_id ) );

				} else {

					$rating = $this->cf7a_b8_classify($message);

					$options = get_option( 'cf7a_options' );

					if ( $action == 'spam' ) {

						$this->cf7a_b8_unlearn_ham($message);
						$this->cf7a_b8_learn_spam($message);

						if ($options['autostore_bad_ip']) $this->cf7a_ban_by_ip($flamingo_post->meta['remote_ip'], __("flamingo ban"));

					} else if ( $action == 'ham' ) {

						$this->cf7a_b8_unlearn_spam($message);
						$this->cf7a_b8_learn_ham($message);

						if ($options['autostore_bad_ip']) $this->cf7a_unban_by_ip($flamingo_post->meta['remote_ip'] );

					}

					$rating_after = $this->cf7a_b8_classify($message);

					update_post_meta( $flamingo_post->id(), '_cf7a_b8_classification', $rating_after );

					if (CF7ANTISPAM_DEBUG) error_log( CF7ANTISPAM_LOG_PREFIX . sprintf( __( "b8 has learned this e-mail from %s was %s - score before/after: %f/%f", 'cf7-antispam'),
							$flamingo_post->from_email,
							$action,
							$rating,
							$rating_after)
					);

				}
			}
		}
	}

	public function cf7a_resend_mail($mail_id) {

		$flamingo_data = new Flamingo_Inbound_Message($mail_id);

		// get the meta fields
		$flamingo_meta = get_post_meta( $mail_id, '_meta', true );

		// get form fields data
		$flamingo_fields = get_post_meta( $mail_id, '_fields', true );
		if ( ! empty( $flamingo_fields ) ) {
			foreach ( (array) $flamingo_fields as $key => $value ) {
				$meta_key = sanitize_key( '_field_' . $key );

				if ( metadata_exists( 'post', $mail_id, $meta_key ) ) {
					$value = get_post_meta( $mail_id, $meta_key, true );
					$flamingo_fields[$key] = $value;
				}
			}
		}

		if ( !$flamingo_meta['message_field'] || empty($flamingo_fields[$flamingo_meta['message_field']])) return true;

		$post_data = get_post($mail_id);

		// init mail
		$subject = $flamingo_meta['subject'];
		$sender = $flamingo_data->from;
		$body = $flamingo_fields[$flamingo_meta['message_field']];
		$recipient = $flamingo_meta['recipient'];

		$headers = "From: $sender\n";
		$headers .= "Content-Type: text/html\n";
		$headers .= "X-WPCF7-Content-Type: text/html\n";
		$headers .= "Reply-To: $sender <$recipient>\n";

		// $additional_headers = '';
		// if ( $additional_headers ) {
		// 	$headers .= implode("\n", $additional_headers);
		// }

		return wp_mail( $recipient, $subject, $body, $headers );
	}

	/**
	 * Using the id of the newly stored flamingo email set the classification meta to that post
	 * @param $result
	 */
	public function cf7a_flamingo_store_additional_data( $result ) {

		$submission = WPCF7_Submission::get_instance();

		if ( ! $submission
		     or ! $posted_data = $submission->get_posted_data() ) {
			return;
		}

		// get the contact form data mail data
		$cf = $submission->get_contact_form();

		// form additional settings
		$additional_settings = $this->cf7a_get_mail_additional_data($result['contact_form_id']);

		$additional_meta = array(
			"message_field" => $additional_settings['message_field'],
			"recipient" => wpcf7_mail_replace_tags($cf->prop('mail')['recipient']),
			"subject" => wpcf7_mail_replace_tags($cf->prop('mail')['subject']),
		);

		// update post meta in order to add cf7a customized data
		$stored_fields = get_post_meta($result[ 'flamingo_inbound_id'], '_meta', true);
		update_post_meta( $result['flamingo_inbound_id'], '_meta', array_merge($stored_fields, $additional_meta) );

		if ( !empty($additional_settings) && isset( $posted_data[$additional_settings['message']] ) ) {

			$text   = stripslashes( $posted_data[$additional_settings['message']] );
			$rating = $text != '' ? $this->cf7a_b8_classify( $text ) : "none";

			update_post_meta( $result['flamingo_inbound_id'], '_cf7a_b8_classification', $rating );

		}
	}


	// FLAMINGO CUSTOMIZATION

	public function flamingo_columns($columns) {
		return array_merge( $columns, array(
			'd8' => __( 'D8 classification', 'cf7-antispam' ),
			'resend' => __( 'CF7-AntiSpam actions', 'cf7-antispam' ),
		));
	}

	public function flamingo_d8_column( $column, $post_id ) {
		$classification = get_post_meta($post_id, '_cf7a_b8_classification', true);
		if ( 'd8' === $column ) {
			echo cf7a_formatRating( $classification );
		}
	}

	public function flamingo_resend_column( $column, $post_id ) {
		if ( 'resend' === $column ) {
			$url = wp_nonce_url( add_query_arg("action", "cf7a_resend_".$post_id , menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce'  );
			printf('<a class="button" href="%s" onclick="confirmationAlert(this)">%s</a>', $url , __('Resend Email', 'cf7-antispam') );
		}
	}


    public function cf7a_check_language_disallowed( $languages, $disalloweds, $alloweds = array() ) {

	    if (!is_array($languages)) $languages = array($languages);

        if ( ! empty( $alloweds ) ) {
            foreach ( $alloweds as $allowed ) {
                if ( in_array( $allowed, $languages ) ) return false;
            }
        }

        if ( ! empty( $disalloweds ) ) {
            foreach ( $disalloweds as $k => $disallowed ) {
                if ( in_array( $disallowed, $languages ) ) return $languages[ $k ];
            }
        }

        return false;
    }

    public function cf7a_log( $string, $log_level = 0 ) {
	    if (empty($string)) return true;
        if (is_array($string)) $string = implode(", " , $string);
        if ($log_level === 0 || $log_level == 1 && CF7ANTISPAM_DEBUG || $log_level == 2 && CF7ANTISPAM_DEBUG_EXTENDED ) error_log( CF7ANTISPAM_LOG_PREFIX . $string );
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
		$message = apply_filters('cf7a_message_before_processing', $message, $posted_data);

		// this plugin options
		$options = get_option( 'cf7a_options', array() );
		$prefix = sanitize_html_class($options['cf7a_customizations_prefix']);

		// the data of the user who sent this email
		// IP
		$real_remote_ip = isset( $_POST[ $prefix . 'address' ] ) ? cf7a_decrypt( sanitize_text_field( $_POST[ $prefix . 'address' ] ), $options['cf7a_cipher'] ) : null;
		$remote_ip = $real_remote_ip ? filter_var( $real_remote_ip, FILTER_VALIDATE_IP ) : null;
		$cf7_remote_ip = sanitize_text_field($submission->get_meta( 'remote_ip' ));

		// CF7A version
		$cf7a_version = isset( $_POST[ $prefix . 'version' ] ) ? cf7a_decrypt( sanitize_text_field( $_POST[ $prefix . 'version' ] ), $options['cf7a_cipher'] ) : null;

		// client referer
		$cf7a_referer = isset( $_POST[ $prefix . 'referer' ] ) ? cf7a_decrypt( sanitize_text_field( $_POST[ $prefix . 'referer' ] ), $options['cf7a_cipher'] ) : null;

		// CF7 user agent
		$user_agent = sanitize_text_field($submission->get_meta( 'user_agent' ));

		// Timestamp checks
		$timestamp                       = isset($_POST[$prefix.'_timestamp']) ? intval( cf7a_decrypt( sanitize_text_field($_POST[$prefix.'_timestamp']), $options['cf7a_cipher'] ) ) : 0;
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
		 * TODO: check coherence between $cf7_remote_ip and $remote_ip
		 */
		if ( !$remote_ip ) {

			$remote_ip = $cf7_remote_ip ?: null;

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

                $this->cf7a_log("The $remote_ip is already blacklisted, status $ip_data_status", 1);
			}
		}

		/**
		 * Check the CF7 AntiSpam version field
		 */
		if ( !$cf7a_version || $cf7a_version != CF7ANTISPAM_VERSION ) {

			$spam_score += $score_warn;
			$reason['data_mismatch'] = "Version mismatch '$cf7a_version' != '". CF7ANTISPAM_VERSION . "'";

			if (CF7ANTISPAM_DEBUG)
				error_log( CF7ANTISPAM_LOG_PREFIX . "Incorrect data submitted by $remote_ip in the hidden field _version, may have been modified, removed or hacked" );
		}

		/**
		 * Check the client http refer
		 * it is much more likely that it is a bot that lands on the page without a referrer than a human that pastes in the address bar the url of the contact form.
		 */
		if ( intval( $options['check_refer'] ) == 1 ) {
			if ( ! $cf7a_referer || $cf7a_referer == '' ) {

				$spam_score            += $score_warn;
				$reason['no_referrer'] = "client has referrer address";

				if ( CF7ANTISPAM_DEBUG ) {
					error_log( CF7ANTISPAM_LOG_PREFIX . "the $remote_ip has reached the contact form page without any referrer" );
				}
			}
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
					"screens"              => !empty( $_POST[$prefix.'screens'] ) ? sanitize_text_field( $_POST[$prefix.'screens'] ) : null,
					"hardware_concurrency" => !empty( $_POST[$prefix.'hardware_concurrency'] ) ? intval( $_POST[$prefix.'hardware_concurrency'] ) : null,
					"memory"               => !empty( $_POST[$prefix.'memory'] ) ? floatval( $_POST[$prefix.'memory'] ) : null,
					"user_agent"           => !empty( $_POST[$prefix.'user_agent'] ) ? sanitize_text_field( $_POST[$prefix.'user_agent'] ) : null,
					"app_version"          => !empty( $_POST[$prefix.'app_version'] ) ? sanitize_text_field( $_POST[$prefix.'app_version'] ) : null,
					"webdriver"            => !empty( $_POST[$prefix.'webdriver'] ) ? sanitize_text_field( $_POST[$prefix.'webdriver'] ) : null,
					"session_storage"      => !empty( $_POST[$prefix.'session_storage'] ) ? intval( $_POST[$prefix.'session_storage'] ) : null,
					"bot_fingerprint"      => !empty( $_POST[$prefix.'bot_fingerprint'] ) ? sanitize_text_field( $_POST[$prefix.'bot_fingerprint'] ) : null,
					"touch"                => !empty( $_POST[$prefix.'touch'] ) ? true : null,
				);

				$fails = array();
				if (!$bot_fingerprint["timezone"]) $fails[] = "timezone";
				if (!$bot_fingerprint["platform"]) $fails[] = "platform";
				if (!$bot_fingerprint["screens"]) $fails[] = "screens";
				if (!$bot_fingerprint["user_agent"]) $fails[] = "user_agent";
				if (!$bot_fingerprint["app_version"]) $fails[] = "app_version";
				if (!$bot_fingerprint["webdriver"]) $fails[] = "webdriver";
				if (!$bot_fingerprint["session_storage"]) $fails[] = "session_storage";
				if (strlen($bot_fingerprint["bot_fingerprint"]) != 5) $fails[] = "bot_fingerprint";

				// navigator hardware_concurrency isn't available under Ios - https://developer.mozilla.org/en-US/docs/Web/API/Navigator/hardwareConcurrency
				if (  empty( $_POST[ $prefix . 'isIos'] ) ) {
					// hardware concurrency need to be a integer > 1 to be valid
					if (!$bot_fingerprint["hardware_concurrency"] >= 1) $fails[] = "hardware_concurrency";
				} else {
					// but in ios isn't provided so we expect a null value
					if ($bot_fingerprint["hardware_concurrency"] !== null) $fails[] = "hardware_concurrency_Ios";
				}

				if ( !empty( $_POST[$prefix.'isIos'] ) || !empty( $_POST[$prefix.'isAndroid'] ) ) {
					if (!$bot_fingerprint["touch"]) $fails[] = "touch";
				}

				// navigator deviceMemory isn't available with Ios and firexfox  - https://developer.mozilla.org/en-US/docs/Web/API/Navigator/deviceMemory
				if ( empty( $_POST[$prefix.'isIos'] ) && empty( $_POST[$prefix.'isFFox'] ) ) {
					// memory need to be a float > 0.25 to be valid
					if ( !$bot_fingerprint["memory"] >= 0.25 )  $fails[] = "memory";
				} else {
					// but in ios and firefox isn't provided so we expect a null value
					if ( $bot_fingerprint["memory"] !== null )  $fails[] = "memory_Ios";
				}

				// increment the spam score if needed, then log the result
				if (!empty($fails)) {
					$spam_score                += count( $fails ) * $score_fingerprinting;
					$reason['bot_fingerprint'] = implode( ", ", $fails );

					if (CF7ANTISPAM_DEBUG)
						error_log( CF7ANTISPAM_LOG_PREFIX . "The $remote_ip ip hasn't passed " . count( $fails ) . " / " . count( $bot_fingerprint ) . " of the bot fingerprint test ({$reason['bot_fingerprint']})" );

					if (CF7ANTISPAM_DEBUG_EXTENDED) error_log( CF7ANTISPAM_LOG_PREFIX . print_r($bot_fingerprint, true) );
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

					if (CF7ANTISPAM_DEBUG)
						error_log( CF7ANTISPAM_LOG_PREFIX . "The $remote_ip ip hasn't passed ".count($fails)." / " . count( $bot_fingerprint_extras ) . " of the bot fingerprint extra test ({$reason['bot_fingerprint_extras']})" );

					if (CF7ANTISPAM_DEBUG_EXTENDED) error_log( CF7ANTISPAM_LOG_PREFIX . print_r($bot_fingerprint_extras, true) );

				}

			}

            /**
             * Bot fingerprints extras
             */
            if ( intval( $options['check_language'] ) == 1 ) {

                // Checks sender has a blacklisted ip address
                $languages_allowed = isset($options['languages']['allowed']) ? $options['languages']['allowed'] : array();
                $languages_disallowed = isset($options['languages']['disallowed']) ? $options['languages']['disallowed'] : array();

                $languages = array();
                $languages['browser_language'] = !empty( $_POST[ $prefix . 'browser_language' ] ) ? sanitize_text_field( $_POST[ $prefix . 'browser_language' ] ) : null;
                $languages['accept_language'] = isset( $_POST[ $prefix . '_language' ] ) ? cf7a_decrypt( sanitize_text_field( $_POST[ $prefix . '_language' ] ), $options['cf7a_cipher'] ) : null;

                if (empty($languages['browser_language'])) {
                    $fails[] = "missing browser language";
                } else {
                    $languages['browser'] = cf7a_get_browser_language_array($languages['browser_language']);
                }

                if (empty($languages['accept_language'])) {
                    $fails[] = "missing language field";
                } else {
                    $languages['accept'] = cf7a_get_accept_language_array($languages['accept_language']);
                }

                if ( !empty($languages['accept']) && !empty($languages['browser']) ) {

                    if ( !array_intersect($languages['browser'], $languages['accept']) ) {

                    	// checks if http accept language is the same of javascript navigator.languages
                        $fails[] = 'languages detected not coherent';

                    } else {

	                    // check if the language is allowed and if is disallowed
	                    $client_languages = array_unique( array_merge($languages['browser'], $languages['accept'] ) );
                        if ( false !== ($language_disallowed = $this->cf7a_check_language_disallowed( $client_languages, $languages_disallowed, $languages_allowed ) ) ) {
                            $fails[] = "language disallowed ($language_disallowed)";
                        }

                    }

                }

                if ( !empty( $fails ) ) {
                    $spam_score += $score_warn;
                    $reason['language'] = implode(", ", $fails);

                    $this->cf7a_log("The $remote_ip fails the languages checks - (".$reason['language'].")", 1);
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
							error_log( CF7ANTISPAM_LOG_PREFIX . "The $remote_ip ip took too little time to fill in the form - (now + timestamp = elapsed $time_now - $timestamp = $time_elapsed) < $submission_minimum_time_elapsed" );
					}

					/**
					 * Check if the time to submit the email il higher than expected
					 */
					if ( $time_elapsed > $submission_maximum_time_elapsed ) {

						$spam_score += $score_time;
						$reason['max_time_elapsed'] = $time_elapsed;

						if (CF7ANTISPAM_DEBUG)
							error_log( CF7ANTISPAM_LOG_PREFIX . "The $remote_ip ip took too much time to fill in the form - (now + timestamp = elapsed $time_now - $timestamp = $time_elapsed) > $submission_maximum_time_elapsed" );
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

				foreach ($options['dnsbl_list'] as $dnsbl) {
					if ( false !== ( $listed = $this->cf7a_check_dnsbl( $reverse_ip, $dnsbl ) ) ) {
						$reason['dsnbl'][] = $listed;
						$spam_score += $score_dnsbl;
					}
				}

				if (CF7ANTISPAM_DNSBL_BENCHMARK) {
					$performance_test = array();
					foreach ($options['dnsbl_list'] as $dnsbl) {
						$microtime = cf7a_microtimeFloat();
						$r = $this->cf7a_check_dnsbl( $reverse_ip, $dnsbl );
						$time_taken = round( cf7a_microtimeFloat() - $microtime, 5 );
						$performance_test[$dnsbl] = $time_taken;
					}

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
				error_log( CF7ANTISPAM_LOG_PREFIX . "$remote_ip will be rejected because suspected of spam! (score $spam_score / 1 - b8 rating $rating / 1)" );

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

		// if the autostore ip is enabled (but not exteded debug)
		if ($options['autostore_bad_ip'] && $spam && !CF7ANTISPAM_DEBUG_EXTENDED) {
			if ( false === $this->cf7a_ban_by_ip($remote_ip, $reason, round($spam_score) ) )
				error_log( CF7ANTISPAM_LOG_PREFIX . "unable to ban $remote_ip" );
		}

		// log the antispam result in extended debug mode
		if (CF7ANTISPAM_DEBUG_EXTENDED) {
			error_log( CF7ANTISPAM_LOG_PREFIX . "$remote_ip antispam results - " . cf7a_compress_array($reason) );
		}

		// combines all the reasons for banning in one string
		if ($spam) {
			$submission->add_spam_log( array(
				'agent'  => 'CF7-AntiSpam',
				'reason' => cf7a_compress_array($reason),
			) );
		}

		// case closed
		return $spam;
	}

}
