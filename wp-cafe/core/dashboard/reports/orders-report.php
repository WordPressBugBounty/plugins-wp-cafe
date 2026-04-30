<?php
/**
 * Orders Report
 *
 * Handles all orders-related business logic and data retrieval.
 *
 * @package WpCafe\Dashboard\Reports
 * @since 1.0.0
 */

namespace WpCafe\Dashboard\Reports;
use WC_Order_Query;

/**
 * Orders Report Class
 *
 * Responsible for managing WooCommerce orders data.
 *
 * @package WpCafe\Dashboard\Reports
 * @since 1.0.0
 */
class Orders_Report {

    /**
     * Get orders data from WooCommerce orders.
     *
     * @since 1.0.0
     * @param string $start_date Custom start date (Y-m-d format) - required for custom period.
     * @param string $end_date   Custom end date (Y-m-d format) - required for custom period.
     * @param string $branch     Branch ID filter ('all' for all branches).
     * @return array Orders data with change percentage.
     */
    public function get_orders_data( $start_date = '', $end_date = '', $branch = 'all' ) {
        // Get current period WooCommerce orders.
        $default_order_statuses = [ 'wc-completed', 'wc-processing', 'wc-on-hold' ];
        $current_orders = $this->get_total_orders( $start_date, $end_date, $default_order_statuses , $branch );

        // Get previous period orders.
        $prev_date_range = Date_Utility::get_previous_period_range('month', $start_date, $end_date );
        $prev_orders = $this->get_total_orders( $prev_date_range['start'], $prev_date_range['end'], $default_order_statuses, $branch );

        $change_percentage = $prev_orders > 0 ? ( ( $current_orders - $prev_orders ) / $prev_orders ) * 100 : 0;
        $change_direction  = $change_percentage >= 0 ? 'up' : 'down';

        return array(
            'total'             => $current_orders,
            'change_percentage' => round( abs( $change_percentage ), 1 ),
            'change_direction'  => $change_direction,
        );
    }

    /**
     * Get orders list with pagination and filters.
     *
     * @since 1.0.0
     * @param int    $per_page   Number of items per page.
     * @param int    $page       Current page number.
     * @param string $status     Order status filter.
     * @param string $branch     Branch filter.
     * @param string $period     Time period for data.
     * @param string $start_date Custom start date (Y-m-d format) - required for custom period.
     * @param string $end_date   Custom end date (Y-m-d format) - required for custom period.
     * @return array Orders list with pagination info.
     */
    public function get_orders_list( $per_page, $page, $status, $branch, $start_date = '', $end_date = '' ) {

        if ( ! function_exists( 'wc_get_orders' ) ) {
            return [];
        }

        $statuses = array( 'completed', 'processing', 'on-hold' );
        $args = array(
            'limit'        => $per_page,
            'offset'       => ( $page - 1 ) * $per_page,
            'status'       => array( 'wc-completed', 'wc-processing', 'wc-on-hold' ),
            'return'       => 'objects',
        );

        // Exclude orders with reservation_id meta
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- required for report/filter functionality
        $args['meta_query'] = array(
            array(
                'key'     => 'reservation_id',
                'compare' => 'NOT EXISTS',
            ),
        );

        $orders = wc_get_orders( $args );

        $formatted_orders = array();
        foreach ( $orders as $order ) {
            // Check if the order object is valid and is not a refund
            if ( ! $order || ! is_a( $order, 'WC_Order' ) || is_a( $order, 'WC_Order_Refund' ) ) {
                continue;
            }

            $first_name = $order->get_billing_first_name();
            $last_name  = $order->get_billing_last_name();

            $customer_name = $first_name . ' ' . $last_name;

            $formatted_orders[] = array(
                'order_id'           => $order->get_id(),
                'status'             => $order->get_status(),
                'total_amount'       => round( (float) $order->get_total(), 2 ),
                'customer_name'      => $customer_name,
                'items_count'        => (int) $order->get_item_count(),
            );
        }

        return array(
            'orders'      => $formatted_orders,
        );
    }

    /**
     * Update order status.
     *
     * @since 1.0.0
     * @param int    $order_id Order ID.
     * @param string $status   New status.
     * @return bool True if updated successfully.
     */
    public function update_order_status( $order_id, $status ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            throw new \Exception( 'Order not found' );
        }

        $allowed_statuses = array_keys( wc_get_order_statuses() );
        if ( ! in_array( 'wc-' . $status, $allowed_statuses, true ) && ! in_array( $status, $allowed_statuses, true ) ) {
            throw new \Exception( 'Invalid order status' );
        }

        $order->update_status( $status );
        return true;
    }

    /**
     * Get order status label.
     *
     * @since 1.0.0
     * @param string $status Order status.
     * @return string Status label.
     */
    private function get_order_status_label( $status ) {
        $status_labels = array(
            'wc-pending'    => 'Pending',
            'wc-processing' => 'Processing',
            'wc-on-hold'    => 'On Hold',
            'wc-completed'  => 'Completed',
            'wc-cancelled'  => 'Cancelled',
            'wc-refunded'   => 'Refunded',
            'wc-failed'     => 'Failed',
        );

        return isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : ucfirst( str_replace( 'wc-', '', $status ) );
    }

    /**
     * Get status filter SQL condition.
     *
     * @since 1.0.0
     * @param string $status Status filter.
     * @return string SQL condition.
     */
    private function get_status_filter( $status ) {
        if ( empty( $status ) ) {
            return '';
        }

        global $wpdb;
        return $wpdb->prepare( "AND p.post_status = %s", $status );
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
                WHERE branch_meta.post_id = p.ID 
                AND branch_meta.meta_key = '_branch_id' 
                AND branch_meta.meta_value = %s
            )",
            $branch
        );
    }

    /**
     * Get total orders.
     *
     * @since 1.0.0
     * @param string $start_date Start date (Y-m-d format).
     * @param string $end_date   End date (Y-m-d format).
     * @param array  $statuses   Order statuses to include (default: completed & processing).
     * @param string $branch     Branch ID filter ('all' for all branches).
     * @return int             Total orders.
     */
    public function get_total_orders( $start_date, $end_date, $statuses = [ 'wc-completed', 'wc-processing', 'wc-on-hold' ], $branch = 'all' ) {
        
        if ( ! function_exists( 'WC' ) ) {
            return 0;
        }

        $query_args = array(
            'limit'        => -1,
            'status'       => $statuses,
            'date_created' => $start_date . '...' . $end_date,
            'return'       => 'ids',
        );

        // Build meta query for filtering
        $meta_query = array(
            'relation' => 'AND',
            array(
                'key'     => 'reservation_id',
                'compare' => 'NOT EXISTS',
            ),
        );

        if ( $branch && 'all' !== $branch ) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- required for report/filter functionality
            $query_args['meta_key']   = 'wpc_location_id';
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- required for report/filter functionality
            $query_args['meta_value'] = $branch;
        }

        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- required for report/filter functionality
        $query_args['meta_query'] = $meta_query;

        $query = new WC_Order_Query( $query_args );
        $order_ids = $query->get_orders();

        return count( $order_ids );
    }

}
