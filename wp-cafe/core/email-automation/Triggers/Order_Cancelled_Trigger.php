<?php

namespace WpCafe\Email_Automation\Triggers;

/**
 * Order Cancelled Trigger
 *
 * Configures the email trigger for when an order is cancelled.
 */
class Order_Cancelled_Trigger extends Abstract_Trigger {

	/**
	 * Get trigger label
	 *
	 * @return string
	 */
	public function get_trigger_label() {
		return __( 'Order Cancelled', 'wp-cafe' );
	}

	/**
	 * Get trigger value (unique identifier)
	 *
	 * @return string
	 */
	public function get_trigger_value() {
		return 'order_cancelled';
	}

	/**
	 * Get trigger data fields
	 *
	 * Returns order, customer, and cancellation field definitions
	 *
	 * @return array
	 */
	public function get_trigger_data() {
		return array(
			// Order Details
			array(
				'label' => __( 'Order ID', 'wp-cafe' ),
				'value' => 'order_id',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Order Number', 'wp-cafe' ),
				'value' => 'order_number',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Order Total', 'wp-cafe' ),
				'value' => 'order_total',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Order Status', 'wp-cafe' ),
				'value' => 'order_status',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Order Currency', 'wp-cafe' ),
				'value' => 'order_currency',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Order Subtotal', 'wp-cafe' ),
				'value' => 'order_subtotal',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Order Shipping Total', 'wp-cafe' ),
				'value' => 'order_shipping_total',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Order Discount Total', 'wp-cafe' ),
				'value' => 'order_discount_total',
				'type'  => 'string',
			),
			// Cancellation Date
			array(
				'label' => __( 'Cancellation Date', 'wp-cafe' ),
				'value' => 'cancellation_date',
				'type'  => 'date',
			),
			// Cancellation Reason
			array(
				'label' => __( 'Cancellation Reason', 'wp-cafe' ),
				'value' => 'cancellation_reason',
				'type'  => 'string',
			),
			// Customer Information
			array(
				'label' => __( 'Customer Name', 'wp-cafe' ),
				'value' => 'customer_name',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Customer Email', 'wp-cafe' ),
				'value' => 'customer_email',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Customer Phone', 'wp-cafe' ),
				'value' => 'customer_phone',
				'type'  => 'string',
			),
		);
	}

	/**
	 * Get delay dependency fields
	 *
	 * @return array
	 */
	public function get_delay_dependencies() {
		return array(
			array(
				'label' => __( 'Cancellation Date', 'wp-cafe' ),
				'value' => 'cancellation_date',
			),
		);
	}

	/**
	 * Get email receiver options
	 *
	 * @return array
	 */
	public function get_email_receivers() {
		return array(
			array(
				'label' => __( 'Customer Email', 'wp-cafe' ),
				'value' => 'customer_email',
			),
			array(
				'label' => __( 'Admin Email', 'wp-cafe' ),
				'value' => 'admin_email',
			),
		);
	}
}
