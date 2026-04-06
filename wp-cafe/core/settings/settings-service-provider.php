<?php
namespace WpCafe\Settings;

use WpCafe\Providers\Base_Service_Provider;
use WpCafe\Settings\Controllers\Settings_Controller;

/**
 * Settings_Service_Provider will responsible for all settings services
 *
 * @package WpCafe/Settings
 */

class Settings_Service_Provider extends Base_Service_Provider {
    /**
     * Store services
     *
     * @var array
     */
    protected $services = [
        Settings_Controller::class,
    ];

    /**
     * Register services
     *
     * @return  void
     */
    public function get_services() {
        return apply_filters( 'wpcafe_settings_services',  $this->services );
    }
}

