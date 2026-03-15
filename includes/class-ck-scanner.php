<?php
/**
 * Compatibility scanner.
 *
 * Runs environment and configuration checks to ensure the site meets
 * Community Kit requirements.
 *
 * @package CommunityKit
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CK_Scanner
 *
 * Stub implementation of the compatibility scanner.
 */
class CK_Scanner {

	/**
	 * Run all compatibility checks.
	 *
	 * Each result in the returned array contains:
	 *   - id      (string) Unique check identifier.
	 *   - label   (string) Human-readable check name.
	 *   - status  (string) One of: pass, warn, fail.
	 *   - message (string) Explanation of the result.
	 *
	 * @return array<int, array{id: string, label: string, status: string, message: string}>
	 */
	public function run(): array {
		$results = array();

		$results[] = $this->check_php_version();
		$results[] = $this->check_wp_version();
		$results[] = $this->check_ssl();

		/**
		 * Filter the scanner results.
		 *
		 * Modules can append their own checks via this filter.
		 *
		 * @param array $results Array of check-result arrays.
		 */
		return apply_filters( 'community_kit_scanner_results', $results );
	}

	/**
	 * Check the PHP version meets the minimum requirement.
	 *
	 * @return array{id: string, label: string, status: string, message: string}
	 */
	private function check_php_version(): array {
		$required = '8.1';

		if ( version_compare( PHP_VERSION, $required, '>=' ) ) {
			return array(
				'id'      => 'php_version',
				'label'   => __( 'PHP Version', 'community-kit' ),
				'status'  => 'pass',
				/* translators: %s: current PHP version */
				'message' => sprintf( __( 'PHP %s detected.', 'community-kit' ), PHP_VERSION ),
			);
		}

		return array(
			'id'      => 'php_version',
			'label'   => __( 'PHP Version', 'community-kit' ),
			'status'  => 'fail',
			/* translators: 1: required PHP version, 2: current PHP version */
			'message' => sprintf( __( 'PHP %1$s or higher is required. You are running %2$s.', 'community-kit' ), $required, PHP_VERSION ),
		);
	}

	/**
	 * Check the WordPress version meets the minimum requirement.
	 *
	 * @return array{id: string, label: string, status: string, message: string}
	 */
	private function check_wp_version(): array {
		$required = '6.3';

		if ( version_compare( get_bloginfo( 'version' ), $required, '>=' ) ) {
			return array(
				'id'      => 'wp_version',
				'label'   => __( 'WordPress Version', 'community-kit' ),
				'status'  => 'pass',
				/* translators: %s: current WordPress version */
				'message' => sprintf( __( 'WordPress %s detected.', 'community-kit' ), get_bloginfo( 'version' ) ),
			);
		}

		return array(
			'id'      => 'wp_version',
			'label'   => __( 'WordPress Version', 'community-kit' ),
			'status'  => 'fail',
			/* translators: 1: required WP version, 2: current WP version */
			'message' => sprintf( __( 'WordPress %1$s or higher is required. You are running %2$s.', 'community-kit' ), $required, get_bloginfo( 'version' ) ),
		);
	}

	/**
	 * Check whether the site is served over HTTPS.
	 *
	 * @return array{id: string, label: string, status: string, message: string}
	 */
	private function check_ssl(): array {
		if ( is_ssl() ) {
			return array(
				'id'      => 'ssl',
				'label'   => __( 'SSL / HTTPS', 'community-kit' ),
				'status'  => 'pass',
				'message' => __( 'Site is served over HTTPS.', 'community-kit' ),
			);
		}

		return array(
			'id'      => 'ssl',
			'label'   => __( 'SSL / HTTPS', 'community-kit' ),
			'status'  => 'warn',
			'message' => __( 'HTTPS is recommended for production sites.', 'community-kit' ),
		);
	}
}
