<?php
class CF7_AntiSpam_Frontend {

	public function __construct() {
		add_filter( 'wpcf7_form_hidden_fields', array( $this, 'cf7a_add_hidden_fields') , 100, 1 );
	}

	public function cf7a_add_hidden_fields( $fields ) {

		$timestamp = time();

		$ip = get_real_ip();

		return array_merge( $fields, array(
			'_wpcf7_form_creation_timestamp' => cf7a_crypt($timestamp),
			'_wpcf7_real_sender_ip' => cf7a_crypt($ip),
		));
	}

}