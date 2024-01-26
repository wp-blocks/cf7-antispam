<?php

namespace CF7_AntiSpam\Admin;

use Exception;

use CF7_AntiSpam\Core\CF7_AntiSpam;
use CF7_AntiSpam\Core\CF7_Antispam_Geoip;
use CF7_AntiSpam\Core\CF7_AntiSpam_Filters;
/**
 * The plugin notices and ui stuff.
 *
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/admin_display
 * @author     Codekraft Studio <info@codekraft.it>
 */

/**
 * Calling the plugin display class.
 */
class CF7_AntiSpam_Admin_Display {

	/**
	 * The options of this plugin.
	 *
	 * @since    0.1.0
	 * @access   public
	 * @var      array    $options    options of this plugin.
	 */
	private $options;


	/**
	 * Init the class and get the options stored in the database.
	 */
	public function __construct() {
		$this->options = CF7_AntiSpam::get_options();
	}

	/**
	 * It adds actions to the `cf7a_dashboard` hook
	 */
	public function display_dashboard() {
		?>
		<div class="wrap">
			<div class="cf7-antispam">
			<h1><span class="icon"><?php echo wp_rand( 0, 1 ) > .5 ? '☂️' : '☔'; ?></span> Contact Form 7 AntiSpam</h1>
			<?php
			add_action( 'cf7a_dashboard', array( $this, 'cf7a_display_content' ), 22 );
			?>
			</div>
		</div>
		<?php

		add_action( 'cf7a_dashboard', array( $this, 'cf7a_display_debug' ), 30 );

		do_action( 'cf7a_dashboard' );
	}

	/**
	 * It displays the content of the widget
	 */
	public function cf7a_display_content() {
		$dismissible_banner_class = get_user_meta( get_current_user_id(), 'cf7a_hide_welcome_panel_on', true ) ? 'hidden' : '';
		?>
	<div id="welcome-panel" class="card banner dismissible <?php echo sanitize_html_class( $dismissible_banner_class ); ?>">
		<div class="inside">
			<a class="welcome-panel-close" href="<?php echo esc_url( add_query_arg( 'action', 'dismiss-banner', menu_page_url( 'cf7-antispam', false ) ) ); ?>"><?php echo esc_html( __( 'Dismiss', 'contact-form-7' ) ); ?></a>
			<?php if ( ! is_plugin_active( 'flamingo/flamingo.php' ) ) { ?>
			<h3><span class="dashicons dashicons-editor-help" aria-hidden="true"></span> <?php echo esc_html( __( 'Before you cry over spilt mail&#8230;', 'contact-form-7' ) ); ?></h3>
			<p><?php echo esc_html( __( 'Contact Form 7 doesn&#8217;t store submitted messages anywhere. Therefore, you may lose important messages forever if your mail server has issues or you make a mistake in mail configuration.', 'contact-form-7' ) ); ?></p>
			<p>
				<?php
				printf( /* translators: %s: link labeled 'Flamingo' */
					esc_html__( 'Install a message storage plugin before this happens to you. %s saves all messages through contact forms into the database. Flamingo is a free WordPress plugin created by the same author as Contact Form 7.', 'contact-form-7' ),
					esc_html__( 'https://contactform7.com/save-submitted-messages-with-flamingo/', 'contact-form-7' ),
					esc_html__( 'Flamingo', 'contact-form-7' )
				)
				?>
			</p>
			<hr />
			<?php } ?>
			<h3 class="blink"><span class="dashicons dashicons-megaphone" aria-hidden="true"></span> <?php echo esc_html( __( "PLEASE don't forget to add ", 'cf7-antispam' ) ); ?></h3>
			<b><code class="blink"><?php echo esc_html( __( 'flamingo_message: "[your-message]" ', 'cf7-antispam' ) ); ?></code></b>
			<p>
				<?php
				printf(
					"%s <b>%s</b> %s <a href='https://contactform7.com/additional-settings/'>%s</a> %s",
					esc_html__( 'Please replace ', 'cf7-antispam' ),
					'[your-message]',
					esc_html__( 'with the message field used in your form because that is the field scanned with b8. You need add this string to each form', 'cf7-antispam' ),
					esc_attr__( 'additional settings section', 'cf7-antispam' ),
					esc_html__( 'to enable the most advanced protection we can offer! Thank you!', 'cf7-antispam' )
				);
				?>
			</p>
		</div>
	</div>

	<div class="card main-options">
		<h3><?php esc_html__( 'Options', 'cf7-antispam' ); ?></h3>
		<form method="post" action="options.php" id="cf7a_settings">
			<?php

			/* This prints out all hidden setting fields */
			settings_fields( 'cf7_antispam_options' );
			do_settings_sections( 'cf7a-settings' );
			submit_button();

			?>
		</form>
	</div>

		<?php
		// Export/Import Options
		$this->cf7a_export_options();
	}

	/**
	 * It prints the blacklisted ip, the rating and some information, returns the plugins debug information and the
	 * plugins debug information
	 */
	public function cf7a_display_debug() {

		/* The blacklisted ip, the rating and some information. */
		$this->cf7a_get_blacklisted_table();

		/* Returns the plugins debug information. */
		$this->cf7a_advanced_settings();

		/* Returns the plugins debug information. */
		$this->cf7a_get_debug_info();
	}


