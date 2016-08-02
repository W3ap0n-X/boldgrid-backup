<?php
/**
 * The admin-specific utilities methods for the plugin
 *
 * @link http://www.boldgrid.com
 * @since 1.0
 *
 * @package Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Backup admin settings class.
 *
 * @since 1.0
 */
class Boldgrid_Backup_Admin_Settings {
	/**
	 * The core class object.
	 *
	 * @since 1.0
	 * @access private
	 * @var Boldgrid_Backup_Admin_Core
	 */
	private $core;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param Boldgrid_Backup_Admin_Core $core Core class object.
	 */
	public function __construct( $core ) {
		// Save the Boldgrid_Backup_Admin_Core object as a class property.
		$this->core = $core;
	}

	/**
	 * Get settings using defaults.
	 *
	 * @since 1.0
	 *
	 * @return array An array of settings.
	 */
	public function get_settings() {
		// Get settings.
		if ( true === is_multisite() ) {
			$settings = get_site_option( 'boldgrid_backup_settings' );
		} else {
			$settings = get_option( 'boldgrid_backup_settings' );
		}

		// Parse settings.
		if ( false === empty( $settings['schedule'] ) ) {
			// Update schedule format.
			// Days of the week.
			$settings['schedule']['dow_sunday'] = ( false ===
				empty( $settings['schedule']['dow_sunday'] ) ? 1 : 0 );
			$settings['schedule']['dow_monday'] = ( false ===
				empty( $settings['schedule']['dow_monday'] ) ? 1 : 0 );
			$settings['schedule']['dow_tuesday'] = ( false ===
				empty( $settings['schedule']['dow_tuesday'] ) ? 1 : 0 );
			$settings['schedule']['dow_wednesday'] = ( false ===
				empty( $settings['schedule']['dow_wednesday'] ) ? 1 : 0 );
			$settings['schedule']['dow_thursday'] = ( false ===
				empty( $settings['schedule']['dow_thursday'] ) ? 1 : 0 );
			$settings['schedule']['dow_friday'] = ( false ===
				empty( $settings['schedule']['dow_friday'] ) ? 1 : 0 );
			$settings['schedule']['dow_saturday'] = ( false ===
				empty( $settings['schedule']['dow_saturday'] ) ? 1 : 0 );

			// Time of day.
			$settings['schedule']['tod_h'] = ( false === empty( $settings['schedule']['tod_h'] ) ? $settings['schedule']['tod_h'] : mt_rand( 1, 5 ) );
			$settings['schedule']['tod_m'] = ( false === empty( $settings['schedule']['tod_m'] ) ? $settings['schedule']['tod_m'] : mt_rand( 1, 59 ) );
			$settings['schedule']['tod_a'] = ( false === empty( $settings['schedule']['tod_a'] ) ? $settings['schedule']['tod_a'] : 'AM' );

			// Notification settings.
			$settings['notifications']['backup'] = ( false ===
				isset( $settings['notifications']['backup'] ) || false ===
				empty( $settings['notifications']['backup'] ) ? 1 : 0 );
			$settings['notifications']['restore'] = ( false ===
				isset( $settings['notifications']['restore'] ) || false ===
				empty( $settings['notifications']['restore'] ) ? 1 : 0 );

			// Notification email address.
			if ( true === empty( $settings['notification_email'] ) ) {
				$settings['notification_email'] = $this->core->config->get_admin_email();
			}

			// Other settings.
			$settings['auto_backup'] = ( false === isset( $settings['auto_backup'] ) ||
				false === empty( $settings['auto_backup'] ) ? 1 : 0 );
			$settings['auto_rollback'] = ( false === isset( $settings['auto_rollback'] ) ||
				false === empty( $settings['auto_rollback'] ) ? 1 : 0 );
		} else {
			// Define defaults.
			// Days of the week.
			$settings['schedule']['dow_sunday'] = 0;
			$settings['schedule']['dow_monday'] = 0;
			$settings['schedule']['dow_tuesday'] = 0;
			$settings['schedule']['dow_wednesday'] = 0;
			$settings['schedule']['dow_thursday'] = 0;
			$settings['schedule']['dow_friday'] = 0;
			$settings['schedule']['dow_saturday'] = 0;

			// Time of day.
			$settings['schedule']['tod_h'] = mt_rand( 1, 5 );
			$settings['schedule']['tod_m'] = mt_rand( 1, 59 );
			$settings['schedule']['tod_a'] = 'AM';

			// Other settings.
			$settings['retention_count'] = 5;
			$settings['notification_email'] = $this->core->config->get_admin_email();
			$settings['notifications']['backup'] = 1;
			$settings['notifications']['restore'] = 1;
			$settings['auto_backup'] = 1;
			$settings['auto_rollback'] = 1;
		}

		// If not updating the settings, then check cron for schedule.
		if ( false === isset( $_POST['save_time'] ) ) {
			$cron_schedule = $this->core->cron->read_cron_entry();
		}

		// If a cron schedule was found, then merge the settings.
		if ( false === empty( $cron_schedule ) ) {
			$settings['schedule'] = array_merge( $settings['schedule'], $cron_schedule );
		}

		// Return the settings array.
		return $settings;
	}

