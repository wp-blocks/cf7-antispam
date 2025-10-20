<?php

namespace CF7_AntiSpam\Admin;

use WP_Query;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
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
	 * @param $max_mail_count int The maximum number of emails to retrieve
	 * @param $date_after string The date after which the emails will be retrieved
	 *
	 * @return WP_Query The query object
	 */
	public function cf7a_get_flamingo_stats( $max_mail_count, $date_after = '1 week ago' ) {
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
	 * Renders the email list HTML
	 *
	 * @param WP_Query $query The query object containing email posts
	 */
	private function cf7a_render_email_list( $query ) {
			echo '<div id="antispam-widget-list" class="activity-block">';
			echo '<h3>' . esc_html__( 'Last Week Emails', 'cf7-antispam' ) . '</h3>';
			echo '<ul>';

			$query->rewind_posts();
		while ( $query->have_posts() ) {
				$query->the_post();
				global $post;

				$is_ham = 'flamingo-spam' !== $post->post_status;

			if ( wp_date( 'Y-m-d' ) > wp_date( 'Y-m-d', strtotime( '-1 week' ) ) ) {
					printf(
						'<li class="cf7-a_list-item"><span class="timestamp">%s </span><a href="%s" value="post-id-%s"><span>%s</span> %s</a> - %s</li>',
						get_the_date( 'Y-m-d' ),
						esc_url( admin_url( 'admin.php?page=flamingo_inbound&post=' . $post->ID . '&action=edit' ) ),
						(int) $post->ID,
						$is_ham ? '🔵' : '🔴',
						esc_html( get_post_meta( $post->ID, '_from' )[0] ),
						esc_html( $post->post_title )
					);
			}
		}

			echo '</ul></div>';
			wp_reset_postdata();
	}

	/**
	 * Converts mail collection to chart data format
	 *
	 * @param array $mail_collection The organized mail collection
	 * @return array Chart data with ham and spam counts by date
	 */
	private function cf7a_prepare_chart_data( $mail_collection ) {
			$mail_collection['by_date'] = array_reverse( $mail_collection['by_date'] );
			$count                      = array();

			/* Process data by date */
		foreach ( $mail_collection['by_date'] as $date => $items ) {
			if ( ! isset( $count[ $date ] ) ) {
					$count[ $date ] = array(
						'ham'  => 0,
						'spam' => 0,
					);
			}

				/* Count items by status for each date */
			foreach ( $items as $item ) {
					++$count[ $date ][ $item['status'] ];
			}
		}

			/* Extract ham and spam arrays for chart */
			$ham  = array();
			$spam = array();

		foreach ( $count as $date_count ) {
				$ham[]  = $date_count['ham'];
				$spam[] = $date_count['spam'];
		}

			return array(
				'dates'   => array_keys( $mail_collection['by_date'] ),
				'ham'     => $ham,
				'spam'    => $spam,
				'by_type' => $mail_collection['by_type'],
			);
	}

	/**
	 * Renders the JavaScript chart data
	 *
	 * @param array $chart_data Prepared chart data
	 */
	private function cf7a_render_chart_script( $chart_data ) {
		?>
			<script>
					var spamChartData = {
							lineData: {
									labels: ["<?php echo wp_kses( implode( '","', $chart_data['dates'] ), array() ); ?>"],
									datasets: [{
											label: 'Ham',
											backgroundColor: 'rgb(38,137,218)',
											borderColor: 'rgb(34 113 177)',
											tension: 0.25,
											data: [<?php echo esc_html( implode( ',', $chart_data['ham'] ) ); ?>],
									},
									{
											label: 'Spam',
											backgroundColor: 'rgb(255,4,0)',
											borderColor: 'rgb(248, 49, 47)',
											tension: 0.25,
											data: [<?php echo esc_html( implode( ',', $chart_data['spam'] ) ); ?>],
									}]
							},
							pieData: {
									labels: ["ham","spam"],
									datasets: [{
											data: [<?php echo esc_html( $chart_data['by_type']['ham'] . ', ' . $chart_data['by_type']['spam'] ); ?>],
											backgroundColor: [
													'rgb(38,137,218)',
													'rgb(248,49,47)'
											]
									}]
							}
					}
			</script>
			<?php
	}

	/**
	 * Renders the footer links
	 */
	private function cf7a_render_footer() {
		?>
			<p class="community-events-footer">
					<a href="<?php echo esc_url_raw( admin_url( 'admin.php?page=flamingo' ) ); ?>">
							<?php
							/* phpcs:ignore WordPress.WP.I18n.TextDomainMismatch */
							esc_html_e( 'Flamingo Inbound Messages', 'flamingo' );
							?>
							<span aria-hidden="true" class="dashicons dashicons-external"></span>
					</a>
					|
					<a href="<?php echo esc_url_raw( admin_url( 'admin.php?page=cf7-antispam' ) ); ?>">
							<?php esc_html_e( 'CF7-Antispam setup', 'cf7-antispam' ); ?>
							<span aria-hidden="true" class="dashicons dashicons-external"></span>
					</a>
			</p>
			<?php
	}

	/**
	 * Renders empty state when no emails are found
	 */
	private function cf7a_render_empty_state() {
			printf(
				'<div class="cf7-a_widget-empty"><span class="dashicons dashicons-welcome-comments"></span><p>%s</p></div>',
				esc_html__( 'You have not received any e-mails in the last 7 days.', 'cf7-antispam' )
			);
	}

	/**
	 * Prints a widget with a chart displaying spam and ham mails received
	 *
	 * It queries the database for all the emails received in the last week, then it creates two lists:
	 * one with the number of emails received per day, and one with the number of emails received per type (ham or spam)
	 */
	public function cf7a_flamingo_widget() {
			$max_mail_count = apply_filters( 'cf7a_dashboard_max_mail_count', 25 );
			$query          = $this->cf7a_get_flamingo_stats( $max_mail_count );

		if ( ! $query->have_posts() ) {
				$this->cf7a_render_empty_state();
				return;
		}

			/* Process the mail collection */
			$mail_collection = $this->cf7a_process_mail_collection( $query );

			/* Prepare chart data */
			$chart_data = $this->cf7a_prepare_chart_data( $mail_collection );

			/* Render the widget */
		?>
			<div id="antispam-widget">
					<canvas id="line-chart" width="400" height="200"></canvas>
					<hr>
					<canvas id="pie-chart" width="50" height="50"></canvas>
					<?php
					$this->cf7a_render_email_list( $query );
					$this->cf7a_render_footer();
					$this->cf7a_render_chart_script( $chart_data );
					?>
			</div>
			<?php
	}

	/**
	 * Prints a widget with a chart displaying spam and ham mails received
	 *
	 * It queries the database for all the emails received in the last week, then it creates two lists:
	 * one with the number of emails received per day, and one with the number of emails received per type (ham or spam)
	 */
	public function cf7a_dash_charts() {
		$max_mail_count = apply_filters( 'cf7a_admin_dashboard_max_mail_count', 50 );
		$query          = $this->cf7a_get_flamingo_stats( $max_mail_count, '1 year ago' );

		if ( ! $query->have_posts() ) {
			$this->cf7a_render_empty_state();
			return;
		}

		/* Process the mail collection */
		$mail_collection = $this->cf7a_process_mail_collection( $query );

		/* Prepare chart data */
		$chart_data = $this->cf7a_prepare_chart_data( $mail_collection );

		/* Render the widget */
		?>
		<div id="antispam-charts">
			<div class="antispam-charts-container">
				<div class="antispam-charts-line">
					<canvas id="line-chart" width="400" height="200"></canvas>
				</div>
				<div class="antispam-charts-line">
					<canvas id="pie-chart" width="400" height="200"></canvas>
				</div>
			</div>
			<?php
				$this->cf7a_render_chart_script( $chart_data );
			?>
		</div>
		<?php
	}
}
