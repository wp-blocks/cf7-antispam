<?php

namespace PhpUnit\Tests;

use PHPUnit\Framework\TestCase;

class CF7_AntiSpam_MailFunctionsTest extends TestCase {

    /**
     * Test cf7a_maybe_split_mail_meta to verify how it handles multiple fields and edge cases.
     */
    public function testCf7a_maybe_split_mail_meta() {
        $posted_data = array(
            'your-message' => 'First content',
            'your-message2' => 'Second content',
            'your-message3' => 'Third content',
            'single-field' => 'Single content',
            'empty-field' => '',
            'zero-field' => '0'
        );

        // Case 1: Multiple fields requested
        $message_tag = '[your-message] [your-message2]';
        $result = cf7a_maybe_split_mail_meta($posted_data, $message_tag);
        $this->assertStringContainsString('your-message: First content', $result);
        $this->assertStringContainsString('your-message2: Second content', $result);

        // Case 2: Some fields missing in multiple request
        // The function seems to skip missing fields in the loop if !empty check fails.
        $message_tag_missing = '[your-message] [missing-field]';
        $result_missing = cf7a_maybe_split_mail_meta($posted_data, $message_tag_missing);
        $this->assertStringContainsString('your-message: First content', $result_missing);
        $this->assertStringNotContainsString('missing-field', $result_missing);

        // Case 3: Single valid field (no brackets in key, but passed as tag name)
        // If passed 'single-field', pattern '] [' is not found.
        $result_single = cf7a_maybe_split_mail_meta($posted_data, 'single-field');
        $this->assertEquals('Single content', $result_single);

        // Case 4: Single missing field
        $result_not_found = cf7a_maybe_split_mail_meta($posted_data, 'non-existent');
        $this->assertEquals('', $result_not_found);

        // Case 5: Empty posted data
        $result_empty_data = cf7a_maybe_split_mail_meta([], 'single-field');
        $this->assertEquals('', $result_empty_data);

        // Case 6: Tag with brackets but single field? 
        // If msg tag is '[single-field]', strict check for explode pattern '] [' might fail if it's just one.
        // strpos( '[single-field]', '] [' ) === false.
        // So it goes to else -> $posted_data['[single-field]'] -> likely undefined.
        $result_bracket_single = cf7a_maybe_split_mail_meta($posted_data, '[single-field]');
        $this->assertEquals('', $result_bracket_single, "Should return empty string if tag has brackets but pattern not found and key doesn't include brackets");

        // Case 7: Zero value (edge case for empty check)
        // !empty('0') is true in PHP? No, "0" is empty.
        // If code uses !empty(), then '0' is skipped.
        // Code: if ( ! empty( $posted_data[ $tag_chunk ] ) )
        $result_zero = cf7a_maybe_split_mail_meta($posted_data, 'zero-field');
        // '0' is considered empty by !empty(), so sanitize_textarea_field might return string?
        // Wait, the SINGLE field path uses: isset( ... ) ? ... : false
        // The MULTIPLE field path uses: if ( ! empty( ... ) )
        
        // 7a: Single '0' field
        // isset is true. sanitize_textarea_field('0') -> '0'.
        $this->assertEquals('0', $result_zero);

        // 7b: Multiple '0' field
        // '[zero-field] [your-message]'
        $message_tag_zero = '[zero-field] [your-message]';
        $result_zero_multi = cf7a_maybe_split_mail_meta($posted_data, $message_tag_zero);
        // if !empty('0') is false, it skips.
        $this->assertStringNotContainsString('zero-field', $result_zero_multi); 
        $this->assertStringContainsString('your-message', $result_zero_multi);
    }

    /**
     * Test cf7a_get_mail_meta to verify stripping brackets.
     */
    public function testCf7a_get_mail_meta() {
        // cf7a_get_mail_meta uses substr($tag, 2, -2). 
        // This implies input format is "[ tag ]" or similiar (2 chars padding).
        // If "[email]" is passed, it returns "mai".
        // Use padded format for successful test:
        $this->assertEquals('email', cf7a_get_mail_meta('[ email ]'));
        $this->assertEquals('your-name', cf7a_get_mail_meta('[ your-name ]'));
        
        // Edge cases
        // "[t]" -> substr("[t]", 2, -2) -> "t" (len 3. 2 start. -2 end. "t" is index 1. Wait.
        // "[t]" indices: 0:[ 1:t 2:]
        // substr start 2 is "]".
        // length -2 is 1? (len 3 - 2 = 1).
        // substr("abc", 2, -2). Len 3. Start 2 ("c"). Length 3-2=1? No. PHP substr using negative length means omit last N chars.
        // If start is 2, and we omit last 2.
        // "[t]": start 2 ("]"). Omit last 2 ("t]"). 
        // Result empty.
        // "[ t ]" len 5. Omit first 2, last 2. returns "t".
        $this->assertEquals('t', cf7a_get_mail_meta('[ t ]'));
        
        // Code: substr( $tag, 2, -2 )
        // "no-brackets" -> starts at index 2 ('-'), matches up to length-2 ('t'). Result "-bracke"
        // This function EXPECTS brackets.
        $this->assertEquals('-bracke', cf7a_get_mail_meta('no-brackets'));
    }
}
