<?php

namespace CF7_AntiSpam\Core;

/**
 * Geoip related functions.
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 * @author     Codekraft Studio <info@codekraft.it>
 */
use PharData;
use Exception;
use GeoIp2\Database\Reader;
use CF7_AntiSpam\Admin\CF7_AntiSpam_Admin_Tools;

/**
 * It checks if the CF7ANTISPAM_GEOIP_KEY constant is set, and if it is, it uses that as the license key. Otherwise, it
 * checks if the geoip_dbkey option is set, and if it is, it uses that as the license key. Otherwise, it sets the license key to false
 */
class CF7_Antispam_Geoip {

	/**
	 * The GeoIP2 db license key
	 *
	 * @since    0.3.1
	 * @access   private
	 * @var      string    $license    license key
	 */
	private $license;

	/**
	 * The options of this plugin.
	 *
	 * @since    0.3.1
	 * @access   private
	 * @var      array    $options    options of this plugin.
	 */
	private $options;

	/**
	 * The next update of the geo-ip database
	 *
	 * @since    0.3.1
	 * @access   private
	 * @var      string    $next_update    the date of the next update in epoch.
	 */
	public $next_update;

	/**
	 * The GeoIP2 reader
	 *
	 * @since    0.3.1
	 * @access   private
	 * @var      GeoIp2\Database\Reader|false    $reader    the GeoIP class
	 */
	public $reader = false;

	/**
	 * CF7_AntiSpam_Filters constructor.
	 *
	 * @since    0.3.1
	 * @return GeoIp2\Database\Reader|false
	 */
	public function __construct() {

		/* the plugin options */
		$this->options = CF7_AntiSpam::get_options();

		/* the GeoIP2 license key */
		$this->license = $this->cf7a_geoip_set_license();

		$this->next_update = get_option( 'cf7a_geodb_update', 0 );

		return $this->cf7a_geoip_get_reader();
	}

	/**
	 * If the CF7ANTISPAM_GEOIP_KEY constant is set, use that. Otherwise, if the geoip_dbkey option is set, use that.
	 * Otherwise, return false
	 *
	 * @return string the value of the CF7ANTISPAM_GEOIP_KEY constant if it is set, or the value of the geoip_dbkey option if it is
	 * set, or false if neither is set.
	 */
	private function cf7a_geoip_set_license() {
		if ( CF7ANTISPAM_GEOIP_KEY ) {
			return CF7ANTISPAM_GEOIP_KEY;
		} elseif ( ! empty( $this->options['geoip_dbkey'] ) ) {
			return $this->options['geoip_dbkey'];
		}
		return false;
	}

	/**
	 * If the license is valid and the database has been updated, then the plugin can enable GeoIP
	 *
	 * @return bool true geo-ip can be enabled
	 */
	public function cf7a_geoip_has_license() {
		return ! empty( $this->license );
	}

	/**
	 *
	 * If the zlib or phar extensions are loaded, the geo-ip can be enabled, return true and otherwise return false
	 *
	 * @return bool true geo-ip can be enabled
	 */
	public function cf7a_geoip_can_be_enabled() {
		if ( extension_loaded( 'zlib' ) || extension_loaded( 'phar' ) ) {
			return true;
		}
		return false;
	}


	/**
	 * If the zlib and phar php modules are enabled, then if the geoip database is enabled, then if the geoip database is
	 * initialized, then return the geoip database, else download the geoip database
	 *
	 * @return GeoIp2\Database\Reader|false
	 */
	private function cf7a_geoip_get_reader() {
		if ( $this->next_update ) {
			$this->reader = $this->cf7a_geo_init();
		}

		return $this->reader;
	}

	/**
	 * It creates a new Reader object, which should be reused across lookups.
	 *
	 * @return GeoIp2\Database\Reader|false The Reader object is being returned.
	 */
	private function cf7a_geo_init() {
		// This creates the Reader object, which should be reused across lookups.
		try {
			return new Reader( self::cf7a_get_upload_dir() . '/GeoLite2-Country.mmdb' );
		} catch ( Exception $exception ) {
			cf7a_log( 'GeoIP Database init error, unable to read the stored file ', 1 );
			cf7a_log( $exception->getMessage(), 2 );
			$this->next_update = false;
			delete_option( 'cf7a_geodb_update' );
			return false;
		}
	}

