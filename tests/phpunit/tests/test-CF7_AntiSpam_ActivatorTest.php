<?php

use PHPUnit\Framework\TestCase;

require_once '../../../cf7-antispam.php';
require_once '../../../includes/cf7a-activator.php';

class CF7_AntiSpam_ActivatorTest extends TestCase {

	public function testInstall() {
		$res = CF7_AntiSpam_Activator::install();
		$this->assertTrue($res);
	}

	public function testActivate() {
		$res = CF7_AntiSpam_Activator::activate();
		$this->assertTrue($res);
	}

	public function testUpdate_options() {
		$res = CF7_AntiSpam_Activator::update_options();
		$this->assertTrue($res);
	}
}
