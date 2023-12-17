<?php

namespace CF7_AntiSpam\Tests\PhpUnit\Tests;

use CF7_AntiSpam\Engine\CF7_AntiSpam_Updater;
use PHPUnit\Framework\TestCase;

class CF7_AntiSpam_UpdaterTest extends TestCase {

	public function testUpdate_db_procedure_to_0_6_0() {

		$v0_6_0 = '0.6.0';

		$options_test_new = array(
			'cf7a_version' => '0.6.0',
			'languages_locales' => array(
				'allowed' => array( 'en', 'it-IT' ),
				'disallowed' => array( 'fr', 'FR'),
			)
		);

		$options_test_old = array(
			'cf7a_version' => '0.4.5',
			'languages' => array(
				'allowed' => array( 'en', 'it-IT' ),
				'disallowed' => array( 'fr', 'FR'),
			)
		);

		$thisInstance = new CF7_AntiSpam_Updater($v0_6_0, $options_test_old);
		$result = $thisInstance->update_db_procedure_to_0_6_0();
		$this->assertEquals($options_test_new, $result ,
			'error expected ' . print_r( $options_test_new, true ) .
			" result " . print_r( $result, true ));
	}


}
