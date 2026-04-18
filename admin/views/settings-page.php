<?php
/**
 * Admin settings page template.
 *
 * Variables available: $is_connected, $property_id, $connected_at, $site_key,
 * $llms_status, $llms_content, $llms_gen_at, $agent_readiness, $robots_recs, $jsonld_recs
 *
 * @package MeasureBoard
 */

defined( 'ABSPATH' ) || exit;
$nonce = wp_create_nonce( 'measureboard_nonce' );
?>
<div class="wrap mb-wrap">
    <h1>
        <span class="dashicons dashicons-chart-area" style="font-size: 28px; margin-right: 8px; color: #f97316;"></span>
        MeasureBoard
    </h1>
    <p class="mb-subtitle">AI-powered SEO analytics, GEO optimization, and agent readiness.</p>

    <!-- Connection Status -->
    <div class="mb-card">
        <h2>Connection</h2>
        <?php if ( $is_connected ) : ?>
            <div class="mb-status mb-status-connected">
                <span class="dashicons dashicons-yes-alt"></span>
                Connected to property <code><?php echo esc_html( $property_id ); ?></code>
                <?php if ( $connected_at ) : ?>
                    <span class="mb-subtext"> since <?php echo esc_html( $connected_at ); ?></span>
                <?php endif; ?>
            </div>
            <p>
                <a href="https://www.measureboard.com/dashboard/<?php echo esc_attr( $property_id ); ?>" target="_blank" class="button button-primary">Open MeasureBoard Dashboard</a>
                <button id="mb-disconnect" class="button" style="margin-left: 8px;">Disconnect</button>
            </p>
        <?php else : ?>
            <div class="mb-status mb-status-disconnected">
                <span class="dashicons dashicons-warning"></span>
                Not connected. Enter your MeasureBoard Property ID to connect.
            </div>
            <ol class="mb-steps">
                <li>Go to <a href="https://www.measureboard.com/auth/signup" target="_blank">measureboard.com</a> and create a free account (takes &lt;30 seconds)</li>
                <li>Copy your Property ID from the dashboard URL (e.g. <code>2I4AiZXM</code>)</li>
                <li>Paste it below and click Connect</li>
            </ol>
            <div class="mb-connect-form">
                <input type="text" id="mb-property-id" placeholder="Property ID (e.g. 2I4AiZXM)" class="regular-text" />
                <button id="mb-connect" class="button button-primary">Connect</button>
            </div>
            <div id="mb-connect-error" class="mb-error" style="display:none;"></div>
            <div id="mb-connect-success" class="mb-success" style="display:none;"></div>
        <?php endif; ?>
    </div>

    <!-- Agent Readiness -->
    <div class="mb-card">
        <h2>AI Agent Readiness Score</h2>
        <div class="mb-score-row">
            <div class="mb-score-circle mb-score-<?php echo $agent_readiness['score'] >= 75 ? 'green' : ( $agent_readiness['score'] >= 50 ? 'yellow' : 'red' ); ?>">
                <?php echo esc_html( $agent_readiness['score'] ); ?>
            </div>
            <div>
                <strong><?php echo esc_html( $agent_readiness['passed'] ); ?>/<?php echo esc_html( $agent_readiness['total'] ); ?> checks passed</strong><br>
                <span class="mb-subtext">How well your site supports AI agents and crawlers.</span>
            </div>
        </div>
        <table class="mb-checks-table">
            <?php foreach ( $agent_readiness['checks'] as $check ) : ?>
                <tr class="<?php echo $check['passed'] ? 'mb-pass' : 'mb-fail'; ?>">
                    <td class="mb-check-icon"><?php echo $check['passed'] ? '&#10003;' : '&#10007;'; ?></td>
                    <td><?php echo esc_html( $check['name'] ); ?></td>
                    <td class="mb-check-status"><?php echo $check['passed'] ? 'Pass' : 'Fail'; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- llms.txt Generator -->
    <div class="mb-card">
        <h2>llms.txt Generator</h2>
        <p>An llms.txt file helps AI models understand your site structure. <a href="https://www.measureboard.com/blog/llms-txt-guide" target="_blank">Learn more</a></p>

        <?php if ( $llms_status === 'published' ) : ?>
            <div class="mb-status mb-status-connected">
                <span class="dashicons dashicons-yes-alt"></span>
                Live at <a href="<?php echo esc_url( get_home_url() . '/llms.txt' ); ?>" target="_blank"><?php echo esc_html( get_home_url() . '/llms.txt' ); ?></a>
            </div>
        <?php elseif ( $llms_status === 'draft' ) : ?>
            <div class="mb-status mb-status-warning">
                <span class="dashicons dashicons-edit"></span>
                Draft generated<?php echo $llms_gen_at ? ' on ' . esc_html( $llms_gen_at ) : ''; ?>. Review below and publish when ready.
            </div>
        <?php else : ?>
            <div class="mb-status mb-status-disconnected">
                <span class="dashicons dashicons-info"></span>
                No llms.txt generated yet.
            </div>
        <?php endif; ?>

        <p>
            <button id="mb-generate-llms" class="button"><?php echo $llms_content ? 'Regenerate' : 'Generate'; ?> llms.txt</button>
            <?php if ( $llms_status === 'draft' ) : ?>
                <button id="mb-publish-llms" class="button button-primary" style="margin-left: 8px;">Publish</button>
            <?php endif; ?>
            <?php if ( $llms_status === 'published' ) : ?>
                <button id="mb-unpublish-llms" class="button" style="margin-left: 8px;">Unpublish</button>
            <?php endif; ?>
        </p>

        <?php if ( $llms_content ) : ?>
            <div class="mb-code-preview">
                <h4>Preview</h4>
                <pre id="mb-llms-preview"><?php echo esc_html( $llms_content ); ?></pre>
            </div>
        <?php endif; ?>
    </div>

    <!-- robots.txt Recommendations -->
    <div class="mb-card">
        <h2>robots.txt Recommendations</h2>
        <?php if ( ! empty( $robots_recs['missingBots'] ) || ! $robots_recs['hasSitemapRef'] || ! $robots_recs['hasLlmsRef'] ) : ?>
            <p>Add the following to your robots.txt to improve AI agent discovery:</p>
            <div class="mb-code-preview">
                <pre><?php echo esc_html( $robots_recs['additions'] ); ?></pre>
            </div>
            <p class="mb-subtext">
                Edit via <strong>Settings &gt; Reading</strong> (if using a virtual robots.txt) or edit the robots.txt file in your site root.
            </p>
        <?php else : ?>
            <div class="mb-status mb-status-connected">
                <span class="dashicons dashicons-yes-alt"></span>
                Your robots.txt includes AI bot rules, Sitemap reference, and LLMS reference.
            </div>
        <?php endif; ?>
    </div>

    <!-- JSON-LD Recommendations -->
    <div class="mb-card">
        <h2>JSON-LD Schema Recommendations</h2>
        <p>Structured data helps AI models understand your content with confidence.</p>
        <table class="mb-recs-table">
            <thead>
                <tr>
                    <th>Schema Type</th>
                    <th>Recommendation</th>
                    <th>Where to Add</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $jsonld_recs as $rec ) : ?>
                    <tr>
                        <td><code><?php echo esc_html( $rec['type'] ); ?></code></td>
                        <td><?php echo esc_html( $rec['description'] ); ?></td>
                        <td class="mb-subtext"><?php echo esc_html( $rec['location'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ( MeasureBoard::has_woocommerce() ) : ?>
    <!-- WooCommerce -->
    <div class="mb-card">
        <h2>WooCommerce Integration</h2>
        <div class="mb-status mb-status-connected">
            <span class="dashicons dashicons-yes-alt"></span>
            WooCommerce detected. Order and product data will sync to MeasureBoard for sales attribution analysis.
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
(function($) {
    var nonce = '<?php echo esc_js( $nonce ); ?>';

    $('#mb-connect').on('click', function() {
        var btn = $(this);
        var pid = $('#mb-property-id').val().trim();
        if (!pid) { $('#mb-connect-error').text('Enter a Property ID.').show(); return; }
        btn.prop('disabled', true).text('Connecting...');
        $.post(ajaxurl, { action: 'measureboard_connect', nonce: nonce, property_id: pid }, function(r) {
            if (r.success) { location.reload(); }
            else { $('#mb-connect-error').text(r.data || 'Connection failed.').show(); btn.prop('disabled', false).text('Connect'); }
        }).fail(function() { $('#mb-connect-error').text('Network error.').show(); btn.prop('disabled', false).text('Connect'); });
    });

    $('#mb-disconnect').on('click', function() {
        if (!confirm('Disconnect from MeasureBoard?')) return;
        $.post(ajaxurl, { action: 'measureboard_disconnect', nonce: nonce }, function() { location.reload(); });
    });

    $('#mb-generate-llms').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('Generating...');
        $.post(ajaxurl, { action: 'measureboard_generate_llms', nonce: nonce }, function(r) {
            if (r.success) { location.reload(); }
            else { alert(r.data || 'Failed to generate.'); btn.prop('disabled', false); }
        });
    });

    $('#mb-publish-llms').on('click', function() {
        $.post(ajaxurl, { action: 'measureboard_publish_llms', nonce: nonce }, function(r) {
            if (r.success) { location.reload(); }
            else { alert(r.data || 'Failed to publish.'); }
        });
    });

    $('#mb-unpublish-llms').on('click', function() {
        $.post(ajaxurl, { action: 'measureboard_unpublish_llms', nonce: nonce }, function(r) {
            if (r.success) { location.reload(); }
            else { alert(r.data || 'Failed to unpublish.'); }
        });
    });
})(jQuery);
</script>
