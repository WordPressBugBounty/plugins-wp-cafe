<?php
namespace WpCafe\FoodOrder\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Abstract\Base_Shortcode;
use WpCafe\Utils\Wpc_Utilities;

/**
 * Food Menu Tab Shortcode
 */
class Food_Menu_Tab extends Base_Shortcode {

    /**
     * Shortcode tag name.
     *
     * @return string
     */
    public function tag() {
        return 'wpc_food_menu_tab';
    }

    /**
     * Render shortcode content.
     *
     * @param array  $atts    Shortcode attributes.
     * @param string $content Shortcode content.
     *
     * @return string
     */
    public function render( $atts = [], $content = null ) {
        if (! class_exists('Woocommerce') ) { return; }
        $settings = array();
        $atts     = Wpc_Utilities::replace_qoute( $atts );

        $atts = extract(shortcode_atts([
            'style'                 => 'style-1',
            'wpc_food_categories'   => '',
            'no_of_product'         => 5,
            'wpc_desc_limit'        => 20,
            'wpc_menu_order'        => 'DESC',
            'wpc_show_desc'         => 'yes',
            'title_link_show'       => 'yes',
            'show_item_status'      => 'yes',
            'product_thumbnail'     => 'yes',
            'wpc_cart_button'       => 'yes',
            'wpc_price_show'        => 'yes',
        ], $atts));

        ob_start();
        $wpc_cat_arr  = explode(',', $wpc_food_categories);

        // Check if categories were provided and are not empty
        $has_categories = ! empty( $wpc_food_categories ) && ! empty( $wpc_cat_arr[0] );

        if ( ! $has_categories ) {
            // Fetch all categories when no categories provided
            $args_cat = array(
                'taxonomy'     => 'product_cat',
                'number'       => 0,
                'hide_empty'   => 0,
                'orderby'      => 'term_order',
            );
            $all_categories = get_categories( $args_cat );
            $wpc_cat_arr = wp_list_pluck( $all_categories, 'term_id' );
        }

        // Get tabs for provided or all categories
        $food_menu_tabs = Wpc_Utilities::get_tab_array_from_category($wpc_cat_arr);

        if ( ! $has_categories && ! empty( $food_menu_tabs ) ) {
            $food_menu_tabs = $this->add_all_products_tab($food_menu_tabs);
        }

        // sort category list
        if ( !empty($food_menu_tabs) ) {
            ksort($food_menu_tabs);

            $unique_id = md5(md5(microtime()));
            $settings["food_menu_tabs"]         = $food_menu_tabs;
            $settings["food_tab_menu_style"]    = $style;
            $settings["show_thumbnail"]         = $product_thumbnail;
            $settings["wpc_menu_order"]         = $wpc_menu_order;
            $settings["show_item_status"]       = $show_item_status;
            $settings["wpc_menu_count"]         = $no_of_product;
            $settings["wpc_show_desc"]          = $wpc_show_desc;
            $settings["wpc_desc_limit"]         = $wpc_desc_limit;
            $settings["title_link_show"]        = $title_link_show;
            $settings["wpc_cart_button"]        = $wpc_cart_button;
            $settings["wpc_price_show"]        = $wpc_price_show;
            // render template
            $template = wpcafe()->template_directory . "/shortcodes/food-tab.php";

            $is_pro_active = function_exists('wpcafe_pro') || defined('WPCAFE_PRO_FILE');
            $allowed_styles = $is_pro_active ? ['style-1', 'style-2', 'style-3', 'style-4', 'style-5'] : ['style-1', 'style-2'];
            $style_file_exists = file_exists( wpcafe()->plugin_directory . "/widgets/wpc-food-menu-tab/style/{$style}.php" );

            if ( ! $style_file_exists && $is_pro_active && function_exists('wpcafe_pro') ) {
                $pro_style_path = wpcafe_pro()->plugin_directory . "/widgets/food-menu-tab/style/{$style}.php";
                $style_file_exists = file_exists( $pro_style_path );
            }

            if ( in_array( $style, $allowed_styles, true ) && $style_file_exists && file_exists( $template ) ){
                include $template;
            }
        }
        
        return ob_get_clean();
    }

    /**
     * Add "All Products" tab to the beginning of food menu tabs.
     *
     * @param array $food_menu_tabs The existing food menu tabs.
     *
     * @return array The tabs with "All Products" prepended.
     */
    private function add_all_products_tab( $food_menu_tabs ) {
        $all_products_tab = [
            'post_cats' => [ 'all-products' ],
            'tab_title' => __('All Products', 'wp-cafe')
        ];

        // Add filter to handle the special 'all-products' marker
        add_filter( 'wpc_product_query_args', [ $this, 'handle_all_products_query' ], 10, 1 );

        return array_merge( [ $all_products_tab ] , $food_menu_tabs );
    }

    /**
     * Handle product query for "All Products" tab.
     * When 'all-products' marker is detected, remove the product_cat taxonomy query but preserve other taxonomies (like location).
     *
     * @param array $args The product query arguments.
     *
     * @return array Modified query arguments.
     */
    public function handle_all_products_query( $args ) {
        if ( $this->should_clear_taxonomy_query( $args ) ) {
            $filtered_tax_query = array( 'relation' => 'AND' );

            foreach ( $args['tax_query'] as $key => $query ) {
                if ( $key === 'relation' || $this->is_query_for_all_products( $query ) ) {
                    continue;
                }

                $filtered_tax_query[] = $query;
            }

            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- required for report/filter functionality
            $args['tax_query'] = $filtered_tax_query;
        }

        return $args;
    }

    /**
     * Check if the tax_query contains the 'all-products' marker.
     *
     * @param array $args The product query arguments.
     *
     * @return bool True if tax_query should be cleared for 'all-products' tab.
     */
    private function should_clear_taxonomy_query( $args ) {
        if ( ! $this->has_valid_tax_query( $args ) ) {
            return false;
        }

        return $this->contains_all_products_marker( $args['tax_query'] );
    }

    /**
     * Validate if tax_query (taxonomy query) exists and is an array.
     *
     * @param array $args The product query arguments.
     *
     * @return bool True if tax_query is valid.
     */
    private function has_valid_tax_query( $args ) {
        return ! empty( $args['tax_query'] ) && is_array( $args['tax_query'] );
    }

    /**
     * Check if tax_query (taxonomy query) contains the 'all-products' marker.
     *
     * @param array $tax_query The tax query array.
     *
     * @return bool True if 'all-products' marker is found.
     */
    private function contains_all_products_marker( $tax_query ) {
        foreach ( $tax_query as $query ) {
            if ( $this->is_query_for_all_products( $query ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a single query contains the 'all-products' marker.
     *
     * @param array $query The individual tax query.
     *
     * @return bool True if query contains 'all-products' marker.
     */
    private function is_query_for_all_products( $query ) {
        return isset( $query['terms'] ) 
            && is_array( $query['terms'] ) 
            && in_array( 'all-products', $query['terms'], true );
    }
}
