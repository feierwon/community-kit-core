<?php
/**
 * Admin interface.
 *
 * Registers admin menus, enqueues assets, and renders admin pages.
 *
 * @package CommunityKit
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CK_Admin
 *
 * Handles all wp-admin functionality for Community Kit.
 */
class CK_Admin {

	/**
	 * Singleton instance.
	 *
	 * @var CK_Admin|null
	 */
	private static ?CK_Admin $instance = null;

	/**
	 * Return the singleton instance.
	 *
	 * @return CK_Admin
	 */
	public static function get_instance(): CK_Admin {
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
	 * Initialise admin hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register admin menu pages.
	 *
	 * @return void
	 */
	public function register_menus(): void {
		add_menu_page(
			__( 'Community Kit', 'community-kit' ),
			__( 'Community Kit', 'community-kit' ),
			'manage_options',
			'community-kit',
			array( $this, 'render_dashboard' ),
			'dashicons-groups',
			30
		);

		add_submenu_page(
			'community-kit',
			__( 'Scanner', 'community-kit' ),
			__( 'Scanner', 'community-kit' ),
			'manage_options',
			'community-kit-scanner',
			array( $this, 'render_scanner' )
		);
	}

	/**
	 * Enqueue admin CSS and JS on plugin pages only.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		// Only load on our own pages.
		if ( ! str_contains( $hook_suffix, 'community-kit' ) ) {
			return;
		}

		wp_enqueue_style(
			'ck-admin',
			CK_URI . 'assets/css/ck-admin.css',
			array(),
			CK_VERSION
		);

		wp_enqueue_script(
			'ck-admin',
			CK_URI . 'assets/js/ck-admin.js',
			array(),
			CK_VERSION,
			true
		);
	}

	/**
	 * Render the dashboard page.
	 *
	 * @return void
	 */
	public function render_dashboard(): void {
		include CK_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * Render the scanner page.
	 *
	 * @return void
	 */
	public function render_scanner(): void {
		include CK_DIR . 'admin/views/scanner.php';
	}
}
