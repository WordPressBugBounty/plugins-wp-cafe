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
class Reservation_Item_Resource extends Resource {
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function to_array() {
        return [
            'product_name'      => $this->data->product_name,
            'quantity'          => $this->data->quantity,
            'price'             => $this->data->price,
        ];
    }
}
