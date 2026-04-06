<?php
namespace WpCafe\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Add default settings
 *
 * @package WpCafe/Default settings
 */
class Default_Settings implements Hookable_Service_Contract {
    /**
     * Register hooks
     *
     * @return  void
     */
    public function register() {
        add_filter( 'wpcafe_settings', [ $this, 'add_default_seetings' ] );
    }

    /**
     * Add default settings
     *
     * @param   array  $settings  Existing settings
     *
     * @return  array
     */
    public function add_default_seetings( $settings ) {
        $default = [
            'wc_status' => wpc_get_plugin_status('woocommerce'),
            'currency' => function_exists('WC') ? get_woocommerce_currency() : wpc_get_option('currency'),
            'currency_symbol_position' => get_option('woocommerce_currency_pos', 'left'),
            'currency_price_separator' => get_option('woocommerce_price_thousand_sep', ','),
            'currency_decimals'        => get_option('woocommerce_price_num_decimals', 2),
            'restaurant_schedule'      => wpc_get_schedule(),
            'reservation_total_seat_capacity' => wpc_get_reservation_capacity(),
            'display_location_selector' => wpc_get_option('display_location_selector', 'dont_show'),
        ];

        return array_merge( $settings, $default );
    }
}

