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
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );
	}

	/**
	 * Register a custom block category for OrangeWidow blocks.
	 *
	 * @param array[]                 $block_categories Array of block categories.
	 * @param WP_Block_Editor_Context $editor_context   The current block editor context.
	 * @return array[]
	 */
	public function register_block_category( $block_categories, $editor_context ) {
		return array_merge(
			$block_categories,
			array(
				array(
					'slug'  => 'orangewidow',
					'title' => __( 'OrangeWidow', 'ow-google-reviews' ),
					'icon'  => null,
				),
			)
		);
	}

	/**
	 * Register block types.
	 */
	public function register_blocks() {
		// Register editor scripts manually so dependencies are explicit.
		// block.json references these handles via "editorScript".
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

			if ( ! file_exists( "{$block_dir}/block.json" ) ) {
				continue;
			}

			// Register the block from its block.json folder. WordPress reads
			// block.json, loads the referenced editorScript/editorStyle, and
			// applies the PHP render_callback supplied here.
			register_block_type(
				$block_dir,
				array( 'render_callback' => $render_callback )
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
	 * Check if test mode is enabled.
	 *
	 * @return bool
	 */
	private function is_test_mode() {
		return '1' === get_option( 'owgr_test_mode', '0' );
	}

	/**
	 * Generate placeholder test reviews.
	 *
	 * @param int $count Number of reviews to generate.
	 * @return array
	 */
	private function get_test_reviews( $count = 10 ) {
		$names    = array(
			__( 'Alex Johnson', 'ow-google-reviews' ),
			__( 'Sam Taylor', 'ow-google-reviews' ),
			__( 'Jordan Lee', 'ow-google-reviews' ),
			__( 'Casey Morgan', 'ow-google-reviews' ),
			__( 'Riley Smith', 'ow-google-reviews' ),
			__( 'Taylor Brown', 'ow-google-reviews' ),
			__( 'Morgan Davis', 'ow-google-reviews' ),
			__( 'Jamie Wilson', 'ow-google-reviews' ),
			__( 'Drew Miller', 'ow-google-reviews' ),
			__( 'Quinn Anderson', 'ow-google-reviews' ),
		);
		$comments = array(
			__( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', 'ow-google-reviews' ),
			__( 'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.', 'ow-google-reviews' ),
			__( 'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.', 'ow-google-reviews' ),
			__( 'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 'ow-google-reviews' ),
			__( 'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium.', 'ow-google-reviews' ),
			__( 'Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit.', 'ow-google-reviews' ),
			__( 'Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit.', 'ow-google-reviews' ),
			__( 'Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur.', 'ow-google-reviews' ),
			__( 'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum.', 'ow-google-reviews' ),
			__( 'Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus.', 'ow-google-reviews' ),
		);

		$reviews = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$reviews[] = array(
				'reviewer_name'       => $names[ $i % count( $names ) ],
				'reviewer_photo_url'  => '',
				'star_rating'         => 5,
				'comment'             => $comments[ $i % count( $comments ) ],
				'create_time'         => gmdate( 'Y-m-d H:i:s', strtotime( "-{$i} days" ) ),
			);
		}

		return $reviews;
	}

	/**
	 * Calculate aggregate rating data from a set of reviews.
	 *
	 * @param array $reviews Reviews array.
	 * @return array
	 */
	private function get_aggregate_data( $reviews ) {
		$count   = count( $reviews );
		$average = 0.0;

		if ( $count > 0 ) {
			$total = 0;
			foreach ( $reviews as $review ) {
				$total += absint( $review['star_rating'] );
			}
			$average = round( $total / $count, 1 );
		}

		return array(
			'count'   => $count,
			'average' => $average,
		);
	}

	/**
	 * Render the aggregate rating summary as human-visible plain text.
	 *
	 * @param int   $count   Number of reviews.
	 * @param float $average Average rating.
	 * @return string
	 */
	private function render_aggregate_summary( $count, $average ) {
		if ( $count < 1 ) {
			return '';
		}

		ob_start();
		?>
		<div class="owgr-aggregate">
			<span class="owgr-aggregate-rating"><?php echo esc_html( number_format_i18n( $average, 1 ) ); ?> / 5</span>
			<span class="owgr-aggregate-stars" aria-hidden="true">
				<?php
				$filled = round( $average );
				for ( $i = 1; $i <= 5; $i++ ) {
					echo $i <= $filled ? '<span class="owgr-star owgr-star--filled">&#9733;</span>' : '<span class="owgr-star owgr-star--empty">&#9734;</span>';
				}
				?>
			</span>
			<span class="owgr-aggregate-count">
				<?php
				printf(
					/* translators: %d: number of reviews */
					esc_html__( 'Based on %d Google reviews', 'ow-google-reviews' ),
					absint( $count )
				);
				?>
			</span>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render JSON-LD schema for the aggregate rating.
	 *
	 * @param int   $count   Number of reviews.
	 * @param float $average Average rating.
	 * @return string
	 */
	private function render_schema( $count, $average ) {
		if ( $count < 1 ) {
			return '';
		}

		$business_name = get_bloginfo( 'name' );
		$maps_url      = esc_url_raw( get_option( 'owgr_maps_url', '' ) );
		$same_as       = array();

		if ( ! empty( $maps_url ) ) {
			$same_as[] = $maps_url;
		}

		$schema = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'LocalBusiness',
			'name'            => $business_name,
			'aggregateRating' => array(
				'@type'       => 'AggregateRating',
				'ratingValue' => number_format( $average, 1 ),
				'reviewCount' => absint( $count ),
				'bestRating'  => '5',
				'worstRating' => '1',
			),
		);

		if ( ! empty( $same_as ) ) {
			$schema['sameAs'] = count( $same_as ) === 1 ? $same_as[0] : $same_as;
		}

		return '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES ) . '</script>';
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
		$test_mode    = $this->is_test_mode();
		$db           = OWGR_Database::instance();

		if ( $test_mode ) {
			$reviews         = $this->get_test_reviews( $count );
			$aggregate       = $this->get_aggregate_data( $reviews );
		} else {
			$reviews         = $db->get_reviews(
				array(
					'limit' => $count,
				)
			);
			$all_reviews     = $db->get_reviews();
			$aggregate       = $this->get_aggregate_data( $all_reviews );
		}

		if ( empty( $reviews ) ) {
			return '<p class="owgr-no-reviews">' . esc_html__( 'No reviews yet.', 'ow-google-reviews' ) . '</p>';
		}

		$summary_html   = $this->render_aggregate_summary( $aggregate['count'], $aggregate['average'] );
		$schema_html    = ! $test_mode ? $this->render_schema( $aggregate['count'], $aggregate['average'] ) : '';

		ob_start();
		echo $schema_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in method.
		?>
		<div class="owgr-recent-reviews owgr-reviews">
			<?php echo $summary_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in method. ?>
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
		$test_mode = $this->is_test_mode();

		if ( $test_mode ) {
			$reviews = $this->get_test_reviews( 10 );
		} else {
			$db      = OWGR_Database::instance();
			$reviews = $db->get_reviews();
		}

		if ( empty( $reviews ) ) {
			return '<p class="owgr-no-reviews">' . esc_html__( 'No reviews yet.', 'ow-google-reviews' ) . '</p>';
		}

		$aggregate      = $this->get_aggregate_data( $reviews );
		$summary_html   = $this->render_aggregate_summary( $aggregate['count'], $aggregate['average'] );
		$schema_html    = ! $test_mode ? $this->render_schema( $aggregate['count'], $aggregate['average'] ) : '';

		ob_start();
		echo $schema_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in method.
		?>
		<div class="owgr-all-reviews owgr-reviews">
			<?php echo $summary_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in method. ?>
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
