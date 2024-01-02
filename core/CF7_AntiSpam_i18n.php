<?php

namespace CF7_AntiSpam\Core;

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 * @author     Codekraft Studio <info@codekraft.it>
 */

/**
 * It loads the plugin's text domain for translation
 */
class CF7_AntiSpam_i18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    0.1.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'cf7-antispam',
			false,
			CF7ANTISPAM_PLUGIN_DIR . '/languages/'
		);
	}
}