	/**
	 * Update settings.
	 *
	 * @since 1.0
	 * @access private
	 *
	 * @see Boldgrid_Backup_Admin_Cron::add_cron_entry().
	 *
	 * @return bool Update success.
	 */
	private function update_settings() {
		// Verify nonce.
		check_admin_referer( 'boldgrid-backup-settings', 'settings_auth' );

		// Check for settings update.
		if ( false === empty( $_POST['save_time'] ) ) {
			// Get settings.
			$settings = $this->get_settings();

			// Initialize $update_error.
			$update_error = false;

			// Initialize $days_scheduled.
			$days_scheduled = array();

			// Validate input for schedule.
			$indices = array(
				'dow_sunday',
				'dow_monday',
				'dow_tuesday',
				'dow_wednesday',
				'dow_thursday',
				'dow_friday',
				'dow_saturday',
				'tod_h',
				'tod_m',
				'tod_a',
			);

			foreach ( $indices as $index ) {
				// Determine input type.
				if ( 0 === strpos( $index, 'dow_' ) ) {
					$type = 'day';
				} elseif ( 'tod_h' === $index ) {
					$type = 'h';
				} elseif ( 'tod_m' === $index ) {
					$type = 'm';
				} elseif ( 'tod_a' === $index ) {
					$type = 'a';
				} else {
					// Unknown type.
					$type = '?';
				}

				if ( false === empty( $_POST[ $index ] ) ) {
					// Validate by type.
					switch ( $type ) {
						case 'day' :
							// Convert to integer.
							$_POST[ $index ] = (int) $_POST[ $index ];

							// If day was scheduled, then track it.
							if ( 1 === $_POST[ $index ] ) {
								$days_scheduled[] = date( 'w', strtotime( str_replace( 'dow_', '', $index ) ) );
							}

							break;
						case 'h' :
							if ( $_POST[ $index ] < 1 || $_POST[ $index ] > 12 ) {
								// Error in input.
								$update_error = true;
								break 2;
							}

							// Convert to integer.
							$_POST[ $index ] = (int) $_POST[ $index ];

							break;
						case 'm' :
							if ( $_POST[ $index ] < 0 || $_POST[ $index ] > 59 ) {
								// Error in input.
								$update_error = true;
								break 2;
							}

							// Convert to integer.
							$_POST[ $index ] = (int) $_POST[ $index ];

							// Pad left with 0.
							$_POST[ $index ] = str_pad( $_POST[ $index ], 2, '0', STR_PAD_LEFT );

							break;
						case 'a' :
							if ( 'AM' !== $_POST[ $index ] && 'PM' !== $_POST[ $index ] ) {
								// Error in input; unknown type.
								$update_error = true;
								break 2;
							}

							break;
						default :
							// Error in input; unknown type.
							$update_error = true;
							break 2;
					}

					// Update the setting value provided.
					$settings['schedule'][ $index ] = $_POST[ $index ];
				} elseif ( 'day' === $type ) {
					// Unassigned days.
					$settings['schedule'][ $index ] = 0;
				} else {
					// Error in input.
					$update_error = true;

					break;
				}
			}

			// Validate input for other settings.
			$settings['retention_count'] = ( true === isset( $_POST['retention_count'] ) ?
				intval( $_POST['retention_count'] ) : 5 );

			$settings['notifications']['backup'] = ( ( true === isset( $_POST['notify_backup'] ) &&
				'1' === $_POST['notify_backup'] ) ? 1 : 0 );

			$settings['notifications']['restore'] = ( ( true === isset( $_POST['notify_restore'] ) &&
				'1' === $_POST['notify_restore'] ) ? 1 : 0 );

			$settings['auto_backup'] = ( ( false === isset( $_POST['auto_backup'] ) ||
				'1' === $_POST['auto_backup'] ) ? 1 : 0 );

			$settings['auto_rollback'] = ( ( false === isset( $_POST['auto_rollback'] ) ||
				'1' === $_POST['auto_rollback'] ) ? 1 : 0 );

			// Update notification email address, if changed.
			if ( true === isset( $settings['notification_email'] ) &&
			sanitize_email( $_POST['notification_email'] ) !== $settings['notification_email'] ) {
				$settings['notification_email'] = sanitize_email( $_POST['notification_email'] );
			}

			// Get the current backup directory path.
			$backup_directory = $this->core->config->get_backup_directory();

			// Save backup directory, if changed.
			if ( false === empty( $_POST['backup_directory'] ) &&
			trim( $_POST['backup_directory'] ) !== $backup_directory ) {
				// Sanitize.
				$backup_directory = trim( $_POST['backup_directory'] );

				// Set the directory.
				$is_directory_set = $this->core->config->set_backup_directory( $backup_directory );

				// If the backup directory was configured, then save the new setting.
				if ( true === $is_directory_set ) {
					$settings['backup_directory'] = $backup_directory;
				} else {
					$update_error = true;
				}
			}

			// If no errors, then save the settings.
			if ( false === $update_error ) {
				// Record the update time.
				$settings['updated'] = time();

				// Attempt to update WP option.
				if ( true === is_multisite() ) {
					$update_status = update_site_option( 'boldgrid_backup_settings', $settings );
				} else {
					$update_status = update_option( 'boldgrid_backup_settings', $settings );
				}

				if ( true !== $update_status ) {
					// Failure.
					$update_error = true;

					do_action(
						'boldgrid_backup_notice',
						esc_html__(
							'Invalid settings submitted.  Please try again.',
							'boldgrid-backup'
						),
						'notice notice-error is-dismissible'
					);
				} else {
					// Delete existing backup cron jobs, and add the new cron entry.
					$cron_status = $this->core->cron->add_cron_entry();
				}
			} else {
				// Interrupted by a previous error.
				do_action(
					'boldgrid_backup_notice',
					esc_html__(
						'Invalid settings submitted.  Please try again.',
						'boldgrid-backup'
					),
					'notice notice-error is-dismissible'
				);
			}
		}

		// If delete cron failed, then show a notice.
		if ( true === isset( $cron_status ) && true !== $cron_status ) {
			$update_error = true;

			do_action(
				'boldgrid_backup_notice',
				esc_html__(
					'An error occurred when modifying cron jobs.  Please try again.',
					'boldgrid-backup'
				),
				'notice notice-error is-dismissible'
			);
		}

		// If there was no error, then show success notice.
		if ( false === $update_error ) {
			// Success.
			do_action(
				'boldgrid_backup_notice',
				esc_html__( 'Settings saved.', 'boldgrid-backup' ),
				'updated settings-error notice is-dismissible'
			);
		}

		// Return success.
		return ! $update_error;
	}

