<?php
namespace WpCafe\Upgrades\Upgrade_3_0_0;

/**
 * Class Upgrade_Module
 *
 * Handles the upgrade process for version 3.0.0.
 */
class Upgrade_Module {
    
    /**
     * Constructor for the Upgrade_Module class.
     *
     * Automatically triggers the upgrade process when an instance is created.
     */
    public function __construct() {
        $this->migrate_module_status();
    }

    /**
     * Upgrade the module.
     *
     * @return void
     */
    public function migrate_module_status() {
        $mini_cart = wpc_get_option( 'allow_mini_cart' ) == 'on' ? true : false;
        $discount  = wpc_get_option( 'wpc_pro_discount_enable' ) == 'on' ? true : false;
        $tipping   = wpc_get_option( 'wpc_pro_tip_enable' ) == 'on' ? true : false;
        $pickup    = wpc_get_option( 'wpc_pro_allow_pickup_date' ) == 'on' ? true : false;
        $delivery  = wpc_get_option( 'wpc_pro_allow_delivery_date' ) == 'on' ? true : false;

        $modules = [
            'mini_cart' => $mini_cart,
            'discount'  => $discount,
            'tipping'   => $tipping,
            'pickup'    => $pickup,
            'delivery'  => $delivery,
        ];
        
        foreach ( $modules as $module => $enable_module ) {
            if ( $enable_module ) {
                wpcafe_extension()->update( $module, 'on' );
            }
        }
    }
} 
