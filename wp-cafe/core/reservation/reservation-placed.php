<?php
namespace WpCafe\Reservation;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Models\Reservation_Model;

defined( 'ABSPATH' ) || exit;

/**
 * Fires `wpcafe_reservation_placed` once a booking is really made.
 *
 * Pay online: when its checkout order is placed, so leaving checkout never
 * counts. Anything else: right after the booking is created.
 * `wpcafe_after_reservation_create` keeps its own timing for emails and
 * integrations.
 *
 * @package WpCafe\Reservation
 */
class Reservation_Placed implements Hookable_Service_Contract {
    /**
     * Register hooks.
     *
     * @return void
     */
    public function register() {
        add_action( 'wpcafe_after_reservation_create', [ $this, 'on_create' ], 5 );
        add_action( 'woocommerce_new_order', [ $this, 'on_new_order' ], 5, 2 );
    }

    /**
     * Bookings that skip checkout are placed as soon as they exist.
     *
     * @param mixed $reservation Created reservation.
     * @return void
     */
    public function on_create( $reservation ) {
        if ( ! $reservation instanceof Reservation_Model || 'wc' === $reservation->payment_method ) {
            return;
        }

        do_action( 'wpcafe_reservation_placed', $reservation );
    }

    /**
     * Pay-online bookings are placed with their checkout order.
     *
     * @param int   $order_id Order id.
     * @param mixed $order    Order object.
     * @return void
     */
    public function on_new_order( $order_id, $order = null ) {
        if ( ! $order instanceof \WC_Order && function_exists( 'wc_get_order' ) ) {
            $order = wc_get_order( $order_id );
        }

        $reservation = wpc_get_order_online_reservation( $order );

        if ( $reservation ) {
            do_action( 'wpcafe_reservation_placed', $reservation );
        }
    }
}
