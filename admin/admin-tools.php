<?php

class CF7_AntiSpam_Admin_Tools {

	public static function cf7a_push_notice($message = "generic", $type = "error", $dismissible = true) {
		$class = "notice notice-$type";
		$class .= $dismissible ? ' is-dismissible' : '';
		return sprintf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	public static function cf7a_format_status($rank) {
		$color = $rank < 3 ? 'warn' :
			($rank < 8 ? 'alert' : 'spammer');
		return "<span class='status $color'>$rank</span>";
	}

	public static function cf7a_get_blacklisted_table() {
		global $wpdb;
		$blacklisted = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cf7a_blacklist ORDER BY `status` DESC LIMIT 1000" );

		echo '<table class="widefat blacklist-table">';
		foreach ($blacklisted as $row) {
			echo "<tr><td>". self::cf7a_format_status($row->status)."</td><td><p class='ip'>$row->ip</p>$row->reason</td></tr>";
		}
		echo '</table>';
	}

}