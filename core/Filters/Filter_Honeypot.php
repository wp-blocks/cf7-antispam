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

		/*
		 * Resolve only the honeypot field names the administrator has explicitly
		 * configured.  No legacy defaults are injected — cf7a_get_honeypot_input_names()
		 * now returns a clean, de-duplicated array of exactly what was saved in options.
		 * If nothing has been configured the array will be empty and the loop is skipped.
		 */
		$input_names = cf7a_get_honeypot_input_names( $options['honeypot_input_names'] );

		foreach ( $input_names as $honeypot_name ) {
			// Flag the submission if the explicitly-configured honeypot field is non-empty.
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
