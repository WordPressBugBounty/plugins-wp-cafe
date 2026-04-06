<?php
/**
 * Customers Report
 *
 * Handles all customers-related business logic and data retrieval.
 *
 * @package WpCafe\Dashboard\Reports
 * @since 1.0.0
 */

namespace WpCafe\Dashboard\Reports;

use WC_Customer;

/**
 * Customers Report Class
 *
 * Responsible for managing customer data from WooCommerce orders.
 *
 * @package WpCafe\Dashboard\Reports
 * @since 1.0.0
 */
class Customers_Report {

    /**
     * Get customers data from WooCommerce orders.
     *
     * @since 1.0.0
     * @param string $period     Time period for data.
     * @param string $branch     Branch filter.
     * @param string $start_date Custom start date (Y-m-d format) - required for custom period.
     * @param string $end_date   Custom end date (Y-m-d format) - required for custom period.
     * @return array Customers data with change percentage.
     */
    public function get_customers_data( $start_date = '', $end_date = '' ) {

        // Get current period unique customers from WooCommerce orders.
        $current_customers = $this->get_total_customers( $start_date, $end_date );

        // Get previous period customers.
        $prev_date_range = Date_Utility::get_previous_period_range( 'month', $start_date, $end_date );
        $prev_customers = $this->get_total_customers( $prev_date_range['start'], $prev_date_range['end'] );

        $change_percentage = $prev_customers > 0 ? ( ( $current_customers - $prev_customers ) / $prev_customers ) * 100 : 0;
        $change_direction  = $change_percentage >= 0 ? 'up' : 'down';

        return array(
            'total'             => $current_customers,
            'change_percentage' => round( abs( $change_percentage ), 1 ),
            'change_direction'  => $change_direction,
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
                WHERE branch_meta.post_id = p.ID 
                AND branch_meta.meta_key = '_branch_id' 
                AND branch_meta.meta_value = %s
            )",
            $branch
        );
    }

    /**
     * Get total customers in a date range.
     *
     * @since 1.0.0
     * @param string $start_date Start date.
     * @param string $end_date End date.
     * @return int Total customers in the date range.
     */ 
    public function get_total_customers( $start_date, $end_date ) {

        if ( ! function_exists( 'wc_get_orders' ) ) {
            return 0;
        }

        $orders = wc_get_orders( array(
            'limit'        => -1,
            'status'       => array( 'wc-completed', 'wc-processing', 'wc-on-hold' ),
            'date_created' => $start_date . '...' . $end_date,
            'return'       => 'ids',
        ) );

        $customer_ids = [];

        foreach ( $orders as $order_id ) {
            $order    = wc_get_order( $order_id );

            // Check if the order object is valid and is not a refund
            if ( ! $order || ! is_a( $order, 'WC_Order' ) || is_a( $order, 'WC_Order_Refund' ) ) {
                continue;
            }

            $customer = new WC_Customer( $order->get_customer_id() );

            if ( $customer && $customer->get_id() ) {
                $customer_ids[] = $customer->get_id();
            } else {
                // Handle guest customer by email
                $customer_ids[] = 'guest_' . $order->get_billing_email();
            }
        }

        // Unique customers (registered + guest)
        $unique_customers = array_unique( $customer_ids );

        return count( $unique_customers );
    }
} 
