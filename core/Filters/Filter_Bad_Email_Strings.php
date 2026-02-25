<?php
/**
 * Filter for Bad Email Strings.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;

/**
 * Class Filter_Bad_Email_Strings
 */
class Filter_Bad_Email_Strings extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks for bad strings inside the email address.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		$options = $data['options'];
		if ( intval( $options['check_bad_email_strings'] ) !== 1 || empty( $data['emails'] ) ) {
			return $data;
		}

		$score_bad_string  = floatval( $options['score']['_bad_string'] );
		$bad_email_strings = isset( $options['bad_email_strings_list'] ) ? $options['bad_email_strings_list'] : array();

		foreach ( $data['emails'] as $email ) {
			foreach ( $bad_email_strings as $bad_email_string ) {
				$bad_email_string = trim( $bad_email_string );
				if ( ! empty( $bad_email_string ) && false !== stripos( strtolower( $email ), strtolower( $bad_email_string ) ) ) {
					$data['spam_score']                    += $score_bad_string;
					$data['reasons']['email_blocklisted'][] = $bad_email_string;
				}
			}
		}

		if ( isset( $data['reasons']['email_blocklisted'] ) && is_array( $data['reasons']['email_blocklisted'] ) ) {
			$data['reasons']['email_blocklisted'] = implode( ',', $data['reasons']['email_blocklisted'] );
			cf7a_log( "The ip address {$data['remote_ip']} sent a mail using bad string {$data['reasons']['email_blocklisted']}", 1 );
		}

		return $data;
	}
}
