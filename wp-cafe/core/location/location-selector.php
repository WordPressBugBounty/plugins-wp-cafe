<?php
namespace WpCafe\Location;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Core\Modules\Guten_Block\Inc\Blocks\Location;
use WpCafe\Models\Location_Model;
use WpCafe\Session;

/**
 * Location Selector Class
 */
class Location_Selector implements Hookable_Service_Contract {

    /*
     * True once the inline checkout selector row renders (canonical checkout
     * page or any shortcode that re-fires the WC review-order hook). Gates the
     * footer modal + script so the Edit Location button works wherever it shows.
     */
    private $checkout_selector_rendered = false;

    /**
     * Initialize the class by hooking into WordPress woocommerce_review_order_before_shipping action.
     */
    public function register() {
        add_action( 'woocommerce_review_order_before_order_total', [ $this, 'display_checkout_location_selector' ] );

        add_action('wp_footer', [ $this, 'add_location_modal_html' ] );
        add_action('wp_footer', [ $this, 'add_floating_widget_html' ], 11 );

        add_action( 'wp_ajax_save_location', [ $this, 'save_location' ] );
        add_action( 'wp_ajax_nopriv_save_location', [ $this, 'save_location' ] );

        add_action( 'woocommerce_checkout_create_order', [ $this, 'save_location_meta' ], 10, 2 );

    }

    /**
     * Display mini checkout at location selector.
     *
     * @return void
     */
    public function display_checkout_location_selector() {
        $this->checkout_selector_rendered = true;
        require_once wpcafe()->template_directory . '/location/checkout-location-selector.php';
    }

    /**
     * Add location modal
     *
     * @return  void
     */
    public function add_location_modal_html() {
        /*
         * Load modal + script wherever the checkout row rendered (incl. one-page
         * shortcodes), not only on pages enabled for the auto-open / floating
         * widget — otherwise the Edit Location button has no JS and reloads.
         */
        if ( ! $this->checkout_selector_rendered && ! $this->should_render_on_current_page() ) {
            return;
        }

        $locations            = Location_Model::all();
        $selected_location_id = wpc_selected_location_id();
        $selected_location    = $selected_location_id ? Location_Model::find( $selected_location_id ) : null;
        $primary_color        = wpc_get_option('primary_color') ?: '#c82333';

        wp_enqueue_style( 'wpc-location-selector' );
        wp_enqueue_script( 'wpc-location-selector' );

        require wpcafe()->template_directory . '/location/location-selector-popup.php';
    }

    /**
     * Render floating location widget when enabled.
     *
     * Honors the same page rules as the auto-open modal so the widget
     * never appears on pages where the location selector itself is hidden.
     *
     * @return void
     */
    public function add_floating_widget_html() {
        if ( ! wpc_get_option('enable_floating_location_widget') ) {
            return;
        }

        if ( ! function_exists('wpc_is_module_enable') || ! wpc_is_module_enable('location') ) {
            return;
        }

        if ( ! $this->should_render_on_current_page() ) {
            return;
        }

        $selected_location_id = wpc_selected_location_id();
        $selected_location    = $selected_location_id ? Location_Model::find( $selected_location_id ) : null;
        $primary_color        = wpc_get_option('primary_color') ?: '#c82333';

        wp_enqueue_style( 'wpc-location-selector' );
        wp_enqueue_script( 'wpc-location-selector' );

        require wpcafe()->template_directory . '/location/location-floating-widget.php';
    }

    /**
     * Whether the location selector (modal + widget) is allowed on the current page.
     *
     * @return bool
     */
    private function should_render_on_current_page() {
        $location_display        = wpc_get_option('display_location_selector', 'dont_show');
        $location_selector_pages = wpc_get_option('location_selector_pages', []);

        if ( $location_display === 'dont_show' ) {
            return false;
        }

        if ( $location_display === 'specific_pages' ) {
            if ( empty( $location_selector_pages ) || ! is_array( $location_selector_pages ) ) {
                return false;
            }
            return $this->is_current_page_in_selected_pages( $location_selector_pages );
        }

        return true;
    }

    /**
     * Check if current page is in selected pages array
     * Handles both regular pages and archive pages (shop, category, etc.)
     *
     * @param array $selected_pages Array of page IDs
     * @return bool
     */
    private function is_current_page_in_selected_pages( $selected_pages ) {
        if ( empty( $selected_pages ) || ! is_array( $selected_pages ) ) {
            return false;
        }

        // Get current page/post ID for regular pages
        $current_page_id = get_the_ID();

        // Check if current page ID is in selected pages
        if ( $current_page_id && in_array( $current_page_id, $selected_pages, true ) ) {
            return true;
        }

        // Check for WooCommerce shop page and product archives
        if ( function_exists( 'wc_get_page_id' ) ) {
            $shop_page_id = wc_get_page_id( 'shop' );
            
            // Check if shop page ID is in selected pages
            if ( $shop_page_id && in_array( $shop_page_id, $selected_pages, true ) ) {
                // Check if we're on shop page or any product archive
                if ( function_exists( 'is_shop' ) && is_shop() ) {
                    return true;
                }
                
                // Check for product category archive
                if ( function_exists( 'is_product_category' ) && is_product_category() ) {
                    return true;
                }
                
                // Check for product tag archive
                if ( function_exists( 'is_product_tag' ) && is_product_tag() ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Save location
     *
     * @return  json
     */
    public function save_location() {
        check_ajax_referer( 'wpc_location_nonce', 'nonce' );

        $location_id = ! empty( $_POST['location_id'] ) ? intval( $_POST['location_id'] ) : 0;

        if ( ! WC()->cart->is_empty() && $location_id != wpc_selected_location_id() ){
            WC()->cart->empty_cart();
        }

        // location_id === 0 means "clear selection". The session and the cookie
        // mirror must both be wiped, else wpc_selected_location_id() rehydrates
        // the session from the surviving cookie and the clear silently fails.
        if ( 0 === $location_id ) {
            Session::delete( 'selected_location' );
            setcookie( 'wpc_selected_location', '', time() - 3600, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), false );
            unset( $_COOKIE['wpc_selected_location'] );

            wp_send_json_success([
                'message' => __( 'Location cleared', 'wp-cafe' ),
            ]);
        }

        Session::set( 'selected_location', $location_id );
        // Mirror to the cookie so the choice survives session expiry, matching
        // the read fallback in wpc_selected_location_id().
        setcookie( 'wpc_selected_location', (string) $location_id, time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), false );
        $_COOKIE['wpc_selected_location'] = (string) $location_id;

        wp_send_json_success([
            'message' => __( 'Successfully updated location', 'wp-cafe' )
        ]);
    }

    /**
     * Save location meta data
     *
     * @param   Object  $order  WC Order Object
     * @param   array  $data   Order data
     *
     * @return  void
     */
    public function save_location_meta( $order, $data ) {
        $selected_location_id = wpc_selected_location_id();
        $location   = Location_Model::find( $selected_location_id );

        if ( ! $location ) {
            return;
        }

        $order->update_meta_data( 'wpc_location_id', $location->term_id );
        $order->update_meta_data( 'wpc_location_name', $location->location );
    }
}
