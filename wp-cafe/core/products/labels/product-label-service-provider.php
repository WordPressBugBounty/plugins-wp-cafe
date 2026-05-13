<?php
namespace WpCafe\Products\Labels;

use WpCafe\Providers\Base_Service_Provider;
use WpCafe\Products\Labels\Controllers\Product_Label_Controller;
use WpCafe\Contracts\Switchable_Provider_Contract;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Service provider that wires the WP Cafe Product Label module.
 *
 * @package WpCafe/Products/Labels
 */
class Product_Label_Service_Provider extends Base_Service_Provider implements Switchable_Provider_Contract {

    /**
     * Services managed by this provider.
     *
     * @var array
     */
    protected $services = [
        Product_Label_Taxonomy::class,
        Product_Label_Admin::class,
        Product_Label_Controller::class,
    ];

    public function get_services() {
        return apply_filters( 'wpcafe_product_label_services', $this->services );
    }

    /**
     * Module is active whenever WooCommerce is loaded.
     *
     * @return bool
     */
    public function is_enable() {
        return function_exists( 'WC' );
    }
}
