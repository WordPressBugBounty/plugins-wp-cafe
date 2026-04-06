<?php
namespace WpCafe\Integrations;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Contracts\Switchable_Service_Contract;

/**
 * Fluent Crm Service
 *
 * @since 1.0.0
 */
class Fluent_Crm implements Hookable_Service_Contract, Switchable_Service_Contract {

    /**
     * Register Services
     *
     * @return  void
     */
    public function register() {
        add_filter('wpcafe_settings', [$this, 'send_fluent_crm_data']);
        add_action('wpcafe_after_reservation_create', [$this, 'send_fluent_crm_reservation_data']);
        add_action('woocommerce_checkout_order_processed', [$this, 'send_fluent_crm_woocommerce_order_data']);
    }

    /**
     * Send fluent crm data
     *
     * @param array $data
     * @return void
     */
    public function send_fluent_crm_data( $data ) {
        if ( ! wpc_is_integration_enable('fluent-crm') ) {
            return $data;
        }

        if ( ! isset( $data['restaurant_type'] ) ) {
            return $data;
        }

        $webhook_url = wpc_get_option('fluentcrm_webhook_url');

        if ( ! $webhook_url ) {
            return $data;
        }

        $name    = ! empty( $data['restaurant_name'] ) ? $data['restaurant_name'] : '';
        $email   = ! empty( $data['restaurant_email'] ) ? $data['restaurant_email'] : '';
        $phone   = ! empty( $data['restaurant_phone'] ) ? $data['restaurant_phone'] : '';
        $address = ! empty( $data['restaurant_location']['address'] ) ? $data['restaurant_location']['address'] : '';

        $onboard_data = [
            'name'    => $name,
            'email'   => $email,
            'phone'   => $phone,
            'address' => $address,
        ];

        $response = wp_remote_post( $webhook_url, [
            'body' => json_encode( $onboard_data ),
        ] );

        return $data;
    }

    /**
     * Send reservation data to FluentCRM after a reservation is created.
     *
     * This function is hooked to the 'wpcafe_after_reservation_create' action and sends
     * the reservation's name, email, and phone to the configured FluentCRM webhook URL,
     * if the integration is enabled and a webhook URL is set.
     *
     * @param object $reservation The reservation object containing reservation details.
     * @return object The original reservation object.
     */
    public function send_fluent_crm_reservation_data( $reservation ) {

        if ( ! wpc_is_integration_enable('fluent-crm') ) {
            return;
        }

        $reservation_data = [
            'name' => $reservation->name,
            'email' => $reservation->email,
            'phone' => $reservation->phone,
        ];

        $this->send_to_webhook( $reservation_data );

        return $reservation;
    }

    /**
     * Sends food order data to FluentCRM when order status changes to processing.
     *
     * @param int $order_id The WooCommerce order ID.
     * @return void
     */
    public function send_fluent_crm_woocommerce_order_data( $order_id ) {

        if ( ! wpc_is_integration_enable('fluent-crm') ) {
            return;
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        $name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
        $email = $order->get_billing_email();
        $phone = $order->get_billing_phone();

        // Only send if all required fields are present
        if ( empty( $name ) || empty( $email ) || empty( $phone ) ) {
            return;
        }

        $order_data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
        ];

        $this->send_to_webhook( $order_data );
    }

    /**
     * Send data to the FluentCRM webhook.
     *
     * @param array $data The data to send to the webhook.
     * @return void
     */
    private function send_to_webhook( $data ) {

        $webhook_url = wpc_get_option('fluentcrm_webhook_url');

        if ( ! $webhook_url ) {
            return;
        }

        wp_remote_post( $webhook_url, [
            'body' => json_encode( $data ),
        ] );
    }

    /**
     * Check if fluent crm is enabled
     *
     * @return  bool
     */
    public function is_enable() {
        return wpc_is_integration_enable('fluent-crm');
    }
}

