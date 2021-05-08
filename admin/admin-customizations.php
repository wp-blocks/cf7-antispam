<?php
class CF7_AntiSpam_Admin_Customizations {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'cf7a_admin_menu'), 99, 0 );
	}

	public function cf7a_admin_menu() {

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once CF7ANTISPAM_PLUGIN_DIR . '/admin/admin.php';

		$admin = new CF7_AntiSpam;

		$addnew = add_submenu_page( 'wpcf7',
			__( 'Antispam', 'cf7-antispam' ),
			__( 'Antispam', 'cf7-antispam' ),
			'wpcf7_edit_contact_forms',
			'wpcf7-antispam',
			array( $this, 'cf7a_admin_dashboard')
		);

		add_action( 'load-' . $addnew, 'wpcf7_load_contact_form_admin', 10, 0 );
	}


	public function cf7a_admin_dashboard() {

	}
}
