<?php
/**
 * Plugin Name: OW Google Reviews
 * Plugin URI:  https://orangewidow.com
 * Description: Sync Google Business reviews to a local database and display them via Gutenberg blocks.
 * Version:     1.0.0
 * Author:      OrangeWidow
 * Author URI:  https://orangewidow.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: ow-google-reviews
 * Domain Path: /languages
 *
 * @package OW_Google_Reviews
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'OWGR_VERSION', '1.0.0' );
define( 'OWGR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OWGR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OWGR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'OWGR_TABLE', 'owgr_reviews' );

/**
 * Class OW_Google_Reviews
 *
 * Main plugin class. Loads dependencies and hooks.
 */
class OW_Google_Reviews {

	/**
	 * Singleton instance.
	 *
	 * @var OW_Google_Reviews|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return OW_Google_Reviews
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
		$this->load_dependencies();
		$this->setup_hooks();
	}

	/**
	 * Load plugin files.
	 */
	private function load_dependencies() {
		require_once OWGR_PLUGIN_DIR . 'includes/class-owgr-database.php';
		require_once OWGR_PLUGIN_DIR . 'includes/class-owgr-google-api.php';
		require_once OWGR_PLUGIN_DIR . 'includes/class-owgr-admin.php';
		require_once OWGR_PLUGIN_DIR . 'includes/class-owgr-cron.php';
		require_once OWGR_PLUGIN_DIR . 'includes/class-owgr-blocks.php';
	}

	/**
	 * Setup WordPress hooks.
	 */
	private function setup_hooks() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize components after plugins are loaded.
	 */
	public function init() {
		OWGR_Database::instance();
		OWGR_Google_API::instance();
		OWGR_Admin::instance();
		OWGR_Cron::instance();
		OWGR_Blocks::instance();
	}
}

/**
 * Activation hook.
 */
function owgr_activate_plugin() {
	require_once OWGR_PLUGIN_DIR . 'includes/class-owgr-activator.php';
	OWGR_Activator::activate();
}
register_activation_hook( __FILE__, 'owgr_activate_plugin' );

/**
 * Deactivation hook.
 */
function owgr_deactivate_plugin() {
	require_once OWGR_PLUGIN_DIR . 'includes/class-owgr-deactivator.php';
	OWGR_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'owgr_deactivate_plugin' );

// Boot the plugin.
OW_Google_Reviews::instance();
