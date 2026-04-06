<?php
namespace WpCafe\FoodOrder\LiveOrder;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Contracts\Switchable_Service_Contract;

/**
 * Main Live Order Service
 * 
 * Responsible for managing live order notifications with proper separation of concerns.
 */
class Liver_Order_Service implements Hookable_Service_Contract, Switchable_Service_Contract {
    /**
     * Register live order notification classes.
     *
     * @return void
     */
    public function register() {
        new Notifier();
        new Assets_Manager();    }

    /**
     * Determine whether this service is enabled.
     *
     * @return bool
     */
    public function is_enable(): bool {
        return true;
    }
}
