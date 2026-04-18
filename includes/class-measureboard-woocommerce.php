<?php
/**
 * WooCommerce integration — extracts order, product, and revenue data.
 * Only loaded when WooCommerce is active.
 *
 * @package MeasureBoard
 */

defined( 'ABSPATH' ) || exit;

class MeasureBoard_WooCommerce {

    /**
     * Get complete WooCommerce summary for MeasureBoard.
     */
    public function get_summary() {
        return array(
            'orders'   => $this->get_recent_orders(),
            'products' => $this->get_top_products(),
            'summary'  => $this->get_revenue_summary(),
        );
    }

    /**
     * Get orders from the last 90 days with channel attribution.
     */
    private function get_recent_orders() {
        $orders = wc_get_orders( array(
            'limit'        => 500,
            'status'       => array( 'completed', 'processing', 'on-hold' ),
            'date_created' => '>' . gmdate( 'Y-m-d', strtotime( '-90 days' ) ),
            'orderby'      => 'date',
            'order'        => 'DESC',
        ) );

        $results = array();
        foreach ( $orders as $order ) {
            $results[] = array(
                'id'          => $order->get_id(),
                'total'       => (float) $order->get_total(),
                'currency'    => $order->get_currency(),
                'status'      => $order->get_status(),
                'date'        => $order->get_date_created() ? $order->get_date_created()->format( 'Y-m-d' ) : null,
                'source'      => $this->classify_order_source( $order ),
                'itemCount'   => $order->get_item_count(),
                'couponUsed'  => ! empty( $order->get_coupon_codes() ),
            );
        }

        return $results;
    }

    /**
     * Classify order source/channel from available metadata.
     */
    private function classify_order_source( $order ) {
        // WooCommerce 8.5+ has built-in order attribution
        $source = $order->get_meta( '_wc_order_attribution_source_type' );
        if ( $source ) {
            return array(
                'type'     => $source,
                'source'   => $order->get_meta( '_wc_order_attribution_utm_source' ) ?: $order->get_meta( '_wc_order_attribution_referrer' ) ?: 'direct',
                'medium'   => $order->get_meta( '_wc_order_attribution_utm_medium' ) ?: '',
                'campaign' => $order->get_meta( '_wc_order_attribution_utm_campaign' ) ?: '',
            );
        }

        // Fallback: check UTM meta fields (common across plugins)
        $utm_source = $order->get_meta( '_utm_source' ) ?: $order->get_meta( 'utm_source' ) ?: '';
        if ( $utm_source ) {
            return array(
                'type'     => 'utm',
                'source'   => $utm_source,
                'medium'   => $order->get_meta( '_utm_medium' ) ?: $order->get_meta( 'utm_medium' ) ?: '',
                'campaign' => $order->get_meta( '_utm_campaign' ) ?: $order->get_meta( 'utm_campaign' ) ?: '',
            );
        }

        return array(
            'type'     => 'unknown',
            'source'   => 'direct',
            'medium'   => '',
            'campaign' => '',
        );
    }

    /**
     * Get top products by revenue.
     */
    private function get_top_products() {
        global $wpdb;

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT oi.order_item_name as name,
                    SUM(oim_qty.meta_value) as quantity,
                    SUM(oim_total.meta_value) as revenue,
                    oim_pid.meta_value as product_id
             FROM {$wpdb->prefix}woocommerce_order_items oi
             JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_qty ON oi.order_item_id = oim_qty.order_item_id AND oim_qty.meta_key = '_qty'
             JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_total ON oi.order_item_id = oim_total.order_item_id AND oim_total.meta_key = '_line_total'
             JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_pid ON oi.order_item_id = oim_pid.order_item_id AND oim_pid.meta_key = '_product_id'
             JOIN {$wpdb->posts} p ON oi.order_id = p.ID AND p.post_status IN ('wc-completed','wc-processing')
             WHERE oi.order_item_type = 'line_item'
               AND p.post_date >= %s
             GROUP BY oim_pid.meta_value
             ORDER BY revenue DESC
             LIMIT 20",
            gmdate( 'Y-m-d', strtotime( '-90 days' ) )
        ), ARRAY_A );

        return array_map( function( $row ) {
            $product = wc_get_product( (int) $row['product_id'] );
            return array(
                'productId' => (int) $row['product_id'],
                'name'      => $row['name'],
                'quantity'  => (int) $row['quantity'],
                'revenue'   => (float) $row['revenue'],
                'url'       => $product ? get_permalink( $product->get_id() ) : '',
                'sku'       => $product ? $product->get_sku() : '',
                'inStock'   => $product ? $product->is_in_stock() : null,
            );
        }, $results ?: array() );
    }

    /**
     * Get revenue summary (last 30, 60, 90 days).
     */
    private function get_revenue_summary() {
        $periods = array( 30, 60, 90 );
        $summary = array();

        foreach ( $periods as $days ) {
            $orders = wc_get_orders( array(
                'limit'        => -1,
                'status'       => array( 'completed', 'processing' ),
                'date_created' => '>' . gmdate( 'Y-m-d', strtotime( "-{$days} days" ) ),
                'return'       => 'ids',
            ) );

            $total = 0;
            foreach ( $orders as $order_id ) {
                $order = wc_get_order( $order_id );
                if ( $order ) {
                    $total += (float) $order->get_total();
                }
            }

            $summary[ "last_{$days}_days" ] = array(
                'orders'  => count( $orders ),
                'revenue' => round( $total, 2 ),
            );
        }

        // Currency
        $summary['currency'] = get_woocommerce_currency();

        return $summary;
    }
}
