<?php
namespace WpCafe\Models;

use WpCafe\Database\Post_Model;

class Reservation_Model extends Post_Model {
    /**
     * Store fillable attributes
     *
     * @var array
     */
    protected array $fillable = [
        'name'          => '',
        'email'         => '',
        'phone'         => '',
        'date'          => '',
        'start_time'    => '',
        'end_time'      => '',
        'total_guest'   => '',
        'table_name'    => '',
        'status'        => '',
        'branch_id'     => '',
        'branch_name'   => '',
        'notes'         => '',
        'invoice'       => '',
        'booking_amount' => '',
        'total_price'   => '',
        'currency'      => '',
        'payment_method'=> '',
        'payment_intent'=> '',
        'woo_order_id'  => '',
        'food_order'    => 'no',
        'seats'         => '',
        'custom_fields' => [],
    ];

    /**
     * Get the post type
     *
     * @return  string  Post type
     */
    public function get_post_type() {
        return 'wpc_reservation';
    }

    /**
     * Get the total price of the reservation
     *
     * @return  float   Total price of the reservation
     */
    public function get_total_price() {
        $total_price = (float) $this->total_price;
        $items       = $this->get_items();

        if ( ! empty( $items ) ) {
            foreach ( $items as $item ) {
                $price    = (float) $item->price;
                $quantity = (int) $item->quantity;
                $total_price += $price * $quantity;
            }
        }

        return $total_price;
    }

    /**
     * Get reservation items associated with this reservation.
     *
     * Retrieves all Reservation_Item_Model instances that are linked to this reservation
     * by matching the 'reservation_id' field with the current reservation's ID.
     *
     * @return array List of Reservation_Item_Model objects for this reservation.
     */
    public function get_items() {
        return Reservation_Item_Model::where( 'reservation_id', $this->id );
    }

    /**
     * Get seats associated with this reservation
     *
     * @return array Array of Seat_Plan_Model instances
     */
    public function get_seats() {
        $seat_ids = $this->seats ?? [];

        if ( empty( $seat_ids ) || ! is_array( $seat_ids ) ) {
            return [];
        }

        // Check if wpcafe-pro is active and has the Seat_Plan_Model
        if ( ! class_exists( 'WpCafePro\Models\Seat_Plan_Model' ) ) {
            return [];
        }

        $seats = [];
        foreach ( $seat_ids as $seat_id ) {
            $seat = \WpCafePro\Models\Seat_Plan_Model::find( $seat_id );
            if ( $seat ) {
                $seats[] = $seat;
            }
        }

        return $seats;
    }

    /**
     * Build a meta query array for searching qrcode posts by a given value.
     *
     * Searches the 'table_name', 'table_id', and 'page_url' meta fields for the provided value.
     *
     * @param string $search_value The value to search for in the qrcode meta fields.
     * @return array The meta query array for use in WP_Query or similar.
     */
    public function search_query( $search_value ) {
        $meta_query = [
            'relation' => 'OR',
            [
                'key'     => 'name',
                'value'   => $search_value,
                'compare' => 'LIKE',
            ],
            [
                'key'     => 'email',
                'value'   => $search_value,
                'compare' => 'LIKE',
            ],
            [
                'key'     => 'phone',
                'value'   => $search_value,
                'compare' => 'LIKE',
            ],
            [
                'key'     => 'date',
                'value'   => $search_value,
                'compare' => 'LIKE',
            ],
            [
                'key'     => 'branch_id',
                'value'   => $search_value,
                'compare' => 'LIKE',
            ],
            [
                'key'     => 'start_time',
                'value'   => $search_value,
                'compare' => 'LIKE',
            ],
            [
                'key'     => 'end_time',
                'value'   => $search_value,
                'compare' => 'LIKE',
            ],
        ];

        return $meta_query;
    }

