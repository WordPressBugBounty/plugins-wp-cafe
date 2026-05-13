<?php
namespace WpCafe\FoodOrder\Shortcodes;

use WpCafe\Abstract\Base_Shortcode;
use WpCafe\Utils\Wpc_Utilities;

/**
 * Food Menu Shortcode
 */
class Food_Location_Menu extends Base_Shortcode {
    /**
     * Shortcode tag name
     *
     * @return  string
     */
    public function tag() {
        return 'wpc_food_location_menu';
    }

    /**
     * Food menu short render content
     *
     * @param   array  $atts     Shortcode attributes
     * @param   string  $content  content
     *
     * @return  string
     */
    public function render($atts = [], $content = null) {
        if ( ! class_exists('Woocommerce') ) {
            return;
        }

        $atts = shortcode_atts(
            [
                'wpc_food_categories'    => '',
                'style'                  => 'style-1',
                'no_of_product'          => 5,
                'show_thumbnail'         => 'yes',
                'wpc_cart_button'        => 'yes',
                'wpc_price_show'         => 'yes',
                'title_link_show'        => 'yes',
                'wpc_menu_col'           => '6',
                'wpc_show_desc'          => 'yes',
                'wpc_desc_limit'         => '15',
                'live_search'            => 'yes',
                'wpc_delivery_time_show' => 'yes',
                'show_item_status'       => 'yes',
                'show_item_label'       => 'no',
                'wpc_menu_order'         => 'DESC',
                'wpc_nav_position'       => 'top',
                'location_alignment'     => 'center',
                'show_pagination'        => 'yes',
            ],
            $atts
        );

        $wpc_food_categories    = $atts['wpc_food_categories'];
        $style                  = $atts['style'];
        $no_of_product          = absint( $atts['no_of_product'] );
        $show_thumbnail         = $atts['show_thumbnail'];
        $wpc_cart_button        = $atts['wpc_cart_button'];
        $wpc_price_show         = $atts['wpc_price_show'];
        $title_link_show        = $atts['title_link_show'];
        $wpc_show_desc          = $atts['wpc_show_desc'];
        $wpc_desc_limit         = $atts['wpc_desc_limit'];
        $wpc_delivery_time_show = $atts['wpc_delivery_time_show'];
        $show_item_status       = $atts['show_item_status'];
        $show_item_label        = $atts['show_item_label'] ?? 'no';
        $wpc_menu_order         = $atts['wpc_menu_order'];
        $location_alignment     = $atts['location_alignment'];
        $wpc_show_vendor        = $atts['wpc_show_vendor'] ?? 'no';
        $show_pagination        = $atts['show_pagination'];

        $allowed_file_names = [ 'style-1' ];
        $style = in_array( $style, $allowed_file_names, true ) ? $style : $allowed_file_names[0];

        $cat_arr = array_filter( array_map( 'trim', explode( ',', $wpc_food_categories ) ) );

        $current_page = 1;

        $query_args = [
            'post_type'     => 'product',
            'no_of_product' => $no_of_product,
            'order'         => $wpc_menu_order,
            'wpc_cat'       => $cat_arr,
            'page'          => $current_page,
        ];

        $selected_location = function_exists( 'wpc_selected_location_id' ) ? wpc_selected_location_id() : null;
        if ( ! empty( $selected_location ) ) {
            $query_args['wpc_location'] = $selected_location;
        }

        $page_result = Wpc_Utilities::product_query_with_pagination( $query_args );
        $products    = $page_result['products'];
        $total_pages = $page_result['total_pages'];

        $unique_id                    = md5( md5( microtime() ) );
        $product_data                 = $atts;
        $product_data['unique_id']    = $unique_id;
        $product_data['wpc_menu_col'] = 'wpc-col-md-8';
        $product_data['style']        = $style;

        ob_start();

        if ( file_exists( wpcafe()->template_directory . "/shortcodes/location-select.php" ) ) {
            ?>
            <div class="location_menu" data-product_data="<?php echo esc_attr( wp_json_encode( $product_data ) ); ?>">
                <?php include wpcafe()->template_directory . "/shortcodes/location-select.php"; ?>
            </div>
            <?php
        }

        return ob_get_clean();
    }
}
