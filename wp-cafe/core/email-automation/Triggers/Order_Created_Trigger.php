<?php

namespace WpCafe\Email_Automation\Triggers;

/**
 * Order Created Trigger
 *
 * Configures the email trigger for when a new order is created.
 */
class Order_Created_Trigger extends Abstract_Trigger {

	/**
	 * Get trigger label
	 *
	 * @return string
	 */
	public function get_trigger_label() {
		return __( 'Order Created', 'wp-cafe' );
	}

	/**
	 * Get trigger value (unique identifier)
	 *
	 * @return string
	 */
	public function get_trigger_value() {
		return 'order_created';
	}

	/**
	 * Get trigger data fields
	 *
	 * Returns order, customer, delivery, and payment field definitions
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
			// Order Date
			array(
				'label' => __( 'Order Date', 'wp-cafe' ),
				'value' => 'order_date',
				'type'  => 'date',
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
			// Delivery Info
			array(
				'label' => __( 'Items Ordered', 'wp-cafe' ),
				'value' => 'items_ordered',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Delivery Address', 'wp-cafe' ),
				'value' => 'delivery_address',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Delivery Date', 'wp-cafe' ),
				'value' => 'delivery_date',
				'type'  => 'date',
			),
			array(
				'label' => __( 'Delivery Time', 'wp-cafe' ),
				'value' => 'delivery_time',
				'type'  => 'string',
			),
			// Payment Method
			array(
				'label' => __( 'Payment Method', 'wp-cafe' ),
				'value' => 'payment_method',
				'type'  => 'string',
			),
			// Special Instructions
			array(
				'label' => __( 'Special Instructions', 'wp-cafe' ),
				'value' => 'special_instructions',
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
				'label' => __( 'Order Date', 'wp-cafe' ),
				'value' => 'order_date',
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
