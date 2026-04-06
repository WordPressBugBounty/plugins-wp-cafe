<?php
namespace WpCafe\Payments;

/**
 * Payment interface
 *
 * Defines the contract for payment methods.
 *
 * @package WpCafe/Payments
 */
interface Payment_Interface {
    /**
     * Initiates a payment process with the provided data.
     *
     * @param array $data The payment data required to initiate the payment.
     * @return Payment_Response The response object containing payment initiation result.
     */
    public function initiate_payment( array $data ): Payment_Response;

    /**
     * Handles the callback/response from the payment gateway.
     *
     * @param array $data The callback data received from the payment gateway.
     * @return Payment_Response The response object containing the result of the callback handling.
     */
    public function handle_callback( array $data ): Payment_Response;
}

