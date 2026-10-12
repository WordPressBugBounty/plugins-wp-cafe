<?php
namespace WpCafe\Integrations\Pointics;

if ( ! defined( 'ABSPATH' ) ) exit;

use Pointics\Earn_Preview\Values\Earn_Quote;
use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Models\Reservation_Model;

/**
 * Order screens count the booking's points alongside the order's own.
 *
 * Both rules pay: the order rule for what was charged, the booking rule for
 * the table. Someone reading one receipt should see the whole figure.
 *
 * @package WpCafe/Integrations/Pointics
 */
class Booking_Order_Points_Line implements Hookable_Service_Contract {
    /**
     * Register hooks.
     *
     * @return void
     */
    public function register() {
        add_filter( 'pointics_order_points_summary', [ $this, 'summary' ], 10, 2 );
        add_filter( 'pointics_cart_earn_quote', [ $this, 'cart_quote' ], 10, 3 );
    }

    /**
     * @param array|null $summary Pointics' own summary for the order.
     * @param object     $order   Order.
     * @return array|null
     */
    public function summary( $summary, $order ) {
        $reservation_id = is_object( $order ) && method_exists( $order, 'get_meta' ) ? absint( $order->get_meta( 'reservation_id' ) ) : 0;
        $reservation    = $reservation_id && 'wpc_reservation' === get_post_type( $reservation_id ) ? Reservation_Model::find( $reservation_id ) : null;

        if ( ! $reservation ) {
            return $summary;
        }

        $user_id = ( new Reservation_Account_Resolver() )->resolve( $reservation );
        // Booking card only: a referral earn sits on the same booking but belongs to the inviter.
        $totals  = ( new Reservation_Points_Reconciler() )->booking_totals( $reservation_id );
        $net     = $totals['net'];
        $earned  = $totals['earned'];
        $effect  = Reservation_Points_Reconciler::status_effect( (string) get_post_status( $reservation_id ) );
        $lost    = 'revoke' === $effect ? max( 0, $earned - $net ) : 0;

        // What the order rule paid for this same order, if anything.
        $order_points = is_array( $summary ) && 'guest' !== $summary['state'] && 'pending' !== $summary['state']
            ? (int) $summary['points']
            : 0;

        $shape = [
            'points'     => $net + $order_points,
            'state'      => 'earned',
            'earned'     => $earned + $order_points,
            // Only a booking in a revoke status really lost points. One waiting
            // to earn holds nothing yet, which is not a refund, and Pointics
            // prints its refund sentence off any non-zero value here.
            'taken_back' => $lost,
            'note'       => $order_points > 0
                ? __( '(incl. table booking)', 'wp-cafe' )
                : __( '(table booking)', 'wp-cafe' ),
            // No-break space, so a narrow totals column wraps before "booking", not inside it.
            // translators: %s: reservation invoice number.
            'admin_note' => str_replace( ' ', "\u{00A0}", sprintf( __( 'booking #%s', 'wp-cafe' ), $reservation->invoice ) ),
            // Stable flag for code; the notes above are translated display text.
            'booking'    => true,
        ];

        if ( $net > 0 || $lost > 0 ) {
            return $shape;
        }

        if ( $user_id <= 0 ) {
            return array_merge( $shape, [ 'state' => 'guest', 'points' => 0, 'earned' => 0, 'note' => '', 'admin_note' => '', 'booking' => false ] );
        }

        // Settled either way and holding nothing: the order's own line is the whole story.
        if ( 'hold' !== $effect ) {
            return $summary;
        }

        // Zeroed, else Pointics prints the refund sentence instead of this line.
        return array_merge(
            $shape,
            [
                'state'      => 'pending',
                'points'     => $this->preview( $reservation, $user_id ) + $order_points,
                'earned'     => 0,
                'taken_back' => 0,
                // The order may be paid already; the booking's status is what the points wait on.
                'pending_note' => __( 'when the booking is confirmed', 'wp-cafe' ),
            ]
        );
    }

    /**
     * Cart and checkout quote the booking too, so the customer is not shown
     * half of what the same payment is about to earn them.
     *
     * Display only. The booking's award is written by the reconciler when the
     * booking settles, never from here, so the two cannot double up.
     *
     * @param \Pointics\Earn_Preview\Values\Earn_Quote|null $quote   The order rules' quote.
     * @param float                                         $amount  Cart total it was priced from.
     * @param string                                        $trigger Host trigger id.
     * @return \Pointics\Earn_Preview\Values\Earn_Quote|null
     */
    public function cart_quote( $quote, $amount, $trigger ) {
        if ( ! $quote || 'woocommerce.order_paid' !== $trigger ) {
            return $quote;
        }

        $reservation = $this->cart_reservation();

        if ( ! $reservation ) {
            return $quote;
        }

        // 0 is fine: a guest booking with no account is quoted what it would earn.
        $booking = $this->preview( $reservation, ( new Reservation_Account_Resolver() )->resolve( $reservation ) );

        if ( $booking <= 0 ) {
            return $quote;
        }

        // Flat: a booking's award does not scale with what is in the cart.
        return new Earn_Quote( $quote->amount_points, $quote->flat_points + $booking, $quote->requires_login );
    }

    /**
     * The booking the current cart is paying for, or null.
     *
     * @return Reservation_Model|null
     */
    private function cart_reservation() {
        $session = function_exists( 'WC' ) && WC()->session ? (array) WC()->session->get( 'wpc_reservation_data' ) : [];
        $id      = absint( $session['reservation_id'] ?? 0 );

        return $id && 'wpc_reservation' === get_post_type( $id ) ? Reservation_Model::find( $id ) : null;
    }

    /**
     * Points the booking will earn once it reaches an earning status.
     *
     * @param Reservation_Model $reservation Booking.
     * @param int               $user_id     Account.
     * @return int
     */
    private function preview( $reservation, $user_id ) {
        return ( new Reservation_Points_Calculator() )->total_for( (int) $user_id, Reservation_Points_Calculator::input_for_reservation( $reservation ) );
    }
}
