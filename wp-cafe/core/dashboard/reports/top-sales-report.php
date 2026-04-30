<?php
/**
 * Top Sales Report
 *
 * Handles all top sales-related business logic and data retrieval.
 *
 * @package WpCafe\Dashboard\Reports
 * @since 1.0.0
 */

namespace WpCafe\Dashboard\Reports;

/**
 * Top Sales Report Class
 *
 * Responsible for managing top sales data from WooCommerce products.
 *
 * @package WpCafe\Dashboard\Reports
 * @since 1.0.0
 */
class Top_Sales_Report {

    /**
     * Get top sales data from WooCommerce products.
     *
     * @since 1.0.0
     * @param int    $limit      Number of items to return.
     * @param string $period     Time period for data.
     * @param string $branch     Branch filter.
     * @param string $start_date Custom start date (Y-m-d format) - required for custom period.
     * @param string $end_date   Custom end date (Y-m-d format) - required for custom period.
     * @return array Top sales data.
     */
    public function get_top_sales_data( $limit, $period, $branch, $start_date = '', $end_date = '' ) {

        if ( ! function_exists( 'wc_get_orders' ) ) {
            return array(
                'top_sales'   => array(),
                'period'      => $period,
                'total_items' => 0,
            );
        }
        
        $date_range = Date_Utility::get_date_range( $period, $start_date, $end_date );
        
        $generic_product_id_for_reservation = (int) wpc_get_option( 'woocommerce_generic_product_id' );
        // Get all completed orders in the date range
        $order_args = array(
            'status'        => array( 'wc-completed', 'wc-processing', 'wc-on-hold' ),
            'type'          => 'shop_order',
            'date_created'  => $date_range['start'] . '...' . $date_range['end'],
            'limit'         => $limit,
            'return'        => 'objects',
        );

        // Add branch filter if specified
        if ( $branch !== 'all' ) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- required for report/filter functionality
            $order_args['meta_query'] = array(
                array(
                    'key'   => 'wpc_branch',
                    'value' => $branch,
                ),
            );
        }

        $orders = wc_get_orders( $order_args );

        // Collect product sales data
        $product_sales = array();
        
        foreach ( $orders as $order ) {
            foreach ( $order->get_items() as $item ) {
                // Get the actual product ID (variation ID if it's a variation, otherwise product ID)
                // @phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
                $variation_id = $item->get_variation_id();
                // @phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
                $product_id = $variation_id ? $variation_id : $item->get_product_id();

                if ( ! $product_id || $product_id === $generic_product_id_for_reservation ) {
                    continue;
                }
                
                $quantity = $item->get_quantity();
                // @phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
                $revenue = $item->get_total();
                
                if ( ! isset( $product_sales[ $product_id ] ) ) {
                    $product_sales[ $product_id ] = array(
                        'product_id'     => $product_id,
                        'total_quantity' => 0,
                        'total_revenue'  => 0.0,
                    );
                }
                
                $product_sales[ $product_id ]['total_quantity'] += $quantity;
                $product_sales[ $product_id ]['total_revenue'] += $revenue;
            }
        }
        
        // Sort by revenue and limit results
        uasort( $product_sales, function( $a, $b ) {
            return $b['total_revenue'] <=> $a['total_revenue'];
        } );

        $product_sales = array_slice( $product_sales, 0, $limit, true );

        $product_ids = array_keys( $product_sales );
        $products = wc_get_products( array(
            'include' => $product_ids,
            'limit'   => count( $product_ids ),
        ) );

        $products_lookup = array();
        foreach ( $products as $product ) {
            $products_lookup[ $product->get_id() ] = $product;
        }

        // Build final results with product details
        $top_sales = array();

        foreach ( $product_sales as $product_id => $sales_data ) {
            // Get product from lookup array
            if ( ! isset( $products_lookup[ $product_id ] ) ) {
                continue;
            }

            $product = $products_lookup[ $product_id ];

            // Get primary category
            $terms = get_the_terms( $product_id, 'product_cat' );
            $primary_category = 'Uncategorized';

            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                $primary_category = $terms[0]->name;
            }

            $top_sales[] = array(
                'product_id'    => $product_id,
                'product_name'  => $product->get_name(),
                'category'      => $primary_category,
                'quantity'      => (int) $sales_data['total_quantity'],
                'total_revenue' => round( (float) $sales_data['total_revenue'], 2 ),
            );
        }
        
        return array(
            'top_sales'   => $top_sales,
            'period'      => $period,
            'total_items' => count( $top_sales ),
        );
    }



    /**
     * Get branch filter SQL condition.
     *
     * @since 1.0.0
     * @param string $branch Branch ID or 'all'.
     * @return string SQL condition for branch filtering.
     */
    private function get_branch_filter( $branch ) {
        if ( 'all' === $branch ) {
            return '';
        }

        global $wpdb;
        return $wpdb->prepare(
            "AND EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} branch_meta 
                WHERE branch_meta.post_id = orders.ID 
                AND branch_meta.meta_key = '_branch_id' 
                AND branch_meta.meta_value = %s
            )",
            $branch
        );
    }
} 
