<?php
namespace WpCafe\Email_Automation\Handlers;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- plugin-wpc-prefix, public backward-compat hooks, or third-party (Elementor) hook names.

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Order Email Handler
 *
 * Handles email notifications for order events via the email automation system.
 *
 * @package WpCafe/Email_Automation/Handlers
 */
class Order_Email_Handler implements Hookable_Service_Contract {

	/**
	 * Register hooks
	 *
	 * Only registers hooks if WooCommerce is active.
	 *
	 * @return void
	 */
	public function register() {
		// Only register if WooCommerce is active and required functions exist.
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		add_action( 'woocommerce_new_order', [ $this, 'send_order_created_notification' ], 10, 1 );
		add_action( 'woocommerce_order_status_changed', [ $this, 'send_order_status_changed_notification' ], 10, 3 );
		add_action( 'wpcafe_order_cancelled', [ $this, 'send_order_cancelled_notification' ], 10, 1 );
	}

	/**
	 * Send order created notification via email automation.
	 *
	 * @param int $order_id The order ID.
	 * @return void
	 */
	public function send_order_created_notification( $order_id ) {
		if ( ! is_numeric( $order_id ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$notification_data = $this->get_order_notification_data( $order, 'created' );
		$notification_data = apply_filters( 'wpc_order_created_notification_data', $notification_data, $order );

		do_action( 'wpcafe_gln_hook', 'order_created', $notification_data );
	}

	/**
	 * Send order status changed notification via email automation.
	 *
	 * @param int    $order_id    The order ID.
	 * @param string $old_status  The previous order status (without 'wc-' prefix).
	 * @param string $new_status  The new order status (without 'wc-' prefix).
	 * @return void
	 */
	public function send_order_status_changed_notification( $order_id, $old_status, $new_status ) {
		if ( ! is_numeric( $order_id ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$notification_data = $this->get_order_notification_data( $order, 'status_changed' );
		$notification_data['previous_status'] = $old_status;
		$notification_data['new_status'] = $new_status;
		$notification_data['status_change_date'] = gmdate( 'Y-m-d H:i:s' );

		$notification_data = apply_filters( 'wpc_order_status_changed_notification_data', $notification_data, $order, $old_status, $new_status );

		do_action( 'wpcafe_gln_hook', 'order_status_changed', $notification_data );
	}

	/**
	 * Send order cancelled notification via email automation.
	 *
	 * @param int $order_id The order ID.
	 * @return void
	 */
	public function send_order_cancelled_notification( $order_id ) {
		if ( ! is_numeric( $order_id ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$notification_data = $this->get_order_notification_data( $order, 'cancelled' );
		$notification_data['cancellation_date'] = gmdate( 'Y-m-d H:i:s' );
		$notification_data['cancellation_reason'] = $order->get_customer_note() ?? '';

		$notification_data = apply_filters( 'wpc_order_cancelled_notification_data', $notification_data, $order );

		do_action( 'wpcafe_gln_hook', 'order_cancelled', $notification_data );
	}

	/**
	 * Build notification data from order object.
	 *
	 * @param \WC_Order $order       The WooCommerce order object.
	 * @param string    $event_type  The event type (created, status_changed, cancelled).
	 * @return array The notification data array.
	 */
	private function get_order_notification_data( $order, $event_type = 'created' ) {
		$customer_email = $order->get_billing_email() ?? '';

		$notification_data = array(
			'admin_email' => get_option( 'admin_email' ),
			'customer_email' => $customer_email,
			'order_id' => (string) $order->get_id(),
			'order_number' => (string) $order->get_order_number(),
			'order_total' => (string) $order->get_total(),
			'order_subtotal' => (string) $order->get_subtotal(),
			'order_status' => $order->get_status(),
			'order_currency' => $order->get_currency(),
			'order_shipping_total' => (string) $order->get_shipping_total(),
			'order_discount_total' => (string) $order->get_discount_total(),
		);

		$notification_data['customer_name'] = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		$notification_data['customer_phone'] = $order->get_billing_phone() ?? '';

		$notification_data['delivery_address'] = $this->format_address( $order );
		$notification_data['items_ordered'] = $this->format_items( $order );

		$notification_data['payment_method'] = $order->get_payment_method_title() ?? '';

		if ( 'created' === $event_type ) {
			$notification_data['order_date'] = $order->get_date_created()->format( 'Y-m-d H:i:s' );
		}

		$notification_data['special_instructions'] = $order->get_customer_note() ?? '';

		return $notification_data;
	}

	/**
	 * Format order address from order object.
	 *
	 * @param \WC_Order $order The WooCommerce order object.
	 * @return string The formatted address.
	 */
	private function format_address( $order ) {
		$address_parts = array(
			$order->get_billing_address_1(),
			$order->get_billing_address_2(),
			$order->get_billing_city(),
			$order->get_billing_state(),
			$order->get_billing_postcode(),
			$order->get_billing_country(),
		);

		$address_parts = array_filter( $address_parts );
		return implode( ', ', $address_parts );
	}

	/**
	 * Format order items for display in email templates.
	 *
	 * @param \WC_Order $order The WooCommerce order object.
	 * @return string The formatted items list.
	 */
	private function format_items( $order ) {
		$items = array();

		foreach ( $order->get_items() as $item ) {
			$quantity = $item->get_quantity();
			$item_name = $item->get_name();
			$items[] = sprintf( '%s (%d)', $item_name, $quantity );
		}

		return implode( ', ', $items );
	}
}
