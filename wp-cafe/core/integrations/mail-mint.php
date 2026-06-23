<?php
namespace WpCafe\Integrations;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Contracts\Switchable_Service_Contract;

/**
 * Mail Mint integration service.
 *
 * Sends contact data (name, email, phone) to a Mail Mint webhook on three
 * events: restaurant onboarding, reservation creation, and WooCommerce order.
 *
 * @since 2.6.0
 */
class Mail_Mint implements Hookable_Service_Contract, Switchable_Service_Contract {

    /**
     * Register hooks.
     *
     * @return void
     */
    public function register() {
        add_filter( 'wpcafe_settings', [ $this, 'send_mail_mint_data' ] );
        add_action( 'wpcafe_after_reservation_create', [ $this, 'send_mail_mint_reservation_data' ] );
        add_action( 'woocommerce_checkout_order_processed', [ $this, 'send_mail_mint_woocommerce_order_data' ] );
    }

    /**
     * Send restaurant onboarding data to Mail Mint on settings save.
     *
     * @param array $data Settings data passed through the wpcafe_settings filter.
     * @return array Unmodified settings data.
     */
    public function send_mail_mint_data( $data ) {
        if ( ! wpc_is_integration_enable( 'mail-mint' ) ) {
            return $data;
        }

        if ( ! isset( $data['restaurant_type'] ) ) {
            return $data;
        }

        $name    = ! empty( $data['restaurant_name'] ) ? $data['restaurant_name'] : '';
        $email   = ! empty( $data['restaurant_email'] ) ? $data['restaurant_email'] : '';
        $phone   = ! empty( $data['restaurant_phone'] ) ? $data['restaurant_phone'] : '';
        $address = ! empty( $data['restaurant_location']['address'] ) ? $data['restaurant_location']['address'] : '';

        [ $first_name, $last_name ] = $this->split_name( $name );

        $this->send_to_webhook( [
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email,
            'phone_number'      => $phone,
            'address_line_1'    => $address,
        ] );

        return $data;
    }

    /**
     * Send reservation contact data to Mail Mint after a reservation is created.
     *
     * @param object $reservation Reservation object with name, email, and phone properties.
     * @return object The original reservation object.
     */
    public function send_mail_mint_reservation_data( $reservation ) {
        if ( ! wpc_is_integration_enable( 'mail-mint' ) ) {
            return;
        }

        [ $first_name, $last_name ] = $this->split_name( $reservation->name );

        $this->send_to_webhook( [
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $reservation->email,
            'phone'      => $reservation->phone,
        ] );

        return $reservation;
    }

    /**
     * Send billing contact data to Mail Mint when a WooCommerce order is processed.
     *
     * @param int $order_id WooCommerce order ID.
     * @return void
     */
    public function send_mail_mint_woocommerce_order_data( $order_id ) {
        if ( ! wpc_is_integration_enable( 'mail-mint' ) ) {
            return;
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        $first_name = trim( $order->get_billing_first_name() );
        $last_name  = trim( $order->get_billing_last_name() );
        $email      = $order->get_billing_email();
        $phone      = $order->get_billing_phone();

        // Only send if all required fields are present.
        if ( ( empty( $first_name ) && empty( $last_name ) ) || empty( $email ) || empty( $phone ) ) {
            return;
        }

        $this->send_to_webhook( [
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email,
            'phone'      => $phone,
        ] );
    }

    /**
     * Split a full name into first and last name on the first space.
     * "John" → ["John", ""], "John Doe Smith" → ["John", "Doe Smith"].
     *
     * @param string $full_name
     * @return array{0: string, 1: string}
     */
    private function split_name( string $full_name ): array {
        $parts = explode( ' ', trim( $full_name ), 2 );
        return [ $parts[0] ?? '', $parts[1] ?? '' ];
    }

    /**
     * POST JSON-encoded data to the configured Mail Mint webhook URL.
     *
     * @param array $data Data to send.
     * @return void
     */
    private function send_to_webhook( array $data ) {
        $webhook_url = wpc_get_option( 'mailmint_webhook_url' );

        if ( ! $webhook_url ) {
            return;
        }

        wp_remote_post( $webhook_url, [
            'body' => json_encode( $data ),
        ] );
    }

    /**
     * Check if Mail Mint integration is enabled.
     *
     * @return bool
     */
    public function is_enable() {
        return wpc_is_integration_enable( 'mail-mint' );
    }
}
