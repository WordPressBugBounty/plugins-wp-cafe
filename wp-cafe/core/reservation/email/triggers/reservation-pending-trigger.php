<?php

namespace WpCafe\Reservation\Email\Triggers;

use WpCafe\Email_Automation\Triggers\Abstract_Trigger;

/**
 * Reservation Pending Trigger
 *
 * Configures the email trigger for when a reservation is set to pending.
 *
 * @package WpCafe/Reservation/Email/Triggers
 */
class Reservation_Pending_Trigger extends Abstract_Trigger {

	/**
	 * Get trigger label
	 *
	 * @return string
	 */
	public function get_trigger_label() {
		return __( 'Reservation Pending', 'wp-cafe' );
	}

	/**
	 * Get trigger value (unique identifier)
	 *
	 * @return string
	 */
	public function get_trigger_value() {
		return 'reservation_pending';
	}

	/**
	 * Get trigger data fields
	 *
	 * Returns all reservation-related field definitions
	 *
	 * @return array
	 */
	public function get_trigger_data() {
		return array(
			// Reservation Basic Info
			array(
				'label' => __( 'Reservation ID', 'wp-cafe' ),
				'value' => 'reservation_id',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Reservation Name', 'wp-cafe' ),
				'value' => 'reservation_name',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Reservation Email', 'wp-cafe' ),
				'value' => 'reservation_email',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Reservation Phone', 'wp-cafe' ),
				'value' => 'reservation_phone',
				'type'  => 'string',
			),
			// Reservation Date and Time
			array(
				'label' => __( 'Reservation Date', 'wp-cafe' ),
				'value' => 'reservation_date',
				'type'  => 'date',
			),
			array(
				'label' => __( 'Reservation Start Time', 'wp-cafe' ),
				'value' => 'reservation_start_time',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Reservation End Time', 'wp-cafe' ),
				'value' => 'reservation_end_time',
				'type'  => 'string',
			),
			// Reservation Details
			array(
				'label' => __( 'Total Guests', 'wp-cafe' ),
				'value' => 'reservation_total_guests',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Table Name', 'wp-cafe' ),
				'value' => 'reservation_table_name',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Branch Name', 'wp-cafe' ),
				'value' => 'reservation_branch_name',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Branch ID', 'wp-cafe' ),
				'value' => 'reservation_branch_id',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Reservation Status', 'wp-cafe' ),
				'value' => 'reservation_status',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Special Notes', 'wp-cafe' ),
				'value' => 'reservation_notes',
				'type'  => 'string',
			),
			// Reservation Pricing
			array(
				'label' => __( 'Booking Amount', 'wp-cafe' ),
				'value' => 'reservation_booking_amount',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Total Price', 'wp-cafe' ),
				'value' => 'reservation_total_price',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Currency', 'wp-cafe' ),
				'value' => 'reservation_currency',
				'type'  => 'string',
			),
			// Reservation Payment and Order
			array(
				'label' => __( 'Payment Method', 'wp-cafe' ),
				'value' => 'reservation_payment_method',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Food Order', 'wp-cafe' ),
				'value' => 'reservation_food_order',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Invoice Number', 'wp-cafe' ),
				'value' => 'reservation_invoice',
				'type'  => 'string',
			),
			array(
				'label' => __( 'Reserved Seat Names', 'wp-cafe' ),
				'value' => 'reservation_seat_names',
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
				'label' => __( 'Reservation Date', 'wp-cafe' ),
				'value' => 'reservation_date',
			),
			array(
				'label' => __( 'After Reservation Date', 'wp-cafe' ),
				'value' => 'after_reservation_date',
			)
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
