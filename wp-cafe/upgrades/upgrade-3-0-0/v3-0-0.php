<?php
namespace WpCafe\Upgrades\Upgrade_3_0_0;

use WpCafe\Upgrades\Upgrade_Interface;
use WpCafe\Upgrades\Upgrade_3_0_0\Upgrade_Reservation;
use WpCafe\Upgrades\Upgrade_3_0_0\Upgrade_Table_Layout;
use WpCafe\Upgrades\Upgrade_3_0_0\Upgrade_Pickup_Delivery;

/**
 * Class V3_0_0
 *
 * Implements the upgrade logic for version 3.0.0.
 */
class V3_0_0 implements Upgrade_Interface {
    /**
     * Upgrade the plugin.
     *
     * @return void
     */
    public function upgrade() {
        new Upgrade_Module();
        new Upgrade_Integration();
        new Upgrade_Reservation();
        new Upgrade_Pickup_Delivery();
        new Upgrade_Settings();
    }

    /**
     * Upgrade the pro.
     *
     * @return void
     */
    public function upgrade_pro() {
        if ( ! function_exists( 'wpcafe_pro' ) ) { 
            return;
        }

        new Upgrade_Table_Layout();
        new Upgrade_Discount();
    }
} 