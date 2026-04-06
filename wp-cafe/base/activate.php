<?php
namespace WpCafe;
use WpCafe\Onboard\Onboarding;

/**
 * Activation class
 *
 * @package WpCafe
 */
class Activate {
    /**
     * Trigger on plugin activation hook
     *
     * @return  void
     */
    public static function run(): void {
        $stored_version = get_option( 'wpc_cafe_version' );
        if ( ! get_option( 'wpcafe_install_fingerprint' ) && ! $stored_version ) {
            update_option( 'wpcafe_install_fingerprint', WPCAFE_VERSION );
        }

        // Initialize onboarding
        Onboarding::onboarding_init();
    }
}
