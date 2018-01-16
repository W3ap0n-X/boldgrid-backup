<?php
/**
 * Archive Details class.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.1
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup Admin Archive Details Class.
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Archive_Details {

	/**
	 * The core class object.
	 *
	 * @since  1.5.1
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * An array of remote storage locations.
	 *
	 * @since  1.5.4
	 * @access public
	 * @var    array
	 */
	public $remote_storage_li = array();

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.5.4
	 */
	public function enqueue_scripts() {
		wp_enqueue_style(
			'boldgrid-backup-admin-zip-browser',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-zip-browser.css',
			array(),
			BOLDGRID_BACKUP_VERSION,
			'all'
		);

		wp_register_script(
			'boldgrid-backup-admin-archive-details',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-archive-details.js',
			array( 'jquery', ),
			BOLDGRID_BACKUP_VERSION
		);
		$translations = array(
			'uploading' => __( 'Uploading', 'boldgrid-backup' ),
			'uploaded' => __( 'Uploaded', 'boldgrid-backup' ),
			'failUpload' => __( 'Unable to upload backup file.', 'boldgrid-backup' ),
		);
		wp_localize_script( 'boldgrid-backup-admin-archive-details', 'boldgrid_backup_archive_details', $translations );
		wp_enqueue_script( 'boldgrid-backup-admin-archive-details' );

		wp_register_script(
			'boldgrid-backup-admin-zip-browser',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-zip-browser.js',
			array( 'jquery', ),
			BOLDGRID_BACKUP_VERSION
		);
		$unknown_error = __( 'An unknown error has occurred.', 'boldgrid-backup' );
		$translations = array(
			'loading' => __( 'Loading', 'boldgrid-backup' ),
			'home' => __( 'Home', 'boldgrid-backup' ),
			'restoring' => __( 'Restoring', 'boldgrid-backup' ),
			'confirmDbRestore' => __( 'Are you sure you want to restore this database backup?', 'boldgrid-backup' ),
			'unknownBrowseError' => __( 'An unknown error has occurred when trying to get a listing of the files in this archive.', 'boldgrid-backup' ),
			'unknownError' => $unknown_error,
			'unknownErrorNotice' => sprintf( '<div class="%1$s"><p>%2$s</p></div>', $this->core->notice->lang['dis_error'], $unknown_error ),
		);
		wp_localize_script( 'boldgrid-backup-admin-zip-browser', 'boldgrid_backup_zip_browser', $translations );
		wp_enqueue_script( 'boldgrid-backup-admin-zip-browser' );

		wp_enqueue_style( 'bglib-ui-css' );

		/**
		 * Allow other plugins to enqueue scripts on this page.
		 *
		 * @since 1.5.3
		 */
		do_action( 'boldgrid_backup_enqueue_archive_details' );
	}

	/**
	 * Render the details page of an archive.
	 *
	 * @since 1.5.1
	 */
	public function render_archive() {
		if ( ! empty( $_POST['delete_now'] ) ) {
			$this->core->delete_archive_file();
		}

		$this->enqueue_scripts();
		$this->core->archive_actions->enqueue_scripts();

		$archive_found = false;

		$filename = ! empty( $_GET['filename'] ) ? $_GET['filename'] : false;
		if( ! $filename ) {
			echo __( 'No archive specified.', 'boldgrid-backup' );
			return;
		}

		// Get our archive.
		$archive = $this->core->archive->get_by_name( $filename );
		if( $archive ) {
			$log = $this->core->archive_log->get_by_zip( $archive['filepath'] );
			$archive = array_merge( $log, $archive );
			$archive_found = true;
			$dump_file = $this->core->get_dump_file( $archive['filepath'] );
		} else {
			$archive = array();
			if( ! empty( $_GET['filename'] ) ) {
				$archive['filename'] = $_GET['filename'];
			}
		}

		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-archive-details.php';
	}

	/**
	 * Validate the nonce on the Backup Archive Details page.
	 *
	 * On the backup archive page, there is a nonce used by several different
	 * methods, boldgrid_backup_remote_storage_upload. This method is an easy
	 * way to validate the nonce.
	 *
	 * The nonce can be added to an ajax request's data via:
	 * 'security' : $( '#_wpnonce' ).val()
	 *
	 * @since 1.5.4
	 */
	public function validate_nonce() {
		return check_ajax_referer( 'boldgrid_backup_remote_storage_upload', 'security', false );
	}
}
