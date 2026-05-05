<?php
namespace WpCafe\Onboard;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;
use WpCafe\Models\Location_Model;
/**
 * Onboard Setup
 *
 * @package WpCafe/Onboard
 */
class Onboard_Setup implements Hookable_Service_Contract {
    /**
     * Register hooks
     *
     * @return  void
     */
    public function register() {
        add_action( 'admin_init', [ self::class, 'redirect_to_onboarding' ] );
        add_filter('wpcafe_settings', [$this, 'create_initial_location']);
    }   

    /**
     * Initialize onboarding
     *
     * @return  void
     */
    public static function init() {
        add_action( 'admin_init', [ self::class, 'redirect_to_onboarding' ] );
    }   

    /**
     * Redirect to onboarding
     *
     * @return  void
     */
    public static function redirect_to_onboarding() {
        if ( ! wpc_get_option( 'onboarding_init', false ) ) {
            return;
        }

        if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
            return;
        }

        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
        if ( 'wpcafe' === $page ) {
            return;
        }

        Onboarding::redirect_to_onboarding();
    }

    /**
     * Create initial location
     *
     * @return  void
     */
    public function create_initial_location( $data ) {
        if ( ! isset( $data['restaurant_type'] ) ) {
            return $data;
        }

        $restaurant_name    = ! empty( $data['restaurant_name'] ) ? $data['restaurant_name'] : '';
        $restaurant_email   = ! empty( $data['restaurant_email'] ) ? $data['restaurant_email'] : '';
        $restaurant_phone   = ! empty( $data['restaurant_phone'] ) ? $data['restaurant_phone'] : '';
        $restaurant_location = ! empty( $data['restaurant_location'] ) ? $data['restaurant_location'] : '';

        $location = Location_Model::create( $restaurant_name, [
            'email'        => $restaurant_email,
            'phone'        => $restaurant_phone,
            'location'     => $restaurant_location,
        ] );

        return $data;
    }
}

