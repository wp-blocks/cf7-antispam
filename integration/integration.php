<?php

/**
 * CF7_Antispam context class.
 *
 * @package   cf7_antispam
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

require_once CF7ANTISPAM_PLUGIN_DIR . '/integration/service.php' ;
/**
 * call the integration action to mount our plugin as a component
 * into the intefration page
 */

function cf7_antispam_register_service() {
	$integration = WPCF7_Integration::get_instance();
	$integration->add_service(
		'cf7-antispam',
		WPCF7_Antispam::get_instance()
	);

}

add_action( 'wpcf7_init', 'cf7_antispam_register_service',1,0);



