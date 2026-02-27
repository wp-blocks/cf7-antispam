<?php
/**
 * Filter for GeoIP.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;
use CF7_AntiSpam\Core\CF7_Antispam_Geoip;
use CF7_AntiSpam\Core\CF7_AntiSpam_Rules;
use Exception;

/**
 * Class Filter_Geoip
 */
class Filter_Geoip extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks GeoIP Location.
	 * If the location does not match, the spam check is skipped.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		$options = $data['options'];
		if ( intval( $options['check_geo_location'] ) !== 1 ) {
			return $data;
		}

		$geoip              = new CF7_Antispam_Geoip();
		$locales_allowed    = CF7_AntiSpam_Rules::cf7a_get_languages_or_locales( $options['languages_locales']['allowed'], 'locales' );
		$locales_disallowed = CF7_AntiSpam_Rules::cf7a_get_languages_or_locales( $options['languages_locales']['disallowed'], 'locales' );

		if ( ! empty( $geoip ) ) {
			try {
				$geoip_data      = $geoip->check_ip( $data['remote_ip'] );
				$geoip_continent = isset( $geoip_data['continent'] ) ? ( $geoip_data['continent'] ) : false;
				$geoip_country   = isset( $geoip_data['country'] ) ? ( $geoip_data['country'] ) : false;
				$geo_data        = array_filter( array( $geoip_continent, $geoip_country ) );

				if ( ! empty( $geo_data ) ) {
					if ( false === CF7_AntiSpam_Rules::cf7a_check_languages_locales_allowed( $geo_data, $locales_disallowed, $locales_allowed ) ) {
						$data['reasons']['geo_ip'][] = $geoip_continent . '-' . $geoip_country;
						$logged_reasons              = implode( ', ', $data['reasons']['geo_ip'] );
						cf7a_log( "The {$data['remote_ip']} is not allowed by geoip " . $logged_reasons, 1 );
					}
				} else {
					// Don't add to reasons if GeoIP lookup returned no data - just log it
					cf7a_log( "GeoIP lookup returned no data for {$data['remote_ip']}", 1 );
				}
			} catch ( Exception $e ) {
				cf7a_log( "unable to check geoip for {$data['remote_ip']} - " . $e->getMessage(), 1 );
			}
		}//end if
		return $data;
	}
}
