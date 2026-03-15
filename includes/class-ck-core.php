<?php
/**
 * Main plugin class.
 *
 * Bootstraps the plugin, registers hooks, and coordinates sub-systems.
 *
 * @package CommunityKit
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CK_Core
 *
 * Singleton entry-point for the Community Kit plugin.
 */
class CK_Core {

	/**
	 * Singleton instance.
	 *
	 * @var CK_Core|null
	 */
	private static ?CK_Core $instance = null;

	/**
	 * Return the singleton instance.
	 *
	 * @return CK_Core
	 */
	public static function get_instance(): CK_Core {
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
	 * Initialise the plugin.
	 *
	 * Called on `plugins_loaded`. Registers hooks and boots sub-systems.
	 *
	 * @return void
	 */
	public function init(): void {
		// Load text domain.
		load_plugin_textdomain( 'community-kit', false, dirname( plugin_basename( CK_DIR . 'community-kit.php' ) ) . '/languages' );

		// Flush rewrite rules once after activation.
		if ( get_option( 'community_kit_flush_rewrite' ) ) {
			flush_rewrite_rules();
			delete_option( 'community_kit_flush_rewrite' );
		}

		// Boot the admin interface.
		if ( is_admin() ) {
			CK_Admin::get_instance()->init();
		}

		/**
		 * Fires after Community Kit core has initialised.
		 *
		 * Use this hook to register modules or extend functionality.
		 */
		do_action( 'community_kit_loaded' );
	}
}
