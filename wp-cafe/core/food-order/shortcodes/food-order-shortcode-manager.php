<?php
namespace WpCafe\FoodOrder\Shortcodes;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Contracts\Shortcode_Interface;

class Shortcode_Manager implements Hookable_Service_Contract {
    /**
     * Store shortcodes
     *
     * @var array
     */
    protected $shortcodes = [
        Food_Menu_Tab::class,
        Food_Menu_List::class,
        Food_Location_Menu::class,
        Food_Location_Filter::class,
    ];

    /**
     * Register Services
     *
     * @return  void
     */
    public function register() {
        new Food_Location_Ajax();

        // Composer's classmap autoloader is built at install time and won't
        // know about files added afterwards until `composer dump-autoload`
        // is run. Require directly so the popup service works on a fresh
        // checkout without that build step.
        require_once __DIR__ . '/product-popup-service.php';
        ( new Product_Popup_Service() )->register();

        $shortcodes = apply_filters( 'wpcafe_food_order_shortcodes', $this->shortcodes );

        foreach ( $shortcodes as $shortcode ) {
            ( new $shortcode() )->register();
        }
    }
}
