<?php
namespace WpCafe\Resources;
use WpCafe\Abstract\Resource;

/**
 * Location Resource
 *
 * Handles reservation data and interactions.
 *
 * @package WpCafe/Resources
 */
class Location_Resource extends Resource {
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function to_array() {
        return [
            'term_id'                      => $this->data->term_id   ,
            'restaurant_name'              => $this->data->restaurant_name,
            'location'                     => $this->data->location,
            'enable_custom_coordinates'    => $this->data->enable_custom_coordinates,
            'slot_interval'              => intval($this->data->slot_interval),
            'multiply_booking_amount_with_guests' => (bool)$this->data->multiply_booking_amount_with_guests,
            'block_timeslot_statuses'    => $this->data->block_timeslot_statuses,
            'override_restaurant_schedule' => $this->data->override_restaurant_schedule,
            'restaurant_schedule'          => $this->data->restaurant_schedule,
            'enable_pickup'                => $this->data->enable_pickup,
            'pickup_minimum_order_amount'   => floatval($this->data->pickup_minimum_order_amount),
            'pickup_prepare_time'           => intval($this->data->pickup_prepare_time),
            'enable_delivery'              => $this->data->enable_delivery,
            'delivery_fee'                 => floatval($this->data->delivery_fee),
            'delivery_minimum_order_amount' => floatval($this->data->delivery_minimum_order_amount),
            'delivery_prepare_time'         => intval($this->data->delivery_prepare_time),
            'reservation_status'            => $this->data->reservation_status,
            'reservation_minimum_guest'     => intval($this->data->reservation_minimum_guest),
            'reservation_maximum_guest'     => intval($this->data->reservation_maximum_guest),
            'reservation_advanced'          => $this->data->reservation_advanced,
            'reservation_early_booking_time'          => $this->data->reservation_early_booking_time,
            'location_image' => $this->data->location_image,
            'reservation_timeslot'          => intval($this->data->reservation_timeslot),
            'reservation_hold_time_late_guest' => intval($this->data->reservation_hold_time_late_guest),
            'minimum_spend_per_reservation'    => floatval($this->data->minimum_spend_per_reservation),
            'reservation_enable_table_layout'  => $this->data->reservation_enable_table_layout,
            'override_online_ordering'         => $this->data->override_online_ordering,
            'override_reservation'             => $this->data->override_reservation,
            'table_layout'                     => $this->data->table_layout,
            'status'                           => $this->data->status,
            'pickup_schedule'                  => $this->data->pickup_schedule,
            'override_pickup_schedule'         => $this->data->override_pickup_schedule,
            'pickup_slot_interval'             => intval($this->data->pickup_slot_interval),
            'delivery_schedule'                => $this->data->delivery_schedule,
            'override_delivery_schedule'       => $this->data->override_delivery_schedule,
            'reservation_schedule'             => $this->data->reservation_schedule,
            'override_reservation_schedule'    => $this->data->override_reservation_schedule,
            'reservation_slot_interval'        => intval($this->data->reservation_slot_interval),
            'delivery_slot_interval'           => intval($this->data->delivery_slot_interval),
            'enable_reservation'               => $this->data->enable_reservation,
            'reservation_total_seat_capacity'  => intval($this->data->reservation_total_seat_capacity),
            'reservation_booking_amount'       => floatval($this->data->reservation_booking_amount),
            'visual_table_layout'              => $this->data->visual_table_layout,
        ];
    }
}
