<?php

class CF7_AntiSpam_Admin_Tools {

	public static function cf7a_push_notice($message = "generic", $type = "error", $dismissible = true) {
		$class = "notice notice-$type";
		$class .= $dismissible ? ' is-dismissible' : '';
		return sprintf( '<div class="%s"><p>%s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	public static function cf7a_format_status($rank) {
		$color = 200 - ($rank * 2);
		$color =  $color < 0 ? 0 : $color;
		return "<span class='ico' style='background-color: rgba(250,$color,0)'>$rank</span>";
	}

	public static function cf7a_handle_blacklist() {

		$req_nonce = isset($_GET['cf7a-nonce']) ? wp_verify_nonce( $_GET['cf7a-nonce'], 'cf7a-nonce' ) : null;
		$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : false;

		$url = esc_url( menu_page_url( 'cf7-antispam', false ) );

		if ( $req_nonce ) {

			$filter = new CF7_AntiSpam_filters();

			// Ban a single ID (related to ip)
			if ( substr( $action, 0, 6 ) === "unban_" ) {

				$unban_id = intval(substr( $action, 6 ));

				$r = $filter->cf7a_unban_by_id( $unban_id );

				if (!is_wp_error($r)) {
					wp_redirect( add_query_arg('action', 'success', $url ));
				} else {
					wp_redirect( add_query_arg('action', 'fail', $url ));
				}

				exit();

			}

			// Purge the blacklist
			if ( $action === 'clean-blacklist' ) {

				$r = $filter->cf7a_clean_blacklist();

				if (!is_wp_error($r)) {
					wp_redirect( add_query_arg('action', 'success', $url ));
				} else {
					wp_redirect( add_query_arg('action', 'fail', $url ));
				}

				exit();
			}

		}

		// admin notices
		if ($action === 'success') {
			add_action( 'admin_notices', function () {
				echo CF7_AntiSpam_Admin_Tools::cf7a_push_notice(__('Success', 'cf7-antispam'), 'success' );
			} );
		} else if ($action === 'fail') {
			add_action( 'admin_notices', function () {
				echo CF7_AntiSpam_Admin_Tools::cf7a_push_notice(__('Error', 'cf7-antispam'), 'error' );
			} );
		}

	}

	public static function cf7a_get_blacklisted_table($nonce) {

		global $wpdb;
		$blacklisted = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cf7a_blacklist ORDER BY `status` DESC LIMIT 1000" );

		if ( $blacklisted ) {
			$html = '  <div class="card"><h3>' . __( 'IP Blacklist' ) . '</h3><div class="widefat blacklist-table">';
			foreach ( $blacklisted as $row ) {
				$html .= sprintf( "<div class='row'><div class='status'>%s</div><div><p class='ip'>%s</p><span class='ellipsis'>%s</span></div></div>", self::cf7a_format_status( $row->status ), $row->ip, $row->reason );
			}
			$html .= '</div></div>';

			echo $html;
		}
	}

	public static function cf7a_get_debug_info() {

		if (CF7ANTISPAM_DEBUG) {
			$options = CF7_AntiSpam::get_options();

			echo '<div class="card"><h3>'.__('Debug info').'</h3>';
			echo '<p>'.__('If you see this box it is because wp_debug is active!').'</p>';

			echo '<pre>' . htmlentities(print_r($options, true)) . '</pre>';
			echo '</div>';
		}

	}
}