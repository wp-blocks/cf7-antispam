<?php

namespace CF7_AntiSpam\Tests\PhpUnit\Tests;

use CF7_AntiSpam\Core\CF7_AntiSpam;
use CF7_AntiSpam\Core\CF7_Antispam_Blocklist;
use CF7_AntiSpam\Engine\CF7_AntiSpam_Activator;
use CF7_AntiSpam\Core\Filters\Filter_IP_Allowlist;
use CF7_AntiSpam\Core\Filters\Filter_Empty_IP;
use CF7_AntiSpam\Core\Filters\Filter_Bad_IP;
use CF7_AntiSpam\Core\Filters\Filter_Honeyform;
use CF7_AntiSpam\Core\Filters\Filter_Bad_Words;
use CF7_AntiSpam\Core\Filters\Filter_Time_Submission;
use CF7_AntiSpam\Core\Filters\Filter_Bad_Email_Strings;
use CF7_AntiSpam\Core\Filters\Filter_User_Agent;
use CF7_AntiSpam\Core\Filters\Filter_Honeypot;
use CF7_AntiSpam\Core\Filters\Filter_IP_Blocklist_History;
use PHPUnit\Framework\TestCase;

class CF7_AntiSpam_FiltersTest extends TestCase {

	/**
	 * @var array
	 */
	private $base_spam_data;

	/**
	 * @var array
	 */
	private $options;

