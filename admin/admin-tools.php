<?php

/**
 *  It creates a class called CF7_AntiSpam_Admin_Tools.
 */
class CF7_AntiSpam_Admin_Tools {

	/**
	 * The options of this plugin.
	 *
	 * @since    0.1.0
	 * @access   public
	 * @var      array    $options    options of this plugin.
	 */
	private $options;


	/**
	 * The GeoIP2 reader
	 *
	 * @since    0.3.1
	 * @access   private
	 * @var      GeoIp2\Database\Reader|false    $reader    the GeoIP class
	 */
	private $geoip;

	/**
	 * The class that handles the for frontend antispam functionalities
	 */
	public function __construct() {
		$this->options = CF7_AntiSpam::get_options();

		$this->geoip = new CF7_Antispam_Geoip();

		$this->geoip->cf7a_geo_maybe_download();
	}

	/**
	 * It sets a transient with the name of `cf7a_notice` and the value of the notice
	 *
	 * @param string  $message The message you want to display.
	 * @param string  $type error, warning, success, info.
	 * @param boolean $dismissible when the notice need the close button.
	 */
	public static function cf7a_push_notice( $message = 'generic', $type = 'error', $dismissible = true ) {
		$class  = "notice notice-$type";
		$class .= $dismissible ? ' is-dismissible' : '';
		$notice = sprintf( '<div class="%s"><p>%s</p></div>', esc_attr( $class ), esc_html( $message ) );
		set_transient( 'cf7a_notice', $notice );
	}

