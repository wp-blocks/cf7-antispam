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

		$bad_email_strings = $options['bad_email_strings_list'] ?? array();

		foreach ( $data['emails'] as $email ) {

			if ( ! is_string( $email ) ) {
				continue;
			}

			foreach ( $bad_email_strings as $bad_email_string ) {
				$bad_email_string = trim( $bad_email_string );
				if ( empty( $bad_email_string ) ) {
					continue;
				}

				$is_match = false;

				// Check if the string is formatted as a Regular Expression (starts and ends with '/')
				if ( str_starts_with( $bad_email_string, 'regex:' ) ) {
					$bad_email_string = substr( $bad_email_string, 6 );
					// @ to suppress warnings if the user writes an invalid regex
					$result = @preg_match( $bad_email_string, $email );
					if ( $result === 1 ) {
						$is_match = true;
					}
				} else {
					// Fallback to the standard substring check
					if ( false !== stripos( strtolower( $email ), strtolower( $bad_email_string ) ) ) {
						$is_match = true;
					}
				}

				if ( $is_match ) {
					$data['reasons']['email_blocklisted'][] = $bad_email_string;
				}
			}//end foreach
		}//end foreach

		if ( ! empty( $data['reasons']['email_blocklisted'] ) ) {
			$logged_reasons = implode( ',', $data['reasons']['email_blocklisted'] );
			cf7a_log( "The ip address {$data['remote_ip']} sent a mail matching bad email string/regex: {$logged_reasons}", 1 );
		}

		return $data;
	}
}
