<?php
/**
 * Filter for IP Allowlist.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/core/Filters
 * @author     Codekraft Studio <info@codekraft.it>
 */

namespace CF7_AntiSpam\Core\Filters;

use CF7_AntiSpam\Core\Abstract_CF7_AntiSpam_Filter;

/**
 * Class Filter_IP_Allowlist
 */
class Filter_IP_Allowlist extends Abstract_CF7_AntiSpam_Filter {

	/**
	 * Checks for IP allowlist.
	 * If the IP is allowlisted, the spam check is skipped.
	 *
	 * @param array $data The data array.
	 *
	 * @return array The data array.
	 */
	public function process( array $data ): array {
		$allowlist = $data['options']['ip_allowlist'] ?? null;
		if ( \CF7_AntiSpam\Core\CF7_Antispam_Blocklist::is_ip_allowlisted( (string) $data['remote_ip'], $allowlist ) ) {
			$data['is_allowlisted'] = true;
		}

		return $data;
	}
}
