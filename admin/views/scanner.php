<?php
/**
 * Scanner admin view.
 *
 * @package CommunityKit
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$scanner = new CK_Scanner();
$results = $scanner->run();
?>
<div class="wrap ck-scanner">
	<h1><?php esc_html_e( 'Compatibility Scanner', 'community-kit' ); ?></h1>
	<p><?php esc_html_e( 'Check that your environment meets Community Kit requirements.', 'community-kit' ); ?></p>

	<table class="widefat striped">
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
						<?php
						$status_labels = array(
							'pass' => __( 'Pass', 'community-kit' ),
							'warn' => __( 'Warning', 'community-kit' ),
							'fail' => __( 'Fail', 'community-kit' ),
						);
						$status_text   = $status_labels[ $result['status'] ] ?? $result['status'];
						?>
						<span class="ck-status ck-status--<?php echo esc_attr( $result['status'] ); ?>">
							<?php echo esc_html( $status_text ); ?>
						</span>
					</td>
					<td><?php echo esc_html( $result['message'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
