<?php

function get_real_ip() {
	if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) && filter_var( $_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP ) ) {
		return $_SERVER['HTTP_CLIENT_IP'];
	} else if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && filter_var( $_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP ) ) {
		return (string) rest_is_ip_address( trim( current( preg_split( '/,/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) );
	} else if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) && filter_var( $_SERVER['HTTP_X_REAL_IP'], FILTER_VALIDATE_IP ) ) {
		return $_SERVER['HTTP_X_REAL_IP'];
	} else if ( isset( $_SERVER["HTTP_CF_CONNECTING_IP"] ) && filter_var( $_SERVER["HTTP_CF_CONNECTING_IP"], FILTER_VALIDATE_IP ) ) {
		return $_SERVER["HTTP_CF_CONNECTING_IP"];
	} else if ( isset( $_SERVER['REMOTE_ADDR'] ) && filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP ) ) {
		return $_SERVER['REMOTE_ADDR'];
	}
}

function cf7a_crypt( $value , $cipher = "aes-256-cbc" ) {
	return openssl_encrypt( $value , $cipher, wp_salt('nonce'), $options=0, substr(wp_salt('nonce'), 0, 16) );
}

function cf7a_decrypt( $value , $cipher = "aes-256-cbc" ) {
	return openssl_decrypt( $value , $cipher, wp_salt('nonce'), $options=0, substr(wp_salt('nonce'), 0, 16) );
}

function microtimeFloat() {
	list($usec, $sec) = explode(' ', microtime());
	return (float) $usec + (float) $sec;
}

function formatRating($rating) {
	if ($rating === false) {
		return '<span style="color:red">'.__('Not available').'</span>';
	}

	$red   = floor(255 * $rating);
	$green = floor(255 * (1 - $rating));
	return "<span style=\"color:rgb($red, $green, 0);\"><b>" . sprintf("%5f", $rating)
	       . "</b></span>";
}