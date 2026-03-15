<?php
/**
 * Compatibility scanner.
 *
 * Runs environment and configuration checks to ensure the site meets
 * Community Kit requirements, plus any module-registered checks.
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
 * Runs core and module compatibility checks and returns grouped results.
 */
class CK_Scanner {

	/**
	 * Run all compatibility checks and return grouped results.
	 *
	 * Returns an associative array keyed by group label. The "Core Checks"
	 * group always comes first, followed by one group per module that has
	 * registered checks.
	 *
	 * @return array<string, array<int, array{id: string, label: string, status: string, message: string}>>
	 */
	public function run(): array {
		$grouped = array();

		// Core checks.
		$grouped['Core Checks'] = array(
			$this->check_php_version(),
			$this->check_wp_version(),
			$this->check_mysql_version(),
			$this->check_curl(),
			$this->check_ssl(),
		);

		// Module-registered checks.
		$registry = CK_Registry::get_instance();
		$checks   = $registry->get_compatibility_checks();
		$modules  = $registry->get_modules();

		foreach ( $checks as $check ) {
			$module_id   = $check['module_id'];
			$group_label = isset( $modules[ $module_id ] )
				? $modules[ $module_id ]['name']
				: $module_id;

			if ( ! isset( $grouped[ $group_label ] ) ) {
				$grouped[ $group_label ] = array();
			}

			$result = call_user_func( $check['callback'] );

			// Ensure the callback returned a valid result array.
			if ( is_array( $result ) && isset( $result['id'], $result['status'], $result['message'] ) ) {
				$grouped[ $group_label ][] = $result;
			}
		}

		/**
		 * Filter the grouped scanner results.
		 *
		 * @param array $grouped Grouped check-result arrays.
		 */
		return apply_filters( 'community_kit_scanner_results', $grouped );
	}

	/**
	 * Run a single check by its ID.
	 *
	 * Looks for the check in core checks first, then module-registered checks.
	 *
	 * @param string $check_id The check identifier.
	 * @return array{id: string, label: string, status: string, message: string}|null Result or null if not found.
	 */
	public function run_single( string $check_id ): ?array {
		// Core check methods mapped by ID.
		$core_checks = array(
			'php_version'   => 'check_php_version',
			'wp_version'    => 'check_wp_version',
			'mysql_version' => 'check_mysql_version',
			'curl'          => 'check_curl',
			'ssl'           => 'check_ssl',
		);

		if ( isset( $core_checks[ $check_id ] ) ) {
			return $this->{$core_checks[ $check_id ]}();
		}

		// Check module-registered checks.
		$checks = CK_Registry::get_instance()->get_compatibility_checks();

		if ( isset( $checks[ $check_id ] ) ) {
			$result = call_user_func( $checks[ $check_id ]['callback'] );
			if ( is_array( $result ) && isset( $result['id'], $result['status'], $result['message'] ) ) {
				return $result;
			}
		}

		return null;
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
	 * Check the MySQL / MariaDB version.
	 *
	 * Warns if below MySQL 5.7 or MariaDB 10.3.
	 *
	 * @return array{id: string, label: string, status: string, message: string}
	 */
	private function check_mysql_version(): array {
		global $wpdb;

		$db_version  = $wpdb->db_version();
		$server_info = $wpdb->db_server_info();
		$is_mariadb  = str_contains( strtolower( $server_info ), 'mariadb' );

		if ( $is_mariadb ) {
			// MariaDB version string often looks like "5.5.5-10.6.12-MariaDB".
			// Extract the actual MariaDB version.
			$mariadb_version = $db_version;
			if ( preg_match( '/(\d+\.\d+\.\d+)-MariaDB/i', $server_info, $matches ) ) {
				$mariadb_version = $matches[1];
			}

			if ( version_compare( $mariadb_version, '10.3', '>=' ) ) {
				return array(
					'id'      => 'mysql_version',
					'label'   => __( 'Database Version', 'community-kit' ),
					'status'  => 'pass',
					/* translators: %s: MariaDB version */
					'message' => sprintf( __( 'MariaDB %s detected.', 'community-kit' ), $mariadb_version ),
				);
			}

			return array(
				'id'      => 'mysql_version',
				'label'   => __( 'Database Version', 'community-kit' ),
				'status'  => 'warn',
				/* translators: %s: MariaDB version */
				'message' => sprintf( __( 'MariaDB 10.3 or higher is recommended. You are running %s.', 'community-kit' ), $mariadb_version ),
			);
		}

		// Standard MySQL.
		if ( version_compare( $db_version, '5.7', '>=' ) ) {
			return array(
				'id'      => 'mysql_version',
				'label'   => __( 'Database Version', 'community-kit' ),
				'status'  => 'pass',
				/* translators: %s: MySQL version */
				'message' => sprintf( __( 'MySQL %s detected.', 'community-kit' ), $db_version ),
			);
		}

		return array(
			'id'      => 'mysql_version',
			'label'   => __( 'Database Version', 'community-kit' ),
			'status'  => 'warn',
			/* translators: %s: MySQL version */
			'message' => sprintf( __( 'MySQL 5.7 or higher is recommended. You are running %s.', 'community-kit' ), $db_version ),
		);
	}

	/**
	 * Check whether the cURL extension is loaded.
	 *
	 * @return array{id: string, label: string, status: string, message: string}
	 */
	private function check_curl(): array {
		if ( function_exists( 'curl_version' ) ) {
			$version = curl_version();
			return array(
				'id'      => 'curl',
				'label'   => __( 'cURL Extension', 'community-kit' ),
				'status'  => 'pass',
				/* translators: %s: cURL version */
				'message' => sprintf( __( 'cURL %s is available.', 'community-kit' ), $version['version'] ),
			);
		}

		return array(
			'id'      => 'curl',
			'label'   => __( 'cURL Extension', 'community-kit' ),
			'status'  => 'fail',
			'message' => __( 'The cURL PHP extension is required but not enabled.', 'community-kit' ),
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
