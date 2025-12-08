<?php

namespace CF7_AntiSpam\Core;

/**
 * Blocklist management functions
 *
 * @since      0.7.0
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 * @author     Codekraft Studio <info@codekraft.it>
 */

/**
 * It's a class that handles blocklist management
 */
class CF7_Antispam_Blacklist {

	/**
	 * CF7_Antispam_Blacklist constructor.
	 */
	public function __construct() {
	}

	/**
	 * It takes an IP address as a parameter, validates it, and then returns the row from the database that matches that IP
	 * address
	 *
	 * @param string $ip - The IP address to check.
	 *
	 * @return array|object|null - the row from the database that matches the IP address.
	 */
	public static function cf7a_blacklist_get_ip( $ip ) {
		$ip = filter_var( $ip, FILTER_VALIDATE_IP );
		if ( $ip ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %i WHERE ip = %s", $wpdb->prefix . 'cf7a_blacklist', $ip ) );
			if ( $r ) {
				return $r;
			}
		}

		return null;
	}

	/**
	 * It adds an IP address to the blocklist.
	 *
	 * @param string $ip The IP address to ban.
	 * @param array $reason The reason why the IP is being banned.
	 * @param int $spam_score This is the number of points that will be added to the IP's spam score.
	 *
	 * @return bool true if the given id was banned
	 */
	public static function cf7a_ban_by_ip( string $ip, array $reason = array(), $spam_score = 1 ): bool {
		$ip = filter_var( $ip, FILTER_VALIDATE_IP );

		if ( $ip ) {
			global $wpdb;

			$ip_row = CF7_Antispam_Blacklist::cf7a_blacklist_get_ip( $ip );

			if ( $ip_row ) {
				// if the ip is in the blocklist, update the status
				$status = isset( $ip_row->status ) ? floatval( $ip_row->status ) + floatval( $spam_score ) : 1;

			} else {
				// if the ip is not in the blocklist, add it and initialize the status
				$status = floatval( $spam_score );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$r = $wpdb->replace( $wpdb->prefix . 'cf7a_blacklist', array(
					'ip'     => $ip,
					'status' => $status,
					'meta'   => serialize( array(
							'reason' => $reason,
							'meta'   => null,
						) ),
				), array( '%s', '%d', '%s' ) );

			if ( $r > - 1 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * It deletes the IP address from the database
	 *
	 * @param string $ip The IP address to unban.
	 *
	 * @return int|false The number of rows deleted.
	 */
	public static function cf7a_unban_by_ip( $ip ) {
		$ip = filter_var( $ip, FILTER_VALIDATE_IP );

		if ( $ip ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$r = $wpdb->delete( $wpdb->prefix . 'cf7a_blacklist', array(
					'ip' => $ip,
				), array(
					'%s',
				) );

			return ! is_wp_error( $r ) ? $r : $wpdb->last_error;
		}

		return false;
	}

	/**
	 * Get all blocklist data from database.
	 *
	 * @since    0.7.0
	 * @return   array Array of blocklist entries
	 */
	public function cf7a_get_blacklist_data() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'cf7a_blacklist';

		// Check if table exists
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %i', $table_name ) ) !== $table_name ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i", $table_name ) );

		return $results ?: array();
	}

	/**
	 * Get a single blocklist entry by ID.
	 *
	 * @since    0.7.0
	 * @param    int $id The blocklist entry ID.
	 * @return   object|null The blocklist entry or null if not found
	 */
	public function cf7a_blacklist_get_id( int $id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'cf7a_blacklist';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, ip, status, meta, modified, created
				FROM %i
				WHERE id = %d",
				$table_name,
				$id
			)
		);
	}

	/**
	 * Unban an IP by ID.
	 *
	 * @since    0.7.0
	 * @param    int $id The blocklist entry ID to unban.
	 * @return   bool True on success, false on failure
	 */
	public function cf7a_unban_by_id( $id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'cf7a_blacklist';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Ban an IP forever by adding it to the permanent ban list.
	 *
	 * @since    0.7.0
	 * @param    int $id The blocklist entry ID.
	 * @return   array Array with 'success' and 'message' keys
	 */
	public function cf7a_ban_forever( $id ) {
		$ban_ip = $this->cf7a_blacklist_get_id( $id );
		$plugin_options = CF7_AntiSpam::get_options();

		if ( $ban_ip && ! empty( $plugin_options ) ) {
			$current_bad_ips = $plugin_options['bad_ip_list'] ?? array();

			// Add the IP to the permanent banlist
			if ( CF7_AntiSpam::update_plugin_option( 'bad_ip_list', array_merge( $current_bad_ips, array( $ban_ip->ip ) ) ) ) {
				$this->cf7a_unban_by_id( $id );
			}

			return array(
				'success' => true,
				'message' => sprintf(
				/* translators: the %1$s is the user id and %2$s is the ip address. */
					__( 'Ban forever id %1$s (ip %2$s) successful', 'cf7-antispam' ),
					$id,
					! empty( $ban_ip->ip ) ? $ban_ip->ip : 'not available'
				),
			);
		} else {
			return array(
				'success' => false,
				'message' => sprintf(
				/* translators: the %1$s is the user id and %2$s is the ip address. */
					__( 'Error: unable to ban forever id %1$s (ip %2$s)', 'cf7-antispam' ),
					$id,
					! empty( $ban_ip->ip ) ? $ban_ip->ip : 'not available'
				),
			);
		}
	}

	/**
	 * Export blocklist data as CSV.
	 *
	 * @since    0.7.0
	 * @return   array Array with 'csv' content and 'filename'
	 */
	public function cf7a_export_blacklist() {
		$blacklist = $this->cf7a_get_blacklist_data();

		$csv = "ID,IP,Status,Meta,Modified,Created\n";

		if ( ! empty( $blacklist ) ) {
			foreach ( $blacklist as $row ) {
				$id     = $row->id;
				$ip     = '"' . str_replace( '"', '""', $row->ip ) . '"';
				$status = $row->status ?? '';

				$meta = '';
				if ( isset( $row->meta ) ) {
					if ( is_array( $row->meta ) && ! empty( $row->meta ) ) {
						$meta = '"' . str_replace( '"', '""', json_encode( $row->meta, JSON_UNESCAPED_UNICODE ) ) . '"';
					} elseif ( ! empty( $row->meta ) ) {
						$meta = '"' . str_replace( '"', '""', $row->meta ) . '"';
					}
				}

				$modified = isset( $row->modified ) ? '"' . str_replace( '"', '""', $row->modified ) . '"' : '""';
				$created  = isset( $row->created ) ? '"' . str_replace( '"', '""', $row->created ) . '"' : '""';

				$csv .= sprintf( "%s,%s,%s,%s,%s,%s\n", $id, $ip, $status, $meta, $modified, $created );
			}
		} else {
			$csv .= "No blocklisted IPs found\n";
		}

		$filename = 'cf7-antispam-blocklist-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv';

		return array(
			'filetype' => 'csv',
			'data'      => $csv,
			'filename' => $filename,
		);
	}

	/**
	 * Clean/reset the entire blocklist.
	 *
	 * @since    0.7.0
	 * @return   bool True on success, false on failure
	 */
	public function cf7a_clean_blacklist() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'cf7a_blacklist';

		// Truncate the table
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->query( "TRUNCATE TABLE {$table_name}" );

		return $result !== false;
	}

	/**
	 * Add an IP to the blocklist.
	 *
	 * @since    0.7.0
	 * @param    string $ip The IP address to blocklist.
	 * @param    string $status The status of the ban.
	 * @param    mixed  $meta Additional metadata.
	 * @return   bool True on success, false on failure
	 */
	public function cf7a_add_to_blacklist( $ip, $status = 'banned', $meta = null ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'cf7a_blacklist';

		// Check if IP already exists
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE ip = %s",
				$table_name,
				$ip
			)
		);

