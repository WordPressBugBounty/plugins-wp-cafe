<?php
namespace WpCafe\FoodOrder\Qrcode;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * QR Fulfillment Gate
 *
 * Hides the Pickup/Delivery order types for visitors that arrived by scanning a
 * table QR code, when the "Show Pickup & Delivery to QR customers" toggle is off.
 *
 * The decision is centralised in wpc_qr_should_show_fulfillment(); this class only
 * wires that decision into every surface that renders the order-type options:
 *
 *   1. Free mini-cart radios   — wpcafe_minicart_show_pickup / _delivery
 *   2. Pro classic checkout    — wpcafe_pro_pickup_enabled / _delivery_enabled
 *   3. Pro WooCommerce blocks   — wpcafe_pro_blocks_pickup_enabled / _delivery_enabled
 *
 * Because the mini-cart HTML is cached client-side in localStorage.wc_fragments_*,
 * the gate also contributes a suffix to the WC fragment/hash cache key so a QR
 * session invalidates a previously cached mini-cart instead of serving stale radios.
 *
 * @see \WpCafe\FoodOrder\Qrcode\Qr_Fulfillment_Gate::filter_cart_fragment_name()
 * @see core/food-order/mini-cart/CLAUDE.md (fragment cache invariants)
 */
class Qr_Fulfillment_Gate implements Hookable_Service_Contract {

    /**
     * Register the gate filters.
     *
     * @return void
     */
    public function register() {
        // Surface 1 — free mini-cart radios.
        add_filter( 'wpcafe_minicart_show_pickup', [ $this, 'gate' ] );
        add_filter( 'wpcafe_minicart_show_delivery', [ $this, 'gate' ] );

        // Surface 2 — pro classic checkout (no-op when pro is inactive).
        add_filter( 'wpcafe_pro_pickup_enabled', [ $this, 'gate' ] );
        add_filter( 'wpcafe_pro_delivery_enabled', [ $this, 'gate' ] );

        // Surface 3 — pro WooCommerce blocks checkout.
        add_filter( 'wpcafe_pro_blocks_pickup_enabled', [ $this, 'gate' ] );
        add_filter( 'wpcafe_pro_blocks_delivery_enabled', [ $this, 'gate' ] );

        // Bust the cached mini-cart fragment when the gate state changes.
        add_filter( 'woocommerce_cart_fragment_name', [ $this, 'filter_cart_fragment_name' ] );
        add_filter( 'woocommerce_cart_hash_key', [ $this, 'filter_cart_fragment_name' ] );
    }

    /**
     * AND the incoming enabled flag with the QR fulfillment decision.
     *
     * False wins — once any layer hides the option it stays hidden.
     *
     * @param  mixed $enabled Current visibility/enabled flag.
     * @return bool
     */
    public function gate( $enabled ): bool {
        return (bool) $enabled && wpc_qr_should_show_fulfillment();
    }

    /**
     * Append the gate state to the mini-cart fragment cache key.
     *
     * @param  string $name Existing fragment name / hash key.
     * @return string
     */
    public function filter_cart_fragment_name( $name ): string {
        $suffix = wpc_qr_should_show_fulfillment() ? '_qrshow1' : '_qrshow0';

        return (string) $name . $suffix;
    }
}
