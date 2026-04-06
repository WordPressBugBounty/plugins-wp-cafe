<?php
namespace WpCafe\FoodOrder\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Utils\Wpc_Utilities as Utils;

/**
 * Food Location Ajax
 *
 * Responsible for handling the ajax request for the food location.
 */
class Food_Location_Ajax {

    /**
     * Constructor
     *
     * Responsible for registering the ajax action.
     */
    public function __construct() {
        add_action( 'wp_ajax_filter_food_location', [ $this, 'food_location_ajax' ] );
        add_action( 'wp_ajax_nopriv_filter_food_location', [ $this, 'food_location_ajax' ] );
    }

    /**
     * Food Location Ajax
     *
     * Responsible for handling the ajax request for the food location.
     */
    public function food_location_ajax() {
        global $woocommerce;

        if ( ! wp_verify_nonce( $_REQUEST['_wpc_nonce'], 'filter_food_location_nonce' ) ) {
            wp_send_json_error(
                [
                    'message' => esc_html__( 'Nonce verification failed!', 'wp-cafe' ),
                ]
            );
        }

        $post_arr = filter_input_array( INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS );
        $location = $post_arr['location'];

        if ( isset( $post_arr['product_data'] ) ) {
            $product_data           = $post_arr['product_data'];
            $show_thumbnail         = $product_data['show_thumbnail'];
            $show_item_status       = $product_data['show_item_status'];
            $wpc_cart_button        = $product_data['wpc_cart_button'];
            $wpc_price_show         = $product_data['wpc_price_show'];
            $wpc_show_desc          = $product_data['wpc_show_desc'];
            $wpc_delivery_time_show = $product_data['wpc_delivery_time_show'];
            $wpc_desc_limit         = $product_data['wpc_desc_limit'];
            $unique_id              = $product_data['unique_id'];
            $col                    = 'wpc-col-md-' . $product_data['wpc_menu_col'];
            $title_link_show        = $product_data['title_link_show'];
            $get_location           = $location === '' ? [] : [ $location ];

            $args = [
                'order'    => 'DESC',
                'wpc_cat'  => $get_location,
                'taxonomy' => 'wpcafe_location',
            ];

            $products = Utils::product_query( $args );

            ob_start();
            ?>
            <div class="wpc-food-wrapper wpc-menu-list-style1">
                <?php
                if ( ! empty( $products ) ) {
                    include wpcafe()->plugin_directory . '/widgets/wpc-menus-list/style/style-1.php';
                } else {
                    ?>
                    <div><?php esc_html_e( 'No menu found', 'wp-cafe' ); ?></div>
                    <?php
                }
                ?>
            </div>
            <?php
            $html = ob_get_clean();
        }

        // Clear cart data.
        if ( ! empty( $post_arr['clear_cart'] ) && 1 === (int) $post_arr['clear_cart'] ) {
            $woocommerce->cart->empty_cart();
            WC()->session->set( 'cart', [] );
        }

        // Check cart data.
        $cart_empty = ( WC()->cart->cart_contents_count === 0 ) ? 1 : 0;

        if ( isset( $post_arr['product_data'] ) ) {
            wp_send_json(
                [
                    'html'       => $html,
                    'cart_empty' => $cart_empty,
                ]
            );
        } else {
            wp_send_json(
                [
                    'cart_empty' => $cart_empty,
                ]
            );
        }

        wp_die();
    }
}