	/**
	 * It handles the actions that are triggered by the user
	 */
	public static function cf7a_handle_actions() {

		$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : false;
		$url    = esc_url( menu_page_url( 'cf7-antispam', false ) );

		if ( 'dismiss-banner' === $action ) {
			if ( get_user_meta( get_current_user_id(), 'cf7a_hide_welcome_panel_on', true ) ) {
				update_user_meta( get_current_user_id(), 'cf7a_hide_welcome_panel_on', true );
			} else {
				add_user_meta( get_current_user_id(), 'cf7a_hide_welcome_panel_on', true, true );
			}

			wp_safe_redirect( $url );
			exit();
		}

		$req_nonce = isset( $_REQUEST['cf7a-nonce'] ) ? wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['cf7a-nonce'] ) ), 'cf7a-nonce' ) : null;

		if ( $req_nonce ) {

			/* Ban a single ID (related to ip) */
			if ( substr( $action, 0, 6 ) === 'unban_' ) {

				$unban_id = intval( substr( $action, 6 ) );

				$filter = new CF7_AntiSpam_Filters();

				$r = $filter->cf7a_unban_by_id( $unban_id );

				if ( $r ) {
					/* translators: %s is the ip address. */
					self::cf7a_push_notice( sprintf( __( 'Success: ip %s unbanned', 'cf7-antispam' ), $unban_id ), 'success' );
				} else {
					/* translators: %s is the ip address. */
					self::cf7a_push_notice( sprintf( __( 'Error: unable to unban %s', 'cf7-antispam' ), $unban_id ) );
				}

				wp_safe_redirect( $url );
				exit();
			}

			/* Ban forever a single ID */
			if ( substr( $action, 0, 12 ) === 'ban_forever_' ) {

				$filter = new CF7_AntiSpam_Filters();

				$ban_id = intval( substr( $action, 12 ) );
				$ban_ip = $filter->cf7a_blacklist_get_id( $ban_id );

				if ( $ban_ip ) {
					if ( CF7_AntiSpam::update_plugin_option( 'bad_ip_list', array( $ban_ip->ip ) ) ) {
						$filter->cf7a_unban_by_id( $ban_id );
					}
					self::cf7a_push_notice(
						sprintf(
						/* translators: the %1$s is the user id and %2$s is the ip address. */
							__( 'Ban forever id %1$s (ip %2$s) successful', 'cf7-antispam' ),
							$ban_id,
							isset( $ban_ip->ip ) ? $ban_ip->ip : 'not available'
						)
					);
				} else {
					self::cf7a_push_notice(
						sprintf(
							/* translators: the %1$s is the user id and %2$s is the ip address. */
							__( 'Error: unable to ban forever id %1$s (ip %2$s)', 'cf7-antispam' ),
							$ban_id,
							isset( $ban_ip->ip ) ? $ban_ip->ip : 'not available'
						)
					);
				}

				wp_safe_redirect( $url );
				exit();
			}

			/* Purge the blacklist */
			if ( 'reset-blacklist' === $action ) {

				$r = self::cf7a_clean_blacklist();

				if ( $r ) {
					self::cf7a_push_notice( __( 'Success: ip blacklist cleaned', 'cf7-antispam' ), 'success' );
				} else {
					self::cf7a_push_notice( __( 'Error: unable to clean blacklist. Please refresh and try again!', 'cf7-antispam' ) );
				}
				wp_safe_redirect( $url );
				exit();
			}

			/* Reset Dictionary */
			if ( 'reset-dictionary' === $action ) {

				$r = self::cf7a_reset_dictionary();

				if ( $r ) {
					self::cf7a_push_notice( __( 'b8 dictionary reset successful', 'cf7-antispam' ), 'success' );
				} else {
					self::cf7a_push_notice( __( 'Something goes wrong while deleting b8 dictionary. Please refresh and try again!', 'cf7-antispam' ) );
				}
				wp_safe_redirect( $url );
				exit();
			}

			/* Rebuild Dictionary */
			if ( 'rebuild-dictionary' === $action ) {

				$r = self::cf7a_rebuild_dictionary();

				if ( $r ) {
					self::cf7a_push_notice( __( 'b8 dictionary rebuild successful', 'cf7-antispam' ), 'success' );
				} else {
					self::cf7a_push_notice( __( 'Something goes wrong while rebuilding b8 dictionary. Please refresh and try again!', 'cf7-antispam' ) );
				}
				wp_safe_redirect( $url );
				exit();
			}

			/* Reset plugin data */
			if ( 'cf7a-full-reset' === $action ) {

				$r = self::cf7a_full_reset();

				if ( $r ) {
					self::cf7a_push_notice( __( 'CF7 AntiSpam fully reinitialized with success. You need to rebuild B8 manually if needed', 'cf7-antispam' ), 'success' );
				} else {
					self::cf7a_push_notice( __( 'Ops! something went wrong... Please refresh and try again!', 'cf7-antispam' ) );
				}
				wp_safe_redirect( $url );
				exit();
			}

			/* Resend an email */
			if ( substr( $action, 0, 12 ) === 'cf7a_resend_' ) {

				$mail_id = (int) substr( $action, 12 );

				$refer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : false;

				if ( $mail_id > 1 ) {

					$cf7a_flamingo = new CF7_AntiSpam_Flamingo();
					$r             = $cf7a_flamingo->cf7a_resend_mail( $mail_id );

					if ( $r ) {
						/* translators: %s is the mail id. */
						self::cf7a_push_notice( sprintf( __( 'Email id %s sent with success', 'success cf7-antispam' ), $mail_id ) );
						wp_safe_redirect( $refer );
						exit();
					}
				}

				/* translators: %s is the mail id. */
				self::cf7a_push_notice( sprintf( __( 'Ops! something went wrong... unable to resend %s email', 'error cf7-antispam' ), $mail_id ) );
				wp_safe_redirect( $refer );
				exit();
			}
		}

	}

	/**
	 * It gets the blacklisted IPs from the database and displays them in a table
	 */
	public static function cf7a_get_blacklisted_table() {

		global $wpdb;
		$blacklisted = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cf7a_blacklist ORDER BY `status` DESC LIMIT 1000" );

		if ( $blacklisted ) {

			$count = count( $blacklisted );

			$html = sprintf( '<div id="blacklist-section"  class="cf7-antispam card"><h3>%s<small> (%s)</small></h3><div class="widefat blacklist-table">', __( 'Blacklist' ), $count . __( ' ip banned' ) );

			foreach ( $blacklisted as $row ) {

				/* the row url */
				$unban_url = wp_nonce_url( add_query_arg( 'action', 'unban_' . $row->id, menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce' );
				$ban_url   = wp_nonce_url( add_query_arg( 'action', 'ban_forever_' . $row->id, menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce' );

				$meta = unserialize( $row->meta );

				/* max_attempts */
				$max_attempts = intval( get_option( 'cf7a_options' )['max_attempts'] );

				/* the row */
				$html .= '<div class="row">';
				$html .= sprintf( "<div class='status'>%s</div>", cf7a_format_status( $row->status - $max_attempts ) );
				$html .= sprintf( '<div><p class="ip">%s<small class="actions"> <a href="%s">%s</a> <a href="%s">%s</a></small></p>', $row->ip, esc_url( $unban_url ), __( '[unban ip]' ), esc_url( $ban_url ), __( '[ban forever]' ) );
				$html .= sprintf( "<span class='data'>%s</span></div>", cf7a_compress_array( $meta['reason'], true ) );
				$html .= '</div>';

			}
			$html .= '</div></div>';

			echo $html;
		}
	}

	/**
	 * It outputs a card with a bunch of buttons that perform various actions on the database
	 *
	 * @return string the html
	 */
	public static function cf7a_advanced_settings() {

		/* the header */
		$html = printf(
			'<div id="advanced-setting-card" class="cf7-antispam card"><h3><span class="dashicons dashicons-shortcode"></span> %s</h3><p>%s</p>',
			esc_html__( 'Advanced settings', 'cf7-antispam' ),
			esc_html__( 'This section contains features that completely change what is stored in the cf7-antispam database, use them with caution!', 'cf7-antispam' )
		);

		/* output the button to remove all the entries in the blacklist database */
		$html .= printf(
			'<hr/><h3>%s</h3><p>%s</p>',
			esc_html__( 'Blacklist Reset', 'cf7-antispam' ),
			esc_html__( 'If you need to remove or reset the whole blacklist data on your server.', 'cf7-antispam' )
		);
		$url   = wp_nonce_url( add_query_arg( 'action', 'reset-blacklist', menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce' );
		$html .= printf(
			'<pre><button class="button cf7a_alert" data-href="%s">%s</button></pre>',
			esc_url( $url ),
			esc_html__( 'Remove all blacklisted IP', 'cf7-antispam' )
		);

		/* output the button to remove all the words into dictionary */
		$html .= printf(
			'<hr/><h3>%s</h3><p>%s</p>',
			esc_html__( 'Dictionary Reset', 'cf7-antispam' ),
			esc_html__( 'Use only if you need to reset the whole b8 dictionary.', 'cf7-antispam' )
		);
		$url   = wp_nonce_url( add_query_arg( 'action', 'reset-dictionary', menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce' );
		$html .= printf(
			'<pre><button class="button cf7a_alert" data-href="%s">%s</button></pre>',
			esc_url( $url ),
			esc_html__( 'Reset b8 dictionary', 'cf7-antispam' )
		);

		/* output the button to rebuild b8 dictionary */
		$html .= printf(
			'<hr/><h3>%s</h3><p>%s</p>',
			esc_html__( 'Rebuid Dictionary', 'cf7-antispam' ),
			esc_html__( 'Reanalyze all the Flamingo inbound emails (you may need to reset dictionary before).', 'cf7-antispam' )
		);
		$url   = wp_nonce_url( add_query_arg( 'action', 'rebuild-dictionary', menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce' );
		$html .= printf(
			'<pre><button class="button cf7a_alert" data-href="%s">%s</button></pre>',
			esc_url( $url ),
			esc_html__( 'Rebuild b8 dictionary', 'cf7-antispam' )
		);

		/* output the button to full reset cf7-antispam */
		$html .= printf(
			'<hr/><h3>%s</h3><p>%s</p>',
			esc_html__( 'Full Reset', 'cf7-antispam' ),
			esc_html__( 'Fully reinitialize cf7-antispam plugin database and options', 'cf7-antispam' )
		);
		$url   = wp_nonce_url( add_query_arg( 'action', 'cf7a-full-reset', menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce' );
		$html .= printf(
			'<pre><button class="button cf7a_alert" data-href="%s">%s</button></pre>',
			esc_url( $url ),
			esc_html__( 'FULL RESET', 'cf7-antispam' )
		);

		$html .= printf( '</div>' );

		return $html;

	}

	/**
	 * It returns a string containing a formatted HTML table with the plugin's options
	 *
	 * @return void the HTML for the debug info options.
	 */
	private function cf7a_get_debug_info_options() {

		printf( '<hr/><h3>%s</h3>', esc_html__( 'Options debug', 'cf7-antispam' ) );
		printf(
			'<p>%s</p><pre>%s</pre>',
			esc_html__( 'Those are the options of this plugin', 'cf7-antispam' ),
			esc_html(
				htmlentities(
					print_r( $this->options, true )
				)
			)
		);
	}

	/**
	 * It checks if the GeoIP database is enabled, and if so, it checks the next update date and displays it
	 */
	private function cf7a_get_debug_info_dnsbl() {

		if ( $this->options['check_dnsbl'] || ! empty( $this->options['dnsbl_list'] ) ) {

			$remote_ip = cf7a_get_real_ip();

			$performance_test = array();

			if ( filter_var( $remote_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {

				$reverse_ip = CF7_AntiSpam_Filters::cf7a_reverse_ipv4( $remote_ip );

			} elseif ( filter_var( $remote_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {

				$reverse_ip = CF7_AntiSpam_Filters::cf7a_reverse_ipv6( $remote_ip );
			} else {
				$reverse_ip = false;
			}

			if ( $reverse_ip ) {
				foreach ( $this->options['dnsbl_list'] as $dnsbl ) {
					$is_spam                    = CF7_AntiSpam_Filters::cf7a_check_dnsbl( $reverse_ip, $dnsbl );
					$microtime                  = cf7a_microtime_float();
					$time_taken                 = strval( round( cf7a_microtime_float() - $microtime, 5 ) );
					$performance_test[ $dnsbl ] = sprintf(
						'<tr><td>%s</td><td>%s</td><td>%f sec</td></tr>',
						$dnsbl,
						$is_spam ? 'SPAM' : 'OK',
						$time_taken
					);
				}

				if ( ! empty( $performance_test ) ) {
					printf(
						'<hr/><h3><span class="dashicons dashicons-privacy"></span> %s</h3><p>%s</p><table class="dnsbl_table">%s</table>',
						esc_html__( 'DNSBL performance test:' ),
						esc_html__( 'Results below 0.01 are fine, OK/Spam indicates the status of your ip on DNSBL servers' ),
						implode( '', $performance_test )
					);
				}
			}
		}
	}

	/**
	 * It checks if the GeoIP database is enabled, and if so, it checks the next update date and displays it
	 */
	private static function cf7a_get_debug_info_geoip() {
		try {

			$cf7a_geo = new CF7_Antispam_Geoip();

			$geoip_update = $cf7a_geo->next_update ? date_i18n( get_option( 'date_format' ), $cf7a_geo->next_update ) : esc_html__( 'not set', 'cf7-antispam' );

			$html_update_schedule = sprintf(
				'<p class="debug"><code>%s</code> %s</p>',
				esc_html__( 'Geo-IP', 'cf7-antispam' ),
				! empty( $cf7a_geo->next_update )
					? esc_html__( 'Enabled', 'cf7-antispam' ) . ' - ' . esc_html__( 'Geo-ip database next scheduled update: ', 'cf7-antispam' ) . $geoip_update
					: esc_html__( 'Disabled', 'cf7-antispam' ) . get_option( 'cf7a_geodb_update', 0 )
			);

			$your_ip     = cf7a_get_real_ip();
			$server_data = $cf7a_geo->cf7a_geoip_check_ip( $your_ip );

			if ( empty( $server_data ) ) {
				$server_data = 'Unable to retrieve geoip information for ' . $your_ip;
			}

			/* The recap of Geo-ip test */
			if ( ! empty( $cf7a_geo->next_update ) ) {
				printf(
					'<h3><span class="dashicons dashicons-location"></span> %s</h3><p>%s</p><p>%s: %s</p><pre>%s</pre>',
					esc_html__( 'Geo-IP test', 'cf7-antispam' ),
					wp_kses(
						$html_update_schedule,
						array(
							'p'    => array( 'class' => array() ),
							'code' => array(),
						)
					),
					esc_html__( 'Your IP address', 'cf7-antispam' ),
					filter_var( $your_ip, FILTER_VALIDATE_IP ),
					print_r( $server_data, true )
				);
			}
		} catch ( Exception $e ) {
			$error_message = $e->getMessage();
			printf(
				'<p>%s</p><pre>%s</pre>',
				esc_html__( 'Geo-IP Test Error', 'cf7-antispam' ),
				$error_message && $error_message['error'] ? esc_html( $error_message['error'] ) : 'error'
			);
		}
	}

	/**
	 * It outputs a debug panel if WP_DEBUG or CF7ANTISPAM_DEBUG are true
	 */
	public function cf7a_get_debug_info() {

		if ( WP_DEBUG || CF7ANTISPAM_DEBUG ) {

			/* the header */
			printf(
				'<div id="debug-info" class="cf7-antispam card"><h3><span class="dashicons dashicons-shortcode"></span> %s</h3><p>%s</p>',
				esc_html__( 'Debug info', 'cf7-antispam' ),
				esc_html__( 'If you can see this panel WP_DEBUG or CF7ANTISPAM_DEBUG are true', 'cf7-antispam' )
			);

			if ( CF7ANTISPAM_DEBUG ) {
				printf(
					'<p class="debug">%s</p>',
					'<code>CF7ANTISPAM_DEBUG</code> ' . esc_html( __( 'is enabled', 'cf7-antispam' ) )
				);
			}

			if ( CF7ANTISPAM_DEBUG_EXTENDED ) {
				printf(
					'<p class="debug">%s</p>',
					'<code>CF7ANTISPAM_DEBUG_EXTENDED</code> ' . esc_html( __( 'is enabled', 'cf7-antispam' ) )
				);
			}

			if ( CF7ANTISPAM_DEBUG_EXTENDED ) {
				printf(
					'<p class="debug"><code>%s</code> %s</p>',
					esc_html__( 'Your ip address', 'cf7-antispam' ),
					filter_var( cf7a_get_real_ip(), FILTER_VALIDATE_IP )
				);
			}

			/* output the options */
			$this->cf7a_get_debug_info_options();

			$this->cf7a_get_debug_info_geoip();

			$this->cf7a_get_debug_info_dnsbl();

			printf( '</div>' );
		}
	}


	/* Database management Flamingo */
	/**
	 * It deletes all the blacklisted ip
	 *
	 * @return bool - The result of the query.
	 */
	private static function cf7a_clean_blacklist() {
		global $wpdb;
		$r = $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}cf7a_blacklist" );
		return ! is_wp_error( $r );
	}

	/**
	 * It resets the database table that stores the spam and ham words
	 *
	 * @return bool - The result of the query.
	 */
	private static function cf7a_reset_dictionary() {
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
	private static function cf7a_reset_b8_classification() {
		global $wpdb;
		$r = $wpdb->query( 'DELETE FROM ' . $wpdb->prefix . "postmeta WHERE `meta_key` = '_cf7a_b8_classification'" );
		return ( ! is_wp_error( $r ) );
	}

	/**
	 * It resets the dictionary and classification, then analyzes all the stored mails
	 *
	 * @return bool - The return value is the number of mails that were analyzed.
	 */
	private static function cf7a_rebuild_dictionary() {
		if ( self::cf7a_reset_dictionary() ) {
			if ( self::cf7a_reset_b8_classification() ) {
				CF7_AntiSpam_Flamingo::cf7a_flamingo_analyze_stored_mails();
			}
		}
		return false;
	}

	/**
	 * It uninstalls the plugin, then reinstall it
	 */
	private static function cf7a_full_reset() {
		require_once CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-uninstall.php';
		CF7_AntiSpam_Uninstaller::uninstall( true );

		require_once CF7ANTISPAM_PLUGIN_DIR . '/includes/cf7a-activator.php';
		CF7_AntiSpam_Activator::install();

		return true;
	}
}
