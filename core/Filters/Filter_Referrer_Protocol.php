<?php
/**
 * Filter for Referrer and Protocol.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;

/**
 * Class Filter_Referrer_Protocol
 */
class Filter_Referrer_Protocol extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks Referrer and Protocol.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		$options    = $data['options'];
		$prefix     = $this->get_prefix( $options );
		$score_warn = floatval( $options['score']['_warn'] );

		if ( intval( $options['check_refer'] ) === 1 ) {
			// The right way to do this is BEFORE decrypting and THEN sanitize, because sanitized data are stripped of any special characters
			$refer_key = esc_attr( $prefix . 'referer' );
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing
			$cf7a_referer = isset( $_POST[ $refer_key ] ) ? sanitize_text_field( wp_unslash( cf7a_decrypt( $_POST[ $refer_key ], $options['cf7a_cipher'] ) ) ) : false;
			if ( ! $cf7a_referer ) {
				$data['spam_score']            += $score_warn;
				$data['reasons']['no_referrer'] = 'client has referrer address';
				cf7a_log( "the {$data['remote_ip']} has reached the contact form page without any referrer", 1 );
			}
		}

		// The right way to do this is BEFORE decrypting and THEN sanitize, because sanitized data are stripped of any special characters
		$protocol_key = esc_attr( $prefix . 'protocol' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing
		$cf7a_protocol = isset( $_POST[ $protocol_key ] ) ? sanitize_text_field( wp_unslash( cf7a_decrypt( $_POST[ $protocol_key ], $options['cf7a_cipher'] ) ) ) : false;

		// Protocol field is completely missing or empty -> SPAM
		if ( ! $cf7a_protocol ) {
			$data['spam_score']            += $score_warn;
			$data['reasons']['no_protocol'] = 'client has a bot-like connection protocol';
			cf7a_log( "the {$data['remote_ip']} has a bot-like connection protocol (HTTP/1.X)", 1 );
		}

		return $data;
	}
}
