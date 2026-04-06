<?php
namespace WpCafe\Products;

use WpCafe\Providers\Base_Service_Provider;
use WpCafe\Products\Controllers\Product_Controller;
use WpCafe\Products\Controllers\Product_Category_Controller;
use WpCafe\Contracts\Switchable_Provider_Contract;

/**
 * Products_Service_Provider will responsible for all product services
 *
 * @package WpCafe/Products
 */

class Products_Service_Provider extends Base_Service_Provider implements Switchable_Provider_Contract {
    /**
     * Store services
     *
     * @var array
     */
    protected $services = [
        Product_Controller::class,
        Product_Category_Controller::class,
    ];

    /**
     * Register services
     *
     * @return  void
     */
    public function get_services() {
        return apply_filters( 'wpcafe_products_services',  $this->services );
    }

    /**
     * Ensure that is enable or disable
     *
     * @return  bool
     */
    public function is_enable() {
        return function_exists('WC');  
    }
} 
