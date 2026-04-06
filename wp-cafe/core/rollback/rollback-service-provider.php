<?php
namespace WpCafe\Rollback;

use WpCafe\Onboard\Controllers\Version_Controller;
use WpCafe\Providers\Base_Service_Provider;

/**
 * Rollback_Service_Provider will responsible for all rollback services
 *
 * @package WpCafe/Rollback
 */

class Rollback_Service_Provider extends Base_Service_Provider {
    /**
     * Store services
     *
     * @var array
     */
    protected $services = [
        Version_Controller::class,
        Rollback_Service::class,
    ];

    /**
     * Register services
     *
     * @return  void
     */
    public function get_services() {
        return apply_filters( 'wpcafe_rollback_services',  $this->services );
    }
}
