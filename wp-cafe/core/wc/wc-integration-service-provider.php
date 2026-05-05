<?php
namespace WpCafe\Wc;

defined( 'ABSPATH' ) || exit;

use WpCafe\Providers\Base_Service_Provider;
use WpCafe\Wc\Blocks\Block_Service;

/**
 * WC Integration Service Provider
 *
 * Registers services that integrate WPCafe with WooCommerce's block-based
 * Cart & Checkout blocks.
 *
 * @package WpCafe/Wc
 */
class Wc_Integration_Service_Provider extends Base_Service_Provider {
    /**
     * Get services
     *
     * @return array
     */
    public function get_services() {
        return [
            Block_Service::class,
        ];
    }
}
