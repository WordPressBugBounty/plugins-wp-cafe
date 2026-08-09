<?php
namespace WpCafe\Traits;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Shared helper for integration webhook payloads.
 *
 * Provides methods to build rich payloads for reservations, WooCommerce orders,
 * and restaurant onboarding so every integration sends comprehensive data.
 *
 * @since 2.7.0
 */
trait Integration_Data_Helper {

	/**
	 * Build a full reservation payload.
	 *
	 * @param object $reservation Reservation_Model instance.
	 * @return array
	 */
	protected function build_reservation_payload( $reservation ) {
		$start_time = $reservation->start_time;
		$end_time   = $reservation->end_time;

		$custom_fields = $reservation->custom_fields;
		if ( ! is_array( $custom_fields ) ) {
			$custom_fields = [];
		}

		$seats = $reservation->seats;
		if ( ! is_array( $seats ) ) {
			$seats = [];
		}

		return [
			'reservation_id'      => (int) $reservation->id,
			'invoice'             => $reservation->invoice,
			'name'                => $reservation->name,
			'email'               => $reservation->email,
			'phone'               => $reservation->phone,
			'date'                => $reservation->date,
			'start_time'          => is_numeric( $start_time ) ? gmdate( 'h:i A', (int) $start_time ) : $start_time,
			'end_time'            => is_numeric( $end_time ) ? gmdate( 'h:i A', (int) $end_time ) : $end_time,
			'total_guest'         => $reservation->total_guest,
			'status'              => $reservation->status,
			'notes'               => $reservation->notes,
			'branch_id'           => $reservation->branch_id,
			'branch_name'         => $reservation->branch_name,
			'table_name'          => $reservation->table_name,
			'booking_amount'      => $reservation->booking_amount,
			'total_price'         => $reservation->total_price,
			'currency'            => $reservation->currency,
			'deposit_value'       => $reservation->deposit_value,
			'remaining_amount'    => $reservation->remaining_amount,
			'is_partial_payment'  => $reservation->is_partial_payment,
			'payment_method'      => $reservation->payment_method,
			'woo_order_id'        => $reservation->woo_order_id,
			'food_order'          => $reservation->food_order,
			'custom_fields'       => $custom_fields,
			'seats'               => $seats,
		];
	}

	/**
	 * Build a full WooCommerce order payload including WPCafe meta and line items.
	 *
	 * @param \WC_Order $order WooCommerce order object.
	 * @return array
	 */
	protected function build_order_payload( $order ) {
		$items = [];
		foreach ( $order->get_items() as $item ) {
			$items[] = [
				'name'      => $item->get_name(),
				'quantity'  => $item->get_quantity(),
				'price'     => $item->get_total() / max( 1, $item->get_quantity() ),
				'total'     => $item->get_total(),
			];
		}

		$order_mode = $order->get_meta( 'wpc_pro_order_mode' )
			?: $order->get_meta( 'wpc_pro_order_time' );

		$payload = [
			'order_id'          => $order->get_id(),
			'order_status'      => $order->get_status(),
			'order_total'       => $order->get_total(),
			'order_currency'    => $order->get_currency(),
			'payment_method'    => $order->get_payment_method_title(),
			'customer_name'     => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
			'customer_email'    => $order->get_billing_email(),
			'customer_phone'    => $order->get_billing_phone(),
			'billing_address'   => $order->get_formatted_billing_address(),
			'items'             => $items,
		];

		$wpc_meta = [
			'order_mode'    => $order_mode,
			'location_id'   => $order->get_meta( 'wpc_location_id' ),
			'location_name' => $order->get_meta( 'wpc_location_name' ),
			'table_label'   => $order->get_meta( 'wpc_pro_table_label' ),
			'party_size'    => $order->get_meta( 'wpc_pro_party_size' ),
		];

		if ( 'Delivery' === $order_mode ) {
			$wpc_meta['delivery_date'] = $order->get_meta( 'wpc_pro_delivery_date' );
			$wpc_meta['delivery_time'] = $order->get_meta( 'wpc_pro_delivery_time' );
		} elseif ( 'Pickup' === $order_mode ) {
			$wpc_meta['pickup_date']   = $order->get_meta( 'wpc_pro_pickup_date' );
			$wpc_meta['pickup_time']   = $order->get_meta( 'wpc_pro_pickup_time' );
		}

		$res_total     = $order->get_meta( '_wpc_reservation_total' );
		$res_deposit   = $order->get_meta( '_wpc_reservation_deposit' );
		$res_remaining = $order->get_meta( '_wpc_reservation_remaining' );
		$res_id        = $order->get_meta( 'reservation_id' );

		if ( $res_total || $res_deposit || $res_remaining ) {
			$wpc_meta['reservation_total']     = $res_total;
			$wpc_meta['reservation_deposit']   = $res_deposit;
			$wpc_meta['reservation_remaining'] = $res_remaining;
		}
		if ( $res_id ) {
			$wpc_meta['reservation_id'] = $res_id;
		}

		$payload['order_meta'] = array_filter( $wpc_meta, function ( $v ) {
			return $v !== '' && $v !== null;
		} );

		return $payload;
	}

	/**
	 * Build a restaurant onboarding payload from WPCafe settings data.
	 *
	 * @param array $data The settings array passed through the wpcafe_settings filter.
	 * @return array
	 */
	protected function build_restaurant_payload( $data ) {
		$name    = ! empty( $data['restaurant_name'] ) ? $data['restaurant_name'] : '';
		$email   = ! empty( $data['restaurant_email'] ) ? $data['restaurant_email'] : '';
		$phone   = ! empty( $data['restaurant_phone'] ) ? $data['restaurant_phone'] : '';
		$address = ! empty( $data['restaurant_location']['address'] ) ? $data['restaurant_location']['address'] : '';

		return [
			'name'    => $name,
			'email'   => $email,
			'phone'   => $phone,
			'address' => $address,
		];
	}

	/**
	 * POST JSON-encoded data to a webhook URL stored in a WPCafe option.
	 *
	 * @param string $option_key WPCafe option key that holds the webhook URL.
	 * @param array  $data       Data to send.
	 * @param bool   $blocking   Whether the request should block (default false for non-blocking).
	 * @return void
	 */
	protected function send_webhook( $option_key, $data, $blocking = false ) {
		$url = wpc_get_option( $option_key );

		if ( ! $url ) {
			return;
		}

		// Send the JSON Content-Type header so strict receivers (e.g. Uncanny
		// Automator) parse the body as JSON. Without it WP defaults to
		// x-www-form-urlencoded and Automator's auto-detect fails to read the
		// fields. Lenient receivers (Fluent CRM, Pabbly) already decode raw JSON.
		wp_remote_post( $url, [
			'headers'  => [ 'Content-Type' => 'application/json' ],
			'body'     => wp_json_encode( $data ),
			'blocking' => $blocking,
		] );
	}
}
