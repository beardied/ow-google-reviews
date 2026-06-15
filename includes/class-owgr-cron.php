<?php
/**
 * OW Google Reviews Cron
 *
 * Handles daily background sync of reviews.
 *
 * @package OW_Google_Reviews
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OWGR_Cron
 */
class OWGR_Cron {

	/**
	 * Singleton instance.
	 *
	 * @var OWGR_Cron|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return OWGR_Cron
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
		add_action( 'owgr_daily_sync', array( $this, 'sync_reviews' ) );
		add_filter( 'cron_schedules', array( $this, 'register_cron_intervals' ) );
	}

	/**
	 * Register custom cron intervals.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public function register_cron_intervals( $schedules ) {
		$schedules['owgr_daily'] = array(
			'interval' => 86400,
			'display'  => __( 'Once Daily', 'ow-google-reviews' ),
		);
		return $schedules;
	}

	/**
	 * Sync reviews from Google Business Profile API.
	 *
	 * @return int|WP_Error Number of reviews synced or WP_Error.
	 */
	public function sync_reviews() {
		$api         = OWGR_Google_API::instance();
		$db          = OWGR_Database::instance();
		$account_id  = get_option( 'owgr_account_id', '' );
		$location_id = get_option( 'owgr_location_id', '' );

		if ( ! $api->is_connected() || empty( $account_id ) || empty( $location_id ) ) {
			return new WP_Error( 'not_connected', __( 'Plugin is not fully connected to Google.', 'ow-google-reviews' ) );
		}

		$response = $api->fetch_reviews( $account_id, $location_id );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$reviews = $response['reviews'] ?? array();
		$synced  = 0;

		foreach ( $reviews as $review ) {
			$result = $db->save_review( $review );
			if ( false !== $result ) {
				$synced++;
			}
		}

		update_option( 'owgr_last_sync_count', $synced );
		update_option( 'owgr_last_sync_time', current_time( 'mysql' ) );

		return $synced;
	}
}
