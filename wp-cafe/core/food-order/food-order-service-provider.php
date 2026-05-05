<?php

namespace WpCafe\FoodOrder;

use WpCafe\Contracts\Switchable_Provider_Contract;
use WpCafe\FoodOrder\Shortcodes\Shortcode_Manager;
use WpCafe\FoodOrder\Mini_Cart\Mini_Cart_Service;
use WpCafe\FoodOrder\Controllers\Food_Order_Controller;
use WpCafe\Providers\Base_Service_Provider;
use WpCafe\FoodOrder\Tip\Tipping_Service;
use WpCafe\FoodOrder\Qrcode\Qrcode_Service;
use WpCafe\FoodOrder\LiveOrder\Liver_Order_Service;

/**
 * Food_Order_Service_Provider will responsible for all appointment services
 *
 * @package WpCafe/FoodOrder
 */
class Food_Order_Service_Provider extends Base_Service_Provider implements Switchable_Provider_Contract {
    /**
     * Store services
     *
     * @var array
     */
    protected $services = [
        Shortcode_Manager::class,
        Mini_Cart_Service::class,
        Tipping_Service::class,
        Qrcode_Service::class,
        Liver_Order_Service::class,
        Food_Order_Controller::class,
    ];

    /**
     * Register services
     *
     * @return  void
     */
    public function get_services() {
        return apply_filters( 'wpcafe_food_order_services', $this->services );
    }

    /**
     * Enable or disable this provider
     *
     * @return  bool
     */
    public function is_enable() {
        return wpc_is_module_enable('food_ordering') && function_exists('WC');
    }
}

