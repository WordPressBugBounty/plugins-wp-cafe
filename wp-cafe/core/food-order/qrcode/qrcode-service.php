<?php
namespace WpCafe\FoodOrder\Qrcode;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Contracts\Switchable_Service_Contract;
use WpCafe\FoodOrder\Controllers\Qrcode_Controller;

/**
 * Main Tipping Service
 * 
 * Responsible only for registering related classes and toggling the feature.
 */
class Qrcode_Service implements Hookable_Service_Contract, Switchable_Service_Contract {

    /**
     * Register tipping-related classes.
     *
     * @return void
     */
    public function register() {
        $qrcode_controller = new Qrcode_Controller();
        $qrcode_controller->register();

        // Register table session handler for QR code functionality
        $table_session_handler = new Table_Session_Handler();
        $table_session_handler->register();
    }

    /**
     * Determine whether this service is enabled.
     *
     * @return bool
     */
    public function is_enable(): bool {
        return true;
    }
}
