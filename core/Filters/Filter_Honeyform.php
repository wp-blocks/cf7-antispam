<?php
/**
 * Filter for Honeyform.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;

/**
 * Class Filter_Honeyform
 */
class Filter_Honeyform extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks the HoneyForm (CSS hidden field).
	 * If the field is not empty, the spam check is skipped.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		$options = $data['options'];
		if ( intval( $options['check_honeyform'] ) === 1 ) {
			$form_class = sanitize_html_class( $options['cf7a_customizations_class'] );

			if ( $this->get_posted_value( '_wpcf7_' . $form_class ) !== null ) {
				$data['is_spam']                = true;
				$data['reasons']['honeyform'][] = 'true';
			}
		}
		return $data;
	}
}
