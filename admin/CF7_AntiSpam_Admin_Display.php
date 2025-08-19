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
				<?php $this->render_tabbed_interface(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the tabbed interface
	 */
	private function render_tabbed_interface() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'dashboard';
		print_r( $active_tab );
		?>
		<div class="cf7a-nav-tab-wrapper">
			<a href="<?php echo esc_url( $this->get_tab_url( 'dashboard' ) ); ?>"
				 class="cf7a-nav-tab tab-dashboard <?php echo $active_tab === 'dashboard' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-dashboard"></span> <?php esc_html_e( 'Dashboard', 'cf7-antispam' ); ?>
			</a>
			<a href="<?php echo esc_url( $this->get_tab_url( 'settings' ) ); ?>"
				 class="cf7a-nav-tab tab-settings <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Settings', 'cf7-antispam' ); ?>
			</a>
			<a href="<?php echo esc_url( $this->get_tab_url( 'blacklist' ) ); ?>"
				 class="cf7a-nav-tab tab-blacklist <?php echo $active_tab === 'blacklist' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-shield"></span> <?php esc_html_e( 'Blacklist', 'cf7-antispam' ); ?>
			</a>
			<a href="<?php echo esc_url( $this->get_tab_url( 'tools' ) ); ?>"
				 class="cf7a-nav-tab tab-tools <?php echo $active_tab === 'tools' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Tools', 'cf7-antispam' ); ?>
			</a>
			<a href="<?php echo esc_url( $this->get_tab_url( 'import-export' ) ); ?>"
				 class="cf7a-nav-tab tab-import-export <?php echo $active_tab === 'tools' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-database-export"></span> <?php esc_html_e( 'Import/Export', 'cf7-antispam' ); ?>
			</a>
			<?php if ( WP_DEBUG || CF7ANTISPAM_DEBUG ) : ?>
				<a href="<?php echo esc_url( $this->get_tab_url( 'debug' ) ); ?>"
					 class="cf7a-nav-tab tab-debug <?php echo $active_tab === 'debug' ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-code-standards"></span> <?php esc_html_e( 'Debug', 'cf7-antispam' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<div class="cf7a-tab-content">
			<div id="dashboard" class="cf7a-tab-panel <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">
				<?php $this->render_dashboard_tab(); ?>
			</div>
			<div id="settings" class="cf7a-tab-panel <?php echo $active_tab === 'settings' ? 'active' : ''; ?>">
				<?php $this->render_settings_tab(); ?>
			</div>
			<div id="blacklist" class="cf7a-tab-panel <?php echo $active_tab === 'blacklist' ? 'active' : ''; ?>">
				<?php $this->render_blacklist_tab(); ?>
			</div>
			<div id="tools" class="cf7a-tab-panel <?php echo $active_tab === 'tools' ? 'active' : ''; ?>">
				<?php $this->render_tools_tab(); ?>
			</div>
			<div id="import-export" class="cf7a-tab-panel <?php echo $active_tab === 'import-export' ? 'active' : ''; ?>">
				<?php $this->render_import_export_tab(); ?>
			</div>
			<?php if ( WP_DEBUG || CF7ANTISPAM_DEBUG ) : ?>
				<div id="debug" class="cf7a-tab-panel <?php echo $active_tab === 'debug' ? 'active' : ''; ?>">
					<?php $this->render_debug_tab(); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get tab URL
	 */
	private function get_tab_url( $tab ) {
		return add_query_arg( 'tab', $tab, menu_page_url( 'cf7-antispam', false ) );
	}

	/**
	 * Render Dashboard Tab
	 */
	private function render_dashboard_tab() {
		$dismissible_banner_class = get_user_meta( get_current_user_id(), 'cf7a_hide_welcome_panel_on', true ) ? 'hidden' : '';
		?>

		<?php $this->cf7a_print_section_main_subtitle(); ?>

		<?php $this->render_stats_overview(); ?>

		<div id="welcome-panel" class="cf7a-card dismissible <?php echo sanitize_html_class( $dismissible_banner_class ); ?>">
			<a class="welcome-panel-close" href="<?php echo esc_url( add_query_arg( 'action', 'dismiss-banner', menu_page_url( 'cf7-antispam', false ) ) ); ?>"><?php echo esc_html( __( 'Dismiss', 'contact-form-7' ) ); ?></a>

			<?php if ( ! is_plugin_active( 'flamingo/flamingo.php' ) ) : ?>
				<h3><span class="dashicons dashicons-editor-help" aria-hidden="true"></span> <?php echo esc_html( __( 'Before you cry over spilt mail&#8230;', 'contact-form-7' ) ); ?></h3>
				<p><?php echo esc_html( __( 'Contact Form 7 doesn&#8217;t store submitted messages anywhere. Therefore, you may lose important messages forever if your mail server has issues or you make a mistake in mail configuration.', 'contact-form-7' ) ); ?></p>
				<p>
					<?php
					printf( /* translators: %s: link labeled 'Flamingo' */
						esc_html__( 'Install a message storage plugin before this happens to you. %s saves all messages through contact forms into the database. Flamingo is a free WordPress plugin created by the same author as Contact Form 7.', 'contact-form-7' ),
						'<a href="https://contactform7.com/save-submitted-messages-with-flamingo/" target="_blank">' . esc_html__( 'Flamingo', 'contact-form-7' ) . '</a>'
					);
					?>
				</p>
				<hr />
			<?php endif; ?>

			<h3 class="blink"><span class="dashicons dashicons-megaphone" aria-hidden="true"></span> <?php echo esc_html( __( "PLEASE don't forget to add ", 'cf7-antispam' ) ); ?></h3>
			<b><code class="blink"><?php echo esc_html( __( 'flamingo_message: "[your-message]" ', 'cf7-antispam' ) ); ?></code></b>
			<p>
				<?php
				printf(
					"%s <b>%s</b> %s <a href='https://contactform7.com/additional-settings/' target='_blank'>%s</a> %s",
					esc_html__( 'Please replace ', 'cf7-antispam' ),
					'[your-message]',
					esc_html__( 'with the message field used in your form because that is the field scanned with b8. You need add this string to each form', 'cf7-antispam' ),
					esc_attr__( 'additional settings section', 'cf7-antispam' ),
					esc_html__( 'to enable the most advanced protection we can offer! Thank you!', 'cf7-antispam' )
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * It returns a random tip from an array of tips
	 *
	 * @return string a random tip from the array of tips.
	 */
	public function cf7a_get_a_random_tip() {
		$tips = array(
			__( 'Do you know,that you can save settings simply using the shortcut [Ctrl + S].', 'cf7-antispam' ),
			__( 'In the CF7-Antispam settings page you can enter values in textarea using the comma-separated format and, on saving, the strings will be split up into one per line format.', 'cf7-antispam' ),
			sprintf(
			/* translators: %s is the (hypothetical) link to the contact page (www.my-website.xyz/contacts). */
				'%s <a href="%s" target="_blank">%s</a>',
				__( 'It is always a good practice to NOT name "contact" the slug of the page with the form. This makes it very easy for a bot to find it, doesn\'t it?', 'cf7-antispam' ),
				trailingslashit( get_bloginfo( 'url' ) ) . __( 'contacts', 'cf7-antispam' ),
				__( 'Give a try', 'cf7-antispam' )
			),
			sprintf(
			/* translators: %s is the link to Flamingo documentation. */
				"%s <a href='%s' target='_blank'>%s</a>. %s",
				__( 'As Flamingo also CF7-Antispam can handle', 'cf7-antispam' ),
				esc_url_raw( 'https://contactform7.com/save-submitted-messages-with-flamingo/' ),
				__( 'fields with multiple tags', 'cf7-antispam' ),
				__( 'In this way, you can scan as a message multiple fields at once (subject line or second text field...)', 'cf7-antispam' )
			),
		);

		return $tips[ round( wp_rand( 0, count( $tips ) - 1 ) ) ];
	}

	/**
	 * It prints The main setting text below the title
	 */
	public function cf7a_print_section_main_subtitle() {
		$tips_wpkses_format = array(
			'a' => array(
				'href'   => array(),
				'target' => array(),
			),
		);

		printf(
			'<div class="cf7a-tip"><p><strong>💡 %s</strong> %s</p></div>',
			esc_html__( 'Tip:', 'cf7-antispam' ),
			wp_kses(
				self::cf7a_get_a_random_tip(),
				$tips_wpkses_format
			)
		);
	}

	/**
	 * Render stats overview
	 */
	private function render_stats_overview() {
		global $wpdb;

		// Get basic stats
		$total_blocked = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}cf7a_blacklist" );

		// Get status breakdown with proper grouping
		$status_data = $wpdb->get_results( "
        SELECT status, COUNT(*) as count
        FROM {$wpdb->prefix}cf7a_blacklist
        GROUP BY status
        ORDER BY status ASC
    " );

		// Group status into ranges
		$status_ranges = array(
			'1-5' => 0,
			'6-10' => 0,
			'11-25' => 0,
			'26-50' => 0,
			'51-100' => 0,
			'100+' => 0
		);

		foreach ( $status_data as $status ) {
			$status_num = intval( $status->status );
			$count = intval( $status->count );

			if ( $status_num >= 1 && $status_num <= 5 ) {
				$status_ranges['1-5'] += $count;
			} elseif ( $status_num >= 6 && $status_num <= 10 ) {
				$status_ranges['6-10'] += $count;
			} elseif ( $status_num >= 11 && $status_num <= 25 ) {
				$status_ranges['11-25'] += $count;
			} elseif ( $status_num >= 26 && $status_num <= 50 ) {
				$status_ranges['26-50'] += $count;
			} elseif ( $status_num >= 51 && $status_num <= 100 ) {
				$status_ranges['51-100'] += $count;
			} else {
				$status_ranges['100+'] += $count;
			}
		}

		// Remove empty ranges for cleaner display
		$status_ranges = array_filter( $status_ranges, function($count) {
			return $count > 0;
		});

		// Get detailed reason stats from serialized meta
		$meta_data = $wpdb->get_results( "
        SELECT meta
        FROM {$wpdb->prefix}cf7a_blacklist
        WHERE meta IS NOT NULL AND meta != '' AND meta != 'a:0:{}'
    " );

		$reason_counts = array();
		foreach ( $meta_data as $row ) {
			$decoded_meta = unserialize( $row->meta );

			if ( is_array( $decoded_meta ) ) {
				foreach ( $decoded_meta as $entry ) {
					if ( is_array( $entry ) ) {
						// Count each reason type within the reason array
						foreach ( $entry as $reason_key => $reason_value ) {
							// Convert reason key to readable format
							$reason_name = $this->format_reason_name( $reason_key );

							if ( !isset( $reason_counts[$reason_name] ) ) {
								$reason_counts[$reason_name] = 0;
							}
							$reason_counts[$reason_name]++;
						}
					}
				}
			}
		}

		// Sort reasons by count and get top 5
		arsort( $reason_counts );
		$top_reasons = array_slice( $reason_counts, 0, 5, true );

		?>
		<div class="cf7a-stats-grid">
			<div class="cf7a-stat-card fit-center">
				<div class="cf7a-stat-number"><?php echo esc_html( $total_blocked ?: '0' ); ?></div>
				<div class="cf7a-stat-label"><?php esc_html_e( 'Total Blocked IPs', 'cf7-antispam' ); ?></div>
			</div>

			<!-- Status Breakdown by Ranges -->
			<div class="cf7a-stat-card cf7a-stat-card-wide">
				<div class="cf7a-stat-label"><?php esc_html_e( 'Warning Count Ranges', 'cf7-antispam' ); ?></div>
				<div class="cf7a-status-breakdown">
					<?php if ( !empty( $status_ranges ) ) : ?>
						<?php foreach ( $status_ranges as $range => $count ) : ?>
							<div class="cf7a-status-item">
                            <span class="cf7a-status-badge cf7a-range-<?php echo esc_attr( str_replace( array( '-', '+' ), array( '_', 'plus' ), $range ) ); ?>">
                                <?php echo esc_html( $range . ' warnings' ); ?>
                            </span>
								<span class="cf7a-status-count"><?php echo esc_html( $count ); ?> IPs</span>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<p><?php esc_html_e( 'No warning data available', 'cf7-antispam' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<!-- Top Reasons -->
			<div class="cf7a-stat-card cf7a-stat-card-wide">
				<div class="cf7a-stat-label"><?php esc_html_e( 'Top Block Reasons', 'cf7-antispam' ); ?></div>
				<div class="cf7a-reasons-breakdown">
					<?php if ( !empty( $top_reasons ) ) : ?>
						<?php foreach ( $top_reasons as $reason => $count ) : ?>
							<div class="cf7a-reason-item">
                            <span class="cf7a-reason-name" title="<?php echo esc_attr( $reason ); ?>">
                                <?php echo esc_html( strlen( $reason ) > 40 ? substr( $reason, 0, 40 ) . '...' : $reason ); ?>
                            </span>
								<span class="cf7a-reason-count"><?php echo esc_html( $count ); ?></span>
							</div>
						<?php endforeach; ?>

						<!-- Show total unique reasons if more than 5 -->
						<?php if ( count( $reason_counts ) > 5 ) : ?>
							<div class="cf7a-reason-item cf7a-reason-summary">
                            <span class="cf7a-reason-name">
                                <em><?php printf( esc_html__( 'Total unique reasons: %d', 'cf7-antispam' ), count( $reason_counts ) ); ?></em>
                            </span>
								<span class="cf7a-reason-count">
                                <em><?php echo esc_html( array_sum( $reason_counts ) ); ?></em>
                            </span>
							</div>
						<?php endif; ?>
					<?php else : ?>
						<p><?php esc_html_e( 'No reason data available', 'cf7-antispam' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Format reason names for better display
	 */
	private function format_reason_name( $reason_key ): string {
		// Handle special cases
		$reason_mappings = array(
			'blacklisted_score' => 'Recidive',
			'blacklisted score' => 'Recidive',
			'data_mismatch' => 'Data Mismatch',
			'bot_fingerprint' => 'Bot Fingerprint',
			'bot_fingerprint_extras' => 'Bot Fingerprint Extras',
			'browser_language' => 'Browser Language',
			'honeypot' => 'Honeypot',
			'b8' => 'B8 Filter',
			'geo_location' => 'Geo Location',
			'ip_reputation' => 'IP Reputation',
			'user_agent' => 'User Agent',
			'disposable_email' => 'Disposable Email',
			'spam_words' => 'Spam Words'
		);

		// Check if we have a custom mapping
		if ( isset( $reason_mappings[$reason_key] ) ) {
			return $reason_mappings[$reason_key];
		}

		// Default formatting: replace underscores with spaces and capitalize
		return ucwords( str_replace( '_', ' ', $reason_key ) );
	}

	/**
	 * Render Settings Tab
	 */
	private function render_settings_tab() {
		?>
		<div class="cf7a-card">
			<h3><?php esc_html_e( 'Plugin Settings', 'cf7-antispam' ); ?></h3>
			<form method="post" action="options.php" id="cf7a_settings">
				<?php
				settings_fields( 'cf7_antispam_options' );
				do_settings_sections( 'cf7a-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render Blacklist Tab
	 */
	private function render_blacklist_tab() {
		?>
		<div class="cf7a-card">
			<h3><?php esc_html_e( 'Blacklisted IPs', 'cf7-antispam' ); ?></h3>
			<p><?php esc_html_e( 'Here you can see all the IPs that have been blacklisted by the plugin.', 'cf7-antispam' ); ?></p>
			<?php $this->cf7a_get_blacklisted_table(); ?>
		</div>
		<?php
	}

	/**
	 * Render Blacklist Tab
	 */
	private function render_import_export_tab() {
		?>
		<div class="cf7a-card">
			<h3><?php esc_html_e( 'Export/Import Options', 'cf7-antispam' ); ?></h3>
			<?php $this->cf7a_export_options(); ?>
		</div>
		<?php
	}

	/**
	 * Render Tools Tab
	 */
	private function render_tools_tab() {
		?>
		<div class="cf7a-card">
			<h3><?php esc_html_e( 'Advanced Tools', 'cf7-antispam' ); ?></h3>
			<p><?php esc_html_e( 'This section contains features that completely change what is stored in the cf7-antispam database, use them with caution!', 'cf7-antispam' ); ?></p>

			<?php $this->render_advanced_tools(); ?>
		</div>
		<?php
	}

	/**
	 * Render advanced tools section
	 */
	private function render_advanced_tools() {
		?>
		<div class="cf7a-danger-zone">
			<h3><?php esc_html_e( 'Danger Zone', 'cf7-antispam' ); ?></h3>
			<p><?php esc_html_e( 'These actions are irreversible. Please make sure you know what you are doing.', 'cf7-antispam' ); ?></p>

			<h4><?php esc_html_e( 'Blacklist Reset', 'cf7-antispam' ); ?></h4>
			<p><?php esc_html_e( 'Remove all blacklisted IPs from the database.', 'cf7-antispam' ); ?></p>
			<?php
			$url = wp_nonce_url( add_query_arg( 'action', 'reset-blacklist', menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce' );
			?>
			<button class="cf7a-alert-button cf7a_alert" data-href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Remove all blacklisted IP', 'cf7-antispam' ); ?></button>

			<h4><?php esc_html_e( 'Dictionary Reset', 'cf7-antispam' ); ?></h4>
			<p><?php esc_html_e( 'Reset the entire b8 dictionary used for spam detection.', 'cf7-antispam' ); ?></p>
			<?php
			$url = wp_nonce_url( add_query_arg( 'action', 'reset-dictionary', menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce' );
			?>
			<button class="cf7a-alert-button cf7a_alert" data-href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Reset b8 dictionary', 'cf7-antispam' ); ?></button>

			<h4><?php esc_html_e( 'Rebuild Dictionary', 'cf7-antispam' ); ?></h4>
			<p><?php esc_html_e( 'Reanalyze all Flamingo inbound emails to rebuild the dictionary.', 'cf7-antispam' ); ?></p>
			<?php
			$url = wp_nonce_url( add_query_arg( 'action', 'rebuild-dictionary', menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce' );
			?>
			<button class="cf7a-alert-button cf7a_alert" data-href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Rebuild b8 dictionary', 'cf7-antispam' ); ?></button>

			<h4><?php esc_html_e( 'Full Reset', 'cf7-antispam' ); ?></h4>
			<p><?php esc_html_e( 'Completely reset the plugin to its initial state.', 'cf7-antispam' ); ?></p>
			<?php
			$url = wp_nonce_url( add_query_arg( 'action', 'cf7a-full-reset', menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce' );
			?>
			<button class="cf7a-alert-button cf7a_alert" data-href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'FULL RESET', 'cf7-antispam' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Render Import/Export Tab
	 */
	private function cf7a_export_options() {
		?>
		<form id="import-export-options" method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
			<?php
			$option_group = 'cf7_antispam_options';
			wp_nonce_field( "$option_group-options" );
			?>
			<input type="hidden" name="option_page" value="cf7_antispam_options">
			<input type="hidden" name="action" value="update">
			<input type="hidden" name="type" value="import">
			<input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( add_query_arg( 'settings-updated', 'true', admin_url( 'admin.php?page=cf7-antispam' ) ) ); ?>">

			<label for="cf7a_options_area"><?php esc_html_e( 'Copy or paste here the settings to import it or export it', 'cf7-antispam' ); ?></label>
			<textarea id="cf7a_options_area" rows="20" style="width: 100%;"><?php echo wp_json_encode( $this->options, JSON_PRETTY_PRINT ); ?></textarea>

			<div class="cf7a_buttons cf7a_buttons_export_import" style="margin-top: 10px;">
				<button type="button" id="cf7a_download_button" class="button button-primary">Download</button>
				<button type="submit" id="cf7a_import_button" class="button button-secondary">Import</button>
			</div>
		</form>
		<?php
	}

	/**
	 * Render Debug Tab
	 */
	private function render_debug_tab() {
		?>
		<div class="cf7a-card">
			<h3><?php esc_html_e( 'Debug Information', 'cf7-antispam' ); ?></h3>
			<p><?php esc_html_e( 'Debug information is only visible when WP_DEBUG or CF7ANTISPAM_DEBUG are enabled.', 'cf7-antispam' ); ?></p>
			<?php $this->cf7a_get_debug_info(); ?>
		</div>
		<?php
	}

	/**
	 * It displays the content of the widget (legacy method for backward compatibility)
	 */
	public function cf7a_display_content() {
		// This method is kept for backward compatibility but redirects to the new tabbed interface
		$this->render_dashboard_tab();
	}

	/**
	 * It prints the blacklisted ip, the rating and some information, returns the plugins debug information and the
	 * plugins debug information (legacy method for backward compatibility)
	 */
	public function cf7a_display_debug() {
		// This method is kept for backward compatibility but content is now in separate tabs
		$this->render_debug_tab();
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
				$unban_url = wp_nonce_url( add_query_arg( 'action', 'unban_' . $row->id, menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce' );
				$ban_url   = wp_nonce_url( add_query_arg( 'action', 'ban_forever_' . $row->id, menu_page_url( 'cf7-antispam', false ) ), 'cf7a-nonce', 'cf7a-nonce' );

				$meta = unserialize( $row->meta );
				$max_attempts = intval( get_option( 'cf7a_options' )['max_attempts'] );

				$rows .= sprintf(
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
				'<div class="widefat blacklist-table">%s</div><p><small>%s</small></p>',
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
				),
				sprintf( esc_html__( 'Showing %d blacklisted IPs', 'cf7-antispam' ), intval( $count ) )
			);
		} else {
			echo '<p>' . esc_html__( 'No blacklisted IPs found.', 'cf7-antispam' ) . '</p>';
		}
	}

	/**
	 * It outputs a card with a bunch of buttons that perform various actions on the database
	 *
	 * @return string the html
	 */
	public static function cf7a_advanced_settings() {
		// This method is now integrated into the Tools tab
		// Keeping for backward compatibility but functionality moved to render_advanced_tools()
		return '';
	}

	/**
	 * It outputs a debug panel if WP_DEBUG or CF7ANTISPAM_DEBUG are true
	 */
	public function cf7a_get_debug_info() {
		if ( WP_DEBUG || CF7ANTISPAM_DEBUG ) {
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

			$this->cf7a_get_debug_info_options();

			if ( ! empty( $this->options['check_geoip_enabled'] ) ) {
				$this->cf7a_get_debug_info_geoip();
			}

			if ( ! empty( $this->options['check_dnsbl'] ) && ! empty( $this->options['dnsbl_list'] ) ) {
				$this->cf7a_get_debug_info_dnsbl();
			}
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
