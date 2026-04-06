<?php
namespace WpCafe\Widgets;

use WpCafe\Providers\Base_Service_Provider;
use WpCafe\Widgets\Manifest;

/**
 * Elementor_Widgets_Service_Provider will responsible for all elementor widget services
 *
 * @package WpCafe/Widgets
 */
class Elementor_Widgets_Service_Provider extends Base_Service_Provider {
    /**
     * Store services
     *
     * @var array
     */
    protected $services = [
        Manifest::class,
    ];

    /**
     * Register services
     *
     * @return  void
     */
    public function get_services() {
        return apply_filters( 'wpcafe_elementor_widgets_services', $this->services );
    }
}
