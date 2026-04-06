<?php
namespace WpCafe\Upgrades\Upgrade_3_0_0;

use WpCafePro\Models\Discount_Model;
use Exception;

/**
 * Class Upgrade_Discount
 *
 * Handles the upgrade process for version 3.0.0.
 */
class Upgrade_Discount {

    /**
     * Constructor for the Upgrade_Discount class.
     *
     * Automatically triggers the upgrade process when an instance is created.
     */
    public function __construct() {
        $this->migrate_discount();
    }

    /**
     * Upgrade the discount.
     *
     * @return void
     */
    public function migrate_discount() {
        if ( ! class_exists(Discount_Model::class) ) {
            return ;
        }

        // Explicitly load the Discount_Model class file to ensure proper initialization
        if ( defined( 'WPCAFE_PRO_FILE' ) ) {
            $discount_model_file = plugin_dir_path( WPCAFE_PRO_FILE ) . 'base/models/discount-model.php';
            if ( file_exists( $discount_model_file ) ) {
                require_once $discount_model_file;
            } else {
                return;
            }
        }

        // Guard against missing functions during upgrade
        if ( ! function_exists( 'wpc_get_option' ) ) {
            return;
        }

        $products        = wpc_get_option( 'wpc_pro_include_menu' );
        $categories      = wpc_get_option( 'wpc_pro_include_cat' );
        $discount_amount = wpc_get_option( 'wpc_pro_discount_percentage' );

        $date_rule = [
            'type'       => 'date_range',
            'start_date' => wp_date( 'Y-m-d' ),
            'end_date'   => wp_date( 'Y-m-d' )
        ];

        $data = [
            'discount_type'     => 'percentage',
            'discount_amount'   => $discount_amount,
            'discount_title'    => 'Discount (migrated)',
            'products'          => $products,
            'categories'        => $categories,
            'discount_status'   => 'active',
            'date_rule'         => $date_rule,
        ];

        try {
            Discount_Model::create( $data );
        } catch ( Exception $e ) {
            // Silent fails even if discounts arent migrated.
        }
    }
}
