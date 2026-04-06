<?php
namespace WpCafe\FoodOrder\Tip;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Responsible for rendering the tip form in WooCommerce checkout/cart.
 */
class Tipping_Form {

    /**
     * Register form rendering hooks.
     *
     * @return void
     */
    public function __construct() {
        add_action( 'woocommerce_after_order_notes', [ $this, 'render' ] );
        add_action( 'woocommerce_before_cart_totals', [ $this, 'render' ] );
    }

    /**
     * Include the tip form view.
     *
     * @return void
     */
    public function render() {
        include wpcafe()->template_directory . "/tip/tip-view.php";
    }
}
