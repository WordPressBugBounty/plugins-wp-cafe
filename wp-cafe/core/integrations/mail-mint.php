<?php
namespace WpCafe\Integrations;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Contracts\Switchable_Service_Contract;
use WpCafe\Traits\Integration_Data_Helper;

/**
 * Mail Mint integration service.
 *
 * Sends contact and order/reservation data to a Mail Mint webhook on three
 * events: restaurant onboarding, reservation creation, and WooCommerce order.
 *
 * @since 2.6.0
 */
class Mail_Mint implements Hookable_Service_Contract, Switchable_Service_Contract {

	use Integration_Data_Helper;

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

		$restaurant = $this->build_restaurant_payload( $data );

		[ $first_name, $last_name ] = $this->split_name( $restaurant['name'] );

		$this->send_webhook( 'mailmint_webhook_url', [
			'first_name'      => $first_name,
			'last_name'       => $last_name,
			'email'           => $restaurant['email'],
			'phone_number'    => $restaurant['phone'],
			'address_line_1'  => $restaurant['address'],
			'custom_fields'   => [
				'source'        => 'wpcafe_restaurant_onboarding',
				'restaurant'    => $restaurant,
			],
		], true );

		return $data;
	}

	/**
	 * Send reservation data to Mail Mint after a reservation is created.
	 *
	 * @param object $reservation Reservation object.
	 * @return object The original reservation object.
	 */
	public function send_mail_mint_reservation_data( $reservation ) {
		if ( ! wpc_is_integration_enable( 'mail-mint' ) ) {
			return $reservation;
		}

		[ $first_name, $last_name ] = $this->split_name( $reservation->name );

		$reservation_data = $this->build_reservation_payload( $reservation );

		$this->send_webhook( 'mailmint_webhook_url', [
			'first_name'    => $first_name,
			'last_name'     => $last_name,
			'email'         => $reservation->email,
			'phone'         => $reservation->phone,
			'custom_fields' => [
				'source'      => 'wpcafe_reservation',
				'reservation' => $reservation_data,
			],
		], true );

		return $reservation;
	}

	/**
	 * Send billing and order data to Mail Mint when a WooCommerce order is processed.
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

		if ( ( empty( $first_name ) && empty( $last_name ) ) || empty( $email ) || empty( $phone ) ) {
			return;
		}

		$order_data = $this->build_order_payload( $order );

		$this->send_webhook( 'mailmint_webhook_url', [
			'first_name'    => $first_name,
			'last_name'     => $last_name,
			'email'         => $email,
			'phone'         => $phone,
			'custom_fields' => [
				'source' => 'wpcafe_woocommerce_order',
				'order'  => $order_data,
			],
		], true );
	}

	/**
	 * Split a full name into first and last name on the first space.
	 *
	 * @param string $full_name
	 * @return array{0: string, 1: string}
	 */
	private function split_name( string $full_name ): array {
		$parts = explode( ' ', trim( $full_name ), 2 );
		return [ $parts[0] ?? '', $parts[1] ?? '' ];
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
