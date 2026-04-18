<?php
/**
 * Main plugin class — singleton orchestrator.
 *
 * @package MeasureBoard
 */

defined( 'ABSPATH' ) || exit;

class MeasureBoard {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        // Register REST API endpoints (for MeasureBoard to pull data)
        add_action( 'rest_api_init', array( 'MeasureBoard_REST', 'register_routes' ) );

        // Register GEO features (llms.txt rewrite, JSON-LD output)
        MeasureBoard_Geo::init();

        // Schedule daily data sync to MeasureBoard
        if ( $this->is_connected() && ! wp_next_scheduled( 'measureboard_daily_sync' ) ) {
            wp_schedule_event( time(), 'daily', 'measureboard_daily_sync' );
        }
        add_action( 'measureboard_daily_sync', array( $this, 'daily_sync' ) );

        // Admin bar indicator
        add_action( 'admin_bar_menu', array( $this, 'admin_bar_link' ), 100 );
    }

    /**
     * Check if the plugin is connected to a MeasureBoard property.
     */
    public function is_connected() {
        return ! empty( get_option( 'measureboard_property_id' ) );
    }

    /**
     * Check if WooCommerce is active.
     */
    public static function has_woocommerce() {
        return class_exists( 'WooCommerce' );
    }

    /**
     * Daily sync: push site health + content summary to MeasureBoard.
     */
    public function daily_sync() {
        if ( ! $this->is_connected() ) {
            return;
        }

        $api = new MeasureBoard_API();
        $api->push_site_health();
        $api->push_content_summary();

        if ( self::has_woocommerce() ) {
            $api->push_woocommerce_summary();
        }
    }

    /**
     * Admin bar link to MeasureBoard dashboard.
     */
    public function admin_bar_link( $wp_admin_bar ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $property_id = get_option( 'measureboard_property_id' );
        $wp_admin_bar->add_node( array(
            'id'    => 'measureboard',
            'title' => '📊 MeasureBoard',
            'href'  => $property_id
                ? 'https://www.measureboard.com/dashboard/' . $property_id
                : admin_url( 'admin.php?page=measureboard' ),
            'meta'  => array( 'target' => '_blank' ),
        ) );
    }
}
