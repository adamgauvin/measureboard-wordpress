<?php
/**
 * REST API endpoints — allows MeasureBoard to pull data from this WordPress site.
 * Authenticated by site key/secret pair generated on activation.
 *
 * @package MeasureBoard
 */

defined( 'ABSPATH' ) || exit;

class MeasureBoard_REST {

    /**
     * Register REST routes under /measureboard/v1/
     */
    public static function register_routes() {
        $namespace = 'measureboard/v1';

        register_rest_route( $namespace, '/content', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'get_content' ),
            'permission_callback' => array( __CLASS__, 'check_auth' ),
        ) );

        register_rest_route( $namespace, '/site-health', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'get_site_health' ),
            'permission_callback' => array( __CLASS__, 'check_auth' ),
        ) );

        register_rest_route( $namespace, '/geo', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'get_geo_data' ),
            'permission_callback' => array( __CLASS__, 'check_auth' ),
        ) );

        if ( MeasureBoard::has_woocommerce() ) {
            register_rest_route( $namespace, '/woocommerce', array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_woocommerce' ),
                'permission_callback' => array( __CLASS__, 'check_auth' ),
            ) );
        }
    }

    /**
     * Authenticate requests using the site key/secret pair.
     */
    public static function check_auth( $request ) {
        $key    = $request->get_header( 'X-MB-Site-Key' );
        $secret = $request->get_header( 'X-MB-Site-Secret' );

        if ( empty( $key ) || empty( $secret ) ) {
            return new WP_Error( 'unauthorized', 'Missing authentication headers.', array( 'status' => 401 ) );
        }

        $stored_key    = get_option( 'measureboard_site_key' );
        $stored_secret = get_option( 'measureboard_site_secret' );

        if ( ! hash_equals( $stored_key, $key ) || ! hash_equals( $stored_secret, $secret ) ) {
            return new WP_Error( 'forbidden', 'Invalid credentials.', array( 'status' => 403 ) );
        }

        return true;
    }

    /**
     * GET /measureboard/v1/content — all published content with SEO metadata.
     */
    public static function get_content() {
        return rest_ensure_response( array(
            'pages' => MeasureBoard_Content::get_all_content(),
        ) );
    }

    /**
     * GET /measureboard/v1/site-health — WordPress environment info.
     */
    public static function get_site_health() {
        $active_plugins = get_option( 'active_plugins', array() );
        $theme          = wp_get_theme();

        return rest_ensure_response( array(
            'wordpressVersion'   => get_bloginfo( 'version' ),
            'phpVersion'         => phpversion(),
            'siteUrl'            => get_site_url(),
            'homeUrl'            => get_home_url(),
            'siteName'           => get_bloginfo( 'name' ),
            'siteDescription'    => get_bloginfo( 'description' ),
            'themeName'          => $theme->get( 'Name' ),
            'themeVersion'       => $theme->get( 'Version' ),
            'activePluginCount'  => count( $active_plugins ),
            'hasWoocommerce'     => MeasureBoard::has_woocommerce(),
            'hasYoast'           => is_plugin_active( 'wordpress-seo/wp-seo.php' ),
            'hasRankMath'        => is_plugin_active( 'seo-by-rank-math/rank-math.php' ),
            'hasAioseo'          => is_plugin_active( 'all-in-one-seo-pack/all_in_one_seo_pack.php' ),
            'permalinkStructure' => get_option( 'permalink_structure' ),
            'postCount'          => (int) wp_count_posts()->publish,
            'pageCount'          => (int) wp_count_posts( 'page' )->publish,
            'hasSsl'             => is_ssl(),
        ) );
    }

    /**
     * GET /measureboard/v1/geo — GEO readiness data.
     */
    public static function get_geo_data() {
        return rest_ensure_response( array(
            'agentReadiness'      => MeasureBoard_Geo::get_agent_readiness(),
            'llmsTxtStatus'       => get_option( 'measureboard_llms_txt_status', 'none' ),
            'llmsTxtContent'      => get_option( 'measureboard_llms_txt_content', '' ),
            'jsonldRecommendations' => MeasureBoard_Geo::get_jsonld_recommendations(),
            'robotsRecommendations' => MeasureBoard_Geo::get_robots_recommendations(),
        ) );
    }

    /**
     * GET /measureboard/v1/woocommerce — WooCommerce data.
     */
    public static function get_woocommerce() {
        if ( ! MeasureBoard::has_woocommerce() ) {
            return new WP_Error( 'no_woocommerce', 'WooCommerce is not active.', array( 'status' => 404 ) );
        }

        $wc = new MeasureBoard_WooCommerce();
        return rest_ensure_response( $wc->get_summary() );
    }
}
