<?php
/**
 * Admin settings page and dashboard widget.
 *
 * @package MeasureBoard
 */

defined( 'ABSPATH' ) || exit;

class MeasureBoard_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_measureboard_connect', array( $this, 'ajax_connect' ) );
        add_action( 'wp_ajax_measureboard_disconnect', array( $this, 'ajax_disconnect' ) );
        add_action( 'wp_ajax_measureboard_generate_llms', array( $this, 'ajax_generate_llms' ) );
        add_action( 'wp_ajax_measureboard_publish_llms', array( $this, 'ajax_publish_llms' ) );
        add_action( 'wp_ajax_measureboard_unpublish_llms', array( $this, 'ajax_unpublish_llms' ) );
    }

    public function add_menu_page() {
        add_menu_page(
            'MeasureBoard',
            'MeasureBoard',
            'manage_options',
            'measureboard',
            array( $this, 'render_settings_page' ),
            'dashicons-chart-area',
            80
        );
    }

    public function register_settings() {
        register_setting( 'measureboard', 'measureboard_property_id', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
    }

    public function enqueue_assets( $hook ) {
        if ( 'toplevel_page_measureboard' !== $hook && 'index.php' !== $hook ) {
            return;
        }
        wp_enqueue_style(
            'measureboard-admin',
            MEASUREBOARD_PLUGIN_URL . 'admin/measureboard-admin.css',
            array(),
            MEASUREBOARD_VERSION
        );

        // The settings page only needs the JS; the dashboard widget is read-only.
        if ( 'toplevel_page_measureboard' === $hook ) {
            wp_enqueue_script(
                'measureboard-admin',
                MEASUREBOARD_PLUGIN_URL . 'admin/js/measureboard-admin.js',
                array( 'jquery' ),
                MEASUREBOARD_VERSION,
                true
            );
            wp_localize_script(
                'measureboard-admin',
                'measureboardAdmin',
                array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'measureboard_nonce' ),
                )
            );
        }
    }

    /**
     * Main settings page.
     */
    public function render_settings_page() {
        $property_id  = get_option( 'measureboard_property_id' );
        $connected_at = get_option( 'measureboard_connected_at' );
        $site_key     = get_option( 'measureboard_site_key' );
        $is_connected = ! empty( $property_id );

        $llms_status  = get_option( 'measureboard_llms_txt_status', 'none' );
        $llms_content = get_option( 'measureboard_llms_txt_content', '' );
        $llms_gen_at  = get_option( 'measureboard_llms_txt_generated_at', '' );

        $agent_readiness = MeasureBoard_Geo::get_agent_readiness();
        $robots_recs     = MeasureBoard_Geo::get_robots_recommendations();
        $jsonld_recs     = MeasureBoard_Geo::get_jsonld_recommendations();

        include MEASUREBOARD_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * Dashboard widget showing agent readiness score.
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'measureboard_readiness',
            '📊 MeasureBoard - AI Agent Readiness',
            array( $this, 'render_dashboard_widget' )
        );
    }

    public function render_dashboard_widget() {
        $readiness   = MeasureBoard_Geo::get_agent_readiness();
        $property_id = get_option( 'measureboard_property_id' );
        ?>
        <div class="mb-widget">
            <div class="mb-score-row">
                <div class="mb-score-circle mb-score-<?php echo $readiness['score'] >= 75 ? 'green' : ( $readiness['score'] >= 50 ? 'yellow' : 'red' ); ?>">
                    <?php echo esc_html( $readiness['score'] ); ?>
                </div>
                <div>
                    <strong>Agent Readiness Score</strong><br>
                    <span class="mb-subtext"><?php echo esc_html( $readiness['passed'] ); ?>/<?php echo esc_html( $readiness['total'] ); ?> checks passed</span>
                </div>
            </div>
            <ul class="mb-checks">
                <?php foreach ( $readiness['checks'] as $check ) : ?>
                    <li class="<?php echo $check['passed'] ? 'mb-pass' : 'mb-fail'; ?>">
                        <?php echo $check['passed'] ? '&#10003;' : '&#10007;'; ?>
                        <?php echo esc_html( $check['name'] ); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=measureboard' ) ); ?>" class="button">View Full Report</a>
                <?php if ( $property_id ) : ?>
                    <a href="https://www.measureboard.com/dashboard/<?php echo esc_attr( $property_id ); ?>/geo" target="_blank" class="button button-primary" style="margin-left: 8px;">Open MeasureBoard</a>
                <?php endif; ?>
            </p>
        </div>
        <?php
    }

    /**
     * AJAX: Connect to MeasureBoard property.
     */
    public function ajax_connect() {
        check_ajax_referer( 'measureboard_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $property_id = sanitize_text_field( wp_unslash( $_POST['property_id'] ?? '' ) );
        if ( empty( $property_id ) ) {
            wp_send_json_error( 'Property ID is required. Find it in your MeasureBoard dashboard URL.' );
        }

        $api    = new MeasureBoard_API();
        $result = $api->connect( $property_id );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        // Trigger initial sync
        $api->push_site_health();
        $api->push_content_summary();

        if ( MeasureBoard::has_woocommerce() ) {
            $api->push_woocommerce_summary();
        }

        wp_send_json_success( array( 'message' => 'Connected successfully!' ) );
    }

    /**
     * AJAX: Disconnect from MeasureBoard.
     */
    public function ajax_disconnect() {
        check_ajax_referer( 'measureboard_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $api = new MeasureBoard_API();
        $api->disconnect();

        wp_send_json_success( array( 'message' => 'Disconnected.' ) );
    }

    /**
     * AJAX: Generate llms.txt draft.
     */
    public function ajax_generate_llms() {
        check_ajax_referer( 'measureboard_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $content = MeasureBoard_Geo::save_llms_txt_draft();
        wp_send_json_success( array( 'content' => $content ) );
    }

    /**
     * AJAX: Publish llms.txt (make it live at /llms.txt).
     */
    public function ajax_publish_llms() {
        check_ajax_referer( 'measureboard_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $result = MeasureBoard_Geo::publish_llms_txt();
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( array( 'message' => 'llms.txt is now live at ' . get_home_url() . '/llms.txt' ) );
    }

    /**
     * AJAX: Unpublish llms.txt.
     */
    public function ajax_unpublish_llms() {
        check_ajax_referer( 'measureboard_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        MeasureBoard_Geo::unpublish_llms_txt();
        wp_send_json_success( array( 'message' => 'llms.txt unpublished.' ) );
    }
}

new MeasureBoard_Admin();
