<?php
namespace WpCafe\FoodOrder\Shortcodes;

use WpCafe\Abstract\Base_Shortcode;

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
     * @return  []                [return description]
     */
    public function render($atts = [], $content = null) {
        if ( ! class_exists('Woocommerce') ) {
            return;
        }

        ob_start();
        $unique_id = md5(md5(microtime()));
        $product_data               = $atts;
        $product_data['unique_id']  = $unique_id ;
        $product_data['wpc_menu_col']  = 'wpc-col-md-8' ;
        
        // shortcode option
        $atts = extract(shortcode_atts(
            [
                'wpc_food_categories'   => '',
                'style'                 => 'style-1',
                'no_of_product'         => 5,
                'show_thumbnail'        => "yes",
                'wpc_cart_button'       => 'yes',
                'wpc_price_show'       => 'yes',
                'title_link_show'       => 'yes',
                'wpc_menu_col'          => '6',
                'wpc_show_desc'         => 'yes',
                'wpc_desc_limit'        => '15',
                'live_search'           => 'yes',
                'wpc_delivery_time_show'=> 'yes',
                'show_item_status'      => 'yes',
                'wpc_menu_order'        => 'DESC',
                'wpc_nav_position'      => 'top',
                'location_alignment '   => 'center'
            ], $atts ));

         
        $location_alignment = "center";

        $allowed_file_names = [
            'style-1'
        ];
        if( in_array($style, $allowed_file_names)){
            $style = esc_html($style);
        }else{
            $style = $allowed_file_names[0];
        }

        $products = wc_get_products([]);

        if ( file_exists( wpcafe()->template_directory . "/shortcodes/location-select.php" ) ) {
            ?>
            <div class="location_menu" data-product_data ="<?php echo esc_attr( json_encode( $product_data  ));?>">
                <?php include_once wpcafe()->template_directory . "/shortcodes/location-select.php"; ?>
            </div>
            <?php
        }
        
        return ob_get_clean();
    }
}
