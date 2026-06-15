<?php
/**
 * OW Google Reviews Deactivator
 *
 * @package OW_Google_Reviews
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OWGR_Deactivator
 *
 * Cleans up scheduled cron jobs on deactivation.
 */
class OWGR_Deactivator {

	/**
	 * Deactivate the plugin.
	 */
	public static function deactivate() {
		$timestamp = wp_next_scheduled( 'owgr_daily_sync' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'owgr_daily_sync' );
		}
	}
}
