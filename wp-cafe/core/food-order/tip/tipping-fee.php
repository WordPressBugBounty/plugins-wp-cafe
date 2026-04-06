<?php
namespace WpCafe\FoodOrder\Tip;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Applies tip as a WooCommerce cart fee.
 */
class Tipping_Fee {

    /**
     * Register WooCommerce fee calculation hook.
     *
     * @return void
     */
    public function __construct() {
        add_action( 'woocommerce_cart_calculate_fees', [ $this, 'apply_fee' ] );
    }

    /**
     * Apply tip to WooCommerce cart.
     *
     * @return void
     */
    public function apply_fee() {
        $tip = WC()->session->get( 'wpc_pro_tip' );

        if ( $tip && $tip['tip_added'] ) {
            $title  = esc_html__( 'Tip', 'wp-cafe' );
            $amount = 0;

            if ( $tip['tip_selected_type'] === 'fixed_amount' ) {
                $amount = $tip['tip_amount'];
            } elseif ( $tip['tip_selected_type'] === 'percentage_amount' ) {
                $title .= ' (' . $tip['tip_amount'] . '%)';
                $amount = ( $tip['tip_amount'] / 100 ) * WC()->cart->get_subtotal();
            } elseif ( $tip['tip_selected_type'] === 'custom' ) {
                $amount = $tip['tip_amount'];
            }

            if ( $amount > 0 ) {
                WC()->cart->add_fee( $title, $amount );
            }
        }
    }
}
