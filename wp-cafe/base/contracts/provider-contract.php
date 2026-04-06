<?php
namespace WpCafe\Contracts;
/**
 * Provider contracts
 *
 * @package WpCafe/Contracts
 */

interface Provider_Contract {
    /**
     * Register provider services
     *
     * @return  void
     */
    public function register();

    /**
     * Boot services that need to initialize
     *
     * @return  void
     */
    public function boot();
}
