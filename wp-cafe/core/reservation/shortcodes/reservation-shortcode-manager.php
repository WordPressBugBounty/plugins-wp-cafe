<?php
namespace WpCafe\Reservation\Shortcodes;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Contracts\Shortcode_Interface;

class Shortcode_Manager implements Hookable_Service_Contract {
    /**
     * Store shortcodes
     *
     * @var array
     */
    protected $shortcodes = [
        Reservation_Form::class,
    ];

    /**
     * Register Services
     *
     * @return  void
     */
    public function register() {
        $shortcodes = apply_filters( 'wpcafe_reservation_shortcodes', $this->shortcodes );

        foreach ( $shortcodes as $shortcode ) {
            ( new $shortcode() )->register();
        }
    }
}
