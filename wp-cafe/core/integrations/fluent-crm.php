<?php
namespace WpCafe\Integrations;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Contracts\Switchable_Service_Contract;
use WpCafe\Traits\Integration_Data_Helper;

/**
 * Fluent CRM integration service.
 *
 * Sends contact, reservation, and order data to a FluentCRM webhook.
 *
 * @since 1.0.0
 */
class Fluent_Crm implements Hookable_Service_Contract, Switchable_Service_Contract {

	use Integration_Data_Helper;

	/**
	 * Register Services
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'wpcafe_settings', [ $this, 'send_fluent_crm_data' ] );
		add_action( 'wpcafe_after_reservation_create', [ $this, 'send_fluent_crm_reservation_data' ] );
		add_action( 'woocommerce_checkout_order_processed', [ $this, 'send_fluent_crm_woocommerce_order_data' ] );
	}

	/**
	 * Send restaurant onboarding data to FluentCRM on settings save.
	 *
	 * @param array $data Settings data.
	 * @return array
	 */
	public function send_fluent_crm_data( $data ) {
		if ( ! wpc_is_integration_enable( 'fluent-crm' ) ) {
			return $data;
		}

		if ( ! isset( $data['restaurant_type'] ) ) {
			return $data;
		}

		$this->send_webhook( 'fluentcrm_webhook_url', [
			'source'     => 'wpcafe_restaurant_onboarding',
			'restaurant' => $this->build_restaurant_payload( $data ),
		] );

		return $data;
	}

	/**
	 * Send reservation data to FluentCRM after a reservation is created.
	 *
	 * @param object $reservation Reservation object.
	 * @return object
	 */
	public function send_fluent_crm_reservation_data( $reservation ) {
		if ( ! wpc_is_integration_enable( 'fluent-crm' ) ) {
			return $reservation;
		}

		$this->send_webhook( 'fluentcrm_webhook_url', [
			'source'      => 'wpcafe_reservation',
			'reservation' => $this->build_reservation_payload( $reservation ),
		] );

		return $reservation;
	}

	/**
	 * Send order data to FluentCRM when a WooCommerce order is processed.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return void
	 */
	public function send_fluent_crm_woocommerce_order_data( $order_id ) {
		if ( ! wpc_is_integration_enable( 'fluent-crm' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$this->send_webhook( 'fluentcrm_webhook_url', [
			'source' => 'wpcafe_woocommerce_order',
			'order'  => $this->build_order_payload( $order ),
		] );
	}

	/**
	 * Check if FluentCRM integration is enabled.
	 *
	 * @return bool
	 */
	public function is_enable() {
		return wpc_is_integration_enable( 'fluent-crm' );
	}
}
