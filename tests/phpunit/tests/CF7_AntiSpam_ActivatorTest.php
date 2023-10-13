<?php

use PHPUnit\Framework\TestCase;

class CF7_AntiSpam_ActivatorTest extends TestCase {

	/*
	  public function testInstall() {
		$res = CF7_AntiSpam_Activator::install();
		$this->assertTrue( $res );
	}

	public function testActivate() {
		$res = CF7_AntiSpam_Activator::activate();
		$this->assertTrue( $res );
	}

	public function testUpdate_options() {
		$res = CF7_AntiSpam_Activator::update_options();
		$this->assertTrue( $res );
	}*/

	public function testEmpty() {
		$stack = array();
		$this->assertEmpty( $stack );

		return $stack;
	}

	/**
	 * @depends testEmpty
	 *
	 * @param array $stack
	 *
	 * @return array
	 */
	public function testPush( array $stack ) {
		array_push( $stack, 'foo' );
		$this->assertSame( 'foo', $stack[ count( $stack ) - 1 ] );
		$this->assertNotEmpty( $stack );

		return $stack;
	}

	public function testOne() {
		$this->assertTrue(true);
	}
}
