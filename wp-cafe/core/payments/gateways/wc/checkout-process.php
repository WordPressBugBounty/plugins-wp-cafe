<?php
namespace WpCafe\Payments\Gateways\WC;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Models\Reservation_Model;
/**
 * WC Checkout Process
 *
 * @package WpCafe/Payments
 */
class Checkout_Process implements Hookable_Service_Contract {
    /**
     * Register hooks
     *
     * @return  void
     */
    public function register() {
        add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'transfer_cart_item_data_to_order' ], 10, 4 );

        add_filter( 'woocommerce_get_cart_item_from_session', [ $this, 'modify_cart_item_price' ], 10, 2 );

        // Charge the booking fee on a reservation-with-food cart. That flow goes
        // straight to checkout with only the food in the cart (it never runs the
        // generic-product payment path), so without this the booking is free.
        add_action( 'woocommerce_cart_calculate_fees', [ $this, 'add_reservation_booking_fee' ] );

        add_action( 'woocommerce_payment_complete', [ $this, 'handle_payment_complete' ], 10, 1 );

        add_action( 'woocommerce_order_status_changed', [ $this, 'handle_order_status_changed' ], 10, 3 );

        add_filter( 'woocommerce_checkout_fields', [ $this, 'prefill_checkout_fields' ] );

        /*
         * Our reservation product is already priced to the deposit, so the Deposet
         * plugin must not apply its own deposit again. We turn Deposet off on two
         * occasions: when the cart total is calculated (this also runs when the
         * customer clicks "Place order", which is where the wrong price slipped in
         * before), and when the checkout page is drawn so its deposit box is gone.
         */
        add_action( 'woocommerce_before_calculate_totals', [ $this, 'suppress_deposet_checkout_ui' ], 1 );
        add_action( 'woocommerce_before_checkout_form', [ $this, 'suppress_deposet_checkout_ui' ], 5 );

        /*
         * Show the booking the customer made on the order-received and "my
         * account" order pages. At checkout the card comes from the session, but
         * that is cleared once the order exists, so here we rebuild it read-only
         * from the reservation saved on the order. Priority 5 keeps it above the
         * payment split below.
         */
        add_action( 'woocommerce_order_details_after_order_table', [ $this, 'display_reservation_summary_on_order' ], 5, 1 );

        /*
         * Show how the reservation payment splits up (full price, paid now, owed
         * later) on the order-received and "my account" order pages, so a partial
         * payment never looks like the customer underpaid.
         */
        add_action( 'woocommerce_order_details_after_order_table', [ $this, 'display_partial_payment_summary' ], 10, 1 );

        // Show the same split in the checkout totals so the customer sees the
        // full price and the balance owed before they pay.
        add_action( 'woocommerce_review_order_after_order_total', [ $this, 'display_checkout_partial_rows' ], 10 );
    }

    /**
     * Modify the cart item price
     *
     * @param array $cart_item
     * @param array $session_data
     * @return array
     */
    public function modify_cart_item_price( $cart_item, $session_data ) {
        if ( isset( $session_data['reservation_id'] ) ) {
            $cart_item['reservation_id'] = $session_data['reservation_id'];

            $reservation = new Reservation_Model( $session_data['reservation_id'] );
            /*
             * Price this line as the booking fee only (deposit when partial
             * payment is on, otherwise the full booking total). Any food is added
             * as its own cart lines, so this must not include it — get_booking_charge()
             * excludes food, whereas get_chargeable_amount() would double-count it.
             */
            $cart_item['data']->set_price( $reservation->get_booking_charge() );
        }

        return $cart_item;
    }

    /**
     * Add the reservation booking amount as a cart fee.
     *
     * A reservation-with-food cart is built from the food the customer added in
     * the booking form and then redirected straight to checkout, so it never
     * runs the generic-product path that prices a plain reservation. Without a
     * fee the booking is collected for free.
     *
     * If a generic reservation line is already present (the plain-reservation
     * path), it carries the booking itself, so we skip the fee to avoid charging
     * twice. The amount is booking-only — food is its own cart lines.
     *
     * @param \WC_Cart $cart The cart being calculated.
     * @return void
     */
    public function add_reservation_booking_fee( $cart ) {
        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return;
        }

        $session_data = WC()->session->get( 'wpc_reservation_data' );
        if ( empty( $session_data['reservation_id'] ) ) {
            return;
        }

        // A generic reservation line already carries the booking — don't double it.
        foreach ( $cart->get_cart() as $cart_item ) {
            if ( ! empty( $cart_item['reservation_id'] ) ) {
                return;
            }
        }

        $reservation = new Reservation_Model( $session_data['reservation_id'] );
        $amount      = $reservation->get_booking_charge();

        if ( $amount > 0 ) {
            $cart->add_fee( __( 'Reservation', 'wp-cafe' ), $amount, false );
        }
    }

    /**
     * Handle actions after WooCommerce payment is complete.
     *
     * This function is hooked to 'woocommerce_payment_complete' and updates the order status to 'completed'
     * when payment is successfully processed.
     *
     * @param int $order_id The ID of the WooCommerce order.
     * @return void
     */
    public function handle_payment_complete( $order_id ) {
        $order          = wc_get_order( $order_id );
        $reservation_id = $order->get_meta( 'reservation_id' );
        $reservation    = new Reservation_Model( $reservation_id );
        $reservation->update( [ 'status' => 'confirmed' ] );
    }

    /**
     * Transfer custom cart item data to the WooCommerce order item.
     *
     * This function is hooked to 'woocommerce_checkout_create_order_line_item' and is responsible
     * for transferring any custom data from the cart item to the order item during the checkout process.
     * For example, it can be used to add reservation or intent keys as order item meta.
     *
     * @param WC_Order_Item_Product $item         The order item to which meta data will be added.
     * @param string                $cart_item_key The cart item key.
     * @param array                 $cart_item     The cart item data array.
     * @param WC_Order              $order         The WooCommerce order object.
     * @return void
     */
    public function transfer_cart_item_data_to_order( $item, $cart_item_key, $cart_item, $order ) {
        if ( $order->get_meta( 'reservation_id' ) ) {
            return;
        }
        // Check if we have any custom cart item data
        $reservation_id = $cart_item['reservation_id'] ?? null;

        if ( empty( $reservation_id ) && function_exists( 'WC' ) && WC()->session ) {
            $session_data = WC()->session->get( 'wpc_reservation_data' );
            if ( ! empty( $session_data['reservation_id'] ) ) {
                $reservation_id = $session_data['reservation_id'];
            }
        }

        if ( ! empty( $reservation_id ) ) {
            $order->add_meta_data( 'reservation_id', $reservation_id );
            $order->save();

            $reservation = new Reservation_Model( $reservation_id );
            $reservation->update( [ 'woo_order_id' => $order->get_id() ] );

            /*
             * Save the payment split on the order so staff can see what was paid
             * now and what is still owed. This is only a record for display — we
             * do not create a second order or chase the balance automatically.
             */
            if ( $reservation->is_partial_payment === 'yes' ) {
                $order->update_meta_data( '_wpc_reservation_total', (float) $reservation->total_price );
                $order->update_meta_data( '_wpc_reservation_deposit', (float) $reservation->deposit_value );
                $order->update_meta_data( '_wpc_reservation_remaining', (float) $reservation->remaining_amount );
                $order->save();
            }
        }
    }

    /**
     * Handle order status changes and update reservation status accordingly.
     *
     * This function is triggered when the WooCommerce order status changes.
     * It updates the associated reservation's status to match the new order status.
     * If the new status is 'completed', it sets the reservation status to 'confirmed'.
     *
     * @param int    $order_id   The ID of the WooCommerce order.
     * @param string $old_status The previous status of the order.
     * @param string $new_status The new status of the order.
     * @return void
     */
    public function handle_order_status_changed( $order_id, $old_status, $new_status ) {
        $order          = wc_get_order( $order_id );
        $reservation_id = $order->get_meta( 'reservation_id' );

        if ( empty( $reservation_id ) ) {
            return;
        }

        $reservation = new Reservation_Model( $reservation_id );

        $status_map = [
            'pending'    => 'pending',
            'on-hold'    => 'pending',
            'processing' => 'pending',
            'completed'  => 'confirmed',
            'cancelled'  => 'cancelled',
            'refunded'   => 'cancelled',
            'failed'     => 'cancelled',
        ];

        $mapped_status = $status_map[ $new_status ] ?? null;

        if ( $mapped_status ) {
            $reservation->update( [ 'status' => $mapped_status ] );
        }

        if ( 'cancelled' === $new_status ) {
            do_action( 'wpcafe_order_cancelled', $order_id );
        }
    }

    /**
     * Prefill checkout fields
     *
     * @param array $fields
     * @return array
     */
    public function prefill_checkout_fields( $fields ) {
        if ( WC()->cart && ! empty( WC()->cart->get_cart() ) ) {
            foreach ( WC()->cart->get_cart() as $cart_item ) {
                if ( ! empty( $cart_item['reservation_id'] ) ) {
                    $reservation = new Reservation_Model( $cart_item['reservation_id'] );

                    $fields['billing']['billing_first_name']['default'] = $reservation->name;
                    $fields['billing']['billing_last_name']['default']  = $reservation->name;
                    $fields['billing']['billing_email']['default']      = $reservation->email;
                    $fields['billing']['billing_phone']['default']      = $reservation->phone;
                    break;
                }
            }
        }

        return $fields;
    }

    /**
     * Render a read-only reservation summary on the order pages.
     *
     * The checkout card is driven by the WC session, which is wiped once the
     * order is created. Here we rebuild the same card from the reservation
     * linked to the order so the customer can still see what they booked.
     * `$reservation_context = 'order'` tells the template to drop the Discard
     * button — a placed booking can't be discarded from the receipt.
     *
     * @param \WC_Order $order The order being viewed.
     * @return void
     */
    public function display_reservation_summary_on_order( $order ) {
        if ( ! is_a( $order, 'WC_Order' ) ) {
            return;
        }

        $reservation_id = $order->get_meta( 'reservation_id' );
        if ( empty( $reservation_id ) ) {
            return;
        }

        $reservation = new Reservation_Model( $reservation_id );

        // The model exposes fields through a magic __get without __isset, so
        // empty()/isset() on $reservation->name always reads false. Pull the
        // values into locals first, then test and map them.
        $name       = $reservation->name;
        $date       = $reservation->date;
        $start_time = $reservation->start_time;

        // No usable booking on the model means nothing worth rendering.
        if ( empty( $name ) && empty( $date ) && empty( $start_time ) ) {
            return;
        }

        // The shared template keys off `reservation_date`; the model stores it
        // as `date`. Map across so the same card renders on the receipt.
        $custom_fields    = $reservation->custom_fields;
        $reservation_data = [
            'name'             => $name,
            'email'            => $reservation->email,
            'phone'            => $reservation->phone,
            'reservation_date' => $date,
            'total_guest'      => $reservation->total_guest,
            'start_time'       => $start_time,
            'end_time'         => $reservation->end_time,
            'notes'            => $reservation->notes,
            'branch_name'      => $reservation->branch_name,
            'table_name'       => $reservation->table_name,
            'custom_fields'    => is_array( $custom_fields ) ? $custom_fields : [],
        ];
        $reservation_context = 'order';

        $template_path = wpcafe()->template_directory . '/reservation/reservation-view.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        }
    }

    /**
     * Print the reservation payment breakdown under the order table.
     *
     * The amount the customer paid is only the deposit, so on its own the order
     * total looks like an underpayment. These rows spell out the full price, what
     * was paid today and what is left to pay, using the figures we saved on the
     * order at checkout.
     *
     * @param \WC_Order $order The order being viewed.
     * @return void
     */
    public function display_partial_payment_summary( $order ) {
        if ( ! is_a( $order, 'WC_Order' ) ) {
            return;
        }

        // Only orders paid with a deposit have this saved, so its absence means
        // there is no split to show.
        $remaining = $order->get_meta( '_wpc_reservation_remaining' );

        if ( '' === $remaining || null === $remaining ) {
            return;
        }

        $total   = (float) $order->get_meta( '_wpc_reservation_total' );
        $deposit = (float) $order->get_meta( '_wpc_reservation_deposit' );

        $rows = [
            [ __( 'Reservation total', 'wp-cafe' ), $total, false ],
            [ __( 'Deposit paid today', 'wp-cafe' ), $deposit, true ],
            [ __( 'Balance due at the restaurant', 'wp-cafe' ), (float) $remaining, false ],
        ];

        $html  = '<section class="wpc-reservation-payment">';
        $html .= '<h2 class="woocommerce-column__title">' . esc_html__( 'Your reservation payment', 'wp-cafe' ) . '</h2>';
        $html .= '<table class="woocommerce-table shop_table">';
        foreach ( $rows as [ $label, $amount, $emphasize ] ) {
            $th = $emphasize ? '<th><strong>' . esc_html( $label ) . '</strong></th>' : '<th>' . esc_html( $label ) . '</th>';
            $td = $emphasize ? '<td><strong>' . wp_kses_post( wc_price( $amount ) ) . '</strong></td>' : '<td>' . wp_kses_post( wc_price( $amount ) ) . '</td>';
            $html .= '<tr>' . $th . $td . '</tr>';
        }
        $html .= '</table>';
        $html .= '<p class="wpc-reservation-payment__note">' . esc_html__( 'You paid a deposit today to confirm your booking. The remaining balance is collected at the restaurant.', 'wp-cafe' ) . '</p>';
        $html .= '</section>';

        echo wp_kses_post( $html );
    }

    /**
     * Add the full-price and balance rows to the checkout totals table.
     *
     * The order does not exist yet at checkout, so we read the figures from the
     * reservation kept in the session. The output goes inside WooCommerce's totals
     * table, so each row must be a <tr>.
     *
     * @return void
     */
    public function display_checkout_partial_rows() {
        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return;
        }

        $session_data = WC()->session->get( 'wpc_reservation_data' );
        if ( empty( $session_data['reservation_id'] ) ) {
            return;
        }

        $reservation = new Reservation_Model( $session_data['reservation_id'] );
        if ( $reservation->is_partial_payment !== 'yes' ) {
            return;
        }

        $total     = (float) $reservation->total_price;
        $remaining = (float) $reservation->remaining_amount;

        $rows = [
            __( 'Reservation total', 'wp-cafe' )            => $total,
            __( 'Balance due at the restaurant', 'wp-cafe' ) => $remaining,
        ];

        $html = '';
        foreach ( $rows as $label => $amount ) {
            $html .= '<tr class="wpc-reservation-split"><th>' . esc_html( $label ) . '</th><td>' . wp_kses_post( wc_price( $amount ) ) . '</td></tr>';
        }

        echo wp_kses_post( $html );
    }

    /**
     * Turn the Deposet plugin off for reservation checkouts.
     *
     * Our reservation product is already priced to the deposit. If Deposet runs
     * as well it takes its cut a second time — for example charging 50% of an
     * already-halved $5 and collecting only $2.50 — and shows its own deposit box
     * on top of ours. So on any cart that holds a reservation we remove Deposet's
     * checkout hooks.
     *
     * Deposet attaches these as methods on its own objects. remove_action() needs
     * that exact object to undo them, which we do not have, so we walk the hook
     * list and drop every callback that belongs to a Deposet\Frontend class.
     *
     * @return void
     */
    public function suppress_deposet_checkout_ui() {
        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return;
        }

        // Nothing to do unless this cart is for a reservation.
        $session_data = WC()->session->get( 'wpc_reservation_data' );
        if ( empty( $session_data['reservation_id'] ) ) {
            return;
        }

        // The hooks where Deposet changes the total or prints its deposit UI.
        $hooks = [
            'woocommerce_calculated_total',
            'woocommerce_review_order_before_payment',
            'woocommerce_review_order_after_order_total',
            'woocommerce_checkout_update_order_meta',
            'woocommerce_cart_calculate_fees',
        ];

        global $wp_filter;
        foreach ( $hooks as $hook ) {
            if ( empty( $wp_filter[ $hook ] ) ) {
                continue;
            }
            foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
                foreach ( $callbacks as $id => $cb ) {
                    if ( is_array( $cb['function'] )
                        && is_object( $cb['function'][0] )
                        && 0 === strpos( get_class( $cb['function'][0] ), 'Deposet\\Frontend\\' ) ) {
                        unset( $wp_filter[ $hook ]->callbacks[ $priority ][ $id ] );
                    }
                }
            }
        }
    }
}
