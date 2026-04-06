<?php
namespace WpCafe\Resources;
use WpCafe\Abstract\Resource;

/**
 * Qrcode Resource
 *
 * Handles Qrcode data and interactions.
 *
 * @package WpCafe/Resources
 */
class Qrcode_Resource extends Resource {
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function to_array() {
        return [
            'id'            => $this->data->id,
            'table_id'      => $this->data->table_id,
            'table_name'    => $this->data->table_name,
            'page_url'      => $this->data->page_url,
        ];
    }
}
