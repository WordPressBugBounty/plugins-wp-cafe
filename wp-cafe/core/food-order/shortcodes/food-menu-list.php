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

        // FE2: load the shared card stylesheet only where a card actually renders.
        wp_enqueue_style( 'wpc-card-core' );
        wp_enqueue_style( 'wpc-popup' );
        wp_enqueue_style( 'wpc-pagination' );

        $atts = Wpc_Utilities::replace_qoute( $atts );
        $atts = shortcode_atts(
            [
                'style'               => 'style-1',
                'wpc_food_categories' => '',
                'no_of_product'       => 5,
                'wpc_cart_button'     => 'yes',
                'product_thumbnail'   => 'yes',
                'wpc_price_show'      => 'yes',
                'show_item_status'    => 'yes',
                'show_item_label'       => 'no',
                'wpc_show_desc'       => 'yes',
                'title_link_show'     => 'yes',
                'wpc_desc_limit'      => 20,
                'wpc_menu_order'      => 'DESC',
                'wpc_menu_col'        => '4',
                'wpc_menu_col_tablet' => '3',
                'wpc_menu_col_mobile' => '2',
                'wpc_show_vendor'     => 'no',
                'show_pagination'     => 'yes',
            ],
            $atts
        );

        $style               = $atts['style'];
        $wpc_food_categories = $atts['wpc_food_categories'];
        $no_of_product       = $atts['no_of_product'];
        $wpc_cart_button     = $atts['wpc_cart_button'];
        $product_thumbnail   = $atts['product_thumbnail'];
        $wpc_price_show      = $atts['wpc_price_show'];
        $show_item_status    = $atts['show_item_status'];
        $show_item_label     = $atts['show_item_label'];
        $wpc_show_desc       = $atts['wpc_show_desc'];
        $title_link_show     = $atts['title_link_show'];
        $wpc_desc_limit      = $atts['wpc_desc_limit'];
        $wpc_menu_order      = $atts['wpc_menu_order'];
        $wpc_menu_col        = $atts['wpc_menu_col'];
        $wpc_menu_col_tablet = $atts['wpc_menu_col_tablet'];
        $wpc_menu_col_mobile = $atts['wpc_menu_col_mobile'];
        $wpc_show_vendor     = $atts['wpc_show_vendor'];
        $show_pagination     = $atts['show_pagination'];

        $allowed_file_names = [ 'style-1', 'style-2', 'style-3', 'style-4' ];
        $template_file = in_array( $style, $allowed_file_names, true ) ? $style : $allowed_file_names[0];

        // Style-specific CSS must be enqueued here, on the host page: the
        // pagination AJAX handler renders the same style but cannot enqueue.
        wpc_enqueue_food_menu_style_assets( $template_file, 'list' );

        ob_start();

        $wpc_cat_arr    = array_filter( array_map( 'trim', explode( ',', $wpc_food_categories ) ) );
        $has_categories = ! empty( $wpc_cat_arr );

        $unique_id = md5( md5( microtime() ) );
        $settings = [
            'food_menu_style'      => $template_file,
            'show_thumbnail'       => $product_thumbnail,
            'wpc_price_show'       => $wpc_price_show,
            'wpc_cart_button_show' => $wpc_cart_button,
            'show_item_status'     => $show_item_status,
            'show_item_label'      => $show_item_label,
            'title_link_show'      => $title_link_show,
            'wpc_show_desc'        => $wpc_show_desc,
            'wpc_desc_limit'       => $wpc_desc_limit,
            'wpc_menu_cat'         => $has_categories ? $wpc_cat_arr : [],
            'wpc_menu_count'       => $no_of_product,
            'wpc_menu_order'       => $wpc_menu_order,
            'wpc_menu_col'         => $wpc_menu_col,
            'wpc_menu_col_tablet'  => $wpc_menu_col_tablet,
            'wpc_menu_col_mobile'  => $wpc_menu_col_mobile,
            'wpc_show_vendor'      => $wpc_show_vendor,
            'show_pagination'      => $show_pagination,
        ];

        $template = wpcafe()->template_directory . "/shortcodes/food-list.php";
        if ( file_exists( $template ) ) {
            include $template;
        }

        return ob_get_clean();
    }
}
