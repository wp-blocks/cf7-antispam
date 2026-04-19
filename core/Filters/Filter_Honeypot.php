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

		/* Collect every real field name registered in this form (any tag type). */
		$real_field_names = array();
		foreach ( $data['mail_tags'] as $mail_tag ) {
			if ( ! empty( $mail_tag['name'] ) ) {
				$real_field_names[] = $mail_tag['name'];
			}
		}

		/*
		 * Get the full candidate list, then subtract any name that belongs to a
		 * real form field. This prevents false positives when a site uses field
		 * names like "email" or "phone" that overlap with the legacy defaults.
		 */
		$all_honeypot_names  = cf7a_get_honeypot_input_names( $options['honeypot_input_names'] );
		$safe_honeypot_names = array_values( array_diff( $all_honeypot_names, $real_field_names ) );

		foreach ( $safe_honeypot_names as $honeypot_name ) {
			if ( ! empty( $this->get_posted_value( $honeypot_name ) ) ) {
				$data['reasons']['honeypot'][] = $honeypot_name;
			}
		}

		if ( ! empty( $data['reasons']['honeypot'] ) ) {
			$logged_reasons = implode( ', ', $data['reasons']['honeypot'] );
			cf7a_log( "The {$data['remote_ip']} has filled the input honeypot(s) {$logged_reasons}", 1 );
		}

		return $data;
	}
}
