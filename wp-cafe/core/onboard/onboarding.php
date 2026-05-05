<?php
/**
 * Manage onboarding
 *
 * @package WpCafe/Onboard
 */

namespace WpCafe\Onboard;

use WpCafe\Settings;
use WP_Error;

/**
 * Class Onboarding
 */
class Onboarding {
    /**
     * Store onboarding fields
     *
     * @var []
     */
    protected static $fields = [
        'restaurant_name'               => '',
        'restaurant_email'              => '',
        'restaurant_type'               => '',
        'order_type'                    => '',
        'enable_order_notification'     => '',
        'enable_order_tip'              => '',
        'table_layout_configuaration'   => '',
        'reservation_multi_slot'        => '',
        'onboard_setup'                 => '',
    ];

    /**
     * Setup onboarding profile
     *
     * @param array $data Onboarding data
     *
     * @return  bool | WP_Error
     */
    public static function update( $data = [] ) {
        if ( $data ) {
            foreach ( $data as $key => $value ) {
                if ( ! array_key_exists( $key, self::$fields ) ) {
                    return new WP_Error(
                        'invalid_field',
                        // translators: %s is the name of the invalid field.
                        sprintf( __( 'Invalid field %s', 'wp-cafe' ), $key ),
                        [ 'status' => 422 ]
                    );
                }
            }
        }

        return Settings::update( $data );
    }

    /**
     * Get onboard field
     *
     * @param string $key Onboarding field
     *
     * @return  mixed
     */
    public static function get( $key ) {
        if ( ! array_key_exists( $key, self::$fields ) ) {
            return new WP_Error(
                'invalid_field',
                // translators: %s is the name of the invalid field.
                sprintf( __( 'Invalid field %s', 'wp-cafe' ), $key ),
                [ 'status' => 422 ]
            );
        }

        return Settings::get( $key );
    }

    /**
     * Check if onboarding has been completed
     *
     * @return  bool
     */
    public static function is_onboarding_completed() {
        return wpc_get_option( 'onboarding_completed', false );
    }

    /**
     * Redirect to onboarding
     *
     * @return  void
     */
    public static function redirect_to_onboarding() {
        if ( self::is_onboarding_completed() ) {
            return;
        }

        wp_safe_redirect( admin_url( 'admin.php?page=wpcafe#/onboard' ) );
        exit;
    }

    /**
     * Mark onboarding as completed and clear the init flag.
     *
     * @return void
     */
    public static function complete() {
        wpc_update_option( 'onboarding_completed', true );
        wpc_update_option( 'onboarding_init', false );
        wpc_update_option( 'onboard_setup', true );
    }

    /**
     * Initialize onboarding
     *
     * @return  void
     */
    public static function onboarding_init() {
        if ( self::is_onboarding_completed() ) {
            wpc_update_option( 'onboarding_init', false );
        } else {
            wpc_update_option( 'onboarding_init', true );
        }
    }
}
