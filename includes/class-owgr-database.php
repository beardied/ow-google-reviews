<?php
/**
 * OW Google Reviews Database
 *
 * @package OW_Google_Reviews
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OWGR_Database
 *
 * Handles all database operations for stored reviews.
 */
class OWGR_Database {

	/**
	 * Singleton instance.
	 *
	 * @var OWGR_Database|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return OWGR_Database
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
	private function __construct() {}

	/**
	 * Get the full table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . OWGR_TABLE;
	}

	/**
	 * Insert or update a review.
	 *
	 * @param array $review Review data from Google API.
	 * @return int|false Rows affected or false on failure.
	 */
	public function save_review( $review ) {
		global $wpdb;

		$table = self::table_name();

		$review_id = sanitize_text_field( $review['reviewId'] ?? $review['review_id'] ?? '' );
		if ( empty( $review_id ) ) {
			return false;
		}

		$reviewer       = $review['reviewer'] ?? array();
		$name           = isset( $reviewer['displayName'] ) ? sanitize_text_field( $reviewer['displayName'] ) : '';
		$photo_url      = isset( $reviewer['profilePhotoUrl'] ) ? esc_url_raw( $reviewer['profilePhotoUrl'] ) : '';
		$star_rating    = $this->normalize_star_rating( $review['starRating'] ?? $review['star_rating'] ?? 0 );
		$comment        = isset( $review['comment'] ) ? wp_kses_post( $review['comment'] ) : '';
		$create_time    = isset( $review['createTime'] ) ? $this->format_datetime( $review['createTime'] ) : current_time( 'mysql' );
		$update_time    = isset( $review['updateTime'] ) ? $this->format_datetime( $review['updateTime'] ) : null;
		$reply          = $review['reviewReply'] ?? array();
		$reply_comment  = isset( $reply['comment'] ) ? wp_kses_post( $reply['comment'] ) : '';
		$reply_updated  = isset( $reply['updateTime'] ) ? $this->format_datetime( $reply['updateTime'] ) : null;

		// Use INSERT ... ON DUPLICATE KEY UPDATE to avoid duplicates.
		$sql = "INSERT INTO {$table} (
			review_id, reviewer_name, reviewer_photo_url, star_rating, comment,
			create_time, update_time, reply_comment, reply_update_time, raw_json, synced_at
		) VALUES (
			%s, %s, %s, %d, %s, %s, %s, %s, %s, %s, %s
		) ON DUPLICATE KEY UPDATE
			reviewer_name = VALUES(reviewer_name),
			reviewer_photo_url = VALUES(reviewer_photo_url),
			star_rating = VALUES(star_rating),
			comment = VALUES(comment),
			create_time = VALUES(create_time),
			update_time = VALUES(update_time),
			reply_comment = VALUES(reply_comment),
			reply_update_time = VALUES(reply_update_time),
			raw_json = VALUES(raw_json),
			synced_at = VALUES(synced_at)";

		$prepared = $wpdb->prepare(
			$sql,
			$review_id,
			$name,
			$photo_url,
			$star_rating,
			$comment,
			$create_time,
			$update_time,
			$reply_comment,
			$reply_updated,
			wp_json_encode( $review ),
			current_time( 'mysql' )
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->query( $prepared );
	}

	/**
	 * Get reviews from the database.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function get_reviews( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'limit'    => 0,
			'offset'   => 0,
			'orderby'  => 'create_time',
			'order'    => 'DESC',
			'rating'   => 0,
		);
		$args = wp_parse_args( $args, $defaults );

		$table = self::table_name();
		$sql   = "SELECT * FROM {$table} WHERE 1=1";
		$where = array();

		if ( ! empty( $args['rating'] ) ) {
			$where[] = $wpdb->prepare( ' AND star_rating = %d', absint( $args['rating'] ) );
		}

		$orderby = in_array( strtolower( $args['orderby'] ), array( 'create_time', 'update_time', 'synced_at', 'star_rating' ), true )
			? sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] )
			: 'create_time DESC';

		$sql .= implode( '', $where );
		$sql .= ' ORDER BY ' . $orderby;

		if ( ! empty( $args['limit'] ) ) {
			$sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', absint( $args['limit'] ), absint( $args['offset'] ) );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Count total reviews.
	 *
	 * @return int
	 */
	public function count_reviews() {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	/**
	 * Get the most recent sync timestamp.
	 *
	 * @return string|null
	 */
	public function get_last_sync() {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var( "SELECT MAX(synced_at) FROM {$table}" );
	}

	/**
	 * Delete all stored reviews.
	 *
	 * @return int|false
	 */
	public function delete_all_reviews() {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->query( "DELETE FROM {$table}" );
	}

	/**
	 * Format an RFC3339 timestamp to MySQL datetime.
	 *
	 * @param string $timestamp RFC3339 timestamp.
	 * @return string|null
	 */
	/**
	 * Normalize a Google star rating enum or integer to a 1–5 value.
	 *
	 * @param string|int $rating Star rating from Google API.
	 * @return int
	 */
	private function normalize_star_rating( $rating ) {
		$map = array(
			'ONE'   => 1,
			'TWO'   => 2,
			'THREE' => 3,
			'FOUR'  => 4,
			'FIVE'  => 5,
		);

		if ( is_string( $rating ) && isset( $map[ strtoupper( $rating ) ] ) ) {
			return $map[ strtoupper( $rating ) ];
		}

		return absint( $rating );
	}

	private function format_datetime( $timestamp ) {
		if ( empty( $timestamp ) ) {
			return null;
		}

		$dt = DateTime::createFromFormat( DateTime::RFC3339, $timestamp, new DateTimeZone( 'UTC' ) );
		if ( ! $dt ) {
			$dt = date_create( $timestamp, new DateTimeZone( 'UTC' ) );
		}

		if ( ! $dt ) {
			return null;
		}

		return $dt->format( 'Y-m-d H:i:s' );
	}
}
