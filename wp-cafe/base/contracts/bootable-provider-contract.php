<?php
namespace WpCafe\Contracts;
/**
 * Bootable Provider Contracts
 *
 * @package WpCafe/Contracts
 */

interface Bootable_Provider_Contract {
    /**
     * Boot services that need to initialize
     *
     * @return  void
     */
    public function boot();
}
