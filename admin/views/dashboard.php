<?php
/**
 * Dashboard admin view.
 *
 * @package CommunityKit
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$modules = CK_Registry::get_instance()->get_modules();
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

	<h2><?php esc_html_e( 'Registered Modules', 'community-kit' ); ?></h2>

	<?php if ( empty( $modules ) ) : ?>
		<p><?php esc_html_e( 'No modules registered yet.', 'community-kit' ); ?></p>
	<?php else : ?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Module', 'community-kit' ); ?></th>
					<th><?php esc_html_e( 'Version', 'community-kit' ); ?></th>
					<th><?php esc_html_e( 'Status', 'community-kit' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $modules as $module ) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html( $module['label'] ); ?></strong>
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
