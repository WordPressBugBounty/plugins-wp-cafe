<?php
namespace WpCafe\Integrations\Pointics;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Re-checks a booking's points whenever the booking or its order changes.
 *
 * @package WpCafe/Integrations/Pointics
 */
class Reservation_Events_Listener implements Hookable_Service_Contract {
    /**
     * Register hooks.
     *
     * @return void
     */
    public function register() {
        add_action( 'wpcafe_after_reservation_create', [ $this, 'on_created' ] );
        add_action( 'wpcafe_after_reservation_status_changed', [ $this, 'on_status_changed' ] );
        add_action( 'wpcafe_after_reservation_cancelled', [ $this, 'on_cancelled' ] );
        add_action( 'wpcafe_after_reservation_update', [ $this, 'on_updated' ] );
        add_action( 'wpcafe_reservation_status_synced', [ $this, 'on_synced' ], 10, 4 );
        add_action( 'woocommerce_order_refunded', [ $this, 'on_order_refunded' ] );
        // Before, not after: the booking's meta still says who holds its points.
        add_action( 'before_delete_post', [ $this, 'on_deleted' ] );
        // Runs after wpcafe_after_reservation_create, so the award is already written.
        add_filter( 'wpcafe_reservation_create_response', [ $this, 'add_points_earned' ], 10, 2 );
    }

    /**
     * What the new booking actually earned, for the thank-you screen. Only for
     * the account it earned to: a guest typing a known email must not learn it has one.
     *
     * @param array                            $payload     Create response data.
     * @param \WpCafe\Models\Reservation_Model $reservation Booking.
     * @return array
     */
    public function add_points_earned( $payload, $reservation ) {
        $reservation_id = is_object( $reservation ) ? (int) $reservation->id : 0;
        $user_id        = get_current_user_id();
        $own            = $reservation_id > 0 && $user_id > 0 && ( new Reservation_Account_Resolver() )->resolve( $reservation ) === $user_id;

        $payload['points_earned'] = $own ? ( new Reservation_Points_Reconciler() )->booking_totals( $reservation_id )['net'] : 0;

        return $payload;
    }

    /**
     * Earn points for a booking that was just made.
     *
     * @param \WpCafe\Models\Reservation_Model $reservation Booking.
     */
    public function on_created( $reservation ) {
        $this->run( $reservation, 'created' );
    }

    /**
     * Earn or take back points when a booking changes status.
     *
     * @param \WpCafe\Models\Reservation_Model $reservation Booking.
     */
    public function on_status_changed( $reservation ) {
        $this->run( $reservation, 'status_changed' );
    }

    /**
     * Take back points when a booking is cancelled.
     *
     * @param \WpCafe\Models\Reservation_Model $reservation Booking.
     */
    public function on_cancelled( $reservation ) {
        $this->run( $reservation, 'cancelled' );
    }

    /**
     * Re-check points when a booking is edited.
     *
     * @param \WpCafe\Models\Reservation_Model $reservation Booking.
     */
    public function on_updated( $reservation ) {
        $this->run( $reservation, 'updated' );
    }

    /**
     * Deleting a booking is permanent, so its points go too.
     *
     * @param int $post_id Post being deleted.
     * @return void
     */
    public function on_deleted( $post_id ) {
        if ( 'wpc_reservation' !== get_post_type( $post_id ) ) {
            return;
        }

        try {
            ( new Reservation_Points_Reconciler() )->take_back_all( (int) $post_id, 'deleted' );
        } catch ( \Throwable $e ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( sprintf( '[WPCafe] Deleted booking %d kept its points: %s', $post_id, $e->getMessage() ) );
        }
    }

    /**
     * @param int    $reservation_id Reservation id.
     * @param string $old_status     Before.
     * @param string $new_status     After.
     * @param string $source         payment_complete or order_status.
     * @return void
     */
    public function on_synced( $reservation_id, $old_status, $new_status, $source ) {
        $this->reconcile( (int) $reservation_id, (string) $source );
    }

    /**
     * A partial refund changes the amount a paid booking is priced on.
     *
     * @param int $order_id Order id.
     * @return void
     */
    public function on_order_refunded( $order_id ) {
        $order          = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;
        $reservation_id = $order ? absint( $order->get_meta( 'reservation_id' ) ) : 0;

        if ( $reservation_id ) {
            $this->reconcile( $reservation_id, 'order_refunded' );
        }
    }

    /**
     * @param mixed  $reservation Booking model.
     * @param string $reason      Why.
     * @return void
     */
    private function run( $reservation, $reason ) {
        // Not empty(): the model's magic properties always read as unset there.
        $reservation_id = is_object( $reservation ) ? (int) $reservation->id : 0;

        if ( $reservation_id > 0 ) {
            $this->reconcile( $reservation_id, $reason );
        }
    }

    /**
     * Points never break a booking or a checkout: a failure is logged and the caller goes on.
     *
     * @param int    $reservation_id Reservation id.
     * @param string $reason         Why.
     * @return void
     */
    private function reconcile( int $reservation_id, string $reason ) {
        try {
            ( new Reservation_Points_Reconciler() )->reconcile( $reservation_id, $reason );
        } catch ( \Throwable $e ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( sprintf( '[WPCafe] Booking %d points not updated (%s): %s', $reservation_id, $reason, $e->getMessage() ) );
        }
    }
}
