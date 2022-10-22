<?php

class CF7_AntiSpam_filters {

	/**
	 * The b8 class
	 *
	 * @var \b8\b8|null $b8 - the bayesian filter
	 * @description the b8 class
	 */
	private $b8;

	/**
	 * The geo-ip class
	 *
	 * @var CF7_Antispam_geoip $geoip the Geo-ip utility
	 * @description the geo-ip class
	 */
	private $geoip;

	/**
	 * CF7_AntiSpam_filters constructor.
	 */
	public function __construct() {

		$this->b8 = $this->cf7a_b8_init();

		$this->geoip = new CF7_Antispam_geoip();

		add_action( 'cf7a_cron', array( $this, 'cf7a_cron_unban' ) );
	}

	/* CF7_AntiSpam_filters Tools */

	/**
	 * It takes an IPv6 address and expands it to its full length
	 *
	 * @param string $ip The IP address to expand.
	 *
	 * @return string The IP address in hexadecimal format.
	 */
	public function cf7a_expand_ipv6( $ip ) {
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
	public function cf7a_reverse_ipv4( $ip ) {
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
	public function cf7a_reverse_ipv6( $ip ) {
		$ip = $this->cf7a_expand_ipv6( $ip );
		return implode( '.', str_split( strrev( str_replace( ':', '', $ip ) ) ) );
	}

	/**
	 * It checks the DNSBL for the IP address.
	 *
	 * @param string $reverse_ip The IP address in reverse order.
	 * @param string $dnsbl The DNSBL url to check against.
	 *
	 * @return bool the dnsbl if the checkdnsrr function returns true.
	 */
	public function cf7a_check_dnsbl( $reverse_ip, $dnsbl ) {

		if ( checkdnsrr( $reverse_ip . '.' . $dnsbl . '.', 'A' ) ) {
			return $dnsbl;
		}

		return false;
	}

	/**
	 * It takes the form ID as a parameter and returns an array of the additional settings for that form
	 *
	 * @param int $form_post_id The ID of the form post.
	 *
	 * @return array|false The additional settings of the form.
	 */
	public function cf7a_get_mail_additional_data( $form_post_id ) {

		/* get the additional setting of the form */
		$form_additional_settings = get_post_meta( $form_post_id, '_additional_settings', true );

		if ( ! empty( $form_additional_settings ) ) {
			$lines = explode( "\n", $form_additional_settings );

			$additional_settings = array();

			/* extract the flamingo_key = value; */
			foreach ( $lines as $line ) {
				if ( substr( trim( $line ), 0, 9 ) === 'flamingo_' ) {
					$matches = array();
					preg_match( '/flamingo_(.*)(?=:): "\[(.*)]"/', $line, $matches );
					$additional_settings[ $matches[1] ] = $matches[2];
				}
			}

			return $additional_settings;
		}

		return false;
	}

	/**
	 * It gets the message content from a Flamingo post
	 *
	 * @param int $flamingo_post_id - a flamingo post id.
	 *
	 * @return false|string The message field from the form.
	 */
	private function cf7a_get_mail_content( $flamingo_post_id ) {

		$flamingo_post = new Flamingo_Inbound_Message( $flamingo_post_id );

		/* get the form tax using the slug we find in the flamingo message */
		$form = get_term_by( 'slug', $flamingo_post->channel, 'flamingo_inbound_channel' );

		/* get the post where are stored the form data */
		$form_post = get_page_by_path( $form->slug, '', 'wpcf7_contact_form' );

		/* get the additional setting of the form */
		$additional_settings = isset( $form_post->ID ) ? $this->cf7a_get_mail_additional_data( $form_post->ID ) : null;

		/* if the message field was find return it */
		if ( ! empty( $additional_settings ) && $flamingo_post->fields[ $additional_settings['message'] ] ) {
			return stripslashes( $flamingo_post->fields[ $additional_settings['message'] ] );
		}

		return false;

	}


	/**
	 * CF7_AntiSpam_filters b8
	 */
	private function cf7a_b8_init() {
		/* the database */
		global $wpdb;

		$db         = explode( ':', DB_HOST );
		$db_address = $db[0];
		$db_port    = ! empty( $db[1] ) ? intval( $db[1] ) : 3306;

		/* B8 config */
		$mysql = new mysqli(
			$db_address,
			DB_USER,
			DB_PASSWORD,
			DB_NAME,
			$db_port
		);

		$config_b8      = array( 'storage' => 'mysql' );
		$config_storage = array(
			'resource' => $mysql,
			'table'    => $wpdb->prefix . 'cf7a_wordlist',
		);

		/* We use the default lexer settings */
		$config_lexer = array();

		/* We use the default degenerator configuration */
		$config_degenerator = array();

		/* Include the b8 code */
		require_once CF7ANTISPAM_PLUGIN_DIR . '/libs/b8/b8.php';

		/* Create a new b8 instance */
		try {
			return new b8\b8( $config_b8, $config_storage, $config_lexer, $config_degenerator );
		} catch ( Exception $e ) {
			cf7a_log( 'error message: ' . $e->getMessage() );
			exit();
		}
	}

	/**
	 * It takes a string, passes it to the b8 classifier, and returns the result
	 *
	 * @param string $message The message to be classified.
	 *
	 * @return float The rating of the message.
	 */
	public function cf7a_b8_classify( $message ) {

		if ( empty( $message ) ) {
			return false;
		}

		$time_elapsed = cf7a_microtime_float();

		$charset = get_option( 'blog_charset' );

		$rating = $this->b8->classify( htmlspecialchars( $message, ENT_QUOTES, $charset ) );

		if ( CF7ANTISPAM_DEBUG_EXTENDED ) {
			cf7a_log( 'd8 email classification: ' . $rating );

			$mem_used      = round( memory_get_usage() / 1048576, 5 );
			$peak_mem_used = round( memory_get_peak_usage() / 1048576, 5 );
			$time_taken    = round( cf7a_microtime_float() - $time_elapsed, 5 );

			cf7a_log( "stats : Memory: $mem_used - Peak memory: $peak_mem_used - Time Elapsed: $time_taken" );
		}

		return $rating;
	}

	/**
	 * It takes the message from the contact form, converts it to HTML, and then sends it to the b8 class to be learned as
	 * spam
	 *
	 * @param string $message The message to learn as spam.
	 */
	public function cf7a_b8_learn_spam( $message ) {
		if ( ! empty( $message ) ) {
			$this->b8->learn( htmlspecialchars( $message, ENT_QUOTES, get_option( 'blog_charset' ) ), b8\b8::SPAM );
		}
	}

	/**
	 * It takes the message from the contact form, converts it to HTML, and then unlearns it as spam
	 *
	 * @param string $message The message to unlearn.
	 */
	public function cf7a_b8_unlearn_spam( $message ) {
		if ( ! empty( $message ) ) {
			$this->b8->unlearn( htmlspecialchars( $message, ENT_QUOTES, get_option( 'blog_charset' ) ), b8\b8::SPAM );
		}
	}

	/**
	 * It takes a message, converts it to HTML entities, and then learns it as ham
	 *
	 * @param string $message The message to learn as ham.
	 */
	public function cf7a_b8_learn_ham( $message ) {
		if ( ! empty( $message ) ) {
			$this->b8->learn( htmlspecialchars( $message, ENT_QUOTES, get_option( 'blog_charset' ) ), b8\b8::HAM );
		}
	}

	/**
	 * It takes the message from the contact form, converts it to HTML entities, and then unlearns it as ham
	 *
	 * @param string $message The message to unlearn.
	 */
	public function cf7a_b8_unlearn_ham( $message ) {
		if ( ! empty( $message ) ) {
			$this->b8->unlearn( htmlspecialchars( $message, ENT_QUOTES, get_option( 'blog_charset' ) ), b8\b8::HAM );
		}
	}

	/**
	 * CF7_AntiSpam_filters blacklists
	 */

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
		return false;
	}

	/**
	 * It adds an IP address to the blacklist.
	 *
	 * @param string $ip The IP address to ban.
	 * @param array  $reason The reason why the IP is being banned.
	 * @param int    $spam_score This is the number of points that will be added to the IP's spam score.
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
					'status' => isset( $ip_row->status ) ? intval( $ip_row->status ) + intval( $spam_score ) : 1,
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

	/* Database management Flamingo */

	/**
	 * It deletes all the blacklisted ip
	 *
	 * @return bool - The result of the query.
	 */
	public function cf7a_clean_blacklist() {
		global $wpdb;
		$r = $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}cf7a_blacklist" );
		return ! is_wp_error( $r );
	}