	/**
	 * If we have a license key, and we need to update the database, then download the database
	 *
	 * @return bool
	 */
	public function cf7a_geo_maybe_download() {
		/* if we have the license key */
		if ( $this->cf7a_geoip_has_license() && $this->cf7a_geoip_can_be_enabled() ) {
			/*if we need to update the database */
			if ( $this->cf7a_maybe_download_geoip_db() ) {
				$this->cf7a_geoip_download_database();
			}
			return true;
		}
		return false;
	}

	/**
	 * If the last time the database was updated is less than one month ago, then return true
	 *
	 * @return bool - true if the database needs to be uploaded
	 */
	public function cf7a_maybe_download_geoip_db() {
		return ! $this->next_update || strtotime( 'now' ) > $this->next_update;
	}

	/**
	 * It returns the path to the upload directory, with a trailing slash
	 *
	 * @return string The upload directory for the plugin.
	 */
	private static function cf7a_get_upload_dir() {
		$upload_dir = wp_upload_dir();

		return trailingslashit( $upload_dir['basedir'] . '/' . CF7ANTISPAM_NAME );
	}

	/**
	 * It creates a directory, creates a .htaccess file in that directory, and writes "Deny from all" to the .htaccess file
	 *
	 * @param string       $plugin_upload_dir The directory you want to create.
	 * @param string|false $htaccess_content - if passed will create also a htaccess with the given content.
	 *
	 * @return bool the value of the variable $plugin_upload_dir.
	 */
	private function cf7a_create_upload_dir( $plugin_upload_dir, $htaccess_content = false ) {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		/* Creates the upload/cf7-antispam directory */
		wp_mkdir_p( $plugin_upload_dir );

		if ( $htaccess_content ) {
			/* and the .htaccess file */
			$htaccess_filename = $plugin_upload_dir . '.htaccess';
			try {
				if ( ! $wp_filesystem->exists( $htaccess_filename ) ) {
					$ht_content = $htaccess_content;
					$wp_filesystem->put_contents( $htaccess_filename, $ht_content, 600 );
				}
			} catch ( Exception $e ) {
				cf7a_log( 'Unable to create the cf7-antispam folder' );
				cf7a_log( $e );

				return false;
			}
		}
		return true;
	}

