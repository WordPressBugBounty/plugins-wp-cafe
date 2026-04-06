<?php
namespace WpCafe\FoodOrder\Tip;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Clears tip data after order placement.
 */
class Tipping_Cleanup {

    /**
     * Register WooCommerce order complete hook.
     *
     * @return void
     */
    public function __construct() {
        add_action( 'woocommerce_new_order', [ $this, 'clear_tip' ] );
    }

    /**
     * Remove tip session after order is placed.
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public function clear_tip( $order_id ) {
        if ( ! is_admin() ) {
            WC()->session->__unset( 'wpc_pro_tip' );
        }
    }
}
