<?php
namespace WpCafe\Assets;

use WpCafe\Providers\Base_Service_Provider;

/**
 * AssetsServiceProvider will responsible for all assets services
 *
 * @package WpCafe/Assets
 */

class Assets_Service_Provider extends Base_Service_Provider {

    /**
     * Store services
     *
     * @var array
     */
    protected $services = [
        Admin_Assets::class,
        Frontend_Assets::class,
        Common_Assets::class,
        Feedback_Assets::class
    ];

    /**
     * Register services
     *
     * @return  array
     */
    public function get_services() {
        return apply_filters( 'wpcafe_assets_services', $this->services );
    }
}
