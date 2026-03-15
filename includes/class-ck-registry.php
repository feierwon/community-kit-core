<?php
/**
 * Module registry.
 *
 * Manages registration and status tracking for Community Kit modules.
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
 * Stores and retrieves registered modules.
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
	 * @param array $args {
	 *     Module arguments.
	 *
	 *     @type string $id          Required. Unique module identifier.
	 *     @type string $label       Required. Human-readable label.
	 *     @type string $description Optional. Short description of the module.
	 *     @type string $version     Optional. Module version string.
	 *     @type string $file        Optional. Absolute path to the module bootstrap file.
	 * }
	 * @return bool True on success, false on failure.
	 */
	public function register_module( array $args ): bool {
		// Validate required fields.
		if ( empty( $args['id'] ) || empty( $args['label'] ) ) {
			community_kit_log( 'Module registration failed: missing id or label.', 'warning' );
			return false;
		}

		$module_id = sanitize_key( $args['id'] );

		// Prevent duplicate registration.
		if ( isset( $this->modules[ $module_id ] ) ) {
			community_kit_log( "Module '{$module_id}' is already registered.", 'warning' );
			return false;
		}

		$this->modules[ $module_id ] = array(
			'id'          => $module_id,
			'label'       => sanitize_text_field( $args['label'] ),
			'description' => isset( $args['description'] ) ? sanitize_text_field( $args['description'] ) : '',
			'version'     => isset( $args['version'] ) ? sanitize_text_field( $args['version'] ) : '0.0.0',
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
}
