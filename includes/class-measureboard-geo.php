<?php
/**
 * GEO Optimization — llms.txt generator, JSON-LD recommendations, agent readiness.
 *
 * All features are read-only or draft-mode:
 * - llms.txt is saved as a WordPress draft (user publishes manually)
 * - JSON-LD and robots.txt changes are shown as recommendations
 *
 * @package MeasureBoard
 */

defined( 'ABSPATH' ) || exit;

class MeasureBoard_Geo {

    public static function init() {
        self::register_rewrite_rules();
        add_action( 'init', array( __CLASS__, 'register_rewrite_rules' ) );
        add_filter( 'template_redirect', array( __CLASS__, 'serve_llms_txt' ) );
    }

    /**
     * Register rewrite rule for /llms.txt
     */
    public static function register_rewrite_rules() {
        add_rewrite_rule( '^llms\.txt$', 'index.php?measureboard_llms_txt=1', 'top' );
        add_rewrite_tag( '%measureboard_llms_txt%', '1' );
    }

    /**
     * Serve the llms.txt file if one has been generated and published.
     */
    public static function serve_llms_txt() {
        if ( ! get_query_var( 'measureboard_llms_txt' ) ) {
            return;
        }

        $content = get_option( 'measureboard_llms_txt_content' );
        $status  = get_option( 'measureboard_llms_txt_status', 'draft' );

        if ( $status !== 'published' || empty( $content ) ) {
            status_header( 404 );
            exit;
        }

        header( 'Content-Type: text/plain; charset=utf-8' );
        header( 'Cache-Control: public, max-age=86400' );
        header( 'X-Robots-Tag: noindex' );
        echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain text file
        exit;
    }

    /**
     * Generate llms.txt content from site data.
     */
    public static function generate_llms_txt() {
        $site_name = get_bloginfo( 'name' );
        $site_desc = get_bloginfo( 'description' );
        $site_url  = get_home_url();

        $lines = array();
        $lines[] = "# {$site_name}";
        $lines[] = '';

        if ( $site_desc ) {
            $lines[] = "> {$site_desc}";
            $lines[] = '';
        }

        // Add main pages
        $pages = get_posts( array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => 30,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ) );

        if ( ! empty( $pages ) ) {
            $lines[] = '## Main Pages';
            $lines[] = '';
            foreach ( $pages as $page ) {
                $url   = get_permalink( $page );
                $title = $page->post_title;
                $desc  = wp_trim_words( wp_strip_all_tags( $page->post_content ), 20, '...' );
                $lines[] = "- [{$title}]({$url}): {$desc}";
            }
            $lines[] = '';
        }

        // Add recent posts
        $posts = get_posts( array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        if ( ! empty( $posts ) ) {
            $lines[] = '## Recent Posts';
            $lines[] = '';
            foreach ( $posts as $post ) {
                $url   = get_permalink( $post );
                $title = $post->post_title;
                $desc  = $post->post_excerpt ?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 20, '...' );
                $lines[] = "- [{$title}]({$url}): {$desc}";
            }
            $lines[] = '';
        }

        // WooCommerce products
        if ( MeasureBoard::has_woocommerce() ) {
            $products = get_posts( array(
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => 20,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ) );

            if ( ! empty( $products ) ) {
                $lines[] = '## Products';
                $lines[] = '';
                foreach ( $products as $product ) {
                    $url   = get_permalink( $product );
                    $title = $product->post_title;
                    $desc  = $product->post_excerpt ?: wp_trim_words( wp_strip_all_tags( $product->post_content ), 15, '...' );
                    $lines[] = "- [{$title}]({$url}): {$desc}";
                }
                $lines[] = '';
            }
        }

        // Contact info
        $lines[] = '## Contact';
        $lines[] = '';
        $lines[] = "- [Website]({$site_url})";
        $lines[] = '';

