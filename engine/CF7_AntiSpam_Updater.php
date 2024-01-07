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

class CF7_AntiSpam_Updater {

	public $hc_version;
	public $current_options;

	/**
	 * Initializes a new instance of the class.
	 *
	 * @param string $hardcoded_version The hardcoded version.
	 * @param mixed  $options The options.
	 */
	public function __construct( $hardcoded_version, $options ) {
		$this->hc_version      = $hardcoded_version;
		$this->current_options = $options;
	}

	/**
	 * Execute any refactoring procedure for plugin updates
	 *
	 * @return boolean true if successful or false if already updated, otherwise false because of error: check your db settings
	 */
	public function may_do_updates() {
		if ( version_compare( $this->hc_version, $this->current_options['cf7a_version'], '>' ) ) {
			$new_options = $this->update_db_procedure_to_0_6_0();
			if ( ! empty( $new_options ) ) {
				return update_option( 'cf7a_options', $new_options );
			}
		}
		return false;
	}

	/**
	 * Update the db procedure to 0.6.0
	 * Substitute "languages" with "
	 *
	 * @return void|mixed
	 */
	public function update_db_procedure_to_0_6_0() {
		if ( array_key_exists( 'languages', $this->current_options ) ) {
			$this->current_options['cf7a_version']                    = $this->hc_version;
			$this->current_options['languages_locales']['allowed']    = $this->current_options['languages']['allowed'];
			$this->current_options['languages_locales']['disallowed'] = $this->current_options['languages']['disallowed'];

			unset( $this->current_options['languages'] );

			return $this->current_options;
		}
	}

}
