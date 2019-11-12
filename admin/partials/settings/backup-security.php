<?php
/**
 * File: backup-security.php
 *
 * Show "Backup Security" on settings page.
 *
 * @since      1.12.0
 * @package    Boldgrid_Backup
 * @subpackage Boldgrid_Backup/admin/partials/settings
 * @copyright  BoldGrid
 * @link       https://www.boldgrid.com
 * @author     BoldGrid <support@boldgrid.com>
 */

defined( 'WPINC' ) || die;
ob_start();
?>
<div class="bg-box">
	<div class="bg-box-top">
		<?php esc_html_e( 'Backup Security', 'boldgrid-backup' ); ?>
		<span class='dashicons dashicons-editor-help' data-id='backup_security'></span>
	</div>
	<div class="bg-box-bottom">
		<p class="help" data-id="backup_security">
<?php
printf(
	// translators: 1: HTML anchor link open tag, 2: HTML anchor closing tag.
	esc_html__(
		'Manage security features to help protect backup archives.%1$sThe %2$sEncrypt Database%3$s premium feature will encrypt the database dump file in backup archives in order to protect sensitive information.%1$sThe %2$sEncryption Token%3$s setting provides a way to copy or update the encryption settings.',
		'boldgrid-backup'
	),
	'<br /><br />',
	'<strong>',
	'</strong>'
);
?>
		</p>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Encrypt Database', 'boldgrid-backup' ); ?></th>
				<td>
					<input id="encrypt-db-enabled" type="radio" name="encrypt_db" value="1"
<?php
if ( $settings['encrypt_db'] ) {
	echo ' checked'; // Default.
}

if ( ! $is_premium || ! $is_premium_installed || ! $is_premium_active ) {
	echo ' disabled="disabled"';
}
?>
					/> <label for="encrypt-db-enabled"><?php esc_html_e( 'Enabled', 'boldgrid-backup' ); ?></label>
					&nbsp; <input id="encrypt-db-disabled" type="radio" name="encrypt_db" value="0"
<?php
if ( ! $settings['encrypt_db'] ) {
	echo ' checked';
}

if ( ! $is_premium || ! $is_premium_installed || ! $is_premium_active ) {
	echo ' disabled="disabled"';
}
?>
					/> <label for="encrypt-db-disabled"><?php esc_html_e( 'Disabled', 'boldgrid-backup' ); ?></label>
				</td>
			</tr>
<?php
if ( ! $is_premium ) {
	?>
			<tr><td colspan="2">
				<div class="bg-box-bottom premium wp-clearfix">
	<?php
	$get_premium_url = 'https://www.boldgrid.com/update-backup?source=bgbkup-settings-security';
	printf(
		// translators: 1: Get premium button/link.
		esc_html__( '%1$sA BoldGrid Backup Premium license is required for encryption features.', 'boldgrid-backup' ),
		$this->core->go_pro->get_premium_button( $get_premium_url, __( 'Get Premium', 'boldgrid-backup' ) ) // phpcs:ignore
	);
	?>
				</div>
			</td></tr>
	<?php
} elseif ( ! $is_premium_installed ) {
	?>
			<tr><td colspan="2">
				<div class="bg-box-bottom premium wp-clearfix">
	<?php
	$get_plugins_url = 'https://www.boldgrid.com/central/plugins?source=bgbkup-settings-security';
	printf(
		// translators: 1: Unlock Feature button/link.
		esc_html__( '%1$sThe BoldGrid Backup Premium plugin is required for encryption features.', 'boldgrid-backup' ),
		$this->core->go_pro->get_premium_button( $get_plugins_url, __( 'Unlock Feature', 'boldgrid-backup' ) ) // phpcs:ignore
	);
	?>
				</div>
			</td></tr>
	<?php
} elseif ( ! $is_premium_active ) {
	?>
			<tr><td colspan="2">
	<?php
	printf(
		// translators: 1: HTML anchor link open tag, 2: HTML anchor closing tag.
		esc_html__( 'The BoldGrid Backup Premium plugin is not active.  Encryption features are not available.  Please go to the %1$sPlugins%2$s page to activate it.', 'boldgrid-backup' ),
		'<a href="' .
			esc_url( admin_url( 'plugins.php?s=Boldgrid%20Backup%20Premium&plugin_status=inactive' ) ) .
			'">',
		'</a>'
	);
	?>
		</td></tr>
	<?php
}
?>
		</table>
	</div>
</div>
<?php
$output = ob_get_contents();
ob_end_clean();

return $output;
