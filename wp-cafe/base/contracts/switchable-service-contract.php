<?php
namespace WpCafe\Contracts;

/**
 * Switchable Service Contract
 */
interface Switchable_Service_Contract {
    /**
     * Ensure that service is enable or disable
     *
     * @return  bool
     */
    public function is_enable();
}
