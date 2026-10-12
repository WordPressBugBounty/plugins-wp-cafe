<?php
namespace WpCafe\Integrations\Pointics;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Keeps a Pointics points coupon on the cart when WP Cafe rebuilds it for a
 * booking payment (WC_Payment empties the cart, which drops coupons too).
 *
 * @package WpCafe/Integrations/Pointics
 */
class Booking_Payment_Coupon_Keeper implements Hookable_Service_Contract {
    const SESSION_KEY = 'wpc_pointics_coupon';

    /**
     * Register hooks.
     *
     * @return void
     */
    public function register() {
        add_action( 'woocommerce_applied_coupon', [ $this, 'remember' ] );
        add_action( 'woocommerce_removed_coupon', [ $this, 'forget_code' ] );
        add_action( 'wpcafe_reservation_cart_rebuilt', [ $this, 'reapply' ] );
        add_action( 'woocommerce_checkout_order_processed', [ $this, 'forget' ] );
        add_action( 'woocommerce_store_api_checkout_order_processed', [ $this, 'forget' ] );
    }

    /**
     * @param string $code Coupon code.
     * @return void
     */
    public function remember( $code ) {
        if ( ! WC()->session ) {
            return;
        }

        $coupon = new \WC_Coupon( $code );

        if ( $coupon->get_id() && $coupon->get_meta( \Pointics\Integrations\WooCommerce\WooCommerce_Coupon_Redeemer::META_KEY ) ) {
            WC()->session->set( self::SESSION_KEY, wc_format_coupon_code( $code ) );
        }
    }

    /**
     * @param string $code Coupon code.
     * @return void
     */
    public function forget_code( $code ) {
        if ( WC()->session && wc_format_coupon_code( $code ) === WC()->session->get( self::SESSION_KEY ) ) {
            $this->forget();
        }
    }

    /**
     * @param \WC_Cart $cart Rebuilt cart.
     * @return void
     */
    public function reapply( $cart ) {
        $code = WC()->session ? (string) WC()->session->get( self::SESSION_KEY ) : '';

        if ( '' === $code || $cart->has_discount( $code ) ) {
            return;
        }

        // The code-to-id lookup is cached, so a deleted coupon can still return
        // its old id, and applying it would throw "Invalid coupon".
        $coupon_id = wc_get_coupon_id_by_code( $code );

        if ( ! $coupon_id || 'shop_coupon' !== get_post_type( $coupon_id ) ) {
            $this->forget();
            return;
        }

        $cart->apply_coupon( $code );
    }

    /**
     * @return void
     */
    public function forget() {
        if ( WC()->session ) {
            WC()->session->set( self::SESSION_KEY, null );
        }
    }
}
