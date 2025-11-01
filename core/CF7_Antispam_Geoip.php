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
use RecursiveIteratorIterator;
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

	private const DATABASE_TYPE = 'GeoLite2-Country';
	private const DATABASE_FILE = 'GeoLite2-Country.mmdb';
	private const UPDATE_INTERVAL = '+1 month';

	public function __construct() {
		$this->options = CF7_AntiSpam::get_options();
		$this->license = $this->set_license();
		$this->next_update = get_option( 'cf7a_geodb_update', 0 );
		$this->initialize_reader();
	}

	// ==================== Public API ====================

	/**
	 * Check if we have a database
	 *
	 * @return bool
	 */
	public function is_ready() {
		return $this->reader !== false;
	}

	/**
	 * Check if we have a database
	 *
	 * @return bool
	 */
	public function has_database() {
		return file_exists( $this->get_database_path() );
	}

	/**
	 * Check if we have a license
	 *
	 * @return bool
	 */
	public function has_license() {
		return ! empty( $this->license );
	}

	/**
	 * Check if the automatic download is enabled
	 *
	 * @return bool
	 */
	public function is_automatic_download_enabled() {
		return $this->options['enable_geoip_download'] ?? false;
	}

	/**
	 * Check if we can enable the geoip
	 *
	 * @return bool
	 */
	public function can_be_enabled() {
		return extension_loaded( 'zlib' ) || extension_loaded( 'phar' );
	}

	/**
	 * Download the database if is possibile and is not yet downloaded
	 *
	 * @return bool
	 */
	public function maybe_download() {
		if ( $this->is_automatic_download_enabled() && $this->has_license() && $this->can_be_enabled() && $this->should_update() ) {
			return $this->download_database();
		}
		return false;
	}

	/**
	 * Download the database
	 *
	 * @return bool
	 */
	public function force_download() {
		return $this->download_database();
	}

	/**
	 * Manual upload the database
	 *
	 * @param string $file
	 *
	 * @return bool
	 */
	public function manual_upload( $file ) {
		return $this->upload_file( $file );
	}

	/**
	 * Schedule update
	 *
	 * @param bool $now
	 *
	 * @return void
	 */
	public function schedule_update() {
		wp_clear_scheduled_hook( 'cf7a_geoip_update_db' );
		$next_event = strtotime( self::UPDATE_INTERVAL );
		wp_schedule_single_event( $next_event, 'cf7a_geoip_update_db', array( 'Geoip_update_db' ) );
	}

	/**
	 * Check the IP
	 *
	 * @param string $ip
	 *
	 * @return array
	 */
	public function check_ip( $ip ) {
		try {
			if ( ! $this->reader ) {
				return array( 'error' => 'geoip not initialized' );
			}

			$ip_data = $this->reader->country( $ip );

			return array(
				'continent'      => $ip_data->continent->code,
				'continent_name' => $ip_data->continent->name,
				'country'        => $ip_data->country->isoCode,
				'country_name'   => $ip_data->country->name,
			);
		} catch ( Exception $e ) {
			return array( 'error' => $e->getMessage() );
		}
	}

	// ==================== Private Helpers ====================

	/**
	 * Set the license
	 *
	 * @return string
	 */
	private function set_license() {
		if ( defined( 'CF7ANTISPAM_GEOIP_KEY' ) && CF7ANTISPAM_GEOIP_KEY ) {
			return CF7ANTISPAM_GEOIP_KEY;
		}
		return $this->options['geoip_dbkey'] ?? false;
	}

	/**
	 * Initialize the reader
	 *7j
	 * @return void
	 */
	private function initialize_reader() {
		try {
			$db_path = $this->get_database_path();
			if ( $this->has_database() ) {
				$this->reader = new Reader( $db_path );
			}
		} catch ( Exception $e ) {
			cf7a_log( 'GeoIP Database init error: ' . $e->getMessage(), 2 );
			$this->next_update = false;
			delete_option( 'cf7a_geodb_update' );
			$this->reader = false;
		}
	}

	/**
	 * Check if we should update the database
	 *
	 * @return bool
	 */
	private function should_update() {
		return ! $this->next_update || time() > $this->next_update;
	}

	/**
	 * Get the upload directory
	 *
	 * @return string
	 */
	private function get_upload_dir() {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['basedir'] . DIRECTORY_SEPARATOR . CF7ANTISPAM_NAME );
	}

	/**
	 * Get the database path
	 *
	 * @return string
	 */
	private function get_database_path() {
		return $this->get_upload_dir() . self::DATABASE_FILE;
	}

	// ==================== Directory Management ====================

	/**
	 * Ensure the upload directory exists
	 *
	 * @return string
	 */
	private function ensure_upload_directory() {
		$upload_dir = $this->get_upload_dir();

		if ( ! is_dir( $upload_dir ) ) {
			wp_mkdir_p( $upload_dir );
			$this->protect_directory( $upload_dir );
			cf7a_log( 'Geo-ip download folder created', 1 );
		}

		return $upload_dir;
	}

	/**
	 * Protect the directory
	 *
	 * @param string $upload_dir
	 *
	 * @return void
	 */
	private function protect_directory( $upload_dir ) {
		$this->create_htaccess( $upload_dir );
		$this->create_index_file( $upload_dir );
	}

	/**
	 * Create the .htaccess file
	 *
	 * @param string $upload_dir
	 *
	 * @return void
	 */
	private function create_htaccess( $upload_dir ) {
		$wp_filesystem = $this->get_filesystem();
		$htaccess_file = $upload_dir . '.htaccess';

		if ( ! file_exists( $htaccess_file ) ) {
			$content = "# Protect GeoIP database files\n";
			$content .= "<Files *.mmdb>\n";
			$content .= "    Require local\n";
			$content .= "</Files>\n";
			$wp_filesystem->put_contents( $htaccess_file, $content, FS_CHMOD_FILE );
		}
	}

	/**
	 * Create the index.php file
	 *
	 * @param string $upload_dir
	 *
	 * @return void
	 */
	private function create_index_file( $upload_dir ) {
		$wp_filesystem = $this->get_filesystem();
		$index_file = $upload_dir . 'index.php';

		if ( ! file_exists( $index_file ) ) {
			$wp_filesystem->put_contents( $index_file, "<?php\n// Silence is golden.\n", FS_CHMOD_FILE );
		}
	}

	// ==================== File Download ====================

	/**
	 * Download the database
	 *
	 * @return bool
	 */
	private function download_database() {
		if ( empty( $this->license ) ) {
			return false;
		}

		cf7a_log( 'GeoIP DB download start', 1 );

		$upload_dir = $this->ensure_upload_directory();
		$download_url = $this->get_download_url();
		$tar_file = $upload_dir . self::DATABASE_TYPE . '.tar.gz';

		// Download the file
		if ( ! $this->download_file( $download_url, $tar_file ) ) {
			return false;
		}

		// Extract and install
		$destination = $this->get_database_path();
		if ( ! $this->extract_and_install( $tar_file, $destination ) ) {
			return false;
		}

		// Cleanup and update metadata
		$this->cleanup_file( $tar_file );
		$this->update_next_update_time();

		cf7a_log( 'GeoIP DB downloaded successfully', 1 );
		return true;
	}

	/**
	 * Get the download URL
	 *
	 * @return string
	 */
	private function get_download_url() {
		return esc_url_raw(
			sprintf(
				'https://download.maxmind.com/app/geoip_download?edition_id=%s&license_key=%s&suffix=tar.gz',
				self::DATABASE_TYPE,
				sanitize_text_field( $this->license )
			)
		);
	}

	/**
	 * Download the file
	 *
	 * @param string $url
	 * @param string $destination
	 *
	 * @return bool
	 */
	private function download_file( $url, $destination ) {
		$wp_filesystem = $this->get_filesystem();

		try {
			$content = $wp_filesystem->get_contents( $url );

			if ( ! $content ) {
				$this->log_download_error( $url, $destination );
				return false;
			}

			$wp_filesystem->put_contents( $destination, $content, 770 );
			return true;
		} catch ( Exception $e ) {
			$this->log_download_error( $url, $destination, $e );
			return false;
		}
	}

	/**
	 * Log the download error
	 *
	 * @param string $url
	 * @param string $destination
	 * @param Exception $exception
	 *
	 * @return void
	 */
	private function log_download_error( $url, $destination, $exception = null ) {
		$message = __( 'Unable to download the geo-ip database. Please check that the key is correct!', 'cf7-antispam' );
		CF7_AntiSpam_Admin_Tools::cf7a_push_notice( $message );
		cf7a_log( "Download failed - URL: $url - Destination: $destination", 1 );

		if ( $exception ) {
			cf7a_log( $exception->getMessage(), 2 );
		}
	}

	// ==================== File Upload ====================

	/**
	 * Upload the file
	 *
	 * @param string $file
	 *
	 * @return bool
	 */
	private function upload_file( $file ) {
		if ( ! $this->validate_upload_file( $file ) ) {
			return false;
		}

		$this->ensure_upload_directory();
		$destination = $this->get_database_path();

		if ( $this->is_archive( $file ) ) {
			// Handle tar.gz files
			return $this->extract_and_install( $file, $destination );
		} elseif ( $this->is_mmdb( $file ) ) {
			// Handle direct .mmdb files
			return $this->install_mmdb_file( $file, $destination );
		}

		// remove the temp file
		$this->cleanup_file( $file );

		return false;
	}

	/**
	 * Validate the upload file
	 *
	 * @param string $file
	 *
	 * @return bool
	 */
	private function validate_upload_file( $file ) {
		if ( empty( $file ) || ! file_exists( $file ) ) {
			cf7a_log( 'Invalid or missing file path', 2 );
			return false;
		}

		if ( ! is_readable( $file ) ) {
			cf7a_log( 'File is not readable: ' . $file, 2 );
			return false;
		}

		return true;
	}

	/**
	 * Check if the file is an archive
	 *
	 * @param string $file The file to be checked
	 *
	 * @return bool true if the file is an archive, false otherwise
	 */
	private function is_archive( $file ) {
		return substr( $file, -3 ) === '.gz';
	}

	/**
	 * Check if the file is a .mmdb file
	 *
	 * @param string $file The file to be checked
	 *
	 * @return bool true if the file is a .mmdb file, false otherwise
	 */
	private function is_mmdb( $file ) {
		return substr( $file, -5 ) === '.mmdb';
	}

	// ==================== File Extraction ====================

	/**
	 * Extract and install the file
	 *
	 * @param string $tar_file
	 * @param string $destination
	 *
	 * @return bool
	 */
	private function extract_and_install( $tar_file, $destination ) {
		try {
			if ( ! class_exists( 'PharData' ) ) {
				$this->log_extraction_error( 'PharData class not available' );
				return false;
			}

			if ( ! $this->validate_tar_gz( $tar_file ) ) {
				return false;
			}

			$extracted_file = $this->extract_mmdb_from_archive( $tar_file );

			if ( ! $extracted_file ) {
				$this->log_extraction_error( 'No .mmdb file found in archive' );
				return false;
			}

			return $this->finalize_installation( $extracted_file );

		} catch ( Exception $e ) {
			$this->log_extraction_error( $e->getMessage(), $e );
			return false;
		}
	}

	/**
	 * Validate the tar.gz file
	 *
	 * @param string $file
	 *
	 * @return bool
	 */
	private function validate_tar_gz( $file ) {
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$mime_type = finfo_file( $finfo, $file );
		finfo_close( $finfo );

		$valid_types = array( 'application/gzip', 'application/x-gzip' );

		if ( ! in_array( $mime_type, $valid_types, true ) ) {
			cf7a_log( 'Invalid tar.gz MIME type: ' . $mime_type, 2 );
			return false;
		}

		return true;
	}

	/**
	 * Extract the .mmdb file from the archive
	 *
	 * @param string $tar_file
	 *
	 * @return string|false
	 */
	private function extract_mmdb_from_archive( $tar_file ) {
		$upload_dir = $this->get_upload_dir();
		$phar = new PharData( $tar_file );
		$iterator = new RecursiveIteratorIterator( $phar );

		// Find the .mmdb file
		foreach ( $iterator as $file_info ) {
			if ( $file_info->isFile() && pathinfo( $file_info->getFilename(), PATHINFO_EXTENSION ) === 'mmdb' ) {
				// Get the internal path relative to the archive root
				$internal_path = $phar->getPath() ?
					str_replace( 'phar://' . $tar_file . '/', '', $file_info->getPathname() ) :
					$file_info->getFilename();

				// Extract the specific file to the destination directory
				$phar->extractTo( $upload_dir, $internal_path, true );

				// delete the tar file
				$this->cleanup_file( $tar_file );

				// Return the full final path to the extracted file
				return $internal_path;
			}
		}

		return false;
	}

	/**
	 * Finalize the installation
	 *
	 * @param string $extracted_file
	 * @param string $destination
	 *
	 * @return bool
	 */
	private function finalize_installation( $extracted_file ) {
		$mmdb_full_path = $this->get_upload_dir() . "/" . $extracted_file;
		if ( ! file_exists( $mmdb_full_path ) ) {
			cf7a_log( 'Extraction failed - file not found: ' . $extracted_file, 2 );
			return false;
		}

		// the cf7-antispam upload directory
		$upload_dir = $this->get_upload_dir();
		// The relative path of the mmdb file
		$relative_path = str_replace( $upload_dir, '', $extracted_file );
		// The extraction directory is the directory where the .mmdb file is located
		$path_parts = explode('/', $relative_path);
		$extraction_dir = $upload_dir . explode('/', $relative_path)[0];

		// Validate the mmdb file
		if ( ! $this->validate_mmdb_file( $mmdb_full_path ) ) {
			cf7a_log( "Extraction failed " . $mmdb_full_path, 2);
			$this->cleanup_extraction_directory( $extraction_dir );
			return false;
		}

		// Move the mmdb file to the destination directory
		$wp_filesystem = $this->get_filesystem();
		$result = $wp_filesystem->move( $mmdb_full_path, $this->get_database_path(), true );

		// Check if the mmdb file is located inside a directory
		if ( $extraction_dir !== $this->get_upload_dir() ) {
			// cleanup extraction directory
			$this->cleanup_extraction_directory( $extraction_dir );
		}

		if ( $result ) {
			cf7a_log( 'GeoIP database installed successfully', 1 );
			return true;
		}

		cf7a_log( 'Failed to move extracted file to destination', 2 );
		return false;
	}

	/**
	 * Install the .mmdb file
	 *
	 * @param string $source
	 * @param string $destination
	 *
	 * @return bool
	 */
	private function install_mmdb_file( $source, $destination ) {
		if ( ! $this->validate_mmdb_file( $source ) ) {
			cf7a_log( 'Invalid MaxMind database file format', 2 );
			return false;
		}

		$wp_filesystem = $this->get_filesystem();
		$contents = $wp_filesystem->get_contents( $source );

		if ( $contents === false ) {
			cf7a_log( 'Unable to read source file: ' . $source, 2 );
			return false;
		}

		if ( ! $wp_filesystem->put_contents( $destination, $contents, FS_CHMOD_FILE ) ) {
			cf7a_log( 'Unable to write destination file: ' . $destination, 2 );
			return false;
		}

		cf7a_log( 'GeoIP database uploaded successfully', 1 );
		return true;
	}

	/**
	 * Validate the .mmdb file
	 *
	 * @param string $file
	 *
	 * @return bool
	 */
	private function validate_mmdb_file( $file ) {
		// we are going to try the uploaded database in order to know if it is working
		$new_geo = new Reader( $file );
		try {
			$new_geo->country( '8.8.8.8' );
		} catch ( Exception $e ) {
			cf7a_log( 'Invalid MaxMind database file format', 1 );
			return false;
		}

		return true;
	}

	// ==================== Cleanup ====================

	/**
	 * Cleanup the file
	 *
	 * @param string $file
	 *
	 * @return void
	 */
	private function cleanup_file( $file ) {
		$wp_filesystem = $this->get_filesystem();
		$wp_filesystem->delete( $file );
	}

	/**
	 * Cleanup the extraction directory
	 *
	 * @param string $extracted_file
	 *
	 * @return void
	 */
	private function cleanup_extraction_directory( $extracted_dir ) {
		$wp_filesystem = $this->get_filesystem();
		$wp_filesystem->rmdir( $extracted_dir, true );
	}

	// ==================== Utilities ====================

	/**
	 * Get the filesystem
	 *
	 * @return WP_Filesystem
	 */
	private function get_filesystem() {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem;
	}

	/**
	 * Update the next update time
	 *
	 * @return void
	 */
	private function update_next_update_time() {
		$update_date = strtotime( self::UPDATE_INTERVAL );

		if ( ! $this->next_update ) {
			add_option( 'cf7a_geodb_update', $update_date );
		} else {
			update_option( 'cf7a_geodb_update', $update_date );
		}

		$this->next_update = $update_date;
	}

	/**
	 * Log the extraction error
	 *
	 * @param string $message
	 * @param Exception $exception
	 *
	 * @return void
	 */
	private function log_extraction_error( $message, $exception = null ) {
		$translated = esc_html__( 'GEO-IP Database extraction failed', 'cf7-antispam' );
		CF7_AntiSpam_Admin_Tools::cf7a_push_notice( $translated );
		CF7_AntiSpam_Admin_Tools::cf7a_push_notice( esc_html( $message ) );
		cf7a_log( $message, 2 );

		if ( $exception ) {
			cf7a_log( $exception->getMessage(), 2 );
		}
	}
}
