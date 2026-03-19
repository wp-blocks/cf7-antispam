<?php
/**
 * Filter for Bot Fingerprint Extras.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;

/**
 * Class Filter_Bot_Fingerprint_Extras
 */
class Filter_Bot_Fingerprint_Extras extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks Bot Fingerprint Extras (User activity).
	 * If the fingerprint extras do not match, the spam check is skipped.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		$options = $data['options'];
		if ( intval( $options['check_bot_fingerprint_extras'] ) !== 1 ) {
			return $data;
		}

		$prefix = $this->get_prefix( $options );

		$extras = array(
			'activity'               => intval( $this->get_posted_value( $prefix . 'activity', 0 ) ),
			'mouseclick_activity'    => $this->get_posted_value( $prefix . 'mouseclick_activity' ) === 'passed',
			'mousemove_activity'     => $this->get_posted_value( $prefix . 'mousemove_activity' ) === 'passed',
			'webgl'                  => $this->get_posted_value( $prefix . 'webgl' ) === 'passed',
			'webgl_render'           => $this->get_posted_value( $prefix . 'webgl_render' ) === 'passed',
			'bot_fingerprint_extras' => empty( $this->get_posted_value( $prefix . 'bot_fingerprint_extras' ) ),
		);

		$fails = array();
		if ( $extras['activity'] < 3 ) {
			$fails[] = "activity {$extras['activity']}";
		}
		if ( empty( $extras['mouseclick_activity'] ) ) {
			$fails[] = 'mouseclick_activity';
		}
		if ( empty( $extras['mousemove_activity'] ) ) {
			$fails[] = 'mousemove_activity';
		}
		if ( empty( $extras['webgl'] ) ) {
			$fails[] = 'webgl';
		}
		if ( empty( $extras['webgl_render'] ) ) {
			$fails[] = 'webgl_render';
		}
		if ( empty( $extras['bot_fingerprint_extras'] ) ) {
			$fails[] = 'bot_fingerprint_extras';
		}

		if ( ! empty( $fails ) ) {
			$data['reasons']['bot_fingerprint_extras'] = $fails;
			cf7a_log( "The {$data['remote_ip']} ip hasn't passed fingerprint extra test", 1 );
		}

		return $data;
	}
}
