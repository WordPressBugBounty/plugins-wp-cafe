<?php
namespace WpCafe\Upgrades;

/**
 * Interface Upgrade_Interface
 *
 * Defines the contract for upgrade classes in WpCafe.
 * Any class implementing this interface must provide an implementation
 * for the upgrade() method, which should handle the upgrade logic.
 */
interface Upgrade_Interface {
    /**
     * Upgrade the plugin.
     *
     * @return void
     */
    public function upgrade();
}
