<?php
/**
 * Archive Browser class.
 *
 * @link  http://www.boldgrid.com
 * @since 1.5.2
 *
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright  BoldGrid.com
 * @version    $Id$
 * @author     BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup Admin Archive Browser Class.
 *
 * @since 1.5.2
 */
class Boldgrid_Backup_Admin_Archive_Browser {

	/**
	 * The core class object.
	 *
	 * @since  1.5.2
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.5.2
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 *
	 */
	public function wp_ajax_browse_archive() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'boldgrid-backup' ) );
		}

		if( ! check_ajax_referer( 'boldgrid_backup_remote_storage_upload', 'security', false ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'boldgrid-backup' ) );
		}

		$filepath = ! empty( $_POST['filepath'] ) ? $_POST['filepath'] : false;
		if( empty( $filepath ) || ! $this->core->wp_filesystem->exists( $filepath ) ) {
			wp_send_json_error( __( 'Invalid archive filepath.', 'boldgrid-backup' ) );
		}

		$dir = ! empty( $_POST['dir'] ) ? $_POST['dir'] : null;

		$zip = new Boldgrid_Backup_Admin_Compressor_Pcl_Zip( $this->core );
		$contents = $zip->browse( $filepath, $dir );

		$tr = '';
		$empty_directory = '<tr><td colspan="3">' . __( 'Empty directory', 'boldgrid-backup' ) . '</td></tr>';

		$table = sprintf(
			'<table class="wp-list-table fixed striped remote-storage widefat">
				<thead>
					<tr>
						<th>%1$s</th>
						<th>%2$s</th>
						<th>%3$s</th>
					</tr>
				</thead>
				<tbody>
			',
			__( 'Name', 'boldgrid-backup' ),
			__( 'Size', 'boldgrid-backup' ),
			__( 'Last Modified', 'boldgrid-backup' )
		);

		foreach( $contents as $file ) {
			$tr .= include BOLDGRID_BACKUP_PATH . '/admin/partials/archive-details/browser-entry.php';
		}

		$table .= empty( $tr ) ? $empty_directory : $tr;

		$table .= '</tbody></table>';

		wp_send_json_success( $table );
	}
}