	/**
	 * It downloads a file from a URL, decompresses it, and copies the decompressed file to a new location
	 *
	 * @return bool true when the database has been downloaded
	 */
	private function cf7a_geoip_download_database() {
		cf7a_log( 'GeoIP DB download start', 1 );

		$key = sanitize_text_field( $this->license );
		if ( empty( $key ) ) {
			return false;
		}

		/* check if the plugin upload directory exist, otherwise create it */
		$plugin_upload_dir = $this::cf7a_get_upload_dir();
		if ( ! is_dir( $plugin_upload_dir ) && $this->cf7a_create_upload_dir( $plugin_upload_dir, 'Require local' ) ) {
			cf7a_log( 'Geo-ip download folder created with success', 1 );
		}

		/* Then download the geoip database */
		$database_type = 'GeoLite2-Country';
		$filename      = $database_type;
		$ext           = '.tar.gz';

		/* The maxmind directory link */
		$download_url = esc_url_raw(
			sprintf(
				'https://download.maxmind.com/app/geoip_download?edition_id=%s&license_key=%s&suffix=tar.gz',
				$database_type,
				$key
			)
		);

		/* Then final file name */
		$destination_file_uri = $plugin_upload_dir . sanitize_file_name( $filename . '.mmdb' );

		/* wp_filesystem */
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		/* Download */
		try {
			$content = $wp_filesystem->get_contents(
				$download_url
			);
			cf7a_log( "Unable to download the geo-ip database, please check that the key provided is correct! destination path: $destination_file_uri - url: $download_url", 1 );
		} catch ( Exception $exception ) {
			$message = __( 'Unable to download the geo-ip database, please check that the key provided is correct! ', 'cf7-antispam' );
			CF7_AntiSpam_Admin_Tools::cf7a_push_notice( $message );
			cf7a_log( $message, 1 );
			cf7a_log( $exception->getMessage(), 2 );
			return false;
		}

		/* Copy */
		try {
			$wp_filesystem->put_contents(
				$destination_file_uri . $ext,
				$content,
				770
			);
		} catch ( Exception $exception ) {
			$message = __( 'Unable to download the geo-ip database, please check that the key provided is correct! ', 'cf7-antispam' );
			CF7_AntiSpam_Admin_Tools::cf7a_push_notice( $message );
			cf7a_log( $message, 1 );
			cf7a_log( $exception->getMessage(), 2 );
			return false;
		}

		/* decompress */
		try {
			$p         = new PharData( $destination_file_uri . $ext );
			$temp_file = $p->current()->getFilename();

			$temp_database_file = $temp_file . "/$database_type.mmdb";

			$p->extractTo(
				$plugin_upload_dir,
				$temp_database_file,
				true
			);
		} catch ( Exception $exception ) {
			$message = esc_html__( 'GEO-IP Database file decompression failed', 'cf7-antispam' );
			CF7_AntiSpam_Admin_Tools::cf7a_push_notice( $message );
			CF7_AntiSpam_Admin_Tools::cf7a_push_notice( $exception->getMessage() );
			cf7a_log( $message, 1 );
			cf7a_log( $exception->getMessage(), 2 );
			return false;
		}

		try {
			$wp_filesystem->copy( $plugin_upload_dir . $temp_database_file, $destination_file_uri );

			/* remove original compressed file */
			$wp_filesystem->delete( $destination_file_uri . $ext );

			/* remove unpacked inside the folder */
			$wp_filesystem->delete( $plugin_upload_dir . $temp_database_file );

			/* remove the extracted directory */
			$wp_filesystem->delete( $plugin_upload_dir . $temp_file );
		} catch ( Exception $exception ) {
			$message = __( 'GEO-IP decompressed database copy failed', 'cf7-antispam' );
			CF7_AntiSpam_Admin_Tools::cf7a_push_notice( $message );
			cf7a_log( $message, 1 );
			cf7a_log( $exception->getMessage(), 2 );
			return false;
		}

		/* Update the geoip next update metadata */
		$update_date = strtotime( '+1 month' );
		if ( ! $this->next_update ) {
			add_option( 'cf7a_geodb_update', $update_date );
		} else {
			update_option( 'cf7a_geodb_update', $update_date );
		}

		cf7a_log( 'GeoIP DB downloaded with success ', 1 );

		$this->next_update = $update_date;

		return true;
	}

	/**
	 * It downloads the GeoIP database from MaxMind and saves it to the plugin's directory
	 *
	 * @param bool $now If true, the database will be downloaded immediately.
	 */
	public function cf7a_geoip_schedule_update( $now = false ) {
		if ( $now ) {
			$this->cf7a_geoip_download_database();
		}

		wp_clear_scheduled_hook( 'cf7a_geoip_update_db' );

		$next_event = strtotime( '+1 month' );

		wp_schedule_single_event( $next_event, 'cf7a_geoip_update_db', array( 'Geoip_update_db' ) );
	}

	/**
	 * It takes an IP address as a parameter, and returns an array of data about the IP address
	 *
	 * @param string $ip The IP address to check.
	 */
	public function cf7a_geoip_check_ip( $ip ) {
		try {
			if ( $this->reader ) {
				$ip_data = $this->reader->country( $ip );

				return array(
					'continent'      => $ip_data->continent->code,
					'continent_name' => $ip_data->continent->name,
					'country'        => $ip_data->country->isoCode,
					'country_name'   => $ip_data->country->name,
				);
			} else {
				return array(
					'error' => 'geoip not initialized',
				);
			}
		} catch ( Exception $e ) {
			return array(
				'error' => $e->getMessage(),
			);
		}
	}
}
