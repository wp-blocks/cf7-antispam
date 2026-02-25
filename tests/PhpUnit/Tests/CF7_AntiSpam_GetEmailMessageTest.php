<?php

namespace PhpUnit\Tests;

use PHPUnit\Framework\TestCase;
use CF7_AntiSpam\Core\CF7_AntiSpam_Rules;

class CF7_AntiSpam_GetEmailMessageTest extends TestCase {

    /**
     * Test get_email_message.
     */
    public function testGetEmailMessage() {
        // Mock data
        $posted_data = array(
            'your-message' => 'Hello World',
            'your-name' => 'John Doe'
        );
        $mail_tags = array(); // Can be empty if we pass explicit message tag

        // Case 1: Explicit message tag (e.g. from Flamingo/Admin settings)
        // logic: uses cf7a_get_mail_meta($tag) then cf7a_maybe_split_mail_meta
        // If passed "[ your-message ]", cf7a_get_mail_meta -> "your-message".
        $message_tag = '[ your-message ]';
        $result = CF7_AntiSpam_Rules::get_email_message($message_tag, $posted_data, $mail_tags);
        
        $this->assertEquals('Hello World', $result);

        // Case 2: Explicit message tag but field missing
        $message_tag_missing = '[ missing-field ]';
        $result_missing = CF7_AntiSpam_Rules::get_email_message($message_tag_missing, $posted_data, $mail_tags);
        $this->assertEquals('', $result_missing);
        
        // Case 3: No message tag passed (empty), finds in mail_tags
        // Note: search_for_message_field looks for 'message' or 'your-message'
        $tag_obj = new \stdClass();
        $tag_obj->name = 'your-message';
        // Mocking the tag object appropriately as it would appear in CF7 mail tags
        $mail_tags_found = array($tag_obj);
        
        $result_auto = CF7_AntiSpam_Rules::get_email_message('', $posted_data, $mail_tags_found);
        $this->assertEquals('Hello World', $result_auto);
        
        // Case 4: No tag, No fallback found -> create_message_from_posted_data
        $mail_tags_empty = array();
        // We expect empty if posted data is small/irrelevant because of min length rules in create_message_from_posted_data
        $result_fallback = CF7_AntiSpam_Rules::get_email_message('', $posted_data, $mail_tags_empty);
        // 'Hello World' is < 20 chars (default min length), so it might be filtered out if create_message_from_posted_data enforces it.
        // Let's check the implementation of create_message_from_posted_data if it has a default.
        // It has `apply_filters( 'cf7a_auto_message_minimum_field_length', 20 );`
        // Hello World is 11 chars. So it returns empty string.
        $this->assertEquals('', $result_fallback);
        
        $posted_data_long = array(
            'short' => 'hi',
            'long' => 'This is a very long message ensuring it is captured by the fallback logic which requires 20 chars.'
        );
        $result_fallback_long = CF7_AntiSpam_Rules::get_email_message('', $posted_data_long, $mail_tags_empty);
        $this->assertStringContainsString('This is a very long message', $result_fallback_long);
    }
}
