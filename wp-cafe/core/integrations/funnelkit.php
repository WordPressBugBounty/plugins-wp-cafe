<?php
namespace WpCafe\Integrations;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Contracts\Switchable_Service_Contract;
use WpCafe\Traits\Integration_Data_Helper;

/**
 * FunnelKit Automations integration service.
 *
 * Sends restaurant onboarding, reservation, and order data to a FunnelKit
 * incoming webhook. The customer creates the webhook in FunnelKit Automations
 * and pastes its URL into the integration config; we POST the same rich
 * payloads the other webhook integrations use.
 *
 * @since 1.0.0
 */
class Funnelkit implements Hookable_Service_Contract, Switchable_Service_Contract {

	use Integration_Data_Helper;

	/**
	 * Register Services
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'wpcafe_settings', [ $this, 'send_funnelkit_data' ] );
		add_action( 'wpcafe_after_reservation_create', [ $this, 'send_funnelkit_reservation_data' ] );
		add_action( 'woocommerce_checkout_order_processed', [ $this, 'send_funnelkit_woocommerce_order_data' ] );
	}

	/**
	 * Send restaurant onboarding data to FunnelKit on settings save.
	 *
	 * @param array $data Settings data.
	 * @return array
	 */
	public function send_funnelkit_data( $data ) {
		if ( ! wpc_is_integration_enable( 'funnelkit' ) ) {
			return $data;
		}

		if ( ! isset( $data['restaurant_type'] ) ) {
			return $data;
		}

		$this->send_webhook( 'funnelkit_webhook_url', [
			'source'     => 'wpcafe_restaurant_onboarding',
			'restaurant' => $this->build_restaurant_payload( $data ),
		] );

		return $data;
	}

	/**
	 * Send reservation data to FunnelKit after a reservation is created.
	 *
	 * @param object $reservation Reservation object.
	 * @return object
	 */
	public function send_funnelkit_reservation_data( $reservation ) {
		if ( ! wpc_is_integration_enable( 'funnelkit' ) ) {
			return $reservation;
		}

		$this->send_webhook( 'funnelkit_webhook_url', [
			'source'      => 'wpcafe_reservation',
			'reservation' => $this->build_reservation_payload( $reservation ),
		] );

		return $reservation;
	}

	/**
	 * Send order data to FunnelKit when a WooCommerce order is processed.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return void
	 */
	public function send_funnelkit_woocommerce_order_data( $order_id ) {
		if ( ! wpc_is_integration_enable( 'funnelkit' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$this->send_webhook( 'funnelkit_webhook_url', [
			'source' => 'wpcafe_woocommerce_order',
			'order'  => $this->build_order_payload( $order ),
		] );
	}

	/**
	 * Check if FunnelKit integration is enabled.
	 *
	 * @return bool
	 */
	public function is_enable() {
		return wpc_is_integration_enable( 'funnelkit' );
	}
}