    /**
     * Build a meta query array for filtering posts by a given value.
     *
     * This method should be implemented in the concrete model class to return
     * a meta query array suitable for use in WP_Query or similar, based on the
     * provided filter value.
     *
     * @param mixed $filter_value The value to filter the posts by.
     * @return array The meta query array for use in WP_Query or similar.
     */
    public function filter_query( $filters ) {
        $meta_query = [ 'relation' => 'AND' ];

        // email filter (used to scope reservations to current logged-in customer)
        if ( ! empty( $filters['email'] ) ) {
            $meta_query[] = [
                'key'     => 'email',
                'value'   => $filters['email'],
                'compare' => '=',
            ];
        }

        // branch filter
        if ( ! empty( $filters['branch'] ) ) {
            $meta_query[] = [
                'key'     => 'branch_id',
                'value'   => $filters['branch'],
                'compare' => 'LIKE',
            ];
        }

        // date filter (exact date)
        if ( ! empty( $filters['date'] ) ) {
            $meta_query[] = [
                'key'     => 'date',
                'value'   => $filters['date'],
                'compare' => '=',
            ];
        }

        // date filter (exact date)
        if ( ! empty( $filters['food_order'] ) ) {
            $meta_query[] = [
                'key'     => 'food_order',
                'value'   => $filters['food_order'],
                'compare' => '=',
            ];
        }

        // date range filter
        if ( ! empty( $filters['date_range'] ) && is_array( $filters['date_range'] ) ) {
            $meta_query[] = [
                'key'     => 'date',
                'value'   => $filters['date_range'],
                'compare' => 'BETWEEN',
                'type'    => 'DATE',
            ];
        }

        return $meta_query;
    }

    /**
     * Convert time to timestamp
     *
     * Handles both DateTime objects and string timestamps.
     *
     * @param string $date The date to use for timestamp conversion.
     * @param string|DateTime $time The time to convert (DateTime object or string).
     * @return int The Unix timestamp.
     */
    private static function convert_time_to_timestamp( $date, $time ) {
        if ( is_object( $time ) && method_exists( $time, 'format' ) ) {
            return strtotime( $date . ' ' . $time->format('h:i A') );
        }
        return strtotime( $date . ' ' . $time );
    }

    /**
     * Get the total guest by date and time
     *
     * @param string $date The date to get the total guest for.
     * @param string $start_time The start time to get the total guest for.
     * @param string $end_time The end time to get the total guest for.
     * @param int|string $branch_id Optional branch ID to filter by.
     * @return int The total guest for the date and time.
     */
    public static function get_total_guest_by_date_time( $date, $start_time, $end_time, $branch_id = '' ) {
        // Convert times to timestamps
        $start_time = self::convert_time_to_timestamp( $date, $start_time );
        $end_time   = self::convert_time_to_timestamp( $date, $end_time );

        // Ensure blocking statuses is an array
        $blocking_statuses = wpc_get_option( 'block_timeslot_statuses', ['confirmed'] );
        $blocking_statuses = self::expand_with_wc_equivalents( $blocking_statuses );

        $meta_query = [
            'relation' => 'AND',
            [
                'key' => 'date',
                'value' => $date,
                'compare' => '=',
            ],
            [
                'key' => 'start_time',
                'value' => $end_time,
                'compare' => '<=',
                'type' => 'NUMERIC',
            ],
            [
                'key' => 'end_time',
                'value' => $start_time,
                'compare' => '>=',
                'type' => 'NUMERIC',
            ],
        ];

        // Add branch filter if provided
        if ( ! empty( $branch_id ) ) {
            $meta_query[] = [
                'key' => 'branch_id',
                'value' => $branch_id,
                'compare' => '=',
            ];
        }

        $reservations = get_posts( [
            'post_type' => 'wpc_reservation',
            'post_status' => $blocking_statuses,
            'numberposts' => -1,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- required for report/filter functionality
            'meta_query' => $meta_query,
        ] );

        $total_guest = 0;

        foreach ( $reservations as $reservation ) {
            $guest_count = get_post_meta( $reservation->ID, 'total_guest', true );
            $total_guest += intval( $guest_count );
        }
        return $total_guest;
    }

    /**
     * Validate guest capacity for a reservation by checking if the requested number of guests exceeds available capacity
     *
     * @param int    $total_guest The total number of guests for the reservation
     * @param string $date        The reservation date
     * @param int    $start_time  The start time (timestamp)
     * @param int    $end_time    The end time (timestamp)
     * @param int    $branch_id   Optional branch ID to filter by
     * @return true|WP_Error True if valid, WP_Error if exceeds capacity
     */
    public static function validate_guest_capacity( $total_guest, $date, $start_time, $end_time, $branch_id = '' ) {
        $total_guest = intval( $total_guest );

        $total_capacity  = wpc_get_reservation_capacity( $branch_id );
        $booked_capacity = self::get_total_guest_by_date_time( $date, $start_time, $end_time, $branch_id );
        $available_capacity = $total_capacity - $booked_capacity;

        if ( $total_guest > $available_capacity ) {
            /* translators: %1$d: requested number of guests, %2$d: available capacity */
            return new \WP_Error( 'insufficient_capacity', sprintf( __( 'Cannot create reservation. Requested %1$d guest(s) exceeds available capacity of %2$d for this time slot.', 'wp-cafe' ), $total_guest, $available_capacity ) );
        }

        return true;
    }

