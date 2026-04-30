<?php
/**
 * Reservations Report
 *
 * Handles all reservations-related business logic and data retrieval.
 *
 * @package WpCafe\Dashboard\Reports
 * @since 1.0.0
 */

namespace WpCafe\Dashboard\Reports;
use WpCafe\Models\Reservation_Model;
use WpCafe\Resources\Reservation_Dashboard_Resource;

/**
 * Reservations Report Class
 *
 * Responsible for managing wpc_reservation custom post type data.
 *
 * @package WpCafe\Dashboard\Reports
 * @since 1.0.0
 */
class Reservations_Report {

    /**
     * Get reservations data from wpc_reservation custom post type.
     *
     * @since 1.0.0
     * @param string $start_date Custom start date (Y-m-d format) - required for custom period.
     * @param string $end_date   Custom end date (Y-m-d format) - required for custom period.
     * @param string $branch     Branch ID filter ('all' for all branches).
     * @return array Reservations data with change percentage.
     */
    public function get_reservations_data( $start_date = '', $end_date = '', $branch = 'all' ) {
        // Get current period reservations.
        $current_reservations = $this->get_total_reservations( $start_date, $end_date, $branch );

        // Get previous period reservations.
        $prev_date_range = Date_Utility::get_previous_period_range('month', $start_date, $end_date );
        $prev_reservations = $this->get_total_reservations( $prev_date_range['start'], $prev_date_range['end'], $branch );
        
        $change_percentage = $prev_reservations > 0 ? ( ( $current_reservations - $prev_reservations ) / $prev_reservations ) * 100 : 0;
        $change_direction  = $change_percentage >= 0 ? 'up' : 'down';

        return array(
            'total'             => $current_reservations,
            'change_percentage' => round( abs( $change_percentage ), 1 ),
            'change_direction'  => $change_direction,
        );
    }

    /**
     * Get reservations list with pagination and filters.
     *
     * @since 1.0.0
     * @param int    $per_page   Number of items per page.
     * @param int    $page       Current page number.
     * @param string $status     Reservation status filter.
     * @param string $branch     Branch filter.
     * @param string $period     Time period for data.
     * @param string $start_date Custom start date (Y-m-d format) - required for custom period.
     * @param string $end_date   Custom end date (Y-m-d format) - required for custom period.
     * @return array Reservations list with pagination info.
     */
    public function get_reservations_list( $per_page = 10, $page = 1, $status = '', $branch = 'all' ) {
        $args = [
            'posts_per_page' => $per_page,
            'paged'          => $page,
        ];

        if ( ! empty( $status ) ) {
            $args['post_status'] = $status;
        } else {
            $args['post_status'] = 'any';
        }

        if ( $branch && 'all' !== $branch ) {
            $args['filters'] = [
                'branch' => $branch,
            ];
        }

        $data = Reservation_Model::paginate( $args );

        return Reservation_Dashboard_Resource::collection( $data['items'] );
    }

    /**
     * Get reservation status label.
     *
     * @since 1.0.0
     * @param string $status Reservation status.
     * @return string Status label.
     */
    private function get_reservation_status_label( $status ) {
        $status_labels = array(
            'publish'   => 'Confirmed',
            'pending'   => 'Pending',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
            'draft'     => 'Draft',
            'trash'     => 'Trash',
        );

        return isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : ucfirst( $status );
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
     * Get total reservations.
     *
     * @since 1.0.0
     * @param string $start_date Start date (Y-m-d format).
     * @param string $end_date   End date (Y-m-d format).
     * @param string $branch     Branch ID filter ('all' for all branches).
     * @return int             Total reservations.
     */
    public function get_total_reservations( $start_date, $end_date, $branch = 'all' ) {
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

        return count( $posts );
    }
} 
