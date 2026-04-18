<?php
/**
 * Content analysis — extracts SEO-relevant data from all posts and pages.
 *
 * @package MeasureBoard
 */

defined( 'ABSPATH' ) || exit;

class MeasureBoard_Content {

    /**
     * Get all published content with SEO-relevant metadata.
     * Includes posts, pages, and WooCommerce products if available.
     */
    public static function get_all_content() {
        $post_types = array( 'post', 'page' );
        if ( MeasureBoard::has_woocommerce() ) {
            $post_types[] = 'product';
        }

        $posts = get_posts( array(
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => 2000,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        ) );

        $results = array();
        foreach ( $posts as $post ) {
            $results[] = self::extract_page_data( $post );
        }

        return $results;
    }

    /**
     * Extract SEO data from a single post/page.
     */
    private static function extract_page_data( $post ) {
        $content    = $post->post_content;
        $plain_text = wp_strip_all_tags( $content );
        $word_count = str_word_count( $plain_text );
        $url        = get_permalink( $post );

        // Extract headings
        $h1s = array();
        $h2s = array();
        if ( preg_match_all( '/<h1[^>]*>(.*?)<\/h1>/si', $content, $matches ) ) {
            $h1s = array_map( 'wp_strip_all_tags', $matches[1] );
        }
        if ( preg_match_all( '/<h2[^>]*>(.*?)<\/h2>/si', $content, $matches ) ) {
            $h2s = array_map( 'wp_strip_all_tags', $matches[1] );
        }

        // Meta description (check Yoast, Rank Math, AIOSEO, then fallback)
        $meta_desc = '';
        $meta_desc = get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
        if ( empty( $meta_desc ) ) {
            $meta_desc = get_post_meta( $post->ID, 'rank_math_description', true );
        }
        if ( empty( $meta_desc ) ) {
            $meta_desc = get_post_meta( $post->ID, '_aioseo_description', true );
        }
        if ( empty( $meta_desc ) ) {
            $meta_desc = wp_trim_words( $plain_text, 30, '...' );
        }

        // Meta title
        $meta_title = '';
        $meta_title = get_post_meta( $post->ID, '_yoast_wpseo_title', true );
        if ( empty( $meta_title ) ) {
            $meta_title = get_post_meta( $post->ID, 'rank_math_title', true );
        }
        if ( empty( $meta_title ) ) {
            $meta_title = $post->post_title;
        }

        // Focus keyword
        $focus_keyword = get_post_meta( $post->ID, '_yoast_wpseo_focuskw', true );
        if ( empty( $focus_keyword ) ) {
            $focus_keyword = get_post_meta( $post->ID, 'rank_math_focus_keyword', true );
        }

        // Images
        $image_count = preg_match_all( '/<img\s/i', $content, $img_matches );
        $images_without_alt = 0;
        if ( preg_match_all( '/<img[^>]*>/i', $content, $img_tags ) ) {
            foreach ( $img_tags[0] as $tag ) {
                if ( ! preg_match( '/alt\s*=\s*["\'][^"\']+["\']/i', $tag ) ) {
                    $images_without_alt++;
                }
            }
        }

        // Internal vs external links
        $site_host     = wp_parse_url( get_site_url(), PHP_URL_HOST );
        $internal_links = 0;
        $external_links = 0;
        if ( preg_match_all( '/<a\s[^>]*href\s*=\s*["\']([^"\']+)["\']/i', $content, $link_matches ) ) {
            foreach ( $link_matches[1] as $href ) {
                $link_host = wp_parse_url( $href, PHP_URL_HOST );
                if ( $link_host && $link_host !== $site_host ) {
                    $external_links++;
                } else {
                    $internal_links++;
                }
            }
        }

        // Featured image
        $has_featured_image = has_post_thumbnail( $post->ID );

        // Categories and tags
        $categories = wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
        $tags       = wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );

        // JSON-LD presence (check for schema in content or Yoast/Rank Math output)
        $has_schema = false;
        if ( strpos( $content, 'application/ld+json' ) !== false ) {
            $has_schema = true;
        }

        return array(
            'url'                 => $url,
            'title'               => $post->post_title,
            'metaTitle'           => $meta_title,
            'metaDescription'     => $meta_desc,
            'focusKeyword'        => $focus_keyword,
            'type'                => $post->post_type,
            'status'              => $post->post_status,
            'wordCount'           => $word_count,
            'publishedAt'         => $post->post_date_gmt,
            'modifiedAt'          => $post->post_modified_gmt,
            'h1s'                 => $h1s,
            'h2s'                 => $h2s,
            'imageCount'          => $image_count,
            'imagesWithoutAlt'    => $images_without_alt,
            'hasFeaturedImage'    => $has_featured_image,
            'internalLinks'       => $internal_links,
            'externalLinks'       => $external_links,
            'categories'          => $categories,
            'tags'                => $tags,
            'hasSchema'           => $has_schema,
            'slug'                => $post->post_name,
            'excerpt'             => $post->post_excerpt,
        );
    }
}
