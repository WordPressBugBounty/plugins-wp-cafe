<?php
/**
 * Fires when the user deletes WP Cafe from the Plugins screen.
 * Clears onboarding flags so a reinstall re-triggers the onboarding flow.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$wpcafe_settings_key = 'wpcafe_reservation_settings_options';
$wpcafe_settings     = get_option( $wpcafe_settings_key, [] );

if ( is_array( $wpcafe_settings ) ) {
    unset(
        $wpcafe_settings['onboarding_completed'],
        $wpcafe_settings['onboarding_init'],
        $wpcafe_settings['onboard_setup']
    );
    update_option( $wpcafe_settings_key, $wpcafe_settings );
}

delete_option( 'wpcafe_install_fingerprint' );
