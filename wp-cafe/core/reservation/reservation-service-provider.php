<?php
namespace WpCafe\Reservation;

use WpCafe\Providers\Base_Service_Provider;
use WpCafe\Reservation\Controllers\Reservation_Controller;
use WpCafe\Reservation\Email\Handlers\Reservation_Email_Handler;
use WpCafe\Reservation\Shortcodes\Shortcode_Manager;
use WpCafe\Contracts\Switchable_Provider_Contract;

/**
 * Reservation_Service_Provider will responsible for all reservation services
 *
 * @package WpCafe/Reservation
 */

class Reservation_Service_Provider extends Base_Service_Provider implements Switchable_Provider_Contract {
    /**
     * Store services
     *
     * @var array
     */
    protected $services = [
        Reservation_Controller::class,
        Reservation_Hooks::class,
        Reservation_Email_Handler::class,
        Shortcode_Manager::class,
    ];

    /**
     * Register services
     *
     * @return  void
     */
    public function get_services() {
        return apply_filters( 'wpcafe_reservation_services',  $this->services );
    }

    /**
     * Ensure that is enable or disable
     *
     * @return  bool
     */
    public function is_enable() {
        return wpc_is_module_enable( 'reservation' );
    }
}

