<?php
namespace WpCafe\Contracts;
/**
 * Hookable Service Contract
 *
 * @package WpCafe/Contract
 */
interface Hookable_Service_Contract {
    /**
     * Register a single service
     *
     * @return  void
     */
    public function register();
}
