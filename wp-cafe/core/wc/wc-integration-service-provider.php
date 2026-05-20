<?php
namespace WpCafe\Wc;

defined( 'ABSPATH' ) || exit;

use WpCafe\Contracts\Bootable_Provider_Contract;
use WpCafe\Wc\Blocks\Block_Service;

/**
 * WC Integration Service Provider
 *
 * Registers services that integrate WPCafe with WooCommerce's block-based
 * Cart & Checkout blocks.
 *
 * @package WpCafe/Wc
 */
class Wc_Integration_Service_Provider implements Bootable_Provider_Contract {
    /**
     * Services to bootstrap.
     *
     * @var array
     */
    protected $services = [
        Block_Service::class,
    ];

    /**
     * Boot services.
     *
     * @return void
     */
    public function boot() {
        foreach ( $this->services as $service ) {
            ( new $service() )->register();
        }
    }
}
