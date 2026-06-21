<?php

namespace PhpUnit\Tests;

use CF7_AntiSpam\Core\CF7_AntiSpam;
use CF7_AntiSpam\Core\CF7_Antispam_Blocklist;
use CF7_AntiSpam\Core\CF7_AntiSpam_Rest_Api;
use WP_UnitTestCase;

class CF7_AntiSpam_Rest_ApiTest extends WP_UnitTestCase {

	private $rest_api;
	private $blocklist;
	private $tmp_csv_file;

	public function setUp(): void {
		parent::setUp();
		$this->rest_api  = new CF7_AntiSpam_Rest_Api();
		$this->blocklist = new CF7_Antispam_Blocklist();
		$this->tmp_csv_file = wp_tempnam( 'cf7a_test_import.csv' );
		
		// Ensure fresh options and blocklist
		\CF7_AntiSpam\Engine\CF7_AntiSpam_Activator::install();
		$options = CF7_AntiSpam::get_options();
		if ( is_array( $options ) ) {
			$options['bad_ip_list'] = array();
			CF7_AntiSpam::update_plugin_options( $options );
		}
		$this->blocklist->cf7a_clean_blocklist();
	}

	public function tearDown(): void {
		parent::tearDown();
		if ( file_exists( $this->tmp_csv_file ) ) {
			@unlink( $this->tmp_csv_file );
		}
	}

	private function create_csv( $lines ) {
		$handle = fopen( $this->tmp_csv_file, 'w' );
		foreach ( $lines as $line ) {
			fputcsv( $handle, $line );
		}
		fclose( $handle );
	}

	public function test_cf7a_parse_import_csv() {
		// 1. Prepare initial database state
		$this->blocklist->cf7a_add_to_blocklist( '192.168.1.100', 'banned' );
		
		// Find the ID of the inserted IP
		global $wpdb;
		$id_to_update = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}cf7a_blocklist WHERE ip = '192.168.1.100'" );

		// 2. Create the CSV data
		$csv_data = array(
			array( 'ID', 'IP', 'Status', 'Extra_Field', 'Another_Field' ),
			// Update existing record by ID
			array( $id_to_update, '192.168.1.100', 'investigating', 'drop_me', 'ignored' ),
			// Insert new record without ID
			array( '', '10.0.0.50', 'banned', 'drop', 'drop' ),
			// Permanent ban record (should go to bad_ip_list)
			array( '', '200.200.200.200', 'permanent', 'drop', 'drop' ),
			// Invalid IP (should be skipped)
			array( '', 'invalid_ip', 'banned', '', '' ),
		);
		$this->create_csv( $csv_data );

		// 3. Run the parser
		$count = $this->rest_api->cf7a_parse_import_csv( $this->tmp_csv_file );

		// We expect 3 successes: the update, the insert, and the permanent ban
		$this->assertEquals( 3, $count );

		// 4. Verify the database state
		$updated_record = $this->blocklist->cf7a_blocklist_get_id( $id_to_update );
		$this->assertEquals( 'investigating', $updated_record->status, 'Status should be updated by ID' );

		$new_record = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}cf7a_blocklist WHERE ip = '10.0.0.50'" );
		$this->assertNotNull( $new_record, 'New IP should be inserted' );
		$this->assertEquals( 'banned', $new_record->status, 'New IP should have banned status' );

		// 5. Verify permanent ban
		$permanent_record = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}cf7a_blocklist WHERE ip = '200.200.200.200'" );
		$this->assertNull( $permanent_record, 'Permanent ban should not be in the blocklist table' );

		$options = CF7_AntiSpam::get_options();
		$bad_ips = $options['bad_ip_list'] ?? array();
		$this->assertContains( '200.200.200.200', $bad_ips, 'Permanent ban should be in bad_ip_list options' );
	}
}
