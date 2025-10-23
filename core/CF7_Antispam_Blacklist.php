<?php

namespace CF7_AntiSpam\Core;

/**
 * Blacklist management functions
 *
 * @since      0.7.0
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 * @author     Codekraft Studio <info@codekraft.it>
 */

/**
 * It's a class that handles blacklist management
 */
class CF7_Antispam_Blacklist {

	/**
	 * CF7_Antispam_Blacklist constructor.
	 */
	public function __construct() {
	}

	/**
	 * Get all blacklist data from database.
	 *
	 * @since    0.7.0
	 * @return   array Array of blacklist entries
	 */
	public function cf7a_get_blacklist_data() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'cf7a_blacklist';

		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			return array();
		}

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT id, ip, status, meta, modified, created
			FROM %i
			ORDER BY created DESC", $table_name ) );

		return $results ?: array();
	}

	/**
	 * Get a single blacklist entry by ID.
	 *
	 * @since    0.7.0
	 * @param    int $id The blacklist entry ID.
	 * @return   object|null The blacklist entry or null if not found
	 */
	public function cf7a_blacklist_get_id( $id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'cf7a_blacklist';

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, ip, status, meta, modified, created
				FROM %i
				WHERE id = %d",
				$table_name,
				$id
			)
		);

		return $result;
	}

	/**
	 * Unban an IP by ID.
	 *
	 * @since    0.7.0
	 * @param    int $id The blacklist entry ID to unban.
	 * @return   bool True on success, false on failure
	 */
	public function cf7a_unban_by_id( $id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'cf7a_blacklist';

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
	 * @param    int $id The blacklist entry ID.
	 * @return   array Array with 'success' and 'message' keys
	 */
	public function cf7a_ban_forever( $id ) {
		$ban_ip = $this->cf7a_blacklist_get_id( $id );
		$plugin_options = CF7_AntiSpam::get_options();

		if ( $ban_ip && ! empty( $plugin_options ) ) {
			$current_bad_ips = $plugin_options['bad_ip_list'] ?? array();

			// Add the IP to the permanent banlist
			if ( CF7_AntiSpam::update_plugin_option( 'bad_ip_list', array_merge( $current_bad_ips, array( $ban_ip->ip ) ) ) ) {
				// Remove from temporary blacklist
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
	 * Export blacklist data as CSV.
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

				$csv .= $id . ',' . $ip . ',' . $status . ',' . $meta . ',' . $modified . ',' . $created . "\n";
			}
		} else {
			$csv .= "No blacklisted IPs found\n";
		}

		$filename = 'cf7-antispam-blacklist-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv';

		return array(
			'csv'      => $csv,
			'filename' => $filename,
		);
	}

	/**
	 * Clean/reset the entire blacklist.
	 *
	 * @since    0.7.0
	 * @return   bool True on success, false on failure
	 */
	public function cf7a_clean_blacklist() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'cf7a_blacklist';

		// Truncate the table
		$result = $wpdb->query( "TRUNCATE TABLE {$table_name}" );

		return $result !== false;
	}

	/**
	 * Add an IP to the blacklist.
	 *
	 * @since    0.7.0
	 * @param    string $ip The IP address to blacklist.
	 * @param    string $status The status of the ban.
	 * @param    mixed  $meta Additional metadata.
	 * @return   bool True on success, false on failure
	 */
	public function cf7a_add_to_blacklist( $ip, $status = 'banned', $meta = null ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'cf7a_blacklist';

		// Check if IP already exists
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE ip = %s",
				$table_name,
				$ip
			)
		);

		if ( $exists > 0 ) {
			// Update existing entry
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
	 * Check if an IP is in the blacklist.
	 *
	 * @since    0.7.0
	 * @param    string $ip The IP address to check.
	 * @return   bool True if blacklisted, false otherwise
	 */
	public function cf7a_is_blacklisted( $ip ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'cf7a_blacklist';

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
	 * Get blacklist statistics.
	 *
	 * @since    0.7.0
	 * @return   array Array with statistics
	 */
	public function cf7a_get_blacklist_stats() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'cf7a_blacklist';

		$total = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i", $table_name ) );

		$today = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE DATE(created) = %s",
				$table_name,
				current_time( 'Y-m-d' )
			)
		);

		$this_week = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE created >= %s",
				$table_name,
				date( 'Y-m-d', strtotime( '-7 days' ) )
			)
		);

		return array(
			'total'      => intval( $total ),
			'today'      => intval( $today ),
			'this_week'  => intval( $this_week ),
		);
	}
}