	/**
	 * Setup before each test.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Ensure tables exist
		CF7_AntiSpam_Activator::install();

		$this->options = CF7_AntiSpam::get_options();

		// Initialize a standard clean state for data to pass through filters
		$this->base_spam_data = array(
			'submission'    => null, // Mock this if needed
			'options'       => $this->options,
			'prefix'        => CF7ANTISPAM_PREFIX,
			'posted_data'   => array(),
			'remote_ip'     => '192.168.1.10',
			'cf7_remote_ip' => '192.168.1.10',
			'emails'        => array( 'test@example.com' ),
			'message'       => 'Hello world',
			'mail_tags'     => array(),
			'user_agent'    => 'Mozilla/5.0',
			'spam_score'    => 0,
			'is_spam'       => false,
			'reasons'       => array(),
			'is_allowlisted'=> false,
		);

		// Ensure global $_POST is clean
		$_POST = array();
	}

	// -------------------------------------------------------------------------
	// TEST 1: IP Whitelist
	// -------------------------------------------------------------------------

	public function test_filter_ip_allowlist_matches_valid_ip() {
		// Arrange
		$data = $this->base_spam_data;
		$data['options']['ip_allowlist'] = array( '192.168.1.10' );
		$data['remote_ip'] = '192.168.1.10';

		// Act
		$filter = new Filter_IP_Allowlist();
		$result = $filter->check( $data );

		// Assert
		$this->assertTrue( $result['is_allowlisted'], 'IP should be allowlisted.' );
	}

	public function test_filter_ip_allowlist_ignores_unknown_ip() {
		// Arrange
		$data = $this->base_spam_data;
		$data['options']['ip_allowlist'] = array( '10.0.0.1' );
		$data['remote_ip'] = '192.168.1.10';

		// Act
		$filter = new Filter_IP_Allowlist();
		$result = $filter->check( $data );

		// Assert
		$this->assertFalse( $result['is_allowlisted'], 'IP should NOT be allowlisted.' );
	}

	// -------------------------------------------------------------------------
	// TEST 2: Empty IP
	// -------------------------------------------------------------------------

	public function test_filter_empty_ip_detects_missing_ip() {
		// Arrange
		$data = $this->base_spam_data;
		$data['remote_ip'] = ''; // Empty
		$data['cf7_remote_ip'] = '';

		// Act
		$filter = new Filter_Empty_IP();
		$result = $filter->check( $data );

		// Assert
		$this->assertTrue( $result['is_spam'], 'Should be spam if IP is missing.' );
		$this->assertArrayHasKey( 'no_ip', $result['reasons'] );
		$this->assertEquals( 1, $result['spam_score'] );
	}

	public function test_filter_empty_ip_passes_valid_ip() {
		// Arrange
		$data = $this->base_spam_data;
		$data['remote_ip'] = '123.123.123.123';

		// Act
		$filter = new Filter_Empty_IP();
		$result = $filter->check( $data );

		// Assert
		$this->assertFalse( $result['is_spam'] );
		$this->assertEquals( 0, $result['spam_score'] );
	}

	// -------------------------------------------------------------------------
	// TEST 3: Bad IP List
	// -------------------------------------------------------------------------

	public function test_filter_bad_ip_detects_blacklisted_ip() {
		// Arrange
		$data = $this->base_spam_data;
		$data['options']['check_bad_ip'] = 1;
		$data['options']['bad_ip_list'] = array( '1.2.3.4', '5.6.7.8' );
		$data['remote_ip'] = '5.6.7.8';

		// Act
		$filter = new Filter_Bad_IP();
		$result = $filter->check( $data );

		// Assert
		$this->assertTrue( $result['is_spam'] );
		$this->assertStringContainsString( '5.6.7.8', $result['reasons']['bad_ip'] );
	}

	public function test_filter_bad_ip_passes_clean_ip() {
		// Arrange
		$data = $this->base_spam_data;
		$data['options']['check_bad_ip'] = 1;
		$data['options']['bad_ip_list'] = array( '1.2.3.4' );
		$data['remote_ip'] = '9.9.9.9'; // Safe IP

		// Act
		$filter = new Filter_Bad_IP();
		$result = $filter->check( $data );

		// Assert
		$this->assertFalse( $result['is_spam'] );
	}

	// -------------------------------------------------------------------------
	// TEST 4: HoneyForm (Hidden Field)
	// -------------------------------------------------------------------------

	public function test_filter_honeyform_detects_filled_field() {
		// Arrange
		$data = $this->base_spam_data;
		$data['options']['check_honeyform'] = 1;
		$data['options']['cf7a_customizations_class'] = 'my-trap';

		// Simulate $_POST submission of the hidden field
		$_POST['_wpcf7_my-trap'] = 'I am a bot';

		// Act
		$filter = new Filter_Honeyform();
		$result = $filter->check( $data );

		// Assert
		$this->assertTrue( $result['is_spam'] );
		$this->assertEquals( 'true', $result['reasons']['honeyform'] );
	}

	public function test_filter_honeyform_passes_empty_field() {
		// Arrange
		$data = $this->base_spam_data;
		$data['options']['check_honeyform'] = 1;
		$data['options']['cf7a_customizations_class'] = 'my-trap';

		// Ensure $_POST is empty for that key
		unset($_POST['_wpcf7_my-trap']);

		// Act
		$filter = new Filter_Honeyform();
		$result = $filter->check( $data );

		// Assert
		$this->assertFalse( $result['is_spam'] );
	}

	// -------------------------------------------------------------------------
	// TEST 5: Bad Words
	// -------------------------------------------------------------------------

	public function test_filter_bad_words_detects_profanity() {
		// Arrange
		$data = $this->base_spam_data;
		$data['options']['check_bad_words'] = 1;
		$data['options']['bad_words_list'] = array( 'buy now', 'cheap' );
		$data['options']['score'] = array( '_bad_string' => 5 );
		$data['message'] = 'Hello, please buy now very cheap!';

		// Act
		$filter = new Filter_Bad_Words();
		$result = $filter->check( $data );

		// Assert
		$this->assertTrue( $result['spam_score'] >= 5 );
		$this->assertStringContainsString( 'buy now', $result['reasons']['bad_word'] );
	}

	public function test_filter_bad_words_passes_clean_message() {
		// Arrange
		$data = $this->base_spam_data;
		$data['options']['check_bad_words'] = 1;
		$data['options']['bad_words_list'] = array( 'buy now' );
		$data['message'] = 'Just saying hello.';

		// Act
		$filter = new Filter_Bad_Words();
		$result = $filter->check( $data );

		// Assert
		$this->assertEquals( 0, $result['spam_score'] );
		$this->assertArrayNotHasKey( 'bad_word', $result['reasons'] );
	}

	// -------------------------------------------------------------------------
	// TEST 6: Time Submission
	// -------------------------------------------------------------------------

	public function test_filter_time_submission_detects_too_fast() {
		// Arrange
		$data = $this->base_spam_data;
		$time_elapsed = 5;
		$data['submission']['time'] = time() - $time_elapsed;
		$data['options']['check_time'] = 1;
		$data['options']['check_time_min'] = 6; // Min 6 seconds
		$prefix = $data['options']['cf7a_customizations_prefix'];
		$_POST[ $prefix . '_timestamp' ] = cf7a_crypt(time() - 5, $data['options']['cf7a_cipher']);

		// Act
		$filter = new Filter_Time_Submission();
		$result = $filter->check( $data );

		// Assert
		$this->assertFalse( $result['is_spam'] ); // False because the time check doesn't force the mail to be spam if wrong
		$this->assertEquals( $data['options']['score']['_time'], $result['spam_score'] ); // Assert that the spam score is correct
		$this->assertIsArray( $result['reasons'] ); // Assert that the reasons are not empty
		$this->assertEquals( $time_elapsed, $result['reasons']['min_time_elapsed'] ); // Assert that the time elapsed is correct
	}

	// -------------------------------------------------------------------------
	// TEST 7: Bad Email Strings
	// -------------------------------------------------------------------------

	public function test_filter_bad_email_strings_detects_spam_domain() {
		// Arrange
		$data = $this->base_spam_data;
		$data['options']['check_bad_email_strings'] = 1;
		$data['options']['bad_email_strings_list'] = array( '.xyz', 'spam.com' );
		$data['options']['score'] = array( '_bad_string' => 4 );
		$data['emails'] = array( 'user@spam.com' );

		// Act
		$filter = new Filter_Bad_Email_Strings();
		$result = $filter->check( $data );

		// Assert
		$this->assertEquals( 4, $result['spam_score'] );
		$this->assertStringContainsString( 'spam.com', $result['reasons']['email_blocklisted'] );
	}

	public function test_filter_bad_email_strings_passes_good_email() {
		// Arrange
		$data = $this->base_spam_data;
		$data['options']['check_bad_email_strings'] = 1;
		$data['options']['bad_email_strings_list'] = array( 'bad.com' );
		$data['emails'] = array( 'user@google.com' );

		// Act
		$filter = new Filter_Bad_Email_Strings();
		$result = $filter->check( $data );

		// Assert
		$this->assertEquals( 0, $result['spam_score'] );
	}

	// -------------------------------------------------------------------------
	// TEST: User Agent Filter
	// -------------------------------------------------------------------------

	public function test_filter_user_agent_detects_empty_ua() {
		// Arrange
		$data = $this->base_spam_data;
		$data['options']['check_bad_user_agent'] = 1;
		$data['options']['score']['_detection'] = 2;
		$data['user_agent'] = ''; // Empty UA

		// Act
		$filter = new Filter_User_Agent();
		$result = $filter->check( $data );

		// Assert
		$this->assertEquals( 2, $result['spam_score'] );
		$this->assertEquals( 'empty', $result['reasons']['user_agent'] );
	}

	public function test_filter_user_agent_detects_blacklisted_bot() {
		// Arrange
		$data = $this->base_spam_data;
		$data['options']['check_bad_user_agent'] = 1;
		$data['options']['bad_user_agent_list'] = array( 'BadBot', 'CrawlerX' );
		$data['options']['score']['_bad_string'] = 3;
		$data['user_agent'] = 'Mozilla/5.0 (compatible; BadBot/1.0)'; // Contains "BadBot"

		// Act
		$filter = new Filter_User_Agent();
		$result = $filter->check( $data );

		// Assert
		$this->assertEquals( 3, $result['spam_score'] );
		$this->assertStringContainsString( 'BadBot', $result['reasons']['user_agent'] );
	}

	public function test_filter_user_agent_passes_valid_ua() {
		// Arrange
		$data = $this->base_spam_data;
		$data['options']['check_bad_user_agent'] = 1;
		$data['options']['bad_user_agent_list'] = array( 'BadBot' );
		$data['user_agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

		// Act
		$filter = new Filter_User_Agent();
		$result = $filter->check( $data );

		// Assert
		$this->assertEquals( 0, $result['spam_score'] );
		$this->assertArrayNotHasKey( 'user_agent', $result['reasons'] );
	}

	// -------------------------------------------------------------------------
	// TEST: Honeypot Filter
	// -------------------------------------------------------------------------

	public function test_filter_honeypot_detects_filled_field() {
		// Arrange
		$data = $this->base_spam_data;
		$data['options']['check_honeypot'] = 1;
		$data['options']['honeypot_input_names'] = array( 'hp_email', 'hp_phone' );
		$data['options']['score']['_honeypot'] = 10;

		// We must simulate form tags so the filter knows it's a valid form to check
		$data['mail_tags'] = array(
			array( 'type' => 'text', 'name' => 'your-name' )
		);

		// Crucial: Assume cf7a_get_honeypot_input_names returns the input array keys
		// We simulate the global $_POST having a value for the honeypot
		$_POST['hp_email'] = 'bot@spam.com';

		// Act
		$filter = new Filter_Honeypot();
		$result = $filter->check( $data );

		// Assert
		$this->assertEquals( 10, $result['spam_score'] );
		$this->assertStringContainsString( 'hp_email', $result['reasons']['honeypot'] );
	}

	public function test_filter_honeypot_skips_if_post_empty() {
		// Arrange
		$data = $this->base_spam_data;
		$data['options']['check_honeypot'] = 1;
		$data['options']['honeypot_input_names'] = array( 'hp_email' );
		$data['mail_tags'] = array( array( 'type' => 'text', 'name' => 'your-name' ) );

		// Ensure POST is empty for the honeypot key
		unset( $_POST['hp_email'] );

		// Act
		$filter = new Filter_Honeypot();
		$result = $filter->check( $data );

		// Assert
		$this->assertEquals( 0, $result['spam_score'] );
	}

	public function test_filter_ip_blocklist_history_respects_threshold() {
		// Arrange
		$data = $this->base_spam_data;
		$data['options']['max_attempts'] = 3;
		$data['remote_ip'] = '100.100.100.101';
		$blocklist = new CF7_Antispam_Blocklist();
		
		// Case 1: Status 1 (Below Threshold)
		$blocklist->cf7a_add_to_blocklist( '100.100.100.101', 1 );
		
		$filter = new Filter_IP_Blocklist_History();
		$result = $filter->check( $data );
		
		$this->assertFalse( $result['is_spam'], 'Status 1 should NOT be spam when max is 3' );
		$this->assertEquals( 0, $result['spam_score'] );

		// Case 2: Status 2 (Below Threshold)
		$blocklist->cf7a_add_to_blocklist( '100.100.100.101', 2 );
		$result = $filter->check( $data );
		$this->assertFalse( $result['is_spam'], 'Status 2 should NOT be spam when max is 3' );

		// Case 3: Status 3 (At Threshold)
		$blocklist->cf7a_add_to_blocklist( '100.100.100.101', 3 );
		$result = $filter->check( $data );
		$this->assertTrue( $result['is_spam'], 'Status 3 SHOULD be spam when max is 3' );
		$this->assertEquals( 3, $result['reasons']['blocklisted'] );

		// Case 4: Status 5 (Above Threshold)
		$blocklist->cf7a_add_to_blocklist( '100.100.100.101', 5 );
		$result = $filter->check( $data );
		$this->assertTrue( $result['is_spam'], 'Status 5 SHOULD be spam when max is 3' );

		// Clean up
		CF7_Antispam_Blocklist::cf7a_unban_by_ip( '100.100.100.101' );
	}

	public function test_filter_ip_blocklist_history_does_not_affect_other_ips() {
		// Arrange
		$blocklist = new CF7_Antispam_Blocklist();
		$filter = new Filter_IP_Blocklist_History();

		// Ban IP 100.100.100.101 with status 5 (above max_attempts 3)
		$data = $this->base_spam_data;
		$data['options']['max_attempts'] = 3;
		$blocklist->cf7a_add_to_blocklist( '100.100.100.101', 5 );

		// Act: Check 100.100.100.101 (should be banned)
		$data['remote_ip'] = '100.100.100.101';
		$result_banned = $filter->check( $data );

		// Act: Check 100.100.100.102 (should NOT be banned)
		$data['remote_ip'] = '100.100.100.102';
		$result_not_banned = $filter->check( $data );

		// Clean up
		CF7_Antispam_Blocklist::cf7a_unban_by_ip( '100.100.100.101' );

		// Assert
		$this->assertTrue( $result_banned['is_spam'], 'IP 100.100.100.101 should be banned' );
		$this->assertFalse( $result_not_banned['is_spam'], 'IP 100.100.100.102 should NOT be banned' );
		$this->assertEquals( 0, $result_not_banned['spam_score'], 'Spam score for 100.100.100.102 should be 0' );
	}
}
