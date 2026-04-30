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

        add_action( 'woocommerce_payment_complete', [ $this, 'handle_payment_complete' ], 10, 1 );

        add_action( 'woocommerce_order_status_changed', [ $this, 'handle_order_status_changed' ], 10, 3 );

        add_filter( 'woocommerce_checkout_fields', [ $this, 'prefill_checkout_fields' ] );

        add_action( 'woocommerce_cart_calculate_fees', [ $this, 'add_booking_amount_fee' ], 20, 1 );
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
            $cart_item['data']->set_price( $reservation->get_total_price() );
        }
        
        return $cart_item;
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
     * Add reservation booking amount as a WooCommerce fee
     *
     * @param WC_Cart $cart The WooCommerce cart object
     * @return void
     */
    public function add_booking_amount_fee( $cart ) {
        if ( ! function_exists( 'WC' ) || $cart->is_empty() || ! WC()->session ) {
            return;
        }

        $session_data = WC()->session->get( 'wpc_reservation_data' );

        if ( empty( $session_data['reservation_id'] ) ) {
            return;
        }

        $reservation    = new Reservation_Model( $session_data['reservation_id'] );
        $total_booking_amount = $reservation->total_price;

        $total_booking_float = (float) $total_booking_amount;
        if ( $total_booking_float > 0 ) {
            $cart->add_fee( __( 'Reservation Booking Amount', 'wp-cafe' ), $total_booking_float );
        }
    }
}
