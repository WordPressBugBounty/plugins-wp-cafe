<?php
namespace WpCafe\FoodOrder\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Utils\Wpc_Utilities;
use WpCafe\Core\Shortcodes\Template_Functions;

/**
 * AJAX handler for paginating menu shortcode product lists.
 */
class Food_Menu_Ajax {

    const ALLOWED_TYPES = [ 'food_menu_list', 'food_menu_tab', 'food_location_menu' ];

    public function __construct() {
        add_action( 'wp_ajax_wpc_food_menu_paginate',        [ $this, 'paginate' ] );
        add_action( 'wp_ajax_nopriv_wpc_food_menu_paginate', [ $this, 'paginate' ] );
    }

    public function paginate() {
        if ( ! check_ajax_referer( 'wpc_food_menu_paginate_nonce', '_wpc_nonce', false ) ) {
            wp_send_json_error( [ 'message' => esc_html__( 'Nonce verification failed', 'wp-cafe' ) ] );
        }

        if ( ! class_exists( 'WooCommerce' ) ) {
            wp_send_json_error( [ 'message' => esc_html__( 'WooCommerce required', 'wp-cafe' ) ] );
        }

        $type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
        if ( ! in_array( $type, self::ALLOWED_TYPES, true ) ) {
            wp_send_json_error( [ 'message' => esc_html__( 'Invalid menu type', 'wp-cafe' ) ] );
        }

        $page = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;

        $raw = isset( $_POST['product_data'] ) && is_array( $_POST['product_data'] )
            ? wp_unslash( $_POST['product_data'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per-field below
            : [];

        switch ( $type ) {
            case 'food_menu_list':
                $html = $this->render_food_menu_list( $raw, $page );
                break;
            case 'food_menu_tab':
                $cat_id = isset( $_POST['cat_id'] ) ? absint( $_POST['cat_id'] ) : 0;
                $html   = $this->render_food_menu_tab( $raw, $page, $cat_id );
                break;
            case 'food_location_menu':
                $html = $this->render_food_location_menu( $raw, $page );
                break;
            default:
                $html = '';
        }

        wp_send_json_success( [ 'html' => $html, 'page' => $page ] );
    }

    private function render_food_menu_list( $raw, $page ) {
        $settings = $this->sanitize_food_menu_list_settings( $raw );

        $food_list_args = [
            'post_type'     => 'product',
            'no_of_product' => max( 1, (int) $settings['wpc_menu_count'] ),
            'wpc_cat'       => $settings['wpc_menu_cat'],
            'order'         => $settings['wpc_menu_order'],
            'taxonomy'      => 'product_cat',
            'page'          => $page,
        ];

        $selected_location = wpc_selected_location_id();
        if ( ! empty( $selected_location ) ) {
            $food_list_args['wpc_location'] = $selected_location;
        }

        $page_result = Wpc_Utilities::product_query_with_pagination( $food_list_args );

        $products       = $page_result['products'];
        $total_pages    = $page_result['total_pages'];

        $style              = $settings['food_menu_style'];
        $show_thumbnail     = $settings['show_thumbnail'];
        $title_link_show    = $settings['title_link_show'];
        $wpc_cart_button    = $settings['wpc_cart_button_show'];
        $wpc_show_desc      = $settings['wpc_show_desc'];
        $wpc_desc_limit     = $settings['wpc_desc_limit'];
        $wpc_price_show     = $settings['wpc_price_show'];
        $show_item_status   = $settings['show_item_status'];
        $wpc_show_vendor    = $settings['wpc_show_vendor'];
        $column_desktop     = $settings['wpc_menu_col'];
        $column_tablet      = $settings['wpc_menu_col_tablet'];
        $column_mobile      = $settings['wpc_menu_col_mobile'];
        $unique_id          = md5( md5( microtime() ) );

        $allowed_styles = [ 'style-1', 'style-2', 'style-3' ];
        $style          = in_array( $style, $allowed_styles, true ) ? $style : 'style-1';

        $show_pagination = $settings['show_pagination'];

        ob_start();
        ?>
        <div class="wpc-paginated-products-body list_template_<?php echo esc_attr( $unique_id ); ?> wpc-nav-shortcode wpc-widget-wrapper">
            <?php include wpcafe()->plugin_directory . "/widgets/wpc-menus-list/style/{$style}.php"; ?>
        </div>
        <?php
        if ( 'yes' === $show_pagination ) {
            echo Wpc_Utilities::render_menu_pagination( $page, $total_pages ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        return ob_get_clean();
    }

    private function render_food_menu_tab( $raw, $page, $cat_id ) {
        $settings = $this->sanitize_food_menu_tab_settings( $raw );

        if ( $cat_id <= 0 && ! empty( $settings['cat_id'] ) ) {
            $cat_id = (int) $settings['cat_id'];
        }

        $post_cats = $cat_id ? [ $cat_id ] : ( $settings['post_cats'] ?: [] );

        $args = [
            'post_type'     => 'product',
            'no_of_product' => max( 1, (int) $settings['no_of_product'] ),
            'wpc_cat'       => $post_cats,
            'order'         => $settings['wpc_menu_order'],
            'page'          => $page,
        ];

        $selected_location = wpc_selected_location_id();
        if ( ! empty( $selected_location ) ) {
            $args['wpc_location'] = $selected_location;
        }

        $page_result = Wpc_Utilities::product_query_with_pagination( $args );

        $products    = $page_result['products'];
        $total_pages = $page_result['total_pages'];

        $style              = $settings['style'];
        $show_thumbnail     = $settings['product_thumbnail'];
        $title_link_show    = $settings['title_link_show'];
        $wpc_cart_button    = $settings['wpc_cart_button'];
        $wpc_show_desc      = $settings['wpc_show_desc'];
        $wpc_desc_limit     = $settings['wpc_desc_limit'];
        $wpc_price_show     = $settings['wpc_price_show'];
        $show_item_status   = $settings['show_item_status'];
        $wpc_show_vendor    = $settings['wpc_show_vendor'] ?? 'no';
        $unique_id          = md5( md5( microtime() ) );

        $is_pro_active  = function_exists( 'wpcafe_pro' ) || defined( 'WPCAFE_PRO_FILE' );
        $allowed_styles = $is_pro_active ? [ 'style-1', 'style-2', 'style-3', 'style-4', 'style-5' ] : [ 'style-1', 'style-2' ];
        $style          = in_array( $style, $allowed_styles, true ) ? $style : 'style-1';

        $template = trailingslashit( wpcafe()->plugin_directory ) . "/widgets/wpc-food-menu-tab/style/{$style}.php";
        if ( ! file_exists( $template ) && $is_pro_active && function_exists( 'wpcafe_pro' ) ) {
            $pro_template = trailingslashit( wpcafe_pro()->plugin_directory ) . "/widgets/food-menu-tab/style/{$style}.php";
            if ( file_exists( $pro_template ) ) {
                $template = $pro_template;
            }
        }

        $wpc_menu_col            = 6;
        $wpc_delivery_time_show  = 'no';
        $wpc_btn_text            = '';
        $customize_btn           = 'no';

        $show_pagination = $settings['show_pagination'];

        ob_start();
        ?>
        <div class="wpc-paginated-products-body">
            <?php
            if ( file_exists( $template ) ) {
                include $template;
            }
            ?>
        </div>
        <?php
        if ( 'yes' === $show_pagination ) {
            echo Wpc_Utilities::render_menu_pagination( $page, $total_pages ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        return ob_get_clean();
    }

    private function render_food_location_menu( $raw, $page ) {
        $settings = $this->sanitize_food_location_menu_settings( $raw );

        $cat_arr = array_filter( array_map( 'trim', explode( ',', $settings['wpc_food_categories'] ) ) );

        $args = [
            'post_type'     => 'product',
            'no_of_product' => max( 1, (int) $settings['no_of_product'] ),
            'wpc_cat'       => $cat_arr,
            'order'         => $settings['wpc_menu_order'],
            'page'          => $page,
        ];

        $selected_location = wpc_selected_location_id();
        if ( ! empty( $selected_location ) ) {
            $args['wpc_location'] = $selected_location;
        }

        $page_result = Wpc_Utilities::product_query_with_pagination( $args );

        $products    = $page_result['products'];
        $total_pages = $page_result['total_pages'];

        $style              = $settings['style'];
        $show_thumbnail     = $settings['show_thumbnail'];
        $title_link_show    = $settings['title_link_show'];
        $wpc_cart_button    = $settings['wpc_cart_button'];
        $wpc_show_desc      = $settings['wpc_show_desc'];
        $wpc_desc_limit     = $settings['wpc_desc_limit'];
        $wpc_price_show     = $settings['wpc_price_show'];
        $show_item_status   = $settings['show_item_status'];
        $wpc_show_vendor    = $settings['wpc_show_vendor'] ?? 'no';
        $unique_id          = md5( md5( microtime() ) );

        $allowed_styles = [ 'style-1' ];
        $style          = in_array( $style, $allowed_styles, true ) ? $style : 'style-1';

        $show_pagination = $settings['show_pagination'];

        ob_start();
        ?>
        <div class="wpc-paginated-products-body">
            <?php include wpcafe()->plugin_directory . "/widgets/wpc-menus-list/style/{$style}.php"; ?>
        </div>
        <?php
        if ( 'yes' === $show_pagination ) {
            echo Wpc_Utilities::render_menu_pagination( $page, $total_pages ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        return ob_get_clean();
    }

    private function sanitize_food_menu_list_settings( $raw ) {
        $cat = isset( $raw['wpc_menu_cat'] ) && is_array( $raw['wpc_menu_cat'] )
            ? array_map( 'absint', $raw['wpc_menu_cat'] )
            : [];

        return [
            'food_menu_style'      => $this->sanitize_style( $raw['food_menu_style'] ?? 'style-1' ),
            'show_thumbnail'       => wpc_sanitize_yes_no( $raw['show_thumbnail'] ?? '', 'yes' ),
            'wpc_price_show'       => $this->sanitize_price_show( $raw['wpc_price_show'] ?? 'yes' ),
            'wpc_cart_button_show' => wpc_sanitize_yes_no( $raw['wpc_cart_button_show'] ?? '', 'yes' ),
            'show_item_status'     => wpc_sanitize_yes_no( $raw['show_item_status'] ?? '', 'yes' ),
            'title_link_show'      => wpc_sanitize_yes_no( $raw['title_link_show'] ?? '', 'yes' ),
            'wpc_show_desc'        => wpc_sanitize_yes_no( $raw['wpc_show_desc'] ?? '', 'yes' ),
            'wpc_desc_limit'       => isset( $raw['wpc_desc_limit'] ) ? absint( $raw['wpc_desc_limit'] ) : 20,
            'wpc_menu_cat'         => $cat,
            'wpc_menu_count'       => isset( $raw['wpc_menu_count'] ) ? max( 1, absint( $raw['wpc_menu_count'] ) ) : 5,
            'wpc_menu_order'       => $this->sanitize_order( $raw['wpc_menu_order'] ?? 'DESC' ),
            'wpc_menu_col'         => isset( $raw['wpc_menu_col'] ) ? sanitize_text_field( $raw['wpc_menu_col'] ) : '4',
            'wpc_menu_col_tablet'  => isset( $raw['wpc_menu_col_tablet'] ) ? sanitize_text_field( $raw['wpc_menu_col_tablet'] ) : '3',
            'wpc_menu_col_mobile'  => isset( $raw['wpc_menu_col_mobile'] ) ? sanitize_text_field( $raw['wpc_menu_col_mobile'] ) : '2',
            'wpc_show_vendor'      => wpc_sanitize_yes_no( $raw['wpc_show_vendor'] ?? '', 'no' ),
            'show_pagination'      => wpc_sanitize_yes_no( $raw['show_pagination'] ?? '', 'yes' ),
        ];
    }

    private function sanitize_food_menu_tab_settings( $raw ) {
        $post_cats = isset( $raw['post_cats'] ) && is_array( $raw['post_cats'] )
            ? array_map( 'absint', $raw['post_cats'] )
            : [];

        return [
            'style'             => $this->sanitize_style( $raw['style'] ?? 'style-1' ),
            'product_thumbnail' => wpc_sanitize_yes_no( $raw['product_thumbnail'] ?? '', 'yes' ),
            'wpc_cart_button'   => wpc_sanitize_yes_no( $raw['wpc_cart_button'] ?? '', 'yes' ),
            'wpc_price_show'    => $this->sanitize_price_show( $raw['wpc_price_show'] ?? 'yes' ),
            'wpc_show_desc'     => wpc_sanitize_yes_no( $raw['wpc_show_desc'] ?? '', 'yes' ),
            'wpc_desc_limit'    => isset( $raw['wpc_desc_limit'] ) ? absint( $raw['wpc_desc_limit'] ) : 20,
            'title_link_show'   => wpc_sanitize_yes_no( $raw['title_link_show'] ?? '', 'yes' ),
            'show_item_status'  => wpc_sanitize_yes_no( $raw['show_item_status'] ?? '', 'yes' ),
            'no_of_product'     => isset( $raw['no_of_product'] ) ? max( 1, absint( $raw['no_of_product'] ) ) : 5,
            'wpc_menu_order'    => $this->sanitize_order( $raw['wpc_menu_order'] ?? 'DESC' ),
            'wpc_show_vendor'   => wpc_sanitize_yes_no( $raw['wpc_show_vendor'] ?? '', 'no' ),
            'show_pagination'   => wpc_sanitize_yes_no( $raw['show_pagination'] ?? '', 'yes' ),
            'cat_id'            => isset( $raw['cat_id'] ) ? absint( $raw['cat_id'] ) : 0,
            'post_cats'         => $post_cats,
        ];
    }

    private function sanitize_food_location_menu_settings( $raw ) {
        return [
            'style'                  => $this->sanitize_style( $raw['style'] ?? 'style-1' ),
            'wpc_food_categories'    => isset( $raw['wpc_food_categories'] ) ? sanitize_text_field( $raw['wpc_food_categories'] ) : '',
            'no_of_product'          => isset( $raw['no_of_product'] ) ? max( 1, absint( $raw['no_of_product'] ) ) : 5,
            'show_thumbnail'         => wpc_sanitize_yes_no( $raw['show_thumbnail'] ?? '', 'yes' ),
            'wpc_cart_button'        => wpc_sanitize_yes_no( $raw['wpc_cart_button'] ?? '', 'yes' ),
            'wpc_price_show'         => $this->sanitize_price_show( $raw['wpc_price_show'] ?? 'yes' ),
            'title_link_show'        => wpc_sanitize_yes_no( $raw['title_link_show'] ?? '', 'yes' ),
            'wpc_show_desc'          => wpc_sanitize_yes_no( $raw['wpc_show_desc'] ?? '', 'yes' ),
            'wpc_desc_limit'         => isset( $raw['wpc_desc_limit'] ) ? absint( $raw['wpc_desc_limit'] ) : 15,
            'wpc_delivery_time_show' => wpc_sanitize_yes_no( $raw['wpc_delivery_time_show'] ?? '', 'no' ),
            'show_item_status'       => wpc_sanitize_yes_no( $raw['show_item_status'] ?? '', 'yes' ),
            'wpc_menu_order'         => $this->sanitize_order( $raw['wpc_menu_order'] ?? 'DESC' ),
            'wpc_show_vendor'        => wpc_sanitize_yes_no( $raw['wpc_show_vendor'] ?? '', 'no' ),
            'show_pagination'        => wpc_sanitize_yes_no( $raw['show_pagination'] ?? '', 'yes' ),
        ];
    }

    private function sanitize_style( $value ) {
        $value = sanitize_text_field( (string) $value );
        return preg_match( '/^style-[1-9][0-9]?$/', $value ) ? $value : 'style-1';
    }

    private function sanitize_order( $value ) {
        $value = strtoupper( sanitize_text_field( (string) $value ) );
        return in_array( $value, [ 'ASC', 'DESC' ], true ) ? $value : 'DESC';
    }

    private function sanitize_price_show( $value ) {
        $value = sanitize_text_field( (string) $value );
        return in_array( $value, [ 'yes', 'no', 'min', 'max' ], true ) ? $value : 'yes';
    }
}
