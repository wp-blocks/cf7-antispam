<?php
namespace CF7_AntiSpam\Engine;

/**
 * Fired at plugin update.
 *
 * This will call certain procedures for plugin updates
 *
 * @since      0.4.5
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 * @author     Codekraft Studio <info@codekraft.it>, gardenboi
 */

use CF7_AntiSpam\Admin\CF7_AntiSpam_Admin_Tools;
use CF7_AntiSpam\Core\CF7_AntiSpam_Filters;
/* TODO: delete the function in the activation script and write a new one that splits only the ";"
	Reason: the "languages_locales" and the old "languages" are the same string[] type, the actual code
	is splitted in the Filters class, therefore delete part of the procedure.
*
class CF7_AntiSpam_Updater {

	public $hc_version;
	public $db_version;
	public $current_options;

	public function __construct($hardcoded_version, $db_options_version, $options) {
		$this->hc_version = $hardcoded_version;
		$this->db_version = $db_options_version;
		$this->current_options = $options;
	}

	/**
	 * Checks the version of the hardcoded version against the database options version.
	 *
	 * @param string $hardcoded_version The hardcoded version to compare.
	 * @param string $db_options_version The database options version to compare.
	 * @param mixed $options cf7a_options.
	 * @return bool|void
	 */
	public function do_updates() {
		if (! version_compare($this->hc_version, $this->db_version, '>')) {
			return;
		}

		$new_options = $this->update_db_procedure_to_0_6_0();

		return update_option('cf7a_options', $new_options);

}




	public function update_db_procedure_to_0_6_0() {

		if (! array_key_exists('languages', $this->current_options)) {
			return;
		}

		$this->current_options['cf7a_version'] = $this->hc_version;

		$opt_languages_v0_4_5 = $this->current_options['languages'];
		unset($this->current_options['languages']);

		$filters = new CF7_AntiSpam_Filters();
		$this->current_options['languages_locales']['allowed']['languages'] = $filters->cf7a_get_language_locales( $opt_languages_v0_4_5['allowed'], 'languages' );
		$this->current_options['languages_locales']['allowed']['locales'] = $filters->cf7a_get_language_locales( $opt_languages_v0_4_5['allowed'], 'locales' );
		$this->current_options['languages_locales']['disallowed']['languages'] = $filters->cf7a_get_language_locales( $opt_languages_v0_4_5['disallowed'], 'languages' );
		$this->current_options['languages_locales']['disallowed']['locales'] = $filters->cf7a_get_language_locales( $opt_languages_v0_4_5['disallowed'], 'locales' );

		return $this->current_options;
	}

}
