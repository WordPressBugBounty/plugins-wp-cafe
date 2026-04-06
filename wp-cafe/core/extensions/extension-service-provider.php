<?php
namespace WpCafe\Extensions;

use WpCafe\Extensions\Controllers\Extension_Controller;
use WpCafe\Extensions\Controllers\Plugin_Controller;
use WpCafe\Extensions\Controllers\Integration_Controller;
use WpCafe\Providers\Base_Service_Provider;

/**
 * Extension_Service_Provider will responsible for all appointment services
 *
 * @package WpCafe/Addon
 */
class Extension_Service_Provider extends Base_Service_Provider {
    /**
     * Store services
     *
     * @var array
     */
    protected $services = [
        Extension_Controller::class,
        Plugin_Controller::class,
        Integration_Controller::class,
    ];

    /**
     * Register services
     *
     * @return  void
     */
    public function get_services() {
        return apply_filters( 'wpcafe_addon_services', $this->services );
    }
}
