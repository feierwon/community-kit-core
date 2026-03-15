<?php
/**
 * Module registry.
 *
 * Manages registration and status tracking for Community Kit modules,
 * and stores module-registered compatibility checks.
 *
 * @package CommunityKit
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CK_Registry
 *
 * Stores and retrieves registered modules and compatibility checks.
 */
class CK_Registry {

	/**
	 * Singleton instance.
	 *
	 * @var CK_Registry|null
	 */
	private static ?CK_Registry $instance = null;

	/**
	 * Registered modules keyed by module ID.
	 *
	 * @var array<string, array>
	 */
	private array $modules = array();

	/**
	 * Registered compatibility checks keyed by check ID.
	 *
	 * Each entry contains:
	 *   - id        (string)   Unique check identifier.
	 *   - label     (string)   Human-readable check name.
	 *   - callback  (callable) Function that returns a check-result array.
	 *   - module_id (string)   The module that registered this check.
	 *
	 * @var array<string, array>
	 */
	private array $compatibility_checks = array();

	/**
	 * Return the singleton instance.
	 *
	 * @return CK_Registry
	 */
	public static function get_instance(): CK_Registry {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to enforce singleton pattern.
	 */
	private function __construct() {
		// Intentionally left empty.
	}

	/**
	 * Register a module.
	 *
	 * Required args: id, name, version, min_core, description.
	 * If the module requires a higher Core version than CK_VERSION the
	 * module is NOT registered and a warning is logged.
	 *
	 * @param array $args {
	 *     Module arguments.
	 *
	 *     @type string $id          Required. Unique module identifier.
	 *     @type string $name        Required. Human-readable name.
	 *     @type string $version     Required. Module version string.
	 *     @type string $min_core    Required. Minimum Community Kit Core version.
	 *     @type string $description Required. Short description of the module.
	 *     @type string $file        Optional. Absolute path to the module bootstrap file.
	 * }
	 * @return bool True on success, false on failure.
	 */
	public function register_module( array $args ): bool {
		$required_keys = array( 'id', 'name', 'version', 'min_core', 'description' );

		foreach ( $required_keys as $key ) {
			if ( empty( $args[ $key ] ) ) {
				community_kit_log(
					sprintf( 'Module registration failed: missing required field "%s".', $key ),
					'warning'
				);
				return false;
			}
		}

		$module_id = sanitize_key( $args['id'] );

		// Prevent duplicate registration.
		if ( isset( $this->modules[ $module_id ] ) ) {
			community_kit_log( "Module '{$module_id}' is already registered.", 'warning' );
			return false;
		}

		// Check minimum Core version requirement.
		if ( version_compare( CK_VERSION, $args['min_core'], '<' ) ) {
			community_kit_log(
				sprintf(
					"Module '%s' requires Community Kit Core %s but %s is installed. Skipping registration.",
					$module_id,
					$args['min_core'],
					CK_VERSION
				),
				'warning'
			);
			return false;
		}

		$this->modules[ $module_id ] = array(
			'id'          => $module_id,
			'name'        => sanitize_text_field( $args['name'] ),
			'description' => sanitize_text_field( $args['description'] ),
			'version'     => sanitize_text_field( $args['version'] ),
			'min_core'    => sanitize_text_field( $args['min_core'] ),
			'file'        => isset( $args['file'] ) ? $args['file'] : '',
			'active'      => true,
		);

		community_kit_log( "Module '{$module_id}' registered successfully." );
		return true;
	}

	/**
	 * Get all registered modules.
	 *
	 * @return array<string, array>
	 */
	public function get_modules(): array {
		return $this->modules;
	}

	/**
	 * Check whether a module is active.
	 *
	 * @param string $module_id The module identifier.
	 * @return bool True if the module is registered and active.
	 */
	public function is_module_active( string $module_id ): bool {
		$module_id = sanitize_key( $module_id );

		if ( ! isset( $this->modules[ $module_id ] ) ) {
			return false;
		}

		return ! empty( $this->modules[ $module_id ]['active'] );
	}

	/**
	 * Register a compatibility check.
	 *
	 * @param array $args {
	 *     Check arguments.
	 *
	 *     @type string   $id        Required. Unique check identifier.
	 *     @type string   $label     Required. Human-readable check name.
	 *     @type callable $callback  Required. Function returning a check-result array
	 *                               with keys: id, label, status, message.
	 *     @type string   $module_id Required. The module registering this check.
	 * }
	 * @return bool True on success, false on failure.
	 */
	public function register_compatibility_check( array $args ): bool {
		$required_keys = array( 'id', 'label', 'callback', 'module_id' );

		foreach ( $required_keys as $key ) {
			if ( empty( $args[ $key ] ) ) {
				community_kit_log(
					sprintf( 'Compatibility check registration failed: missing required field "%s".', $key ),
					'warning'
				);
				return false;
			}
		}

		if ( ! is_callable( $args['callback'] ) ) {
			community_kit_log(
				sprintf( 'Compatibility check "%s" callback is not callable.', $args['id'] ),
				'warning'
			);
			return false;
		}

		$check_id = sanitize_key( $args['id'] );

		if ( isset( $this->compatibility_checks[ $check_id ] ) ) {
			community_kit_log( "Compatibility check '{$check_id}' is already registered.", 'warning' );
			return false;
		}

		$this->compatibility_checks[ $check_id ] = array(
			'id'        => $check_id,
			'label'     => sanitize_text_field( $args['label'] ),
			'callback'  => $args['callback'],
			'module_id' => sanitize_key( $args['module_id'] ),
		);

		return true;
	}

	/**
	 * Get all registered compatibility checks.
	 *
	 * @return array<string, array>
	 */
	public function get_compatibility_checks(): array {
		return $this->compatibility_checks;
	}
}
