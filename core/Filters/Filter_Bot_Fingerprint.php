<?php
/**
 * Filter for Bot Fingerprint.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;

/**
 * Class Filter_Bot_Fingerprint
 */
class Filter_Bot_Fingerprint extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks Browser Fingerprint (JS based).
	 * If the fingerprint does not match, the spam check is skipped.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		$options = $data['options'];
		if ( intval( $options['check_bot_fingerprint'] ) !== 1 ) {
			return $data;
		}

		$prefix = $this->get_prefix( $options );

		$bot_fingerprint = array(
			'timezone'        => $this->get_posted_value( $prefix . 'timezone' ),
			'platform'        => $this->get_posted_value( $prefix . 'platform' ),
			'screens'         => $this->get_posted_value( $prefix . 'screens' ),
			'memory'          => intval( $this->get_posted_value( $prefix . 'memory' ) ),
			'user_agent'      => $this->get_posted_value( $prefix . 'user_agent' ),
			'app_version'     => $this->get_posted_value( $prefix . 'app_version' ),
			'webdriver'       => $this->get_posted_value( $prefix . 'webdriver' ),
			'session_storage' => $this->get_posted_value( $prefix . 'session_storage' ),
			'bot_fingerprint' => $this->get_posted_value( $prefix . 'bot_fingerprint' ),
			'touch'           => $this->get_posted_value( $prefix . 'touch' ),
		);

		$fails = array();
		if ( ! $bot_fingerprint['timezone'] ) {
			$fails[] = 'timezone';
		}
		if ( ! $bot_fingerprint['platform'] ) {
			$fails[] = 'platform';
		}
		if ( ! $bot_fingerprint['screens'] ) {
			$fails[] = 'screens';
		}
		if ( ! $bot_fingerprint['user_agent'] ) {
			$fails[] = 'user_agent';
		}
		if ( ! $bot_fingerprint['app_version'] ) {
			$fails[] = 'app_version';
		}
		if ( ! $bot_fingerprint['webdriver'] ) {
			$fails[] = 'webdriver';
		}
		if ( null === $bot_fingerprint['session_storage'] ) {
			$fails[] = 'session_storage';
		}
		if ( 5 !== strlen( $bot_fingerprint['bot_fingerprint'] ) ) {
			$fails[] = 'bot_fingerprint';
		}

		// Safari on all platforms doesn't support navigator.deviceMemory, neither does Firefox or IE.
		$is_ios     = $this->get_posted_value( $prefix . 'isIos' );
		$is_ff      = $this->get_posted_value( $prefix . 'isFFox' );
		$is_ie      = $this->get_posted_value( $prefix . 'isIE' );
		$is_safari  = $this->get_posted_value( $prefix . 'isSafari' );
		$is_android = $this->get_posted_value( $prefix . 'isAndroid' );

		$memory_unsupported_browser = $is_ios || $is_ff || $is_ie || $is_safari;
		if ( $memory_unsupported_browser ) {
			if ( $bot_fingerprint['memory'] ) {
				$fails[] = 'memory_supported';
			}
		} elseif ( ! $bot_fingerprint['memory'] ) {
			$fails[] = 'memory';
		}

		if ( $is_ios || $is_android ) {
			if ( ! $bot_fingerprint['touch'] ) {
				$fails[] = 'touch';
			}
		}

		if ( ! empty( $fails ) ) {
			$data['reasons']['bot_fingerprint'] = $fails;
			$logged_reasons                     = implode( ', ', $fails );
			cf7a_log( "The {$data['remote_ip']} ip hasn't passed fingerprint test ({$logged_reasons})", 1 );
		}

		return $data;
	}
}
