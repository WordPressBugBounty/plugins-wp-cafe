<?php
namespace WpCafe\FoodOrder\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Utils\Wpc_Utilities as Utils;
use WpCafe\Session;

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

        if ( ! check_ajax_referer( 'filter_food_location_nonce', '_wpc_nonce', false ) ) {
            wp_send_json_error(
                [
                    'message' => esc_html__( 'Nonce verification failed!', 'wp-cafe' ),
                ]
            );
        }

        $location    = isset( $_POST['location'] ) ? sanitize_text_field( wp_unslash( $_POST['location'] ) ) : '';
        $location_id = absint( $location );

        if ( $location_id ) {
            Session::set( 'selected_location', $location_id );
            setcookie( 'wpc_selected_location', (string) $location_id, time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), false );
            $_COOKIE['wpc_selected_location'] = (string) $location_id;
        } else {
            Session::delete( 'selected_location' );
            setcookie( 'wpc_selected_location', '', time() - 3600, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), false );
            unset( $_COOKIE['wpc_selected_location'] );
        }

        $has_product_data = isset( $_POST['product_data'] ) && is_array( $_POST['product_data'] );

        if ( $has_product_data ) {
            $raw_product_data = wp_unslash( $_POST['product_data'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per-field below.

            $show_thumbnail         = wpc_sanitize_yes_no( $raw_product_data['show_thumbnail'] ?? '', 'yes' );
            $show_item_status       = wpc_sanitize_yes_no( $raw_product_data['show_item_status'] ?? '', 'yes' );
            $show_item_label        = $product_data['show_item_label'] ?? 'no';
            $wpc_cart_button        = wpc_sanitize_yes_no( $raw_product_data['wpc_cart_button'] ?? '', 'yes' );
            $wpc_price_show         = wpc_sanitize_yes_no( $raw_product_data['wpc_price_show'] ?? '', 'yes' );
            $wpc_show_desc          = wpc_sanitize_yes_no( $raw_product_data['wpc_show_desc'] ?? '', 'yes' );
            $wpc_delivery_time_show = wpc_sanitize_yes_no( $raw_product_data['wpc_delivery_time_show'] ?? '', 'no' );
            $title_link_show        = wpc_sanitize_yes_no( $raw_product_data['title_link_show'] ?? '', 'yes' );
            $wpc_desc_limit         = isset( $raw_product_data['wpc_desc_limit'] ) ? absint( $raw_product_data['wpc_desc_limit'] ) : 15;
            $unique_id              = isset( $raw_product_data['unique_id'] ) ? sanitize_text_field( $raw_product_data['unique_id'] ) : '';

            $allowed_cols = [ '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12' ];
            $raw_menu_col = isset( $raw_product_data['wpc_menu_col'] ) ? sanitize_text_field( $raw_product_data['wpc_menu_col'] ) : '3';
            $menu_col     = in_array( $raw_menu_col, $allowed_cols, true ) ? $raw_menu_col : '3';
            $col          = 'wpc-col-md-' . $menu_col;

            $get_location = $location_id ? [ $location_id ] : [];

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
        $clear_cart = isset( $_POST['clear_cart'] ) ? absint( $_POST['clear_cart'] ) : 0;
        if ( 1 === $clear_cart ) {
            $woocommerce->cart->empty_cart();
            WC()->session->set( 'cart', [] );
        }

        // Check cart data.
        $cart_empty = ( WC()->cart->cart_contents_count === 0 ) ? 1 : 0;

        if ( $has_product_data ) {
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
