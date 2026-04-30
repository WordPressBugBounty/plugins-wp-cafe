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

    /**
     * Initialize the class by hooking into WordPress woocommerce_review_order_before_shipping action.
     */
    public function register() {
        add_action( 'woocommerce_review_order_before_order_total', [ $this, 'display_checkout_location_selector' ] );

        add_action('wp_footer', [ $this, 'add_location_modal_html' ] );

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
        require_once wpcafe()->template_directory . '/location/checkout-location-selector.php';
    }

    /**
     * Add location modal
     *
     * @return  void
     */
    public function add_location_modal_html() { 
        $location_display = wpc_get_option('display_location_selector', 'dont_show');
        $location_selector_pages = wpc_get_option('location_selector_pages', []);
        
        // Don't show if disabled
        if ( $location_display == 'dont_show') {
            return;
        }
 
        // If "specific_pages" is selected, check if current page is in the list
        if ( $location_display == 'specific_pages' ) {
            // Only check pages if specific_pages mode is selected
            if ( ! empty( $location_selector_pages ) && is_array( $location_selector_pages ) ) {
                if ( ! $this->is_current_page_in_selected_pages( $location_selector_pages ) ) {
                    return;
                }
            } else {
                // If specific_pages is selected but no pages are chosen, don't show
                return;
            }
        }
        
        // For "all_pages" mode, show on all pages including home page

        $locations         = Location_Model::all();
        $selected_location_id = wpc_selected_location_id();
        wp_enqueue_style( 'wpc-location-selector' );
        wp_enqueue_script( 'wpc-location-selector' );

        require_once wpcafe()->template_directory . '/location/location-selector-popup.php';
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

        Session::set( 'selected_location', $location_id );

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
