<?php

namespace CF7_AntiSpam\Tests\PhpUnit\Tests;

use CF7_AntiSpam\Core\CF7_AntiSpam_Filters;
use PHPUnit\Framework\TestCase;

class CF7_AntiSpam_FiltersTest extends TestCase {

	/**
	 * @var CF7_AntiSpam_Filters
	 */
	private $filters;

	public function __construct( $name = null, $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );
		$this->filters = new CF7_AntiSpam_Filters();
	}

	public function testCf7a_check_dnsbl() {
		/* Barracuda returns always spam for 2.0.0.127 */
		$this->assertTrue( $this->filters->cf7a_check_dnsbl( "2.0.0.127", 'b.barracudacentral.org' ) );
		/* Barracuda returns always ham for 1.0.0.127 */
		$this->assertFalse( $this->filters->cf7a_check_dnsbl( "1.0.0.127", 'b.barracudacentral.org' ) );
	}

	public function testCf7a_reverse_ipv6() {
		$mail     = '::1';
		$reversed = $this->filters->cf7a_reverse_ipv6( $mail );
		$this->assertIsString( $reversed );
		$this->returnValue( '1.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0' );
	}

	public function testCf7a_reverse_ipv4() {
		$mail     = '192.168.1.1';
		$reversed = $this->filters->cf7a_reverse_ipv4( $mail );
		$this->assertIsString( $reversed );
		$this->returnValue( '1.1.168.192' );
	}

	public function testCf7a_init_languages_locales_array() {
		$tests = array(
			array(
				"string"   => 'en-US,en;q=0.9,it;q=0.8,it-IT;q=0.7',
				"expected" => array( 'en-US', 'en', 'it', 'it-IT' )
			));

		foreach ( $tests as $test ) {
			$result = cf7a_init_languages_locales_array( $test['string'] );
			$this->assertEquals( $test['expected'], $result, 'error expected ' . print_r( $test, true ) . " result " . print_r( $result, true ) );
		}
	}

	public function testCf7a_get_browser_languages_locales_array() {

		$tests = array(
			array(
				"string"   => 'en-US,en;q=0.9,it;q=0.8,it-IT;q=0.7',
				"expected" => array( 'languages' => array( 'en', 'it' ), 'locales' => array( 'US', 'IT' ) )
			),
			array(
				"string"   => 'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7,it;q=0.6,it-IT;q=0.5',
				"expected" => array( 'languages' => array( 'de', 'en', 'it' ), 'locales' => array( 'DE', 'US', 'IT' ) )
			),
			array(
				"string"   => 'en-US,en;q=0.5',
				"expected" => array( 'languages' => array( 'en' ), 'locales' => array( 'US' ) )
			),
			array(
				"string"   => 'da,en-GB;q=0.8,en;q=0.7',
				"expected" => array( 'languages' => array( 'da', 'en' ), 'locales' => array( 'GB' ) )
			),
			array(
				"string"   => 'zh-CN, zh-TW; q = 0.9, zh-HK; q = 0.8, zh; q = 0.7, en; q = 0.6',
				"expected" => array( 'languages' => array( 'zh', 'en' ), 'locales' => array( 'CN', 'TW', 'HK' ) )
			),
			array(
				"string"   => 'en-US,en;q=0.9,de;q=0.8,es;q=0.7,fr;q=0.6,it;q=0.5,pt;q=0.4,ru;q=0.3,ja;q=0.2,zh-CN;q=0.1,zh-TW;q=0.1',
				"expected" => array(
					'languages' => array( 'en', 'de', 'es', 'fr', 'it', 'pt', 'ru', 'ja', 'zh' ),
					'locales'   => array( 'US', 'CN', 'TW' )
				)
			),
			array(
				"string"   => 'ru-RU, be-BY;q=0.9, en-US;q=0.8, en;q=0.7',
				"expected" => array( 'languages' => array( 'ru', 'be', 'en' ), 'locales' => array( 'RU', 'BY', 'US' ) )
			)
		);

		foreach ( $tests as $test ) {
			$result = cf7a_get_browser_languages_locales_array( $test['string'] );
			$this->assertEquals( $test['expected'], $result, 'error expected ' . print_r( $test, true ) . " result " . print_r( $result, true ) );
		}
	}

	public function testCf7a_check_languages_locales_allowed() {
		/* cf7a_check_language_allowed - 1 current lang or loc - 2 NOT allowed languages - 3 allowed (and has the precedence over the not allowed if specified) */

		$testCases = [
			//LANGUAGES TEST CASES
			//TRUE
			[ 'lan_loc' => [ 'it' ], 'disalloweds' => [], 'alloweds' => [ 'it' ], 'assert' => true ],
			[ 'lan_loc' => [ 'it' ], 'disalloweds' => [ 'en' ], 'alloweds' => [ 'en' ], 'assert' => true ],
			[ 'lan_loc' => [ 'it' ], 'disalloweds' => [ 'en' ], 'alloweds' => [ 'fr', 'it' ], 'assert' => true ],
			[ 'lan_loc' => [ 'it' ], 'disalloweds' => [ 'en' ], 'alloweds' => [ 'it', 'fr' ], 'assert' => true ],
			[ 'lan_loc' => [ 'it' ], 'disalloweds' => [ 'en', 'fr' ], 'alloweds' => [ 'it' ], 'assert' => true ],
			[ 'lan_loc' => [ 'it' ], 'disalloweds' => [ 'en' ], 'alloweds' => [], 'assert' => true ],
			[ 'lan_loc' => [ 'it' ], 'disalloweds' => [], 'alloweds' => [], 'assert' => true ],
			[ 'lan_loc' => [ 'it' ], 'disalloweds' => [ '' ], 'alloweds' => [ '' ], 'assert' => true ],
			[ 'lan_loc' => [ 'it' ], 'disalloweds' => [ 'en', 'fr' ], 'alloweds' => [ 'IT' ], 'assert' => true ],
			//is case-sensitive and truthy
			[ 'lan_loc' => [ 'it' ], 'disalloweds' => [ 'en', 'IT' ], 'alloweds' => [ 'it' ], 'assert' => true ],
			//is case-sensitive
			//FALSE
			[ 'lan_loc' => [ 'it' ], 'disalloweds' => [ 'it' ], 'alloweds' => [ '' ], 'assert' => false ],
			[ 'lan_loc' => [ 'it' ], 'disalloweds' => [ 'it', 'en' ], 'alloweds' => [ '' ], 'assert' => false ],
			[ 'lan_loc' => [ 'it' ], 'disalloweds' => [ 'en', 'it' ], 'alloweds' => [ '' ], 'assert' => false ],
			//LOCALES TEST CASES
			//TRUE
			[ 'lan_loc' => [ 'IT' ], 'disalloweds' => [], 'alloweds' => [ 'IT' ], 'assert' => true ],
			[ 'lan_loc' => [ 'IT' ], 'disalloweds' => [ 'GB', 'FR' ], 'alloweds' => [ 'IT' ], 'assert' => true ],
			[ 'lan_loc' => [ 'IT' ], 'disalloweds' => [ 'it' ], 'alloweds' => [], 'assert' => true ],
			// is case-sensitive
			[ 'lan_loc' => [ 'IT' ], 'disalloweds' => [ 'GB', 'IT' ], 'alloweds' => [ 'FR', 'IT' ], 'assert' => true ],
			// alloweds has precedence
			//FALSE
			[ 'lan_loc' => [ 'IT' ], 'disalloweds' => [ 'IT' ], 'alloweds' => [ '' ], 'assert' => false ],
			[ 'lan_loc' => [ 'IT' ], 'disalloweds' => [ 'GB', 'IT' ], 'alloweds' => [ 'FR' ], 'assert' => false ],
			[ 'lan_loc' => [ 'IT' ], 'disalloweds' => [ 'GB', 'FR', 'IT' ], 'alloweds' => [ 'FR' ], 'assert' => false ],
		];


		foreach ( $testCases as $testCase ) {
			$result = $this->filters->cf7a_check_languages_locales_allowed( $testCase['lan_loc'], $testCase['disalloweds'], $testCase['alloweds'] );
			$this->assertEquals( $testCase['assert'],
				$result,
				'error expected ' . print_r( $testCase['assert'], true ) .
				" result " . print_r( $result, true ) .
				" array : " . print_r( $testCase, true )
			);
		}

	}

	public function testCf7a_get_languages_or_locales() {

		$tests = array(
			array(
				"string"            => 'languages',
				"languages_locales" => array( 'ru-RU', 'en', 'en-US', 'it-IT' ),
				"expected"          => array( 'ru', 'en', 'it' )
			),
			array(
				"string"            => 'locales',
				"languages_locales" => array( 'ru-RU', 'en', 'en-US', 'it-IT' ),
				"expected"          => array( 'RU', 'US', 'IT' )
			)
		);

		foreach ( $tests as $test ) {
			$result = $this->filters->cf7a_get_languages_or_locales( $test['languages_locales'], $test['string'] );
			$this->assertEquals( $test['expected'], $result, 'error expected ' . print_r( $test, true ) . " result " . print_r( $result, true ) );
		}
	}
}
