<?php
namespace WpCafe\Products\Labels;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Registers the 'wpcafe_product_label' taxonomy on WooCommerce products.
 *
 * Tag-style (hierarchical=false) so the WP product edit screen renders the
 * familiar comma-separated tag metabox in the sidebar.
 */
class Product_Label_Taxonomy implements Hookable_Service_Contract {

    const TAXONOMY = 'wpcafe_product_label';

    public function register() {
        add_action( 'init', [ $this, 'register_taxonomy' ], 50 );
    }

    public function register_taxonomy() {
        $labels = [
            'name'                       => __( 'Product Labels', 'wp-cafe' ),
            'singular_name'              => __( 'Product Label', 'wp-cafe' ),
            'search_items'               => __( 'Search Product Labels', 'wp-cafe' ),
            'popular_items'              => __( 'Popular Product Labels', 'wp-cafe' ),
            'all_items'                  => __( 'All Product Labels', 'wp-cafe' ),
            'edit_item'                  => __( 'Edit Product Label', 'wp-cafe' ),
            'update_item'                => __( 'Update Product Label', 'wp-cafe' ),
            'add_new_item'               => __( 'Add New Product Label', 'wp-cafe' ),
            'new_item_name'              => __( 'New Product Label Name', 'wp-cafe' ),
            'separate_items_with_commas' => __( 'Separate labels with commas', 'wp-cafe' ),
            'add_or_remove_items'        => __( 'Add or remove labels', 'wp-cafe' ),
            'choose_from_most_used'      => __( 'Choose from the most used labels', 'wp-cafe' ),
            'not_found'                  => __( 'No labels found.', 'wp-cafe' ),
            'menu_name'                  => __( 'Product Labels', 'wp-cafe' ),
        ];

        $args = [
            'labels'            => $labels,
            'public'            => true,
            'show_in_nav_menus' => true,
            'can_export'        => true,
            'show_admin_column' => false,
            'hierarchical'      => false,
            'query_var'         => true,
            'show_tagcloud'     => false,
            'show_in_menu'      => false,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'rewrite'           => [ 'slug' => 'product-label' ],
        ];

        register_taxonomy( self::TAXONOMY, 'product', $args );
    }
}
