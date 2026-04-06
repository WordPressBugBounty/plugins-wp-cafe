<?php
namespace WpCafe\Payments;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Payments\Gateways\WC\WC_Payment;

/**
 * Payment Setup
 *
 * @package WpCafe/Payments
 */
class Payment_Setup implements Hookable_Service_Contract {
    /**
     * Register hooks
     *
     * @return  void
     */
    public function register() {
        add_filter( 'wpcafe_payment_gateways', [ $this, 'add_payment_gateways' ] );
    }

    /**
     * Add payment gateways
     *
     * @param array $payment_gateways The payment gateways.
     * @return array The payment gateways.
     */
    public function add_payment_gateways($payment_gateways) {
        /**
         * Add new payment gateways
         *
         * @param array $payment_gateways The payment gateways.
         * @return array The payment gateways.
         */
        $new_payment_gateways = [
            'wc' => WC_Payment::class,
        ];

        return array_merge( $payment_gateways, $new_payment_gateways );
    }
}
