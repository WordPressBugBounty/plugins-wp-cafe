<?php
/**
 * Fires when the user deletes WP Cafe from the Plugins screen.
 * Clears onboarding flags so a reinstall re-triggers the onboarding flow.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$settings_key = 'wpcafe_reservation_settings_options';
$settings     = get_option( $settings_key, [] );

if ( is_array( $settings ) ) {
    unset(
        $settings['onboarding_completed'],
        $settings['onboarding_init'],
        $settings['onboard_setup']
    );
    update_option( $settings_key, $settings );
}

delete_option( 'wpcafe_install_fingerprint' );
