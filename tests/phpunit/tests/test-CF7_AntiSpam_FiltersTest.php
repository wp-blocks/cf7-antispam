<?php

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
		$this->assertTrue ($this->filters->cf7a_check_dnsbl( "2.0.0.127", 'b.barracudacentral.org' ) );
		/* Barracuda returns always ham for 1.0.0.127 */
		$this->assertFalse($this->filters->cf7a_check_dnsbl( "1.0.0.127", 'b.barracudacentral.org' ) );
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

	public function testCf7a_check_language_allowed() {
		/* cf7a_check_language_allowed - 1 current lang - 2 NOT allowed languages - 3 allowed (and has the precedence over the not allowed if specified) */
		$this->assertTrue($this->filters->cf7a_check_language_allowed( array('it'), array(), array('it') ));
		$this->assertTrue($this->filters->cf7a_check_language_allowed( array('it'), array('en'), array('it') ));
		$this->assertTrue($this->filters->cf7a_check_language_allowed( array('it'), array('en'), array("fr","it") ));
		$this->assertTrue($this->filters->cf7a_check_language_allowed( array('it'), array('en'), array("it", "fr") ));
		$this->assertTrue($this->filters->cf7a_check_language_allowed( array('it'), array("en", "fr"), array('it') ));
		$this->assertTrue($this->filters->cf7a_check_language_allowed( array('it'), array("en"), array() ));
		$this->assertTrue($this->filters->cf7a_check_language_allowed( array('it'), array(), array() ));
		$this->assertTrue($this->filters->cf7a_check_language_allowed( array('it'), array(""), array("") ));

		$this->assertTrue($this->filters->cf7a_check_language_allowed( array('it'), array("en", "fr"), array("it") ));
		$this->assertTrue($this->filters->cf7a_check_language_allowed( array('it'), array("en", "it"), array("it") ));

		$this->assertFalse($this->filters->cf7a_check_language_allowed( array('it'), array("it"), array("") ));
		$this->assertFalse($this->filters->cf7a_check_language_allowed( array('it'), array("it", "en"), array("") ));
		$this->assertFalse($this->filters->cf7a_check_language_allowed( array('it'), array("en", "it"), array("") ));
	}

}
