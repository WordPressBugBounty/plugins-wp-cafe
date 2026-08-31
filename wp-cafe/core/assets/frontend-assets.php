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
        add_action( 'elementor/preview/enqueue_scripts', [ $this, 'enqueue_for_elementor_preview' ] );
    }

    /**
     * Enqueue shortcode assets inside the Elementor editor preview iframe.
     *
     * @return void
     */
    public function enqueue_for_elementor_preview() {
        if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
            return;
        }

        $preview = \Elementor\Plugin::$instance->preview ?? null;
        $post_id = $preview && method_exists( $preview, 'get_post_id' )
            ? (int) $preview->get_post_id()
            : (int) get_the_ID();

        if ( ! function_exists( 'wpc_post_uses_shortcode' )
            || ! wpc_post_uses_shortcode( $post_id, 'wpc_reservation_form' ) ) {
            return;
        }

        wp_enqueue_style( 'wpcafe-frontend-style' );
        wp_enqueue_script( 'wpcafe-frontend-scripts' );
    }

    /**
     * Enqueue scripts and styles
     *
     * @return  void
     */
    public function enqueue() {

        // Sibling plugins (e.g. the WCFM multivendor addon) lazy-load their own
        // wp-cafe-pro modules on pages wpcafe_should_load_frontend() doesn't
        // recognize as ours, but those modules still call into our shared
        // window.wpCafeI18nLoader for translations. That global only exists if
        // this runs, so — unlike the rest of this method — it can't wait behind
        // the page-type gate below.
        $this->enqueue_i18n_loader();

        // BE1/FE1/FE2: only load the (historically sitewide) card/grid CSS, the
        // jQuery public bundle and the forced WC scripts where WP Cafe actually
        // renders. Registration stays unconditional in register_styles_scripts(),
        // so sibling plugins that depend on these handles still resolve them.
        if ( ! wpcafe_should_load_frontend() ) {
            return;
        }


        wp_enqueue_style( 'wpc-public' );
        wp_enqueue_script( 'wpc-public' );
        wp_enqueue_script( 'wpc-popup' );
        wp_enqueue_style( 'wpc-icon' );
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'wpc-product-labels' );

        // Load card CSS in the head. The per-shortcode enqueues run inside
        // the_content (after </head>) so WP prints them in the footer, and the
        // cards reflow once that CSS applies (CLS). Dedupes with those.
        wp_enqueue_style( 'wpc-card-core' );
        wp_enqueue_style( 'wpc-popup' );
        wp_enqueue_style( 'wpc-pagination' );
        wp_enqueue_style( 'wpc-food-menu-tab' );

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
            'wpc_seat_capacity_nonce'           => wp_create_nonce('wpc_seat_capacity_nonce'),
            'wpc_food_menu_paginate_nonce'      => wp_create_nonce('wpc_food_menu_paginate_nonce'),
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
                // wpcafe-vendor-forms: bundle externalizes rhf/zod/resolvers to
                // the shared global, so the provider must print first.
                'deps'      => ['wp-i18n', 'wp-data','wp-api-fetch', 'wpcafe-vendor-forms'],
                'in_footer' => true,
            ],
            'wpcafe-restaurant-management-scripts' => [
                'src'       => wpcafe()->assets_url . '/build/js/restaurant-management.js',
                'deps'      => ['wp-i18n', 'wp-element', 'wp-api-fetch', 'wpcafe-vendor-forms'],
                'in_footer' => true,
            ],
            'wpc-flatpicker'     => [
                'src'       => wpcafe()->assets_url . '/js/flatpickr.min.js',
                'deps'      => ['jquery'],
                'in_footer' => true,
            ],
            'wpc-public'    => [
                'src'       => wpcafe()->assets_url . '/build/js/wpc-public.js',
                'deps'      => ['jquery'],
                'in_footer' => true,
            ],
            // Popup behaviour (FE2). Split out of wpc-public.js — opener +
            // add-to-cart for the customize/variation popup. Loaded alongside
            // wpc-public; carries the wpc_obj localization (Product_Popup_Service).
            'wpc-popup'     => [
                'src'       => wpcafe()->assets_url . '/build/js/wpc-popup.js', // Phase 10: bundled.
                'deps'      => ['jquery'],
                'in_footer' => true,
            ],
            'wpc-location-selector'    => [
                'src'       => wpcafe()->assets_url . '/build/js/location-selector.js',
                'deps'      => [],
                'in_footer' => true,
            ],
            'wpc-tip'    => [
                'src'       => wpcafe()->assets_url . '/build/js/tip.js', // Phase 10: bundled.
                'deps'      => ['jquery'],
                'in_footer' => true,
            ],
            'wpc-mini-cart' => [
                'src'       => wpcafe()->assets_url . '/build/js/mini-cart.js', // Phase 10: bundled.
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
            // Mini-cart styles (FE2). Split out of wpc-public.css and enqueued by
            // the mini-cart module wherever the mini-cart renders. Theme-compat
            // mini-cart overrides intentionally stay in wpc-public.css.
            'wpc-minicart' => [
                'src' => wpcafe()->assets_url . '/css/wpc-minicart.css',
            ],
            // Shared food product-card primitive (FE2). Registered here, enqueued
            // via wp_enqueue_style( 'wpc-card-core' ) (or widget get_style_depends)
            // only where a card renders, so it stays off non-card pages.
            'wpc-card-core' => [
                'src' => wpcafe()->assets_url . '/css/card-core.css',
            ],
            // Customize/variation popup inner styles (FE2). Split out of
            // wpc-public.css and enqueued next to wpc-card-core wherever a food
            // card can open the popup. The base overlay (incl. display:none)
            // stays in wpc-public.css since the modal shell prints sitewide.
            'wpc-popup' => [
                'src' => wpcafe()->assets_url . '/css/wpc-popup.css',
            ],
            // Food-menu tab navigation (FE2). Split out of wpc-public.css and
            // enqueued only by the [wpc_food_menu_tab] shortcode and the
            // matching Elementor widgets (those that render .wpc-food-tab-wrapper).
            // Cascade order relies on global enqueue — no explicit deps.
            'wpc-food-menu-tab' => [
                'src' => wpcafe()->assets_url . '/css/food-menu-tab.css',
            ],
            // Menu pagination nav (FE2). Split out of wpc-public.css and enqueued
            // next to wpc-card-core wherever a food-menu list renders.
            'wpc-pagination' => [
                'src' => wpcafe()->assets_url . '/css/wpc-pagination.css',
            ],
            // Newer card set (list style-4, tab style-6/7/8). Split per concern so
            // a page only downloads the card shape it renders; all three are
            // enqueued through wpc_food_menu_style_assets(). Depends on
            // wpc-card-core for the shared add-to-cart / loader chrome.
            'wpc-card-atoms' => [
                'src'  => wpcafe()->assets_url . '/css/card-atoms.css',
                'deps' => [ 'wpc-card-core' ],
            ],
            'wpc-card-row' => [
                'src'  => wpcafe()->assets_url . '/css/card-row.css',
                'deps' => [ 'wpc-card-atoms' ],
            ],
            'wpc-card-grid' => [
                'src'  => wpcafe()->assets_url . '/css/card-grid.css',
                'deps' => [ 'wpc-card-atoms' ],
            ],
            // Tab nav variants (pills-end / rail / pills-thumb). Kept out of
            // food-menu-tab.css so styles 1-5 do not download rules they never use.
            'wpc-tab-nav-v2' => [
                'src'  => wpcafe()->assets_url . '/css/tab-nav-v2.css',
                'deps' => [ 'wpc-food-menu-tab' ],
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
            'wpc-product-labels' => [
                'src' => wpcafe()->assets_url . '/build/css/product-labels.css',
            ],
        ];

        return apply_filters( 'wpcafe_frontend_styles', $styles );
    }
}
