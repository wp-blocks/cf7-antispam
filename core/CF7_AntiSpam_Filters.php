<?php
/**
 * Antispam functions.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core;

use CF7_AntiSpam\Core\Filters\Filter_B8_Bayesian;
use CF7_AntiSpam\Core\Filters\Filter_Bad_Email_Strings;
use CF7_AntiSpam\Core\Filters\Filter_Bad_IP;
use CF7_AntiSpam\Core\Filters\Filter_Bad_Words;
use CF7_AntiSpam\Core\Filters\Filter_Bot_Fingerprint;
use CF7_AntiSpam\Core\Filters\Filter_Bot_Fingerprint_Extras;
use CF7_AntiSpam\Core\Filters\Filter_DNSBL;
use CF7_AntiSpam\Core\Filters\Filter_Empty_IP;
use CF7_AntiSpam\Core\Filters\Filter_Geoip;
use CF7_AntiSpam\Core\Filters\Filter_Honeyform;
use CF7_AntiSpam\Core\Filters\Filter_Honeypot;
use CF7_AntiSpam\Core\Filters\Filter_IP_Allowlist;
use CF7_AntiSpam\Core\Filters\Filter_IP_Blocklist_History;
use CF7_AntiSpam\Core\Filters\Filter_Language;
use CF7_AntiSpam\Core\Filters\Filter_Plugin_Version;
use CF7_AntiSpam\Core\Filters\Filter_Referrer_Protocol;
use CF7_AntiSpam\Core\Filters\Filter_Time_Submission;
use CF7_AntiSpam\Core\Filters\Filter_User_Agent;
use WPCF7_Submission;

/**
 * A class that is used to filter out spam.
 */
class CF7_AntiSpam_Filters {

