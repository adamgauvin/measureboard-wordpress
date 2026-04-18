<?php
/**
 * Plugin Name: MeasureBoard – AI SEO & Analytics
 * Plugin URI: https://www.measureboard.com/tools
 * Description: Free AI-powered SEO analytics, GEO optimization, AI agent readiness checks, AI rank tracking, and WooCommerce sales attribution. Connect to MeasureBoard for AI-powered insights.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: MeasureBoard
 * Author URI: https://www.measureboard.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: measureboard-ai-seo-analytics
 * Domain Path: /languages
 *
 * @package MeasureBoard
 */

defined( 'ABSPATH' ) || exit;

define( 'MEASUREBOARD_VERSION', '1.0.0' );
define( 'MEASUREBOARD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MEASUREBOARD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MEASUREBOARD_API_BASE', 'https://www.measureboard.com/api' );

// Core includes
require_once MEASUREBOARD_PLUGIN_DIR . 'includes/class-measureboard.php';
require_once MEASUREBOARD_PLUGIN_DIR . 'includes/class-measureboard-api.php';
require_once MEASUREBOARD_PLUGIN_DIR . 'includes/class-measureboard-content.php';
require_once MEASUREBOARD_PLUGIN_DIR . 'includes/class-measureboard-geo.php';
require_once MEASUREBOARD_PLUGIN_DIR . 'includes/class-measureboard-rest.php';

// Admin
if ( is_admin() ) {
    require_once MEASUREBOARD_PLUGIN_DIR . 'admin/class-measureboard-admin.php';
}

// WooCommerce integration (loaded only when WooCommerce is active)
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
    require_once MEASUREBOARD_PLUGIN_DIR . 'includes/class-measureboard-woocommerce.php';
}

/**
 * Initialize the plugin.
 */
function measureboard_init() {
    $plugin = MeasureBoard::instance();
    $plugin->init();
}
add_action( 'plugins_loaded', 'measureboard_init' );

/**
 * Activation hook — generate API keys, set defaults.
 */
function measureboard_activate() {
    // Generate a unique site key pair for authenticating with MeasureBoard
    if ( ! get_option( 'measureboard_site_key' ) ) {
        update_option( 'measureboard_site_key', wp_generate_password( 32, false ) );
    }
    if ( ! get_option( 'measureboard_site_secret' ) ) {
        update_option( 'measureboard_site_secret', wp_generate_password( 64, false ) );
    }

    // Flush rewrite rules for the llms.txt endpoint
    MeasureBoard_Geo::register_rewrite_rules();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'measureboard_activate' );

/**
 * Deactivation hook.
 */
function measureboard_deactivate() {
    flush_rewrite_rules();
    wp_clear_scheduled_hook( 'measureboard_daily_sync' );
}
register_deactivation_hook( __FILE__, 'measureboard_deactivate' );
