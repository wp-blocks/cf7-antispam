<?php

class CF7_AntiSpam_Admin_Tools {

	public static function cf7a_push_notice($message = "generic", $type = "error", $dismissible = true) {
		$class = "notice notice-$type";
		$class .= $dismissible ? ' is-dismissible' : '';
		return sprintf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

}