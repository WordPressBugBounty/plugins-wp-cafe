<?php
namespace WpCafe\Payments;

/**
 * Payment processor
 *
 * Handles the payment process.
 *
 * @package WpCafe/Payments
 */
class Payment_Processor {
    /**
     * The payment gateway.
     *
     * @var Payment_Interface
     */
    private Payment_Interface $gateway;

    /**
     * Constructor for PaymentProcessor class
     *
     * @param string $gateway The gateway name.
     * @param array $settings The settings for the payment method.
     */
    public function __construct( string $gateway, array $settings = [] ) {
        $this->gateway = Payment_Factory::create($gateway, $settings);
    }

    /**
     * Pay the payment
     *
     * @param array $data The payment data.
     * @return Payment_Response The payment response.
     */
    public function process_payment( array $data ): Payment_Response {
        return $this->gateway->initiate_payment($data);
    }

    /**
     * Handle the callback from the payment gateway
     *
     * @param array $data The payment data.
     * @return Payment_Response The payment response.
     */
    public function callback( array $data ): Payment_Response {
        return $this->gateway->handle_callback($data);
    }
}