	/**
	 * Delete the boldgrid_backup_pending_rollback option.
	 *
	 * @since 1.0.1
	 */
	public function delete_rollback_option() {
		if ( true === is_multisite() ) {
			delete_site_option( 'boldgrid_backup_pending_rollback' );
		} else {
			delete_option( 'boldgrid_backup_pending_rollback' );
		}
	}

	/**
	 * Menu callback to display the Backup schedule page.
	 *
	 * @since 1.0
	 *
	 * @return null
	 */
	public function page_backup_settings() {
		// Run the functionality tests.
		$is_functional = $this->core->test->get_is_functional();

		// If tests fail, then show an admin notice and abort.
		if ( false === $is_functional ) {
			do_action(
				'boldgrid_backup_notice',
				sprintf(
					esc_html__(
						'Functionality test has failed.  You can go to <a href="%s">Functionality Test</a> to view a report.',
						'boldgrid-backup'
					),
					admin_url( 'admin.php?page=boldgrid-backup-test' )
				),
				'notice notice-error is-dismissible'
			);
		}

		// Display warning on resource usage and backups.
		do_action(
			'boldgrid_backup_notice',
			esc_html__(
				'Warning: Making backups uses resources. When the system is backing up, it will slow down your site for visitors. Furthermore, when the database itself is being copied, your site must “pause” temporarily to preserve data integrity. For most sites, the pause is typically a few seconds and is not noticed by visitors. Large sites take longer though. Please keep the number of backups you have stored and how often you make those backups to a minimum.',
				'boldgrid-backup'
			),
			'notice notice-warning is-dismissible'
		);

		// Get BoldGrid reseller settings.
		$boldgrid_reseller = get_option( 'boldgrid_reseller' );

		// If not part of a reseller, then show the unofficial host notice.
		if ( true === empty( $boldgrid_reseller ) ) {
			do_action(
				'boldgrid_backup_notice',
				esc_html__(
					'Please note that your web hosting provider may have a policy against these types of backups. Please verify with your provider or choose a BoldGrid Official Host.',
					'boldgrid-backup'
				),
				'notice notice-warning is-dismissible'
			);
		}

		// Check for settings update.
		if ( true === isset( $_POST['save_time'] ) ) {
			// Verify nonce.
			check_admin_referer( 'boldgrid-backup-settings', 'settings_auth' );

			$this->update_settings();
		}

		// Enqueue CSS for the settings page.
		wp_enqueue_style( 'boldgrid-backup-admin-settings',
			plugin_dir_url( __FILE__ ) . 'css/boldgrid-backup-admin-settings.css', array(),
			BOLDGRID_BACKUP_VERSION, 'all'
		);

		// Enqueue the JS for the settings page.
		wp_enqueue_script( 'boldgrid-backup-admin-settings',
			plugin_dir_url( __FILE__ ) . 'js/boldgrid-backup-admin-settings.js',
			array(
				'jquery',
			), BOLDGRID_BACKUP_VERSION, false
		);

		// Get settings.
		$settings = $this->get_settings();

		// If the directory path is not in the settings, then add it for the form.
		if ( true === empty( $settings['backup_directory'] ) ) {
			$settings['backup_directory'] = $this->core->config->get_backup_directory();
		}

		// Include the page template.
		include BOLDGRID_BACKUP_PATH . '/admin/partials/boldgrid-backup-admin-settings.php';

		return;
	}
}