<?php
/**
 * Scanner admin view.
 *
 * Displays grouped compatibility check results with a manual re-scan button.
 *
 * @package CommunityKit
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$scanner     = new CK_Scanner();
$scan_groups = $scanner->run();

$status_labels = array(
	'pass' => __( 'Pass', 'community-kit' ),
	'warn' => __( 'Warning', 'community-kit' ),
	'fail' => __( 'Fail', 'community-kit' ),
);
?>
<div class="wrap ck-scanner">
	<h1><?php esc_html_e( 'Compatibility Scanner', 'community-kit' ); ?></h1>
	<p><?php esc_html_e( 'Check that your environment meets Community Kit requirements.', 'community-kit' ); ?></p>

	<p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=community-kit-scanner' ) ); ?>" class="button button-primary">
			<?php esc_html_e( 'Run Scan Now', 'community-kit' ); ?>
		</a>
	</p>

	<?php foreach ( $scan_groups as $group_label => $results ) : ?>
		<h2 class="ck-group-heading"><?php echo esc_html( $group_label ); ?></h2>
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
