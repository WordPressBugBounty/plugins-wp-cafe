<?php
namespace WpCafe\Payments\Gateways\WC;

use WpCafe\Payments\Abstract_Payment;   
use WpCafe\Payments\Payment_Response;
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

        $cart   = WC()->cart;
        $cart->empty_cart();
        $product_id = $this->generate_generic_product();

        $item_data = [
            'reservation_id' => $data['reservation_id'],
        ];

        // Add reservation product with meta
        WC()->cart->add_to_cart( $product_id, 1, 0, [], $item_data );

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
        }
        
        return $generic_product_id;
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
