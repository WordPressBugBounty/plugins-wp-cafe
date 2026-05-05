<?php
namespace WpCafe\Reservation\Email\Handlers;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- plugin-wpc-prefix, public backward-compat hooks, or third-party (Elementor) hook names.

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
		add_action( 'wpcafe_after_reservation_update', [ $this, 'send_reservation_updated_notification' ], 10, 2 );
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
	 * Resolve reservation status from post status (model meta is unreliable).
	 *
	 * @param Reservation_Model $reservation
	 * @return string
	 */
	private function get_reservation_status( $reservation ) {
		$status = get_post_status( $reservation->id );
		return $status ?: '';
	}

	/**
	 * Load the table-layout JSON for a branch.
	 *
	 * Reads location-specific term meta first, then falls back to the global
	 * `visual_table_layout` plugin setting. Result is decoded and cached.
	 *
	 * @param int $branch_id Reservation branch term_id.
	 * @return array
	 */
	private function get_table_layout( $branch_id ) {
		static $cache = [];
		$key = (int) $branch_id;
		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}

		$layout = '';
		if ( $branch_id ) {
			$layout = get_term_meta( $branch_id, 'visual_table_layout', true );
		}
		if ( empty( $layout ) ) {
			$layout = wpc_get_option( 'visual_table_layout', '' );
		}

		if ( is_string( $layout ) && '' !== $layout ) {
			$decoded = json_decode( $layout, true );
			$layout  = is_array( $decoded ) ? $decoded : [];
		}
		if ( ! is_array( $layout ) ) {
			$layout = [];
		}

		$cache[ $key ] = $layout;
		return $layout;
	}

	/**
	 * Resolve a stored seat ref to its containing table.
	 *
	 * @param string $seat_ref e.g. "S5".
	 * @param int    $branch_id Reservation branch term_id.
	 * @return array{seat_label:string, table_label:string}|null
	 */
	private function resolve_seat_row( $seat_ref, $branch_id ) {
		$seat_ref = (string) $seat_ref;
		if ( '' === $seat_ref ) {
			return null;
		}

		$layout = $this->get_table_layout( $branch_id );
		$tables = $layout['tables'] ?? [];

		if ( empty( $tables ) || ! is_array( $tables ) ) {
			return null;
		}

		foreach ( $tables as $table ) {
			$seats = $table['seats'] ?? [];
			if ( is_array( $seats ) ) {
				foreach ( $seats as $seat ) {
					$id = (string) ( $seat['id'] ?? '' );
					if ( $id === $seat_ref ) {
						return [
							'seat_label'  => (string) ( $seat['label'] ?? $seat['name'] ?? $id ),
							'table_label' => (string) ( $table['name'] ?? $table['label'] ?? $table['id'] ?? '' ),
						];
					}
				}
			}

			$top_id = (string) ( $table['id'] ?? '' );
			if ( $top_id === $seat_ref ) {
				$table_label = (string) ( $table['name'] ?? $table['label'] ?? $top_id );
				return [
					'seat_label'  => $table_label,
					'table_label' => $table_label,
				];
			}
		}

		return null;
	}

	/**
	 * Build comma-separated seat label list for the reservation.
	 *
	 * @param Reservation_Model $reservation
	 * @return string
	 */
	private function get_seat_names( $reservation ) {
		$seat_ids = $reservation->seats ?? [];
		if ( empty( $seat_ids ) || ! is_array( $seat_ids ) ) {
			return '';
		}

		$branch_id = (int) ( $reservation->branch_id ?? 0 );
		$labels    = [];

		foreach ( $seat_ids as $seat_ref ) {
			$resolved = $this->resolve_seat_row( $seat_ref, $branch_id );
			$label    = ( $resolved && ! empty( $resolved['seat_label'] ) ) ? $resolved['seat_label'] : (string) $seat_ref;
			if ( '' !== $label ) {
				$labels[] = $label;
			}
		}

		return implode( ', ', $labels );
	}

	/**
	 * Resolve table name. Priority:
	 *  1. QR-flow `table_name` meta.
	 *  2. Parent-table label resolved from booked seats.
	 *  3. Empty.
	 *
	 * @param Reservation_Model $reservation
	 * @return string
	 */
	private function get_table_name( $reservation ) {
		$table_name = $reservation->table_name ?? '';
		if ( '' !== $table_name ) {
			return $table_name;
		}

		$seat_ids = $reservation->seats ?? [];
		if ( empty( $seat_ids ) || ! is_array( $seat_ids ) ) {
			return '';
		}

		$branch_id    = (int) ( $reservation->branch_id ?? 0 );
		$table_labels = [];
		$seen         = [];

		foreach ( $seat_ids as $seat_ref ) {
			$resolved = $this->resolve_seat_row( $seat_ref, $branch_id );
			if ( ! $resolved || empty( $resolved['table_label'] ) ) {
				continue;
			}
			$label = $resolved['table_label'];
			if ( isset( $seen[ $label ] ) ) {
				continue;
			}
			$seen[ $label ]   = true;
			$table_labels[]   = $label;
		}

		return implode( ', ', $table_labels );
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
			'reservation_table_name'    => $this->get_table_name( $reservation ),
			'reservation_branch_name'   => $reservation->branch_name ?? '',
			'reservation_branch_address'=> $branch_address,
			'reservation_branch_id'     => (string) ( $reservation->branch_id ?? '' ),
			'reservation_status'        => $this->get_reservation_status( $reservation ),
			'reservation_notes'         => $reservation->notes ?? '',
			'reservation_booking_amount'=> (string) ( $reservation->booking_amount ?? '' ),
			'reservation_total_price'   => (string) ( $reservation->total_price ?? '' ),
			'reservation_currency'      => $reservation->currency ?? '',
			'reservation_payment_method'=> $reservation->payment_method ?? '',
			'reservation_food_order'    => $reservation->food_order ?? '',
			'reservation_invoice'       => $reservation->invoice ?? '',
			'reservation_seat_names'    => $this->get_seat_names( $reservation ),
		);

		// Add custom field data to notification
		$custom_fields_data = $this->get_custom_field_data( $reservation );
		$notification_data = array_merge( $notification_data, $custom_fields_data );

		$notification_data = apply_filters( 'wpc_reservation_created_notification_data', $notification_data, $reservation );

		do_action( 'wpcafe_gln_hook', 'reservation_created', $notification_data );
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
			'reservation_table_name'    => $this->get_table_name( $reservation ),
			'reservation_branch_name'   => $reservation->branch_name ?? '',
			'reservation_branch_address'=> $branch_address,
			'reservation_branch_id'     => (string) ( $reservation->branch_id ?? '' ),
			'reservation_status'        => $this->get_reservation_status( $reservation ),
			'reservation_notes'         => $reservation->notes ?? '',
			'reservation_booking_amount'=> (string) ( $reservation->booking_amount ?? '' ),
			'reservation_total_price'   => (string) ( $reservation->total_price ?? '' ),
			'reservation_currency'      => $reservation->currency ?? '',
			'reservation_payment_method'=> $reservation->payment_method ?? '',
			'reservation_food_order'    => $reservation->food_order ?? '',
			'reservation_invoice'       => $reservation->invoice ?? '',
			'reservation_seat_names'    => $this->get_seat_names( $reservation ),
		);

		// Add custom field data to notification
		$custom_fields_data = $this->get_custom_field_data( $reservation );
		$notification_data = array_merge( $notification_data, $custom_fields_data );

		$notification_data = apply_filters( 'wpc_reservation_cancelled_notification_data', $notification_data, $reservation );

		do_action( 'wpcafe_gln_hook', 'reservation_cancelled', $notification_data );
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
			'reservation_table_name'         => $this->get_table_name( $reservation ),
			'reservation_branch_name'        => $reservation->branch_name ?? '',
			'reservation_branch_address'     => $branch_address,
			'reservation_branch_id'          => (string) ( $reservation->branch_id ?? '' ),
			'reservation_status'             => $this->get_reservation_status( $reservation ),
			'reservation_previous_status'    => $old_status,
			'reservation_notes'              => $reservation->notes ?? '',
			'reservation_booking_amount'     => (string) ( $reservation->booking_amount ?? '' ),
			'reservation_total_price'        => (string) ( $reservation->total_price ?? '' ),
			'reservation_currency'           => $reservation->currency ?? '',
			'reservation_payment_method'     => $reservation->payment_method ?? '',
			'reservation_food_order'         => $reservation->food_order ?? '',
			'reservation_invoice'            => $reservation->invoice ?? '',
			'reservation_seat_names'         => $this->get_seat_names( $reservation ),
		);

		// Add custom field data to notification
		$custom_fields_data = $this->get_custom_field_data( $reservation );
		$notification_data = array_merge( $notification_data, $custom_fields_data );

		$notification_data = apply_filters( 'wpc_reservation_status_changed_notification_data', $notification_data, $reservation, $old_status );
		do_action( 'wpcafe_gln_hook', $trigger_event, $notification_data );
	}

	/**
	 * Send reservation updated notification via email automation.
	 *
	 * @param Reservation_Model $reservation         The reservation model instance.
	 * @param array              $old_reservation_data The old reservation data before update.
	 * @return void
	 */
	public function send_reservation_updated_notification( $reservation, $old_reservation_data ) {
		if ( ! $reservation instanceof Reservation_Model ) {
			return;
		}

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
			'reservation_table_name'         => $this->get_table_name( $reservation ),
			'reservation_branch_name'        => $reservation->branch_name ?? '',
			'reservation_branch_address'     => $branch_address,
			'reservation_branch_id'          => (string) ( $reservation->branch_id ?? '' ),
			'reservation_status'             => $this->get_reservation_status( $reservation ),
			'reservation_notes'              => $reservation->notes ?? '',
			'reservation_booking_amount'     => (string) ( $reservation->booking_amount ?? '' ),
			'reservation_total_price'        => (string) ( $reservation->total_price ?? '' ),
			'reservation_currency'           => $reservation->currency ?? '',
			'reservation_payment_method'     => $reservation->payment_method ?? '',
			'reservation_food_order'         => $reservation->food_order ?? '',
			'reservation_invoice'            => $reservation->invoice ?? '',
			'reservation_seat_names'         => $this->get_seat_names( $reservation ),
		);

		// Add custom field data to notification
		$custom_fields_data = $this->get_custom_field_data( $reservation );
		$notification_data = array_merge( $notification_data, $custom_fields_data );

		$notification_data = apply_filters( 'wpc_reservation_updated_notification_data', $notification_data, $reservation, $old_reservation_data );
		do_action( 'wpcafe_gln_hook', 'reservation_updated', $notification_data );
	}

	/**
	 * Get custom field data from reservation and settings
	 *
	 * Retrieves user-added custom fields (where notDeletable is false or not set)
	 * and builds notification data with keys custom_{field_id}
	 *
	 * @param Reservation_Model $reservation
	 * @return array Custom field data with keys custom_{field_id}
	 */
	private function get_custom_field_data( $reservation ) {
		$custom_field_data = [];
		$custom_fields = $reservation->custom_fields ?? [];

		$customization_settings = wpc_get_option( 'reservation_form_customization', [] );

		$user_field_ids = [];
		foreach ( $customization_settings as $step ) {
			if ( empty( $step['fields'] ) ) {
				continue;
			}

			foreach ( $step['fields'] as $field ) {
				$field_id = $field['id'] ?? '';

				// Custom field = BOTH notDeletable AND inGroup do NOT exist in field definition
				if ( $field_id && ! array_key_exists( 'notDeletable', $field ) && ! array_key_exists( 'inGroup', $field ) ) {
					$user_field_ids[] = $field_id;
				}
			}
		}

		foreach ( $user_field_ids as $field_id ) {
			// Only include if value actually exists in custom_fields
			if ( isset( $custom_fields[ $field_id ] ) && ! empty( $custom_fields[ $field_id ] ) ) {
				$key = 'custom_' . $field_id;
				$custom_field_data[ $key ] = $custom_fields[ $field_id ];
			}
		}

		return $custom_field_data;
	}
}
