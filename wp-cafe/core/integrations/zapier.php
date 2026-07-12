<?php
namespace WpCafe\Integrations;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Contracts\Switchable_Service_Contract;
use WpCafe\Traits\Integration_Data_Helper;

/**
 * Zapier integration service.
 *
 * Sends restaurant onboarding, reservation, and order data to a Zapier webhook.
 *
 * @since 1.0.0
 */
class Zapier implements Hookable_Service_Contract, Switchable_Service_Contract {

	use Integration_Data_Helper;

	/**
	 * Register Services
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'wpcafe_settings', [ $this, 'send_zapier_restaurant_data' ] );
		add_action( 'wpcafe_after_reservation_create', [ $this, 'send_zapier_reservation_data' ] );
		add_action( 'woocommerce_checkout_order_processed', [ $this, 'send_zapier_order_data' ] );
	}

	/**
	 * Send restaurant onboarding data to Zapier on settings save.
	 *
	 * @param array $data Settings data.
	 * @return array
	 */
	public function send_zapier_restaurant_data( $data ) {
		if ( ! wpc_is_integration_enable( 'zapier' ) ) {
			return $data;
		}

		if ( ! isset( $data['restaurant_type'] ) ) {
			return $data;
		}

		$this->send_webhook( 'zapier_webhook_url', [
			'source'     => 'wpcafe_restaurant_onboarding',
			'restaurant' => $this->build_restaurant_payload( $data ),
		], true );

		return $data;
	}

	/**
	 * Send reservation data to Zapier after a reservation is created.
	 *
	 * @param object $reservation Reservation object.
	 * @return object
	 */
	public function send_zapier_reservation_data( $reservation ) {
		if ( ! wpc_is_integration_enable( 'zapier' ) ) {
			return $reservation;
		}

		$this->send_webhook( 'zapier_webhook_url', [
			'source'      => 'wpcafe_reservation',
			'reservation' => $this->build_reservation_payload( $reservation ),
		], true );

		return $reservation;
	}

	/**
	 * Send order data to Zapier when a WooCommerce order is processed.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return void
	 */
	public function send_zapier_order_data( $order_id ) {
		if ( ! wpc_is_integration_enable( 'zapier' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$this->send_webhook( 'zapier_webhook_url', [
			'source' => 'wpcafe_woocommerce_order',
			'order'  => $this->build_order_payload( $order ),
		], true );
	}

	/**
	 * Check if Zapier integration is enabled.
	 *
	 * @return bool
	 */
	public function is_enable() {
		return wpc_is_integration_enable( 'zapier' );
	}
}
