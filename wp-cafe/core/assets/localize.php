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
            'deposet'             => wpc_is_deposet_active(),
            'aisentic'            => self::get_aisentic_state(),
        ];

        return apply_filters( 'wpcafe_admin_localize', $data );
    }

    /**
     * Aisentic connect state for the onboarding consent box and dashboard banner.
     *
     * Both surfaces show the email before the user opts in, so the value comes
     * from the same resolver the connect request uses.
     *
     * @return array Aisentic identity plus active/registered flags.
     */
    private static function get_aisentic_state() {
        $identity = wpc_aisentic_identity();

        return [
            'name'       => $identity['name'],
            'email'      => $identity['email'],
            'active'     => class_exists( 'Aisentic\Init' ),
            'registered' => wpc_aisentic_is_registered(),
            /*
             * Aisentic builds before the registration handshake have no
             * listener for our hook. Connecting would fail silently there, so
             * the UI hides the offer instead of promising something dead.
             */
            'supports_registration' => class_exists( 'Aisentic\Api\Services\Registration_Service' ),
            'terms_url'  => 'https://themewinter.com/terms-of-service/',
        ];
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
            'deposet'             => wpc_is_deposet_active(),
        ];

        return apply_filters( 'wpcafe_frontend_localize', $data );
    }
}