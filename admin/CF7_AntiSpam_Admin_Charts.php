<?php

namespace CF7_AntiSpam\Admin;

use WP_Query;

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/admin
 * @author     Codekraft Studio <info@codekraft.it>
 */
class CF7_AntiSpam_Admin_Charts {
	/**
	 * It queries the database for all the emails received in the last week, then it creates two lists:
	 * one with the number of emails received per day, and one with the number of emails received per type (ham or spam)
	 *
	 * @param int    $max_mail_count The maximum number of emails to retrieve
	 * @param string $date_after The date after which the emails will be retrieved
	 *
	 * @return WP_Query The query object
	 */
	public function cf7a_get_flamingo_stats( int $max_mail_count, string $date_after = '1 week ago' ): WP_Query {
			$args = array(
				'post_type'      => 'flamingo_inbound',
				'post_status'    => array( 'flamingo-spam', 'publish' ),
				'posts_per_page' => $max_mail_count,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'date_query'     => array(
					array(
						'after' => $date_after,
					),
				),
			);

			return new WP_Query( $args );
	}

	/**
	 * Get blocked IPs grouped by date.
	 *
	 * @param string $date_after The start date.
	 * @return array
	 */
	private function cf7a_get_blocked_ips_by_date( string $date_after ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7a_blocklist';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$query = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'SELECT DATE(created) as date, COUNT(*) as count FROM ' . $table_name . ' WHERE created >= %s GROUP BY DATE(created) ORDER BY DATE(created) ASC',
			gmdate( 'Y-m-d H:i:s', strtotime( $date_after ) )
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $query, ARRAY_A );
		$data    = array();
		if ( $results ) {
			foreach ( $results as $row ) {
				$data[ $row['date'] ] = (int) $row['count'];
			}
		}
		return $data;
	}

	/**
	 * Get blocked comments grouped by date.
	 *
	 * @param string $date_after The start date.
	 * @return array
	 */
	private function cf7a_get_blocked_comments_by_date( string $date_after ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT DATE(c.comment_date) as date, COUNT(*) as count
			FROM {$wpdb->comments} c
			INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id
			WHERE cm.meta_key = '_cf7a_spam_reasons'
			AND c.comment_date >= %s
			GROUP BY DATE(c.comment_date)
			ORDER BY DATE(c.comment_date) ASC",
			gmdate( 'Y-m-d H:i:s', strtotime( $date_after ) )
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $query, ARRAY_A );
		$data    = array();
		if ( $results ) {
			foreach ( $results as $row ) {
				$data[ $row['date'] ] = (int) $row['count'];
			}
		}
		return $data;
	}

	/**
	 * Get recent blocked IPs.
	 *
	 * @param int    $limit The max items.
	 * @param string $date_after The start date.
	 * @return array
	 */
	private function cf7a_get_recent_blocked_ips( int $limit, string $date_after ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7a_blocklist';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$query = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'SELECT id, ip, created, meta FROM ' . $table_name . ' WHERE created >= %s ORDER BY created DESC LIMIT %d',
			gmdate( 'Y-m-d H:i:s', strtotime( $date_after ) ),
			$limit
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $query, ARRAY_A ) ?: array();
	}

	/**
	 * Get recent blocked comments.
	 *
	 * @param int    $limit The max items.
	 * @param string $date_after The start date.
	 * @return array
	 */
	private function cf7a_get_recent_blocked_comments( int $limit, string $date_after ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT c.comment_ID, c.comment_author, c.comment_content, c.comment_date
			FROM {$wpdb->comments} c
			INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id
			WHERE cm.meta_key = '_cf7a_spam_reasons'
			AND c.comment_date >= %s
			ORDER BY c.comment_date DESC LIMIT %d",
			gmdate( 'Y-m-d H:i:s', strtotime( $date_after ) ),
			$limit
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $query, ARRAY_A ) ?: array();
	}

	/**
	 * Processes email query results and organizes them by type and date
	 *
	 * @param WP_Query $query The query object containing email posts
	 * @return array Organized mail collection with by_type and by_date arrays
	 */
	private function cf7a_process_mail_collection( $query ) {
			$mail_collection = array(
				'by_type' => array(
					'ham'  => 0,
					'spam' => 0,
				),
				'by_date' => array(),
			);

			if ( ! $query ) {
				return $mail_collection;
			}

			while ( $query->have_posts() ) {
					$query->the_post();
					global $post;

					$is_ham = 'flamingo-spam' !== $post->post_status;
					$today  = esc_html( get_the_date( 'Y-m-d' ) );

					/* Initialize the date array if not exists */
				if ( ! isset( $mail_collection['by_date'][ $today ] ) ) {
						$mail_collection['by_date'][ $today ] = array();
				}

					/* Count by type */
					++$mail_collection['by_type'][ $is_ham ? 'ham' : 'spam' ];

					/* Store by date */
					$mail_collection['by_date'][ $today ][] = array(
						'status' => $is_ham ? 'ham' : 'spam',
					);
			}

			wp_reset_postdata();
			return $mail_collection;
	}

	/**
	 * Renders the recent activity list HTML
	 *
	 * @param WP_Query|null $flamingo_query The query object containing email posts
	 * @param array         $recent_ips The recent blocked IPs
	 * @param array         $recent_comments The recent blocked comments
	 */
	private function cf7a_render_recent_activity_list( $flamingo_query, $recent_ips, $recent_comments ) {
		$items = array();

		// Add Flamingo emails
		if ( $flamingo_query && $flamingo_query->have_posts() ) {
			foreach ( $flamingo_query->posts as $p ) {
				$is_ham  = 'flamingo-spam' !== $p->post_status;
				$excerpt = function_exists( 'mb_substr' ) ? mb_substr( $p->post_content, 0, 16 ) : substr( $p->post_content, 0, 16 );
				$items[] = array(
					'timestamp' => strtotime( $p->post_date ),
					'date'      => wp_date( 'Y-m-d', strtotime( $p->post_date ) ),
					'html'      => sprintf(
						'<li class="cf7-a_list-item"><span class="timestamp">%s </span><span>%s</span> %s <a href="%s" data-post-id="%s"><strong>%s</strong></a> - %s</li>',
						esc_html( wp_date( 'Y-m-d', strtotime( $p->post_date ) ) ),
						$is_ham ? '🔵' : '🔴',
						esc_html__( 'Mail:', 'cf7-antispam' ),
						esc_url( admin_url( 'admin.php?page=flamingo_inbound&post=' . $p->ID . '&action=edit' ) ),
						(int) $p->ID,
						esc_html( get_post_meta( $p->ID, '_from', true ) ?? '' ),
						esc_html( $excerpt . '...' )
					),
				);
			}
		}

		// Add Blocked IPs
		$options      = \CF7_AntiSpam\Core\CF7_AntiSpam::get_options();
		$geoip_active = ! empty( $options['check_geo_location'] ) || ! empty( $options['check_language'] );

		foreach ( $recent_ips as $ip_row ) {
			$flag_html = '';
			if ( $geoip_active && ! empty( $ip_row['meta'] ) ) {
				$meta = maybe_unserialize( $ip_row['meta'] );
				if ( ! empty( $meta['country'] ) ) {
					$iso_code  = strtolower( $meta['country'] );
					$flag_html = sprintf(
						'<img src="%s/assets/flags/%s.svg" width="16" alt="%s" class="cf7a-country-flag" style="vertical-align: middle; margin-right: 5px; display: inline-block;"> ',
						esc_url( CF7ANTISPAM_PLUGIN_URL ),
						esc_attr( $iso_code ),
						esc_attr( strtoupper( $iso_code ) )
					);
				}
			}

			$reason_str = '';
			if ( ! empty( $ip_row['meta'] ) ) {
				$meta_data = maybe_unserialize( $ip_row['meta'] );
				if ( ! empty( $meta_data['reason'] ) ) {
					$reasons     = is_string( $meta_data['reason'] ) ? array( $meta_data['reason'] ) : array_keys( $meta_data['reason'] );
					$reason_text = implode(
						', ',
						array_map(
							function ( $r ) {
								return ucwords( str_replace( '_', ' ', $r ) );
							},
							$reasons
						)
					);
					$reason_str  = ' (' . $reason_text . ')';
				}
			}

			$items[] = array(
				'timestamp' => strtotime( $ip_row['created'] ),
				'date'      => wp_date( 'Y-m-d', strtotime( $ip_row['created'] ) ),
				'html'      => sprintf(
					'<li class="cf7-a_list-item"><span class="timestamp">%s </span><span>🟠</span> %s%s<a href="%s" class="cf7a-ip-%s"><strong>%s</strong></a>%s</li>',
					esc_html( wp_date( 'Y-m-d', strtotime( $ip_row['created'] ) ) ),
					$flag_html,
					esc_html__( 'Blocked: ', 'cf7-antispam' ),
					esc_url( admin_url( 'admin.php?page=cf7-antispam&tab=blocklist&s=' . rawurlencode( $ip_row['ip'] ) ) ),
					esc_attr( str_replace( '.', '-', $ip_row['ip'] ) ),
					esc_html( $ip_row['ip'] ),
					esc_html( $reason_str )
				),
			);
		}//end foreach

		// Add Blocked Comments
		foreach ( $recent_comments as $comment_row ) {
			$excerpt = function_exists( 'mb_substr' ) ? mb_substr( $comment_row['comment_content'], 0, 16 ) : substr( $comment_row['comment_content'], 0, 16 );
			$items[] = array(
				'timestamp' => strtotime( $comment_row['comment_date'] ),
				'date'      => wp_date( 'Y-m-d', strtotime( $comment_row['comment_date'] ) ),
				'html'      => sprintf(
					'<li class="cf7-a_list-item"><span class="timestamp">%s </span><span>🟣</span> %s <a href="%s"><strong>%s</strong></a> - %s</li>',
					esc_html( wp_date( 'Y-m-d', strtotime( $comment_row['comment_date'] ) ) ),
					esc_html__( 'Comment:', 'cf7-antispam' ),
					esc_url( admin_url( 'comment.php?action=editcomment&c=' . $comment_row['comment_ID'] ) ),
					esc_html( $comment_row['comment_author'] ?: __( 'Anonymous', 'cf7-antispam' ) ),
					esc_html( $excerpt . '...' )
				),
			);
		}

		// Sort by timestamp DESC
		usort(
			$items,
			function ( $a, $b ) {
				return $b['timestamp'] <=> $a['timestamp'];
			}
		);

		$total_items  = count( $items );
		$render_limit = 12;

		// Trim to limit
		$items = array_slice( $items, 0, $render_limit );

		echo '<div id="antispam-widget-list" class="activity-block">';

		if ( empty( $items ) ) {
			$this->cf7a_render_empty_state();
		} else {
			printf(
				'<h3>%s</h3>',
				esc_html__( 'Recent Activity', 'cf7-antispam' )
			);
			echo '<ul>';
			foreach ( $items as $item ) {
				// We already escaped the parts in the sprintf calls above.
				echo $item['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</ul>';

			if ( $total_items > $render_limit ) {
				echo '<p style="text-align: center; font-style: italic;">Too many results, please check the <a href="' . esc_url( admin_url( 'admin.php?page=cf7-antispam&tab=blocklist' ) ) . '">Blocklist</a>.</p>';
			}
		}

		echo '</div>';
	}

	/**
	 * Converts mail collection to chart data format
	 *
	 * @param array  $mail_collection The organized mail collection
	 * @param array  $ips_by_date Blocked IPs by date
	 * @param array  $comments_by_date Blocked comments by date
	 * @param string $date_after The date filter
	 * @return array Chart data
	 */
	private function cf7a_prepare_chart_data( $mail_collection, $ips_by_date, $comments_by_date, $date_after ) {
			$start_time = strtotime( $date_after );
			$end_time   = time();

			$dates        = array();
			$current_time = $start_time;
		while ( $current_time <= $end_time ) {
			$dates[]      = gmdate( 'Y-m-d', $current_time );
			$current_time = strtotime( '+1 day', $current_time );
		}

			$ham              = array();
			$spam             = array();
			$blocked_ips      = array();
			$blocked_comments = array();

		foreach ( $dates as $date ) {
			// Count emails for this date
			$ham_count  = 0;
			$spam_count = 0;
			if ( isset( $mail_collection['by_date'][ $date ] ) ) {
				foreach ( $mail_collection['by_date'][ $date ] as $item ) {
					if ( 'ham' === $item['status'] ) {
						++$ham_count;
					} else {
						++$spam_count;
					}
				}
			}
			$ham[]  = $ham_count;
			$spam[] = $spam_count;

			// Count IPs for this date
			$blocked_ips[] = isset( $ips_by_date[ $date ] ) ? $ips_by_date[ $date ] : 0;

			// Count comments for this date
			$blocked_comments[] = isset( $comments_by_date[ $date ] ) ? $comments_by_date[ $date ] : 0;
		}//end foreach

			return array(
				'dates'           => $dates,
				'ham'             => $ham,
				'spam'            => $spam,
				'blockedIps'      => $blocked_ips,
				'blockedComments' => $blocked_comments,
				'by_type'         => $mail_collection['by_type'],
			);
	}

	/**
	 * Retrieves and aggregates spammers by country from the blocklist table.
	 *
	 * @return array Array of countries and their spam counts.
	 */
	public function cf7a_get_spammers_by_country_data() {
		global $wpdb;
		$blocklist_table = $wpdb->prefix . 'cf7a_blocklist';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results( "SELECT meta FROM {$blocklist_table} WHERE meta IS NOT NULL", ARRAY_A );

		$country_counts = array();

		if ( $results ) {
			foreach ( $results as $row ) {
				if ( empty( $row['meta'] ) ) {
					continue;
				}
				$meta = maybe_unserialize( $row['meta'] );
				if ( is_array( $meta ) && ! empty( $meta['country'] ) ) {
					$country = sanitize_text_field( $meta['country'] );
					if ( ! isset( $country_counts[ $country ] ) ) {
						$country_counts[ $country ] = 0;
					}
					++$country_counts[ $country ];
				}
			}
		}

		// Sort by count descending
		arsort( $country_counts );

		return $country_counts;
	}

	/**
	 * Get pie chart data depending on GeoIP status.
	 *
	 * @return array
	 */
	public function cf7a_get_pie_chart_data() {
		$options      = \CF7_AntiSpam\Core\CF7_AntiSpam::get_options();
		$geoip_active = ! empty( $options['check_geo_location'] ) || ! empty( $options['check_language'] );

		if ( $geoip_active ) {
			return $this->cf7a_get_spammers_by_country_data();
		}

		$pie_data = array();

		if ( defined( 'FLAMINGO_VERSION' ) ) {
			// Fetch 30 days of data for pie chart.
			$query           = $this->cf7a_get_flamingo_stats( 500, '-30 days' );
			$mail_collection = $this->cf7a_process_mail_collection( $query );

			$pie_data['Ham']  = isset( $mail_collection['by_type']['ham'] ) ? $mail_collection['by_type']['ham'] : 0;
			$pie_data['Spam'] = isset( $mail_collection['by_type']['spam'] ) ? $mail_collection['by_type']['spam'] : 0;
		}

		return $pie_data;
	}

	/**
	 * Gather all dashboard stats data
	 *
	 * @param string $date_after The date after which to filter
	 * @param int    $limit Max items to return in activity list
	 * @return array Dashboard stats response array
	 */
	public function cf7a_get_dashboard_stats_data( string $date_after, int $limit ) {
		$stats = array(
			'chartData'    => array(),
			'activityHtml' => '',
			'isEmpty'      => true,
		);

		$flamingo_query  = null;
		$mail_collection = array(
			'by_type' => array(
				'ham'  => 0,
				'spam' => 0,
			),
			'by_date' => array(),
		);

		if ( defined( 'FLAMINGO_VERSION' ) ) {
			// +1 is requested because get_flamingo_stats uses posts_per_page
			// But we are grouping by date here, so max_mail_count might limit the date range incorrectly.
			// However we will stick to the existing method signature.
			// Usually for charts we want 500 or -1, but let's stick to $limit * 2 for now.
			$flamingo_query  = $this->cf7a_get_flamingo_stats( $limit * 10, $date_after );
			$mail_collection = $this->cf7a_process_mail_collection( $flamingo_query );
		}

		$ips_by_date      = $this->cf7a_get_blocked_ips_by_date( $date_after );
		$comments_by_date = $this->cf7a_get_blocked_comments_by_date( $date_after );

		$recent_ips      = $this->cf7a_get_recent_blocked_ips( $limit, $date_after );
		$recent_comments = $this->cf7a_get_recent_blocked_comments( $limit, $date_after );

		$stats['chartData'] = $this->cf7a_prepare_chart_data( $mail_collection, $ips_by_date, $comments_by_date, $date_after );

		ob_start();
		$this->cf7a_render_recent_activity_list( $flamingo_query, $recent_ips, $recent_comments );
		$stats['activityHtml'] = ob_get_clean();

		$total_activity   = array_sum( $stats['chartData']['ham'] ) + array_sum( $stats['chartData']['spam'] ) + array_sum( $stats['chartData']['blockedIps'] ) + array_sum( $stats['chartData']['blockedComments'] );
		$stats['isEmpty'] = ( 0 === $total_activity );

		$options      = \CF7_AntiSpam\Core\CF7_AntiSpam::get_options();
		$geoip_active = ! empty( $options['check_geo_location'] ) || ! empty( $options['check_language'] );

		$stats['chartData']['countryData'] = null;
		if ( $geoip_active ) {
			$stats['chartData']['countryData'] = $this->cf7a_get_spammers_by_country_data();
		}

		return $stats;
	}

	/**
	 * Renders the footer links
	 */
	private function cf7a_render_footer() {
		?>
			<p class="community-events-footer">
					<?php if ( defined( 'FLAMINGO_VERSION' ) ) : ?>
					<a href="<?php echo esc_url_raw( admin_url( 'admin.php?page=flamingo' ) ); ?>">
							<?php
							/* phpcs:ignore WordPress.WP.I18n.TextDomainMismatch */
							esc_html_e( 'Flamingo Inbound Messages', 'flamingo' );
							?>
							<span aria-hidden="true" class="dashicons dashicons-external"></span>
					</a>
					|
					<?php endif; ?>
					<a href="<?php echo esc_url_raw( admin_url( 'admin.php?page=cf7-antispam' ) ); ?>">
							<?php esc_html_e( 'CF7-Antispam setup', 'cf7-antispam' ); ?>
							<span aria-hidden="true" class="dashicons dashicons-external"></span>
					</a>
			</p>
			<?php
	}

	/**
	 * Renders empty state when no activity is found
	 */
	private function cf7a_render_empty_state() {
			printf(
				'<div class="cf7-a_widget-empty"><span class="dashicons dashicons-welcome-comments"></span><p>%s</p></div>',
				esc_html__( 'No activity found in the selected period.', 'cf7-antispam' )
			);
	}

	/**
	 * Render the widget loader skeleton and the fetch script
	 *
	 * @param string  $period The period to request
	 * @param string  $container_id The wrapper ID
	 * @param boolean $show_list Whether to show the activity list below charts
	 */
	private function cf7a_render_async_widget( $period, $container_id, $show_list = true ) {
		$options      = \CF7_AntiSpam\Core\CF7_AntiSpam::get_options();
		$geoip_active = ! empty( $options['check_geo_location'] ) || ! empty( $options['check_language'] );

		$api_url = rest_url( 'cf7-antispam/v1/dashboard-stats' );
		if ( 'year' === $period ) {
			$api_url = add_query_arg( 'period', 'year', $api_url );
		}

		$config = array(
			'url'   => $api_url,
			'nonce' => wp_create_nonce( 'wp_rest' ),
		);
		?>
		<div id="<?php echo esc_attr( $container_id ); ?>" class="cf7a-async-widget" data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
			<div class="cf7a-loader-wrapper" style="text-align: center; padding: 20px;">
				<span class="spinner is-active" style="float: none; margin: 0;"></span>
				<p><?php esc_html_e( 'Loading data...', 'cf7-antispam' ); ?></p>
			</div>
			<div class="cf7a-widget-content" style="display: none;">
				<div class="antispam-charts-container">
					<div class="antispam-charts-line">
						<canvas id="line-chart" width="400" height="200"></canvas>
					</div>
					<?php if ( 'year' === $period ) : ?>
						<?php if ( $geoip_active ) : ?>
						<div class="antispam-charts-pie country-pie-chart-wrapper">
							<canvas id="country-pie-chart" width="400" height="200"></canvas>
						</div>
						<?php else : ?>
						<div class="antispam-charts-pie">
							<canvas id="pie-chart" width="400" height="200"></canvas>
						</div>
						<?php endif; ?>
					<?php endif; ?>
				</div>
				<?php if ( $show_list ) : ?>
					<hr>
					<div class="cf7a-activity-list-wrapper"></div>
				<?php endif; ?>
				<?php $this->cf7a_render_footer(); ?>
			</div>
		</div>
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			var wrapper = document.getElementById('<?php echo esc_js( $container_id ); ?>');
			if ( ! wrapper ) return;
			var config = JSON.parse(wrapper.getAttribute('data-config'));

			fetch(config.url, {
				headers: {
					'X-WP-Nonce': config.nonce,
					'Accept': 'application/json'
				}
			})
			.then(function(response) { return response.json(); })
			.then(function(data) {
				wrapper.querySelector('.cf7a-loader-wrapper').style.display = 'none';
				wrapper.querySelector('.cf7a-widget-content').style.display = 'block';

				if ( data.isEmpty ) {
					var listWrapper = wrapper.querySelector('.cf7a-activity-list-wrapper');
					if ( listWrapper ) listWrapper.innerHTML = data.activityHtml;
					wrapper.querySelector('.antispam-charts-container').style.display = 'none';
				} else {
					var listWrapper = wrapper.querySelector('.cf7a-activity-list-wrapper');
					if ( listWrapper ) listWrapper.innerHTML = data.activityHtml;
					if ( window.cf7aInitCharts ) {
						window.cf7aInitCharts(data.chartData);
					} else {
						// Store it so charts.ts can pick it up when loaded
						window.spamChartData = data.chartData;
					}
				}
			})
			.catch(function(error) {
				console.error('Error fetching dashboard stats:', error);
				wrapper.querySelector('.cf7a-loader-wrapper').innerHTML = '<p>Error loading data.</p>';
			});
		});
		</script>
		<?php
	}

	/**
	 * Prints a widget with a chart displaying spam and ham mails received
	 */
	public function cf7a_flamingo_widget() {
		$this->cf7a_render_async_widget( 'week', 'antispam-widget-async' );
	}

	/**
	 * Prints a widget with a chart displaying spam and ham mails received
	 */
	public function cf7a_dash_charts() {
		$this->cf7a_render_async_widget( 'year', 'antispam-charts-async', false );
	}
}
