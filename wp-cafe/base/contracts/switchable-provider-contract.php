<?php
namespace WpCafe\Contracts;

/**
 * Switchable Provider Contract
 */
interface Switchable_Provider_Contract {
    /**
     * Ensure that service is enable or disable
     *
     * @return  bool
     */
    public function is_enable();
}
