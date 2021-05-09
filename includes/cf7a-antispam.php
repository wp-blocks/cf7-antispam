<?php

// the spam filter
add_filter( 'wpcf7_spam', function ( $spam ) {

	if ( $spam ) {
		return $spam;
	}

	// Get the submitted data
	$submission = WPCF7_Submission::get_instance();

	// check the timestamp
	$timestamp                       = intval(cf7a_decrypt($_POST['_wpcf7_form_creation_timestamp']));
	$submission_minimum_time_elapsed = 10;
	$submission_maximum_time_elapsed = 3600;

	// checks if the mail contains bad words
	$bad_words = array( "make money", "bitcoin", "viagra" );
	$message   = str_replace( " ", "", strtolower( $_POST['your-message'] ) );

	// b8
	$B8enabled = true;

	if ($B8enabled == true) {

		global $wpdb;

		// B8 config
		$mysql = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

		$config_b8      = array( 'storage' => 'mysql' );
		$config_storage = array(
			'resource' => $mysql,
			'table'    => $wpdb->prefix . 'cf7_antispam_wordlist'
		);

		// We use the default lexer settings
		$config_lexer = [];

		// We use the default degenerator configuration
		$config_degenerator = [];

		$time_start = null;

		$time_start = microtimeFloat();

		// Include the b8 code
		require_once CF7ANTISPAM_PLUGIN_DIR . '/vendor/b8/b8.php';

		# Create a new b8 instance
		try {
			$b8 = new b8\b8($config_b8, $config_storage, $config_lexer, $config_degenerator);
		} catch(Exception $e) {
			error_log( 'CF7 Antispam error message: ' . $e->getMessage() );
			exit();
		}

		$text = stripslashes($message);
		$postedText = htmlentities($text, ENT_QUOTES, 'UTF-8');
		$action = "Classify";

		switch($action) {
			case 'Classify':
				error_log('CF7 Antispam Mail Spaminess: ' . $b8->classify($text) );
				break;

			case 'Save as Spam':
				$ratingBefore = $b8->classify($text);
				$b8->learn($text, b8\b8::SPAM);
				$ratingAfter = $b8->classify($text);

				echo "<p>Saved the text as Spam</p>\n\n";
				echo "<div><table>\n";
				echo '<tr><td>Classification before learning:</td><td>' . formatRating($ratingBefore)
				     . "</td></tr>\n";
				echo '<tr><td>Classification after learning:</td><td>'  . formatRating($ratingAfter)
				     . "</td></tr>\n";
				echo "</table></div>\n\n";

				break;

			case 'Save as Ham':
				$ratingBefore = $b8->classify($text);
				$b8->learn($text, b8\b8::HAM);
				$ratingAfter = $b8->classify($text);

				echo "<p>Saved the text as Ham</p>\n\n";

				echo "<div><table>\n";
				echo '<tr><td>Classification before learning:</td><td>' . formatRating($ratingBefore)
				     . "</td></tr>\n";
				echo '<tr><td>Classification after learning:</td><td>'  . formatRating($ratingAfter)
				     . "</td></tr>\n";
				echo "</table></div>\n\n";

				break;

			case 'Delete from Spam':
				$b8->unlearn($text, b8\b8::SPAM);
				echo "<p style=\"color:green\">Deleted the text from Spam</p>\n\n";
				break;

			case 'Delete from Ham':
				$b8->unlearn($text, b8\b8::HAM);
				echo "<p style=\"color:green\">Deleted the text from Ham</p>\n\n";
				break;

		}

		$mem_used      = round(memory_get_usage() / 1048576, 5);
		$peak_mem_used = round(memory_get_peak_usage() / 1048576, 5);
		$time_taken    = round(microtimeFloat() - $time_start, 5);

		error_log( "CF7 Antispam stats : \r\nMemory: $mem_used \r\nPeak memory: $peak_mem_used \r\nTime Elapsed: $time_taken" );

		unset($b8);
	}

	if ( time() <= ( $timestamp + $submission_minimum_time_elapsed ) ) {

		$spam = true;

		$time_elapsed = time() - $timestamp;

		error_log( "It took too little time to fill in the form - ($time_elapsed)" );

		$submission->add_spam_log( array(
			'agent'  => 'timestamp_issue',
			'reason' => "Sender send the email in $time_elapsed. Too little to complete this form!",
		) );

		return $spam;

	}

	if ( time() >= ( $timestamp + $submission_maximum_time_elapsed ) ) {

		$spam = true;

		$time_elapsed = time() - $timestamp;

		error_log( "It took too much time to fill in the form - ($time_elapsed)" );

		$submission->add_spam_log( array(
			'agent'  => 'timestamp_issue',
			'reason' => "Sender send the email in $time_elapsed. Too much time to complete this form or the timestamp was hacked!",
		) );

		return $spam;

	}

	// if the sender mail is the same of the website domain may be an attempt to bypass controls,
	// because emails client can't blacklists the email itself we must prevent mail from this domain
	if ( isset( $_POST['your-email'] ) ) {
		$email_address = explode( "@", trim( $_POST['your-email'] ) );
		$domain        = str_replace( array( 'http://', 'https://' ), "", get_site_url() );

		// check if the mail sender is the same domain of the website, in this case it's spam
		if ( $email_address[0] == $domain || $email_address[1] == $domain ) {

			$spam = true;

			error_log( "the sender mail domain is the same of the website - {$email_address[0]}@{$email_address[1]} $domain" );

			$submission->add_spam_log( array(
				'agent'  => 'same_domain',
				'reason' => "Hijack the sender mail",
			) );

			return $spam;
		}
	}

	foreach ( $bad_words as $bad_word ) {
		if ( false !== stripos( $message, str_replace( " ", "", strtolower( $bad_word ) ) ) ) {

			error_log( "Detected a bad word ($bad_word)" );

			$spam = true;

			$submission->add_spam_log( array(
				'agent'  => 'bad_words',
				'reason' => "Detected a bad word ($bad_word)",
			) );

			return $spam;
		}
	}

	// check the remote ip
	$remote_ip = $submission->get_meta( 'remote_ip' );

	$remote_ip = cf7a_decrypt($_POST['_wpcf7_real_sender_ip']);
	$remote_ip = filter_var( $remote_ip, FILTER_VALIDATE_IP ) ? $remote_ip : '';

	// $remote_ip = "93.57.247.109"; //test ipv4
	// $remote_ip = "185.153.110.243"; //test ipv4
	// $remote_ip = "0000:0000:0000:0000:0000:ffff:7f00:0001"; //test ipv6 spam
	// $remote_ip = "2a00:23c6:f508:7b00:41aa:8900:7785:3824"; //test ipv6 ok


	if ( $remote_ip ) {

		// dsnbl check - inspiration taken from https://gist.github.com/tbreuss/74da96ff5f976ce770e6628badbd7dfc

		if ( filter_var( $remote_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {

			$reverse_ip = cf7a_reverse_ipv4( $remote_ip );

			if ( false !== ( $dnsbl = cf7a_check_dnsbl( $reverse_ip, 'ipv4' ) ) ) {

				error_log( "The $remote_ip has tried to send an email but is listed in the $dnsbl IPv4 Domain Name System Blacklists." );

				$spam = true;

				$submission->add_spam_log( array(
					'agent'  => 'dnsbl_listed',
					'reason' => "$remote_ip listed in the dnsbl IPv4 $dnsbl",
				) );

				return $spam;
			}

		} else if ( filter_var( $remote_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {

			$reverse_ip = cf7a_reverse_ipv6( $remote_ip );

			if ( false !== ( $dnsbl = cf7a_check_dnsbl( $reverse_ip, 'ipv6' ) ) ) {

				error_log( "The $remote_ip has tried to send an email but is listed in the $dnsbl IPv6 Domain Name System Blacklists." );

				$spam = true;

				$submission->add_spam_log( array(
					'agent'  => 'dnsbl_listed',
					'reason' => "$remote_ip listed in the dnsbl IPv6 $dnsbl",
				) );

				return $spam;
			}
		}
	}


	return $spam;

}, 10, 1 );