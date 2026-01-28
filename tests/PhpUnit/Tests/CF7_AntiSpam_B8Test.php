<?php

namespace CF7_AntiSpam\Tests;

use CF7_AntiSpam\Core\CF7_AntiSpam_B8;
use PHPUnit\Framework\TestCase;

class CF7_AntiSpam_B8Test extends TestCase {

	public function test_sanitize_message_strips_emojis() {
		$emoji_string = "Hello 🙂 World 🌍";
		$clean_string = \CF7_AntiSpam\Core\CF7_AntiSpam_B8::sanitize_message( $emoji_string );

		$this->assertEquals( "Hello  World ", $clean_string );
	}
}