        return implode( "\n", $lines );
    }

    /**
     * Save generated llms.txt as draft (user reviews and publishes).
     */
    public static function save_llms_txt_draft() {
        $content = self::generate_llms_txt();
        update_option( 'measureboard_llms_txt_content', $content );
        update_option( 'measureboard_llms_txt_status', 'draft' );
        update_option( 'measureboard_llms_txt_generated_at', current_time( 'mysql' ) );
        return $content;
    }

    /**
     * Publish the llms.txt file (makes it live at /llms.txt).
     */
    public static function publish_llms_txt() {
        $content = get_option( 'measureboard_llms_txt_content' );
        if ( empty( $content ) ) {
            return new WP_Error( 'no_content', 'Generate llms.txt first.' );
        }
        update_option( 'measureboard_llms_txt_status', 'published' );
        update_option( 'measureboard_llms_txt_published_at', current_time( 'mysql' ) );
        return true;
    }

    /**
     * Unpublish the llms.txt file.
     */
    public static function unpublish_llms_txt() {
        update_option( 'measureboard_llms_txt_status', 'draft' );
        return true;
    }

    /**
     * Get JSON-LD recommendations based on current site schema.
     */
    public static function get_jsonld_recommendations() {
        $recs = array();
        $site_url  = get_home_url();
        $site_name = get_bloginfo( 'name' );

        // Check if Organization schema exists
        $recs[] = array(
            'type'        => 'Organization',
            'description' => 'Add Organization schema to your homepage for brand recognition in AI search.',
            'code'        => wp_json_encode( array(
                '@context' => 'https://schema.org',
                '@type'    => 'Organization',
                'name'     => $site_name,
                'url'      => $site_url,
            ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
            'location'    => 'Homepage <head> or via your SEO plugin',
        );

        // WebSite schema with SearchAction
        $recs[] = array(
            'type'        => 'WebSite',
            'description' => 'Add WebSite schema with search action for sitelinks search box.',
            'code'        => wp_json_encode( array(
                '@context'        => 'https://schema.org',
                '@type'           => 'WebSite',
                'name'            => $site_name,
                'url'             => $site_url,
                'potentialAction'  => array(
                    '@type'       => 'SearchAction',
                    'target'      => $site_url . '/?s={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ),
            ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
            'location'    => 'Homepage <head>',
        );

        // BreadcrumbList
        $recs[] = array(
            'type'        => 'BreadcrumbList',
            'description' => 'Add breadcrumb schema to improve navigation signals. Most SEO plugins handle this automatically.',
            'code'        => null,
            'location'    => 'Enable in Yoast SEO > Search Appearance > Breadcrumbs, or Rank Math > General Settings > Breadcrumbs',
        );

        // Article schema for blog posts
        $post_count = wp_count_posts()->publish;
        if ( $post_count > 0 ) {
            $recs[] = array(
                'type'        => 'Article',
                'description' => "Add Article schema to your {$post_count} blog posts. Most SEO plugins do this automatically when configured.",
                'code'        => null,
                'location'    => 'Handled by Yoast SEO or Rank Math when post type schema is set to Article',
            );
        }

        // Product schema for WooCommerce
        if ( MeasureBoard::has_woocommerce() ) {
            $product_count = wp_count_posts( 'product' )->publish;
            $recs[] = array(
                'type'        => 'Product',
                'description' => "WooCommerce adds Product schema automatically for your {$product_count} products. Verify it includes price, availability, and review data.",
                'code'        => null,
                'location'    => 'WooCommerce handles this. Check with Google Rich Results Test.',
            );
        }

        return $recs;
    }

    /**
     * Get robots.txt recommendations for AI crawlers.
     */
    public static function get_robots_recommendations() {
        $current = '';
        if ( is_file( ABSPATH . 'robots.txt' ) ) {
            $current = file_get_contents( ABSPATH . 'robots.txt' );
        } else {
            // WordPress generates robots.txt dynamically
            $current = "# WordPress virtual robots.txt\n# Edit via Settings > Reading or a plugin";
        }

        $ai_bots = array(
            'GPTBot', 'ChatGPT-User', 'ClaudeBot', 'anthropic-ai',
            'PerplexityBot', 'Google-Extended', 'Applebot-Extended',
        );

        $missing_bots = array();
        foreach ( $ai_bots as $bot ) {
            if ( stripos( $current, $bot ) === false ) {
                $missing_bots[] = $bot;
            }
        }

        $has_sitemap_ref = (bool) preg_match( '/^Sitemap:/im', $current );
        $has_llms_ref    = (bool) preg_match( '/^LLMS:/im', $current );

        $additions = array();
        if ( ! empty( $missing_bots ) ) {
            $additions[] = "\n# AI Crawlers - Allow access for AI search visibility";
            foreach ( $missing_bots as $bot ) {
                $additions[] = "User-agent: {$bot}";
                $additions[] = "Allow: /";
                $additions[] = '';
            }
        }
        if ( ! $has_sitemap_ref ) {
            $sitemap_url   = get_home_url() . '/sitemap.xml';
            $additions[]   = "Sitemap: {$sitemap_url}";
        }
        if ( ! $has_llms_ref ) {
            $llms_url    = get_home_url() . '/llms.txt';
            $additions[] = "LLMS: {$llms_url}";
        }

        return array(
            'current'      => $current,
            'missingBots'  => $missing_bots,
            'hasSitemapRef' => $has_sitemap_ref,
            'hasLlmsRef'   => $has_llms_ref,
            'additions'    => implode( "\n", $additions ),
        );
    }

    /**
     * Run agent readiness checks against the local site.
     */
    public static function get_agent_readiness() {
        $home_url = get_home_url();
        $checks   = array();

        // 1. robots.txt
        $robots_url = $home_url . '/robots.txt';
        $robots_res = wp_remote_get( $robots_url, array( 'timeout' => 5 ) );
        $robots_ok  = ! is_wp_error( $robots_res ) && wp_remote_retrieve_response_code( $robots_res ) === 200;
        $robots_txt = $robots_ok ? wp_remote_retrieve_body( $robots_res ) : '';
        $checks[]   = array( 'name' => 'robots.txt', 'passed' => $robots_ok );

        // 2. Sitemap
        $sitemap_res = wp_remote_head( $home_url . '/sitemap.xml', array( 'timeout' => 5 ) );
        $sitemap_ok  = ! is_wp_error( $sitemap_res ) && wp_remote_retrieve_response_code( $sitemap_res ) === 200;
        $has_ref     = (bool) preg_match( '/^Sitemap:/im', $robots_txt );
        $checks[]    = array( 'name' => 'Sitemap', 'passed' => $sitemap_ok && $has_ref );

        // 3. AI bot rules
        $ai_bots_found = array();
        $all_bots = array( 'GPTBot', 'ClaudeBot', 'PerplexityBot', 'Google-Extended', 'Applebot-Extended' );
        foreach ( $all_bots as $bot ) {
            if ( preg_match( '/User-agent:\s*' . preg_quote( $bot, '/' ) . '/i', $robots_txt ) ) {
                $ai_bots_found[] = $bot;
            }
        }
        $checks[] = array( 'name' => 'AI Bot Rules', 'passed' => ! empty( $ai_bots_found ) );

        // 4. llms.txt
        $llms_res = wp_remote_head( $home_url . '/llms.txt', array( 'timeout' => 5 ) );
        $llms_ok  = ! is_wp_error( $llms_res ) && wp_remote_retrieve_response_code( $llms_res ) === 200;
        $checks[] = array( 'name' => 'llms.txt', 'passed' => $llms_ok );

        // 5. LLMS directive
        $has_llms_ref = (bool) preg_match( '/^LLMS:/im', $robots_txt );
        $checks[]     = array( 'name' => 'LLMS Directive', 'passed' => $has_llms_ref );

        // 6. JSON-LD on homepage
        $home_res   = wp_remote_get( $home_url, array( 'timeout' => 10 ) );
        $home_html  = ! is_wp_error( $home_res ) ? wp_remote_retrieve_body( $home_res ) : '';
        $has_jsonld = (bool) preg_match( '/application\/ld\+json/i', $home_html );
        $checks[]   = array( 'name' => 'JSON-LD Schema', 'passed' => $has_jsonld );

        // 7. Link headers
        $link_header = '';
        if ( ! is_wp_error( $home_res ) ) {
            $link_header = wp_remote_retrieve_header( $home_res, 'Link' );
        }
        $checks[] = array( 'name' => 'Link Headers', 'passed' => ! empty( $link_header ) );

        // 8. Markdown negotiation
        $md_res     = wp_remote_get( $home_url, array(
            'timeout' => 5,
            'headers' => array( 'Accept' => 'text/markdown' ),
        ) );
        $md_ct      = ! is_wp_error( $md_res ) ? wp_remote_retrieve_header( $md_res, 'content-type' ) : '';
        $has_md     = stripos( $md_ct, 'text/markdown' ) !== false;
        $checks[]   = array( 'name' => 'Markdown Negotiation', 'passed' => $has_md );

        $passed = count( array_filter( $checks, function( $c ) { return $c['passed']; } ) );
        $score  = round( ( $passed / count( $checks ) ) * 100 );

        return array(
            'score'  => $score,
            'checks' => $checks,
            'passed' => $passed,
            'total'  => count( $checks ),
        );
    }
}
