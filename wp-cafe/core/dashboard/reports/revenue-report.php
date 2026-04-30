<?php
/**
 * Revenue Report
 *
 * Handles all revenue-related business logic and data retrieval.
 *
 * @package WpCafe\Dashboard\Reports
 * @since 1.0.0
 */

namespace WpCafe\Dashboard\Reports;

use WC_Order_Query;
use WpCafe\Models\Reservation_Model;

/**
 * Revenue Report Class
 *
 * Responsible for calculating revenue data from WooCommerce orders.
 *
 * @package WpCafe\Dashboard\Reports
 * @since 1.0.0
 */
class Revenue_Report {
    /**
     * Get revenue data from WooCommerce orders and reservations.
     *
     * @since 1.0.0
     * @param string $start_date Custom start date (Y-m-d format) - required for custom period.
     * @param string $end_date   Custom end date (Y-m-d format) - required for custom period.
     * @param string $branch     Branch ID filter ('all' for all branches).
     * @return array Revenue data with change percentage.
     */
    public function get_revenue_data( $start_date = '', $end_date = '', $branch = 'all' ) {
        $revenue_statuses = [ 'wc-completed', 'wc-processing' ];
        $current_orders_revenue = $this->get_total_order_revenue( $start_date, $end_date, $revenue_statuses , $branch );

        $current_reservations_revenue = $this->get_total_reservations_revenue( $start_date, $end_date, $branch );

        // Calculate total current revenue.
        $current_revenue = $current_orders_revenue + $current_reservations_revenue;

        // Get previous period revenue for comparison.
        $prev_date_range = Date_Utility::get_previous_period_range( 'month', $start_date, $end_date );

        // Previous period WooCommerce orders revenue.
        $prev_orders_revenue = $this->get_total_order_revenue( $prev_date_range['start'], $prev_date_range['end'], $revenue_statuses, $branch );

        // Previous period reservations revenue.
        $prev_reservations_revenue = $this->get_total_reservations_revenue( $prev_date_range['start'], $prev_date_range['end'], $branch );

        // Calculate total previous revenue.
        $prev_revenue = $prev_orders_revenue + $prev_reservations_revenue;

        $change_percentage = $prev_revenue > 0 ? ( ( $current_revenue - $prev_revenue ) / $prev_revenue ) * 100 : 0;
        $change_direction  = $change_percentage >= 0 ? 'up' : 'down';

        return array(
            'total'     			=> round( $current_revenue, 2 ),
            'orders_revenue'    	=> round( $current_orders_revenue, 2 ),
            'reservations_revenue'  => round( $current_reservations_revenue, 2 ),
            'change_percentage' 	=> round( abs( $change_percentage ), 2 ),
            'change_direction'  	=> $change_direction,
        );
    }

    /**
     * Get total revenue of WooCommerce orders within a date range using WC_Order_Query.
     *
     * @param string $start_date Start date (Y-m-d format).
     * @param string $end_date   End date (Y-m-d format).
     * @param array  $statuses   Order statuses to include (default: completed & processing).
     * @param string $branch     Branch ID filter ('all' for all branches).
     * @return float             Total revenue in store currency.
     */
    public function get_total_order_revenue( $start_date, $end_date, $statuses = array( 'wc-completed', 'wc-processing' ), $branch = 'all' ) {

        if ( ! function_exists( 'WC' ) ) {
            return 0.0;
        }

        $query_args = array(
            'limit'        => -1,
            'status'       => $statuses,
            'date_created' => $start_date . '...' . $end_date,
            'return'       => 'ids',
        );
        
        // Exclude orders with reservation_id meta (to skip woocommerce orders for reservation)
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- required for report/filter functionality
        $query_args['meta_query'] = array(
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

        $query = new WC_Order_Query( $query_args );
        $order_ids = $query->get_orders();

        if ( empty( $order_ids ) ) {
            return 0.0;
        }

        $total = 0.0;

        foreach ( $order_ids as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( $order ) {
                $total += (float) $order->get_total();
            }
        }

        return $total;
    }

    /**
     * Get total reservation value within a date range.
     *
     * @param string $start_date Start date (Y-m-d format).
     * @param string $end_date   End date (Y-m-d format).
     * @param string $branch     Branch ID filter ('all' for all branches).
     * @return float             Total reservation value.
     */
    public function get_total_reservations_revenue( $start_date, $end_date, $branch = 'all' ) {
        $args = array(
            'post_type'      => 'wpc_reservation',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'date_query'     => array(
                array(
                    'after'     => $start_date . ' 00:00:00',
                    'before'    => $end_date . ' 23:59:59',
                    'inclusive' => true,
                ),
            ),
        );

        if ( $branch && 'all' !== $branch ) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- required for report/filter functionality
            $args['meta_key']   = 'branch_id';
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- required for report/filter functionality
            $args['meta_value'] = $branch;
        }

        $posts = get_posts( $args );

        $total = 0.0;

        foreach ( $posts as $post_id ) {
            $reservation = new Reservation_Model( $post_id );
            $total += floatval( $reservation->get_total_price() );
        }

        return $total;
    }
} 
