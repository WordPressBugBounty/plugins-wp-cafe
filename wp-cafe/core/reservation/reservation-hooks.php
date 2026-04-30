<?php
namespace WpCafe\Reservation;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Reservation\Email\Triggers\Reservation_Created_Trigger;
use WpCafe\Reservation\Email\Triggers\Reservation_Cancelled_Trigger;
use WpCafe\Reservation\Email\Triggers\Reservation_Confirmed_Trigger;
use WpCafe\Reservation\Email\Triggers\Reservation_Pending_Trigger;
use WpCafe\Reservation\Email\Triggers\Reservation_Updated_Trigger;

/**
 * Reservation Hooks Class
 *
 * Handles WordPress and WooCommerce hooks for reservation functionality
 *
 * @package WpCafe\Reservation
 * @since 1.0.0
 */
class Reservation_Hooks implements Hookable_Service_Contract {

    /**
     * Register all hooks
     *
     * @return void
     */
    public function register(): void {
        add_action( 'woocommerce_before_order_notes', [ $this, 'wpc_display_reservation_info_on_checkout' ] );
        add_action( 'woocommerce_new_order', [ $this, 'clear_reservation_session' ], 10, 1 );
        add_filter( 'wpcafe_settings' , [ $this, 'return_empty_color_settings_as_object' ] );
        add_filter( 'wpc_available_email_triggers', [ $this, 'add_on_reservation_email_trigger' ] );
        add_action( 'wp_ajax_wpc_discard_reservation', [ $this, 'discard_reservation_ajax' ] );
        add_action( 'wp_ajax_nopriv_wpc_discard_reservation', [ $this, 'discard_reservation_ajax' ] );
    }

    public function add_on_reservation_email_trigger( $available_triggers ) {
        $available_triggers[] = Reservation_Created_Trigger::class;
        $available_triggers[] = Reservation_Cancelled_Trigger::class;
        $available_triggers[] = Reservation_Confirmed_Trigger::class;
        $available_triggers[] = Reservation_Pending_Trigger::class;
        $available_triggers[] = Reservation_Updated_Trigger::class;
        return $available_triggers;
    }

    /**
     * Display reservation information on WooCommerce checkout page
     *
     * Shows reservation details if they exist in the WooCommerce session
     *
     * @return void
     */
    public function wpc_display_reservation_info_on_checkout(): void {
        // Check if WooCommerce is available and session exists
        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            return;
        }

        $reservation_data = WC()->session->get( 'wpc_reservation_data' );

        // Exit early if no reservation data exists
        if ( empty( $reservation_data ) || ! is_array( $reservation_data ) ) {
            return;
        }

        // Include the reservation details template
        $template_path = wpcafe()->template_directory . '/reservation/reservation-view.php';

        if ( file_exists( $template_path ) ) {
            include $template_path;
        }
    }

    /**
     * Remove reservation session data after order is created
     *
     * @param int $order_id The WooCommerce order ID.
     * @return void
     */
    public function clear_reservation_session( $order_id ): void {
        if ( ! is_admin() && function_exists( 'WC' ) && WC()->session ) {
            WC()->session->__unset( 'wpc_reservation_data' );
        }
    }

    /**
     * Ensures colorSettings always returns empty object in response when empty.
     *
     * This function modifies the given settings array by replacing an empty colorSettings array with an empty stdClass.
     * This is to ensure that the colorSettings key always returns an object, even if it is empty.
     *
     * @param  array $settings The settings array to be modified.
     * @return array The modified settings array.
     */
    public function return_empty_color_settings_as_object( $settings ) {
        if ( ! isset( $settings['visual_table_layout']['colorSettings'] ) ) {
            return $settings;
        }

        $color_settings = $settings['visual_table_layout']['colorSettings'];

        if ( empty( $color_settings ) && is_array( $color_settings ) ) {
            $settings['visual_table_layout']['colorSettings'] = new \stdClass();
        }

        return $settings;
    }

    /**
     * Removes reservation data from WooCommerce session, cart, and cleans up
     * associated booking amounts and products.
     *
     * @return void
     */
    public function discard_reservation_ajax(): void {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpc_discard_reservation' ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed', 'wp-cafe' ) ] );
        }

        if ( ! function_exists( 'WC' ) || ! WC()->session ) {
            wp_send_json_error( [ 'message' => __( 'WooCommerce session not available', 'wp-cafe' ) ] );
        }

        $reservation_data = WC()->session->get( 'wpc_reservation_data' );

        if ( empty( $reservation_data ) ) {
            wp_send_json_error( [ 'message' => __( 'No reservation found in session', 'wp-cafe' ) ] );
        }

        $this->remove_reservation_from_cart( $reservation_data );

        WC()->session->__unset( 'wpc_reservation_data' );

        wp_send_json_success( [ 'message' => __( 'Reservation discarded successfully', 'wp-cafe' ) ] );
    }

    /**
     * Removes the reservation booking amount fee and any reservation-related products
     * from the WooCommerce cart.
     *
     * @param array $reservation_data Reservation data from session
     * @return void
     */
    private function remove_reservation_from_cart( $reservation_data ): void {
        if ( function_exists( 'wc_load_cart' ) && is_null( WC()->cart ) ) {
            wc_load_cart();
        }

        if ( ! WC()->cart ) {
            return;
        }

        $cart = WC()->cart;

        $generic_product_id = wpc_get_option( 'woocommerce_generic_product_id' );

        $reservation_id = $reservation_data['reservation_id'] ?? null;

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            $product_id = $cart_item['product_id'];

            if ( ! empty( $generic_product_id ) && (int) $product_id === (int) $generic_product_id ) {
                $cart->remove_cart_item( $cart_item_key );
            } elseif ( ! empty( $reservation_id ) && isset( $cart_item['reservation_id'] ) && $cart_item['reservation_id'] == $reservation_id ) {
                $cart->remove_cart_item( $cart_item_key );
            }
        }

        $cart->calculate_totals();
    }
}