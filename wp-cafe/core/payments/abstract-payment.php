<?php
namespace WpCafe\Payments;
/**
 * Abstract payment
 *
 * Abstract class for payment.
 *
 * @package WpCafe/Payments
 */
abstract class Abstract_Payment implements Payment_Interface {
    /**
     * The settings for the payment method.
     *
     * @var array
     */
    protected $settings;

    /**
     * Constructor for abstract payment
     *
     * @param array $settings The settings for the payment method.
     */
    public function __construct( array $settings = [] ) {
        $this->settings = $settings;
    }
}
