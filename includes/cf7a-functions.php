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

// expand IPv6 address
function cf7a_expand_ipv6( $ip ) {
	$hex = unpack( "H*hex", inet_pton( $ip ) );
	return substr( preg_replace( "/([A-f0-9]{4})/", "$1:", $hex['hex'] ), 0, - 1 );
}

function cf7a_reverse_ipv4( $ip ) {
	return implode( ".", array_reverse( explode( ".", $ip ) ) );
}

function cf7a_reverse_ipv6( $ip ) {
	$ip = expand( $ip );
	// remove ":" and reverse the string then
	// add a dot for each digit
	return implode( '.', str_split( strrev( str_replace( ":", "", $ip ) ) ) );
}

function cf7a_check_dnsbl( $reverse_ip, $ip_type = "ipv4" ) {

	$dnsbl_blacklists = array(
		"dnsbl-1.uceprotect.net",
		"dnsbl-2.uceprotect.net",

		"dnsbl.sorbs.net",
		"spam.dnsbl.sorbs.net",
		"zen.spamhaus.org",
		"bl.spamcop.net",
		"b.barracudacentral.org",
		"dnsbl.dronebl.org",
		"ips.backscatterer.org",

		// too much aggressive, use with caution
		// "dnsbl-3.uceprotect.net",

		// ipv6 dnsbl
		"bogons.cymru.com",
		"bl.ipv6.spameatingmonkey.net",
	);

	foreach ( $dnsbl_blacklists as $dnsbl ) {
		if ( checkdnsrr( $reverse_ip . "." . $dnsbl . ".", "A" ) ) {
			return $dnsbl;
		}
	}

	return false;
}

function cf7a_crypt( $value , $cipher = "aes-256-cbc" ) {
	return openssl_encrypt( $value , $cipher, wp_salt('nonce'), $options=0, substr(wp_salt('nonce'), 0, 16) );
}

function cf7a_decrypt( $value , $cipher = "aes-256-cbc" ) {
	return openssl_decrypt( $value , $cipher, wp_salt('nonce'), $options=0, substr(wp_salt('nonce'), 0, 16) );
}