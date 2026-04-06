<?php
/**
 * Dashboard Service Provider
 *
 * Registers dashboard-related reports with the plugin container.
 *
 * @package WpCafe\Dashboard
 * @since 1.0.0
 */

namespace WpCafe\Dashboard;

use WpCafe\Providers\Base_Service_Provider;
use WpCafe\Dashboard\Controllers\Dashboard_Controller;
use WpCafe\Contracts\Switchable_Provider_Contract;

/**
 * Dashboard Service Provider Class
 *
 * Responsible for registering all dashboard-related reports.
 *
 * @package WpCafe\Dashboard
 * @since 1.0.0
 */
class Dashboard_Service_Provider extends Base_Service_Provider implements Switchable_Provider_Contract {

    /**
     * Store reports.
     *
     * @var array
     */
    protected $reports = array(
        Dashboard_Controller::class,
    );

    /**
     * Get registered reports.
     *
     * @since 1.0.0
     * @return array Array of report classes.
     */
    public function get_services() {
        return apply_filters( 'wpcafe_dashboard_reports', $this->reports );
    }

    /**
     * Check if the service provider is enabled.
     *
     * @since 1.0.0
     * @return bool True if enabled, false otherwise.
     */
    public function is_enable() {
        return true;
    }
} 
