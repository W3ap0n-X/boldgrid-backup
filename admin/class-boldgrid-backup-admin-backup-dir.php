<?php
/**
 * Boldgrid Backup Admin Backup Dir.
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
 * Boldgrid Backup Admin Backup Dir class.
 *
 * @since 1.5.1
 */
class Boldgrid_Backup_Admin_Backup_Dir {

	/**
	 * Backup directory.
	 *
	 * @since 1.5.1
	 * @var   string
	 */
	public $backup_directory;

	/**
	 * The core class object.
	 *
	 * @since  1.5.1
	 * @access private
	 * @var    Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * An array of errors.
	 *
	 * @since  1.5.1
	 * @access public
	 * @var    array
	 */
	public $errors = array();

	/**
	 * The backup directory with the absolute path removed.
	 *
	 * @since  1.5.1
	 * @access public
	 * @var    string
	 */
	public $without_abspath;

	/**
	 * Constructor.
	 *
	 * @since 1.5.1
	 *
	 * @param Boldgrid_Backup_Admin_Core $core
	 */
	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Create our backup directory and necessary files.
	 *
	 * @since 1.5.1
	 *
	 * @param  string $backup_dir
	 * @return string
	 */
	public function create( $backup_dir ) {
		$check_permissions = __( 'Please ensure your backup directory exists and has the proper read, write, and modify permissions.', 'boldgrid-backup' );

		$cannot_create = __( 'Unable to create necessary file: %1$s<br />%2$s', 'boldgrid-backup' );
		$cannot_write = __( 'Unable to write to necessary file: %1$s<br />%2$s', 'boldgrid-backup' );

		$backup_dir = Boldgrid_Backup_Admin_Utility::trailingslashit( $backup_dir );

		$htaccess_path = $backup_dir . '.htaccess';
		$index_html_path = $backup_dir . 'index.html';
		$index_php_path = $backup_dir . 'index.php';

		$files = array(
			array(
				'type' => 'dir',
				'path' => $backup_dir,
			),
			array(
				'type' => 'file',
				'path' => $htaccess_path,
				'contents' => "<IfModule mod_access_compat.c>\nOrder Allow,Deny\nDeny from all\n</IfModule>\nOptions -Indexes\n",
			),
			array(
				'type' => 'file',
				'path' => $index_html_path,
			),
			array(
				'type' => 'file',
				'path' => $index_php_path,
			),
		);

		foreach( $files as $file ) {
			switch( $file['type'] ) {
				case 'dir':
					if( ! $this->core->wp_filesystem->exists( $file['path'] ) ) {
						$created = $this->core->wp_filesystem->mkdir( $file['path'] );
						if( ! $created ) {
							$this->errors[] = sprintf( $cannot_create, $file['path'], $check_permissions );
							return false;
						}
					}
					break;
				case 'file':
					if( ! $this->core->wp_filesystem->exists( $file['path'] ) ) {
						$created = $this->core->wp_filesystem->touch( $file['path'] );
						if( ! $created ) {
							$this->errors[] = sprintf( $cannot_create, $file['path'], $check_permissions );
							return false;
						}

						if( ! empty( $file['contents'] ) ) {
							$written = $this->core->wp_filesystem->put_contents( $file['path'], $file['contents'] );
							if( ! $written ) {
								$this->errors[] = sprintf( $cannot_write, $file['path'], $check_permissions );
								return false;
							}
						}
					}
					break;
			}
		}

		return $backup_dir;
	}

