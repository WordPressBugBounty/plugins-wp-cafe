<?php
namespace WpCafe\Rollback;

if ( ! defined( 'ABSPATH' ) ) exit;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Rollback Service
 *
 * Responsible only for registering related classes and toggling the feature.
 */
class Rollback_Service implements Hookable_Service_Contract {

    /**
     * Register rollback classes.
     *
     * @return void
     */
    public function register() {
        add_action( 'admin_post_wpcafe_rollback', [ $this, 'post_wpcafe_rollback' ] );
    }

    /**
     * WpCafe version rollback.
     *
     * Rollback to previous WpCafe version.
     *
     * Fired by `admin_post_wpcafe_rollback` action.
     *
     * @since 1.5.0
     * @access public
     */
    public function post_wpcafe_rollback() {
        check_admin_referer( 'wpcafe_rollback' );

        if ( ! $this->can_user_rollback_versions() ) {
            wp_die( esc_html__( 'Not allowed to rollback versions', 'wp-cafe' ) );
        }

        $rollback_versions = $this->get_rollback_versions();
        $version = ! empty( $_GET['version'] ) ? sanitize_text_field( wp_unslash( $_GET['version'] ) ) : '';

        if ( empty( $version ) || ! in_array( $version, $rollback_versions, true ) ) {
        	wp_die( esc_html__( 'An error occurred, the selected version is invalid. Try selecting different version.', 'wp-cafe' ) );
        }

        $this->rollback_settings();

        $plugin_slug = 'wp-cafe';

        $rollback = new Rollback( [
            'version' => $version,
            'plugin_name' => plugin_basename(WPCAFE_FILE),
            'plugin_slug' => $plugin_slug,
            'package_url' => sprintf( 'https://downloads.wordpress.org/plugin/%s.%s.zip', $plugin_slug, $version ),
        ] );

        $rollback->run();

        wp_die(
            '', esc_html__( 'Rollback to Previous Version', 'wp-cafe' ), [
                'response' => 200,
            ]
        );
    }

    /**
     * Check if the current user can access the version control tab and rollback versions.
     *
     * @return bool
     */
    public function can_user_rollback_versions() {
        return current_user_can( 'activate_plugins' ) && current_user_can( 'update_plugins' );
    }

    /**
     * Get version list
     *
     * @return  array
     */
    public function get_rollback_versions() {
        $rollback_versions = get_transient( 'wpcafe_rollback_versions_' . WPCAFE_VERSION );

        if ( ! $rollback_versions ) {
            return [];
        }

        return $rollback_versions;
    }

    /**
     * Rollback settings
     *
     * @return  void
     */
    public function rollback_settings() {
        $old_settings = wpc_get_option('wpcafe_old_settings');

        if ( $old_settings ) {
            update_option('wpcafe_reservation_settings_options', $old_settings);
        }
    }
}
