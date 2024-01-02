<?php

namespace CF7_AntiSpam\Core;

use Exception;
use b8\b8;
/**
 * B8 related functions
 *
 * @since      0.0.1
 * @package    CF7_AntiSpam
 * @subpackage CF7_AntiSpam/includes
 * @author     Codekraft Studio <info@codekraft.it>
 */

/**
 * It's a class that interface the plugin with b8
 */
class CF7_AntiSpam_B8 {

	/**
	 * The bayesian filter class
	 *
	 * @since    0.4.0
	 * @access   private
	 * @var      b8|false|string $b8 the b8 filter
	 */
	private $b8;

	/**
	 * CF7_AntiSpam_b8 constructor.
	 */
	public function __construct() {
		$this->b8 = $this->cf7a_b8_init();
	}


	/**
	 * CF7_AntiSpam_Filters b8
	 *
	 * @return b8|false the B8 instance if it can be enabled, otherwise false
	 */
	private function cf7a_b8_init() {
		/* the database */
		global $wpdb;

		if ( ! extension_loaded( 'mysqli' ) ) {
			return false;
		}

		$dbh = $wpdb->__get( 'dbh' );

		if ( empty( $dbh ) ) {
			cf7a_log( 'There might be a problem with the MySQL server connection' );
			return false;
		}

		$config_b8      = array( 'storage' => 'mysql' );
		$config_storage = array(
			'resource' => $dbh,
			'table'    => $wpdb->prefix . 'cf7a_wordlist',
		);

		/* We use the default lexer settings */
		$config_lexer = array();

		/* We use the default degenerator configuration */
		$config_degenerator = array();

		/* Create a new b8 instance */
		try {
			return new b8( $config_b8, $config_storage, $config_lexer, $config_degenerator );
		} catch ( Exception $e ) {
			cf7a_log( 'error message: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * It takes a string, passes it to the b8 classifier, and returns the result
	 *
	 * @param string $message The message to be classified.
	 * @param bool   $verbose Whetever to log  the stats for this mail analysis.
	 *
	 * @return float The rating of the message.
	 */
	public function cf7a_b8_classify( $message, $verbose = false ) {
		if ( empty( $message ) ) {
			return false;
		}

		$time_elapsed = cf7a_microtime_float();

		$charset = get_option( 'blog_charset' );

		$rating = $this->b8->classify( htmlspecialchars( $message, ENT_QUOTES, $charset ) );

		if ( $verbose ) {
			if ( CF7ANTISPAM_DEBUG_EXTENDED ) {
				$mem_used      = round( memory_get_usage() / 1048576, 5 );
				$peak_mem_used = round( memory_get_peak_usage() / 1048576, 5 );
				$time_taken    = round( cf7a_microtime_float() - $time_elapsed, 5 );

				/* translators: in order - the memory used by antispam process, the peak memory and the time elapsed */
				cf7a_log( sprintf( 'd8 email classification: ' . $rating . ' - stats - Memory: %s - Peak memory: %s - Time Elapsed: %s', $mem_used, $peak_mem_used, $time_taken ) );
			} else {
				cf7a_log( 'd8 email classification: ' . $rating, 1 );
			}
		}

		return $rating;
	}

	/**
	 * It takes the message from the contact form, converts it to HTML, and then sends it to the b8 class to be learned as
	 * spam
	 *
	 * @param string $message The message to learn as spam.
	 */
	public function cf7a_b8_learn_spam( $message ) {
		if ( ! empty( $message ) ) {
			$this->b8->learn( htmlspecialchars( $message, ENT_QUOTES, get_option( 'blog_charset' ) ), b8::SPAM );
		}
	}

	/**
	 * It takes the message from the contact form, converts it to HTML, and then unlearns it as spam
	 *
	 * @param string $message The message to unlearn.
	 */
	public function cf7a_b8_unlearn_spam( $message ) {
		if ( ! empty( $message ) ) {
			$this->b8->unlearn( htmlspecialchars( $message, ENT_QUOTES, get_option( 'blog_charset' ) ), b8::SPAM );
		}
	}

	/**
	 * It takes a message, converts it to HTML entities, and then learns it as ham
	 *
	 * @param string $message The message to learn as ham.
	 */
	public function cf7a_b8_learn_ham( $message ) {
		if ( ! empty( $message ) ) {
			$this->b8->learn( htmlspecialchars( $message, ENT_QUOTES, get_option( 'blog_charset' ) ), b8::HAM );
		}
	}

	/**
	 * It takes the message from the contact form, converts it to HTML entities, and then unlearns it as ham
	 *
	 * @param string $message The message to unlearn.
	 */
	public function cf7a_b8_unlearn_ham( $message ) {
		if ( ! empty( $message ) ) {
			$this->b8->unlearn( htmlspecialchars( $message, ENT_QUOTES, get_option( 'blog_charset' ) ), b8::HAM );
		}
	}

}
