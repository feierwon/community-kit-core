<?php
/**
 * Dashboard admin view.
 *
 * Displays system status, registered modules, and license key management.
 *
 * @package CommunityKit
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$registry    = CK_Registry::get_instance();
$modules     = $registry->get_modules();
$scanner     = new CK_Scanner();
$scan_groups = $scanner->run();
$license_key = get_option( 'community_kit_license_key', '' );
$updated     = isset( $_GET['ck-updated'] ) && '1' === $_GET['ck-updated']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$status_labels = array(
	'pass' => __( 'Pass', 'community-kit' ),
	'warn' => __( 'Warning', 'community-kit' ),
	'fail' => __( 'Fail', 'community-kit' ),
);
?>
<div class="wrap ck-dashboard">
	<h1><?php esc_html_e( 'Community Kit', 'community-kit' ); ?></h1>

	<div class="ck-welcome-panel">
		<p><?php esc_html_e( 'Welcome to Community Kit — a modular framework for nonprofits and community organizations.', 'community-kit' ); ?></p>
		<p>
			<?php
			printf(
				/* translators: %s: plugin version */
				esc_html__( 'Version %s', 'community-kit' ),
				esc_html( CK_VERSION )
			);
			?>
		</p>
	</div>

	<!-- System Status -->
	<div class="ck-section">
		<h2><?php esc_html_e( 'System Status', 'community-kit' ); ?></h2>

		<?php foreach ( $scan_groups as $group_label => $results ) : ?>
			<h3 class="ck-group-heading"><?php echo esc_html( $group_label ); ?></h3>
			<table class="widefat striped ck-status-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Check', 'community-kit' ); ?></th>
						<th><?php esc_html_e( 'Status', 'community-kit' ); ?></th>
						<th><?php esc_html_e( 'Details', 'community-kit' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $results as $result ) : ?>
						<tr>
							<td><?php echo esc_html( $result['label'] ); ?></td>
							<td>
								<span class="ck-status ck-status--<?php echo esc_attr( $result['status'] ); ?>">
									<?php echo esc_html( $status_labels[ $result['status'] ] ?? $result['status'] ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $result['message'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endforeach; ?>
	</div>

	<!-- Registered Modules -->
	<div class="ck-section">
		<h2><?php esc_html_e( 'Registered Modules', 'community-kit' ); ?></h2>

		<?php if ( empty( $modules ) ) : ?>
			<p class="ck-empty-state"><?php esc_html_e( 'No modules registered yet.', 'community-kit' ); ?></p>
		<?php else : ?>
			<table class="widefat striped ck-modules-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ID', 'community-kit' ); ?></th>
						<th><?php esc_html_e( 'Name', 'community-kit' ); ?></th>
						<th><?php esc_html_e( 'Version', 'community-kit' ); ?></th>
						<th><?php esc_html_e( 'Status', 'community-kit' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $modules as $module ) : ?>
						<tr>
							<td><code><?php echo esc_html( $module['id'] ); ?></code></td>
							<td>
								<strong><?php echo esc_html( $module['name'] ); ?></strong>
								<?php if ( ! empty( $module['description'] ) ) : ?>
									<br><span class="description"><?php echo esc_html( $module['description'] ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $module['version'] ); ?></td>
							<td>
								<?php if ( ! empty( $module['active'] ) ) : ?>
									<span class="ck-status ck-status--active"><?php esc_html_e( 'Active', 'community-kit' ); ?></span>
								<?php else : ?>
									<span class="ck-status ck-status--inactive"><?php esc_html_e( 'Inactive', 'community-kit' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<!-- License Key -->
	<div class="ck-section ck-license-section">
		<h2><?php esc_html_e( 'License Key', 'community-kit' ); ?></h2>

		<?php if ( $updated ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'License key saved.', 'community-kit' ); ?></p>
			</div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
			<?php wp_nonce_field( 'ck_save_license', 'ck_license_nonce' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="ck-license-key"><?php esc_html_e( 'License Key', 'community-kit' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							id="ck-license-key"
							name="ck_license_key"
							value="<?php echo esc_attr( $license_key ); ?>"
							class="regular-text"
							placeholder="<?php esc_attr_e( 'Enter your license key', 'community-kit' ); ?>"
						/>
						<p class="description">
							<?php esc_html_e( 'Enter your Community Kit license key to enable premium features and updates.', 'community-kit' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save License Key', 'community-kit' ), 'primary', 'ck-save-license' ); ?>
		</form>
	</div>
</div>