	/**
	 * It resets the database table that stores the spam and ham words
	 *
	 * @return bool - The result of the query.
	 */
	public function cf7a_reset_dictionary() {
		global $wpdb;
		$r = $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}cf7a_wordlist" );

		if ( ! is_wp_error( $r ) ) {
			$wpdb->query( 'INSERT INTO ' . $wpdb->prefix . "cf7a_wordlist (`token`, `count_ham`) VALUES ('b8*dbversion', '3');" );
			$wpdb->query( 'INSERT INTO ' . $wpdb->prefix . "cf7a_wordlist (`token`, `count_ham`, `count_spam`) VALUES ('b8*texts', '0', '0');" );
			return true;
		}
		return false;
	}

	/**
	 * It deletes all the _cf7a_b8_classification metadata from the database
	 */
	public static function cf7a_reset_b8_classification() {
		global $wpdb;
		$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . "postmeta WHERE `meta_key` = '_cf7a_b8_classification'" );
	}

	/**
	 * It resets the dictionary and classification, then analyzes all the stored mails
	 *
	 * @return bool - The return value is the number of mails that were analyzed.
	 */
	public function cf7a_rebuild_dictionary() {
		$this->cf7a_reset_dictionary();
		$this->cf7a_reset_b8_classification();
		return $this->cf7a_flamingo_analyze_stored_mails();
	}

	/**
	 * It uninstalls the plugin, then reinstall it
	 */
	public function cf7a_full_reset() {
		require_once CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-uninstall.php';
		CF7_AntiSpam_Uninstaller::uninstall( true );

		require_once CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-activator.php';
		CF7_AntiSpam_Activator::install();

		return true;
	}

	/* CF7_AntiSpam_filters Flamingo */

	/**
	 * It checks the database for any stored emails that have been sent by Contact Form 7, and if it finds any, it adds them
	 * to the Flamingo database
	 */
	public function cf7a_flamingo_on_install() {
		$this->cf7a_flamingo_analyze_stored_mails();
	}

	/**
	 * It gets all the Flamingo inbound posts, and for each one, it gets the content of the post, and then it uses the b8
	 * classifier to classify the content as spam or ham
	 */
	private function cf7a_flamingo_analyze_stored_mails() {

		/* get all the flamingo inbound post and classify them */
		$args = array(
			'post_type'      => 'flamingo_inbound',
			'posts_per_page' => -1,
			'post_status'    => array( 'publish', 'flamingo-spam' ),
		);

		$query = new WP_Query( $args );

		while ( $query->have_posts() ) :
			$query->the_post();

			$post_id     = get_the_ID();
			$post_status = get_post_status();

			$message = $this->cf7a_get_mail_content( $post_id );

			if ( ! empty( $message ) ) {

				if ( 'flamingo-spam' === $post_status ) {
					$this->cf7a_b8_learn_spam( $message );
				} elseif ( 'publish' === $post_status ) {
					$this->cf7a_b8_learn_ham( $message );
				}

				update_post_meta( $post_id, '_cf7a_b8_classification', $this->cf7a_b8_classify( $message ) );

			} else {
				cf7a_log( "Flamingo post $post_id seems empty, so can't be analyzed" );
			}

		endwhile;

		return true;
	}

	/**
	 * It adds a column to the Flamingo spam folder, and when you mark a message as spam or ham, it learns from it
	 */
	public function cf7a_d8_flamingo_classify() {
		$req_action = isset( $_REQUEST['action'] ) ? esc_attr( $_REQUEST['action'] ) : false;
		$req_save   = isset( $_REQUEST['save'] ) ? esc_attr( $_REQUEST['save'] ) : false;
		$req_status = isset( $_REQUEST['inbound']['status'] ) ? esc_attr( $_REQUEST['inbound']['status'] ) : false;

		if ( $req_action && ( 'spam' === $req_action || 'unspam' === $req_action || 'save' === $req_action ) ) {

			if ( 'save' === $req_action && 'Update' === $req_save ) {
				$action = 'spam' === $req_status ? 'spam' : 'ham';
			} elseif ( 'spam' === $req_action ) {
				$action = 'spam';
			} elseif ( 'unspam' === $req_action ) {
				$action = 'ham';
			}

			if ( isset( $action ) ) {
				foreach ( (array) $_REQUEST['post'] as $post_id ) {

					/* get the message from flamingo mail */
					$message = $this->cf7a_get_mail_content( $post_id );

					$flamingo_post = new Flamingo_Inbound_Message( $post_id );

					if ( empty( $message ) ) {

						update_post_meta( $flamingo_post->id(), '_cf7a_b8_classification', 'none' );

						if ( CF7ANTISPAM_DEBUG ) {
							/* translators: %s - the post id. */
							cf7a_log( sprintf( __( "%s has no message text so can't be analyzed", 'cf7-antispam' ), $post_id ) );
						}
					} else {

						$rating = $this->cf7a_b8_classify( $message );

						$options = get_option( 'cf7a_options' );

						if ( 'spam' === $action ) {

							$this->cf7a_b8_unlearn_ham( $message );
							$this->cf7a_b8_learn_spam( $message );

							if ( $options['autostore_bad_ip'] ) {
								$this->cf7a_ban_by_ip( $flamingo_post->meta['remote_ip'], __( 'flamingo ban' ) );
							}
						} elseif ( 'ham' === $action ) {

							$this->cf7a_b8_unlearn_spam( $message );
							$this->cf7a_b8_learn_ham( $message );

							if ( $options['autostore_bad_ip'] ) {
								$this->cf7a_unban_by_ip( $flamingo_post->meta['remote_ip'] );
							}
						}

						$rating_after = $this->cf7a_b8_classify( $message );

						update_post_meta( $flamingo_post->id(), '_cf7a_b8_classification', $rating_after );

						cf7a_log(
							CF7ANTISPAM_LOG_PREFIX . sprintf(
								/* translators: %1$s is the mail "from" field (the sender). %2$s spam/ham. %3$s and %4$s the rating of the processed email */
								__( 'b8 has learned this e-mail from %1$s was %2$s - score before/after: %3$f/%4$f', 'cf7-antispam' ),
								$flamingo_post->from_email,
								$action,
								$rating,
								$rating_after
							),
							1
						);
					}
				}
			}
		}
	}

	/**
	 * It takes a Flamingo mail ID, gets the data from the Flamingo database, and then resends the email.
	 *
	 * @param int $mail_id The ID of the mail to resend.
	 *
	 * @return bool|mixed|null
	 */
	public function cf7a_resend_mail( $mail_id ) {

		$flamingo_data = new Flamingo_Inbound_Message( $mail_id );

		/* get the meta fields */
		$flamingo_meta = get_post_meta( $mail_id, '_meta', true );

		/* get form fields data */
		$flamingo_fields = get_post_meta( $mail_id, '_fields', true );
		if ( ! empty( $flamingo_fields ) ) {
			foreach ( (array) $flamingo_fields as $key => $value ) {
				$meta_key = sanitize_key( '_field_' . $key );

				if ( metadata_exists( 'post', $mail_id, $meta_key ) ) {
					$value                   = get_post_meta( $mail_id, $meta_key, true );
					$flamingo_fields[ $key ] = $value;
				}
			}
		}

		if ( ! $flamingo_meta['message_field'] || empty( $flamingo_fields[ $flamingo_meta['message_field'] ] ) ) {
			return true;
		}

		$post_data = get_post( $mail_id );

		/* init mail */
		$subject   = $flamingo_meta['subject'];
		$sender    = $flamingo_data->from;
		$body      = $flamingo_fields[ $flamingo_meta['message_field'] ];
		$recipient = $flamingo_meta['recipient'];

		$headers  = "From: $sender\n";
		$headers .= "Content-Type: text/html\n";
		$headers .= "X-WPCF7-Content-Type: text/html\n";
		$headers .= "Reply-To: $sender <$recipient>\n";

		return wp_mail( $recipient, $subject, $body, $headers );
	}

	/**
	 * Using the id of the newly stored flamingo email set the classification meta to that post
	 *
	 * @param array $result - The mail data.
	 *
	 * @return bool|void
	 */
	public function cf7a_flamingo_store_additional_data( $result ) {

		$submission = WPCF7_Submission::get_instance();

		if ( ! $submission ) {
			return true;
		}

		$posted_data = $submission->get_posted_data();

		/* get the contact form data mail data */
		$cf = $submission->get_contact_form();

		/* form additional settings */
		$additional_settings = $this->cf7a_get_mail_additional_data( $result['contact_form_id'] );

		$additional_meta = array(
			'message_field' => $additional_settings['message'],
			'recipient'     => wpcf7_mail_replace_tags( $cf->prop( 'mail' )['recipient'] ),
			'subject'       => wpcf7_mail_replace_tags( $cf->prop( 'mail' )['subject'] ),
		);

		/* update post meta in order to add cf7a customized data */
		$stored_fields = get_post_meta( $result['flamingo_inbound_id'], '_meta', true );
		update_post_meta( $result['flamingo_inbound_id'], '_meta', array_merge( $stored_fields, $additional_meta ) );

		if ( ! empty( $additional_settings ) && isset( $posted_data[ $additional_settings['message'] ] ) ) {

			$text   = stripslashes( $posted_data[ $additional_settings['message'] ] );
			$rating = ! empty( $text ) ? $this->cf7a_b8_classify( $text ) : 'none';

			update_post_meta( $result['flamingo_inbound_id'], '_cf7a_b8_classification', round( $rating, 2 ) );

		}
	}

	/**
	 * It removes the honeypot field from the Flamingo database
	 *
	 * @param array $result The result of the submission.
	 *
	 * @return bool|int
	 */
	public function cf7a_flamingo_remove_honeypot( $result ) {

		$options = get_option( 'cf7a_options', array() );

		if ( isset( $options['check_honeypot'] ) && intval( $options['check_honeypot'] ) === 1 ) {

			$submission             = WPCF7_Submission::get_instance();
			$honeypot_default_names = get_honeypot_input_names( $options['honeypot_input_names'] );

			if ( ! $submission ) {
				return true;
			}

			$posted_data = $submission->get_posted_data();

			$fields = array();

			foreach ( $posted_data as $key => $field ) {

				/* if a honeypot field was found into posted data delete it */
				if ( in_array( $key, $honeypot_default_names, true ) && empty( $field ) ) {
					delete_post_meta( $result['flamingo_inbound_id'], '_field_' . $key );
				} else {
					$fields[ $key ] = null;
				}
			}

			return update_post_meta( $result['flamingo_inbound_id'], '_fields', $fields );
		}

		return true;
	}


	/**
	 * FLAMINGO CUSTOMIZATIONS
	 *
	 * It adds two columns to the Flamingo plugin.
	 *
	 * @param array $columns The original array of columns.
	 *
	 * @return array The new columns set for flamingo inbound page
	 */
	public function flamingo_columns( $columns ) {
		return array_merge(
			$columns,
			array(
				'd8'     => __( 'D8 classification', 'cf7-antispam' ),
				'resend' => __( 'CF7-AntiSpam actions', 'cf7-antispam' ),
			)
		);
	}

	/**
	 * It adds a new column to the Flamingo Inbound Messages list table, and populates it with the value of the custom field
	 * `_cf7a_b8_classification`
	 *
	 * @param string $column The name of the column to display.
	 * @param int    $post_id The post ID of the post being displayed.
	 */
	public function flamingo_d8_column( $column, $post_id ) {
		$classification = get_post_meta( $post_id, '_cf7a_b8_classification', true );
		if ( 'd8' === $column ) {
			echo wp_kses(
				cf7a_format_rating( floatval( $classification ) ),
				array(
					'span' => array(
						'class' => true,
						'style' => true,
					),
					'b'    => array(),
				)
			);
		}
	}

	/**
	 * It adds a new column to the CF7 admin page, and adds a button to each row of the table
	 *
	 * @param string $column The name of the column.
	 * @param int    $post_id The post ID of the post being displayed.
	 */
	public function flamingo_resend_column( $column, $post_id ) {
		if ( 'resend' === $column ) {
			$url = wp_nonce_url( add_query_arg( 'action', 'cf7a_resend_' . $post_id, menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce' );
			printf(
				'<a class="button cf7a_alert" data-href="%s" data-message="%s">%s</a>',
				esc_url_raw( $url ),
				esc_html__( 'Are you sure?', 'cf7-antispam' ),
				esc_html__( 'Resend Email', 'cf7-antispam' )
			);
		}
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
	 * CF7_AntiSpam_filters The antispam filter
	 *
	 * @param bool $spam - spam or not.
	 *
	 * @return bool
	 */
	public function cf7a_spam_filter( $spam ) {

		/* Get the submitted data */
		$submission = WPCF7_Submission::get_instance();

		if ( ! $submission ) {
			return true;
		}

		$posted_data = $submission->get_posted_data();

		/* Get the contact form additional data */
		$contact_form = $submission->get_contact_form();

		/* get the tag used in the form */
		$mail_tags = $contact_form->scan_form_tags();

		/* the email and the message from the email */
		$email_tag   = substr( $contact_form->pref( 'flamingo_email' ), 2, -2 );
		$message_tag = substr( $contact_form->pref( 'flamingo_message' ), 2, -2 );

		$email   = isset( $posted_data[ $email_tag ] ) ? $posted_data[ $email_tag ] : false;
		$message = isset( $posted_data[ $message_tag ] ) ? $posted_data[ $message_tag ] : false;

		/* let developers hack the message */
		$message = apply_filters( 'cf7a_message_before_processing', $message, $posted_data );

		/* this plugin options */
		$options = get_option( 'cf7a_options', array() );
		$prefix  = sanitize_html_class( $options['cf7a_customizations_prefix'] );

		/* The data of the user who sent this email */

		/* IP */
		$real_remote_ip = isset( $_POST[ $prefix . 'address' ] ) ? cf7a_decrypt( sanitize_text_field( $_POST[ $prefix . 'address' ] ), $options['cf7a_cipher'] ) : false;
		$remote_ip      = $real_remote_ip ? filter_var( $real_remote_ip, FILTER_VALIDATE_IP ) : false;
		$cf7_remote_ip  = sanitize_text_field( $submission->get_meta( 'remote_ip' ) );

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

		/* b8 threshold */
		$b8_threshold = floatval( $options['b8_threshold'] );
		$b8_threshold = $b8_threshold > 0 && $b8_threshold < 1 ? $b8_threshold : 1;

		/* Scoring */

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
		 * Checking if the IP address is empty. If it is empty, it will add a score of 10 to the spam score and add a reason to
		* the reason array.
		*/
		if ( ! $remote_ip ) {

			$remote_ip = isset( $cf7_remote_ip ) ? $cf7_remote_ip : null;

			$spam_score     += $score_detection;
			$reason['no_ip'] = 'Address field empty';

			if ( CF7ANTISPAM_DEBUG ) {
				cf7a_log( "ip address field of $remote_ip is empty, this means it has been modified, removed or hacked! (used php data to get the real ip)" );
			}
		}

		/* Checking if the IP address was already blacklisted - no mercy :) */
		if ( $remote_ip && $options['max_attempts'] ) {

			$ip_data        = self::cf7a_blacklist_get_ip( $remote_ip );
			$ip_data_status = isset( $ip_data->status ) ? intval( $ip_data->status ) : 0;

			if ( $ip_data_status >= $options['max_attempts'] ) {

				$spam_score           += $score_detection;
				$reason['blacklisted'] = 'Score: ' . ( $ip_data_status + $spam_score );

				cf7a_log( "The $remote_ip is already blacklisted, status $ip_data_status", 1 );
			}
		}

		if ( intval( $options['check_honeyform'] ) !== 0 ) {

			$form_class = sanitize_html_class( $options['cf7a_customizations_class'] );

			/* get the "marker" field */
			if ( ! empty( $_POST[ '_wpcf7_' . $form_class ] ) ) {
				$spam_score               += $score_detection;
				$reason['bot_fingerprint'] = 'honeyform';
			}
		}

		/**
		 * If the mail was marked as spam no more checks are needed.
		 * This will save server computing power, this ip has already been banned so there's no reason for further processing
		 */
		if ( $spam_score < 1 ) {

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

				if ( empty( $languages['browser_language'] ) ) {
					$fails[] = 'missing browser language';
				} else {
					$languages['browser'] = cf7a_get_browser_language_array( $languages['browser_language'] );
				}

				if ( empty( $languages['accept_language'] ) ) {
					$fails[] = 'missing language field';
				} else {
					$languages['accept'] = cf7a_get_accept_language_array( $languages['accept_language'] );
				}

				if ( ! empty( $languages['accept'] ) && ! empty( $languages['browser'] ) ) {

					if ( ! array_intersect( $languages['browser'], $languages['accept'] ) ) {

						/* checks if http accept language is the same of javascript navigator.languages */
						$fails[] = 'languages detected not coherent (' . implode( '-', $languages['browser'] ) . ' vs ' . implode( '-', $languages['accept'] ) . ')';

					} else {

						/* check if the language is allowed and if is disallowed */
						$client_languages = array_unique( array_merge( $languages['browser'], $languages['accept'] ) );

						$language_disallowed = $this->cf7a_check_language_allowed( $client_languages, $languages_disallowed, $languages_allowed );

						if ( false === $language_disallowed ) {
							$spam_score        += $score_detection;
							$reason['language'] = implode( ', ', $client_languages );
						}
					}
				}

				if ( $this->geoip && intval( $options['check_geoip'] ) === 1 ) {

					try {
						$geoip_data = $this->geoip->cf7a_geoip_check_ip( $remote_ip );
						$geo_data   = array( strtolower( $geoip_data['continent'] ), strtolower( $geoip_data['country'] ) );

						$country_disallowed = $this->cf7a_check_language_allowed( $geo_data, $languages_disallowed, $languages_allowed );

						if ( empty( $geoip_data['error'] ) && false === $country_disallowed ) {
							$reason['geo'] = 'GEOIP:' . $geoip_data['continent'] . ',' . $geoip_data['country'];
							$fails[]       = "GeoIP country disallowed ($country_disallowed)";
						}
					} catch ( Exception $e ) {
						cf7a_log( "unable to check geoip for $remote_ip - " . $e->getMessage(), 1 );
					}
				}

				if ( ! empty( $fails ) ) {
					$spam_score        += $score_warn;
					$reason['language'] = implode( ', ', $fails );

					cf7a_log( "The $remote_ip fails the languages checks - (" . $reason['language'] . ')', 1 );
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

						cf7a_log( "The $remote_ip ip took too little time to fill in the form - (now + timestamp = elapsed $time_now - $timestamp = $time_elapsed) < $submission_minimum_time_elapsed", 1 );
					}

					/**
					 * Check if the time to submit the email il higher than expected
					 */
					if ( $time_elapsed > $submission_maximum_time_elapsed ) {

						$spam_score                += $score_time;
						$reason['max_time_elapsed'] = $time_elapsed;

						if ( CF7ANTISPAM_DEBUG ) {
							cf7a_log( "The $remote_ip ip took too much time to fill in the form - (now + timestamp = elapsed $time_now - $timestamp = $time_elapsed) > $submission_maximum_time_elapsed" );
						}
					}
				}
			}

			/**
			 * Checks if the emails IP is filtered by user
			 */
			if ( intval( $options['check_bad_ip'] ) === 1 ) {

				foreach ( $bad_ip_list as $bad_ip ) {

					if ( false !== stripos( $remote_ip, $bad_ip ) ) {

						$bad_ip = filter_var( $bad_ip, FILTER_VALIDATE_IP );

						$spam_score    += $score_bad_string;
						$reason['ip'][] = $bad_ip;

					}
				}

				if ( isset( $reason['ip'] ) ) {
					$reason['ip'] = implode( ', ', $reason['ip'] );

					if ( CF7ANTISPAM_DEBUG ) {
						cf7a_log( "The ip address $remote_ip is listed into bad ip list (contains {$reason['ip']})" );
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
						$reason['email_blackilisted'][] = $email;
					}
				}

				if ( isset( $reason['email_blackilisted'] ) ) {

					$reason['email_blackilisted'] = implode( ',', $reason['email_blackilisted'] );

					cf7a_log( "The ip address $remote_ip  sent a mail from {$email} but contains {$reason['email_blackilisted']} (blacklisted email string)", 1 );
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
							$spam_score            += $score_bad_string;
							$reason['user_agent'][] = $bad_user_agent;
						}
					}

					if ( ! empty( $reason['user_agent'] ) ) {
						cf7a_log( "The $remote_ip ip user agent was listed into bad user agent list - $user_agent contains " . implode( ', ', $reason['user_agent'] ), 1 );
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

					cf7a_log( "$remote_ip has bad word in message - " . implode( ', ', $bad_words ), 1 );
				}
			}

			/**
			 * Check the remote ip if is listed into Domain Name System Blacklists
			 * DNS blacklist are spam blocking DNS like lists that allow to block messages from specific systems that have a history of sending spam
			 * inspiration taken from https://gist.github.com/tbreuss/74da96ff5f976ce770e6628badbd7dfc
			 *
			 * TODO: enhance the performance using curl or threading. break after threshold reached
			 */
			if ( intval( $options['check_dnsbl'] ) === 1 && $remote_ip ) {

				$reverse_ip = '';

				if ( filter_var( $remote_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {

					$reverse_ip = $this->cf7a_reverse_ipv4( $remote_ip );

				} elseif ( filter_var( $remote_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {

					$reverse_ip = $this->cf7a_reverse_ipv6( $remote_ip );
				}

				foreach ( $options['dnsbl_list'] as $dnsbl ) {
					$listed = $this->cf7a_check_dnsbl( $reverse_ip, $dnsbl );

					if ( false !== $listed ) {
						$reason['dsnbl'][] = $listed;
						$spam_score       += $score_dnsbl;
					}
				}

				if ( CF7ANTISPAM_DNSBL_BENCHMARK ) {
					$performance_test = array();
					foreach ( $options['dnsbl_list'] as $dnsbl ) {
						if ( $this->cf7a_check_dnsbl( $reverse_ip, $dnsbl ) ) {
							$microtime                  = cf7a_microtime_float();
							$time_taken                 = round( cf7a_microtime_float() - $microtime, 5 );
							$performance_test[ $dnsbl ] = $time_taken;
						}
					}

					cf7a_log( 'DNSBL performance test' );
					cf7a_log( $performance_test );
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

					$mail_tag_count = count( $mail_tag_text );
					for ( $i = 0; $i < $mail_tag_count; $i ++ ) {

						$input_name = isset( $_POST[ $input_names[ $i ] ] ) && sanitize_text_field( wp_unslash( $_POST[ $input_names[ $i ] ] ) );

						/* check only if it's set and if it is different from "" */
						if ( $input_name ) {
							$spam_score          += $score_honeypot;
							$reason['honeypot'][] = $input_names[ $i ];
						}
					}

					if ( isset( $reason['honeypot'] ) ) {
						$reason['honeypot'] = implode( ', ', $reason['honeypot'] );

						cf7a_log( "The $remote_ip has filled the input honeypot {$reason['honeypot']}", 1 );
					}
				}
			}
		}

		/* hook to add some filters before d8 */
		do_action( 'cf7a_before_b8', $message, $submission, $spam );

		/**
		 * B8 is a statistical "Bayesian" spam filter
		 * https://nasauber.de/opensource/b8/
		 */
		if ( $options['enable_b8'] && $message && ! isset( $reason['blacklisted'] ) ) {

			$text = stripslashes( $message );

			$rating = $this->cf7a_b8_classify( $text );

			if ( $spam_score >= 1 || $rating >= $b8_threshold ) {

				$spam = true;
				cf7a_log( "$remote_ip will be rejected because suspected of spam! (score $spam_score / 1 - b8 rating $rating / 1)" );

				$this->cf7a_b8_learn_spam( $text );

				if ( $rating > $b8_threshold ) {

					$reason['b8'] = $rating;

					cf7a_log( "D8 detect spamminess of $rating while the minimum is > $b8_threshold so the mail from $remote_ip will be marked as spam", 1 );

				}
			} elseif ( $rating < $b8_threshold * .5 ) {

				/* the mail was classified as ham, so we let learn to d8 what is considered (a probable) ham */
				$this->cf7a_b8_learn_ham( $text );

				cf7a_log( "D8 detect spamminess of $rating (below the half of the threshold of $b8_threshold) so the mail from $remote_ip will be marked as ham", 1 );

			}
		} else {

			$spam = $spam_score >= 1;

			if ( $spam ) {

				/* if d8 isn't enabled we only need to mark as spam and leave a log */
				cf7a_log( "$remote_ip will be rejected because suspected of spam! (score $spam_score / 1)", 1 );

			}
		}

		/* hook to add some filters after d8 */
		do_action( 'cf7a_additional_spam_filters', $message, $submission, $spam );

		/* if the auto-store ip is enabled (and NOT in extended debug mode) */
		if ( $options['autostore_bad_ip'] && $spam ) {
			if ( false === $this->cf7a_ban_by_ip( $remote_ip, $reason, round( $spam_score ) ) ) {
				cf7a_log( "unable to ban $remote_ip" );
			}
		} else {

		}

		/* log the antispam result in extended debug mode */
		cf7a_log( "$remote_ip antispam results - " . cf7a_compress_array( $reason ), 2 );

		/* combines all the reasons for banning in one string */
		if ( $spam ) {
			$submission->add_spam_log(
				array(
					'agent'  => 'CF7-AntiSpam',
					'reason' => cf7a_compress_array( $reason ),
				)
			);
		}

		/* case closed */
		return $spam;
	}

}
