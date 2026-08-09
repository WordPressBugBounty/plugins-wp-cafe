<?php
namespace WpCafe\Extensions\Controllers;

use Arraytics\ToolsSdk\PluginManager;
use WP_REST_Server;
use WpCafe\Abstract\Base_Rest_Controller;
use WP_Error;

/**
* Plugin_Controller class. Handles extension related REST API requests.
*
* @package WpCafe/Settings/Controllers
*/
class Plugin_Controller extends Base_Rest_Controller {
    /**
     * Store the namespace for the REST API.
     *
     * @var string
     */
    protected $namespace = 'wpcafe/v2';

    /**
     * Store the REST base for the API.
     *
     * @var string
     */
    protected $rest_base = 'plugins';

    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route( $this->namespace, $this->rest_base, [
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'update_item'],
                'permission_callback' => function () {
                    return current_user_can( 'manage_options' );
                },
            ],
        ] );

    }

    /**
     * Enable or disable plugin
     *
     * @param   WP_Rest_Request  $request  [$request description]
     *
     * @return  WP_Response | WP_Error
     */
    public function update_item( $request ) {
        $input_data = json_decode( $request->get_body(), true );

        $name   = ! empty( $input_data['name'] ) ? sanitize_text_field( $input_data['name'] ) : '';
        $status = ! empty( $input_data['status'] ) ? sanitize_text_field( $input_data['status'] ) : '';

        $statuses = ['install', 'activate', 'deactivate'];

        if ( ! $name ) {
            return $this->error( __( 'Please enter plugin name', 'wp-cafe' ) );
        }

        if (  ! $status ) {
            return $this->error( __( 'Please plugin enter status', 'wp-cafe' ) );
        }

        if ( ! in_array( $status, $statuses ) ) {
            return $this->error( __( 'Invalid status', 'wp-cafe' ) );
        }

        $plugin = wpcafe_extension()->find( $name );
        $deps = ! empty( $plugin['deps'] ) ? $plugin['deps'] : [];

        if ( $deps ) {
            foreach ( $deps as $dep ) {
                if ( ! PluginManager::is_installed( $dep ) ) {
                    /* translators: %s: plugin name */
                    return $this->error( sprintf( __( 'Dependency plugin %s is not installed', 'wp-cafe' ), $dep ) );
                }
            }
        }

        $our_plugins  = function_exists( 'wpcafe_our_plugins_list' ) ? wpcafe_our_plugins_list() : [];
        $slug         = isset( $our_plugins[ $name ]['slug'] ) ? $our_plugins[ $name ]['slug'] : $name;

        // About Us "Our Plugins" carry their own download_url and win over the
        // extension-list entry, so a non-wporg URL there isn't shadowed by a
        // same-named module entry. Falls back to extension-list, then wporg slug.
        $download_url = ! empty( $our_plugins[ $name ]['download_url'] )
            ? $our_plugins[ $name ]['download_url']
            : ( ! empty( $plugin['download_url'] ) ? $plugin['download_url'] : null );

        $update = false;

        if ( $status === 'install' ) {
            $install_result = $this->install_plugin_package( $slug, $download_url );

            if ( is_wp_error( $install_result ) ) {
                return $this->error( $install_result->get_error_message() );
            }

            $update = $install_result;
        }

        if ( $status === 'activate' ) {
            if ( ! PluginManager::is_installed( $slug ) ) {
                $install_result = $this->install_plugin_package( $slug, $download_url );

                if ( is_wp_error( $install_result ) ) {
                    return $this->error( $install_result->get_error_message() );
                }

                if ( ! $install_result ) {
                    return $this->error( __( 'Plugin installation failed.', 'wp-cafe' ) );
                }
            }

            $update = PluginManager::activate_plugin( $slug );
        }

        if ( $status === 'deactivate' && PluginManager::is_activated( $slug ) ) {
            $update = PluginManager::deactivate_plugin( $slug );
        }

        if ( ! $update ) {
            /* translators: %s: action status (install, activate, or deactivate) */
            return $this->error( sprintf( __( 'Plugin couldn\'t %s', 'wp-cafe' ), $status ) );
        }

        $response = [
            'message' => __( 'Successfully updated', 'wp-cafe' ),
        ];

        /*
         * Registration only runs when the caller sent explicit consent, which
         * today means the onboarding checkbox or the dashboard banner button.
         * Activating from Modules or About Us installs the plugin and stops
         * there - no identity leaves the site without the user opting in.
         */
        if ( 'aisentic' === $name && 'activate' === $status && ! empty( $input_data['consent'] ) && PluginManager::is_activated( $slug ) ) {
            $this->register_aisentic_site();

            // The banner needs to know whether the handshake actually landed so
            // it can show an error instead of silently disappearing.
            $response['aisentic_registered'] = wpc_aisentic_is_registered();
        }

        return $this->response( $response );
    }

    /**
     * Record the user's consent and hand the identity to Aisentic.
     *
     * Values come from wpc_aisentic_identity() so they match what the consent
     * UI showed. Aisentic swallows provider errors and skips the call when it
     * already has an api key, so this never affects the activation response.
     *
     * @return void
     */
    private function register_aisentic_site() {
        // Older Aisentic builds have no listener for the action below, so the
        // handshake would go nowhere. Skip instead of storing consent for a
        // registration that cannot happen.
        if ( ! class_exists( 'Aisentic\Api\Services\Registration_Service' ) ) {
            return;
        }

        $identity = wpc_aisentic_identity();

        // No email means nothing to register with, and Aisentic would reject
        // the call anyway. Fail closed rather than inventing a value.
        if ( empty( $identity['email'] ) ) {
            return;
        }

        // Proof of consent: who agreed, when, and for which email. Also lets
        // the banner tell "declined" apart from "never asked".
        update_option(
            'wpcafe_aisentic_consent',
            [
                'agreed'  => true,
                'time'    => gmdate( 'c' ),
                'user_id' => get_current_user_id(),
                'email'   => $identity['email'],
            ],
            false
        );

        /**
         * Fires after the user opts in to connecting the site with Aisentic.
         *
         * Aisentic's WpCafe integration listens for this, registers the site
         * with its provider and marks itself connected.
         *
         * @param string $account_name Restaurant/account name.
         * @param string $email        Restaurant/account email.
         * @param string $site_url     Site URL to register with the provider.
         */
        do_action( 'wpcafe/aisentic/register_site', $identity['name'], $identity['email'], $identity['site_url'] );
    }

    /**
     * Install a plugin package from a direct download URL or WordPress.org.
     *
     * @param string      $slug         Plugin slug.
     * @param string|null $download_url Optional direct download URL.
     *
     * @return bool|\WP_Error True on success, false on failure, WP_Error on invalid URL.
     */
    private function install_plugin_package( $slug, $download_url = null ) {
        if ( PluginManager::is_installed( $slug ) ) {
            return true;
        }

        if ( $download_url ) {
            // Validate download URL scheme and host before installing.
            $parsed          = wp_parse_url( $download_url );
            $allowed_domains = [ 'wordpress.org', 'arraytics.com', 'themewinter.com', 'github.com' ];
            if ( ( $parsed['scheme'] ?? '' ) !== 'https' || ! in_array( $parsed['host'] ?? '', $allowed_domains, true ) ) {
                return new \WP_Error(
                    'invalid_download_url',
                    __( 'Download URL must use HTTPS from a trusted domain.', 'wp-cafe' )
                );
            }

            include_once ABSPATH . 'wp-admin/includes/file.php';
            include_once ABSPATH . 'wp-admin/includes/misc.php';
            include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

            $skin     = new \Automatic_Upgrader_Skin();
            $upgrader = new \Plugin_Upgrader( $skin );

            return $upgrader->install( $download_url ) ? true : false;
        }

        return PluginManager::install_plugin( $slug );
    }
}