	/**
	 * CF7_AntiSpam_Filters constructor.
	 * Registers the individual spam checks to the custom filter hook.
	 *
	 * @param bool $register_hooks Whether to register the default hooks.
	 */
	public function __construct( $register_hooks = true ) {
		if ( ! $register_hooks ) {
			return;
		}
		// Priority 5: Allowlist checks (should run first to stop processing if safe)
		add_filter( 'cf7a_spam_check_chain', array( new Filter_IP_Allowlist(), 'check' ), 5 );

		// Priority 10: Standard checks
		add_filter( 'cf7a_spam_check_chain', array( new Filter_Empty_IP(), 'check' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( new Filter_Bad_IP(), 'check' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( new Filter_IP_Blocklist_History(), 'check' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( new Filter_Honeyform(), 'check' ), 10 );

		// Checks that originally ran only if score < 1 (See logic inside methods)
		add_filter( 'cf7a_spam_check_chain', array( new Filter_Referrer_Protocol(), 'check' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( new Filter_Plugin_Version(), 'check' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( new Filter_Bot_Fingerprint(), 'check' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( new Filter_Bot_Fingerprint_Extras(), 'check' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( new Filter_Language(), 'check' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( new Filter_Geoip(), 'check' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( new Filter_Time_Submission(), 'check' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( new Filter_Bad_Email_Strings(), 'check' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( new Filter_User_Agent(), 'check' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( new Filter_Bad_Words(), 'check' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( new Filter_DNSBL(), 'check' ), 10 );
		add_filter( 'cf7a_spam_check_chain', array( new Filter_Honeypot(), 'check' ), 10 );

		// Priority 20: Bayesian filter
		add_filter( 'cf7a_spam_check_chain', array( new Filter_B8_Bayesian(), 'check' ), 20 );
	}

	// ------------------------
	// MAIN FILTER ORCHESTRATOR
	// ------------------------

	/**
	 * CF7_AntiSpam_Filters The antispam filter
	 *
	 * @param boolean $spam - spam or not.
	 *
	 * @return boolean
	 */
	public function cf7a_spam_filter( $spam ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing

		/* Get the submitted data */
		$submission = WPCF7_Submission::get_instance();

		if ( ! $submission ) {
			return true;
		}

		/* Get the contact form additional data */
		$posted_data  = $submission->get_posted_data();
		$contact_form = $submission->get_contact_form();

		/* Get plugin options */
		$options = get_option( 'cf7a_options', array() );

		/* Check the period of grace and, if it is expired, reset the error count */
		if ( ! empty( $options['last_update_data']['errors'] ) ) {
			$period_of_grace = apply_filters( 'cf7a_period_of_grace', WEEK_IN_SECONDS );
			if ( time() - $options['last_update_data']['time'] > $period_of_grace ) {
				$options['last_update_data']['errors'] = array();
			}
			// then save the updated options to the database
			update_option( 'cf7a_options', $options );
		}

		/* Get basic submission details */
		$mail_tags = $contact_form->scan_form_tags();
		$email_tag = sanitize_title( cf7a_get_mail_meta( $contact_form->pref( 'flamingo_email' ) ) );
		$emails    = isset( $posted_data[ $email_tag ] ) ? array( $posted_data[ $email_tag ] ) : CF7_AntiSpam_Rules::scan_email_tags( $mail_tags );

		/**
		 * Get the message from the contact form
		 */
		$message = CF7_AntiSpam_Rules::get_email_message(
			sanitize_text_field( $contact_form->pref( 'flamingo_message' ) ),
			$posted_data,
			$mail_tags
		);

		/**
		 * Let developers hack the message
		 */
		$message = apply_filters( 'cf7a_message_before_processing', $message, $posted_data );

		/* Prepare IP and basic user data */
		$prefix = sanitize_text_field( $options['cf7a_customizations_prefix'] );
		// The right way to do this is BEFORE decrypting and THEN sanitize, because sanitized data are stripped of any special characters
		$address_key = esc_attr( $prefix . 'address' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$real_remote_ip = isset( $_POST[ $address_key ] ) ? sanitize_text_field( wp_unslash( cf7a_decrypt( $_POST[ $address_key ], $options['cf7a_cipher'] ) ) ) : false;
		$remote_ip      = $real_remote_ip ? filter_var( $real_remote_ip, FILTER_VALIDATE_IP ) : false;
		$cf7_remote_ip  = filter_var( $submission->get_meta( 'remote_ip' ), FILTER_VALIDATE_IP );
		$user_agent     = sanitize_text_field( $submission->get_meta( 'user_agent' ) );

		// -------------------------------------------------------------
		// BUILD THE DATA OBJECT (Context)
		// -------------------------------------------------------------
		$spam_data = array(
			'submission'     => $submission,
			'options'        => $options,
			'posted_data'    => $posted_data,
			'remote_ip'      => $remote_ip,
			'cf7_remote_ip'  => $cf7_remote_ip,
			'emails'         => $emails,
			'message'        => $message,
			'mail_tags'      => $mail_tags,
			'user_agent'     => $user_agent,
			// State trackers
			'spam_score'     => 0,
			'is_spam'        => $spam,
			'reasons'        => array(),
			'is_allowlisted' => false,
		// Flag to stop processing
		);

		if ( CF7ANTISPAM_DEBUG_EXTENDED ) {
			cf7a_log( 'New submission from ' . $remote_ip . ' will be processed', 1 );
		}

		/**
		 * RUN THE FILTER CHAIN
		 * This triggers all the checks registered in __construct
		 */
		$spam_data = apply_filters( 'cf7a_spam_check_chain', $spam_data );

		/**
		 * BAYESIAN FILTER (B8)
		 * Placed explicitly here to ensure it runs at the end of the function,
		 * regardless of previous spam detection (unless allowlisted).
		 */
		$spam_data = apply_filters( 'cf7a_check_b8', $spam_data );

		/**
		 * Define how reasons map to score options
		 *
		 * @var array $score_mapping The score mapping array.
		 */
		$score_mapping = apply_filters(
			'cf7a_score_mapping',
			array(
				'b8'                     => '_detection',
				'bad_word'               => '_bad_string',
				'email_blocklisted'      => '_bad_string',
				'bad_ip'                 => '_bad_ip',
				'bot_fingerprint'        => '_fingerprinting',
				'bot_fingerprint_extras' => '_fingerprinting',
				'dnsbl'                  => '_dnsbl',
				'no_ip'                  => '_warn',
				'geo_ip'                 => '_warn',
				'high_entropy'           => '_bad_string',
				'honeyform'              => '_honeypot',
				'honeypot'               => '_honeypot',
				'blocklisted'            => '_warn',
				'browser_language'       => '_detection',
				'language_field'         => '_detection',
				'language_incoherence'   => '_detection',
				'disallowed_language'    => '_detection',
				'data_mismatch'          => '_fingerprinting',
				'no_referrer'            => '_warn',
				'no_protocol'            => '_warn',
				'timestamp'              => '_detection',
				'min_time_elapsed'       => '_time',
				'max_time_elapsed'       => '_time',
				'user_agent'             => '_bad_string',
				'fallback'               => '_warn',
			)
		);

		// Centralized Score Calculation
		foreach ( $spam_data['reasons'] as $reason_key => $reason_values ) {
			if ( isset( $score_mapping[ $reason_key ] ) ) {
				$option_key = $score_mapping[ $reason_key ];

				// Ensure a penalty score exists in the options
				if ( isset( $options['score'][ $option_key ] ) ) {
					$score_per_violation = floatval( $options['score'][ $option_key ] );

					// Multiply the score by the number of times the rule was broken
					$occurrences = is_array( $reason_values ) ? count( $reason_values ) : 1;

					$spam_data['spam_score'] += ( $score_per_violation * $occurrences );
				}
			} else {
				// Fallback to warn if no score preset is found
				$spam_data['spam_score'] += floatval( $options['score'][ $score_mapping['fallback'] ] );
			}
		}

		// Extract results
		$spam_score = $spam_data['spam_score'];
		$reason     = $spam_data['reasons'];
		$spam       = $spam_data['is_spam'];
		$remote_ip  = $spam_data['remote_ip'] ? $spam_data['remote_ip'] : $spam_data['cf7_remote_ip'];

		/**
		 * Final filter before the ban
		 *
		 * @param bool $spam
		 * @param string $message
		 * @param WPCF7_Submission $submission
		 */
		$spam = apply_filters( 'cf7a_additional_spam_filters', $spam, $message, $submission );

		/* If the spam score is lower than 1 the mail is ham */
		if ( $spam_score < 1 && ! $spam ) {
			return $spam;
			// Usually false
		}

		/* Prepare for ban/logging */
		$reasons_for_ban = cf7a_compress_array( $reason );

		/* If the auto-store ip is enabled */
		if ( isset( $options['autostore_bad_ip'] ) && $options['autostore_bad_ip'] ) {
			if ( CF7_Antispam_Blocklist::cf7a_ban_by_ip( $remote_ip, $reason, round( $spam_score ) ) ) {
				cf7a_log( "Ban for $remote_ip - results - " . $reasons_for_ban, 2 );
			} else {
				cf7a_log( "Unable to ban $remote_ip" );
			}
		}

		/* Store the ban reason into mail post-metadata */
		$submission->add_spam_log(
			array(
				'agent'  => 'CF7-AntiSpam',
				'reason' => $reasons_for_ban,
			)
		);

		return true;
	}
}
