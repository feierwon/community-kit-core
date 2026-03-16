<?php
/**
 * Wizard manager.
 *
 * Handles wizard state persistence via REST API, renders the full-screen
 * wizard shell on admin pages, and detects when a wizard should auto-launch.
 *
 * @package CommunityKit
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CK_Wizard
 *
 * Coordinates the wizard shell and REST API endpoints.
 */
class CK_Wizard {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	private const REST_NAMESPACE = 'community-kit/v1';

	/**
	 * Singleton instance.
	 *
	 * @var CK_Wizard|null
	 */
	private static ?CK_Wizard $instance = null;

	/**
	 * Return the singleton instance.
	 *
	 * @return CK_Wizard
	 */
	public static function get_instance(): CK_Wizard {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		// Intentionally left empty.
	}

	/**
	 * Initialise wizard hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_wizard' ) );
		add_action( 'admin_footer', array( $this, 'render_shell' ) );
	}

	/**
	 * Register REST API routes for wizard state.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Get wizard state.
		register_rest_route( self::REST_NAMESPACE, '/wizard/(?P<module>[a-z0-9-]+)/state', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'rest_get_state' ),
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
			'args'                => array(
				'module' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_key',
				),
			),
		) );

		// Save wizard state.
		register_rest_route( self::REST_NAMESPACE, '/wizard/(?P<module>[a-z0-9-]+)/state', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_save_state' ),
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
			'args'                => array(
				'module' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_key',
				),
			),
		) );

		// Dismiss wizard (skip without completing).
		register_rest_route( self::REST_NAMESPACE, '/wizard/(?P<module>[a-z0-9-]+)/dismiss', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_dismiss' ),
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
			'args'                => array(
				'module' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_key',
				),
			),
		) );

		// Mark wizard complete.
		register_rest_route( self::REST_NAMESPACE, '/wizard/(?P<module>[a-z0-9-]+)/complete', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_complete' ),
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
			'args'                => array(
				'module' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_key',
				),
			),
		) );
	}

	/**
	 * REST callback: get wizard state.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function rest_get_state( WP_REST_Request $request ): WP_REST_Response {
		$module_id = $request->get_param( 'module' );
		$state     = get_option( 'ck_wizard_state_' . $module_id, array() );

		return new WP_REST_Response( array(
			'module' => $module_id,
			'state'  => $state,
		) );
	}

	/**
	 * REST callback: save wizard state.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function rest_save_state( WP_REST_Request $request ): WP_REST_Response {
		$module_id = $request->get_param( 'module' );
		$body      = $request->get_json_params();
		$state     = isset( $body['state'] ) && is_array( $body['state'] ) ? $body['state'] : array();

		// Sanitize all values recursively.
		$state = map_deep( $state, 'sanitize_text_field' );

		update_option( 'ck_wizard_state_' . $module_id, $state );

		return new WP_REST_Response( array(
			'module' => $module_id,
			'state'  => $state,
			'saved'  => true,
		) );
	}

	/**
	 * REST callback: dismiss a wizard so it stops auto-launching.
	 *
	 * The wizard can still be manually launched via ?ck-wizard=module-id.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function rest_dismiss( WP_REST_Request $request ): WP_REST_Response {
		$module_id = $request->get_param( 'module' );
		$dismissed = get_option( 'ck_wizard_dismissed', array() );

		if ( ! in_array( $module_id, $dismissed, true ) ) {
			$dismissed[] = $module_id;
			update_option( 'ck_wizard_dismissed', $dismissed );
		}

		return new WP_REST_Response( array(
			'module'    => $module_id,
			'dismissed' => true,
		) );
	}

	/**
	 * REST callback: mark wizard as complete.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function rest_complete( WP_REST_Request $request ): WP_REST_Response {
		$module_id = $request->get_param( 'module' );
		$wizard    = CK_Registry::get_instance()->get_wizard( $module_id );

		if ( null === $wizard ) {
			return new WP_REST_Response( array( 'error' => 'Wizard not found.' ), 404 );
		}

		update_option( $wizard['option_key'], true );

		// Clean up wizard state and dismissal flag.
		delete_option( 'ck_wizard_state_' . $module_id );

		$dismissed = get_option( 'ck_wizard_dismissed', array() );
		$dismissed = array_filter( $dismissed, function ( $id ) use ( $module_id ) {
			return $id !== $module_id;
		} );
		update_option( 'ck_wizard_dismissed', array_values( $dismissed ) );

		return new WP_REST_Response( array(
			'module'   => $module_id,
			'complete' => true,
		) );
	}

	/**
	 * Determine which wizard (if any) should be active on this admin page,
	 * and enqueue its assets.
	 *
	 * Auto-launch only happens on Community Kit admin pages.
	 * Manual launch via ?ck-wizard=module-id works on any admin page.
	 * Dismissed wizards don't auto-launch until the dismissal is cleared.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function maybe_enqueue_wizard( string $hook_suffix ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$wizards       = CK_Registry::get_instance()->get_wizards();
		$active_wizard = null;
		$dismissed     = get_option( 'ck_wizard_dismissed', array() );

		// Check for manual launch via query param — works on any page.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['ck-wizard'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$requested = sanitize_key( wp_unslash( $_GET['ck-wizard'] ) );
			if ( isset( $wizards[ $requested ] ) ) {
				$active_wizard = $wizards[ $requested ];
			}
		}

		// Auto-launch only on Community Kit admin pages.
		if ( null === $active_wizard ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
			$is_ck_page = str_starts_with( $page, 'community-kit' ) || str_starts_with( $page, 'ck-' );

			if ( $is_ck_page ) {
				foreach ( $wizards as $wizard ) {
					// Skip completed and dismissed wizards.
					if ( get_option( $wizard['option_key'] ) ) {
						continue;
					}
					if ( in_array( $wizard['module'], $dismissed, true ) ) {
						continue;
					}
					$active_wizard = $wizard;
					break;
				}
			}
		}

		if ( null === $active_wizard ) {
			return;
		}

		// Enqueue the wizard shell.
		wp_enqueue_style(
			'ck-wizard-shell',
			CK_URI . 'assets/css/ck-wizard.css',
			array(),
			CK_VERSION
		);

		wp_enqueue_script(
			'ck-wizard-shell',
			CK_URI . 'build/wizard-shell.js',
			array( 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' ),
			CK_VERSION,
			true
		);

		wp_localize_script( 'ck-wizard-shell', 'ckWizardShell', array(
			'activeWizard' => $active_wizard,
			'restBase'     => rest_url( self::REST_NAMESPACE . '/wizard/' ),
			'nonce'        => wp_create_nonce( 'wp_rest' ),
		) );

		// Enqueue the module's own wizard script if registered.
		if ( ! empty( $active_wizard['script'] ) ) {
			// The module is responsible for wp_register_script; we just enqueue it.
			wp_enqueue_script( $active_wizard['script'] );
		}
	}

	/**
	 * Render the wizard shell mount point in the admin footer.
	 *
	 * @return void
	 */
	public function render_shell(): void {
		if ( ! wp_script_is( 'ck-wizard-shell', 'enqueued' ) ) {
			return;
		}
		echo '<div id="ck-wizard-root"></div>';
	}
}
