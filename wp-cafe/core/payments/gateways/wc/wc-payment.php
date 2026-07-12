<?php
namespace WpCafe\Payments\Gateways\WC;

use WpCafe\Payments\Abstract_Payment;
use WpCafe\Payments\Payment_Response;
use WpCafe\Models\Reservation_Model;
use WC_Product_Simple;
use WC_Cart;


class WC_Payment extends Abstract_Payment {
    /**
     * The settings for the payment.
     *
     * @var array
     */
    protected $settings;
    /**
     * Initiate the payment
     *
     * @param array $data The payment data.
     * @return Payment_Response The payment response.
     */
    public function initiate_payment( array $data ): Payment_Response {
        // Clear cart
        if ( ! function_exists( 'WC' ) ) {
            return new Payment_Response(
                'failed',
                '',
                'WooCommerce is not installed'
            );
        }

        $this->init_woocommerce();

        $cart = WC()->cart;
        $cart->empty_cart();

        $reservation = new Reservation_Model( $data['reservation_id'] );

        if ( 'yes' === $reservation->food_order ) {
            foreach ( $reservation->get_items() as $item ) {
                $product_id = (int) $item->product_id;
                $quantity   = max( 1, (int) $item->quantity );

                if ( $product_id > 0 ) {
                    $cart->add_to_cart( $product_id, $quantity );
                }
            }
        }

        /*
         * Always add the reservation line so the booking fee is charged. It
         * carries the `reservation_id` so modify_cart_item_price() prices it
         * from the booking (food, when present, is its own lines above). Before
         * this was gated on an empty cart, so a reservation-with-food cart
         * collected only the food and never the booking amount.
         */
        $product_id = $this->generate_generic_product();
        $this->disable_deposet_for_product( $product_id );

        $cart->add_to_cart( $product_id, 1, 0, [], [ 'reservation_id' => $data['reservation_id'] ] );

        // Redirect to checkout
        return new Payment_Response(
            'redirect',
            wc_get_checkout_url(),
            'Redirecting to checkout'
        );
    }

    /**
     * Handle the callback from the payment gateway
     *
     * @param array $data The payment data.
     * @return Payment_Response The payment response.
     */
    public function handle_callback( array $data ): Payment_Response {
        // WooCommerce handles payment confirmation internally
        return new Payment_Response('success', '', 'WooCommerce handles callbacks.');
    }

    /**
     * Get the generic WooCommerce product ID used for reservations.
     *
     * Retrieves the product ID from the plugin options. If the product does not exist
     * or needs to be regenerated, a new simple virtual product is created and its ID is stored.
     *
     * @return string The generic product ID.
     */
    public function get_generic_product_id() : string{
        return wpc_get_option('woocommerce_generic_product_id', '');
    }

    /**
     * Generate a generic WooCommerce product for reservations.
     *
     * Creates a new simple, virtual WooCommerce product named "Reservation" if one does not already exist
     * or if forced regeneration is requested. The product ID is stored in the plugin options for reuse.
     *
     * @param bool $force Whether to force regeneration of the product even if it exists. Default false.
     * @return string The ID of the generic reservation product.
     */
    public function generate_generic_product(bool $force = false) : string{
        $generic_product_id = $this->get_generic_product_id();

        if ( empty( $generic_product_id ) || $force ) {
            $product = new WC_Product_Simple();
            $product->set_name( 'Reservation' );
            $product->set_regular_price( 0 );
            $product->set_status( 'publish' );
            $product->set_virtual( true );
            $generic_product_id = $product->save();

            wpc_update_option( 'woocommerce_generic_product_id', $generic_product_id);

            // Mark it as no-deposit the moment it is created.
            $this->disable_deposet_for_product( $generic_product_id );
        }

        return $generic_product_id;
    }

    /**
     * Mark the reservation product so the Deposet plugin skips it.
     *
     * Deposet reads these per-product settings. We set them so the product does
     * not follow the site-wide deposit rule and has no deposit of its own. Calling
     * this more than once does no harm, so we also run it on older products that
     * were created before this opt-out was added.
     *
     * @param int|string $product_id Generic reservation product ID.
     * @return void
     */
    private function disable_deposet_for_product( $product_id ) : void {
        if ( empty( $product_id ) ) {
            return;
        }

        update_post_meta( $product_id, '_deposet_inherit', 'no' );
        update_post_meta( $product_id, '_deposet_enable', 'no' );
        update_post_meta( $product_id, '_deposet_force', 'no' );
    }

    /**
     * Init woocommerce functions
     *
     * @return  void
     */
    public function init_woocommerce() {
        if ( ! WC()->is_rest_api_request() ) {
            return;
        }

        WC()->frontend_includes();

        if ( null === WC()->cart && function_exists( 'wc_load_cart') ) {
            wc_load_cart();
        }
    }
}
