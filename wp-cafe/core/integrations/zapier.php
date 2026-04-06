<?php
namespace WpCafe\Integrations;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Contracts\Switchable_Service_Contract;

/**
* Zapier Service
 *
 * @since 1.0.0
 */
class Zapier implements Hookable_Service_Contract, Switchable_Service_Contract {

    /**
     * Register Services
     *
     * @return  void
     */
    public function register() {
        add_action('wpcafe_after_reservation_create', [$this, 'send_zapier_reservation_data']);
        add_action('woocommerce_checkout_order_processed', [$this, 'send_zappier_order_data']);
    }

    /**
     * Send reservation data to Zappier after a reservation is created.
     *
     * This function is hooked to the 'wpcafe_after_reservation_create' action and sends
     * the reservation's name, email, and phone to the configured Zapier webhook URL,
     * if the integration is enabled and a webhook URL is set.
     *
     * @param object $reservation The reservation object containing reservation details.
     * @return object The original reservation object.
     */
    public function send_zapier_reservation_data( $reservation ) {

        if ( ! wpc_is_integration_enable('zapier') ) {
            return;
        }
        
        $webhook_url = wpc_get_option('zapier_webhook_url');

        if ( ! $webhook_url ) {
            return;
        }
        
        $reservation_data = [
            'name' => $reservation->name,
            'email' => $reservation->email,
            'phone' => $reservation->phone,
        ];

        $response = wp_remote_post( $webhook_url, [
            'body' => json_encode( $reservation_data ),
        ] );
        
        return $reservation;
    }

    /**
     * Send order data to Zappier after a WooCommerce order is processed.
     *
     * This function is hooked to the 'woocommerce_checkout_order_processed' action and sends
     * the order's customer details and order information to the configured Pabbly webhook URL,
     * if the integration is enabled and a webhook URL is set.
     *
     * @param int $order_id The WooCommerce order ID.
     * @return void
     */
    public function send_zappier_order_data( $order_id ) {

        if ( ! $this->is_enable() ) {
            return;
        }

        $webhook_url = wpc_get_option('zapier_webhook_url');

        if ( ! $webhook_url ) {
            return;
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        $order_data = [
            'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
        ];

        $response = wp_remote_post( $webhook_url, [
            'body' => json_encode( $order_data ),
        ] );

        return;
    }

    /**
     * Check if zapier is enabled
     *
     * @return  bool
     */
    public function is_enable() {
        return wpc_is_integration_enable('zapier');
    }
}

