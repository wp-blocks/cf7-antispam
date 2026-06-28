<?php
/**
 * Filter for max links allowed.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;

/**
 * Class Filter_Max_Links.
 *
 * Checks if the total number of links submitted across all form fields
 * exceeds the configured maximum.
 */
class Filter_Max_Links extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Process the filter logic.
	 *
	 * @param array $spam_data The spam data context.
	 *
	 * @return array The updated spam data context.
	 */
	protected function process( array $spam_data ): array {
		if ( empty( $spam_data['posted_data'] ) ) {
			return $spam_data;
		}

		// Concatenate all text-based fields into a single string
		$all_text = '';
		foreach ( $spam_data['posted_data'] as $field_value ) {
			if ( is_string( $field_value ) ) {
				$all_text .= ' ' . $field_value;
			} elseif ( is_array( $field_value ) ) {
				// Handle array payloads securely
				$all_text .= ' ' . implode( ' ', array_map( 'sanitize_text_field', $field_value ) );
			}
		}

		// Security: Prevent CPU exhaustion by truncating massive payloads (e.g., limit to 10KB)
		if ( strlen( $all_text ) > 10240 ) {
			$all_text = substr( $all_text, 0, 10240 );
		}

		if ( empty( trim( $all_text ) ) ) {
			return $spam_data;
		}

		// Security: Use native WP function to avoid ReDoS vulnerabilities
		$extracted_urls = wp_extract_urls( $all_text );
		$count          = count( $extracted_urls );

		// Get max links threshold (default to 2 if not set)
		$max_links = isset( $spam_data['options']['max_links'] ) ? absint( $spam_data['options']['max_links'] ) : 2;

		if ( $count > $max_links ) {
			// Increase the score to trigger a spam ban
			$spam_data['spam_score']               += 1.0;
			$spam_data['reasons']['too_many_links'] = array( sprintf( 'Too many links in submission (%d found, max allowed %d)', $count, $max_links ) );
		}

		return $spam_data;
	}
}
