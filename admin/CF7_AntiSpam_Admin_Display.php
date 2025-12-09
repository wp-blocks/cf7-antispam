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

	private static function is_flamingo_active() {
		return is_plugin_active( 'flamingo/flamingo.php' );
	}

	private static function cf7a_welcome_message() {
		self::is_flamingo_active()
			/* translators: %s is the shortcode */
			? printf( esc_html__( 'Please do not forget to add %s to your forms to enable B8 Bayesian filtering.', 'cf7-antispam' ), '<code>flamingo_message: "[your-message]"</code>' )
			: esc_html_e( 'Please install and activate the Flamingo plugin to enable advanced B8 Bayesian filtering.', 'cf7-antispam' );
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

		$active_tab = 'dashboard'; // Default tab

		// Check if 'tab' is present in the GET request.
		$nonce_action = 'cf7a_admin_tab_switch';

		if ( isset( $_GET['tab'] ) ) {
			if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), $nonce_action ) ) {
				$active_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
			}
		}
		?>
		<div class="cf7a-nav-tab-wrapper">
			<a href="<?php echo esc_url( wp_nonce_url( $this->get_tab_url( 'dashboard' ), $nonce_action ) ); ?>"
				class="cf7a-nav-tab tab-dashboard <?php echo $active_tab === 'dashboard' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-dashboard"></span> <?php esc_html_e( 'Dashboard', 'cf7-antispam' ); ?>
			</a>
			<a href="<?php echo esc_url( wp_nonce_url( $this->get_tab_url( 'settings' ), $nonce_action ) ); ?>"
				class="cf7a-nav-tab tab-settings <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Settings', 'cf7-antispam' ); ?>
			</a>
			<a href="<?php echo esc_url( wp_nonce_url( $this->get_tab_url( 'blocklist' ), $nonce_action ) ); ?>"
				class="cf7a-nav-tab tab-blocklist <?php echo $active_tab === 'blocklist' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-shield"></span> <?php esc_html_e( 'Blocklist', 'cf7-antispam' ); ?>
			</a>
			<a href="<?php echo esc_url( wp_nonce_url( $this->get_tab_url( 'tools' ), $nonce_action ) ); ?>"
				class="cf7a-nav-tab tab-tools <?php echo $active_tab === 'tools' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Tools', 'cf7-antispam' ); ?>
			</a>
			<a href="<?php echo esc_url( wp_nonce_url( $this->get_tab_url( 'import-export' ), $nonce_action ) ); ?>"
				class="cf7a-nav-tab tab-import-export <?php echo $active_tab === 'import-export' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-database-export"></span> <?php esc_html_e( 'Import/Export', 'cf7-antispam' ); ?>
			</a>
			<?php if ( WP_DEBUG || CF7ANTISPAM_DEBUG ) : ?>
				<a href="<?php echo esc_url( wp_nonce_url( $this->get_tab_url( 'debug' ), $nonce_action ) ); ?>"
					class="cf7a-nav-tab tab-debug <?php echo $active_tab === 'debug' ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-code-standards"></span> <?php esc_html_e( 'Debug', 'cf7-antispam' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<div class="cf7a-tab-content">
			<div id="dashboard" class="cf7a-tab-panel <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">
				<?php
				if ( $active_tab === 'dashboard' ) {
					$this->render_dashboard_tab();
				}
				?>
			</div>
			<div id="settings" class="cf7a-tab-panel <?php echo $active_tab === 'settings' ? 'active' : ''; ?>">
				<?php
				if ( $active_tab === 'settings' ) {
					$this->render_settings_tab();
				}
				?>
			</div>
			<div id="blocklist" class="cf7a-tab-panel <?php echo $active_tab === 'blocklist' ? 'active' : ''; ?>">
				<?php
				if ( $active_tab === 'blocklist' ) {
					$this->render_blacklist_tab();
				}
				?>
			</div>
			<div id="tools" class="cf7a-tab-panel <?php echo $active_tab === 'tools' ? 'active' : ''; ?>">
				<?php
				if ( $active_tab === 'tools' ) {
					$this->render_tools_tab();
				}
				?>
			</div>
			<div id="import-export" class="cf7a-tab-panel <?php echo $active_tab === 'import-export' ? 'active' : ''; ?>">
				<?php
				if ( $active_tab === 'import-export' ) {
					$this->render_import_export_tab();
				}
				?>
			</div>
			<?php if ( WP_DEBUG || CF7ANTISPAM_DEBUG ) : ?>
				<div id="debug" class="cf7a-tab-panel <?php echo $active_tab === 'debug' ? 'active' : ''; ?>">
					<?php
					if ( $active_tab === 'debug' ) {
						$this->render_debug_tab();
					}
					?>
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
	 * Check if there's enough data to display the dashboard
	 *
	 * @return bool True if there's enough data
	 */
	private function has_enough_data(): bool {
		global $wpdb;
		// Check blocklist entries
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$blacklist_count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $wpdb->prefix . 'cf7a_blacklist' ) );
		$has_blacklist   = intval( $blacklist_count ) > 0;

		// Check wordlist entries (beyond just the b8*texts token)
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wordlist_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE token != 'b8*texts' AND token != 'b8*dbversion'", $wpdb->prefix . 'cf7a_wordlist' ) );
		$has_wordlist   = intval( $wordlist_count ) > 0;

		return $has_blacklist || $has_wordlist;
	}

	/**
	 * Render the empty state dashboard when no data is available
	 */
	private function render_empty_state_dashboard() {
		$settings_url = wp_nonce_url( $this->get_tab_url( 'settings' ), 'cf7a_admin_tab_switch' );
		?>
		<div class="cf7a-empty-state">
			<div class="cf7a-empty-state-content">
				<div class="cf7a-empty-state-icon">
					<span class="icon">☔</span>
				</div>

				<h2 class="cf7a-empty-state-title">
					<?php esc_html_e( 'Welcome to CF7 AntiSpam!', 'cf7-antispam' ); ?>
				</h2>

				<p class="cf7a-empty-state-description">
					<?php esc_html_e( "Your protection is active, but we haven't collected any data yet. Once your forms start receiving submissions, you'll see detailed statistics here.", 'cf7-antispam' ); ?>
				</p>

				<div class="cf7a-empty-state-features">
					<div class="cf7a-empty-state-feature">
						<span class="dashicons dashicons-chart-bar"></span>
						<span><?php esc_html_e( 'Email Statistics', 'cf7-antispam' ); ?></span>
					</div>
					<div class="cf7a-empty-state-feature">
						<span class="dashicons dashicons-block-default"></span>
						<span><?php esc_html_e( 'IPs Blocklist', 'cf7-antispam' ); ?></span>
					</div>
					<div class="cf7a-empty-state-feature">
						<span class="dashicons dashicons-filter"></span>
						<span><?php esc_html_e( 'Customizable Filter', 'cf7-antispam' ); ?></span>
					</div>
				</div>

				<div class="cf7a-empty-state-actions">
					<a href="<?php echo esc_url( $settings_url ); ?>" class="button button-primary button-hero">
						<span class="dashicons dashicons-admin-settings"></span>
						<?php esc_html_e( 'Configure Settings', 'cf7-antispam' ); ?>
					</a>
					<a href="https://github.com/erikyo/contact-form-7-antispam#readme" target="_blank" class="button button-secondary button-hero">
						<span class="dashicons dashicons-book"></span>
						<?php esc_html_e( 'Read Documentation', 'cf7-antispam' ); ?>
					</a>
				</div>

				<p class="cf7a-empty-state-tip">
					<span class="dashicons dashicons-lightbulb"></span>
					<span class="cf7a-empty-state-tip-text"><?php echo self::cf7a_get_a_random_tip(); ?></span>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Dashboard Tab
	 */
	private function render_dashboard_tab() {
		// Render the one-time alert banner
		$this->render_one_time_alert_banner();

		// Check if there's enough data to show the full dashboard
		if ( ! $this->has_enough_data() ) {
			$this->render_empty_state_dashboard();
			return;
		}

		$this->render_antispam_charts();

		$this->render_stats_overview();
	}

	private function render_one_time_alert_banner() {
		$dismissible_banner_class = get_user_meta( get_current_user_id(), 'cf7a_hide_welcome_panel_on', true ) ? 'hidden' : '';
		?>
		<div id="welcome-notice" class="cf7a-card cf7-notice dismissible <?php echo sanitize_html_class( $dismissible_banner_class ); ?>">
			<a class="welcome-panel-close" href="<?php echo wp_nonce_url( add_query_arg( 'action', 'dismiss-banner', menu_page_url( 'cf7-antispam', false ) ), 'dismiss-banner' ); ?>"><span class="screen-reader-text"><?php echo esc_html( __( 'Dismiss', 'contact-form-7' ) ); ?></span></a>
			<span class="dashicons dashicons-megaphone" aria-hidden="true"></span>
			<p>
				<?php self::cf7a_welcome_message(); ?>
			</p>
		</div>
		<?php
	}

	private function render_antispam_charts() {
		$cf7a_charts = new CF7_AntiSpam_Admin_Charts();

		echo '<div class="cf7a-stat-card dashboard-charts-section cf7a-stat-card cf7a-stat-card-wide">';
		echo '<h2>' . esc_html__( 'Email Statistics', 'cf7-antispam' ) . '</h2>';

		$cf7a_charts->cf7a_dash_charts();

		echo '</div>';
	}

	/**
	 * Render stats overview
	 */
	private function render_stats_overview() {
		global $wpdb;

		// Set cache expiration times (in seconds)
		$cache_time_short = 5 * MINUTE_IN_SECONDS;  // 5 minutes for frequently changing data
		$cache_time_long  = 15 * MINUTE_IN_SECONDS; // 15 minutes for more stable data

		// Get basic stats with caching
		$cache_key_total = 'cf7a_total_blocked_count';
		$total_blocked   = wp_cache_get( $cache_key_total, 'cf7a_blacklist_stats' );

		$blacklist_table = $wpdb->prefix . 'cf7a_blacklist';

		if ( false === $total_blocked ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total_blocked = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $blacklist_table ) );
			wp_cache_set( $cache_key_total, $total_blocked, 'cf7a_blacklist_stats', $cache_time_short );
		}

		// Get status breakdown with caching
		$cache_key_status = 'cf7a_status_breakdown';
		$status_data      = wp_cache_get( $cache_key_status, 'cf7a_blacklist_stats' );

		if ( false === $status_data ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$status_data = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT status, COUNT(*) as count
			FROM %i
			GROUP BY status
			ORDER BY status ASC',
					$blacklist_table
				)
			);
			wp_cache_set( $cache_key_status, $status_data, 'cf7a_blacklist_stats', $cache_time_short );
		}

		// Group status into ranges
		$status_ranges = array(
			'1-5'    => 0,
			'6-10'   => 0,
			'11-25'  => 0,
			'26-50'  => 0,
			'51-100' => 0,
			'100+'   => 0,
		);

		foreach ( $status_data as $status ) {
			$status_num = intval( $status->status );
			$count      = intval( $status->count );

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
		$status_ranges = array_filter(
			$status_ranges,
			function ( $count ) {
				return $count > 0;
			}
		);

		// Get detailed reason stats with caching
		$cache_key_reasons = 'cf7a_reason_counts';
		$reason_counts     = wp_cache_get( $cache_key_reasons, 'cf7a_blacklist_stats' );

		if ( false === $reason_counts ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$meta_data = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT meta
			FROM %i
			WHERE meta IS NOT NULL AND meta != '' AND meta != 'a:0:{}'",
					$wpdb->prefix . 'cf7a_blacklist'
				)
			);

			$reason_counts = array();
			foreach ( $meta_data as $row ) {
				$decoded_meta = unserialize( $row->meta ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize

				if ( is_array( $decoded_meta ) ) {
					foreach ( $decoded_meta as $entry ) {
						if ( is_array( $entry ) ) {
							// Count each reason type within the reason array.
							foreach ( $entry as $reason_key => $reason_value ) {
								// Convert reason key to readable format.
								$reason_name = $this->format_reason_name( $reason_key );

								if ( ! isset( $reason_counts[ $reason_name ] ) ) {
									$reason_counts[ $reason_name ] = 0;
								}
								++$reason_counts[ $reason_name ];
							}
						}
					}
				}
			}

			wp_cache_set( $cache_key_reasons, $reason_counts, 'cf7a_blacklist_stats', $cache_time_short );
		}

		// Sort reasons by count and get top 5
		arsort( $reason_counts );
		$top_reasons = array_slice( $reason_counts, 0, 5, true );

		// Get top 10 spam words with caching
		$cache_key_spam = 'cf7a_top_spam_words';
		$top_spam_words = wp_cache_get( $cache_key_spam, 'cf7a_wordlist_stats' );

		if ( false === $top_spam_words ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$top_spam_words = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT token, count_spam
			FROM %i
			WHERE count_spam > 0 AND token != 'b8*texts' AND token != 'b8*dbversion'
			ORDER BY count_spam DESC
			LIMIT 10",
					$wpdb->prefix . 'cf7a_wordlist'
				)
			);
			wp_cache_set( $cache_key_spam, $top_spam_words, 'cf7a_wordlist_stats', $cache_time_long );
		}

		// Get top 10 ham words with caching
		$cache_key_ham = 'cf7a_top_ham_words';
		$top_ham_words = wp_cache_get( $cache_key_ham, 'cf7a_wordlist_stats' );

		if ( false === $top_ham_words ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$top_ham_words = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT token, count_ham
			FROM %i
			WHERE count_ham > 0 AND token != 'b8*texts' AND token != 'b8*dbversion'
			ORDER BY count_ham DESC
			LIMIT 10",
					$wpdb->prefix . 'cf7a_wordlist'
				)
			);
			wp_cache_set( $cache_key_ham, $top_ham_words, 'cf7a_wordlist_stats', $cache_time_long );
		}
		?>
		<div class="cf7a-stats-grid">

			<!-- Status Breakdown by Ranges -->
			<div class="cf7a-stat-card cf7a-stat-card-wide">
				<div class="cf7a-stat-label"><?php esc_html_e( 'Warning Count Ranges', 'cf7-antispam' ); ?></div>

				<div class="cf7a-stat-recap fit-center">
					<div class="cf7a-stat-number"><?php echo esc_html( $total_blocked ?: '0' ); ?></div>
					<div class="cf7a-stat-label"><?php esc_html_e( 'Total Blocked IPs', 'cf7-antispam' ); ?></div>
				</div>

				<div class="cf7a-status-breakdown">
					<?php if ( ! empty( $status_ranges ) ) : ?>
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

			<!-- B8 wordlist -->
			<div class="cf7a-stat-card cf7a-stat-card-wide">
				<div class="cf7a-stat-label"><?php esc_html_e( 'B8 Wordlist', 'cf7-antispam' ); ?></div>

				<div class="cf7a-wordlist-breakdown">
					<!-- Top Spam Words -->
					<div class="cf7a-wordlist-column">
						<h4><?php esc_html_e( 'Top Spam Words', 'cf7-antispam' ); ?></h4>
						<?php if ( ! empty( $top_spam_words ) ) : ?>
							<?php foreach ( $top_spam_words as $word ) : ?>
								<div class="cf7a-word-item">
						<span class="cf7a-word-name" title="<?php echo esc_attr( $word->token ); ?>">
								<?php echo esc_html( $word->token ); ?>
						</span>
									<span class="cf7a-word-count"><?php echo esc_html( $word->count_spam ); ?></span>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<p><?php esc_html_e( 'No spam words available', 'cf7-antispam' ); ?></p>
						<?php endif; ?>
					</div>

					<!-- Top Ham Words -->
					<div class="cf7a-wordlist-column">
						<h4><?php esc_html_e( 'Top Ham Words', 'cf7-antispam' ); ?></h4>
						<?php if ( ! empty( $top_ham_words ) ) : ?>
							<?php foreach ( $top_ham_words as $word ) : ?>
								<div class="cf7a-word-item">
						<span class="cf7a-word-name" title="<?php echo esc_attr( $word->token ); ?>">
								<?php echo esc_html( $word->token ); ?>
						</span>
									<span class="cf7a-word-count"><?php echo esc_html( $word->count_ham ); ?></span>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<p><?php esc_html_e( 'No ham words available', 'cf7-antispam' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>


			<!-- Top Reasons -->
			<div class="cf7a-stat-card cf7a-stat-card-wide">
				<div class="cf7a-stat-label"><?php esc_html_e( 'Top Block Reasons', 'cf7-antispam' ); ?></div>
				<div class="cf7a-reasons-breakdown">
					<?php if ( ! empty( $top_reasons ) ) : ?>
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
								<em>
								<?php
								printf(
									/* translators: %d is the number of unique reasons */
									esc_html__( 'Total unique reasons: %d', 'cf7-antispam' ),
									count( $reason_counts )
								);
								?>
								</em>
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
			'blacklisted_score'      => 'Recidive',
			'blacklisted score'      => 'Recidive',
			'data_mismatch'          => 'Data Mismatch',
			'bot_fingerprint'        => 'Bot Fingerprint',
			'bot_fingerprint_extras' => 'Bot Fingerprint Extras',
			'browser_language'       => 'Browser Language',
			'honeypot'               => 'Honeypot',
			'b8'                     => 'B8 Filter',
			'geo_location'           => 'Geo Location',
			'ip_reputation'          => 'IP Reputation',
			'user_agent'             => 'User Agent',
			'disposable_email'       => 'Disposable Email',
			'spam_words'             => 'Spam Words',
		);

		// Check if we have a custom mapping
		if ( isset( $reason_mappings[ $reason_key ] ) ) {
			return $reason_mappings[ $reason_key ];
		}

		// Default formatting: replace underscores with spaces and capitalize
		return ucwords( str_replace( '_', ' ', $reason_key ) );
	}

	/**
	 * It returns a random tip from an array of tips
	 *
	 * @return string a random tip from the array of tips.
	 */
	public function cf7a_get_a_random_tip() {
		$tips = array(
			__( 'Did you know? You can customize the spam score threshold for individual filters in the Settings tab to fine-tune protection.', 'cf7-antispam' ),
			__( 'Tip: Enable the Flamingo plugin to unlock advanced B8 Bayesian filtering, which learns from your ham and spam messages.', 'cf7-antispam' ),
			__( 'Secure your forms by blocking specific languages. Go to Settings > Language to disallow messages in languages irrelevant to your business.', 'cf7-antispam' ),
			__( 'Use GeoIP filtering to block submissions from specific countries or continents often associated with spam.', 'cf7-antispam' ),
			__( 'The Honeypot feature adds a hidden field that only bots fill out. Ensure it\'s enabled in the Settings for effortless protection.', 'cf7-antispam' ),
			__( 'Too fast? The "Time Submission" check flags forms submitted inhumanly quickly. You can adjust the minimum time required.', 'cf7-antispam' ),
			__( 'Check the Blocklist tab to see blocked IPs. You can manually ban or unban IPs and view the reasons for their blocking.', 'cf7-antispam' ),
			__( 'Have a trusted static IP? Add it to the IP Whitelist in Settings to ensure your own tests or admin submissions are never blocked.', 'cf7-antispam' ),
			__( 'The "Max Attempts" setting automatically blocks IPs that repeatedly trigger spam filters. Adjust this limit to be stricter or more lenient.', 'cf7-antispam' ),
			__( 'Browser fingerprinting helps identify bots even if they change IPs. Ensure "Check Bot Fingerprint" is active for robust detection.', 'cf7-antispam' ),
		);

		return $tips[ round( wp_rand( 0, count( $tips ) - 1 ) ) ];
	}

	/**
	 * It prints The main setting text below the title
	 */
	public function cf7a_print_section_options_subtitle() {
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
	 * Render the Settings Tab
	 */
	private function render_settings_tab() {
		?>
		<div class="cf7a-card">
			<h3><?php esc_html_e( 'Plugin Settings', 'cf7-antispam' ); ?></h3>
			<?php $this->cf7a_print_section_options_subtitle(); ?>
			<?php $this->cf7a_get_debug_info_forms(); ?>
			<form method="post" action="options.php" id="cf7a_settings" enctype="multipart/form-data">
				<?php
				settings_fields( 'cf7_antispam_options' );
				do_settings_sections( 'cf7a-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	private function cf7a_export_button() {
		printf(
			'<p class="cf7a-export-blocklist-button alignright"><button class="button cf7a_export_action" data-action="export-blocklist" data-nonce="%s">%s</button></p>',
			esc_attr( wp_create_nonce( 'cf7a-nonce' ) ),
			esc_html__( 'Export blocklist', 'cf7-antispam' )
		);
	}

	/**
	 * Render the Blocklist Tab
	 */
	private function render_blacklist_tab() {
		?>
		<div class="cf7a-card">
			<?php $this->cf7a_export_button(); ?>
			<h3><?php esc_html_e( 'Blacklisted IPs', 'cf7-antispam' ); ?></h3>
			<p><?php esc_html_e( 'Here you can see all the IPs that have been blacklisted by the plugin.', 'cf7-antispam' ); ?></p>
			<?php $this->cf7a_get_blacklisted_table(); ?>
		</div>
		<?php
	}

	/**
	 * Render the Blocklist Tab
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
	 * Render the Tools Tab
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
	 * Render the advanced tools section
	 */
	private function render_advanced_tools() {
		$nonce = wp_create_nonce( 'cf7a-nonce' );
		?>

		<h4><?php esc_html_e( 'Update Database', 'cf7-antispam' ); ?></h4>
		<p><?php esc_html_e( 'If something has gone wrong during updates, you can perform a forced database and options update.', 'cf7-antispam' ); ?></p>
		<button class="cf7a_action-button cf7a_action cf7a-action-info" data-action="force-update" data-nonce="<?php echo esc_attr( $nonce ); ?>" ><?php esc_html_e( 'Update Database', 'cf7-antispam' ); ?></button>

		<div class="cf7a-danger-zone">
			<h3><?php esc_html_e( 'Danger Zone', 'cf7-antispam' ); ?></h3>
			<p><?php esc_html_e( 'These actions are irreversible. Please make sure you know what you are doing.', 'cf7-antispam' ); ?></p>

			<h4><?php esc_html_e( 'Blocklist Reset', 'cf7-antispam' ); ?></h4>
			<p><?php esc_html_e( 'Remove all blacklisted IPs from the database.', 'cf7-antispam' ); ?></p>
			<button class="cf7a_action-button cf7a_action cf7a-action-danger" data-action="reset-blocklist" data-nonce="<?php echo esc_attr( $nonce ); ?>" ><?php esc_html_e( 'Remove all blacklisted IP', 'cf7-antispam' ); ?></button>

			<h4><?php esc_html_e( 'Dictionary Reset', 'cf7-antispam' ); ?></h4>
			<p><?php esc_html_e( 'Reset the entire b8 dictionary used for spam detection.', 'cf7-antispam' ); ?></p>
			<button class="cf7a_action-button cf7a_action cf7a-action-danger" data-action="reset-dictionary" data-nonce="<?php echo esc_attr( $nonce ); ?>" ><?php esc_html_e( 'Reset b8 dictionary', 'cf7-antispam' ); ?></button>

			<h4><?php esc_html_e( 'Rebuild Dictionary', 'cf7-antispam' ); ?></h4>
			<p><?php esc_html_e( 'Reanalyze all Flamingo inbound emails to rebuild the dictionary.', 'cf7-antispam' ); ?></p>
			<button class="cf7a_action-button cf7a_action cf7a-action-danger" data-action="rebuild-dictionary" data-nonce="<?php echo esc_attr( $nonce ); ?>" ><?php esc_html_e( 'Rebuild b8 dictionary', 'cf7-antispam' ); ?></button>

			<h4><?php esc_html_e( 'Full Reset', 'cf7-antispam' ); ?></h4>
			<p><?php esc_html_e( 'Completely reset the plugin to its initial state.', 'cf7-antispam' ); ?></p>
			<button class="cf7a_action-button cf7a_action cf7a-action-danger" data-action="full-reset" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-message="<?php esc_html_e( 'Are you sure? This will reset the plugin to its initial state.', 'cf7-antispam' ); ?>" ><?php esc_html_e( 'FULL RESET', 'cf7-antispam' ); ?></button>
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
			<textarea id="cf7a_options_area" rows="20" style="width: 100%;" data-nonce="<?php echo esc_attr( wp_create_nonce( 'cf7a-nonce' ) ); ?>"><?php echo wp_json_encode( $this->options, JSON_PRETTY_PRINT ); ?></textarea>

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
		<div class="cf7a-card card-debug">
			<h2 class="title cf7a-card-title"><?php esc_html_e( 'Debug Information', 'cf7-antispam' ); ?></h2>
			<p><?php esc_html_e( 'Debug information is only visible when WP_DEBUG or CF7ANTISPAM_DEBUG are enabled.', 'cf7-antispam' ); ?></p>
			<?php $this->cf7a_get_debug_info(); ?>
		</div>
		<?php
	}

	/**
	 * It gets the blacklisted IPs from the database and displays them in a table
	 */
	public static function cf7a_get_blacklisted_table() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$blacklisted = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY `status` DESC LIMIT 1000', $wpdb->prefix . 'cf7a_blacklist' ) );
		$nonce       = wp_create_nonce( 'cf7a-nonce' );

		if ( $blacklisted ) {
			$count = count( $blacklisted );
			$rows  = '';

			foreach ( $blacklisted as $row ) {
				$meta         = unserialize( $row->meta );
				$max_attempts = intval( get_option( 'cf7a_options' )['max_attempts'] );

				$rows .= sprintf(
					'<div class="row row-%s"><div class="status">%s</div><div><p class="ip">%s <small class="actions"><span class="cf7a_action" data-action="unban-ip" data-id="%s" data-nonce="%s" data-callback="hide">%s</span> <span class="cf7a_action" data-action="ban-forever" data-id="%s" data-nonce="%s" data-callback="hide">%s</span></small></p><span class="data">%s</span><span class="data date"><b>%s:</b> %s</span></div></div>',
					esc_attr( intval( $row->id ) ),
					cf7a_format_status( $row->status - $max_attempts ),
					esc_html( $row->ip ),
					esc_attr( $row->id ),
					esc_attr( $nonce ),
					esc_html__( '[unban ip]', 'cf7-antispam' ),
					esc_attr( $row->id ),
					esc_attr( $nonce ),
					esc_html__( '[ban forever]', 'cf7-antispam' ),
					cf7a_compress_array( $meta['reason'], true ),
					esc_html__( 'First seen on', 'cf7-antispam' ),
					$row->created
				);
			}

			/* The table */
			printf(
				'<div class="widefat blocklist-table">%s</div><p><small>%s</small></p>',
				wp_kses(
					$rows,
					array(
						'div'   => array( 'class' => array() ),
						'small' => array( 'class' => array() ),
						'p'     => array( 'class' => array() ),
						'a'     => array( 'href' => array() ),
						'b'     => array(),
						'br'    => array(),
						'span'  => array(
							'class'         => array(),
							'style'         => array(),
							'data-action'   => array(),
							'data-id'       => array(),
							'data-nonce'    => array(),
							'data-callback' => array(),
						),
					)
				),
				sprintf(
					/* translators: %d is the number of blacklisted IPs */
					esc_html__( 'Showing %d blacklisted IPs', 'cf7-antispam' ),
					intval( $count )
				)
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

				printf(
					'<p class="debug"><code>%s</code> %s</p>',
					esc_html__( 'Your ip address', 'cf7-antispam' ),
					filter_var( cf7a_get_real_ip(), FILTER_VALIDATE_IP )
				);
			} else {
				printf(
					'<p class="debug">%s</p>',
					'<code>CF7ANTISPAM_DEBUG_EXTENDED</code> ' . esc_html( __( 'is disabled, use CF7ANTISPAM_DEBUG_EXTENDED to enable it if needed', 'cf7-antispam' ) )
				);
			}

			if ( ! empty( $this->options['check_language'] ) ) {
				$result = $this->cf7a_get_debug_info_geoip();
				if ( $result ) {
					printf(
						'<h3 class="title"><span class="dashicons dashicons-location"></span> %s</h3>%s',
						esc_html( $result['title'] ),
						$result['content'] // phpcs:ignore WordPress.Security.EscapeOutput
					);
				}
			} else {
				printf(
					'<h3 class="title"><span class="dashicons dashicons-location"></span> GeoIP</h3><p><b>GeoIP</b> %s</p>',
					esc_html__( 'is disabled', 'cf7-antispam' )
				);
			}

			if ( ! empty( $this->options['check_dnsbl'] ) && ! empty( $this->options['dnsbl_list'] ) ) {
				$this->cf7a_get_debug_info_dnsbl();
			} else {
				printf(
					'<h3 class="title"><span class="dashicons dashicons-networking"></span> DNSBL</h3><p><b>DNSBL</b> %s</p>',
					esc_html__( 'is disabled', 'cf7-antispam' )
				);
			}

			$this->cf7a_get_debug_info_rest_api();

			$this->cf7a_get_debug_info_options();
		}
	}

	/**
	 * It returns a string containing a formatted HTML table with the Contact Form 7 forms information
	 *
	 * @return void
	 */
	private function cf7a_get_debug_info_forms() {
		if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
			return;
		}

		$forms = \WPCF7_ContactForm::find(
			array(
				'posts_per_page' => -1,
			)
		);

		if ( empty( $forms ) ) {
			printf(
				'<h3>%s</h3><p>%s</p>',
				esc_html__( 'Contact Forms', 'cf7-antispam' ),
				esc_html__( 'No Contact Form 7 forms found. Please create a form before using this plugin.', 'cf7-antispam' )
			);
			return;
		}

		// loop through forms and check if flamingo_message is set
		$form_has_missing_tag = false;
		foreach ( $forms as $form ) {
			$flamingo_message_val = $form->pref( 'flamingo_message' );
			if ( empty( $flamingo_message_val ) ) {
				$form_has_missing_tag = true;
				break;
			}
		}

		// if is set for all forms there is no reason to show the table
		if ( ! $form_has_missing_tag ) {
			return;
		}

		$rows = '';
		foreach ( $forms as $form ) {
			$flamingo_message_val = $form->pref( 'flamingo_message' );
			$has_correct_field    = ! empty( $flamingo_message_val );

			$status_icon = $has_correct_field
				? '<span class="dashicons dashicons-yes" style="color: #46b450;"></span>'
				: '<span class="dashicons dashicons-warning" style="color: #ffb900;"></span>';

			$rows .= sprintf(
				'<tr>
					<td>%d</td>
					<td><a href="%s" target="_blank">%s</a></td>
					<td><code>%s</code></td>
					<td>%s</td>
				</tr>',
				$form->id(),
				admin_url( 'admin.php?page=wpcf7&post=' . $form->id() . '&action=edit' ),
				esc_html( $form->title() ),
				esc_html( $flamingo_message_val ?: '-' ),
				$status_icon
			);
		}

		printf(
			'<h3>%s</h3>
	<p>%s<code>%s</code>%s</p>
	<p>%s</p>
	<table class="widefat striped" style="margin-top: 10px; max-width: 760px;">
		<thead>
			<tr>
				<th>ID</th>
				<th>%s</th>
				<th>%s</th>
				<th>%s</th>
			</tr>
		</thead>
		<tbody>
			%s
		</tbody>
	</table>
	<hr style="margin: 2rem 0 0;" />',
			esc_html__( 'Contact Forms Configuration', 'cf7-antispam' ),
			esc_html__( 'Please ensure that the Flamingo message tag is correctly configured. This tag tells the plugin which textarea field contains the message content. Add the following in the Additional Settings tab:', 'cf7-antispam' ),
			esc_html( 'flamingo_message: "[your-message-field]"' ),
			esc_html__( ' (replace [your-message-field] with the actual name of your textarea field).', 'cf7-antispam' ),
			esc_html__( 'If the field is not defined, the plugin will try to detect it automatically. It will first look for a textarea named similar to "message". If nothing is found, it will merge all input fields together (excluding phone and email fields, and fields shorter than 20 characters). This fallback is not always accurate, so manual configuration is recommended.', 'cf7-antispam' ),
			esc_html__( 'Form Name & Link', 'cf7-antispam' ),
			esc_html__( 'Flamingo Message Value', 'cf7-antispam' ),
			esc_html__( 'Valid', 'cf7-antispam' ),
			$rows // phpcs:ignore WordPress.Security.EscapeOutput
		);
	}

	/**
	 * It returns a string containing a formatted HTML table with the plugin's options
	 *
	 * @return void the HTML for the debug info options.
	 */
	private function cf7a_get_debug_info_rest_api() {
		printf(
			'<h3 class="title"><span class="dashicons dashicons-rest-api"></span> Rest API</h3><p><b>Rest API</b><div id="rest-api-status" class="waiting">%s</div></p>',
			esc_html__( 'Waiting for Rest API Status...', 'cf7-antispam' )
		);
	}

	private function get_plugin_version( $plugin_file ) {
		if ( file_exists( $plugin_file ) ) {
			$plugin_data = get_plugin_data( $plugin_file );
			if ( ! empty( $plugin_data['Version'] ) ) {
				return $plugin_data['Version'];
			}
		}
		return 'Not installed';
	}

	/**
	 * It returns a string containing a formatted HTML table with the plugin's options
	 *
	 * @return void the HTML for the debug info options.
	 */
	private function cf7a_get_debug_info_options() {
		global $wpdb;
		$cf7_plugin_file      = WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php';
		$flamingo_plugin_file = WP_PLUGIN_DIR . '/flamingo/flamingo.php';
		$debug_data           = array(
			'cf7a_version'           => CF7ANTISPAM_VERSION,
			'cf7a_options'           => $this->options,
			'wp_version'             => get_bloginfo( 'version' ),
			'contact_form_7_version' => $this->get_plugin_version( $cf7_plugin_file ),
			'flamingo_version'       => $this->get_plugin_version( $flamingo_plugin_file ),
			'php_version'            => PHP_VERSION,
			'mysql_version'          => $wpdb->db_version(),
			'plugins'                => array_map(
				function ( $plugin ) {
					return $plugin['Name'] . ' (' . $plugin['Version'] . ')';
				},
				get_plugins()
			),
			'wp_debug'               => WP_DEBUG ? 'Enabled' : 'Disabled',
			'wp_debug_log'           => WP_DEBUG_LOG ? 'Enabled' : 'Disabled',
			'wp_debug_display'       => WP_DEBUG_DISPLAY ? 'Enabled' : 'Disabled',
			'wp_memory_limit'        => WP_MEMORY_LIMIT,
			'php_memory_limit'       => ini_get( 'memory_limit' ),
			'upload_max_size'        => ini_get( 'upload_max_size' ),
			'post_max_size'          => ini_get( 'post_max_size' ),
		);
		printf( '<h2 class="title">%s</h2>', esc_html__( 'Options debug', 'cf7-antispam' ) );
		printf(
			'<p>%s</p><pre class="codeblock"><code>%s</code></pre>',
			esc_html__( 'The plugin options are:', 'cf7-antispam' ),
			esc_html(
				htmlentities(
					print_r( $debug_data, true ) // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
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
						$is_spam ? esc_html__( 'spam', 'cf7-antispam' ) : esc_html__( 'ham', 'cf7-antispam' ),
						$time_taken
					);
			}

			if ( ! empty( $performance_test ) ) {
				printf(
					'<h3 class="title"><span class="dashicons dashicons-privacy"></span> %s</h3><p>%s</p><p>%s: %s</p><table class="dnsbl_table">%s</table>',
					esc_html__( 'DNSBL performance test:', 'cf7-antispam' ),
					esc_html__( 'Results below 0.01 are fine, OK/Spam indicates the status of your ip on DNSBL servers', 'cf7-antispam' ),
					esc_html__( 'Your IP address', 'cf7-antispam' ),
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
	 *
	 * @return array an array with the title and content of the debug info geoip.
	 */
	private static function cf7a_get_debug_info_geoip() {
		try {
			$cf7a_geo = new CF7_Antispam_Geoip();

			$geoip_update = $cf7a_geo->next_update ? esc_html( date_i18n( get_option( 'date_format' ), $cf7a_geo->next_update ) ) : esc_html__( 'not set', 'cf7-antispam' );

			/* get the usar IP and check it against the GeoIP database */
			$your_ip     = cf7a_get_real_ip();
			$server_data = $cf7a_geo->check_ip( $your_ip );

			/* if the server_data is empty, set it to a string */
			if ( empty( $server_data ) ) {
				$server_data = 'Unable to retrieve geoip information for ' . $your_ip;
			}

			$res = array(
				'title' => esc_html__( 'Geo-IP test', 'cf7-antispam' ),
			);

			/* The recap of Geo-ip test */
			if ( ! empty( $cf7a_geo->next_update ) ) {

				$html_update_schedule = sprintf(
					'<p class="debug"><code>%s</code> %s</p>',
					esc_html__( 'Geo-IP', 'cf7-antispam' ),
					esc_html__( 'Enabled', 'cf7-antispam' ) . ' - ' . esc_html__( 'Geo-ip database next scheduled update: ', 'cf7-antispam' ) . $geoip_update
				);

				$res['content'] = sprintf(
					'<p>%s</p><p>%s: %s</p><pre>%s</pre>',
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
			} else {
				$res['content'] = sprintf(
					'<p><b>%s</b> %s</p><p>%s %s %s</p>',
					esc_html__( 'Geo-IP', 'cf7-antispam' ),
					esc_html__( 'is disabled.', 'cf7-antispam' ),
					esc_html__( 'To enable it, please go to the settings page and enable the "Detect location using GeoIP" checkbox.', 'cf7-antispam' ),
					esc_html__( 'Your IP address', 'cf7-antispam' ),
					$your_ip
				);
			}

			// return the result to the frontend
			return $res;

		} catch ( Exception $e ) {
			$error_message = $e->getMessage();
			return array(
				'title'   => esc_html__( 'Geo-IP test', 'cf7-antispam' ),
				'content' => sprintf( '<p>%s</p><pre>%s</pre>', esc_html__( 'Geo-IP Test Error', 'cf7-antispam' ), $error_message && $error_message['error'] ? esc_html( $error_message['error'] ) : 'error' ),
			);
		}
	}
}