	/**
	 * Get and return the backup directory path.
	 *
	 * @since 1.0
	 *
	 * @return string|bool The backup directory path, or FALSE on error.
	 */
	public function get() {
		if( ! empty( $this->backup_directory ) ) {
			return $this->backup_directory;
		}

		$possible_dirs = $this->get_possible_dirs();

		$settings = $this->core->settings->get_settings();
		if( ! empty( $settings['backup_directory'] ) ) {
			$parent_from_settings = dirname( $settings['backup_directory'] );
			array_unshift( $possible_dirs, $parent_from_settings );
		}

		foreach( $possible_dirs as $possible_dir ) {
			// Ensure /parent_directory exists.
			if( ! $this->core->wp_filesystem->exists( $possible_dir ) ) {
				continue;
			}

			// Create /parent_directory/boldgrid-backup.
			$possible_dir .= DIRECTORY_SEPARATOR . 'boldgrid-backup';
			if( ! $this->core->wp_filesystem->exists( $possible_dir ) ) {
				$created = $this->core->wp_filesystem->mkdir( $possible_dir );
				if( ! $created ) {
					continue;
				}
			}

			// Validate read/write/modify/ect. permissions of /parent_directory/boldgrid-backup.
			$valid = $this->is_valid( $possible_dir );
			if( ! $valid ) {
				continue;
			}

			// Create necessary files, such as /parent_directory/boldgrid-backup/.htaccess
			$created = $this->create( $possible_dir );
			if( ! $created ) {
				continue;
			}

			$backup_directory = $created;
			$this->backup_directory = $backup_directory;
			break;
		}

		if( empty( $backup_directory ) ) {
			return false;
		}

		/*
		 * If there is not a valid backup directory stored in the settings, then
		 * we'll need to add / overwrite the value in the settings with a dir
		 * that we create.
		 */
		if( empty( $settings['backup_directory'] ) || $settings['backup_directory'] !== $backup_directory ) {
			$settings['backup_directory'] = $backup_directory;
			update_site_option( 'boldgrid_backup_settings', $settings );
		}

		/*
		 * Even in a Windows environment, wp_filesystem->dirlist retrieves paths
		 * with a / instead of \. Fix $without_abspath so we can properly check
		 * if files are in the backup directory.
		 */
		$this->without_abspath = str_replace( ABSPATH, '', $this->backup_directory );
		$this->without_abspath = str_replace( '\\', '/', $this->without_abspath );

		return $this->backup_directory;
	}

	/**
	 * Get an array of possible backup directories.
	 *
	 * @since  1.5.1
	 * @return array
	 */
	public function get_possible_dirs() {
		$dirs = array();

		// Standard value, the user's home directory.
		$dirs[] = $this->core->config->get_home_directory();

		if( $this->core->test->is_windows() ) {
			// C:\Users\user\AppData\Local
			$dirs[] = $this->core->config->get_home_directory() . DIRECTORY_SEPARATOR . 'AppData' . DIRECTORY_SEPARATOR . 'Local';

			if( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
				/*
				 * App_Data (Windows / Plesk).
				 *
				 * The App_Data folder is used as a data storage for the web
				 * application. It can store files such as .mdf, .mdb, and XML. It
				 * manages all of your application's data centrally. It is
				 * accessible from anywhere in your web application. The real
				 * advantage of the App_Data folder is that, any file you place
				 * there won't be downloadable.
				 */
				$app_data = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'App_Data';
				$dirs[] = str_replace( '\\\\', '\\', $app_data );
			}
		}

		// As a last resort, we will store backups in the /wp-content folder.
		$dirs[] = WP_CONTENT_DIR;

		return $dirs;
	}

	/**
	 * Determine if a file is in the backup directory.
	 *
	 * Pass in a filepath (relative to ABSPATH) and this method will determine
	 * if it's within the backup directory.
	 *
	 * Example $this->without_abspath:
	 * * wp-content/boldgrid-backup/
	 *
	 * Example $file(s):
	 * * (no)  .htaccess
	 * * (no)  wp-admin/admin.php
	 * * (yes) wp-content/boldgrid-backup/boldgrid-backup-domain-000-000-000.zip
	 *
	 * @since 1.5.1
	 *
	 * @param  string $file
	 * @return bool
	 */
	public function file_in_dir( $file ) {
		return false !== strpos( $file, $this->without_abspath );
	}

	/**
	 * Validate backup directory.
	 *
	 * Make sure it exists, it's writable, etc.
	 */
	public function is_valid( $backup_dir ) {
		$perms = $this->core->test->extensive_dir_test( $backup_dir );

		if( ! $perms['exists'] ) {
			$this->errors[] = sprintf( __( 'Backup Directory does not exists: %1$s', 'boldgrid-backup' ), $backup_dir );
		}

		if( ! $perms['read'] ) {
			$this->errors[] = sprintf( __( 'Backup Directory does not have read permission: %1$s', 'boldgrid-backup' ), $backup_dir );
		}

		if( ! $perms['rename'] ) {
			$this->errors[] = sprintf( __( 'Backup Directory does not have permission to rename files: %1$s', 'boldgrid-backup' ), $backup_dir );
		}

		if( ! $perms['delete'] ) {
			$this->errors[] = sprintf( __( 'Backup Directory does not have permission to delete files: %1$s', 'boldgrid-backup' ), $backup_dir );
		}

		if( ! $perms['dirlist'] ) {
			$this->errors[] = sprintf( __( 'Backup Directory does not have permission to retrieve directory listing: %1$s', 'boldgrid-backup' ), $backup_dir );
		}

		return $perms['exists'] && $perms['read'] && $perms['write'] && $perms['rename'] && $perms['delete'] && $perms['dirlist'];
	}
}