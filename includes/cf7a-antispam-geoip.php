<?php

use GeoIp2\Database\Reader;


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
	 * The GeoIP2 reader
	 *
	 * @since    0.3.1
	 * @access   private
	 * @var      GeoIp2\Database\Reader|false    $geo    the GeoIP class
	 */
	private $geo;

	/**
	 * The options of this plugin.
	 *
	 * @since    0.3.1
	 * @access   private
	 * @var      array    $options    options of this plugin.
	 */
	private $options;

	/**
	 * CF7_AntiSpam_filters constructor.
	 *
	 * @since    0.3.1
	 */
	public function __construct() {

		// zlib and phar php modules are mandatory to unpack database.
		if ( ! extension_loaded( 'zlib' ) || ! extension_loaded( 'phar' ) ) {
			CF7_AntiSpam_Admin_Tools::cf7a_push_notice( esc_html__( 'to activate geo-ip you must necessarily have the zlib and phar php modules enabled' ) );
			return false;
		}

		/* the plugin options */
		$this->options = CF7_AntiSpam::get_options();

		$this->next_update = get_option( 'cf7a_geodb_update', 0 );

		/* the GeoIP2 license key */
		$this->license = $this->cf7a_geoip_set_license();

		/* init the geocoder */
		if ( $this->cf7a_can_enable_geoip() && $this->next_update ) {

			if ( $this->cf7a_maybe_download_geoip_db() ) {
				$this->cf7a_geoip_download_database();
			}

			$this->geo = $this->cf7a_geo_init();

			/* if at this point there is still no this->geo, all has failed, and I'm unable to access to geo-ip database file, disabling */
			if ( ! $this->geo ) {
				delete_option( 'cf7a_geodb_update' );

				CF7_AntiSpam_Admin_Tools::cf7a_push_notice( __( 'unable to access geoip database file', 'cf7-antispam' ) );
			}
		} else {
			delete_option( 'cf7a_geodb_update' );
		}

		add_action( 'cf7a_geoip_update_db', array( $this, 'cf7a_geoip_download_database' ) );
	}

	/**
	 * If the CF7ANTISPAM_GEOIP_KEY constant is set, use that. Otherwise, if the geoip_dbkey option is set, use that.
	 * Otherwise, return false
	 *
	 * @return bool the value of the CF7ANTISPAM_GEOIP_KEY constant if it is set, or the value of the geoip_dbkey option if it is
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
	 * It creates a new Reader object, which should be reused across lookups.
	 *
	 * @return GeoIp2\Database\Reader|false The Reader object is being returned.
	 */
	private function cf7a_geo_init() {
		// This creates the Reader object, which should be reused across lookups.
		try {
			return new Reader( $this->cf7a_get_upload_dir() . '/GeoLite2-Country.mmdb' );
		} catch ( Exception $e ) {
			cf7a_log( 'GeoIP Database init error, unable to read file' );
			delete_option( 'cf7a_geodb_update' );
			return false;
		}
	}

	/**
	 * If the license is valid and the database has been updated, then the plugin can enable GeoIP
	 *
	 * @return bool true geo-ip can be enabled
	 */
	public function cf7a_can_enable_geoip() {
		return ! empty( $this->license );
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
	private function cf7a_get_upload_dir() {

		$upload_dir = wp_upload_dir();

		return trailingslashit( $upload_dir['basedir'] . '/' . CF7ANTISPAM_NAME );

	}

	/**
	 * It creates a directory, creates a .htaccess file in that directory, and writes "Deny from all" to the .htaccess file
	 *
	 * @param string $plugin_upload_dir The directory you want to create.
	 * @param bool   $htaccess_content - if passed will create also a htaccess with the given content.
	 *
	 * @return bool the value of the variable $plugin_upload_dir.
	 */
	private function cf7a_create_upload_dir( $plugin_upload_dir, $htaccess_content = false ) {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		/* Creates a directory */
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
	public function cf7a_geoip_download_database() {

		cf7a_log( 'GeoIP DB download start', 1 );

		$upload_dir        = wp_upload_dir();
		$plugin_upload_dir = $this->cf7a_get_upload_dir();

		$database_type = 'GeoLite2-Country';
		$filename      = $database_type;
		$ext           = '.tar.gz';
		$key           = rawurlencode( $this->license );

		$download_url = sprintf(
			'https://download.maxmind.com/app/geoip_download?edition_id=%s&license_key=%s&suffix=tar.gz',
			$database_type,
			$key
		);

		$destination_file_uri = $plugin_upload_dir . sanitize_file_name( $filename . '.mmdb' );

		/* check if the plugin upload directory exist, otherwise create it */
		if ( ! is_dir( $plugin_upload_dir ) && $this->cf7a_create_upload_dir( $plugin_upload_dir, 'Require local' ) ) {
			cf7a_log( 'Geo-ip download folder created with success', 1 );
		}

		$file_content = '';

		/* Download */
		if ( ( $stream = fopen( $download_url, 'r' ) ) !== false ) {
			while ( ! feof( $stream ) ) {
				$file_content .= fgets( $stream );
			}
			fclose( $stream );
		} else {

			CF7_AntiSpam_Admin_Tools::cf7a_push_notice( __( 'Unable to download the file with the geo-ip database, please check that the key provided is correct!', 'cf7-antispam' ) );
			cf7a_log( "Unable to download the file with the geo-ip database, please check that the key provided is correct! key: $key - url: $download_url" );
			return false;
		}

		if ( ! empty( $file_content ) ) {

			if ( file_exists( $destination_file_uri . $ext ) ) {
				wp_delete_file( $destination_file_uri . $ext );
			}

			if ( ! file_put_contents( $destination_file_uri . $ext, $file_content ) ) {
				cf7a_log( sprintf( 'Unable to write geo-ip database at this path %s.', $destination_file_uri ) );
				return false;
			}

			/* decompress */
			$p = new PharData( $destination_file_uri . $ext );

			$temp_file          = $p->current()->getFilename();
			$temp_database_file = $temp_file . "/$database_type.mmdb";

			$p->extractTo(
				dirname( $destination_file_uri ),
				$temp_database_file,
				true
			);

			if ( copy(
				$plugin_upload_dir . $temp_database_file,
				$destination_file_uri
			) ) {
				/* remove original compressed file */
				wp_delete_file( $destination_file_uri . $ext );

				/* remove unpacked inside the folder */
				wp_delete_file( $plugin_upload_dir . $temp_database_file );

				/* remove the extracted directory */
				rmdir( $plugin_upload_dir . $temp_file );

				/* then update the database update only if needed  */
				if ( update_option( 'cf7a_geodb_update', strtotime( '+1 month' ) ) ) {
					return true;
				}

				$this->cf7a_geoip_schedule_update();

				cf7a_log( 'GeoIP DB downloaded with success ' . wp_date(get_option( 'date_format' )), 1 );
			}

			CF7_AntiSpam_Admin_Tools::cf7a_push_notice( __( 'GEO-IP Database update failed', 'cf7-antispam' ) );
			cf7a_log( 'GEO-IP Database copy failed ' . $plugin_upload_dir . $temp_database_file . ' to ' . $plugin_upload_dir );

		}

		return false;

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
			if ( $this->geo ) {
				$ip_data = $this->geo->country( $ip );

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
