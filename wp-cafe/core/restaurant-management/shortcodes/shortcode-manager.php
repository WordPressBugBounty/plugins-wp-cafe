<?php
namespace WpCafe\RestaurantManagement\Shortcodes;

use WpCafe\Contracts\Hookable_Service_Contract;

class Shortcode_Manager implements Hookable_Service_Contract {
    /**
     * Store shortcodes
     *
     * @var array
     */
    protected $shortcodes = [
        Restaurant_Management_Dashboard::class,
    ];

    /**
     * Register Services
     *
     * @return void
     */
    public function register() {
        $shortcodes = apply_filters( 'wpcafe_restaurant_management_shortcodes', $this->shortcodes );

        foreach ( $shortcodes as $shortcode ) {
            ( new $shortcode() )->register();
        }
    }
}
