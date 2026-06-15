<?php
/**
 * OW Google Reviews Google API
 *
 * Handles OAuth and Google My Business API calls.
 *
 * @package OW_Google_Reviews
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OWGR_Google_API
 */
class OWGR_Google_API {

	/**
	 * Singleton instance.
	 *
	 * @var OWGR_Google_API|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return OWGR_Google_API
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
		add_action( 'admin_init', array( $this, 'handle_oauth_callback' ) );
	}

	/**
	 * Get the OAuth redirect URI.
	 *
	 * @return string
	 */
	public static function get_redirect_uri() {
		return admin_url( 'admin.php?page=ow-google-reviews&owgr_action=oauth_callback' );
	}

	/**
	 * Get stored client ID.
	 *
	 * @return string
	 */
	public function get_client_id() {
		return sanitize_text_field( get_option( 'owgr_client_id', '' ) );
	}

	/**
	 * Get stored client secret.
	 *
	 * @return string
	 */
	public function get_client_secret() {
		return sanitize_text_field( get_option( 'owgr_client_secret', '' ) );
	}

	/**
	 * Get stored access token.
	 *
	 * @return string
	 */
	public function get_access_token() {
		return sanitize_text_field( get_option( 'owgr_access_token', '' ) );
	}

	/**
	 * Get stored refresh token.
	 *
	 * @return string
	 */
	public function get_refresh_token() {
		return sanitize_text_field( get_option( 'owgr_refresh_token', '' ) );
	}

	/**
	 * Check if plugin is fully connected.
	 *
	 * @return bool
	 */
	public function is_connected() {
		return (
			! empty( $this->get_client_id() ) &&
			! empty( $this->get_client_secret() ) &&
			! empty( $this->get_access_token() ) &&
			! empty( $this->get_refresh_token() ) &&
			! empty( get_option( 'owgr_account_id' ) ) &&
			! empty( get_option( 'owgr_location_id' ) )
		);
	}

	/**
	 * Build the Google OAuth authorization URL.
	 *
	 * @return string|false
	 */
	public function get_auth_url() {
		$client_id = $this->get_client_id();
		if ( empty( $client_id ) ) {
			return false;
		}

		$params = array(
			'client_id'     => $client_id,
			'redirect_uri'  => self::get_redirect_uri(),
			'response_type' => 'code',
			'scope'         => 'https://www.googleapis.com/auth/business.manage',
			'access_type'   => 'offline',
			'prompt'        => 'consent',
			'state'         => wp_create_nonce( 'owgr_oauth_state' ),
		);

		return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( $params );
	}

