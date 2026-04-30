<?php
namespace WpCafe\FoodOrder\Mini_Cart;

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- plugin-wpc-prefix, public backward-compat hooks, or third-party (Elementor) hook names.

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Contracts\Switchable_Service_Contract;
use WpCafe\FoodOrder\Mini_Cart\Mini_Cart;

/**
 * Mini Cart Service
 *
 * @since 1.0.0
 */
class Mini_Cart_Service implements Hookable_Service_Contract, Switchable_Service_Contract {

    /**
     * Register Services
     *
     * @return  void
     */
    public function register() {
        new Mini_Cart();

        do_action('wpc_mini_cart_register_services');
    }

    /**
     * Check if mini cart is enabled
     *
     * @return  bool
     */
    public function is_enable() {
        return wpc_is_module_enable('mini_cart');
    }
}
