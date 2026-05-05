<?php
namespace WpCafe\Assets;

/**
 * Manage all localize data
 */
class Localize {

    /**
     * Get admin localize data
     *
     * @return  array Collection localize data
     */
    public static function get_admin() {
        $current_user = wp_get_current_user();

        $data = [
            'site_url'            => site_url(),
            'admin_url'           => admin_url(),
            'nonce'               => wp_create_nonce( 'wp_rest' ),
            'date_format'         => get_option( 'date_format' ),
            'date_format_string'  => date_i18n( get_option( 'date_format' ) ),
            'time_format'         => get_option( 'time_format' ),
            'time_format_string'  => date_i18n( get_option( 'time_format' ) ),
            'start_of_week'       => get_option( 'start_of_week', 0 ),
            'current_user_id'     => get_current_user_id(),
            'currency_list'       => wpc_get_currencies(),
            'wpcafePro'           => function_exists('wpcafe_pro'),
            'user_role'           => $current_user->roles,
            'pages'               => wpc_get_pages(),
            'table_layout'        => wpc_is_module_enable('table_layout'),
            'has_woo_products'    => (wp_count_posts( 'product' )->publish ?? 0) > 0,
        ];

        return apply_filters( 'wpcafe_admin_localize', $data );
    } 

    /**
     * Get frontend localize data
     *
     * @return  array Collection localize data
     */
    public static function get_frontend() {
        $data = [
            'site_url'            => site_url(),
            'admin_url'           => admin_url(),
            'nonce'               => wp_create_nonce( 'wp_rest' ),
            'date_format'         => get_option( 'date_format' ),
            'time_format'         => get_option( 'time_format' ),
            'current_user_id'     => get_current_user_id(),
            'currency_list'       => wpc_get_currencies(),
            'start_of_week'       => get_option( 'start_of_week', 0 ),
            'locale_name'         => strtolower( str_replace( '_', '-', get_locale() ) ),
            'table_layout'        => wpc_is_module_enable('table_layout'),
            'wpcafePro'           => function_exists('wpcafe_pro'),
            'wpcafeMultivendor'   => class_exists('\Wpcafe_Multivendor'),
        ];

        return apply_filters( 'wpcafe_frontend_localize', $data );
    }
}