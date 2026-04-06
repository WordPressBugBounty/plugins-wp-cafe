<?php

namespace WpCafe\Location;

use WpCafe\Location\Controllers\Location_Controller;
use WpCafe\Providers\Base_Service_Provider;
use WpCafe\Contracts\Switchable_Provider_Contract;

/**
 * Location Service will responsible for all location
 *
 * @package WpCafe/Location
 */
class Location_Service_Provider extends Base_Service_Provider implements Switchable_Provider_Contract {
    /**
     * Store services
     *
     * @var array
     */
    protected $services = [
        Location_Controller::class,
        Location_Taxonomy::class,
        Location_Selector::class,
        Product_Filter::class,
        Order_Filter::class
    ];

    /**
     * Register services
     *
     * @return  void
     */
    public function get_services() {
        return apply_filters( 'wpcafe_location_services', $this->services );
    }

    /**
     * Check if the service is enabled
     *
     * @return  bool
     */
    public function is_enable() {
        return wpc_is_module_enable( 'location' );
    }
}
