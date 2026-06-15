<?php
/**
 * OW Google Reviews Activator
 *
 * @package OW_Google_Reviews
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OWGR_Activator
 *
 * Handles plugin activation tasks such as database table creation.
 */
class OWGR_Activator {

	/**
	 * Activate the plugin.
	 */
	public static function activate() {
		self::create_tables();
		self::schedule_cron();
		self::set_default_options();
	}

	/**
	 * Create custom database tables.
	 */
	public static function create_tables() {
		global $wpdb;

		$table_name      = $wpdb->prefix . OWGR_TABLE;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			review_id varchar(255) NOT NULL,
			reviewer_name varchar(255) NOT NULL,
			reviewer_photo_url varchar(500) DEFAULT NULL,
			star_rating tinyint(1) unsigned NOT NULL DEFAULT 0,
			comment text,
			create_time datetime NOT NULL,
			update_time datetime DEFAULT NULL,
			reply_comment text,
			reply_update_time datetime DEFAULT NULL,
			raw_json longtext,
			synced_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY review_id (review_id),
			KEY create_time (create_time),
			KEY synced_at (synced_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Schedule the daily sync cron.
	 */
	public static function schedule_cron() {
		if ( ! wp_next_scheduled( 'owgr_daily_sync' ) ) {
			// Schedule for 06:00 Asia/Manila time every day.
			$tz      = new DateTimeZone( 'Asia/Manila' );
			$now     = new DateTime( 'now', $tz );
			$tomorrow = new DateTime( 'tomorrow 06:00:00', $tz );

			// If it is already past 06:00 Manila today, schedule for tomorrow.
			$six_am_today = new DateTime( 'today 06:00:00', $tz );
			if ( $now < $six_am_today ) {
				$tomorrow = $six_am_today;
			}

			wp_schedule_event( $tomorrow->getTimestamp(), 'daily', 'owgr_daily_sync' );
		}
	}

	/**
	 * Set default plugin options.
	 */
	public static function set_default_options() {
		$defaults = array(
			'owgr_connected'   => '0',
			'owgr_account_id'  => '',
			'owgr_location_id' => '',
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}
}
