<?php
/**
 * Admin interface.
 *
 * Registers admin menus, enqueues assets, handles license key saving,
 * and renders admin pages.
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
	 * The hook suffix for the top-level menu page.
	 *
	 * @var string
	 */
	private string $dashboard_hook = '';

	/**
	 * The hook suffix for the scanner submenu page.
	 *
	 * @var string
	 */
	private string $scanner_hook = '';

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
		add_action( 'admin_init', array( $this, 'handle_license_save' ) );
	}

	/**
	 * Register admin menu pages.
	 *
	 * @return void
	 */
	public function register_menus(): void {
		$this->dashboard_hook = add_menu_page(
			__( 'Community Kit', 'community-kit' ),
			__( 'Community Kit', 'community-kit' ),
			'manage_options',
			'community-kit',
			array( $this, 'render_dashboard' ),
			'dashicons-groups',
			30
		);

		$this->scanner_hook = add_submenu_page(
			'community-kit',
			__( 'Scanner', 'community-kit' ),
			__( 'Scanner', 'community-kit' ),
			'manage_options',
			'community-kit-scanner',
			array( $this, 'render_scanner' )
		);
	}

	/**
	 * Enqueue admin CSS and JS on Community Kit pages only.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		$allowed_hooks = array( $this->dashboard_hook, $this->scanner_hook );

		if ( ! in_array( $hook_suffix, $allowed_hooks, true ) ) {
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
	 * Handle license key form submission.
	 *
	 * @return void
	 */
	public function handle_license_save(): void {
		if ( ! isset( $_POST['ck_license_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ck_license_nonce'] ) ), 'ck_save_license' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'community-kit' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'community-kit' ) );
		}

		$license_key = isset( $_POST['ck_license_key'] )
			? sanitize_text_field( wp_unslash( $_POST['ck_license_key'] ) )
			: '';

		update_option( 'community_kit_license_key', $license_key );

		// Redirect back with a success message.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => 'community-kit',
					'ck-updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
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
