<?php
/**
 * OW Google Reviews Admin
 *
 * @package OW_Google_Reviews
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OWGR_Admin
 */
class OWGR_Admin {

	/**
	 * Singleton instance.
	 *
	 * @var OWGR_Admin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return OWGR_Admin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'handle_manual_sync' ) );
		add_action( 'admin_init', array( $this, 'handle_account_selection' ) );
		add_action( 'admin_init', array( $this, 'handle_disconnect' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_owgr_fetch_accounts', array( $this, 'ajax_fetch_accounts' ) );
		add_action( 'wp_ajax_owgr_fetch_locations', array( $this, 'ajax_fetch_locations' ) );
	}

	/**
	 * Add admin menu page.
	 */
	public function add_admin_menu() {
		add_management_page(
			__( 'OW Google Reviews', 'ow-google-reviews' ),
			__( 'Google Reviews', 'ow-google-reviews' ),
			'manage_options',
			'ow-google-reviews',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting( 'owgr_settings_group', 'owgr_client_id', 'sanitize_text_field' );
		register_setting( 'owgr_settings_group', 'owgr_client_secret', 'sanitize_text_field' );
		register_setting( 'owgr_settings_group', 'owgr_account_id', 'sanitize_text_field' );
		register_setting( 'owgr_settings_group', 'owgr_location_id', 'sanitize_text_field' );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'tools_page_ow-google-reviews' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'owgr-admin-css',
			OWGR_PLUGIN_URL . 'admin/css/owgr-admin.css',
			array(),
			OWGR_VERSION
		);

		wp_enqueue_script(
			'owgr-admin-js',
			OWGR_PLUGIN_URL . 'admin/js/owgr-admin.js',
			array( 'jquery' ),
			OWGR_VERSION,
			true
		);

		wp_localize_script(
			'owgr-admin-js',
			'owgr_admin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'owgr_admin_nonce' ),
				'strings'  => array(
					'loading' => __( 'Loading...', 'ow-google-reviews' ),
					'error'   => __( 'An error occurred.', 'ow-google-reviews' ),
				),
			)
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$api            = OWGR_Google_API::instance();
		$db             = OWGR_Database::instance();
		$client_id      = $api->get_client_id();
		$client_secret  = $api->get_client_secret();
		$connected      = $api->is_connected();
		$account_id     = get_option( 'owgr_account_id', '' );
		$location_id    = get_option( 'owgr_location_id', '' );
		$auth_url       = $api->get_auth_url();
		$review_count   = $db->count_reviews();
		$last_sync      = $db->get_last_sync();

		include OWGR_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	/**
	 * Handle manual sync request.
	 */
	public function handle_manual_sync() {
		if ( ! isset( $_GET['page'] ) || 'ow-google-reviews' !== $_GET['page'] ) {
			return;
		}

		if ( ! isset( $_GET['owgr_action'] ) || 'sync_now' !== $_GET['owgr_action'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'owgr_sync_now' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'ow-google-reviews' ) );
		}

		$result = OWGR_Cron::instance()->sync_reviews();

		if ( is_wp_error( $result ) ) {
			add_settings_error(
				'owgr_messages',
				'owgr_sync_error',
				$result->get_error_message(),
				'error'
			);
		} else {
			add_settings_error(
				'owgr_messages',
				'owgr_sync_success',
				sprintf(
					/* translators: %d: number of reviews synced */
					__( 'Sync complete. %d reviews processed.', 'ow-google-reviews' ),
					$result
				),
				'success'
			);
		}
	}

	/**
	 * Handle account/location manual save.
	 */
	public function handle_account_selection() {
		if ( ! isset( $_POST['owgr_save_account_location'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'owgr_save_account_location' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'ow-google-reviews' ) );
		}

		update_option( 'owgr_account_id', sanitize_text_field( wp_unslash( $_POST['owgr_account_id'] ?? '' ) ) );
		update_option( 'owgr_location_id', sanitize_text_field( wp_unslash( $_POST['owgr_location_id'] ?? '' ) ) );

		add_settings_error(
			'owgr_messages',
			'owgr_account_saved',
			__( 'Account and location saved.', 'ow-google-reviews' ),
			'success'
		);
	}

	/**
	 * Handle disconnect / reset request.
	 */
	public function handle_disconnect() {
		if ( ! isset( $_GET['page'] ) || 'ow-google-reviews' !== $_GET['page'] ) {
			return;
		}

		if ( ! isset( $_GET['owgr_action'] ) || 'disconnect' !== $_GET['owgr_action'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'owgr_disconnect' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'ow-google-reviews' ) );
		}

		delete_option( 'owgr_access_token' );
		delete_option( 'owgr_refresh_token' );
		delete_option( 'owgr_token_expires_at' );
		delete_option( 'owgr_account_id' );
		delete_option( 'owgr_location_id' );
		delete_option( 'owgr_connected' );
		delete_option( 'owgr_last_sync_count' );
		delete_option( 'owgr_last_sync_time' );

		add_settings_error(
			'owgr_messages',
			'owgr_disconnected',
			__( 'Connection reset. You can reconnect using the same credentials or enter new ones.', 'ow-google-reviews' ),
			'success'
		);
	}

	/**
	 * AJAX handler to fetch Google Business accounts.
	 */
	public function ajax_fetch_accounts() {
		check_ajax_referer( 'owgr_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'ow-google-reviews' ), 403 );
		}

		$api  = OWGR_Google_API::instance();
		$data = $api->list_accounts();

		if ( is_wp_error( $data ) ) {
			wp_send_json_error( $data->get_error_message() );
		}

		$accounts = array();
		if ( ! empty( $data['accounts'] ) ) {
			foreach ( $data['accounts'] as $account ) {
				$accounts[] = array(
					'id'   => $account['name'],
					'name' => $account['accountName'] ?? $account['name'],
				);
			}
		}

		wp_send_json_success( $accounts );
	}

	/**
	 * AJAX handler to fetch locations for an account.
	 */
	public function ajax_fetch_locations() {
		check_ajax_referer( 'owgr_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'ow-google-reviews' ), 403 );
		}

		$account_id = sanitize_text_field( wp_unslash( $_POST['account_id'] ?? '' ) );
		if ( empty( $account_id ) ) {
			wp_send_json_error( __( 'Account ID is required.', 'ow-google-reviews' ) );
		}

		$api  = OWGR_Google_API::instance();
		$data = $api->list_locations( $account_id );

		if ( is_wp_error( $data ) ) {
			wp_send_json_error( $data->get_error_message() );
		}

		$locations = array();
		if ( ! empty( $data['locations'] ) ) {
			foreach ( $data['locations'] as $location ) {
				$locations[] = array(
					'id'   => $location['name'],
					'name' => $location['locationName'] ?? $location['name'],
				);
			}
		}

		wp_send_json_success( $locations );
	}
}
