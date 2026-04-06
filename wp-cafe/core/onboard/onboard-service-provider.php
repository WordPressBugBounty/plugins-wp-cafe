<?php

namespace WpCafe\Onboard;

use WpCafe\Onboard\Controllers\Onboard_Controller;
use WpCafe\Providers\Base_Service_Provider;

/**
 * Onboard_Service_Provider will responsible for all appointment services
 *
 * @package WpCafe/Onboard
 */
class Onboard_Service_Provider extends Base_Service_Provider {
    /**
     * Store services
     *
     * @var array
     */
    protected $services = [
        Onboard_Controller::class,
        Onboard_Setup::class,
    ];

    /**
     * Register services
     *
     * @return  void
     */
    public function get_services() {
        return apply_filters( 'wpcafe_onboard_services', $this->services );
    }
}