    /**
     * Get booked seat IDs for a specific time slot
     *
     * Finds all reservations that overlap with the given time slot
     * and returns all seat IDs that are booked during that period.
     *
     * @param string $date The reservation date
     * @param string $start_time The start time (can be timestamp or time string)
     * @param string $end_time The end time (can be timestamp or time string)
     * @param int $branch_id The branch ID to filter by
     * @return array Array of unique booked seat IDs
     */
    public static function get_booked_seats_for_time_slot( $date, $start_time, $end_time, $branch_id ) {
        // Convert time to timestamps for comparison
        $start_timestamp = is_numeric( $start_time ) ? $start_time : strtotime( $date . ' ' . $start_time );
        $end_timestamp   = is_numeric( $end_time ) ? $end_time : strtotime( $date . ' ' . $end_time );

        // Build meta query for overlapping reservations
        $meta_query = [
            'relation' => 'AND',
            [
                'key'     => 'date',
                'value'   => $date,
                'compare' => '=',
            ],
            [
                'key'     => 'start_time',
                'value'   => $end_timestamp,
                'compare' => '<=',
                'type'    => 'NUMERIC',
            ],
            [
                'key'     => 'end_time',
                'value'   => $start_timestamp,
                'compare' => '>=',
                'type'    => 'NUMERIC',
            ],
        ];

        // Add branch filter (filter by location) if provided
        if ( ! empty( $branch_id ) && $branch_id !== 'undefined' ) {
            $meta_query[] = [
                'key'     => 'branch_id',
                'value'   => $branch_id,
                'compare' => '=',
            ];
        }

        $statuses_for_blocking_seats = wpc_get_option('block_timeslot_statuses', []);
        if ( ! is_array( $statuses_for_blocking_seats ) || empty( $statuses_for_blocking_seats ) ) {
            $statuses_for_blocking_seats = ['confirmed'];
        }
        $statuses_for_blocking_seats = self::expand_with_wc_equivalents( $statuses_for_blocking_seats );

        $posts = get_posts([
            'post_type'     => 'wpc_reservation',
            'post_status'   => $statuses_for_blocking_seats ,
            'numberposts'   => -1,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- required for report/filter functionality
            'meta_query'    => $meta_query,
        ]);

        // Convert posts to model Reservation_Model instances
        $reservations = array_map( function( $post ) {
            return new self( $post );
        }, $posts );

        // Collect all booked seat IDs
        $booked_seat_ids = [];

        foreach ( $reservations as $reservation ) {
            $seats = $reservation->seats ?? [];

            if ( ! empty( $seats ) && is_array( $seats ) ) {
                $booked_seat_ids = array_merge( $booked_seat_ids, $seats );
            }
        }

        // Return unique seat IDs
        return array_values( array_unique( $booked_seat_ids ) );
    }

    /**
     * Expand a list of reservation statuses to also include their WooCommerce
     * order-status equivalents.
     *
     * @param array $statuses Logical reservation statuses (`pending`, `confirmed`, `cancelled`, ...).
     * @return array Statuses including WC equivalents, deduplicated.
     */
    private static function expand_with_wc_equivalents( $statuses ) {
        if ( ! is_array( $statuses ) || empty( $statuses ) ) {
            return $statuses;
        }

        $wc_equivalents = [
            'pending'   => ['pending', 'on-hold', 'processing'],
            'confirmed' => ['completed'],
            'cancelled' => ['refunded', 'failed'],
        ];

        $expanded = $statuses;
        foreach ( $statuses as $status ) {
            if ( isset( $wc_equivalents[ $status ] ) ) {
                $expanded = array_merge( $expanded, $wc_equivalents[ $status ] );
            }
        }

        return array_values( array_unique( $expanded ) );
    }
}