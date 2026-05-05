<?php
namespace WpCafe\RestaurantManagement;

use WpCafe\Providers\Base_Service_Provider;
use WpCafe\RestaurantManagement\Shortcodes\Shortcode_Manager;

/**
 * Restaurant Management Service Provider.
 *
 * Frontend staff panel rendered via shortcode + React (Shadow DOM).
 */
class Restaurant_Management_Service_Provider extends Base_Service_Provider {
    /**
     * Store services
     *
     * @var array
     */
    protected $services = [
        Shortcode_Manager::class,
    ];

    /**
     * Register services
     *
     * @return array
     */
    public function get_services() {
        return apply_filters( 'wpcafe_restaurant_management_services', $this->services );
    }

    /**
     * Boot role registration and module services.
     *
     * @return void
     */
    public function boot() {
        add_action( 'init', [ Roles::class, 'register' ], 19 );
        parent::boot();
    }
}
