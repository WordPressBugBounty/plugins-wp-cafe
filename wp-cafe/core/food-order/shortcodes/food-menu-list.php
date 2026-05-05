<?php
namespace WpCafe\FoodOrder\Shortcodes;

use WpCafe\Abstract\Base_Shortcode;
use WpCafe\Utils\Wpc_Utilities;

/**
 * Food Menu Shortcode
 */
class Food_Menu_List extends Base_Shortcode {
    /**
     * Shortcode tag name
     *
     * @return  string
     */
    public function tag() {
        return 'wpc_food_menu_list';
    }

    /**
     * Food menu short render content
     *
     * @param   array  $atts     Shortcode attributes
     * @param   string  $content  content
     *
     * @return  []  [return description]
     */
    public function render($atts = [], $content = null) {
        if (!class_exists('Woocommerce')) { return; }

        $atts    = Wpc_Utilities::replace_qoute( $atts );
        $atts    = extract(shortcode_atts(
            [
                'style'                 => 'style-1',
                'wpc_food_categories'   => '',
                'no_of_product'         => 5,
                'wpc_cart_button'       => 'yes',
                'product_thumbnail'     => 'yes',
                'wpc_price_show'        => 'yes',
                'show_item_status'      => 'yes',
                'wpc_show_desc'         => 'yes',
                'title_link_show'       => 'yes',
                'wpc_desc_limit'        => 20,
                'wpc_menu_order'        => 'DESC',
                'wpc_menu_col'          => '4',
                'wpc_menu_col_tablet'   => '3',
                'wpc_menu_col_mobile'   => '2',
                'wpc_show_vendor'       => 'no',
                'wpc_location'          => ''
            ],
            $atts
        ));

        $allowed_file_names = [
            'style-1',
            'style-2',
            'style-3'
        ];
    
        if( in_array($style, $allowed_file_names)){
            $template_file = esc_html($style);
        }else{
            $template_file = $allowed_file_names[0];
        }

        ob_start();
        // category sorting from backend
        $wpc_cat_arr      = explode(',', $wpc_food_categories);

        // Show all products if no categories provided, or only specified categories
        $has_categories = is_array($wpc_cat_arr) && count($wpc_cat_arr) > 0 && !empty($wpc_cat_arr[0]);

        if ( $has_categories || empty( $wpc_food_categories ) ) {
            $unique_id = md5(md5(microtime()));
            $settings = array();
            $settings["food_menu_style"]        = $template_file;
            $settings["show_thumbnail"]         = $product_thumbnail;
            $settings["wpc_price_show"]         = $wpc_price_show;
            $settings["wpc_cart_button_show"]   = $wpc_cart_button;
            $settings["show_item_status"]       = $show_item_status;
            $settings["title_link_show"]        = $title_link_show;
            $settings["wpc_show_desc"]          = $wpc_show_desc;
            $settings["wpc_desc_limit"]         = $wpc_desc_limit;
            $settings["wpc_menu_cat"]           = $has_categories ? $wpc_cat_arr : [];
            $settings["wpc_menu_count"]         = $no_of_product;
            $settings["wpc_menu_order"]         = $wpc_menu_order;

            $settings['wpc_menu_col']           = $wpc_menu_col;
            $settings['wpc_menu_col_tablet']    = $wpc_menu_col_tablet;
            $settings['wpc_menu_col_mobile']    = $wpc_menu_col_mobile;
            $settings["wpc_show_vendor"]        = $wpc_show_vendor;
            $settings["wpc_location"]           = !empty($wpc_location) ? intval($wpc_location) : null;

            // render template
            $template = wpcafe()->template_directory . "/shortcodes/food-list.php";
            if( file_exists( $template ) ){
                include $template;
            }
        }
        return ob_get_clean();
    }
}
