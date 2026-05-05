<?php
namespace WpCafe;

use WpCafe\RestaurantManagement\Roles;

/**
 * Class Deactivation
 * 
 * @package WpCafe
 */
class Deactivate {
    /**
     * Trigger on plugin deactivation hook
     *
     * @return  void
     */
    public static function run(): void {
        Roles::deregister();
    }
}
