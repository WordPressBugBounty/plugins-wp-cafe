<?php
namespace WpCafe\Payments;
/**
 * Payment factory
 *
 * Creates instances of payment methods based on the gateway name.
 *
 * @package WpCafe/Payments
 */
class Payment_Factory {
    /**
     * Create a new payment method instance.
     *
     * @param string $gateway The gateway name.
     * @param array $settings The settings for the payment method.
     * @return Payment_Interface The payment method instance.
     */
    public static function create( string $gateway, array $settings = [] ): Payment_Interface {
        /**
         * Filter the payment gateways
         *
         * @param array $payment_gateways The payment gateways.
         */
        $payment_gateways = [];

        /**
         * Filter the payment gateways
         *
         * @param array $payment_gateways The payment gateways.
         */
        $payment_gateways = apply_filters( 'wpcafe_payment_gateways', $payment_gateways );

        /**
         * Throw an exception if the payment gateway is not supported
         *
         * @param string $gateway The gateway name.
         */
        if ( ! isset( $payment_gateways[ $gateway ] ) ) {
            /* translators: %s: payment gateway name */
            throw new \Exception( esc_html( sprintf( __( 'Unsupported payment gateway: %s', 'wp-cafe' ), $gateway ) ) );
        }

        /**
         * Return the payment gateway instance
         *
         * @param string $gateway The gateway name.
         * @return Payment_Interface The payment gateway instance.
         */
        return new $payment_gateways[ $gateway ]();
    }
}
