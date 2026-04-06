<?php
namespace WpCafe\Models;

use WpCafe\Database\Post_Model;

class Reservation_Item_Model extends Post_Model {
    /**
     * Store fillable attributes
     *
     * @var array
     */
    protected array $fillable = [  
        'reservation_id' => '',
        'product_id'     => '',
        'product_name'   => '',
        'quantity'       => '',
        'price'          => '',
    ];

    /**
     * Get the post type
     *
     * @return  string  Post type
     */
    public function get_post_type() {
        return 'wpc_reservation_item';
    }
}
