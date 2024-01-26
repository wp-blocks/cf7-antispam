<?php

namespace CF7_AntiSpam\Admin;

use CF7_AntiSpam\Core\CF7_Antispam_Geoip;
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

/**
 * It creates a class called CF7_AntiSpam_Admin.
 */
class CF7_AntiSpam_Admin_Core {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.1.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * The geoip class
	 *
	 * @since    0.4.6
	 * @access   private
	 * @var      CF7_Antispam_Geoip $geoip
	 */
	private $geoip;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.1.0
	 *
	 * @param      string $plugin_name The name of this plugin.
	 * @param      string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		/* Setting the value of the $plugin_name */
		$this->plugin_name = $plugin_name;

		/* Setting the version of the plugin */
		$this->version = $version;

		/* The menu item */
		new CF7_AntiSpam_Admin_Customizations();

		$this->geoip = new CF7_Antispam_Geoip();
		$this->geoip->cf7a_geo_maybe_download();
	}



	/**
	 * It adds a submenu page to the Contact Form 7 menu in the admin dashboard
	 */
	public function cf7a_admin_menu() {
		add_submenu_page(
			'wpcf7',
			__( 'Antispam', 'cf7-antispam' ),
			__( 'Antispam', 'cf7-antispam' ),
			'wpcf7_edit_contact_forms',
			$this->plugin_name,
			array( $this, 'cf7a_admin_dashboard' )
		);
	}

	/**
	 * Add go to settings link on plugin page.
	 *
	 * @since 0.2.2
	 *
	 * @param  array $links Array of plugin action links.
	 * @return array Modified array of plugin action links.
	 */
	public function cf7a_plugin_settings_link( array $links ) {
		$options = get_option( 'cf7a_options' );
		if ( $options['cf7a_enable'] ) {
			$settings_page_link = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=cf7-antispam' ), esc_html__( 'Antispam Settings', 'cf7-antispam' ) );
		} else {
			$settings_page_link = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=wpcf7-integration' ), esc_html__( 'Activate Contact Form 7 integration', 'cf7-antispam' ) );
		}
		array_unshift( $links, $settings_page_link );

		return $links;
	}

	/**
	 * It creates a new instance of the CF7_AntiSpam_Admin_Display class and then calls the display_dashboard() method on that
	 * instance
	 */
	public function cf7a_admin_dashboard() {
		$admin_display = new CF7_AntiSpam_Admin_Display();
		$admin_display->display_dashboard();
	}

	/**
	 * If the current admin page is not the plugin's admin page, return. Otherwise, if the settings have been updated, display
	 * a success message. Otherwise, if there's a notice in the transient, display it and delete the transient
	 *
	 * @return void
	 */
	public function cf7a_display_notices() {

		/* It checks if the current admin page is the plugin's admin page. If it is not, it returns. */
		$admin_page = get_current_screen();
		if ( false === strpos( $admin_page->base, $this->plugin_name ) ) {
			return;
		}

		/* It checks if the settings have been updated, and if so, it displays a success message. */
		$settings_updated = isset( $_REQUEST['settings-updated'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['settings-updated'] ) ) : false;
		if ( 'true' === $settings_updated ) {
			CF7_AntiSpam_Admin_Tools::cf7a_push_notice( esc_html__( 'Antispam setting updated with success', 'cf7-antispam' ), 'success' );
		}

		/* if there is a notice stored, print it then delete the transient */
		$notice = get_transient( 'cf7a_notice' );
		if ( ! empty( $notice ) ) {
			echo wp_kses(
				$notice,
				array(
					'div'    => array(
						'class' => array(),
					),
					'p'      => array(),
					'strong' => array(),
				)
			);
			delete_transient( 'cf7a_notice' );
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    0.1.0
	 */
	public function enqueue_styles() {

		/**
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in load_admin as all the hooks are defined
		 * in that particular class.
		 *
		 * The load_admin will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, CF7ANTISPAM_PLUGIN_URL . '/build/admin-scripts.css', array(), $this->version );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    0.1.0
	 */
	public function enqueue_scripts() {

		/**
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in load_admin as all the hooks are defined
		 * in that particular class.
		 *
		 * The load_admin will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		$asset = include CF7ANTISPAM_PLUGIN_DIR . '/build/admin-scripts.asset.php';
		wp_register_script( $this->plugin_name, CF7ANTISPAM_PLUGIN_URL . '/build/admin-scripts.js', $asset['dependencies'], $asset['version'], true );
		wp_enqueue_script( $this->plugin_name );

		wp_localize_script(
			$this->plugin_name,
			'cf7a_admin_settings',
			array(
				'alertMessage' => esc_html__( 'Are you sure?', 'cf7-antispam' ),
			)
		);
	}

	/**
	 * If the current admin page is not a Contact Form 7 Anti-Spam page, then return the $classes variable. Otherwise, return
	 * the $classes variable with the string "cf7-antispam-admin" appended to it
	 *
	 * @param string $classes Space-separated list of CSS classes.
	 *
	 * @return string $classes The $classes variable is being returned.
	 */
	public function cf7a_body_class( $classes ) {
		$admin_page = get_current_screen();
		if ( false === strpos( $admin_page->base, $this->plugin_name ) ) {
			return $classes;
		}
		return "$classes cf7-antispam-admin";
	}

	/**
	 * It adds a dashboard widget to the WordPress admin dashboard
	 */
	public function cf7a_dashboard_widget() {
		global $wp_meta_boxes;
		wp_add_dashboard_widget( 'cf7a-widget', __( 'Stats for CF7 Antispam', 'cf7-antispam' ), array( $this, 'cf7a_flamingo_recap' ) );
	}

	/**
	 * Prints a widget with a chart displaying spam and ham mails received
	 *
	 * It queries the database for all the emails received in the last week, then it creates two lists: one with the number of
	 * emails received per day, and one with the number of emails received per type (ham or spam)
	 */
	public function cf7a_flamingo_recap() {
		$max_mail_count = apply_filters( 'cf7a_dashboard_max_mail_count', 25 );

		$args = array(
			'post_type'      => 'flamingo_inbound',
			'post_status'    => array( 'flamingo-spam', 'publish' ),
			'posts_per_page' => $max_mail_count,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'date_query'     => array(
				array(
					'after' => '1 week ago',
				),
			),
		);

		$mail_collection = array(
			'by_type' => array(
				'ham'  => 0,
				'spam' => 0,
			),
			'by_date' => array(),
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) :
			/* this is needed to parse and create a list of emails. */
			?>
			<div id="antispam-widget">
				<canvas id="line-chart" width="400" height="200"></canvas>
				<hr>
				<canvas id="pie-chart" width="50" height="50"></canvas>
				<div id="antispam-widget-list" class="activity-block"><h3> <?php esc_html_e( 'Last Week Emails', 'cf7-antispam' ); ?></h3><ul>
				<?php
				/* print the received mail list */

				while ( $query->have_posts() ) :
					$query->the_post();
					global $post;

					$is_ham = 'flamingo-spam' !== $post->post_status;

					if ( wp_date( 'Y-m-d' ) > wp_date( 'Y-m-d', strtotime( '-1 week' ) ) ) {
						printf(
							'<li class="cf7-a_list-item"><span class="timestamp">%s </span><a href="%s" value="post-id-%s"><span>%s</span> %s</a> - %s</li>',
							get_the_date( 'Y-m-d' ),
							admin_url( 'admin.php?page=flamingo_inbound&post=' . $post->ID . '&action=edit' ),
							$post->ID,
							$is_ham ? '🔵' : '🔴',
							esc_html( get_post_meta( $post->ID, '_from' )[0] ),
							esc_html( $post->post_title )
						);
					}

					$today = esc_html( get_the_date( 'Y-m-d' ) );

					// for each post collect the main information like spam/ham or date.
					if ( ! isset( $mail_collection['by_date'][ $today ] ) ) {
						$mail_collection['by_date'][ $today ] = array();
					}
					$mail_collection['by_type'][ $is_ham ? 'ham' : 'spam' ]++;
					$mail_collection['by_date'][ $today ][] = array( 'status' => $is_ham ? 'ham' : 'spam' );
				endwhile;

				wp_reset_postdata();

				$count = array();

				$mail_collection['by_date'] = array_reverse( $mail_collection['by_date'] );

				/* for each date */
				foreach ( $mail_collection['by_date'] as $date => $items ) {

					/* add the date to the list if not yet added */
					if ( ! isset( $count[ $date ] ) ) {
						$count[ $date ] = array(
							'ham'  => 0,
							'spam' => 0,
						); }

					/* for each item of that date feed the count by email type */
					foreach ( $items as $item ) {
						'spam' === $item['status'] ? $count[ $date ]['spam'] ++ : $count[ $date ]['ham'] ++; }
				}

				/* Create two lists where the key is the date and the value is the number of mails of that type */
				foreach ( $count as $date ) {
					$ham[]  = $date['ham'];
					$spam[] = $date['spam'];
				}
				?>
				</ul></div>
				<p class="community-events-footer">
					<a href="<?php echo esc_url_raw( admin_url( 'admin.php?page=flamingo' ) ); ?>"><?php esc_html_e( 'Flamingo Inbound Messages', 'flamingo' ); ?><span aria-hidden="true" class="dashicons dashicons-external"></span></a>
					|
					<a href="<?php echo esc_url_raw( admin_url( 'admin.php?page=cf7-antispam' ) ); ?>"><?php esc_html_e( 'CF7-Antispam setup', 'cf7-antispam' ); ?><span aria-hidden="true" class="dashicons dashicons-external"></span></a>
				</p>
				<script>
					var spamChartData = {
						lineData: {
							labels: ["<?php echo wp_kses( implode( '","', array_keys( $mail_collection['by_date'] ) ), array() ); ?>"],
							datasets: [{
								label: 'Ham',
								backgroundColor: 'rgb(38,137,218)',
								borderColor: 'rgb(34 113 177)',
								tension: 0.25,
								data: [
									<?php
									if ( isset( $ham ) ) {
										echo esc_html( implode( ',', $ham ) );}
									?>
								],
							},
							{
								label: 'Spam',
								backgroundColor: 'rgb(255,4,0)',
								borderColor: 'rgb(248, 49, 47)',
								tension: 0.25,
								data: [
									<?php
									if ( isset( $spam ) ) {
										echo esc_html( implode( ',', $spam ) );
									}
									?>
								],
							}]
						},
						pieData: {
							labels: ["<?php echo wp_kses( implode( '","', array_keys( $mail_collection['by_type'] ) ), array() ); ?>"],
							datasets: [{
								data: [<?php echo esc_html( $mail_collection['by_type']['ham'] . ', ' . $mail_collection['by_type']['spam'] ); ?>],
								backgroundColor: [
									'rgb(38,137,218)',
									'rgb(248,49,47)'
								]
							}]
						}
					}
				</script>
			</div>
			<?php
		else :
			printf(
				'<div class="cf7-a_widget-empty"><span class="dashicons dashicons-welcome-comments"></span><p>%s</p></div>',
				esc_html__( 'You have not received any e-mails in the last 7 days.', 'cf7-antispam' )
			);
		endif;
	}
}
