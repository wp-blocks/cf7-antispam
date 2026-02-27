<?php
/**
 * Filter for Honeypot fields.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;

/**
 * Class Filter_Honeypot
 */
class Filter_Honeypot extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks visible honeypot fields.
	 * If the honeypot fields are not empty, the spam check is skipped.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		$options = $data['options'];
		if ( ! $options['check_honeypot'] ) {
			return $data;
		}

		$mail_tag_text = array();
		foreach ( $data['mail_tags'] as $mail_tag ) {
			if ( 'text' === $mail_tag['type'] || 'text*' === $mail_tag['type'] ) {
				$mail_tag_text[] = $mail_tag['name'];
			}
		}

		if ( ! empty( $mail_tag_text ) ) {
			$input_names    = cf7a_get_honeypot_input_names( $options['honeypot_input_names'] );
			$mail_tag_count = count( $input_names );

			for ( $i = 0; $i < $mail_tag_count; $i++ ) {
				$val          = $this->get_posted_value( $input_names[ $i ] );
				$has_honeypot = ! empty( $val );
				if ( $has_honeypot ) {
					$data['reasons']['honeypot'][] = $input_names[ $i ];
				}
			}

			if ( ! empty( $data['reasons']['honeypot'] ) ) {
				$logged_reasons = implode( ', ', $data['reasons']['honeypot'] );
				cf7a_log( "The {$data['remote_ip']} has filled the input honeypot(s) {$logged_reasons}", 1 );
			}
		}
		return $data;
	}
}