		if ( $exists > 0 ) {
			// Update existing entry
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->update(
				$table_name,
				array(
					'status'   => $status,
					'meta'     => is_array( $meta ) ? json_encode( $meta ) : $meta,
					'modified' => current_time( 'mysql' ),
				),
				array( 'ip' => $ip ),
				array( '%s', '%s', '%s' ),
				array( '%s' )
			);
		} else {
			// Insert new entry
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->insert(
				$table_name,
				array(
					'ip'       => $ip,
					'status'   => $status,
					'meta'     => is_array( $meta ) ? json_encode( $meta ) : $meta,
					'created'  => current_time( 'mysql' ),
					'modified' => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%s', '%s' )
			);
		}

		return $result !== false;
	}

	/**
	 * Check if an IP is in the blocklist.
	 *
	 * @since    0.7.0
	 * @param    string $ip The IP address to check.
	 * @return   bool True if blocklisted, false otherwise
	 */
	public function cf7a_is_blacklisted( $ip ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'cf7a_blacklist';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE ip = %s",
				$table_name,
				$ip
			)
		);

		return $result > 0;
	}

	/**
	 * Get blocklist statistics.
	 *
	 * @since    0.7.0
	 * @return   array Array with statistics
	 */
	public function cf7a_get_blacklist_stats() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'cf7a_blacklist';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i", $table_name ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$today = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE DATE(created) = %s",
				$table_name,
				current_time( 'Y-m-d' )
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this_week = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE created >= %s",
				$table_name,
				gmdate( 'Y-m-d', strtotime( '-7 days' ) )
			)
		);

		return array(
			'total'      => intval( $total ),
			'today'      => intval( $today ),
			'this_week'  => intval( $this_week ),
		);
	}

	/**
	 * It updates the status of all the users in the blocklist table by subtracting 1 from the status column.
	 *
	 * Then it deletes all the users whose status is 0.
	 * The status column is the number of days the user is banned for.
	 * So if the user is banned for 3 days, the status column will be 3. After the first day, the status column will be 2. After the second day, the status column will be 1. After the third day, the status column will be 0.
	 * When the status column is 0, the user is unbanned.
	 *
	 * The function returns true if the user is unbanned.
	 *
	 * @return true.
	 */
	public function cf7a_cron_unban() {
		global $wpdb;

		/* We remove 1 from the status column */
		$status_decrement = 1;

		/* Below 0 is not anymore a valid status for a blocklist entry, so we can remove it */
		$lower_bound = 0;

		$blacklist_table = $wpdb->prefix . 'cf7a_blacklist';

		/* removes a status count at each balcklisted ip */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE %i SET `status` = `status` - %d", $blacklist_table, $status_decrement ) );
		cf7a_log( "Status updated for blocklisted (score -1) - $updated users", 1 );

		/* when the line has 0 in status, we can remove it from the blocklist */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated_deletion = $wpdb->delete(
			$blacklist_table,
			array( 'status' => $lower_bound ),
			array( '%d' )
		);
		cf7a_log( "Removed {$updated_deletion} users from blocklist", 1 );

		return true;
	}
}
