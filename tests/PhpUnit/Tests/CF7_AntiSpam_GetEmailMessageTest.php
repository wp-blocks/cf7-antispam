<?php

namespace PhpUnit\Tests;

use PHPUnit\Framework\TestCase;
use CF7_AntiSpam\Core\CF7_AntiSpam_Filters;

class CF7_AntiSpam_GetEmailMessageTest extends TestCase {

    /**
     * Test get_email_message using reflection since it is private.
     */
    public function testGetEmailMessage() {
        $filters = new CF7_AntiSpam_Filters();
        $reflection = new \ReflectionClass(get_class($filters));
        $method = $reflection->getMethod('get_email_message');
        $method->setAccessible(true);

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
        $result = $method->invokeArgs($filters, array($message_tag, $posted_data, $mail_tags));
        
        $this->assertEquals('Hello World', $result);

        // Case 2: Explicit message tag but field missing
        $message_tag_missing = '[ missing-field ]';
        $result_missing = $method->invokeArgs($filters, array($message_tag_missing, $posted_data, $mail_tags));
        $this->assertEquals('', $result_missing);
        
        // Case 3: No message tag passed (empty), finds in mail_tags
        $tag_obj = new \stdClass();
        $tag_obj->name = 'your-message';
        $mail_tags_found = array($tag_obj);
        
        $result_auto = $method->invokeArgs($filters, array('', $posted_data, $mail_tags_found));
        $this->assertEquals('Hello World', $result_auto);
        
        // Case 4: No tag, No fallback found -> create_message_from_posted_data
        $mail_tags_empty = array();
        $result_fallback = $method->invokeArgs($filters, array('', $posted_data, $mail_tags_empty));
        
        $posted_data_long = array(
            'short' => 'hi',
            'long' => 'This is a very long message ensuring it is captured by the fallback logic which requires 20 chars.'
        );
        $result_fallback_long = $method->invokeArgs($filters, array('', $posted_data_long, $mail_tags_empty));
        $this->assertStringContainsString('This is a very long message', $result_fallback_long);
    }
}
