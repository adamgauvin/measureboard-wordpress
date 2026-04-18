<?php
/**
 * MeasureBoard API client — pushes site data to MeasureBoard.
 *
 * @package MeasureBoard
 */

defined( 'ABSPATH' ) || exit;

class MeasureBoard_API {

    /**
     * Send authenticated request to MeasureBoard API.
     */
    public function request( $endpoint, $data = array(), $method = 'POST' ) {
        $property_id = get_option( 'measureboard_property_id' );
        $site_key    = get_option( 'measureboard_site_key' );
        $site_secret = get_option( 'measureboard_site_secret' );

        if ( ! $property_id || ! $site_key ) {
            return new WP_Error( 'not_connected', 'MeasureBoard is not connected.' );
        }

        $url = MEASUREBOARD_API_BASE . $endpoint;

        $response = wp_remote_request( $url, array(
            'method'  => $method,
            'timeout' => 30,
            'headers' => array(
                'Content-Type'         => 'application/json',
                'X-MB-Property'        => $property_id,
                'X-MB-Site-Key'        => $site_key,
                'X-MB-Site-Secret'     => $site_secret,
                'X-MB-Plugin-Version'  => MEASUREBOARD_VERSION,
            ),
            'body' => wp_json_encode( $data ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 400 ) {
            return new WP_Error( 'api_error', $body['error'] ?? "API error ($code)" );
        }

        return $body;
    }

    /**
     * Push site health data (WordPress version, plugins, theme, PHP, etc.)
     */
    public function push_site_health() {
        $active_plugins = get_option( 'active_plugins', array() );
        $theme          = wp_get_theme();

        $data = array(
            'wordpress_version' => get_bloginfo( 'version' ),
            'php_version'       => phpversion(),
            'site_url'          => get_site_url(),
            'home_url'          => get_home_url(),
            'site_name'         => get_bloginfo( 'name' ),
            'site_description'  => get_bloginfo( 'description' ),
            'theme_name'        => $theme->get( 'Name' ),
            'theme_version'     => $theme->get( 'Version' ),
            'active_plugins'    => array_map( function( $p ) {
                $data = get_plugin_data( WP_PLUGIN_DIR . '/' . $p, false, false );
                return array(
                    'slug'    => dirname( $p ),
                    'name'    => $data['Name'] ?? $p,
                    'version' => $data['Version'] ?? '',
                );
            }, $active_plugins ),
            'has_woocommerce'   => MeasureBoard::has_woocommerce(),
            'is_multisite'      => is_multisite(),
            'permalink_structure' => get_option( 'permalink_structure' ),
            'timezone'          => wp_timezone_string(),
            'language'          => get_locale(),
            'post_count'        => (int) wp_count_posts()->publish,
            'page_count'        => (int) wp_count_posts( 'page' )->publish,
            'has_ssl'           => is_ssl(),
        );

        return $this->request( '/wordpress/site-health', $data );
    }

    /**
     * Push content summary (all published posts + pages with SEO-relevant fields).
     */
    public function push_content_summary() {
        $content = MeasureBoard_Content::get_all_content();
        return $this->request( '/wordpress/content', array( 'pages' => $content ) );
    }

    /**
     * Push WooCommerce summary (orders, products, revenue).
     */
    public function push_woocommerce_summary() {
        if ( ! MeasureBoard::has_woocommerce() ) {
            return new WP_Error( 'no_woocommerce', 'WooCommerce is not active.' );
        }

        $wc = new MeasureBoard_WooCommerce();
        $data = $wc->get_summary();
        return $this->request( '/wordpress/woocommerce', $data );
    }

    /**
     * Test the connection to MeasureBoard.
     */
    public function test_connection() {
        return $this->request( '/wordpress/ping', array(), 'GET' );
    }

    /**
     * Register this WordPress site with a MeasureBoard property.
     */
    public function connect( $property_id ) {
        $site_key    = get_option( 'measureboard_site_key' );
        $site_secret = get_option( 'measureboard_site_secret' );

        $response = wp_remote_post( MEASUREBOARD_API_BASE . '/wordpress/connect', array(
            'timeout' => 15,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( array(
                'propertyId' => $property_id,
                'siteUrl'    => get_site_url(),
                'homeUrl'    => get_home_url(),
                'siteName'   => get_bloginfo( 'name' ),
                'siteKey'    => $site_key,
                'siteSecret' => $site_secret,
                'wpVersion'  => get_bloginfo( 'version' ),
                'hasWoo'     => MeasureBoard::has_woocommerce(),
                'pluginVersion' => MEASUREBOARD_VERSION,
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 400 ) {
            return new WP_Error( 'connect_failed', $body['error'] ?? 'Connection failed.' );
        }

        update_option( 'measureboard_property_id', sanitize_text_field( $property_id ) );
        update_option( 'measureboard_connected_at', current_time( 'mysql' ) );

        return $body;
    }

    /**
     * Disconnect from MeasureBoard.
     */
    public function disconnect() {
        $this->request( '/wordpress/disconnect', array(), 'DELETE' );
        delete_option( 'measureboard_property_id' );
        delete_option( 'measureboard_connected_at' );
        wp_clear_scheduled_hook( 'measureboard_daily_sync' );
    }
}
