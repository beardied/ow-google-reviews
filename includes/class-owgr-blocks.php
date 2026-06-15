<?php
/**
 * OW Google Reviews Blocks
 *
 * Registers Gutenberg blocks for recent and all reviews.
 *
 * @package OW_Google_Reviews
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OWGR_Blocks
 */
class OWGR_Blocks {

	/**
	 * Singleton instance.
	 *
	 * @var OWGR_Blocks|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return OWGR_Blocks
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
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
	}

	/**
	 * Register block types.
	 */
	public function register_blocks() {
		// Register editor scripts manually so dependencies are explicit.
		wp_register_script(
			'owgr-recent-reviews-editor',
			OWGR_PLUGIN_URL . 'blocks/recent-reviews/recent-reviews.js',
			array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components' ),
			OWGR_VERSION,
			true
		);

		wp_register_script(
			'owgr-all-reviews-editor',
			OWGR_PLUGIN_URL . 'blocks/all-reviews/all-reviews.js',
			array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor' ),
			OWGR_VERSION,
			true
		);

		$blocks = array(
			'recent-reviews' => array( $this, 'render_recent_reviews' ),
			'all-reviews'    => array( $this, 'render_all_reviews' ),
		);

		foreach ( $blocks as $block => $render_callback ) {
			$block_dir = OWGR_PLUGIN_DIR . "blocks/{$block}";
			$metadata  = wp_json_file_decode( "{$block_dir}/block.json", array( 'associative' => true ) );

			if ( ! $metadata ) {
				continue;
			}

			register_block_type(
				$metadata['name'],
				array_merge(
					$metadata,
					array( 'render_callback' => $render_callback )
				)
			);
		}
	}

	/**
	 * Enqueue public-facing block assets.
	 */
	public function enqueue_public_assets() {
		if ( ! has_block( 'ow-google-reviews/recent-reviews' ) && ! has_block( 'ow-google-reviews/all-reviews' ) ) {
			return;
		}

		wp_enqueue_style(
			'owgr-public-css',
			OWGR_PLUGIN_URL . 'public/css/owgr-public.css',
			array(),
			OWGR_VERSION
		);
	}

	/**
	 * Render recent reviews block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string
	 */
	public function render_recent_reviews( $attributes, $content, $block ) {
		$count        = absint( $attributes['count'] ?? 3 );
		$show_button  = ! empty( $attributes['showViewAllButton'] );
		$button_url   = isset( $attributes['buttonUrl'] ) ? esc_url( $attributes['buttonUrl'] ) : '';
		$button_text  = isset( $attributes['buttonText'] ) ? sanitize_text_field( $attributes['buttonText'] ) : __( 'View all reviews', 'ow-google-reviews' );

		$db      = OWGR_Database::instance();
		$reviews = $db->get_reviews(
			array(
				'limit' => $count,
			)
		);

		if ( empty( $reviews ) ) {
			return '<p class="owgr-no-reviews">' . esc_html__( 'No reviews yet.', 'ow-google-reviews' ) . '</p>';
		}

		ob_start();
		?>
		<div class="owgr-recent-reviews owgr-reviews">
			<?php foreach ( $reviews as $review ) : ?>
				<?php $this->render_review_card( $review ); ?>
			<?php endforeach; ?>

			<?php if ( $show_button && ! empty( $button_url ) ) : ?>
				<div class="owgr-view-all">
					<a href="<?php echo esc_url( $button_url ); ?>" class="owgr-button">
						<?php echo esc_html( $button_text ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render all reviews block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string
	 */
	public function render_all_reviews( $attributes, $content, $block ) {
		$db      = OWGR_Database::instance();
		$reviews = $db->get_reviews();

		if ( empty( $reviews ) ) {
			return '<p class="owgr-no-reviews">' . esc_html__( 'No reviews yet.', 'ow-google-reviews' ) . '</p>';
		}

		ob_start();
		?>
		<div class="owgr-all-reviews owgr-reviews">
			<?php foreach ( $reviews as $review ) : ?>
				<?php $this->render_review_card( $review ); ?>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a single review card.
	 *
	 * @param array $review Review data.
	 */
	private function render_review_card( $review ) {
		$name      = sanitize_text_field( $review['reviewer_name'] );
		$photo     = ! empty( $review['reviewer_photo_url'] ) ? esc_url( $review['reviewer_photo_url'] ) : '';
		$rating    = absint( $review['star_rating'] );
		$date      = ! empty( $review['create_time'] ) ? mysql2date( get_option( 'date_format' ), $review['create_time'] ) : '';
		$comment   = wpautop( esc_html( $review['comment'] ) );
		$initial   = strtoupper( substr( $name, 0, 1 ) );
		?>
		<div class="owgr-review-card">
			<div class="owgr-review-header">
				<div class="owgr-review-avatar">
					<?php if ( $photo ) : ?>
						<img src="<?php echo esc_url( $photo ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy">
					<?php else : ?>
						<span class="owgr-avatar-initial"><?php echo esc_html( $initial ); ?></span>
					<?php endif; ?>
				</div>
				<div class="owgr-review-meta">
					<strong class="owgr-review-name"><?php echo esc_html( $name ); ?></strong>
					<div class="owgr-review-rating" aria-label="<?php printf( /* translators: %d: star rating */ esc_attr__( '%d out of 5 stars', 'ow-google-reviews' ), esc_attr( $rating ) ); ?>">
						<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
							<?php if ( $i <= $rating ) : ?>
								<span class="owgr-star owgr-star--filled" aria-hidden="true">&#9733;</span>
							<?php else : ?>
								<span class="owgr-star owgr-star--empty" aria-hidden="true">&#9734;</span>
							<?php endif; ?>
						<?php endfor; ?>
					</div>
					<?php if ( $date ) : ?>
						<time class="owgr-review-date" datetime="<?php echo esc_attr( $review['create_time'] ); ?>"><?php echo esc_html( $date ); ?></time>
					<?php endif; ?>
				</div>
			</div>
			<div class="owgr-review-body">
				<?php echo $comment; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?>
			</div>
		</div>
		<?php
	}
}