	/**
	 * It gets the blacklisted IPs from the database and displays them in a table
	 */
	public static function cf7a_get_blacklisted_table() {
		global $wpdb;
		$blacklisted = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cf7a_blacklist ORDER BY `status` DESC LIMIT 1000" );

		if ( $blacklisted ) {
			$count = count( $blacklisted );
			$rows  = '';

			foreach ( $blacklisted as $row ) {

				/* the row url */
				$unban_url = wp_nonce_url( add_query_arg( 'action', 'unban_' . $row->id, menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce' );
				$ban_url   = wp_nonce_url( add_query_arg( 'action', 'ban_forever_' . $row->id, menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce' );

				$meta = unserialize( $row->meta );

				/* max_attempts */
				$max_attempts = intval( get_option( 'cf7a_options' )['max_attempts'] );

				/* the row */
				$rows .=
					sprintf(
						'<div class="row"><div class="status">%s</div><div><p class="ip">%s<small class="actions"><a href="%s">%s</a> <a href="%s">%s</a></small></p><span class="data">%s</span></div></div>',
						cf7a_format_status( $row->status - $max_attempts ),
						esc_html( $row->ip ),
						esc_url( $unban_url ),
						esc_html__( '[unban ip]' ),
						esc_url( $ban_url ),
						esc_html__( '[ban forever]' ),
						cf7a_compress_array( $meta['reason'], true )
					);
			}

			printf(
				'<div id="blacklist-section"  class="cf7-antispam card"><h3>%s<small> (%s)</small></h3><div class="widefat blacklist-table">%s</div></div>',
				esc_html( 'Blacklist' ),
				intval( $count ) . esc_html__( ' ip banned' ),
				wp_kses(
					$rows,
					array(
						'div'   => array( 'class' => array() ),
						'small' => array( 'class' => array() ),
						'p'     => array( 'class' => array() ),
						'a'     => array( 'href' => array() ),
						'b'     => array(),
						'span'  => array(
							'class' => array(),
							'style' => array(),
						),
					)
				)
			);
		}
	}

	private function cf7a_export_options() {

		?>
		<div id="cf7a_export_import" class="cf7-antispam card">
			<h3><?php esc_html_e( 'Export/Import Options', 'cf7-antispam' ); ?></h3>
			<form id="import-export-options" method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php
				$option_group = 'cf7_antispam_options';
				wp_nonce_field( "$option_group-options" );
				?>
				<input type="hidden" name="option_page" value="cf7_antispam_options">
				<input type="hidden" name="action" value="update">
				<input type="hidden" name="type" value="import">
				<input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( add_query_arg( 'settings-updated', 'true', admin_url( 'admin.php?page=cf7-antispam' ) ) ); ?>">

				<!-- Form field -->
				<label for="cf7a_options_area"><?php esc_html__( 'Copy or paste here the settings to import it or export it', 'cf7-antispam' ); ?></label>
				<textarea id="cf7a_options_area" rows="5"><?php echo wp_json_encode( $this->options, JSON_PRETTY_PRINT ); ?></textarea>

				<!-- buttons -->
				<div class="cf7a_buttons cf7a_buttons_export_import">
					<button type="button" id="cf7a_download_button" class="button button-primary">Download</button>
					<button type="submit" id="cf7a_import_button" class="button button-secondary">Import</button>
				</div>
			</form>
		</div>
		<?php
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
			'<hr/><h3>%s</h3><p>%s<br/>%s</p>',
			esc_html__( 'Rebuid Dictionary', 'cf7-antispam' ),
			esc_html__( 'Reanalyze all the Flamingo inbound emails (you may need to reset dictionary before).', 'cf7-antispam' ),
			esc_html__( 'Use this function after correctly sorting spam and non-spam mails or if you have experienced a Bayesian poisoning attack, will maximise the accuracy of the algorithm', 'cf7-antispam' )
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

			if ( ! empty( $this->options['check_geoip_enabled'] ) ) {
				$this->cf7a_get_debug_info_geoip();
			}

			if ( ! empty( $this->options['check_dnsbl'] ) && ! empty( $this->options['dnsbl_list'] ) ) {
				$this->cf7a_get_debug_info_dnsbl();
			}

			printf( '</div>' );
		}
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
				$performance_test[ $dnsbl ] =
					sprintf(
						'<tr><td>%s</td><td>%s</td><td>%f sec</td></tr>',
						$dnsbl,
						$is_spam ? esc_html__( 'spam' ) : esc_html__( 'ham' ),
						$time_taken
					);
			}

			if ( ! empty( $performance_test ) ) {
				printf(
					'<hr/><h3><span class="dashicons dashicons-privacy"></span> %s</h3><p>%s</p><p>%s: %s</p><table class="dnsbl_table">%s</table>',
					esc_html__( 'DNSBL performance test:' ),
					esc_html__( 'Results below 0.01 are fine, OK/Spam indicates the status of your ip on DNSBL servers' ),
					esc_html__( 'Your IP address' ),
					filter_var( $remote_ip, FILTER_VALIDATE_IP ),
					wp_kses(
						implode( '', $performance_test ),
						array(
							'tr' => array(),
							'td' => array(),
						)
					)
				);
			}
		}
	}

	/**
	 * It checks if the GeoIP database is enabled, and if so, it checks the next update date and displays it
	 */
	private static function cf7a_get_debug_info_geoip() {
		try {
			$cf7a_geo = new CF7_Antispam_Geoip();

			$geoip_update = $cf7a_geo->next_update ? esc_html( date_i18n( get_option( 'date_format' ), $cf7a_geo->next_update ) ) : esc_html__( 'not set', 'cf7-antispam' );

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
					// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r
					wp_kses( print_r( $server_data, true ), array( 'pre' => array() ) )
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
}
