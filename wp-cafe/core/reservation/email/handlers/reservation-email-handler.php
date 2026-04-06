<?php
namespace WpCafe\Reservation\Email\Handlers;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Models\Reservation_Model;

/**
 * Reservation Email Handler
 *
 * Handles email notifications for reservation events via the email automation system.
 *
 * @package WpCafe/Reservation/Email/Handlers
 */
class Reservation_Email_Handler implements Hookable_Service_Contract {

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wpcafe_after_reservation_create', [ $this, 'send_reservation_created_notification' ], 10, 1 );
		add_action( 'wpcafe_after_reservation_cancelled', [ $this, 'send_reservation_cancelled_notification' ], 10, 1 );
		add_action( 'wpcafe_after_reservation_status_changed', [ $this, 'send_reservation_status_update_notification' ], 10, 2 );
	}

	/**
	 * Format reservation date using WordPress date format.
	 *
	 * @param string $date The date string in YYYY-MM-DD format.
	 * @return string Formatted date string.
	 */
	private function format_reservation_date( $date ) {
		if ( empty( $date ) ) {
			return '';
		}
	
		$datetime = \DateTimeImmutable::createFromFormat( 'Y-m-d', $date, wp_timezone() );
		if ( false === $datetime ) {
			return $date;
		}
	
		return wp_date( get_option( 'date_format' ), $datetime->getTimestamp() );
	}

	/**
	 * Format reservation time using WordPress time format.
	 *
	 * @param string|int $time The Unix timestamp.
	 * @return string Formatted time string.
	 */
	private function format_reservation_time( $time ) {
		if ( empty( $time ) ) {
			return '';
		}

		$timestamp = (int) $time;
		if ( $timestamp <= 0 ) {
			return '';
		}

		return wp_date( get_option( 'time_format' ), $timestamp, new \DateTimeZone( 'UTC' ) );
	}

	/**
	 * Get reservation date and time combined as Unix timestamp.
	 *
	 * @param string $date The date string in YYYY-MM-DD format.
	 * @param string|int $start_time The Unix timestamp for start time.
	 * @return int Unix timestamp combining date and time, or 0 if invalid.
	 */
	private function get_reservation_datetime_timestamp( $date, $start_time ) {
		if ( empty( $date ) || empty( $start_time ) ) {
			return 0;
		}

		$start_time_int = (int) $start_time;
		if ( $start_time_int <= 0 ) {
			return 0;
		}

		// Get time components from start_time timestamp using wp_date
		$hour = (int) wp_date( 'H', $start_time_int );
		$minute = (int) wp_date( 'i', $start_time_int );
		$second = (int) wp_date( 's', $start_time_int );

		// Combine date with time
		$datetime_string = $date . ' ' . sprintf( '%02d:%02d:%02d', $hour, $minute, $second );
		$timestamp = strtotime( $datetime_string );

		return ( false === $timestamp ) ? 0 : $timestamp;
	}

	/**
	 * Send reservation created notification via email automation.
	 *
	 * @param Reservation_Model $reservation The reservation model instance.
	 * @return void
	 */
	public function send_reservation_created_notification( $reservation ) {
		if ( ! $reservation instanceof Reservation_Model ) {
			return;
		}

		// Get branch address if branch_id exists
		$branch_address = '';
		if ( ! empty( $reservation->branch_id ) ) {
			$location = \WpCafe\Models\Location_Model::find( $reservation->branch_id );
			if ( $location && ! empty( $location->location ) ) {
				$location_data = $location->location;
				if ( is_string( $location_data ) ) {
					$decoded = json_decode( $location_data, true );
					$branch_address = ( is_array( $decoded ) && isset( $decoded['address'] ) ) ? $decoded['address'] : $location_data;
				}
			}
		}

		$notification_data = array(
			'admin_email'				=> get_option( 'admin_email' ),
			'customer_email'			=> $reservation->email ?? '',
			'reservation_id'            => $reservation->id ?? '',
			'reservation_name'          => $reservation->name ?? '',
			'reservation_email'         => $reservation->email ?? '',
			'reservation_phone'         => $reservation->phone ?? '',
			'reservation_date'          => $this->format_reservation_date( $reservation->date ?? '' ),
			'reservation_date_timestamp'=> (string) $this->get_reservation_datetime_timestamp( $reservation->date ?? '', $reservation->start_time ?? '' ),
			'reservation_start_time'    => $this->format_reservation_time( $reservation->start_time ?? '' ),
			'reservation_end_time'      => $this->format_reservation_time( $reservation->end_time ?? '' ),
			'reservation_total_guests'  => (string) ( $reservation->total_guest ?? '' ),
			'reservation_table_name'    => $reservation->table_name ?? '',
			'reservation_branch_name'   => $reservation->branch_name ?? '',
			'reservation_branch_address'=> $branch_address,
			'reservation_branch_id'     => (string) ( $reservation->branch_id ?? '' ),
			'reservation_status'        => $reservation->status ?? '',
			'reservation_notes'         => $reservation->notes ?? '',
			'reservation_booking_amount'=> (string) ( $reservation->booking_amount ?? '' ),
			'reservation_total_price'   => (string) ( $reservation->total_price ?? '' ),
			'reservation_currency'      => $reservation->currency ?? '',
			'reservation_payment_method'=> $reservation->payment_method ?? '',
			'reservation_food_order'    => $reservation->food_order ?? '',
			'reservation_invoice'       => $reservation->invoice ?? '',
			'reservation_seat_names'    => '',
		);

		$notification_data = apply_filters( 'wpc_reservation_created_notification_data', $notification_data, $reservation );

		do_action( 'global_notification_hook', 'reservation_created', $notification_data );
	}

	/**
	 * Send reservation cancelled notification via email automation.
	 *
	 * @param Reservation_Model $reservation The reservation model instance.
	 * @return void
	 */
	public function send_reservation_cancelled_notification( $reservation ) {
		if ( ! $reservation instanceof Reservation_Model ) {
			return;
		}

		// Get branch address if branch_id exists
		$branch_address = '';
		if ( ! empty( $reservation->branch_id ) ) {
			$location = \WpCafe\Models\Location_Model::find( $reservation->branch_id );
			if ( $location && ! empty( $location->location ) ) {
				$location_data = $location->location;
				if ( is_string( $location_data ) ) {
					$decoded = json_decode( $location_data, true );
					$branch_address = ( is_array( $decoded ) && isset( $decoded['address'] ) ) ? $decoded['address'] : $location_data;
				}
			}
		}

		$notification_data = array(
			'admin_email'				=> get_option( 'admin_email' ),
			'customer_email'			=> $reservation->email ?? '',
			'reservation_id'            => $reservation->id ?? '',
			'reservation_name'          => $reservation->name ?? '',
			'reservation_email'         => $reservation->email ?? '',
			'reservation_phone'         => $reservation->phone ?? '',
			'reservation_date'          => $this->format_reservation_date( $reservation->date ?? '' ),
			'reservation_date_timestamp'=> (string) $this->get_reservation_datetime_timestamp( $reservation->date ?? '', $reservation->start_time ?? '' ),
			'reservation_start_time'    => $this->format_reservation_time( $reservation->start_time ?? '' ),
			'reservation_end_time'      => $this->format_reservation_time( $reservation->end_time ?? '' ),
			'reservation_total_guests'  => (string) ( $reservation->total_guest ?? '' ),
			'reservation_table_name'    => $reservation->table_name ?? '',
			'reservation_branch_name'   => $reservation->branch_name ?? '',
			'reservation_branch_address'=> $branch_address,
			'reservation_branch_id'     => (string) ( $reservation->branch_id ?? '' ),
			'reservation_status'        => $reservation->status ?? '',
			'reservation_notes'         => $reservation->notes ?? '',
			'reservation_booking_amount'=> (string) ( $reservation->booking_amount ?? '' ),
			'reservation_total_price'   => (string) ( $reservation->total_price ?? '' ),
			'reservation_currency'      => $reservation->currency ?? '',
			'reservation_payment_method'=> $reservation->payment_method ?? '',
			'reservation_food_order'    => $reservation->food_order ?? '',
			'reservation_invoice'       => $reservation->invoice ?? '',
			'reservation_seat_names'    => '',
		);

		$notification_data = apply_filters( 'wpc_reservation_cancelled_notification_data', $notification_data, $reservation );

		do_action( 'global_notification_hook', 'reservation_cancelled', $notification_data );
	}

	/**
	 * Send reservation status update notification via email automation.
	 *
	 * @param Reservation_Model $reservation The reservation model instance.
	 * @param string            $old_status  The previous reservation status.
	 * @return void
	 */
	public function send_reservation_status_update_notification( $reservation, $old_status ) {
		if ( ! $reservation instanceof Reservation_Model ) {
			return;
		}

		$status_to_trigger = array(
			'confirmed' => 'reservation_confirmed',
			'pending'   => 'reservation_pending',
		);

		$trigger_event = $status_to_trigger[ $reservation->status ] ?? null;

		if ( ! $trigger_event ) {
			return;
		}

		// Get branch address if branch_id exists
		$branch_address = '';
		if ( ! empty( $reservation->branch_id ) ) {
			$location = \WpCafe\Models\Location_Model::find( $reservation->branch_id );
			if ( $location && ! empty( $location->location ) ) {
				$location_data = $location->location;
				if ( is_string( $location_data ) ) {
					$decoded = json_decode( $location_data, true );
					$branch_address = ( is_array( $decoded ) && isset( $decoded['address'] ) ) ? $decoded['address'] : $location_data;
				}
			}
		}

		$notification_data = array(
			'admin_email'                    => get_option( 'admin_email' ),
			'customer_email'                 => $reservation->email ?? '',
			'reservation_id'                 => $reservation->id ?? '',
			'reservation_name'               => $reservation->name ?? '',
			'reservation_email'              => $reservation->email ?? '',
			'reservation_phone'              => $reservation->phone ?? '',
			'reservation_date'               => $this->format_reservation_date( $reservation->date ?? '' ),
			'reservation_date_timestamp'     => (string) $this->get_reservation_datetime_timestamp( $reservation->date ?? '', $reservation->start_time ?? '' ),
			'reservation_start_time'         => $this->format_reservation_time( $reservation->start_time ?? '' ),
			'reservation_end_time'           => $this->format_reservation_time( $reservation->end_time ?? '' ),
			'reservation_total_guests'       => (string) ( $reservation->total_guest ?? '' ),
			'reservation_table_name'         => $reservation->table_name ?? '',
			'reservation_branch_name'        => $reservation->branch_name ?? '',
			'reservation_branch_address'     => $branch_address,
			'reservation_branch_id'          => (string) ( $reservation->branch_id ?? '' ),
			'reservation_status'             => $reservation->status ?? '',
			'reservation_previous_status'    => $old_status,
			'reservation_notes'              => $reservation->notes ?? '',
			'reservation_booking_amount'     => (string) ( $reservation->booking_amount ?? '' ),
			'reservation_total_price'        => (string) ( $reservation->total_price ?? '' ),
			'reservation_currency'           => $reservation->currency ?? '',
			'reservation_payment_method'     => $reservation->payment_method ?? '',
			'reservation_food_order'         => $reservation->food_order ?? '',
			'reservation_invoice'            => $reservation->invoice ?? '',
			'reservation_seat_names'         => '',
		);

		$notification_data = apply_filters( 'wpc_reservation_status_changed_notification_data', $notification_data, $reservation, $old_status );
		do_action( 'global_notification_hook', $trigger_event, $notification_data );
	}
}
