<?php
namespace WpCafe\Assets;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Session;
use WpCafe\Settings;

/**
 * Manage all frontend scripts and styles
 */
class Frontend_Assets extends Base_Assets {
    /**
     * Register single service
     *
     * @return  void
     */
    public function register() {
        add_action( 'wp_enqueue_scripts',  [$this, 'register_styles_scripts'] );
        add_action( 'wp_enqueue_scripts',  [$this, 'enqueue'] );
    }

    /**
     * Enqueue scripts and styles
     *
     * @return  void
     */
    public function enqueue() {

        wp_enqueue_style( 'wpcafe-frontend-style' );
        wp_enqueue_style( 'wpc-public' );
        wp_enqueue_script( 'wpc-public' );
        wp_enqueue_style( 'wpc-icon' );

        // Force-enqueue WooCommerce's frontend scripts so the food-menu
        // shortcode + customize popup work on arbitrary pages. WC only
        // auto-loads these on shop / archive / single-product pages.
        //   - wc-add-to-cart            : AJAX add-to-cart for simple products
        //   - wc-cart-fragments         : mini-cart auto-refresh
        //   - wc-add-to-cart-variation  : variation form (matches selected
        //                                 attributes to a variation_id; the
        //                                 popup add-to-cart depends on this)
        //   - wc-single-product         : tabs / image zoom inside the modal
        if ( function_exists( 'WC' ) ) {
            wp_enqueue_script( 'wc-add-to-cart' );
            wp_enqueue_script( 'wc-cart-fragments' );
            wp_enqueue_script( 'wc-add-to-cart-variation' );
            wp_enqueue_script( 'wc-single-product' );
        }

        if(function_exists('is_cart') && is_cart() || function_exists('is_checkout') && is_checkout()) {
             wp_enqueue_script( 'wpc-flatpicker' );
             wp_enqueue_style( 'flatpicker' );
        }

        
        $form_data                        = [];
        $form_data['settings']            = Settings::get();
        $form_data['wpc_ajax_url']        = admin_url( 'admin-ajax.php' );
        $form_data['wpc_validation_message'] = [
            'error_text'    => esc_html__('Please fill the field', 'wp-cafe'),
            'email'         => esc_html__('Email is not valid', 'wp-cafe'),
            'phone'         => [
                'phone_invalid'     => esc_html__('Invalid phone number', 'wp-cafe'),
                'number_allowed'    => esc_html__('Only number allowed', 'wp-cafe'),
             ],
             'table_layout'         => [
                'empty'         => esc_html__( 'Please choose available table/chair for reservation', 'wp-cafe' ),
                'min_invalid'   => esc_html__( 'Minimum allowed guest is ', 'wp-cafe' ),
                'max_invalid'   => esc_html__( 'Maximum allowed guest is ', 'wp-cafe' ),
             ],
        ];
        $form_data['wpc_form_dynamic_text'] = [
            'wpc_guest_count'    => esc_html__('Select number of guests', 'wp-cafe'),
            'wpc_additional_information'    => esc_html__('Additional Information:', 'wp-cafe')
        ];

        $form_data['_nonces'] = [
            'wpc_check_for_submission_nonce'    => wp_create_nonce('wpc_check_for_submission_nonce'),
            'filter_food_location_nonce'        => wp_create_nonce('filter_food_location_nonce'),
            'wpc_seat_capacity_nonce'           => wp_create_nonce('wpc_seat_capacity_nonce')
        ];
        wp_localize_script( 'wpc-public', 'wpc_form_client_data', $form_data );
        wp_localize_script( 'wpc-public', 'wpCafe',  Localize::get_frontend() );

        wp_localize_script( 'wpc-location-selector', 'wpcLocation', [
            'ajaxUrl'          => admin_url('admin-ajax.php'),
            'nonce'            => wp_create_nonce( 'wpc_location_nonce' ),
            'selectedLocation' => Session::get('selected_location'),
            'wc_cart_url'      => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
            'require_location'          => wpc_get_option('require_location'),
            'location_selector'         => wpc_get_option('display_location_selector', 'dont_show'),
            'location_selector_pages'   => wpc_get_option('location_selector_pages'),
            'current_page_id'           => get_the_ID(),
            'wc_cart_empty'             => function_exists( 'WC' ) && WC()->cart ? WC()->cart->is_empty() : true,
        ] );

        wp_set_script_translations(
            'wpcafe-frontend-scripts',
            'wp-cafe' // text domain
        );

        $this->enqueue_i18n_loader();
    }

    /**
     * Get all scripts
     *
     * @return  array List register scripts
     */
    public function get_scripts() {
        $scripts = [
             'wpcafe-i18n' => [
                'src'       => wpcafe()->assets_url . '/build/js/i18n-loader.js',
                'deps'      => [],
                'in_footer' => true,
            ],
            'wpcafe-frontend-scripts'     => [
                'src'       => wpcafe()->assets_url . '/build/js/frontend.js',
                'deps'      => ['wp-i18n', 'wp-data','wp-api-fetch'],
                'in_footer' => true,
            ],
            'wpcafe-restaurant-management-scripts' => [
                'src'       => wpcafe()->assets_url . '/build/js/restaurant-management.js',
                'deps'      => ['wp-i18n', 'wp-element', 'wp-api-fetch'],
                'in_footer' => true,
            ],
            'wpc-flatpicker'     => [
                'src'       => wpcafe()->assets_url . '/js/flatpickr.min.js',
                'deps'      => ['jquery'],
                'in_footer' => true,
            ],
            'wpc-public'    => [
                'src'       => wpcafe()->assets_url . '/js/wpc-public.js',
                'deps'      => ['jquery'],
                'in_footer' => true,
            ],
            'wpc-location-selector'    => [
                'src'       => wpcafe()->assets_url . '/js/location-selector.js',
                'deps'      => ['jquery'],
                'in_footer' => true,
            ], 
            'wpc-tip'    => [
                'src'       => wpcafe()->assets_url . '/js/tip.js',
                'deps'      => ['jquery'],
                'in_footer' => true,
            ],
            'wpc-mini-cart' => [
                'src'       => wpcafe()->assets_url . '/js/mini-cart.js',
                'deps'      => ['jquery'],
                'in_footer' => true,
            ],
        ];

        return apply_filters( 'wpcafe_frontend_scripts', $scripts );
    }

    /**
     * List of register styles
     *
     * @return  array
     */
    public function get_styles() {
        $styles = [
            'wpcafe-frontend-style'    => [
                'src' => wpcafe()->assets_url . '/build/css/frontend.css',
            ],
            'wpcafe-restaurant-management-style' => [
                'src' => wpcafe()->assets_url . '/build/css/restaurant-management.css',
            ],
            'flatpicker'    => [
                'src' => wpcafe()->assets_url . '/css/flatpickr.min.css',
            ],
            'wpc-public'    => [
                'src' => wpcafe()->assets_url . '/css/wpc-public.css',
            ],
            'wpc-location-selector'    => [
                'src' => wpcafe()->assets_url . '/css/location-selector.css',
            ],
            'wpc-icon'    => [
                'src' => wpcafe()->assets_url . '/css/wpc-icon.css',
            ],
            'wpc-tip'    => [
                'src' => wpcafe()->assets_url . '/css/tip.css',
            ],
        ];

        return apply_filters( 'wpcafe_frontend_styles', $styles );
    }
}