<?php
/**
 * Plugin Name: Community Kit
 * Description: A modular WordPress framework for nonprofits and community organizations.
 * Version:     0.1.0
 * Requires at least: 6.3
 * Requires PHP: 8.1
 * Author:      Feierwon Media
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: community-kit
 * Domain Path: /languages
 *
 * @package CommunityKit
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 *
 * @var string
 */
define( 'CK_VERSION', '0.1.0' );

/**
 * Plugin directory path (with trailing slash).
 *
 * @var string
 */
define( 'CK_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL (with trailing slash).
 *
 * @var string
 */
define( 'CK_URI', plugin_dir_url( __FILE__ ) );

/*
|--------------------------------------------------------------------------
| Autoload include files
|--------------------------------------------------------------------------
*/

require_once CK_DIR . 'includes/class-ck-core.php';
require_once CK_DIR . 'includes/class-ck-registry.php';
require_once CK_DIR . 'includes/class-ck-scanner.php';
require_once CK_DIR . 'includes/class-ck-license.php';
require_once CK_DIR . 'includes/class-ck-updater.php';
require_once CK_DIR . 'admin/class-ck-admin.php';

/*
|--------------------------------------------------------------------------
| Activation & Deactivation Hooks
|--------------------------------------------------------------------------
*/

/**
 * Runs on plugin activation.
 *
 * @return void
 */
function community_kit_activate(): void {
	// Set a flag so we can flush rewrite rules on next load.
	update_option( 'community_kit_flush_rewrite', true );
}
register_activation_hook( __FILE__, 'community_kit_activate' );

/**
 * Runs on plugin deactivation.
 *
 * @return void
 */
function community_kit_deactivate(): void {
	// Clean up transient data.
	delete_transient( 'community_kit_scanner_results' );
}
register_deactivation_hook( __FILE__, 'community_kit_deactivate' );

/*
|--------------------------------------------------------------------------
| Boot the plugin
|--------------------------------------------------------------------------
*/

/**
 * Initialise the plugin on plugins_loaded so extensions can hook in.
 *
 * @return void
 */
function community_kit_init(): void {
	CK_Core::get_instance()->init();
}
add_action( 'plugins_loaded', 'community_kit_init' );

/*
|--------------------------------------------------------------------------
| Global helper functions
|--------------------------------------------------------------------------
*/

/**
 * Register a module with Community Kit.
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
function community_kit_register_module( array $args ): bool {
	return CK_Registry::get_instance()->register_module( $args );
}

/**
 * Check whether a module is currently active.
 *
 * @param string $module_id The module identifier to check.
 * @return bool
 */
function community_kit_is_module_active( string $module_id ): bool {
	return CK_Registry::get_instance()->is_module_active( $module_id );
}

/**
 * Register a compatibility check for the scanner.
 *
 * @param array $args {
 *     Check arguments.
 *
 *     @type string   $id        Required. Unique check identifier.
 *     @type string   $label     Required. Human-readable check name.
 *     @type callable $callback  Required. Function returning an array with keys:
 *                               id, label, status (pass/warn/fail), message.
 *     @type string   $module_id Required. The module registering this check.
 * }
 * @return bool True on success, false on failure.
 */
function community_kit_register_compatibility_check( array $args ): bool {
	return CK_Registry::get_instance()->register_compatibility_check( $args );
}

/**
 * Log a message to the Community Kit debug log.
 *
 * @param string $message The message to log.
 * @param string $level   Log level: debug, info, warning, error. Default 'debug'.
 * @return void
 */
function community_kit_log( string $message, string $level = 'debug' ): void {
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
		return;
	}

	$timestamp = current_time( 'Y-m-d H:i:s' );
	$entry     = sprintf( '[%s] [Community Kit] [%s] %s', $timestamp, strtoupper( $level ), $message );

	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( $entry );
}
