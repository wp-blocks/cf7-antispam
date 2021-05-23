<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class CF7_AntiSpam_Deactivator {

	public static function deactivate() {
		if (CF7ANTISPAM_DEBUG) error_log(print_r(CF7ANTISPAM_LOG_PREFIX.'plugin deactivated',true));
	}
}