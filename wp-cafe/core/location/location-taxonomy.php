<?php
namespace WpCafe\Location;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Class to register the custom taxonomy 'wpcafe_location' under WooCommerce products.
 */
class Location_Taxonomy implements Hookable_Service_Contract {

    /**
     * Initialize the class by hooking into WordPress init action.
     */
    public function register() {
        add_action( 'init', [ $this, 'register_taxonomy' ], 50 );
    }

    /**
     * Registers the 'wpcafe_location' taxonomy for WooCommerce products.
     *
     * @return void
     */
    public function register_taxonomy() {
        $labels = [
            'name'              => __( 'Food Locations', 'wp-cafe' ),
            'singular_name'     => __( 'Food Location', 'wp-cafe' ),
            'search_items'      => __( 'Search Food Locations', 'wp-cafe' ),
            'all_items'         => __( 'All Food Locations', 'wp-cafe' ),
            'parent_item'       => __( 'Parent Food Location', 'wp-cafe' ),
            'parent_item_colon' => __( 'Parent Food Location:', 'wp-cafe' ),
            'edit_item'         => __( 'Edit Food Location', 'wp-cafe' ),
            'update_item'       => __( 'Update Food Location', 'wp-cafe' ),
            'add_new_item'      => __( 'Add New Food Location', 'wp-cafe' ),
            'new_item_name'     => __( 'New Food Location Name', 'wp-cafe' ),
            'menu_name'         => __( 'Food Locations', 'wp-cafe' ),
        ];

        $args = [
            'labels'            => $labels,
            'public'            => true,
            'show_in_nav_menus' => true,
            'can_export'        => true,
            'show_admin_column' => false,
            'hierarchical'      => true,
            'query_var'         => true,
            'show_tagcloud'     => true,
            'show_in_menu'      => false,
            'show_ui'           => true,
        ];

        // Register for WooCommerce 'product' post type
        register_taxonomy( 'wpcafe_location', 'product', $args );
    }
}
