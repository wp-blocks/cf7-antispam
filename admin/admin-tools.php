<?php

class CF7_AntiSpam_Admin_Tools {

	public static function cf7a_push_notice($message = "generic", $type = "error", $dismissible = true) {
		$class = "notice notice-$type";
		$class .= $dismissible ? ' is-dismissible' : '';
		return sprintf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	public static function cf7a_format_status($rank) {
		$color = 200 - ($rank * 2);
		$color =  $color < 0 ? 0 : $color;
		return "<span class='ico' style='background-color: rgba(250,$color,0)'>$rank</span>";
	}

	public static function cf7a_get_blacklisted_table() {
		global $wpdb;
		$blacklisted = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cf7a_blacklist ORDER BY `status` DESC LIMIT 1000" );

		if ($blacklisted) {
			echo '<div class="widefat blacklist-table">';
			foreach ($blacklisted as $row) {
				printf("<div class='row'><div class='status'>%s</div><div><p class='ip'>%s</p><span class='ellipsis'>%s</span></div></div>", self::cf7a_format_status($row->status), $row->ip, $row->reason);
			}
			echo '</div>';
		}
	}

}