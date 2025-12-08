<?php

namespace CF7_AntiSpam\Engine;

class CF7_AntiSpam_Updater {

	public $hc_version;
	public $current_options;

	/**
	 * Initializes a new instance of the class.
	 *
	 * @param string $hardcoded_version The hardcoded version.
	 * @param mixed  $options The options.
	 */
	public function __construct( $hardcoded_version, $options ) {
		$this->hc_version      = $hardcoded_version;
		$this->current_options = $options;
	}

	/**
	 * Execute any refactoring procedure for plugin updates
	 *
	 * @return boolean true if successful or false if already updated, otherwise false because of error: check your db settings
	 */
	public function may_do_updates() {
		$updated = false;

		/* Check if there are no options already stored, exit immediately in this case */
		if ( empty( $this->current_options ) ) {
			return false;
		}

		/* Check if we need to update from older versions */
		if ( version_compare( $this->hc_version, $this->current_options['cf7a_version'], '>' ) ) {

			/* Update to 0.6.0 if needed */
			if ( version_compare( $this->current_options['cf7a_version'], '0.6.0', '<' ) ) {
				$new_options = $this->update_db_procedure_to_0_6_0();
				if ( ! empty( $new_options ) ) {
					$this->current_options = $new_options;
					$updated               = true;
				}
			}

			/* Update to 0.7.0 if needed */
			if ( version_compare( $this->current_options['cf7a_version'], '0.7.0', '<' ) ) {
				$db_updated = $this->update_db_procedure_to_0_7_0();
				if ( $db_updated ) {
					$this->current_options['cf7a_version'] = $this->hc_version;
					$updated                               = true;
				}
			}

			/* Update the version to current if any updates were made */
			if ( $updated ) {
				$this->current_options['cf7a_version'] = $this->hc_version;
				return update_option( 'cf7a_options', $this->current_options );
			}
		}

		return false;
	}

	/**
	 * Update the db procedure to 0.6.0
	 * Substitute "languages" with "languages_locales"
	 *
	 * @return void|mixed
	 */
	public function update_db_procedure_to_0_6_0() {
		if ( array_key_exists( 'languages', $this->current_options ) ) {
			$this->current_options['cf7a_version']                    = $this->hc_version;
			$this->current_options['languages_locales']['allowed']    = $this->current_options['languages']['allowed'];
			$this->current_options['languages_locales']['disallowed'] = $this->current_options['languages']['disallowed'];

			unset( $this->current_options['languages'] );

			cf7a_log( 'CF7-antispam updated to 0.6.0: languages option migrated to languages_locales', 1 );

			return $this->current_options;
		}

		return false;
	}
	/**
	 * Update the database schema to 0.7.0
	 * Add 'modified' and 'created' columns to blocklist table
	 *
	 * @return boolean
	 */
	public function update_db_procedure_to_0_7_0() {
		global $wpdb;

		$blacklist_table = $wpdb->prefix . 'cf7a_blacklist';
		$updated = false;

		// Check if the table exists first
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$has_blacklist_table = $wpdb->get_var( $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$wpdb->esc_like( $blacklist_table )
		) );
		if ( $has_blacklist_table !== $blacklist_table ) {
			cf7a_log( 'CF7-antispam update to 0.7.0: blocklist table does not exist, skipping schema update', 2 );
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Check if the 'modified' column exists, if not add it
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$has_blacklist_modified_col = $wpdb->get_var( $wpdb->prepare(
			"SHOW COLUMNS FROM %i LIKE %s",
			$blacklist_table,
			'modified'
		) );
		if ( ! $has_blacklist_modified_col ) {
			// Note: $wpdb->prepare cannot be used with ALTER TABLE statements.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
			$sql = "ALTER TABLE `{$blacklist_table}` ADD `modified` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$result = $wpdb->query( $sql );

			if ( $result !== false ) {
				cf7a_log( 'CF7-antispam updated to 0.7.0: added modified column to blocklist table', 2 );
				$updated = true;
			} else {
				cf7a_log( 'CF7-antispam update to 0.7.0: failed to add modified column to blocklist table', 1 );
			}
		}

		// Check if the 'created' column exists, if not add it
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$has_blacklist_created_col =  $wpdb->get_var( $wpdb->prepare(
			"SHOW COLUMNS FROM %i LIKE %s",
			$blacklist_table,
			'created'
		) );
		if ( ! $has_blacklist_created_col ) {
			// Note: $wpdb->prepare cannot be used with ALTER TABLE statements.
			$sql = "ALTER TABLE `{$blacklist_table}` ADD `created` datetime DEFAULT CURRENT_TIMESTAMP;";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->query( $sql );

			if ( $result !== false ) {
				cf7a_log( 'CF7-antispam updated to 0.7.0: added created column to blocklist table', 2 );
				$updated = true;
			} else {
				cf7a_log( 'CF7-antispam update to 0.7.0: failed to add created column to blocklist table', 1 );
			}

			// if flamingo is enabled, try to get the created date from the flamingo post meta
			if ( class_exists( 'Flamingo' ) ) {
				// get all flamingo posts
				// TODO: get the post by ip addr and get the related item of the backlist table, then copy the flamingo dates to the item found
			}
		}

		if ( $updated ) {
			cf7a_log( 'CF7-antispam database schema updated to 0.7.0', 2 );
		}

		return $updated;
	}
}
