<?php
namespace WpCafe\Resources;
use WpCafe\Abstract\Resource;

/**
 * Reservation Resource
 *
 * Handles reservation data and interactions.
 *
 * @package WpCafe/Resources
 */
class Reservation_Resource extends Resource {
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function to_array() {
        $status = get_post_status( $this->data->id );
        $payment_method = $this->get_payment_method();
        $start_time = $this->data->start_time;
        $end_time   = $this->data->end_time;

        $reservation_data = [
            'id'                => $this->data->id,
            'name'              => $this->data->name,
            'email'             => $this->data->email,
            'phone'             => $this->data->phone,
            'date'              => $this->data->date,
            'start_time'        => ! empty( $start_time ) && is_numeric( $start_time ) ? gmdate('h:i A', $start_time) : '',
            'end_time'          => ! empty( $end_time ) && is_numeric( $end_time ) ? gmdate('h:i A', $end_time) : '',
            'total_guest'       => $this->data->total_guest,
            'status'            => $status,
            'branch_id'         => $this->data->branch_id,
            'branch_name'       => $this->data->branch_name,
            'notes'             => $this->data->notes,
            'invoice'           => $this->data->invoice,
            'total_price'       => $this->data->total_price,
            'currency'          => $this->data->currency,
            'payment_method'    => $payment_method,
            'woo_order_id'      => $this->data->woo_order_id,
            'table_name'        => $this->data->table_name,
            'custom_fields'     => $this->data->custom_fields,
            'seats'             => $this->data->seats,
            'food_items'        => Reservation_Item_Resource::collection( $this->data->get_items() ),
        ];

        if ( ! empty( $reservation_data['food_items'] && class_exists('WooCommerce') && function_exists('wc_get_checkout_url') ) ) {
            $reservation_data['redirect_url'] = wc_get_checkout_url();
        }

        return $reservation_data;
    }

    /**
     * Get payment method from WooCommerce order.
     *
     * @return string
     */
    private function get_payment_method() {
        $woo_order_id = $this->data->woo_order_id;

        if ( ! empty( $woo_order_id ) && function_exists( 'wc_get_order' ) ) {
            try {
                $order = wc_get_order( $woo_order_id );

                if ( $order && is_a( $order, 'WC_Order' ) ) {
                    $payment_method = $order->get_payment_method();
                    if ( ! empty( $payment_method ) ) {
                        return $payment_method;
                    }
                }
            } catch ( \Exception ) {
                // Order fetch or payment method retrieval failed, fall back to stored value
            }
        }
        $payment_method =  $this->data->payment_method;
        return ! empty( $payment_method ) ? $payment_method : '';
    }
}
