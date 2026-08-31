<?php
namespace WpCafe\FoodOrder\Digital;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Digital Fulfillment Gate
 *
 * Hides Pickup/Delivery/Dine-in when the cart holds only digital items. Nothing
 * in such a cart is prepared or handed over, so the order-type controls, their
 * date/time slots and the delivery fee have nothing to act on.
 *
 * The decision is centralised in wpc_cart_is_digital_only(); this class only
 * wires it into every surface that renders the order-type options:
 *
 *   1. Free mini-cart radios   — wpcafe_minicart_show_pickup / _delivery / _dine_in
 *   2. Pro classic checkout    — wpcafe_pro_pickup_enabled / _delivery_enabled
 *   3. Pro WooCommerce blocks  — wpcafe_pro_blocks_pickup_enabled / _delivery_enabled
 *
 * Unlike Qr_Fulfillment_Gate this adds no mini-cart fragment cache suffix: the
 * decision is derived from cart contents, which the WC cart hash already covers.
 *
 * @see \WpCafe\FoodOrder\Qrcode\Qr_Fulfillment_Gate The same pattern for QR sessions.
 */
class Digital_Fulfillment_Gate implements Hookable_Service_Contract {

    /**
     * Register the gate filters.
     *
     * @return void
     */
    public function register() {
        // Surface 1 — free mini-cart radios.
        add_filter( 'wpcafe_minicart_show_pickup', [ $this, 'gate' ] );
        add_filter( 'wpcafe_minicart_show_delivery', [ $this, 'gate' ] );
        add_filter( 'wpcafe_minicart_show_dine_in', [ $this, 'gate' ] );

        // Surface 2 — pro classic checkout (no-op when pro is inactive).
        add_filter( 'wpcafe_pro_pickup_enabled', [ $this, 'gate' ] );
        add_filter( 'wpcafe_pro_delivery_enabled', [ $this, 'gate' ] );

        // Surface 3 — pro WooCommerce blocks checkout.
        add_filter( 'wpcafe_pro_blocks_pickup_enabled', [ $this, 'gate' ] );
        add_filter( 'wpcafe_pro_blocks_delivery_enabled', [ $this, 'gate' ] );
    }

    /**
     * AND the incoming enabled flag with the digital-cart decision.
     *
     * False wins — once any layer hides the option it stays hidden.
     *
     * @param  mixed $enabled Current visibility/enabled flag.
     * @return bool
     */
    public function gate( $enabled ): bool {
        return (bool) $enabled && ! wpc_cart_is_digital_only();
    }
}
