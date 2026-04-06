<?php
namespace WpCafe\Admin;

use WpCafe\Admin\Migration_Runner;
use WpCafe\Admin\Controllers\Migration_Controller;
use WpCafe\Upgrades\Install_Detector;
use WpCafe\Providers\Base_Service_Provider;

/**
 * Admin_Service_Provider will responsible for all admin services
 *
 * @package WpCafe/Admin
 */

class Admin_Service_Provider extends Base_Service_Provider {
    /**
     * Store services
     *
     * @var array
     */
    protected $services = [
        Menu::class,
        Default_Settings::class,
        Post_Status::class,
        WooCommerce_Admin::class,
        Install_Detector::class,
        Migration_Runner::class,
        Migration_Controller::class,
    ];

    /**
     * Register services
     *
     * @return  void
     */
    public function get_services() {
        return apply_filters( 'wpcafe_admin_services',  $this->services );
    }
}