<?php
namespace WpCafe\Models;

use WpCafe\Database\Post_Model;

class Qrcode_Model extends Post_Model {
    /**
     * Store fillable attributes
     *
     * @var array
     */
    protected array $fillable = [  
        'table_id'   => '',
        'table_name' => '',
        'page_url'   => '',
    ];

    /**
     * Get the post type
     *
     * @return  string  Post type
     */
    public function get_post_type() {
        return 'wpc-qrcode';
    }
    
    /**
     * Build a meta query array for searching qrcode posts by a given value.
     *
     * Searches the 'table_name', 'table_id', and 'page_url' meta fields for the provided value.
     *
     * @param string $search_value The value to search for in the qrcode meta fields.
     * @return array The meta query array for use in WP_Query or similar.
     */
    public function search_query( $search_value ) {
        $meta_query = [
            'relation' => 'OR',
            [
                'key'     => 'table_name',
                'value'   => $search_value,
                'compare' => 'LIKE',
            ],
            [
                'key'     => 'table_id',
                'value'   => $search_value,
                'compare' => 'LIKE',
            ],
            [
                'key'     => 'page_url',
                'value'   => $search_value,
                'compare' => 'LIKE',
            ],
        ];
        
        return $meta_query;
    }
}
