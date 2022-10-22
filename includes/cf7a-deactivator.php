<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class CF7_AntiSpam_Deactivator {

	/**
	 * * When the plugin is deactivated, delete all user related metadata (the setting panel notice)
	 */
	public static function deactivate() {
		if ( CF7ANTISPAM_DEBUG ) {
			cf7a_log( 'plugin deactivated' );
		}

		delete_metadata( 'user', 0, 'cf7a_hide_welcome_panel_on', '', true );
	}
}
