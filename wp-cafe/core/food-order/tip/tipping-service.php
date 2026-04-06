<?php
namespace WpCafe\FoodOrder\Tip;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Contracts\Switchable_Service_Contract;

/**
 * Main Tipping Service
 * 
 * Responsible only for registering related classes and toggling the feature.
 */
class Tipping_Service implements Hookable_Service_Contract, Switchable_Service_Contract {

    /**
     * Register tipping-related classes.
     *
     * @return void
     */
    public function register() {
        new Tipping_Form();
        new Tipping_Ajax();
        new Tipping_Fee();
        new Tipping_Cleanup();
        new Tipping_Assets();
    }

    /**
     * Determine whether this service is enabled.
     *
     * @return bool
     */
    public function is_enable(): bool {
        return wpc_is_module_enable('tipping');
    }
}