	/**
	 * Handle OAuth callback.
	 */
	public function handle_oauth_callback() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_GET['page'] ) || 'ow-google-reviews' !== $_GET['page'] ) {
			return;
		}

		if ( ! isset( $_GET['owgr_action'] ) || 'oauth_callback' !== $_GET['owgr_action'] ) {
			return;
		}

		if ( ! isset( $_GET['code'] ) ) {
			add_settings_error(
				'owgr_messages',
				'owgr_oauth_error',
				__( 'Authorization code missing from Google.', 'ow-google-reviews' ),
				'error'
			);
			return;
		}

		if ( ! isset( $_GET['state'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['state'] ) ), 'owgr_oauth_state' ) ) {
			add_settings_error(
				'owgr_messages',
				'owgr_oauth_error',
				__( 'OAuth state verification failed. Please try again.', 'ow-google-reviews' ),
				'error'
			);
			return;
		}

		$code = sanitize_text_field( wp_unslash( $_GET['code'] ) );

		$response = $this->exchange_code_for_tokens( $code );

		if ( is_wp_error( $response ) ) {
			add_settings_error(
				'owgr_messages',
				'owgr_oauth_error',
				$response->get_error_message(),
				'error'
			);
			return;
		}

		if ( empty( $response['refresh_token'] ) ) {
			add_settings_error(
				'owgr_messages',
				'owgr_oauth_error',
				__( 'Google did not return a refresh token. Please revoke plugin access in your Google Account and reconnect.', 'ow-google-reviews' ),
				'error'
			);
			return;
		}

		update_option( 'owgr_access_token', sanitize_text_field( $response['access_token'] ) );
		update_option( 'owgr_refresh_token', sanitize_text_field( $response['refresh_token'] ) );
		update_option( 'owgr_token_expires_at', time() + absint( $response['expires_in'] ?? 3600 ) );
		update_option( 'owgr_connected', '1' );

		add_settings_error(
			'owgr_messages',
			'owgr_oauth_success',
			__( 'Google account connected successfully. Now select your Business Profile account and location.', 'ow-google-reviews' ),
			'success'
		);
	}

	/**
	 * Exchange authorization code for tokens.
	 *
	 * @param string $code Authorization code.
	 * @return array|WP_Error
	 */
	public function exchange_code_for_tokens( $code ) {
		$client_id     = $this->get_client_id();
		$client_secret = $this->get_client_secret();

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			return new WP_Error( 'missing_credentials', __( 'Client ID or Secret missing.', 'ow-google-reviews' ) );
		}

		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'body' => array(
					'code'          => $code,
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'redirect_uri'  => self::get_redirect_uri(),
					'grant_type'    => 'authorization_code',
				),
			)
		);

		return $this->parse_json_response( $response );
	}

	/**
	 * Refresh the access token using the refresh token.
	 *
	 * @return string|WP_Error New access token or error.
	 */
	public function refresh_access_token() {
		$refresh_token = $this->get_refresh_token();
		$client_id     = $this->get_client_id();
		$client_secret = $this->get_client_secret();

		if ( empty( $refresh_token ) || empty( $client_id ) || empty( $client_secret ) ) {
			return new WP_Error( 'missing_refresh_credentials', __( 'Missing credentials or refresh token.', 'ow-google-reviews' ) );
		}

		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'body' => array(
					'refresh_token' => $refresh_token,
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'grant_type'    => 'refresh_token',
				),
			)
		);

		$data = $this->parse_json_response( $response );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$access_token = sanitize_text_field( $data['access_token'] );
		$expires_in   = absint( $data['expires_in'] ?? 3600 );

		update_option( 'owgr_access_token', $access_token );
		update_option( 'owgr_token_expires_at', time() + $expires_in );

		// Google does not always return a new refresh token; keep the existing one if absent.
		if ( ! empty( $data['refresh_token'] ) ) {
			update_option( 'owgr_refresh_token', sanitize_text_field( $data['refresh_token'] ) );
		}

		return $access_token;
	}

	/**
	 * Ensure a valid access token is available.
	 *
	 * @return string|WP_Error
	 */
	public function ensure_valid_token() {
		$expires_at = (int) get_option( 'owgr_token_expires_at', 0 );

		if ( empty( $this->get_access_token() ) || time() >= ( $expires_at - 300 ) ) {
			return $this->refresh_access_token();
		}

		return $this->get_access_token();
	}

	/**
	 * Make an authenticated request to a Google API endpoint.
	 *
	 * @param string $url API endpoint URL.
	 * @param string $method HTTP method.
	 * @param array  $body Optional request body.
	 * @return array|WP_Error
	 */
	public function api_request( $url, $method = 'GET', $body = array() ) {
		$token = $this->ensure_valid_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$args = array(
			'method'  => strtoupper( $method ),
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
		);

		if ( ! empty( $body ) && in_array( strtoupper( $method ), array( 'POST', 'PATCH', 'PUT' ), true ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );
		return $this->parse_json_response( $response );
	}

	/**
	 * List Google Business accounts.
	 *
	 * @return array|WP_Error
	 */
	public function list_accounts() {
		return $this->api_request( 'https://mybusiness.googleapis.com/v4/accounts' );
	}

	/**
	 * List locations for an account.
	 *
	 * @param string $account_id Account ID or resource name.
	 * @return array|WP_Error
	 */
	public function list_locations( $account_id ) {
		$account_id = sanitize_text_field( $account_id );
		if ( 0 !== strpos( $account_id, 'accounts/' ) ) {
			$account_id = "accounts/{$account_id}";
		}
		$url = "https://mybusiness.googleapis.com/v4/{$account_id}/locations";
		return $this->api_request( $url );
	}

	/**
	 * Fetch reviews for a location.
	 *
	 * @param string $account_id Account ID or full resource name.
	 * @param string $location_id Location ID or full resource name.
	 * @return array|WP_Error
	 */
	public function fetch_reviews( $account_id, $location_id ) {
		$account_id  = sanitize_text_field( $account_id );
		$location_id = sanitize_text_field( $location_id );
		$location    = $this->normalize_location_path( $account_id, $location_id );
		$url         = "https://mybusiness.googleapis.com/v4/{$location}/reviews";

		$all_reviews = array();
		$page_token  = '';

		// Google paginates reviews; loop through every page.
		while ( true ) {
			$request_url = $url;
			if ( ! empty( $page_token ) ) {
				$request_url = add_query_arg( 'pageToken', $page_token, $request_url );
			}

			$response = $this->api_request( $request_url );
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			if ( ! empty( $response['reviews'] ) ) {
				$all_reviews = array_merge( $all_reviews, $response['reviews'] );
			}

			if ( empty( $response['nextPageToken'] ) ) {
				break;
			}

			$page_token = sanitize_text_field( $response['nextPageToken'] );
		}

		return array( 'reviews' => $all_reviews );
	}

	/**
	 * Normalize account and location identifiers into a full resource path.
	 *
	 * @param string $account_id  Account ID or resource name.
	 * @param string $location_id Location ID or resource name.
	 * @return string
	 */
	private function normalize_location_path( $account_id, $location_id ) {
		$account_id  = sanitize_text_field( $account_id );
		$location_id = sanitize_text_field( $location_id );

		if ( 0 === strpos( $location_id, 'accounts/' ) && false !== strpos( $location_id, '/locations/' ) ) {
			return $location_id;
		}

		if ( 0 === strpos( $account_id, 'accounts/' ) ) {
			$account_id = substr( $account_id, 9 );
		}

		if ( 0 === strpos( $location_id, 'locations/' ) ) {
			$location_id = substr( $location_id, 10 );
		}

		return "accounts/{$account_id}/locations/{$location_id}";
	}

	/**
	 * Parse a WordPress HTTP response as JSON.
	 *
	 * @param array|WP_Error $response WP HTTP response.
	 * @return array|WP_Error
	 */
	private function parse_json_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'json_error', __( 'Invalid JSON response from Google.', 'ow-google-reviews' ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			$message = isset( $data['error_description'] ) ? $data['error_description'] : ( $data['error']['message'] ?? __( 'Google API error.', 'ow-google-reviews' ) );
			return new WP_Error( 'google_api_error', $message, array( 'status' => $code, 'response' => $data ) );
		}

		return $data;
	}
}
