<?php
/**
 * Filter for Plugin Version.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Admin\CF7_AntiSpam_Admin_Tools;
use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;

/**
 * Class Filter_Plugin_Version
 */
class Filter_Plugin_Version extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks Plugin Version match.
	 * If the version does not match, the spam check is skipped.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		$options              = $data['options'];
		$prefix               = $this->get_prefix( $options );
		$score_fingerprinting = floatval( $options['score']['_fingerprinting'] );

		// The right way to do this is BEFORE decrypting and THEN sanitize, because sanitized data are stripped of any special characters
		$version_key = esc_attr( $prefix . 'version' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing
		$cf7a_version = isset( $_POST[ $version_key ] ) ? sanitize_text_field( wp_unslash( cf7a_decrypt( $_POST[ $version_key ], $options['cf7a_cipher'] ) ) ) : false;

		// CASE A: Version field is completely missing or empty -> SPAM
		if ( ! $cf7a_version ) {
			$data['spam_score']              += $score_fingerprinting;
			$data['reasons']['data_mismatch'] = sprintf( "Version mismatch (empty) != '%s'", CF7ANTISPAM_VERSION );
			cf7a_log( sprintf( "The 'version' field submitted by %s is empty", $data['remote_ip'] ), 1 );

			return $data;
		}

		// CASE B: Version matches current version -> OK
		if ( CF7ANTISPAM_VERSION === $cf7a_version ) {
			return $data;
		}

		// CASE C: Version Mismatch logic (Cache vs Spam)
		// Retrieve update data stored during the last plugin update
		$last_update_data = $options['last_update_data'] ?? null;

		// Check if we have update data and if the submitted version matches the PREVIOUS version
		$is_old_version_match = ( $last_update_data && isset( $last_update_data['old_version'] ) && $cf7a_version === $last_update_data['old_version'] );

		// Check if the update happened less than a week ago
		$period_of_grace        = apply_filters( 'cf7a_period_of_grace', WEEK_IN_SECONDS );
		$is_within_grace_period = ( $last_update_data && isset( $last_update_data['time'] ) && ( time() - $last_update_data['time'] ) < $period_of_grace );

		if ( $is_old_version_match && $is_within_grace_period ) {

			// --- CACHE ISSUE DETECTED (FALLBACK) ---
			// Do NOT mark as spam. This is likely a cached user.

			cf7a_log( "Cache mismatch detected for IP {$data['remote_ip']}. Submitted: $cf7a_version. Expected: " . CF7ANTISPAM_VERSION, 1 );

			// Record the error
			if ( ! isset( $options['last_update_data']['errors'] ) ) {
				$options['last_update_data']['errors'] = array();
			}

			// Add error details
			$options['last_update_data']['errors'][] = array(
				'ip'   => $data['remote_ip'],
				'time' => time(),
			);

			$error_count = count( $options['last_update_data']['errors'] );

			// Check trigger for email notification (Exactly on the 5th error)
			$cf7a_period_of_grace_max_attempts = intval( apply_filters( 'cf7a_period_of_grace_max_attempts', 5 ) );
			if ( $cf7a_period_of_grace_max_attempts === $error_count || $error_count * 3 === $cf7a_period_of_grace_max_attempts ) {
				$this->send_cache_warning_email( $options['last_update_data'] );
				cf7a_log( 'Cache warning email sent to admin.', 1 );
			}

			// SAVE OPTIONS: We must save the error count to the database
			// Update the local $options variable first so later filters use it if needed (though unlikely)
			$data['options'] = $options;

			// Persist to DB
			update_option( 'cf7a_options', $options );

		} else {

			// --- REAL SPAM / INVALID VERSION ---
			// Either the grace period expired, or the version is completely random

			$data['spam_score']              += $score_fingerprinting;
			$data['reasons']['data_mismatch'] = "Version mismatch '$cf7a_version' != '" . CF7ANTISPAM_VERSION . "'";
			cf7a_log( "The 'version' field submitted by {$data['remote_ip']} is mismatching (expired grace period or invalid)", 1 );
		}//end if

		return $data;
	}

	/**
	 * Sends an email to the admin, warning them to clear the cache.
	 *
	 * @param array $update_data the array of data to be sent to the admin
	 *
	 * @return void
	 */
	private function send_cache_warning_email( $update_data ): void {
		$tools     = new CF7_AntiSpam_Admin_Tools();
		$recipient = get_option( 'admin_email' );
		$body      = sprintf(
			"Hello Admin,\n\nWe detected 5 users trying to submit forms with the old version (%s) instead of the new one (%s).\n\nThis usually means your website cache (or CDN) hasn't been cleared after the last update.\n\nPlease purge your site cache immediately to prevent legitimate users from being flagged as spam.\n\nTime of update: %s",
			$update_data['old_version'],
			$update_data['new_version'],
			gmdate( 'Y-m-d H:i:s', $update_data['time'] )
		);
		$subject   = 'CF7 AntiSpam - Cache Warning Alert';

		$tools->send_email_to_admin( $subject, $recipient, $body, $recipient );
	}
}
