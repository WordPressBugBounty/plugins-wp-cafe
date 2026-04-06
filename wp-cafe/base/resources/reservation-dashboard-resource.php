<?php
namespace WpCafe\Resources;
use WpCafe\Abstract\Resource;

/**
 * Reservation Dashboard Resource optimized for better performance
 *
 * @package WpCafe/Resources
 */
class Reservation_Dashboard_Resource extends Resource {
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function to_array() {
        $status     = get_post_status( $this->data->id );
        $start_time = $this->data->start_time;

        return [
            'id'          => $this->data->id,
            'date'        => $this->data->date,
            'start_time'  => ! empty( $start_time ) && is_numeric( $start_time ) ? gmdate('h:i A', $start_time) : '',
            'name'        => $this->data->name,
            'total_guest' => $this->data->total_guest,
            'email'       => $this->data->email,
            'phone'       => $this->data->phone,
            'status'      => $status,
        ];
    }
}
