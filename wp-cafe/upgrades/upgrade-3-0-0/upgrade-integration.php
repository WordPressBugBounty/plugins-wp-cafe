<?php
namespace WpCafe\Upgrades\Upgrade_3_0_0;

use WpCafePro\Models\Discount_Model;

/**
 * Class Upgrade_Integration
 *
 * Handles the upgrade process for version 3.0.0.
 */
class Upgrade_Integration {
    
    /**
     * Constructor for the Upgrade_Integration class.
     *
     * Automatically triggers the upgrade process when an instance is created.
     */
    public function __construct() {
        $this->migrate_integration();
    }

    /**
     * Upgrade the integration.
     *
     * @return void
     */
    public function migrate_integration() {
        $zapier = wpc_get_option( 'wpc_allow_zapier' ) == 'on' ? true : false;
        $pabbly = wpc_get_option( 'wpc_allow_pabbly' ) == 'on' ? true : false;

        $integrations = [
            'zapier' => $zapier,
            'pabbly' => $pabbly,
        ];

        foreach ( $integrations as $integration => $status ) {
            if ( $status ) {
                wpc_integration()->update( $integration, 'on' );
            }
        }
    }
} 
